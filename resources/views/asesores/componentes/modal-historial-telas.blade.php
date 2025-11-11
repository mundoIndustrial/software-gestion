<!-- Modal Historial de Telas -->
<div id="modalHistorialTelas" class="modal-overlay" style="display: none;">
    <div class="modal-container modal-large">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="material-symbols-rounded">analytics</span>
                Historial y Estadísticas de Inventario
            </h3>
            <button type="button" class="modal-close" onclick="cerrarModalHistorial()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div class="modal-body">
            <!-- Tabs -->
            <div class="tabs-container">
                <button class="tab-btn active" onclick="cambiarTab('graficas')">
                    <span class="material-symbols-rounded">bar_chart</span>
                    Gráficas
                </button>
                <button class="tab-btn" onclick="cambiarTab('historial')">
                    <span class="material-symbols-rounded">history</span>
                    Historial de Movimientos
                </button>
            </div>

            <!-- Tab Gráficas -->
            <div id="tab-graficas" class="tab-content active">
                <!-- Gráfica de Telas Más Movidas -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h4>
                            <span class="material-symbols-rounded">trending_up</span>
                            Telas Más Movidas (Últimos 30 días)
                        </h4>
                        <p class="chart-subtitle">Basado en la cantidad total de entradas y salidas</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="chartTelasMasMovidas"></canvas>
                    </div>
                </div>

                <!-- Gráfica de Stock por Tela -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h4>
                            <span class="material-symbols-rounded">inventory_2</span>
                            Stock Actual por Tela
                        </h4>
                        <p class="chart-subtitle">Ordenado de mayor a menor stock disponible</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="chartStockPorTela"></canvas>
                    </div>
                </div>

                <!-- Estadísticas Rápidas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon entrada">
                            <span class="material-symbols-rounded">add_circle</span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Entradas</span>
                            <span class="stat-value" id="totalEntradas">0</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon salida">
                            <span class="material-symbols-rounded">remove_circle</span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Salidas</span>
                            <span class="stat-value" id="totalSalidas">0</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stock">
                            <span class="material-symbols-rounded">inventory</span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Stock Total</span>
                            <span class="stat-value" id="stockTotal">0 m</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Historial -->
            <div id="tab-historial" class="tab-content">
                <!-- Filtros -->
                <div class="historial-filters">
                    <select id="filtroTipoAccion" class="filter-select-small" onchange="filtrarHistorial()">
                        <option value="">Todas las acciones</option>
                        <option value="entrada">Entradas</option>
                        <option value="salida">Salidas</option>
                    </select>
                    <select id="filtroTelaHistorial" class="filter-select-small" onchange="filtrarHistorial()">
                        <option value="">Todas las telas</option>
                    </select>
                </div>

                <!-- Tabla de Historial -->
                <div class="historial-table-container">
                    <table class="historial-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tela</th>
                                <th>Acción</th>
                                <th>Cantidad</th>
                                <th>Stock Anterior</th>
                                <th>Stock Nuevo</th>
                                <th>Usuario</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody id="historialTableBody">
                            <tr>
                                <td colspan="8" class="loading-cell">
                                    <div class="loading-spinner"></div>
                                    Cargando historial...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.modal-large {
    max-width: 1200px;
    width: 95%;
}

.tabs-container {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid var(--border-color);
}

.tab-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--text-secondary);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tab-btn:hover {
    color: var(--text-primary);
    background: var(--bg-hover);
}

.tab-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-btn .material-symbols-rounded {
    font-size: 1.25rem;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.chart-card {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
}

.chart-header {
    margin-bottom: 1.5rem;
}

.chart-header h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.chart-header .material-symbols-rounded {
    color: var(--primary-color);
    font-size: 1.5rem;
}

.chart-subtitle {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin: 0;
}

.chart-container {
    position: relative;
    height: 350px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid var(--border-color);
}

.stat-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
}

.stat-icon.entrada {
    background: rgba(0, 168, 107, 0.1);
    color: var(--success-color);
}

.stat-icon.salida {
    background: rgba(220, 38, 38, 0.1);
    color: var(--danger-color);
}

.stat-icon.stock {
    background: rgba(0, 102, 204, 0.1);
    color: var(--primary-color);
}

.stat-icon .material-symbols-rounded {
    font-size: 2rem;
}

.stat-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 600;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
}

.historial-filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.filter-select-small {
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 0.9rem;
    background: var(--bg-card);
    color: var(--text-primary);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-select-small:focus {
    outline: none;
    border-color: var(--primary-color);
}

.historial-table-container {
    background: var(--bg-card);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    max-height: 500px;
    overflow-y: auto;
}

.historial-table {
    width: 100%;
    border-collapse: collapse;
}

.historial-table thead {
    position: sticky;
    top: 0;
    z-index: 10;
}

body.dark-theme .historial-table thead {
    background: #2A3544;
}

body.light-theme .historial-table thead {
    background: var(--bg-card);
}

.historial-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 700;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border-color);
}

body.dark-theme .historial-table th {
    color: #B8C5D6;
}

body.light-theme .historial-table th {
    color: var(--text-secondary);
}

.historial-table td {
    padding: 1rem;
    font-size: 0.9rem;
    color: var(--text-primary);
    border-bottom: 1px solid var(--border-color);
}

body.dark-theme .historial-table tbody tr {
    background-color: #1A2332;
}

.historial-table tbody tr:hover {
    background-color: var(--bg-hover);
}

.accion-badge {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.875rem;
}

.accion-entrada {
    background: rgba(0, 168, 107, 0.1);
    color: var(--success-color);
}

.accion-salida {
    background: rgba(220, 38, 38, 0.1);
    color: var(--danger-color);
}

.loading-cell {
    text-align: center;
    padding: 3rem !important;
    color: var(--text-secondary);
}

.loading-spinner {
    display: inline-block;
    width: 30px;
    height: 30px;
    border: 3px solid var(--border-color);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .modal-large {
        width: 98%;
        max-height: 95vh;
    }

    .chart-container {
        height: 250px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .historial-table {
        font-size: 0.8rem;
    }

    .historial-table th,
    .historial-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>
