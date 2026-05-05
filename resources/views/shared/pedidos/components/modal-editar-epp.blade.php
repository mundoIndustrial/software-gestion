<!-- Modal para editar EPP del pedido -->
<style>
    /* Estilos para que el toast esté siempre encima */
    .swal-toast-container {
        z-index: 99999999 !important;
        position: fixed !important;
    }
    
    .swal2-container.swal-toast-container {
        z-index: 99999999 !important;
        position: fixed !important;
    }
    
    .swal2-container.swal-toast-container.swal2-top-end {
        z-index: 99999999 !important;
        position: fixed !important;
        top: 20px !important;
        right: 20px !important;
    }
    
    .swal2-container.swal-toast-container .swal2-popup {
        z-index: 99999999 !important;
    }
</style>

<script>
    // Debug: Log de z-index del modal
 
    /**
     *  Recargar la tabla de pedidos sin recargar la página
     */
    async function recargarTablaPedidos() {
        try {
            
            // Obtener parámetros de URL actuales
            const urlParams = new URLSearchParams(window.location.search);
            const currentUrl = window.location.pathname + window.location.search;
            
            // Hacer fetch de la página actual
            const response = await fetch(currentUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'text/html',
                }
            });
            
            if (!response.ok) {
                return;
            }
            
            const html = await response.text();
            
            // Crear un DOM temporal para parsear el HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Buscar la tabla en el HTML nuevo
            const nuevaTabla = tempDiv.querySelector('.table-scroll-container');
            const tablaActual = document.querySelector('.table-scroll-container');
            
            if (nuevaTabla && tablaActual) {
                // Reemplazar la tabla actual con la nueva
                tablaActual.replaceWith(nuevaTabla);
            } else {
            }
        } catch (error) {
            console.error(' [recargarTablaPedidos] Error:', error);
        }
    }

    async function refrescarDatosEdicionPedidoDesdeServidor(pedidoId) {
        if (!pedidoId) return false;

        try {
            const response = await fetch(`/api/asesores/pedidos/${pedidoId}/factura-datos`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                return false;
            }

            const respuesta = await response.json();
            if (!respuesta?.success) {
                return false;
            }

            const datos = respuesta.data || respuesta.datos || {};
            window.datosEdicionPedido = {
                id: datos.id,
                numero_pedido: datos.numero_pedido || datos.numero,
                numero: datos.numero || datos.numero_pedido,
                cliente: datos.cliente || 'Cliente sin especificar',
                asesora: datos.asesor || datos.asesora?.name || 'Asesor sin especificar',
                estado: datos.estado || 'Pendiente',
                forma_de_pago: datos.forma_pago || datos.forma_de_pago || 'No especificada',
                prendas: datos.prendas || [],
                epps: datos.epps_transformados || datos.epps || [],
                procesos: datos.procesos || [],
                ...datos
            };

            return true;
        } catch (error) {
            console.warn('[EPP] No se pudo refrescar datos de edición:', error);
            return false;
        }
    }
    
    /**
     * Abrir formulario para editar EPP del pedido (lista seleccionable)
     */
    function abrirEditarEPP() {
        Validator.requireEdicionPedido(() => {
            const datos = window.datosEdicionPedido;
            // datosEdicionPedido tiene estructura: { epps: [...], prendas: [...], numero_pedido, ...}
            const epp = datos.epps || [];


            if (epp.length === 0) {
                // Mostrar modal con opción de agregar EPP
                const htmlSinEPP = `
                    <div style="background: white; border-radius: 6px; width: 100%; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.3); overflow: hidden;">
                        <div style="padding: 20px; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                            <h3 style="margin: 0; color: white; font-size: 20px; font-weight: 700; flex: 1;">
                                EPP del Pedido
                            </h3>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <button onclick="agregarNuevoEPPAPedido()"
                                    style="background: #10b981; border: none; cursor: pointer; color: white; padding: 10px 16px; line-height: 1; transition: all 0.2s; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 14px; white-space: nowrap; flex-shrink: 0;"
                                    onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'"
                                    onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                                    ＋ Agregar EPP
                                </button>
                                <button onclick="abrirModalEditarPedido(window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido, window.datosEdicionPedido, 'editar');"
                                    style="background: #ef4444; border: none; cursor: pointer; color: white; padding: 10px 16px; line-height: 1; transition: all 0.2s; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 14px; white-space: nowrap; flex-shrink: 0;"
                                    onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'"
                                    onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                                    ← Volver
                                </button>
                            </div>
                        </div>
                        <div style="padding: 48px 24px; background: #fafafa; text-align: center;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">🛡️</div>
                            <h4 style="margin: 0 0 0.5rem 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;">Sin EPP</h4>
                            <p style="margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;">No hay EPP agregado en este pedido</p>
                            <button onclick="agregarNuevoEPPAPedido()"
                                style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; transition: all 0.2s;"
                                onmouseover="this.style.background='#059669'; this.style.transform='scale(1.05)'"
                                onmouseout="this.style.background='#10b981'; this.style.transform='scale(1)'">
                                ＋ Agregar EPP al Pedido
                            </button>
                        </div>
                    </div>
                `;

                Swal.fire({
                    html: htmlSinEPP,
                    width: '600px',
                    showConfirmButton: false,
                    allowOutsideClick: true,
                    allowEscapeKey: true,
                    customClass: {
                        container: 'epp-modal-container',
                        popup: 'epp-modal-popup',
                        htmlContainer: 'epp-modal-html'
                    },
                    didOpen: (modal) => {
                        const container = modal.closest('.swal2-container');
                        if (container) {
                            container.style.display = 'flex';
                            container.style.alignItems = 'center';
                            container.style.justifyContent = 'center';
                            container.style.height = '100vh';
                            container.style.zIndex = '999998';
                        }
                        modal.style.marginTop = '0';
                        modal.style.marginBottom = '0';
                        document.body.style.overflow = 'hidden';
                    },
                    willClose: () => {
                        document.body.style.overflow = '';
                    }
                });
                return;
            }
            
            window.eppEdicion = {
                pedidoId: datos.numero_pedido || datos.id,
                epp: epp
            };
            
            let htmlListaEPP = `<div style="display: grid; grid-template-columns: 1fr; gap: 0.75rem;">`;
            
            epp.forEach((item, idx) => {
                // Usar campos estandarizados del backend: nombre_completo, nombre, epp_nombre
                const nombre = item.nombre_completo || item.epp_nombre || item.nombre || '';
                htmlListaEPP += `
                    <div style="background: white; border: 2px solid #1e40af; border-radius: 8px; padding: 1rem; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s ease;"
                        onmouseover="this.style.background='#eff6ff';"
                        onmouseout="this.style.background='white';">
                        <div style="flex: 1;">
                            <h4 style="margin: 0; color: #1f2937; font-size: 0.95rem; font-weight: 700;"> ${nombre.toUpperCase()}</h4>
                            <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.85rem;">Cantidad: <strong>${item.cantidad || 0}</strong></p>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                            <button onclick="abrirModalHomologarEpp(${JSON.stringify(item).replace(/"/g, '&quot;')}, ${idx}, window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido)"
                                style="background: #8b5cf6; color: white; padding: 0.5rem 1rem; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;"
                                onmouseover="this.style.background='#7c3aed'; this.style.transform='scale(1.05)';"
                                onmouseout="this.style.background='#8b5cf6'; this.style.transform='scale(1)';">
                                 Homologar
                            </button>
                        </div>
                    </div>
                `;
            });
            htmlListaEPP += '</div>';
            
            // Crear HTML con header mejorado
            const htmlConHeader = `
                <div style="background: white; border-radius: 6px; width: 100%; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.3); overflow: hidden;">
                    <!-- Header Azul con mejor espaciado -->
                    <div style="padding: 20px; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <h3 style="margin: 0; color: white; font-size: 20px; font-weight: 700; flex: 1;">
                             Selecciona un EPP para Editar
                        </h3>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <button onclick="agregarNuevoEPPAPedido()"
                                style="background: #10b981; border: none; cursor: pointer; color: white; padding: 10px 16px; line-height: 1; transition: all 0.2s; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 14px; white-space: nowrap; flex-shrink: 0;"
                                onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'"
                                onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                                ＋ Agregar EPP
                            </button>
                            <button onclick="abrirModalEditarPedido(window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido, window.datosEdicionPedido, 'editar');"
                                style="background: #ef4444; border: none; cursor: pointer; color: white; padding: 10px 16px; line-height: 1; transition: all 0.2s; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 14px; white-space: nowrap; flex-shrink: 0;"
                                onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'"
                                onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                                ← Volver
                            </button>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div style="padding: 24px; background: #fafafa; overflow-y: auto; max-height: 65vh; min-height: 200px;">
                        ${htmlListaEPP}
                    </div>
                </div>
            `;
            
            Swal.fire({
                html: htmlConHeader,
                width: '800px',
                showConfirmButton: false,
                allowOutsideClick: true,
                allowEscapeKey: true,
                customClass: {
                    container: 'epp-modal-container',
                    popup: 'epp-modal-popup',
                    htmlContainer: 'epp-modal-html'
                },
                didOpen: (modal) => {
                    // Centrar el modal verticalmente en la pantalla
                    const container = modal.closest('.swal2-container');
                    if (container) {
                        container.style.display = 'flex';
                        container.style.alignItems = 'center';
                        container.style.justifyContent = 'center';
                        container.style.height = '100vh';
                        container.style.zIndex = '999998';
                    }
                    modal.style.marginTop = '0';
                    modal.style.marginBottom = '0';
                    // Prevenir scroll del body cuando se abre el modal
                    document.body.style.overflow = 'hidden';
                },
                willClose: () => {
                    // Restaurar scroll del body cuando se cierra el modal
                    document.body.style.overflow = '';
                }
            });
        });
    }
    
    /**
     * Abrir modal de edición para un EPP específico
     */
    function abrirEditarEPPEspecifico(eppIndex) {
        
        Validator.requireEppItem(eppIndex, async (epp) => {
            
            try {
                // Obtener datos frescos de la BD
                const pedidoId = window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido;
                const pedidoEppId = epp.id;
                
           
                const response = await fetch(`/api/pedidos/${pedidoId}/epp/${pedidoEppId}`);
                
                if (!response.ok) {
                    throw new Error(`Error ${response.status}: No se pudieron obtener los datos`);
                }
                
                const resultado = await response.json();
                
                if (!resultado.success || !resultado.data) {
                    throw new Error('No se recibieron datos válidos del servidor');
                }
                
                const eppDelServidor = resultado.data;
                console.log(' [EDITAR-EPP-ESPECIFICO] Datos del servidor recibidos:', {
                    nombre: eppDelServidor.nombre_completo,
                    cantidad: eppDelServidor.cantidad,
                    observaciones: eppDelServidor.observaciones
                });
                
                // Usar el modal simple de edición
                console.log(' [EDITAR-EPP-ESPECIFICO] Abriendo modal simple de edición');
                Swal.close();
                abrirModalEditarEppForm(eppDelServidor);
                console.log(' [EDITAR-EPP-ESPECIFICO] Modal abierto exitosamente');
                
            } catch (error) {
                console.error(' [EDITAR-EPP-ESPECIFICO] Error:', error.message);
                Swal.fire('Error', `No se pudieron cargar los datos: ${error.message}`, 'error');
            }
        });
    }
