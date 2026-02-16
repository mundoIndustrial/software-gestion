/**
 * 🔌 ADAPTER: Prenda Editor para Pedidos/Index
 * 
 * Proporciona las funciones globales necesarias para que el modal
 * modal-agregar-prenda-nueva funcione en el contexto de pedidos/index.blade.php
 * (edición de prendas existentes en pedidos ya creados).
 * 
 * Flujo de datos:
 *   1. editarPrendaDePedido() → GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
 *      → Trae datos completos de BD (tallas, colores, telas, procesos, imágenes, variantes)
 *   2. cargarPrendaEnModal() → Abre modal y carga datos en formulario
 *   3. agregarPrendaNueva() → POST /asesores/pedidos/{id}/actualizar-prenda
 *      → Guarda cambios usando actualizarPrendaCompleta
 * 
 * Tablas consultadas:
 *   prendas_pedido, prenda_pedido_variantes, prenda_pedido_tallas,
 *   prenda_pedido_talla_colores, prenda_pedido_colores_telas,
 *   prenda_fotos_tela_pedido, prenda_fotos_pedido,
 *   pedidos_procesos_prenda_detalles, pedidos_procesos_imagenes
 * 
 * Dependencias:
 *   PrendaEditor, PrendaModalManager, PrendaFormCollector, SweetAlert2
 */
