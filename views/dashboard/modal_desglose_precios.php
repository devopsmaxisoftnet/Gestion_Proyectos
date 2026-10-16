<?php
/**
 * Modal: Desglose Detallado de Precios y Oportunidades por Línea de Negocio
 * Permite auditar y visualizar cada proyecto y su precio unitario que compone la suma total.
 */
?>
<!-- Modal Desglose de Precios -->
<div id="modalDesglosePrecios" class="desglose-modal-overlay" style="display:none;" onclick="if(event.target===this) cerrarModalDesglose();">
    <div class="desglose-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="desgloseModalTitle">
        
        <!-- Header del Modal -->
        <div class="desglose-modal-header" id="desgloseModalHeader">
            <div class="desglose-header-left">
                <div class="desglose-header-icon" id="desgloseIconBox">
                    <i id="desgloseIcon" class="fas fa-calculator"></i>
                </div>
                <div>
                    <div class="desglose-header-tag" id="desgloseHeaderTag">
                        <i class="fas fa-layer-group"></i> <span id="desgloseTagText">Cartera Comercial</span>
                    </div>
                    <h3 class="desglose-modal-title" id="desgloseModalTitle">Desglose de Precios por Oportunidad</h3>
                    <p class="desglose-modal-subtitle" id="desgloseModalSubtitle">
                        Auditoría de los precios unitarios que componen el valor estimado de la cartera.
                    </p>
                </div>
            </div>
            <button type="button" class="desglose-modal-close" onclick="cerrarModalDesglose()" title="Cerrar modal (Esc)">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Barra de Pestañas / Filtro Rápido por Línea -->
        <div class="desglose-tabs-bar" id="desgloseTabsBar">
            <button type="button" class="desglose-tab-btn active" data-linea-id="todas" onclick="cambiarTabDesglose('todas')">
                <i class="fas fa-globe"></i> Todas las líneas
                <span class="desglose-tab-badge" id="tabBadgeTodas">0</span>
            </button>
            <?php foreach (($data['cartera_lineas'] ?? []) as $cl): ?>
            <button type="button" class="desglose-tab-btn" data-linea-id="<?= (int)$cl['id'] ?>" onclick="cambiarTabDesglose(<?= (int)$cl['id'] ?>)">
                <i class="fas fa-<?= htmlspecialchars($cl['icono'] ?: 'briefcase') ?>" style="color: <?= htmlspecialchars($cl['color']) ?>"></i>
                <?= htmlspecialchars($cl['nombre']) ?>
                <span class="desglose-tab-badge" id="tabBadgeLinea_<?= (int)$cl['id'] ?>"><?= (int)$cl['num_oportunidades'] ?></span>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Métricas Resumen de la Suma Actual -->
        <div class="desglose-metrics-grid">
            <div class="desglose-metric-card metric-primary">
                <div class="desglose-metric-label">
                    <i class="fas fa-coins"></i> SUMA TOTAL EXACTA
                </div>
                <div class="desglose-metric-value" id="desgloseTotalSuma">S/ 0.00</div>
                <div class="desglose-metric-sub" id="desgloseTotalSub">Suma calculada de oportunidades activas</div>
            </div>

            <div class="desglose-metric-card">
                <div class="desglose-metric-label">
                    <i class="fas fa-clipboard-list"></i> PROYECTOS / CONTRATOS
                </div>
                <div class="desglose-metric-value" id="desgloseTotalCount">0</div>
                <div class="desglose-metric-sub">Oportunidades que suman este valor</div>
            </div>

            <div class="desglose-metric-card">
                <div class="desglose-metric-label">
                    <i class="fas fa-chart-line"></i> PRECIO PROMEDIO
                </div>
                <div class="desglose-metric-value" id="desglosePromedio">S/ 0.00</div>
                <div class="desglose-metric-sub">Por oportunidad en esta línea</div>
            </div>

            <div class="desglose-metric-card">
                <div class="desglose-metric-label">
                    <i class="fas fa-trophy"></i> MAYOR PROYECTO
                </div>
                <div class="desglose-metric-value" id="desgloseMayorMonto">S/ 0.00</div>
                <div class="desglose-metric-sub" id="desgloseMayorNombre">-</div>
            </div>
        </div>

        <!-- Barra de Búsqueda y Filtros en Tiempo Real -->
        <div class="desglose-search-bar">
            <div class="desglose-search-input-wrap">
                <i class="fas fa-search desglose-search-icon"></i>
                <input type="text" id="desgloseSearchInput" class="desglose-search-input" 
                       placeholder="Buscar por código (ej: OP-2025), nombre de proyecto o cliente..." 
                       oninput="filtrarDesgloseEnVivo(this.value)">
                <button type="button" id="desgloseClearSearch" class="desglose-clear-search" style="display:none;" onclick="limpiarBusquedaDesglose()">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
            <div class="desglose-search-status" id="desgloseStatusText">
                Mostrando 0 registros
            </div>
        </div>

        <!-- Tabla Detallada con Scroll -->
        <div class="desglose-table-wrapper">
            <table class="desglose-table" id="tablaDesglosePrecios">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">#</th>
                        <th style="width: 120px;">Código</th>
                        <th>Proyecto / Oportunidad</th>
                        <th style="width: 150px;">Línea</th>
                        <th style="width: 110px;">Estado</th>
                        <th style="width: 100px; text-align: center;">% de la Suma</th>
                        <th style="width: 160px; text-align: right;">Precio Estimado (S/)</th>
                    </tr>
                </thead>
                <tbody id="desgloseTableBody">
                    <!-- Filas generadas dinámicamente con JavaScript -->
                </tbody>
            </table>

            <!-- Estado Vacío -->
            <div id="desgloseEmptyState" class="desglose-empty-state" style="display:none;">
                <div class="desglose-empty-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <h4>No se encontraron proyectos</h4>
                <p id="desgloseEmptyMsg">Esta línea de negocio no cuenta con oportunidades activas registradas en este momento.</p>
            </div>
        </div>

        <!-- Footer del Modal con Total Matemático y Acciones -->
        <div class="desglose-modal-footer">
            <div class="desglose-footer-left">
                <i class="fas fa-check-circle" style="color: #10b981; margin-right: 6px;"></i>
                <span>Suma matemática auditada. Excluye descartadas y cerradas.</span>
            </div>
            <div class="desglose-footer-right">
                <button type="button" class="btn-desglose-copy" onclick="copiarDesgloseExcel()" title="Copiar tabla para pegar en Excel">
                    <i class="fas fa-file-excel"></i> Copiar para Excel
                </button>
                <button type="button" class="btn-desglose-close-footer" onclick="cerrarModalDesglose()">
                    Cerrar
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Toast flotante para confirmación de copiado -->
<div id="desgloseToast" class="desglose-toast">
    <i class="fas fa-check-circle"></i>
    <span id="desgloseToastMsg">¡Desglose copiado al portapapeles! Listo para pegar en Excel.</span>
