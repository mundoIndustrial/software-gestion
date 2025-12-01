@extends('layouts.app')

@section('content')

@php
    function getEficienciaClass($eficiencia) {
        if ($eficiencia === null) return '';
        $eficiencia = floatval($eficiencia);
        if ($eficiencia < 0.7) return 'eficiencia-red';
        if ($eficiencia >= 0.7 && $eficiencia < 0.8) return 'eficiencia-yellow';
        if ($eficiencia >= 0.8 && $eficiencia < 1.0) return 'eficiencia-green';
        if ($eficiencia >= 1.0) return 'eficiencia-blue';
        return '';
    }
@endphp
<!-- Font Awesome para iconos de paginación -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/tableros.css') }}">
<link rel="stylesheet" href="{{ asset('css/orders styles/modern-table.css') }}">
<script src="{{ asset('js/tableros.js') }}"></script>
<style>
    .tableros-container {
        zoom: 0.76;
    }
    
    /* Forzar estilos del header en modo claro */
    body:not(.dark-theme) .modern-table .table-head {
        background: linear-gradient(135deg, #475569 0%, #334155 100%) !important;
    }
    
    body:not(.dark-theme) .modern-table .table-header-cell {
        color: #ffffff !important;
        background: transparent !important;
    }
    
    body:not(.dark-theme) .modern-table .header-content {
        color: #ffffff !important;
    }
    
    body:not(.dark-theme) .modern-table .filter-icon {
        color: #cbd5e1 !important;
    }
    
    body:not(.dark-theme) .modern-table .filter-icon:hover {
        color: #FF6B35 !important;
        background: rgba(255, 107, 53, 0.2) !important;
    }
    
    /* Animaciones para notificaciones */
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
</style>
@include('components.tableros-form-modal')
@include('components.form_modal_piso_corte')
<div class="tableros-container" x-data="tablerosApp()">
    <h1 class="tableros-title">Tableros de Producción</h1>

    <div class="tab-cards">
        <div class="tab-card" :class="{ 'active': activeTab === 'produccion' }" @click="activeTab = 'produccion'">
            <h3>Tablero de Piso Producción</h3>
            <p>Visualización de métricas de producción general</p>
        </div>

        <div class="tab-card" :class="{ 'active': activeTab === 'polos' }" @click="activeTab = 'polos'">
            <h3>Tablero Piso Polos</h3>
            <p>Métricas específicas del área de polos</p>
        </div>

        <div class="tab-card" :class="{ 'active': activeTab === 'corte' }" @click="activeTab = 'corte'">
            <h3>Tablero Piso Corte</h3>
            <p>Indicadores del proceso de corte</p>
        </div>
    </div>

    <div class="tab-content">
        <div x-show="activeTab === 'produccion'" class="chart-placeholder">
            <!-- Barra de opciones unificada -->
            @include('components.top-controls')
            
            <!-- Seguimiento módulos (visible by default) -->
            <div x-show="!showRecords" id="seguimiento-container-produccion">
                @include('components.seguimiento-modulos', ['section' => 'produccion', 'seguimiento' => $seguimientoProduccion])
            </div>

            <!-- Tabla de registros (hidden by default) -->
            <div x-show="showRecords" class="records-table-container">
                <div class="table-scroll-container">
                    <table class="modern-table" data-section="produccion">
                        <thead class="table-head">
                            <tr>
                                @foreach($columns as $column)
                                    <th class="table-header-cell" data-column="{{ $column }}">
                                        <div class="header-content">
                                            {{ ucfirst(str_replace('_', ' ', $column)) }}
                                            <button class="filter-icon" data-column="{{ $column }}" title="Filtrar por {{ ucfirst(str_replace('_', ' ', $column)) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                @endforeach
                                <th class="table-header-cell">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @foreach($registros as $registro)
                            <tr class="table-row" data-id="{{ $registro->id }}">
                                @foreach($columns as $column)
                                    @php
                                        $value = $registro->$column;
                                        $displayValue = $value;
                                        if ($column === 'fecha' && $value) {
                                            $displayValue = $value->format('d-m-Y');
                                        } elseif ($column === 'hora' && $value) {
                                            $displayValue = $value;
                                        } elseif ($column === 'eficiencia' && $value !== null) {
                                            // Si el valor es mayor a 1, ya está en porcentaje, solo formatearlo
                                            // Si es menor o igual a 1, es decimal y hay que multiplicar por 100
                                            $eficienciaValue = $value > 1 ? $value : ($value * 100);
                                            $displayValue = number_format($eficienciaValue, 1, '.', '') . '%';
                                        }
                                        // Para la clase de eficiencia, normalizar el valor a decimal (0-1)
                                        $eficienciaClass = '';
                                        if ($column === 'eficiencia' && $value !== null) {
                                            $normalizedValue = $value > 1 ? ($value / 100) : $value;
                                            $eficienciaClass = getEficienciaClass($normalizedValue);
                                        }
                                    @endphp
                                    <td class="table-cell editable-cell {{ $eficienciaClass }}" data-column="{{ $column }}" data-value="{{ $column === 'fecha' ? $displayValue : $value }}" title="Doble clic para editar">{{ $displayValue }}</td>
                                @endforeach
                                <td class="table-cell">
                                    <div class="action-buttons">
                                        <button class="duplicate-btn" data-id="{{ $registro->id }}" data-section="produccion" title="Duplicar registro">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        </button>
                                        <button class="delete-btn" data-id="{{ $registro->id }}" data-section="produccion" title="Eliminar registro">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="table-pagination" data-section="produccion" id="pagination-produccion">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ ($registros->currentPage() / $registros->lastPage()) * 100 }}%"></div>
                    </div>
                    <div class="pagination-info">
                        <span id="paginationInfo-produccion">Mostrando {{ $registros->firstItem() }}-{{ $registros->lastItem() }} de {{ $registros->total() }} registros</span>
                    </div>
                    <div class="pagination-controls" id="paginationControls-produccion">
                        @if($registros->hasPages())
                            <button class="pagination-btn" data-page="1" {{ $registros->currentPage() == 1 ? 'disabled' : '' }}>
                                <i class="fas fa-angle-double-left"></i>
                            </button>
                            <button class="pagination-btn" data-page="{{ $registros->currentPage() - 1 }}" {{ $registros->currentPage() == 1 ? 'disabled' : '' }}>
                                <i class="fas fa-angle-left"></i>
                            </button>
                            
                            @php
                                $start = max(1, $registros->currentPage() - 2);
                                $end = min($registros->lastPage(), $registros->currentPage() + 2);
                            @endphp
                            
                            @for($i = $start; $i <= $end; $i++)
                                <button class="pagination-btn page-number {{ $i == $registros->currentPage() ? 'active' : '' }}" data-page="{{ $i }}">
                                    {{ $i }}
                                </button>
                            @endfor
                            
                            <button class="pagination-btn" data-page="{{ $registros->currentPage() + 1 }}" {{ $registros->currentPage() == $registros->lastPage() ? 'disabled' : '' }}>
                                <i class="fas fa-angle-right"></i>
                            </button>
                            <button class="pagination-btn" data-page="{{ $registros->lastPage() }}" {{ $registros->currentPage() == $registros->lastPage() ? 'disabled' : '' }}>
                                <i class="fas fa-angle-double-right"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'polos'" class="chart-placeholder" x-init="console.log('🔍 POLOS TAB - activeTab:', activeTab, 'showRecords:', showRecords)">
            <!-- Barra de opciones unificada -->
            @include('components.top-controls')

            <!-- Seguimiento módulos (visible by default) -->
            <div x-show="!showRecords" id="seguimiento-container-polos" x-init="console.log('📊 Seguimiento Polos - showRecords:', showRecords, 'Visible:', !showRecords)">
                <script>
                    console.log('🔍 Datos de seguimientoPolos:', @json($seguimientoPolos));
                </script>
                @include('components.seguimiento-modulos', ['section' => 'polos', 'seguimiento' => $seguimientoPolos])
            </div>

            <!-- Tabla de registros (hidden by default) -->
            <div x-show="showRecords" class="records-table-container" x-init="console.log('📋 Tabla Polos - showRecords:', showRecords, 'Visible:', showRecords)">
                <div class="table-scroll-container">
                    <table class="modern-table" data-section="polos">
                        <thead class="table-head">
                            <tr>
                                @foreach($columnsPolos as $column)
                                    <th class="table-header-cell" data-column="{{ $column }}">
                                        <div class="header-content">
                                            {{ ucfirst(str_replace('_', ' ', $column)) }}
                                            <button class="filter-icon" data-column="{{ $column }}" title="Filtrar por {{ ucfirst(str_replace('_', ' ', $column)) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                @endforeach
                                <th class="table-header-cell">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @foreach($registrosPolos as $registro)
                            <tr class="table-row" data-id="{{ $registro->id }}">
                                @foreach($columnsPolos as $column)
                                    @php
                                        $value = $registro->$column;
                                        $displayValue = $value;
                                        if ($column === 'fecha' && $value) {
                                            $displayValue = $value->format('d-m-Y');
                                        } elseif ($column === 'hora' && $value) {
                                            $displayValue = $value;
                                        } elseif ($column === 'eficiencia' && $value !== null) {
                                            // Si el valor es mayor a 1, ya está en porcentaje, solo formatearlo
                                            // Si es menor o igual a 1, es decimal y hay que multiplicar por 100
                                            $eficienciaValue = $value > 1 ? $value : ($value * 100);
                                            $displayValue = number_format($eficienciaValue, 1, '.', '') . '%';
                                        }
                                        // Para la clase de eficiencia, normalizar el valor a decimal (0-1)
                                        $eficienciaClass = '';
                                        if ($column === 'eficiencia' && $value !== null) {
                                            $normalizedValue = $value > 1 ? ($value / 100) : $value;
                                            $eficienciaClass = getEficienciaClass($normalizedValue);
                                        }
                                    @endphp
                                    <td class="table-cell editable-cell {{ $eficienciaClass }}" data-column="{{ $column }}" data-value="{{ $column === 'fecha' ? $displayValue : $value }}" title="Doble clic para editar">{{ $displayValue }}</td>
                                @endforeach
                                <td class="table-cell">
                                    <div class="action-buttons">
                                        <button class="duplicate-btn" data-id="{{ $registro->id }}" data-section="polos" title="Duplicar registro">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        </button>
                                        <button class="delete-btn" data-id="{{ $registro->id }}" data-section="polos" title="Eliminar registro">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="table-pagination" data-section="polos" id="pagination-polos">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ ($registrosPolos->currentPage() / $registrosPolos->lastPage()) * 100 }}%"></div>
                    </div>
                    <div class="pagination-info">
                        <span id="paginationInfo-polos">Mostrando {{ $registrosPolos->firstItem() }}-{{ $registrosPolos->lastItem() }} de {{ $registrosPolos->total() }} registros</span>
                    </div>
                    <div class="pagination-controls" id="paginationControls-polos">
                        @if($registrosPolos->hasPages())
                            <button class="pagination-btn" data-page="1" {{ $registrosPolos->currentPage() == 1 ? 'disabled' : '' }}>
                                <i class="fas fa-angle-double-left"></i>
                            </button>
                            <button class="pagination-btn" data-page="{{ $registrosPolos->currentPage() - 1 }}" {{ $registrosPolos->currentPage() == 1 ? 'disabled' : '' }}>
                                <i class="fas fa-angle-left"></i>
                            </button>
                            
                            @php
                                $start = max(1, $registrosPolos->currentPage() - 2);
                                $end = min($registrosPolos->lastPage(), $registrosPolos->currentPage() + 2);
                            @endphp
                            
                            @for($i = $start; $i <= $end; $i++)
                                <button class="pagination-btn page-number {{ $i == $registrosPolos->currentPage() ? 'active' : '' }}" data-page="{{ $i }}">
                                    {{ $i }}
                                </button>
                            @endfor
                            
                            <button class="pagination-btn" data-page="{{ $registrosPolos->currentPage() + 1 }}" {{ $registrosPolos->currentPage() == $registrosPolos->lastPage() ? 'disabled' : '' }}>
                                <i class="fas fa-angle-right"></i>
                            </button>
                            <button class="pagination-btn" data-page="{{ $registrosPolos->lastPage() }}" {{ $registrosPolos->currentPage() == $registrosPolos->lastPage() ? 'disabled' : '' }}>
                                <i class="fas fa-angle-double-right"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'corte'" class="chart-placeholder">
            <!-- Barra de opciones unificada -->
            @include('components.top-controls')


            <!-- Dashboard Tables Corte (visible by default) -->
            <div x-show="!showRecords" id="seguimiento-container-corte">
                @include('components.dashboard-tables-corte')
            </div>

            <!-- Tabla de registros (hidden by default) -->
            <div x-show="showRecords" class="records-table-container">
                <div class="table-scroll-container">
                    <table class="modern-table" data-section="corte">
                        <thead class="table-head">
                            <tr>
                                @foreach($columnsCorte ?? [] as $column)
                                    @php
                                        $headerText = ucfirst(str_replace('_', ' ', $column));
                                        if ($column === 'hora_id') {
                                            $headerText = 'Hora';
                                        } elseif ($column === 'operario_id') {
                                            $headerText = 'Operario';
                                        } elseif ($column === 'maquina_id') {
                                            $headerText = 'Máquina';
                                        } elseif ($column === 'tela_id') {
                                            $headerText = 'Tela';
                                        }
                                    @endphp
                                    <th class="table-header-cell" data-column="{{ $column }}">
                                        <div class="header-content">
                                            {{ $headerText }}
                                            <button class="filter-icon" data-column="{{ $column }}" title="Filtrar por {{ $headerText }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                @endforeach
                                <th class="table-header-cell">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @foreach($registrosCorte as $registro)
                            <tr class="table-row {{ (str_contains(strtolower($registro->actividad), 'extender') || str_contains(strtolower($registro->actividad), 'trazar')) ? 'extend-trazar-row' : '' }}" data-id="{{ $registro->id }}">
                                @foreach($columnsCorte as $column)
                                    @php
                                        $value = $registro->$column;
                                        $displayValue = $value;
                                        $dataValue = $value; // Valor para data-value
                                        
                                        if ($column === 'fecha' && $value) {
                                            $displayValue = $value->format('d-m-Y');
                                            $dataValue = $displayValue;
                                        } elseif ($column === 'hora_id' && $registro->hora) {
                                            $displayValue = $registro->hora->hora;
                                            $dataValue = $registro->hora->hora; // Usar valor de hora en lugar de ID
                                        } elseif ($column === 'operario_id' && $registro->operario) {
                                            $displayValue = $registro->operario->name;
                                            $dataValue = $registro->operario->name; // Usar nombre en lugar de ID
                                        } elseif ($column === 'maquina_id' && $registro->maquina) {
                                            $displayValue = $registro->maquina->nombre_maquina;
                                            $dataValue = $registro->maquina->nombre_maquina; // Usar nombre en lugar de ID
                                        } elseif ($column === 'tela_id' && $registro->tela) {
                                            $displayValue = $registro->tela->nombre_tela;
                                            $dataValue = $registro->tela->nombre_tela; // Usar nombre en lugar de ID
                                        } elseif ($column === 'eficiencia' && $value !== null) {
                                            $displayValue = round($value * 100, 1) . '%';
                                        }
                                        $eficienciaClass = ($column === 'eficiencia' && $value !== null) ? getEficienciaClass($value) : '';
                                    @endphp
                                    <td class="table-cell editable-cell {{ $eficienciaClass }}" data-column="{{ $column }}" data-value="{{ $dataValue }}" title="Doble clic para editar">{{ $displayValue }}</td>
                                @endforeach
                                <td class="table-cell">
                                    <div class="action-buttons">
                                        <button class="duplicate-btn" data-id="{{ $registro->id }}" data-section="corte" title="Duplicar registro">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        </button>
                                        <button class="delete-btn" data-id="{{ $registro->id }}" data-section="corte" title="Eliminar registro">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="table-pagination" data-section="corte" id="pagination-corte">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ ($registrosCorte->currentPage() / $registrosCorte->lastPage()) * 100 }}%"></div>
                    </div>
                    <div class="pagination-info">
                        <span id="paginationInfo-corte">Mostrando {{ $registrosCorte->firstItem() }}-{{ $registrosCorte->lastItem() }} de {{ $registrosCorte->total() }} registros</span>
                    </div>
                    <div class="pagination-controls" id="paginationControls-corte">
                        @if($registrosCorte->hasPages())
                            <button class="pagination-btn" data-page="1" {{ $registrosCorte->currentPage() == 1 ? 'disabled' : '' }}>
                                <i class="fas fa-angle-double-left"></i>
                            </button>
                            <button class="pagination-btn" data-page="{{ $registrosCorte->currentPage() - 1 }}" {{ $registrosCorte->currentPage() == 1 ? 'disabled' : '' }}>
                                <i class="fas fa-angle-left"></i>
                            </button>
                            
                            @php
                                $start = max(1, $registrosCorte->currentPage() - 2);
                                $end = min($registrosCorte->lastPage(), $registrosCorte->currentPage() + 2);
                            @endphp
                            
                            @for($i = $start; $i <= $end; $i++)
                                <button class="pagination-btn page-number {{ $i == $registrosCorte->currentPage() ? 'active' : '' }}" data-page="{{ $i }}">
                                    {{ $i }}
                                </button>
                            @endfor
                            
                            <button class="pagination-btn" data-page="{{ $registrosCorte->currentPage() + 1 }}" {{ $registrosCorte->currentPage() == $registrosCorte->lastPage() ? 'disabled' : '' }}>
                                <i class="fas fa-angle-right"></i>
                            </button>
                            <button class="pagination-btn" data-page="{{ $registrosCorte->lastPage() }}" {{ $registrosCorte->currentPage() == $registrosCorte->lastPage() ? 'disabled' : '' }}>
                                <i class="fas fa-angle-double-right"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar celda -->
