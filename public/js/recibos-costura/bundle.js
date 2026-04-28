/**
 *  Bundle compilado del módulo Recibos Costura
 * 
 * Este archivo incluye TODOS los componentes del módulo en un único archivo
 * para evitar problemas de carga con Vite y assets.
 * 
 * Incluye:
 * - Domain Layer (Value Objects)
 * - Infrastructure Layer (State Manager + API)
 * - Presentation Layer (Controllers)
 * - Entry Point (Initializer)
 */

// ========================================
// 1. VALUE OBJECTS - DOMAIN LAYER
// ========================================

/**
 * EstadoRecibo - Value Object inmutable
 */
class EstadoRecibo {
    static PENDIENTE_INSUMOS = 'PENDIENTE_INSUMOS';
    static EN_EJECUCION = 'En Ejecución';
    static NO_INICIADO = 'No iniciado';

    constructor(valor) {
        const estadosValidos = [
            EstadoRecibo.PENDIENTE_INSUMOS,
            EstadoRecibo.EN_EJECUCION,
            EstadoRecibo.NO_INICIADO
        ];

        if (!estadosValidos.includes(valor)) {
            throw new Error(`Estado inválido: "${valor}". Estados válidos: ${estadosValidos.join(', ')}`);
        }

        Object.defineProperty(this, '_value', {
            value: valor,
            writable: false,
            configurable: false,
            enumerable: false
        });
    }

    toString() { return this._value; }
    getValue() { return this._value; }
    equals(otro) { return otro instanceof EstadoRecibo && this._value === otro._value; }
    getColorBadge() {
        const colores = {
            [EstadoRecibo.PENDIENTE_INSUMOS]: 'secondary',
            [EstadoRecibo.EN_EJECUCION]: 'info',
            [EstadoRecibo.NO_INICIADO]: 'warning'
        };
        return colores[this._value] || 'light';
    }
    getColorHex() {
        const colores = {
            [EstadoRecibo.PENDIENTE_INSUMOS]: '#6c757d',
            [EstadoRecibo.EN_EJECUCION]: '#0dcaf0',
            [EstadoRecibo.NO_INICIADO]: '#ffc107'
        };
        return colores[this._value] || '#ffffff';
    }
    getIcon() {
        const iconos = {
            [EstadoRecibo.PENDIENTE_INSUMOS]: 'fa-hourglass-half',
            [EstadoRecibo.EN_EJECUCION]: 'fa-spinner',
            [EstadoRecibo.NO_INICIADO]: 'fa-exclamation-circle'
        };
        return iconos[this._value] || 'fa-circle';
    }
    pendienteInsumos() { return this._value === EstadoRecibo.PENDIENTE_INSUMOS; }
    enEjecucion() { return this._value === EstadoRecibo.EN_EJECUCION; }
    noIniciado() { return this._value === EstadoRecibo.NO_INICIADO; }
    static from(valor) {
        if (!valor) throw new Error('El estado no puede ser vacío');
        return new EstadoRecibo(valor);
    }
    static todos() {
        return [
            new EstadoRecibo(EstadoRecibo.PENDIENTE_INSUMOS),
            new EstadoRecibo(EstadoRecibo.EN_EJECUCION),
            new EstadoRecibo(EstadoRecibo.NO_INICIADO)
        ];
    }
    static isValido(valor) {
        return [EstadoRecibo.PENDIENTE_INSUMOS, EstadoRecibo.EN_EJECUCION, EstadoRecibo.NO_INICIADO].includes(valor);
    }
}

/**
 * AreaRecibo - Value Object inmutable
 */
class AreaRecibo {
    static COSTURA = 'Costura';
    static CORTE = 'Corte';
    static INSUMOS = 'Insumos';
    static ESTAMPADO = 'Estampado';
    static BORDADO = 'Bordado';
    static CONTROL_CALIDAD = 'Control Calidad';

    constructor(valor) {
        const areasValidas = [
            AreaRecibo.COSTURA, AreaRecibo.CORTE, AreaRecibo.INSUMOS,
            AreaRecibo.ESTAMPADO, AreaRecibo.BORDADO, AreaRecibo.CONTROL_CALIDAD
        ];

        if (!areasValidas.includes(valor)) {
            throw new Error(`Área inválida: "${valor}". Áreas válidas: ${areasValidas.join(', ')}`);
        }

        Object.defineProperty(this, '_value', {
            value: valor,
            writable: false,
            configurable: false,
            enumerable: false
        });
    }

