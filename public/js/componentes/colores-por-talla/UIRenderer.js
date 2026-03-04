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
                width: '100%'
            });
            
            // Crear tabla profesional
            const tablaDiv = document.createElement('div');
            Object.assign(tablaDiv.style, {
                display: 'flex',
                flexDirection: 'column',
                gap: '1.5rem',
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
                borderRadius: '8px',
                overflow: 'hidden',
                background: 'white'
            });
            
            // Encabezado de la sección (talla)
            const header = document.createElement('div');
            Object.assign(header.style, {
                background: '#3b82f6',
                padding: '0.5rem 1rem',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between'
            });
            header.innerHTML = `<span style="font-weight: 700; color: white; font-size: 0.95rem;">Talla ${talla}</span>`;
            seccion.appendChild(header);
            
            // Encabezados de columna
            const headerRow = document.createElement('div');
            Object.assign(headerRow.style, {
                display: 'grid',
                gridTemplateColumns: '1fr 120px 80px 140px 36px',
                gap: '0.5rem',
                padding: '0.5rem 0.75rem',
                background: '#f9fafb',
                borderBottom: '1px solid #e5e7eb',
                fontSize: '0.7rem',
                fontWeight: '600',
                color: '#6b7280',
                textTransform: 'uppercase',
                letterSpacing: '0.05em'
            });
            headerRow.innerHTML = `
                <span>Color</span>
                <span>Referencia</span>
                <span style="text-align:center">Cant.</span>
                <span>Imagen</span>
                <span></span>
            `;
            seccion.appendChild(headerRow);
            
            // Contenedor del contenido (filas de color y cantidad)
            const contenedor = document.createElement('div');
            contenedor.className = 'contenedor-colores-' + idx;
            Object.assign(contenedor.style, {
                display: 'flex',
                flexDirection: 'column'
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
         * Agregar una fila de color + cantidad con diseño limpio tipo tabla
         */
        agregarFilaColorCantidad(contenedor, talla, tipo, tallaIdx, colorIdx) {
            // ID único para el datalist
            const datalistId = `colores-list-${tallaIdx}-${colorIdx}`;
            
            // Fila única horizontal con todos los campos
            const fila = document.createElement('div');
            fila.className = `fila-color-${tallaIdx}-${colorIdx}`;
            Object.assign(fila.style, {
                display: 'grid',
                gridTemplateColumns: '1fr 120px 80px 140px 36px',
                gap: '0.5rem',
                alignItems: 'center',
                padding: '0.5rem 0.75rem',
                borderBottom: '1px solid #f3f4f6',
                transition: 'background 0.15s'
            });
            
            // Hover effect
            fila.addEventListener('mouseover', () => { fila.style.background = '#f9fafb'; });
            fila.addEventListener('mouseout', () => { fila.style.background = 'transparent'; });
            
            // 1. Input de color
            const inputColor = this.crearInputColor(talla, tipo, datalistId);
            fila.appendChild(inputColor);
            
            // 2. Input de referencia
            const inputReferencia = this.crearInputReferenciaColor();
            fila.appendChild(inputReferencia);
            
            // 3. Input de cantidad
            const inputCantidad = this.crearInputCantidad();
            fila.appendChild(inputCantidad);
            
            // 4. Input de imagen (botón estilizado)
            const imgWrapper = this.crearBotonImagenTela();
            fila.appendChild(imgWrapper);
            
            // 5. Botón eliminar
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
            inputColor.setAttribute('list', datalistId);
            inputColor.placeholder = 'Ej: ROJO';
            Object.assign(inputColor.style, {
                padding: '0.4rem 0.5rem',
                border: '1px solid #d1d5db',
                borderRadius: '4px',
                fontSize: '0.8rem',
                textTransform: 'uppercase',
                background: 'white',
                width: '100%',
                boxSizing: 'border-box'
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
                padding: '0.4rem 0.25rem',
                border: '1px solid #d1d5db',
                borderRadius: '4px',
                textAlign: 'center',
                fontSize: '0.8rem',
                background: 'white',
                width: '100%',
                boxSizing: 'border-box'
            });
            
            return inputCantidad;
        },

        /**
         * Crear input de referencia del color
         */
        crearInputReferenciaColor() {
            const inputReferencia = document.createElement('input');
            inputReferencia.type = 'text';
            inputReferencia.className = 'referencia-input-wizard';
            inputReferencia.placeholder = 'REF-001';
            Object.assign(inputReferencia.style, {
                padding: '0.4rem 0.5rem',
                border: '1px solid #d1d5db',
                borderRadius: '4px',
                fontSize: '0.8rem',
                textTransform: 'uppercase',
                background: 'white',
                width: '100%',
                boxSizing: 'border-box'
            });
            
            inputReferencia.addEventListener('keyup', function() {
                this.value = this.value.toUpperCase();
            });
            
            return inputReferencia;
        },

        /**
         * Crear widget de imagen con preview, drag & drop, Ctrl+V y eliminar
         */
        crearBotonImagenTela() {
            const wrapper = document.createElement('div');
            wrapper.className = 'imagen-tela-wrapper';
            Object.assign(wrapper.style, {
                position: 'relative',
                width: '100%'
            });
            
            // Input file oculto
            const inputImagen = document.createElement('input');
            inputImagen.type = 'file';
            inputImagen.className = 'imagen-tela-wizard';
            inputImagen.accept = 'image/*';
            Object.assign(inputImagen.style, {
                position: 'absolute',
                width: '0',
                height: '0',
                opacity: '0',
                overflow: 'hidden'
            });
            
            // --- ESTADO: Sin imagen (drop zone + botón) ---
            const dropZone = document.createElement('div');
            dropZone.tabIndex = 0;
            Object.assign(dropZone.style, {
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.25rem',
                width: '100%',
                padding: '0.35rem 0.5rem',
                border: '1px dashed #d1d5db',
                borderRadius: '4px',
                background: '#fafafa',
                color: '#6b7280',
                fontSize: '0.75rem',
                cursor: 'pointer',
                transition: 'all 0.15s',
                boxSizing: 'border-box',
                whiteSpace: 'nowrap',
                overflow: 'hidden',
                outline: 'none'
            });
            dropZone.innerHTML = '<span class="material-symbols-rounded" style="font-size: 0.9rem;">add_photo_alternate</span><span>Subir / Pegar</span>';
            
            // --- ESTADO: Con imagen (preview) ---
            const previewContainer = document.createElement('div');
            Object.assign(previewContainer.style, {
                display: 'none',
                position: 'relative',
                width: '100%',
                borderRadius: '4px',
                overflow: 'hidden',
                border: '1px solid #d1d5db'
            });
            
            const previewImg = document.createElement('img');
            Object.assign(previewImg.style, {
                width: '100%',
                height: '60px',
                objectFit: 'cover',
                display: 'block',
                cursor: 'pointer',
                borderRadius: '3px'
            });
            previewImg.alt = 'Preview tela';
            
            // Botón eliminar imagen (X rojo)
            const btnRemove = document.createElement('button');
            btnRemove.type = 'button';
            Object.assign(btnRemove.style, {
                position: 'absolute',
                top: '2px',
                right: '2px',
                width: '18px',
                height: '18px',
                borderRadius: '50%',
                border: 'none',
                background: '#ef4444',
                color: 'white',
                fontSize: '0.65rem',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '0',
                lineHeight: '1',
                boxShadow: '0 1px 3px rgba(0,0,0,0.3)',
                zIndex: '2'
            });
            btnRemove.innerHTML = '<span class="material-symbols-rounded" style="font-size: 0.75rem;">close</span>';
            
            previewContainer.appendChild(previewImg);
            previewContainer.appendChild(btnRemove);
            
            // === FUNCIÓN: Cargar imagen y mostrar preview ===
            const cargarImagen = (file) => {
                if (!file || !file.type.startsWith('image/')) return;
                
                // Guardar archivo en un DataTransfer para que el input file lo tenga
                const dt = new DataTransfer();
                dt.items.add(file);
                inputImagen.files = dt.files;
                
                // Mostrar preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    dropZone.style.display = 'none';
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            };
            
            // === FUNCIÓN: Eliminar imagen ===
            const eliminarImagen = () => {
                const dt = new DataTransfer();
                inputImagen.files = dt.files;
                previewImg.src = '';
                previewContainer.style.display = 'none';
                dropZone.style.display = 'flex';
                dropZone.style.borderColor = '#d1d5db';
                dropZone.style.background = '#fafafa';
            };
            
            // --- EVENTO: Click en drop zone abre file dialog ---
            dropZone.addEventListener('click', () => inputImagen.click());
            
            // --- EVENTO: Click en preview abre file dialog para cambiar ---
            previewImg.addEventListener('click', () => inputImagen.click());
            
            // --- EVENTO: Botón eliminar ---
            btnRemove.addEventListener('click', (e) => {
                e.stopPropagation();
                eliminarImagen();
            });
            
            // --- EVENTO: Input file change ---
            inputImagen.addEventListener('change', () => {
                if (inputImagen.files.length) {
                    cargarImagen(inputImagen.files[0]);
                }
            });
            
            // --- EVENTO: Drag & Drop ---
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#3b82f6';
                dropZone.style.background = '#eff6ff';
                dropZone.style.borderStyle = 'solid';
            });
            
            dropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#d1d5db';
                dropZone.style.background = '#fafafa';
                dropZone.style.borderStyle = 'dashed';
            });
            
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#d1d5db';
                dropZone.style.background = '#fafafa';
                dropZone.style.borderStyle = 'dashed';
                
                const files = e.dataTransfer.files;
                if (files.length && files[0].type.startsWith('image/')) {
                    cargarImagen(files[0]);
                }
            });
            
            // --- EVENTO: Ctrl+V (paste) ---
            dropZone.addEventListener('paste', (e) => {
                e.preventDefault();
                const items = e.clipboardData?.items;
                if (!items) return;
                
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.startsWith('image/')) {
                        const file = items[i].getAsFile();
                        if (file) cargarImagen(file);
                        break;
                    }
                }
            });
            
            // --- EVENTO: Hover en drop zone ---
            dropZone.addEventListener('mouseover', () => {
                if (previewContainer.style.display === 'none') {
                    dropZone.style.borderColor = '#3b82f6';
                    dropZone.style.color = '#3b82f6';
                    dropZone.style.background = '#eff6ff';
                }
            });
            dropZone.addEventListener('mouseout', () => {
                if (previewContainer.style.display === 'none') {
                    dropZone.style.borderColor = '#d1d5db';
                    dropZone.style.color = '#6b7280';
                    dropZone.style.background = '#fafafa';
                }
            });
            
            // --- EVENTO: Focus para Ctrl+V ---
            dropZone.addEventListener('focus', () => {
                dropZone.style.borderColor = '#3b82f6';
                dropZone.style.boxShadow = '0 0 0 2px rgba(59,130,246,0.2)';
            });
            dropZone.addEventListener('blur', () => {
                if (previewContainer.style.display === 'none') {
                    dropZone.style.borderColor = '#d1d5db';
                }
                dropZone.style.boxShadow = 'none';
            });
            
            wrapper.appendChild(inputImagen);
            wrapper.appendChild(dropZone);
            wrapper.appendChild(previewContainer);
            return wrapper;
        },

        /**
         * Crear input de observaciones (compacto, una línea) - COMENTADO
         */
        // crearInputObservaciones() {
        //     const inputObs = document.createElement('input');
        //     inputObs.type = 'text';
        //     inputObs.className = 'observaciones-input-wizard';
        //     inputObs.placeholder = 'Notas...';
        //     Object.assign(inputObs.style, {
        //         padding: '0.4rem 0.5rem',
        //         border: '1px solid #d1d5db',
        //         borderRadius: '4px',
        //         fontSize: '0.8rem',
        //         background: 'white',
        //         width: '100%',
        //         boxSizing: 'border-box'
        //     });
        //     
        //     return inputObs;
        // }

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
         * Delega a ColoresPorTalla.actualizarTablaResumen() que maneja la tabla unificada
         */
        actualizarResumenAsignaciones() {
            if (window.ColoresPorTalla && typeof window.ColoresPorTalla.actualizarTablaResumen === 'function') {
                window.ColoresPorTalla.actualizarTablaResumen();
            }
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
            const tieneTelasSimples = (window.telasCreacion && window.telasCreacion.length > 0);
    
            
            if (tieneAsignaciones || tieneTelasSimples) {
                // Si hay asignaciones o telas simples, mostrar resumen
                if (tieneAsignaciones && seccionTallasCantidades) {
                    seccionTallasCantidades.style.display = 'none';
                }
                if (seccionResumenAsignaciones) {
                    seccionResumenAsignaciones.style.display = 'block';
                }
            } else {
                // Si no hay nada, mostrar TALLAS Y CANTIDADES y ocultar resumen
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
