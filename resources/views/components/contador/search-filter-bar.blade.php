{{-- Barra de Búsqueda y Filtros por Columnas para Contador --}}
<div class="search-filter-container" style="background: var(--bg-card, white); padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <!-- Barra de Búsqueda General -->
        <div style="flex: 1; min-width: 250px; position: relative;">
            <input 
                type="text" 
                id="searchInput" 
                placeholder="Buscar por número, cliente o asesora..." 
                class="search-input"
                style="
                    width: 100%;
                    padding: 10px 14px 10px 36px;
                    border: 1px solid var(--border-color, #d1d5db);
                    border-radius: 6px;
                    font-size: 0.95rem;
                    transition: all 0.2s ease;
                    background: var(--bg-input, white);
                    color: var(--text-primary, #000);
                "
                oninput="aplicarBusquedaYFiltros()"
            >
            <i class="fas fa-search" style="
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: var(--text-secondary, #9ca3af);
                font-size: 0.9rem;
            "></i>
        </div>

        <!-- Botón Limpiar Filtros -->
        <button 
            id="btnLimpiarFiltros"
            onclick="limpiarTodosFiltros()"
            style="
                padding: 8px 16px;
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 500;
                font-size: 0.9rem;
                transition: all 0.2s ease;
                opacity: 0;
                visibility: hidden;
                transform: scale(0);
            "
            onmouseover="this.style.background='linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)'"
            onmouseout="this.style.background='linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'"
        >
            <i class="fas fa-redo" style="margin-right: 6px;"></i>Limpiar Filtros
        </button>
    </div>
</div>

<style>
    .search-filter-container {
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .search-input:focus {
        outline: none;
        border-color: var(--primary-color, #1e40af);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .search-input::placeholder {
        color: var(--text-secondary, #9ca3af);
    }

    /* Modo oscuro */
    @media (prefers-color-scheme: dark) {
        .search-input {
            background: var(--bg-input, #1f2937) !important;
            color: var(--text-primary, #f3f4f6) !important;
            border-color: var(--border-color, #374151) !important;
        }

        .search-input::placeholder {
            color: var(--text-secondary, #9ca3af) !important;
        }
    }

    /* Estilos para botones de filtro en header */
    .btn-filter-column {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        opacity: 0.7;
        position: relative;
    }

    .btn-filter-column:hover {
        opacity: 1;
        transform: scale(1.15);
    }

    .btn-filter-column .material-symbols-rounded {
        font-size: 1.2rem;
    }

    .filter-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .btn-filter-column.has-filter .filter-badge {
        opacity: 1;
        transform: scale(1);
    }

    /* Modal de Filtros */
    .filter-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .filter-modal-overlay.active {
        display: flex;
    }

    .filter-modal {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        max-height: 90vh;
        overflow-y: auto;
        animation: slideUp 0.3s ease;
        width: 90%;
        max-width: 450px;
    }

    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .filter-modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        background: white;
        z-index: 10;
    }

    .filter-modal-header h3 {
        margin: 0;
        font-size: 1.125rem;
        color: #1e40af;
        font-weight: 700;
    }

    .filter-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
        transition: color 0.3s ease;
    }

    .filter-modal-close:hover {
        color: #1e40af;
    }

    .filter-modal-body {
        padding: 1.5rem;
    }

    .filter-search {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.95rem;
        margin-bottom: 1rem;
        transition: border-color 0.3s ease;
    }

    .filter-search:focus {
        outline: none;
        border-color: #1e40af;
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .filter-options {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        max-height: 400px;
        overflow-y: auto;
    }

    .filter-option {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 6px;
        transition: background 0.2s ease;
    }

    .filter-option:hover {
        background: #f3f4f6;
    }

    .filter-option input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #1e40af;
    }

    .filter-option label {
        flex: 1;
        cursor: pointer;
        font-size: 0.95rem;
        color: #374151;
    }

    .filter-modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        position: sticky;
        bottom: 0;
        background: white;
    }

    .btn-filter-apply,
    .btn-filter-reset {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.875rem;
    }

    .btn-filter-apply {
        background: #1e40af;
        color: white;
    }

    .btn-filter-apply:hover {
        background: #1e3a8a;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(30, 64, 175, 0.2);
    }

    .btn-filter-reset {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
    }

    .btn-filter-reset:hover {
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(249, 115, 22, 0.3);
    }
</style>

<script>
// Almacenar filtros activos por columna
let filtrosActivos = {};
let filtrosColumnasActivas = {};

function obtenerValoresColumna(columna) {
    const valores = [];
    const rows = document.querySelectorAll('.table-row');
    rows.forEach(row => {
        const valor = row.dataset[columna];
        if (valor && !valores.includes(valor)) {
            valores.push(valor);
        }
    });
    return valores.sort();
}

function abrirFiltroColumna(columna, valores) {
    const modal = document.getElementById(`filter-modal-${columna}`);
    if (!modal) {
        crearModalFiltro(columna, valores);
    } else {
        modal.classList.add('active');
    }
}

function crearModalFiltro(columna, valores) {
    const overlay = document.createElement('div');
    overlay.className = 'filter-modal-overlay active';
    overlay.id = `filter-modal-${columna}`;
    
    const valoresUnicos = [...new Set(valores)].sort();
    
    overlay.innerHTML = `
        <div class="filter-modal">
            <div class="filter-modal-header">
                <h3>Filtrar por ${columna}</h3>
                <button class="filter-modal-close" onclick="cerrarFiltroColumna('${columna}')">×</button>
            </div>
            <div class="filter-modal-body">
                <input type="text" class="filter-search" placeholder="Buscar..." onkeyup="filtrarOpcionesModal(this, '${columna}')">
                <div class="filter-options">
                    ${valoresUnicos.map(valor => `
                        <div class="filter-option">
                            <input type="checkbox" id="check-${columna}-${valor}" value="${valor}" onchange="actualizarFiltroColumna('${columna}')">
                            <label for="check-${columna}-${valor}">${valor || '(vacío)'}</label>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div class="filter-modal-footer">
                <button class="btn-filter-reset" onclick="limpiarFiltroColumna('${columna}')">Limpiar</button>
                <button class="btn-filter-apply" onclick="aplicarFiltroColumna('${columna}')">Aplicar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) cerrarFiltroColumna(columna);
    });
}

function cerrarFiltroColumna(columna) {
    const modal = document.getElementById(`filter-modal-${columna}`);
    if (modal) modal.classList.remove('active');
}

function actualizarFiltroColumna(columna) {
    const checkboxes = document.querySelectorAll(`#filter-modal-${columna} input[type="checkbox"]:checked`);
    const valores = Array.from(checkboxes).map(cb => cb.value);
    filtrosColumnasActivas[columna] = valores;
    actualizarBadgeFiltro(columna);
}

function aplicarFiltroColumna(columna) {
    actualizarFiltroColumna(columna);
    aplicarBusquedaYFiltros();
    cerrarFiltroColumna(columna);
}

function limpiarFiltroColumna(columna) {
    document.querySelectorAll(`#filter-modal-${columna} input[type="checkbox"]`).forEach(cb => cb.checked = false);
    delete filtrosColumnasActivas[columna];
    actualizarBadgeFiltro(columna);
}

function actualizarBadgeFiltro(columna) {
    const btn = document.querySelector(`[data-filter-column="${columna}"]`);
    if (btn) {
        if (filtrosColumnasActivas[columna]?.length > 0) {
            btn.classList.add('has-filter');
            let badge = btn.querySelector('.filter-badge');
            if (!badge) {
                badge = document.createElement('div');
                badge.className = 'filter-badge';
                btn.appendChild(badge);
            }
            badge.textContent = filtrosColumnasActivas[columna].length;
        } else {
            btn.classList.remove('has-filter');
        }
    }
}

function filtrarOpcionesModal(input, columna) {
    const termino = input.value.toLowerCase();
    const opciones = document.querySelectorAll(`#filter-modal-${columna} .filter-option`);
    opciones.forEach(opcion => {
        const label = opcion.querySelector('label').textContent.toLowerCase();
        opcion.style.display = label.includes(termino) ? '' : 'none';
    });
}

function aplicarBusquedaYFiltros() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.table-row');
    let hayFiltrosActivos = false;

    rows.forEach(row => {
        let mostrar = true;

        // Búsqueda general
        if (searchTerm) {
            const numero = row.dataset.numero?.toLowerCase() || '';
            const cliente = row.dataset.cliente?.toLowerCase() || '';
            const asesora = row.dataset.asesora?.toLowerCase() || '';
            
            mostrar = numero.includes(searchTerm) || cliente.includes(searchTerm) || asesora.includes(searchTerm);
        }

        // Filtros por columna
        for (const [columna, valores] of Object.entries(filtrosColumnasActivas)) {
            if (valores.length > 0) {
                hayFiltrosActivos = true;
                const valorFila = row.dataset[columna.toLowerCase()]?.toLowerCase() || '';
                mostrar = mostrar && valores.some(v => valorFila.includes(v.toLowerCase()));
            }
        }

        row.style.display = mostrar ? '' : 'none';
    });

    // Mostrar/ocultar botón limpiar filtros
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    if (btnLimpiar) {
        if (hayFiltrosActivos || searchTerm) {
            btnLimpiar.style.opacity = '1';
            btnLimpiar.style.visibility = 'visible';
            btnLimpiar.style.transform = 'scale(1)';
        } else {
            btnLimpiar.style.opacity = '0';
            btnLimpiar.style.visibility = 'hidden';
            btnLimpiar.style.transform = 'scale(0)';
        }
    }
}

function limpiarTodosFiltros() {
    document.getElementById('searchInput').value = '';
    filtrosColumnasActivas = {};
    document.querySelectorAll('.btn-filter-column').forEach(btn => btn.classList.remove('has-filter'));
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    aplicarBusquedaYFiltros();
}
</script>