    toString() { return this._value; }
    getValue() { return this._value; }
    equals(otra) { return otra instanceof AreaRecibo && this._value === otra._value; }
    getColorBadge() {
        const colores = {
            [AreaRecibo.COSTURA]: 'primary',
            [AreaRecibo.CORTE]: 'info',
            [AreaRecibo.INSUMOS]: 'warning',
            [AreaRecibo.ESTAMPADO]: 'danger',
            [AreaRecibo.BORDADO]: 'success',
            [AreaRecibo.CONTROL_CALIDAD]: 'dark'
        };
        return colores[this._value] || 'light';
    }
    getColorHex() {
        const colores = {
            [AreaRecibo.COSTURA]: '#0d6efd',
            [AreaRecibo.CORTE]: '#0dcaf0',
            [AreaRecibo.INSUMOS]: '#ffc107',
            [AreaRecibo.ESTAMPADO]: '#dc3545',
            [AreaRecibo.BORDADO]: '#198754',
            [AreaRecibo.CONTROL_CALIDAD]: '#212529'
        };
        return colores[this._value] || '#ffffff';
    }
    getIcon() {
        const iconos = {
            [AreaRecibo.COSTURA]: 'fa-needle',
            [AreaRecibo.CORTE]: 'fa-cut',
            [AreaRecibo.INSUMOS]: 'fa-boxes',
            [AreaRecibo.ESTAMPADO]: 'fa-stamp',
            [AreaRecibo.BORDADO]: 'fa-palette',
            [AreaRecibo.CONTROL_CALIDAD]: 'fa-check-circle'
        };
        return iconos[this._value] || 'fa-circle';
    }
    esCostura() { return this._value === AreaRecibo.COSTURA; }
    esCorte() { return this._value === AreaRecibo.CORTE; }
    esInsumos() { return this._value === AreaRecibo.INSUMOS; }
    esEstampado() { return this._value === AreaRecibo.ESTAMPADO; }
    esBordado() { return this._value === AreaRecibo.BORDADO; }
    esControlCalidad() { return this._value === AreaRecibo.CONTROL_CALIDAD; }
    esAreaProduccion() { return ![AreaRecibo.INSUMOS, AreaRecibo.CONTROL_CALIDAD].includes(this._value); }
    static from(valor) {
        if (!valor) throw new Error('El área no puede ser vacía');
        return new AreaRecibo(valor);
    }
    static todas() {
        return [
            new AreaRecibo(AreaRecibo.COSTURA), new AreaRecibo(AreaRecibo.CORTE),
            new AreaRecibo(AreaRecibo.INSUMOS), new AreaRecibo(AreaRecibo.ESTAMPADO),
            new AreaRecibo(AreaRecibo.BORDADO), new AreaRecibo(AreaRecibo.CONTROL_CALIDAD)
        ];
    }
    static areasProduccion() {
        return [
            new AreaRecibo(AreaRecibo.COSTURA), new AreaRecibo(AreaRecibo.CORTE),
            new AreaRecibo(AreaRecibo.ESTAMPADO), new AreaRecibo(AreaRecibo.BORDADO)
        ];
    }
    static isValida(valor) {
        return [AreaRecibo.COSTURA, AreaRecibo.CORTE, AreaRecibo.INSUMOS,
                AreaRecibo.ESTAMPADO, AreaRecibo.BORDADO, AreaRecibo.CONTROL_CALIDAD].includes(valor);
    }
}

/**
 * DiasTranscurridos - Value Object inmutable
 */
class DiasTranscurridos {
    static RANGO_VERDE = { min: 0, max: 4, nombre: 'verde' };
    static RANGO_AMARILLO = { min: 5, max: 13, nombre: 'amarillo' };
    static RANGO_ROJO = { min: 14, max: Infinity, nombre: 'rojo' };

    constructor(numero) {
        if (!Number.isInteger(numero) || numero < 0) {
            throw new Error(`Los días deben ser un número entero no negativo. Recibido: ${numero}`);
        }

        Object.defineProperty(this, '_value', {
            value: numero,
            writable: false,
            configurable: false,
            enumerable: false
        });
    }

    toNumber() { return this._value; }
    toString() { return `${this._value} día${this._value === 1 ? '' : 's'}`; }
    equals(otros) { return otros instanceof DiasTranscurridos && this._value === otros._value; }
    getRango() {
        if (this._value >= DiasTranscurridos.RANGO_ROJO.min) return 'rojo';
        if (this._value >= DiasTranscurridos.RANGO_AMARILLO.min) return 'amarillo';
        return 'verde';
    }
    getColorBadge() {
        const rango = this.getRango();
        return { 'verde': 'success', 'amarillo': 'warning', 'rojo': 'danger' }[rango] || 'light';
    }
    getColorHex() {
        const rango = this.getRango();
        return { 'verde': '#198754', 'amarillo': '#ffc107', 'rojo': '#dc3545' }[rango] || '#ffffff';
    }
    getIcon() {
        const rango = this.getRango();
        return { 'verde': 'fa-check-circle', 'amarillo': 'fa-clock', 'rojo': 'fa-exclamation-circle' }[rango] || 'fa-circle';
    }
    esReciente() { return this.getRango() === 'verde'; }
    esNormal() { return this.getRango() === 'amarillo'; }
    esRetrasado() { return this.getRango() === 'rojo'; }
    static from(numero) {
        if (numero === null || numero === undefined) throw new Error('El número de días no puede ser nulo');
        return new DiasTranscurridos(numero);
    }
    static fromFechas(fechaInicio, fechaFin) {
        if (!fechaInicio || !fechaFin) throw new Error('Las fechas no pueden ser nulas');
        const inicio = new Date(fechaInicio);
        const fin = new Date(fechaFin);
        if (Number.isNaN(inicio.getTime()) || Number.isNaN(fin.getTime())) throw new Error('Las fechas no son válidas');
        const tiempoTranscurrido = fin.getTime() - inicio.getTime();
        const diasTranscurridos = Math.ceil(tiempoTranscurrido / (1000 * 60 * 60 * 24));
        return new DiasTranscurridos(Math.max(0, diasTranscurridos));
    }
    static cero() { return new DiasTranscurridos(0); }
}

