/**
 * Balanceo Module - Optimized JavaScript
 * Split into smaller, more manageable functions for better performance
 */

// State Management
export function createBalanceoState(balanceoId, initialData) {
    return {
        balanceoId,
        operaciones: initialData.operaciones || [],
        editingCell: null,
        parametros: initialData.parametros || {
            total_operarios: 0,
            turnos: 1,
            horas_por_turno: 8
        },
        metricas: initialData.metricas || {},
        showAddModal: false,
        editingOperacion: null,
        pendingOperaciones: [],
        formData: createEmptyFormData()
    };
}

function createEmptyFormData() {
    return {
        letra: '',
        operacion: '',
        precedencia: '',
        maquina: '',
        sam: '',
        operario: '',
        op: '',
        seccion: 'DEL',
        operario_a: '',
        orden: 0
    };
}

// UI Helpers
export function getSectionColor(seccion) {
    const colors = {
        'DEL': '#667eea',
        'TRAS': '#f5576c',
        'ENS': '#43e97b',
        'OTRO': '#999'
    };
    return colors[seccion] || '#999';
}

export function showSuccessMessage(message, duration = 1500) {
    const successMsg = document.createElement('div');
    successMsg.textContent = message;
    successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 8px 16px; border-radius: 6px; z-index: 10000; box-shadow: 0 2px 4px rgba(0,0,0,0.2); font-size: 13px;';
    document.body.appendChild(successMsg);
    
    setTimeout(() => {
        successMsg.remove();
    }, duration);
}

// Clipboard Operations
export async function copyColumn(operaciones, columnName) {
    const values = operaciones.map(op => {
        const value = op[columnName];
        return value !== null && value !== undefined ? value : '-';
    });
    
    const text = values.join('\n');
    
    try {
        await navigator.clipboard.writeText(text);
        showSuccessMessage(`✓ Columna "${columnName}" copiada`, 2000);
    } catch (err) {
        console.error('Error al copiar:', err);
        alert('No se pudo copiar la columna');
    }
}

// Cell Editing
export function startEditingCell(operacion, field, event, context) {
    event.stopPropagation();
    context.editingCell = `${operacion.id}-${field}`;
    
    // Focus en el input después de que se muestre
    context.$nextTick(() => {
        const input = event.target.querySelector('input, select');
        if (input) {
            input.focus();
            if (input.tagName === 'INPUT' && input.type === 'text') {
                input.select();
            }
        }
    });
}

export async function saveCell(operacion, field, newValue, context) {
    // Si el valor no cambió, solo cancelar
    if (operacion[field] == newValue) {
        context.editingCell = null;
        return;
    }

    try {
        const response = await fetch(`/balanceo/operacion/${operacion.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                [field]: newValue || null
            })
        });

        const data = await response.json();
        
        if (data.success) {
            operacion[field] = newValue;
            
            if (field === 'sam') {
                updateMetricas(data.balanceo, context);
            }
            
            showSuccessMessage('✓ Guardado');
        } else {
            alert('Error al guardar: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error saving cell:', error);
        alert('Error al guardar el cambio');
    } finally {
        context.editingCell = null;
    }
}

// Parameters Update
export async function updateParametros(balanceoId, parametros, context) {
    try {
        const response = await fetch(`/balanceo/${balanceoId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(parametros)
        });
        
        const data = await response.json();
        if (data.success) {
            updateMetricas(data.balanceo, context);
        }
    } catch (error) {
        console.error('Error updating parameters:', error);
    }
}

export function updateMetricas(balanceo, context) {
    context.metricas = {
        sam_total: balanceo.sam_total,
        meta_teorica: balanceo.meta_teorica,
        meta_real: balanceo.meta_real,
        meta_sugerida_85: balanceo.meta_sugerida_85,
        tiempo_disponible_horas: balanceo.tiempo_disponible_horas,
        tiempo_disponible_segundos: balanceo.tiempo_disponible_segundos,
        operario_cuello_botella: balanceo.operario_cuello_botella,
        tiempo_cuello_botella: balanceo.tiempo_cuello_botella,
        sam_real: balanceo.sam_real
    };
}

