/**
 * Ejemplo de uso del PayloadSanitizer
 * 
 * Demuestra cómo sanitizar datos ANTES de enviar a Laravel
 * Resuelve problemas de:
 * - Propiedades reactivas (Vue Proxy, __v_isRef, etc.)
 * - Booleanos como strings ("true" vs true)
 * - Referencias circulares
 * - Arrays cuando se esperan objetos
 * - "Over 9 levels deep, aborting normalization"
 */

// ==================== EJEMPLO 1: SANITIZAR VARIACIONES ====================

console.log('🔧 Ejemplo 1: Sanitizar Variaciones');

// ANTES: Objeto sucio con propiedades reactivas de Vue
const variacionesSucias = {
    tipo_manga: "LARGA",
    obs_manga: "observación con espacios  ",
    tiene_bolsillos: "true",           // ❌ String en lugar de boolean
    obs_bolsillos: null,
    tipo_broche: "boton",
    tipo_broche_boton_id: "2",         // ❌ String en lugar de number
    obs_broche: "",                     // ❌ String vacío
    tiene_reflectivo: false,
    obs_reflectivo: null,
    __v_isRef: true,                    // ❌ Propiedad reactiva de Vue
    __v_isReactive: true,               // ❌ Propiedad reactiva de Vue
    _value: {/* ... */},                // ❌ Propiedad interna
};

// DESPUÉS: Objeto limpio para Laravel
const variacionesLimpias = PayloadSanitizer.sanitizarVariaciones(variacionesSucias);

console.log('✅ Variaciones sanitizadas:');
console.log(JSON.stringify(variacionesLimpias, null, 2));
/* Resultado:
{
  "tipo_manga": "LARGA",
  "obs_manga": "observación con espacios",
  "obs_bolsillos": null,
  "tipo_broche": "boton",
  "obs_broche": null,
  "obs_reflectivo": null,
  "tiene_bolsillos": true,        // ✅ Boolean real
  "tiene_reflectivo": false,      // ✅ Boolean real
  "tipo_broche_boton_id": 2       // ✅ Number real
}
*/

// ==================== EJEMPLO 2: SANITIZAR ITEM COMPLETO ====================

console.log('\n🔧 Ejemplo 2: Sanitizar Item Completo');

// ANTES: Item sucio desde formulario reactivo
const itemSucio = {
    tipo: "prenda_nueva",
    nombre_prenda: "Camisa Corporativa",
    descripcion: "Camisa manga larga",
    origen: "bodega",
    
    cantidad_talla: {
        DAMA: {
            S: "10",    // ❌ String
            M: "20",    // ❌ String
            __ob__: {/* Observer */}  // ❌ Vue Observer
        },
        CABALLERO: {
            M: 15,
            L: "25"     // ❌ String
        },
        _reactive: {/* ... */}  // ❌ Propiedad reactiva
    },
    
    variaciones: {
        tipo_manga: "larga",
        tiene_bolsillos: "1",  // ❌ String "1" en lugar de true
        tipo_broche: "boton",
        __v_isRef: true        // ❌ Vue reactivity
    },
    
    procesos: {
        reflectivo: {
            tipo: "reflectivo",
            datos: {
                ubicaciones: ["PECHO", "ESPALDA"],
                observaciones: "Logo reflectivo"
            }
        }
    },
    
    telas: [
        {
            tela: "DRILL",
            color: "AZUL",
            referencia: "DR-001",
            imagenes: [[]]  // ❌ Array anidado vacío
        }
    ],
    
    imagenes: [[]],  // ❌ Array anidado vacío
    
    __v_isReactive: true,  // ❌ Propiedad reactiva
};

// DESPUÉS: Item limpio
const itemLimpio = PayloadSanitizer.sanitizarItem(itemSucio);

console.log('✅ Item sanitizado:');
console.log(JSON.stringify(itemLimpio, null, 2));

// ==================== EJEMPLO 3: SANITIZAR PEDIDO COMPLETO ====================

console.log('\n🔧 Ejemplo 3: Sanitizar Pedido Completo');

