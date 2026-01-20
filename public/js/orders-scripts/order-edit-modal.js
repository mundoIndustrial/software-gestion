// order-edit-modal.js - Modal de edición de órdenes

let editPrendasCount = 0;
let currentEditOrderId = null;
let originalPrendas = []; // Para rastrear prendas originales
let prendaUniqueId = 0; // ID único para cada prenda

/**
 * Abrir el modal de edición y cargar los datos de la orden
 */
async function openEditModal(pedido) {
    try {
        currentEditOrderId = pedido;
        const modal = document.getElementById('orderEditModal');
        
        // Guardar el overflow original del body antes de modificarlo
        if (!globalThis.originalBodyOverflow) {
            globalThis.originalBodyOverflow = document.body.style.overflow || getComputedStyle(document.body).overflow || '';
        }
        
        // Mostrar modal con animación
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Cargar datos de la orden
        await loadOrderData(pedido);

    } catch (error) {
        console.error('Error al abrir modal de edición:', error);
        showEditNotification('Error al cargar la orden', 'error');
    }
}

/**
 * Cerrar el modal de edición
 */
function closeEditModal() {
    const modal = document.getElementById('orderEditModal');
    modal.style.display = 'none';
    
    // Restaurar el overflow original del body
    if (globalThis.originalBodyOverflow !== undefined) {
        document.body.style.overflow = globalThis.originalBodyOverflow;
        // Limpiar la variable para la próxima vez
        globalThis.originalBodyOverflow = undefined;
    } else {
        // Fallback: remover el estilo inline para que use el CSS por defecto
        document.body.style.overflow = '';
    }
    
    // Restaurar el botón guardar a su estado original
    const guardarBtn = document.getElementById('edit_guardarBtn');
    if (guardarBtn) {
        guardarBtn.disabled = false;
        guardarBtn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Guardar Cambios
        `;
    }
    
    // Ocultar notificación
    hideEditNotification();
    
    // Limpiar el formulario
    document.getElementById('orderEditForm').reset();
    document.getElementById('edit_prendasContainer').innerHTML = '';
    editPrendasCount = 0;
    currentEditOrderId = null;
    originalPrendas = [];
    prendaUniqueId = 0;
}

/**
 * Cargar datos de la orden desde el servidor
 */
async function loadOrderData(pedido) {
    try {
        showEditNotification('Cargando datos de la orden...', 'info');

        // Determinar el contexto (registros o bodega)
        const context = globalThis.modalContext || 'registros';
        const baseUrl = context === 'bodega' ? '/bodega' : '/registros';

        // Cargar datos de tabla_original o tabla_original_bodega
        const ordenResponse = await fetch(`${baseUrl}/${pedido}`);
        if (!ordenResponse.ok) throw new Error('Error al cargar orden');
        
        const ordenData = await ordenResponse.json();

        // Llenar campos del formulario
        document.getElementById('edit_pedido').value = ordenData.pedido;
        document.getElementById('editOrderNumber').textContent = `#${ordenData.pedido}`;
        document.getElementById('edit_estado').value = ordenData.estado || 'No iniciado';
        document.getElementById('edit_cliente').value = ordenData.cliente || '';
        document.getElementById('edit_fecha_creacion').value = ordenData.fecha_de_creacion_de_orden || '';
        document.getElementById('edit_encargado').value = ordenData.encargado_orden || '';
        document.getElementById('edit_asesora').value = ordenData.asesora || '';
        document.getElementById('edit_forma_pago').value = ordenData.forma_de_pago || '';

        // Cargar prendas desde registros_por_orden o registros_por_orden_bodega
        await loadPrendas(pedido);

        showEditNotification('Orden cargada correctamente', 'success');
        setTimeout(() => hideEditNotification(), 2000);

    } catch (error) {
        console.error('Error al cargar datos:', error);
        showEditNotification('Error al cargar los datos de la orden', 'error');
    }
}

/**
 * Cargar prendas y tallas desde registros_por_orden o registros_por_orden_bodega
 */
