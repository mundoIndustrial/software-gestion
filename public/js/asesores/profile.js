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
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const bioTextarea = document.getElementById('bio');
    const bioCounter = document.getElementById('bioCounter');
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');

    // ========================================
    // MANEJO DE AVATAR
    // ========================================
    
    // Preview de imagen al seleccionar y subir automáticamente
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
                
                // Crear preview local
                const reader = new FileReader();
                reader.onload = function(e) {
                    const dataUrl = e.target.result;
                    
                    // Actualizar avatar en el header
                    if (avatarImage) {
                        avatarImage.src = dataUrl;
                    } else {
                        // Si no existe, crear nueva imagen
                        avatarPreview.innerHTML = `<img src="${dataUrl}" alt="Avatar Preview" id="avatarImage" class="avatar-img">`;
                        // Re-agregar el botón flotante
                        const editBtn = document.querySelector('.avatar-edit-btn');
                        if (editBtn) {
                            avatarPreview.appendChild(editBtn);
                        }
                    }
                    
                    // Mostrar mensaje de carga
                    showMessage('Subiendo tu foto de perfil...', 'info');
                    
                    // Subir automáticamente
                    uploadAvatar(file);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ========================================
    // FORMULARIO DE PERFIL
    // ========================================
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(profileForm);
            
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
            submitProfileForm(formData);
        });
    }

    // ========================================
    // FORMULARIO DE CONTRASEÑA
    // ========================================
    
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;
            
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
            
            // Preparar datos
            const formData = new FormData(passwordForm);
            
            // Agregar datos del perfil (requeridos para actualizar el perfil)
            if (document.getElementById('name')) {
                formData.append('name', document.getElementById('name').value);
            }
            if (document.getElementById('email')) {
                formData.append('email', document.getElementById('email').value);
            }
            
            // Enviar formulario
            fetch('/asesores/perfil/update', {
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
                    showMessage('✓ Contraseña actualizada exitosamente', 'success');
                    passwordForm.reset();
                } else {
                    showMessage('✗ ' + (data.message || 'Error al actualizar contraseña'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error de conexión al actualizar contraseña', 'error');
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
    
    function uploadAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        
        // Obtener otros datos del formulario
        if (document.getElementById('name')) {
            formData.append('name', document.getElementById('name').value);
        }
        if (document.getElementById('email')) {
            formData.append('email', document.getElementById('email').value);
        }
        
        fetch('/asesores/perfil/update', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor:', data);
            
            if (data.success) {
                // Actualizar la URL de la imagen con la URL correcta del servidor
                if (data.avatar_url && avatarImage) {
                    // Agregar un parámetro de cache para forzar la recarga
                    const timestamp = new Date().getTime();
                    avatarImage.src = data.avatar_url + '?t=' + timestamp;
                    console.log('Avatar actualizado a:', data.avatar_url);
                }
                
                showMessage('✓ Foto de perfil actualizada exitosamente', 'success');
                
                // Limpiar el input de archivo
                if (avatarInput) {
                    avatarInput.value = '';
                }
            } else {
                showMessage('Error: ' + (data.message || 'No se pudo subir la foto'), 'error');
            }
        })
        .catch(error => {
            console.error('Error al subir avatar:', error);
            showMessage('Error de conexión al subir la foto', 'error');
        });
    }
    
    function submitProfileForm(formData) {
        fetch('/asesores/perfil/update', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor:', data);
            
            if (data.success) {
                showMessage('✓ ' + data.message, 'success');
                // NO recargar página - actualizar datos en tiempo real
                if (avatarInput) {
                    avatarInput.value = '';
                }
            } else {
                showMessage('✗ ' + (data.message || 'Error al actualizar'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error de conexión al actualizar perfil', 'error');
        });
    }
    
    function showMessage(message, type) {
        const messageDiv = document.getElementById('profileMessage');
        const messageText = messageDiv.querySelector('.message-text');
        const messageIcon = messageDiv.querySelector('.message-icon');
        
        messageDiv.className = `profile-message ${type}`;
        messageText.textContent = message;
        
        if (type === 'success') {
            messageIcon.textContent = 'check_circle';
        } else if (type === 'error') {
            messageIcon.textContent = 'error';
        } else if (type === 'info') {
            messageIcon.textContent = 'info';
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

