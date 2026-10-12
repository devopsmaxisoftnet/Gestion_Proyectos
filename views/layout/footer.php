        </div><!-- /.content-area -->
    </main><!-- /.main-content -->
</div><!-- /.app-layout -->

<!-- Side Modal Global -->
<div id="globalSideModalOverlay" class="side-modal-overlay" onclick="closeSideModal()"></div>
<div id="globalSideModal" class="side-modal">
    <div class="side-modal-header">
        <h3 id="sideModalTitle">Detalle</h3>
        <button class="side-modal-close" onclick="closeSideModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="side-modal-body" id="sideModalBody">
        <div class="side-modal-loader" style="text-align:center; padding: 40px; color:#64748b;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top:10px;">Cargando...</p>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
    function openSideModal(url, title = 'Detalle') {
        if (typeof abrirGlobalModal === 'function') {
            abrirGlobalModal(title, url + (url.includes('?') ? '&' : '?') + 'modal_iframe=1', 'fas fa-edit', 'Ventana de gestión', 'lg');
            return;
        }
        document.getElementById('globalSideModalOverlay').classList.add('active');
        document.getElementById('globalSideModal').classList.add('active');
        document.getElementById('sideModalTitle').innerText = title;
        document.getElementById('sideModalBody').innerHTML = `
            <div class="side-modal-loader" style="text-align:center; padding: 40px; color:#64748b;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top:10px;">Cargando...</p>
            </div>`;
        
        fetch(url + (url.includes('?') ? '&' : '?') + 'ajax=1')
            .then(res => res.text())
            .then(html => {
                document.getElementById('sideModalBody').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('sideModalBody').innerHTML = '<p style="color:red; padding:20px;">Error al cargar la información.</p>';
            });
    }

    function closeSideModal() {
        document.getElementById('globalSideModalOverlay').classList.remove('active');
        document.getElementById('globalSideModal').classList.remove('active');
    }

    // Interceptar clicks
    document.addEventListener('DOMContentLoaded', () => {
        document.body.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-side-modal]');
            if (btn) {
                e.preventDefault();
                openSideModal(btn.href || btn.getAttribute('data-url'), btn.getAttribute('title') || 'Detalle');
            }
        });
    });
</script>
</body>
</html>



