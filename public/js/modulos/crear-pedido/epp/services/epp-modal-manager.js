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

            return;
        }
        modal.style.display = 'flex';

    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        const modal = document.getElementById(this.modalId);
        if (!modal) return;
        modal.style.display = 'none';
        this.limpiarFormulario();

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

    }

    /**
     * Mostrar producto seleccionado
     */
    mostrarProductoSeleccionado(producto) {
        document.getElementById('nombreProductoEPP').textContent = producto.nombre;
        document.getElementById('categoriaProductoEPP').textContent = producto.categoria;
        document.getElementById('codigoProductoEPP').textContent = producto.codigo;
        document.getElementById('productoCardEPP').style.display = 'flex';

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

        
        // Usar setTimeout para asegurar que el DOM esté listo
        setTimeout(() => {
            const inputCantidad = document.getElementById('cantidadEPP');
            const inputObservaciones = document.getElementById('observacionesEPP');
            
            if (inputCantidad) {
                inputCantidad.value = cantidad || 0;
                // Forzar actualización del valor
                inputCantidad.dispatchEvent(new Event('input', { bubbles: true }));

            } else {

            }
            
            if (inputObservaciones) {
                inputObservaciones.value = observaciones || '';
                // Forzar actualización del valor
                inputObservaciones.dispatchEvent(new Event('input', { bubbles: true }));

            } else {

            }
            

        }, 10);
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
                // Remover el atributo disabled
                elemento.removeAttribute('disabled');
                // Aplicar estilos mediante atributo de estilo
                elemento.setAttribute('style', `
                    width: 100%; 
                    padding: 0.75rem; 
                    border: 2px solid #3b82f6 !important; 
                    border-radius: 6px; 
                    font-size: 0.95rem; 
                    font-family: inherit; 
                    background: white !important; 
                    color: #1f2937 !important; 
                    cursor: text !important;
                `);
            }
        });

        document.getElementById('areaCargarImagenes').style.display = 'block';
        document.getElementById('mensajeSelecccionarEPP').style.display = 'none';

    }

    /**
     * Mostrar imágenes cargadas
     */
    mostrarImagenes(imagenes = []) {

        
        const contenedor = document.getElementById('contenedorImagenesSubidas');
        const listaImagenes = document.getElementById('listaImagenesSubidas');
        
        if (!contenedor || !listaImagenes) {

            return;
        }
        
        contenedor.innerHTML = '';

        if (imagenes && imagenes.length > 0) {
            listaImagenes.style.display = 'block';
            imagenes.forEach((img, idx) => {
                try {
                    const card = this._crearCardImagen(img);
                    contenedor.appendChild(card);

                } catch (e) {

                }
            });

        } else {
            listaImagenes.style.display = 'none';

        }
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


    }

    /**
     * Crear card de imagen
     */
    _crearCardImagen(imagen) {
        // Manejar tanto strings como objetos
        const imagenUrl = typeof imagen === 'string' ? imagen : (imagen.url || imagen.ruta_web || '');
        const imagenId = typeof imagen === 'string' ? `img-${Math.random()}` : (imagen.id || `img-${Math.random()}`);
        
        const card = document.createElement('div');
        card.id = `imagen-${imagenId}`;
        card.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb;';
        card.innerHTML = `
            <img src="${imagenUrl}" alt="Imagen EPP" style="width: 100%; height: 80px; object-fit: cover; display: block;">
            <button 
                type="button"
                onclick="window.eppImagenManager?.eliminarImagen('${imagenId}')"
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
