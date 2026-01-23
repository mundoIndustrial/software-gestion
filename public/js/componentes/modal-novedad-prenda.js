/**
 * Modal Novedad Prenda - Componente Reutilizable
 * 
 * Maneja:
 * - Modal para registrar novedad antes de agregar prenda
 * - Modal de cargando durante guardado
 * - Modal de éxito
 * - Modal de error
 * 
 * ESTRATEGIA DE Z-INDEX:
 * - Fuerza z-index muy alto (999999) para asegurar que aparezca encima de TODO
 * - Manipula directamente el DOM después de que SweetAlert abre el modal
 * - Usa MutationObserver para monitorear y forzar el z-index
 */

class ModalNovedadPrenda {
    constructor() {
        this.pedidoId = null;
        this.prendaData = null;
        this.zIndexMaximoForzado = 999999;
    }

    /**
     * Forzar z-index en el modal (estrategia agresiva)
     */
    forzarZIndexMaximo() {
        const container = document.querySelector('.swal2-container');
        const popup = document.querySelector('.swal2-popup');
        const backdrop = document.querySelector('.swal2-backdrop');
        
        if (container) container.style.zIndex = this.zIndexMaximoForzado;
        if (popup) popup.style.zIndex = this.zIndexMaximoForzado;
        if (backdrop) backdrop.style.zIndex = (this.zIndexMaximoForzado - 1);
    }

