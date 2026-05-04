@extends('operario.layout')

@section('title', 'Detalle de Entrega - Talleres')

@section('page-title')
    <span style="display: inline-flex; align-items: center; gap: 0.6rem;">
        <span class="material-symbols-rounded">construction</span>
        <span>ENTREGAS TALLERES</span>
    </span>
@endsection


@push('styles')
    <link rel="stylesheet" href="{{ asset('css/entregas-talleres.css') }}?v={{ time() }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="entregas-container">
        <div class="results-header" style="justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="javascript:history.back()" class="back-btn">
                    <span class="material-symbols-rounded">arrow_back</span>
                </a>
            </div>
            <a href="{{ route('entregas-talleres.index') }}" class="back-btn">
                <span class="material-symbols-rounded">close</span>
            </a>
        </div>

    <div class="results-content" style="padding-top: 20px;" 
         id="recibo-data" 
         data-id="{{ $recibo->id }}" 
         data-parcial="{{ $esParcial ? '1' : '0' }}">
        <div class="detail-card">
            <div class="recibo-info">
                <div class="recibo-id">Recibo #{{ $numeroRecibo }}</div>
                <div class="recibo-name" style="font-size: 20px; margin: 8px 0;">{{ $recibo->prenda->nombre_prenda }}</div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="recibo-user">
                        <span class="material-symbols-rounded" style="font-size: 16px;">person</span>
                        {{ $encargado ?? 'Sin asignar' }}
                    </div>
                    <a href="javascript:void(0)" onclick="openHistorial()" style="color: var(--accent-blue); font-weight: 700; font-size: 13px; text-decoration: underline;">Historial</a>
                </div>
            </div>
        </div>

        <div class="section-label" style="padding: 0 16px;">Tallas y Cantidades</div>

        <div class="tallas-section">
            @foreach($tallas as $talla)
                @php
                    $entregado = $entregasPorTalla[$talla->talla] ?? 0;
                    $disponible = $talla->cantidad - $entregado;
                    $isCompleted = $disponible <= 0;
                @endphp
                <div class="talla-item {{ $isCompleted ? 'completed' : '' }}" id="talla-item-{{ $talla->talla }}">
                    <div class="talla-badge">{{ $talla->talla }}</div>
                    <div class="talla-info">
                        <div class="talla-counts">
                            <span class="delivered" id="delivered-{{ $talla->talla }}">{{ $entregado }}</span>
                            <span class="total"> / {{ $talla->cantidad }}</span>
                        </div>
                        <div class="talla-status" id="status-container-{{ $talla->talla }}">
                            @if($isCompleted)
                                COMPLETADO
                            @else
                                <span id="disponibles-{{ $talla->talla }}">{{ $disponible }}</span> DISPONIBLES
                            @endif
                        </div>
                    </div>
                    @if($isCompleted)
                        <div class="btn-completed">
                            <span class="material-symbols-rounded">check</span>
                        </div>
                    @else
                        <button class="btn-add" onclick="promptDelivery('{{ $talla->talla }}', {{ $disponible }})">
                            <span class="material-symbols-rounded">add</span>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Modal Historial -->
