/**
 * EppService - Servicio principal que orquesta toda la lógica de EPP
 * Patrón: Facade + Orchestrator
 */

class EppService {
    constructor() {
        this.apiService = globalThis.eppApiService;
        this.stateManager = globalThis.eppStateManager;
        this.modalManager = null;
        // Detectar cual item manager esta disponible (tabla o tarjeta)
        this.itemManager = globalThis.eppItemManagerTabla || globalThis.eppItemManagerTarjeta || globalThis.eppItemManager;
        this.imagenManager = null;
        
        // Debouncing y cache para busqueda
        this.debounceTimerBusqueda = null;
        this.cacheBusqueda = {};
        this.ultimaConsultaBusqueda = '';
    }

    /**
     * Inicializar servicio
     */
    inicializar() {
        this.modalManager = new EppModalManager(this.stateManager);
        this.imagenManager = new EppImagenManager(this.apiService, this.stateManager, this.modalManager);

        // Exportar globalmente
        globalThis.eppModalManager = this.modalManager;
        globalThis.eppImagenManager = this.imagenManager;


    }

    /**
     * Abrir modal para agregar EPP
     */
    abrirModalAgregar() {
        // El nuevo modal de Blade es autonomo, no necesita el manager antiguo
        // Simplemente abrir el nuevo modal
        if (typeof abrirModalAgregarEPP === 'function') {
            abrirModalAgregarEPP(); // Funcion del template Blade
        } else {
            console.warn(' [EppService] abrirModalAgregarEPP() no definida');
        }
    }

    /**
     * Abrir modal para editar EPP
     * Seguro para datos opcionales (categoria, codigo)
     */
    abrirModalEditarEPP(eppData) {
        console.log('[EppService] Abriendo modal de edicion con datos:', eppData);

        // Resetear estado y marcar como edicion
        this.stateManager.iniciarEdicion(eppData.epp_id || eppData.id, true, eppData.pedido_epp_id || eppData.id);
        
        // Obtener nombre (nombre_completo o nombre)
        const nombre = eppData.nombre_completo || eppData.nombre || '';
        
        // Cargar datos del EPP (tolerante a valores null/undefined)
        this.stateManager.setProductoSeleccionado({
            id: eppData.epp_id || eppData.id,
            nombre: nombre,
            nombre_completo: nombre,
            codigo: eppData.codigo || null,
            categoria: eppData.categoria || null
        });

        // Mostrar producto seleccionado (sin forzar categoria)
        this.modalManager.mostrarProductoSeleccionado({
            nombre: nombre,
            nombre_completo: nombre,
            codigo: eppData.codigo || undefined,
            categoria: eppData.categoria || undefined
        });

        // Cargar valores en el formulario
        this.modalManager.cargarValoresFormulario(
            null,
            eppData.cantidad || 1,
            eppData.observaciones || ''
        );

        // Limpiar imagenes previas
        console.log('[EppService]  Limpiando imagenes previas en stateManager');
        this.stateManager.limpiarImagenesSubidas();
        this.modalManager.mostrarImagenes([]); // Mostrar contenedor vacio para poder agregar nuevas

        // Habilitar campos
        this.modalManager.habilitarCampos();

        // Abrir modal
        this.modalManager.abrirModal();

        console.log('[EppService] Modal de edicion abierto');
    }

    /**
     * Seleccionar producto EPP
     */
    seleccionarProducto(producto) {
        console.log(' [EppService] seleccionarProducto llamado:', producto);
        this.stateManager.setProductoSeleccionado(producto);
        console.log(' [EppService] Producto guardado en state');
        
        this.modalManager.mostrarProductoSeleccionado(producto);
        console.log(' [EppService] Mostrado en modal');
        
        this.modalManager.habilitarCampos();
        console.log(' [EppService] Campos habilitados');
        
        // Cerrar lista de busqueda automaticamente
        const resultados = document.getElementById('resultadosBuscadorEPP');
        if (resultados) {
            resultados.style.display = 'none';
            console.log('[EppService]  Lista de busqueda cerrada');
        }
        const inputBuscador = document.getElementById('inputBuscadorEPP');
        if (inputBuscador) {
            inputBuscador.value = '';
            console.log('[EppService]  Buscador limpiado');
        }
    }

