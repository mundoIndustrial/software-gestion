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
    document.querySelectorAll('input[name="roles_ids[]"]').forEach(checkbox => {
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
                    const checkbox = document.getElementById(`edit_role_${roleId}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
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
});

