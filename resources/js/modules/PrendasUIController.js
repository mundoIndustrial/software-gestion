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
                
                <div class="genero-selector" style="margin: 1rem 0; padding: 1rem; background: #f9fafb; border-radius: 4px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1f2937;">
                        Selecciona género(s):
                    </label>
                    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="genero[${index}][]" value="dama" class="genero-checkbox" data-prenda="${index}" style="cursor: pointer;">
                            <span style="font-size: 0.9rem; color: #374151;">Dama</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="genero[${index}][]" value="caballero" class="genero-checkbox" data-prenda="${index}" style="cursor: pointer;">
                            <span style="font-size: 0.9rem; color: #374151;">Caballero</span>
                        </label>
                    </div>
                </div>

                <div style="font-weight: 600; margin: 1rem 0 0.5rem 0; color: #1f2937;">TALLAS A COTIZAR</div>
                
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
                // Obtener datos de la prenda original
                const prendaOriginal = this.prendas[index] || {};
                
                // Construir descripción detallada con toda la información
                const descripcion = this.construirDescripcionCompleta(prendaOriginal);
                
                prendas.push({
                    index: index,
                    nombre_producto: prendaOriginal.nombre_producto || '',
                    descripcion: descripcion,
                    tela: prendaOriginal.variantes?.tela || null,
                    tela_referencia: prendaOriginal.variantes?.tela_referencia || null,
                    tela_id: prendaOriginal.variantes?.tela_id || null,
                    color: prendaOriginal.variantes?.color || null,
                    color_id: prendaOriginal.variantes?.color_id || null,
                    genero: prendaOriginal.variantes?.genero || null,
                    manga: prendaOriginal.variantes?.manga || null,
                    tipo_manga_id: prendaOriginal.variantes?.tipo_manga_id || null,
                    broche: prendaOriginal.variantes?.broche || null,
                    tipo_broche_id: prendaOriginal.variantes?.tipo_broche_id || null,
                    tiene_bolsillos: prendaOriginal.variantes?.tiene_bolsillos || false,
                    tiene_reflectivo: prendaOriginal.variantes?.tiene_reflectivo || false,
                    manga_obs: prendaOriginal.variantes?.manga_obs || null,
                    bolsillos_obs: prendaOriginal.variantes?.bolsillos_obs || null,
                    broche_obs: prendaOriginal.variantes?.broche_obs || null,
                    reflectivo_obs: prendaOriginal.variantes?.reflectivo_obs || null,
                    observaciones: prendaOriginal.variantes?.observaciones || null,
                    cantidades: cantidadesPorTalla,
                    fotos: prendaOriginal.fotos || [],
                    logos: prendaOriginal.logos || [],
                    telas: prendaOriginal.telas || [],
                });
            }
        });

        return prendas;
    }

    /**
     * Construye descripción completa de la prenda para persistencia
     * Formato similar al que se ve en pedidos históricos
     */
    construirDescripcionCompleta(prenda, tallas = {}) {
        const variantes = prenda.variantes || {};
        
        // Construir descripción con formato texto estructurado
        let descripcion = '';
        
        // 1. Nombre de prenda
        descripcion += `Prenda 1: ${prenda.nombre_producto || ''}\n`;
        
        // 2. Descripción general
        if (variantes.descripcion || variantes.observaciones) {
            descripcion += `Descripción: ${variantes.descripcion || variantes.observaciones}\n`;
        }
        
        // 3. Tela y referencia
        if (variantes.tela) {
            const tela = variantes.tela + (variantes.tela_referencia ? `\n  REF:${variantes.tela_referencia}` : '');
            descripcion += `Tela: ${tela}\n`;
        }
        
        // 4. Color
        if (variantes.color) {
            descripcion += `Color: ${variantes.color}\n`;
        }
        
        // 5. Género
        if (variantes.genero) {
            descripcion += `Género: ${variantes.genero}\n`;
        }
        
        // 6. Manga
        if (variantes.manga) {
            descripcion += `Manga: ${variantes.manga}`;
            if (variantes.manga_obs) {
                descripcion += ` - ${variantes.manga_obs}`;
            }
            descripcion += '\n';
        }
        
        // 7. Bolsillos
        if (variantes.tiene_bolsillos || variantes.bolsillos_obs) {
            descripcion += `Bolsillos: ${variantes.tiene_bolsillos ? 'SI' : 'NO'}`;
            if (variantes.bolsillos_obs) {
                descripcion += ` - ${variantes.bolsillos_obs}`;
            }
            descripcion += '\n';
        }
        
        // 8. Broche
        if (variantes.broche || variantes.broche_obs) {
            descripcion += `Broche: ${variantes.broche || 'N/A'}`;
            if (variantes.broche_obs) {
                descripcion += ` - ${variantes.broche_obs}`;
            }
            descripcion += '\n';
        }
        
        // 9. Reflectivo
        if (variantes.tiene_reflectivo || variantes.reflectivo_obs) {
            descripcion += `Reflectivo: ${variantes.tiene_reflectivo ? 'SI' : 'NO'}`;
            if (variantes.reflectivo_obs) {
                descripcion += ` - ${variantes.reflectivo_obs}`;
            }
            descripcion += '\n';
        }
        
        // 10. Tallas con cantidades
        if (Object.keys(tallas).length > 0) {
            const tallasCadena = Object.entries(tallas)
                .map(([talla, cant]) => `${talla}:${cant}`)
                .join(', ');
            descripcion += `Tallas: ${tallasCadena}\n`;
        }
        
        return descripcion.trim() || null;
    }
}
