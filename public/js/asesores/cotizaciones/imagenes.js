/**
 * SISTEMA DE COTIZACIONES - GESTIÓN DE IMÁGENES
 * Responsabilidad: Drag & drop, preview y gestión de imágenes generales
 */

let archivosAcumulados = [];

// ============ DRAG AND DROP ============

function configurarDragAndDrop() {
    const dropZone = document.getElementById('drop_zone_imagenes');
    const fileInput = document.getElementById('imagenes_bordado');
    if (!dropZone || !fileInput) return;
    
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.style.background = '#e3f2fd';
        dropZone.style.borderColor = '#2196F3';
    });
    dropZone.addEventListener('dragleave', (e) => {
        e.stopPropagation();
        dropZone.style.background = '#f0f7ff';
        dropZone.style.borderColor = '#3498db';
    });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.style.background = '#f0f7ff';
        dropZone.style.borderColor = '#3498db';
        agregarImagenes(e.dataTransfer.files);
    });
    fileInput.addEventListener('change', function() {
        agregarImagenes(this.files);
    });
}

// ============ AGREGAR IMÁGENES ============

function agregarImagenes(newFiles) {
    const newFilesArray = Array.from(newFiles);
    if (archivosAcumulados.length + newFilesArray.length > 5) {
        alert('Máximo 5 imágenes permitidas');
        return;
    }
    archivosAcumulados = archivosAcumulados.concat(newFilesArray);
    
    // Guardar en memoria
    newFilesArray.forEach(file => {
        window.imagenesEnMemoria.logo.push(file);
        console.log(`✅ Imagen guardada en memoria: ${file.name}`);
    });
    
    const dt = new DataTransfer();
    archivosAcumulados.forEach(f => dt.items.add(f));
    document.getElementById('imagenes_bordado').files = dt.files;
    mostrarImagenes(archivosAcumulados);
}

// ============ MOSTRAR IMÁGENES ============

function mostrarImagenes(files) {
    const galeria = document.getElementById('galeria_imagenes');
    if (!galeria) return;
    galeria.innerHTML = '';
    let imagenesLoaded = [];
    let imagenesCount = 0;
    
    Array.from(files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(event) {
            imagenesLoaded[index] = { src: event.target.result, index: index };
            imagenesCount++;
            if (imagenesCount === Array.from(files).length) {
                imagenesLoaded.forEach((imgData, posicion) => {
                    if (imgData) {
                        const div = document.createElement('div');
                        div.style.cssText = 'position: relative; width: 100%; padding-bottom: 100%; overflow: hidden; border-radius: 8px; border: 1px solid #ddd;';
                        const img = document.createElement('img');
                        img.src = imgData.src;
                        img.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;';
                        const numero = document.createElement('div');
                        numero.innerHTML = posicion + 1;
                        numero.style.cssText = 'position: absolute; bottom: 5px; left: 5px; background: #3498db; color: white; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;';
                        const btnEliminar = document.createElement('button');
                        btnEliminar.type = 'button';
                        btnEliminar.innerHTML = '✕';
                        btnEliminar.style.cssText = 'position: absolute; top: 5px; right: 5px; background: #f44336; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; padding: 0;';
                        btnEliminar.addEventListener('click', (e) => {
                            e.preventDefault();
                            const fileEliminado = archivosAcumulados[posicion];
                            console.log(`🗑️ Eliminando imagen de bordado: ${fileEliminado?.name || 'desconocida'}`);
                            
                            // ✅ IMPORTANTE: Eliminar también de window.imagenesEnMemoria.logo
                            if (window.imagenesEnMemoria && window.imagenesEnMemoria.logo && Array.isArray(window.imagenesEnMemoria.logo)) {
                                const beforeCount = window.imagenesEnMemoria.logo.length;
                                window.imagenesEnMemoria.logo = window.imagenesEnMemoria.logo.filter(img => {
                                    // Comparar por nombre si es un archivo, o por propiedad si es un objeto
                                    if (img && typeof img === 'object' && img.name === fileEliminado?.name) {
                                        console.log(`❌ Eliminado de memoria global:`, img.name);
                                        return false;
                                    }
                                    return true;
                                });
                                console.log(`✅ Eliminación completada. Antes: ${beforeCount}, Después: ${window.imagenesEnMemoria.logo.length}`);
                            }
                            
                            archivosAcumulados.splice(posicion, 1);
                            const dt = new DataTransfer();
                            archivosAcumulados.forEach(f => dt.items.add(f));
                            document.getElementById('imagenes_bordado').files = dt.files;
                            mostrarImagenes(archivosAcumulados);
                        });
                        div.appendChild(img);
                        div.appendChild(numero);
                        div.appendChild(btnEliminar);
                        galeria.appendChild(div);
                    }
                });
            }
        };
        reader.readAsDataURL(file);
    });
}
