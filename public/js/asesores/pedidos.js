// ========================================
// GESTIÓN DE PRODUCTOS EN PEDIDOS
// ========================================

let productoCounter = 0;

document.addEventListener('DOMContentLoaded', function() {
    const btnAgregarProducto = document.getElementById('btnAgregarProducto');
    const productosContainer = document.getElementById('productosContainer');
    const formCrear = document.getElementById('formCrearPedido');
    const formEditar = document.getElementById('formEditarPedido');

    // Agregar primer producto automáticamente en crear
    if (formCrear && productosContainer.children.length === 0) {
        agregarProducto();
    }

    // Contar productos existentes en editar
    if (formEditar) {
        productoCounter = productosContainer.children.length;
        actualizarResumen();
        // Agregar event listeners a productos existentes
        agregarEventListenersProductos();
    }

    // Botón agregar producto
    if (btnAgregarProducto) {
        btnAgregarProducto.addEventListener('click', agregarProducto);
    }

    // Submit del formulario de crear
    if (formCrear) {
        formCrear.addEventListener('submit', async function(e) {
            e.preventDefault();
            await guardarPedido(this);
        });
    }

    // Submit del formulario de editar
    if (formEditar) {
        formEditar.addEventListener('submit', async function(e) {
            e.preventDefault();
            await actualizarPedido(this);
        });
    }
});

// ========================================
// AGREGAR PRODUCTO
// ========================================
function agregarProducto() {
    const template = document.getElementById('productoTemplate');
    const clone = template.content.cloneNode(true);
    const productosContainer = document.getElementById('productosContainer');
    
    productoCounter++;
    
    // Actualizar número de producto
    const numeroSpan = clone.querySelector('.producto-numero');
    if (numeroSpan) {
        numeroSpan.textContent = productoCounter;
    }
    
    // Actualizar nombres de inputs y IDs para que sean únicos
    const inputs = clone.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
            input.setAttribute('name', name.replace('[]', `[${productoCounter - 1}]`));
        }
        
        // Actualizar IDs de checkboxes de personalización
        const id = input.getAttribute('id');
        if (id && id.includes('PRODUCTINDEX')) {
            input.setAttribute('id', id.replace(/PRODUCTINDEX/g, productoCounter - 1));
        }
    });
    
    // Actualizar labels con atributo for
    const labels = clone.querySelectorAll('label[for]');
    labels.forEach(label => {
        const forAttr = label.getAttribute('for');
        if (forAttr && forAttr.includes('PRODUCTINDEX')) {
            label.setAttribute('for', forAttr.replace(/PRODUCTINDEX/g, productoCounter - 1));
        }
    });
    
    const divs = clone.querySelectorAll('div[id]');
    divs.forEach(div => {
        const id = div.getAttribute('id');
        if (id && id.includes('PRODUCTINDEX')) {
            div.setAttribute('id', id.replace(/PRODUCTINDEX/g, productoCounter - 1));
        }
    });
    
    // Agregar event listener al botón de eliminar
    const btnRemove = clone.querySelector('.btn-remove-product');
    btnRemove.addEventListener('click', function() {
        this.closest('.producto-item').remove();
        actualizarResumen();
        renumerarProductos();
    });
    
    // Agregar event listeners para cálculos
    const cantidad = clone.querySelector('.producto-cantidad');
    const precio = clone.querySelector('.producto-precio');
    
    if (cantidad) {
        cantidad.addEventListener('input', function() {
            calcularSubtotal(this.closest('.producto-item'));
            actualizarResumen();
        });
    }
    
    if (precio) {
        precio.addEventListener('input', function() {
            calcularSubtotal(this.closest('.producto-item'));
            actualizarResumen();
        });
    }
    
    productosContainer.appendChild(clone);
    actualizarResumen();
}

