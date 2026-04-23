/**
 * GESTIÓN DE TALLAS Y CANTIDADES
 * Sistema centralizado para manejar tallas, géneros y cantidades de prendas
 * 
 * FLUJO CORRECTO:
 * 1. Se abre modal del género
 * 2. Si el otro género ya tiene tipo de talla, se SINCRONIZA automáticamente
 * 3. Si no, se muestran botones para elegir TIPO DE TALLA (LETRA o NÚMERO)
 * 4. Una vez seleccionado el tipo, se muestran las tallas disponibles
 * 5. Se seleccionan las tallas deseadas
 * 6. Se confirma y se crea la tarjeta con el tipo mostrado
 */

// ========== ESTADO GLOBAL DE TALLAS (MODELO RELACIONAL) ==========
// Estructura: { GENERO: { TALLA: CANTIDAD } }
// Ejemplo: { DAMA: { S: 10, M: 15 }, CABALLERO: { 32: 20 } }
window.tallasRelacionales = window.tallasRelacionales || {
    DAMA: {},
    CABALLERO: {},
    UNISEX: {},
    SOBREMEDIDA: {}
};

// Cache de catálogo de tallas desde BD (no resetear si ya cargado)
if (window.catálogoTallasDisponibles === undefined) {
    window.catálogoTallasDisponibles = null;
}

// Variables para rastrear el estado del modal
window.generoActualModal = null;

/**
 * Construye un snapshot de tallas de la prenda en formato de proceso.
 * Salida: { dama: {}, caballero: {}, unisex: {}, sobremedida: {} }
 */
window.obtenerSnapshotTallasParaProcesos = function() {
    _ensureTallasRelacionales();
    return {
        dama: { ...(window.tallasRelacionales.DAMA || {}) },
        caballero: { ...(window.tallasRelacionales.CABALLERO || {}) },
        unisex: { ...(window.tallasRelacionales.UNISEX || {}) },
        sobremedida: { ...(window.tallasRelacionales.SOBREMEDIDA || {}) }
    };
};

/**
 * Sincroniza las tallas de prenda hacia tarjetas de procesos renderizadas.
 * Esto permite ver cambios de cantidades/tallas en tiempo real en cada tarjeta.
 */
window.sincronizarTallasConTarjetasProcesos = function() {
    try {
        const crearDetalleExtendidoVacio = () => ({
            ubicaciones: [],
            observaciones: '',
            imagenes: [],
            imagenesFiles: []
        });

        const obtenerDetalleExtendido = (extendidosGenero, tallaKey) => {
            if (!extendidosGenero || typeof extendidosGenero !== 'object') return null;
            if (extendidosGenero[tallaKey] && typeof extendidosGenero[tallaKey] === 'object') {
                return extendidosGenero[tallaKey];
            }

            const tallaNormalizada = String(tallaKey).replace(/_/g, ' ').trim();
            const keyEquivalente = Object.keys(extendidosGenero).find((k) => {
                return String(k).replace(/_/g, ' ').trim() === tallaNormalizada;
            });

            return keyEquivalente ? extendidosGenero[keyEquivalente] : null;
        };

        const sincronizarDatosExtendidosPorTallas = (datosProceso, tallasSnapshot) => {
            if (!datosProceso || typeof datosProceso !== 'object') return;

            if (!datosProceso.datosExtendidos || typeof datosProceso.datosExtendidos !== 'object') {
                datosProceso.datosExtendidos = {};
            }

            ['dama', 'caballero', 'unisex', 'sobremedida'].forEach((generoKey) => {
                const tallasGenero = tallasSnapshot[generoKey] || {};
                const extendidosGenero = (datosProceso.datosExtendidos[generoKey] && typeof datosProceso.datosExtendidos[generoKey] === 'object')
                    ? datosProceso.datosExtendidos[generoKey]
                    : {};

                const siguiente = {};
                Object.keys(tallasGenero).forEach((tallaKey) => {
                    const detalleExistente = obtenerDetalleExtendido(extendidosGenero, tallaKey);
                    siguiente[tallaKey] = (detalleExistente && typeof detalleExistente === 'object')
                        ? detalleExistente
                        : crearDetalleExtendidoVacio();
                });

                datosProceso.datosExtendidos[generoKey] = siguiente;
            });
        };

        const procesos = window.procesosSeleccionados || {};
        const tipos = Object.keys(procesos);
        if (tipos.length === 0) return;

        const tallasSnapshot = window.obtenerSnapshotTallasParaProcesos();
        let huboCambios = false;

        tipos.forEach((tipo) => {
            const proceso = procesos[tipo];
            if (!proceso || typeof proceso !== 'object') return;
            if (!proceso.datos || typeof proceso.datos !== 'object') return;

            const modoTallas = proceso.datos.modo_tallas || 'generico';
            proceso.datos.tallas = {
                ...tallasSnapshot
            };

            // Mantener detalle por talla alineado al editar tallas en modo GENERAL/ESPECIFICO.
            if (modoTallas === 'general' || modoTallas === 'especifico') {
                sincronizarDatosExtendidosPorTallas(proceso.datos, tallasSnapshot);
            }

            huboCambios = true;
        });

        if (huboCambios && typeof window.renderizarTarjetasProcesos === 'function') {
            window.renderizarTarjetasProcesos();
        }
    } catch (error) {
        console.error('[sincronizarTallasConTarjetasProcesos] Error:', error);
    }
};

/**
 * ========== SINCRONIZACIÓN CON MODAL DE PROCESO ==========
 * Cuando se actualizan las tallas de la prenda, sincronizar automáticamente
 * el resumen de tallas en el modal del proceso (si está abierto)
 */
