// Arrays y variables globales
let contadorProductosReflectivo = 0;

// FUNCIÓN PARA ABRIR MODAL ESPECIFICACIONES
function abrirModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    const especificacionesGuardadas = document.getElementById('especificaciones').value;

    // Si hay especificaciones guardadas, cargarlas en los checkboxes y observaciones
    if (especificacionesGuardadas && especificacionesGuardadas !== '{}' && especificacionesGuardadas !== '[]' && especificacionesGuardadas !== '') {
        try {
            const datos = JSON.parse(especificacionesGuardadas);
            console.log(' Estructura de datos:', Object.keys(datos));
            
            // FORMATO 1: Estructura con forma_pago, disponibilidad, etc (desde cotizaciones.especificaciones)
            // FORMATO 2: Estructura tabla_orden[field] (desde modal anterior)
            
            // Si tiene estructura de array (forma_pago, disponibilidad, etc)
            if (datos.forma_pago || datos.disponibilidad || datos.regimen) {
                // Procesar FORMA_PAGO
                if (datos.forma_pago && Array.isArray(datos.forma_pago)) {
                    datos.forma_pago.forEach((pago) => {
                        // Normalizar el valor para buscar checkbox
                        let valorNormalizado = pago.valor.toLowerCase();
                        if (valorNormalizado === 'crédito' || valorNormalizado === 'credito') {
                            valorNormalizado = 'credito';
                        }
                        
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        let checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            // Cargar observación si existe
                            if (pago.observacion) {
                                let obsName;
                                if (valorNormalizado === 'contado') {
                                    obsName = 'tabla_orden[pago_contado_obs]';
                                } else if (valorNormalizado === 'credito') {
                                    obsName = 'tabla_orden[pago_credito_obs]';
                                }
                                
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = pago.observacion;
                                }
                            }
                        }
                    });
                }
                
                // Procesar DISPONIBILIDAD
                if (datos.disponibilidad && Array.isArray(datos.disponibilidad)) {
                    datos.disponibilidad.forEach((disp) => {
                        const valorNormalizado = disp.valor.toLowerCase();
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        const checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            if (disp.observacion) {
                                const obsName = `tabla_orden[${valorNormalizado}_obs]`;
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = disp.observacion;
                                }
                            }
                        }
                    });
                }
                
                // Procesar RÉGIMEN
                if (datos.regimen && Array.isArray(datos.regimen)) {
                    datos.regimen.forEach((reg) => {
                        const valorNormalizado = reg.valor.toLowerCase();
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        const checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            if (reg.observacion) {
                                let obsName;
                                if (valorNormalizado === 'común' || valorNormalizado === 'comun') {
                                    obsName = 'tabla_orden[regimen_comun_obs]';
                                } else if (valorNormalizado === 'simplificado') {
                                    obsName = 'tabla_orden[regimen_simp_obs]';
                                }
                                
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = reg.observacion;
                                }
                            }
                        }
                    });
                }
                
                // Procesar SE HA VENDIDO
                if (datos.se_ha_vendido && Array.isArray(datos.se_ha_vendido)) {
                    const tbodyVendido = document.querySelector('#tbody_vendido');
                    if (tbodyVendido) {
                        datos.se_ha_vendido.forEach((vendido) => {
                            const firstRow = tbodyVendido.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="vendido_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="vendido"]');
                                const obsInput = firstRow.querySelector('input[name*="vendido_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = vendido.valor;
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                                if (obsInput) {
                                    obsInput.value = vendido.observacion || '';
                                }
                            }
                        });
                    }
                }
                
                // Procesar ÚLTIMA VENTA
                if (datos.ultima_venta && Array.isArray(datos.ultima_venta)) {
                    const tbodyUltimaVenta = document.querySelector('#tbody_ultima_venta');
                    if (tbodyUltimaVenta) {
                        datos.ultima_venta.forEach((ultimaVenta) => {
                            const firstRow = tbodyUltimaVenta.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="ultima_venta_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="ultima_venta"]');
                                const obsInput = firstRow.querySelector('input[name*="ultima_venta_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = ultimaVenta.valor;
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                                if (obsInput) {
                                    obsInput.value = ultimaVenta.observacion || '';
                                }
                            }
                        });
                    }
                }
                
                // Procesar FLETE
                if (datos.flete && Array.isArray(datos.flete)) {
                    const tbodyFlete = document.querySelector('#tbody_flete');
                    if (tbodyFlete) {
                        datos.flete.forEach((flete) => {
                            const firstRow = tbodyFlete.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="flete_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="flete"]');
                                const obsInput = firstRow.querySelector('input[name*="flete_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = flete.valor;
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                                if (obsInput) {
                                    obsInput.value = flete.observacion || '';
                                }
                            }
                        });
                    }
                }
            } else {
                // FORMATO 2: Estructura tabla_orden[field] (anterior)
                Object.keys(datos).forEach((key) => {
                    const element = document.querySelector(`[name="${key}"]`);
                    if (element) {
                        if (element.type === 'checkbox') {
                            element.checked = datos[key] === '1' || datos[key] === true;
                        } else {
                            element.value = datos[key] || '';
                        }
                    }
                });
            }
        } catch (e) {
        }
    } else {
        // Limpiar todos los checkboxes si no hay especificaciones guardadas
        document.querySelectorAll('[name^="tabla_orden"]').forEach((element) => {
            if (element.type === 'checkbox') {
                element.checked = false;
            } else if (element.type === 'text') {
                element.value = '';
            }
        });
    }
    
    if (modal) {
        modal.style.display = 'flex';
    }
}

// FUNCIÓN PARA CERRAR MODAL ESPECIFICACIONES
function cerrarModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    if (modal) {
        modal.style.display = 'none';
    }
}

// FUNCIÓN PARA GUARDAR ESPECIFICACIONES
function guardarEspecificacionesReflectivo() {
    // Estructura final en formato cotizaciones.especificaciones
    const especificaciones = {
        forma_pago: [],
        disponibilidad: [],
        regimen: [],
        se_ha_vendido: [],
        ultima_venta: [],
        flete: []
    };
    
    const modal = document.getElementById('modalEspecificaciones');
    if (!modal) {
        return;
    }
    
    // PROCESAR FORMA_PAGO
    const formaPagoCheckboxes = [
        { checkbox: 'contado', label: 'Contado', obsField: 'pago_contado_obs' },
        { checkbox: 'credito', label: 'Crédito', obsField: 'pago_credito_obs' }
    ];
    
    formaPagoCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.forma_pago.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
        }
    });
    
    // PROCESAR DISPONIBILIDAD
    const disponibilidadCheckboxes = [
        { checkbox: 'bodega', label: 'Bodega', obsField: 'bodega_obs' },
        { checkbox: 'cucuta', label: 'Cúcuta', obsField: 'cucuta_obs' },
        { checkbox: 'lafayette', label: 'Lafayette', obsField: 'lafayette_obs' },
        { checkbox: 'fabrica', label: 'Fábrica', obsField: 'fabrica_obs' }
    ];
    
    disponibilidadCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.disponibilidad.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
        }
    });
    
    // PROCESAR RÉGIMEN
    const regimenCheckboxes = [
        { checkbox: 'comun', label: 'Común', obsField: 'regimen_comun_obs' },
        { checkbox: 'simplificado', label: 'Simplificado', obsField: 'regimen_simp_obs' }
    ];
    
    regimenCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.regimen.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
        }
    });
    
    // PROCESAR SE HA VENDIDO
    const tbodySeHaVendido = modal.querySelector('#tbody_vendido');
    if (tbodySeHaVendido) {
        const rows = tbodySeHaVendido.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name="tabla_orden[vendido_item]"]');
            const checkbox = row.querySelector('input[type="checkbox"][name="tabla_orden[vendido]"]');
            const obsInput = row.querySelector('input[name="tabla_orden[vendido_obs]"]');
            //  Si el checkbox está marcado, guardar aunque el valor esté vacío
            if (checkbox && checkbox.checked) {
                const valorTexto = valorInput?.value.trim() || 'Sí'; // Valor por defecto "Sí" si está vacío
                especificaciones.se_ha_vendido.push({
                    valor: valorTexto,
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
            }
        });
    }
    
    // PROCESAR ÚLTIMA VENTA
    const tbodyUltimaVenta = modal.querySelector('#tbody_ultima_venta');
    if (tbodyUltimaVenta) {
        const rows = tbodyUltimaVenta.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name="tabla_orden[ultima_venta_item]"]');
            const checkbox = row.querySelector('input[type="checkbox"][name="tabla_orden[ultima_venta]"]');
            const obsInput = row.querySelector('input[name="tabla_orden[ultima_venta_obs]"]');
            //  Si el checkbox está marcado, guardar aunque el valor esté vacío
            if (checkbox && checkbox.checked) {
                const valorTexto = valorInput?.value.trim() || 'Sí'; // Valor por defecto "Sí" si está vacío
                especificaciones.ultima_venta.push({
                    valor: valorTexto,
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
            }
        });
    }
    
    // PROCESAR FLETE
    const tbodyFlete = modal.querySelector('#tbody_flete');
    if (tbodyFlete) {
        const rows = tbodyFlete.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name="tabla_orden[flete_item]"]');
            const checkbox = row.querySelector('input[type="checkbox"][name="tabla_orden[flete]"]');
            const obsInput = row.querySelector('input[name="tabla_orden[flete_obs]"]');
            //  Si el checkbox está marcado, guardar aunque el valor esté vacío
            if (checkbox && checkbox.checked) {
                const valorTexto = valorInput?.value.trim() || 'Sí'; // Valor por defecto "Sí" si está vacío
                especificaciones.flete.push({
                    valor: valorTexto,
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
            }
        });
    }
    
    // Convertir a JSON string y guardar en campo oculto
    const especificacionesJSON = JSON.stringify(especificaciones);
    document.getElementById('especificaciones').value = especificacionesJSON;


    cerrarModalEspecificaciones();
}

