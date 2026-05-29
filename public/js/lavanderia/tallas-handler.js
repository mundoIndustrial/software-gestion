/**
 * TALLAS HANDLER - Lavandería
 * Maneja la selección y cantidad de tallas
 */

class TallasHandler {
    constructor() {
        this.selectedTallas = [];
    }

    /**
     * Renderiza las tallas disponibles
     */
    renderTallas(recibo) {
        const tallasContainer = document.getElementById('tallasContainer');
        const tallas = recibo.tallas || [];

        if (tallas.length === 0) {
            tallasContainer.innerHTML = '<p style="color: #94a3b8; text-align: center;">No hay tallas disponibles</p>';
            return;
        }

        tallasContainer.innerHTML = tallas.map((talla, index) => `
            <label class="talla-item" data-talla-index="${index}">
                <input type="checkbox" class="talla-checkbox" data-talla="${talla.talla}" data-cantidad-disponible="${talla.cantidad}" data-genero="${talla.genero || ''}">
                <div class="talla-item-content">
                    <div class="talla-nombre">${talla.talla}${talla.genero ? ' (' + talla.genero + ')' : ''}</div>
                    <div class="talla-cantidad">DISP: ${talla.cantidad} pzas</div>
                </div>
                <input type="number" class="talla-input-cantidad" value="${talla.cantidad}" min="0" max="${talla.cantidad}" style="display: none;">
            </label>
        `).join('');

        document.querySelectorAll('.talla-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => this.handleTallaChange(e));
        });

        document.querySelectorAll('.talla-input-cantidad').forEach(input => {
            input.addEventListener('change', (e) => this.handleCantidadChange(e));
        });
    }

    /**
     * Maneja el cambio de selección de talla
     */
    handleTallaChange(e) {
        const checkbox = e.target;
        const label = checkbox.closest('.talla-item');
        const input = label.querySelector('.talla-input-cantidad');
        const talla = checkbox.dataset.talla;
        const cantidadDisponible = parseInt(checkbox.dataset.cantidadDisponible);

        if (checkbox.checked) {
            label.classList.add('checked');
            input.style.display = 'block';
            input.value = cantidadDisponible;
            input.max = cantidadDisponible;
        } else {
            label.classList.remove('checked');
            input.style.display = 'none';
            this.selectedTallas = this.selectedTallas.filter(t => t.talla !== talla);
        }
    }

    /**
     * Maneja el cambio de cantidad
     */
    handleCantidadChange(e) {
        const input = e.target;
        const label = input.closest('.talla-item');
        const checkbox = label.querySelector('.talla-checkbox');
        const talla = checkbox.dataset.talla;
        const cantidad = parseInt(input.value) || 0;
        const cantidadDisponible = parseInt(checkbox.dataset.cantidadDisponible);

        if (cantidad > cantidadDisponible) {
            input.value = cantidadDisponible;
            return;
        }

        const existingIndex = this.selectedTallas.findIndex(t => t.talla === talla);
        if (existingIndex >= 0) {
            this.selectedTallas[existingIndex].cantidad = cantidad;
        } else if (checkbox.checked) {
            this.selectedTallas.push({ 
                talla, 
                cantidad,
                genero: checkbox.dataset.genero
            });
        }
    }

    /**
     * Obtiene las tallas seleccionadas
     */
    getSelectedTallas() {
        const tallasSeleccionadas = [];
        document.querySelectorAll('.talla-checkbox:checked').forEach(checkbox => {
            const label = checkbox.closest('.talla-item');
            const input = label.querySelector('.talla-input-cantidad');
            const cantidad = parseInt(input.value) || 0;

            if (cantidad > 0) {
                tallasSeleccionadas.push({
                    talla: checkbox.dataset.talla,
                    genero: checkbox.dataset.genero || '',
                    cantidad_enviada: cantidad
                });
            }
        });

        return tallasSeleccionadas;
    }

    /**
     * Limpia las tallas seleccionadas
     */
    clearSelectedTallas() {
        this.selectedTallas = [];
        document.querySelectorAll('.talla-checkbox').forEach(checkbox => {
            checkbox.checked = false;
            checkbox.closest('.talla-item').classList.remove('checked');
            checkbox.closest('.talla-item').querySelector('.talla-input-cantidad').style.display = 'none';
        });
    }
}

export { TallasHandler };
