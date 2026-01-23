@extends('bordado.layout')

@section('title', 'Bordado - Lista de Cotizaciones')
@section('page-title', 'Lista de Cotizaciones')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/bordado/cotizaciones-lista.css') }}">
@endpush

@section('content')
<div class="cotizaciones-lista-container">
    <!-- Header con búsqueda -->
    <div class="lista-header">
        <div class="search-box">
            <span class="material-symbols-rounded search-icon">search</span>
            <input 
                type="text" 
                id="buscadorCotizaciones" 
                class="search-input" 
                placeholder="Buscar por cliente, número, fecha..."
                onkeyup="filtrarCotizaciones()">
        </div>
    </div>

    <!-- Tabla de Cotizaciones -->
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <!-- TABLA HEAD -->
                <div class="table-head">
                    <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                        @php
                            $columns = [
                                ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 150px', 'justify' => 'flex-start'],
                                ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 120px', 'justify' => 'center'],
                                ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '1 1 200px', 'justify' => 'flex-start'],
                                ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 140px', 'justify' => 'center'],
                            ];
                        @endphp
                        @foreach($columns as $column)
                            <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                                <span class="header-text">{{ $column['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- TABLA BODY -->
                <div class="modern-table">
                    <div class="table-body" id="tablaCotizacionesBody">
                        <!-- Las cotizaciones se cargarán aquí -->
                    </div>
                </div>

                <!-- ESTADO VACÍO -->
                <div id="emptyStateCotizaciones" class="empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; color: #9ca3af; width: 100%; min-height: 300px;">
                    <span class="material-symbols-rounded" style="font-size: 3rem; opacity: 0.5;">description</span>
                    <p style="margin-top: 1rem; font-size: 1.1rem;">No hay cotizaciones disponibles</p>
                    <p style="font-size: 0.95rem; margin-top: 0.5rem; color: #d1d5db;">Haz clic en el botón "Agregar" para crear una nueva cotización</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA AGREGAR/EDITAR COTIZACIONES -->
<div id="modalCotizacion" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
            <div>
                <h2 class="modal-title">
                    <span class="material-symbols-rounded">description</span>
                    Agregar Cotización
                </h2>
                <p class="modal-subtitle">Ingresa los datos de la cotización</p>
            </div>
            <button class="btn-cerrar-modal" onclick="cerrarModalCotizacion()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            <form id="formCotizacion" class="form-cotizacion">
                @csrf
                
                <div class="form-grid">
                    <!-- Número -->
                    <div class="form-group">
                        <label class="form-label">Número</label>
                        <input 
                            type="text" 
                            id="numeroCotizacionInput" 
                            class="form-input"
                            placeholder="Ej: COT-001"
                            required>
                        <span class="form-error" id="errorNumeroCotizacion"></span>
                    </div>

                    <!-- Cliente -->
                    <div class="form-group">
                        <label class="form-label">Cliente</label>
                        <input 
                            type="text" 
                            id="clienteCotizacionInput" 
                            class="form-input"
                            placeholder="Nombre del cliente"
                            required>
                        <span class="form-error" id="errorClienteCotizacion"></span>
                    </div>

                    <!-- Fecha -->
                    <div class="form-group">
                        <label class="form-label">Fecha</label>
                        <input 
                            type="date" 
                            id="fechaCotizacionInput" 
                            class="form-input"
                            required>
                        <span class="form-error" id="errorFechaCotizacion"></span>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group" style="grid-column: 1/-1;">
                        <label class="form-label">Descripción</label>
                        <textarea 
                            id="descripcionCotizacionInput" 
                            class="form-input form-textarea"
                            placeholder="Detalles de la cotización..."
                            rows="4"></textarea>
                        <span class="form-error" id="errorDescripcionCotizacion"></span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModalCotizacion()">
                <span class="material-symbols-rounded">close</span>
                Cancelar
            </button>
            <button class="btn-guardar" onclick="guardarCotizacion()">
                <span class="material-symbols-rounded">save</span>
                Guardar
            </button>
        </div>
    </div>
</div>

<script>
    // Simulación de datos de cotizaciones (será reemplazado por API)
    let cotizacionesData = [
        {
            id: 1,
            numero: 'COT-001',
            cliente: 'Cliente A',
            fecha: '2026-01-23',
            descripcion: 'Cotización para bordado de logos'
        },
        {
            id: 2,
            numero: 'COT-002',
            cliente: 'Cliente B',
            fecha: '2026-01-22',
            descripcion: 'Cotización para prendas de trabajo'
        }
    ];

    /**
     * Renderizar tabla de cotizaciones
     */
    function renderizarCotizaciones(datos) {
        const tablaCotizacionesBody = document.getElementById('tablaCotizacionesBody');
        const emptyState = document.getElementById('emptyStateCotizaciones');

        if (!datos || datos.length === 0) {
            tablaCotizacionesBody.innerHTML = '';
            emptyState.style.display = 'flex';
            return;
        }

        emptyState.style.display = 'none';
        tablaCotizacionesBody.innerHTML = datos.map(cotizacion => `
            <div class="table-row" style="display: flex; align-items: center; gap: 12px; padding: 14px 12px; border-bottom: 1px solid #f3f4f6;">
                <div style="flex: 0 0 150px; display: flex; gap: 0.5rem;">
                    <button class="btn-accion btn-editar" title="Editar" onclick="editarCotizacion(${cotizacion.id})">
                        <span class="material-symbols-rounded">edit</span>
                    </button>
                    <button class="btn-accion btn-eliminar" title="Eliminar" onclick="eliminarCotizacion(${cotizacion.id})">
                        <span class="material-symbols-rounded">delete</span>
                    </button>
                </div>
                <div style="flex: 0 0 120px; text-align: center;">
                    <span class="table-cell-badge">${cotizacion.numero}</span>
                </div>
                <div style="flex: 1 1 200px; text-align: left;">
                    <span class="table-cell-text">${cotizacion.cliente}</span>
                </div>
                <div style="flex: 0 0 140px; text-align: center;">
                    <span class="table-cell-text">${formatearFecha(cotizacion.fecha)}</span>
                </div>
            </div>
        `).join('');
    }

    /**
     * Filtrar cotizaciones por búsqueda
     */
    function filtrarCotizaciones() {
        const buscador = document.getElementById('buscadorCotizaciones').value.toLowerCase();
        const cotizaciones = cotizacionesData.filter(cot => 
            cot.numero.toLowerCase().includes(buscador) ||
            cot.cliente.toLowerCase().includes(buscador) ||
            cot.fecha.includes(buscador)
        );
        renderizarCotizaciones(cotizaciones);
    }

    /**
     * Abrir modal de cotización
     */
    function abrirModalCotizacion() {
        const modal = document.getElementById('modalCotizacion');
        document.getElementById('formCotizacion').reset();
        document.getElementById('fechaCotizacionInput').valueAsDate = new Date();
        limpiarErroresCotizacion();
        modal.style.display = 'flex';
    }

    /**
     * Cerrar modal de cotización
     */
    function cerrarModalCotizacion() {
        const modal = document.getElementById('modalCotizacion');
        modal.style.display = 'none';
        document.getElementById('formCotizacion').reset();
        limpiarErroresCotizacion();
    }

    /**
     * Editar cotización
     */
    function editarCotizacion(id) {
        const cotizacion = cotizacionesData.find(c => c.id === id);
        if (cotizacion) {
            document.getElementById('numeroCotizacionInput').value = cotizacion.numero;
            document.getElementById('clienteCotizacionInput').value = cotizacion.cliente;
            document.getElementById('fechaCotizacionInput').value = cotizacion.fecha;
            document.getElementById('descripcionCotizacionInput').value = cotizacion.descripcion;
            abrirModalCotizacion();
        }
    }

    /**
     * Eliminar cotización
     */
    function eliminarCotizacion(id) {
        if (confirm('¿Estás seguro de que deseas eliminar esta cotización?')) {
            cotizacionesData = cotizacionesData.filter(c => c.id !== id);
            renderizarCotizaciones(cotizacionesData);
            if (typeof showToast === 'function') {
                showToast('Cotización eliminada correctamente', 'success');
            }
        }
    }

    /**
     * Guardar cotización (placeholder)
     */
    function guardarCotizacion() {
        const numero = document.getElementById('numeroCotizacionInput').value;
        const cliente = document.getElementById('clienteCotizacionInput').value;
        const fecha = document.getElementById('fechaCotizacionInput').value;
        const descripcion = document.getElementById('descripcionCotizacionInput').value;

        // Validaciones simples
        let errores = {};
        if (!numero) errores.numeroCotizacion = 'El número es requerido';
        if (!cliente) errores.clienteCotizacion = 'El cliente es requerido';
        if (!fecha) errores.fechaCotizacion = 'La fecha es requerida';

        if (Object.keys(errores).length > 0) {
            mostrarErroresCotizacion(errores);
            return;
        }

        const nuevaCotizacion = {
            id: cotizacionesData.length + 1,
            numero: numero,
            cliente: cliente,
            fecha: fecha,
            descripcion: descripcion
        };

        cotizacionesData.unshift(nuevaCotizacion);
        renderizarCotizaciones(cotizacionesData);
        cerrarModalCotizacion();

        if (typeof showToast === 'function') {
            showToast('Cotización guardada correctamente', 'success');
        }
    }

    /**
     * Mostrar errores en el formulario
     */
    function mostrarErroresCotizacion(errores) {
        limpiarErroresCotizacion();
        Object.keys(errores).forEach(campo => {
            const errorElement = document.getElementById(`error${campo.charAt(0).toUpperCase() + campo.slice(1)}`);
            if (errorElement) {
                errorElement.textContent = errores[campo];
                errorElement.style.display = 'block';
            }
        });
    }

    /**
     * Limpiar errores
     */
    function limpiarErroresCotizacion() {
        document.querySelectorAll('.form-error').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
    }

    /**
     * Formatear fecha
     */
    function formatearFecha(fechaStr) {
        const opciones = { year: 'numeric', month: '2-digit', day: '2-digit' };
        return new Date(fechaStr).toLocaleDateString('es-CO', opciones);
    }

    /**
     * Cerrar modal al hacer clic en el overlay
     */
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalCotizacion');
        
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                cerrarModalCotizacion();
            }
        });

        // Cargar datos iniciales
        renderizarCotizaciones(cotizacionesData);

        // Establecer fecha por defecto a hoy
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fechaCotizacionInput').value = today;
    });
</script>
@endsection
