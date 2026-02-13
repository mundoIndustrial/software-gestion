/**
 * ================================================
 * CÓDIGO DE INTEGRACIÓN FSM EN GestionItemsUI
 * ================================================
 * 
 * Este archivo contiene el código EXACTO a reemplazar
 * en gestion-items-pedido.js línea 309+
 * 
 * INSTRUCCIONES:
 * 1. Copiar contenido de abrirModalAgregarPrendaNueva()
 * 2. Reemplazar en gestion-items-pedido.js (ver línea correspondiente)
 * 3. Verificar que funcione
 * 4. Hacer git add + commit
 * 
 * @file integration-modal-fsm.js
 */


// ================================================
// MÉTODO MEJORADO: abrirModalAgregarPrendaNueva()
// ================================================

async abrirModalAgregarPrendaNueva() {
    try {
        // ========================================
        // FASE 1: GUARDIA CON FSM
        // ========================================
        const fsm = window.__MODAL_FSM__;
        if (!fsm) {
            console.error('[abrirModalAgregarPrendaNueva] ❌ FSM no cargado. Verificar que modal-mini-fsm.js esté incluido en el Blade.');
            throw new Error('FSM no disponible');
        }

        if (!fsm.puedeAbrir()) {
            console.warn(`[abrirModalAgregarPrendaNueva] ⚠️ Modal ya está en estado: ${fsm.obtenerEstado()}. Ignorando llamada.`);
            return;  // Salir silenciosamente
        }

        // Transicionar: CLOSED → OPENING
        fsm.cambiarEstado('OPENING', { origen: 'abrirModalAgregarPrendaNueva' });

        console.log('[abrirModalAgregarPrendaNueva] FASE 1: Iniciando apertura del modal');

        // ========================================
        // FASE 2: CARGAR CATÁLOGOS
        // ========================================
        if (typeof window.cargarCatalogosModal === 'function') {
            console.log('[abrirModalAgregarPrendaNueva] FASE 2: Cargando catálogos...');
            
            try {
                await window.cargarCatalogosModal();
                console.log('[abrirModalAgregarPrendaNueva] ✅ Catálogos cargados correctamente');
            } catch (error) {
                console.error('[abrirModalAgregarPrendaNueva] ❌ Error cargando catálogos:', error.message);
                // NO lanzar error - continuar de todas formas
                // Los catálogos se pueden cargar bajo demanda
            }
        } else {
            console.warn('[abrirModalAgregarPrendaNueva] ⚠️ cargarCatalogosModal no disponible. Continuando sin precarga.');
        }

        // ========================================
        // FASE 3: DETERMINAR MODO (EDICIÓN vs CREACIÓN)
        // ========================================
        const esEdicion = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;

        if (esEdicion) {
            console.log('[abrirModalAgregarPrendaNueva] FASE 3a: EDICIÓN - Abriendo para editar prenda index:', this.prendaEditIndex);

            // Cargar la prenda guardada en DOM
            const prendaAEditar = this.prendas[this.prendaEditIndex];
            if (prendaAEditar && this.prendaEditor) {
                this.prendaEditor.cargarPrendaEnModal(prendaAEditar, this.prendaEditIndex);
            } else {
                console.warn('[abrirModalAgregarPrendaNueva] ⚠️ Prenda no encontrada en índice:', this.prendaEditIndex);
            }
        } else {
            console.log('[abrirModalAgregarPrendaNueva] FASE 3b: CREACIÓN - Abriendo modal vacío para nueva prenda');

            // Abrir modal vacío para crear nueva
            if (this.prendaEditor) {
                this.prendaEditor.abrirModal(false, null);
            } else {
                console.error('[abrirModalAgregarPrendaNueva] ❌ prendaEditor no disponible');
            }
        }

        // ========================================
        // FASE 4: ESPERAR A QUE MODAL SEA VISIBLE
        // ========================================
        console.log('[abrirModalAgregarPrendaNueva] FASE 4: Esperando a que modal esté visible en DOM...');
        await this._esperarModalVisible(1500);  // timeout de 1.5 segundos
        console.log('[abrirModalAgregarPrendaNueva] ✅ Modal visible en DOM');

        // ========================================
        // FASE 5: INICIALIZAR DRAG & DROP
        // ========================================
        if (window.DragDropManager) {
            console.log('[abrirModalAgregarPrendaNueva] FASE 5: Inicializando DragDropManager...');
            try {
                window.DragDropManager.inicializar();
                console.log('[abrirModalAgregarPrendaNueva] ✅ DragDropManager inicializado');
            } catch (error) {
                // No es error fatal
                console.warn('[abrirModalAgregarPrendaNueva] ⚠️ Error inicializando DragDropManager:', error.message);
            }
        } else {
            console.warn('[abrirModalAgregarPrendaNueva] ⚠️ DragDropManager no disponible');
        }

        // ========================================
        // FASE 6: TRANSICIONAR A OPEN
        // ========================================
        fsm.cambiarEstado('OPEN', { origen: 'abrirModalAgregarPrendaNueva' });

        console.log('[abrirModalAgregarPrendaNueva] ✅ ÉXITO - Modal abierto correctamente');
        console.log('[abrirModalAgregarPrendaNueva] Timing:', {
            modal: esEdicion ? 'edición' : 'creación',
            estadoFSM: fsm.obtenerEstado(),
            dragDropListo: !!window.DragDropManager?.inicializado
        });

    } catch (error) {
        // ========================================
        // ERROR HANDLING
        // ========================================
        console.error('[abrirModalAgregarPrendaNueva] ❌ ERROR CRÍTICO:', {
            message: error.message,
            stack: error.stack
        });

        // Resetear FSM a CLOSED (emergencia)
        const fsm = window.__MODAL_FSM__;
        if (fsm) {
            fsm.cambiarEstado('CLOSED', { razon: 'error', error: error.message });
        }

        // Notificar al usuario
        if (typeof NotificationService !== 'undefined' && NotificationService) {
            NotificationService.error('Error abriendo modal de prenda: ' + error.message);
        } else {
            alert('Error abriendo modal: ' + error.message);
        }
    }
}

