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
        if (telasBody) telasBody.innerHTML = '';

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

        // Fallback: recolectar campos básicos manualmente
        const nombre = document.getElementById('nueva-prenda-nombre')?.value?.trim();
        const descripcion = document.getElementById('nueva-prenda-descripcion')?.value?.trim();
        const origenSelect = document.getElementById('nueva-prenda-origen-select')?.value || 'bodega';

        if (!nombre) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Validación', 'El nombre de la prenda es requerido', 'warning');
            }
            return null;
        }

        return {
            nombre_prenda: nombre,
            nombre: nombre,
            descripcion: descripcion,
            de_bodega: origenSelect === 'bodega' ? 1 : 0,
            // Incluir tallas si existen
            tallas: window.tallasRelacionales || {},
            // Incluir procesos si existen
            procesos: window.procesosSeleccionados || [],
            // Incluir telas si existen
            telas: window.telasCreacion || window.telasAgregadas || []
        };
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

            // Mostrar loading
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Guardando cambios...',
                    text: 'Actualizando la prenda',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
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
            if (datos.de_bodega !== undefined) {
                formData.append('de_bodega', datos.de_bodega);
                formData.append('origen', datos.de_bodega == 1 ? 'bodega' : 'confeccion');
            }

            // Tallas (JSON)
            if (datos.tallas || datos.cantidad_talla) {
                formData.append('tallas', JSON.stringify(datos.tallas || datos.cantidad_talla || {}));
            }

            // Variantes (JSON)
            if (datos.variantes) {
                formData.append('variantes', JSON.stringify(datos.variantes));
            }

            // Colores y Telas (JSON)
            if (datos.colores_telas || datos.telasAgregadas || datos.telas) {
                const coloresTelas = datos.colores_telas || datos.telasAgregadas || datos.telas || [];
                formData.append('colores_telas', JSON.stringify(coloresTelas));
            }

            // Procesos (JSON)
            if (datos.procesos) {
                formData.append('procesos', JSON.stringify(datos.procesos));
            }

            // Imágenes de prenda (File objects)
            if (datos.imagenes && Array.isArray(datos.imagenes)) {
                datos.imagenes.forEach((img, idx) => {
                    if (img instanceof File) {
                        formData.append('imagenes[]', img);
                    }
                });
            }

            // Imágenes existentes a preservar
            if (datos.imagenes_existentes) {
                formData.append('imagenes_existentes', JSON.stringify(datos.imagenes_existentes));
            }

            console.log('[PedidosAdapter] 📤 Enviando a POST /asesores/pedidos/' + pedidoId + '/actualizar-prenda');

            const response = await fetch(`/asesores/pedidos/${pedidoId}/actualizar-prenda`, {
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
                    Swal.fire('Error', `No se pudo guardar: ${errorMsg}`, 'error');
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

            // Mostrar éxito
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '✅ Prenda actualizada',
                    text: 'Los cambios se guardaron correctamente',
                    timer: 1800,
                    showConfirmButton: false
                });
            }

            // Cerrar modal después de breve delay
            setTimeout(function() {
                cerrarModalPrendaNueva();
            }, 1900);

        } catch (error) {
            console.error('[PedidosAdapter] Error de red:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Error de conexión al guardar la prenda', 'error');
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
                console.log('[PedidosAdapter] 📡 Fetching datos de BD:', `/asesores/pedidos-produccion/${pedidoId}/prenda/${prendaId}/datos`);
                const response = await fetch(`/asesores/pedidos-produccion/${pedidoId}/prenda/${prendaId}/datos`, {
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
