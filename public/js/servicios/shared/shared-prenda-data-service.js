/**
 * 🔒 SharedPrendaDataService
 * 
 * IMPORTANTE: AISLADO DE COTIZACIONES
 * - Solo maneja datos de PRENDAS (tabla prendas_pedido)
 * - NO toca endpoints de cotización
 * - NO interfiere con cotizaciones
 */

class SharedPrendaDataService {
    // Constantes de endpoints permitidos
    static ENDPOINTS_PERMITIDOS = [
        '/api/prendas',
        '/api/prendas/',
        '/api/storage/prendas',
        '/api/storage/prendas/'
    ];

    // Constantes de endpoints PROHIBIDOS
    static ENDPOINTS_PROHIBIDOS = [
        '/api/cotizaciones',
        '/api/cotizaciones/',
        '/api/cotizaciones/prendas',
        '/storage/cotizaciones',
        '/storage/cotizaciones/'
    ];

    constructor(config = {}) {
        // IMPORTANTE: Validar endpoint al inicializar
        const endpoint = config.apiBaseUrl || '/api/prendas';
        this._validarEndpointPermitido(endpoint);

        // Configuración
        this.apiBaseUrl = endpoint;
        this.cacheEnabled = config.cacheEnabled !== false;
        this.cacheTTL = config.cacheTTL || 5 * 60 * 1000; // 5 minutos

        // Cache local
        this.cache = new Map();
        this.cacheTimestamps = new Map();

        // Detector de formato
        this.formatDetector = config.formatDetector || new FormatDetector();

        console.log('[SharedPrendaDataService] ✓ Inicializado');
        console.log('[SharedPrendaDataService] 🔐 Endpoint validado:', this.apiBaseUrl);
    }

    /**
     * VALIDAR endpoint permitido
     * Previene acceso a cotizaciones
     */
    _validarEndpointPermitido(endpoint) {
        // Verificar que NO sea endpoint de cotización
        const esProhibido = SharedPrendaDataService.ENDPOINTS_PROHIBIDOS.some(ep => 
            endpoint.includes(ep)
        );

        if (esProhibido) {
            const msg = `🚨 VIOLACIÓN DE AISLAMIENTO: Intento de usar endpoint prohibido: ${endpoint}`;
            console.error(msg);
            throw new Error(msg);
        }

        // Advertencia si endpoint es inusual (pero permitido)
        const esPermitido = SharedPrendaDataService.ENDPOINTS_PERMITIDOS.some(ep => 
            endpoint.includes(ep)
        );

        if (!esPermitido) {
            console.warn(`⚠️ [SharedPrendaDataService] Endpoint inusual (pero permitido): ${endpoint}`);
        }
    }

