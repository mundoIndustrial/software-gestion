/**
 * 📏 Módulo de Tallas y Cantidades
 * Responsabilidad: Cargar tarjetas de género con inputs de tallas
 */

class PrendaEditorTallas {
    // Tallas disponibles por género
    static TALLAS_DISPONIBLES = {
        'DAMA': ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        'CABALLERO': ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        'UNISEX': ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        'SOBREMEDIDA': []
    };

    /**
     * Cargar tallas y cantidades
     */
    static cargar(prenda) {
        console.log('📏 [Tallas] Cargando:', {
            generos: Object.keys(prenda.cantidad_talla || {}),
            estructura: typeof prenda.cantidad_talla
        });
        
        const container = document.getElementById('tarjetas-generos-container');
        if (!container) {
            console.warn(' [Tallas] No encontrado #tarjetas-generos-container');
            return;
        }
        
        // 🔄 LIMPIAR TARJETAS ANTERIORES (importante en edición)
        container.innerHTML = '';
        console.log('[Tallas] 🧹 Tarjetas limpias');
        
        const tallasData = prenda.tallasRelacionales || prenda.cantidad_talla;
        if (!tallasData) {
            console.warn(' [Tallas] Sin datos de tallas');
            return;
        }
        
        // Cargar por género
        Object.entries(tallasData).forEach(([genero, tallas]) => {
            // 🔴 NUEVO: SALTAR GENERICO (se maneja como UNISEX)
            if (genero.toUpperCase() === 'GENERICO') {
                console.log('[Tallas] ⏭️ Saltando GENERICO (se renderiza como UNISEX via crearTarjetaUnisex)');
                return;
            }
            
            if (!tallas || typeof tallas !== 'object' || Object.keys(tallas).length === 0) {
                console.log(`[Tallas] ${genero} sin datos`);
                return;
            }
            
            // Buscar tarjeta existente
            let tarjeta = container.querySelector(`[data-genero="${genero}"]`);
            
            // Si no existe, crearla
            if (!tarjeta) {
                console.log(`[Tallas] Creando tarjeta de ${genero}`);
                tarjeta = this._crearTarjeta(genero, tallas);  //  Pasar tallas específicas
                container.appendChild(tarjeta);
            }
            
            // Llenar inputs
            Object.entries(tallas).forEach(([talla, cantidad]) => {
                const input = tarjeta.querySelector(`input[data-talla="${talla}"]`);
                if (input) {
                    input.value = cantidad || 0;
                    // 🔥 AGREGAR EVENTO PARA ACTUALIZAR window.tallasRelacionales AL CAMBIAR
                    input.addEventListener('change', () => {
                        const nuevaCantidad = parseInt(input.value) || 0;
                        console.log(`[Tallas] 🔄 Actualizando ${genero} - ${talla}: ${cantidad} → ${nuevaCantidad}`);
                        
                        // Actualizar window.tallasRelacionales
                        if (!window.tallasRelacionales) {
                            window.tallasRelacionales = {};
                        }
                        if (!window.tallasRelacionales[genero]) {
                            window.tallasRelacionales[genero] = {};
                        }
                        
                        if (nuevaCantidad > 0) {
                            window.tallasRelacionales[genero][talla] = nuevaCantidad;
                        } else {
                            delete window.tallasRelacionales[genero][talla];
                        }
                        
                        console.log(`[Tallas] 📊 window.tallasRelacionales actualizado:`, window.tallasRelacionales);
                        
                        // Actualizar total
                        this._actualizarTotal();
                    });
                    
                    // 🔥 AGREGAR EVENTO input PARA ACTUALIZAR EN TIEMPO REAL
                    input.addEventListener('input', () => {
                        const nuevaCantidad = parseInt(input.value) || 0;
                        console.log(`[Tallas] ⌨️ Input en tiempo real ${genero} - ${talla}: ${cantidad} → ${nuevaCantidad}`);
                        
                        // Actualizar window.tallasRelacionales
                        if (!window.tallasRelacionales) {
                            window.tallasRelacionales = {};
                        }
                        if (!window.tallasRelacionales[genero]) {
                            window.tallasRelacionales[genero] = {};
                        }
                        
                        if (nuevaCantidad > 0) {
                            window.tallasRelacionales[genero][talla] = nuevaCantidad;
                        } else {
                            delete window.tallasRelacionales[genero][talla];
                        }
                        
                        // Actualizar total
                        this._actualizarTotal();
                    });
                    
                    console.log(` [Tallas] ${genero} - ${talla}: ${cantidad}`);
                }
            });
        });
        
        // Actualizar total
        this._actualizarTotal();
        
        //  Replicar a global para que sea editable
        const tallasAUsar = prenda.cantidad_talla || prenda.tallasRelacionales;
        if (tallasAUsar) {
            window.tallasRelacionales = JSON.parse(JSON.stringify(tallasAUsar));
            console.log('[Carga] 📏 Tallas replicadas en window.tallasRelacionales');
        }
        
        console.log(' [Tallas] Completado');
    }

