/**
 * SISTEMA DE COTIZACIONES - GESTIÓN DE PRODUCTOS
 * Responsabilidad: Agregar, eliminar, buscar y gestionar productos
 */

let productosCount = 0;
let fotosSeleccionadas = {};
let telasSeleccionadas = {}; // Nueva variable para guardar telas por prenda

// ============ PRODUCTOS ============

function agregarProductoFriendly() {
    productosCount++;
    const template = document.getElementById('productoTemplate');
    const clone = template.content.cloneNode(true);
    clone.querySelector('.numero-producto').textContent = productosCount;
    const productoId = 'producto-' + Date.now() + '-' + productosCount;
    clone.querySelector('.producto-card').dataset.productoId = productoId;
    fotosSeleccionadas[productoId] = [];
    telasSeleccionadas[productoId] = []; // Inicializar telas para esta prenda
    document.getElementById('productosContainer').appendChild(clone);
}

function eliminarProductoFriendly(btn) {
    btn.closest('.producto-card').remove();
    renumerarPrendas();
    actualizarResumenFriendly();
}

function renumerarPrendas() {
    const prendas = document.querySelectorAll('.producto-card');
    prendas.forEach((prenda, index) => {
        prenda.querySelector('.numero-producto').textContent = index + 1;
    });
    productosCount = prendas.length;
}

// Agregar prenda por defecto al cargar
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('productosContainer');
    if (container && container.children.length === 0) {
        agregarProductoFriendly();
    }
});

function toggleProductoBody(btn) {
    const body = btn.closest('.producto-card').querySelector('.producto-body');
    if (body.style.display === 'none') {
        body.style.display = 'block';
        btn.style.transform = 'rotate(180deg)';
    } else {
        body.style.display = 'none';
        btn.style.transform = 'rotate(0deg)';
    }
}

// ============ FOTOS DE PRODUCTOS ============

function manejarDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    const dropZone = event.currentTarget;
    dropZone.classList.remove('drag-over');
    agregarFotos(event.dataTransfer.files, dropZone);
}

function agregarFotos(files, dropZone) {
    const productoCard = dropZone.closest('.producto-card');
    const productoId = productoCard ? productoCard.dataset.productoId : 'default';
    if (!fotosSeleccionadas[productoId]) fotosSeleccionadas[productoId] = [];
    
    console.log('📁 Agregando fotos de prenda a memoria');
    Array.from(files).forEach(file => {
        if (fotosSeleccionadas[productoId].length < 3) {
            fotosSeleccionadas[productoId].push(file);
            window.imagenesEnMemoria.prenda.push(file);
            console.log(`✅ Foto de prenda guardada: ${file.name}`);
        }
    });
    actualizarPreviewFotos(dropZone);
}

function actualizarPreviewFotos(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) return;
    const productoId = productoCard.dataset.productoId || 'default';
    
    let container = null;
    const label = input.closest('label');
    if (label && label.parentElement) container = label.parentElement.querySelector('.fotos-preview');
    if (!container) container = productoCard.querySelector('.fotos-preview');
    if (!container) return;
    
    container.innerHTML = '';
    const fotos = fotosSeleccionadas[productoId] || [];
    
    fotos.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.setAttribute('data-foto', 'true');
            preview.setAttribute('data-index', index);
            preview.style.cssText = 'position: relative; width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f0f0f0; cursor: pointer;';
            const imagenSrc = e.target.result;
            preview.innerHTML = `
                <img src="${imagenSrc}" style="width: 100%; height: 100%; object-fit: cover;">
                <span style="position: absolute; top: 1px; left: 1px; background: #0066cc; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold;">${index + 1}</span>
                <button type="button" onclick="event.stopPropagation(); eliminarFoto('${productoId}', ${index})" style="position: absolute; top: 1px; left: 1px; background: #f44336; color: white; border: none; border-radius: 50%; width: 16px; height: 16px; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; padding: 0; opacity: 0; transition: opacity 0.2s;">✕</button>
            `;
            
            // Agregar evento para abrir modal al hacer clic en la imagen
            preview.addEventListener('click', function(e) {
                if (e.target.tagName !== 'BUTTON') {
                    abrirModalImagenPrendaConIndice(imagenSrc, index);
                }
            });
            
            preview.addEventListener('mouseenter', function() {
                this.querySelector('button').style.opacity = '1';
                this.querySelector('span').style.opacity = '0';
            });
            preview.addEventListener('mouseleave', function() {
                this.querySelector('button').style.opacity = '0';
                this.querySelector('span').style.opacity = '1';
            });
            container.appendChild(preview);
        };
        reader.readAsDataURL(file);
    });
}

