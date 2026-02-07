/**
 * PrendaEditorService - Servicio de lógica de negocio
 * 
 * Propósito: Contener toda la lógica de negocio sin dependencias del DOM
 * Responsabilidades:
 * - Transformación de datos
 * - Validación
 * - Orquestación de operaciones
 * - Emisión de eventos
 */
class PrendaEditorService {
    constructor(opciones = {}) {
        this.api = opciones.api;
        this.eventBus = opciones.eventBus;
        this.prendaActual = null;
        this.cotizacionActual = null;
        this.prendaEditIndex = null;
        
        // Almacenamiento de datos procesados
        this.telasAgregadas = [];
        this.procesosSeleccionados = {};
        this.tallasRelacionales = {
            DAMA: {},
            CABALLERO: {},
            UNISEX: {}
        };
        this.variacionesActuales = {};
    }

    /**
     * Aplicar origen automático desde cotización
     * FUERZA origen = 'bodega' si cotización es Reflectivo o Logo
     */
    aplicarOrigenAutomaticoDesdeCotizacion(prenda) {
        if (!this.cotizacionActual) {
            return prenda;
        }

        const cotizacion = this.cotizacionActual;
        const tipoCotizacionId = cotizacion.tipo_cotizacion_id;
        
        // Obtener nombre del tipo de cotización
        let nombreTipo = null;
        if (cotizacion.tipo_cotizacion?.nombre) {
            nombreTipo = cotizacion.tipo_cotizacion.nombre;
        } else if (cotizacion.tipo_nombre) {
            nombreTipo = cotizacion.tipo_nombre;
        }

        // Si es Reflectivo o Logo → FORZAR bodega
        const esReflectivo = (nombreTipo && nombreTipo.toLowerCase() === 'reflectivo') || 
                           tipoCotizacionId === 'Reflectivo' || 
                           tipoCotizacionId === 4;
        
        const esLogo = (nombreTipo && nombreTipo.toLowerCase() === 'logo') || 
                      tipoCotizacionId === 'Logo' || 
                      tipoCotizacionId === 3;

        if (esReflectivo || esLogo) {
            prenda.origen = 'bodega';
        } else {
            prenda.origen = prenda.origen || 'confeccion';
        }

        return prenda;
    }

    /**
     * Cargar telas desde cotización
     */
    async cargarTelasDesdeCotizacion(prenda) {
        if (!prenda.prenda_id || !prenda.cotizacion_id) {
            return;
        }

        try {
            const datos = await this.api.cargarTelasDesdeCotizacion(
                prenda.cotizacion_id,
                prenda.prenda_id
            );

            const telas = datos.telas || datos.data?.telas || [];
            const variaciones = datos.variaciones || datos.data?.variaciones || [];
            const ubicaciones = datos.ubicaciones || datos.data?.ubicaciones || [];
            const descripcion = datos.descripcion || datos.data?.descripcion || '';

            if (telas.length > 0) {
                const telasAgregadas = telas.map(tela => ({
                    nombre_tela: tela.nombre_tela || tela.tela?.nombre || 'N/A',
                    color: tela.color || 'N/A',
                    referencia: tela.referencia || tela.tela?.referencia || 'N/A',
                    fotos: this.procesarFotosTela(tela.fotos || []),
                    origen: 'cotizacion'
                }));

                prenda.telasAgregadas = telasAgregadas;
                this.telasAgregadas = telasAgregadas;
                this.eventBus?.emit(PrendaEventBus.EVENTOS.TELAS_DESDE_COTIZACION, telasAgregadas);
            }

            if (variaciones && variaciones.length > 0) {
                prenda.variacionesReflectivo = variaciones;
                this.eventBus?.emit(PrendaEventBus.EVENTOS.VARIACIONES_CARGADAS, variaciones);
            }

            if (ubicaciones && ubicaciones.length > 0) {
                prenda.ubicacionesReflectivo = ubicaciones;
            }

            if (descripcion) {
                prenda.descripcionReflectivo = descripcion;
            }

            this.eventBus?.emit(PrendaEventBus.EVENTOS.COTIZACION_DATOS_CARGADOS, {
                telas: telas.length,
                variaciones: variaciones.length,
                ubicaciones: ubicaciones.length
            });

        } catch (error) {
            console.error('[PrendaEditorService] Error cargando telas de cotización:', error);
            this.eventBus?.emit(PrendaEventBus.EVENTOS.ERROR_OCURRIDO, {
                mensaje: error.message,
                contexto: 'cargarTelasDesdeCotizacion'
            });
        }
    }

    /**
     * Procesar fotos de tela
     * @private
     */
    procesarFotosTela(fotos) {
        if (!Array.isArray(fotos)) return [];
        
        return fotos.map(foto => {
            if (typeof foto === 'string') return foto;
            return foto.url || foto.ruta || foto.ruta_webp || '';
        }).filter(url => url);
    }