// ANTES: Pedido sucio desde formulario Vue/React
const pedidoSucio = {
    cliente: "EMPRESA XYZ",
    asesora: "yus2",
    forma_de_pago: "CREDITO",
    descripcion: "Pedido corporativo",
    
    items: [
        {
            tipo: "prenda_nueva",
            nombre_prenda: "Camisa",
            variaciones: {
                tipo_manga: "larga",
                tiene_bolsillos: "true",  // ❌ String
                tipo_broche_boton_id: "2"  // ❌ String
            },
            cantidad_talla: {
                DAMA: { S: "10", M: "20" },  // ❌ Strings
                CABALLERO: []
            }
        },
        {
            tipo: "prenda_nueva",
            nombre_prenda: "Pantalón",
            variaciones: {
                tiene_bolsillos: true,
                tipo_broche: "cremallera"
            },
            cantidad_talla: {
                CABALLERO: { M: 15, L: 25 }
            }
        }
    ],
    
    __v_isReactive: true,  // ❌ Vue reactivity
    _meta: {/* ... */}      // ❌ Metadata interna
};

// DESPUÉS: Pedido limpio
const pedidoLimpio = PayloadSanitizer.sanitizarPedido(pedidoSucio);

console.log('✅ Pedido sanitizado:');
console.log(JSON.stringify(pedidoLimpio, null, 2));

// ==================== EJEMPLO 4: USO CON FETCH ====================

console.log('\n🔧 Ejemplo 4: Uso con Fetch API');

async function crearPedido(pedidoFormulario) {
    try {
        // 1. Sanitizar payload ANTES de enviar
        const payloadLimpio = PayloadSanitizer.sanitizarPedido(pedidoFormulario);
        
        // 2. Validar antes de enviar (opcional pero recomendado)
        const validacion = PayloadSanitizer.validarPayload(payloadLimpio);
        if (!validacion.valido) {
            console.error('❌ Payload inválido:', validacion.errores);
            return {
                success: false,
                errors: validacion.errores
            };
        }
        
        // 3. Debug en desarrollo (opcional)
        if (process.env.NODE_ENV === 'development') {
            PayloadSanitizer.debug(pedidoFormulario, payloadLimpio);
        }
        
        // 4. Enviar a Laravel
        const response = await fetch('/api/pedidos-editable/crear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify(payloadLimpio)  // ✅ Payload limpio
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al crear pedido');
        }
        
        const resultado = await response.json();
        console.log('✅ Pedido creado:', resultado);
        
        return resultado;
        
    } catch (error) {
        console.error('❌ Error al crear pedido:', error);
        throw error;
    }
}

// ==================== EJEMPLO 5: USO CON AXIOS ====================

console.log('\n🔧 Ejemplo 5: Uso con Axios');

async function crearPedidoConAxios(pedidoFormulario) {
    try {
        // Sanitizar
        const payloadLimpio = PayloadSanitizer.sanitizarPedido(pedidoFormulario);
        
        // Validar
        const { valido, errores } = PayloadSanitizer.validarPayload(payloadLimpio);
        if (!valido) {
            throw new Error(`Validación fallida: ${errores.join(', ')}`);
        }
        
        // Enviar
        const response = await axios.post('/api/pedidos-editable/crear', payloadLimpio);
        
        console.log('✅ Pedido creado:', response.data);
        return response.data;
        
    } catch (error) {
        if (error.response?.status === 422) {
            console.error('❌ Validación Laravel fallida:', error.response.data.errors);
        } else {
            console.error('❌ Error:', error.message);
        }
        throw error;
    }
}

// ==================== EJEMPLO 6: INTEGRACIÓN CON VUE 3 ====================

console.log('\n🔧 Ejemplo 6: Integración con Vue 3 Composition API');

// En tu componente Vue
const ejemploVue3 = `
<script setup>
import { ref, reactive } from 'vue';
import PayloadSanitizer from '@/utils/payload-sanitizer';

const pedido = reactive({
    cliente: '',
    forma_de_pago: 'CONTADO',
    items: []
});

const isSubmitting = ref(false);
const error = ref(null);

async function enviarPedido() {
    isSubmitting.value = true;
    error.value = null;
    
    try {
        // ✅ Sanitizar ANTES de enviar (elimina Proxy reactivos de Vue)
        const payloadLimpio = PayloadSanitizer.sanitizarPedido(pedido);
        
        // Validar
        const { valido, errores } = PayloadSanitizer.validarPayload(payloadLimpio);
        if (!valido) {
            error.value = errores.join(', ');
            return;
        }
        
        // Enviar
        const response = await fetch('/api/pedidos-editable/crear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payloadLimpio)
        });
        
        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message);
        }
        
        const resultado = await response.json();
        console.log('✅ Pedido creado:', resultado);
        
        // Redirigir o mostrar success
        alert('Pedido creado exitosamente');
        
    } catch (err) {
        error.value = err.message;
        console.error('❌ Error:', err);
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <form @submit.prevent="enviarPedido">
        <!-- Tu formulario aquí -->
        <button type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'Creando...' : 'Crear Pedido' }}
        </button>
        <p v-if="error" class="error">{{ error }}</p>
    </form>
</template>
`;

