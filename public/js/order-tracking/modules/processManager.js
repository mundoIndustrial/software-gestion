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
        // Remover modal anterior si existe
        const existingModal = document.getElementById('editProcesoModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const modalHTML = `
            <div id="editProcesoModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; animation: fadeIn 0.3s ease;">
                <div style="background: white; border-radius: 12px; padding: 28px; max-width: 500px; width: 90%; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); z-index: 9999; animation: slideUp 0.3s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h2 style="margin: 0; font-size: 22px; font-weight: 700; color: #1f2937;">Editar Proceso</h2>
                        <button id="btnCerrarModal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                            ×
                        </button>
                    </div>
                    
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #374151;">Nombre del Proceso</label>
                        <input type="text" id="editProceso" value="${procesoData.proceso}" 
                               style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 14px; transition: border-color 0.3s ease; color: #1f2937; background: white;"
                               onFocus="this.style.borderColor='#3b82f6'"
                               onBlur="this.style.borderColor='#d1d5db'">
                    </div>
                    
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #374151;">Fecha Inicio</label>
                        <input type="date" id="editFecha" value="${convertToDateInput(procesoData.fecha_inicio)}" 
                               style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 14px; transition: border-color 0.3s ease; color: #1f2937; background: white;"
                               onFocus="this.style.borderColor='#3b82f6'"
                               onBlur="this.style.borderColor='#d1d5db'">
                    </div>
                    
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #374151;">Encargado</label>
                        <input type="text" id="editEncargado" value="${procesoData.encargado || ''}" 
                               style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 14px; transition: border-color 0.3s ease; color: #1f2937; background: white;"
                               onFocus="this.style.borderColor='#3b82f6'"
                               onBlur="this.style.borderColor='#d1d5db'">
                    </div>
                    
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #374151;">Estado</label>
                        <select id="editEstado" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 14px; transition: border-color 0.3s ease; color: #1f2937; background: white;"
                                onFocus="this.style.borderColor='#3b82f6'"
                                onBlur="this.style.borderColor='#d1d5db'">
                            <option value="Pendiente" ${procesoData.estado_proceso === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                            <option value="En Progreso" ${procesoData.estado_proceso === 'En Progreso' ? 'selected' : ''}>En Progreso</option>
                            <option value="Completado" ${procesoData.estado_proceso === 'Completado' ? 'selected' : ''}>Completado</option>
                            <option value="Pausado" ${procesoData.estado_proceso === 'Pausado' ? 'selected' : ''}>Pausado</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button id="btnCancelarProceso" style="padding: 12px 24px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease;"
                                onMouseOver="this.style.background='#d1d5db'"
                                onMouseOut="this.style.background='#e5e7eb'">
                            Cancelar
                        </button>
                        <button id="btnGuardarProceso" style="padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease;"
                                onMouseOver="this.style.background='#2563eb'"
                                onMouseOut="this.style.background='#3b82f6'">
                            Guardar
                        </button>
                    </div>
                </div>
                <style>
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideUp {
                        from { transform: translateY(20px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                </style>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Agregar event listeners con manejo de errores
        const btnCancelar = document.getElementById('btnCancelarProceso');
        const btnCerrar = document.getElementById('btnCerrarModal');
        const btnGuardar = document.getElementById('btnGuardarProceso');
        const modal = document.getElementById('editProcesoModal');
        
        if (btnCancelar) {
            btnCancelar.addEventListener('click', closeEditModal);
        }
        
        if (btnCerrar) {
            btnCerrar.addEventListener('click', closeEditModal);
        }
        
        if (btnGuardar) {
            btnGuardar.addEventListener('click', () => saveProcess(procesoData));
        }
        
        // Cerrar al hacer click en el overlay
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeEditModal();
                }
            });
            
            // Cerrar con tecla ESC
            const handleEsc = (e) => {
                if (e.key === 'Escape') {
                    closeEditModal();
                    document.removeEventListener('keydown', handleEsc);
                }
            };
            document.addEventListener('keydown', handleEsc);
        }
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
        if (!dateString) return '';
        
        // Si ya está en formato yyyy-mm-dd, devolver como está
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
            return dateString;
        }
        
        // Intentar parsear diferentes formatos
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return '';
            }
            // Convertir a UTC para evitar problemas de zona horaria
            const year = date.getUTCFullYear();
            const month = String(date.getUTCMonth() + 1).padStart(2, '0');
            const day = String(date.getUTCDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        } catch (e) {
            return '';
        }
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
                    confirmButtonColor: '#3b82f6',
                    didOpen: (modal) => {
                        const swalContainer = document.querySelector('.swal2-container');
                        if (swalContainer) {
                            swalContainer.style.zIndex = '10002';
                        }
                        modal.style.zIndex = '10002';
                    }
                });
                return;
            }
            
            // Usar el ID del proceso original para actualizar
            if (!procesoOriginal.id) {
                throw new Error('ID de proceso no disponible');
            }
            
            // Actualizar proceso
            const result = await ApiClient.updateProceso(procesoOriginal.id, {
                numero_pedido: procesoOriginal.numero_pedido,
                proceso,
                fecha_inicio,
                encargado,
                estado_proceso
            });
            
            if (result.success) {
                closeEditModal();
                
                // Usar toast en lugar de modal
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    },
                    didClose: () => reloadTrackingModal()
                });
                Toast.fire({
                    icon: 'success',
                    title: 'Guardado exitosamente'
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error al guardar:', error);
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            Toast.fire({
                icon: 'error',
                title: 'Error al guardar',
                text: error.message
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
    /**
     * Espera a que UpdatesModule esté disponible
     */
    function waitForUpdatesModule(maxAttempts = 10) {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const interval = setInterval(() => {
                if (window.UpdatesModule || globalThis.UpdatesModule) {
                    clearInterval(interval);
                    resolve(window.UpdatesModule || globalThis.UpdatesModule);
                } else if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    reject(new Error('UpdatesModule no disponible después de múltiples intentos'));
                }
                attempts++;
            }, 100);
        });
    }

    async function deleteProcess(procesoData) {
        const confirmed = await Swal.fire({
            icon: 'warning',
            title: 'Confirmar eliminación',
            text: `¿Está seguro de que desea eliminar el proceso "${procesoData.proceso}"?`,
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            didOpen: (modal) => {
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '99999';
                }
                modal.style.zIndex = '99999';
            }
        });
        
        if (!confirmed.isConfirmed) {
            return;
        }
        
        try {
            if (!procesoData.id) {
                throw new Error('ID de proceso no disponible');
            }
            
            const result = await ApiClient.deleteProceso(procesoData.id, procesoData.numero_pedido);
            
            if (result.success) {
                if (typeof showToast === 'function') {
                    showToast('Proceso eliminado exitosamente', 'success');
                } else {
                    console.log('✅ Proceso eliminado exitosamente');
                }
                
                // 🆕 IMPORTANTE: El Observer ya actualizó el área en la BD
                // Obtener la orden actualizada para saber cuál es la nueva área
                const ordenResponse = await fetch(`/registros/${procesoData.numero_pedido}`);
                const ordenActualizada = await ordenResponse.json();
                const newAreaFromServer = ordenActualizada.area;
                
                console.log(`📡 Área actualizada en servidor: ${newAreaFromServer}`);
                
                // Obtener los procesos actualizados (solo para referencia)
                const procesosData = await fetch(`/api/ordenes/${procesoData.numero_pedido}/procesos`).then(r => r.json());
                const procesos = procesosData.procesos || [];
                
                console.log(`📋 Procesos restantes: ${procesos.length}`);
                procesos.forEach((p, i) => {
                    console.log(`   [${i}] ${p.proceso}: ${p.estado_proceso} (fecha: ${p.fecha_inicio})`);
                });
                
                // Usar el área que el Observer ya actualizó en la BD
                const newArea = newAreaFromServer;
                
                // Actualizar el área en la tabla usando UpdatesModule
                if (newArea) {
                    try {
                        const updatesModule = window.UpdatesModule || globalThis.UpdatesModule;
                        
                        if (updatesModule) {
                            let areaDropdown = null;
                            const tabla = document.querySelector('table#tablaOrdenes tbody');
                            if (tabla) {
                                areaDropdown = tabla.querySelector(`.area-dropdown[data-id="${procesoData.numero_pedido}"]`);
                            }
                            
                            const oldArea = areaDropdown?.dataset.value || '';
                            
                            console.log(`🔄 [deleteProcess] Llamando UpdatesModule.updateOrderArea`);
                            console.log(`   - Pedido: ${procesoData.numero_pedido}`);
                            console.log(`   - Area anterior: ${oldArea}`);
                            console.log(`   - Area nueva: ${newArea}`);
                            console.log(`   - Dropdown en tabla encontrado: ${!!areaDropdown}`);
                            
                            await updatesModule.updateOrderArea(
                                procesoData.numero_pedido,
                                newArea,
                                oldArea,
                                areaDropdown
                            );
                            
                            console.log('✅ UpdatesModule.updateOrderArea completado');
                        } else {
                            console.warn('⚠️ UpdatesModule no disponible, intentando alternativa...');
                            const tabla = document.querySelector('table#tablaOrdenes tbody');
                            const areaDropdown = tabla ? tabla.querySelector(`.area-dropdown[data-id="${procesoData.numero_pedido}"]`) : null;
                            if (areaDropdown) {
                                areaDropdown.value = newArea;
                                areaDropdown.dataset.value = newArea;
                                areaDropdown.dataset.programmaticChange = 'true';
                                areaDropdown.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('✅ Área actualizada directamente en dropdown de tabla');
                            }
                        }
                    } catch (error) {
                        console.error('Error al actualizar área después de eliminar:', error);
                    }
                }
                
                // Recargar el modal después de un pequeño delay para asegurar que la BD esté actualizada
                const numeroPedido = document.getElementById('trackingOrderNumber')?.textContent.replace('#', '');
                if (numeroPedido) {
                    // Esperar 300ms para asegurar que la BD esté actualizada
                    await new Promise(resolve => setTimeout(resolve, 300));
                    
                    try {
                        console.log(`🔄 Recargando modal para pedido ${numeroPedido}...`);
                        const data = await ApiClient.getOrderProcesos(numeroPedido);
                        console.log(`📋 Procesos recargados: ${data.procesos?.length || 0}`);
                        
                        // Recargar el modal con los nuevos datos
                        if (typeof displayOrderTrackingWithProcesos === 'function') {
                            displayOrderTrackingWithProcesos(data);
                            console.log('✅ Modal recargado con nuevos procesos');
                        } else if (typeof reloadTrackingModal === 'function') {
                            reloadTrackingModal();
                            console.log('✅ Modal recargado con reloadTrackingModal');
                        } else {
                            console.warn('⚠️ No se encontró función para recargar el modal');
                        }
                    } catch (error) {
                        console.error('Error recargando tracking:', error);
                    }
                }
                
                // Refrescar la tabla en segundo plano
                console.log('🔄 Refrescando tabla en segundo plano...');
                setTimeout(() => {
                    _refreshTableRow(procesoData.numero_pedido);
                }, 500);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error al eliminar:', error);
            if (typeof showToast === 'function') {
                showToast(`Error al eliminar: ${error.message}`, 'error');
            } else {
                console.error('Error al eliminar:', error);
            }
        }
    }
    
    /**
     * 🆕 Refrescar una fila específica de la tabla desde el servidor
     */
    function _refreshTableRow(numeroPedido) {
        return (async () => {
            try {
                console.log(`📊 Refrescando fila ${numeroPedido} en tabla...`);
                
                // Obtener los procesos actuales desde el API
                const procesosResponse = await fetch(`/api/ordenes/${numeroPedido}/procesos`);
                if (!procesosResponse.ok) {
                    console.warn(`⚠️ No se pudieron obtener procesos para ${numeroPedido}`);
                    return;
                }
                
                const procesosData = await procesosResponse.json();
                const procesos = procesosData.procesos || [];
                
                console.log(`📋 Procesos totales: ${procesos.length}`);
                procesos.forEach((p, i) => {
                    console.log(`   [${i}] ${p.proceso}: ${p.estado_proceso}`);
                });
                
                // Buscar el próximo proceso pendiente
                let proximoProceso = procesos.find(p => p.estado_proceso === 'Pendiente');
                
                // Si no hay pendiente, buscar el primer no-completado
                if (!proximoProceso) {
                    console.log('⚠️ No hay proceso Pendiente, buscando próximo no-completado...');
                    proximoProceso = procesos.find(p => p.estado_proceso !== 'Completado');
                }
                
                // Si tampoco hay, simplemente usar el área actual del dropdown
                if (!proximoProceso) {
                    console.log('ℹ️ Todos los procesos están completados, no hay cambios que hacer');
                    return;
                }
                
                const newArea = proximoProceso.proceso;
                
                // Buscar la fila en la tabla
                const tabla = document.querySelector('table#tablaOrdenes tbody');
                if (!tabla) {
                    console.warn('⚠️ Tabla no encontrada');
                    return;
                }
                
                const fila = tabla.querySelector(`tr[data-numero-pedido="${numeroPedido}"]`);
                if (!fila) {
                    console.warn(`⚠️ Fila ${numeroPedido} no encontrada en tabla`);
                    return;
                }
                
                // Actualizar el dropdown de área en la fila
                const areaDropdown = fila.querySelector(`.area-dropdown[data-id="${numeroPedido}"]`);
                if (areaDropdown) {
                    const oldValue = areaDropdown.value;
                    if (oldValue !== newArea) {
                        areaDropdown.value = newArea;
                        areaDropdown.dataset.value = newArea;
                        
                        // Disparar evento para actualizar visualmente
                        areaDropdown.dispatchEvent(new Event('input', { bubbles: true }));
                        
                        console.log(`✅ Fila ${numeroPedido} refrescada: ${oldValue} → ${newArea}`);
                    } else {
                        console.log(`ℹ️ Fila ${numeroPedido} ya estaba actualizada: ${newArea}`);
                    }
                } else {
                    console.warn(`⚠️ Dropdown de área no encontrado en fila ${numeroPedido}`);
                }
            } catch (error) {
                console.error(`❌ Error refrescando fila ${numeroPedido}:`, error);
            }
        })();
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
