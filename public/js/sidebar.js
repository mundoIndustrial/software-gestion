const sidebarToggleBtns = document.querySelectorAll(".sidebar-toggle");
const sidebar = document.querySelector(".sidebar");
const searchForm = document.querySelector(".search-form");
const menuLinks = document.querySelectorAll(".menu-link");
const themeToggle = document.getElementById("themeToggle");
const logo = document.querySelector(".header-logo");

// Theme management
const getStoredTheme = () => localStorage.getItem("theme");
const setStoredTheme = (theme) => localStorage.setItem("theme", theme);

const applyTheme = (theme) => {
  if (logo) {
    // Agregar clase para fade out
    logo.classList.add("changing");
    
    // Esperar a que termine el fade out antes de cambiar la imagen
    setTimeout(() => {
      if (theme === "dark") {
        document.body.classList.add("dark-theme");
        logo.src = logo.dataset.logoDark;
      } else {
        document.body.classList.remove("dark-theme");
        logo.src = logo.dataset.logoLight;
      }
      
      // Remover clase para fade in
      setTimeout(() => {
        logo.classList.remove("changing");
      }, 30);
    }, 250);
  } else {
    // Si no hay logo, solo cambiar el tema
    if (theme === "dark") {
      document.body.classList.add("dark-theme");
    } else {
      document.body.classList.remove("dark-theme");
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

// Expand the sidebar when the search form is clicked
searchForm.addEventListener("click", () => {
  if (sidebar.classList.contains("collapsed")) {
    sidebar.classList.remove("collapsed");
    searchForm.querySelector("input").focus();
  }
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
