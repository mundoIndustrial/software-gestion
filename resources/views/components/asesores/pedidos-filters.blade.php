{{-- Componente de Filtros para Pedidos de Asesores --}}
<div class="pedidos-filters-wrapper">
    <div class="filters-header">
        <h3 style="margin: 0; font-size: 1rem; color: #374151; font-weight: 600;">Filtros</h3>
        <button type="button" class="btn-clear-filters" onclick="limpiarFiltrosPedidos()">
            <span class="material-symbols-rounded">close</span>
            Limpiar
        </button>
    </div>

    <div class="filters-grid">
        {{-- Filtro por Estado --}}
        <div class="filter-group">
            <label for="filter-estado">Estado</label>
            <select id="filter-estado" class="filter-select" onchange="aplicarFiltrosPedidos()">
                <option value="">Todos los estados</option>
                @foreach($estados as $estado)
                    <option value="{{ $estado }}">{{ $estado }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filtro por Forma de Pago --}}
        <div class="filter-group">
            <label for="filter-forma-pago">Forma de Pago</label>
            <select id="filter-forma-pago" class="filter-select" onchange="aplicarFiltrosPedidos()">
                <option value="">Todas las formas</option>
                @foreach($formasPago as $forma)
                    <option value="{{ $forma }}">{{ $forma }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filtro por Cliente --}}
        <div class="filter-group">
            <label for="filter-cliente">Cliente</label>
            <input type="text" id="filter-cliente" class="filter-input" placeholder="Buscar cliente..." onkeyup="aplicarFiltrosPedidos()">
        </div>

        {{-- Filtro por Número de Pedido --}}
        <div class="filter-group">
            <label for="filter-pedido">Número de Pedido</label>
            <input type="text" id="filter-pedido" class="filter-input" placeholder="Ej: PED-00001..." onkeyup="aplicarFiltrosPedidos()">
        </div>
    </div>
</div>

<style>
/* ===== CONTENEDOR FILTROS ===== */
.pedidos-filters-wrapper {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 16px;
    margin-bottom: 20px;
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

/* ===== BOTÓN LIMPIAR ===== */
.btn-clear-filters {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    background-color: #fee2e2;
    color: #991b1b;
    transition: all 0.2s;
}

.btn-clear-filters:hover {
    background-color: #fecaca;
    transform: scale(1.02);
}

.btn-clear-filters .material-symbols-rounded {
    font-size: 16px;
}

/* ===== GRID DE FILTROS ===== */
.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-select,
.filter-input {
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 13px;
    background-color: #f9fafb;
    color: #4b5563;
    transition: all 0.2s;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: #1e40af;
    background-color: white;
    box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .pedidos-filters-wrapper {
        padding: 12px;
    }

    .filters-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .filters-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .btn-clear-filters {
        width: 100%;
        justify-content: center;
    }
}

/* ===== MODO OSCURO ===== */
[data-theme="dark"] .pedidos-filters-wrapper {
    background: rgba(255, 255, 255, 0.05);
}

[data-theme="dark"] .filter-select,
[data-theme="dark"] .filter-input {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: #e5e7eb;
}

[data-theme="dark"] .filter-select:focus,
[data-theme="dark"] .filter-input:focus {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: #60a5fa;
}

[data-theme="dark"] .filter-group label {
    color: #e5e7eb;
}
</style>

<script>
function aplicarFiltrosPedidos() {
    const estado = document.getElementById('filter-estado').value;
    const formaPago = document.getElementById('filter-forma-pago').value;
    const cliente = document.getElementById('filter-cliente').value;
    const pedido = document.getElementById('filter-pedido').value;

    // Construir URL con parámetros
    const params = new URLSearchParams();
    if (estado) params.append('estado', estado);
    if (formaPago) params.append('forma_pago', formaPago);
    if (cliente) params.append('cliente', cliente);
    if (pedido) params.append('pedido', pedido);

    // Redirigir con filtros
    const url = new URL(window.location);
    url.search = params.toString();
    window.location.href = url.toString();
}

function limpiarFiltrosPedidos() {
    document.getElementById('filter-estado').value = '';
    document.getElementById('filter-forma-pago').value = '';
    document.getElementById('filter-cliente').value = '';
    document.getElementById('filter-pedido').value = '';

    // Redirigir sin parámetros
    window.location.href = window.location.pathname;
}
</script>
