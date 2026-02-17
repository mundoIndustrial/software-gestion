/**
 * SISTEMA DE COTIZACIONES - GESTIÓN DE PRODUCTOS
 * Responsabilidad: Agregar, eliminar, buscar y gestionar productos
 */

let productosCount = 0;

const MAX_FOTOS_PRENDA = 3;
const MAX_FOTOS_TELA = 3;

const DROPZONE_PASTE_HINT_TEXT = 'Pega la imagen con Ctrl+V';

let dropzoneDestinoPegado = null;
let pasteListenerRegistrado = false;

// Usar window para que sean accesibles desde otros módulos
if (!window.fotosSeleccionadas) {
    window.fotosSeleccionadas = {};
}

function clipboardTieneImagen() {
    try {
        if (!navigator.clipboard || typeof navigator.clipboard.read !== 'function') {
            return Promise.resolve(false);
        }
        return navigator.clipboard.read().then((items) => {
            for (const item of items) {
                if (!item || !item.types) continue;
                if (item.types.some(t => typeof t === 'string' && t.startsWith('image/'))) {
                    return true;
                }
            }
            return false;
        }).catch(() => false);
    } catch (_) {
        return Promise.resolve(false);
    }
}

function aplicarPasteHintVisual(dropZone) {
    if (!dropZone) return;

    if (!dropZone.style.position || dropZone.style.position === 'static') {
        dropZone.style.position = 'relative';
    }

    let overlay = dropZone.querySelector('[data-dropzone-paste-overlay="1"]');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.setAttribute('data-dropzone-paste-overlay', '1');
        overlay.style.cssText = [
            'position:absolute',
            'inset:0',
            'display:flex',
            'align-items:center',
            'justify-content:center',
            'background:rgba(16,185,129,0.10)',
            'border-radius:6px',
            'font-weight:800',
            'color:#047857',
            'letter-spacing:0.3px',
            'pointer-events:none',
            'text-transform:uppercase',
            'font-size:0.78rem'
        ].join(';');
        overlay.textContent = DROPZONE_PASTE_HINT_TEXT;
        dropZone.appendChild(overlay);
    }
}

function limpiarPasteHintVisual(dropZone) {
    if (!dropZone) return;
    const overlay = dropZone.querySelector('[data-dropzone-paste-overlay="1"]');
    if (overlay) overlay.remove();
}

function adjuntarPasteHintEnDropzone(dropZone) {
    if (!dropZone) return;
    if (dropZone.getAttribute('data-paste-hint-bound') === '1') return;
    dropZone.setAttribute('data-paste-hint-bound', '1');

    dropZone.addEventListener('mouseenter', () => {
        dropzoneDestinoPegado = dropZone;

        // No interferir con el estado drag-over
        if (dropZone.classList && dropZone.classList.contains('drag-over')) return;

        clipboardTieneImagen().then((tiene) => {
            // Si el mouse ya no está encima, no mostrar
            if (!dropZone.matches(':hover')) return;
            if (dropZone.classList && dropZone.classList.contains('drag-over')) return;

            if (tiene) {
                aplicarPasteHintVisual(dropZone);
            }
        });
    });

    dropZone.addEventListener('mouseleave', () => {
        if (dropzoneDestinoPegado === dropZone) {
            dropzoneDestinoPegado = null;
        }
        limpiarPasteHintVisual(dropZone);
    });

    // Si se empieza a arrastrar, quitar hint de pegado
    dropZone.addEventListener('dragover', () => {
        limpiarPasteHintVisual(dropZone);
    });

    if (!pasteListenerRegistrado) {
        pasteListenerRegistrado = true;
        document.addEventListener('paste', (e) => {
            try {
                if (!dropzoneDestinoPegado) return;

                const dtItems = e.clipboardData && e.clipboardData.items ? Array.from(e.clipboardData.items) : [];
                const archivos = dtItems
                    .filter(i => i && i.kind === 'file')
                    .map(i => i.getAsFile && i.getAsFile())
                    .filter(f => f && f.type && f.type.startsWith('image/'));

                if (archivos.length === 0) return;

                const input = dropzoneDestinoPegado.querySelector('input[type="file"]')
                    || (dropzoneDestinoPegado.closest('label') ? dropzoneDestinoPegado.closest('label').querySelector('input[type="file"]') : null);

                if (!input) return;

                const dataTransfer = new DataTransfer();
                archivos.forEach((f) => dataTransfer.items.add(f));
                input.files = dataTransfer.files;

                // Disparar el flujo normal (prenda/tela) del input
                input.dispatchEvent(new Event('change', { bubbles: true }));

                try {
                    limpiarPasteHintVisual(dropzoneDestinoPegado);
                } catch (_) {}
            } catch (_) {}
        });
    }
}

function adjuntarPasteHintEnProducto(productoRoot) {
    if (!productoRoot) return;

    // Dropzone prenda (label del input de fotos)
    const dropzonesPrenda = productoRoot.querySelectorAll('label[ondrop*="manejarDrop"], label[ondrop*="event.dataTransfer"], label[ondragover]');
    dropzonesPrenda.forEach((dz) => adjuntarPasteHintEnDropzone(dz));

    // Dropzones de tela (label dentro de fila-tela)
    const dropzonesTela = productoRoot.querySelectorAll('.fila-tela label');
    dropzonesTela.forEach((dz) => adjuntarPasteHintEnDropzone(dz));
}

