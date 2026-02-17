// Script de diagnóstico para eliminación de imágenes
// Usar en consola del navegador del VPS para identificar problemas

console.log('🔍 DIAGNÓSTICO DE ELIMINACIÓN DE IMÁGENES');

// 1. Verificar variables globales
console.log('📊 Variables globales:', {
    modoEdicion: window.modoEdicion,
    pedidoEditarId: window.pedidoEditarId,
    pedidoEditarData: !!window.pedidoEditarData,
    imagenesPrendaStorage: !!window.imagenesPrendaStorage,
    imagenesProcesoExistentes: !!window.imagenesProcesoExistentes,
    imagenesProcesoActual: !!window.imagenesProcesoActual
});

// 2. Verificar funciones disponibles
console.log('🔧 Funciones disponibles:', {
    confirmarEliminarImagenProceso: typeof window.confirmarEliminarImagenProceso,
    eliminarImagenActual: typeof window.eliminarImagenActual,
    actualizarPreviewPrenda: typeof window.actualizarPreviewPrenda
});

// 3. Verificar si los archivos JavaScript cargaron correctamente
const scripts = [
    'PrendaDragDropHandler.js',
    'gestor-modal-proceso-generico.js',
    'image-management.js'
];

scripts.forEach(script => {
    const scriptElement = document.querySelector(`script[src*="${script}"]`);
    console.log(`📄 ${script}:`, {
        loaded: !!scriptElement,
        src: scriptElement?.src,
        version: scriptElement?.src?.match(/v=([^&]+)/)?.[1] || 'no version'
    });
});

// 4. Simular eliminación de imagen
console.log('🧪 Simulando eliminación de imagen...');
if (window.imagenesProcesoActual && window.imagenesProcesoActual.length > 0) {
    console.log('✅ Hay imágenes en el storage');
    console.log('📸 Imágenes:', window.imagenesProcesoActual);
} else {
    console.log('❌ No hay imágenes en el storage');
}

// 5. Verificar eventos click en botones de eliminar
document.querySelectorAll('[onclick*="eliminar"], [onclick*="delete"]').forEach((btn, index) => {
    console.log(`🔘 Botón eliminar ${index + 1}:`, {
        text: btn.textContent.trim(),
        onclick: btn.getAttribute('onclick'),
        id: btn.id
    });
});

console.log('🏁 FIN DEL DIAGNÓSTICO');
