(function () {
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function parseUrlFilterValues(columna, href) {
        const url = new URL(href, window.location.origin);
        const raw = url.searchParams.get(columna) || '';
        if (!raw) return [];
        if (columna === 'fecha_creacion') return [raw];
        return raw.split(',').map((v) => v.trim()).filter(Boolean);
    }

    function create(config) {
        let filtroActual = null;
        const {
            contentSelector,
            openButtonSelector,
            filterOptionsEndpoint,
            navigate,
            titleMap = {},
        } = config;

        function refreshIndicators() {
            document.querySelectorAll('.btn-filter-column').forEach((btn) => {
                const col = btn.getAttribute('data-col');
                const valores = col ? parseUrlFilterValues(col, window.location.href) : [];
                const cantidad = valores.length;
                let badge = btn.querySelector('.filter-badge');

                if (cantidad > 0) {
                    btn.classList.add('has-filter');
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'filter-badge';
                        btn.appendChild(badge);
                    }
                    badge.textContent = String(cantidad);
                } else {
                    btn.classList.remove('has-filter');
                    if (badge) badge.remove();
                }
            });

            window.dispatchEvent(new Event('supervisorPedidos:filtersUpdated'));
        }

        function close() {
            const modal = document.getElementById('modalFiltro');
            if (modal) modal.style.display = 'none';
            filtroActual = null;
        }

        function clearCurrent() {
            if (!filtroActual) return;
            const url = new URL(window.location.href);
            url.searchParams.delete(filtroActual);
            close();
            navigate(url.toString());
        }

        function apply(event) {
            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }
            if (!filtroActual) return;

            const url = new URL(window.location.href);
            if (filtroActual === 'fecha_creacion') {
                url.searchParams.delete('fecha_creacion');
                const fecha = document.getElementById('filtroFecha')?.value;
                if (fecha) url.searchParams.set('fecha_creacion', fecha);
            } else {
                const checkboxes = document.querySelectorAll('.filtro-checkbox:checked');
                const values = Array.from(checkboxes).map((cb) => cb.value);
                url.searchParams.delete(filtroActual);
                if (values.length > 0) {
                    url.searchParams.set(filtroActual, values.join(','));
                }
            }

            close();
            navigate(url.toString());
        }

        function open(columna) {
            filtroActual = columna;
            const modal = document.getElementById('modalFiltro');
            const modalTitulo = document.getElementById('modalFiltroTitulo');
            const filtroContenido = document.getElementById('filtroContenido');
            if (!modal || !modalTitulo || !filtroContenido) return;

            modalTitulo.textContent = titleMap[columna] || 'Filtrar';

            if (columna === 'fecha_creacion') {
                const actual = (parseUrlFilterValues('fecha_creacion', window.location.href)[0] || '');
                filtroContenido.innerHTML = `
                    <div class="form-group">
                        <label for="filtroFecha" style="display:block; margin-bottom:0.5rem;">Fecha (YYYY-MM-DD)</label>
                        <input type="date" id="filtroFecha" class="form-control" value="${actual}">
                    </div>
                `;
                modal.style.display = 'flex';
                return;
            }

            const endpoint = filterOptionsEndpoint(columna);
            filtroContenido.innerHTML = '<p style="color:#6b7280;">Cargando...</p>';

            fetch(endpoint)
                .then((r) => r.json())
                .then((data) => {
                    const opciones = Array.isArray(data.opciones) ? data.opciones : [];
                    const seleccionados = new Set(parseUrlFilterValues(columna, window.location.href));

                    filtroContenido.innerHTML = `
                        <div class="form-group">
                            <input type="text" id="buscadorFiltro" class="form-control" placeholder="Buscar..." style="margin-bottom: 1rem;" />
                            <div id="listaOpciones" style="max-height: 300px; overflow-y: auto;">
                                ${opciones.map((opcion) => {
                                    const safeValue = (opcion === null || opcion === undefined) ? '' : String(opcion);
                                    const label = safeValue || '(Sin especificar)';
                                    const checked = seleccionados.has(safeValue) ? 'checked' : '';
                                    return `
                                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                            <input type="checkbox" class="filtro-checkbox" value="${escapeHtml(safeValue)}" ${checked} />
                                            <span>${escapeHtml(label)}</span>
                                        </label>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    `;

                    setTimeout(() => {
                        document.getElementById('buscadorFiltro')?.addEventListener('input', function (e) {
                            const valor = e.target.value.toLowerCase();
                            document.querySelectorAll('#listaOpciones label').forEach((label) => {
                                const texto = label.textContent.toLowerCase();
                                label.style.display = texto.includes(valor) ? 'flex' : 'none';
                            });
                        });
                    }, 0);

                    modal.style.display = 'flex';
                })
                .catch(() => {
                    filtroContenido.innerHTML = '<p style="color: red;">Error cargando opciones de filtro</p>';
                    modal.style.display = 'flex';
                });
        }

        function bindUi() {
            document.addEventListener('click', function (e) {
                const btn = e.target.closest(openButtonSelector || `${contentSelector} .btn-filter-column`);
                if (!btn) return;
                const col = btn.getAttribute('data-col');
                if (!col) return;
                e.preventDefault();
                open(col);
            });

            const overlay = document.getElementById('modalFiltro');
            if (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) close();
                });
            }
        }

        return {
            bindUi,
            open,
            close,
            apply,
            clearCurrent,
            refreshIndicators,
        };
    }

    window.SupervisorReceiptsApiFilters = { create };
})();
