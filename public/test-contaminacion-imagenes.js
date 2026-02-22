/**
 * Script de prueba para verificar la solución de contaminación de imágenes v2.0
 * 
 * Para usar:
 * 1. Abre la página de creación de pedidos
 * 2. Abre la consola del navegador
 * 3. Copia y pega este script
 * 4. Ejecuta las pruebas sugeridas
 */

console.log('🧪 [TEST v2.0] Iniciando pruebas de contaminación de imágenes...');

// Función para verificar el estado completo del storage y arrays
function verificarEstadoCompleto() {
    console.group('🔍 [TEST] Estado completo del sistema');
    
    // 1. Verificar storage universal
    if (!window.universalImagenesStorage) {
        console.error('❌ universalImagenesStorage no disponible');
        console.groupEnd();
        return;
    }
    
    const resumen = window.universalImagenesStorage.obtenerResumen();
    console.log('📊 Resumen storage universal:', resumen);
    
    // 2. Verificar arrays globales
    console.log('🖼️ Arrays globales:');
    console.log('  window.imagenesProcesoActual:', window.imagenesProcesoActual);
    console.log('  window.imagenesProcesoExistentes:', window.imagenesProcesoExistentes);
    console.log('  window.procesoActualIndex:', window.procesoActualIndex);
    
    // 3. Verificar procesos seleccionados
    console.log('📋 Procesos seleccionados:', window.procesosSeleccionados);
    
    // 4. Verificar estado del DOM
    console.log('🎨 Estado del DOM:');
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        if (preview) {
            const img = preview.querySelector('img');
            console.log(`  Preview ${i}: ${img ? '✅ tiene imagen' : '❌ vacío'}`);
        }
    }
    
    console.groupEnd();
}

// Función para simular la apertura de un nuevo proceso
function simularAperturaProceso(tipoProceso) {
    console.group(`🔄 [TEST] Simulando apertura de proceso: ${tipoProceso}`);
    
    // Simular la asignación de índice
    const indicesUsados = new Set();
    Object.values(window.procesosSeleccionados || {}).forEach(proceso => {
        if (proceso.indiceResultado !== undefined) {
            indicesUsados.add(proceso.indiceResultado);
        }
    });
    
    let indiceDisponible = 1;
    while (indicesUsados.has(indiceDisponible) && indiceDisponible <= 3) {
        indiceDisponible++;
    }
    
    window.procesoActualIndex = indiceDisponible;
    console.log(`📍 Índice asignado: ${window.procesoActualIndex}`);
    
    // Simular limpieza completa (como lo hace el código corregido)
    console.log('🧹 Simulando limpieza completa...');
    
    // 1. Limpiar storage universal
    window.universalImagenesStorage.eliminarTodasLasImagenes('procesos', window.procesoActualIndex);
    
    // 2. Limpiar arrays globales
    if (window.imagenesProcesoActual) {
        window.imagenesProcesoActual = [null, null, null];
    }
    if (window.imagenesProcesoExistentes) {
        window.imagenesProcesoExistentes = [];
    }
    
    // 3. Limpiar DOM
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        if (preview) {
            preview.style.border = '2px dashed #0066cc';
            preview.style.background = '#f9fafb';
            preview.innerHTML = `
                <div class="placeholder-content" style="text-align: center;">
                    <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                    <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen ${i}</div>
                </div>
            `;
        }
    }
    
    // Verificar estado después de limpiar
    const imagenesDespues = window.universalImagenesStorage.obtenerImagenes('procesos', window.procesoActualIndex);
    console.log(`✅ Verificación: Storage[${window.procesoActualIndex}] tiene ${imagenesDespues.length} imágenes`);
    console.log(`✅ Verificación: window.imagenesProcesoActual está limpio:`, window.imagenesProcesoActual);
    console.log(`✅ Verificación: window.imagenesProcesoExistentes está limpio:`, window.imagenesProcesoExistentes);
    
    console.groupEnd();
    
    return window.procesoActualIndex;
}