async function loadPrendas(pedido) {
    try {
        // Determinar el contexto (registros o bodega)
        const context = globalThis.modalContext || 'registros';
        const apiUrl = context === 'bodega' 
            ? `/api/registros-por-orden-bodega/${pedido}` 
            : `/api/registros-por-orden/${pedido}`;

        // Obtener registros por orden
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error('Error al cargar prendas');
        
        const registros = await response.json();

        // Agrupar por prenda + descripción (para diferenciar prendas con mismo nombre pero diferente descripción)
        const prendasMap = {};
        registros.forEach(registro => {
            // Crear clave única combinando nombre y descripción
            const prendaKey = `${registro.prenda}|||${registro.descripcion || ''}`;
            
            if (!prendasMap[prendaKey]) {
                prendasMap[prendaKey] = {
                    nombre: registro.prenda,
                    descripcion: registro.descripcion || '',
                    tallas: []
                };
            }
            prendasMap[prendaKey].tallas.push({
                talla: registro.talla,
                cantidad: registro.cantidad
            });
        });

        // Convertir a array
        const prendasArray = Object.values(prendasMap);
        originalPrendas = JSON.parse(JSON.stringify(prendasArray)); // Copia profunda

        // Renderizar prendas
        const container = document.getElementById('edit_prendasContainer');
        container.innerHTML = '';
        editPrendasCount = 0;

        prendasArray.forEach((prenda, index) => {
            addEditPrendaCard(prenda, index);
        });

    } catch (error) {
        console.error('Error al cargar prendas:', error);
        showEditNotification('Error al cargar las prendas', 'error');
    }
}

/**
 * Añadir tarjeta de prenda al contenedor
 */
