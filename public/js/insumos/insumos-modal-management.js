/**
 * Insumos Modal Management - FASE 4b
 * Maneja el modal de insumos, materiales y observaciones
 * 
 * Funciones extranas:
 * - abrirModalInsumos()
 * - cerrarModalInsumos()
 * - llenarTablaInsumos()
 * - crearFilaMaterial()
 * - agregarMaterialModal()
 * - agregarMaterialATabla()
 * - abrirModalObservaciones()
 * - cerrarModalObservaciones()
 * - guardarObservaciones()
 * - eliminarFilaMaterial()
 * - actualizarDiasDemora()
 */

function initInsumosModalManagement() {
    let currentObservacionesMaterialId = null;
    /**
     * Llena la tabla de insumos del modal
     */
    function llenarTablaInsumos(materiales) {
        const tbody = document.getElementById('insumosTableBody');
        if (!tbody) {
            console.error('[llenarTablaInsumos] Tabla body no encontrada');
            return;
        }
        
        tbody.innerHTML = '';

        const pedidoEl = document.getElementById('modalPedido');
        const pedido = pedidoEl ? pedidoEl.textContent : 'N/A';
        
        if (!Array.isArray(materiales)) {
            return;
        }
        
        materiales.forEach((materialData, index) => {
            if (typeof crearFilaMaterial === 'function') {
                crearFilaMaterial(materialData.nombre_material, materialData, index, pedido, tbody);
            }
        });
    };

    /**
     * Crea una fila de material en la tabla
     */
    function crearFilaMaterial(nombreMaterial, materialData, index, pedido, tbody) {
        const sanitizedMaterial = nombreMaterial.replace(/\s+/g, '_').toLowerCase();
        const materialId = `material_modal_${pedido}_${index}_${sanitizedMaterial}`;

        const row = document.createElement('tr');
        row.className = 'border-b border-gray-200 hover:bg-gray-50 transition';
        row.id = `row_${materialId}`;
        row.setAttribute('data-guardado', 'true');
        
        const colores = ['bg-green-500', 'bg-yellow-500', 'bg-gray-400'];
        const colorPunto = colores[index % 3];

        row.innerHTML = `
            <td class="py-3 px-4 font-medium text-gray-900 min-w-max">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full ${colorPunto}"></div>
                    <span>${nombreMaterial}</span>
                </div>
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="checkbox" 
                    id="checkbox_${materialId}"
                    class="w-5 h-5 cursor-pointer material-checkbox accent-green-500"
                    ${materialData.recibido ? 'checked' : ''}
                    data-original="${materialData.recibido ? 'true' : 'false'}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_orden_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                    value="${materialData.fecha_orden ? materialData.fecha_orden : ''}"
                    data-original="${materialData.fecha_orden ? materialData.fecha_orden : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_pedido_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                    value="${materialData.fecha_pedido ? materialData.fecha_pedido : ''}"
                    data-original="${materialData.fecha_pedido ? materialData.fecha_pedido : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_pago_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 w-full"
                    value="${materialData.fecha_pago ? materialData.fecha_pago : ''}"
                    data-original="${materialData.fecha_pago ? materialData.fecha_pago : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_despacho_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 w-full"
                    value="${materialData.fecha_despacho ? materialData.fecha_despacho : ''}"
                    data-original="${materialData.fecha_despacho ? materialData.fecha_despacho : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_llegada_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 w-full"
                    value="${materialData.fecha_llegada ? materialData.fecha_llegada : ''}"
                    data-original="${materialData.fecha_llegada ? materialData.fecha_llegada : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600 flex items-center justify-center gap-1">
                    ${materialData.dias_demora !== null && materialData.dias_demora !== undefined ? 
                        (materialData.dias_demora <= 0 ? '<i class="fas fa-check text-green-600"></i>' : 
                         materialData.dias_demora <= 5 ? '<i class="fas fa-exclamation-triangle text-yellow-600"></i>' : 
                         '<i class="fas fa-times text-red-600"></i>') + 
                        materialData.dias_demora + 'd' 
                        : '-'}
                </span>
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    data-insumos-action="material-open-observaciones"
                    data-material-id="${materialId}"
                    data-material-name="${nombreMaterial.replace(/"/g, '&quot;')}"
                    class="px-2 py-1 bg-blue-100 text-blue-600 font-medium rounded hover:bg-blue-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Ver/Editar observaciones"
                >
                    <i class="fas fa-eye"></i>
                </button>
                <input type="hidden" id="observaciones_${materialId}" value="${materialData.observaciones ? materialData.observaciones.replace(/"/g, '&quot;') : ''}">
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    data-insumos-action="material-delete-row"
                    data-material-id="${materialId}"
                    class="px-2 py-1 bg-red-100 text-red-600 font-medium rounded hover:bg-red-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Eliminar"
                >
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
    };

    /**
     * Muestra modal para agregar nuevo material
     */
    function agregarMaterialModal() {
        const materialesEstandar = [
            'Tela', 'Reflectivo', 'Cierre', 'Cuello y puño',
            'Sesgo Relleno', 'Sesgo Tela', 'Sesgo en la misma Tela',
            'Hiladillo', 'Citafalla', 'Cordon'
        ];
        const tbody = document.getElementById('insumosTableBody');
        
        const materialesAgregados = new Set();
        tbody.querySelectorAll('tr').forEach(fila => {
            const nombre = fila.querySelector('td:first-child span').textContent.trim();
            materialesAgregados.add(nombre);
        });
        
        const materialesDisponibles = materialesEstandar.filter(m => !materialesAgregados.has(m));
        
        const opcionesHTML = `
            <div style="text-align: left;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold;">Seleccionar o Escribir Insumo:</label>
                <input 
                    type="text" 
                    id="materialInput" 
                    list="materialesList"
                    placeholder="Selecciona o escribe un insumo..."
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;"
                    autocomplete="off"
                >
                <datalist id="materialesList">
                    ${materialesDisponibles.map(m => `<option value="${m}">`).join('')}
                </datalist>
            </div>
        `;
        
        Swal.fire({
            title: 'Agregar Insumo',
            html: opcionesHTML,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Agregar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                const inputElement = document.getElementById('materialInput');
                if (inputElement) {
                    inputElement.focus();
                }
                
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '10010';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const inputElement = document.getElementById('materialInput');
                const nombreMaterial = inputElement?.value.trim() || '';
                
                if (!nombreMaterial) {
                    showToast('Debes seleccionar o ingresar un material', 'warning');
                    return;
                }
                
                agregarMaterialATabla(nombreMaterial);
            }
        });
    };

    /**
     * añade un material a la tabla
     */
    function agregarMaterialATabla(nombreMaterial) {
        const tbody = document.getElementById('insumosTableBody');
        const pedido = document.getElementById('modalPedido').textContent;
        const index = tbody.children.length;
        const sanitizedMaterial = nombreMaterial.replace(/\s+/g, '_').toLowerCase();
        const materialId = `material_modal_${pedido}_${index}_${sanitizedMaterial}`;

        const colores = ['bg-green-500', 'bg-yellow-500', 'bg-gray-400'];
        const colorPunto = colores[index % 3];

        const row = document.createElement('tr');
        row.className = 'border-b border-gray-200 hover:bg-gray-50 transition';
        row.id = `row_${materialId}`;
        
        row.setAttribute('data-nuevo', 'true');
        row.setAttribute('data-observaciones', '');

        row.innerHTML = `
            <td class="py-3 px-4 font-medium text-gray-900 min-w-max">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full ${colorPunto}"></div>
                    <span>${nombreMaterial}</span>
                </div>
            </td>
            <td class="py-3 px-3 text-center">
                <input type="checkbox" id="checkbox_${materialId}" class="w-5 h-5 cursor-pointer material-checkbox accent-green-500" data-original="false">
            </td>
            <td class="py-3 px-3 text-center">
                <input type="date" id="fecha_orden_${materialId}" class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full" data-original="">
            </td>
            <td class="py-3 px-3 text-center">
                <input type="date" id="fecha_pedido_${materialId}" class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full" data-original="">
            </td>
            <td class="py-3 px-3 text-center">
                <input type="date" id="fecha_pago_${materialId}" class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 w-full" data-original="">
            </td>
            <td class="py-3 px-3 text-center">
                <input type="date" id="fecha_llegada_${materialId}" class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 w-full" data-original="">
            </td>
            <td class="py-3 px-3 text-center">
                <input type="date" id="fecha_despacho_${materialId}" class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 w-full" data-original="">
            </td>
            <td class="py-3 px-3 text-center">
                <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">-</span>
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    data-insumos-action="material-open-observaciones"
                    data-material-id="${materialId}"
                    data-material-name="${nombreMaterial.replace(/"/g, '&quot;')}"
                    class="px-2 py-1 bg-blue-100 text-blue-600 font-medium rounded hover:bg-blue-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Ver/Editar observaciones"
                >
                    <i class="fas fa-eye"></i>
                </button>
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    data-insumos-action="material-delete-row"
                    data-material-id="${materialId}"
                    class="px-2 py-1 bg-red-100 text-red-600 font-medium rounded hover:bg-red-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Eliminar"
                >
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
        showToast(`Material "${nombreMaterial}" agregado`, 'success');
    };

    /**
     * Abre modal de observaciones para un material
     */
    function abrirModalObservaciones(materialId, nombreMaterial) {
        const modal = document.getElementById('observacionesModal');
        const textArea = document.getElementById('observacionesTexto');
        const observacionesInput = document.getElementById(`observaciones_${materialId}`);
        
        document.getElementById('observacionesMaterial').textContent = nombreMaterial;
        textArea.value = observacionesInput ? observacionesInput.value : '';
        
        // Guardar referencia del material actual
        currentObservacionesMaterialId = materialId;
        
        modal.style.display = 'flex';
    };

    /**
     * Cierra modal de observaciones
     */
    function cerrarModalObservaciones() {
        const modal = document.getElementById('observacionesModal');
        modal.style.display = 'none';
        currentObservacionesMaterialId = null;
    };

    /**
     * Guarda observaciones del material en el backend
     */
    async function guardarObservaciones() {
        const textArea = document.getElementById('observacionesTexto');
        const materialId = currentObservacionesMaterialId;
        
        if (!materialId) {
            showToast('Error: No se identifica el material', 'error');
            return;
        }
        
        // Obtener el pedido del modal
        const numeroPedido = document.getElementById('modalPedido')?.textContent;
        const nombreMaterial = document.getElementById('observacionesMaterial')?.textContent;
        
        if (!numeroPedido || !nombreMaterial) {
            showToast('Error: Datos incompletos del material', 'error');
            return;
        }
        
        const observaciones = textArea.value;
        
        console.log('[guardarObservaciones] Iniciando guardado:', {
            numeroPedido: numeroPedido,
            nombreMaterial: nombreMaterial,
            observacionesLength: observaciones.length
        });
        
        try {
            // Enviar POST al backend
            const response = await fetch('/insumos/guardar-observaciones', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    numero_pedido: numeroPedido,
                    nombre_material: nombreMaterial,
                    observaciones: observaciones
                })
            });
            
            console.log('[guardarObservaciones] Response status:', response.status);
            console.log('[guardarObservaciones] Response headers:', {
                'content-type': response.headers.get('content-type')
            });
            
            // Si no es JSON, loguear el texto para debug
            const textContent = await response.text();
            if (!textContent) {
                showToast('Error: Respuesta vacia del servidor', 'error');
                return;
            }
            
            let data;
            try {
                data = JSON.parse(textContent);
            } catch (e) {
                console.error('[guardarObservaciones] Respuesta no es JSON:', textContent.substring(0, 200));
                showToast('Error: El servidor no respondio correctamente (Â¿rutas cacheadas?)', 'error');
                return;
            }
            
            if (data.success) {
                // Actualizar el input hidden con el nuevo valor
                const observacionesInput = document.getElementById(`observaciones_${materialId}`);
                if (observacionesInput) {
                    observacionesInput.value = observaciones;
                }
                
                showToast(' Observaciones guardadas exitosamente', 'success');
                cerrarModalObservaciones();
                
                console.log('[guardarObservaciones] Guardadas correctamente:', data);
            } else {
                showToast('Error: ' + (data.message || data.error || 'No se pudieron guardar las observaciones'), 'error');
                console.error('[guardarObservaciones] Error del servidor:', data);
            }
        } catch (error) {
            console.error('[guardarObservaciones] Error de red:', error);
            showToast('Error de conexion al guardar observaciones: ' + error.message, 'error');
        }
    };

    /**
     * Elimina una fila de material
     */
    function eliminarFilaMaterial(materialId) {
        const row = document.getElementById(`row_${materialId}`);
        if (row) {
            row.remove();
            showToast('Material eliminado', 'info');
        }
    };

    /**
     * Actualiza los dias de demora en tiempo real
     */
    async function actualizarDiasDemora(fila) {
        const todosInputsFecha = fila.querySelectorAll('input[type="date"]');
        const diasSpan = fila.querySelector('span[class*="bg-"]');
        
        if (!diasSpan) {
            return;
        }
        
        if (!todosInputsFecha[0]?.value || !todosInputsFecha[1]?.value) {
            diasSpan.textContent = '-';
            diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
            return;
        }
        
        if (typeof window.insumosHandlers?.utilities?.calcularDemoraAsync === 'function') {
            const demora = await window.insumosHandlers?.utilities?.calcularDemoraAsync(todosInputsFecha[0].value, todosInputsFecha[1].value);
            diasSpan.textContent = demora.texto;
            diasSpan.className = `inline-block px-3 py-1 rounded-full text-sm font-semibold ${demora.clase_bg} ${demora.clase_text}`;
        }
    };

    // Event listeners para checkboxes y cambios de fecha
    document.addEventListener('change', function(e) {
        
        if (e.target.type === 'date') {
            const fila = e.target.closest('tr');
            if (fila) {
                actualizarDiasDemora(fila);
            }
        }
    });


    // Cerrar modal al hacer clic fuera
    const insumosModal = document.getElementById('insumosModal');
    if (insumosModal) {
        insumosModal.addEventListener('click', function(e) {
            if (e.target === this) {
                (window.insumosHandlers?.modalHandlers?.cerrarModalInsumos || function(){})();
            }
        });
    }

    const observacionesModal = document.getElementById('observacionesModal');
    if (observacionesModal) {
        observacionesModal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalObservaciones();
            }
        });
    }

    window.insumosHandlers = window.insumosHandlers || {};
    window.insumosHandlers.insumosModalManagement = {
        llenarTablaInsumos,
        crearFilaMaterial,
        agregarMaterialModal,
        agregarMaterialATabla,
        abrirModalObservaciones,
        cerrarModalObservaciones,
        guardarObservaciones,
        eliminarFilaMaterial,
        actualizarDiasDemora,
    };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initInsumosModalManagement);
} else {
    initInsumosModalManagement();
}
