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
// USER DROPDOWN
// ========================================
// COMENTADO: El manejo del dropdown de usuario se hace en top-nav.js para evitar conflictos
// que causaban que el menú se cerrara inmediatamente después de abrirse
/*
document.addEventListener('DOMContentLoaded', function() {
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');
    
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            


            
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
    } else {

    }
});
*/

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

        throw error;
    }
};