// FUNCIÓN PARA AGREGAR FILA DE ESPECIFICACIÓN
function agregarFilaEspecificacion(seccion) {
}

// FUNCIONES PARA AGREGAR/ELIMINAR PRODUCTOS DE REFLECTIVO
function agregarProductoPrenda() {
    contadorProductosReflectivo++;
    const template = document.getElementById('productoReflectivoTemplate');
    const clone = template.content.cloneNode(true);
    
    // Actualizar el número de prenda
    clone.querySelector('.numero-producto').textContent = contadorProductosReflectivo;
    
    // Agregar al contenedor
    const container = document.getElementById('prendas-contenedor');
    container.appendChild(clone);

    // Inicializar variaciones por defecto para la nueva prenda
    setTimeout(() => {
        const ultimoPrenda = container.lastElementChild;
        if (ultimoPrenda) {
            inicializarVariacionesDefault(ultimoPrenda);
        }
    }, 50);
}

function eliminarProductoPrenda(button) {
    const card = button.closest('.producto-card');
    card.remove();
    renumerarPrendas();
}

function renumerarPrendas() {
    const prendas = document.querySelectorAll('.producto-card');
    prendas.forEach((prenda, index) => {
        prenda.querySelector('.numero-producto').textContent = index + 1;
    });
}

function toggleProductoBody(button) {
    const body = button.closest('.producto-card').querySelector('.producto-body');
    body.style.display = body.style.display === 'none' ? 'block' : 'none';
    button.textContent = body.style.display === 'none' ? '▶' : '▼';
}

function toggleSeccionReflectivo(titleElement) {
    const icon = titleElement.querySelector('.fa-chevron-down');
    const secciones = titleElement.parentElement.nextElementSibling;
    
    if (secciones) {
        secciones.style.display = secciones.style.display === 'none' ? 'block' : 'none';
        if (icon) {
            icon.style.transform = secciones.style.display === 'none' ? 'rotate(0deg)' : 'rotate(180deg)';
        }
    }
}

function agregarFotosAlProductoReflectivo(input) {
    const files = input.files;
    const preview = input.closest('.producto-section').querySelector('.fotos-preview-reflectivo');
    const previewCount = preview.querySelectorAll('img').length;
    
    if (previewCount + files.length > 3) {
        alert('Máximo 3 imágenes permitidas');
        input.value = '';
        return;
    }
    
    // Obtener archivos existentes del input (si los hay)
    const existingFiles = input._storedFiles || [];
    const newFiles = Array.from(files);
    
    // Combinar archivos existentes con nuevos
    const allFiles = [...existingFiles, ...newFiles];
    
    // Crear previews solo para los nuevos archivos
    newFiles.forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; aspect-ratio: 1;';
                div.setAttribute('data-file-index', existingFiles.length + index);
                div.innerHTML = `
                    <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                    <button type="button" onclick="eliminarImagenReflectivo(this)" style="position: absolute; top: 2px; right: 2px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center;">×</button>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Guardar todos los archivos en el input usando DataTransfer
    const dataTransfer = new DataTransfer();
    allFiles.forEach(file => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
    
    // Almacenar referencia para futuras adiciones
    input._storedFiles = allFiles;
}

function eliminarImagenReflectivo(button) {
    const div = button.parentElement;
    const fileIndex = parseInt(div.getAttribute('data-file-index'));
    const preview = div.parentElement;
    const input = preview.closest('.producto-section').querySelector('.input-file-reflectivo');
    
    // Obtener archivos actuales
    const currentFiles = input._storedFiles || Array.from(input.files);
    
    // Eliminar el archivo del índice especificado
    currentFiles.splice(fileIndex, 1);
    
    // Actualizar el input con los archivos restantes
    const dataTransfer = new DataTransfer();
    currentFiles.forEach(file => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
    input._storedFiles = currentFiles;
    
    // Eliminar preview del DOM
    div.remove();
    
    // Renumerar los índices de los divs restantes
    preview.querySelectorAll('[data-file-index]').forEach((d, idx) => {
        d.setAttribute('data-file-index', idx);
    });
}

function abrirModalUbicacion(button) {
    const input = button.previousElementSibling;
    const ubicacion = input.value.trim();
    
    if (!ubicacion) {
        input.style.border = '2px solid #ef4444';
        setTimeout(() => input.style.border = '', 1500);
        return;
    }
    
    // Guardar el botón e input para usar después
    window.ubicacionModalData = {
        button: button,
        input: input,
        ubicacion: ubicacion,
        prenda: button.closest('.producto-card')
    };
    
    // Mostrar modal con la ubicación escrita
    document.getElementById('modalUbicacionNombre').textContent = ubicacion;
    document.getElementById('modalUbicacionTextarea').value = '';
    document.getElementById('modalUbicacionReflectivo').style.display = 'flex';
    
    // Focus en el textarea
    setTimeout(() => document.getElementById('modalUbicacionTextarea').focus(), 100);
}

function cerrarModalUbicacion() {
    document.getElementById('modalUbicacionReflectivo').style.display = 'none';
}

function guardarUbicacionReflectivo() {
    const textarea = document.getElementById('modalUbicacionTextarea');
    const observacion = textarea.value.trim();
    
    if (!window.ubicacionModalData) return;
    
    const { ubicacion, prenda } = window.ubicacionModalData;
    const container = prenda.querySelector('.ubicaciones-agregadas-reflectivo');
    
    if (!container) return;
    
    // Crear elemento de ubicación como componente expandible
    const item = document.createElement('div');
    item.className = 'ubicacion-item-reflectivo'; // ADD CLASS FOR EASY IDENTIFICATION
    item.style.cssText = 'background: white; border: 2px solid #0ea5e9; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; width: 100%; box-shadow: 0 2px 4px rgba(14, 165, 233, 0.15); position: relative;';
    
    const header = document.createElement('div');
    header.className = 'ubicacion-header-reflectivo'; // ADD CLASS
    header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; cursor: pointer;';
    header.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
            <span style="color: #0ea5e9; font-weight: 700; font-size: 1rem;"></span>
            <span class="ubicacion-nombre-reflectivo" style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">${ubicacion}</span>
        </div>
        <span style="color: #0ea5e9; font-size: 1.2rem; transition: transform 0.3s ease;" class="ubicacion-toggle">▼</span>
    `;
    
    const body = document.createElement('div');
    body.className = 'ubicacion-body-reflectivo'; // ADD CLASS
    body.style.cssText = 'display: block; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0;';
    body.innerHTML = `
        <p style="margin: 0 0 0.5rem 0; color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Descripción:</p>
        <p class="ubicacion-descripcion-reflectivo" style="margin: 0; color: #334155; font-size: 0.9rem; line-height: 1.5;">${observacion || 'Sin descripción adicional'}</p>
    `;
    
    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.style.cssText = 'position: absolute; top: 0.5rem; right: 0.5rem; background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: bold;';
    deleteBtn.textContent = '×';
    deleteBtn.onclick = (e) => {
        e.stopPropagation();
        item.remove();
    };
    
    item.appendChild(header);
    header.appendChild(deleteBtn);
    item.appendChild(body);
    
    // Toggle para expandir/contraer
    let expanded = true;
    header.addEventListener('click', () => {
        expanded = !expanded;
        body.style.display = expanded ? 'block' : 'none';
        header.querySelector('.ubicacion-toggle').style.transform = expanded ? 'rotate(0deg)' : 'rotate(-90deg)';
    });
    
    container.appendChild(item);
    
    // Limpiar y cerrar modal
    window.ubicacionModalData.input.value = '';
    cerrarModalUbicacion();
}

// Sincronizar valores del header con el formulario
document.getElementById('header-cliente').addEventListener('input', function() {
    document.getElementById('cliente').value = this.value;
});

document.getElementById('header-fecha').addEventListener('change', function() {
    document.getElementById('fecha').value = this.value;
});

