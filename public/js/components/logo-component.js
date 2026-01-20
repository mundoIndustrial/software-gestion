/**
 * LogoComponent - Gestión de Logo en Pedidos
 * Maneja fotos, técnicas, secciones/ubicaciones y observaciones del logo
 */

class LogoComponent {
    constructor() {
        this.fotosSeleccionadas = [];
        this.tecnicasSeleccionadas = [];
        this.seccionesSeleccionadas = [];
        this.observacionesSeleccionadas = [];
        this.ubicacionEditIndex = null;
        this.ubicacionTempNombre = '';
        
        // Opciones predefinidas por ubicación
        this.opcionesPorUbicacion = {
            'PECHO': ['IZQUIERDO', 'DERECHO', 'CENTRO'],
            'ESPALDA': ['SUPERIOR', 'CENTRO', 'INFERIOR'],
            'MANGA': ['IZQUIERDA', 'DERECHA', 'AMBAS'],
            'CUELLO': ['FRONTAL', 'POSTERIOR'],
            'BOLSILLO': ['IZQUIERDO', 'DERECHO', 'AMBOS']
        };
    }

    // ============================================================
    // GESTIÓN DE FOTOS
    // ============================================================

    /**
     * Renderizar galería de fotos del logo
     */
    renderizarFotos() {
        const container = document.getElementById('galeria-fotos-logo');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (this.fotosSeleccionadas.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; color: #9ca3af; text-align: center; padding: 2rem;">Sin imágenes</p>';
            return;
        }
        
        this.fotosSeleccionadas.forEach((foto, idx) => {
            const div = document.createElement('div');
            div.style.cssText = 'position: relative; display: inline-block; width: 100%; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: all 0.3s;';
            div.innerHTML = `
                <img src="${foto.preview}" 
                     alt="Imagen ${idx + 1}" 
                     style="width: 100%; height: 120px; object-fit: cover; cursor: pointer; transition: transform 0.2s; display: block;" 
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform=''"
                     onclick="abrirModalImagen('${foto.preview}', 'Logo - Imagen ${idx + 1}')">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0); transition: background 0.2s;" 
                     onmouseover="this.parentElement.querySelector('.btn-eliminar-foto').style.opacity='1'; this.style.background='rgba(0,0,0,0.3)'" 
                     onmouseout="this.parentElement.querySelector('.btn-eliminar-foto').style.opacity='0'; this.style.background='rgba(0,0,0,0)'"></div>
                <button type="button" onclick="window.LogoComponent.eliminarFoto(${idx})" 
                        style="position: absolute; top: 8px; right: 8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; z-index: 10; padding: 0; line-height: 1;" 
                        class="btn-eliminar-foto">×</button>
            `;
            container.appendChild(div);
        });
    }

    /**
     * Abrir modal para agregar fotos del logo
     */
    abrirModalAgregarFotos() {
        if (this.fotosSeleccionadas.length >= 5) {
            Swal.fire({
                icon: 'warning',
                title: 'Límite de imágenes',
                text: 'Ya has alcanzado el máximo de 5 imágenes permitidas',
                confirmButtonColor: '#0066cc'
            });
            return;
        }
        
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true;
        
        input.addEventListener('change', (e) => {
            this.manejarArchivos(e.target.files);
        });
        
        input.click();
    }

