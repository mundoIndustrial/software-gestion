// bodega-edit-modal.js - Modal de edición de órdenes de bodega

let bodegaPrendasCount = 0;
let currentBodegaOrderId = null;
let bodegaOriginalPrendas = []; // Para rastrear prendas originales
let bodegaPrendaUniqueId = 0; // ID único para cada prenda

/**
 * Función wrapper para ser llamada desde onclick en la tabla
 */
function openEditModal(pedido) {
    console.log('🖱️ Click en botón Editar, pedido:', pedido);
    console.log('🔍 Verificando si openBodegaEditModal existe:', typeof openBodegaEditModal);
    if (typeof openBodegaEditModal === 'function') {
        openBodegaEditModal(pedido);
    } else {
        console.error('❌ openBodegaEditModal no es una función');
    }
}

/**
 * Inicializar modal de edición de bodega
 */
function initializeBodegaEditModal() {
    console.log('📦 Bodega Edit Modal Script Loaded');
    
    // Event delegation para botones de editar
    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('[data-action="edit-bodega"]');
        if (editBtn) {
            const pedido = editBtn.dataset.id;
            openBodegaEditModal(pedido);
        }
    });
    
    // Manejar envío de formulario
    const form = document.getElementById('bodegaEditForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            await saveBodegaChanges(currentBodegaOrderId);
        });
    }
}

/**
 * Abrir el modal de edición y cargar los datos de la orden
 */
async function openBodegaEditModal(pedido) {
    console.log(`✏️ Abriendo edición de bodega ${pedido}`);
    currentBodegaOrderId = pedido;
    
    try {
        // Verificar que el modal existe
        const modal = document.getElementById('bodegaEditModal');
        if (!modal) {
            console.error('❌ Modal #bodegaEditModal no encontrado en el DOM');
            console.log('📋 Elementos en el DOM:', document.querySelectorAll('[id*="Modal"]').length);
            showNotification('Error: Modal no encontrado', 'error');
            return;
        }
        console.log('✅ Modal encontrado en el DOM');
        console.log('📊 Computado style display:', window.getComputedStyle(modal).display);
        
        // Cargar datos de la orden
        console.log('📦 Cargando datos de la orden...');
        await loadBodegaOrderData(pedido);
        
        // Cargar prendas y tallas
        console.log('👕 Cargando prendas...');
        await loadBodegaPrendas(pedido);
        
        // Mostrar modal
        console.log('🎯 Mostrando modal...');
        showBodegaEditModal();
        
        // Verificar que se mostró
        setTimeout(() => {
            const computedStyle = window.getComputedStyle(modal);
            console.log('📊 Después de mostrar - display:', computedStyle.display);
            console.log('📊 Después de mostrar - visibility:', computedStyle.visibility);
            console.log('📊 Después de mostrar - opacity:', computedStyle.opacity);
            console.log('📊 Después de mostrar - z-index:', computedStyle.zIndex);
        }, 100);
        
        console.log('✅ Modal abierto exitosamente');
    } catch (error) {
        console.error('❌ Error abriendo modal:', error);
        showNotification('Error al cargar los datos: ' + error.message, 'error');
    }
}

/**
 * Cerrar el modal de edición
 */
function closeBodegaEditModal() {
    console.log('❌ Cerrando modal de edición');
    const modal = document.getElementById('bodegaEditModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
    }
    hideBodegaEditNotification();
}

/**
 * Cargar datos de la orden desde el servidor
 */
