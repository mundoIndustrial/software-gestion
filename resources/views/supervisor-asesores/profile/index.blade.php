@extends('layouts.supervisor-asesores')

@section('title', 'Mi Perfil')
@section('page-title', 'Mi Perfil')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <h1>Mi Perfil</h1>
        <p>Información y configuración de mi cuenta</p>
    </div>

    <div class="profile-container">
        <!-- Información Personal -->
        <div class="profile-section">
            <div class="section-header">
                <h2>Información Personal</h2>
            </div>

            <form id="profileForm" class="form-grid">
                <div class="form-group">
                    <label for="name">Nombre Completo</label>
                    <input type="text" id="name" name="name" value="{{ Auth::user()->name }}" disabled>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" value="{{ Auth::user()->email }}" disabled>
                </div>

                <div class="form-group">
                    <label for="phone">Teléfono</label>
                    <input type="tel" id="phone" name="phone" value="{{ Auth::user()->phone ?? '' }}" disabled>
                </div>

                <div class="form-group">
                    <label for="department">Departamento</label>
                    <input type="text" id="department" name="department" value="Supervisión de Asesores" disabled>
                </div>
            </form>
        </div>

        <!-- Avatar -->
        <div class="profile-section">
            <div class="section-header">
                <h2>Avatar</h2>
            </div>

            <div class="avatar-section">
                <div class="avatar-large">
                    @if(Auth::user()->avatar)
                        <img src="/storage/{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}">
                    @else
                        <span>{{ substr(Auth::user()->name, 0, 1) }}</span>
                    @endif
                </div>
                <p style="color: #666; margin-top: 1rem;">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="profile-section">
            <div class="section-header">
                <h2>Mis Estadísticas</h2>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-symbols-rounded">article</span>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">Cotizaciones Monitoreadas</p>
                        <p class="stat-value" id="statCotizaciones">0</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-symbols-rounded">shopping_bag</span>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">Pedidos Monitoreados</p>
                        <p class="stat-value" id="statPedidos">0</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-symbols-rounded">group</span>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">Asesores Bajo Supervisión</p>
                        <p class="stat-value" id="statAsesores">0</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-symbols-rounded">trending_up</span>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">Tasa de Conversión Promedio</p>
                        <p class="stat-value" id="statConversion">0%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seguridad -->
        <div class="profile-section">
            <div class="section-header">
                <h2>Seguridad</h2>
            </div>

            <div class="security-options">
                <div class="security-item">
                    <div>
                        <h3>Cambiar Contraseña</h3>
                        <p>Actualiza tu contraseña regularmente para mantener tu cuenta segura</p>
                    </div>
                    <button class="btn-secondary" onclick="abrirModalCambiarContraseña()">Cambiar</button>
                </div>

                <div class="security-item">
                    <div>
                        <h3>Sesiones Activas</h3>
                        <p>Controla las sesiones activas en diferentes dispositivos</p>
                    </div>
                    <button class="btn-secondary" onclick="verSesiones()">Ver Sesiones</button>
                </div>
            </div>
        </div>

        <!-- Preferencias -->
        <div class="profile-section">
            <div class="section-header">
                <h2>Preferencias</h2>
            </div>

            <div class="preferences-options">
                <div class="preference-item">
                    <div>
                        <h3>Notificaciones por Email</h3>
                        <p>Recibe notificaciones sobre cotizaciones y pedidos</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="emailNotifications" checked>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="preference-item">
                    <div>
                        <h3>Alertas de Actividad</h3>
                        <p>Notificaciones en tiempo real sobre cambios importantes</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="activityAlerts" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="profile-actions">
            <button class="btn-primary" onclick="guardarCambios()">
                <span class="material-symbols-rounded">save</span>
                Guardar Cambios
            </button>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn-danger">
                    <span class="material-symbols-rounded">logout</span>
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cambiar Contraseña -->
<div id="modalCambiarContraseña" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Cambiar Contraseña</h2>
            <button class="btn-close" onclick="cerrarModalCambiarContraseña()">&times;</button>
        </div>
        <form id="formCambiarContraseña" class="form-vertical">
            <div class="form-group">
                <label for="passwordAntigua">Contraseña Actual</label>
                <input type="password" id="passwordAntigua" name="password_antigua" required>
            </div>
            <div class="form-group">
                <label for="passwordNueva">Nueva Contraseña</label>
                <input type="password" id="passwordNueva" name="password_nueva" required>
            </div>
            <div class="form-group">
                <label for="passwordConfirmar">Confirmar Contraseña</label>
                <input type="password" id="passwordConfirmar" name="password_confirmar" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModalCambiarContraseña()">Cancelar</button>
                <button type="submit" class="btn-primary">Cambiar Contraseña</button>
            </div>
        </form>
    </div>