window.sincronizarTallasConModalProceso = function() {
    try {
        // Siempre mantener las tarjetas de proceso alineadas con las tallas de prenda.
        window.sincronizarTallasConTarjetasProcesos();

        // Solo sincronizar si el modal del proceso está visible
        const modalProceso = document.getElementById('modal-proceso-generico');
        if (!modalProceso || modalProceso.style.display === 'none') {
            console.log('[sincronizarTallasConModalProceso]  Modal de proceso no visible, sincronización saltada');
            return;
        }
        
        console.log('[sincronizarTallasConModalProceso]  Iniciando sincronización...');
        
        // DETECTAR MODO: ¿Hay datos existentes en tallasCantidadesProceso?
        const hayDatosExistentes = (
            (window.tallasCantidadesProceso.dama && Object.keys(window.tallasCantidadesProceso.dama).length > 0) ||
            (window.tallasCantidadesProceso.caballero && Object.keys(window.tallasCantidadesProceso.caballero).length > 0) ||
            (window.tallasCantidadesProceso.unisex && Object.keys(window.tallasCantidadesProceso.unisex).length > 0) ||
            (window.tallasCantidadesProceso.sobremedida && Object.keys(window.tallasCantidadesProceso.sobremedida).length > 0)
        );
        const esEdicion = hayDatosExistentes;
        
        console.log(`[sincronizarTallasConModalProceso]  Modo detectado: ${esEdicion ? 'EDICIÓN' : 'CREACIÓN'}`);
        
        // 1. SINCRONIZAR window.tallasCantidadesProceso desde window.tallasRelacionales
        // En CREACIÓN: reemplazar todo
        // En EDICIÓN: agregar NUEVAS tallas sin sobrescribir existentes
        if (window.tallasRelacionales) {
            // DAMA
            if (window.tallasRelacionales.DAMA && Object.keys(window.tallasRelacionales.DAMA).length > 0) {
                if (esEdicion) {
                    // EDICIÓN: Merge - agregar nuevas, mantener existentes
                    window.tallasCantidadesProceso.dama = {
                        ...window.tallasRelacionales.DAMA,  // Nuevas tallas
                        ...window.tallasCantidadesProceso.dama  // Existentes (sobrescriben si hay duplicadas)
                    };
                    console.log('[sincronizarTallasConModalProceso] DAMA (EDICIÓN - MERGE):', window.tallasCantidadesProceso.dama);
                } else {
                    // CREACIÓN: Reemplazar
                    window.tallasCantidadesProceso.dama = { ...window.tallasRelacionales.DAMA };
                    console.log('[sincronizarTallasConModalProceso] DAMA (CREACIÓN):', window.tallasCantidadesProceso.dama);
                }
            }
            
            // CABALLERO
            if (window.tallasRelacionales.CABALLERO && Object.keys(window.tallasRelacionales.CABALLERO).length > 0) {
                if (esEdicion) {
                    // EDICIÓN: Merge
                    window.tallasCantidadesProceso.caballero = {
                        ...window.tallasRelacionales.CABALLERO,
                        ...window.tallasCantidadesProceso.caballero
                    };
                    console.log('[sincronizarTallasConModalProceso] CABALLERO (EDICIÓN - MERGE):', window.tallasCantidadesProceso.caballero);
                } else {
                    // CREACIÓN: Reemplazar
                    window.tallasCantidadesProceso.caballero = { ...window.tallasRelacionales.CABALLERO };
                    console.log('[sincronizarTallasConModalProceso] CABALLERO (CREACIÓN):', window.tallasCantidadesProceso.caballero);
                }
            }
            
            // SOBREMEDIDA
            if (window.tallasRelacionales.SOBREMEDIDA && Object.keys(window.tallasRelacionales.SOBREMEDIDA).length > 0) {
                if (esEdicion) {
                    // EDICIÓN: Merge
                    window.tallasCantidadesProceso.sobremedida = {
                        ...window.tallasRelacionales.SOBREMEDIDA,
                        ...window.tallasCantidadesProceso.sobremedida
                    };
                    console.log('[sincronizarTallasConModalProceso] SOBREMEDIDA (EDICIÓN - MERGE):', window.tallasCantidadesProceso.sobremedida);
                } else {
                    // CREACIÓN: Reemplazar
                    window.tallasCantidadesProceso.sobremedida = { ...window.tallasRelacionales.SOBREMEDIDA };
                    console.log('[sincronizarTallasConModalProceso] SOBREMEDIDA (CREACIÓN):', window.tallasCantidadesProceso.sobremedida);
                }
            }
            
            // UNISEX
            if (window.tallasRelacionales.UNISEX && Object.keys(window.tallasRelacionales.UNISEX).length > 0) {
                if (!window.tallasCantidadesProceso.unisex) {
                    window.tallasCantidadesProceso.unisex = {};
                }
                if (esEdicion) {
                    // EDICIÓN: Merge
                    window.tallasCantidadesProceso.unisex = {
                        ...window.tallasRelacionales.UNISEX,
                        ...window.tallasCantidadesProceso.unisex
                    };
                    console.log('[sincronizarTallasConModalProceso] UNISEX (EDICIÓN - MERGE):', window.tallasCantidadesProceso.unisex);
                } else {
                    // CREACIÓN: Reemplazar
                    window.tallasCantidadesProceso.unisex = { ...window.tallasRelacionales.UNISEX };
                    console.log('[sincronizarTallasConModalProceso] UNISEX (CREACIÓN):', window.tallasCantidadesProceso.unisex);
                }
            }
        }
        
        // 2. SINCRONIZAR window.tallasSeleccionadasProceso (qué tallas se seleccionaron)
        // Esto indica cuáles tallas están disponibles para el proceso
        if (window.tallasRelacionales && window.tallasSeleccionadasProceso) {
            // Actualizar lista de tallas DAMA seleccionadas
            if (window.tallasRelacionales.DAMA && Object.keys(window.tallasRelacionales.DAMA).length > 0) {
                window.tallasSeleccionadasProceso.dama = Object.keys(window.tallasRelacionales.DAMA);
                console.log('[sincronizarTallasConModalProceso] Tallas DAMA seleccionadas:', window.tallasSeleccionadasProceso.dama);
            } else {
                window.tallasSeleccionadasProceso.dama = [];
            }
            
            // Actualizar lista de tallas CABALLERO seleccionadas
            if (window.tallasRelacionales.CABALLERO && Object.keys(window.tallasRelacionales.CABALLERO).length > 0) {
                window.tallasSeleccionadasProceso.caballero = Object.keys(window.tallasRelacionales.CABALLERO);
                console.log('[sincronizarTallasConModalProceso] Tallas CABALLERO seleccionadas:', window.tallasSeleccionadasProceso.caballero);
            } else {
                window.tallasSeleccionadasProceso.caballero = [];
            }

            // Actualizar lista de tallas UNISEX seleccionadas
            if (window.tallasRelacionales.UNISEX && Object.keys(window.tallasRelacionales.UNISEX).length > 0) {
                window.tallasSeleccionadasProceso.unisex = Object.keys(window.tallasRelacionales.UNISEX);
                console.log('[sincronizarTallasConModalProceso] Tallas UNISEX seleccionadas:', window.tallasSeleccionadasProceso.unisex);
            } else {
                window.tallasSeleccionadasProceso.unisex = [];
            }
            
            // SOBREMEDIDA - actualizar si existe
            if (window.tallasRelacionales.SOBREMEDIDA && Object.keys(window.tallasRelacionales.SOBREMEDIDA).length > 0) {
                window.tallasSeleccionadasProceso.sobremedida = window.tallasRelacionales.SOBREMEDIDA;
                console.log('[sincronizarTallasConModalProceso] Sobremedida seleccionada:', window.tallasSeleccionadasProceso.sobremedida);
            } else {
                window.tallasSeleccionadasProceso.sobremedida = null;
            }
        }
        
        // 3. ACTUALIZAR EL RESUMEN EN EL MODAL DEL PROCESO
        if (typeof window.actualizarResumenTallasProceso === 'function') {
            window.actualizarResumenTallasProceso();
            console.log('[sincronizarTallasConModalProceso]  Resumen de tallas del proceso actualizado');
        } else {
            console.warn('[sincronizarTallasConModalProceso]  Función actualizarResumenTallasProceso no disponible');
        }
        
    } catch (error) {
        console.error('[sincronizarTallasConModalProceso]  Error durante sincronización:', error);
    }
};

/**
 * Punto único para notificar cambios de tallas.
 * - Ejecuta sincronización de procesos/tarjetas.
 * - Emite evento de dominio para desacoplar consumidores.
 */
window.notificarCambioTallas = function(origen = 'desconocido') {
    try {
        if (typeof window.sincronizarTallasConModalProceso === 'function') {
            window.sincronizarTallasConModalProceso();
        }

        const snapshot = (typeof window.obtenerSnapshotTallasParaProcesos === 'function')
            ? window.obtenerSnapshotTallasParaProcesos()
            : null;

        window.dispatchEvent(new CustomEvent('pedido:tallas-cambiadas', {
            detail: {
                origen,
                tallas: snapshot
            }
        }));
    } catch (error) {
        console.error('[notificarCambioTallas] Error notificando cambio de tallas:', error);
    }
};

// Alias legible para flujos existentes.
window.emitirCambioTallas = window.notificarCambioTallas;
window.tipoTallaSeleccionado = null;

// Helper: asegurar que tallasRelacionales nunca sea null
function _ensureTallasRelacionales() {
    if (!window.tallasRelacionales || typeof window.tallasRelacionales !== 'object') {
        window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
    }
}

// ========== FUNCIONES PARA CARGAR TALLAS DESDE BD ==========

/**
 * Cargar catálogo de tallas disponibles desde el endpoint API
 * Se llama una sola vez al inicializar la página
 */