function addEditPrendaCard(prendaData = null, index = null) {
    const container = document.getElementById('edit_prendasContainer');
    const uniqueId = prendaUniqueId++; // ID único para esta prenda
    
    const prendaCard = document.createElement('div');
    prendaCard.className = 'prenda-card';
    prendaCard.dataset.prendaId = uniqueId; // Usar ID único en lugar de índice
    prendaCard.dataset.originalName = prendaData ? prendaData.nombre : '';
    
    prendaCard.innerHTML = `
        <div class="prenda-header">
            <span class="prenda-number">Prenda ${container.children.length + 1}</span>
            <button type="button" class="btn-delete eliminar-prenda-btn" data-prenda-id="${uniqueId}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <div class="prenda-content">
            <div class="form-group">
                <label class="form-label-small">Nombre de la prenda</label>
                <input type="text" 
                       class="form-input-compact prenda-nombre-input" 
                       placeholder="Ej: POLO ROJA" 
                       value="${prendaData ? prendaData.nombre : ''}" 
                       required />
            </div>
            <div class="form-group">
                <label class="form-label-small">Descripción/Detalles</label>
                <textarea class="form-textarea prenda-descripcion-input" 
                          rows="3" 
                          placeholder="Ej: Pegar bolsillo en la parte frontal">${prendaData ? prendaData.descripcion : ''}</textarea>
            </div>
            <div class="tallas-section">
                <label class="form-label-small">Tallas y Cantidades</label>
                <div class="tallas-list" data-prenda-id="${uniqueId}">
                    ${prendaData && prendaData.tallas ? renderTallas(prendaData.tallas, uniqueId) : ''}
                </div>
                <button type="button" class="btn-add-talla" data-prenda-id="${uniqueId}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir talla
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(prendaCard);
    
    // Agregar event listeners
    attachPrendaEventListeners(prendaCard, uniqueId);
    
    editPrendasCount++;
    updatePrendaNumbers();
}

/**
 * Renderizar tallas existentes
 */
function renderTallas(tallas, prendaId) {
    return tallas.map((talla, tallaIndex) => `
        <div class="talla-item">
            <input type="text" 
                   class="talla-input" 
                   value="${talla.talla}" 
                   placeholder="Talla (ej: M)" 
                   required />
            <input type="number" 
                   class="cantidad-input" 
                   value="${talla.cantidad}" 
                   placeholder="Cantidad" 
                   min="1" 
                   required />
            <button type="button" class="eliminar-talla-btn">×</button>
        </div>
    `).join('');
}

/**
 * Añadir event listeners a una tarjeta de prenda
 */
function attachPrendaEventListeners(prendaCard, prendaId) {
    // Botón eliminar prenda
    const deleteBtn = prendaCard.querySelector('.eliminar-prenda-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            removeEditPrenda(prendaId);
        });
    }
    
    // Botón añadir talla
    const addTallaBtn = prendaCard.querySelector('.btn-add-talla');
    if (addTallaBtn) {
        addTallaBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addEditTalla(prendaId);
        });
    }
    
    // Botones eliminar talla existentes
    attachTallaDeleteListeners(prendaCard);
}

/**
 * Añadir event listeners a botones de eliminar talla
 */
function attachTallaDeleteListeners(prendaCard) {
    const deleteTallaBtns = prendaCard.querySelectorAll('.eliminar-talla-btn');
    deleteTallaBtns.forEach(btn => {
        // Remover listener anterior si existe
        btn.replaceWith(btn.cloneNode(true));
    });
    
    // Agregar nuevos listeners
    const newDeleteTallaBtns = prendaCard.querySelectorAll('.eliminar-talla-btn');
    newDeleteTallaBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            removeEditTalla(this);
        });
    });
}

/**
 * Añadir una nueva prenda vacía
 */
function addNewEditPrenda() {
    addEditPrendaCard(null, null);
}

/**
 * Añadir talla a una prenda
 */
function addEditTalla(prendaId) {
    const tallasList = document.querySelector(`.tallas-list[data-prenda-id="${prendaId}"]`);
    if (!tallasList) {
        console.error('No se encontró la lista de tallas para prenda ID:', prendaId);
        return;
    }
    
    const tallaDiv = document.createElement('div');
    tallaDiv.className = 'talla-item';
    tallaDiv.innerHTML = `
        <input type="text" 
               class="talla-input" 
               placeholder="Talla (ej: M)" 
               required />
        <input type="number" 
               class="cantidad-input" 
               placeholder="Cantidad" 
               min="1" 
               required />
        <button type="button" class="eliminar-talla-btn">×</button>
    `;
    
    tallasList.appendChild(tallaDiv);
    
    // Agregar event listener al botón de eliminar
    const deleteBtn = tallaDiv.querySelector('.eliminar-talla-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            removeEditTalla(this);
        });
    }
}

/**
 * Eliminar talla
 */
function removeEditTalla(button) {
    const tallaItem = button.closest('.talla-item');
    if (tallaItem) {
        tallaItem.remove();
    }
}

/**
 * Eliminar prenda
 */
function removeEditPrenda(prendaId) {
    const prendaCard = document.querySelector(`.prenda-card[data-prenda-id="${prendaId}"]`);
    if (prendaCard) {
        // Animación de salida
        prendaCard.style.opacity = '0';
        prendaCard.style.transform = 'scale(0.95)';
        prendaCard.style.transition = 'all 0.2s ease';
        
        setTimeout(() => {
            prendaCard.remove();
            updatePrendaNumbers();
        }, 200);
    } else {
        console.error('No se encontró la prenda con ID:', prendaId);
    }
}

/**
 * Actualizar números de prendas
 */
function updatePrendaNumbers() {
    const prendaCards = document.querySelectorAll('#edit_prendasContainer .prenda-card');
    prendaCards.forEach((card, index) => {
        const numberSpan = card.querySelector('.prenda-number');
        if (numberSpan) {
            numberSpan.textContent = `Prenda ${index + 1}`;
        }
    });
}

/**
 * Mostrar notificación
 */
function showEditNotification(message, type = 'success') {
    const notification = document.getElementById('editNotification');
    if (!notification) {
        console.warn(' Elemento #editNotification no encontrado');
        return;
    }
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';
}

/**
 * Ocultar notificación
 */
function hideEditNotification() {
    const notification = document.getElementById('editNotification');
    if (notification) {
        notification.style.display = 'none';
    }
}

/**
 * Guardar cambios de la orden
 */
async function saveEditOrder(event) {
    event.preventDefault();
    
    try {
        const guardarBtn = document.getElementById('edit_guardarBtn');
        guardarBtn.disabled = true;
        guardarBtn.innerHTML = `
            <svg class="animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10" stroke-width="2" stroke-dasharray="32" stroke-linecap="round"/>
            </svg>
            Guardando...
        `;

        // Recopilar datos del formulario
        const formData = collectEditFormData();

        // Validar datos
        if (!validateEditFormData(formData)) {
            throw new Error('Por favor complete todos los campos requeridos');
        }

        showEditNotification('Guardando cambios...', 'info');

        // Determinar el contexto (registros o bodega)
        const context = globalThis.modalContext || 'registros';
        const baseUrl = context === 'bodega' ? '/bodega' : '/registros';

        // Obtener CSRF token de forma segura
        const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
        if (!csrfTokenElement) {
            throw new Error('CSRF token no encontrado en el documento');
        }

        // Enviar al servidor
        const response = await fetch(`${baseUrl}/${currentEditOrderId}/edit-full`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfTokenElement.content
            },
            body: JSON.stringify(formData)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Error al guardar cambios');
        }

        const result = await response.json();

        showEditNotification('¡Cambios guardados correctamente!', 'success');
        
        // Actualizar la tabla en tiempo real sin recargar
        if (result.orden && globalThis.modernTableInstance && typeof globalThis.modernTableInstance.actualizarOrdenEnTabla === 'function') {
            globalThis.modernTableInstance.actualizarOrdenEnTabla(result.orden);
            console.log(' Orden actualizada en tiempo real desde el modal');
        }
        
        // Cerrar modal después de 1.5 segundos
        setTimeout(() => {
            closeEditModal();
        }, 1500);

    } catch (error) {
        console.error('Error al guardar:', error);
        showEditNotification(error.message || 'Error al guardar los cambios', 'error');
        
        // Restaurar botón
        const guardarBtn = document.getElementById('edit_guardarBtn');
        guardarBtn.disabled = false;
        guardarBtn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Guardar Cambios
        `;
    }
}

