<?php
/**
 * FileSecurityHelper - Validación y Manejo Seguro de Archivos Subidos
 * Previene ejecución remota de código (RCE), subida de shells, path traversal y evasión de tipos.
 */
class FileSecurityHelper {

    // Lista blanca estricta de extensiones permitidas
    private static $allowedExtensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
        'jpg', 'jpeg', 'png', 'webp',
        'zip', 'rar'
    ];

    // Mapa de extensiones a MIME types válidos
    private static $allowedMimes = [
        'pdf'  => ['application/pdf', 'application/x-pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/plain', 'text/csv', 'application/vnd.ms-excel', 'application/csv'],
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png', 'image/x-png'],
        'webp' => ['image/webp'],
        'zip'  => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'rar'  => ['application/x-rar-compressed', 'application/octet-stream', 'application/vnd.rar']
    ];

    // Extensiones prohibidas de alto riesgo
    private static $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'inc',
        'cgi', 'pl', 'py', 'sh', 'bash', 'exe', 'bat', 'cmd', 'msi', 'vbs',
        'js', 'html', 'htm', 'shtml', 'svg', 'asp', 'aspx', 'jsp', 'htaccess'
    ];

    /**
     * Valida y sube un archivo de forma completamente segura.
     * 
     * @param array  $fileEntry     Elemento de $_FILES (ej. $_FILES['documento'])
     * @param string $destinationDir Directorio destino (ej. 'uploads/oportunidades/')
     * @param array  $options        Opciones adicionales (max_size, prefix, allowed_extensions)
     * @return array                ['success' => bool, 'path' => string, 'filename' => string, 'orig_name' => string, 'error' => string]
     */
    public static function validateAndUpload(array $fileEntry, string $destinationDir, array $options = []): array {
        // 1. Verificar error básico de subida de PHP
        if (!isset($fileEntry['error']) || is_array($fileEntry['error'])) {
            return ['success' => false, 'error' => 'Parámetros de subida inválidos.'];
        }

        if ($fileEntry['error'] !== UPLOAD_ERR_OK) {
            $errorMsgs = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo supera el tamaño máximo permitido por el servidor.',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el tamaño máximo permitido por el formulario.',
                UPLOAD_ERR_PARTIAL    => 'El archivo se subió solo parcialmente.',
                UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco.',
                UPLOAD_ERR_EXTENSION  => 'Una extensión del servidor detuvo la subida.'
            ];
            return ['success' => false, 'error' => $errorMsgs[$fileEntry['error']] ?? 'Error al subir el archivo.'];
        }

        // 2. Verificar tamaño máximo (por defecto 25MB)
        $maxSize = $options['max_size'] ?? (defined('MAX_UPLOAD_FILE_SIZE') ? MAX_UPLOAD_FILE_SIZE : 25 * 1024 * 1024);
        if ($fileEntry['size'] > $maxSize) {
            return ['success' => false, 'error' => 'El archivo supera el límite de ' . round($maxSize / (1024 * 1024)) . 'MB.'];
        }

        if ($fileEntry['size'] <= 0) {
            return ['success' => false, 'error' => 'El archivo está vacío.'];
        }

        // 3. Sanitizar y validar nombre de archivo original
        $origName = basename($fileEntry['name']);
        
        // Prevenir null bytes y secuencias de escape
        if (strpos($origName, "\0") !== false || strpos($origName, '..') !== false) {
            return ['success' => false, 'error' => 'Nombre de archivo potencialmente malicioso.'];
        }

        // 4. Extraer y validar extensión
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (empty($ext)) {
            return ['success' => false, 'error' => 'El archivo no tiene una extensión válida.'];
        }

        $allowedExts = $options['allowed_extensions'] ?? (defined('ALLOWED_FILE_EXTENSIONS') ? ALLOWED_FILE_EXTENSIONS : self::$allowedExtensions);
        if (!in_array($ext, $allowedExts, true)) {
            return ['success' => false, 'error' => 'Tipo de archivo no permitido (.' . htmlspecialchars($ext) . '). Solo se aceptan documentos (PDF, Office), imágenes y archivos de datos.'];
        }

        // 5. Protección contra doble extensión (ej. archivo.php.pdf o archivo.phtml.jpg)
        $nameParts = explode('.', strtolower($origName));
        if (count($nameParts) > 2) {
            array_pop($nameParts); // Quitar la última extensión (ya validada)
            foreach ($nameParts as $part) {
                if (in_array($part, self::$dangerousExtensions, true)) {
                    return ['success' => false, 'error' => 'Archivo rechazado: Contiene extensiones ejecutables intermedias no permitidas.'];
                }
            }
        }

        // 6. Validar tipo MIME real inspeccionando el contenido binario (finfo)
        $tmpPath = $fileEntry['tmp_name'];
        if (!is_uploaded_file($tmpPath)) {
            return ['success' => false, 'error' => 'El archivo cargado no proviene de una subida legítima HTTP POST.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = $finfo ? finfo_file($finfo, $tmpPath) : mime_content_type($tmpPath);
        if ($finfo) finfo_close($finfo);

        if (!empty(self::$allowedMimes[$ext])) {
            $validMimesForExt = self::$allowedMimes[$ext];
            if (!in_array($realMime, $validMimesForExt, true)) {
                // Si es un archivo de texto o csv, verificar que sea texto
                if ($ext === 'csv' || $ext === 'txt') {
                    if (strpos($realMime, 'text/') !== 0 && $realMime !== 'application/csv') {
                        return ['success' => false, 'error' => 'El contenido del archivo no coincide con un formato de texto válido.'];
                    }
                } else {
                    return ['success' => false, 'error' => 'El contenido real del archivo (' . htmlspecialchars($realMime) . ') no coincide con su extensión .' . htmlspecialchars($ext)];
                }
            }
        }

        // 7. Inspección profunda de contenido: Rechazar archivos con código PHP/Script embebido
        $buffer = @file_get_contents($tmpPath, false, null, 0, 4096);
        if ($buffer !== false) {
            if (preg_match('/<\?php|<\?=|<script\s+language\s*=\s*["\']?php/i', $buffer)) {
                return ['success' => false, 'error' => 'Archivo rechazado: Contiene código ejecutable o scripts prohibidos.'];
            }
        }

        // 8. Crear directorio destino con permisos seguros si no existe
        $destinationDir = rtrim($destinationDir, '/\\') . '/';
        if (!is_dir($destinationDir)) {
            if (!@mkdir($destinationDir, 0755, true) && !is_dir($destinationDir)) {
                return ['success' => false, 'error' => 'No se pudo crear el directorio de destino.'];
            }
        }

        // Asegurar que exista un .htaccess bloqueador en la carpeta de subidas
        $uploadsRoot = dirname(__DIR__) . '/uploads';
        if (is_dir($uploadsRoot) && !file_exists($uploadsRoot . '/.htaccess')) {
            @file_put_contents($uploadsRoot . '/.htaccess', "# Proteccion automatica\nRemoveHandler .php .phtml .phar\nphp_flag engine off\nOptions -Indexes -ExecCGI\n");
        }

        // 9. Generar nombre de archivo único y seguro
        $prefix = isset($options['prefix']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $options['prefix']) . '_' : '';
        $safeFileName = date('Ymd_His') . '_' . $prefix . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetFile = $destinationDir . $safeFileName;

        // 10. Mover archivo a su ubicación final
        if (!move_uploaded_file($tmpPath, $targetFile)) {
            return ['success' => false, 'error' => 'No se pudo mover el archivo al almacenamiento final.'];
        }

        // Establecer permisos seguros de solo lectura/escritura (sin ejecución)
        @chmod($targetFile, 0644);

        $cleanOrigName = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $origName);

        return [
            'success'   => true,
            'path'      => $targetFile,
            'filename'  => $safeFileName,
            'orig_name' => $cleanOrigName,
            'ext'       => $ext,
            'mime'      => $realMime,
            'size'      => $fileEntry['size']
        ];
    }
}