    /**
     * Editar EPP desde formulario (no guardado en BD)
     * Parametros: id, nombre, cantidad, observaciones, imagenes
     * Notas: codigo y categoria son opcionales (null-safe)
     */
    editarEPPFormulario(id, nombre, codigo = null, categoria = null, cantidad, observaciones = '', imagenes = []) {
        // Manejo defensivo de parametros para compatibilidad
        // Si codigo es un numero (cantidad), ajustar parametros
        if (typeof codigo === 'number' && typeof categoria === 'number') {
            // Llamada antigua: editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes)
            // codigo es cantidad, categoria es observaciones
            cantidad = codigo;
            observaciones = categoria;
            imagenes = arguments[4] || [];
            codigo = null;
            categoria = null;
        } else if (typeof codigo === 'number') {
            // Parametros desalineados: asumir que codigo es cantidad
            cantidad = codigo;
            observaciones = categoria || '';
            imagenes = cantidad || [];
            codigo = null;
            categoria = null;
        }

        // Asegurar que el modal existe en el DOM
        if (!document.getElementById('modal-agregar-epp')) {
            if (typeof globalThis.EppModalTemplate !== 'undefined') {
                const modalHTML = globalThis.EppModalTemplate.getHTML();
                document.body.insertAdjacentHTML('beforeend', modalHTML);
            }
        }

        this.stateManager.iniciarEdicion(id, false);
        this.stateManager.setProductoSeleccionado({ 
            id, 
            nombre, 
            nombre_completo: nombre,
            codigo: codigo || null, 
            categoria: categoria || null 
        });
        this.stateManager.guardarDatosItem(id, { 
            id, 
            nombre, 
            nombre_completo: nombre,
            codigo: codigo || null, 
            categoria: categoria || null, 
            cantidad, 
            observaciones, 
            imagenes: imagenes || [] 
        });

        // Mostrar solo nombre si codigo/categoria no existen
        this.modalManager.mostrarProductoSeleccionado({ 
            nombre,
            nombre_completo: nombre,
            codigo: codigo || undefined,
            categoria: categoria || undefined,
            imagen: imagenes && imagenes.length > 0 ? imagenes[0].ruta_webp || imagenes[0].ruta_original || imagenes[0] : undefined
        });
        this.modalManager.cargarValoresFormulario(null, cantidad, observaciones);
        this.modalManager.mostrarImagenes(imagenes || []);
        this.modalManager.habilitarCampos();
        this.modalManager.abrirModal();
    }

    /**
     * Editar EPP desde BD
     */
    async editarEPPDesdeDB(eppId) {
        try {


            const epp = await this.apiService.obtenerEPP(eppId);

            this.stateManager.iniciarEdicion(eppId, true);
            this.stateManager.setProductoSeleccionado({
                id: epp.id,
                nombre: epp.nombre_completo || epp.nombre
            });

            this.modalManager.mostrarProductoSeleccionado({
                nombre: epp.nombre_completo || epp.nombre,
                nombre_completo: epp.nombre_completo || epp.nombre,
                imagen: epp.imagenes && epp.imagenes.length > 0 ? epp.imagenes[0].ruta_webp || epp.imagenes[0].ruta_original || epp.imagenes[0] : undefined
            });
            this.modalManager.cargarValoresFormulario(null, epp.cantidad, epp.observaciones);
            this.modalManager.mostrarImagenes(epp.imagenes || []);
            this.modalManager.habilitarCampos();
            this.modalManager.abrirModal();


        } catch (error) {

            alert('Error al obtener EPP: ' + error.message);
        }
    }

    /**
     * Guardar EPP (agregar o actualizar)
     */
    async guardarEPP() {
        if (!this.modalManager.validarFormulario()) {
            return;
        }

        const producto = this.stateManager.getProductoSeleccionado();
        const valores = this.modalManager.obtenerValoresFormulario();
        const imagenes = this.stateManager.getImagenesSubidas();

        if (this.stateManager.isEditandoDesdeDB()) {
            await this._guardarEPPDesdeDB(valores);
        } else {
            this._guardarEPPFormulario(producto, valores, imagenes);
        }
    }

