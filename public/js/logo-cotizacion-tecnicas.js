/**
 * EJEMPLO DE INTEGRACIÓN JAVASCRIPT
 * Para: resources/views/cotizaciones/bordado/create.blade.php
 * 
 * Este archivo muestra cómo integrar los endpoints API en la interfaz
 */

// =========================================================
// 1. VARIABLES GLOBALES
// =========================================================

let tecnicasAgregadas = [];
let tiposDisponibles = [];
let logoCotizacionId = null;

// =========================================================
// 2. CARGAR TIPOS DE TÉCNICAS AL INICIALIZAR
// =========================================================

async function cargarTiposDisponibles() {
    try {
        const response = await fetch('/api/logo-cotizacion-tecnicas/tipos-disponibles');
        const data = await response.json();
        
        if (data.success) {
            tiposDisponibles = data.data;
            console.log('✅ Tipos cargados:', tiposDisponibles);
            renderizarSelectTecnicas();
        }
    } catch (error) {
        console.error('❌ Error cargando tipos:', error);
    }
}

// =========================================================
// 3. RENDERIZAR SELECT DE TÉCNICAS
// =========================================================

function renderizarSelectTecnicas() {
    const select = document.getElementById('selectTecnicanueva');
    
    if (!select) {
        console.error('❌ Element selectTecnicanueva no encontrado');
        return;
    }
    
    tiposDisponibles.forEach(tipo => {
        const option = document.createElement('option');
        option.value = tipo.id;
        option.textContent = tipo.nombre;
        if (tipo.color) {
            option.style.backgroundColor = tipo.color;
            option.style.color = '#fff';
        }
        select.appendChild(option);
    });
    
    // Escuchar cambios en el select para cambiar color del botón
    select.addEventListener('change', function() {
        const btnAgregar = document.getElementById('btnAgregarPrendas');
        if (btnAgregar) {
            if (this.value) {
                btnAgregar.style.color = '#1e40af';
                btnAgregar.title = 'Agregar prendas';
                btnAgregar.style.cursor = 'pointer';
            } else {
                btnAgregar.style.color = '#999';
                btnAgregar.title = 'Selecciona una técnica primero';
                btnAgregar.style.cursor = 'not-allowed';
            }
        }
    });
}

// =========================================================
// 4. ABRIR MODAL PARA AGREGAR TÉCNICA
// =========================================================

function abrirModalAgregarTecnica() {
    const selectElement = document.getElementById('selectTecnicanueva');
    const tipoId = selectElement?.value;
    
    console.log('🔍 abrirModalAgregarTecnica() llamado');
    console.log('📌 tipoId:', tipoId);
    console.log('📌 selectElement:', selectElement);
    
    if (!tipoId) {
        console.log('❌ SIN TÉCNICA SELECCIONADA - Llamando abrirModalValidacionTecnica()');
        abrirModalValidacionTecnica();
        return;
    }
    
    console.log('✅ Técnica seleccionada, continuando...');
    
    // Obtener el nombre de la técnica
    const tipo = tiposDisponibles.find(t => t.id == tipoId);
    if (tipo) {
        const nombreElement = document.getElementById('tecnicaSeleccionadaNombre');
        if (nombreElement) {
            nombreElement.textContent = tipo.nombre;
        }
    }
    
    // Limpiar prendas del modal
    const listaPrendas = document.getElementById('listaPrendas');
    if (listaPrendas) {
        listaPrendas.innerHTML = '';
    }
    
    // Agregar una prenda por defecto
    agregarFilaPrenda();
    
    // Mostrar/ocultar mensaje de sin prendas
    const noPrendasMsg = document.getElementById('noPrendasMsg');
    if (noPrendasMsg) {
        noPrendasMsg.style.display = 'none';
    }
    
    // Mostrar el modal
    const modal = document.getElementById('modalAgregarTecnica');
    if (modal) {
        modal.style.display = 'flex';
    }
}

// =========================================================
// 5. FORMULARIO DINÁMICO DE PRENDAS EN MODAL
// =========================================================

