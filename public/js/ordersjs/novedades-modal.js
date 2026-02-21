/**
 * Gestor del Modal de Novedades
 * Permite agregar y editar novedades de pedidos directamente desde la tabla
 * 
 * NOTA: Usa currentOrderId declarado globalmente en order-navigation.js
 */

let isEditMode = false;

/**
 * Abre el modal de edición de novedades
 * @param {string} ordenId - ID del pedido
 * @param {string} novedadesActual - Contenido actual de novedades (fallback)
 */
function openNovedadesModal(ordenId, novedadesActual) {
    currentOrderId = ordenId;
    const modal = document.getElementById('novedadesEditModal');
    const textarea = document.getElementById('novedadesTextarea');
    const orderNumber = document.getElementById('novedadesOrderNumber');
    isEditMode = false;

    // Verificar si es un modal de recibo
    const tipoNovedades = modal.getAttribute('data-tipo-novedades');
    if (tipoNovedades === 'recibo') {
        // Ya fue configurado por openNovedadesModalRecibo, solo mostrar
        modal.style.display = 'flex';
        return;
    }

    // Obtener el valor actualizado del DOM primero
    let novedadesValue = novedadesActual || '';
    const row = document.querySelector(`[data-orden-id="${ordenId}"]`);
    if (row) {
        const btnEdit = row.querySelector('.btn-edit-novedades');
        if (btnEdit) {
            const textSpan = btnEdit.querySelector('.novedades-text');
            if (textSpan && !textSpan.classList.contains('empty')) {
                const fullText = btnEdit.getAttribute('data-full-novedades') || textSpan.getAttribute('title');
                if (fullText) {
                    novedadesValue = fullText;
                }
            }
        }
    }

    // Establecer contenido
    textarea.value = novedadesValue;
    textarea.readOnly = true;
    orderNumber.textContent = `Pedido: ${ordenId}`;

    // Resetear botones y estado
    document.getElementById('btnEditToggle').style.display = 'inline-flex';
    document.getElementById('btnSaveEdit').style.display = 'none';
    document.getElementById('btnAddNew').style.display = 'inline-flex';
    document.getElementById('nuevaNovedadContainer').style.display = 'none';

    // Mostrar modal
    modal.style.display = 'flex';
    
    // Enfocar textarea
    setTimeout(() => {
        textarea.focus();
    }, 100);

    // Prevenir scroll en el body
    document.body.style.overflow = 'hidden';
}

/**
 * Cierra el modal de novedades
 */
