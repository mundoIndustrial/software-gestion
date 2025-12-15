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
    
    // Cargar tipo de cotización (tipo_venta en JSON, tipo_venta en el formulario)
    if (cotizacion.tipo_venta) {
        const tipoVentaSelect = document.getElementById('tipo_venta');
        if (tipoVentaSelect) {
            tipoVentaSelect.value = cotizacion.tipo_venta;
            tipoVentaSelect.dispatchEvent(new Event('change', { bubbles: true }));
            console.log('✅ Tipo de venta cargado:', cotizacion.tipo_venta);
        }
    }
    
    // Cargar especificaciones (del modal)
    if (cotizacion.especificaciones && typeof cotizacion.especificaciones === 'object') {
        console.log('📋 Cargando especificaciones:', cotizacion.especificaciones);
        
        // Guardar especificaciones en variable global para acceso en el modal
        window.especificacionesActuales = cotizacion.especificaciones;
        
        // Las especificaciones vienen como arrays de objetos con estructura: {valor: "...", observacion: "..."}
        // Esperar a que el modal esté disponible
        setTimeout(() => {
            Object.keys(cotizacion.especificaciones).forEach(key => {
                const valor = cotizacion.especificaciones[key];
                
                // Si es un array, tomar el primer elemento
                let valorFinal = '';
                let esCheckbox = false;
                
                if (Array.isArray(valor) && valor.length > 0) {
                    const primerElemento = valor[0];
                    
                    // Si el primer elemento es un objeto con propiedades valor y observacion
                    if (typeof primerElemento === 'object' && primerElemento !== null) {
                        // Extraer el valor
                        valorFinal = primerElemento.valor || '';
                        
                        // Detectar si es checkbox (valor es ✓ o similar)
                        esCheckbox = valorFinal === '✓' || valorFinal === 'on' || valorFinal === true;
                    } else {
                        valorFinal = primerElemento;
                    }
                }
                
                console.log(`🔍 DEBUG Especificación ${key}:`, {
                    valor: valorFinal,
                    esCheckbox: esCheckbox,
                    tipo: typeof valorFinal
                });
                
                // Buscar el input correspondiente en el modal
                const input = document.querySelector(`input[name="tabla_orden[${key}_obs]"]`);
                const checkbox = document.querySelector(`input[name="tabla_orden[${key}_check]"]`);
                
                if (input && valorFinal && typeof valorFinal === 'string') {
                    input.value = valorFinal;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    console.log(`✅ Especificación cargada: ${key} = ${valorFinal}`);
                }
                
                if (checkbox && esCheckbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    console.log(`✅ Checkbox especificación marcado: ${key}`);
                }
            });
        }, 500);
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
                        
                        // Extraer valores de talla (pueden ser strings o objetos)
                        const tallasValores = producto.tallas.map(t => {
                            if (typeof t === 'string') {
                                return t;
                            } else if (typeof t === 'object' && t.talla) {
                                return t.talla;
                            }
                            return null;
                        }).filter(t => t !== null);
                        
                        console.log('📏 Tallas extraídas:', tallasValores);
                        
                        if (tallasValores.length > 0) {
                            // Detectar tipo de talla (letra o número)
                            const tallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
                            const esLetra = tallasValores.some(t => tallasLetras.includes(t));
                            const tipoTalla = esLetra ? 'letra' : 'numero';
                            
                            console.log('📏 Tipo de talla detectado:', tipoTalla);
                            console.log('📏 Tallas a cargar:', tallasValores);
                            
                            // Seleccionar tipo de talla
                            const tipoSelect = ultimoProducto.querySelector('.talla-tipo-select');
                            if (tipoSelect) {
                                tipoSelect.value = tipoTalla;
                                tipoSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                tipoSelect.dispatchEvent(new Event('input', { bubbles: true }));
                                console.log('✅ Tipo de talla seleccionado:', tipoTalla);
                                console.log('📏 Valor actual del select:', tipoSelect.value);
                            }
                            
                            // Esperar a que se carguen los botones (aumentar delay)
                            setTimeout(() => {
                                console.log('⏳ Esperando botones de talla...');
                                
                                // Verificar que los botones existan
                                const botonesExistentes = ultimoProducto.querySelectorAll('.talla-btn');
                                console.log('📏 Botones encontrados:', botonesExistentes.length);
                                
                                // Si es número, detectar género
                                if (!esLetra) {
                                    const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
                                    const esGenero = tallasValores.some(t => tallasDama.includes(t));
                                    const genero = esGenero ? 'dama' : 'caballero';
                                    
                                    const generoSelect = ultimoProducto.querySelector('.talla-genero-select');
                                    if (generoSelect) {
                                        generoSelect.value = genero;
                                        generoSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                        console.log('✅ Género seleccionado:', genero);
                                    }
                                }
                                
                                // Esperar a que se carguen los botones del género
                                setTimeout(() => {
                                    console.log('⏳ Haciendo clic en botones de talla...');
                                    
                                    // Hacer clic en los botones de talla
                                    let tallasActivadas = 0;
                                    tallasValores.forEach(tallaValor => {
                                        const tallaBtn = ultimoProducto.querySelector(`.talla-btn[data-talla="${tallaValor}"]`);
                                        if (tallaBtn) {
                                            tallaBtn.click();
                                            tallasActivadas++;
                                            console.log('✅ Talla activada:', tallaValor);
                                        } else {
                                            console.log('⚠️ Botón de talla no encontrado:', tallaValor);
                                            // Debug: mostrar botones disponibles
                                            const botonesDisponibles = ultimoProducto.querySelectorAll('.talla-btn');
                                            console.log('📏 Botones disponibles:', Array.from(botonesDisponibles).map(b => b.dataset.talla));
                                        }
                                    });
                                    
                                    console.log(`📏 Total de tallas activadas: ${tallasActivadas}/${tallasValores.length}`);
                                    
                                    // Hacer clic en "Agregar Tallas"
                                    setTimeout(() => {
                                        const btnAgregarTallas = ultimoProducto.querySelector('button[onclick*="agregarTallasSeleccionadas"]');
                                        if (btnAgregarTallas) {
                                            btnAgregarTallas.click();
                                            console.log('✅ Botón "Agregar Tallas" clickeado');
                                        }
                                    }, 300);
                                }, 500);
                            }, 500);
                        }
                    }
                    
                    // Cargar variantes (color, tela, referencia, manga, bolsillos, broche, reflectivo)
                    if (producto.variantes && typeof producto.variantes === 'object') {
                        console.log('🎨 Cargando variantes:', producto.variantes);
                        
                        const variantes = producto.variantes;
                        
                        // Cargar género si existe (para tallas numéricas)
                        if (variantes.genero_id) {
                            console.log('👤 Género ID encontrado:', variantes.genero_id);
                            // El género se cargará cuando se seleccione el tipo de talla número
                            window.generoIdGuardado = variantes.genero_id;
                        }
                        
                        // Color
                        if (variantes.color) {
                            const colorInput = ultimoProducto.querySelector('.color-input');
                            if (colorInput) {
                                colorInput.value = variantes.color;
                                colorInput.dispatchEvent(new Event('input', { bubbles: true }));
                                console.log('✅ Color cargado:', variantes.color);
                            }
                        }
                        
                        // Tela
                        if (variantes.tela) {
                            const telaSelect = ultimoProducto.querySelector('.tela-input');
                            if (telaSelect) {
                                telaSelect.value = variantes.tela;
                                telaSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('✅ Tela cargada:', variantes.tela);
                            }
                        }
                        
                        // Referencia
                        if (variantes.referencia) {
                            const refInput = ultimoProducto.querySelector('.referencia-input');
                            if (refInput) {
                                refInput.value = variantes.referencia;
                                refInput.dispatchEvent(new Event('input', { bubbles: true }));
                                console.log('✅ Referencia cargada:', variantes.referencia);
                            }
                        }
                        
                        // Manga - Checkbox y Select
                        if (variantes.tipo_manga_id) {
                            const mangaCheckbox = ultimoProducto.querySelector('input[name*="aplica_manga"]');
                            if (mangaCheckbox) {
                                mangaCheckbox.checked = true;
                                mangaCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('✅ Checkbox Manga activado');
                            }
                            
                            setTimeout(() => {
                                const mangaIdInput = ultimoProducto.querySelector('.manga-id-input');
                                const mangaInput = ultimoProducto.querySelector('.manga-input');
                                
                                if (mangaIdInput) {
                                    mangaIdInput.value = variantes.tipo_manga_id;
                                    mangaIdInput.dispatchEvent(new Event('change', { bubbles: true }));
                                    console.log('✅ Manga ID cargado:', variantes.tipo_manga_id);
                                }
                                
                                if (mangaInput && variantes.tipo_manga) {
                                    mangaInput.value = variantes.tipo_manga;
                                    mangaInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    console.log('✅ Manga nombre cargado:', variantes.tipo_manga);
                                }
                            }, 300);
                        }
                        
                        // Observación de Manga
                        if (variantes.obs_manga) {
                            const mangaObs = ultimoProducto.querySelector('input[name*="obs_manga"]');
                            if (mangaObs) {
                                mangaObs.value = variantes.obs_manga;
                                mangaObs.dispatchEvent(new Event('input', { bubbles: true }));
                                console.log('✅ Obs Manga cargada:', variantes.obs_manga);
                            }
                        }
                        
                        // Bolsillos - Checkbox
                        if (variantes.tiene_bolsillos) {
                            const bolsillosCheckbox = ultimoProducto.querySelector('input[name*="aplica_bolsillos"]');
                            if (bolsillosCheckbox) {
                                bolsillosCheckbox.checked = true;
                                bolsillosCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('✅ Checkbox Bolsillos activado');
                            }
                        }
                        
                        // Observación de Bolsillos
                        if (variantes.obs_bolsillos) {
                            const bolsillosObs = ultimoProducto.querySelector('input[name*="obs_bolsillos"]');
                            if (bolsillosObs) {
                                bolsillosObs.value = variantes.obs_bolsillos;
                                bolsillosObs.dispatchEvent(new Event('input', { bubbles: true }));
                                console.log('✅ Obs Bolsillos cargada:', variantes.obs_bolsillos);
                            }
                        }
                        
                        // Broche - Checkbox y Select
                        if (variantes.tipo_broche_id) {
                            const brocheCheckbox = ultimoProducto.querySelector('input[name*="aplica_broche"]');
                            if (brocheCheckbox) {
                                brocheCheckbox.checked = true;
                                brocheCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('✅ Checkbox Broche activado');
                            }
                            
                            setTimeout(() => {
                                const brocheSelect = ultimoProducto.querySelector('select[name*="tipo_broche_id"]');
                                if (brocheSelect) {
                                    brocheSelect.value = variantes.tipo_broche_id;
                                    brocheSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                    console.log('✅ Broche cargado:', variantes.tipo_broche_id);
                                }
                            }, 200);
                        }
                        
                        // Observación de Broche
                        if (variantes.obs_broche) {
                            const brocheObs = ultimoProducto.querySelector('input[name*="obs_broche"]');
                            if (brocheObs) {
                                brocheObs.value = variantes.obs_broche;
                                brocheObs.dispatchEvent(new Event('input', { bubbles: true }));
                                console.log('✅ Obs Broche cargada:', variantes.obs_broche);
                            }
                        }
                        
                        // Reflectivo - Checkbox
                        if (variantes.tiene_reflectivo) {
                            const reflectivoCheckbox = ultimoProducto.querySelector('input[name*="aplica_reflectivo"]');
                            if (reflectivoCheckbox) {
                                reflectivoCheckbox.checked = true;
                                reflectivoCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('✅ Checkbox Reflectivo activado');
                            }
                        }
                        
                        // Observación de Reflectivo
                        if (variantes.obs_reflectivo) {
                            const reflectivoObs = ultimoProducto.querySelector('input[name*="obs_reflectivo"]');
                            if (reflectivoObs) {
                                reflectivoObs.value = variantes.obs_reflectivo;
                                reflectivoObs.dispatchEvent(new Event('input', { bubbles: true }));
                                console.log('✅ Obs Reflectivo cargada:', variantes.obs_reflectivo);
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
    
    // Cargar imágenes guardadas desde productos/prendas
    if (cotizacion.productos && Array.isArray(cotizacion.productos)) {
        console.log('📸 Cargando imágenes de prendas:', cotizacion.productos.length);
        
        cotizacion.productos.forEach((prenda, prendaIdx) => {
            console.log(`📸 Procesando imágenes de prenda ${prendaIdx}:`, prenda);
            
            // Cargar fotos de prenda en window.imagenesEnMemoria
            if (prenda.fotos && Array.isArray(prenda.fotos)) {
                console.log(`📸 Fotos encontradas para prenda ${prendaIdx}:`, prenda.fotos.length);
                prenda.fotos.forEach((foto, fotoIdx) => {
                    // Las fotos guardadas son objetos con ruta_original, ruta_webp, etc.
                    // Agregar a window.imagenesEnMemoria como referencias (no File objects)
                    if (foto.ruta_original || foto.ruta_webp) {
                        const rutaFoto = foto.ruta_original || foto.ruta_webp;
                        console.log(`📸 Foto ${fotoIdx}:`, rutaFoto);
                        
                        // Agregar a window.imagenesEnMemoria.prendaConIndice
                        if (!window.imagenesEnMemoria.prendaConIndice) {
                            window.imagenesEnMemoria.prendaConIndice = [];
                        }
                        
                        window.imagenesEnMemoria.prendaConIndice.push({
                            file: rutaFoto, // Guardar la ruta como string, no como File object
                            prendaIndex: prendaIdx,
                            esGuardada: true // Marcar como imagen guardada
                        });
                        
                        console.log(`✅ Foto agregada a imagenesEnMemoria [${prendaIdx}][${fotoIdx}]`);
                    }
                });
            }
            
            // Cargar telas en window.imagenesEnMemoria
            if (prenda.tela_fotos && Array.isArray(prenda.tela_fotos)) {
                console.log(`🧵 Telas encontradas para prenda ${prendaIdx}:`, prenda.tela_fotos.length);
                prenda.tela_fotos.forEach((tela, telaIdx) => {
                    if (tela.ruta_original || tela.ruta_webp) {
                        const rutaTela = tela.ruta_original || tela.ruta_webp;
                        console.log(`🧵 Tela ${telaIdx}:`, rutaTela);
                        
                        // Agregar a window.imagenesEnMemoria.telaConIndice
                        if (!window.imagenesEnMemoria.telaConIndice) {
                            window.imagenesEnMemoria.telaConIndice = [];
                        }
                        
                        window.imagenesEnMemoria.telaConIndice.push({
                            file: rutaTela,
                            prendaIndex: prendaIdx,
                            esGuardada: true
                        });
                        
                        console.log(`✅ Tela agregada a imagenesEnMemoria [${prendaIdx}][${telaIdx}]`);
                    }
                });
            }
            
            // Esperar a que se cree la tarjeta de producto
            setTimeout(() => {
                const productosCards = document.querySelectorAll('.producto-card');
                if (productosCards[prendaIdx]) {
                    const card = productosCards[prendaIdx];
                    const productoId = card.dataset.productoId || `producto-${prendaIdx}`;
                    const fotosPreview = card.querySelector('.fotos-preview');
                    
                    // Cargar fotos de prenda (son arrays JSON en el modelo)
                    if (fotosPreview && prenda.fotos && Array.isArray(prenda.fotos)) {
                        console.log(`📸 Cargando ${prenda.fotos.length} fotos de prenda en preview`);
                        prenda.fotos.forEach((fotoData, fotoIdx) => {
                            // Las fotos tienen estructura {ruta_original: '...', ruta_webp: '...'} o ser strings
                            let rutaFoto = '';
                            if (typeof fotoData === 'string') {
                                rutaFoto = fotoData;
                            } else if (fotoData.ruta_webp) {
                                rutaFoto = fotoData.ruta_webp;
                            } else if (fotoData.ruta_original) {
                                rutaFoto = fotoData.ruta_original;
                            } else if (fotoData.ruta) {
                                rutaFoto = fotoData.ruta;
                            } else if (fotoData.nombre) {
                                rutaFoto = fotoData.nombre;
                            }
                            
                            // Construir URL correctamente
                            let srcUrl = '';
                            if (rutaFoto) {
                                // Si ya comienza con /storage/, usarlo tal cual
                                if (rutaFoto.startsWith('/storage/')) {
                                    srcUrl = rutaFoto;
                                } else if (rutaFoto.startsWith('http')) {
                                    srcUrl = rutaFoto;
                                } else {
                                    // Si no, agregar /storage/ al inicio
                                    srcUrl = `/storage/${rutaFoto}`;
                                }
                            }
                            
                            // Crear el mismo diseño que las fotos nuevas
                            const preview = document.createElement('div');
                            preview.setAttribute('data-foto', 'true');
                            preview.setAttribute('data-foto-guardada', 'true'); // Marcar como guardada
                            preview.style.cssText = 'position: relative; width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f0f0f0; cursor: pointer;';
                            preview.innerHTML = `
                                <img src="${srcUrl}" style="width: 100%; height: 100%; object-fit: cover;">
                                <span style="position: absolute; top: 1px; left: 1px; background: #0066cc; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold;">${fotoIdx + 1}</span>
                                <button type="button" onclick="event.stopPropagation(); eliminarFoto('${productoId}', Array.from(this.closest('.fotos-preview').querySelectorAll('[data-foto]')).indexOf(this.closest('[data-foto]')))" style="position: absolute; top: 1px; left: 1px; background: #f44336; color: white; border: none; border-radius: 50%; width: 16px; height: 16px; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; padding: 0; opacity: 0; transition: opacity 0.2s;">✕</button>
                            `;
                            
                            // Mostrar botón de eliminar al pasar el mouse
                            preview.addEventListener('mouseenter', function() {
                                this.querySelector('button').style.opacity = '1';
                            });
                            preview.addEventListener('mouseleave', function() {
                                this.querySelector('button').style.opacity = '0';
                            });
                            
                            fotosPreview.appendChild(preview);
                            console.log('✅ Foto de prenda cargada:', srcUrl);
                        });
                    } else {
                        console.log(`📸 No hay fotos de prenda para cargar (fotos: ${prenda.fotos ? prenda.fotos.length : 0})`);
                    }
                    
                    // Cargar fotos de telas (desde tela_fotos)
                    const fotoTelaPreview = card.querySelector('.foto-tela-preview');
                    console.log(`🧵 DEBUG Tela Preview:`, {
                        encontrado: !!fotoTelaPreview,
                        selector: '.foto-tela-preview',
                        tela_fotos_existe: !!prenda.tela_fotos,
                        tela_fotos_es_array: Array.isArray(prenda.tela_fotos),
                        tela_fotos_count: prenda.tela_fotos ? (Array.isArray(prenda.tela_fotos) ? prenda.tela_fotos.length : 'no es array') : 0
                    });
                    if (fotoTelaPreview && prenda.tela_fotos && Array.isArray(prenda.tela_fotos)) {
                        console.log(`🧵 Cargando ${prenda.tela_fotos.length} fotos de tela en preview`);
                        prenda.tela_fotos.forEach((fotoData, fotoIdx) => {
                            // Extraer ruta correctamente
                            let rutaFoto = '';
                            if (typeof fotoData === 'string') {
                                rutaFoto = fotoData;
                            } else if (fotoData.ruta_webp) {
                                rutaFoto = fotoData.ruta_webp;
                            } else if (fotoData.ruta_original) {
                                rutaFoto = fotoData.ruta_original;
                            } else if (fotoData.ruta) {
                                rutaFoto = fotoData.ruta;
                            } else if (fotoData.nombre) {
                                rutaFoto = fotoData.nombre;
                            }
                            
                            // Construir URL correctamente
                            let srcUrl = '';
                            if (rutaFoto) {
                                if (rutaFoto.startsWith('/storage/')) {
                                    srcUrl = rutaFoto;
                                } else if (rutaFoto.startsWith('http')) {
                                    srcUrl = rutaFoto;
                                } else {
                                    srcUrl = `/storage/${rutaFoto}`;
                                }
                            }
                            
                            const img = document.createElement('img');
                            img.src = srcUrl;
                            img.style.cssText = 'width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;';
                            img.alt = 'Foto de tela';
                            img.title = 'Haz clic para eliminar';
                            img.dataset.ruta = srcUrl;
                            img.onclick = function() {
                                eliminarFotoLogoInmediatamente(srcUrl, window.cotizacionIdActual);
                            };
                            fotoTelaPreview.appendChild(img);
                            console.log(`✅ Foto de tela ${fotoIdx + 1} cargada:`, srcUrl);
                        });
                    }
                }
            }, 1000 + (prendaIdx * 200));
        });
    }
    
    // Cargar datos del logo (Paso 4)
    if (cotizacion.logo_cotizacion) {
        console.log('🎨 Cargando datos del logo:', cotizacion.logo_cotizacion);
        
        // Cargar descripción del logo
        if (cotizacion.logo_cotizacion.descripcion) {
            const descLogoInput = document.getElementById('descripcion_logo') || document.querySelector('textarea[name="descripcion_logo"]');
            if (descLogoInput) {
                descLogoInput.value = cotizacion.logo_cotizacion.descripcion;
                descLogoInput.dispatchEvent(new Event('input', { bubbles: true }));
                console.log('✅ Descripción del logo cargada');
            }
        }
        
        // Cargar técnicas del logo
        if (cotizacion.logo_cotizacion.tecnicas) {
            let tecnicas = cotizacion.logo_cotizacion.tecnicas;
            if (typeof tecnicas === 'string') {
                try {
                    tecnicas = JSON.parse(tecnicas);
                } catch (e) {
                    tecnicas = [];
                }
            }
            
            if (Array.isArray(tecnicas) && tecnicas.length > 0) {
                const tecnicasContainer = document.getElementById('tecnicas_seleccionadas');
                if (tecnicasContainer) {
                    tecnicas.forEach(tecnica => {
                        const div = document.createElement('div');
                        div.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; margin-right: 8px; margin-bottom: 8px;';
                        div.innerHTML = `
                            <span>${tecnica}</span>
                            <button type="button" onclick="this.closest('div').remove()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
                            <input type="hidden" name="tecnicas[]" value="${tecnica}">
                        `;
                        tecnicasContainer.appendChild(div);
                    });
                    console.log('✅ Técnicas cargadas:', tecnicas.length);
                }
            }
        }
        
        // Cargar ubicaciones del logo
        if (cotizacion.logo_cotizacion.ubicaciones) {
            let ubicaciones = cotizacion.logo_cotizacion.ubicaciones;
            if (typeof ubicaciones === 'string') {
                try {
                    ubicaciones = JSON.parse(ubicaciones);
                } catch (e) {
                    ubicaciones = [];
                }
            }
            
            if (Array.isArray(ubicaciones) && ubicaciones.length > 0) {
                const ubicacionesContainer = document.getElementById('ubicaciones_seleccionadas');
                if (ubicacionesContainer) {
                    ubicaciones.forEach(ubicacion => {
                        const div = document.createElement('div');
                        div.style.cssText = 'background: #10b981; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; margin-right: 8px; margin-bottom: 8px;';
                        div.innerHTML = `
                            <span>${ubicacion.seccion || ubicacion}</span>
                            <button type="button" onclick="this.closest('div').remove()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
                            <input type="hidden" name="ubicaciones[]" value="${ubicacion.seccion || ubicacion}">
                        `;
                        ubicacionesContainer.appendChild(div);
                    });
                    console.log('✅ Ubicaciones cargadas:', ubicaciones.length);
                }
            }
        }
        
        // Cargar observaciones generales
        if (cotizacion.logo_cotizacion.observaciones_generales) {
            let obsGenerales = cotizacion.logo_cotizacion.observaciones_generales;
            if (typeof obsGenerales === 'string') {
                try {
                    obsGenerales = JSON.parse(obsGenerales);
                } catch (e) {
                    obsGenerales = [];
                }
            }
            
            if (Array.isArray(obsGenerales) && obsGenerales.length > 0) {
                const obsContainer = document.getElementById('observaciones_lista');
                if (obsContainer) {
                    obsGenerales.forEach(obs => {
                        const fila = document.createElement('div');
                        fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
                        
                        let texto = obs.texto || obs;
                        let tipo = obs.tipo || 'texto';
                        let valor = obs.valor || '';
                        
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
                        obsContainer.appendChild(fila);
                    });
                    console.log('✅ Observaciones generales cargadas:', obsGenerales.length);
                }
            }
        }
    }
    
    // Cargar imágenes generales (del logo cotización)
    if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.fotos) {
        console.log('📸 Cargando imágenes generales:', cotizacion.logo_cotizacion.fotos);
        
        const galeriaImagenes = document.getElementById('galeria_imagenes');
        if (galeriaImagenes) {
            const fotos = cotizacion.logo_cotizacion.fotos;
            (Array.isArray(fotos) ? fotos : [fotos]).forEach((fotoData, fotoIdx) => {
                // Extraer ruta correctamente
                let rutaFoto = '';
                if (typeof fotoData === 'string') {
                    rutaFoto = fotoData;
                } else if (fotoData.ruta_webp) {
                    rutaFoto = fotoData.ruta_webp;
                } else if (fotoData.ruta_original) {
                    rutaFoto = fotoData.ruta_original;
                } else if (fotoData.ruta) {
                    rutaFoto = fotoData.ruta;
                } else if (fotoData.nombre) {
                    rutaFoto = fotoData.nombre;
                }
                
                if (!rutaFoto) return;
                
                // Construir URL correctamente
                let srcUrl = '';
                if (rutaFoto.startsWith('/storage/')) {
                    srcUrl = rutaFoto;
                } else if (rutaFoto.startsWith('http')) {
                    srcUrl = rutaFoto;
                } else {
                    srcUrl = `/storage/${rutaFoto}`;
                }
                
                const div = document.createElement('div');
                div.style.cssText = 'position: relative; width: 100px; height: 100px; background: #f0f0f0; border-radius: 4px; overflow: hidden;';
                div.setAttribute('data-foto-logo', 'true');
                div.setAttribute('data-foto-guardada', 'true');
                div.innerHTML = `
                    <img src="${srcUrl}" 
                         style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" 
                         alt="Imagen general"
                         title="Haz clic para eliminar"
                         data-ruta="${srcUrl}">
                    <button type="button" 
                            style="position: absolute; top: 0; right: 0; background: rgba(0,0,0,0.7); color: white; border: none; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; opacity: 0; transition: opacity 0.2s;">✕</button>
                `;
                
                // Mostrar botón al pasar el mouse
                div.addEventListener('mouseenter', function() {
                    this.querySelector('button').style.opacity = '1';
                });
                div.addEventListener('mouseleave', function() {
                    this.querySelector('button').style.opacity = '0';
                });
                
                // Agregar evento para eliminar
                const btn = div.querySelector('button');
                btn.onclick = function(e) {
                    e.stopPropagation();
                    eliminarFotoLogoInmediatamente(srcUrl, window.cotizacionIdActual);
                };
                
                galeriaImagenes.appendChild(div);
                console.log('✅ Imagen general cargada:', srcUrl);
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

/**
 * Eliminar foto de logo inmediatamente (sin esperar a guardar)
 */
async function eliminarFotoLogoInmediatamente(rutaFoto, cotizacionId) {
    // Mostrar modal de confirmación
    Swal.fire({
        title: '¿Eliminar imagen?',
        text: 'Esta imagen se borrará definitivamente de la carpeta.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f44336',
        cancelButtonColor: '#757575',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                html: '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;"><div style="width: 12px; height: 12px; border-radius: 50%; background: #f44336; animation: pulse 1.5s infinite;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #f44336; animation: pulse 1.5s infinite 0.3s;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #f44336; animation: pulse 1.5s infinite 0.6s;"></div></div><style>@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }</style>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });
            
            // Enviar solicitud al backend para eliminar inmediatamente
            fetch(window.location.origin + '/asesores/fotos/eliminar', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ruta: rutaFoto,
                    cotizacion_id: cotizacionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(`✅ Foto de logo eliminada del servidor:`, rutaFoto);
                    
                    // Eliminar del DOM
                    const fotoElement = document.querySelector(`img[data-ruta="${rutaFoto}"]`);
                    if (fotoElement) {
                        const container = fotoElement.closest('[data-foto-logo]');
                        if (container) {
                            container.remove();
                        }
                    }
                    
                    Swal.fire({
                        title: '¡Eliminada!',
                        text: 'La imagen ha sido eliminada correctamente.',
                        icon: 'success',
                        confirmButtonColor: '#1e40af',
                        timer: 2000
                    });
                } else {
                    console.error(`❌ Error al eliminar foto:`, data.message);
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo eliminar la imagen.',
                        icon: 'error',
                        confirmButtonColor: '#1e40af'
                    });
                }
            })
            .catch(error => {
                console.error(`❌ Error en la solicitud:`, error);
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo conectar con el servidor.',
                    icon: 'error',
                    confirmButtonColor: '#1e40af'
                });
            });
        }
    });
}