<div id="editCellModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title" id="editModalTitle">Editar Celda</h3>
            <button type="button" class="close" id="closeEditModal">&times;</button>
        </div>
        <div class="modal-body">
            <input type="text" id="editCellInput" list="autocompleteList" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; color: black; background: white; text-transform: uppercase;">
            <datalist id="autocompleteList"></datalist>
            <small id="editHint" style="color: #666; display: block; margin-top: 5px;">Escribe para buscar o crear nuevo</small>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelEdit">Cancelar</button>
            <button type="button" class="btn btn-primary" id="saveEdit">Guardar (Enter)</button>
        </div>
    </div>
</div>

<!-- Modal para confirmar eliminación -->
<div id="deleteConfirmModal" class="modal-overlay" style="display: none;">
    <div class="modal-content delete-modal-wrapper">
        <div class="modal-header delete-modal-header">
            <h3 class="modal-title" id="deleteModalTitle">Confirmar Eliminación</h3>
            <button type="button" class="close delete-modal-close" id="closeDeleteModal">&times;</button>
        </div>
        <div class="modal-body delete-modal-body" id="deleteModalBody">
            <p>¿Estás seguro de que quieres eliminar este registro?</p>
        </div>
        <div class="modal-footer delete-modal-footer" id="deleteModalFooter">
            <button type="button" class="btn btn-secondary" id="cancelDelete">Cancelar</button>
            <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; justify-content: center; align-items: center;">
    <div style="text-align: center; color: white;">
        <div class="spinner" style="border: 4px solid rgba(255, 255, 255, 0.3); border-top: 4px solid #FF6B35; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 15px;"></div>
        <p id="loadingText" style="font-size: 16px; font-weight: 600;">Procesando...</p>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Variables globales
let currentCell = null;
let currentRowId = null;
let currentColumn = null;
let currentAutocompleteListener = null;

// 🗺️ Mapa para almacenar referencias a los registros por ID
// Esto permite actualizar el objeto cuando se edita una celda
const registrosMap = {
    corte: {},
    produccion: {},
    polos: {}
};

// ⚡ OPTIMIZACIÓN: Debounce para actualizar seguimiento (evitar múltiples llamadas en rápida sucesión)
const seguimientoDebounceTimers = {};
let isSearchingCell = false; // Flag para evitar actualizar seguimiento durante búsqueda

function actualizarSeguimientoDebounced(section) {
    // NO actualizar si estamos buscando una celda
    if (isSearchingCell) {
        console.log(`⏭️ Saltando actualización de seguimiento porque isSearchingCell=true`);
        return;
    }
    
    // Cancelar el timeout anterior si existe
    if (seguimientoDebounceTimers[section]) {
        clearTimeout(seguimientoDebounceTimers[section]);
    }
    
    // Esperar 1500ms antes de recargar el seguimiento (antes era 500ms)
    // Si se llama de nuevo antes de que termine el timeout, se cancela el anterior
    seguimientoDebounceTimers[section] = setTimeout(() => {
        console.log(`📊 Actualizando seguimiento de ${section} después del debounce...`);
        if (typeof recargarSeguimientoEspecifico === 'function') {
            recargarSeguimientoEspecifico(section);
        }
        delete seguimientoDebounceTimers[section];
    }, 1500);
}

// ⚡ OPTIMIZACIÓN: Cachear búsquedas anteriores para evitar llamadas duplicadas
const searchCache = {
    operario: {},
    maquina: {},
    tela: {},
    hora: {}
};