function closeNovedadesModal() {
    const modal = document.getElementById('novedadesEditModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    currentOrderId = null;
    isEditMode = false;
}

/**
 * Toggle modo edición
 */
function toggleEditMode() {
    const modal = document.getElementById('novedadesEditModal');
    const tipoNovedades = modal.getAttribute('data-tipo-novedades');
    
    // Si es un modal de recibo, usar la función específica
    if (tipoNovedades === 'recibo') {
        if (typeof toggleEditModeRecibo === 'function') {
            toggleEditModeRecibo();
            return;
        }
    }
    
    // Función original para pedidos normales
    isEditMode = !isEditMode;
    const textarea = document.getElementById('novedadesTextarea');
    const btnEditToggle = document.getElementById('btnEditToggle');
    const btnSaveEdit = document.getElementById('btnSaveEdit');
    const btnAddNew = document.getElementById('btnAddNew');

    if (isEditMode) {
        textarea.readOnly = false;
        textarea.style.background = '#f9fafb';
        textarea.style.border = '2px solid #dbeafe';
        btnEditToggle.style.display = 'none';
        btnSaveEdit.style.display = 'inline-flex';
        btnAddNew.style.display = 'none';
    } else {
        textarea.readOnly = true;
        textarea.style.background = '';
        textarea.style.border = '';
        btnEditToggle.style.display = 'inline-flex';
        btnSaveEdit.style.display = 'none';
        btnAddNew.style.display = 'inline-flex';
    }
}

/**
 * Muestra el input para agregar una nueva novedad
 */
function showNewNovedadInput() {
    const modal = document.getElementById('novedadesEditModal');
    const tipoNovedades = modal.getAttribute('data-tipo-novedades');
    
    // Si es un modal de recibo, usar la función específica
    if (tipoNovedades === 'recibo') {
        if (typeof showNewNovedadInputRecibo === 'function') {
            showNewNovedadInputRecibo();
            return;
        }
    }
    
    // Función original para pedidos normales
    const container = document.getElementById('nuevaNovedadContainer');
    const nuevaTextarea = document.getElementById('nuevaNovedadTextarea');
    
    if (container) {
        container.style.display = 'block';
        if (nuevaTextarea) {
            nuevaTextarea.value = '';
            nuevaTextarea.focus();
        }
    }
}

/**
 * Cancela la agregación de nueva novedad
 */
function cancelNewNovedad() {
    const modal = document.getElementById('novedadesEditModal');
    const tipoNovedades = modal.getAttribute('data-tipo-novedades');
    
    // Si es un modal de recibo, usar la función específica
    if (tipoNovedades === 'recibo') {
        if (typeof cancelNewNovedadRecibo === 'function') {
            cancelNewNovedadRecibo();
            return;
        }
    }
    
    // Función original para pedidos normales
    document.getElementById('nuevaNovedadContainer').style.display = 'none';
    document.getElementById('nuevaNovedadTextarea').value = '';
    document.getElementById('newCharCount').textContent = '0';
}

/**
 * Guarda una nueva novedad (agregando al final con formato [usuario - fecha hora])
 */
async function saveNewNovedad() {
    const modal = document.getElementById('novedadesEditModal');
    const tipoNovedades = modal.getAttribute('data-tipo-novedades');
    
    // Si es un modal de recibo, usar la función específica
    if (tipoNovedades === 'recibo') {
        if (typeof saveNewNovedadRecibo === 'function') {
            saveNewNovedadRecibo();
            return;
        }
    }
    
    // Función original para pedidos normales
    if (!currentOrderId) {

        return;
    }

    const nuevaNovedadTextarea = document.getElementById('nuevaNovedadTextarea');
    const novedad = nuevaNovedadTextarea.value.trim();
    const btnSaveNew = document.querySelector('.btn-save-new');

    if (!novedad) {
        showNotification(' Ingresa una novedad antes de guardar', 'warning');
        return;
    }

    if (novedad.length > 500) {
        showNotification(' La novedad no puede exceder 500 caracteres', 'error');
        return;
    }

    try {
        btnSaveNew.disabled = true;
        btnSaveNew.classList.add('loading');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Enviar solicitud AJAX para agregar nueva novedad
        const response = await fetch(`/api/ordenes/${currentOrderId}/novedades/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                novedad: novedad
            })
        });

        const data = await response.json();

        if (!response.ok) {

            throw new Error(data.message || `Error ${response.status}`);
        }


        showNotification(' Novedad agregada correctamente', 'success');

        // Actualizar textarea con las nuevas novedades
        document.getElementById('novedadesTextarea').value = data.data.novedades;

        // Actualizar la fila en la tabla
        updateRowNovedades(currentOrderId, data.data.novedades);

        // Limpiar input
        cancelNewNovedad();

    } catch (error) {

        showNotification(` Error: ${error.message}`, 'error');
    } finally {
        btnSaveNew.disabled = false;
        btnSaveNew.classList.remove('loading');
    }
}

/**
 * Guarda las novedades editadas
 */
async function saveEditedNovedades() {
    const modal = document.getElementById('novedadesEditModal');
    const tipoNovedades = modal.getAttribute('data-tipo-novedades');
    
    // Si es un modal de recibo, usar la función específica
    if (tipoNovedades === 'recibo') {
        if (typeof saveEditedNovedadesRecibo === 'function') {
            saveEditedNovedadesRecibo();
            return;
        }
    }
    
    // Función original para pedidos normales
    if (!currentOrderId) {

        return;
    }

    const textarea = document.getElementById('novedadesTextarea');
    const novedades = textarea.value.trim();
    const btnSaveEdit = document.getElementById('btnSaveEdit');

    try {
        btnSaveEdit.disabled = true;
        btnSaveEdit.classList.add('loading');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');


        // Enviar solicitud AJAX para reemplazar novedades
        const response = await fetch(`/api/ordenes/${currentOrderId}/novedades`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                novedades: novedades
            })
        });

        const data = await response.json();

        if (!response.ok) {

            throw new Error(data.message || `Error ${response.status}`);
        }


        showNotification(' Novedades actualizadas correctamente', 'success');

        // Actualizar la fila en la tabla
        updateRowNovedades(currentOrderId, novedades);

        // Salir de modo edición
        setTimeout(() => {
            toggleEditMode();
        }, 500);

    } catch (error) {

        showNotification(` Error: ${error.message}`, 'error');
    } finally {
        btnSaveEdit.disabled = false;
        btnSaveEdit.classList.remove('loading');
    }
}

/**
 * Actualiza la celda de novedades en la tabla
 * @param {string} ordenId - ID del pedido
 * @param {string} novedades - Contenido nuevo de novedades
 */
function updateRowNovedades(ordenId, novedades) {
    const row = document.querySelector(`[data-orden-id="${ordenId}"]`);
    if (!row) return;

    const btnEdit = row.querySelector('.btn-edit-novedades');
    if (btnEdit) {
        btnEdit.setAttribute('data-full-novedades', novedades || '');
        
        const textSpan = btnEdit.querySelector('.novedades-text');
        if (textSpan) {
            if (novedades) {
                textSpan.textContent = novedades.length > 50 ? novedades.substring(0, 50) + '...' : novedades;
                textSpan.classList.remove('empty');
            } else {
                textSpan.textContent = 'Sin novedades';
                textSpan.classList.add('empty');
            }
        }
    }
}

/**
 * Muestra una notificación al usuario
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de notificación (success, error, info, warning)
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <p>${message}</p>
        </div>
    `;

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        max-width: 400px;
        padding: 16px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        z-index: 10002;
        animation: slideInRight 0.3s ease-out;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        color: white;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

/**
 * Event Listeners para el modal
 */
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('novedadesTextarea');
    const nuevaNovedadTextarea = document.getElementById('nuevaNovedadTextarea');
    const modal = document.getElementById('novedadesEditModal');

    // Actualizar contador para nueva novedad mientras escribe
    if (nuevaNovedadTextarea) {
        nuevaNovedadTextarea.addEventListener('input', function() {
            document.getElementById('newCharCount').textContent = this.value.length;
        });

        // Permitir Ctrl+Enter para guardar nueva novedad
        nuevaNovedadTextarea.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                saveNewNovedad();
            }
        });
    }

    // Cerrar modal con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
            closeNovedadesModal();
        }
    });

    // Cerrar modal al hacer clic fuera
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeNovedadesModal();
            }
        });
    }
});

