/**
 * 🔒 SharedPrendaEditorService
 * 
 * IMPORTANTE: Este servicio es AGNÓSTICO de cotizaciones
 * - NO toca window.cotizacionActual
 * - NO tiene lógica de tipo_cotizacion_id
 * - NO interfiere con CotizacionEditorService
 * - COMPLETAMENTE AISLADO de módulo de cotizaciones
 * 
 * USO: Solo para Crear Pedidos y Editar Pedidos
 */

class SharedPrendaEditorService {
    constructor(dependencies) {
        // Validar que recibe dependencias
        if (!dependencies) {
            throw new Error('SharedPrendaEditorService: Inyectar dependencias requeridas');
        }

        // Servicios inyectados (desacoplados)
        this.dataService = dependencies.dataService;
        this.storageService = dependencies.storageService;
        this.validationService = dependencies.validationService;
        this.eventBus = dependencies.eventBus || new EventBus();

        // Estado de edición (SOLO para este servicio)
        this.cache = {
            prendaActual: null,
            cambiosPendientes: new Map(),
            estado: 'idle', // idle | editando | guardando
            modo: null      // crear | editar | duplicar
        };

        console.log('[SharedPrendaEditor] ✓ Inicializado (AISLADO DE COTIZACIONES)');
    }

    /**
     * 🎯 MÉTODO PRINCIPAL - Abrir editor
     * 
     * NOTA: Este método IGNORA cotizaciones completamente
     * Si necesitas lógica de cotización, usa CotizacionEditorService en su lugar
     * 
     * NUEVO: Soporta 'crear-desde-cotizacion' para crear pedidos desde prendas de cotizaciones
     * En este caso, se hace una COPIA de los datos (NO se modifica la cotización original)
     */
    async abrirEditor(config = {}) {
        console.log('[SharedPrendaEditor] 📖 abrirEditor - Contexto:', config.contexto);

        // Validar parámetros
        const configValidada = {
            modo: config.modo || 'crear',                    // crear | editar | duplicar
            prendaId: config.prendaId,                       // Para editar/duplicar
            prendaLocal: config.prendaLocal,                 // Para crear
            contexto: config.contexto,                       // contextual info
            cotizacionId: config.cotizacionId,               // NUEVO: ID de cotización origen (si aplica)
            prendaCotizacionId: config.prendaCotizacionId,   // NUEVO: ID de prenda en cotización
            origenCotizacion: config.origenCotizacion,       // NUEVO: Metadatos de origen para auditoría
            onGuardar: config.onGuardar,                     // callback
            onCancelar: config.onCancelar,                   // callback
            ...config
        };

        // Validar que es un contexto permitido (NO cotización)
        const contextosPermitidos = ['crear-nuevo', 'pedidos-editable', 'crear-desde-cotizacion', 'otros-pedidos'];
        if (config.contexto && !contextosPermitidos.includes(config.contexto)) {
            console.warn(`[SharedPrendaEditor]  Contexto inusual: ${config.contexto}`);
        }

        // IMPORTANTE: Para crear-desde-cotizacion, verificar que se hizo copia de datos
        if (config.contexto === 'crear-desde-cotizacion' && !config.prendaLocal) {
            throw new Error('[SharedPrendaEditor]  Para crear-desde-cotizacion, debe proporcionar prendaLocal (copia de datos)');
        }

        try {
            // Cambiar estado
            this.cache.estado = 'editando';
            this.cache.modo = configValidada.modo;

            // Emitir evento: editor abierto
            this.eventBus.emit('editor:abierto', configValidada);

            let prenda;

            // 1️⃣ CARGAR DATOS según modo
            switch (configValidada.modo) {
                case 'crear':
                    // Crear vacía o desde datos locales
                    prenda = configValidada.prendaLocal || this.crearPrendaVacia();
                    console.log('[SharedPrendaEditor] 📝 Modo CREAR - Prenda nueva');
                    break;

                case 'editar':
                    // Cargar desde BD (SIN lógica de cotización)
                    if (!configValidada.prendaId) {
                        throw new Error('Modo EDITAR requiere prendaId');
                    }
                    prenda = await this.dataService.obtenerPrendPorId(configValidada.prendaId);
                    console.log('[SharedPrendaEditor] ✏️ Modo EDITAR - Prenda ID:', configValidada.prendaId);
                    break;

                case 'duplicar':
                    // Duplicar una existente
                    if (!configValidada.prendaId) {
                        throw new Error('Modo DUPLICAR requiere prendaId');
                    }
                    const original = await this.dataService.obtenerPrendPorId(configValidada.prendaId);
                    prenda = { ...JSON.parse(JSON.stringify(original)) };
                    prenda.id = null; // Remover ID para crear nueva
                    console.log('[SharedPrendaEditor]  Modo DUPLICAR - Prenda duplicada');
                    break;

                default:
                    throw new Error(`Modo desconocido: ${configValidada.modo}`);
            }

            // 2️⃣ GUARDAR EN CONTEXTO LOCAL (NO en cotización)
            this.cache.prendaActual = { ...prenda };

            // 2.5️⃣ GUARDAR METADATOS DE ORIGEN (para auditoría)
            this.cache.editorState = {
                contexto: configValidada.contexto,
                modo: configValidada.modo,
                cotizacionId: configValidada.cotizacionId,        // NUEVO
                prendaCotizacionId: configValidada.prendaCotizacionId,  // NUEVO
                origenCotizacion: configValidada.origenCotizacion  // NUEVO
            };

            // 3️⃣ EMITIR EVENTO: datos cargados
            this.eventBus.emit('editor:datos-cargados', {
                prenda,
                modo: configValidada.modo,
                contexto: configValidada.contexto,
                cotizacionId: configValidada.cotizacionId,        // NUEVO
                origenCotizacion: configValidada.origenCotizacion  // NUEVO
            });

            console.log('[SharedPrendaEditor] ✓ Editor abierto correctamente');
            return prenda;

        } catch (error) {
            console.error('[SharedPrendaEditor]  Error abriendo editor:', error);
            this.cache.estado = 'idle';
            this.eventBus.emit('editor:error', error);
            throw error;
        }
    }