window.cargarCatálogoTallas = async function() {
    try {
        if (window.catálogoTallasDisponibles) {
            // Ya cargado, no hacer nada
            console.log('[gestion-tallas] Catálogo ya cargado en caché');
            return;
        }

        console.log('[gestion-tallas] Cargando catálogo de tallas desde BD...');
        
        // Obtener CSRF token del meta tag o del DOM
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        const response = await fetch('/api/asesores/tallas-disponibles', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin'  // Incluir cookies de sesión
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        
        if (result.success && result.data) {
            window.catálogoTallasDisponibles = result.data;
            console.log('[gestion-tallas]  Catálogo cargado:', result.data);
        } else {
            throw new Error(result.message || 'Respuesta inválida del servidor');
        }

    } catch (error) {
        console.error('[gestion-tallas]  Error al cargar catálogo:', error);
        // Fallback a constantes hardcodeadas si falla el fetch
        const numerosDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];
        const numerosCaballero = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];
        window.catálogoTallasDisponibles = {
            DAMA: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
            CABALLERO: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
            NUMEROS_DAMA: numerosDama,
            NUMEROS_CABALLERO: numerosCaballero,
            NUMEROS_UNISEX: Array.from(new Set([...numerosDama, ...numerosCaballero])),
            UNISEX: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']
        };
        console.warn('[gestion-tallas]  Usando catálogo hardcodeado como fallback');
    }
};

// ========== FUNCIONES DE GESTIÓN DE TALLAS RELACIONAL ==========

/**
 * Guardar cantidad de talla en estructura relacional { GENERO: { TALLA: CANTIDAD } }
 */
window.guardarCantidadTalla = function(genero, talla, cantidad) {
    // Normalizar género a mayúsculas para consistencia
    genero = String(genero).toUpperCase();
    _ensureTallasRelacionales();
    
    const cantInt = parseInt(cantidad) || 0;
    
    if (!window.tallasRelacionales[genero]) {
        window.tallasRelacionales[genero] = {};
    }
    
    if (cantInt > 0) {
        window.tallasRelacionales[genero][talla] = cantInt;
        console.log(`[gestion-tallas]  Talla guardada: ${genero} - ${talla}: ${cantInt}`);
    } else {
        delete window.tallasRelacionales[genero][talla];
        console.log(`[gestion-tallas]  Talla eliminada: ${genero} - ${talla}`);
    }
    
    // Log del estado actual de todas las tallas
    console.log('[gestion-tallas]  Estado actual de tallasRelacionales:', window.tallasRelacionales);
    window.notificarCambioTallas('guardar-cantidad-talla');
};

/**
 * Obtener cantidad de una talla en estructura relacional
 */
window.obtenerCantidadTalla = function(genero, talla) {
    _ensureTallasRelacionales();
    return window.tallasRelacionales[genero] ? (window.tallasRelacionales[genero][talla] || 0) : 0;
};

/**
 * Mostrar las tallas disponibles según el tipo seleccionado
 */
window.mostrarTallasDisponibles = function(tipo) {
    _ensureTallasRelacionales();
    
    const container = document.getElementById('container-tallas-disponibles');
    if (!container) return;
    
    container.innerHTML = '';
    
    let tallasAMostrar = [];
    
    // Usar catálogo cargado desde BD, con fallback a constantes
    const catalogo = window.catálogoTallasDisponibles;
    const genero = window.generoActualModal;
    
    if (!catalogo) {
        console.warn('[gestion-tallas]  Catálogo no cargado, usando constantes');
        // Fallback a constantes si no se cargó el catálogo
        if (tipo === 'letra') {
            tallasAMostrar = TALLAS_LETRAS || [];
        } else if (tipo === 'numero') {
            tallasAMostrar = genero === 'DAMA' ? (TALLAS_NUMEROS_DAMA || []) : (TALLAS_NUMEROS_CABALLERO || []);
        }
    } else {
        // Usar catálogo cargado desde BD
        if (tipo === 'letra') {
            // Mostrar tallas de letra (XS, S, M, L, XL, etc.)
            tallasAMostrar = (genero && catalogo[genero]) || catalogo['DAMA'] || catalogo['UNISEX'] || [];
        } else if (tipo === 'numero') {
            // Mostrar tallas de número diferenciadas por género
            if (genero === 'DAMA') {
                tallasAMostrar = catalogo['NUMEROS_DAMA'] || TALLAS_NUMEROS_DAMA || [];
            } else if (genero === 'UNISEX') {
                // Para UNISEX, combinar todos los números disponibles (Dama + Caballero)
                const numerosDama = catalogo['NUMEROS_DAMA'] || TALLAS_NUMEROS_DAMA || [];
                const numerosCaballero = catalogo['NUMEROS_CABALLERO'] || TALLAS_NUMEROS_CABALLERO || [];
                // Usar Set para evitar duplicados y mantener orden
                tallasAMostrar = Array.from(new Set([...numerosDama, ...numerosCaballero]));
            } else {
                tallasAMostrar = catalogo['NUMEROS_CABALLERO'] || TALLAS_NUMEROS_CABALLERO || [];
            }
        }
    }
    
    // Crear grid de tallas
    const grid = document.createElement('div');
    grid.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem;';
    
    const tallasDic = (window.tallasRelacionales && window.tallasRelacionales[window.generoActualModal]) || {};
    
    tallasAMostrar.forEach(talla => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.dataset.talla = talla;
        
        const isSelected = tallasDic.hasOwnProperty(talla);
        
        btn.style.cssText = `
            padding: 0.75rem;
            border: 2px solid ${isSelected ? '#0066cc' : '#d1d5db'};
            background: ${isSelected ? '#0066cc' : 'white'};
            color: ${isSelected ? 'white' : '#1f2937'};
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
        `;
        
        btn.textContent = talla;
        btn.onclick = () => {
            const isCurrentlySelected = tallasDic.hasOwnProperty(talla);
            
            // Asegurar que el objeto del género existe
            if (!window.tallasRelacionales[window.generoActualModal]) {
                window.tallasRelacionales[window.generoActualModal] = {};
            }
            
            if (isCurrentlySelected) {
                // Deseleccionar: eliminar talla
                delete window.tallasRelacionales[window.generoActualModal][talla];
                console.log(`[gestion-tallas]  Talla deseleccionada: ${window.generoActualModal} - ${talla}`);
                btn.style.borderColor = '#d1d5db';
                btn.style.background = 'white';
                btn.style.color = '#1f2937';

            } else {
                // Seleccionar: agregar talla con cantidad 0
                window.tallasRelacionales[window.generoActualModal][talla] = 0;
                console.log(`[gestion-tallas]  Talla seleccionada: ${window.generoActualModal} - ${talla}`);
                btn.style.borderColor = '#0066cc';
                btn.style.background = '#0066cc';
                btn.style.color = 'white';

            }
            console.log('[gestion-tallas]  Tallas actuales del modal:', window.tallasRelacionales[window.generoActualModal]);
        };
        
        grid.appendChild(btn);
    });
    
    container.appendChild(grid);
};

/**
 * Seleccionar tipo de talla (LETRA o NÚMERO)
 */
window.seleccionarTipoTalla = function(tipo) {

    
    window.tipoTallaSeleccionado = tipo;
    
    // Actualizar botones
    const btnLetra = document.getElementById('btn-tipo-letra');
    const btnNumero = document.getElementById('btn-tipo-numero');
    
    if (btnLetra && btnNumero) {
        if (tipo === 'letra') {
            btnLetra.style.background = '#0066cc';
            btnLetra.style.borderColor = '#0066cc';
            btnLetra.style.color = 'white';
            btnNumero.style.background = 'white';
            btnNumero.style.borderColor = '#d1d5db';
            btnNumero.style.color = '#1f2937';

        } else {
            btnNumero.style.background = '#0066cc';
            btnNumero.style.borderColor = '#0066cc';
            btnNumero.style.color = 'white';
            btnLetra.style.background = 'white';
            btnLetra.style.borderColor = '#d1d5db';
            btnLetra.style.color = '#1f2937';

        }
    }
    
    // Mostrar las tallas disponibles del tipo seleccionado
    mostrarTallasDisponibles(tipo);
};

