/**
 * Gestión de Roles del Personal
 * Maneja la visualización y actualización de roles para cada persona
 */

const PersonalRolesManager = {
    roles: [
        { id: 19, name: 'produccion' },
        { id: 20, name: 'administrativo' },
        { id: 21, name: 'mixto' }
    ],

    allPersonalData: [], // Almacenar todos los datos para búsqueda

    init() {
        this.attachEventListeners();
    },

    attachEventListeners() {
        // Botón para abrir el modal
        document.getElementById('verPersonalBtn')?.addEventListener('click', () => {
            this.openModal();
        });

        // Botón para cerrar el modal
        document.getElementById('btnCloseVerPersonal')?.addEventListener('click', () => {
            this.closeModal();
        });

        // Cerrar modal al hacer clic fuera del contenido
        document.getElementById('verPersonalModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'verPersonalModal') {
                this.closeModal();
            }
        });

        // Barra de búsqueda en tiempo real
        document.getElementById('personalSearchInput')?.addEventListener('input', (e) => {
            this.filterPersonal(e.target.value);
        });
    },

    openModal() {
        const modal = document.getElementById('verPersonalModal');
        if (modal) {
            modal.style.display = 'flex';
            this.loadPersonal();
        }
    },

    closeModal() {
        const modal = document.getElementById('verPersonalModal');
        if (modal) {
            modal.style.display = 'none';
            // Limpiar búsqueda al cerrar
            const searchInput = document.getElementById('personalSearchInput');
            if (searchInput) {
                searchInput.value = '';
            }
        }
    },

    loadPersonal() {
        fetch('/api/personal/list')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar el personal');
                }
                return response.json();
            })
            .then(data => {
                // Almacenar datos y ordenar por código de persona (de menor a mayor)
                this.allPersonalData = data.sort((a, b) => {
                    const codigoA = parseInt(a.codigo_persona) || 0;
                    const codigoB = parseInt(b.codigo_persona) || 0;
                    return codigoA - codigoB;
                });
                this.renderPersonalTable(this.allPersonalData);
                this.updateTotalPersonal(this.allPersonalData.length);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar el personal');
            });
    },

    filterPersonal(searchTerm) {
        const filtered = this.allPersonalData.filter(person => {
            const codigo = person.codigo_persona.toString().toLowerCase();
            const nombre = person.nombre_persona.toLowerCase();
            const search = searchTerm.toLowerCase();
            return codigo.includes(search) || nombre.includes(search);
        });
        
        this.renderPersonalTable(filtered);
        this.updateTotalPersonal(filtered.length, this.allPersonalData.length);
    },

    updateTotalPersonal(shown, total) {
        const totalSpan = document.getElementById('totalPersonal');
        if (totalSpan) {
            if (total !== undefined && shown !== total) {
                totalSpan.textContent = `${shown}/${total}`;
            } else {
                totalSpan.textContent = shown;
            }
        }
    },

    renderPersonalTable(personalList) {
        const tbody = document.getElementById('personalTableBody');
        tbody.innerHTML = '';

        if (personalList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="empty-cell">No hay personal registrado</td></tr>';
            return;
        }

        personalList.forEach(person => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${person.codigo_persona}</td>
                <td>${person.nombre_persona}</td>
                <td>
                    <select class="role-select" data-personal-id="${person.id}" data-current-rol="${person.id_rol || ''}">
                        <option value="">-- Sin seleccionar --</option>
                        ${this.roles.map(rol => `
                            <option value="${rol.id}" ${person.id_rol == rol.id ? 'selected' : ''}>
                                ${this.capitalize(rol.name)}
                            </option>
                        `).join('')}
                    </select>
                </td>
                <td>
                    <button class="btn-save-rol" data-personal-id="${person.id}">
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
        document.querySelectorAll('.btn-save-rol').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const personalId = e.currentTarget.dataset.personalId;
                const select = document.querySelector(`select[data-personal-id="${personalId}"]`);
                const rolId = select.value;
                this.saveRol(personalId, rolId || null);
            });
        });
    },

    saveRol(personalId, rolId) {
        const payload = {
            id_rol: rolId
        };

        fetch(`/api/personal/${personalId}/rol`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(payload)
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al actualizar el rol');
                }
                return response.json();
            })
            .then(data => {
                const personName = data.personal.nombre_persona;
                const rolName = data.personal.rol ? this.capitalize(data.personal.rol) : 'Sin rol';
                
                this.showSuccessToast(personName, rolName);
                // Recargar la tabla
                this.loadPersonal();
            })
            .catch(error => {
                console.error('Error:', error);
                this.showErrorToast('Error al actualizar el rol');
            });
    },

    showSuccessToast(personName, rolName) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification toast-success';
        toast.innerHTML = `
            <div class="toast-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <div class="toast-content">
                <div class="toast-title">Rol actualizado correctamente</div>
                <div class="toast-message">${personName} ahora tiene el rol <strong>${rolName}</strong></div>
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

    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    PersonalRolesManager.init();
});

