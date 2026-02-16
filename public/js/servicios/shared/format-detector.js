/**
 * 🔒 FormatDetector
 * 
 * Detecta automáticamente el formato de datos retornados por la API
 * Permite que el sistema sea flexible con cambios de formato backend
 */

class FormatDetector {
    constructor() {
        this.debugMode = false;
    }

    /**
     * Detectar formato de datos de prenda
     * 
     * @returns {string} 'NUEVO' | 'ANTIGUO' | 'DESCONOCIDO'
     */
    detectar(datos) {
        if (!datos || typeof datos !== 'object') {
            return 'DESCONOCIDO';
        }

        // Score para cada formato
        let scoreNuevo = 0;
        let scoreAntiguo = 0;

        // === INDICADORES DE FORMATO NUEVO (DDD) ===

        // Tiene generosConTallas (nueva estructura)
        if (datos.generosConTallas && typeof datos.generosConTallas === 'object') {
            scoreNuevo += 3;
            this.log('✓ Tiene generosConTallas');
        }

        // Tiene telas_array (nuevo)
        if (datos.telas_array && Array.isArray(datos.telas_array)) {
            scoreNuevo += 3;
            this.log('✓ Tiene telas_array');
        }

        // Tiene variantes con estructura nueva
        if (datos.variantes && Array.isArray(datos.variantes) && datos.variantes.length > 0) {
            const v = datos.variantes[0];
            if ((typeof v.manga === 'string' || typeof v.broche === 'string') ||
                (typeof v.manga === 'object' && v.manga?.nome)) {
                scoreNuevo += 2;
                this.log('✓ Variantes en formato nuevo');
            }
        }

        // Tiene imagenes/fotos mapeadas con ruta_webp
        if ((datos.fotos || datos.imagenes) && Array.isArray(datos.fotos || datos.imagenes)) {
            const imgs = datos.fotos || datos.imagenes;
            if (imgs.length > 0 && imgs[0].ruta_webp) {
                scoreNuevo += 1;
                this.log('✓ Imágenes con ruta_webp (nuevo)');
            }
        }

        // === INDICADORES DE FORMATO ANTIGUO ===

        // Tiene tallas_dama y tallas_caballero como arrays
        if ((datos.tallas_dama || datos.tallas_caballero) && Array.isArray(datos.tallas_dama)) {
            scoreAntiguo += 3;
            this.log('✓ Tiene tallas_dama (antiguo)');
        }

        if (Array.isArray(datos.tallas_caballero)) {
            scoreAntiguo += 3;
            this.log('✓ Tiene tallas_caballero (antiguo)');
        }

        // Tiene colores_telas
        if (datos.colores_telas && Array.isArray(datos.colores_telas)) {
            scoreAntiguo += 3;
            this.log('✓ Tiene colores_telas (antiguo)');
        }

        // Tiene talla como string directo
        if (datos.talla && typeof datos.talla === 'string') {
            scoreAntiguo += 1;
            this.log('✓ Tiene talla (antiguo)');
        }

        // === DETERMINAR FORMATO ===

        this.log(`Scores: NUEVO=${scoreNuevo}, ANTIGUO=${scoreAntiguo}`);

        if (scoreNuevo > scoreAntiguo) {
            return 'NUEVO';
        } else if (scoreAntiguo > scoreNuevo) {
            return 'ANTIGUO';
        } else if (scoreNuevo > 0 || scoreAntiguo > 0) {
            // Ambos tienen puntos, pero iguales - tomar el que más aparezca
            return this.detectarPorEstructura(datos);
        } else {
            return 'DESCONOCIDO';
        }
    }