console.log(ejemploVue3);

// ==================== EJEMPLO 7: TESTING ====================

console.log('\n🔧 Ejemplo 7: Testing del Sanitizador');

function testSanitizador() {
    console.group('🧪 Tests del PayloadSanitizer');
    
    // Test 1: Eliminar propiedades reactivas
    console.log('\n✅ Test 1: Eliminar propiedades reactivas');
    const conReactivos = { nombre: "Test", __v_isRef: true, _value: {} };
    const sinReactivos = PayloadSanitizer.clonarProfundo(conReactivos);
    console.assert(!sinReactivos.__v_isRef, 'Debería eliminar __v_isRef');
    console.assert(!sinReactivos._value, 'Debería eliminar _value');
    console.log('Antes:', Object.keys(conReactivos));
    console.log('Después:', Object.keys(sinReactivos));
    
    // Test 2: Convertir booleanos
    console.log('\n✅ Test 2: Convertir booleanos');
    console.assert(PayloadSanitizer.convertirBoolean("true") === true, '"true" -> true');
    console.assert(PayloadSanitizer.convertirBoolean("false") === false, '"false" -> false');
    console.assert(PayloadSanitizer.convertirBoolean("1") === true, '"1" -> true');
    console.assert(PayloadSanitizer.convertirBoolean("0") === false, '"0" -> false');
    console.assert(PayloadSanitizer.convertirBoolean(1) === true, '1 -> true');
    console.assert(PayloadSanitizer.convertirBoolean(0) === false, '0 -> false');
    
    // Test 3: Convertir números
    console.log('\n✅ Test 3: Convertir números');
    console.assert(PayloadSanitizer.convertirNumero("123") === 123, '"123" -> 123');
    console.assert(PayloadSanitizer.convertirNumero("45.67") === 45.67, '"45.67" -> 45.67');
    console.assert(PayloadSanitizer.convertirNumero("") === null, '"" -> null');
    console.assert(PayloadSanitizer.convertirNumero(null) === null, 'null -> null');
    
    // Test 4: Limpiar strings
    console.log('\n✅ Test 4: Limpiar strings');
    console.assert(PayloadSanitizer.limpiarString("  test  ") === "test", 'Elimina espacios');
    console.assert(PayloadSanitizer.limpiarString("") === null, 'String vacío -> null');
    console.assert(PayloadSanitizer.limpiarString(null) === null, 'null -> null');
    
    // Test 5: Aplanar arrays anidados
    console.log('\n✅ Test 5: Sanitizar imágenes (aplanar arrays)');
    const imagenesAnidadas = [[[{ original: "img1.jpg" }]], [{ original: "img2.jpg" }]];
    const imagenesLimpias = PayloadSanitizer.sanitizarImagenes(imagenesAnidadas);
    console.assert(imagenesLimpias.length === 2, 'Debería aplanar correctamente');
    console.log('Anidadas:', imagenesAnidadas);
    console.log('Limpias:', imagenesLimpias);
    
    console.groupEnd();
    console.log('\n✅ Todos los tests pasaron');
}

// Ejecutar tests
testSanitizador();

// ==================== RESUMEN Y MEJORES PRÁCTICAS ====================

console.log('\n📚 RESUMEN Y MEJORES PRÁCTICAS:');
console.log(`
✅ SIEMPRE sanitizar antes de enviar a Laravel:
   const payload = PayloadSanitizer.sanitizarPedido(formData);

✅ VALIDAR después de sanitizar:
   const { valido, errores } = PayloadSanitizer.validarPayload(payload);

✅ DEBUG en desarrollo:
   PayloadSanitizer.debug(antes, despues);

❌ NUNCA enviar objetos reactivos directamente:
   fetch('/api', { body: JSON.stringify(reactive(data)) }); // MAL

✅ USAR siempre el sanitizador:
   fetch('/api', { body: JSON.stringify(PayloadSanitizer.sanitizarPedido(data)) }); // BIEN

📦 INCLUIR en tu HTML:
   <script src="/js/modulos/crear-pedido/utils/payload-sanitizer.js"></script>

🔧 USAR en cualquier componente:
   const limpio = PayloadSanitizer.sanitizarPedido(pedido);
`);
