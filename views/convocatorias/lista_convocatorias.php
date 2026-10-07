<!-- =====================================================
     BÚSQUEDA DE LICITACIONES / CONVOCATORIAS
     ===================================================== -->

<div class="page-header" style="justify-content: space-between; align-items: flex-start;">
    <div>
        <h2>Convocatorias detectadas</h2>
        <p>Concursos encontrados en fuentes externas pendientes de revisión inicial.</p>
    </div>
    <div style="background: white; border: 1px solid var(--gray-200); border-radius: 8px; padding: 4px 12px; display: flex; align-items: center; gap: 8px; width: 300px;">
        <i class="fas fa-search" style="color: var(--gray-400);"></i>
        <input type="text" placeholder="Buscar entidad u objeto" id="search-convocatorias"
               style="border: none; outline: none; background: transparent; width: 100%; padding: 6px 0; font-size: 13px;"
               value="<?= htmlspecialchars($data['filtros']['busqueda'] ?? '') ?>"
               onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=convocatorias&busqueda='+this.value">
    </div>
</div>

<!-- Info Box - Portal de búsqueda -->
<div class="info-box" style="background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: var(--radius-lg); padding: 24px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="color: var(--accent-600); display: flex; align-items: center; gap: 8px; font-size: 16px;">
            <i class="fas fa-search"></i> Buscar en OECE / SEACE
        </h3>
        <span style="font-size: 11px; font-weight: 600; color: var(--accent-600); background: white; padding: 4px 10px; border-radius: 4px; border: 1px solid #ccfbf1;">
            Fuente oficial OCDS
        </span>
    </div>
    <p style="color: var(--gray-600); font-size: 13px; margin-bottom: 20px;">
        Consulta la API oficial de Contrataciones Abiertas y preselecciona procesos compatibles.
    </p>
    
    <div class="api-connection-box">
        <div class="api-card">
            <h4 class="api-card-title">Portales consultados y enlaces de validación</h4>
            <p class="api-card-desc">La búsqueda automática usa Contrataciones Abiertas; los demás enlaces permiten contrastar el proceso directamente.</p>
            <div class="portal-grid">
                <a href="https://www.gob.pe/seace" target="_blank" class="portal-link">
                    <div class="portal-link-text">
                        <strong>Buscadores públicos SEACE</strong>
                        <span>Página oficial en Gob.pe</span>
                    </div>
                    <i class="fas fa-external-link-alt"></i>
                </a>
                <a href="#" class="portal-link">
                    <div class="portal-link-text">
                        <strong>Buscador público SEACE V3</strong>
                        <span>Validación manual de procedimientos</span>
                    </div>
                    <i class="fas fa-external-link-alt"></i>
                </a>
                <a href="https://contratacionesabiertas.oece.gob.pe" target="_blank" class="portal-link">
                    <div class="portal-link-text">
                        <strong>Contrataciones Abiertas OECE</strong>
                        <span>Datos abiertos bajo estándar OCDS</span>
                    </div>
                    <i class="fas fa-external-link-alt"></i>
                </a>
                <a href="#" class="portal-link">
                    <div class="portal-link-text">
                        <strong>Documentación oficial de la API</strong>
                        <span>Métodos y parámetros disponibles</span>
                    </div>
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
        </div>
        
        <div class="api-card api-status-card">
            <div class="api-status-badge">
                <i class="fas fa-wifi"></i> Acceso restringido por OECE · HTTP 403
            </div>
            <h4 class="api-card-title">API conectada</h4>
            <p class="api-card-desc">GET /api/v1/releases<br>· SEACE V3</p>
            
            <a href="https://contratacionesabiertas.oece.gob.pe/api/v1/releases" target="_blank" class="api-status-url">
                https://contratacionesabiertas.oece.gob.pe/api/v1/releases
            </a>
            
            <div class="api-status-msg">
                El portal respondió, pero rechazó la consulta desde el servidor.
            </div>
            <div class="api-status-date">
                Última verificación: 28/9/2026, 11:49:14 a. m.
            </div>
        </div>
    </div>
    
    <!-- Test Result (Success Simulation) -->
    <div class="test-result-card">
        <div class="test-result-header">
            <i class="fas fa-caret-down"></i> Prueba técnica satisfactoria · 24/09/2026
        </div>
        <div class="test-result-grid">
            <div class="test-result-item">
                <label>Parámetros enviados</label>
                <span><strong>Fuente:</strong> seace_v3 · Obras<br>20/09/2026 al 24/09/2026 · orden descendente</span>
            </div>
            <div class="test-result-item">
                <label>Respuesta</label>
                <span>HTTP 200 · OCDS 1.1<br>Datos publicados: 23/09/2026 20:01 (-05:00)</span>
            </div>
            <div class="test-result-item">
                <label>Proceso comprobado</label>
                <span>LP-ABR-256-2026-MDSM/CS-1<br>Municipalidad Distrital de San Marcos</span>
            </div>
            <div class="test-result-item">
                <label>Identificación</label>
                <span>ocds-dgv273-seacev3-1251986<br>Construcción de carretera · monto no informado</span>
            </div>
        </div>
        <div style="margin-top: 12px;">
            <a href="#" style="font-size: 11px; color: #16a34a; text-decoration: underline;">Abrir exactamente la consulta probada <i class="fas fa-external-link-alt" style="font-size:10px"></i></a>
        </div>
    </div>
    
    <!-- Filtros Inside Info Box -->
    <form class="filter-bar" method="get" action="<?= BASE_URL ?>/" style="background: transparent; border: none; padding: 0; box-shadow: none; margin-bottom: 0;">
        <input type="hidden" name="page" value="convocatorias">
        
        <div class="form-grid" style="grid-template-columns: repeat(4, 1fr); gap: 12px; align-items: end;">
            <div class="filter-group">
                <label style="font-size:11px; font-weight:600; color:var(--gray-600)">Objeto</label>
                <select name="linea_negocio_id" class="form-control" style="background:white; height:36px; font-size:13px;">
                    <option value="">Obras</option>
                    <?php foreach ($data['lineas_negocio'] ?? [] as $ln): ?>
                    <option value="<?= $ln['id'] ?>" <?= (($data['filtros']['linea_negocio_id'] ?? '') == $ln['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ln['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group" style="grid-column: span 2;">
                <label style="font-size:11px; font-weight:600; color:var(--gray-600)">Palabras clave</label>
                <input type="text" name="busqueda" class="form-control" style="background:white; height:36px; font-size:13px;" placeholder="carretera" value="<?= htmlspecialchars($data['filtros']['busqueda'] ?? '') ?>">
            </div>
            
            <div class="filter-group">
                <label style="font-size:11px; font-weight:600; color:var(--gray-600)">Departamento</label>
                <input type="text" name="departamento" class="form-control" style="background:white; height:36px; font-size:13px;" placeholder="Ej. Ucayali" value="<?= htmlspecialchars($data['filtros']['departamento'] ?? '') ?>">
            </div>
            
            <div class="filter-group">
                <label style="font-size:11px; font-weight:600; color:var(--gray-600)">Publicadas desde</label>
                <input type="date" name="fecha_desde" class="form-control" style="background:white; height:36px; font-size:13px;" value="2026-09-20">
            </div>
            
            <div class="filter-group">
                <label style="font-size:11px; font-weight:600; color:var(--gray-600)">Publicadas hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" style="background:white; height:36px; font-size:13px;" value="2026-09-24">
            </div>
            
            <div class="filter-group">
                <label style="font-size:11px; font-weight:600; color:var(--gray-600)">Monto mínimo</label>
                <input type="number" name="monto_min" class="form-control" style="background:white; height:36px; font-size:13px;" placeholder="0">
            </div>
            
            <div class="filter-group">
                <label style="font-size:11px; font-weight:600; color:var(--gray-600)">Monto máximo</label>
                <input type="text" class="form-control" style="background:white; height:36px; font-size:13px;" placeholder="Sin límite" disabled>
            </div>
            
            <div class="filter-group">
                <button type="submit" class="btn btn-primary" style="background: var(--accent-600); height: 36px; padding: 0 16px; font-size: 13px;">
                    <i class="fas fa-search"></i> Buscar convocatorias
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns: 1fr 1fr 2fr; margin-bottom: 20px;">
    <div class="stat-card" style="padding:16px 24px;">
        <div class="stat-card-title" style="margin-bottom:8px">Detectadas</div>
        <div class="stat-card-value" style="font-size:32px"><?= $data['stats']['detectadas'] ?? 4 ?></div>
    </div>
    <div class="stat-card" style="padding:16px 24px;">
        <div class="stat-card-title" style="margin-bottom:8px">Convertidas</div>
        <div class="stat-card-value" style="font-size:32px"><?= $data['stats']['convertidas'] ?? 1 ?></div>
    </div>
    <div class="stat-card" style="padding:16px 24px; display: flex; flex-direction: column; justify-content: center;">
        <div class="stat-card-title" style="margin-bottom:4px">Siguiente paso</div>
        <div class="stat-card-value" style="font-size:16px">Revisión inicial</div>
        <div class="stat-card-detail" style="margin-top:2px">Requisitos básicos, monto y fechas</div>
    </div>
</div>

<!-- Tabla -->
<div class="data-card" style="animation-delay:0.2s">
    <table class="data-table" id="tabla-convocatorias">
        <thead>
            <tr>
                <th>Convocatoria</th>
                <th>Gestión</th>
                <th>Requisitos</th>
                <th>Estado</th>
                <th style="text-align:right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['convocatorias'])): ?>
            <tr>
                <td colspan="5">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No se encontraron convocatorias</h3>
                        <p>Intenta ajustar los filtros de búsqueda o agrega una nueva convocatoria.</p>
                    </div>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($data['convocatorias'] as $conv): ?>
            <tr>
                <td>
                    <span class="cell-main" style="font-weight:600"><?= htmlspecialchars(mb_substr($conv['nombre'], 0, 55)) ?></span>
                    <span class="cell-sub" style="font-size:11px; margin-top:2px"><?= htmlspecialchars($conv['entidad'] ?? '-') ?></span>
                    <span class="cell-sub" style="font-size:11px"><?= $conv['codigo'] ?></span>
                </td>
                <td>
                    <span class="cell-main" style="font-weight:600"><?= htmlspecialchars($conv['tipo_gestion'] ?? $conv['linea_negocio'] ?? '-') ?></span>
                    <span class="cell-main" style="font-size:13px; margin-top:2px"><?= formatMoney($conv['monto_referencial']) ?></span>
                    <span class="cell-sub" style="font-size:11px"><?= htmlspecialchars($conv['plazo_ejecucion'] ?? '-') ?></span>
                </td>
                <td>
                    <span class="cell-main" style="font-size:13px; font-weight:600"><?= htmlspecialchars($conv['requisitos_profesionales'] ?? '-') ?></span>
                    <span class="cell-sub" style="font-size:11px; margin-top:2px">Oferta: <?= formatDate($conv['fecha_limite_oferta']) ?></span>
                </td>
                <td>
                    <span class="badge" style="background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; border-radius:4px; font-size:11px; padding:2px 8px; font-weight:500;">
                        <?= getEstadoLabel($conv['estado']) ?>
                    </span>
                </td>
                <td>
                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:8px;">
                        <a href="<?= BASE_URL ?>/convocatorias/ver/<?= $conv['id'] ?>" data-side-modal title="Ver Convocatoria" style="color:var(--gray-600); font-size:13px; font-weight:500; display:flex; align-items:center; gap:4px">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                        <a href="javascript:void(0)" onclick="abrirGlobalModal('Editar Convocatoria', '<?= BASE_URL ?>/convocatorias/editar/<?= $conv['id'] ?>?modal_iframe=1', 'fas fa-pen')" style="color:var(--gray-500); padding:4px" title="Editar convocatoria">
                            <i class="fas fa-pen" style="font-size:12px"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/convocatorias/eliminar/<?= $conv['id'] ?>" onclick="return confirm('¿Eliminar esta convocatoria?')" style="color:var(--rose-500); padding:4px">
                            <i class="fas fa-trash" style="font-size:12px"></i>
                        </a>
                        <?php if ($conv['estado'] === 'detectada'): ?>
                        <a href="<?= BASE_URL ?>/convocatorias/evaluar/<?= $conv['id'] ?>" style="background:var(--accent-600); color:white; border-radius:4px; padding:6px 12px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px; margin-left:4px">
                            <i class="fas fa-hammer"></i> Evaluar
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>



