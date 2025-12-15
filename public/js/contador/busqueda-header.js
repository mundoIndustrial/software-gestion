// Script para manejar búsqueda desde el header en las nuevas vistas
let filtrosColumnasActivas = {};

/**
 * Obtener valores únicos de una columna en la tabla
 */
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

/**
 * Abrir modal de filtro para una columna
 */
function abrirFiltroColumna(columna, valores) {
    const modal = document.getElementById(`filter-modal-${columna}`);
    if (!modal) {
        crearModalFiltro(columna, valores);
    } else {
        modal.classList.add('active');
    }
}

/**
 * Crear modal de filtro
 */
function crearModalFiltro(columna, valores) {
    const overlay = document.createElement('div');
    overlay.className = 'filter-modal-overlay active';
    overlay.id = `filter-modal-${columna}`;
    
    const valoresUnicos = [...new Set(valores)].sort();
    
    overlay.innerHTML = `
        <div class="filter-modal">
            <div class="filter-modal-header">
                <h3>Filtrar por ${columna.charAt(0).toUpperCase() + columna.slice(1)}</h3>
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

/**
 * Cerrar modal de filtro
 */
function cerrarFiltroColumna(columna) {
    const modal = document.getElementById(`filter-modal-${columna}`);
    if (modal) modal.classList.remove('active');
}

/**
 * Actualizar filtro de columna
 */
function actualizarFiltroColumna(columna) {
    const checkboxes = document.querySelectorAll(`#filter-modal-${columna} input[type="checkbox"]:checked`);
    const valores = Array.from(checkboxes).map(cb => cb.value);
    filtrosColumnasActivas[columna] = valores;
    actualizarBadgeFiltro(columna);
}

/**
 * Aplicar filtro de columna
 */
function aplicarFiltroColumna(columna) {
    actualizarFiltroColumna(columna);
    aplicarBusquedaYFiltros();
    cerrarFiltroColumna(columna);
}

/**
 * Limpiar filtro de columna
 */
function limpiarFiltroColumna(columna) {
    document.querySelectorAll(`#filter-modal-${columna} input[type="checkbox"]`).forEach(cb => cb.checked = false);
    delete filtrosColumnasActivas[columna];
    actualizarBadgeFiltro(columna);
}

/**
 * Actualizar badge de filtro
 */
function actualizarBadgeFiltro(columna) {
    const btn = document.querySelector(`[data-filter-column="${columna}"]`);
    if (btn) {
        const badge = btn.querySelector('.filter-badge');
        if (filtrosColumnasActivas[columna]?.length > 0) {
            badge.textContent = filtrosColumnasActivas[columna].length;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Filtrar opciones en modal
 */
function filtrarOpcionesModal(input, columna) {
    const termino = input.value.toLowerCase();
    const opciones = document.querySelectorAll(`#filter-modal-${columna} .filter-option`);
    opciones.forEach(opcion => {
        const label = opcion.querySelector('label').textContent.toLowerCase();
        opcion.style.display = label.includes(termino) ? '' : 'none';
    });
}

/**
 * Aplicar búsqueda y filtros
 */
function aplicarBusquedaYFiltros() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
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
                const valorFila = row.dataset[columna]?.toLowerCase() || '';
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

/**
 * Limpiar todos los filtros
 */
function limpiarTodosFiltros() {
    document.getElementById('searchInput').value = '';
    filtrosColumnasActivas = {};
    document.querySelectorAll('.btn-filter-column').forEach(btn => {
        const badge = btn.querySelector('.filter-badge');
        if (badge) badge.style.display = 'none';
    });
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    aplicarBusquedaYFiltros();
}

// Estilos CSS para los modales de filtro
const style = document.createElement('style');
style.textContent = `
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
        box-sizing: border-box;
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

    .filter-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
`;
document.head.appendChild(style);
