<!-- Modal Avanzado de Novedades -->
<div id="novedadesAdvancedModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9998 overflow-auto" style="z-index: 100001; display: none;">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full mx-4 my-8 max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">
                💬 Novedades - Pedido <span id="modalNovedadesNumeroPedido">-</span>
            </h2>
            <button onclick="cerrarModalNovedadesAdvanced()" class="text-white hover:text-slate-200 text-2xl leading-none transition-colors">
                ✕
            </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-6 overflow-y-auto" style="max-height: calc(90vh - 200px);">
            <!-- Lista de Novedades -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-md font-semibold text-slate-900">Historial de Novedades</h3>
                    <button onclick="abrirModalAgregarNovedad()" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                        <span class="material-symbols-rounded text-sm">add</span>
                        Agregar Novedad
                    </button>
                </div>
                
                <!-- Contenedor de novedades -->
                <div id="novedadesContainer" class="space-y-3">
                    <!-- Las novedades se cargarán aquí dinámicamente -->
                    <div class="flex justify-center items-center py-8">
                        <span class="text-slate-500"> Cargando novedades...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
            <button type="button" onclick="cerrarModalNovedadesAdvanced()" class="px-4 py-2 bg-slate-400 hover:bg-slate-500 text-white font-medium rounded-lg transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal para Agregar/Editar Novedad -->
<div id="novedadFormModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999 overflow-auto" style="z-index: 100002; display: none;">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 my-8">
        <!-- Header -->
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white" id="novedadFormTitle">Agregar Nueva Novedad</h2>
            <button onclick="cerrarModalNovedadForm()" class="text-white hover:text-slate-200 text-2xl leading-none transition-colors">
                ✕
            </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-6">
            <form id="novedadForm">
                <!-- Campo de texto para la novedad -->
                <div class="mb-4">
                    <label for="novedadTexto" class="block text-sm font-bold text-slate-900 mb-2">
                        Contenido de la Novedad:
                    </label>
                    <textarea 
                        id="novedadTexto" 
                        name="novedadTexto"
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-700 outline-none transition resize-none"
                        placeholder="Escribe el contenido de la novedad aquí..."
                        rows="5"
                        maxlength="500"
                        required
                    ></textarea>
                    <div class="mt-1 text-xs text-slate-500">
                        <span id="charCount">0</span>/500 caracteres
                    </div>
                </div>

                <!-- Campo oculto para el ID de la novedad (solo para edición) -->
                <input type="hidden" id="novedadId" name="novedadId">
            </form>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
            <button type="button" onclick="cerrarModalNovedadForm()" class="px-4 py-2 bg-slate-400 hover:bg-slate-500 text-white font-medium rounded-lg transition-colors">
                Cancelar
            </button>
            <button type="button" onclick="guardarNovedadForm()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg transition-colors">
                <span id="novedadFormButtonText">Guardar Novedad</span>
            </button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
/**
 * Muestra una notificación simple al usuario
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de notificación (success, error, info, warning)
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

    // Estilos inline para la notificación
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
        z-index: 100003;
        animation: slideInRight 0.3s ease-out;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        color: white;
    `;

    // Agregar animación CSS si no existe
    if (!document.querySelector('#notification-animations')) {
        const style = document.createElement('style');
        style.id = 'notification-animations';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    // Agregar al DOM
    document.body.appendChild(notification);

    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}

/**
 * Actualiza la celda de novedades en la tabla principal
 * @param {string} pedidoId - ID del pedido
 * @param {string} novedades - Contenido nuevo de novedades
 */
function updateRowNovedades(pedidoId, novedades) {
    // Buscar la fila en la tabla principal
    const row = document.querySelector(`[data-orden-id="${pedidoId}"]`);
    if (!row) return;

    // Buscar el botón de novedades
    const btnEdit = row.querySelector('.btn-edit-novedades');
    if (btnEdit) {
        // Actualizar el atributo data-full-novedades
        btnEdit.setAttribute('data-full-novedades', novedades || '');
        
        // Actualizar el texto visible
        const textSpan = btnEdit.querySelector('.novedades-text');
        if (textSpan) {
            if (novedades && novedades.trim() !== '') {
                // Mostrar las primeras 50 caracteres
                const preview = novedades.length > 50 ? novedades.substring(0, 50) + '...' : novedades;
                textSpan.textContent = preview;
                textSpan.classList.remove('empty');
            } else {
                textSpan.textContent = 'Sin novedades';
                textSpan.classList.add('empty');
            }
        }
    }
}

