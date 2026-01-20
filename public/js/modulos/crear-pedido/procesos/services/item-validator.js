/**
 * ItemValidator - Validador de Ítems
 * 
 * Responsabilidad única: Validar datos de ítems y pedidos
 * 
 * Principios SOLID aplicados:
 * - SRP: Solo valida datos
 * - OCP: Fácil agregar nuevas reglas de validación
 * - DIP: Puede ser inyectado como dependencia
 */
class ItemValidator {
    constructor(options = {}) {
        this.reglas = options.reglas || this.obtenerReglasDefault();
    }

    /**
     * Validar un ítem individual
     */
    validarItem(item) {
        const errores = [];

        if (!item) {
            errores.push('El ítem no puede estar vacío');
            return { válido: false, errores };
        }

        if (!item.tipo) {
            errores.push('El tipo de ítem es requerido');
        }

        if (!item.prenda && item.tipo !== 'epp') {
            errores.push('El nombre de la prenda es requerido');
        }

        if (item.tipo === 'epp') {
            if (!item.epp_id) {
                errores.push('El EPP ID es requerido');
            }
            if (!item.nombre) {
                errores.push('El nombre del EPP es requerido');
            }
            if (!item.cantidad || item.cantidad <= 0) {
                errores.push('La cantidad debe ser mayor a 0');
            }
        } else {
            // Para prendas
            const tieneTallas = (item.tallas && item.tallas.length > 0) || 
                               (item.cantidad_talla && Object.keys(item.cantidad_talla).length > 0);
            
            if (!tieneTallas) {
                errores.push('Debe especificar al menos una talla');
            }
        }

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Validar un pedido completo
     */
    validarPedido(pedido) {
        const errores = [];

        if (!pedido) {
            errores.push('El pedido no puede estar vacío');
            return { válido: false, errores };
        }

        // Validar cliente
        if (!pedido.cliente || pedido.cliente.trim() === '') {
            errores.push('El cliente es requerido');
        }

        // Validar ítems
        if (!pedido.items || pedido.items.length === 0) {
            errores.push('Debe agregar al menos un ítem al pedido');
        } else {
            pedido.items.forEach((item, idx) => {
                const validacion = this.validarItem(item);
                if (!validacion.válido) {
                    validacion.errores.forEach(err => {
                        errores.push(`Ítem ${idx + 1}: ${err}`);
                    });
                }
            });
        }

        // Validar forma de pago si se especifica
        if (pedido.forma_de_pago && pedido.forma_de_pago.trim() === '') {
            errores.push('Seleccione una forma de pago válida');
        }

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Validar formulario rápido (campos básicos)
     */
    validarFormularioRápido(datos = {}) {
        const errores = [];

        if (!datos.nombrePrenda || datos.nombrePrenda.trim() === '') {
            errores.push('El nombre de la prenda es requerido');
        }

        if (!datos.genero || datos.genero.length === 0) {
            errores.push('Debe seleccionar al menos un género');
        }

        if (!datos.tallas || Object.keys(datos.tallas).length === 0) {
            errores.push('Debe seleccionar al menos una talla');
        }

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Validar prenda nueva con todos los campos
     */
    validarPrendaNueva(prenda) {
        const errores = [];

        if (!prenda) {
            errores.push('La prenda no puede estar vacía');
            return { válido: false, errores };
        }

        if (!prenda.nombre_producto || prenda.nombre_producto.trim() === '') {
            errores.push('El nombre de la prenda es requerido');
        }

        if (!prenda.genero || (Array.isArray(prenda.genero) && prenda.genero.length === 0)) {
            errores.push('Debe especificar al menos un género');
        }

        if (!prenda.cantidadesPorTalla || Object.keys(prenda.cantidadesPorTalla).length === 0) {
            errores.push('Debe especificar cantidades por talla');
        }

        const totalCantidad = Object.values(prenda.cantidadesPorTalla || {})
            .reduce((sum, qty) => sum + parseInt(qty || 0), 0);
        
        if (totalCantidad === 0) {
            errores.push('La cantidad total debe ser mayor a 0');
        }

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Obtener reglas de validación por defecto
     */
    obtenerReglasDefault() {
        return {
            cliente: {
                requerido: true,
                minimo: 3,
                maximo: 100
            },
            nombrePrenda: {
                requerido: true,
                minimo: 3,
                maximo: 255
            },
            cantidad: {
                requerido: true,
                minimo: 1,
                maximo: 10000
            }
        };
    }

    /**
     * Agregar nueva regla de validación
     */
    agregarRegla(campo, regla) {
        this.reglas[campo] = regla;
    }
}

window.ItemValidator = ItemValidator;
