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
     * 🔄 Recargar la tabla de pedidos sin recargar la página
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
            console.error('❌ [recargarTablaPedidos] Error:', error);
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
                UI.info('Sin EPP', 'No hay EPP agregado en este pedido');
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
                console.error('❌ [EDITAR-EPP-ESPECIFICO] Error:', error.message);
                Swal.fire('Error', `No se pudieron cargar los datos: ${error.message}`, 'error');
            }
        });
    }
</script>

<!-- Modal para editar EPP (formulario simple) -->
<div id="modal-editar-epp-form" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <h3 style="margin: 0 0 1.5rem 0; color: #1f2937; font-size: 1.3rem;">Editar EPP</h3>
        
        <div style="margin-bottom: 1.5rem; position: relative;">
            <label for="buscadorEppEdicion" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">Buscar EPP:</label>
            <input type="text" id="buscadorEppEdicion" placeholder="Buscar EPP..." style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
            <div id="resultadosBuscadorEppEdicion" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 6px 6px; max-height: 200px; overflow-y: auto; display: none; z-index: 10;"></div>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <label for="modalEppNombre" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">EPP Seleccionado:</label>
            <input type="text" id="modalEppNombre" disabled style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; background: #f3f4f6; color: #6b7280;">
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <label for="cantidadEPP" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">Cantidad:</label>
            <input type="number" id="cantidadEPP" min="1" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <label for="observacionesEPP" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">Observaciones:</label>
            <textarea id="observacionesEPP" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; min-height: 100px; resize: vertical;"></textarea>
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
    // Funciones para abrir/cerrar el modal de edición de EPP
    function abrirModalEditarEppForm(eppData) {
        console.log('[Modal Editar EPP] Abriendo formulario con datos:', eppData);
        
        const modal = document.getElementById('modal-editar-epp-form');
        if (!modal) {
            console.error('❌ Modal no encontrado en el DOM');
            return;
        }
        
        // Llenar los campos
        document.getElementById('modalEppNombre').value = eppData.nombre_completo || eppData.nombre || '';
        document.getElementById('buscadorEppEdicion').value = '';
        document.getElementById('cantidadEPP').value = eppData.cantidad || 1;
        document.getElementById('observacionesEPP').value = eppData.observaciones || '';
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
        document.getElementById('buscadorEppEdicion').value = '';
    }
    
    // Inicializar buscador de EPPs
    function inicializarBuscadorEpp() {
        const buscador = document.getElementById('buscadorEppEdicion');
        if (!buscador) return;
        
        buscador.addEventListener('input', function(e) {
            const valor = e.target.value.trim();
            console.log('🔍 Buscando EPP:', valor);
            
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
            console.error('❌ Error buscando EPPs:', error);
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
        
        const cantidad = parseInt(document.getElementById('cantidadEPP').value);
        const observaciones = document.getElementById('observacionesEPP').value;
        const eppId = eppData.epp_id;
        
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
            cantidad,
            observaciones,
            eppId
        };
        
        // Mostrar modal para registrar la novedad
        document.getElementById('modal-novedad-epp').style.display = 'flex';
        document.getElementById('noveladaEPP').value = '';
        document.getElementById('noveladaEPP').focus();
    }
    
    function cerrarModalNovedad() {
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
        
        try {
            const pedidoId = window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido;
            const pedidoEppId = cambios.eppData.pedido_epp_id;
            
            console.log('📡 [Modal Novedad] Guardando cambios con novedad...', {
                pedidoId,
                pedidoEppId,
                cantidad: cambios.cantidad,
                observaciones: cambios.observaciones,
                eppId: cambios.eppId,
                novedad
            });
            
            const response = await fetch(`/api/pedidos/${pedidoId}/epp/${pedidoEppId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    cantidad: cambios.cantidad,
                    observaciones: cambios.observaciones,
                    epp_id: cambios.eppId,
                    novedad: novedad
                })
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Error al guardar');
            }
            
            const resultado = await response.json();
            console.log(' Cambios guardados con novedad:', resultado);
            
            // Actualizar el EPP en la lista de datos sin recargar la página
            if (window.datosEdicionPedido && window.datosEdicionPedido.epps) {
                const eppIndex = window.datosEdicionPedido.epps.findIndex(e => e.id === pedidoEppId);
                if (eppIndex !== -1) {
                    window.datosEdicionPedido.epps[eppIndex].cantidad = cambios.cantidad;
                    window.datosEdicionPedido.epps[eppIndex].observaciones = cambios.observaciones;
                    if (cambios.eppId) {
                        window.datosEdicionPedido.epps[eppIndex].epp_id = cambios.eppId;
                    }
                    console.log(' [Actualizar EPP] EPP actualizado en memoria:', window.datosEdicionPedido.epps[eppIndex]);
                }
            }
            
            cerrarModalNovedad();
            cerrarModalEditarEppForm();
            
            // 🔄 Recargar la tabla de pedidos sin recargar la página
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
            console.error('❌ Error guardando cambios:', error);
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
        }
    }
    
    // Exponer funciones globalmente
    window.abrirModalEditarEppForm = abrirModalEditarEppForm;
    window.cerrarModalEditarEppForm = cerrarModalEditarEppForm;
    window.guardarCambiosEPP = guardarCambiosEPP;
</script>

