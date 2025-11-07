@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/control-calidad.css') }}">

    <div class="control-calidad-container">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-clipboard-check"></i>
                Control de Calidad
            </h1>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por pedido o cliente..." value="{{ $query }}">
                <button type="button" id="clearSearch" style="display: {{ $query ? 'block' : 'none' }};">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Tabla de Pedidos -->
        <div class="table-section">
            <h2 class="section-title">Órdenes de Pedidos</h2>
            <div class="table-wrapper">
                <div class="table-scroll">
                    <table class="data-table" id="tablaPedidos">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Novedad</th>
                                <th>Fecha Ingreso a Control de Calidad</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $ordenesPedido = $ordenes->filter(function($orden) {
                                return $orden->getTable() === 'tabla_original';
                            });
                        @endphp
                        @forelse($ordenesPedido as $orden)
                            <tr data-pedido="{{ $orden->pedido }}">
                                <td>
                                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $orden->estado ?? 'default')) }}">
                                        {{ $orden->estado ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if($orden->fecha_de_creacion_de_orden)
                                        {{ \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $orden->pedido ?? '-' }}</td>
                                <td>{{ $orden->cliente ?? '-' }}</td>
                                <td>{{ $orden->novedades ?? '-' }}</td>
                                <td>
                                    @if($orden->control_de_calidad)
                                        {{ \Carbon\Carbon::parse($orden->control_de_calidad)->format('d/m/Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-clipboard-check"></i>
                                    <p>No hay órdenes de pedidos en Control de Calidad</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">Total de órdenes: {{ $ordenesPedido->count() }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabla de Bodega -->
        <div class="table-section">
            <h2 class="section-title">Órdenes de Bodega</h2>
            <div class="table-wrapper">
                <div class="table-scroll">
                    <table class="data-table" id="tablaBodega">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Novedad</th>
                                <th>Fecha Ingreso a Control de Calidad</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $ordenesBodega = $ordenes->filter(function($orden) {
                                return $orden->getTable() === 'tabla_original_bodega';
                            });
                        @endphp
                        @forelse($ordenesBodega as $orden)
                            <tr data-pedido="{{ $orden->pedido }}">
                                <td>
                                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $orden->estado ?? 'default')) }}">
                                        {{ $orden->estado ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if($orden->fecha_de_creacion_de_orden)
                                        {{ \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $orden->pedido ?? '-' }}</td>
                                <td>{{ $orden->cliente ?? '-' }}</td>
                                <td>{{ $orden->novedades ?? '-' }}</td>
                                <td>
                                    @if($orden->control_de_calidad)
                                        {{ \Carbon\Carbon::parse($orden->control_de_calidad)->format('d/m/Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-clipboard-check"></i>
                                    <p>No hay órdenes de bodega en Control de Calidad</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">Total de órdenes: {{ $ordenesBodega->count() }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/control-calidad.js') }}"></script>
@endsection
