@extends('asesores.layout')

@section('title', 'Mi Perfil')
@section('page-title', 'Mi Perfil')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/profile.css') }}">
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
                    <img src="{{ asset('storage/avatars/' . $user->avatar) }}" alt="Avatar" id="avatarImage" class="avatar-img">
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

    <!-- Grid de Secciones - Estilo Facebook -->
    <div class="profile-grid">
        <!-- Tarjeta Principal Unificada -->
        <div class="profile-card">
            <!-- Sección de Información Personal -->
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
                        <button type="submit" class="btn-save">
                            <span class="material-symbols-rounded">save</span>
                            <span>Guardar Cambios</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Divisor -->
            <div style="border-top: 1px solid var(--gray-100);"></div>

            <!-- Sección de Seguridad -->
            <div class="card-header">
                <h2 class="card-title">
                    <span class="material-symbols-rounded">lock</span>
                    Seguridad
                </h2>
            </div>
            <div class="card-body profile-section">
                <form id="passwordForm" class="profile-form">
                    @csrf
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <span class="material-symbols-rounded">vpn_key</span>
                            Nueva Contraseña
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-input" 
                                   placeholder="Dejar en blanco para no cambiar"
                                   minlength="8">
                            <button type="button" class="toggle-password" data-target="password">
                                <span class="material-symbols-rounded">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">
                            <span class="material-symbols-rounded">check_circle</span>
                            Confirmar Contraseña
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   class="form-input" 
                                   placeholder="Confirmar nueva contraseña"
                                   minlength="8">
                            <button type="button" class="toggle-password" data-target="password_confirmation">
                                <span class="material-symbols-rounded">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="password-requirements">
                        <p class="requirements-title">
                            <span class="material-symbols-rounded">info</span>
                            Requisitos de contraseña:
                        </p>
                        <ul class="requirements-list">
                            <li>Mínimo 8 caracteres</li>
                            <li>Se recomienda usar letras, números y símbolos</li>
                        </ul>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <span class="material-symbols-rounded">security</span>
                            <span>Actualizar Contraseña</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Divisor -->
            <div style="border-top: 1px solid var(--gray-100);"></div>

            <!-- Sección de Información de Cuenta -->
            <div class="card-header">
                <h2 class="card-title">
                    <span class="material-symbols-rounded">info</span>
                    Información de Cuenta
                </h2>
            </div>
            <div class="card-body profile-section">
                <div class="account-info">
                    <div class="info-item">
                        <span class="info-label">
                            <span class="material-symbols-rounded">calendar_today</span>
                            Miembro desde
                        </span>
                        <span class="info-value">{{ $user->created_at->format('d/m/Y') }}</span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">
                            <span class="material-symbols-rounded">update</span>
                            Última actualización
                        </span>
                        <span class="info-value">{{ $user->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">
                            <span class="material-symbols-rounded">badge</span>
                            Rol
                        </span>
                        <span class="info-value role-badge">Asesor</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fin de Tarjeta Principal Unificada -->
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/asesores/profile.js') }}"></script>
@endpush
