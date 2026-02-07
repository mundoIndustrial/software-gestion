/**
 * PrendaOrigenService - Gestión de origen automático desde cotización
 * 
 * Propósito: Encapsular la lógica de determinación automática de origen
 * basado en el tipo de cotización
 * 
 * Casos de uso:
 * - Reflectivo → FUERZA origen = 'bodega'
 * - Logo → FUERZA origen = 'bodega'
 * - Otros tipos → Mantiene origen original o default 'confeccion'
 */
class PrendaOrigenService {
    constructor(opciones = {}) {
        this.eventBus = opciones.eventBus;
    }

    /**
     * Aplicar origen automático desde cotización
     * FUERZA origen = 'bodega' si cotización es Reflectivo o Logo
     */
    aplicarOrigenAutomaticoDesdeCotizacion(prenda, cotizacion = null) {
        if (!cotizacion) {
            console.debug('[PrendaOrigenService] No hay cotización, omitiendo origen automático');
            return prenda;
        }

        const tipoCotizacionId = cotizacion.tipo_cotizacion_id;
        const nombreTipo = this.extraerNombreTipoCotizacion(cotizacion);

        console.log('[PrendaOrigenService] Analizando origen automático:', {
            tipoCotizacionId,
            nombreTipo,
            esReflectivo: this.esReflectivo(nombreTipo, tipoCotizacionId),
            esLogo: this.esLogo(nombreTipo, tipoCotizacionId)
        });

        // Si es Reflectivo o Logo → FORZAR bodega
        if (this.esReflectivo(nombreTipo, tipoCotizacionId) || this.esLogo(nombreTipo, tipoCotizacionId)) {
            prenda.origen = 'bodega';
            console.log('[PrendaOrigenService] FORZANDO origen = "bodega"');
        } else {
            // Para otros tipos, mantener o usar default
            prenda.origen = prenda.origen || 'confeccion';
            console.log('[PrendaOrigenService] Origen = "' + prenda.origen + '"');
        }

        this.eventBus?.emit(PrendaEventBus.EVENTOS.ORIGEN_CAMBIADO, {
            prenda: prenda.nombre_prenda,
            origen: prenda.origen,
            cotizacion: cotizacion.numero_cotizacion || cotizacion.id,
            tipo: nombreTipo || tipoCotizacionId
        });

        return prenda;
    }

    /**
     * Extraer nombre del tipo de cotización desde varios formatos posibles
     * @private
     */
    extraerNombreTipoCotizacion(cotizacion) {
        if (!cotizacion) return null;

        // Intento 1: tipo_cotizacion.nombre (objeto relacionado)
        if (cotizacion.tipo_cotizacion?.nombre) {
            return cotizacion.tipo_cotizacion.nombre;
        }

        // Intento 2: tipo_nombre (string directo)
        if (cotizacion.tipo_nombre) {
            return cotizacion.tipo_nombre;
        }

        // Intento 3: tipo (fallback)
        if (cotizacion.tipo) {
            return cotizacion.tipo;
        }

        return null;
    }

    /**
     * Detectar si es cotización Reflectivo
     * @private
     */
    esReflectivo(nombreTipo, tipoCotizacionId) {
        if (!nombreTipo && !tipoCotizacionId) return false;

        const nombreNormalizado = nombreTipo?.toLowerCase() || '';
        const idString = String(tipoCotizacionId || '').toLowerCase();

        return (
            nombreNormalizado === 'reflectivo' ||
            idString === 'reflectivo' ||
            tipoCotizacionId === 4 ||              // ID correcto para Reflectivo
            tipoCotizacionId === 'Reflectivo'
        );
    }

    /**
     * Detectar si es cotización Logo
     * @private
     */
    esLogo(nombreTipo, tipoCotizacionId) {
        if (!nombreTipo && !tipoCotizacionId) return false;

        const nombreNormalizado = nombreTipo?.toLowerCase() || '';
        const idString = String(tipoCotizacionId || '').toLowerCase();

        return (
            nombreNormalizado === 'logo' ||
            idString === 'logo' ||
            tipoCotizacionId === 3 ||              // ID para Logo
            tipoCotizacionId === 'Logo'
        );
    }

    /**
     * Obtener tipo de cotización con métodos múltiples
     */
    esCotizacionReflectivoOLogo(cotizacion) {
        if (!cotizacion) {
            return { reflectivo: false, logo: false };
        }

        const nombreTipo = this.extraerNombreTipoCotizacion(cotizacion);
        const tipoCotizacionId = cotizacion.tipo_cotizacion_id;

        return {
            reflectivo: this.esReflectivo(nombreTipo, tipoCotizacionId),
            logo: this.esLogo(nombreTipo, tipoCotizacionId)
        };
    }

    /**
     * Obtener origen basado en tipo de cotización
     */
    obtenerOrigenPorTipoCotizacion(cotizacion, origenDefault = 'confeccion') {
        if (!cotizacion) {
            return origenDefault;
        }

        const nombreTipo = this.extraerNombreTipoCotizacion(cotizacion);
        const tipoCotizacionId = cotizacion.tipo_cotizacion_id;

        if (this.esReflectivo(nombreTipo, tipoCotizacionId) || this.esLogo(nombreTipo, tipoCotizacionId)) {
            return 'bodega';
        }

        return origenDefault;
    }

    /**
     * Normalizar y validar origen
     */
    normalizarOrigen(origen) {
        if (!origen) return 'confeccion';

        const origenLower = origen.toLowerCase().trim();

        // Mapper de posibles variaciones
        const origenMap = {
            'bodega': 'bodega',
            'warehouse': 'bodega',
            'de bodega': 'bodega',
            'confeccion': 'confeccion',
            'confección': 'confeccion',
            'custom': 'confeccion',
            'a confeccionar': 'confeccion',
            'sewing': 'confeccion'
        };

        return origenMap[origenLower] || 'confeccion';
    }

    /**
     * Comparar orígenes (con normalización)
     */
    sonOrigenesIguales(origen1, origen2) {
        return this.normalizarOrigen(origen1) === this.normalizarOrigen(origen2);
    }

    /**
     * Validar si origen es válido
     */
    esOrigenValido(origen) {
        const valid = ['bodega', 'confeccion'];
        return valid.includes(this.normalizarOrigen(origen));
    }
}

window.PrendaOrigenService = PrendaOrigenService;
