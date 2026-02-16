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
                    const nombre = item.nombre_prenda || item.nombre || 'Prenda sin nombre';
                    // Calcular cantidad total desde tallas
                    let cantidad = 0;
                    if (item.tallas && typeof item.tallas === 'object') {
                        Object.values(item.tallas).forEach(generoTallas => {
                            if (generoTallas && typeof generoTallas === 'object') {
                                Object.values(generoTallas).forEach(qty => {
                                    cantidad += parseInt(qty) || 0;
                                });
                            }
                        });
                    }
                    if (!cantidad && item.cantidad) cantidad = item.cantidad;
                    
                    // Serializar item para inyectarlo como dato literal en el onclick
                    const itemJson = JSON.stringify(item).replace(/"/g, '&quot;').replace(/'/g, "\\'");
                    htmlListaPrendas += `
                        <button onclick="(function() {
                            var item = JSON.parse(this.getAttribute('data-prenda'));
                            var idx = ${idx};
                            var pedidoId = window.datosEdicionPedido?.id;
                            console.log('[ONCLICK] Prenda clickeada:', item.nombre_prenda || item.nombre, 'idx:', idx);
                            
                            Swal.close();
                            setTimeout(function() {
                                // Usar el adapter de pedidos para abrir modal completo
                                if (typeof window.editarPrendaDePedido === 'function') {
                                    console.log('[ONCLICK] Abriendo modal completo via editarPrendaDePedido');
                                    window.editarPrendaDePedido(item, idx, pedidoId);
                                } else {
                                    console.error('[ONCLICK-ERROR] editarPrendaDePedido no disponible');
                                    // Fallback: intentar abrir modal directamente
                                    var editor = window.prendaEditorGlobal;
                                    if (editor && typeof editor.cargarPrendaEnModal === 'function') {
                                        window._editandoPrendaDePedido = { pedidoId: pedidoId, prendaIndex: idx };
                                        editor.cargarPrendaEnModal(item, idx);
                                    }
                                }
                            }, 150);
                        }).call(this)" 
                            data-prenda="${itemJson}"
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
                
                // Botón de agregar más prendas removido
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