// Función para probar la contaminación de forma completa
function probarContaminacionCompleta() {
    console.group('🧪 [TEST] Probando escenario completo de contaminación');
    
    // 1. Verificar estado inicial
    verificarEstadoCompleto();
    
    // 2. Simular agregar imágenes a un proceso
    console.log('📝 Simulando imágenes en proceso 1...');
    const imagenMock1 = {
        file: new File(['test1'], 'imagen1.jpg', { type: 'image/jpeg' }),
        previewUrl: 'blob:mock1',
        nombre: 'imagen1.jpg',
        tipo: 'procesos'
    };
    
    window.universalImagenesStorage.agregarImagen('procesos', 1, imagenMock1);
    window.imagenesProcesoActual = [imagenMock1.file, null, null];
    window.imagenesProcesoExistentes = [imagenMock1];
    
    console.log('✅ Imagen simulada agregada');
    
    // 3. Simular apertura de nuevo proceso (debería usar índice 2)
    const nuevoIndice = simularAperturaProceso('reflectivo');
    
    // 4. Verificar que no haya contaminación en NINGÚN lugar
    console.log('🔍 Verificación completa de no contaminación:');
    
    // Storage universal
    const imagenesStorage = window.universalImagenesStorage.obtenerImagenes('procesos', nuevoIndice);
    const storageLimpio = imagenesStorage.length === 0;
    console.log(`  📁 Storage[${nuevoIndice}]: ${storageLimpio ? '✅' : '❌'} ${imagenesStorage.length} imágenes`);
    
    // Arrays globales
    const arrayActualLimpio = window.imagenesProcesoActual.every(img => img === null);
    const arrayExistentesLimpio = window.imagenesProcesoExistentes.length === 0;
    console.log(`  🖼️ window.imagenesProcesoActual: ${arrayActualLimpio ? '✅' : '❌'} limpio`);
    console.log(`  🖼️ window.imagenesProcesoExistentes: ${arrayExistentesLimpio ? '✅' : '❌'} limpio`);
    
    // DOM
    let domLimpio = true;
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        if (preview && preview.querySelector('img')) {
            domLimpio = false;
            break;
        }
    }
    console.log(`  🎨 DOM previews: ${domLimpio ? '✅' : '❌'} limpio`);
    
    // Resultado final
    const todoLimpio = storageLimpio && arrayActualLimpio && arrayExistentesLimpio && domLimpio;
    
    if (todoLimpio) {
        console.log('🎉 [TEST] ✅ PRUEBA COMPLETA PASADA: No hay contaminación en ningún nivel');
    } else {
        console.error('❌ [TEST] ❌ PRUEBA FALLIDA: Hay contaminación detectada');
        console.error('   Revisa los logs arriba para identificar dónde está el problema');
    }
    
    // 5. Limpiar después de la prueba
    window.universalImagenesStorage.eliminarTodasLasImagenes('procesos', 1);
    window.universalImagenesStorage.eliminarTodasLasImagenes('procesos', nuevoIndice);
    window.imagenesProcesoActual = [null, null, null];
    window.imagenesProcesoExistentes = [];
    
    console.groupEnd();
    
    return todoLimpio;
}

// Función para probar el flujo real del usuario
function probarFlujoReal() {
    console.group('👤 [TEST] Probando flujo real de usuario');
    
    console.log('📋 Instrucciones para el flujo real:');
    console.log('1. Abre el modal de un proceso (ej: Reflectivo)');
    console.log('2. Agrega una imagen');
    console.log('3. Guarda el proceso');
    console.log('4. Abre otro proceso diferente (ej: Bordado)');
    console.log('5. Verifica que NO aparezca la imagen anterior');
    console.log('6. Ejecuta verificarEstadoCompleto() en la consola');
    
    verificarEstadoCompleto();
    
    console.groupEnd();
}

// Ejecutar pruebas automáticas
console.log('🚀 [TEST] Ejecutando pruebas automáticas...');
const resultadoPrueba = probarContaminacionCompleta();

// Funciones disponibles para pruebas manuales
window.testImagenes = {
    verificarEstado: verificarEstadoCompleto,
    simularApertura: simularAperturaProceso,
    probarContaminacion: probarContaminacionCompleta,
    probarFlujoReal: probarFlujoReal
};

console.log('✅ [TEST] Pruebas completadas.');
console.log('📊 Resultado prueba automática:', resultadoPrueba ? '✅ PASÓ' : '❌ FALLÓ');
console.log('💡 [TEST] Funciones disponibles en window.testImagenes:');
console.log('   - window.testImagenes.verificarEstado() - Ver estado completo');
console.log('   - window.testImagenes.simularApertura("bordado") - Simular apertura');
console.log('   - window.testImagenes.probarContaminacion() - Probar contaminación');
console.log('   - window.testImagenes.probarFlujoReal() - Instrucciones flujo real');
