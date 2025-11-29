// ========================================
// SIDEBAR TOGGLE
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileToggle');
    
    // Toggle sidebar en desktop
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            // En m├│viles, solo cerrar el sidebar sin colapso
            if (window.innerWidth <= 480) {
                sidebar.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            } else {
                // En desktop, aplicar colapso
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            }
        });
    }
    
    // Toggle sidebar en mobile
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            // En m├│viles, NO usar collapsed, solo show
            if (window.innerWidth <= 480) {
                sidebar.classList.toggle('show');
                document.body.classList.toggle('sidebar-open');
            } else {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            }
        });
    }
    
    // Restaurar estado del sidebar (solo en desktop)
    if (window.innerWidth > 480) {
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
        if (sidebarCollapsed === 'true') {
            sidebar.classList.add('collapsed');
        }
    }
    
    // Cerrar sidebar al hacer click fuera en mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 480) {
            if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target)) {
                sidebar.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            }
        }
    });
});

// ========================================
// THEME TOGGLE
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    
    // Cargar tema guardado
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        body.classList.add('dark-theme');
        updateThemeButton(true);
    }
    
    // Toggle theme
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            body.classList.toggle('dark-theme');
            const isDark = body.classList.contains('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeButton(isDark);
        });
    }
    
    function updateThemeButton(isDark) {
        const icon = themeToggle.querySelector('.material-symbols-rounded');
        const text = themeToggle.querySelector('.theme-text');
        const logo = document.querySelector('.header-logo');
        
        if (isDark) {
            if (icon) icon.textContent = 'light_mode';
            if (text) text.textContent = 'Modo Claro';
            if (logo) {
                logo.src = logo.dataset.logoDark || 'https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png';
            }
        } else {
            if (icon) icon.textContent = 'dark_mode';
            if (text) text.textContent = 'Modo Oscuro';
            if (logo) {
                logo.src = logo.dataset.logoLight || logo.dataset.logoLight;
            }
        }
    }
});

// ========================================
// USER DROPDOWN
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');
    
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('show');
            
            // Cerrar notificaciones si est├ín abiertas
            const notificationMenu = document.getElementById('notificationMenu');
            if (notificationMenu) {
                notificationMenu.classList.remove('show');
            }
        });
        
        // Cerrar al hacer click fuera
        document.addEventListener('click', function(event) {
            if (!userBtn.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.remove('show');
            }
        });
    }
});

// ========================================
// NOTIFICATION DROPDOWN
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationMenu = document.getElementById('notificationMenu');
    
    if (notificationBtn && notificationMenu) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationMenu.classList.toggle('show');
            
            // Cerrar men├║ de usuario si est├í abierto
            const userMenu = document.getElementById('userMenu');
            if (userMenu) {
                userMenu.classList.remove('show');
            }
        });
        
        // Cerrar al hacer click fuera
        document.addEventListener('click', function(event) {
            if (!notificationBtn.contains(event.target) && !notificationMenu.contains(event.target)) {
                notificationMenu.classList.remove('show');
            }
        });
    }
});

// ========================================
// CSRF TOKEN SETUP
// ========================================
const csrfToken = document.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.csrfToken = csrfToken.getAttribute('content');
}

// ========================================
// FETCH HELPER
// ========================================
window.fetchAPI = async function(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken,
            'Accept': 'application/json'
        }
    };
    
    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    };
    
    try {
        const response = await fetch(url, mergedOptions);
        
        if (!response.ok) {
            // Intentar parsear como JSON, si falla, usar texto del error HTTP
            let errorMessage = `Error HTTP ${response.status}`;
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorMessage;
            } catch {
                errorMessage = response.statusText || errorMessage;
            }
            throw new Error(errorMessage);
        }
        
        // Solo parsear como JSON si la respuesta es exitosa
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            return data;
        } else {
            throw new Error('Respuesta no es JSON válida');
        }
    } catch (error) {
        console.error('Error en fetchAPI:', error);
        throw error;
    }
};

// ========================================
// TOAST NOTIFICATIONS
// ========================================
window.showToast = function(message, type = 'success') {
    // Crear contenedor si no existe
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(toastContainer);
    }
    
    // Crear toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    
    toast.innerHTML = `<span>${message}</span>`;
    
    toastContainer.appendChild(toast);
    
    // Remover despu├®s de 3 segundos
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

// Agregar animaciones CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ========================================
// MODAL - VER ORDEN
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Escuchar clicks en botones "Ver Orden"
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-ver-orden');
        if (!btn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        console.log('🔵 Click en Ver Orden');
        
        // Obtener datos de la orden
        const ordenJSON = btn.getAttribute('data-orden');
        if (!ordenJSON) {
            console.error('❌ No hay datos de orden en el botón');
            return;
        }
        
        try {
            const orden = JSON.parse(ordenJSON);
            console.log('📋 Orden:', orden);
            
            // Llenar datos del modal
            llenarModalOrden(orden);
            
            // Abrir el modal usando Alpine.js event
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('open-modal', {
                    detail: 'order-detail'
                }));
                console.log('✅ Modal abierto');
            }, 100);
        } catch (error) {
            console.error('❌ Error al parsear orden:', error);
        }
    });
    
    // Función para llenar el modal con datos de la orden
    window.llenarModalOrden = function(orden) {
        try {
            // Llenar campos del modal
            const cliente = document.querySelector('#cliente-value');
            if (cliente) cliente.textContent = orden.cliente || 'Sin cliente';
            
            const descripcion = document.querySelector('#descripcion-text');
            if (descripcion) descripcion.textContent = orden.descripcion || 'Sin descripción';
            
            const asesora = document.querySelector('#asesora-value');
            if (asesora) asesora.textContent = orden.asesora || 'Sin asesora';
            
            const formaPago = document.querySelector('#forma-pago-value');
            if (formaPago) formaPago.textContent = orden.forma_pago || 'Sin forma de pago';
            
            const encargado = document.querySelector('#encargado-value');
            if (encargado) encargado.textContent = orden.encargado_orden || 'Sin encargado';
            
            const prendas = document.querySelector('#prendas-entregadas-value');
            if (prendas) prendas.textContent = orden.prendas_entregadas || '0';
            
            const pedido = document.querySelector('#order-pedido');
            if (pedido) pedido.textContent = orden.pedido || 'Sin código';
            
            // Llenar fecha
            if (orden.fecha_de_creacion_de_orden) {
                const fecha = new Date(orden.fecha_de_creacion_de_orden);
                const day = document.querySelector('.day-box');
                const month = document.querySelector('.month-box');
                const year = document.querySelector('.year-box');
                
                if (day) day.textContent = String(fecha.getDate()).padStart(2, '0');
                if (month) month.textContent = String(fecha.getMonth() + 1).padStart(2, '0');
                if (year) year.textContent = fecha.getFullYear();
            }
            
            console.log('✅ Modal llenado correctamente');
        } catch (error) {
            console.error('❌ Error al llenar modal:', error);
        }
    };
});