    /**
     * Crear tarjeta de género dinámicamente
     * @private
     */
    static _crearTarjeta(genero, tallasData = {}) {
        const tarjeta = document.createElement('div');
        tarjeta.setAttribute('data-genero', genero);
        tarjeta.style.cssText = 'background: white; border: 1px solid rgb(229, 231, 235); border-radius: 8px; padding: 1.5rem; margin-top: 1rem; box-shadow: rgba(0, 0, 0, 0.1) 0px 1px 3px;';
        
        const icons = {
            'DAMA': 'woman',
            'CABALLERO': 'man',
            'UNISEX': 'diversity_1',
            'SOBREMEDIDA': 'straighten'
        };
        
        // Encabezado
        const header = document.createElement('div');
        header.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; justify-content: space-between;';
        
        const headerLeft = document.createElement('div');
        headerLeft.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
        headerLeft.innerHTML = `
            <span class="material-symbols-rounded" style="font-size: 1.5rem; color: #374151;">${icons[genero] || 'help'}</span>
            <h4 style="margin: 0; color: #1f2937; font-size: 1rem; font-weight: 600;">${genero}</h4>
        `;
        header.appendChild(headerLeft);
        
        // Botones de acciones
        const btnGroup = document.createElement('div');
        btnGroup.style.cssText = 'display: flex; gap: 0.25rem;';
        
        const btnEditar = document.createElement('button');
        btnEditar.type = 'button';
        btnEditar.title = 'Editar tallas';
        btnEditar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 6px;';
        btnEditar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem;">edit</span>';
        btnEditar.onmouseover = () => {
            btnEditar.style.color = '#0066cc';
            btnEditar.style.background = '#f3f4f6';
        };
        btnEditar.onmouseout = () => {
            btnEditar.style.color = '#6b7280';
            btnEditar.style.background = 'transparent';
        };
        btnEditar.onclick = () => {
            console.log(`[PrendaEditorTallas] 📝 Editando tallas de ${genero}`);
            // Llamar a la función de edición si existe
            if (typeof abrirModalSeleccionarTallas === 'function') {
                abrirModalSeleccionarTallas(genero);
            }
        };
        btnGroup.appendChild(btnEditar);
        
        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.title = 'Eliminar tallas';
        btnEliminar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 6px;';
        btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem;">delete</span>';
        btnEliminar.onmouseover = () => {
            btnEliminar.style.color = '#ef4444';
            btnEliminar.style.background = '#fee2e2';
        };
        btnEliminar.onmouseout = () => {
            btnEliminar.style.color = '#6b7280';
            btnEliminar.style.background = 'transparent';
        };
        btnEliminar.onclick = () => {
            console.log(`[PrendaEditorTallas]  Eliminando tallas de ${genero}`);
            // Limpiar tallas del género
            window.tallasRelacionales[genero] = {};
            
            // Remover tarjeta del DOM
            tarjeta.remove();
            
            // Desmarcar botón de género si existe
            const btnGenero = document.getElementById(`btn-genero-${genero}`);
            if (btnGenero) {
                btnGenero.dataset.selected = 'false';
                btnGenero.style.borderColor = '#d1d5db';
                btnGenero.style.background = 'white';
                btnGenero.style.color = '#1f2937';
            }
            
            // Actualizar total
            this._actualizarTotal();
        };
        btnGroup.appendChild(btnEliminar);
        
        header.appendChild(btnGroup);
        
        // Grid de inputs -  SOLO para tallas que tienen datos
        const grid = document.createElement('div');
        grid.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem;';
        
        // Iterar SOLO sobre las tallas que existen en tallasData
        Object.keys(tallasData).forEach(talla => {
            const item = document.createElement('div');
            item.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem;';
            item.innerHTML = `
                <label style="font-size: 0.875rem; font-weight: 600; color: rgb(107, 114, 128); text-align: center;">${talla}</label>
                <input 
                    type="number" 
                    min="0" 
                    data-talla="${talla}"
                    value="0"
                    style="padding: 0.5rem; border: 2px solid rgb(0, 102, 204); border-radius: 6px; text-align: center; font-weight: 600; font-size: 0.9rem;">
            `;
            grid.appendChild(item);
        });
        
        tarjeta.appendChild(header);
        tarjeta.appendChild(grid);
        
        return tarjeta;
    }