    /**
     * Manejar archivos de fotos del logo
     */
    async manejarArchivos(files) {
        if (!files || files.length === 0) return;

        const espacioDisponible = 5 - this.fotosSeleccionadas.length;
        if (files.length > espacioDisponible) {
            window.ImageService.showWarning(
                `Solo puedes agregar ${espacioDisponible} imagen${espacioDisponible !== 1 ? 's' : ''} más. Máximo 5 en total.`
            );
            return;
        }

        const logoCotizacionId = document.getElementById('logoCotizacionId')?.value;

        try {
            Swal.fire({
                title: 'Subiendo imágenes de logo...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const uploadedImages = [];
            
            for (const file of files) {
                if (!file.type.startsWith('image/')) continue;
                
                try {
                    const imageData = await window.ImageService.uploadLogoImage(file, logoCotizacionId);
                    uploadedImages.push(imageData);
                    console.log(` Imagen de logo subida: ${imageData.filename}`);
                } catch (error) {
                    console.error(` Error al subir imagen de logo:`, error);
                }
            }

            if (uploadedImages.length === 0) {
                throw new Error('No se pudo subir ninguna imagen');
            }

            uploadedImages.forEach(img => {
                this.fotosSeleccionadas.push({
                    preview: img.webp_url,
                    original: img.original_url,
                    filename: img.filename,
                    isNew: true
                });
            });

            Swal.fire({
                icon: 'success',
                title: '¡Imágenes subidas!',
                text: `${uploadedImages.length} imagen${uploadedImages.length !== 1 ? 'es' : ''} agregada${uploadedImages.length !== 1 ? 's' : ''} correctamente`,
                timer: 1500,
                showConfirmButton: false
            });

            this.renderizarFotos();
            
        } catch (error) {
            console.error(' Error al subir imágenes de logo:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudieron subir las imágenes',
                confirmButtonColor: '#ef4444'
            });
        }
    }

    /**
     * Eliminar foto del logo
     */
    async eliminarFoto(index) {
        const foto = this.fotosSeleccionadas[index];
        
        const result = await Swal.fire({
            title: '¿Eliminar imagen?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444'
        });

        if (result.isConfirmed) {
            if (foto.id) {
                try {
                    await window.ImageService.deleteLogoImage(foto.id);
                    console.log(` Imagen de logo eliminada del servidor: ${foto.id}`);
                } catch (error) {
                    console.error(' Error al eliminar imagen del servidor:', error);
                }
            }

            this.fotosSeleccionadas.splice(index, 1);
            this.renderizarFotos();
            
            Swal.fire({
                icon: 'success',
                title: 'Imagen eliminada',
                timer: 1500,
                showConfirmButton: false
            });
        }
    }

    // ============================================================
    // GESTIÓN DE TÉCNICAS
    // ============================================================

    /**
     * Agregar técnica al logo
     */
    agregarTecnica() {
        const selector = document.getElementById('selector_tecnicas_logo');
        const tecnica = selector.value;
        
        if (!tecnica) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona una técnica',
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }
        
        if (this.tecnicasSeleccionadas.includes(tecnica)) {
            Swal.fire({
                icon: 'info',
                title: 'Técnica ya agregada',
                text: 'Esta técnica ya está en la lista',
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }
        
        this.tecnicasSeleccionadas.push(tecnica);
        selector.value = '';
        this.renderizarTecnicas();
    }

    /**
     * Renderizar técnicas seleccionadas
     */
    renderizarTecnicas() {
        const container = document.getElementById('tecnicas_seleccionadas_logo');
        if (!container) return;
        
        container.innerHTML = '';
        
        this.tecnicasSeleccionadas.forEach((tecnica, index) => {
            const badge = document.createElement('span');
            badge.style.cssText = 'background: #0066cc; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;';
            badge.innerHTML = `
                ${tecnica}
                <span style="cursor: pointer; font-weight: bold; font-size: 1rem;" onclick="window.LogoComponent.eliminarTecnica(${index})">×</span>
            `;
            container.appendChild(badge);
        });
    }

    /**
     * Eliminar técnica
     */
    eliminarTecnica(index) {
        this.tecnicasSeleccionadas.splice(index, 1);
        this.renderizarTecnicas();
    }

    // ============================================================
    // GESTIÓN DE SECCIONES/UBICACIONES
    // ============================================================

    /**
     * Agregar sección/ubicación al logo
     */
    agregarSeccion() {
        const selector = document.getElementById('seccion_prenda_logo');
        const ubicacion = selector.value;
        const errorDiv = document.getElementById('errorSeccionPrendaLogo');
        
        if (!ubicacion) {
            selector.style.border = '2px solid #ef4444';
            selector.style.background = '#fee2e2';
            selector.classList.add('shake');
            if (errorDiv) errorDiv.style.display = 'block';
            
            setTimeout(() => {
                selector.style.border = '';
                selector.style.background = '';
                selector.classList.remove('shake');
            }, 600);
            
            setTimeout(() => {
                if (errorDiv) errorDiv.style.display = 'none';
            }, 3000);
            
            return;
        }
        
        selector.style.border = '';
        selector.style.background = '';
        if (errorDiv) errorDiv.style.display = 'none';
        
        const opciones = this.opcionesPorUbicacion[ubicacion] || [];
        this.ubicacionTempNombre = ubicacion;
        this.ubicacionEditIndex = null;
        
        this.abrirModalUbicacion(ubicacion, opciones, null);
    }

    /**
     * Editar sección existente
     */
    editarSeccion(index) {
        const seccion = this.seccionesSeleccionadas[index];
        const opciones = this.opcionesPorUbicacion[seccion.ubicacion] || [];
        this.ubicacionTempNombre = seccion.ubicacion;
        this.ubicacionEditIndex = index;
        
        this.abrirModalUbicacion(seccion.ubicacion, opciones, seccion);
    }

    /**
     * Abrir modal de ubicación
     */
    abrirModalUbicacion(ubicacion, opciones, seccionActual) {
        const opcionesHTML = opciones.map(op => `
            <label style="display: flex; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f9fafb'">
                <input type="checkbox" value="${op}" ${seccionActual?.opciones?.includes(op) ? 'checked' : ''} style="margin-right: 0.75rem; width: 18px; height: 18px; cursor: pointer;">
                <span style="font-weight: 500; color: #374151;">${op}</span>
            </label>
        `).join('');

        const html = `
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;" id="modalUbicacionLogo">
                <div style="background: white; border-radius: 16px; padding: 2rem; max-width: 600px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #f0f0f0;">
                        <h2 style="margin: 0; color: #1e40af; font-size: 1.3rem; font-weight: 700;">Editar Ubicación</h2>
                        <button type="button" onclick="window.LogoComponent.cerrarModalUbicacion()" style="background: none; border: none; color: #999; font-size: 1.8rem; cursor: pointer;">×</button>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">Nombre de la sección:</label>
                        <input type="text" id="nombreSeccionLogo" value="${seccionActual?.nombre || ubicacion}" 
                               style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #374151;">Ubicaciones específicas:</label>
                        <div id="opcionesUbicacionLogo" style="display: grid; gap: 0.5rem;">
                            ${opcionesHTML}
                        </div>
                        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <input type="text" id="nuevaOpcionLogo" placeholder="Agregar ubicación personalizada" 
                                   style="flex: 1; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                            <button type="button" onclick="window.LogoComponent.agregarOpcionPersonalizada()" 
                                    style="background: #0066cc; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                + Agregar
                            </button>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">Observaciones:</label>
                        <textarea id="obsUbicacionLogo" rows="3" 
                                  style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; resize: vertical;">${seccionActual?.observaciones || ''}</textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="window.LogoComponent.cerrarModalUbicacion()" 
                                style="background: #e5e7eb; color: #374151; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            Cancelar
                        </button>
                        <button type="button" onclick="window.LogoComponent.guardarUbicacion()" 
                                style="background: #0066cc; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', html);
    }

    /**
     * Agregar opción personalizada
     */
    agregarOpcionPersonalizada() {
        const input = document.getElementById('nuevaOpcionLogo');
        const opcion = input.value.trim().toUpperCase();
        
        if (!opcion) return;
        
        const container = document.getElementById('opcionesUbicacionLogo');
        const existe = Array.from(container.querySelectorAll('input[type="checkbox"]'))
            .some(cb => cb.value === opcion);
        
        if (existe) {
            Swal.fire({
                icon: 'info',
                title: 'Opción ya existe',
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }
        
        const label = document.createElement('label');
        label.style.cssText = 'display: flex; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px; cursor: pointer; transition: all 0.2s;';
        label.innerHTML = `
            <input type="checkbox" value="${opcion}" checked style="margin-right: 0.75rem; width: 18px; height: 18px; cursor: pointer;">
            <span style="font-weight: 500; color: #374151;">${opcion}</span>
            <button type="button" onclick="window.LogoComponent.eliminarOpcion('${opcion}')" 
                    style="margin-left: auto; background: #ef4444; color: white; border: none; border-radius: 4px; padding: 0.25rem 0.5rem; cursor: pointer; font-size: 0.75rem;">
                Eliminar
            </button>
        `;
        
        container.appendChild(label);
        input.value = '';
    }

    /**
     * Eliminar opción personalizada
     */
    eliminarOpcion(opcion) {
        const container = document.getElementById('opcionesUbicacionLogo');
        const labels = container.querySelectorAll('label');
        
        labels.forEach(label => {
            const checkbox = label.querySelector('input[type="checkbox"]');
            if (checkbox && checkbox.value === opcion) {
                label.remove();
            }
        });
    }

    /**
     * Cerrar modal de ubicación
     */
    cerrarModalUbicacion() {
        const modal = document.getElementById('modalUbicacionLogo');
        if (modal) modal.remove();
    }

    /**
     * Guardar ubicación
     */
    guardarUbicacion() {
        const nombre = document.getElementById('nombreSeccionLogo').value.trim().toUpperCase();
        const checkboxes = document.querySelectorAll('#opcionesUbicacionLogo input[type="checkbox"]:checked');
        const obs = document.getElementById('obsUbicacionLogo').value;
        
        const opcionesSeleccionadas = Array.from(checkboxes).map(cb => cb.value);
        
        if (!nombre) {
            Swal.fire({
                icon: 'warning',
                title: 'Nombre requerido',
                text: 'Ingresa un nombre para la sección',
                timer: 1500
            });
            return;
        }
        
        const seccion = {
            ubicacion: this.ubicacionTempNombre,
            nombre: nombre,
            opciones: opcionesSeleccionadas,
            observaciones: obs
        };
        
        if (this.ubicacionEditIndex !== null) {
            this.seccionesSeleccionadas[this.ubicacionEditIndex] = seccion;
        } else {
            this.seccionesSeleccionadas.push(seccion);
        }
        
        this.cerrarModalUbicacion();
        this.renderizarSecciones();
    }

    /**
     * Renderizar secciones
     */
    renderizarSecciones() {
        const container = document.getElementById('secciones_agregadas_logo');
        if (!container) return;
        
        container.innerHTML = '';
        
        this.seccionesSeleccionadas.forEach((seccion, index) => {
            const card = document.createElement('div');
            card.style.cssText = 'background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem;';
            card.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #1e40af; margin-bottom: 0.25rem;">${seccion.nombre}</div>
                        <div style="font-size: 0.85rem; color: #6b7280;">
                            <strong>Ubicaciones:</strong> ${seccion.opciones.join(', ') || 'Sin especificar'}
                        </div>
                        ${seccion.observaciones ? `<div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem;"><strong>Obs:</strong> ${seccion.observaciones}</div>` : ''}
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" onclick="window.LogoComponent.editarSeccion(${index})" 
                                style="background: #0066cc; color: white; border: none; padding: 0.4rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">
                            Editar
                        </button>
                        <button type="button" onclick="window.LogoComponent.eliminarSeccion(${index})" 
                                style="background: #ef4444; color: white; border: none; padding: 0.4rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">
                            Eliminar
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    /**
     * Eliminar sección
     */
    async eliminarSeccion(index) {
        const result = await Swal.fire({
            title: '¿Eliminar sección?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444'
        });

        if (result.isConfirmed) {
            this.seccionesSeleccionadas.splice(index, 1);
            this.renderizarSecciones();
        }
    }

    // ============================================================
    // UTILIDADES
    // ============================================================

    /**
     * Obtener datos del logo para envío
     */
    obtenerDatos() {
        return {
            fotos: this.fotosSeleccionadas,
            tecnicas: this.tecnicasSeleccionadas,
            secciones: this.seccionesSeleccionadas,
            observaciones: this.observacionesSeleccionadas
        };
    }

    /**
     * Limpiar todos los datos
     */
    limpiar() {
        this.fotosSeleccionadas = [];
        this.tecnicasSeleccionadas = [];
        this.seccionesSeleccionadas = [];
        this.observacionesSeleccionadas = [];
        this.ubicacionEditIndex = null;
        this.ubicacionTempNombre = '';
        
        this.renderizarFotos();
        this.renderizarTecnicas();
        this.renderizarSecciones();
    }

    /**
     * Cargar datos existentes
     */
    cargarDatos(datos) {
        if (datos.fotos) this.fotosSeleccionadas = datos.fotos;
        if (datos.tecnicas) this.tecnicasSeleccionadas = datos.tecnicas;
        if (datos.secciones) this.seccionesSeleccionadas = datos.secciones;
        if (datos.observaciones) this.observacionesSeleccionadas = datos.observaciones;
        
        this.renderizarFotos();
        this.renderizarTecnicas();
        this.renderizarSecciones();
    }
}

// Exportar globalmente
window.LogoComponent = new LogoComponent();
