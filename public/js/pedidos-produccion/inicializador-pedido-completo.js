/**
 * ═══════════════════════════════════════════════════════════════════════════
 * INICIALIZADOR DE PEDIDO COMPLETO UNIFICADO
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * PROPÓSITO:
 * - Puente entre módulos ES6 y código global legacy
 * - Integra PedidoCompletoUnificado con ApiService
 * - Reemplaza funciones existentes con builder unificado
 * 
 * USO:
 * - Se carga automáticamente en vistas blade con type="module"
 * - Expone funciones globales compatibles con código existente
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { PedidoCompletoUnificado } from './PedidoCompletoUnificado.js';

// Hacer disponible globalmente para código legacy
window.PedidoCompletoUnificado = PedidoCompletoUnificado;

console.log(' [PedidoCompletoUnificado] Builder cargado y disponible globalmente');

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ADAPTADOR PARA GESTOR SIN COTIZACIÓN
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Crear pedido sin cotización usando el builder unificado
 * ENVÍA COMO FormData (NO JSON puro)
 */
window.crearPedidoConBuilderUnificado = async function() {
    try {
        console.log('[Builder] Iniciando creación de pedido unificado');
        
        // 1. Obtener datos del gestor
        const gestor = window.gestorPedidoSinCotizacion;
        if (!gestor) throw new Error('Gestor no inicializado');
        
        const prendas = gestor.obtenerTodas();
        if (prendas.length === 0) throw new Error('No hay prendas agregadas');
        
        // 2. Datos generales
        const cliente = document.getElementById('cliente_editable')?.value;
        const ordenCompra = document.getElementById('orden_compra_editable')?.value;
        const asesora = document.getElementById('asesora_editable')?.value;
        const formaPago = document.getElementById('forma_de_pago_editable')?.value;
        const diaEntrega = document.getElementById('dia_de_entrega_editable')?.value;
        
        if (!cliente) throw new Error('Cliente es requerido');
        
        // 3. Construir con builder
        const builder = new PedidoCompletoUnificado();
        builder
            .setCliente(cliente)
            .setOrdenCompra(ordenCompra)
            .setAsesora(asesora)
            .setFormaPago(formaPago)
            .setDiaEntrega(diaEntrega);
        
        console.log('[Builder] Estado ANTES:', {
            procesos_totales: prendas.reduce((sum, p) => sum + Object.keys(p.procesos || {}).length, 0),
            telas_totales: prendas.reduce((sum, p) => sum + (p.telas || []).length, 0),
            imagenes_totales: prendas.reduce((sum, p) => sum + (p.imagenes || []).length, 0),
        });
        
        // Agregar prendas
        prendas.forEach((prenda) => {
            console.log(`[Builder] Agregando prenda: ${prenda.nombre_producto}`, {
                procesos: Object.keys(prenda.procesos || {}),
                telas: (prenda.telas || []).length,
                imagenes: (prenda.imagenes || []).length
            });
            builder.agregarPrenda(prenda);
        });
        
        // 4. Validar y construir
        builder.validate();
        const payloadLimpio = builder.build();
        
        console.log('[Builder] Payload FINAL:', {
            procesos: payloadLimpio.items.reduce((sum, i) => sum + Object.keys(i.procesos || {}).length, 0),
            telas: payloadLimpio.items.reduce((sum, i) => sum + (i.telas || []).length, 0),
            imagenes: payloadLimpio.items.reduce((sum, i) => sum + (i.imagenes || []).length, 0),
        });
        
        // 5. CONSTRUIR FormData (no JSON puro)
        const formData = window.FormDataBuilder.build(payloadLimpio);
        
        console.log('[Builder] FormData construido, enviando...');
        
        // 6. ENVIAR
        const response = await window.FormDataBuilder.send(
            formData,
            '/api/asesores/pedidos/crear'
        );
        
        console.log('[Builder] Response recibida:', {
            success: response.success,
            pedido_id: response.pedido_id,
            tipo_pedido_id: typeof response.pedido_id,
            numero_pedido: response.numero_pedido,
            response_completa: response
        });
        
        if (response.success) {
            //  DIAGNÓSTICO: Verificar estructura de respuesta
            console.log('[Builder]  DIAGNÓSTICO de pedido_id:', {
                valor: response.pedido_id,
                esUndefined: response.pedido_id === undefined,
                esNull: response.pedido_id === null,
                esObject: typeof response.pedido_id === 'object',
                tieneId: response.pedido_id?.id !== undefined,
                idValor: response.pedido_id?.id
            });
            
            // Asegurar que pedido_id sea un número
            let pedidoId;
            if (response.pedido_id && typeof response.pedido_id === 'object' && response.pedido_id.id !== undefined) {
                pedidoId = response.pedido_id.id;
                console.log('[Builder]  Usando pedido_id.id:', pedidoId);
            } else if (response.pedido_id && typeof response.pedido_id !== 'object') {
                pedidoId = response.pedido_id;
                console.log('[Builder]  Usando pedido_id directamente:', pedidoId);
            } else {
                console.error('[Builder]  Estructura de pedido_id no válida:', response.pedido_id);
                console.log('[Builder]  Buscando otros campos posibles...');
                
                // Buscar en otros campos posibles
                const posiblesIds = ['id', 'pedido_id', 'pedidoId', 'pedido'];
                for (const campo of posiblesIds) {
                    if (response[campo] !== undefined) {
                        pedidoId = response[campo];
                        console.log(`[Builder]  Encontrado en campo "${campo}":`, pedidoId);
                        break;
                    }
                }
            }
            
            console.log('[Builder]  pedidoId final:', pedidoId, 'tipo:', typeof pedidoId);
            
            if (!pedidoId || pedidoId === undefined || pedidoId === 'undefined') {
                console.error('[Builder]  No se pudo determinar un ID de pedido válido');
                console.log('[Builder]  Response completa para debugging:', JSON.stringify(response, null, 2));
                throw new Error('No se recibió ID de pedido válido del servidor');
            }
            
            console.log('[Builder]  Pedido creado, navegando a:', pedidoId);
            window.location.href = `/asesores/pedidos/${pedidoId}`;
        } else {
            throw new Error(response.message || 'Error desconocido al crear pedido');
        }
        
    } catch (error) {
        console.error('[Builder]  Error:', error);
        alert('Error: ' + error.message);
    }
};

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * HELPER: Construir pedido desde datos crudos
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Construir pedido limpio desde cualquier fuente de datos
 * @param {Object} datosFormulario - Datos crudos del formulario
 * @returns {Object} Payload limpio listo para enviar
 */
