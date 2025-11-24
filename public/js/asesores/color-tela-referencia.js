/**
 * SISTEMA DE BÚSQUEDA Y CREACIÓN DE COLOR, TELA Y REFERENCIA
 * Find/Create: Busca si existe, si no, crea uno nuevo
 */

// Datos de ejemplo (en producción vendrían de la API)
const coloresDisponibles = [
    { id: 1, nombre: 'Azul' },
    { id: 2, nombre: 'Negro' },
    { id: 3, nombre: 'Gris' },
    { id: 4, nombre: 'Blanco' },
    { id: 5, nombre: 'Naranja' },
    { id: 6, nombre: 'Rojo' },
    { id: 7, nombre: 'Verde' },
    { id: 8, nombre: 'Amarillo' }
];

const telasDisponibles = [
    { id: 1, nombre: 'NAPOLES', referencia: 'REF-NAP-001' },
    { id: 2, nombre: 'DRILL BORNEO', referencia: 'REF-DB-001' },
    { id: 3, nombre: 'OXFORD', referencia: 'REF-OX-001' },
    { id: 4, nombre: 'JERSEY', referencia: 'REF-JER-001' },
    { id: 5, nombre: 'LINO', referencia: 'REF-LIN-001' }
];

let proximoColorId = 9;
let proximoTelaId = 6;

/**
 * BÚSQUEDA DE COLORES
 */
function buscarColor(input) {
    const valor = input.value.toLowerCase().trim();
    const suggestionsDiv = input.closest('td').querySelector('.color-suggestions');
    
    if (!valor) {
        suggestionsDiv.style.display = 'none';
        return;
    }
    
    const coincidencias = coloresDisponibles.filter(c => 
        c.nombre.toLowerCase().includes(valor)
    );
    
    let html = '';
    
    // Mostrar coincidencias
    if (coincidencias.length > 0) {
        html += coincidencias.map(c => `
            <div style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;" 
                 onmouseover="this.style.backgroundColor='#f0f0f0'" 
                 onmouseout="this.style.backgroundColor='white'"
                 onclick="seleccionarColor('${c.id}', '${c.nombre}', this)">
                <strong>${c.nombre}</strong>
            </div>
        `).join('');
    }
    
    // Siempre mostrar opción de crear
    const valorLimpio = input.value.trim();
    html += `
        <div style="padding: 8px 12px; cursor: pointer; border-top: 1px solid #0066cc; background-color: #e6f2ff;" 
             onmouseover="this.style.backgroundColor='#cce5ff'" 
             onmouseout="this.style.backgroundColor='#e6f2ff'"
             onclick="crearColorDesdeSelector('${valorLimpio}', this)">
            <i class="fas fa-plus"></i> <strong>Crear: "${valorLimpio}"</strong>
        </div>
    `;
    
    suggestionsDiv.innerHTML = html;
    suggestionsDiv.style.display = 'block';
}

function seleccionarColor(id, nombre, element) {
    const td = element.closest('td');
    const input = td.querySelector('.color-input');
    const idInput = td.querySelector('.color-id-input');
    
    input.value = nombre;
    idInput.value = id;
    td.querySelector('.color-suggestions').style.display = 'none';
    
    console.log(`✅ Color seleccionado: ${nombre} (ID: ${id})`);
}

function buscarOCrearColor(btn) {
    const td = btn.closest('td');
    const input = td.querySelector('.color-input');
    const idInput = td.querySelector('.color-id-input');
    const valor = input.value.trim();
    
    if (!valor) {
        alert('Por favor escribe un color');
        return;
    }
    
    // Buscar si existe
    const existe = coloresDisponibles.find(c => 
        c.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarColor(existe.id, existe.nombre, btn);
    } else {
        // Crear nuevo
        const nuevoId = proximoColorId++;
        const nuevoColor = { id: nuevoId, nombre: valor };
        coloresDisponibles.push(nuevoColor);
        
        idInput.value = nuevoId;
        td.querySelector('.color-suggestions').style.display = 'none';
        
        console.log(`✅ Nuevo color creado: ${valor} (ID: ${nuevoId})`);
        alert(`✅ Color "${valor}" creado exitosamente`);
    }
}

/**
 * BÚSQUEDA DE TELAS
 */
