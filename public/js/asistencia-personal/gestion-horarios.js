/**
 * Gestión de Horarios por Roles
 * Maneja la visualización y actualización de horarios para cada rol
 */

const GestionHorariosManager = {
    allHorarios: [],

    init() {
        this.attachEventListeners();
    },

    attachEventListeners() {
        // Botón para abrir el modal
        document.getElementById('gestionHorariosBtn')?.addEventListener('click', () => {
            this.openModal();
        });

        // Botón para cerrar el modal
        document.getElementById('btnCloseGestionHorarios')?.addEventListener('click', () => {
            this.closeModal();
        });

        // Cerrar modal al hacer clic fuera del contenido
        document.getElementById('gestionHorariosModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'gestionHorariosModal') {
                this.closeModal();
            }
        });
    },

    openModal() {
        const modal = document.getElementById('gestionHorariosModal');
        if (modal) {
            modal.style.display = 'flex';
            this.loadHorarios();
        }
    },

    closeModal() {
        const modal = document.getElementById('gestionHorariosModal');
        if (modal) {
            modal.style.display = 'none';
        }
    },

    loadHorarios() {
        fetch('/api/horarios/list')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar los horarios');
                }
                return response.json();
            })
            .then(data => {
                this.allHorarios = data;
                this.renderHorariosTable(data);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los horarios');
            });
    },

    renderHorariosTable(horarios) {
        const tbody = document.getElementById('horariosTableBody');
        tbody.innerHTML = '';

        if (horarios.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-cell">No hay horarios registrados</td></tr>';
            return;
        }

        horarios.forEach(horario => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${this.capitalize(horario.nombre_rol)}</strong></td>
                <td>
                    <input type="time" class="hora-input" data-horario-id="${horario.id}" data-campo="entrada_manana" value="${horario.entrada_manana || ''}" />
                </td>
                <td>
                    <input type="time" class="hora-input" data-horario-id="${horario.id}" data-campo="salida_manana" value="${horario.salida_manana || ''}" />
                </td>
                <td>
                    <input type="time" class="hora-input" data-horario-id="${horario.id}" data-campo="entrada_tarde" value="${horario.entrada_tarde || ''}" />
                </td>
                <td>
                    <input type="time" class="hora-input" data-horario-id="${horario.id}" data-campo="salida_tarde" value="${horario.salida_tarde || ''}" />
                </td>
                <td>
                    <input type="time" class="hora-input" data-horario-id="${horario.id}" data-campo="entrada_sabado" value="${horario.entrada_sabado || ''}" />
                </td>
                <td>
                    <input type="time" class="hora-input" data-horario-id="${horario.id}" data-campo="salida_sabado" value="${horario.salida_sabado || ''}" />
                </td>
                <td>
                    <button class="btn-save-horario" data-horario-id="${horario.id}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Guardar
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Agregar event listeners a los botones de guardar
        document.querySelectorAll('.btn-save-horario').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const horarioId = e.currentTarget.dataset.horarioId;
                this.saveHorario(horarioId);
            });
        });
    },

    saveHorario(horarioId) {
        const horarioObj = this.allHorarios.find(h => h.id == horarioId);
        if (!horarioObj) return;

        // Recolectar valores de los inputs
        const payload = {
            entrada_manana: document.querySelector(`input[data-horario-id="${horarioId}"][data-campo="entrada_manana"]`)?.value || null,
            salida_manana: document.querySelector(`input[data-horario-id="${horarioId}"][data-campo="salida_manana"]`)?.value || null,
            entrada_tarde: document.querySelector(`input[data-horario-id="${horarioId}"][data-campo="entrada_tarde"]`)?.value || null,
            salida_tarde: document.querySelector(`input[data-horario-id="${horarioId}"][data-campo="salida_tarde"]`)?.value || null,
            entrada_sabado: document.querySelector(`input[data-horario-id="${horarioId}"][data-campo="entrada_sabado"]`)?.value || null,
            salida_sabado: document.querySelector(`input[data-horario-id="${horarioId}"][data-campo="salida_sabado"]`)?.value || null,
        };

        // Convertir a formato HH:MM:SS
        Object.keys(payload).forEach(key => {
            if (payload[key]) {
                payload[key] = payload[key] + ':00';
            }
        });

        fetch(`/api/horarios/${horarioId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(payload)
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al actualizar el horario');
                }
                return response.json();
            })
            .then(data => {
                this.showSuccessToast(data.horario.nombre_rol);
                // Recargar la tabla
                this.loadHorarios();
            })
            .catch(error => {
                console.error('Error:', error);
                this.showErrorToast('Error al actualizar el horario');
            });
    },

    showSuccessToast(rolName) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification toast-success';
        toast.innerHTML = `
            <div class="toast-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <div class="toast-content">
                <div class="toast-title">Horario actualizado</div>
                <div class="toast-message">Horarios de <strong>${this.capitalize(rolName)}</strong> actualizados correctamente</div>
            </div>
            <button class="toast-close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        `;

        this.appendToast(toast);
    },

    showErrorToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification toast-error';
        toast.innerHTML = `
            <div class="toast-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <div class="toast-content">
                <div class="toast-title">Error</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        `;

        this.appendToast(toast);
    },

    appendToast(toast) {
        // Crear contenedor de toasts si no existe
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        toastContainer.appendChild(toast);

        // Event listener para cerrar
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.remove();
        });

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    },

    formatHora(hora) {
        if (!hora) return '';
        // Formato de entrada puede ser "HH:MM:SS" o "HH:MM"
        if (hora.includes(':')) {
            return hora.substring(0, 5); // Retorna "HH:MM"
        }
        return hora;
    },

    capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    GestionHorariosManager.init();
});