window.construirPedidoLimpio = function(datosFormulario) {
    const builder = new PedidoCompletoUnificado();
    
    // Datos generales
        builder
            .setCliente(datosFormulario.cliente)
            .setAsesora(datosFormulario.asesora || datosFormulario.asesor)
            .setFormaPago(datosFormulario.forma_de_pago)
            .setDiaEntrega(datosFormulario.dia_de_entrega);
    
    // Prendas
    if (Array.isArray(datosFormulario.items)) {
        datosFormulario.items.forEach(item => builder.agregarPrenda(item));
    } else if (datosFormulario.nombre_prenda || datosFormulario.nombre_producto) {
        // Pedido de una sola prenda
        builder.agregarPrenda(datosFormulario);
    }
    
    // Validar y construir
    builder.validate();
    return builder.build();
};

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * EXTENSIÓN DE ApiService GLOBAL
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Esperar a que ApiService esté disponible
if (window.ApiService) {
    console.log(' [Builder] ApiService detectado, extendiendo métodos');
    
    // Reemplazar método crearPedidoSinCotizacion con versión que usa builder
    const originalCrearPedido = window.ApiService.crearPedidoSinCotizacion.bind(window.ApiService);
    
    window.ApiService.crearPedidoSinCotizacion = async function(pedidoData) {
        console.log('[ApiService Override] Usando builder unificado');
        
        const builder = new PedidoCompletoUnificado();
        
        builder
            .setCliente(pedidoData.cliente)
            .setAsesora(pedidoData.asesora || pedidoData.asesor)
            .setFormaPago(pedidoData.forma_de_pago)
            .setDiaEntrega(pedidoData.dia_de_entrega);
        
        if (Array.isArray(pedidoData.items)) {
            pedidoData.items.forEach(item => builder.agregarPrenda(item));
        }
        
        builder.validate();
        const pedidoLimpio = builder.build();
        
        return originalCrearPedido(pedidoLimpio);
    };
    
    // Reemplazar método crearPedidoPrendaSinCotizacion
    const originalCrearPrenda = window.ApiService.crearPedidoPrendaSinCotizacion.bind(window.ApiService);
    
    window.ApiService.crearPedidoPrendaSinCotizacion = async function(pedidoData) {
        console.log('[ApiService Override] Usando builder unificado para prenda');
        
        const builder = new PedidoCompletoUnificado();
        
        builder
            .setCliente(pedidoData.cliente)
            .setAsesora(pedidoData.asesora || pedidoData.asesor)
            .setFormaPago(pedidoData.forma_de_pago)
            .setDiaEntrega(pedidoData.dia_de_entrega);
        
        builder.agregarPrenda(pedidoData);
        
        builder.validate();
        const pedidoLimpio = builder.build();
        
        return originalCrearPrenda(pedidoLimpio);
    };
    
} else {
    console.warn(' [Builder] ApiService no detectado, funcionalidad limitada');
}

console.log(' [PedidoCompletoUnificado] Inicializador cargado completamente');
