<?php
/**
 * Modelo de Asistencias y Tareos (RRHH y Control de Mano de Obra) — Costeo por actividad
 *
 * Cada registro de tareo imputa las horas de un profesional a UNA actividad del WBS (tarea_id) en
 * un día. Un mismo profesional puede tener varias filas el mismo día (una por actividad).
 * El costo se calcula al guardar con la tarifa vigente del profesional (queda como "foto" del
 * costo histórico) y dispara TareaModel::recalcularCostoRealTarea() de cada actividad afectada.
 *
 * Gestión de Proyectos - ERP Core
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/TareaModel.php';
require_once __DIR__ . '/CompraModel.php';

class AsistenciaModel {

    /** Máximo de horas que una persona puede registrar en un día, sumando todos los proyectos */
    private const MAX_HORAS_DIA = 24.0;

    // ═════════════════════════════════════════════════════════════════════
    //  CONSULTAS
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Nómina disponible para el tareo. Incluye a los marcados "En Proyecto" (activo = 0),
     * que son justamente quienes trabajan en obra; solo se ocultan si no tienen nombre.
     */
    public function getProfesionales(): array {
        return dbQueryAll("SELECT id, nombre, tipo_profesional, especialidad, costo_hora, costo_hora_extra, activo
                           FROM profesionales ORDER BY nombre ASC");
    }

    /** Registros del día de un proyecto (una fila por profesional × actividad) */
    public function getRegistrosDia(int $proyectoId, string $fecha): array {
        return dbQueryAll("SELECT a.id, a.profesional_id, a.tarea_id, a.horas_normales, a.horas_extras, a.costo_calculado,
                                  a.tipo_labor, a.observaciones, a.created_at, u.nombre AS registrado_por_nombre,
                                  p.nombre AS profesional_nombre
                           FROM asistencias a
                           JOIN profesionales p ON p.id = a.profesional_id
                           LEFT JOIN usuarios u ON u.id = a.registrado_por
                           WHERE a.proyecto_id = ? AND a.fecha = ?
                           ORDER BY p.nombre ASC, a.id ASC", [$proyectoId, $fecha]);
    }

    /** Horas que cada profesional ya tiene ese día en OTROS proyectos: [profesional_id => horas] */
    public function getHorasOtrosProyectos(int $proyectoId, string $fecha): array {
        $out = [];
        foreach (dbQueryAll("SELECT profesional_id, SUM(horas_normales + horas_extras) AS h
                             FROM asistencias WHERE fecha = ? AND proyecto_id <> ? GROUP BY profesional_id", [$fecha, $proyectoId]) as $r) {
            $out[(int)$r['profesional_id']] = (float)$r['h'];
        }
        return $out;
    }

    /** Resumen de la jornada y acumulado del proyecto */
    public function getResumen(int $proyectoId, string $fecha): array {
        $dia = dbQueryOne("SELECT COUNT(DISTINCT profesional_id) AS personas,
                                  COALESCE(SUM(horas_normales), 0) AS hn, COALESCE(SUM(horas_extras), 0) AS he,
                                  COALESCE(SUM(costo_calculado), 0) AS costo
                           FROM asistencias WHERE proyecto_id = ? AND fecha = ?", [$proyectoId, $fecha]) ?: [];
        $acum = dbQueryOne("SELECT COALESCE(SUM(horas_normales + horas_extras), 0) AS horas, COALESCE(SUM(horas_extras), 0) AS extras,
                                   COALESCE(SUM(costo_calculado), 0) AS costo, COUNT(DISTINCT profesional_id) AS personas,
                                   COUNT(DISTINCT fecha) AS dias,
                                   COALESCE(SUM(CASE WHEN tarea_id IS NULL THEN horas_normales + horas_extras ELSE 0 END), 0) AS sin_actividad
                            FROM asistencias WHERE proyecto_id = ?", [$proyectoId]) ?: [];
        $proy = dbQueryOne("SELECT avance_porcentaje FROM proyectos WHERE id = ?", [$proyectoId]) ?: [];

        $horas = (float)($acum['horas'] ?? 0);
        $avance = (float)($proy['avance_porcentaje'] ?? 0);
        return [
            'personas_dia' => (int)($dia['personas'] ?? 0),
            'hn_dia' => (float)($dia['hn'] ?? 0),
            'he_dia' => (float)($dia['he'] ?? 0),
            'costo_dia' => (float)($dia['costo'] ?? 0),
            'horas_acum' => $horas,
            'costo_acum' => (float)($acum['costo'] ?? 0),
            'personas_acum' => (int)($acum['personas'] ?? 0),
            'dias' => (int)($acum['dias'] ?? 0),
            'pct_extras' => $horas > 0 ? round((float)$acum['extras'] / $horas * 100, 1) : 0.0,
            'sin_actividad' => (float)($acum['sin_actividad'] ?? 0),
            'avance' => $avance,
            'hh_por_avance' => ($avance > 0 && $horas > 0) ? round($horas / $avance, 1) : null,
        ];
    }

    /**
     * Analítica de productividad del proyecto.
     * Productividad = horas-hombre invertidas por cada 1 % de avance de la actividad (menor = mejor).
     */
    public function getAnalitica(int $proyectoId): array {
        // Por actividad
        $act = dbQueryAll("SELECT t.id, t.codigo, t.nombre, t.progreso,
                                  SUM(a.horas_normales) AS hn, SUM(a.horas_extras) AS he, SUM(a.costo_calculado) AS costo,
                                  COUNT(DISTINCT a.profesional_id) AS personas
                           FROM asistencias a JOIN tareas t ON t.id = a.tarea_id
                           WHERE a.proyecto_id = ?
                           GROUP BY t.id, t.codigo, t.nombre, t.progreso
                           ORDER BY (SUM(a.horas_normales) + SUM(a.horas_extras)) DESC", [$proyectoId]);
        $porActividad = [];
        foreach ($act as $r) {
            $horas = (float)$r['hn'] + (float)$r['he'];
            $prog = (float)$r['progreso'];
            $porActividad[] = [
                'id' => (int)$r['id'], 'codigo' => $r['codigo'], 'nombre' => $r['nombre'], 'progreso' => $prog,
                'hn' => (float)$r['hn'], 'he' => (float)$r['he'], 'horas' => $horas, 'costo' => (float)$r['costo'],
                'personas' => (int)$r['personas'],
                'hh_por_avance' => $prog > 0 ? round($horas / $prog, 2) : null,
                'costo_por_avance' => $prog > 0 ? round((float)$r['costo'] / $prog, 2) : null,
            ];
        }
        $sin = dbQueryOne("SELECT COALESCE(SUM(horas_normales), 0) hn, COALESCE(SUM(horas_extras), 0) he, COALESCE(SUM(costo_calculado), 0) costo
                           FROM asistencias WHERE proyecto_id = ? AND tarea_id IS NULL", [$proyectoId]);
        if ($sin && ((float)$sin['hn'] + (float)$sin['he']) > 0) {
            $porActividad[] = ['id' => 0, 'codigo' => '—', 'nombre' => 'Sin actividad asignada', 'progreso' => 0.0,
                'hn' => (float)$sin['hn'], 'he' => (float)$sin['he'], 'horas' => (float)$sin['hn'] + (float)$sin['he'],
                'costo' => (float)$sin['costo'], 'personas' => 0, 'hh_por_avance' => null, 'costo_por_avance' => null];
        }

        // Tendencia diaria (últimos 14 días, con días sin registro en cero)
        $dias = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $dias[$d] = ['fecha' => $d, 'label' => date('d/m', strtotime($d)), 'horas' => 0.0, 'costo' => 0.0];
        }
        foreach (dbQueryAll("SELECT fecha, SUM(horas_normales + horas_extras) AS h, SUM(costo_calculado) AS c
                             FROM asistencias WHERE proyecto_id = ? AND fecha >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY fecha", [$proyectoId]) as $r) {
            if (isset($dias[$r['fecha']])) { $dias[$r['fecha']]['horas'] = round((float)$r['h'], 2); $dias[$r['fecha']]['costo'] = round((float)$r['c'], 2); }
        }

        // Por profesional
        $porProfesional = array_map(fn($r) => [
            'nombre' => $r['nombre'], 'horas' => round((float)$r['h'], 2), 'extras' => round((float)$r['he'], 2),
            'costo' => round((float)$r['c'], 2), 'dias' => (int)$r['dias'],
        ], dbQueryAll("SELECT p.nombre, SUM(a.horas_normales + a.horas_extras) AS h, SUM(a.horas_extras) AS he,
                              SUM(a.costo_calculado) AS c, COUNT(DISTINCT a.fecha) AS dias
                       FROM asistencias a JOIN profesionales p ON p.id = a.profesional_id
                       WHERE a.proyecto_id = ? GROUP BY p.id, p.nombre ORDER BY c DESC LIMIT 8", [$proyectoId]));

        return ['actividades' => $porActividad, 'diario' => array_values($dias), 'profesionales' => $porProfesional];
    }

    public function getProyectos(): array {
        return dbQueryAll("SELECT id, codigo, nombre, estado FROM proyectos ORDER BY fecha_inicio DESC, nombre ASC");
    }

    /** Actividades del proyecto en orden de árbol (con es_hoja) para el selector */
    public function getTareasProyecto(int $proyectoId): array {
        return (new CompraModel())->getTareasProyecto($proyectoId);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  ESCRITURA
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Guarda la hoja de tareo del día de un proyecto (reemplaza los registros de ese proyecto y fecha).
     *
     * @param array $items [['profesional_id','tarea_id','horas_normales','horas_extras','tipo_labor','observaciones'], ...]
     * @return array{registros:int, horas:float, costo:float}
     * @throws Exception con mensaje apto para el usuario
     */
    public function guardarDia(int $proyectoId, string $fecha, array $items, int $registradoPor): array {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) { throw new Exception('La fecha del tareo no es válida.'); }
        if ($fecha > date('Y-m-d')) { throw new Exception('No se puede registrar tareo de fechas futuras.'); }
        if (!dbQueryOne("SELECT id FROM proyectos WHERE id = ?", [$proyectoId])) { throw new Exception('El proyecto no existe.'); }

        // ── Normalizar: una fila por (profesional, actividad); las repetidas suman horas ──
        $filas = [];
        foreach ($items as $n => $it) {
            $pos = $n + 1;
            $pid = (int)($it['profesional_id'] ?? 0);
            $tid = (int)($it['tarea_id'] ?? 0);
            $hn = round((float)($it['horas_normales'] ?? 0), 2);
            $he = round((float)($it['horas_extras'] ?? 0), 2);

            if ($pid <= 0 && $hn == 0 && $he == 0) { continue; }                 // fila vacía
            if ($pid <= 0)   { throw new Exception("Fila $pos: selecciona al colaborador."); }
            if ($hn < 0 || $he < 0 || $hn > self::MAX_HORAS_DIA || $he > self::MAX_HORAS_DIA) {
                throw new Exception("Fila $pos: las horas deben estar entre 0 y " . self::MAX_HORAS_DIA . '.');
            }
            if ($hn + $he <= 0) { continue; }                                      // sin horas: no se registra
            if ($tid <= 0)   { throw new Exception("Fila $pos: la actividad del WBS es obligatoria para imputar el costo."); }

            $k = $pid . ':' . $tid;
            if (!isset($filas[$k])) {
                $filas[$k] = ['profesional_id' => $pid, 'tarea_id' => $tid, 'hn' => 0.0, 'he' => 0.0, 'tipo_labor' => '', 'obs' => ''];
            }
            $filas[$k]['hn'] = round($filas[$k]['hn'] + $hn, 2);
            $filas[$k]['he'] = round($filas[$k]['he'] + $he, 2);
            $tipo = mb_substr(trim((string)($it['tipo_labor'] ?? '')), 0, 100);
            if ($filas[$k]['tipo_labor'] === '' && $tipo !== '') { $filas[$k]['tipo_labor'] = $tipo; }
            $obs = trim((string)($it['observaciones'] ?? ''));
            if ($obs !== '') { $filas[$k]['obs'] = trim($filas[$k]['obs'] . ' ' . $obs); }
        }

        // ── Validar actividades y profesionales; tarifas vigentes ──
        $tareaIds = array_values(array_unique(array_column($filas, 'tarea_id')));
        $validas = [];
        if ($tareaIds) {
            $in = implode(',', array_fill(0, count($tareaIds), '?'));
            foreach (dbQueryAll("SELECT id FROM tareas WHERE proyecto_id = ? AND id IN ($in)", array_merge([$proyectoId], $tareaIds)) as $r) { $validas[(int)$r['id']] = true; }
        }
        $profIds = array_values(array_unique(array_column($filas, 'profesional_id')));
        $profes = [];
        if ($profIds) {
            $in = implode(',', array_fill(0, count($profIds), '?'));
            foreach (dbQueryAll("SELECT id, nombre, costo_hora, costo_hora_extra FROM profesionales WHERE id IN ($in)", $profIds) as $r) { $profes[(int)$r['id']] = $r; }
        }
        $horasPersona = [];
        foreach ($filas as $f) {
            if (!isset($validas[$f['tarea_id']])) { throw new Exception('Una de las actividades no pertenece al proyecto seleccionado.'); }
            if (!isset($profes[$f['profesional_id']])) { throw new Exception('Uno de los colaboradores no existe en la nómina.'); }
            $horasPersona[$f['profesional_id']] = ($horasPersona[$f['profesional_id']] ?? 0) + $f['hn'] + $f['he'];
        }

        // Tope diario por persona considerando lo que ya tiene en otros proyectos ese día
        $otros = $this->getHorasOtrosProyectos($proyectoId, $fecha);
        foreach ($horasPersona as $pid => $h) {
            $total = $h + ($otros[$pid] ?? 0);
            if ($total > self::MAX_HORAS_DIA + 0.001) {
                throw new Exception("{$profes[$pid]['nombre']} suma " . number_format($total, 1) . ' h el ' . date('d/m/Y', strtotime($fecha))
                    . (($otros[$pid] ?? 0) > 0 ? ' (incluye ' . number_format($otros[$pid], 1) . ' h en otros proyectos)' : '')
                    . ': el máximo es ' . self::MAX_HORAS_DIA . ' h por día.');
            }
        }

        // ── Persistir (reemplazo del día del proyecto) ──
        $conn = getConnection();
        $conn->beginTransaction();
        try {
            $previas = array_column(dbQueryAll("SELECT DISTINCT tarea_id FROM asistencias WHERE proyecto_id = ? AND fecha = ? AND tarea_id IS NOT NULL", [$proyectoId, $fecha]), 'tarea_id');
            dbExecute("DELETE FROM asistencias WHERE proyecto_id = ? AND fecha = ?", [$proyectoId, $fecha]);

            $stmt = $conn->prepare("INSERT INTO asistencias (proyecto_id, tarea_id, profesional_id, fecha, horas_normales, horas_extras,
                                                            costo_calculado, tipo_labor, observaciones, registrado_por)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $totalHoras = 0.0; $totalCosto = 0.0;
            foreach ($filas as $f) {
                $pr = $profes[$f['profesional_id']];
                $costo = round($f['hn'] * (float)$pr['costo_hora'] + $f['he'] * (float)$pr['costo_hora_extra'], 2);
                $stmt->execute([$proyectoId, $f['tarea_id'], $f['profesional_id'], $fecha, $f['hn'], $f['he'], $costo,
                                $f['tipo_labor'] !== '' ? $f['tipo_labor'] : 'Operativo', $f['obs'] !== '' ? mb_substr($f['obs'], 0, 1000) : null,
                                $registradoPor ?: null]);
                $totalHoras += $f['hn'] + $f['he']; $totalCosto += $costo;
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        // Costo real de cada actividad afectada (las del día anterior también, por si se quitaron filas)
        $this->recalcularTareas(array_merge($tareaIds, $previas));

        return ['registros' => count($filas), 'horas' => $totalHoras, 'costo' => round($totalCosto, 2)];
    }

    /** Elimina un registro y actualiza el costo de su actividad */
    public function delete(int $id): bool {
        $r = dbQueryOne("SELECT tarea_id FROM asistencias WHERE id = ?", [$id]);
        if (!$r) { return false; }
        dbExecute("DELETE FROM asistencias WHERE id = ?", [$id]);
        if (!empty($r['tarea_id'])) { $this->recalcularTareas([(int)$r['tarea_id']]); }
        return true;
    }

    /**
     * Recalcula el costo real de cada actividad (también propaga a padres y proyecto).
     * Un fallo aquí no revierte el tareo ya guardado: se registra y el usuario puede usar "Recalcular costos".
     */
    private function recalcularTareas(array $tareaIds): void {
        $tm = new TareaModel();
        foreach (array_unique(array_filter(array_map('intval', $tareaIds))) as $tid) {
            try { $tm->recalcularCostoRealTarea($tid); }
            catch (Throwable $e) { error_log("AsistenciaModel: no se pudo recalcular la actividad $tid: " . $e->getMessage()); }
        }
    }
}
