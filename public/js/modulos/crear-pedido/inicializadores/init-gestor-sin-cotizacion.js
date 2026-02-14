/**
 * INICIALIZACIÓN DEL GESTOR DE PEDIDO SIN COTIZACIÓN - FASE 3b
 * 
 * Detecta si el usuario quiere crear un pedido sin cotización
 * e inicializa el gestor correspondiente
 */

(function() {
    'use strict';

    /**
     * Inicializar GestorPrendaSinCotizacion
     */
    window.inicializarGestorSinCotizacion = function() {
        console.log('[init-gestor-sin-cotizacion.js]  Verificando disponibilidad de clase:', {
            'GestorPrendaSinCotizacion': typeof window.GestorPrendaSinCotizacion,
            'gestorPedidoSinCotizacion': typeof window.gestorPedidoSinCotizacion
        });
        
        if (!window.gestorPedidoSinCotizacion) {
            window.gestorPedidoSinCotizacion = new GestorPrendaSinCotizacion();
            console.log('[init-gestor-sin-cotizacion.js]  GestorPedidoSinCotizacion instanciado');
        }
    };

    /**
     * Entrar en modo SIN COTIZACIÓN
     * Llama a función que ya existe en crear-pedido-editable.js
     * pero ahora usa el gestor
     */
    window.crearPedidoSinCotizacionConGestor = function() {

        
        // Inicializar si no existe
        if (!window.gestorPedidoSinCotizacion) {
            window.inicializarGestorSinCotizacion();
        }

        // Activar modo sin cotización
        window.gestorPedidoSinCotizacion.activar();

        // Inicializar con una prenda vacía
        window.gestorPedidoSinCotizacion.agregarPrenda();

        // Renderizar UI
        window.renderizarPrendasSinCotizacion();

        // Scroll a la sección
        document.getElementById('seccion-info-prenda')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    /**
     * Renderizar prendas del gestor sin cotización
     */
    window.renderizarPrendasSinCotizacion = function() {
        const prendasContainer = document.getElementById('prendas-container-editable');
        if (!prendasContainer) return;

        // Ocultar la lista de items readonly (lista-items-pedido)
        const listaItems = document.getElementById('lista-items-pedido');
        if (listaItems) {
            listaItems.style.display = 'none';
        }

        // Mostrar el contenedor de formulario de entrada
        prendasContainer.style.display = 'block';

        const prendas = window.gestorPedidoSinCotizacion?.obtenerTodas() || [];

        if (prendas.length === 0) {
            prendasContainer.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <p style="color: #6b7280; margin-bottom: 1rem;">No hay prendas agregadas. Haz clic en el botón de abajo para agregar.</p>
                    <button type="button" onclick="agregarPrendaSinCotizacionConGestor()" class="btn btn-primary" style="background: #0066cc; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        ➕ Agregar Prenda
                    </button>
                </div>
            `;
            return;
        }

        let html = '';
        prendas.forEach((prenda, index) => {
            html += `
                <div class="prenda-card-editable" data-prenda-index="${index}" style="margin-bottom: 2rem; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="margin: 0; color: #1f2937; font-size: 1.1rem;">Prenda ${index + 1}</h3>
                        <button type="button" onclick="eliminarPrendaSinCotizacionConGestor(${index})" style="background: #dc3545; color: white; border: none; border-radius: 6px; padding: 0.5rem 1rem; cursor: pointer; font-weight: 600;">
                            ✕ Eliminar
                        </button>
                    </div>

                    <!-- Nombre del Producto -->
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="font-weight: 600; margin-bottom: 0.5rem;">Nombre del Producto</label>
                        <input type="text" 
                               name="nombre_producto[${index}]" 
                               class="prenda-nombre"
                               placeholder="Ej: POLO HOMBRE"
                               value="${prenda.nombre_producto || ''}"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    </div>

                    <!-- Descripción -->
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="font-weight: 600; margin-bottom: 0.5rem;">Descripción</label>
                        <textarea name="descripcion[${index}]" 
                                  class="prenda-descripcion"
                                  placeholder="Describe la prenda..."
                                  style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; min-height: 100px;">${prenda.descripcion || ''}</textarea>
                    </div>

                    <!-- Género -->
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="font-weight: 600; margin-bottom: 0.5rem;">Género</label>
                        <select name="genero[${index}]" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                            <option value="">Seleccionar género</option>
                            <option value="Dama" ${prenda.genero === 'Dama' ? 'selected' : ''}>Dama</option>
                            <option value="Caballero" ${prenda.genero === 'Caballero' ? 'selected' : ''}>Caballero</option>
                        </select>
                    </div>

                    <!-- Tallas y Cantidades -->
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="font-weight: 600; margin-bottom: 0.5rem;">Tallas y Cantidades</label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem;">
                            ${CONFIG.TALLAS_ESTANDAR?.map(talla => `
                                <div>
                                    <label style="font-size: 0.875rem; color: #6b7280;">Talla ${talla}</label>
                                    <input type="number" 
                                           data-talla="${talla}"
                                           class="talla-cantidad"
                                           min="0" 
                                           value="${prenda.cantidadesPorTalla[talla] || 0}"
                                           placeholder="0"
                                           style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px;">
                                </div>
                            `).join('') || '<p style="color: #9ca3af;">Cargando tallas...</p>'}
                        </div>
                    </div>
                </div>
            `;
        });

        prendasContainer.innerHTML = html + `
            <div style="text-align: center; margin-top: 2rem;">
                <button type="button" onclick="agregarPrendaSinCotizacionConGestor()" class="btn btn-primary" style="background: #0066cc; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    ➕ Agregar Otra Prenda
                </button>
            </div>
        `;
    };

    /**
     * Agregar prenda sin cotización usando gestor
     */
    window.agregarPrendaSinCotizacionConGestor = function() {
        if (!window.gestorPedidoSinCotizacion) {
            window.inicializarGestorSinCotizacion();
        }

        window.gestorPedidoSinCotizacion.agregarPrenda();
        window.renderizarPrendasSinCotizacion();
    };

    /**
     * Eliminar prenda sin cotización usando gestor
     */
    window.eliminarPrendaSinCotizacionConGestor = function(index) {
        confirmarEliminacion(
            'Eliminar prenda',
            '¿Estás seguro de que quieres eliminar esta prenda?',
            () => {
                if (window.gestorPedidoSinCotizacion) {
                    window.gestorPedidoSinCotizacion.eliminarPrenda(index);
                    window.renderizarPrendasSinCotizacion();
                    mostrarExito('Prenda eliminada', '✓ Prenda eliminada correctamente');
                }
            }
        );
    };

    /**
     * Procesar envío de pedido sin cotización
     * Detecta si es tipo PRENDA y lo maneja especialmente
     */
    window.procesarSubmitSinCotizacion = function() {
        // Detectar si es tipo PRENDA sin cotización
        const tipoPedido = document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value;
        const tipoNuevo = tipoPedido === 'nuevo';
        const tipoPrendaSelect = document.getElementById('tipo_pedido_nuevo')?.value;
        
        if (tipoNuevo && tipoPrendaSelect === 'P') {
            // Usar módulo especializado para PRENDA sin cotización

            return window.enviarPrendaSinCotizacion()
                .then(response => {
                    // La redirección la maneja enviarPrendaSinCotizacion
                    return response;
                })
                .catch(error => {

                    return Promise.reject(error);
                });
        }

        // Detectar si es tipo REFLECTIVO sin cotización
        if (tipoNuevo && tipoPrendaSelect === 'R') {
            // Usar módulo especializado para REFLECTIVO sin cotización

            return window.enviarReflectivoSinCotizacion()
                .then(response => {
                    // La redirección la maneja enviarReflectivoSinCotizacion
                    return response;
                })
                .catch(error => {

                    return Promise.reject(error);
                });
        }

        // Flujo estándar para otros tipos de pedidos sin cotización
        if (!window.gestorPedidoSinCotizacion) {
            window.inicializarGestorSinCotizacion();
        }



        return window.gestorPedidoSinCotizacion.enviarAlServidor()
            .then(response => {
                // Redirigir a lista de pedidos después de 2 segundos
                setTimeout(() => {
                    window.location.href = '/asesores/pedidos';
                }, 2000);
                return response;
            })
            .catch(error => {

                return Promise.reject(error);
            });
    };

    /**
     * Ejecutar inicialización al cargar el DOM
     */
    document.addEventListener('DOMContentLoaded', function() {
        window.inicializarGestorSinCotizacion();
    });

})();
