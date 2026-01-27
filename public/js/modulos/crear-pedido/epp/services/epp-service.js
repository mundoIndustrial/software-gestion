/**
 * EppService - Servicio principal que orquesta toda la lógica de EPP
 * Patrón: Facade + Orchestrator
 */

class EppService {
    constructor() {
        this.apiService = window.eppApiService;
        this.stateManager = window.eppStateManager;
        this.modalManager = null;
        this.itemManager = window.eppItemManager;
        this.imagenManager = null;
    }

    /**
     * Inicializar servicio
     */
    inicializar() {
        this.modalManager = new EppModalManager(this.stateManager);
        this.imagenManager = new EppImagenManager(this.apiService, this.stateManager, this.modalManager);

        // Exportar globalmente
        window.eppModalManager = this.modalManager;
        window.eppImagenManager = this.imagenManager;


    }

    /**
     * Abrir modal para agregar EPP
     */
    abrirModalAgregar() {
        // El nuevo modal de Blade es autónomo, no necesita el manager antiguo
        // Simplemente abrir el nuevo modal
        if (typeof abrirModalAgregarEPP === 'function') {
            abrirModalAgregarEPP(); // Función del template Blade
        } else {
            console.warn('⚠️ [EppService] abrirModalAgregarEPP() no definida');
        }
    }

    /**
     * Abrir modal para editar EPP
     * Seguro para datos opcionales (categoria, codigo)
     */
    abrirModalEditarEPP(eppData) {
        console.log('[EppService] 📝 Abriendo modal de edición con datos:', eppData);

        // Estandarizar datos: crear propiedad 'imagen' si no existe pero hay 'imagenes'
        if (!eppData.imagen && eppData.imagenes && Array.isArray(eppData.imagenes) && eppData.imagenes.length > 0) {
            eppData.imagen = eppData.imagenes[0];
            console.log('[EppService] 📸 Estandarizando: creada propiedad imagen desde imagenes[0]');
        }

        // Resetear estado y marcar como edición
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

        // Mostrar producto seleccionado (sin forzar categoría)
        this.modalManager.mostrarProductoSeleccionado({
            nombre: nombre,
            nombre_completo: nombre,
            codigo: eppData.codigo || undefined,
            categoria: eppData.categoria || undefined,
            imagen: eppData.imagen ? (eppData.imagen.ruta_webp || eppData.imagen.ruta_web || eppData.imagen.url || eppData.imagen) : undefined
        });

        // Cargar valores en el formulario
        this.modalManager.cargarValoresFormulario(
            null,
            eppData.cantidad || 1,
            eppData.observaciones || ''
        );

        // Mostrar y guardar imágenes si existen
        if (eppData.imagenes && Array.isArray(eppData.imagenes) && eppData.imagenes.length > 0) {
            console.log('[EppService] 📸 Guardando imágenes en estado:', eppData.imagenes);
            this.modalManager.mostrarImagenes(eppData.imagenes);
            
            // Cargar imágenes en el stateManager para que se puedan eliminar correctamente
            console.log('[EppService] 📸 Limpiando imágenes previas en stateManager');
            this.stateManager.limpiarImagenesSubidas();
            
            // Agregar cada imagen al estado
            eppData.imagenes.forEach((img, idx) => {
                const imagenParaEstado = {
                    id: img.id || `${eppData.epp_id}-img-${idx}`,
                    nombre: img.nombre || `imagen-${idx}`,
                    preview: img.url || img.ruta_web || img.preview || '',
                    url: img.url || img.ruta_web || img.preview || '',
                    ruta_web: img.ruta_web || img.url || img.preview || '',
                    archivo: null // No es un File, es una imagen existente
                };
                console.log('[EppService] 📸 Agregando imagen al estado:', imagenParaEstado);
                this.stateManager.agregarImagenSubida(imagenParaEstado);
            });
            
            // Guardar imágenes en el estado para que se incluyan al guardar (versión legacy)
            if (this.stateManager.cargarImagenesExistentes) {
                this.stateManager.cargarImagenesExistentes(eppData.imagenes);
            }
        } else {
            // Si no hay imágenes, limpiar estado pero MOSTRAR el contenedor para agregar nuevas
            console.log('[EppService] 📸 Sin imágenes existentes, limpiando estado');
            this.stateManager.limpiarImagenesSubidas();
            this.modalManager.mostrarImagenes([]); // Mostrar contenedor vacío para poder agregar
        }

        // Habilitar campos
        this.modalManager.habilitarCampos();

        // Abrir modal
        this.modalManager.abrirModal();

        console.log('[EppService] Modal de edición abierto');
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
        
        // Cerrar lista de búsqueda automáticamente
        const resultados = document.getElementById('resultadosBuscadorEPP');
        if (resultados) {
            resultados.style.display = 'none';
            console.log('[EppService] ✅ Lista de búsqueda cerrada');
        }
        const inputBuscador = document.getElementById('inputBuscadorEPP');
        if (inputBuscador) {
            inputBuscador.value = '';
            console.log('[EppService] ✅ Buscador limpiado');
        }
    }

