/**
 * SISTEMA DE COTIZACIONES - GESTIÓN DE PRODUCTOS
 * Responsabilidad: Agregar, eliminar, buscar y gestionar productos
 */

let productosCount = 0;

// Usar window para que sean accesibles desde otros módulos
if (!window.fotosSeleccionadas) {
    window.fotosSeleccionadas = {};
}
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
    dropZone.classList.remove('drag-over');
    agregarFotos(event.dataTransfer.files, dropZone);
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
    
    console.log('📁 Agregando fotos de prenda a memoria');
    console.log('📁 Producto ID:', productoId);
    console.log('📁 Índice de prenda:', prendaIndex);
    
    // Contar imágenes guardadas (desde cargarBorrador)
    const fotosGuardadas = Array.from(dropZone.closest('.producto-card').querySelectorAll('[data-foto]:not([data-foto-nueva])')).length;
    const fotosNuevasActuales = window.fotosSeleccionadas[productoId].length;
    const totalFotosActuales = fotosGuardadas + fotosNuevasActuales;
    
    console.log(`📊 Fotos guardadas: ${fotosGuardadas}, Fotos nuevas actuales: ${fotosNuevasActuales}, Total actual: ${totalFotosActuales}`);
    
    // Calcular cuántas fotos podemos agregar
    const espacioDisponible = 3 - totalFotosActuales;
    
    if (espacioDisponible <= 0) {
        console.warn(`⚠️ Límite de 3 fotos alcanzado. No se puede agregar más fotos.`);
        return;
    }
    
    console.log(` Espacio disponible para ${espacioDisponible} foto(s)`);
    
    // Agregar solo las fotos que caben en el límite
    const fotosParaAgregar = Array.from(files).slice(0, espacioDisponible);
    
    fotosParaAgregar.forEach((file, fileIndex) => {
        window.fotosSeleccionadas[productoId].push(file);
        
        // Guardar con índice de prenda (similar a telaConIndice)
        if (!window.imagenesEnMemoria.prendaConIndice) {
            window.imagenesEnMemoria.prendaConIndice = [];
        }
        window.imagenesEnMemoria.prendaConIndice.push({
            file: file,
            prendaIndex: prendaIndex
        });
        
        console.log(` Foto ${fileIndex + 1} de ${fotosParaAgregar.length} agregada: ${file.name} (Prenda ${prendaIndex})`);
    });
    
    // Mostrar mensaje si no se pudieron agregar todas las fotos seleccionadas
    if (files.length > fotosParaAgregar.length) {
        const noAgregadas = files.length - fotosParaAgregar.length;
        console.warn(`⚠️ Solo se agregaron ${fotosParaAgregar.length} de ${files.length} fotos. Límite de 3 fotos alcanzado.`);
    }
    actualizarPreviewFotos(dropZone);
}

