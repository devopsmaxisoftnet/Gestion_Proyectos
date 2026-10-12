/**
 * ==========================================================================
 * SISTEMA UNIVERSAL DE PAGINACIÓN ANIMADA - GESTIÓN DE PROYECTOS ERP
 * Paginación dinámica (10 por página por defecto), suavemente animada y responsiva.
 * ==========================================================================
 */

class ERPTablePaginator {
    constructor(table, options = {}) {
        this.table = typeof table === 'string' ? document.querySelector(table) : table;
        if (!this.table) return;

        this.options = Object.assign({
            pageSize: 10,
            entityName: 'registros',
            entityIcon: 'fas fa-layer-group',
            searchInput: null,
            minRowsToPaginate: 10
        }, options);

        this.tbody = this.table.querySelector('tbody');
        if (!this.tbody) return;

        // Identificar filas de datos reales (excluyendo mensajes de vacío)
        this.allRows = Array.from(this.tbody.querySelectorAll('tr')).filter(tr => {
            return !tr.querySelector('.empty-state') && !tr.classList.contains('no-paginate-row') && !tr.id.includes('empty');
        });

        this.totalRows = this.allRows.length;

        // Si no excede los 10 registros, no mostrar paginación salvo que se fuerce
        if (this.totalRows <= this.options.minRowsToPaginate && !options.forceShow) {
            // Asegurar que todas las filas estén visibles
            this.allRows.forEach(r => r.style.display = '');
            const existingFooter = this.table.parentElement.querySelector('.pagination-footer-wrapper');
            if (existingFooter) existingFooter.remove();
            return;
        }

        this.currentPage = 1;
        this.pageSize = this.options.pageSize;
        this.filteredRows = [...this.allRows];

        // Leer parámetro URL ?pagina= si aplica
        const urlParams = new URLSearchParams(window.location.search);
        const urlPage = parseInt(urlParams.get('pagina'), 10);
        if (!isNaN(urlPage) && urlPage > 0) {
            this.currentPage = urlPage;
        }

        this.initDOM();
        this.bindSearch();
        this.render();
    }

    initDOM() {
        // Remover cualquier barra previa en el contenedor
        const parentCard = this.table.closest('.data-card') || this.table.parentElement;
        const oldFooter = parentCard.querySelector('.pagination-footer-wrapper');
        if (oldFooter) oldFooter.remove();

        // Crear la estructura de la barra de paginación
        this.footer = document.createElement('div');
        this.footer.className = 'pagination-footer-wrapper';
        this.footer.innerHTML = `
            <div class="pagination-info-group">
                <div class="pagination-badge-info">
                    <i class="${this.options.entityIcon}"></i>
                    <span>Mostrando <strong class="pag-range-text">1 - 10</strong> de <strong class="pag-total-text">${this.totalRows}</strong> ${this.options.entityName}</span>
                </div>
                <div class="pagination-select-wrapper">
                    <span>Por página:</span>
                    <select class="pag-size-select">
                        <option value="10" ${this.pageSize == 10 ? 'selected' : ''}>10</option>
                        <option value="20" ${this.pageSize == 20 ? 'selected' : ''}>20</option>
                        <option value="50" ${this.pageSize == 50 ? 'selected' : ''}>50</option>
                        <option value="all" ${this.pageSize == 'all' ? 'selected' : ''}>Todos</option>
                    </select>
                </div>
            </div>
            <div class="pagination-controls-nav">
                <!-- Botones inyectados dinámicamente -->
            </div>
        `;

        // Insertar después de la tabla
        if (this.table.nextSibling) {
            this.table.parentNode.insertBefore(this.footer, this.table.nextSibling);
        } else {
            this.table.parentNode.appendChild(this.footer);
        }

        this.rangeEl = this.footer.querySelector('.pag-range-text');
        this.totalEl = this.footer.querySelector('.pag-total-text');
        this.controlsNav = this.footer.querySelector('.pagination-controls-nav');
        this.sizeSelect = this.footer.querySelector('.pag-size-select');

        this.sizeSelect.addEventListener('change', (e) => {
            const val = e.target.value;
            this.pageSize = val === 'all' ? 'all' : parseInt(val, 10);
            this.currentPage = 1;
            this.render();
        });
    }