</script>

<!-- Modal para editar EPP (formulario simple) -->
<div id="modal-editar-epp-form" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <h3 style="margin: 0 0 1.5rem 0; color: #1f2937; font-size: 1.3rem;">Editar EPP</h3>
        
        <div style="margin-bottom: 1.5rem; position: relative;">
            <label for="buscadorEppEdicion" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">Buscar EPP:</label>
            <input type="text" id="buscadorEppEdicion" placeholder="Buscar EPP..." style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
            <div id="resultadosBuscadorEppEdicion" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 6px 6px; max-height: 200px; overflow-y: auto; display: none; z-index: 10;"></div>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <label for="modalEppNombre" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">EPP Seleccionado:</label>
            <input type="text" id="modalEppNombre" disabled="" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; background: #f3f4f6; color: #6b7280;">
        </div>
        
        <!-- Sección de imágenes -->
        <div style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <label style="color: #374151; font-weight: 600;">Imágenes:</label>
                <button type="button" onclick="abrirSelectorImagenesEPP()"
                        style="background: #3b82f6; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600; transition: background 0.2s;"
                        onmouseover="this.style.background='#2563eb'"
                        onmouseout="this.style.background='#3b82f6'">
                    📷 Agregar Imágenes
                </button>
            </div>
            
            <!-- Galería de imágenes existentes -->
            <div id="galeriaImagenesEPP" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem; min-height: 100px; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 6px; background: #f9fafb;">
                <div style="grid-column: 1 / -1; text-align: center; color: #6b7280; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;">
                    No hay imágenes agregadas
                </div>
            </div>
            
            <!-- Input oculto para selección de archivos -->
            <input type="file" id="inputImagenesEPP" multiple accept="image/*" style="display: none;">
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <label for="cantidadEPPEdicion" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">Cantidad:</label>
            <input type="number" id="cantidadEPPEdicion" min="1" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <label for="observacionesEPPEdicion" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">Observaciones:</label>
            <textarea id="observacionesEPPEdicion" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; min-height: 100px; resize: vertical;"></textarea>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button onclick="cerrarModalEditarEppForm()" style="background: #e5e7eb; color: #374151; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s;">
                Cancelar
            </button>
            <button onclick="guardarCambiosEPP()" style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                Guardar Cambios
            </button>
        </div>
    </div>
