// 🔍 SCRIPT DE DIAGNÓSTICO - FOTOS DE TELAS NO SE GUARDAN
// Copia TODO este código y pégalo en la CONSOLA del navegador (F12 → Console)
// Luego carga una foto en el formulario y verás los resultados

console.log('🚀 INICIANDO DIAGNÓSTICO DE FOTOS DE TELAS...\n');

// Paso 1: Verificar que las funciones existen
console.log('📋 PASO 1: Verificar disponibilidad de funciones');
console.log('✅ agregarFotoTela disponible:', typeof agregarFotoTela === 'function' ? 'SÍ' : 'NO');
console.log('✅ agregarFilaTela disponible:', typeof agregarFilaTela === 'function' ? 'SÍ' : 'NO');
console.log('✅ mostrarPreviewFoto disponible:', typeof mostrarPreviewFoto === 'function' ? 'SÍ' : 'NO');
console.log('');

// Paso 2: Verificar estructura global
console.log('📋 PASO 2: Verificar estructura global de datos');
console.log('✅ window.fotosSeleccionadas:', window.fotosSeleccionadas);
console.log('✅ window.telasSeleccionadas:', window.telasSeleccionadas);
console.log('');

// Paso 3: Contar elementos del DOM
console.log('📋 PASO 3: Verificar estructura del DOM');
const productCards = document.querySelectorAll('.producto-card');
console.log(`✅ Productos encontrados: ${productCards.length}`);

productCards.forEach((card, idx) => {
    const productoId = card.dataset.productoId;
    const filasTelas = card.querySelectorAll('.fila-tela');
    const inputsArchivos = card.querySelectorAll('input[type="file"][name*="telas"]');
    
    console.log(`   Prenda ${idx + 1} (ID: ${productoId})`);
    console.log(`   - Filas de telas: ${filasTelas.length}`);
    console.log(`   - Inputs de archivo para telas: ${inputsArchivos.length}`);
    
    filasTelas.forEach((fila, filaIdx) => {
        const telaIndex = fila.getAttribute('data-tela-index');
        console.log(`     • Fila ${filaIdx}: data-tela-index="${telaIndex}"`);
    });
    
    console.log('');
});

// Paso 4: Crear archivo de prueba
console.log('📋 PASO 4: Crear archivo de prueba');
const testFile = new File(['test'], 'test.txt', { type: 'text/plain' });
console.log(`✅ Archivo de prueba creado: ${testFile.name}`);
console.log('');

// Paso 5: Simular carga de foto (opcional)
console.log('📋 PASO 5: Test manual - Instrucciones');
console.log('Para probar:');
console.log('1. Abre la tabla "COLOR, TELA Y REFERENCIA"');
console.log('2. Haz clic en "CLIC" en la celda de "Imagen Tela"');
console.log('3. Selecciona una foto');
console.log('4. Observa los mensajes que aparecen ABAJO');
console.log('5. Deberías ver: "🔥 agregarFotoTela LLAMADA:"');
console.log('');

// Paso 6: Crear monitor en tiempo real
console.log('📋 PASO 6: Activando monitor en tiempo real...');
console.log('Cada vez que cargues una foto, verás los cambios en telasSeleccionadas');
console.log('');

// Función para monitorear cambios
let previousState = JSON.stringify(window.telasSeleccionadas);
setInterval(() => {
    const currentState = JSON.stringify(window.telasSeleccionadas);
    if (currentState !== previousState) {
        console.log('🔔 CAMBIO DETECTADO en telasSeleccionadas:');
        console.log(window.telasSeleccionadas);
        previousState = currentState;
    }
}, 500);

console.log('✅ Monitor activado. Cada 500ms verificará cambios.');
console.log('');
console.log('====================================================');
console.log('✅ DIAGNÓSTICO COMPLETADO');
console.log('====================================================');
console.log('');
console.log('📝 Ahora CARGA UNA FOTO y observa lo que sucede...');
