let productosCount = 0;

// Agregar prenda por defecto al cargar el formulario
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('productosContainer');
    if (container && container.children.length === 0) {
        agregarProductoFriendly();
    }
});

// Ir al paso especificado (sin validación - libre navegación)
function irAlPaso(paso) {
    // Ocultar todos los pasos
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });

    // Mostrar paso seleccionado
    document.querySelector(`[data-step="${paso}"]`).classList.add('active');

    // Actualizar stepper
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });
    document.querySelector(`[data-step="${paso}"].step`).classList.add('active');

    // Si es el paso 3, actualizar resumen
    if (paso === 3) {
        actualizarResumenFriendly();
    }
}

// Validar paso actual
function validarPasoActual() {
    const pasoActivo = document.querySelector('.form-step.active');
    const paso = pasoActivo.getAttribute('data-step');

    if (paso === '1') {
        const cliente = document.getElementById('cliente').value.trim();
        if (!cliente) {
            alert('Por favor ingresa el nombre del cliente');
            return false;
        }
        return true;
    }

    if (paso === '2') {
        const productos = document.querySelectorAll('.producto-card');
        if (productos.length === 0) {
            alert('Por favor agrega al menos un producto');
            return false;
        }

        // Validar que cada producto tenga los campos requeridos
        let valido = true;
        productos.forEach((producto, index) => {
            const nombre = producto.querySelector('input[name*="nombre_producto"]').value.trim();
            const cantidad = producto.querySelector('input[name*="cantidad"]').value;
            const talla = producto.querySelector('select[name*="talla"]').value;

            if (!nombre || !cantidad || !talla) {
                alert(`Prenda ${index + 1}: Por favor completa los campos obligatorios (Tipo, Cantidad y Talla)`);
                valido = false;
                return;
            }
        });

        return valido;
    }

    return true;
}

// Agregar producto
function agregarProductoFriendly() {
    productosCount++;
    const template = document.getElementById('productoTemplate');
    const clone = template.content.cloneNode(true);

    // Actualizar número de prenda
    clone.querySelector('.numero-producto').textContent = productosCount;

    // Agregar al contenedor
    document.getElementById('productosContainer').appendChild(clone);
}

// Eliminar producto
function eliminarProductoFriendly(btn) {
    btn.closest('.producto-card').remove();
    renumerarPrendas();
    actualizarResumenFriendly();
}

// Renumerar prendas después de eliminar
function renumerarPrendas() {
    const prendas = document.querySelectorAll('.producto-card');
    prendas.forEach((prenda, index) => {
        prenda.querySelector('.numero-producto').textContent = index + 1;
    });
    productosCount = prendas.length;
}

// Actualizar resumen
function actualizarResumenFriendly() {
    // Información del cliente
    const cliente = document.getElementById('cliente').value || '-';
    const formaPago = document.getElementById('forma_de_pago').value || '-';

    document.getElementById('reviewCliente').textContent = cliente;
    document.getElementById('reviewFormaPago').textContent = formatearFormaPago(formaPago);

    // Productos
    const productos = document.querySelectorAll('.producto-card');
    let totalProductos = 0;
    let totalCantidad = 0;

    const reviewProductos = document.getElementById('reviewProductos');
    reviewProductos.innerHTML = '';

    productos.forEach((producto, index) => {
        const nombre = producto.querySelector('input[name*="nombre_producto"]').value || 'Sin nombre';
        const cantidad = Number.parseInt(producto.querySelector('input[name*="cantidad"]').value) || 0;
        const talla = producto.querySelector('select[name*="talla"]').value || '-';
        const color = producto.querySelector('input[name*="color"]').value || '-';

        totalProductos++;
        totalCantidad += cantidad;

        const item = document.createElement('div');
        item.className = 'review-item';
        item.innerHTML = `
            <span class="review-label">${nombre} (${talla})</span>
            <span class="review-value">${cantidad} unidades</span>
        `;
        reviewProductos.appendChild(item);
    });

    document.getElementById('reviewTotalProductos').textContent = totalProductos;
    document.getElementById('reviewCantidadTotal').textContent = totalCantidad;
}

// Formatear forma de pago
function formatearFormaPago(valor) {
    const opciones = {
        'CONTADO': '💵 Contado',
        'CRÉDITO': '📋 Crédito',
        '50/50': '⚖️ 50/50',
        'ANTICIPO': '🎯 Anticipo'
    };
    return opciones[valor] || '-';
}

// Manejar envío del formulario
document.getElementById('formCrearPedidoFriendly').addEventListener('submit', function(e) {
    e.preventDefault();

    // Validar paso 2 (productos)
    if (!validarPasoActual()) {
        irAlPaso(2);
        return;
    }

    // Recolectar datos
    const formData = new FormData(this);

    // Enviar
    fetch('{{ route("asesores.pedidos.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('¡Pedido creado exitosamente!');
            window.location.href = '{{ route("asesores.pedidos.index") }}';
        } else {
            alert('Error: ' + (data.message || 'No se pudo crear el pedido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al crear el pedido. Por favor intenta de nuevo.');
    });
});

// Agregar primer producto automáticamente
document.addEventListener('DOMContentLoaded', function() {
    agregarProductoFriendly();
});

