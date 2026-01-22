/**
 * INICIALIZACIÓN DE GESTORES - FASE 2
 * 
 * Inicializa las instancias globales de los gestores
 * Se ejecuta antes de crear-pedido-editable.js
 * 
 * Gestores globales disponibles:
 * - window.gestorCotizacion: Búsqueda y selección de cotizaciones
 * - window.gestorPrendas: Gestión de prendas, fotos, tallas
 * - window.gestorLogo: Gestión de logo y técnicas
 */

(function() {
    'use strict';

    // =====================================================================
    // INICIALIZAR GESTOR DE COTIZACIÓN
    // =====================================================================
    window.inicializarGestorCotizacion = function() {
        const searchInput = document.getElementById('cotizacion_search_editable');
        const dropdown = document.getElementById('cotizacion_dropdown_editable');
        const selectedDiv = document.getElementById('cotizacion_selected_editable');
        const misCotizaciones = window.cotizacionesData || [];

        window.gestorCotizacion = new GestorCotizacion(
            misCotizaciones,
            '#cotizacion_search_editable',
            '#cotizacion_dropdown_editable',
            '#cotizacion_selected_editable',
            function(id, numero, cliente, asesora, formaPago) {
                // Callback cuando se selecciona una cotización
                console.log(' Cotización seleccionada:', { id, numero, cliente, asesora, formaPago });
                
                // Actualizar campos del formulario
                document.getElementById('cotizacion_id_editable').value = id;
                document.getElementById('numero_cotizacion_editable').value = numero;
                document.getElementById('cliente_editable').value = cliente;
                document.getElementById('asesora_editable').value = asesora;
                document.getElementById('forma_de_pago_editable').value = formaPago || '';
                document.getElementById('cotizacion_selected_text_editable').textContent = `${numero} - ${cliente}`;
                selectedDiv.style.display = 'block';

                // Cargar prendas usando el cargador
                const cargador = new CargadorCotizacion('/asesores/pedidos-produccion/obtener-datos-cotizacion');
                cargador.cargar(id)
                    .then(data => {
                        
                        // Inicializar gestor de prendas con datos cargados
                        window.inicializarGestorPrendas(data.prendas || []);
                        
                        // Inicializar gestor de logo con datos cargados
                        if (data.logo) {
                            window.inicializarGestorLogo(data.logo);
                        }

                        // Almacenar datos globales para compatibilidad
                        window.currentTipoCotizacion = data.tipo_cotizacion_codigo || 'PL';
                        window.currentEsReflectivo = window.currentTipoCotizacion === 'RF';
                        window.currentEsLogo = window.currentTipoCotizacion === 'L';
                        window.currentDatosReflectivo = data.reflectivo || null;
                        window.currentEspecificaciones = data.especificaciones || null;

                    })
                    .catch(error => {
                        console.error(' Error cargando cotización:', error);
                        mostrarError('Error', 'No se pudieron cargar los datos de la cotización');
                    });
            }
        );

    };

    // =====================================================================
    // INICIALIZAR GESTOR DE PRENDAS
    // =====================================================================
    window.inicializarGestorPrendas = function(prendasData = []) {
        window.gestorPrendas = new GestorPrendas(
            prendasData,
            'prendas-container-editable'
        );

    };

    // =====================================================================
    // INICIALIZAR GESTOR DE LOGO
    // =====================================================================
    window.inicializarGestorLogo = function(logoCotizacion = {}) {
        window.gestorLogo = new GestorLogo(logoCotizacion);
        
    };

    // =====================================================================
    // HELPERS PARA USAR LOS GESTORES
    // =====================================================================

    /**
     * Agregar nueva prenda sin cotización
     */
    window.agregarPrendaSinCotizacionConGestor = function() {
        const nuevaPrenda = {
            id: window.generarUUID(),
            nombre_producto: '',
            descripcion: '',
            genero: '',
            tallas: [],
            cantidad_total: 1,
            telas: [],
            fotos: []
        };

        if (!window.gestorPrendas) {
            window.inicializarGestorPrendas();
        }

        window.gestorPrendas.agregar(nuevaPrenda);
        
        // Notificar a la UI para renderizar
        if (window.renderizarPrendasConGestor) {
            window.renderizarPrendasConGestor();
        }
    };

    /**
     * Eliminar prenda por índice
     */
    window.eliminarPrendaConGestor = function(index) {
        if (window.gestorPrendas) {
            window.gestorPrendas.eliminar(index);
            
            if (window.renderizarPrendasConGestor) {
                window.renderizarPrendasConGestor();
            }
        }
    };

    /**
     * Obtener datos formateados para envío
     */
    window.obtenerDatosParaEnvio = function() {
        const datosFormulario = {};

        // Datos básicos del pedido
        datosFormulario.numero_cotizacion = document.getElementById('numero_cotizacion_editable')?.value;
        datosFormulario.cliente = document.getElementById('cliente_editable')?.value;
        datosFormulario.asesora = document.getElementById('asesora_editable')?.value;
        datosFormulario.forma_pago = document.getElementById('forma_de_pago_editable')?.value;

        // Datos de prendas
        if (window.gestorPrendas) {
            const datosGestor = window.gestorPrendas.obtenerDatosFormato();
            datosFormulario.prendas = datosGestor.prendas;
            datosFormulario.fotos_nuevas = datosGestor.fotosNuevas;
            datosFormulario.prendas_eliminadas = Array.from(datosGestor.prendasEliminadas);
        }

        // Datos de logo
        if (window.gestorLogo) {
            const datosLogo = window.gestorLogo.obtenerDatosFormato();
            datosFormulario.logo = datosLogo;
        }

        // Datos específicos según tipo de cotización
        if (window.currentEsReflectivo && window.currentDatosReflectivo) {
            datosFormulario.reflectivo = window.currentDatosReflectivo;
        }

        return datosFormulario;
    };

    /**
     * Validar antes de enviar
     */
    window.validarDatos = function() {
        const errores = [];

        // Validar cotización
        if (!document.getElementById('numero_cotizacion_editable')?.value) {
            errores.push('Número de cotización requerido');
        }

        // Validar prendas
        if (window.gestorPrendas) {
            const validacionPrendas = window.gestorPrendas.validar();
            if (!validacionPrendas.valido) {
                errores.push(...validacionPrendas.errores);
            }
        }

        // Validar logo si es necesario
        if (window.currentEsLogo && window.gestorLogo) {
            const validacionLogo = window.gestorLogo.validar();
            if (!validacionLogo.valido) {
                errores.push(...validacionLogo.errores);
            }
        }

        return {
            valido: errores.length === 0,
            errores
        };
    };

    // =====================================================================
    // EJECUTAR INICIALIZACIÓN AL CARGAR EL DOM
    // =====================================================================
    document.addEventListener('DOMContentLoaded', function() {
        
        // Inicializar gestor de cotización
        window.inicializarGestorCotizacion();
        
        // Inicializar gestor de prendas vacío
        window.inicializarGestorPrendas();
        
        // Inicializar gestor de logo vacío
        window.inicializarGestorLogo();

    });

})();
