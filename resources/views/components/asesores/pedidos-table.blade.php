{{-- Componente de Tabla para Pedidos de Asesores --}}
<div class="pedidos-wrapper">
    <div class="pedidos-scroll">
        @if($pedidos->count() > 0)
            <table class="pedidos-table">
                <thead>
                    <tr>
                        <th class="th-filterable">
                            <div class="th-content">
                                <span>Número Pedido</span>
                                <button class="btn-filter" onclick="abrirFiltroModal('numero_pedido', this)" title="Filtrar">
                                    <span class="material-symbols-rounded">tune</span>
                                </button>
                            </div>
                        </th>
                        <th class="th-filterable">
                            <div class="th-content">
                                <span>Cliente</span>
                                <button class="btn-filter" onclick="abrirFiltroModal('cliente', this)" title="Filtrar">
                                    <span class="material-symbols-rounded">tune</span>
                                </button>
                            </div>
                        </th>
                        <th class="th-filterable">
                            <div class="th-content">
                                <span>Asesora</span>
                                <button class="btn-filter" onclick="abrirFiltroModal('asesora', this)" title="Filtrar">
                                    <span class="material-symbols-rounded">tune</span>
                                </button>
                            </div>
                        </th>
                        <th class="th-filterable">
                            <div class="th-content">
                                <span>Forma de Pago</span>
                                <button class="btn-filter" onclick="abrirFiltroModal('forma_pago', this)" title="Filtrar">
                                    <span class="material-symbols-rounded">tune</span>
                                </button>
                            </div>
                        </th>
                        <th class="th-filterable">
                            <div class="th-content">
                                <span>Estado</span>
                                <button class="btn-filter" onclick="abrirFiltroModal('estado', this)" title="Filtrar">
                                    <span class="material-symbols-rounded">tune</span>
                                </button>
                            </div>
                        </th>
                        <th class="th-filterable">
                            <div class="th-content">
                                <span>Fecha Creación</span>
                                <button class="btn-filter" onclick="abrirFiltroModal('fecha_creacion', this)" title="Filtrar">
                                    <span class="material-symbols-rounded">tune</span>
                                </button>
                            </div>
                        </th>
                        <th class="th-filterable">
                            <div class="th-content">
                                <span>Fecha Estimada</span>
                                <button class="btn-filter" onclick="abrirFiltroModal('fecha_estimada', this)" title="Filtrar">
                                    <span class="material-symbols-rounded">tune</span>
                                </button>
                            </div>
                        </th>
                        <th class="th-filterable">
                            <div class="th-content">
                                <span>Cantidad Total</span>
                                <button class="btn-filter" onclick="abrirFiltroModal('cantidad_total', this)" title="Filtrar">
                                    <span class="material-symbols-rounded">tune</span>
                                </button>
                            </div>
                        </th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedidos as $pedido)
                        <tr class="pedido-row" data-pedido-id="{{ $pedido->id }}">
                            <td class="numero-pedido">
                                <strong>PED-{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}</strong>
                            </td>
                            <td class="cliente">
                                {{ $pedido->cliente ?? 'N/A' }}
                            </td>
                            <td class="asesora">
                                {{ $pedido->asesora?->name ?? 'N/A' }}
                            </td>
                            <td class="forma-pago">
                                {{ $pedido->forma_de_pago ?? 'N/A' }}
                            </td>
                            <td class="estado">
                                <span class="badge-estado" style="background-color: {{ $config['estadoColores'][$pedido->estado] ?? '#95a5a6' }}; color: white;">
                                    {{ $pedido->estado ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="fecha-creacion">
                                {{ $pedido->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($pedido->fecha_de_creacion_de_orden)->format('d/m/Y') : 'N/A' }}
                            </td>
                            <td class="fecha-estimada">
                                {{ $pedido->fecha_estimada_entrega ? \Carbon\Carbon::parse($pedido->fecha_estimada_entrega)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="cantidad-total" style="text-align: center; font-weight: bold;">
                                {{ $pedido->cantidad_total ?? 0 }}
                            </td>
                            <script>
                                console.log(' [Pedido {{ $pedido->id }}]', {
                                    numero_pedido: {{ $pedido->numero_pedido }},
                                    cliente: '{{ $pedido->cliente }}',
                                    cantidad_total_db: {{ $pedido->cantidad_total ?? 'null' }},
                                    prendas_count: {{ $pedido->prendas->count() }},
                                    prendas: {!! json_encode($pedido->prendas->map(function($p) {
                                        return [
                                            'id' => $p->id,
                                            'nombre' => $p->nombre_prenda,
                                            'cantidad_atributo' => $p->cantidad,
                                            'tallas' => $p->tallas ? $p->tallas->mapToGroups(fn($t) => [$t->genero => [$t->talla => $t->cantidad]])->toArray() : []
                                        ];
                                    })->all()) !!}
                                });
                            </script>
                            <td class="acciones">
                                <div class="pedidos-actions">
                                    <!-- Botón de Acciones con Dropdown -->
                                    <div style="position: relative; display: inline-block;">
                                        <button onclick="toggleDropdown(event)" data-menu-id="menu-{{ $pedido->id }}" class="btn-actions-dropdown">
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                        <div class="dropdown-menu" id="menu-{{ $pedido->id }}">
                                            <button onclick="verDetallesPedido({{ $pedido->id }}); closeDropdown()" class="dropdown-item">
                                                <i class="fas fa-eye"></i> Detalle
                                            </button>
                                            <div class="dropdown-divider"></div>
                                            <button onclick="verSeguimiento({{ $pedido->id }}); closeDropdown()" class="dropdown-item">
                                                <i class="fas fa-tasks"></i> Seguimiento
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-pedidos">
                <span class="material-symbols-rounded">inbox</span>
                <p>No hay pedidos de producción</p>
            </div>
        @endif
    </div>
</div>

<style>
/* ===== CONTENEDOR TABLA ===== */
.pedidos-wrapper {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.pedidos-scroll {
    overflow-x: auto;
}

/* ===== TABLA ===== */
.pedidos-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.pedidos-table thead {
    background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
    border-bottom: 2px solid #e5e7eb;
}

.pedidos-table thead th {
    padding: 0;
    text-align: left;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
}

.th-filterable {
    padding: 0 !important;
}

.th-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 16px;
}

.th-content span {
    flex: 1;
}

.btn-filter {
    width: 28px;
    height: 28px;
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    transition: all 0.2s;
    border-radius: 4px;
    flex-shrink: 0;
}

.btn-filter:hover {
    background-color: rgba(30, 64, 175, 0.1);
    color: #1e40af;
}

.btn-filter .material-symbols-rounded {
    font-size: 18px;
}

.pedidos-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.2s;
}

.pedidos-table tbody tr:hover {
    background-color: #f9fafb;
}

.pedidos-table tbody td {
    padding: 16px;
    color: #4b5563;
}

/* ===== COLUMNAS ESPECÍFICAS ===== */
.numero-pedido {
    color: #1e40af;
    font-weight: 600;
}

.badge-estado {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

/* ===== BOTONES DE ACCIÓN ===== */
.pedidos-actions {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-view {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 14px;
    background-color: #dbeafe;
    color: #1e40af;
}

.btn-view:hover {
    background-color: #bfdbfe;
    transform: scale(1.05);
}

.btn-menu {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 14px;
    background-color: #f3f4f6;
    color: #6b7280;
}

.btn-menu:hover {
    background-color: #e5e7eb;
    transform: scale(1.05);
}

/* Botón de acciones con dropdown */
.btn-actions-dropdown {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
}

.btn-actions-dropdown:hover {
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
    transform: translateY(-1px);
}

.btn-actions-dropdown i {
    margin-right: 4px;
}

/* Dropdown Menu */
.dropdown-menu {
    display: none;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 99999;
    overflow: visible;
    min-width: 160px;
}

.dropdown-item {
    width: 100%;
    text-align: left;
    padding: 12px 14px;
    font-weight: 500;
    background: transparent;
    border: none;
    cursor: pointer;
    color: #374151;
    font-size: 14px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.dropdown-item:hover {
    background-color: #f0f9ff;
}

.dropdown-item i {
    font-size: 16px;
}

.dropdown-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 4px 0;
}
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 14px;
    background-color: #fee2e2;
    color: #991b1b;
}

.btn-pdf:hover {
    background-color: #fecaca;
    transform: scale(1.05);
}

/* ===== MENSAJE VACÍO ===== */
.no-pedidos {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.no-pedidos .material-symbols-rounded {
    font-size: 48px;
    margin-bottom: 12px;
    display: block;
    opacity: 0.5;
}

.no-pedidos p {
    margin: 0;
    font-size: 16px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .pedidos-table thead th,
    .pedidos-table tbody td {
        padding: 12px;
        font-size: 13px;
    }

    .pedidos-actions {
        gap: 6px;
    }

    .btn-view,
    .btn-pdf {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }
}

/* ===== MODO OSCURO ===== */
[data-theme="dark"] .pedidos-wrapper {
    background: rgba(255, 255, 255, 0.05);
}

[data-theme="dark"] .pedidos-table thead {
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .pedidos-table thead th {
    color: #ffffff;
}

[data-theme="dark"] .pedidos-table tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

[data-theme="dark"] .pedidos-table tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.95);
}

[data-theme="dark"] .pedidos-table tbody tr:hover td {
    color: #000000;
}

[data-theme="dark"] .pedidos-table tbody td {
    color: #e5e7eb;
}
</style>

<script>
// ===== FUNCIONES DE FILTRO =====
function abrirFiltroModal(columna, boton) {
    // Obtener todos los valores únicos de la columna
    const tabla = document.querySelector('.pedidos-table');
    const filas = tabla.querySelectorAll('tbody tr');
    const valores = new Set();
    
    // Mapeo de columnas a índices
    const columnMap = {
        'numero_pedido': 0,
        'cliente': 1,
        'asesora': 2,
        'forma_pago': 3,
        'estado': 4,
        'fecha_creacion': 5,
        'fecha_estimada': 6
    };
    
    const indice = columnMap[columna];
    
    filas.forEach(fila => {
        const celda = fila.cells[indice];
        if (celda) {
            let valor = celda.textContent.trim();
            // Limpiar valores especiales
            if (columna === 'estado') {
                valor = valor.replace(/\s+/g, ' ');
            }
            if (valor) valores.add(valor);
        }
    });
    
    // Crear modal
    const modal = document.createElement('div');
    modal.className = 'filter-modal-overlay';
    modal.innerHTML = `
        <div class="filter-modal">
            <div class="filter-modal-header">
                <h3>Filtrar por ${columna.replace('_', ' ')}</h3>
                <button class="btn-close-modal" onclick="this.closest('.filter-modal-overlay').remove()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="filter-modal-content">
                <div class="filter-search">
                    <input type="text" class="filter-search-input" placeholder="Buscar..." onkeyup="filtrarOpciones(this)">
                </div>
                <div class="filter-options">
                    ${Array.from(valores).sort().map(valor => `
                        <label class="filter-option">
                            <input type="checkbox" value="${valor}" onchange="aplicarFiltroTabla('${columna}')">
                            <span>${valor}</span>
                        </label>
                    `).join('')}
                </div>
            </div>
            <div class="filter-modal-footer">
                <button class="btn-reset-filter" onclick="resetearFiltro('${columna}')">Limpiar</button>
                <button class="btn-apply-filter" onclick="this.closest('.filter-modal-overlay').remove()">Aplicar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Cerrar al hacer clic fuera
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
    });
}

function filtrarOpciones(input) {
    const filtro = input.value.toLowerCase();
    const opciones = input.closest('.filter-modal-content').querySelectorAll('.filter-option');
    
    opciones.forEach(opcion => {
        const texto = opcion.textContent.toLowerCase();
        opcion.style.display = texto.includes(filtro) ? 'flex' : 'none';
    });
}

function aplicarFiltroTabla(columna) {
    const modal = document.querySelector('.filter-modal-overlay');
    const checkboxes = modal.querySelectorAll('.filter-option input:checked');
    const valoresSeleccionados = Array.from(checkboxes).map(cb => cb.value);
    
    const tabla = document.querySelector('.pedidos-table');
    const filas = tabla.querySelectorAll('tbody tr');
    
    const columnMap = {
        'numero_pedido': 0,
        'cliente': 1,
        'asesora': 2,
        'forma_pago': 3,
        'estado': 4,
        'fecha_creacion': 5,
        'fecha_estimada': 6
    };
    
    const indice = columnMap[columna];
    
    filas.forEach(fila => {
        const celda = fila.cells[indice];
        if (celda) {
            let valor = celda.textContent.trim();
            if (columna === 'estado') {
                valor = valor.replace(/\s+/g, ' ');
            }
            
            const mostrar = valoresSeleccionados.length === 0 || valoresSeleccionados.includes(valor);
            fila.style.display = mostrar ? '' : 'none';
        }
    });
}

function resetearFiltro(columna) {
    const modal = document.querySelector('.filter-modal-overlay');
    const checkboxes = modal.querySelectorAll('.filter-option input');
    checkboxes.forEach(cb => cb.checked = false);
    aplicarFiltroTabla(columna);
}

function verDetallesPedido(pedidoId) {
    window.location.href = `/asesores/pedidos-produccion/${pedidoId}`;
}

function verSeguimiento(pedidoId) {
    window.location.href = `/asesores/pedidos-produccion/${pedidoId}/seguimiento`;
}

function toggleDropdown(event) {
    event.preventDefault();
    const button = event.target.closest('.btn-actions-dropdown');
    const menuId = button.getAttribute('data-menu-id');
    const menu = document.getElementById(menuId);
    
    // Cerrar otros menús abiertos
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        if (m.id !== menuId) {
            m.style.display = 'none';
        }
    });
    
    // Toggle menú actual
    if (menu) {
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        
        if (menu.style.display === 'block') {
            // Posicionar el menú
            const rect = button.getBoundingClientRect();
            menu.style.position = 'fixed';
            menu.style.top = (rect.bottom + 4) + 'px';
            menu.style.left = (rect.left - 120) + 'px';
        }
    }
}

function closeDropdown() {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
    });
}

// Cerrar dropdown al hacer click afuera
document.addEventListener('click', function(event) {
    if (!event.target.closest('.pedidos-actions')) {
        closeDropdown();
    }
});
</script>

<style>
/* ===== MODAL DE FILTRO ===== */
.filter-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.filter-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 400px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.filter-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.filter-modal-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.btn-close-modal {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    border-radius: 4px;
    transition: all 0.2s;
}

.btn-close-modal:hover {
    background: #f3f4f6;
    color: #1f2937;
}

.filter-modal-content {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}

.filter-search {
    margin-bottom: 12px;
}

.filter-search-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.filter-search-input:focus {
    outline: none;
    border-color: #1e40af;
    box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
}

.filter-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}

.filter-option:hover {
    background: #f3f4f6;
}

.filter-option input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
    accent-color: #1e40af;
}

.filter-option span {
    font-size: 14px;
    color: #4b5563;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.filter-modal-footer {
    display: flex;
    gap: 8px;
    padding: 12px;
    border-top: 1px solid #e5e7eb;
}

.btn-reset-filter,
.btn-apply-filter {
    flex: 1;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-reset-filter {
    background: #f3f4f6;
    color: #4b5563;
}

.btn-reset-filter:hover {
    background: #e5e7eb;
}

.btn-apply-filter {
    background: #1e40af;
    color: white;
}

.btn-apply-filter:hover {
    background: #1e3a8a;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .filter-modal {
        width: 95%;
        max-height: 90vh;
    }
}

/* ===== MODO OSCURO ===== */
[data-theme="dark"] .filter-modal {
    background: rgba(255, 255, 255, 0.05);
}

[data-theme="dark"] .filter-modal-header,
[data-theme="dark"] .filter-modal-footer {
    border-color: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .filter-modal-header h3 {
    color: #ffffff;
}

[data-theme="dark"] .filter-search-input {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: #e5e7eb;
}

[data-theme="dark"] .filter-option {
    color: #e5e7eb;
}

[data-theme="dark"] .filter-option:hover {
    background: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .btn-reset-filter {
    background: rgba(255, 255, 255, 0.1);
    color: #e5e7eb;
}

[data-theme="dark"] .btn-reset-filter:hover {
    background: rgba(255, 255, 255, 0.15);
}
</style>

