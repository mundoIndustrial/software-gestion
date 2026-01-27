// ============================================================================
// REFLECTIVO COMPONENT - JavaScript
// ============================================================================
// Este archivo contiene toda la lógica para gestionar el proceso de reflectivo
// en prendas. El reflectivo es un proceso opcional que se aplica a las prendas
// seleccionadas con imágenes, ubicaciones y tallas específicas.
// ============================================================================

// Variables globales para reflectivo
window.datosReflectivo = {
    imagenes: [],
    ubicaciones: [],
    aplicarATodas: true,
    tallasPorGenero: {
        dama: [],
        caballero: []
    }
};

// Variables para reflectivo - tallas
window.reflectivoTallasSeleccionadas = {
    dama: { tallas: [], tipo: null },
    caballero: { tallas: [], tipo: null }
};

/**
 * Abre el modal principal de configuración de reflectivo
 */
window.abrirModalReflectivo = function() {
    const modal = document.createElement('div');
    modal.id = 'modal-reflectivo';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10001;';
    
    const container = document.createElement('div');
    container.style.cssText = 'background: white; border-radius: 12px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);';
    
    // Header
    const header = document.createElement('div');
    header.style.cssText = 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; padding: 1.5rem; border-radius: 12px 12px 0 0; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;';
    
    const headerContent = document.createElement('div');
    headerContent.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
    headerContent.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">light_mode</span><h2 style="margin: 0; font-size: 1.25rem;">Configurar Reflectivo</h2>';
    header.appendChild(headerContent);
    
    const btnCerrarHeader = document.createElement('button');
    btnCerrarHeader.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrarHeader.style.cssText = 'background: transparent; color: white; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;';
    btnCerrarHeader.onclick = () => cerrarModalReflectivo();
    header.appendChild(btnCerrarHeader);
    
    container.appendChild(header);
    
    // Content
    const content = document.createElement('div');
    content.style.cssText = 'padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem;';
    
    // Sección 1: Imágenes
    const seccionImagenes = document.createElement('div');
    seccionImagenes.innerHTML = `
        <div>
            <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1rem;">Imágenes (Máximo 3)</h3>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.75rem;" id="reflectivo-imagenes-preview"></div>
            <button type="button" onclick="document.getElementById('reflectivo-img-input').click()" class="btn btn-primary" style="padding: 0.75rem 1rem; font-size: 0.875rem;">
                <span class="material-symbols-rounded" style="margin-right: 0.5rem; font-size: 1rem;">image</span>Agregar Imagen
            </button>
            <input type="file" id="reflectivo-img-input" accept="image/*" style="display: none;" onchange="manejarImagenReflectivo(this)">
        </div>
    `;
    content.appendChild(seccionImagenes);
    
    // Sección 2: Ubicaciones
    const seccionUbicaciones = document.createElement('div');
    seccionUbicaciones.innerHTML = `
        <div>
            <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1rem;">Ubicaciones</h3>
            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                <input type="text" id="reflectivo-ubicacion-input" placeholder="Ej: Pecho, Espalda..." style="flex: 1; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem;">
                <button type="button" onclick="agregarUbicacionReflectivo()" class="btn btn-primary" style="padding: 0.75rem 1rem; font-size: 0.875rem; white-space: nowrap;">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">add</span>
                </button>
            </div>
            <div id="reflectivo-ubicaciones-lista" style="display: flex; flex-direction: column; gap: 0.5rem;"></div>
        </div>
    `;
    content.appendChild(seccionUbicaciones);
    
    // Sección 3: Tallas
    const seccionTallas = document.createElement('div');
    seccionTallas.innerHTML = `
        <div>
            <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1rem;">Aplicar a Tallas</h3>
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 1rem;">
                <input type="checkbox" id="reflectivo-aplicar-todas" checked style="width: 18px; height: 18px; cursor: pointer;">
                <span style="font-size: 0.875rem; color: #1f2937;">Aplicar a todas las tallas</span>
            </label>
            <button type="button" id="reflectivo-btn-editar-tallas" onclick="abrirEditorTallasReflectivo()" class="btn btn-primary" style="padding: 0.75rem 1rem; font-size: 0.875rem; display: none;">
                <span class="material-symbols-rounded" style="margin-right: 0.5rem;">edit</span>Editar Tallas
            </button>
            <div id="reflectivo-tallas-seleccionadas" style="display: none; margin-top: 1rem;"></div>
        </div>
    `;
    content.appendChild(seccionTallas);
    
    // Agregar event listener al checkbox
    setTimeout(() => {
        const checkbox = document.getElementById('reflectivo-aplicar-todas');
        const btnEditar = document.getElementById('reflectivo-btn-editar-tallas');
        if (checkbox && btnEditar) {
            checkbox.addEventListener('change', function() {
                btnEditar.style.display = this.checked ? 'none' : 'block';
                const tallasCont = document.getElementById('reflectivo-tallas-seleccionadas');
                if (tallasCont) {
                    tallasCont.style.display = this.checked ? 'none' : 'block';
                }
            });
        }
    }, 100);
    
    // Sección 4: Observaciones
    const seccionObservaciones = document.createElement('div');
    seccionObservaciones.innerHTML = `
        <div>
            <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1rem;">Observaciones</h3>
            <textarea id="reflectivo-observaciones" placeholder="Agregar observaciones..." style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; font-family: inherit; resize: vertical; min-height: 80px;"></textarea>
        </div>
    `;
    content.appendChild(seccionObservaciones);
    
    container.appendChild(content);
    
    // Footer
    const footer = document.createElement('div');
    footer.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid #e5e7eb;';
    
    const btnCancelar = document.createElement('button');
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; font-size: 0.875rem;';
    btnCancelar.onclick = () => cerrarModalReflectivo();
    footer.appendChild(btnCancelar);
    
    const btnGuardar = document.createElement('button');
    btnGuardar.textContent = 'Guardar';
    btnGuardar.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; font-size: 0.875rem;';
    btnGuardar.onclick = () => guardarConfiguracionReflectivo();
    footer.appendChild(btnGuardar);
    
    container.appendChild(footer);
    modal.appendChild(container);
    document.body.appendChild(modal);
};