// Función para abrir modal de imagen de prenda con índice correcto
function abrirModalImagenPrendaConIndice(imagenSrc, indice) {
    let modal = document.getElementById('modalImagenPrendaLocal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modalImagenPrendaLocal';
        modal.style.cssText = 'display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 10000; align-items: center; justify-content: center;';
        modal.innerHTML = `
            <div style="position: relative; max-width: 600px; max-height: 600px; display: flex; align-items: center; justify-content: center;">
                <img id="modalImagenPrendaLocalImg" src="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <button type="button" onclick="cerrarModalImagenPrendaLocal()" style="position: absolute; top: 10px; right: 10px; background: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; color: #333;">✕</button>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    modal.style.display = 'flex';
    document.getElementById('modalImagenPrendaLocalImg').src = imagenSrc;
}

function cerrarModalImagenPrendaLocal() {
    const modal = document.getElementById('modalImagenPrendaLocal');
    if (modal) modal.style.display = 'none';
}

// Función para abrir modal de imagen con índice correcto (para cotizaciones guardadas)
function abrirModalImagenConIndice(imagenUrl, indice, todasLasImagenes) {
    let modal = document.getElementById('modalImagen');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modalImagen';
        modal.style.cssText = 'display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); z-index: 9999; align-items: center; justify-content: center; padding: 0; margin: 0; overflow: hidden;';
        modal.innerHTML = `
            <div style="position: relative; width: calc(100vw - 160px); height: calc(100vh - 120px); display: flex; align-items: center; justify-content: center; overflow: auto;">
                <img id="modalImagenImg" src="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <button type="button" onclick="cerrarModalImagen()" style="position: absolute; top: 20px; right: 20px; background: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; color: #333;">✕</button>
                <button type="button" onclick="imagenAnterior()" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; color: #333;">◀</button>
                <button type="button" onclick="imagenSiguiente()" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; color: #333;">▶</button>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    modal.style.display = 'flex';
    modal.dataset.imagenes = JSON.stringify(todasLasImagenes || [imagenUrl]);
    modal.dataset.indiceActual = indice;
    document.getElementById('modalImagenImg').src = imagenUrl;
}

function cerrarModalImagen() {
    const modal = document.getElementById('modalImagen');
    if (modal) modal.style.display = 'none';
}

function imagenAnterior() {
    const modal = document.getElementById('modalImagen');
    if (!modal) return;
    
    const imagenes = JSON.parse(modal.dataset.imagenes || '[]');
    let indice = parseInt(modal.dataset.indiceActual || 0);
    indice = (indice - 1 + imagenes.length) % imagenes.length;
    
    modal.dataset.indiceActual = indice;
    document.getElementById('modalImagenImg').src = imagenes[indice];
}

function imagenSiguiente() {
    const modal = document.getElementById('modalImagen');
    if (!modal) return;
    
    const imagenes = JSON.parse(modal.dataset.imagenes || '[]');
    let indice = parseInt(modal.dataset.indiceActual || 0);
    indice = (indice + 1) % imagenes.length;
    
    modal.dataset.indiceActual = indice;
    document.getElementById('modalImagenImg').src = imagenes[indice];
}

function eliminarFoto(productoId, index) {
    if (fotosSeleccionadas[productoId]) {
        fotosSeleccionadas[productoId].splice(index, 1);
        const productoCard = document.querySelector(`[data-producto-id="${productoId}"]`);
        if (productoCard) {
            const input = productoCard.querySelector('input[type="file"]');
            if (input) actualizarPreviewFotos(input);
        }
    }
}

function agregarFotoTela(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) return;
    
    const productoId = productoCard.dataset.productoId;
    if (!telasSeleccionadas[productoId]) telasSeleccionadas[productoId] = [];
    
    // Obtener el índice de la prenda (número de prenda en el formulario)
    const numeroPrenda = productoCard.querySelector('.numero-producto')?.textContent || '1';
    const prendaIndex = parseInt(numeroPrenda) - 1; // Convertir a índice (0-based)
    
    console.log('📁 Agregando foto de tela a memoria para prenda índice:', prendaIndex);
    Array.from(input.files).forEach((file, fileIndex) => {
        telasSeleccionadas[productoId].push(file);
        // Agregar al global con información del índice de prenda
        // IMPORTANTE: Solo guardar la PRIMERA tela por prenda (imagen_tela es un campo singular)
        if (fileIndex === 0) {
            if (!window.imagenesEnMemoria.telaConIndice) {
                window.imagenesEnMemoria.telaConIndice = [];
            }
            window.imagenesEnMemoria.telaConIndice.push({
                file: file,
                prendaIndex: prendaIndex
            });
            console.log(`✅ Foto de tela guardada para prenda ${prendaIndex}: ${file.name}`);
        }
    });
    const container = productoCard.querySelector('.foto-tela-preview');
    if (container) mostrarPreviewFoto(input, container);
}