// Operation CRUD
export async function saveOperacion(context, keepOpen = false) {
    try {
        const url = context.editingOperacion 
            ? `/balanceo/operacion/${context.editingOperacion}`
            : `/balanceo/${context.balanceoId}/operacion`;
        
        const method = context.editingOperacion ? 'PATCH' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(context.formData)
        });
        
        const data = await response.json();
        if (data.success) {
            if (context.editingOperacion) {
                const index = context.operaciones.findIndex(op => op.id === context.editingOperacion);
                context.operaciones[index] = data.operacion;
            } else {
                context.operaciones.push(data.operacion);
            }
            
            updateMetricas(data.balanceo, context);
            
            if (keepOpen && !context.editingOperacion) {
                resetForm(context);
                showSuccessMessage('✓ Operación guardada correctamente', 2000);
            } else {
                context.showAddModal = false;
                resetForm(context);
            }
        }
    } catch (error) {
        console.error('Error saving operation:', error);
    }
}

export async function deleteOperacion(id, context) {
    if (!confirm('¿Estás seguro de eliminar esta operación?')) return;
    
    try {
        const response = await fetch(`/balanceo/operacion/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        if (data.success) {
            context.operaciones = context.operaciones.filter(op => op.id !== id);
            updateMetricas(data.balanceo, context);
        }
    } catch (error) {
        console.error('Error deleting operation:', error);
    }
}

export function resetForm(context) {
    context.formData = {
        letra: '',
        operacion: '',
        precedencia: '',
        maquina: '',
        sam: '',
        operario: '',
        op: '',
        seccion: 'DEL',
        operario_a: '',
        orden: context.operaciones.length
    };
    context.editingOperacion = null;
}

// Batch Operations
export function addOperacionToList(context) {
    if (!context.formData.letra || !context.formData.sam || !context.formData.operacion || !context.formData.seccion) {
        alert('Por favor completa los campos requeridos: Letra, SAM, Operación y Sección');
        return;
    }

    context.pendingOperaciones.push({
        letra: context.formData.letra,
        operacion: context.formData.operacion,
        precedencia: context.formData.precedencia,
        maquina: context.formData.maquina,
        sam: parseFloat(context.formData.sam),
        operario: context.formData.operario,
        op: context.formData.op,
        seccion: context.formData.seccion,
        operario_a: context.formData.operario_a,
        orden: context.operaciones.length + context.pendingOperaciones.length
    });

    resetForm(context);
}

export async function saveAllOperaciones(context) {
    if (context.pendingOperaciones.length === 0) {
        alert('No hay operaciones pendientes para guardar');
        return;
    }

    try {
        let savedCount = 0;
        let failedCount = 0;

        for (const operacion of context.pendingOperaciones) {
            try {
                const response = await fetch(`/balanceo/${context.balanceoId}/operacion`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(operacion)
                });

                const data = await response.json();
                if (data.success) {
                    context.operaciones.push(data.operacion);
                    updateMetricas(data.balanceo, context);
                    savedCount++;
                } else {
                    failedCount++;
                }
            } catch (error) {
                console.error('Error saving operation:', error);
                failedCount++;
            }
        }

        if (savedCount > 0) {
            showSuccessMessage(
                `✓ ${savedCount} operación(es) guardada(s) correctamente${failedCount > 0 ? `. ${failedCount} fallaron.` : ''}`,
                3000
            );
        }

        context.pendingOperaciones = [];
        context.showAddModal = false;
        resetForm(context);

    } catch (error) {
        console.error('Error saving operations:', error);
        alert('Error al guardar las operaciones');
    }
}

// Lazy load AlpineJS only when needed
export function initBalanceoModule() {
    if (document.querySelector('[x-data*="balanceoApp"]')) {
        // AlpineJS is already loaded via Vite
        console.log('Balanceo module initialized');
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBalanceoModule);
} else {
    initBalanceoModule();
}