    /**
     * Cargar tallas del backend
     */
    async cargarTallasDesdeBackend(cotizacionId) {
        try {
            // Aquí iría el endpoint específico si existe
            // Por ahora retornamos estructura vacía
            return {
                DAMA: {},
                CABALLERO: {},
                UNISEX: {}
            };
        } catch (error) {
            console.error('[PrendaEditorService] Error cargando tallas:', error);
            return {
                DAMA: {},
                CABALLERO: {},
                UNISEX: {}
            };
        }
    }

    /**
     * Procesar tallas desde diferentes formatos
     */
    procesarTallasDesProcesos(procesos) {
        const tallas = {
            DAMA: {},
            CABALLERO: {},
            UNISEX: {}
        };

        if (!procesos) return tallas;

        const procesosArray = Array.isArray(procesos) ? procesos : Object.values(procesos);
        
        procesosArray.forEach(proceso => {
            if (proceso.talla_cantidad) {
                // Formato: { 'DAMA': { 'S': 10, 'M': 20 } }
                if (typeof proceso.talla_cantidad === 'object') {
                    Object.entries(proceso.talla_cantidad).forEach(([talla, cantidad]) => {
                        if (talla.toUpperCase() === 'DAMA' || talla.toUpperCase() === 'CABALLERO' || talla.toUpperCase() === 'UNISEX') {
                            tallas[talla.toUpperCase()] = proceso.talla_cantidad[Object.keys(proceso.talla_cantidad)[0]] || {};
                        }
                    });
                }
            }
        });

        return tallas;
    }

    /**
     * Normalizar procesos: convierte objeto a array
     */
    normalizarProcesos(procesos) {
        if (!procesos) return [];
        if (Array.isArray(procesos)) return procesos;
        if (typeof procesos === 'object') return Object.values(procesos).filter(p => p);
        return [];
    }

    /**
     * Procesar procesos para almacenamiento
     */
    procesarProcesos(procesos) {
        const procesosNormalizados = this.normalizarProcesos(procesos);
        this.procesosSeleccionados = {};

        procesosNormalizados.forEach((proceso, idx) => {
            if (proceso) {
                const datosReales = proceso.datos ? proceso.datos : proceso;
                const tipoBackend = datosReales.tipo || datosReales.tipo_proceso || datosReales.nombre || `Proceso ${idx}`;
                const slugDirecto = datosReales.slug;
                const tipoProceso = slugDirecto || tipoBackend.toLowerCase().replace(/\s+/g, '-');

                // Procesar ubicaciones
                let ubicacionesFormato = [];
                if (datosReales.ubicaciones) {
                    if (typeof datosReales.ubicaciones === 'string') {
                        try {
                            ubicacionesFormato = JSON.parse(datosReales.ubicaciones);
                        } catch {
                            ubicacionesFormato = [datosReales.ubicaciones];
                        }
                    } else if (Array.isArray(datosReales.ubicaciones)) {
                        ubicacionesFormato = datosReales.ubicaciones;
                    }
                }

                // Convertir tallas
                let tallasFormato = datosReales.tallas || { dama: {}, caballero: {} };
                if (Array.isArray(tallasFormato) && tallasFormato.length === 0) {
                    tallasFormato = { dama: {}, caballero: {} };
                }

                // Procesar imágenes
                const imagenes = (datosReales.imagenes || [])
                    .map(img => {
                        if (typeof img === 'string') return img;
                        if (img.url) return img.url;
                        if (img.ruta) return img.ruta;
                        if (img.ruta_webp) return img.ruta_webp;
                        return null;
                    })
                    .filter(url => url);

                this.procesosSeleccionados[tipoProceso] = {
                    datos: {
                        id: datosReales.id,
                        tipo: tipoProceso,
                        nombre: tipoBackend,
                        nombre_proceso: datosReales.nombre_proceso,
                        tipo_proceso: datosReales.tipo_proceso,
                        tipo_proceso_id: datosReales.tipo_proceso_id,
                        ubicaciones: ubicacionesFormato,
                        observaciones: datosReales.observaciones || '',
                        tallas: tallasFormato,
                        variaciones_prenda: datosReales.variaciones_prenda || {},
                        talla_cantidad: datosReales.talla_cantidad || {},
                        imagenes: imagenes
                    }
                };
            }
        });

        this.eventBus?.emit(PrendaEventBus.EVENTOS.PROCESOS_CARGADOS, this.procesosSeleccionados);
        return this.procesosSeleccionados;
    }

