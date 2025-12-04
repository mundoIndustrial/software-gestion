/**
 * PERFIL DE CONTADOR - FUNCIONALIDAD
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
                        avatarImage.style.display = 'block';
                    }
                    
                    // Si no hay imagen, mostrar placeholder
                    const placeholder = avatarPreview.querySelector('.avatar-placeholder-large');
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    
                    // Trigger formchange
                    profileForm.dispatchEvent(new Event('change'));
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ========================================
    // VALIDACIÓN DE COUNTER DE BIO
    // ========================================
    if (bioTextarea) {
        bioTextarea.addEventListener('input', function() {
            bioCounter.textContent = this.value.length;
        });
    }

    // ========================================
    // TOGGLE DE VISIBILIDAD DE CONTRASEÑAS
    // ========================================
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('span');
            
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
    // MANEJO DE FORMULARIO DE PERFIL
    // ========================================
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Crear FormData para enviar archivos
            const formData = new FormData(profileForm);
            
            // Si hay archivo de avatar, agregarlo
            if (avatarInput && avatarInput.files[0]) {
                formData.set('avatar', avatarInput.files[0]);
            }
            
            // Enviar solicitud
            fetch('/contador/perfil/update', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    // Limpiar input de avatar
                    avatarInput.value = '';
                } else if (data.errors) {
                    const errorMessages = Object.values(data.errors).flat().join(', ');
                    showMessage(errorMessages, 'error');
                } else {
                    showMessage(data.message || 'Error al guardar cambios', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error al guardar cambios: ' + error.message, 'error');
            });
        });
    }

    // ========================================
    // MANEJO DE FORMULARIO DE CONTRASEÑA
    // ========================================
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;
            
            // Validaciones básicas
            if (!password && !passwordConfirmation) {
                showMessage('Por favor ingresa una contraseña', 'error');
                return;
            }
            
            if (password !== passwordConfirmation) {
                showMessage('Las contraseñas no coinciden', 'error');
                return;
            }
            
            if (password.length < 8) {
                showMessage('La contraseña debe tener al menos 8 caracteres', 'error');
                return;
            }
            
            // Crear FormData
            const formData = new FormData();
            formData.append('password', password);
            formData.append('password_confirmation', passwordConfirmation);
            formData.append('_token', document.querySelector('input[name="_token"]').value);
            
            // Enviar solicitud
            fetch('/contador/perfil/update', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    // Limpiar formulario
                    passwordForm.reset();
                } else if (data.errors) {
                    const errorMessages = Object.values(data.errors).flat().join(', ');
                    showMessage(errorMessages, 'error');
                } else {
                    showMessage(data.message || 'Error al actualizar contraseña', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error al actualizar contraseña: ' + error.message, 'error');
            });
        });
    }

    // ========================================
    // FUNCIÓN PARA MOSTRAR MENSAJES
    // ========================================
    function showMessage(message, type = 'info') {
        const messageEl = document.getElementById('profileMessage');
        const messageIcon = messageEl.querySelector('.message-icon');
        const messageText = messageEl.querySelector('.message-text');
        
        // Definir icono según tipo
        let icon = 'info';
        if (type === 'success') {
            icon = 'check_circle';
            messageEl.style.backgroundColor = 'var(--success-light, #d4edda)';
            messageEl.style.borderColor = 'var(--success, #28a745)';
            messageEl.style.color = 'var(--success, #28a745)';
        } else if (type === 'error') {
            icon = 'error';
            messageEl.style.backgroundColor = 'var(--danger-light, #f8d7da)';
            messageEl.style.borderColor = 'var(--danger, #dc3545)';
            messageEl.style.color = 'var(--danger, #dc3545)';
        } else {
            messageEl.style.backgroundColor = 'var(--info-light, #d1ecf1)';
            messageEl.style.borderColor = 'var(--info, #17a2b8)';
            messageEl.style.color = 'var(--info, #17a2b8)';
        }
        
        messageIcon.textContent = icon;
        messageText.textContent = message;
        messageEl.style.display = 'flex';
        
        // Ocultar después de 5 segundos
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 5000);
    }
});