async function loadBodegaOrderData(pedido) {
    try {
        const response = await fetch(`/bodega/${pedido}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) throw new Error('Error al cargar datos');
        
        const orden = await response.json();
        console.log('📦 Orden completa recibida:', orden);
        console.log('📍 Área recibida:', orden.area);
        
        // Llenar información general
        document.getElementById('bodega_edit_pedido').value = orden.pedido || '';
        document.getElementById('bodega_edit_estado').value = orden.estado || 'No iniciado';
        document.getElementById('bodega_edit_area').value = orden.area || '';
        document.getElementById('bodega_edit_cliente').value = orden.cliente || '';
        document.getElementById('bodega_edit_cantidad').value = orden.cantidad || '';
        document.getElementById('bodega_edit_novedades').value = orden.novedades || '';
        document.getElementById('editBodegaOrderNumber').textContent = `#${orden.pedido}`;
        
        console.log('✅ Datos de orden cargados');
    } catch (error) {
        console.error('Error al cargar datos de orden:', error);
        throw error;
    }
}

/**
 * Cargar prendas y tallas desde registros_por_orden_bodega
 */
async function loadBodegaPrendas(pedido) {
    try {
        const response = await fetch(`/bodega/${pedido}/prendas`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            console.warn('⚠️ No se pudieron cargar las prendas');
            return;
        }
        
        const registros = await response.json();
        
        // Agrupar prendas por nombre
        const prendasMap = {};
        registros.forEach(registro => {
            const prendaKey = registro.prenda || 'Sin nombre';
            
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
        bodegaOriginalPrendas = JSON.parse(JSON.stringify(prendasArray));

        // Renderizar prendas
        const container = document.getElementById('edit_prendasContainer');
        container.innerHTML = '';
        bodegaPrendasCount = 0;

        prendasArray.forEach((prenda, index) => {
            addBodegaPrendaCard(prenda, index);
        });
        
        console.log(`✅ ${prendasArray.length} prendas cargadas`);
    } catch (error) {
        console.warn('⚠️ No se pudieron cargar las prendas:', error);
    }
}

/**
 * Añadir tarjeta de prenda al contenedor
 */
function addBodegaPrendaCard(prendaData = null, index = null) {
    const container = document.getElementById('edit_prendasContainer');
    const uniqueId = bodegaPrendaUniqueId++;
    
    const prendaCard = document.createElement('div');
    prendaCard.className = 'prenda-card';
    prendaCard.dataset.prendaId = uniqueId;
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
                       data-prenda-nombre="${uniqueId}"
                       placeholder="Ej: CAMISA DRILL BLANCA" 
                       value="${prendaData ? prendaData.nombre : ''}" 
                       required />
            </div>
            <div class="form-group">
                <label class="form-label-small">Descripción/Detalles</label>
                <textarea class="form-textarea prenda-descripcion-input" 
                          data-prenda-descripcion="${uniqueId}"
                          rows="3" 
                          placeholder="Ej: CAMISA DRILL MANGA LARGA, BOTONES PLÁSTICOS">${prendaData ? prendaData.descripcion : ''}</textarea>
            </div>
            <div class="tallas-section">
                <label class="form-label-small">Tallas y Cantidades</label>
                <div class="tallas-list" data-prenda-id="${uniqueId}">
                    ${prendaData && prendaData.tallas ? renderBodegaTallas(prendaData.tallas, uniqueId) : ''}
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
    attachBodegaPrendaEventListeners(prendaCard, uniqueId);
    bodegaPrendasCount++;
    updateBodegaPrendaNumbers();
}

/**
 * Renderizar tallas existentes
 */
function renderBodegaTallas(tallas, prendaId) {
    return tallas.map((talla, idx) => `
        <div class="talla-item">
            <input type="text" 
                   class="talla-input" 
                   data-prenda-talla="${prendaId}"
                   value="${talla.talla}" 
                   placeholder="Talla" 
                   required />
            <input type="number" 
                   class="cantidad-input" 
                   data-prenda-cantidad="${prendaId}"
                   value="${talla.cantidad}" 
                   placeholder="Cantidad" 
                   required />
            <button type="button" class="eliminar-talla-btn" data-talla-index="${idx}">×</button>
        </div>
    `).join('');
}

/**
 * Añadir event listeners a una tarjeta de prenda
 */
function attachBodegaPrendaEventListeners(prendaCard, prendaId) {
    const deleteBtn = prendaCard.querySelector('.eliminar-prenda-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            removeBodegaPrenda(prendaId);
        });
    }
    
    const addTallaBtn = prendaCard.querySelector('.btn-add-talla');
    if (addTallaBtn) {
        addTallaBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addBodegaTalla(prendaId);
        });
    }
    
    attachBodegaTallaDeleteListeners(prendaCard);
}

/**
 * Agregar event listeners para eliminar tallas
 */
function attachBodegaTallaDeleteListeners(prendaCard) {
    const deleteBtns = prendaCard.querySelectorAll('.eliminar-talla-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            this.closest('.talla-item').remove();
        });
    });
}

/**
 * Añadir nueva talla
 */
function addBodegaTalla(prendaId) {
    const prendaCard = document.querySelector(`[data-prenda-id="${prendaId}"]`);
    if (!prendaCard) return;
    
    const tallasList = prendaCard.querySelector('.tallas-list');
    const tallaItem = document.createElement('div');
    tallaItem.className = 'talla-item';
    tallaItem.innerHTML = `
        <input type="text" 
               class="talla-input" 
               data-prenda-talla="${prendaId}"
               placeholder="Talla" 
               required />
        <input type="number" 
               class="cantidad-input" 
               data-prenda-cantidad="${prendaId}"
               placeholder="Cantidad" 
               required />
        <button type="button" class="eliminar-talla-btn">×</button>
    `;
    
    tallasList.appendChild(tallaItem);
    
    tallaItem.querySelector('.eliminar-talla-btn').addEventListener('click', function(e) {
        e.preventDefault();
        tallaItem.remove();
    });
}

