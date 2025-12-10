/**
 * SISTEMA DE COTIZACIONES - CARGAR BORRADOR
 * Responsabilidad: Cargar datos de un borrador existente en el formulario
 */

function cargarBorrador(cotizacion) {
    if (!cotizacion) return;
    
    console.log('📂 Cargando borrador:', cotizacion);
    
    // Guardar ID de cotización en variable global para usarlo en funciones de foto
    window.cotizacionIdActual = cotizacion.id;
    
    // Cargar cliente
    if (cotizacion.cliente) {
        const clienteInput = document.getElementById('cliente');
        if (clienteInput) {
            clienteInput.value = cotizacion.cliente;
            clienteInput.dispatchEvent(new Event('input', { bubbles: true }));
            clienteInput.dispatchEvent(new Event('change', { bubbles: true }));
            console.log('✅ Cliente cargado:', cotizacion.cliente);
        }
    }
    
    // Cargar tipo de cotización (tipo_venta en JSON, tipo_cotizacion en el formulario)
    if (cotizacion.tipo_venta) {
        const tipoCotizacionSelect = document.getElementById('tipo_cotizacion');
        if (tipoCotizacionSelect) {
            tipoCotizacionSelect.value = cotizacion.tipo_venta;
            tipoCotizacionSelect.dispatchEvent(new Event('change', { bubbles: true }));
            console.log('✅ Tipo de cotización cargado:', cotizacion.tipo_venta);
        }
    }
    
    // Cargar productos
    if (cotizacion.productos && Array.isArray(cotizacion.productos)) {
        console.log('📦 Cargando', cotizacion.productos.length, 'productos');
        
        cotizacion.productos.forEach((producto, index) => {
            console.log(`📦 Producto ${index}:`, producto);
            
            // Agregar un nuevo producto solo si no es el primero (el primero ya existe)
            if (index > 0) {
                agregarProductoFriendly();
            }
            
            // Esperar más tiempo y con reintentos
            const intentarCargar = (intento = 0) => {
                const productosCards = document.querySelectorAll('.producto-card');
                const ultimoProducto = productosCards[productosCards.length - 1];
                
                console.log(`⏳ Intento ${intento}: ${productosCards.length} productos encontrados`);
                
                if (ultimoProducto) {
                    // Nombre del producto
                    const inputNombre = ultimoProducto.querySelector('input[name*="nombre_producto"]');
                    console.log('🔍 Buscando input nombre:', {
                        encontrado: !!inputNombre,
                        selector: 'input[name*="nombre_producto"]',
                        html: inputNombre?.outerHTML
                    });
                    
                    if (inputNombre) {
                        inputNombre.value = producto.nombre_producto || '';
                        inputNombre.dispatchEvent(new Event('input', { bubbles: true }));
                        inputNombre.dispatchEvent(new Event('change', { bubbles: true }));
                        console.log('✅ Nombre cargado:', producto.nombre_producto, 'Valor actual:', inputNombre.value);
                    } else if (intento < 5) {
                        console.log('⏳ Input nombre no encontrado, reintentando...');
                        setTimeout(() => intentarCargar(intento + 1), 200);
                        return;
                    }
                    
                    // Descripción
                    const textareaDesc = ultimoProducto.querySelector('textarea[name*="descripcion"]');
                    console.log('🔍 Buscando textarea descripción:', {
                        encontrado: !!textareaDesc,
                        selector: 'textarea[name*="descripcion"]',
                        html: textareaDesc?.outerHTML
                    });
                    
                    if (textareaDesc) {
                        textareaDesc.value = producto.descripcion || '';
                        textareaDesc.dispatchEvent(new Event('input', { bubbles: true }));
                        textareaDesc.dispatchEvent(new Event('change', { bubbles: true }));
                        console.log('✅ Descripción cargada:', producto.descripcion, 'Valor actual:', textareaDesc.value);
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
                            } else {
                                console.log('⚠️ Botón de talla no encontrado:', talla);
                            }
                        });
                    }
                    
                    // Cargar variantes (color, tela, referencia, etc.)
                    if (producto.variantes && Array.isArray(producto.variantes) && producto.variantes.length > 0) {
                        console.log('🎨 Cargando variantes:', producto.variantes);
                        
                        // Cargar telas desde la relación variantes[0].telas
                        const primeraVariante = producto.variantes[0];
                        let telasMultiples = null;
                        
                        if (primeraVariante && primeraVariante.telas && Array.isArray(primeraVariante.telas)) {
                            telasMultiples = primeraVariante.telas;
                        }
                        
                        // Cargar telas si existen
                        if (telasMultiples && Array.isArray(telasMultiples) && telasMultiples.length > 0) {
                            console.log('🧵 Cargando múltiples telas desde relación:', telasMultiples);
                            
                            telasMultiples.forEach((telaData, telaIdx) => {
                                // Agregar fila de tela si no es la primera
                                if (telaIdx > 0) {
                                    const btnAgregarTela = ultimoProducto.querySelector('button[onclick*="agregarFilaTela"]');
                                    if (btnAgregarTela) {
                                        btnAgregarTela.click();
                                        console.log('✅ Fila de tela agregada:', telaIdx);
                                    }
                                }
                                
                                // Esperar a que se cree la fila y cargar datos
                                setTimeout(() => {
                                    const filasTela = ultimoProducto.querySelectorAll('tr[data-tela-index]');
                                    const filaActual = filasTela[telaIdx];
                                    
                                    if (filaActual) {
                                        // Color (desde relación color)
                                        const colorInput = filaActual.querySelector('input[name*="color"]');
                                        if (colorInput && telaData.color && telaData.color.nombre) {
                                            colorInput.value = telaData.color.nombre;
                                            colorInput.dispatchEvent(new Event('input', { bubbles: true }));
                                            console.log('✅ Color cargado:', telaData.color.nombre);
                                        }
                                        
                                        // Tela (desde relación tela)
                                        const telaSelect = filaActual.querySelector('select[name*="tela"]');
                                        if (telaSelect && telaData.tela && telaData.tela.nombre) {
                                            telaSelect.value = telaData.tela.nombre;
                                            telaSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                            console.log('✅ Tela cargada:', telaData.tela.nombre);
                                        }
                                        
                                        // Referencia (desde relación tela.referencia)
                                        const refInput = filaActual.querySelector('input[name*="referencia"]');
                                        if (refInput && telaData.tela && telaData.tela.referencia) {
                                            refInput.value = telaData.tela.referencia;
                                            refInput.dispatchEvent(new Event('input', { bubbles: true }));
                                            console.log('✅ Referencia cargada:', telaData.tela.referencia);
                                        }
                                    }
                                }, 300 + (telaIdx * 100));
                            });
                        } else {
                            // Cargar variante única (compatibilidad con formato antiguo)
                            // Color
                            if (producto.variantes.color) {
                                const colorInput = ultimoProducto.querySelector('input[name*="color"]');
                                if (colorInput) {
                                    colorInput.value = producto.variantes.color;
                                    colorInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    console.log('✅ Color cargado:', producto.variantes.color);
                                }
                            }
                            
                            // Tela
                            if (producto.variantes.tela) {
                                const telaSelect = ultimoProducto.querySelector('select[name*="tela"]');
                                if (telaSelect) {
                                    telaSelect.value = producto.variantes.tela;
                                    telaSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                    console.log('✅ Tela cargada:', producto.variantes.tela);
                                }
                            }
                            
                            // Referencia
                            if (producto.variantes.referencia) {
                                const refInput = ultimoProducto.querySelector('input[name*="referencia"]');
                                if (refInput) {
                                    refInput.value = producto.variantes.referencia;
                                    refInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    console.log('✅ Referencia cargada:', producto.variantes.referencia);
                                }
                            }
                        }
                        
                        // Manga
                        if (producto.variantes.tipo_manga_id) {
                            const mangaSelect = ultimoProducto.querySelector('select[name*="tipo_manga"]');
                            if (mangaSelect) {
                                mangaSelect.value = producto.variantes.tipo_manga_id;
                                mangaSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('✅ Manga cargada:', producto.variantes.tipo_manga_id);
                            }
                        }
                        
                        // Bolsillos
                        if (producto.variantes.tiene_bolsillos !== undefined) {
                            const bolsillosCheckbox = ultimoProducto.querySelector('input[name*="tiene_bolsillos"]');
                            if (bolsillosCheckbox) {
                                bolsillosCheckbox.checked = !!producto.variantes.tiene_bolsillos;
                                bolsillosCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('✅ Bolsillos cargado:', producto.variantes.tiene_bolsillos);
                            }
                        }
                        
                        // Broche
                        if (producto.variantes.tipo_broche_id) {
                            const brocheSelect = ultimoProducto.querySelector('select[name*="tipo_broche"]');
                            if (brocheSelect) {
                                brocheSelect.value = producto.variantes.tipo_broche_id;
                                brocheSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('✅ Broche cargado:', producto.variantes.tipo_broche_id);
                            }
                        }
                        
                        // Reflectivo
                        if (producto.variantes.tiene_reflectivo !== undefined) {
                            const reflectivoCheckbox = ultimoProducto.querySelector('input[name*="tiene_reflectivo"]');
                            if (reflectivoCheckbox) {
                                reflectivoCheckbox.checked = !!producto.variantes.tiene_reflectivo;
                                reflectivoCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('✅ Reflectivo cargado:', producto.variantes.tiene_reflectivo);
                            }
                        }
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
    
    // Cargar imágenes guardadas
    if (cotizacion.prendasCotizaciones && Array.isArray(cotizacion.prendasCotizaciones)) {
        console.log('📸 Cargando imágenes de prendas:', cotizacion.prendasCotizaciones.length);
        
        cotizacion.prendasCotizaciones.forEach((prenda, prendaIdx) => {
            // Esperar a que se cree la tarjeta de producto
            setTimeout(() => {
                const productosCards = document.querySelectorAll('.producto-card');
                if (productosCards[prendaIdx]) {
                    const card = productosCards[prendaIdx];
                    const fotosPreview = card.querySelector('.fotos-preview');
                    
                    // Cargar fotos de prenda (son arrays JSON en el modelo)
                    if (fotosPreview && prenda.fotos && Array.isArray(prenda.fotos)) {
                        prenda.fotos.forEach(fotoData => {
                            const img = document.createElement('img');
                            // Las fotos pueden tener estructura {nombre: '...', ruta: '...'} o ser strings
                            const rutaFoto = (typeof fotoData === 'string') ? fotoData : (fotoData.ruta || fotoData.nombre || '');
                            img.src = rutaFoto.startsWith('http') ? rutaFoto : `/storage/${rutaFoto}`;
                            img.style.cssText = 'width: 100%; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;';
                            img.alt = 'Foto de prenda';
                            img.title = 'Haz clic para eliminar';
                            img.dataset.ruta = rutaFoto.startsWith('http') ? rutaFoto : `/storage/${rutaFoto}`;
                            img.onclick = function() {
                                eliminarFotoCotizacion(this, window.cotizacionIdActual);
                            };
                            fotosPreview.appendChild(img);
                            console.log('✅ Foto de prenda cargada:', rutaFoto);
                        });
                    }
                    
                    // Cargar fotos de telas
                    const fotoTelaPreview = card.querySelector('.foto-tela-preview');
                    if (fotoTelaPreview && prenda.telas && Array.isArray(prenda.telas)) {
                        prenda.telas.forEach(tela => {
                            // Las telas también pueden ser arrays de fotos
                            const fotosDelaTela = (typeof tela === 'object' && tela.fotos) ? tela.fotos : (Array.isArray(tela) ? tela : []);
                            
                            fotosDelaTela.forEach(fotoData => {
                                const img = document.createElement('img');
                                const rutaFoto = (typeof fotoData === 'string') ? fotoData : (fotoData.ruta || fotoData.nombre || '');
                                img.src = rutaFoto.startsWith('http') ? rutaFoto : `/storage/${rutaFoto}`;
                                img.style.cssText = 'width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;';
                                img.alt = 'Foto de tela';
                                img.title = 'Haz clic para eliminar';
                                img.dataset.ruta = rutaFoto.startsWith('http') ? rutaFoto : `/storage/${rutaFoto}`;
                                img.onclick = function() {
                                    eliminarFotoCotizacion(this, window.cotizacionIdActual);
                                };
                                fotoTelaPreview.appendChild(img);
                                console.log('✅ Foto de tela cargada:', rutaFoto);
                            });
                        });
                    }
                }
            }, 1000 + (prendaIdx * 200));
        });
    }
    
    // Cargar imágenes generales (del logo cotización)
    if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.fotos) {
        console.log('📸 Cargando imágenes generales:', cotizacion.logo_cotizacion.fotos);
        
        const galeriaImagenes = document.getElementById('galeria_imagenes');
        if (galeriaImagenes) {
            const fotos = cotizacion.logo_cotizacion.fotos;
            (Array.isArray(fotos) ? fotos : [fotos]).forEach(fotoData => {
                const rutaFoto = (typeof fotoData === 'string') ? fotoData : (fotoData.ruta || fotoData.nombre || '');
                if (!rutaFoto) return;
                
                const div = document.createElement('div');
                div.style.cssText = 'position: relative; width: 100px; height: 100px; background: #f0f0f0; border-radius: 4px; overflow: hidden;';
                div.innerHTML = `
                    <img src="${rutaFoto.startsWith('http') ? rutaFoto : `/storage/${rutaFoto}`}" 
                         style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" 
                         alt="Imagen general"
                         title="Haz clic para eliminar"
                         data-ruta="${rutaFoto.startsWith('http') ? rutaFoto : `/storage/${rutaFoto}`}">
                    <button type="button" 
                            style="position: absolute; top: 0; right: 0; background: rgba(0,0,0,0.7); color: white; border: none; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold;">✕</button>
                `;
                
                // Agregar evento para eliminar
                const img = div.querySelector('img');
                img.onclick = function() {
                    eliminarFotoCotizacion(this, window.cotizacionIdActual);
                };
                
                galeriaImagenes.appendChild(div);
                console.log('✅ Imagen general cargada:', rutaFoto);
            });
        }
    }
    
    console.log('✅ Borrador cargado correctamente');
    actualizarResumenFriendly();
}

/**
 * Eliminar foto de cotización (tanto del DOM como del servidor)
 */
async function eliminarFotoCotizacion(element, cotizacionId) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta foto?')) {
        return;
    }
    
    const ruta = element.dataset.ruta;
    if (!ruta) {
        console.error('No se pudo obtener la ruta de la imagen');
        return;
    }
    
    try {
        // Llamar al servidor para eliminar la imagen
        const response = await fetch(`/asesores/cotizaciones/${cotizacionId}/imagenes`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                ruta: ruta
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Eliminar del DOM
            const container = element.closest('div') || element.parentElement;
            if (container) {
                container.remove();
            } else {
                element.remove();
            }
            console.log('✅ Foto eliminada correctamente:', ruta);
            window.showToast('Foto eliminada correctamente', 'success');
        } else {
            console.error('Error al eliminar foto:', data.message);
            window.showToast('Error al eliminar la foto: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error en eliminarFotoCotizacion:', error);
        window.showToast('Error al eliminar la foto', 'error');
    }
}