    /**
     * Editar EPP desde formulario (no guardado en BD)
     * Parámetros: id, nombre, cantidad, observaciones, imagenes
     * Notas: codigo y categoria son opcionales (null-safe)
     */
    editarEPPFormulario(id, nombre, codigo = null, categoria = null, cantidad, observaciones = '', imagenes = []) {
        // Manejo defensivo de parámetros para compatibilidad
        // Si codigo es un número (cantidad), ajustar parámetros
        if (typeof codigo === 'number' && typeof categoria === 'number') {
            // Llamada antigua: editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes)
            // codigo es cantidad, categoria es observaciones
            cantidad = codigo;
            observaciones = categoria;
            imagenes = arguments[4] || [];
            codigo = null;
            categoria = null;
        } else if (typeof codigo === 'number') {
            // Parámetros desalineados: asumir que codigo es cantidad
            cantidad = codigo;
            observaciones = categoria || '';
            imagenes = cantidad || [];
            codigo = null;
            categoria = null;
        }

        // Asegurar que el modal existe en el DOM
        if (!document.getElementById('modal-agregar-epp')) {
            if (typeof window.EppModalTemplate !== 'undefined') {
                const modalHTML = window.EppModalTemplate.getHTML();
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
            const imagenes = this.stateManager.getImagenesSubidas();

            // Agregar EPP al pedido
            const resultado = await this.apiService.agregarEPPAlPedido(
                pedidoId,
                eppId,
                valores.cantidad,
                valores.observaciones,
                imagenes
            );

            if (window.eppNotificationService) {
                window.eppNotificationService.mostrarExitoModal(
                    '✅ EPP Agregado',
                    'EPP agregado al pedido correctamente'
                );
            }
            this.cerrarModal();
            this.stateManager.finalizarEdicion();

            // Recargar página
            setTimeout(() => location.reload(), 1500);
        } catch (error) {

            if (window.eppNotificationService) {
                window.eppNotificationService.mostrarErrorModal(
                    '❌ Error al Guardar',
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
                console.log('[EppService] 🔄 Actualizando pedido_epp en BD:', pedidoEppId);
                
                // Actualizar en BD
                this.apiService.actualizarPedidoEpp(pedidoEppId, {
                    cantidad: valores.cantidad,
                    observaciones: valores.observaciones
                }).then(resultado => {
                    console.log('[EppService] ✅ pedido_epp actualizado en BD:', resultado);
                    
                    // Actualizar la tarjeta en el DOM
                    this.itemManager.actualizarItem(eppId, {
                        cantidad: valores.cantidad,
                        observaciones: valores.observaciones,
                        imagenes: imagenes
                    });

                    // Actualizar en window.itemsPedido si existe
                    if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
                        const index = window.itemsPedido.findIndex(item => item.tipo === 'epp' && item.epp_id === eppId);
                        if (index !== -1) {
                            window.itemsPedido[index] = {
                                ...window.itemsPedido[index],
                                cantidad: valores.cantidad,
                                observaciones: valores.observaciones,
                                imagenes: imagenes
                            };
                        }
                    }

                    if (window.eppNotificationService) {
                        window.eppNotificationService.mostrarExito(
                            '✅ EPP Actualizado',
                            'Los cambios fueron guardados correctamente'
                        );
                    }

                    this.cerrarModal();
                    this.stateManager.finalizarEdicion();
                }).catch(error => {
                    console.error('[EppService] ❌ Error al actualizar pedido_epp:', error);
                    if (window.eppNotificationService) {
                        window.eppNotificationService.mostrarError(
                            '❌ Error',
                            'No se pudo guardar los cambios'
                        );
                    }
                });

                return;
            }

            // Si NO estamos editando, crear nuevo item
            console.log('[EppService] ➕ Creando nuevo EPP');
            
            this.itemManager.crearItem(
                producto.id,
                producto.nombre_completo || producto.nombre,
                producto.categoria,
                valores.cantidad,
                valores.observaciones,
                imagenes
            );

            // Crear objeto EPP (solo campos necesarios)
            const eppData = {
                tipo: 'epp',
                epp_id: producto.id,
                nombre_epp: producto.nombre_completo || producto.nombre || '',
                categoria: producto.categoria || '',
                cantidad: valores.cantidad,
                observaciones: valores.observaciones,
                imagenes: imagenes
            };
            
            console.log('[EppService]  Objeto EPP a guardar:', eppData);

            // Solo agregar a GestionItemsUI o itemsPedido si es NUEVO (no editando)
            if (!eppId) {
                console.log('[EppService] 📝 Agregando EPP nuevo a estado');
                
                // Agregar a GestionItemsUI si está disponible (mantiene sincronización)
                if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarEPPDesdeModal === 'function') {
                    window.gestionItemsUI.agregarEPPDesdeModal(eppData);
                } else {
                    // Fallback: agregar a window.itemsPedido si GestionItemsUI no está disponible
                    if (!window.itemsPedido) {
                        window.itemsPedido = [];
                    }
                    window.itemsPedido.push(eppData);
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
            '¿Eliminar este EPP?',
            'Esta acción no se puede deshacer.',
            () => {
                this.itemManager.eliminarItem(eppId);

                // Eliminar de window.itemsPedido
                if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
                    const index = window.itemsPedido.findIndex(item => item.tipo === 'epp' && item.epp_id === eppId);
                    if (index !== -1) {
                        window.itemsPedido.splice(index, 1);
                    }
                }
            }
        );
    }

    /**
     * Mostrar modal de confirmación
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
     * Actualizar estado del botón
     */
    actualizarBoton() {
        this.modalManager.actualizarBoton();
    }

    /**
     * Filtrar EPP por término de búsqueda
     */
    async filtrarEPP(valor) {
        console.log('🔎 [EppService] filtrarEPP iniciado con valor:', valor);
        const container = document.getElementById('resultadosBuscadorEPP');
        console.log('🔎 [EppService] Contenedor encontrado:', !!container);
        
        if (!container) {
            console.warn(' [EppService] No se encontró el contenedor resultadosBuscadorEPP');
            return;
        }

        if (!valor || valor.trim() === '') {
            console.log('🔎 [EppService] Valor vacío, ocultando resultados');
            container.style.display = 'none';
            return;
        }

        try {
            console.log('🔎 [EppService] Llamando a _buscarEPPDesdeDB');
            // Buscar EPP desde la base de datos
            const epps = await this._buscarEPPDesdeDB(valor);
            console.log('🔎 [EppService] EPPs retornados:', epps.length);

            if (epps.length === 0) {
                container.innerHTML = `<div style="padding: 1rem; text-align: center; color: #6b7280;">No se encontraron resultados para "${valor}"</div>`;
            } else {
                console.log('🔍 [EppService] Renderizando resultados:', epps.length);
                const html = epps.map(epp => `
                    <div onclick="if(window.mostrarProductoEPP) { window.mostrarProductoEPP({id: ${epp.id}, nombre_completo: '${epp.nombre_completo || epp.nombre}', nombre: '${epp.nombre}', imagen: '${epp.imagen || ''}', tallas: ${JSON.stringify(epp.tallas || [])}}); } document.getElementById('resultadosBuscadorEPP').style.display = 'none'; document.getElementById('inputBuscadorEPP').value = '';" 
                         style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #e5e7eb; transition: background 0.2s ease;"
                         onmouseover="this.style.background = '#f3f4f6';"
                         onmouseout="this.style.background = 'white';">
                        <div style="font-weight: 500; color: #1f2937;">${epp.nombre_completo || epp.nombre}</div>
                    </div>
                `).join('');
                container.innerHTML = html;
                console.log('✅ [EppService] HTML renderizado en contenedor');
            }

            container.style.display = 'block';
            console.log('✅ [EppService] Contenedor visible:', container.style.display);
        } catch (error) {

            container.innerHTML = `<div style="padding: 1rem; text-align: center; color: #dc2626;">Error al buscar EPP</div>`;
            container.style.display = 'block';
        }
    }

    /**
     * Búsqueda de EPP desde la base de datos
     */
    async _buscarEPPDesdeDB(valor) {
        console.log('🔍 [EppService] _buscarEPPDesdeDB iniciado con término:', valor);
        try {
            const url = `/api/epp?q=${encodeURIComponent(valor)}`;
            console.log('🔍 [EppService] Realizando fetch a:', url);
            
            const response = await fetch(url);
            console.log('🔍 [EppService] Response status:', response.status, response.statusText);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error(' [EppService] Error HTTP:', response.status, errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('✅ [EppService] Resultado JSON recibido:', result);
            console.log('✅ [EppService] Total EPPs encontrados:', result.data?.length || 0);
            
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
        if (window.eppNotificationService) {
            window.eppNotificationService.mostrarValidacion(titulo, mensaje);
        } else {
            alert(titulo + '\n\n' + mensaje);
        }
    }

    /**
     * Mostrar error
     */
    mostrarError(titulo, mensaje) {
        if (window.eppNotificationService) {
            window.eppNotificationService.mostrarErrorModal(titulo, mensaje);
        } else {
            alert('ERROR: ' + titulo + '\n\n' + mensaje);
        }
    }

    /**
     * Mostrar éxito
     */
    mostrarExito(titulo, mensaje) {
        if (window.eppNotificationService) {
            window.eppNotificationService.mostrarExitoModal(titulo, mensaje);
        } else {
            alert('✓ ' + titulo + '\n\n' + mensaje);
        }
    }
}

// Exportar instancia global
window.eppService = new EppService();
