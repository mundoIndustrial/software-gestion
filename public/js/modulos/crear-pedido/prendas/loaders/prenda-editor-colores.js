/**
 *  Módulo de Asignación de Colores por Talla
 * Responsabilidad: Cargar y mostrar asignación de colores
 * Renderiza EXACTAMENTE igual que ColoresPorTalla.js (agrupado por talla con chips de colores)
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
        
        // Necesitamos asignacionesColoresPorTalla para renderizar agrupado
        if (!prenda.asignacionesColoresPorTalla || Object.keys(prenda.asignacionesColoresPorTalla).length === 0) {
            console.log(' [Colores] Sin asignaciones para cargar');
            this._ocultarSeccion();
            this._mostrarTarjetasTallas();
            return;
        }
        
        // Guardar referencia interna de asignaciones agrupadas
        this._asignacionesAgrupadas = JSON.parse(JSON.stringify(prenda.asignacionesColoresPorTalla));
        
        // Renderizar tabla agrupada (igual que ColoresPorTalla.js)
        this._renderizarTablaAgrupada();
        
        // Mostrar sección
        this._mostrarSeccion();
        
        // Flujo 2: Ocultar tarjetas de tallas individuales
        this._ocultarTarjetasTallas();
        console.log('[Colores]  Flujo 2 detectado - tarjetas de tallas ocultadas');
        
        //  Replicar a global para que sea editable
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
        
        console.log(' [Colores] Completado');
    }

    /**
     * Renderizar tabla agrupada igual que ColoresPorTalla.js actualizarTablaResumen()
     * @private
     */
    static _renderizarTablaAgrupada() {
        const tabla = document.getElementById('tabla-resumen-asignaciones-cuerpo');
        if (!tabla) return;
        
        const asignaciones = this._asignacionesAgrupadas || {};
        const asignacionesArray = Object.entries(asignaciones);
        
        if (asignacionesArray.length === 0) {
            tabla.innerHTML = '';
            this._actualizarTotalDesdeAgrupado();
            return;
        }
        
        const cellStyle = 'padding: 0.75rem; color: #374151;';
        let html = '';
        let totalAsignaciones = 0;

        asignacionesArray.forEach(([clave, asignacion], rowIdx) => {
            const { genero, talla, tela, colores } = asignacion;
            
            if (!colores || !Array.isArray(colores) || colores.length === 0) return;
            
            // Calcular cantidad total para esta asignación
            const totalCant = colores.reduce((sum, c) => sum + (typeof c.cantidad === 'number' ? c.cantidad : 1), 0);
            totalAsignaciones += totalCant;
            
            const bg = (rowIdx % 2 === 0) ? '#ffffff' : '#f9fafb';
            
            // Chips de colores con cantidad (igual que ColoresPorTalla.js)
            const coloresChipsHtml = colores.map(c => {
                const nombre = c.nombre || '--';
                const cant = typeof c.cantidad === 'number' ? c.cantidad : 1;
                return `<span style="display:inline-block;background:#dbeafe;color:#1e40af;padding:0.15rem 0.5rem;border-radius:12px;font-size:0.73rem;font-weight:500;margin:0.1rem;white-space:nowrap;">${nombre} (${cant})</span>`;
            }).join('');
            
            // Combinar referencias (únicas, no vacías)
            const refs = [...new Set(colores.map(c => c.referencia).filter(Boolean))];
            const refHtml = refs.length > 0 ? refs.join(', ') : '-';
            
            // Combinar imágenes
            let imgsHtml = '<span style="color:#9ca3af;font-size:0.75rem;">—</span>';
            const conImagen = colores.filter(c => c.imagen_id);
            if (conImagen.length > 0) {
                const imgParts = conImagen.map(c => {
                    const blobUrl = this._getBlobUrl(c.imagen_id);
                    if (blobUrl) {
                        return `<img src="${blobUrl}" style="width:28px;height:28px;object-fit:cover;border-radius:3px;border:1px solid #d1d5db;margin:1px;" alt="img">`;
                    } else if (c.imagen_nombre) {
                        return `<span style="color:#6b7280;font-size:0.7rem;">${c.imagen_nombre}</span>`;
                    }
                    return '';
                }).filter(Boolean);
                if (imgParts.length > 0) {
                    imgsHtml = imgParts.join('');
                }
            }
            
            // Combinar observaciones (únicas, no vacías)
            const obs = [...new Set(colores.map(c => c.observaciones).filter(Boolean))];
            const obsText = obs.length > 0 ? obs.join(' | ') : '-';
            
            html += `
                <tr style="background: ${bg}; border-bottom: 1px solid #e5e7eb;" data-clave="${clave}" data-tipo="wizard">
                    <td style="${cellStyle} font-weight: 500;" data-field="tela">${tela || '--'}</td>
                    <td style="${cellStyle}" data-field="color"><div style="display:flex;flex-wrap:wrap;gap:0.15rem;">${coloresChipsHtml}</div></td>
                    <td style="${cellStyle} font-size:0.8rem;" data-field="referencia">${refHtml}</td>
                    <td style="${cellStyle}" data-field="imagen"><div style="display:flex;flex-wrap:wrap;gap:2px;">${imgsHtml}</div></td>
                    <td style="${cellStyle} font-size:0.8rem; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" data-field="observaciones" title="${obsText}">${obsText}</td>
                    <td style="${cellStyle}" data-field="genero">${genero ? genero.toUpperCase() : '--'}</td>
                    <td style="${cellStyle} font-weight: 500;" data-field="talla">${talla || '--'}</td>
                    <td style="${cellStyle} text-align: center; font-weight: 600;" data-field="cantidad">${totalCant}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <div style="display: flex; gap: 0.25rem; justify-content: center;">
                            <button type="button" class="btn-editar-asignacion" data-clave="${clave}"
                                style="background: #dbeafe; border: none; color: #2563eb; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"
                                title="Editar asignación">✎</button>
                            <button type="button" class="btn-eliminar-asignacion" data-clave="${clave}"
                                style="background: #fee2e2; border: none; color: #dc2626; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"
                                title="Eliminar asignación">✕</button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tabla.innerHTML = html;
        
        // Actualizar total
        const totalSpan = document.getElementById('total-asignaciones-resumen');
        if (totalSpan) totalSpan.textContent = totalAsignaciones;
        const totalPrendas = document.getElementById('total-prendas');
        if (totalPrendas) totalPrendas.textContent = totalAsignaciones;
        
        console.log(` [Colores] Tabla renderizada: ${asignacionesArray.length} filas, ${totalAsignaciones} unidades`);
    }

    /**
     * Actualizar total desde asignaciones agrupadas
     * @private
     */
    static _actualizarTotalDesdeAgrupado() {
        let total = 0;
        if (this._asignacionesAgrupadas) {
            Object.values(this._asignacionesAgrupadas).forEach(asig => {
                if (asig.colores && Array.isArray(asig.colores)) {
                    asig.colores.forEach(c => {
                        total += parseInt(c.cantidad) || 0;
                    });
                }
            });
        }
        
        const totalSpan = document.getElementById('total-asignaciones-resumen');
        if (totalSpan) totalSpan.textContent = total;
        const totalPrendas = document.getElementById('total-prendas');
        if (totalPrendas) totalPrendas.textContent = total;
    }

    /**
     * Obtener blob URL de una imagen por su ID
     * @private
     */
    static _getBlobUrl(imagenId) {
        if (!imagenId) return null;
        // Intentar obtener del almacén de ColoresPorTalla
        if (window.ColoresPorTalla && window.ColoresPorTalla._imageStore) {
            const img = window.ColoresPorTalla._imageStore.get(imagenId);
            return img?.blobUrl || null;
        }
        if (typeof window._getImage === 'function') {
            const img = window._getImage(imagenId);
            return img?.blobUrl || null;
        }
        return null;
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
        
        this._asignacionesAgrupadas = {};
        
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
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorColores;
}
