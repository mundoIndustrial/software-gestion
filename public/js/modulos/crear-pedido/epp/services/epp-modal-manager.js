/**
 * EppModalManager - Gestiona la UI del modal de EPP
 * Patrón: UI Manager
 */

class EppModalManager {
    constructor(stateManager) {
        this.stateManager = stateManager;
        this.modalId = 'modal-agregar-epp';
    }

    /**
     * Abrir modal
     */
    abrirModal() {
        const modal = document.getElementById(this.modalId);
        if (!modal) {
            console.error('[EppModalManager] Modal no encontrado');
            return;
        }
        modal.style.display = 'flex';
        console.log('[EppModalManager] Modal abierto');
    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        const modal = document.getElementById(this.modalId);
        if (!modal) return;
        modal.style.display = 'none';
        this.limpiarFormulario();
        console.log('[EppModalManager] Modal cerrado');
    }

    /**
     * Limpiar formulario
     */
    limpiarFormulario() {
        const campos = [
            'cantidadEPP',
            'observacionesEPP'
        ];

        campos.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.value = '';
            }
        });

        this.limpiarProductoCard();
        this.limpiarImagenes();
        console.log('[EppModalManager] Formulario limpiado');
    }

    /**
     * Mostrar producto seleccionado
     */
    mostrarProductoSeleccionado(producto) {
        document.getElementById('nombreProductoEPP').textContent = producto.nombre;
        document.getElementById('categoriaProductoEPP').textContent = producto.categoria;
        document.getElementById('codigoProductoEPP').textContent = producto.codigo;
        document.getElementById('productoCardEPP').style.display = 'flex';
        console.log('[EppModalManager] Producto mostrado:', producto.nombre);
    }

    /**
     * Limpiar producto card
     */
    limpiarProductoCard() {
        document.getElementById('productoCardEPP').style.display = 'none';
        document.getElementById('mensajeSelecccionarEPP').style.display = 'block';
    }

    /**
     * Cargar valores en formulario
     */
    cargarValoresFormulario(talla, cantidad, observaciones) {
        document.getElementById('cantidadEPP').value = cantidad || 0;
        document.getElementById('observacionesEPP').value = observaciones || '';
        console.log('[EppModalManager] Valores cargados en formulario');
    }

    /**
     * Habilitar campos de edición
     */
    habilitarCampos() {
        const campos = [
            'cantidadEPP',
            'observacionesEPP'
        ];

        campos.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.disabled = false;
                elemento.style.background = 'white';
                elemento.style.color = '#1f2937';
                elemento.style.cursor = 'text';
            }
        });

        document.getElementById('areaCargarImagenes').style.display = 'block';
        document.getElementById('mensajeSelecccionarEPP').style.display = 'none';
        console.log('[EppModalManager] Campos habilitados');
    }

    /**
     * Mostrar imágenes cargadas
     */
    mostrarImagenes(imagenes = []) {
        const contenedor = document.getElementById('contenedorImagenesSubidas');
        contenedor.innerHTML = '';

        if (imagenes.length > 0) {
            document.getElementById('listaImagenesSubidas').style.display = 'block';
            imagenes.forEach(img => {
                const card = this._crearCardImagen(img);
                contenedor.appendChild(card);
            });
        } else {
            document.getElementById('listaImagenesSubidas').style.display = 'none';
        }

        console.log('[EppModalManager] Imágenes mostradas:', imagenes.length);
    }

    /**
     * Agregar imagen a la UI
     */
    agregarImagenUI(imagen) {
        const contenedor = document.getElementById('contenedorImagenesSubidas');
        const card = this._crearCardImagen(imagen);
        contenedor.appendChild(card);

        if (contenedor.children.length > 0) {
            document.getElementById('listaImagenesSubidas').style.display = 'block';
        }

        console.log('[EppModalManager] Imagen agregada a UI');
    }

    /**
     * Eliminar imagen de la UI
     */
    eliminarImagenUI(imagenId) {
        const card = document.getElementById(`imagen-${imagenId}`);
        if (card) {
            card.remove();
        }

        const contenedor = document.getElementById('contenedorImagenesSubidas');
        if (contenedor.children.length === 0) {
            document.getElementById('listaImagenesSubidas').style.display = 'none';
        }

        console.log('[EppModalManager] Imagen eliminada de UI');
    }

    /**
     * Crear card de imagen
     */
    _crearCardImagen(imagen) {
        const card = document.createElement('div');
        card.id = `imagen-${imagen.id}`;
        card.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb;';
        card.innerHTML = `
            <img src="${imagen.url}" alt="Imagen" style="width: 100%; height: 80px; object-fit: cover; display: block;">
            <button 
                type="button"
                onclick="window.eppImagenManager?.eliminarImagen(${imagen.id})"
                style="position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; background: rgba(255,0,0,0.8); color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; padding: 0; transition: background 0.2s ease;"
                onmouseover="this.style.background = 'rgba(220,0,0,1)'"
                onmouseout="this.style.background = 'rgba(255,0,0,0.8)'"
            >
                ×
            </button>
        `;
        return card;
    }

    /**
     * Limpiar imágenes
     */
    limpiarImagenes() {
        document.getElementById('contenedorImagenesSubidas').innerHTML = '';
        document.getElementById('listaImagenesSubidas').style.display = 'none';
    }

    /**
     * Obtener valores del formulario
     */
    obtenerValoresFormulario() {
        return {
            cantidad: parseInt(document.getElementById('cantidadEPP').value) || 0,
            observaciones: document.getElementById('observacionesEPP').value.trim() || null
        };
    }

    /**
     * Validar formulario
     */
    validarFormulario() {
        const valores = this.obtenerValoresFormulario();

        if (valores.cantidad <= 0) {
            alert('Cantidad debe ser mayor a 0');
            return false;
        }

        return true;
    }

    /**
     * Actualizar estado del botón
     */
    actualizarBoton() {
        const btnAgregar = document.getElementById('btnAgregarEPP');
        if (!btnAgregar) return;

        const valores = this.obtenerValoresFormulario();
        const puedeGuardar = valores.cantidad > 0;

        if (puedeGuardar) {
            btnAgregar.disabled = false;
            btnAgregar.style.opacity = '1';
            btnAgregar.style.cursor = 'pointer';
            btnAgregar.style.background = '#0066cc';
        } else {
            btnAgregar.disabled = true;
            btnAgregar.style.opacity = '0.5';
            btnAgregar.style.cursor = 'not-allowed';
        }
    }
}

// Exportar instancia global
window.eppModalManager = null; // Se inicializa después
