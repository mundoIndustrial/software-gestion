/**
 * Módulo: FormInfoUpdater
 * Responsabilidad: Actualizar campos de información del pedido
 * Principio SRP: solo responsable de actualizar UI de información
 */
export class FormInfoUpdater {
    constructor(fieldsConfig = {}) {
        this.numeroCotizacion = fieldsConfig.numeroCotizacion;
        this.cliente = fieldsConfig.cliente;
        this.asesora = fieldsConfig.asesora;
        this.formaPago = fieldsConfig.formaPago;
        this.numeroPedido = fieldsConfig.numeroPedido;
    }

    /**
     * Actualiza información del formulario
     */
    actualizar(cotizacion, datosCotizacion) {
        if (this.numeroCotizacion) {
            this.numeroCotizacion.value = cotizacion.numero || '';
        }
        if (this.cliente) {
            this.cliente.value = cotizacion.cliente || '';
        }
        if (this.asesora) {
            this.asesora.value = cotizacion.asesora || '';
        }
        if (this.formaPago) {
            this.formaPago.value = datosCotizacion?.forma_pago || cotizacion.formaPago || '';
        }
    }

    /**
     * Establece número de pedido siguiente
     */
    establecerNumeroPedido(numero) {
        if (this.numeroPedido) {
            this.numeroPedido.value = numero;
        }
    }
}