    /**
     * Cargar variaciones de la prenda
     */
    procesarVariaciones(prenda) {
        let variantes = prenda.variantes || {};

        // Si viene vacío pero hay procesos, extraer de procesos
        if ((!variantes || Object.keys(variantes).length === 0) && prenda.procesos) {
            const procesosArray = Array.isArray(prenda.procesos) ? prenda.procesos : Object.values(prenda.procesos);
            if (procesosArray.length > 0 && procesosArray[0].variaciones_prenda) {
                variantes = procesosArray[0].variaciones_prenda;
            }
        }

        this.variacionesActuales = variantes;
        this.eventBus?.emit(PrendaEventBus.EVENTOS.VARIACIONES_CARGADAS, variantes);
        
        return variantes;
    }

    /**
     * Validar datos de prenda antes de guardar
     */
    validarPrenda(datosPrenda) {
        const errores = [];

        if (!datosPrenda.nombre_prenda || !datosPrenda.nombre_prenda.trim()) {
            errores.push('El nombre de la prenda es obligatorio');
        }

        if (!datosPrenda.origen) {
            errores.push('Debe seleccionar un origen (bodega/confección)');
        }

        if (this.telasAgregadas.length === 0 && !datosPrenda.cotizacion_id) {
            errores.push('Debe agregar al menos una tela');
        }

        return {
            valido: errores.length === 0,
            errores
        };
    }

    /**
     * Preparar datos de prenda para guardar
     */
    prepararDatosParaGuardar(datosPrenda) {
        return {
            nombre_prenda: datosPrenda.nombre_prenda.trim(),
            descripcion: (datosPrenda.descripcion || '').trim(),
            origen: datosPrenda.origen,
            de_bodega: datosPrenda.origen === 'bodega' ? 1 : 0,
            telasAgregadas: this.telasAgregadas,
            variantes: this.variacionesActuales,
            procesosSeleccionados: this.procesosSeleccionados,
            tallasRelacionales: this.tallasRelacionales,
            cotizacion_id: this.cotizacionActual?.id
        };
    }

    /**
     * Asignar cotización actual
     */
    asignarCotizacion(cotizacion) {
        this.cotizacionActual = cotizacion;
        this.eventBus?.emit(PrendaEventBus.EVENTOS.COTIZACION_ASIGNADA, cotizacion);
    }

    /**
     * Asignar prenda actual
     */
    asignarPrenda(prenda, index = null) {
        this.prendaActual = prenda;
        this.prendaEditIndex = index;
        this.eventBus?.emit(PrendaEventBus.EVENTOS.PRENDA_CARGADA, {
            prenda,
            index
        });
    }

    /**
     * Obtener prenda actual
     */
    obtenerPrendaActual() {
        return this.prendaActual;
    }

    /**
     * Obtener cotización actual
     */
    obtenerCotizacionActual() {
        return this.cotizacionActual;
    }

    /**
     * Detectar si es cotización Reflectivo o Logo
     */
    esCotizacionReflectivoOLogo() {
        if (!this.cotizacionActual) return { reflectivo: false, logo: false };

        const tipoCotizacionId = this.cotizacionActual.tipo_cotizacion_id;
        let nombreTipo = this.cotizacionActual.tipo_cotizacion?.nombre || this.cotizacionActual.tipo_nombre;

        const reflectivo = (nombreTipo && nombreTipo.toLowerCase() === 'reflectivo') || 
                          tipoCotizacionId === 'Reflectivo' || 
                          tipoCotizacionId === 4;
        
        const logo = (nombreTipo && nombreTipo.toLowerCase() === 'logo') || 
                    tipoCotizacionId === 'Logo' || 
                    tipoCotizacionId === 3;

        return { reflectivo, logo };
    }

    /**
     * Cargar múltiples prendas desde cotización
     */
    cargarPrendasDesdeCotizacion(prendas, cotizacion) {
        if (!Array.isArray(prendas) || !cotizacion) {
            return [];
        }

        this.asignarCotizacion(cotizacion);

        const prendasProcesadas = prendas.map((prenda) => {
            return this.aplicarOrigenAutomaticoDesdeCotizacion(prenda);
        });

        this.eventBus?.emit(PrendaEventBus.EVENTOS.COTIZACION_ASIGNADA, {
            cotizacion,
            prendas: prendasProcesadas
        });

        return prendasProcesadas;
    }

    /**
     * Resetear estado
     */
    resetear() {
        this.prendaActual = null;
        this.cotizacionActual = null;
        this.prendaEditIndex = null;
        this.telasAgregadas = [];
        this.procesosSeleccionados = {};
        this.variacionesActuales = {};
        this.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {} };
        