/**
 * EncargadoProceso - Value Object inmutable
 */
class EncargadoProceso {
    constructor(nombre) {
        if (!nombre || typeof nombre !== 'string' || nombre.trim() === '') {
            throw new Error('El nombre del encargado no puede estar vacío');
        }

        const nombreLimpio = nombre.trim();

        Object.defineProperty(this, '_value', {
            value: nombreLimpio,
            writable: false,
            configurable: false,
            enumerable: false
        });
    }

    getNombre() { return this._value; }
    toString() { return this._value; }
    equals(otro) { return otro instanceof EncargadoProceso && this._value.toLowerCase() === otro._value.toLowerCase(); }
    
    getIniciales() {
        const palabras = this._value.trim().split(/\s+/);
        if (palabras.length === 0) return '?';
        if (palabras.length === 1) return palabras[0].substring(0, 2).toUpperCase();
        const iniciales = palabras.slice(0, 3).map(p => p[0].toUpperCase()).join('');
        return iniciales;
    }

    _hashStringToColor() {
        const nombre = this._value.toLowerCase();
        let hash = 0;
        for (let i = 0; i < nombre.length; i++) {
            hash = ((hash << 5) - hash) + (nombre.codePointAt(i) || 0);
            hash = hash & hash;
        }
        const hue = Math.abs(hash) % 360;
        return `hsl(${hue}, 70%, 60%)`;
    }

    _getTextColor() {
        const hslString = this._hashStringToColor();
        const hslMatch = /(\d+),\s*(\d+)%,\s*(\d+)%/.exec(hslString);
        if (!hslMatch) return '#000000';
        const lightness = Number(hslMatch[3]);
        return lightness > 50 ? '#000000' : '#ffffff';
    }

    getAvatarUrl() {
        const iniciales = this.getIniciales();
        const params = new URLSearchParams({
            name: iniciales,
            size: 40,
            background: this._hashStringToColor(),
            color: this._getTextColor(),
            rounded: true,
            font_size: 0.4,
            bold: true
        });
        return `https://ui-avatars.com/api/?${params.toString()}`;
    }

    static from(nombre) {
        if (!nombre || typeof nombre !== 'string' || nombre.trim() === '') {
            throw new Error('El nombre del encargado no puede estar vacío');
        }
        return new EncargadoProceso(nombre);
    }

    static tryFrom(nombre) {
        if (!nombre || typeof nombre !== 'string' || nombre.trim() === '') return null;
        try { 
            return new EncargadoProceso(nombre); 
        } catch (error) { 
            console.error('Error al crear EncargadoProceso desde tryFrom:', error);
            return null; 
        }
    }

    static isEncargadoValido(valor) {
        return valor && typeof valor === 'string' && valor.trim() !== '';
    }
}

// ========================================
// 2. API CLIENT - INFRASTRUCTURE LAYER
// ========================================

/**
 * ReciboCosturaAPI - Cliente HTTP
 */
class ReciboCosturaAPI {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl || '/api';
        this.timeout = 30000;
        this._filterOptionsCache = null;
        this._filterOptionsCacheExpires = 0;
    }

    _buildQueryString(params) {
        const query = new URLSearchParams();
        for (const [key, value] of Object.entries(params)) {
            if (value !== null && value !== undefined && value !== '') query.append(key, value);
        }
        return query.toString();
    }

    async _fetch(url, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            return await response.json();
        } catch (error) {
            if (error.name === 'AbortError') throw new Error('La solicitud excedió el tiempo límite');
            throw error;
        } finally {
            clearTimeout(timeoutId);
        }
    }

    async getRecibos(filtros = {}) {
        const params = { page: filtros.page || 1, per_page: filtros.per_page || 25, ...filtros };
        const queryString = this._buildQueryString(params);
        const url = `${this.baseUrl}/recibos-costura${queryString ? '?' + queryString : ''}`;
        try {
            return await this._fetch(url);
        } catch (error) {
            console.error('Error en getRecibos:', error);
            throw error;
        }
    }

    async getFilterOptions() {
        if (this._filterOptionsCache && Date.now() < this._filterOptionsCacheExpires) {
            return this._filterOptionsCache;
        }

        const url = `${this.baseUrl}/recibos-costura/filter-options`;
        try {
            const respuesta = await this._fetch(url);
            this._filterOptionsCache = respuesta;
            this._filterOptionsCacheExpires = Date.now() + (60 * 60 * 1000);
            return respuesta;
        } catch (error) {
            console.error('Error en getFilterOptions:', error);
            throw error;
        }
    }

    clearFilterOptionsCache() {
        this._filterOptionsCache = null;
        this._filterOptionsCacheExpires = 0;
    }

    async getRecibo(id) {
        const url = `${this.baseUrl}/recibos-costura/${id}`;
        try {
            return await this._fetch(url);
        } catch (error) {
            console.error('Error en getRecibo:', error);
            throw error;
        }
    }
}

