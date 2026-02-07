/**
 * Gestión de Ítems (REFACTORIZADO - DDD)
 * 
 * ✅ Capa de Presentación pura
 * ✅ Sin lógica de negocio
 * ✅ Backend maneja orden, validaciones, persistencia
 * 
 * Responsabilidades:
 * - Recolectar datos del formulario
 * - Llamar APIs RESTful
 * - Renderizar UI
 * - Mostrar notificaciones
 */

class GestionItemsUIRefactorizado {
    constructor(options = {}) {
        // SIMPLIFICADO: Solo almacenamos items como vienen del backend
        this.items = [];
        
        // Controlar modo edición
        this.prendaEditIndex = null; // IMPORTANTE: Inicializar explícitamente a null
        this.prendaEnEdicion = null;
        
        // Editor de prenda para modal
        this.prendaEditor = new (window.PrendaModalEditor || class{})(
            options.notificationService
        );
        
        // Servicios
        this.notificationService = options.notificationService || (
            typeof NotificationService !== 'undefined' ? new NotificationService() : null
        );
        this.apiService = options.apiService || (
            typeof ItemAPIService !== 'undefined' ? new ItemAPIService() : null
        );
        this.renderer = options.renderer || (
            typeof ItemRenderer !== 'undefined' ? new ItemRenderer() : null
        );

        if (this.apiService && this.notificationService) {
            this.inicializar();
        }
    }

    inicializar() {
        this.attachEventListeners();
        this.cargarItems();
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

    /**
     * ✅ SIMPLIFICADO: Solo cargar del backend
     */
    async cargarItems() {
        try {
            if (!this.apiService || !this.renderer) return;

            // Backend retorna items ya ordenados
            const resultado = await this.apiService.obtenerItems();
            this.items = resultado.items || [];
            
            await this.renderer.actualizar(this.items);
        } catch (error) {
            console.error('[GestionItemsUI] Error al cargar items:', error);
            this.notificationService?.error('Error al cargar ítems');
        }
    }

    /**
     * Agregar item
     * Modo CREATE: almacena localmente (sin pedidoId)
     * Modo EDIT: persiste en backend vía API (con pedidoId)
     */
    async agregarItem(itemData) {
        try {
            if (!this.apiService || !this.renderer) return false;

            // Detectar modo: ¿existe pedido_id?
            const pedidoId = window.datosEdicionPedido?.pedido_id;
            const modoEditar = !!pedidoId;

            if (modoEditar) {
                // ====== MODO EDITAR: Persiste vía API ======
                const resultado = await this.apiService.agregarItem(itemData);

                if (resultado.success) {
                    // Backend retorna items actualizados y ordenados
                    this.items = resultado.items || [];
                    await this.renderer.actualizar(this.items);
                    this.notificationService.exito('Ítem agregado correctamente');
                    return true;
                } else {
                    if (resultado.validation_errors) {
                        resultado.validation_errors.forEach(err => {
                            this.notificationService?.error(err.message || err);
                        });
                    } else {
                        this.notificationService?.error(resultado.message || 'Error al agregar item');
                    }
                    return false;
                }
            } else {
                // ====== MODO CREAR: Almacena localmente ======
                // Generar ID temporal para poder identificar el item
                const itemConId = {
                    ...itemData,
                    id: Math.random().toString(36).substr(2, 9),
                    _temporal: true // Marcar como temporal (no persistido)
                };

                this.items.push(itemConId);
                await this.renderer.actualizar(this.items);
                this.notificationService.exito('Ítem agregado (se guardará al crear el pedido)');
                return true;
            }
        } catch (error) {
            console.error('[GestionItemsUI] Error:', error);
            this.notificationService?.error('Error: ' + error.message);
            return false;
        }
    }

    /**
     * Eliminar item
     * Modo CREATE: elimina localmente
     * Modo EDIT: elimina vía API
     */
    async eliminarItem(index) {
        // Validar índice
        if (index < 0 || index >= this.items.length) {
            this.notificationService?.error('Item no encontrado');
            return;
        }

        const item = this.items[index];
        if (!item.id) {
            this.notificationService?.error('ID de item inválido');
            return;
        }

        // Confirmación
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
            if (!this.apiService || !this.renderer) return;

            // Detectar modo: ¿existe pedido_id?
            const pedidoId = window.datosEdicionPedido?.pedido_id;
            const modoEditar = !!pedidoId;

            if (modoEditar) {
                // ====== MODO EDITAR: Elimina vía API ======
                const resultado = await this.apiService.eliminarItem(item.id);

                if (resultado.success) {
                    this.items = resultado.items || [];
                    await this.renderer.actualizar(this.items);
                    this.notificationService.exito('Ítem eliminado');
                } else {
                    this.notificationService?.error(resultado.message || 'Error al eliminar');
                }
            } else {
                // ====== MODO CREAR: Elimina localmente ======
                this.items.splice(index, 1);
                await this.renderer.actualizar(this.items);
                this.notificationService.exito('Ítem eliminado');
            }
        } catch (error) {
            console.error('[GestionItemsUI] Error:', error);
            this.notificationService?.error('Error: ' + error.message);
        }
    }

