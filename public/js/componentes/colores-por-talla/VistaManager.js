/**
 * VistaManager - Gestor de Vistas del Sistema
 * Maneja las transiciones entre vistas y el estado visual
 * Centraliza toda la lógica de UI del sistema de colores por talla
 */

window.VistaManager = (function() {
    'use strict';
    
    // Estado actual de las vistas
    let estadoActual = {
        vistaActiva: 'tabla-telas', // 'tabla-telas' | 'asignacion'
        wizardVisible: false,
        resumenVisible: false
    };
    
    // Cache de elementos DOM
    const elementos = {
        vistaTablaTelas: null,
        vistaAsignacion: null,
        btnAsignar: null,
        wizardContenedor: null,
        seccionTallasCantidades: null,
        seccionResumenAsignaciones: null
    };
    
    /**
     * Inicializar el gestor de vistas
     */
    function init() {
        console.log('[VistaManager]  Inicializando gestor de vistas...');
        
        // Cache de elementos DOM
        cacheElementos();
        
        // Establecer estado inicial
        establecerEstadoInicial();
        
        console.log('[VistaManager]  Gestor de vistas inicializado');
        return true;
    }
    
    /**
     * Cache de elementos DOM para mejor performance
     */
    function cacheElementos() {
        elementos.vistaTablaTelas = document.getElementById('vista-tabla-telas');
        elementos.vistaAsignacion = document.getElementById('vista-asignacion-colores');
        elementos.btnAsignar = document.getElementById('btn-asignar-colores-tallas');
        elementos.wizardContenedor = document.getElementById('wizard-contenedor');
        elementos.seccionTallasCantidades = document.getElementById('seccion-tallas-cantidades');
        elementos.seccionResumenAsignaciones = document.getElementById('seccion-resumen-asignaciones');
        
        console.log('[VistaManager]  Elementos cacheados:', {
            vistaTablaTelas: !!elementos.vistaTablaTelas,
            vistaAsignacion: !!elementos.vistaAsignacion,
            btnAsignar: !!elementos.btnAsignar,
            wizardContenedor: !!elementos.wizardContenedor,
            seccionTallasCantidades: !!elementos.seccionTallasCantidades,
            seccionResumenAsignaciones: !!elementos.seccionResumenAsignaciones
        });
    }
    
    /**
     * Establecer estado inicial de las vistas
     */
    function establecerEstadoInicial() {
        // Por defecto, mostrar tabla de telas y ocultar asignación
        if (elementos.vistaTablaTelas) {
            elementos.vistaTablaTelas.style.display = 'block';
        }
        if (elementos.vistaAsignacion) {
            elementos.vistaAsignacion.style.display = 'none';
        }
        
        // Ocultar wizard inicialmente
        if (elementos.wizardContenedor) {
            elementos.wizardContenedor.style.display = 'none';
        }
        
        // Actualizar botón
        actualizarBotonAsignar('tabla-telas');
        
        console.log('[VistaManager]  Estado inicial establecido');
    }
    
    /**
     * Toggle entre vista de tabla y asignación
     */
    function toggleVista() {
        console.log('[VistaManager]  Toggle de vista solicitado');
        
        if (estadoActual.vistaActiva === 'tabla-telas') {
            return mostrarVistaAsignacion();
        } else {
            return mostrarVistaTablaTelas();
        }
    }
    
    /**
     * Mostrar vista de asignación de colores
     */
    function mostrarVistaAsignacion() {
        console.log('[VistaManager] 📋 Mostrando vista de asignación...');
        
        // Cambiar vistas principales
        if (elementos.vistaTablaTelas) {
            elementos.vistaTablaTelas.style.display = 'none';
        }
        if (elementos.vistaAsignacion) {
            elementos.vistaAsignacion.style.display = 'block';
        }
        
        // Actualizar botón
        actualizarBotonAsignar('asignacion');
        
        // Actualizar estado
        estadoActual.vistaActiva = 'asignacion';
        
        // Mostrar wizard
        mostrarWizard();
        
        // Resetear selects
        resetearSelects();
        
        console.log('[VistaManager]  Vista de asignación mostrada');
        return true;
    }
    
    /**
     * Mostrar vista de tabla de telas
     */
    function mostrarVistaTablaTelas() {
        console.log('[VistaManager] 📋 Mostrando vista de tabla de telas...');
        
        // Cambiar vistas principales
        if (elementos.vistaTablaTelas) {
            elementos.vistaTablaTelas.style.display = 'block';
        }
        if (elementos.vistaAsignacion) {
            elementos.vistaAsignacion.style.display = 'none';
        }
        
        // Actualizar botón
        actualizarBotonAsignar('tabla-telas');
        
        // Actualizar estado
        estadoActual.vistaActiva = 'tabla-telas';
        
        // Ocultar wizard y mostrar resumen si hay asignaciones
        ocultarWizardYMostrarResumen();
        
        console.log('[VistaManager]  Vista de tabla mostrada');
        return true;
    }
    
    /**
     * Mostrar wizard
     */
    function mostrarWizard() {
        console.log('[VistaManager] 🧙 Mostrando wizard...');
        
        if (elementos.wizardContenedor) {
            elementos.wizardContenedor.style.display = 'block';
            estadoActual.wizardVisible = true;
            
            // Inicializar wizard si está disponible
            if (window.WizardManager) {
                window.WizardManager.resetearWizard();
                window.WizardManager.inicializarWizard();
            }
            
            console.log('[VistaManager]  Wizard mostrado');
            return true;
        }
        
        console.warn('[VistaManager]  Wizard contenedor no encontrado');
        return false;
    }
    
    /**
     * Ocultar wizard
     */
    function ocultarWizard() {
        console.log('[VistaManager] 🧙 Ocultando wizard...');
        
        if (elementos.wizardContenedor) {
            elementos.wizardContenedor.style.display = 'none';
            estadoActual.wizardVisible = false;
            console.log('[VistaManager]  Wizard ocultado');
            return true;
        }
        
        return false;
    }
    
    /**
     * Ocultar wizard y mostrar resumen si hay asignaciones
     */
    function ocultarWizardYMostrarResumen() {
        console.log('[VistaManager]  Ocultando wizard y evaluando resumen...');
        
        // Ocultar wizard
        ocultarWizard();
        
        // Evaluar si mostrar resumen
        const tieneAsignaciones = window.AsignacionManager ? 
            window.AsignacionManager.obtenerTotalAsignaciones() > 0 : false;
        
        if (elementos.seccionTallasCantidades && elementos.seccionResumenAsignaciones) {
            if (tieneAsignaciones) {
                elementos.seccionTallasCantidades.style.display = 'none';
                elementos.seccionResumenAsignaciones.style.display = 'block';
                estadoActual.resumenVisible = true;
                console.log('[VistaManager]  Hay asignaciones - mostrando resumen');
            } else {
                elementos.seccionTallasCantidades.style.display = 'block';
                elementos.seccionResumenAsignaciones.style.display = 'none';
                estadoActual.resumenVisible = false;
                console.log('[VistaManager]  Sin asignaciones - mostrando tallas');
            }
        }
        
        return tieneAsignaciones;
    }
    
    /**
     * Actualizar botón de asignación según vista activa
     */
    function actualizarBotonAsignar(vista) {
        if (!elementos.btnAsignar) return;
        
        if (vista === 'tabla-telas') {
            elementos.btnAsignar.innerHTML = '<span class="material-symbols-rounded">palette</span>Asignar Colores';
        } else if (vista === 'asignacion') {
            elementos.btnAsignar.innerHTML = '<span class="material-symbols-rounded">arrow_back</span>Volver a Telas';
        }
        
        console.log(`[VistaManager]  Botón actualizado para vista: ${vista}`);
    }
    
    /**
     * Resetear selects de género y talla
     */
    function resetearSelects() {
        console.log('[VistaManager]  Resetando selects...');
        
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        
        if (generoSelect) {
            generoSelect.value = '';
            generoSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        if (tallaSelect) {
            tallaSelect.value = '';
            tallaSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        console.log('[VistaManager]  Selects reseteados');
    }
    
    /**
     * Actualizar vistas después de guardar asignación
     */
    function actualizarDespuesDeGuardar() {
        console.log('[VistaManager]  Actualizando vistas después de guardar...');
        
        // Actualizar UI
        if (window.UIRenderer) {
            window.UIRenderer.actualizarTablaAsignaciones();
            window.UIRenderer.actualizarResumenAsignaciones();
            window.UIRenderer.actualizarVisibilidadSeccionesResumen();
        }
        
        // Resetear wizard
        if (window.WizardManager) {
            window.WizardManager.resetearWizard();
        }
        
        console.log('[VistaManager]  Vistas actualizadas después de guardar');
    }
    
    /**
     * Mostrar modal de selección de tela
     */
    function mostrarModalSeleccionTela(telas) {
        console.log('[VistaManager] 📋 Mostrando modal de selección de tela...');
        
        if (telas.length <= 1) {
            console.log('[VistaManager]  Una sola tela - no mostrar modal');
            return Promise.resolve(telas[0]?.tela || telas[0]?.nombre_tela || null);
        }
        
        const modal = document.getElementById('modal-seleccionar-tela');
        const selector = document.getElementById('selector-tela');
        
        if (!modal || !selector) {
            console.error('[VistaManager]  Modal o selector no encontrado');
            return Promise.resolve(null);
        }
        
        // Limpiar y llenar selector
        selector.innerHTML = '';
        
        telas.forEach((tela, index) => {
            const nombreTela = tela.tela || tela.nombre_tela || `Tela ${index + 1}`;
            const option = document.createElement('option');
            option.value = nombreTela;
            option.textContent = nombreTela;
            selector.appendChild(option);
        });
        
        // Mostrar modal
        modal.style.display = 'flex';
        
        return new Promise((resolve) => {
            const btnConfirmar = document.getElementById('btn-confirmar-tela');
            const btnCancelar = document.getElementById('btn-cancelar-tela');
            
            const cleanup = () => {
                modal.style.display = 'none';
                btnConfirmar.onclick = null;
                btnCancelar.onclick = null;
            };
            
            btnConfirmar.onclick = () => {
                const seleccionado = selector.value;
                cleanup();
                resolve(seleccionado);
            };
            
            btnCancelar.onclick = () => {
                cleanup();
                resolve(null);
            };
        });
    }
    
    /**
     * Obtener estado actual
     */
    function obtenerEstado() {
        return { ...estadoActual };
    }
    
    /**
     * Verificar si una vista está activa
     */
    function esVistaActiva(vista) {
        return estadoActual.vistaActiva === vista;
    }
    
    /**
     * Forzar actualización de todas las vistas
     */
    function actualizarTodo() {
        console.log('[VistaManager]  Actualizando todas las vistas...');
        
        if (window.UIRenderer) {
            window.UIRenderer.actualizarTablaAsignaciones();
            window.UIRenderer.actualizarResumenAsignaciones();
            window.UIRenderer.actualizarVisibilidadSeccionesResumen();
        }
        
        console.log('[VistaManager]  Todas las vistas actualizadas');
    }
    
    /**
     * API Pública
     */
    return {
        init,
        toggleVista,
        mostrarVistaAsignacion,
        mostrarVistaTablaTelas,
        mostrarWizard,
        ocultarWizard,
        ocultarWizardYMostrarResumen,
        actualizarDespuesDeGuardar,
        mostrarModalSeleccionTela,
        resetearSelects,
        actualizarTodo,
        obtenerEstado,
        esVistaActiva
    };
})();
