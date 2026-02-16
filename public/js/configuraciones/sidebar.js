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
  if (!themeToggle) return; // Exit if themeToggle doesn't exist
  
  const icon = themeToggle.querySelector(".material-symbols-rounded");
  const text = themeToggle.querySelector(".theme-text");
  
  if (icon) {
    icon.textContent = theme === "dark" ? "dark_mode" : "light_mode";
  }
  
  if (text) {
    text.textContent = theme === "dark" ? "Modo Oscuro" : "Modo Claro";
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
if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme = document.body.classList.contains("dark-theme") ? "dark" : "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    setStoredTheme(newTheme);
    applyTheme(newTheme);
  });
}

// Toggle sidebar collapsed state on buttons click
if (sidebarToggleBtns.length > 0 && sidebar) {
  sidebarToggleBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed");
      // También toggle la clase en el container para que la tabla se adapte
      const container = document.querySelector(".app-container");
      if (container) {
        container.classList.toggle("sidebar-collapsed");
      }
      // También toggle la clase en contador-wrapper
      const contadorWrapper = document.querySelector(".contador-wrapper");
      if (contadorWrapper) {
        contadorWrapper.classList.toggle("sidebar-collapsed");
      }
      // Ocultar/Mostrar flechas del modal
      const arrowContainers = document.querySelectorAll(".arrow-container");
      const isSidebarCollapsed = sidebar.classList.contains("collapsed");
      arrowContainers.forEach((container) => {
        if (isSidebarCollapsed) {
          container.style.display = "none !important";
        } else {
          container.style.display = "flex";
        }
      });
      
      // Cuando se colapsa, cerrar submenús abiertos
      if (isSidebarCollapsed) {

        const openSubmenus = document.querySelectorAll(".submenu.open");
        const activeToggles = document.querySelectorAll(".submenu-toggle.active");
        


        
        activeToggles.forEach((toggle, index) => {

          toggle.classList.remove("active");

        });
        
        openSubmenus.forEach((submenu) => {
          submenu.classList.remove("open");
        });
      }
      
      // Persistir estado en localStorage
      localStorage.setItem("sidebarCollapsed", sidebar.classList.contains("collapsed"));
    });
  });
}

// Restore sidebar state from localStorage on large screens
if (window.innerWidth > 768 && sidebar) {
  const sidebarCollapsed = localStorage.getItem("sidebarCollapsed");
  const container = document.querySelector(".app-container");
  const contadorWrapper = document.querySelector(".contador-wrapper");
  if (sidebarCollapsed === "true") {
    sidebar.classList.add("collapsed");
    if (container) {
      container.classList.add("sidebar-collapsed");
    }
    if (contadorWrapper) {
      contadorWrapper.classList.add("sidebar-collapsed");
    }
  } else {
    sidebar.classList.remove("collapsed");
    if (container) {
      container.classList.remove("sidebar-collapsed");
    }
    if (contadorWrapper) {
      contadorWrapper.classList.remove("sidebar-collapsed");
    }
  }
}

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