// Funciones para mostrar/ocultar loading overlay
function showLoading(message = 'Procesando...') {
    const overlay = document.getElementById('loadingOverlay');
    const text = document.getElementById('loadingText');
    if (overlay && text) {
        text.textContent = message;
        overlay.style.display = 'flex';
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// ⚡ Flag para evitar múltiples aperturas del modal
let modalOpening = false;

// Función global para manejar doble click en celdas
function handleCellDoubleClick() {
    // ⚡ Evitar múltiples aperturas del modal
    if (modalOpening) {
        console.log('⏭️ Modal ya está abriéndose, ignorando doble clic');
        return;
    }
    
    console.log('🖱️ Doble clic detectado en celda');
    currentCell = this;
    const row = this.closest('tr');
    console.log('🔍 Row encontrado:', !!row);
    console.log('🔍 Row dataset:', row?.dataset);
    currentRowId = row?.dataset?.id;
    currentColumn = this.dataset.column;

    // Obtener el valor a mostrar (preferir textContent que tiene el nombre, no el ID)
    const currentValue = this.textContent.trim();
    console.log(`📝 Editando - ID: ${currentRowId}, Columna: ${currentColumn}, Valor: ${currentValue}`);
    
    if (!currentRowId) {
        console.error('❌ ERROR: No se pudo obtener el ID del registro');
        alert('Error: No se pudo identificar el registro');
        return;
    }
    
    const modal = document.getElementById('editCellModal');
    const input = document.getElementById('editCellInput');
    const datalist = document.getElementById('autocompleteList');
    const modalTitle = document.getElementById('editModalTitle');
    const hint = document.getElementById('editHint');
    
    console.log('Modal encontrado:', !!modal);
    if (modal) {
        modalOpening = true;
        // Configurar título según la columna
        if (currentColumn === 'operario_id' || currentColumn === 'operario') {
            modalTitle.textContent = 'Editar Operario';
            hint.textContent = 'Escribe el nombre del operario (se creará si no existe)';
            setupAutocomplete('operario');
        } else if (currentColumn === 'maquina_id' || currentColumn === 'maquina') {
            modalTitle.textContent = 'Editar Máquina';
            hint.textContent = 'Escribe el nombre de la máquina (se creará si no existe)';
            setupAutocomplete('maquina');
        } else if (currentColumn === 'tela_id' || currentColumn === 'tela') {
            modalTitle.textContent = 'Editar Tela';
            hint.textContent = 'Escribe el nombre de la tela (se creará si no existe)';
            setupAutocomplete('tela');
        } else if (currentColumn === 'hora_id' || currentColumn === 'hora') {
            modalTitle.textContent = 'Editar Hora';
            hint.textContent = 'Escribe la hora (ej: 1, 2, 3, etc.)';
            datalist.innerHTML = ''; // Sin autocomplete para hora
        } else {
            modalTitle.textContent = 'Editar Celda';
            hint.textContent = 'Ingrese el nuevo valor';
            datalist.innerHTML = ''; // Limpiar datalist para otras columnas
        }
        
        modal.style.display = 'flex';
        modal.style.opacity = '1';
        modal.style.visibility = 'visible';
        input.value = currentValue;
        input.focus();
        input.select();
    } else {
        console.error('Modal no encontrado');
    }
}

// Configurar autocompletado para operario, máquina o tela
function setupAutocomplete(type) {
    const input = document.getElementById('editCellInput');
    const datalist = document.getElementById('autocompleteList');
    
    // Limpiar datalist
    datalist.innerHTML = '';
    
    // Remover listener anterior si existe
    if (currentAutocompleteListener) {
        input.removeEventListener('input', currentAutocompleteListener);
    }
    
    // Crear nuevo listener
    let searchTimeout;
    currentAutocompleteListener = function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.toUpperCase().trim();
        
        if (query.length < 1) {
            datalist.innerHTML = '';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            let searchUrl = '';
            if (type === 'operario') {
                searchUrl = '/search-operarios';
            } else if (type === 'maquina') {
                searchUrl = '/search-maquinas';
            } else if (type === 'tela') {
                searchUrl = '/search-telas';
            }
            
            console.log(`🔍 Buscando ${type}: "${query}"`);
            
            fetch(`${searchUrl}?q=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    datalist.innerHTML = '';
                    // Extraer el array correcto según el tipo
                    let items = [];
                    if (type === 'operario' && data.operarios) {
                        items = data.operarios;
                    } else if (type === 'maquina' && data.maquinas) {
                        items = data.maquinas;
                    } else if (type === 'tela' && data.telas) {
                        items = data.telas;
                    }
                    
                    console.log(`✅ Encontrados ${items.length} resultados para ${type}`);
                    
                    items.forEach(item => {
                        const option = document.createElement('option');
                        if (type === 'operario') {
                            option.value = item.name;
                            console.log(`  - ${item.name} (ID: ${item.id})`);
                        } else if (type === 'maquina') {
                            option.value = item.nombre_maquina;
                            console.log(`  - ${item.nombre_maquina} (ID: ${item.id})`);
                        } else if (type === 'tela') {
                            option.value = item.nombre_tela;
                            console.log(`  - ${item.nombre_tela} (ID: ${item.id})`);
                        }
                        datalist.appendChild(option);
                    });
                })
                .catch(error => console.error(`❌ Error buscando ${type}:`, error));
        }, 300);
    };
    
    // Agregar el nuevo listener
    input.addEventListener('input', currentAutocompleteListener);
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript cargado para edición de celdas y filtros');

    // Función para agregar registros a la tabla dinámicamente
    window.agregarRegistrosATabla = function(registros, section) {
        const table = document.querySelector(`table[data-section="${section}"]`);
        if (!table) {
            console.error('Tabla no encontrada para sección:', section);
            return;
        }

        const tbody = table.querySelector('tbody');
        const headers = table.querySelectorAll('th[data-column]');
        const columns = Array.from(headers).map(th => th.dataset.column);

        registros.forEach(registro => {
            const tr = document.createElement('tr');
            tr.className = 'table-row';
            tr.setAttribute('data-id', registro.id);

            columns.forEach(column => {
                const td = document.createElement('td');
                td.className = 'table-cell editable-cell';
                td.setAttribute('data-column', column);

                let value = registro[column];
                let displayValue = value;
                let eficienciaClass = '';

                if (column === 'fecha' && value) {
                    const date = new Date(value);
                    displayValue = date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
                } else if (column === 'hora_id' && registro.hora_display) {
                    displayValue = registro.hora_display;
                } else if (column === 'operario_id' && registro.operario_display) {
                    displayValue = registro.operario_display;
                } else if (column === 'maquina_id' && registro.maquina_display) {
                    displayValue = registro.maquina_display;
                } else if (column === 'tela_id' && registro.tela_display) {
                    displayValue = registro.tela_display;
                } else if (column === 'eficiencia' && value !== null) {
                    displayValue = value + '%';
                    eficienciaClass = getEficienciaClass(value);
                }

                if (eficienciaClass) {
                    td.classList.add(eficienciaClass);
                }
                td.setAttribute('data-value', value);
                td.title = 'Doble clic para editar';
                td.textContent = displayValue;

                tr.appendChild(td);
            });

            // Agregar celda de acciones con botones de duplicar y eliminar
            const actionTd = document.createElement('td');
            actionTd.className = 'table-cell';
            actionTd.innerHTML = `
                <div class="action-buttons">
                    <button class="duplicate-btn" data-id="${registro.id}" data-section="${section}" title="Duplicar registro">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                    <button class="delete-btn" data-id="${registro.id}" data-section="${section}" title="Eliminar registro">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </div>
            `;
            tr.appendChild(actionTd);

            // Agregar al final de la tabla (orden ascendente por ID)
            tbody.appendChild(tr);
        });

        // Actualizar información de paginación (aproximada)
        const paginationInfo = table.closest('.records-table-container').querySelector('.pagination-info span');
        if (paginationInfo) {
            const currentText = paginationInfo.textContent;
            const match = currentText.match(/Mostrando (\d+)-(\d+) de (\d+)/);
            if (match) {
                const start = parseInt(match[1]);
                const end = parseInt(match[2]) + registros.length;
                const total = parseInt(match[3]) + registros.length;
                paginationInfo.textContent = `Mostrando ${start}-${end} de ${total} registros`;
            }
        }

        console.log(`Agregados ${registros.length} registros a la tabla de ${section}`);
    };

    // Función para hacer celdas editables con doble click
    // ⚡ OPTIMIZACIÓN: Usar event delegation en lugar de adjuntar listeners a cada celda
    function attachEditableCellListeners() {
        // Solo adjuntar listener UNA VEZ al documento, no a cada celda
        if (!window.editableCellListenerAttached) {
            document.addEventListener('dblclick', function(e) {
                const cell = e.target.closest('.editable-cell');
                if (cell) {
                    handleCellDoubleClick.call(cell);
                }
            });
            window.editableCellListenerAttached = true;
            console.log('✅ Event delegation para celdas editables adjuntado UNA sola vez');
        }
    }

    // Inicializar event listeners
    attachEditableCellListeners();

    // Guardar cambios
    document.getElementById('saveEdit').addEventListener('click', saveCellEdit);
    document.getElementById('editCellInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            saveCellEdit();
        }
    });

    // Cancelar edición
    document.getElementById('cancelEdit').addEventListener('click', closeEditModal);
    document.getElementById('closeEditModal').addEventListener('click', closeEditModal);
    document.getElementById('editCellModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    async function saveCellEdit() {
        // 🕐 TIMING: Inicio del proceso
        const startTime = performance.now();
        const timings = {};
        
        // Mostrar loading
        showLoading('Guardando cambios...');
        
        let newValue = document.getElementById('editCellInput').value;
        let section = currentCell.closest('table').dataset.section;
        
        // 🔒 VALIDACIÓN: Si no se puede determinar la sección, usar 'produccion' como default
        if (!section) {
            console.warn('⚠️ No se pudo determinar la sección, usando default: produccion');
            section = 'produccion';
        }
        
        let displayName = newValue; // Guardar el nombre para mostrar
        
        timings.start = 0;
        
        // Mapear nombres de columnas si es necesario (ej: 'hora' -> 'hora_id', 'operario' -> 'operario_id', etc.)
        // ⚡ IMPORTANTE: El mapeo depende de la sección (solo 'corte' usa IDs para relaciones)
        let columnName = currentColumn;
        if (currentColumn === 'hora' && section === 'corte') {
            columnName = 'hora_id';
        } else if (currentColumn === 'operario' && section === 'corte') {
            columnName = 'operario_id';
            newValue = newValue.toUpperCase(); // Solo convertir a mayúsculas para texto
            displayName = newValue; // ⚡ IMPORTANTE: Actualizar displayName también
        } else if (currentColumn === 'maquina' && section === 'corte') {
            columnName = 'maquina_id';
            newValue = newValue.toUpperCase();
            displayName = newValue; // ⚡ IMPORTANTE: Actualizar displayName también
        } else if (currentColumn === 'tela' && section === 'corte') {
            columnName = 'tela_id';
            newValue = newValue.toUpperCase();
            displayName = newValue; // ⚡ IMPORTANTE: Actualizar displayName también
        }
        
        // Datos a enviar (permitir agregar campos adicionales cuando se requiera)
        const payload = { [columnName]: newValue, section: section };
        console.log(`📝 Columna original: ${currentColumn}, Columna mapeada: ${columnName}`);

        // Mapear PARADAS PROGRAMADAS -> TIEMPO PARA PROGRAMADA (segundos)
        function mapParadaToSeconds(valor) {
            const v = (valor || '').toString().trim().toUpperCase();
            if (v === 'DESAYUNO' || v === 'MEDIA TARDE') return 900;
            if (v === 'NINGUNA') return 0;
            return 0; // Default
        }

        // ⚡ OPTIMIZACIÓN: Ejecutar búsquedas en PARALELO usando Promise.all()
        try {
            if (['hora_id', 'operario_id', 'maquina_id', 'tela_id'].includes(columnName)) {
                // Determinar la URL y nombre del campo según el tipo
                let url = '';
                let dataKey = '';
                let displayKey = '';
                let cacheType = '';
                
                if (columnName === 'hora_id') {
                    url = '/find-hora-id';
                    dataKey = 'id';
                    displayKey = 'hora';
                    cacheType = 'hora';
                } else if (columnName === 'operario_id') {
                    url = '/find-or-create-operario';
                    dataKey = 'id';
                    displayKey = 'name';
                    cacheType = 'operario';
                } else if (columnName === 'maquina_id') {
                    url = '/find-or-create-maquina';
                    dataKey = 'id';
                    displayKey = 'nombre_maquina';
                    cacheType = 'maquina';
                } else if (columnName === 'tela_id') {
                    url = '/find-or-create-tela';
                    dataKey = 'id';
                    displayKey = 'nombre_tela';
                    cacheType = 'tela';
                }
                
                // ⚡ OPTIMIZACIÓN: Revisar caché primero
                const cacheKey = cacheType === 'hora' ? newValue : newValue.toUpperCase();
                if (searchCache[cacheType] && searchCache[cacheType][cacheKey]) {
                    const cachedData = searchCache[cacheType][cacheKey];
                    displayName = cachedData[displayKey];
                    newValue = cachedData[dataKey];
                    payload[columnName] = newValue;
                    
                    // 🎯 FIX: Formatear displayName para relaciones
                    if (columnName === 'hora_id') {
                        // Convertir a número si es string, luego formatear como "HORA XX"
                        const horaNum = typeof displayName === 'number' ? displayName : parseInt(displayName);
                        displayName = 'HORA ' + String(horaNum).padStart(2, '0');
                        console.log(`📌 Hora formateada (desde caché): ${horaNum} → ${displayName}`);
                    }
                    
                    timings.cacheHit = performance.now() - startTime;
                    console.log(`✅ ${columnName} obtenido del caché:`, cachedData, `(${timings.cacheHit.toFixed(2)}ms)`);
                } else {
                    const searchStart = performance.now();
                    // ⚡ OPTIMIZACIÓN: Marcar que estamos buscando para evitar actualizaciones de seguimiento
                    isSearchingCell = true;
                    console.log(`⏳ isSearchingCell = true (búsqueda iniciada)`);
                    
                    // Hacer la búsqueda si no está en caché
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(
                            columnName === 'hora_id' ? { hora: newValue } :
                            columnName === 'operario_id' ? { name: newValue } :
                            columnName === 'maquina_id' ? { nombre: newValue } :
                            { nombre: newValue }
                        )
                    });
                    
                    const data = await response.json();
                    timings.searchRequest = performance.now() - searchStart;
                    if (data.success || data.id) {
                        displayName = data[displayKey] || data[dataKey];
                        newValue = data[dataKey];
                        payload[columnName] = newValue;
                        
                        // 🎯 FIX: Formatear displayName para relaciones
                        if (columnName === 'hora_id') {
                            // Convertir a número si es string, luego formatear como "HORA XX"
                            const horaNum = typeof displayName === 'number' ? displayName : parseInt(displayName);
                            displayName = 'HORA ' + String(horaNum).padStart(2, '0');
                            console.log(`📌 Hora formateada: ${horaNum} → ${displayName}`);
                        }
                        
                        // ⚡ OPTIMIZACIÓN: Guardar en caché para búsquedas futuras
                        if (!searchCache[cacheType]) {
                            searchCache[cacheType] = {};
                        }
                        // Para hora, no hacer toUpperCase() del key
                        const keyToStore = cacheType === 'hora' ? String(data[displayKey]) : String(data[displayKey]).toUpperCase();
                        searchCache[cacheType][keyToStore] = data;
                        
                        console.log(`✅ ${columnName} encontrado/creado y cacheado:`, data, `(búsqueda: ${timings.searchRequest.toFixed(2)}ms)`);
                    } else {
                        hideLoading();
                        alert(`Error al procesar ${columnName}`);
                        isSearchingCell = false;
                        throw new Error(`Error al procesar ${columnName}`);
                    }
                    
                    // ⚡ OPTIMIZACIÓN: Resetear el flag después de búsqueda
                    isSearchingCell = false;
                    console.log(`✅ isSearchingCell = false (búsqueda completada)`);
                }
            }
        } catch (error) {
            console.error('❌ Error al buscar/crear:', error);
            isSearchingCell = false; // Resetear también en caso de error
            hideLoading();
            alert('Error al procesar el cambio');
            return;
        }

        // Si se edita PARADAS PROGRAMADAS, establecer TIEMPO PARA PROGRAMADA automáticamente
        if (currentColumn === 'paradas_programadas') {
            const tppSeconds = mapParadaToSeconds(newValue);
            // Incluir en el payload para que backend pueda recalcular con este dato
            payload['tiempo_para_programada'] = tppSeconds;
            payload[currentColumn] = newValue; // Asegurar que el payload tiene el valor correcto
        }

        console.log(`📤 Enviando PATCH a /tableros/${currentRowId}`);
        console.log(`📦 Payload:`, payload);
        
        const patchStart = performance.now();
        
        // 🎯 FIX: Actualizar la celda INMEDIATAMENTE en el front (Optimistic Update)
        // Sin esperar respuesta del servidor
        if (['hora_id', 'operario_id', 'maquina_id', 'tela_id'].includes(currentColumn)) {
            currentCell.dataset.value = displayName;
            currentCell.textContent = displayName;
            console.log(`✅ Celda actualizada INMEDIATAMENTE en el front: ${displayName}`);
        } else if (currentColumn !== 'paradas_programadas') {
            // Para otros campos que no son paradas_programadas, actualizar también inmediatamente
            currentCell.dataset.value = newValue;
            currentCell.textContent = formatDisplayValue(currentColumn, newValue);
        }
        
        fetch(`/tableros/${currentRowId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            timings.patchRequest = performance.now() - patchStart;
            console.log(`📥 Respuesta HTTP: ${response.status} (${timings.patchRequest.toFixed(2)}ms)`);
            return response.json();
        })
        .then(data => {
            console.log(`✅ Respuesta del servidor:`, data);
            console.log(`🔍 data.data existe:`, !!data.data);
            console.log(`🔍 data.data contenido:`, data.data);
            const totalTime = performance.now() - startTime;
            console.log(`⏱️ TIMINGS TOTALES:
            - Búsqueda: ${timings.searchRequest?.toFixed(2) || 'N/A'}ms
            - Cache hit: ${timings.cacheHit?.toFixed(2) || 'N/A'}ms
            - PATCH request: ${timings.patchRequest?.toFixed(2) || 'N/A'}ms
            - TOTAL: ${totalTime.toFixed(2)}ms
            `);
            if (data.success) {
                // 🎯 FIX: Si se cambió una relación que mapea a _id (en 'corte'), usar valor del servidor
                // O si cambió 'hora' directamente (en 'produccion')
                const isMappedColumn = ['operario_id', 'maquina_id', 'tela_id', 'hora_id'].includes(currentColumn);
                const isDirectHoraColumn = currentColumn === 'hora' && section === 'produccion';
                
                if (isMappedColumn || isDirectHoraColumn) {
                    // Usar el valor del servidor como source of truth
                    let serverDisplayValue = displayName; // Fallback al displayName calculado
                    
                    if ((currentColumn === 'hora_id' || currentColumn === 'hora') && data.data && data.data.hora) {
                        serverDisplayValue = data.data.hora;
                        console.log(`✅ Usando valor del servidor para hora: ${serverDisplayValue}`);
                        console.log(`📍 currentCell.textContent ANTES:`, currentCell.textContent);
                    }
                    
                    currentCell.dataset.value = serverDisplayValue;
                    currentCell.textContent = serverDisplayValue;
                    
                    if (currentColumn === 'hora_id' || currentColumn === 'hora') {
                        console.log(`📍 currentCell.textContent DESPUÉS:`, currentCell.textContent);
                        console.log(`✅ Celda confirmada después de PATCH - Valor: ${serverDisplayValue}`);
                    }
                    
                    // Actualizar el objeto local también con data.data si está disponible
                    if (data.data && registrosMap[section] && registrosMap[section][currentRowId]) {
                        if (currentColumn === 'hora_id' && data.data.hora) {
                            registrosMap[section][currentRowId].hora = data.data.hora;
                        } else if (currentColumn === 'operario_id' && data.data.operario) {
                            registrosMap[section][currentRowId].operario = data.data.operario;
                        } else if (currentColumn === 'maquina_id' && data.data.maquina) {
                            registrosMap[section][currentRowId].maquina = data.data.maquina;
                        } else if (currentColumn === 'tela_id' && data.data.tela) {
                            registrosMap[section][currentRowId].tela = data.data.tela;
                        }
                    }
                } else {
                    currentCell.dataset.value = newValue;
                    currentCell.textContent = formatDisplayValue(currentColumn, newValue);
                }

                // Si se editó una celda dependiente, actualizar también tiempo_disponible, meta y eficiencia
                if (['porcion_tiempo', 'numero_operarios', 'tiempo_parada_no_programada', 'tiempo_para_programada', 'tiempo_ciclo', 'cantidad', 'paradas_programadas', 'paradas_no_programadas', 'tipo_extendido', 'numero_capas', 'tiempo_trazado'].includes(currentColumn)) {
                    console.log('🔄 Actualizando celdas calculadas para cambio en:', currentColumn);
                    console.log('📦 data.data completo:', data.data);
                    console.log('⏱️ data.data.tiempo_disponible:', data.data?.tiempo_disponible);
                    console.log('📊 data.data.meta:', data.data?.meta);
                    console.log('📈 data.data.eficiencia:', data.data?.eficiencia);

                    // Si cambiamos paradas_programadas y el backend no devuelve tpp, actualizarlo localmente también
                    if (currentColumn === 'paradas_programadas') {
                        const tppCell = currentCell.closest('tr').querySelector('[data-column="tiempo_para_programada"]');
                        if (tppCell) {
                            const tppSeconds = mapParadaToSeconds(newValue);
                            tppCell.dataset.value = tppSeconds;
                            tppCell.textContent = formatDisplayValue('tiempo_para_programada', tppSeconds);
                            console.log(`✅ TPP actualizada a ${tppSeconds}s`);
                        }
                    }

                    const row = currentCell.closest('tr');
                    const tiempoDisponibleCell = row.querySelector('[data-column="tiempo_disponible"]');
                    console.log('🔍 tiempoDisponibleCell encontrado:', !!tiempoDisponibleCell);
                    if (tiempoDisponibleCell && data.data && data.data.tiempo_disponible !== undefined) {
                        tiempoDisponibleCell.dataset.value = data.data.tiempo_disponible;
                        tiempoDisponibleCell.textContent = formatDisplayValue('tiempo_disponible', data.data.tiempo_disponible);
                        console.log('✅ Tiempo disponible actualizado:', data.data.tiempo_disponible);
                    } else {
                        console.warn('⚠️ No se pudo actualizar tiempo_disponible. Cell:', !!tiempoDisponibleCell, 'Valor:', data.data?.tiempo_disponible);
                    }
                    
                    // 🎯 FIX: Actualizar tiempo_extendido si está disponible
                    const tiempoExtendidoCell = row.querySelector('[data-column="tiempo_extendido"]');
                    console.log('🔍 tiempoExtendidoCell encontrado:', !!tiempoExtendidoCell);
                    if (tiempoExtendidoCell && data.data && data.data.tiempo_extendido !== undefined) {
                        tiempoExtendidoCell.dataset.value = data.data.tiempo_extendido;
                        tiempoExtendidoCell.textContent = formatDisplayValue('tiempo_extendido', data.data.tiempo_extendido);
                        console.log('✅ Tiempo extendido actualizado:', data.data.tiempo_extendido);
                    } else if (tiempoExtendidoCell) {
                        console.warn('⚠️ No se pudo actualizar tiempo_extendido. Valor:', data.data?.tiempo_extendido);
                    }

                    const metaCell = row.querySelector('[data-column="meta"]');
                    console.log('🔍 metaCell encontrado:', !!metaCell);
                    if (metaCell && data.data && data.data.meta !== undefined) {
                        metaCell.dataset.value = data.data.meta;
                        metaCell.textContent = formatDisplayValue('meta', data.data.meta);
                        console.log('✅ Meta actualizada:', data.data.meta);
                    } else {
                        console.warn('⚠️ No se pudo actualizar meta. Cell:', !!metaCell, 'Valor:', data.data?.meta);
                    }

                    const eficienciaCell = row.querySelector('[data-column="eficiencia"]');
                    console.log('🔍 eficienciaCell encontrado:', !!eficienciaCell);
                    if (eficienciaCell && data.data && data.data.eficiencia !== undefined) {
                        eficienciaCell.dataset.value = data.data.eficiencia;
                        eficienciaCell.textContent = formatDisplayValue('eficiencia', data.data.eficiencia);
                        // Actualizar clase de formato condicional
                        eficienciaCell.className = eficienciaCell.className.replace(/eficiencia-\w+/g, '');
                        const newClass = getEficienciaClass(data.data.eficiencia);
                        if (newClass) {
                            eficienciaCell.classList.add(newClass);
                        }
                        console.log('✅ Eficiencia actualizada:', data.data.eficiencia);
                    } else {
                        console.warn('⚠️ No se pudo actualizar eficiencia. Cell:', !!eficienciaCell, 'Valor:', data.data?.eficiencia);
                    }

                    // Recalcular en el front como respaldo inmediato con los valores visibles
                    if (typeof recalculateRowDerivedValues === 'function') {
                        try {
                            recalculateRowDerivedValues(row);
                        } catch (e) {
                            console.warn('⚠️ Recalculo local fallido (esto es normal, el servidor ya recalculó):', e);
                        }
                    }
                }

                // ⚡ FAST: Cerrar modal y ocultar loading INMEDIATAMENTE
                closeEditModal();
                hideLoading();
                
                // Mostrar notificación de éxito
                showNotification('Cambios guardados correctamente', 'success');
            } else {
                hideLoading();
                console.error('❌ Error del servidor:', data.message);
                console.error('❌ Respuesta completa:', data);
                alert('Error al guardar: ' + data.message);
            }
        })
        .catch(error => {
            console.error('❌ Error de red:', error);
            hideLoading();
            alert('Error al guardar los cambios: ' + error.message);
        });
    }
    
    function showNotification(message, type = 'success') {
        // Crear notificación temporal
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Remover después de 3 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    function closeEditModal() {
        const modal = document.getElementById('editCellModal');
        if (modal) {
            modal.style.display = 'none';
            modal.style.opacity = '0';
            modal.style.visibility = 'hidden';
        }
        // ⚡ Resetear flag para permitir nuevas aperturas del modal
        modalOpening = false;
        currentCell = null;
        currentRowId = null;
        currentColumn = null;
    }

    function formatDisplayValue(column, value) {
                if (column === 'fecha' && value) {
                    const date = new Date(value);
                    return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
                }
                if (column === 'hora' && value) {
                    return value;
                }
                if (column === 'eficiencia' && value !== null) {
                    return Math.round(value * 100 * 10) / 10 + '%';
                }
        return value;
    }

    function getEficienciaClass(eficiencia) {
        if (eficiencia === null || eficiencia === undefined) return '';
        eficiencia = parseFloat(eficiencia);
        if (eficiencia < 0.7) return 'eficiencia-red';
        if (eficiencia >= 0.7 && eficiencia < 0.8) return 'eficiencia-yellow';
        if (eficiencia >= 0.8 && eficiencia < 1.0) return 'eficiencia-green';
        if (eficiencia >= 1.0) return 'eficiencia-blue';
        return '';
    }

    // Función para manejar duplicación de registros
    function duplicateRegistro(id, section) {
        // Mostrar loading
        showLoading('Duplicando registro...');
        
        // Hacer la petición para duplicar
        fetch(`/tableros/${id}/duplicate?section=${section}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Registro duplicado exitosamente:', data.registro);
                
                // Agregar el registro a la tabla INMEDIATAMENTE (sin esperar WebSocket)
                console.log('⚡ Agregando registro a la tabla inmediatamente...');
                agregarRegistroTiempoReal(data.registro, section);
                
                // Ocultar loading
                hideLoading();
                
                // Mostrar notificación de éxito
                showNotification('Registro duplicado correctamente', 'success');
            } else {
                console.error('Error al duplicar:', data.message);
                hideLoading();
                alert('Error al duplicar el registro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            hideLoading();
            alert('Error al duplicar el registro');
        });
    }

    // Función para manejar eliminación de registros
    function deleteRegistro(id, section) {
        // Mostrar modal de confirmación
        const modal = document.getElementById('deleteConfirmModal');
        if (modal) {
            // Resetear el modal a su estado original
            document.getElementById('deleteModalTitle').textContent = 'Confirmar Eliminación';
            document.getElementById('deleteModalBody').innerHTML = '<p>¿Estás seguro de que quieres eliminar este registro?</p>';
            document.getElementById('deleteModalFooter').style.display = 'flex';

            modal.style.display = 'flex';
            modal.style.opacity = '1';
            modal.style.visibility = 'visible';
            // Guardar id y section para usar en confirmDelete
            modal.dataset.deleteId = id;
            modal.dataset.deleteSection = section;
        }
    }

    let isDeleting = false; // Flag para prevenir múltiples eliminaciones

    function confirmDeleteRegistro() {
        // Prevenir múltiples clics
        if (isDeleting) {
            console.log('⏳ Ya hay una eliminación en proceso...');
            return;
        }

        const modal = document.getElementById('deleteConfirmModal');
        const id = modal.dataset.deleteId;
        const section = modal.dataset.deleteSection;

        // Mostrar loading
        showLoading('Eliminando registro...');

        // Deshabilitar el botón de eliminar
        const confirmBtn = document.getElementById('confirmDelete');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Eliminando...';
        }

        isDeleting = true;

        // Eliminar la fila INMEDIATAMENTE (optimista)
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) {
            row.style.transition = 'opacity 0.3s ease';
            row.style.opacity = '0';
            setTimeout(() => {
                row.remove();
                
                // Verificar si la página quedó vacía (solo si no estamos ya redirigiendo)
                if (!window.isRedirecting) {
                    const table = document.querySelector(`table[data-section="${section}"]`);
                    if (table) {
                        const tbody = table.querySelector('tbody');
                        const remainingRows = tbody ? tbody.querySelectorAll('tr[data-id]').length : 0;
                        
                        console.log(`Filas restantes en la página: ${remainingRows}`);
                        
                        // Si no quedan filas, ir a la página anterior
                        if (remainingRows === 0) {
                            const urlParams = new URLSearchParams(window.location.search);
                            const currentPage = parseInt(urlParams.get('page')) || 1;
                            
                            if (currentPage > 1) {
                                console.log(`Página vacía, redirigiendo a página ${currentPage - 1}`);
                                window.isRedirecting = true;
                                
                                // Esperar un poco antes de redirigir para evitar bucles
                                setTimeout(() => {
                                    urlParams.set('page', currentPage - 1);
                                    window.location.search = urlParams.toString();
                                }, 500);
                            }
                        }
                    }
                }
            }, 300);
        }
        
        // Cerrar el modal INMEDIATAMENTE
        closeDeleteModal();
        
        // Resetear el modal para el próximo uso
        setTimeout(() => {
            document.getElementById('deleteModalTitle').textContent = 'Confirmar Eliminación';
            document.getElementById('deleteModalBody').innerHTML = '<p>¿Estás seguro de que quieres eliminar este registro?</p>';
            document.getElementById('deleteModalFooter').style.display = 'flex';
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Eliminar';
            }
        }, 500);

        // Hacer la petición en segundo plano
        fetch(`/tableros/${id}?section=${section}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Registro eliminado del servidor:', id);
                
                // Ocultar loading
                hideLoading();
                
                // NO llamar a recargarDashboardCorte aquí
                // El dashboard se actualizará automáticamente por el evento WebSocket
                
                // Emitir evento personalizado para que otras ventanas actualicen
                window.dispatchEvent(new CustomEvent('registro-eliminado', { 
                    detail: { id, section } 
                }));
            } else {
                console.error('Error al eliminar:', data.message);
                hideLoading();
                // Si falla, recargar la página para restaurar el estado correcto
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            hideLoading();
            alert('Error al eliminar el registro');
            // Re-habilitar el botón si hay error
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Eliminar';
            }
        })
        .finally(() => {
            // Resetear el flag inmediatamente
            isDeleting = false;
        });
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteConfirmModal');
        if (modal) {
            modal.style.display = 'none';
            modal.style.opacity = '0';
            modal.style.visibility = 'hidden';
        }
    }

    // Agregar event listeners a los botones de eliminar
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn') || e.target.closest('.delete-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.delete-btn');
            const id = btn.dataset.id;
            const section = btn.dataset.section;
            deleteRegistro(id, section);
        }
        
        // Event listener para botones de duplicar
        if (e.target.classList.contains('duplicate-btn') || e.target.closest('.duplicate-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.duplicate-btn');
            const id = btn.dataset.id;
            const section = btn.dataset.section;
            duplicateRegistro(id, section);
        }
    });

    // Event listeners para el modal de eliminación
    document.getElementById('confirmDelete').addEventListener('click', confirmDeleteRegistro);
    document.getElementById('cancelDelete').addEventListener('click', closeDeleteModal);
    document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);
    document.getElementById('deleteConfirmModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Initialize filters for all sections on page load
    setTimeout(() => {
        initializeTableFilters('produccion');
        initializeTableFilters('polos');
        initializeTableFilters('corte');
    }, 100);
});