// Envío del formulario
document.getElementById('cotizacionReflectivoForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Sincronizar valores del header
    const cliente = document.getElementById('header-cliente').value.trim();
    const fecha = document.getElementById('header-fecha').value;

    if (!cliente || !fecha) {
        alert(' Completa el Cliente y la Fecha');
        return;
    }

    //  RECOPILAR PRENDAS CON SUS TALLAS Y UBICACIONES (POR PRENDA)
    const prendas = [];
    document.querySelectorAll('.producto-card').forEach((prenda, index) => {
        const tipo = prenda.querySelector('[name*="tipo_prenda"]')?.value || '';
        const descripcion = prenda.querySelector('[name*="descripcion"]')?.value || '';
        
        //  RECOPILAR GÉNERO DE ESTA PRENDA
        const genero = prenda.querySelector('.talla-genero-select-reflectivo')?.value || '';
        
        //  RECOPILAR TALLAS Y CANTIDADES
        const tallas = [];
        const cantidades = {};
        // Primero intentar leer desde el campo oculto
        const tallasHidden = prenda.querySelector('.tallas-hidden-reflectivo');

        if (tallasHidden && tallasHidden.value) {
            const tallasArray = tallasHidden.value.split(',').map(t => t.trim()).filter(t => t);
            tallasArray.forEach(talla => {
                tallas.push(talla);
                cantidades[talla] = 1; // Valor por defecto
            });
        } else {
            // Fallback: leer desde el DOM visual
            const tallasContainer = prenda.querySelector('.tallas-agregadas-reflectivo');
            if (tallasContainer) {
                const spans = tallasContainer.querySelectorAll('div > span:first-child');
                spans.forEach(span => {
                    const tallaText = span.textContent.trim();
                    if (tallaText) {
                        tallas.push(tallaText);
                        cantidades[tallaText] = 1; // Valor por defecto
                    }
                });
            }
        }

        //  RECOPILAR UBICACIONES DE ESTA PRENDA ESPECÍFICA
        const ubicacionesDePrenda = [];
        const ubicacionesContainer = prenda.querySelector('.ubicaciones-agregadas-reflectivo');
        if (ubicacionesContainer) {
            ubicacionesContainer.querySelectorAll('.ubicacion-item-reflectivo').forEach((item) => {
                const nombreSpan = item.querySelector('.ubicacion-nombre-reflectivo');
                const descripcionP = item.querySelector('.ubicacion-descripcion-reflectivo');
                
                if (nombreSpan && descripcionP) {
                    const ubicacionText = nombreSpan.textContent.trim();
                    const descripcionUbi = descripcionP.textContent.trim();
                    
                    if (ubicacionText && ubicacionText !== 'Sin descripción adicional') {
                        ubicacionesDePrenda.push({
                            ubicacion: ubicacionText,
                            descripcion: descripcionUbi
                        });
                    }
                }
            });
        }

        //  RECOPILAR VARIACIONES DE ESTA PRENDA ESPECÍFICA
        let variacionesDePrenda = [];
        const variacionesJsonInput = prenda.querySelector('.variaciones-json-reflectivo');
        if (variacionesJsonInput && variacionesJsonInput.value) {
            try {
                const variacionesData = JSON.parse(variacionesJsonInput.value);
                if (Array.isArray(variacionesData)) {
                    variacionesDePrenda = variacionesData;
                }
            } catch (e) {
                console.warn('Error al parsear variaciones JSON:', e);
            }
        }

        //  RECOPILAR COLOR, TELA, REFERENCIA E IMÁGENES DE TELA DE ESTA PRENDA
        let colorTelaRefDePrenda = [];
        const tbody = prenda.querySelector('.telas-tbody-reflectivo');
        if (tbody) {
            tbody.querySelectorAll('tr.fila-tela-reflectivo').forEach((fila, telaIndex) => {
                const color = fila.querySelector('.color-input-reflectivo')?.value || '';
                const tela = fila.querySelector('.tela-input-reflectivo')?.value || '';
                const referencia = fila.querySelector('.referencia-input-reflectivo')?.value || '';
                const colorId = fila.querySelector('.color-id-input-reflectivo')?.value || null;
                const telaId = fila.querySelector('.tela-id-input-reflectivo')?.value || null;
                
                // Solo agregar si hay al menos un campo con valor
                if (color || tela || referencia) {
                    const telaObj = {
                        indice: telaIndex,
                        color: color,
                        color_id: colorId,
                        tela: tela,
                        tela_id: telaId,
                        referencia: referencia,
                        fotos: []  // Se agregarán luego
                    };
                    
                    colorTelaRefDePrenda.push(telaObj);
                }
            });
        }

        if (tipo.trim()) {
            prendas.push({
                tipo: tipo,
                descripcion: descripcion,
                tallas: tallas,
                genero: genero,  //  AGREGAR GÉNERO
                cantidades: cantidades,  //  AGREGAR CANTIDADES POR TALLA
                ubicaciones: ubicacionesDePrenda,  //  Ubicaciones específicas de esta prenda
                variaciones: variacionesDePrenda,  //  Variaciones específicas de esta prenda
                color_tela_ref: colorTelaRefDePrenda  //  Color, Tela, Referencia e imágenes de tela
            });
            
            console.log(` Prenda ${index + 1}: ${tipo}`);
            console.log(`    Ubicaciones: ${ubicacionesDePrenda.length}`);
            console.log(`   Variaciones: ${variacionesDePrenda.length}`);
            console.log(`   Variaciones Detalles:`, variacionesDePrenda);
            console.log(`   Color/Tela/Ref: ${colorTelaRefDePrenda.length}`);
            console.log(`   Género: ${genero || 'No especificado'}`);
            console.log(`    Tallas: ${tallas.length > 0 ? tallas.join(', ') : 'Ninguna'}`);
        }
    });

    if (prendas.length === 0) {
        alert(' Debes agregar al menos una PRENDA con TIPO');
        return;
    }

    //  Las ubicaciones ya están incluidas en cada objeto de prenda
    // Ya no necesitamos recopilarlas por separado

    const submitButton = e.submitter;
    const action = submitButton ? submitButton.value : 'borrador';

    // Preparar FormData
    const formData = new FormData();
    formData.append('cliente', cliente);
    formData.append('asesora', document.getElementById('asesora').value);
    formData.append('fecha', fecha);
    formData.append('action', action);
    formData.append('tipo', 'RF');
    formData.append('tipo_venta_reflectivo', document.getElementById('header-tipo-venta').value);
    
    // DEBUG: Log de datos que se envían
    // DEBUG: Log de datos que se envían




    console.log('   tipo_venta:', document.getElementById('header-tipo-venta').value);
    console.log('   prendas completas:', JSON.stringify(prendas, null, 2));
    
    formData.append('prendas', JSON.stringify(prendas)); //  Enviar prendas con ubicaciones incluidas
    formData.append('especificaciones', document.getElementById('especificaciones').value || '');
    formData.append('descripcion_reflectivo', document.getElementById('descripcion_reflectivo')?.value || 'Reflectivo');
    formData.append('observaciones_generales', JSON.stringify([]));

    // DEBUG: Log de prendas con ubicaciones
    prendas.forEach((p, i) => {
        console.log(`🔵 Prenda ${i}: ${p.tipo}`, {
            ubicaciones: p.ubicaciones,
            variaciones: p.variaciones,
            tallas: p.tallas
        });
    });

    //  AGREGAR IMÁGENES DE TELA POR PRENDA Y POR TELA CON SUS ÍNDICES
    document.querySelectorAll('.producto-card').forEach((prenda, prendaIndex) => {
        const tbody = prenda.querySelector('.telas-tbody-reflectivo');
        if (tbody) {
            tbody.querySelectorAll('tr.fila-tela-reflectivo').forEach((fila, telaIndex) => {
                const inputFile = fila.querySelector('.input-file-tela-reflectivo');
                if (inputFile && inputFile.files.length > 0) {
                    Array.from(inputFile.files).forEach((file, fileIdx) => {
                        // Enviar imagen con estructura: prendas[prendaIndex][telas][telaIndex][fotos][]
                        const campoNombre = `prendas[${prendaIndex}][telas][${telaIndex}][fotos][]`;
                        formData.append(campoNombre, file);
                        console.log(`  📸 Imagen tela - Prenda ${prendaIndex}, Tela ${telaIndex}: "${file.name}" → "${campoNombre}"`);
                    });
                }
            });
        }
    });

    //  AGREGAR IMÁGENES POR PRENDA CON SU ÍNDICE
    document.querySelectorAll('.producto-card').forEach((prenda, prendaIndex) => {
        const input = prenda.querySelector('.input-file-reflectivo');
        const filesLength = input?.files.length ?? 'N/A';
        if (input && input.files.length > 0) {
            Array.from(input.files).forEach((file, fileIdx) => {
                // Agregar imagen con índice de prenda
                const campoNombre = 'imagenes_reflectivo_prenda_' + prendaIndex + '[]';
                formData.append(campoNombre, file);
                console.log('     Imagen ' + (fileIdx + 1) + ': "' + file.name + '" → "' + campoNombre + '"');
            });
        } else {
        }
    });

    // Agregar fotos eliminadas
    if (fotosEliminadas.length > 0) {
        formData.append('imagenes_a_eliminar', JSON.stringify(fotosEliminadas));
    }

    try {
        // Determinar ruta y método según si es edición o creación
        let url, metodo, bodyData;
        
        if (window.esEdicion && window.cotizacionIdActual) {
            // EDICIÓN: Usar POST con _method=PUT para compatibilidad con FormData
            url = '/asesores/cotizaciones/reflectivo/' + window.cotizacionIdActual;
            metodo = 'POST'; //  Cambiar a POST
            // Limpiar FormData anterior y reconstruir con datos de edición
            const editFormData = new FormData();
            editFormData.append('_method', 'PUT'); //  Simular PUT con POST
            editFormData.append('cliente', cliente);
            editFormData.append('asesora', document.getElementById('asesora').value);
            editFormData.append('fecha', fecha);
            editFormData.append('action', action);
            editFormData.append('tipo', 'RF');
            editFormData.append('tipo_venta_reflectivo', document.getElementById('header-tipo-venta').value);
            editFormData.append('prendas', JSON.stringify(prendas.length > 0 ? prendas : []));
            editFormData.append('especificaciones', document.getElementById('especificaciones').value || '');
            editFormData.append('descripcion_reflectivo', document.getElementById('descripcion_reflectivo')?.value || 'Reflectivo');
            editFormData.append('observaciones_generales', JSON.stringify([]));
            
            //  AGREGAR IMÁGENES POR PRENDA (IGUAL QUE EN CREACIÓN)
            document.querySelectorAll('.producto-card').forEach((prenda, prendaIndex) => {
                const input = prenda.querySelector('.input-file-reflectivo');
                if (input && input.files.length > 0) {
                    Array.from(input.files).forEach((file) => {
                        editFormData.append(`imagenes_reflectivo_prenda_${prendaIndex}[]`, file);
                    });
                }
            });
            
            // Agregar fotos eliminadas
            if (fotosEliminadas.length > 0) {
                editFormData.append('imagenes_a_eliminar', JSON.stringify(fotosEliminadas));
            }
            
            bodyData = editFormData;
        } else {
            // CREACIÓN: Usar POST storeReflectivo con FormData
            url = '/asesores/cotizaciones/reflectivo/guardar';
            metodo = 'POST';
            bodyData = formData;
        }
        
        const response = await fetch(url, {
            method: metodo, // Siempre POST ahora
            body: bodyData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        });

        console.log('📤 SOLICITUD ENVIADA:', {
            url: url,
            metodo: metodo,
            formDataKeys: bodyData instanceof FormData ? Array.from(bodyData.keys()) : 'No es FormData',
        });

        const result = await response.json();

        console.log('🔵 RESPUESTA DEL SERVIDOR:', {
            status: response.status,
            success: result.success,
            message: result.message,
            errors: result.errors,
            errores: result.errores,
        });

        if (result.success) {
            // Mostrar modal de éxito
            const titulo = action === 'borrador' ? 'Cotización guardada como borrador ✓' : 'Cotización enviada al contador ✓';
            const mensaje = action === 'borrador' 
                ? 'Tu cotización ha sido guardada correctamente como borrador. Podrás seguir editándola cuando lo necesites.'
                : 'Tu cotización ha sido enviada al contador para su revisión y aprobación.';
            
            const numeroCot = result.data?.cotizacion?.numero_cotizacion || result.numero_cotizacion;
            mostrarModalExito(titulo, mensaje, numeroCot, action === 'enviar');
        } else {
            let mensajeError = result.message || 'Error al guardar';
            
            if (result.errors) {
                console.log('❌ ERRORES DE VALIDACIÓN (errors):');
                console.table(result.errors);
                const errores = [];
                for (const [campo, msgs] of Object.entries(result.errors)) {
                    const mensaje = Array.isArray(msgs) ? msgs[0] : msgs;
                    errores.push(`${campo}: ${mensaje}`);
                    console.log(`  ❌ ${campo}: ${mensaje}`);
                }
                mensajeError = 'Errores de validación:\n' + errores.join('\n');
            } else if (result.errores) {
                console.log('❌ ERRORES (errores):');
                console.table(result.errores);
                const errores = [];
                for (const [campo, msgs] of Object.entries(result.errores)) {
                    const mensaje = Array.isArray(msgs) ? msgs[0] : msgs;
                    errores.push(`${campo}: ${mensaje}`);
                    console.log(`  ❌ ${campo}: ${mensaje}`);
                }
                mensajeError = 'Errores:\n' + errores.join('\n');
            }
            // Mostrar error de forma más legible
            console.error('🔴 ERROR FINAL:', mensajeError);
            alert(` ${mensajeError}`);
        }
    } catch (error) {
        alert(` Error de conexión: ${error.message}\n\nVerifica la consola para más detalles.`);
    }
});