/**
 * Ocultar selectores de tipo y mostrar contenedor de tallas
 */
window.ocultarSelectorTipo = function() {
    const selectorDiv = document.getElementById('selector-tipo-talla');
    const tallasDiv = document.getElementById('container-tallas-disponibles');
    
    if (selectorDiv) {
        selectorDiv.style.display = 'none';
    }
    if (tallasDiv) {
        tallasDiv.style.display = 'block';
    }
};

/**
 * Mostrar selectores de tipo
 */
window.mostrarSelectorTipo = function() {
    const selectorDiv = document.getElementById('selector-tipo-talla');
    const tallasDiv = document.getElementById('container-tallas-disponibles');
    
    if (selectorDiv) {
        selectorDiv.style.display = 'block';
    }
    if (tallasDiv) {
        tallasDiv.style.display = 'none';
    }
};

/**
 * Abrir modal para seleccionar tallas de un género
 */
window.abrirModalSeleccionarTallas = async function(genero) {
    // Normalizar género a mayúsculas para consistencia
    genero = String(genero).toUpperCase();
    
    // Cargar catálogo de tallas si no está cargado
    await window.cargarCatálogoTallas();
    
    window.generoActualModal = genero;
    
    const modal = document.createElement('div');
    modal.id = `modal-tallas-${genero}`;
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1060000;';
    
    const container = document.createElement('div');
    container.style.cssText = 'background: white; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);';
    
    // Header
    const header = document.createElement('div');
    header.style.cssText = 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; padding: 1.5rem; border-radius: 12px 12px 0 0; display: flex; align-items: center; justify-content: space-between;';
    
    const headerContent = document.createElement('div');
    headerContent.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
    let icon = 'man';
    if (genero === 'DAMA') {
        icon = 'woman';
    } else if (genero === 'UNISEX') {
        icon = 'wc';
    }
    headerContent.innerHTML = `<span class="material-symbols-rounded" style="font-size: 1.5rem;">${icon}</span><h2 style="margin: 0; font-size: 1.25rem;">Seleccionar Tallas ${genero}</h2>`;
    header.appendChild(headerContent);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: transparent; color: white; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;';
    btnCerrar.onclick = () => cerrarModalTallas(genero);
    header.appendChild(btnCerrar);
    
    container.appendChild(header);
    
    // Content
    const content = document.createElement('div');
    content.style.cssText = 'padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;';
    
    // ========== SELECTOR DE TIPO DE TALLA ==========
    const selectorTipo = document.createElement('div');
    selectorTipo.id = 'selector-tipo-talla';
    selectorTipo.style.cssText = 'display: flex; flex-direction: column; gap: 1rem;';
    
    // MOSTRAR OPCIONES DE TIPO (LETRA o NÚMERO) - O SIN TALLA si es UNISEX
    const titleTipo = document.createElement('h3');
    titleTipo.textContent = '¿Qué tipo de tallas deseas?';
    titleTipo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937; font-size: 1.05rem; font-weight: 600;';
    selectorTipo.appendChild(titleTipo);
    
    const btnGroupTipo = document.createElement('div');
    // Si es UNISEX, 3 columnas; si no, 2 columnas
    const esUnisex = genero === 'UNISEX';
    btnGroupTipo.style.cssText = `display: grid; grid-template-columns: ${esUnisex ? '1fr 1fr 1fr' : '1fr 1fr'}; gap: 1rem;`;
    
    // Botón LETRA
    const btnLetra = document.createElement('button');
    btnLetra.id = 'btn-tipo-letra';
    btnLetra.type = 'button';
    btnLetra.style.cssText = `
        padding: 1.25rem;
        border: 2px solid #d1d5db;
        background: white;
        color: #1f2937;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    `;
    btnLetra.innerHTML = '<span class="material-symbols-rounded" style="font-size: 2rem;">text_fields</span><div>LETRA</div><div style="font-size: 0.75rem; color: #6b7280; font-weight: 400;">S, M, L, XL, XXL</div>';
    btnLetra.onclick = () => {
        seleccionarTipoTalla('letra');
        ocultarSelectorTipo();
    };
    btnLetra.onmouseover = () => {
        btnLetra.style.borderColor = '#0066cc';
        btnLetra.style.background = '#f0f9ff';
    };
    btnLetra.onmouseout = () => {
        if (window.tipoTallaSeleccionado !== 'letra') {
            btnLetra.style.borderColor = '#d1d5db';
            btnLetra.style.background = 'white';
        }
    };
    btnGroupTipo.appendChild(btnLetra);
    
    // Botón NÚMERO
    const btnNumero = document.createElement('button');
    btnNumero.id = 'btn-tipo-numero';
    btnNumero.type = 'button';
    btnNumero.style.cssText = `
        padding: 1.25rem;
        border: 2px solid #d1d5db;
        background: white;
        color: #1f2937;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    `;
    btnNumero.innerHTML = '<span class="material-symbols-rounded" style="font-size: 2rem;">tag</span><div>NÚMERO</div><div style="font-size: 0.75rem; color: #6b7280; font-weight: 400;">34, 36, 38, 40...</div>';
    btnNumero.onclick = () => {
        seleccionarTipoTalla('numero');
        ocultarSelectorTipo();
    };
    btnNumero.onmouseover = () => {
        btnNumero.style.borderColor = '#0066cc';
        btnNumero.style.background = '#f0f9ff';
    };
    btnNumero.onmouseout = () => {
        if (window.tipoTallaSeleccionado !== 'numero') {
            btnNumero.style.borderColor = '#d1d5db';
            btnNumero.style.background = 'white';
        }
    };
    btnGroupTipo.appendChild(btnNumero);
    
    // Botón SIN TALLA (solo para UNISEX)
    if (esUnisex) {
        const btnSinTalla = document.createElement('button');
        btnSinTalla.id = 'btn-tipo-sintalla';
        btnSinTalla.type = 'button';
        btnSinTalla.style.cssText = `
            padding: 1.25rem;
            border: 2px solid #d1d5db;
            background: white;
            color: #1f2937;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        `;
        btnSinTalla.innerHTML = '<span class="material-symbols-rounded" style="font-size: 2rem;">package_2</span><div>SIN TALLA</div><div style="font-size: 0.75rem; color: #6b7280; font-weight: 400;">Solo cantidad</div>';
        btnSinTalla.onclick = () => {
            abrirModalCantidadSinTalla();
        };
        btnSinTalla.onmouseover = () => {
            btnSinTalla.style.borderColor = '#7c3aed';
            btnSinTalla.style.background = '#f5f3ff';
        };
        btnSinTalla.onmouseout = () => {
            btnSinTalla.style.borderColor = '#d1d5db';
            btnSinTalla.style.background = 'white';
        };
        btnGroupTipo.appendChild(btnSinTalla);
    }
    
    selectorTipo.appendChild(btnGroupTipo);
    
    content.appendChild(selectorTipo);
    
    // ========== CONTENEDOR DE TALLAS DISPONIBLES ==========
    const tallaContainer = document.createElement('div');
    tallaContainer.id = 'container-tallas-disponibles';
    tallaContainer.style.cssText = 'display: none;';
    
    content.appendChild(tallaContainer);
    container.appendChild(content);
    
    // Footer
    const footer = document.createElement('div');
    footer.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid #e5e7eb;';
    
    const btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;';
    btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
    btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
    btnCancelar.onclick = () => cerrarModalTallas(genero);
    footer.appendChild(btnCancelar);
    
    const btnConfirmar = document.createElement('button');
    btnConfirmar.type = 'button';
    btnConfirmar.textContent = 'Confirmar';
    btnConfirmar.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;';
    btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#0052a3';
    btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#0066cc';
    btnConfirmar.onclick = () => {
        // Asegurar que tallasRelacionales y el género existen
        if (!window.tallasRelacionales) {
            window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        }
        if (!window.tallasRelacionales[genero]) {
            window.tallasRelacionales[genero] = {};
        }
        
        if (Object.keys(window.tallasRelacionales[genero]).length === 0) {
            console.warn('[gestion-tallas]  No hay tallas seleccionadas para', genero);
            alert(' Debes seleccionar al menos una talla');
            return;
        }
        
        console.log(`[gestion-tallas]  Confirmando tallas para ${genero}:`, window.tallasRelacionales[genero]);
        cerrarModalTallas(genero);
        crearTarjetaGenero(genero);
        actualizarTotalPrendas();
        window.notificarCambioTallas('confirmar-modal-tallas');
    };
    footer.appendChild(btnConfirmar);
    
    container.appendChild(footer);
    modal.appendChild(container);
    document.body.appendChild(modal);
};