function agregarFilaPrenda() {
    const container = document.getElementById('listaPrendas');
    
    if (!container) {
        console.error('❌ Elemento listaPrendas no encontrado');
        return;
    }
    
    const numeroPrenda = container.children.length + 1;
    
    const fila = document.createElement('div');
    fila.className = 'prenda-item';
    fila.style.cssText = 'margin-bottom: 15px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 6px; background: #f9f9f9;';
    fila.innerHTML = `
        <div style="display: grid; gap: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; background: #1e40af; color: white; padding: 10px; border-radius: 4px; font-weight: 600; font-size: 0.95rem;">
                <span>PRENDA <span class="numero-prenda">${numeroPrenda}</span></span>
                <button type="button" onclick="this.closest('.prenda-item').remove(); actualizarNumeracionPrendas();" style="background: #d32f2f; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-weight: 600; font-size: 0.8rem; transition: background 0.2s;" onmouseover="this.style.background='#b71c1c'" onmouseout="this.style.background='#d32f2f'">
                    ✕ Eliminar
                </button>
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #333; font-size: 0.9rem;">Nombre de prenda</label>
                <input type="text" class="nombre_prenda" placeholder="Ej: Camisa, Pantalón" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #333; font-size: 0.9rem;">Ubicaciones</label>
                <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <input type="text" class="ubicacion-input" placeholder="Ej: Pecho, Espalda, Manga" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                    <button type="button" class="btn-agregar-ubicacion" style="background: #1e40af; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.9rem;">
                        + Agregar
                    </button>
                </div>
                <div class="ubicaciones-lista" style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <!-- Ubicaciones agregadas aquí como tags -->
                </div>
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #333; font-size: 0.9rem;">Observaciones</label>
                <textarea class="descripcion" rows="2" placeholder="Describe detalles adicionales" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
            </div>
            
            <!-- Tallas y Cantidades Dinámicas -->
            <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
                <button type="button" onclick="agregarTallaCantidad(this)" style="background: #1e40af; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.85rem;">+ Talla</button>
            </div>
            <div class="tallas-container" style="display: grid; gap: 8px;">
                <!-- Filas de talla-cantidad aquí -->
            </div>
        </div>
    `;
    
    container.appendChild(fila);
    
    // Agregar una fila de talla-cantidad por defecto
    agregarTallaCantidad(fila.querySelector('button'));
}

// =========================================================
// 5.5 ACTUALIZAR NUMERACIÓN DE PRENDAS
// =========================================================

function actualizarNumeracionPrendas() {
    const prendas = document.querySelectorAll('.prenda-item');
    prendas.forEach((prenda, index) => {
        const numeroPrendaElement = prenda.querySelector('.numero-prenda');
        if (numeroPrendaElement) {
            numeroPrendaElement.textContent = index + 1;
        }
    });
}

// 5.6 ELIMINAR FILA DE PRENDA
// =========================================================

function eliminarFilaPrenda(prendasIndex) {
    const item = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
    if (item) {
        item.remove();
        
        // Mostrar mensaje de sin prendas si no hay más prendas
        const listaPrendas = document.getElementById('listaPrendas');
        if (listaPrendas && listaPrendas.children.length === 0) {
            const noPrendasMsg = document.getElementById('noPrendasMsg');
            if (noPrendasMsg) {
                noPrendasMsg.style.display = 'block';
            }
        }
    }
}

