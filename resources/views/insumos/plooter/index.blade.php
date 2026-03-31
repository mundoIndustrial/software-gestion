@extends('layouts.insumos.app')

@section('title', 'Gestión Plooter - Insumos')
@section('page-title', 'Gestión Plooter')

@section('content')
<style>
    .plooter-container {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .plooter-title {
        font-size: 28px;
        font-weight: 600;
        color: #1a1a1a;
        margin-bottom: 30px;
        border-bottom: 3px solid #007bff;
        padding-bottom: 15px;
    }
    
    .plooter-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .plooter-table thead {
        background: #007bff;
        color: white;
        font-weight: 600;
    }
    
    .plooter-table thead th {
        padding: 18px;
        text-align: left;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
    }
    
    .plooter-table tbody tr {
        border-bottom: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .plooter-table tbody tr:hover {
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .plooter-table tbody td {
        padding: 15px 18px;
        font-size: 14px;
        color: #495057;
    }
    
    .plooter-table tbody td strong {
        color: #667eea;
        font-weight: 600;
    }
    
    .text-muted-custom {
        color: #999;
        font-style: italic;
    }
    
    .no-records {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }
    
    .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s ease;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 11px;
    }
    
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    
    .btn-primary:hover:not(:disabled) {
        background-color: #0056b3;
        transform: scale(1.1);
    }
    
    .btn-success {
        background-color: #28a745;
        color: white;
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .btn-warning {
        background-color: #ffc107;
        color: #000;
    }
    
    .btn-warning:hover {
        background-color: #e0a800;
        transform: scale(1.1);
    }
    
    /* Responsividad para móvil */
    @media (max-width: 768px) {
        .plooter-container {
            padding: 15px;
        }
        
        .plooter-table {
            font-size: 12px;
        }
        
        .plooter-table thead th {
            padding: 10px 6px;
            font-size: 11px;
        }
        
        .plooter-table tbody td {
            padding: 10px 6px;
            font-size: 12px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 10px;
        }
    }
    
    @media (max-width: 480px) {
        .plooter-container {
            padding: 10px;
        }
        
        .plooter-table {
            font-size: 11px;
        }
        
        .plooter-table thead th {
            padding: 8px 4px;
            font-size: 10px;
        }
        
        .plooter-table tbody td {
            padding: 8px 4px;
            font-size: 11px;
            word-break: break-word;
        }
        
        .btn-sm {
            padding: 3px 6px;
            font-size: 9px;
        }
    }
</style>

<div class="plooter-container">

    @if($recibosPlooter->isEmpty())
    <div class="no-records">
        <i style="font-size: 48px; color: #ccc;">ℹ️</i>
        <p style="margin-top: 15px; font-size: 16px;">No hay registros de plooter en este momento.</p>
    </div>
    @else

    <table class="plooter-table">
        <thead>
            <tr>
                <th style="width: 10%; text-align: center;">Acciones</th>
                <th>Número Recibo</th>
                <th>Fecha Envío</th>
                <th>Fecha Llegada</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recibosPlooter as $plooter)
            <tr>
                <td style="text-align: center;">
                    @php
                        $userRoles = auth()->user()->roles->pluck('name')->toArray();
                        $esVisualizador = in_array('visualizador_plooter', $userRoles) && count($userRoles) === 1;
                    @endphp
                    
                    @if($plooter->fecha_llegada)
                        <button
                            type="button"
                            class="btn btn-sm btn-warning"
                            data-plooter-action="open-delete-modal"
                            data-recibo-id="{{ $plooter->recibo->id }}"
                            data-plooter-id="{{ $plooter->id }}"
                            title="Eliminar fecha de llegada"
                        >
                            <i class="fas fa-undo"></i>
                        </button>
                    @else
                        <button
                            type="button"
                            class="btn btn-sm btn-primary"
                            data-plooter-action="registrar-fecha-hoy"
                            data-recibo-id="{{ $plooter->recibo->id }}"
                            data-plooter-id="{{ $plooter->id }}"
                            title="Registrar fecha de llegada"
                        >
                            <i class="fas fa-check"></i>
                        </button>
                    @endif
                </td>
                <td><strong>{{ $plooter->recibo->consecutivo_actual }}</strong></td>
                <td>
                    @if($plooter->fecha_envio)
                        ✓ {{ $plooter->fecha_envio->format('d/m/Y h:i A') }}
                    @else
                        <span class="text-muted-custom">No registrada</span>
                    @endif
                </td>
                <td>
                    @if($plooter->fecha_llegada)
                        ✓ {{ $plooter->fecha_llegada->format('d/m/Y h:i A') }}
                    @else
                        <span class="text-muted-custom">No registrada</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @endif
</div>

<!-- Modal personalizado para confirmar eliminación de fecha de llegada -->
<div id="modalEliminarFecha" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background-color: white; border-radius: 8px; padding: 0; width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
        <!-- Header -->
        <div style="background-color: #ffc107; padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; color: #000; font-weight: 600; font-size: 18px;">
                <i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i>Eliminar Fecha de Llegada
            </h5>
            <button type="button" data-plooter-action="close-delete-modal" style="background: none; border: none; font-size: 28px; color: #000; cursor: pointer; padding: 0; margin: 0;">
                &times;
            </button>
        </div>
        
        <!-- Body -->
        <div style="padding: 30px;">
            <p style="font-size: 16px; margin: 0; color: #333; margin-bottom: 15px;">
                ¿Estás seguro de que deseas <strong>eliminar</strong> la fecha de llegada de este recibo?
            </p>
            <p style="font-size: 14px; color: #999; margin: 0; font-style: italic;">
                 Esta acción NO se puede deshacer.
            </p>
        </div>
        
        <!-- Footer -->
        <div style="padding: 15px; border-top: 1px solid #e9ecef; display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" data-plooter-action="close-delete-modal" style="background-color: #6c757d; border: none; color: white; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500;">
                Cancelar
            </button>
            <button type="button" data-plooter-action="confirm-delete-fecha" style="background-color: #dc3545; border: none; color: white; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500;">
                Sí, Eliminar
            </button>
        </div>
    </div>
</div>

<style>
    #modalEliminarFecha[style*="display: flex"] {
        display: flex !important;
    }
</style>

<script>
    const deleteState = {
        reciboId: null,
        plooterId: null,
    };

    function registrarFechaLlegadaHoy(reciboId, plooterId) {
        // Registrar fecha de llegada con la fecha de hoy
        const today = new Date().toISOString().split('T')[0];
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch(`/insumos/plooter/${reciboId}/registrar-fecha-llegada`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ fecha_llegada: today }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar la página
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al registrar fecha de llegada');
        });
    }
    
    function eliminarFechaLlegada(reciboId, plooterId) {
        // Guardar los IDs a eliminar
        deleteState.reciboId = reciboId;
        deleteState.plooterId = plooterId;
        
        // Abrir el modal con JavaScript vanilla
        const modal = document.getElementById('modalEliminarFecha');
        modal.style.display = 'flex';
    }
    
    function cerrarModalEliminar() {
        const modal = document.getElementById('modalEliminarFecha');
        modal.style.display = 'none';
    }
    
    function confirmarEliminarFecha() {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch(`/insumos/plooter/${deleteState.reciboId}/registrar-fecha-llegada`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ fecha_llegada: null }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar modal y recargar
                cerrarModalEliminar();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar fecha de llegada');
        });
    }

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-plooter-action]');
        if (!trigger) return;

        const action = trigger.getAttribute('data-plooter-action');
        if (!action) return;

        if (action === 'registrar-fecha-hoy') {
            event.preventDefault();
            const reciboId = trigger.getAttribute('data-recibo-id');
            const plooterId = trigger.getAttribute('data-plooter-id');
            registrarFechaLlegadaHoy(reciboId, plooterId);
            return;
        }

        if (action === 'open-delete-modal') {
            event.preventDefault();
            const reciboId = trigger.getAttribute('data-recibo-id');
            const plooterId = trigger.getAttribute('data-plooter-id');
            eliminarFechaLlegada(reciboId, plooterId);
            return;
        }

        if (action === 'close-delete-modal') {
            event.preventDefault();
            cerrarModalEliminar();
            return;
        }

        if (action === 'confirm-delete-fecha') {
            event.preventDefault();
            confirmarEliminarFecha();
        }
    });
</script>
@endsection