/**
 * Eliminar prenda
 */
function removeBodegaPrenda(prendaId) {
    const prendaCard = document.querySelector(`[data-prenda-id="${prendaId}"]`);
    if (prendaCard) {
        prendaCard.remove();
        updateBodegaPrendaNumbers();
    }
}

/**
 * Actualizar números de prendas
 */
function updateBodegaPrendaNumbers() {
    const prendaCards = document.querySelectorAll('#edit_prendasContainer .prenda-card');
    prendaCards.forEach((card, index) => {
        const numberSpan = card.querySelector('.prenda-number');
        if (numberSpan) {
            numberSpan.textContent = `Prenda ${index + 1}`;
        }
    });
}

/**
 * Mostrar modal
 */
function showBodegaEditModal() {
    const modal = document.getElementById('bodegaEditModal');
    if (modal) {
        console.log('🎯 Mostrando modal de edición');
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        // Forzar reflow para asegurar que se aplique el display
        const _ = modal.offsetHeight;
        console.log('✅ Modal visible');
    } else {
        console.error('❌ Modal #bodegaEditModal no encontrado en el DOM');
    }
}

/**
 * Guardar cambios
 */
async function saveBodegaChanges(pedido) {
    try {
        // Recopilar datos de prendas editadas
        const prendas = [];
        const prendaCards = document.querySelectorAll('[data-prenda-id]');
        prendaCards.forEach(card => {
            const prendaId = card.dataset.prendaId;
            const nombre = card.querySelector(`[data-prenda-nombre="${prendaId}"]`)?.value || '';
            const descripcion = card.querySelector(`[data-prenda-descripcion="${prendaId}"]`)?.value || '';
            
            // Recopilar tallas
            const tallas = [];
            const tallaInputs = card.querySelectorAll(`[data-prenda-talla="${prendaId}"]`);
            const cantidadInputs = card.querySelectorAll(`[data-prenda-cantidad="${prendaId}"]`);
            
            tallaInputs.forEach((tallaInput, index) => {
                const talla = tallaInput.value;
                const cantidad = cantidadInputs[index]?.value || '';
                if (talla) {
                    tallas.push({ talla, cantidad });
                }
            });
            
            prendas.push({
                prenda: nombre,
                descripcion: descripcion,
                tallas: tallas
            });
        });

        const formData = {
            pedido: pedido,
            estado: document.getElementById('bodega_edit_estado').value,
            area: document.getElementById('bodega_edit_area').value,
            cliente: document.getElementById('bodega_edit_cliente').value,
            cantidad: document.getElementById('bodega_edit_cantidad').value,
            novedades: document.getElementById('bodega_edit_novedades').value,
            prendas: prendas
        };
        
        console.log('💾 Guardando cambios:', formData);
        
        const response = await fetch(`/bodega/${pedido}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        if (!response.ok) {
            throw new Error('Error al guardar cambios');
        }
        
        console.log('✅ Cambios guardados');
        showBodegaEditNotification('Cambios guardados exitosamente', 'success');
        
        setTimeout(() => {
            closeBodegaEditModal();
            location.reload();
        }, 1500);
        
    } catch (error) {
        console.error('Error al guardar:', error);
        showBodegaEditNotification('Error al guardar los cambios', 'error');
    }
}

/**
 * Mostrar notificación
 */
function showBodegaEditNotification(message, type = 'success') {
    const notification = document.getElementById('editBodegaNotification');
    if (!notification) {
        console.warn('⚠️ Elemento #editBodegaNotification no encontrado');
        return;
    }
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';
}

/**
 * Ocultar notificación
 */
function hideBodegaEditNotification() {
    const notification = document.getElementById('editBodegaNotification');
    if (notification) {
        notification.style.display = 'none';
    }
}

/**
 * Mostrar notificación genérica
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff';
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10001;
        animation: slideIn 0.3s ease;
    `;
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Inicializar inmediatamente si el DOM ya está listo, o esperar si no
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ DOM Loaded - Inicializando Bodega Edit Modal');
        initializeBodegaEditModal();
    });
} else {
    // El DOM ya está completamente cargado
    console.log('✅ DOM Already Ready - Inicializando Bodega Edit Modal');
    initializeBodegaEditModal();
}

// Hacer funciones globales accesibles desde onclick
globalThis.openEditModal = openEditModal;
globalThis.closeBodegaEditModal = closeBodegaEditModal;
