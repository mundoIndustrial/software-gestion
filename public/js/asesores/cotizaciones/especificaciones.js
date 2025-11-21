/**
 * SISTEMA DE COTIZACIONES - ESPECIFICACIONES Y SECCIONES
 * Responsabilidad: Modal de especificaciones, secciones de ubicación
 */

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

function agregarSeccion() {
    const seccion = document.getElementById('seccion_prenda').value;
    const contenedor = document.getElementById('secciones_agregadas');
    if (!seccion) {
        alert('Por favor selecciona una sección');
        return;
    }
    
    let ubicaciones = [];
    if (seccion === 'CAMISA') {
        ubicaciones = ['LADO IZQUIERDO', 'LADO DERECHO', 'ESPALDA', 'MANGA'];
    } else if (seccion === 'JEAN_SUDADERA') {
        ubicaciones = ['PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO'];
    } else if (seccion === 'GORRAS') {
        ubicaciones = ['FRONTAL', 'LATERAL'];
    }
    
    const seccionDiv = document.createElement('div');
    seccionDiv.style.cssText = 'background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; position: relative;';
    
    const titulo = document.createElement('div');
    titulo.style.cssText = 'font-weight: bold; font-size: 1.1rem; margin-bottom: 10px;';
    titulo.innerHTML = seccion;
    seccionDiv.appendChild(titulo);
    
    const tabla = document.createElement('table');
    tabla.style.cssText = 'width: 100%; border-collapse: collapse; margin-bottom: 10px;';
    
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
            <th style="padding: 10px; text-align: left;">Ubicación</th>
            <th style="padding: 10px; text-align: center; width: 50px;">Acción</th>
        </tr>
    `;
    tabla.appendChild(thead);
    
    const tbody = document.createElement('tbody');
    ubicaciones.forEach(ubicacion => {
        const fila = document.createElement('tr');
        fila.style.borderBottom = '1px solid #ddd';
        fila.innerHTML = `
            <td style="padding: 10px;">
                <input type="hidden" name="ubicaciones_seccion[]" value="${seccion}">
                <input type="hidden" name="ubicaciones[]" value="${ubicacion}">
                ${ubicacion}
            </td>
            <td style="padding: 10px; text-align: center;">
                <button type="button" onclick="this.closest('tr').remove()" style="background: #f44336; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">✕</button>
            </td>
        `;
        tbody.appendChild(fila);
    });
    tabla.appendChild(tbody);
    seccionDiv.appendChild(tabla);
    
    const btnAgregar = document.createElement('button');
    btnAgregar.type = 'button';
    btnAgregar.textContent = '+';
    btnAgregar.style.cssText = 'position: absolute; top: 10px; right: 10px; background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;';
    btnAgregar.onclick = function() {
        const fila = document.createElement('tr');
        fila.style.borderBottom = '1px solid #ddd';
        fila.innerHTML = `
            <td style="padding: 10px;">
                <input type="text" name="ubicaciones[]" class="input-large" style="width: 100%; padding: 5px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Ubicación...">
            </td>
            <td style="padding: 10px; text-align: center;">
                <button type="button" onclick="this.closest('tr').remove()" style="background: #f44336; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">✕</button>
            </td>
        `;
        tbody.appendChild(fila);
    };
    seccionDiv.appendChild(btnAgregar);
    
    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.textContent = '✕';
    btnEliminar.style.cssText = 'position: absolute; top: 10px; right: 50px; background: #f44336; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; line-height: 1;';
    btnEliminar.onclick = function() {
        seccionDiv.remove();
    };
    seccionDiv.appendChild(btnEliminar);
    
    const obsDiv = document.createElement('div');
    obsDiv.style.marginTop = '10px';
    obsDiv.innerHTML = `
        <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">
            <i class="fas fa-sticky-note"></i> Observación
        </label>
        <textarea name="observaciones_seccion[]" class="input-large" rows="2" placeholder="Observación..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;"></textarea>
    `;
    seccionDiv.appendChild(obsDiv);
    
    contenedor.appendChild(seccionDiv);
    document.getElementById('seccion_prenda').value = '';
}
