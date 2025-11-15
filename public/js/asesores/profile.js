/**
 * PERFIL DE ASESOR - FUNCIONALIDAD
 * Manejo de formularios, avatar y validaciones
 */

document.addEventListener('DOMContentLoaded', function() {
    // ========================================
    // ELEMENTOS DEL DOM
    // ========================================
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarImage = document.getElementById('avatarImage');
    const deleteAvatarBtn = document.getElementById('deleteAvatarBtn');
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const bioTextarea = document.getElementById('bio');
    const bioCounter = document.getElementById('bioCounter');
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');

    // ========================================
    // MANEJO DE AVATAR
    // ========================================
    
    // Preview de imagen al seleccionar
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validar tipo de archivo
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showMessage('Por favor selecciona una imagen válida (JPG, PNG, GIF)', 'error');
                    avatarInput.value = ''; // Limpiar input
                    return;
                }
                
                // Validar tamaño (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showMessage('La imagen no debe superar los 2MB', 'error');
                    avatarInput.value = ''; // Limpiar input
                    return;
                }
                
                // Crear preview solamente (NO subir automáticamente)
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Si ya existe una imagen, actualizarla
                    if (avatarImage) {
                        avatarImage.src = e.target.result;
                    } else {
                        // Si no existe, crear nueva imagen
                        avatarPreview.innerHTML = `<img src="${e.target.result}" alt="Avatar Preview" id="avatarImage">`;
                    }
                    
                    // Agregar indicador de cambios pendientes
                    const avatarCard = document.querySelector('.avatar-card');
                    if (avatarCard && !avatarCard.querySelector('.pending-changes')) {
                        const pendingBadge = document.createElement('div');
                        pendingBadge.className = 'pending-changes';
                        pendingBadge.innerHTML = '<span class="material-symbols-rounded">info</span> Haz clic en "Guardar Cambios" para actualizar tu foto';
                        avatarCard.querySelector('.card-body').insertBefore(pendingBadge, avatarCard.querySelector('.avatar-section'));
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Eliminar avatar
    if (deleteAvatarBtn) {
        deleteAvatarBtn.addEventListener('click', handleDeleteAvatar);
    }
    
    function handleDeleteAvatar() {
        if (!confirm('¿Estás segura de que deseas eliminar tu foto de perfil?')) {
            return;
        }
        
        fetch('/asesores/profile/delete-avatar', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Obtener la primera letra del nombre
                const userName = document.querySelector('.user-name').textContent;
                const initials = userName.substring(0, 2).toUpperCase();
                
                // Reemplazar con placeholder
                avatarPreview.innerHTML = `
                    <div class="avatar-placeholder-large">
                        ${initials}
                    </div>
                `;
                
                // Eliminar botón de eliminar
                if (deleteAvatarBtn) {
                    deleteAvatarBtn.remove();
                }
                
                // Actualizar avatar en el header
                const headerAvatar = document.querySelector('.user-avatar');
                if (headerAvatar) {
                    headerAvatar.innerHTML = `
                        <div class="avatar-placeholder">
                            ${initials.substring(0, 1)}
                        </div>
                    `;
                }
                
                showMessage(data.message, 'success');
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error al eliminar el avatar', 'error');
        });
    }
    

    // ========================================
    // FORMULARIO DE PERFIL
    // ========================================
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(profileForm);
            
            // Agregar el archivo de avatar si se seleccionó uno nuevo
            if (avatarInput && avatarInput.files.length > 0) {
                formData.append('avatar', avatarInput.files[0]);
            }
            
            // Validaciones básicas
            const name = formData.get('name');
            const email = formData.get('email');
            
            if (!name || name.trim().length < 3) {
                showMessage('El nombre debe tener al menos 3 caracteres', 'error');
                return;
            }
            
            if (!email || !isValidEmail(email)) {
                showMessage('Por favor ingresa un correo electrónico válido', 'error');
                return;
            }
            
            // Enviar formulario
            fetch('/asesores/profile/update', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del servidor:', data); // Debug
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    
                    // Actualizar nombre en el header
                    const userName = document.querySelector('.user-name');
                    if (userName) {
                        userName.textContent = formData.get('name');
                    }
                    
                    // Actualizar placeholder del avatar si cambió el nombre
                    const avatarPlaceholder = document.querySelector('.avatar-placeholder-large');
                    if (avatarPlaceholder) {
                        const initials = formData.get('name').substring(0, 2).toUpperCase();
                        avatarPlaceholder.textContent = initials;
                    }
                    
                    // Actualizar avatar en el header si se subió uno nuevo
                    if (data.avatar_url) {
                        console.log('URL del avatar:', data.avatar_url); // Debug
                        
                        const avatarContainer = document.querySelector('.user-avatar');
                        console.log('Avatar container encontrado:', avatarContainer); // Debug
                        
                        if (avatarContainer) {
                            const timestamp = new Date().getTime();
                            avatarContainer.innerHTML = `<img src="${data.avatar_url}?t=${timestamp}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                            console.log('Avatar actualizado en el header'); // Debug
                        }
                        
                        // Limpiar el input de archivo
                        if (avatarInput) {
                            avatarInput.value = '';
                        }
                    }
                    
                    // Eliminar indicador de cambios pendientes
                    const pendingBadge = document.querySelector('.pending-changes');
                    if (pendingBadge) {
                        pendingBadge.remove();
                    }
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error al actualizar el perfil', 'error');
            });
        });
    }

    // ========================================
    // FORMULARIO DE CONTRASEÑA
    // ========================================
    
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(passwordForm);
            const password = formData.get('password');
            const passwordConfirmation = formData.get('password_confirmation');
            
            // Validar que se haya ingresado contraseña
            if (!password) {
                showMessage('Por favor ingresa una nueva contraseña', 'error');
                return;
            }
            
            // Validar longitud mínima
            if (password.length < 8) {
                showMessage('La contraseña debe tener al menos 8 caracteres', 'error');
                return;
            }
            
            // Validar que las contraseñas coincidan
            if (password !== passwordConfirmation) {
                showMessage('Las contraseñas no coinciden', 'error');
                return;
            }
            
            // Agregar datos del perfil al FormData
            formData.append('name', document.getElementById('name').value);
            formData.append('email', document.getElementById('email').value);
            
            // Enviar formulario
            fetch('/asesores/profile/update', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Contraseña actualizada exitosamente', 'success');
                    passwordForm.reset();
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error al actualizar la contraseña', 'error');
            });
        });
    }

    // ========================================
    // CONTADOR DE CARACTERES
    // ========================================
    
    if (bioTextarea && bioCounter) {
        bioTextarea.addEventListener('input', function() {
            const length = this.value.length;
            bioCounter.textContent = length;
            
            // Cambiar color si se acerca al límite
            if (length > 450) {
                bioCounter.style.color = '#dc3545';
            } else if (length > 400) {
                bioCounter.style.color = '#3b82f6';
            } else {
                bioCounter.style.color = '#6c757d';
            }
        });
    }

    // ========================================
    // TOGGLE DE CONTRASEÑA
    // ========================================
    
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('.material-symbols-rounded');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        });
    });

    // ========================================
    // FUNCIONES AUXILIARES
    // ========================================
    
    function showMessage(message, type) {
        const messageDiv = document.getElementById('profileMessage');
        const messageText = messageDiv.querySelector('.message-text');
        const messageIcon = messageDiv.querySelector('.message-icon');
        
        messageDiv.className = `profile-message ${type}`;
        messageText.textContent = message;
        
        if (type === 'success') {
            messageIcon.textContent = 'check_circle';
        } else {
            messageIcon.textContent = 'error';
        }
        
        messageDiv.style.display = 'flex';
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
        
        // Scroll al mensaje
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