function mostrarPreviewFoto(input, container) {
    const fotosExistentes = container.querySelectorAll('div[data-foto]').length;
    const fotosNuevas = input.files.length;
    if (fotosExistentes + fotosNuevas > 3) {
        alert('Máximo 3 fotos permitidas');
        return;
    }
    if (!container.style.display) container.style.cssText = 'display: grid; grid-template-columns: repeat(3, 60px); gap: 0.4rem; margin-top: 0.5rem;';
    
    Array.from(input.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.setAttribute('data-foto', 'true');
            preview.style.cssText = 'position: relative; width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f0f0f0; cursor: pointer;';
            const imagenSrc = e.target.result;
            preview.innerHTML = `
                <img src="${imagenSrc}" style="width: 100%; height: 100%; object-fit: cover;">
                <span style="position: absolute; top: 1px; left: 1px; background: #0066cc; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold;">${fotosExistentes + index + 1}</span>
                <button type="button" onclick="event.stopPropagation(); this.closest('div').remove()" style="position: absolute; top: 1px; left: 1px; background: #f44336; color: white; border: none; border-radius: 50%; width: 16px; height: 16px; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; padding: 0; opacity: 0; transition: opacity 0.2s;">✕</button>
            `;
            
            // Agregar evento para abrir modal al hacer clic en la imagen
            preview.addEventListener('click', function(e) {
                if (e.target.tagName !== 'BUTTON') {
                    abrirModalImagenTela(imagenSrc);
                }
            });
            
            preview.addEventListener('mouseenter', function() {
                this.querySelector('button').style.opacity = '1';
                this.querySelector('span').style.opacity = '0';
            });
            preview.addEventListener('mouseleave', function() {
                this.querySelector('button').style.opacity = '0';
                this.querySelector('span').style.opacity = '1';
            });
            container.appendChild(preview);
        };
        reader.readAsDataURL(file);
    });
    input.value = '';
}

// Función para abrir modal de imagen de tela
function abrirModalImagenTela(imagenSrc) {
    let modal = document.getElementById('modalImagenTela');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modalImagenTela';
        modal.style.cssText = 'display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 10000; align-items: center; justify-content: center;';
        modal.innerHTML = `
            <div style="position: relative; max-width: 600px; max-height: 600px; display: flex; align-items: center; justify-content: center;">
                <img id="modalImagenTelaImg" src="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <button type="button" onclick="cerrarModalImagenTela()" style="position: absolute; top: 10px; right: 10px; background: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; color: #333;">✕</button>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    modal.style.display = 'flex';
    document.getElementById('modalImagenTelaImg').src = imagenSrc;
}

function cerrarModalImagenTela() {
    const modal = document.getElementById('modalImagenTela');
    if (modal) modal.style.display = 'none';
}

// Función para abrir modal de imagen de prenda
function abrirModalImagenPrenda(imagenes, indiceInicial = 0) {
    let modal = document.getElementById('modalImagenPrenda');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modalImagenPrenda';
        modal.style.cssText = 'display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 10000; align-items: center; justify-content: center;';
        modal.innerHTML = `
            <div style="position: relative; max-width: 600px; max-height: 600px; display: flex; align-items: center; justify-content: center;">
                <img id="modalImagenPrendaImg" src="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <button type="button" onclick="cerrarModalImagenPrenda()" style="position: absolute; top: 10px; right: 10px; background: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; color: #333;">✕</button>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    modal.style.display = 'flex';
    modal.dataset.imagenes = JSON.stringify(imagenes);
    modal.dataset.indiceActual = indiceInicial;
    document.getElementById('modalImagenPrendaImg').src = imagenes[indiceInicial];
}

