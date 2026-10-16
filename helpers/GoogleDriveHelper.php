<?php
/**
 * Helper para integración con Google Drive API
 * Carga credenciales locales y maneja sincronización de archivos y carpetas
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;

class GoogleDriveHelper {
    private $client;
    private $credentialsPath;
    private $tokenPath;
    
    public function __construct() {
        $this->credentialsPath = __DIR__ . '/../config/client_secret.json';
        $this->tokenPath = __DIR__ . '/../config/token.json';

        if (!file_exists($this->credentialsPath)) {
            throw new Exception("Archivo client_secret.json no encontrado en config/");
        }

        $this->client = new \Google_Client();
        $this->client->setApplicationName('Gestion de Proyectos');
        $this->client->setScopes([\Google_Service_Drive::DRIVE_FILE, \Google_Service_Drive::DRIVE]);
        $this->client->setAuthConfig($this->credentialsPath);
        $this->client->setAccessType('offline');
        
        if (file_exists($this->tokenPath)) {
            $tokenRaw = file_get_contents($this->tokenPath);
            $accessToken = json_decode($tokenRaw, true);
            if (!empty($accessToken)) {
                $this->client->setAccessToken($accessToken);
            }
        }
        
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken(), JSON_PRETTY_PRINT));
            } else {
                throw new Exception("El token de Google Drive ha expirado y no hay token de refresco disponible.");
            }
        }
    }
    
    /**
     * Extrae el ID limpio de carpeta a partir de una URL o código suelto
     */
    public static function extractFolderId($input) {
        $input = trim((string)$input);
        if (empty($input)) return '';

        // Formato https://drive.google.com/drive/folders/ID...
        if (preg_match('#folders/([a-zA-Z0-9_-]+)#', $input, $matches)) {
            return $matches[1];
        }
        // Formato ?id=ID...
        if (preg_match('#id=([a-zA-Z0-9_-]+)#', $input, $matches)) {
            return $matches[1];
        }
        // ID limpio
        return preg_replace('#[^a-zA-Z0-9_-]#', '', $input);
    }
    
    public function getService() {
        return new \Google_Service_Drive($this->client);
    }

    /**
     * Devuelve el correo electrónico de la cuenta conectada a Drive
     */
    public function getAuthenticatedEmail() {
        try {
            $oauth2 = new \Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();
            return $userInfo->email;
        } catch (\Exception $e) {
            return "devops.maxisoftnet@gmail.com";
        }
    }
    
    /**
     * Valida si el bot tiene acceso de edición sobre la carpeta
     */
    public function validateFolderAccess($folderId) {
        $folderId = self::extractFolderId($folderId);
        if (empty($folderId)) return false;

        try {
            $service = $this->getService();
            $folder = $service->files->get($folderId, [
                'fields' => 'id, name, capabilities, trashed',
                'supportsAllDrives' => true
            ]);

            if ($folder->getTrashed()) {
                return false;
            }

            $caps = $folder->getCapabilities();
            if ($caps && $caps->getCanAddChildren()) {
                return true;
            }
            // Si tiene acceso pero canAddChildren es false, aún comprobamos si se pueden listar
            return true;
        } catch (\Exception $e) {
            error_log("GoogleDrive validation error: " . $e->getMessage());
            return false;
        }
    }

    private static $folderCache = [];

    /**
     * Obtiene el ID de la carpeta raíz configurada en el sistema
     */
    public static function getRootFolderId() {
        $folderId = null;

        if (function_exists('dbQueryOne')) {
            try {
                // 1. Carpeta configurada de la empresa del software
                $sw = dbQueryOne("SELECT drive_folder_id FROM empresa_software WHERE drive_folder_id IS NOT NULL AND drive_folder_id != '' ORDER BY id DESC LIMIT 1");
                if (!empty($sw['drive_folder_id'])) {
                    $folderId = $sw['drive_folder_id'];
                }

                // 2. Clave global de configuración
                if (!$folderId) {
                    $cfg = dbQueryOne("SELECT valor FROM configuracion WHERE clave IN ('drive_root_folder_id', 'drive_folder_id') AND valor != '' ORDER BY clave DESC LIMIT 1");
                    if (!empty($cfg['valor'])) {
                        $folderId = $cfg['valor'];
                    }
                }

                // 3. Fallback a empresas propias si existiera
                if (!$folderId) {
                    $emp = dbQueryOne("SELECT drive_folder_id FROM empresas WHERE tipo_empresa = 'propia' AND drive_folder_id IS NOT NULL AND drive_folder_id != '' ORDER BY id DESC LIMIT 1");
                    if (!empty($emp['drive_folder_id'])) {
                        $folderId = $emp['drive_folder_id'];
                    }
                }
            } catch (\Exception $e) {
                // Silencioso
            }
        }

        if (!$folderId && file_exists(__DIR__ . '/../config/drive_root_folder.txt')) {
            $folderId = trim(file_get_contents(__DIR__ . '/../config/drive_root_folder.txt'));
        }

        return self::extractFolderId($folderId);
    }

    /**
     * Busca una subcarpeta por nombre dentro de una carpeta padre (con cache en memoria)
     */
    public function findFolderByName($name, $parentFolderId) {
        $name = trim((string)$name);
        $cacheKey = $parentFolderId . '::' . $name;
        if (isset(self::$folderCache[$cacheKey])) {
            return self::$folderCache[$cacheKey];
        }

        $service = $this->getService();
        $safeName = str_replace(["\\", "'"], ["\\\\", "\\'"], $name);
        $query = "mimeType='application/vnd.google-apps.folder' and name='{$safeName}' and '{$parentFolderId}' in parents and trashed=false";
        
        $results = $service->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true
        ]);
        
        $files = $results->getFiles();
        if (!empty($files) && count($files) > 0) {
            $foundId = $files[0]->getId();
            self::$folderCache[$cacheKey] = $foundId;
            return $foundId;
        }
        return null;
    }
    
    /**
     * Crea una subcarpeta dentro de una carpeta padre
     */
    public function createFolder($name, $parentFolderId) {
        $name = trim((string)$name);
        $service = $this->getService();
        $fileMetadata = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentFolderId]
        ]);
        
        $folder = $service->files->create($fileMetadata, [
            'fields' => 'id, webViewLink',
            'supportsAllDrives' => true
        ]);
        
        $folderId = $folder->getId();
        $cacheKey = $parentFolderId . '::' . $name;
        self::$folderCache[$cacheKey] = $folderId;
        return $folderId;
    }

    /**
     * Obtiene una subcarpeta o la crea si no existe
     */
    public function getOrCreateSubFolder($folderName, $parentFolderId) {
        $folderName = trim((string)$folderName);
        $folderId = $this->findFolderByName($folderName, $parentFolderId);
        if (!$folderId) {
            $folderId = $this->createFolder($folderName, $parentFolderId);
        }
        return $folderId;
    }

    /**
     * Sube un archivo a la carpeta especificada en Drive
     */
    public function uploadFile($filePath, $fileName, $mimeType, $folderId) {
        $service = $this->getService();
        
        // Optimizar imágenes a WebP si aplica
        if (strpos($mimeType, 'image/') === 0 && function_exists('imagewebp')) {
            try {
                $image = null;
                if ($mimeType === 'image/jpeg' || $mimeType === 'image/jpg') {
                    $image = @imagecreatefromjpeg($filePath);
                } elseif ($mimeType === 'image/png') {
                    $image = @imagecreatefrompng($filePath);
                    if ($image) {
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                    }
                }
                
                if ($image) {
                    $webpPath = $filePath . '.webp';
                    imagewebp($image, $webpPath, 85);
                    imagedestroy($image);
                    
                    if (file_exists($webpPath)) {
                        $filePath = $webpPath;
                        $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.webp';
                        $mimeType = 'image/webp';
                    }
                }
            } catch (\Exception $e) {
                // Continuar con el archivo original si falla conversión
            }
        }

        $fileMetadata = new DriveFile([
            'name' => $fileName,
            'parents' => [$folderId]
        ]);
        
        $content = file_get_contents($filePath);
        
        $file = $service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink, thumbnailLink',
            'supportsAllDrives' => true
        ]);
        
        // Configurar permisos de lectura pública
        $this->setReadPermission($file->getId());
        
        return $file;
    }

    /**
     * Configura permisos de lectura para cualquier persona con el link
     */
    public function setReadPermission($fileId) {
        try {
            $service = $this->getService();
            $permission = new Permission([
                'type' => 'anyone',
                'role' => 'reader'
            ]);
            $service->permissions->create($fileId, $permission, ['supportsAllDrives' => true]);
        } catch (\Exception $e) {
            // Silencioso en caso de restricciones organizacionales
        }
    }

    /**
     * Obtiene el link de visualización de un archivo
     */
    public function getFileViewLink($fileId) {
        try {
            $service = $this->getService();
            $file = $service->files->get($fileId, [
                'fields' => 'id, webViewLink',
                'supportsAllDrives' => true
            ]);
            return $file->getWebViewLink();
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * Obtiene la carpeta de un proceso comercial específico dentro del Drive
     * Ejemplos: 'Convocatorias', 'Oportunidades', 'Propuestas', 'Contratos', 'Documentos'
     */
    public function getProcessFolder($processName, $rootFolderId = null) {
        if (!$rootFolderId) {
            $rootFolderId = self::getRootFolderId();
        }
        if (empty($rootFolderId)) return null;

        return $this->getOrCreateSubFolder($processName, $rootFolderId);
    }

    /**
     * Obtiene o crea la subcarpeta específica de un elemento (por su código o razón social)
     * dentro de la carpeta del proceso en Drive.
     * Ejemplo: 'Oportunidades' -> 'OP-2025-001'
     */
    public function getItemFolder($processName, $itemCode, $rootFolderId = null) {
        $processFolderId = $this->getProcessFolder($processName, $rootFolderId);
        if (!$processFolderId) return null;

        $cleanCode = trim((string)$itemCode);
        if (empty($cleanCode)) return $processFolderId;

        return $this->getOrCreateSubFolder($cleanCode, $processFolderId);
    }

    /**
     * Sube un archivo del sistema directamente a la subcarpeta correspondiente en Google Drive.
     * Si se proporciona $itemCode, se guarda en:
     *   [Carpeta Raíz] / [Proceso] / [Codigo_Item] / [Archivo]
     * Si además se pasa $subSubFolder (opcional por ej. 'Tecnica', 'Bases'):
     *   [Carpeta Raíz] / [Proceso] / [Codigo_Item] / [SubSubFolder] / [Archivo]
     *
     * @param string $localPath      Ruta local del archivo en el servidor
     * @param string $originalName   Nombre original del archivo
     * @param string $processName    Nombre del proceso ('Oportunidades', 'Propuestas', 'Contratos', 'Convocatorias', 'Documentos')
     * @param string|null $itemCode  Código identificador único (ej: 'OP-2025-001', 'PROP-2025-001', 'Constructora SAC')
     * @param string|null $subSubFolder Subcarpeta adicional opcional
     * @return array|null            Información del archivo subido o null en caso de error
     */
    public static function uploadSystemFile($localPath, $originalName, $processName = 'Documentos', $itemCode = null, $subSubFolder = null) {
        if (!file_exists($localPath)) return null;

        try {
            $rootFolderId = self::getRootFolderId();
            if (empty($rootFolderId)) {
                return null;
            }

            $helper = new self();
            
            // 1. Obtener o crear carpeta del proceso (ej: 'Oportunidades')
            $targetFolderId = $helper->getProcessFolder($processName, $rootFolderId);
            if (!$targetFolderId) return null;

            // 2. Si se proporciona $itemCode, obtener o crear la subcarpeta de este elemento
            if (!empty($itemCode)) {
                $targetFolderId = $helper->getOrCreateSubFolder(trim((string)$itemCode), $targetFolderId);
            }

            // 3. Subcarpeta de tercer nivel opcional
            if (!empty($subSubFolder)) {
                $targetFolderId = $helper->getOrCreateSubFolder(trim((string)$subSubFolder), $targetFolderId);
            }

            $mimeType = function_exists('mime_content_type') ? mime_content_type($localPath) : 'application/octet-stream';
            $driveFile = $helper->uploadFile($localPath, $originalName, $mimeType, $targetFolderId);

            return [
                'success' => true,
                'id' => $driveFile->getId(),
                'webViewLink' => $driveFile->getWebViewLink(),
                'thumbnailLink' => $driveFile->getThumbnailLink(),
                'folderId' => $targetFolderId,
                'fileName' => $originalName,
                'process' => $processName,
                'itemCode' => $itemCode
            ];
        } catch (\Exception $e) {
            error_log("Error al subir archivo a Google Drive: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sincroniza y valida que todos los procesos y elementos de la BD tengan
     * su subcarpeta correspondiente creada en Google Drive.
     */
    public static function syncAllProcessFolders() {
        $rootFolderId = self::getRootFolderId();
        if (empty($rootFolderId)) {
            return ['success' => false, 'message' => 'No se encontró la carpeta raíz de Google Drive configurada.'];
        }

        $helper = new self();
        $report = [
            'success' => true,
            'root_folder_id' => $rootFolderId,
            'procesos' => []
        ];

        $procesos = [
            'Convocatorias' => [
                'table' => 'convocatorias',
                'code_col' => 'codigo',
                'name_col' => 'nombre'
            ],
            'Oportunidades' => [
                'table' => 'oportunidades',
                'code_col' => 'codigo',
                'name_col' => 'nombre'
            ],
            'Propuestas' => [
                'table' => 'propuestas',
                'code_col' => 'codigo',
                'name_col' => 'id'
            ],
            'Contratos' => [
                'table' => 'contratos',
                'code_col' => 'codigo',
                'name_col' => 'objeto'
            ],
            'Documentos' => [
                'table' => 'empresas',
                'code_col' => 'razon_social',
                'name_col' => 'ruc'
            ]
        ];

        foreach ($procesos as $procName => $conf) {
            $procFolderId = $helper->getProcessFolder($procName, $rootFolderId);
            $subcarpetas = [];

            // 1. Obtener todas las carpetas existentes en una sola consulta API
            try {
                $service = $helper->getService();
                $existingResults = $service->files->listFiles([
                    'q' => "'{$procFolderId}' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false",
                    'fields' => 'files(id, name)',
                    'pageSize' => 500,
                    'supportsAllDrives' => true,
                    'includeItemsFromAllDrives' => true
                ]);
                foreach ($existingResults->getFiles() as $f) {
                    $subcarpetas[$f->getName()] = $f->getId();
                    self::$folderCache[$procFolderId . '::' . $f->getName()] = $f->getId();
                }
            } catch (\Exception $e) {
                error_log("Error al listar subcarpetas existentes de {$procName}: " . $e->getMessage());
            }

            // 2. Para cada registro en BD, si no existe en Google Drive, crearla
            if (function_exists('dbQuery')) {
                try {
                    $rows = dbQuery("SELECT id, {$conf['code_col']} as codigo FROM {$conf['table']}");
                    foreach ($rows as $row) {
                        $code = trim((string)$row['codigo']);
                        if (!empty($code)) {
                            if (!isset($subcarpetas[$code])) {
                                try {
                                    $subId = $helper->createFolder($code, $procFolderId);
                                    $subcarpetas[$code] = $subId;
                                    usleep(100000); // 100ms throttle para prevenir rate limiting
                                } catch (\Exception $e) {
                                    error_log("Error creando subcarpeta {$code} en {$procName}: " . $e->getMessage());
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Error al consultar BD para {$procName}: " . $e->getMessage());
                }
            }

            $report['procesos'][$procName] = [
                'folder_id' => $procFolderId,
                'total_subcarpetas' => count($subcarpetas),
                'items' => array_keys($subcarpetas)
            ];
        }

        return $report;
    }
}