    /**
     * Guardar EPP desde BD
     */
    async _guardarEPPDesdeDB(valores) {
        try {
            const eppId = this.stateManager.getEppIdSeleccionado();
            const pedidoId = this.stateManager.getPedidoId();
            const pedidoEppId = this.stateManager.getPedidoEppId();
            const imagenes = this.stateManager.getImagenesSubidas();

            console.log('[EppService] Guardando EPP desde BD:', {
                eppId,
                pedidoId,
                pedidoEppId,
                cantidad: valores.cantidad,
                observaciones: valores.observaciones,
                esEdicion: !!pedidoEppId
            });

            let resultado;

            // Si tiene pedidoEppId, es una edicion (UPDATE)
            if (pedidoEppId) {
                console.log('[EppService]  MODO EDICION: Actualizando EPP en el pedido...');
                resultado = await this.apiService.actualizarEPPDelPedido(
                    pedidoId,
                    pedidoEppId,
                    valores.cantidad,
                    valores.observaciones
                );
            } else {
                // Si no tiene pedidoEppId, es agregar (CREATE)
                console.log('[EppService]  MODO CREAR: Agregando nuevo EPP al pedido...');
                resultado = await this.apiService.agregarEPPAlPedido(
                    pedidoId,
                    eppId,
                    valores.cantidad,
                    valores.observaciones,
                    imagenes
                );
            }

            if (globalThis.eppNotificationService) {
                const mensaje = pedidoEppId ? 'EPP actualizado correctamente' : 'EPP agregado al pedido correctamente';
                globalThis.eppNotificationService.mostrarExitoModal(
                    ' ' + (pedidoEppId ? 'Actualizado' : 'Agregado'),
                    mensaje
                );
            }
            this.cerrarModal();
            this.stateManager.finalizarEdicion();

            // Recargar pagina
            setTimeout(() => location.reload(), 1500);
        } catch (error) {
            console.error('[EppService]  Error al guardar EPP:', error);

            if (globalThis.eppNotificationService) {
                globalThis.eppNotificationService.mostrarErrorModal(
                    ' Error al Guardar',
                    error.message
                );
            } else {
                alert('Error al guardar: ' + error.message);
            }
        }
    }

