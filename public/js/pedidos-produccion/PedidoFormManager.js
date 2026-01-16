/**
 * 🎯 PEDIDO FORM MANAGER
 * 
 * Gestor central de estado para formulario complejo de pedidos de producción.
 * Maneja:
 * - Estado global del pedido (prendas, variantes, fotos, procesos)
 * - CRUD de prendas, variantes, procesos
 * - Manejo de archivos (fotos)
 * - Persistencia en localStorage
 * - Event listeners para reactividad
 * 
 * @author Senior Frontend Developer
 * @version 1.0.0
 */

class PedidoFormManager {
    constructor(config = {}) {
        // ==================== CONFIGURACIÓN ====================
        this.config = {
            storageKey: 'pedidoFormState',
            autoSave: config.autoSave !== false,
            saveInterval: 30000, // 30s
            maxFileSizeMB: 10,
            maxFotosPerPrenda: 10,
            ...config
        };

        // ==================== ESTADO PRINCIPAL ====================
        this.state = {
            pedido_produccion_id: null,
            prendas: []
        };

        // ==================== LISTENERS Y EVENTOS ====================
        this.listeners = new Map();
        this.history = [];
        this.historyIndex = -1;

        // ==================== INICIALIZACIÓN ====================
        this.init();
    }

    // ==================== INICIALIZACIÓN ====================

    init() {
        console.log('🎯 Inicializando PedidoFormManager...');
        
        // Cargar estado guardado
        this.loadFromStorage();
        
        // Auto-guardado
        if (this.config.autoSave) {
            this.startAutoSave();
        }

        console.log('✅ PedidoFormManager inicializado');
    }

    // ==================== PERSISTENCIA ====================

    /**
     * Guardar estado en localStorage
     */
    saveToStorage() {
        try {
            localStorage.setItem(
                this.config.storageKey,
                JSON.stringify(this.state)
            );
            console.log('💾 Estado guardado en localStorage');
        } catch (error) {
            console.error('❌ Error guardando estado:', error);
        }
    }

    /**
     * Cargar estado desde localStorage
     */
    loadFromStorage() {
        try {
            const saved = localStorage.getItem(this.config.storageKey);
            if (saved) {
                this.state = JSON.parse(saved);
                console.log('📂 Estado cargado desde localStorage');
            }
        } catch (error) {
            console.error('❌ Error cargando estado:', error);
        }
    }

    /**
     * Iniciar auto-guardado periódico
     */
    startAutoSave() {
        this.autoSaveInterval = setInterval(() => {
            this.saveToStorage();
        }, this.config.saveInterval);
    }

