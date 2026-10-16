<?php
/**
 * Modelo de Estructura Organizacional y Permisos Jerárquicos
 * Gestión de Proyectos - ERP Core
 */
require_once __DIR__ . '/../config/database.php';

class OrganizacionModel {

    // =========================================================================
    // ÁREAS ORGANIZACIONALES (Jerarquía)
    // =========================================================================

    /**
     * Obtiene todas las áreas en formato plano, con conteo de cargos y colaboradores.
     */
    public function getAllAreas(): array {
        $sql = "SELECT a.*, 
                       p.nombre as padre_nombre,
                       (SELECT COUNT(*) FROM org_cargos c WHERE c.area_id = a.id AND c.activo = 1) as total_cargos,
                       (SELECT COUNT(*) FROM usuarios u WHERE (u.org_area_id = a.id OR u.cargo_id IN (SELECT id FROM org_cargos WHERE area_id = a.id)) AND u.activo = 1 AND (u.acceso_aprobado = 1 OR u.acceso_aprobado IS NULL)) as total_usuarios
                FROM org_areas a
                LEFT JOIN org_areas p ON a.parent_id = p.id
                WHERE a.activo = 1
                ORDER BY a.nivel ASC, a.orden ASC, a.nombre ASC";
        return dbQueryAll($sql);
    }

    /**
     * Construye y devuelve el árbol jerárquico recursivo de áreas corporativas.
     */
    public function getAreasTree(): array {
        $areas = $this->getAllAreas();
        return $this->buildTree($areas, null);
    }

    private function buildTree(array $elements, ?int $parentId = null): array {
        $branch = [];
        foreach ($elements as $element) {
            $elemParent = $element['parent_id'] !== null ? (int)$element['parent_id'] : null;
            if ($elemParent === $parentId) {
                $children = $this->buildTree($elements, (int)$element['id']);
                if ($children) {
                    $element['hijos'] = $children;
                } else {
                    $element['hijos'] = [];
                }
                // Obtener cargos de esta área
                $element['cargos'] = $this->getCargosByArea((int)$element['id']);
                $branch[] = $element;
            }
        }
        return $branch;
    }

    public function getAreaById(int $id): ?array {
        $sql = "SELECT a.*, p.nombre as padre_nombre 
                FROM org_areas a 
                LEFT JOIN org_areas p ON a.parent_id = p.id 
                WHERE a.id = ? LIMIT 1";
        return dbQueryOne($sql, [$id]);
    }

    public function createArea(array $data): int {
        $nombre = trim($data['nombre'] ?? '');
        $codigo = strtoupper(trim($data['codigo'] ?? ''));
        $descripcion = trim($data['descripcion'] ?? '');
        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $icono = !empty($data['icono']) ? trim($data['icono']) : 'fas fa-sitemap';
        $color = !empty($data['color']) ? trim($data['color']) : '#6366f1';
        $orden = (int)($data['orden'] ?? 0);

        // Calcular nivel automáticamente según el padre
        $nivel = 0;
        if ($parentId) {
            $padre = $this->getAreaById($parentId);
            $nivel = $padre ? ((int)$padre['nivel'] + 1) : 1;
        }

        $sql = "INSERT INTO org_areas (nombre, codigo, descripcion, parent_id, nivel, icono, color, orden, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        return dbInsert($sql, [$nombre, $codigo, $descripcion, $parentId, $nivel, $icono, $color, $orden]);
    }

    public function updateArea(int $id, array $data): bool {
        $nombre = trim($data['nombre'] ?? '');
        $codigo = strtoupper(trim($data['codigo'] ?? ''));
        $descripcion = trim($data['descripcion'] ?? '');
        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $icono = !empty($data['icono']) ? trim($data['icono']) : 'fas fa-sitemap';
        $color = !empty($data['color']) ? trim($data['color']) : '#6366f1';
        $orden = (int)($data['orden'] ?? 0);

        // Prevenir auto-referencia o ciclos
        if ($parentId === $id) {
            $parentId = null;
        }

        $nivel = 0;
        if ($parentId) {
            $padre = $this->getAreaById($parentId);
            $nivel = $padre ? ((int)$padre['nivel'] + 1) : 1;
        }

        $sql = "UPDATE org_areas 
                SET nombre = ?, codigo = ?, descripcion = ?, parent_id = ?, nivel = ?, icono = ?, color = ?, orden = ?
                WHERE id = ?";
        return dbExecute($sql, [$nombre, $codigo, $descripcion, $parentId, $nivel, $icono, $color, $orden, $id]) > 0;
    }