// Mover attachEditableCellListeners fuera para que sea global
window.attachEditableCellListeners = function() {
    const editableCells = document.querySelectorAll('.editable-cell');
    console.log('Celdas editables encontradas:', editableCells.length);
    editableCells.forEach(cell => {
        cell.removeEventListener('dblclick', handleCellDoubleClick);
        cell.addEventListener('dblclick', handleCellDoubleClick);
    });
}
</script>

<script>
// Recalcular en el front: tiempo_disponible, meta y eficiencia por fila
function recalculateRowDerivedValues(row) {
    if (!row) return;

    function getNumeric(cellSelector) {
        const cell = row.querySelector(cellSelector);
        if (!cell) return 0;
        const raw = cell.dataset.value ?? cell.textContent;
        const cleaned = (raw || '').toString().replace('%', '').replace(',', '.');
        const num = parseFloat(cleaned);
        return isNaN(num) ? 0 : num;
    }

    // Lectura de valores base
    const porcionTiempo = getNumeric('[data-column="porcion_tiempo"]');
    const numeroOperarios = getNumeric('[data-column="numero_operarios"]');
    const tnp = getNumeric('[data-column="tiempo_parada_no_programada"]');
    let tpp = getNumeric('[data-column="tiempo_para_programada"]');

    // Si hay texto en paradas_programadas, mapearlo a segundos
    const paradasCell = row.querySelector('[data-column="paradas_programadas"]');
    if (paradasCell) {
        const val = (paradasCell.dataset.value || paradasCell.textContent || '').toString().toUpperCase();
        const mapped = (val === 'DESAYUNO' || val === 'MEDIA TARDE') ? 900 : (val === 'NINGUNA' ? 0 : null);
        if (mapped !== null) tpp = mapped;
    }

    const tiempoCiclo = getNumeric('[data-column="tiempo_ciclo"]');
    const cantidad = getNumeric('[data-column="cantidad"]');

    // Cálculos
    const tiempoDisponible = (3600 * porcionTiempo * numeroOperarios) - tnp - tpp;
    const meta = tiempoCiclo > 0 ? (tiempoDisponible / tiempoCiclo) * 0.9 : 0;
    const eficiencia = meta > 0 ? (cantidad / meta) : 0; // ratio 0..n

    // Escribir en celdas
    const tdCell = row.querySelector('[data-column="tiempo_disponible"]');
    if (tdCell) {
        tdCell.dataset.value = tiempoDisponible;
        tdCell.textContent = formatDisplayValue('tiempo_disponible', tiempoDisponible.toFixed(2));
    }

    const metaCell = row.querySelector('[data-column="meta"]');
    if (metaCell) {
        metaCell.dataset.value = meta;
        metaCell.textContent = formatDisplayValue('meta', meta.toFixed(2));
    }

    const efCell = row.querySelector('[data-column="eficiencia"]');
    if (efCell) {
        efCell.dataset.value = eficiencia;
        efCell.textContent = formatDisplayValue('eficiencia', eficiencia);
        efCell.className = efCell.className.replace(/eficiencia-\w+/g, '');
        const cls = getEficienciaClass(eficiencia);
        if (cls) efCell.classList.add(cls);
    }
}
</script>
<!-- Real-time updates script -->
<script>
// Initialize real-time listeners for all tableros
function initializeRealtimeListeners() {
    console.log('=== TABLEROS - Inicializando Echo para tiempo real ===');
    console.log('window.Echo disponible:', !!window.Echo);

    // 🗺️ Inicializar el mapa de registros con los datos que ya están en las tablas
    // Esto es importante para que cuando se editen celdas, se actualice la relación correcta
    const tables = document.querySelectorAll('table[data-section]');
    tables.forEach(table => {
        const section = table.dataset.section;
        const rows = table.querySelectorAll('tbody tr[data-id]');
        rows.forEach(row => {
            const rowId = parseInt(row.dataset.id);
            // Por ahora solo guardamos una referencia al row, se actualizará cuando recibamos WebSocket updates
            if (!registrosMap[section]) {
                registrosMap[section] = {};
            }
            // Estos se actualizarán cuando lleguen los eventos de tiempo real
            // pero nos permite tener un registro incluso si solo hay datos en HTML
        });
    });
    
    console.log('✅ Mapa de registros inicializado. Contenido:', registrosMap);

    if (!window.Echo) {
        console.error('❌ Echo NO está disponible. Reintentando en 500ms...');
        setTimeout(initializeRealtimeListeners, 500);
        return;
    }

    // Verificar si ya hay suscripciones activas (de seguimiento-modulos)
    if (window.tablerosChannelSubscribed) {
        console.log('⚠️ Listeners de tableros ya inicializados, omitiendo...');
        return;
    }
    
    window.tablerosChannelSubscribed = true;
    console.log('✅ Echo disponible. Suscribiendo a canales...');

    // Canal de Producción
    const produccionChannel = window.Echo.channel('produccion');
    produccionChannel.subscribed(() => {
        console.log('✅ Suscrito al canal "produccion"');
    });
    produccionChannel.error((error) => {
        console.error('❌ Error en canal "produccion":', error);
    });
    produccionChannel.listen('ProduccionRecordCreated', (e) => {
        console.log('🎉 Evento ProduccionRecordCreated recibido!', e);
        
        // Si es un evento de eliminación
        if (e.registro && e.registro.deleted) {
            console.log('🗑️ Eliminando registro ID:', e.registro.id);
            const row = document.querySelector(`tr[data-id="${e.registro.id}"]`);
            if (row) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
            
            // También notificar al seguimiento para que se actualice
            console.log('📊 Notificando actualización al seguimiento de producción...');
            if (typeof recargarSeguimientoEspecifico === 'function') {
                recargarSeguimientoEspecifico('produccion');
            }
        } else {
            agregarRegistroTiempoReal(e.registro, 'produccion');
        }
    });

    // Canal de Polo
    const poloChannel = window.Echo.channel('polo');
    poloChannel.subscribed(() => {
        console.log('✅ Suscrito al canal "polo"');
    });
    poloChannel.error((error) => {
        console.error('❌ Error en canal "polo":', error);
    });
    poloChannel.listen('PoloRecordCreated', (e) => {
        console.log('🎉 Evento PoloRecordCreated recibido!', e);
        
        // Si es un evento de eliminación
        if (e.registro && e.registro.deleted) {
            console.log('🗑️ Eliminando registro ID:', e.registro.id);
            const row = document.querySelector(`tr[data-id="${e.registro.id}"]`);
            if (row) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
            
            // También notificar al seguimiento para que se actualice
            console.log('📊 Notificando actualización al seguimiento de polos...');
            if (typeof recargarSeguimientoEspecifico === 'function') {
                recargarSeguimientoEspecifico('polos');
            }
        } else {
            agregarRegistroTiempoReal(e.registro, 'polos');
        }
    });

    // Canal de Corte
    const corteChannel = window.Echo.channel('corte');
    corteChannel.subscribed(() => {
        console.log('✅ Suscrito al canal "corte"');
    });
    corteChannel.error((error) => {
        console.error('❌ Error en canal "corte":', error);
    });
    corteChannel.listen('CorteRecordCreated', (e) => {
        console.log('🎉 Evento CorteRecordCreated recibido!', e);
        
        // ⚡ OPTIMIZACIÓN: Solo procesar si la tabla de corte está visible
        // El dashboard-tables-corte.blade.php tiene su propio listener
        const corteTable = document.querySelector('table[data-section="corte"]');
        if (!corteTable) {
            console.log('⏭️ Tabla de corte no visible, ignorando evento');
            return;
        }
        
        // Si es un evento de eliminación
        if (e.registro && e.registro.deleted) {
            console.log('🗑️ Eliminando registro ID:', e.registro.id);
            const row = document.querySelector(`tr[data-id="${e.registro.id}"]`);
            if (row) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
        } else {
            // Es un evento de creación o actualización
            agregarRegistroTiempoReal(e.registro, 'corte');
        }
    });

    console.log('✅ Todos los listeners configurados');
}

