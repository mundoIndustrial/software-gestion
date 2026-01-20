/**
 *  PEDIDO VALIDATOR
 * 
 * Validador exhaustivo del estado completo del pedido.
 * Valida TODAS las reglas de negocio antes de permitir envío.
 * 
 * Retorna:
 * {
 *   valid: boolean,
 *   errors: {
 *     field: ['mensaje1', 'mensaje2'],
 *     ...
 *   }
 * }
 * 
 * @author Senior Frontend Developer
 * @version 1.0.0
 */

class PedidoValidator {
    /**
     * Validar estado completo del pedido
     */
    static validar(state) {
        const errors = {};

        // Validar estructura
        this.validarEstructura(state, errors);
        
        if (!this.tienePrendas(state)) {
            errors['prendas'] = ['Debe existir al menos una prenda'];
        } else {
            // Validar prendas
            state.prendas.forEach((prenda, indexPrenda) => {
                this.validarPrenda(prenda, indexPrenda, errors);
            });
        }

        const valid = Object.keys(errors).length === 0;

        return {
            valid,
            errors,
            mensaje: valid ? ' Pedido válido' : ' El pedido tiene errores'
        };
    }

    // ==================== VALIDACIÓN PRINCIPAL ====================

    /**
     * Validar estructura base
     */
    static validarEstructura(state, errors) {
        if (!state) {
            errors['general'] = ['Estado no válido'];
            return;
        }

        // Validar pedido_produccion_id
        if (!state.pedido_produccion_id || typeof state.pedido_produccion_id !== 'number') {
            errors['pedido_produccion_id'] = [
                'Debe seleccionar un pedido de producción válido'
            ];
        }

        // Validar que prendas sea array
        if (!Array.isArray(state.prendas)) {
            errors['prendas'] = ['La estructura de prendas no es válida'];
        }
    }

    /**
     * Validar prenda individual
     */
    static validarPrenda(prenda, index, errors) {
        const prefix = `prenda_${index}`;

        // Validar nombre
        if (!prenda.nombre_prenda || prenda.nombre_prenda.trim() === '') {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('La prenda debe tener un nombre');
        }

        // Validar género
        if (!['dama', 'caballero', 'unisex', null].includes(prenda.genero)) {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('Género no válido');
        }

        // Validar que tenga variantes
        if (!Array.isArray(prenda.variantes) || prenda.variantes.length === 0) {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('Cada prenda debe tener al menos una variante');
        } else {
            // Validar cada variante
            prenda.variantes.forEach((variante, indexVariante) => {
                this.validarVariante(variante, index, indexVariante, errors);
            });
        }

        // Validar procesos (opcional pero si existen, deben ser válidos)
        if (Array.isArray(prenda.procesos)) {
            prenda.procesos.forEach((proceso, indexProceso) => {
                this.validarProceso(proceso, index, indexProceso, errors);
            });
        }

        // Validar observaciones condicionales
        this.validarObservacionesCondicionales(prenda, index, errors);
    }

    /**
     * Validar variante
     */
    static validarVariante(variante, indexPrenda, indexVariante, errors) {
        const prefix = `variante_${indexPrenda}_${indexVariante}`;

        // Validar talla
        if (!variante.talla || variante.talla.trim() === '') {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('La talla es obligatoria');
        }

        // Validar cantidad
        if (!Number.isInteger(variante.cantidad) || variante.cantidad <= 0) {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('La cantidad debe ser un número mayor a 0');
        }

        // Validar cantidad máxima (opcional, ajustar según negocio)
        if (variante.cantidad > 10000) {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('La cantidad no puede exceder 10000 unidades');
        }

        // Si tiene bolsillos, observaciones son obligatorias
        if (variante.tiene_bolsillos === true) {
            if (!variante.bolsillos_obs || variante.bolsillos_obs.trim() === '') {
                if (!errors[prefix]) errors[prefix] = [];
                errors[prefix].push('Si tiene bolsillos, debe describir los detalles');
            }
        }

        // Si tiene tipo_manga_id, manga_obs es recomendado
        if (variante.tipo_manga_id && (!variante.manga_obs || variante.manga_obs.trim() === '')) {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('Se recomienda agregar observaciones de manga');
        }

        // Si tiene broche/botón, observaciones recomendadas
        if (variante.tipo_broche_boton_id && (!variante.broche_boton_obs || variante.broche_boton_obs.trim() === '')) {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('Se recomienda agregar observaciones de broche/botón');
        }
    }

