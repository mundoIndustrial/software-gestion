@extends('layouts.app')

@section('title', 'Errores del Sistema')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                Errores del Sistema
            </h1>
            <p class="text-muted mt-1">Monitoreo de errores en creación, edición y borradores de pedidos</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.errores.exportar', request()->query()) }}" class="btn btn-sm btn-info">
                <i class="fas fa-download"></i> Descargar CSV
            </a>
            @if($totalReciente > 0)
                <button class="btn btn-sm btn-danger" onclick="limpiarErrores()">
                    <i class="fas fa-trash"></i> Limpiar Antiguos
                </button>
            @endif
        </div>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="text-primary font-weight-bold text-lg">{{ $totalReciente }}</div>
                    <div class="text-muted small">Total Errores (últimas {{ $filtroHoras }}h)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger">
                <div class="card-body">
                    <div class="text-danger font-weight-bold text-lg">{{ $porTipo->get('ERROR_RED', 0) ?? 0 }}</div>
                    <div class="text-muted small">Errores de Red</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="text-warning font-weight-bold text-lg">{{ $porTipo->get('ERROR_IMAGEN', 0) ?? 0 }}</div>
                    <div class="text-muted small">Errores de Imagen</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="text-info font-weight-bold text-lg">{{ $porTipo->get('ERROR_VALIDACION', 0) ?? 0 }}</div>
                    <div class="text-muted small">Errores de Validación</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="tipo" class="form-label">Tipo de Error</label>
                    <select name="tipo" id="tipo" class="form-select">
                        <option value="">Todos</option>
                        @foreach($porTipo as $tipoError => $count)
                            <option value="{{ $tipoError }}" {{ $filtroTipo === $tipoError ? 'selected' : '' }}>
                                {{ $tipoError }} ({{ $count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="origen" class="form-label">Origen</label>
                    <select name="origen" id="origen" class="form-select">
                        <option value="">Todos</option>
                        @foreach($porOrigen as $origenError => $count)
                            <option value="{{ $origenError }}" {{ $filtroOrigen === $origenError ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('-', ' ', $origenError)) }} ({{ $count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="horas" class="form-label">Período</label>
                    <select name="horas" id="horas" class="form-select">
                        <option value="1" {{ $filtroHoras == 1 ? 'selected' : '' }}>Última hora</option>
                        <option value="24" {{ $filtroHoras == 24 ? 'selected' : '' }}>Últimas 24h</option>
                        <option value="72" {{ $filtroHoras == 72 ? 'selected' : '' }}>Últimos 3 días</option>
                        <option value="168" {{ $filtroHoras == 168 ? 'selected' : '' }}>Última semana</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="buscar" class="form-label">Buscar</label>
                    <input type="text" name="buscar" id="buscar" class="form-control"
                           placeholder="Tipo o mensaje" value="{{ $buscar }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Errores -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tipo</th>
                        <th>Mensaje</th>
                        <th>Asesor</th>
                        <th>Pedido</th>
                        <th>Origen</th>
                        <th>Hora</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($errores as $error)
                        <tr>
                            <td>
                                <span class="badge bg-danger">{{ $error->tipo }}</span>
                            </td>
                            <td>
                                <small class="text-muted d-block">
                                    {{ Str::limit($error->mensaje, 50) }}
                                </small>
                            </td>
                            <td>
                                @if($error->usuario)
                                    <small class="font-weight-bold">
                                        <i class="fas fa-user-circle"></i> {{ $error->usuario->name }}
                                    </small>
                                    <br>
                                    <small class="text-muted">{{ $error->usuario->email }}</small>
                                @else
                                    <small class="text-muted">
                                        <i class="fas fa-robot"></i> Sistema
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($error->pedido_id)
                                    <small class="badge bg-primary">
                                        <i class="fas fa-file-invoice"></i> #{{ $error->pedido_id }}
                                    </small>
                                    @if($error->pedido)
                                        <br>
                                        <small class="text-muted d-block">
                                            {{ Str::limit($error->pedido->cliente, 30) }}
                                        </small>
                                    @endif
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td>
                                <small class="badge bg-secondary">{{ $error->origen }}</small>
                            </td>
                            <td>
                                <small class="text-muted" title="{{ $error->ocurrido_en->format('d/m/Y H:i:s') }}">
                                    {{ $error->ocurrido_en->diffForHumans() }}
                                </small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.errores.ver', $error) }}"
                                   class="btn btn-xs btn-info" title="Ver detalles completos">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <p class="text-muted mb-0">
                                    <i class="fas fa-check-circle text-success fa-2x"></i>
                                    <br><small>No hay errores en este período</small>
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($errores->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $errores->links() }}
        </div>
    @endif
</div>

<script>
function limpiarErrores() {
    if (confirm('¿Eliminar errores más antiguos de 72 horas?')) {
        fetch('{{ route('admin.errores.limpiar') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ horas: 72 })
        })
        .then(r => r.json())
        .then(() => location.reload())
        .catch(e => alert('Error: ' + e.message));
    }
}
</script>

<style>
.border-left-primary { border-left: 4px solid #0066cc; }
.border-left-danger { border-left: 4px solid #ef4444; }
.border-left-warning { border-left: 4px solid #f59e0b; }
.border-left-info { border-left: 4px solid #06b6d4; }
</style>
@endsection