(function() {
    'use strict';

    console.log('[PedidosAdapter] ✅ Cargado');

    // Detectar contexto: supervisor vs asesor basado en la URL actual
    function _getUrlPrefix() {
        const path = window.location.pathname;
        if (path.startsWith('/supervisor-pedidos')) {
            return { fetch: '/supervisor-pedidos', save: '/supervisor-pedidos', context: 'supervisor' };
        }
        return { fetch: '/asesores/pedidos-produccion', save: '/asesores/pedidos', context: 'asesor' };
    }

    // ====================================================
    // 1. INICIALIZAR PrendaEditor global
    // ====================================================
    function initPrendaEditor() {
        if (typeof PrendaEditor !== 'undefined' && !window.prendaEditorGlobal) {
            window.prendaEditorGlobal = new PrendaEditor({
                modalId: 'modal-agregar-prenda-nueva'
            });
            console.log('[PedidosAdapter] ✅ prendaEditorGlobal creado');
        }
    }

    // Intentar crear al cargar DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(initPrendaEditor, 300));
    } else {
        setTimeout(initPrendaEditor, 300);
    }

    // Intentar cuando el lazy loader termine
    window.addEventListener('prendaEditorRefactoredReady', () => {
        setTimeout(initPrendaEditor, 100);
    });

    // ====================================================
    // 1b. FALLBACK: manejarImagenesPrenda (no cargado en pedidos/index)
    // ====================================================
    if (typeof window.manejarImagenesPrenda !== 'function') {
        window.manejarImagenesPrenda = function(input) {
            if (!input.files || input.files.length === 0) return;
            try {
                if (!window.imagenesPrendaStorage) {
                    console.warn('[PedidosAdapter] imagenesPrendaStorage no disponible');
                    return;
                }
                window.imagenesPrendaStorage.agregarImagen(input.files[0])
                    .then(() => {
                        if (typeof window.actualizarPreviewPrenda === 'function') {
                            window.actualizarPreviewPrenda();
                        }
                    })
                    .catch(err => {
                        console.error('[PedidosAdapter] Error al agregar imagen:', err.message);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Error', text: err.message === 'MAX_LIMIT' ? 'Máximo 3 imágenes por prenda' : 'Error al procesar imagen: ' + err.message });
                        }
                    });
            } catch (err) {
                console.error('[PedidosAdapter] Error procesando imagen:', err);
            }
            input.value = '';
        };
        console.log('[PedidosAdapter] ✅ manejarImagenesPrenda definida (fallback)');
    }

    if (typeof window.actualizarPreviewPrenda !== 'function') {
        window.actualizarPreviewPrenda = function() {
            const preview = document.getElementById('nueva-prenda-foto-preview');
            if (!preview || !window.imagenesPrendaStorage) return;
            
            const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
            if (typeof PrendaEditorImagenes !== 'undefined') {
                PrendaEditorImagenes._actualizarPreviewDOM(preview);
                // Reconfigurar click handler de galería
                if (imagenes.length > 0) {
                    PrendaEditorImagenes._configurarClickGaleria(preview, imagenes);
                }
            } else {
                // Fallback mínimo sin PrendaEditorImagenes
                preview.innerHTML = '';
                imagenes.forEach((img, idx) => {
                    const container = document.createElement('div');
                    container.style.cssText = 'position: relative; margin-bottom: 0.5rem;';
                    const imgEl = document.createElement('img');
                    imgEl.src = img.previewUrl || img.url || img.ruta || '';
                    imgEl.alt = `Imagen ${idx + 1}`;
                    imgEl.style.cssText = 'max-width: 100%; height: auto; border-radius: 4px;';
                    container.appendChild(imgEl);
                    preview.appendChild(container);
                });
                if (imagenes.length === 0) {
                    preview.innerHTML = '<div class="foto-preview-content"><div class="material-symbols-rounded">add_photo_alternate</div><div class="foto-preview-text">Click para seleccionar o<br>Ctrl+V para pegar imagen</div></div>';
                }
            }
            
            const contador = document.getElementById('nueva-prenda-foto-contador');
            if (contador) contador.textContent = imagenes.length > 0 ? (imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos') : '';
            
            const btn = document.getElementById('nueva-prenda-foto-btn');
            if (btn) btn.style.display = imagenes.length < 3 ? 'block' : 'none';
        };
        console.log('[PedidosAdapter] ✅ actualizarPreviewPrenda definida (fallback)');
    }

    // ====================================================
    // 2. cerrarModalPrendaNueva — Cierra el modal de prenda
    // ====================================================
    window.cerrarModalPrendaNueva = function() {
        console.log('[PedidosAdapter] Cerrando modal de prenda');

        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'none';
        }

        // Restaurar scroll del body
        document.body.style.overflow = '';
        document.body.classList.remove('modal-open');

        // Reset FSM si existe
        if (window.__MODAL_FSM__ && typeof window.__MODAL_FSM__.forceReset === 'function') {
            window.__MODAL_FSM__.forceReset();
        } else if (window.__MODAL_FSM__) {
            window.__MODAL_FSM__.state = 'CLOSED';
        }

        // Reset PrendaModalManager
        if (typeof PrendaModalManager !== 'undefined') {
            try {
                PrendaModalManager.limpiar('modal-agregar-prenda-nueva');
            } catch (e) {
                console.warn('[PedidosAdapter] Error limpiando modal:', e);
            }
        }

        // Limpiar estado
        window.prendaActual = null;
        window.prendaEditIndex = null;

        // Limpiar arrays temporales
        window.telasAgregadas = [];
        window.telasCreacion = [];
        window.tallasRelacionales = null;
        window.procesosSeleccionados = [];

        // Resetear formulario
        const form = document.getElementById('form-prenda-nueva');
        if (form) form.reset();

        // Limpiar previews de imágenes
        const previewContainer = document.getElementById('imagenes-prenda-preview');
        if (previewContainer) previewContainer.innerHTML = '';

        const telasBody = document.getElementById('tbody-telas');
        if (telasBody) {
            // Preservar la fila de inputs (para agregar nuevas telas)
            const filaInputs = telasBody.querySelector('#nueva-prenda-tela')?.closest('tr');
            const filas = telasBody.querySelectorAll('tr');
            filas.forEach(fila => {
                if (fila !== filaInputs) fila.remove();
            });
            // Limpiar valores de la fila de inputs
            if (filaInputs) {
                filaInputs.querySelectorAll('input[type="text"]').forEach(inp => inp.value = '');
                const preview = filaInputs.querySelector('#nueva-prenda-tela-preview');
                if (preview) { preview.innerHTML = ''; preview.style.display = 'none'; }
            }
        }

        // Resetear botón a estado original
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.textContent = '💾 Agregar Prenda';
            btnGuardar.className = 'btn btn-primary';
        }

        // Si estábamos editando desde pedido, volver a la lista de prendas
        if (window._editandoPrendaDePedido) {
            const context = window._editandoPrendaDePedido;
            window._editandoPrendaDePedido = null;
            
            // Re-abrir lista de prendas del pedido
            setTimeout(function() {
                if (typeof window.abrirEditarPrendas === 'function') {
                    abrirEditarPrendas();
                }
            }, 250);
        }
    };

    // ====================================================
    // 3. agregarPrendaNueva — Guardar prenda editada (POST API)
    // ====================================================
    window.agregarPrendaNueva = function() {
        console.log('[PedidosAdapter] Guardando prenda editada');

        const editContext = window._editandoPrendaDePedido;
        if (!editContext) {
            console.warn('[PedidosAdapter] No hay contexto de edición de pedido');
            // Intentar con contexto mínimo si prendaActual existe
            if (!window.prendaActual) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'No se encontró contexto de edición', 'error');
                }
                return;
            }
        }

        const pedidoId = editContext?.pedidoId || window.datosEdicionPedido?.id;
        const prendaId = editContext?.prendaId || window.prendaActual?.id || window.prendaActual?.prenda_pedido_id;
        const prendaIndex = editContext?.prendaIndex;

        if (!pedidoId || !prendaId) {
            console.error('[PedidosAdapter] Faltan pedidoId o prendaId:', { pedidoId, prendaId });
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se pudo identificar el pedido o la prenda para guardar', 'error');
            }
            return;
        }

        // Recolectar datos del formulario
        let datosModificados = _recolectarDatosFormulario(prendaIndex);
        if (!datosModificados) {
            console.error('[PedidosAdapter] No se pudieron recolectar datos del formulario');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Validación', 'Por favor completa los datos requeridos de la prenda', 'warning');
            }
            return;
        }

        console.log('[PedidosAdapter] Datos a guardar:', datosModificados);

        // Enviar PUT API
        _guardarPrendaEnAPI(pedidoId, prendaId, datosModificados, prendaIndex);
    };

    /**
     * Recolectar datos del formulario del modal
     * @private
     */
    function _recolectarDatosFormulario(prendaIndex) {
        // Intentar usar PrendaFormCollector si está disponible
        if (typeof PrendaFormCollector !== 'undefined') {
            try {
                const collector = new PrendaFormCollector();
                // Crear un notificationService simple basado en SweetAlert
                collector.setNotificationService({
                    error: function(msg) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Validación', msg, 'warning');
                        }
                    }
                });
                const datos = collector.construirPrendaDesdeFormulario(
                    prendaIndex,
                    window.datosEdicionPedido?.prendas || []
                );
                return datos;
            } catch (e) {
                console.error('[PedidosAdapter] Error en PrendaFormCollector:', e);
            }
        }

        // Fallback: recolectar TODOS los campos manualmente
        const nombre = document.getElementById('nueva-prenda-nombre')?.value?.trim();
        const descripcion = document.getElementById('nueva-prenda-descripcion')?.value?.trim();
        const origenSelect = document.getElementById('nueva-prenda-origen-select')?.value || 'bodega';

        if (!nombre) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Validación', 'El nombre de la prenda es requerido', 'warning');
            }
            return null;
        }

        // Recolectar variantes del DOM
        const variantes = _recolectarVariantesDelDOM();

        // Recolectar imágenes del storage
        let imagenesStorage = window.imagenesPrendaStorage?.obtenerImagenes?.() || [];
        
        // FALLBACK: Si el storage está vacío pero hay snapshot (imágenes originales de BD),
        // usar el snapshot. Esto protege contra resets accidentales del storage.
        if (imagenesStorage.length === 0 && window.imagenesPrendaStorage?.snapshotOriginal?.length > 0) {
            console.log('[PedidosAdapter] ⚠️ Storage vacío pero snapshot tiene', window.imagenesPrendaStorage.snapshotOriginal.length, 'imágenes - usando snapshot como fallback');
            imagenesStorage = window.imagenesPrendaStorage.snapshotOriginal;
        }
        
        const imagenes = imagenesStorage.map(img => {
            if (img instanceof File) return img;
            if (img?.file instanceof File) return { file: img.file, previewUrl: img.previewUrl, nombre: img.nombre };
            if (img?.url?.startsWith('/')) return { url: img.url, id: img.id, urlDesdeDB: true };
            if (img?.ruta?.startsWith('/')) return { ruta: img.ruta, id: img.id, urlDesdeDB: true };
            if (img?.previewUrl?.startsWith('/storage/')) return { url: img.previewUrl, id: img.id, urlDesdeDB: true };
            return img;
        });

        // Deep copy de procesos
        let procesos = {};
        if (window.procesosSeleccionados && typeof window.procesosSeleccionados === 'object') {
            Object.entries(window.procesosSeleccionados).forEach(([tipo, proc]) => {
                procesos[tipo] = { ...proc };
            });
        }

        return {
            nombre_prenda: nombre,
            nombre: nombre,
            descripcion: descripcion,
            origen: origenSelect,
            de_bodega: origenSelect === 'bodega' ? 1 : 0,
            tallas: window.tallasRelacionales || {},
            procesos: procesos,
            telas: window.telasCreacion || window.telasAgregadas || [],
            variantes: variantes,
            imagenes: imagenes
        };
    }

    /**
     * Recolectar variantes (manga, bolsillos, broche) directamente del DOM
     * @private
     */
    function _recolectarVariantesDelDOM() {
        const variantes = {};
        const checkManga = document.getElementById('aplica-manga');
        if (checkManga && checkManga.checked) {
            const mangaInput = document.getElementById('manga-input');
            const mangaObs = document.getElementById('manga-obs');
            variantes.tipo_manga = mangaInput?.value?.trim() || '';
            variantes.obs_manga = mangaObs?.value || '';
            if (variantes.tipo_manga) {
                const datalist = document.getElementById('opciones-manga');
                if (datalist) {
                    for (let opt of datalist.options) {
                        if (opt.value.toLowerCase() === variantes.tipo_manga.toLowerCase()) {
                            variantes.tipo_manga_id = parseInt(opt.dataset.id);
                            break;
                        }
                    }
                }
            }
        }
        const checkBolsillos = document.getElementById('aplica-bolsillos');
        if (checkBolsillos && checkBolsillos.checked) {
            variantes.tiene_bolsillos = true;
            variantes.obs_bolsillos = document.getElementById('bolsillos-obs')?.value || '';
        }
        const checkBroche = document.getElementById('aplica-broche');
        if (checkBroche && checkBroche.checked) {
            const brocheInput = document.getElementById('broche-input');
            variantes.tipo_broche = brocheInput?.value || '';
            variantes.obs_broche = document.getElementById('broche-obs')?.value || '';
            const bVal = brocheInput?.value?.toLowerCase() || '';
            if (bVal === 'broche') variantes.tipo_broche_boton_id = 1;
            else if (bVal === 'boton') variantes.tipo_broche_boton_id = 2;
        }
        return variantes;
    }

    /**
     * Enviar datos al servidor via POST (actualizarPrendaCompleta)
     * Endpoint: POST /asesores/pedidos/{pedidoId}/actualizar-prenda
     * @private
     */
    async function _guardarPrendaEnAPI(pedidoId, prendaId, datos, prendaIndex) {
        try {
            // Pedir novedad/justificación del cambio (requerido por el backend)
            const novedad = await _pedirNovedad();
            if (novedad === null) return; // cancelado

            // Mostrar loading con z-index alto
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Guardando cambios...',
                    text: 'Actualizando la prenda',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    customClass: { container: 'swal-galeria-container' },
                    didOpen: () => Swal.showLoading()
                });
            }

            // Construir FormData para soportar imágenes
            const formData = new FormData();
            formData.append('prenda_id', prendaId);
            formData.append('nombre_prenda', datos.nombre_prenda || datos.nombre || '');
            formData.append('descripcion', datos.descripcion || '');
            formData.append('novedad', novedad);

            // Origen / de_bodega
            const origen = datos.origen || (datos.de_bodega == 1 ? 'bodega' : 'confeccion');
            formData.append('origen', origen);
            formData.append('de_bodega', datos.de_bodega !== undefined ? datos.de_bodega : (origen === 'bodega' ? 1 : 0));

            // Tallas (JSON) - solo enviar si hay datos reales (no objeto vacío {})
            const tallasData = datos.tallas || datos.cantidad_talla || null;
            if (tallasData && typeof tallasData === 'object' && Object.keys(tallasData).length > 0) {
                // Verificar que al menos un género tenga tallas con cantidad > 0
                const tieneDataReal = Object.values(tallasData).some(genero => 
                    typeof genero === 'object' && genero !== null && Object.keys(genero).filter(k => !k.startsWith('_')).length > 0
                );
                if (tieneDataReal) {
                    formData.append('tallas', JSON.stringify(tallasData));
                }
            }

            // Variantes (JSON)
            if (datos.variantes && Object.keys(datos.variantes).length > 0) {
                formData.append('variantes', JSON.stringify(datos.variantes));
            }

            // Asignaciones de colores por talla
            // 🔴 NUEVO: Enviar SIEMPRE si existe (incluso vacío {} para señalar "eliminar todo")
            if (datos.asignacionesColoresPorTalla !== undefined && datos.asignacionesColoresPorTalla !== null) {
                formData.append('asignaciones_colores', JSON.stringify(datos.asignacionesColoresPorTalla));
                console.log('[PedidosAdapter] 🎨 asignaciones_colores enviado:', JSON.stringify(datos.asignacionesColoresPorTalla));
            }

            // Telas (JSON) - usar telasAgregadas o telas
            const telas = datos.telasAgregadas || datos.colores_telas || datos.telas || [];
            if (telas.length > 0) {
                // Separar File objects de datos JSON
                const telasJSON = telas.map((t, idx) => {
                    const telaData = {
                        tela: t.tela || t.nombre_tela || '',
                        color: t.color || t.color_nombre || '',
                        referencia: t.referencia || '',
                        tela_id: t.tela_id || 0,
                        color_id: t.color_id || 0,
                        id: t.id || t._original_id || undefined
                    };
                    // Agregar File objects de imágenes de tela al FormData
                    if (t.imagenes && Array.isArray(t.imagenes)) {
                        t.imagenes.forEach((img, imgIdx) => {
                            if (img instanceof File) {
                                formData.append(`fotos_tela[${idx}]`, img);
                            } else if (img?.file instanceof File) {
                                formData.append(`fotos_tela[${idx}]`, img.file);
                            }
                        });
                    }
                    return telaData;
                });
                formData.append('colores_telas', JSON.stringify(telasJSON));
            }

            // Procesos (JSON) - transformar de objeto a array si es necesario
            if (datos.procesos) {
                let procesosRaw = datos.procesos;
                let procesosArray = [];
                
                if (!Array.isArray(procesosRaw) && typeof procesosRaw === 'object') {
                    // 🔴 PASO 1: Extraer File images ANTES de transformar (se pierden en JSON.stringify)
                    const filesPorProceso = {};
                    Object.entries(procesosRaw).forEach(([tipo, proc], idx) => {
                        const d = proc?.datos || proc || {};
                        const imagenes = d.imagenes || [];
                        filesPorProceso[idx] = [];
                        if (Array.isArray(imagenes)) {
                            imagenes.forEach(img => {
                                if (img instanceof File) {
                                    filesPorProceso[idx].push(img);
                                } else if (img?.file instanceof File) {
                                    filesPorProceso[idx].push(img.file);
                                }
                            });
                        }
                    });
                    
                    // 🔴 PASO 2: Transformar a array con imagenes_existentes (URLs para el backend)
                    procesosArray = Object.entries(procesosRaw).map(([tipo, proc]) => {
                        const d = proc?.datos || proc || {};
                        
                        const imagenesExistentes = [];
                        if (d.imagenes && Array.isArray(d.imagenes)) {
                            d.imagenes.forEach(img => {
                                if (img && !(img instanceof File) && !(img?.file instanceof File)) {
                                    const url = img.url || img.ruta_original || img.ruta_webp || img.ruta || img.previewUrl || '';
                                    const id = img.id || null;
                                    if (url || id) {
                                        imagenesExistentes.push({ id: id, url: url, ruta_original: img.ruta_original || url, ruta_webp: img.ruta_webp || '' });
                                    }
                                }
                            });
                        }
                        
                        return {
                            id: d.id || undefined,
                            tipo_proceso_id: d.tipo_proceso_id || undefined,
                            tipo: d.tipo || tipo,
                            nombre: d.nombre || tipo,
                            ubicaciones: d.ubicaciones || [],
                            observaciones: d.observaciones || '',
                            tallas: d.tallas || {},
                            estado: d.estado || 'PENDIENTE',
                            imagenes_existentes: imagenesExistentes
                        };
                    });
                    
                    // 🔴 PASO 3: Agregar File images al FormData
                    Object.entries(filesPorProceso).forEach(([idx, files]) => {
                        files.forEach(file => {
                            formData.append(`fotosProcesoNuevo_${idx}`, file);
                        });
                    });
                } else if (Array.isArray(procesosRaw)) {
                    procesosArray = procesosRaw;
                }
                
                formData.append('procesos', JSON.stringify(procesosArray));
                console.log('[PedidosAdapter] 🔧 Procesos enviados:', procesosArray.length, 'procesos');
            }

            // Imágenes de prenda - separar nuevas (File) de existentes (BD)
            const imagenesNuevas = [];
            const imagenesExistentes = [];
            const imgs = datos.imagenes || [];
            imgs.forEach((img) => {
                if (img instanceof File) {
                    imagenesNuevas.push(img);
                } else if (img?.file instanceof File) {
                    imagenesNuevas.push(img.file);
                } else if (img?.urlDesdeDB || img?.id || img?.url?.startsWith('/') || img?.ruta || img?.ruta_original || img?.ruta_webp || img?.previewUrl?.startsWith('/storage/')) {
                    const url = img.url || img.ruta || img.ruta_webp || img.ruta_original || img.previewUrl || '';
                    imagenesExistentes.push({ id: img.id, url: url });
                }
            });
            imagenesNuevas.forEach((file) => formData.append('imagenes[]', file));
            formData.append('imagenes_existentes', JSON.stringify(imagenesExistentes));
            
            // 🔴 NUEVO: Agregar imágenes marcadas para eliminación
            if (window.imagenesAEliminar && window.imagenesAEliminar.length > 0) {
                formData.append('imagenes_a_eliminar', JSON.stringify(window.imagenesAEliminar));
                console.log('[PedidosAdapter] 🗑️ Imágenes marcadas para eliminación:', window.imagenesAEliminar);
            }

            const urlPrefix = _getUrlPrefix();
            const saveUrl = `${urlPrefix.save}/${pedidoId}/actualizar-prenda`;
            console.log('[PedidosAdapter] 📤 Enviando a POST', saveUrl, '(contexto:', urlPrefix.context + ')');

            const response = await fetch(saveUrl, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                    // NO poner Content-Type — FormData lo pone automáticamente con boundary
                },
                body: formData
            });

            if (!response.ok) {
                let errorMsg = 'Error desconocido';
                try {
                    const error = await response.json();
                    errorMsg = error.message || error.error || JSON.stringify(error);
                } catch (e) {
                    errorMsg = `HTTP ${response.status}: ${response.statusText}`;
                }
                console.error('[PedidosAdapter] Error API:', errorMsg);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: `No se pudo guardar: ${errorMsg}`, customClass: { container: 'swal-galeria-container' } });
                }
                return;
            }

            const result = await response.json();
            console.log('[PedidosAdapter] ✅ Prenda guardada:', result);

            // Actualizar datos locales para que la lista refleje cambios
            if (window.datosEdicionPedido?.prendas && prendaIndex !== null && prendaIndex !== undefined) {
                Object.assign(window.datosEdicionPedido.prendas[prendaIndex], datos);
                if (datos.nombre_prenda) {
                    window.datosEdicionPedido.prendas[prendaIndex].nombre = datos.nombre_prenda;
                    window.datosEdicionPedido.prendas[prendaIndex].nombre_prenda = datos.nombre_prenda;
                }
            }

            // Mostrar éxito centrado encima del modal
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '✅ Prenda actualizada',
                    text: 'Los cambios se guardaron correctamente',
                    timer: 1800,
                    showConfirmButton: false,
                    customClass: { container: 'swal-galeria-container' }
                });
            }

            // Cerrar modal después de breve delay
            setTimeout(function() {
                cerrarModalPrendaNueva();
            }, 1900);

        } catch (error) {
            console.error('[PedidosAdapter] Error de red:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión al guardar la prenda', customClass: { container: 'swal-galeria-container' } });
            }
        }
    }

    /**
     * Pedir novedad/justificación antes de guardar (requerido por backend)
     * @returns {Promise<string|null>} novedad o null si canceló
     * @private
     */
    function _pedirNovedad() {
        return new Promise((resolve) => {
            if (typeof Swal === 'undefined') {
                resolve('Edición de prenda desde lista de pedidos');
                return;
            }

            // Inyectar CSS para z-index encima del modal de prenda (1050000)
            let galeriaStyle = document.getElementById('swal-galeria-zindex-style');
            if (!galeriaStyle) {
                galeriaStyle = document.createElement('style');
                galeriaStyle.id = 'swal-galeria-zindex-style';
                document.head.appendChild(galeriaStyle);
            }
            galeriaStyle.textContent = `
                .swal-galeria-container {
                    z-index: 2000000 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    height: 100% !important;
                }
                .swal-galeria-container .swal2-popup {
                    margin: auto !important;
                }
            `;

            Swal.fire({
                title: 'Novedad del cambio',
                input: 'textarea',
                inputLabel: '¿Por qué hiciste este cambio?',
                inputPlaceholder: 'Describe brevemente el motivo...',
                inputAttributes: { 'aria-label': 'Novedad del cambio' },
                showCancelButton: true,
                confirmButtonText: '💾 Guardar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981',
                customClass: { container: 'swal-galeria-container' },
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Debes ingresar una novedad del cambio';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    resolve(result.value.trim());
                } else {
                    resolve(null);
                }
            });
        });
    }

    // ====================================================
    // 4. editarPrendaDePedido — Punto de entrada para editar
    //    Trae datos completos de BD antes de abrir modal
    // ====================================================
    window.editarPrendaDePedido = async function(prenda, prendaIndex, pedidoId) {
        const prendaId = prenda.id || prenda.prenda_pedido_id;
        console.log('[PedidosAdapter] Editando prenda:', prenda.nombre_prenda || prenda.nombre, 'id:', prendaId, 'pedidoId:', pedidoId);

        // Guardar contexto
        window._editandoPrendaDePedido = {
            pedidoId: pedidoId,
            prendaIndex: prendaIndex,
            prendaId: prendaId
        };

        // Mostrar loading mientras cargamos datos de BD
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Cargando prenda...',
                text: 'Obteniendo datos completos',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
        }

        try {
            // ===== PASO 1: Traer datos completos desde BD =====
            let prendaCompleta = prenda; // fallback a datos locales

            if (pedidoId && prendaId) {
                const urlPrefix = _getUrlPrefix();
                const fetchUrl = `${urlPrefix.fetch}/${pedidoId}/prenda/${prendaId}/datos`;
                console.log('[PedidosAdapter] 📡 Fetching datos de BD:', fetchUrl, '(contexto:', urlPrefix.context + ')');
                const response = await fetch(fetchUrl, {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.prenda) {
                        prendaCompleta = result.prenda;
                        // Asegurar que tenga pedido_id para PrendaEditorService
                        prendaCompleta.pedido_id = pedidoId;
                        prendaCompleta.pedidoId = pedidoId;
                        // Marcar como datos de BD para que PrendaEditorService NO re-fetch
                        prendaCompleta._fromDB = true;

                        // ===== NORMALIZAR formato BD → formato loaders =====
                        prendaCompleta = _normalizarDatosBD(prendaCompleta);

                        console.log('[PedidosAdapter] ✅ Datos completos de BD (normalizados):', {
                            nombre: prendaCompleta.nombre_prenda || prendaCompleta.nombre,
                            cantidad_talla: prendaCompleta.cantidad_talla ? Object.keys(prendaCompleta.cantidad_talla) : [],
                            variantes: prendaCompleta.variantes,
                            telasAgregadas: prendaCompleta.telasAgregadas?.length || 0,
                            procesos: prendaCompleta.procesos?.length || 0,
                            imagenes: prendaCompleta.imagenes?.length || 0
                        });
                    } else {
                        console.warn('[PedidosAdapter] ⚠️ Respuesta sin datos de prenda, usando datos locales');
                    }
                } else {
                    console.warn('[PedidosAdapter] ⚠️ Error HTTP', response.status, '- usando datos locales');
                }
            }

            // Cerrar TODOS los SweetAlerts activos (loading + edit-pedido + prendas-lista)
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
            // Limpiar backdrops/overlays residuales de SweetAlert
            document.querySelectorAll('.swal2-container').forEach(el => el.remove());
            document.body.classList.remove('swal2-shown', 'swal2-height-auto');
            document.body.style.overflow = '';

            // ===== PASO 2: Asegurar módulos cargados =====
            if (window.PrendaEditorLoader && typeof window.PrendaEditorLoader.load === 'function') {
                await window.PrendaEditorLoader.load();
            }
            initPrendaEditor();

            // ===== PASO 3: Abrir modal con datos completos =====
            const editor = window.prendaEditorGlobal;
            if (editor && typeof editor.cargarPrendaEnModal === 'function') {
                // Guardar prenda completa como referencia global
                window.prendaActual = prendaCompleta;
                await editor.cargarPrendaEnModal(prendaCompleta, prendaIndex);
            } else {
                console.error('[PedidosAdapter] prendaEditorGlobal no disponible, abriendo modal manualmente');
                _abrirModalManual(prendaCompleta);
            }

            // ===== PASO 4: Cambiar botón y título a modo edición =====
            const btnGuardar = document.getElementById('btn-guardar-prenda');
            if (btnGuardar) {
                btnGuardar.textContent = '💾 Guardar Cambios';
                btnGuardar.className = 'btn btn-success';
            }
            const tituloModal = document.getElementById('modal-prenda-titulo');
            if (tituloModal) {
                tituloModal.textContent = 'Editar Prenda';
            }

        } catch (error) {
            console.error('[PedidosAdapter] Error al cargar prenda:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se pudieron cargar los datos de la prenda: ' + error.message, 'error');
            }
        }
    };

    // ====================================================
    // 5. _normalizarDatosBD — Convierte formato BD al formato
    //    que esperan los loaders del modal (PrendaEditorTallas,
    //    PrendaEditorVariaciones, PrendaEditorTelas, etc.)
    // ====================================================

    /**
     * Normalizar datos de BD al formato que esperan los loaders
     * 
     * BD retorna (transformarPrendaParaEdicion):
     *   tallas_dama: [{talla:'S', cantidad:5}, ...]
     *   tallas_caballero: [{talla:'L', cantidad:2}, ...]
     *   variantes: [{tipo_manga:'Larga', tipo_broche_boton:'Botón', bolsillos_obs:'4 bolsillos', ...}]
     *   colores_telas: [{color_nombre:'AZUL', tela_nombre:'DRILL', fotos_tela:[{url:'...'}], ...}]
     * 
     * Loaders esperan:
     *   cantidad_talla: {DAMA: {S: 5}, CABALLERO: {L: 2}}
     *   variantes: {tipo_manga:'Larga', obs_manga:'...', tipo_broche:'Botón', obs_bolsillos:'4 bolsillos'}
     *   telasAgregadas: [{tela:'DRILL', color:'AZUL', imagenes:[...]}]
     * 
     * @param {Object} prenda - Datos de la prenda desde BD
     * @returns {Object} prenda normalizada
     * @private
     */
    function _normalizarDatosBD(prenda) {
        if (!prenda) return prenda;

        // ---- 1. TALLAS: tallas_dama/tallas_caballero/tallas_unisex/tallas_sobremedida → cantidad_talla ----
        if ((prenda.tallas_dama && prenda.tallas_dama.length > 0) || 
            (prenda.tallas_caballero && prenda.tallas_caballero.length > 0) ||
            (prenda.tallas_unisex && prenda.tallas_unisex.length > 0) ||
            (prenda.tallas_sobremedida && prenda.tallas_sobremedida.length > 0)) {
            
            const cantidadTalla = {};

            // Mapeo de géneros: propiedad del servidor → key en cantidad_talla
            const generosMap = {
                tallas_dama: 'DAMA',
                tallas_caballero: 'CABALLERO',
                tallas_unisex: 'UNISEX',
                tallas_sobremedida: 'SOBREMEDIDA'
            };

            Object.entries(generosMap).forEach(([prop, genero]) => {
                if (prenda[prop] && prenda[prop].length > 0) {
                    cantidadTalla[genero] = {};
                    prenda[prop].forEach(t => {
                        cantidadTalla[genero][t.talla] = parseInt(t.cantidad) || 0;
                    });
                }
            });

            prenda.cantidad_talla = cantidadTalla;
            prenda.tallasRelacionales = cantidadTalla;
            console.log('[PedidosAdapter] 📏 Tallas normalizadas:', cantidadTalla);
        }

        // ---- 1b. COLORES POR TALLA (prenda_pedido_talla_colores) → asignaciones ----
        if (Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0) {
            console.log('[PedidosAdapter] 🎨 Construyendo asignaciones desde talla_colores:', prenda.talla_colores.length, 'registros');

            // Construir asignaciones (array plano para la tabla resumen)
            prenda.asignaciones = prenda.talla_colores.map(tc => ({
                tela: tc.tela_nombre || '',
                genero: tc.genero || '',
                talla: tc.talla || '',
                color: tc.color_nombre || '',
                cantidad: parseInt(tc.cantidad) || 0
            }));

            // Construir asignacionesColoresPorTalla (formato StateManager)
            const coloresPorTalla = {};
            prenda.talla_colores.forEach(tc => {
                const genero = tc.genero || '';
                const talla = tc.talla || '';
                const tela = tc.tela_nombre || '';
                const key = `${genero}-${tela}-${talla}`;

                if (!coloresPorTalla[key]) {
                    coloresPorTalla[key] = {
                        genero: genero,
                        tela: tela,
                        tipo: genero,
                        talla: talla,
                        colores: []
                    };
                }

                coloresPorTalla[key].colores.push({
                    nombre: tc.color_nombre || '',
                    cantidad: parseInt(tc.cantidad) || 0
                });
            });

            prenda.asignacionesColoresPorTalla = coloresPorTalla;
            console.log('[PedidosAdapter] 🎨 Asignaciones construidas:', prenda.asignaciones.length, 'filas');
            console.log('[PedidosAdapter] 🎨 ColoresPorTalla:', Object.keys(coloresPorTalla).length, 'grupos');
        }

        // ---- 2. VARIANTES: array → objeto plano ----
        if (Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const v = prenda.variantes[0]; // Tomar primera variante
            prenda.variantes = {
                tipo_manga: v.tipo_manga !== 'Sin especificar' ? v.tipo_manga : null,
                tipo_manga_id: v.tipo_manga_id || null,
                obs_manga: v.manga_obs || '',
                tipo_broche: v.tipo_broche_boton !== 'Sin especificar' ? v.tipo_broche_boton : null,
                tipo_broche_id: v.tipo_broche_boton_id || null,
                obs_broche: v.broche_boton_obs || '',
                tiene_bolsillos: v.tiene_bolsillos || false,
                obs_bolsillos: v.bolsillos_obs || ''
            };
            console.log('[PedidosAdapter] ⚙️ Variantes normalizadas:', prenda.variantes);
        } else if (Array.isArray(prenda.variantes) && prenda.variantes.length === 0) {
            prenda.variantes = {};
        }

        // ---- 3. COLORES/TELAS: colores_telas → telasAgregadas ----
        if (Array.isArray(prenda.colores_telas) && prenda.colores_telas.length > 0) {
            prenda.telasAgregadas = prenda.colores_telas.map(ct => {
                // Mapear fotos_tela al formato de imagenes (con /storage/ prefix)
                let imagenes = [];
                if (ct.fotos_tela && Array.isArray(ct.fotos_tela)) {
                    imagenes = ct.fotos_tela.map(f => {
                        const ruta = f.url || f.ruta_webp || f.ruta_original || '';
                        if (!ruta) return '';
                        // Asegurar prefijo /storage/ para rutas relativas
                        if (ruta.startsWith('/storage/') || ruta.startsWith('http') || ruta.startsWith('blob:') || ruta.startsWith('data:')) return ruta;
                        if (ruta.startsWith('/')) return '/storage' + ruta;
                        return '/storage/' + ruta;
                    }).filter(r => r !== '');
                }

                return {
                    tela: ct.tela_nombre || '',
                    tela_id: ct.tela_id || null,
                    color: ct.color_nombre || '',
                    color_id: ct.color_id || null,
                    referencia: ct.tela_referencia || '',
                    imagenes: imagenes,
                    // Preservar datos originales para el save
                    _original_id: ct.id
                };
            });
            console.log('[PedidosAdapter] 🧵 Telas normalizadas:', prenda.telasAgregadas.length, 'telas');
        }

        // ---- 3b. FALLBACK TELAS: si no hay colores_telas pero sí talla_colores, extraer telas únicas ----
        if ((!prenda.telasAgregadas || prenda.telasAgregadas.length === 0) &&
            Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0) {
            
            const telasUnicas = new Map();
            prenda.talla_colores.forEach(tc => {
                const telaKey = `${tc.tela_id || ''}_${tc.tela_nombre || ''}`;
                if (!telasUnicas.has(telaKey)) {
                    telasUnicas.set(telaKey, {
                        tela: tc.tela_nombre || '',
                        tela_id: tc.tela_id || null,
                        color: tc.color_nombre || '',
                        color_id: tc.color_id || null,
                        referencia: '',
                        imagenes: []
                    });
                }
            });
            
            prenda.telasAgregadas = Array.from(telasUnicas.values());
            console.log('[PedidosAdapter] 🧵 Telas extraídas desde talla_colores (fallback):', prenda.telasAgregadas.length, 'telas');
        }

        // ---- 4. IMÁGENES DE PRENDA: agregar /storage/ a rutas relativas ----
        if (Array.isArray(prenda.imagenes)) {
            prenda.imagenes = prenda.imagenes.map(img => {
                if (typeof img === 'string') {
                    if (img.startsWith('/storage/') || img.startsWith('http') || img.startsWith('blob:') || img.startsWith('data:')) return img;
                    if (img.startsWith('/')) return '/storage' + img;
                    return '/storage/' + img;
                }
                return img;
            });
            console.log('[PedidosAdapter] 🖼️ Imágenes de prenda normalizadas:', prenda.imagenes.length);
        }

        // ---- 5. NOMBRE: asegurar consistencia ----
        if (prenda.nombre_prenda && !prenda.nombre) {
            prenda.nombre = prenda.nombre_prenda;
        }

        return prenda;
    }

    /**
     * Abrir modal manualmente sin PrendaEditor (fallback)
     * @private
     */
    function _abrirModalManual(prenda) {
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (!modal) {
            console.error('[PedidosAdapter] Modal #modal-agregar-prenda-nueva no encontrado en DOM');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se encontró el modal de edición de prenda', 'error');
            }
            return;
        }

        // Mostrar modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Cargar datos básicos manualmente
        const nombreInput = document.getElementById('nueva-prenda-nombre');
        if (nombreInput) nombreInput.value = prenda.nombre_prenda || prenda.nombre || '';

        const descInput = document.getElementById('nueva-prenda-descripcion');
        if (descInput) descInput.value = prenda.descripcion || '';

        // Origen
        const origenSelect = document.getElementById('nueva-prenda-origen-select');
        if (origenSelect) {
            origenSelect.value = prenda.origen || (prenda.de_bodega ? 'bodega' : 'confeccion');
        }

        // Cambiar botón a modo edición
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.textContent = '💾 Guardar Cambios';
            btnGuardar.className = 'btn btn-success';
        }

        // Guardar referencia
        window.prendaActual = prenda;
    }

})();
