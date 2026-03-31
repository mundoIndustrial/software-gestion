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

    console.log('[PedidosAdapter]  Cargado');

    // Detectar contexto: supervisor vs asesor basado en la URL actual
    function _getUrlPrefix() {
        const path = window.location.pathname;
        if (path.startsWith('/supervisor-pedidos')) {
            return { fetch: '/api/supervisor-pedidos/ordenes', save: '/api/supervisor-pedidos/ordenes', context: 'supervisor' };
        }
        return { fetch: '/api/asesores/pedidos-produccion', save: '/api/asesores/pedidos', context: 'asesor' };
    }

    // ====================================================
    // 1. INICIALIZAR PrendaEditor global
    // ====================================================
    function initPrendaEditor() {
        if (typeof PrendaEditor !== 'undefined' && !window.prendaEditorGlobal) {
            window.prendaEditorGlobal = new PrendaEditor({
                modalId: 'modal-agregar-prenda-nueva'
            });
            console.log('[PedidosAdapter]  prendaEditorGlobal creado');
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
        console.log('[PedidosAdapter]  manejarImagenesPrenda definida (fallback)');
    }

    if (typeof window.actualizarPreviewPrenda !== 'function') {
        window.actualizarPreviewPrenda = function() {
            const preview = document.getElementById('nueva-prenda-foto-preview');
            if (!preview || !window.imagenesPrendaStorage) return;
            
            const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
            if (typeof PrendaEditorImagenes !== 'undefined') {
                //  ELIMINADO: _actualizarPreviewDOM() causaba apilamiento
                // Usar actualizarPreviewDespuesDeAgregar() en su lugar
                PrendaEditorImagenes.actualizarPreviewDespuesDeAgregar();
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
        console.log('[PedidosAdapter]  actualizarPreviewPrenda definida (fallback)');
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
        window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
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
            btnGuardar.textContent = ' Agregar Prenda';
            btnGuardar.className = 'btn btn-primary';
        }

        // Limpiar estado de edición
        window._editandoPrendaDePedido = null;
        
        // Limpiar imágenes marcadas para eliminación
        if (window.imagenesAEliminar) {
            console.log('[PedidosAdapter]  Limpiando imágenes marcadas para eliminación:', window.imagenesAEliminar.length);
            window.imagenesAEliminar = [];
        }
        
        // Limpiar otros estados temporales
        if (window.procesosParaEliminarIds) {
            window.procesosParaEliminarIds.clear();
        }
    };

    // ====================================================
    // 3. agregarPrendaNueva — Guardar prenda editada (POST API)
    //    Nota: El modo "agregar nueva prenda" se maneja en
    //    prenda-agregar-pedido.js que wrappea esta función
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
        if (typeof PrendaFormCollector === 'undefined') {
            console.error('[PedidosAdapter] PrendaFormCollector no está cargado. Es una dependencia requerida.');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Componente PrendaFormCollector no disponible. Recarga la página.', 'error');
            }
            return null;
        }

        const collector = new PrendaFormCollector();
        collector.setNotificationService({
            error: function(msg) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Validación', msg, 'warning');
                }
            }
        });
        return collector.construirPrendaDesdeFormulario(
            prendaIndex,
            window.datosEdicionPedido?.prendas || []
        );
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

            // Asignaciones de colores por talla - DEFENSIVO en edición
            // Solo enviar si hay cambios o si fue creación nueva
            // En edición sin cambios, NO enviar (null) para preservar datos en BD
            const hayAsignacionesParaEnviar = datos.asignacionesColoresPorTalla !== undefined && 
                                              datos.asignacionesColoresPorTalla !== null &&
                                              Object.keys(datos.asignacionesColoresPorTalla).length > 0;
            
            if (hayAsignacionesParaEnviar) {
                formData.append('asignaciones_colores', JSON.stringify(datos.asignacionesColoresPorTalla));
                console.log('[PedidosAdapter] asignaciones_colores enviado (CON CAMBIOS):', JSON.stringify(datos.asignacionesColoresPorTalla).substring(0, 200) + '...');

                //  NUEVO: Extraer File objects de imágenes de color desde _imageStore
                // Las imágenes están en ColoresPorTalla._imageStore referenciadas por imagen_id
                if (window.ColoresPorTalla && typeof window.ColoresPorTalla.getImage === 'function') {
                    let colorImgIdx = 0;
                    Object.entries(datos.asignacionesColoresPorTalla).forEach(([clave, asignacion]) => {
                        if (asignacion.colores && Array.isArray(asignacion.colores)) {
                            asignacion.colores.forEach((colorItem) => {
                                if (colorItem.imagen_id) {
                                    const imgData = window.ColoresPorTalla.getImage(colorItem.imagen_id);
                                    if (imgData && imgData.file) {
                                        formData.append(`fotos_color[${colorImgIdx}]`, imgData.file);
                                        formData.append(`fotos_color_meta[${colorImgIdx}]`, JSON.stringify({
                                            clave: clave,
                                            color_nombre: colorItem.nombre,
                                            imagen_id: colorItem.imagen_id,
                                            imagen_nombre: imgData.nombre || imgData.file.name
                                        }));
                                        console.log(`[PedidosAdapter]  Imagen de color adjuntada: idx=${colorImgIdx}, clave=${clave}, color=${colorItem.nombre}, file=${imgData.nombre}`);
                                        colorImgIdx++;
                                    }
                                }
                            });
                        }
                    });
                    if (colorImgIdx > 0) {
                        console.log(`[PedidosAdapter]  Total imágenes de color adjuntadas: ${colorImgIdx}`);
                    }
                }
            }

            // Telas (JSON) - usar telasAgregadas o telas
            //  CRÍTICO: Enviar SIEMPRE (incluso vacío) para que el backend sepa si eliminar telas
            const telas = datos.telasAgregadas || datos.colores_telas || datos.telas || [];
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
            // Siempre enviar, incluso si está vacío (para eliminar telas en BD)
            formData.append('colores_telas', JSON.stringify(telasJSON));
            console.log('[PedidosAdapter] 🧵 Telas enviadas:', telasJSON.length, 'telas');

            // Procesos (JSON) - transformar de objeto a array si es necesario
            if (datos.procesos) {
                let procesosRaw = datos.procesos;
                let procesosArray = [];
                
                if (!Array.isArray(procesosRaw) && typeof procesosRaw === 'object') {
                    //  PASO 1: Extraer File images ANTES de transformar (se pierden en JSON.stringify)
                    const filesPorProceso = {};
                    const filesPorTalla = {}; // NUEVO: Para imágenes de tallas en datosExtendidos
                    
                    Object.entries(procesosRaw).forEach(([tipo, proc], idx) => {
                        const d = proc?.datos || proc || {};
                        const imagenes = d.imagenes || [];
                        filesPorProceso[idx] = [];
                        
                        //  PASO 1a: Usar imagenesFiles directamente si está disponible (desde modal por tallas)
                        if (d.imagenesFiles && Array.isArray(d.imagenesFiles)) {
                            d.imagenesFiles.forEach(file => {
                                if (file instanceof File) {
                                    filesPorProceso[idx].push(file);
                                    console.log(`[PedidosAdapter]  File encontrado en imagenesFiles para ${tipo}`);
                                }
                            });
                        }
                        
                        //  PASO 1b: Extraer File objects de imagenes del proceso (fallback para compatibilidad)
                        if (Array.isArray(imagenes)) {
                            imagenes.forEach(img => {
                                if (img instanceof File) {
                                    filesPorProceso[idx].push(img);
                                } else if (img?.file instanceof File) {
                                    filesPorProceso[idx].push(img.file);
                                }
                            });
                        }
                        
                        // NUEVO: Extraer File objects de imagenesFiles en datosExtendidos por talla
                        if (d.datosExtendidos && typeof d.datosExtendidos === 'object') {
                            Object.entries(d.datosExtendidos).forEach(([genero, tallasDatos]) => {
                                if (tallasDatos && typeof tallasDatos === 'object') {
                                    Object.entries(tallasDatos).forEach(([talla, tallaData]) => {
                                        if (tallaData?.imagenesFiles && Array.isArray(tallaData.imagenesFiles)) {
                                            const keyTalla = `${idx}_${genero}_${talla}`;
                                            filesPorTalla[keyTalla] = filesPorTalla[keyTalla] || [];
                                            
                                            tallaData.imagenesFiles.forEach(img => {
                                                if (img instanceof File) {
                                                    filesPorTalla[keyTalla].push(img);
                                                    console.log(`[PedidosAdapter]  Imagen File encontrada para talla ${genero}/${talla}`);
                                                }
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    });
                    
                    //  PASO 2: Transformar a array con imagenes_existentes (URLs para el backend)
                    procesosArray = Object.entries(procesosRaw).map(([tipo, proc]) => {
                        const d = proc?.datos || proc || {};
                        
                        console.log(`[PedidosAdapter]  DIAGNÓSTICO Proceso ${tipo}:`, {
                            tieneImagenes: !!d.imagenes,
                            tieneImagenesEliminadas: !!d.imagenesEliminadas,
                            cantidadImagenes: d.imagenes?.length || 0,
                            cantidadEliminadas: d.imagenesEliminadas?.length || 0,
                            datosKeys: Object.keys(d)
                        });
                        
                        const imagenesExistentes = [];
                        const imagenesAEliminar = [];
                        
                        //  PASO 1: Usar imagenes_existentes explícitamente si está disponible (desde modal por tallas/general)
                        if (d.imagenes_existentes && Array.isArray(d.imagenes_existentes)) {
                            console.log(`[PedidosAdapter]  Usando imagenes_existentes explícitas para ${tipo}:`, d.imagenes_existentes.length, 'imágenes');
                            d.imagenes_existentes.forEach(img => {
                                const url = img.url || img.ruta_original || img.ruta_webp || img.ruta || img.previewUrl || '';
                                const id = img.id || null;
                                if (url || id) {
                                    imagenesExistentes.push({ id: id, url: url, ruta_original: img.ruta_original || url, ruta_webp: img.ruta_webp || '' });
                                }
                            });
                        } else {
                            //  PASO 2 (fallback): Procesar imágenes del array imagenes: separar existentes de eliminadas
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
                        }
                        
                        // 🟢 NUEVO: Registrar imágenes ELIMINADAS de la BD (NUEVO NOMBRE: imagenes_a_eliminar)
                        if (d.imagenes_a_eliminar && Array.isArray(d.imagenes_a_eliminar)) {
                            console.log(`[PedidosAdapter]  Imágenes a eliminar para ${tipo}:`, d.imagenes_a_eliminar.length);
                            d.imagenes_a_eliminar.forEach(img => {
                                // El nuevo formato envía objetos completos {id, url, ruta_original, ruta_webp}
                                if (img) {
                                    imagenesAEliminar.push(img);
                                }
                            });
                        }
                        
                        // FALLBACK: Registrar imágenes ELIMINADAS de la BD (nombre antiguo)
                        if (d.imagenes_eliminadas && Array.isArray(d.imagenes_eliminadas)) {
                            console.log(`[PedidosAdapter]  Imágenes a eliminar para ${tipo}:`, d.imagenes_eliminadas.length);
                            d.imagenes_eliminadas.forEach(imgUrl => {
                                if (imgUrl && typeof imgUrl === 'string') {
                                    imagenesAEliminar.push({
                                        url: imgUrl,
                                        ruta_original: imgUrl
                                    });
                                }
                            });
                        }
                        
                        //  CRÍTICO: También incluir imágenes que fueron eliminadas (marcadas como null)
                        if (d.imagenesEliminadas && Array.isArray(d.imagenesEliminadas)) {
                            console.log(`[PedidosAdapter]  DEBUGGING imagenesEliminadas para ${tipo}:`, {
                                length: d.imagenesEliminadas.length,
                                items: d.imagenesEliminadas.map((img, idx) => ({
                                    idx,
                                    esNull: img === null,
                                    tipo: typeof img,
                                    tieneId: !!img?.id,
                                    id: img?.id,
                                    ruta: img?.ruta_original,
                                    claves: typeof img === 'object' ? Object.keys(img || {}) : 'N/A'
                                }))
                            });
                            d.imagenesEliminadas.forEach(img => {
                                //  WICHTIG: Backend espera objeto completo con {id, ruta_original, ruta_webp}
                                const tieneIdentificador = img && (img.id || img.ruta_original);
                                if (tieneIdentificador) {
                                    const imagenAEliminar = {
                                        id: img.id || undefined,
                                        ruta_original: img.ruta_original || img.url || '',
                                        ruta_webp: img.ruta_webp || ''
                                    };
                                    imagenesAEliminar.push(imagenAEliminar);
                                    console.log(`[PedidosAdapter]  Objeto AGREGADO a imagenesAEliminar:`, imagenAEliminar);
                                } else {
                                    console.log(`[PedidosAdapter]  IMAGEN RECHAZADA - sin ID ni ruta:`, img);
                                }
                            });
                        }
                        
                        const procesoEnvio = {
                            id: d.id || undefined,
                            tipo_proceso_id: d.tipo_proceso_id || undefined,
                            tipo: d.tipo || tipo,
                            nombre: d.nombre || tipo,
                            ubicaciones: d.ubicaciones || [],
                            observaciones: d.observaciones || '',
                            modoTallas: d.modoTallas || d.modo_tallas || proc?.modoTallas || 'generico',
                            tallas: d.tallas || {},
                            datosExtendidos: d.datosExtendidos || {},
                            estado: d.estado || 'PENDIENTE',
                            imagenes_existentes: imagenesExistentes
                        };
                        
                        // Incluir imágenes a eliminar si hay
                        if (imagenesAEliminar.length > 0) {
                            procesoEnvio.imagenes_a_eliminar = imagenesAEliminar;
                            console.log(`[PedidosAdapter]  Proceso ${tipo}: ${imagenesAEliminar.length} imagen(es) marcada(s) para eliminar:`, imagenesAEliminar);
                        }
                        
                        return procesoEnvio;
                    });
                    
                    //  PASO 3: Agregar File images al FormData
                    // Usar fotosProcesoNuevo_{procesoIdx}[] para soportar múltiples archivos por proceso
                    Object.entries(filesPorProceso).forEach(([idx, files]) => {
                        files.forEach((file, fileIdx) => {
                            formData.append(`fotosProcesoNuevo_${idx}[]`, file);
                            console.log(`[PedidosAdapter]  Foto proceso[${idx}][${fileIdx}]: ${file.name}`);
                        });
                    });
                    
                    // NUEVO: Agregar File images de tallas al FormData
                    Object.entries(filesPorTalla).forEach(([keyTalla, files]) => {
                        files.forEach((file, fileIdx) => {
                            formData.append(`fotosProcesoTallasNuevo_${keyTalla}[]`, file);
                            console.log(`[PedidosAdapter]  Foto talla[${keyTalla}][${fileIdx}]: ${file.name}`);
                        });
                    });
                } else if (Array.isArray(procesosRaw)) {
                    procesosArray = procesosRaw;
                }
                
                // 🟢 CRÍTICO: Extraer imágenes a eliminar de TODOS los procesos y agregarlas al FormData
                const todasLasImagenesAEliminar = [];
                procesosArray.forEach((proceso, idx) => {
                    if (proceso.imagenes_a_eliminar && Array.isArray(proceso.imagenes_a_eliminar)) {
                        proceso.imagenes_a_eliminar.forEach(img => {
                            todasLasImagenesAEliminar.push(img);
                            console.log(`[PedidosAdapter]  Imagen a eliminar de proceso ${idx}:`, img);
                        });
                        // NO ELIMINAR del JSON, mantenerlo para registro
                    }
                });
                
                // Agregar imágenes a eliminar al FormData (nivel superior)
                if (todasLasImagenesAEliminar.length > 0) {
                    formData.append('imagenes_a_eliminar', JSON.stringify(todasLasImagenesAEliminar));
                    console.log('[PedidosAdapter]  Total imágenes a eliminar:', todasLasImagenesAEliminar.length);
                }
                
                formData.append('procesos', JSON.stringify(procesosArray));
                console.log('[PedidosAdapter]  Procesos enviados:', procesosArray.length, 'procesos');
            }

            // Imágenes de prenda - separar nuevas (File) de existentes (BD)
            const imagenesNuevas = [];
            const imagenesExistentes = [];
            const imgs = datos.imagenes || [];
            
            console.log('[PedidosAdapter]  DIAGNÓSTICO DE IMÁGENES:', {
                'datos.imagenes': datos.imagenes,
                'typeof datos.imagenes': typeof datos.imagenes,
                'imgs.length': imgs.length,
                'imgs': imgs,
                'window.imagenesAEliminar': window.imagenesAEliminar
            });
            
            imgs.forEach((img, index) => {
                console.log(`[PedidosAdapter]  Procesando imagen ${index}:`, {
                    'img': img,
                    'type': typeof img,
                    'isFile': img instanceof File,
                    'hasFile': img?.file instanceof File,
                    'hasId': !!img.id,
                    'hasUrl': !!img.url,
                    'hasPreviewUrl': !!img.previewUrl
                });
                
                if (img instanceof File) {
                    imagenesNuevas.push(img);
                } else if (img?.file instanceof File) {
                    imagenesNuevas.push(img.file);
                } else if (img?.urlDesdeDB || img?.id || img?.url?.startsWith('/') || img?.ruta || img?.ruta_original || img?.ruta_webp || img?.previewUrl?.startsWith('/storage/')) {
                    const url = img.url || img.ruta || img.ruta_webp || img.ruta_original || img.previewUrl || '';
                    imagenesExistentes.push({ id: img.id, url: url });
                }
            });
            
            console.log('[PedidosAdapter]RESULTADO IMÁGENES:', {
                'imagenesNuevas': imagenesNuevas.length,
                'imagenesExistentes': imagenesExistentes.length,
                'imagenesExistentes_content': imagenesExistentes
            });
            
            imagenesNuevas.forEach((file) => formData.append('imagenes[]', file));
            formData.append('imagenes_existentes', JSON.stringify(imagenesExistentes));
            
            //  NUEVO: Agregar imágenes marcadas para eliminación
            if (window.imagenesAEliminar && window.imagenesAEliminar.length > 0) {
                formData.append('imagenes_a_eliminar', JSON.stringify(window.imagenesAEliminar));
                console.log('[PedidosAdapter]  Imágenes marcadas para eliminación:', window.imagenesAEliminar);
            }
            
            //  NUEVO: Agregar procesos marcados para eliminación
            if (window.procesosParaEliminarIds && window.procesosParaEliminarIds.size > 0) {
                const procesosAEliminar = Array.from(window.procesosParaEliminarIds);
                formData.append('procesos_a_eliminar', JSON.stringify(procesosAEliminar));
                console.log('[PedidosAdapter]  Procesos marcados para eliminación:', procesosAEliminar);
            }

            const urlPrefix = _getUrlPrefix();
            const saveUrl = `${urlPrefix.save}/${pedidoId}/actualizar-prenda`;
            console.log('[PedidosAdapter]  Enviando a POST', saveUrl, '(contexto:', urlPrefix.context + ')');

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
            console.log('[PedidosAdapter]  Prenda guardada:', result);

            // Actualizar datos locales con la respuesta real del backend
            if (window.datosEdicionPedido?.prendas && prendaIndex !== null && prendaIndex !== undefined) {
                const prendaActualizada = result?.prenda ? _normalizarDatosBD(result.prenda) : null;

                if (prendaActualizada) {
                    window.datosEdicionPedido.prendas[prendaIndex] = {
                        ...window.datosEdicionPedido.prendas[prendaIndex],
                        ...prendaActualizada,
                    };
                } else {
                    Object.assign(window.datosEdicionPedido.prendas[prendaIndex], datos);
                    if (datos.nombre_prenda) {
                        window.datosEdicionPedido.prendas[prendaIndex].nombre = datos.nombre_prenda;
                        window.datosEdicionPedido.prendas[prendaIndex].nombre_prenda = datos.nombre_prenda;
                    }
                }
            }

            // Mostrar éxito centrado encima del modal
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: ' Prenda actualizada',
                    text: 'Los cambios se guardaron correctamente',
                    timer: 1800,
                    showConfirmButton: false,
                    customClass: { container: 'swal-galeria-container' }
                });
            }

            // Cerrar modal después de breve delay
            setTimeout(function() {
                cerrarModalPrendaNueva();
                
                // Recargar página si estamos en supervisor-pedidos para mostrar cambios
                const urlPrefix = _getUrlPrefix();
                if (urlPrefix.context === 'supervisor') {
                    console.log('[PedidosAdapter]  Recargando página de supervisor-pedidos para mostrar cambios');
                    setTimeout(() => {
                        window.location.reload();
                    }, 200);
                }
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
                confirmButtonText: ' Guardar',
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
                console.log('[PedidosAdapter]  Fetching datos de BD:', fetchUrl, '(contexto:', urlPrefix.context + ')');
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

                        console.log('[PedidosAdapter]  Datos completos de BD (normalizados):', {
                            nombre: prendaCompleta.nombre_prenda || prendaCompleta.nombre,
                            cantidad_talla: prendaCompleta.cantidad_talla ? Object.keys(prendaCompleta.cantidad_talla) : [],
                            variantes: prendaCompleta.variantes,
                            telasAgregadas: prendaCompleta.telasAgregadas?.length || 0,
                            procesos: prendaCompleta.procesos?.length || 0,
                            imagenes: prendaCompleta.imagenes?.length || 0
                        });
                    } else {
                        console.warn('[PedidosAdapter]  Respuesta sin datos de prenda, usando datos locales');
                    }
                } else {
                    console.warn('[PedidosAdapter]  Error HTTP', response.status, '- usando datos locales');
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
                
                //  NUEVO: Cargar UNISEX (antes SOLO CANTIDAD) si existe
                if (typeof window.cargarPrendaEnFormularioModal === 'function') {
                    console.log('[PedidosAdapter]  Llamando cargarPrendaEnFormularioModal para detectar UNISEX...');
                    window.cargarPrendaEnFormularioModal(prendaCompleta);
                }
            } else {
                console.error('[PedidosAdapter] prendaEditorGlobal no disponible, abriendo modal manualmente');
                _abrirModalManual(prendaCompleta);
            }

            // ===== PASO 4: Cambiar botón y título a modo edición =====
            const btnGuardar = document.getElementById('btn-guardar-prenda');
            if (btnGuardar) {
                btnGuardar.textContent = ' Guardar Cambios';
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

        // ---- 1. TALLAS: tallas_dama/tallas_caballero/tallas_unisex/tallas_sobremedida/tallas_generico → cantidad_talla ----
        if ((prenda.tallas_dama && prenda.tallas_dama.length > 0) || 
            (prenda.tallas_caballero && prenda.tallas_caballero.length > 0) ||
            (prenda.tallas_unisex && prenda.tallas_unisex.length > 0) ||
            (prenda.tallas_sobremedida && prenda.tallas_sobremedida.length > 0) ||
            (prenda.tallas_generico && prenda.tallas_generico.length > 0)) {
            
            const cantidadTalla = {};

            // Mapeo de géneros: propiedad del servidor → key en cantidad_talla
            const generosMap = {
                tallas_dama: 'DAMA',
                tallas_caballero: 'CABALLERO',
                tallas_unisex: 'UNISEX',
                tallas_sobremedida: 'SOBREMEDIDA',
                tallas_generico: 'GENERICO'  //  NUEVO: Para UNISEX (antes SOLO CANTIDAD)
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
            console.log('[PedidosAdapter]  Tallas normalizadas:', cantidadTalla);
        }
        // ---- 1a. UNISEX: Si cantidad_talla viene como objeto desde BD (sin tallas_dama/caballero/etc) ----
        else if (prenda.cantidad_talla && typeof prenda.cantidad_talla === 'object' && !Array.isArray(prenda.cantidad_talla)) {
            console.log('[PedidosAdapter]  cantidad_talla ya normalizada (UNISEX desde BD):', prenda.cantidad_talla);
            // La BD ya tiene la estructura correcta {GENERICO: {SIN_ESPECIFICAR: qty}}
            // No hacer nada, preservarla tal como está
        }
        // Fallback: Si cantidad_talla llega como array vacío, inicializar como objeto vacío
        else if (Array.isArray(prenda.cantidad_talla) && prenda.cantidad_talla.length === 0) {
            prenda.cantidad_talla = {};
        }
        // Si cantidad_talla no existe, inicializar
        else if (!prenda.cantidad_talla) {
            prenda.cantidad_talla = {};
        }
        
        // ---- 1a2. Construir generosConTallas para que cargarPrendaEnFormularioModal pueda detectar UNISEX ----
        if (!prenda.generosConTallas && prenda.cantidad_talla && typeof prenda.cantidad_talla === 'object') {
            prenda.generosConTallas = {};
            Object.keys(prenda.cantidad_talla).forEach(genero => {
                prenda.generosConTallas[genero] = {
                    tallas: Object.values(prenda.cantidad_talla[genero] || {}) 
                };
            });
            console.log('[PedidosAdapter]  generosConTallas construido para detección UNISEX:', Object.keys(prenda.generosConTallas));
        }

        // ---- 1b. COLORES POR TALLA (prenda_pedido_talla_colores) → asignaciones ----
        console.log('[PedidosAdapter] DEBUG: Verificando talla_colores ANTES de procesar', {
            'existe': 'talla_colores' in prenda,
            'es_array': Array.isArray(prenda.talla_colores),
            'longitud': prenda.talla_colores?.length || 0,
            'contenido': prenda.talla_colores
        });
        
        if (Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0) {
            console.log('[PedidosAdapter] Construyendo asignaciones desde talla_colores:', prenda.talla_colores.length, 'registros');

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
                const genero = (tc.genero || '').toLowerCase();
                const talla = tc.talla || '';
                const tela = tc.tela_nombre || '';
                // Determinar tipo de talla (IGUAL que cargar-prendas-cotizacion.js)
                const tipoTalla = /^\d+$/.test(talla) ? 'Número' : 'Letra';
                // Clave: genero-tipoTalla-talla (ej: dama-Letra-L)
                const key = `${genero}-${tipoTalla}-${talla}`;

                if (!coloresPorTalla[key]) {
                    coloresPorTalla[key] = {
                        genero: genero,
                        tela: tela,
                        tipo: tipoTalla,
                        talla: talla,
                        colores: []
                    };
                }

                coloresPorTalla[key].colores.push({
                    nombre: tc.color_nombre || '',
                    cantidad: parseInt(tc.cantidad) || 0,
                    referencia: tc.referencia || '',
                    observaciones: tc.observaciones || '',
                    imagen_ruta: tc.imagen_ruta || null
                });
            });

            prenda.asignacionesColoresPorTalla = coloresPorTalla;
            console.log('[PedidosAdapter] Asignaciones construidas:', prenda.asignaciones.length, 'filas');
            console.log('[PedidosAdapter] ColoresPorTalla:', Object.keys(coloresPorTalla).length, 'grupos');
        } else {
            console.log('[PedidosAdapter]  talla_colores está vacío o no es array');
        }
        
        // DEBUG: Verificar estado FINAL de talla_colores
        console.log('[PedidosAdapter] DEBUG: Estado FINAL de talla_colores', {
            'existe': 'talla_colores' in prenda,
            'es_array': Array.isArray(prenda.talla_colores),
            'longitud': prenda.talla_colores?.length || 0,
            'asignaciones_existe': 'asignaciones' in prenda,
            'asignaciones_longitud': prenda.asignaciones?.length || 0,
            'asignacionesColoresPorTalla_existe': 'asignacionesColoresPorTalla' in prenda,
            'asignacionesColoresPorTalla_keys': Object.keys(prenda.asignacionesColoresPorTalla || {}).length
        });

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
            console.log('[PedidosAdapter]  Variantes normalizadas:', prenda.variantes);
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

        // ---- 4. IMÁGENES DE PRENDA: agregar /storage/ a rutas relativas y asegurar estructura correcta ----
        if (Array.isArray(prenda.imagenes)) {
            prenda.imagenes = prenda.imagenes.map(img => {
                // Si ya es un objeto con id y url, normalizar la url
                if (typeof img === 'object' && img !== null) {
                    const url = img.url || img.ruta_webp || img.ruta_original || img.previewUrl || '';
                    let normalizedUrl = url;
                    
                    if (url && typeof url === 'string') {
                        if (url.startsWith('/storage/') || url.startsWith('http') || url.startsWith('blob:') || url.startsWith('data:')) {
                            normalizedUrl = url;
                        } else if (url.startsWith('/')) {
                            normalizedUrl = '/storage' + url;
                        } else {
                            normalizedUrl = '/storage/' + url;
                        }
                    }
                    
                    return {
                        id: img.id,
                        url: normalizedUrl,
                        previewUrl: normalizedUrl,
                        ruta_original: normalizedUrl,
                        ruta_webp: normalizedUrl,
                        nombre: img.nombre || `imagen-${img.id}`,
                        tamano: img.tamano || 0
                    };
                }
                
                // Si es string, convertir a objeto con estructura mínima
                if (typeof img === 'string') {
                    let normalizedUrl = img;
                    if (img.startsWith('/storage/') || img.startsWith('http') || img.startsWith('blob:') || img.startsWith('data:')) {
                        normalizedUrl = img;
                    } else if (img.startsWith('/')) {
                        normalizedUrl = '/storage' + img;
                    } else {
                        normalizedUrl = '/storage/' + img;
                    }
                    
                    return {
                        id: null, // No hay ID si viene solo como string
                        url: normalizedUrl,
                        previewUrl: normalizedUrl,
                        ruta_original: normalizedUrl,
                        ruta_webp: normalizedUrl,
                        nombre: 'imagen-sin-id',
                        tamano: 0
                    };
                }
                
                return img;
            });
            console.log('[PedidosAdapter]  Imágenes de prenda normalizadas:', prenda.imagenes.length);
            console.log('[PedidosAdapter]  Detalle imágenes:', prenda.imagenes);
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
            btnGuardar.textContent = ' Guardar Cambios';
            btnGuardar.className = 'btn btn-success';
        }

        // Guardar referencia
        window.prendaActual = prenda;
    }

    // ====================================================
    // 6. abrirModalEliminarPrenda — Abre modal para eliminar prenda
    //    Pide motivo y luego elimina del servidor
    // ====================================================
    window.abrirModalEliminarPrenda = function(prenda, prendaIndex, pedidoId) {
        const prendaId = prenda.id || prenda.prenda_pedido_id;
        console.log('[PedidosAdapter]  Eliminando prenda:', prenda.nombre_prenda || prenda.nombre, 'id:', prendaId, 'pedidoId:', pedidoId);

        if (!pedidoId || !prendaId) {
            console.error('[PedidosAdapter] Faltan pedidoId o prendaId para eliminar');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se pudo identificar el pedido o la prenda para eliminar', 'error');
            }
            return;
        }

        if (typeof Swal === 'undefined') {
            console.error('[PedidosAdapter] SweetAlert2 no disponible');
            return;
        }

        // Inyectar CSS para z-index y centrado
        let eliminarStyle = document.getElementById('swal-eliminar-prenda-style');
        if (!eliminarStyle) {
            eliminarStyle = document.createElement('style');
            eliminarStyle.id = 'swal-eliminar-prenda-style';
            document.head.appendChild(eliminarStyle);
        }
        eliminarStyle.textContent = `
            .swal-eliminar-prenda-container {
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
            .swal-eliminar-prenda-container .swal2-popup {
                margin: auto !important;
            }
        `;

        // Pedir motivo de eliminación
        Swal.fire({
            title: '¿Eliminar prenda?',
            html: `<p>¿Estás seguro de que deseas eliminar <strong>${(prenda.nombre_prenda || prenda.nombre || 'esta prenda').toUpperCase()}</strong>?</p>
                   <p style="color: #ef4444; font-size: 0.9em; margin-top: 1rem;">Esta acción no se puede deshacer.</p>`,
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Motivo de la eliminación',
            inputPlaceholder: 'Ej: Prenda no requerida, cambio en especificaciones, etc.',
            inputAttributes: { 'aria-label': 'Motivo de eliminación' },
            showCancelButton: true,
            confirmButtonText: ' Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            customClass: {
                container: 'swal-eliminar-prenda-container'
            },
            didOpen: (modal) => {
                const container = modal.closest('.swal2-container');
                if (container) {
                    container.style.display = 'flex';
                    container.style.alignItems = 'center';
                    container.style.justifyContent = 'center';
                    container.style.height = '100vh';
                    container.style.zIndex = '2000000';
                }
            },
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Debes ingresar un motivo de eliminación';
                }
                if (value.trim().length < 5) {
                    return 'El motivo debe tener al menos 5 caracteres';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                _eliminarPrendaDelAPI(pedidoId, prendaId, prendaIndex, prenda, result.value.trim());
            }
        });
    };

    /**
     * Eliminar prenda del servidor y actualizar novedades
     * @private
     */
    async function _eliminarPrendaDelAPI(pedidoId, prendaId, prendaIndex, prenda, motivo) {
        try {
            // Mostrar loading
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Eliminando prenda...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    customClass: {
                        container: 'swal-eliminar-prenda-container'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal-eliminar-prenda-container');
                        if (container) {
                            container.style.display = 'flex';
                            container.style.alignItems = 'center';
                            container.style.justifyContent = 'center';
                            container.style.height = '100vh';
                            container.style.zIndex = '2000000';
                        }
                        Swal.showLoading();
                    }
                });
            }

            const urlPrefix = _getUrlPrefix();
            const deleteUrl = `${urlPrefix.save}/${pedidoId}/eliminar-prenda`;
            
            console.log('[PedidosAdapter]  Enviando DELETE a:', deleteUrl);

            const response = await fetch(deleteUrl, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    prenda_id: prendaId,
                    motivo: motivo
                })
            });

            if (!response.ok) {
                let errorMsg = 'Error desconocido';
                try {
                    const error = await response.json();
                    errorMsg = error.message || error.error || JSON.stringify(error);
                } catch (e) {
                    errorMsg = `HTTP ${response.status}: ${response.statusText}`;
                }
                console.error('[PedidosAdapter] Error al eliminar:', errorMsg);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Error', 
                        text: `No se pudo eliminar: ${errorMsg}`,
                        customClass: {
                            container: 'swal-eliminar-prenda-container'
                        },
                        didOpen: (modal) => {
                            const container = modal.closest('.swal2-container');
                            if (container) {
                                container.style.display = 'flex';
                                container.style.alignItems = 'center';
                                container.style.justifyContent = 'center';
                                container.style.height = '100vh';
                                container.style.zIndex = '2000000';
                            }
                        }
                    });
                }
                return;
            }

            const result = await response.json();
            console.log('[PedidosAdapter]  Prenda eliminada:', result);

            // Actualizar datos locales
            if (window.datosEdicionPedido?.prendas && prendaIndex !== null && prendaIndex !== undefined) {
                window.datosEdicionPedido.prendas.splice(prendaIndex, 1);
                console.log('[PedidosAdapter]  Lista de prendas actualizada (removida prenda en índice', prendaIndex + ')');
            }

            // Mostrar éxito
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: ' Prenda eliminada',
                    text: 'La prenda se elimió y se registró el motivo en novedades del pedido',
                    timer: 1800,
                    showConfirmButton: false,
                    customClass: {
                        container: 'swal-eliminar-prenda-container'
                    },
                    didOpen: (modal) => {
                        const container = modal.closest('.swal2-container');
                        if (container) {
                            container.style.display = 'flex';
                            container.style.alignItems = 'center';
                            container.style.justifyContent = 'center';
                            container.style.height = '100vh';
                            container.style.zIndex = '2000000';
                        }
                    }
                });
            }

            // Cerrar modal y recargar lista de prendas
            setTimeout(function() {
                // Recargar la lista de prendas
                abrirEditarPrendas();
            }, 1900);

        } catch (error) {
            console.error('[PedidosAdapter] Error de red al eliminar:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error', 
                    text: 'Error de conexión al eliminar la prenda',
                    customClass: {
                        container: 'swal-eliminar-prenda-container'
                    },
                    didOpen: (modal) => {
                        const container = modal.closest('.swal2-container');
                        if (container) {
                            container.style.display = 'flex';
                            container.style.alignItems = 'center';
                            container.style.justifyContent = 'center';
                            container.style.height = '100vh';
                            container.style.zIndex = '2000000';
                        }
                    }
                });
            }
        }
    }

})();