// Función para agregar un registro en tiempo real a la tabla
function agregarRegistroTiempoReal(registro, section) {
    console.log(`Agregando registro en tiempo real a sección: ${section}`, registro);
    
    const table = document.querySelector(`table[data-section="${section}"]`);
    if (!table) {
        console.warn(`Tabla no encontrada para sección: ${section}`);
        // Aún así, actualizar el seguimiento con debounce
        console.log('📊 Actualizando seguimiento aunque no haya tabla visible...');
        if (typeof actualizarSeguimientoDebounced === 'function') {
            actualizarSeguimientoDebounced(section);
        }
        return;
    }

    const tbody = table.querySelector('tbody');
    if (!tbody) {
        console.warn(`tbody no encontrado en tabla de sección: ${section}`);
        return;
    }

    // Verificar si el registro ya existe
    const existingRow = tbody.querySelector(`tr[data-id="${registro.id}"]`);
    if (existingRow) {
        console.log(`Registro ${registro.id} ya existe, actualizando...`);
        // Actualizar fila existente
        actualizarFilaExistente(existingRow, registro, section);
        // ⚡ OPTIMIZACIÓN: Actualizar el seguimiento con debounce (no inmediatamente)
        if (typeof actualizarSeguimientoDebounced === 'function') {
            actualizarSeguimientoDebounced(section);
        }
        return;
    }
    
    // ⏳ NO actualizar el seguimiento ANTES de agregar - hacerlo DESPUÉS
    // Esto evita múltiples fetches innecesarios

    // Crear nueva fila
    const row = document.createElement('tr');
    row.className = 'table-row';
    row.setAttribute('data-id', registro.id);

    // Obtener columnas según la sección
    const columns = getColumnsForSection(section);
    
    // Crear celdas
    columns.forEach(column => {
        const td = document.createElement('td');
        td.className = 'table-cell editable-cell';
        td.setAttribute('data-column', column);
        td.title = 'Doble clic para editar';
        
        let value = registro[column];
        let displayValue = value;
        
        // Manejar relaciones (objetos)
        // En corte, hora es un objeto; en produccion/polos, es un string
        if ((column === 'hora' || column === 'hora_id') && registro.hora) {
            if (typeof registro.hora === 'object' && registro.hora.hora) {
                // Corte: hora es un objeto
                value = registro.hora.id;
                displayValue = registro.hora.hora;
            } else {
                // Produccion/Polos: hora es un string
                value = registro.hora;
                displayValue = registro.hora;
            }
        } else if ((column === 'operario' || column === 'operario_id') && registro.operario) {
            value = registro.operario.id;
            displayValue = registro.operario.name;
        } else if ((column === 'maquina' || column === 'maquina_id') && registro.maquina) {
            value = registro.maquina.id;
            displayValue = registro.maquina.nombre_maquina;
        } else if ((column === 'tela' || column === 'tela_id') && registro.tela) {
            value = registro.tela.id;
            displayValue = registro.tela.nombre_tela;
        } else if (column === 'fecha' && value) {
            displayValue = new Date(value).toLocaleDateString('es-ES');
        } else if (column === 'eficiencia' && value !== null) {
            // La eficiencia viene como decimal (0.85 = 85%)
            // Convertir a porcentaje con 1 decimal
            const eficienciaDecimal = parseFloat(value);
            // Si el valor es mayor a 10, probablemente ya está en porcentaje (error de datos)
            const eficienciaPorcentaje = eficienciaDecimal > 10 ? eficienciaDecimal : eficienciaDecimal * 100;
            displayValue = Math.round(eficienciaPorcentaje * 10) / 10 + '%';
            td.classList.add(getEficienciaClass(eficienciaDecimal > 10 ? eficienciaDecimal / 100 : eficienciaDecimal));
        }
        
        td.setAttribute('data-value', value);
        td.textContent = displayValue || '';
        row.appendChild(td);
    });

    // Agregar celda de acciones con botones de duplicar y eliminar
    const actionTd = document.createElement('td');
    actionTd.className = 'table-cell';
    actionTd.innerHTML = `
        <div class="action-buttons">
            <button class="duplicate-btn" data-id="${registro.id}" data-section="${section}" title="Duplicar registro">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </button>
            <button class="delete-btn" data-id="${registro.id}" data-section="${section}" title="Eliminar registro">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
        </div>
    `;
    row.appendChild(actionTd);

    // Insertar fila en la posición correcta según el ID (orden descendente - más reciente primero)
    const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
    let inserted = false;
    
    for (let i = 0; i < rows.length; i++) {
        const existingId = parseInt(rows[i].getAttribute('data-id'));
        const newId = parseInt(registro.id);
        
        // Orden descendente: insertar antes si el nuevo ID es MAYOR
        if (newId > existingId) {
            tbody.insertBefore(row, rows[i]);
            inserted = true;
            break;
        }
    }
    
    // Si no se insertó antes de ninguna fila, agregar al final
    if (!inserted) {
        tbody.appendChild(row);
    }
    
    // Animación de entrada
    row.style.backgroundColor = 'rgba(34, 197, 94, 0.2)';
    setTimeout(() => {
        row.style.transition = 'background-color 1s ease';
        row.style.backgroundColor = '';
    }, 100);

    // Adjuntar event listeners de edición a las nuevas celdas
    if (window.attachEditableCellListeners) {
        window.attachEditableCellListeners();
    }

    // 🗺️ Guardar referencia al registro para poder actualizarlo si se edita
    registrosMap[section][registro.id] = registro;

    console.log(`✅ Registro ${registro.id} agregado a la tabla de ${section}`);
    
    // ⚡ OPTIMIZACIÓN: Actualizar el seguimiento con debounce (no inmediatamente)
    if (typeof actualizarSeguimientoDebounced === 'function') {
        actualizarSeguimientoDebounced(section);
    }
}