// ========================================
// 3. STATE MANAGER - INFRASTRUCTURE LAYER
// ========================================

/**
 * RecibosState - Singleton con patrón Observable
 */
class RecibosState {
    static #instance = null;

    constructor() {
        this._state = {
            recibos: [],
            paginacion: { current_page: 1, last_page: 1, per_page: 25, total: 0, from: 0, to: 0 },
            filtrosActivos: { numero_recibo: '', estado: '', area: '', cliente: '', dia_entrega: '' },
            opcionesFiltro: { estados: [], areas: [], numeros_recibo: [], clientes: [], dias_entrega: [] },
            reciboSeleccionado: null,
            detallesRecibo: null,
            recibosSeen: new Set(),
            loading: false,
            error: null,
            successMessage: null,
            modalAgregarProceso: { abierto: false, pedidoId: null, prendaId: null, datos: null },
            modalSeguimiento: { abierto: false, numeroPedido: null },
            modalNovedades: { abierto: false, numeroPedido: null, numeroRecibo: null }
        };

        this._subscribers = new Map();
        this._errorTimeoutId = null;
        this._successTimeoutId = null;
    }

    static getInstance() {
        if (!RecibosState.#instance) {
            RecibosState.#instance = new RecibosState();
        }
        return RecibosState.#instance;
    }

    getState() { return structuredClone(this._state); }

    get(ruta) {
        const partes = ruta.split('.');
        let valor = this._state;
        for (const parte of partes) {
            if (valor && typeof valor === 'object' && parte in valor) {
                valor = valor[parte];
            } else {
                return undefined;
            }
        }
        return valor;
    }

    set(ruta, valor) {
        const partes = ruta.split('.');
        const ultimaClave = partes.pop();
        let obj = this._state;
        for (const parte of partes) {
            if (!(parte in obj)) obj[parte] = {};
            obj = obj[parte];
        }

        if (obj[ultimaClave] === valor) return;

        obj[ultimaClave] = valor;
        this._notify(ruta);
    }

    setMultiple(actualizaciones) {
        const rutasNotificadas = new Set();
        for (const [ruta, valor] of Object.entries(actualizaciones)) {
            const partes = ruta.split('.');
            const ultimaClave = partes.pop();
            let obj = this._state;
            for (const parte of partes) {
                if (!(parte in obj)) obj[parte] = {};
                obj = obj[parte];
            }
            if (obj[ultimaClave] !== valor) {
                obj[ultimaClave] = valor;
                rutasNotificadas.add(ruta);
            }
        }
        for (const ruta of rutasNotificadas) {
            this._notify(ruta);
        }
    }

    subscribe(ruta, callback) {
        if (!this._subscribers.has(ruta)) {
            this._subscribers.set(ruta, new Set());
        }
        this._subscribers.get(ruta).add(callback);
        return () => {
            const subscribers = this._subscribers.get(ruta);
            if (subscribers) {
                subscribers.delete(callback);
                if (subscribers.size === 0) {
                    this._subscribers.delete(ruta);
                }
            }
        };
    }

    _notifySubscribers(ruta) {
        if (!this._subscribers.has(ruta)) return;
        const valor = this.get(ruta);
        for (const callback of this._subscribers.get(ruta)) {
            try {
                callback(valor);
            } catch (error) {
                console.error(`Error en subscriber de "${ruta}":`, error);
            }
        }
    }

    _notify(ruta) {
        this._notifySubscribers(ruta);
        const partes = ruta.split('.');
        while (partes.length > 1) {
            partes.pop();
            this._notifySubscribers(partes.join('.'));
        }
    }

    setRecibos(recibos) { this.set('recibos', recibos); }
    setPaginacion(paginacion) { this.set('paginacion', paginacion); }
    setLoading(isLoading) { this.set('loading', isLoading); }
    
    setError(mensaje) {
        if(this._errorTimeoutId) clearTimeout(this._errorTimeoutId);
        this.set('error', mensaje);
        if (mensaje) {
            this._errorTimeoutId = setTimeout(() => { this.set('error', null); }, 5000);
        }
    }