// Variables globales
let currentPedidoId = null;
let novedadesData = [];
let isEditingNovedad = false;

/**
 * Abre el modal avanzado de novedades
 * @param {string} pedidoId - ID del pedido
 */
function abrirModalNovedadesAdvanced(pedidoId) {
    currentPedidoId = pedidoId;
    const modal = document.getElementById('novedadesAdvancedModal');
    
    // Actualizar número de pedido
    document.getElementById('modalNovedadesNumeroPedido').textContent = pedidoId;
    
    // Mostrar modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Cargar novedades
    cargarNovedadesAdvanced(pedidoId);
}

/**
 * Cierra el modal avanzado de novedades
 */
function cerrarModalNovedadesAdvanced() {
    const modal = document.getElementById('novedadesAdvancedModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    currentPedidoId = null;
    novedadesData = [];
}

/**
 * Carga las novedades del pedido desde la base de datos
 */
async function cargarNovedadesAdvanced(pedidoId) {
    const container = document.getElementById('novedadesContainer');
    
    // Mostrar loading
    container.innerHTML = `
        <div class="flex justify-center items-center py-8">
            <span class="text-slate-500"> Cargando novedades...</span>
        </div>
    `;
    
    try {
        const response = await fetch(`/despacho/${pedidoId}/observaciones`);
        
        if (!response.ok) {
            throw new Error('Error al cargar novedades');
        }
        
        const data = await response.json();
        
        if (data.success && data.data && Array.isArray(data.data) && data.data.length > 0) {
            // Mapear observaciones al formato esperado por renderizarNovedades
            novedadesData = data.data.map(obs => ({
                id: obs.id,
                usuario: obs.usuario_nombre || 'Sin usuario',
                fechaHora: obs.created_at ? new Date(obs.created_at).toLocaleString() : '',
                texto: obs.contenido,
                source: obs.source,
            }));
            renderizarNovedades();
        } else {
            // No hay novedades
            container.innerHTML = `
                <div class="flex justify-center items-center py-8">
                    <div class="text-center">
                        <span class="text-slate-500 text-lg"> No hay novedades registradas</span>
                        <p class="text-slate-400 text-sm mt-2">Haz clic en "Agregar Novedad" para comenzar</p>
                    </div>
                </div>
            `;
        }
        
    } catch (error) {
        console.error('[cargarNovedadesAdvanced] Error:', error);
        
        // Mostrar error
        container.innerHTML = `
            <div class="flex justify-center items-center py-8">
                <div class="text-center">
                    <span class="text-red-500 text-lg"> Error al cargar novedades</span>
                    <p class="text-slate-400 text-sm mt-2">Por favor, intenta nuevamente</p>
                </div>
            </div>
        `;
    }
}

/**
 * Parsea el texto de novedades en un array de objetos
 * @param {string} novedadesTexto - Texto completo de novedades
 * @returns {Array} Array de objetos con id, usuario, fecha, texto
 */
function parsearNovedades(novedadesTexto) {
    const novedades = [];
    const lineas = novedadesTexto.split('\n\n').filter(linea => linea.trim() !== '');
    
    lineas.forEach((linea, index) => {
        // Intentar parsear formato [usuario - fecha hora] contenido
        const match = linea.match(/^\[([^\]]+)\]\s*(.+)$/);
        
        if (match) {
            novedades.push({
                id: `novedad-${index}`,
                usuario: match[1],
                fechaHora: match[1],
                texto: match[2].trim(),
                lineaCompleta: linea
            });
        } else {
            // Formato antiguo o sin prefijo
            novedades.push({
                id: `novedad-${index}`,
                usuario: 'Sistema',
                fechaHora: 'Sin fecha',
                texto: linea.trim(),
                lineaCompleta: linea
            });
        }
    });
    
    return novedades;
}