/**
 * Cerrar modal de tallas
 */
window.cerrarModalTallas = function(genero) {
    // Normalizar género a mayúsculas para consistencia
    genero = String(genero).toUpperCase();
    
    const modal = document.getElementById(`modal-tallas-${genero}`);
    if (modal) {
        modal.remove();
    }
    window.generoActualModal = null;
    window.tipoTallaSeleccionado = null;
};

/**
 * Crear tarjeta de género con tallas y cantidades en estructura relacional
 */
window.crearTarjetaGenero = function(genero, tallas) {
    // Normalizar género a mayúsculas para consistencia
    genero = String(genero).toUpperCase();
    
    //  NUEVO: Si es "GENERICO" (UNISEX), no crear tarjeta visual
    // La tarjeta de unisex se muestra en su propio contenedor via crearTarjetaUnisex()
    if (genero === 'GENERICO') {
        console.log('[crearTarjetaGenero]  GENERICO detectado - No creando tarjeta visual (se muestra via crearTarjetaUnisex)');
        return;
    }
    
    _ensureTallasRelacionales();
    const tallasDic = window.tallasRelacionales[genero] || {};
    
    if (Object.keys(tallasDic).length === 0) {

        return;
    }
    
    // Marcar botón de género como seleccionado
    const btnGenero = document.getElementById(`btn-genero-${genero}`);
    const checkMark = document.getElementById(`check-${genero}`);
    
    if (btnGenero) {
        btnGenero.dataset.selected = 'true';
        btnGenero.style.borderColor = '#0066cc';
        btnGenero.style.background = '#f0f9ff';
    }
    
    if (checkMark) {
        checkMark.style.display = 'block';
    }
    
    // Obtener contenedor
    const container = document.getElementById('tarjetas-generos-container');
    if (!container) {

        return;
    }
    
    // Eliminar tarjeta anterior si existe
    const tarjetaAnterior = container.querySelector(`[data-genero="${genero}"]`);
    if (tarjetaAnterior) {
        tarjetaAnterior.remove();
    }
    
    // Crear tarjeta
    const tarjeta = document.createElement('div');
    tarjeta.dataset.genero = genero;
    tarjeta.style.cssText = `
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    `;
    
    // Header de tarjeta
    const header = document.createElement('div');
    header.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; justify-content: space-between;';
    
    const headerLeft = document.createElement('div');
    headerLeft.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
    const icon = genero === 'DAMA' ? 'woman' : 'man';
    headerLeft.innerHTML = `
        <span class="material-symbols-rounded" style="font-size: 1.5rem; color: #374151;">${icon}</span>
        <div>
            <h4 style="margin: 0; color: #1f2937; font-size: 1rem; font-weight: 600;">${genero}</h4>
        </div>
    `;
    header.appendChild(headerLeft);
    
    const btnGroupAcciones = document.createElement('div');
    btnGroupAcciones.style.cssText = 'display: flex; align-items: center; gap: 0.25rem;';
    
    const btnEditar = document.createElement('button');
    btnEditar.type = 'button';
    btnEditar.title = 'Editar tallas';
    btnEditar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 6px;';
    btnEditar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem;">edit</span>';
    btnEditar.onmouseover = () => {
        btnEditar.style.color = '#0066cc';
        btnEditar.style.background = '#f3f4f6';
    };
    btnEditar.onmouseout = () => {
        btnEditar.style.color = '#6b7280';
        btnEditar.style.background = 'transparent';
    };
    btnEditar.onclick = (() => {
        // Capturar 'tallas' en la clausura
        const tallasDelGenero = tallas;
        return () => {
            // DETECTAR si es SOBREMEDIDA o tallas normales
            if (tallasDelGenero && typeof tallasDelGenero === 'object' && tallasDelGenero.SOBREMEDIDA) {
                //  ES SOBREMEDIDA - Abrir modal especial de sobremedida
                console.log(`[crearTarjetaGenero]  Detectado SOBREMEDIDA en ${genero}, abriendo modal especial`);
                abrirModalSobremedida();
            } else {
                //  SON TALLAS NORMALES - Abrir modal de seleccionar tallas (letra/número)
                console.log(`[crearTarjetaGenero]  Tallas normales en ${genero}, abriendo selector`);
                abrirModalSeleccionarTallas(genero);
            }
        };
    })();
    btnGroupAcciones.appendChild(btnEditar);
    
    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.title = 'Eliminar tallas';
    btnEliminar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 6px;';
    btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem;">delete</span>';
    btnEliminar.onmouseover = () => {
        btnEliminar.style.color = '#ef4444';
        btnEliminar.style.background = '#fee2e2';
    };
    btnEliminar.onmouseout = () => {
        btnEliminar.style.color = '#6b7280';
        btnEliminar.style.background = 'transparent';
    };
    btnEliminar.onclick = () => {

        
        // Limpiar tallas del género (estructura relacional)
        window.tallasRelacionales[genero] = {};

        
        // Remover tarjeta del DOM
        tarjeta.remove();
        
        // Desmarcar botón de género
        const btn = document.getElementById(`btn-genero-${genero}`);
        const check = document.getElementById(`check-${genero}`);
        
        if (btn) {
            btn.dataset.selected = 'false';
            btn.style.borderColor = '#d1d5db';
            btn.style.background = 'white';
            btn.style.color = '#1f2937';
        }
        
        if (check) {
            check.style.display = 'none';
        }
        
        // Actualizar total
        actualizarTotalPrendas();
        
        //  SINCRONIZAR CON MODAL DE PROCESO cuando se elimina un género
        window.notificarCambioTallas('eliminar-genero-tarjeta');
    };
    btnGroupAcciones.appendChild(btnEliminar);
    
    header.appendChild(btnGroupAcciones);
    
    tarjeta.appendChild(header);
    
    // Grid de cantidades en estructura relacional
    const gridCantidades = document.createElement('div');
    gridCantidades.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem;';
    
    Object.entries(tallasDic).forEach(([talla, cantidad]) => {
        const itemDiv = document.createElement('div');
        itemDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem;';
        
        const label = document.createElement('label');
        label.textContent = talla;
        label.style.cssText = 'font-size: 0.875rem; font-weight: 600; color: #6b7280; text-align: center;';
        
        const input = document.createElement('input');
        input.type = 'number';
        input.min = '0';
        input.value = cantidad;  //  Cargar la cantidad (puede ser 0)
        input.data = { talla, cantidad };  //  Guardar datos para referencia
        input.style.cssText = 'padding: 0.5rem; border: 2px solid #0066cc; border-radius: 6px; text-align: center; font-weight: 600; font-size: 0.9rem;';
        input.onchange = () => {
            console.log(`[crearTarjetaGenero] ${genero} - ${talla}: ${input.value}`);  //  Logging
            guardarCantidadTalla(genero, talla, input.value);
            actualizarTotalPrendas();
        };
        input.onkeyup = () => {
            actualizarTotalPrendas();
            //  SINCRONIZAR CON MODAL DE PROCESO en tiempo real mientras se escriben cantidades
            window.notificarCambioTallas('tecleo-cantidad-tarjeta-genero');
        };
        
        itemDiv.appendChild(label);
        itemDiv.appendChild(input);
        gridCantidades.appendChild(itemDiv);
    });
    
    tarjeta.appendChild(gridCantidades);
    container.appendChild(tarjeta);
};

