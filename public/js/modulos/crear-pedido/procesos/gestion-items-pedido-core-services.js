/**
 * Core services para gestion-items-pedido.
 * Extraido para reducir el monolito y facilitar mantenimiento modular.
 */

const PEDIDOS_LOG_LEVELS = {
    silent: 0,
    error: 1,
    warn: 2,
    info: 3,
    debug: 4
};

function getPedidosLogLevel() {
    const configured = globalThis.PEDIDOS_LOG_LEVEL;
    if (typeof configured === 'string') {
        const normalized = configured.toLowerCase();
        if (Object.prototype.hasOwnProperty.call(PEDIDOS_LOG_LEVELS, normalized)) {
            return normalized;
        }
    }

    // Compatibilidad hacia atrás:
    // - DEBUG_PEDIDOS=true  => debug
    // - caso contrario      => warn
    if (globalThis.DEBUG_PEDIDOS === true) {
        logDeprecatedFallbackOnce(
            'pedidos-log-level-debug-pedidos',
            'Se está usando DEBUG_PEDIDOS. Migra a PEDIDOS_LOG_LEVEL.'
        );
    }
    return globalThis.DEBUG_PEDIDOS === true ? 'debug' : 'warn';
}

function canPedidosLog(level) {
    const currentValue = PEDIDOS_LOG_LEVELS[getPedidosLogLevel()] ?? PEDIDOS_LOG_LEVELS.warn;
    const requestedValue = PEDIDOS_LOG_LEVELS[level] ?? PEDIDOS_LOG_LEVELS.debug;
    return currentValue >= requestedValue;
}

function debugLog(...args) {
    if (canPedidosLog('debug')) {
        console.log(...args);
    }
}

const _deprecatedFallbackLogged = new Set();
function logDeprecatedFallbackOnce(key, message, meta = null) {
    if (_deprecatedFallbackLogged.has(key)) return;
    _deprecatedFallbackLogged.add(key);

    if (meta) {
        debugLog(`[deprecated-fallback] ${message}`, meta);
    } else {
        debugLog(`[deprecated-fallback] ${message}`);
    }
}

class PedidoSubmitController {
    constructor(options = {}) {
        this.formCollector = options.formCollector || null;
        this.apiService = options.apiService || null;
        this.notificationService = options.notificationService || null;
        this.ui = options.ui || {};
    }

    async manejarSubmitFormulario(e) {
        e?.preventDefault?.();
        if (globalThis.__pedidoSubmitInFlight) {
            this.notificationService?.warn?.('Ya estamos procesando este pedido...');
            return;
        }

        globalThis.__pedidoSubmitInFlight = true;
        this.setSubmitDisabled(true);

        try {
            if (!this.formCollector || !this.apiService || !this.notificationService) return;

            const clienteInput = document.getElementById('cliente_editable');
            if (!this._validarCliente(clienteInput)) return;

            const pedidoData = this.formCollector.recolectarDatosPedido();
            this._logPedidoData(pedidoData);

            if (!this._validarItemsPedido(pedidoData)) return;

            this.ui.mostrarCargando?.('Validando pedido...');
            const validacion = await this.apiService.validarPedido(pedidoData);
            debugLog('[gestion-items-pedido]  Validacion recibida:', validacion);

            if (!validacion.success) {
                this.ui.ocultarCargando?.();
                this._mostrarErroresValidacion(validacion);
                return;
            }

            debugLog('[gestion-items-pedido] Validacion exitosa, procediendo a crear pedido');
            this.ui.mostrarCargando?.('Creando pedido...');
            const resultado = await this.apiService.crearPedido(pedidoData);
            this._logResultadoCreacion(resultado);

            if (resultado.success) {
                // El pedido ya fue creado: no debe advertir cambios sin guardar al salir.
                if (globalThis.DraftPedidoUnsavedChanges?.marcarGuardado) {
                    globalThis.DraftPedidoUnsavedChanges.marcarGuardado();
                }

                this.ui.setDatosPedidoCreado?.({
                    pedido_id: resultado.pedido_id,
                    numero_pedido: resultado.numero_pedido
                });
                this.ui.ocultarCargando?.();
                setTimeout(() => {
                    this.ui.mostrarModalExito?.();
                }, 300);
            } else {
                console.warn('[gestion-items-pedido]  resultado.success es FALSE o undefined');
            }
        } catch (error) {
            this._manejarErrorSubmit(error);
        } finally {
            globalThis.__pedidoSubmitInFlight = false;
            this.setSubmitDisabled(false);
        }
    }

