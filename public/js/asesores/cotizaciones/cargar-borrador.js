/**
 * SISTEMA DE COTIZACIONES - CARGAR BORRADOR
 * Responsabilidad: Cargar datos de un borrador existente en el formulario
 */

function cargarBorrador(cotizacion) {
    if (!cotizacion) return;
    
    console.log('📂 Cargando borrador:', cotizacion);
    
    // Cargar productos
    if (cotizacion.productos && Array.isArray(cotizacion.productos)) {
        console.log('📦 Cargando', cotizacion.productos.length, 'productos');
        
        cotizacion.productos.forEach((producto, index) => {
            console.log(`📦 Producto ${index}:`, producto);
            
            // Agregar un nuevo producto
            agregarProductoFriendly();
            
            // Esperar más tiempo y con reintentos
            const intentarCargar = (intento = 0) => {
                const productosCards = document.querySelectorAll('.producto-card');
                const ultimoProducto = productosCards[productosCards.length - 1];
                
                console.log(`⏳ Intento ${intento}: ${productosCards.length} productos encontrados`);
                
                if (ultimoProducto) {
                    // Nombre del producto
                    const inputNombre = ultimoProducto.querySelector('input[name*="nombre_producto"]');
                    if (inputNombre) {
                        inputNombre.value = producto.nombre_producto || '';
                        inputNombre.dispatchEvent(new Event('input', { bubbles: true }));
                        console.log('✅ Nombre cargado:', producto.nombre_producto);
                    } else if (intento < 5) {
                        console.log('⏳ Input nombre no encontrado, reintentando...');
                        setTimeout(() => intentarCargar(intento + 1), 200);
                        return;
                    }
                    
                    // Descripción
                    const textareaDesc = ultimoProducto.querySelector('textarea[name*="descripcion"]');
                    if (textareaDesc) {
                        textareaDesc.value = producto.descripcion || '';
                        textareaDesc.dispatchEvent(new Event('input', { bubbles: true }));
                        console.log('✅ Descripción cargada');
                    }
                    
                    // Tallas - buscar en los botones de talla
                    if (producto.tallas && Array.isArray(producto.tallas)) {
                        console.log('📏 Cargando tallas:', producto.tallas);
                        
                        producto.tallas.forEach(talla => {
                            // Buscar el botón de talla
                            const tallaBtn = ultimoProducto.querySelector(`.talla-btn[data-talla="${talla}"]`);
                            if (tallaBtn) {
                                tallaBtn.click();
                                console.log('✅ Talla activada:', talla);
                            }
                        });
                    }
                } else if (intento < 5) {
                    console.log('⏳ Producto card no encontrado, reintentando...');
                    setTimeout(() => intentarCargar(intento + 1), 200);
                }
            };
            
            setTimeout(() => intentarCargar(), 500);
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
            const contenedor = document.getElementById('observaciones_lista');
            if (!contenedor) return;
            
            // Manejar ambos formatos: string antiguo y objeto nuevo
            let texto = '';
            let tipo = 'texto';
            let valor = '';
            
            if (typeof obs === 'string') {
                // Formato antiguo: solo string
                texto = obs;
            } else if (typeof obs === 'object' && obs.texto) {
                // Formato nuevo: objeto con {texto, tipo, valor}
                texto = obs.texto || '';
                tipo = obs.tipo || 'texto';
                valor = obs.valor || '';
            }
            
            if (!texto.trim()) return;
            
            const fila = document.createElement('div');
            fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
            fila.innerHTML = `
                <input type="text" name="observaciones_generales[]" class="input-large" value="${texto}" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
                    <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px; ${tipo === 'checkbox' ? '' : 'display: none;'}">
                        <input type="checkbox" name="observaciones_check[]" style="width: 20px; height: 20px; cursor: pointer;" ${tipo === 'checkbox' ? 'checked' : ''}>
                    </div>
                    <div class="obs-text-mode" style="display: ${tipo === 'texto' ? 'block' : 'none'}; flex: 1;">
                        <input type="text" name="observaciones_valor[]" placeholder="Valor..." value="${valor}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                    </div>
                    <button type="button" class="obs-toggle-btn" style="background: ${tipo === 'checkbox' ? '#3498db' : '#ff9800'}; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">✓/✎</button>
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
                    checkboxMode.style.display = 'flex';
                    textMode.style.display = 'none';
                    toggleBtn.style.background = '#3498db';
                } else {
                    checkboxMode.style.display = 'none';
                    textMode.style.display = 'block';
                    toggleBtn.style.background = '#ff9800';
                }
            });
        });
    }
    
    // Cargar ubicaciones/secciones
    if (cotizacion.ubicaciones && Array.isArray(cotizacion.ubicaciones)) {
        cotizacion.ubicaciones.forEach(ubicacion => {
            if (ubicacion.seccion) {
                // Aquí se puede implementar lógica para cargar secciones
                console.log('📍 Ubicación encontrada:', ubicacion.seccion);
            }
        });
    }
    
    // Cargar imágenes guardadas en window.imagenesEnMemoria
    if (cotizacion.imagenes && Array.isArray(cotizacion.imagenes)) {
        console.log('📸 Cargando imágenes guardadas:', cotizacion.imagenes);
        // Las imágenes se mostrarán en el preview cuando se cargue la página
        // (se manejan en el backend con las rutas de storage)
    }
    
    console.log('✅ Borrador cargado correctamente');
    actualizarResumenFriendly();
}
