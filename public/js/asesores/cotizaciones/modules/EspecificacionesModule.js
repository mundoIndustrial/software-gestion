/**
 * EspecificacionesModule - Gestión de especificaciones de cotización
 * 
 * Single Responsibility: Manejo de especificaciones (disponibilidad, pago, régimen, etc.)
 * 
 * @module EspecificacionesModule
 */
class EspecificacionesModule {
    constructor() {
        this.modal = document.getElementById('modalEspecificaciones');
        this.especificaciones = {};
        this.categoriasMap = {
            'tbody_disponibilidad': 'disponibilidad',
            'tbody_pago': 'forma_pago',
            'tbody_regimen': 'regimen',
            'tbody_vendido': 'se_ha_vendido',
            'tbody_ultima_venta': 'ultima_venta',
            'tbody_flete': 'flete'
        };
    }

    /**
     * Inicializa el módulo
     */
    init() {
        this.setupEventListeners();
    }

    /**
     * Configura listeners
     */
    setupEventListeners() {
        // Botón para abrir modal
        const btnAbrirModal = document.querySelector('[onclick*="abrirModalEspecificaciones"]');
        if (btnAbrirModal) {
            btnAbrirModal.addEventListener('click', () => this.abrirModal());
        }

        // Cerrar modal al hacer click fuera
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.cerrarModal();
                }
            });
        }

        // Botones dentro del modal
        document.addEventListener('click', (e) => {
            if (e.target.textContent?.includes('GUARDAR') && e.target.closest('#modalEspecificaciones')) {
                this.guardarEspecificaciones();
            }
            if (e.target.textContent?.includes('CANCELAR') && e.target.closest('#modalEspecificaciones')) {
                this.cerrarModal();
            }
        });
    }

    /**
     * Abre el modal
     */
    abrirModal() {
        if (this.modal) {
            this.modal.style.display = 'flex';
            console.log(' Modal de especificaciones abierto');
        }
    }

    /**
     * Cierra el modal
     */
    cerrarModal() {
        if (this.modal) {
            this.modal.style.display = 'none';
            console.log(' Modal de especificaciones cerrado');
        }
    }

    /**
     * Guarda las especificaciones seleccionadas
     */
    guardarEspecificaciones() {
        this.especificaciones = this.extraerEspecificaciones();
        window.especificacionesSeleccionadas = this.especificaciones;

        console.log(' Especificaciones guardadas:', this.especificaciones);
        this.cerrarModal();
    }

    /**
     * Extrae las especificaciones del modal
     */
    extraerEspecificaciones() {
        const especificaciones = {};

        Object.entries(this.categoriasMap).forEach(([tbodyId, categoriaKey]) => {
            const tbody = document.getElementById(tbodyId);
            if (!tbody) return;

            const valoresSeleccionados = [];
            const filas = tbody.querySelectorAll('tr');

            filas.forEach((fila) => {
                const checkbox = fila.querySelector('input[type="checkbox"]');
                const label = fila.querySelector('label');
                const input = fila.querySelector('input[type="text"]');

                if (checkbox && checkbox.checked) {
                    let valor = '';

                    // Prioridad: label > input.value > '✓'
                    if (label) {
                        valor = label.textContent.trim();
                    } else if (input && input.value.trim()) {
                        valor = input.value.trim();
                    } else {
                        valor = '✓';
                    }

                    if (valor) {
                        valoresSeleccionados.push(valor);
                    }
                }
            });

            if (valoresSeleccionados.length > 0) {
                especificaciones[categoriaKey] = valoresSeleccionados;
            }
        });

        return especificaciones;
    }

    /**
     * Agrega una fila a una categoría de especificaciones
     */
    agregarFila(categoria) {
        const tbodyId = 'tbody_' + categoria;
        const tbody = document.getElementById(tbodyId);

        if (!tbody) {
            console.warn(` No encontrado tbody: ${tbodyId}`);
            return;
        }

        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td style="padding: 10px; border: 1px solid #ddd;">
                <input type="text" class="input-compact" placeholder="Escribe aquí" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
            </td>
            <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
            </td>
            <td style="padding: 10px; border: 1px solid #ddd;">
                <input type="text" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
            </td>
            <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(fila);
        console.log(` Fila agregada a: ${categoria}`);
    }

    /**
     * Obtiene las especificaciones actuales
     */
    getEspecificaciones() {
        return { ...this.especificaciones };
    }

    /**
     * Valida que haya especificaciones seleccionadas
     */
    validar() {
        const total = Object.keys(this.especificaciones).length;
        return {
            valid: total > 0,
            message: total === 0 ? 'Selecciona al menos una especificación' : ''
        };
    }

    /**
     * Limpia todas las especificaciones
     */
    limpiar() {
        this.especificaciones = {};
        window.especificacionesSeleccionadas = {};
        console.log(' Especificaciones limpias');
    }
}

// Exportar para uso global
const especificacionesModule = new EspecificacionesModule();
