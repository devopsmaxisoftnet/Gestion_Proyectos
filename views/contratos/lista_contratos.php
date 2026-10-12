<!-- =====================================================
     CONTRATOS Y VALORIZACIONES - EJERCICIO REAL
     ===================================================== -->
<?php
$contratos = $data['contratos'] ?? [];
$totales = $data['totales'] ?? [];
$filtros = $data['filtros'] ?? [];

$totalContratos = $totales['total_contratos'] ?? count($contratos);
$montoTotalContratado = floatval($totales['total_contratado'] ?? 0);
$totalFacturado = floatval($totales['total_facturado'] ?? 0);
$totalIngreso = floatval($totales['total_ingreso'] ?? 0);
$totalRetenido = floatval($totales['total_retenido'] ?? 0);

$currAnio = $filtros['anio'] ?? '';
$currGestion = $filtros['tipo_gestion'] ?? '';
?>

<div class="page-header" style="margin-bottom: 20px;">
    <div>
        <h2 style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
            Contratos y Valorizaciones
        </h2>
        <p style="color: #64748b; font-size: 13.5px; margin: 0;">
            Seguimiento de contratos públicos, empresas consorciadas, valorizaciones, facturación y retenciones.
        </p>
    </div>
    <div style="display:flex; align-items:center; gap: 12px; flex-wrap: wrap;">
        <div class="search-bar" style="margin:0;">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar por CUI, contrato, entidad o nombre corto..." id="search-contratos"
                   value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>"
                   onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/contratos?anio=<?= urlencode($currAnio) ?>&tipo_gestion=<?= urlencode($currGestion) ?>&busqueda='+encodeURIComponent(this.value)">
        </div>
        <button type="button" class="btn btn-primary" onclick="abrirGlobalModal('Nuevo Contrato', '<?= BASE_URL ?>/contratos/crear?modal_iframe=1', 'fas fa-file-contract', 'Registro y formalización contractual')">
            <i class="fas fa-plus"></i> Nuevo contrato
        </button>
    </div>
</div>

<!-- Barra de Filtros / Pestañas estilo Excel -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px; background: #ffffff; padding: 12px 18px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,0.02);">
    <!-- Selector de Ejercicio / Año -->
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
            <i class="far fa-calendar-alt" style="margin-right: 4px;"></i> Ejercicio:
        </span>
        <a href="<?= BASE_URL ?>/contratos?tipo_gestion=<?= urlencode($currGestion) ?>" 
           class="badge-filter <?= empty($currAnio) ? 'active' : '' ?>" 
           style="padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; <?= empty($currAnio) ? 'background:#0f172a; color:#fff;' : 'background:#f1f5f9; color:#475569;' ?>">
            Todos
        </a>
        <a href="<?= BASE_URL ?>/contratos?anio=2026&tipo_gestion=<?= urlencode($currGestion) ?>" 
           class="badge-filter <?= ($currAnio === '2026') ? 'active' : '' ?>" 
           style="padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; <?= ($currAnio === '2026') ? 'background:#2563eb; color:#fff;' : 'background:#f1f5f9; color:#475569;' ?>">
            2026
        </a>
        <a href="<?= BASE_URL ?>/contratos?anio=2025&tipo_gestion=<?= urlencode($currGestion) ?>" 
           class="badge-filter <?= ($currAnio === '2025') ? 'active' : '' ?>" 
           style="padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; <?= ($currAnio === '2025') ? 'background:#2563eb; color:#fff;' : 'background:#f1f5f9; color:#475569;' ?>">
            2025
        </a>
    </div>

    <!-- Segmentos de Tipo de Gestión -->
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
            <i class="fas fa-layer-group" style="margin-right: 4px;"></i> Tipo:
        </span>
        <a href="<?= BASE_URL ?>/contratos?anio=<?= urlencode($currAnio) ?>" 
           style="padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; text-decoration: none; <?= empty($currGestion) ? 'background:#0f172a; color:#fff;' : 'background:#f1f5f9; color:#475569;' ?>">
            Todos (<?= $totalContratos ?>)
        </a>
        <a href="<?= BASE_URL ?>/contratos?anio=<?= urlencode($currAnio) ?>&tipo_gestion=propia" 
           style="padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; <?= ($currGestion === 'propia') ? 'background:#059669; color:#fff;' : 'background:#f1f5f9; color:#475569;' ?>">
            <i class="fas fa-building"></i> Empresas Propias
        </a>
        <a href="<?= BASE_URL ?>/contratos?anio=<?= urlencode($currAnio) ?>&tipo_gestion=externa" 
           style="padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; <?= ($currGestion === 'externa') ? 'background:#d97706; color:#fff;' : 'background:#f1f5f9; color:#475569;' ?>">
            <i class="fas fa-handshake"></i> Empresas Externas
        </a>
    </div>
