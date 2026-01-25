/**
 * TEST: Verificar que PayloadNormalizer funciona correctamente
 * Simular el payload que recibe validarPedido() del formulario
 * y verificar que sea normalizado apropiadamente
 */

// Mock de datos corruptos (como vienen del form-collector)
const payloadCorrupto = {
    cliente: 1,
    asesora: 1,
    forma_de_pago: "efectivo",
    descripcion: "Test pedido",
    items: [{
        tipo: "prenda_nueva",
        nombre_prenda: "ETREt",
        descripcion: "prueba",
        origen: "bodega",
        cantidad_talla: {
            "DAMA": { "S": "20", "M": "20" },  // STRINGS (malo)
            "CABALLERO": {}
        },
        variaciones: { tipo_manga_id: 19 },
        telas: [{
            tela_id: 68,
            color_id: 36,
            tela_nombre: "Tela test",
            color_nombre: "Color test",
            referencia: "TET",
            imagenes: [[]]  // EMPTY NESTED ARRAYS (malo)
        }],
        procesos: {
            reflectivo: {
                tipo: "reflectivo",
                ubicaciones: ["location1", "location2"],
                observaciones: "notes",
                tallas: {
                    dama: { "S": "20", "M": "20" },  // STRINGS (malo)
                    caballero: []
                },
                imagenes: [[]]  // EMPTY NESTED ARRAYS (malo)
            }
        }
    }]
};

console.log('===============================================');
console.log('🧪 TEST: Normalización de Payload');
console.log('===============================================');

// STEP 1: Limpiar Files (aunque no hay en este test)
console.log('\n📝 PASO 1: Payload CORRUPTO (entrada)');
console.log(JSON.stringify(payloadCorrupto, null, 2));

// STEP 2: Limpiar
const payloadLimpio = PayloadNormalizer.limpiarFiles(payloadCorrupto);
console.log('\n🧹 PASO 2: Payload LIMPIO (después de limpiarFiles)');
console.log(JSON.stringify(payloadLimpio, null, 2));

// STEP 3: Normalizar
const payloadNormalizado = PayloadNormalizer.normalizar(payloadLimpio);
console.log('\n✨ PASO 3: Payload NORMALIZADO (después de normalizar)');
console.log(JSON.stringify(payloadNormalizado, null, 2));

// STEP 4: Validar
const jsonString = JSON.stringify(payloadNormalizado);
try {
    PayloadNormalizer.validarNoHayFiles(jsonString);
    console.log('\n✅ VALIDACIÓN: NO hay Files en el JSON');
} catch (e) {
    console.error('\n❌ VALIDACIÓN FALLIDA:', e.message);
}

// Verificaciones específicas
console.log('\n📊 VERIFICACIONES:');

const item = payloadNormalizado.items[0];
const tela = item.telas[0];
const proceso = item.procesos.reflectivo;

console.log('1. ¿Telas sin imagenes key?', !('imagenes' in tela) ? '✅ SÍ' : '❌ NO');
console.log('2. ¿Procesos sin imagenes key?', !('imagenes' in proceso) ? '✅ SÍ' : '❌ NO');
console.log('3. ¿Tallas son números?', 
    typeof item.cantidad_talla.DAMA.S === 'number' && 
    typeof proceso.tallas.dama.S === 'number' 
    ? '✅ SÍ' : '❌ NO');
console.log('4. ¿Valores de tallas correctos?',
    item.cantidad_talla.DAMA.S === 20 &&
    proceso.tallas.dama.S === 20
    ? '✅ SÍ' : '❌ NO');

console.log('\n✅ TEST COMPLETADO');
