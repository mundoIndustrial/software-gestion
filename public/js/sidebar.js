const sidebarToggleBtns = document.querySelectorAll(".sidebar-toggle");
const sidebar = document.querySelector(".sidebar");
const searchForm = document.querySelector(".search-form");
const menuLinks = document.querySelectorAll(".menu-link");

// Apply dark theme by default
document.body.classList.add("dark-theme");

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
