/**
 * Gestión de Ítems - Capa de Presentación (Refactorizado - SOLID)
 * 
 * Esta clase orquesta los servicios especializados que manejan:
 * - ItemFormCollector: Recolecta datos de formularios
 * - PrendaEditor: Gestiona edición de prendas
 * - ItemRenderer: Renderiza UI
 * - NotificationService: Maneja notificaciones
 * - ItemAPIService: Comunica con backend
 */

class GestionItemsUI {
    constructor(options = {}) {
        this.prendaEditIndex = null;
        this.items = [];
        this.prendas = [];      // Array separado para prendas
        this.epps = [];         // Array separado para EPPs
        this.ordenItems = [];   // Orden de inserción: [{tipo: 'prenda', index: 0}, {tipo: 'epp', index: 0}, ...]
        
        // Inicializar servicios con validación de disponibilidad
        try {
            this.notificationService = options.notificationService || (typeof NotificationService !== 'undefined' ? new NotificationService() : null);
            this.apiService = options.apiService || (typeof ItemAPIService !== 'undefined' ? new ItemAPIService() : null);
            this.formCollector = options.formCollector || (typeof ItemFormCollector !== 'undefined' ? new ItemFormCollector() : null);
            this.renderer = options.renderer || (typeof ItemRenderer !== 'undefined' && this.apiService ? new ItemRenderer({ apiService: this.apiService }) : null);
            this.prendaEditor = options.prendaEditor || (typeof PrendaEditor !== 'undefined' && this.notificationService ? new PrendaEditor({ notificationService: this.notificationService }) : null);
            
            // Solo inicializar si los servicios esenciales están disponibles
            if (this.formCollector && this.notificationService) {
                this.inicializar();
            } else {

            }
        } catch (error) {

        }
    }

    /**
     * Obtener todos los items en orden de inserción
     */
    obtenerItemsOrdenados() {
        const itemsOrdenados = [];
        console.log('[gestionItemsUI] 📋 obtenerItemsOrdenados() - this.ordenItems:', JSON.stringify(this.ordenItems));
        console.log('[gestionItemsUI] 📋 obtenerItemsOrdenados() - this.prendas:', this.prendas.length, 'items');
        console.log('[gestionItemsUI] 📋 obtenerItemsOrdenados() - this.epps:', this.epps.length, 'items');
        
        this.ordenItems.forEach(({ tipo, index }) => {
            if (tipo === 'prenda' && this.prendas[index]) {
                itemsOrdenados.push(this.prendas[index]);
                console.log('[gestionItemsUI]  Agregado PRENDA index:', index);
            } else if (tipo === 'epp' && this.epps[index]) {
                itemsOrdenados.push(this.epps[index]);
                console.log('[gestionItemsUI]  Agregado EPP index:', index);
            } else {
                console.log('[gestionItemsUI]  ITEM NO ENCONTRADO - tipo:', tipo, 'index:', index);
            }
        });
        
        console.log('[gestionItemsUI]  Total items a renderizar:', itemsOrdenados.length);
        return itemsOrdenados;
    }

    /**
     * Agregar prenda y registrar en orden
     */
    agregarPrendaAlOrden(prenda) {
        const index = this.prendas.length;
        
        this.prendas.push(prenda);
        this.ordenItems.push({ tipo: 'prenda', index });
        
        console.log('[gestionItemsUI]  agregarPrendaAlOrden() - PRENDA agregada:', prenda.nombre_prenda);
        console.log('[gestionItemsUI]  agregarPrendaAlOrden() - Nuevo index PRENDA:', index);
        console.log('[gestionItemsUI]  agregarPrendaAlOrden() - this.ordenItems ahora:', JSON.stringify(this.ordenItems));
        console.log('[gestionItemsUI]  agregarPrendaAlOrden() - Total PRENDAS:', this.prendas.length);
        console.log('[gestionItemsUI]  agregarPrendaAlOrden() - Total EPPs:', this.epps.length);
    }

    /**
     * Agregar EPP y registrar en orden
     */
    agregarEPPAlOrden(epp) {
        const index = this.epps.length;
        this.epps.push(epp);
        this.ordenItems.push({ tipo: 'epp', index });
        
        console.log('[gestionItemsUI]  agregarEPPAlOrden() - EPP agregado:', epp.nombre_completo || epp.nombre);
        console.log('[gestionItemsUI]  agregarEPPAlOrden() - Nuevo index EPP:', index);
        console.log('[gestionItemsUI]  agregarEPPAlOrden() - this.ordenItems ahora:', JSON.stringify(this.ordenItems));
        console.log('[gestionItemsUI]  agregarEPPAlOrden() - Total EPPs:', this.epps.length);

        return index;
    }

    /**
     * Método público para agregar EPP desde modal externo
     */
    async agregarEPPDesdeModal(eppData) {
        try {

            console.log('[gestionItemsUI] 📥 agregarEPPDesdeModal() iniciado con EPP:', eppData.nombre_completo || eppData.nombre);
            
            // Agregar al orden
            this.agregarEPPAlOrden(eppData);
            
            console.log('[gestionItemsUI] 📥 Después de agregarEPPAlOrden()');
            console.log('[gestionItemsUI] 📥 this.epps:', this.epps.length);
            console.log('[gestionItemsUI] 📥 this.ordenItems:', JSON.stringify(this.ordenItems));
            
            // Notificar éxito
            this.notificationService?.exito('EPP agregado correctamente');
            
            // Actualizar visualización en orden
            if (this.renderer) {
                const itemsOrdenados = this.obtenerItemsOrdenados();
                console.log('[gestionItemsUI] 📥 Renderizando', itemsOrdenados.length, 'items');
                await this.renderer.actualizar(itemsOrdenados);
            }
            
            return true;
        } catch (error) {

            this.notificationService?.error('Error al agregar EPP: ' + error.message);
            return false;
        }
    }