/**
 * Actualizar total de prendas (incluyendo sobremedida y UNISEX)
 */
window.actualizarTotalPrendas = function() {
    let total = 0;
    
    // Sumar tallas normales
    document.querySelectorAll('#tarjetas-generos-container input[type="number"]').forEach(input => {
        total += parseInt(input.value) || 0;
    });
    
    // Sumar UNISEX desde la estructura relacional
    if (window.tallasRelacionales && window.tallasRelacionales.UNISEX) {
        Object.values(window.tallasRelacionales.UNISEX).forEach(cantidad => {
            total += parseInt(cantidad) || 0;
        });
    }
    
    // Sumar sobremedida
    if (window.tallasRelacionales && window.tallasRelacionales.SOBREMEDIDA) {
        Object.values(window.tallasRelacionales.SOBREMEDIDA).forEach(cantidad => {
            total += parseInt(cantidad) || 0;
        });
    }
    
    // Fallback: Sumar desde cantidadSoloSeleccionada si existe y no está en tallasRelacionales.UNISEX
    if (window.cantidadSoloSeleccionada && (!window.tallasRelacionales || !window.tallasRelacionales.UNISEX || Object.keys(window.tallasRelacionales.UNISEX).length === 0)) {
        total += parseInt(window.cantidadSoloSeleccionada) || 0;
    }
    
    const totalElement = document.getElementById('total-prendas');
    if (totalElement) {
        totalElement.textContent = total;
        console.log(`[gestion-tallas]  Total de prendas actualizado: ${total}`);
    }
};

/**
 * Obtener todas las tallas y cantidades en estructura relacional
 */
window.obtenerTallasYCantidades = function() {
    // Retornar directamente la estructura relacional: { GENERO: { TALLA: CANTIDAD } }
    const resultado = {};
    
    console.log('[gestion-tallas]  Diagnóstico antes de procesar:');
    console.log('[gestion-tallas] Estado completo de tallasRelacionales:', window.tallasRelacionales);
    
    Object.entries(window.tallasRelacionales).forEach(([genero, tallasObj]) => {
        if (Object.keys(tallasObj).length > 0) {
            resultado[genero] = tallasObj;
            console.log(`[gestion-tallas]  Género ${genero} incluido en resultado:`, tallasObj);
        } else {
            console.log(`[gestion-tallas] ⏭️ Género ${genero} ignorado (vacío)`, tallasObj);
        }
    });
    
    console.log('[gestion-tallas] Tallas y cantidades FINALES a enviar:', resultado);
    return resultado;
};

/**
 * Validar que se hayan seleccionado tallas
 */
window.validarTallasSeleccionadas = function() {
    const dama = Object.keys(window.tallasRelacionales.DAMA || {}).length > 0;
    const caballero = Object.keys(window.tallasRelacionales.CABALLERO || {}).length > 0;
    const unisex = Object.keys(window.tallasRelacionales.UNISEX || {}).length > 0;
    const sobremedida = Object.keys(window.tallasRelacionales.SOBREMEDIDA || {}).length > 0;
    
    if (!dama && !caballero && !unisex && !sobremedida) {

        alert(' Debe seleccionar al menos tallas de un género (DAMA, CABALLERO o UNISEX)');
        return false;
    }
    

    return true;
};

/**
 * Limpiar todas las tallas y cantidades
 */
window.limpiarTallasSeleccionadas = function() {

    
    // Resetear estructura relacional
    window.tallasRelacionales = {
        DAMA: {},
        CABALLERO: {},
        UNISEX: {},
        SOBREMEDIDA: {}
    };
    

    
    // Actualizar UI
    const container = document.getElementById('tarjetas-generos-container');
    if (container) {
        container.innerHTML = '';
    }
    
    ['DAMA', 'CABALLERO'].forEach(genero => {
        const btn = document.getElementById(`btn-genero-${genero}`);
        const check = document.getElementById(`check-${genero}`);
        
        if (btn) {
            btn.dataset.selected = 'false';
            btn.style.borderColor = '#d1d5db';
            btn.style.background = 'white';
            btn.style.color = '#1f2937';
        }
        
        if (check) {
            check.style.display = 'none';
        }
    });
    
    actualizarTotalPrendas();
    window.notificarCambioTallas('limpiar-tallas-seleccionadas');

};

// ========== FUNCIONES PARA SOBREMEDIDA ==========

/**
 * Estructura especial para sobremedida en tallasRelacionales:
 * { SOBREMEDIDA: { cantidad: <numero>, genero: 'UNISEX'|'DAMA'|'CABALLERO' } }
 */

/**
 * Abrir modal para ingresar sobremedida (solo cantidad, sin tallas)
 */
window.abrirModalSobremedida = async function() {
    const modal = document.createElement('div');
    modal.id = 'modal-sobremedida';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1060000;';
    
    const container = document.createElement('div');
    container.style.cssText = 'background: white; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); overflow: hidden;';
    
    // Header
    const header = document.createElement('div');
    header.style.cssText = 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;';
    
    const headerContent = document.createElement('div');
    headerContent.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
    headerContent.innerHTML = `<span class="material-symbols-rounded" style="font-size: 1.5rem;">straighten</span><h2 style="margin: 0; font-size: 1.25rem;">Agregar Sobremedida</h2>`;
    header.appendChild(headerContent);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: transparent; color: white; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;';
    btnCerrar.onclick = () => cerrarModalSobremedida();
    header.appendChild(btnCerrar);
    
    container.appendChild(header);
    
    // Content
    const content = document.createElement('div');
    content.style.cssText = 'padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem;';
    
    // Explicación
    const explicacion = document.createElement('p');
    explicacion.style.cssText = 'margin: 0; color: #6b7280; font-size: 0.95rem; line-height: 1.5;';
    explicacion.textContent = 'La sobremedida permite agregar cantidad total sin especificar tallas individuales. Ideal para prenda a medida o genérica.';
    content.appendChild(explicacion);
    
    // Selector de Género
    const generoLabel = document.createElement('label');
    generoLabel.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem; font-weight: 600; color: #1f2937;';
    generoLabel.innerHTML = '<span>¿Para cuál género? *</span>';
    
    const generoSelect = document.createElement('select');
    generoSelect.id = 'sobremedida-genero';
    generoSelect.style.cssText = 'padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 1rem; cursor: pointer;';
    generoSelect.innerHTML = `
        <option value="UNISEX">UNISEX / Indistinto</option>
        <option value="DAMA">DAMA</option>
        <option value="CABALLERO">CABALLERO</option>
    `;
    generoLabel.appendChild(generoSelect);
    content.appendChild(generoLabel);
    
    // Input de Cantidad
    const cantidadLabel = document.createElement('label');
    cantidadLabel.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem; font-weight: 600; color: #1f2937;';
    cantidadLabel.innerHTML = '<span>Cantidad Total *</span>';
    
    const cantidadInput = document.createElement('input');
    cantidadInput.id = 'sobremedida-cantidad';
    cantidadInput.type = 'number';
    cantidadInput.min = '1';
    cantidadInput.placeholder = 'Ej: 100';
    cantidadInput.style.cssText = 'padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 1rem; font-weight: 600;';
    cantidadLabel.appendChild(cantidadInput);
    content.appendChild(cantidadLabel);
    
    container.appendChild(content);
    
    // Footer
    const footer = document.createElement('div');
    footer.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid #e5e7eb;';
    
    const btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;';
    btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
    btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
    btnCancelar.onclick = () => cerrarModalSobremedida();
    footer.appendChild(btnCancelar);
    
    const btnConfirmar = document.createElement('button');
    btnConfirmar.type = 'button';
    btnConfirmar.textContent = 'Confirmar';
    btnConfirmar.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;';
    btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#0052a3';
    btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#0066cc';
    btnConfirmar.onclick = () => {
        const genero = document.getElementById('sobremedida-genero').value;
        const cantidad = parseInt(document.getElementById('sobremedida-cantidad').value) || 0;
        
        if (!genero) {
            alert('Selecciona un género');
            return;
        }
        
        if (cantidad <= 0) {
            alert('La cantidad debe ser mayor a 0');
            document.getElementById('sobremedida-cantidad').focus();
            return;
        }
        
        guardarSobremedida(genero, cantidad);
        cerrarModalSobremedida();
        crearTarjetaSobremedida(genero, cantidad);
        actualizarTotalPrendas();
        window.notificarCambioTallas('confirmar-sobremedida');
    };
    footer.appendChild(btnConfirmar);
    
    container.appendChild(footer);
    modal.appendChild(container);
    
    document.body.appendChild(modal);
    
    // Focus en cantidad
    setTimeout(() => document.getElementById('sobremedida-cantidad').focus(), 100);
};