/**
 * Recopilar datos del formulario
 */
function collectEditFormData() {
    const getInputValue = (id) => {
        const element = document.getElementById(id);
        return element ? element.value : '';
    };

    const formData = {
        pedido: currentEditOrderId,
        estado: getInputValue('edit_estado'),
        cliente: getInputValue('edit_cliente'),
        fecha_creacion: getInputValue('edit_fecha_creacion'),
        encargado: getInputValue('edit_encargado'),
        asesora: getInputValue('edit_asesora'),
        forma_pago: getInputValue('edit_forma_pago'),
        prendas: []
    };

    // Recopilar prendas usando las nuevas clases
    const prendaCards = document.querySelectorAll('#edit_prendasContainer .prenda-card');
    prendaCards.forEach((card) => {
        const prendaNombre = card.querySelector('.prenda-nombre-input')?.value || '';
        const prendaDescripcion = card.querySelector('.prenda-descripcion-input')?.value || '';
        const originalName = card.dataset.originalName || '';
        
        const tallas = [];
        const tallaItems = card.querySelectorAll('.talla-item');
        
        tallaItems.forEach((item) => {
            const tallaInput = item.querySelector('.talla-input');
            const cantidadInput = item.querySelector('.cantidad-input');
            
            if (tallaInput && cantidadInput && tallaInput.value && cantidadInput.value) {
                tallas.push({
                    talla: tallaInput.value,
                    cantidad: parseInt(cantidadInput.value) || 0
                });
            }
        });

        if (prendaNombre && tallas.length > 0) {
            formData.prendas.push({
                prenda: prendaNombre,
                descripcion: prendaDescripcion,
                tallas: tallas,
                originalName: originalName // Para identificar si es una prenda existente
            });
        }
    });

    return formData;
}

/**
 * Validar datos del formulario
 */
function validateEditFormData(formData) {
    if (!formData || typeof formData !== 'object') {
        showEditNotification('Datos del formulario inválidos', 'error');
        return false;
    }

    if (!formData.cliente?.trim?.()) {
        showEditNotification('El campo Cliente es requerido', 'error');
        return false;
    }

    if (!formData.fecha_creacion?.trim?.()) {
        showEditNotification('La Fecha de Creación es requerida', 'error');
        return false;
    }

    if (!Array.isArray(formData.prendas) || formData.prendas.length === 0) {
        showEditNotification('Debe agregar al menos una prenda', 'error');
        return false;
    }

    for (let i = 0; i < formData.prendas.length; i++) {
        const prenda = formData.prendas[i];
        if (!prenda.prenda?.trim?.()) {
            showEditNotification(`La prenda ${i + 1} debe tener un nombre`, 'error');
            return false;
        }
        if (!Array.isArray(prenda.tallas) || prenda.tallas.length === 0) {
            showEditNotification(`La prenda ${i + 1} debe tener al menos una talla`, 'error');
            return false;
        }
    }

    return true;
}

// Inicializar event listeners cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeEventListeners);
} else {
    // DOM ya está listo
    initializeEventListeners();
}

function initializeEventListeners() {
    // Botón añadir prenda
    const añadirPrendaBtn = document.getElementById('edit_añadirPrendaBtn');
    if (añadirPrendaBtn) {
        añadirPrendaBtn.addEventListener('click', addNewEditPrenda);
    }

    // Formulario submit
    const form = document.getElementById('orderEditForm');
    if (form) {
        form.addEventListener('submit', saveEditOrder);
    }

    // Cerrar al hacer clic fuera del modal
    const modal = document.getElementById('orderEditModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeEditModal();
            }
        });
    }

    // Tecla ESC para cerrar
    const handleEscapeKey = (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('orderEditModal');
            if (modal && modal.style.display === 'flex') {
                closeEditModal();
            }
        }
    };
    document.addEventListener('keydown', handleEscapeKey);
}

// Añadir estilo de animación para el spinner
(function() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
    `;
    
    // Solo agregar si no existe ya
    if (!document.querySelector('style[data-animation="order-edit-spinner"]')) {
        style.setAttribute('data-animation', 'order-edit-spinner');
        const head = document.head || document.documentElement;
        if (head) {
            head.appendChild(style);
        }
    }
})();
