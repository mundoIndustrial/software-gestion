@extends('layouts.asistencia-clean')

@section('page-title', 'Asistencia Personal')

@section('content')
<div class="asistencia-content">
    <!-- Sección de Controles -->
    <div class="controls-section">
        <h2 class="section-title">Gestión de Asistencia</h2>
        <div class="buttons-grid">
            <button class="btn btn-primary" id="insertReportBtn">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
                <span>Insertar Reporte (PDF)</span>
            </button>
            <button class="btn btn-success" id="saveReportBtn">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Guardar Reporte</span>
            </button>
        </div>
    </div>

    <!-- Input oculto para PDF -->
    <input type="file" id="pdfInput" accept=".pdf" style="display: none;">

    <!-- Sección de Tabla de Reportes Guardados -->
    <div class="reports-list-section">
        <h3 class="subsection-title">Reportes Guardados</h3>
        <div class="table-wrapper">
            <table class="reports-list-table">
                <thead>
                    <tr>
                        <th>Número de Reporte</th>
                        <th>Nombre</th>
                        <th>Fecha de Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="reportsListBody">
                    @forelse ($reportes as $reporte)
                        <tr>
                            <td>{{ $reporte->numero_reporte }}</td>
                            <td>{{ $reporte->nombre_reporte }}</td>
                            <td>{{ $reporte->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <button class="btn-action btn-view" data-id="{{ $reporte->id }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-cell">No hay reportes guardados aún</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/asistencia-personal.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('js/asistencia-personal.js') }}"></script>
@endsection