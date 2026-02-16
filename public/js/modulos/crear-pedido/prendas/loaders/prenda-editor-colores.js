/**
 *  Módulo de Asignación de Colores por Talla
 * Responsabilidad: Cargar y mostrar asignación de colores
 */

class PrendaEditorColores {
    /**
     * Cargar asignación de colores por talla
     */
    static cargar(prenda) {
        console.log(' [Colores] Cargando asignaciones:', {
            cantidad: prenda.asignaciones?.length || 0,
            asignacionesColoresPorTalla: Object.keys(prenda.asignacionesColoresPorTalla || {}).length
        });
        
        const tabla = document.getElementById('tabla-resumen-asignaciones-cuerpo');
        if (!tabla) {
            console.warn(' [Colores] No encontrado #tabla-resumen-asignaciones-cuerpo');
            return;
        }
        
        // Si no hay array plano pero sí hay asignacionesColoresPorTalla, generarlo
        if ((!prenda.asignaciones || !Array.isArray(prenda.asignaciones) || prenda.asignaciones.length === 0)
            && prenda.asignacionesColoresPorTalla && Object.keys(prenda.asignacionesColoresPorTalla).length > 0) {
            console.log('[Colores] Generando array plano desde asignacionesColoresPorTalla...');
            prenda.asignaciones = this._generarAsignacionesPlanas(prenda.asignacionesColoresPorTalla);
            console.log('[Colores] Array plano generado:', prenda.asignaciones.length, 'filas');
        }
        
        // Si no hay asignaciones, salir
        if (!prenda.asignaciones || !Array.isArray(prenda.asignaciones) || prenda.asignaciones.length === 0) {
            console.log(' [Colores] Sin asignaciones para cargar');
            this._ocultarSeccion();
            // Mostrar tarjetas de tallas (flujo 1)
            this._mostrarTarjetasTallas();
            return;
        }
        
        // Limpiar tabla
        tabla.innerHTML = '';
        
        // Guardar asignaciones en referencia interna
        this._asignaciones = [...prenda.asignaciones];
        
        // Cargar asignaciones
        prenda.asignaciones.forEach((asignacion, idx) => {
            const fila = this._crearFilaAsignacion(asignacion, idx);
            tabla.appendChild(fila);
            console.log(` [Colores] Asignación ${idx + 1}: ${asignacion.tela} - ${asignacion.talla}`);
        });
        
        // Mostrar sección y actualizar contador
        this._mostrarSeccion();
        this._actualizarContadores(prenda);
        
        // Flujo 2: Ocultar tarjetas de tallas individuales (la info ya está en la tabla resumen)
        // Aplica tanto cuando viene de cotización como cuando se edita pedido existente con colores por talla
        if (prenda.asignaciones.length > 0) {
            this._ocultarTarjetasTallas();
            console.log('[Colores]  Flujo 2 detectado - tarjetas de tallas ocultadas');
        }
        
        //  Replicar a global para que sea editable
        if (prenda.asignacionesColoresPorTalla) {
            window.ColoresPorTalla = window.ColoresPorTalla || {};
            window.ColoresPorTalla.datos = JSON.parse(JSON.stringify(prenda.asignacionesColoresPorTalla));
            console.log('[Carga]  Asignaciones de colores replicadas en ColoresPorTalla');
            
            // También poblar StateManager si existe (para que el wizard lo reconozca)
            if (window.StateManager && typeof window.StateManager.agregarAsignacion === 'function') {
                Object.entries(prenda.asignacionesColoresPorTalla).forEach(([clave, asignacion]) => {
                    window.StateManager.agregarAsignacion(clave, JSON.parse(JSON.stringify(asignacion)));
                });
                console.log('[Carga]  Asignaciones replicadas en StateManager');
            }
        }
        
        console.log(' [Colores] Completado');
    }

