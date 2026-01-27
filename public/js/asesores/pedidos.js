// ========================================
// GESTI�N DE PRODUCTOS EN PEDIDOS (REFACTORIZADO)
// ========================================

let productoCounter = 0;

document.addEventListener('DOMContentLoaded', function() {
    const btnAgregarProducto = document.getElementById('btnAgregarProducto');
    const productosContainer = document.getElementById('productosContainer');
    const formCrear = document.getElementById('formCrearPedido');
    const formEditar = document.getElementById('formEditarPedido');

    // Agregar primer producto autom�ticamente en crear
    if (formCrear && productosContainer.children.length === 0) {
        agregarProducto();
    }

    // Contar productos existentes en editar
    if (formEditar) {
        productoCounter = productosContainer.children.length;
        ProductEventManager._actualizarResumen();
        // Agregar event listeners a productos existentes
        document.querySelectorAll('.producto-item').forEach(item => {
            ProductEventManager.setupListeners(item);
        });
    }

    // Bot�n agregar producto
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
// AGREGAR PRODUCTO (REFACTORIZADO)
// ========================================
function agregarProducto() {
    const template = document.getElementById('productoTemplate');
    const clone = template.content.cloneNode(true);
    const productosContainer = document.getElementById('productosContainer');
    
    productoCounter++;
    
    // Actualizar n�mero de producto
    const numeroSpan = clone.querySelector('.producto-numero');
    if (numeroSpan) {
        numeroSpan.textContent = productoCounter;
    }
    
    // Actualizar nombres de inputs para que sean �nicos
    const inputs = clone.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
            input.setAttribute('name', name.replace('[]', `[${productoCounter - 1}]`));
        }
    });
    
    productosContainer.appendChild(clone);
    
    // Configurar listeners usando el servicio centralizado
    const nuevoProducto = productosContainer.lastElementChild;
    ProductEventManager.setupListeners(nuevoProducto);
    ProductEventManager._actualizarResumen();
}

// ========================================
// AGREGAR EVENT LISTENERS A PRODUCTOS EXISTENTES
// ========================================
function agregarEventListenersProductos() {
    const productos = document.querySelectorAll('.producto-item');
    
    productos.forEach(producto => {
        // Bot+�n eliminar
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
// GUARDAR PEDIDO (REFACTORIZADO)
// ========================================
async function guardarPedido(form, crear = false) {
    const formData = new FormData(form);
    
    //  Recolectar productos usando ProductCollectorService
    const productos = ProductCollector.recolectar();
    
    //  Validar usando ProductCollectorService
    const validacion = ProductCollector.validar(productos, crear);
    if (!validacion.valido) {
        UI.toastError(validacion.mensaje);
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
    
    try {
        UI.cargando('Guardando pedido...', 'Por favor espera');
        
        //  Usar PedidoAPIService centralizado
        const ruta = crear ? '/asesores/pedidos' : '/asesores/borradores/guardar';
        const result = await PedidoAPI[crear ? 'crear' : 'guardarBorrador'](data);
        
        Swal.close();
        
        if (result.success) {
            if (crear) {
                UI.toastExito('Pedido creado exitosamente');
                setTimeout(() => {
                    window.location.href = `/asesores/pedidos/${result.pedido}`;
                }, 1500);
            } else {
                UI.toastExito('Borrador guardado exitosamente');
                setTimeout(() => {
                    window.location.href = '/asesores/borradores';
                }, 1500);
            }
        } else {
            UI.toastError(result.message || 'Error al guardar');
        }
    } catch (error) {
        Swal.close();

        UI.toastError('Error al guardar');
    }
}

// ========================================
// ACTUALIZAR PEDIDO (REFACTORIZADO)
// ========================================
async function actualizarPedido(form) {
    const pedidoId = form.dataset.pedido;
    
    //  Recolectar productos usando ProductCollectorService
    const productos = ProductCollector.recolectar();
    
    const formData = new FormData(form);
    
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
        UI.cargando('Actualizando pedido...', 'Por favor espera');
        
        //  Usar PedidoAPIService centralizado
        const result = await PedidoAPI.actualizar(pedidoId, data);
        
        Swal.close();
        
        if (result.success) {
            UI.toastExito('Pedido actualizado exitosamente');
            setTimeout(() => {
                window.location.href = `/asesores/pedidos/${pedidoId}`;
            }, 1500);
        } else {
            UI.toastError(result.message || 'Error al actualizar el pedido');
        }
    } catch (error) {
        Swal.close();

        UI.toastError('Error al actualizar el pedido');
    }
}

// ========================================
// FUNCIONES GLOBALES PARA GALER�A
// (Delegadas a GaleriaService)
// ========================================

/**
 * Muestra la factura de costura (vista anterior)
 */
window.toggleFactura = function() {
    Galeria.toggleFactura('order-detail-modal-wrapper', 'btn-factura', 'btn-galeria');
};

/**
 * Alterna a vista de galer�a de costura
 */
window.toggleGaleria = function() {
    Galeria.toggleGaleria('order-detail-modal-wrapper', 'order-pedido', 'btn-factura', 'btn-galeria');
};

/**
 * Muestra una imagen en grande en modal
 */
window.mostrarImagenGrande = function(index) {
    Galeria.mostrarImagenGrande(index);
};

/**
 * Cierra el modal de imagen grande
 */
window.cerrarImagenGrande = function() {
    Galeria.cerrarImagenGrande();
};

/**
 * Cambia entre im�genes
 */
window.cambiarImagen = function(direccion) {
    Galeria.cambiarImagen(direccion);
};


