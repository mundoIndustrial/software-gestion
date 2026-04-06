// Agregar listener para detectar clicks en el backdrop (fondo oscuro)
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalAgregarEPP');
    if (!modal) {
        console.warn('[Modal EPP] Elemento modal no encontrado');
        return;
    }
    
    // Listener en el backdrop (el div con fixed inset-0)
    modal.addEventListener('click', function(event) {
        // Verificar si el click fue en el backdrop, no en el contenedor blanco
        const contenedorBlanco = modal.querySelector('.bg-white');
        if (contenedorBlanco && !contenedorBlanco.contains(event.target)) {
            console.log('[Modal EPP] Click detectado en backdrop');
            cerrarModalAgregarEPP();
        }
    });
    
    console.log('[Modal EPP] Listener de backdrop agregado');
});
