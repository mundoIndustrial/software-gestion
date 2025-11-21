/**
 * SISTEMA DE COTIZACIONES - CARGAR BORRADOR
 * Responsabilidad: Cargar datos de un borrador existente en el formulario
 */

function cargarBorrador(cotizacion) {
    if (!cotizacion) return;
    
    console.log('📂 Cargando borrador:', cotizacion);
    
    // Cargar cliente
    const inputCliente = document.getElementById('cliente');
    if (inputCliente && cotizacion.cliente) {
        inputCliente.value = cotizacion.cliente;
    }
    
    // Cargar productos
    if (cotizacion.productos && Array.isArray(cotizacion.productos)) {
        cotizacion.productos.forEach(producto => {
            agregarProductoFriendly();
            const ultimoProducto = document.querySelectorAll('.producto-card')[document.querySelectorAll('.producto-card').length - 1];
            
            if (ultimoProducto) {
                // Nombre del producto
                const inputNombre = ultimoProducto.querySelector('input[name*="nombre_producto"]');
                if (inputNombre) inputNombre.value = producto.nombre_producto || '';
                
                // Descripción
                const textareaDesc = ultimoProducto.querySelector('textarea[name*="descripcion"]');
                if (textareaDesc) textareaDesc.value = producto.descripcion || '';
                
                // Tallas
                if (producto.tallas && Array.isArray(producto.tallas)) {
                    producto.tallas.forEach(talla => {
                        const checkboxTalla = ultimoProducto.querySelector(`input[name*="tallas"][value="${talla}"]`);
                        if (checkboxTalla) checkboxTalla.checked = true;
                    });
                }
            }
        });
    }
    
    // Cargar técnicas
    if (cotizacion.tecnicas && Array.isArray(cotizacion.tecnicas)) {
        cotizacion.tecnicas.forEach(tecnica => {
            const contenedor = document.getElementById('tecnicas_seleccionadas');
            if (contenedor) {
                const tag = document.createElement('div');
                tag.style.cssText = 'background: #3498db; color: white; padding: 6px 12px; border-radius: 20px; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600;';
                tag.innerHTML = `
                    <input type="hidden" name="tecnicas[]" value="${tecnica}">
                    <span>${tecnica}</span>
                    <button type="button" onclick="this.closest('div').remove()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem; padding: 0; line-height: 1;">✕</button>
                `;
                contenedor.appendChild(tag);
            }
        });
    }
    
    // Cargar observaciones técnicas
    if (cotizacion.observaciones_tecnicas) {
        const textarea = document.getElementById('observaciones_tecnicas');
        if (textarea) textarea.value = cotizacion.observaciones_tecnicas;
    }
    
    // Cargar observaciones generales
    if (cotizacion.observaciones_generales && Array.isArray(cotizacion.observaciones_generales)) {
        cotizacion.observaciones_generales.forEach(obs => {
            if (typeof obs === 'string' && obs.trim()) {
                const contenedor = document.getElementById('observaciones_lista');
                if (contenedor) {
                    const fila = document.createElement('div');
                    fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
                    fila.innerHTML = `
                        <input type="text" name="observaciones_generales[]" class="input-large" value="${obs}" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                        <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
                            <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px;">
                                <input type="checkbox" name="observaciones_check[]" style="width: 20px; height: 20px; cursor: pointer;">
                            </div>
                            <div class="obs-text-mode" style="display: none; flex: 1;">
                                <input type="text" name="observaciones_valor[]" placeholder="Valor..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                            </div>
                            <button type="button" class="obs-toggle-btn" style="background: #3498db; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">✓/✎</button>
                        </div>
                        <button type="button" onclick="this.closest('div').remove()" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
                    `;
                    contenedor.appendChild(fila);
                    
                    const toggleBtn = fila.querySelector('.obs-toggle-btn');
                    const checkboxMode = fila.querySelector('.obs-checkbox-mode');
                    const textMode = fila.querySelector('.obs-text-mode');
                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (checkboxMode.style.display === 'none') {
                            checkboxMode.style.display = 'block';
                            textMode.style.display = 'none';
                            toggleBtn.style.background = '#3498db';
                        } else {
                            checkboxMode.style.display = 'none';
                            textMode.style.display = 'block';
                            toggleBtn.style.background = '#ff9800';
                        }
                    });
                }
            }
        });
    }
    
    // Cargar ubicaciones/secciones
    if (cotizacion.ubicaciones && Array.isArray(cotizacion.ubicaciones)) {
        const secciones = {};
        cotizacion.ubicaciones.forEach(ubicacion => {
            // Agrupar por sección
            // Aquí se puede implementar lógica para cargar secciones si es necesario
        });
    }
    
    console.log('✅ Borrador cargado correctamente');
    actualizarResumenFriendly();
}
