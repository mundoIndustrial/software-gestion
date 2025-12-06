/**
 * Módulo: CotizacionSearchUIController
 * Responsabilidad: Controlar la UI de búsqueda de cotizaciones
 * Principio SRP: solo maneja la UI, no la lógica de búsqueda
 * Principio DIP: recibe dependency injection del repository
 */
export class CotizacionSearchUIController {
    constructor(repository, config = {}) {
        this.repository = repository;
        this.searchInput = config.searchInput;
        this.hiddenInput = config.hiddenInput;
        this.dropdown = config.dropdown;
        this.selectedDiv = config.selectedDiv;
        this.selectedText = config.selectedText;

        this.initializeEventListeners();
    }

    /**
     * Inicializa listeners de eventos
     */
    initializeEventListeners() {
        this.searchInput.addEventListener('input', () => this.handleSearch());
        this.searchInput.addEventListener('focus', () => this.handleFocus());
        document.addEventListener('click', (e) => this.handleClickOutside(e));
    }

    /**
     * Maneja búsqueda
     */
    handleSearch() {
        const termino = this.searchInput.value;
        const resultados = this.repository.buscar(termino);
        this.mostrarDropdown(resultados);
    }

    /**
     * Maneja focus en input
     */
    handleFocus() {
        if (this.searchInput.value === '') {
            const resultados = this.repository.obtenerTodas();
            this.mostrarDropdown(resultados);
        }
    }

    /**
     * Cierra dropdown al hacer click afuera
     */
    handleClickOutside(e) {
        if (e.target !== this.searchInput && e.target !== this.dropdown) {
            this.dropdown.style.display = 'none';
        }
    }

    /**
     * Muestra opciones en dropdown
     */
    mostrarDropdown(opciones) {
        if (opciones.length === 0) {
            this.dropdown.innerHTML = 
                '<div style="padding: 1rem; color: #9ca3af; text-align: center;">No se encontraron cotizaciones</div>';
        } else {
            this.dropdown.innerHTML = opciones
                .map(cot => this.crearItemHTML(cot))
                .join('');
        }
        this.dropdown.style.display = 'block';
    }

    /**
     * Crea HTML de item de dropdown
     */
    crearItemHTML(cot) {
        return `
            <div class="cotizacion-item" data-id="${cot.id}">
                <div style="font-weight: 600; color: #1f2937;">${cot.numero}</div>
                <div style="font-size: 0.875rem; color: #6b7280;">
                    Cliente: <strong>${cot.cliente}</strong> | ${cot.prendasCount} prendas
                </div>
                ${cot.formaPago ? `<div style="font-size: 0.75rem; color: #9ca3af;">Forma de pago: ${cot.formaPago}</div>` : ''}
            </div>
        `;
    }

    /**
     * Selecciona una cotización
     */
    seleccionar(cotizacion, callback) {
        this.hiddenInput.value = cotizacion.id;
        this.searchInput.value = `${cotizacion.numero} - ${cotizacion.cliente}`;
        this.dropdown.style.display = 'none';
        this.mostrarSeleccionada(cotizacion);

        if (callback) {
            callback(cotizacion);
        }
    }

    /**
     * Muestra resumen de cotización seleccionada
     */
    mostrarSeleccionada(cotizacion) {
        this.selectedDiv.style.display = 'block';
        this.selectedText.textContent = 
            `${cotizacion.numero} - ${cotizacion.cliente} (${cotizacion.prendasCount} prendas)`;
    }

    /**
     * Obtiene ID de cotización seleccionada
     */
    obtenerSeleccionada() {
        return parseInt(this.hiddenInput.value) || null;
    }
}
