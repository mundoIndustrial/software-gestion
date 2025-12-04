@extends('supervisor-pedidos.layout')

@section('title', 'Mi Perfil')
@section('page-title', 'Mi Perfil')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/profile.css') }}">
<style>
    .profile-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem;
    }

    .profile-message {
        padding: 1.25rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        animation: slideDown 0.3s ease-out;
    }

    .profile-message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .profile-message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .profile-header {
        display: flex;
        gap: 2rem;
        margin-bottom: 2rem;
        padding: 2rem;
        background: var(--bg-card);
        border-radius: 14px;
        border: 1px solid var(--border-color);
    }

    .profile-header-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .avatar-preview {
        position: relative;
        width: 150px;
        height: 150px;
        border-radius: 14px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f0f0;
    }

    .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-placeholder-large {
        font-size: 3.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }

    .avatar-edit-btn {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 48px;
        height: 48px;
        background: var(--primary-color);
        color: white;
        border: 2px solid white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: var(--transition);
    }

    .avatar-edit-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .profile-header-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .profile-header-info h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .profile-header-info p {
        color: var(--text-secondary);
        margin: 0.5rem 0 0 0;
        font-size: 0.95rem;
    }

    .profile-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        background: var(--bg-secondary);
    }

    .card-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }

    .card-title .material-symbols-rounded {
        font-size: 1.5rem;
        color: var(--primary-color);
    }

    .card-body {
        padding: 2rem;
    }

    .profile-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-label {
        font-weight: 500;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
    }

    .form-label .material-symbols-rounded {
        font-size: 1.2rem;
        color: var(--primary-color);
    }

    .form-input,
    .form-textarea {
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 0.95rem;
        color: var(--text-primary);
        background: var(--bg-input);
        transition: var(--transition);
    }

    .form-input:focus,
    .form-textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .form-textarea {
        resize: vertical;
    }

    .char-counter {
        font-size: 0.8rem;
        color: var(--text-secondary);
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }

    .btn-save {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 0.875rem 2rem;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-save:hover {
        background: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    }

    .btn-save:disabled {
        background: #95a5a6;
        cursor: not-allowed;
        transform: none;
    }

    @media (max-width: 768px) {
        .profile-container {
            padding: 1rem;
        }

        .profile-header {
            flex-direction: column;
            text-align: center;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .profile-header-info {
            align-items: center;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
        }

        .avatar-placeholder-large {
            font-size: 2.8rem;
        }
    }
</style>
@endpush

@section('content')
<div class="profile-container">
    <!-- Mensajes de éxito/error -->
    <div id="profileMessage" class="profile-message" style="display: none;">
        <span class="material-symbols-rounded message-icon"></span>
        <span class="message-text"></span>
    </div>

    <!-- Header del Perfil -->
    <div class="profile-header">
        <div class="profile-header-avatar">
            <div class="avatar-preview" id="avatarPreview">
                @if($user->avatar)
                    <img src="{{ asset('storage/supervisores/' . $user->avatar) }}" alt="Avatar" id="avatarImage" class="avatar-img">
                @else
                    <div class="avatar-placeholder-large">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                @endif
                <!-- Botón flotante para editar avatar -->
                <label for="avatarInput" class="avatar-edit-btn" title="Cambiar foto">
                    <span class="material-symbols-rounded">photo_camera</span>
                </label>
                <input type="file" id="avatarInput" accept="image/*" style="display: none;">
            </div>
        </div>
        <div class="profile-header-info">
            <h1>{{ $user->name }}</h1>
            <p>{{ $user->email }}</p>
        </div>
    </div>

    <!-- Tarjeta de Información Personal -->
    <div class="profile-card">
        <div class="card-header">
            <h2 class="card-title">
                <span class="material-symbols-rounded">person</span>
                Información Personal
            </h2>
        </div>
        <div class="card-body profile-section">
            <form id="profileForm" class="profile-form">
                @csrf
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name" class="form-label">
                            <span class="material-symbols-rounded">badge</span>
                            Nombre Completo *
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-input" 
                               value="{{ $user->name }}" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">
                            <span class="material-symbols-rounded">email</span>
                            Correo Electrónico *
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-input" 
                               value="{{ $user->email }}" 
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono" class="form-label">
                            <span class="material-symbols-rounded">phone</span>
                            Teléfono
                        </label>
                        <input type="tel" 
                               id="telefono" 
                               name="telefono" 
                               class="form-input" 
                               value="{{ $user->telefono ?? '' }}"
                               placeholder="Ej: 3001234567">
                    </div>

                    <div class="form-group">
                        <label for="ciudad" class="form-label">
                            <span class="material-symbols-rounded">location_city</span>
                            Ciudad
                        </label>
                        <input type="text" 
                               id="ciudad" 
                               name="ciudad" 
                               class="form-input" 
                               value="{{ $user->ciudad ?? '' }}"
                               placeholder="Ej: Pereira">
                    </div>
                </div>

                <div class="form-group">
                    <label for="departamento" class="form-label">
                        <span class="material-symbols-rounded">map</span>
                        Departamento
                    </label>
                    <input type="text" 
                           id="departamento" 
                           name="departamento" 
                           class="form-input" 
                           value="{{ $user->departamento ?? '' }}"
                           placeholder="Ej: Risaralda">
                </div>

                <div class="form-group">
                    <label for="bio" class="form-label">
                        <span class="material-symbols-rounded">description</span>
                        Biografía
                    </label>
                    <textarea id="bio" 
                              name="bio" 
                              class="form-textarea" 
                              rows="4"
                              maxlength="500"
                              placeholder="Cuéntanos un poco sobre ti...">{{ $user->bio ?? '' }}</textarea>
                    <span class="char-counter">
                        <span id="bioCounter">{{ strlen($user->bio ?? '') }}</span>/500 caracteres
                    </span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save" id="submitBtn">
                        <span class="material-symbols-rounded">save</span>
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Contador de caracteres para biografía
        const bioTextarea = document.getElementById('bio');
        if (bioTextarea) {
            bioTextarea.addEventListener('input', function() {
                const counter = document.getElementById('bioCounter');
                if (counter) {
                    counter.textContent = this.value.length;
                }
            });
        }

        // Manejo de avatar
        const avatarInput = document.getElementById('avatarInput');
        if (avatarInput) {
            avatarInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const preview = document.getElementById('avatarPreview');
                        if (preview) {
                            preview.innerHTML = `
                                <img src="${event.target.result}" class="avatar-img" id="avatarImage">
                                <label for="avatarInput" class="avatar-edit-btn" title="Cambiar foto">
                                    <span class="material-symbols-rounded">photo_camera</span>
                                </label>
                            `;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Envío del formulario
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = document.getElementById('submitBtn');
                
                if (!submitBtn) {
                    console.error('No se encontró el botón de envío');
                    return;
                }
                
                const originalText = submitBtn.innerHTML;
                
                // Si hay un archivo de avatar, añadirlo al FormData
                if (avatarInput && avatarInput.files.length > 0) {
                    formData.append('avatar', avatarInput.files[0]);
                }

                try {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="material-symbols-rounded">hourglass_empty</span><span>Guardando...</span>';

                    const response = await fetch('{{ route("supervisor-pedidos.update-profile") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showMessage('success', data.message);
                        // Limpiar input de avatar
                        if (avatarInput) {
                            avatarInput.value = '';
                        }
                        // Recargar la página después de 2 segundos para mostrar los cambios
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage('error', data.message || 'Error al guardar los cambios');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showMessage('error', 'Error de conexión al guardar los cambios');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }

        // Mostrar mensaje
        function showMessage(type, text) {
            const messageDiv = document.getElementById('profileMessage');
            if (!messageDiv) return;
            
            const icon = type === 'success' ? 'check_circle' : 'error';
            
            messageDiv.className = `profile-message ${type}`;
            messageDiv.innerHTML = `
                <span class="material-symbols-rounded message-icon">${icon}</span>
                <span class="message-text">${text}</span>
            `;
            messageDiv.style.display = 'flex';

            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 4000);
        }
    });
</script>
@endpush
@endsection