    /**
     * Guardar EPP en formulario
     */
    _guardarEPPFormulario(producto, valores, imagenes) {
        try {
            const eppId = this.stateManager.getEditandoId();
            const pedidoEppId = this.stateManager.getPedidoEppId();

            // Si estamos editando, actualizar en BD
            if (eppId && pedidoEppId) {
                console.log('[EppService]  Actualizando pedido_epp en BD:', pedidoEppId);
                
                // Actualizar en BD
                this.apiService.actualizarPedidoEpp(pedidoEppId, {
                    cantidad: valores.cantidad,
                    observaciones: valores.observaciones
                }).then(resultado => {
                    console.log('[EppService]  pedido_epp actualizado en BD:', resultado);
                    
                    // Actualizar la tarjeta en el DOM
                    this.itemManager.actualizarItem(eppId, {
                        cantidad: valores.cantidad,
                        observaciones: valores.observaciones,
                        imagenes: imagenes,
                        valor_unitario: valores.valor_unitario,
                        total: valores.total
                    });

                    if (globalThis.eppNotificationService) {
                        globalThis.eppNotificationService.mostrarExito(
                            ' EPP Actualizado',
                            'Los cambios fueron guardados correctamente'
                        );
                    }

                    this.cerrarModal();
                    this.stateManager.finalizarEdicion();
                }).catch(error => {
                    console.error('[EppService]  Error al actualizar pedido_epp:', error);
                    if (globalThis.eppNotificationService) {
                        globalThis.eppNotificationService.mostrarError(
                            ' Error',
                            'No se pudo guardar los cambios'
                        );
                    }
                });

                return;
            }

            // Si NO estamos editando, crear nuevo item
            console.log('[EppService]  Creando nuevo EPP');
            
            this.itemManager.crearItem(
                producto.id,
                producto.nombre_completo || producto.nombre,
                producto.categoria,
                valores.cantidad,
                valores.observaciones,
                imagenes,
                null,
                valores.valor_unitario,
                valores.total
            );

            // Crear objeto EPP (solo campos necesarios)
            const eppData = {
                tipo: 'epp',
                epp_id: producto.id,
                nombre_epp: producto.nombre_completo || producto.nombre || '',
                categoria: producto.categoria || '',
                cantidad: valores.cantidad,
                observaciones: valores.observaciones,
                imagenes: imagenes,
                valor_unitario: valores.valor_unitario,
                total: valores.total
            };
            
            console.log('[EppService]  Objeto EPP a guardar:', eppData);

            // Solo agregar a GestionItemsUI si es NUEVO (no editando)
            if (!eppId) {
                console.log('[EppService] Agregando EPP nuevo a estado');
                console.log('[EppService] Â¿globalThis.gestionItemsUI existe?', !!globalThis.gestionItemsUI);
                console.log('[EppService] Â¿agregarEPPDesdeModal existe?', globalThis.gestionItemsUI && typeof globalThis.gestionItemsUI.agregarEPPDesdeModal === 'function');
                
                // Agregar a GestionItemsUI si esta disponible (mantiene sincronizacion)
                if (globalThis.gestionItemsUI && typeof globalThis.gestionItemsUI.agregarEPPDesdeModal === 'function') {
                    console.log('[EppService]  USANDO GESTION ITEMS UI');
                    globalThis.gestionItemsUI.agregarEPPDesdeModal(eppData);
                } else if (globalThis.gestionItemsUI && typeof globalThis.gestionItemsUI.agregarEPPAlOrden === 'function') {
                    console.log('[EppService]  USANDO gestionItemsUI.agregarEPPAlOrden');
                    globalThis.gestionItemsUI.agregarEPPAlOrden(eppData);
                } else {
                    console.error('[EppService] gestionItemsUI no disponible para agregar EPP');
                }
            }

            this.cerrarModal();
            this.stateManager.finalizarEdicion();
        } catch (error) {

            alert('Error: ' + error.message);
        }
    }

    /**
     * Eliminar EPP
     */
    eliminarEPP(eppId) {
        this._mostrarModalConfirmacion(
            'Â¿Eliminar este EPP?',
            'Esta accion no se puede deshacer.',
            () => {
                this.itemManager.eliminarItem(eppId);
            }
        );
    }

