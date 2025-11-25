/**
 * Script de integración de variantes de prendas
 * Maneja la integración de variantes seleccionadas dinámicamente
 */

document.addEventListener('DOMContentLoaded', function() {
    // Agregar listener a inputs existentes
    document.querySelectorAll('.prenda-search-input').forEach(input => {
        input.addEventListener('change', function() {
            mostrarSelectorVariantes(this);
        });
    });

    // Observar cambios en el DOM para nuevas prendas agregadas
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('producto-card')) {
                        // Nueva prenda agregada
                        const input = node.querySelector('.prenda-search-input');
                        if (input) {
                            input.addEventListener('change', function() {
                                mostrarSelectorVariantes(this);
                            });
                        }
                    }
                });
            }
        });
    });

    observer.observe(document.getElementById('productosContainer'), {
        childList: true,
        subtree: true
    });
});
