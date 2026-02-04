/**
 * Componente de Gestión de Tallas
 * Maneja toda la lógica relacionada con tallas de prendas
 * 
 * @class TallaComponent
 */

class TallaComponent {
    constructor() {
        // Definición de tallas disponibles por tipo y género
        this.tallasDefinicion = {
            LETRA: {
                hombre: ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'],
                mujer: ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'],
                unisex: ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL']
            },
            NUMERO: {
                hombre: ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'],
                mujer: ['2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30'],
                unisex: ['2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40']
            }
        };
    }

    // ============================================================
    // OBTENER TALLAS DISPONIBLES
    // ============================================================

    /**
     * Obtener tallas disponibles según tipo y género
     * @param {string} tipoTalla - 'LETRA' o 'NUMERO'
     * @param {string} genero - 'hombre', 'mujer', 'unisex'
     * @returns {Array<string>}
     */
    getTallasDisponibles(tipoTalla, genero = 'unisex') {
        if (!this.tallasDefinicion[tipoTalla]) {

            return [];
        }

        return this.tallasDefinicion[tipoTalla][genero] || [];
    }

    /**
     * Obtener tallas ya agregadas para un género
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} genero - Género
     * @returns {Array<string>}
     */
    getTallasAgregadas(prendaIndex, genero) {
        const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        if (!prendasCard) return [];

        const inputs = prendasCard.querySelectorAll(
            `.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"]`
        );

        return Array.from(inputs).map(input => input.dataset.talla);
    }

    /**
     * Obtener tipo de talla del otro género (si existe)
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} generoActual - Género actual
     * @returns {string|null} - 'LETRA', 'NUMERO' o null
     */
    getTipoTallaOtroGenero(prendaIndex, generoActual) {
        const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        if (!prendasCard) return null;

        const otroGenero = generoActual === 'hombre' ? 'mujer' : 'hombre';
        const inputsOtroGenero = prendasCard.querySelectorAll(
            `.talla-cantidad-genero-editable[data-genero="${otroGenero}"]`
        );

        if (inputsOtroGenero.length === 0) return null;

        const primerInput = inputsOtroGenero[0];
        return primerInput.dataset.tipoTalla || null;
    }

    // ============================================================
    // MODAL PARA AGREGAR TALLAS
    // ============================================================