</div>

<style>
/* ═════════════════════════════════════════════════════════════════════
   ESTILOS PREMIUM PARA EL MODAL DE DESGLOSE DE PRECIOS
   ═════════════════════════════════════════════════════════════════════ */

/* Botón único inferior en la tarjeta de línea de negocio */
.linea-card-footer-action {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px dashed var(--gray-100, #e2e8f0);
}

.btn-linea-ver-todos {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 8px 14px;
    border-radius: 9px;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-linea-ver-todos i {
    color: #0284c7;
    font-size: 12px;
    transition: transform 0.2s ease;
}

.btn-linea-ver-todos:hover {
    background: #0284c7;
    border-color: #0284c7;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
    transform: translateY(-1px);
}

.btn-linea-ver-todos:hover i {
    color: #ffffff;
    transform: scale(1.15);
}

/* Botón en el Hero Banner */
.hero-btn-desglose {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    transition: all 0.2s ease;
}

.hero-btn-desglose:hover {
    background: #ffffff;
    color: #1e1b4b;
    border-color: #ffffff;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
    transform: translateY(-1px);
}

/* ── MODAL OVERLAY & CONTAINER ── */
.desglose-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.72);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
    animation: desgloseOverlayFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.desglose-modal-dialog {
    background: #ffffff;
    width: 100%;
    max-width: 1040px;
    height: 82vh;
    max-height: 750px;
    min-height: 560px;
    border-radius: 20px;
    box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(226, 232, 240, 0.8);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: desgloseModalPopIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes desgloseOverlayFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes desgloseModalPopIn {
    from {
        opacity: 0;
        transform: scale(0.94) translateY(12px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Header del modal */
.desglose-modal-header {
    padding: 20px 26px 16px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.desglose-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.desglose-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: #ede9fe;
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.25);
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.desglose-header-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
    margin-bottom: 2px;
}

.desglose-modal-title {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.desglose-modal-subtitle {
    margin: 2px 0 0;
    font-size: 12.5px;
    color: #64748b;
}

.desglose-modal-close {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 15px;
    transition: all 0.2s ease;
}

.desglose-modal-close:hover {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fca5a5;
    transform: rotate(90deg);
}

/* Barra de Pestañas */
.desglose-tabs-bar {
    display: flex;
    gap: 8px;
    padding: 12px 24px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    overflow-x: auto;
    scrollbar-width: none;
}

.desglose-tabs-bar::-webkit-scrollbar {
    display: none;
}

.desglose-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    border-radius: 999px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.desglose-tab-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.desglose-tab-btn.active {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
}

.desglose-tab-btn.active i {
    color: #38bdf8 !important;
}

.desglose-tab-badge {
    background: rgba(0, 0, 0, 0.08);
    color: inherit;
    font-size: 11px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 999px;
}

.desglose-tab-btn.active .desglose-tab-badge {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
}

/* Grid de métricas resumen */
.desglose-metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    padding: 16px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.desglose-metric-card {
    background: #ffffff;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 84px;
    box-sizing: border-box;
    min-width: 0;
}

.desglose-metric-card.metric-primary {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-color: #bfdbfe;
}

.desglose-metric-label {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.metric-primary .desglose-metric-label {
    color: #1d4ed8;
}

.desglose-metric-value {
    font-size: 15.5px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: -0.01em;
    font-variant-numeric: tabular-nums;
}

.metric-primary .desglose-metric-value {
    color: #1e40af;
    font-size: 16.5px;
}

.desglose-metric-sub {
    font-size: 10.5px;
    color: #94a3b8;
    margin-top: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.25;
}

.metric-primary .desglose-metric-sub {
    color: #3b82f6;
    font-weight: 600;
}

/* Barra de Búsqueda */
.desglose-search-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 24px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
}

.desglose-search-input-wrap {
    position: relative;
    flex: 1;
}

.desglose-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
    pointer-events: none;
}

.desglose-search-input {
    width: 100%;
    padding: 9px 34px 9px 34px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 12.5px;
    outline: none;
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.desglose-search-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.desglose-clear-search {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 14px;
    padding: 0;
}

.desglose-clear-search:hover {
    color: #475569;
}

.desglose-search-status {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
}

/* Tabla con scroll */
.desglose-table-wrapper {
    flex: 1;
    overflow-y: auto;
    background: #ffffff;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.desglose-table-wrapper::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.desglose-table-wrapper::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}
.desglose-table-wrapper::-webkit-scrollbar-track {
    background: #f8fafc;
}

.desglose-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    text-align: left;
}

.desglose-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 10px 14px;
    border-bottom: 2px solid #e2e8f0;
}

.desglose-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s ease;
}

.desglose-table tbody tr:hover {
    background: #f8fafc;
}

.desglose-table tbody td {
    padding: 10px 14px;
    color: #334155;
    vertical-align: middle;
}

.desglose-row-index {
    text-align: center;
    font-weight: 700;
    color: #94a3b8;
    font-size: 11px;
}

.desglose-code-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 700;
    font-size: 11px;
    font-family: monospace;
    border: 1px solid #e2e8f0;
}

.desglose-item-title {
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
}

.desglose-item-client {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.desglose-linea-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}

.desglose-status-pill {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    text-align: center;
}

.status-adjudicada {
    background: #dcfce7;
    color: #15803d;
}
.status-propuesta {
    background: #dbeafe;
    color: #1d4ed8;
}
.status-evaluacion {
    background: #fef3c7;
    color: #b45309;
}
.status-detectada {
    background: #f3e8ff;
    color: #7e22ce;
}
.status-otro {
    background: #f1f5f9;
    color: #475569;
}

.desglose-pct-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}

.desglose-pct-text {
    font-size: 10.5px;
    font-weight: 700;
    color: #475569;
}

.desglose-pct-bar {
    width: 60px;
    height: 5px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
}

.desglose-pct-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #06b6d4);
    border-radius: 999px;
}

