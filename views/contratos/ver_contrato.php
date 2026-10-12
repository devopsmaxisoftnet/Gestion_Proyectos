<?php
$ct = $data['contrato'] ?? [];
$pagos = $ct['pagos'] ?? [];
$m = $ct['metricas'] ?? [];

$montoContratado = floatval($ct['monto_contrato'] ?? 0);
$montoFacturado = floatval($m['sum_facturado'] ?? 0);
$montoIngreso = floatval($m['sum_ingreso'] ?? 0);
$montoRetenido = floatval($m['sum_retenido'] ?? 0);
$saldoPorFacturar = max(0, $montoContratado - $montoFacturado);
$porcentajeFacturado = $montoContratado > 0 ? min(100, round(($montoFacturado / $montoContratado) * 100, 1)) : 0;
$porcentajeCobrado = $montoFacturado > 0 ? min(100, round(($montoIngreso / $montoFacturado) * 100, 1)) : 0;
?>
<!DOCTYPE html>
<!-- Asegurar que el contexto del modal iframe cargue estilos y librerías completas -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<div class="contrato-detail-wrapper">
    <!-- Encabezado Principal del Contrato (TEMA CLARO ELEGANTE) -->
    <div class="contrato-hero-card light-theme">
        <div class="hero-left">
            <div class="hero-badges">
                <span class="hero-badge badge-year">
                    <i class="far fa-calendar-alt"></i> Ejercicio <?= $ct['anio'] ?? 2025 ?>
                </span>
                <span class="hero-badge <?= ($ct['tipo_gestion'] === 'propia') ? 'badge-propia' : 'badge-externa' ?>">
                    <i class="fas <?= ($ct['tipo_gestion'] === 'propia') ? 'fa-building' : 'fa-handshake' ?>"></i>
                    <?= ($ct['tipo_gestion'] === 'propia') ? 'Empresa Propia' : 'Empresa Externa' ?>
                </span>
                <?php if (!empty($ct['cui'])): ?>
                <span class="hero-badge badge-cui">
                    <i class="fas fa-barcode"></i> CUI: <?= htmlspecialchars($ct['cui']) ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($ct['estado_licitacion'])): ?>
                <span class="hero-badge badge-sub">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($ct['estado_licitacion']) ?>
                </span>
                <?php endif; ?>
            </div>

            <h1 class="hero-title">
                <?= htmlspecialchars($ct['nombre_corto'] ?: $ct['codigo']) ?>
            </h1>

            <div class="hero-meta">
                <span class="meta-item">
                    <i class="fas fa-file-contract" style="color: #2563eb;"></i>
                    <span style="color: #64748b;">N° Contrato:</span> <strong><?= htmlspecialchars($ct['codigo']) ?></strong>
                </span>
                <span class="meta-sep">•</span>
                <span class="meta-item">
                    <i class="fas fa-landmark" style="color: #7c3aed;"></i>
                    <span><?= htmlspecialchars($ct['cliente_nombre'] ?? 'Entidad Pública') ?></span>
                </span>
            </div>
        </div>

        <div class="hero-right">
            <span class="hero-amount-label">Monto Contratado</span>
            <div class="hero-amount-val"><?= formatMoney($montoContratado) ?></div>
            <div class="hero-status-pill">
                <?php
                $st = strtolower($ct['estado'] ?? 'en_proceso');
                $stLabel = strtoupper(str_replace('_', ' ', $st));
                ?>
                <span class="status-tag status-<?= $st ?>"><?= $stLabel ?></span>
            </div>
        </div>
    </div>

    <!-- Barra de 4 Tarjetas de Métricas Financieras -->
    <div class="contrato-kpi-grid">
        <!-- 1. Total Facturado -->
        <div class="kpi-box">
            <div class="kpi-top">
                <span class="kpi-label">Total Facturado</span>
                <span class="kpi-chip chip-blue"><?= $porcentajeFacturado ?>%</span>
            </div>
            <div class="kpi-value val-blue"><?= formatMoney($montoFacturado) ?></div>
            <div class="kpi-progress-bar">
                <div class="bar-fill fill-blue" style="width: <?= $porcentajeFacturado ?>%;"></div>
            </div>
            <div class="kpi-sub"><?= count($pagos) ?> comprobante(s) emitido(s)</div>
        </div>

        <!-- 2. Ingreso a Cuenta (Neto) -->
        <div class="kpi-box">
            <div class="kpi-top">
                <span class="kpi-label">Ingreso en Cuenta</span>
                <span class="kpi-chip chip-green"><i class="fas fa-check"></i> Neto</span>
            </div>
            <div class="kpi-value val-green"><?= formatMoney($montoIngreso) ?></div>
            <div class="kpi-progress-bar">
                <div class="bar-fill fill-green" style="width: <?= $porcentajeCobrado ?>%;"></div>
            </div>
            <div class="kpi-sub">Total cobrado en banco</div>
        </div>

        <!-- 3. Monto Retenido / Detracciones -->
        <div class="kpi-box">
            <div class="kpi-top">
                <span class="kpi-label">Monto Retenido</span>
                <span class="kpi-chip chip-amber"><i class="fas fa-percentage"></i> Retención</span>
            </div>
            <div class="kpi-value val-amber"><?= formatMoney($montoRetenido) ?></div>
            <div class="kpi-progress-bar">
                <div class="bar-fill fill-amber" style="width: <?= ($montoFacturado > 0 ? min(100, round(($montoRetenido / $montoFacturado) * 100)) : 0) ?>%;"></div>
            </div>
            <div class="kpi-sub">Detracciones y fiel cumplimiento</div>
        </div>

        <!-- 4. Saldo por Facturar -->
        <div class="kpi-box">
            <div class="kpi-top">
                <span class="kpi-label">Saldo por Facturar</span>
                <span class="kpi-chip chip-purple"><?= (100 - $porcentajeFacturado) ?>%</span>
            </div>
            <div class="kpi-value val-purple"><?= formatMoney($saldoPorFacturar) ?></div>
            <div class="kpi-progress-bar">
                <div class="bar-fill fill-purple" style="width: <?= (100 - $porcentajeFacturado) ?>%;"></div>
            </div>
            <div class="kpi-sub">Pendiente de devengar / cobrar</div>
        </div>
    </div>

    <!-- Barra de Progreso Financiero General -->
    <div class="progress-bar-card">
        <div class="progress-bar-header">
            <span>
                <i class="fas fa-chart-line" style="color: #2563eb; margin-right: 6px;"></i>
                <strong>Avance Financiero del Contrato:</strong> <?= $porcentajeFacturado ?>% Facturado
            </span>
            <span>
                Facturado: <strong><?= formatMoney($montoFacturado) ?></strong> de <strong><?= formatMoney($montoContratado) ?></strong>
            </span>
        </div>
        <div class="progress-track">
            <div class="track-fill" style="width: <?= $porcentajeFacturado ?>%;"></div>
        </div>
    </div>

    <!-- Ficha Técnica del Proyecto y Contrato (CON ICONOS VISIBLES Y ANIMADOS) -->
    <div class="dossier-card">
        <div class="dossier-header">
            <h3 class="dossier-title">
                <i class="fas fa-layer-group" style="color: #2563eb;"></i>
                Ficha Técnica & Información Contractual
            </h3>
            <span class="dossier-tag">
                <i class="fas fa-shield-alt"></i> Datos Registrados
            </span>
        </div>

        <!-- Objeto del Contrato -->
        <div class="objeto-box">
            <span class="objeto-tag"><i class="fas fa-align-left"></i> DESCRIPCIÓN / OBJETO CONTRACTUAL</span>
            <p class="objeto-text"><?= htmlspecialchars($ct['objeto'] ?? 'Sin descripción registrada.') ?></p>
        </div>

        <!-- Grid de Metadatos con Iconos Destacados -->
        <div class="dossier-grid">
            <!-- 1. Consultor / Contratista -->
            <div class="dossier-item">
                <div class="item-icon icon-blue">
                    <i class="fas fa-building"></i>
                </div>
                <div class="item-content">
                    <span class="item-label">Consultor / Contratista</span>
                    <strong class="item-val"><?= htmlspecialchars($ct['consultor'] ?? '-') ?></strong>
                </div>
            </div>

            <!-- 2. Empresa que Factura -->
            <div class="dossier-item">
                <div class="item-icon icon-emerald">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="item-content">
                    <span class="item-label">Empresa que Factura</span>
                    <strong class="item-val"><?= htmlspecialchars($ct['empresa_factura'] ?? $ct['consultor'] ?? '-') ?></strong>
                </div>
            </div>

            <!-- 3. Empresas Consorciadas / Socios -->
            <div class="dossier-item item-span-2">
                <div class="item-icon icon-indigo">
                    <i class="fas fa-users-gear"></i>
                </div>
                <div class="item-content">
                    <span class="item-label">Empresas Consorciadas / Socios</span>
                    <strong class="item-val"><?= htmlspecialchars($ct['empresas_asociadas'] ?? 'Consorcio único / Sin asociadas') ?></strong>
                </div>
            </div>

            <!-- 4. Representante Legal -->
            <div class="dossier-item">
                <div class="item-icon icon-violet">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="item-content">
                    <span class="item-label">Representante Legal</span>
                    <strong class="item-val"><?= htmlspecialchars($ct['representante'] ?? '-') ?></strong>
                </div>
            </div>

            <!-- 5. Responsable Asignado -->
            <div class="dossier-item">
                <div class="item-icon icon-amber">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="item-content">
                    <span class="item-label">Responsable Asignado</span>
                    <strong class="item-val"><?= htmlspecialchars($ct['responsable_texto'] ?? $ct['resp_nombre'] ?? '-') ?></strong>
                </div>
            </div>

            <!-- 6. Tipo de Servicio -->
            <div class="dossier-item">
                <div class="item-icon icon-teal">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="item-content">
                    <span class="item-label">Tipo de Servicio</span>
                    <span class="service-pill"><?= htmlspecialchars($ct['tipo_servicio'] ?? $ct['tipo_contrato'] ?? 'CONSULTORÍA') ?></span>
                </div>
            </div>

            <!-- 7. Clasificación / Especialidad -->
            <div class="dossier-item">
                <div class="item-icon icon-sky">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="item-content">
                    <span class="item-label">Clasificación / Especialidad</span>
                    <span class="class-pill"><?= htmlspecialchars($ct['clasificacion'] ?? 'GENERAL') ?></span>
                </div>
            </div>

            <!-- 8. Fecha de Suscripción / Firma -->
            <div class="dossier-item">
                <div class="item-icon icon-slate">
                    <i class="far fa-calendar-alt"></i>
                </div>
                <div class="item-content">
                    <span class="item-label">Fecha de Suscripción / Firma</span>
                    <strong class="item-val"><?= formatDate($ct['fecha_firma']) ?></strong>
                </div>
            </div>

            <!-- 9. Utilidad Proyectada si aplica -->
            <?php if (!empty($ct['monto_utilidad']) && $ct['monto_utilidad'] > 0): ?>
            <div class="dossier-item">
                <div class="item-icon icon-gold">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="item-content">
                    <span class="item-label">Utilidad Proyectada</span>
                    <strong class="item-val" style="color:#059669;"><?= formatMoney($ct['monto_utilidad']) ?></strong>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sección de Pagos, Facturas y Valorizaciones -->
    <div class="pagos-card">
        <div class="pagos-header">
            <div>
                <h3 class="pagos-title">
                    <i class="fas fa-receipt" style="color: #059669;"></i>
                    Desglose de Valorizaciones, Facturas y Retenciones
                </h3>
                <p class="pagos-subtitle">
                    Registro detallado de comprobantes, fechas de desembolso y deducciones aplicadas.
                </p>
            </div>
            <button type="button" class="btn-toggle-pago" onclick="toggleFormPago()">
                <i class="fas fa-plus"></i> Registrar Pago / Factura
            </button>
        </div>

        <!-- Formulario Desplegable para Registrar Pago -->
        <div id="form-nuevo-pago" class="form-pago-panel" style="display: none;">
            <div class="form-pago-header">
                <h4><i class="fas fa-plus-circle" style="color: #2563eb;"></i> Registrar Nueva Valorización / Factura</h4>
                <button type="button" class="btn-close-form" onclick="toggleFormPago()"><i class="fas fa-times"></i></button>
            </div>
            <form method="post" action="<?= BASE_URL ?>/contratos/agregar_pago/<?= $ct['id'] ?>">
                <div class="form-pago-grid">
                    <div class="f-group">
                        <label>N° DE PAGO / CONCEPTO *</label>
                        <input type="text" name="numero_pago" required placeholder="Ej: VALORIZACIÓN N° 01">
                    </div>
                    <div class="f-group">
                        <label>FECHA DE EMISIÓN</label>
                        <input type="date" name="fecha_emision">
                    </div>
                    <div class="f-group">
                        <label>N° DE FACTURA *</label>
                        <input type="text" name="nro_factura" required placeholder="Ej: E001-5">
                    </div>
                    <div class="f-group">
                        <label>MONTO FACTURADO (S/) *</label>
                        <input type="number" step="0.01" name="monto_facturado" required placeholder="0.00">
                    </div>
                    <div class="f-group">
                        <label>ESTADO DEL PAGO</label>
                        <select name="estado">
                            <option value="PAGADA">PAGADA</option>
                            <option value="PENDIENTE">PENDIENTE</option>
                        </select>
                    </div>
                    <div class="f-group">
                        <label>FECHA DE PAGO (ABONO)</label>
                        <input type="date" name="fecha_pago">
                    </div>
                    <div class="f-group">
                        <label>INGRESO A CUENTA (NETO S/)</label>
                        <input type="number" step="0.01" name="monto_ingreso_cuenta" placeholder="0.00">
                    </div>
                    <div class="f-group">
                        <label>RETENCIÓN / DETRACCIÓN (S/)</label>
                        <input type="number" step="0.01" name="monto_retenido" placeholder="0.00">
                    </div>
                </div>
                <div class="form-pago-actions">
                    <button type="submit" class="btn-submit-pago"><i class="fas fa-save"></i> Guardar Pago</button>
                    <button type="button" class="btn-cancel-pago" onclick="toggleFormPago()">Cancelar</button>
                </div>
            </form>
        </div>

        <!-- Tabla de Pagos -->
        <div class="pagos-table-wrapper">
            <table class="pagos-table">
                <thead>
                    <tr>
                        <th>N° Pago / Concepto</th>
                        <th>N° Factura</th>
                        <th>Fecha Emisión</th>
                        <th style="text-align: right;">Monto Facturado</th>
                        <th style="text-align: center;">Estado</th>
                        <th>Fecha Pago</th>
                        <th style="text-align: right;">Ingreso Cuenta (Neto)</th>
                        <th style="text-align: right;">Retención / Detracción</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pagos)): ?>
                    <tr>
                        <td colspan="9" class="empty-pagos-cell">
                            <div class="empty-pagos-state">
                                <div class="empty-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                <h4>No hay valorizaciones ni facturas registradas aún</h4>
                                <p>Este contrato se encuentra en fase inicial. Puedes comenzar registrando el primer pago o valorización.</p>
                                <button type="button" class="btn-toggle-pago" onclick="toggleFormPago()" style="margin-top: 10px;">
                                    <i class="fas fa-plus"></i> Registrar Primer Pago
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($pagos as $idx => $p): ?>
                    <tr>
                        <td class="col-concepto">
                            <span class="badge-idx"><?= $idx + 1 ?></span>
                            <strong><?= htmlspecialchars($p['numero_pago']) ?></strong>
                        </td>
                        <td>
                            <span class="invoice-pill">
                                <i class="fas fa-receipt" style="font-size: 10px;"></i> <?= htmlspecialchars($p['nro_factura'] ?? '-') ?>
                            </span>
                        </td>
                        <td class="col-date">
                            <?= !empty($p['fecha_emision']) ? formatDate($p['fecha_emision']) : '<span class="txt-muted">-</span>' ?>
                        </td>
                        <td class="col-amount col-billed">
                            <?= formatMoney($p['monto_facturado']) ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if (strtoupper($p['estado']) === 'PAGADA' || strtoupper($p['estado']) === 'PAGADO'): ?>
                                <span class="tag-status tag-pagado"><i class="fas fa-check"></i> PAGADO</span>
                            <?php else: ?>
                                <span class="tag-status tag-pendiente"><i class="fas fa-clock"></i> PENDIENTE</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-date">
                            <?= !empty($p['fecha_pago']) ? formatDate($p['fecha_pago']) : '<span class="txt-muted">-</span>' ?>
                        </td>
                        <td class="col-amount col-ingreso">
                            <?= floatval($p['monto_ingreso_cuenta']) > 0 ? formatMoney($p['monto_ingreso_cuenta']) : '<span class="txt-muted">-</span>' ?>
                        </td>
                        <td class="col-amount col-retenido">
                            <?= floatval($p['monto_retenido']) > 0 ? formatMoney($p['monto_retenido']) : '<span class="txt-muted">-</span>' ?>
                        </td>
                        <td style="text-align: center;">
                            <a href="<?= BASE_URL ?>/contratos/eliminar_pago/<?= $p['id'] ?>" 
                               onclick="return confirm('¿Seguro que deseas eliminar este registro de pago?')" 
                               class="btn-delete-pago" 
                               title="Eliminar este pago">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <!-- Fila de Resumen Totalizador -->
                    <tr class="row-totales">
                        <td colspan="3" class="total-label">
                            <i class="fas fa-calculator" style="margin-right: 6px; color: #2563eb;"></i> TOTALES REGISTRADOS
                        </td>
                        <td class="total-val col-billed"><?= formatMoney($montoFacturado) ?></td>
                        <td colspan="2"></td>
                        <td class="total-val col-ingreso"><?= formatMoney($montoIngreso) ?></td>
                        <td class="total-val col-retenido"><?= formatMoney($montoRetenido) ?></td>
                        <td></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* ==========================================================================
   ESTILOS PREMIUM - DOSSIER Y VALORIZACIONES DE CONTRATOS (TEMA CLARO)
   ========================================================================== */