    inicializar() {
        this.attachEventListeners();
        
        if (document.getElementById('btn-agregar-item-cotizacion') || 
            document.getElementById('btn-agregar-item-tipo')) {
            this.cargarItems();
        }
    }

    attachEventListeners() {
        document.getElementById('btn-agregar-item-cotizacion')?.addEventListener('click', 
            () => this.abrirModalSeleccionPrendas());

        document.getElementById('btn-agregar-item-tipo')?.addEventListener('click',
            () => this.abrirModalAgregarPrendaNueva());

        document.getElementById('btn-vista-previa')?.addEventListener('click',
            () => this.mostrarVistaPreviaFactura());

        document.getElementById('formCrearPedidoEditable')?.addEventListener('submit',
            (e) => this.manejarSubmitFormulario(e));
    }

    async cargarItems() {
        try {
            if (!this.apiService || !this.renderer || !this.notificationService) {

                return;
            }
            const resultado = await this.apiService.obtenerItems();
            this.items = resultado.items;
            //  Usar obtenerItemsOrdenados() para preservar prendas y EPPs en orden
            const itemsOrdenados = this.obtenerItemsOrdenados();
            await this.renderer.actualizar(itemsOrdenados);
        } catch (error) {

            if (this.notificationService) {
                this.notificationService.error('Error al cargar ítems');
            }
        }
    }

    async agregarItem(itemData) {
        try {
            if (!this.apiService || !this.renderer || !this.notificationService) {

                return false;
            }
            const resultado = await this.apiService.agregarItem(itemData);
            if (resultado.success) {
                this.items = resultado.items;
                //  Usar obtenerItemsOrdenados() para preservar prendas y EPPs en orden
                const itemsOrdenados = this.obtenerItemsOrdenados();
                await this.renderer.actualizar(itemsOrdenados);
                this.notificationService.exito('Ítem agregado correctamente');
                return true;
            }
        } catch (error) {
            if (this.notificationService) {
                this.notificationService.error('Error: ' + error.message);
            }
            return false;
        }
    }