// Función auxiliar para actualizar fila existente
function actualizarFilaExistente(row, registro, section) {
    const columns = getColumnsForSection(section);
    const cells = row.querySelectorAll('.editable-cell');
    
    columns.forEach((column, index) => {
        if (cells[index]) {
            let value = registro[column];
            let displayValue = value;
            
            // Manejar relaciones (objetos)
            // En corte, hora es un objeto; en produccion/polos, es un string
            if ((column === 'hora' || column === 'hora_id') && registro.hora) {
                if (typeof registro.hora === 'object' && registro.hora.hora) {
                    // Corte: hora es un objeto
                    value = registro.hora.id;
                    displayValue = registro.hora.hora;
                } else {
                    // Produccion/Polos: hora es un string
                    value = registro.hora;
                    displayValue = registro.hora;
                }
            } else if ((column === 'operario' || column === 'operario_id') && registro.operario) {
                value = registro.operario.id;
                displayValue = registro.operario.name;
            } else if ((column === 'maquina' || column === 'maquina_id') && registro.maquina) {
                value = registro.maquina.id;
                displayValue = registro.maquina.nombre_maquina;
            } else if ((column === 'tela' || column === 'tela_id') && registro.tela) {
                value = registro.tela.id;
                displayValue = registro.tela.nombre_tela;
            } else if (column === 'fecha' && value) {
                displayValue = new Date(value).toLocaleDateString('es-ES');
            } else if (column === 'eficiencia' && value !== null) {
                // La eficiencia viene como decimal (0.85 = 85%)
                const eficienciaDecimal = parseFloat(value);
                // Si el valor es mayor a 10, probablemente ya está en porcentaje (error de datos)
                const eficienciaPorcentaje = eficienciaDecimal > 10 ? eficienciaDecimal : eficienciaDecimal * 100;
                displayValue = Math.round(eficienciaPorcentaje * 10) / 10 + '%';
                cells[index].className = 'table-cell editable-cell ' + getEficienciaClass(eficienciaDecimal > 10 ? eficienciaDecimal / 100 : eficienciaDecimal);
            }
            
            cells[index].setAttribute('data-value', value);
            cells[index].textContent = displayValue || '';
        }
    });
    
    // Animación de actualización
    row.style.backgroundColor = 'rgba(59, 130, 246, 0.2)';
    setTimeout(() => {
        row.style.transition = 'background-color 1s ease';
        row.style.backgroundColor = '';
    }, 100);
    
    // Adjuntar event listeners de edición a las celdas actualizadas
    if (window.attachEditableCellListeners) {
        window.attachEditableCellListeners();
    }

    // 🗺️ Actualizar referencia al registro en el mapa
    registrosMap[section][registro.id] = registro;
}

