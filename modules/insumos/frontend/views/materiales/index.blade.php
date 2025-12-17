@extends('layouts.insumos.app')

@section('page-title', 'Materiales Insumos')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12 text-end">
            <a href="{{ route('insumos.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Lista de Materiales</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Insumo</th>
                        <th>Cantidad</th>
                        <th>Estado</th>
                        <th>Área</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($materiales as $material)
                        <tr>
                            <td>{{ $material->numero_pedido }}</td>
                            <td>{{ $material->nombre_insumo }}</td>
                            <td>{{ $material->cantidad }}</td>
                            <td>
                                <span class="badge bg-{{ $material->estado === 'No iniciado' ? 'secondary' : ($material->estado === 'En Ejecución' ? 'primary' : 'danger') }}">
                                    {{ $material->estado }}
                                </span>
                            </td>
                            <td>{{ $material->area }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarMaterial({{ $material->id }})">
                                    Editar
                                </button>
                                <form method="POST" action="{{ route('insumos.materiales.destroy', $material->numero_pedido) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $material->id }}">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro?')">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No hay materiales registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function editarMaterial(id) {
        // Implementar lógica de edición
        console.log('Editar material:', id);
    }
</script>
@endsection