    /**
     * Mostrar modal de novedad (paso obligatorio antes de crear prenda)
     */
    async mostrarModalYGuardar(pedidoId, prendaData) {
        this.pedidoId = pedidoId;
        this.prendaData = prendaData;

        return new Promise((resolve) => {
            const html = `
                <div style="text-align: left;">
                    <p style="margin: 0 0 1rem 0; color: #374151; font-size: 1rem;">
                        <strong>📝 Registra una novedad del cambio</strong>
                    </p>
                    <p style="margin: 0 0 1rem 0; color: #6b7280; font-size: 0.875rem;">
                        Explica qué cambios se están realizando en este pedido:
                    </p>
                    <textarea id="modalNovedad" placeholder="Ej: Se agrega nueva prenda de tela drill color rojo con variaciones..." 
                              style="width: 100%; padding: 0.75rem; border: 2px solid #3b82f6; border-radius: 6px; 
                                     font-size: 0.95rem; min-height: 120px; font-family: inherit; resize: vertical;"></textarea>
                </div>
            `;

            Swal.fire({
                title: '📝 Agregar Novedad del Cambio',
                html: html,
                icon: 'info',
                confirmButtonText: '✓ Guardar y Crear Prenda',
                confirmButtonColor: '#3b82f6',
                cancelButtonText: 'Cancelar',
                cancelButtonColor: '#ef4444',
                showCancelButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: {
                    container: 'swal-modal-novedad'
                },
                didOpen: () => {
                    // Forzar z-index INMEDIATAMENTE
                    this.forzarZIndexMaximo();
                    
                    // Monitorear cambios y mantener z-index forzado
                    const observer = new MutationObserver(() => {
                        this.forzarZIndexMaximo();
                    });
                    
                    const container = document.querySelector('.swal2-container');
                    if (container) {
                        observer.observe(container, {
                            attributes: true,
                            subtree: true,
                            attributeFilter: ['style', 'class']
                        });
                    }
                    
                    const swalPopup = document.querySelector('.swal2-popup');
                    if (swalPopup) {
                        swalPopup.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1)';
                    }
                    // Enfoque automático en textarea
                    setTimeout(() => {
                        const textarea = document.getElementById('modalNovedad');
                        if (textarea) textarea.focus();
                    }, 100);
                    
                    // Parar el observer cuando se cierre el modal
                    const cerrarObserver = new MutationObserver(() => {
                        if (!document.querySelector('.swal2-container')) {
                            observer.disconnect();
                            cerrarObserver.disconnect();
                        }
                    });
                    cerrarObserver.observe(document.body, { childList: true });
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const novedad = document.getElementById('modalNovedad').value.trim();
                    
                    if (!novedad) {
                        Swal.fire({
                            title: '⚠️ Campo requerido',
                            html: '<p style="color: #374151;">Por favor, escribe una novedad para registrar este cambio.</p>',
                            icon: 'warning',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#f59e0b',
                            customClass: {
                                container: 'swal-modal-warning'
                            },
                            didOpen: () => {
                                this.forzarZIndexMaximo();
                            }
                        }).then(() => {
                            resolve(this.mostrarModalYGuardar(pedidoId, prendaData));
                        });
                        return;
                    }
                    
                    // PASO 2: Proceder a guardar la prenda con la novedad
                    await this.guardarPrendaConNovedad(novedad);
                    resolve();
                } else {
                    resolve();
                }
            });
        });
    }

    /**
     * Guardar prenda con novedad
     */
    async guardarPrendaConNovedad(novedad) {
        // Mostrar modal de cargando
        this.mostrarCargando();

        try {
            // Crear FormData para enviar archivos
            const formData = new FormData();
            formData.append('nombre_prenda', this.prendaData.nombre_prenda);
            formData.append('descripcion', this.prendaData.descripcion);
            formData.append('origen', this.prendaData.origen);
            formData.append('cantidad_talla', JSON.stringify(this.prendaData.cantidad_talla || {}));
            formData.append('procesos', JSON.stringify(this.prendaData.procesos || {}));
            formData.append('novedad', novedad);  // AGREGAR NOVEDAD
            
            // Agregar imágenes de prenda
            if (this.prendaData.imagenes && this.prendaData.imagenes.length > 0) {
                this.prendaData.imagenes.forEach((img, idx) => {
                    if (img instanceof File) {
                        formData.append(`imagenes[${idx}]`, img);
                    }
                });
            }
            
            // Agregar telas
            if (this.prendaData.telasAgregadas && this.prendaData.telasAgregadas.length > 0) {
                this.prendaData.telasAgregadas.forEach((tela, telaIdx) => {
                    formData.append(`telas[${telaIdx}][tela]`, tela.tela);
                    formData.append(`telas[${telaIdx}][color]`, tela.color);
                    formData.append(`telas[${telaIdx}][referencia]`, tela.referencia);
                    
                    // Agregar imágenes de tela
                    if (tela.imagenes && tela.imagenes.length > 0) {
                        tela.imagenes.forEach((img, imgIdx) => {
                            if (img instanceof File) {
                                formData.append(`telas[${telaIdx}][imagenes][${imgIdx}]`, img);
                            }
                        });
                    }
                });
            }
            
            const response = await fetch(`/asesores/pedidos/${this.pedidoId}/agregar-prenda`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: formData
            });
            
            const resultado = await response.json();
            
            if (!response.ok || !resultado.success) {
                throw new Error(resultado.message || 'Error al guardar prenda en el servidor');
            }
            

            
            // Mostrar modal de éxito
            this.mostrarExito();
            
        } catch (error) {

            
            // Mostrar modal de error
            this.mostrarError(error.message);
        }
    }

    /**
     * Mostrar modal de cargando
     */
    mostrarCargando() {
        Swal.fire({
            title: '⏳ Cargando',
            html: '<div style="display: flex; align-items: center; gap: 1rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #3b82f6;"></i><div style="text-align: left;"><p style="margin: 0; font-size: 1rem; color: #374151;">Guardando prenda en la base de datos...</p><p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #6b7280;">Por favor espera</p></div></div>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            customClass: {
                container: 'swal-modal-cargando'
            },
            didOpen: () => {
                this.forzarZIndexMaximo();
                const swalPopup = document.querySelector('.swal2-popup');
                if (swalPopup) {
                    swalPopup.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1)';
                }
            }
        });
    }

    /**
     * Mostrar modal de éxito
     */
    mostrarExito() {
        Swal.fire({
            title: ' ¡Éxito!',
            html: '<div style="text-align: left;"><p style="margin: 0 0 1rem 0; font-size: 1rem; color: #374151;"><strong>Prenda agregada correctamente</strong></p><p style="margin: 0; font-size: 0.875rem; color: #6b7280;">La prenda se ha guardado en la base de datos y asociado al pedido.</p></div>',
            icon: 'success',
            confirmButtonText: ' Ver lista de prendas',
            confirmButtonColor: '#3b82f6',
            showConfirmButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: {
                container: 'swal-modal-exito'
            },
            didOpen: () => {
                this.forzarZIndexMaximo();
                const swalPopup = document.querySelector('.swal2-popup');
                if (swalPopup) {
                    swalPopup.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1)';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Cerrar el modal de edición de prendas
                if (typeof window.cerrarModalPrendaNueva === 'function') {

                    window.cerrarModalPrendaNueva();
                }
                
                // Ir a la lista de prendas
                if (typeof window.abrirEditarPrendas === 'function') {

                    window.abrirEditarPrendas();
                }
            }
        });
    }

    /**
     * Mostrar modal de error
     */
    mostrarError(mensaje) {
        Swal.fire({
            title: ' Error',
            html: `<div style="text-align: left;"><p style="margin: 0 0 1rem 0; font-size: 1rem; color: #374151;"><strong>No se pudo guardar la prenda</strong></p><p style="margin: 0; font-size: 0.875rem; color: #6b7280;">${mensaje}</p></div>`,
            icon: 'error',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#ef4444',
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: {
                container: 'swal-modal-error'
            },
            didOpen: () => {
                this.forzarZIndexMaximo();
            }
        });
    }
}

// Instancia global reutilizable
window.modalNovedadPrenda = new ModalNovedadPrenda();