    public function deleteArea(int $id): bool {
        // Soft delete o verificación previa de hijos
        $tieneHijos = (int)(dbQueryOne("SELECT COUNT(*) as t FROM org_areas WHERE parent_id = ? AND activo = 1", [$id])['t'] ?? 0);
        if ($tieneHijos > 0) {
            throw new Exception("No se puede eliminar el área porque contiene sub-áreas subordinadas.");
        }
        $tieneCargos = (int)(dbQueryOne("SELECT COUNT(*) as t FROM org_cargos WHERE area_id = ? AND activo = 1", [$id])['t'] ?? 0);
        if ($tieneCargos > 0) {
            throw new Exception("No se puede eliminar el área porque tiene cargos asignados.");
        }
        return dbExecute("UPDATE org_areas SET activo = 0 WHERE id = ?", [$id]) > 0;
    }

    // =========================================================================
    // CARGOS Y PUESTOS
    // =========================================================================

    public function getAllCargos(?int $areaId = null): array {
        $params = [];
        $where = "c.activo = 1";
        if ($areaId) {
            $where .= " AND c.area_id = ?";
            $params[] = $areaId;
        }

        $sql = "SELECT c.*, a.nombre as area_nombre, a.codigo as area_codigo, a.color as area_color,
                       (SELECT COUNT(*) FROM cargo_permisos cp WHERE cp.cargo_id = c.id) as total_permisos,
                       (SELECT COUNT(*) FROM usuarios u WHERE u.cargo_id = c.id AND u.activo = 1 AND (u.acceso_aprobado = 1 OR u.acceso_aprobado IS NULL)) as total_usuarios
                FROM org_cargos c
                JOIN org_areas a ON c.area_id = a.id
                WHERE {$where}
                ORDER BY c.nivel_jerarquico ASC, a.nombre ASC, c.nombre ASC";
        return dbQueryAll($sql, $params);
    }