    /**
     * Validar proceso
     */
    static validarProceso(proceso, indexPrenda, indexProceso, errors) {
        const prefix = `proceso_${indexPrenda}_${indexProceso}`;

        // Tipo de proceso es obligatorio
        if (!proceso.tipo_proceso_id) {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('Debe seleccionar un tipo de proceso');
        }

        // Si tiene ubicaciones, debe tener al menos una
        if (Array.isArray(proceso.ubicaciones) && proceso.ubicaciones.length === 0) {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('Debe seleccionar al menos una ubicación para el proceso');
        }

        // Observaciones son recomendadas
        if (!proceso.observaciones || proceso.observaciones.trim() === '') {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push('Se recomienda agregar observaciones al proceso');
        }
    }

    /**
     * Validar observaciones condicionales
     */
    static validarObservacionesCondicionales(prenda, index, errors) {
        const prefix = `prenda_${index}`;

        // Si la descripción es muy corta y la prenda es compleja, avisar
        if (prenda.descripcion && prenda.descripcion.length < 10 && prenda.variantes.length > 1) {
            if (!errors[prefix]) errors[prefix] = [];
            errors[prefix].push(' Descripción muy breve para prenda con múltiples variantes');
        }
    }

    // ==================== UTILIDADES ====================

    /**
     * Verificar si hay prendas
     */
    static tienePrendas(state) {
        return state.prendas && Array.isArray(state.prendas) && state.prendas.length > 0;
    }

    /**
     * Validar que el pedido esté "listo para envío"
     */
    static estaCompleto(state) {
        const result = this.validar(state);
        return result.valid;
    }

    /**
     * Contar errores
     */
    static contarErrores(errors) {
        let total = 0;
        Object.values(errors).forEach(fieldErrors => {
            if (Array.isArray(fieldErrors)) {
                total += fieldErrors.length;
            }
        });
        return total;
    }

    /**
     * Obtener primer error (para mostrar en toast)
     */
    static obtenerPrimerError(errors) {
        for (const [field, messages] of Object.entries(errors)) {
            if (Array.isArray(messages) && messages.length > 0) {
                return messages[0];
            }
        }
        return 'Error desconocido';
    }

    /**
     * Formatear errores para mostrar
     */
    static formatearErrores(errors) {
        const formatted = [];

        for (const [field, messages] of Object.entries(errors)) {
            if (Array.isArray(messages)) {
                messages.forEach(msg => {
                    formatted.push({
                        field,
                        message: msg
                    });
                });
            }
        }

        return formatted;
    }

    /**
     * Validar un campo específico (para validación en tiempo real)
     */
    static validarCampo(field, value, context = {}) {
        const errors = [];

        switch (field) {
            case 'nombre_prenda':
                if (!value || value.trim() === '') {
                    errors.push('El nombre de la prenda es obligatorio');
                }
                if (value && value.length > 100) {
                    errors.push('El nombre no puede exceder 100 caracteres');
                }
                break;

            case 'cantidad':
                if (!Number.isInteger(value) || value <= 0) {
                    errors.push('La cantidad debe ser un número mayor a 0');
                }
                if (value > 10000) {
                    errors.push('La cantidad no puede exceder 10000 unidades');
                }
                break;

            case 'talla':
                if (!value || value.trim() === '') {
                    errors.push('La talla es obligatoria');
                }
                break;

            case 'bolsillos_obs':
                if (context.tiene_bolsillos === true && (!value || value.trim() === '')) {
                    errors.push('Las observaciones de bolsillos son obligatorias');
                }
                break;

            case 'tipo_proceso_id':
                if (!value) {
                    errors.push('El tipo de proceso es obligatorio');
                }
                break;

            case 'ubicaciones':
                if (!Array.isArray(value) || value.length === 0) {
                    errors.push('Debe seleccionar al menos una ubicación');
                }
                break;
        }

        return {
            valid: errors.length === 0,
            errors
        };
    }

    /**
     * Obtener reporte completo de validación
     */
    static obtenerReporte(state) {
        const result = this.validar(state);
        const formatted = this.formatearErrores(result.errors);
        const errorCount = this.contarErrores(result.errors);

        return {
            valid: result.valid,
            mensaje: result.mensaje,
            totalErrores: errorCount,
            errores: formatted,
            resumen: {
                pedidoId: state.pedido_produccion_id,
                prendas: state.prendas?.length || 0,
                variantes: state.prendas?.reduce((sum, p) => sum + p.variantes.length, 0) || 0,
                procesos: state.prendas?.reduce((sum, p) => sum + p.procesos.length, 0) || 0,
                items: state.prendas?.reduce((sum, p) => 
                    sum + p.variantes.reduce((vs, v) => vs + v.cantidad, 0), 0
                ) || 0
            }
        };
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PedidoValidator;
}
