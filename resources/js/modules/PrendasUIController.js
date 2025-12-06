/**
 * Módulo: PrendasUIController
 * Responsabilidad: Gestionar UI de prendas y tallas
 * Principio SRP: solo responsable de UI de prendas
 */
export class PrendasUIController {
    constructor(config = {}) {
        this.container = config.container;
        this.prendas = [];
    }

    /**
     * Carga prendas en la UI
     */
    cargar(prendas) {
        this.prendas = prendas;
        
        if (!prendas || prendas.length === 0) {
            this.container.innerHTML = 
                '<p class="text-gray-500 text-center py-8">Esta cotización no tiene prendas</p>';
            return;
        }

        this.container.innerHTML = prendas
            .map((prenda, index) => this.crearPrendaHTML(prenda, index))
            .join('');

        this.attachEventListeners();
    }

    /**
     * Crea HTML de prenda completa
     */
    crearPrendaHTML(prenda, index) {
        const tallas = prenda.tallas || [];
        const imagen = prenda.fotos?.[0];
        const variantes = prenda.variantes || {};

        const descripcion = this.construirDescripcion(prenda, tallas);

        return `
            <div class="prenda-card" data-prenda-index="${index}">
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="flex: 1;">
                        <div class="prenda-descripcion" style="font-size: 0.9rem;">
                            ${descripcion}
                        </div>
                    </div>
                    ${imagen ? this.crearImagenHTML(imagen, prenda.nombre_producto) : ''}
                </div>
                
                <div class="tallas-grid">
                    ${tallas.length > 0 
                        ? tallas.map(talla => this.crearTallaHTML(index, talla)).join('')
                        : this.crearPlaceholderTallasHTML()
                    }
                </div>

                <div class="tallas-actions">
                    <input type="text" class="input-nueva-talla" placeholder="Nueva talla (ej: XS, 3XL, XL)" data-prenda="${index}">
                    <button type="button" class="btn-agregar-talla" data-prenda="${index}">
                        + Agregar
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Construye descripción multilínea de la prenda
     */
    construirDescripcion(prenda, tallas) {
        const variantes = prenda.variantes || {};
        
        // Línea 1: Nombre + variantes principales
        let linea1 = prenda.nombre_producto || '';
        const variacionesPrincipales = [];
        if (variantes.tela) variacionesPrincipales.push(variantes.tela);
        if (variantes.color) variacionesPrincipales.push(variantes.color);
        if (variantes.genero) variacionesPrincipales.push(variantes.genero);
        
        if (variacionesPrincipales.length > 0) {
            linea1 += ' ' + variacionesPrincipales.join(' ');
        }

        // Línea 2: Descripción + detalles
        let linea2 = prenda.descripcion || '';
        const detalles = [];
        if (variantes.manga) detalles.push(`MANGA ${variantes.manga.toUpperCase()}`);
        if (variantes.tiene_bolsillos) detalles.push('CON BOLSILLO');
        if (variantes.broche) detalles.push(`BROCHE ${variantes.broche.toUpperCase()}`);
        if (variantes.tiene_reflectivo) detalles.push('CON REFLECTIVO');
        
        if (detalles.length > 0) {
            linea2 = linea2 ? `${linea2} ${detalles.join(' ')}` : detalles.join(' ');
        }

        // Línea 3: Tallas
        let linea3 = 'TALLAS: ';
        linea3 += tallas.length > 0 
            ? tallas.map(t => `${t}:0`).join(', ')
            : 'N/A: 0';

        return `
            <div style="font-size: 0.9rem; line-height: 1.6; color: #1f2937;">
                <div style="font-weight: 600; margin-bottom: 0.5rem;">
                    ${linea1}
                </div>
                <div style="margin-bottom: 0.5rem; color: #4b5563;">
                    <strong>Descripción:</strong> ${linea2}
                </div>
                <div style="color: #374151;">
                    ${linea3}
                </div>
            </div>
        `;
    }

    /**
     * Crea HTML de imagen
     */
    crearImagenHTML(imagen, nombreProducto) {
        return `
            <div style="flex-shrink: 0;">
                <img src="${imagen}" alt="${nombreProducto}" class="prenda-imagen"
                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0; cursor: pointer;">
            </div>
        `;
    }

    /**
     * Crea HTML de talla individual
     */
    crearTallaHTML(prendasIndex, talla) {
        return `
            <div class="talla-group" data-talla="${talla}" data-prenda="${prendasIndex}">
                <div class="talla-header">
                    <label class="talla-label">${talla}</label>
                    <button type="button" class="btn-eliminar-talla" title="Eliminar talla">
                        ✕
                    </button>
                </div>
                <input type="number" 
                       name="cantidades[${prendasIndex}][${talla}]" 
                       class="talla-input" 
                       min="0" 
                       value="0" 
                       placeholder="0">
            </div>
        `;
    }

    /**
     * Placeholder cuando no hay tallas
     */
    crearPlaceholderTallasHTML() {
        return `
            <div style="grid-column: 1 / -1; padding: 1rem; background: #f0f9ff; border-radius: 4px; text-align: center; color: #0066cc; font-size: 0.85rem;">
                <strong>Sin tallas definidas</strong> - Agrega una talla abajo
            </div>
        `;
    }

    /**
     * Adjunta listeners de eventos
     */
    attachEventListeners() {
        // Botones de eliminar talla
        this.container.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-eliminar-talla')) {
                this.eliminarTalla(e.target);
            }

            // Botones de agregar talla
            if (e.target.classList.contains('btn-agregar-talla')) {
                this.agregarTalla(e.target);
            }
        });

        // Click en imágenes
        this.container.addEventListener('click', (e) => {
            if (e.target.classList.contains('prenda-imagen')) {
                window.abrirModalImagen?.(e.target.src, e.target.alt);
            }
        });
    }

    /**
     * Elimina una talla
     */
    eliminarTalla(btn) {
        const tallaGroup = btn.closest('.talla-group');
        const talla = tallaGroup.getAttribute('data-talla');
        
        if (confirm(`¿Eliminar la talla ${talla}?`)) {
            tallaGroup.classList.add('talla-eliminada');
            const input = tallaGroup.querySelector('.talla-input');
            input.disabled = true;
            input.value = '';
            btn.textContent = '✓';
            btn.style.background = '#10b981';
            btn.disabled = true;
        }
    }

    /**
     * Agrega una talla nueva
     */
    agregarTalla(btn) {
        const input = btn.previousElementSibling;
        const nuevaTalla = input.value.trim().toUpperCase();
        const prendasIndex = input.getAttribute('data-prenda');
        
        if (!nuevaTalla) {
            alert('Por favor ingresa el nombre de la talla');
            return;
        }

        const tallaGroup = document.createElement('div');
        tallaGroup.className = 'talla-group';
        tallaGroup.setAttribute('data-talla', nuevaTalla);
        tallaGroup.setAttribute('data-prenda', prendasIndex);
        
        tallaGroup.innerHTML = `
            <div class="talla-header">
                <label class="talla-label">${nuevaTalla}</label>
                <button type="button" class="btn-eliminar-talla" title="Eliminar talla">
                    ✕
                </button>
            </div>
            <input type="number" 
                   name="cantidades[${prendasIndex}][${nuevaTalla}]" 
                   class="talla-input" 
                   min="0" 
                   value="0" 
                   placeholder="0">
        `;

        const tallasGrid = input.closest('.tallas-actions').previousElementSibling;
        tallasGrid.appendChild(tallaGroup);
        input.value = '';
        input.focus();
    }

    /**
     * Obtiene datos de prendas con cantidades
     */
    obtenerDatos() {
        const prendas = [];
        const cards = this.container.querySelectorAll('.prenda-card');

        cards.forEach((card, index) => {
            const tallasInputs = card.querySelectorAll('.talla-input');
            const cantidadesPorTalla = {};
            
            tallasInputs.forEach(input => {
                const talla = input.closest('.talla-group')?.getAttribute('data-talla');
                const cantidad = parseInt(input.value) || 0;
                if (talla && cantidad > 0) {
                    cantidadesPorTalla[talla] = cantidad;
                }
            });
            
            if (Object.keys(cantidadesPorTalla).length > 0) {
                prendas.push({
                    index: index,
                    cantidades: cantidadesPorTalla
                });
            }
        });

        return prendas;
    }
}
