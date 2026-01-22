<!-- Modal para editar EPP del pedido -->
<script>
    /**
     * Abrir formulario para editar EPP del pedido (lista seleccionable)
     */
    function abrirEditarEPP() {
        Validator.requireEdicionPedido(() => {
            const datos = window.datosEdicionPedido;
            // datosEdicionPedido tiene estructura: { epps: [...], prendas: [...], numero_pedido, ...}
            const epp = datos.epps || [];
            
            console.log('[EDITAR-EPP] Datos del pedido recibidos:', datos);
            console.log('[EDITAR-EPP] Estructura de datos.epps:', epp);
            console.log('[EDITAR-EPP] Primera EPP ejemplo:', epp.length > 0 ? epp[0] : 'Sin EPP');
            
            if (epp.length === 0) {
                UI.info('Sin EPP', 'No hay EPP agregado en este pedido');
                return;
            }
            
            window.eppEdicion = {
                pedidoId: datos.numero_pedido || datos.id,
                epp: epp
            };
            
            let htmlListaEPP = `<div style="display: grid; grid-template-columns: 1fr; gap: 0.75rem;">`;
            
            epp.forEach((item, idx) => {
                // Los campos del backend son: epp_nombre, epp_codigo, epp_categoria, cantidad, observaciones, imagenes
                const nombre = item.epp_nombre || item.nombre_completo || item.nombre || item.descripcion || 'EPP sin nombre';
                htmlListaEPP += `
                    <button onclick="abrirEditarEPPEspecifico(${idx})" 
                        style="background: white; border: 2px solid #1e40af; border-radius: 8px; padding: 1rem; text-align: left; cursor: pointer; transition: all 0.3s ease;"
                        onmouseover="this.style.background='#eff6ff'; this.style.borderColor='#1e40af';"
                        onmouseout="this.style.background='white'; this.style.borderColor='#1e40af';">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="margin: 0; color: #1f2937; font-size: 0.95rem; font-weight: 700;"> ${nombre.toUpperCase()}</h4>
                                <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.85rem;">Cantidad: <strong>${item.cantidad || 0}</strong></p>
                            </div>
                            <span style="background: #1e40af; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;"> Editar</span>
                        </div>
                    </button>
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
                        <button onclick="abrirModalEditarPedido(window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido, window.datosEdicionPedido, 'editar');" 
                            style="background: #ef4444; border: none; cursor: pointer; color: white; padding: 10px 16px; line-height: 1; transition: all 0.2s; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 14px; white-space: nowrap; flex-shrink: 0;"
                            onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'"
                            onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                            ← Volver
                        </button>
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
                }
            });
        });
    }
    
    /**
     * Abrir modal de edición para un EPP específico
     */
    function abrirEditarEPPEspecifico(eppIndex) {
        Validator.requireEppItem(eppIndex, (epp) => {
            // Usar el servicio de EPP si está disponible (para usar el mismo modal de la página de crear)
            if (typeof window.eppService !== 'undefined' && window.eppService?.editarEPPFormulario) {
                console.log('[EDITAR-EPP] Objeto EPP completo:', JSON.stringify(epp, null, 2));
                console.log('[EDITAR-EPP] Cantidad:', epp.cantidad);
                console.log('[EDITAR-EPP] Observaciones:', epp.observaciones);
                console.log('[EDITAR-EPP] Imágenes objeto:', epp.imagenes);
                console.log('[EDITAR-EPP] Todas las claves del EPP:', Object.keys(epp));
                
                // Preparar datos del EPP - usar los campos del backend: epp_id, epp_nombre, epp_codigo, epp_categoria
                const datosEpp = {
                    id: epp.epp_id || epp.id,
                    nombre: epp.epp_nombre || epp.nombre || epp.nombre_completo || 'EPP',
                    codigo: epp.epp_codigo || epp.codigo || '',
                    categoria: epp.epp_categoria || epp.categoria || ''
                };
                
                // Las imágenes vienen como array desde el backend
                const imagenes = Array.isArray(epp.imagenes) ? epp.imagenes : [];
                console.log('[EDITAR-EPP] Imágenes formateadas:', imagenes);
                console.log('[EDITAR-EPP] Cantidad de imágenes:', imagenes.length);
                
                console.log('[EDITAR-EPP] Abriendo modal de EPP usando servicio con datos:', datosEpp);
                
                // Abrir el modal con los datos del EPP y las imágenes
                window.eppService.editarEPPFormulario(
                    datosEpp.id,
                    datosEpp.nombre,
                    datosEpp.codigo,
                    datosEpp.categoria,
                    epp.cantidad || 0,
                    epp.observaciones || '',
                    imagenes
                );
            }
        });
    }
</script>
