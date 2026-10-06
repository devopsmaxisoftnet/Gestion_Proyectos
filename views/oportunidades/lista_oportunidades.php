<!-- =====================================================
     BANDEJA DE OPORTUNIDADES
     ===================================================== -->

<div class="page-header">
    <div>
        <h2>Oportunidades registradas</h2>
        <p>Evalúa liquidez, capacidad operativa y decisión de participación.</p>
    </div>
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar por entidad u objeto" id="search-oportunidades"
               value="<?= htmlspecialchars($data['filtros']['busqueda']) ?>"
               onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=oportunidades&busqueda='+this.value">
    </div>
</div>

<div class="data-card data-section full-width">
    <table class="data-table" id="tabla-oportunidades">
        <thead>
            <tr>
                <th>Oportunidad</th>
                <th>Entidad</th>
                <th>Línea</th>
                <th>Monto</th>
                <th>Fecha límite</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['oportunidades'])): ?>
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <i class="fas fa-bullseye"></i>
                        <h3>No hay oportunidades</h3>
                        <p>Comienza detectando convocatorias o crea una oportunidad manualmente.</p>
                    </div>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($data['oportunidades'] as $op): ?>
            <tr>
                <td>
                    <span class="cell-main"><?= htmlspecialchars(mb_substr($op['nombre'], 0, 50)) ?></span>
                    <span class="cell-sub"><?= $op['codigo'] ?></span>
                </td>
                <td><?= htmlspecialchars($op['entidad'] ?? '-') ?></td>
                <td><?= htmlspecialchars($op['linea'] ?? '-') ?></td>
                <td class="cell-money"><?= formatMoney($op['monto_estimado']) ?></td>
                <td><?= formatDate($op['fecha_limite']) ?></td>
                <td><span class="badge <?= getEstadoClass($op['estado']) ?>"><?= getEstadoLabel($op['estado']) ?></span></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/oportunidades/ver/<?= $op['id'] ?>" class="section-action" style="font-size:12px">
                            Revisar <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>