// Agregar event listeners para ubicaciones cuando se agrega una prenda
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-agregar-ubicacion')) {
        e.preventDefault();
        const input = e.target.previousElementSibling;
        const ubicacion = input.value.trim();
        
        if (!ubicacion) {
            alert('Escribe una ubicación primero');
            return;
        }
        
        const lista = e.target.closest('div').nextElementSibling;
        
        // Verificar que no esté duplicada
        const existentes = Array.from(lista.querySelectorAll('[data-ubicacion]')).map(tag => tag.getAttribute('data-ubicacion'));
        if (existentes.includes(ubicacion)) {
            alert('Esta ubicación ya fue agregada');
            return;
        }
        
        // Crear tag de ubicación
        const tag = document.createElement('div');
        tag.setAttribute('data-ubicacion', ubicacion);
        tag.style.cssText = 'background: #1e40af; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; display: flex; align-items: center; gap: 6px;';
        tag.innerHTML = `
            ${ubicacion}
            <button type="button" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">×</button>
        `;
        
        // Eliminar ubicación al hacer click en ×
        tag.querySelector('button').addEventListener('click', function() {
            tag.remove();
        });
        
        lista.appendChild(tag);
        input.value = '';
        input.focus();
    }
});

function agregarTallaCantidad(button) {
    console.log('🔍 agregarTallaCantidad() llamado');
    console.log('Button:', button);
    
    // Encontrar la prenda más cercana (el div principal)
    const prendaDiv = button.closest('div[style*="margin-bottom: 15px"]') || button.closest('div').parentElement;
    console.log('prendaDiv:', prendaDiv);
    
    // Buscar el contenedor dentro de esa prenda
    const container = prendaDiv ? prendaDiv.querySelector('.tallas-container') : null;
    
    if (!container) {
        console.error('❌ Contenedor tallas-container no encontrado');
        console.error('prendaDiv:', prendaDiv);
        return;
    }
    
    console.log('✅ Container encontrado:', container);
    
    const filaTC = document.createElement('div');
    filaTC.className = 'talla-cantidad-row';
    filaTC.style.cssText = 'display: flex; gap: 8px; align-items: flex-end;';
    filaTC.innerHTML = `
        <div style="flex: 1;">
            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 3px; color: #333;">Talla</label>
            <input type="text" class="talla-input" placeholder="S, M, L, XL" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div style="flex: 1;">
            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 3px; color: #333;">Cantidad</label>
            <input type="number" class="cantidad-input" min="1" value="1" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <button type="button" onclick="this.parentElement.remove()" style="background: #d9534f; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">×</button>
    `;
    
    container.appendChild(filaTC);
    console.log('✅ Fila de talla-cantidad agregada');
}

// =========================================================
// 6. GUARDAR TÉCNICA CON PRENDAS
// =========================================================

