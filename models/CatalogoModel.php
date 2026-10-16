<?php
/**
 * Modelo de Catálogos Maestros de Logística: materiales, equipos y proveedores.
 * Los registros no se borran físicamente (están referenciados por órdenes de compra y almacén):
 * se desactivan con activo = 0.
 */
require_once __DIR__ . '/../config/database.php';

class CatalogoModel {

    /** Tipos soportados → tabla y prefijo de código autogenerado */
    private const TIPOS = [
        'material'  => ['tabla' => 'cat_materiales', 'prefijo' => 'MAT'],
        'equipo'    => ['tabla' => 'cat_equipos',    'prefijo' => 'EQ'],
        'proveedor' => ['tabla' => 'proveedores',    'prefijo' => null],
    ];

    public function getMateriales(bool $soloActivos = false): array {
        $w = $soloActivos ? 'WHERE activo = 1' : '';
        return dbQueryAll("SELECT * FROM cat_materiales $w ORDER BY activo DESC, nombre ASC");
    }

    public function getEquipos(bool $soloActivos = false): array {
        $w = $soloActivos ? 'WHERE activo = 1' : '';
        return dbQueryAll("SELECT * FROM cat_equipos $w ORDER BY activo DESC, nombre ASC");
    }

    public function getProveedores(bool $soloActivos = false): array {
        $w = $soloActivos ? 'WHERE activo = 1' : '';
        return dbQueryAll("SELECT * FROM proveedores $w ORDER BY activo DESC, razon_social ASC");
    }

    /**
     * Crea o actualiza un registro de catálogo.
     *
     * @return array El registro guardado
     * @throws Exception con mensaje apto para el usuario
     */
    public function guardar(string $tipo, int $id, array $d): array {
        if (!isset(self::TIPOS[$tipo])) {
            throw new Exception('Tipo de catálogo no válido.');
        }
        switch ($tipo) {
            case 'material':  $id = $this->guardarMaterial($id, $d);  break;
            case 'equipo':    $id = $this->guardarEquipo($id, $d);    break;
            default:          $id = $this->guardarProveedor($id, $d); break;
        }
        return dbQueryOne("SELECT * FROM `" . self::TIPOS[$tipo]['tabla'] . "` WHERE id = ?", [$id]);
    }

    public function cambiarEstado(string $tipo, int $id, bool $activo): bool {
        if (!isset(self::TIPOS[$tipo])) {
            throw new Exception('Tipo de catálogo no válido.');
        }
        $tabla = self::TIPOS[$tipo]['tabla'];
        return (bool)dbExecute("UPDATE `$tabla` SET activo = ? WHERE id = ?", [$activo ? 1 : 0, $id]);
    }

    // ─────────────────────────────────────────────────────────────────────

    private function guardarMaterial(int $id, array $d): int {
        $nombre = $this->texto($d['nombre'] ?? '', 255);
        if ($nombre === '') { throw new Exception('El nombre del material es obligatorio.'); }

        $codigo = strtoupper($this->texto($d['codigo'] ?? '', 50));
        if ($codigo === '') { $codigo = $this->siguienteCodigo('cat_materiales', 'MAT'); }
        $this->validarCodigoUnico('cat_materiales', $codigo, $id, 'material');

        $unidad = strtoupper($this->texto($d['unidad_medida'] ?? 'UND', 20)) ?: 'UND';
        $categoria = $this->texto($d['categoria'] ?? '', 100) ?: null;
        $precio = $this->decimal($d['precio_referencial'] ?? 0, 'precio referencial');
        $stockMin = $this->decimal($d['stock_minimo'] ?? 0, 'stock mínimo');

        // El stock real solo se mueve con ingresos/salidas de almacén (kardex): al editar se ignora.
        // En la creación se acepta como stock inicial, valorizado al precio referencial.
        if ($id > 0) {
            dbExecute("UPDATE cat_materiales SET codigo=?, nombre=?, unidad_medida=?, categoria=?, precio_referencial=?, stock_minimo=? WHERE id=?",
                [$codigo, $nombre, $unidad, $categoria, $precio, $stockMin, $id]);
            return $id;
        }
        $stock = $this->decimal($d['stock_actual'] ?? 0, 'stock inicial');
        return dbInsert("INSERT INTO cat_materiales (codigo, nombre, unidad_medida, categoria, precio_referencial, stock_actual, costo_promedio, stock_minimo, activo)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)", [$codigo, $nombre, $unidad, $categoria, $precio, $stock, $precio, $stockMin]);
    }