    /**
     * OBTENER prenda por ID
     * Cargar desde BD con normalización automática
     */
    async obtenerPrendPorId(prendaId) {
        console.log(`[SharedPrendaData] 🔍 Obteniendo prenda ${prendaId}...`);

        try {
            // 1️⃣ Verificar cache
            if (this.cacheEnabled && this.cache.has(prendaId)) {
                const cached = this.cache.get(prendaId);
                const age = Date.now() - this.cacheTimestamps.get(prendaId);

                if (age < this.cacheTTL) {
                    console.log(`[SharedPrendaData] ✓ Cache hit para prenda ${prendaId}`);
                    return cached;
                } else {
                    // Cache expirado
                    this.cache.delete(prendaId);
                    this.cacheTimestamps.delete(prendaId);
                }
            }

            // 2️⃣ Fetch desde API
            const response = await fetch(`${this.apiBaseUrl}/${prendaId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const resultado = await response.json();

            if (!resultado.success || !resultado.data) {
                throw new Error(resultado.message || 'Sin datos en respuesta');
            }

            // 3️⃣ Normalizar a formato estándar
            const prendaNormalizada = this.normalizarDesdeAPI(resultado.data);

            // 4️⃣ Cachear
            if (this.cacheEnabled) {
                this.cache.set(prendaId, prendaNormalizada);
                this.cacheTimestamps.set(prendaId, Date.now());
            }

            console.log(`[SharedPrendaData] ✓ Prenda cargada: ${prendaNormalizada.nombre}`);
            return prendaNormalizada;

        } catch (error) {
            console.error('[SharedPrendaData] ❌ Error obteniendo prenda:', error);
            throw error;
        }
    }

    /**
     * CREAR o ACTUALIZAR prenda
     */
    async guardarPrenda(prendaData) {
        console.log('[SharedPrendaData] 💾 Guardando prenda...');

        try {
            // 🔐 VALIDACIÓN DE AISLAMIENTO
            // No permitir guardar datos que referencien cotizaciones
            if (prendaData.cotizacion_id !== undefined && prendaData.cotizacion_id !== null) {
                console.warn('⚠️ [SharedPrendaData] Detectado cotizacion_id en datos, será limpiado según contexto');
                
                // Si es crear-desde-cotizacion, guardar como referencia histórica
                if (prendaData.contexto === 'crear-desde-cotizacion') {
                    // Renombrar para no ser "dato principal"
                    prendaData.copiada_desde_cotizacion_id = prendaData.cotizacion_id;
                }
                
                // Limpiar ID de cotización principal
                delete prendaData.cotizacion_id;
            }

            // Validar que tabla_origen NO es cotizaciones
            if (prendaData.tabla_origen === 'cotizaciones') {
                throw new Error('🚨 VIOLACIÓN: Intente de guardar en tabla de cotizaciones');
            }

            const esActualizacion = !!prendaData.id;
            const metodo = esActualizacion ? 'PATCH' : 'POST';
            const endpoint = esActualizacion
                ? `${this.apiBaseUrl}/${prendaData.id}`
                : this.apiBaseUrl;

            console.log(`[SharedPrendaData] ${metodo} ${endpoint}`);

            const response = await fetch(endpoint, {
                method: metodo,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(prendaData)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const resultado = await response.json();

            if (!resultado.success || !resultado.data) {
                throw new Error(resultado.message || 'Error guardando prenda');
            }

            // Normalizar respuesta
            const prendaGuardada = this.normalizarDesdeAPI(resultado.data);

            // Limpiar cache si fue actualización
            if (esActualizacion) {
                this.cache.delete(prendaData.id);
                this.cacheTimestamps.delete(prendaData.id);
            }

            console.log('[SharedPrendaData] ✓ Prenda guardada:', prendaGuardada.nombre);
            return prendaGuardada;

        } catch (error) {
            console.error('[SharedPrendaData] ❌ Error guardando:', error);
            throw error;
        }
    }

    /**
     * ELIMINAR prenda
     */
    async eliminarPrenda(prendaId) {
        console.log(`[SharedPrendaData] 🗑️ Eliminando prenda ${prendaId}...`);

        try {
            const response = await fetch(`${this.apiBaseUrl}/${prendaId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Limpiar cache
            this.cache.delete(prendaId);
            this.cacheTimestamps.delete(prendaId);

            console.log('[SharedPrendaData] ✓ Prenda eliminada');
            return true;

        } catch (error) {
            console.error('[SharedPrendaData] ❌ Error eliminando:', error);
            throw error;
        }
    }

    /**
     * NORMALIZAR datos desde API a formato estándar
     * 
     * Detecta automáticamente si viene en formato ANTIGUO o NUEVO
     * y transforma a estructura coherente
     */
    normalizarDesdeAPI(datos) {
        console.log('[SharedPrendaData] 🔄 Normalizando datos...');

        // Detectar formato
        const formato = this.formatDetector.detectar(datos);
        console.log('[SharedPrendaData] Formato detectado:', formato);

        let normalizado;

        if (formato === 'NUEVO') {
            normalizado = this.transformarDesdeNuevo(datos);
        } else if (formato === 'ANTIGUO') {
            normalizado = this.transformarDesdeAntiguo(datos);
        } else {
            console.warn('[SharedPrendaData] ⚠️ Formato no reconocido, usando defaults');
            normalizado = this.crearPrendaDefecto();
        }

        return normalizado;
    }

    /**
     * Transformar desde formato NUEVO (DDD)
     */
    transformarDesdeNuevo(datos) {
        return {
            id: datos.id,
            nombre: datos.nombre_prenda || datos.nombre || '',
            descripcion: datos.descripcion || '',
            origen: datos.origen || 'confeccion',
            de_bodega: datos.de_bodega,

            // Tallas
            tallas: datos.tallas || [],
            generosConTallas: datos.generosConTallas || {},

            // Telas
            telas: datos.telas_array || datos.telas || [],
            telasCotizacion: datos.telas_array || [],

            // Procesos
            procesos: (datos.procesos || []).map(p => ({
                id: p.id,
                nombre: p.nombre || p.tipo,
                tipo: p.tipo,
                observaciones: p.observaciones,
                ubicaciones: p.ubicaciones,
                imagenes: (p.imagenes || []).map(img => ({
                    id: img.id,
                    url: img.ruta_webp || img.ruta_original || img.url
                }))
            })),

            // Imágenes
            imagenes: (datos.fotos || datos.imagenes || []).map(img => ({
                id: img.id,
                url: img.ruta_webp || img.ruta_original || img.url,
                ruta_original: img.ruta_original,
                ruta_webp: img.ruta_webp
            })),

            // Variantes
            variantes: datos.variantes || {}
        };
    }

    /**
     * Transformar desde formato ANTIGUO
     */
    transformarDesdeAntiguo(datos) {
        // Construir tallas desde arrays antiguos
        const tallas = [];
        if (datos.tallas_dama) {
            datos.tallas_dama.forEach(t => {
                tallas.push({
                    genero: 'DAMA',
                    talla: t.talla,
                    cantidad: t.cantidad
                });
            });
        }
        if (datos.tallas_caballero) {
            datos.tallas_caballero.forEach(t => {
                tallas.push({
                    genero: 'CABALLERO',
                    talla: t.talla,
                    cantidad: t.cantidad
                });
            });
        }

        return {
            id: datos.id,
            nombre: datos.nombre_prenda || datos.nombre || '',
            descripcion: datos.descripcion || '',
            origen: datos.origen || 'confeccion',
            de_bodega: datos.de_bodega,

            tallas: tallas,
            generosConTallas: this.construirGenerosConTallas(tallas),

            telas: datos.colores_telas || [],
            telasCotizacion: datos.colores_telas || [],

            procesos: datos.procesos || [],
            imagenes: datos.fotos || datos.imagenes || [],
            variantes: datos.variantes || {}
        };
    }

    /**
     * Construir generosConTallas desde array plano
     */
    construirGenerosConTallas(tallasArray) {
        const resultado = {};

        tallasArray.forEach(t => {
            if (!resultado[t.genero]) {
                resultado[t.genero] = {};
            }
            resultado[t.genero][t.talla] = t.cantidad;
        });

        return resultado;
    }

    /**
     * Crear estructura vacía
     */
    crearPrendaDefecto() {
        return {
            id: null,
            nombre: '',
            descripcion: '',
            origen: 'confeccion',
            de_bodega: false,
            tallas: [],
            generosConTallas: {},
            telas: [],
            telasCotizacion: [],
            procesos: [],
            imagenes: [],
            variantes: {}
        };
    }

    /**
     * Limpiar cache
     */
    limpiarCache() {
        this.cache.clear();
        this.cacheTimestamps.clear();
        console.log('[SharedPrendaData] 🗑️ Cache limpiado');
    }

    /**
     * Obtener estadísticas de cache
     */
    getEstadisticasCache() {
        return {
            items: this.cache.size,
            enabled: this.cacheEnabled,
            ttl: this.cacheTTL
        };
    }
}

// Exportar
window.SharedPrendaDataService = SharedPrendaDataService;
console.log('[SharedPrendaDataService] 🔐 Cargado (AISLADO DE COTIZACIONES)');
