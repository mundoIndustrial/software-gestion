/**
 * Filtros estilo pedidos-logo para recibos de costura/reflectivo
 */

document.addEventListener('DOMContentLoaded', function() {
    const STORAGE_KEY = 'recibos_costura_filters';
    window.__columnFilters = (() => {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    })();

    window.__filterModalState = {
        columnKey: null,
        title: null,
        options: [],
        selected: new Set(),
    };

    function persistFilters() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(window.__columnFilters || {}));
        } catch (e) {}
    }

    function updateFilterBadges() {
        document.querySelectorAll('.btn-filter-column').forEach(btn => {
            const key = btn.dataset.column;
            const values = (window.__columnFilters && window.__columnFilters[key]) ? window.__columnFilters[key] : [];
            const badge = btn.querySelector('.filter-badge');
            if (badge) badge.textContent = String(values.length || 0);
            if (values.length) {
                btn.classList.add('has-filter');
            } else {
                btn.classList.remove('has-filter');
            }
        });

        const floatingBtn = document.getElementById('floating-clear-filters');
        if (floatingBtn) {
            const totalActive = Object.values(window.__columnFilters || {}).reduce((acc, arr) => acc + ((arr || []).length), 0);
            if (totalActive > 0) floatingBtn.classList.add('visible');
            else floatingBtn.classList.remove('visible');
        }
    }

    window.clearAllFilters = function() {
        window.__columnFilters = {};
        persistFilters();
        updateFilterBadges();
        // Recargar la página sin filtros
        window.location.href = window.location.pathname;
    };

    window.openColumnFilter = async function(columnKey, title) {
        window.__filterModalState.columnKey = columnKey;
        window.__filterModalState.title = title;

        const activeValues = (window.__columnFilters && window.__columnFilters[columnKey]) ? window.__columnFilters[columnKey] : [];
        window.__filterModalState.selected = new Set(activeValues);

        const overlay = document.getElementById('column-filter-modal-overlay');
        const titleEl = document.getElementById('logo-filter-title');
        const searchEl = document.getElementById('logo-filter-search');
        const optionsContainer = document.getElementById('logo-filter-options');

        if (!overlay || !titleEl || !searchEl || !optionsContainer) {
            console.error('No se encontró el modal de filtros en el DOM');
            return;
        }

        titleEl.textContent = `Filtrar: ${title}`;
        searchEl.value = '';
        
        // Mostrar cargando
        optionsContainer.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Cargando opciones...</div>';
        overlay.classList.add('active');

        // Columnas que requieren datos de todas las páginas (desde el servidor)
        const serverColumns = ['area', 'encargado', 'estado', 'cliente', 'numero_recibo', 'total_dias'];
        
        let options = [];
        if (serverColumns.includes(columnKey)) {
            try {
                const response = await fetch('/api/recibos-costura/filter-options');
                const data = await response.json();
                if (data.success && data.filter_options) {
                    const mapping = {
                        'area': 'areas',
                        'encargado': 'encargados',
                        'estado': 'estados',
                        'cliente': 'clientes',
                        'numero_recibo': 'numeros_recibo',
                        'total_dias': 'dias_entrega'
                    };
                    const apiKey = mapping[columnKey];
                    options = data.filter_options[apiKey] || [];
                } else {
                    options = getUniqueOptionsForColumn(columnKey);
                }
            } catch (e) {
                console.error('Error fetching filter options:', e);
                options = getUniqueOptionsForColumn(columnKey);
            }
        } else {
            options = getUniqueOptionsForColumn(columnKey);
        }

        window.__filterModalState.options = options.sort((a, b) => String(a).localeCompare(String(b), 'es'));
        renderFilterOptions('');
        setTimeout(() => searchEl.focus(), 50);
    };

    window.closeColumnFilterModal = function(e) {
        if (e && e.target !== document.getElementById('column-filter-modal-overlay')) {
            return;
        }
        const overlay = document.getElementById('column-filter-modal-overlay');
        if (overlay) overlay.classList.remove('active');
    };

    window.resetColumnFilter = function() {
        const key = window.__filterModalState.columnKey;
        if (!key) return;
        window.__columnFilters[key] = [];
        persistFilters();
        updateFilterBadges();
        window.closeColumnFilterModal();
        applyFiltersToTable();
        // Recargar la página con los filtros actualizados
        reloadWithFilters();
    };

    window.applyColumnFilter = function() {
        const key = window.__filterModalState.columnKey;
        if (!key) return;
        const values = Array.from(window.__filterModalState.selected);
        window.__columnFilters[key] = values;
        persistFilters();
        updateFilterBadges();
        window.closeColumnFilterModal();
        // Recargar la página con los filtros aplicados
        reloadWithFilters();
    };

    function reloadWithFilters() {
        const url = new URL(window.location.href);
        
        // Limpiar parámetros de filtro existentes
        url.searchParams.delete('estado');
        url.searchParams.delete('area');
        url.searchParams.delete('total_dias');
        url.searchParams.delete('numero_recibo');
        url.searchParams.delete('cliente');
        url.searchParams.delete('descripcion');
        url.searchParams.delete('cantidad');
        url.searchParams.delete('novedades');
        url.searchParams.delete('fecha_creacion');
        url.searchParams.delete('fecha_estimada');
        url.searchParams.delete('encargado');
        url.searchParams.delete('page');

        // Agregar filtros activos
        const filters = window.__columnFilters || {};
        Object.keys(filters).forEach(key => {
            let values = filters[key] || [];

            if (key === 'total_dias') {
                values = values
                    .map(v => extractNumericDays(v))
                    .filter(v => Number.isInteger(v))
                    .map(v => String(v));
            }

            if (values.length > 0) {
                url.searchParams.set(key, values.join(','));
            }
        });

        window.location.href = url.toString();
    }

    function applyFiltersToTable() {
        const tbody = document.getElementById('tablaRecibosBody');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr');
        const filters = window.__columnFilters || {};
        const filterKeys = Object.keys(filters);

        if (filterKeys.length === 0) {
            // Mostrar todas las filas si no hay filtros
            rows.forEach(row => row.style.display = '');
            return;
        }

        // Mapeo de columnas a índices
        const columnIndexMap = {
            'estado': 1,
            'area': 2,
            'total_dias': 3,
            'numero_recibo': 4,
            'cliente': 5,
            'descripcion': 6,
            'cantidad': 7,
            'novedades': 8,
            'fecha_creacion': 9,
            'fecha_estimada': 10,
            'encargado': 11
        };

        rows.forEach(row => {
            let shouldShow = true;

            filterKeys.forEach(key => {
                const filterValues = filters[key] || [];
                if (filterValues.length === 0) return;

                const colIndex = columnIndexMap[key];
                if (colIndex !== undefined) {
                    const cell = row.cells[colIndex];
                    if (cell) {
                        const cellValue = key === 'total_dias'
                            ? String(extractDaysFromCell(cell) ?? '')
                            : cell.textContent.trim();

                        if (!filterValues.includes(cellValue)) {
                            shouldShow = false;
                        }
                    }
                }
            });

            row.style.display = shouldShow ? '' : 'none';
        });
    }

    function getUniqueOptionsForColumn(columnKey) {
        const set = new Set();
        const tbody = document.getElementById('tablaRecibosBody');
        if (!tbody) return [];

        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            let value = '';
            
            // Mapeo de columnas a índices o selectores
            const columnIndexMap = {
                'estado': 1,
                'area': 2,
                'total_dias': 3,
                'numero_recibo': 4,
                'cliente': 5,
                'descripcion': 6,
                'cantidad': 7,
                'novedades': 8,
                'fecha_creacion': 9,
                'fecha_estimada': 10,
                'encargado': 11
            };

            const colIndex = columnIndexMap[columnKey];
            if (colIndex !== undefined) {
                const cell = row.cells[colIndex];
                if (cell) {
                    value = columnKey === 'total_dias'
                        ? String(extractDaysFromCell(cell) ?? '')
                        : cell.textContent.trim();
                    if (value && value !== '-') {
                        set.add(value);
                    }
                }
            }
        });

        return Array.from(set).sort((a, b) => a.localeCompare(b, 'es'));
    }

    function extractDaysFromCell(cell) {
        if (!cell) return null;
        const txt = cell.textContent || '';
        return extractNumericDays(txt);
    }

    function extractNumericDays(value) {
        if (value === null || value === undefined) return null;
        const match = String(value).match(/-?\d+/);
        if (!match) return null;
        const n = parseInt(match[0], 10);
        return Number.isNaN(n) ? null : n;
    }

    function renderFilterOptions(search) {
        const container = document.getElementById('logo-filter-options');
        const s = (search || '').toLowerCase();
        const opts = (window.__filterModalState.options || []).filter(o => String(o).toLowerCase().includes(s));

        if (!container) return;

        container.innerHTML = opts.map((opt) => {
            const checked = window.__filterModalState.selected.has(opt) ? 'checked' : '';
            return `
                <label class="logo-filter-option">
                    <input type="checkbox" data-filter-option="1" value="${String(opt).replace(/"/g, '&quot;')}" ${checked} />
                    <span>${opt}</span>
                </label>
            `;
        }).join('');

        container.querySelectorAll('input[data-filter-option="1"]').forEach(cb => {
            cb.addEventListener('change', function() {
                const v = this.value;
                if (this.checked) window.__filterModalState.selected.add(v);
                else window.__filterModalState.selected.delete(v);
            });
        });
    }

    const filterSearchEl = document.getElementById('logo-filter-search');
    if (filterSearchEl) {
        filterSearchEl.addEventListener('input', function() {
            renderFilterOptions(this.value);
        });
    }

    // Al cargar, si hay filtros guardados y la URL aun no los tiene,
    // forzar recarga para que el filtrado sea global (servidor), no local por pagina.
    setTimeout(() => {
        updateFilterBadges();
        const filters = window.__columnFilters || {};
        const hasStoredFilters = Object.values(filters).some(arr => Array.isArray(arr) && arr.length > 0);
        if (!hasStoredFilters) return;

        const url = new URL(window.location.href);
        const filterKeys = [
            'estado', 'area', 'total_dias', 'numero_recibo', 'cliente',
            'descripcion', 'cantidad', 'novedades', 'fecha_creacion',
            'fecha_estimada', 'encargado'
        ];

        const hasAnyFilterInUrl = filterKeys.some(key => {
            const value = (url.searchParams.get(key) || '').trim();
            return value !== '';
        });

        if (!hasAnyFilterInUrl) {
            reloadWithFilters();
        }
    }, 100);
});