    public function getCargosByArea(int $areaId): array {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM cargo_permisos cp WHERE cp.cargo_id = c.id) as total_permisos,
                       (SELECT COUNT(*) FROM usuarios u WHERE u.cargo_id = c.id AND u.activo = 1 AND (u.acceso_aprobado = 1 OR u.acceso_aprobado IS NULL)) as total_usuarios
                FROM org_cargos c
                WHERE c.area_id = ? AND c.activo = 1
                ORDER BY c.nivel_jerarquico ASC, c.nombre ASC";
        return dbQueryAll($sql, [$areaId]);
    }

    public function getCargoById(int $id): ?array {
        $sql = "SELECT c.*, a.nombre as area_nombre, a.codigo as area_codigo 
                FROM org_cargos c
                JOIN org_areas a ON c.area_id = a.id
                WHERE c.id = ? LIMIT 1";
        return dbQueryOne($sql, [$id]);
    }

    public function createCargo(array $data): int {
        $nombre = trim($data['nombre'] ?? '');
        $codigo = strtoupper(trim($data['codigo'] ?? ''));
        $descripcion = trim($data['descripcion'] ?? '');
        $areaId = (int)($data['area_id'] ?? 0);
        $nivelJerarquico = (int)($data['nivel_jerarquico'] ?? 3);
        $puedeAprobar = !empty($data['puede_aprobar']) ? 1 : 0;

        $sql = "INSERT INTO org_cargos (nombre, codigo, descripcion, area_id, nivel_jerarquico, puede_aprobar, activo)
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        return dbInsert($sql, [$nombre, $codigo, $descripcion, $areaId, $nivelJerarquico, $puedeAprobar]);
    }

    public function updateCargo(int $id, array $data): bool {
        $nombre = trim($data['nombre'] ?? '');
        $codigo = strtoupper(trim($data['codigo'] ?? ''));
        $descripcion = trim($data['descripcion'] ?? '');
        $areaId = (int)($data['area_id'] ?? 0);
        $nivelJerarquico = (int)($data['nivel_jerarquico'] ?? 3);
        $puedeAprobar = !empty($data['puede_aprobar']) ? 1 : 0;

        $sql = "UPDATE org_cargos 
                SET nombre = ?, codigo = ?, descripcion = ?, area_id = ?, nivel_jerarquico = ?, puede_aprobar = ?
                WHERE id = ?";
        return dbExecute($sql, [$nombre, $codigo, $descripcion, $areaId, $nivelJerarquico, $puedeAprobar, $id]) > 0;
    }

    public function deleteCargo(int $id): bool {
        $totalUsuarios = (int)(dbQueryOne("SELECT COUNT(*) as t FROM usuarios WHERE cargo_id = ? AND activo = 1", [$id])['t'] ?? 0);
        if ($totalUsuarios > 0) {
            throw new Exception("No se puede eliminar el cargo porque tiene {$totalUsuarios} usuario(s) asignado(s).");
        }
        return dbExecute("UPDATE org_cargos SET activo = 0 WHERE id = ?", [$id]) > 0;
    }

    // =========================================================================
    // CATÁLOGO DE PERMISOS Y MATRIZ POR CARGO
    // =========================================================================

    /**
     * Obtiene el catálogo completo de permisos agrupados por módulo.
     */
    public function getPermisosCatalogo(): array {
        $rows = dbQueryAll("SELECT * FROM permisos ORDER BY modulo ASC, accion ASC");
        $catalogo = [];
        foreach ($rows as $r) {
            $modulo = $r['modulo'];
            if (!isset($catalogo[$modulo])) {
                $catalogo[$modulo] = [];
            }
            $catalogo[$modulo][] = $r;
        }
        return $catalogo;
    }

    /**
     * Obtiene los IDs de los permisos asignados a un cargo específico.
     */
    public function getPermisosIdsByCargo(int $cargoId): array {
        $rows = dbQueryAll("SELECT permiso_id FROM cargo_permisos WHERE cargo_id = ?", [$cargoId]);
        return array_map('intval', array_column($rows, 'permiso_id'));
    }

    /**
     * Sincroniza atómicamente los permisos asignados a un cargo.
     */
    public function syncCargoPermisos(int $cargoId, array $permisoIds): bool {
        $conn = getConnection();
        $conn->beginTransaction();
        try {
            // Eliminar anteriores
            $stmtDel = $conn->prepare("DELETE FROM cargo_permisos WHERE cargo_id = ?");
            $stmtDel->execute([$cargoId]);

            // Insertar nuevos
            if (!empty($permisoIds)) {
                $stmtIns = $conn->prepare("INSERT INTO cargo_permisos (cargo_id, permiso_id) VALUES (?, ?)");
                foreach ($permisoIds as $pid) {
                    $stmtIns->execute([$cargoId, (int)$pid]);
                }
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    // =========================================================================
    // DELEGACIONES TEMPORALES DE PERMISOS
    // =========================================================================

    public function getDelegaciones(): array {
        $sql = "SELECT upt.*, 
                       u.nombre as usuario_nombre, u.email as usuario_email,
                       ot.nombre as otorgado_por_nombre,
                       p.modulo, p.accion, p.descripcion as permiso_desc,
                       a.nombre as area_nombre
                FROM usuario_permisos_temp upt
                JOIN usuarios u ON upt.usuario_id = u.id
                JOIN usuarios ot ON upt.otorgado_por = ot.id
                JOIN permisos p ON upt.permiso_id = p.id
                LEFT JOIN org_areas a ON upt.area_id = a.id
                ORDER BY upt.activo DESC, upt.fecha_fin DESC";
        return dbQueryAll($sql);
    }

    public function createDelegacion(array $data, int $otorgadoPorId): int {
        $usuarioId = (int)($data['usuario_id'] ?? 0);
        $permisoId = (int)($data['permiso_id'] ?? 0);
        $areaId = !empty($data['area_id']) ? (int)$data['area_id'] : null;
        $motivo = trim($data['motivo'] ?? '');
        $fechaInicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : date('Y-m-d H:i:s');
        $fechaFin = trim($data['fecha_fin'] ?? '');

        if (!$usuarioId || !$permisoId || empty($fechaFin)) {
            throw new Exception("Todos los campos obligatorios deben ser completados.");
        }

        $sql = "INSERT INTO usuario_permisos_temp (usuario_id, permiso_id, area_id, otorgado_por, motivo, fecha_inicio, fecha_fin, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        return dbInsert($sql, [$usuarioId, $permisoId, $areaId, $otorgadoPorId, $motivo, $fechaInicio, $fechaFin]);
    }

    public function revocarDelegacion(int $id): bool {
        return dbExecute("UPDATE usuario_permisos_temp SET activo = 0 WHERE id = ?", [$id]) > 0;
    }

    // =========================================================================
    // ASIGNACIÓN DE USUARIOS A CARGOS Y ÁREAS
    // =========================================================================

    public function getUsuariosConEstructura(): array {
        $sql = "SELECT u.id, u.nombre, u.email, u.rol, u.activo,
                       c.id as cargo_id, c.nombre as cargo_nombre, c.codigo as cargo_codigo,
                       a.id as area_id, a.nombre as area_nombre, a.color as area_color
                FROM usuarios u
                LEFT JOIN org_cargos c ON u.cargo_id = c.id
                LEFT JOIN org_areas a ON (u.org_area_id = a.id OR c.area_id = a.id)
                WHERE u.activo = 1 AND (u.acceso_aprobado = 1 OR u.acceso_aprobado IS NULL)
                ORDER BY a.nombre ASC, c.nivel_jerarquico ASC, u.nombre ASC";
        return dbQueryAll($sql);
    }

    public function asignarCargoUsuario(int $usuarioId, ?int $cargoId, ?int $orgAreaId = null): bool {
        // Si no se especifica área pero sí cargo, tomar el área del cargo
        if ($cargoId && !$orgAreaId) {
            $cargo = $this->getCargoById($cargoId);
            if ($cargo) {
                $orgAreaId = (int)$cargo['area_id'];
            }
        }
        $sql = "UPDATE usuarios SET cargo_id = ?, org_area_id = ? WHERE id = ?";
        return dbExecute($sql, [$cargoId, $orgAreaId, $usuarioId]) > 0;
    }
}