function buscarTela(input) {
    const valor = input.value.toLowerCase().trim();
    const suggestionsDiv = input.closest('td').querySelector('.tela-suggestions');
    
    if (!valor) {
        suggestionsDiv.style.display = 'none';
        return;
    }
    
    const coincidencias = telasDisponibles.filter(t => 
        t.nombre.toLowerCase().includes(valor) || 
        t.referencia.toLowerCase().includes(valor)
    );
    
    let html = '';
    
    // Mostrar coincidencias
    if (coincidencias.length > 0) {
        html += coincidencias.map(t => `
            <div style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;" 
                 onmouseover="this.style.backgroundColor='#f0f0f0'" 
                 onmouseout="this.style.backgroundColor='white'"
                 onclick="seleccionarTela('${t.id}', '${t.nombre}', '${t.referencia}', this)">
                <strong>${t.nombre}</strong>
                <br>
                <small style="color: #666;">${t.referencia}</small>
            </div>
        `).join('');
    }
    
    // Siempre mostrar opción de crear
    const valorLimpio = input.value.trim();
    html += `
        <div style="padding: 8px 12px; cursor: pointer; border-top: 1px solid #0066cc; background-color: #e6f2ff;" 
             onmouseover="this.style.backgroundColor='#cce5ff'" 
             onmouseout="this.style.backgroundColor='#e6f2ff'"
             onclick="crearTelaDesdeSelector('${valorLimpio}', this)">
            <i class="fas fa-plus"></i> <strong>Crear: "${valorLimpio}"</strong>
        </div>
    `;
    
    suggestionsDiv.innerHTML = html;
    suggestionsDiv.style.display = 'block';
}

function seleccionarTela(id, nombre, referencia, element) {
    const td = element.closest('td');
    const input = td.querySelector('.tela-input');
    const idInput = td.querySelector('.tela-id-input');
    
    input.value = nombre;
    idInput.value = id;
    
    // Llenar también el campo de referencia
    const trPadre = td.closest('tr');
    const refInput = trPadre.querySelector('.referencia-input');
    if (refInput && !refInput.value) {
        refInput.value = referencia;
    }
    
    td.querySelector('.tela-suggestions').style.display = 'none';
    
    console.log(`✅ Tela seleccionada: ${nombre} (ID: ${id})`);
}

function buscarOCrearTela(btn) {
    const td = btn.closest('td');
    const input = td.querySelector('.tela-input');
    const idInput = td.querySelector('.tela-id-input');
    const valor = input.value.trim();
    
    if (!valor) {
        alert('Por favor escribe una tela');
        return;
    }
    
    // Buscar si existe
    const existe = telasDisponibles.find(t => 
        t.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarTela(existe.id, existe.nombre, existe.referencia, btn);
    } else {
        // Crear nueva
        const nuevoId = proximoTelaId++;
        const nuevaTela = { 
            id: nuevoId, 
            nombre: valor,
            referencia: `REF-${valor.substring(0, 3).toUpperCase()}-001`
        };
        telasDisponibles.push(nuevaTela);
        
        idInput.value = nuevoId;
        
        // Llenar también el campo de referencia
        const trPadre = td.closest('tr');
        const refInput = trPadre.querySelector('.referencia-input');
        if (refInput) {
            refInput.value = nuevaTela.referencia;
        }
        
        td.querySelector('.tela-suggestions').style.display = 'none';
        
        console.log(`✅ Nueva tela creada: ${valor} (ID: ${nuevoId})`);
        alert(`✅ Tela "${valor}" creada exitosamente\nReferencia: ${nuevaTela.referencia}`);
    }
}

/**
 * Crear color desde input (Enter)
 */
function crearColorDesdeInput(input) {
    const td = input.closest('td');
    const valor = input.value.trim();
    
    if (!valor) return;
    
    const existe = coloresDisponibles.find(c => 
        c.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarColor(existe.id, existe.nombre, input);
    } else {
        const nuevoId = proximoColorId++;
        const nuevoColor = { id: nuevoId, nombre: valor };
        coloresDisponibles.push(nuevoColor);
        
        const idInput = td.querySelector('.color-id-input');
        idInput.value = nuevoId;
        td.querySelector('.color-suggestions').style.display = 'none';
        
        console.log(`✅ Nuevo color creado: ${valor} (ID: ${nuevoId})`);
    }
}

/**
 * Crear color desde selector (Click en opción)
 */
function crearColorDesdeSelector(valor, element) {
    const td = element.closest('td');
    const input = td.querySelector('.color-input');
    const idInput = td.querySelector('.color-id-input');
    
    const existe = coloresDisponibles.find(c => 
        c.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarColor(existe.id, existe.nombre, element);
    } else {
        const nuevoId = proximoColorId++;
        const nuevoColor = { id: nuevoId, nombre: valor };
        coloresDisponibles.push(nuevoColor);
        
        input.value = valor;
        idInput.value = nuevoId;
        td.querySelector('.color-suggestions').style.display = 'none';
        
        console.log(`✅ Nuevo color creado desde selector: ${valor} (ID: ${nuevoId})`);
    }
}

