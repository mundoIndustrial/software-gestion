/**
 * GESTIÓN DE TELAS
 * Sistema centralizado para manejar telas, colores, referencias e imágenes
 * 
 * CARACTERÍSTICAS:
 * - Gestión de múltiples telas con colores, referencias e imágenes
 * - Hasta 3 imágenes por tela
 * - Modal de visualización de imágenes en galería
 * - Tabla dinámica de telas agregadas
 */

// ========== ESTADO GLOBAL DE TELAS ==========
window.telasAgregadas = [];
window.imagenesTelaModalNueva = [];

// ========== AGREGAR NUEVA TELA ==========
window.agregarTelaNueva = function() {
    console.log('🧵 [TELAS] agregarTelaNueva() LLAMADO');
    
    const color = document.getElementById('nueva-prenda-color').value.trim().toUpperCase();
    const tela = document.getElementById('nueva-prenda-tela').value.trim().toUpperCase();
    const referencia = document.getElementById('nueva-prenda-referencia').value.trim().toUpperCase();
    
    console.log('🧵 [TELAS] Valores:', { color, tela, referencia });
    
    // Validación
    if (!color) {
        alert('Por favor completa el campo Color');
        document.getElementById('nueva-prenda-color').focus();
        return;
    }
    if (!tela) {
        alert('Por favor completa el campo Tela');
        document.getElementById('nueva-prenda-tela').focus();
        return;
    }
    if (!referencia) {
        alert('Por favor completa el campo Referencia');
        document.getElementById('nueva-prenda-referencia').focus();
        return;
    }
    
    // Obtener imágenes del storage temporal - SOLO GUARDAR FILE OBJECTS (no blob URLs)
    const imagenesTemporales = window.imagenesTelaStorage.obtenerImagenes();
    console.log('🧵 [TELAS] Imágenes temporales:', imagenesTemporales.length);
    
    // Copiar SOLO los File objects y metadatos (NO el previewUrl volátil)
    const imagenesCopia = imagenesTemporales.map(img => ({
        file: img.file,  // El File object es permanente
        nombre: img.nombre,
        tamaño: img.tamaño
        // NO copiar previewUrl - crearemos una nueva blob URL cuando sea necesario
    }));
    
    // Agregar a la lista
    window.telasAgregadas.push({ 
        color, 
        tela, 
        referencia,
        imagenes: imagenesCopia
    });
    
    console.log('✅ [TELAS] Tela agregada a telasAgregadas. Total ahora:', window.telasAgregadas.length);
    console.log('✅ [TELAS] telasAgregadas:', window.telasAgregadas);
    
    // Limpiar inputs
    document.getElementById('nueva-prenda-color').value = '';
    document.getElementById('nueva-prenda-tela').value = '';
    document.getElementById('nueva-prenda-referencia').value = '';
    
    // Limpiar storage de imágenes (revoca blob URLs viejas)
    window.imagenesTelaStorage.limpiar();
    
    // Limpiar preview temporal (el que se mostró mientras se agregaban imágenes - ahora dentro de la celda)
    const previewTemporal = document.getElementById('nueva-prenda-tela-preview');
    if (previewTemporal) {
        previewTemporal.innerHTML = '';
        previewTemporal.style.display = 'none'; // Ocultar completamente
    }
    
    // Limpiar input file
    const inputFile = document.getElementById('nueva-prenda-tela-img-input');
    if (inputFile) {
        inputFile.value = '';
    }
    
    // Actualizar tabla
    console.log('🧵 [TELAS] Llamando a actualizarTablaTelas()');
    actualizarTablaTelas();
};

/**
 * Actualizar tabla de telas
 */
