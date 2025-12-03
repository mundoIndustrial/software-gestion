// ========================================
// SIDEBAR MOBILE TOGGLE
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('mobileToggle');
    
    // Toggle sidebar en mobile
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function() {
            // En móviles, mostrar/ocultar el sidebar
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                document.body.classList.toggle('sidebar-open');
            }
        });
    }
    
    // Cerrar sidebar al hacer click fuera en mobile
    if (sidebar && mobileToggle) {
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            }
        });
    }
});

// ========================================
// THEME TOGGLE
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const html = document.documentElement;
    
    // Toggle theme
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            body.classList.toggle('dark-theme');
            html.classList.toggle('dark-theme');
            html.setAttribute('data-theme', body.classList.contains('dark-theme') ? 'dark' : 'light');
            
            const isDark = body.classList.contains('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeButton(isDark);
        });
    }
    
    // Sincronizar estado actual del tema
    const currentIsDark = body.classList.contains('dark-theme');
    if (currentIsDark) {
        updateThemeButton(true);
    } else {
        updateThemeButton(false);
    }
    
    function updateThemeButton(isDark) {
        if (!themeToggle) return;
        
        const icon = themeToggle.querySelector('.material-symbols-rounded');
        const text = themeToggle.querySelector('.theme-text');
        const logo = document.querySelector('.header-logo');
        
        if (isDark) {
            if (icon) icon.textContent = 'light_mode';
            if (text) text.textContent = 'Modo Claro';
            if (logo && logo.dataset.logoDark) {
                logo.src = logo.dataset.logoDark;
            }
        } else {
            if (icon) icon.textContent = 'dark_mode';
            if (text) text.textContent = 'Modo Oscuro';
            if (logo && logo.dataset.logoLight) {
                logo.src = logo.dataset.logoLight;
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
            
            // Cerrar notificaciones si están abiertas
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
            
            // Cerrar menú de usuario si está abierto
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

/**
 * Maneja respuesta de fetch del servidor
 */
async function _handleAsesorFetchResponse(response) {
    const data = await response.json();
    
    if (!response.ok) {
        throw new Error(data.message || 'Error en la petición');
    }
    
    return data;
}

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
        return await _handleAsesorFetchResponse(response);
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
    
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
    toast.innerHTML = `
        <span style="font-size: 1.25rem;">${icon}</span>
        <span>${message}</span>
    `;
    
    toastContainer.appendChild(toast);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

// Agregar animaciones CSS
const toastStyleElement = document.createElement('style');
toastStyleElement.textContent = `
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
document.head.appendChild(toastStyleElement);