// ========================================
// AGREGAR EVENT LISTENERS A PRODUCTOS EXISTENTES
// ========================================
function agregarEventListenersProductos() {
    const productos = document.querySelectorAll('.producto-item');
    
    productos.forEach(producto => {
        // Botón eliminar
        const btnRemove = producto.querySelector('.btn-remove-product');
        if (btnRemove) {
            btnRemove.addEventListener('click', function() {
                this.closest('.producto-item').remove();
                actualizarResumen();
                renumerarProductos();
            });
        }
        
        // Cantidad y precio
        const cantidad = producto.querySelector('.producto-cantidad');
        const precio = producto.querySelector('.producto-precio');
        
        if (cantidad) {
            cantidad.addEventListener('input', function() {
                calcularSubtotal(this.closest('.producto-item'));
                actualizarResumen();
            });
        }
        
        if (precio) {
            precio.addEventListener('input', function() {
                calcularSubtotal(this.closest('.producto-item'));
                actualizarResumen();
            });
        }
        
        // Calcular subtotal inicial
        calcularSubtotal(producto);
    });
}

// ========================================
// CALCULAR SUBTOTAL DE UN PRODUCTO
// ========================================
function calcularSubtotal(productoItem) {
    const cantidad = productoItem.querySelector('.producto-cantidad');
    const precio = productoItem.querySelector('.producto-precio');
    const subtotal = productoItem.querySelector('.producto-subtotal');
    
    if (cantidad && precio && subtotal) {
        const cantidadVal = parseFloat(cantidad.value) || 0;
        const precioVal = parseFloat(precio.value) || 0;
        const subtotalVal = cantidadVal * precioVal;
        
        subtotal.value = subtotalVal > 0 ? `$${subtotalVal.toFixed(2)}` : '$0.00';
    }
}

// ========================================
// ACTUALIZAR RESUMEN
// ========================================
function actualizarResumen() {
    const productos = document.querySelectorAll('.producto-item');
    const totalProductos = document.getElementById('totalProductos');
    const cantidadTotal = document.getElementById('cantidadTotal');
    
    let totalCantidad = 0;
    
    productos.forEach(producto => {
        const cantidad = producto.querySelector('.producto-cantidad');
        if (cantidad) {
            totalCantidad += parseInt(cantidad.value) || 0;
        }
    });
    
    if (totalProductos) {
        totalProductos.textContent = productos.length;
    }
    
    if (cantidadTotal) {
        cantidadTotal.textContent = totalCantidad;
    }
}

// ========================================
// RENUMERAR PRODUCTOS
// ========================================
function renumerarProductos() {
    const productos = document.querySelectorAll('.producto-item');
    productoCounter = 0;
    
    productos.forEach((producto, index) => {
        productoCounter++;
        const numeroSpan = producto.querySelector('.producto-numero');
        if (numeroSpan) {
            numeroSpan.textContent = index + 1;
        }
        
        // Actualizar nombres de inputs
        const inputs = producto.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            const name = input.getAttribute('name');
            if (name && name.includes('[')) {
                const baseName = name.substring(0, name.indexOf('['));
                const fieldName = name.substring(name.lastIndexOf('['));
                input.setAttribute('name', `${baseName}[${index}]${fieldName}`);
            }
        });
    });
}

