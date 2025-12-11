(function() {
    'use strict';

    function applyRowConditionalColors(row) {
        if (!row) return;

        const estado = row.getAttribute('data-estado') || '';
        const totalDias = parseInt(row.getAttribute('data-total-dias')) || 0;

        row.classList.remove('status-entregado', 'status-anulada', 'dias-5-9', 'dias-10-15', 'dias-mayor-15');

        if (estado === 'Entregado') {
            row.classList.add('status-entregado');
        } else if (estado === 'Anulada') {
            row.classList.add('status-anulada');
        } else {
            if (totalDias >= 5 && totalDias <= 9) {
                row.classList.add('dias-5-9');
            } else if (totalDias >= 10 && totalDias <= 15) {
                row.classList.add('dias-10-15');
            } else if (totalDias > 15) {
                row.classList.add('dias-mayor-15');
            }
        }
    }

    function applyAllRowConditionalColors() {
        const rows = document.querySelectorAll('.table-row');
        rows.forEach(row => applyRowConditionalColors(row));
    }

    function updateRowConditionalColors() {
        applyAllRowConditionalColors();
    }

    document.addEventListener('DOMContentLoaded', function() {
        applyAllRowConditionalColors();

        const tableBody = document.getElementById('tablaOrdenesBody');
        if (tableBody) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        applyAllRowConditionalColors();
                    }
                });
            });

            observer.observe(tableBody, {
                childList: true,
                subtree: true
            });
        }

        document.addEventListener('estadoChanged', function(e) {
            const row = e.detail?.row || document.querySelector(`[data-order-id="${e.detail?.ordenId}"]`);
            if (row) {
                row.setAttribute('data-estado', e.detail?.nuevoEstado || '');
                applyRowConditionalColors(row);
            }
        });
    });

    window.updateRowConditionalColors = updateRowConditionalColors;
    window.applyRowConditionalColors = applyRowConditionalColors;
    window.applyAllRowConditionalColors = applyAllRowConditionalColors;
})();
