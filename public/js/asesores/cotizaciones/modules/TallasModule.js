/**
 * TallasModule - Gestión de tallas
 * 
 * Single Responsibility: Manejo de selección y almacenamiento de tallas
 * 
 * @module TallasModule
 */
class TallasModule {
    constructor() {
        this.tallasPorLetra = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        this.tallasDama = ['32', '34', '36', '38', '40', '42', '44'];
        this.tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42'];
    }

    /**
     * Actualiza el selector de tallas según el tipo
     */
    actualizarSelectTallas(selectElement) {
        const tipoSeleccionado = selectElement.value;
        const formCol = selectElement.closest('.form-col');

        const generoSelect = formCol.querySelector('.talla-genero-select');
        const modoSelect = formCol.querySelector('.talla-modo-select');
        const tallaBotones = formCol.querySelector('.talla-botones');
        const tallasSection = formCol.querySelector('.tallas-section');
        const rangoSelectors = formCol.querySelector('.talla-rango-selectors');

        if (tipoSeleccionado === 'letra') {
            this.configurarTallasLetra(formCol, generoSelect, modoSelect, tallaBotones, tallasSection, rangoSelectors);
        } else if (tipoSeleccionado === 'numero') {
            this.configurarTallasNumero(formCol, generoSelect, modoSelect, tallaBotones, tallasSection, rangoSelectors);
        } else {
            this.limpiarTallas(generoSelect, modoSelect, tallaBotones, tallasSection, rangoSelectors);
        }
    }

    /**
     * Configura tallas por letra
     */
    configurarTallasLetra(formCol, generoSelect, modoSelect, tallaBotones, tallasSection, rangoSelectors) {
        generoSelect.style.display = 'block';  // Mostrar género para letras también
        generoSelect.value = '';  // Resetear valor
        modoSelect.style.display = 'block';
        modoSelect.value = 'manual';
        tallaBotones.style.display = 'block';
        tallasSection.style.display = 'block';
        rangoSelectors.style.display = 'none';

        // Agregar event listener al modoSelect para cambios
        modoSelect.addEventListener('change', () => {
            if (modoSelect.value === 'rango') {
                rangoSelectors.style.display = 'flex';
                this.llenarSelectoresRangoLetras(formCol);
            } else {
                rangoSelectors.style.display = 'none';
                tallaBotones.style.display = 'block';
            }
        });

        this.crearBotonesTallas(formCol, this.tallasPorLetra);
    }

    /**
     * Configura tallas por número
     */
    configurarTallasNumero(formCol, generoSelect, modoSelect, tallaBotones, tallasSection, rangoSelectors) {
        generoSelect.style.display = 'block';
        modoSelect.style.display = 'block';
        tallaBotones.style.display = 'none';
        tallasSection.style.display = 'none';
        rangoSelectors.style.display = 'none';

        modoSelect.addEventListener('change', () => {
            if (modoSelect.value === 'rango') {
                rangoSelectors.style.display = 'flex';
                this.llenarSelectoresRango(formCol, generoSelect.value);
            } else {
                rangoSelectors.style.display = 'none';
            }
        });
    }

    /**
     * Limpia los selectores de tallas
     */
    limpiarTallas(generoSelect, modoSelect, tallaBotones, tallasSection, rangoSelectors) {
        generoSelect.style.display = 'none';
        modoSelect.style.display = 'none';
        tallaBotones.style.display = 'none';
        tallasSection.style.display = 'none';
        rangoSelectors.style.display = 'none';
    }

    /**
     * Crea botones para selección de tallas
     */
    crearBotonesTallas(formCol, tallas) {
        const container = formCol.querySelector('.talla-botones-container');
        if (!container) return;

        container.innerHTML = '';

        tallas.forEach(talla => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'talla-btn';
            btn.textContent = talla;
            btn.setAttribute('data-talla', talla);
            btn.style.cssText = `
                padding: 8px 16px;
                border: 2px solid #0066cc;
                border-radius: 6px;
                background: white;
                color: #0066cc;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.2s;
            `;

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleBotónTalla(btn);
            });

