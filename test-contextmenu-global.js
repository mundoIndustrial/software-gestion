// Verificar si el contextmenu funciona en la página
function verificarContextmenuGlobal() {
    console.log('[GLOBAL] 🧪 Verificando contextmenu global...');
    
    // Crear un elemento de prueba simple
    const testDiv = document.createElement('div');
    testDiv.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        background: blue;
        color: white;
        padding: 10px;
        border-radius: 5px;
        z-index: 999999;
        cursor: pointer;
        font-size: 12px;
    `;
    testDiv.innerHTML = '🧪 Prueba Contextmenu (haz clic derecho)';
    
    // Agregar al DOM
    document.body.appendChild(testDiv);
    console.log('[GLOBAL] ✅ Elemento de prueba agregado');
    
    // Agregar evento contextmenu
    testDiv.addEventListener('contextmenu', function(e) {
        console.log('[GLOBAL] 🎉 ¡Contextmenu detectado en elemento de prueba!');
        e.preventDefault();
        e.stopPropagation();
        alert('¡Contextmenu funciona en elemento de prueba!');
    });
    
    console.log('[GLOBAL] ✅ Evento contextmenu agregado');
    console.log('[GLOBAL] 🏁 Haz clic derecho en el elemento azul arriba a la derecha');
}

// También verificar si hay algún CSS que bloquee contextmenu
function verificarCSS() {
    console.log('[CSS] 🔍 Verificando CSS que pueda bloquear contextmenu...');
    
    const allElements = document.querySelectorAll('*');
    let blockedElements = [];
    
    allElements.forEach(el => {
        const styles = window.getComputedStyle(el);
        if (styles.pointerEvents === 'none' || styles.userSelect === 'none') {
            blockedElements.push({
                element: el,
                tagName: el.tagName,
                className: el.className,
                id: el.id,
                pointerEvents: styles.pointerEvents,
                userSelect: styles.userSelect
            });
        }
    });
    
    console.log('[CSS] 📋 Elementos con eventos bloqueados:', blockedElements.length);
    
    if (blockedElements.length > 0) {
        console.log('[CSS] ⚠️ Posibles elementos bloqueando eventos:', blockedElements.slice(0, 5));
    }
}

// Ejecutar ambas pruebas
verificarContextmenuGlobal();
verificarCSS();
