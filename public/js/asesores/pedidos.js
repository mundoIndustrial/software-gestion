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
            await guardarPedido(this, true);
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
    
    // Actualizar nombres de inputs para que sean únicos
    const inputs = clone.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
            input.setAttribute('name', name.replace('[]', `[${productoCounter - 1}]`));
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
            totalCantidad += Number.parseInt(cantidad.value) || 0;
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
async function guardarPedido(form, crear = false) {
    const formData = new FormData(form);
    const productos = [];
    
    // Recopilar productos
    const productosItems = document.querySelectorAll('.producto-item');
    productosItems.forEach((item, index) => {
        const nombreInput = item.querySelector(`[name*="nombre_producto"]`);
        const descripcionInput = item.querySelector(`[name*="descripcion"]`);
        const telaInput = item.querySelector(`[name*="tela"]`);
        const tipoMangaInput = item.querySelector(`[name*="tipo_manga"]`);
        const colorInput = item.querySelector(`[name*="color"]`);
        const tallaInput = item.querySelector(`[name*="talla"]`);
        const generoInput = item.querySelector(`[name*="genero"]`);
        const cantidadInput = item.querySelector(`[name*="cantidad"]`);
        const refHiloInput = item.querySelector(`[name*="ref_hilo"]`);
        const precioInput = item.querySelector(`[name*="precio_unitario"]`);
        
        if (nombreInput && cantidadInput) {
            productos.push({
                nombre_producto: nombreInput.value,
                descripcion: descripcionInput ? descripcionInput.value : '',
                tela: telaInput ? telaInput.value : '',
                tipo_manga: tipoMangaInput ? tipoMangaInput.value : '',
                color: colorInput ? colorInput.value : '',
                talla: tallaInput ? tallaInput.value : '',
                genero: generoInput ? generoInput.value : '',
                cantidad: Number.parseInt(cantidadInput.value) || 1,
                ref_hilo: refHiloInput ? refHiloInput.value : '',
                precio_unitario: precioInput ? parseFloat(precioInput.value) || null : null
            });
        }
    });
    
    // Validar que haya al menos un producto (solo si se va a crear)
    if (crear && productos.length === 0) {
        mostrarToast('Debes agregar al menos un producto', 'error');
        return;
    }
    
    // Preparar datos
    const data = {
        pedido: crear ? Number.parseInt(formData.get('pedido')) : null,
        cliente: formData.get('cliente'),
        descripcion: formData.get('descripcion'),
        novedades: formData.get('novedades'),
        forma_de_pago: formData.get('forma_de_pago'),
        estado: formData.get('estado'),
        area: formData.get('area') || 'Creación Orden',
        crear: crear,
        productos: productos
    };
    
    // Determinar la ruta según si es crear o guardar como borrador
    const ruta = crear ? '/asesores/pedidos' : '/asesores/borradores/guardar';
    
    try {
        const response = await fetch(ruta, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (crear) {
                mostrarToast('Pedido creado exitosamente', 'success');
                setTimeout(() => {
                    window.location.href = `/asesores/pedidos/${result.pedido}`;
                }, 1500);
            } else {
                mostrarToast('Borrador guardado exitosamente', 'success');
                setTimeout(() => {
                    window.location.href = '/asesores/borradores';
                }, 1500);
            }
        } else {
            mostrarToast(result.message || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarToast('Error al guardar', 'error');
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
        const telaInput = item.querySelector(`[name*="tela"]`);
        const tipoMangaInput = item.querySelector(`[name*="tipo_manga"]`);
        const colorInput = item.querySelector(`[name*="color"]`);
        const tallaInput = item.querySelector(`[name*="talla"]`);
        const generoInput = item.querySelector(`[name*="genero"]`);
        const cantidadInput = item.querySelector(`[name*="cantidad"]`);
        const refHiloInput = item.querySelector(`[name*="ref_hilo"]`);
        const precioInput = item.querySelector(`[name*="precio_unitario"]`);
        
        if (nombreInput && cantidadInput) {
            productos.push({
                nombre_producto: nombreInput.value,
                descripcion: descripcionInput ? descripcionInput.value : '',
                tela: telaInput ? telaInput.value : '',
                tipo_manga: tipoMangaInput ? tipoMangaInput.value : '',
                color: colorInput ? colorInput.value : '',
                talla: tallaInput ? tallaInput.value : '',
                genero: generoInput ? generoInput.value : '',
                cantidad: Number.parseInt(cantidadInput.value) || 1,
                ref_hilo: refHiloInput ? refHiloInput.value : '',
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

