@extends('layouts.app')

@section('content')
@php
    function getEstadoColor($estado) {
        if (!$estado) return 'bg-secondary';
        $e = strtoupper($estado);
        if (str_contains($e, 'INSUMOS')) return 'bg-info text-dark';
        if (str_contains($e, 'CARTERA')) return 'bg-warning text-dark';
        if (str_contains($e, 'SUPERVISOR')) return 'bg-primary';
        if (str_contains($e, 'PENDIENTE') || str_contains($e, 'BORRADOR') || str_contains($e, 'NO INICIADO')) return 'bg-warning text-dark';
        if (str_contains($e, 'EJECUCIÓN') || str_contains($e, 'PROCESO')) return 'bg-primary';
        if (str_contains($e, 'ANULAD') || str_contains($e, 'RECHAZAD') || str_contains($e, 'DEVUELT')) return 'bg-danger';
        if (str_contains($e, 'ENTREGAD') || str_contains($e, 'COMPLETAD') || str_contains($e, 'APROBAD')) return 'bg-success';
        return 'bg-secondary';
    }

    function getDuration($start, $end) {
        if (empty($start) || empty($end)) return null;
        try {
            $s = \Carbon\Carbon::parse($start);
            $e = \Carbon\Carbon::parse($end);
            if ($e->lt($s)) return null;
            
            // Calculo de dias habiles (excluye sabados, domingos, festivos y el mismo dia de inicio)
            // Usamos FestivosColombiaService (cmixin/business-day) para incluir festivos moviles
            // como Jueves y Viernes Santo.
            $festivosRango = \App\Services\FestivosColombiaService::festivosEnRango(
                $s->copy()->startOfDay(),
                $e->copy()->startOfDay()
            );
            $festivosSet = array_fill_keys($festivosRango, true);
            
            $current = $s->copy()->startOfDay()->addDay(); // +1 para NO contar el dia de inicio
            $endDay = $e->copy()->startOfDay();
            
            $diasHabiles = 0;
            while ($current->lte($endDay)) {
                $dayOfWeek = $current->dayOfWeek;
                $ymd = $current->format('Y-m-d');
                
                if ($dayOfWeek !== \Carbon\Carbon::SUNDAY && $dayOfWeek !== \Carbon\Carbon::SATURDAY && !isset($festivosSet[$ymd])) {
                    $diasHabiles++;
                }
                
                $current->addDay();
            }
            
            $businessDays = $diasHabiles;

            // Regla principal: mostrar dias habiles para evitar mezclas inconsistentes
            if ($businessDays > 0) {
                return $businessDays . 'd';
            }

            // Si cae el mismo dia habil o no hay dias habiles entre fechas, mostrar horas/minutos
            $minutes = $s->diffInMinutes($e);
            if ($minutes <= 0) return '0m';

            $hours = intdiv($minutes, 60);
            $mins = $minutes % 60;

            if ($hours > 0 && $mins > 0) return $hours . 'h ' . $mins . 'm';
            if ($hours > 0) return $hours . 'h';
            return $mins . 'm';
        } catch (\Exception $e) {
            return null;
        }
    }
