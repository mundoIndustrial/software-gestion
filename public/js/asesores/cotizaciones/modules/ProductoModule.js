/**
 * ProductoModule - Gestión de productos/prendas
 * 
 * Single Responsibility: CRUD de productos en el formulario
 * 
 * @module ProductoModule
 */
class ProductoModule {
    constructor() {
        this.productos = new Map();
        this.contenedor = document.getElementById('productosContainer');
        this.template = document.getElementById('productoTemplate');
        this.contador = 0;
    }

    /**
     * Inicializa el módulo
     */
    init() {
        this.setupTemplateListeners();
    }

    /**
     * Agrega un nuevo producto
     */
    agregarProducto() {
        if (!this.template || !this.contenedor) {
            console.error('Template o contenedor no encontrado');
            return;
        }

        const clone = this.template.content.cloneNode(true);
        this.contador++;
        const productoId = `producto-${Date.now()}-${this.contador}`;

        // Configurar datos del producto
        const card = clone.querySelector('.producto-card');
        card.dataset.productoId = productoId;
        card.dataset.numero = this.contador;

        // Actualizar número
        clone.querySelector('.numero-producto').textContent = this.contador;

        // Inicializar memoria para fotos y telas
        if (!window.fotosSeleccionadas) window.fotosSeleccionadas = {};
        if (!window.telasSeleccionadas) window.telasSeleccionadas = {};
        
        window.fotosSeleccionadas[productoId] = [];
        window.telasSeleccionadas[productoId] = [];

        // Agregar al contenedor
        this.contenedor.appendChild(clone);

        // Configurar listeners del nuevo producto
        this.setupProductoListeners(productoId);

        console.log(`✅ Producto agregado: ${productoId}`);
        return productoId;
    }

    /**
     * Configura listeners para un producto específico
     */
    setupProductoListeners(productoId) {
        const card = document.querySelector(`[data-producto-id="${productoId}"]`);
        if (!card) return;

        // Listener para cambios en el tipo de prenda
        const inputPrenda = card.querySelector('.prenda-search-input');
        if (inputPrenda) {
            inputPrenda.addEventListener('change', () => {
                this.handlePrendaChange(card);
            });
        }

        // Listener para toggle de expandir/contraer
        const btnToggle = card.querySelector('.btn-toggle-product');
        if (btnToggle) {
            btnToggle.addEventListener('click', () => {
                this.toggleProductoBody(card);
            });
        }

        // Nota: El botón de eliminar usa onclick en el HTML para evitar duplicados
        // No agregamos addEventListener aquí
    }

    /**
     * Maneja cambio en tipo de prenda
     */
    handlePrendaChange(card) {
        const inputPrenda = card.querySelector('.prenda-search-input');
        const valor = inputPrenda?.value?.toUpperCase() || '';

        const esJeanOPantalon = valor.includes('JEAN') || 
                               valor.includes('PANTALÓN') || 
                               valor.includes('PANTALONES') || 
                               valor.includes('PANTALON');

        const selectorVariantes = card.querySelector('.tipo-jean-pantalon-inline');
        if (selectorVariantes) {
            selectorVariantes.style.display = esJeanOPantalon ? 'block' : 'none';
        }
    }

    /**
     * Toggle para expandir/contraer producto
     */
    toggleProductoBody(card) {
        const body = card.querySelector('.producto-body');
        const btn = card.querySelector('.btn-toggle-product');

        if (body.style.display === 'none') {
            body.style.display = 'block';
            btn.textContent = '▼';
        } else {
            body.style.display = 'none';
            btn.textContent = '▶';
        }
    }

