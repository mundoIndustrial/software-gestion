/**
 * Renderizador de Tarjetas de Procesos
 * Muestra las tarjetas de procesos configurados dentro del modal de prenda
 */

(function() {
    'use strict';

const PROCESOS_ICONOS = Object.freeze({
    reflectivo: '<span class="material-symbols-rounded" style="color: #f59e0b;">wb_twilight</span>',
    bordado: '<span class="material-symbols-rounded" style="color: #1e40af;">auto_awesome</span>',
    estampado: '<span class="material-symbols-rounded" style="color: #ec4899;">format_paint</span>',
    dtf: '<span class="material-symbols-rounded" style="color: #06b6d4;">print</span>',
    sublimado: '<span class="material-symbols-rounded" style="color: #3b82f6;">water_drop</span>'
});

const PROCESOS_NOMBRES = Object.freeze({
    reflectivo: 'Reflectivo',
    bordado: 'Bordado',
    estampado: 'Estampado',
    dtf: 'DTF',
    sublimado: 'Sublimado'
});

// Compatibilidad hacia atras para scripts legacy.
globalThis.iconosProcesos = PROCESOS_ICONOS;
globalThis.nombresProcesos = PROCESOS_NOMBRES;

function agregarStorageUrl(url) {
    if (!url || typeof url !== 'string') return '';
    if (url.startsWith('/')) return url;
    if (url.startsWith('http')) return url;
    if (url.startsWith('blob:')) return url;
    if (url.startsWith('data:')) return url;
    return '/storage/' + url;
}

function resolverUrlImagenProceso(img) {
    if (img instanceof File) {
        return URL.createObjectURL(img);
    }
    if (img?.previewUrl) {
        return img.previewUrl;
    }
    if (img?.dataURL) {
        return img.dataURL;
    }
    if (typeof img === 'string') {
        return agregarStorageUrl(img);
    }
    if (typeof img === 'object' && img) {
        const url = img.url || img.ruta || img.ruta_webp || img.ruta_original;
        return (typeof url === 'string') ? agregarStorageUrl(url) : '';
    }
    return '';
}

const RENDER_PROCESOS_CONTAINER_ID = 'contenedor-tarjetas-procesos';

function obtenerProcesosConDatos(procesos) {
    return Object.keys(procesos || {}).filter(tipo => {
        const tieneData = procesos[tipo]?.datos !== null && procesos[tipo]?.datos !== undefined;
        if (tieneData) {
            console.log(`   Tipo: ${tipo} ? Tiene datos`, procesos[tipo]?.datos);
        } else {
            console.log(`   Tipo: ${tipo} ? Sin datos`);
        }
        return tieneData;
    });
}

function normalizarModoTallasProceso(datosProceso) {
    if (!datosProceso?.modo_tallas) {
        datosProceso.modo_tallas = 'generico';
    }
    return datosProceso;
}

function construirEstadoRenderProcesos(container, procesos) {
    return {
        contenedorEncontrado: !!container,
        procesosKey: Object.keys(procesos || {}),
        procesosLength: Object.keys(procesos || {}).length,
        displayActual: container?.style?.display
    };
}

function construirMensajeSinProcesos() {
    return `
        <div style="text-align: center; padding: 1.5rem; color: #9ca3af; font-size: 0.875rem;">
            <span class="material-symbols-rounded" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;">add_circle</span>
            No hay procesos configurados. Marca un checkbox arriba para agregar procesos.
        </div>
    `;
}

function construirHtmlTarjetasProcesos(procesos, tiposConDatos) {
    let html = '';
    tiposConDatos.forEach(tipo => {
        const procesoCompleto = procesos[tipo];
        const datosProcess = normalizarModoTallasProceso(procesoCompleto.datos);

        console.log(`[RENDER-PROCESOS] Generando tarjeta para: ${tipo}`, {
            modo_tallas: datosProcess.modo_tallas,
            ubicacionesCount: Array.isArray(datosProcess.ubicaciones) ? datosProcess.ubicaciones.length : 0,
            ubicacionesValue: datosProcess.ubicaciones,
            imagenesCount: Array.isArray(datosProcess.imagenes) ? datosProcess.imagenes.length : 0,
            imagenesPreview: datosProcess.imagenes ? datosProcess.imagenes.slice(0, 1) : [],
            tallas: Object.keys(datosProcess.tallas?.dama || {}).length + Object.keys(datosProcess.tallas?.caballero || {}).length + Object.keys(datosProcess.tallas?.unisex || {}).length + Object.keys(datosProcess.tallas?.sobremedida || {}).length,
            observaciones: datosProcess.observaciones ? 'si' : 'no',
            tieneDatosExtendidos: !!datosProcess.datosExtendidos,
            datosExtendidosClaves: datosProcess.datosExtendidos ? Object.keys(datosProcess.datosExtendidos) : 'N/A'
        });

        html += generarTarjetaProceso(tipo, datosProcess);
    });
    return html;
}

function etiquetarTarjetasProceso(container) {
    container.querySelectorAll('.tarjeta-proceso').forEach(tarjeta => {
        const tipoMatch = tarjeta.className.match(/tipo-([a-z]+)/);
        if (tipoMatch) {
            tarjeta.setAttribute('data-tipo-proceso', tipoMatch[1]);
        }
    });
}

function mostrarContenedorProcesos(container) {
    container.style.display = 'block';
    container.style.visibility = 'visible';
    container.style.opacity = '1';
}

function configurarDragDropSiDisponible() {
    console.log('[RENDER-PROCESOS] Verificando configuracion de drag & drop');
    console.log('[RENDER-PROCESOS] configurarDragDropProcesos disponible:', typeof configurarDragDropProcesos);

    if (typeof configurarDragDropProcesos !== 'function') {
        console.warn('[RENDER-PROCESOS] configurarDragDropProcesos no disponible');
        return;
    }

    console.log('[RENDER-PROCESOS] Llamando a configurarDragDropProcesos()');
    configurarDragDropProcesos();
    console.log('[RENDER-PROCESOS] Drag & drop configurado para procesos');
}
/**
 * Renderizar todas las tarjetas de procesos en el modal de prenda - OPTIMIZADO
 * Usa batch rendering para evitar reflows multiples
 */
globalThis.renderizarTarjetasProcesos = function() {
    const container = document.getElementById(RENDER_PROCESOS_CONTAINER_ID);

    if (!container) {
        console.error('[RENDER-PROCESOS] No se encontro contenedor', {
            contenedorId: RENDER_PROCESOS_CONTAINER_ID,
            documento: document.body ? 'cargado' : 'no cargado'
        });
        return false;
    }

    const procesos = globalThis.procesosSeleccionados || {};
    console.log('[RENDER-PROCESOS] Iniciando renderizacion', construirEstadoRenderProcesos(container, procesos));

    const procesosConDatos = obtenerProcesosConDatos(procesos);

    console.log(' [RENDER-PROCESOS] Procesos a renderizar:', {
        total: procesosConDatos.length,
        tipos: procesosConDatos
    });

    if (procesosConDatos.length === 0) {
        console.log('[RENDER-PROCESOS] Sin procesos con datos, mostrando mensaje vacio');
        container.innerHTML = construirMensajeSinProcesos();
        container.style.display = 'block';
        return false;
    }

    const html = construirHtmlTarjetasProcesos(procesos, procesosConDatos);

    console.log('[RENDER-PROCESOS] HTML generado:', {
        htmlLength: html.length,
        htmlPreview: html.substring(0, 100)
    });

    container.innerHTML = html;
    etiquetarTarjetasProceso(container);
    mostrarContenedorProcesos(container);
    configurarDragDropSiDisponible();

    console.log(' [RENDER-PROCESOS] Renderizacion completada', {
        tarjetasRenderizadas: container.querySelectorAll('.tarjeta-proceso').length,
        displayStyle: container.style.display,
        visibilityStyle: container.style.visibility,
        opacityStyle: container.style.opacity
    });
    return true;
};
/**
 * Generar HTML de una tarjeta de proceso - VERSION SIMPLIFICADA
 */
function generarTarjetaProceso(tipo, datos) {
    if (globalThis.ProcesoCardRendererService && typeof globalThis.ProcesoCardRendererService.generarTarjetaProceso === 'function') {
        return globalThis.ProcesoCardRendererService.generarTarjetaProceso(tipo, datos);
    }

    console.error('[RENDER-PROCESOS] ProcesoCardRendererService no disponible');
    return '';
}
const ProcesoEditService = {
    obtenerProceso(tipo) {
        return globalThis.procesosSeleccionados?.[tipo] || null;
    },

    esModoPorTallas(proceso) {
        const modoTallas = proceso?.datos?.modo_tallas || 'generico';
        return modoTallas === 'general' || modoTallas === 'especifico';
    },

    abrirModalPorTallas(tipo) {
        if (globalThis.ProcesoPorTallasController?.abrirModalProcesoPorTallas) {
            globalThis.ProcesoPorTallasController.abrirModalProcesoPorTallas(tipo);
        }
    },

    abrirModalGenerico(tipo, esEdicion = false) {
        const controller = globalThis.ProcesoModalController;
        if (controller?.abrir) {
            controller.abrir(tipo, esEdicion);
            return true;
        }
        if (globalThis.abrirModalProcesoGenerico) {
            globalThis.abrirModalProcesoGenerico(tipo, esEdicion);
            return true;
        }
        return false;
    },

    editarDesdeModal(tipo) {
        console.log(' [EDITAR-PROCESO-MODAL] Iniciando edicion de proceso existente:', tipo);
        
        // Obtener datos del proceso
        const proceso = this.obtenerProceso(tipo);

        // Detectar si fue guardado como "Por Tallas" y abrir el modal correcto
        // CRITICO: Buscar en orden de prioridad correcto
        const modoTallas = proceso?.datos?.modo_tallas || 'generico';
        console.log(' [EDITAR-PROCESO-MODAL] Modo de tallas detectado:', modoTallas, {
            datos_modo_tallas: proceso?.datos?.modo_tallas,
            procesoCompleto: proceso
        });
        
        if (this.esModoPorTallas(proceso)) {
            console.log(' [EDITAR-PROCESO-MODAL] Detectado modo POR TALLAS, abriendo modal por tallas');
            this.abrirModalPorTallas(tipo);
            return;
        }

        console.log(' [EDITAR-PROCESO-MODAL] ESTRUCTURA COMPLETA del proceso:', {
            procesoCompleto: proceso,
            datos: proceso?.datos,
            todasLasKeys: proceso?.datos ? Object.keys(proceso.datos) : [],
            valoresEspecificos: {
                modo_tallas: proceso?.datos?.modo_tallas,
                modo_tallas_tipo: typeof proceso?.datos?.modo_tallas,
                datosExtendidos: proceso?.datos?.datosExtendidos,
                tieneDatosExtendidos: !!proceso?.datos?.datosExtendidos,
                tipoProceso: proceso?.datos?.tipoProceso,
                ubicaciones: proceso?.datos?.ubicaciones,
                tallas: proceso?.datos?.tallas
            }
        });

        if (!proceso?.datos) {
            console.error(' [EDITAR-PROCESO-MODAL] No hay datos para el proceso:', tipo);
            return;
        }
        
        //  PASO 1: Iniciar el gestor de edicion (marca como "en edicion")
        if (globalThis.gestorEditacionProcesos) {
            globalThis.gestorEditacionProcesos.iniciarEdicion(tipo, false); // false = no es nuevo
            console.log(' [EDITAR-PROCESO-MODAL] Gestor de edicion iniciado para:', tipo);
        }
        
        //  PASO 2: Iniciar editor de procesos (captura estado original)
        if (globalThis.procesosEditor) {
            const exito = globalThis.procesosEditor.iniciarEdicion(tipo, proceso.datos);
            if (!exito) {
                console.error(' [EDITAR-PROCESO-MODAL] No se pudo iniciar editor de procesos');
                return;
            }
            console.log(' [EDITAR-PROCESO-MODAL] Editor de procesos iniciado en modo EDICION');
        }
        
        //  PASO 3: Cargar datos en el modal ANTES de abrirlo
        console.log(' [EDITAR-PROCESO-MODAL] Cargando datos en modal...');
        if (typeof globalThis.cargarDatosProcesoEnModal === 'function') {
            globalThis.cargarDatosProcesoEnModal(tipo, proceso.datos);
        } else {
            console.error('[EDITAR-PROCESO-MODAL] cargarDatosProcesoEnModal no disponible');
        }
        
        //  PASO 4: Abrir modal en modo EDICION
        if (this.abrirModalGenerico(tipo, true)) {
            console.log(' [EDITAR-PROCESO-MODAL] Abriendo modal generico en modo EDICION');
            
            const swalContainer = document.querySelector('.swal2-container');
            const swalPopup = document.querySelector('.swal2-popup');
            console.log(' [EDITAR-PROCESO-MODAL] Swal2 visible?:', !!swalContainer);
            console.log(' [EDITAR-PROCESO-MODAL] Swal2 popup existe?:', !!swalPopup);
            if (swalContainer) {
                console.log(' [EDITAR-PROCESO-MODAL] Swal2 z-index:', globalThis.getComputedStyle(swalContainer).zIndex);
            }
            
            // apertura delegada a abrirModalGenerico

            const procesoTieneTallasGuardadas = (() => {
                const t = proceso?.datos?.tallas;
                if (!t || typeof t !== 'object') return false;
                const total = Object.keys(t.dama || {}).length + Object.keys(t.caballero || {}).length + Object.keys(t.unisex || {}).length + Object.keys(t.sobremedida || {}).length;
                return total > 0;
            })();

            // Sincronizar desde la prenda SOLO como fallback cuando el proceso NO tiene tallas guardadas.
            // Si se ejecuta en edicion con tallas (especialmente talla__color), pisa la configuracion del proceso.
            if (!procesoTieneTallasGuardadas) {
                setTimeout(() => {
                    // Copiar tallas de globalThis.tallasRelacionales a globalThis.tallasCantidadesProceso
                    if (globalThis.tallasRelacionales) {
                        console.log('[EDITAR-PROCESO-MODAL]  Sincronizando tallas desde prenda a proceso (fallback sin tallas guardadas)...');
                        console.log('[EDITAR-PROCESO-MODAL]  globalThis.tallasRelacionales:', globalThis.tallasRelacionales);

                        // Inicializar si no existe
                        if (!globalThis.tallasCantidadesProceso) {
                            globalThis.tallasCantidadesProceso = { dama: {}, caballero: {}, unisex: {}, sobremedida: {} };
                        }

                        if (!globalThis.tallasSeleccionadasProceso) {
                            globalThis.tallasSeleccionadasProceso = { dama: [], caballero: [], unisex: [], sobremedida: {} };
                        }

                        // Copiar DAMA - PROCESAR CORRECTAMENTE si tiene SOBREMEDIDA anidada
                        if (globalThis.tallasRelacionales.DAMA && Object.keys(globalThis.tallasRelacionales.DAMA).length > 0) {
                            globalThis.tallasCantidadesProceso.dama = {};
                            const tallasDama = [];

                            //  FIX: Si DAMA tiene SOBREMEDIDA (numero o objeto anidado), EXTRAERLA
                            for (const [talla, valor] of Object.entries(globalThis.tallasRelacionales.DAMA)) {
                                if (talla === 'SOBREMEDIDA') {
                                    // SOBREMEDIDA puede ser:
                                    // 1. Un NUMERO directo: 344  significa DAMA sobremedida
                                    // 2. Un OBJETO anidado: {DAMA: 34}  extraer por genero

                                    if (typeof valor === 'number') {
                                        // SOBREMEDIDA como numero: es para DAMA (genero actual)
                                        globalThis.tallasCantidadesProceso.sobremedida['DAMA'] = valor;
                                        console.log('[EDITAR-PROCESO-MODAL]  DAMA SOBREMEDIDA (numero) extraida:', valor);
                                    } else if (typeof valor === 'object' && valor !== null) {
                                        // SOBREMEDIDA anidada: {DAMA: 34, CABALLERO: 20}
                                        for (const [genero, cantidad] of Object.entries(valor)) {
                                            globalThis.tallasCantidadesProceso.sobremedida[genero] = cantidad;
                                        }
                                        console.log('[EDITAR-PROCESO-MODAL]  DAMA SOBREMEDIDA (objeto) extraida:', valor);
                                    }
                                } else {
                                    // Otras tallas: copiar directamente
                                    globalThis.tallasCantidadesProceso.dama[talla] = valor;
                                    tallasDama.push(talla);
                                }
                            }
                            globalThis.tallasSeleccionadasProceso.dama = tallasDama;
                            console.log('[EDITAR-PROCESO-MODAL]  Tallas DAMA copiadas al proceso:', globalThis.tallasCantidadesProceso.dama);
                        }

                        // Copiar CABALLERO
                        if (globalThis.tallasRelacionales.CABALLERO && Object.keys(globalThis.tallasRelacionales.CABALLERO).length > 0) {
                            globalThis.tallasCantidadesProceso.caballero = {};
                            const tallasCaballero = [];

                            //  FIX: Mismo tratamiento para CABALLERO (numero o objeto anidado)
                            for (const [talla, valor] of Object.entries(globalThis.tallasRelacionales.CABALLERO)) {
                                if (talla === 'SOBREMEDIDA') {
                                    // SOBREMEDIDA puede ser numero o objeto
                                    if (typeof valor === 'number') {
                                        // SOBREMEDIDA como numero: es para CABALLERO
                                        globalThis.tallasCantidadesProceso.sobremedida['CABALLERO'] = valor;
                                        console.log('[EDITAR-PROCESO-MODAL]  CABALLERO SOBREMEDIDA (numero) extraida:', valor);
                                    } else if (typeof valor === 'object' && valor !== null) {
                                        // SOBREMEDIDA anidada: extraer por genero
                                        for (const [genero, cantidad] of Object.entries(valor)) {
                                            globalThis.tallasCantidadesProceso.sobremedida[genero] = cantidad;
                                        }
                                        console.log('[EDITAR-PROCESO-MODAL]  CABALLERO SOBREMEDIDA (objeto) extraida:', valor);
                                    }
                                } else {
                                    globalThis.tallasCantidadesProceso.caballero[talla] = valor;
                                    tallasCaballero.push(talla);
                                }
                            }
                            globalThis.tallasSeleccionadasProceso.caballero = tallasCaballero;
                            console.log('[EDITAR-PROCESO-MODAL]  Tallas CABALLERO copiadas al proceso:', globalThis.tallasCantidadesProceso.caballero);
                        }

                        // Copiar UNISEX si existe
                        if (globalThis.tallasRelacionales.UNISEX && Object.keys(globalThis.tallasRelacionales.UNISEX).length > 0) {
                            globalThis.tallasCantidadesProceso.unisex = { ...globalThis.tallasRelacionales.UNISEX };
                            globalThis.tallasSeleccionadasProceso.unisex = Object.keys(globalThis.tallasRelacionales.UNISEX);
                            console.log('[EDITAR-PROCESO-MODAL]  Tallas UNISEX copiadas al proceso:', globalThis.tallasCantidadesProceso.unisex);
                        }

                        console.log('[EDITAR-PROCESO-MODAL]  Tallas seleccionadas sincronizadas:', {
                            dama: globalThis.tallasSeleccionadasProceso.dama,
                            caballero: globalThis.tallasSeleccionadasProceso.caballero,
                            unisex: globalThis.tallasSeleccionadasProceso.unisex,
                            sobremedida: globalThis.tallasCantidadesProceso.sobremedida
                        });
                    }

                    // Renderizar el resumen con las tallas ya aplicadas
                    const actualizarResumen = globalThis.ProcesoModalController?.tallas?.actualizarResumen || globalThis.actualizarResumenTallasProceso;
                    if (typeof actualizarResumen === 'function') {
                        console.log('[EDITAR-PROCESO-MODAL]  Renderizando resumen de tallas automaticamente con "done_all"...');
                        actualizarResumen();
                        console.log('[EDITAR-PROCESO-MODAL]  Resumen de tallas renderizado con tallas aplicadas');
                    }
                }, 100);
            } else {
                console.log('[EDITAR-PROCESO-MODAL]  Se omite sync desde prenda: el proceso ya tiene tallas guardadas', {
                    tipo,
                    tallasGuardadas: proceso?.datos?.tallas
                });
            }
            
            // Verificar z-index despues de abrir
            setTimeout(() => {
                const modalProceso = document.getElementById('modal-proceso-generico');
                const swal = document.querySelector('.swal2-container');
                
                // Forzar z-index maximo para asegurar que este encima de todo
                if (modalProceso) {
                    modalProceso.style.setProperty('z-index', '9999999999', 'important');
                    console.log(' [EDITAR-PROCESO-MODAL] Z-index forzado dinamicamente:', globalThis.getComputedStyle(modalProceso).zIndex);
                }
                
                console.log(' [EDITAR-PROCESO-MODAL] DESPUES de abrirModalProcesoGenerico:');
                console.log('   - Modal proceso existe?:', !!modalProceso);
                if (modalProceso) {
                    console.log('   - Modal proceso z-index (inline):', modalProceso.style.zIndex);
                    console.log('   - Modal proceso z-index (computed):', globalThis.getComputedStyle(modalProceso).zIndex);
                    console.log('   - Modal proceso display:', globalThis.getComputedStyle(modalProceso).display);
                    console.log('   - Modal proceso classList:', modalProceso.className);
                }
                console.log('   - Swal2 existe?:', !!swal);
                if (swal) {
                    console.log('   - Swal2 z-index:', globalThis.getComputedStyle(swal).zIndex);
                }
                console.log('   - Elementos en body:', document.body.children.length);
                
                // Listar top 5 elementos con z-index alto
                const elementos = document.querySelectorAll('[style*="z-index"]');
                console.log('   - Elementos con z-index:', elementos.length);
                const conZAlto = Array.from(elementos).filter(el => {
                    const z = parseInt(globalThis.getComputedStyle(el).zIndex);
                    return z > 90000;
                }).sort((a, b) => {
                    const zA = parseInt(globalThis.getComputedStyle(a).zIndex);
                    const zB = parseInt(globalThis.getComputedStyle(b).zIndex);
                    return zB - zA;
                });
                console.log('   - Top elementos con z-index alto:');
                conZAlto.slice(0, 5).forEach(el => {
                    console.log(`     âœ“ ${el.tagName}#${el.id || '(sin-id)'}.${el.className || '(sin-class)'}: z=${globalThis.getComputedStyle(el).zIndex}`);
                });
            }, 100);
            
            // Marcar claramente que estamos en modo edicion
            const modalProceso = document.getElementById('modal-proceso-generico');
            if (modalProceso) {
                modalProceso.setAttribute('data-modo-edicion', 'true');
                modalProceso.setAttribute('data-tipo-proceso-editando', tipo);
                console.log(' [EDITAR-PROCESO-MODAL] Modal marcado como modo edicion');
            }
        } else {
            console.error(' [EDITAR-PROCESO-MODAL] No existe apertura de modal de proceso');
        }
    },

    editar(tipo) {
        // Detectar si fue guardado como "Por Tallas" y abrir el modal correcto
        const proceso = this.obtenerProceso(tipo);
        if (proceso?.datos?.datosExtendidos || proceso?.datos?.modo_tallas === 'especifico') {
            console.log(' [EDITAR-PROCESO] Detectado modo POR TALLAS, abriendo modal por tallas');
            this.abrirModalPorTallas(tipo);
            return;
        }
        
        // Abrir modal del proceso
        if (this.abrirModalGenerico(tipo, true)) {
            // apertura delegada a abrirModalGenerico
            
            // Cargar datos existentes en el modal
            if (proceso?.datos) {
                if (typeof globalThis.cargarDatosProcesoEnModal === 'function') {
                    globalThis.cargarDatosProcesoEnModal(tipo, proceso.datos);
                } else {
                    console.error('[EDITAR-PROCESO] cargarDatosProcesoEnModal no disponible');
                }
            }
        }
    }
};

globalThis.editarProcesoDesdeModal = function(tipo) {
    return ProcesoEditService.editarDesdeModal(tipo);
};

/**
 * Editar un proceso existente
 */
globalThis.editarProceso = function(tipo) {
    return ProcesoEditService.editar(tipo);
};

// Carga de datos del modal movida a proceso-modal-loader-service.js

// Eliminar proceso localmente (UI)
function eliminarProcesoLocalmente(tipo) {
    // Eliminar del estado
    if (globalThis.procesosSeleccionados && globalThis.procesosSeleccionados[tipo]) {
        delete globalThis.procesosSeleccionados[tipo];
    }
    
    // Desmarcar checkbox
    const checkbox = document.getElementById(`checkbox-${tipo}`);
    if (checkbox) {
        checkbox.checked = false;
    }
    
    // Re-renderizar
    globalThis.renderizarTarjetasProcesos();
    
    // Actualizar resumen
    if (globalThis.actualizarResumenProcesos) {
        globalThis.actualizarResumenProcesos();
    }
}

/**
 * API del modulo frontend (fachada estable).
 * Mantiene un punto unico de acceso y reduce acoplamiento directo a globals sueltos.
 */
globalThis.RenderizadorTarjetasProcesosModule = Object.freeze({
    renderizar: () => globalThis.renderizarTarjetasProcesos(),
    editarDesdeModal: (tipo) => globalThis.editarProcesoDesdeModal(tipo),
    editar: (tipo) => globalThis.editarProceso(tipo),
    abrirGaleria: (tipo) => globalThis.abrirGaleriaImagenesProceso(tipo),
    navegarGaleria: (direccion) => globalThis.navegarGaleriaImagenesProceso(direccion),
    irAImagenGaleria: (indice) => globalThis.irAImagenProceso(indice),
    cerrarGaleria: () => globalThis.cerrarGaleriaImagenesProceso(),
    eliminarTarjeta: (tipo) => globalThis.eliminarTarjetaProceso(tipo),
    eliminarMarcadosDelBackend: () => globalThis.eliminarProcesossMarcadosDelBackend()
});

})();