    /**
     * Guardar cambios (CREATE o UPDATE)
     */
    async guardarCambios() {
        console.log('[SharedPrendaEditor] 💾 Guardando cambios...');

        try {
            if (!this.cache.prendaActual) {
                throw new Error('No hay prenda en edición');
            }

            // 1️⃣ RECOLECTAR DATOS del modal
            const datos = this.recolectarDatosDelModal();

            // 2️⃣ VALIDAR
            const errores = this.validationService.validar(datos);
            if (errores.length > 0) {
                console.warn('[SharedPrendaEditor]  Errores de validación:', errores);
                this.eventBus.emit('editor:error-validacion', errores);
                throw new Error('Datos inválidos');
            }

            // 3️⃣ PROCESAR IMÁGENES
            const datosConImagenes = await this.procesarCambiosImagenes(datos);

            // 4️⃣ GUARDAR EN BD
            this.cache.estado = 'guardando';
            const prendaGuardada = await this.dataService.guardarPrenda(datosConImagenes);

            // 5️⃣ ACTUALIZAR CACHE
            this.cache.prendaActual = prendaGuardada;
            this.cache.cambiosPendientes.clear();
            this.cache.estado = 'editando';

            // 6️⃣ EMITIR EVENTO
            this.eventBus.emit('editor:guardado', prendaGuardada);

            console.log('[SharedPrendaEditor]  Cambios guardados exitosamente');
            return prendaGuardada;

        } catch (error) {
            console.error('[SharedPrendaEditor]  Error guardando:', error);
            this.cache.estado = 'editando';
            this.eventBus.emit('editor:error', error);
            throw error;
        }
    }

    /**
     * Cancelar edición
     */
    cancelarEdicion() {
        console.log('[SharedPrendaEditor]  Edición cancelada');
        this.cache.prendaActual = null;
        this.cache.cambiosPendientes.clear();
        this.cache.estado = 'idle';
        this.cache.modo = null;
        this.cache.editorState = null;  // NUEVO: Limpiar estado
        this.eventBus.emit('editor:cancelado');
    }

    /**
     * Crear estructura vacía de prenda (GENÉRICA, sin lógica de cotización)
     */
    crearPrendaVacia() {
        return {
            id: null,
            nombre: '',
            descripcion: '',
            origen: 'confeccion',  // Default genérico
            tallas: [],
            telas: [],
            procesos: [],
            imagenes: [],
            variantes: {}
        };
    }

    /**
     * Recolectar datos del modal
     */
    recolectarDatosDelModal() {
        const nombreInput = document.getElementById('nueva-prenda-nombre');
        const descInput = document.getElementById('nueva-prenda-descripcion');
        const origenSelect = document.getElementById('nueva-prenda-origen-select');

        return {
            ...this.cache.prendaActual,
            nombre: nombreInput?.value || '',
            descripcion: descInput?.value || '',
            origen: origenSelect?.value || 'confeccion',
            tallas: window.tallasRelacionales || [],
            telas: window.telasCotizacion || [], // TODO: cambiar a nombre genérico
            procesos: window.procesosSeleccionados || [],
            imagenes: window.imagenesCreacion || []
        };
    }

    /**
     * Procesar cambios de imágenes
     */
    async procesarCambiosImagenes(datos) {
        if (!this.storageService) {
            console.warn('[SharedPrendaEditor]  StorageService no disponible');
            return datos;
        }

        const imagenesActuales = this.cache.prendaActual.imagenes || [];
        const imagenesNuevas = datos.imagenes || [];

        const cambios = await this.storageService.procesarCambiosImagenes(
            imagenesActuales,
            imagenesNuevas
        );

        // Procesar cambios
        if (cambios.agregar.length > 0) {
            const urlsNuevas = await this.storageService.subirImagenes(cambios.agregar);
            cambios.mantener.push(...urlsNuevas);
        }

        if (cambios.eliminar.length > 0) {
            await this.storageService.eliminarImagenes(cambios.eliminar);
        }

        datos.imagenes = cambios.mantener;
        return datos;
    }

    /**
     * Obtener estado actual
     */
    getEstado() {
        return {
            ...this.cache,
            tieneEdicion: !!this.cache.prendaActual
        };
    }

    /**
     * Verificar si hay cambios pendientes
     */
    hayChangios() {
        return this.cache.cambiosPendientes.size > 0;
    }
}

// Exportar
window.SharedPrendaEditorService = SharedPrendaEditorService;
console.log('[SharedPrendaEditorService] 🔐 Cargado (COMPLETAMENTE AISLADO DE COTIZACIONES)');