async function guardarTecnica() {
    const selectTecnica = document.getElementById('selectTecnicanueva');
    const tipoId = selectTecnica ? selectTecnica.value : null;
    const prendas = [];
    
    if (!tipoId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Selecciona un tipo de técnica'
        });
        return;
    }
    
    // Recopilar datos de prendas desde el modal
    const prendasRows = document.querySelectorAll('#listaPrendas > div');
    
    console.log('🔍 TOTAL DE PRENDAS ENCONTRADAS:', prendasRows.length);
    
    prendasRows.forEach((fila, index) => {
        const nombrePrenda = fila.querySelector('.nombre_prenda')?.value;
        const descripcion = fila.querySelector('.descripcion')?.value;
        
        console.log(`📋 PRENDA ${index + 1}:`);
        console.log('  - Nombre:', nombrePrenda);
        console.log('  - Descripción:', descripcion);
        
        const ubicacionesChecked = Array.from(
            fila.querySelectorAll('.ubicaciones-lista')[0]?.querySelectorAll('[data-ubicacion]') || []
        ).map(tag => tag.getAttribute('data-ubicacion'));
        
        console.log('  - Ubicaciones:', ubicacionesChecked);
        
        // Recopilar tallas y cantidades
        const tallaCantidadInputs = fila.querySelectorAll('.talla-cantidad-row');
        const tallaCantidad = Array.from(tallaCantidadInputs).map(row => {
            const talla = row.querySelector('.talla-input')?.value;
            const cantidad = row.querySelector('.cantidad-input')?.value;
            console.log(`    📏 Talla: "${talla}", Cantidad: "${cantidad}"`);
            return { talla, cantidad: parseInt(cantidad) };
        });
        
        console.log('  - Tallas y Cantidades encontradas:', tallaCantidad.length);
        tallaCantidad.forEach((tc, idx) => {
            console.log(`    [${idx}] Talla: "${tc.talla}" | Cantidad: ${tc.cantidad}`);
        });
        
        if (!nombrePrenda) {
            console.error('❌ FALTA NOMBRE DE PRENDA');
            alert('Error: Rellena el nombre de todas las prendas');
            throw new Error('Datos incompletos');
        }
        
        // Observaciones es OPCIONAL
        if (!descripcion) {
            console.warn('⚠️ Observaciones vacías (es opcional)');
        }
        
        if (ubicacionesChecked.length === 0) {
            console.error('❌ NO HAY UBICACIONES');
            alert('Error: Agrega al menos una ubicación por prenda');
            throw new Error('Ubicaciones no seleccionadas');
        }
        
        if (tallaCantidad.length === 0) {
            console.error('❌ NO HAY TALLAS');
            alert('Error: Agrega al menos una talla con cantidad');
            throw new Error('Tallas no registradas');
        }
        
        prendas.push({
            nombre_prenda: nombrePrenda,
            descripcion: descripcion,
            ubicaciones: ubicacionesChecked,
            talla_cantidad: tallaCantidad
        });
    });
    
    if (prendas.length === 0) {
        alert('Error: Agrega al menos una prenda');
        return;
    }
    
    // Obtener logoCotizacionId
    const logoCotizacionInput = document.getElementById('logoCotizacionId');
    const logoCotId = logoCotizacionInput ? logoCotizacionInput.value : null;
    
    if (!logoCotId) {
        alert('Error: No se encontró el ID de cotización');
        return;
    }
    
    // Enviar a API
    try {
        const response = await fetch('/api/logo-cotizacion-tecnicas/agregar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                logo_cotizacion_id: logoCotId,
                tipo_logo_id: tipoId,
                prendas: prendas
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Técnica agregada correctamente'
            });
            
            // Cerrar modal
            cerrarModalAgregarTecnica();
            
            // Recargar técnicas
            cargarTecnicasAgregadas();
            
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error al guardar'
            });
        }
    } catch (error) {
        console.error('❌ Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error en la solicitud'
        });
    }
}

// =========================================================
// 7. CARGAR Y RENDERIZAR TÉCNICAS AGREGADAS
// =========================================================

async function cargarTecnicasAgregadas() {
    if (!logoCotizacionId) return;
    
    try {
        const response = await fetch(
            `/api/logo-cotizacion-tecnicas/cotizacion/${logoCotizacionId}`
        );
        const data = await response.json();
        
        if (data.success) {
            tecnicasAgregadas = data.data;
            renderizarTecnicasAgregadas();
        }
    } catch (error) {
        console.error('❌ Error cargando técnicas:', error);
    }
}

function renderizarTecnicasAgregadas() {
    const container = document.getElementById('tecnicas_agregadas');
    const sinTecnicas = document.getElementById('sin_tecnicas');
    
    if (!container) {
        console.error('❌ Elemento tecnicas_agregadas no encontrado');
        return;
    }
    
    container.innerHTML = '';
    
    if (tecnicasAgregadas.length === 0) {
        if (sinTecnicas) sinTecnicas.style.display = 'block';
        return;
    }
    
    if (sinTecnicas) sinTecnicas.style.display = 'none';
    container.innerHTML = '';
    
    tecnicasAgregadas.forEach(tecnica => {
        const div = document.createElement('div');
        div.className = 'card mb-3';
        div.style.borderLeftColor = tecnica.tipo.color;
        div.style.borderLeftWidth = '5px';
        
        const prendasHTML = tecnica.prendas.map(prenda => `
            <li>
                <strong>${prenda.nombre}</strong> - ${prenda.descripcion}
                <br>
                <small>
                    Ubicaciones: ${prenda.ubicaciones.join(', ')}
                    | Tallas: ${(prenda.tallas || []).join(', ') || 'Variadas'}
                    | Cantidad: ${prenda.cantidad}
                </small>
            </li>
        `).join('');
        
        div.innerHTML = `
            <div class="card-header" style="background-color: ${tecnica.tipo.color}22;">
                <h5 style="margin: 0; color: ${tecnica.tipo.color};">
                    <i class="fas fa-${getTipoIcono(tecnica.tipo.nombre)}"></i>
                    ${tecnica.tipo.nombre}
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">${tecnica.observaciones_tecnica || 'Sin observaciones'}</p>
                <h6>Prendas:</h6>
                <ul>
                    ${prendasHTML}
                </ul>
                <button class="btn btn-danger btn-sm" 
                        onclick="eliminarTecnica(${tecnica.id})">
                    Eliminar técnica
                </button>
            </div>
        `;
        
        container.appendChild(div);
    });
}

