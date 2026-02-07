/**
 * PrendaVariacionesService - Gestión de variaciones (manga, bolsillos, broche, reflectivo)
 * 
 * Responsabilidades:
 * - Cargar variaciones desde prenda o procesos
 * - Normalizar formatos múltiples de datos
 * - Aplicar variaciones al formulario (checkboxes, inputs, textareas)
 * - Gestionar género de la prenda
 */
class PrendaVariacionesService {
    constructor(opciones = {}) {
        this.domAdapter = opciones.domAdapter;
        this.eventBus = opciones.eventBus;
    }

    /**
     * Procesar y cargar variaciones de la prenda
     * Extrae desde variantes o procesos y aplica al DOM
     */
    cargarVariaciones(prenda) {
        console.log('[PrendaVariacionesService] Cargando variaciones');

        let variantes = prenda.variantes || {};

        // Si variantes vacío pero hay procesos, extraer de procesos
        if ((!variantes || Object.keys(variantes).length === 0) && prenda.procesos) {
            variantes = this.extraerVariacionesDesdeProcesos(prenda.procesos);
        }

        // Cargar género
        this.aplicarGenero(variantes);

        // Cargar cada tipo de variación
        this.aplicarManga(variantes);
        this.aplicarBolsillos(variantes);
        this.aplicarBroche(variantes);
        this.aplicarReflectivo(variantes);

        this.eventBus?.emit(PrendaEventBus.EVENTOS.VARIACIONES_CARGADAS, variantes);

        console.log('[PrendaVariacionesService] ✓ Variaciones cargadas');
    }

    /**
     * Extraer variaciones desde procesos
     * Si variantes está vacío pero hay procesos Logo/Reflectivo
     * @private
     */
    extraerVariacionesDesdeProcesos(procesos) {
        if (!procesos) return {};

        const procesosArray = Array.isArray(procesos) ? procesos : Object.values(procesos);
        
        if (procesosArray.length > 0 && procesosArray[0].variaciones_prenda) {
            console.log('[PrendaVariacionesService] Variaciones extraídas desde procesos');
            return procesosArray[0].variaciones_prenda;
        }

        return {};
    }

    /**
     * Aplicar género (marca checkbox)
     * genero_id: 1=DAMA, 2=CABALLERO
     * @private
     */
    aplicarGenero(variantes) {
        if (!variantes.genero_id) return;

        const generoMap = {
            1: 'DAMA',
            2: 'CABALLERO'
        };

        const generoSeleccionado = generoMap[variantes.genero_id];
        if (generoSeleccionado && this.domAdapter) {
            console.log('[PrendaVariacionesService] Género:', generoSeleccionado);
            this.domAdapter.marcarGenero(generoSeleccionado, true);
        }
    }

    /**
     * Aplicar manga (checkbox + input + observación)
     * Soporta múltiples formatos de datos
     * @private
     */
    aplicarManga(variantes) {
        let mangaOpcion = '';
        let mangaObs = '';

        // Intento 1: variantes.tipo_manga (string directo)
        if (typeof variantes.tipo_manga === 'string' && variantes.tipo_manga) {
            mangaOpcion = variantes.tipo_manga;
        }
        // Intento 2: variantes.manga.opcion (objeto)
        else if (variantes.manga?.opcion) {
            mangaOpcion = variantes.manga.opcion;
        }
        // Intento 3: variantes.manga.tipo_manga (variación de nombre)
        else if (variantes.manga?.tipo_manga) {
            mangaOpcion = variantes.manga.tipo_manga;
        }

        // Observación
        mangaObs = variantes.obs_manga || '';

        if (!mangaOpcion && !mangaObs) {
            return; // Sin manga
        }

        if (this.domAdapter) {
            this.domAdapter.marcarVariacion('manga', true);
            if (mangaOpcion) {
                const mangaNormalizado = this.normalizarValor(mangaOpcion);
                this.domAdapter.establecerVariacionInput('manga', mangaNormalizado);
            }
            if (mangaObs) {
                this.domAdapter.establecerVariacionObs('manga', mangaObs);
            }
        }

        console.log('[PrendaVariacionesService] Manga aplicada:', mangaOpcion);
    }

    /**
     * Aplicar bolsillos
     * @private
     */
    aplicarBolsillos(variantes) {
        const bolsillosObs = variantes.bolsillos?.observacion || variantes.obs_bolsillos || '';
        const bolsillosOpcion = variantes.bolsillos?.opcion || '';

        if (!bolsillosObs && !bolsillosOpcion) {
            return; // Sin bolsillos
        }

        if (this.domAdapter) {
            this.domAdapter.marcarVariacion('bolsillos', true);
            if (bolsillosObs) {
                this.domAdapter.establecerVariacionObs('bolsillos', bolsillosObs);
            }
        }

        console.log('[PrendaVariacionesService] Bolsillos aplicados');
    }

