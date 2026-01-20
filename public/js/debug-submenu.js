// Debug script para verificar funcionamiento de submenus
console.log(' === SUBMENU DEBUG SCRIPT INICIADO ===');

// Verificar estructura HTML
const submenuToggles = document.querySelectorAll('.submenu-toggle');
console.log(` Encontrados ${submenuToggles.length} botones submenu-toggle`);

const submenus = document.querySelectorAll('.submenu');
console.log(` Encontrados ${submenus.length} elementos .submenu`);

// Verificar estilos computados de un submenu
if (submenus.length > 0) {
  const firstSubmenu = submenus[0];
  const computed = window.getComputedStyle(firstSubmenu);
  
  console.group(' Estilos Computados del Primer Submenu:');
  console.log('max-height:', computed.maxHeight);
  console.log('opacity:', computed.opacity);
  console.log('overflow:', computed.overflow);
  console.log('display:', computed.display);
  console.log('visibility:', computed.visibility);
  console.log('pointer-events:', computed.pointerEvents);
  console.log('Clases:', firstSubmenu.className);
  console.groupEnd();
}

// Verificar que el JavaScript de toggle funciona
if (submenuToggles.length > 0) {
  const firstToggle = submenuToggles[0];
  console.log('🧪 Probando click en primer toggle...');
  
  firstToggle.addEventListener('click', function(e) {
    const submenu = this.nextElementSibling;
    if (submenu && submenu.classList.contains('submenu')) {
      console.log('📌 Submenu encontrado:', submenu.className);
      console.log('📌 ¿Tiene clase open?:', submenu.classList.contains('open'));
      
      setTimeout(() => {
        const computed = window.getComputedStyle(submenu);
        console.log(' Después del click:');
        console.log('  - max-height:', computed.maxHeight);
        console.log('  - opacity:', computed.opacity);
        console.log('  - Clases:', submenu.className);
      }, 50);
    }
  });
}

// Verificar estilos de sidebar-content
const sidebarContent = document.querySelector('.sidebar-content');
if (sidebarContent) {
  const computed = window.getComputedStyle(sidebarContent);
  console.group(' Estilos del Contenedor .sidebar-content:');
  console.log('overflow-x:', computed.overflowX);
  console.log('overflow-y:', computed.overflowY);
  console.log('overflow:', computed.overflow);
  console.groupEnd();
}

console.log(' === SUBMENU DEBUG SCRIPT COMPLETADO ===');
