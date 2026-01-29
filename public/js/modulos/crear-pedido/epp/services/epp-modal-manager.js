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
        // IMPORTANTE: Limpiar estado de imágenes para evitar "imágenes fantasma"
        this.stateManager.limpiarImagenesSubidas();

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
        console.log(' [ModalManager] mostrarProductoSeleccionado:', producto);
        
        const nombreElement = document.getElementById('nombreProductoEPP');
        console.log(' [ModalManager] Elemento nombreProductoEPP encontrado:', !!nombreElement);
        if (nombreElement) {
            nombreElement.textContent = producto.nombre_completo || producto.nombre;
            console.log(' [ModalManager] Nombre mostrado:', producto.nombre_completo || producto.nombre);
        } else {
            console.warn(' [ModalManager] Elemento nombreProductoEPP NO ENCONTRADO');
        }
        
        const productoCard = document.getElementById('productoCardEPP');
        console.log(' [ModalManager] Elemento productoCardEPP encontrado:', !!productoCard);
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
        const productoCard = document.getElementById('productoCardEPP');
        if (productoCard) {
            productoCard.style.display = 'none';
        }
        
        const mensaje = document.getElementById('mensajeSelecccionarEPP');
        if (mensaje) {
            mensaje.style.display = 'block';
        }
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
        console.log('[ModalManager] Buscando mensajeSelecccionarEPP, encontrado:', !!mensajeSeleccionar);
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
            console.warn('⚠️ [ModalManager] Contenedor o listaImagenes no encontrados');
            return;
        }
        
        contenedor.innerHTML = '';

        if (imagenes && imagenes.length > 0) {
            console.log(`📸 [ModalManager] mostrarImagenes: ${imagenes.length} imagen(es) para mostrar`);
            listaImagenes.style.display = 'block';
            imagenes.forEach((img, idx) => {
                try {
                    console.log(`   Imagen ${idx}:`, img);
                    const card = this._crearCardImagen(img);
                    contenedor.appendChild(card);
                    console.log(`   ✅ Imagen ${idx} agregada al DOM`);

                } catch (e) {
                    console.error(`   ❌ Error al crear card para imagen ${idx}:`, e);
                }
            });
            console.log(`✅ [ModalManager] ${imagenes.length} imagen(es) mostrada(s)`);

        } else {
            // IMPORTANTE: No ocultar el contenedor cuando está vacío
            // para permitir agregar nuevas imágenes después
            // Solo ocultar si explícitamente se llama a limpiarImagenes()
            console.log('📸 [ModalManager] mostrarImagenes: sin imágenes, preparando contenedor');
            // listaImagenes.style.display = 'none'; // ELIMINADO - esto bloqueaba agregar nuevas
        }
    }

    /**
     * Agregar imagen a la UI
     */
    agregarImagenUI(imagen) {
        console.log('📸 [ModalManager] agregarImagenUI() llamado con imagen:', imagen.id);
        
        const contenedor = document.getElementById('contenedorImagenesSubidas');
        const listaImagenes = document.getElementById('listaImagenesSubidas');
        
        if (!contenedor || !listaImagenes) {
            console.warn('⚠️ [ModalManager] Contenedor o listaImagenes no encontrado');
            return;
        }
        
        const card = this._crearCardImagen(imagen);
        contenedor.appendChild(card);
        console.log('📸 [ModalManager] Carta agregada al contenedor');

        // IMPORTANTE: Asegurar que el contenedor sea visible
        listaImagenes.style.display = 'block';
        console.log('📸 [ModalManager] listaImagenesSubidas hecha visible');
    }

    /**
     * Eliminar imagen de la UI
     */
    eliminarImagenUI(imagenId) {
        console.log('🖼️ [ModalManager] eliminarImagenUI() buscando imagen con ID:', imagenId);
        
        // Intentar búsqueda con prefijo "imagen-"
        let card = document.getElementById(`imagen-${imagenId}`);
        console.log('🖼️ [ModalManager] Búsqueda 1 (imagen-${imagenId}):', card ? 'ENCONTRADO' : 'NO ENCONTRADO');
        
        // Si no encuentra, intentar búsqueda sin prefijo
        if (!card) {
            card = document.getElementById(imagenId);
            console.log('🖼️ [ModalManager] Búsqueda 2 (solo imagenId):', card ? 'ENCONTRADO' : 'NO ENCONTRADO');
        }
        
        // Si no encuentra, intentar buscar por atributo data-imagen-id
        if (!card) {
            card = document.querySelector(`[data-imagen-id="${imagenId}"]`);
            console.log('🖼️ [ModalManager] Búsqueda 3 (data-imagen-id):', card ? 'ENCONTRADO' : 'NO ENCONTRADO');
        }
        
        // Si aún no encuentra, buscar todos los elementos del contenedor
        if (!card) {
            const contenedor = document.getElementById('contenedorImagenesSubidas');
            if (contenedor) {
                const todasLasCartas = contenedor.querySelectorAll('div[id^="imagen-"]');
                console.log('🖼️ [ModalManager] Total de cartas en contenedor:', todasLasCartas.length);
                todasLasCartas.forEach((carta, idx) => {
                    console.log(`   Carta ${idx}: ID=${carta.id}`);
                    if (carta.id.includes(imagenId) || carta.id.endsWith(imagenId)) {
                        card = carta;
                        console.log('🖼️ [ModalManager] Búsqueda 4: ENCONTRADO por coincidencia parcial:', card.id);
                    }
                });
            }
        }
        
        if (card) {
            console.log('✅ [ModalManager] Removiendo carta:', card.id);
            console.log('   Antes de remove - card.parentNode:', !!card.parentNode);
            // Asegurar que se oculte primero por si acaso
            card.style.display = 'none';
            // Luego remover del DOM
            setTimeout(() => {
                if (card.parentNode) {
                    card.remove();
                    console.log('   Después de remove (async) - eliminado del DOM');
                }
            }, 10);
        } else {
            console.warn('⚠️ [ModalManager] No se encontró elemento para eliminar. ImagenId buscado:', imagenId);
        }

        const contenedor = document.getElementById('contenedorImagenesSubidas');
        const listaImagenes = document.getElementById('listaImagenesSubidas');
        
        // IMPORTANTE: NO ocultar el contenedor cuando está vacío
        // Esto permite que el usuario pueda agregar nuevas imágenes después de eliminar
        if (contenedor && contenedor.children.length === 0) {
            console.log('✅ [ModalManager] Contenedor vacío pero VISIBLE para agregar nuevas imágenes');
            // Asegurar que está visible para agregar nuevas
            if (listaImagenes) {
                listaImagenes.style.display = 'block';
            }
        }
    }

    /**
     * Crear card de imagen
     */
    _crearCardImagen(imagen) {
        // Manejar tanto strings como objetos
        const imagenUrl = typeof imagen === 'string' ? imagen : (imagen.preview || imagen.url || imagen.ruta_web || imagen.ruta_webp || imagen.ruta_original || '');
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
        const contenedor = document.getElementById('contenedorImagenesSubidas');
        if (contenedor) {
            contenedor.innerHTML = '';
        }
        // IMPORTANTE: NO ocultar listaImagenesSubidas aquí
        // Solo vaciar el contenedor. habilitarCampos() lo mostrará cuando sea necesario
        console.log('🗑️ [ModalManager] limpiarImagenes() completado');
    }

    /**
     * Obtener valores del formulario
     */
    obtenerValoresFormulario() {
        const cantidadInput = document.getElementById('cantidadEPP');
        const observacionesInput = document.getElementById('observacionesEPP');
        
        return {
            cantidad: cantidadInput ? (parseInt(cantidadInput.value) || 0) : 0,
            observaciones: observacionesInput ? (observacionesInput.value.trim() || null) : null
        };
    }

    /**
     * Validar formulario
     */
    validarFormulario() {
        const valores = this.obtenerValoresFormulario();

        if (valores.cantidad <= 0) {
            if (window.eppNotificationService) {
                window.eppNotificationService.mostrarValidacion(
                    '⚠️ Cantidad Requerida',
                    'La cantidad debe ser mayor a 0'
                );
            } else {
                alert('Cantidad debe ser mayor a 0');
            }
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
