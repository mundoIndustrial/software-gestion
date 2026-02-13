/**
 * ⚙️ Módulo de Procesos
 * Responsabilidad: Cargar y mostrar procesos (reflectivo, bordado, etc.)
 */

class PrendaEditorProcesos {
    /**
     * Cargar procesos en el modal
     */
    static cargar(prenda) {
        console.log('⚙️ [Procesos] Cargando:', {
            cantidad: prenda.procesos?.length || Object.keys(prenda.procesos || {}).length || 0,
            tipo: Array.isArray(prenda.procesos) ? 'array' : typeof prenda.procesos
        });
        
        // Buscar contenedor
        let container = document.getElementById('contenedor-tarjetas-procesos');
        if (!container) {
            container = document.getElementById('procesos-agregados');
        }
        if (!container) {
            container = document.querySelector('.procesos-container, [class*="procesos"]');
        }
        
        if (!container) {
            console.warn('❌ [Procesos] No encontrado contenedor');
            return;
        }
        
        // Convertir procesos a array
        const procesosArray = this._convertirAArray(prenda.procesos);
        
        if (!procesosArray || procesosArray.length === 0) {
            console.log('ℹ️ [Procesos] Sin procesos para cargar');
            container.innerHTML = '';
            container.style.display = 'none';
            return;
        }
        
        // Mostrar procesos
        container.innerHTML = '';
        container.style.display = 'block';
        
        procesosArray.forEach((proceso, idx) => {
            const tarjeta = this._crearTarjeta(proceso, idx);
            container.appendChild(tarjeta);
            console.log(`✅ [Procesos] ${idx + 1}: ${proceso.nombre}`);
        });
        
        // 🔥 Replicar a global para que sea editable
        if (prenda.procesos) {
            window.procesosSeleccionados = JSON.parse(JSON.stringify(prenda.procesos));
            console.log('[Carga] ⚙️ Procesos replicados en window.procesosSeleccionados');
        }
        
        console.log('✅ [Procesos] Completado');
    }

    /**
     * Convertir procesos a array
     * @private
     */
    static _convertirAArray(procesos) {
        // Si ya es un array, devolverlo
        if (Array.isArray(procesos)) {
            return procesos;
        }
        
        // Si es un objeto, convertirlo
        if (procesos && typeof procesos === 'object') {
            return Object.entries(procesos)
                .filter(([key, value]) => {
                    // Ignorar valores falsos
                    if (value === false || value === '' || value === null) return false;
                    return true;
                })
                .map(([nombre, detalles]) => {
                    // Si es un objeto con detalles, usarlo
                    if (typeof detalles === 'object') {
                        return { nombre, ...detalles };
                    }
                    // Si es un string/boolean, solo usar el nombre
                    return { nombre, tipo: nombre };
                });
        }
        
        return [];
    }

    /**
     * Crear tarjeta de proceso
     * @private
     */
    static _crearTarjeta(proceso, idx) {
        const tarjeta = document.createElement('div');
        tarjeta.className = 'proceso-tarjeta';
        tarjeta.style.cssText = 'background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;';
        
        const nombre = proceso.nombre || proceso.tipo || 'Proceso sin nombre';
        
        tarjeta.innerHTML = `
            <div>
                <strong>${nombre}</strong>
                ${proceso.detalles ? `<p style="color: #6b7280; margin: 0.5rem 0 0 0; font-size: 0.875rem;">${proceso.detalles}</p>` : ''}
            </div>
            <button type="button" class="btn btn-sm btn-danger" 
                onclick="eliminarProceso(${idx})"
                title="Eliminar proceso"
                style="flex-shrink: 0; margin-left: 1rem;">
                ✕
            </button>
        `;
        
        return tarjeta;
    }

    /**
     * Limpiar procesos
     */
    static limpiar() {
        const containers = [
            document.getElementById('contenedor-tarjetas-procesos'),
            document.getElementById('procesos-agregados'),
            document.querySelector('.procesos-container')
        ];
        
        containers.forEach(container => {
            if (container) {
                container.innerHTML = '';
                container.style.display = 'none';
            }
        });
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorProcesos;
}