/**
 * Cierra el modal de reflectivo
 */
window.cerrarModalReflectivo = function() {
    const modal = document.getElementById('modal-reflectivo');
    if (modal) {
        modal.remove();
    }
    // Desmarcar checkbox si se cancela
    const checkbox = document.getElementById('checkbox-reflectivo');
    if (checkbox) {
        checkbox.checked = false;
    }
};

/**
 * Maneja la carga de imágenes para reflectivo
 */
window.manejarImagenReflectivo = function(input) {
    if (!input.files || input.files.length === 0) return;
    
    if (window.datosReflectivo.imagenes.length >= 3) {
        alert('Máximo 3 imágenes');
        return;
    }
    
    const file = input.files[0];
    if (!file.type.startsWith('image/')) {
        alert('Por favor selecciona una imagen válida');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        // GUARDAR EL ARCHIVO FILE REAL, NO SOLO EL DATA URL
        window.datosReflectivo.imagenes.push({
            nombre: file.name,
            data: e.target.result,
            file: file  // ⚡ ARCHIVO REAL PARA ENVÍO
        });
        actualizarPreviewImagenesReflectivo();
        input.value = '';
    };
    reader.readAsDataURL(file);
};

/**
 * Actualiza el preview de imágenes en el modal
 */
window.actualizarPreviewImagenesReflectivo = function() {
    const preview = document.getElementById('reflectivo-imagenes-preview');
    if (!preview) {

        return;
    }
    

    
    preview.innerHTML = '';
    window.datosReflectivo.imagenes.forEach((img, index) => {
        const imgElement = document.createElement('img');
        imgElement.src = img.data;
        imgElement.style.cssText = 'width: 60px; height: 60px; border-radius: 6px; object-fit: cover; border: 2px solid #0066cc; cursor: pointer;';
        imgElement.title = `Imagen ${index + 1}`;
        imgElement.onclick = () => {
            if (confirm('¿Eliminar esta imagen?')) {
                window.datosReflectivo.imagenes.splice(index, 1);
                actualizarPreviewImagenesReflectivo();
            }
        };
        preview.appendChild(imgElement);
    });
};

/**
 * Selecciona/deselecciona género para reflectivo
 */
