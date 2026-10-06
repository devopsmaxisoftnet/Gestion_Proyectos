/**
 * Gestión Comercial - JavaScript
 * Interacciones y animaciones
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // =====================================================
    // Animaciones de entrada para elementos
    // =====================================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const animateOnScroll = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                animateOnScroll.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observar stat cards y linea cards para animación al scroll
    document.querySelectorAll('.stat-card, .linea-card, .data-card, .info-box, .flow-widget').forEach(el => {
        animateOnScroll.observe(el);
    });
    
    // =====================================================
    // Contadores animados
    // =====================================================
    function animateCounter(element, target, duration = 1000) {
        let start = 0;
        const increment = target / (duration / 16);
        
        function update() {
            start += increment;
            if (start >= target) {
                element.textContent = target.toLocaleString();
                return;
            }
            element.textContent = Math.floor(start).toLocaleString();
            requestAnimationFrame(update);
        }
        
        update();
    }
    
    // Animar los valores de stat cards
    document.querySelectorAll('.stat-card-value').forEach(el => {
        const value = parseInt(el.textContent.replace(/[^0-9]/g, ''));
        if (!isNaN(value) && value > 0 && value < 100) {
            el.textContent = '0';
            setTimeout(() => animateCounter(el, value, 800), 300);
        }
    });
    
    // =====================================================
    // Progress bars animados
    // =====================================================
    document.querySelectorAll('.hero-progress-fill, .progress-bar-fill, .flow-item-bar-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
    
    // =====================================================
    // Hover effect en filas de tabla
    // =====================================================
    document.querySelectorAll('.data-table tbody tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.2s ease';
        });
    });
    
    // =====================================================
    // Sidebar active link indicator
    // =====================================================
    const activeLink = document.querySelector('.sidebar-link.active');
    if (activeLink) {
        activeLink.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    
    // =====================================================
    // Mobile sidebar toggle
    // =====================================================
    const sidebar = document.getElementById('sidebar');
    
    // Crear botón de menú móvil
    const menuBtn = document.createElement('button');
    menuBtn.className = 'btn-icon mobile-menu-btn';
    menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    menuBtn.style.cssText = 'display:none;position:fixed;top:14px;left:12px;z-index:200;background:white;box-shadow:0 2px 8px rgba(0,0,0,0.1);border-radius:8px;width:40px;height:40px;';
    document.body.appendChild(menuBtn);
    
    function checkMobile() {
        if (window.innerWidth <= 768) {
            menuBtn.style.display = 'flex';
        } else {
            menuBtn.style.display = 'none';
            sidebar.classList.remove('open');
        }
    }
    
    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });
    
    window.addEventListener('resize', checkMobile);
    checkMobile();
    
    // Cerrar sidebar al hacer click fuera en móvil
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
    
    // =====================================================
    // Tooltips simples
    // =====================================================
    document.querySelectorAll('[title]').forEach(el => {
        el.addEventListener('mouseenter', function() {
            this.setAttribute('data-title', this.getAttribute('title'));
            this.removeAttribute('title');
        });
        el.addEventListener('mouseleave', function() {
            if (this.getAttribute('data-title')) {
                this.setAttribute('title', this.getAttribute('data-title'));
                this.removeAttribute('data-title');
            }
        });
    });
    
    // =====================================================
    // Confirmación de eliminación con estilo
    // =====================================================
    document.querySelectorAll('a[onclick*="confirm"]').forEach(link => {
        link.addEventListener('click', function(e) {
            // La confirmación nativa se maneja por el onclick
        });
    });
    
    // =====================================================
    // Efecto ripple en botones
    // =====================================================
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255,255,255,0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: rippleEffect 0.6s ease-out;
                pointer-events: none;
            `;
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    // Agregar la animación de ripple al CSS dinámicamente
    const style = document.createElement('style');
    style.textContent = `
        @keyframes rippleEffect {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .badge-suspended {
            background: #fee2e2;
            color: #991b1b;
        }
    `;
    document.head.appendChild(style);
    
    // =====================================================
    // Auto-hide toasts
    // =====================================================
    document.querySelectorAll('.toast').forEach(toast => {
        setTimeout(() => {
            toast.style.animation = 'slideInRight 0.3s ease-out reverse forwards';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    });
    
    // =====================================================
    // Búsqueda en tiempo real (Client-side filtering)
    // =====================================================
    const searchInputs = document.querySelectorAll('input[id^="search-"]');
    
    searchInputs.forEach(input => {
        // Remove the onkeyup inline attribute to avoid server-side redirect on Enter
        input.removeAttribute('onkeyup');
        
        input.addEventListener('input', function(e) {
            const searchTerm = this.value.toLowerCase().trim();
            const tableId = this.id.replace('search-', 'tabla-');
            const table = document.getElementById(tableId);
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    // Skip empty state row
                    if (row.querySelector('.empty-state')) return;
                    
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
        
        // Evitar que el Enter recargue la página si el input está en un form
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    });

    console.log('🚀 Gestión Comercial v1.0 - Sistema cargado correctamente');
});