    /**
     * Elimina un producto
     */
    eliminarProducto(card) {
        const productoId = card.dataset.productoId;
        console.log(`🗑️ Intentando eliminar producto: ${productoId}`);

        // Prevenir eliminaciones duplicadas
        if (card.dataset.eliminando === 'true') {
            console.log('⚠️ Este producto ya está siendo eliminado');
            return;
        }

        // Usar SweetAlert2 si está disponible, sino usar confirm
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Eliminar prenda?',
                text: '¿Está seguro de que desea eliminar esta prenda de la cotización?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.confirmarEliminacion(card, productoId);
                }
            });
        } else {
            if (confirm('¿Está seguro de que desea eliminar esta prenda?')) {
                this.confirmarEliminacion(card, productoId);
            }
        }
    }

    /**
     * Confirma y ejecuta la eliminación
     */
    confirmarEliminacion(card, productoId) {
        // Marcar como en proceso de eliminación
        card.dataset.eliminando = 'true';
        console.log(`🔍 Card antes de remover:`, card);
        console.log(`🔍 Contenedor tiene ${this.contenedor.querySelectorAll('.producto-card').length} productos`);

        // Limpiar datos asociados
        delete window.fotosSeleccionadas[productoId];
        delete window.telasSeleccionadas[productoId];

        card.remove();
        console.log(`✅ Card removida del DOM`);
        console.log(`🔍 Contenedor ahora tiene ${this.contenedor.querySelectorAll('.producto-card').length} productos`);
        
        this.renumerarProductos();
        console.log(`✅ Productos renumerados`);

        console.log(`✅ Producto eliminado: ${productoId}`);
        
        // Mostrar toast de éxito
        if (window.mostrarToast) {
            window.mostrarToast('✅ Prenda eliminada de la cotización exitosamente', 'success');
        } else {
            // Fallback: crear un toast simple
            this.mostrarToastSimple('✅ Prenda eliminada de la cotización exitosamente');
        }
    }

    /**
     * Renumera los productos después de eliminar
     */
    renumerarProductos() {
        const cards = this.contenedor.querySelectorAll('.producto-card');
        cards.forEach((card, idx) => {
            const numero = idx + 1;
            card.dataset.numero = numero;
            const numeroSpan = card.querySelector('.numero-producto');
            if (numeroSpan) {
                numeroSpan.textContent = numero;
            }
        });
    }

    /**
     * Obtiene el producto con índice
     */
    getProducto(index) {
        const cards = this.contenedor.querySelectorAll('.producto-card');
        return cards[index] || null;
    }

    /**
     * Muestra un toast simple
     */
    mostrarToastSimple(mensaje) {
        // Crear contenedor del toast si no existe
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
            `;
            document.body.appendChild(toastContainer);
        }

        // Crear el toast
        const toast = document.createElement('div');
        toast.style.cssText = `
            background: #10b981;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            font-size: 0.9rem;
            font-weight: 500;
            animation: slideIn 0.3s ease;
            min-width: 300px;
        `;
        toast.textContent = mensaje;

        toastContainer.appendChild(toast);

        // Auto-remover después de 3 segundos
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Obtiene todos los productos
     */
    getTodosProductos() {
        return Array.from(this.contenedor.querySelectorAll('.producto-card'));
    }

    /**
     * Obtiene el número de productos
     */
    getNumeroProductos() {
        return this.contenedor.querySelectorAll('.producto-card').length;
    }

    /**
     * Configura listeners del template
     */
    setupTemplateListeners() {
        // Delegación de eventos para elementos dinámicos
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-toggle-product')) {
                const card = e.target.closest('.producto-card');
                this.toggleProductoBody(card);
            }
            
            if (e.target.classList.contains('btn-remove-product')) {
                const card = e.target.closest('.producto-card');
                this.eliminarProducto(card);
            }
        });
    }

    /**
     * Valida que haya al menos un producto con datos
     */
    validarProductos() {
        const productos = this.getTodosProductos();
        
        if (productos.length === 0) {
            return { valid: false, message: 'Debe agregar al menos una prenda' };
        }

        let tieneValido = false;
        productos.forEach(card => {
            const nombre = card.querySelector('input[name*="nombre_producto"]')?.value?.trim();
            if (nombre) {
                tieneValido = true;
            }
        });

        return {
            valid: tieneValido,
            message: tieneValido ? '' : 'Al menos una prenda debe tener un nombre'
        };
    }
}

// Exportar para uso global
const productoModule = new ProductoModule();