    setSuccess(mensaje) {
        if(this._successTimeoutId) clearTimeout(this._successTimeoutId);
        this.set('successMessage', mensaje);
        if (mensaje) {
            this._successTimeoutId = setTimeout(() => { this.set('successMessage', null); }, 3000);
        }
    }

    setFiltrosActivos(filtros) { this.set('filtrosActivos', { ...this.get('filtrosActivos'), ...filtros }); }
    setOpcionesFiltro(opciones) { this.set('opcionesFiltro', opciones); }
    limpiarFiltros() { this.setFiltrosActivos({ numero_recibo: '', estado: '', area: '', cliente: '', dia_entrega: '' }); }

    getFiltrosParaAPI() {
        const filtros = this.get('filtrosActivos');
        const filtrosActivos = {};
        for (const [clave, valor] of Object.entries(filtros)) {
            if (valor !== '' && valor !== null && valor !== undefined) {
                filtrosActivos[clave] = valor;
            }
        }
        return filtrosActivos;
    }

    abrirModalSeguimiento(numeroPedido) { this.set('modalSeguimiento', { abierto: true, numeroPedido }); }
    cerrarModalSeguimiento() { this.set('modalSeguimiento', { abierto: false, numeroPedido: null }); }
}

// ========================================
// 4. TABLE CONTROLLER - PRESENTATION LAYER
// ========================================

/**
 * RecibosTableController - Orquestador de tabla
 */
class RecibosTableController {
    constructor() {
        this.api = new ReciboCosturaAPI();
        this.state = RecibosState.getInstance();
        this.initialized = false;
        this.elements = {
            tbody: null,
            paginationContainer: null,
            filterContainer: null,
            loadingSpinner: null,
            errorAlert: null,
            successAlert: null
        };
        this.unsubscribers = [];
    }

    async init(options = {}) {
        try {
            this.elements.tbody = options.tbody || document.querySelector('tbody');
            this.elements.paginationContainer = options.paginationContainer || document.querySelector('.pagination-container');
            this.elements.loadingSpinner = options.loadingSpinner || document.querySelector('.loading-spinner');
            this.elements.errorAlert = options.errorAlert || document.querySelector('.alert-danger');
            this.elements.successAlert = options.successAlert || document.querySelector('.alert-success');

            if (!this.elements.tbody) throw new Error('No se encontró elemento tbody en el DOM');

            this.state.setLoading(true);
            const opcionesFiltro = await this.api.getFilterOptions();
            this.state.setOpcionesFiltro(opcionesFiltro);

            await this.cargarRecibos();
            this._subscribirACambiosDeEstado();
            this.initialized = true;
            
            console.log(' RecibosTableController inicializado');
        } catch (error) {
            this.state.setError(`Error al inicializar: ${error.message}`);
            console.error('Error en init:', error);
            throw error;
        }
    }

    async cargarRecibos(pagina = 1) {
        try {
            this.state.setLoading(true);
            this.state.setError(null);

            const filtros = this.state.getFiltrosParaAPI();
            const respuesta = await this.api.getRecibos({ ...filtros, page: pagina });

            this.state.setRecibos(respuesta.data || []);
            this.state.setPaginacion(respuesta.pagination || {});
            this.state.setLoading(false);
        } catch (error) {
            this.state.setError(`Error al cargar recibos: ${error.message}`);
            this.state.setLoading(false);
            console.error('Error en cargarRecibos:', error);
        }
    }

    async aplicarFiltros(filtrosNuevos) {
        try {
            this.state.setFiltrosActivos(filtrosNuevos);
            await this.cargarRecibos(1);
        } catch (error) {
            console.error('Error al aplicar filtros:', error);
        }
    }

    async limpiarFiltros() {
        try {
            this.state.limpiarFiltros();
            await this.cargarRecibos(1);
            this.state.setSuccess('Filtros eliminados');
        } catch (error) {
            console.error('Error al limpiar filtros:', error);
        }
    }

    async irAPageina(numeroPagina) {
        try {
            await this.cargarRecibos(numeroPagina);
        } catch (error) {
            console.error('Error al ir a página:', error);
        }
    }

    _subscribirACambiosDeEstado() {
        const unsub1 = this.state.subscribe('recibos', (recibos) => {
            this.renderizarTabla(recibos);
        });

        const unsub2 = this.state.subscribe('paginacion', (paginacion) => {
            this.renderizarPaginacion(paginacion);
        });

        const unsub3 = this.state.subscribe('error', (error) => {
            if (this.elements.errorAlert) {
                if (error) {
                    this.elements.errorAlert.textContent = error;
                    this.elements.errorAlert.classList.remove('d-none');
                } else {
                    this.elements.errorAlert.classList.add('d-none');
                }
            }
        });

        const unsub4 = this.state.subscribe('loading', (loading) => {
            if (this.elements.loadingSpinner) {
                loading 
                    ? this.elements.loadingSpinner.classList.remove('d-none')
                    : this.elements.loadingSpinner.classList.add('d-none');
            }
        });

        this.unsubscribers = [unsub1, unsub2, unsub3, unsub4];
    }

