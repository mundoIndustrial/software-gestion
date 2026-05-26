@extends('layouts.lavanderia')

@section('content')
<div class="lavanderia-container">
    <div class="lavanderia-content">
        
        <!-- SECCIÓN: BOTÓN REGISTRAR SALIDA -->
        <div class="salida-section">
            <div class="section-header">
                <div class="section-icon">
                    <span class="material-symbols-rounded">local_shipping</span>
                </div>
                <div>
                    <h2 class="section-title">Registrar Salida</h2>
                    <p class="section-subtitle">Busca y registra prendas que salen de lavandería</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary" id="btnAbrirModalSalida" style="width: 100%;">
                <span class="material-symbols-rounded">add_circle</span>
                Nueva Salida
            </button>
        </div>

        <!-- SECCIÓN: TABLA DE CONTROL -->
        <div class="control-section">
            <div class="section-header">
                <div class="section-icon">
                    <span class="material-symbols-rounded">list_alt</span>
                </div>
                <div>
                    <h2 class="section-title">Control de Movimientos</h2>
                    <p class="section-subtitle">Historial de salidas y llegadas</p>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="control-table">
                    <thead>
                        <tr>
                            <th>Recibo / Tipo</th>
                            <th>Cliente</th>
                            <th>Prenda / Tallas Enviadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px;">
                                <div class="empty-state">
                                    <div class="empty-icon">📦</div>
                                    <h3 class="empty-title">Sin movimientos</h3>
                                    <p class="empty-text">No hay registros de salidas aún</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- MODAL: REGISTRAR SALIDA -->
<div class="modal" id="modalSalida">
    <div class="modal-content modal-salida-content">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Registrar Salida de Lavandería</h3>
            </div>
            <button type="button" class="modal-close">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Número de Recibo</label>
                <div class="search-wrapper">
                    <input 
                        type="text" 
                        id="searchRecibo" 
                        class="form-input search-input-large" 
                        placeholder="Ejemplo: C-101, B-201..."
                        autocomplete="off"
                    >
                    <div class="autocomplete-results"></div>
                </div>
            </div>

            <!-- Información del Recibo (se muestra después de seleccionar) -->
            <div id="reciboInfo" style="display: none; margin-top: 20px;">
                <div class="recibo-info-card">
                    <div class="info-row">
                        <span class="info-label">Cliente</span>
                        <span class="info-value" id="infoCliente">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Prenda a Despachar</span>
                        <span class="info-value" id="infoPrenda">-</span>
                    </div>
                </div>

                <!-- Selección de Tallas -->
                <div style="margin-top: 24px;">
                    <h3 class="form-label" style="margin-bottom: 16px;">Prendas por Talla para la Salida</h3>
                    <div id="tallasContainer" class="tallas-container">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>

                <!-- Botón Registrar -->
                <button type="button" class="btn btn-primary" id="btnRegistrarSalida" style="margin-top: 24px; width: 100%;">
                    <span class="material-symbols-rounded">check_circle</span>
                    Registrar Salida
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/lavanderia.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
@endpush

@push('scripts')
    <script>
        const apiSearchUrl = "{{ route('gestion-lavanderia.api.search-recibos') }}";
    </script>
    <script src="{{ asset('js/lavanderia/lavanderia.js') }}"></script>
@endpush
