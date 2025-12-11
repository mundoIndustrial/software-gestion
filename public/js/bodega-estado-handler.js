(function() {
    'use strict';

    function updateRowColorOnStateChange(pedidoId, nuevoEstado) {
        const row = document.querySelector(`[data-order-id="${pedidoId}"]`);
        if (!row) return;

        row.setAttribute('data-estado', nuevoEstado);
        
        if (typeof window.applyRowConditionalColors === 'function') {
            window.applyRowConditionalColors(row);
        } else if (typeof applyAllRowConditionalColors === 'function') {
            applyAllRowConditionalColors();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.getElementById('tablaOrdenesBody');
        if (!tableBody) return;

        tableBody.addEventListener('change', function(e) {
            const estadoDropdown = e.target.closest('.estado-dropdown');
            if (estadoDropdown) {
                const pedidoId = estadoDropdown.getAttribute('data-id');
                const nuevoEstado = estadoDropdown.value;
                
                setTimeout(() => {
                    updateRowColorOnStateChange(pedidoId, nuevoEstado);
                }, 100);
            }
        });
    });

    window.updateRowColorOnStateChange = updateRowColorOnStateChange;
})();