// ========================================
// GUARDAR PEDIDO
// ========================================
async function guardarPedido(form) {
    const formData = new FormData(form);
    
    // Validar que haya al menos un producto
    const productosItems = document.querySelectorAll('.producto-item');
    if (productosItems.length === 0) {
        mostrarToast('Debes agregar al menos un producto', 'error');
        return;
    }
    
    try {
        const response = await fetch('/asesores/pedidos', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarToast('Pedido creado exitosamente', 'success');
            setTimeout(() => {
                window.location.href = `/asesores/pedidos/${result.pedido}`;
            }, 1500);
        } else {
            mostrarToast(result.message || 'Error al crear el pedido', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarToast('Error al crear el pedido', 'error');
    }
}

// ========================================
// ACTUALIZAR PEDIDO
// ========================================
async function actualizarPedido(form) {
    const pedidoId = form.dataset.pedido;
    const formData = new FormData(form);
    const productos = [];
    
    // Recopilar productos
    const productosItems = document.querySelectorAll('.producto-item');
    productosItems.forEach((item, index) => {
        const nombreInput = item.querySelector(`[name*="nombre_producto"]`);
        const descripcionInput = item.querySelector(`[name*="descripcion"]`);
        const tallaInput = item.querySelector(`[name*="talla"]`);
        const cantidadInput = item.querySelector(`[name*="cantidad"]`);
        const precioInput = item.querySelector(`[name*="precio_unitario"]`);
        
        if (nombreInput && cantidadInput) {
            productos.push({
                nombre_producto: nombreInput.value,
                descripcion: descripcionInput ? descripcionInput.value : '',
                talla: tallaInput ? tallaInput.value : '',
                cantidad: parseInt(cantidadInput.value) || 1,
                precio_unitario: precioInput ? parseFloat(precioInput.value) || null : null
            });
        }
    });
    
    // Preparar datos
    const data = {
        cliente: formData.get('cliente'),
        descripcion: formData.get('descripcion'),
        novedades: formData.get('novedades'),
        forma_de_pago: formData.get('forma_de_pago'),
        estado: formData.get('estado'),
        area: formData.get('area'),
        productos: productos
    };
    
    try {
        const response = await fetch(`/asesores/pedidos/${pedidoId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarToast('Pedido actualizado exitosamente', 'success');
            setTimeout(() => {
                window.location.href = `/asesores/pedidos/${pedidoId}`;
            }, 1500);
        } else {
            mostrarToast(result.message || 'Error al actualizar el pedido', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarToast('Error al actualizar el pedido', 'error');
    }
}

// ========================================
// MOSTRAR TOAST
// ========================================
function mostrarToast(mensaje, tipo = 'info') {
    // Crear elemento toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${mensaje}</span>
    `;
    
    // Agregar estilos si no existen
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                position: fixed;
                top: 2rem;
                right: 2rem;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                color: white;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            .toast-success {
                background: linear-gradient(135deg, #10b981, #059669);
            }
            .toast-error {
                background: linear-gradient(135deg, #ef4444, #dc2626);
            }
            .toast-info {
                background: linear-gradient(135deg, #3b82f6, #2563eb);
            }
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(toast);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ========================================
// MANEJO DE IMÁGENES
// ========================================

// Preview de imagen principal
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('producto-imagen-input')) {
        handleImagePreview(e.target);
    }
});

// Preview de imágenes adicionales
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('producto-imagenes-adicionales')) {
        handleMultipleImagesPreview(e.target);
    }
});

// Preview de imágenes de personalización
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('personalizacion-images-input')) {
        handlePersonalizacionImagesPreview(e.target);
    }
});

// Remover preview
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-remove-preview')) {
        removeImagePreview(e.target);
    }
});

