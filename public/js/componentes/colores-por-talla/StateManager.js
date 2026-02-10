/**
 * Módulo: StateManager
 * Gestiona el estado global de asignaciones y wizard
 */

window.StateManager = (function() {
    'use strict';

    // Estado privado
    let state = {
        asignacionesColoresPorTalla: {},
        wizardState: {
            pasoActual: 1,
            telaSeleccionada: null,  // ✅ NUEVA: tela seleccionada del wizard
            generoSeleccionado: null,
            tallasSeleccionadas: [],
            tipoTallaSel: null,
            tallaActualPaso3: null
        },
        tallasDisponiblesPorGenero: {
            dama: {
                'Letra': ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
                'Número': ['34', '36', '38', '40', '42', '44', '46', '48']
            },
            caballero: {
                'Letra': ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
                'Número': ['36', '38', '40', '42', '44', '46', '48', '50', '52']
            },
            sobremedida: {
                'Libre': []
            }
        }
    };

    // Métodos públicos
    return {
        /**
         * Obtener todo el estado
         */
        getState() {
            return JSON.parse(JSON.stringify(state));
        },

        /**
         * Obtener asignaciones de colores
         */
        getAsignaciones() {
            return JSON.parse(JSON.stringify(state.asignacionesColoresPorTalla));
        },

        /**
         * Establecer asignaciones de colores
         */
        setAsignaciones(asignaciones) {
            state.asignacionesColoresPorTalla = JSON.parse(JSON.stringify(asignaciones));
        },

        /**
         * Agregar una asignación
         */
        agregarAsignacion(clave, asignacion) {
            state.asignacionesColoresPorTalla[clave] = JSON.parse(JSON.stringify(asignacion));
        },

        /**
         * Eliminar una asignación
         */
        eliminarAsignacion(clave) {
            delete state.asignacionesColoresPorTalla[clave];
        },

        /**
         * Limpiar todas las asignaciones
         */
        limpiarAsignaciones() {
            state.asignacionesColoresPorTalla = {};
        },

        /**
         * Verificar si hay asignaciones
         */
        tieneAsignaciones() {
            return Object.keys(state.asignacionesColoresPorTalla).length > 0;
        },

        /**
         * Obtener estado del wizard
         */
        getWizardState() {
            return JSON.parse(JSON.stringify(state.wizardState));
        },

        /**
         * Establecer estado del wizard
         */
        setWizardState(wizardState) {
            state.wizardState = JSON.parse(JSON.stringify(wizardState));
        },

        /**
         * Resetear estado del wizard
         */
        resetWizardState() {
            state.wizardState = {
                pasoActual: 1,
                telaSeleccionada: null,  // ✅ Reset tela
                generoSeleccionado: null,
                tallasSeleccionadas: [],
                tipoTallaSel: null,
                tallaActualPaso3: null
            };
        },

        /**
         * Actualizar paso actual del wizard
         */
        setPasoActual(paso) {
            state.wizardState.pasoActual = paso;
        },

        /**
         * Establecer género seleccionado
         */
        setGeneroSeleccionado(genero) {
            state.wizardState.generoSeleccionado = genero;
        },

        /**
         * Agregar talla seleccionada
         */
        agregarTallaSeleccionada(talla) {
            if (!state.wizardState.tallasSeleccionadas.includes(talla)) {
                state.wizardState.tallasSeleccionadas.push(talla);
            }
        },

        /**
         * Remover talla seleccionada
         */
        removerTallaSeleccionada(talla) {
            state.wizardState.tallasSeleccionadas = state.wizardState.tallasSeleccionadas.filter(t => t !== talla);
        },

        /**
         * Limpiar tallas seleccionadas
         */
        limpiarTallasSeleccionadas() {
            state.wizardState.tallasSeleccionadas = [];
        },

        /**
         * Establecer tipo de talla seleccionado
         */
        setTipoTallaSel(tipo) {
            state.wizardState.tipoTallaSel = tipo;
        },

        /**
         * Obtener tela seleccionada en el wizard
         */
        getTelaSeleccionada() {
            return state.wizardState.telaSeleccionada;
        },

        /**
         * Establecer tela seleccionada en el wizard
         */
        setTelaSeleccionada(tela) {
            state.wizardState.telaSeleccionada = tela;
        },

        /**
         * Obtener tallas disponibles por género
         */
        getTallasDisponibles(genero) {
            return state.tallasDisponiblesPorGenero[genero] || {};
        },

        /**
         * Verificar si hay tallas seleccionadas
         */
        tieneTallasSeleccionadas() {
            return state.wizardState.tallasSeleccionadas.length > 0;
        },

        /**
         * Obtener género seleccionado
         */
        getGeneroSeleccionado() {
            return state.wizardState.generoSeleccionado;
        },

        /**
         * Obtener tallas seleccionadas
         */
        getTallasSeleccionadas() {
            return [...state.wizardState.tallasSeleccionadas];
        },

        /**
         * Obtener tipo de talla seleccionado
         */
        getTipoTallaSel() {
            return state.wizardState.tipoTallaSel;
        },

        /**
         * Obtener paso actual del wizard
         */
        getPasoActual() {
            return state.wizardState.pasoActual;
        },

        /**
         * Cargar asignaciones previas (para edición)
         */
        cargarAsignacionesPrevias(datos) {
            if (datos && typeof datos === 'object') {
                state.asignacionesColoresPorTalla = JSON.parse(JSON.stringify(datos));
                console.log('[StateManager] Asignaciones cargadas:', state.asignacionesColoresPorTalla);
            }
        },

        /**
         * Obtener datos de asignaciones para guardar
         */
        obtenerDatosAsignaciones() {
            return JSON.parse(JSON.stringify(state.asignacionesColoresPorTalla));
        }
    };
})();
