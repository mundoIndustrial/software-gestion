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

