// ===== BÚSQUEDA EN TIEMPO REAL =====
document.getElementById('buscarUsuario').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#tablaUsuariosBody .table-row');
    
    rows.forEach(row => {
        const name = row.querySelector('.user-info span')?.textContent.toLowerCase() || '';
        const cells = row.querySelectorAll('.table-cell');
        const email = cells[1]?.textContent.toLowerCase() || '';
        const telefono = cells[2]?.textContent.toLowerCase() || '';
        
        if (name.includes(searchTerm) || email.includes(searchTerm) || telefono.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// ===== SISTEMA DE FILTROS POR COLUMNAS =====
const columnFilters = {
    nombre: new Set(),
    email: new Set(),
    telefono: new Set(),
    rol: new Set(),
    fecha: new Set()
};

function initializeFilters() {
    const headers = document.querySelectorAll('.table-header-cell[data-column]');
    
    headers.forEach(header => {
        const column = header.getAttribute('data-column');
        const filterDropdown = header.querySelector('.filter-dropdown');
        
        // Obtener valores únicos de la columna
        const values = getUniqueColumnValues(column);
        
        // Crear contenido del filtro
        let filterHTML = `
            <div class="filter-dropdown-header">Filtrar por ${column}</div>
            <div class="filter-dropdown-content">
        `;
        
        values.forEach(value => {
            filterHTML += `
                <label class="filter-option">
                    <input type="checkbox" value="${value}" class="filter-checkbox" data-column="${column}">
                    <span>${value || '(vacío)'}</span>
                </label>
            `;
        });
        
        filterHTML += `
            </div>
            <div class="filter-dropdown-footer">
                <button type="button" class="filter-btn filter-btn-clear" onclick="event.stopPropagation(); clearColumnFilter('${column}')">Limpiar</button>
                <button type="button" class="filter-btn filter-btn-apply" onclick="event.stopPropagation(); applyFilters()">Aplicar</button>
            </div>
        `;
        
        filterDropdown.innerHTML = filterHTML;
        
        // Agregar event listeners a los checkboxes
        filterDropdown.querySelectorAll('.filter-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    columnFilters[column].add(this.value);
                    this.closest('.filter-option').classList.add('selected');
                } else {
                    columnFilters[column].delete(this.value);
                    this.closest('.filter-option').classList.remove('selected');
                }
            });
        });
        
        // Agregar event listener al dropdown para prevenir que se cierre
        filterDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Agregar event listeners a los headers para abrir/cerrar filtros
    headers.forEach(header => {
        header.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const filterIcon = this.querySelector('.filter-icon');
            const dropdown = this.querySelector('.filter-dropdown');
            
            // Solo abrir si se hace clic en el icono de filtro
            if (e.target === filterIcon || e.target.closest('.filter-icon')) {
                // Cerrar otros dropdowns
                document.querySelectorAll('.filter-dropdown.active').forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('active');
                    }
                });
                
                dropdown.classList.toggle('active');
            }
        });
    });
}

function getUniqueColumnValues(column) {
    const rows = document.querySelectorAll('#tablaUsuariosBody .table-row');
    const values = new Set();
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('.table-cell');
        let value = '';
        
        switch(column) {
            case 'nombre':
                value = row.querySelector('.user-info span')?.textContent.trim() || '';
                break;
            case 'email':
                value = cells[1]?.textContent.trim() || '';
                break;
            case 'telefono':
                value = cells[2]?.textContent.trim() || '';
                if (value === '—') value = '';
                break;
            case 'rol':
                const badges = cells[3]?.querySelectorAll('.badge');
                if (badges && badges.length > 0) {
                    badges.forEach(badge => {
                        values.add(badge.textContent.trim());
                    });
                    return;
                }
                value = cells[3]?.textContent.trim() || '';
                break;
            case 'fecha':
                value = cells[4]?.textContent.trim() || '';
                break;
        }
        
        if (value) {
            values.add(value);
        }
    });
    
    return Array.from(values).sort();
}

