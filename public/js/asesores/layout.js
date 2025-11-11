const sidebarToggleBtns = document.querySelectorAll(".sidebar-toggle");
const sidebar = document.querySelector(".sidebar");
const menuLinks = document.querySelectorAll(".menu-link");
const themeToggle = document.getElementById("themeToggle");
const logo = document.querySelector(".header-logo");

// Theme management
const getStoredTheme = () => localStorage.getItem("theme");
const setStoredTheme = (theme) => {
  localStorage.setItem("theme", theme);
  // Guardar también en cookie para que el servidor pueda leerlo
  document.cookie = `theme=${theme}; path=/; max-age=31536000`; // 1 año
};

const applyTheme = (theme) => {
  if (theme === "dark") {
    document.documentElement.classList.add("dark-theme");
    document.documentElement.setAttribute("data-theme", "dark");
    document.body.classList.add("dark-theme");
    if (logo) {
      logo.src = logo.dataset.logoDark;
    }
  } else {
    document.documentElement.classList.remove("dark-theme");
    document.documentElement.removeAttribute("data-theme");
    document.body.classList.remove("dark-theme");
    if (logo) {
      logo.src = logo.dataset.logoLight;
    }
  }
  
  updateThemeButton(theme);
};

const updateThemeButton = (theme) => {
  const icon = themeToggle.querySelector(".material-symbols-rounded");
  const text = themeToggle.querySelector(".theme-text");
  
  if (theme === "dark") {
    icon.textContent = "dark_mode";
    text.textContent = "Modo Oscuro";
  } else {
    icon.textContent = "light_mode";
    text.textContent = "Modo Claro";
  }
};

// Initialize theme from localStorage or default to light
const initialTheme = getStoredTheme() || "light";
// Asegurar que la cookie esté sincronizada con localStorage
if (initialTheme) {
  setStoredTheme(initialTheme);
}
applyTheme(initialTheme);

// Theme toggle event
themeToggle.addEventListener("click", () => {
  const currentTheme = document.body.classList.contains("dark-theme") ? "dark" : "light";
  const newTheme = currentTheme === "dark" ? "light" : "dark";
  setStoredTheme(newTheme);
  applyTheme(newTheme);
});

// Toggle sidebar collapsed state on buttons click
sidebarToggleBtns.forEach((btn) => {
  btn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
  });
});

// Expand sidebar by default on large screens
if (window.innerWidth > 768) sidebar.classList.remove("collapsed");

// Submenu toggle functionality
const submenuToggles = document.querySelectorAll(".submenu-toggle");

submenuToggles.forEach((toggle) => {
  toggle.addEventListener("click", (e) => {
    e.preventDefault();
    const submenu = toggle.nextElementSibling;
    if (submenu && submenu.classList.contains("submenu")) {
      submenu.classList.toggle("open");
      toggle.classList.toggle("active");
    }
  });
});

// Mobile toggle functionality
const mobileToggle = document.getElementById('mobileToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (mobileToggle) {
  mobileToggle.addEventListener('click', () => {
    sidebar.classList.add('active');
    sidebar.classList.remove('collapsed');
    if (sidebarOverlay) {
      sidebarOverlay.classList.add('active');
    }
  });
}

if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');
  });
}

// Cerrar sidebar en móvil al hacer clic en un enlace
if (window.innerWidth <= 1024) {
  menuLinks.forEach((link) => {
    link.addEventListener('click', () => {
      if (sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
        if (sidebarOverlay) {
          sidebarOverlay.classList.remove('active');
        }
      }
    });
  });
}

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
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Error en la petición');
        }
        
        return data;
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