    setSubmitDisabled(disabled) {
        const btn = document.getElementById('btn-submit');
        if (!btn) return;

        btn.disabled = disabled;
        btn.style.opacity = disabled ? '0.7' : '1';
        btn.style.cursor = disabled ? 'not-allowed' : 'pointer';
    }

    _validarCliente(clienteInput) {
        if (!clienteInput?.value || clienteInput.value.trim() === '') {
            this.notificationService.error('El cliente es requerido');
            clienteInput?.focus();
            return false;
        }
        return true;
    }

    _validarItemsPedido(pedidoData) {
        const tienePrendas = pedidoData.prendas && pedidoData.prendas.length > 0;
        const tieneEpps = pedidoData.epps && pedidoData.epps.length > 0;
        const tieneItemsLegacy = pedidoData.items && pedidoData.items.length > 0;
        if (!tienePrendas && !tieneEpps && !tieneItemsLegacy) {
            this.notificationService.error('Debe agregar al menos una prenda o un EPP');
            return false;
        }
        return true;
    }

    _mostrarErroresValidacion(validacion) {
        debugLog('[gestion-items-pedido]  Validacion fallo:', validacion.errores);
        const errores = validacion.errores || [];
        if (Array.isArray(errores) && errores.length > 0) {
            alert('Errores en el pedido:\n' + errores.join('\n'));
        } else {
            alert('Error en validacion: ' + (validacion.message || JSON.stringify(validacion)));
        }
    }

    _logPedidoData(pedidoData) {
        debugLog('[gestion-items-pedido]  PEDIDO DATA RECOLECTADA:', {
            prendas_total: pedidoData.prendas?.length || 0,
            epps_total: pedidoData.epps?.length || 0,
            primer_prenda_telas: pedidoData.prendas?.[0]?.telas?.length || 0,
            primer_prenda_procesos: pedidoData.prendas?.[0]?.procesos ? Object.keys(pedidoData.prendas[0].procesos) : [],
            primer_prenda_contenido: pedidoData.prendas?.[0]
        });
        if (pedidoData.prendas?.[0]?.procesos) {
            Object.entries(pedidoData.prendas[0].procesos).forEach(([procesoKey, proceso]) => {
                if (proceso.datos?.datosExtendidos) {
                    debugLog(`[gestion-items-pedido]Proceso "${procesoKey}" TIENE datosExtendidos:`, {
                        generos: Object.keys(proceso.datos.datosExtendidos),
                        datosExtendidos: proceso.datos.datosExtendidos
                    });
                } else {
                    debugLog(`[gestion-items-pedido]  Proceso "${procesoKey}" NO tiene datosExtendidos`);
                }
            });
        }
    }

    _logResultadoCreacion(resultado) {
        debugLog('[gestion-items-pedido]  Resultado recibido:', resultado);
        debugLog('[gestion-items-pedido] ¿resultado.success?', resultado.success);
        debugLog('[gestion-items-pedido] typeof resultado.success:', typeof resultado.success);
        if (resultado.success) {
            debugLog('[gestion-items-pedido]  ENTRANDO AL IF - Pedido creado exitosamente');
            debugLog('[gestion-items-pedido]  datosPedidoCreado:', {
                pedido_id: resultado.pedido_id,
                numero_pedido: resultado.numero_pedido
            });
        }
    }

    _manejarErrorSubmit(error) {
        console.error('[gestion-items-pedido]  ERROR CAPTURADO:', error);
        console.error('[gestion-items-pedido]  Stack:', error.stack);
        console.error('[gestion-items-pedido]  Message:', error.message);
        this.ui.ocultarCargando?.();
        if (this.notificationService) {
            const mensajeError = error.message || 'Error desconocido al crear el pedido';
            this.notificationService.error('Error: ' + mensajeError);
        }
    }
}

