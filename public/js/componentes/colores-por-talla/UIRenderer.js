/**
 * Módulo: UIRenderer
 * Renderiza interfaces visuales para el sistema de colores por talla
 */

window.UIRenderer = (function() {
    'use strict';

    return {
        /**
         * Generar interfaz profesional para seleccionar colores y cantidades para múltiples tallas
         */
        generarInterfazColoresPorTalla(genero, tallas, tipo) {
            const contenedorColores = document.getElementById('lista-colores-checkboxes');
            if (!contenedorColores) return;
            
            contenedorColores.innerHTML = '';
            
            // Estilo del contenedor para centrar
            Object.assign(contenedorColores.style, {
                display: 'flex',
                flexDirection: 'column',
                gap: '1.5rem',
                alignItems: 'center',
                width: '100%'
            });
            
            // Crear tabla profesional
            const tablaDiv = document.createElement('div');
            Object.assign(tablaDiv.style, {
                display: 'grid',
                gap: '1.5rem',
                gridTemplateColumns: '1fr',
                maxWidth: '600px',
                width: '100%'
            });
            
            // Para cada talla, crear una sección con tabla de colores
            tallas.forEach((talla, idx) => {
                const seccion = this.crearSeccionTalla(talla, tipo, idx);
                tablaDiv.appendChild(seccion);
            });
            
            contenedorColores.appendChild(tablaDiv);
            
            // console.log('[UIRenderer] Interfaz profesional creada para tallas:', tallas);
        },

        /**
         * Crear sección para una talla específica
         */
        crearSeccionTalla(talla, tipo, idx) {
            const seccion = document.createElement('div');
            Object.assign(seccion.style, {
                border: '1px solid #e5e7eb',
                borderRadius: '6px',
                overflow: 'hidden',
                background: '#f9fafb'
            });
            
            // Encabezado de la sección (talla)
            const header = document.createElement('div');
            Object.assign(header.style, {
                background: '#f3f4f6',
                padding: '0.75rem 1rem',
                borderBottom: '1px solid #e5e7eb',
                display: 'flex',
                alignItems: 'center',
                gap: '0.5rem',
                fontWeight: '600',
                color: '#374151',
                fontSize: '0.95rem'
            });
            header.innerHTML = `<span style="font-weight: 700; color: #111827;">${talla}</span>`;
            seccion.appendChild(header);
            
            // Contenedor del contenido (filas de color y cantidad)
            const contenedor = document.createElement('div');
            contenedor.className = 'contenedor-colores-' + idx;
            Object.assign(contenedor.style, {
                display: 'grid',
                gridTemplateColumns: '1fr',
                gap: '0',
                padding: '0.75rem 1rem'
            });
            
            // Primera fila
            this.agregarFilaColorCantidad(contenedor, talla, tipo, idx, 0);
            
            seccion.appendChild(contenedor);
            
            // Botón agregar color
            const btnAgregar = this.crearBotonAgregarColor(contenedor, talla, tipo, idx);
            seccion.appendChild(btnAgregar);
            
            return seccion;
        },

        /**
         * Crear botón para agregar color
         */
        crearBotonAgregarColor(contenedor, talla, tipo, idx) {
            const btnAgregar = document.createElement('button');
            btnAgregar.type = 'button';
            Object.assign(btnAgregar.style, {
                width: '100%',
                padding: '0.5rem',
                border: 'none',
                borderTop: '1px solid #e5e7eb',
                background: 'white',
                color: '#3b82f6',
                fontSize: '0.85rem',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.3rem',
                transition: 'all 0.2s'
            });
            btnAgregar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 0.95rem;">add</span>Agregar color';
            
            let numColores = 1;
            btnAgregar.addEventListener('click', () => {
                this.agregarFilaColorCantidad(contenedor, talla, tipo, idx, numColores);
                numColores++;
            });
            
            btnAgregar.addEventListener('mouseover', () => {
                btnAgregar.style.background = '#f3f4f6';
            });
            
            btnAgregar.addEventListener('mouseout', () => {
                btnAgregar.style.background = 'white';
            });
            
            return btnAgregar;
        },

        /**
         * Agregar una fila de color + cantidad con diseño profesional y datalist
         */
        agregarFilaColorCantidad(contenedor, talla, tipo, tallaIdx, colorIdx) {
            const fila = document.createElement('div');
            fila.className = `fila-color-${tallaIdx}-${colorIdx}`;
            Object.assign(fila.style, {
                display: 'grid',
                gridTemplateColumns: '1fr 70px 32px',
                gap: '0.75rem',
                alignItems: 'center',
                padding: '0.5rem 0',
                borderBottom: '1px solid #f3f4f6'
            });
            
            // ID único para el datalist
            const datalistId = `colores-list-${tallaIdx}-${colorIdx}`;
            
            // Input de color (texto con datalist)
            const inputColor = this.crearInputColor(talla, tipo, datalistId);
            fila.appendChild(inputColor);
            
            // Input de cantidad
            const inputCantidad = this.crearInputCantidad();
            fila.appendChild(inputCantidad);
            
            // Botón eliminar
            const btnEliminar = this.crearBotonEliminarFila(fila);
            fila.appendChild(btnEliminar);
            
            // Crear y agregar datalist
            this.crearDatalistColores(datalistId);
            
            contenedor.appendChild(fila);
        },

        /**
         * Crear input de color
         */
        crearInputColor(talla, tipo, datalistId) {
            const inputColor = document.createElement('input');
            inputColor.type = 'text';
            inputColor.className = 'color-input-wizard';
            inputColor.setAttribute('list', datalistId);  //  Usar setAttribute en lugar de .list
            inputColor.placeholder = 'ROJO, AZUL, VERDE...';
            Object.assign(inputColor.style, {
                padding: '0.5rem 0.75rem',
                border: '1px solid #d1d5db',
                borderRadius: '4px',
                fontSize: '0.85rem',
                textTransform: 'uppercase',
                background: 'white'
            });
            
            inputColor.dataset.talla = talla;
            inputColor.dataset.tipo = tipo;
            
            inputColor.addEventListener('keyup', function() {
                this.value = this.value.toUpperCase();
            });
            
            return inputColor;
        },

        /**
         * Crear input de cantidad
         */
        crearInputCantidad() {
            const inputCantidad = document.createElement('input');
            inputCantidad.type = 'number';
            inputCantidad.className = 'cantidad-input-wizard';
            inputCantidad.min = '0';
            inputCantidad.value = '1';
            Object.assign(inputCantidad.style, {
                padding: '0.5rem 0.5rem',
                border: '1px solid #d1d5db',
                borderRadius: '4px',
                textAlign: 'center',
                fontSize: '0.85rem',
                background: 'white'
            });
            
            return inputCantidad;
        },

        /**
         * Crear botón eliminar fila
         */
        crearBotonEliminarFila(fila) {
            const btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            Object.assign(btnEliminar.style, {
                padding: '0.4rem',
                border: '1px solid #f3f4f6',
                background: '#f9fafb',
                borderRadius: '4px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                minWidth: '32px',
                height: '32px',
                color: '#9ca3af',
                transition: 'all 0.2s'
            });
            btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1rem;">close</span>';
            
            btnEliminar.addEventListener('mouseover', () => {
                Object.assign(btnEliminar.style, {
                    background: '#fee2e2',
                    color: '#dc2626',
                    borderColor: '#fecaca'
                });
            });
            
            btnEliminar.addEventListener('mouseout', () => {
                Object.assign(btnEliminar.style, {
                    background: '#f9fafb',
                    color: '#9ca3af',
                    borderColor: '#f3f4f6'
                });
            });
            
            btnEliminar.addEventListener('click', () => {
                fila.remove();
            });
            
            return btnEliminar;
        },

        /**
         * Crear datalist de colores
         */
        crearDatalistColores(datalistId) {
            // Crear datalist
            const datalist = document.createElement('datalist');
            datalist.id = datalistId;
            
            // Cargar colores desde la API y agregar al datalist
            this.cargarColoresWizard().then(colores => {
                colores.forEach(color => {
                    const option = document.createElement('option');
                    option.value = color.nombre;
                    option.dataset.id = color.id;
                    option.dataset.codigo = color.codigo || '';
                    datalist.appendChild(option);
                });
            });
            
            // Agregar datalist al final del documento para que funcione con el input
            document.body.appendChild(datalist);
        },

        /**
         * Cargar colores disponibles desde la API
         */
        async cargarColoresWizard() {
            try {
                const response = await fetch('/api/public/colores');
                const result = await response.json();
                
                if (result.success && result.data) {
                    return result.data.map(color => ({
                        id: color.id,
                        nombre: color.nombre,
                        codigo: color.codigo || ''
                    }));
                }
            } catch (error) {
                console.warn('[UIRenderer] Error cargando colores:', error);
            }
            return [];
        },

        /**
         * Actualizar tabla de asignaciones
         */
        actualizarTablaAsignaciones() {
            const tbody = document.getElementById('tabla-asignaciones-cuerpo');
            const msgSinAsignaciones = document.getElementById('msg-sin-asignaciones');
            const contador = document.getElementById('contador-asignaciones');
            
            // Si los elementos no existen (tabla eliminada), no hacer nada
            if (!tbody || !msgSinAsignaciones) return;
            
            // Limpiar tabla
            tbody.innerHTML = '';
            
            const asignaciones = StateManager.getAsignaciones();
            const asignacionesArray = Object.values(asignaciones);
            
            if (asignacionesArray.length === 0) {
                msgSinAsignaciones.style.display = 'block';
                if (contador) contador.textContent = '0';
                return;
            }
            
            msgSinAsignaciones.style.display = 'none';
            
            let totalAsignaciones = 0;
            
            asignacionesArray.forEach((asignacion) => {
                if (!asignacion.colores) return;
                
                asignacion.colores.forEach((color) => {
                    totalAsignaciones++;
                    
                    const tr = document.createElement('tr');
                    Object.assign(tr.style, { borderBottom: '1px solid #e5e7eb;' });
                    
                    const tallaDisplay = asignacion.talla; // Solo mostrar la talla, sin el tipo
                    
                    tr.innerHTML = `
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937; font-weight: 500;">
                            ${asignacion.genero.toUpperCase()}
                        </td>
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937; font-weight: 500;">
                            ${tallaDisplay}
                        </td>
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937;">
                            ${color.nombre}
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <input type="number" value="${color.cantidad || 0}" min="0" class="form-input" style="width: 70px; text-align: center; padding: 0.5rem;" 
                                onchange="window.ColoresPorTalla.actualizarCantidadAsignacion('${asignacion.genero}', '${asignacion.talla}', '${color.nombre}', this.value)">
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <button type="button" class="btn btn-danger btn-xs btn-eliminar-asignacion" 
                                data-genero="${asignacion.genero}" 
                                data-talla="${asignacion.talla}" 
                                data-color="${color.nombre}" 
                                style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                <span class="material-symbols-rounded" style="font-size: 1rem;">close</span>
                            </button>
                        </td>
                    `;
                    
                    tbody.appendChild(tr);
                });
            });
            
            if (contador) contador.textContent = totalAsignaciones;
            // console.log('[UIRenderer] Tabla actualizada con', totalAsignaciones, 'asignaciones');
            
            // Configurar event delegation para los botones de eliminar
            this.configurarEventosEliminarAsignacion();
        },

        /**
         * Actualizar el resumen de asignaciones
         */
        actualizarResumenAsignaciones() {
            // console.log('[UIRenderer.actualizarResumenAsignaciones]  Iniciando actualización de resumen...');
            
            const tbodyResumen = document.getElementById('tabla-resumen-asignaciones-cuerpo');
            const msgResumenVacio = document.getElementById('msg-resumen-vacio');
            const totalResumen = document.getElementById('total-asignaciones-resumen');
            
            
            if (!tbodyResumen) {
                console.error('[UIRenderer.actualizarResumenAsignaciones]  No se encontró tabla-resumen-asignaciones-cuerpo');
                return;
            }
            
            // Limpiar tabla
            tbodyResumen.innerHTML = '';
            // console.log('[UIRenderer.actualizarResumenAsignaciones] 🧹 Tabla limpiada');
            
            const asignaciones = StateManager.getAsignaciones();
            const asignacionesArray = Object.values(asignaciones);
            
            // console.log('[UIRenderer.actualizarResumenAsignaciones]  Asignaciones en StateManager:', {
            //     cantidad: asignacionesArray.length,
            //     datos: asignacionesArray
            // });
            
            if (asignacionesArray.length === 0) {
                // console.log('[UIRenderer.actualizarResumenAsignaciones]  Sin asignaciones - mostrando mensaje vacío');
                if (msgResumenVacio) msgResumenVacio.style.display = 'block';
                if (totalResumen) totalResumen.textContent = '0';
                return;
            }
            
            // console.log('[UIRenderer.actualizarResumenAsignaciones]  Hay asignaciones - ocultando mensaje vacío');
            if (msgResumenVacio) msgResumenVacio.style.display = 'none';
            
            let totalUnidades = 0;
            let filaCount = 0;
            
            asignacionesArray.forEach((asignacion, asigIndex) => {
                // console.log(`[UIRenderer.actualizarResumenAsignaciones]  Procesando asignación #${asigIndex}:`, asignacion);
                
                if (!asignacion.colores || asignacion.colores.length === 0) {
                    // console.log(`[UIRenderer.actualizarResumenAsignaciones] ⏭️ Sin colores en asignación #${asigIndex}, saltando`);
                    return;
                }
                
                asignacion.colores.forEach((color, colorIndex) => {
                    const cantidad = parseInt(color.cantidad) || 0;
                    totalUnidades += cantidad;
                    filaCount++;
                    
                    // console.log(`[UIRenderer.actualizarResumenAsignaciones] 📝 Fila #${filaCount}: ${asignacion.tela} | ${asignacion.genero} | ${asignacion.talla} | ${color.nombre} | ${cantidad}`);
                    
                    const tr = document.createElement('tr');
                    Object.assign(tr.style, { borderBottom: '1px solid #e5e7eb;' });
                    
                    const tallaDisplay = asignacion.talla; // Solo mostrar la talla, sin el tipo
                    const tela = asignacion.tela || '--';
                    
                    const generoKey = asignacion.genero || '';
                    const claveResumen = `${generoKey.toLowerCase()}-Letra-${tallaDisplay}`;
                    
                    tr.innerHTML = `
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937; font-weight: 500;" data-field="tela">
                            ${tela}
                        </td>
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937; font-weight: 500;" data-field="genero">
                            ${asignacion.genero.toUpperCase()}
                        </td>
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937; font-weight: 500;" data-field="talla">
                            ${tallaDisplay}
                        </td>
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937;" data-field="color">
                            ${color.nombre}
                        </td>
                        <td style="padding: 0.75rem; text-align: center; font-weight: 500;" data-field="cantidad">
                            ${cantidad}
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <div style="display: flex; gap: 0.25rem; justify-content: center;">
                                <button type="button" class="btn-editar-asignacion" 
                                    data-clave="${claveResumen}" data-color="${color.nombre}"
                                    style="background: #dbeafe; border: none; color: #2563eb; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"
                                    title="Editar fila">
                                    ✎
                                </button>
                                <button type="button" class="btn btn-danger btn-xs btn-eliminar-asignacion" 
                                    data-genero="${asignacion.genero}" 
                                    data-talla="${asignacion.talla}" 
                                    data-color="${color.nombre}" 
                                    style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                    <span class="material-symbols-rounded" style="font-size: 1rem;">close</span>
                                </button>
                            </div>
                        </td>
                    `;
                    
                    tbodyResumen.appendChild(tr);
                    // console.log(`[UIRenderer.actualizarResumenAsignaciones]  Fila #${filaCount} añadida a la tabla`);
                });
            });
            
            if (totalResumen) {
                totalResumen.textContent = totalUnidades;
                // console.log('[UIRenderer.actualizarResumenAsignaciones] 🎯 Total actualizado a:', totalUnidades);
            }
            
            // console.log('[UIRenderer.actualizarResumenAsignaciones]  COMPLETADO - Tabla actualizada con', filaCount, 'filas y', totalUnidades, 'unidades totales');
            
            // Configurar edición inline para botones ✎
            this._configurarEdicionInlineResumen(tbodyResumen);
            
            // Configurar event delegation para los botones de eliminar
            this.configurarEventosEliminarAsignacion();
        },

        /**
         * Configurar edición inline en la tabla resumen (botón ✎)
         */
        _configurarEdicionInlineResumen(tablaBody) {
            tablaBody.querySelectorAll('.btn-editar-asignacion').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const fila = btn.closest('tr');
                    const clave = btn.getAttribute('data-clave');
                    const colorNombre = btn.getAttribute('data-color');
                    
                    const tdTela = fila.querySelector('[data-field="tela"]');
                    const tdGenero = fila.querySelector('[data-field="genero"]');
                    const tdTalla = fila.querySelector('[data-field="talla"]');
                    const tdColor = fila.querySelector('[data-field="color"]');
                    const tdCantidad = fila.querySelector('[data-field="cantidad"]');
                    const tdAccion = fila.querySelector('td:last-child');
                    if (!tdTela) return;

                    const orig = {
                        tela: tdTela.textContent.trim(),
                        genero: tdGenero.textContent.trim(),
                        talla: tdTalla.textContent.trim(),
                        color: tdColor.textContent.trim(),
                        cantidad: parseInt(tdCantidad.textContent.trim()) || 0
                    };

                    fila.style.background = '#eff6ff';
                    tdTela.innerHTML = `<input type="text" list="opciones-telas" value="${orig.tela}" style="width:100%;padding:0.35rem;border:1px solid #93c5fd;border-radius:4px;font-size:0.8rem;text-transform:uppercase;" onkeyup="this.value=this.value.toUpperCase()">`;
                    tdGenero.innerHTML = `<select style="width:100%;padding:0.35rem;border:1px solid #93c5fd;border-radius:4px;font-size:0.8rem;"><option value="DAMA" ${orig.genero==='DAMA'?'selected':''}>DAMA</option><option value="CABALLERO" ${orig.genero==='CABALLERO'?'selected':''}>CABALLERO</option><option value="UNISEX" ${orig.genero==='UNISEX'?'selected':''}>UNISEX</option></select>`;
                    tdTalla.innerHTML = `<input type="text" value="${orig.talla}" style="width:100%;padding:0.35rem;border:1px solid #93c5fd;border-radius:4px;font-size:0.8rem;text-transform:uppercase;text-align:center;" onkeyup="this.value=this.value.toUpperCase()">`;
                    tdColor.innerHTML = `<input type="text" list="opciones-colores" value="${orig.color}" style="width:100%;padding:0.35rem;border:1px solid #93c5fd;border-radius:4px;font-size:0.8rem;text-transform:uppercase;" onkeyup="this.value=this.value.toUpperCase()">`;
                    tdCantidad.innerHTML = `<input type="number" min="0" value="${orig.cantidad}" style="width:70px;padding:0.35rem;border:1px solid #93c5fd;border-radius:4px;font-size:0.8rem;text-align:center;font-weight:600;">`;
                    tdAccion.innerHTML = `<div style="display:flex;gap:0.25rem;justify-content:center;"><button type="button" class="btn-guardar-edicion" style="background:#dcfce7;border:none;color:#16a34a;padding:0.25rem 0.5rem;border-radius:4px;cursor:pointer;font-size:0.75rem;font-weight:600;" title="Guardar">✓</button><button type="button" class="btn-cancelar-edicion" style="background:#f3f4f6;border:none;color:#6b7280;padding:0.25rem 0.5rem;border-radius:4px;cursor:pointer;font-size:0.75rem;font-weight:600;" title="Cancelar">✕</button></div>`;

                    const self = UIRenderer;
                    tdAccion.querySelector('.btn-guardar-edicion').addEventListener('click', function(ev) {
                        ev.preventDefault(); ev.stopPropagation();
                        const nTela = tdTela.querySelector('input').value.trim().toUpperCase();
                        const nGenero = tdGenero.querySelector('select').value;
                        const nTalla = tdTalla.querySelector('input').value.trim().toUpperCase();
                        const nColor = tdColor.querySelector('input').value.trim().toUpperCase();
                        const nCantidad = parseInt(tdCantidad.querySelector('input').value) || 0;

                        if (window.StateManager) {
                            const asignaciones = window.StateManager.getAsignaciones();
                            if (asignaciones[clave] && asignaciones[clave].colores) {
                                asignaciones[clave].colores = asignaciones[clave].colores.filter(c => c.nombre !== colorNombre);
                                if (asignaciones[clave].colores.length === 0) delete asignaciones[clave];
                            }
                            const nuevaClave = `${nGenero.toLowerCase()}-Letra-${nTalla}`;
                            if (!asignaciones[nuevaClave]) {
                                asignaciones[nuevaClave] = { genero: nGenero.toLowerCase(), tela: nTela, tipo: 'Letra', talla: nTalla, colores: [] };
                            }
                            asignaciones[nuevaClave].colores.push({ nombre: nColor, cantidad: nCantidad });
                            window.StateManager.setAsignaciones(asignaciones);
                        }
                        self.actualizarResumenAsignaciones();
                        console.log('[UIRenderer] ✅ Fila editada:', { tela: nTela, genero: nGenero, talla: nTalla, color: nColor, cantidad: nCantidad });
                    });

                    tdAccion.querySelector('.btn-cancelar-edicion').addEventListener('click', function(ev) {
                        ev.preventDefault(); ev.stopPropagation();
                        self.actualizarResumenAsignaciones();
                    });
                });
            });
        },

        /**
         * Configurar event delegation para los botones de eliminar asignaciones
         */
        configurarEventosEliminarAsignacion() {
            // Configurar para tabla de asignaciones regular
            const tbodyAsignaciones = document.getElementById('tabla-asignaciones-cuerpo');
            if (tbodyAsignaciones) {
                tbodyAsignaciones.removeEventListener('click', this._handleEliminarClickAsignaciones);
                
                this._handleEliminarClickAsignaciones = (event) => {
                    const btn = event.target.closest('.btn-eliminar-asignacion');
                    if (!btn) return;
                    
                    const genero = btn.dataset.genero;
                    const talla = btn.dataset.talla;
                    const color = btn.dataset.color;
                    
                    console.log('[UIRenderer.configurarEventosEliminarAsignacion]  Eliminando de tabla regular:', { genero, talla, color });
                    
                    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.eliminarAsignacion === 'function') {
                        window.ColoresPorTalla.eliminarAsignacion(genero, talla, color);
                    } else {
                        console.error('[UIRenderer.configurarEventosEliminarAsignacion]  ColoresPorTalla no disponible');
                    }
                };
                
                tbodyAsignaciones.addEventListener('click', this._handleEliminarClickAsignaciones);
            }
            
            // Configurar para tabla de resumen
            const tbodyResumen = document.getElementById('tabla-resumen-asignaciones-cuerpo');
            if (tbodyResumen) {
                tbodyResumen.removeEventListener('click', this._handleEliminarClickResumen);
                
                this._handleEliminarClickResumen = (event) => {
                    const btn = event.target.closest('.btn-eliminar-asignacion');
                    if (!btn) return;
                    
                    const genero = btn.dataset.genero;
                    const talla = btn.dataset.talla;
                    const color = btn.dataset.color;
                    
                    console.log('[UIRenderer.configurarEventosEliminarAsignacion]  Eliminando de tabla resumen:', { genero, talla, color });
                    
                    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.eliminarAsignacion === 'function') {
                        window.ColoresPorTalla.eliminarAsignacion(genero, talla, color);
                    } else {
                        console.error('[UIRenderer.configurarEventosEliminarAsignacion]  ColoresPorTalla no disponible');
                    }
                };
                
                tbodyResumen.addEventListener('click', this._handleEliminarClickResumen);
            }
            
            console.log('[UIRenderer.configurarEventosEliminarAsignacion]  Event delegation configurado para ambas tablas');
        },

        /**
         * Actualizar visibilidad de secciones de resumen
         */
        actualizarVisibilidadSeccionesResumen() {
            
            const seccionTallasCantidades = document.getElementById('seccion-tallas-cantidades');
            const seccionResumenAsignaciones = document.getElementById('seccion-resumen-asignaciones');
            const tieneAsignaciones = StateManager.tieneAsignaciones();
    
            
            if (tieneAsignaciones) {
                // Si hay asignaciones, mostrar resumen y ocultar TALLAS Y CANTIDADES
                if (seccionTallasCantidades) {
                    seccionTallasCantidades.style.display = 'none';
                }
                if (seccionResumenAsignaciones) {
                    seccionResumenAsignaciones.style.display = 'block';
                }
            } else {
                // Si no hay asignaciones, mostrar TALLAS Y CANTIDADES y ocultar resumen
                if (seccionTallasCantidades) {
                    seccionTallasCantidades.style.display = 'block';
                }
                if (seccionResumenAsignaciones) {
                    seccionResumenAsignaciones.style.display = 'none';
                }
            }
            
        },

        /**
         * Cargar y mostrar colores disponibles para asignación
         */
        cargarColoresDispAsignacion() {
            console.log('[UIRenderer] 🔵 Cargando colores disponibles...');
            
            try {
                const contenedor = document.getElementById('lista-colores-checkboxes');
                const seccionPersonalizado = document.getElementById('seccion-agregar-color-personalizado');
                
                console.log('[UIRenderer] Estado:', {
                    contenedorExiste: !!contenedor,
                    seccionPersonalizadoExiste: !!seccionPersonalizado
                });
                
                if (!contenedor) {
                    console.error('[UIRenderer]  No se encontró elemento lista-colores-checkboxes');
                    return;
                }
                
                // Limpiar contenedor
                contenedor.innerHTML = '';
                console.log('[UIRenderer]  Contenedor limpiado');
                
                // Siempre mostrar sección de color personalizado
                if (seccionPersonalizado) {
                    seccionPersonalizado.style.display = 'block';
                    console.log('[UIRenderer]  Sección personalizado MOSTRADA');
                } else {
                    console.error('[UIRenderer]  seccionPersonalizado no existe');
                }
                
                console.log('[UIRenderer]  Completado');
                
            } catch (error) {
                console.error('[UIRenderer]  ERROR FATAL:', error.message);
                console.error('[UIRenderer] Stack:', error.stack);
            }
        }
    };
})();
