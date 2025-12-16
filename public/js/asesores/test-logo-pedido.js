// ============================================
// TEST: GUARDADO DE LOGO EN PEDIDO BORRADOR
// ============================================

console.log('🧪 INICIANDO TEST DE GUARDADO DE LOGO...\n');

// TEST 1: Verificar que window.imagenesEnMemoria está inicializado
console.log('Test 1: Verificar inicialización de imagenesEnMemoria');
if (window.imagenesEnMemoria) {
    console.log('✅ PASÓ - window.imagenesEnMemoria existe');
    console.log('   Logo:', window.imagenesEnMemoria.logo?.length || 0, 'imágenes');
    console.log('   Prenda:', window.imagenesEnMemoria.prenda?.length || 0, 'imágenes');
    console.log('   Tela:', window.imagenesEnMemoria.tela?.length || 0, 'imágenes');
} else {
    console.error('❌ FALLÓ - window.imagenesEnMemoria no está inicializado');
}

// TEST 2: Verificar que la función recopilarDatosLogo existe
console.log('\nTest 2: Verificar existencia de recopilarDatosLogo');
if (typeof recopilarDatosLogo === 'function') {
    console.log('✅ PASÓ - recopilarDatosLogo es una función');
    try {
        const datos = recopilarDatosLogo();
        console.log('   Datos recopilados:', {
            descripcion: datos.descripcion ? 'Sí' : 'No',
            tecnicas: datos.tecnicas?.length || 0,
            ubicaciones: datos.ubicaciones?.length || 0,
            imagenes: datos.imagenes?.length || 0
        });
    } catch (e) {
        console.warn('   ⚠️ Error al recopilar datos:', e.message);
    }
} else {
    console.error('❌ FALLÓ - recopilarDatosLogo no existe');
}

// TEST 3: Verificar que los campos HTML del logo existen
console.log('\nTest 3: Verificar campos HTML del logo');
const camposLogo = {
    descripcion: document.getElementById('descripcion_logo'),
    imagenes: document.getElementById('imagenes_bordado'),
    tecnicas: document.getElementById('tecnicas_seleccionadas'),
    ubicaciones: document.getElementById('secciones_agregadas'),
    observaciones: document.getElementById('observaciones_tecnicas'),
    galeriaImagenes: document.getElementById('galeria_imagenes')
};

const camposValidos = Object.entries(camposLogo).filter(([key, el]) => el !== null).length;
console.log(`✅ PASÓ - ${camposValidos}/${Object.keys(camposLogo).length} campos encontrados`);
Object.entries(camposLogo).forEach(([key, el]) => {
    console.log(`   ${el ? '✅' : '❌'} ${key}`);
});

// TEST 4: Verificar que la función guardarPedidoModal existe
console.log('\nTest 4: Verificar existencia de guardarPedidoModal');
if (typeof guardarPedidoModal === 'function') {
    console.log('✅ PASÓ - guardarPedidoModal es una función');
} else {
    console.error('❌ FALLÓ - guardarPedidoModal no existe');
}

// TEST 5: Verificar FormData support
console.log('\nTest 5: Verificar soporte de FormData');
if (typeof FormData !== 'undefined') {
    console.log('✅ PASÓ - FormData está disponible');
    try {
        const fd = new FormData();
        fd.append('logo[descripcion]', 'Test');
        console.log('   ✅ Puede agregar datos al FormData');
    } catch (e) {
        console.error('   ❌ Error al usar FormData:', e.message);
    }
} else {
    console.error('❌ FALLÓ - FormData no está disponible');
}

console.log('\n🧪 TEST COMPLETADO\n');
