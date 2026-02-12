@extends('layouts.asesores')

@section('content')
<div class="container-fluid mt-4">
    <!-- Encabezado -->
    <div class="header-section">
        <div class="header-top">
            <h1 class="page-title">Mis Borradores</h1>
        </div>

        <!-- Filtros -->
        <form method="GET" action="{{ route('asesores.borradores.index') }}" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="cliente">Cliente:</label>
                    <input type="text" id="cliente" name="cliente" placeholder="Buscar por cliente..." 
                           value="{{ request('cliente') }}" class="filter-input">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">
                        <span class="material-symbols-rounded">search</span>
                        Buscar
                    </button>
                    <a href="{{ route('asesores.borradores.index') }}" class="btn-filter-reset">
                        <span class="material-symbols-rounded">clear</span>
                        Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $borradores->total() }}</div>
            <div class="stat-label">Borradores Totales</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $borradores->count() }}</div>
            <div class="stat-label">En Esta Página</div>
        </div>
    </div>

    <!-- Tabla de Borradores -->
    @if($borradores->count() > 0)
        <div class="table-container">
            <table class="ordenes-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Prendas</th>
                        <th>Prioridad</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($borradores as $orden)
                        <tr class="orden-row borrador">
                            <td class="id-cell">
                                <span class="badge-id">#{{ $orden->id }}</span>
                            </td>
                            <td class="cliente-cell">
                                <div class="cliente-info">
                                    <div class="cliente-nombre">{{ $orden->cliente }}</div>
                                    @if($orden->telefono)
                                        <div class="cliente-tel">{{ $orden->telefono }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="cantidad-cell">
                                <span class="badge">{{ $orden->cantidad_prendas ?? 0 }}</span>
                            </td>
                            <td class="prioridad-cell">
                                <span class="badge badge-{{ strtolower($orden->prioridad ?? 'baja') }}">
                                    {{ ucfirst($orden->prioridad ?? 'sin prioridad') }}
                                </span>
                            </td>
                            <td class="fecha-cell">
                                {{ $orden->created_at->format('d/m/Y h:i A') }}
                            </td>
                            <td class="actions-cell">
                                @php($editUrl = route('asesores.cotizaciones.edit-borrador', ['id' => $orden->id]))
                                <a href="{{ $editUrl }}" data-url="{{ $editUrl }}" class="btn-action btn-edit btn-editar-borrador">
                                    <span class="material-symbols-rounded">edit</span>
                                    Editar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="pagination-wrapper">
            {{ $borradores->links('pagination::bootstrap-4') }}
        </div>
    @else
        <div class="empty-state">
            <span class="material-symbols-rounded">folder_off</span>
            <h3>No hay borradores</h3>
            <p>Aún no has guardado ningún borrador. Crea uno desde el formulario.</p>
        </div>
    @endif
</div>

<style>
    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
    }

    .header-section {
        margin-bottom: 30px;
    }

    .header-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .page-title {
        font-size: 28px;
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    .filter-form {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .filter-row {
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }

    .filter-group {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-size: 13px;
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
    }

    .filter-input {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .filter-input:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }

    .filter-actions {
        display: flex;
        gap: 10px;
    }

    .btn-filter, .btn-filter-reset {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 15px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.3s;
    }

    .btn-filter:hover {
        background: #e9ecef;
        border-color: #adb5bd;
    }

    .btn-filter-reset:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid #d1d5db;
        background: #fff;
        color: #111827;
        transition: all 0.2s ease;
    }

    .btn-action:hover {
        background: #f3f4f6;
        border-color: #cbd5e1;
    }

    .btn-action .material-symbols-rounded {
        font-size: 18px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border: 1px solid #e9ecef;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #007bff;
    }

    .stat-label {
        font-size: 13px;
        color: #6c757d;
        margin-top: 8px;
    }

    .table-container {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .ordenes-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ordenes-table thead {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .ordenes-table th {
        padding: 15px;
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        color: #495057;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .ordenes-table tbody tr {
        border-bottom: 1px solid #dee2e6;
        transition: background 0.3s;
    }

    .ordenes-table tbody tr:hover {
        background: #f8f9fa;
    }

    .ordenes-table td {
        padding: 15px;
        font-size: 14px;
    }

    .id-cell {
        width: 80px;
    }

    .cliente-cell {
        min-width: 150px;
    }

    .cliente-info {
        display: flex;
        flex-direction: column;
    }

    .cliente-nombre {
        font-weight: 600;
        color: #333;
    }

    .cliente-tel {
        font-size: 12px;
        color: #6c757d;
        margin-top: 2px;
    }

    .badge-id {
        background: #e7f3ff;
        color: #0056b3;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: #e9ecef;
        color: #495057;
    }

    .badge-baja {
        background: #d1ecf1;
        color: #0c5460;
    }

    .badge-media {
        background: #fff3cd;
        color: #856404;
    }

    .badge-alta {
        background: #f8d7da;
        color: #721c24;
    }

    .badge-urgente {
        background: #f5c6cb;
        color: #721c24;
    }

    .monto {
        font-weight: 600;
        color: #28a745;
    }

    .fecha-vacia {
        color: #adb5bd;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        background: #e9ecef;
        color: #495057;
        transition: all 0.3s;
        font-size: 18px;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .btn-edit {
        background: #cfe2ff;
        color: #084298;
    }

    .btn-edit:hover {
        background: #bbdefb;
    }

    .btn-confirm {
        background: #d1e7dd;
        color: #0f5132;
    }

    .btn-confirm:hover {
        background: #badbcc;
    }

    .btn-delete {
        background: #f8d7da;
        color: #842029;
    }

    .btn-delete:hover {
        background: #f1b0b7;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 8px;
        border: 2px dashed #dee2e6;
    }

    .empty-state .material-symbols-rounded {
        font-size: 64px;
        color: #dee2e6;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: #495057;
        margin: 0 0 10px 0;
    }

    .empty-state p {
        color: #6c757d;
        margin: 0 0 20px 0;
    }

    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-content {
        background: white;
        margin: 10% auto;
        padding: 30px;
        border-radius: 8px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .modal-content h2 {
        margin: 0 0 15px 0;
        font-size: 20px;
        color: #333;
    }

    .modal-content p {
        margin: 0 0 20px 0;
        color: #666;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .btn-secondary {
        padding: 10px 20px;
        background: #e9ecef;
        color: #495057;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-secondary:hover {
        background: #dee2e6;
    }

    .btn-danger {
        padding: 10px 20px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    @media (max-width: 1200px) {
        .ordenes-table {
            font-size: 12px;
        }

        .ordenes-table th,
        .ordenes-table td {
            padding: 10px;
        }

        .header-top {
            flex-direction: column;
            gap: 15px;
        }

        .filter-row {
            flex-direction: column;
        }
    }

    @media (max-width: 768px) {
        .page-title {
            font-size: 22px;
        }

        .ordenes-table {
            font-size: 11px;
        }

        .action-buttons {
            gap: 4px;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            font-size: 16px;
        }

        .cliente-info {
            gap: 2px;
        }

        .cliente-nombre {
            font-size: 12px;
        }

        .cliente-tel {
            font-size: 10px;
        }
    }
</style>

@endsection