// Función auxiliar para obtener columnas según sección
function getColumnsForSection(section) {
    // Estas columnas deben coincidir con las definidas en el controlador
    // IMPORTANTE: El orden debe coincidir EXACTAMENTE con el orden de las columnas en la base de datos
    const columnMap = {
        'produccion': ['fecha', 'modulo', 'orden_produccion', 'hora', 'tiempo_ciclo', 'porcion_tiempo', 'cantidad', 'paradas_programadas', 'paradas_no_programadas', 'tiempo_parada_no_programada', 'numero_operarios', 'tiempo_para_programada', 'tiempo_disponible', 'meta', 'eficiencia'],
        'polos': ['fecha', 'modulo', 'orden_produccion', 'hora', 'tiempo_ciclo', 'porcion_tiempo', 'cantidad', 'paradas_programadas', 'paradas_no_programadas', 'tiempo_parada_no_programada', 'numero_operarios', 'tiempo_para_programada', 'tiempo_disponible', 'meta', 'eficiencia'],
        'corte': ['fecha', 'orden_produccion', 'porcion_tiempo', 'cantidad', 'tiempo_ciclo', 'paradas_programadas', 'tiempo_para_programada', 'paradas_no_programadas', 'tiempo_parada_no_programada', 'tipo_extendido', 'numero_capas', 'tiempo_extendido', 'trazado', 'tiempo_trazado', 'actividad', 'tiempo_disponible', 'meta', 'eficiencia', 'hora', 'operario', 'maquina', 'tela']
    };
    return columnMap[section] || [];
}

// Función auxiliar para obtener clase de eficiencia
function getEficienciaClass(eficiencia) {
    if (eficiencia === null) return '';
    const value = parseFloat(eficiencia);
    if (value < 70) return 'eficiencia-red';
    if (value >= 70 && value < 80) return 'eficiencia-yellow';
    if (value >= 80 && value < 100) return 'eficiencia-green';
    if (value >= 100) return 'eficiencia-blue';
    return '';
}

// Función para actualizar tablas de seguimiento cuando se aplica filtro de fecha
window.updateDashboardTablesFromFilter = function(searchParams) {
    console.log('Actualizando tablas de seguimiento con filtros:', searchParams.toString());
    
    // Detectar qué tablero está activo buscando el elemento visible
    let currentSection = 'produccion'; // Default
    
    // Método 1: Buscar el tab-card con clase 'active'
    const activeTabCard = document.querySelector('.tab-card.active');
    if (activeTabCard) {
        const tabText = activeTabCard.textContent.toLowerCase();
        if (tabText.includes('produccion')) {
            currentSection = 'produccion';
        } else if (tabText.includes('polos')) {
            currentSection = 'polos';
        } else if (tabText.includes('corte')) {
            currentSection = 'corte';
        }
        console.log('🎯 Tablero detectado por tab-card activo:', currentSection);
    } else {
        // Método 2: Buscar el contenedor visible (sin display: none)
        const visibleTab = document.querySelector('.chart-placeholder:not([style*="display: none"])');
        if (visibleTab) {
            const xShow = visibleTab.getAttribute('x-show');
            if (xShow) {
                if (xShow.includes('produccion')) currentSection = 'produccion';
                else if (xShow.includes('polos')) currentSection = 'polos';
                else if (xShow.includes('corte')) currentSection = 'corte';
            }
        }
        console.log('🎯 Tablero detectado por contenedor visible:', currentSection);
    }
    
    // Construir URL con filtros
    const url = new URL(window.location.origin + window.location.pathname);
    searchParams.forEach((value, key) => {
        url.searchParams.set(key, value);
    });
    
    // Agregar parámetro de sección para que el backend sepa qué tablero filtrar
    url.searchParams.set('active_section', currentSection);
    
    // Agregar parámetro para indicar que solo queremos el componente de seguimiento
    url.searchParams.set('component_only', 'true');
    
    // Buscar el contenedor del componente de seguimiento por ID específico
    const containerId = `seguimiento-container-${currentSection}`;
    console.log(`🔍 Buscando contenedor con ID: ${containerId}`);
    
    const seguimientoContainer = document.getElementById(containerId);
    
    if (!seguimientoContainer) {
        console.log(`❌ No se encontró contenedor de seguimiento para ${currentSection}`);
        console.log('📋 Contenedores disponibles:', 
            Array.from(document.querySelectorAll('[id^="seguimiento-container-"]')).map(el => el.id)
        );
        console.log('⚠️ Recargando página completa...');
        window.location.href = url.toString();
        return;
    }
    
    console.log('✅ Contenedor de seguimiento encontrado:', seguimientoContainer);
    
    // Mostrar indicador de carga
    seguimientoContainer.style.opacity = '0.5';
    seguimientoContainer.style.pointerEvents = 'none';
    
    // Hacer petición AJAX
    fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(html => {
        console.log('HTML recibido para seguimiento');
        
        // Crear un documento temporal para parsear el HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Buscar el nuevo contenedor de seguimiento en el HTML recibido por ID
        const newContainerId = `seguimiento-container-${currentSection}`;
        console.log(`🔍 Buscando nuevo contenedor con ID: ${newContainerId}`);
        
        const newSeguimientoContainer = tempDiv.querySelector(`#${newContainerId}`);
        
        if (!newSeguimientoContainer) {
            console.log('❌ No se encontró nuevo contenedor en HTML recibido');
            console.log('📋 IDs disponibles en HTML recibido:', 
                Array.from(tempDiv.querySelectorAll('[id^="seguimiento-container-"]')).map(el => el.id)
            );
        }
        
        if (newSeguimientoContainer && seguimientoContainer) {
            // Reemplazar el contenido completo del contenedor
            console.log('🔄 Reemplazando contenido del contenedor...');
            seguimientoContainer.innerHTML = newSeguimientoContainer.innerHTML;
            console.log('✅ Componente de seguimiento actualizado completamente');
            
            // Restaurar opacidad
            seguimientoContainer.style.opacity = '1';
            seguimientoContainer.style.pointerEvents = 'auto';
            
            // Actualizar URL sin recargar
            window.history.pushState({}, '', url.toString());
            
            console.log('✅ Filtro aplicado exitosamente');
            return;
        }
        
        // Si no se encontró el nuevo contenedor, recargar la página
        console.log('❌ No se pudo actualizar el componente, recargando página...');
        window.location.href = url.toString();
    })
    .catch(error => {
        console.error('Error al aplicar filtros:', error);
        
        // Restaurar opacidad
        if (seguimientoContainer) {
            seguimientoContainer.style.opacity = '1';
            seguimientoContainer.style.pointerEvents = 'auto';
        }
        
        alert('Error al aplicar filtros. Por favor, intenta de nuevo.');
    });
}

// Función para actualizar tabla de seguimiento
function updateSeguimientoTable(section, data) {
    console.log(`Actualizando tabla de seguimiento para ${section}`, data);
    // TODO: Implementar actualización dinámica de la tabla de seguimiento
    // Por ahora solo mostramos un mensaje en consola
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initializeRealtimeListeners, 100);
    });
} else {
    setTimeout(initializeRealtimeListeners, 100);
}

// Paginación AJAX sin recargar la página
function initializePaginationAjax() {
    console.log('🔧 Inicializando event listeners de paginación AJAX');
    
    document.addEventListener('click', function(e) {
        console.log('👆 Click detectado en:', e.target);
        
        // Buscar si el click fue en un enlace o botón de paginación
        const paginationLink = e.target.closest('.pagination-link, .pagination-btn:not([disabled])');
        
        if (paginationLink) {
            console.log('🎯 Click en elemento de paginación:', paginationLink);
            console.log('🎯 Texto del elemento:', paginationLink.textContent.trim());
            console.log('🎯 Tag:', paginationLink.tagName);
            console.log('🎯 Clases:', paginationLink.className);
            
            // Si es un botón activo, no hacer nada
            if (paginationLink.classList.contains('active')) {
                console.log('⚠️ Es el botón activo, no hacer nada');
                return;
            }
            
            e.preventDefault();
            console.log('✋ Evento preventDefault aplicado');
            
            let page = null;
            
            // Primero intentar obtener de data-page
            if (paginationLink.dataset.page) {
                page = paginationLink.dataset.page;
                console.log('📊 Página obtenida del data-page:', page);
            }
            // Si es un enlace, obtener la página de la URL
            else if (paginationLink.tagName === 'A' && paginationLink.href) {
                const url = new URL(paginationLink.href);
                page = url.searchParams.get('page');
                console.log('🔗 Página obtenida del enlace:', page);
            }
            // Si es un botón, obtener el número del texto
            else if (paginationLink.tagName === 'BUTTON') {
                const pageText = paginationLink.textContent.trim();
                // Verificar si es un número
                if (!isNaN(pageText)) {
                    page = pageText;
                    console.log('🔘 Página obtenida del botón:', page);
                }
            }
            
            if (!page) {
                console.log('❌ No se pudo obtener el número de página');
                return;
            }
            
            // Determinar qué tabla actualizar según el contenedor padre
            const paginationContainer = paginationLink.closest('.table-pagination');
            let section = 'produccion'; // Default
            
            if (paginationContainer) {
                section = paginationContainer.dataset.section || 'produccion';
            }
            
            console.log('🎯 Sección detectada:', section);
            
            console.log('📋 Sección determinada:', section);
            
            // ✨ ACTUALIZAR BOTÓN ACTIVO INMEDIATAMENTE (antes del AJAX)
            updateActiveButtonImmediately(paginationLink, section, page);
            
            // Hacer petición AJAX
            loadPage(page, section);
        } else {
            console.log('❌ Click NO fue en elemento de paginación');
        }
    });
    
    console.log('✅ Event listeners de paginación inicializados');
}

