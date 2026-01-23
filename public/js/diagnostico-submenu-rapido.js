// Script de diagnóstico rápido para submenu


// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(function() {

    
    // 1. Verificar si existen los elementos
    const submenus = document.querySelectorAll('.submenu');

    
    if(submenus.length === 0) {

      return;
    }
    
    // 2. Verificar el primer submenu
    const firstSubmenu = submenus[0];




    
    // 3. Estilos computados
    const computed = window.getComputedStyle(firstSubmenu);







    
    // 4. Ancestros

    let el = firstSubmenu.parentElement;
    let level = 1;
    while(el && level < 10) {
      const c = window.getComputedStyle(el);


      el = el.parentElement;
      level++;
    }
    
    // 5. Probar el click

    const toggle = document.querySelector('.submenu-toggle');
    if(toggle) {

      toggle.click();
      
      setTimeout(function() {
        const submenuAfter = document.querySelector('.submenu');
        const computedAfter = window.getComputedStyle(submenuAfter);




      }, 100);
    }
    
  }, 500);
});