window.seleccionarGeneroReflectivo = function(genero) {
    const btn = document.getElementById(`reflectivo-btn-genero-${genero}`);
    if (!btn) return;
    
    const isSelected = btn.dataset.selected === 'true';
    
    if (isSelected) {
        btn.dataset.selected = 'false';
        btn.style.borderColor = '#d1d5db';
        btn.style.background = 'white';
        btn.style.color = '#1f2937';
        window.reflectivoTallasSeleccionadas[genero].tallas = [];
        window.reflectivoTallasSeleccionadas[genero].tipo = null;
    } else {
        btn.dataset.selected = 'true';
        btn.style.borderColor = '#0066cc';
        btn.style.background = '#0066cc';
        btn.style.color = 'white';
    }
    
    // Mostrar/ocultar selector de tipo de talla
    const container = document.getElementById('reflectivo-tipo-talla-container');
    const btnDama = document.getElementById('reflectivo-btn-genero-dama');
    const btnCaballero = document.getElementById('reflectivo-btn-genero-caballero');
    
    if (!container || !btnDama || !btnCaballero) return;
    
    const dama = btnDama.dataset.selected === 'true';
    const caballero = btnCaballero.dataset.selected === 'true';
    
    if (dama || caballero) {
        container.style.display = 'block';
        actualizarTallasReflectivo();
    } else {
        container.style.display = 'none';
        const grid = document.getElementById('reflectivo-tallas-grid');
        const tabla = document.getElementById('reflectivo-tallas-tabla-container');
        if (grid) grid.innerHTML = '';
        if (tabla) tabla.style.display = 'none';
    }
};

/**
 * Actualiza la grid de tallas disponibles
 */
window.actualizarTallasReflectivo = function() {
    const grid = document.getElementById('reflectivo-tallas-grid');
    if (!grid) return;
    
    grid.innerHTML = '';
    
    const tipoSelect = document.getElementById('reflectivo-tipo-talla');
    if (!tipoSelect) return;
    
    const tipo = tipoSelect.value;
    const btnDama = document.getElementById('reflectivo-btn-genero-dama');
    const btnCaballero = document.getElementById('reflectivo-btn-genero-caballero');
    
    if (!btnDama || !btnCaballero) return;
    
    const dama = btnDama.dataset.selected === 'true';
    const caballero = btnCaballero.dataset.selected === 'true';
    
    const tallas = tipo === 'letra' 
        ? ['XS', 'S', 'M', 'L', 'XL', 'XXL']
        : ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];
    
    tallas.forEach(talla => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = talla;
        btn.style.cssText = 'padding: 0.75rem; border: 2px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer; font-weight: 500; color: #1f2937; transition: all 0.2s;';
        btn.onclick = () => agregarTallaReflectivo(talla, tipo, btn);
        grid.appendChild(btn);
    });
};

/**
 * Agrega una talla a la selección
 */
window.agregarTallaReflectivo = function(talla, tipo, btn) {
    const dama = document.getElementById('reflectivo-btn-genero-dama').dataset.selected === 'true';
    const caballero = document.getElementById('reflectivo-btn-genero-caballero').dataset.selected === 'true';
    
    if (dama) {
        if (!window.reflectivoTallasSeleccionadas.dama.tallas.includes(talla)) {
            window.reflectivoTallasSeleccionadas.dama.tallas.push(talla);
            window.reflectivoTallasSeleccionadas.dama.tipo = tipo;
        }
    }
    
    if (caballero) {
        if (!window.reflectivoTallasSeleccionadas.caballero.tallas.includes(talla)) {
            window.reflectivoTallasSeleccionadas.caballero.tallas.push(talla);
            window.reflectivoTallasSeleccionadas.caballero.tipo = tipo;
        }
    }
    
    btn.style.borderColor = '#0066cc';
    btn.style.background = '#0066cc';
    btn.style.color = 'white';
    
    actualizarTablaTallasReflectivo();
};

/**
 * Actualiza la tabla de tallas seleccionadas
 */