// Función para actualizar el botón activo INMEDIATAMENTE al hacer click
function updateActiveButtonImmediately(clickedElement, section, page) {
    console.log(`🚀 Actualizando botón activo INMEDIATAMENTE: página ${page} en ${section}`);
    console.log('Elemento clickeado:', clickedElement);
    console.log('Clases antes:', clickedElement.className);
    
    const paginationContainer = document.querySelector(`[data-section="${section}"]`);
    if (!paginationContainer) {
        console.error('❌ No se encontró paginationContainer para sección:', section);
        return;
    }
    
    console.log('✅ paginationContainer encontrado:', paginationContainer);
    
    const paginationNav = paginationContainer.querySelector('.pagination');
    if (!paginationNav) {
        console.error('❌ No se encontró paginationNav dentro de:', paginationContainer);
        console.log('🔍 Elementos dentro del container:', paginationContainer.innerHTML);
        
        // Buscar en todo el documento como fallback
        const allPaginations = document.querySelectorAll('.pagination');
        console.log('🔍 Todas las paginaciones encontradas:', allPaginations.length);
        
        if (allPaginations.length > 0) {
            // Buscar la paginación de la sección correcta
            let fallbackNav = null;
            
            allPaginations.forEach(pagination => {
                const paginationContainer = pagination.closest('.table-pagination');
                if (paginationContainer && paginationContainer.dataset.section === section) {
                    fallbackNav = pagination;
                    console.log(`🎯 Paginación encontrada para sección ${section}:`, fallbackNav);
                }
            });
            
            // Si no encuentra la sección específica, usar la primera
            if (!fallbackNav) {
                fallbackNav = allPaginations[0];
                console.log('🔄 Usando paginación fallback (primera encontrada):', fallbackNav);
            }
            
            // PASO 1: Quitar estilos de TODOS los elementos
            fallbackNav.querySelectorAll('button, a').forEach((element, index) => {
                const hadActive = element.classList.contains('active');
                element.classList.remove('active');
                
                // Quitar estilos directos también
                element.style.background = '';
                element.style.color = '';
                element.style.boxShadow = '';
                
                console.log(`${index}: "${element.textContent.trim()}" - Tenía active: ${hadActive}, Estilos removidos`);
            });
            
            // PASO 2: Agregar 'active' al elemento clickeado
            clickedElement.classList.add('active');
            console.log(`✅ Clases después de agregar active: ${clickedElement.className}`);
            
            // PASO 3: Aplicar estilos directamente SOLO al elemento clickeado
            clickedElement.style.background = 'linear-gradient(135deg, #f97316 0%, #fb923c 100%)';
            clickedElement.style.color = 'white';
            clickedElement.style.boxShadow = '0 4px 12px rgba(249, 115, 22, 0.4)';
            console.log('🎨 Estilos aplicados directamente al elemento clickeado');
        }
        return;
    }
    
    console.log('📋 Elementos encontrados en paginación:');
    
    // PASO 1: Quitar 'active' y estilos de TODOS los botones/enlaces
    paginationNav.querySelectorAll('button, a').forEach((element, index) => {
        const hadActive = element.classList.contains('active');
        element.classList.remove('active');
        
        // Quitar estilos directos también
        element.style.background = '';
        element.style.color = '';
        element.style.boxShadow = '';
        
        console.log(`${index}: "${element.textContent.trim()}" - Tenía active: ${hadActive}, Estilos removidos`);
    });
    
    // PASO 2: Agregar 'active' al elemento clickeado
    clickedElement.classList.add('active');
    console.log(`✅ Clases después de agregar active: ${clickedElement.className}`);
    
    // PASO 3: Aplicar estilos directamente SOLO al elemento clickeado
    clickedElement.style.background = 'linear-gradient(135deg, #f97316 0%, #fb923c 100%)';
    clickedElement.style.color = 'white';
    clickedElement.style.boxShadow = '0 4px 12px rgba(249, 115, 22, 0.4)';
    console.log('🎨 Estilos aplicados directamente al elemento clickeado');
    
    // PASO 5: Actualizar barra de progreso inmediatamente (estimado)
    const progressFill = paginationContainer.querySelector('.progress-fill');
    if (progressFill) {
        // Obtener total de páginas del último enlace visible
        const allPageNumbers = [];
        paginationNav.querySelectorAll('button, a').forEach(element => {
            const pageText = element.textContent.trim();
            const pageNumber = parseInt(pageText);
            if (!isNaN(pageNumber)) {
                allPageNumbers.push(pageNumber);
            }
        });
        
        const maxPage = Math.max(...allPageNumbers);
        if (maxPage > 0) {
            const progressPercent = (parseInt(page) / maxPage) * 100;
            progressFill.style.width = progressPercent + '%';
            console.log(`📊 Barra de progreso actualizada INMEDIATAMENTE: ${progressPercent}%`);
        }
    }
}

function loadPage(page, section) {
    console.log(`Cargando página ${page} para sección ${section}`);
    
    const url = new URL(window.location.origin + window.location.pathname);
    url.searchParams.set('page', page);
    
    // Copiar otros parámetros existentes (filtros, etc.)
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.forEach((value, key) => {
        if (key !== 'page') {
            url.searchParams.set(key, value);
        }
    });
    
    // Mostrar indicador de carga
    const paginationContainer = document.querySelector(`[data-section="${section}"]`);
    const tableContainer = paginationContainer ? paginationContainer.closest('.chart-placeholder') : null;
    const tableBody = tableContainer ? tableContainer.querySelector('.table-body') : null;
    
    if (tableBody) {
        tableBody.style.opacity = '0.5';
        tableBody.style.pointerEvents = 'none';
    }
    
    fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Datos recibidos:', data);
        
        try {
            // Actualizar la tabla según la sección
            if (section === 'produccion' && data.registros) {
                updateTableContent(data.registros, data.columns, 'produccion');
                updatePaginationInfo(data.pagination, 'produccion');
                updatePaginationLinks(data.pagination, 'produccion');
            } else if (section === 'polos' && data.registrosPolos) {
                updateTableContent(data.registrosPolos, data.columnsPolos, 'polos');
                updatePaginationInfo(data.paginationPolos, 'polos');
                updatePaginationLinks(data.paginationPolos, 'polos');
            } else if (section === 'corte' && data.registrosCorte) {
                updateTableContent(data.registrosCorte, data.columnsCorte, 'corte');
                updatePaginationInfo(data.paginationCorte, 'corte');
                updatePaginationLinks(data.paginationCorte, 'corte');
            }
            
            // Actualizar URL sin recargar
            window.history.pushState({}, '', url.toString());
            
            console.log('✅ Tabla actualizada exitosamente');
        } catch (updateError) {
            console.error('Error al actualizar tabla:', updateError);
            // No recargar la página, solo mostrar el error
        } finally {
            // Restaurar opacidad siempre
            if (tableBody) {
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
            }
            
            // Scroll suave a la tabla
            if (tableContainer) {
                tableContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    })
    .catch(error => {
        console.error('Error al cargar página:', error);
        // NO recargar la página, solo restaurar el estado
        if (tableBody) {
            tableBody.style.opacity = '1';
            tableBody.style.pointerEvents = 'auto';
        }
    });
}

// Función para actualizar los enlaces de paginación
function updatePaginationLinks(pagination, section) {
    const paginationContainer = document.querySelector(`[data-section="${section}"]`);
    if (!paginationContainer || !pagination) return;
    
    console.log(`🔄 Actualizando paginación para ${section}, página actual: ${pagination.current_page}`);
    
    // Actualizar el HTML de los enlaces de paginación directamente desde el servidor
    const paginationControls = paginationContainer.querySelector('.pagination-controls');
    if (paginationControls && pagination.links_html) {
        paginationControls.innerHTML = pagination.links_html;
        console.log(`✅ Enlaces de paginación actualizados desde el servidor`);
    }
    
    // Actualizar la información de paginación
    const paginationInfo = paginationContainer.querySelector('.pagination-info span');
    if (paginationInfo && pagination.first_item && pagination.last_item) {
        paginationInfo.textContent = `Mostrando ${pagination.first_item}-${pagination.last_item} de ${pagination.total} registros`;
    }
    
    // Actualizar barra de progreso
    const progressFill = paginationContainer.querySelector('.progress-fill');
    if (progressFill && pagination.last_page > 0) {
        const progressPercent = (pagination.current_page / pagination.last_page) * 100;
        progressFill.style.width = progressPercent + '%';
        console.log(`📊 Barra de progreso: ${progressPercent}%`);
    }
    
    console.log(`✅ Paginación actualizada para ${section}`);
}

function updateTableContent(registros, columns, section) {
    console.log(`Actualizando contenido de tabla para ${section}`, registros.length, 'registros');
    
    // Buscar el tbody de la sección correcta
    const allTableBodies = document.querySelectorAll('.table-body');
    let tableBody = null;
    
    // Encontrar el tbody correcto según la sección
    allTableBodies.forEach(tbody => {
        const table = tbody.closest('table');
        if (table && table.dataset.section === section) {
            tableBody = tbody;
            console.log(`✅ Tabla encontrada para sección ${section}:`, table);
        }
    });
    
    if (!tableBody) {
        console.error(`No se encontró tabla para la sección: ${section}`);
        return;
    }
    
    console.log('Tabla encontrada, actualizando contenido...');
    tableBody.innerHTML = '';
    
    registros.forEach(registro => {
        const row = document.createElement('tr');
        row.className = 'table-row';
        row.dataset.id = registro.id;
        
        columns.forEach(column => {
            const td = document.createElement('td');
            td.className = 'table-cell editable-cell';
            td.dataset.column = column;
            td.title = 'Doble clic para editar';
            
            let value = registro[column];
            let displayValue = value;
            
            // Formatear valores especiales según la sección
            if (column === 'fecha' && value) {
                displayValue = new Date(value).toLocaleDateString('es-ES');
            } else if (column === 'eficiencia' && value !== null) {
                displayValue = Math.round(value * 100 * 10) / 10 + '%';
                td.classList.add(getEficienciaClass(value));
            } else if (section === 'corte') {
                // Formateo específico para tabla de Corte
                if (column === 'hora_id' && registro.hora_display) {
                    displayValue = registro.hora_display;
                } else if (column === 'operario_id' && registro.operario_display) {
                    displayValue = registro.operario_display;
                } else if (column === 'maquina_id' && registro.maquina_display) {
                    displayValue = registro.maquina_display;
                } else if (column === 'tela_id' && registro.tela_display) {
                    displayValue = registro.tela_display;
                }
            }
            // Las tablas de 'produccion' y 'polos' usan el formateo estándar
            
            td.dataset.value = value;
            td.textContent = displayValue;
            row.appendChild(td);
        });
        
        // Agregar botón de eliminar
        const deleteTd = document.createElement('td');
        deleteTd.className = 'table-cell';
        deleteTd.innerHTML = `
            <button class="delete-btn" data-id="${registro.id}" data-section="${section}" title="Eliminar registro">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                    <path d="M3 6h18"/>
                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>
        `;
        row.appendChild(deleteTd);
        
        tableBody.appendChild(row);
    });
    
    console.log(`Tabla actualizada con ${registros.length} registros`);
    
    // Reinicializar event listeners
    if (typeof window.attachEditableCellListeners === 'function') {
        window.attachEditableCellListeners();
    } else {
        console.warn('attachEditableCellListeners no está disponible');
    }
}

function updatePaginationInfo(pagination, section) {
    const paginationContainer = document.querySelector(`[data-section="${section}"]`);
    if (!paginationContainer || !pagination) return;
    
    // Actualizar texto de información
    const paginationInfo = paginationContainer.querySelector('.pagination-info span');
    if (paginationInfo) {
        const firstItem = ((pagination.current_page - 1) * pagination.per_page) + 1;
        const lastItem = Math.min(pagination.current_page * pagination.per_page, pagination.total);
        paginationInfo.textContent = `Mostrando ${firstItem}-${lastItem} de ${pagination.total} registros`;
    }
    
    // Actualizar barra de progreso
    const progressFill = paginationContainer.querySelector('.progress-fill');
    if (progressFill) {
        const progress = (pagination.current_page / pagination.last_page) * 100;
        progressFill.style.width = progress + '%';
    }
}
</script>

<!-- Paginación AJAX simple -->
<script src="{{ asset('js/tableros-pagination.js') }}"></script>
@endsection