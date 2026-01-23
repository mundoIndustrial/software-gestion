@extends('bordado.layout')

@section('title', 'Bordado - Medidas')
@section('page-title', 'Medidas de Cotizaciones')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/bordado/medidas.css') }}">
@endpush

@section('content')
<div class="medidas-container">
    <!-- Header con búsqueda y botón -->
    <div class="medidas-header">
        <div class="search-box">
            <span class="material-symbols-rounded search-icon">search</span>
            <input 
                type="text" 
                id="buscadorMedidas" 
                class="search-input" 
                placeholder="Buscar por cliente, número, fecha..."
                onkeyup="filtrarMedidas()">
        </div>
        <button class="btn-agregar" onclick="abrirModalMedidas()">
            <span class="material-symbols-rounded">add</span>
            <span>Agregar</span>
        </button>
    </div>

    <!-- Tabla de Medidas -->
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <!-- TABLA HEAD -->
                <div class="table-head">
                    <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                        @php
                            $columns = [
                                ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 140px', 'justify' => 'center'],
                                ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '1 1 200px', 'justify' => 'flex-start'],
                                ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 120px', 'justify' => 'center'],
                                ['key' => 'medidas', 'label' => 'Medidas', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ];
                        @endphp
                        @foreach($columns as $column)
                            <div class="table-header-cell" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                                <span class="header-text">{{ $column['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- TABLA BODY -->
                <div class="modern-table">
                    <div class="table-body" id="tablaMedidasBody">
                        <!-- Las medidas se cargarán aquí -->
                    </div>
                </div>

                <!-- ESTADO VACÍO -->
                <div id="emptyStateMedidas" class="empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; color: #9ca3af; width: 100%; min-height: 300px;">
                    <span class="material-symbols-rounded" style="font-size: 3rem; opacity: 0.5;">straighten</span>
                    <p style="margin-top: 1rem; font-size: 1.1rem;">No hay medidas registradas</p>
                    <p style="font-size: 0.95rem; margin-top: 0.5rem; color: #d1d5db;">Haz clic en el botón "Agregar" para crear una nueva medida</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA AGREGAR/EDITAR MEDIDAS -->
<div id="modalMedidas" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
            <div>
                <h2 class="modal-title">
                    <span class="material-symbols-rounded">straighten</span>
                    Agregar Medida
                </h2>
                <p class="modal-subtitle">Ingresa los datos de la medida</p>
            </div>
            <button class="btn-cerrar-modal" onclick="cerrarModalMedidas()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            <form id="formMedidas" class="form-medidas">
                @csrf
                
                <div class="form-grid">
                    <!-- Fecha -->
                    <div class="form-group">
                        <label class="form-label">Fecha</label>
                        <input 
                            type="date" 
                            id="fechaInput" 
                            class="form-input"
                            required>
                        <span class="form-error" id="errorFecha"></span>
                    </div>

                    <!-- Cliente -->
                    <div class="form-group">
                        <label class="form-label">Cliente</label>
                        <input 
                            type="text" 
                            id="clienteInput" 
                            class="form-input"
                            placeholder="Nombre del cliente"
                            required>
                        <span class="form-error" id="errorCliente"></span>
                    </div>

                    <!-- Número -->
                    <div class="form-group">
                        <label class="form-label">Número</label>
                        <input 
                            type="text" 
                            id="numeroInput" 
                            class="form-input"
                            placeholder="Ej: CZ-001"
                            required>
                        <span class="form-error" id="errorNumero"></span>
                    </div>

                    <!-- Medidas -->
                    <div class="form-group">
                        <label class="form-label">Medidas</label>
                        <input 
                            type="text" 
                            id="medidasInput" 
                            class="form-input"
                            placeholder="Ej: 50x30 cm"
                            required>
                        <span class="form-error" id="errorMedidas"></span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModalMedidas()">
                <span class="material-symbols-rounded">close</span>
                Cancelar
            </button>
            <button class="btn-guardar" onclick="guardarMedida()">
                <span class="material-symbols-rounded">save</span>
                Guardar
            </button>
        </div>
    </div>
</div>

<script>
    // Simulación de datos de medidas (será reemplazado por API)
    let medidasData = [
        {
            id: 1,
            fecha: '2026-01-23',
            cliente: 'Cliente A',
            numero: 'CZ-001',
            medidas: '50x30 cm'
        },
        {
            id: 2,
            fecha: '2026-01-22',
            cliente: 'Cliente B',
            numero: 'CZ-002',
            medidas: '60x40 cm'
        }
    ];

    /**
     * Renderizar tabla de medidas
     */
    function renderizarMedidas(datos) {
        const tablaMedidasBody = document.getElementById('tablaMedidasBody');
        const emptyState = document.getElementById('emptyStateMedidas');

        if (!datos || datos.length === 0) {
            tablaMedidasBody.innerHTML = '';
            emptyState.style.display = 'flex';
            return;
        }

        emptyState.style.display = 'none';
        tablaMedidasBody.innerHTML = datos.map(medida => `
            <div class="table-row" style="display: flex; align-items: center; gap: 12px; padding: 14px 12px; border-bottom: 1px solid #f3f4f6;">
                <div style="flex: 0 0 140px; text-align: center;">
                    <span class="table-cell-text">${formatearFecha(medida.fecha)}</span>
                </div>
                <div style="flex: 1 1 200px; text-align: left;">
                    <span class="table-cell-text">${medida.cliente}</span>
                </div>
                <div style="flex: 0 0 120px; text-align: center;">
                    <span class="table-cell-badge">${medida.numero}</span>
                </div>
                <div style="flex: 0 0 150px; text-align: center;">
                    <span class="table-cell-text">${medida.medidas}</span>
                </div>
            </div>
        `).join('');
    }

    /**
     * Filtrar medidas por búsqueda
     */
    function filtrarMedidas() {
        const buscador = document.getElementById('buscadorMedidas').value.toLowerCase();
        const medidas = medidasData.filter(medida => 
            medida.cliente.toLowerCase().includes(buscador) ||
            medida.numero.toLowerCase().includes(buscador) ||
            medida.fecha.includes(buscador) ||
            medida.medidas.toLowerCase().includes(buscador)
        );
        renderizarMedidas(medidas);
    }

    /**
     * Abrir modal de medidas
     */
    function abrirModalMedidas() {
        const modal = document.getElementById('modalMedidas');
        document.getElementById('formMedidas').reset();
        document.getElementById('fechaInput').valueAsDate = new Date();
        limpiarErrores();
        modal.style.display = 'flex';
    }

    /**
     * Cerrar modal de medidas
     */
    function cerrarModalMedidas() {
        const modal = document.getElementById('modalMedidas');
        modal.style.display = 'none';
        document.getElementById('formMedidas').reset();
        limpiarErrores();
    }

    /**
     * Guardar medida (placeholder)
     */
    function guardarMedida() {
        const fecha = document.getElementById('fechaInput').value;
        const cliente = document.getElementById('clienteInput').value;
        const numero = document.getElementById('numeroInput').value;
        const medidas = document.getElementById('medidasInput').value;

        // Validaciones simples
        let errores = {};
        if (!fecha) errores.fecha = 'La fecha es requerida';
        if (!cliente) errores.cliente = 'El cliente es requerido';
        if (!numero) errores.numero = 'El número es requerido';
        if (!medidas) errores.medidas = 'Las medidas son requeridas';

        if (Object.keys(errores).length > 0) {
            mostrarErrores(errores);
            return;
        }

        // Aquí irá la llamada a la API para guardar
        const nuevaMedida = {
            id: medidasData.length + 1,
            fecha: fecha,
            cliente: cliente,
            numero: numero,
            medidas: medidas
        };

        medidasData.unshift(nuevaMedida);
        renderizarMedidas(medidasData);
        cerrarModalMedidas();

        // Mostrar notificación de éxito (si existe showToast)
        if (typeof showToast === 'function') {
            showToast('Medida guardada correctamente', 'success');
        }
    }

    /**
     * Mostrar errores en el formulario
     */
    function mostrarErrores(errores) {
        limpiarErrores();
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
    function limpiarErrores() {
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
        const modal = document.getElementById('modalMedidas');
        
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                cerrarModalMedidas();
            }
        });

        // Cargar datos iniciales
        renderizarMedidas(medidasData);

        // Establecer fecha por defecto a hoy
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fechaInput').value = today;
    });
</script>
@endsection
