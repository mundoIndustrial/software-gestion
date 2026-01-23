/**
 * Gestor de Novedades para Bodega
 * Reutiliza el modal de novedades pero con endpoint específico para tabla_original_bodega
 */

let currentBodegaPedido = null;

/**
 * Abre el modal de novedades para bodega
 * @param {number} pedido - Número del pedido en bodega
 * @param {string} novedadesActual - Contenido actual de novedades (fallback)
 */
function openNovedadesBodegaModal(pedido, novedadesActual) {
    currentOrderId = pedido; // Reutiliza la variable global
    currentBodegaPedido = pedido;
    const modal = document.getElementById('novedadesEditModal');
    const textarea = document.getElementById('novedadesTextarea');
    const orderNumber = document.getElementById('novedadesOrderNumber');
    isEditMode = false;

    // Obtener el valor actualizado del DOM primero
    let novedadesValue = novedadesActual || '';
    const row = document.querySelector(`[data-order-id="${pedido}"]`);
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
    orderNumber.textContent = `Bodega - Pedido: ${pedido}`;

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
 * Sobrescribe saveNewNovedad para bodega
 */
const originalSaveNewNovedad = window.saveNewNovedad;
window.saveNewNovedad = async function() {
    if (!currentBodegaPedido) {

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

        // Enviar solicitud AJAX para bodega
        const response = await fetch(`/api/bodega/${currentBodegaPedido}/novedades/add`, {
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
        updateRowNovedadesBodega(currentBodegaPedido, data.data.novedades);

        // Limpiar input
        cancelNewNovedad();

    } catch (error) {

        showNotification(` Error: ${error.message}`, 'error');
    } finally {
        btnSaveNew.disabled = false;
        btnSaveNew.classList.remove('loading');
    }
};

/**
 * Sobrescribe saveEditedNovedades para bodega
 */
const originalSaveEditedNovedades = window.saveEditedNovedades;
window.saveEditedNovedades = async function() {
    if (!currentBodegaPedido) {

        return;
    }

    const textarea = document.getElementById('novedadesTextarea');
    const novedades = textarea.value.trim();
    const btnSaveEdit = document.getElementById('btnSaveEdit');

    try {
        btnSaveEdit.disabled = true;
        btnSaveEdit.classList.add('loading');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');


        // Enviar solicitud AJAX para bodega
        const response = await fetch(`/api/bodega/${currentBodegaPedido}/novedades`, {
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
        updateRowNovedadesBodega(currentBodegaPedido, novedades);

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
};

/**
 * Actualiza la celda de novedades en la tabla de bodega
 * @param {number} pedido - Número del pedido
 * @param {string} novedades - Contenido nuevo de novedades
 */
function updateRowNovedadesBodega(pedido, novedades) {
    const row = document.querySelector(`[data-order-id="${pedido}"]`);
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
