/**
 * ProductCollectorService - Recolector Centralizado de Productos
 * 
 * Elimina duplicación: guardarPedido() + actualizarPedido()
 * Ambas funciones recolectaban productos de forma idéntica (40 líneas cada una)
 * 
 * Uso: ProductCollector.recolectar()
 */
window.ProductCollector = {
    /**
     * Recolectar todos los productos del formulario
     * @returns {Array} Array de productos
     */
    recolectar: function() {
        const productos = [];
        const productosItems = document.querySelectorAll('.producto-item');
        
        productosItems.forEach((item) => {
            const producto = this.extraerProducto(item);
            if (producto.nombre_producto && producto.cantidad) {
                productos.push(producto);
            }
        });
        
        return productos;
    },

    /**
     * Extraer datos de un producto desde su elemento DOM
     * @param {HTMLElement} item - Elemento .producto-item
     * @returns {Object} Objeto producto
     */
    extraerProducto: function(item) {
        return {
            nombre_producto: item.querySelector('[name*="nombre_producto"]')?.value || '',
            descripcion: item.querySelector('[name*="descripcion"]')?.value || '',
            tela: item.querySelector('[name*="tela"]')?.value || '',
            tipo_manga: item.querySelector('[name*="tipo_manga"]')?.value || '',
            color: item.querySelector('[name*="color"]')?.value || '',
            talla: item.querySelector('[name*="talla"]')?.value || '',
            genero: item.querySelector('[name*="genero"]')?.value || '',
            cantidad: Number.parseInt(item.querySelector('[name*="cantidad"]')?.value) || 1,
            ref_hilo: item.querySelector('[name*="ref_hilo"]')?.value || '',
            precio_unitario: parseFloat(item.querySelector('[name*="precio_unitario"]')?.value) || null
        };
    },

    /**
     * Validar que haya productos
     * @param {Array} productos - Array de productos
     * @param {boolean} requerido - Si es obligatorio tener productos
     * @returns {Object} {válido: boolean, mensaje: string}
     */
    validar: function(productos, requerido = true) {
        if (requerido && productos.length === 0) {
            return {
                válido: false,
                mensaje: 'Debes agregar al menos un producto'
            };
        }
        return { válido: true };
    }
};

console.log(' ProductCollectorService cargado');