</div>

<!-- KPI Cards Financieros -->
<div class="dashboard-kpis" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 22px;">
    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 46px; height: 46px; border-radius: 12px; background: #eff6ff; color: #2563eb; display:flex; align-items:center; justify-content:center; font-size: 20px;">
            <i class="fas fa-file-signature"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Contratos</span>
            <div style="font-size: 22px; font-weight: 900; color: #0f172a; line-height: 1.2;"><?= count($contratos) ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 46px; height: 46px; border-radius: 12px; background: #ecfdf5; color: #059669; display:flex; align-items:center; justify-content:center; font-size: 20px;">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Monto Contratado</span>
            <div style="font-size: 18px; font-weight: 900; color: #059669; line-height: 1.2;"><?= formatMoney($montoTotalContratado) ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 46px; height: 46px; border-radius: 12px; background: #eff6ff; color: #2563eb; display:flex; align-items:center; justify-content:center; font-size: 20px;">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Facturado</span>
            <div style="font-size: 18px; font-weight: 900; color: #2563eb; line-height: 1.2;"><?= formatMoney($totalFacturado) ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 46px; height: 46px; border-radius: 12px; background: #fef3c7; color: #d97706; display:flex; align-items:center; justify-content:center; font-size: 20px;">
            <i class="fas fa-percentage"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Retenido</span>
            <div style="font-size: 18px; font-weight: 900; color: #d97706; line-height: 1.2;"><?= formatMoney($totalRetenido) ?></div>
        </div>
    </div>
</div>