</div>

<!-- Modal para seleccionar imágenes -->
<div id="modal-selector-imagenes-epp" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 999999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <h3 style="margin: 0 0 1.5rem 0; color: #1f2937; font-size: 1.3rem;">Seleccionar Imágenes</h3>
        
        <div style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 2rem; text-align: center; margin-bottom: 1.5rem;">
            <div style="color: #6b7280; font-size: 1rem; margin-bottom: 1rem;">📷</div>
            <p style="color: #6b7280; margin: 0;">Haz clic aquí o arrastra imágenes</p>
            <input type="file" id="inputImagenesSelector" multiple accept="image/*" style="display: none;">
        </div>
        
        <div id="vistaPreviaImagenes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; max-height: 200px; overflow-y: auto;"></div>
        
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button onclick="cerrarModalSelectorImagenes()" style="background: #e5e7eb; color: #374151; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s;">
                Cancelar
            </button>
            <button onclick="confirmarSeleccionImagenes()" style="background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                Agregar Seleccionadas
            </button>
        </div>
    </div>
</div>

<!-- Modal para registrar la novedad del cambio -->
<div id="modal-novedad-epp" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <h3 style="margin: 0 0 1rem 0; color: #1f2937; font-size: 1.3rem;">Registrar Novedad del Cambio</h3>
        <p style="margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;">Explica por qué estás realizando este cambio en el EPP</p>
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">Motivo del cambio:</label>
            <textarea id="noveladaEPP" placeholder="Ej: Cliente solicitó cambiar cantidad por ajuste de presupuesto..." style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; min-height: 120px; resize: vertical; font-family: inherit;"></textarea>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button onclick="cerrarModalNovedad()" style="background: #e5e7eb; color: #374151; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s;">
                Cancelar
            </button>
            <button onclick="confirmarNovedad()" style="background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                Guardar Novedad y Aplicar Cambios
            </button>
        </div>
    </div>
</div>

