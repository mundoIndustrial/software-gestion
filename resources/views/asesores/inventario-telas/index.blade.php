@extends('asesores.layout')

@section('title', 'Inventario de Telas')
@section('page-title', 'Inventario de Telas')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventario-telas/inventario.css') }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="inventario-telas-container">
    <!-- Barra de Acciones -->
    <div class="list-header">
        @if(false) {{-- Oculto para asesores, disponible para futuro rol insumos --}}
        <button type="button" class="btn btn-create" onclick="abrirModalCrearTela()">
            <span class="material-symbols-rounded">add_circle</span>
            Nueva Tela
        </button>
        <button type="button" class="btn btn-historial" onclick="abrirModalHistorial()">
            <span class="material-symbols-rounded">analytics</span>
            Historial y Estadísticas
        </button>
        @endif
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar por nombre o categoría...">
        </div>
        
        <div class="filter-group">
            <select id="filterCategoria" class="filter-select">
                <option value="">Todas las Categorías</option>
                @foreach($telas->unique('categoria')->pluck('categoria')->sort() as $categoria)
                    <option value="{{ $categoria }}">{{ $categoria }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Tabla de Inventario -->
    <div class="table-container">
        @if($telas->count() > 0)
            <table class="inventario-table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Nombre de Tela</th>
                        <th>Stock</th>
                        <th>Metraje Sugerido</th>
                        <th>Fecha de Registro</th>
                        @if(false) {{-- Oculto para asesores --}}
                        <th>Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="telasTableBody">
                    @foreach($telas as $tela)
                        <tr data-categoria="{{ $tela->categoria }}" data-nombre="{{ $tela->nombre_tela }}">
                            <td class="categoria-cell">{{ $tela->categoria }}</td>
                            <td class="nombre-tela">{{ $tela->nombre_tela }}</td>
                            <td>
                                <span class="stock-badge {{ $tela->stock < 10 ? 'stock-bajo' : ($tela->stock < 50 ? 'stock-medio' : 'stock-alto') }}">
                                    {{ $tela->stock == floor($tela->stock) ? number_format($tela->stock, 0) : number_format($tela->stock, 2) }} m
                                </span>
                            </td>
                            <td class="metraje-cell">
                                {{ $tela->metraje_sugerido ? ($tela->metraje_sugerido == floor($tela->metraje_sugerido) ? number_format($tela->metraje_sugerido, 0) : number_format($tela->metraje_sugerido, 2)) . ' m' : '-' }}
                            </td>
                            <td class="fecha-cell">{{ $tela->fecha_registro ? \Carbon\Carbon::parse($tela->fecha_registro)->format('d/m/Y H:i') : '-' }}</td>
                            @if(false) {{-- Oculto para asesores --}}
                            <td class="actions-cell">
                                <button type="button" 
                                        class="btn-action btn-adjust" 
                                        onclick="abrirModalAjustarStock({{ $tela->id }}, '{{ $tela->nombre_tela }}', {{ $tela->stock }})"
                                        title="Ajustar Stock">
                                    <span class="material-symbols-rounded">tune</span>
                                </button>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No hay telas en el inventario</h3>
                <p>Aún no se han registrado telas en el sistema.</p>
            </div>
        @endif
    </div>
</div>

<!-- Incluir Modales -->
@include('asesores.componentes.modal-ajustar-stock')
@include('asesores.componentes.modal-crear-tela', ['categorias' => $telas->unique('categoria')->pluck('categoria')->sort()])
@include('asesores.componentes.modal-historial-telas')

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterCategoria = document.getElementById('filterCategoria');
    const tableBody = document.getElementById('telasTableBody');
    const rows = tableBody.querySelectorAll('tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategoria = filterCategoria.value.toLowerCase();

        rows.forEach(row => {
            const nombre = row.dataset.nombre.toLowerCase();
            const categoria = row.dataset.categoria.toLowerCase();

            const matchesSearch = nombre.includes(searchTerm) || categoria.includes(searchTerm);
            const matchesCategoria = !selectedCategoria || categoria === selectedCategoria;

            if (matchesSearch && matchesCategoria) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterTable);
    filterCategoria.addEventListener('change', filterTable);
});

// ========================================
// FUNCIONES PARA MODAL AJUSTAR STOCK
// ========================================
let stockActualGlobal = 0;

function abrirModalAjustarStock(telaId, telaNombre, stockActual) {
    stockActualGlobal = stockActual;
    document.getElementById('tela_id_ajuste').value = telaId;
    document.getElementById('tela_nombre_ajuste').textContent = telaNombre;
    document.getElementById('stock_actual_ajuste').textContent = stockActual + ' m';
    document.getElementById('preview_stock_actual').textContent = stockActual + ' m';
    document.getElementById('cantidad_ajuste').value = '';
    document.getElementById('observaciones_ajuste').value = '';
    actualizarVistaPrevia();
    document.getElementById('modalAjustarStock').style.display = 'flex';
}

function cerrarModalAjustarStock() {
    document.getElementById('modalAjustarStock').style.display = 'none';
}

function actualizarVistaPrevia() {
    const tipoAccion = document.querySelector('input[name="tipo_accion"]:checked').value;
    const cantidad = parseFloat(document.getElementById('cantidad_ajuste').value) || 0;
    
    const operador = tipoAccion === 'entrada' ? '+' : '-';
    document.getElementById('preview_operator').textContent = operador;
    document.getElementById('preview_cantidad').textContent = cantidad.toFixed(2) + ' m';
    
    const nuevoStock = tipoAccion === 'entrada' 
        ? stockActualGlobal + cantidad 
        : stockActualGlobal - cantidad;
    
    document.getElementById('preview_stock_nuevo').textContent = nuevoStock.toFixed(2) + ' m';
    
    const stockNuevoElement = document.getElementById('preview_stock_nuevo');
    if (nuevoStock < 0) {
        stockNuevoElement.style.color = 'var(--danger-color)';
    } else {
        stockNuevoElement.style.color = 'var(--success-color)';
    }
}

function ajustarStock(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const cantidad = parseFloat(formData.get('cantidad'));
    const tipoAccion = formData.get('tipo_accion');
    const nuevoStock = tipoAccion === 'entrada' 
        ? stockActualGlobal + cantidad 
        : stockActualGlobal - cantidad;
    
    if (nuevoStock < 0) {
        alert('Error: El stock no puede ser negativo');
        return;
    }
    
    fetch('{{ route("asesores.inventario-telas.ajustar-stock") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            tela_id: formData.get('tela_id'),
            tipo_accion: tipoAccion,
            cantidad: cantidad,
            observaciones: formData.get('observaciones')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Stock ajustado correctamente');
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo ajustar el stock'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al ajustar el stock');
    });
}