window.actualizarTablaTelas = function() {
    const tbody = document.getElementById('tbody-telas');
    
    if (!tbody) {
        console.error('❌ [TELAS] No se encontró tbody-telas');
        return;
    }
    
    console.log('📊 [TELAS] actualizarTablaTelas llamado. telasAgregadas:', window.telasAgregadas);
    
    // Limpiar tbody excepto la fila de inputs (la primera fila)
    const filas = Array.from(tbody.querySelectorAll('tr'));
    console.log('📊 [TELAS] Filas en tbody:', filas.length);
    
    filas.forEach((fila, index) => {
        console.log(`📊 [TELAS] Fila ${index}:`, fila.innerHTML.substring(0, 50));
        if (index > 0) {
            fila.remove();
            console.log(`📊 [TELAS] Removida fila ${index}`);
        }
    });
    
    console.log('📊 [TELAS] Actualizando tabla con', window.telasAgregadas.length, 'telas');
    
    // Agregar filas con los datos
    window.telasAgregadas.forEach((telaData, index) => {
        console.log(`📊 [TELAS] Agregando tela ${index}:`, telaData);
        
        const tr = document.createElement('tr');
        tr.style.cssText = 'border-bottom: 1px solid #e5e7eb;';
        
        // Crear celda de imágenes
        let imagenHTML = '';
        if (telaData.imagenes && telaData.imagenes.length > 0) {
            console.log(`📸 [TELAS] Tela ${index} tiene ${telaData.imagenes.length} imagen(es)`);
            
            // Crear un array con blob URLs dinámicas para esta visualización
            const imagenConBlobUrl = telaData.imagenes.map((img, imgIndex) => {
                // Crear una nueva blob URL a partir del File object
                const blobUrl = URL.createObjectURL(img.file);
                console.log(`📸 [TELAS] Creada blob URL para imagen ${imgIndex} de tela ${index}: ${blobUrl.substring(0, 50)}...`);
                return {
                    ...img,
                    previewUrl: blobUrl  // Blob URL recién creada para esta sesión
                };
            });
            
            imagenHTML = `
                <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                    <img src="${imagenConBlobUrl[0].previewUrl}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; cursor: pointer;" onclick="mostrarGaleriaImagenesTela(null, ${index}, 0)">
                    ${imagenConBlobUrl.length > 1 ? `<span style="background: #0066cc; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">+${imagenConBlobUrl.length - 1}</span>` : ''}
                </div>
            `;
        } else {
            console.log(`📸 [TELAS] Tela ${index} NO tiene imágenes`);
        }
        
        const html = `
            <td style="padding: 0.75rem; vertical-align: middle;">${telaData.tela}</td>
            <td style="padding: 0.75rem; vertical-align: middle;">${telaData.color}</td>
            <td style="padding: 0.75rem; vertical-align: middle;">${telaData.referencia}</td>
            <td style="padding: 0.75rem; text-align: center; vertical-align: middle; min-height: 60px; display: table-cell;">
                ${imagenHTML}
            </td>
            <td style="padding: 0.75rem; text-align: center; vertical-align: middle;">
                <button type="button" onclick="eliminarTela(${index})" class="btn btn-sm" style="background: #ef4444; color: white; padding: 0.25rem 0.5rem; font-size: 0.75rem; border: none; cursor: pointer; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">delete</span>
                </button>
            </td>
        `;
        
        tr.innerHTML = html;
        tbody.appendChild(tr);
        console.log(`✅ [TELAS] Tela ${index} agregada a la tabla`);
    });
    
    console.log('✅ [TELAS] Tabla actualizada. Filas en tbody ahora:', tbody.querySelectorAll('tr').length);
};

/**
 * Eliminar tela con confirmación
 */
window.eliminarTela = function(index) {
    const confirmModal = document.createElement('div');
    confirmModal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10002;';
    
    const confirmBox = document.createElement('div');
    confirmBox.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
    
    const titulo = document.createElement('h3');
    titulo.textContent = '¿Eliminar esta tela?';
    titulo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937; font-size: 1.25rem;';
    confirmBox.appendChild(titulo);
    
    const mensaje = document.createElement('p');
    mensaje.textContent = 'Esta acción no se puede deshacer.';
    mensaje.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;';
    confirmBox.appendChild(mensaje);
    
    const botones = document.createElement('div');
    botones.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end;';
    
    const btnCancelar = document.createElement('button');
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
    btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
    btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
    btnCancelar.onclick = () => confirmModal.remove();
    botones.appendChild(btnCancelar);
    
    const btnConfirmar = document.createElement('button');
    btnConfirmar.textContent = 'Eliminar';
    btnConfirmar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
    btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#dc2626';
    btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#ef4444';
    btnConfirmar.onclick = () => {
        confirmModal.remove();
        console.log('🗑️ [TELAS] Eliminando tela en índice:', index);
        window.telasAgregadas.splice(index, 1);
        actualizarTablaTelas();
    };
    botones.appendChild(btnConfirmar);
    
    confirmBox.appendChild(botones);
    confirmModal.appendChild(confirmBox);
    document.body.appendChild(confirmModal);
};

/**
 * Manejar imagen de tela
 */
