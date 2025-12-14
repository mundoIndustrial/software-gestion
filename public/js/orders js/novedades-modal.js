/**
 * Gestor del Modal de Novedades
 * Permite editar novedades de pedidos directamente desde la tabla
 * 
 * NOTA: Usa currentOrderId declarado globalmente en order-navigation.js
 */

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

    // 🆕 Intentar obtener el valor actualizado del DOM primero
    let novedadesValue = novedadesActual || '';
    const row = document.querySelector(`[data-orden-id="${ordenId}"]`);
    if (row) {
        const btnEdit = row.querySelector('.btn-edit-novedades');
        if (btnEdit) {
            const textSpan = btnEdit.querySelector('.novedades-text');
            if (textSpan && !textSpan.classList.contains('empty')) {
                // Si no está vacío, obtener el atributo data-full-text o el textContent
                const fullText = btnEdit.getAttribute('data-full-novedades') || textSpan.getAttribute('title');
                if (fullText) {
                    novedadesValue = fullText;
                }
            }
        }
    }

    // Establecer contenido
    textarea.value = novedadesValue;
    orderNumber.textContent = `Pedido: ${ordenId}`;

    // Actualizar contador de caracteres
    updateCharCount();

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
}

/**
 * Actualiza el contador de caracteres
 */
function updateCharCount() {
    const textarea = document.getElementById('novedadesTextarea');
    const charCount = document.getElementById('charCount');
    charCount.textContent = textarea.value.length;
}

/**
 * Guarda las novedades
 */
async function saveNovedades() {
    if (!currentOrderId) {
        console.error('No se especificó el ID de la orden');
        return;
    }

    const textarea = document.getElementById('novedadesTextarea');
    const novedades = textarea.value.trim();
    const btnSave = document.querySelector('.novedades-modal-footer .btn-save');

    // Validación básica
    if (novedades.length > 1000) {
        showNotification('❌ Las novedades no pueden exceder 1000 caracteres', 'error');
        return;
    }

    try {
        // Desabilitar botón
        btnSave.disabled = true;
        btnSave.classList.add('loading');

        // Obtener CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('🔐 CSRF Token:', csrfToken ? 'Presente' : 'FALTANTE');
        console.log('🔄 Enviando novedades:', { ordenId: currentOrderId, novedades: novedades });

        // Enviar solicitud AJAX
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

        console.log('📨 Status de respuesta:', response.status);
        const data = await response.json();
        console.log('📨 Respuesta del servidor:', { status: response.status, data: data });

        if (!response.ok) {
            console.error('❌ Respuesta no OK:', data);
            throw new Error(data.message || `Error ${response.status}: ${JSON.stringify(data)}`);
        }

        console.log('✅ Guardado exitoso');

        // Mostrar notificación de éxito
        showNotification('✅ Novedades guardadas correctamente', 'success');

        // Actualizar la fila en la tabla
        updateRowNovedades(currentOrderId, novedades);

        // Cerrar modal
        setTimeout(() => {
            closeNovedadesModal();
        }, 500);

    } catch (error) {
        console.error('❌ Error completo:', error);
        showNotification(`❌ Error: ${error.message}`, 'error');
    } finally {
        // Rehabilitar botón
        btnSave.disabled = false;
        btnSave.classList.remove('loading');
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

    const novedadesCell = row.querySelector('[data-novedades-cell]');
    if (!novedadesCell) {
        // Buscar el botón de edición de novedades
        const btnEdit = row.querySelector('.btn-edit-novedades');
        if (btnEdit) {
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
        return;
    }

    novedadesCell.textContent = novedades || '-';
}

/**
 * Muestra una notificación al usuario
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de notificación (success, error, info)
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <p>${message}</p>
        </div>
    `;

    // Estilos básicos si no están definidos en CSS
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
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
    `;

    document.body.appendChild(notification);

    // Remover después de 3 segundos
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
    const modal = document.getElementById('novedadesEditModal');

    // Actualizar contador mientras escribe
    if (textarea) {
        textarea.addEventListener('input', updateCharCount);

        // Permitir Ctrl+Enter para guardar
        textarea.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                saveNovedades();
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