// Variable global para rastrear fotos eliminadas
let fotosEliminadas = [];

/**
 * Mostrar modal de éxito
 */
function mostrarModalExito(titulo, mensaje, numeroCotizacion, mostrarNumero) {
    const modal = document.getElementById('modalExito');
    const modalTitulo = document.getElementById('modalExitoTitulo');
    const modalMensaje = document.getElementById('modalExitoMensaje');
    const modalNumero = document.getElementById('modalExitoNumero');
    const modalNumeroCotizacion = document.getElementById('modalExitoNumeroCotizacion');
    
    // Establecer contenido
    modalTitulo.textContent = titulo;
    modalMensaje.textContent = mensaje;
    
    // Mostrar número de cotización si se envía
    if (mostrarNumero && numeroCotizacion) {
        modalNumero.style.display = 'block';
        modalNumeroCotizacion.textContent = numeroCotizacion;
    } else {
        modalNumero.style.display = 'none';
    }
    
    // Mostrar modal
    if (modal) {
        modal.style.display = 'flex';
    }
}

/**
 * Cerrar modal de éxito
 */
function cerrarModalExito() {
    const modal = document.getElementById('modalExito');
    if (modal) {
        modal.style.display = 'none';
    }
    // Redirigir a cotizaciones después de cerrar
    window.location.href = '/asesores/cotizaciones';
}

/**
 * Función para eliminar una foto del reflectivo INMEDIATAMENTE
 */
function eliminarFotoReflectivo(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const boton = event.target;
    const fotoId = boton.getAttribute('data-foto-id');
    const contenedor = boton.closest('div[data-foto-id]');
    
    if (!fotoId || !contenedor) {
        return;
    }
    
    // Obtener la URL de la imagen para enviarla al backend
    const img = contenedor.querySelector('img');
    const fotoUrl = img ? img.src : '';

    // Mostrar modal de confirmación
    mostrarModalConfirmarEliminar(fotoId, fotoUrl, contenedor);
}

/**
 * Mostrar modal de confirmación de eliminación
 */
function mostrarModalConfirmarEliminar(fotoId, fotoUrl, contenedor) {
    const modal = document.getElementById('modalConfirmarEliminar');
    const btnConfirmar = document.getElementById('btnConfirmarEliminar');
    
    if (!modal || !btnConfirmar) {
        return;
    }
    
    // Mostrar modal
    modal.style.display = 'flex';
    
    // Configurar botón de confirmación
    btnConfirmar.onclick = async function() {
        // Cerrar modal
        modal.style.display = 'none';
        
        // Proceder con la eliminación
        try {
            const response = await fetch('/asesores/fotos/eliminar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    foto_id: fotoId,
                    ruta: fotoUrl,
                    cotizacion_id: window.cotizacionIdActual || null
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Remover del DOM
                contenedor.remove();
            } else {
                alert('Error al eliminar la foto: ' + result.message);
            }
        } catch (error) {
            alert('Error de conexión al eliminar la foto. Por favor, intenta de nuevo.');
        }
    };
}

/**
 * Cerrar modal de confirmación de eliminación
 */