// ========================================
// FUNCIONES PARA MODAL CREAR TELA
// ========================================
function abrirModalCrearTela() {
    document.getElementById('formCrearTela').reset();
    document.getElementById('nuevaCategoriaContainer').style.display = 'none';
    document.getElementById('modalCrearTela').style.display = 'flex';
}

function cerrarModalCrearTela() {
    document.getElementById('modalCrearTela').style.display = 'none';
}

function mostrarInputNuevaCategoria() {
    document.getElementById('nuevaCategoriaContainer').style.display = 'block';
    document.getElementById('nueva_categoria_input').focus();
}

function cancelarNuevaCategoria() {
    document.getElementById('nuevaCategoriaContainer').style.display = 'none';
    document.getElementById('nueva_categoria_input').value = '';
}

function agregarNuevaCategoria() {
    const nuevaCategoria = document.getElementById('nueva_categoria_input').value.trim();
    if (nuevaCategoria) {
        const select = document.getElementById('categoria_nueva');
        const option = new Option(nuevaCategoria, nuevaCategoria, true, true);
        select.add(option);
        cancelarNuevaCategoria();
    }
}

function crearTela(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('{{ route("asesores.inventario-telas.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            categoria: formData.get('categoria'),
            nombre_tela: formData.get('nombre_tela'),
            stock: formData.get('stock'),
            metraje_sugerido: formData.get('metraje_sugerido')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Tela creada correctamente');
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo crear la tela'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al crear la tela');
    });
}

// Cerrar modales al hacer clic fuera
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
    }
});

// ========================================
// FUNCIONES PARA MODAL HISTORIAL
// ========================================
let chartTelasMasMovidas = null;
let chartStockPorTela = null;
let historialData = [];

function abrirModalHistorial() {
    document.getElementById('modalHistorialTelas').style.display = 'flex';
    cargarDatosHistorial();
}

function cerrarModalHistorial() {
    document.getElementById('modalHistorialTelas').style.display = 'none';
}

function cambiarTab(tabName) {
    // Ocultar todos los tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar tab seleccionado
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.closest('.tab-btn').classList.add('active');
}