function cerrarModalImagenPrenda() {
    const modal = document.getElementById('modalImagenPrenda');
    if (modal) modal.style.display = 'none';
}

// ============ BÚSQUEDA DE PRENDAS ============

function buscarPrendas(input) {
    const valor = input.value.toLowerCase();
    const suggestions = input.closest('.prenda-search-container').querySelector('.prenda-suggestions');
    const items = suggestions.querySelectorAll('.prenda-suggestion-item');
    
    if (valor.length === 0) {
        suggestions.classList.remove('show');
        return;
    }
    
    let hayCoincidencias = false;
    items.forEach(item => {
        if (item.textContent.toLowerCase().includes(valor)) {
            item.style.display = 'block';
            hayCoincidencias = true;
        } else {
            item.style.display = 'none';
        }
    });
    suggestions.classList.toggle('show', hayCoincidencias);
}

function seleccionarPrenda(valor, element) {
    const input = element.closest('.prenda-search-container').querySelector('.prenda-search-input');
    input.value = valor;
    input.closest('.prenda-search-container').querySelector('.prenda-suggestions').classList.remove('show');
    actualizarResumenFriendly();
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.prenda-search-container')) {
        document.querySelectorAll('.prenda-suggestions').forEach(s => s.classList.remove('show'));
    }
});

// ============ SECCIONES ============

function toggleSeccion(btn) {
    const content = btn.closest('.producto-section').querySelector('.section-content');
    const icon = btn.querySelector('i.fa-chevron-down');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        if (icon) icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}

// ============ TÉCNICAS ============

function agregarTecnica() {
    const selector = document.getElementById('selector_tecnicas');
    const tecnica = selector.value;
    if (!tecnica) {
        alert('Por favor selecciona una técnica');
        return;
    }
    const contenedor = document.getElementById('tecnicas_seleccionadas');
    if (Array.from(contenedor.children).some(tag => tag.textContent.includes(tecnica))) {
        alert('Esta técnica ya está agregada');
        return;
    }
    const tag = document.createElement('div');
    tag.style.cssText = 'background: #3498db; color: white; padding: 6px 12px; border-radius: 20px; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600;';
    tag.innerHTML = `
        <input type="hidden" name="tecnicas[]" value="${tecnica}">
        <span>${tecnica}</span>
        <button type="button" onclick="this.closest('div').remove()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem; padding: 0; line-height: 1;">✕</button>
    `;
    contenedor.appendChild(tag);
    selector.value = '';
}

// ============ OBSERVACIONES ============

function agregarObservacion() {
    const contenedor = document.getElementById('observaciones_lista');
    const fila = document.createElement('div');
    fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
    fila.innerHTML = `
        <input type="text" name="observaciones_generales[]" class="input-large" placeholder="Escribe una observación..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
        <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
            <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="observaciones_check[]" style="width: 20px; height: 20px; cursor: pointer;">
            </div>
            <div class="obs-text-mode" style="display: none; flex: 1;">
                <input type="text" name="observaciones_valor[]" placeholder="Valor..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            </div>
            <button type="button" class="obs-toggle-btn" style="background: #3498db; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">✓/✎</button>
        </div>
        <button type="button" onclick="this.closest('div').remove()" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
    `;
    contenedor.appendChild(fila);
    
    const toggleBtn = fila.querySelector('.obs-toggle-btn');
    const checkboxMode = fila.querySelector('.obs-checkbox-mode');
    const textMode = fila.querySelector('.obs-text-mode');
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (checkboxMode.style.display === 'none') {
            checkboxMode.style.display = 'block';
            textMode.style.display = 'none';
            toggleBtn.style.background = '#3498db';
        } else {
            checkboxMode.style.display = 'none';
            textMode.style.display = 'block';
            toggleBtn.style.background = '#ff9800';
        }
    });
}