document.addEventListener('DOMContentLoaded', () => {
    try {
        document.querySelectorAll('.producto-card').forEach((card) => adjuntarPasteHintEnProducto(card));
    } catch (_) {}
});
if (!window.telasSeleccionadas) {
    window.telasSeleccionadas = {};
}

// Rastrear fotos eliminadas del servidor (para no duplicar al guardar)
if (!window.fotosEliminadasServidor) {
    window.fotosEliminadasServidor = {
        prendas: [], // IDs de fotos de prenda eliminadas
        telas: []    // IDs de fotos de tela eliminadas
    };
}

// ============ PRODUCTOS ============

function agregarProductoFriendly() {
    productosCount++;
    const template = document.getElementById('productoTemplate');
    const clone = template.content.cloneNode(true);
    
    // DEBUG: Verificar que el campo hidden existe en el template
    const hiddenEnTemplate = template.content.querySelector('.tallas-hidden');
    console.log(' Campo hidden en template:', !!hiddenEnTemplate, hiddenEnTemplate);
    
    clone.querySelector('.numero-producto').textContent = productosCount;
    const productoId = 'producto-' + Date.now() + '-' + productosCount;
    clone.querySelector('.producto-card').dataset.productoId = productoId;
    window.fotosSeleccionadas[productoId] = [];
    // Inicializar telas como objeto con índices (no como array)
    window.telasSeleccionadas[productoId] = {
        '0': [] // La primera tela con índice 0
    };
    const container = document.getElementById('productosContainer');
    container.appendChild(clone);

    // Adjuntar ayuda visual de pegado a los nuevos dropzones del clon
    try {
        const nuevoProducto = container.lastElementChild;
        if (nuevoProducto) {
            adjuntarPasteHintEnProducto(nuevoProducto);
        }
    } catch (_) {}
    
    // DEBUG: Verificar que el campo hidden existe después de clonar
    setTimeout(() => {
        const nuevoProducto = container.lastElementChild;
        const hiddenEnClon = nuevoProducto.querySelector('.tallas-hidden');
        console.log(' Campo hidden en clon:', !!hiddenEnClon, hiddenEnClon);
        console.log(' Todos los hidden en documento:', document.querySelectorAll('.tallas-hidden').length);
    }, 100);
    
    // Scroll automático a la nueva prenda agregada
    setTimeout(() => {
        const nuevaPrenda = container.lastElementChild;
        if (nuevaPrenda) {
            nuevaPrenda.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 100);
}

function eliminarProductoFriendly(btn) {
    const productoCard = btn.closest('.producto-card');
    const numeroPrenda = productoCard.querySelector('.numero-producto').textContent;
    
    // Modal de confirmación con SweetAlert
    Swal.fire({
        title: '¿Eliminar Prenda?',
        text: `¿Estás seguro de que deseas eliminar la PRENDA ${numeroPrenda}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            productoCard.remove();
            renumerarPrendas();
            actualizarResumenFriendly();
            
            // Toast de éxito
            Swal.fire({
                title: '¡Eliminada!',
                text: `Prenda ${numeroPrenda} eliminada exitosamente`,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}

// Función para mostrar toast
function mostrarToast(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.textContent = mensaje;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 6px;
        font-weight: 600;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        ${tipo === 'success' ? 'background: #10b981; color: white;' : 'background: #3498db; color: white;'}
    `;
    
    document.body.appendChild(toast);
    
    // Remover toast después de 3 segundos
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function renumerarPrendas() {
    const prendas = document.querySelectorAll('.producto-card');
    prendas.forEach((prenda, index) => {
        prenda.querySelector('.numero-producto').textContent = index + 1;
    });
    productosCount = prendas.length;
}

//  CORREGIDO: NO agregar prenda por defecto - El usuario debe agregarla explícitamente
// Esto evitaba la duplicación de prendas cuando se guardaba sin llenar la prenda automática
// document.addEventListener('DOMContentLoaded', function() {
//     const container = document.getElementById('productosContainer');
//     if (container && container.children.length === 0) {
//         agregarProductoFriendly();
//     }
// });

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

function manejarDrop(event, dropZone) {
    event.preventDefault();
    event.stopPropagation();
    // Si no se pasa dropZone, usar event.currentTarget (para compatibilidad)
    if (!dropZone) {
        dropZone = event.currentTarget;
    }
    try {
        limpiarDropzoneVisual(dropZone);
    } catch (_) {}

    // Si el dropzone pertenece a una fila de tela, manejar como fotos de tela
    const filaTela = dropZone.closest('.fila-tela');
    if (filaTela) {
        agregarFotosTelaDesdeDrop(event.dataTransfer.files, dropZone);
        return;
    }

    // Caso normal: fotos de prenda
    agregarFotos(event.dataTransfer.files, dropZone);
}

function manejarDragOver(event, dropZone) {
    event.preventDefault();
    event.stopPropagation();

    if (!dropZone) {
        dropZone = event.currentTarget;
    }

    aplicarDropzoneVisual(dropZone);
}

function manejarDragLeave(event, dropZone) {
    event.preventDefault();
    event.stopPropagation();

    if (!dropZone) {
        dropZone = event.currentTarget;
    }

    limpiarDropzoneVisual(dropZone);
}

function aplicarDropzoneVisual(dropZone) {
    if (!dropZone) return;

    dropZone.classList.add('drag-over');
    dropZone.style.background = '#e8f1ff';
    dropZone.style.borderColor = '#1e40af';

    if (!dropZone.style.position || dropZone.style.position === 'static') {
        dropZone.style.position = 'relative';
    }

    let overlay = dropZone.querySelector('[data-dropzone-overlay="1"]');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.setAttribute('data-dropzone-overlay', '1');
        overlay.style.cssText = [
            'position:absolute',
            'inset:0',
            'display:flex',
            'align-items:center',
            'justify-content:center',
            'background:rgba(30,64,175,0.08)',
            'border-radius:6px',
            'font-weight:800',
            'color:#1e40af',
            'letter-spacing:0.3px',
            'pointer-events:none',
            'text-transform:uppercase',
            'font-size:0.85rem'
        ].join(';');
        overlay.textContent = 'Suelta para adjuntar';
        dropZone.appendChild(overlay);
    }
}

function limpiarDropzoneVisual(dropZone) {
    if (!dropZone) return;

    dropZone.classList.remove('drag-over');
    dropZone.style.background = '';
    dropZone.style.borderColor = '';

    const overlay = dropZone.querySelector('[data-dropzone-overlay="1"]');
    if (overlay) overlay.remove();
}

function agregarFotosTelaDesdeDrop(files, dropZone) {
    const productoCard = dropZone.closest('.producto-card');
    if (!productoCard) return;

    const productoId = productoCard.dataset.productoId;
    const filaTelaActual = dropZone.closest('.fila-tela');
    const telaIndex = filaTelaActual ? filaTelaActual.getAttribute('data-tela-index') : '0';

    if (!window.telasSeleccionadas[productoId]) window.telasSeleccionadas[productoId] = {};
    if (!window.telasSeleccionadas[productoId][telaIndex]) window.telasSeleccionadas[productoId][telaIndex] = [];

    const container = productoCard.querySelector(`.fila-tela[data-tela-index="${telaIndex}"] .foto-tela-preview`);
    if (!container) return;

    // Contar fotos guardadas + nuevas en ESTE contenedor de tela
    const fotosGuardadas = Array.from(container.querySelectorAll('[data-foto]:not([data-file-name])')).length;
    const fotosNuevasActuales = window.telasSeleccionadas[productoId][telaIndex].length;
    const totalActual = fotosGuardadas + fotosNuevasActuales;
    const espacioDisponible = MAX_FOTOS_TELA - totalActual;

    if (espacioDisponible <= 0) {
        try {
            Swal.fire({
                icon: 'warning',
                title: 'Límite de imágenes',
                text: `Máximo ${MAX_FOTOS_TELA} imágenes por tela`,
                confirmButtonColor: '#1e40af'
            });
        } catch (_) {
            alert(`Máximo ${MAX_FOTOS_TELA} imágenes por tela`);
        }
        return;
    }

    const soloImagenes = Array.from(files).filter(f => f && f.type && f.type.startsWith('image/'));
    const fotosParaAgregar = soloImagenes.slice(0, espacioDisponible);
    fotosParaAgregar.forEach((file) => {
        window.telasSeleccionadas[productoId][telaIndex].push(file);
    });

    if (soloImagenes.length > fotosParaAgregar.length) {
        const noAgregadas = soloImagenes.length - fotosParaAgregar.length;
        try {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'info',
                title: `Se omitieron ${noAgregadas} imagen(es) por el límite de ${MAX_FOTOS_TELA}`,
                showConfirmButton: false,
                timer: 2500
            });
        } catch (_) {}
    }

    // Mostrar preview SOLO de las fotos nuevas agregadas
    mostrarPreviewFoto(fotosParaAgregar, container, container.querySelectorAll('div[data-foto]').length);
}

function agregarFotos(files, dropZone) {
    const productoCard = dropZone.closest('.producto-card');
    const productoId = productoCard ? productoCard.dataset.productoId : 'default';
    if (!window.fotosSeleccionadas[productoId]) window.fotosSeleccionadas[productoId] = [];
    
    // Obtener índice de prenda (posición en la lista de productos)
    // Usar querySelectorAll con el índice real
    const allProductos = document.querySelectorAll('.producto-card');
    let prendaIndex = -1;
    for (let i = 0; i < allProductos.length; i++) {
        if (allProductos[i] === productoCard) {
            prendaIndex = i;
            break;
        }
    }
    



    
    // Contar imágenes guardadas SOLO del preview de prenda (no mezclar con telas)
    let contenedorPreviewPrenda = null;
    const label = dropZone.closest('label');
    if (label && label.parentElement) {
        contenedorPreviewPrenda = label.parentElement.querySelector('.fotos-preview');
    }
    if (!contenedorPreviewPrenda) {
        contenedorPreviewPrenda = productoCard ? productoCard.querySelector('.fotos-preview') : null;
    }

    const fotosGuardadas = contenedorPreviewPrenda
        ? Array.from(contenedorPreviewPrenda.querySelectorAll('[data-foto]:not([data-foto-nueva])')).length
        : 0;
    const fotosNuevasActuales = window.fotosSeleccionadas[productoId].length;
    const totalFotosActuales = fotosGuardadas + fotosNuevasActuales;
    

    
    // Calcular cuántas fotos podemos agregar
    const espacioDisponible = MAX_FOTOS_PRENDA - totalFotosActuales;
    
    if (espacioDisponible <= 0) {
        try {
            Swal.fire({
                icon: 'warning',
                title: 'Límite de imágenes',
                text: `Máximo ${MAX_FOTOS_PRENDA} imágenes por prenda`,
                confirmButtonColor: '#1e40af'
            });
        } catch (_) {
            alert(`Máximo ${MAX_FOTOS_PRENDA} imágenes por prenda`);
        }
        return;
    }
    

    
    // Agregar solo las fotos que caben en el límite
    const fotosParaAgregar = Array.from(files).slice(0, espacioDisponible);
    
    fotosParaAgregar.forEach((file, fileIndex) => {
        if (file && !file.__uid) {
            file.__uid = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        }
        window.fotosSeleccionadas[productoId].push(file);
        
        // Guardar con índice de prenda (similar a telaConIndice)
        if (!window.imagenesEnMemoria.prendaConIndice) {
            window.imagenesEnMemoria.prendaConIndice = [];
        }
        window.imagenesEnMemoria.prendaConIndice.push({
            file: file,
            uid: file ? file.__uid : null,
            prendaIndex: prendaIndex
        });
        

    });
    
    // Mostrar mensaje si no se pudieron agregar todas las fotos seleccionadas
    if (files.length > fotosParaAgregar.length) {
        const noAgregadas = files.length - fotosParaAgregar.length;
        try {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'info',
                title: `Se omitieron ${noAgregadas} imagen(es) por el límite de ${MAX_FOTOS_PRENDA}`,
                showConfirmButton: false,
                timer: 2500
            });
        } catch (_) {}
    }
    actualizarPreviewFotos(dropZone);
}

