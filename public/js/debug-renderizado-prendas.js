/**
 * SCRIPT DE DEBUG PARA PROBLEMA DE RENDERIZADO DE PRENDAS
 * 
 * Copia y pega este script en la consola del navegador (F12 → Console)
 * para verificar que todo está funcionando correctamente
 */

// ============================================
//  VERIFICAR ESTADO ACTUAL
// ============================================
console.log(' ========== DEBUG RENDERIZADO PRENDAS ==========');

console.log(' Verificando componentes globales...');
console.log('   ✓ GestionItemsUI:', typeof window.gestionItemsUI !== 'undefined' ? '' : '');
console.log('   ✓ GestorPrendaSinCotizacion:', typeof window.gestorPrendaSinCotizacion !== 'undefined' ? '' : '');
console.log('   ✓ obtenerProcesosConfigurables:', typeof window.obtenerProcesosConfigurables === 'function' ? '' : '');
console.log('   ✓ renderizarPrendasTipoPrendaSinCotizacion:', typeof window.renderizarPrendasTipoPrendaSinCotizacion === 'function' ? '' : '');

// ============================================
//  REVISAR PROCESOS SELECCIONADOS
// ============================================
console.log('\n Procesos seleccionados actualmente:');
const procesos = window.procesosSeleccionados || {};
console.log('   Procesos:', Object.keys(procesos).length === 0 ? '[vacío]' : Object.keys(procesos));
console.log('   Estructura completa:', procesos);

// ============================================
//  REVISAR PRENDAS EN GESTOR
// ============================================
console.log('\n Prendas en el gestor:');
if (window.gestorPrendaSinCotizacion) {
    const todasLasPrendas = window.gestorPrendaSinCotizacion.prendas;
    const prendasActivas = window.gestorPrendaSinCotizacion.obtenerActivas();
    const prendasEliminadas = Array.from(window.gestorPrendaSinCotizacion.prendasEliminadas);
    
    console.log(`   Total prendas: ${todasLasPrendas.length}`);
    console.log(`   Prendas activas: ${prendasActivas.length}`);
    console.log(`   Prendas eliminadas: ${prendasEliminadas.length}`);
    
    if (prendasActivas.length > 0) {
        console.log('\n   Detalle de prendas activas:');
        prendasActivas.forEach((prenda, idx) => {
            console.log(`   Prenda ${idx}: "${prenda.nombre_producto}"`);
            console.log(`      - Géneros: ${Array.isArray(prenda.genero) ? prenda.genero.join(', ') : prenda.genero}`);
            console.log(`      - Procesos: ${Object.keys(prenda.procesos || {}).length > 0 ? Object.keys(prenda.procesos).join(', ') : '[ninguno]'}`);
            console.log(`      - Tallas: ${prenda.tallas?.length || 0}`);
            console.log(`      - Telas: ${prenda.telas?.length || 0}`);
        });
    }
}

// ============================================
// 4️⃣ REVISAR DOM
// ============================================
console.log('\n4️⃣ Verificar DOM renderizado:');
const container = document.getElementById('prendas-container-editable');
const prendaCards = document.querySelectorAll('.prenda-card-editable');
console.log(`   Container encontrado: ${container ? '' : ''}`);
console.log(`   Tarjetas renderizadas: ${prendaCards.length}`);

if (prendaCards.length > 0) {
    console.log('\n   Detalle de tarjetas:');
    prendaCards.forEach((card, idx) => {
        const title = card.querySelector('.prenda-title')?.textContent || 'Sin título';
        const tieneProcesos = card.innerHTML.includes('PROCESOS CONFIGURADOS');
        const tieneTelas = card.innerHTML.includes('Telas');
        console.log(`   Tarjeta ${idx}: "${title}"`);
        console.log(`      - ¿Tiene sección de procesos? ${tieneProcesos ? '' : ''}`);
        console.log(`      - ¿Tiene sección de telas? ${tieneTelas ? '' : ''}`);
    });
}

// ============================================
// 5️⃣ SIMULAR AGREGAR PRENDA (TEST)
// ============================================
console.log('\n5️⃣ Prueba: Simular agregar prenda con procesos');
console.log('   Instrucciones:');
console.log('   1. En el modal, selecciona un género (ej: Dama)');
console.log('   2. Marca el checkbox "Reflectivo"');
console.log('   3. Llena los detalles del reflectivo');
console.log('   4. Haz click en "Agregar Prenda"');
console.log('   5. Luego ejecuta en consola: debugVerificarUltimaPrenda()');

window.debugVerificarUltimaPrenda = function() {
    console.log('\n Verificando última prenda agregada...');
    
    if (!window.gestorPrendaSinCotizacion) {
        console.error(' GestorPrendaSinCotizacion no existe');
        return;
    }
    
    const prendas = window.gestorPrendaSinCotizacion.prendas;
    if (prendas.length === 0) {
        console.warn('  No hay prendas en el gestor');
        return;
    }
    
    const ultimaPrenda = prendas[prendas.length - 1];
    console.log('\n Datos de la última prenda:');
    console.log('   Nombre:', ultimaPrenda.nombre_producto);
    console.log('   Género(s):', ultimaPrenda.genero);
    console.log('   Procesos guardados:', Object.keys(ultimaPrenda.procesos || {}));
    console.log('   Estructura completa:', ultimaPrenda);
    
    // Verificar en DOM
    const ultimaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendas.length - 1}"]`);
    if (ultimaCard) {
        const tieneProcesosEnDOM = ultimaCard.innerHTML.includes('PROCESOS CONFIGURADOS');
        console.log('\n Verificación en DOM:');
        console.log('   ¿Tarjeta renderizada en DOM?', '');
        console.log('   ¿Contiene sección de procesos?', tieneProcesosEnDOM ? '' : '');
        
        if (tieneProcesosEnDOM) {
            const seccionProcesos = ultimaCard.querySelector('[style*="PROCESOS"]');
            console.log('   HTML de procesos encontrado:', seccionProcesos ? '' : '');
        }
    } else {
        console.error(' Tarjeta no encontrada en DOM');
    }
};

// ============================================
// 6️⃣ FUNCIÓN DE LIMPIEZA
// ============================================
window.debugLimpiarYReiniciar = function() {
    console.log('\n🧹 Limpiando estado...');
    
    // Limpiar procesos
    if (window.limpiarProcesosSeleccionados) {
        window.limpiarProcesosSeleccionados();
        console.log('    Procesos limpiados');
    }
    
    // Limpiar imágenes
    if (window.imagenesPrendaStorage) {
        window.imagenesPrendaStorage.limpiar();
        console.log('    Imágenes limpiadas');
    }
    
    // Cerrar modal
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        modal.style.display = 'none';
        console.log('    Modal cerrado');
    }
    
    console.log(' Estado limpiado. Listo para nueva prueba.');
};

// ============================================
// 7️⃣ INFORMACIÓN DE AYUDA
// ============================================
console.log('\n7️⃣ Funciones útiles disponibles en consola:');
console.log('   • debugVerificarUltimaPrenda() - Verifica la última prenda agregada');
console.log('   • debugLimpiarYReiniciar() - Limpia el estado actual');
console.log('   • window.procesosSeleccionados - Ver procesos seleccionados');
console.log('   • window.gestorPrendaSinCotizacion.prendas - Ver todas las prendas');

console.log('\n Debug iniciado. Revisa los logs arriba para el estado actual.');