<script>
    function restaurarEstadoModalNovedad() {
        const botonGuardar = document.querySelector('#modal-novedad-epp button[onclick="confirmarNovedad()"]');
        const botonCancelar = document.querySelector('#modal-novedad-epp button[onclick="cerrarModalNovedad()"]');

        if (botonGuardar) {
            botonGuardar.disabled = false;
            botonGuardar.style.opacity = '1';
            botonGuardar.style.cursor = 'pointer';
            botonGuardar.textContent = 'Guardar Novedad y Aplicar Cambios';
        }

        if (botonCancelar) {
            botonCancelar.disabled = false;
            botonCancelar.style.opacity = '1';
            botonCancelar.style.cursor = 'pointer';
        }
    }

    // Funciones para abrir/cerrar el modal de edición de EPP
    function abrirModalEditarEppForm(eppData) {
        console.log('[Modal Editar EPP] Abriendo formulario con datos:', eppData);
        
        const modal = document.getElementById('modal-editar-epp-form');
        if (!modal) {
            console.error(' Modal no encontrado en el DOM');
            return;
        }
        
        // Llenar los campos
        document.getElementById('modalEppNombre').value = eppData.nombre_completo || eppData.nombre || '';
        document.getElementById('buscadorEppEdicion').value = '';
        document.getElementById('cantidadEPPEdicion').value = eppData.cantidad || 1;
        document.getElementById('observacionesEPPEdicion').value = eppData.observaciones || '';
        document.getElementById('resultadosBuscadorEppEdicion').style.display = 'none';
        
        // Guardar datos en variable global
        window.eppEnEdicionActual = eppData;
        
        // Mostrar modal
        modal.style.display = 'flex';
        
        // Inicializar el buscador
        setTimeout(() => {
            inicializarBuscadorEpp();
        }, 100);
        
    }
    
    function cerrarModalEditarEppForm() {
        const modal = document.getElementById('modal-editar-epp-form');
        if (modal) {
            modal.style.display = 'none';
        }
        window.eppEnEdicionActual = null;
        window.eppEnHomologacion = null; // Limpiar flag de homologación
        document.getElementById('buscadorEppEdicion').value = '';
    }
    
    // Inicializar buscador de EPPs
    function inicializarBuscadorEpp() {
        const buscador = document.getElementById('buscadorEppEdicion');
        if (!buscador) return;
        
        buscador.addEventListener('input', function(e) {
            const valor = e.target.value.trim();
            console.log(' Buscando EPP:', valor);
            
            if (valor.length < 2) {
                document.getElementById('resultadosBuscadorEppEdicion').style.display = 'none';
                return;
            }
            
            buscarEppsDisponibles(valor);
        });
    }
    
    async function buscarEppsDisponibles(termino) {
        try {
            const resultadosDiv = document.getElementById('resultadosBuscadorEppEdicion');
            resultadosDiv.innerHTML = '<div style="padding: 10px; color: #6b7280;">Buscando...</div>';
            resultadosDiv.style.display = 'block';
            
            const response = await fetch(`/api/epps/buscar?q=${encodeURIComponent(termino)}`);
            const resultado = await response.json();
            const resultados = resultado.data || [];
            
            if (resultados.length === 0) {
                resultadosDiv.innerHTML = '<div style="padding: 10px; color: #ef4444;">No se encontraron EPPs</div>';
                return;
            }
            
            mostrarResultadosBuscador(resultados);
        } catch (error) {
            console.error(' Error buscando EPPs:', error);
            document.getElementById('resultadosBuscadorEppEdicion').innerHTML = '<div style="padding: 10px; color: #ef4444;">Error al buscar</div>';
        }
    }
    
    function mostrarResultadosBuscador(resultados) {
        const resultadosDiv = document.getElementById('resultadosBuscadorEppEdicion');
        
        if (resultados.length === 0) {
            resultadosDiv.innerHTML = '<div style="padding: 10px; color: #ef4444;">No se encontraron EPPs</div>';
            return;
        }
        
        let html = '';
        resultados.forEach(epp => {
            const nombre = epp.nombre_completo;
            html += `
                <div onclick="seleccionarEppBuscador(${epp.id}, '${nombre.replace(/'/g, "\\'")}', ${epp.id})" 
                     style="padding: 12px; border-bottom: 1px solid #e5e7eb; cursor: pointer; transition: background 0.2s;"
                     onmouseover="this.style.background='#f3f4f6'"
                     onmouseout="this.style.background='white'">
                    <div style="font-weight: 500; color: #1f2937;">${nombre}</div>
                    <div style="font-size: 0.85rem; color: #6b7280;">ID: ${epp.id}</div>
                </div>
            `;
        });
        
        resultadosDiv.innerHTML = html;
    }
    
    function seleccionarEppBuscador(eppId, eppNombre, eppEppId) {
        console.log(' EPP seleccionado:', { eppId, eppNombre, eppEppId });
        
        // Guardar el ID del EPP seleccionado en los datos actuales
        if (window.eppEnEdicionActual) {
            window.eppEnEdicionActual.epp_id = eppEppId;
        }
        
        document.getElementById('modalEppNombre').value = eppNombre;
        document.getElementById('buscadorEppEdicion').value = eppNombre;
        document.getElementById('resultadosBuscadorEppEdicion').style.display = 'none';
    }
    
    async function guardarCambiosEPP() {
        const eppData = window.eppEnEdicionActual;
        if (!eppData) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No hay datos del EPP',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: { container: 'swal-toast-container' }
            });
            return;
        }
        
        const cantidad = parseInt(document.getElementById('cantidadEPPEdicion').value);
        const observaciones = document.getElementById('observacionesEPPEdicion').value;
        const eppId = eppData.epp_id;
        const pedidoEppId = obtenerPedidoEppIdDesdeData(eppData);
        
        if (cantidad < 1) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La cantidad debe ser mayor a 0',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: { container: 'swal-toast-container' }
            });
            return;
        }
        
        // Guardar los datos para usarlos después en confirmarNovedad
        window.cambiosEppPendientes = {
            eppData,
            pedidoEppId,
            cantidad,
            observaciones,
            eppId
        };
        
        // Mostrar modal para registrar la novedad
        restaurarEstadoModalNovedad();
        document.getElementById('modal-novedad-epp').style.display = 'flex';
        document.getElementById('noveladaEPP').value = '';
        document.getElementById('noveladaEPP').focus();
    }
    
    function cerrarModalNovedad() {
        const modalNovedad = document.getElementById('modal-novedad-epp');
        if (modalNovedad?.contains(document.activeElement)) {
            document.activeElement.blur();
        }

        restaurarEstadoModalNovedad();
        document.getElementById('modal-novedad-epp').style.display = 'none';
        window.cambiosEppPendientes = null;
    }
    
    async function confirmarNovedad() {
        const novedad = document.getElementById('noveladaEPP').value.trim();
        
        if (!novedad) {
            Swal.fire({
                icon: 'warning',
                title: 'Novedad requerida',
                text: 'Por favor ingresa el motivo del cambio',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: { container: 'swal-toast-container' }
            });
            return;
        }
        
        const cambios = window.cambiosEppPendientes;
        if (!cambios) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No hay cambios pendientes para guardar',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: { container: 'swal-toast-container' }
            });
            return;
        }

        // Obtener el botón y desactivarlo
        const botonGuardar = document.querySelector('#modal-novedad-epp button[onclick="confirmarNovedad()"]');
        const botonCancelar = document.querySelector('#modal-novedad-epp button[onclick="cerrarModalNovedad()"]');
        
        if (botonGuardar) {
            botonGuardar.disabled = true;
            botonGuardar.style.opacity = '0.6';
            botonGuardar.style.cursor = 'not-allowed';
            botonGuardar.textContent = ' Guardando...';
        }
        
        if (botonCancelar) {
            botonCancelar.disabled = true;
            botonCancelar.style.opacity = '0.6';
            botonCancelar.style.cursor = 'not-allowed';
        }

        try {
            // Procesar eliminaciones de imágenes primero
            if (cambios.imagenesAEliminar && cambios.imagenesAEliminar.length > 0) {
                console.log(' [Modal Novedad] Procesando eliminación de imágenes:', cambios.imagenesAEliminar);
                
                for (const imagen of cambios.imagenesAEliminar) {
                    try {
                        console.log(` Eliminando imagen: ${imagen.id} - ${imagen.nombre || imagen.ruta_original}`);
                        
                        const response = await fetch(`/api/pedido-epp/imagenes/${imagen.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        
                        if (!response.ok) {
                            const error = await response.json();
                            console.warn(` No se pudo eliminar imagen ${imagen.id}:`, error.message);
                        } else {
                            console.log(` Imagen ${imagen.id} eliminada correctamente`);
                        }
                    } catch (error) {
                        console.error(` Error eliminando imagen ${imagen.id}:`, error);
                    }
                }
            }
            
            // Procesar agregaciones de nuevas imágenes
            if (cambios.imagenesAAgregar && cambios.imagenesAAgregar.length > 0) {
                console.log(' [Modal Novedad] Procesando agregación de imágenes:', cambios.imagenesAAgregar);
                
                const formData = new FormData();
                let archivosAgregados = 0;
                
                cambios.imagenesAAgregar.forEach((imagen, index) => {
                    if (imagen.file) {
                        console.log(` Agregando archivo ${index}:`, {
                            nombre: imagen.nombre,
                            size: imagen.size,
                            type: imagen.type,
                            file: imagen.file
                        });
                        
                        // Laravel espera 'imagenes[]' para recibir un array de archivos
                        formData.append('imagenes[]', imagen.file);
                        
                        // Agregar metadatos como JSON string
                        formData.append(`metadatos[${index}]`, JSON.stringify({
                            nombre: imagen.nombre,
                            size: imagen.size,
                            type: imagen.type
                        }));
                        archivosAgregados++;
                    } else {
                        console.warn(` Imagen ${index} no tiene archivo:`, imagen);
                    }
                });
                
                console.log(' FormData creado:', {
                    archivosCount: archivosAgregados,
                    formDataEntries: Array.from(formData.entries())
                });
                
                try {
                    const response = await fetch(`/api/pedido-epp/${cambios.pedidoEppId}/imagenes`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            // No Content-Type para FormData, el navegador lo establece automáticamente
                        },
                        body: formData
                    });
                    
                    if (!response.ok) {
                        const error = await response.json();
                        console.warn(` No se pudieron agregar imágenes:`, error.message);
                    } else {
                        const resultado = await response.json();
                        console.log(` Imágenes agregadas:`, resultado);
                        
                        // Actualizar la lista de imágenes existentes con las nuevas
                        if (resultado.data && Array.isArray(resultado.data)) {
                            resultado.data.forEach(nuevaImagen => {
                                imagenesEPPActuales.push({
                                    id: nuevaImagen.id,
                                    ruta_original: nuevaImagen.ruta_original,
                                    ruta_web: nuevaImagen.ruta_web,
                                    nombre: nuevaImagen.nombre,
                                    principal: nuevaImagen.principal,
                                    orden: nuevaImagen.orden
                                });
                            });
                        }
                    }
                } catch (error) {
                    console.error(' Error agregando imágenes:', error);
                }
            }
            
            const pedidoId = window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido;
            const pedidoEppId = cambios.pedidoEppId;
            
            console.log(' [Modal Novedad] Guardando cambios con novedad...', {
                pedidoId,
                pedidoEppId,
                cantidad: cambios.cantidad,
                observaciones: cambios.observaciones,
                eppId: cambios.eppId,
                novedad,
                imagenesEliminadas: cambios.imagenesAEliminar?.length || 0,
                imagenesAgregadas: cambios.imagenesAAgregar?.length || 0,
                esHomologacion: !!window.eppEnHomologacion
            });
            
            // Detectar si es una homologación
            const esHomologacion = !!window.eppEnHomologacion;
            let response;
            if (esHomologacion) {
                console.log(' [Modal Novedad] Modo HOMOLOGACIÓN detectado');
                
                response = await fetch(`/api/asesores/pedidos/${pedidoId}/homologar-epp`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        pedido_epp_id: window.eppEnHomologacion.id,
                        cantidad: cambios.cantidad,
                        observaciones: cambios.observaciones,
                        epp_id: cambios.eppId,
                        motivo: novedad
                    })
                });
            } else {
                // Modo edición normal
                response = await fetch(`/api/pedidos/${pedidoId}/epp/${pedidoEppId}`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        cantidad: cambios.cantidad,
                        observaciones: cambios.observaciones,
                        epp_id: cambios.eppId,
                        novedad: novedad
                    })
                });
            }
            
            const contentType = response.headers.get('content-type') || '';
            const esJson = contentType.includes('application/json');
            const payload = esJson ? await response.json() : await response.text();

            if (!response.ok) {
                if (esJson) {
                    throw new Error(payload?.message || 'Error al guardar');
                }

                const mensajeHtml = (typeof payload === 'string' && payload.trim().startsWith('<!DOCTYPE'))
                    ? 'El servidor devolvio HTML en lugar de JSON (posible sesion expirada o error interno).'
                    : 'Respuesta inesperada del servidor.';
                throw new Error(`${mensajeHtml} [HTTP ${response.status}]`);
            }

            if (!esJson) {
                throw new Error(`Respuesta no JSON del servidor [HTTP ${response.status}]`);
            }

            const resultado = payload;
            console.log(' Cambios guardados con novedad:', resultado);

            const refrescoOk = await refrescarDatosEdicionPedidoDesdeServidor(pedidoId);

            // Fallback local si por alguna razón falla el refresco remoto
            if (!refrescoOk && window.datosEdicionPedido && Array.isArray(window.datosEdicionPedido.epps)) {
                const eppsLista = window.datosEdicionPedido.epps;
                const indexAnterior = eppsLista.findIndex(
                    e => Number(e?.id || e?.pedido_epp_id) === Number(pedidoEppId)
                );

                if (esHomologacion) {
                    const nuevoPedidoEppId = Number(resultado?.epp_id_nuevo || 0);
                    if (indexAnterior !== -1) {
                        const anterior = eppsLista[indexAnterior] || {};
                        eppsLista.splice(indexAnterior, 1, {
                            ...anterior,
                            id: nuevoPedidoEppId || anterior.id,
                            pedido_epp_id: nuevoPedidoEppId || anterior.pedido_epp_id,
                            epp_id: cambios.eppId || anterior.epp_id,
                            cantidad: cambios.cantidad,
                            observaciones: cambios.observaciones,
                            nombre: cambios.nombreSeleccionado || anterior.nombre || anterior.nombre_completo,
                            nombre_completo: cambios.nombreSeleccionado || anterior.nombre_completo || anterior.nombre
                        });
                    }
                } else if (indexAnterior !== -1) {
                    eppsLista[indexAnterior].cantidad = cambios.cantidad;
                    eppsLista[indexAnterior].observaciones = cambios.observaciones;
                    if (cambios.eppId) {
                        eppsLista[indexAnterior].epp_id = cambios.eppId;
                    }
                    if (cambios.nombreSeleccionado) {
                        eppsLista[indexAnterior].nombre = cambios.nombreSeleccionado;
                        eppsLista[indexAnterior].nombre_completo = cambios.nombreSeleccionado;
                    }
                }
            }
            
            cerrarModalNovedad();
            cerrarModalEditarEppForm();
            
            //  Recargar la tabla de pedidos sin recargar la página
            await recargarTablaPedidos();
            
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'EPP actualizado y novedad registrada correctamente',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: { container: 'swal-toast-container' }
            }).then(() => {
                // Reabrir el modal con la lista actualizada
                abrirEditarEPP();
            });
            
        } catch (error) {
            console.error(' Error guardando cambios:', error);
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: `No se pudieron guardar los cambios: ${error.message}`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                customClass: { container: 'swal-toast-container' }
            });
        } finally {
            restaurarEstadoModalNovedad();
        }
    }
    
    // Funciones para manejo de imágenes de EPP
    let imagenesEPPActuales = [];
    let imagenesAEliminar = [];
    let imagenesAAgregar = [];

    function obtenerPedidoEppIdDesdeData(eppData) {
        if (!eppData || typeof eppData !== 'object') return null;
        return eppData.pedido_epp_id || eppData.id || null;
    }

    function normalizarUrlImagenEpp(imagen) {
        const rawUrl = imagen?.ruta_web || imagen?.url || imagen?.ruta_original || '';
        if (!rawUrl) return '';

        const url = String(rawUrl).replace(/\\/g, '/').trim();

        if (/^(https?:)?\/\//i.test(url) || /^(blob:|data:)/i.test(url)) return url;
        if (url.startsWith('/storage/')) return url;
        if (url.startsWith('storage/')) return `/${url}`;
        if (url.startsWith('/')) return url;

        return `/storage/${url.replace(/^\/+/, '')}`;
    }

    function abrirVistaImagenEpp(imgUrl, nombre) {
        if (!imgUrl) return;

        if (window.invoiceRenderer && typeof window.invoiceRenderer.abrirModalImagen === 'function') {
            window.invoiceRenderer.abrirModalImagen(imgUrl, nombre || 'Imagen EPP');
            return;
        }

        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
                imageUrl: imgUrl,
                imageAlt: nombre || 'Imagen EPP',
                showConfirmButton: false,
                showCloseButton: true,
                width: 'auto'
            });
            return;
        }

        window.open(imgUrl, '_blank');
    }
    
    function abrirSelectorImagenesEPP() {
        document.getElementById('modal-selector-imagenes-epp').style.display = 'flex';
        document.getElementById('vistaPreviaImagenes').innerHTML = '';
        
        // Configurar drag and drop
        const dropZone = document.querySelector('#modal-selector-imagenes-epp > div > div[style*="border: 2px dashed"]');
        const fileInput = document.getElementById('inputImagenesSelector');
        
        dropZone.onclick = () => fileInput.click();
        
        dropZone.ondragover = (e) => {
            e.preventDefault();
            dropZone.style.background = '#f0f9ff';
            dropZone.style.borderColor = '#3b82f6';
        };
        
        dropZone.ondragleave = () => {
            dropZone.style.background = '';
            dropZone.style.borderColor = '#d1d5db';
        };
        
        dropZone.ondrop = (e) => {
            e.preventDefault();
            dropZone.style.background = '';
            dropZone.style.borderColor = '#d1d5db';
            manejarSeleccionArchivos(e.dataTransfer.files);
        };
        
        fileInput.onchange = (e) => {
            manejarSeleccionArchivos(e.target.files);
        };
    }
    
    function cerrarModalSelectorImagenes() {
        document.getElementById('modal-selector-imagenes-epp').style.display = 'none';
        document.getElementById('vistaPreviaImagenes').innerHTML = '';
        document.getElementById('inputImagenesSelector').value = '';
        
        // Limpiar array temporal para liberar memoria
        if (window.imagenesTemporales) {
            window.imagenesTemporales = [];
        }
    }
    
    function manejarSeleccionArchivos(archivos) {
        const vistaPrevia = document.getElementById('vistaPreviaImagenes');
        vistaPrevia.innerHTML = '';
        
        Array.from(archivos).forEach((archivo, index) => {
            if (archivo.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.style.cssText = 'position: relative; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;';
                    div.innerHTML = `
                        <img src="${e.target.result}" style="width: 100%; height: 100px; object-fit: cover;">
                        <div style="position: absolute; top: 5px; right: 5px; background: rgba(239, 68, 68, 0.9); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px;" onclick="this.parentElement.remove()">×</div>
                        <div style="padding: 8px; background: white; font-size: 12px; color: #6b7280; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${archivo.name}</div>
                    `;
                    
                    // Guardar referencia directa al archivo, no en JSON
                    div.dataset.archivoIndex = index;
                    
                    // Guardar el archivo en un array separado para evitar JSON.stringify
                    if (!window.imagenesTemporales) {
                        window.imagenesTemporales = [];
                    }
                    window.imagenesTemporales[index] = {
                        nombre: archivo.name,
                        size: archivo.size,
                        type: archivo.type,
                        preview: e.target.result,
                        file: archivo // ← Archivo real guardado directamente
                    };
                    
                    vistaPrevia.appendChild(div);
                };
                reader.readAsDataURL(archivo);
            }
        });
    }
    
    function confirmarSeleccionImagenes() {
        const elementos = document.querySelectorAll('#vistaPreviaImagenes > div');
        
        // Limpiar array de imágenes a agregar
        imagenesAAgregar = [];
        
        elementos.forEach(el => {
            const index = el.dataset.archivoIndex;
            if (index !== undefined && window.imagenesTemporales && window.imagenesTemporales[index]) {
                // Guardar el archivo real para poder enviarlo al backend
                imagenesAAgregar.push(window.imagenesTemporales[index]);
            }
        });
        
        console.log(' [confirmarSeleccionImagenes] Imágenes para agregar:', imagenesAAgregar);
        actualizarGaleriaImagenes();
        cerrarModalSelectorImagenes();
    }
    
    function actualizarGaleriaImagenes() {
        const galeria = document.getElementById('galeriaImagenesEPP');
        
        if (imagenesEPPActuales.length === 0 && imagenesAAgregar.length === 0) {
            galeria.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; color: #6b7280; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;">
                    No hay imágenes agregadas
                </div>
            `;
            return;
        }
        
        let html = '';
        
        // Imágenes existentes
        imagenesEPPActuales.forEach((imagen, index) => {
            // Construir URL asegurando que siempre incluya /storage/
            let imgUrl = normalizarUrlImagenEpp(imagen);
            
            // Si no empieza con /storage/, agregarlo
            const nombreImagen = imagen.nombre || `Imagen ${index + 1}`;
            const imgUrlEscapada = imgUrl.replace(/'/g, "\\'");
            const nombreEscapado = nombreImagen.replace(/'/g, "\\'");
            
            console.log(`[actualizarGaleriaImagenes] Imagen ${index}:`, {
                id: imagen.id,
                ruta_web: imagen.ruta_web,
                url: imagen.url,
                ruta_original: imagen.ruta_original,
                imgUrl_final: imgUrl
            });
            html += `
                <div style="position: relative; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; aspect-ratio: 1;">
                    <img src="${imgUrl}" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" onclick="abrirVistaImagenEpp('${imgUrlEscapada}', '${nombreEscapado}')">
                    <div style="position: absolute; top: 5px; right: 5px; background: rgba(239, 68, 68, 0.9); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px;" onclick="eliminarImagenEPP('${imagen.id}', ${index})">×</div>
                </div>
            `;
        });
        
        // Nuevas imágenes
        imagenesAAgregar.forEach((imagen, index) => {
            html += `
                <div style="position: relative; border-radius: 8px; overflow: hidden; border: 1px solid #10b981; aspect-ratio: 1;">
                    <img src="${imagen.preview}" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; top: 5px; right: 5px; background: rgba(239, 68, 68, 0.9); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px;" onclick="eliminarImagenNuevaEPP(${index})">×</div>
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(16, 185, 129, 0.9); color: white; padding: 4px; font-size: 10px; text-align: center;">NUEVA</div>
                </div>
            `;
        });
        
        galeria.innerHTML = html;
    }
    
    function eliminarImagenEPP(imagenId, index) {
        const imagen = imagenesEPPActuales[index];
        imagenesAEliminar.push(imagen);
        imagenesEPPActuales.splice(index, 1);
        actualizarGaleriaImagenes();
    }
    
    function eliminarImagenNuevaEPP(index) {
        imagenesAAgregar.splice(index, 1);
        actualizarGaleriaImagenes();
    }
    
    async function cargarImagenesEPP(pedidoEppId) {
        try {
            const response = await fetch(`/api/pedido-epp/${pedidoEppId}/imagenes`);
            const resultado = await response.json();
            imagenesEPPActuales = resultado.data || [];
            actualizarGaleriaImagenes();
        } catch (error) {
            console.error('Error cargando imágenes del EPP:', error);
            imagenesEPPActuales = [];
            actualizarGaleriaImagenes();
        }
    }
    
    // Modificar la función abrirModalEditarEppForm para cargar imágenes
    function abrirModalEditarEppFormConImagenes(eppData) {
        console.log('[Modal Editar EPP] Abriendo formulario con datos:', eppData);
        
        const modal = document.getElementById('modal-editar-epp-form');
        if (!modal) {
            console.error(' Modal no encontrado en el DOM');
            return;
        }
        
        // Resetear variables de imágenes
        imagenesEPPActuales = [];
        imagenesAEliminar = [];
        imagenesAAgregar = [];
        
        // Llenar los campos
        document.getElementById('modalEppNombre').value = eppData.nombre_completo || eppData.nombre || '';
        document.getElementById('buscadorEppEdicion').value = '';
        document.getElementById('cantidadEPPEdicion').value = eppData.cantidad || 1;
        document.getElementById('observacionesEPPEdicion').value = eppData.observaciones || '';
        document.getElementById('resultadosBuscadorEppEdicion').style.display = 'none';
        
        // Guardar datos en variable global
        window.eppEnEdicionActual = eppData;
        const pedidoEppId = obtenerPedidoEppIdDesdeData(eppData);
        window.eppEnEdicionActual.pedido_epp_id = pedidoEppId;
        
        // Cargar imágenes existentes
        if (pedidoEppId) {
            cargarImagenesEPP(pedidoEppId);
        } else {
            actualizarGaleriaImagenes();
        }
        
        // Mostrar modal
        modal.style.display = 'flex';
        
        // Inicializar el buscador
        setTimeout(() => {
            inicializarBuscadorEpp();
        }, 100);
    }
    
    // Modificar guardarCambiosEPP para incluir imágenes
    async function guardarCambiosEPPConImagenes() {
        const eppData = window.eppEnEdicionActual;
        if (!eppData) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No hay datos del EPP',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: { container: 'swal-toast-container' }
            });
            return;
        }
        
        const cantidad = parseInt(document.getElementById('cantidadEPPEdicion').value);
        const observaciones = document.getElementById('observacionesEPPEdicion').value;
        const eppId = eppData.epp_id;
        const pedidoEppId = obtenerPedidoEppIdDesdeData(eppData);
        
        if (cantidad < 1) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La cantidad debe ser mayor a 0',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: { container: 'swal-toast-container' }
            });
            return;
        }
        
        // Guardar los datos para usarlos después en confirmarNovedad
        window.cambiosEppPendientes = {
            eppData,
            pedidoEppId,
            cantidad,
            observaciones,
            eppId,
            nombreSeleccionado: (document.getElementById('modalEppNombre')?.value || '').trim(),
            imagenesAEliminar,
            imagenesAAgregar
        };
        
        // Mostrar modal para registrar la novedad
        restaurarEstadoModalNovedad();
        document.getElementById('modal-novedad-epp').style.display = 'flex';
        document.getElementById('noveladaEPP').value = '';
        document.getElementById('noveladaEPP').focus();
    }
    
    /**
     * Eliminar EPP del pedido
     */
    window.abrirModalEliminarEpp = function(epp, eppIndex, pedidoId) {
        const eppId = epp.id || epp.pedido_epp_id;
        const nombreEpp = epp.nombre || epp.epp?.nombre || epp.nombre_completo || epp.epp_nombre || 'EPP Sin nombre';
        const cantidad = epp.cantidad || 1;
        
        console.log('[EPP]  Eliminando EPP:', nombreEpp, 'id:', eppId, 'pedidoId:', pedidoId);

        if (!pedidoId || !eppId) {
            console.error('[EPP] Faltan pedidoId o eppId para eliminar');
            Swal.fire('Error', 'No se pudo identificar el pedido o el EPP para eliminar', 'error');
            return;
        }

        // Pedir motivo de eliminación
        Swal.fire({
            title: '¿Eliminar EPP?',
            html: `<p>¿Estás seguro de que deseas eliminar <strong>${nombreEpp.toUpperCase()}</strong>?</p>
                   <p style="color: #6b7280; font-size: 0.85em; margin-top: 0.5rem;">Cantidad: <strong>${cantidad}</strong></p>
                   <p style="color: #ef4444; font-size: 0.9em; margin-top: 1rem;">Se registrará en las novedades del pedido.</p>`,
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Motivo de la eliminación',
            inputPlaceholder: 'Ej: EPP no requerido, cambio en especificaciones, etc.',
            inputAttributes: { 'aria-label': 'Motivo de eliminación' },
            showCancelButton: true,
            confirmButtonText: ' Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            didOpen: (modal) => {
                const container = modal.closest('.swal2-container');
                if (container) {
                    container.style.display = 'flex';
                    container.style.alignItems = 'center';
                    container.style.justifyContent = 'center';
                    container.style.height = '100vh';
                    container.style.zIndex = '2000000';
                }
            },
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Debes ingresar un motivo de eliminación';
                }
                if (value.trim().length < 5) {
                    return 'El motivo debe tener al menos 5 caracteres';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                _eliminarEppDelAPI(pedidoId, eppId, eppIndex, epp, result.value.trim());
            }
        });
    };

    /**
     * Eliminar EPP del servidor
     * @private
     */
    async function _eliminarEppDelAPI(pedidoId, eppId, eppIndex, epp, motivo) {
        try {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando EPP...',
                html: 'Por favor espera mientras se elimina el EPP',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            console.log('[EPP]  Enviando DELETE a: /api/asesores/pedidos/' + pedidoId + '/eliminar-epp');
            
            const response = await fetch(`/api/asesores/pedidos/${pedidoId}/eliminar-epp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    epp_id: eppId,
                    motivo: motivo
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Error al eliminar EPP');
            }

            const data = await response.json();

            if (data.success) {
                console.log('[EPP]  EPP eliminado correctamente:', data);
                
                Swal.fire({
                    title: '¡EPP Eliminado!',
                    html: `<p><strong>${data.epp_nombre}</strong> ha sido eliminado correctamente.</p>
                           <p style="color: #6b7280; font-size: 0.9em; margin-top: 0.5rem;">Se ha registrado en las novedades del pedido.</p>`,
                    icon: 'success',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#1e40af'
                }).then(() => {
                    // Recargar la página para reflejar cambios
                    location.reload();
                });
            } else {
                throw new Error(data.message || 'No se pudo eliminar el EPP');
            }

        } catch (error) {
            console.error('[EPP] Error al eliminar:', error.message);
            
            Swal.fire({
                title: 'Error',
                text: error.message || 'No se pudo eliminar el EPP. Por favor intenta de nuevo.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#1e40af'
            });
        }
    }

    /**
     * Homologar EPP: Abrir modal de edición para poder editarlo
     */
    window.abrirModalHomologarEpp = function(epp, eppIndex, pedidoId) {
        const eppId = epp.id || epp.pedido_epp_id;
        const nombreEpp = epp.nombre || epp.epp?.nombre || epp.nombre_completo || epp.epp_nombre || 'EPP Sin nombre';
        
        console.log('[EPP]  Homologando EPP:', nombreEpp, 'id:', eppId, 'pedidoId:', pedidoId);

        if (!pedidoId || !eppId) {
            console.error('[EPP] Faltan pedidoId o eppId para homologar');
            Swal.fire('Error', 'No se pudo identificar el pedido o el EPP para homologar', 'error');
            return;
        }

        // Marcar que estamos en modo homologación
        window.eppEnHomologacion = {
            id: eppId,
            original: JSON.parse(JSON.stringify(epp))
        };

        // Abrir el modal de edición normal
        try {
            Swal.close();
            abrirModalEditarEppForm(epp);
            console.log('[EPP]  Modal abierto en modo homologación');
        } catch (error) {
            console.error('[EPP] Error abriendo modal:', error);
            Swal.fire('Error', 'No se pudo abrir el modal de edición', 'error');
        }
    };

    // Exponer funciones globalmente
    window.abrirModalEditarEppForm = abrirModalEditarEppFormConImagenes;
    window.cerrarModalEditarEppForm = cerrarModalEditarEppForm;
    window.guardarCambiosEPP = guardarCambiosEPPConImagenes;
    window.abrirSelectorImagenesEPP = abrirSelectorImagenesEPP;
    window.cerrarModalSelectorImagenes = cerrarModalSelectorImagenes;
    window.confirmarSeleccionImagenes = confirmarSeleccionImagenes;
    window.eliminarImagenEPP = eliminarImagenEPP;
    window.eliminarImagenNuevaEPP = eliminarImagenNuevaEPP;
    window.abrirModalHomologarEpp = abrirModalHomologarEpp;
</script>
