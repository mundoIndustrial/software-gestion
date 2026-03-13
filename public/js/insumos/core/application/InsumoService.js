/**
 * Application Layer - Insumo Service
 * 
 * Lógica de negocio desacoplada de presentación e infraestructura
 * - Coordina obtención de insumos
 * - Implementa reglas de negocio
 * - Maneja errores gracefully
 * 
 * DDD Principle: Domain logic separado de acceso a datos y presentación
 */

class InsumoService {
    constructor(repository) {
        this.repository = repository;
    }

    /**
     * Obtiene insumos de un pedido
     * Use case: Usuario abre modal de insumos
     */
    async obtenerInsumosDelPedido(pedidoId, prendaId = null) {
        // Validación
        if (!pedidoId || isNaN(pedidoId)) {
            throw new ValidationError('pedidoId debe ser un número válido');
        }

        // Lógica de negocio: obtener datos
        const insumos = await this.repository.obtenerInsumos(pedidoId, prendaId);

        // Validad respuesta
        if (!insumos || !insumos.materiales) {
            throw new BusinessError('Respuesta inválida del servidor');
        }

        // Enriquecer datos (reglas de negocio)
        return this._enriquecerInsumos(insumos);
    }

    /**
     * Guarda cambios en insumos
     * Use case: Usuario hace clic en "Guardar Cambios"
     */
    async guardarCambiosInsumos(pedidoId, prendaId, materiales) {
        // Validación
        if (!pedidoId || !prendaId || !Array.isArray(materiales)) {
            throw new ValidationError('Parámetros inválidos');
        }

        if (materiales.length === 0) {
            throw new BusinessError('Debe haber al menos un material');
        }

        // Validar cada material
        materiales.forEach((material, index) => {
            this._validarMaterial(material, index);
        });

        // Guardar en el servidor
        return await this.repository.guardarInsumos(pedidoId, prendaId, {
            materiales: materiales
        });
    }

    /**
     * Verifica si hay datos en caché
     * Use case: Decidir si mostrar datos desde caché o cargar del servidor
     */
    async tieneDataEnCache(pedidoId, prendaId = null) {
        return await this.repository.existeEnCache(pedidoId, prendaId);
    }

    /**
     * Limpia el caché de insumos
     * Use case: Después de guardar, invalidar caché
     */
    async limpiarCache(pedidoId = null) {
        return await this.repository.limpiar(pedidoId);
    }

    /**
     * Enriquece datos de insumos con lógica de negocio
     * @private
     */
    _enriquecerInsumos(insumos) {
        if (!insumos.materiales) {
            return insumos;
        }

        // Agregar campos calculados o transformaciones
        return {
            ...insumos,
            totalMateriales: insumos.materiales.length,
            materialesRecibidos: insumos.materiales.filter(m => m.recibido).length,
            requiereCierre: insumos.materiales.every(m => m.recibido)
        };
    }

    /**
     * Valida un material individual
     * @private
     */
    _validarMaterial(material, index) {
        if (!material.nombre_material) {
            throw new ValidationError(`Material ${index + 1}: nombre requerido`);
        }

        // Agregar más validaciones según reglas de negocio
        if (material.fecha_pedido && material.fecha_llegada) {
            const fechaPedido = new Date(material.fecha_pedido);
            const fechaLlegada = new Date(material.fecha_llegada);
            
            if (fechaLlegada < fechaPedido) {
                throw new ValidationError(
                    `Material ${index + 1}: fecha de llegada no puede ser antes de fecha de pedido`
                );
            }
        }
    }
}

/**
 * Custom Errors
 */
class ValidationError extends Error {
    constructor(message) {
        super(message);
        this.name = 'ValidationError';
    }
}

class BusinessError extends Error {
    constructor(message) {
        super(message);
        this.name = 'BusinessError';
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { InsumoService, ValidationError, BusinessError };
} else {
    window.InsumoService = InsumoService;
    window.ValidationError = ValidationError;
    window.BusinessError = BusinessError;
}