@endphp

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body bg-light">
            @if($pedidos->isEmpty())
                <div class="alert alert-info text-center my-4">
                    No hay pedidos activos actualmente.
                </div>
            @else
                <div class="row">
                    @foreach($pedidos as $pedido)
                        <div class="col-12 mb-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                        <h5 class="mb-0 text-dark font-weight-bold">
                                            Pedido #{{ $pedido->numero_pedido }}
                                            <span class="badge {{ getEstadoColor($pedido->estado) }} ms-2">{{ str_replace('_', ' ', strtoupper($pedido->estado)) }}</span>
                                        </h5>
                                        <div class="text-muted small">
                                            <strong>Cliente:</strong> {{ $pedido->cliente }} | 
                                            <strong>Asesor:</strong> {{ $pedido->asesor_nombre ?? 'N/A' }} | 
                                            <strong>Área:</strong> {{ $pedido->area ?? 'N/A' }}
                                        </div>
                                    </div>
                                    
                                    <div class="timeline-horizontal">
                                        <!-- Paso 1: Creación -->
                                        <div class="timeline-step {{ $pedido->created_at ? 'completed' : '' }}">
                                            <div class="step-indicator">
                                                <span class="material-symbols-rounded">note_add</span>
                                            </div>
                                            <div class="step-label">
                                                <strong>Creado</strong><br>
                                                                                <small class="text-muted">{{ $pedido->created_at ? \Carbon\Carbon::parse($pedido->created_at)->format('d-m-Y h:i A') : '-' }}</small>
                                            </div>
                                        </div>

                                        <!-- Paso 2: Cartera -->
                                        <div class="timeline-step {{ $pedido->aprobado_por_cartera_en ? 'completed' : '' }}">
                                            <div class="step-indicator">
                                                <span class="material-symbols-rounded">payments</span>
                                            </div>
                                            <div class="step-label">
                                                <strong>Aprob. Cartera</strong><br>
                                                                                <small class="text-muted">{{ $pedido->aprobado_por_cartera_en ? \Carbon\Carbon::parse($pedido->aprobado_por_cartera_en)->format('d-m-Y h:i A') : 'Pendiente' }}</small>
                                                @if($pedido->aprobado_por_cartera_en && $pedido->created_at)
                                                    <div class="mt-1" style="font-size: 0.75rem;">
                                                        <span class="badge bg-light text-secondary border" title="Tiempo transcurrido desde Creación">
                                                            <span class="material-symbols-rounded align-middle" style="font-size: 11px;">schedule</span>
                                                            {{ getDuration($pedido->created_at, $pedido->aprobado_por_cartera_en) }}
                                                        </span>
                                                    </div>
                                                @endif
                                                @if($pedido->cartera_nombre)
                                                    <div class="mt-1 text-truncate px-1" style="font-size: 0.7rem; max-width: 120px; margin: 0 auto;">
                                                        <span class="text-primary" title="Aprobado por: {{ $pedido->cartera_nombre }}"><span class="material-symbols-rounded align-middle" style="font-size: 11px;">person</span> {{ explode(' ', trim($pedido->cartera_nombre))[0] }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Paso 3: Supervisor -->
                                        <div class="timeline-step {{ $pedido->aprobado_por_supervisor_en ? 'completed' : '' }}">
                                            <div class="step-indicator">
                                                <span class="material-symbols-rounded">engineering</span>
                                            </div>
                                            <div class="step-label">
                                                <strong>Aprob. Supervisor</strong><br>
                                                                                <small class="text-muted">{{ $pedido->aprobado_por_supervisor_en ? \Carbon\Carbon::parse($pedido->aprobado_por_supervisor_en)->format('d-m-Y h:i A') : 'Pendiente' }}</small>
                                                @if($pedido->aprobado_por_supervisor_en && $pedido->aprobado_por_cartera_en)
                                                    <div class="mt-1" style="font-size: 0.75rem;">
                                                        <span class="badge bg-light text-secondary border" title="Tiempo transcurrido desde Cartera">
                                                            <span class="material-symbols-rounded align-middle" style="font-size: 11px;">schedule</span>
                                                            {{ getDuration($pedido->aprobado_por_cartera_en, $pedido->aprobado_por_supervisor_en) }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>



                                        <!-- Paso 5: Entrega Estimada -->
                                        <div class="timeline-step {{ $pedido->fecha_estimada_de_entrega && \Carbon\Carbon::parse($pedido->fecha_estimada_de_entrega)->isPast() ? 'delayed' : 'future' }}">
                                            <div class="step-indicator">
                                                <span class="material-symbols-rounded">local_shipping</span>
                                            </div>
                                            <div class="step-label">
                                                <strong>Est. Entrega</strong><br>
                                                <small class="{{ $pedido->fecha_estimada_de_entrega && \Carbon\Carbon::parse($pedido->fecha_estimada_de_entrega)->isPast() ? 'text-danger fw-bold' : 'text-muted' }}">
                                                                                    {{ $pedido->fecha_estimada_de_entrega ? \Carbon\Carbon::parse($pedido->fecha_estimada_de_entrega)->format('d-m-Y') : 'No definida' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Accordion de Recibos -->
                                    @if(isset($recibos) && isset($recibos[$pedido->id]) && $recibos[$pedido->id]->count() > 0)
                                    <div class="accordion mt-4" id="accordion-pedido-{{ $pedido->id }}">
                                        <div class="accordion-item border-0 shadow-sm rounded">
                                            <h2 class="accordion-header" id="heading-{{ $pedido->id }}">
                                                <button class="accordion-button collapsed py-3 rounded text-primary fw-bold bg-white" type="button" onclick="toggleAccordion('collapse-{{ $pedido->id }}', this)" aria-expanded="false" aria-controls="collapse-{{ $pedido->id }}" style="width: 100%; text-align: left; border: 1px solid #dee2e6;">
                                                    <span class="material-symbols-rounded me-2 align-middle" style="font-size: 20px;">receipt_long</span>
                                                    <span class="align-middle">Ver Recibos del Pedido ({{ $recibos[$pedido->id]->count() }})</span>
                                                </button>
                                            </h2>
                                            <div id="collapse-{{ $pedido->id }}" class="accordion-collapse" style="display: none;" aria-labelledby="heading-{{ $pedido->id }}">
                                                <div class="accordion-body bg-light rounded-bottom p-3 border border-top-0 border-light">
                                                    @foreach($recibos[$pedido->id] as $recibo)
                                                        <div class="card mb-3 border-0 shadow-sm">
                                                            <div class="card-body p-3">
                                                                <h6 class="mb-3 text-primary border-bottom pb-2">
                                                                    {{ $recibo->tipo_recibo }} #{{ $recibo->consecutivo_actual }}
                                                                    @if(isset($recibo->is_parcial) && $recibo->is_parcial)
                                                                        <span class="badge bg-secondary ms-1">PARCIAL</span>
                                                                    @endif
                                                                    <span class="badge {{ getEstadoColor($recibo->estado) }} ms-2">{{ str_replace('_', ' ', strtoupper($recibo->estado ?? 'Pendiente')) }}</span>
                                                                </h6>
                                                                
                                                                <div class="timeline-horizontal timeline-sm" style="overflow-x: auto; padding-bottom: 15px; flex-wrap: nowrap;">
                                                                    <!-- Insumos Step -->
                                                                    @php
                                                                        $firstProc = isset($recibo->subprocesos) && $recibo->subprocesos->count() > 0 ? $recibo->subprocesos->first() : null;
                                                                        $firstDate = $firstProc ? ($firstProc->fecha_inicio ?? $firstProc->created_at) : null;
                                                                    @endphp
                                                                    <div class="timeline-step {{ $recibo->aprobado_insumos_en ? 'completed' : 'active' }}" style="min-width: 140px; flex: 1;">
                                                                        <div class="step-indicator">
                                                                            <span class="material-symbols-rounded" style="font-size: 14px;">{{ $recibo->aprobado_insumos_en ? 'check_circle' : 'pending_actions' }}</span>
                                                                        </div>
                                                                        <div class="step-label">
                                                                            <strong title="Insumos">Insumos</strong><br>
                                                                            <small class="text-muted" title="Fecha Inicio">{{ $recibo->created_at ? \Carbon\Carbon::parse($recibo->created_at)->format('d-m-Y h:i A') : 'Sin fecha' }}</small>

                                                                            @if($firstDate)
                                                                                @php 
                                                                                    $durToNext = getDuration($recibo->created_at, $firstDate); 
                                                                                @endphp
                                                                                @if($durToNext)
                                                                                    <div class="mt-1" style="font-size: 0.7rem;">
                                                                                        <span class="badge bg-light text-secondary border" title="Tiempo hasta el siguiente proceso">
                                                                                            <span class="material-symbols-rounded align-middle" style="font-size: 10px;">schedule</span>
                                                                                            {{ $durToNext }}
                                                                                        </span>
                                                                                    </div>
                                                                                @else
                                                                                    <!-- DEBUG: durToNext is false/null. created_at: {{ $recibo->created_at }}, firstDate: {{ $firstDate }} -->
                                                                                @endif
                                                                            @else
                                                                                <!-- DEBUG: firstDate is null -->
                                                                            @endif
                                                                        </div>
                                                                    </div>

                                                                    @if(isset($recibo->subprocesos) && $recibo->subprocesos->count() > 0)
                                                                        @if(in_array($recibo->tipo_recibo, ['COSTURA', 'REFLECTIVO']))
                                                                            @foreach($recibo->subprocesos as $index => $proc)
                                                                                <div class="timeline-step {{ str_contains(strtolower($proc->estado_proceso), 'completado') ? 'completed' : 'active' }}" style="min-width: 140px; flex: 1;">
                                                                                    <div class="step-indicator">
                                                                                        <span class="material-symbols-rounded" style="font-size: 14px;">{{ str_contains(strtolower($proc->estado_proceso), 'completado') ? 'check_circle' : 'pending_actions' }}</span>
                                                                                    </div>
                                                                                    <div class="step-label">
                                                                                        <strong title="{{ $proc->proceso }}">{{ \Illuminate\Support\Str::limit($proc->proceso, 15) }}</strong><br>
                                                                                        @if($proc->fecha_fin)
                                                                                            <small class="text-muted" title="Fecha Fin">{{ \Carbon\Carbon::parse($proc->fecha_fin)->format('d-m-Y h:i A') }}</small>
                                                                                        @else
                                                                                            <small class="text-muted" title="Fecha Inicio">{{ $proc->fecha_inicio ? \Carbon\Carbon::parse($proc->fecha_inicio)->format('d-m-Y h:i A') : 'Sin fecha' }}</small>
                                                                                        @endif

                                                                                        @if($proc->fecha_inicio && $proc->fecha_fin)
                                                                                            @php $durProcess = getDuration($proc->fecha_inicio, $proc->fecha_fin); @endphp
                                                                                            @if($durProcess)
                                                                                                <div class="mt-1" style="font-size: 0.7rem;">
                                                                                                    <span class="badge bg-light text-secondary border" title="Duración del proceso">
                                                                                                        <span class="material-symbols-rounded align-middle" style="font-size: 10px;">hourglass_bottom</span>
                                                                                                        {{ $durProcess }}
                                                                                                    </span>
                                                                                                </div>
                                                                                            @endif
                                                                                        @endif

                                                                                        @php
                                                                                            $nextProc = $recibo->subprocesos->get($index + 1);
                                                                                            $nextDate = $nextProc ? ($nextProc->fecha_inicio ?? $nextProc->created_at) : null;
                                                                                            // "Tiempo hasta el siguiente proceso" se calcula de inicio a inicio
                                                                                            // para que refleje el lapso entre etapas en la linea de tiempo.
                                                                                            $currentStartDate = $proc->fecha_inicio ?? $proc->created_at ?? null;
                                                                                        @endphp

                                                                                        @if($currentStartDate && $nextDate)
                                                                                            @php $durToNext = getDuration($currentStartDate, $nextDate); @endphp
                                                                                            @if($durToNext)
                                                                                                <div class="mt-1" style="font-size: 0.7rem;">
                                                                                                    <span class="badge bg-light text-secondary border" title="Tiempo hasta el siguiente proceso">
                                                                                                        <span class="material-symbols-rounded align-middle" style="font-size: 10px;">schedule</span>
                                                                                                        {{ $durToNext }}
                                                                                                    </span>
                                                                                                </div>
                                                                                            @endif
                                                                                        @endif

                                                                                        @if(!empty($proc->encargado))
                                                                                            <div class="mt-1 text-truncate" style="font-size: 0.7rem;" title="Encargado: {{ $proc->encargado }}">
                                                                                                <span class="text-primary"><span class="material-symbols-rounded align-middle" style="font-size: 10px;">person</span> {{ $proc->encargado }}</span>
                                                                                            </div>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        @else
                                                                            @foreach($recibo->subprocesos as $index => $proc)
                                                                                <div class="timeline-step completed" style="min-width: 140px; flex: 1;">
                                                                                    <div class="step-indicator">
                                                                                        <span class="material-symbols-rounded" style="font-size: 14px;">history</span>
                                                                                    </div>
                                                                                    <div class="step-label">
                                                                                        <strong title="{{ str_replace('_', ' ', $proc->area) }}">{{ \Illuminate\Support\Str::limit(str_replace('_', ' ', $proc->area), 15) }}</strong><br>
                                                                                        <small class="text-muted">{{ $proc->created_at ? \Carbon\Carbon::parse($proc->created_at)->format('d-m-Y h:i A') : '-' }}</small>

                                                                                        @php
                                                                                            $nextProc = $recibo->subprocesos->get($index + 1);
                                                                                            $nextDate = $nextProc ? $nextProc->created_at : null;
                                                                                        @endphp

                                                                                        @if($proc->created_at && $nextDate)
                                                                                            @php $durToNext = getDuration($proc->created_at, $nextDate); @endphp
                                                                                            @if($durToNext)
                                                                                                <div class="mt-1" style="font-size: 0.7rem;">
                                                                                                    <span class="badge bg-light text-secondary border" title="Tiempo hasta el siguiente proceso">
                                                                                                        <span class="material-symbols-rounded align-middle" style="font-size: 10px;">schedule</span>
                                                                                                        {{ $durToNext }}
                                                                                                    </span>
                                                                                                </div>
                                                                                            @endif
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    {{ $pedidos->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .timeline-horizontal {
        display: flex;
        justify-content: space-between;
        position: relative;
        padding-top: 10px;
        margin-top: 15px;
    }
    
    .timeline-horizontal::before {
        content: '';
        position: absolute;
        top: 25px;
        left: 30px;
        right: 30px;
        height: 4px;
        background-color: #e9ecef;
        z-index: 1;
    }

    .timeline-step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }

    .step-indicator {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #f8f9fa;
        border: 3px solid #dee2e6;
        color: #adb5bd;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px auto;
        transition: all 0.3s ease;
    }

    .step-indicator .material-symbols-rounded {
        font-size: 18px;
    }

    .timeline-step.completed .step-indicator {
        background-color: #198754;
        border-color: #198754;
        color: white;
    }
    
    .timeline-step.completed ~ .timeline-step::after {
        /* Se podría usar para colorear la línea pero es complejo sin JS o selectores específicos.
           Dejaremos la línea gris y los círculos de colores. */
    }

    .timeline-step.active .step-indicator {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.2);
    }
    
    .timeline-step.delayed .step-indicator {
        background-color: #dc3545;
        border-color: #dc3545;
        color: white;
    }
    
    .timeline-step.future .step-indicator {
        background-color: #fff;
        border-color: #0dcaf0;
        color: #0dcaf0;
    }

    .step-label {
        font-size: 0.85rem;
    }
    
    .step-label strong {
        display: block;
        color: #495057;
    }
    
    /* Timeline Pequeña (Recibos) */
    .timeline-horizontal.timeline-sm {
        margin-top: 5px;
        padding-top: 5px;
    }
    
    .timeline-horizontal.timeline-sm::before {
        top: 15px;
        height: 3px;
    }

    .timeline-horizontal.timeline-sm .step-indicator {
        width: 28px;
        height: 28px;
        border-width: 2px;
        margin-bottom: 5px;
    }
    
    .timeline-horizontal.timeline-sm .step-label {
        font-size: 0.75rem;
    }

    /* Responsividad */
    @media (max-width: 768px) {
        .timeline-horizontal {
            flex-direction: column;
            align-items: flex-start;
            padding-left: 20px;
        }
        
        .timeline-horizontal::before {
            top: 10px;
            bottom: 10px;
            left: 36px;
            right: auto;
            width: 4px;
            height: auto;
        }

        .timeline-horizontal.timeline-sm::before {
            left: 33px;
            width: 3px;
        }
        
        .timeline-step {
            display: flex;
            align-items: center;
            width: 100%;
            text-align: left;
            margin-bottom: 20px;
        }
        
        .step-indicator {
            margin: 0 15px 0 0;
        }
    }
</style>

<script>
    function toggleAccordion(targetId, buttonElement) {
        var targetElement = document.getElementById(targetId);
        if (!targetElement) return;

        var isHidden = targetElement.style.display === 'none';
        
        if (isHidden) {
            // Abrir
            targetElement.style.display = 'block';
            buttonElement.classList.remove('collapsed');
            buttonElement.setAttribute('aria-expanded', 'true');
        } else {
            // Cerrar
            targetElement.style.display = 'none';
            buttonElement.classList.add('collapsed');
            buttonElement.setAttribute('aria-expanded', 'false');
        }
    }
</script>
@endsection
