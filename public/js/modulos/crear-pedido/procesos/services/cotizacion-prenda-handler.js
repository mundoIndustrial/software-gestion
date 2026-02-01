/**
 * CotizacionPrendaHandler - Gestor de lógica de Prendas desde Cotizaciones
 * 
 * Responsabilidad: Aplicar reglas de negocio específicas para prendas originarias de cotizaciones
 * - Determinar origen automático basado en tipo de cotización
 * - Validar y asignar propiedades según el tipo de cotización
 */
class CotizacionPrendaHandler {
    /**
     * Tipos de cotización que requieren origen en bodega
     * Formato: { tipoId: 'nombre_tipo' }
     * 
     * NOTA: Estos IDs deben sincronizarse con la BD (tabla tipos_cotizacion)
     * Pueden agregarse más tipos según sea necesario
     */
    static TIPOS_COTIZACION_BODEGA = {
        'Reflectivo': ['Reflectivo'],
        'Logo': ['Logo'],
        // Agregar más tipos aquí según la necesidad del negocio
        // 'Otro': ['Otro']
    };

    /**
     * Verifica si un tipo de cotización requiere origen en bodega
     * 
     * @param {number|string} tipoCotizacionId - ID o nombre del tipo de cotización
     * @param {string} nombreTipo - Nombre del tipo de cotización (alternativa para búsqueda)
     * @returns {boolean} - true si requiere bodega, false en caso contrario
     */
    static requiereBodega(tipoCotizacionId, nombreTipo = null) {
        if (!tipoCotizacionId) {
            return false;
        }

        // Búsqueda por nombre si se proporciona
        if (nombreTipo) {
            return Object.values(this.TIPOS_COTIZACION_BODEGA).some(tipos => 
                tipos.includes(nombreTipo)
            );
        }

        // Búsqueda por ID en las claves
        return Object.keys(this.TIPOS_COTIZACION_BODEGA).some(key => 
            String(tipoCotizacionId) === String(key)
        );
    }

    /**
     * Aplica las reglas de origen automático a una prenda desde cotización
     * 
     * @param {Object} prenda - Objeto prenda a procesar
     * @param {Object} cotizacionSeleccionada - Objeto cotización con propiedades:
     *     - tipo_cotizacion_id: {number|string} ID del tipo de cotización
     *     - tipo_cotizacion?: {Object} Con propiedad 'nombre' (alternativa)
     * @returns {Object} - Prenda modificada con origen asignado
     */
    static aplicarOrigenAutomatico(prenda, cotizacionSeleccionada) {
        // Validaciones
        if (!prenda || typeof prenda !== 'object') {
            console.warn('[CotizacionPrendaHandler] Prenda inválida:', prenda);
            return prenda;
        }

        if (!cotizacionSeleccionada || typeof cotizacionSeleccionada !== 'object') {
            console.warn('[CotizacionPrendaHandler] Cotización inválida:', cotizacionSeleccionada);
            return prenda;
        }

        // Extraer información del tipo de cotización
        const tipoCotizacionId = cotizacionSeleccionada.tipo_cotizacion_id;
        const nombreTipo = cotizacionSeleccionada.tipo_cotizacion?.nombre;

        // Verificar si requiere bodega
        if (this.requiereBodega(tipoCotizacionId, nombreTipo)) {
            prenda.origen = 'bodega';
            console.debug('[CotizacionPrendaHandler] Origen asignado a bodega para prenda:', prenda.nombre);
        } else {
            // Mantener comportamiento normal (confección)
            prenda.origen = prenda.origen || 'confeccion';
            console.debug('[CotizacionPrendaHandler] Origen normal (confección) para prenda:', prenda.nombre);
        }

        return prenda;
    }

    /**
     * Procesa una prenda antes de abrir el modal de edición
     * Aplica todas las reglas de negocio necesarias
     * 
     * @param {Object} prenda - Prenda a procesar
     * @param {Object} cotizacionSeleccionada - Cotización asociada (opcional)
     * @returns {Object} - Prenda procesada y lista para edición
     */
    static prepararPrendaParaEdicion(prenda, cotizacionSeleccionada = null) {
        if (!prenda) {
            console.error('[CotizacionPrendaHandler] Intento de preparar prenda nula');
            return prenda;
        }

        // Si viene de cotización, aplicar reglas automáticas
        if (cotizacionSeleccionada) {
            this.aplicarOrigenAutomatico(prenda, cotizacionSeleccionada);
        }

        // Aquí pueden agregarse más procesamiento según sea necesario
        // Ej: validación de campos, transformación de datos, etc.

        return prenda;
    }

    /**
     * Registra un nuevo tipo de cotización que requiere bodega
     * Útil para agregar tipos dinámicamente sin modificar código
     * 
     * @param {string|number} tipoId - ID o identificador del tipo
     * @param {string} nombreTipo - Nombre del tipo de cotización
     * @returns {boolean} - true si se registró correctamente
     */
    static registrarTipoBodega(tipoId, nombreTipo) {
        if (!tipoId || !nombreTipo) {
            console.error('[CotizacionPrendaHandler] ID y nombre son requeridos');
            return false;
        }

        // Evitar duplicados
        if (this.requiereBodega(tipoId, nombreTipo)) {
            console.warn(`[CotizacionPrendaHandler] Tipo "${nombreTipo}" ya está registrado`);
            return false;
        }

        // Registrar nuevo tipo
        this.TIPOS_COTIZACION_BODEGA[String(tipoId)] = [nombreTipo];
        console.info(`[CotizacionPrendaHandler] Tipo de bodega registrado: "${nombreTipo}" (ID: ${tipoId})`);
        return true;
    }

    /**
     * Obtiene lista de tipos que requieren bodega (para UI/debugging)
     * 
     * @returns {Array<string>} - Array con nombres de tipos que requieren bodega
     */
    static obtenerTiposBodega() {
        return Object.keys(this.TIPOS_COTIZACION_BODEGA);
    }

    /**
     * Limpia y reinicia la configuración de tipos (útil para testing)
     * 
     * @param {Object} nuevossTipos - Nuevas configuraciones (opcional)
     */
    static reiniciarTipos(nuevosTipos = null) {
        if (nuevosTipos && typeof nuevosTipos === 'object') {
            this.TIPOS_COTIZACION_BODEGA = nuevosTipos;
            console.info('[CotizacionPrendaHandler] Tipos reiniciados');
        }
    }
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CotizacionPrendaHandler;
}