/**
 * Renderiza las novedades en el contenedor
 */
function renderizarNovedades() {
    const container = document.getElementById('novedadesContainer');
    
    if (novedadesData.length === 0) {
        container.innerHTML = `
            <div class="flex justify-center items-center py-8">
                <div class="text-center">
                    <span class="text-slate-500 text-lg"> No hay novedades registradas</span>
                    <p class="text-slate-400 text-sm mt-2">Haz clic en "Agregar Novedad" para comenzar</p>
                </div>
            </div>
        `;
        return;
    }
    
    const novedadesHTML = novedadesData.map((novedad, index) => `
        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow" data-novedad-id="${novedad.id}">
            <div class="flex justify-between items-start mb-2">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-blue-500 text-sm">person</span>
                    <span class="text-sm font-medium text-slate-700">${novedad.usuario}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500">${novedad.fechaHora}</span>
                    <div class="flex gap-1">
                        <button onclick="editarNovedad('${novedad.id}')" class="p-1 text-blue-500 hover:bg-blue-50 rounded transition-colors" title="Editar novedad">
                            <span class="material-symbols-rounded text-sm">edit</span>
                        </button>
                        <button onclick="eliminarNovedad('${novedad.id}')" class="p-1 text-red-500 hover:bg-red-50 rounded transition-colors" title="Eliminar novedad">
                            <span class="material-symbols-rounded text-sm">delete</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="text-sm text-slate-700 whitespace-pre-wrap pl-6">${novedad.texto}</div>
        </div>
    `).join('');
    
    container.innerHTML = novedadesHTML;
}

/**
 * Abre el modal para agregar una nueva novedad
 */
function abrirModalAgregarNovedad() {
    isEditingNovedad = false;
    
    // Limpiar formulario
    document.getElementById('novedadTexto').value = '';
    document.getElementById('novedadId').value = '';
    document.getElementById('charCount').textContent = '0';
    
    // Actualizar título
    document.getElementById('novedadFormTitle').textContent = 'Agregar Nueva Novedad';
    document.getElementById('novedadFormButtonText').textContent = 'Guardar Novedad';
    
    // Mostrar modal
    document.getElementById('novedadFormModal').style.display = 'flex';
    
    // Enfocar textarea
    setTimeout(() => {
        document.getElementById('novedadTexto').focus();
    }, 100);
}

/**
 * Abre el modal para editar una novedad existente
 * @param {string} novedadId - ID de la novedad a editar
 */
function editarNovedad(novedadId) {
    const novedad = novedadesData.find(n => n.id === novedadId);
    
    if (!novedad) {
        console.error('[editarNovedad] Novedad no encontrada:', novedadId);
        return;
    }
    
    isEditingNovedad = true;
    
    // Cargar datos en formulario
    document.getElementById('novedadTexto').value = novedad.texto;
    document.getElementById('novedadId').value = novedadId;
    document.getElementById('charCount').textContent = novedad.texto.length;
    
    // Actualizar título
    document.getElementById('novedadFormTitle').textContent = 'Editar Novedad';
    document.getElementById('novedadFormButtonText').textContent = 'Actualizar Novedad';
    
    // Mostrar modal
    document.getElementById('novedadFormModal').style.display = 'flex';
    
    // Enfocar textarea
    setTimeout(() => {
        document.getElementById('novedadTexto').focus();
        document.getElementById('novedadTexto').setSelectionRange(novedad.texto.length, novedad.texto.length);
    }, 100);
}

/**
 * Cierra el modal del formulario de novedades
 */
function cerrarModalNovedadForm() {
    document.getElementById('novedadFormModal').style.display = 'none';
    isEditingNovedad = false;
}

/**
 * Guarda el formulario de novedades (agregar o editar)
 */
