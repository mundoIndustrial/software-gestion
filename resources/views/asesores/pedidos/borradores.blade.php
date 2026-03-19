@extends('layouts.asesores')

@section('title', 'Borradores de Pedidos')
@section('page-title', 'Borradores de Pedidos')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/asesores/pedidos/index.css') }}">
    <style>
        .borrador-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #fb923c;
            border-radius: 6px;
            padding: 0.65rem 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .borrador-item:hover {
            box-shadow: 0 2px 8px rgba(251, 146, 60, 0.12);
            background: #fffbf7;
        }

        .borrador-info {
            flex: 1;
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }

        .borrador-cliente {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1f2937;
            word-break: break-word;
            min-width: 160px;
        }

        .borrador-fecha {
            font-size: 0.8rem;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
        }

        .borrador-meta {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .borrador-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            color: #6b7280;
        }

        .borrador-meta-badge {
            background: #f3f4f6;
            padding: 0.15rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .borrador-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
            align-items: center;
        }

        .btn-action {
            padding: 0.35rem 0.75rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-editar {
            background: #0066cc;
            color: white;
        }

        .btn-editar:hover {
            background: #0052a3;
            box-shadow: 0 2px 8px rgba(0, 102, 204, 0.3);
        }

        .btn-eliminar {
            background: #ef4444;
            color: white;
        }

        .btn-eliminar:hover {
            background: #dc2626;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            color: #9ca3af;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .empty-state-text {
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .btn-crear-nuevo {
            background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-crear-nuevo:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(251, 146, 60, 0.3);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #6b7280;
            font-size: 0.95rem;
            margin: 0;
        }

        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .borrador-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .borrador-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endsection

@section('content')
<div class="page-header">
    <h1>
        <span class="material-symbols-rounded">draft</span>
        Borradores de Pedidos
    </h1>
</div>

@if($borradores->isEmpty())
    <div class="empty-state">
        <span class="material-symbols-rounded empty-state-icon">draft</span>
        <p class="empty-state-text">No tienes borradores de pedidos</p>
        <a href="{{ route('asesores.pedidos-editable.crear-nuevo') }}" class="btn-crear-nuevo">
            <span class="material-symbols-rounded">add_box</span>
            Crear Nuevo Pedido
        </a>
    </div>
@else
    <div class="borradores-list">
        @foreach($borradores as $borrador)
            <div class="borrador-item">
                <div class="borrador-info">
                    <div class="borrador-cliente">
                        {{ $borrador->cliente ?? 'Sin cliente' }}
                    </div>
                    <div class="borrador-fecha">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">schedule</span>
                        Creado el {{ $borrador->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div class="borrador-meta">
                        @php
                            $cantidadPrendas = $borrador->prendas()->count();
                            $cantidadEpps = $borrador->epps()->count();
                        @endphp
                        @if($cantidadPrendas > 0)
                            <div class="borrador-meta-item">
                                <span class="material-symbols-rounded" style="font-size: 0.95rem;">checkroom</span>
                                <span class="borrador-meta-badge">{{ $cantidadPrendas }} {{ $cantidadPrendas === 1 ? 'Prenda' : 'Prendas' }}</span>
                            </div>
                        @endif
                        @if($cantidadEpps > 0)
                            <div class="borrador-meta-item">
                                <span class="material-symbols-rounded" style="font-size: 0.95rem;">shield</span>
                                <span class="borrador-meta-badge">{{ $cantidadEpps }} {{ $cantidadEpps === 1 ? 'EPP' : 'EPPs' }}</span>
                            </div>
                        @endif
                        <div class="borrador-meta-item">
                            <span class="material-symbols-rounded" style="font-size: 0.95rem;">person</span>
                            <span class="borrador-meta-badge">{{ $borrador->asesor->name ?? 'Sin asesora' }}</span>
                        </div>
                    </div>
                </div>
                <div class="borrador-actions">
                    <button class="btn-action btn-editar" onclick="editarBorrador({{ $borrador->id }})">
                        <span class="material-symbols-rounded">edit</span>
                        Continuar
                    </button>
                    <button class="btn-action btn-eliminar" onclick="eliminarBorrador({{ $borrador->id }}, '{{ $borrador->cliente }}')">
                        <span class="material-symbols-rounded">delete</span>
                        Eliminar
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if($borradores->hasPages())
        <div class="pagination-wrapper">
            {{ $borradores->links() }}
        </div>
    @endif
@endif

<script>
    function editarBorrador(pedidoId) {
        // Redirigir a la página de edición del borrador
        window.location.href = `{{ route('asesores.pedidos-editable.crear-nuevo') }}?edit=${pedidoId}`;
    }

    function eliminarBorrador(pedidoId, clienteNombre) {
        Swal.fire({
            icon: 'warning',
            title: '¿Eliminar Borrador?',
            html: `
                <div style="text-align: left;">
                    <p>¿Estás seguro de que deseas eliminar el borrador de <strong>${clienteNombre}</strong>?</p>
                    <p style="font-size: 0.875rem; color: #ef4444; margin-top: 1rem;">
                        <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 1rem;">warning</span>
                        Esta acción no se puede deshacer.
                    </p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear un formulario temporal para enviar la solicitud DELETE
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ route('asesores.pedidos.borradores.destroy', ['id' => 'PEDIDO_ID']) }}`.replace('PEDIDO_ID', pedidoId);

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild(csrfInput);
                form.appendChild(methodInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endsection
