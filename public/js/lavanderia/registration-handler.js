/**
 * REGISTRATION HANDLER - Lavandería
 * Maneja el registro de nuevos movimientos
 */

class RegistrationHandler {
    constructor(apiSearchUrl, tallasHandler) {
        this.apiSearchUrl = apiSearchUrl;
        this.tallasHandler = tallasHandler;
        this.currentRecibo = null;
    }

    /**
     * Muestra la información del recibo seleccionado
     */
    showReciboInfo(recibo) {
        document.getElementById('infoCliente').textContent = recibo.cliente;
        document.getElementById('infoPrenda').textContent = recibo.prenda;
        document.getElementById('reciboInfo').style.display = 'block';
        this.tallasHandler.renderTallas(recibo);
    }

    /**
     * Registra una salida
     */
    registrarSalida() {
        if (!this.currentRecibo) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Recibo Requerido', message: 'Por favor selecciona un recibo', type: 'error' }
            }));
            return;
        }

        const tallasSeleccionadas = this.tallasHandler.getSelectedTallas();

        if (tallasSeleccionadas.length === 0) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Tallas Requeridas', message: 'Por favor selecciona al menos una talla con cantidad mayor a 0', type: 'error' }
            }));
            return;
        }

        const novedad = document.getElementById('inputNovedad').value.trim();
        const tipoMovimiento = document.getElementById('selectTipoMovimiento').value;

        const datos = {
            recibo_id: parseInt(this.currentRecibo.id),
            numero_recibo: String(this.currentRecibo.numero_recibo),
            tipo_recibo: String(this.currentRecibo.tipo_recibo),
            tipo_movimiento: tipoMovimiento,
            novedad: novedad,
            tallas: tallasSeleccionadas
        };

        fetch(`${this.apiSearchUrl.replace('search-recibos', 'registrar-salida')}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(datos)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.dispatchEvent(new CustomEvent('showToast', { 
                    detail: { title: '¡Movimiento Registrado!', message: 'El movimiento se ha registrado exitosamente', type: 'success' }
                }));
                document.getElementById('modalSalida').classList.remove('active');
                window.dispatchEvent(new CustomEvent('reloadMovements'));
                this.clearForm();
            } else {
                window.dispatchEvent(new CustomEvent('showToast', { 
                    detail: { title: 'Error', message: data.message || 'No se pudo registrar el movimiento', type: 'error' }
                }));
            }
        })
        .catch(error => {
            console.error('Error al registrar:', error);
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Error', message: 'Error al registrar el movimiento', type: 'error' }
            }));
        });
    }

    /**
     * Limpia el formulario
     */
    clearForm() {
        const searchInput = document.getElementById('searchRecibo');
        if (searchInput) searchInput.value = '';
        
        document.querySelector('.autocomplete-results').classList.remove('active');
        document.getElementById('reciboInfo').style.display = 'none';
        document.getElementById('inputNovedad').value = '';
        this.currentRecibo = null;
        this.tallasHandler.clearSelectedTallas();
    }

    /**
     * Abre el modal de registro
     */
    openModalSalida() {
        const modal = document.getElementById('modalSalida');
        if (modal) {
            modal.classList.add('active');
            this.clearForm();
            this.currentRecibo = null;
        }
    }
}

export { RegistrationHandler };