.desglose-price-cell {
    text-align: right;
    font-weight: 800;
    font-size: 13.5px;
    color: #0f172a;
    font-variant-numeric: tabular-nums;
}

/* Estado vacío */
.desglose-empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 24px;
    text-align: center;
    color: #64748b;
    margin: auto 0;
}

.desglose-empty-icon {
    font-size: 40px;
    color: #cbd5e1;
    margin-bottom: 12px;
}

.desglose-empty-state h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #334155;
}

.desglose-empty-state p {
    margin: 6px auto 0;
    font-size: 13px;
    max-width: 400px;
    color: #94a3b8;
}

/* Footer del modal */
.desglose-modal-footer {
    padding: 14px 24px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.desglose-footer-left {
    display: flex;
    align-items: center;
    font-size: 11.5px;
    color: #64748b;
    font-weight: 500;
}

.desglose-footer-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-desglose-copy {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #10b981;
    color: #ffffff;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
    transition: all 0.2s ease;
}

.btn-desglose-copy:hover {
    background: #059669;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    transform: translateY(-1px);
}

.btn-desglose-close-footer {
    background: #ffffff;
    color: #475569;
    border: 1px solid #cbd5e1;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-desglose-close-footer:hover {
    background: #f1f5f9;
    color: #0f172a;
}

/* Toast de notificación */
.desglose-toast {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #0f172a;
    color: #ffffff;
    padding: 12px 22px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    z-index: 100000;
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    pointer-events: none;
}

.desglose-toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

.desglose-toast i {
    color: #10b981;
    font-size: 16px;
}

/* Responsividad para móviles */
@media (max-width: 768px) {
    .desglose-metrics-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .desglose-modal-dialog {
        max-height: 96vh;
        border-radius: 14px;
    }
    .desglose-footer-left {
        display: none;
    }
}
</style>

<script>
/**
 * Lógica Javascript del Modal de Desglose de Precios
 */
(function() {
    // Dataset inyectado desde PHP con todas las oportunidades activas
    const DESGLOSE_DATA = <?= json_encode($data['desglose_oportunidades'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const CARTERA_LINEAS = <?= json_encode($data['cartera_lineas'] ?? [], JSON_UNESCAPED_UNICODE) ?>;

    let lineaActualFiltro = 'todas';
    let terminoBusquedaActual = '';

    // Mapa de colores e iconos por línea
    const LINEA_INFO = {
        '1': { nombre: 'Negocios propios', color: '#6C5CE7', icono: 'building' },
        '2': { nombre: 'Consultoría', color: '#00B894', icono: 'clipboard-check' },
        '3': { nombre: 'Ejecución de obra', color: '#E17055', icono: 'hard-hat' },
        'todas': { nombre: 'Todas las líneas de negocio', color: '#6366f1', icono: 'calculator' }
    };

    CARTERA_LINEAS.forEach(cl => {
        LINEA_INFO[cl.id] = {
            nombre: cl.nombre,
            color: cl.color || '#6366f1',
            icono: cl.icono || 'briefcase'
        };
    });

    /**
     * Formatear número a Soles con coma de miles
     */
    function formatMoneyJs(val) {
        val = Number(val) || 0;
        return 'S/ ' + val.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * Obtener clase CSS para el badge de estado
     */
    function getEstadoBadgeClass(estado) {
        estado = (estado || '').toLowerCase();
        if (estado.includes('adjudicada')) return 'status-adjudicada';
        if (estado.includes('propuesta')) return 'status-propuesta';
        if (estado.includes('evaluacion')) return 'status-evaluacion';
        if (estado.includes('detectada')) return 'status-detectada';
        return 'status-otro';
    }

    function getEstadoLabelJs(estado) {
        estado = (estado || '').toLowerCase();
        const labels = {
            'adjudicada': 'Adjudicada',
            'en_propuesta': 'En propuesta',
            'en_evaluacion': 'En evaluación',
            'detectada': 'Detectada',
            'participar': 'Participar',
            'no_adjudicada': 'No adjudicada',
            'cerrada': 'Cerrada'
        };
        return labels[estado] || (estado.charAt(0).toUpperCase() + estado.slice(1));
    }

    /**
     * Abrir el modal de desglose
     */
    window.abrirModalDesglose = function(lineaId, nombre, color, icono) {
        const modal = document.getElementById('modalDesglosePrecios');
        if (!modal) return;

        lineaId = lineaId || 'todas';
        lineaActualFiltro = lineaId;
        terminoBusquedaActual = '';

        const inputSearch = document.getElementById('desgloseSearchInput');
        if (inputSearch) inputSearch.value = '';
        const btnClear = document.getElementById('desgloseClearSearch');
        if (btnClear) btnClear.style.display = 'none';

        // Actualizar pestañas activas
        actualizarTabsUI(lineaId);

        // Renderizar tabla y métricas
        renderizarDesglose();

        // Mostrar modal con animación
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Escuchar tecla ESC
        document.addEventListener('keydown', handleEscKey);
    };

    /**
     * Cerrar el modal
     */
    window.cerrarModalDesglose = function() {
        const modal = document.getElementById('modalDesglosePrecios');
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
        document.removeEventListener('keydown', handleEscKey);
    };

    function handleEscKey(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            cerrarModalDesglose();
        }
    }

    /**
     * Cambiar de pestaña dentro del modal
     */
    window.cambiarTabDesglose = function(lineaId) {
        lineaActualFiltro = lineaId;
        actualizarTabsUI(lineaId);
        renderizarDesglose();
    };

    function actualizarTabsUI(lineaId) {
        document.querySelectorAll('.desglose-tab-btn').forEach(btn => {
            const btnLinea = btn.getAttribute('data-linea-id');
            if (String(btnLinea) === String(lineaId)) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    /**
     * Filtrar en tiempo real por búsqueda
     */
    window.filtrarDesgloseEnVivo = function(term) {
        terminoBusquedaActual = (term || '').trim().toLowerCase();
        const btnClear = document.getElementById('desgloseClearSearch');
        if (btnClear) {
            btnClear.style.display = terminoBusquedaActual.length > 0 ? 'block' : 'none';
        }
        renderizarDesglose();
    };

    window.limpiarBusquedaDesglose = function() {
        const input = document.getElementById('desgloseSearchInput');
        if (input) input.value = '';
        filtrarDesgloseEnVivo('');
        if (input) input.focus();
    };

    /**
     * Renderizar datos, métricas y tabla
     */
    function renderizarDesglose() {
        // Filtrar por línea
        let filtrados = DESGLOSE_DATA.slice();
        if (lineaActualFiltro !== 'todas') {
            filtrados = filtrados.filter(item => String(item.linea_negocio_id) === String(lineaActualFiltro));
        }

        // Filtrar por texto de búsqueda si existe
        if (terminoBusquedaActual) {
            filtrados = filtrados.filter(item => {
                const cod = (item.codigo || '').toLowerCase();
                const nom = (item.nombre || '').toLowerCase();
                const cli = (item.cliente_nombre || '').toLowerCase();
                const est = (item.estado || '').toLowerCase();
                return cod.includes(terminoBusquedaActual) ||
                       nom.includes(terminoBusquedaActual) ||
                       cli.includes(terminoBusquedaActual) ||
                       est.includes(terminoBusquedaActual);
            });
        }

        // Info de la línea seleccionada
        const info = LINEA_INFO[lineaActualFiltro] || LINEA_INFO['todas'];

        // Actualizar Header
        const iconBox = document.getElementById('desgloseIconBox');
        const iconEl = document.getElementById('desgloseIcon');
        const titleEl = document.getElementById('desgloseModalTitle');
        const subEl = document.getElementById('desgloseModalSubtitle');
        const tagText = document.getElementById('desgloseTagText');

        if (iconBox && iconEl) {
            iconBox.style.background = info.color + '22';
            iconBox.style.color = info.color;
            iconBox.style.boxShadow = `0 4px 14px ${info.color}35`;
            iconEl.className = 'fas fa-' + (info.icono || 'briefcase');
        }

        if (titleEl) {
            if (lineaActualFiltro === 'todas') {
                titleEl.textContent = 'Desglose de Precios · Portafolio General';
            } else {
                titleEl.textContent = 'Desglose de Precios · ' + info.nombre;
            }
        }

        if (tagText) {
            tagText.textContent = lineaActualFiltro === 'todas' ? 'Portafolio Completo' : info.nombre;
        }

        // Calcular Métricas
        let sumaTotal = 0;
        let mayorMonto = 0;
        let mayorProyecto = '-';

        filtrados.forEach(item => {
            const m = parseFloat(item.monto_estimado) || 0;
            sumaTotal += m;
            if (m > mayorMonto) {
                mayorMonto = m;
                mayorProyecto = item.nombre;
            }
        });

        const count = filtrados.length;
        const promedio = count > 0 ? (sumaTotal / count) : 0;

        if (subEl) {
            subEl.textContent = `Auditoría de los ${count} proyectos que componen la suma estimada de ${formatMoneyJs(sumaTotal)}.`;
        }

        // Asignar Métricas
        const elTotal = document.getElementById('desgloseTotalSuma');
        const elCount = document.getElementById('desgloseTotalCount');
        const elProm = document.getElementById('desglosePromedio');
        const elMayor = document.getElementById('desgloseMayorMonto');
        const elMayorNom = document.getElementById('desgloseMayorNombre');

        if (elTotal) elTotal.textContent = formatMoneyJs(sumaTotal);
        if (elCount) elCount.textContent = count + (count === 1 ? ' oportunidad' : ' oportunidades');
        if (elProm) elProm.textContent = formatMoneyJs(promedio);
        if (elMayor) elMayor.textContent = formatMoneyJs(mayorMonto);
        if (elMayorNom) {
            elMayorNom.textContent = mayorProyecto;
            elMayorNom.title = mayorProyecto;
        }

        // Actualizar badges de conteo en pestañas
        const totalGeneral = DESGLOSE_DATA.length;
        const tabTodasBadge = document.getElementById('tabBadgeTodas');
        if (tabTodasBadge) tabTodasBadge.textContent = totalGeneral;

        // Status texto
        const statusEl = document.getElementById('desgloseStatusText');
        if (statusEl) {
            statusEl.textContent = `Mostrando ${count} ${count === 1 ? 'proyecto' : 'proyectos'}`;
        }

        // Renderizar Filas de la Tabla
        const tbody = document.getElementById('desgloseTableBody');
        const emptyState = document.getElementById('desgloseEmptyState');
        const emptyMsg = document.getElementById('desgloseEmptyMsg');
        const table = document.getElementById('tablaDesglosePrecios');

        if (!tbody) return;
        tbody.innerHTML = '';

        if (count === 0) {
            if (table) table.style.display = 'none';
            if (emptyState) emptyState.style.display = 'block';
            if (emptyMsg) {
                if (terminoBusquedaActual) {
                    emptyMsg.textContent = `No hay proyectos que coincidan con la búsqueda "${terminoBusquedaActual}".`;
                } else {
                    emptyMsg.textContent = `La línea de negocio "${info.nombre}" no cuenta con proyectos activos registrados en este momento.`;
                }
            }
            return;
        }

        if (table) table.style.display = 'table';
        if (emptyState) emptyState.style.display = 'none';

        filtrados.forEach((item, index) => {
            const monto = parseFloat(item.monto_estimado) || 0;
            const pct = sumaTotal > 0 ? ((monto / sumaTotal) * 100).toFixed(1) : '0.0';
            const lineaItemColor = item.linea_color || '#6366f1';
            const lineaItemIcon = item.linea_icono || 'briefcase';
            const estadoBadge = getEstadoBadgeClass(item.estado);
            const estadoLabel = getEstadoLabelJs(item.estado);

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="desglose-row-index">${index + 1}</td>
                <td>
                    <span class="desglose-code-badge">${escapeHtml(item.codigo || '-')}</span>
                </td>
                <td>
                    <div class="desglose-item-title">${escapeHtml(item.nombre || 'Sin título')}</div>
                    <div class="desglose-item-client">
                        <i class="fas fa-building" style="font-size:10px;"></i>
                        <span>${escapeHtml(item.cliente_nombre || 'Entidad no especificada')}</span>
                    </div>
                </td>
                <td>
                    <span class="desglose-linea-pill" style="background: ${lineaItemColor}18; color: ${lineaItemColor}">
                        <i class="fas fa-${lineaItemIcon}"></i> ${escapeHtml(item.linea_nombre || '-')}
                    </span>
                </td>
                <td>
                    <span class="desglose-status-pill ${estadoBadge}">${estadoLabel}</span>
                </td>
                <td>
                    <div class="desglose-pct-container" title="${pct}% de la suma total">
                        <span class="desglose-pct-text">${pct}%</span>
                        <div class="desglose-pct-bar">
                            <div class="desglose-pct-bar-fill" style="width: ${Math.min(100, Math.max(3, pct))}%;"></div>
                        </div>
                    </div>
                </td>
                <td class="desglose-price-cell">
                    ${formatMoneyJs(monto)}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Copiar los datos actuales filtrados al portapapeles formateados para pegar directo en Excel
     */
    window.copiarDesgloseExcel = function() {
        let filtrados = DESGLOSE_DATA.slice();
        if (lineaActualFiltro !== 'todas') {
            filtrados = filtrados.filter(item => String(item.linea_negocio_id) === String(lineaActualFiltro));
        }
        if (terminoBusquedaActual) {
            filtrados = filtrados.filter(item => {
                const cod = (item.codigo || '').toLowerCase();
                const nom = (item.nombre || '').toLowerCase();
                const cli = (item.cliente_nombre || '').toLowerCase();
                const est = (item.estado || '').toLowerCase();
                return cod.includes(terminoBusquedaActual) ||
                       nom.includes(terminoBusquedaActual) ||
                       cli.includes(terminoBusquedaActual) ||
                       est.includes(terminoBusquedaActual);
            });
        }

        if (filtrados.length === 0) {
            mostrarToast('No hay datos para copiar.');
            return;
        }

        // Construir formato TSV (Tab Separated Values)
        let tsv = "#\tCódigo\tProyecto / Oportunidad\tEntidad / Cliente\tLínea de Negocio\tEstado\tPrecio Estimado (S/)\n";
        let suma = 0;
        filtrados.forEach((it, idx) => {
            const m = parseFloat(it.monto_estimado) || 0;
            suma += m;
            tsv += `${idx + 1}\t${it.codigo}\t${(it.nombre||'').replace(/\t|\n/g, ' ')}\t${(it.cliente_nombre||'').replace(/\t|\n/g, ' ')}\t${it.linea_nombre}\t${it.estado}\t${m.toFixed(2)}\n`;
        });
        tsv += `\t\tTOTAL SUMA AUDITADA\t\t\t\t${suma.toFixed(2)}\n`;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(tsv).then(() => {
                mostrarToast(`✓ ¡${filtrados.length} proyectos copiados! Pégalos directamente en Excel con Ctrl+V.`);
            }).catch(() => {
                copiarFallback(tsv, filtrados.length);
            });
        } else {
            copiarFallback(tsv, filtrados.length);
        }
    };

    function copiarFallback(text, totalRows) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            mostrarToast(`✓ ¡${totalRows} proyectos copiados! Pégalos directamente en Excel.`);
        } catch (e) {
            alert('No se pudo copiar automáticamente. Por favor selecciona el texto manualmente.');
        }
        document.body.removeChild(ta);
    }

    function mostrarToast(msg) {
        const toast = document.getElementById('desgloseToast');
        const toastMsg = document.getElementById('desgloseToastMsg');
        if (!toast) return;
        if (toastMsg) toastMsg.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3500);
    }

})();
</script>