    /**
     * Crear fila de asignación para la tabla (modo lectura con botón editar)
     * @private
     */
    static _crearFilaAsignacion(asignacion, idx) {
        const fila = document.createElement('tr');
        fila.setAttribute('data-idx', idx);
        fila.style.cssText = 'background: #ffffff; border-bottom: 1px solid #e5e7eb;';
        
        const tela = asignacion.tela || asignacion.tela_nombre || '-';
        const genero = asignacion.genero || asignacion.genero_nombre || '-';
        const talla = asignacion.talla || '-';
        const color = asignacion.color || asignacion.color_nombre || '-';
        const cantidad = asignacion.cantidad || 0;
        const clave = `${(asignacion.genero || '').toLowerCase()}-${asignacion.tipo || 'Letra'}-${asignacion.talla || ''}`;
        
        fila.innerHTML = `
            <td style="padding: 0.75rem; color: #374151; font-weight: 500;" data-field="tela">${tela}</td>
            <td style="padding: 0.75rem; color: #374151;" data-field="genero">${genero}</td>
            <td style="padding: 0.75rem; color: #374151; font-weight: 500;" data-field="talla">${talla}</td>
            <td style="padding: 0.75rem; color: #374151;" data-field="color">${color}</td>
            <td style="padding: 0.75rem; text-align: center; color: #374151; font-weight: 600;" data-field="cantidad">${cantidad}</td>
            <td style="padding: 0.75rem; text-align: center;">
                <div style="display: flex; gap: 0.25rem; justify-content: center;">
                    <button type="button" class="btn-editar-asignacion"
                        data-idx="${idx}"
                        style="background: #dbeafe; border: none; color: #2563eb; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"
                        title="Editar fila">
                        ✎
                    </button>
                    <button type="button" class="btn-eliminar-asignacion" 
                        data-clave="${clave}"
                        data-color="${color}"
                        data-idx="${idx}"
                        style="background: #fee2e2; border: none; color: #dc2626; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">
                        ✕
                    </button>
                </div>
            </td>
        `;
        
        // Event: editar
        fila.querySelector('.btn-editar-asignacion').addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            PrendaEditorColores._editarFila(fila, idx);
        });
        
        // Event: eliminar
        fila.querySelector('.btn-eliminar-asignacion').addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            PrendaEditorColores._eliminarFila(idx);
        });
        
        return fila;
    }

    /**
     * Convertir fila a modo edición inline
     * @private
     */
    static _editarFila(fila, idx) {
        const asignacion = this._asignaciones[idx];
        if (!asignacion) return;
        
        const tdTela = fila.querySelector('[data-field="tela"]');
        const tdGenero = fila.querySelector('[data-field="genero"]');
        const tdTalla = fila.querySelector('[data-field="talla"]');
        const tdColor = fila.querySelector('[data-field="color"]');
        const tdCantidad = fila.querySelector('[data-field="cantidad"]');
        const tdAccion = fila.querySelector('td:last-child');
        
        // Guardar valores originales
        const original = {
            tela: asignacion.tela || '',
            genero: asignacion.genero || '',
            talla: asignacion.talla || '',
            color: asignacion.color || '',
            cantidad: asignacion.cantidad || 0
        };
        
        fila.style.background = '#eff6ff';
        
        // Reemplazar celdas con inputs editables
        tdTela.innerHTML = `<input type="text" list="opciones-telas" value="${original.tela}" 
            style="width: 100%; padding: 0.35rem; border: 1px solid #93c5fd; border-radius: 4px; font-size: 0.8rem; text-transform: uppercase;" 
            onkeyup="this.value = this.value.toUpperCase()">`;
        
        tdGenero.innerHTML = `<select style="width: 100%; padding: 0.35rem; border: 1px solid #93c5fd; border-radius: 4px; font-size: 0.8rem;">
            <option value="DAMA" ${original.genero.toUpperCase() === 'DAMA' ? 'selected' : ''}>DAMA</option>
            <option value="CABALLERO" ${original.genero.toUpperCase() === 'CABALLERO' ? 'selected' : ''}>CABALLERO</option>
            <option value="UNISEX" ${original.genero.toUpperCase() === 'UNISEX' ? 'selected' : ''}>UNISEX</option>
        </select>`;
        
        tdTalla.innerHTML = `<input type="text" value="${original.talla}" 
            style="width: 100%; padding: 0.35rem; border: 1px solid #93c5fd; border-radius: 4px; font-size: 0.8rem; text-transform: uppercase; text-align: center;"
            onkeyup="this.value = this.value.toUpperCase()">`;
        
        tdColor.innerHTML = `<input type="text" list="opciones-colores" value="${original.color}" 
            style="width: 100%; padding: 0.35rem; border: 1px solid #93c5fd; border-radius: 4px; font-size: 0.8rem; text-transform: uppercase;"
            onkeyup="this.value = this.value.toUpperCase()">`;
        
        tdCantidad.innerHTML = `<input type="number" min="0" value="${original.cantidad}" 
            style="width: 70px; padding: 0.35rem; border: 1px solid #93c5fd; border-radius: 4px; font-size: 0.8rem; text-align: center; font-weight: 600;">`;
        
        // Botones: Guardar + Cancelar
        tdAccion.innerHTML = `
            <div style="display: flex; gap: 0.25rem; justify-content: center;">
                <button type="button" class="btn-guardar-edicion"
                    style="background: #dcfce7; border: none; color: #16a34a; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"
                    title="Guardar cambios">
                    ✓
                </button>
                <button type="button" class="btn-cancelar-edicion"
                    style="background: #f3f4f6; border: none; color: #6b7280; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"
                    title="Cancelar">
                    ✕
                </button>
            </div>
        `;
        
        // Event: guardar
        tdAccion.querySelector('.btn-guardar-edicion').addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const nuevosTela = tdTela.querySelector('input').value.trim().toUpperCase();
            const nuevosGenero = tdGenero.querySelector('select').value;
            const nuevosTalla = tdTalla.querySelector('input').value.trim().toUpperCase();
            const nuevosColor = tdColor.querySelector('input').value.trim().toUpperCase();
            const nuevosCantidad = parseInt(tdCantidad.querySelector('input').value) || 0;
            
            // Actualizar asignación interna
            this._asignaciones[idx] = {
                ...asignacion,
                tela: nuevosTela,
                genero: nuevosGenero,
                talla: nuevosTalla,
                color: nuevosColor,
                cantidad: nuevosCantidad
            };
            
            // Sincronizar con StateManager
            this._sincronizarStateManager();
            
            // Sincronizar con tallasRelacionales
            this._sincronizarTallasRelacionales();
            
            // Re-renderizar fila en modo lectura
            this._reRenderizarFila(fila, idx);
            
            // Actualizar total
            this._actualizarTotal();
            
            console.log(`[Colores] ✅ Fila ${idx} editada:`, this._asignaciones[idx]);
        });
        
        // Event: cancelar
        tdAccion.querySelector('.btn-cancelar-edicion').addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this._reRenderizarFila(fila, idx);
        });
    }

    /**
     * Re-renderizar fila en modo lectura
     * @private
     */
    static _reRenderizarFila(filaVieja, idx) {
        const asignacion = this._asignaciones[idx];
        if (!asignacion) return;
        
        const nuevaFila = this._crearFilaAsignacion(asignacion, idx);
        filaVieja.replaceWith(nuevaFila);
    }

    /**
     * Eliminar fila
     * @private
     */
    static _eliminarFila(idx) {
        if (!this._asignaciones || idx < 0 || idx >= this._asignaciones.length) return;
        
        this._asignaciones.splice(idx, 1);
        
        // Re-renderizar toda la tabla
        const tabla = document.getElementById('tabla-resumen-asignaciones-cuerpo');
        if (tabla) {
            tabla.innerHTML = '';
            this._asignaciones.forEach((asig, i) => {
                const fila = this._crearFilaAsignacion(asig, i);
                tabla.appendChild(fila);
            });
        }
        
        // Sincronizar con StateManager
        this._sincronizarStateManager();
        
        // Sincronizar con tallasRelacionales
        this._sincronizarTallasRelacionales();
        
        // Actualizar total
        this._actualizarTotal();
        
        // Si no quedan asignaciones, ocultar sección y mostrar tarjetas
        if (this._asignaciones.length === 0) {
            this._ocultarSeccion();
            this._mostrarTarjetasTallas();
        }
        
        console.log(`[Colores] 🗑️ Fila ${idx} eliminada, quedan ${this._asignaciones.length}`);
    }

    /**
     * Sincronizar asignaciones internas con StateManager
     * @private
     */
    static _sincronizarStateManager() {
        // Reconstruir asignacionesColoresPorTalla desde asignaciones planas
        const nuevo = {};
        this._asignaciones.forEach(asig => {
            const generoLower = (asig.genero || 'dama').toLowerCase();
            const tipoTalla = /^\d+$/.test(asig.talla) ? 'Número' : 'Letra';
            const clave = `${generoLower}-${tipoTalla}-${asig.talla}`;
            
            if (!nuevo[clave]) {
                nuevo[clave] = {
                    genero: generoLower,
                    tela: asig.tela || '',
                    tipo: tipoTalla,
                    talla: asig.talla || '',
                    colores: []
                };
            }
            
            const existeColor = nuevo[clave].colores.find(c => c.nombre === (asig.color || ''));
            if (existeColor) {
                existeColor.cantidad += (asig.cantidad || 0);
            } else {
                nuevo[clave].colores.push({
                    nombre: asig.color || '',
                    cantidad: asig.cantidad || 0
                });
            }
        });
        
        // Actualizar StateManager
        if (window.StateManager && typeof window.StateManager.setAsignaciones === 'function') {
            window.StateManager.setAsignaciones(nuevo);
        }
        
        // Actualizar ColoresPorTalla
        window.ColoresPorTalla = window.ColoresPorTalla || {};
        window.ColoresPorTalla.datos = JSON.parse(JSON.stringify(nuevo));
    }

    /**
     * Sincronizar tallasRelacionales desde las asignaciones
     * @private
     */
    static _sincronizarTallasRelacionales() {
        if (!this._asignaciones || this._asignaciones.length === 0) return;
        
        const tallas = {};
        this._asignaciones.forEach(asig => {
            const genero = (asig.genero || 'DAMA').toUpperCase();
            if (!tallas[genero]) tallas[genero] = {};
            // Sumar cantidades por talla
            tallas[genero][asig.talla] = (tallas[genero][asig.talla] || 0) + (asig.cantidad || 0);
        });
        
        window.tallasRelacionales = tallas;
    }

    /**
     * Actualizar total de unidades asignadas
     * @private
     */
    static _actualizarTotal() {
        let total = 0;
        if (this._asignaciones) {
            this._asignaciones.forEach(a => {
                total += parseInt(a.cantidad) || 0;
            });
        }
        
        const totalSpan = document.getElementById('total-asignaciones-resumen');
        if (totalSpan) totalSpan.textContent = total;
        
        // También actualizar el total general de prendas
        const totalPrendas = document.getElementById('total-prendas');
        if (totalPrendas) totalPrendas.textContent = total;
    }

    /**
     * Actualizar contadores (para carga inicial)
     * @private
     */
    static _actualizarContadores(prenda) {
        const contador = document.getElementById('contador-asignaciones');
        if (contador) {
            contador.value = prenda.asignaciones.length;
        }
        
        let total = 0;
        prenda.asignaciones.forEach(a => {
            total += parseInt(a.cantidad) || 0;
        });
        
        const totalAsignaciones = document.getElementById('total-asignaciones-resumen');
        if (totalAsignaciones) totalAsignaciones.textContent = total;
        
        // También actualizar total de prendas
        const totalPrendas = document.getElementById('total-prendas');
        if (totalPrendas) totalPrendas.textContent = total;
    }

    /**
     * Mostrar sección de asignaciones
     * @private
     */
    static _mostrarSeccion() {
        const msgVacio = document.getElementById('msg-resumen-vacio');
        if (msgVacio) msgVacio.style.display = 'none';
        
        const seccion = document.getElementById('seccion-resumen-asignaciones');
        if (seccion) seccion.style.display = 'block';
    }

    /**
     * Ocultar sección de asignaciones
     * @private
     */
    static _ocultarSeccion() {
        const msgVacio = document.getElementById('msg-resumen-vacio');
        if (msgVacio) msgVacio.style.display = 'block';
        
        const seccion = document.getElementById('seccion-resumen-asignaciones');
        if (seccion) seccion.style.display = 'none';
    }

    /**
     * Ocultar tarjetas de tallas (flujo 2 - la tabla resumen reemplaza las tarjetas)
     * @private
     */
    static _ocultarTarjetasTallas() {
        const seccionTallas = document.getElementById('seccion-tallas-cantidades');
        if (seccionTallas) {
            seccionTallas.style.display = 'none';
        }
    }

    /**
     * Mostrar tarjetas de tallas (flujo 1 normal)
     * @private
     */
    static _mostrarTarjetasTallas() {
        const seccionTallas = document.getElementById('seccion-tallas-cantidades');
        if (seccionTallas) {
            seccionTallas.style.display = '';
        }
    }

    /**
     * Limpiar asignaciones
     */
    static limpiar() {
        const tabla = document.getElementById('tabla-resumen-asignaciones-cuerpo');
        if (tabla) tabla.innerHTML = '';
        
        this._asignaciones = [];
        
        const contador = document.getElementById('contador-asignaciones');
        if (contador) contador.value = '';
        
        const totalAsignaciones = document.getElementById('total-asignaciones-resumen');
        if (totalAsignaciones) totalAsignaciones.textContent = '0';
        
        this._ocultarSeccion();
        this._mostrarTarjetasTallas();
        
        // Limpiar StateManager y wizard para evitar datos residuales de prenda anterior
        if (window.StateManager) {
            if (typeof window.StateManager.limpiarAsignaciones === 'function') {
                window.StateManager.limpiarAsignaciones();
            }
            if (typeof window.StateManager.resetWizardState === 'function') {
                window.StateManager.resetWizardState();
            }
        }
        
        // Limpiar ColoresPorTalla datos
        if (window.ColoresPorTalla && window.ColoresPorTalla.datos) {
            window.ColoresPorTalla.datos = {};
        }
    }

    /**
     * Generar array plano de asignaciones desde asignacionesColoresPorTalla
     * Convierte: { "dama-Letra-M": { genero, tela, talla, colores: [{nombre, cantidad}] } }
     * A: [ { tela, genero, talla, color, cantidad, tipo } ]
     * @private
     */
    static _generarAsignacionesPlanas(asignacionesObj) {
        const planas = [];
        Object.entries(asignacionesObj).forEach(([clave, asignacion]) => {
            const genero = (asignacion.genero || '').toUpperCase();
            const tela = asignacion.tela || '';
            const talla = asignacion.talla || '';
            const tipo = asignacion.tipo || 'Letra';
            
            if (asignacion.colores && Array.isArray(asignacion.colores)) {
                asignacion.colores.forEach(color => {
                    planas.push({
                        tela: tela,
                        genero: genero,
                        talla: talla,
                        color: color.nombre || '',
                        cantidad: parseInt(color.cantidad) || 0,
                        tipo: tipo
                    });
                });
            }
        });
        return planas;
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorColores;
}