/**
 * Funciones específicas para Novedades de Recibos
 */

/**
 * Guardar novedades de recibo usando la nueva API
 */
async function saveNovedadesRecibo(pedidoId, numeroRecibo, novedadesTexto) {
    try {
        console.log(`[saveNovedadesRecibo] 📝 Guardando novedades para pedido: ${pedidoId}, recibo: ${numeroRecibo}`);
        
        const response = await fetch(`/recibos-novedades/${pedidoId}/${numeroRecibo}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                novedades: novedadesTexto,
                tipo_novedad: 'observacion', // Por defecto, se puede cambiar después
                prendas_ids: [] // Aplica a todas las prendas del pedido
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[saveNovedadesRecibo] ✅ Novedades guardadas:', result);
            showNotification('Novedades guardadas correctamente', 'success');
            
            // Recargar la página para mostrar los cambios
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            console.error('[saveNovedadesRecibo] ❌ Error:', result.message);
            showNotification('Error al guardar novedades: ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('[saveNovedadesRecibo] ❌ Error de red:', error);
        showNotification('Error de conexión al guardar novedades', 'error');
    }
}

/**
 * Modificar el modal existente para soportar novedades de recibo
 */
function setupNovedadesReciboModal() {
    const modal = document.getElementById('novedadesModal') || document.getElementById('novedadesEditModal');
    if (!modal) return;
    
    // Verificar si ya está configurado para recibos
    if (modal.hasAttribute('data-recibo-setup')) return;
    
    modal.setAttribute('data-recibo-setup', 'true');
    
    // Agregar botón de guardar específico para recibos si no existe
    const saveButton = document.createElement('button');
    saveButton.id = 'btnSaveReciboNovedades';
    saveButton.className = 'btn btn-primary';
    saveButton.textContent = 'Guardar Novedades de Recibo';
    saveButton.style.display = 'none'; // Se muestra solo cuando es modal de recibo
    saveButton.onclick = function() {
        const pedidoId = modal.getAttribute('data-pedido-id');
        const numeroRecibo = modal.getAttribute('data-numero-recibo');
        const textarea = modal.querySelector('#novedadesTexto') || modal.querySelector('#novedadesTextarea');
        
        if (pedidoId && numeroRecibo && textarea) {
            saveNovedadesRecibo(pedidoId, numeroRecibo, textarea.value);
        }
    };
    
    // Agregar el botón al footer del modal
    const footer = modal.querySelector('.modal-footer') || modal.querySelector('.flex.justify-end');
    if (footer) {
        footer.appendChild(saveButton);
    }
}

// Configurar el modal cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    setupNovedadesReciboModal();
});

