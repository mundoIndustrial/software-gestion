/**
 * Módulo: FormularioPedidoController
 * Responsabilidad: Gestionar envío de formulario
 * Principio SRP: solo responsable del envío y validación de formulario
 * Principio DIP: recibe dependencias inyectadas
 */
export class FormularioPedidoController {
    constructor(form, dependencies = {}) {
        this.form = form;
        this.cotizacionSearch = dependencies.cotizacionSearch;
        this.prendasUI = dependencies.prendasUI;
        this.formInfoUpdater = dependencies.formInfoUpdater;
        this.csrfToken = dependencies.csrfToken;
        this.cotizacionId = null;

        this.attachEventListeners();
    }

    /**
     * Adjunta listeners al formulario
     */
    attachEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    /**
     * Actualiza información del formulario cuando se selecciona cotización
     */
    actualizarInfo(cotizacion, datosCotizacion) {
        if (this.formInfoUpdater) {
            this.formInfoUpdater.actualizar(cotizacion, datosCotizacion);
        }
        this.cotizacionId = cotizacion.id;
    }

    /**
     * Maneja envío del formulario
     */
    handleSubmit(e) {
        e.preventDefault();

        // Validar cotización seleccionada
        const cotizacionId = this.cotizacionSearch.obtenerSeleccionada();
        if (!cotizacionId) {
            this.mostrarError('Selecciona una cotización', 'Selecciona una cotización antes de continuar');
            return;
        }

        // Obtener datos de prendas
        const prendasData = this.prendasUI.obtenerDatos();
        if (prendasData.length === 0) {
            this.mostrarError('Sin prendas', 'Debes agregar al menos una prenda con cantidades');
            return;
        }

        // Enviar
        this.enviar(cotizacionId, prendasData);
    }

    /**
     * Envía los datos al servidor
     */
    async enviar(cotizacionId, prendasData) {
        try {
            // Obtener forma de pago del campo del formulario
            const formaPagoInput = document.getElementById('forma_de_pago');
            const formaDePago = formaPagoInput ? formaPagoInput.value : null;

            const response = await fetch(
                `/asesores/cotizaciones/${cotizacionId}/crear-pedido-produccion`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        cotizacion_id: cotizacionId,
                        prendas: prendasData,
                        forma_de_pago: formaDePago,
                    })
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.mostrarExito(data.message || 'Pedido creado exitosamente');
                setTimeout(() => {
                    window.location.href = data.redirect || '/asesores/pedidos-produccion';
                }, 1500);
            } else {
                this.mostrarError('Error', data.message || 'Error al crear el pedido');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarError('Error de conexión', 'Error al crear el pedido: ' + error.message);
        }
    }

    /**
     * Muestra mensaje de error con SweetAlert
     */
    mostrarError(titulo, mensaje) {
        if (typeof Swal === 'undefined') {
            alert(`${titulo}: ${mensaje}`);
            return;
        }

        Swal.fire({
            icon: 'error',
            title: titulo,
            text: mensaje,
            confirmButtonText: 'OK'
        });
    }

    /**
     * Muestra mensaje de éxito con SweetAlert
     */
    mostrarExito(mensaje) {
        if (typeof Swal === 'undefined') {
            alert(mensaje);
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Creado exitosamente',
            text: mensaje,
            timer: 1500,
            timerProgressBar: true,
            showConfirmButton: false
        });
    }
}