function handleImagePreview(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validar tamaño (5MB)
    if (file.size > 5 * 1024 * 1024) {
        mostrarToast('La imagen no debe superar 5MB', 'error');
        input.value = '';
        return;
    }
    
    // Validar tipo
    if (!file.type.startsWith('image/')) {
        mostrarToast('Solo se permiten archivos de imagen', 'error');
        input.value = '';
        return;
    }
    
    const container = input.nextElementSibling;
    const img = container.querySelector('.image-preview');
    
    const reader = new FileReader();
    reader.onload = function(e) {
        img.src = e.target.result;
        container.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function handleMultipleImagesPreview(input) {
    const files = Array.from(input.files);
    const grid = input.nextElementSibling;
    
    grid.innerHTML = '';
    
    files.forEach((file, index) => {
        // Validar tamaño
        if (file.size > 5 * 1024 * 1024) {
            mostrarToast(`Imagen ${index + 1} supera 5MB`, 'error');
            return;
        }
        
        // Validar tipo
        if (!file.type.startsWith('image/')) {
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.className = 'image-preview-item';
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview ${index + 1}">
                <div class="image-info">
                    <small>${file.name}</small>
                    <small>${(file.size / 1024).toFixed(1)} KB</small>
                </div>
            `;
            grid.appendChild(preview);
        };
        reader.readAsDataURL(file);
    });
}

function removeImagePreview(button) {
    const container = button.closest('.image-preview-container');
    const input = container.previousElementSibling;
    
    input.value = '';
    container.style.display = 'none';
    container.querySelector('.image-preview').src = '';
}

// Preview de imágenes de personalización (bordados/estampados)
function handlePersonalizacionImagesPreview(input) {
    const files = Array.from(input.files);
    const previewContainer = input.nextElementSibling;
    
    if (!previewContainer || !previewContainer.classList.contains('personalizacion-images-preview')) {
        return;
    }
    
    previewContainer.innerHTML = '';
    
    files.forEach((file, index) => {
        // Validar tamaño
        if (file.size > 5 * 1024 * 1024) {
            mostrarToast(`Imagen ${index + 1} supera 5MB`, 'error');
            return;
        }
        
        // Validar tipo
        if (!file.type.startsWith('image/')) {
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imageItem = document.createElement('div');
            imageItem.className = 'personalizacion-image-item';
            imageItem.innerHTML = `
                <img src="${e.target.result}" alt="Preview ${index + 1}">
                <button type="button" class="remove-btn" onclick="removePersonalizacionImage(this, ${index})">×</button>
            `;
            previewContainer.appendChild(imageItem);
        };
        reader.readAsDataURL(file);
    });
}

// Remover imagen de personalización
function removePersonalizacionImage(button, index) {
    const imageItem = button.closest('.personalizacion-image-item');
    const previewContainer = imageItem.parentElement;
    const input = previewContainer.previousElementSibling;
    
    // Remover visualmente
    imageItem.remove();
    
    // Crear nuevo FileList sin el archivo eliminado
    const dt = new DataTransfer();
    const files = Array.from(input.files);
    
    files.forEach((file, i) => {
        if (i !== index) {
            dt.items.add(file);
        }
    });
    
    input.files = dt.files;
    
    // Actualizar preview
    handlePersonalizacionImagesPreview(input);
}

// ========================================
// SISTEMA DE TALLAS Y CANTIDADES
// ========================================

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-add-talla') || 
        e.target.closest('.btn-add-talla')) {
        const button = e.target.classList.contains('btn-add-talla') ? 
                      e.target : e.target.closest('.btn-add-talla');
        agregarTalla(button);
    }
    
    if (e.target.classList.contains('btn-remove-talla') || 
        e.target.closest('.btn-remove-talla')) {
        const button = e.target.classList.contains('btn-remove-talla') ? 
                      e.target : e.target.closest('.btn-remove-talla');
        removerTalla(button);
    }
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('talla-select') || 
        e.target.classList.contains('cantidad-input')) {
        actualizarTotales(e.target);
    }
});

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('cantidad-input')) {
        actualizarTotales(e.target);
    }
});

function agregarTalla(button) {
    const tallasList = button.previousElementSibling;
    
    const tallaHTML = `
        <div class="talla-item">
            <span class="talla-label">Talla</span>
            <select class="talla-select">
                <option value="">Seleccionar...</option>
                <option value="XXS">XXS</option>
                <option value="XS">XS</option>
                <option value="S">S</option>
                <option value="M">M</option>
                <option value="L">L</option>
                <option value="XL">XL</option>
                <option value="XXL">XXL</option>
                <option value="XXXL">XXXL</option>
                <option value="4">4</option>
                <option value="6">6</option>
                <option value="8">8</option>
                <option value="10">10</option>
                <option value="12">12</option>
                <option value="14">14</option>
                <option value="16">16</option>
                <option value="Única">Única</option>
                <option value="Personalizada">Otra</option>
            </select>
            <div class="cantidad-wrapper">
                <span class="cantidad-label">Cant:</span>
                <input type="number" 
                       class="cantidad-input" 
                       min="1" 
                       value="1">
            </div>
            <button type="button" class="btn-remove-talla">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
    `;
    
    tallasList.insertAdjacentHTML('beforeend', tallaHTML);
    actualizarTotales(button);
}

function removerTalla(button) {
    const tallaItem = button.closest('.talla-item');
    const tallasList = tallaItem.parentElement;
    
    // No permitir eliminar si es la única talla
    if (tallasList.children.length > 1) {
        tallaItem.remove();
        actualizarTotales(button);
    } else {
        mostrarToast('Debe haber al menos una talla', 'warning');
    }
}

function actualizarTotales(element) {
    const configuracion = element.closest('.tallas-configuracion');
    const tallasList = configuracion.querySelector('.tallas-list');
    const resumenTotal = configuracion.querySelector('.resumen-total');
    const hiddenInput = configuracion.querySelector('.tallas-cantidades-hidden');
    const cantidadTotal = configuracion.querySelector('.producto-cantidad');
    
    let total = 0;
    const tallas = [];
    
    tallasList.querySelectorAll('.talla-item').forEach((item, index) => {
        const select = item.querySelector('.talla-select');
        const input = item.querySelector('.cantidad-input');
        const removeBtn = item.querySelector('.btn-remove-talla');
        
        // Mostrar botón de eliminar solo si hay más de una talla
        if (tallasList.children.length > 1) {
            removeBtn.style.display = 'flex';
        } else {
            removeBtn.style.display = 'none';
        }
        
        const talla = select.value;
        const cantidad = parseInt(input.value) || 0;
        
        if (talla && cantidad > 0) {
            total += cantidad;
            tallas.push({
                talla: talla,
                cantidad: cantidad
            });
        }
    });
    
    resumenTotal.textContent = total;
    hiddenInput.value = JSON.stringify(tallas);
    cantidadTotal.value = total;
    
    // Actualizar resumen general del pedido
    actualizarResumen();
}

// ========================================
// CONFIGURACIÓN DE TELAS POR SECCIÓN
// ========================================

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-add-tela-section') || 
        e.target.closest('.btn-add-tela-section')) {
        const button = e.target.classList.contains('btn-add-tela-section') ? 
                      e.target : e.target.closest('.btn-add-tela-section');
        agregarSeccionTela(button);
    }
    
    if (e.target.classList.contains('btn-remove-tela-section') || 
        e.target.closest('.btn-remove-tela-section')) {
        const button = e.target.classList.contains('btn-remove-tela-section') ? 
                      e.target : e.target.closest('.btn-remove-tela-section');
        removerSeccionTela(button);
    }
});

function agregarSeccionTela(button) {
    const container = button.nextElementSibling;
    const seccionesCount = container.children.length;
    
    const seccionHTML = `
        <div class="tela-section adicional">
            <div class="tela-section-header">
                <span class="material-symbols-rounded">category</span>
                <select class="seccion-selector">
                    <option value="">Seleccionar sección...</option>
                    <option value="Mangas">Mangas</option>
                    <option value="Cuello">Cuello</option>
                    <option value="Puños">Puños</option>
                    <option value="Bolsillos">Bolsillos</option>
                    <option value="Espalda">Espalda</option>
                    <option value="Frente">Frente</option>
                    <option value="Laterales">Laterales</option>
                    <option value="Capucha">Capucha</option>
                    <option value="Forro">Forro</option>
                    <option value="Otro">Otro (especificar)</option>
                </select>
            </div>
            <div class="tela-fields">
                <input type="text" 
                       placeholder="Tela para esta sección"
                       class="tela-input tela-adicional">
                <input type="text" 
                       placeholder="Color"
                       class="color-input color-adicional">
            </div>
            <div class="tela-section-actions">
                <button type="button" class="btn-remove-tela-section">
                    <span class="material-symbols-rounded">delete</span>
                    Eliminar Sección
                </button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', seccionHTML);
    actualizarConfiguracionTelas(button.closest('.telas-configuracion'));
}

function removerSeccionTela(button) {
    const seccion = button.closest('.tela-section');
    const configuracion = seccion.closest('.telas-configuracion');
    seccion.remove();
    actualizarConfiguracionTelas(configuracion);
}

function actualizarConfiguracionTelas(configuracion) {
    const productoItem = configuracion.closest('.producto-item');
    const hiddenInput = productoItem.querySelector('.configuracion-telas-hidden');
    
    const telaPrincipal = {
        seccion: 'Principal (Cuerpo)',
        tela: configuracion.querySelector('.tela-section.principal .tela-input')?.value || '',
        color: configuracion.querySelector('.tela-section.principal .color-input')?.value || ''
    };
    
    const telasAdicionales = [];
    configuracion.querySelectorAll('.tela-section.adicional').forEach(seccion => {
        const selector = seccion.querySelector('.seccion-selector');
        const tela = seccion.querySelector('.tela-adicional');
        const color = seccion.querySelector('.color-adicional');
        
        if (selector && selector.value) {
            telasAdicionales.push({
                seccion: selector.value,
                tela: tela?.value || '',
                color: color?.value || ''
            });
        }
    });
    
    const configuracionCompleta = {
        principal: telaPrincipal,
        adicionales: telasAdicionales
    };
    
    hiddenInput.value = JSON.stringify(configuracionCompleta);
}

// Actualizar configuración cuando cambien los inputs
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('tela-input') || 
        e.target.classList.contains('color-input') ||
        e.target.classList.contains('seccion-selector')) {
        const configuracion = e.target.closest('.telas-configuracion');
        if (configuracion) {
            actualizarConfiguracionTelas(configuracion);
        }
    }
});

// ========================================
// MANEJO DE PERSONALIZACIÓN (BORDADO/ESTAMPADO)
// ========================================

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('personalizacion-checkbox')) {
        handlePersonalizacionToggle(e.target);
    }
});