    /**
     * Detección fallback por estructura
     */
    detectarPorEstructura(datos) {
        // Si tiene tallas_dama/caballero es antiguo
        if (datos.tallas_dama || datos.tallas_caballero) {
            this.log('Fallback: detectado ANTIGUO por tallas_*');
            return 'ANTIGUO';
        }

        // Si tiene generosConTallas es nuevo
        if (datos.generosConTallas) {
            this.log('Fallback: detectado NUEVO por generosConTallas');
            return 'NUEVO';
        }

        // Si tiene tela_id  en array es nuevo
        if (datos.telas_array && datos.telas_array[0]?.tela_id) {
            this.log('Fallback: detectado NUEVO por telas_array[].tela_id');
            return 'NUEVO';
        }

        // Si tiene color_id en array es antiguo
        if (datos.colores_telas && datos.colores_telas[0]?.color_id) {
            this.log('Fallback: detectado ANTIGUO por colores_telas[].color_id');
            return 'ANTIGUO';
        }

        this.log('Fallback: No detectado, retornando DESCONOCIDO');
        return 'DESCONOCIDO';
    }

    /**
     * Detectar todos los formatos individuales
     */
    detectarTodosLosFormatos(datos) {
        return {
            tallas: this.detectarFormatoTallas(datos),
            telas: this.detectarFormatoTelas(datos),
            variantes: this.detectarFormatoVariantes(datos),
            imagenes: this.detectarFormatoImagenes(datos)
        };
    }

    /**
     * Detectar formato de TALLAS
     */
    detectarFormatoTallas(datos) {
        if (datos.generosConTallas) return 'NUEVO';
        if (datos.tallas && typeof datos.tallas === 'object' && !Array.isArray(datos.tallas)) {
            if (Object.keys(datos.tallas).some(k => ['DAMA', 'CABALLERO', 'UNISEX'].includes(k))) {
                return 'NUEVO';
            }
        }
        if (Array.isArray(datos.tallas_dama) || Array.isArray(datos.tallas_caballero)) {
            return 'ANTIGUO';
        }
        return 'DESCONOCIDO';
    }

    /**
     * Detectar formato de TELAS
     */
    detectarFormatoTelas(datos) {
        if (datos.telas_array && Array.isArray(datos.telas_array)) return 'NUEVO';
        if (datos.colores_telas && Array.isArray(datos.colores_telas)) return 'ANTIGUO';
        if (datos.telas && Array.isArray(datos.telas)) {
            // Ambiguo, mirar primera tela
            if (datos.telas[0]?.tela_id) return 'NUEVO';
            if (datos.telas[0]?.color_id) return 'ANTIGUO';
        }
        return 'DESCONOCIDO';
    }

    /**
     * Detectar formato de VARIANTES
     */
    detectarFormatoVariantes(datos) {
        if (!datos.variantes || !Array.isArray(datos.variantes) || datos.variantes.length === 0) {
            return 'DESCONOCIDO';
        }

        const v = datos.variantes[0];

        // Nuevo: puede tener 'manga' como string
        if (typeof v.manga === 'string' && v.manga) return 'NUEVO';
        if (typeof v.broche === 'string' && v.broche) return 'NUEVO';

        // Antiguo: tipo_manga_id, tipo_broche_boton_id
        if (v.tipo_manga_id || v.tipo_broche_boton_id !== undefined) return 'ANTIGUO';

        return 'DESCONOCIDO';
    }

    /**
     * Detectar formato de IMÁGENES
     */
    detectarFormatoImagenes(datos) {
        const imgs = datos.fotos || datos.imagenes || [];

        if (!Array.isArray(imgs) || imgs.length === 0) {
            return 'DESCONOCIDO';
        }

        const img = imgs[0];

        // Nuevo: ruta_webp + ruta_original
        if (img.ruta_webp || img.ruta_original) return 'NUEVO';

        // Antiguo: simplemente una URL
        if (img.url || img.ruta) return 'ANTIGUO';

        return 'DESCONOCIDO';
    }

    /**
     * Habilitar/deshabilitar modo debug
     */
    enableDebug(enabled = true) {
        this.debugMode = enabled;
    }

    /**
     * Log interno
     */
    log(mensaje) {
        if (this.debugMode) {
            Logger.debug(mensaje, 'FormatDetector');
        }
    }
}

// Exportar
window.FormatDetector = FormatDetector;
Logger.debug('FormatDetector cargado', 'FormatDetector');
