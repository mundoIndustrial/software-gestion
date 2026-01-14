/**
 * ================================================
 * COMPONENTE: PRENDAS - VERSIÓN REFACTORIZADA
 * ================================================
 * 
 * RESPONSABILIDAD ÚNICA:
 * - Mostrar galerías de imágenes (ÚNICA funcionalidad específica de este componente)
 * 
 * DELEGADO A OTROS MÓDULOS:
 * ❌ Gestión de telas → gestion-telas.js
 * ❌ Gestión de tallas → gestion-tallas.js
 * ❌ Agregar/Editar prendas → GestorPrendaSinCotizacion + GestionItemsUI
 * ❌ Limpieza de formularios → GestionItemsUI
 * ❌ Transformación de datos → GestorPrendaSinCotizacion
 * ❌ Renderización de cards → GestionItemsUI + Blade templates
 * 
 * MANTENER:
 * ✅ abrirGaleriaItemCard() - Galería de imágenes de producto
 * ✅ abrirGaleriaTela() - Galería de imágenes de tela
 */

/**
 * Abre una galería modal de imágenes para el item de la card
 * @param {number} itemIndex - Índice del item
 * @param {Event} event - Evento del click
 */
function abrirGaleriaItemCard(itemIndex, event) {
    event.stopPropagation();
    
    console.log('🖼️ [GALERIA] Abriendo galería para item:', itemIndex);
    
    if (!window.itemsPedido || !window.itemsPedido[itemIndex]) {
        console.error('❌ [GALERIA] Item no encontrado');
        return;
    }
    
    const item = window.itemsPedido[itemIndex];
    const imagenes = item.imagenes || [];
    
    if (!imagenes || imagenes.length === 0) {
        console.warn('⚠️ [GALERIA] Item sin imágenes');
        alert('Este item no tiene imágenes');
        return;
    }
    
    console.log('📸 [GALERIA] Imágenes encontradas:', imagenes.length);
    
    // Función para obtener un blob URL válido desde una imagen
    const obtenerBlobUrl = (img) => {
        // Si es un File object, crear blob URL
        if (img.file && img.file instanceof File) {
            return URL.createObjectURL(img.file);
        }
        
        // Si es un blob URL válido (comienza con blob:)
        if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
            return img.previewUrl;
        }
        
        // Fallback: intentar recrear desde previewUrl si existe
        if (img.previewUrl) {
            return img.previewUrl;
        }
        
        return null;
    };
    
    // Crear modal de galería
    const modal = document.createElement('div');
    modal.id = 'galeria-item-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 20px;
        box-sizing: border-box;
    `;
    
    let indiceActual = 0;
    
    // Definir handleKeyPress antes de usarlo
    const handleKeyPress = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', handleKeyPress);
        } else if (e.key === 'ArrowLeft') {
            indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
            const nuevoUrl = obtenerBlobUrl(imagenes[indiceActual]);
            if (nuevoUrl) {
                img.src = nuevoUrl;
                actualizarIndicador();
            }
        } else if (e.key === 'ArrowRight') {
            indiceActual = (indiceActual + 1) % imagenes.length;
            const nuevoUrl = obtenerBlobUrl(imagenes[indiceActual]);
            if (nuevoUrl) {
                img.src = nuevoUrl;
                actualizarIndicador();
            }
        }
    };
    
    // Contenedor de imagen
    const imagenContainer = document.createElement('div');
    imagenContainer.style.cssText = `
        position: relative;
        width: 100%;
        max-width: 800px;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        max-height: 90vh;
    `;
    
    const img = document.createElement('img');
    // Obtener blob URL dinámicamente
    let urlActual = obtenerBlobUrl(imagenes[0]);
    if (!urlActual) {
        console.error('❌ [GALERIA] No se pudo obtener blob URL para la primera imagen');
        return;
    }
    img.src = urlActual;
    img.style.cssText = `
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
    `;
    
    imagenContainer.appendChild(img);
    
    // Botón cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded">close</span>';
    btnCerrar.style.cssText = `
        position: absolute;
        top: 15px;
        right: 15px;
        background: #ff4444;
        color: white;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        z-index: 2001;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        transition: all 0.2s ease;
    `;
    btnCerrar.onmouseover = () => {
        btnCerrar.style.background = '#ff1111';
        btnCerrar.style.transform = 'scale(1.1)';
        btnCerrar.style.boxShadow = '0 4px 12px rgba(0,0,0,0.4)';
    };
    btnCerrar.onmouseout = () => {
        btnCerrar.style.background = '#ff4444';
        btnCerrar.style.transform = 'scale(1)';
        btnCerrar.style.boxShadow = '0 2px 8px rgba(0,0,0,0.3)';
    };
    btnCerrar.onclick = (e) => {
        e.stopPropagation();
        modal.remove();
        document.removeEventListener('keydown', handleKeyPress);
    };
    
    imagenContainer.appendChild(btnCerrar);
    
    // Botón anterior
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded">chevron_left</span>';
    btnAnterior.style.cssText = `
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.9);
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        z-index: 2001;
    `;
    btnAnterior.onclick = () => {
        indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
        // Obtener blob URL dinámicamente
        const nuevoUrl = obtenerBlobUrl(imagenes[indiceActual]);
        if (nuevoUrl) {
            img.src = nuevoUrl;
            actualizarIndicador();
        } else {
            console.error('❌ [GALERIA] No se pudo obtener blob URL para imagen anterior');
        }
    };
    imagenContainer.appendChild(btnAnterior);
    
    // Botón siguiente
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded">chevron_right</span>';
    btnSiguiente.style.cssText = `
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.9);
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        z-index: 2001;
    `;
    btnSiguiente.onclick = () => {
        indiceActual = (indiceActual + 1) % imagenes.length;
        // Obtener blob URL dinámicamente
        const nuevoUrl = obtenerBlobUrl(imagenes[indiceActual]);
        if (nuevoUrl) {
            img.src = nuevoUrl;
            actualizarIndicador();
        } else {
            console.error('❌ [GALERIA] No se pudo obtener blob URL para imagen siguiente');
        }
    };
    imagenContainer.appendChild(btnSiguiente);
    
    // Indicador de posición
    const indicador = document.createElement('div');
    indicador.style.cssText = `
        position: absolute;
        bottom: 15px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
        z-index: 2001;
    `;
    
    const actualizarIndicador = () => {
        indicador.textContent = `${indiceActual + 1} / ${imagenes.length}`;
    };
    
    actualizarIndicador();
    imagenContainer.appendChild(indicador);
    
    document.addEventListener('keydown', handleKeyPress);
    
    // Agregar contenedor a modal y mostrar
    modal.appendChild(imagenContainer);
    document.body.appendChild(modal);
    
    console.log('✅ [GALERIA] Modal abierto');
}

/**
 * Abre una galería modal para las imágenes de tela
 * @param {number} itemIndex - Índice del item
 * @param {Event} event - Evento del click
 */
function abrirGaleriaTela(itemIndex, event) {
    event.stopPropagation();
    
    console.log('🧵 [GALERIA TELA] Abriendo galería para item:', itemIndex);
    
    if (!window.itemsPedido || !window.itemsPedido[itemIndex]) {
        console.error('❌ [GALERIA TELA] Item no encontrado');
        return;
    }
    
    const item = window.itemsPedido[itemIndex];
    const prendaData = item.prenda || {};
    const telas = prendaData.telas || [];
    
    if (!telas || telas.length === 0) {
        console.warn('⚠️ [GALERIA TELA] Item sin telas');
        return;
    }
    
    console.log('🧵 [GALERIA TELA] Telas encontradas:', telas.length);
    
    // Función para obtener un blob URL válido desde una imagen de tela
    const obtenerBlobUrlTela = (tela) => {
        if (!tela.imagenes || tela.imagenes.length === 0) return null;
        
        const img = tela.imagenes[0];
        
        // Si es un File object, crear blob URL
        if (img.file && img.file instanceof File) {
            return URL.createObjectURL(img.file);
        }
        
        // Si es un blob URL válido
        if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
            return img.previewUrl;
        }
        
        // Fallback
        return img.previewUrl || null;
    };
    
    // Crear modal de galería
    const modal = document.createElement('div');
    modal.id = 'galeria-tela-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 20px;
        box-sizing: border-box;
    `;
    
    let indiceActual = 0;
    
    // Definir handleKeyPress antes de usarlo
    const handleKeyPressTela = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', handleKeyPressTela);
        } else if (e.key === 'ArrowLeft') {
            indiceActual = (indiceActual - 1 + telas.length) % telas.length;
            const nuevoUrl = obtenerBlobUrlTela(telas[indiceActual]);
            if (nuevoUrl) {
                img.src = nuevoUrl;
                actualizarIndicador();
            }
        } else if (e.key === 'ArrowRight') {
            indiceActual = (indiceActual + 1) % telas.length;
            const nuevoUrl = obtenerBlobUrlTela(telas[indiceActual]);
            if (nuevoUrl) {
                img.src = nuevoUrl;
                actualizarIndicador();
            }
        }
    };
    
    // Contenedor de imagen
    const imagenContainer = document.createElement('div');
    imagenContainer.style.cssText = `
        position: relative;
        width: 100%;
        max-width: 800px;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        max-height: 90vh;
    `;
    
    const img = document.createElement('img');
    let urlActual = obtenerBlobUrlTela(telas[0]);
    if (!urlActual) {
        console.error('❌ [GALERIA TELA] No se pudo obtener blob URL para la primera tela');
        return;
    }
    img.src = urlActual;
    img.style.cssText = `
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
    `;
    
    imagenContainer.appendChild(img);
    
    // Botón cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded">close</span>';
    btnCerrar.style.cssText = `
        position: absolute;
        top: 15px;
        right: 15px;
        background: #ff4444;
        color: white;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        z-index: 2001;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        transition: all 0.2s ease;
    `;
    btnCerrar.onmouseover = () => {
        btnCerrar.style.background = '#ff1111';
        btnCerrar.style.transform = 'scale(1.1)';
        btnCerrar.style.boxShadow = '0 4px 12px rgba(0,0,0,0.4)';
    };
    btnCerrar.onmouseout = () => {
        btnCerrar.style.background = '#ff4444';
        btnCerrar.style.transform = 'scale(1)';
        btnCerrar.style.boxShadow = '0 2px 8px rgba(0,0,0,0.3)';
    };
    btnCerrar.onclick = (e) => {
        e.stopPropagation();
        modal.remove();
        document.removeEventListener('keydown', handleKeyPressTela);
    };
    
    imagenContainer.appendChild(btnCerrar);
    
    // Botón anterior
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded">chevron_left</span>';
    btnAnterior.style.cssText = `
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.9);
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        z-index: 2001;
    `;
    btnAnterior.onclick = () => {
        indiceActual = (indiceActual - 1 + telas.length) % telas.length;
        const nuevoUrl = obtenerBlobUrlTela(telas[indiceActual]);
        if (nuevoUrl) {
            img.src = nuevoUrl;
            actualizarIndicador();
        } else {
            console.error('❌ [GALERIA TELA] No se pudo obtener blob URL para tela anterior');
        }
    };
    imagenContainer.appendChild(btnAnterior);
    
    // Botón siguiente
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded">chevron_right</span>';
    btnSiguiente.style.cssText = `
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.9);
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        z-index: 2001;
    `;
    btnSiguiente.onclick = () => {
        indiceActual = (indiceActual + 1) % telas.length;
        const nuevoUrl = obtenerBlobUrlTela(telas[indiceActual]);
        if (nuevoUrl) {
            img.src = nuevoUrl;
            actualizarIndicador();
        } else {
            console.error('❌ [GALERIA TELA] No se pudo obtener blob URL para tela siguiente');
        }
    };
    imagenContainer.appendChild(btnSiguiente);
    
    // Indicador de posición
    const indicador = document.createElement('div');
    indicador.style.cssText = `
        position: absolute;
        bottom: 15px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
        z-index: 2001;
    `;
    
    const actualizarIndicador = () => {
        indicador.textContent = `${indiceActual + 1} / ${telas.length}`;
    };
    
    actualizarIndicador();
    imagenContainer.appendChild(indicador);
    
    document.addEventListener('keydown', handleKeyPressTela);
    
    // Agregar contenedor a modal y mostrar
    modal.appendChild(imagenContainer);
    document.body.appendChild(modal);
    
    console.log('✅ [GALERIA TELA] Modal abierto');
}

console.log('✅ [PRENDAS.JS] Módulo cargado - solo galerías');