<!-- Tabla de Contratos -->
<div class="data-card data-section full-width" style="border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; background: #ffffff; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
    <div style="overflow-x: auto;">
        <table class="data-table" id="tabla-contratos" style="width:100%; border-collapse: separate; border-spacing: 0;">
            <thead>
                <tr style="background: #f8fafc; font-size: 11.5px; text-transform: uppercase; color: #475569;">
                    <th style="padding: 14px 16px; border-bottom: 2px solid #e2e8f0;">N° Contrato / Año</th>
                    <th style="padding: 14px 16px; border-bottom: 2px solid #e2e8f0;">Proyecto / Nombre Corto</th>
                    <th style="padding: 14px 16px; border-bottom: 2px solid #e2e8f0;">Entidad Pública</th>
                    <th style="padding: 14px 16px; border-bottom: 2px solid #e2e8f0;">Consultor & Consorcio</th>
                    <th style="padding: 14px 16px; border-bottom: 2px solid #e2e8f0; text-align: right;">Monto Contratado</th>
                    <th style="padding: 14px 16px; border-bottom: 2px solid #e2e8f0; text-align: right;">Facturado / Pagos</th>
                    <th style="padding: 14px 16px; border-bottom: 2px solid #e2e8f0; text-align: center;">Gestión</th>
                    <th style="padding: 14px 16px; border-bottom: 2px solid #e2e8f0; text-align: center;">Estado</th>
                    <th style="padding: 14px 16px; border-bottom: 2px solid #e2e8f0; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contratos)): ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding: 40px; color: #94a3b8;">
                        <div class="empty-state">
                            <i class="fas fa-file-contract" style="font-size: 32px; margin-bottom: 10px; display:block;"></i>
                            <h3 style="font-size: 16px; font-weight: 700; color: #334155;">No se encontraron contratos</h3>
                            <p style="font-size: 13px;">No hay registros con los filtros seleccionados.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <tr id="no-search-results-row" style="display: none;">
                    <td colspan="9" style="text-align:center; padding: 40px; color: #94a3b8;">
                        <div class="empty-state">
                            <i class="fas fa-search" style="font-size: 32px; margin-bottom: 10px; color: #cbd5e1; display:block;"></i>
                            <h4 style="font-size: 15px; font-weight: 700; color: #334155; margin: 0 0 4px 0;">Sin coincidencias</h4>
                            <p style="font-size: 13px; color: #94a3b8; margin:0;">No se encontraron contratos que coincidan con el término buscado.</p>
                        </div>
                    </td>
                </tr>
                <?php foreach ($contratos as $ct): ?>
                <tr class="contrato-row" style="border-bottom: 1px solid #f1f5f9; font-size: 13px; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <!-- N° Contrato & Año -->
                    <td style="padding: 14px 16px;">
                        <div style="display:flex; align-items:center; gap: 6px; margin-bottom: 4px;">
                            <span style="background: <?= ($ct['anio'] == 2026) ? '#eff6ff' : '#f8fafc' ?>; color: <?= ($ct['anio'] == 2026) ? '#2563eb' : '#64748b' ?>; font-size: 10.5px; font-weight: 800; padding: 2px 6px; border-radius: 4px; border: 1px solid <?= ($ct['anio'] == 2026) ? '#bfdbfe' : '#e2e8f0' ?>;">
                                <?= $ct['anio'] ?>
                            </span>
                            <?php if (!empty($ct['cui'])): ?>
                                <span style="background: #f1f5f9; color: #475569; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px;" title="CUI">
                                    CUI <?= htmlspecialchars($ct['cui']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span style="font-weight: 800; color: #0f172a; font-size: 12.5px; display:block;">
                            <?= htmlspecialchars($ct['codigo']) ?>
                        </span>
                    </td>

                    <!-- Proyecto / Nombre Corto -->
                    <td style="padding: 14px 16px; max-width: 280px;">
                        <span style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 3px; font-size: 13px;">
                            <?= htmlspecialchars($ct['nombre_corto'] ?: mb_substr($ct['objeto'], 0, 36)) ?>
                        </span>
                        <span style="color: #64748b; font-size: 11.5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.35;" title="<?= htmlspecialchars($ct['objeto']) ?>">
                            <?= htmlspecialchars($ct['objeto']) ?>
                        </span>
                    </td>

                    <!-- Entidad Pública -->
                    <td style="padding: 14px 16px; max-width: 200px;">
                        <span style="font-weight: 600; color: #334155; font-size: 12.5px; display:flex; align-items:flex-start; gap: 6px;">
                            <i class="fas fa-landmark" style="color: #94a3b8; font-size: 12px; margin-top: 3px;"></i>
                            <span><?= htmlspecialchars($ct['cliente_nombre'] ?? '-') ?></span>
                        </span>
                    </td>

                    <!-- Consultor & Consorcio -->
                    <td style="padding: 14px 16px; max-width: 240px;">
                        <span style="font-weight: 700; color: #0f172a; font-size: 12px; display:block;">
                            <?= htmlspecialchars($ct['consultor'] ?? '-') ?>
                        </span>
                        <?php if (!empty($ct['empresas_asociadas'])): ?>
                            <span style="color: #64748b; font-size: 11px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="<?= htmlspecialchars($ct['empresas_asociadas']) ?>">
                                <i class="fas fa-users" style="color: #cbd5e1; margin-right: 2px;"></i> <?= htmlspecialchars($ct['empresas_asociadas']) ?>
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Monto Contratado -->
                    <td style="padding: 14px 16px; text-align: right; font-weight: 800; color: #059669; font-size: 13.5px; white-space: nowrap;">
                        <?= formatMoney($ct['monto_contrato']) ?>
                    </td>

                    <!-- Facturado / Pagos -->
                    <td style="padding: 14px 16px; text-align: right; white-space: nowrap;">
                        <span style="font-weight: 800; color: <?= floatval($ct['total_facturado']) > 0 ? '#2563eb' : '#94a3b8' ?>; font-size: 13px; display:block;">
                            <?= formatMoney($ct['total_facturado']) ?>
                        </span>
                        <?php if (intval($ct['cant_pagos']) > 0): ?>
                            <span style="background: #eff6ff; color: #1d4ed8; font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 2px;">
                                <i class="fas fa-receipt" style="margin-right: 3px;"></i> <?= $ct['cant_pagos'] ?> pago(s)
                            </span>
                        <?php else: ?>
                            <span style="font-size: 10.5px; color: #94a3b8;">Sin pagos</span>
                        <?php endif; ?>
                    </td>

                    <!-- Tipo de Gestión (Propia vs Externa) -->
                    <td style="padding: 14px 16px; text-align: center;">
                        <?php if ($ct['tipo_gestion'] === 'propia'): ?>
                            <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fas fa-building"></i> Propia
                            </span>
                        <?php else: ?>
                            <span style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fas fa-handshake"></i> Externa
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Estado -->
                    <td style="padding: 14px 16px; text-align: center;">
                        <?php
                        $st = strtolower($ct['estado'] ?? 'en_proceso');
                        if (in_array($st, ['en_ejecucion', 'en_desarrollo'])):
                        ?>
                            <span style="background: #ecfdf5; color: #065f46; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                En ejecución
                            </span>
                        <?php elseif ($st === 'apelacion_ejecucion_obra'): ?>
                            <span style="background: #fef2f2; color: #991b1b; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                Apelación
                            </span>
                        <?php elseif ($st === 'prestamo'): ?>
                            <span style="background: #f5f3ff; color: #6d28d9; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                Préstamo
                            </span>
                        <?php else: ?>
                            <span style="background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                En proceso
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Acciones -->
                    <td style="padding: 14px 16px; text-align: right; white-space: nowrap;">
                        <button type="button" 
                                onclick="abrirGlobalModal('Contrato <?= htmlspecialchars($ct['nombre_corto'] ?: $ct['codigo']) ?>', '<?= BASE_URL ?>/contratos/ver/<?= $ct['id'] ?>?modal_iframe=1', 'fas fa-file-contract', 'Dossier contractual, valorizaciones y facturas')" 
                                style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease;"
                                onmouseover="this.style.background='#2563eb'; this.style.color='#fff';"
                                onmouseout="this.style.background='#eff6ff'; this.style.color='#2563eb';">
                            <i class="fas fa-eye"></i> Ver y Pagos
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Barra de Paginación Animada -->
    <?php if (!empty($contratos)): ?>
    <div class="pagination-footer-wrapper">
        <div class="pagination-info-group">
            <div class="pagination-badge-info">
                <i class="fas fa-layer-group" style="color: #2563eb;"></i>
                <span>Mostrando <strong id="pag-range-text">1 - 10</strong> de <strong id="pag-total-text"><?= count($contratos) ?></strong> contratos</span>
            </div>
            <div class="pagination-select-wrapper">
                <span>Por página:</span>
                <select id="pag-tamano-select" onchange="cambiarTamanoPagina(this.value)">
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="all">Todos</option>
                </select>
            </div>
        </div>

        <div class="pagination-controls-nav" id="paginacion-controls">
            <!-- Botones generados dinámicamente -->
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* ========================================================
   ESTILOS DE PAGINACIÓN ANIMADA - CONTRATOS
   ======================================================== */
@keyframes rowFadeSlideIn {
    0% {
        opacity: 0;
        transform: translateY(12px) scale(0.995);
    }
    60% {
        opacity: 0.95;
        transform: translateY(-1px) scale(1.001);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.contrato-row.animating-row {
    animation: rowFadeSlideIn 0.30s cubic-bezier(0.16, 1, 0.3, 1) both;
    animation-delay: calc(var(--anim-delay, 0) * 0.025s);
}

.pagination-footer-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding: 16px 22px;
    background: #ffffff;
    border-top: 1px solid #edf2f7;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
}

.pagination-info-group {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

.pagination-badge-info {
    font-size: 13px;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #f8fafc;
    padding: 7px 14px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.pagination-badge-info strong {
    color: #0f172a;
    font-weight: 700;
}

.pagination-select-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    font-weight: 600;
    color: #64748b;
}

.pagination-select-wrapper select {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #1e293b;
    font-size: 12.5px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 8px;
    outline: none;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

.pagination-select-wrapper select:focus,
.pagination-select-wrapper select:hover {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.pagination-controls-nav {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.pag-ctrl-btn {
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    user-select: none;
}

.pag-ctrl-btn:hover:not(:disabled):not(.active) {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.06);
}

.pag-ctrl-btn.active {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border-color: #2563eb;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
    transform: scale(1.05);
    position: relative;
}

.pag-ctrl-btn.active::after {
    content: '';
    position: absolute;
    bottom: 3px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #ffffff;
}

.pag-ctrl-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #94a3b8;
    transform: none !important;
    box-shadow: none !important;
}

.pag-ctrl-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 36px;
    color: #94a3b8;
    font-weight: 700;
}
</style>

<script>
(function() {
    let currentPage = 1;
    let pageSize = 10;
    const table = document.getElementById('tabla-contratos');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr.contrato-row'));
    const noResultsRow = document.getElementById('no-search-results-row');
    if (!allRows.length) return;

    // Leer parámetro ?pagina= de la URL si existe
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = parseInt(urlParams.get('pagina'), 10);
    if (!isNaN(initialPage) && initialPage > 0) {
        currentPage = initialPage;
    }

    let filteredRows = [...allRows];

    function renderPagination() {
        const totalItems = filteredRows.length;
        const totalPages = pageSize === 'all' ? 1 : Math.max(1, Math.ceil(totalItems / pageSize));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = pageSize === 'all' ? 0 : (currentPage - 1) * pageSize;
        const endIndex = pageSize === 'all' ? totalItems : Math.min(startIndex + pageSize, totalItems);

        // Ocultar todas las filas
        allRows.forEach(row => {
            row.style.display = 'none';
            row.classList.remove('animating-row');
        });

        if (totalItems === 0) {
            if (noResultsRow) noResultsRow.style.display = '';
        } else {
            if (noResultsRow) noResultsRow.style.display = 'none';

            // Mostrar y animar las filas de esta página
            let animCounter = 0;
            for (let i = startIndex; i < endIndex; i++) {
                const row = filteredRows[i];
                if (row) {
                    row.style.display = '';
                    row.style.setProperty('--anim-delay', animCounter);
                    row.classList.add('animating-row');
                    animCounter++;
                }
            }
        }

        // Actualizar textos de rango e info
        const rangeEl = document.getElementById('pag-range-text');
        const totalEl = document.getElementById('pag-total-text');
        if (rangeEl && totalEl) {
            if (totalItems === 0) {
                rangeEl.textContent = '0 - 0';
            } else {
                rangeEl.textContent = `${startIndex + 1} - ${endIndex}`;
            }
            totalEl.textContent = totalItems;
        }

        // Renderizar controles de botones
        const controlsContainer = document.getElementById('paginacion-controls');
        if (!controlsContainer) return;

        if (totalItems <= pageSize && totalPages <= 1 && pageSize !== 'all') {
            // Solo 1 página pero aún mostramos los controles limpios o simplificados
        }

        let html = '';

        // Botón Primero (si hay más de 3 páginas)
        if (totalPages > 3) {
            const firstDisabled = currentPage <= 1 ? 'disabled' : '';
            html += `<button type="button" class="pag-ctrl-btn" ${firstDisabled} onclick="contratoIrPagina(1)" title="Primera página">
                        <i class="fas fa-angle-double-left" style="font-size: 11px;"></i>
                     </button>`;
        }

        // Botón Anterior
        const prevDisabled = currentPage <= 1 ? 'disabled' : '';
        html += `<button type="button" class="pag-ctrl-btn" ${prevDisabled} onclick="contratoIrPagina(${currentPage - 1})" title="Página anterior">
                    <i class="fas fa-chevron-left" style="font-size: 11px;"></i>
                    <span style="font-size: 12px; margin-left: 2px;">Ant.</span>
                 </button>`;

        // Números de Página
        const maxButtons = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);

        if (endPage - startPage + 1 < maxButtons) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }

        if (startPage > 1) {
            html += `<button type="button" class="pag-ctrl-btn" onclick="contratoIrPagina(1)">1</button>`;
            if (startPage > 2) {
                html += `<span class="pag-ctrl-ellipsis">&hellip;</span>`;
            }
        }

        for (let p = startPage; p <= endPage; p++) {
            const activeClass = p === currentPage ? 'active' : '';
            html += `<button type="button" class="pag-ctrl-btn ${activeClass}" onclick="contratoIrPagina(${p})">${p}</button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<span class="pag-ctrl-ellipsis">&hellip;</span>`;
            }
            html += `<button type="button" class="pag-ctrl-btn" onclick="contratoIrPagina(${totalPages})">${totalPages}</button>`;
        }

        // Botón Siguiente
        const nextDisabled = currentPage >= totalPages ? 'disabled' : '';
        html += `<button type="button" class="pag-ctrl-btn" ${nextDisabled} onclick="contratoIrPagina(${currentPage + 1})" title="Página siguiente">
                    <span style="font-size: 12px; margin-right: 2px;">Sig.</span>
                    <i class="fas fa-chevron-right" style="font-size: 11px;"></i>
                 </button>`;

        // Botón Último (si hay más de 3 páginas)
        if (totalPages > 3) {
            const lastDisabled = currentPage >= totalPages ? 'disabled' : '';
            html += `<button type="button" class="pag-ctrl-btn" ${lastDisabled} onclick="contratoIrPagina(${totalPages})" title="Última página">
                        <i class="fas fa-angle-double-right" style="font-size: 11px;"></i>
                     </button>`;
        }

        controlsContainer.innerHTML = html;
    }

    window.contratoIrPagina = function(page) {
        currentPage = page;
        renderPagination();

        // Actualizar URL con query param 'pagina' sin recargar la pantalla
        try {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('pagina', page);
            window.history.replaceState({}, '', currentUrl.toString());
        } catch(e) {}

        // Scroll suave al inicio de la tabla si el usuario está más abajo
        const tableCard = table.closest('.data-card');
        if (tableCard) {
            const rect = tableCard.getBoundingClientRect();
            if (rect.top < 0) {
                tableCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    };

    window.cambiarTamanoPagina = function(newSize) {
        pageSize = newSize === 'all' ? 'all' : parseInt(newSize, 10);
        currentPage = 1;
        renderPagination();
    };

    // Live search instantáneo integrado con la paginación
    const searchInput = document.getElementById('search-contratos');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.trim().toLowerCase();
            if (!term) {
                filteredRows = [...allRows];
            } else {
                filteredRows = allRows.filter(row => {
                    const text = row.innerText.toLowerCase();
                    return text.includes(term);
                });
            }
            currentPage = 1;
            renderPagination();
        });
    }

    // Inicializar al cargar
    renderPagination();
})();
</script>