function handlePersonalizacionToggle(checkbox) {
    const productoItem = checkbox.closest('.producto-item');
    
    // Obtener estado de ambos checkboxes
    const bordadoCheckbox = productoItem.querySelector('[data-target="bordado"]');
    const estampadoCheckbox = productoItem.querySelector('[data-target="estampado"]');
    
    const bordadoChecked = bordadoCheckbox && bordadoCheckbox.checked;
    const estampadoChecked = estampadoCheckbox && estampadoCheckbox.checked;
    
    // Obtener todos los divs de detalles
    const bordadoDiv = productoItem.querySelector('.bordado-only');
    const estampadoDiv = productoItem.querySelector('.estampado-only');
    const combinadoDiv = productoItem.querySelector('.combinado-details');
    
    // Lógica de visualización
    if (bordadoChecked && estampadoChecked) {
        // Ambos seleccionados: mostrar solo el campo combinado
        if (bordadoDiv) bordadoDiv.style.display = 'none';
        if (estampadoDiv) estampadoDiv.style.display = 'none';
        if (combinadoDiv) {
            combinadoDiv.style.display = 'block';
            const textarea = combinadoDiv.querySelector('textarea');
            if (textarea) setTimeout(() => textarea.focus(), 100);
        }
    } else if (bordadoChecked) {
        // Solo bordado
        if (bordadoDiv) {
            bordadoDiv.style.display = 'block';
            const textarea = bordadoDiv.querySelector('textarea');
            if (textarea) setTimeout(() => textarea.focus(), 100);
        }
        if (estampadoDiv) estampadoDiv.style.display = 'none';
        if (combinadoDiv) combinadoDiv.style.display = 'none';
    } else if (estampadoChecked) {
        // Solo estampado
        if (bordadoDiv) bordadoDiv.style.display = 'none';
        if (estampadoDiv) {
            estampadoDiv.style.display = 'block';
            const textarea = estampadoDiv.querySelector('textarea');
            if (textarea) setTimeout(() => textarea.focus(), 100);
        }
        if (combinadoDiv) combinadoDiv.style.display = 'none';
    } else {
        // Ninguno seleccionado
        if (bordadoDiv) {
            bordadoDiv.style.display = 'none';
            const textarea = bordadoDiv.querySelector('textarea');
            if (textarea) textarea.value = '';
        }
        if (estampadoDiv) {
            estampadoDiv.style.display = 'none';
            const textarea = estampadoDiv.querySelector('textarea');
            if (textarea) textarea.value = '';
        }
        if (combinadoDiv) {
            combinadoDiv.style.display = 'none';
            const textarea = combinadoDiv.querySelector('textarea');
            if (textarea) textarea.value = '';
        }
    }
}
