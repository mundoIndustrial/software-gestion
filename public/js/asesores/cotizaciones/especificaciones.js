/**
 * SISTEMA DE COTIZACIONES - ESPECIFICACIONES Y SECCIONES
 * Responsabilidad: Modal de especificaciones, secciones de ubicación
 */

// Agregar estilos de animación
const shakeStyleElement = document.createElement('style');
shakeStyleElement.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    .shake {
        animation: shake 0.5s ease-in-out;
    }
`;
document.head.appendChild(shakeStyleElement);

// Índice en edición para secciones friendly
let seccionEditIndexFriendly = null;

// ============ MODAL ESPECIFICACIONES ============

function abrirModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    if (modal) modal.style.display = 'flex';
}

function cerrarModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    if (modal) modal.style.display = 'none';
}

function guardarEspecificaciones() {
    const especificaciones = {};
    const modal = document.getElementById('modalEspecificaciones');
    if (!modal) return;
    
    // Mapeo de categorías
    const categoriasMap = {
        'tbody_disponibilidad': 'disponibilidad',
        'tbody_pago': 'forma_pago',
        'tbody_regimen': 'regimen',
        'tbody_vendido': 'se_ha_vendido',
        'tbody_ultima_venta': 'ultima_venta',
        'tbody_flete': 'flete'
    };
    

    
    // Procesar cada categoría
    Object.entries(categoriasMap).forEach(([tbodyId, categoriaKey]) => {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) {

            return;
        }
        

        
        const filas = tbody.querySelectorAll('tr');
        const valoresSeleccionados = [];
        

        
        filas.forEach((fila, filaIndex) => {
            const checkbox = fila.querySelector('input[type="checkbox"]');
            const label = fila.querySelector('label');
            
            // Obtener todos los inputs de texto
            const allTextInputs = fila.querySelectorAll('input[type="text"]');
            let itemInput = null;
            let obsInput = null;
            
            if (allTextInputs.length === 1) {
                // Solo hay un input: es para observaciones (categorías con label fijo)
                obsInput = allTextInputs[0];
            } else if (allTextInputs.length >= 2) {
                // Hay dos o más inputs: primero es el valor, último es observaciones
                itemInput = allTextInputs[0];
                obsInput = allTextInputs[allTextInputs.length - 1];
            }
            

            
            // Si está marcado, guardar el valor con observaciones
            if (checkbox && checkbox.checked) {
                let valor = '';
                let observacion = '';
                
                // Obtener observación si existe
                if (obsInput && obsInput.value.trim()) {
                    observacion = obsInput.value.trim();
                }
                
                // Prioridad: label (para items fijos) > input value (para items personalizados) > "✓" (si solo está marcado)
                if (label) {
                    // Si es un label fijo (para categorías como DISPONIBILIDAD, FORMA DE PAGO, etc.)
                    valor = label.textContent.trim();
                } else if (itemInput && itemInput.value.trim()) {
                    // Si hay input con valor (para categorías como SE HA VENDIDO, ÚLTIMA VENTA)
                    valor = itemInput.value.trim();
                } else {
                    // Si solo está marcado sin valor, guardar "✓"
                    valor = '✓';
                }
                
                if (valor) {
                    // Crear objeto con valor y observación
                    const item = {
                        valor: valor,
                        observacion: observacion || ''
                    };
                    valoresSeleccionados.push(item);

                }
            }
        });
        
        // Solo guardar la categoría si tiene valores seleccionados
        if (valoresSeleccionados.length > 0) {
            especificaciones[categoriaKey] = valoresSeleccionados;

        }
    });
    
    window.especificacionesSeleccionadas = especificaciones;


    
    //  ACTUALIZAR COLOR DEL BOTÓN ENVIAR
    actualizarColorBotonEnviar();
    
    cerrarModalEspecificaciones();
}

//  FUNCIÓN PARA ACTUALIZAR COLOR DEL BOTÓN ENVIAR
function actualizarColorBotonEnviar() {
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    if (!btnEnviar) return;
    
    const especificaciones = window.especificacionesSeleccionadas || {};
    const tieneEspecificaciones = Object.keys(especificaciones).length > 0;
    
    if (tieneEspecificaciones) {
        // Verde: tiene especificaciones
        btnEnviar.style.background = '#10b981';
        btnEnviar.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.4)';
        btnEnviar.title = ' Especificaciones completadas - Listo para enviar';

    } else {
        // Rojo: falta especificaciones
        btnEnviar.style.background = '#ef4444';
        btnEnviar.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.3)';
        btnEnviar.title = ' Falta completar especificaciones';

    }
}

function agregarFilaEspecificacion(categoria) {
    const tbodyId = 'tbody_' + categoria;
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    
    const fila = document.createElement('tr');
    fila.innerHTML = `
        <td><input type="text" name="tabla_orden[${categoria}_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
        <td style="text-align: center;">
            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
        </td>
        <td style="padding: 10px;">
            <input type="text" name="tabla_orden[${categoria}_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
        </td>
        <td style="text-align: center;">
            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    `;
    tbody.appendChild(fila);
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('modalEspecificaciones');
    if (e.target === modal) {
        cerrarModalEspecificaciones();
    }
});

//  INICIALIZAR COLOR DEL BOTÓN AL CARGAR LA PÁGINA
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        actualizarColorBotonEnviar();
    }, 500);
});

// ============ SECCIONES DE UBICACIÓN ============

// Opciones por ubicación
const opcionesPorUbicacionFriendly = {
    'CAMISA': ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO'],
    'JEAN_SUDADERA': ['PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO'],
    'GORRAS': ['FRENTE', 'LATERAL', 'TRASERA']
};

window.seccionesSeleccionadasFriendly = [];

function agregarSeccion() {
    const selector = document.getElementById('seccion_prenda');
    const ubicacion = selector.value;
    const errorDiv = document.getElementById('errorSeccionPrendaFriendly');
    
    if (!ubicacion) {
        // Mostrar error
        selector.style.border = '2px solid #ef4444';
        selector.style.background = '#fee2e2';
        selector.classList.add('shake');
        
        if (errorDiv) {
            errorDiv.style.display = 'block';
        }
        
        // Remover efecto después de 600ms
        setTimeout(() => {
            selector.style.border = '';
            selector.style.background = '';
            selector.classList.remove('shake');
        }, 600);
        
        // Remover mensaje de error después de 3 segundos
        setTimeout(() => {
            if (errorDiv) errorDiv.style.display = 'none';
        }, 3000);
        
        return;
    }
    
    // Limpiar error si hay selección
    selector.style.border = '';
    selector.style.background = '';
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
    
    // Crear modal con opciones
    const opciones = opcionesPorUbicacionFriendly[ubicacion] || [];
    
    let html = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;" id="modalUbicacionFriendly">
            <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; color: #1e40af; font-size: 1.1rem;">${ubicacion}</h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" onclick="cerrarModalUbicacionFriendly()" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">×</button>
                        <button type="button" onclick="guardarUbicacionFriendly('${ubicacion}')" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">+</button>
                    </div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #333;">Ubicación</label>
                    <div id="opcionesUbicacionFriendly" style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 300px; overflow-y: auto;"></div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333;">Observaciones de ${ubicacion}</label>
                    <textarea id="obsUbicacionFriendly" placeholder="Observaciones..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem; resize: vertical; min-height: 80px;"></textarea>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', html);
    
    // Agregar opciones como checkboxes (con delay para que el DOM se actualice)
    setTimeout(() => {
        const container = document.getElementById('opcionesUbicacionFriendly');


        
        if (container && opciones.length > 0) {
            opciones.forEach(opcion => {
                const label = document.createElement('label');
                label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border-radius: 6px; transition: background 0.2s;';
                label.innerHTML = `
                    <input type="checkbox" value="${opcion}" style="width: 18px; height: 18px; cursor: pointer;">
                    <span>${opcion}</span>
                `;
                label.addEventListener('mouseover', () => label.style.background = '#f0f7ff');
                label.addEventListener('mouseout', () => label.style.background = 'transparent');
                container.appendChild(label);
            });
        } else {

        }
    }, 10);
}

function cerrarModalUbicacionFriendly() {
    const modal = document.getElementById('modalUbicacionFriendly');
    if (modal) modal.remove();
}

function guardarUbicacionFriendly(ubicacion) {
    const checkboxes = document.querySelectorAll('#opcionesUbicacionFriendly input[type="checkbox"]:checked');
    const obs = document.getElementById('obsUbicacionFriendly').value;
    const container = document.getElementById('opcionesUbicacionFriendly');
    
    if (checkboxes.length === 0) {
        // Efecto de temblor y color rojo
        container.style.border = '2px solid #ef4444';
        container.style.background = '#fee2e2';
        container.style.borderRadius = '6px';
        container.style.padding = '0.5rem';
        container.classList.add('shake');
        
        // Remover efecto después de 600ms
        setTimeout(() => {
            container.style.border = '';
            container.style.background = '';
            container.style.padding = '';
            container.classList.remove('shake');
        }, 600);
        
        return;
    }
    
    const opciones = Array.from(checkboxes).map(cb => cb.value);

    const seccionPayload = {
        ubicacion: ubicacion,
        opciones: opciones,
        observaciones: obs
    };
    
    if (seccionEditIndexFriendly !== null && seccionEditIndexFriendly >= 0 && seccionEditIndexFriendly < seccionesSeleccionadasFriendly.length) {
        seccionesSeleccionadasFriendly[seccionEditIndexFriendly] = seccionPayload;
    } else {
        seccionesSeleccionadasFriendly.push(seccionPayload);
    }
    seccionEditIndexFriendly = null;
    
    cerrarModalUbicacionFriendly();
    document.getElementById('seccion_prenda').value = '';
    renderizarSeccionesFriendly();
    
    //  ACTUALIZAR EL CAMPO OCULTO paso3_secciones_datos
    const campoOculto = document.getElementById('paso3_secciones_datos');
    if (campoOculto) {
        campoOculto.value = JSON.stringify(seccionesSeleccionadasFriendly);

    }
}

function renderizarSeccionesFriendly() {
    const container = document.getElementById('secciones_agregadas');
    container.innerHTML = '';
    
    seccionesSeleccionadasFriendly.forEach((seccion, index) => {
        const div = document.createElement('div');
        div.style.cssText = 'background: white; border: 2px solid #3498db; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;';
        
        const opcionesText = Array.isArray(seccion.opciones)
            ? seccion.opciones
                .map(opt => {
                    if (typeof opt === 'object') {
                        return opt.nombre || opt.ubicacion || opt.seccion || opt.valor || JSON.stringify(opt);
                    }
                    return opt;
                })
                .join(', ')
            : (typeof seccion.opciones === 'object'
                ? (seccion.opciones.nombre || seccion.opciones.ubicacion || seccion.opciones.seccion || seccion.opciones.valor || JSON.stringify(seccion.opciones))
                : (seccion.opciones || ''));

        const ubicacionText = (typeof seccion.ubicacion === 'object')
            ? (seccion.ubicacion.nombre || seccion.ubicacion.ubicacion || seccion.ubicacion.seccion || JSON.stringify(seccion.ubicacion))
            : (seccion.ubicacion || seccion);

        const tallasText = Array.isArray(seccion.tallas) && seccion.tallas.length > 0
            ? seccion.tallas
                .map(t => {
                    if (typeof t === 'object') {
                        const nombreTalla = t.talla || t.nombre || t.valor || '';
                        const cantidad = t.cantidad ? ` (${t.cantidad})` : '';
                        return `${nombreTalla}${cantidad}`.trim();
                    }
                    return t;
                })
                .filter(Boolean)
                .join(', ')
            : '';

        const obsText = seccion.observaciones || '';
        
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                <div>
                    <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 0.95rem;">${ubicacionText}</h4>
                    <p style="margin: 0 0 0.5rem 0; color: #666; font-size: 0.85rem;"><strong>Ubicación:</strong> ${opcionesText}</p>
                    ${tallasText ? `<p style="margin: 0 0 0.5rem 0; color: #666; font-size: 0.85rem;"><strong>Tallas:</strong> ${tallasText}</p>` : ''}
                    ${obsText ? `<p style="margin: 0; color: #666; font-size: 0.85rem;"><strong>Observaciones:</strong> ${obsText}</p>` : ''}
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" onclick="editarSeccionFriendly(${index})" style="background: #0ea5e9; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;">✎</button>
                    <button type="button" onclick="eliminarSeccionFriendly(${index})" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;">×</button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });

    // Sincronizar campo oculto paso3_secciones_datos (para envíos y persistencia)
    const hiddenSecciones = document.getElementById('paso3_secciones_datos');
    if (hiddenSecciones) {
        hiddenSecciones.value = JSON.stringify(seccionesSeleccionadasFriendly);
    }
}

function eliminarSeccionFriendly(index) {
    seccionesSeleccionadasFriendly.splice(index, 1);
    renderizarSeccionesFriendly();
    
    //  ACTUALIZAR EL CAMPO OCULTO paso3_secciones_datos
    const campoOculto = document.getElementById('paso3_secciones_datos');
    if (campoOculto) {
        campoOculto.value = JSON.stringify(seccionesSeleccionadasFriendly);

    }
}

// Editar seccion existente (usa el mismo modal de creación)
function editarSeccionFriendly(index) {
    const seccion = seccionesSeleccionadasFriendly[index];
    if (!seccion) return;
    seccionEditIndexFriendly = index;
    
    // Preferir modal de paso-tres (abre con tallas y ubicaciones)
    if (typeof abrirModalUbicaciones === 'function') {
        const ubicacion = seccion.ubicacion || '';
        const opciones = seccion.opciones || [];
        const tallas = seccion.tallas || [];
        const obs = seccion.observaciones || '';

        abrirModalUbicaciones(
            ubicacion,
            opciones,
            tallas,
            (nuevasUbicaciones, nuevasTallas, nuevasObs) => {
                seccionesSeleccionadasFriendly[index] = {
                    ubicacion,
                    opciones: nuevasUbicaciones,
                    tallas: nuevasTallas,
                    observaciones: nuevasObs || ''
                };
                renderizarSeccionesFriendly();
                if (typeof cerrarModalUbicacion === 'function') {
                    cerrarModalUbicacion('modalUbicaciones');
                }
            },
            obs
        );
        return;
    }
    
    // Si no existe el modal anterior, intentar el de friendly; si tampoco existe, crear inline
    if (typeof abrirModalUbicacionFriendly === 'function') {
        abrirModalUbicacionFriendly();
    } else {
        // Modal inline básico si no existe el de paso-tres
        const existing = document.getElementById('modalUbicacionFriendly');
        if (existing) existing.remove();
        const modal = document.createElement('div');
        modal.id = 'modalUbicacionFriendly';
        modal.style.cssText = 'position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.4);z-index:9999;';
        modal.innerHTML = `
            <div style="background:white;padding:1rem;border-radius:8px;max-width:420px;width:100%;box-shadow:0 10px 30px rgba(0,0,0,0.25);">
                <h3 style="margin-top:0;margin-bottom:0.75rem;color:#0ea5e9;">Editar sección</h3>
                <label style="display:block;margin-bottom:0.25rem;font-weight:600;">Sección</label>
                <input id="seccion_prenda" type="text" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;margin-bottom:0.75rem;" />
                <div id="opcionesUbicacionFriendly" style="margin-bottom:0.75rem;">
                    ${(window.todasLasUbicaciones || ['PECHO','ESPALDA']).map(opt => `
                        <label style="display:inline-flex;align-items:center;gap:6px;margin:4px 8px 4px 0;font-size:0.9rem;">
                            <input type="checkbox" value="${opt}"> ${opt}
                        </label>
                    `).join('')}
                </div>
                <label style="display:block;margin-bottom:0.25rem;font-weight:600;">Observaciones</label>
                <textarea id="obsUbicacionFriendly" rows="2" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;"></textarea>
                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:1rem;">
                    <button onclick="cerrarModalUbicacionFriendly()" style="padding:8px 12px;border:1px solid #ccc;border-radius:4px;background:#f3f4f6;">Cancelar</button>
                    <button onclick="guardarUbicacionFriendly(document.getElementById('seccion_prenda').value)" style="padding:8px 12px;border:none;border-radius:4px;background:#0ea5e9;color:white;font-weight:700;">Guardar</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Rellenar campos después de que el modal exista en el DOM
    setTimeout(() => {
        const selector = document.getElementById('seccion_prenda');
        if (selector && seccion.ubicacion) {
            selector.value = seccion.ubicacion;
        }
        
        const checkboxes = document.querySelectorAll('#opcionesUbicacionFriendly input[type="checkbox"]');
        if (checkboxes && seccion.opciones) {
            const opcionesSet = new Set(
                Array.isArray(seccion.opciones) ? seccion.opciones.map(o => (typeof o === 'object' ? (o.valor || o.nombre || o.ubicacion || o.seccion) : o)) : []
            );
            checkboxes.forEach(cb => cb.checked = opcionesSet.has(cb.value));
        }
        
        const obsInput = document.getElementById('obsUbicacionFriendly');
        if (obsInput) {
            obsInput.value = seccion.observaciones || '';
        }
    }, 0);
}