function applyFilters() {
    const rows = document.querySelectorAll('#tablaUsuariosBody .table-row');
    
    rows.forEach(row => {
        let showRow = true;
        
        // Verificar cada filtro activo
        for (const [column, selectedValues] of Object.entries(columnFilters)) {
            if (selectedValues.size === 0) continue; // Si no hay filtro, continuar
            
            const cells = row.querySelectorAll('.table-cell');
            let rowValue = '';
            
            switch(column) {
                case 'nombre':
                    rowValue = row.querySelector('.user-info span')?.textContent.trim() || '';
                    break;
                case 'email':
                    rowValue = cells[1]?.textContent.trim() || '';
                    break;
                case 'telefono':
                    rowValue = cells[2]?.textContent.trim() || '';
                    if (rowValue === '—') rowValue = '';
                    break;
                case 'rol':
                    const badges = cells[3]?.querySelectorAll('.badge');
                    if (badges && badges.length > 0) {
                        let hasMatch = false;
                        badges.forEach(badge => {
                            if (selectedValues.has(badge.textContent.trim())) {
                                hasMatch = true;
                            }
                        });
                        if (!hasMatch) {
                            showRow = false;
                        }
                        break;
                    }
                    rowValue = cells[3]?.textContent.trim() || '';
                    break;
                case 'fecha':
                    rowValue = cells[4]?.textContent.trim() || '';
                    break;
            }
            
            if (column !== 'rol' && !selectedValues.has(rowValue)) {
                showRow = false;
                break;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    });
    
    // Cerrar todos los dropdowns
    document.querySelectorAll('.filter-dropdown.active').forEach(d => {
        d.classList.remove('active');
    });
}

function clearColumnFilter(column) {
    columnFilters[column].clear();
    
    const filterDropdown = document.querySelector(`#filter-${column}`);
    filterDropdown.querySelectorAll('.filter-checkbox').forEach(checkbox => {
        checkbox.checked = false;
        checkbox.closest('.filter-option').classList.remove('selected');
    });
    
    applyFilters();
}

// Cerrar filtros al hacer clic fuera
document.addEventListener('click', function(e) {
    // Solo cerrar si no es un elemento del filtro
    if (!e.target.closest('.table-header-cell') && !e.target.closest('.filter-dropdown')) {
        document.querySelectorAll('.filter-dropdown.active').forEach(d => {
            d.classList.remove('active');
        });
    }
});

// ===== MODAL CREAR USUARIO =====
function openCreateModal() {
    const modal = document.getElementById('createModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCreateModal() {
    const modal = document.getElementById('createModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Limpiar formulario
    document.getElementById('create_name').value = '';
    document.getElementById('create_email').value = '';
    document.getElementById('create_telefono').value = '';
    document.getElementById('create_avatar').value = '';
    document.getElementById('create_password').value = '';
    
    // Resetear preview de avatar
    const previewDiv = document.getElementById('create_avatar_preview');
    previewDiv.innerHTML = '<i class="fas fa-user" style="font-size: 40px; color: #999;"></i>';
    
    // Desmarcar todos los checkboxes de roles
    document.querySelectorAll('input[name="roles_ids[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// ===== MODAL EDITAR USUARIO =====
function openEditModal(userId, name, email) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');
    
    // Configurar la acción del formulario
    form.action = `/users/${userId}`;
    
    // Desmarcar todos los checkboxes primero
    document.querySelectorAll('#editModal .role-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Fetch de datos del usuario
    fetch(`/users/${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al cargar datos del usuario');
            }
            return response.json();
        })
        .then(data => {
            // Llenar los campos
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_email').value = data.email;
            document.getElementById('edit_telefono').value = data.telefono || '';
            
            // Mostrar avatar si existe
            const previewDiv = document.getElementById('edit_avatar_preview');
            if (data.avatar) {
                previewDiv.innerHTML = `<img src="${data.avatar}" style="width: 100%; height: 100%; object-fit: cover;">`;
            } else {
                previewDiv.innerHTML = '<i class="fas fa-user" style="font-size: 40px; color: #999;"></i>';
            }
            
            // Limpiar input de archivo
            document.getElementById('edit_avatar').value = '';
            
            // Pre-seleccionar los roles usando IDs
            if (data.roles_ids && data.roles_ids.length > 0) {
                data.roles_ids.forEach(roleId => {
                    const checkbox = document.querySelector(`#editModal .role-checkbox[value="${roleId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
            
            // Actualizar roles seleccionados
            updateSelectedRoles('edit');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los datos del usuario');
        });
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// ===== MODAL CAMBIAR CONTRASEÑA =====
function openPasswordModal(userId, userName) {
    const modal = document.getElementById('passwordModal');
    const form = document.getElementById('passwordForm');
    
    // Configurar la acción del formulario
    form.action = `/users/${userId}/password`;
    
    // Mostrar nombre del usuario
    document.getElementById('password_user_name').textContent = userName;
    
    // Limpiar campo de contraseña
    document.getElementById('new_password').value = '';
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePasswordModal() {
    const modal = document.getElementById('passwordModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// ===== MODAL ELIMINAR USUARIO =====
function confirmDelete(userId, userName) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteForm');
    
    // Configurar la acción del formulario
    form.action = `/users/${userId}`;
    
    // Mostrar nombre del usuario
    document.getElementById('delete_user_name').textContent = userName;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// ===== CERRAR MODALES AL HACER CLIC FUERA =====
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// ===== CERRAR MODALES CON TECLA ESC =====
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const activeModal = document.querySelector('.modal.active');
        if (activeModal) {
            activeModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});

// ===== PREVIEW DE AVATARES =====
function previewCreateAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('create_avatar_preview');
            preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewEditAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('edit_avatar_preview');
            preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ===== AUTO-CERRAR ALERTAS DESPUÉS DE 5 SEGUNDOS =====
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
    
    // Inicializar filtros
    initializeFilters();
    
    // Inicializar buscador de roles
    initializeRolesSearch();
});



// ===== BUSCADOR DE ROLES =====
function initializeRolesSearch() {
    // Buscador para crear modal
    const createRolesSearch = document.getElementById('create_roles_search');
    if (createRolesSearch) {
        createRolesSearch.addEventListener('input', function(e) {
            filterRoles('create', e.target.value.toLowerCase());
        });
    }
    
    // Buscador para editar modal
    const editRolesSearch = document.getElementById('edit_roles_search');
    if (editRolesSearch) {
        editRolesSearch.addEventListener('input', function(e) {
            filterRoles('edit', e.target.value.toLowerCase());
        });
    }
    
    // Event listeners para checkboxes de roles
    document.querySelectorAll('.role-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const modalType = this.closest('.roles-selector').querySelector('.roles-search-input').id.includes('create') ? 'create' : 'edit';
            updateSelectedRoles(modalType);
        });
    });
}

function filterRoles(modalType, searchTerm) {
    const selector = modalType === 'create' ? '#createModal' : '#editModal';
    const modal = document.querySelector(selector);
    const roleItems = modal.querySelectorAll('.role-item');
    
    roleItems.forEach(item => {
        const roleName = item.getAttribute('data-role-name');
        if (roleName.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

function updateSelectedRoles(modalType) {
    const selector = modalType === 'create' ? '#createModal' : '#editModal';
    const modal = document.querySelector(selector);
    const selectedContainer = modal.querySelector(modalType === 'create' ? '#create_selected_roles' : '#edit_selected_roles');
    const checkboxes = modal.querySelectorAll('.role-checkbox:checked');
    
    selectedContainer.innerHTML = '';
    
    checkboxes.forEach(checkbox => {
        const roleItem = checkbox.closest('.role-item');
        const roleName = roleItem.querySelector('.role-name').textContent;
        const roleId = checkbox.value;
        
        const tag = document.createElement('div');
        tag.className = 'selected-role-tag';
        tag.innerHTML = `
            ${roleName}
            <span class="remove-role" onclick="removeRole(this, '${roleId}', '${modalType}')">×</span>
        `;
        selectedContainer.appendChild(tag);
    });
}

function removeRole(element, roleId, modalType) {
    const selector = modalType === 'create' ? '#createModal' : '#editModal';
    const modal = document.querySelector(selector);
    const checkbox = modal.querySelector(`.role-checkbox[value="${roleId}"]`);
    
    if (checkbox) {
        checkbox.checked = false;
        updateSelectedRoles(modalType);
    }
}