function cerrarModalConfirmarEliminar() {
    const modal = document.getElementById('modalConfirmarEliminar');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Agregar PRENDA 1 por default al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Capturar ID de cotización si existe (para edición)
    window.cotizacionIdActual = null; // Se obtiene del atributo data si existe
    window.esEdicion = !!window.cotizacionIdActual;


    // Si hay datos iniciales (edición), cargarlos
    const datosIniciales = null;
    if (datosIniciales) {
        try {
            // Cargar cliente
            if (datosIniciales.cliente) {
                const nombreCliente = datosIniciales.cliente.nombre || datosIniciales.cliente;
                console.log('👤 Cargando cliente:', nombreCliente);
                document.getElementById('header-cliente').value = nombreCliente;
                document.getElementById('cliente').value = nombreCliente;
            }
            
            // Cargar fecha
            if (datosIniciales.fecha_inicio) {
                const fecha = new Date(datosIniciales.fecha_inicio);
                const fechaFormato = fecha.toISOString().split('T')[0];
                document.getElementById('header-fecha').value = fechaFormato;
                document.getElementById('fecha').value = fechaFormato;
            }
            
            // Cargar tipo_venta
            if (datosIniciales.tipo_venta) {
                document.getElementById('header-tipo-venta').value = datosIniciales.tipo_venta;
                document.getElementById('tipo_venta_reflectivo').value = datosIniciales.tipo_venta;
            }
            
            // También cargar desde reflectivo_cotizacion si existe (tiene prioridad)
            if (datosIniciales.reflectivo_cotizacion && datosIniciales.reflectivo_cotizacion.tipo_venta) {
                document.getElementById('header-tipo-venta').value = datosIniciales.reflectivo_cotizacion.tipo_venta;
                document.getElementById('tipo_venta_reflectivo').value = datosIniciales.reflectivo_cotizacion.tipo_venta;
            }
            
            // Cargar especificaciones
            if (datosIniciales.especificaciones) {
                let especificacionesValue = '';
                
                if (typeof datosIniciales.especificaciones === 'string') {
                    // Si es string, parsearlo para verificar si tiene datos
                    try {
                        const parsed = JSON.parse(datosIniciales.especificaciones);
                        // Si es un objeto con propiedades, guardar el string original
                        if (Object.keys(parsed).length > 0) {
                            especificacionesValue = datosIniciales.especificaciones;
                        } else {
                            especificacionesValue = '{}';
                        }
                    } catch (e) {
                        especificacionesValue = datosIniciales.especificaciones;
                    }
                } else if (typeof datosIniciales.especificaciones === 'object') {
                    // Si es objeto, convertir a JSON string
                    especificacionesValue = JSON.stringify(datosIniciales.especificaciones);
                }
                document.getElementById('especificaciones').value = especificacionesValue;
            }
            
            // Cargar prendas (reflectivo)
            if (datosIniciales.prendas && datosIniciales.prendas.length > 0) {
                // Limpiar la prenda por defecto
                const contenedor = document.getElementById('prendas-contenedor');
                contenedor.innerHTML = '';
                
                // Agregar cada prenda
                datosIniciales.prendas.forEach((prenda, index) => {
                    contadorProductosReflectivo++;
                    const template = document.getElementById('productoReflectivoTemplate');
                    const clone = template.content.cloneNode(true);
                    
                    // Actualizar número
                    clone.querySelector('.numero-producto').textContent = contadorProductosReflectivo;
                    
                    // Cargar tipo de prenda
                    const tipoInput = clone.querySelector('[name*="tipo_prenda"]');
                    if (tipoInput && prenda.nombre_producto) {
                        tipoInput.value = prenda.nombre_producto;
                    }
                    
                    // Cargar descripción
                    const descInput = clone.querySelector('[name*="descripcion"]');
                    if (descInput && prenda.descripcion) {
                        descInput.value = prenda.descripcion;
                    }
                    
                    //  CARGAR GÉNERO DE LA PRENDA
                    const generoSelect = clone.querySelector('.talla-genero-select-reflectivo');
                    if (generoSelect && prenda.genero) {
                        // Mostrar el select de género
                        generoSelect.style.display = 'block';
                        generoSelect.value = prenda.genero;
                    }
                    
                    //  CARGAR TALLAS DE LA PRENDA
                    if (prenda.tallas && prenda.tallas.length > 0) {
                        const prendaCard = clone;
                        const tallasAgregadas = prendaCard.querySelector('.tallas-agregadas-reflectivo');
                        const tallasHidden = prendaCard.querySelector('.tallas-hidden-reflectivo');
                        const tallasSection = prendaCard.querySelector('.tallas-section-reflectivo');
                        
                        if (tallasAgregadas) {
                            // Limpiar tallas previas si existen
                            tallasAgregadas.innerHTML = '';
                            
                            // Agregar cada talla como tag
                            prenda.tallas.forEach(talla => {
                                const tag = document.createElement('div');
                                tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
                                tag.innerHTML = `
                                    <span>${talla}</span>
                                    <button type="button" onclick="this.closest('div').remove(); actualizarTallasHiddenReflectivo(this.closest('.producto-card'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
                                `;
                                tallasAgregadas.appendChild(tag);
                            });
                            
                            // Actualizar hidden input
                            if (tallasHidden) {
                                tallasHidden.value = prenda.tallas.join(', ');
                            }
                            
                            // Mostrar sección
                            if (tallasSection) {
                                tallasSection.style.display = 'block';
                            }
                        }
                    }
                    
                    // Agregar el clone al DOM primero
                    contenedor.appendChild(clone);
                    
                    //  CARGAR FOTOS - Después de agregar al DOM para evitar duplicación
                    const fotosParaCargar = prenda.reflectivo?.fotos || prenda.fotos || [];
                    if (fotosParaCargar && fotosParaCargar.length > 0) {
                        // Buscar el contenedor en el DOM, no en el clone
                        const prendaCard = contenedor.lastElementChild;
                        const fotosContainer = prendaCard.querySelector('.fotos-preview-reflectivo');
                        
                        if (fotosContainer) {
                            //  LIMPIAR el contenedor antes de agregar fotos
                            const fotosExistentes = fotosContainer.children.length;
                            fotosContainer.innerHTML = '';
                            
                            fotosParaCargar.forEach((foto, idx) => {
                                const imgDiv = document.createElement('div');
                                imgDiv.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; aspect-ratio: 1;';
                                imgDiv.setAttribute('data-foto-id', foto.id);
                                imgDiv.innerHTML = `
                                    <img src="${foto.url}" style="width: 100%; height: 100%; object-fit: cover;">
                                    <button type="button" data-foto-id="${foto.id}" onclick="eliminarFotoReflectivo(event)" style="position: absolute; top: 2px; right: 2px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center;">×</button>
                                `;
                                fotosContainer.appendChild(imgDiv);
                            });
                        }
                    } else {
                    }
                    
                    //  CARGAR UBICACIONES DE ESTA PRENDA (después de agregar al DOM)
                    if (prenda.reflectivo && prenda.reflectivo.ubicacion) {
                        const prendaCard = contenedor.lastElementChild;
                        const ubicacionesContainer = prendaCard.querySelector('.ubicaciones-agregadas-reflectivo');
                        
                        if (ubicacionesContainer) {
                            const ubicaciones = Array.isArray(prenda.reflectivo.ubicacion) 
                                ? prenda.reflectivo.ubicacion 
                                : (typeof prenda.reflectivo.ubicacion === 'string' ? JSON.parse(prenda.reflectivo.ubicacion) : []);
                            
                            ubicaciones.forEach(ubi => {
                                if (ubi && ubi.ubicacion) {
                                    const item = document.createElement('div');
                                    item.className = 'ubicacion-item-reflectivo';
                                    item.style.cssText = 'background: white; border: 2px solid #0ea5e9; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; width: 100%; box-shadow: 0 2px 4px rgba(14, 165, 233, 0.15); position: relative;';
                                    
                                    const header = document.createElement('div');
                                    header.className = 'ubicacion-header-reflectivo';
                                    header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; cursor: pointer;';
                                    header.innerHTML = `
                                        <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                                            <span style="color: #0ea5e9; font-weight: 700; font-size: 1rem;"></span>
                                            <span class="ubicacion-nombre-reflectivo" style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">${ubi.ubicacion}</span>
                                        </div>
                                        <span style="color: #0ea5e9; font-size: 1.2rem; transition: transform 0.3s ease;" class="ubicacion-toggle">▼</span>
                                    `;
                                    
                                    const body = document.createElement('div');
                                    body.className = 'ubicacion-body-reflectivo';
                                    body.style.cssText = 'display: block; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0;';
                                    body.innerHTML = `
                                        <p style="margin: 0 0 0.5rem 0; color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Descripción:</p>
                                        <p class="ubicacion-descripcion-reflectivo" style="margin: 0; color: #334155; font-size: 0.9rem; line-height: 1.5;">${ubi.descripcion || 'Sin descripción adicional'}</p>
                                    `;
                                    
                                    const deleteBtn = document.createElement('button');
                                    deleteBtn.type = 'button';
                                    deleteBtn.style.cssText = 'position: absolute; top: 0.5rem; right: 0.5rem; background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: bold;';
                                    deleteBtn.textContent = '×';
                                    deleteBtn.onclick = (e) => {
                                        e.stopPropagation();
                                        item.remove();
                                    };
                                    
                                    item.appendChild(header);
                                    header.appendChild(deleteBtn);
                                    item.appendChild(body);
                                    
                                    let expanded = true;
                                    header.addEventListener('click', () => {
                                        expanded = !expanded;
                                        body.style.display = expanded ? 'block' : 'none';
                                        header.querySelector('.ubicacion-toggle').style.transform = expanded ? 'rotate(0deg)' : 'rotate(-90deg)';
                                    });
                                    
                                    ubicacionesContainer.appendChild(item);
                                }
                            });
                        }
                    }
                });
            } else {
                agregarProductoPrenda();
            }
            
            //  FOTOS YA SE CARGAN POR PRENDA (líneas 2229-2258)
            // No cargar fotos globalmente para evitar duplicación


            const reflectivo = datosIniciales.reflectivo_cotizacion || datosIniciales.reflectivo;
            console.log(' Fotos cargadas por prenda (no globalmente para evitar duplicaciones)');
            
            // Cargar descripción del reflectivo (si existe)
            if (reflectivo && reflectivo.descripcion) {
                const descInput = document.getElementById('descripcion_reflectivo');
                if (descInput) {
                    descInput.value = reflectivo.descripcion;
                }
            }
            
            //  NO CARGAR UBICACIÓN GLOBAL - Ya se cargan por PRENDA (línea ~2108)
            // Las ubicaciones deben cargarse dentro del contexto de cada prenda, no globalmente
            // Esto previene duplicación en la primera prenda
            console.log(' Ubicaciones cargadas por prenda (no globalmente para evitar duplicaciones)');
        } catch (e) {

            agregarProductoPrenda();
        }
    } else {
        agregarProductoPrenda();
    }

    // ============ FUNCIONES PARA TALLAS EN REFLECTIVO ============

    /**
     * Actualiza el input oculto genero_id con el género seleccionado
     */
    window.actualizarGeneroSeleccionadoReflectivo = function(select) {
        const productoSection = select.closest('.producto-section');
        if (!productoSection) {
            return;
        }
        
        const generoInput = productoSection.querySelector('.genero-id-hidden-reflectivo');
        if (!generoInput) {
            return;
        }
        
        const generoValue = select.value;
        // Mapear valores de género a IDs
        let generoId = '';
        if (generoValue === 'dama') {
            generoId = '1';
        } else if (generoValue === 'caballero') {
            generoId = '2';
        }
        
        generoInput.value = generoId;
    };

    // Mapeos de tallas por tipo y género (copiado de tallas.js)
    const TALLAS_LETRAS = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
    const TALLAS_NUMEROS_DAMA = ['2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28'];
    const TALLAS_NUMEROS_CABALLERO = ['30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50', '52', '54', '56'];

    window.actualizarSelectTallasReflectivo = function(select) {
        console.log('🔵 actualizarSelectTallasReflectivo() llamado');
        
        const container = select.closest('.producto-section');
        const tallaBotones = container.querySelector('.talla-botones-reflectivo');
        const botonesDiv = container.querySelector('.talla-botones-container-reflectivo');
        const generoSelect = container.querySelector('.talla-genero-select-reflectivo');
        const modoSelect = container.querySelector('.talla-modo-select-reflectivo');
        const tallaRangoSelectors = container.querySelector('.talla-rango-selectors-reflectivo');
        const tipo = select.value;
        // LIMPIAR COMPLETAMENTE TODO
        botonesDiv.innerHTML = '';
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
        modoSelect.style.display = 'none';
        generoSelect.style.display = 'none';
        generoSelect.value = '';
        modoSelect.value = '';
        
        // Remover event listeners anteriores
        if (modoSelect._handlerLetras) {
            modoSelect.removeEventListener('change', modoSelect._handlerLetras);
            modoSelect._handlerLetras = null;
        }
        if (modoSelect._handlerNumeros) {
            modoSelect.removeEventListener('change', modoSelect._handlerNumeros);
            modoSelect._handlerNumeros = null;
        }
        if (modoSelect._handler) {
            modoSelect.removeEventListener('change', modoSelect._handler);
            modoSelect._handler = null;
        }
        if (generoSelect._handlerLetras) {
            generoSelect.removeEventListener('change', generoSelect._handlerLetras);
            generoSelect._handlerLetras = null;
        }
        if (generoSelect._handler) {
            generoSelect.removeEventListener('change', generoSelect._handler);
            generoSelect._handler = null;
        }
        
        if (tipo === 'letra') {
            // LETRAS muestra género y modo
            generoSelect.style.display = 'block';
            modoSelect.style.display = 'block';
            modoSelect.value = 'manual';
            
            // Event listener para modo
            modoSelect._handlerLetras = function() {
                actualizarModoLetrasReflectivo(container, this.value);
            };
            modoSelect.addEventListener('change', modoSelect._handlerLetras);
            
            // Mostrar botones de LETRAS en manual
            tallaBotones.style.display = 'block';
            tallaRangoSelectors.style.display = 'none';
            
            TALLAS_LETRAS.forEach(talla => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = talla;
                btn.className = 'talla-btn';
                btn.dataset.talla = talla;
                btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
                btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
                btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
                btn.onclick = function(e) {
                    e.preventDefault();
                    this.classList.toggle('activo');
                    if (this.classList.contains('activo')) {
                        this.style.background = '#0066cc';
                        this.style.color = 'white';
                    } else {
                        this.style.background = 'white';
                        this.style.color = '#0066cc';
                    }
                };
                botonesDiv.appendChild(btn);
            });
        } else if (tipo === 'numero') {
            generoSelect.style.display = 'block';
            
            generoSelect._handler = function() {
                console.log('🔢 Género seleccionado (NÚMEROS):', this.value);
                actualizarBotonesPorGeneroReflectivo(container, this.value);
            };
            generoSelect.addEventListener('change', generoSelect._handler);
        }
    };
    
    window.actualizarModoLetrasReflectivo = function(container, modo) {
        const tallaBotones = container.querySelector('.talla-botones-reflectivo');
        const tallaRangoSelectors = container.querySelector('.talla-rango-selectors-reflectivo');
        const botonesDiv = container.querySelector('.talla-botones-container-reflectivo');
        
        botonesDiv.innerHTML = '';
        
        if (modo === 'manual') {
            tallaBotones.style.display = 'block';
            tallaRangoSelectors.style.display = 'none';
            
            TALLAS_LETRAS.forEach(talla => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = talla;
                btn.className = 'talla-btn';
                btn.dataset.talla = talla;
                btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
                btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
                btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
                btn.onclick = function(e) {
                    e.preventDefault();
                    this.classList.toggle('activo');
                    if (this.classList.contains('activo')) {
                        this.style.background = '#0066cc';
                        this.style.color = 'white';
                    } else {
                        this.style.background = 'white';
                        this.style.color = '#0066cc';
                    }
                };
                botonesDiv.appendChild(btn);
            });
        } else if (modo === 'rango') {
            tallaBotones.style.display = 'none';
            tallaRangoSelectors.style.display = 'flex';
            actualizarSelectoresRangoLetrasReflectivo(container);
        } else {
            tallaBotones.style.display = 'none';
            tallaRangoSelectors.style.display = 'none';
        }
    };
    
    window.actualizarSelectoresRangoLetrasReflectivo = function(container) {
        const desdeSelect = container.querySelector('.talla-desde-reflectivo');
        const hastaSelect = container.querySelector('.talla-hasta-reflectivo');
        
        desdeSelect.innerHTML = '<option value="">Desde</option>';
        hastaSelect.innerHTML = '<option value="">Hasta</option>';
        
        TALLAS_LETRAS.forEach(talla => {
            const optDesde = document.createElement('option');
            optDesde.value = talla;
            optDesde.textContent = talla;
            desdeSelect.appendChild(optDesde);
            
            const optHasta = document.createElement('option');
            optHasta.value = talla;
            optHasta.textContent = talla;
            hastaSelect.appendChild(optHasta);
        });
    };
    
    window.actualizarBotonesPorGeneroReflectivo = function(container, genero) {
        if (!genero) {
            container.querySelector('.talla-botones-reflectivo').style.display = 'none';
            container.querySelector('.talla-rango-selectors-reflectivo').style.display = 'none';
            container.querySelector('.talla-modo-select-reflectivo').style.display = 'none';
            return;
        }
        
        // Mostrar modo
        const modoSelect = container.querySelector('.talla-modo-select-reflectivo');
        modoSelect.style.display = 'block';
        modoSelect.value = 'manual';
        
        // Remover event listener anterior
        if (modoSelect._handlerNumeros) {
            modoSelect.removeEventListener('change', modoSelect._handlerNumeros);
        }
        
        // Agregar nuevo event listener
        modoSelect._handlerNumeros = function() {
            actualizarModoNumerosReflectivo(container, this.value, genero);
        };
        modoSelect.addEventListener('change', modoSelect._handlerNumeros);
        
        // Mostrar botones en manual
        actualizarModoNumerosReflectivo(container, 'manual', genero);
    };
    
    window.actualizarModoNumerosReflectivo = function(container, modo, genero) {
        const tallaBotones = container.querySelector('.talla-botones-reflectivo');
        const tallaRangoSelectors = container.querySelector('.talla-rango-selectors-reflectivo');
        const botonesDiv = container.querySelector('.talla-botones-container-reflectivo');
        
        botonesDiv.innerHTML = '';
        
        const tallas = genero === 'dama' ? TALLAS_NUMEROS_DAMA : TALLAS_NUMEROS_CABALLERO;
        
        if (modo === 'manual') {
            tallaBotones.style.display = 'block';
            tallaRangoSelectors.style.display = 'none';
            
            tallas.forEach(talla => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = talla;
                btn.className = 'talla-btn';
                btn.dataset.talla = talla;
                btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
                btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
                btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
                btn.onclick = function(e) {
                    e.preventDefault();
                    this.classList.toggle('activo');
                    if (this.classList.contains('activo')) {
                        this.style.background = '#0066cc';
                        this.style.color = 'white';
                    } else {
                        this.style.background = 'white';
                        this.style.color = '#0066cc';
                    }
                };
                botonesDiv.appendChild(btn);
            });
        } else if (modo === 'rango') {
            tallaBotones.style.display = 'none';
            tallaRangoSelectors.style.display = 'flex';
            actualizarSelectoresRangoNumerosReflectivo(container, tallas);
        } else {
            tallaBotones.style.display = 'none';
            tallaRangoSelectors.style.display = 'none';
        }
    };
    
    window.actualizarSelectoresRangoNumerosReflectivo = function(container, tallas) {
        const desdeSelect = container.querySelector('.talla-desde-reflectivo');
        const hastaSelect = container.querySelector('.talla-hasta-reflectivo');
        
        desdeSelect.innerHTML = '<option value="">Desde</option>';
        hastaSelect.innerHTML = '<option value="">Hasta</option>';
        
        tallas.forEach(talla => {
            const optDesde = document.createElement('option');
            optDesde.value = talla;
            optDesde.textContent = talla;
            desdeSelect.appendChild(optDesde);
            
            const optHasta = document.createElement('option');
            optHasta.value = talla;
            optHasta.textContent = talla;
            hastaSelect.appendChild(optHasta);
        });
    };

    window.agregarTallasSeleccionadasReflectivo = function(btn) {
        const card = btn.closest('.producto-card');
        const botonesActivos = card.querySelectorAll('.talla-btn.activo');
        const tallasAgregadas = card.querySelector('.tallas-agregadas-reflectivo');
        const tallasSection = card.querySelector('.tallas-section-reflectivo');
        
        if (botonesActivos.length === 0) {
            alert('Por favor selecciona al menos una talla');
            return;
        }
        
        botonesActivos.forEach(boton => {
            const talla = boton.dataset.talla;
            
            const existe = Array.from(tallasAgregadas.querySelectorAll('div')).some(tag =>
                tag.querySelector('span').textContent === talla
            );
            
            if (!existe) {
                const tag = document.createElement('div');
                tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
                tag.innerHTML = `
                    <span>${talla}</span>
                    <button type="button" onclick="this.closest('div').remove(); actualizarTallasHiddenReflectivo(this.closest('.producto-card'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
                `;
                
                tallasAgregadas.appendChild(tag);
            }
        });
        
        tallasSection.style.display = 'block';
        actualizarTallasHiddenReflectivo(card);
        
        botonesActivos.forEach(boton => {
            boton.classList.remove('activo');
            boton.style.background = 'white';
            boton.style.color = '#0066cc';
        });
    };

    window.agregarTallasRangoReflectivo = function(btn) {
        const card = btn.closest('.producto-card');
        const tallaDesde = card.querySelector('.talla-desde-reflectivo').value;
        const tallaHasta = card.querySelector('.talla-hasta-reflectivo').value;
        const tallasAgregadas = card.querySelector('.tallas-agregadas-reflectivo');
        const tallasSection = card.querySelector('.tallas-section-reflectivo');
        const tipoSelect = card.querySelector('.talla-tipo-select-reflectivo');
        const generoSelect = card.querySelector('.talla-genero-select-reflectivo');


        if (!tallaDesde || !tallaHasta) {
            alert('Por favor selecciona un rango completo (Desde y Hasta)');
            return;
        }
        
        let tallas;
        
        if (tipoSelect.value === 'letra') {
            tallas = TALLAS_LETRAS;
        } else if (tipoSelect.value === 'numero') {
            if (!generoSelect.value) {
                alert('Por favor selecciona un género primero');
                return;
            }
            tallas = generoSelect.value === 'dama' ? TALLAS_NUMEROS_DAMA : TALLAS_NUMEROS_CABALLERO;
        } else {
            alert('Por favor selecciona un tipo de talla primero');
            return;
        }

        const indexDesde = tallas.indexOf(tallaDesde);
        const indexHasta = tallas.indexOf(tallaHasta);
        if (indexDesde === -1 || indexHasta === -1) {


            alert('Las tallas seleccionadas no son válidas');
            return;
        }
        
        if (indexDesde > indexHasta) {
            alert('La talla "Desde" no puede ser mayor que "Hasta"');
            return;
        }
        
        const tallasRango = tallas.slice(indexDesde, indexHasta + 1);
        tallasRango.forEach(talla => {
            const existe = Array.from(tallasAgregadas.querySelectorAll('div')).some(tag =>
                tag.querySelector('span').textContent === talla
            );
            
            if (!existe) {
                const tag = document.createElement('div');
                tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
                tag.innerHTML = `
                    <span>${talla}</span>
                    <button type="button" onclick="this.closest('div').remove(); actualizarTallasHiddenReflectivo(this.closest('.producto-card'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
                `;
                
                tallasAgregadas.appendChild(tag);
            }
        });
        
        tallasSection.style.display = 'block';
        actualizarTallasHiddenReflectivo(card);
    };

    window.actualizarTallasHiddenReflectivo = function(container) {
        if (!container) {
            return;
        }
        
        const tallasAgregadas = container.querySelector('.tallas-agregadas-reflectivo');
        const tallasHidden = container.querySelector('.tallas-hidden-reflectivo');
        if (!tallasAgregadas || !tallasHidden) {
            return;
        }
        
        const tallas = [];
        
        tallasAgregadas.querySelectorAll('div > span:first-child').forEach(span => {
            if (span.textContent) {
                tallas.push(span.textContent);
            }
        });
        
        tallasHidden.value = tallas.join(', ');
    };

    // ========================================================================
    // FUNCIONES PARA MANEJAR VARIACIONES
    // ========================================================================

    window.agregarFilaVariacionReflectivo = function(button) {
        const seccion = button.closest('.variaciones-seccion-reflectivo');
        if (!seccion) return;

        const tbody = seccion.querySelector('.variaciones-tbody-reflectivo');
        if (!tbody) return;

        const fila = document.createElement('tr');
        fila.style.cssText = 'border-bottom: 1px solid #e2e8f0;';
        fila.classList.add('variacion-fila-agregada'); // Marcar como fila agregada (no por defecto)
        
        fila.innerHTML = `
            <td style="padding: 0.75rem;">
                <div class="variaciones-fila-input" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" class="variacion-checkbox" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0ea5e9;" checked>
                    <input type="text" class="variacion-nombre" placeholder="Ej: Manga, Bolsillos, etc." style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                </div>
            </td>
            <td style="padding: 0.75rem;">
                <input type="text" class="variacion-opcion" placeholder="Opción..." style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            </td>
            <td style="padding: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="text" class="variacion-observacion" placeholder="Ej: Manga larga, bolsillo izquierdo, etc." style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; min-height: 40px;">
                    <button type="button" class="btn-eliminar-variacion" style="background-color: #ef4444; color: white; border: none; padding: 0.4rem 0.6rem; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.8rem;">Eliminar</button>
                </div>
            </td>
        `;

        // Agregar event listener al botón eliminar
        const btnEliminar = fila.querySelector('.btn-eliminar-variacion');
        btnEliminar.addEventListener('click', function(e) {
            e.preventDefault();
            fila.remove();
            actualizarVariacionesJSON(seccion);
        });

        // Agregar event listeners para actualizar JSON cuando cambien los inputs
        fila.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('change', () => actualizarVariacionesJSON(seccion));
            input.addEventListener('input', () => actualizarVariacionesJSON(seccion));
        });

        tbody.appendChild(fila);
        actualizarVariacionesJSON(seccion);
    };

    window.actualizarVariacionesJSON = function(seccionElement) {
        const seccion = seccionElement.closest ? seccionElement.closest('.variaciones-seccion-reflectivo') : seccionElement;
        if (!seccion) return;

        const tbody = seccion.querySelector('.variaciones-tbody-reflectivo');
        const jsonInput = seccion.querySelector('.variaciones-json-reflectivo');
        
        if (!tbody || !jsonInput) return;

        const variaciones = [];
        tbody.querySelectorAll('tr').forEach(fila => {
            const checkbox = fila.querySelector('.variacion-checkbox');
            const nombre = fila.querySelector('.variacion-nombre');
            const opcion = fila.querySelector('.variacion-opcion');
            const observacion = fila.querySelector('.variacion-observacion');

            if (checkbox && nombre && observacion) {
                const variacion = {
                    variacion: nombre.value.trim(),
                    checked: checkbox.checked,
                    observacion: observacion.value.trim()
                };
                
                // Agregar opción si existe (para Manga y Broche/Botón)
                if (opcion && opcion.tagName === 'SELECT') {
                    variacion.opcion = opcion.value.trim();
                }
                
                variaciones.push(variacion);
            }
        });

        jsonInput.value = JSON.stringify(variaciones);
    };

    // Inicializar algunas variaciones por defecto cuando se carga la página
    window.inicializarVariacionesDefault = function(container) {
        const seccion = container.querySelector('.variaciones-seccion-reflectivo');
        if (!seccion) return;

        const tbody = seccion.querySelector('.variaciones-tbody-reflectivo');
        if (!tbody) return;

        // Si la tabla está vacía, agregar variaciones por defecto
        if (tbody.querySelectorAll('tr').length === 0) {
            const variacionesDefault = ['Manga', 'Bolsillos', 'Broche/Botón'];
            variacionesDefault.forEach(variacion => {
                const fila = document.createElement('tr');
                fila.style.cssText = 'border-bottom: 1px solid #e2e8f0;';
                fila.classList.add('variacion-fila-default'); // Marcar como fila por defecto
                
                let opcionHTML = '';
                
                // Generar HTML de opción según el tipo de variación
                if (variacion === 'Manga') {
                    opcionHTML = `
                        <td style="padding: 0.75rem;">
                            <select class="variacion-opcion" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                <option value="">-- Seleccionar --</option>
                                <option value="Corta">Corta</option>
                                <option value="Larga">Larga</option>
                            </select>
                        </td>
                    `;
                } else if (variacion === 'Broche/Botón') {
                    // Cargar opciones de broche desde el API
                    opcionHTML = `
                        <td style="padding: 0.75rem;">
                            <select class="variacion-opcion" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                <option value="">-- Cargando --</option>
                            </select>
                        </td>
                    `;
                } else if (variacion === 'Bolsillos') {
                    // Bolsillos no tiene opción
                    opcionHTML = `
                        <td style="padding: 0.75rem;">
                            <span style="color: #999; font-size: 0.9rem;">N/A</span>
                        </td>
                    `;
                }
                
                fila.innerHTML = `
                    <td style="padding: 0.75rem;">
                        <div class="variaciones-fila-input" style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" class="variacion-checkbox" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0ea5e9;">
                            <input type="text" class="variacion-nombre" value="${variacion}" placeholder="Ej: Manga, Bolsillos, etc." style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                        </div>
                    </td>
                    ${opcionHTML}
                    <td style="padding: 0.75rem;">
                        <input type="text" class="variacion-observacion" placeholder="Ej: Manga larga, bolsillo izquierdo, etc." style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; min-height: 40px;">
                    </td>
                `;

                // Agregar event listeners para actualizar JSON cuando cambien los inputs
                fila.querySelectorAll('input, select').forEach(input => {
                    input.addEventListener('change', () => actualizarVariacionesJSON(seccion));
                    input.addEventListener('input', () => actualizarVariacionesJSON(seccion));
                });

                tbody.appendChild(fila);
                
                // Si es Broche/Botón, cargar los tipos de broche
                if (variacion === 'Broche/Botón') {
                    cargarTiposBrocheEnVariacion(fila);
                }
            });
            actualizarVariacionesJSON(seccion);
        }
    };
    
    // Función para cargar tipos de broche en el select de variación
    window.cargarTiposBrocheEnVariacion = function(fila) {
        const select = fila.querySelector('.variacion-opcion');
        if (!select) return;
        
        fetch('/asesores/api/tipos-broche-boton')
            .then(response => response.json())
            .then(data => {
                let tiposBroche = [];
                
                // Manejar respuesta con estructura { success, data }
                if (data && data.data && Array.isArray(data.data)) {
                    tiposBroche = data.data;
                } 
                // O si es directamente un array
                else if (Array.isArray(data)) {
                    tiposBroche = data;
                }
                
                if (tiposBroche.length > 0) {
                    select.innerHTML = '<option value="">-- Seleccionar --</option>';
                    tiposBroche.forEach(broche => {
                        const option = document.createElement('option');
                        option.value = broche.nombre;
                        option.textContent = broche.nombre;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error al cargar tipos de broche:', error);
            });
    };});

/**
 * ============================================================
 * FUNCIONES PARA TABLA COLOR, TELA Y REFERENCIA - REFLECTIVO
 * ============================================================
 */

// Datos de colores y telas disponibles (similar al archivo color-tela-referencia.js)
const coloresDisponiblesReflectivo = [
    { id: 1, nombre: 'Azul' },
    { id: 2, nombre: 'Negro' },
    { id: 3, nombre: 'Gris' },
    { id: 4, nombre: 'Blanco' },
    { id: 5, nombre: 'Naranja' },
    { id: 6, nombre: 'Rojo' },
    { id: 7, nombre: 'Verde' },
    { id: 8, nombre: 'Amarillo' }
];

const telasDisponiblesReflectivo = [
    { id: 1, nombre: 'NAPOLES', referencia: 'REF-NAP-001' },
    { id: 2, nombre: 'DRILL BORNEO', referencia: 'REF-DB-001' },
    { id: 3, nombre: 'OXFORD', referencia: 'REF-OX-001' },
    { id: 4, nombre: 'JERSEY', referencia: 'REF-JER-001' },
    { id: 5, nombre: 'LINO', referencia: 'REF-LIN-001' }
];

let proximoColorIdReflectivo = 9;
let proximoTelaIdReflectivo = 6;

/**
 * AGREGAR FILA DE TELA
 */
function agregarFilaTelaReflectivo(btn) {
    const seccion = btn.closest('.producto-section');
    const tbody = seccion.querySelector('.telas-tbody-reflectivo');
    const telaCount = tbody.querySelectorAll('tr').length;
    
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid #ddd';
    tr.className = 'fila-tela-reflectivo';
    tr.setAttribute('data-tela-index', telaCount);
    
    tr.innerHTML = `
        <td style="padding: 14px; border-right: 1px solid #ddd;">
            <div style="position: relative;">
                <label class="sr-only">Color</label>
                <input type="text" class="color-input-reflectivo" placeholder="Color..." style="width: 100%; padding: 12px; border: 2px solid #0066cc; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; min-height: 44px;" onkeyup="buscarColorReflectivo(this)" onkeypress="if(event.key==='Enter') crearColorDesdeInputReflectivo(this)" aria-label="Selecciona o escribe un color">
                <input type="hidden" name="productos_reflectivo[][telas][${telaCount}][color_id]" class="color-id-input-reflectivo" value="">
                <div class="color-suggestions-reflectivo" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; min-width: 100%; display: none; margin-top: 2px; top: 100%;"></div>
            </div>
        </td>
        <td style="padding: 14px; border-right: 1px solid #ddd;">
            <div style="position: relative;">
                <label class="sr-only">Tela</label>
                <input type="text" class="tela-input-reflectivo" placeholder="Tela..." style="width: 100%; padding: 12px; border: 2px solid #0066cc; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; min-height: 44px;" onkeyup="buscarTelaReflectivo(this)" onkeypress="if(event.key==='Enter') crearTelaDesdeInputReflectivo(this)" aria-label="Selecciona o escribe el tipo de tela">
                <input type="hidden" name="productos_reflectivo[][telas][${telaCount}][tela_id]" class="tela-id-input-reflectivo" value="">
                <div class="tela-suggestions-reflectivo" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; min-width: 100%; display: none; margin-top: 2px; top: 100%;"></div>
            </div>
        </td>
        <td style="padding: 14px; border-right: 1px solid #ddd;">
            <label class="sr-only">Referencia</label>
            <input type="text" name="productos_reflectivo[][telas][${telaCount}][referencia]" class="referencia-input-reflectivo" placeholder="Ref..." style="width: 100%; padding: 12px; border: 2px solid #0066cc; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; min-height: 44px;" aria-label="Referencia del producto">
        </td>
        <td style="padding: 14px; text-align: center; border-right: 1px solid #ddd;">
            <label style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px; padding: 8px; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDropReflectivo(event, this)" ondragover="event.preventDefault(); this.style.background='#e8f4f8';" ondragleave="this.style.background='#f0f7ff'">
                <input type="file" name="productos_reflectivo[][telas][${telaCount}][fotos][]" class="input-file-tela-reflectivo" accept="image/*" multiple onchange="agregarFotoTelaReflectivo(this)" style="display: none;">
                <div class="drop-zone-content" style="font-size: 0.8rem;">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 1.2rem; color: #0066cc; margin-bottom: 4px;"></i>
                    <p style="margin: 4px 0; color: #0066cc; font-weight: 600; font-size: 0.8rem;">CLIC</p>
                    <small style="color: #666; font-size: 0.75rem;">(Máx. 3)</small>
                </div>
            </label>
            <div class="foto-tela-preview-reflectivo" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 6px;"></div>
        </td>
        <td style="padding: 14px; text-align: center;">
            <button type="button" class="btn-eliminar-tela-reflectivo" onclick="eliminarFilaTelaReflectivo(this)" style="padding: 10px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; display: none; min-width: 44px; min-height: 44px;">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(tr);
    
    // Mostrar botones de eliminar si hay más de una fila
    actualizarBotonesEliminarTelaReflectivo();
}

/**
 * ELIMINAR FILA DE TELA
 */
function eliminarFilaTelaReflectivo(btn) {
    const fila = btn.closest('tr');
    fila.remove();
    actualizarBotonesEliminarTelaReflectivo();
}

/**
 * ACTUALIZAR VISIBILIDAD DE BOTONES ELIMINAR
 */
function actualizarBotonesEliminarTelaReflectivo() {
    const seccion = document.querySelector('.producto-section:has(.telas-tbody-reflectivo)');
    if (!seccion) return;
    
    const tbody = seccion.querySelector('.telas-tbody-reflectivo');
    const filas = tbody.querySelectorAll('tr');
    const botonesEliminar = tbody.querySelectorAll('.btn-eliminar-tela-reflectivo');
    
    // Mostrar botón eliminar solo si hay más de una fila
    botonesEliminar.forEach((btn, index) => {
        btn.style.display = filas.length > 1 ? 'block' : 'none';
    });
}

/**
 * BÚSQUEDA DE COLORES
 */
function buscarColorReflectivo(input) {
    const valor = input.value.toLowerCase().trim();
    const suggestionsDiv = input.closest('td').querySelector('.color-suggestions-reflectivo');
    
    if (!valor) {
        suggestionsDiv.style.display = 'none';
        return;
    }
    
    const coincidencias = coloresDisponiblesReflectivo.filter(c => 
        c.nombre.toLowerCase().includes(valor)
    );
    
    let html = '';
    
    if (coincidencias.length > 0) {
        html += coincidencias.map(c => `
            <div style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;" 
                 onmouseover="this.style.backgroundColor='#f0f0f0'" 
                 onmouseout="this.style.backgroundColor='white'"
                 onclick="seleccionarColorReflectivo('${c.id}', '${c.nombre}', this)">
                <strong>${c.nombre}</strong>
            </div>
        `).join('');
    }
    
    if (html) {
        html += `
            <div style="padding: 8px 12px; border-top: 1px solid #eee; background: #f9f9f9; cursor: pointer; color: #0066cc; font-weight: 600;" 
                 onmouseover="this.style.backgroundColor='#eef5ff'" 
                 onmouseout="this.style.backgroundColor='#f9f9f9'"
                 onclick="crearColorDesdeInputReflectivo(this.closest('td').querySelector('.color-input-reflectivo'))">
                + Crear nuevo color
            </div>
        `;
    }
    
    suggestionsDiv.innerHTML = html;
    suggestionsDiv.style.display = html ? 'block' : 'none';
}

/**
 * SELECCIONAR COLOR
 */
function seleccionarColorReflectivo(id, nombre, element) {
    const td = element.closest('td');
    const input = td.querySelector('.color-input-reflectivo');
    const idInput = td.querySelector('.color-id-input-reflectivo');
    
    input.value = nombre;
    idInput.value = id;
    td.querySelector('.color-suggestions-reflectivo').style.display = 'none';
}

/**
 * CREAR COLOR DESDE INPUT
 */
function crearColorDesdeInputReflectivo(input) {
    const valor = input.value.trim();
    
    if (!valor) {
        alert('Por favor escribe un color');
        return;
    }
    
    const existe = coloresDisponiblesReflectivo.find(c => 
        c.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarColorReflectivo(existe.id, existe.nombre, input);
    } else {
        const nuevoId = proximoColorIdReflectivo++;
        const nuevoColor = { id: nuevoId, nombre: valor };
        coloresDisponiblesReflectivo.push(nuevoColor);
        
        const td = input.closest('td');
        const idInput = td.querySelector('.color-id-input-reflectivo');
        input.value = valor;
        idInput.value = nuevoId;
        td.querySelector('.color-suggestions-reflectivo').style.display = 'none';
    }
}

/**
 * BÚSQUEDA DE TELAS
 */
function buscarTelaReflectivo(input) {
    const valor = input.value.toLowerCase().trim();
    const suggestionsDiv = input.closest('td').querySelector('.tela-suggestions-reflectivo');
    
    if (!valor) {
        suggestionsDiv.style.display = 'none';
        return;
    }
    
    const coincidencias = telasDisponiblesReflectivo.filter(t => 
        t.nombre.toLowerCase().includes(valor) || 
        t.referencia.toLowerCase().includes(valor)
    );
    
    let html = '';
    
    if (coincidencias.length > 0) {
        html += coincidencias.map(t => `
            <div style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;" 
                 onmouseover="this.style.backgroundColor='#f0f0f0'" 
                 onmouseout="this.style.backgroundColor='white'"
                 onclick="seleccionarTelaReflectivo('${t.id}', '${t.nombre}', this)">
                <strong>${t.nombre}</strong>
                <small style="color: #999; display: block;">${t.referencia}</small>
            </div>
        `).join('');
    }
    
    if (html) {
        html += `
            <div style="padding: 8px 12px; border-top: 1px solid #eee; background: #f9f9f9; cursor: pointer; color: #0066cc; font-weight: 600;" 
                 onmouseover="this.style.backgroundColor='#eef5ff'" 
                 onmouseout="this.style.backgroundColor='#f9f9f9'"
                 onclick="crearTelaDesdeInputReflectivo(this.closest('td').querySelector('.tela-input-reflectivo'))">
                + Crear nueva tela
            </div>
        `;
    }
    
    suggestionsDiv.innerHTML = html;
    suggestionsDiv.style.display = html ? 'block' : 'none';
}

/**
 * SELECCIONAR TELA
 */
function seleccionarTelaReflectivo(id, nombre, element) {
    const td = element.closest('td');
    const input = td.querySelector('.tela-input-reflectivo');
    const idInput = td.querySelector('.tela-id-input-reflectivo');
    
    input.value = nombre;
    idInput.value = id;
    
    td.querySelector('.tela-suggestions-reflectivo').style.display = 'none';
}

/**
 * CREAR TELA DESDE INPUT
 */
function crearTelaDesdeInputReflectivo(input) {
    const valor = input.value.trim();
    
    if (!valor) {
        alert('Por favor escribe una tela');
        return;
    }
    
    const existe = telasDisponiblesReflectivo.find(t => 
        t.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarTelaReflectivo(existe.id, existe.nombre, input);
    } else {
        const nuevoId = proximoTelaIdReflectivo++;
        const nuevaTela = { 
            id: nuevoId, 
            nombre: valor,
            referencia: ''
        };
        telasDisponiblesReflectivo.push(nuevaTela);
        
        const td = input.closest('td');
        const idInput = td.querySelector('.tela-id-input-reflectivo');
        input.value = valor;
        idInput.value = nuevoId;
        
        // Limpiar referencia si se crea tela nueva
        const trPadre = td.closest('tr');
        const refInput = trPadre.querySelector('.referencia-input-reflectivo');
        if (refInput) {
            refInput.value = '';
        }
        
        td.querySelector('.tela-suggestions-reflectivo').style.display = 'none';
    }
}

/**
 * AGREGAR FOTO DE TELA
 */
function agregarFotoTelaReflectivo(input) {
    const archivos = input.files;
    if (!archivos || archivos.length === 0) return;
    
    const td = input.closest('td');
    const previewDiv = td.querySelector('.foto-tela-preview-reflectivo');
    
    // Limitar a 3 archivos
    const archivosArray = Array.from(archivos).slice(0, 3);
    previewDiv.innerHTML = '';
    
    archivosArray.forEach((archivo, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.style.cssText = 'position: relative; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
            
            div.innerHTML = `
                <img src="${e.target.result}" style="width: 100%; height: 80px; object-fit: cover; display: block;">
                <button type="button" class="btn-eliminar-foto" onclick="this.parentElement.remove()" style="position: absolute; top: 2px; right: 2px; width: 24px; height: 24px; padding: 0; background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">×</button>
            `;
            
            previewDiv.appendChild(div);
        };
        reader.readAsDataURL(archivo);
    });
}

/**
 * MANEJAR DROP DE ARCHIVOS
 */
function manejarDropReflectivo(event, element) {
    event.preventDefault();
    event.stopPropagation();
    
    const files = event.dataTransfer.files;
    const input = element.querySelector('.input-file-tela-reflectivo');
    
    if (input) {
        input.files = files;
        // Trigger change event
        const changeEvent = new Event('change', { bubbles: true });
        input.dispatchEvent(changeEvent);
    }
    
    element.style.background = '#f0f7ff';
}