/**
 * ProductEventManager - Gestor Centralizado de Events para Productos
 * 
 * Elimina duplicación: agregarProducto() + agregarEventListenersProductos()
 * Ambas cargaban listeners idénticos para cantidad/precio (25 líneas duplicadas)
 * 
 * Uso: ProductEventManager.setupListeners(elemento)
 */
window.ProductEventManager = {
    /**
     * Configurar todos los event listeners para un producto
     * @param {HTMLElement} productoElement - Elemento .producto-item
     */
    setupListeners: function(productoElement) {
        this._setupCantidadListener(productoElement);
        this._setupPrecioListener(productoElement);
        this._setupRemoveListener(productoElement);
    },

    /**
     * Listener para cambios de cantidad
     * @private
     */
    _setupCantidadListener: function(productoElement) {
        const cantidadInput = productoElement.querySelector('.producto-cantidad');
        if (cantidadInput) {
            cantidadInput.addEventListener('input', () => {
                this._calcularSubtotal(productoElement);
                this._actualizarResumen();
            });
        }
    },

    /**
     * Listener para cambios de precio
     * @private
     */
    _setupPrecioListener: function(productoElement) {
        const precioInput = productoElement.querySelector('.producto-precio');
        if (precioInput) {
            precioInput.addEventListener('input', () => {
                this._calcularSubtotal(productoElement);
                this._actualizarResumen();
            });
        }
    },

    /**
     * Listener para eliminar producto
     * @private
     */
    _setupRemoveListener: function(productoElement) {
        const btnRemove = productoElement.querySelector('.btn-remove-product');
        if (btnRemove) {
            btnRemove.addEventListener('click', () => {
                productoElement.remove();
                this._actualizarResumen();
                this._renumerarProductos();
            });
        }
    },

    /**
     * Calcular subtotal de un producto
     * @private
     */
    _calcularSubtotal: function(productoElement) {
        const cantidad = productoElement.querySelector('.producto-cantidad');
        const precio = productoElement.querySelector('.producto-precio');
        const subtotal = productoElement.querySelector('.producto-subtotal');

        if (cantidad && precio && subtotal) {
            const cantidadVal = parseFloat(cantidad.value) || 0;
            const precioVal = parseFloat(precio.value) || 0;
            const subtotalVal = cantidadVal * precioVal;

            subtotal.value = subtotalVal > 0 ? `$${subtotalVal.toFixed(2)}` : '$0.00';
        }
    },

    /**
     * Actualizar resumen de productos
     * @private
     */
    _actualizarResumen: function() {
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
    },

    /**
     * Renumerar productos después de eliminar
     * @private
     */
    _renumerarProductos: function() {
        let contador = 0;
        document.querySelectorAll('.producto-item').forEach((producto, index) => {
            contador++;
            const numeroSpan = producto.querySelector('.producto-numero');
            if (numeroSpan) {
                numeroSpan.textContent = index + 1;
            }

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
};