window.manejarImagenTela = function(input) {
    if (!input.files || input.files.length === 0) {
        return;
    }
    
    const file = input.files[0];
    
    // Validar que sea imagen
    if (!file.type.startsWith('image/')) {
        alert('Por favor selecciona una imagen válida');
        return;
    }
    
    // Verificar límite de 3 imágenes
    if (window.imagenesTelaStorage.obtenerImagenes().length >= 3) {
        alert('Máximo 3 imágenes por tela');
        return;
    }
    
    // Agregar imagen al storage
    window.imagenesTelaStorage.agregarImagen(file)
        .then(() => {
            console.log('✅ [TELAS] Imagen agregada. Total:', window.imagenesTelaStorage.obtenerImagenes().length);
            // ✅ NO renderizar nada aquí - la imagen se renderizará cuando se agregue la tela a la tabla
            input.value = '';
        })
        .catch(err => {
            alert(err.message);
        });
};

/**
 * ⚠️ DEPRECATED: Ya no se usa - las imágenes de tela se renderizan en actualizarTablaTelas()
 * Las imágenes se renderizaban en este punto, pero causaba errores porque la fila
 * de la tela aún no existía en la tabla. Ahora solo se renderizan cuando la tela
 * se agrega y se crea su fila correspondiente.
 */
window.actualizarPreviewTela = function() {
    console.warn('⚠️ [TELAS] actualizarPreviewTela() NO DEBE LLAMARSE - usar actualizarTablaTelas() en su lugar');
};

/**
 * Mostrar galería de imágenes temporales (antes de guardar tela)
 */
window.mostrarGaleriaImagenesTemporales = function(imagenes, indiceInicial = 0) {
    if (!imagenes || imagenes.length === 0) return;
    
    window.imagenesTelaModalNueva = imagenes;
    let indiceActual = indiceInicial;
    
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; padding: 0;';
    
    const container = document.createElement('div');
    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
    
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; height: calc(100% - 120px); padding: 2rem;';
    
    const imgModal = document.createElement('img');
    imgModal.src = imagenes[indiceActual].previewUrl;  // Usar blob URL en lugar de base64
    imgModal.style.cssText = 'width: 90vw; height: 85vh; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    
    imgContainer.appendChild(imgModal);
    
    // Toolbar
    const toolbar = document.createElement('div');
    toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
    
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
    btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
    btnAnterior.onclick = () => {
        indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
        imgModal.src = imagenes[indiceActual].previewUrl;  // Usar blob URL
        contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
    };
    toolbar.appendChild(btnAnterior);
    
    // ❌ BOTÓN ELIMINAR REMOVIDO - Solo usar la X para cerrar la galería
    
    const contador = document.createElement('div');
    contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
    contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
    toolbar.appendChild(contador);
    
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
    btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
    btnSiguiente.onclick = () => {
        indiceActual = (indiceActual + 1) % imagenes.length;
        imgModal.src = imagenes[indiceActual].previewUrl;  // Usar blob URL
        contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
    };
    toolbar.appendChild(btnSiguiente);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
    btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
    btnCerrar.onclick = () => modal.remove();
    toolbar.appendChild(btnCerrar);
    
    container.appendChild(imgContainer);
    container.appendChild(toolbar);
    modal.appendChild(container);
    document.body.appendChild(modal);
};

/**
 * Obtener telas para envío
 */
window.obtenerTelasParaEnvio = function() {
    console.log('📦 [TELAS] Preparando datos de telas para envío');
    return window.telasAgregadas;
};

/**
 * Limpiar todas las telas
 */
window.limpiarTelas = function() {
    console.log('🧹 [TELAS] Limpiando todas las telas');
    window.telasAgregadas = [];
    if (window.imagenesTelaStorage) {
        window.imagenesTelaStorage.limpiar();
    }
    actualizarTablaTelas();
};

/**
 * NUEVA GALERÍA: Mostrar galería de imágenes de tela (mismo comportamiento que prendas)
 * @param {Array} imagenes - Array de imágenes de la tela
 * @param {number} telaIndex - Índice de la tela en la tabla
 * @param {number} indiceInicial - Índice inicial a mostrar
 */
