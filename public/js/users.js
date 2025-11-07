// ===== BÚSQUEDA EN TIEMPO REAL =====
document.getElementById('buscarUsuario').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#tablaUsuariosBody .table-row');
    
    rows.forEach(row => {
        const name = row.querySelector('.user-info span')?.textContent.toLowerCase() || '';
        const email = row.querySelectorAll('.table-cell')[2]?.textContent.toLowerCase() || '';
        
        if (name.includes(searchTerm) || email.includes(searchTerm)) {
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
    document.getElementById('create_password').value = '';
    document.getElementById('create_role_id').value = '';
}

// ===== MODAL EDITAR USUARIO =====
function openEditModal(userId, name, email, roleId) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');
    
    // Configurar la acción del formulario
    form.action = `/users/${userId}`;
    
    // Llenar los campos
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role_id').value = roleId;
    
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
