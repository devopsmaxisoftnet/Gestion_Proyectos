<?php
/**
 * Datos de los dashboards de las fases 2 a 5 del modelo de integración.
 *  - Fase 2 · Planificación y proyectos   → avance físico vs financiero, WBS, plazos, horas-hombre
 *  - Fase 3 · Ejecución y operación       → compras, proveedores, almacén y stock
 *  - Fase 4 · Control económico           → valorizaciones, cuentas por cobrar, tesorería
 *  - Fase 5 · Análisis y rentabilidad     → margen proyectado, CPI/SPI, cartera por línea
 *
 * Cada método devuelve arrays listos para la vista (números ya casteados, series completas).
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/TareaModel.php';
require_once __DIR__ . '/CompraModel.php';
require_once __DIR__ . '/AlmacenModel.php';

class DashboardFasesModel {

    private const MESES = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    // ═════════════════════════════════════════════════════════════════════
    //  Utilidades
    // ═════════════════════════════════════════════════════════════════════

    /** Lista de los últimos N meses [clave 'Y-m' => índice] y sus etiquetas */
    private function ultimosMeses(int $n): array {
        $claves = []; $labels = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -$i months");
            $claves[date('Y-m', $ts)] = count($labels);
            $labels[] = self::MESES[(int)date('n', $ts)] . ' ' . date('y', $ts);
        }
        return [$claves, $labels];
    }

    /** Avance esperado (0-100) según el tiempo transcurrido del cronograma */
    private function avancePlanificado(?string $inicio, ?string $fin): float {
        if (!$inicio || !$fin) { return 0.0; }
        $i = strtotime($inicio); $f = strtotime($fin); $h = strtotime(date('Y-m-d'));
        if ($f <= $i) { return 100.0; }
        return round(max(0, min(1, ($h - $i) / ($f - $i))) * 100, 1);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  FASE 2 — Planificación y proyectos
    // ═════════════════════════════════════════════════════════════════════

    public function fase2(): array {
        $hoy = date('Y-m-d');

        // Proyectos
        $porEstado = [];
        foreach (dbQueryAll("SELECT estado, COUNT(*) n FROM proyectos GROUP BY estado") as $r) { $porEstado[$r['estado']] = (int)$r['n']; }
        $totalProyectos = array_sum($porEstado);

        $ej = dbQueryOne("SELECT COUNT(*) n, COALESCE(SUM(monto_presupuesto),0) pres, COALESCE(SUM(monto_ejecutado),0) ejec,
                                 COALESCE(SUM(avance_porcentaje * monto_presupuesto) / NULLIF(SUM(monto_presupuesto),0), 0) av_pond
                          FROM proyectos WHERE estado = 'en_ejecucion'") ?: [];

        // Atención: proyectos con alertas de plazo, costo o avance
        $proyectosActivos = dbQueryAll(
            "SELECT p.id, p.codigo, p.nombre, p.estado, p.avance_porcentaje AS av, p.monto_presupuesto AS pres, p.monto_ejecutado AS ejec,
                    p.fecha_inicio, p.fecha_fin_planificada AS ff,
                    (SELECT COUNT(*) FROM tareas t WHERE t.proyecto_id = p.id AND t.estado = 'bloqueada') AS bloqueadas
             FROM proyectos p WHERE p.estado IN ('planificacion', 'en_ejecucion')");

        $atencion = [];
        $atrasados = 0;
        foreach ($proyectosActivos as $p) {
            $av = (float)$p['av']; $pres = (float)$p['pres']; $ejec = (float)$p['ejec'];
            $consumo = $pres > 0 ? $ejec / $pres * 100 : 0;
            $motivos = []; $peso = 0;

            if ($ejec > $pres && $pres > 0) { $motivos[] = ['danger', 'Sobrecosto ' . round($consumo - 100, 1) . '%']; $peso += 100; }
            if (!empty($p['ff']) && $p['ff'] < $hoy && $av < 100) {
                $dias = (int)((strtotime($hoy) - strtotime($p['ff'])) / 86400);
                $motivos[] = ['danger', "Plazo vencido hace $dias d"]; $peso += 60 + min(30, $dias / 10); $atrasados++;
            }
            if ($p['estado'] === 'en_ejecucion' && $consumo - $av > 15) {
                $motivos[] = ['warn', 'Gasto ' . round($consumo) . '% vs avance ' . round($av) . '%']; $peso += 30;
            }
            if ((int)$p['bloqueadas'] > 0) { $motivos[] = ['warn', (int)$p['bloqueadas'] . ' actividad(es) bloqueada(s)']; $peso += 20; }

            if ($motivos) {
                $atencion[] = ['id' => (int)$p['id'], 'codigo' => $p['codigo'], 'nombre' => $p['nombre'], 'estado' => $p['estado'],
                               'avance' => $av, 'consumo' => round($consumo, 1), 'motivos' => $motivos, 'peso' => $peso];
            }
        }
        usort($atencion, fn($a, $b) => $b['peso'] <=> $a['peso']);

        // Avance físico vs consumo financiero (proyectos en ejecución, los de mayor presupuesto)
        $top = dbQueryAll("SELECT codigo, nombre, avance_porcentaje AS av, monto_presupuesto AS pres, monto_ejecutado AS ejec
                           FROM proyectos WHERE estado = 'en_ejecucion' AND monto_presupuesto > 0
                           ORDER BY monto_presupuesto DESC LIMIT 10");
        $avanceVsCosto = ['labels' => [], 'nombres' => [], 'avance' => [], 'consumo' => []];
        foreach ($top as $p) {
            $avanceVsCosto['labels'][] = $p['codigo'];
            $avanceVsCosto['nombres'][] = $p['nombre'];
            $avanceVsCosto['avance'][] = round((float)$p['av'], 1);
            $avanceVsCosto['consumo'][] = round((float)$p['ejec'] / (float)$p['pres'] * 100, 1);
        }

        // WBS
        $t = dbQueryOne("SELECT COUNT(*) total,
                                SUM(estado = 'completada') completadas, SUM(estado = 'en_proceso') en_proceso,
                                SUM(estado = 'pendiente') pendientes, SUM(estado = 'bloqueada') bloqueadas,
                                SUM(estado <> 'completada' AND fecha_fin < CURDATE()) vencidas
                         FROM tareas") ?: [];

        // Horas-hombre (tareo)
        [$claves, $labels] = $this->ultimosMeses(6);
        $horas = ['labels' => $labels, 'normales' => array_fill(0, 6, 0.0), 'extras' => array_fill(0, 6, 0.0)];
        foreach (dbQueryAll("SELECT DATE_FORMAT(fecha, '%Y-%m') mes, SUM(horas_normales) hn, SUM(horas_extras) he
                             FROM asistencias WHERE fecha >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH) GROUP BY mes") as $r) {
            if (isset($claves[$r['mes']])) {
                $horas['normales'][$claves[$r['mes']]] = round((float)$r['hn'], 1);
                $horas['extras'][$claves[$r['mes']]] = round((float)$r['he'], 1);
            }
        }
        $hm = dbQueryOne("SELECT COALESCE(SUM(horas_normales + horas_extras), 0) h, COALESCE(SUM(costo_calculado), 0) c
                          FROM asistencias WHERE fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')") ?: [];

        $costos = (new TareaModel())->getCostosDirectosGlobal();

        return [
            'kpis' => [
                'proyectos_total' => $totalProyectos,
                'proyectos_ejecucion' => (int)($ej['n'] ?? 0),
                'proyectos_planificacion' => $porEstado['planificacion'] ?? 0,
                'presupuesto_ejecucion' => (float)($ej['pres'] ?? 0),
                'ejecutado_ejecucion' => (float)($ej['ejec'] ?? 0),
                'avance_ponderado' => round((float)($ej['av_pond'] ?? 0), 1),
                'consumo_pct' => (float)($ej['pres'] ?? 0) > 0 ? round((float)$ej['ejec'] / (float)$ej['pres'] * 100, 1) : 0.0,
                'atrasados' => $atrasados,
                'tareas_total' => (int)($t['total'] ?? 0),
                'tareas_completadas' => (int)($t['completadas'] ?? 0),
                'tareas_bloqueadas' => (int)($t['bloqueadas'] ?? 0),
                'tareas_vencidas' => (int)($t['vencidas'] ?? 0),
                'horas_mes' => round((float)($hm['h'] ?? 0), 1),
                'costo_mo_mes' => (float)($hm['c'] ?? 0),
            ],
            'graficos' => [
                'avance_vs_costo' => $avanceVsCosto,
                'estados_proyecto' => $porEstado,
                'estados_tareas' => [
                    'Completadas' => (int)($t['completadas'] ?? 0), 'En proceso' => (int)($t['en_proceso'] ?? 0),
                    'Pendientes' => (int)($t['pendientes'] ?? 0), 'Bloqueadas' => (int)($t['bloqueadas'] ?? 0),
                ],
                'costos' => $costos,
                'horas' => $horas,
            ],
            'atencion' => array_slice($atencion, 0, 8),
        ];
    }

    // ═════════════════════════════════════════════════════════════════════
    //  FASE 3 — Ejecución y operación
    // ═════════════════════════════════════════════════════════════════════

    public function fase3(): array {
        $compras = new CompraModel();
        $almacen = new AlmacenModel();

        $resumen = $compras->getResumen(null);
        $estados = $compras->getComprasPorEstado(null);
        $kAlm = $almacen->getKpis();

        $recibidas = (int)($estados['recibida']['cantidad'] ?? 0);
        $aprobadas = (int)($estados['aprobada']['cantidad'] ?? 0);
        $cumplimiento = ($recibidas + $aprobadas) > 0 ? round($recibidas / ($recibidas + $aprobadas) * 100, 1) : 0.0;

        $comprado = dbQueryOne("SELECT COALESCE(SUM(subtotal), 0) m FROM ordenes_compra WHERE estado IN ('aprobada','recibida')");
        $mesActual = dbQueryOne("SELECT COALESCE(SUM(subtotal), 0) m, COUNT(*) n FROM ordenes_compra
                                 WHERE estado IN ('aprobada','recibida') AND fecha_emision >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");

        // Compras por mes (monto neto de OC aprobadas/recibidas)
        [$claves, $labels] = $this->ultimosMeses(6);
        $porMes = ['labels' => $labels, 'monto' => array_fill(0, 6, 0.0), 'ordenes' => array_fill(0, 6, 0)];
        foreach (dbQueryAll("SELECT DATE_FORMAT(fecha_emision, '%Y-%m') mes, SUM(subtotal) monto, COUNT(*) n
                             FROM ordenes_compra WHERE estado IN ('aprobada','recibida')
                               AND fecha_emision >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH) GROUP BY mes") as $r) {
            if (isset($claves[$r['mes']])) {
                $porMes['monto'][$claves[$r['mes']]] = round((float)$r['monto'], 2);
                $porMes['ordenes'][$claves[$r['mes']]] = (int)$r['n'];
            }
        }

        $proveedores = dbQueryAll("SELECT COALESCE(pv.razon_social, oc.proveedor_nombre, 'Sin proveedor') AS nombre,
                                          SUM(oc.subtotal) AS monto, COUNT(*) AS n
                                   FROM ordenes_compra oc LEFT JOIN proveedores pv ON pv.id = oc.proveedor_id
                                   WHERE oc.estado IN ('aprobada','recibida')
                                   GROUP BY COALESCE(pv.razon_social, oc.proveedor_nombre, 'Sin proveedor')
                                   ORDER BY monto DESC LIMIT 6");

        $porAprobar = dbQueryAll("SELECT oc.id, oc.codigo, oc.fecha_emision, oc.total, oc.moneda,
                                         COALESCE(pv.razon_social, oc.proveedor_nombre) AS proveedor, p.codigo AS proyecto,
                                         DATEDIFF(CURDATE(), oc.fecha_emision) AS dias
                                  FROM ordenes_compra oc JOIN proyectos p ON p.id = oc.proyecto_id
                                  LEFT JOIN proveedores pv ON pv.id = oc.proveedor_id
                                  WHERE oc.estado = 'pendiente_aprobacion' ORDER BY oc.fecha_emision ASC LIMIT 6");

        $sub = dbQueryOne("SELECT COUNT(*) n, COALESCE(SUM(monto), 0) m FROM subcontratos WHERE estado = 'activo'") ?: [];

        return [
            'kpis' => [
                'comprado' => (float)($comprado['m'] ?? 0),
                'comprado_mes' => (float)($mesActual['m'] ?? 0),
                'ordenes_mes' => (int)($mesActual['n'] ?? 0),
                'cumplimiento' => $cumplimiento,
                'pendientes_n' => $resumen['total_pendientes'],
                'pendientes_monto' => $resumen['monto_pendiente'],
                'por_recepcionar' => $kAlm['por_recepcionar'],
                'valor_inventario' => $kAlm['valor_inventario'],
                'criticos' => $kAlm['criticos'],
                'sin_actividad' => $resumen['monto_sin_actividad'],
                'lineas_sin_actividad' => $resumen['lineas_sin_actividad'],
                'subcontratos_n' => (int)($sub['n'] ?? 0),
                'subcontratos_monto' => (float)($sub['m'] ?? 0),
                'salidas_mes' => $kAlm['salidas_mes']['monto'],
            ],
            'graficos' => [
                'compras_mes' => $porMes,
                'estados' => $estados,
                'proveedores' => $proveedores,
                'almacen_meses' => $almacen->getMovimientosPorMes(6),
                'top_actividades' => $compras->getTopActividades(null, 6),
                'critico' => $almacen->getStockCritico(8),
            ],
            'por_aprobar' => $porAprobar,
            'por_recepcionar' => array_slice($almacen->getOrdenesRecepcionables(), 0, 5),
        ];
    }

    // ═════════════════════════════════════════════════════════════════════
    //  FASE 4 — Control económico y financiero
    // ═════════════════════════════════════════════════════════════════════

    public function fase4(): array {
        $porEstado = ['borrador' => 0.0, 'presentada' => 0.0, 'aprobada' => 0.0, 'pagada' => 0.0];
        $conteo = $porEstado;
        foreach (dbQueryAll("SELECT estado, COUNT(*) n, COALESCE(SUM(monto), 0) m FROM valorizaciones GROUP BY estado") as $r) {
            $porEstado[$r['estado']] = (float)$r['m'];
            $conteo[$r['estado']] = (int)$r['n'];
        }
        $valorizado = $porEstado['presentada'] + $porEstado['aprobada'] + $porEstado['pagada'];
        $porCobrar = $porEstado['presentada'] + $porEstado['aprobada'];
        $cobrado = $porEstado['pagada'];

        // Contratado y costo de los proyectos que ya valorizan
        $base = dbQueryOne("SELECT COALESCE(SUM(c.monto_contrato), 0) contratado, COALESCE(SUM(p.monto_ejecutado), 0) costo
                            FROM proyectos p LEFT JOIN contratos c ON c.id = p.contrato_id
                            WHERE p.id IN (SELECT DISTINCT proyecto_id FROM valorizaciones)") ?: [];
        $contratado = (float)($base['contratado'] ?? 0);
        $costo = (float)($base['costo'] ?? 0);

        // Cuentas por cobrar con antigüedad
        $pendientes = dbQueryAll("SELECT v.id, v.numero, v.periodo, v.monto, v.estado, p.codigo AS proyecto, p.nombre AS proyecto_nombre,
                                         COALESCE(v.fecha_presentacion, DATE(v.created_at)) AS fecha,
                                         DATEDIFF(CURDATE(), COALESCE(v.fecha_presentacion, DATE(v.created_at))) AS dias
                                  FROM valorizaciones v JOIN proyectos p ON p.id = v.proyecto_id
                                  WHERE v.estado IN ('presentada', 'aprobada')
                                  ORDER BY fecha ASC");
        $diasProm = 0; $montoPend = 0;
        foreach ($pendientes as $p) { $diasProm += (float)$p['dias'] * (float)$p['monto']; $montoPend += (float)$p['monto']; }
        $diasProm = $montoPend > 0 ? round($diasProm / $montoPend) : 0;   // antigüedad ponderada por monto

        // Valorizaciones por mes (12 meses) apiladas por estado
        [$claves, $labels] = $this->ultimosMeses(12);
        $serie = ['labels' => $labels, 'presentada' => array_fill(0, 12, 0.0), 'aprobada' => array_fill(0, 12, 0.0), 'pagada' => array_fill(0, 12, 0.0)];
        foreach (dbQueryAll("SELECT DATE_FORMAT(COALESCE(fecha_presentacion, created_at), '%Y-%m') mes, estado, SUM(monto) m
                             FROM valorizaciones WHERE estado IN ('presentada','aprobada','pagada')
                               AND COALESCE(fecha_presentacion, created_at) >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
                             GROUP BY mes, estado") as $r) {
            if (isset($claves[$r['mes']])) { $serie[$r['estado']][$claves[$r['mes']]] = round((float)$r['m'], 2); }
        }

        // Valorizado vs costo ejecutado por proyecto
        $porProyecto = ['labels' => [], 'nombres' => [], 'valorizado' => [], 'cobrado' => [], 'costo' => []];
        foreach (dbQueryAll("SELECT p.codigo, p.nombre, p.monto_ejecutado AS costo,
                                    SUM(CASE WHEN v.estado IN ('presentada','aprobada','pagada') THEN v.monto ELSE 0 END) AS valorizado,
                                    SUM(CASE WHEN v.estado = 'pagada' THEN v.monto ELSE 0 END) AS cobrado
                             FROM valorizaciones v JOIN proyectos p ON p.id = v.proyecto_id
                             GROUP BY p.id, p.codigo, p.nombre, p.monto_ejecutado
                             HAVING valorizado > 0 ORDER BY valorizado DESC LIMIT 8") as $r) {
            $porProyecto['labels'][] = $r['codigo']; $porProyecto['nombres'][] = $r['nombre'];
            $porProyecto['valorizado'][] = round((float)$r['valorizado'], 2);
            $porProyecto['cobrado'][] = round((float)$r['cobrado'], 2);
            $porProyecto['costo'][] = round((float)$r['costo'], 2);
        }

        // Tesorería (movimientos reales de caja)
        [$claves6, $labels6] = $this->ultimosMeses(6);
        $teso = ['labels' => $labels6, 'ingresos' => array_fill(0, 6, 0.0), 'egresos' => array_fill(0, 6, 0.0)];
        foreach (dbQueryAll("SELECT DATE_FORMAT(fecha_pago, '%Y-%m') mes, tipo, SUM(monto) m FROM tesoreria_movimientos
                             WHERE fecha_pago >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH) GROUP BY mes, tipo") as $r) {
            if (isset($claves6[$r['mes']])) { $teso[$r['tipo'] === 'ingreso' ? 'ingresos' : 'egresos'][$claves6[$r['mes']]] = round((float)$r['m'], 2); }
        }
        $tt = dbQueryOne("SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN tipo='ingreso' THEN monto ELSE 0 END),0) i,
                                 COALESCE(SUM(CASE WHEN tipo='egreso' THEN monto ELSE 0 END),0) e FROM tesoreria_movimientos") ?: [];

        return [
            'kpis' => [
                'valorizado' => $valorizado,
                'cobrado' => $cobrado,
                'por_cobrar' => $porCobrar,
                'pct_cobrado' => $valorizado > 0 ? round($cobrado / $valorizado * 100, 1) : 0.0,
                'contratado' => $contratado,
                'pct_facturado' => $contratado > 0 ? round($valorizado / $contratado * 100, 1) : 0.0,
                'costo_ejecutado' => $costo,
                'flujo_neto' => $cobrado - $costo,
                'dias_promedio' => (int)$diasProm,
                'n_valorizaciones' => array_sum($conteo),
                'n_pendientes' => count($pendientes),
                'tesoreria_movs' => (int)($tt['n'] ?? 0),
                'tesoreria_neto' => (float)($tt['i'] ?? 0) - (float)($tt['e'] ?? 0),
            ],
            'graficos' => [
                'pipeline' => ['Borrador' => $porEstado['borrador'], 'Presentada' => $porEstado['presentada'], 'Aprobada' => $porEstado['aprobada'], 'Pagada' => $porEstado['pagada']],
                'mensual' => $serie,
                'por_proyecto' => $porProyecto,
                'tesoreria' => $teso,
            ],
            'pendientes' => array_slice($pendientes, 0, 8),
        ];
    }

    // ═════════════════════════════════════════════════════════════════════
    //  FASE 5 — Análisis y rentabilidad
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Indicadores tipo Valor Ganado por proyecto en ejecución:
     *  CPI = valor ganado / costo real        (≥ 1 = dentro del presupuesto)
     *  SPI = avance real / avance planificado (≥ 1 = al día)
     *  EAC = costo proyectado al cierre       (costo real ÷ avance; con avance < 10 % se usa el presupuesto)
     *  Margen proyectado = ingreso contratado − EAC
     */
    public function fase5(): array {
        $rows = dbQueryAll("SELECT p.id, p.codigo, p.nombre, p.avance_porcentaje AS av, p.monto_presupuesto AS pres, p.monto_ejecutado AS ejec,
                                   p.fecha_inicio, p.fecha_fin_planificada AS ff, COALESCE(c.monto_contrato, p.monto_presupuesto) AS ingreso
                            FROM proyectos p LEFT JOIN contratos c ON c.id = p.contrato_id
                            WHERE p.estado = 'en_ejecucion' AND p.monto_presupuesto > 0");

        $proy = [];
        $sumIng = $sumEac = $sumEv = $sumEjec = $sumPlan = $sumPres = 0.0;
        foreach ($rows as $r) {
            $av = (float)$r['av']; $pres = (float)$r['pres']; $ejec = (float)$r['ejec']; $ing = (float)$r['ingreso'];
            $ev = $av / 100 * $pres;
            $plan = $this->avancePlanificado($r['fecha_inicio'], $r['ff']);
            $eac = $av >= 10 ? $ejec / ($av / 100) : $pres;
            $margen = $ing - $eac;

            $proy[] = [
                'id' => (int)$r['id'], 'codigo' => $r['codigo'], 'nombre' => $r['nombre'],
                'ingreso' => $ing, 'eac' => round($eac, 2), 'margen' => round($margen, 2),
                'margen_pct' => $ing > 0 ? round($margen / $ing * 100, 1) : 0.0,
                'cpi' => $ejec > 0 ? round($ev / $ejec, 2) : null,
                'spi' => $plan > 0 ? round($av / $plan, 2) : null,
                'avance' => $av, 'planificado' => $plan,
            ];
            $sumIng += $ing; $sumEac += $eac; $sumEv += $ev; $sumEjec += $ejec; $sumPres += $pres; $sumPlan += $plan / 100 * $pres;
        }
        usort($proy, fn($a, $b) => $b['ingreso'] <=> $a['ingreso']);
        $ranking = $proy;
        usort($ranking, fn($a, $b) => $a['margen_pct'] <=> $b['margen_pct']);

        $enRiesgo = count(array_filter($proy, fn($p) => $p['margen'] < 0 || ($p['cpi'] !== null && $p['cpi'] < 0.9)));
        $margenTotal = $sumIng - $sumEac;

        // Utilidad declarada en contratos (comprometida comercialmente)
        $ut = dbQueryOne("SELECT COALESCE(SUM(monto_utilidad), 0) u, COALESCE(SUM(monto_contrato), 0) m FROM contratos") ?: [];

        // Cartera contratada por línea de negocio
        $lineas = dbQueryAll("SELECT COALESCE(l.nombre, 'Sin línea') AS nombre, COALESCE(l.color, '#94a3b8') AS color,
                                     SUM(c.monto_contrato) AS monto, COUNT(*) AS n, COALESCE(SUM(c.monto_utilidad), 0) AS utilidad
                              FROM contratos c
                              LEFT JOIN oportunidades o ON o.id = c.oportunidad_id
                              LEFT JOIN lineas_negocio l ON l.id = o.linea_negocio_id
                              GROUP BY COALESCE(l.nombre, 'Sin línea'), COALESCE(l.color, '#94a3b8')
                              ORDER BY monto DESC");

        $top = array_slice($proy, 0, 8);
        $series = [
            'labels' => array_column($top, 'codigo'), 'nombres' => array_column($top, 'nombre'),
            'ingreso' => array_column($top, 'ingreso'), 'eac' => array_column($top, 'eac'),
        ];
        $indices = [
            'labels' => array_column($proy, 'codigo'), 'nombres' => array_column($proy, 'nombre'),
            'cpi' => array_map(fn($p) => $p['cpi'], $proy), 'spi' => array_map(fn($p) => $p['spi'], $proy),
        ];

        return [
            'kpis' => [
                'ingreso' => $sumIng,
                'eac' => $sumEac,
                'margen' => $margenTotal,
                'margen_pct' => $sumIng > 0 ? round($margenTotal / $sumIng * 100, 1) : 0.0,
                'cpi' => $sumEjec > 0 ? round($sumEv / $sumEjec, 2) : null,
                'spi' => $sumPlan > 0 ? round($sumEv / $sumPlan, 2) : null,
                'en_riesgo' => $enRiesgo,
                'proyectos' => count($proy),
                'utilidad_contratos' => (float)($ut['u'] ?? 0),
                'cartera_contratos' => (float)($ut['m'] ?? 0),
            ],
            'graficos' => [
                'ingreso_vs_costo' => $series,
                'indices' => $indices,
                'lineas' => array_map(fn($l) => ['nombre' => $l['nombre'], 'color' => $l['color'], 'monto' => (float)$l['monto'], 'n' => (int)$l['n'], 'utilidad' => (float)$l['utilidad']], $lineas),
            ],
            'ranking' => array_slice($ranking, 0, 8),
        ];
    }
}