window.mostrarGaleriaImagenesTela = function(imagenes, telaIndex = 0, indiceInicial = 0) {
    // ✅ Obtener la tela específica y sus imágenes (fuente de verdad por tela)
    const telaActual = window.telasAgregadas && window.telasAgregadas[telaIndex] ? window.telasAgregadas[telaIndex] : null;
    if (!telaActual) {
        console.error('❌ [GALERÍA TELA] No se encontró la tela en índice', telaIndex);
        return;
    }
    const imagenesActuales = telaActual.imagenes || [];
    
    if (!imagenesActuales || imagenesActuales.length === 0) {
        console.error('❌ [GALERÍA TELA] No hay imágenes para mostrar');
        return;
    }
    
    // ✅ Evitar que se reabra la galería mientras está en uso
    if (window.__galeriaTelaAbierta) {
        console.warn('⚠️ [GALERÍA TELA] Galería ya está abierta, ignorando');
        return;
    }
    window.__galeriaTelaAbierta = true;
    
    console.log('🖼️ [GALERÍA TELA] Abriendo galería para tela', telaIndex, ':', imagenesActuales.length, 'imágenes');
    
    // Crear nuevos blob URLs para evitar que se revoquen
    const imagenesConBlobUrl = imagenesActuales.map((img, idx) => {
        let blobUrl;
        if (img.file instanceof File || img.file instanceof Blob) {
            blobUrl = URL.createObjectURL(img.file);
        } else if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
            blobUrl = img.previewUrl;
        } else {
            console.error(`❌ [GALERÍA TELA] Imagen ${idx} sin File o blob URL válido`);
            return null;
        }
        return {
            ...img,
            previewUrl: blobUrl,
            blobUrl: blobUrl
        };
    }).filter(img => img !== null);
    
    if (imagenesConBlobUrl.length === 0) {
        console.error('❌ [GALERÍA TELA] No se pudieron crear blob URLs válidos');
        window.__galeriaTelaAbierta = false;
        return;
    }
    
    let indiceActual = indiceInicial;
    
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: flex-start; z-index: 10000; padding: 0; margin: 0; overflow: hidden;';
    
    const container = document.createElement('div');
    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
    
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; padding: 2rem 1rem; overflow: hidden;';
    
    const imgModal = document.createElement('img');
    imgModal.src = imagenesConBlobUrl[indiceActual].previewUrl;
    imgModal.style.cssText = 'width: 90vw; height: 85vh; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    
    imgContainer.appendChild(imgModal);
    
    // ✅ Función auxiliar para actualizar la imagen
    const actualizarImagen = (nuevoIndice) => {
        indiceActual = nuevoIndice;
        const newBlobUrl = imagenesConBlobUrl[indiceActual].previewUrl;
        imgModal.src = '';
        imgModal.src = newBlobUrl;
        contador.textContent = (indiceActual + 1) + ' de ' + imagenesConBlobUrl.length;
        console.log(`🔄 [GALERÍA TELA] Imagen actualizada a índice ${indiceActual}`);
    };
    
    // Toolbar
    const toolbar = document.createElement('div');
    toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
    
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
    btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
    btnAnterior.onclick = () => {
        console.log('⬅️ [GALERÍA TELA] Imagen anterior');
        const nuevoIndice = (indiceActual - 1 + imagenesConBlobUrl.length) % imagenesConBlobUrl.length;
        actualizarImagen(nuevoIndice);
    };
    toolbar.appendChild(btnAnterior);
    
    // ❌ BOTÓN ELIMINAR REMOVIDO - Solo usar la X del formulario para eliminar
    // Las imágenes de telas se eliminan desde el formulario, no desde la galería
    
    const contador = document.createElement('div');
    contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
    contador.textContent = (indiceActual + 1) + ' de ' + imagenesConBlobUrl.length;
    toolbar.appendChild(contador);
    
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
    btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
    btnSiguiente.onclick = () => {
        console.log('➡️ [GALERÍA TELA] Imagen siguiente');
        const nuevoIndice = (indiceActual + 1) % imagenesConBlobUrl.length;
        actualizarImagen(nuevoIndice);
    };
    toolbar.appendChild(btnSiguiente);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
    btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
    
    let cerrando = false;
    btnCerrar.onclick = () => {
        if (cerrando) return;
        cerrando = true;
        console.log('❌ [GALERÍA TELA] Cerrando');
        cerrarGaleria();
    };
    toolbar.appendChild(btnCerrar);
    
    // Cerrar con ESC
    const handleEsc = (e) => {
        if (e.key === 'Escape') {
            console.log('⌨️ [GALERÍA TELA] ESC presionado');
            cerrarGaleria();
        }
    };
    document.addEventListener('keydown', handleEsc);
    
    // Cerrar al clickear afuera
    modal.onclick = (e) => {
        if (e.target === modal) {
            console.log('🖱️ [GALERÍA TELA] Click fuera');
            cerrarGaleria();
        }
    };
    
    // ✅ Función para cerrar la galería y limpiar flags
    const cerrarGaleria = () => {
        document.removeEventListener('keydown', handleEsc);
        modal.remove();
        window.__galeriaTelaAbierta = false;
    };
    
    container.appendChild(imgContainer);
    container.appendChild(toolbar);
    modal.appendChild(container);
    document.body.appendChild(modal);
    
    console.log('✅ [GALERÍA TELA] Galería abierta');
};