    renderizarTabla(recibos) {
        if (!this.elements.tbody) return;

        this.elements.tbody.innerHTML = '';

        if (!recibos || recibos.length === 0) {
            const fila = document.createElement('tr');
            fila.innerHTML = '<td colspan="12" class="text-center py-3 text-muted">No se encontraron recibos</td>';
            this.elements.tbody.appendChild(fila);
            return;
        }

        for (const recibo of recibos) {
            const fila = this._renderFila(recibo);
            this.elements.tbody.appendChild(fila);
        }
    }

    _renderFila(recibo) {
        const fila = document.createElement('tr');
        fila.dataset.reciboId = recibo.id;

        const estado = new EstadoRecibo(recibo.estado || 'No iniciado');
        const area = new AreaRecibo(recibo.area || 'Costura');
        const diasDesdeCreacion = Number.parseInt(recibo.dias_desde_creacion, 10);
        const dias = Number.isInteger(diasDesdeCreacion) && diasDesdeCreacion >= 0
            ? DiasTranscurridos.from(diasDesdeCreacion)
            : DiasTranscurridos.fromFechas(recibo.fecha_creacion, new Date());
        const encargado = EncargadoProceso.tryFrom(recibo.encargado_proceso);

        fila.innerHTML = `
            <td><span class="badge" style="background-color: ${estado.getColorHex()}"><i class="fa ${estado.getIcon()}"></i> ${estado.toString()}</span></td>
            <td><span class="badge" style="background-color: ${area.getColorHex()}"><i class="fa ${area.getIcon()}"></i> ${area.toString()}</span></td>
            <td><span class="badge bg-${dias.getColorBadge()}"><i class="fa ${dias.getIcon()}"></i> ${dias.toString()}</span></td>
            <td>${recibo.numero_recibo || 'N/A'}</td>
            <td>${recibo.cliente || 'N/A'}</td>
            <td>${recibo.cantidad || 0}</td>
            <td>${encargado ? `<img src="${encargado.getAvatarUrl()}" alt="${encargado.getNombre()}" style="width:30px;border-radius:50%;" title="${encargado.getNombre()}">` : 'N/A'}</td>
        `;
        return fila;
    }

    renderizarPaginacion(paginacion) {
        if (!this.elements.paginationContainer || paginacion.last_page <= 1) return;

        const nav = document.createElement('nav');
        const ul = document.createElement('ul');
        ul.className = 'pagination';

        if (paginacion.current_page > 1) {
            const li = document.createElement('li');
            li.className = 'page-item';
            li.innerHTML = `<a class="page-link" href="#" data-page="${paginacion.current_page - 1}">&laquo; Anterior</a>`;
            ul.appendChild(li);
        }

        for (let i = 1; i <= Math.min(paginacion.last_page, 5); i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === paginacion.current_page ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            ul.appendChild(li);
        }

        if (paginacion.current_page < paginacion.last_page) {
            const li = document.createElement('li');
            li.className = 'page-item';
            li.innerHTML = `<a class="page-link" href="#" data-page="${paginacion.current_page + 1}">Siguiente &raquo;</a>`;
            ul.appendChild(li);
        }

        ul.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.irAPageina(Number(link.dataset.page));
            });
        });

        nav.appendChild(ul);
        this.elements.paginationContainer.innerHTML = '';
        this.elements.paginationContainer.appendChild(nav);
    }

    destroy() {
        for (const unsub of this.unsubscribers) {
            unsub();
        }
        this.initialized = false;
    }
}

// ========================================
// 5. INITIALIZER
// ========================================

class RecibosCostruaModule {
    state = null;
    api = null;
    tableController = null;
    initialized = false;

    async init() {
        try {
            console.log(' Inicializando módulo de recibos de costura...');

            this.state = RecibosState.getInstance();
            this.api = new ReciboCosturaAPI();
            this.tableController = new RecibosTableController();

            await this.tableController.init({
                tbody: document.querySelector('table tbody'),
                paginationContainer: document.querySelector('.pagination-container'),
                loadingSpinner: document.querySelector('.loading-spinner'),
                errorAlert: document.querySelector('.alert-danger'),
                successAlert: document.querySelector('.alert-success')
            });

            globalThis.recibosCostruaModule = this;
            this.initialized = true;
            
            console.log(' Módulo de recibos de costura inicializado exitosamente');

            const evento = new CustomEvent('recibosCostruaModuleReady', { detail: { module: this } });
            document.dispatchEvent(evento);

        } catch (error) {
            console.error(' Error al inicializar módulo:', error);
            throw error;
        }
    }
}

