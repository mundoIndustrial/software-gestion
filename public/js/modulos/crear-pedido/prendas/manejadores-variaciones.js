/**
 * manejadores-variaciones.js
 * 
 * Maneja los eventos de variaciones en prendas (manga, bolsillos, broche)
 * Habilita/deshabilita inputs según los checkboxes seleccionados
 */

// Manejar cambio de variaciones (manga, bolsillos, broche)
window.manejarCheckVariacion = function(checkbox) {
    const idCheckbox = checkbox.id;
    console.log('🔧 [VARIACIONES] manejarCheckVariacion llamado para:', idCheckbox);
    console.log('🔧 [VARIACIONES] Checkbox marcado:', checkbox.checked);
    
    let inputIds = [];
    
    if (idCheckbox === 'aplica-manga') inputIds = ['manga-input', 'manga-obs'];
    else if (idCheckbox === 'aplica-bolsillos') {
        inputIds = ['bolsillos-obs'];
        console.log('🔧 [BOLSILLOS] Habilitando solo observaciones:', inputIds);
    }
    else if (idCheckbox === 'aplica-broche') inputIds = ['broche-input', 'broche-obs'];
    
    inputIds.forEach(inputId => {
        const input = document.getElementById(inputId);
        console.log(`🔧 [VARIACIONES] Campo ${inputId} encontrado:`, !!input);
        if (input) {
            input.disabled = !checkbox.checked;
            input.style.opacity = checkbox.checked ? '1' : '0.5';
            console.log(` [VARIACIONES] Campo ${inputId} - disabled: ${input.disabled}, opacity: ${input.style.opacity}`);
        }
    });
};

console.log(' Módulo manejadores-variaciones.js cargado correctamente');