function actualizarPreviewFotos(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) {
        console.warn('⚠️ No se encontró .producto-card');
        return;
    }
    const productoId = productoCard.dataset.productoId || 'default';
    
    let container = null;
    const label = input.closest('label');
    console.log('🔍 Buscando contenedor para fotos:');
    console.log('   - label:', label);
    
    if (label && label.parentElement) {
        container = label.parentElement.querySelector('.fotos-preview');
        console.log('   - Intentó label.parentElement.querySelector:', container);
    }
    if (!container) {
        container = productoCard.querySelector('.fotos-preview');
        console.log('   - Intentó productoCard.querySelector:', container);
    }
    if (!container) {
        console.warn(' No se encontró contenedor .fotos-preview');
        return;
    }
    
    console.log('✓ Contenedor encontrado:', container);
    
    // NO limpiar el contenedor - solo agregar las nuevas fotos (File objects)
    // Las imágenes guardadas ya están en el contenedor desde cargarBorrador()
    const fotos = window.fotosSeleccionadas[productoId] || [];
    
    console.log(`📸 Procesando ${fotos.length} fotos para producto ${productoId}`);
    
    if (fotos.length === 0) {
        console.log(' No hay fotos nuevas para mostrar');
        return;
    }
    
    // Obtener las fotos que ya están en el preview
    const fotosEnPreview = Array.from(container.querySelectorAll('[data-foto-nueva]')).map(el => el.dataset.fileName);
    
    // Filtrar solo las fotos que NO están en el preview
    const fotosNuevasParaMostrar = fotos.filter(file => !fotosEnPreview.includes(file.name));
    
    console.log(`📸 Mostrando ${fotosNuevasParaMostrar.length} fotos nuevas (${fotosEnPreview.length} ya en preview)`);
    
    fotosNuevasParaMostrar.forEach((file, index) => {
        // Generar un ID único para esta foto (usando timestamp + random)
        const fotoId = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.setAttribute('data-foto', 'true');
            preview.setAttribute('data-foto-nueva', 'true'); // Marcar como foto nueva
            preview.setAttribute('data-foto-id', fotoId); // ID único en lugar de índice
            preview.setAttribute('data-file-name', file.name); // Guardar nombre del archivo
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
        
        console.log(`🗑️ Intentando eliminar foto ${index + 1}:`, {
            esGuardada: esGuardada,
            fileName: fileName,
            fotoId: fotoId
        });
        
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
                    
                    console.log(`🗑️ Eliminando foto inmediatamente:`, rutaFoto);
                    
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
                            console.log(` Foto eliminada del servidor:`, rutaFoto);
                            
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
                            console.error(` Error al eliminar foto:`, data.message);
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'No se pudo eliminar la imagen.',
                                icon: 'error',
                                confirmButtonColor: '#1e40af'
                            });
                        }
                    })
                    .catch(error => {
                        console.error(` Error en la solicitud:`, error);
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
                    console.log(` Foto nueva eliminada de fotosSeleccionadas`);
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
        console.warn(`⚠️ No se encontró foto con ID: ${fotoId}`);
        return;
    }
    
    const esGuardada = fotoAEliminar.hasAttribute('data-foto-guardada');
    const fileName = fotoAEliminar.getAttribute('data-file-name');
    
    console.log(`🗑️ Eliminando foto con ID ${fotoId}:`, {
        esGuardada: esGuardada,
        fileName: fileName
    });
    
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
                
                console.log(`🗑️ Eliminando foto del servidor:`, rutaFoto);
                
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
                        console.log(` Foto eliminada del servidor:`, rutaFoto);
                        
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
                        console.error(` Error al eliminar foto:`, data.message);
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar la imagen.',
                            icon: 'error',
                            confirmButtonColor: '#1e40af'
                        });
                    }
                })
                .catch(error => {
                    console.error(` Error en la solicitud:`, error);
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
        if (fotosSeleccionadas[productoId]) {
            // Encontrar el índice en fotosSeleccionadas por nombre
            const indexEnFotos = fotosSeleccionadas[productoId].findIndex(f => f.name === fileName);
            if (indexEnFotos !== -1) {
                fotosSeleccionadas[productoId].splice(indexEnFotos, 1);
                console.log(` Foto nueva eliminada de fotosSeleccionadas`);
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
    console.log(`📊 Números de fotos actualizados. Total: ${todasLasFotos.length}`);
}

function agregarFotoTela(input) {
    console.log('🔥 agregarFotoTela LLAMADA:', { 
        inputName: input.name,
        files: input.files.length,
        tiempoActual: new Date().toLocaleTimeString()
    });
    
    const productoCard = input.closest('.producto-card');
    if (!productoCard) {
        console.error(' No se encontró producto-card para este input');
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
    
    console.log('📁 Agregando foto de tela a memoria');
    console.log('📁 Producto ID:', productoId);
    console.log('📁 Tela Index:', telaIndex);
    console.log('📁 Índice de prenda:', prendaIndex);
    console.log('📁 Total de archivos cargados:', input.files.length);
    
    // Contar fotos existentes ANTES de agregar
    const fotosExistentesAntes = window.telasSeleccionadas[productoId][telaIndex].length;
    
    // Array para guardar las fotos nuevas agregadas
    const fotosNuevasAgregadas = [];
    
    Array.from(input.files).forEach((file, fileIndex) => {
        // Máximo 3 fotos por tela
        if (window.telasSeleccionadas[productoId][telaIndex].length < 3) {
            window.telasSeleccionadas[productoId][telaIndex].push(file);
            fotosNuevasAgregadas.push(file); // Guardar para la iteración de preview
            console.log(` Foto ${fileIndex + 1} de tela ${telaIndex} guardada: ${file.name}`);
        }
    });
    
    // Mostrar estado actual de telasSeleccionadas
    console.log('📊 Estado actual de telasSeleccionadas:', JSON.stringify({
        productoId,
        telaIndex,
        fotosAlmacenadas: window.telasSeleccionadas[productoId][telaIndex].length,
        fotosNuevasAgregadas: fotosNuevasAgregadas.length,
        estructuraCompleta: window.telasSeleccionadas
    }));
    
    const container = productoCard.querySelector(`.fila-tela[data-tela-index="${telaIndex}"] .foto-tela-preview`);
    if (container) {
        console.log(' Contenedor encontrado, mostrando preview');
        // Pasar SOLO las fotos nuevas, no input.files
        mostrarPreviewFoto(fotosNuevasAgregadas, container, fotosExistentesAntes);
    } else {
        console.error(' No se encontró contenedor para mostrar preview');
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
        // Generar ID único para esta foto de tela
        const fotoTelaId = Date.now() + '-' + Math.random().toString(36).substr(2, 9) + '-' + index;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.setAttribute('data-foto', 'true');
            preview.setAttribute('data-foto-tela-id', fotoTelaId); // ID único
            preview.setAttribute('data-file-name', file.name);
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
        console.warn(`⚠️ No se encontró foto de tela con ID: ${fotoTelaId}`);
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
            console.log(`📝 Foto de tela ID ${fotoIdServidor} marcada para eliminar del servidor`);
        }
    }
    
    // Obtener el contenedor (foto-tela-preview)
    const container = fotoElement.closest('.foto-tela-preview');
    if (!container) {
        console.warn(`⚠️ No se encontró contenedor .foto-tela-preview`);
        return;
    }
    
    // Obtener la fila de tela para saber el índice
    const filaTelaActual = container.closest('.fila-tela');
    const telaIndex = filaTelaActual ? filaTelaActual.getAttribute('data-tela-index') : '0';
    
    const fileName = fotoElement.getAttribute('data-file-name');
    
    console.log(`🗑️ Eliminando foto de tela ${telaIndex} con ID ${fotoTelaId}:`, fileName);
    
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
    
    console.log(` Foto de tela ${telaIndex} eliminada. Total restante: ${todasLasFotos.length}`);
    
    // Actualizar telasSeleccionadas (solo si es foto nueva, no guardada)
    if (!fotoIdServidor) {
        const productoCard = container.closest('.producto-card');
        if (productoCard) {
            const productoId = productoCard.dataset.productoId;
            if (window.telasSeleccionadas && window.telasSeleccionadas[productoId] && window.telasSeleccionadas[productoId][telaIndex]) {
                const indexEnTelas = window.telasSeleccionadas[productoId][telaIndex].findIndex(f => f.name === fileName);
                if (indexEnTelas !== -1) {
                    window.telasSeleccionadas[productoId][telaIndex].splice(indexEnTelas, 1);
                    console.log(` Foto nueva de tela ${telaIndex} eliminada de telasSeleccionadas`);
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
        console.warn('⚠️ Contenedor .prenda-search-container no encontrado');
        return;
    }
    
    const suggestions = container.querySelector('.prenda-suggestions');
    
    // Validar que suggestions existe
    if (!suggestions) {
        console.warn('⚠️ Elemento .prenda-suggestions no encontrado');
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
    console.log('🔧 agregarTecnica() llamado');
    console.log('⏰ Timestamp:', new Date().toISOString());
    
    const selector = document.getElementById('selector_tecnicas');
    console.log('🔧 Selector encontrado:', !!selector);
    
    if (!selector) {
        console.error('🔧 ERROR: No se encontró selector_tecnicas');
        return;
    }
    
    const tecnica = selector.value;
    console.log('🔧 Técnica seleccionada:', tecnica);
    console.log('🔧 Value del selector:', selector.value);
    console.log('🔧 Options disponibles:', Array.from(selector.options).map(o => o.value));
    
    if (!tecnica) {
        alert('Por favor selecciona una técnica');
        return;
    }
    
    const contenedor = document.getElementById('tecnicas_seleccionadas');
    console.log('🔧 Contenedor encontrado:', !!contenedor);
    console.log('🔧 innerHTML del contenedor ANTES:', contenedor.innerHTML);
    
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
    console.log(' Técnica agregada:', tecnica);
    console.log(' innerHTML del contenedor DESPUÉS:', contenedor.innerHTML);
    console.log(' Total técnicas:', contenedor.children.length);
    
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
                    console.log(' Imagen de tela eliminada');
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
                console.log(` Imagen de tela ${index} cargada`);
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
        console.error(' No se encontró tbody para telas');
        return;
    }
    
    // Obtener el número de filas existentes para usar como índice
    const filasExistentes = tbody.querySelectorAll('.fila-tela');
    const nuevoIndice = filasExistentes.length;
    
    console.log('📊 agregarFilaTela DEBUG:', {
        filasActuales: filasExistentes.length,
        nuevoIndice,
        tblasLength: tbody ? tbody.childNodes.length : 'sin tbody'
    });
    
    // Obtener la primera fila como template
    const primeraFila = tbody.querySelector('.fila-tela');
    if (!primeraFila) {
        console.error(' No se encontró fila template');
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
            console.log('🔄 Actualizando input:', { nameOriginal: nameAttr, nameNuevo: nuevoName });
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
    
    console.log(' Nueva fila de tela agregada con índice:', nuevoIndice);
    console.log('🧵 Fila agregada - inputs actualizados:', {
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
        console.error(' No se encontró tbody');
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
            console.log(' Fila de tela eliminada');
            
            // Mostrar toast
            mostrarToast('Tela eliminada', 'success');
        }
    });
}