class PedidoFeedbackUIService {
    mostrarCargando(mensaje = 'Cargando...') {
        this.ocultarCargando();

        const loader = document.createElement('div');
        loader.id = 'pedido-loader';
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;

        const contenido = document.createElement('div');
        contenido.style.cssText = `
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        `;

        const spinner = document.createElement('div');
        spinner.style.cssText = `
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        `;

        const texto = document.createElement('p');
        texto.textContent = mensaje;
        texto.style.cssText = `
            margin: 0;
            color: #333;
            font-size: 16px;
            font-weight: 500;
        `;

        if (!document.getElementById('pedido-loader-style')) {
            const style = document.createElement('style');
            style.id = 'pedido-loader-style';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }

        contenido.appendChild(spinner);
        contenido.appendChild(texto);
        loader.appendChild(contenido);
        document.body.appendChild(loader);
    }

    ocultarCargando() {
        const loader = document.getElementById('pedido-loader');
        if (loader) {
            loader.remove();
        }
    }

    mostrarExito(mensaje) {
        const exito = document.createElement('div');
        exito.id = 'pedido-exito';
        exito.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;

        const texto = document.createElement('p');
        texto.textContent = mensaje;
        texto.style.cssText = `
            margin: 0;
            color: #27ae60;
            font-size: 18px;
            font-weight: 600;
        `;

        if (!document.getElementById('pedido-exito-style')) {
            const style = document.createElement('style');
            style.id = 'pedido-exito-style';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translate(-50%, -60%);
                    }
                    to {
                        opacity: 1;
                        transform: translate(-50%, -50%);
                    }
                }
            `;
            document.head.appendChild(style);
        }

        exito.appendChild(texto);
        document.body.appendChild(exito);

        setTimeout(() => {
            exito.remove();
        }, 2000);
    }
}

class PedidoSuccessModalService {
    mostrarModalExito(options = {}) {
        const { datosPedidoCreado = null, ctx = (key) => globalThis[key] } = options;

        debugLog('[mostrarModalExito]  INICIANDO');
        debugLog('[mostrarModalExito] ¿Existe MODAL_EXITO_PEDIDO_HTML?', typeof MODAL_EXITO_PEDIDO_HTML);
        debugLog('[mostrarModalExito] ¿datosPedidoCreado?', datosPedidoCreado);

        debugLog('[mostrarModalExito]  LIMPIANDO asignaciones de colores tras creacion exitosa...');
        if (typeof limpiarAsignacionesColores === 'function') {
            limpiarAsignacionesColores();
            debugLog('[mostrarModalExito]  Asignaciones limpiadas');
        } else if (ctx('StateManager') && typeof ctx('StateManager').limpiarAsignaciones === 'function') {
            logDeprecatedFallbackOnce(
                'modal-exito-state-manager-cleanup',
                'Se está usando fallback de limpieza via StateManager. Migra a limpiarAsignacionesColores().'
            );
            ctx('StateManager').limpiarAsignaciones();
            debugLog('[mostrarModalExito]  Asignaciones limpiadas (StateManager)');
        }

        let modalElement = document.getElementById('modalExitoPedido');
        debugLog('[mostrarModalExito] ¿modalElement existe?', !!modalElement);

        if (!modalElement) {
            debugLog('[mostrarModalExito]  Creando modal desde HTML...');
            if (typeof MODAL_EXITO_PEDIDO_HTML === 'undefined') {
                console.error('[mostrarModalExito]  CRITICO: MODAL_EXITO_PEDIDO_HTML no esta definido');
                throw new Error('MODAL_EXITO_PEDIDO_HTML no esta disponible');
            }
            document.body.insertAdjacentHTML('beforeend', MODAL_EXITO_PEDIDO_HTML);
            modalElement = document.getElementById('modalExitoPedido');
            debugLog('[mostrarModalExito]  Modal creado, elemento encontrado?', !!modalElement);
        }

        const btnVolverAPedidos = document.getElementById('btnVolverAPedidos');
        debugLog('[mostrarModalExito] ¿btnVolverAPedidos encontrado?', !!btnVolverAPedidos);

        if (btnVolverAPedidos) {
            debugLog('[mostrarModalExito]  Asignando onclick');
            btnVolverAPedidos.onclick = () => {
                debugLog('[mostrarModalExito] Boton presionado, redirigiendo...');
                if (globalThis.DraftPedidoUnsavedChanges?.marcarGuardado) {
                    globalThis.DraftPedidoUnsavedChanges.marcarGuardado();
                }
                ctx('location').href = '/asesores/pedidos';
            };
        }

        debugLog('[mostrarModalExito]  Mostrando modal');
        modalElement.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        debugLog('[mostrarModalExito]  COMPLETADO');
    }
}