/**
 * Cerrar modal de sobremedida
 */
window.cerrarModalSobremedida = function() {
    const modal = document.getElementById('modal-sobremedida');
    if (modal) {
        modal.remove();
    }
};

/**
 * Guardar sobremedida en la estructura relacional
 */
window.guardarSobremedida = function(genero, cantidad) {
    genero = String(genero).toUpperCase();
    
    // Crear estructura especial para sobremedida
    if (!window.tallasRelacionales.SOBREMEDIDA) {
        window.tallasRelacionales.SOBREMEDIDA = {};
    }
    
    window.tallasRelacionales.SOBREMEDIDA[genero] = cantidad;
    
    console.log('[gestion-tallas] Sobremedida guardada:', {
        genero: genero,
        cantidad: cantidad,
        estado: window.tallasRelacionales.SOBREMEDIDA
    });
};

/**
 * Crear tarjeta de sobremedida en el DOM
 */
window.crearTarjetaSobremedida = function(genero, cantidad) {
    genero = String(genero).toUpperCase();
    
    // Marcar botón
    const btnSobremedida = document.getElementById('btn-genero-sobremedida');
    const checkMark = document.getElementById('check-sobremedida');
    
    if (btnSobremedida) {
        btnSobremedida.dataset.selected = 'true';
        btnSobremedida.style.borderColor = '#0066cc';
        btnSobremedida.style.background = '#f0f9ff';
    }
    
    if (checkMark) {
        checkMark.style.display = 'block';
    }
    
    // Obtener o crear contenedor
    const container = document.getElementById('tarjetas-generos-container');
    if (!container) return;
    
    // Eliminar tarjeta anterior si existe
    const tarjetaAnterior = container.querySelector('[data-sobremedida="true"]');
    if (tarjetaAnterior) {
        tarjetaAnterior.remove();
    }
    
    // Crear tarjeta compacta
    const tarjeta = document.createElement('div');
    tarjeta.dataset.sobremedida = 'true';
    tarjeta.style.cssText = `
        background: white;
        border: 2px solid #0066cc;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    `;
    
    // Header compacto
    const header = document.createElement('div');
    header.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; justify-content: space-between;';
    
    const headerLeft = document.createElement('div');
    headerLeft.style.cssText = 'display: flex; align-items: center; gap: 0.5rem;';
    headerLeft.innerHTML = `
        <span class="material-symbols-rounded" style="font-size: 1.25rem; color: #0066cc;">straighten</span>
        <div>
            <h4 style="margin: 0; color: #1f2937; font-size: 0.9rem; font-weight: 600;">SOBREMEDIDA</h4>
            <p style="margin: 0; color: #6b7280; font-size: 0.75rem;">${genero}</p>
        </div>
    `;
    header.appendChild(headerLeft);
    
    const btnGroupAcciones = document.createElement('div');
    btnGroupAcciones.style.cssText = 'display: flex; align-items: center; gap: 0.25rem;';
    
    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.title = 'Eliminar sobremedida';
    btnEliminar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.35rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 4px; font-size: 1rem;';
    btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1rem;">delete</span>';
    btnEliminar.onmouseover = () => {
        btnEliminar.style.color = '#ef4444';
        btnEliminar.style.background = '#fee2e2';
    };
    btnEliminar.onmouseout = () => {
        btnEliminar.style.color = '#6b7280';
        btnEliminar.style.background = 'transparent';
    };
    btnEliminar.onclick = () => {
        delete window.tallasRelacionales.SOBREMEDIDA;
        tarjeta.remove();
        
        const btn = document.getElementById('btn-genero-sobremedida');
        const check = document.getElementById('check-sobremedida');
        
        if (btn) {
            btn.dataset.selected = 'false';
            btn.style.borderColor = '#d1d5db';
            btn.style.background = 'white';
        }
        
        if (check) {
            check.style.display = 'none';
        }
        
        actualizarTotalPrendas();
        window.notificarCambioTallas('eliminar-sobremedida');
    };
    btnGroupAcciones.appendChild(btnEliminar);
    
    header.appendChild(btnGroupAcciones);
    tarjeta.appendChild(header);
    
    // Cantidad compacta y destacada
    const cantidadDiv = document.createElement('div');
    cantidadDiv.style.cssText = `
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        background: #f0f9ff;
        border-radius: 6px;
        padding: 0.75rem;
        border: 1px solid #bfdbfe;
    `;
    cantidadDiv.innerHTML = `
        <span class="material-symbols-rounded" style="font-size: 1.25rem; color: #0066cc;">shopping_bag</span>
        <div style="text-align: center;">
            <p style="margin: 0; color: #6b7280; font-size: 0.7rem; text-transform: uppercase; font-weight: 600;">Cantidad</p>
            <p style="margin: 0; color: #0066cc; font-size: 1.5rem; font-weight: 700;">${cantidad}</p>
        </div>
    `;
    tarjeta.appendChild(cantidadDiv);
    
    container.appendChild(tarjeta);
};

const originalObtenerTallasYCantidades = window.obtenerTallasYCantidades;
window.obtenerTallasYCantidades = function() {
    const resultado = originalObtenerTallasYCantidades.call(this);
    
    // Agregar sobremedida si existe
    if (window.tallasRelacionales.SOBREMEDIDA && Object.keys(window.tallasRelacionales.SOBREMEDIDA).length > 0) {
        resultado.SOBREMEDIDA = window.tallasRelacionales.SOBREMEDIDA;
        console.log('[gestion-tallas] Sobremedida incluida en resultado:', resultado.SOBREMEDIDA);
    }
    
    return resultado;
};

/**
 * Abrir modal para "SIN TALLA" en UNISEX
 * Permite guardar cantidad sin especificar una talla específica
 */
