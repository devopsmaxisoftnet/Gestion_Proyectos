<?php
/**
 * Modelo de Subcontratos y Valorizaciones del subcontratista (Fase 3: Logística)
 *
 * Contrato: monto (sin IGV), adelanto y retención de garantía (% de cada valorización).
 * Valorización: se registra por AVANCE ACUMULADO (%). De ahí se calcula:
 *     bruto del periodo   = monto × avance acumulado − bruto acumulado anterior
 *     amortización        = adelanto × bruto / monto (en el 100 % se amortiza el saldo exacto)
 *     facturable          = bruto − amortización
 *     IGV                 = 18 % × facturable
 *     retención           = retención % × bruto (fondo de garantía, no lleva IGV)
 *     neto a pagar        = facturable + IGV − retención
 *
 * Costo real de la actividad = monto_bruto de las valorizaciones APROBADAS o PAGADAS (TareaModel).
 * Lo contratado y aún sin valorizar es un compromiso (ControlPresupuestalModel).
 *
 * Flujo de la valorización: borrador → aprobada → pagada. Solo se anula la última no anulada (y no si ya se pagó),
 * un contrato admite un único borrador a la vez, y una valorización solo se aprueba si las anteriores ya están aprobadas.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/TareaModel.php';

class SubcontratoModel {

    public const IGV = 0.18;
    public const ESTADOS = ['activo', 'finalizado', 'suspendido', 'anulado'];
    private const EPS = 0.005;

    private TareaModel $tareas;

    public function __construct() {
        $this->tareas = new TareaModel();
    }

    public function disponible(): bool {
        $r = dbQueryOne("SELECT COUNT(*) n FROM information_schema.TABLES
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('subcontrato_valorizaciones')");
        $c = dbQueryOne("SELECT COUNT(*) n FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subcontratos' AND COLUMN_NAME IN ('adelanto_monto', 'retencion_pct')");
        return (int)($r['n'] ?? 0) === 1 && (int)($c['n'] ?? 0) === 2;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  CÁLCULO DE LA VALORIZACIÓN (puro, probado por separado)
    // ═════════════════════════════════════════════════════════════════════

    /**
     * @param array $sub contrato (monto, adelanto_monto, retencion_pct)
     * @param float $pctAcum avance acumulado a la fecha (0-100)
     * @param float $brutoPrevAcum bruto acumulado de la valorización anterior
     * @param float $amortPrevAcum adelanto ya amortizado en valorizaciones anteriores
     */
    public static function calcular(array $sub, float $pctAcum, float $brutoPrevAcum, float $amortPrevAcum): array {
        $monto = (float)$sub['monto'];
        $adelanto = (float)$sub['adelanto_monto'];
        $retPct = (float)$sub['retencion_pct'];

        $brutoAcum = round($monto * $pctAcum / 100, 2);
        $bruto = round($brutoAcum - $brutoPrevAcum, 2);
        $restante = max(0.0, round($adelanto - $amortPrevAcum, 2));
        $amort = $pctAcum >= 100 - self::EPS ? $restante
               : ($monto > 0 ? min($restante, round($adelanto * $bruto / $monto, 2)) : 0.0);
        $retencion = round($bruto * $retPct / 100, 2);
        $facturable = round($bruto - $amort, 2);
        $igv = round($facturable * self::IGV, 2);
        $neto = round($facturable + $igv - $retencion, 2);

        return ['bruto_acumulado' => $brutoAcum, 'monto_bruto' => $bruto, 'amortizacion_adelanto' => $amort,
                'retencion' => $retencion, 'facturable' => $facturable, 'igv' => $igv, 'neto_pagar' => $neto];
    }

    // ═════════════════════════════════════════════════════════════════════
    //  CONSULTAS
    // ═════════════════════════════════════════════════════════════════════

    private function selectBase(): string {
        return "SELECT s.*, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                       pv.razon_social AS proveedor, pv.ruc AS proveedor_ruc,
                       t.codigo AS tarea_codigo, t.nombre AS tarea_nombre,
                       COALESCE(v.valorizado, 0) AS valorizado, COALESCE(v.pagado, 0) AS pagado,
                       COALESCE(v.por_pagar, 0) AS por_pagar, COALESCE(v.retenido, 0) AS retenido,
                       COALESCE(v.amortizado, 0) AS amortizado, COALESCE(v.n, 0) AS n_val, COALESCE(v.borradores, 0) AS borradores
                FROM subcontratos s
                JOIN proyectos p ON p.id = s.proyecto_id
                JOIN proveedores pv ON pv.id = s.proveedor_id
                LEFT JOIN tareas t ON t.id = s.tarea_id
                LEFT JOIN (SELECT subcontrato_id,
                                  SUM(CASE WHEN estado IN ('aprobada','pagada') THEN monto_bruto ELSE 0 END) AS valorizado,
                                  SUM(CASE WHEN estado = 'pagada' THEN neto_pagar ELSE 0 END) AS pagado,
                                  SUM(CASE WHEN estado = 'aprobada' THEN neto_pagar ELSE 0 END) AS por_pagar,
                                  SUM(CASE WHEN estado IN ('aprobada','pagada') THEN retencion ELSE 0 END) AS retenido,
                                  SUM(CASE WHEN estado IN ('aprobada','pagada') THEN amortizacion_adelanto ELSE 0 END) AS amortizado,
                                  SUM(CASE WHEN estado <> 'anulada' THEN 1 ELSE 0 END) AS n,
                                  SUM(CASE WHEN estado = 'borrador' THEN 1 ELSE 0 END) AS borradores
                           FROM subcontrato_valorizaciones GROUP BY subcontrato_id) v ON v.subcontrato_id = s.id";
    }

    private function enriquecer(array $r): array {
        $monto = (float)$r['monto'];
        foreach (['monto', 'adelanto_monto', 'retencion_pct', 'valorizado', 'pagado', 'por_pagar', 'retenido', 'amortizado'] as $k) { $r[$k] = (float)$r[$k]; }
        $r['id'] = (int)$r['id']; $r['n_val'] = (int)$r['n_val']; $r['borradores'] = (int)$r['borradores'];
        $r['por_valorizar'] = $r['estado'] === 'activo' ? round(max(0, $monto - $r['valorizado']), 2) : 0.0;
        $r['avance_pct'] = $monto > 0 ? round($r['valorizado'] / $monto * 100, 1) : 0.0;
        $r['adelanto_pendiente'] = round(max(0, $r['adelanto_monto'] - $r['amortizado']), 2);
        return $r;
    }

    public function getAll(array $f = []): array {
        $where = ['1=1']; $params = [];
        if (!empty($f['proyecto_id'])) { $where[] = 's.proyecto_id = ?'; $params[] = (int)$f['proyecto_id']; }
        if (!empty($f['estado']) && in_array($f['estado'], self::ESTADOS, true)) { $where[] = 's.estado = ?'; $params[] = $f['estado']; }
        if (!empty($f['proveedor_id'])) { $where[] = 's.proveedor_id = ?'; $params[] = (int)$f['proveedor_id']; }
        if (!empty($f['q'])) {
            $where[] = '(s.codigo LIKE ? OR s.servicio LIKE ? OR pv.razon_social LIKE ?)';
            $q = '%' . trim($f['q']) . '%'; array_push($params, $q, $q, $q);
        }
        $rows = dbQueryAll($this->selectBase() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY s.id DESC', $params);
        return array_map([$this, 'enriquecer'], $rows);
    }

    public function getById(int $id): ?array {
        $r = dbQueryOne($this->selectBase() . ' WHERE s.id = ?', [$id]);
        return $r ? $this->enriquecer($r) : null;
    }

    public function getValorizaciones(int $subId): array {
        $rows = dbQueryAll(
            "SELECT v.*, ur.nombre AS registrado_nombre, ua.nombre AS aprobado_nombre
             FROM subcontrato_valorizaciones v
             LEFT JOIN usuarios ur ON ur.id = v.registrado_por
             LEFT JOIN usuarios ua ON ua.id = v.aprobado_por
             WHERE v.subcontrato_id = ? ORDER BY v.numero ASC", [$subId]);
        foreach ($rows as &$r) {
            foreach (['avance_acumulado_pct', 'monto_bruto', 'bruto_acumulado', 'amortizacion_adelanto', 'retencion', 'igv', 'neto_pagar'] as $k) { $r[$k] = (float)$r[$k]; }
            $r['id'] = (int)$r['id']; $r['numero'] = (int)$r['numero'];
            $r['facturable'] = round($r['monto_bruto'] - $r['amortizacion_adelanto'], 2);
        }
        return $rows;
    }

    /** Resumen global (o de un proyecto) para tarjetas y gráficos. */
    public function getResumen(?int $proyectoId = null): array {
        $rows = array_filter($this->getAll($proyectoId ? ['proyecto_id' => $proyectoId] : []), fn($r) => $r['estado'] !== 'anulado');
        $k = ['contratos' => count($rows), 'activos' => 0, 'contratado' => 0.0, 'valorizado' => 0.0, 'por_valorizar' => 0.0,
              'pagado' => 0.0, 'por_pagar' => 0.0, 'retenido' => 0.0, 'adelanto_pendiente' => 0.0, 'borradores' => 0];
        $porEstado = []; $porProv = [];
        foreach ($rows as $r) {
            if ($r['estado'] === 'activo') { $k['activos']++; $k['adelanto_pendiente'] += $r['adelanto_pendiente']; }
            $k['contratado'] += $r['monto']; $k['valorizado'] += $r['valorizado']; $k['por_valorizar'] += $r['por_valorizar'];
            $k['pagado'] += $r['pagado']; $k['por_pagar'] += $r['por_pagar']; $k['retenido'] += $r['retenido']; $k['borradores'] += $r['borradores'];
            $porEstado[$r['estado']] = ($porEstado[$r['estado']] ?? 0) + 1;
            $pv = &$porProv[$r['proveedor']]; $pv = $pv ?? ['proveedor' => $r['proveedor'], 'contratado' => 0.0, 'valorizado' => 0.0];
            $pv['contratado'] += $r['monto']; $pv['valorizado'] += $r['valorizado']; unset($pv);
        }
        foreach ($k as $a => $v) { if (is_float($v)) { $k[$a] = round($v, 2); } }
        usort($porProv, fn($a, $b) => $b['contratado'] <=> $a['contratado']);
        return ['kpis' => $k, 'por_estado' => $porEstado, 'por_proveedor' => array_slice($porProv, 0, 8)];
    }

    public function getProyectos(): array {
        return dbQueryAll("SELECT id, codigo, nombre, estado FROM proyectos ORDER BY fecha_inicio DESC, nombre ASC");
    }

    public function getProveedores(): array {
        return dbQueryAll("SELECT id, razon_social, ruc, especialidad FROM proveedores WHERE activo = 1 ORDER BY razon_social ASC");
    }

    // ═════════════════════════════════════════════════════════════════════
    //  CONTRATOS
    // ═════════════════════════════════════════════════════════════════════

    private function normalizarContrato(array $d, ?array $actual = null): array {
        $proyectoId = (int)($d['proyecto_id'] ?? 0);
        $proveedorId = (int)($d['proveedor_id'] ?? 0);
        $tareaId = (int)($d['tarea_id'] ?? 0);
        $servicio = trim((string)($d['servicio'] ?? ''));
        $monto = round((float)str_replace(',', '', (string)($d['monto'] ?? 0)), 2);
        $adelanto = round((float)str_replace(',', '', (string)($d['adelanto_monto'] ?? 0)), 2);
        $retPct = round((float)str_replace(',', '.', (string)($d['retencion_pct'] ?? 5)), 2);
        $ini = !empty($d['fecha_inicio']) ? $d['fecha_inicio'] : null;
        $fin = !empty($d['fecha_fin']) ? $d['fecha_fin'] : null;
        $obs = trim((string)($d['observaciones'] ?? ''));

        if (!dbQueryOne("SELECT id FROM proyectos WHERE id = ?", [$proyectoId])) { throw new Exception('Selecciona un proyecto válido.'); }
        if (!dbQueryOne("SELECT id FROM proveedores WHERE id = ? AND activo = 1", [$proveedorId])) { throw new Exception('Selecciona un subcontratista (proveedor) activo del catálogo.'); }
        if ($tareaId <= 0 || !dbQueryOne("SELECT id FROM tareas WHERE id = ? AND proyecto_id = ?", [$tareaId, $proyectoId])) {
            throw new Exception('Imputa el subcontrato a una actividad del WBS del proyecto.');
        }
        if ($servicio === '') { throw new Exception('Describe el servicio subcontratado.'); }
        if ($monto <= 0 || $monto > 99999999999) { throw new Exception('El monto contratado debe ser mayor a 0.'); }
        if ($adelanto < 0 || $adelanto > $monto + self::EPS) { throw new Exception('El adelanto no puede ser negativo ni mayor al monto contratado.'); }
        if ($retPct < 0 || $retPct > 30) { throw new Exception('La retención de garantía debe estar entre 0 % y 30 %.'); }
        if ($ini && $fin && $fin < $ini) { throw new Exception('La fecha de fin no puede ser anterior a la de inicio.'); }

        return ['proyecto_id' => $proyectoId, 'proveedor_id' => $proveedorId, 'tarea_id' => $tareaId, 'servicio' => mb_substr($servicio, 0, 255),
                'monto' => $monto, 'adelanto_monto' => $adelanto, 'retencion_pct' => $retPct, 'fecha_inicio' => $ini, 'fecha_fin' => $fin,
                'observaciones' => $obs !== '' ? mb_substr($obs, 0, 2000) : null];
    }

    public function create(array $d): int {
        $c = $this->normalizarContrato($d);
        $conn = getConnection();
        $conn->beginTransaction();
        try {
            $anio = date('Y');
            $max = dbQueryOne("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)), 0) m FROM subcontratos
                               WHERE codigo LIKE ? FOR UPDATE", ["SC-$anio-%"]);
            $codigo = 'SC-' . $anio . '-' . str_pad((string)((int)$max['m'] + 1), 4, '0', STR_PAD_LEFT);
            dbExecute("INSERT INTO subcontratos (proyecto_id, tarea_id, proveedor_id, codigo, servicio, monto, adelanto_monto, retencion_pct, fecha_inicio, fecha_fin, estado, observaciones)
                       VALUES (?,?,?,?,?,?,?,?,?,?, 'activo', ?)",
                [$c['proyecto_id'], $c['tarea_id'], $c['proveedor_id'], $codigo, $c['servicio'], $c['monto'], $c['adelanto_monto'], $c['retencion_pct'],
                 $c['fecha_inicio'], $c['fecha_fin'], $c['observaciones']]);
            $id = (int)$conn->lastInsertId();
            $conn->commit();
        } catch (Throwable $e) { $conn->rollBack(); throw $e; }
        $this->tareas->recalcularCostoRealTarea($c['tarea_id']);
        return $id;
    }

    public function update(int $id, array $d): void {
        $act = $this->getById($id);
        if (!$act) { throw new Exception('El subcontrato no existe.'); }
        if (!in_array($act['estado'], ['activo', 'suspendido'], true)) { throw new Exception('Solo se editan subcontratos activos o suspendidos.'); }
        $c = $this->normalizarContrato($d + ['proyecto_id' => $act['proyecto_id']]);

        $conValorizaciones = $act['n_val'] > 0;
        if ($conValorizaciones) {
            // Con valorizaciones el proveedor, la actividad y la retención ya forman parte de cálculos emitidos
            if ($c['proveedor_id'] !== (int)$act['proveedor_id'] || $c['tarea_id'] !== (int)$act['tarea_id'] || abs($c['retencion_pct'] - $act['retencion_pct']) > self::EPS) {
                throw new Exception('Con valorizaciones registradas no se puede cambiar el subcontratista, la actividad ni la retención de garantía.');
            }
        }
        if ($c['proyecto_id'] !== (int)$act['proyecto_id']) { throw new Exception('No se puede mover el subcontrato a otro proyecto.'); }
        if ($c['monto'] + self::EPS < $act['valorizado']) { throw new Exception('El monto contratado no puede ser menor a lo ya valorizado (S/ ' . number_format($act['valorizado'], 2) . ').'); }
        if ($c['adelanto_monto'] + self::EPS < $act['amortizado']) { throw new Exception('El adelanto no puede ser menor a lo ya amortizado (S/ ' . number_format($act['amortizado'], 2) . ').'); }

        dbExecute("UPDATE subcontratos SET proveedor_id = ?, tarea_id = ?, servicio = ?, monto = ?, adelanto_monto = ?, retencion_pct = ?,
                          fecha_inicio = ?, fecha_fin = ?, observaciones = ? WHERE id = ?",
            [$c['proveedor_id'], $c['tarea_id'], $c['servicio'], $c['monto'], $c['adelanto_monto'], $c['retencion_pct'], $c['fecha_inicio'], $c['fecha_fin'], $c['observaciones'], $id]);
        $this->tareas->recalcularCostoRealTarea($c['tarea_id']);
        if ((int)$act['tarea_id'] !== $c['tarea_id'] && $act['tarea_id']) { $this->tareas->recalcularCostoRealTarea((int)$act['tarea_id']); }
    }

    /**
     * Cambia el estado del contrato. activo⇄suspendido, activo→finalizado (libera lo no valorizado), finalizado→activo (reabrir),
     * y anulado (solo sin valorizaciones vigentes).
     */
    public function cambiarEstado(int $id, string $nuevo): void {
        if (!in_array($nuevo, self::ESTADOS, true)) { throw new Exception('Estado inválido.'); }
        $conn = getConnection();
        $conn->beginTransaction();
        try {
            $s = dbQueryOne("SELECT id, estado, tarea_id FROM subcontratos WHERE id = ? FOR UPDATE", [$id]);
            if (!$s) { throw new Exception('El subcontrato no existe.'); }
            $permitidas = ['activo' => ['finalizado', 'suspendido', 'anulado'], 'suspendido' => ['activo', 'anulado'], 'finalizado' => ['activo'], 'anulado' => []];
            if (!in_array($nuevo, $permitidas[$s['estado']] ?? [], true)) { throw new Exception("No se puede pasar de «{$s['estado']}» a «$nuevo»."); }
            $v = dbQueryOne("SELECT SUM(estado <> 'anulada') n, SUM(estado = 'borrador') b FROM subcontrato_valorizaciones WHERE subcontrato_id = ?", [$id]);
            if ($nuevo === 'anulado' && (int)$v['n'] > 0) { throw new Exception('El subcontrato tiene valorizaciones vigentes: anúlalas primero o finalízalo.'); }
            if ($nuevo === 'finalizado' && (int)$v['b'] > 0) { throw new Exception('Hay una valorización en borrador: apruébala o anúlala antes de finalizar.'); }
            dbExecute("UPDATE subcontratos SET estado = ? WHERE id = ?", [$nuevo, $id]);
            $conn->commit();
        } catch (Throwable $e) { $conn->rollBack(); throw $e; }
        if ($s['tarea_id']) { $this->tareas->recalcularCostoRealTarea((int)$s['tarea_id']); }
    }

    // ═════════════════════════════════════════════════════════════════════
    //  VALORIZACIONES
    // ═════════════════════════════════════════════════════════════════════

    /** Última valorización vigente (no anulada) de un contrato, opcionalmente ignorando una (la que se edita). */
    private function ultimaVigente(int $subId, ?int $ignorarId = null): ?array {
        $sql = "SELECT * FROM subcontrato_valorizaciones WHERE subcontrato_id = ? AND estado <> 'anulada'";
        $p = [$subId];
        if ($ignorarId) { $sql .= " AND id <> ?"; $p[] = $ignorarId; }
        return dbQueryOne($sql . " ORDER BY numero DESC LIMIT 1", $p) ?: null;
    }

    /**
     * Crea (o edita, si es un borrador) una valorización.
     * @param array $d avance_acumulado_pct, periodo_desde, periodo_hasta, observaciones
     */
    public function guardarValorizacion(int $subId, ?int $valId, array $d, int $userId): int {
        $conn = getConnection();
        $conn->beginTransaction();
        try {
            $s = dbQueryOne("SELECT * FROM subcontratos WHERE id = ? FOR UPDATE", [$subId]);
            if (!$s) { throw new Exception('El subcontrato no existe.'); }
            if ($s['estado'] !== 'activo') { throw new Exception('Solo se valorizan subcontratos activos.'); }

            if ($valId) {
                $actual = dbQueryOne("SELECT * FROM subcontrato_valorizaciones WHERE id = ? AND subcontrato_id = ?", [$valId, $subId]);
                if (!$actual) { throw new Exception('La valorización no existe.'); }
                if ($actual['estado'] !== 'borrador') { throw new Exception('Solo se edita una valorización en borrador.'); }
            } elseif ((int)dbQueryOne("SELECT COUNT(*) c FROM subcontrato_valorizaciones WHERE subcontrato_id = ? AND estado = 'borrador'", [$subId])['c'] > 0) {
                throw new Exception('Ya hay una valorización en borrador: apruébala, edítala o anúlala antes de registrar otra.');
            }

            $pct = round((float)str_replace(',', '.', (string)($d['avance_acumulado_pct'] ?? 0)), 2);
            $desde = !empty($d['periodo_desde']) ? $d['periodo_desde'] : null;
            $hasta = !empty($d['periodo_hasta']) ? $d['periodo_hasta'] : null;
            $obs = trim((string)($d['observaciones'] ?? ''));
            if ($pct <= 0 || $pct > 100) { throw new Exception('El avance acumulado debe estar entre 0 % y 100 %.'); }
            if (!$desde || !$hasta) { throw new Exception('Indica el periodo valorizado (desde y hasta).'); }
            if ($hasta < $desde) { throw new Exception('El fin del periodo no puede ser anterior a su inicio.'); }
            if ($hasta > date('Y-m-d', strtotime('+31 days'))) { throw new Exception('El periodo valorizado no puede terminar en el futuro.'); }

            $prev = $this->ultimaVigente($subId, $valId);
            $pctPrev = $prev ? (float)$prev['avance_acumulado_pct'] : 0.0;
            if ($pct <= $pctPrev + self::EPS) { throw new Exception("El avance acumulado debe ser mayor al de la valorización anterior ({$pctPrev} %)."); }
            if ($prev && $prev['periodo_hasta'] && $desde <= $prev['periodo_hasta']) { throw new Exception('El periodo debe comenzar después del fin del periodo anterior (' . $prev['periodo_hasta'] . ').'); }

            $amortPrev = (float)dbQueryOne("SELECT COALESCE(SUM(amortizacion_adelanto), 0) a FROM subcontrato_valorizaciones
                                            WHERE subcontrato_id = ? AND estado <> 'anulada'" . ($valId ? " AND id <> " . (int)$valId : ''), [$subId])['a'];
            $c = self::calcular($s, $pct, $prev ? (float)$prev['bruto_acumulado'] : 0.0, $amortPrev);
            if ($c['monto_bruto'] <= 0) { throw new Exception('Con ese avance el monto valorizado del periodo es 0.'); }

            if ($valId) {
                dbExecute("UPDATE subcontrato_valorizaciones SET periodo_desde = ?, periodo_hasta = ?, avance_acumulado_pct = ?, monto_bruto = ?, bruto_acumulado = ?,
                                  amortizacion_adelanto = ?, retencion = ?, igv = ?, neto_pagar = ?, observaciones = ? WHERE id = ?",
                    [$desde, $hasta, $pct, $c['monto_bruto'], $c['bruto_acumulado'], $c['amortizacion_adelanto'], $c['retencion'], $c['igv'], $c['neto_pagar'],
                     $obs !== '' ? mb_substr($obs, 0, 500) : null, $valId]);
                $id = $valId;
            } else {
                $n = (int)dbQueryOne("SELECT COALESCE(MAX(numero), 0) m FROM subcontrato_valorizaciones WHERE subcontrato_id = ?", [$subId])['m'] + 1;
                dbExecute("INSERT INTO subcontrato_valorizaciones (subcontrato_id, numero, periodo_desde, periodo_hasta, avance_acumulado_pct, monto_bruto, bruto_acumulado,
                                  amortizacion_adelanto, retencion, igv, neto_pagar, estado, observaciones, registrado_por)
                           VALUES (?,?,?,?,?,?,?,?,?,?,?, 'borrador', ?, ?)",
                    [$subId, $n, $desde, $hasta, $pct, $c['monto_bruto'], $c['bruto_acumulado'], $c['amortizacion_adelanto'], $c['retencion'], $c['igv'], $c['neto_pagar'],
                     $obs !== '' ? mb_substr($obs, 0, 500) : null, $userId ?: null]);
                $id = (int)$conn->lastInsertId();
            }
            $conn->commit();
            return $id;
        } catch (Throwable $e) { $conn->rollBack(); throw $e; }
    }

    private function valorizacionConContrato(int $valId): array {
        $v = dbQueryOne("SELECT v.*, s.tarea_id, s.estado AS contrato_estado FROM subcontrato_valorizaciones v
                         JOIN subcontratos s ON s.id = v.subcontrato_id WHERE v.id = ? FOR UPDATE", [$valId]);
        if (!$v) { throw new Exception('La valorización no existe.'); }
        return $v;
    }

    public function aprobarValorizacion(int $valId, int $userId): void {
        $conn = getConnection();
        $conn->beginTransaction();
        try {
            $v = $this->valorizacionConContrato($valId);
            if ($v['estado'] !== 'borrador') { throw new Exception('Solo se aprueba una valorización en borrador.'); }
            if ($v['contrato_estado'] !== 'activo') { throw new Exception('El subcontrato debe estar activo para aprobar valorizaciones.'); }
            $pend = dbQueryOne("SELECT COUNT(*) c FROM subcontrato_valorizaciones WHERE subcontrato_id = ? AND numero < ? AND estado = 'borrador'", [$v['subcontrato_id'], $v['numero']]);
            if ((int)$pend['c'] > 0) { throw new Exception('Aprueba primero las valorizaciones anteriores.'); }
            $tot = dbQueryOne("SELECT COALESCE(SUM(monto_bruto), 0) t FROM subcontrato_valorizaciones WHERE subcontrato_id = ? AND estado IN ('aprobada','pagada')", [$v['subcontrato_id']]);
            $monto = (float)dbQueryOne("SELECT monto FROM subcontratos WHERE id = ?", [$v['subcontrato_id']])['monto'];
            if ((float)$tot['t'] + (float)$v['monto_bruto'] > $monto + self::EPS) { throw new Exception('Lo valorizado superaría el monto contratado.'); }
            dbExecute("UPDATE subcontrato_valorizaciones SET estado = 'aprobada', aprobado_por = ?, fecha_aprobacion = NOW() WHERE id = ? AND estado = 'borrador'", [$userId ?: null, $valId]);
            $conn->commit();
        } catch (Throwable $e) { $conn->rollBack(); throw $e; }
        if ($v['tarea_id']) { $this->tareas->recalcularCostoRealTarea((int)$v['tarea_id']); }
    }

    public function pagarValorizacion(int $valId, string $fechaPago, string $nroFactura): void {
        $d = DateTime::createFromFormat('Y-m-d', $fechaPago);
        if (!$d || $d->format('Y-m-d') !== $fechaPago) { throw new Exception('Indica una fecha de pago válida.'); }
        if ($fechaPago > date('Y-m-d')) { throw new Exception('La fecha de pago no puede ser futura.'); }
        $nroFactura = mb_substr(trim($nroFactura), 0, 50);
        $conn = getConnection();
        $conn->beginTransaction();
        try {
            $v = $this->valorizacionConContrato($valId);
            if ($v['estado'] !== 'aprobada') { throw new Exception('Solo se paga una valorización aprobada.'); }
            dbExecute("UPDATE subcontrato_valorizaciones SET estado = 'pagada', fecha_pago = ?, nro_factura = ? WHERE id = ? AND estado = 'aprobada'",
                [$fechaPago, $nroFactura !== '' ? $nroFactura : null, $valId]);
            $conn->commit();
        } catch (Throwable $e) { $conn->rollBack(); throw $e; }
    }

    /** Anula la última valorización vigente (borrador o aprobada; no una ya pagada). */
    public function anularValorizacion(int $valId): void {
        $conn = getConnection();
        $conn->beginTransaction();
        try {
            $v = $this->valorizacionConContrato($valId);
            if (!in_array($v['estado'], ['borrador', 'aprobada'], true)) { throw new Exception($v['estado'] === 'pagada' ? 'Una valorización pagada no se puede anular.' : 'La valorización ya está anulada.'); }
            $ult = $this->ultimaVigente((int)$v['subcontrato_id']);
            if (!$ult || (int)$ult['id'] !== $valId) { throw new Exception('Solo se puede anular la última valorización vigente.'); }
            dbExecute("UPDATE subcontrato_valorizaciones SET estado = 'anulada' WHERE id = ?", [$valId]);
            $conn->commit();
        } catch (Throwable $e) { $conn->rollBack(); throw $e; }
        if ($v['tarea_id']) { $this->tareas->recalcularCostoRealTarea((int)$v['tarea_id']); }
    }
}
