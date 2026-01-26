/**
 * EJEMPLO: Uso del flujo de creación de pedidos con EPPs e imágenes
 * 
 * Este archivo demuestra cómo usar EppFlujoCreacion en un escenario real
 */

// ============================================================================
// EJEMPLO 1: Estructura de datos del pedido (lo que viaja en el formulario)
// ============================================================================

const ejemploPedidoConImagenes = {
    cliente: 'Juan Pérez',
    asesora: 'María López',
    forma_de_pago: 'Contado',
    descripcion: 'Pedido de seguridad laboral',
    
    prendas: [],  // Vacío en este ejemplo (solo EPPs)
    
    epps: [
        {
            epp_id: 849,
            nombre_epp: 'ADAPTADOR PLASTICO PORTA VISOR PARA CASCO STEELPRO',
            categoria: 'Protección',
            cantidad: 324,
            observaciones: 'Urgente, entrega rápida',
            imagenes: [
                {
                    id: 1769414898357,
                    nombre: '20d87e75394e203121b338cd5abc588f.jpg',
                    archivo: File,  // Objeto File del navegador
                    preview: 'data:image/jpeg;base64,...'  // Para mostrar en UI
                }
            ]
        },
        {
            epp_id: 2455,
            nombre_epp: 'ABRIGO CON CAPUCHA AMARILLO',
            categoria: 'Abrigo',
            cantidad: 50,
            observaciones: '',
            imagenes: [
                { archivo: File },
                { archivo: File }
            ]
        }
    ]
};

// ============================================================================
// EJEMPLO 2: Implementar en el formulario de envío
// ============================================================================

async function manejarSubmitFormulario(event) {
    event.preventDefault();

    try {
        // Recopilar datos del formulario (como ya lo haces)
        const pedidoData = {
            cliente: document.getElementById('cliente').value,
            asesora: document.getElementById('asesora').value,
            forma_de_pago: document.getElementById('forma_de_pago').value,
            epps: window.eppState?.epps || []  // Tu estado actual
        };

        // Validar que haya EPPs
        if (!pedidoData.epps || pedidoData.epps.length === 0) {
            alert('Debes agregar al menos un EPP');
            return;
        }

        // Mostrar spinner/loading
        mostrarCargando(true);

        // USAR EL FLUJO COMPLETO
        const flujo = new window.EppFlujoCreacion('/api');
        const resultado = await flujo.crearPedidoCompleto(pedidoData);

        // ✅ Éxito
        alert(`✅ Pedido creado exitosamente\n` +
              `Número: ${resultado.numero_pedido}\n` +
              `Imágenes subidas: ${resultado.imagenes_resultado.imagenes_subidas}`);

        // Limpiar formulario
        limpiarFormulario();

        // Redirigir a vista del pedido
        window.location.href = `/asesores/pedidos/${resultado.pedido_id}`;

    } catch (error) {
        console.error('❌ Error:', error);
        alert(`Error: ${error.message}`);
    } finally {
        mostrarCargando(false);
    }
}

// ============================================================================
// EJEMPLO 3: Si prefieres controlar PASO 1 y PASO 2 por separado
// ============================================================================

async function crearPedidoPaso1(pedidoData) {
    const flujo = new window.EppFlujoCreacion('/api');
    
    try {
        const resultado = await flujo.crearPedido(pedidoData);
        console.log('✅ Pedido creado, ID:', resultado.pedido_id);
        
        return resultado.pedido_id;
    } catch (error) {
        console.error('❌ Fallo crear pedido:', error);
        throw error;
    }
}

async function subirImagenesPaso2(pedidoId, pedidoData) {
    const flujo = new window.EppFlujoCreacion('/api');
    
    try {
        const resultado = await flujo.subirImagenesPedido(pedidoId, pedidoData);
        console.log('✅ Imágenes subidas:', resultado.imagenes_subidas);
        
        return resultado;
    } catch (error) {
        console.error('❌ Fallo subir imágenes:', error);
        // El pedido ya existe, no es crítico que fallen las imágenes
        return {
            success: false,
            imagenes_subidas: 0,
            error: error.message
        };
    }
}

// Uso paso a paso:
async function flujoManual() {
    const pedidoData = obtenerDatosFormulario();
    
    try {
        // PASO 1
        const pedidoCreado = await crearPedidoPaso1(pedidoData);
        console.log('Pedido ID:', pedidoCreado);
        
        // PASO 2
        const imagenesSubidas = await subirImagenesPaso2(pedidoCreado, pedidoData);
        
        if (imagenesSubidas.success) {
            console.log('✅ Todo perfecto');
        } else {
            console.warn('⚠️ Pedido OK pero imágenes fallaron');
        }
        
    } catch (error) {
        console.error('❌ Fatal:', error);
    }
}

// ============================================================================
// EJEMPLO 4: Manejo de errores detallado
// ============================================================================

async function crearPedidoConManejErrors(pedidoData) {
    const flujo = new window.EppFlujoCreacion('/api');

    try {
        // PASO 1: Crear pedido
        console.log('📝 Creando pedido...');
        const pedidoCreado = await flujo.crearPedido(pedidoData);

        if (!pedidoCreado.success) {
            throw new Error('Respuesta inválida del servidor');
        }

        const pedidoId = pedidoCreado.pedido_id;
        console.log(`✅ Pedido ${pedidoId} creado`);

        // PASO 2: Subir imágenes
        console.log('🖼️ Subiendo imágenes...');
        const imagenesSubidas = await flujo.subirImagenesPedido(pedidoId, pedidoData);

        if (!imagenesSubidas.success) {
            console.warn('⚠️ Las imágenes no se subieron:', imagenesSubidas.error);
            // Pero el pedido existe, así que continuamos
            return {
                success: true,
                pedido_id: pedidoId,
                imagenes_warning: imagenesSubidas.error
            };
        }

        console.log(`✅ ${imagenesSubidas.imagenes_subidas} imágenes subidas`);

        return {
            success: true,
            pedido_id: pedidoId,
            imagenes_subidas: imagenesSubidas.imagenes_subidas
        };

    } catch (error) {
        console.error('❌ Error fatal:', error.message);
        
        // Decidir qué hacer según el tipo de error
        if (error.message.includes('crear pedido')) {
            // PASO 1 falló: No hay pedido_id, rollback conceptual
            return {
                success: false,
                etapa: 'creacion_pedido',
                error: error.message
            };
        } else {
            // PASO 2 falló: El pedido existe
            return {
                success: false,
                etapa: 'subida_imagenes',
                error: error.message
            };
        }
    }
}

// ============================================================================
// EJEMPLO 5: Integración con Webpack/Module
// ============================================================================

// En tu archivo HTML:
/*
<script src="/js/modulos/crear-pedido/procesos/services/epp-flujo-creacion.js"></script>
<script src="/js/ejemplos/epp-flujo-uso.js"></script>
*/

// O si usas módulos ES6:
// import { EppFlujoCreacion } from './epp-flujo-creacion.js';
