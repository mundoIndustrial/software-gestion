/**
 * TELAS - Gestión de telas en Prenda Sin Cotización
 * 
 * Funciones para:
 * - Agregar telas
 * - Eliminar telas
 * - Eliminar imágenes de telas
 */

/**
 * Agregar una tela a una prenda
 * @param {number} prendaIndex - Índice de la prenda
 */
window.agregarTelaPrendaTipo = function(prendaIndex) {
    let selectedFiles = [];
    
    Swal.fire({
        title: 'Agregar Tela',
        html: `
            <form>
                <div style="margin-bottom: 1rem; text-align: left;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Nombre de la Tela:</label>
                    <input id="nombre-tela" type="text" placeholder="Ej: Algodón, Poliéster" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem; text-align: left;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Color:</label>
                    <input id="color-tela" type="text" placeholder="Ej: Rojo, Azul" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem; text-align: left;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Referencia:</label>
                    <input id="referencia-tela" type="text" placeholder="Ej: REF-001" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem; text-align: left;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Imágenes (opcional):
                        <span style="font-weight: normal; color: #6b7280; font-size: 0.85rem;">Agrega una o varias imágenes</span>
                    </label>
                    <button type="button" id="btn-agregar-imagenes" style="width: 100%; padding: 0.75rem; background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <span>📷</span>
                        <span>Agregar Imágenes</span>
                    </button>
                    <input id="imagenes-tela" type="file" accept="image/*" multiple style="display: none;">
                    <div id="contador-imagenes" style="margin-top: 0.5rem; font-size: 0.85rem; color: #0066cc; font-weight: 600;"></div>
                    <div id="preview-imagenes" style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;"></div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: (modal) => {
            document.getElementById('nombre-tela').focus();
            
            // Manejar selección de imágenes
            const inputImagenes = document.getElementById('imagenes-tela');
            const btnAgregarImagenes = document.getElementById('btn-agregar-imagenes');
            const previewContainer = document.getElementById('preview-imagenes');
            const contadorImagenes = document.getElementById('contador-imagenes');
            
            // Botón para abrir selector de archivos
            btnAgregarImagenes.addEventListener('click', () => {
                inputImagenes.click();
            });
            
            // Acumular imágenes seleccionadas
            inputImagenes.addEventListener('change', (e) => {
                const newFiles = Array.from(e.target.files);
                
                // Agregar nuevos archivos a los existentes (acumular)
                newFiles.forEach(file => {
                    // Verificar que no esté duplicado
                    const existe = selectedFiles.some(f => f.name === file.name && f.size === file.size);
                    if (!existe) {
                        selectedFiles.push(file);
                    }
                });
                
                // Actualizar contador
                if (selectedFiles.length > 0) {
                    contadorImagenes.textContent = `✓ ${selectedFiles.length} imagen${selectedFiles.length !== 1 ? 'es' : ''} seleccionada${selectedFiles.length !== 1 ? 's' : ''}`;
                } else {
                    contadorImagenes.textContent = '';
                }
                
                // Re-renderizar todas las previews
                previewContainer.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const preview = document.createElement('div');
                        preview.style.cssText = 'position: relative; width: 60px; height: 60px;';
                        preview.innerHTML = `
                            <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 2px solid #0066cc;">
                            <div style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer;" data-index="${index}">×</div>
                        `;
                        
                        // Agregar evento para eliminar imagen individual
                        const btnEliminar = preview.querySelector('[data-index]');
                        btnEliminar.addEventListener('click', () => {
                            selectedFiles.splice(index, 1);
                            preview.remove();
                            // Actualizar contador
                            if (selectedFiles.length > 0) {
                                contadorImagenes.textContent = `✓ ${selectedFiles.length} imagen${selectedFiles.length !== 1 ? 'es' : ''} seleccionada${selectedFiles.length !== 1 ? 's' : ''}`;
                            } else {
                                contadorImagenes.textContent = '';
                            }
                        });
                        
                        previewContainer.appendChild(preview);
                    };
                    reader.readAsDataURL(file);
                });
                
                // Resetear input para permitir seleccionar el mismo archivo de nuevo
                inputImagenes.value = '';
            });
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const nombreTela = document.getElementById('nombre-tela').value;
            const color = document.getElementById('color-tela').value;
            const referencia = document.getElementById('referencia-tela').value;

            if (nombreTela) {
                // Agregar la tela al gestor
                window.gestorPrendaSinCotizacion.agregarTela(prendaIndex, {
                    nombre_tela: nombreTela,
                    color: color,
                    referencia: referencia
                });
                
                // Obtener el índice de la tela recién agregada
                const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
                const telaIndex = (prenda?.variantes?.telas_multiples?.length || 1) - 1;
                
                // Si hay imágenes seleccionadas, subirlas
                if (selectedFiles.length > 0) {
                    console.log(`📸 Subiendo ${selectedFiles.length} imagen(es) para la tela...`);
                    
                    try {
                        Swal.fire({
                            title: 'Subiendo imágenes...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                        
                        // Subir cada imagen
                        for (const file of selectedFiles) {
                            if (file.type.startsWith('image/')) {
                                const imageData = await window.ImageService.uploadTelaImage(file, prendaIndex, telaIndex, null);
                                
                                // Guardar en el gestor
                                if (!window.gestorPrendaSinCotizacion.telasFotosNuevas) {
                                    window.gestorPrendaSinCotizacion.telasFotosNuevas = {};
                                }
                                if (!window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex]) {
                                    window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex] = {};
                                }
                                if (!window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex]) {
                                    window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex] = [];
                                }
                                
                                window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex].push({
                                    url: imageData.url,
                                    ruta_webp: imageData.ruta_webp,
                                    ruta_original: imageData.ruta_original,
                                    thumbnail: imageData.thumbnail,
                                    tela_id: imageData.tela_id,
                                    isNew: true
                                });
                                
                                console.log(`✅ Imagen subida: ${imageData.filename}`);
                            }
                        }
                        
                        Swal.close();
                    } catch (error) {
                        console.error('❌ Error al subir imágenes:', error);
                        Swal.fire('Advertencia', 'Tela agregada pero hubo un error al subir algunas imágenes', 'warning');
                    }
                }
                
                // Re-renderizar la sección de telas
                if (prenda && typeof window.renderizarTelasPrendaTipo === 'function') {
                    const container = document.querySelector(`[data-prenda-index="${prendaIndex}"]`);
                    if (container) {
                        const telasSection = container.querySelector('[data-section="telas"]');
                        if (telasSection) {
                            telasSection.innerHTML = window.renderizarTelasPrendaTipo(prenda, prendaIndex);
                            console.log('✅ Sección de telas actualizada con nueva tela y fotos');
                        }
                    }
                }
                
                const mensaje = selectedFiles.length > 0 
                    ? `Tela agregada con ${selectedFiles.length} imagen(es)` 
                    : 'Tela agregada correctamente';
                Swal.fire('Éxito', mensaje, 'success');
            } else {
                Swal.fire('Error', 'Ingrese el nombre de la tela', 'error');
            }
        }
    });
};

/**
 * Eliminar una tela de una prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} telaIndex - Índice de la tela
 */
window.eliminarTelaPrendaTipo = function(prendaIndex, telaIndex) {
    Swal.fire({
        title: '¿Eliminar Tela?',
        text: '¿Está seguro que desea eliminar esta tela?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.gestorPrendaSinCotizacion.eliminarTela(prendaIndex, telaIndex);
            window.renderizarPrendasTipoPrendaSinCotizacion();
            Swal.fire('Eliminada', 'La tela ha sido eliminada', 'success');
        }
    });
};

/**
 * Eliminar imagen de tela
 * @param {HTMLElement} element - Elemento del botón
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} telaIndex - Índice de la tela
 */
window.eliminarImagenTelaTipo = function(element, prendaIndex, telaIndex) {
    Swal.fire({
        title: '¿Eliminar Imagen?',
        text: '¿Está seguro que desea eliminar esta imagen de tela?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            console.log(`🗑️ Eliminando foto de tela - Prenda: ${prendaIndex}, Tela: ${telaIndex}`);
            
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
            if (prenda) {
                const tela = prenda.telas && prenda.telas[telaIndex];
                
                // ✅ OPCIÓN 1: Limpiar telaFotos (fotos guardadas del servidor)
                if (tela && prenda.telaFotos && Array.isArray(prenda.telaFotos)) {
                    console.log(`   Antes: telaFotos.length = ${prenda.telaFotos.length}`);
                    prenda.telaFotos = [];  // Vaciar completamente
                    console.log(`   Después: telaFotos.length = ${prenda.telaFotos.length}`);
                }
                
                // ✅ OPCIÓN 2: Limpiar fotos nuevas de tela
                const keyFotosNuevas = `${prendaIndex}_${telaIndex}`;
                if (window.gestorPrendaSinCotizacion.telasFotosNuevas && 
                    window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex]) {
                    window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex] = [];
                    console.log(`   Fotos nuevas de tela limpiadas`);
                }
            }
            
            // ✅ Solo re-renderizar la sección de TELAS (no toda la prenda)
            const container = document.querySelector(`[data-prenda-index="${prendaIndex}"]`);
            if (container) {
                const telasSection = container.querySelector('[data-section="telas"]');
                if (telasSection) {
                    const telasHtml = window.renderizarTelasPrendaTipo(prenda, prendaIndex);
                    telasSection.innerHTML = telasHtml;
                    logWithEmoji('🗑️', `Foto de tela eliminada, sección actualizada`);
                }
            }
            
            Swal.fire('Eliminada', 'La imagen de tela ha sido eliminada', 'success');
        }
    });
};

console.log('✅ [TELAS] Componente prenda-sin-cotizacion-telas.js cargado');
