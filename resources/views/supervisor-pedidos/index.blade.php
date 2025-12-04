@extends('supervisor-pedidos.layout')

@section('title', 'Supervisión de Pedidos')
@section('page-title', 'Supervisión de Pedidos')

@push('styles')
    <style>
        /* ====================== ESTILOS GENERALES ====================== */
        :root {
            --primary-color: #3498db;
            --primary-hover: #2980b9;
            --secondary-color: #ecf0f1;
            --danger-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --info-color: #9b59b6;
            --light-bg: #f5f7fa;
            --light-gray: #f8f9fa;
            --border-color: #e0e6ed;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --radius: 8px;
            --transition: all 0.3s ease;
        }

        .supervisor-pedidos-container {
            background: var(--light-bg);
            min-height: 100vh;
            padding: 2rem;
        }

        /* ====================== FILTROS ====================== */
        .filtros-section {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .filtros-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            align-items: flex-end;
        }

        .filtro-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filtro-group label {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filtro-select,
        .filtro-input {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.875rem;
            font-family: inherit;
            background: white;
            transition: var(--transition);
        }

        .filtro-select:focus,
        .filtro-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn-filtrar,
        .btn-limpiar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-filtrar {
            background: var(--primary-color);
            color: white;
        }

        .btn-filtrar:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .btn-limpiar {
            background: var(--secondary-color);
            color: var(--text-secondary);
        }

        .btn-limpiar:hover {
            background: #bdc3c7;
        }

        /* ====================== TABLA ====================== */
        .tabla-section {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .tabla-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .tabla-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .total-ordenes {
            background: var(--light-gray);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .tabla-responsive {
            overflow-x: auto;
        }

        .tabla-ordenes {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-ordenes thead {
            background: var(--light-gray);
            border-bottom: 2px solid var(--border-color);
        }

        .tabla-ordenes th {
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            font-size: 0.75rem;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .th-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            width: 100%;
        }

        .btn-filter-column {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--primary-color);
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            flex-shrink: 0;
            margin-left: auto;
        }

        .btn-filter-column:hover {
            color: var(--primary-hover);
            transform: scale(1.2);
        }

        .tabla-ordenes tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: background 0.3s ease;
        }

        .tabla-ordenes tbody tr:hover {
            background: var(--light-gray);
        }

        .tabla-ordenes td {
            padding: 1rem;
            font-size: 0.875rem;
            color: var(--text-primary);
        }

        .id-orden {
            color: var(--primary-color);
            font-weight: 700;
        }

        /* ====================== BADGES ====================== */
        .badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
        }

        .badge-no-iniciado {
            background: #ecf0f1;
            color: var(--text-secondary);
        }

        .badge-en-ejecución {
            background: #fff3cd;
            color: #856404;
        }

        .badge-en-ejeccion {
            background: #fff3cd;
            color: #856404;
        }

        .badge-entregado {
            background: #d4edda;
            color: #155724;
        }

        .badge-anulada {
            background: #f8d7da;
            color: #721c24;
        }

        /* ====================== ACCIONES ====================== */
        .acciones {
            text-align: center;
        }

        .acciones-group {
            display: flex;
            justify-content: center;
            gap: 0.4rem;
            flex-wrap: nowrap;
        }

        .ver-menu-container {
            position: relative;
            display: inline-block;
        }

        .ver-submenu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e0e6ed;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 100;
            min-width: 140px;
            margin-top: 0.5rem;
            animation: slideDown 0.2s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .submenu-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.7rem 1rem;
            border: none;
            background: none;
            cursor: pointer;
            color: #2c3e50;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-align: left;
        }

        .submenu-item:first-child {
            border-radius: 6px 6px 0 0;
        }

        .submenu-item:last-child {
            border-radius: 0 0 6px 6px;
        }

        .submenu-item:hover {
            background: #f0f4f8;
            color: var(--primary-color);
        }

        .submenu-item .material-symbols-rounded {
            font-size: 1rem;
        }

        .btn-accion {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1.2rem;
        }

        .btn-ver {
            background: #e8f4f8;
            color: var(--primary-color);
        }

        .btn-ver:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .btn-aprobar {
            background: #d4edda;
            color: #27ae60;
        }

        .btn-aprobar:hover {
            background: #27ae60;
            color: white;
            transform: scale(1.1);
        }

        .btn-pdf {
            background: #fef5e7;
            color: var(--danger-color);
            text-decoration: none;
        }

        .btn-pdf:hover {
            background: var(--danger-color);
            color: white;
            transform: scale(1.1);
        }

        .btn-anular {
            background: #fadbd8;
            color: #c0392b;
        }

        .btn-anular:hover {
            background: #c0392b;
            color: white;
            transform: scale(1.1);
        }

        .btn-anular:disabled {
            background: #bdc3c7;
            color: #7f8c8d;
            cursor: not-allowed;
            transform: none;
        }

        /* ====================== PAGINACIÓN ====================== */
        .paginacion {
            padding: 2rem 1.5rem;
            border-top: 1px solid #e0e6ed;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .pagination-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        /* Estilos para el componente de paginación personalizado */
        .pagination {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin: 0;
            padding: 0;
            list-style: none;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination .page-item {
            display: inline-block;
        }

        .pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0.5rem 0.75rem;
            border: 1px solid #e0e6ed;
            border-radius: 6px;
            text-decoration: none;
            color: #2c3e50;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
        }

        .pagination .page-link .material-symbols-rounded {
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pagination .page-item:not(.disabled) .page-link:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }

        /* Botones especiales (primero y último) */
        .pagination .page-item:first-child .page-link,
        .pagination .page-item:last-child .page-link {
            font-weight: 600;
        }

        .pagination .page-item.active .page-link {
            background: #3498db;
            color: white;
            border-color: #3498db;
            font-weight: 600;
            cursor: default;
        }

        .pagination .page-item.disabled .page-link {
            color: #bdc3c7;
            cursor: not-allowed;
            opacity: 0.5;
            background: #f8f9fa;
        }

        .pagination .page-item.disabled .page-link:hover {
            background: #f8f9fa;
            border-color: #e0e6ed;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .paginacion {
                padding: 1.5rem 1rem;
            }

            .pagination {
                gap: 0.25rem;
            }

            .pagination .page-link {
                min-width: 36px;
                height: 36px;
                padding: 0.4rem 0.6rem;
                font-size: 0.8rem;
            }

            .pagination .page-link .material-symbols-rounded {
                font-size: 1rem;
            }
        }

        .sin-datos {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .sin-datos .material-symbols-rounded {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .sin-datos p {
            margin: 0;
            font-size: 1rem;
        }

        /* ====================== MODALES ====================== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-lg {
            width: 90%;
            max-width: 900px;
        }

        .modal-anulacion {
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--text-primary);
        }

        .modal-anulacion .modal-header {
            flex-direction: column;
            text-align: center;
            padding-top: 3rem;
        }

        .header-icon {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 60px;
            background: #fff3cd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #f39c12;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .btn-close:hover {
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .advertencia-texto {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.875rem;
            resize: vertical;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .contador-caracteres {
            display: block;
            margin-top: 0.5rem;
            color: #95a5a6;
            font-size: 0.75rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: var(--text-secondary);
        }

        .btn-secondary:hover {
            background: #bdc3c7;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        /* ====================== DETALLES ORDEN ====================== */
        .orden-detalle {
            padding: 1rem 0;
        }

        .detalle-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detalle-col label {
            display: block;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .detalle-col strong {
            display: block;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .prendas-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .prendas-section h3 {
            margin: 0 0 1rem 0;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .tabla-prendas {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-prendas th {
            background: var(--light-gray);
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
        }

        .tabla-prendas td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
        }

        /* ====================== RESPONSIVE ====================== */
        @media (max-width: 768px) {
            .supervisor-pedidos-container {
                padding: 1rem;
            }

            .filtros-form {
                grid-template-columns: 1fr;
            }

            .tabla-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .tabla-ordenes {
                font-size: 0.75rem;
            }

            .tabla-ordenes th,
            .tabla-ordenes td {
                padding: 0.75rem 0.5rem;
            }

            .acciones-group {
                flex-wrap: wrap;
            }

            .modal-lg {
                width: 95%;
                max-width: none;
            }

            .modal-anulacion {
                width: 95%;
                max-width: none;
            }

            .detalle-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .tabla-ordenes th,
            .tabla-ordenes td {
                padding: 0.5rem 0.25rem;
                font-size: 0.7rem;
            }

            .btn-accion {
                width: 32px;
                height: 32px;
                font-size: 1rem;
            }

            .filtros-form {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .filtro-group label {
                font-size: 0.75rem;
            }
        }
    </style>
@endpush

@section('content')
<div class="supervisor-pedidos-container">
    <!-- Buscador General -->
    <div style="margin-bottom: 2rem;">
        <form method="GET" action="{{ route('supervisor-pedidos.index') }}" style="display: flex; gap: 1rem;">
            <div style="flex: 1;">
                <input type="text" 
                       name="busqueda" 
                       id="busqueda" 
                       class="filtro-input" 
                       placeholder="Buscar por pedido o cliente..." 
                       value="{{ request('busqueda') }}"
                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem;">
            </div>
            <button type="submit" class="btn-filtrar" style="padding: 0.75rem 2rem; display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-symbols-rounded">search</span>
                Buscar
            </button>
            <a href="{{ route('supervisor-pedidos.index') }}" class="btn-limpiar" style="padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-symbols-rounded">clear</span>
                Limpiar
            </a>
        </form>
    </div>

    <!-- Tabla de Órdenes -->
    <div class="tabla-section">
        <div class="tabla-header">
            <h2>Órdenes de Producción</h2>
            <span class="total-ordenes">Total: {{ $ordenes->total() }}</span>
        </div>

        @if($ordenes->count() > 0)
            <div class="tabla-responsive">
                <table class="tabla-ordenes">
                    <thead>
                        <tr>
                            <th>
                                <div class="th-wrapper">
                                    <span>ID ORDEN</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('id-orden')" title="Filtrar ID Orden">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>CLIENTE</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('cliente')" title="Filtrar Cliente">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>FECHA</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('fecha')" title="Filtrar Fecha">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>ESTADO</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('estado')" title="Filtrar Estado">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>ASESORA</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('asesora')" title="Filtrar Asesora">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>FORMA PAGO</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('forma-pago')" title="Filtrar Forma Pago">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th style="text-align: center; white-space: normal;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ordenes as $orden)
                            <tr class="orden-row" data-orden-id="{{ $orden->id }}" data-estado="{{ $orden->estado }}">
                                <td class="id-orden">
                                    <strong>#{{ $orden->numero_pedido }}</strong>
                                </td>
                                <td class="cliente">{{ $orden->cliente }}</td>
                                <td class="fecha">{{ \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d/m/Y') }}</td>
                                <td class="estado">
                                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $orden->estado)) }}">
                                        {{ $orden->estado }}
                                    </span>
                                </td>
                                <td class="asesora">{{ $orden->asesora?->name ?? 'N/A' }}</td>
                                <td class="forma-pago">{{ $orden->forma_de_pago ?? 'N/A' }}</td>
                                <td class="acciones">
                                    <div class="acciones-group">
                                        <!-- Ver Orden - Menu -->
                                        <div class="ver-menu-container">
                                            <button class="btn-accion btn-ver" 
                                                    title="Ver orden"
                                                    onclick="toggleVerMenu(event, {{ $orden->id }})">
                                                <span class="material-symbols-rounded">visibility</span>
                                            </button>
                                            <div class="ver-submenu" id="ver-menu-{{ $orden->id }}" style="display: none;">
                                                <button class="submenu-item" onclick="verOrdenComparar({{ $orden->id }})">
                                                    <span class="material-symbols-rounded">compare_arrows</span>
                                                    Comparar
                                                </button>
                                                <button class="submenu-item" onclick="verOrdenDetalles({{ $orden->id }})">
                                                    <span class="material-symbols-rounded">description</span>
                                                    Detalles
                                                </button>
                                                <a href="{{ route('supervisor-pedidos.pdf', $orden->id) }}" 
                                                   class="submenu-item"
                                                   title="Descargar PDF"
                                                   target="_blank">
                                                    <span class="material-symbols-rounded">picture_as_pdf</span>
                                                    Descargar PDF
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Aprobar Orden (Enviar a Producción) -->
                                        @if($orden->estado === 'No iniciado' && !$orden->aprobado_por_supervisor_en && !request()->filled('estado'))
                                            <button class="btn-accion btn-aprobar" 
                                                    title="Aprobar orden"
                                                    onclick="aprobarOrden({{ $orden->id }}, '{{ $orden->numero_pedido }}')">
                                                <span class="material-symbols-rounded">check_circle</span>
                                            </button>
                                        @endif

                                        <!-- Anular Orden -->
                                        @if($orden->estado !== 'Anulada' && !$orden->aprobado_por_supervisor_en && !request()->filled('estado'))
                                            <button class="btn-accion btn-anular" 
                                                    title="Anular orden"
                                                    onclick="abrirModalAnulacion({{ $orden->id }}, '{{ $orden->numero_pedido }}')">
                                                <span class="material-symbols-rounded">cancel</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="paginacion">
                {{ $ordenes->links('components.pagination') }}
            </div>
        @else
            <div class="sin-datos">
                <span class="material-symbols-rounded">inbox</span>
                <p>No hay órdenes que mostrar</p>
            </div>
        @endif
    </div>
</div>

<!-- Modal Filtro Dinámico -->
<div id="modalFiltro" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 90%; max-width: 400px;">
        <div class="modal-header">
            <h2 id="modalFiltroTitulo">Filtrar</h2>
            <button class="btn-close" onclick="cerrarModalFiltro()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="formFiltroColumna" onsubmit="aplicarFiltroColumna(event)">
                <div class="form-group" id="filtroContenido">
                    <!-- Contenido dinámico según la columna -->
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalFiltro()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" style="background: var(--primary-color); color: white;">
                        Aplicar Filtro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Orden -->
<div id="modalVerOrden" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>Detalle de Orden</h2>
            <button class="btn-close" onclick="cerrarModalVerOrden()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body" id="modalVerOrdenContent">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal Anulación -->
<div id="modalAnulacion" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-anulacion">
        <div class="modal-header">
            <div class="header-icon">
                <span class="material-symbols-rounded">warning</span>
            </div>
            <h2>¿Anular Orden <span id="ordenNumero"></span>?</h2>
        </div>

        <div class="modal-body">
            <p class="advertencia-texto">
                Esta acción cancelará la orden y no se podrá revertir. Por favor ingresa el motivo de la anulación.
            </p>

            <form id="formAnulacion" onsubmit="confirmarAnulacion(event)">
                @csrf
                <div class="form-group">
                    <label for="motivoAnulacion">Motivo de anulación *</label>
                    <textarea 
                        id="motivoAnulacion" 
                        name="motivo_anulacion" 
                        class="form-control" 
                        rows="4" 
                        placeholder="Ej: El cliente solicitó reembolso, error en precios..."
                        required
                        minlength="10"
                        maxlength="500">
                    </textarea>
                    <small class="contador-caracteres">
                        <span id="contadorActual">0</span>/500 caracteres
                    </small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalAnulacion()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <span class="material-symbols-rounded">delete</span>
                        Confirmar Anulación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .supervisor-pedidos-container {
        padding: 2rem;
        background: #f5f7fa;
        min-height: 100vh;
    }

    /* Filtros */
    .filtros-section {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .filtros-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: flex-end;
    }

    .filtro-group {
        display: flex;
        flex-direction: column;
    }

    .filtro-group label {
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        color: #2c3e50;
    }

    .filtro-select,
    .filtro-input {
        padding: 0.75rem;
        border: 1px solid #e0e6ed;
        border-radius: 6px;
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }

    .filtro-select:focus,
    .filtro-input:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .btn-filtrar,
    .btn-limpiar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-filtrar {
        background: #3498db;
        color: white;
    }

    .btn-filtrar:hover {
        background: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    }

    .btn-limpiar {
        background: #ecf0f1;
        color: #7f8c8d;
    }

    .btn-limpiar:hover {
        background: #bdc3c7;
    }

    /* Tabla */
    .tabla-section {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .tabla-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid #e0e6ed;
    }

    .tabla-header h2 {
        margin: 0;
        font-size: 1.25rem;
        color: #2c3e50;
    }

    .total-ordenes {
        background: #ecf0f1;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        color: #7f8c8d;
    }

    .tabla-responsive {
        overflow-x: auto;
    }

    .tabla-ordenes {
        width: 100%;
        border-collapse: collapse;
    }

    .tabla-ordenes thead {
        background: #f8f9fa;
        border-bottom: 2px solid #e0e6ed;
    }

    .tabla-ordenes th {
        padding: 1rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.875rem;
        color: #2c3e50;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tabla-ordenes tbody tr {
        border-bottom: 1px solid #e0e6ed;
        transition: background 0.3s ease;
    }

    .tabla-ordenes tbody tr:hover {
        background: #f8f9fa;
    }

    .tabla-ordenes td {
        padding: 1rem;
        font-size: 0.875rem;
        color: #555;
    }

    .id-orden {
        color: #3498db;
        font-weight: 600;
    }

    .estado {
        text-align: center;
    }

    .badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-no-iniciado {
        background: #ecf0f1;
        color: #7f8c8d;
    }

    .badge-en-ejecución {
        background: #fff3cd;
        color: #856404;
    }

    .badge-entregado {
        background: #d4edda;
        color: #155724;
    }

    .badge-anulada {
        background: #f8d7da;
        color: #721c24;
    }

    .acciones {
        text-align: center;
    }

    .acciones-group {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-accion {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1.2rem;
    }

    .btn-ver {
        background: #e8f4f8;
        color: #3498db;
    }

    .btn-ver:hover {
        background: #3498db;
        color: white;
        transform: scale(1.1);
    }

    .btn-pdf {
        background: #fef5e7;
        color: #e74c3c;
        text-decoration: none;
    }

    .btn-pdf:hover {
        background: #e74c3c;
        color: white;
        transform: scale(1.1);
    }

    .btn-anular {
        background: #fadbd8;
        color: #c0392b;
    }

    .btn-anular:hover {
        background: #c0392b;
        color: white;
        transform: scale(1.1);
    }

    /* Modales */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        max-height: 90vh;
        overflow-y: auto;
        animation: slideIn 0.3s ease;
    }

    .modal-lg {
        width: 90%;
        max-width: 1200px;
    }

    .modal-anulacion {
        width: 90%;
        max-width: 500px;
    }

    .modal-body-two-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        padding: 1.5rem;
        height: 70vh;
        max-height: 70vh;
        overflow: hidden;
    }

    .modal-column {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 0.5rem;
    }

    .modal-column::-webkit-scrollbar {
        width: 6px;
    }

    .modal-column::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .modal-column::-webkit-scrollbar-thumb {
        background: #bdc3c7;
        border-radius: 10px;
    }

    .modal-column::-webkit-scrollbar-thumb:hover {
        background: #95a5a6;
    }

    .modal-column h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 1rem 0;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e0e6ed;
        flex-shrink: 0;
    }

    .column-divider {
        border-left: 2px solid #e0e6ed;
        padding-left: 2rem;
    }

    @media (max-width: 1024px) {
        .modal-body-two-columns {
            grid-template-columns: 1fr;
            gap: 1.5rem;
            height: auto;
            max-height: none;
        }

        .modal-column {
            height: auto;
            overflow-y: visible;
            padding-right: 0;
        }

        .column-divider {
            border-left: none;
            border-top: 2px solid #e0e6ed;
            padding-left: 0;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
    }

    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e0e6ed;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h2 {
        margin: 0;
        color: #2c3e50;
    }

    .header-icon {
        position: absolute;
        top: -30px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 60px;
        background: #fff3cd;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #f39c12;
    }

    .modal-anulacion .modal-header {
        position: relative;
        padding-top: 3rem;
        flex-direction: column;
        text-align: center;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #7f8c8d;
        transition: color 0.3s ease;
    }

    .btn-close:hover {
        color: #2c3e50;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .advertencia-texto {
        color: #7f8c8d;
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #2c3e50;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e0e6ed;
        border-radius: 6px;
        font-family: inherit;
        font-size: 0.875rem;
        resize: vertical;
    }

    .form-control:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .contador-caracteres {
        display: block;
        margin-top: 0.5rem;
        color: #95a5a6;
        font-size: 0.75rem;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-secondary {
        background: #ecf0f1;
        color: #7f8c8d;
    }

    .btn-secondary:hover {
        background: #bdc3c7;
    }

    .btn-danger {
        background: #e74c3c;
        color: white;
    }

    .btn-danger:hover {
        background: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
    }

    @media (max-width: 768px) {
        .filtros-form {
            grid-template-columns: 1fr;
        }

        .tabla-ordenes {
            font-size: 0.75rem;
        }

        .tabla-ordenes th,
        .tabla-ordenes td {
            padding: 0.75rem 0.5rem;
        }

        .acciones-group {
            flex-wrap: wrap;
        }

        .modal-lg {
            width: 95%;
            max-width: none;
        }

        .modal-anulacion {
            width: 95%;
            max-width: none;
        }

        .detalle-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    }
</style>

<script>
    // ===== VARIABLES GLOBALES =====
    let filtroActual = null;

    // ===== MENU VER ORDEN =====
    function toggleVerMenu(event, ordenId) {
        event.stopPropagation();
        const menu = document.getElementById(`ver-menu-${ordenId}`);
        
        // Cerrar otros menús abiertos
        document.querySelectorAll('.ver-submenu[style*="display: block"]').forEach(m => {
            if (m.id !== `ver-menu-${ordenId}`) {
                m.style.display = 'none';
            }
        });
        
        // Toggle del menú actual
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    // Cerrar menús al hacer clic afuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.ver-menu-container')) {
            document.querySelectorAll('.ver-submenu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });

    // ===== FILTROS DE COLUMNAS =====

    function abrirModalFiltro(columna) {
        filtroActual = columna;
        const modalTitulo = document.getElementById('modalFiltroTitulo');
        const filtroContenido = document.getElementById('filtroContenido');
        const modal = document.getElementById('modalFiltro');

        let titulo = '';
        let campoNombre = '';

        // Configurar según la columna
        switch(columna) {
            case 'id-orden':
                titulo = 'Filtrar por ID Orden';
                campoNombre = 'numero';
                break;
            case 'cliente':
                titulo = 'Filtrar por Cliente';
                campoNombre = 'cliente';
                break;
            case 'fecha':
                modalTitulo.textContent = 'Filtrar por Fecha';
                filtroContenido.innerHTML = `
                    <label for="filtroDesde">Desde:</label>
                    <input type="date" id="filtroDesde" name="fecha_desde" class="form-control">
                    <label for="filtroHasta" style="margin-top: 1rem;">Hasta:</label>
                    <input type="date" id="filtroHasta" name="fecha_hasta" class="form-control">
                `;
                modal.style.display = 'flex';
                return;
            case 'estado':
                titulo = 'Filtrar por Estado';
                campoNombre = 'estado';
                // Estados predefinidos
                const estados = ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada'];
                filtroContenido.innerHTML = `
                    <div class="form-group">
                        <input type="text" id="buscadorEstado" class="form-control" placeholder="Buscar estado..." style="margin-bottom: 1rem;">
                        <div id="listaEstados">
                            ${estados.map(estado => `
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                    <input type="checkbox" name="estado" value="${estado}" class="filtro-checkbox">
                                    <span>${estado}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                `;
                
                // Agregar funcionalidad de búsqueda
                setTimeout(() => {
                    document.getElementById('buscadorEstado')?.addEventListener('input', function(e) {
                        const valor = e.target.value.toLowerCase();
                        document.querySelectorAll('#listaEstados label').forEach(label => {
                            const texto = label.textContent.toLowerCase();
                            label.style.display = texto.includes(valor) ? 'flex' : 'none';
                        });
                    });
                }, 0);
                
                modal.style.display = 'flex';
                return;
            case 'asesora':
                titulo = 'Filtrar por Asesora';
                campoNombre = 'asesora';
                break;
            case 'forma-pago':
                titulo = 'Filtrar por Forma de Pago';
                campoNombre = 'forma_pago';
                break;
        }

        // Para columnas que necesitan cargar datos de la BD
        if (campoNombre && columna !== 'fecha' && columna !== 'estado') {
            cargarOpcionesFiltro(campoNombre, titulo, modal, filtroContenido);
        }
    }

    function cargarOpcionesFiltro(campo, titulo, modal, filtroContenido) {
        // Mapear campos a columnas de la BD
        const endpoint = `/supervisor-pedidos/filtro-opciones/${campo}`;
        
        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                modalTitulo = document.getElementById('modalFiltroTitulo');
                modalTitulo.textContent = titulo;
                
                // Crear HTML con buscador y checkboxes
                filtroContenido.innerHTML = `
                    <div class="form-group">
                        <input type="text" id="buscadorFiltro" class="form-control" placeholder="Buscar..." style="margin-bottom: 1rem;">
                        <div id="listaOpciones" style="max-height: 300px; overflow-y: auto;">
                            ${data.opciones.map(opcion => `
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                                    <input type="checkbox" name="${campo}" value="${opcion}" class="filtro-checkbox">
                                    <span>${opcion || '(Sin especificar)'}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                `;
                
                // Agregar funcionalidad de búsqueda
                setTimeout(() => {
                    document.getElementById('buscadorFiltro')?.addEventListener('input', function(e) {
                        const valor = e.target.value.toLowerCase();
                        document.querySelectorAll('#listaOpciones label').forEach(label => {
                            const texto = label.textContent.toLowerCase();
                            label.style.display = texto.includes(valor) ? 'flex' : 'none';
                        });
                    });
                }, 0);
                
                modal.style.display = 'flex';
            })
            .catch(error => {
                console.error('Error cargando opciones:', error);
                filtroContenido.innerHTML = `<p style="color: red;">Error cargando opciones de filtro</p>`;
                modal.style.display = 'flex';
            });
    }

    function cerrarModalFiltro() {
        document.getElementById('modalFiltro').style.display = 'none';
        filtroActual = null;
    }

    function aplicarFiltroColumna(event) {
        event.preventDefault();
        
        // Construir URL con parámetros actuales
        const url = new URL(window.location);
        
        // Obtener todos los checkboxes seleccionados
        const checkboxes = document.querySelectorAll('.filtro-checkbox:checked');
        const valoresSeleccionados = Array.from(checkboxes).map(cb => cb.value);
        
        // Limpiar parámetros anteriores según el filtro actual
        if (filtroActual === 'id-orden') {
            url.searchParams.delete('numero');
            if (valoresSeleccionados.length > 0) url.searchParams.set('numero', valoresSeleccionados.join(','));
        } else if (filtroActual === 'cliente') {
            url.searchParams.delete('cliente');
            if (valoresSeleccionados.length > 0) url.searchParams.set('cliente', valoresSeleccionados.join(','));
        } else if (filtroActual === 'fecha') {
            url.searchParams.delete('fecha_desde');
            url.searchParams.delete('fecha_hasta');
            const desde = document.getElementById('filtroDesde')?.value;
            const hasta = document.getElementById('filtroHasta')?.value;
            if (desde) url.searchParams.set('fecha_desde', desde);
            if (hasta) url.searchParams.set('fecha_hasta', hasta);
        } else if (filtroActual === 'estado') {
            url.searchParams.delete('estado');
            if (valoresSeleccionados.length > 0) url.searchParams.set('estado', valoresSeleccionados.join(','));
        } else if (filtroActual === 'asesora') {
            url.searchParams.delete('asesora');
            if (valoresSeleccionados.length > 0) url.searchParams.set('asesora', valoresSeleccionados.join(','));
        } else if (filtroActual === 'forma-pago') {
            url.searchParams.delete('forma_pago');
            if (valoresSeleccionados.length > 0) url.searchParams.set('forma_pago', valoresSeleccionados.join(','));
        }
        
        window.location.href = url.toString();
    }

    // Cerrar modal al hacer clic fuera
    document.getElementById('modalFiltro')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalFiltro();
    });

    // ===== MODALES DE ÓRDENES =====
    function verOrdenComparar(ordenId) {
        document.getElementById(`ver-menu-${ordenId}`).style.display = 'none';
        const modal = document.getElementById('modalVerOrden');
        const content = document.getElementById('modalVerOrdenContent');

        fetch(`/supervisor-pedidos/${ordenId}/datos`)
            .then(response => response.json())
            .then(data => {
                let html = '';
                const tieneCotizacion = data.cotizacion && Object.keys(data.cotizacion).length > 0;

                // Si tiene cotización, usar layout de dos columnas
                if (tieneCotizacion) {
                    const cotizacion = data.cotizacion;
                    
                    // Formatear datos de prendas del pedido
                    const prendasPedidoHTML = data.prendas?.map(prenda => `
                        <tr>
                            <td>${prenda.nombre_prenda}</td>
                            <td>${prenda.cantidad}</td>
                            <td>${prenda.descripcion || 'N/A'}</td>
                        </tr>
                    `).join('') || '<tr><td colspan="3">Sin prendas</td></tr>';

                    // Formatear datos de prendas de la cotización
                    const prendasCotizacionHTML = cotizacion.prendas_cotizaciones?.map(prenda => `
                        <tr>
                            <td>${prenda.nombre_prenda || 'N/A'}</td>
                            <td>${prenda.cantidad || 'N/A'}</td>
                            <td>${prenda.descripcion || 'N/A'}</td>
                        </tr>
                    `).join('') || '<tr><td colspan="3">Sin prendas</td></tr>';

                    html = `
                        <div class="modal-body-two-columns">
                            <!-- COLUMNA 1: PEDIDO (Izquierda) -->
                            <div class="modal-column">
                                <h3>📦 Pedido #${data.numero_pedido}</h3>
                                <div class="orden-detalle">
                                    <div class="detalle-row" style="grid-template-columns: 1fr;">
                                        <div class="detalle-col">
                                            <label>Cliente:</label>
                                            <strong>${data.cliente}</strong>
                                        </div>
                                        <div class="detalle-col">
                                            <label>Asesora:</label>
                                            <strong>${data.asesora?.name || data.asesora || 'N/A'}</strong>
                                        </div>
                                    </div>

                                    <div class="detalle-row" style="grid-template-columns: 1fr;">
                                        <div class="detalle-col">
                                            <label>Estado:</label>
                                            <strong>${data.estado}</strong>
                                        </div>
                                        <div class="detalle-col">
                                            <label>Forma de Pago:</label>
                                            <strong>${data.forma_de_pago || 'N/A'}</strong>
                                        </div>
                                    </div>

                                    <div class="detalle-row" style="grid-template-columns: 1fr;">
                                        <div class="detalle-col">
                                            <label>Fecha de Pedido:</label>
                                            <strong>${new Date(data.fecha_de_creacion_de_orden).toLocaleDateString('es-CO')}</strong>
                                        </div>
                                    </div>

                                    ${data.direccion_entrega ? `
                                        <div class="detalle-row" style="grid-template-columns: 1fr;">
                                            <div class="detalle-col">
                                                <label>Dirección de Entrega:</label>
                                                <strong>${data.direccion_entrega}</strong>
                                            </div>
                                        </div>
                                    ` : ''}

                                    <div class="prendas-section">
                                        <h3 style="font-size: 1rem; margin-top: 1rem;">Prendas del Pedido</h3>
                                        ${data.prendas && data.prendas.length > 0 ? `
                                            <div style="overflow-x: auto; margin-top: 1rem;">
                                                <table class="tabla-prendas" style="font-size: 0.85rem;">
                                                    <thead>
                                                        <tr style="background: #1e40af; color: white;">
                                                            <th style="padding: 0.75rem; text-align: left;">PRENDA</th>
                                                            <th style="padding: 0.75rem; text-align: left;">DESCRIPCIÓN</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${data.prendas.map(prenda => `
                                                            <tr style="border-bottom: 1px solid #e0e6ed;">
                                                                <td style="padding: 1rem; font-weight: 600; color: #1e293b;">
                                                                    ${prenda.nombre_prenda || 'Sin nombre'}
                                                                </td>
                                                                <td style="padding: 1rem;">
                                                                    <div style="font-size: 0.9rem; line-height: 1.8; white-space: pre-wrap; word-break: break-word; color: #475569;">
                                                                        ${prenda.descripcion_armada || prenda.descripcion || 'Sin especificaciones'}
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        `).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                        ` : '<p style="color: #95a5a6;">Sin prendas</p>'}
                                    </div>

                                    ${data.observaciones ? `
                                        <div class="detalle-row" style="grid-template-columns: 1fr; margin-top: 1.5rem;">
                                            <div class="detalle-col">
                                                <label>Observaciones:</label>
                                                <strong>${data.observaciones}</strong>
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>

                            <!-- COLUMNA 2: COTIZACIÓN (Derecha) -->
                            <div class="modal-column column-divider">
                                <h3>📋 Cotización #${cotizacion.numero_cotizacion || 'N/A'}</h3>
                                <div class="orden-detalle">
                                    <div class="detalle-row" style="grid-template-columns: 1fr;">
                                        <div class="detalle-col">
                                            <label>Estado:</label>
                                            <strong>${cotizacion.estado || 'N/A'}</strong>
                                        </div>
                                        <div class="detalle-col">
                                            <label>Fecha Cotización:</label>
                                            <strong>${cotizacion.created_at ? new Date(cotizacion.created_at).toLocaleDateString('es-CO') : 'N/A'}</strong>
                                        </div>
                                    </div>

                                                    <div class="prendas-section">
                                        <h3 style="font-size: 1rem; margin-top: 1rem;">Prendas en Cotización</h3>
                                        ${tieneCotizacion && cotizacion.prendas_cotizaciones && cotizacion.prendas_cotizaciones.length > 0 ? `
                                            <div style="overflow-x: auto; margin-top: 1rem;">
                                                <table class="tabla-prendas" style="font-size: 0.85rem;">
                                                    <thead>
                                                        <tr style="background: #1e40af; color: white;">
                                                            <th style="padding: 0.75rem; text-align: left;">PRENDA</th>
                                                            <th style="padding: 0.75rem; text-align: left;">DESCRIPCIÓN & TALLAS</th>
                                                            <th style="padding: 0.75rem; text-align: left;">COLOR, TELA & VARIACIONES</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${cotizacion.prendas_cotizaciones.map(prenda => {
                                                            const variante = prenda.variantes && prenda.variantes.length > 0 ? prenda.variantes[0] : null;
                                                            
                                                            // Parser para descripcion_adicional
                                                            let obsArray = [];
                                                            if (variante && variante.descripcion_adicional) {
                                                                obsArray = variante.descripcion_adicional.split(' | ');
                                                            }
                                                            
                                                            const parseObs = (prefix) => {
                                                                for (let obs of obsArray) {
                                                                    if (obs.indexOf(prefix + ':') === 0) {
                                                                        return obs.replace(prefix + ':', '').trim();
                                                                    }
                                                                }
                                                                return null;
                                                            };
                                                            
                                                            const obsManga = parseObs('Manga');
                                                            const obsBolsillos = parseObs('Bolsillos');
                                                            const obsBroche = parseObs('Broche');
                                                            const obsReflectivo = parseObs('Reflectivo');
                                                            
                                                            return `
                                                                <tr style="border-bottom: 1px solid #e0e6ed;">
                                                                    <td style="padding: 1rem; font-weight: 600; color: #1e293b;">
                                                                        ${prenda.nombre_producto || 'Sin nombre'}
                                                                    </td>
                                                                    <td style="padding: 1rem;">
                                                                        <div style="margin-bottom: 0.5rem; color: #475569;">
                                                                            ${prenda.descripcion || '-'}
                                                                        </div>
                                                                        ${prenda.genero ? `
                                                                            <div style="margin-bottom: 0.5rem;">
                                                                                <span style="font-weight: 600; color: #64748b; font-size: 0.8rem;">Género:</span>
                                                                                <span style="background: #f0f4f8; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; font-size: 0.8rem;">
                                                                                    ${prenda.genero}
                                                                                </span>
                                                                            </div>
                                                                        ` : ''}
                                                                        <div>
                                                                            <span style="font-weight: 600; color: #64748b; font-size: 0.8rem;">Tallas:</span>
                                                                            <span style="color: #1e293b;">
                                                                                ${prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0 ? prenda.tallas.join(', ') : '-'}
                                                                            </span>
                                                                        </div>
                                                                    </td>
                                                                    <td style="padding: 1rem;">
                                                                        ${variante ? `
                                                                            <div style="font-size: 0.9rem; line-height: 1.8;">
                                                                                <div style="margin-bottom: 0.5rem;">
                                                                                    <span style="font-weight: 600; color: #0066cc;">Color:</span>
                                                                                    <span style="color: #1e293b;">${variante.color?.nombre || '-'}</span>
                                                                                </div>
                                                                                <div style="margin-bottom: 0.5rem;">
                                                                                    <span style="font-weight: 600; color: #0066cc;">Tela:</span>
                                                                                    <span style="color: #1e293b;">${variante.tela?.nombre || '-'}</span>
                                                                                    ${variante.tela?.referencia ? `<div style="color: #64748b; font-size: 0.75rem;">Ref: ${variante.tela.referencia}</div>` : ''}
                                                                                </div>
                                                                                <div style="margin-bottom: 0.5rem;">
                                                                                    <span style="font-weight: 600; color: #0066cc;">Manga:</span>
                                                                                    <span style="color: #1e293b;">${variante.tipo_manga?.nombre || '-'}</span>
                                                                                    ${obsManga ? `<div style="color: #64748b; font-size: 0.75rem;">${obsManga}</div>` : ''}
                                                                                </div>
                                                                                <div style="margin-bottom: 0.5rem;">
                                                                                    <span style="font-weight: 600; color: #0066cc;">Bolsillos:</span>
                                                                                    ${variante.tiene_bolsillos ? '<span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.75rem; font-weight: 600;">Sí</span>' : '<span style="background: #cbd5e1; color: #475569; padding: 2px 6px; border-radius: 3px; font-size: 0.75rem; font-weight: 600;">No</span>'}
                                                                                    ${obsBolsillos ? `<div style="color: #64748b; font-size: 0.75rem;">${obsBolsillos}</div>` : ''}
                                                                                </div>
                                                                                ${variante.tipo_broche ? `
                                                                                    <div style="margin-bottom: 0.5rem;">
                                                                                        <span style="font-weight: 600; color: #0066cc;">Broche:</span>
                                                                                        <span style="color: #1e293b;">${variante.tipo_broche.nombre}</span>
                                                                                        ${obsBroche ? `<div style="color: #64748b; font-size: 0.75rem;">${obsBroche}</div>` : ''}
                                                                                    </div>
                                                                                ` : ''}
                                                                                <div style="margin-bottom: 0.5rem;">
                                                                                    <span style="font-weight: 600; color: #0066cc;">Reflectivo:</span>
                                                                                    ${variante.tiene_reflectivo ? '<span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.75rem; font-weight: 600;">Sí</span>' : '<span style="background: #cbd5e1; color: #475569; padding: 2px 6px; border-radius: 3px; font-size: 0.75rem; font-weight: 600;">No</span>'}
                                                                                    ${obsReflectivo ? `<div style="color: #64748b; font-size: 0.75rem;">${obsReflectivo}</div>` : ''}
                                                                                </div>
                                                                            </div>
                                                                        ` : '<span style="color: #95a5a6;">Sin información</span>'}
                                                                    </td>
                                                                </tr>
                                                            `;
                                                        }).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                        ` : '<tr><td colspan="3" style="text-align: center; padding: 1rem; color: #95a5a6;">Sin prendas</td></tr>'}
                                    </div>

                                    ${cotizacion.observaciones ? `
                                        <div class="detalle-row" style="grid-template-columns: 1fr; margin-top: 1.5rem;">
                                            <div class="detalle-col">
                                                <label>Observaciones:</label>
                                                <strong>${cotizacion.observaciones}</strong>
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    // Sin cotización: mostrar solo el pedido (una columna)
                    const prendasPedidoHTML = data.prendas?.map(prenda => `
                        <tr>
                            <td>${prenda.nombre_prenda}</td>
                            <td>${prenda.cantidad}</td>
                            <td>${prenda.descripcion || 'N/A'}</td>
                        </tr>
                    `).join('') || '<tr><td colspan="3">Sin prendas</td></tr>';

                    html = `
                        <div style="padding: 1.5rem;">
                            <div class="orden-detalle">
                                <div class="detalle-row" style="grid-template-columns: 1fr;">
                                    <div class="detalle-col">
                                        <label>Número de Orden:</label>
                                        <strong>#${data.numero_pedido}</strong>
                                    </div>
                                    <div class="detalle-col">
                                        <label>Cliente:</label>
                                        <strong>${data.cliente}</strong>
                                    </div>
                                </div>

                                <div class="detalle-row" style="grid-template-columns: 1fr;">
                                    <div class="detalle-col">
                                        <label>Asesora:</label>
                                        <strong>${data.asesora?.name || data.asesora || 'N/A'}</strong>
                                    </div>
                                    <div class="detalle-col">
                                        <label>Estado:</label>
                                        <strong>${data.estado}</strong>
                                    </div>
                                </div>

                                <div class="detalle-row" style="grid-template-columns: 1fr;">
                                    <div class="detalle-col">
                                        <label>Fecha Creación:</label>
                                        <strong>${new Date(data.fecha_de_creacion_de_orden).toLocaleDateString('es-CO')}</strong>
                                    </div>
                                    <div class="detalle-col">
                                        <label>Forma de Pago:</label>
                                        <strong>${data.forma_de_pago || 'N/A'}</strong>
                                    </div>
                                </div>

                                ${data.direccion_entrega ? `
                                    <div class="detalle-row" style="grid-template-columns: 1fr;">
                                        <div class="detalle-col">
                                            <label>Dirección de Entrega:</label>
                                            <strong>${data.direccion_entrega}</strong>
                                        </div>
                                    </div>
                                ` : ''}

                                <div class="prendas-section">
                                    <h3>Prendas del Pedido</h3>
                                    <table class="tabla-prendas">
                                        <thead>
                                            <tr>
                                                <th>Prenda</th>
                                                <th>Cantidad</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${prendasPedidoHTML}
                                        </tbody>
                                    </table>
                                </div>

                                ${data.observaciones ? `
                                    <div class="detalle-row" style="grid-template-columns: 1fr; margin-top: 1.5rem;">
                                        <div class="detalle-col">
                                            <label>Observaciones:</label>
                                            <strong>${data.observaciones}</strong>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }

                content.innerHTML = html;
                modal.style.display = 'flex';
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = '<p style="color: red; padding: 2rem;">Error al cargar los datos</p>';
                modal.style.display = 'flex';
            });
    }

    function cerrarModalVerOrden() {
        document.getElementById('modalVerOrden').style.display = 'none';
    }

    function abrirModalAnulacion(ordenId, numeroOrden) {
        document.getElementById('ordenNumero').textContent = '#' + numeroOrden;
        document.getElementById('formAnulacion').dataset.ordenId = ordenId;
        document.getElementById('motivoAnulacion').value = '';
        document.getElementById('contadorActual').textContent = '0';
        document.getElementById('modalAnulacion').style.display = 'flex';
    }

    function cerrarModalAnulacion() {
        document.getElementById('modalAnulacion').style.display = 'none';
    }

    function confirmarAnulacion(event) {
        event.preventDefault();
        
        const ordenId = document.getElementById('formAnulacion').dataset.ordenId;
        const motivo = document.getElementById('motivoAnulacion').value;

        fetch(`/supervisor-pedidos/${ordenId}/anular`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                motivo_anulacion: motivo,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Orden anulada correctamente');
                // Recargar notificaciones si la función existe
                if (typeof cargarNotificacionesPendientes === 'function') {
                    cargarNotificacionesPendientes();
                }
                // Cerrar modal y recargar después de 1 segundo
                cerrarModalAnulacion();
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al anular la orden');
        });
    }

    // Contador de caracteres
    document.getElementById('motivoAnulacion')?.addEventListener('input', function() {
        document.getElementById('contadorActual').textContent = this.value.length;
        document.getElementById('btnConfirmarAnulacion').disabled = this.value.length < 10 || this.value.length > 500;
    });

    // Cerrar modales al hacer clic fuera
    document.getElementById('modalVerOrden')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalVerOrden();
    });

    document.getElementById('modalAnulacion')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalAnulacion();
    });

    // Función para aprobar orden
    function aprobarOrden(ordenId, numeroOrden) {
        if (!confirm(`¿Confirmar aprobación de orden #${numeroOrden}?`)) {
            return;
        }

        fetch(`/supervisor-pedidos/${ordenId}/aprobar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({}),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Orden aprobada correctamente');
                // Recargar notificaciones si la función existe
                if (typeof cargarNotificacionesPendientes === 'function') {
                    cargarNotificacionesPendientes();
                }
                // Recargar la página después de 1 segundo
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al aprobar la orden');
        });
    }

    // Función para ver detalles de orden (orden-detail-modal)
    // Cierra el menú y abre el modal de detalles
    function verOrdenDetalles(ordenId) {
        // Cerrar el menú ver
        const menu = document.getElementById(`ver-menu-${ordenId}`);
        if (menu) {
            menu.style.display = 'none';
        }
        
        // Abrir el modal de detalles usando la función externa
        openOrderDetailModal(ordenId);
    }
</script>

<!-- Modal Overlay y Wrapper para Detalles de Orden -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 90vw; max-height: 90vh; overflow-y: auto; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

@push('scripts')
    <script src="{{ asset('js/supervisor-pedidos/supervisor-pedidos-detail-modal.js') }}"></script>
@endpush

@endsection
