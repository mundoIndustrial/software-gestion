// Ocultar loading cuando los CSS lazy-loaded se hayan cargado
document.addEventListener('DOMContentLoaded', function() {
    // Pequeño delay para asegurar que todo esté listo
    setTimeout(function() {
        const loadingSpinner = document.getElementById('loadingSpinner');
        if (loadingSpinner) {
            loadingSpinner.style.transition = 'opacity 0.3s ease-out';
            loadingSpinner.style.opacity = '0';
            setTimeout(() => {
                loadingSpinner.style.display = 'none';
            }, 300);
        }
    }, 500);
});

// Asegurar que se oculte cuando la ventana esté completamente cargada
window.addEventListener('load', function() {
    const loadingSpinner = document.getElementById('loadingSpinner');
    if (loadingSpinner && loadingSpinner.style.display !== 'none') {
        loadingSpinner.style.transition = 'opacity 0.3s ease-out';
        loadingSpinner.style.opacity = '0';
        setTimeout(() => {
            loadingSpinner.style.display = 'none';
        }, 300);
    }
});