    /**
     * Agregar nueva prenda (modal)
     */
    async agregarPrendaNueva() {
        try {
            // Recolectar datos del formulario
            const prendaData = this.recolectarPrendaDelFormulario();

            if (!prendaData || !prendaData.nombre_prenda) {
                this.notificationService?.error('Por favor completa los datos de la prenda');
                return;
            }

            // Obtener imágenes del storage
            let imagenes = [];
            if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.obtenerImagenes === 'function') {
                imagenes = window.imagenesPrendaStorage.obtenerImagenes() || [];
            }

            console.log('[agregarPrendaNueva] 🚀 Preparando item con datos:', {
                nombre: prendaData.nombre_prenda,
                tiene_telas: prendaData.telasAgregadas?.length > 0,
                tiene_procesos: Object.keys(prendaData.procesos || {}).length > 0,
                imagenes_count: imagenes.length,
                editando: this.prendaEditIndex !== null
            });

            // Construir item de prenda completo
            // IMPORTANTE: Los datos se ponen tanto en raíz que el PrendaDataTransformer
            // espera ciertos campos en la raíz, no dentro de datos_presentacion
            const itemData = {
                tipo: 'prenda',
                referencia_id: null, // No tiene ID de catálogo (es prenda nueva)
                nombre_prenda: prendaData.nombre_prenda,  // Para compatibilidad
                nombre: prendaData.nombre_prenda,
                descripcion: prendaData.descripcion,
                origen: prendaData.origen,
                
                // Telas (directas en raíz para que el transformador las encuentre)
                telasAgregadas: prendaData.telasAgregadas,
                
                // Tallas (con ambos formatos para compatibilidad)
                cantidad_talla: prendaData.cantidad_talla,
                tallas: prendaData.cantidad_talla,
                
                // Variaciones/Procesos (directos en raíz)
                variantes: prendaData.variantes,
                procesos: prendaData.procesos,
                
                // Imágenes (obtener del storage correctamente)
                imagenes: imagenes,
                fotos: imagenes,
                
                // También en datos_presentacion para compatibilidad
                datos_presentacion: {
                    origen: prendaData.origen,
                    tallas: prendaData.cantidad_talla,
                    telas: prendaData.telasAgregadas,
                    variantes: prendaData.variantes,
                    procesos: prendaData.procesos
                },
                
                // Adjuntar la prenda completa para que el backend tenga acceso a todo
                _complete_prenda: {
                    nombre_prenda: prendaData.nombre_prenda,
                    descripcion: prendaData.descripcion,
                    origen: prendaData.origen,
                    cantidad_talla: prendaData.cantidad_talla,
                    telasAgregadas: prendaData.telasAgregadas,
                    variantes: prendaData.variantes,
                    procesos: prendaData.procesos
                }
            };

            // Detectar si estamos editando (en modo CREATE - sin pedido_id)
            if (this.prendaEditIndex !== null) {
                // ====== MODO EDICIÓN LOCAL ======
                // Reemplazar el item existente
                itemData.id = this.items[this.prendaEditIndex]?.id;
                this.items[this.prendaEditIndex] = itemData;
                await this.renderer.actualizar(this.items);
                this.notificationService.exito('Ítem actualizado correctamente');
                this.prendaEditIndex = null;
            } else {
                // ====== MODO NUEVA PRENDA ======
                const exito = await this.agregarItem(itemData);
                
                if (!exito) {
                    return;
                }
            }

            if (true) {
                this.cerrarModalAgregarPrendaNueva();
                // Limpiar formulario para próxima prenda
                this.limpiarFormularioPrenda();
            }
        } catch (error) {
            console.error('[agregarPrendaNueva]', error);
            this.notificationService?.error('Error: ' + error.message);
        }
    }
    
    limpiarFormularioPrenda() {
        // Limpiar campos básicos de entrada
        const inputNombre = document.getElementById('nueva-prenda-nombre');
        const inputDesc = document.getElementById('nueva-prenda-descripcion');
        const inputTela = document.getElementById('nueva-prenda-tela');
        const inputColor = document.getElementById('nueva-prenda-color');
        const inputRef = document.getElementById('nueva-prenda-referencia');
        
        if (inputNombre) inputNombre.value = '';
        if (inputDesc) inputDesc.value = '';
        if (inputTela) inputTela.value = '';
        if (inputColor) inputColor.value = '';
        if (inputRef) inputRef.value = '';
        
        // Limpiar tabla de telas
        const tbodyTelas = document.getElementById('tbody-telas');
        if (tbodyTelas) {
            // Mantener la fila de entrada pero limpiar las filas agregadas
            const rows = tbodyTelas.querySelectorAll('tr');
            rows.forEach((row, idx) => {
                if (idx > 0) { // Mantener la primera fila (input)
                    row.remove();
                }
            });
        }
        
        // Limpiar preview de imagen de tela
        const previewTela = document.getElementById('nueva-prenda-tela-preview');
        if (previewTela) {
            previewTela.innerHTML = '';
            previewTela.style.display = 'none';
        }
        
        // Limpiar tarjetas de géneros
        const tarjetasGeneros = document.getElementById('tarjetas-generos-container');
        if (tarjetasGeneros) tarjetasGeneros.innerHTML = '';
        
        // Limpiar tarjetas de procesos
        const tarjetasProcesos = document.getElementById('contenedor-tarjetas-procesos');
        if (tarjetasProcesos) {
            tarjetasProcesos.innerHTML = '';
            tarjetasProcesos.style.display = 'none';
        }
        
        // Limpiar preview/galería de fotos de prenda
        const fotoPrincipal = document.getElementById('nueva-prenda-foto-preview');
        const fotoContador = document.getElementById('nueva-prenda-foto-contador');
        if (fotoPrincipal) {
            fotoPrincipal.innerHTML = '<div class="foto-preview-content"><div class="material-symbols-rounded">add_photo_alternate</div><div class="foto-preview-text">Agregar</div></div>';
        }
        if (fotoContador) fotoContador.innerHTML = '';
        
        // Limpiar ImageStorageService
        if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.limpiar === 'function') {
            window.imagenesPrendaStorage.limpiar();
        }
        
        // Reset botones de género
        const btnDama = document.getElementById('btn-genero-dama');
        const btnCaballero = document.getElementById('btn-genero-caballero');
        if (btnDama) {
            btnDama.setAttribute('data-selected', 'false');
            btnDama.classList.remove('selected');
        }
        if (btnCaballero) {
            btnCaballero.setAttribute('data-selected', 'false');
            btnCaballero.classList.remove('selected');
        }
        
        // Limpiar total de prendas
        const totalPrendas = document.getElementById('total-prendas');
        if (totalPrendas) totalPrendas.textContent = '0';
        
        // Limpiar checkboxes y inputs de variaciones
        const checkboxesVariaciones = [
            'aplica-manga', 'manga-input', 'manga-obs',
            'aplica-bolsillos', 'bolsillos-obs',
            'aplica-broche', 'broche-input', 'broche-obs',
            'checkbox-reflectivo', 'checkbox-bordado', 'checkbox-estampado',
            'checkbox-dtf', 'checkbox-sublimado',
            'aplica-reflectivo', 'dama', 'caballero'
        ];
        checkboxesVariaciones.forEach(id => {
            const elem = document.getElementById(id);
            if (elem) {
                if (elem.type === 'checkbox' || elem.type === 'radio') {
                    elem.checked = false;
                    if (elem._ignorarOnclick) elem._ignorarOnclick = false;
                } else if (elem.type === 'text' || elem.type === 'select-one') {
                    elem.value = '';
                    if (elem.disabled) elem.disabled = true;
                }
            }
        });
        
        // Limpiar globales - AMBOS LUGARES donde se guardan telas
        window.telasAgregadas = [];
        window.telasCreacion = [];  // También limpiar donde se guardan en CREATE mode
        window.procesosSeleccionados = {};
        window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {} };
        window.imagenesProcesoActual = [];
        
        console.log('[limpiarFormularioPrenda] Formulario limpiado para nueva prenda');
    }

    /**
     * Agregar EPP desde modal
     */
    async agregarEPPDesdeModal(eppData) {
        try {
            if (!eppData || !eppData.id) {
                this.notificationService?.error('EPP inválido');
                return;
            }

            const itemData = {
                tipo: 'epp',
                referencia_id: eppData.id,
                nombre: eppData.nombre_completo || eppData.nombre,
                descripcion: eppData.descripcion,
                datos_presentacion: {
                    // Datos para renderizar
                    codigo: eppData.codigo,
                    // ... otros datos
                }
            };

            const exito = await this.agregarItem(itemData);

            if (exito) {
                this.notificationService.exito('EPP agregado correctamente');
            }

            return exito;
        } catch (error) {
            console.error('[agregarEPPDesdeModal]', error);
            this.notificationService?.error('Error: ' + error.message);
            return false;
        }
    }

    async manejarSubmitFormulario(e) {
        e.preventDefault();

        try {
            if (!this.notificationService || !this.apiService) return;

            const clienteInput = document.getElementById('cliente_editable');
            if (!clienteInput?.value?.trim()) {
                this.notificationService.error('El cliente es requerido');
                clienteInput?.focus();
                return;
            }

            // Validación básica UI
            if (this.items.length === 0) {
                this.notificationService.error('Debe agregar al menos un item');
                return;
            }

            this.mostrarCargando('Validando pedido...');

            const pedidoData = {
                cliente: clienteInput.value,
                items: this.items.map(item => ({ id: item.id, tipo: item.tipo })),
                // ... otros campos
            };

            // Backend valida TODAS las reglas de negocio
            const validacion = await this.apiService.validarPedido(pedidoData);

            if (!validacion.success) {
                this.ocultarCargando();
                const errores = validacion.validation_errors || [];
                if (errores.length > 0) {
                    alert('Errores:\n' + errores.map(e => e.message).join('\n'));
                }
                return;
            }

            this.mostrarCargando('Creando pedido...');
            const resultado = await this.apiService.crearPedido(pedidoData);

            if (resultado.success) {
                this.ocultarCargando();
                this.mostrarModalExito();
            }
        } catch (error) {
            console.error('[manejarSubmitFormulario]', error);
            this.ocultarCargando();
            this.notificationService?.error('Error: ' + error.message);
        }
    }

    // ====== HELPERS ======

    recolectarPrendaDelFormulario() {
        // Recopilar TODOS los datos de la prenda desde el formulario
        // Usando los IDs correctos como en prenda-editor
        
        console.log('[recolectarPrendaDelFormulario] 🔍 Recolectando datos de la prenda...');
        
        // IDs correctos del formulario modal
        const nombre = document.getElementById('nueva-prenda-nombre')?.value || '';
        const descripcion = document.getElementById('nueva-prenda-descripcion')?.value || '';
        const origen = document.getElementById('nueva-prenda-origen-select')?.value || 'confeccion';
        
        // Tallas del formulario
        const tallasRelacionales = window.tallasRelacionales || {};
        
        // Telas agregadas - BUSCAR EN AMBOS LUGARES
        // window.telasCreacion se usa en modo CREATE
        // window.telasAgregadas se usa en modo EDIT
        let telasAgregadas = window.telasAgregadas || [];
        if (telasAgregadas.length === 0 && window.telasCreacion && window.telasCreacion.length > 0) {
            telasAgregadas = window.telasCreacion;
            console.log('[recolectarPrendaDelFormulario]  Usando telasCreacion en lugar de telasAgregadas');
        }
        
        // Variaciones del formulario
        const variaciones = {
            genero_id: this.obtenerGeneroSeleccionado(),
            tipo_manga: document.getElementById('manga-input')?.value,
            tipo_broche: document.getElementById('broche-input')?.value,
            aplica_bolsillos: document.getElementById('aplica-bolsillos')?.checked || false,
            aplica_reflectivo: document.getElementById('aplica-reflectivo')?.checked || false
        };
        
        // Procesos del formulario
        const procesos = window.procesosSeleccionados || {};
        
        const prendaData = {
            nombre_prenda: nombre,
            descripcion: descripcion,
            origen: origen,
            cantidad_talla: tallasRelacionales,  // w.tallasRelacionales tiene {DAMA: {...}, CABALLERO: {...}}
            telasAgregadas: telasAgregadas,
            variantes: variaciones,
            procesos: procesos
        };
        
        console.log('[recolectarPrendaDelFormulario]  Datos recolectados:', {
            nombre: nombre,
            tiene_telas: telasAgregadas.length > 0,
            telas_count: telasAgregadas.length,
            telas_sources: {
                telasAgregadas: window.telasAgregadas?.length || 0,
                telasCreacion: window.telasCreacion?.length || 0
            },
            tiene_procesos: Object.keys(procesos).length > 0,
            procesos_count: Object.keys(procesos).length,
            genero: variaciones.genero_id
        });
        
        return prendaData;
    }
    
    obtenerGeneroSeleccionado() {
        // Obtener género seleccionado desde los checkboxes
        const damaCb = document.getElementById('dama');
        const caballeroCb = document.getElementById('caballero');
        
        if (damaCb?.checked) return 1;  // DAMA
        if (caballeroCb?.checked) return 2;  // CABALLERO
        return 1; // Default DAMA
    }

    cerrarModalAgregarPrendaNueva() {
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    abrirModalSeleccionPrendas() {
        if (window.abrirModalSeleccionPrendas) {
            window.abrirModalSeleccionPrendas();
        }
    }

    abrirModalAgregarPrendaNueva() {
        // Abrir modal directamente sin recursión
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'flex';
        } else {
            this.notificationService?.error('Modal no encontrado');
        }
    }

    mostrarVistaPreviaFactura() {
        if (this.renderer) {
            this.renderer.mostrarVistaPreviaFactura(this.items);
        }
    }

    mostrarCargando(mensaje = 'Cargando...') {
        this.ocultarCargando();
        // Implementación del loader
    }

    ocultarCargando() {
        const loader = document.getElementById('pedido-loader');
        if (loader) loader.remove();
    }

    mostrarModalExito() {
        // Implementación del modal de éxito
    }

    recolectarDatosPedido() {
        return {
            items: this.items
        };
    }

    /**
     * Obtener items ordenados (para edición)
     */
    obtenerItemsOrdenados() {
        return this.items || [];
    }

    /**
     * Cargar prenda en modal para edición
     */
    cargarPrendaEnModalParaEdicion(prenda, prendaIndex) {
        // Delegar a PrendaModalEditor
        this.prendaEditor.cargarPrendaEnModal(prenda, prendaIndex);
        
        // Guardar referencia de edición en esta clase también
        this.prendaEditIndex = this.prendaEditor.obtenerIndicePrendaEdicion();
    }
}

// ====== INICIALIZACIÓN ======

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.gestionItemsUI) {
            const notificationService = typeof NotificationService !== 'undefined' 
                ? new NotificationService() 
                : null;

            window.gestionItemsUI = new GestionItemsUIRefactorizado({
                notificationService: notificationService
            });
        }
    });
} else {
    if (!window.gestionItemsUI) {
        const notificationService = typeof NotificationService !== 'undefined' 
            ? new NotificationService() 
            : null;

        window.gestionItemsUI = new GestionItemsUIRefactorizado({
            notificationService: notificationService
        });
    }
}
