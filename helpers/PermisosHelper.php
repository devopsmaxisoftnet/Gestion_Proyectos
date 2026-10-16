<?php
/**
 * Motor Central de Autorización y Permisos Jerárquicos
 * Gestión de Proyectos - ERP Core de Seguridad
 */
require_once __DIR__ . '/../config/database.php';

class PermisosHelper {
    private static $userPermisosCache = null;
    private static $userAreasHijasCache = null;

    /**
     * Valida si el usuario en sesión tiene permiso para ejecutar una acción en un módulo.
     * Soporta validación jerárquica por área y permisos temporales delegados.
     *
     * @param string $accion Acción: 'ver', 'crear', 'editar', 'eliminar', 'aprobar', 'exportar'
     * @param string $modulo Módulo: 'oportunidades', 'contratos', 'compras', 'organizacion', etc.
     * @param int|null $recursoAreaId ID del área organizacional a la que pertenece el recurso (opcional)
     * @return bool
     */
    public static function puede(string $accion, string $modulo, ?int $recursoAreaId = null): bool {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) { session_start(); }
        
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return false;
        }

        $rol = $user['rol'] ?? 'usuario';

        // 1. Superadmin y Administrador tienen acceso total
        if (in_array($rol, ['superadmin', 'admin'])) {
            return true;
        }

        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            return false;
        }

        // Cargar permisos en caché de sesión/memoria si no están cargados
        self::cargarPermisosUsuario($userId);

        // 2. Verificar permiso base (asignado al cargo o delegado)
        $clavePermiso = strtolower(trim($modulo)) . '.' . strtolower(trim($accion));
        $tienePermisoBase = in_array($clavePermiso, self::$userPermisosCache['permisos'] ?? []);

        if (!$tienePermisoBase) {
            // Verificar si tiene comodín global del módulo (ej: modulo.*)
            $claveComodin = strtolower(trim($modulo)) . '.*';
            $tienePermisoBase = in_array($claveComodin, self::$userPermisosCache['permisos'] ?? []);
        }

        if (!$tienePermisoBase) {
            return false;
        }

        // 3. Validación de Jerarquía Organizacional y Áreas si se especifica recurso
        if ($recursoAreaId !== null && $recursoAreaId > 0) {
            $userNivel = self::$userPermisosCache['nivel_jerarquico'] ?? 99;
            $userAreaId = self::$userPermisosCache['org_area_id'] ?? null;

            // Nivel 1 (CEO / Dirección): Acceso total a todas las áreas
            if ($userNivel <= 1) {
                return true;
            }

            // Si el recurso pertenece directamente al área del usuario
            if ($userAreaId && (int)$userAreaId === (int)$recursoAreaId) {
                return true;
            }

            // Nivel 2 (Gerente de Área): Puede ver y gestionar su área y todas sus sub-áreas
            if ($userNivel == 2) {
                $areasPermitidas = self::getAreasDescendientes($userAreaId);
                if (in_array((int)$recursoAreaId, $areasPermitidas)) {
                    return true;
                }
            }

            // Verificar si tiene delegación temporal específica para esta área
            $areasDelegadas = self::$userPermisosCache['areas_delegadas'][$clavePermiso] ?? [];
            if (in_array((int)$recursoAreaId, $areasDelegadas) || in_array(0, $areasDelegadas)) {
                return true;
            }

            // Acceso denegado por restricción de área (Anti-tampering)
            return false;
        }

        return true;
    }

    /**
     * Mapeo canónico entre módulos operativos y códigos de área organizacional
     */
    private static $moduloAreaMap = [
        'convocatorias'  => 'LIC',
        'oportunidades'  => 'LIC',
        'licitaciones'   => 'LIC',
        'propuestas'     => 'LIC',
        'clientes'       => 'LIC',
        'profesionales'  => 'LIC',
        'contratos'      => 'EJEC',
        'proyectos'      => 'EJEC',
        'tareas'         => 'EJEC',
        'edt'            => 'EJEC',
        'asistencias'    => 'RRHH',
        'personal'       => 'RRHH',
        'compras'        => 'LOG',
        'almacenes'      => 'LOG',
        'proveedores'    => 'LOG',
        'subcontratos'   => 'LOG',
        'materiales'     => 'LOG',
        'equipos'        => 'LOG',
        'tesoreria'      => 'TES',
        'cuentas'        => 'TES',
        'facturacion'    => 'CONT',
        'contabilidad'   => 'CONT',
        'usuarios'       => 'GAF',
        'organizacion'   => 'GG',
        'empresas'       => 'GAF',
        'configuracion'  => 'GG'
    ];

    /**
     * Verifica si el usuario actual tiene acceso a un área o módulo según su Rol/Nivel/Área.
     * Usado en el Menú Dinámico y en el Candado de Controladores.
     *
     * Reglas del Organigrama:
     * - Nivel 0 (CEO / Admin): Acceso total a todo el sistema.
     * - Nivel 1 (Gerentes, ej. Gerente de Proyectos): Acceso a sus áreas hijas (Licitaciones, Consultoría, Ejecuciones).
     * - Nivel 2 (Jefes de Área, ej. Jefe de Licitaciones): Acceso ESTRICTO solo a su módulo (Licitaciones).
     * - Nivel 3 (Asistentes/Operativos): Acceso solo a tareas operativas de su área específica.
     *
     * @param string $areaOModulo Nombre o código del área o módulo
     * @param string $accion Acción requerida ('ver', 'crear', 'editar', etc.)
     * @return bool
     */
    public static function tieneAcceso(string $areaOModulo, string $accion = 'ver'): bool {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) { session_start(); }

        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return false;
        }

        $rol = $user['rol'] ?? 'usuario';

        // 1. Nivel 0: Superadmin y Administrador tienen acceso total
        if (in_array($rol, ['superadmin', 'admin'])) {
            return true;
        }

        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $key = strtolower(trim($areaOModulo));
        if ($key === 'dashboard') {
            return true;
        }

        self::cargarPermisosUsuario($userId);

        // Si es usuario colaborador pero no tiene acceso aprobado aún
        $accesoAprobado = (int)($user['acceso_aprobado'] ?? self::$userPermisosCache['acceso_aprobado'] ?? 0);
        if (!$accesoAprobado) {
            return false;
        }

        $nivel = (int)(self::$userPermisosCache['nivel_jerarquico'] ?? 99);
        $cargoCodigo = strtoupper(self::$userPermisosCache['cargo_codigo'] ?? '');
        $userAreaId = self::$userPermisosCache['org_area_id'] ?? null;
        $userAreaCodigo = strtoupper(self::$userPermisosCache['area_codigo'] ?? '');

        // 2. Nivel 0 (CEO / Dirección): Acceso total a todo el sistema
        if ($nivel <= 1 || $cargoCodigo === 'CEO' || $cargoCodigo === 'DIR-GEN') {
            return true;
        }

        // Determinar el código del área objetivo
        $targetAreaCode = null;
        if (isset(self::$moduloAreaMap[$key])) {
            $targetAreaCode = self::$moduloAreaMap[$key];
        } else {
            $keyUpper = strtoupper($key);
            $areaDb = dbQueryOne("SELECT id, codigo, parent_id FROM org_areas WHERE UPPER(codigo) = ? OR LOWER(nombre) = ? LIMIT 1", [$keyUpper, $key]);
            if ($areaDb) {
                $targetAreaCode = strtoupper($areaDb['codigo']);
            }
        }

        // 3. Nivel 1: Gerentes (ej. Gerente de Proyectos 'GP', Gerente de Administración 'GAF')
        // Tienen acceso a su área y a todas sus áreas hijas (sub-áreas)
        if ($nivel === 2 || strpos($cargoCodigo, 'GER-') === 0) {
            if ($userAreaId && $targetAreaCode) {
                $areasHijasIds = self::getAreasDescendientes($userAreaId);
                $targetAreaDb = dbQueryOne("SELECT id FROM org_areas WHERE codigo = ? LIMIT 1", [$targetAreaCode]);
                if ($targetAreaDb && in_array((int)$targetAreaDb['id'], $areasHijasIds)) {
                    return true;
                }
            }
            if (self::puede($accion, $key)) {
                return true;
            }
            return false;
        }

        // 4. Nivel 2: Jefes de Área (ej. Jefe de Licitaciones 'JEFE-LIC', Jefe de Tesorería 'JEFE-TES')
        // Acceso ESTRICTO solo a su módulo / área específica
        if ($nivel === 3 || strpos($cargoCodigo, 'JEFE-') === 0) {
            if ($targetAreaCode && $userAreaCodigo && $targetAreaCode === $userAreaCodigo) {
                return true;
            }
            if (self::puede($accion, $key)) {
                return true;
            }
            return false;
        }

        // 5. Nivel 3: Asistentes / Operativos (nivel_jerarquico >= 4)
        // Acceso solo a tareas operativas de su área específica (sin aprobación crítica ni configuración)
        if ($nivel >= 4) {
            if (in_array($accion, ['aprobar', 'eliminar', 'configurar'])) {
                return self::puede($accion, $key);
            }
            if ($targetAreaCode && $userAreaCodigo && $targetAreaCode === $userAreaCodigo) {
                return true;
            }
            return self::puede($accion, $key);
        }

        return self::puede($accion, $key);
    }

    /**
     * Candado de Seguridad para Controladores operativos.
     * Si el usuario no tiene autorización según el organigrama y permisos,
     * bloquea la ejecución y redirige al Dashboard con HTTP 403 y alerta flash.
     *
     * @param string $areaOModulo Nombre del área o módulo
     * @param string $accion Acción requerida ('ver', 'crear', etc.)
     */
    public static function verificarAcceso(string $areaOModulo, string $accion = 'ver'): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) { session_start(); }

        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        if (!self::tieneAcceso($areaOModulo, $accion)) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                   || !empty($_GET['ajax'])
                   || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

            if ($isAjax || $_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 403,
                    'message' => 'Acceso Denegado (403): Tu puesto y nivel jerárquico no tienen autorización para acceder a ' . $areaOModulo . '.'
                ]);
                exit;
            }

            http_response_code(403);
            $_SESSION['flash_error'] = 'Acceso Denegado (403): Tu puesto y nivel jerárquico no tienen autorización para acceder al módulo de ' . ucfirst($areaOModulo) . '.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Requiere que el usuario tenga el permiso especificado.
     * Si no lo tiene, responde con 403 (JSON para AJAX) o redirige con alerta flash.
     */
    public static function requirePermiso(string $accion, string $modulo, ?int $recursoAreaId = null): void {
        if (!self::puede($accion, $modulo, $recursoAreaId)) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                   || !empty($_GET['ajax'])
                   || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

            if ($isAjax || $_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => '403 Forbidden',
                    'message' => "No tienes permisos suficientes para {$accion} en el módulo {$modulo}."
                ]);
                exit;
            }

            $_SESSION['flash_error'] = "Acceso denegado: No posees permisos para {$accion} en el módulo {$modulo}.";
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Carga todos los permisos vigentes del usuario: Cargo + Delegaciones Temporales.
     */
    public static function cargarPermisosUsuario(int $userId): void {
        if (self::$userPermisosCache !== null && (self::$userPermisosCache['user_id'] ?? 0) === $userId) {
            return;
        }

        // Obtener datos del usuario, su cargo y área
        $userRow = dbQueryOne("SELECT u.id, u.rol, u.cargo_id, u.org_area_id, u.acceso_aprobado,
                                      c.nivel_jerarquico, c.puede_aprobar, c.codigo as cargo_codigo, c.nombre as cargo_nombre,
                                      a.nivel as area_nivel, a.parent_id as area_parent_id, a.codigo as area_codigo, a.nombre as area_nombre
                               FROM usuarios u
                               LEFT JOIN org_cargos c ON u.cargo_id = c.id
                               LEFT JOIN org_areas a ON (u.org_area_id = a.id OR c.area_id = a.id)
                               WHERE u.id = ? LIMIT 1", [$userId]);

        $permisosLista = [];
        $areasDelegadas = [];

        $cargoId = $userRow['cargo_id'] ?? null;
        $orgAreaId = $userRow['org_area_id'] ?? null;
        $nivelJerarquico = (int)($userRow['nivel_jerarquico'] ?? 5);

        // 1. Permisos fijos asignados al cargo
        if ($cargoId) {
            $sqlPermisos = "SELECT p.modulo, p.accion 
                            FROM cargo_permisos cp
                            JOIN permisos p ON cp.permiso_id = p.id
                            WHERE cp.cargo_id = ?";
            $permisosRows = dbQueryAll($sqlPermisos, [$cargoId]);
            foreach ($permisosRows as $p) {
                $permisosLista[] = strtolower($p['modulo']) . '.' . strtolower($p['accion']);
            }
        }

        // 2. Permisos temporales delegados vigentes
        $sqlTemp = "SELECT p.modulo, p.accion, upt.area_id
                    FROM usuario_permisos_temp upt
                    JOIN permisos p ON upt.permiso_id = p.id
                    WHERE upt.usuario_id = ? 
                      AND upt.activo = 1 
                      AND upt.fecha_inicio <= NOW() 
                      AND upt.fecha_fin >= NOW()";
        $tempRows = dbQueryAll($sqlTemp, [$userId]);
        foreach ($tempRows as $t) {
            $key = strtolower($t['modulo']) . '.' . strtolower($t['accion']);
            if (!in_array($key, $permisosLista)) {
                $permisosLista[] = $key;
            }
            $areaId = (int)($t['area_id'] ?? 0);
            if (!isset($areasDelegadas[$key])) {
                $areasDelegadas[$key] = [];
            }
            $areasDelegadas[$key][] = $areaId;
        }

        self::$userPermisosCache = [
            'user_id' => $userId,
            'acceso_aprobado' => (int)($userRow['acceso_aprobado'] ?? 0),
            'cargo_id' => $cargoId,
            'cargo_codigo' => $userRow['cargo_codigo'] ?? '',
            'cargo_nombre' => $userRow['cargo_nombre'] ?? '',
            'org_area_id' => $orgAreaId,
            'area_codigo' => $userRow['area_codigo'] ?? '',
            'area_nombre' => $userRow['area_nombre'] ?? '',
            'nivel_jerarquico' => $nivelJerarquico,
            'puede_aprobar' => (int)($userRow['puede_aprobar'] ?? 0),
            'permisos' => $permisosLista,
            'areas_delegadas' => $areasDelegadas
        ];
    }

    /**
     * Obtiene recursivamente todas las sub-áreas dependientes de un área dada.
     */
    public static function getAreasDescendientes(?int $areaId): array {
        if (!$areaId) {
            return [];
        }

        if (self::$userAreasHijasCache !== null && isset(self::$userAreasHijasCache[$areaId])) {
            return self::$userAreasHijasCache[$areaId];
        }

        $todasLasAreas = dbQueryAll("SELECT id, parent_id FROM org_areas WHERE activo = 1");
        $hijas = [(int)$areaId];

        $buscar = [(int)$areaId];
        while (!empty($buscar)) {
            $nuevoBuscar = [];
            foreach ($todasLasAreas as $a) {
                if ($a['parent_id'] !== null && in_array((int)$a['parent_id'], $buscar)) {
                    if (!in_array((int)$a['id'], $hijas)) {
                        $hijas[] = (int)$a['id'];
                        $nuevoBuscar[] = (int)$a['id'];
                    }
                }
            }
            $buscar = $nuevoBuscar;
        }

        if (self::$userAreasHijasCache === null) {
            self::$userAreasHijasCache = [];
        }
        self::$userAreasHijasCache[$areaId] = $hijas;

        return $hijas;
    }

    /**
     * Devuelve la lista de IDs de áreas accesibles para el usuario actual.
     * Retorna null si tiene acceso total (CEO / Admin).
     */
    public static function getAreasAccesibles(): ?array {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $user = $_SESSION['user'] ?? null;
        if (!$user) return [];

        if (in_array($user['rol'] ?? '', ['superadmin', 'admin'])) {
            return null; // Acceso irrestricto
        }

        $userId = (int)($user['id'] ?? 0);
        self::cargarPermisosUsuario($userId);

        $nivel = self::$userPermisosCache['nivel_jerarquico'] ?? 99;
        if ($nivel <= 1) {
            return null; // CEO / Dirección
        }

        $areaId = self::$userPermisosCache['org_area_id'] ?? null;
        if (!$areaId) {
            return [];
        }

        if ($nivel == 2) {
            return self::getAreasDescendientes($areaId);
        }

        return [(int)$areaId];
    }

    /**
     * Invalida la caché de permisos en memoria (útil tras actualizar permisos o cargos).
     */
    public static function clearCache(): void {
        self::$userPermisosCache = null;
        self::$userAreasHijasCache = null;
    }
}
