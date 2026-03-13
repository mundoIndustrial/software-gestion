/**
 * FASE 6 + FASE 6c: Handlers de index.blade.php
 * 
 * 🏗️  REFACTORIZADO CON ARQUITECTURA DDD
 * Ahora usa window.insumoService (inyectado por bootstrap.js)
 * 
 * Funciones inline extraídas del blade:
 * - abrirModalInsumos()        [REFACTORIZADO - usa InsumoService]
 * - cerrarModalInsumos()
 * - llenarTablaInsumos()
 * - crearFilaMaterial()
 * - agregarMaterialModal()
 * - agregarMaterialATabla()
 * - mostrarBotonesAnchoMetraje()
 * - actualizarDiasDemora()
 * - generarInputsPorPiezaStub()
 * - agregarFilaPieza()
 * - cambiarModoAnchoMetrajeStub()
 */

/**
 * Abre el modal de insumos para una orden y prenda específica
 * 
 * REFACTORIZADO: Usa window.insumoService en lugar de fetch directo
 * - Lógica de validación centralizada en InsumoService
 * - Cache automático (30 minutos)
 * - Retry logic automático (3 intentos)
 * - Errores tipados (ValidationError, BusinessError, HttpError)
 */
window.abrirModalInsumos = async function(pedido, prendaId) {
    try {
        // Mostrar el modal en estado de carga
        const modal = document.getElementById('insumosModal');
        modal.style.display = 'flex';
        modal.setAttribute('data-loading', 'true');
        
        // Remover aria-hidden del contenido principal para evitar conflictos
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.removeAttribute('aria-hidden');
        }

        // Establecer el pedido y prenda
        document.getElementById('modalPedido').textContent = pedido;
        document.getElementById('modalPrendaId').value = prendaId || '';
        document.getElementById('modalPrendaNombre').textContent = prendaId ? `Cargando...` : 'General';

        // Validar que el servicio está disponible
        if (!window.insumoService) {
            throw new Error('InsumoService no inicializado. Verifique que bootstrap.js se cargó correctamente.');
        }

        // Usar InsumoService para obtener insumos (con caché y reintentos automáticos)
        const insumos = await window.insumoService.obtenerInsumosDelPedido(pedido, prendaId);

        // Actualizar UI con datos obtenidos
        if (insumos.nombre_prenda) {
            document.getElementById('modalPrendaNombre').textContent = insumos.nombre_prenda;
        } else if (prendaId) {
            document.getElementById('modalPrendaNombre').textContent = `Prenda #${prendaId}`;
        }

        // Llenar tabla con materiales
        llenarTablaInsumos(insumos.materiales || []);

        // Mostrar botones según lógica de negocio
        if (insumos.requiereCierre) {
            const btnCerrar = document.getElementById('btnCerrarPedido');
            if (btnCerrar) {
                btnCerrar.style.display = 'inline-block';
            }
        }

        // Remover estado de carga
        modal.removeAttribute('data-loading');

    } catch (error) {
        // Manejar diferentes tipos de errores
        console.error('[abrirModalInsumos Error]', error);

        if (error instanceof ValidationError) {
            showToast(`Error de validación: ${error.message}`, 'error');
        } else if (error instanceof BusinessError) {
            showToast(`Error de operación: ${error.message}`, 'error');
        } else if (error instanceof HttpError) {
            console.error(`HTTP Error: ${error.status} ${error.statusText}`);
            showToast('Error al conectar con el servidor (reintentos completados). Intente nuevamente.', 'error');
        } else if (error instanceof RepositoryError) {
            console.error('Repository Error:', error.originalError);
            showToast('Error al acceder al caché. Recargue la página.', 'error');
        } else {
            showToast('Error desconocido al cargar insumos', 'error');
        }

        // Cerrar modal si hay error
        const modal = document.getElementById('insumosModal');
        if (modal) {
            modal.style.display = 'none';
            modal.removeAttribute('data-loading');
        }
    }
};

/**
 * Cierra el modal de insumos
 */
window.cerrarModalInsumos = function() {
    const modal = document.getElementById('insumosModal');
    modal.style.display = 'none';
    
    // Restaurar aria-hidden al contenido principal
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.setAttribute('aria-hidden', 'false');
    }
};

/**
 * Llena la tabla de insumos del modal
 */