</div>

<style>
    .content-wrapper {
        padding: 2rem;
    }

    .content-header {
        margin-bottom: 2rem;
    }

    .content-header h1 {
        font-size: 2rem;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .content-header p {
        color: #666;
    }

    .profile-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 2rem;
    }

    .profile-section {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .section-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
    }

    .section-header h2 {
        margin: 0;
        font-size: 1.3rem;
    }

    .form-grid {
        padding: 1.5rem;
        display: grid;
        gap: 1rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
        font-size: 0.9rem;
    }

    .form-group input {
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    .form-group input:disabled {
        background: #f5f5f5;
        cursor: not-allowed;
    }

    .avatar-section {
        padding: 2rem;
        text-align: center;
    }

    .avatar-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        color: white;
        font-size: 3rem;
        font-weight: bold;
        overflow: hidden;
    }

    .avatar-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .stats-grid {
        padding: 1.5rem;
        display: grid;
        gap: 1rem;
    }

    .stat-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 6px;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stat-info {
        flex: 1;
    }

    .stat-label {
        margin: 0;
        font-size: 0.85rem;
        color: #666;
    }

    .stat-value {
        margin: 0.25rem 0 0 0;
        font-size: 1.3rem;
        font-weight: 700;
        color: #333;
    }

    .security-options,
    .preferences-options {
        padding: 1.5rem;
    }

    .security-item,
    .preference-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #eee;
    }

    .security-item:last-child,
    .preference-item:last-child {
        border-bottom: none;
    }

    .security-item h3,
    .preference-item h3 {
        margin: 0 0 0.25rem 0;
        color: #333;
    }

    .security-item p,
    .preference-item p {
        margin: 0;
        font-size: 0.9rem;
        color: #666;
    }

    .btn-secondary {
        padding: 0.5rem 1rem;
        background: #f5f5f5;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-secondary:hover {
        background: #eee;
        border-color: #999;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.4s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: #667eea;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .profile-actions {
        grid-column: 1 / -1;
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn-primary,
    .btn-danger {
        flex: 1;
        padding: 1rem;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 1rem;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
    }

    .btn-danger {
        background: #e74c3c;
        color: white;
    }

    .btn-danger:hover {
        background: #c0392b;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.4);
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 8px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        width: 90%;
        max-width: 500px;
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h2 {
        margin: 0;
    }

    .btn-close {
        background: none;
        border: none;
        color: white;
        font-size: 2rem;
        cursor: pointer;
    }

    .form-vertical {
        padding: 1.5rem;
    }

    .form-vertical .form-group {
        margin-bottom: 1rem;
    }

    .modal-footer {
        display: flex;
        gap: 1rem;
        padding: 1.5rem;
        border-top: 1px solid #eee;
    }

    .modal-footer .btn-secondary,
    .modal-footer .btn-primary {
        flex: 1;
        padding: 0.75rem;
    }

    @media (max-width: 768px) {
        .profile-container {
            grid-template-columns: 1fr;
        }

        .security-item,
        .preference-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .profile-actions {
            flex-direction: column;
        }
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        cargarEstadisticas();
        document.getElementById('formCambiarContraseña').addEventListener('submit', cambiarContraseña);
    });

    function cargarEstadisticas() {
        fetch('{{ route("supervisor-asesores.profile.stats") }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('statCotizaciones').textContent = data.cotizaciones_count || 0;
                document.getElementById('statPedidos').textContent = data.pedidos_count || 0;
                document.getElementById('statAsesores').textContent = data.asesores_count || 0;
                document.getElementById('statConversion').textContent = data.conversion_rate || '0%';
            })
            .catch(error => console.error('Error:', error));
    }

    function abrirModalCambiarContraseña() {
        document.getElementById('modalCambiarContraseña').style.display = 'block';
    }

    function cerrarModalCambiarContraseña() {
        document.getElementById('modalCambiarContraseña').style.display = 'none';
    }

    function cambiarContraseña(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch('{{ route("supervisor-asesores.profile.password-update") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Contraseña actualizada exitosamente');
                cerrarModalCambiarContraseña();
                form.reset();
            } else {
                alert('Error: ' + (data.message || 'No se pudo actualizar la contraseña'));
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function guardarCambios() {
        alert('Los cambios se guardarían aquí');
    }

    function verSesiones() {
        alert('Aquí se mostrarían las sesiones activas');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('modalCambiarContraseña');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>
@endpush
@endsection