.contrato-detail-wrapper {
    padding: 12px 18px 36px 18px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1e293b;
    max-width: 100%;
    box-sizing: border-box;
    animation: wrapperFadeIn 0.25s ease-out;
}
@keyframes wrapperFadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Hero Header (TEMA CLARO) */
.contrato-hero-card.light-theme {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #eff6ff 100%);
    border-radius: 18px;
    padding: 24px 28px;
    color: #0f172a;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
.contrato-hero-card.light-theme::after {
    content: '';
    position: absolute;
    right: -40px;
    top: -40px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.hero-left {
    max-width: 720px;
    z-index: 1;
}
.hero-badges {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}
.hero-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 11px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    transition: transform 0.15s ease;
}
.hero-badge:hover {
    transform: translateY(-1px);
}
.badge-year {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
.badge-propia {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.badge-externa {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
}
.badge-cui {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
}
.badge-sub {
    background: #eef2ff;
    color: #4f46e5;
    border: 1px solid #c7d2fe;
}
.hero-title {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 8px 0;
    line-height: 1.25;
    letter-spacing: -0.4px;
}
.hero-meta {
    font-size: 13px;
    color: #475569;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.meta-item strong {
    color: #0f172a;
}
.meta-sep {
    color: #cbd5e1;
}

.hero-right {
    text-align: right;
    z-index: 1;
    min-width: 200px;
}
.hero-amount-label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 2px;
}
.hero-amount-val {
    font-size: 28px;
    font-weight: 900;
    color: #059669;
    letter-spacing: -0.5px;
    line-height: 1.1;
    margin-bottom: 8px;
}
.status-tag {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.status-tag.status-prestamo {
    background: #f5f3ff;
    color: #6d28d9;
    border: 1px solid #ddd6fe;
}
.status-tag.status-en_ejecucion {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.status-tag.status-en_desarrollo {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.status-tag.status-en_proceso {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
}
.status-tag.status-apelacion_ejecucion_obra {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

/* 4 KPIs Grid */
.contrato-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 16px;
}
@media (max-width: 960px) {
    .contrato-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 520px) {
    .contrato-kpi-grid {
        grid-template-columns: 1fr;
    }
}

.kpi-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 18px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.kpi-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.05);
}
.kpi-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.kpi-label {
    font-size: 11.5px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.kpi-chip {
    font-size: 10.5px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
}
.chip-blue { background: #eff6ff; color: #2563eb; }
.chip-green { background: #ecfdf5; color: #059669; }
.chip-amber { background: #fffbeb; color: #d97706; }
.chip-purple { background: #f5f3ff; color: #7c3aed; }

.kpi-value {
    font-size: 20px;
    font-weight: 900;
    line-height: 1.2;
    margin-bottom: 8px;
    letter-spacing: -0.3px;
}
.val-blue { color: #1e40af; }
.val-green { color: #047857; }
.val-amber { color: #b45309; }
.val-purple { color: #6d28d9; }

.kpi-progress-bar {
    width: 100%;
    height: 5px;
    background: #f1f5f9;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 6px;
}
.bar-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.5s ease;
}
.fill-blue { background: #2563eb; }
.fill-green { background: #10b981; }
.fill-amber { background: #f59e0b; }
.fill-purple { background: #8b5cf6; }

.kpi-sub {
    font-size: 11.5px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Barra de progreso global */
.progress-bar-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 18px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.progress-bar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12.5px;
    color: #475569;
    margin-bottom: 8px;
}
.progress-bar-header strong {
    color: #0f172a;
}
.progress-track {
    width: 100%;
    height: 8px;
    background: #f1f5f9;
    border-radius: 999px;
    overflow: hidden;
}
.track-fill {
    height: 100%;
    background: linear-gradient(90deg, #2563eb 0%, #10b981 100%);
    border-radius: 999px;
    transition: width 0.6s ease;
}

/* Dossier Card (Ficha Técnica) */
.dossier-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 22px 24px;
    margin-bottom: 22px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}
.dossier-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 12px;
}
.dossier-title {
    font-size: 15.5px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dossier-tag {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 4px 10px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.objeto-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #2563eb;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 18px;
}
.objeto-tag {
    font-size: 10.5px;
    font-weight: 800;
    color: #2563eb;
    display: block;
    margin-bottom: 4px;
    letter-spacing: 0.4px;
}
.objeto-text {
    font-size: 13px;
    color: #334155;
    line-height: 1.5;
    margin: 0;
}

.dossier-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}
@media (max-width: 900px) {
    .dossier-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 580px) {
    .dossier-grid {
        grid-template-columns: 1fr;
    }
}
.item-span-2 {
    grid-column: span 2;
}
@media (max-width: 900px) {
    .item-span-2 {
        grid-column: span 1;
    }
}

.dossier-item {
    background: #ffffff;
    border: 1px solid #eef2f6;
    border-radius: 12px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.01);
}
.dossier-item:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
}

/* Iconos de colores con presencia visual */
.item-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}
.dossier-item:hover .item-icon {
    transform: scale(1.08);
}

.icon-blue {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
.icon-emerald {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.icon-indigo {
    background: #eef2ff;
    color: #4f46e5;
    border: 1px solid #c7d2fe;
}
.icon-violet {
    background: #f5f3ff;
    color: #7c3aed;
    border: 1px solid #ddd6fe;
}
.icon-amber {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
}
.icon-teal {
    background: #f0fdfa;
    color: #0d9488;
    border: 1px solid #99f6e4;
}
.icon-sky {
    background: #f0f9ff;
    color: #0284c7;
    border: 1px solid #bae6fd;
}
.icon-slate {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.icon-gold {
    background: #fefce8;
    color: #ca8a04;
    border: 1px solid #fef08a;
}

.item-content {
    overflow: hidden;
}
.item-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    display: block;
    margin-bottom: 2px;
    letter-spacing: 0.3px;
}
.item-val {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    display: block;
    white-space: normal;
    word-break: break-word;
}
.service-pill {
    background: #e0f2fe;
    color: #0369a1;
    font-size: 11.5px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 6px;
    display: inline-block;
    border: 1px solid #bae6fd;
}
.class-pill {
    background: #f1f5f9;
    color: #475569;
    font-size: 11.5px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 6px;
    display: inline-block;
    border: 1px solid #e2e8f0;
}

/* Pagos Card */
.pagos-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 22px 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}
.pagos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 18px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 14px;
}
.pagos-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pagos-subtitle {
    font-size: 12.5px;
    color: #64748b;
    margin: 3px 0 0 0;
}
.btn-toggle-pago {
    background: #2563eb;
    color: #ffffff;
    border: none;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
}
.btn-toggle-pago:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);
}

/* Formulario Nuevo Pago */
.form-pago-panel {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 22px;
    animation: fadeIn 0.25s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
.form-pago-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 10px;
}
.form-pago-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
}
.btn-close-form {
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 15px;
}
.btn-close-form:hover {
    color: #ef4444;
}

.form-pago-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}
.f-group label {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    display: block;
    margin-bottom: 4px;
}
.f-group input,
.f-group select {
    width: 100%;
    box-sizing: border-box;
    padding: 8px 11px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 12.5px;
    font-weight: 600;
    color: #1e293b;
    outline: none;
    background: #ffffff;
    transition: border 0.15s ease;
}
.f-group input:focus,
.f-group select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
.form-pago-actions {
    margin-top: 16px;
    display: flex;
    gap: 10px;
}
.btn-submit-pago {
    background: #059669;
    color: #ffffff;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-submit-pago:hover {
    background: #047857;
}
.btn-cancel-pago {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
}

/* Tabla de Pagos */
.pagos-table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.pagos-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.pagos-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 12px 14px;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}
.pagos-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12.5px;
    vertical-align: middle;
}
.pagos-table tbody tr:hover td {
    background: #f8fafc;
}

.col-concepto {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 190px;
}
.badge-idx {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}
.invoice-pill {
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    border: 1px solid #bae6fd;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.col-date {
    color: #64748b;
    white-space: nowrap;
    font-size: 12px;
}
.col-amount {
    font-weight: 700;
    white-space: nowrap;
}
.col-billed {
    color: #0f172a;
}
.col-ingreso {
    color: #059669;
}
.col-retenido {
    color: #d97706;
}
.txt-muted {
    color: #cbd5e1;
}

.tag-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
}
.tag-pagado {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.tag-pendiente {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.btn-delete-pago {
    color: #cbd5e1;
    font-size: 13px;
    transition: color 0.15s ease;
}
.btn-delete-pago:hover {
    color: #ef4444;
}

.row-totales {
    background: #f8fafc;
    border-top: 2px solid #cbd5e1;
    font-weight: 800;
}
.row-totales td {
    padding: 13px 14px !important;
    border-bottom: none !important;
}
.total-label {
    font-size: 12.5px;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.total-val {
    font-size: 13.5px;
    font-weight: 900;
    text-align: right;
}

/* Empty State */
.empty-pagos-cell {
    padding: 40px 20px !important;
    text-align: center;
}
.empty-pagos-state {
    max-width: 440px;
    margin: 0 auto;
}
.empty-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #f1f5f9;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin: 0 auto 12px auto;
}
.empty-pagos-state h4 {
    font-size: 15px;
    font-weight: 800;
    color: #334155;
    margin: 0 0 6px 0;
}
.empty-pagos-state p {
    font-size: 12.5px;
    color: #64748b;
    line-height: 1.45;
    margin: 0 0 12px 0;
}
</style>

<script>
function toggleFormPago() {
    const el = document.getElementById('form-nuevo-pago');
    if (!el) return;
    const isHidden = (el.style.display === 'none' || el.style.display === '');
    el.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
</script>
