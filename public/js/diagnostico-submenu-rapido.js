// Script de diagnóstico rápido para submenu
console.log(' === DIAGNÓSTICO SUBMENU INICIADO ===');

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(function() {
    console.log('\n INFORMACIÓN DEL SUBMENU:');
    
    // 1. Verificar si existen los elementos
    const submenus = document.querySelectorAll('.submenu');
    console.log(`✓ Total de submenus encontrados: ${submenus.length}`);
    
    if(submenus.length === 0) {
      console.error(' NO HAY SUBMENUS EN EL DOM');
      return;
    }
    
    // 2. Verificar el primer submenu
    const firstSubmenu = submenus[0];
    console.log('\n Primer submenu:');
    console.log('  - Clase HTML:', firstSubmenu.className);
    console.log('  - Tiene .open?', firstSubmenu.classList.contains('open'));
    console.log('  - Items dentro:', firstSubmenu.querySelectorAll('.submenu-item').length);
    
    // 3. Estilos computados
    const computed = window.getComputedStyle(firstSubmenu);
    console.log('\n Estilos computados:');
    console.log('  - max-height:', computed.maxHeight);
    console.log('  - opacity:', computed.opacity);
    console.log('  - overflow:', computed.overflow);
    console.log('  - display:', computed.display);
    console.log('  - visibility:', computed.visibility);
    console.log('  - position:', computed.position);
    
    // 4. Ancestros
    console.log('\n🌳 Jerarquía de ancestros:');
    let el = firstSubmenu.parentElement;
    let level = 1;
    while(el && level < 10) {
      const c = window.getComputedStyle(el);
      console.log(`  ${'└─'.repeat(level)} ${el.className || el.tagName}:`);
      console.log(`     overflow: ${c.overflow}, position: ${c.position}, z-index: ${c.zIndex}`);
      el = el.parentElement;
      level++;
    }
    
    // 5. Probar el click
    console.log('\n🧪 PRUEBA DE CLICK:');
    const toggle = document.querySelector('.submenu-toggle');
    if(toggle) {
      console.log('Haciendo click en el toggle...');
      toggle.click();
      
      setTimeout(function() {
        const submenuAfter = document.querySelector('.submenu');
        const computedAfter = window.getComputedStyle(submenuAfter);
        console.log('\n✓ Después del click:');
        console.log('  - Tiene .open?', submenuAfter.classList.contains('open'));
        console.log('  - max-height:', computedAfter.maxHeight);
        console.log('  - opacity:', computedAfter.opacity);
      }, 100);
    }
    
  }, 500);
});
