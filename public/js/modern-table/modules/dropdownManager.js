/**
 * ModernTableDropdownManager
 * Responsabilidad: Gestionar dropdowns SOLO para ModernTable (estado)
 * SOLID: Single Responsibility
 * NOTA: Los dropdowns de área son manejados por OrdersDropdownManager
 */
const ModernTableDropdownManager = (() => {
    return {
        // Inicializar dropdowns de estado
        initializeStatusDropdowns: (callback) => {
            document.querySelectorAll('.estado-dropdown').forEach(dropdown => {
                const currentValue = dropdown.value;
                const newDropdown = dropdown.cloneNode(true);
                dropdown.parentNode.replaceChild(newDropdown, dropdown);
                
                newDropdown.value = currentValue;
                newDropdown.addEventListener('change', e => callback(e.target));
            });
        },

        // NOTA: initializeAreaDropdowns se eliminó - ahora lo maneja OrdersDropdownManager

        // Actualizar estado de orden
        updateOrderStatus: async (dropdown, baseRoute) => {
            const orderId = dropdown.dataset.id;
            const newStatus = dropdown.value;
            const oldStatus = dropdown.dataset.value;

            try {
                const response = await fetch(`${baseRoute}/${orderId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ estado: newStatus })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                if (data.success) {

                    dropdown.dataset.value = newStatus;
                } else {

                    dropdown.value = oldStatus;
                }
            } catch (error) {

                alert(`Error al actualizar el estado: ${error.message}`);
                dropdown.value = oldStatus;
            }
        }
    };
})();

// NO exponer como DropdownManager global para evitar conflictos
globalThis.ModernTableDropdownManager = ModernTableDropdownManager;

