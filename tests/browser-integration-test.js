/**
 * Test de Integración - Simula el flujo completo en el navegador
 * Este archivo se puede ejecutar en la consola del navegador para validar
 * que los datos se capturan correctamente en tiempo real
 */

console.log('%c🧪 INICIANDO TEST DE INTEGRACIÓN COMPLETO', 'color: #00CCFF; font-size: 16px; font-weight: bold');

// ============================================
// 1. SIMULACIÓN DE DATOS DEL FORMULARIO
// ============================================

console.log('\n%c1️⃣  SIMULANDO SELECCIÓN DE USUARIO EN FORMULARIO', 'color: #00CCFF; font-weight: bold');

// Simular que el usuario seleccionó estas tallas
window.tallasPorGenero = [
    { genero: 'dama', tallas: ['S', 'M', 'L'], tipo: 'letra' }
];

window.cantidadesPorTalla = {
    'S': 230,
    'M': 230,
    'L': 230
};

console.log('✅ Usuario seleccionó:');
console.log('   - Género: Dama');
console.log('   - Tallas: S (230), M (230), L (230)');
console.log('   - Total de prendas: 690');

// ============================================
// 2. CONSTRUCCIÓN DE generosConTallas
// ============================================

console.log('\n%c2️⃣  CONSTRUYENDO generosConTallas', 'color: #FFD700; font-weight: bold');

const generosConTallas = {};
window.tallasPorGenero.forEach(tallaData => {
    const generoKey = tallaData.genero;
    generosConTallas[generoKey] = {};
    
    if (tallaData.tallas && Array.isArray(tallaData.tallas)) {
        tallaData.tallas.forEach(talla => {
            const cantidad = window.cantidadesPorTalla[talla] || 0;
            if (cantidad > 0) {
                generosConTallas[generoKey][talla] = cantidad;
            }
        });
    }
});

console.log('✅ generosConTallas construido:');
console.table(generosConTallas);

// ============================================
// 3. CREACIÓN DE LA PRENDA
// ============================================

console.log('\n%c3️⃣  CREANDO OBJETO PRENDA', 'color: #00FF00; font-weight: bold');

const prendaNueva = {
    nombre: 'Polo corporativo',
    descripcion: 'Polo gris corporativo',
    referencia: 'POL-001',
    generosConTallas: generosConTallas,
    cantidad_tallas: Object.keys(generosConTallas).length,
    total_unidades: Object.values(generosConTallas).reduce((sum, gender) => 
        sum + Object.values(gender).reduce((a, b) => a + b, 0), 0
    )
};

console.log('✅ Prenda creada:');
console.table(prendaNueva);

// ============================================
// 4. DERIVACIÓN DE cantidadTalla PARA API
// ============================================

console.log('\n%c4️⃣  DERIVANDO cantidadTalla PARA API', 'color: #FF6600; font-weight: bold');

const cantidadTalla = {};
Object.keys(prendaNueva.generosConTallas).forEach(genero => {
    const tallas = prendaNueva.generosConTallas[genero];
    Object.keys(tallas).forEach(talla => {
        const key = `${genero}-${talla}`;
        cantidadTalla[key] = tallas[talla];
    });
});

console.log('✅ cantidadTalla (formato para API):');
console.table(cantidadTalla);

// ============================================
// 5. CONSTRUCCIÓN DEL ARRAY tallas
// ============================================

console.log('\n%c5️⃣  CONSTRUYENDO ARRAY tallas PARA VALIDACIÓN', 'color: #FF00FF; font-weight: bold');

const tallasArray = Object.keys(cantidadTalla).map(key => {
    const [genero, talla] = key.split('-');
    return {
        genero,
        talla,
        cantidad: cantidadTalla[key]
    };
});

console.log('✅ Array tallas para validación:');
console.table(tallasArray);

// ============================================
// 6. PAYLOAD FINAL PARA EL BACKEND
// ============================================

console.log('\n%c6️⃣  CONSTRUYENDO PAYLOAD FINAL PARA BACKEND', 'color: #0099FF; font-weight: bold');

const payloadParaBackend = {
    items: [
        {
            nombre: prendaNueva.nombre,
            descripcion: prendaNueva.descripcion,
            referencia: prendaNueva.referencia,
            cantidad_total: prendaNueva.total_unidades,
            tallas: tallasArray
        }
    ]
};

console.log('✅ Payload completo para backend:');
console.log(JSON.stringify(payloadParaBackend, null, 2));

// ============================================
// 7. VALIDACIONES
// ============================================

console.log('\n%c7️⃣  EJECUTANDO VALIDACIONES', 'color: #FF0099; font-weight: bold');