window.abrirModalCantidadSinTalla = async function() {
    // Cerrar el modal de tallas actual
    const genero = window.generoActualModal;
    cerrarModalTallas(genero);
    
    // Crear nuevo modal
    const modal = document.createElement('div');
    modal.id = 'modal-sintalla';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1060000;';
    
    const container = document.createElement('div');
    container.style.cssText = 'background: white; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); overflow: hidden;';
    
    // Header
    const header = document.createElement('div');
    header.style.cssText = 'background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); color: white; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;';
    
    const headerContent = document.createElement('div');
    headerContent.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
    headerContent.innerHTML = `<span class="material-symbols-rounded" style="font-size: 1.5rem;">package_2</span><h2 style="margin: 0; font-size: 1.25rem;">Cantidad sin Talla</h2>`;
    header.appendChild(headerContent);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: transparent; color: white; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;';
    btnCerrar.onclick = () => cerrarModalCantidadSinTalla();
    header.appendChild(btnCerrar);
    
    container.appendChild(header);
    
    // Content
    const content = document.createElement('div');
    content.style.cssText = 'padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem;';
    
    // Explicación
    const explicacion = document.createElement('p');
    explicacion.style.cssText = 'margin: 0; color: #6b7280; font-size: 0.95rem; line-height: 1.5;';
    explicacion.textContent = 'Ingresa la cantidad total sin especificar una talla específica. Ideal para prendas genéricas, a medida o confección especial.';
    content.appendChild(explicacion);
    
    // Input de Cantidad
    const cantidadLabel = document.createElement('label');
    cantidadLabel.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem; font-weight: 600; color: #1f2937;';
    cantidadLabel.innerHTML = '<span>Cantidad Total *</span>';
    
    const cantidadInput = document.createElement('input');
    cantidadInput.id = 'sintalla-cantidad';
    cantidadInput.type = 'number';
    cantidadInput.min = '1';
    cantidadInput.placeholder = 'Ej: 100';
    cantidadInput.style.cssText = 'padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 1rem; font-weight: 600;';
    
    // Enter para confirmar
    cantidadInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            confirmarCantidadSinTalla();
        }
    });
    
    cantidadLabel.appendChild(cantidadInput);
    content.appendChild(cantidadLabel);
    
    container.appendChild(content);
    
    // Footer
    const footer = document.createElement('div');
    footer.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid #e5e7eb;';
    
    const btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;';
    btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
    btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
    btnCancelar.onclick = () => cerrarModalCantidadSinTalla();
    footer.appendChild(btnCancelar);
    
    const btnConfirmar = document.createElement('button');
    btnConfirmar.type = 'button';
    btnConfirmar.textContent = 'Confirmar';
    btnConfirmar.style.cssText = 'background: #7c3aed; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;';
    btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#5b21b6';
    btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#7c3aed';
    btnConfirmar.onclick = () => confirmarCantidadSinTalla();
    footer.appendChild(btnConfirmar);
    
    container.appendChild(footer);
    modal.appendChild(container);
    
    document.body.appendChild(modal);
    
    // Focus en cantidad
    setTimeout(() => document.getElementById('sintalla-cantidad').focus(), 100);
};

/**
 * Confirmar cantidad sin talla para UNISEX
 */
window.confirmarCantidadSinTalla = function() {
    const cantidad = parseInt(document.getElementById('sintalla-cantidad').value) || 0;
    
    if (cantidad <= 0) {
        alert('La cantidad debe ser mayor a 0');
        document.getElementById('sintalla-cantidad').focus();
        return;
    }
    
    // Guardar en estructura relacional con talla especial "SIN_TALLA"
    if (!window.tallasRelacionales.UNISEX) {
        window.tallasRelacionales.UNISEX = {};
    }
    
    window.tallasRelacionales.UNISEX['SIN_TALLA'] = cantidad;
    
    console.log('[gestion-tallas] Cantidad sin talla guardada para UNISEX:', window.tallasRelacionales.UNISEX);
    
    // Cerrar modal
    cerrarModalCantidadSinTalla();
    
    // Crear tarjeta visual
    crearTarjetaUnisexSinTalla(cantidad);
    
    // Actualizar total
    actualizarTotalPrendas();
    window.notificarCambioTallas('confirmar-cantidad-sin-talla');
};

/**
 * Cerrar modal de cantidad sin talla
 */
window.cerrarModalCantidadSinTalla = function() {
    const modal = document.getElementById('modal-sintalla');
    if (modal) {
        modal.remove();
    }
};

/**
 * Crear tarjeta visual para UNISEX sin talla
 */
window.crearTarjetaUnisexSinTalla = function(cantidad) {
    // Marcar botón Unisex
    const btnUnisex = document.getElementById('btn-genero-unisex');
    const checkUnisex = document.getElementById('check-unisex');
    
    if (btnUnisex) {
        btnUnisex.dataset.selected = 'true';
        btnUnisex.style.borderColor = '#7c3aed';
        btnUnisex.style.background = '#f5f3ff';
    }
    
    if (checkUnisex) {
        checkUnisex.style.display = 'block';
    }
    
    // Obtener contenedor
    const container = document.getElementById('tarjetas-generos-container');
    if (!container) return;
    
    // Eliminar tarjeta anterior si existe
    const tarjetaAnterior = container.querySelector('[data-genero="UNISEX"]');
    if (tarjetaAnterior) {
        tarjetaAnterior.remove();
    }
    
    // Crear tarjeta
    const tarjeta = document.createElement('div');
    tarjeta.dataset.genero = 'UNISEX';
    tarjeta.style.cssText = `
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    `;
    
    // Header de tarjeta
    const header = document.createElement('div');
    header.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; justify-content: space-between;';
    
    const headerLeft = document.createElement('div');
    headerLeft.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
    headerLeft.innerHTML = `
        <span class="material-symbols-rounded" style="font-size: 1.5rem; color: #374151;">wc</span>
        <div>
            <h4 style="margin: 0; color: #1f2937; font-size: 1rem; font-weight: 600;">UNISEX - SIN TALLA</h4>
            <p style="margin: 0; color: #6b7280; font-size: 0.8rem;">Cantidad total: <strong>${cantidad}</strong></p>
        </div>
    `;
    header.appendChild(headerLeft);
    
    const btnGroupAcciones = document.createElement('div');
    btnGroupAcciones.style.cssText = 'display: flex; align-items: center; gap: 0.25rem;';
    
    const btnEditar = document.createElement('button');
    btnEditar.type = 'button';
    btnEditar.title = 'Editar cantidad';
    btnEditar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 6px;';
    btnEditar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem;">edit</span>';
    btnEditar.onmouseover = () => {
        btnEditar.style.color = '#7c3aed';
        btnEditar.style.background = '#f5f3ff';
    };
    btnEditar.onmouseout = () => {
        btnEditar.style.color = '#6b7280';
        btnEditar.style.background = 'transparent';
    };
    btnEditar.onclick = () => {
        abrirModalCantidadSinTalla();
    };
    btnGroupAcciones.appendChild(btnEditar);
    
    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.title = 'Eliminar';
    btnEliminar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 6px;';
    btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem;">delete</span>';
    btnEliminar.onmouseover = () => {
        btnEliminar.style.color = '#ef4444';
        btnEliminar.style.background = '#fee2e2';
    };
    btnEliminar.onmouseout = () => {
        btnEliminar.style.color = '#6b7280';
        btnEliminar.style.background = 'transparent';
    };
    btnEliminar.onclick = () => {
        delete window.tallasRelacionales.UNISEX['SIN_TALLA'];
        if (Object.keys(window.tallasRelacionales.UNISEX).length === 0) {
            delete window.tallasRelacionales.UNISEX;
        }
        tarjeta.remove();
        
        const btn = document.getElementById('btn-genero-unisex');
        const check = document.getElementById('check-unisex');
        
        if (btn) {
            btn.dataset.selected = 'false';
            btn.style.borderColor = '#d1d5db';
            btn.style.background = 'white';
        }
        
        if (check) {
            check.style.display = 'none';
        }
        
        actualizarTotalPrendas();
        window.notificarCambioTallas('eliminar-unisex-sin-talla');
    };
    btnGroupAcciones.appendChild(btnEliminar);
    
    header.appendChild(btnGroupAcciones);
    tarjeta.appendChild(header);
    
    container.appendChild(tarjeta);
};