    /**
     * Aplicar broche/botón
     * Soporta múltiples formatos de datos
     * @private
     */
    aplicarBroche(variantes) {
        let brocheOpcion = '';
        let brocheObs = '';

        // Intento 1: variantes.tipo_broche (string directo)
        if (typeof variantes.tipo_broche === 'string' && variantes.tipo_broche) {
            brocheOpcion = variantes.tipo_broche;
        }
        // Intento 2: variantes.broche_boton.opcion (objeto)
        else if (variantes.broche_boton?.opcion) {
            brocheOpcion = variantes.broche_boton.opcion;
        }
        // Intento 3: variantes.broche.opcion (variación de nombre)
        else if (variantes.broche?.opcion) {
            brocheOpcion = variantes.broche.opcion;
        }

        // Observación (puede estar en varios lugares)
        brocheObs = variantes.obs_broche || variantes.broche_boton_obs || '';

        if (!brocheOpcion && !brocheObs) {
            return; // Sin broche
        }

        if (this.domAdapter) {
            this.domAdapter.marcarVariacion('broche', true);
            if (brocheOpcion) {
                const brocheNormalizado = this.normalizarValor(brocheOpcion);
                this.domAdapter.establecerVariacionInput('broche', brocheNormalizado);
            }
            if (brocheObs) {
                this.domAdapter.establecerVariacionObs('broche', brocheObs);
            }
        }

        console.log('[PrendaVariacionesService] Broche aplicado:', brocheOpcion);
    }

    /**
     * Aplicar reflectivo
     * @private
     */
    aplicarReflectivo(variantes) {
        const tieneReflectivo = variantes.tiene_reflectivo === true;
        const refObs = variantes.obs_reflectivo || '';

        if (!tieneReflectivo && !refObs) {
            return; // Sin reflectivo
        }

        if (this.domAdapter) {
            this.domAdapter.marcarVariacion('reflectivo', true);
            if (refObs) {
                this.domAdapter.establecerVariacionObs('reflectivo', refObs);
            }
        }

        console.log('[PrendaVariacionesService] Reflectivo aplicado');
    }

    /**
     * Normalizar valor de entrada
     * Convierte a minúsculas y remueve acentos
     * @private
     */
    normalizarValor(valor) {
        if (!valor) return '';

        return valor
            .toLowerCase()
            .replace(/á/g, 'a')
            .replace(/é/g, 'e')
            .replace(/í/g, 'i')
            .replace(/ó/g, 'o')
            .replace(/ú/g, 'u')
            .trim();
    }

    /**
     * Obtener variaciones actuales del formulario
     */
    obtenerVariacionesDelFormulario() {
        if (!this.domAdapter) return {};

        return {
            tipo_manga: this.domAdapter.estaVariacionMarcada('manga') ? 
                this.domAdapter.obtenerVariacionInput('manga') : null,
            obs_manga: this.domAdapter.obtenerVariacionObs('manga'),
            
            bolsillos_opcion: this.domAdapter.obtenerVariacionInput('bolsillos'),
            obs_bolsillos: this.domAdapter.obtenerVariacionObs('bolsillos'),
            
            tipo_broche: this.domAdapter.estaVariacionMarcada('broche') ? 
                this.domAdapter.obtenerVariacionInput('broche') : null,
            obs_broche: this.domAdapter.obtenerVariacionObs('broche'),
            
            tiene_reflectivo: this.domAdapter.estaVariacionMarcada('reflectivo'),
            obs_reflectivo: this.domAdapter.obtenerVariacionObs('reflectivo')
        };
    }

    /**
     * Validar variaciones
     */
    validarVariaciones(variaciones) {
        const errores = [];

        // Validaciones simples
        if (variaciones.tipo_manga && variaciones.tipo_manga.length < 2) {
            errores.push('Tipo de manga muy corto');
        }

        if (variaciones.tipo_broche && variaciones.tipo_broche.length < 2) {
            errores.push('Tipo de broche muy corto');
        }

        return {
            valido: errores.length === 0,
            errores
        };
    }

    /**
     * Limpiar variaciones
     */
    limpiarVariaciones() {
        if (!this.domAdapter) return;

        this.domAdapter.marcarVariacion('manga', false);
        this.domAdapter.marcarVariacion('bolsillos', false);
        this.domAdapter.marcarVariacion('broche', false);
        this.domAdapter.marcarVariacion('reflectivo', false);

        this.domAdapter.establecerVariacionInput('manga', '');
        this.domAdapter.establecerVariacionInput('broche', '');
        this.domAdapter.establecerVariacionObs('manga', '');
        this.domAdapter.establecerVariacionObs('bolsillos', '');
        this.domAdapter.establecerVariacionObs('broche', '');
        this.domAdapter.establecerVariacionObs('reflectivo', '');
    }
}

window.PrendaVariacionesService = PrendaVariacionesService;
