/**
 * MANEJADOR DE FOTOS PARA EDICIÓN DE PRENDAS
 * 
 * Funciones para agregar fotos durante la edición:
 * - Agregar fotos a prenda SIN limpiar las existentes
 * - Agregar fotos a tela SIN limpiar las existentes
 * 
 * Esto permite que durante la edición se agreguen nuevas fotos sin perder las existentes.
 */

/**
 * ✅ NUEVO: Manejar archivos de fotos de prenda en modal de edición
 * Agrega las fotos al storage SIN limpiar las existentes
 */
window.manejarArchivosFotosPrenda = function(archivos, prendaIndex) {
    console.log('📷 [FOTOS-EDICIÓN] manejarArchivosFotosPrenda() llamado');
    console.log('   Archivos:', archivos.length);
    console.log('   prendaIndex:', prendaIndex);
    
    if (!archivos || archivos.length === 0) {
        console.warn('⚠️  [FOTOS-EDICIÓN] No hay archivos seleccionados');
        return;
    }
    
    // ✅ CRÍTICO: NO limpiar el storage. Solo agregar las nuevas imágenes
    if (!window.imagenesPrendaStorage) {
        console.error('❌ [FOTOS-EDICIÓN] imagenesPrendaStorage no disponible');
        return;
    }
    
    Array.from(archivos).forEach((file, index) => {
        console.log(`   Procesando archivo ${index + 1}:`, file.name);
        window.imagenesPrendaStorage.agregarImagen(file);
        console.log(`   ✅ Imagen agregada al storage:`, file.name);
    });
    
    // Actualizar preview
    if (window.actualizarPreviewPrenda) {
        window.actualizarPreviewPrenda();
        console.log('   ✅ Preview actualizado');
    }
};

/**
 * ✅ NUEVO: Manejar archivos de fotos de tela en modal de edición
 * Agrega las fotos de tela al array sin limpiar las existentes
 */
window.manejarArchivosFotosTela = function(archivos, prendaIndex, telaIndex) {
    console.log('📷 [FOTOS-EDICIÓN] manejarArchivosFotosTela() llamado');
    console.log('   Archivos:', archivos.length);
    console.log('   prendaIndex:', prendaIndex);
    console.log('   telaIndex:', telaIndex);
    
    if (!archivos || archivos.length === 0) {
        console.warn('⚠️  [FOTOS-EDICIÓN] No hay archivos seleccionados');
        return;
    }
    
    // ✅ CRÍTICO: NO limpiar telasAgregadas. Solo agregar las nuevas imágenes a la tela
    if (!window.telasAgregadas || !window.telasAgregadas[telaIndex]) {
        console.error('❌ [FOTOS-EDICIÓN] Tela no encontrada en índice:', telaIndex);
        return;
    }
    
    const tela = window.telasAgregadas[telaIndex];
    
    if (!tela.imagenes) {
        tela.imagenes = [];
    }
    
    Array.from(archivos).forEach((file, index) => {
        console.log(`   Procesando archivo ${index + 1}:`, file.name);
        
        // Crear blob URL para preview
        const blobUrl = URL.createObjectURL(file);
        
        // Agregar a las imágenes de la tela
        tela.imagenes.push({
            file: file,
            nombre: file.name,
            tamaño: file.size,
            blobUrl: blobUrl
        });
        
        console.log(`   ✅ Imagen de tela agregada:`, file.name);
    });
    
    console.log('   Total imágenes de tela ahora:', tela.imagenes.length);
    
    // Actualizar preview de tela
    if (window.actualizarPreviewTela) {
        window.actualizarPreviewTela();
        console.log('   ✅ Preview de tela actualizado');
    }
    
    // Actualizar tabla de telas
    if (window.actualizarTablaTelas) {
        window.actualizarTablaTelas();
        console.log('   ✅ Tabla de telas actualizada');
    }
};

console.log('✅ [FOTOS-EDICIÓN] Módulo manejador-fotos-prenda-edicion.js cargado');