        this.eventBus?.emit(PrendaEventBus.EVENTOS.PRENDA_CERRADA);
    }

    /**
     * Obtener estado actual
     */
    obtenerEstado() {
        return {
            prendaActual: this.prendaActual,
            cotizacionActual: this.cotizacionActual,
            prendaEditIndex: this.prendaEditIndex,
            telasAgregadas: this.telasAgregadas,
            procesosSeleccionados: this.procesosSeleccionados,
            variacionesActuales: this.variacionesActuales,
            tallasRelacionales: this.tallasRelacionales
        };
    }

    /**
     * Enriquecer telas con referencias desde variantes
     * Lógica compleja para Reflectivo/Logo que vienen sin referencias
     * @private
     */
    enriquecerTelasDesdeVariantes(telasAgregadas, variantes) {
        if (!variantes || telasAgregadas.length === 0) {
            return telasAgregadas;
        }

        const telasMejoradas = telasAgregadas.map(tela => {
            // Si ya tiene referencia, no hacer nada
            if (tela.referencia && tela.referencia !== 'N/A' && tela.referencia !== '') {
                return tela;
            }

            // Buscar en variantes.telas_multiples (estructura de Reflectivo/Logo)
            if (variantes.telas_multiples && Array.isArray(variantes.telas_multiples)) {
                const telaVariante = variantes.telas_multiples.find(tv => 
                    tv.nombre_tela?.toLowerCase() === tela.nombre_tela?.toLowerCase()
                );

                if (telaVariante && telaVariante.referencia) {
                    return {
                        ...tela,
                        referencia: telaVariante.referencia,
                        origen: 'enriquecida-desde-variantes'
                    };
                }
            }

            return tela;
        });

        return telasMejoradas;
    }

    /**
     * Procesar ubicaciones desde diferentes formatos
     * Maneja: array, string JSON, string directo
     * @private
     */
    procesarUbicaciones(ubicacionesRaw) {
        let ubicacionesFormato = [];

        if (!ubicacionesRaw) {
            return ubicacionesFormato;
        }

        // CASO 1: Ya es un array
        if (Array.isArray(ubicacionesRaw)) {
            ubicacionesFormato = ubicacionesRaw;
        }
        // CASO 2: Es un string (puede ser JSON o texto plano)
        else if (typeof ubicacionesRaw === 'string') {
            try {
                // Intentar parsear como JSON
                ubicacionesFormato = JSON.parse(ubicacionesRaw);
                if (!Array.isArray(ubicacionesFormato)) {
                    ubicacionesFormato = [ubicacionesRaw];
                }
            } catch (e) {
                // Si no es JSON válido, tratar como string directo
                ubicacionesFormato = [ubicacionesRaw];
            }
        }
        // CASO 3: Es un objeto
        else if (typeof ubicacionesRaw === 'object') {
            ubicacionesFormato = [ubicacionesRaw];
        }

        return ubicacionesFormato;
    }

    /**
     * Aplicar tallas automáticamente a procesos en cotización
     * Usado para Reflectivo/Logo donde los procesos necesitan tallas
     * @private
     */
    aplicarTallasAProcessos(procesos, tallasRelacionales) {
        if (!procesos || !tallasRelacionales) {
            return procesos;
        }

        const procesosArray = Array.isArray(procesos) ? procesos : Object.values(procesos);
        
        procesosArray.forEach(proceso => {
            if (proceso && proceso.datos) {
                // Copiar tallas automáticamente
                proceso.datos.tallas = {
                    dama: { ...tallasRelacionales.DAMA },
                    caballero: { ...tallasRelacionales.CABALLERO }
                };

                // Si hay estructura talla_cantidad, también copiar
                if (!proceso.datos.talla_cantidad) {
                    proceso.datos.talla_cantidad = {};
                }
                
                Object.entries(tallasRelacionales).forEach(([genero, tallas]) => {
                    proceso.datos.talla_cantidad[genero.toLowerCase()] = { ...tallas };
                });
            }
        });

        return procesos;
    }

    /**
     * Normalizar valores con acentos (para variaciones manga/broche)
     * Convierte: "Manga Larga" → "manga larga"
     * @private
     */
    normalizarValorVariacion(valor) {
        if (!valor) return '';

        return valor
            .toLowerCase()
            .replace(/á/g, 'a')
            .replace(/é/g, 'e')
            .replace(/í/g, 'i')
            .replace(/ó/g, 'o')
            .replace(/ú/g, 'u')
            .replace(/ü/g, 'u')
            .trim();
    }

    /**
     * Validar y preparar datos de variaciones refleCtivas
     * Maneja checkbox + input + observaciones
     * @private
     */
    prepararVariacionReflectiva(nombreVariacion, data) {
        return {
            nombre: nombreVariacion,
            checkbox: `aplica-${nombreVariacion.toLowerCase()}`,
            input: `${nombreVariacion.toLowerCase()}-input`,
            obs: `${nombreVariacion.toLowerCase()}-obs`,
            valor: data.valor || '',
            observacion: data.observacion || '',
            checked: data.checked === true,
            esReflectivo: true
        };
    }
}

window.PrendaEditorService = PrendaEditorService;
