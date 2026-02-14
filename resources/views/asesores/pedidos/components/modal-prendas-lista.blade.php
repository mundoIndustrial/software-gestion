<!-- Modal de lista de prendas con opción de agregar -->
<script>
    /**
     * Abre el modal de prendas del pedido con lista editable
     */
    function abrirEditarPrendas() {
        Validator.requireEdicionPedido(() => {
            const datos = window.datosEdicionPedido;
            const prendas = datos.prendas || [];
            // Siempre mostrar lista, aunque esté vacía
            window.prendasEdicion = {
                pedidoId: datos.id, // Siempre usar el ID real de la BD
                prendas: prendas
            };
            
            let htmlListaPrendas = `<div style="display: grid; grid-template-columns: 1fr; gap: 0.75rem;">`;
            
            if (prendas.length === 0) {
                // Mostrar botón para agregar prenda si la lista está vacía
                htmlListaPrendas += `
                    <div style="text-align: center; padding: 2rem; background: #f9fafb; border-radius: 8px; border: 2px dashed #d1d5db;">
                        <p style="color: #6b7280; margin: 0 0 1rem 0;">No hay prendas agregadas aún</p>
                        <button onclick="abrirAgregarPrenda()" 
                            style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-size: 0.95rem; font-weight: 600; transition: all 0.2s;"
                            onmouseover="this.style.backgroundColor='#059669'"
                            onmouseout="this.style.backgroundColor='#10b981'">
                            ➕ Agregar Prenda
                        </button>
                    </div>
                `;
            } else {
                prendas.forEach((item, idx) => {
                    console.log(' [PRENDA-DEBUG] Estructura de la prenda:', {
                        idx: idx,
                        item_keys: Object.keys(item),
                        id: item.id,
                        prenda_pedido_id: item.prenda_pedido_id,
                        nombre_prenda: item.nombre_prenda,
                        item_completo: item
                    });
                    
                    const nombre = item.nombre_prenda || 'Prenda sin nombre';
                    const cantidad = item.cantidad || 0;
                    htmlListaPrendas += `
                        <button onclick="(function() {
                            console.log(' [ONCLICK-INICIO] Botón prenda clickeado');
                            console.log(' [ONCLICK-DATOS] item:', item);
                            console.log(' [ONCLICK-DATOS] idx:', idx);
                            console.log(' [ONCLICK-DATOS] datosEdicionPedido:', window.datosEdicionPedido);
                            
                            const pedidoId = window.datosEdicionPedido?.id;
                            console.log(' [ONCLICK-PEDIDO-ID] Usando pedidoId:', pedidoId);
                            
                            Swal.close();
                            setTimeout(() => {
                                console.log(' [ONCLICK-POST-SWAL] Después de Swal.close()');
                                console.log(' [ONCLICK-CHECK-FUNC] Verificando si abrirEditarPrendaModal existe:', typeof window.abrirEditarPrendaModal);
                                
                                if (typeof window.abrirEditarPrendaModal === 'function') {
                                    console.log(' [ONCLICK-EJECUTANDO] abrirEditarPrendaModal encontrada, ejecutando...');
                                    window.abrirEditarPrendaModal(item, idx, pedidoId);
                                } else {
                                    console.error(' [ONCLICK-ERROR] abrirEditarPrendaModal NO ES FUNCIÓN');
                                    console.error('Tipo actual:', typeof window.abrirEditarPrendaModal);
                                    console.error('Valor:', window.abrirEditarPrendaModal);
                                    console.error('Funciones disponibles:', Object.keys(window).filter(k => k.includes('abrirEditar')).slice(0, 10));
                                }
                            }, 100);
                        })()" 
                            style="background: white; border: 2px solid #1e40af; border-radius: 8px; padding: 1rem; text-align: left; cursor: pointer; transition: all 0.3s ease;"
                            onmouseover="this.style.background='#eff6ff'; this.style.borderColor='#1e40af';"
                            onmouseout="this.style.background='white'; this.style.borderColor='#1e40af';">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="margin: 0; color: #1f2937; font-size: 0.95rem; font-weight: 700;">${nombre.toUpperCase()}</h4>
                                    <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.85rem;">Cantidad: <strong>${cantidad}</strong></p>
                                </div>
                                <span style="background: #1e40af; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">✏️ Editar</span>
                            </div>
                        </button>
                    `;
                });
                
                // Agregar botón para agregar más prendas
                htmlListaPrendas += `
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #e5e7eb; text-align: center;">
                        <button onclick="abrirAgregarPrenda()" 
                            style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-size: 0.95rem; font-weight: 600; transition: all 0.2s;"
                            onmouseover="this.style.backgroundColor='#059669'"
                            onmouseout="this.style.backgroundColor='#10b981'">
                            ➕ Agregar Más Prendas
                        </button>
                    </div>
                `;
            }
            
            htmlListaPrendas += '</div>';
            
            // Crear HTML con header mejorado
            const htmlConHeader = `
                <div style="background: white; border-radius: 6px; width: 100%; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.3); overflow: hidden;">
                    <!-- Header Azul con mejor espaciado -->
                    <div style="padding: 20px; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <h3 style="margin: 0; color: white; font-size: 20px; font-weight: 700; flex: 1;">
                            Selecciona una Prenda para Editar
                        </h3>
                        <button onclick="abrirModalEditarPedido(window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido, window.datosEdicionPedido, 'editar');" 
                            style="background: #ef4444; border: none; cursor: pointer; color: white; padding: 10px 16px; line-height: 1; transition: all 0.2s; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 14px; white-space: nowrap; flex-shrink: 0;"
                            onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'"
                            onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                            ← Volver
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div style="padding: 24px; background: #fafafa; overflow-y: auto; max-height: 65vh; min-height: 200px;">
                        ${htmlListaPrendas}
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
                    container: 'swal-centered-container prendas-modal-container',
                    popup: 'swal-centered-popup prendas-modal-popup',
                    htmlContainer: 'prendas-modal-html'
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
                }
            });
        });
    }
</script>