    /**
     * Detener auto-guardado
     */
    stopAutoSave() {
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
        }
    }

    /**
     * Limpiar estado completamente
     */
    clear() {
        this.state = {
            pedido_produccion_id: null,
            prendas: []
        };
        this.history = [];
        this.historyIndex = -1;
        this.saveToStorage();
        this.notifyListeners('state:cleared');
        console.log('🗑️  Estado limpiado');
    }

    // ==================== GESTIÓN DE PEDIDO ====================

    /**
     * Establecer ID del pedido de producción
     */
    setPedidoId(pedidoId) {
        if (!Number.isInteger(pedidoId) || pedidoId <= 0) {
            throw new Error('❌ pedido_produccion_id debe ser un número > 0');
        }
        this.state.pedido_produccion_id = pedidoId;
        this.saveToStorage();
        this.notifyListeners('pedido:updated');
        console.log(`✅ Pedido ID establecido: ${pedidoId}`);
    }

    /**
     * Obtener ID del pedido
     */
    getPedidoId() {
        return this.state.pedido_produccion_id;
    }

    // ==================== GESTIÓN DE PRENDAS ====================

    /**
     * Agregar nueva prenda
     */
    addPrenda(dataPrenda = {}) {
        const prenda = this.createPrendaTemplate(dataPrenda);
        this.state.prendas.push(prenda);
        
        this.saveToStorage();
        this.notifyListeners('prenda:added', { prenda });
        
        console.log(`✅ Prenda agregada (ID: ${prenda._id})`);
        return prenda;
    }

    /**
     * Template de prenda vacía
     */
    createPrendaTemplate(data = {}) {
        return {
            _id: this.generateId(),
            nombre_prenda: data.nombre_prenda || '',
            descripcion: data.descripcion || '',
            genero: data.genero || null, // 'dama', 'caballero', 'unisex'
            de_bodega: data.de_bodega || false,
            fotos_prenda: data.fotos_prenda || [],
            fotos_tela: data.fotos_tela || [],
            variantes: data.variantes || [],
            procesos: data.procesos || []
        };
    }

    /**
     * Editar prenda existente
     */
    editPrenda(prendaId, updates) {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        Object.assign(prenda, updates);
        this.saveToStorage();
        this.notifyListeners('prenda:updated', { prendaId, updates });
        
        console.log(`✅ Prenda actualizada: ${prendaId}`);
        return prenda;
    }

    /**
     * Obtener prenda por ID
     */
    getPrenda(prendaId) {
        return this.state.prendas.find(p => p._id === prendaId);
    }

    /**
     * Eliminar prenda
     */
    deletePrenda(prendaId) {
        const index = this.state.prendas.findIndex(p => p._id === prendaId);
        if (index === -1) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        const prenda = this.state.prendas[index];
        this.state.prendas.splice(index, 1);
        
        this.saveToStorage();
        this.notifyListeners('prenda:deleted', { prendaId });
        
        console.log(`✅ Prenda eliminada: ${prendaId}`);
        return prenda;
    }

    /**
     * Obtener todas las prendas
     */
    getPrendas() {
        return [...this.state.prendas];
    }

    // ==================== GESTIÓN DE VARIANTES ====================

    /**
     * Agregar variante a prenda
     */
    addVariante(prendaId, dataVariante = {}) {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        const variante = {
            _id: this.generateId(),
            talla: dataVariante.talla || '',
            cantidad: dataVariante.cantidad || 0,
            color_id: dataVariante.color_id || null,
            tela_id: dataVariante.tela_id || null,
            tipo_manga_id: dataVariante.tipo_manga_id || null,
            manga_obs: dataVariante.manga_obs || '',
            tipo_broche_boton_id: dataVariante.tipo_broche_boton_id || null,
            broche_boton_obs: dataVariante.broche_boton_obs || '',
            tiene_bolsillos: dataVariante.tiene_bolsillos || false,
            bolsillos_obs: dataVariante.bolsillos_obs || ''
        };

        prenda.variantes.push(variante);
        this.saveToStorage();
        this.notifyListeners('variante:added', { prendaId, variante });
        
        console.log(`✅ Variante agregada a prenda ${prendaId}`);
        return variante;
    }

    /**
     * Editar variante
     */
    editVariante(prendaId, varianteId, updates) {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        const variante = prenda.variantes.find(v => v._id === varianteId);
        if (!variante) throw new Error(`❌ Variante no encontrada: ${varianteId}`);

        Object.assign(variante, updates);
        this.saveToStorage();
        this.notifyListeners('variante:updated', { prendaId, varianteId, updates });
        
        return variante;
    }

    /**
     * Eliminar variante
     */
    deleteVariante(prendaId, varianteId) {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        const index = prenda.variantes.findIndex(v => v._id === varianteId);
        if (index === -1) throw new Error(`❌ Variante no encontrada: ${varianteId}`);

        const variante = prenda.variantes[index];
        prenda.variantes.splice(index, 1);
        
        this.saveToStorage();
        this.notifyListeners('variante:deleted', { prendaId, varianteId });
        
        return variante;
    }

    /**
     * Obtener variantes de prenda
     */
    getVariantes(prendaId) {
        const prenda = this.getPrenda(prendaId);
        return prenda ? [...prenda.variantes] : [];
    }

    // ==================== GESTIÓN DE FOTOS ====================

    /**
     * Agregar foto a prenda (referencia)
     */
    addFotoPrenda(prendaId, fotoData) {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        if (prenda.fotos_prenda.length >= this.config.maxFotosPerPrenda) {
            throw new Error(`❌ Máximo ${this.config.maxFotosPerPrenda} fotos por prenda`);
        }

        const foto = {
            _id: this.generateId(),
            file: fotoData.file, // File object
            nombre: fotoData.nombre || fotoData.file.name,
            tipo_archivo: fotoData.file.type,
            tamanio: fotoData.file.size,
            fecha_carga: new Date().toISOString(),
            observaciones: fotoData.observaciones || ''
        };

        prenda.fotos_prenda.push(foto);
        this.saveToStorage();
        this.notifyListeners('foto:added', { prendaId, foto });
        
        console.log(`✅ Foto agregada a prenda ${prendaId}`);
        return foto;
    }

    /**
     * Agregar foto de tela
     */
    addFotoTela(prendaId, fotoData) {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        const foto = {
            _id: this.generateId(),
            file: fotoData.file,
            nombre: fotoData.nombre || fotoData.file.name,
            color: fotoData.color || '',
            observaciones: fotoData.observaciones || '',
            tipo_archivo: fotoData.file.type,
            tamanio: fotoData.file.size,
            fecha_carga: new Date().toISOString()
        };

        prenda.fotos_tela.push(foto);
        this.saveToStorage();
        this.notifyListeners('foto_tela:added', { prendaId, foto });
        
        return foto;
    }

    /**
     * Eliminar foto
     */
    deleteFoto(prendaId, fotoId, tipo = 'prenda') {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        const fotos = tipo === 'tela' ? prenda.fotos_tela : prenda.fotos_prenda;
        const index = fotos.findIndex(f => f._id === fotoId);
        
        if (index === -1) throw new Error(`❌ Foto no encontrada: ${fotoId}`);

        const foto = fotos[index];
        fotos.splice(index, 1);
        
        this.saveToStorage();
        this.notifyListeners('foto:deleted', { prendaId, fotoId, tipo });
        
        return foto;
    }

    /**
     * Obtener fotos de prenda
     */
    getFotos(prendaId) {
        const prenda = this.getPrenda(prendaId);
        return prenda ? [...prenda.fotos_prenda] : [];
    }

    /**
     * Obtener fotos de tela
     */
    getFotosTela(prendaId) {
        const prenda = this.getPrenda(prendaId);
        return prenda ? [...prenda.fotos_tela] : [];
    }

    // ==================== GESTIÓN DE PROCESOS ====================

    /**
     * Agregar proceso a prenda
     */
    addProceso(prendaId, dataProceso = {}) {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        const proceso = {
            _id: this.generateId(),
            tipo_proceso_id: dataProceso.tipo_proceso_id || null, // FK a tipos_procesos
            ubicaciones: dataProceso.ubicaciones || [], // ['pecho', 'espalda', etc]
            observaciones: dataProceso.observaciones || '',
            imagenes: dataProceso.imagenes || []
        };

        prenda.procesos.push(proceso);
        this.saveToStorage();
        this.notifyListeners('proceso:added', { prendaId, proceso });
        
        console.log(`✅ Proceso agregado a prenda ${prendaId}`);
        return proceso;
    }

    /**
     * Editar proceso
     */
    editProceso(prendaId, procesoId, updates) {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        const proceso = prenda.procesos.find(p => p._id === procesoId);
        if (!proceso) throw new Error(`❌ Proceso no encontrado: ${procesoId}`);

        Object.assign(proceso, updates);
        this.saveToStorage();
        this.notifyListeners('proceso:updated', { prendaId, procesoId, updates });
        
        return proceso;
    }

    /**
     * Eliminar proceso
     */
    deleteProceso(prendaId, procesoId) {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        const index = prenda.procesos.findIndex(p => p._id === procesoId);
        if (index === -1) throw new Error(`❌ Proceso no encontrado: ${procesoId}`);

        const proceso = prenda.procesos[index];
        prenda.procesos.splice(index, 1);
        
        this.saveToStorage();
        this.notifyListeners('proceso:deleted', { prendaId, procesoId });
        
        return proceso;
    }

    /**
     * Agregar imagen a proceso
     */
    addImagenProceso(prendaId, procesoId, imagenData) {
        const prenda = this.getPrenda(prendaId);
        if (!prenda) throw new Error(`❌ Prenda no encontrada: ${prendaId}`);

        const proceso = prenda.procesos.find(p => p._id === procesoId);
        if (!proceso) throw new Error(`❌ Proceso no encontrado: ${procesoId}`);

        const imagen = {
            _id: this.generateId(),
            file: imagenData.file,
            nombre: imagenData.nombre || imagenData.file.name,
            observaciones: imagenData.observaciones || '',
            tipo_archivo: imagenData.file.type,
            tamanio: imagenData.file.size,
            fecha_carga: new Date().toISOString()
        };

        proceso.imagenes.push(imagen);
        this.saveToStorage();
        this.notifyListeners('imagen_proceso:added', { prendaId, procesoId, imagen });
        
        return imagen;
    }

    /**
     * Obtener procesos de prenda
     */
    getProcesos(prendaId) {
        const prenda = this.getPrenda(prendaId);
        return prenda ? [...prenda.procesos] : [];
    }

    // ==================== EVENT LISTENERS ====================

    /**
     * Escuchar cambios de estado
     */
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);
        console.log(`📻 Listener agregado: ${event}`);
    }

    /**
     * Dejar de escuchar
     */
    off(event, callback) {
        if (!this.listeners.has(event)) return;
        
        const callbacks = this.listeners.get(event);
        const index = callbacks.indexOf(callback);
        
        if (index > -1) {
            callbacks.splice(index, 1);
        }
    }

    /**
     * Notificar listeners (interno)
     */
    notifyListeners(event, data = {}) {
        if (!this.listeners.has(event)) return;
        
        this.listeners.get(event).forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error(`❌ Error en listener ${event}:`, error);
            }
        });
    }

    // ==================== UTILITIES ====================

    /**
     * Generar ID único
     */
    generateId() {
        return `_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Obtener estado completo (preparado para envío)
     */
    getState() {
        return JSON.parse(JSON.stringify(this.state));
    }

    /**
     * Obtener resumen del pedido
     */
    getSummary() {
        const prendas = this.state.prendas;
        const totalPrendas = prendas.length;
        const totalVariantes = prendas.reduce((sum, p) => sum + p.variantes.length, 0);
        const totalItems = prendas.reduce((sum, p) => 
            sum + p.variantes.reduce((vs, v) => vs + v.cantidad, 0), 
            0
        );
        const totalProcesos = prendas.reduce((sum, p) => sum + p.procesos.length, 0);

        return {
            pedido_id: this.state.pedido_produccion_id,
            prendas: totalPrendas,
            variantes: totalVariantes,
            items: totalItems,
            procesos: totalProcesos,
            completo: totalPrendas > 0 && totalVariantes > 0
        };
    }

    /**
     * Destruir manager
     */
    destroy() {
        this.stopAutoSave();
        this.listeners.clear();
        console.log('🗑️  PedidoFormManager destruido');
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PedidoFormManager;
}