    /**
     * Actualizar total de prendas
     * @private
     */
    static _actualizarTotal() {
        const totalSpan = document.getElementById('total-prendas');
        if (!totalSpan) return;
        
        let total = 0;
        const inputs = document.querySelectorAll('#tarjetas-generos-container input[type="number"]');
        inputs.forEach(input => {
            const valor = parseInt(input.value) || 0;
            // Solo contar valores > 0
            if (valor > 0) {
                total += valor;
            }
        });
        
        totalSpan.textContent = total;
        console.log(`📊 [Tallas] Total actualizado: ${total}`);
    }

    /**
     * Marcar géneros como seleccionados
     */
    static marcarGeneros(prenda) {
        if (!prenda.cantidad_talla) return;
        
        //  PRIMERO: Desmarcar TODOS los géneros
        ['dama', 'caballero', 'sobremedida'].forEach(genero => {
            const btn = document.getElementById(`btn-genero-${genero}`);
            if (btn) {
                btn.setAttribute('data-selected', 'false');
                btn.style.background = 'white';
                btn.style.borderColor = '#d1d5db';
            }
        });
        
        // 🟢 LUEGO: Marcar SOLO los que tienen datos reales (tallas con cantidades > 0)
        Object.entries(prenda.cantidad_talla).forEach(([genero, tallas]) => {
            // Verificar si este género tiene al menos una talla con cantidad > 0
            const tieneDatos = Object.values(tallas || {}).some(val => parseInt(val) > 0);
            
            if (tieneDatos) {
                const generoLower = genero.toLowerCase();
                const btn = document.getElementById(`btn-genero-${generoLower}`);
                if (btn) {
                    btn.setAttribute('data-selected', 'true');
                    btn.style.background = '#dbeafe';
                    btn.style.borderColor = '#0369a1';
                    console.log(` [Tallas] Género ${genero} marcado (tiene ${Object.values(tallas).filter(v => v > 0).length} talla(s))`);
                }
            }
        });
    }

    /**
     * Limpiar tarjetas
     */
    static limpiar() {
        const container = document.getElementById('tarjetas-generos-container');
        if (container) {
            container.innerHTML = '';
        }
        
        const totalSpan = document.getElementById('total-prendas');
        if (totalSpan) {
            totalSpan.textContent = '0';
        }
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorTallas;
}