    /**
     * Mostrar modal para agregar talla a una prenda
     * @param {number} prendaIndex - Índice de la prenda
     */
    async mostrarModalAgregarTalla(prendaIndex) {


        const tallasDisponibles = window.PedidoState?.getTallasDisponibles() || [];
        
        if (tallasDisponibles.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin tallas disponibles',
                text: 'No hay tallas disponibles en la cotización',
                confirmButtonColor: '#0066cc'
            });
            return;
        }

        const { value: talla } = await Swal.fire({
            title: 'Agregar Talla',
            html: `
                <div style="text-align: left;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Selecciona una talla:
                    </label>
                    <select id="swal-talla-select" class="swal2-input" style="width: 100%; padding: 0.5rem;">
                        <option value="">-- Selecciona --</option>
                        ${tallasDisponibles.map(t => `<option value="${t}">${t}</option>`).join('')}
                    </select>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Agregar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0066cc',
            preConfirm: () => {
                const select = document.getElementById('swal-talla-select');
                if (!select.value) {
                    Swal.showValidationMessage('Debes seleccionar una talla');
                    return false;
                }
                return select.value;
            }
        });

        if (talla) {
            this.agregarTallaAlFormulario(prendaIndex, talla);
        }
    }

    /**
     * Agregar talla al formulario de prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} talla - Talla a agregar
     */
    agregarTallaAlFormulario(prendaIndex, talla) {
        const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        if (!prendaCard) {

            return;
        }

        const tallasContainer = prendaCard.querySelector('.tallas-editable');
        if (!tallasContainer) {

            return;
        }

        // Verificar si la talla ya existe
        const tallaExistente = tallasContainer.querySelector(`input[data-talla="${talla}"]`);
        if (tallaExistente) {
            Swal.fire({
                icon: 'warning',
                title: 'Talla duplicada',
                text: `La talla ${talla} ya está agregada`,
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        // Crear nuevo input de talla
        const tallaItem = document.createElement('div');
        tallaItem.className = 'talla-item';
        tallaItem.innerHTML = `
            <strong style="min-width: 60px;">${talla}:</strong>
            <input 
                type="number" 
                class="talla-cantidad-editable" 
                data-prenda="${prendaIndex}" 
                data-talla="${talla}"
                min="0" 
                value="0"
                placeholder="Cantidad"
                style="flex: 1; max-width: 120px; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px;"
            >
            <button 
                type="button" 
                class="btn-quitar-talla" 
                onclick="window.TallaComponent.eliminarTalla(${prendaIndex}, '${talla}')"
                style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;"
            >
                ✕ Quitar
            </button>
        `;

        tallasContainer.appendChild(tallaItem);



        Swal.fire({
            icon: 'success',
            title: 'Talla agregada',
            text: `Talla ${talla} agregada correctamente`,
            timer: 1500,
            showConfirmButton: false
        });
    }

    // ============================================================
    // GESTIÓN DE TALLAS POR GÉNERO
    // ============================================================

    /**
     * Agregar talla para un género específico
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} genero - 'hombre' o 'mujer'
     */
    async agregarTallaParaGenero(prendaIndex, genero) {
        const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        if (!prendasCard) return;

        // Verificar si el otro género ya tiene tallas
        const tipoTallaOtroGenero = this.getTipoTallaOtroGenero(prendaIndex, genero);
        
        let tipoTalla;
        if (tipoTallaOtroGenero) {
            // Debe usar el mismo tipo que el otro género
            tipoTalla = tipoTallaOtroGenero;
            
            Swal.fire({
                icon: 'info',
                title: 'Tipo de talla fijado',
                text: `Debes usar tallas tipo ${tipoTalla} (igual que el otro género)`,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            // Puede elegir el tipo
            const { value: tipoSeleccionado } = await Swal.fire({
                title: 'Tipo de Talla',
                html: `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 1rem;">Selecciona el tipo de talla:</p>
                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <button type="button" class="swal2-confirm swal2-styled" id="btn-letra" style="flex: 1;">
                                 LETRA<br><small>(XS, S, M, L, XL...)</small>
                            </button>
                            <button type="button" class="swal2-confirm swal2-styled" id="btn-numero" style="flex: 1;">
                                 NÚMERO<br><small>(6, 8, 10, 12...)</small>
                            </button>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                didOpen: () => {
                    document.getElementById('btn-letra').addEventListener('click', () => {
                        Swal.clickConfirm();
                        Swal.close();
                        Swal.getConfirmButton().value = 'LETRA';
                    });
                    document.getElementById('btn-numero').addEventListener('click', () => {
                        Swal.clickConfirm();
                        Swal.close();
                        Swal.getConfirmButton().value = 'NUMERO';
                    });
                },
                preConfirm: () => {
                    return Swal.getConfirmButton().value;
                }
            });

            if (!tipoSeleccionado) return;
            tipoTalla = tipoSeleccionado;
        }

        // Mostrar modal para elegir método
        await this.agregarTallasPorMetodo(prendaIndex, genero, tipoTalla);
    }

    /**
     * Agregar tallas por método (manual o rango)
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} genero - Género
     * @param {string} tipoTalla - Tipo de talla
     */
    async agregarTallasPorMetodo(prendaIndex, genero, tipoTalla) {
        const { value: metodo } = await Swal.fire({
            title: 'Método de Selección',
            html: `
                <div style="text-align: left;">
                    <p style="margin-bottom: 1rem;">¿Cómo deseas agregar las tallas?</p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button type="button" class="swal2-confirm swal2-styled" id="btn-manual" style="flex: 1;">
                            ✋ MANUAL<br><small>Seleccionar una por una</small>
                        </button>
                        <button type="button" class="swal2-confirm swal2-styled" id="btn-rango" style="flex: 1;">
                             RANGO<br><small>Desde... hasta</small>
                        </button>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                document.getElementById('btn-manual').addEventListener('click', () => {
                    Swal.getConfirmButton().value = 'manual';
                    Swal.clickConfirm();
                    Swal.close();
                });
                document.getElementById('btn-rango').addEventListener('click', () => {
                    Swal.getConfirmButton().value = 'rango';
                    Swal.clickConfirm();
                    Swal.close();
                });
            },
            preConfirm: () => {
                return Swal.getConfirmButton().value;
            }
        });

        if (!metodo) return;

        const tallasDisponibles = this.getTallasDisponibles(tipoTalla, genero);
        const tallasActuales = this.getTallasAgregadas(prendaIndex, genero);

        if (metodo === 'manual') {
            await this.seleccionarTallasManual(prendaIndex, genero, tallasDisponibles, tallasActuales, tipoTalla);
        } else {
            await this.seleccionarTallasRango(prendaIndex, genero, tallasDisponibles, tallasActuales, tipoTalla);
        }
    }

    /**
     * Selección manual de tallas
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} genero - Género
     * @param {Array} tallasDisponibles - Tallas disponibles
     * @param {Array} tallasActuales - Tallas ya agregadas
     * @param {string} tipoTalla - Tipo de talla
     */
    async seleccionarTallasManual(prendaIndex, genero, tallasDisponibles, tallasActuales, tipoTalla) {
        const tallasParaSeleccionar = tallasDisponibles.filter(t => !tallasActuales.includes(t));

        if (tallasParaSeleccionar.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sin tallas disponibles',
                text: 'Ya agregaste todas las tallas disponibles',
                confirmButtonColor: '#0066cc'
            });
            return;
        }

        const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);

        const { value: tallasSeleccionadas } = await Swal.fire({
            title: `Seleccionar Tallas - ${generoLabel}`,
            html: `
                <div style="text-align: left;">
                    <p style="margin-bottom: 1rem;">Selecciona las tallas que deseas agregar:</p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.5rem; max-height: 300px; overflow-y: auto;">
                        ${tallasParaSeleccionar.map(talla => `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                                <input type="checkbox" value="${talla}" style="cursor: pointer;">
                                <span>${talla}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Agregar Seleccionadas',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0066cc',
            preConfirm: () => {
                const checkboxes = Swal.getPopup().querySelectorAll('input[type="checkbox"]:checked');
                const seleccionadas = Array.from(checkboxes).map(cb => cb.value);
                
                if (seleccionadas.length === 0) {
                    Swal.showValidationMessage('Debes seleccionar al menos una talla');
                    return false;
                }
                
                return seleccionadas;
            }
        });

        if (tallasSeleccionadas && tallasSeleccionadas.length > 0) {
            this.agregarTallasAlGenero(prendaIndex, genero, tallasSeleccionadas, tipoTalla);
        }
    }

    /**
     * Selección por rango de tallas
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} genero - Género
     * @param {Array} tallasDisponibles - Tallas disponibles
     * @param {Array} tallasActuales - Tallas ya agregadas
     * @param {string} tipoTalla - Tipo de talla
     */
    async seleccionarTallasRango(prendaIndex, genero, tallasDisponibles, tallasActuales, tipoTalla) {
        const tallasParaSeleccionar = tallasDisponibles.filter(t => !tallasActuales.includes(t));

        if (tallasParaSeleccionar.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sin tallas disponibles',
                text: 'Ya agregaste todas las tallas disponibles',
                confirmButtonColor: '#0066cc'
            });
            return;
        }

        const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);

        const { value: rango } = await Swal.fire({
            title: `Seleccionar Rango - ${generoLabel}`,
            html: `
                <div style="text-align: left;">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Desde:</label>
                        <select id="swal-desde" class="swal2-input" style="width: 100%;">
                            ${tallasParaSeleccionar.map(t => `<option value="${t}">${t}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Hasta:</label>
                        <select id="swal-hasta" class="swal2-input" style="width: 100%;">
                            ${tallasParaSeleccionar.map(t => `<option value="${t}">${t}</option>`).join('')}
                        </select>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Agregar Rango',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0066cc',
            preConfirm: () => {
                const desde = document.getElementById('swal-desde').value;
                const hasta = document.getElementById('swal-hasta').value;
                
                const indexDesde = tallasParaSeleccionar.indexOf(desde);
                const indexHasta = tallasParaSeleccionar.indexOf(hasta);
                
                if (indexDesde > indexHasta) {
                    Swal.showValidationMessage('El rango "Desde" debe ser menor o igual que "Hasta"');
                    return false;
                }
                
                return { desde, hasta };
            }
        });

        if (rango) {
            const indexDesde = tallasParaSeleccionar.indexOf(rango.desde);
            const indexHasta = tallasParaSeleccionar.indexOf(rango.hasta);
            const tallasEnRango = tallasParaSeleccionar.slice(indexDesde, indexHasta + 1);
            
            this.agregarTallasAlGenero(prendaIndex, genero, tallasEnRango, tipoTalla);
        }
    }

    /**
     * Agregar tallas al género
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} genero - Género
     * @param {Array} tallas - Tallas a agregar
     * @param {string} tipoTalla - Tipo de talla
     */
    agregarTallasAlGenero(prendaIndex, genero, tallas, tipoTalla) {
        const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        if (!prendasCard) return;

        const generoContainer = prendasCard.querySelector(`[data-genero-container="${genero}"]`);
        if (!generoContainer) {

            return;
        }

        const tallasContainer = generoContainer.querySelector('.tallas-genero-list');
        if (!tallasContainer) {

            return;
        }

        tallas.forEach(talla => {
            const tallaItem = document.createElement('div');
            tallaItem.className = 'talla-item';
            tallaItem.innerHTML = `
                <strong style="min-width: 60px;">${talla}:</strong>
                <input 
                    type="number" 
                    class="talla-cantidad-genero-editable" 
                    data-prenda="${prendaIndex}" 
                    data-genero="${genero}"
                    data-talla="${talla}"
                    data-tipo-talla="${tipoTalla}"
                    min="0" 
                    value="0"
                    placeholder="Cantidad"
                    style="flex: 1; max-width: 120px; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px;"
                >
                <button 
                    type="button" 
                    class="btn-quitar-talla" 
                    onclick="window.TallaComponent.eliminarTallaDelGenero(${prendaIndex}, '${genero}', '${talla}')"
                    style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;"
                >
                    ✕
                </button>
            `;

            tallasContainer.appendChild(tallaItem);
        });



        Swal.fire({
            icon: 'success',
            title: 'Tallas agregadas',
            text: `${tallas.length} talla(s) agregadas correctamente`,
            timer: 1500,
            showConfirmButton: false
        });
    }

    // ============================================================
    // ELIMINAR TALLAS
    // ============================================================

    /**
     * Eliminar talla simple
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} talla - Talla a eliminar
     */
    async eliminarTalla(prendaIndex, talla) {
        const result = await Swal.fire({
            title: '¿Eliminar talla?',
            text: `¿Deseas eliminar la talla ${talla}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
            if (prendaCard) {
                const input = prendaCard.querySelector(`.talla-cantidad-editable[data-talla="${talla}"]`);
                if (input) {
                    input.closest('.talla-item').remove();

                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Talla eliminada',
                        timer: 1000,
                        showConfirmButton: false
                    });
                }
            }
        }
    }

    /**
     * Eliminar talla de un género
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} genero - Género
     * @param {string} talla - Talla a eliminar
     */
    async eliminarTallaDelGenero(prendaIndex, genero, talla) {
        const result = await Swal.fire({
            title: '¿Eliminar talla?',
            text: `¿Eliminar talla ${talla} de ${genero}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
            if (prendasCard) {
                const input = prendasCard.querySelector(
                    `.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"][data-talla="${talla}"]`
                );
                
                if (input) {
                    input.closest('.talla-item').remove();

                    
                    // Si es gestor sin cotización, actualizar
                    if (window.gestorPrendaSinCotizacion) {
                        window.gestorPrendaSinCotizacion.eliminarTalla(prendaIndex, talla);
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Talla eliminada',
                        timer: 1000,
                        showConfirmButton: false
                    });
                }
            }
        }
    }

    // ============================================================
    // UTILIDADES
    // ============================================================

    /**
     * Obtener cantidades por talla de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @returns {Object} - { talla: cantidad }
     */
    getCantidadesPorTalla(prendaIndex) {
        const cantidades = {};
        const inputs = document.querySelectorAll(
            `.talla-cantidad-editable[data-prenda="${prendaIndex}"], 
             .talla-cantidad-genero-editable[data-prenda="${prendaIndex}"]`
        );

        inputs.forEach(input => {
            const talla = input.dataset.talla;
            const cantidad = parseInt(input.value) || 0;
            
            if (cantidad > 0) {
                cantidades[talla] = cantidad;
            }
        });

        return cantidades;
    }

    /**
     * Validar que haya al menos una talla con cantidad
     * @param {number} prendaIndex - Índice de la prenda
     * @returns {boolean}
     */
    validarTallas(prendaIndex) {
        const cantidades = this.getCantidadesPorTalla(prendaIndex);
        return Object.keys(cantidades).length > 0;
    }
}

// Crear instancia global
window.TallaComponent = new TallaComponent();
