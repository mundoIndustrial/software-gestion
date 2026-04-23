@extends('layouts.app')

@section('title', 'Detalle del Error')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="{{ route('admin.errores.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Información Principal -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">{{ $error->tipo }}</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-muted mb-2">Mensaje</h6>
                    <p class="lead mb-4">{{ $error->mensaje }}</p>

                    <h6 class="text-muted mb-2">Detalles Técnicos</h6>
                    @if($error->detalles)
                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($error->detalles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                    @else
                        <p class="text-muted">Sin detalles adicionales</p>
                    @endif

                    <h6 class="text-muted mb-2">Página</h6>
                    <p class="text-break">
                        <code>{{ $error->url_pagina }}</code>
                    </p>

                    <h6 class="text-muted mb-2">Navegador</h6>
                    <p class="text-break small">
                        {{ $error->navegador }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Información Complementaria -->
        <div class="col-md-4">
            <!-- Tarjeta de Información del Error -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Información del Error</h6>
                </div>
                <div class="card-body">
                    <dl class="row small">
                        <dt class="col-sm-6">Tipo:</dt>
                        <dd class="col-sm-6">
                            <span class="badge bg-danger">{{ $error->tipo }}</span>
                        </dd>

                        <dt class="col-sm-6">Origen:</dt>
                        <dd class="col-sm-6">
                            <span class="badge bg-secondary">{{ $error->origen }}</span>
                        </dd>

                        <dt class="col-sm-6">Ocurrido:</dt>
                        <dd class="col-sm-6">
                            <small>{{ $error->ocurrido_en->format('d/m/Y H:i:s') }}</small>
                            <br>
                            <small class="text-muted">
                                (hace {{ $error->ocurrido_en->diffForHumans() }})
                            </small>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Tarjeta de Asesor -->
            <div class="card mb-3 border-left border-primary">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-user-tie text-primary"></i> Asesor (Usuario)
                    </h6>
                </div>
                <div class="card-body">
                    @if($error->usuario)
                        <div class="mb-3">
                            <div class="font-weight-bold text-primary">{{ $error->usuario->name }}</div>
                            <small class="text-muted d-block">{{ $error->usuario->email }}</small>
                            @if($error->usuario->rol)
                                <small class="badge bg-info mt-2">{{ $error->usuario->rol }}</small>
                            @endif
                        </div>
                        <hr>
                        <small class="text-muted d-block">
                            <strong>ID Usuario:</strong> {{ $error->usuario_id }}
                        </small>
                    @else
                        <div class="text-muted">
                            <i class="fas fa-robot"></i> Sistema (sin usuario identificado)
                        </div>
                    @endif
                </div>
            </div>

            <!-- Tarjeta de Pedido -->
            <div class="card border-left border-success">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-file-invoice text-success"></i> Pedido Relacionado
                    </h6>
                </div>
                <div class="card-body">
                    @if($error->pedido_id && $error->pedido)
                        <div class="mb-3">
                            <div class="font-weight-bold text-success">
                                Pedido #{{ $error->pedido_id }}
                            </div>
                            <small class="text-muted d-block">
                                <strong>Cliente:</strong> {{ $error->pedido->cliente ?? 'N/A' }}
                            </small>
                            <small class="text-muted d-block mt-2">
                                <strong>Estado:</strong>
                                <span class="badge bg-info">{{ $error->pedido->estado ?? 'desconocido' }}</span>
                            </small>
                        </div>
                        <hr>
                        <a href="{{ route('admin.pedidos.edit', $error->pedido) }}"
                           class="btn btn-sm btn-success w-100">
                            <i class="fas fa-eye"></i> Ver Pedido
                        </a>
                    @elseif($error->pedido_id)
                        <div class="text-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Pedido #{{ $error->pedido_id }}</strong>
                            <br>
                            <small class="text-muted">(Pedido no encontrado o eliminado)</small>
                        </div>
                    @else
                        <div class="text-muted">
                            <i class="fas fa-minus-circle"></i>
                            No hay pedido asociado
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
