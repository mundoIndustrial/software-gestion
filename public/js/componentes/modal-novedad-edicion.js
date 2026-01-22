/**
 * Modal Novedad Edición - Componente Reutilizable
 * Maneja modales para registrar novedades antes de actualizar una prenda existente
 */

console.log('[ModalNovedadEdicion]  Cargando...');

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
            // Convertir tallas array a JSON compatible con guardarTallasDesdeJson()
            const tallasJson = this.convertirTallasAlFormatoJson(this.prendaData.tallas);
            formData.append('cantidad_talla', JSON.stringify(tallasJson));
            formData.append('procesos', JSON.stringify(this.prendaData.procesos || {}));
            formData.append('novedad', novedad);
            
            // Obtener prenda_id - puede venir en diferentes propiedades
            const prendaId = this.prendaData.prenda_pedido_id || this.prendaData.id;
            console.log('[ModalNovedadEdicion] prendaData completa:', this.prendaData);
            console.log('[ModalNovedadEdicion] prendaData.prenda_pedido_id:', this.prendaData.prenda_pedido_id);
            console.log('[ModalNovedadEdicion] prendaData.id:', this.prendaData.id);
            console.log('[ModalNovedadEdicion] prendaId seleccionado:', prendaId);
            
            if (!prendaId || isNaN(prendaId)) {
                throw new Error('ID de prenda inválido o no disponible. Recibido: ' + prendaId);
            }
            
            const prendaIdInt = parseInt(prendaId);
            console.log('[ModalNovedadEdicion] prendaIdInt (convertido):', prendaIdInt);
            formData.append('prenda_id', prendaIdInt);
            
            if (this.prendaData.imagenes && this.prendaData.imagenes.length > 0) {
                this.prendaData.imagenes.forEach((img, idx) => {
                    if (img instanceof File) formData.append(`imagenes[${idx}]`, img);
                });
            }
            
            console.log('[ModalNovedadEdicion] Enviando a:', `/asesores/pedidos/${this.pedidoId}/actualizar-prenda`);
            console.log('[ModalNovedadEdicion] pedidoId:', this.pedidoId);

            const response = await fetch(`/asesores/pedidos/${this.pedidoId}/actualizar-prenda`, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                body: formData
            });
            
            const resultado = await response.json();
            if (!response.ok || !resultado.success) throw new Error(resultado.message);
            
            console.log('[ModalNovedadEdicion] Prenda actualizada');
            console.log('[ModalNovedadEdicion] Respuesta del servidor:', resultado.prenda);
            
            // IMPORTANTE: Recargar datos completos del pedido para asegurar que telasAgregadas y datos relacionados se actualizan correctamente
            if (window.prendaEnEdicion) {
                const pedidoId = window.prendaEnEdicion.pedidoId;
                console.log('[ModalNovedadEdicion] Recargando datos del pedido ' + pedidoId + ' para actualizar telas agregadas...');
                
                try {
                    const respDataEdicion = await fetch(`/asesores/pedidos-produccion/${pedidoId}/datos-edicion`);
                    const resultadoDataEdicion = await respDataEdicion.json();
                    
                    if (resultadoDataEdicion.success && resultadoDataEdicion.datos) {
                        console.log('[ModalNovedadEdicion] Datos del pedido recargados correctamente');
                        window.datosEdicionPedido = resultadoDataEdicion.datos;
                        
                        // Actualizar en prendasEdicion también
                        if (window.prendasEdicion) {
                            window.prendasEdicion.prendas = resultadoDataEdicion.datos.prendas;
                            window.prendasEdicion.pedidoId = resultadoDataEdicion.datos.id || resultadoDataEdicion.datos.numero_pedido;
                        }
                    }
                } catch (e) {
                    console.warn('[ModalNovedadEdicion] Error recargando datos del pedido:', e);
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
            console.error('[ModalNovedadEdicion] Error:', error);
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
}

window.modalNovedadEditacion = new ModalNovedadEdicion();
console.log('[ModalNovedadEdicion]  Instancia creada - método disponible:', typeof window.modalNovedadEditacion.mostrarModalYActualizar);
