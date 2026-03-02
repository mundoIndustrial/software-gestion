/**
 * 🆕 Agregar Prenda Nueva a Pedido Existente
 * 
 * Módulo independiente que permite agregar prendas nuevas a un pedido
 * ya creado, reutilizando el modal de creación de prendas (modal-agregar-prenda-nueva).
 * 
 * Funciones expuestas:
 *   - window.agregarNuevaPrendaAPedido()  → Abre modal en modo creación limpio
 * 
 * Flujo:
 *   1. Usuario hace clic en "+ Agregar Prenda" desde la lista de prendas del pedido
 *   2. agregarNuevaPrendaAPedido() cierra el Swal de lista, limpia formulario, abre modal
 *   3. El usuario llena la prenda y hace clic en "Agregar Prenda"
 *   4. Se intercepta agregarPrendaNueva() → detecta modoAgregar → POST /agregar-prenda
 *   5. Backend crea la prenda con AgregarPrendaCompletaUseCase
 *   6. Página recarga para reflejar cambios
 * 
 * Dependencias:
 *   - prenda-editor-pedidos-adapter.js (debe cargarse ANTES para _getUrlPrefix, _pedirNovedad, etc.)
 *   - PrendaEditor, PrendaFormCollector, SweetAlert2
 */
(function() {
    'use strict';

    console.log('[PrendaAgregarPedido] ✅ Cargado');

    // ====================================================
    // Utilidades privadas
    // ====================================================

    /**
     * Detectar contexto (supervisor vs asesor) basado en URL
     * @private
     */
    function _getUrlPrefix() {
        const path = window.location.pathname;
        if (path.startsWith('/supervisor-pedidos')) {
            return { fetch: '/supervisor-pedidos', save: '/supervisor-pedidos', context: 'supervisor' };
        }
        return { fetch: '/asesores/pedidos-produccion', save: '/asesores/pedidos', context: 'asesor' };
    }

    /**
     * Pedir novedad/justificación antes de guardar
     * @returns {Promise<string|null>} novedad o null si canceló
     * @private
     */
    function _pedirNovedad() {
        return new Promise((resolve) => {
            if (typeof Swal === 'undefined') {
                resolve('Prenda nueva agregada al pedido');
                return;
            }

            // CSS para z-index encima del modal de prenda (1050000)
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
                inputLabel: '¿Por qué agregas esta prenda?',
                inputPlaceholder: 'Describe brevemente el motivo...',
                inputAttributes: { 'aria-label': 'Novedad del cambio' },
                showCancelButton: true,
                confirmButtonText: '💾 Agregar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981',
                customClass: { container: 'swal-galeria-container' },
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Debes ingresar un motivo';
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

    /**
     * Recolectar datos del formulario del modal de prenda
     * @private
     */
    function _recolectarDatosFormulario() {
        // Intentar usar PrendaFormCollector si está disponible
        if (typeof PrendaFormCollector !== 'undefined') {
            try {
                const collector = new PrendaFormCollector();
                collector.setNotificationService({
                    error: function(msg) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Validación', msg, 'warning');
                        }
                    }
                });
                const datos = collector.construirPrendaDesdeFormulario(null, []);
                return datos;
            } catch (e) {
                console.error('[PrendaAgregarPedido] Error en PrendaFormCollector:', e);
            }
        }

        // Fallback: recolectar campos manualmente
        const nombre = document.getElementById('nueva-prenda-nombre')?.value?.trim();
        const descripcion = document.getElementById('nueva-prenda-descripcion')?.value?.trim();
        const origenSelect = document.getElementById('nueva-prenda-origen-select')?.value || 'bodega';

        if (!nombre) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Validación', 'El nombre de la prenda es requerido', 'warning');
            }
            return null;
        }

        // Variantes del DOM
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

        // Imágenes del storage
        let imagenesStorage = window.imagenesPrendaStorage?.obtenerImagenes?.() || [];
        const imagenes = imagenesStorage.map(img => {
            if (img instanceof File) return img;
            if (img?.file instanceof File) return { file: img.file, previewUrl: img.previewUrl, nombre: img.nombre };
            return img;
        });

        // Procesos
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

    // ====================================================
    // 1. agregarNuevaPrendaAPedido — Abrir modal en modo creación
    // ====================================================
    window.agregarNuevaPrendaAPedido = async function() {
        const pedidoId = window.datosEdicionPedido?.id;
        if (!pedidoId) {
            console.error('[PrendaAgregarPedido] No se encontró ID del pedido');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se encontró el pedido para agregar la prenda', 'error');
            }
            return;
        }

        console.log('[PrendaAgregarPedido] 🆕 Abriendo modal para AGREGAR nueva prenda al pedido:', pedidoId);

        // Marcar contexto como "agregar nueva prenda" (sin prendaId)
        window._editandoPrendaDePedido = {
            pedidoId: pedidoId,
            prendaIndex: null,
            prendaId: null,
            modoAgregar: true
        };

        // Cerrar modal de lista de prendas (Swal)
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
        document.querySelectorAll('.swal2-container').forEach(el => el.remove());
        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
        document.body.style.overflow = '';

        try {
            // Asegurar módulos cargados
            if (window.PrendaEditorLoader && typeof window.PrendaEditorLoader.load === 'function') {
                await window.PrendaEditorLoader.load();
            }
            // Inicializar PrendaEditor global si no existe
            if (typeof PrendaEditor !== 'undefined' && !window.prendaEditorGlobal) {
                window.prendaEditorGlobal = new PrendaEditor({ modalId: 'modal-agregar-prenda-nueva' });
            }

            // Limpiar estado previo completo
            window.prendaActual = null;
            window.prendaEditIndex = null;
            window.telasAgregadas = [];
            window.telasCreacion = [];
            window.imagenesTelaModalNueva = [];
            window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
            window.procesosSeleccionados = {};
            window.cantidadSoloSeleccionada = null;
            if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.limpiar === 'function') {
                window.imagenesPrendaStorage.limpiar();
            }
            if (window.imagenesTelaStorage && typeof window.imagenesTelaStorage.limpiar === 'function') {
                window.imagenesTelaStorage.limpiar();
            }
            if (window.imagenesAEliminar) window.imagenesAEliminar = [];
            if (window.procesosParaEliminarIds) window.procesosParaEliminarIds.clear();

            // Abrir modal limpio
            const editor = window.prendaEditorGlobal;
            if (editor && typeof editor.abrirModal === 'function') {
                editor.abrirModal(false, null);
            } else {
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) modal.style.display = 'flex';
            }

            // Limpiar campos del formulario
            const nombreInput = document.getElementById('nueva-prenda-nombre');
            if (nombreInput) nombreInput.value = '';
            const descripcionInput = document.getElementById('nueva-prenda-descripcion');
            if (descripcionInput) descripcionInput.value = '';
            const origenSelect = document.getElementById('nueva-prenda-origen-select');
            if (origenSelect) origenSelect.value = 'bodega';

            // Limpiar previews
            const previewPrenda = document.getElementById('nueva-prenda-foto-preview');
            if (previewPrenda) {
                previewPrenda.innerHTML = '<span class="material-symbols-rounded" style="font-size: 36px; color: #93c5fd;">add_photo_alternate</span><span style="font-size: 13px; color: #60a5fa;">Arrastra o haz clic</span>';
            }
            const contadorFoto = document.getElementById('nueva-prenda-foto-contador');
            if (contadorFoto) contadorFoto.textContent = '';

            // Vaciar chips de telas
            if (typeof window.renderizarTelasChips === 'function') {
                window.renderizarTelasChips();
            }

            // Limpiar checkboxes de procesos
            document.querySelectorAll('input[name="nueva-prenda-procesos"]').forEach(cb => {
                cb._ignorarOnclick = true;
                cb.checked = false;
                cb._ignorarOnclick = false;
            });

            // Configurar botón y título para modo "Agregar"
            const btnGuardar = document.getElementById('btn-guardar-prenda');
            if (btnGuardar) {
                btnGuardar.innerHTML = '<span class="material-symbols-rounded">check</span>Agregar Prenda';
                btnGuardar.className = 'btn btn-primary';
            }
            const tituloModal = document.getElementById('modal-prenda-texto') || document.getElementById('modal-prenda-titulo');
            if (tituloModal) {
                tituloModal.textContent = 'Agregar Prenda Nueva';
            }

            console.log('[PrendaAgregarPedido] 🆕 Modal abierto en modo AGREGAR para pedido:', pedidoId);

        } catch (error) {
            console.error('[PrendaAgregarPedido] Error al abrir modal:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se pudo abrir el formulario: ' + error.message, 'error');
            }
        }
    };

    // ====================================================
    // 2. _enviarNuevaPrendaAPI — POST al backend
    // ====================================================
    async function _enviarNuevaPrendaAPI(pedidoId) {
        try {
            // Recolectar datos del formulario
            let datos = _recolectarDatosFormulario();
            if (!datos) {
                console.error('[PrendaAgregarPedido] No se pudieron recolectar datos');
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Validación', 'Por favor completa los datos requeridos de la prenda', 'warning');
                }
                return;
            }

            // Validar nombre
            if (!datos.nombre_prenda && !datos.nombre) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Validación', 'El nombre de la prenda es requerido', 'warning');
                }
                return;
            }

            // Pedir novedad/justificación
            const novedad = await _pedirNovedad();
            if (novedad === null) return;

            // Loading
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Agregando prenda...',
                    text: 'Guardando nueva prenda en el pedido',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    customClass: { container: 'swal-galeria-container' },
                    didOpen: () => Swal.showLoading()
                });
            }

            // Construir FormData
            const formData = new FormData();
            formData.append('nombre_prenda', datos.nombre_prenda || datos.nombre || '');
            formData.append('descripcion', datos.descripcion || '');
            formData.append('novedad', novedad);

            // Origen
            const origen = datos.origen || (datos.de_bodega == 1 ? 'bodega' : 'confeccion');
            formData.append('origen', origen);

            // Tallas
            const tallasData = datos.tallas || datos.cantidad_talla || {};
            if (tallasData && typeof tallasData === 'object' && Object.keys(tallasData).length > 0) {
                formData.append('cantidad_talla', JSON.stringify(tallasData));
            }

            // Solo cantidad (sin tallas)
            if (window.cantidadSoloSeleccionada && window.cantidadSoloSeleccionada > 0) {
                const cantidadSoloTallas = { GENERICO: { SIN_ESPECIFICAR: window.cantidadSoloSeleccionada } };
                formData.append('cantidad_talla', JSON.stringify(cantidadSoloTallas));
            }

            // Asignaciones de colores por talla
            if (datos.asignacionesColoresPorTalla) {
                formData.append('asignaciones_colores', JSON.stringify(datos.asignacionesColoresPorTalla));
            }

            // Procesos
            if (datos.procesos && typeof datos.procesos === 'object') {
                let procesosArray = [];
                const filesPorProceso = {};

                if (!Array.isArray(datos.procesos)) {
                    Object.entries(datos.procesos).forEach(([tipo, d], idx) => {
                        if (d.imagenes && Array.isArray(d.imagenes)) {
                            filesPorProceso[idx] = [];
                            d.imagenes.forEach(img => {
                                if (img instanceof File) {
                                    filesPorProceso[idx].push(img);
                                } else if (img?.file instanceof File) {
                                    filesPorProceso[idx].push(img.file);
                                }
                            });
                        }
                        procesosArray.push({
                            tipo: d.tipo || tipo,
                            nombre: d.nombre || tipo,
                            ubicaciones: d.ubicaciones || [],
                            observaciones: d.observaciones || '',
                            tallas: d.tallas || {},
                            estado: d.estado || 'PENDIENTE'
                        });
                    });

                    Object.entries(filesPorProceso).forEach(([idx, files]) => {
                        files.forEach((file) => {
                            formData.append(`fotosProcesoNuevo_${idx}[]`, file);
                        });
                    });
                } else {
                    procesosArray = datos.procesos;
                }

                formData.append('procesos', JSON.stringify(procesosArray));
            }

            // Imágenes de prenda
            const imgs = datos.imagenes || [];
            const imagenesExistentes = [];
            imgs.forEach((img) => {
                if (img instanceof File) {
                    formData.append('imagenes[]', img);
                } else if (img?.file instanceof File) {
                    formData.append('imagenes[]', img.file);
                } else if (img?.urlDesdeDB || img?.id || img?.url?.startsWith('/') || img?.ruta || img?.ruta_original) {
                    const url = img.url || img.ruta || img.ruta_webp || img.ruta_original || img.previewUrl || '';
                    imagenesExistentes.push({ id: img.id, url: url });
                }
            });
            formData.append('imagenes_existentes', JSON.stringify(imagenesExistentes));

            // Telas
            const telas = datos.telasAgregadas || datos.telas || [];
            if (telas.length > 0) {
                const telasJSON = telas.map((t, idx) => {
                    const telaData = {
                        tela: t.tela || t.nombre_tela || '',
                        color: t.color || t.color_nombre || '',
                        referencia: t.referencia || '',
                        tela_id: t.tela_id || 0,
                        color_id: t.color_id || 0
                    };
                    if (t.imagenes && Array.isArray(t.imagenes)) {
                        t.imagenes.forEach((img) => {
                            if (img instanceof File) {
                                formData.append(`fotos_tela[${idx}]`, img);
                            } else if (img?.file instanceof File) {
                                formData.append(`fotos_tela[${idx}]`, img.file);
                            }
                        });
                    }
                    return telaData;
                });
                formData.append('telas', JSON.stringify(telasJSON));
            }

            // Variantes
            if (datos.variantes && Object.keys(datos.variantes).length > 0) {
                formData.append('variantes', JSON.stringify(datos.variantes));
            }

            // Enviar
            const urlPrefix = _getUrlPrefix();
            const saveUrl = `${urlPrefix.save}/${pedidoId}/agregar-prenda`;
            console.log('[PrendaAgregarPedido] 📤 POST', saveUrl);

            const response = await fetch(saveUrl, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
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
                console.error('[PrendaAgregarPedido] Error API:', errorMsg);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: `No se pudo agregar la prenda: ${errorMsg}`, customClass: { container: 'swal-galeria-container' } });
                }
                return;
            }

            const result = await response.json();
            console.log('[PrendaAgregarPedido] ✅ Prenda agregada:', result);

            // Agregar a datos locales
            if (window.datosEdicionPedido && result.prenda) {
                if (!window.datosEdicionPedido.prendas) {
                    window.datosEdicionPedido.prendas = [];
                }
                window.datosEdicionPedido.prendas.push(result.prenda);
            }

            // Éxito
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '✅ Prenda agregada',
                    text: 'La prenda se agregó correctamente al pedido',
                    timer: 1800,
                    showConfirmButton: false,
                    customClass: { container: 'swal-galeria-container' }
                });
            }

            // Cerrar modal y recargar
            setTimeout(function() {
                if (typeof window.cerrarModalPrendaNueva === 'function') {
                    window.cerrarModalPrendaNueva();
                }
                window._editandoPrendaDePedido = null;

                setTimeout(() => {
                    window.location.reload();
                }, 200);
            }, 1900);

        } catch (error) {
            console.error('[PrendaAgregarPedido] Error de red:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión al agregar la prenda', customClass: { container: 'swal-galeria-container' } });
            }
        }
    }

    // ====================================================
    // 3. Interceptar agregarPrendaNueva para modo AGREGAR
    //    Se ejecuta DESPUÉS del adapter para wrappear su función
    // ====================================================
    function _interceptarGuardarPrenda() {
        const originalAgregarPrendaNueva = window.agregarPrendaNueva;

        window.agregarPrendaNueva = function() {
            const editContext = window._editandoPrendaDePedido;

            // Si estamos en modo AGREGAR, redirigir a nuestro flujo
            if (editContext && editContext.modoAgregar) {
                console.log('[PrendaAgregarPedido] 🆕 Interceptado → guardando NUEVA prenda para pedido:', editContext.pedidoId);
                _enviarNuevaPrendaAPI(editContext.pedidoId);
                return;
            }

            // De lo contrario, delegar al adapter original (edición)
            if (typeof originalAgregarPrendaNueva === 'function') {
                return originalAgregarPrendaNueva.apply(this, arguments);
            }
        };

        console.log('[PrendaAgregarPedido] ✅ agregarPrendaNueva interceptada');
    }

    // Interceptar cuando el DOM esté listo (después de que el adapter defina agregarPrendaNueva)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(_interceptarGuardarPrenda, 500));
    } else {
        setTimeout(_interceptarGuardarPrenda, 500);
    }

})();