// Auto-inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // NO inicializar si estamos en vista de bordado/estampado
        if (window.__SKIP_RECIBOS_TABLE_INIT__ === true) {
            console.log('[bundle.js] Saltando inicialización de módulo de costura (vista: bordado/estampado)');
            return;
        }

        const modulo = new RecibosCostruaModule();
        await modulo.init();

        // Agregar event listeners para botones de acción
        setupTableEventListeners();
    } catch (error) {
        console.error('Error en DOMContentLoaded:', error);
    }
});

/**
 * Configurar event listeners para la tabla
 *  COMENTADO: legacy-handlers.js ya maneja esto con mejor logging
 * Mantener solo para referencia en caso de fallback
 */
function setupTableEventListeners() {
    //  Event listener delegado POR LEGACY-HANDLERS.JS - No duplicar aquí
    console.log(' Event listeners configurados para tabla (delegados a legacy-handlers.js)');
    
    
}

/**
 * Crear dropdown dinámico para recibos-costura
 */
function crearDropdownRecibos(button) {
    const { menuId, pedidoId, prendaId, tipoRecibo = 'COSTURA' } = button.dataset;

    // Verificar si ya existe
    let existing = document.getElementById(menuId);
    if (existing) {
        return existing;
    }

    // Crear o obtener contenedor
    let container = document.getElementById('dropdowns-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'dropdowns-container';
        container.style.cssText = 'position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;';
        document.body.appendChild(container);
    }

    const dropdown = document.createElement('div');
    dropdown.id = menuId;
    dropdown.className = 'dropdown-menu-recibos';
    dropdown.style.cssText = `
        position: fixed;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        min-width: 200px;
        z-index: 1000000;
        pointer-events: auto;
        display: none;
    `;

    dropdown.innerHTML = `
        <button class="dropdown-item" onclick="openOrderDetailModalDirect(${pedidoId}, ${prendaId}, '${tipoRecibo}'); closeDropdownRecibos();" style="width: 100%; padding: 12px 16px; border: none; background: none; text-align: left; cursor: pointer; border-bottom: 1px solid #f3f4f6; font-size: 14px; transition: background 0.2s;">
            <i class="fas fa-eye" style="margin-right: 8px;"></i> Ver Detalles
        </button>
        <button class="dropdown-item" onclick="openOrderTrackingDirect(${pedidoId}, ${prendaId}); closeDropdownRecibos();" style="width: 100%; padding: 12px 16px; border: none; background: none; text-align: left; cursor: pointer; border-bottom: 1px solid #f3f4f6; font-size: 14px; transition: background 0.2s;">
            <i class="fas fa-location-arrow" style="margin-right: 8px;"></i> Seguimiento
        </button>
        <button class="dropdown-item" onclick="openNovedadesModal(${pedidoId}, '${tipoRecibo}'); closeDropdownRecibos();" style="width: 100%; padding: 12px 16px; border: none; background: none; text-align: left; cursor: pointer; font-size: 14px; transition: background 0.2s;">
            <i class="fas fa-sticky-note" style="margin-right: 8px;"></i> Novedades
        </button>
    `;

    // Agregar hover effect
    dropdown.addEventListener('mouseover', (e) => {
        if (e.target.closest('.dropdown-item')) {
            e.target.closest('.dropdown-item').style.background = '#f9fafb';
        }
    });

    dropdown.addEventListener('mouseout', (e) => {
        if (e.target.closest('.dropdown-item')) {
            e.target.closest('.dropdown-item').style.background = 'white';
        }
    });

    container.appendChild(dropdown);
    return dropdown;
}

/**
 * Posicionar dropdown cerca del botón
 */
/**
 *  COMENTADA: Usar la versión de legacy-handlers.js 
 * Esta función tenía parámetros incompatibles causando que estilos se aplicaran al botón
 */


/**
 * Cerrar todos los dropdowns
 */
function closeDropdownRecibos() {
    document.querySelectorAll('.dropdown-menu-recibos').forEach(menu => {
        menu.style.display = 'none';
        menu.style.pointerEvents = 'none';
    });
}

/**
 * Abrir modal de detalles directamente
 */
function openOrderDetailModalDirect(pedidoId, prendaId, tipoRecibo) {
    const datos = {
        pedido_id: pedidoId,
        prenda_id: prendaId,
        tipo_recibo: tipoRecibo,
        es_parcial: false,
        pedido_parcial_id: null
    };
    
    openOrderDetailModal(datos);
}

/**
 * Abrir modal de seguimiento directamente
 */
function openOrderTrackingDirect(pedidoId, prendaId) {
    console.log(' Abriendo seguimiento para pedido:', pedidoId, 'prenda:', prendaId);
    
    // Si existe la función global de seguimiento, usarla
    if (typeof abrirModalSeguimientoDirecto === 'function') {
        abrirModalSeguimientoDirecto(pedidoId, prendaId);
    } else if (typeof openOrderTracking === 'function') {
        openOrderTracking(pedidoId, prendaId);
    } else {
        console.warn('No se encontró función para abrir seguimiento');
    }
}