/**
 * MÉTODO AUXILIAR: Esperar a que el modal sea visible
 * 
 * Problema: El modal abre visualmente pero el DOM puede no estar 100% listo
 * Solución: Esperar a que style.display !== 'none' y offsetHeight > 0
 * 
 * @param {number} timeoutMs - Timeout máximo en milisegundos
 * @returns {Promise<void>}
 * @private
 */
async _esperarModalVisible(timeoutMs = 1500) {
    return new Promise((resolve) => {
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        
        if (!modal) {
            console.warn('[_esperarModalVisible] ❌ Modal no encontrado en DOM.');
            resolve();
            return;
        }

        // Comprobar cada 50ms
        const startTime = Date.now();
        const intervalo = setInterval(() => {
            const isVisible = 
                modal.style.display !== 'none' && 
                modal.style.display !== '' &&
                modal.offsetHeight > 0;
            
            if (isVisible) {
                clearInterval(intervalo);
                const elapsed = Date.now() - startTime;
                console.log(`[_esperarModalVisible] ✅ Modal visible en ${elapsed}ms`);
                resolve();
                return;
            }

            // Comprobar timeout
            if (Date.now() - startTime > timeoutMs) {
                clearInterval(intervalo);
                console.warn(`[_esperarModalVisible] ⚠️ Timeout (${timeoutMs}ms) esperando modal visible. Continuando de todas formas.`);
                resolve();  // Continuar de todas formas (no es error fatal)
            }
        }, 50);
    });
}

/**
 * MÉTODO MEJORADO: Cerrar modal de agregar/editar prenda
 */
cerrarModalAgregarPrendaNueva() {
    try {
        const fsm = window.__MODAL_FSM__;
        
        console.log('[cerrarModalAgregarPrendaNueva] INICIO - Cerrando modal...');

        // Transicionar: OPEN → CLOSING (si es posible)
        if (fsm && fsm.obtenerEstado() === 'OPEN') {
            fsm.cambiarEstado('CLOSING', { origen: 'cerrarModalAgregarPrendaNueva' });
        }

        // Resetear la bandera de nueva prenda desde cotización
        if (this.prendaEditor) {
            this.prendaEditor.prendaEnModoEdicion = false;
            this.prendaEditor.prendaEditIndex = null;
        }

        // Usar PrendaModalManager si está disponible
        if (typeof PrendaModalManager !== 'undefined') {
            try {
                PrendaModalManager.cerrar('modal-agregar-prenda-nueva');
                PrendaModalManager.limpiar('modal-agregar-prenda-nueva');
                console.log('[cerrarModalAgregarPrendaNueva] ✅ Modal cerrado vía PrendaModalManager');
            } catch (error) {
                console.error('[cerrarModalAgregarPrendaNueva] Error en PrendaModalManager:', error);
            }
        }

        // Transicionar: CLOSING → CLOSED (si es posible)
        if (fsm) {
            fsm.cambiarEstado('CLOSED', { origen: 'cerrarModalAgregarPrendaNueva' });
        }

        console.log('[cerrarModalAgregarPrendaNueva] ✅ Modal cerrado correctamente');

    } catch (error) {
        console.error('[cerrarModalAgregarPrendaNueva] ❌ Error:', error);

        // Resetear FSM a CLOSED (emergencia)
        const fsm = window.__MODAL_FSM__;
        if (fsm) {
            fsm.cambiarEstado('CLOSED', { razon: 'error al cerrar', error: error.message });
        }
    }
}

// ================================================
// FIN DE CÓDIGO DE INTEGRACIÓN
// ================================================