async function guardarNovedadForm() {
    const texto = document.getElementById('novedadTexto').value.trim();
    const novedadId = document.getElementById('novedadId').value;
    
    if (!texto) {
        alert('Por favor, escribe el contenido de la novedad');
        return;
    }
    
    if (texto.length > 500) {
        alert('La novedad no puede exceder 500 caracteres');
        return;
    }
    
    try {
        if (isEditingNovedad) {
            // Editar novedad existente
            await actualizarNovedad(novedadId, texto);
        } else {
            // Agregar nueva novedad
            await agregarNuevaNovedad(texto);
        }
        
        // Cerrar modal y recargar
        cerrarModalNovedadForm();
        cargarNovedadesAdvanced(currentPedidoId);
        
    } catch (error) {
        console.error('[guardarNovedadForm] Error:', error);
        alert('Error al guardar la novedad: ' + error.message);
    }
}

/**
 * Agrega una nueva novedad al pedido
 */
async function agregarNuevaNovedad(texto) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    const response = await fetch(`/api/ordenes/${currentPedidoId}/novedades/add`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            novedad: texto
        })
    });
    
    const data = await response.json();
    
    if (!response.ok) {
        throw new Error(data.message || `Error ${response.status}`);
    }
    
    // Mostrar notificación de éxito
    showNotification(' Novedad agregada correctamente', 'success');
    
    // Actualizar la tabla principal
    updateRowNovedades(currentPedidoId, data.data.novedades);
}

/**
 * Actualiza una novedad existente
 */
async function actualizarNovedad(novedadId, nuevoTexto) {
    // Encontrar la novedad a editar
    const novedadIndex = novedadesData.findIndex(n => n.id === novedadId);
    
    if (novedadIndex === -1) {
        throw new Error('Novedad no encontrada');
    }
    
    // Actualizar el texto de la novedad
    novedadesData[novedadIndex].texto = nuevoTexto;
    
    // Reconstruir el texto completo de novedades
    const novedadesTexto = novedadesData.map(n => n.lineaCompleta).join('\n\n');
    
    // Enviar actualización completa
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    const response = await fetch(`/api/ordenes/${currentPedidoId}/novedades`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            novedades: novedadesTexto
        })
    });
    
    const data = await response.json();
    
    if (!response.ok) {
        throw new Error(data.message || `Error ${response.status}`);
    }
    
    // Mostrar notificación de éxito
    showNotification(' Novedad actualizada correctamente', 'success');
    
    // Actualizar la tabla principal
    updateRowNovedades(currentPedidoId, data.data.novedades);
}

/**
 * Elimina una novedad
 */
async function eliminarNovedad(novedadId) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta novedad?')) {
        return;
    }
    
    try {
        // Filtrar novedades para eliminar la seleccionada
        novedadesData = novedadesData.filter(n => n.id !== novedadId);
        
        // Reconstruir el texto completo
        const novedadesTexto = novedadesData.map(n => n.lineaCompleta).join('\n\n');
        
        // Enviar actualización
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch(`/api/ordenes/${currentPedidoId}/novedades`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                novedades: novedadesTexto
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || `Error ${response.status}`);
        }
        
        // Mostrar notificación y recargar
        showNotification(' Novedad eliminada correctamente', 'success');
        updateRowNovedades(currentPedidoId, data.data.novedades);
        renderizarNovedades();
        
    } catch (error) {
        console.error('[eliminarNovedad] Error:', error);
        alert('Error al eliminar la novedad: ' + error.message);
    }
}

/**
 * Actualiza el contador de caracteres
 */
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('novedadTexto');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
        
        // Permitir Ctrl+Enter para guardar
        textarea.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                guardarNovedadForm();
            }
        });
    }
    
    // Cerrar modales con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('novedadFormModal').style.display === 'flex') {
                cerrarModalNovedadForm();
            } else if (document.getElementById('novedadesAdvancedModal').style.display === 'flex') {
                cerrarModalNovedadesAdvanced();
            }
        }
    });
    
    // Cerrar modales al hacer clic fuera
    document.getElementById('novedadesAdvancedModal').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalNovedadesAdvanced();
        }
    });
    
    document.getElementById('novedadFormModal').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalNovedadForm();
        }
    });
});
</script>
