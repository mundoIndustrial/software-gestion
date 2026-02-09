/**
 * DEBUG-TELAS-EDICION.JS
 * Herramientas de depuración para problemas con telas en modo edición
 * 
 * USO: Abrir consola del navegador (F12) y ejecutar:
 * - window.debugTelaInputs()
 * - window.debugTelaButtons()
 * - window.debugTelaFunctionality()
 */

window.debugTelaInputs = function() {
    console.log('=== DEBUG: INPUTS DE TELAS ===');
    
    const inputs = {
        tela: document.getElementById('nueva-prenda-tela'),
        color: document.getElementById('nueva-prenda-color'),
        referencia: document.getElementById('nueva-prenda-referencia')
    };
    
    Object.keys(inputs).forEach(key => {
        const input = inputs[key];
        console.log(`\n${key.toUpperCase()}`);
        console.log('  Existe:', !!input);
        if (input) {
            console.log('  Visible:', input.offsetParent !== null);
            console.log('  Deshabilitado:', input.disabled);
            console.log('  Valor:', input.value);
            console.log('  Display:', window.getComputedStyle(input).display);
            console.log('  Visibility:', window.getComputedStyle(input).visibility);
            console.log('  Opacity:', window.getComputedStyle(input).opacity);
            console.log('  PointerEvents:', window.getComputedStyle(input).pointerEvents);
        }
    });
};

window.debugTelaButtons = function() {
    console.log('=== DEBUG: BOTONES DE TELAS ===');
    
    const tbody = document.getElementById('tbody-telas');
    if (!tbody) {
        console.error('tbody-telas NO ENCONTRADO');
        return;
    }
    
    const primeraFila = tbody.querySelector('tr:first-child');
    if (!primeraFila) {
        console.error('Primera fila de tabla NO ENCONTRADA');
        return;
    }
    
    const botones = primeraFila.querySelectorAll('button');
    console.log(`Botones encontrados: ${botones.length}`);
    
    botones.forEach((btn, idx) => {
        console.log(`\nBotón ${idx}`);
        console.log('  Texto:', btn.textContent.trim());
        console.log('  Deshabilitado:', btn.disabled);
        console.log('  Display:', window.getComputedStyle(btn).display);
        console.log('  Visible:', btn.offsetParent !== null);
        console.log('  Cursor:', window.getComputedStyle(btn).cursor);
        console.log('  OnClick:', btn.onclick ? 'SÍ' : 'NO');
    });
};

window.debugTelaFunctionality = function() {
    console.log('=== DEBUG: FUNCIONALIDAD DE TELAS ===');
    
    console.log('Variables globales:');
    console.log('  window.telasAgregadas:', window.telasAgregadas?.length || 0, 'telas');
    console.log('  window.telasCreacion:', window.telasCreacion?.length || 0, 'telas');
    console.log('  window.telasEdicion:', window.telasEdicion?.length || 0, 'telas');
    console.log('  window.prendaEditIndex:', window.prendaEditIndex);
    
    console.log('\nFunciones:');
    console.log('  agregarTelaNueva:', typeof window.agregarTelaNueva);
    console.log('  eliminarTela:', typeof window.eliminarTela);
    console.log('  actualizarTablaTelas:', typeof window.actualizarTablaTelas);
    
    console.log('\nIntentando agregar tela de prueba...');
    const form_tela = document.getElementById('nueva-prenda-tela');
    const form_color = document.getElementById('nueva-prenda-color');
    const form_ref = document.getElementById('nueva-prenda-referencia');
    
    if (form_tela && form_color) {
        form_tela.value = 'DRIL PRUEBA';
        form_color.value = 'AZUL PRUEBA';
        form_ref.value = 'REF-TEST';
        
        console.log('  Valores ingresados');
        console.log('  Llamando agregarTelaNueva()...');
        
        try {
            window.agregarTelaNueva();
            console.log('  ✓ agregarTelaNueva ejecutada sin errores');
        } catch (error) {
            console.error('  ✗ Error ejecutando agregarTelaNueva:', error);
        }
    } else {
        console.error('  ✗ Inputs de tela NO ENCONTRADOS');
    }
};

window.debugTelasCompleto = function() {
    console.log('=== DEBUG COMPLETO DE TELAS ===');
    window.debugTelaInputs();
    window.debugTelaButtons();
    window.debugTelaFunctionality();
};

console.log('[DEBUG-TELAS] Utilidades de depuración cargadas');
console.log('Ejecutar: window.debugTelasCompleto()');