function getTipoIcono(nombreTipo) {
    const iconos = {
        'BORDADO': 'needle',
        'ESTAMPADO': 'stamp',
        'SUBLIMADO': 'fire',
        'DTF': 'film'
    };
    return iconos[nombreTipo] || 'tools';
}

// =========================================================
// 8. ELIMINAR TÉCNICA
// =========================================================

async function eliminarTecnica(tecnicaId) {
    const confirmacion = await Swal.fire({
        icon: 'warning',
        title: '¿Eliminar técnica?',
        text: 'Esta acción no se puede deshacer',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!confirmacion.isConfirmed) return;
    
    try {
        const response = await fetch(
            `/api/logo-cotizacion-tecnicas/${tecnicaId}`,
            { method: 'DELETE',
              headers: {
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
              }
            }
        );
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Eliminada',
                text: 'Técnica eliminada correctamente'
            });
            cargarTecnicasAgregadas();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    } catch (error) {
        console.error('❌ Error:', error);
    }
}

// =========================================================
// 8. CERRAR MODAL
// =========================================================

function cerrarModalAgregarTecnica() {
    const modal = document.getElementById('modalAgregarTecnica');
    if (modal) {
        modal.style.display = 'none';
    }
}

// =========================================================
// 9. INICIALIZACIÓN
// =========================================================

document.addEventListener('DOMContentLoaded', async function() {
    // Obtener ID de cotización del formulario o URL
    const urlParams = new URLSearchParams(window.location.search);
    logoCotizacionId = document.getElementById('logoCotizacionId')?.value 
                    || urlParams.get('editar')
                    || null;
    
    // Cargar datos iniciales
    await cargarTiposDisponibles();
    
    if (logoCotizacionId) {
        await cargarTecnicasAgregadas();
    }
});

// =========================================================
// EXPORTAR PARA USO EN OTROS SCRIPTS
// =========================================================

window.LogoCotizacion = {
    cargarTiposDisponibles,
    abrirModalAgregarTecnica,
    agregarFilaPrenda,
    guardarTecnica,
    cerrarModalAgregarTecnica,
    cargarTecnicasAgregadas,
    eliminarTecnica,
    abrirModalValidacionTecnica,
    cerrarModalValidacionTecnica
};

// =========================================================
// FUNCIONES DE VALIDACIÓN - MODAL
// =========================================================

function abrirModalValidacionTecnica() {
    console.log('🚀 abrirModalValidacionTecnica() ejecutándose');
    
    const modal = document.getElementById('modalValidacionTecnica');
    
    console.log('🔍 Modal elemento:', modal);
    console.log('🔍 Modal existe:', !!modal);
    
    if (modal) {
        console.log('✅ Modal encontrado, mostrando...');
        modal.style.display = 'flex';
        console.log('✅ Display set a flex');
    } else {
        console.error('❌ Modal no encontrado en el DOM');
    }
}

function cerrarModalValidacionTecnica() {
    console.log('🔴 Cerrando modalValidacionTecnica');
    const modal = document.getElementById('modalValidacionTecnica');
    if (modal) {
        modal.style.display = 'none';
    }
}