/**
 * Crear tela desde input (Enter)
 */
function crearTelaDesdeInput(input) {
    const td = input.closest('td');
    const valor = input.value.trim();
    
    if (!valor) return;
    
    const existe = telasDisponibles.find(t => 
        t.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarTela(existe.id, existe.nombre, existe.referencia, input);
    } else {
        const nuevoId = proximoTelaId++;
        const nuevaTela = { 
            id: nuevoId, 
            nombre: valor,
            referencia: `REF-${valor.substring(0, 3).toUpperCase()}-001`
        };
        telasDisponibles.push(nuevaTela);
        
        const idInput = td.querySelector('.tela-id-input');
        idInput.value = nuevoId;
        
        const trPadre = td.closest('tr');
        const refInput = trPadre.querySelector('.referencia-input');
        if (refInput) {
            refInput.value = nuevaTela.referencia;
        }
        
        td.querySelector('.tela-suggestions').style.display = 'none';
        
        console.log(`✅ Nueva tela creada: ${valor} (ID: ${nuevoId})`);
    }
}

/**
 * Crear tela desde selector (Click en opción)
 */
function crearTelaDesdeSelector(valor, element) {
    const td = element.closest('td');
    const input = td.querySelector('.tela-input');
    const idInput = td.querySelector('.tela-id-input');
    
    const existe = telasDisponibles.find(t => 
        t.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarTela(existe.id, existe.nombre, existe.referencia, element);
    } else {
        const nuevoId = proximoTelaId++;
        const nuevaTela = { 
            id: nuevoId, 
            nombre: valor,
            referencia: `REF-${valor.substring(0, 3).toUpperCase()}-001`
        };
        telasDisponibles.push(nuevaTela);
        
        input.value = valor;
        idInput.value = nuevoId;
        
        const trPadre = td.closest('tr');
        const refInput = trPadre.querySelector('.referencia-input');
        if (refInput) {
            refInput.value = nuevaTela.referencia;
        }
        
        td.querySelector('.tela-suggestions').style.display = 'none';
        
        console.log(`✅ Nueva tela creada desde selector: ${valor} (ID: ${nuevoId})`);
    }
}

/**
 * SISTEMA DE IMÁGENES DINÁMICAS
 */
let contadorImagenes = {};

function agregarCampoImagen(btn) {
    const td = btn.closest('td');
    const container = td.querySelector('.tela-imagenes-container');
    const productoCard = td.closest('.producto-card');
    const productoId = productoCard.dataset.productoId || 'default';
    
    // Inicializar contador si no existe
    if (!contadorImagenes[productoId]) {
        contadorImagenes[productoId] = 0;
    }
    
    // Verificar límite de 3 imágenes
    if (contadorImagenes[productoId] >= 3) {
        alert('Máximo 3 imágenes permitidas');
        return;
    }
    
    contadorImagenes[productoId]++;
    const numeroImagen = contadorImagenes[productoId];
    
    // Crear elemento de imagen
    const imagenDiv = document.createElement('div');
    imagenDiv.className = 'tela-preview-item';
    imagenDiv.style.position = 'relative';
    imagenDiv.innerHTML = `
        <div class="tela-preview" data-numero="${numeroImagen}" style="width: 80px; height: 80px; border: 2px dashed #0066cc; border-radius: 4px; display: flex; align-items: center; justify-content: center; background-color: #f9f9f9; cursor: pointer; position: relative; overflow: hidden;">
            <i class="fas fa-image" style="font-size: 2rem; color: #ccc;"></i>
            <input type="file" name="productos_friendly[][tela_imagen_${numeroImagen}]" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;" onchange="previewTelaImagen(this); agregarFotoTela(this);">
        </div>
        <small style="color: #666; font-size: 0.75rem; display: block; text-align: center; margin-top: 4px; numero-label">${numeroImagen}/3</small>
    `;
    
    container.appendChild(imagenDiv);
    
    // Deshabilitar botón si se alcanzó el límite
    if (contadorImagenes[productoId] >= 3) {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.cursor = 'not-allowed';
    }
    
    console.log(`✅ Campo de imagen agregado: ${numeroImagen}/3`);
}

/**
 * Cerrar sugerencias al hacer click fuera
 */
document.addEventListener('click', function(e) {
    if (!e.target.closest('td')) {
        document.querySelectorAll('.color-suggestions, .tela-suggestions, .referencia-suggestions').forEach(div => {
            div.style.display = 'none';
        });
    }
});
