<?php
/**
 * Modelo: Convocatoria
 */
class ConvocatoriaModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['estado'])) {
            $where .= " AND c.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (c.nombre LIKE ? OR c.entidad LIKE ? OR c.codigo LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        if (!empty($filtros['linea_negocio_id'])) {
            $where .= " AND c.linea_negocio_id = ?";
            $params[] = $filtros['linea_negocio_id'];
        }
        if (!empty($filtros['departamento'])) {
            $where .= " AND c.departamento = ?";
            $params[] = $filtros['departamento'];
        }
        
        return dbQuery("
            SELECT c.*, ln.nombre as linea_negocio
            FROM convocatorias c
            LEFT JOIN lineas_negocio ln ON c.linea_negocio_id = ln.id
            WHERE $where
            ORDER BY c.fecha_publicacion DESC
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("
            SELECT c.*, ln.nombre as linea_negocio, cl.razon_social as cliente_nombre
            FROM convocatorias c
            LEFT JOIN lineas_negocio ln ON c.linea_negocio_id = ln.id
            LEFT JOIN clientes cl ON c.cliente_id = cl.id
            WHERE c.id = ?
        ", [$id]);
    }
    
    public function getCatalogoPerfiles() {
        return dbQuery("SELECT * FROM catalogo_perfiles WHERE activo = 1 ORDER BY nombre ASC");
    }

    public function addPerfilCatalogo($nombre) {
        $nombre = trim($nombre);
        if (empty($nombre)) return false;
        try {
            dbExecute("INSERT IGNORE INTO catalogo_perfiles (nombre) VALUES (?)", [$nombre]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getPerfilesByConvocatoria($convocatoriaId) {
        return dbQuery("
            SELECT * FROM convocatoria_perfiles 
            WHERE convocatoria_id = ? 
            ORDER BY id ASC
        ", [$convocatoriaId]);
    }

    public function savePerfiles($convocatoriaId, $perfiles) {
        dbExecute("DELETE FROM convocatoria_perfiles WHERE convocatoria_id = ?", [$convocatoriaId]);
        if (!empty($perfiles) && is_array($perfiles)) {
            foreach ($perfiles as $p) {
                $nombre = trim($p['nombre'] ?? '');
                $tipo = trim($p['tipo'] ?? 'Profesional clave');
                $cantidad = max(1, (int)($p['cantidad'] ?? 1));
                if (!empty($nombre)) {
                    dbExecute("
                        INSERT INTO convocatoria_perfiles (convocatoria_id, perfil_nombre, tipo_personal, cantidad)
                        VALUES (?, ?, ?, ?)
                    ", [$convocatoriaId, $nombre, $tipo, $cantidad]);
                    // Auto-registrar en catálogo si no existe
                    $this->addPerfilCatalogo($nombre);
                }
            }
        }
    }

    public function create($data) {
        $codigo = 'CONV-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $monto = !empty($data['monto_referencial']) ? (float)$data['monto_referencial'] : 0.00;
        $duracion = !empty($data['duracion_dias']) ? (int)$data['duracion_dias'] : null;
        $cantProf = !empty($data['cantidad_profesionales']) ? (int)$data['cantidad_profesionales'] : 0;
        
        dbExecute("
            INSERT INTO convocatorias (codigo, nombre, entidad, cliente_id, linea_negocio_id, tipo_gestion, 
            monto_referencial, duracion_dias, fecha_publicacion, fecha_consultas, fecha_limite_oferta, fecha_buena_pro,
            plazo_ejecucion, requisitos_profesionales, cantidad_profesionales, departamento, fuente, url_fuente, estado, detectada_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'detectada', ?)
        ", [
            $codigo, $data['nombre'], $data['entidad'], !empty($data['cliente_id']) ? $data['cliente_id'] : null,
            !empty($data['linea_negocio_id']) ? $data['linea_negocio_id'] : null, $data['tipo_gestion'] ?? 'Consultoría u obra convocada',
            $monto, $duracion, !empty($data['fecha_publicacion']) ? $data['fecha_publicacion'] : null,
            !empty($data['fecha_consultas']) ? $data['fecha_consultas'] : null,
            !empty($data['fecha_limite_oferta']) ? $data['fecha_limite_oferta'] : null,
            !empty($data['fecha_buena_pro']) ? $data['fecha_buena_pro'] : null,
            $duracion ? ($duracion . ' días') : ($data['plazo_ejecucion'] ?? null),
            $cantProf ? ($cantProf . ' profesionales') : ($data['requisitos_profesionales'] ?? null),
            $cantProf, $data['departamento'] ?? '', $data['fuente'] ?? 'SEACE',
            $data['url_fuente'] ?? null, 
            !empty($data['detectada_por']) ? $data['detectada_por'] : (!empty($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null)
        ]);
        
        $convId = dbLastId();
        
        // Guardar perfiles
        if (!empty($data['perfiles'])) {
            $this->savePerfiles($convId, $data['perfiles']);
        }

        // Crear subcarpeta correspondiente en Google Drive
        try {
            require_once __DIR__ . '/../helpers/GoogleDriveHelper.php';
            $helper = new GoogleDriveHelper();
            $helper->getItemFolder('Convocatorias', $codigo);
        } catch (\Exception $e) {}
        
        return $convId;
    }
    
    public function update($id, $data) {
        $monto = !empty($data['monto_referencial']) ? (float)$data['monto_referencial'] : 0.00;
        $duracion = !empty($data['duracion_dias']) ? (int)$data['duracion_dias'] : null;
        $cantProf = !empty($data['cantidad_profesionales']) ? (int)$data['cantidad_profesionales'] : 0;
        
        $res = dbExecute("
            UPDATE convocatorias SET
            nombre = ?, entidad = ?, cliente_id = ?, linea_negocio_id = ?, tipo_gestion = ?,
            monto_referencial = ?, duracion_dias = ?, fecha_publicacion = ?, fecha_consultas = ?,
            fecha_limite_oferta = ?, fecha_buena_pro = ?, plazo_ejecucion = ?,
            requisitos_profesionales = ?, cantidad_profesionales = ?, departamento = ?,
            url_fuente = ?, estado = ?, observaciones = ?
            WHERE id = ?
        ", [
            $data['nombre'], $data['entidad'], !empty($data['cliente_id']) ? $data['cliente_id'] : null,
            !empty($data['linea_negocio_id']) ? $data['linea_negocio_id'] : null, $data['tipo_gestion'] ?? '',
            $monto, $duracion, !empty($data['fecha_publicacion']) ? $data['fecha_publicacion'] : null,
            !empty($data['fecha_consultas']) ? $data['fecha_consultas'] : null,
            !empty($data['fecha_limite_oferta']) ? $data['fecha_limite_oferta'] : null,
            !empty($data['fecha_buena_pro']) ? $data['fecha_buena_pro'] : null,
            $duracion ? ($duracion . ' días') : ($data['plazo_ejecucion'] ?? null),
            $cantProf ? ($cantProf . ' profesionales') : ($data['requisitos_profesionales'] ?? null),
            $cantProf, $data['departamento'] ?? '', $data['url_fuente'] ?? null,
            $data['estado'] ?? 'detectada', $data['observaciones'] ?? '', $id
        ]);
        
        // Guardar perfiles si vienen en data
        if (isset($data['perfiles'])) {
            $this->savePerfiles($id, $data['perfiles']);
        }
        
        return $res;
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM convocatorias WHERE id = ?", [$id]);
    }
    
    public function evaluar($id, $estado) {
        return dbExecute("UPDATE convocatorias SET estado = ? WHERE id = ?", [$estado, $id]);
    }
    
    public function convertirAOportunidad($id) {
        $conv = $this->getById($id);
        if (!$conv) return false;
        
        $codigo = 'OP-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        dbExecute("
            INSERT INTO oportunidades (codigo, nombre, convocatoria_id, cliente_id, linea_negocio_id, 
            monto_estimado, fecha_limite, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'en_evaluacion')
        ", [
            $codigo, $conv['nombre'], $id, $conv['cliente_id'],
            $conv['linea_negocio_id'], $conv['monto_referencial'], $conv['fecha_limite_oferta']
        ]);
        
        $this->evaluar($id, 'convertida');
        return dbLastId();
    }
    
    public function getStats() {
        return [
            'detectadas' => dbQueryOne("SELECT COUNT(*) as t FROM convocatorias WHERE estado = 'detectada'")['t'],
            'convertidas' => dbQueryOne("SELECT COUNT(*) as t FROM convocatorias WHERE estado = 'convertida'")['t'],
        ];
    }
}
