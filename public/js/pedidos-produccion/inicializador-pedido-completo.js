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

console.log('✅ [PedidoCompletoUnificado] Builder cargado y disponible globalmente');

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ADAPTADOR PARA GESTOR SIN COTIZACIÓN
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Crear pedido sin cotización usando el builder unificado
 * Esta función reemplaza la lógica anterior y garantiza sanitización completa
 */
window.crearPedidoConBuilderUnificado = async function() {
    try {
        console.log('[Builder] Iniciando creación de pedido unificado');
        
        // 1. Obtener datos del gestor existente
        const gestor = window.gestorPedidoSinCotizacion;
        if (!gestor) {
            throw new Error('Gestor no inicializado');
        }
        
        const prendas = gestor.obtenerTodas();
        if (prendas.length === 0) {
            throw new Error('No hay prendas agregadas al pedido');
        }
        
        // 2. Obtener datos generales del formulario
        const cliente = document.getElementById('cliente_editable')?.value;
        const asesora = document.getElementById('asesora_editable')?.value;
        const formaPago = document.getElementById('forma_de_pago_editable')?.value;
        
        if (!cliente) {
            throw new Error('Cliente es requerido');
        }
        
        // 3. Construir pedido con builder
        const builder = new PedidoCompletoUnificado();
        
        builder
            .setCliente(cliente)
            .setAsesora(asesora)
            .setFormaPago(formaPago);
        
        // Agregar cada prenda
        prendas.forEach(prenda => {
            console.log('[Builder] Agregando prenda:', prenda.nombre_producto || prenda.nombre_prenda);
            builder.agregarPrenda(prenda);
        });
        
        // 4. Validar antes de enviar
        builder.validate();
        
        // 5. Construir payload limpio
        const payloadLimpio = builder.build();
        
        console.log('[Builder] Payload construido:', {
            cliente: payloadLimpio.cliente,
            items_count: payloadLimpio.items.length,
            forma_pago: payloadLimpio.forma_de_pago
        });
        
        // 6. Enviar al backend
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Creando pedido...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
        
        const response = await fetch('/asesores/pedidos-editable/crear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(payloadLimpio)
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Error al crear pedido');
        }
        
        // 7. Éxito
        console.log('✅ [Builder] Pedido creado exitosamente:', data);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Pedido creado correctamente',
                confirmButtonColor: '#10b981'
            }).then(() => {
                // Redirigir o limpiar formulario
                if (data.pedido_id) {
                    window.location.href = `/asesores/pedidos-produccion/${data.pedido_id}`;
                } else {
                    window.location.reload();
                }
            });
        }
        
        return data;
        
    } catch (error) {
        console.error('[Builder] Error:', error);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo crear el pedido',
                confirmButtonColor: '#dc3545'
            });
        }
        
        throw error;
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
        .setFormaPago(datosFormulario.forma_de_pago);
    
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
    console.log('✅ [Builder] ApiService detectado, extendiendo métodos');
    
    // Reemplazar método crearPedidoSinCotizacion con versión que usa builder
    const originalCrearPedido = window.ApiService.crearPedidoSinCotizacion.bind(window.ApiService);
    
    window.ApiService.crearPedidoSinCotizacion = async function(pedidoData) {
        console.log('[ApiService Override] Usando builder unificado');
        
        const builder = new PedidoCompletoUnificado();
        
        builder
            .setCliente(pedidoData.cliente)
            .setAsesora(pedidoData.asesora || pedidoData.asesor)
            .setFormaPago(pedidoData.forma_de_pago);
        
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
            .setFormaPago(pedidoData.forma_de_pago);
        
        builder.agregarPrenda(pedidoData);
        
        builder.validate();
        const pedidoLimpio = builder.build();
        
        return originalCrearPrenda(pedidoLimpio);
    };
    
} else {
    console.warn('⚠️ [Builder] ApiService no detectado, funcionalidad limitada');
}

console.log('✅ [PedidoCompletoUnificado] Inicializador cargado completamente');
