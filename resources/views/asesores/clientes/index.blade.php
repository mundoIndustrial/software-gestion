@extends('asesores.layout')

@section('title', 'Clientes')
@section('page-title', 'Mis Clientes')

@section('content')
<div class="container-fluid">
    <!-- HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="margin: 0; font-size: 2rem; color: #333;">👥 Clientes</h1>
        <button onclick="abrirModalCliente()" class="btn btn-primary" style="background: #3498db; color: white; padding: 10px 20px; border-radius: 4px; border: none; cursor: pointer;">
            <i class="fas fa-plus"></i> Nuevo Cliente
        </button>
    </div>

    <!-- TABLA DE CLIENTES -->
    @if($clientes->count() > 0)
        <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #ecf0f1;">
                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #333;">Nombre</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #333;">Email</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #333;">Teléfono</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #333;">Ciudad</th>
                        <th style="padding: 15px; text-align: center; font-weight: 600; color: #333;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientes as $cliente)
                        <tr style="border-bottom: 1px solid #ecf0f1; hover: background: #f8f9fa;">
                            <td style="padding: 15px;">{{ $cliente->nombre }}</td>
                            <td style="padding: 15px;">{{ $cliente->email ?? '-' }}</td>
                            <td style="padding: 15px;">{{ $cliente->telefono ?? '-' }}</td>
                            <td style="padding: 15px;">{{ $cliente->ciudad ?? '-' }}</td>
                            <td style="padding: 15px; text-align: center;">
                                <button onclick="editarCliente({{ $cliente->id }}, '{{ $cliente->nombre }}', '{{ $cliente->email }}', '{{ $cliente->telefono }}', '{{ $cliente->ciudad }}', '{{ $cliente->notas }}')" class="btn" style="background: #f39c12; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; margin-right: 5px;">
                                    ✏️ Editar
                                </button>
                                <button onclick="eliminarCliente({{ $cliente->id }})" class="btn" style="background: #e74c3c; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">
                                    🗑️ Eliminar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->
        <div style="margin-top: 30px; display: flex; justify-content: center;">
            {{ $clientes->links() }}
        </div>
    @else
        <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 40px; text-align: center;">
            <p style="margin: 0; color: #666; font-size: 1.1rem;">
                📭 No hay clientes registrados aún
            </p>
            <button onclick="abrirModalCliente()" style="display: inline-block; margin-top: 15px; background: #3498db; color: white; padding: 10px 20px; border-radius: 4px; border: none; cursor: pointer;">
                Crear Primer Cliente
            </button>
        </div>
    @endif
</div>

<!-- MODAL CLIENTE -->
<div id="modalCliente" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 30px; width: 90%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 20px 0; color: #333;">Nuevo Cliente</h2>
        
        <form id="formCliente">
            @csrf
            <input type="hidden" id="clienteId" value="">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Email</label>
                <input type="email" id="email" name="email" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Teléfono</label>
                <input type="text" id="telefono" name="telefono" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Ciudad</label>
                <input type="text" id="ciudad" name="ciudad" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Notas</label>
                <textarea id="notas" name="notas" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; resize: vertical; min-height: 80px;"></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn" style="flex: 1; background: #3498db; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    Guardar
                </button>
                <button type="button" onclick="cerrarModalCliente()" class="btn" style="flex: 1; background: #95a5a6; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalCliente() {
    document.getElementById('modalCliente').style.display = 'flex';
    document.getElementById('formCliente').reset();
    document.getElementById('clienteId').value = '';
    document.querySelector('#modalCliente h2').textContent = 'Nuevo Cliente';
}

function cerrarModalCliente() {
    document.getElementById('modalCliente').style.display = 'none';
}

function editarCliente(id, nombre, email, telefono, ciudad, notas) {
    document.getElementById('clienteId').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('email').value = email;
    document.getElementById('telefono').value = telefono;
    document.getElementById('ciudad').value = ciudad;
    document.getElementById('notas').value = notas;
    document.querySelector('#modalCliente h2').textContent = 'Editar Cliente';
    document.getElementById('modalCliente').style.display = 'flex';
}

function eliminarCliente(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este cliente?')) {
        fetch(`/asesores/clientes/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Cliente eliminado');
                location.reload();
            }
        });
    }
}

document.getElementById('formCliente').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const clienteId = document.getElementById('clienteId').value;
    const url = clienteId ? `/asesores/clientes/${clienteId}` : '/asesores/clientes';
    const method = clienteId ? 'PATCH' : 'POST';
    
    const formData = new FormData(this);
    
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        }
    });
});

// Cerrar modal al hacer clic fuera
document.getElementById('modalCliente').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalCliente();
    }
});
</script>
@endsection