function cargarDatosHistorial() {
    fetch('{{ route("asesores.inventario-telas.historial") }}', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        historialData = data.historial;
        
        // Actualizar estadísticas
        document.getElementById('totalEntradas').textContent = data.estadisticas.total_entradas;
        document.getElementById('totalSalidas').textContent = data.estadisticas.total_salidas;
        document.getElementById('stockTotal').textContent = data.estadisticas.stock_total + ' m';
        
        // Crear gráficas
        crearGraficaTelasMasMovidas(data.telas_mas_movidas);
        crearGraficaStockPorTela(data.stock_por_tela);
        
        // Llenar tabla de historial
        llenarTablaHistorial(data.historial);
        
        // Llenar filtro de telas
        llenarFiltroTelas(data.telas);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cargar el historial');
    });
}

function crearGraficaTelasMasMovidas(datos) {
    const ctx = document.getElementById('chartTelasMasMovidas');
    
    if (chartTelasMasMovidas) {
        chartTelasMasMovidas.destroy();
    }
    
    chartTelasMasMovidas = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.nombre_tela),
            datasets: [{
                label: 'Total Movimientos (m)',
                data: datos.map(d => d.total_movimientos),
                backgroundColor: 'rgba(0, 102, 204, 0.7)',
                borderColor: 'rgba(0, 102, 204, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            return 'Total: ' + context.parsed.y.toFixed(2) + ' m';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' m';
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function crearGraficaStockPorTela(datos) {
    const ctx = document.getElementById('chartStockPorTela');
    
    if (chartStockPorTela) {
        chartStockPorTela.destroy();
    }
    
    // Ordenar de mayor a menor
    datos.sort((a, b) => b.stock - a.stock);
    
    chartStockPorTela = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.nombre_tela),
            datasets: [{
                label: 'Stock Actual (m)',
                data: datos.map(d => d.stock),
                backgroundColor: datos.map(d => {
                    if (d.stock < 10) return 'rgba(220, 38, 38, 0.7)';
                    if (d.stock < 50) return 'rgba(247, 127, 0, 0.7)';
                    return 'rgba(0, 168, 107, 0.7)';
                }),
                borderColor: datos.map(d => {
                    if (d.stock < 10) return 'rgba(220, 38, 38, 1)';
                    if (d.stock < 50) return 'rgba(247, 127, 0, 1)';
                    return 'rgba(0, 168, 107, 1)';
                }),
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            return 'Stock: ' + context.parsed.x.toFixed(2) + ' m';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' m';
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function llenarTablaHistorial(historial) {
    const tbody = document.getElementById('historialTableBody');
    
    if (historial.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="loading-cell">No hay movimientos registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = historial.map(h => `
        <tr data-tipo="${h.tipo_accion}" data-tela="${h.tela_nombre}">
            <td>${new Date(h.fecha_accion).toLocaleString('es-ES')}</td>
            <td><strong>${h.tela_nombre}</strong></td>
            <td>
                <span class="accion-badge accion-${h.tipo_accion}">
                    ${h.tipo_accion.charAt(0).toUpperCase() + h.tipo_accion.slice(1)}
                </span>
            </td>
            <td><strong>${parseFloat(h.cantidad).toFixed(2)} m</strong></td>
            <td>${parseFloat(h.stock_anterior).toFixed(2)} m</td>
            <td>${parseFloat(h.stock_nuevo).toFixed(2)} m</td>
            <td>${h.usuario_nombre}</td>
            <td>${h.observaciones || '-'}</td>
        </tr>
    `).join('');
}

function llenarFiltroTelas(telas) {
    const select = document.getElementById('filtroTelaHistorial');
    select.innerHTML = '<option value="">Todas las telas</option>';
    
    telas.forEach(tela => {
        const option = document.createElement('option');
        option.value = tela.nombre_tela;
        option.textContent = tela.nombre_tela;
        select.appendChild(option);
    });
}

function filtrarHistorial() {
    const tipoAccion = document.getElementById('filtroTipoAccion').value;
    const tela = document.getElementById('filtroTelaHistorial').value;
    
    const rows = document.querySelectorAll('#historialTableBody tr');
    
    rows.forEach(row => {
        const rowTipo = row.dataset.tipo;
        const rowTela = row.dataset.tela;
        
        const matchTipo = !tipoAccion || rowTipo === tipoAccion;
        const matchTela = !tela || rowTela === tela;
        
        if (matchTipo && matchTela) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
@endsection
