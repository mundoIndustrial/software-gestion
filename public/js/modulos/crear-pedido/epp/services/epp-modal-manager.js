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

        // Ocultar formulario de crear EPP y mostrar búsqueda
        const formularioCrear = document.getElementById('formularioEPPNuevo');
        const inputBuscador = document.getElementById('inputBuscadorEPP');
        if (formularioCrear) {
            formularioCrear.style.display = 'none';
        }
        if (inputBuscador) {
            inputBuscador.value = '';
            inputBuscador.focus();
        }

        this.limpiarProductoCard();
        this.limpiarImagenes();

    }

    /**
     * Mostrar producto seleccionado
     */
    mostrarProductoSeleccionado(producto) {
        console.log('🎯 [ModalManager] mostrarProductoSeleccionado:', producto);
        
        const nombreElement = document.getElementById('nombreProductoEPP');
        console.log('🎯 [ModalManager] Elemento nombreProductoEPP encontrado:', !!nombreElement);
        if (nombreElement) {
            nombreElement.textContent = producto.nombre_completo || producto.nombre;
            console.log('🎯 [ModalManager] Nombre mostrado:', producto.nombre_completo || producto.nombre);
        } else {
            console.warn(' [ModalManager] Elemento nombreProductoEPP NO ENCONTRADO');
        }
        
        // Mostrar imagen si existe
        const imagenElemento = document.getElementById('imagenProductoEPP');
        console.log('🎯 [ModalManager] Elemento imagenProductoEPP encontrado:', !!imagenElemento);
        if (imagenElemento && producto.imagen) {
            imagenElemento.src = producto.imagen;
            console.log('🎯 [ModalManager] Imagen mostrada:', producto.imagen);
        }
        
        const productoCard = document.getElementById('productoCardEPP');
        console.log('🎯 [ModalManager] Elemento productoCardEPP encontrado:', !!productoCard);
        if (productoCard) {
            productoCard.style.display = 'flex';
            console.log('✅ [ModalManager] Tarjeta de producto mostrada');
        } else {
            console.warn(' [ModalManager] Elemento productoCardEPP NO ENCONTRADO');
        }
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
        console.log('🔓 [ModalManager] habilitarCampos() iniciado');
        const campos = [
            'cantidadEPP',
            'observacionesEPP'
        ];

        campos.forEach(id => {
            const elemento = document.getElementById(id);
            console.log(`🔓 [ModalManager] Buscando campo: ${id}, encontrado:`, !!elemento);
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
                console.log(`✅ [ModalManager] Campo ${id} habilitado`);
            } else {
                console.warn(` [ModalManager] Campo ${id} NO ENCONTRADO en el DOM`);
            }
        });

        // Habilitar área de imágenes
        const areaImagenes = document.getElementById('areaCargarImagenes');
        console.log('🖼️ [ModalManager] Buscando areaCargarImagenes, encontrada:', !!areaImagenes);
        if (areaImagenes) {
            areaImagenes.setAttribute('style', `
                display: block; 
                margin-bottom: 1rem; 
                padding: 1.5rem; 
                background: white; 
                border: 2px dashed #0066cc; 
                border-radius: 8px; 
                text-align: center; 
                cursor: pointer; 
                transition: all 0.3s ease;
                opacity: 1;
            `);
            console.log('✅ [ModalManager] Área de imágenes habilitada');
        } else {
            console.warn(' [ModalManager] Área de imágenes NO ENCONTRADA en el DOM');
        }

        const mensajeSeleccionar = document.getElementById('mensajeSelecccionarEPP');
        console.log('📝 [ModalManager] Buscando mensajeSelecccionarEPP, encontrado:', !!mensajeSeleccionar);
        if (mensajeSeleccionar) {
            mensajeSeleccionar.style.display = 'none';
            console.log('✅ [ModalManager] Mensaje de selección ocultado');
        } else {
            console.warn(' [ModalManager] Mensaje de selección NO ENCONTRADO en el DOM');
        }
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
        const imagenUrl = typeof imagen === 'string' ? imagen : (imagen.preview || imagen.url || imagen.ruta_web || '');
        const imagenId = typeof imagen === 'string' ? `img-${Math.random()}` : (imagen.id || `img-${Math.random()}`);
        
        const card = document.createElement('div');
        card.id = `imagen-${imagenId}`;
        card.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb; width: 100%; aspect-ratio: 1 / 1;';
        card.innerHTML = `
            <img src="${imagenUrl}" alt="Imagen EPP" style="width: 100%; height: 100%; object-fit: cover; display: block;">
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
