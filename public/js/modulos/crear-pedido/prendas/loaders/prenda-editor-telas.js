/**
 * 🧵 Módulo de Telas
 * Responsabilidad: Cargar y gestionar tabla de telas
 */

class PrendaEditorTelas {
    /**
     * Cargar telas en la tabla
     */
    static cargar(prenda) {
        console.log('🧵 [Telas] Cargando:', {
            cantidad: prenda.telasAgregadas?.length || 0,
            telas: prenda.telasAgregadas?.map(t => t.tela_nombre || t.tela || t.nombre || 'Sin nombre')
        });
        
        // Buscar tabla
        const tablaTelas = document.querySelector('#tbody-telas');
        if (!tablaTelas) {
            console.warn('❌ [Telas] No encontrado #tbody-telas');
            return;
        }
        
        // Encontrar fila de inputs (para agregar nuevas)
        const filaInputs = tablaTelas.querySelector('[id="nueva-prenda-tela"]')?.closest('tr');
        
        // Eliminar filas viejas (excepto inputs)
        const filasExistentes = tablaTelas.querySelectorAll('tr');
        filasExistentes.forEach(fila => {
            if (fila !== filaInputs) {
                fila.remove();
            }
        });
        
        console.log('[Telas] Filas viejas eliminadas');
        
        // Cargar telas nuevas
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0) {
            prenda.telasAgregadas.forEach((tela, idx) => {
                const fila = this._crearFilaTela(tela, idx);
                
                // Insertar ANTES de la fila de inputs
                if (filaInputs) {
                    filaInputs.parentNode.insertBefore(fila, filaInputs);
                } else {
                    tablaTelas.appendChild(fila);
                }
                
                console.log(`✅ [Telas] ${idx + 1}: ${tela.tela_nombre || tela.tela || tela.nombre || 'Sin nombre'}`);
            });
        }
        
        // 🔥 Replicar a global para que sea editable
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
            window.telasCreacion = JSON.parse(JSON.stringify(prenda.telasAgregadas));
            console.log('[Carga] 🧵 Telas replicadas en window.telasCreacion:', window.telasCreacion.length);
        }
        
        console.log('✅ [Telas] Completado');
    }

    /**
     * Crear fila de tela para la tabla
     * @private
     */
    static _crearFilaTela(tela, idx) {
        const fila = document.createElement('tr');
        fila.style.borderBottom = '1px solid #e5e7eb';
        fila.innerHTML = `
            <td style="padding: 0.5rem;">${tela.tela_nombre || tela.tela || tela.nombre || 'Sin nombre'}</td>
            <td style="padding: 0.5rem;">${tela.color_nombre || tela.color || 'Sin color'}</td>
            <td style="padding: 0.5rem;">${tela.referencia || '-'}</td>
            <td style="padding: 0.5rem; text-align: center; vertical-align: top;">
                <div style="font-size: 0.75rem; color: #9ca3af;">(sin imagen)</div>
            </td>
            <td style="padding: 0.5rem; text-align: center;">
                <button type="button" class="btn btn-sm btn-danger" 
                    onclick="eliminarTela(${idx})"
                    title="Eliminar tela">
                    ✕
                </button>
            </td>
        `;
        return fila;
    }

    /**
     * Limpiar tabla de telas
     */
    static limpiar() {
        const tablaTelas = document.querySelector('#tbody-telas');
        if (!tablaTelas) return;
        
        // Eliminar todo excepto fila de inputs
        const filaInputs = tablaTelas.querySelector('[id="nueva-prenda-tela"]')?.closest('tr');
        const filasExistentes = tablaTelas.querySelectorAll('tr');
        
        filasExistentes.forEach(fila => {
            if (fila !== filaInputs) {
                fila.remove();
            }
        });
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorTelas;
}
