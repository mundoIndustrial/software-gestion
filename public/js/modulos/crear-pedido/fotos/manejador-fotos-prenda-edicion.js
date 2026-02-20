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
 *  NUEVO: Manejar archivos de fotos de prenda en modal de edición
 * Agrega las fotos al storage SIN limpiar las existentes
 */
window.manejarArchivosFotosPrenda = function(archivos, prendaIndex) {



    
    if (!archivos || archivos.length === 0) {

        return;
    }
    
    //  CRÍTICO: NO limpiar el storage. Solo agregar las nuevas imágenes
    if (!window.imagenesPrendaStorage) {

        return;
    }
    
    Array.from(archivos).forEach((file, index) => {
        window.imagenesPrendaStorage.agregarImagen(file)
            .then(() => {
                console.log(`[manejadorFotosPrendaEdicion] ✅ Imagen ${index + 1} agregada`);
            })
            .catch(err => {
                console.error(`[manejadorFotosPrendaEdicion] ❌ Error agregando imagen ${index + 1}:`, err);
            });
    });
    
    // Actualizar preview
    if (window.actualizarPreviewPrenda) {
        window.actualizarPreviewPrenda();

    }
};

/**
 *  NUEVO: Manejar archivos de fotos de tela en modal de edición
 * Agrega las fotos de tela al array sin limpiar las existentes
 */
window.manejarArchivosFotosTela = function(archivos, prendaIndex, telaIndex) {




    
    if (!archivos || archivos.length === 0) {

        return;
    }
    
    //  CRÍTICO: NO limpiar telasAgregadas. Solo agregar las nuevas imágenes a la tela
    if (!window.telasAgregadas || !window.telasAgregadas[telaIndex]) {

        return;
    }
    
    const tela = window.telasAgregadas[telaIndex];
    
    if (!tela.imagenes) {
        tela.imagenes = [];
    }
    
    Array.from(archivos).forEach((file, index) => {

        
        // Crear blob URL para preview
        const blobUrl = URL.createObjectURL(file);
        
        // Agregar a las imágenes de la tela
        tela.imagenes.push({
            file: file,
            nombre: file.name,
            tamaño: file.size,
            blobUrl: blobUrl
        });
        

    });
    

    
    // Actualizar preview de tela
    if (window.actualizarPreviewTela) {
        window.actualizarPreviewTela();

    }
    
    // Actualizar tabla de telas
    if (window.actualizarTablaTelas) {
        window.actualizarTablaTelas();

    }
};