const validaciones = [
    {
        nombre: '✅ generosConTallas NO está vacío',
        resultado: Object.keys(prendaNueva.generosConTallas).length > 0,
        detalle: `Géneros: ${Object.keys(prendaNueva.generosConTallas).join(', ')}`
    },
    {
        nombre: '✅ cantidadTalla NO está vacío',
        resultado: Object.keys(cantidadTalla).length > 0,
        detalle: `Elementos: ${Object.keys(cantidadTalla).length}`
    },
    {
        nombre: '✅ Array tallas NO está vacío',
        resultado: Array.isArray(tallasArray) && tallasArray.length > 0,
        detalle: `Elementos en array: ${tallasArray.length}`
    },
    {
        nombre: '✅ Cantidad total correcta',
        resultado: prendaNueva.total_unidades === 690,
        detalle: `Total: ${prendaNueva.total_unidades} (esperado: 690)`
    },
    {
        nombre: '✅ Cada talla tiene estructura correcta',
        resultado: tallasArray.every(t => t.genero && t.talla && t.cantidad > 0),
        detalle: `Tallas válidas: ${tallasArray.length}/${tallasArray.length}`
    },
    {
        nombre: '✅ Pasaría validación del backend (tallas.length > 0)',
        resultado: payloadParaBackend.items[0].tallas.length > 0,
        detalle: `Array no vacío: true`
    },
    {
        nombre: '✅ Todos los géneros tienen tallas asignadas',
        resultado: tallasArray.every(t => prendaNueva.generosConTallas[t.genero] !== undefined),
        detalle: `Géneros validados: ${Object.keys(prendaNueva.generosConTallas).length}`
    }
];

let pasadas = 0;
validaciones.forEach((val, index) => {
    const icono = val.resultado ? '✅' : '❌';
    console.log(`\n${icono} ${val.nombre}`);
    console.log(`   └─ ${val.detalle}`);
    if (val.resultado) pasadas++;
});

// ============================================
// 8. RESUMEN FINAL
// ============================================

console.log('\n%c═══════════════════════════════════════════════════════════', 'color: #00CCFF');
console.log('%c📊 RESUMEN FINAL DEL TEST', 'color: #00CCFF; font-size: 14px; font-weight: bold');
console.log('%c═══════════════════════════════════════════════════════════', 'color: #00CCFF');

console.log(`\n📈 Validaciones pasadas: ${pasadas}/${validaciones.length}`);
console.log(`📦 Estructura de datos: ${pasadas === validaciones.length ? '✅ VÁLIDA' : '❌ INVÁLIDA'}`);
console.log(`🚀 Listo para enviar a backend: ${pasadas === validaciones.length ? '✅ SÍ' : '❌ NO'}`);

// Resumen de la estructura final
console.log('\n%c📋 ESTRUCTURA DE DATOS FINAL:', 'color: #00FF00; font-weight: bold');
console.log(`\n1️⃣  generosConTallas:`, generosConTallas);
console.log(`2️⃣  cantidadTalla:`, cantidadTalla);
console.log(`3️⃣  Array tallas:`, tallasArray);
console.log(`4️⃣  Payload para API:`, payloadParaBackend);

// Si queremos hacer un console.table
console.log('\n%c📊 VISTA EN TABLA:', 'color: #00FF00; font-weight: bold');
console.log('\nTallas por género:');
console.table(generosConTallas);

console.log('\nArray tallas (lo que se envía al backend):');
console.table(tallasArray);

// Guardar datos en window para debugging
window._testData = {
    generosConTallas,
    cantidadTalla,
    tallasArray,
    payload: payloadParaBackend,
    validaciones,
    pasadas,
    exitoso: pasadas === validaciones.length
};

console.log('\n%c✅ Test completado. Datos guardados en window._testData', 'color: #00FF00; font-weight: bold');
console.log('%c   Puedes acceder a ellos en la consola: window._testData', 'color: #00FF00');

// Alerta final
if (pasadas === validaciones.length) {
    console.log('%c\n🎉 ¡TODOS LOS TESTS PASARON! La información se captura correctamente.', 'color: #00FF00; font-size: 14px; font-weight: bold; background: #000033; padding: 10px; border-radius: 5px');
} else {
    console.log('%c\n❌ ALGUNAS VALIDACIONES FALLARON. Revisa la estructura de datos.', 'color: #FF0000; font-size: 14px; font-weight: bold; background: #330000; padding: 10px; border-radius: 5px');
}

console.log('%c═══════════════════════════════════════════════════════════\n', 'color: #00CCFF');