<div class="modal-overlay" id="modal-overlay" onclick="closeHistorial()"></div>
<div class="historial-modal" id="historial-modal">
    <div class="modal-header">
        <div class="close-btn" onclick="closeHistorial()">
            <span class="material-symbols-rounded">close</span>
        </div>
        <h2 style="font-weight: 800; font-size: 22px;">Historial de Entregas</h2>
    </div>
    <div id="historial-items-container">
        <!-- Items loaded via JS -->
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const reciboId = {{ $recibo->id }};

    function promptDelivery(talla, disponible) {
        if (disponible <= 0) {
            Swal.fire({
                title: 'No hay más unidades',
                text: 'Ya se han entregado todas las unidades de esta talla.',
                icon: 'warning',
                confirmButtonColor: '#2450ef'
            });
            return;
        }

        Swal.fire({
            title: `Entrega Talla ${talla}`,
            text: `¿Cuántas unidades vas a entregar? (Máximo ${disponible})`,
            input: 'number',
            inputAttributes: {
                min: 1,
                max: disponible,
                step: 1
            },
            inputValue: 1,
            showCancelButton: true,
            confirmButtonText: 'Registrar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2450ef',
            preConfirm: (value) => {
                if (!value || value < 1 || value > disponible) {
                    Swal.showValidationMessage(`Ingresa una cantidad válida entre 1 y ${disponible}`);
                }
                return value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                registrarEntrega(talla, parseInt(result.value));
            }
        });
    }

    async function registrarEntrega(talla, cantidad) {
        const container = document.getElementById('recibo-data');
        const reciboId = container.dataset.id;
        const esParcial = container.dataset.parcial;

        try {
            const response = await fetch('{{ route('entregas-talleres.registrar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    recibo_id: reciboId,
                    es_parcial: esParcial,
                    talla: talla,
                    cantidad: cantidad
                })
            });

            const data = await response.json();

            if (data.success) {
                if (data.completado) {
                    Swal.fire({
                        title: '¡Recibo Completado!',
                        text: 'Todas las tallas han sido entregadas correctamente.',
                        icon: 'success',
                        confirmButtonColor: '#2450ef'
                    });
                } else {
                    Swal.fire({
                        title: '¡Registrado!',
                        text: 'La entrega se guardó correctamente.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }

                // Actualizar UI localmente
                const deliveredEl = document.getElementById(`delivered-${talla}`);
                const itemEl = document.getElementById(`talla-item-${talla}`);
                const statusContainer = document.getElementById(`status-container-${talla}`);

                if (deliveredEl && itemEl && statusContainer) {
                    const currentDelivered = parseInt(deliveredEl.innerText) || 0;
                    const totalText = deliveredEl.nextElementSibling.innerText;
                    const total = parseInt(totalText.replace(/[^\d]/g, '')) || 0;
                    
                    const newDelivered = currentDelivered + cantidad;
                    deliveredEl.innerText = newDelivered;
                    
                    if (newDelivered >= total) {
                        itemEl.classList.add('completed');
                        statusContainer.innerHTML = 'COMPLETADO';
                        
                        // Reemplazar botón por check icon
                        const btnAdd = itemEl.querySelector('.btn-add');
                        if (btnAdd) {
                            btnAdd.outerHTML = `
                                <div class="btn-completed">
                                    <span class="material-symbols-rounded">check</span>
                                </div>
                            `;
                        }
                    } else {
                        const disponiblesEl = document.getElementById(`disponibles-${talla}`);
                        if (disponiblesEl) {
                            disponiblesEl.innerText = total - newDelivered;
                        } else {
                            statusContainer.innerHTML = `<span id="disponibles-${talla}">${total - newDelivered}</span> DISPONIBLES`;
                        }
                    }
                }

            } else {
                Swal.fire('Error', data.message || 'No se pudo registrar la entrega', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Ocurrió un error en la comunicación con el servidor', 'error');
        }
    }

    async function loadHistorial() {
        const container = document.getElementById('historial-items-container');
        const dataContainer = document.getElementById('recibo-data');
        const reciboId = dataContainer.dataset.id;
        const esParcial = dataContainer.dataset.parcial;

        container.innerHTML = '<div style="text-align:center; padding: 20px;">Cargando...</div>';
        
        try {
            const response = await fetch(`/entregas-talleres/historial/${reciboId}?es_parcial=${esParcial}`);
            const items = await response.json();
            
            if (items.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding: 20px; color: #666;">No hay entregas registradas</div>';
                return;
            }

            container.innerHTML = '';
            items.forEach(item => {
                const html = `
                    <div class="historial-item">
                        <div class="historial-info">
                            <div class="historial-title">${item.cantidad_total} unidades</div>
                            <div class="historial-date">${item.fecha} • <b>${item.encargado}</b></div>
                        </div>
                        <div class="check-icon">
                            <span class="material-symbols-rounded" style="font-size: 16px;">check</span>
                        </div>
                    </div>
                `;
                container.innerHTML += html;
            });
        } catch (error) {
            container.innerHTML = '<div style="text-align:center; color:red;">Error al cargar historial</div>';
        }
    }

    function openHistorial() {
        document.getElementById('modal-overlay').style.display = 'block';
        document.getElementById('historial-modal').classList.add('show');
    }

    function closeHistorial() {
        document.getElementById('modal-overlay').style.display = 'none';
        document.getElementById('historial-modal').classList.remove('show');
    }
</script>
@endpush