    bindSearch() {
        let searchInput = null;
        if (this.options.searchInput) {
            searchInput = typeof this.options.searchInput === 'string' 
                ? document.querySelector(this.options.searchInput) 
                : this.options.searchInput;
        } else {
            // Intentar detectar buscador cercano en el header de la página
            const card = this.table.closest('.data-card') || document;
            searchInput = card.querySelector('input[type="text"][id*="search"], input[type="text"][placeholder*="Buscar"]');
            if (!searchInput) {
                searchInput = document.querySelector('input[type="text"][id*="search"], .search-bar input');
            }
        }

        if (searchInput && !searchInput.dataset.pagSearchBound) {
            searchInput.dataset.pagSearchBound = 'true';
            
            // Interceptar tipeo para filtrado dinámico inmediato
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase().trim();
                if (!term) {
                    this.filteredRows = [...this.allRows];
                } else {
                    this.filteredRows = this.allRows.filter(row => {
                        return row.textContent.toLowerCase().includes(term);
                    });
                }
                this.currentPage = 1;
                this.render();
            });
        }
    }

    goToPage(page) {
        this.currentPage = page;
        this.render();

        // Desplazar suavemente si la tabla está fuera del viewport superior
        const rect = this.table.getBoundingClientRect();
        if (rect.top < 60) {
            this.table.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    render() {
        const totalItems = this.filteredRows.length;
        const totalPages = this.pageSize === 'all' ? 1 : Math.max(1, Math.ceil(totalItems / this.pageSize));

        if (this.currentPage > totalPages) this.currentPage = totalPages;
        if (this.currentPage < 1) this.currentPage = 1;

        const startIndex = this.pageSize === 'all' ? 0 : (this.currentPage - 1) * this.pageSize;
        const endIndex = this.pageSize === 'all' ? totalItems : Math.min(startIndex + this.pageSize, totalItems);

        // Ocultar todas las filas
        this.allRows.forEach(row => {
            row.style.display = 'none';
            row.classList.remove('pag-row-animated');
        });

        // Mostrar y animar las filas correspondientes a la página activa
        let delayIndex = 0;
        for (let i = startIndex; i < endIndex; i++) {
            const row = this.filteredRows[i];
            if (row) {
                row.style.display = '';
                row.style.setProperty('--pag-delay', delayIndex);
                row.classList.add('pag-row-animated');
                delayIndex++;
            }
        }

        // Actualizar textos informativos
        if (this.rangeEl && this.totalEl) {
            if (totalItems === 0) {
                this.rangeEl.textContent = '0 - 0';
            } else {
                this.rangeEl.textContent = `${startIndex + 1} - ${endIndex}`;
            }
            this.totalEl.textContent = totalItems;
        }

        // Si el total de items es menor o igual a 10 y pageSize es 10, podemos ocultar los controles si no hay más páginas
        if (totalPages <= 1 && totalItems <= this.options.minRowsToPaginate && this.pageSize === 10) {
            this.footer.style.display = 'none';
            return;
        } else {
            this.footer.style.display = 'flex';
        }

        // Renderizar botones de paginación
        this.renderNavButtons(totalPages);
    }

    renderNavButtons(totalPages) {
        if (!this.controlsNav) return;
        this.controlsNav.innerHTML = '';

        if (totalPages <= 1) return;

        // Botón Primero
        const btnFirst = document.createElement('button');
        btnFirst.type = 'button';
        btnFirst.className = 'pag-ctrl-btn';
        btnFirst.innerHTML = '<i class="fas fa-angle-double-left"></i>';
        btnFirst.title = 'Primera página';
        btnFirst.disabled = (this.currentPage === 1);
        btnFirst.onclick = () => this.goToPage(1);
        this.controlsNav.appendChild(btnFirst);

        // Botón Anterior
        const btnPrev = document.createElement('button');
        btnPrev.type = 'button';
        btnPrev.className = 'pag-ctrl-btn';
        btnPrev.innerHTML = '<i class="fas fa-chevron-left"></i>';
        btnPrev.title = 'Página anterior';
        btnPrev.disabled = (this.currentPage === 1);
        btnPrev.onclick = () => this.goToPage(this.currentPage - 1);
        this.controlsNav.appendChild(btnPrev);

        // Calcular rango de números visibles
        let pages = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            let start = Math.max(2, this.currentPage - 1);
            let end = Math.min(totalPages - 1, this.currentPage + 1);

            if (start > 2) pages.push('ellipsis');
            for (let i = start; i <= end; i++) pages.push(i);
            if (end < totalPages - 1) pages.push('ellipsis');

            pages.push(totalPages);
        }

        // Inyectar botones de páginas
        pages.forEach(p => {
            if (p === 'ellipsis') {
                const span = document.createElement('span');
                span.className = 'pag-ctrl-ellipsis';
                span.innerHTML = '…';
                this.controlsNav.appendChild(span);
            } else {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'pag-ctrl-btn' + (p === this.currentPage ? ' active' : '');
                btn.textContent = p;
                btn.onclick = () => this.goToPage(p);
                this.controlsNav.appendChild(btn);
            }
        });

        // Botón Siguiente
        const btnNext = document.createElement('button');
        btnNext.type = 'button';
        btnNext.className = 'pag-ctrl-btn';
        btnNext.innerHTML = '<i class="fas fa-chevron-right"></i>';
        btnNext.title = 'Página siguiente';
        btnNext.disabled = (this.currentPage === totalPages);
        btnNext.onclick = () => this.goToPage(this.currentPage + 1);
        this.controlsNav.appendChild(btnNext);

        // Botón Último
        const btnLast = document.createElement('button');
        btnLast.type = 'button';
        btnLast.className = 'pag-ctrl-btn';
        btnLast.innerHTML = '<i class="fas fa-angle-double-right"></i>';
        btnLast.title = 'Última página';
        btnLast.disabled = (this.currentPage === totalPages);
        btnLast.onclick = () => this.goToPage(totalPages);
        this.controlsNav.appendChild(btnLast);
    }
}