window.llenarTablaInsumos = function(materiales) {
    const tbody = document.getElementById('insumosTableBody');
    tbody.innerHTML = '';

    const pedido = document.getElementById('modalPedido').textContent;
    
    // Mostrar SOLO los materiales que ya están guardados (sin mostrar estándar por defecto)
    materiales.forEach((materialData, index) => {
        crearFilaMaterial(materialData.nombre_material, materialData, index, pedido, tbody);
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
                onclick="abrirModalObservaciones('${materialId}', '${nombreMaterial}')"
                class="px-2 py-1 bg-blue-100 text-blue-600 font-medium rounded hover:bg-blue-200 transition text-sm flex items-center gap-1 justify-center"
                title="Ver/Editar observaciones"
            >
                <i class="fas fa-eye"></i>
            </button>
            <input type="hidden" id="observaciones_${materialId}" value="${materialData.observaciones ? materialData.observaciones.replace(/"/g, '&quot;') : ''}">
        </td>
        <td class="py-3 px-3 text-center">
            <button 
                onclick="eliminarFilaMaterial('${materialId}')"
                class="px-2 py-1 bg-red-100 text-red-600 font-medium rounded hover:bg-red-200 transition text-sm flex items-center gap-1 justify-center"
                title="Eliminar"
            >
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    `;

    tbody.appendChild(row);
}

/**
 * Mostrar modal para agregar nuevo material
 */
window.agregarMaterialModal = function() {
    const materialesEstandar = [
        'Tela', 
        'Reflectivo', 
        'Cierre', 
        'Cuello y puños',
        'Sesgo Relleno',
        'Sesgo Tela',
        'Sesgo en la misma Tela',
        'Hiladillo',
        'Citafalla',
        'Cordón'
    ];
    const tbody = document.getElementById('insumosTableBody');
    
    // Obtener materiales ya agregados
    const materialesAgregados = new Set();
    tbody.querySelectorAll('tr').forEach(fila => {
        const nombre = fila.querySelector('td:first-child span').textContent.trim();
        materialesAgregados.add(nombre);
    });
    
    // Filtrar materiales estándar que no estén agregados
    const materialesDisponibles = materialesEstandar.filter(m => !materialesAgregados.has(m));
    
    // Crear opciones HTML con datalist
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
        customClass: {
            container: 'swal-container-top',
            popup: 'swal-popup-top'
        },
        didOpen: () => {
            const inputElement = document.getElementById('materialInput');
            if (inputElement) {
                inputElement.focus();
            }
            
            // Asegurar z-index superior
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
 * Agregar material a la tabla
 */
window.agregarMaterialATabla = function(nombreMaterial) {
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
    
    // Marcar como fila nueva (no guardada en BD)
    row.setAttribute('data-nuevo', 'true');
    // Inicializar atributo data-observaciones vacío
    row.setAttribute('data-observaciones', '');

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
                data-original="false"
            >
        </td>
        <td class="py-3 px-3 text-center">
            <input 
                type="date" 
                id="fecha_orden_${materialId}"
                class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                data-original=""
            >
        </td>
        <td class="py-3 px-3 text-center">
            <input 
                type="date" 
                id="fecha_pedido_${materialId}"
                class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                data-original=""
            >
        </td>
        <td class="py-3 px-3 text-center">
            <input 
                type="date" 
                id="fecha_pago_${materialId}"
                class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 w-full"
                data-original=""
            >
        </td>
        <td class="py-3 px-3 text-center">
            <input 
                type="date" 
                id="fecha_llegada_${materialId}"
                class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 w-full"
                data-original=""
            >
        </td>
        <td class="py-3 px-3 text-center">
            <input 
                type="date" 
                id="fecha_despacho_${materialId}"
                class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 w-full"
                data-original=""
            >
        </td>
        <td class="py-3 px-3 text-center">
            <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">-</span>
        </td>
        <td class="py-3 px-3 text-center">
            <button 
                onclick="abrirModalObservaciones('${materialId}', '${nombreMaterial}')"
                class="px-2 py-1 bg-blue-100 text-blue-600 font-medium rounded hover:bg-blue-200 transition text-sm flex items-center gap-1 justify-center"
                title="Ver/Editar observaciones"
            >
                <i class="fas fa-eye"></i>
            </button>
        </td>
        <td class="py-3 px-3 text-center">
            <button 
                onclick="eliminarFilaMaterial('${materialId}')"
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
 * Mostrar/ocultar botón eliminar basado en si hay datos guardados
 */
window.mostrarBotonesAnchoMetraje = function() {
    const modal = document.getElementById('modalAnchoMetraje');
    const btnEliminar = document.getElementById('btnEliminarAnchoMetraje');
    
    if (modal.tieneDatosGuardados) {
        btnEliminar.classList.remove('hidden');
    } else {
        btnEliminar.classList.add('hidden');
    }
};

/**
 * Actualiza los días de demora en tiempo real
 * Delegado a calcularDemora() que usa API asincrónica
 */
window.actualizarDiasDemora = async function(fila) {
    const todosInputsFecha = fila.querySelectorAll('input[type="date"]');
    const diasSpan = fila.querySelector('span[class*="bg-"]');
    
    if (!diasSpan) {
        return;
    }
    
    // Si no hay fechas completas, mostrar "-"
    if (!todosInputsFecha[0]?.value || !todosInputsFecha[1]?.value) {
        diasSpan.textContent = '-';
        diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
        return;
    }
    
    // Obtener demora desde API
    const demora = await window.calcularDemoraAsync(todosInputsFecha[0].value, todosInputsFecha[1].value);
    diasSpan.textContent = demora.texto;
    diasSpan.className = `inline-block px-3 py-1 rounded-full text-sm font-semibold ${demora.clase_bg} ${demora.clase_text}`;
};

/**
 * Cierra el modal al hacer clic fuera de él
 */
document.addEventListener('DOMContentLoaded', function() {
    const insumosModal = document.getElementById('insumosModal');
    if (insumosModal) {
        insumosModal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalInsumos();
            }
        });
    }

    /**
     * Event listener para checkboxes de materiales en el modal
     */
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('material-checkbox')) {
            const checkbox = e.target;
            const materialId = checkbox.id.replace('checkbox_', '');
            if (typeof confirmarEliminacion === 'function') {
                confirmarEliminacion(checkbox, materialId);
            }
        }
        
        // Recalcular días de demora cuando cambian las fechas
        if (e.target.type === 'date') {
            const fila = e.target.closest('tr');
            if (fila) {
                actualizarDiasDemora(fila);
            }
        }
    });
});
