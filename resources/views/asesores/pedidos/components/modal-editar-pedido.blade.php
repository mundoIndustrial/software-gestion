{{-- Modal para Editar Pedido --}}
<script>
    /**
     * Abrir modal de edición de pedido (factura interactiva)
     * @param {number} pedidoId - ID del pedido
     * @param {object} datosCompletos - Datos del pedido
     * @param {string} modo - 'editar' (con botones) o 'ver' (solo lectura)
     */
    async function abrirModalEditarPedido(pedidoId, datosCompletos, modo = 'editar') {
        console.log('[🔍 abrirModalEditarPedido] Iniciando... Pedido:', pedidoId);
        console.log('[🔍 Swal disponible?:', typeof Swal !== 'undefined');
        
        // 🔥 IMPORTANTE: Esperar a que Swal esté disponible
        if (typeof Swal === 'undefined') {
            console.log('[⏳ Esperando a que Swal cargue...]');
            await _ensureSwal();
            console.log('[✅ Swal ya está disponible]');
        }
        
        // Guardar datos en variable global
        window.datosEdicionPedido = datosCompletos;
        
        // Generar factura
        let htmlFactura = window.generarHTMLFactura ? window.generarHTMLFactura(datosCompletos) : '<p>Error: No se pudo generar la factura</p>';
        
        let htmlBotones = '';
        if (modo === 'editar') {
            htmlBotones = `
                <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px; border-left: 4px solid #6b7280; margin-bottom: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <button onclick="abrirEditarDatos()" style="background: #6b7280; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 11px !important; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.backgroundColor='#4b5563'" onmouseout="this.style.backgroundColor='#6b7280'"> Editar Datos</button>
                    <button onclick="abrirEditarPrendas()" style="background: #6b7280; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 11px !important; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.backgroundColor='#4b5563'" onmouseout="this.style.backgroundColor='#6b7280'"> Editar Prendas</button>
                    <button onclick="abrirEditarEPP()" style="background: #6b7280; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 11px !important; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.backgroundColor='#4b5563'" onmouseout="this.style.backgroundColor='#6b7280'"> Editar EPP</button>
                </div>
            `;
        }
        
        const htmlConHeader = `
            <div style="background: white; border-radius: 6px; width: 100%; height: 100%; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.3); overflow: hidden; box-sizing: border-box;">
                <!-- Header -->
                <div style="padding: 16px 20px; border-bottom: 2px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); flex-shrink: 0;">
                    <h3 style="margin: 0; color: white; font-size: 11px !important; font-weight: 700;">
                         Editar Pedido #${datosCompletos.numero_pedido}
                    </h3>
                    <button onclick="Swal.close();" 
                            style="background: #ef4444; border: none; font-size: 11px !important; cursor: pointer; color: white; padding: 0; line-height: 1; transition: all 0.2s; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; width: 50px; height: 50px; flex-shrink: 0;" onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'" onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                        ×
                    </button>
                </div>
                
                <!-- Content -->
                <div style="flex: 1; overflow: auto; padding: 8px 10px; background: #fafafa; box-sizing: border-box;">
                    ${htmlBotones + `<div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; background: white;">
                        ${htmlFactura}
                        <div id="lista-items-pedido" style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.5rem;"></div>
                    </div>`}
                </div>
            </div>
        `;
        
        console.log('[🔍 abrirModalEditarPedido] HTML generado');
        
        // Usar Swal.fire() directamente en lugar de UI.contenido()
        // para evitar el centrado vertical que corta el modal
        return Swal.fire({
            html: htmlConHeader,
            width: '95%',
            showConfirmButton: false,
            allowOutsideClick: true,
            allowEscapeKey: true,
            didOpen: (modal) => {
                console.log('[✅ abrirModalEditarPedido] Modal abierto correctamente en Swal');
                
                // � LOGS DE TAMAÑO
                const swalContainer = document.querySelector('.swal2-container');
                const swalPopup = document.querySelector('.swal2-popup');
                const modalInner = document.querySelector('.swal2-popup > div');
                
                if (swalContainer) {
                    const containerRect = swalContainer.getBoundingClientRect();
                    console.log(`[📏 Container] Ancho: ${containerRect.width}px, Alto: ${containerRect.height}px`);
                }
                
                if (swalPopup) {
                    const popupRect = swalPopup.getBoundingClientRect();
                    console.log(`[📏 Popup] Ancho: ${popupRect.width}px, Alto: ${popupRect.height}px`);
                    console.log(`[📏 Popup Style] width: ${swalPopup.style.width}, maxWidth: ${swalPopup.style.maxWidth}`);
                }
                
                if (modalInner) {
                    const innerRect = modalInner.getBoundingClientRect();
                    console.log(`[📏 Modal Inner] Ancho: ${innerRect.width}px, Alto: ${innerRect.height}px`);
                    console.log(`[📏 Modal Inner CSS] width: ${window.getComputedStyle(modalInner).width}, maxWidth: ${window.getComputedStyle(modalInner).maxWidth}`);
                }
                
                // �🔥 Inicializar servicio de almacenamiento de imágenes si no existe
                if (!window.imagenesPrendaStorage) {
                    console.log('[🔧 Inicializando ImageStorageService...]');
                    window.imagenesPrendaStorage = new ImageStorageService(3);
                    console.log('[✅ ImageStorageService inicializado]');
                }
                
                // 🔥 FIX: Centrar el modal en la página
                if (swalContainer) {
                    // Forzar display flex y centrado - z-index muy alto para estar siempre al frente
                    swalContainer.style.cssText = 'display: flex !important; align-items: center !important; justify-content: center !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; z-index: 9999999 !important;';
                    
                    // 🔍 LOGS de debug
                    console.log('[🔍 Container CSS aplicado]');
                    const computedStyle = window.getComputedStyle(swalContainer);
                    console.log('  display:', computedStyle.display);
                    console.log('  alignItems:', computedStyle.alignItems);
                    console.log('  justifyContent:', computedStyle.justifyContent);
                    console.log('  z-index:', computedStyle.zIndex);
                    console.log('[✅ Container configurado]');
                }
                
                if (swalPopup) {
                    // Forzar que sea visible con display block
                    swalPopup.style.cssText = 'display: flex !important; flex-direction: column !important; position: static !important; width: 95vw !important; max-height: 95vh !important; height: 95vh !important; overflow: hidden !important; padding: 0 !important; margin: 0 !important; box-sizing: border-box !important;';
                    
                    // 🔍 LOGS del popup
                    console.log('[🔍 Popup CSS aplicado]');
                    const popupComputed = window.getComputedStyle(swalPopup);
                    console.log('  display:', popupComputed.display);
                    console.log('  position:', popupComputed.position);
                    console.log('  width:', popupComputed.width);
                    console.log('[✅ Popup configurado]');
                }
                
                const swalHtmlContainer = document.querySelector('.swal2-html-container');
                if (swalHtmlContainer) {
                    swalHtmlContainer.style.cssText = 'padding: 0 !important; margin: 0 !important; overflow: visible !important; width: 100% !important; height: 100% !important; display: flex !important; flex-direction: column !important; box-sizing: border-box !important;';
                    console.log('[🔍 HTML Container CSS aplicado]');
                }
                
                // Prevenir scroll del body
                document.body.style.overflow = 'hidden';
                
                console.log('[✅ abrirModalEditarPedido] Configuración completada');
            },
            willClose: () => {
                document.body.style.overflow = '';
            }
        });
    }
</script>
