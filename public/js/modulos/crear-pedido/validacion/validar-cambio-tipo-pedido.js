/**
 * Módulo: Validar Cambio de Tipo de Pedido
 * 
 * Responsabilidad: Interceptar y validar el cambio entre "Desde Cotización" y "Nuevo Pedido"
 * Si hay datos armados en el formulario, muestra un modal de confirmación
 * 
 * Datos considerados "armados":
 * - Cliente ingresado
 * - Forma de pago ingresada
 * - Prendas cargadas
 * - Cotización seleccionada
 */

(() => {
    'use strict';

    /**
     * Detectar si hay datos "armados" en el formulario
     * @returns {Object} - { tienesDatos: boolean, detalles: [] }
     */
    function detectarDatosArmados() {
        const detalles = [];
        let tieneDatos = false;

        // Verificar Cliente
        const clienteInput = document.getElementById('cliente_editable');
        if (clienteInput && clienteInput.value.trim()) {
            detalles.push(`Cliente: "${clienteInput.value}"`);
            tieneDatos = true;
        }

        // Verificar Forma de Pago
        const formaPagoInput = document.getElementById('forma_de_pago_editable');
        if (formaPagoInput && formaPagoInput.value.trim()) {
            detalles.push(`Forma de pago: "${formaPagoInput.value}"`);
            tieneDatos = true;
        }

        // Verificar Prendas cargadas
        const prendasContainer = document.getElementById('prendas-container-editable');
        if (prendasContainer) {
            const prendaCards = prendasContainer.querySelectorAll('.prenda-card-editable');
            if (prendaCards.length > 0) {
                detalles.push(`${prendaCards.length} prenda(s) agregada(s)`);
                tieneDatos = true;
            }
        }

        // Verificar Cotización seleccionada
        const cotizacionId = document.getElementById('cotizacion_id_editable');
        if (cotizacionId && cotizacionId.value) {
            const cotizacionText = document.getElementById('cotizacion_selected_text_editable');
            if (cotizacionText && cotizacionText.textContent) {
                detalles.push(`Cotización: ${cotizacionText.textContent}`);
                tieneDatos = true;
            }
        }

        return { tieneDatos, detalles };
    }

    /**
     * Limpiar todos los datos del formulario
     */
    function limpiarFormulario() {
        // Limpiar Cliente
        const clienteInput = document.getElementById('cliente_editable');
        if (clienteInput) clienteInput.value = '';

        // Limpiar Forma de Pago
        const formaPagoInput = document.getElementById('forma_de_pago_editable');
        if (formaPagoInput) formaPagoInput.value = '';

        // Limpiar Cotización
        const cotizacionId = document.getElementById('cotizacion_id_editable');
        if (cotizacionId) cotizacionId.value = '';

        const logoCotizacionId = document.getElementById('logoCotizacionId');
        if (logoCotizacionId) logoCotizacionId.value = '';

        const cotizacionSearch = document.getElementById('cotizacion_search_editable');
        if (cotizacionSearch) cotizacionSearch.value = '';

        const cotizacionSelected = document.getElementById('cotizacion_selected_editable');
        if (cotizacionSelected) cotizacionSelected.style.display = 'none';

        // Limpiar Prendas
        const prendasContainer = document.getElementById('prendas-container-editable');
        if (prendasContainer) {
            prendasContainer.innerHTML = `
                <div class="empty-state">
                    <p>Selecciona una cotización para ver las prendas</p>
                </div>
            `;
        }

        // Limpiar global si existe
        if (window.prendasCargadas) {
            window.prendasCargadas = [];
        }

        // Limpiar números de identificación
        const numeroCotizacion = document.getElementById('numero_cotizacion_editable');
        if (numeroCotizacion) numeroCotizacion.value = '';

        const numeroPedido = document.getElementById('numero_pedido_editable');
        if (numeroPedido) numeroPedido.value = '';

        console.log('🧹 Formulario limpiado');
    }

    /**
     * Mostrar modal de confirmación antes de cambiar tipo
     * @returns {Promise<boolean>}
     */
    async function mostrarModalConfirmacion(datosActuales) {
        return new Promise((resolve) => {
            // Construir lista de datos a perder
            const detallesHTML = datosActuales.detalles
                .map(detalle => `<li style="text-align: left; margin: 0.5rem 0;">• ${detalle}</li>`)
                .join('');

            Swal.fire({
                title: ' ¿Cambiar tipo de pedido?',
                html: `
                    <div style="text-align: center;">
                        <p style="margin-bottom: 1rem; font-size: 0.95rem;">
                            Ya tienes información armada en el formulario que <strong>será eliminada</strong>:
                        </p>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.9rem; color: #dc2626;">
                            ${detallesHTML}
                        </ul>
                        <p style="margin-top: 1rem; font-size: 0.9rem; color: #6b7280;">
                            ¿Estás seguro de que deseas continuar? Esta acción no se puede deshacer.
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                resolve(result.isConfirmed);
            });
        });
    }

    /**
     * Inicializar validación en el cambio de tipo de pedido
     */
    function inicializarValidacion() {
        const tipoDesdeRadio = document.getElementById('tipo_desde_cotizacion');
        const tipoNuevoRadio = document.getElementById('tipo_nuevo_pedido');

        if (!tipoDesdeRadio || !tipoNuevoRadio) {
            console.warn(' [validar-cambio-tipo-pedido] No se encontraron los radios de tipo de pedido');
            return;
        }

        /**
         * Manejador para cambios en los radio buttons
         */
        async function manejarCambioTipo(e) {
            const radioCambiado = e.target;
            
            // Si el radio ya estaba seleccionado, no hacer nada
            if (!radioCambiado.checked) {
                return;
            }

            // Detectar si hay datos armados
            const datosDetectados = detectarDatosArmados();

            if (datosDetectados.tieneDatos) {
                // Mostrar modal de confirmación
                const usuarioConfirmo = await mostrarModalConfirmacion(datosDetectados);

                if (usuarioConfirmo) {
                    // Usuario confirmó: limpiar formulario
                    limpiarFormulario();
                } else {
                    // Usuario canceló: revertir el cambio
                    e.preventDefault();
                    
                    // Revertir el radio a su estado anterior
                    if (tipoDesdeRadio.checked !== true && radioCambiado === tipoDesdeRadio) {
                        tipoNuevoRadio.checked = true;
                    } else if (tipoNuevoRadio.checked !== true && radioCambiado === tipoNuevoRadio) {
                        tipoDesdeRadio.checked = true;
                    }
                    
                    console.log(' Usuario canceló cambio - radio revertido');
                }
            }
        }

        // Agregar listeners
        tipoDesdeRadio.addEventListener('change', manejarCambioTipo);
        tipoNuevoRadio.addEventListener('change', manejarCambioTipo);
    }

    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarValidacion);
    } else {
        inicializarValidacion();
    }
})();
