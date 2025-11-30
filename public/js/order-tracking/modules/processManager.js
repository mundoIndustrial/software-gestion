/**
 * Módulo: ProcessManager
 * Responsabilidad: Gestionar operaciones sobre procesos (editar, eliminar)
 * Principio SOLID: Single Responsibility
 */

const ProcessManager = (() => {
    /**
     * Abre el modal de edición de un proceso
     */
    function openEditModal(procesoData) {
        const modalHTML = `
            <div id="editProcesoModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;">
                <div style="background: white; border-radius: 8px; padding: 24px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <h2 style="margin: 0 0 20px 0; font-size: 20px; font-weight: 600; color: #1f2937;">Editar Proceso</h2>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #374151;">Nombre del Proceso</label>
                        <input type="text" id="editProceso" value="${procesoData.proceso}" 
                               style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #374151;">Fecha Inicio</label>
                        <input type="date" id="editFecha" value="${convertToDateInput(procesoData.fecha_inicio)}" 
                               style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #374151;">Encargado</label>
                        <input type="text" id="editEncargado" value="${procesoData.encargado || ''}" 
                               style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #374151;">Estado</label>
                        <select id="editEstado" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                            <option value="Pendiente" ${procesoData.estado_proceso === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                            <option value="En Progreso" ${procesoData.estado_proceso === 'En Progreso' ? 'selected' : ''}>En Progreso</option>
                            <option value="Completado" ${procesoData.estado_proceso === 'Completado' ? 'selected' : ''}>Completado</option>
                            <option value="Pausado" ${procesoData.estado_proceso === 'Pausado' ? 'selected' : ''}>Pausado</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button id="btnCancelarProceso" style="padding: 10px 20px; background: #e5e7eb; color: #374151; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">
                            Cancelar
                        </button>
                        <button id="btnGuardarProceso" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        document.getElementById('btnCancelarProceso').addEventListener('click', closeEditModal);
        document.getElementById('btnGuardarProceso').addEventListener('click', () => saveProcess(procesoData));
    }
    
    /**
     * Cierra el modal de edición
     */
    function closeEditModal() {
        const modal = document.getElementById('editProcesoModal');
        if (modal) modal.remove();
    }
    
    /**
     * Convierte fecha a formato yyyy-mm-dd para input date
     */
    function convertToDateInput(dateString) {
        const fechaParts = dateString.split('-');
        return fechaParts.length === 3 ? dateString : new Date(dateString).toISOString().split('T')[0];
    }
    
    /**
     * Guarda cambios de un proceso
     */
    async function saveProcess(procesoOriginal) {
        const btnGuardar = document.getElementById('btnGuardarProceso');
        
        if (btnGuardar.disabled || btnGuardar.dataset.saving === 'true') {
            return;
        }
        
        btnGuardar.disabled = true;
        btnGuardar.dataset.saving = 'true';
        const textOriginal = btnGuardar.textContent;
        btnGuardar.textContent = 'Guardando...';
        
        try {
            const proceso = document.getElementById('editProceso').value;
            const fecha_inicio = document.getElementById('editFecha').value;
            const encargado = document.getElementById('editEncargado').value;
            const estado_proceso = document.getElementById('editEstado').value;
            
            if (!proceso || !fecha_inicio) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos requeridos',
                    text: 'Por favor completa todos los campos',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            // Buscar proceso
            const buscarData = await ApiClient.buscarProceso(procesoOriginal.numero_pedido, procesoOriginal.proceso);
            const procesoId = buscarData.id;
            
            // Actualizar proceso
            const result = await ApiClient.updateProceso(procesoId, {
                numero_pedido: procesoOriginal.numero_pedido,
                proceso,
                fecha_inicio,
                encargado,
                estado_proceso
            });
            
            if (result.success) {
                closeEditModal();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: 'Proceso actualizado correctamente',
                    timer: 1500,
                    timerProgressBar: true,
                    confirmButtonColor: '#10b981',
                    didClose: () => reloadTrackingModal()
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error al guardar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#ef4444'
            });
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.dataset.saving = 'false';
            btnGuardar.textContent = textOriginal;
        }
    }
    
    /**
     * Elimina un proceso
     */
    async function deleteProcess(procesoData) {
        const confirmed = await Swal.fire({
            icon: 'warning',
            title: 'Confirmar eliminación',
            text: `¿Está seguro de que desea eliminar el proceso "${procesoData.proceso}"?`,
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });
        
        if (!confirmed.isConfirmed) {
            return;
        }
        
        try {
            const buscarData = await ApiClient.buscarProceso(procesoData.numero_pedido, procesoData.proceso);
            const procesoId = buscarData.id;
            
            const result = await ApiClient.deleteProceso(procesoId, procesoData.numero_pedido);
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminado',
                    text: 'Proceso eliminado correctamente',
                    timer: 1500,
                    timerProgressBar: true,
                    confirmButtonColor: '#10b981',
                    didClose: () => reloadTrackingModal()
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error al eliminar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#ef4444'
            });
        }
    }
    
    /**
     * Recarga el modal de tracking
     */
    async function reloadTrackingModal() {
        const numeroPedido = document.getElementById('trackingOrderNumber').textContent.replace('#', '');
        if (numeroPedido) {
            try {
                const data = await ApiClient.getOrderProcesos(numeroPedido);
                displayOrderTrackingWithProcesos(data);
            } catch (error) {
                console.error('Error recargando tracking:', error);
            }
        }
    }
    
    // Interfaz pública
    return {
        openEditModal,
        closeEditModal,
        deleteProcess,
        reloadTrackingModal
    };
})();

globalThis.ProcessManager = ProcessManager;
