{{-- Modal para Editar Pedido --}}
<script>
    /**
     * Abrir modal de edición de pedido (factura interactiva)
     * @param {number} pedidoId - ID del pedido
     * @param {object} datosCompletos - Datos del pedido
     * @param {string} modo - 'editar' (con botones) o 'ver' (solo lectura)
     */
    function abrirModalEditarPedido(pedidoId, datosCompletos, modo = 'editar') {
        // Guardar datos en variable global
        window.datosEdicionPedido = datosCompletos;
        
        // Generar factura
        let htmlFactura = window.generarHTMLFactura ? window.generarHTMLFactura(datosCompletos) : '<p>Error: No se pudo generar la factura</p>';
        
        let htmlBotones = '';
        if (modo === 'editar') {
            htmlBotones = `
                <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px; border-left: 4px solid #6b7280; margin-bottom: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <button onclick="abrirEditarDatos()" style="background: #6b7280; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.backgroundColor='#4b5563'" onmouseout="this.style.backgroundColor='#6b7280'"> Editar Datos</button>
                    <button onclick="abrirEditarPrendas()" style="background: #6b7280; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.backgroundColor='#4b5563'" onmouseout="this.style.backgroundColor='#6b7280'"> Editar Prendas</button>
                    <button onclick="abrirEditarEPP()" style="background: #6b7280; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.backgroundColor='#4b5563'" onmouseout="this.style.backgroundColor='#6b7280'"> Editar EPP</button>
                </div>
            `;
        }
        
        const htmlConHeader = `
            <div style="background: white; border-radius: 6px; width: 100%; max-width: 1100px; height: 95vh; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.3); overflow: hidden;">
                <!-- Header -->
                <div style="padding: 16px 20px; border-bottom: 2px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);">
                    <h3 style="margin: 0; color: white; font-size: 18px; font-weight: 700;">
                         Editar Pedido #${datosCompletos.numero_pedido}
                    </h3>
                    <button onclick="Swal.close();" 
                            style="background: #ef4444; border: none; font-size: 40px; cursor: pointer; color: white; padding: 0; line-height: 1; transition: all 0.2s; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; width: 50px; height: 50px;" onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'" onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                        X
                    </button>
                </div>
                
                <!-- Content -->
                <div style="flex: 1; overflow: auto; padding: 8px 10px; background: #fafafa;">
                    ${htmlBotones + `<div style="max-height: 600px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; background: white;">${htmlFactura}</div>`}
                </div>
            </div>
        `;
        
        UI.contenido({
            html: htmlConHeader,
            ancho: '1150px',
            showConfirmButton: false,
            allowOutsideClick: true,
            allowEscapeKey: true,
            didOpen: () => {
                const swalPopup = document.querySelector('.swal2-popup');
                if (swalPopup) {
                    swalPopup.style.padding = '0';
                    swalPopup.style.borderRadius = '6px';
                }
                const swalHtmlContainer = document.querySelector('.swal2-html-container');
                if (swalHtmlContainer) {
                    swalHtmlContainer.style.padding = '0';
                    swalHtmlContainer.style.margin = '0';
                }
            }
        });
    }
</script>