function actualizarPreviewFotos(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) {

        return;
    }
    const productoId = productoCard.dataset.productoId || 'default';
    
    let container = null;
    const label = input.closest('label');


    
    if (label && label.parentElement) {
        container = label.parentElement.querySelector('.fotos-preview');

    }
    if (!container) {
        container = productoCard.querySelector('.fotos-preview');

    }
    if (!container) {

        return;
    }
    

    
    // NO limpiar el contenedor - solo agregar las nuevas fotos (File objects)
    const fotos = window.fotosSeleccionadas[productoId] || [];
    

    
    if (fotos.length === 0) {

        return;
    }
    
    // Asegurar UID por archivo
    fotos.forEach((f) => {
        if (f && !f.__uid) {
            f.__uid = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        }
    });

    // Obtener las fotos que ya están en el preview (por UID)
    const fotosEnPreview = Array.from(container.querySelectorAll('[data-foto-nueva]')).map(el => el.dataset.fileUid);
    
    // Filtrar solo las fotos que NO están en el preview
    const fotosNuevasParaMostrar = fotos.filter(file => file && !fotosEnPreview.includes(String(file.__uid || '')));
    

    
    fotosNuevasParaMostrar.forEach((file, index) => {
        // Usar UID estable del archivo (para soportar nombres repetidos/pegados)
        const fotoId = file.__uid || (Date.now() + '-' + Math.random().toString(36).substr(2, 9));
        file.__uid = fotoId;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.setAttribute('data-foto', 'true');
            preview.setAttribute('data-foto-nueva', 'true'); // Marcar como foto nueva
            preview.setAttribute('data-foto-id', fotoId); // ID único en lugar de índice
            preview.setAttribute('data-file-uid', String(fotoId));
            preview.setAttribute('data-file-name', file.name || ''); // Guardar nombre del archivo
            preview.style.cssText = 'position: relative; width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f0f0f0; cursor: pointer;';
            const imagenSrc = e.target.result;
            
            // Contar el número de fotos en el preview para mostrar el número correcto
            const numeroFoto = container.querySelectorAll('[data-foto]').length + 1;
            
            preview.innerHTML = `
                <img src="${imagenSrc}" style="width: 100%; height: 100%; object-fit: cover;">
                <span style="position: absolute; top: 1px; left: 1px; background: #0066cc; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold;">${numeroFoto}</span>
                <button type="button" onclick="event.stopPropagation(); eliminarFotoById('${productoId}', '${fotoId}')" style="position: absolute; top: 1px; left: 1px; background: #f44336; color: white; border: none; border-radius: 50%; width: 16px; height: 16px; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; padding: 0; opacity: 0; transition: opacity 0.2s;">✕</button>
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
    const productoCard = document.querySelector(`[data-producto-id="${productoId}"]`);
    if (!productoCard) return;
    
    const fotosPreview = productoCard.querySelector('.fotos-preview');
    if (!fotosPreview) return;
    
    // Obtener todas las fotos en el preview (guardadas + nuevas)
    const todasLasFotos = Array.from(fotosPreview.querySelectorAll('[data-foto]'));
    
    // Obtener la foto a eliminar por índice
    if (todasLasFotos[index]) {
        const fotoAEliminar = todasLasFotos[index];
        const esGuardada = fotoAEliminar.hasAttribute('data-foto-guardada');
        const fileName = fotoAEliminar.getAttribute('data-file-name');
        const fotoId = fotoAEliminar.getAttribute('data-foto-id');
        if (esGuardada) {
            // Es una foto guardada - mostrar modal de confirmación
            Swal.fire({
                title: '¿Eliminar imagen?',
                text: 'Esta imagen se borrará definitivamente de la carpeta.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#757575',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const rutaFoto = fotoAEliminar.querySelector('img')?.src || '';
                    

                    
                    // Mostrar loading
                    Swal.fire({
                        title: 'Eliminando...',
                        html: '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;"><div style="width: 12px; height: 12px; border-radius: 50%; background: #f44336; animation: pulse 1.5s infinite;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #f44336; animation: pulse 1.5s infinite 0.3s;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #f44336; animation: pulse 1.5s infinite 0.6s;"></div></div><style>@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }</style>',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false
                    });
                    
                    // Enviar solicitud al backend para eliminar inmediatamente
                    fetch(window.location.origin + '/asesores/fotos/eliminar', {
                        // Nota: La ruta está dentro del grupo 'prefix(asesores)' en web.php, 
                        // así que la URL completa es /asesores/fotos/eliminar
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            ruta: rutaFoto,
                            cotizacion_id: window.cotizacionIdActual
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {

                            
                            // Eliminar del preview
                            fotoAEliminar.remove();
                            actualizarNumerosPreview(fotosPreview);
                            
                            Swal.fire({
                                title: '¡Eliminada!',
                                text: 'La imagen ha sido eliminada correctamente.',
                                icon: 'success',
                                confirmButtonColor: '#1e40af',
                                timer: 2000
                            });
                        } else {

                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'No se pudo eliminar la imagen.',
                                icon: 'error',
                                confirmButtonColor: '#1e40af'
                            });
                        }
                    })
                    .catch(error => {

                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo conectar con el servidor.',
                            icon: 'error',
                            confirmButtonColor: '#1e40af'
                        });
                    });
                }
            });
        } else {
            // Es una foto nueva - eliminarla directamente sin confirmar
            if (window.fotosSeleccionadas[productoId]) {
                // Encontrar el índice en fotosSeleccionadas por nombre
                const indexEnFotos = window.fotosSeleccionadas[productoId].findIndex(f => f.name === fileName);
                if (indexEnFotos !== -1) {
                    window.fotosSeleccionadas[productoId].splice(indexEnFotos, 1);

                }
            }
            fotoAEliminar.remove();
            actualizarNumerosPreview(fotosPreview);
            
            // Actualizar imagenesEnMemoria
            if (window.imagenesEnMemoria && window.imagenesEnMemoria.prendaConIndice) {
                window.imagenesEnMemoria.prendaConIndice = window.imagenesEnMemoria.prendaConIndice.filter(item => 
                    !(item.file && item.file.name === fileName)
                );
            }
        }
    }
}

/**
 * Eliminar foto usando ID único (más seguro que índices)
 */
function eliminarFotoById(productoId, fotoId) {
    const productoCard = document.querySelector(`[data-producto-id="${productoId}"]`);
    if (!productoCard) return;
    
    const fotosPreview = productoCard.querySelector('.fotos-preview');
    if (!fotosPreview) return;
    
    // Encontrar la foto por su ID único
    const fotoAEliminar = fotosPreview.querySelector(`[data-foto-id="${fotoId}"]`);
    if (!fotoAEliminar) {

        return;
    }
    
    const esGuardada = fotoAEliminar.hasAttribute('data-foto-guardada');
    const fileUid = fotoAEliminar.getAttribute('data-file-uid');
    const fileName = fotoAEliminar.getAttribute('data-file-name');
    if (esGuardada) {
        // Es una foto guardada - mostrar modal de confirmación
        Swal.fire({
            title: '¿Eliminar imagen?',
            text: 'Esta imagen se borrará definitivamente de la carpeta.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f44336',
            cancelButtonColor: '#757575',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const rutaFoto = fotoAEliminar.querySelector('img')?.src || '';
                

                
                // Mostrar loading
                Swal.fire({
                    title: 'Eliminando...',
                    html: '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;"><div style="width: 12px; height: 12px; border-radius: 50%; background: #f44336; animation: pulse 1.5s infinite;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #f44336; animation: pulse 1.5s infinite 0.3s;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #f44336; animation: pulse 1.5s infinite 0.6s;"></div></div><style>@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }</style>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false
                });
                
                // Enviar solicitud al backend para eliminar inmediatamente
                fetch(window.location.origin + '/asesores/fotos/eliminar', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ruta: rutaFoto,
                        cotizacion_id: window.cotizacionIdActual
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {

                        
                        // Eliminar del preview
                        fotoAEliminar.remove();
                        actualizarNumerosPreview(fotosPreview);
                        
                        Swal.fire({
                            title: '¡Eliminada!',
                            text: 'La imagen ha sido eliminada correctamente.',
                            icon: 'success',
                            confirmButtonColor: '#1e40af',
                            timer: 2000
                        });
                    } else {

                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar la imagen.',
                            icon: 'error',
                            confirmButtonColor: '#1e40af'
                        });
                    }
                })
                .catch(error => {

                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo conectar con el servidor.',
                        icon: 'error',
                        confirmButtonColor: '#1e40af'
                    });
                });
            }
        });
    } else {
        // Es una foto nueva - eliminarla directamente sin confirmar
        if (window.fotosSeleccionadas && window.fotosSeleccionadas[productoId]) {
            // Encontrar el índice en fotosSeleccionadas por UID (más robusto que name)
            const indexEnFotos = window.fotosSeleccionadas[productoId].findIndex(f => String(f && f.__uid) === String(fileUid));
            if (indexEnFotos !== -1) {
                window.fotosSeleccionadas[productoId].splice(indexEnFotos, 1);

            }
        }
        fotoAEliminar.remove();
        actualizarNumerosPreview(fotosPreview);
        
        // Actualizar imagenesEnMemoria
        if (window.imagenesEnMemoria && window.imagenesEnMemoria.prendaConIndice) {
            window.imagenesEnMemoria.prendaConIndice = window.imagenesEnMemoria.prendaConIndice.filter(item => 
                !(String(item && item.uid) === String(fileUid))
            );
        }
    }
}

/**
 * Actualizar números de fotos después de eliminar una
 */
function actualizarNumerosPreview(fotosPreview) {
    const todasLasFotos = Array.from(fotosPreview.querySelectorAll('[data-foto]'));
    todasLasFotos.forEach((fotoElement, index) => {
        const spanNumero = fotoElement.querySelector('span');
        if (spanNumero) {
            spanNumero.textContent = index + 1;
        }
    });

}

function agregarFotoTela(input) {
    console.log(' agregarFotoTela LLAMADA:', { 
        inputName: input.name,
        files: input.files.length,
        tiempoActual: new Date().toLocaleTimeString()
    });
    
    const productoCard = input.closest('.producto-card');
    if (!productoCard) {

        return;
    }
    
    const productoId = productoCard.dataset.productoId;
    const filaTelaActual = input.closest('.fila-tela');
    const telaIndex = filaTelaActual ? filaTelaActual.getAttribute('data-tela-index') : '0';
    
    if (!window.telasSeleccionadas[productoId]) window.telasSeleccionadas[productoId] = {};
    if (!window.telasSeleccionadas[productoId][telaIndex]) window.telasSeleccionadas[productoId][telaIndex] = [];
    
    // Obtener índice de prenda (posición en la lista de productos)
    const allProductos = document.querySelectorAll('.producto-card');
    let prendaIndex = -1;
    for (let i = 0; i < allProductos.length; i++) {
        if (allProductos[i] === productoCard) {
            prendaIndex = i;
            break;
        }
    }
    





    
    // Contar fotos existentes ANTES de agregar
    const fotosExistentesAntes = window.telasSeleccionadas[productoId][telaIndex].length;
    
    // Array para guardar las fotos nuevas agregadas
    const fotosNuevasAgregadas = [];
    
    Array.from(input.files).forEach((file, fileIndex) => {
        // Máximo 3 fotos por tela
        if (window.telasSeleccionadas[productoId][telaIndex].length < 3) {
            window.telasSeleccionadas[productoId][telaIndex].push(file);
            fotosNuevasAgregadas.push(file); // Guardar para la iteración de preview

        }
    });
    
    // Mostrar estado actual de telasSeleccionadas
    console.log(' Estado actual de telasSeleccionadas:', JSON.stringify({
        productoId,
        telaIndex,
        fotosAlmacenadas: window.telasSeleccionadas[productoId][telaIndex].length,
        fotosNuevasAgregadas: fotosNuevasAgregadas.length,
        estructuraCompleta: window.telasSeleccionadas
    }));
    
    const container = productoCard.querySelector(`.fila-tela[data-tela-index="${telaIndex}"] .foto-tela-preview`);
    if (container) {

        // Pasar SOLO las fotos nuevas, no input.files
        mostrarPreviewFoto(fotosNuevasAgregadas, container, fotosExistentesAntes);
    } else {

    }
}

function mostrarPreviewFoto(archivosNuevos, container, fotosExistentesAntes = 0) {
    const fotosExistentes = container.querySelectorAll('div[data-foto]').length;
    const fotosNuevas = archivosNuevos.length;
    if (fotosExistentes + fotosNuevas > 3) {
        alert('Máximo 3 fotos permitidas');
        return;
    }
    if (!container.style.display) container.style.cssText = 'display: grid; grid-template-columns: repeat(3, 60px); gap: 0.4rem; margin-top: 0.5rem;';
    
    // Iterar SOLO sobre las fotos nuevas agregadas (no sobre input.files)
    archivosNuevos.forEach((file, index) => {
        // UID estable por archivo
        if (file && !file.__uid) {
            file.__uid = Date.now() + '-' + Math.random().toString(36).substr(2, 9) + '-' + index;
        }
        const fotoTelaId = file.__uid;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.setAttribute('data-foto', 'true');
            preview.setAttribute('data-foto-tela-id', fotoTelaId); // ID único
            preview.setAttribute('data-file-uid', String(fotoTelaId));
            preview.setAttribute('data-file-name', file.name || '');
            preview.style.cssText = 'position: relative; width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f0f0f0; cursor: pointer;';
            const imagenSrc = e.target.result;
            
            // Contar el número correcto basado en todas las fotos en el contenedor
            const numeroFoto = container.querySelectorAll('div[data-foto]').length + 1;
            
            preview.innerHTML = `
                <img src="${imagenSrc}" style="width: 100%; height: 100%; object-fit: cover;">
                <span style="position: absolute; top: 1px; left: 1px; background: #0066cc; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold;">${numeroFoto}</span>
                <button type="button" onclick="event.stopPropagation(); eliminarFotoTelaById('${fotoTelaId}'); return false;" style="position: absolute; top: 1px; left: 1px; background: #f44336; color: white; border: none; border-radius: 50%; width: 16px; height: 16px; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; padding: 0; opacity: 0; transition: opacity 0.2s;">✕</button>
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

/**
 * Eliminar foto de tela usando ID único (dinámico)
 */
function eliminarFotoTelaById(fotoTelaId) {
    // Encontrar el contenedor de fotos de tela
    const fotoElement = document.querySelector(`[data-foto-tela-id="${fotoTelaId}"]`);
    if (!fotoElement) {

        return;
    }
    
    // Verificar si es una foto guardada en el servidor (tiene data-foto-id)
    const fotoImg = fotoElement.querySelector('img[data-foto-id]');
    const fotoIdServidor = fotoImg ? fotoImg.getAttribute('data-foto-id') : null;
    
    if (fotoIdServidor) {
        // Es una foto guardada en el servidor, registrar para eliminar al guardar
        if (!window.fotosEliminadasServidor) {
            window.fotosEliminadasServidor = { prendas: [], telas: [] };
        }
        if (!window.fotosEliminadasServidor.telas.includes(fotoIdServidor)) {
            window.fotosEliminadasServidor.telas.push(fotoIdServidor);

        }
    }
    
    // Obtener el contenedor (foto-tela-preview)
    const container = fotoElement.closest('.foto-tela-preview');
    if (!container) {

        return;
    }
    
    // Obtener la fila de tela para saber el índice
    const filaTelaActual = container.closest('.fila-tela');
    const telaIndex = filaTelaActual ? filaTelaActual.getAttribute('data-tela-index') : '0';
    
    const fileUid = fotoElement.getAttribute('data-file-uid');
    const fileName = fotoElement.getAttribute('data-file-name');
    

    
    // Eliminar la foto del DOM
    fotoElement.remove();
    
    // Actualizar números de las fotos restantes
    const todasLasFotos = Array.from(container.querySelectorAll('[data-foto]'));
    todasLasFotos.forEach((fotoEl, index) => {
        const spanNumero = fotoEl.querySelector('span');
        if (spanNumero) {
            spanNumero.textContent = index + 1;
        }
    });
    

    
    // Actualizar telasSeleccionadas (solo si es foto nueva, no guardada)
    if (!fotoIdServidor) {
        const productoCard = container.closest('.producto-card');
        if (productoCard) {
            const productoId = productoCard.dataset.productoId;
            if (window.telasSeleccionadas && window.telasSeleccionadas[productoId] && window.telasSeleccionadas[productoId][telaIndex]) {
                const indexEnTelas = window.telasSeleccionadas[productoId][telaIndex].findIndex(f => String(f && f.__uid) === String(fileUid));
                if (indexEnTelas !== -1) {
                    window.telasSeleccionadas[productoId][telaIndex].splice(indexEnTelas, 1);

                }
            }
        }
    }
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
    const container = input.closest('.prenda-search-container');
    
    // Validar que el contenedor existe
    if (!container) {

        return;
    }
    
    const suggestions = container.querySelector('.prenda-suggestions');
    
    // Validar que suggestions existe
    if (!suggestions) {

        return;
    }
    
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
    // Mostrar variantes dinámicamente
    mostrarSelectorVariantes(input);
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

    
    if (!selector) {

        return;
    }
    
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

/**
 * Previsualizar imagen de tela (máximo 3 imágenes)
 */
function previewTelaImagen(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = input.closest('.tela-preview');
            if (preview) {
                // Limpiar contenido anterior
                preview.innerHTML = '';
                
                // Crear imagen
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '4px';
                
                // Crear botón para eliminar
                const btnEliminar = document.createElement('button');
                btnEliminar.type = 'button';
                btnEliminar.innerHTML = '<i class="fas fa-times"></i>';
                btnEliminar.style.position = 'absolute';
                btnEliminar.style.top = '4px';
                btnEliminar.style.right = '4px';
                btnEliminar.style.background = 'rgba(255, 0, 0, 0.8)';
                btnEliminar.style.color = 'white';
                btnEliminar.style.border = 'none';
                btnEliminar.style.borderRadius = '50%';
                btnEliminar.style.width = '28px';
                btnEliminar.style.height = '28px';
                btnEliminar.style.cursor = 'pointer';
                btnEliminar.style.display = 'flex';
                btnEliminar.style.alignItems = 'center';
                btnEliminar.style.justifyContent = 'center';
                btnEliminar.style.opacity = '0';
                btnEliminar.style.transition = 'opacity 0.3s';
                btnEliminar.style.fontSize = '1.2rem';
                
                btnEliminar.onclick = function(e) {
                    e.preventDefault();
                    input.value = '';
                    preview.innerHTML = '<i class="fas fa-image" style="font-size: 2rem; color: #ccc;"></i>';

                };
                
                preview.appendChild(img);
                preview.appendChild(btnEliminar);
                
                // Mostrar botón al pasar mouse
                preview.onmouseover = function() {
                    btnEliminar.style.opacity = '1';
                };
                preview.onmouseout = function() {
                    btnEliminar.style.opacity = '0';
                };
                
                const index = preview.dataset.index || '?';

            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ============ GESTIÓN DE MÚLTIPLES TELAS ============

/**
 * Agregar una nueva fila de tela (color, tela, referencia, imagen)
 */
function agregarFilaTela(btn) {
    const productoCard = btn.closest('.producto-card');
    const tbody = productoCard.querySelector('.telas-tbody');
    
    if (!tbody) {

        return;
    }
    
    // Obtener el número de filas existentes para usar como índice
    const filasExistentes = tbody.querySelectorAll('.fila-tela');
    const nuevoIndice = filasExistentes.length;
    // Obtener la primera fila como template
    const primeraFila = tbody.querySelector('.fila-tela');
    if (!primeraFila) {

        return;
    }
    
    // Clonar la primera fila
    const nuevaFila = primeraFila.cloneNode(true);
    
    // Actualizar el atributo data-tela-index
    nuevaFila.setAttribute('data-tela-index', nuevoIndice);
    
    // Actualizar todos los nombres de inputs para usar el nuevo índice
    // IMPORTANTE: Reemplazar SOLO el número dentro de [telas][X] no otros índices
    nuevaFila.querySelectorAll('input, select, textarea').forEach(input => {
        const nameAttr = input.getAttribute('name');
        if (nameAttr && nameAttr.includes('[telas]')) {
            // Buscar el patrón [telas][número] y reemplazarlo con [telas][nuevoIndice]
            const nuevoName = nameAttr.replace(/\[telas\]\[\d+\]/, '[telas][' + nuevoIndice + ']');

            input.setAttribute('name', nuevoName);
        }
        // Limpiar valores
        input.value = '';
        if (input.type === 'checkbox') {
            input.checked = false;
        }
    });
    
    // Limpiar las previsualizaciones de fotos
    nuevaFila.querySelectorAll('.foto-tela-preview').forEach(preview => {
        preview.innerHTML = '';
    });
    
    // Mostrar el botón de eliminar en la nueva fila
    const btnEliminar = nuevaFila.querySelector('.btn-eliminar-tela');
    if (btnEliminar) {
        btnEliminar.style.display = 'block';
    }
    
    // Agregar la nueva fila a la tabla
    tbody.appendChild(nuevaFila);
    

    console.log(' Fila agregada - inputs actualizados:', {
        colorInput: nuevaFila.querySelector('.color-id-input')?.getAttribute('name'),
        telaInput: nuevaFila.querySelector('.tela-id-input')?.getAttribute('name'),
        fotosInput: nuevaFila.querySelector('.input-file-tela')?.getAttribute('name')
    });
    
    // Mostrar toast
    mostrarToast('Nueva tela agregada', 'success');
}

/**
 * Eliminar una fila de tela
 */
function eliminarFilaTela(btn) {
    const fila = btn.closest('.fila-tela');
    const tbody = fila.closest('.telas-tbody');
    
    if (!tbody) {

        return;
    }
    
    // Contar cuántas filas hay
    const filas = tbody.querySelectorAll('.fila-tela');
    
    // No permitir eliminar si es la única fila
    if (filas.length <= 1) {
        Swal.fire({
            title: 'No se puede eliminar',
            text: 'Debe haber al menos una fila de tela',
            icon: 'warning',
            confirmButtonColor: '#0066cc'
        });
        return;
    }
    
    // Confirmar eliminación
    Swal.fire({
        title: '¿Eliminar tela?',
        text: '¿Estás seguro de que deseas eliminar esta fila de tela?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fila.remove();

            
            // Mostrar toast
            mostrarToast('Tela eliminada', 'success');
        }
    });
}

