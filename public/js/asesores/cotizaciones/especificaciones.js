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
    
    console.log('🔍 Buscando especificaciones en modal...');
    
    // Procesar cada categoría
    Object.entries(categoriasMap).forEach(([tbodyId, categoriaKey]) => {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) {
            console.warn(`⚠️ No encontrado: ${tbodyId}`);
            return;
        }
        
        console.log(`📋 Procesando ${categoriaKey} (${tbodyId})`);
        
        const filas = tbody.querySelectorAll('tr');
        const valoresSeleccionados = [];
        
        console.log(`   Encontradas ${filas.length} filas`);
        
        filas.forEach((fila, filaIndex) => {
            const checkbox = fila.querySelector('input[type="checkbox"]');
            const itemInput = fila.querySelector('input[type="text"]');
            const label = fila.querySelector('label');
            
            console.log(`   Fila ${filaIndex}: checkbox=${checkbox ? checkbox.checked : 'no'}, input=${itemInput ? 'sí' : 'no'}, label=${label ? label.textContent : 'no'}`);
            
            // Si está marcado, guardar el valor
            if (checkbox && checkbox.checked) {
                let valor = '';
                
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
                    valoresSeleccionados.push(valor);
                    console.log(`      ✅ Valor guardado: ${valor}`);
                }
            }
        });
        
        // Solo guardar la categoría si tiene valores seleccionados
        if (valoresSeleccionados.length > 0) {
            especificaciones[categoriaKey] = valoresSeleccionados;
            console.log(`✅ ${categoriaKey}: ${valoresSeleccionados.join(', ')}`);
        }
    });
    
    window.especificacionesSeleccionadas = especificaciones;
    console.log('✅ Especificaciones guardadas:', especificaciones);
    console.log('📊 Total categorías:', Object.keys(especificaciones).length);
    cerrarModalEspecificaciones();
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
        <td style="display: flex; gap: 5px;">
            <input type="text" name="tabla_orden[${categoria}_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
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

// ============ SECCIONES DE UBICACIÓN ============

// Opciones por ubicación
const opcionesPorUbicacionFriendly = {
    'CAMISA': ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO'],
    'JEAN_SUDADERA': ['PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO'],
    'GORRAS': ['FRENTE', 'LATERAL', 'TRASERA']
};

let seccionesSeleccionadasFriendly = [];

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
        console.log('Opciones Friendly:', opciones);
        console.log('Container Friendly:', container);
        
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
            console.log('Container o opciones vacías Friendly');
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
    
    seccionesSeleccionadasFriendly.push({
        ubicacion: ubicacion,
        opciones: opciones,
        observaciones: obs
    });
    
    cerrarModalUbicacionFriendly();
    document.getElementById('seccion_prenda').value = '';
    renderizarSeccionesFriendly();
}

function renderizarSeccionesFriendly() {
    const container = document.getElementById('secciones_agregadas');
    container.innerHTML = '';
    
    seccionesSeleccionadasFriendly.forEach((seccion, index) => {
        const div = document.createElement('div');
        div.style.cssText = 'background: white; border: 2px solid #3498db; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;';
        
        const opcionesText = Array.isArray(seccion.opciones) ? seccion.opciones.join(', ') : seccion;
        const ubicacionText = seccion.ubicacion || seccion;
        const obsText = seccion.observaciones || '';
        
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                <div>
                    <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 0.95rem;">${ubicacionText}</h4>
                    <p style="margin: 0 0 0.5rem 0; color: #666; font-size: 0.85rem;"><strong>Ubicación:</strong> ${opcionesText}</p>
                    ${obsText ? `<p style="margin: 0; color: #666; font-size: 0.85rem;"><strong>Observaciones:</strong> ${obsText}</p>` : ''}
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" onclick="eliminarSeccionFriendly(${index})" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;">×</button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function eliminarSeccionFriendly(index) {
    seccionesSeleccionadasFriendly.splice(index, 1);
    renderizarSeccionesFriendly();
}
