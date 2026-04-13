(function () {
    'use strict';

    const MAX_ITEMS = 15;
    const MIN_QUERY_LENGTH = 1;

    function debounce(fn, waitMs) {
        let timeoutId;

        return function debounced(...args) {
            if (timeoutId) {
                clearTimeout(timeoutId);
            }

            timeoutId = setTimeout(() => {
                fn.apply(this, args);
            }, waitMs);
        };
    }

    function renderDatalist(datalist, clientes) {
        if (!datalist) {
            return;
        }

        datalist.innerHTML = '';

        clientes.forEach((cliente) => {
            const option = document.createElement('option');
            option.value = cliente.nombre;
            datalist.appendChild(option);
        });
    }

    async function fetchClientes(query) {
        const url = new URL('/api/asesores/clientes/autocomplete', window.location.origin);
        url.searchParams.set('q', query);
        url.searchParams.set('limit', String(MAX_ITEMS));

        const response = await fetch(url.toString(), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            return [];
        }

        const payload = await response.json();

        if (!payload || payload.success !== true || !Array.isArray(payload.clientes)) {
            return [];
        }

        return payload.clientes
            .filter((item) => item && typeof item.nombre === 'string' && item.nombre.trim() !== '');
    }

    function setupClienteAutocomplete() {
        const input = document.getElementById('cliente_editable');

        if (!input) {
            return;
        }

        const listId = 'clientes-editable-list';
        let datalist = document.getElementById(listId);

        if (!datalist) {
            datalist = document.createElement('datalist');
            datalist.id = listId;
            input.insertAdjacentElement('afterend', datalist);
        }

        input.setAttribute('list', listId);

        const loadSuggestions = debounce(async () => {
            const query = (input.value || '').trim();

            if (query.length < MIN_QUERY_LENGTH) {
                renderDatalist(datalist, []);
                return;
            }

            try {
                const clientes = await fetchClientes(query);
                renderDatalist(datalist, clientes);
            } catch (error) {
                console.warn('[cliente-autocomplete] No se pudieron cargar sugerencias', error);
            }
        }, 180);

        input.addEventListener('input', loadSuggestions);
        input.addEventListener('focus', loadSuggestions);
    }

    window.InitializeClienteAutocomplete = setupClienteAutocomplete;

    document.addEventListener('DOMContentLoaded', function () {
        setupClienteAutocomplete();
    });
})();
