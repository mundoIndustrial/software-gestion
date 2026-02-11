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
            
            console.log('[UIRenderer] Interfaz profesional creada para tallas:', tallas);
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
                            <button type="button" class="btn btn-danger btn-xs" onclick="window.ColoresPorTalla.eliminarAsignacion('${asignacion.genero}', '${asignacion.talla}', '${color.nombre}');" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                <span class="material-symbols-rounded" style="font-size: 1rem;">close</span>
                            </button>
                        </td>
                    `;
                    
                    tbody.appendChild(tr);
                });
            });
            
            if (contador) contador.textContent = totalAsignaciones;
            console.log('[UIRenderer] Tabla actualizada con', totalAsignaciones, 'asignaciones');
        },

        /**
         * Actualizar el resumen de asignaciones
         */
        actualizarResumenAsignaciones() {
            console.log('[UIRenderer.actualizarResumenAsignaciones]  Iniciando actualización de resumen...');
            
            const tbodyResumen = document.getElementById('tabla-resumen-asignaciones-cuerpo');
            const msgResumenVacio = document.getElementById('msg-resumen-vacio');
            const totalResumen = document.getElementById('total-asignaciones-resumen');
            
            console.log('[UIRenderer.actualizarResumenAsignaciones] 📍 Elementos del DOM:', {
                tbodyResumen: !!tbodyResumen,
                msgResumenVacio: !!msgResumenVacio,
                totalResumen: !!totalResumen
            });
            
            if (!tbodyResumen) {
                console.error('[UIRenderer.actualizarResumenAsignaciones]  No se encontró tabla-resumen-asignaciones-cuerpo');
                return;
            }
            
            // Limpiar tabla
            tbodyResumen.innerHTML = '';
            console.log('[UIRenderer.actualizarResumenAsignaciones] 🧹 Tabla limpiada');
            
            const asignaciones = StateManager.getAsignaciones();
            const asignacionesArray = Object.values(asignaciones);
            
            console.log('[UIRenderer.actualizarResumenAsignaciones]  Asignaciones en StateManager:', {
                cantidad: asignacionesArray.length,
                datos: asignacionesArray
            });
            
            if (asignacionesArray.length === 0) {
                console.log('[UIRenderer.actualizarResumenAsignaciones]  Sin asignaciones - mostrando mensaje vacío');
                if (msgResumenVacio) msgResumenVacio.style.display = 'block';
                if (totalResumen) totalResumen.textContent = '0';
                return;
            }
            
            console.log('[UIRenderer.actualizarResumenAsignaciones]  Hay asignaciones - ocultando mensaje vacío');
            if (msgResumenVacio) msgResumenVacio.style.display = 'none';
            
            let totalUnidades = 0;
            let filaCount = 0;
            
            asignacionesArray.forEach((asignacion, asigIndex) => {
                console.log(`[UIRenderer.actualizarResumenAsignaciones]  Procesando asignación #${asigIndex}:`, asignacion);
                
                if (!asignacion.colores || asignacion.colores.length === 0) {
                    console.log(`[UIRenderer.actualizarResumenAsignaciones] ⏭️ Sin colores en asignación #${asigIndex}, saltando`);
                    return;
                }
                
                asignacion.colores.forEach((color, colorIndex) => {
                    const cantidad = parseInt(color.cantidad) || 0;
                    totalUnidades += cantidad;
                    filaCount++;
                    
                    console.log(`[UIRenderer.actualizarResumenAsignaciones] 📝 Fila #${filaCount}: ${asignacion.tela} | ${asignacion.genero} | ${asignacion.talla} | ${color.nombre} | ${cantidad}`);
                    
                    const tr = document.createElement('tr');
                    Object.assign(tr.style, { borderBottom: '1px solid #e5e7eb;' });
                    
                    const tallaDisplay = asignacion.talla; // Solo mostrar la talla, sin el tipo
                    const tela = asignacion.tela || '--';
                    
                    tr.innerHTML = `
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937; font-weight: 500;">
                            ${tela}
                        </td>
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937; font-weight: 500;">
                            ${asignacion.genero.toUpperCase()}
                        </td>
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937; font-weight: 500;">
                            ${tallaDisplay}
                        </td>
                        <td style="padding: 0.75rem; text-align: left; color: #1f2937;">
                            ${color.nombre}
                        </td>
                        <td style="padding: 0.75rem; text-align: center; font-weight: 500;">
                            ${cantidad}
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <button type="button" class="btn btn-danger btn-xs" onclick="window.ColoresPorTalla.eliminarAsignacion('${asignacion.genero}', '${asignacion.talla}', '${color.nombre}');" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                <span class="material-symbols-rounded" style="font-size: 1rem;">close</span>
                            </button>
                        </td>
                    `;
                    
                    tbodyResumen.appendChild(tr);
                    console.log(`[UIRenderer.actualizarResumenAsignaciones]  Fila #${filaCount} añadida a la tabla`);
                });
            });
            
            if (totalResumen) {
                totalResumen.textContent = totalUnidades;
                console.log('[UIRenderer.actualizarResumenAsignaciones] 🎯 Total actualizado a:', totalUnidades);
            }
            
            console.log('[UIRenderer.actualizarResumenAsignaciones]  COMPLETADO - Tabla actualizada con', filaCount, 'filas y', totalUnidades, 'unidades totales');
        },

        /**
         * Actualizar visibilidad de secciones de resumen
         */
        actualizarVisibilidadSeccionesResumen() {
            console.log('[UIRenderer.actualizarVisibilidadSeccionesResumen]  Verificando visibilidad de secciones...');
            
            const seccionTallasCantidades = document.getElementById('seccion-tallas-cantidades');
            const seccionResumenAsignaciones = document.getElementById('seccion-resumen-asignaciones');
            const tieneAsignaciones = StateManager.tieneAsignaciones();
            
            console.log('[UIRenderer.actualizarVisibilidadSeccionesResumen] 📍 Elementos del DOM:', {
                seccionTallasCantidades: !!seccionTallasCantidades,
                seccionResumenAsignaciones: !!seccionResumenAsignaciones,
                tieneAsignaciones: tieneAsignaciones
            });
            
            if (tieneAsignaciones) {
                console.log('[UIRenderer.actualizarVisibilidadSeccionesResumen]  Hay asignaciones - mostrando resumen, ocultando tallas');
                // Si hay asignaciones, mostrar resumen y ocultar TALLAS Y CANTIDADES
                if (seccionTallasCantidades) {
                    seccionTallasCantidades.style.display = 'none';
                    console.log('[UIRenderer.actualizarVisibilidadSeccionesResumen]  seccion-tallas-cantidades ocultada');
                }
                if (seccionResumenAsignaciones) {
                    seccionResumenAsignaciones.style.display = 'block';
                    console.log('[UIRenderer.actualizarVisibilidadSeccionesResumen]  seccion-resumen-asignaciones mostrada');
                }
            } else {
                console.log('[UIRenderer.actualizarVisibilidadSeccionesResumen]  Sin asignaciones - mostrando tallas, ocultando resumen');
                // Si no hay asignaciones, mostrar TALLAS Y CANTIDADES y ocultar resumen
                if (seccionTallasCantidades) {
                    seccionTallasCantidades.style.display = 'block';
                    console.log('[UIRenderer.actualizarVisibilidadSeccionesResumen]  seccion-tallas-cantidades mostrada');
                }
                if (seccionResumenAsignaciones) {
                    seccionResumenAsignaciones.style.display = 'none';
                    console.log('[UIRenderer.actualizarVisibilidadSeccionesResumen]  seccion-resumen-asignaciones ocultada');
                }
            }
            
            console.log('[UIRenderer.actualizarVisibilidadSeccionesResumen]  COMPLETADO');
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