/**
 * Abrir modal de novedades
 */
function openNovedadesModal(pedidoId, tipoRecibo) {
    console.log(' Abriendo novedades para pedido:', pedidoId);
    
    if (typeof openNovedadesEditModal === 'function') {
        openNovedadesEditModal(pedidoId, tipoRecibo);
    } else {
        console.warn('No se encontró función para abrir novedades');
    }
}

/**
 * Abrir modal de detalles del recibo
 */
function openOrderDetailModal(datos) {
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    const modalOverlay = document.getElementById('modal-overlay');

    if (!modalWrapper || !modalOverlay) {
        console.error(' No se encontraron elementos del modal');
        return;
    }

    const pedidoId = datos.pedido_id;
    const prendaId = datos.prenda_id;

    console.log(` Cargando datos para abrir recibo - Pedido: ${pedidoId}, Prenda: ${prendaId}`);

    // Hacer fetch de los datos del recibo
    fetch(`/registros/${pedidoId}/recibos-datos`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(responseData => {
            // Procesar respuesta
            let datosRecibo = responseData.data || responseData;
            
            if (!datosRecibo.prendas || !Array.isArray(datosRecibo.prendas)) {
                throw new Error('No se encontraron prendas en la respuesta');
            }

            // Usar la prenda especificada o la primera
            let prendaSeleccionada = prendaId ? 
                datosRecibo.prendas.find(p => p.id == prendaId) : 
                datosRecibo.prendas[0];

            if (!prendaSeleccionada) {
                prendaSeleccionada = datosRecibo.prendas[0];
            }

            console.log(` Datos cargados - Pedido: ${pedidoId}, Prenda seleccionada:`, prendaSeleccionada.id);

            // Guardar datos en el DOM
            modalWrapper.dataset.pedidoId = pedidoId;
            modalWrapper.dataset.prendaId = prendaSeleccionada.id;
            modalWrapper.dataset.tipoRecibo = datos.tipo_recibo || 'COSTURA';

            // Mostrar modales
            modalOverlay.style.display = 'block';
            modalWrapper.style.display = 'block';

            console.log('🔓 Modal abierto con datos:', datos);

            // Usar el módulo pedidosRecibosModule si está disponible
            if (globalThis.pedidosRecibosModule !== undefined && 
                typeof globalThis.pedidosRecibosModule.abrirRecibo === 'function') {
                console.log(` Delegando a pedidosRecibosModule.abrirRecibo(${pedidoId}, ${prendaSeleccionada.id}, 'costura')`);
                globalThis.pedidosRecibosModule.abrirRecibo(pedidoId, prendaSeleccionada.id, 'costura');
            } else {
                console.warn(' módulo pedidosRecibosModule no disponible');
            }

            // Disparar evento personalizado
            const evento = new CustomEvent('orderDetailModalOpened', { detail: datos });
            document.dispatchEvent(evento);
        })
        .catch(error => {
            console.error(' Error al cargar datos del recibo:', error);
            alert('Error al cargar los datos del recibo: ' + error.message);
            
            // Cerrar modal si hay error
            modalOverlay.style.display = 'none';
            modalWrapper.style.display = 'none';
        });
}

/**
 * Cerrar modal de detalles (función global)
 */
globalThis.closeModalOverlay = function() {
    console.log(' Cerrando modal de detalles...');
    
    // Delegar al módulo de pedidos si está disponible (limpia estado, galería, botones)
    if (globalThis.pedidosRecibosModule !== undefined && 
        typeof globalThis.pedidosRecibosModule.cerrarRecibo === 'function') {
        console.log(' Delegando cierre a pedidosRecibosModule.cerrarRecibo()');
        globalThis.pedidosRecibosModule.cerrarRecibo();
    }

    // Cerrar overlay y wrapper
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    const modalOverlay = document.getElementById('modal-overlay');

    if (modalWrapper) {
        modalWrapper.style.display = 'none';
    }
    if (modalOverlay) {
        modalOverlay.style.display = 'none';
    }

    // Limpiar elementos residuales (por si acaso)
    const galeria = document.getElementById('galeria-modal-costura');
    if (galeria) {
        galeria.remove();
    }
    const btnCerrarInsumos = document.getElementById('btn-cerrar-modal-insumos');
    if (btnCerrarInsumos) {
        btnCerrarInsumos.remove();
    }
    const btnCerrarDinamico = document.getElementById('btn-cerrar-modal-dinamico');
    if (btnCerrarDinamico) {
        btnCerrarDinamico.remove();
    }
    const floatingContainer = document.getElementById('floating-buttons-container');
    if (floatingContainer) {
        floatingContainer.style.display = 'none';
    }

    console.log(' Modal cerrado completamente');
};