    /**
     * Mostrar modal de confirmacion
     */
    _mostrarModalConfirmacion(titulo, mensaje, onConfirmar) {
        const modalHTML = `
            <div id="modalConfirmacion" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
                <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 400px; text-align: center;">
                    <h2 style="margin: 0 0 1rem 0; color: #1f2937; font-size: 1.25rem;">${titulo}</h2>
                    <p style="margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;">${mensaje}</p>
                    <div style="display: flex; gap: 0.75rem; justify-content: center;">
                        <button onclick="document.getElementById('modalConfirmacion').remove();" style="padding: 0.75rem 1.5rem; background: #e5e7eb; color: #1f2937; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: background 0.2s;">
                            Cancelar
                        </button>
                        <button id="btnConfirmarEliminar" style="padding: 0.75rem 1.5rem; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: background 0.2s;">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Inyectar modal
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Asignar evento
        document.getElementById('btnConfirmarEliminar').addEventListener('click', () => {
            document.getElementById('modalConfirmacion').remove();
            onConfirmar();
        });
    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        this.modalManager.cerrarModal();
    }

    /**
     * Actualizar estado del boton
     */
    actualizarBoton() {
        this.modalManager.actualizarBoton();
    }

    /**
     * Filtrar EPP por termino de busqueda - OPTIMIZADO (debounce + validacion minima)
     */
    async filtrarEPP(valor) {
        console.log(' [EppService] filtrarEPP iniciado con valor:', valor);
        const container = document.getElementById('resultadosBuscadorEPP');
        const inputBuscador = document.getElementById('inputBuscadorEPP');
        
        if (!container) {
            console.warn(' [EppService] No se encontro el contenedor resultadosBuscadorEPP');
            return;
        }

        const valorLimpio = (valor || '').trim().toLowerCase();

        if (!valorLimpio) {
            console.log(' [EppService] Valor vacio, ocultando resultados');
            container.style.display = 'none';
            return;
        }

        //  VALIDACION: Requiere minimo 2 caracteres
        if (valorLimpio.length < 2) {
            container.innerHTML = `<div style="padding: 0.75rem 1rem; text-align: center; color: #9ca3af; font-size: 0.85rem;">Escribe al menos 2 caracteres para buscar</div>`;
            container.style.display = 'block';
            
            // Limpiar debounce anterior
            if (this.debounceTimerBusqueda) {
                clearTimeout(this.debounceTimerBusqueda);
            }
            return;
        }

        // Limpiar debounce anterior
        if (this.debounceTimerBusqueda) {
            clearTimeout(this.debounceTimerBusqueda);
        }

        // Mostrar indicador de carga
        container.innerHTML = `
            <div style="padding: 1rem; text-align: center; color: #6b7280;">
                <div style="display: inline-block;">
                    <div style="border: 2px solid #e5e7eb; border-top: 2px solid #3b82f6; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite;"></div>
                </div>
                <p style="margin-top: 0.5rem; font-size: 0.85rem;">Buscando...</p>
            </div>
            <style>
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            </style>
        `;
        container.style.display = 'block';

        //  DEBOUNCE AUMENTADO: esperar 400ms despues de inactividad
        this.debounceTimerBusqueda = setTimeout(async () => {
            const terminoBusqueda = (inputBuscador?.value || '').toLowerCase().trim();
            
            if (!terminoBusqueda || terminoBusqueda.length < 2) {
                container.style.display = 'none';
                return;
            }

            try {
                console.log(' [EppService] Ejecutando búsqueda con debounce para:', terminoBusqueda);
                
                // Verificar si está en caché
                if (this.cacheBusqueda[terminoBusqueda]) {
                    console.log(' [EppService] Resultado obtenido del caché');
                    const epps = this.cacheBusqueda[terminoBusqueda];
                    this._renderizarResultadosBusqueda(epps, terminoBusqueda, container);
                    return;
                }

                // Buscar desde BD
                const epps = await this._buscarEPPDesdeDB(terminoBusqueda);
                
                // Guardar en cache
                this.cacheBusqueda[terminoBusqueda] = epps;
                
                console.log(' [EppService] EPPs retornados:', epps.length);
                this._renderizarResultadosBusqueda(epps, terminoBusqueda, container);

            } catch (error) {
                console.error(' [EppService] Error en filtrarEPP:', error);
                container.innerHTML = `<div style="padding: 1rem; text-align: center; color: #dc2626; font-size: 0.9rem;"> Error al buscar EPP</div>`;
                container.style.display = 'block';
            }
        }, 400); //  Esperar 400ms (fue 300ms) para reducir peticiones
    }

    /**
     * Renderizar resultados de busqueda con mejor UI
     */
    _renderizarResultadosBusqueda(epps, termino, container) {
        if (epps.length === 0) {
            container.innerHTML = `
                <div style="padding: 1rem; text-align: center; color: #6b7280; font-size: 0.9rem;">
                    <p style="margin: 0;"> No se encontraron resultados para "<strong>${termino}</strong>"</p>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #9ca3af;">Intenta con otras palabras</p>
                </div>
            `;
        } else {
            console.log(' [EppService] Renderizando resultados:', epps.length);
            
            // Crear HTML para cada resultado con mas informacion
            const html = epps.map(epp => {
                const nombre = epp.nombre_completo || epp.nombre;
                const codigo = epp.codigo ? `<span style="color: #6b7280; font-size: 0.8rem; margin-top: 0.25rem; display: block;">Código: ${epp.codigo}</span>` : '';
                const categoria = epp.categoria ? `<span style="color: #9ca3af; font-size: 0.75rem; margin-top: 0.15rem; display: block;"> ${epp.categoria}</span>` : '';
                const imagen = epp.imagen ? `<img src="${epp.imagen}" alt="${nombre}" style="width: 35px; height: 35px; object-fit: cover; border-radius: 4px; margin-right: 0.75rem;">` : '';
                
                return `
                    <div onclick="if(globalThis.mostrarProductoEPP) { globalThis.mostrarProductoEPP({id: ${epp.id}, nombre_completo: '${nombre.replace(/'/g, "\\'")}', nombre: '${epp.nombre.replace(/'/g, "\\'")}', imagen: '${epp.imagen || ''}', tallas: ${JSON.stringify(epp.tallas || [])}}); } document.getElementById('resultadosBuscadorEPP').style.display = 'none'; document.getElementById('inputBuscadorEPP').value = '';" 
                         style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #e5e7eb; display: flex; align-items: flex-start; transition: background 0.2s ease;"
                         onmouseover="this.style.background = '#f9fafb';"
                         onmouseout="this.style.background = 'white';">
                        <div style="display: flex; align-items: center; width: 100%;">
                            ${imagen}
                            <div style="flex: 1;">
                                <div style="font-weight: 500; color: #1f2937; font-size: 0.95rem;">${nombre}</div>
                                ${codigo}
                                ${categoria}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            // Mostrar contador de resultados
            const contador = epps.length > 1 ? `<div style="padding: 0.5rem 1rem; background: #f3f4f6; font-size: 0.8rem; color: #6b7280; border-bottom: 1px solid #e5e7eb;">
                âœ“ Se encontraron ${epps.length} resultados
            </div>` : '';
            
            container.innerHTML = contador + html;
            console.log(' [EppService] HTML renderizado en contenedor');
        }
        
        container.style.display = 'block';
        console.log(' [EppService] Contenedor visible:', container.style.display);
    }

    /**
     * Búsqueda de EPP desde la base de datos
     */
    async _buscarEPPDesdeDB(valor) {
        console.log(' [EppService] _buscarEPPDesdeDB iniciado con término:', valor);
        try {
            const url = `/api/epp?q=${encodeURIComponent(valor)}`;
            console.log(' [EppService] Realizando fetch a:', url);
            
            const response = await fetch(url);
            console.log(' [EppService] Response status:', response.status, response.statusText);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error(' [EppService] Error HTTP:', response.status, errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log(' [EppService] Resultado JSON recibido:', result);
            console.log(' [EppService] Total EPPs encontrados:', result.data?.length || 0);
            
            return result.data && Array.isArray(result.data) ? result.data : [];
        } catch (error) {
            console.error(' [EppService] Error en _buscarEPPDesdeDB:', error.message);
            console.error(' [EppService] Stack:', error.stack);
            return [];
        }
    }

    /**
     * Cargar EPP disponibles
     */
    cargarEPP() {

        // Implementar carga de EPPs desde API
    }

    /**
     * Cargar categorías
     */
    cargarCategorias() {

        // Implementar carga de categorías desde API
    }

    /**
     * Limpiar modal
     */
    limpiarModal() {
        this.stateManager.resetear();
        this.modalManager.limpiarFormulario();

    }

    /**
     * Agregar EPP al pedido
     */
    agregarEPPAlPedido() {
        this.guardarEPP();
    }

    /**
     * Obtener estado actual (para debugging)
     */
    obtenerEstado() {
        return {
            state: this.stateManager.getEstado(),
            itemsCount: this.itemManager.contarItems()
        };
    }

    /**
     * Mostrar validación
     */
    mostrarValidacion(titulo, mensaje) {
        if (globalThis.eppNotificationService) {
            globalThis.eppNotificationService.mostrarValidacion(titulo, mensaje);
        } else {
            alert(titulo + '\n\n' + mensaje);
        }
    }

    /**
     * Mostrar error
     */
    mostrarError(titulo, mensaje) {
        if (globalThis.eppNotificationService) {
            globalThis.eppNotificationService.mostrarErrorModal(titulo, mensaje);
        } else {
            alert('ERROR: ' + titulo + '\n\n' + mensaje);
        }
    }

    /**
     * Mostrar éxito
     */
    mostrarExito(titulo, mensaje) {
        if (globalThis.eppNotificationService) {
            globalThis.eppNotificationService.mostrarExitoModal(titulo, mensaje);
        } else {
            alert('âœ“ ' + titulo + '\n\n' + mensaje);
        }
    }
}

// Exportar instancia global
globalThis.eppService = new EppService();



