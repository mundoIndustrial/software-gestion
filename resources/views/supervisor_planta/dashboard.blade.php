@extends('supervisor_planta.layout')

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Dashboard - Supervisor de Planta</h1>
        <p>Bienvenido, {{ auth()->user()->name }}</p>
    </div>

    <div class="dashboard-grid">
        <!-- Tarjeta: Gestión de Órdenes -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="material-symbols-rounded">assignment</span>
            </div>
            <div class="card-content">
                <h3>Gestión de Órdenes</h3>
                <p>Administra y supervisa los pedidos en producción</p>
                <a href="{{ route('registros.index') }}" class="card-link">
                    Ir a Órdenes
                    <span class="material-symbols-rounded">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Tarjeta: Tableros -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="material-symbols-rounded">table_chart</span>
            </div>
            <div class="card-content">
                <h3>Tableros de Producción</h3>
                <p>Visualiza el estado de la producción en tiempo real</p>
                <a href="{{ route('tableros.index') }}" class="card-link">
                    Ver Tableros
                    <span class="material-symbols-rounded">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Tarjeta: Entregas -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="material-symbols-rounded">local_shipping</span>
            </div>
            <div class="card-content">
                <h3>Entregas</h3>
                <p>Gestiona las entregas de pedidos y bodega</p>
                <a href="{{ route('entrega.index', ['tipo' => 'pedido']) }}" class="card-link">
                    Ver Entregas
                    <span class="material-symbols-rounded">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Tarjeta: Insumos -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="material-symbols-rounded">inventory_2</span>
            </div>
            <div class="card-content">
                <h3>Gestión de Insumos</h3>
                <p>Administra materiales y recursos de producción</p>
                <a href="{{ route('insumos.materiales.index') }}" class="card-link">
                    Ver Insumos
                    <span class="material-symbols-rounded">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Tarjeta: Balanceo -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="material-symbols-rounded">schedule</span>
            </div>
            <div class="card-content">
                <h3>Balanceo de Líneas</h3>
                <p>Optimiza la distribución de trabajo en las líneas</p>
                <a href="{{ route('balanceo.index') }}" class="card-link">
                    Ver Balanceo
                    <span class="material-symbols-rounded">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Tarjeta: Bodega -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="material-symbols-rounded">inventory</span>
            </div>
            <div class="card-content">
                <h3>Bodega</h3>
                <p>Supervisa el inventario y órdenes de bodega</p>
                <a href="{{ route('bodega.index') }}" class="card-link">
                    Ver Bodega
                    <span class="material-symbols-rounded">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Tarjeta: Usuarios -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="material-symbols-rounded">group</span>
            </div>
            <div class="card-content">
                <h3>Gestión de Usuarios</h3>
                <p>Administra usuarios del sistema</p>
                <a href="{{ route('users.index') }}" class="card-link">
                    Ver Usuarios
                    <span class="material-symbols-rounded">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Tarjeta: Configuración -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="material-symbols-rounded">settings</span>
            </div>
            <div class="card-content">
                <h3>Configuración del Sistema</h3>
                <p>Configura parámetros y opciones del sistema</p>
                <a href="{{ route('configuracion.index') }}" class="card-link">
                    Ir a Configuración
                    <span class="material-symbols-rounded">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Información del rol -->
    <div class="role-info">
        <h2>Permisos del Rol Supervisor de Planta</h2>
        <div class="permissions-grid">
            <div class="permission-item">
                <span class="material-symbols-rounded">check_circle</span>
                <span>Acceso completo a todas las funciones</span>
            </div>
            <div class="permission-item">
                <span class="material-symbols-rounded">check_circle</span>
                <span>Gestión de órdenes y pedidos</span>
            </div>
            <div class="permission-item">
                <span class="material-symbols-rounded">check_circle</span>
                <span>Supervisión de producción en tiempo real</span>
            </div>
            <div class="permission-item">
                <span class="material-symbols-rounded">check_circle</span>
                <span>Gestión de insumos y materiales</span>
            </div>
            <div class="permission-item">
                <span class="material-symbols-rounded">check_circle</span>
                <span>Administración de entregas</span>
            </div>
            <div class="permission-item">
                <span class="material-symbols-rounded">check_circle</span>
                <span>Balanceo de líneas de producción</span>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-container {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    .dashboard-header {
        margin-bottom: 3rem;
    }

    .dashboard-header h1 {
        font-size: 2.5rem;
        margin: 0 0 0.5rem 0;
        color: var(--text-primary);
    }

    .dashboard-header p {
        font-size: 1.1rem;
        color: var(--text-secondary);
        margin: 0;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .dashboard-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 2rem;
        display: flex;
        gap: 1.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        border-color: #3498db;
    }

    .card-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #3498db, #2980b9);
        border-radius: 12px;
        flex-shrink: 0;
    }

    .card-icon span {
        color: white;
        font-size: 2rem;
    }

    .card-content {
        flex: 1;
    }

    .card-content h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.2rem;
        color: var(--text-primary);
    }

    .card-content p {
        margin: 0 0 1rem 0;
        color: var(--text-secondary);
        font-size: 0.95rem;
    }

    .card-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .card-link:hover {
        color: #2980b9;
        gap: 0.75rem;
    }

    .card-link span {
        font-size: 1.2rem;
    }

    .role-info {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 2rem;
        margin-top: 3rem;
    }

    .role-info h2 {
        margin: 0 0 1.5rem 0;
        font-size: 1.5rem;
        color: var(--text-primary);
    }

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .permission-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--bg-primary);
        border-radius: 8px;
        color: var(--text-primary);
    }

    .permission-item span:first-child {
        color: #27ae60;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }

        .dashboard-header h1 {
            font-size: 1.8rem;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .dashboard-card {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .card-icon {
            width: 50px;
            height: 50px;
        }

        .card-icon span {
            font-size: 1.5rem;
        }

        .permissions-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Tema oscuro */
    :root[data-theme="dark"] .dashboard-card {
        background: #1e293b;
        border-color: #334155;
    }

    :root[data-theme="dark"] .dashboard-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    :root[data-theme="dark"] .role-info {
        background: #1e293b;
        border-color: #334155;
    }

    :root[data-theme="dark"] .permission-item {
        background: #0f172a;
    }
</style>
@endsection