    private function guardarEquipo(int $id, array $d): int {
        $nombre = $this->texto($d['nombre'] ?? '', 255);
        if ($nombre === '') { throw new Exception('El nombre del equipo es obligatorio.'); }

        $codigo = strtoupper($this->texto($d['codigo'] ?? '', 50));
        if ($codigo === '') { $codigo = $this->siguienteCodigo('cat_equipos', 'EQ'); }
        $this->validarCodigoUnico('cat_equipos', $codigo, $id, 'equipo');

        $tipo = $this->texto($d['tipo'] ?? '', 100) ?: null;
        $costoHora = $this->decimal($d['costo_hora'] ?? 0, 'costo hora');

        if ($id > 0) {
            dbExecute("UPDATE cat_equipos SET codigo=?, nombre=?, tipo=?, costo_hora=? WHERE id=?",
                [$codigo, $nombre, $tipo, $costoHora, $id]);
            return $id;
        }
        return dbInsert("INSERT INTO cat_equipos (codigo, nombre, tipo, costo_hora, activo) VALUES (?, ?, ?, ?, 1)",
            [$codigo, $nombre, $tipo, $costoHora]);
    }

    private function guardarProveedor(int $id, array $d): int {
        $razon = $this->texto($d['razon_social'] ?? '', 255);
        if ($razon === '') { throw new Exception('La razón social es obligatoria.'); }

        $ruc = $this->texto($d['ruc'] ?? '', 20) ?: null;
        if ($ruc !== null && !preg_match('/^\d{11}$/', $ruc)) {
            throw new Exception('El RUC debe tener 11 dígitos.');
        }
        if ($ruc !== null) {
            $dup = dbQueryOne("SELECT id FROM proveedores WHERE ruc = ? AND id <> ? LIMIT 1", [$ruc, $id]);
            if ($dup) { throw new Exception('Ya existe un proveedor registrado con ese RUC.'); }
        }

        $email = $this->texto($d['email'] ?? '', 150) ?: null;
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El correo electrónico no es válido.');
        }

        $calif = isset($d['calificacion']) && $d['calificacion'] !== '' ? (float)$d['calificacion'] : 5.0;
        $calif = max(0, min(5, $calif));

        $vals = [
            $razon, $ruc,
            $this->texto($d['direccion'] ?? '', 500) ?: null,
            $this->texto($d['telefono'] ?? '', 50) ?: null,
            $email,
            $this->texto($d['contacto_nombre'] ?? '', 150) ?: null,
            $this->texto($d['especialidad'] ?? '', 255) ?: null,
            $calif
        ];

        if ($id > 0) {
            dbExecute("UPDATE proveedores SET razon_social=?, ruc=?, direccion=?, telefono=?, email=?, contacto_nombre=?, especialidad=?, calificacion=? WHERE id=?",
                array_merge($vals, [$id]));
            return $id;
        }
        return dbInsert("INSERT INTO proveedores (razon_social, ruc, direccion, telefono, email, contacto_nombre, especialidad, calificacion, activo)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)", $vals);
    }

    // ─────────────────────────────────────────────────────────────────────

    private function texto($v, int $max): string {
        return mb_substr(trim((string)$v), 0, $max);
    }

    private function decimal($v, string $campo): float {
        if ($v === '' || $v === null) { return 0.0; }
        if (!is_numeric($v) || (float)$v < 0) {
            throw new Exception("El valor de $campo debe ser un número mayor o igual a 0.");
        }
        return round((float)$v, 2);
    }

    private function validarCodigoUnico(string $tabla, string $codigo, int $id, string $etiqueta): void {
        $dup = dbQueryOne("SELECT id FROM `$tabla` WHERE codigo = ? AND id <> ? LIMIT 1", [$codigo, $id]);
        if ($dup) { throw new Exception("Ya existe un $etiqueta con el código $codigo."); }
    }

    private function siguienteCodigo(string $tabla, string $prefijo): string {
        $row = dbQueryOne("SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, ?) AS UNSIGNED)), 0) AS m FROM `$tabla` WHERE codigo LIKE ?",
            [strlen($prefijo) + 2, $prefijo . '-%']);
        return $prefijo . '-' . str_pad(((int)$row['m']) + 1, 4, '0', STR_PAD_LEFT);
    }
}