    async eliminarItem(index) {
        // Mostrar confirmación con SweetAlert
        const result = await Swal.fire({
            title: '¿Eliminar este ítem?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        });

        if (!result.isConfirmed) return;

        try {
            if (!this.apiService || !this.renderer || !this.notificationService) {

                return;
            }
            
            //  Buscar qué tipo de item es (prenda o epp)
            const itemAEliminar = this.ordenItems.find((item, posicion) => {
                if (item.tipo === 'prenda') {
                    return this.prendas[item.index] && this.prendas[item.index]._id === undefined && posicion === index;
                } else if (item.tipo === 'epp') {
                    return this.epps[item.index] && this.epps[item.index]._id === undefined && posicion === index;
                }
                return false;
            });
            
            //  ALTERNATIVA: buscar por posición en itemsOrdenados
            const itemsOrdenados = this.obtenerItemsOrdenados();
            if (index >= 0 && index < itemsOrdenados.length) {
                const itemEnPosicion = itemsOrdenados[index];
                
                // Encontrar qué array y qué índice tiene este item
                let tipoBuscado, indiceBuscado;
                
                if (itemEnPosicion.nombre_prenda) {
                    // Es una prenda - buscar en this.prendas
                    tipoBuscado = 'prenda';
                    indiceBuscado = this.prendas.findIndex(p => p === itemEnPosicion);
                } else if (itemEnPosicion.nombre_completo || itemEnPosicion.nombre) {
                    // Es un EPP - buscar en this.epps
                    tipoBuscado = 'epp';
                    indiceBuscado = this.epps.findIndex(e => e === itemEnPosicion);
                }
                
                console.log(`[eliminarItem]  Eliminando item en posición ${index}:`, {
                    tipo: tipoBuscado,
                    indiceEnArray: indiceBuscado,
                    item: itemEnPosicion
                });
                
                // Eliminar de los arrays correspondientes
                if (tipoBuscado === 'prenda' && indiceBuscado >= 0) {
                    this.prendas.splice(indiceBuscado, 1);
                    console.log(`[eliminarItem]  Prenda eliminada del array. Quedan: ${this.prendas.length}`);
                } else if (tipoBuscado === 'epp' && indiceBuscado >= 0) {
                    this.epps.splice(indiceBuscado, 1);
                    console.log(`[eliminarItem]  EPP eliminado del array. Quedan: ${this.epps.length}`);
                }
                
                // Eliminar de ordenItems por posición
                this.ordenItems.splice(index, 1);
                
                // Reconstruir índices en ordenItems después de la eliminación
                let prendaIdx = 0, eppIdx = 0;
                this.ordenItems.forEach(item => {
                    if (item.tipo === 'prenda') {
                        item.index = prendaIdx;
                        prendaIdx++;
                    } else if (item.tipo === 'epp') {
                        item.index = eppIdx;
                        eppIdx++;
                    }
                });
                
                console.log(`[eliminarItem]  ordenItems actualizado:`, JSON.stringify(this.ordenItems));
                
                //  SINCRONIZAR CON GESTOR: Eliminar también del gestorPrendaSinCotizacion si existe
                if (tipoBuscado === 'prenda' && window.gestorPrendaSinCotizacion?.eliminar) {
                    console.log(`[eliminarItem]  Sincronizando eliminación en gestorPrendaSinCotizacion (índice original: ${indiceBuscado})`);
                    window.gestorPrendaSinCotizacion.eliminar(indiceBuscado);
                }
            }
            
            // Renderizar items actualizados
            const itemsActualizados = this.obtenerItemsOrdenados();
            await this.renderer.actualizar(itemsActualizados);
            this.notificationService.exito('Ítem eliminado');
        } catch (error) {
            if (this.notificationService) {
                this.notificationService.error('Error: ' + error.message);
            }
        }
    }

    abrirModalSeleccionPrendas() {
        if (window.abrirModalSeleccionPrendas) {
            window.abrirModalSeleccionPrendas();
        }
    }

    /**
     * Abrir modal de prenda (crear o editar)
     * 
     * Flujo: FSM guard → OPENING → catálogos → shown.bs.modal({once}) → DragDrop → OPEN
     */
    async abrirModalAgregarPrendaNueva() {
        const fsm = window.__MODAL_FSM__;

        // Guard: FSM previene doble apertura
        if (fsm && !fsm.puedeAbrir()) {
            console.warn('[abrirModal] Bloqueado por FSM (estado:', fsm.obtenerEstado(), ')');
            return;
        }

        try {
            // FSM → OPENING
            if (fsm) fsm.cambiarEstado('OPENING', { origen: 'abrirModalAgregarPrendaNueva' });

            // 1. Cargar catálogos (deduplicado automáticamente)
            if (typeof window.cargarCatalogosModal === 'function') {
                await window.cargarCatalogosModal();
            }

            // 2. Registrar listener shown ANTES de abrir (se auto-elimina con {once})
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            if (modal) {
                modal.addEventListener('shown.bs.modal', () => {
                    // DragDrop init (guard interno previene doble init)
                    if (window.DragDropManager) {
                        window.DragDropManager.inicializar();
                    }
                    // FSM → OPEN
                    if (fsm) fsm.cambiarEstado('OPEN', { origen: 'shown.bs.modal' });
                    console.log('[abrirModal] ✅ Modal OPEN — DragDrop inicializado');
                }, { once: true });
            }

            // 3. Abrir modal (dispara shown.bs.modal sincrónicamente)
            const esEdicion = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;

            if (esEdicion) {
                const prendaAEditar = this.prendas[this.prendaEditIndex];
                if (prendaAEditar && this.prendaEditor) {
                    this.prendaEditor.cargarPrendaEnModal(prendaAEditar, this.prendaEditIndex);
                }
            } else {
                if (this.prendaEditor) {
                    this.prendaEditor.abrirModal(false, null);
                }
            }

        } catch (error) {
            console.error('[abrirModalAgregarPrendaNueva] ERROR:', error);
            // Reset de emergencia
            if (fsm) fsm.cambiarEstado('CLOSED', { error: error.message });

            if (typeof NotificationService !== 'undefined' && NotificationService) {
                NotificationService.error('Error abriendo modal: ' + error.message);
            }
        }
    }

    /**
     * Cerrar modal de agregar/editar prenda
     */
    cerrarModalAgregarPrendaNueva() {
        const fsm = window.__MODAL_FSM__;

        try {
            // FSM → CLOSING
            if (fsm) fsm.cambiarEstado('CLOSING', { origen: 'cerrarModalAgregarPrendaNueva' });

            // Resetear bandera de nueva prenda desde cotización
            if (this.prendaEditor) {
                this.prendaEditor.esNuevaPrendaDesdeCotizacion = false;
            }

            // Limpiar modal (oculta + limpia campos)
            if (typeof ModalCleanup !== 'undefined') {
                ModalCleanup.limpiarDespuésDeGuardar();
            } else {
                this.prendaEditIndex = null;
                if (this.prendaEditor) this.prendaEditor.prendaEditIndex = null;
                window.prendaEditIndex = null;
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) modal.style.display = 'none';
            }

            // Resetear editor
            if (this.prendaEditor) {
                this.prendaEditor.resetearEdicion();
            }

            // Destruir DragDrop (resetea inicializado=false para próxima apertura)
            if (window.DragDropManager) {
                window.DragDropManager.destruir();
            }

            // FSM → CLOSED
            if (fsm) fsm.cambiarEstado('CLOSED', { origen: 'cerrarModalAgregarPrendaNueva' });

        } catch (error) {
            console.error('[cerrarModal] ERROR:', error);
            if (fsm) fsm.cambiarEstado('CLOSED', { error: error.message });
        }
    }


    /**
     * Cargar datos de prenda en el modal para editar
     */
    cargarItemEnModal(prenda, prendaIndex) {
        this.prendaEditIndex = prendaIndex;
        this.prendaEditor.cargarPrendaEnModal(prenda, prendaIndex);
    }

    /**
     * Agregar prenda nueva - Recolectar datos del formulario modal y guardar
     */
    async agregarPrendaNueva() {
        try {
            // 🔥 CRÍTICO: Verificar estado ANTES de hacer nada
            console.log('[agregarPrendaNueva]  INICIO - Estado actual:');
            console.log('[agregarPrendaNueva]   - this.prendaEditIndex:', this.prendaEditIndex);
            console.log('[agregarPrendaNueva]   - this.prendas.length:', this.prendas.length);
            console.log('[agregarPrendaNueva]   - ¿Es edición?:', this.prendaEditIndex !== null && this.prendaEditIndex !== undefined);
            
            // Verificar si el servicio de notificaciones está disponible
            if (!this.notificationService) {
                console.warn('[GestionItemsUI]  notificationService no disponible, usando fallback');
                // Crear servicio de notificaciones temporal para este caso
                this.notificationService = typeof NotificationService !== 'undefined' ? new NotificationService() : {
                    success: (msg) => console.log('', msg),
                    error: (msg) => console.error('', msg),
                    warning: (msg) => console.warn('', msg)
                };
            }

            // Recolectar datos del formulario modal usando el componente extraído
            window.prendaFormCollector.setNotificationService(this.notificationService);
            const prendaData = window.prendaFormCollector.construirPrendaDesdeFormulario(
                this.prendaEditIndex,
                this.prendas
            );
            
            if (!prendaData) {

                this.notificationService?.error('Por favor completa los datos de la prenda');
                return;
            }

            // Validar que al menos haya seleccionado tallas
            const tieneTallas = prendaData.cantidad_talla && 
                Object.values(prendaData.cantidad_talla).some(genero => 
                    Object.keys(genero).length > 0
                );

            console.log('[gestion-items-pedido]  Validación de tallas:');
            console.log('[gestion-items-pedido]   - prendaData.cantidad_talla:', prendaData.cantidad_talla);
            console.log('[gestion-items-pedido]   - tieneTallas:', tieneTallas);

            if (!tieneTallas) {
                this.notificationService?.advertencia(' Por favor selecciona al menos una talla para la prenda');
                console.log('[gestion-items-pedido]  Validación FALLIDA: No hay tallas');
                return;
            }

            console.log('[gestion-items-pedido]  Validación EXITOSA: Hay tallas, procediendo a guardar');

            // PROCESAR TIPO DE MANGA: Crear si no existe
            if (prendaData.variantes?.tipo_manga_crear && prendaData.variantes?.tipo_manga) {
                console.log('[gestion-items-pedido]  Creando tipo de manga:', prendaData.variantes.tipo_manga);
                
                try {
                    const response = await fetch('/api/public/tipos-manga', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({ nombre: prendaData.variantes.tipo_manga })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success && result.data) {
                        // Guardar el ID recién creado
                        prendaData.variantes.tipo_manga_id = result.data.id;
                        
                        // Agregar al datalist para futuras búsquedas
                        const datalist = document.getElementById('opciones-manga');
                        if (datalist) {
                            const newOption = document.createElement('option');
                            newOption.value = result.data.nombre;
                            newOption.dataset.id = result.data.id;
                            datalist.appendChild(newOption);
                        }
                        
                        console.log('[gestion-items-pedido] Tipo de manga creado:', {
                            id: result.data.id,
                            nombre: result.data.nombre
                        });
                        
                        // Limpiar flag de creación
                        delete prendaData.variantes.tipo_manga_crear;
                    } else {
                        console.warn('[gestion-items-pedido]  No se pudo crear tipo de manga:', result);
                        this.notificationService?.advertencia('No se pudo crear el tipo de manga, se guardará solo el nombre');
                    }
                } catch (error) {
                    console.error('[gestion-items-pedido]  Error creando tipo de manga:', error);
                    this.notificationService?.advertencia('Error al crear tipo de manga, se guardará solo el nombre');
                }
            }


            // Verificar si estamos en un pedido existente
            const enPedidoExistente = window.datosEdicionPedido && (window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido);
            // IMPORTANTE: Usar SIEMPRE el .id de la BD, nunca numero_pedido
            // El numero_pedido es solo para mostrar en UI, no para rutas de API
            const pedidoId = enPedidoExistente ? window.datosEdicionPedido.id : null;
            
            if (!pedidoId && enPedidoExistente) {

                // Solo si realmente no hay id, usar numero_pedido (pero esto NO debería pasar en producción)
            }

            // Si es edición (prendaEditIndex !== null), actualizar; si no, agregar nueva
            //  NUEVO: Detectar si es nueva prenda desde cotización
            const esNuevaDesdeCotz = this.prendaEditor?.esNuevaPrendaDesdeCotizacion === true;
            const esEdicionReal = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;
            
            console.log('[guardarPrenda]  DETECCIÓN CRÍTICA:', {
                esNuevaDesdeCotz: esNuevaDesdeCotz,
                esEdicionReal: esEdicionReal,
                prendaEditIndex: this.prendaEditIndex,
                esNuevaPrendaDesdeCotizacion: this.prendaEditor?.esNuevaPrendaDesdeCotizacion
            });
            
            // Determinar si vamos a editar o crear nueva
            const vamosAEditar = esEdicionReal && !esNuevaDesdeCotz;
            console.log('[guardarPrenda] 🎯 ACCIÓN A EJECUTAR:', vamosAEditar ? '✏️ EDITAR' : ' AGREGAR NUEVA');
            
            if (vamosAEditar) {

                
                // SI ESTAMOS EDITANDO EN UN PEDIDO EXISTENTE, MOSTRAR MODAL DE NOVEDADES
                if (enPedidoExistente) {

                    
                    // Obtener la prenda original desde window.prendaEnEdicion (guardada por prenda-editor-modal.js)
                    const prendaOriginal = window.prendaEnEdicion?.prendaOriginal;

                    //  DEBUG: Verificar qué contiene window.prendaEnEdicion
                    console.log('[gestion-items-pedido]  DEBUG window.prendaEnEdicion:', {
                        existe: !!window.prendaEnEdicion,
                        prendaOriginal: window.prendaEnEdicion?.prendaOriginal,
                        prendaOriginalId: window.prendaEnEdicion?.prendaOriginal?.prenda_pedido_id || window.prendaEnEdicion?.prendaOriginal?.id,
                        pedidoId: window.prendaEnEdicion?.pedidoId,
                        prendasIndex: window.prendaEnEdicion?.prendasIndex
                    });

                    
                    // Agregar el ID de la prenda original a prendaData
                    prendaData.prenda_pedido_id = prendaOriginal?.prenda_pedido_id || prendaOriginal?.id;

                    //  DEBUG: Verificar qué se asignó
                    console.log('[gestion-items-pedido]  DEBUG asignación de ID:', {
                        prendaOriginalId: prendaOriginal?.prenda_pedido_id || prendaOriginal?.id,
                        prendaDataPrendaPedidoId: prendaData.prenda_pedido_id,
                        prendaDataId: prendaData.id
                    });

                    // 🔥 CRÍTICO: Incluir telas nuevas si fueron agregadas
                    // Si hay telas en window.telasAgg o window.telasCreacion, incluirlas en prendaData
                    if ((window.telasAgregadas && window.telasAgregadas.length > 0) || 
                        (window.telasCreacion && window.telasCreacion.length > 0)) {
                        
                        const telasAIncluir = window.telasAgregadas?.length > 0 ? window.telasAgregadas : window.telasCreacion;
                        prendaData.telasAgregadas = telasAIncluir;
                        
                        console.log('[gestion-items-pedido]  Telas nuevas incluidas en prendaData:', {
                            cantidad: telasAIncluir.length,
                            origen: window.telasAgregadas?.length > 0 ? 'telasAgregadas' : 'telasCreacion',
                            telas: telasAIncluir.map(t => ({ color: t.color, tela: t.tela, imagenes: t.imagenes?.length }))
                        });
                    }
                    
                    await window.modalNovedadEditacion.mostrarModalYActualizar(pedidoId, prendaData, this.prendaEditIndex);
                    // El modal de novedades maneja todo: actualización, cierre, etc.
                    // NO continuamos aquí
                    return;
                } else {
                    // Solo en memoria - sin novedades
                    console.log('[guardarPrenda] 💾 MODO CREACIÓN: Actualizando prenda en memoria');
                    console.log('[guardarPrenda]   - this.prendaEditIndex:', this.prendaEditIndex);
                    console.log('[guardarPrenda]   - this.prendas.length:', this.prendas.length);
                    console.log('[guardarPrenda]   - ¿Existe prenda en este index?:', !!this.prendas[this.prendaEditIndex]);

                    if (this.prendas[this.prendaEditIndex]) {
                        // 🔥 MANEJO ESPECÍFICO: Eliminación de imágenes en modo CREATE
                        // Si estamos en modo CREATE (no edición desde backend) y se eliminaron todas las imágenes
                        const esModoCreate = !window.datosEdicionPedido || (!window.datosEdicionPedido.id && !window.datosEdicionPedido.numero_pedido);
                        const imagenesStorage = window.imagenesPrendaStorage?.obtenerImagenes?.() || [];
                        const seEliminaronTodasLasImagenes = imagenesStorage.length === 0;
                        
                        if (esModoCreate && seEliminaronTodasLasImagenes) {
                            console.log('🗑️ [GESTION-ITEMS] Modo CREATE: Todas las imágenes eliminadas, actualizando array a []');
                            
                            // Forzar que prendaData.imagenes sea array vacío
                            prendaData.imagenes = [];
                            
                            // Actualizar directamente en memoria también
                            this.prendas[this.prendaEditIndex].imagenes = [];
                            
                            console.log(' [GESTION-ITEMS] Array de imágenes actualizado a [] en memoria y en prendaData');
                        }
                        
                        //  ANTES: Estado de la prenda antes de actualizar
                        const prendaAnterior = JSON.parse(JSON.stringify(this.prendas[this.prendaEditIndex]));
                        
                        // Actualizar prenda con los datos modificados
                        this.prendas[this.prendaEditIndex] = { ...this.prendas[this.prendaEditIndex], ...prendaData };
                        
                        // 🟢 DESPUÉS: Verificar que se actualizó
                        const prendaActualizada = this.prendas[this.prendaEditIndex];
                        console.log('[guardarPrenda] ✏️ PRENDA ACTUALIZADA:');
                        console.log('[guardarPrenda]   - Nombre ANTES:', prendaAnterior.nombre_prenda);
                        console.log('[guardarPrenda]   - Nombre DESPUÉS:', prendaActualizada.nombre_prenda);
                        console.log('[guardarPrenda]   - Descripción ANTES:', prendaAnterior.descripcion);
                        console.log('[guardarPrenda]   - Descripción DESPUÉS:', prendaActualizada.descripcion);
                        
                        //  CRÍTICO: Renderizar inmediatamente después de actualizar
                        console.log('[gestionItemsUI] ✏️ Prenda actualizada, re-renderizando...');
                        if (this.renderer) {
                            const itemsOrdenados = this.obtenerItemsOrdenados();
                            this.renderer.actualizar(itemsOrdenados).catch(err => {
                                console.error('[gestionItemsUI] Error renderizando:', err);
                            });
                        }

                        this.notificationService?.exito('Prenda actualizada correctamente');
                    } else {
                        console.error('[guardarPrenda]  ERROR: No existe prenda en index', this.prendaEditIndex);
                    }
                    
                    //  Cerrar modal AQUÍ en modo edición
                    this.cerrarModalAgregarPrendaNueva();
                    
                    //  IMPORTANTE: Salir completamente para evitar que se agregue nueva prenda
                    return;
                }
            } else {

                
                // GUARDAR EN LA BASE DE DATOS SI ESTAMOS EN EDICIÓN DE PEDIDO EXISTENTE
                if (enPedidoExistente) {

                    
                    // PASO 1: MOSTRAR MODAL DE NOVEDAD USANDO COMPONENTE EXTRAÍDO
                    await window.modalNovedadPrenda.mostrarModalYGuardar(pedidoId, prendaData);
                    // El modal de novedades maneja todo: guardado, cierre, etc.
                    // NO continuamos aquí
                    return;
                    
                } else {

                    this.notificationService?.exito('Prenda agregada correctamente');
                    
                    //  NORMALIZAR: cambiar tipo de 'prenda_nueva' a 'prenda' para que ItemFormCollector lo procese
                    if (prendaData.tipo === 'prenda_nueva') {
                        prendaData.tipo = 'prenda';
                    }
                    
                    console.log('[gestionItemsUI]  Prenda normalizada antes de agregar:', {
                        tipo: prendaData.tipo,
                        nombre_prenda: prendaData.nombre_prenda,
                        cantidad_talla: prendaData.cantidad_talla,
                        telas: prendaData.telas?.length || 0,
                        telasAgregadas: prendaData.telasAgregadas?.length || 0,
                        contenido_telasAgregadas: prendaData.telasAgregadas,
                        asignacionesColoresPorTalla: prendaData.asignacionesColoresPorTalla
                    });
                    
                    // Agregar prenda al orden
                    this.agregarPrendaAlOrden(prendaData);

                }
            }

            // Cerrar el modal
            this.cerrarModalAgregarPrendaNueva();
            
            console.log('[gestionItemsUI] 📤 PUNTO CRÍTICO: Después de agregar prenda');
            console.log('[gestionItemsUI] 📤 this.prendas:', this.prendas.length);
            console.log('[gestionItemsUI] 📤 this.epps:', this.epps.length);
            console.log('[gestionItemsUI] 📤 this.ordenItems:', JSON.stringify(this.ordenItems));
            
            //  Solo en modo CREACIÓN: renderizar
            // En modo EDICIÓN ya salimos arriba con return
            if (this.renderer) {
                const itemsOrdenados = this.obtenerItemsOrdenados();
                console.log('[gestionItemsUI] 📤 Llamando renderer.actualizar() con', itemsOrdenados.length, 'items (CREACIÓN)');
                await this.renderer.actualizar(itemsOrdenados);
            }

            // IMPORTANTE: Actualizar window.datosEdicionPedido.prendas (sin reabrirse automáticamente)
            if (window.datosEdicionPedido) {

                window.datosEdicionPedido.prendas = this.prendas;

                // El modal de éxito se mostrará y el usuario decidirá si ver la lista
            }

        } catch (error) {

            this.notificationService?.error('Error al agregar prenda: ' + error.message);
        }
    }

    /**
     * Construir objeto de prenda desde el formulario modal
     * Recolecta: nombre, descripción, origen, imágenes, telas, tallas, variaciones, procesos
     * @private
     */
    /**
     * Actualizar prenda existente
     */
    async actualizarPrendaExistente() {
        await this.prendaEditor.actualizarPrendaExistente();
        this.prendaEditIndex = null;
    }

    async manejarSubmitFormulario(e) {
        e.preventDefault();

        try {
            if (!this.formCollector || !this.apiService || !this.notificationService) {

                return;
            }
            
            const clienteInput = document.getElementById('cliente_editable');
            if (!clienteInput?.value || clienteInput.value.trim() === '') {
                this.notificationService.error('El cliente es requerido');
                clienteInput?.focus();
                return;
            }

            const pedidoData = this.formCollector.recolectarDatosPedido();
            
            console.log('[gestion-items-pedido]  PEDIDO DATA RECOLECTADA:', {
                prendas_total: pedidoData.prendas?.length || 0,
                epps_total: pedidoData.epps?.length || 0,
                primer_prenda_telas: pedidoData.prendas?.[0]?.telas?.length || 0,
                primer_prenda_contenido: pedidoData.prendas?.[0]
            });

            // Permitir prendas O epps (al menos uno debe tener items)
            const tienePrendas = pedidoData.prendas && pedidoData.prendas.length > 0;
            const tieneEpps = pedidoData.epps && pedidoData.epps.length > 0;
            const tieneItemsLegacy = pedidoData.items && pedidoData.items.length > 0;
            
            if (!tienePrendas && !tieneEpps && !tieneItemsLegacy) {
                this.notificationService.error('Debe agregar al menos una prenda o un EPP');
                return;
            }

            // Mostrar indicador de carga
            this.mostrarCargando('Validando pedido...');

            const validacion = await this.apiService.validarPedido(pedidoData);
            console.log('[gestion-items-pedido] 📋 Validación recibida:', validacion);
            
            // El backend retorna "success", no "valid"
            if (!validacion.success) {
                this.ocultarCargando();
                console.log('[gestion-items-pedido]  Validación falló:', validacion.errores);
                const errores = validacion.errores || [];
                if (Array.isArray(errores) && errores.length > 0) {
                    alert('Errores en el pedido:\n' + errores.join('\n'));
                } else {
                    alert('Error en validación: ' + (validacion.message || JSON.stringify(validacion)));
                }
                return;
            }
            
            console.log('[gestion-items-pedido] Validación exitosa, procediendo a crear pedido');

            this.mostrarCargando('Creando pedido...');
            const resultado = await this.apiService.crearPedido(pedidoData);
            console.log('[gestion-items-pedido] 📋 Resultado recibido:', resultado);
            console.log('[gestion-items-pedido] ¿resultado.success?', resultado.success);
            console.log('[gestion-items-pedido] typeof resultado.success:', typeof resultado.success);

            if (resultado.success) {
                console.log('[gestion-items-pedido]  ENTRANDO AL IF - Pedido creado exitosamente');
                this.datosPedidoCreado = {
                    pedido_id: resultado.pedido_id,
                    numero_pedido: resultado.numero_pedido
                };
                console.log('[gestion-items-pedido] 📌 datosPedidoCreado:', this.datosPedidoCreado);
                
                // Ocultar loader y mostrar modal de éxito existente
                this.ocultarCargando();
                console.log('[gestion-items-pedido] 🎬 Llamando mostrarModalExito()...');
                setTimeout(() => {
                    console.log('[gestion-items-pedido] 🎬 EN TIMEOUT - Ejecutando mostrarModalExito()');
                    this.mostrarModalExito();
                }, 300);
            } else {
                console.warn('[gestion-items-pedido]  resultado.success es FALSE o undefined');
            }
        } catch (error) {
            console.error('[gestion-items-pedido]  ERROR CAPTURADO:', error);
            console.error('[gestion-items-pedido]  Stack:', error.stack);
            console.error('[gestion-items-pedido]  Message:', error.message);
            
            this.ocultarCargando();
            if (this.notificationService) {
                const mensajeError = error.message || 'Error desconocido al crear el pedido';
                this.notificationService.error('Error: ' + mensajeError);
            }
        }
    }

    mostrarCargando(mensaje = 'Cargando...') {
        // Remover loader anterior si existe
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
        
        // Agregar animación CSS
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
        
        // Agregar animación CSS si no existe
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
        
        // Remover después de 2 segundos
        setTimeout(() => {
            exito.remove();
        }, 2000);
    }

    recolectarDatosPedido() {
        return this.formCollector.recolectarDatosPedido();
    }

    mostrarVistaPreviaFactura() {
        if (!this.renderer) {

            return;
        }
        this.renderer.mostrarVistaPreviaFactura();
    }

    mostrarModalExito() {
        console.log('[mostrarModalExito] 🎬 INICIANDO');
        console.log('[mostrarModalExito] ¿Existe MODAL_EXITO_PEDIDO_HTML?', typeof MODAL_EXITO_PEDIDO_HTML);
        console.log('[mostrarModalExito] ¿datosPedidoCreado?', this.datosPedidoCreado);
        
        // LIMPIAR asignaciones de colores tras crear el pedido
        console.log('[mostrarModalExito] 🧹 LIMPIANDO asignaciones de colores tras creación exitosa...');
        if (typeof limpiarAsignacionesColores === 'function') {
            limpiarAsignacionesColores();
            console.log('[mostrarModalExito] ✓ Asignaciones limpiadas');
        } else if (window.StateManager && typeof window.StateManager.limpiarAsignaciones === 'function') {
            window.StateManager.limpiarAsignaciones();
            console.log('[mostrarModalExito] ✓ Asignaciones limpiadas (StateManager)');
        }
        
        let modalElement = document.getElementById('modalExitoPedido');
        console.log('[mostrarModalExito] ¿modalElement existe?', !!modalElement);
        
        if (!modalElement) {
            console.log('[mostrarModalExito] 🔧 Creando modal desde HTML...');
            if (typeof MODAL_EXITO_PEDIDO_HTML === 'undefined') {
                console.error('[mostrarModalExito]  CRÍTICO: MODAL_EXITO_PEDIDO_HTML no está definido');
                throw new Error('MODAL_EXITO_PEDIDO_HTML no está disponible');
            }
            document.body.insertAdjacentHTML('beforeend', MODAL_EXITO_PEDIDO_HTML);
            modalElement = document.getElementById('modalExitoPedido');
            console.log('[mostrarModalExito]  Modal creado, elemento encontrado?', !!modalElement);
        }

        const btnVolverAPedidos = document.getElementById('btnVolverAPedidos');
        console.log('[mostrarModalExito] ¿btnVolverAPedidos encontrado?', !!btnVolverAPedidos);
        
        if (btnVolverAPedidos) {
            console.log('[mostrarModalExito] 🔗 Asignando onclick');
            btnVolverAPedidos.onclick = () => {
                console.log('[mostrarModalExito] 👉 Botón presionado, redirigiendo...');
                window.location.href = '/asesores/pedidos';
            };
        }

        console.log('[mostrarModalExito]  Mostrando modal');
        modalElement.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        console.log('[mostrarModalExito]  COMPLETADO');
    }

    renderizarProcesosDirectos() {
        this.renderer.renderizarProcesosDirectos();
    }
}

/**
 * Wrapper para editarProceso que funciona en contexto de edición
 */
window.editarProcesoEdicion = function(tipo) {

    
    // Asegurar que el modal de proceso esté encima
    const modalProceso = document.getElementById('modal-proceso-generico');
    if (modalProceso) {
        modalProceso.style.zIndex = '10000';

    }
    
    // Usar la función global si existe
    if (window.editarProceso && window.editarProceso !== window.editarProcesoEdicion) {
        window.editarProceso(tipo);
        return;
    }
    
    // Si no existe, intentar abrir el modal genérico
    if (window.abrirModalProcesoGenerico) {
        window.abrirModalProcesoGenerico(tipo);
        
        // Asegurar nuevamente que el z-index esté alto después de abrir
        if (modalProceso) {
            setTimeout(() => {
                modalProceso.style.zIndex = '10000';
            }, 100);
        }
        
        // Cargar datos en el modal
        const proceso = window.procesosSeleccionados?.[tipo];
        if (proceso?.datos) {
            cargarDatosProcesoEnModalEdicion(tipo, proceso.datos);
        }
    } else {

    }
};

/**
 * Cargar datos de un proceso en el modal para editar (fallback)
 */
function cargarDatosProcesoEnModalEdicion(tipo, datos) {

    
    // Limpiar imágenes anteriores
    if (window.imagenesProcesoActual) {
        window.imagenesProcesoActual = [null, null, null];
    }
    if (!window.imagenesProcesoExistentes) {
        window.imagenesProcesoExistentes = [];
    }
    window.imagenesProcesoExistentes = [];
    
    // Cargar imágenes
    const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
    imagenes.forEach((img, idx) => {
        if (img && idx < 3) {
            const indice = idx + 1;
            const preview = document.getElementById(`proceso-foto-preview-${indice}`);
            
            if (preview) {
                const imgUrl = img instanceof File ? URL.createObjectURL(img) : img;
                const htmlImg = IMAGEN_PROCESO_EDICION_TEMPLATE
                    .replace('{{imgUrl}}', imgUrl)
                    .replace('{{indice}}', indice);
                preview.innerHTML = htmlImg;
            }
            
            if (window.imagenesProcesoActual) {
                window.imagenesProcesoActual[idx] = img;
            }
        }
    });
    
    // Cargar ubicaciones
    if (datos.ubicaciones && window.ubicacionesProcesoSeleccionadas) {
        window.ubicacionesProcesoSeleccionadas.length = 0;
        window.ubicacionesProcesoSeleccionadas.push(...datos.ubicaciones);
        if (window.renderizarListaUbicaciones) {
            window.renderizarListaUbicaciones();
        }
    }
    
    // Cargar observaciones
    const obsInput = document.getElementById('proceso-observaciones');
    if (obsInput && datos.observaciones) {
        obsInput.value = datos.observaciones;
    }
    
    // Cargar tallas
    if (datos.tallas && window.tallasSeleccionadasProceso) {
        let damaTallas = datos.tallas.dama || {};
        let caballeroTallas = datos.tallas.caballero || {};
        let sobremedidaTallas = datos.tallas.sobremedida || {};
        
        // 🔥 FIX: Si DAMA o CABALLERO tienen SOBREMEDIDA anidada (número u objeto), EXTRAERLA
        const damaTallasLimpias = {};
        for (const [talla, valor] of Object.entries(damaTallas)) {
            if (talla === 'SOBREMEDIDA') {
                if (typeof valor === 'number') {
                    sobremedidaTallas['DAMA'] = valor;
                } else if (typeof valor === 'object' && valor !== null) {
                    // SOBREMEDIDA anidada: extraer a sobremedidaTallas
                    for (const [genero, cantidad] of Object.entries(valor)) {
                        sobremedidaTallas[genero] = cantidad;
                    }
                }
            } else {
                damaTallasLimpias[talla] = valor;
            }
        }
        damaTallas = damaTallasLimpias;
        
        const caballeroTallasLimpias = {};
        for (const [talla, valor] of Object.entries(caballeroTallas)) {
            if (talla === 'SOBREMEDIDA') {
                if (typeof valor === 'number') {
                    sobremedidaTallas['CABALLERO'] = valor;
                } else if (typeof valor === 'object' && valor !== null) {
                    // SOBREMEDIDA anidada: extraer a sobremedidaTallas
                    for (const [genero, cantidad] of Object.entries(valor)) {
                        sobremedidaTallas[genero] = cantidad;
                    }
                }
            } else {
                caballeroTallasLimpias[talla] = valor;
            }
        }
        caballeroTallas = caballeroTallasLimpias;
        
        window.tallasSeleccionadasProceso.dama = Object.keys(damaTallas);
        window.tallasSeleccionadasProceso.caballero = Object.keys(caballeroTallas);
        window.tallasSeleccionadasProceso.sobremedida = sobremedidaTallas;
        
        // Actualizar tallasCantidadesProceso también
        if (!window.tallasCantidadesProceso) {
            window.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };
        }
        window.tallasCantidadesProceso.dama = damaTallas;
        window.tallasCantidadesProceso.caballero = caballeroTallas;
        window.tallasCantidadesProceso.sobremedida = sobremedidaTallas;
        
        if (window.actualizarResumenTallasProceso) {
            window.actualizarResumenTallasProceso();
        }
    }
}

// Inicializar cuando el DOM esté listo O inmediatamente si el script se carga dinámicamente

if (document.readyState === 'loading') {
    // Si aún está cargando el DOM, esperar
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.gestionItemsUI) {
            // Inicializar con servicios disponibles
            const notificationService = typeof NotificationService !== 'undefined' ? new NotificationService() : null;
            
            window.gestionItemsUI = new GestionItemsUI({
                notificationService: notificationService
            });
        }
    });
} else {
    // Si el DOM ya está cargado (carga dinámica de script), inicializar inmediatamente
    if (!window.gestionItemsUI) {
        // Inicializar con servicios disponibles
        const notificationService = typeof NotificationService !== 'undefined' ? new NotificationService() : null;
        
        window.gestionItemsUI = new GestionItemsUI({
            notificationService: notificationService
        });
        
    }
}
 