            container.appendChild(btn);
        });
    }

    /**
     * Toggle para botón de talla
     */
    toggleBotónTalla(btn) {
        const isSelected = btn.style.background === 'rgb(0, 102, 204)';

        btn.style.background = isSelected ? 'white' : '#0066cc';
        btn.style.color = isSelected ? '#0066cc' : 'white';
    }

    /**
     * Llena los selectores de rango
     */
    llenarSelectoresRango(formCol, genero) {
        const desdeSelect = formCol.querySelector('.talla-desde');
        const hastaSelect = formCol.querySelector('.talla-hasta');

        if (!desdeSelect || !hastaSelect) return;

        const tallas = genero === 'dama' ? this.tallasDama : this.tallasCaballero;

        desdeSelect.innerHTML = '<option value="">Desde</option>';
        hastaSelect.innerHTML = '<option value="">Hasta</option>';

        tallas.forEach(talla => {
            const optionDesde = document.createElement('option');
            optionDesde.value = talla;
            optionDesde.textContent = talla;
            desdeSelect.appendChild(optionDesde);

            const optionHasta = document.createElement('option');
            optionHasta.value = talla;
            optionHasta.textContent = talla;
            hastaSelect.appendChild(optionHasta);
        });
    }

    /**
     * Llena los selectores de rango para LETRAS
     */
    llenarSelectoresRangoLetras(formCol) {
        const desdeSelect = formCol.querySelector('.talla-desde');
        const hastaSelect = formCol.querySelector('.talla-hasta');

        if (!desdeSelect || !hastaSelect) return;

        desdeSelect.innerHTML = '<option value="">Desde</option>';
        hastaSelect.innerHTML = '<option value="">Hasta</option>';

        this.tallasPorLetra.forEach(talla => {
            const optionDesde = document.createElement('option');
            optionDesde.value = talla;
            optionDesde.textContent = talla;
            desdeSelect.appendChild(optionDesde);

            const optionHasta = document.createElement('option');
            optionHasta.value = talla;
            optionHasta.textContent = talla;
            hastaSelect.appendChild(optionHasta);
        });
    }

    /**
     * Agrega tallas desde rango
     */
    agregarTallasRango(btn) {
        const formCol = btn.closest('.form-col');
        const desdeSelect = formCol.querySelector('.talla-desde');
        const hastaSelect = formCol.querySelector('.talla-hasta');
        const tallasAgregadas = formCol.querySelector('.tallas-agregadas');
        const tallasHidden = formCol.querySelector('.tallas-hidden');

        const desde = parseInt(desdeSelect.value);
        const hasta = parseInt(hastaSelect.value);

        if (!desde || !hasta) {
            alert('Selecciona ambos valores');
            return;
        }

        if (desde > hasta) {
            alert('El valor "Desde" no puede ser mayor que "Hasta"');
            return;
        }

        const tallasSeleccionadas = [];
        for (let i = desde; i <= hasta; i++) {
            tallasSeleccionadas.push(i.toString());
        }

        this.mostrarTallasSeleccionadas(tallasAgregadas, tallasHidden, tallasSeleccionadas);
    }

    /**
     * Agrega tallas desde botones
     */
    agregarTallasSeleccionadas(btn) {
        const formCol = btn.closest('.form-col');
        const container = formCol.querySelector('.talla-botones-container');
        const tallasAgregadas = formCol.querySelector('.tallas-agregadas');
        const tallasHidden = formCol.querySelector('.tallas-hidden');

        const tallasBotones = container.querySelectorAll('.talla-btn');
        const tallasSeleccionadas = [];

        tallasBotones.forEach(btn => {
            if (btn.style.background === 'rgb(0, 102, 204)') {
                tallasSeleccionadas.push(btn.getAttribute('data-talla'));
            }
        });

        if (tallasSeleccionadas.length === 0) {
            alert('Selecciona al menos una talla');
            return;
        }

        this.mostrarTallasSeleccionadas(tallasAgregadas, tallasHidden, tallasSeleccionadas);
    }

    /**
     * Muestra las tallas seleccionadas
     */
    mostrarTallasSeleccionadas(tallasAgregadas, tallasHidden, tallasSeleccionadas) {
        tallasAgregadas.innerHTML = '';

        tallasSeleccionadas.forEach(talla => {
            const badge = document.createElement('span');
            badge.style.cssText = `
                background: #0066cc;
                color: white;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 0.85rem;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            `;

            const btnRemove = document.createElement('button');
            btnRemove.type = 'button';
            btnRemove.textContent = '×';
            btnRemove.style.cssText = `
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                font-size: 1.1rem;
                padding: 0;
                line-height: 1;
            `;
            btnRemove.addEventListener('click', () => badge.remove());

            badge.appendChild(document.createTextNode(talla + ' '));
            badge.appendChild(btnRemove);
            tallasAgregadas.appendChild(badge);
        });

        tallasHidden.value = JSON.stringify(tallasSeleccionadas);
    }

    /**
     * Obtiene las tallas seleccionadas de un producto
     */
    obtenerTallasSeleccionadas(productoCard) {
        const input = productoCard.querySelector('input[name*="tallas"]');
        if (!input || !input.value) {
            return [];
        }

        try {
            return JSON.parse(input.value);
        } catch {
            return input.value.split(',').map(t => t.trim()).filter(t => t);
        }
    }

    /**
     * Valida que se hayan seleccionado tallas
     */
    validarTallasSeleccionadas(productoCard) {
        const tallas = this.obtenerTallasSeleccionadas(productoCard);
        return {
            valid: tallas.length > 0,
            message: tallas.length === 0 ? 'Debes seleccionar al menos una talla' : ''
        };
    }
}

// Exportar para uso global
const tallasModule = new TallasModule();
