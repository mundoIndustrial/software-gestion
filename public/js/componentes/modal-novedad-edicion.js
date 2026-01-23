/**
 * Modal Novedad Edición - Componente Reutilizable
 * Maneja modales para registrar novedades antes de actualizar una prenda existente
 */



class ModalNovedadEdicion {
    constructor() {
        this.pedidoId = null;
        this.prendaData = null;
        this.prendaIndex = null;
        this.zIndexMaximoForzado = 999999;
    }

    forzarZIndexMaximo() {
        const container = document.querySelector('.swal2-container');
        const popup = document.querySelector('.swal2-popup');
        const backdrop = document.querySelector('.swal2-backdrop');
        if (container) container.style.zIndex = this.zIndexMaximoForzado;
        if (popup) popup.style.zIndex = this.zIndexMaximoForzado;
        if (backdrop) backdrop.style.zIndex = (this.zIndexMaximoForzado - 1);
    }

    async mostrarModalYActualizar(pedidoId, prendaData, prendaIndex) {
        this.pedidoId = pedidoId;
        this.prendaData = prendaData;
        this.prendaIndex = prendaIndex;

        return new Promise((resolve) => {
            const html = `
                <div style="text-align: left;">
                    <p style="margin: 0 0 1rem 0; color: #374151; font-size: 1rem;">
                        <strong>📝 Registra una novedad del cambio</strong>
                    </p>
                    <textarea id="modalNovedadEdicion" placeholder="Ej: Se cambió el color a rojo..." 
                              style="width: 100%; padding: 0.75rem; border: 2px solid #3b82f6; border-radius: 6px; 
                                     font-size: 0.95rem; min-height: 120px; font-family: inherit; resize: vertical;"></textarea>
                </div>
            `;

            Swal.fire({
                title: '📝 Registrar Cambios en Prenda',
                html: html,
                icon: 'info',
                confirmButtonText: '✓ Guardar Cambios',
                confirmButtonColor: '#3b82f6',
                cancelButtonText: 'Cancelar',
                showCancelButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    this.forzarZIndexMaximo();
                    const textarea = document.getElementById('modalNovedadEdicion');
                    if (textarea) textarea.focus();
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const novedad = document.getElementById('modalNovedadEdicion').value.trim();
                    if (!novedad) {
                        Swal.fire({
                            title: '⚠️ Campo requerido',
                            html: '<p>Por favor escribe una novedad</p>',
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        }).then(() => {
                            resolve(this.mostrarModalYActualizar(pedidoId, prendaData, prendaIndex));
                        });
                        return;
                    }
                    await this.actualizarPrendaConNovedad(novedad);
                    resolve();
                } else {
                    resolve();
                }
            });
        });
    }

    async actualizarPrendaConNovedad(novedad) {
        this.mostrarCargando();

        try {
            const formData = new FormData();
            formData.append('nombre_prenda', this.prendaData.nombre_prenda);
            formData.append('descripcion', this.prendaData.descripcion);
            formData.append('origen', this.prendaData.origen);
            
            // Enviar tallas - se guardan en prenda_pedido_tallas
            if (this.prendaData.tallas && Object.keys(this.prendaData.tallas).length > 0) {
                // Convertir de {GENERO: {TALLA: CANTIDAD}} a [{genero, talla, cantidad}, ...]
                const tallasArray = [];
                for (const [genero, tallas] of Object.entries(this.prendaData.tallas)) {
                    if (typeof tallas === 'object' && tallas !== null) {
                        for (const [talla, cantidad] of Object.entries(tallas)) {
                            if (cantidad > 0) {
                                tallasArray.push({
                                    genero: genero,
                                    talla: talla,
                                    cantidad: parseInt(cantidad)
                                });
                            }
                        }
                    }
                }
                if (tallasArray.length > 0) {
                    formData.append('tallas', JSON.stringify(tallasArray));
                    console.log('[modal-novedad-edicion] Tallas enviadas:', tallasArray);
                }
            }
            
            // Agregar variantes si existen
            // Las variantes pueden ser un objeto {manga, obs_manga, etc} OR un array
            if (this.prendaData.variantes) {
                const tieneVariantes = Array.isArray(this.prendaData.variantes) 
                    ? this.prendaData.variantes.length > 0
                    : Object.keys(this.prendaData.variantes).length > 0;
                    
                if (tieneVariantes) {
                    // Convertir variantes a formato esperado por backend: array de objetos
                    const variantesArray = this.convertirVariantesAlFormatoBackend(this.prendaData.variantes);
                    formData.append('variantes', JSON.stringify(variantesArray));
                    console.log('[modal-novedad-edicion] Variantes enviadas:', variantesArray);
                } else {
                    console.log('[modal-novedad-edicion] ⚠️ No hay variantes para enviar');
                }
            } else {
                console.log('[modal-novedad-edicion] ⚠️ No hay variantes para enviar');
            }
            
            formData.append('procesos', JSON.stringify(this.prendaData.procesos || {}));
            formData.append('novedad', novedad);
            
            // Obtener prenda_id - puede venir en diferentes propiedades
            const prendaId = this.prendaData.prenda_pedido_id || this.prendaData.id;




            
            if (!prendaId || isNaN(prendaId)) {
                throw new Error('ID de prenda inválido o no disponible. Recibido: ' + prendaId);
            }
            
            const prendaIdInt = parseInt(prendaId);

            formData.append('prenda_id', prendaIdInt);
            
            if (this.prendaData.imagenes && this.prendaData.imagenes.length > 0) {
                this.prendaData.imagenes.forEach((img, idx) => {
                    if (img instanceof File) formData.append(`imagenes[${idx}]`, img);
                });
            }
            



            const response = await fetch(`/asesores/pedidos/${this.pedidoId}/actualizar-prenda`, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                body: formData
            });
            
            const resultado = await response.json();
            if (!response.ok || !resultado.success) throw new Error(resultado.message);
            


            
            // IMPORTANTE: Recargar datos completos del pedido para asegurar que telasAgregadas y datos relacionados se actualizan correctamente
            if (window.prendaEnEdicion) {
                const pedidoId = window.prendaEnEdicion.pedidoId;

                
                try {
                    const respDataEdicion = await fetch(`/asesores/pedidos-produccion/${pedidoId}/datos-edicion`);
                    const resultadoDataEdicion = await respDataEdicion.json();
                    
                    if (resultadoDataEdicion.success && resultadoDataEdicion.datos) {

                        window.datosEdicionPedido = resultadoDataEdicion.datos;
                        
                        // Actualizar en prendasEdicion también
                        if (window.prendasEdicion) {
                            window.prendasEdicion.prendas = resultadoDataEdicion.datos.prendas;
                            window.prendasEdicion.pedidoId = resultadoDataEdicion.datos.id || resultadoDataEdicion.datos.numero_pedido;
                        }
                    }
                } catch (e) {

                    // Si falla la recarga automática, al menos actualizar la prenda con los datos que vinieron
                    if (resultado.prenda && window.datosEdicionPedido && window.prendaEnEdicion) {
                        const prendasIndex = window.prendaEnEdicion.prendasIndex;
                        if (prendasIndex !== null && prendasIndex !== undefined) {
                            window.datosEdicionPedido.prendas[prendasIndex] = resultado.prenda;
                            if (window.prendasEdicion && window.prendasEdicion.prendas) {
                                window.prendasEdicion.prendas[prendasIndex] = resultado.prenda;
                            }
                        }
                    }
                }
            }
            
            this.mostrarExito();
        } catch (error) {

            this.mostrarError(error.message);
        }
    }

    mostrarCargando() {
        Swal.fire({
            title: '⏳ Actualizando...',
            html: '<p>Por favor espera</p>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => this.forzarZIndexMaximo()
        });
    }

    mostrarExito() {
        Swal.fire({
            title: ' ¡Éxito!',
            html: '<p>Prenda actualizada correctamente</p>',
            icon: 'success',
            confirmButtonText: 'Ver lista de prendas',
            confirmButtonColor: '#3b82f6',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => this.forzarZIndexMaximo()
        }).then((result) => {
            if (result.isConfirmed) {
                if (typeof window.cerrarModalPrendaNueva === 'function') {
                    window.cerrarModalPrendaNueva();
                }
                if (typeof window.abrirEditarPrendas === 'function') {
                    window.abrirEditarPrendas();
                }
            }
        });
    }

    mostrarError(mensaje) {
        Swal.fire({
            title: ' Error',
            html: `<p>${mensaje}</p>`,
            icon: 'error',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#ef4444',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => this.forzarZIndexMaximo()
        });
    }

    convertirTallasAlFormatoJson(tallas) {
        // Convierte array de tallas {genero, talla, cantidad} a JSON {GENERO: {talla: cantidad}}
        if (!Array.isArray(tallas)) return {};
        
        const resultado = {};
        tallas.forEach(tallaObj => {
            if (tallaObj.genero && tallaObj.talla && tallaObj.cantidad) {
                const genero = tallaObj.genero.toUpperCase();
                if (!resultado[genero]) {
                    resultado[genero] = {};
                }
                resultado[genero][tallaObj.talla] = tallaObj.cantidad;
            }
        });
        return resultado;
    }

    convertirVariantesAlFormatoBackend(variantes) {
        // Convierte variantes (objeto o array) al formato esperado por backend
        // Formato esperado: [ { tipo_manga_id, tipo_broche_boton_id, manga_obs, broche_boton_obs, tiene_bolsillos, bolsillos_obs } ]
        
        // Si ya es un array, validar que tenga los campos correctos
        if (Array.isArray(variantes)) {
            return variantes.map(v => ({
                tipo_manga_id: v.tipo_manga_id || null,
                tipo_broche_boton_id: v.tipo_broche_boton_id || null,
                manga_obs: v.manga_obs || v.obs_manga || v.manga || '',
                broche_boton_obs: v.broche_boton_obs || v.obs_broche || v.broche || '',
                tiene_bolsillos: v.tiene_bolsillos || false,
                bolsillos_obs: v.bolsillos_obs || v.obs_bolsillos || '',
                tiene_reflectivo: v.tiene_reflectivo || false,
                reflectivo_obs: v.reflectivo_obs || v.obs_reflectivo || ''
            }));
        }
        
        // Si es un objeto con propiedades de variantes, convertir a array
        if (variantes && typeof variantes === 'object') {
            // Crear un único objeto de variante con todas las propiedades
            const varianteObject = {
                tipo_manga_id: variantes.tipo_manga_id || null,
                tipo_broche_boton_id: variantes.tipo_broche_boton_id || null,
                manga_obs: variantes.obs_manga || variantes.manga || variantes.manga_obs || '',
                broche_boton_obs: variantes.obs_broche || variantes.broche || variantes.broche_boton_obs || '',
                tiene_bolsillos: variantes.tiene_bolsillos || false,
                bolsillos_obs: variantes.obs_bolsillos || variantes.bolsillos_obs || '',
                tiene_reflectivo: variantes.tiene_reflectivo || false,
                reflectivo_obs: variantes.obs_reflectivo || variantes.reflectivo_obs || ''
            };
            
            // Retornar como array con un único elemento
            return [varianteObject];
        }
        
        // Si está vacío, retornar array vacío
        return [];
    }
}

window.modalNovedadEditacion = new ModalNovedadEdicion();

