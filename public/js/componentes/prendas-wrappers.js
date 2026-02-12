/**
 * ================================================
 * PRENDAS WRAPPERS - COMPATIBILITY LAYER
 * ================================================
 * 
 * Este archivo mantiene compatibilidad con el sistema existente
 * mientras carga la nueva arquitectura modular
 * 
 * @deprecated Usar prendas-wrappers-v2.js para nuevas implementaciones
 */

// Evitar cargas múltiples del script
if (window.prendaWrappersCargado) {
    console.log('[prendas-wrappers] ⚠️ Script ya cargado, evitando duplicación');
} else {
    window.prendaWrappersCargado = true;
    
    // Cargar el nuevo sistema modular de forma síncrona para garantizar disponibilidad
    const prendaScript = document.createElement('script');
    prendaScript.src = '/js/componentes/prendas-module/prendas-wrappers-v2.js';
    prendaScript.async = false; // Carga síncrona para garantizar disponibilidad
    document.head.appendChild(prendaScript);

// Definir funciones básicas inmediatamente (sin setTimeout)
window.abrirModalPrendaNueva = function() {
    console.log('[prendas-wrappers] Abriendo modal de prenda nueva');
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        modal.style.display = 'flex';
        // Limpiar formulario al abrir
        const form = modal.querySelector('#form-prenda-nueva');
        if (form) {
            form.reset();
        }
    } else {
        console.warn('[prendas-wrappers] Modal no encontrado');
    }
};

window.cerrarModalPrendaNueva = function() {
    console.log('[prendas-wrappers] Cerrando modal de prenda nueva');
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.manejarImagenesPrenda = function(input) {
    if (input.files && input.files.length > 0) {
        console.log('📸 Archivo recibido:', input.files[0].name);
        // Lógica básica de manejo de imágenes
        const preview = document.getElementById('nueva-prenda-foto-preview');
        if (preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
};

// Escuchar cuando el módulo completo cargue para reemplazar con funciones avanzadas
document.addEventListener('prendasModuleLoaded', (event) => {
    const exported = event.detail;
    console.log('[prendas-wrappers] 📦 Módulo prendas cargado, actualizando funciones globales...');
    
    // Reemplazar funciones básicas con las avanzadas del módulo
    if (typeof exported.cerrarModalPrendaNueva === 'function') {
        window.cerrarModalPrendaNueva = exported.cerrarModalPrendaNueva;
        console.log('  cerrarModalPrendaNueva actualizada con función avanzada');
    }
    
    if (typeof exported.manejarImagenesPrenda === 'function') {
        window.manejarImagenesPrenda = exported.manejarImagenesPrenda;
        console.log('  manejarImagenesPrenda actualizada con función avanzada');
    }
    
    if (typeof exported.agregarPrendaNueva === 'function') {
        window.agregarPrendaNueva = exported.agregarPrendaNueva;
        console.log('  agregarPrendaNueva asignada');
    }
    
    if (typeof exported.cargarItemEnModal === 'function') {
        window.cargarItemEnModal = exported.cargarItemEnModal;
        console.log('  cargarItemEnModal asignada');
    }
    
    console.log('[prendas-wrappers] ✅ Funciones globales actualizadas correctamente');
});

} // Cierre del bloque else de prendaWrappersCargado