window.actualizarTablaTallasReflectivo = function() {
    const tbody = document.getElementById('reflectivo-tallas-tbody');
    const container = document.getElementById('reflectivo-tallas-tabla-container');
    
    tbody.innerHTML = '';
    
    const todasLasTallas = [];
    
    if (window.reflectivoTallasSeleccionadas.dama.tallas.length > 0) {
        window.reflectivoTallasSeleccionadas.dama.tallas.forEach(talla => {
            todasLasTallas.push({ talla, genero: 'dama' });
        });
    }
    
    if (window.reflectivoTallasSeleccionadas.caballero.tallas.length > 0) {
        window.reflectivoTallasSeleccionadas.caballero.tallas.forEach(talla => {
            todasLasTallas.push({ talla, genero: 'caballero' });
        });
    }
    
    if (todasLasTallas.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    
    todasLasTallas.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.style.cssText = 'border-bottom: 1px solid #d1d5db;';
        tr.innerHTML = `
            <td style="padding: 0.75rem; font-size: 0.875rem;">${item.talla} (${item.genero})</td>
            <td style="padding: 0.75rem; text-align: center;">
                <input type="number" value="1" min="1" style="width: 60px; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; text-align: center; font-size: 0.875rem;">
            </td>
            <td style="padding: 0.75rem; text-align: center;">
                <button type="button" onclick="eliminarTallaReflectivo('${item.talla}', '${item.genero}')" class="btn btn-sm" style="background: #ef4444; color: white; padding: 0.5rem 0.75rem; font-size: 0.75rem; border: none; cursor: pointer; border-radius: 4px;">
                    <span class="material-symbols-rounded" style="font-size: 0.9rem;">delete</span>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
};

/**
 * Elimina una talla de la selección
 */
window.eliminarTallaReflectivo = function(talla, genero) {
    const index = window.reflectivoTallasSeleccionadas[genero].tallas.indexOf(talla);
    if (index > -1) {
        window.reflectivoTallasSeleccionadas[genero].tallas.splice(index, 1);
    }
    actualizarTablaTallasReflectivo();
};

/**
 * Genera selectores de tallas de reflectivo
 */
window.generarSelectoresTallasReflectivo = function() {
    // Esta función ya no se usa, se reemplazó con seleccionarGeneroReflectivo
};

/**
 * Genera selectores de tallas genéricos
 */
window.generarSelectoresTallas = function() {
    const container = document.getElementById('reflectivo-tallas-generos');
    if (!container) return;
    
    container.innerHTML = '';
    
    // Obtener tallas seleccionadas del modal de tallas
    const tallasSeleccionadas = window.tallasSeleccionadas || { dama: { tallas: [] }, caballero: { tallas: [] } };
    
    ['dama', 'caballero'].forEach(genero => {
        const tallas = tallasSeleccionadas[genero]?.tallas || [];
        if (tallas.length === 0) return;
        
        const div = document.createElement('div');
        div.innerHTML = `<h4 style="margin: 0 0 0.5rem 0; color: #1f2937; text-transform: capitalize;">${genero}</h4>`;
        
        const tallasList = document.createElement('div');
        tallasList.style.cssText = 'display: flex; flex-wrap: wrap; gap: 0.5rem;';
        
        tallas.forEach(talla => {
            const label = document.createElement('label');
            label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; cursor: pointer;';
            label.innerHTML = `
                <input type="checkbox" class="reflectivo-talla-${genero}" value="${talla}" style="width: 16px; height: 16px; cursor: pointer;">
                <span style="font-size: 0.875rem;">${talla}</span>
            `;
            tallasList.appendChild(label);
        });
        
        div.appendChild(tallasList);
        container.appendChild(div);
    });
};

/**
 * Agrega una ubicación al reflectivo
 */
window.agregarUbicacionReflectivo = function() {
    const input = document.getElementById('reflectivo-ubicacion-input');
    const ubicacion = input.value.trim();
    
    if (!ubicacion) {
        alert('Por favor escribe una ubicación');
        return;
    }
    
    // Agregar a la lista
    window.datosReflectivo.ubicaciones.push(ubicacion);
    
    // Limpiar input
    input.value = '';
    input.focus();
    
    // Actualizar lista visual
    actualizarListaUbicacionesReflectivo();
};

/**
 * Actualiza la lista visual de ubicaciones
 */
window.actualizarListaUbicacionesReflectivo = function() {
    const lista = document.getElementById('reflectivo-ubicaciones-lista');
    if (!lista) return;
    
    lista.innerHTML = '';
    window.datosReflectivo.ubicaciones.forEach((ubicacion, index) => {
        const item = document.createElement('div');
        item.style.cssText = 'display: flex; align-items: center; justify-content: space-between; background: #f3f4f6; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #0066cc;';
        
        const texto = document.createElement('span');
        texto.textContent = ubicacion;
        texto.style.cssText = 'font-size: 0.875rem; color: #1f2937;';
        item.appendChild(texto);
        
        const btnEliminar = document.createElement('button');
        btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1rem;">close</span>';
        btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 4px; padding: 0.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px;';
        btnEliminar.onclick = () => {
            window.datosReflectivo.ubicaciones.splice(index, 1);
            actualizarListaUbicacionesReflectivo();
        };
        item.appendChild(btnEliminar);
        
        lista.appendChild(item);
    });
};

/**
 * Abre el modal del editor de tallas
 */
window.abrirEditorTallasReflectivo = function() {
    const modal = document.createElement('div');
    modal.id = 'modal-editor-tallas-reflectivo';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10002;';
    
    const container = document.createElement('div');
    container.style.cssText = 'background: white; border-radius: 12px; max-width: 400px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);';
    
    // Header
    const header = document.createElement('div');
    header.style.cssText = 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; padding: 1.5rem; border-radius: 12px 12px 0 0; display: flex; align-items: center; justify-content: space-between;';
    header.innerHTML = '<h2 style="margin: 0; font-size: 1.25rem;">Seleccionar Tallas</h2>';
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded">close</span>';
    btnCerrar.style.cssText = 'background: transparent; color: white; border: none; cursor: pointer; padding: 0; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;';
    btnCerrar.onclick = () => modal.remove();
    header.appendChild(btnCerrar);
    container.appendChild(header);
    
    // Content
    const content = document.createElement('div');
    content.style.cssText = 'padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;';
    
    // Obtener tallas seleccionadas del modal principal
    const tallasSeleccionadas = window.tallasSeleccionadas || { dama: { tallas: [] }, caballero: { tallas: [] } };
    
    ['dama', 'caballero'].forEach(genero => {
        const tallas = tallasSeleccionadas[genero]?.tallas || [];
        if (tallas.length === 0) return;
        
        const div = document.createElement('div');
        div.innerHTML = `<h4 style="margin: 0 0 0.5rem 0; color: #1f2937; text-transform: capitalize; font-size: 0.95rem;">${genero.toUpperCase()}</h4>`;
        
        const tallasList = document.createElement('div');
        tallasList.style.cssText = 'display: flex; flex-wrap: wrap; gap: 0.5rem;';
        
        tallas.forEach(talla => {
            const label = document.createElement('label');
            label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; cursor: pointer;';
            label.innerHTML = `
                <input type="checkbox" class="reflectivo-talla-editor-${genero}" value="${talla}" style="width: 16px; height: 16px; cursor: pointer;">
                <span style="font-size: 0.875rem;">${talla}</span>
            `;
            tallasList.appendChild(label);
        });
        
        div.appendChild(tallasList);
        content.appendChild(div);
    });
    
    container.appendChild(content);
    
    // Footer
    const footer = document.createElement('div');
    footer.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid #e5e7eb;';
    
    const btnCancelar = document.createElement('button');
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; font-size: 0.875rem;';
    btnCancelar.onclick = () => modal.remove();
    footer.appendChild(btnCancelar);
    
    const btnGuardar = document.createElement('button');
    btnGuardar.textContent = 'Guardar';
    btnGuardar.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; font-size: 0.875rem;';
    btnGuardar.onclick = () => {
        window.datosReflectivo.tallasPorGenero.dama = Array.from(document.querySelectorAll('.reflectivo-talla-editor-dama:checked')).map(cb => cb.value);
        window.datosReflectivo.tallasPorGenero.caballero = Array.from(document.querySelectorAll('.reflectivo-talla-editor-caballero:checked')).map(cb => cb.value);
        
        // Actualizar tarjeta de tallas en el modal principal
        actualizarTarjetaTallasReflectivo();
        
        modal.remove();
    };
    footer.appendChild(btnGuardar);
    
    container.appendChild(footer);
    modal.appendChild(container);
    document.body.appendChild(modal);
};

/**
 * Actualiza la tarjeta de tallas seleccionadas en el modal
 */
window.actualizarTarjetaTallasReflectivo = function() {
    const container = document.getElementById('reflectivo-tallas-seleccionadas');
    if (!container) return;
    
    container.innerHTML = '';
    
    const todasLasTallas = [];
    
    if (window.datosReflectivo.tallasPorGenero.dama.length > 0) {
        window.datosReflectivo.tallasPorGenero.dama.forEach(talla => {
            todasLasTallas.push({ talla, genero: 'dama' });
        });
    }
    
    if (window.datosReflectivo.tallasPorGenero.caballero.length > 0) {
        window.datosReflectivo.tallasPorGenero.caballero.forEach(talla => {
            todasLasTallas.push({ talla, genero: 'caballero' });
        });
    }
    
    if (todasLasTallas.length === 0) {
        container.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas seleccionadas</p>';
        return;
    }
    
    const tabla = document.createElement('table');
    tabla.style.cssText = 'width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden;';
    
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr style="background: #f3f4f6;">
            <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; border-bottom: 1px solid #d1d5db;">Talla</th>
            <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; border-bottom: 1px solid #d1d5db;">Género</th>
            <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; border-bottom: 1px solid #d1d5db;">Cantidad</th>
            <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; border-bottom: 1px solid #d1d5db;">Acción</th>
        </tr>
    `;
    tabla.appendChild(thead);
    
    const tbody = document.createElement('tbody');
    todasLasTallas.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.style.cssText = 'border-bottom: 1px solid #d1d5db;';
        
        const cantidadKey = `reflectivo-cantidad-${item.genero}-${item.talla}`;
        const cantidadGuardada = sessionStorage.getItem(cantidadKey) || '1';
        
        tr.innerHTML = `
            <td style="padding: 0.75rem; font-size: 0.875rem;">${item.talla}</td>
            <td style="padding: 0.75rem; text-align: center; font-size: 0.875rem; text-transform: capitalize;">${item.genero}</td>
            <td style="padding: 0.75rem; text-align: center;">
                <input type="number" id="${cantidadKey}" value="${cantidadGuardada}" min="1" style="width: 60px; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; text-align: center; font-size: 0.875rem;" onchange="guardarCantidadReflectivo('${cantidadKey}')">
            </td>
            <td style="padding: 0.75rem; text-align: center;">
                <button type="button" onclick="eliminarTallaDelReflectivo('${item.talla}', '${item.genero}')" class="btn btn-sm" style="background: #ef4444; color: white; padding: 0.5rem 0.75rem; font-size: 0.75rem; border: none; cursor: pointer; border-radius: 4px;">
                    <span class="material-symbols-rounded" style="font-size: 0.9rem;">delete</span>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    tabla.appendChild(tbody);
    container.appendChild(tabla);
};

/**
 * Guarda la cantidad de reflectivo para una talla
 */
window.guardarCantidadReflectivo = function(cantidadKey) {
    const input = document.getElementById(cantidadKey);
    if (!input) return;
    
    const cantidadReflectivo = parseInt(input.value) || 0;
    
    // Extraer género y talla del key
    const partes = cantidadKey.split('-');
    const genero = partes[2];
    const talla = partes.slice(3).join('-');
    
    // Capturar cantidades de prenda si no están disponibles
    if (!window.cantidadesPrenda || Object.keys(window.cantidadesPrenda).length === 0) {
        capturarCantidadesPrenda();
    }
    
    // Obtener cantidad de prenda para esta talla
    const cantidadPrenda = window.cantidadesPrenda ? window.cantidadesPrenda[`${genero}-${talla}`] : null;
    
    if (cantidadPrenda && cantidadReflectivo > cantidadPrenda) {
        // Mostrar modal de error
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10003;';
        
        const box = document.createElement('div');
        box.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
        
        const titulo = document.createElement('h3');
        titulo.textContent = ' Cantidad Excedida';
        titulo.style.cssText = 'margin: 0 0 1rem 0; color: #ef4444; font-size: 1.1rem;';
        box.appendChild(titulo);
        
        const mensaje = document.createElement('p');
        mensaje.textContent = `La cantidad de reflectivo (${cantidadReflectivo}) no puede ser mayor que la cantidad de prendas (${cantidadPrenda}) para la talla ${talla} ${genero}.`;
        mensaje.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;';
        box.appendChild(mensaje);
        
        const btn = document.createElement('button');
        btn.textContent = 'Entendido';
        btn.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; width: 100%;';
        btn.onclick = () => {
            modal.remove();
            input.value = cantidadPrenda;
            sessionStorage.setItem(cantidadKey, cantidadPrenda);
        };
        box.appendChild(btn);
        
        modal.appendChild(box);
        document.body.appendChild(modal);
        
        input.value = cantidadPrenda;
        return;
    }
    
    sessionStorage.setItem(cantidadKey, input.value);
};

/**
 * Elimina una talla del reflectivo
 */
window.eliminarTallaDelReflectivo = function(talla, genero) {
    const index = window.datosReflectivo.tallasPorGenero[genero].indexOf(talla);
    if (index > -1) {
        window.datosReflectivo.tallasPorGenero[genero].splice(index, 1);
    }
    actualizarTarjetaTallasReflectivo();
};

/**
 * Guarda la configuración completa del reflectivo
 */
window.guardarConfiguracionReflectivo = function() {
    // Guardar observaciones
    const observacionesInput = document.getElementById('reflectivo-observaciones');
    if (observacionesInput) {
        window.datosReflectivo.observaciones = observacionesInput.value;
    }
    
    // Guardar si aplica a todas las tallas
    const aplicarTodas = document.getElementById('reflectivo-aplicar-todas');
    if (aplicarTodas) {
        window.datosReflectivo.aplicarATodas = aplicarTodas.checked;
    }
    

    
    // Mostrar sección de reflectivo en el modal principal
    mostrarResumenReflectivo();
    
    cerrarModalReflectivo();

};

/**
 * Muestra el resumen del reflectivo configurado
 */
window.mostrarResumenReflectivo = function() {
    const seccion = document.getElementById('seccion-reflectivo-resumen');
    const contenido = document.getElementById('reflectivo-resumen-contenido');
    
    if (!seccion || !contenido) return;
    
    // Construir resumen
    let html = '';
    
    // Imágenes con preview
    if (window.datosReflectivo.imagenes.length > 0) {
        html += `<div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem;">`;
        html += `<img src="${window.datosReflectivo.imagenes[0].previewUrl}" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover; border: 2px solid #0066cc;">`;
        if (window.datosReflectivo.imagenes.length > 1) {
            html += `<span style="background: #0066cc; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">+${window.datosReflectivo.imagenes.length - 1}</span>`;
        }
        html += `</div>`;
    }
    
    // Ubicaciones
    if (window.datosReflectivo.ubicaciones.length > 0) {
        html += `<p style="margin: 0.5rem 0;"><strong> Ubicaciones:</strong> ${window.datosReflectivo.ubicaciones.join(', ')}</p>`;
    }
    
    // Tallas
    if (!window.datosReflectivo.aplicarATodas) {
        const todasLasTallas = [];
        if (window.datosReflectivo.tallasPorGenero.dama.length > 0) {
            todasLasTallas.push(...window.datosReflectivo.tallasPorGenero.dama.map(t => `${t} (D)`));
        }
        if (window.datosReflectivo.tallasPorGenero.caballero.length > 0) {
            todasLasTallas.push(...window.datosReflectivo.tallasPorGenero.caballero.map(t => `${t} (C)`));
        }
        if (todasLasTallas.length > 0) {
            html += `<p style="margin: 0.5rem 0;"><strong> Tallas:</strong> ${todasLasTallas.join(', ')}</p>`;
        }
    } else {
        html += `<p style="margin: 0.5rem 0;"><strong> Tallas:</strong> Todas las tallas</p>`;
    }
    
    // Observaciones
    if (window.datosReflectivo.observaciones) {
        html += `<p style="margin: 0.5rem 0;"><strong> Observaciones:</strong> ${window.datosReflectivo.observaciones}</p>`;
    }
    
    if (html === '') {
        html = '<p style="color: #9ca3af;">Sin configuración</p>';
    }
    
    contenido.innerHTML = html;
    seccion.style.display = 'block';
};