// Inicializador global automático para todo el sistema
function inicializarPaginacionGlobal() {
    const tableConfigs = [
        { id: 'tabla-contratos', name: 'contratos', icon: 'fas fa-file-contract' },
        { id: 'tabla-oportunidades', name: 'oportunidades', icon: 'fas fa-bullseye' },
        { id: 'tabla-convocatorias', name: 'convocatorias', icon: 'fas fa-search' },
        { id: 'tabla-licitaciones', name: 'licitaciones', icon: 'fas fa-gavel' },
        { id: 'tabla-propuestas', name: 'propuestas', icon: 'fas fa-paper-plane' },
        { id: 'tabla-proyectos', name: 'proyectos', icon: 'fas fa-project-diagram' },
        { id: 'tabla-empresas', name: 'empresas', icon: 'fas fa-city' },
        { id: 'tabla-clientes', name: 'clientes', icon: 'fas fa-building' },
        { id: 'tabla-profesionales', name: 'profesionales', icon: 'fas fa-users' },
        { id: 'tabla-usuarios', name: 'usuarios', icon: 'fas fa-user-shield' }
    ];

    tableConfigs.forEach(conf => {
        const table = document.getElementById(conf.id);
        if (table && !table.dataset.paginatorActive) {
            table.dataset.paginatorActive = 'true';
            new ERPTablePaginator(table, {
                pageSize: 10,
                entityName: conf.name,
                entityIcon: conf.icon,
                minRowsToPaginate: 10
            });
        }
    });

    // También escanear cualquier otra tabla con clase .data-table que no haya sido inicializada
    document.querySelectorAll('table.data-table:not([data-paginator-active])').forEach(table => {
        // Excluir si ya tiene paginación manual inline
        if (table.parentElement.querySelector('.pagination-footer-wrapper')) return;
        
        table.dataset.paginatorActive = 'true';
        new ERPTablePaginator(table, {
            pageSize: 10,
            entityName: 'registros',
            entityIcon: 'fas fa-layer-group',
            minRowsToPaginate: 10
        });
    });
}

// Iniciar al cargar el documento
document.addEventListener('DOMContentLoaded', inicializarPaginacionGlobal);

// Exponer función para uso manual o tras recargas dinámicas
window.ERPTablePaginator = ERPTablePaginator;
window.inicializarPaginacionGlobal = inicializarPaginacionGlobal;
