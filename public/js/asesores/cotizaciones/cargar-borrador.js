/**
 * SISTEMA DE COTIZACIONES - CARGAR BORRADOR
 * Responsabilidad: Cargar datos de un borrador existente en el formulario
 */

function cargarBorrador(cotizacion) {
    if (!cotizacion) return;
    

    
    // 💾 GUARDAR COTIZACIÓN EN MEMORIA PARA PASO 3
    window.cotizacionData = cotizacion;

    
    //  LIMPIAR MEMORIA DE FOTOS ANTES DE CARGAR PARA EVITAR DUPLICADOS
    if (window.fotosSeleccionadas) {
        window.fotosSeleccionadas = {};

    }
    if (window.telasSeleccionadas) {
        window.telasSeleccionadas = {};

    }
    if (window.fotosEliminadasServidor) {
        window.fotosEliminadasServidor = { prendas: [], telas: [] };

    }
    
    // Guardar ID de cotización en variable global para usarlo en funciones de foto
    window.cotizacionIdActual = cotizacion.id;
    
    // Cargar cliente
    if (cotizacion.cliente) {
        const clienteInput = document.getElementById('cliente');
        if (clienteInput) {
            clienteInput.value = cotizacion.cliente;
            clienteInput.dispatchEvent(new Event('input', { bubbles: true }));
            clienteInput.dispatchEvent(new Event('change', { bubbles: true }));

        }
    }
    
    // Cargar tipo de cotización (tipo_venta en JSON, tipo_venta en el formulario)
    if (cotizacion.tipo_venta) {
        const tipoVentaSelect = document.getElementById('tipo_venta');
        if (tipoVentaSelect) {
            tipoVentaSelect.value = cotizacion.tipo_venta;
            tipoVentaSelect.dispatchEvent(new Event('change', { bubbles: true }));

        }
    }
    
    // Cargar especificaciones (del modal)
    if (cotizacion.especificaciones) {

        
        // Decodificar especificaciones si viene como string JSON
        let especificacionesDecodificadas = cotizacion.especificaciones;
        if (typeof especificacionesDecodificadas === 'string') {
            try {
                especificacionesDecodificadas = JSON.parse(especificacionesDecodificadas);
            } catch (e) {

                especificacionesDecodificadas = {};
            }
        }
        
        // Guardar especificaciones en variable global para acceso en el modal
        window.especificacionesActuales = especificacionesDecodificadas;

        
        // Solo continuar si hay especificaciones decodificadas
        if (Object.keys(especificacionesDecodificadas).length === 0) {

            return;
        }
        
        // Mapeo de claves JSON a IDs de tbody (algunas no coinciden)
        const tbodyMapping = {
            'forma_pago': 'tbody_pago',
            'disponibilidad': 'tbody_disponibilidad',
            'regimen': 'tbody_regimen',
            'se_ha_vendido': 'tbody_vendido',
            'ultima_venta': 'tbody_ultima_venta',
            'flete': 'tbody_flete'
        };
        
        // Las especificaciones vienen como arrays de objetos con estructura: {valor: "...", observacion: "..."}
        // Esperar a que el modal esté disponible
        setTimeout(() => {
            Object.keys(especificacionesDecodificadas).forEach(key => {
                const valor = especificacionesDecodificadas[key];
                
                // Si es un array, procesar cada elemento
                if (Array.isArray(valor) && valor.length > 0) {
                    valor.forEach((item, index) => {
                        if (typeof item === 'object' && item !== null) {
                            const valorItem = item.valor || '';
                            const observacion = item.observacion || '';
                            // Buscar la fila correspondiente por el label o valor
                            let fila = null;
                            
                            // Buscar en todos los tbody de la categoría correspondiente
                            // Usar mapeo para keys que no coinciden con su tbody_id
                            const tbodyId = tbodyMapping[key] || `tbody_${key}`;
                            const tbody = document.getElementById(tbodyId);
                            

                            
                            if (tbody) {
                                const filas = tbody.querySelectorAll('tr');
                                
                                // Primero intentar buscar por label fijo (DISPONIBILIDAD, FORMA DE PAGO, REGIMEN)
                                filas.forEach(tr => {
                                    const label = tr.querySelector('label');
                                    if (label && label.textContent.trim() === valorItem) {
                                        fila = tr;
                                    }
                                });
                                
                                // Si no se encontró por label, buscar PRIMERA fila (SE HA VENDIDO, ÚLTIMA VENTA, FLETE)
                                if (!fila && filas.length > 0) {
                                    // Para estas categorías, siempre usar la primera fila disponible
                                    fila = filas[index] || filas[0];

                                }
                            }
                            
                            if (fila) {
                                // Para items sin label fijo, cargar el valor en el itemInput
                                const itemInput = fila.querySelector('input[type="text"]:not([name*="_obs"])');
                                if (itemInput && !fila.querySelector('label')) {
                                    itemInput.value = valorItem;  // Sobrescribir cualquier valor anterior
                                    itemInput.dispatchEvent(new Event('input', { bubbles: true }));

                                }
                                
                                // Cargar la observación (sobrescribir si existe)
                                const obsInput = fila.querySelector('input[name*="_obs"]');
                                if (obsInput) {
                                    obsInput.value = observacion || '';  // Cargar observación o dejar vacío
                                    obsInput.value = observacion;
                                    obsInput.dispatchEvent(new Event('input', { bubbles: true }));

                                }
                                
                                // Marcar el checkbox
                                const checkbox = fila.querySelector('input[type="checkbox"].checkbox-guardar');
                                if (checkbox) {
                                    checkbox.checked = true;
                                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));

                                }
                            } else {

                            }
                        }
                    });
                }
            });
            
            //  GUARDAR EN window.especificacionesSeleccionadas PARA REGUARDAR
            // Después de cargar todo en el DOM, cargar también en memoria
            setTimeout(() => {

                window.especificacionesSeleccionadas = especificacionesDecodificadas || {};
                
                // Actualizar color del botón enviar
                if (typeof actualizarColorBotonEnviar === 'function') {
                    actualizarColorBotonEnviar();

                }
                

            }, 1000);
        }, 500);
    }
    
    // Cargar productos/prendas
    // Soportar ambos nombres: productos (legacy) y prendas (nuevo)
    const prendas = cotizacion.prendas || cotizacion.productos || [];
    
    if (prendas && Array.isArray(prendas) && prendas.length > 0) {

        
        prendas.forEach((prenda, index) => {

            
            //  CAPTURAR EL ÍNDICE EN UNA CONSTANTE PARA EVITAR PROBLEMAS DE CLOSURE
            const prendaIndexActual = index;
            
            // Agregar un nuevo producto solo si no es el primero (el primero ya existe)
            if (prendaIndexActual > 0) {
                agregarProductoFriendly();
            }
            
            // Esperar más tiempo y con reintentos
            const intentarCargar = (intento = 0) => {
                const productosCards = document.querySelectorAll('.producto-card');
                

                
                // IMPORTANTE: Usar el producto correspondiente al índice, NO el último
                const productoActual = productosCards[prendaIndexActual];
                
                if (!productoActual) {
                    if (intento < 5) {

                        setTimeout(() => intentarCargar(intento + 1), 200);
                        return;
                    } else {

                        return;
                    }
                }
                

                
                // Nombre del producto
                const inputNombre = productoActual.querySelector('input[name*="nombre_producto"]');
                if (inputNombre) {
                    // Soportar ambos campos: nombre_producto y nombre
                    const nombreValue = prenda.nombre_producto || prenda.nombre || '';
                    inputNombre.value = nombreValue;
                    inputNombre.dispatchEvent(new Event('input', { bubbles: true }));
                    inputNombre.dispatchEvent(new Event('change', { bubbles: true }));

                } else if (intento < 5) {

                    setTimeout(() => intentarCargar(intento + 1), 200);
                    return;
                }
                
                // Descripción
                const textareaDesc = productoActual.querySelector('textarea[name*="descripcion"]');
                if (textareaDesc) {
                    textareaDesc.value = prenda.descripcion || '';
                    textareaDesc.dispatchEvent(new Event('input', { bubbles: true }));
                    textareaDesc.dispatchEvent(new Event('change', { bubbles: true }));

                }
                    
                    // Tallas - buscar en los botones de talla
                    // Soportar múltiples formatos: array strings, array objects con .talla, objeto con .prendas_tallas[]
                    let tallasValores = [];
                    
                    if (prenda.tallas && Array.isArray(prenda.tallas)) {
                        // Formato: ["XS", "S", "M"] o [{talla: "XS"}, ...]
                        tallasValores = prenda.tallas.map(t => {
                            if (typeof t === 'string') {
                                return t;
                            } else if (typeof t === 'object' && t.talla) {
                                return t.talla;
                            }
                            return null;
                        }).filter(t => t !== null);
                    } else if (prenda.prendas_tallas && Array.isArray(prenda.prendas_tallas)) {
                        // Formato: relación de Eloquent
                        tallasValores = prenda.prendas_tallas.map(pt => pt.talla).filter(t => t);
                    }
                    

                    
                    if (tallasValores.length > 0) {
                        // Detectar tipo de talla (letra o número)
                        const tallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
                        const esLetra = tallasValores.some(t => tallasLetras.includes(t));
                        const tipoTalla = esLetra ? 'letra' : 'numero';
                        


                        
                        // Seleccionar tipo de talla
                        const tipoSelect = productoActual.querySelector('.talla-tipo-select');
                        if (tipoSelect) {
                            tipoSelect.value = tipoTalla;
                            tipoSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            tipoSelect.dispatchEvent(new Event('input', { bubbles: true }));


                        }
                        
                        // Esperar a que se carguen los botones (aumentar delay)
                        setTimeout(() => {

                            
                            // Verificar que los botones existan
                            const botonesExistentes = productoActual.querySelectorAll('.talla-btn');

                            
                            // Si es número, detectar género
                            if (!esLetra) {
                                const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
                                const esGenero = tallasValores.some(t => tallasDama.includes(t));
                                const genero = esGenero ? 'dama' : 'caballero';
                                
                                const generoSelect = productoActual.querySelector('.talla-genero-select');
                                if (generoSelect) {
                                    generoSelect.value = genero;
                                    generoSelect.dispatchEvent(new Event('change', { bubbles: true }));

                                }
                            }
                            
                            // Esperar a que se carguen los botones del género
                            setTimeout(() => {

                                
                                // Hacer clic en los botones de talla
                                let tallasActivadas = 0;
                                tallasValores.forEach(tallaValor => {
                                    const tallaBtn = productoActual.querySelector(`.talla-btn[data-talla="${tallaValor}"]`);
                                    if (tallaBtn) {
                                        tallaBtn.click();
                                        tallasActivadas++;

                                    } else {

                                        // Debug: mostrar botones disponibles
                                        const botonesDisponibles = productoActual.querySelectorAll('.talla-btn');

                                    }
                                });
                                

                                
                                // Hacer clic en "Agregar Tallas"
                                setTimeout(() => {
                                    const btnAgregarTallas = productoActual.querySelector('button[onclick*="agregarTallasSeleccionadas"]');
                                    if (btnAgregarTallas) {
                                        btnAgregarTallas.click();

                                    }
                                }, 300);
                            }, 500);
                        }, 500);
                    }
                    
                    // Cargar variantes (color, tela, referencia, manga, bolsillos, broche, reflectivo)
                    // Soportar múltiples formatos: objeto directo, array [object], o relación Eloquent
                    let variantes = prenda.variantes;
                    
                    // Inicializar array global de variaciones
                    if (!window.variacionesGuardadas) {
                        window.variacionesGuardadas = [];
                    }
                    
                    // Si variantes es un array, tomar el primer elemento
                    if (Array.isArray(variantes) && variantes.length > 0) {

                        variantes = variantes[0];
                    }
                    
                    // Si aún no hay variantes, intentar con .prendas_variantes
                    if (!variantes && prenda.prendas_variantes && Array.isArray(prenda.prendas_variantes) && prenda.prendas_variantes.length > 0) {

                        variantes = prenda.prendas_variantes[0];
                    }
                    
                    if (variantes && typeof variantes === 'object') {


                        
                        // Cargar género en el selector de TALLAS A COTIZAR
                        if (variantes.genero_id !== undefined && variantes.genero_id !== null) {
                            const generoSelect = productoActual.querySelector('.talla-genero-select');
                            if (generoSelect) {
                                // Mapeo de IDs a valores del select
                                let valorGenero = '';
                                if (variantes.genero_id === 4 || variantes.genero_id === '4') {
                                    // 4 = Ambos (ya no disponible, se ignora)

                                    valorGenero = '';
                                } else if (variantes.genero_id === 1 || variantes.genero_id === '1') {
                                    valorGenero = 'dama';
                                } else if (variantes.genero_id === 2 || variantes.genero_id === '2') {
                                    valorGenero = 'caballero';
                                }
                                
                                if (valorGenero) {
                                    generoSelect.value = valorGenero;
                                    generoSelect.dispatchEvent(new Event('change', { bubbles: true }));

                                } else {

                                }
                            } else {

                            }
                        } else {

                        }
                        
                        // Color
                        if (variantes.color) {
                            const colorInput = productoActual.querySelector('.color-input');
                            if (colorInput) {
                                colorInput.value = variantes.color;
                                colorInput.dispatchEvent(new Event('input', { bubbles: true }));

                            }
                        }
                        
                        // Tela
                        if (variantes.telas_multiples && Array.isArray(variantes.telas_multiples) && variantes.telas_multiples.length > 0) {
                            const primeraTela = variantes.telas_multiples[0];
                            if (primeraTela.tela) {
                                const telaSelect = productoActual.querySelector('.tela-input');
                                if (telaSelect) {
                                    telaSelect.value = primeraTela.tela;
                                    telaSelect.dispatchEvent(new Event('change', { bubbles: true }));

                                    
                                    // Trigger change event after a delay para que las imágenes se carguen
                                    setTimeout(() => {
                                        telaSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                    }, 100);
                                } else {

                                }
                            }
                            
                        // Color primera tela
                            if (primeraTela.color) {
                                const colorInput = productoActual.querySelector('input[name*="[color_id]"]');
                                if (colorInput) {
                                    colorInput.value = primeraTela.color;
                                    colorInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    colorInput.dispatchEvent(new Event('change', { bubbles: true }));
                                    colorInput.dispatchEvent(new Event('blur', { bubbles: true }));

                                }
                            }
                            
                            // Referencia primera tela
                            if (primeraTela.referencia) {
                                const refInput = productoActual.querySelector('.referencia-input');
                                if (refInput) {
                                    refInput.value = primeraTela.referencia;
                                    refInput.dispatchEvent(new Event('input', { bubbles: true }));

                                } else {

                                }
                            }
                            
                            // CREAR FILAS ADICIONALES PARA TELAS 2, 3, etc.
                            if (variantes.telas_multiples.length > 1) {

                                
                                setTimeout(() => {
                                    const btnAgregarTela = productoActual.querySelector('.btn-agregar-tela');
                                    
                                    // Usar función recursiva para crear filas secuencialmente
                                    const crearFilaTela = (index) => {
                                        if (index >= variantes.telas_multiples.length) {

                                            return;
                                        }
                                        
                                        const telaData = variantes.telas_multiples[index];
                                        
                                        // Hacer clic en "Agregar Tela"
                                        if (btnAgregarTela) {
                                            btnAgregarTela.click();
                                            
                                            // Esperar que se cree la fila y llenar datos
                                            setTimeout(() => {
                                                // Buscar todas las filas visibles de telas en este producto
                                                const todasLasFilas = Array.from(productoActual.querySelectorAll('table tbody tr')).filter(tr => {
                                                    // Filtrar solo las filas que tienen inputs de tela
                                                    return tr.querySelector('input[name*="[tela_id]"]') !== null;
                                                });
                                                

                                                const nuevaFila = todasLasFilas[index];
                                                
                                                if (nuevaFila) {

                                                    
                                                    // Color
                                                    if (telaData.color) {
                                                        const colorInput = nuevaFila.querySelector('.color-input, input[name*="[color_id]"]');
                                                        if (colorInput) {
                                                            colorInput.value = telaData.color;
                                                            colorInput.dispatchEvent(new Event('input', { bubbles: true }));
                                                            colorInput.dispatchEvent(new Event('change', { bubbles: true }));
                                                            colorInput.dispatchEvent(new Event('blur', { bubbles: true }));

                                                        } else {

                                                        }
                                                    }
                                                    
                                                    // Tela
                                                    if (telaData.tela) {
                                                        const telaInput = nuevaFila.querySelector('.tela-input, input[name*="[tela_id]"]');
                                                        if (telaInput) {
                                                            telaInput.value = telaData.tela;
                                                            telaInput.dispatchEvent(new Event('input', { bubbles: true }));
                                                            telaInput.dispatchEvent(new Event('change', { bubbles: true }));
                                                            telaInput.dispatchEvent(new Event('blur', { bubbles: true }));

                                                        } else {

                                                        }
                                                    }
                                                    
                                                    // Referencia
                                                    if (telaData.referencia) {
                                                        const refInput = nuevaFila.querySelector('.referencia-input, input[name*="[referencia]"]');
                                                        if (refInput) {
                                                            refInput.value = telaData.referencia;
                                                            refInput.dispatchEvent(new Event('input', { bubbles: true }));

                                                        }
                                                    }
                                                    

                                                } else {

                                                }
                                                
                                                // Crear siguiente fila
                                                crearFilaTela(index + 1);
                                            }, 300);
                                        }
                                    };
                                    
                                    // Iniciar desde la fila 1 (la 0 ya existe)
                                    crearFilaTela(1);
                                }, 400);
                            }
                        } else {

                        }
                        
                        // Manga - Checkbox y Select
                        if (variantes.tipo_manga_id) {
                            const mangaCheckbox = productoActual.querySelector('input[name*="aplica_manga"]');
                            if (mangaCheckbox) {
                                mangaCheckbox.checked = true;
                                mangaCheckbox.dispatchEvent(new Event('change', { bubbles: true }));

                            }
                            
                            setTimeout(() => {
                                // Buscar el input de manga
                                const mangaInput = productoActual.querySelector('.manga-input');
                                
                                if (mangaInput) {
                                    // Mapeo de IDs a nombres
                                    const mangasMap = {
                                        1: 'Larga',
                                        2: 'Corta',
                                        3: '3/4'
                                    };
                                    
                                    const nombreManga = mangasMap[variantes.tipo_manga_id] || variantes.tipo_manga || `Manga ${variantes.tipo_manga_id}`;
                                    
                                    // Usar la función seleccionarManga de variantes-prendas.js si está disponible
                                    if (typeof seleccionarManga === 'function') {
                                        seleccionarManga(variantes.tipo_manga_id, nombreManga, mangaInput);
                                    } else {
                                        // Fallback manual
                                        const mangaIdInput = productoActual.querySelector('.manga-id-input');
                                        if (mangaIdInput) {
                                            mangaIdInput.value = variantes.tipo_manga_id;
                                        }
                                        mangaInput.value = nombreManga;
                                    }
                                    

                                }
                            }, 300);
                        }
                        
                        // Observación de Manga
                        if (variantes.obs_manga) {
                            const mangaObs = productoActual.querySelector('input[name*="obs_manga"]');
                            if (mangaObs) {
                                mangaObs.value = variantes.obs_manga;
                                mangaObs.dispatchEvent(new Event('input', { bubbles: true }));

                            }
                        }
                        
                        // Bolsillos - Checkbox
                        if (variantes.tiene_bolsillos) {
                            const bolsillosCheckbox = productoActual.querySelector('input[name*="aplica_bolsillos"]');
                            if (bolsillosCheckbox) {
                                bolsillosCheckbox.checked = true;
                                bolsillosCheckbox.dispatchEvent(new Event('change', { bubbles: true }));

                            }
                        }
                        
                        // Observación de Bolsillos
                        if (variantes.obs_bolsillos) {
                            const bolsillosObs = productoActual.querySelector('input[name*="obs_bolsillos"]');
                            if (bolsillosObs) {
                                bolsillosObs.value = variantes.obs_bolsillos;
                                bolsillosObs.dispatchEvent(new Event('input', { bubbles: true }));

                            }
                        }
                        
                        // Broche - Checkbox y Select
                        if (variantes.tipo_broche_id) {
                            const brocheCheckbox = productoActual.querySelector('input[name*="aplica_broche"]');
                            if (brocheCheckbox) {
                                brocheCheckbox.checked = true;
                                brocheCheckbox.dispatchEvent(new Event('change', { bubbles: true }));

                            }
                            
                            setTimeout(() => {
                                const brocheSelect = productoActual.querySelector('select[name*="tipo_broche_id"]');
                                if (brocheSelect) {
                                    brocheSelect.value = variantes.tipo_broche_id;
                                    brocheSelect.dispatchEvent(new Event('change', { bubbles: true }));

                                }
                            }, 200);
                        }
                        
                        // Observación de Broche
                        if (variantes.obs_broche) {
                            const brocheObs = productoActual.querySelector('input[name*="obs_broche"]');
                            if (brocheObs) {
                                brocheObs.value = variantes.obs_broche;
                                brocheObs.dispatchEvent(new Event('input', { bubbles: true }));

                            }
                        }
                        
                        // Reflectivo - Checkbox
                        if (variantes.tiene_reflectivo) {
                            const reflectivoCheckbox = productoActual.querySelector('input[name*="aplica_reflectivo"]');
                            if (reflectivoCheckbox) {
                                reflectivoCheckbox.checked = true;
                                reflectivoCheckbox.dispatchEvent(new Event('change', { bubbles: true }));

                            }
                        }
                        
                        // Observación de Reflectivo
                        if (variantes.obs_reflectivo) {
                            const reflectivoObs = productoActual.querySelector('input[name*="obs_reflectivo"]');
                            if (reflectivoObs) {
                                reflectivoObs.value = variantes.obs_reflectivo;
                                reflectivoObs.dispatchEvent(new Event('input', { bubbles: true }));

                            }
                        }
                        
                        // JEAN PANTALÓN - Tipo de jean
                        if (variantes.tipo_jean_pantalon || variantes.es_jean_pantalon) {
                            // Buscar el input de tipo de prenda para activar el selector
                            const tipoPrendaInput = productoActual.querySelector('.prenda-search-input');
                            if (tipoPrendaInput) {
                                // Mostrar el selector si contiene JEAN o PANTALÓN
                                const valorTipoPrenda = tipoPrendaInput.value.toUpperCase();
                                if (valorTipoPrenda.includes('JEAN') || valorTipoPrenda.includes('PANTALÓN') || valorTipoPrenda.includes('PANTALON')) {
                                    if (typeof mostrarSelectorVariantes === 'function') {
                                        mostrarSelectorVariantes(tipoPrendaInput);
                                    }
                                    
                                    // Esperar a que el selector se cree y luego establecer los valores
                                    setTimeout(() => {
                                        const tipoJeanSelect = productoActual.querySelector('select[name*="tipo_jean_pantalon"]');
                                        const esJeanHidden = productoActual.querySelector('input[name*="es_jean_pantalon"]');
                                        
                                        if (tipoJeanSelect && variantes.tipo_jean_pantalon) {
                                            tipoJeanSelect.value = variantes.tipo_jean_pantalon;
                                            tipoJeanSelect.dispatchEvent(new Event('change', { bubbles: true }));

                                        }
                                        
                                        if (esJeanHidden) {
                                            esJeanHidden.value = variantes.es_jean_pantalon ? '1' : '0';

                                        }
                                    }, 300);
                                }
                            }
                        }
                        
                        // Guardar variaciones en variable global para Paso 4
                        // Obtener tallas seleccionadas DESDE el objeto prenda
                        let tallasSeleccionadas = [];
                        
                        // Intentar obtener desde tallas array
                        if (prenda.tallas && Array.isArray(prenda.tallas)) {
                            tallasSeleccionadas = prenda.tallas.map(t => {
                                if (typeof t === 'string') return t;
                                if (typeof t === 'object' && t.talla) return t.talla;
                                return null;
                            }).filter(t => t !== null);
                        }
                        
                        // Si no, intentar desde prendas_tallas
                        if (tallasSeleccionadas.length === 0 && prenda.prendas_tallas && Array.isArray(prenda.prendas_tallas)) {
                            tallasSeleccionadas = prenda.prendas_tallas.map(pt => pt.talla).filter(t => t);
                        }
                        

                        
                        // Mapeos para obtener nombres de IDs
                        const mangasMap = {
                            1: 'Larga',
                            2: 'Corta',
                            3: '3/4'
                        };
                        
                        // Obtener nombre de manga si existe
                        let nombreManga = '';
                        if (variantes.tipo_manga_id) {
                            nombreManga = mangasMap[variantes.tipo_manga_id] || `Manga ${variantes.tipo_manga_id}`;
                        }
                        
                        // Obtener nombre de broche si existe - buscar en el select del DOM
                        let nombreBroche = '';
                        if (variantes.tipo_broche_id) {
                            const brocheSelect = productoActual?.querySelector('select[name*="tipo_broche"]');
                            if (brocheSelect) {
                                const opcionBroche = brocheSelect.querySelector(`option[value="${variantes.tipo_broche_id}"]`);
                                if (opcionBroche) {
                                    nombreBroche = opcionBroche.textContent;
                                } else {
                                    nombreBroche = `Broche ${variantes.tipo_broche_id}`;
                                }
                            } else {
                                nombreBroche = `Broche ${variantes.tipo_broche_id}`;
                            }
                        }
                        
                        // Construir objeto de variaciones con observaciones
                        let variacionesObj = {
                            nombreProducto: prenda.nombre_producto,
                            tallas: tallasSeleccionadas.join(', '),
                            genero: variantes.genero || '', // Agregar género para mostrar en resumen
                            color: variantes.color || '',
                            tela: (variantes.telas_multiples && variantes.telas_multiples.length > 0) ? variantes.telas_multiples[0].tela : '',
                            referencia: (variantes.telas_multiples && variantes.telas_multiples.length > 0) ? variantes.telas_multiples[0].referencia : '',
                            manga: nombreManga,
                            obsManga: variantes.obs_manga || '',
                            bolsillos: variantes.tiene_bolsillos || false,
                            obsBolsillos: variantes.obs_bolsillos || '',
                            broche: nombreBroche,
                            obsBroche: variantes.obs_broche || '',
                            reflectivo: variantes.tiene_reflectivo || false,
                            obsReflectivo: variantes.obs_reflectivo || ''
                        };
                        
                        window.variacionesGuardadas.push(variacionesObj);

                    } else {

                    }
                    
                    // Cargar fotos de prenda
                    if (prenda.fotos && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {

                        
                        setTimeout(() => {
                            const fotosContainer = productoActual.querySelector('.fotos-preview');
                            if (fotosContainer) {
                                prenda.fotos.forEach((foto, idx) => {
                                    const fotoDiv = document.createElement('div');
                                    fotoDiv.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; background: #f0f0f0;';
                                    
                                    const rutaFoto = foto.ruta_webp || foto.ruta_original || foto.url;
                                    let urlFoto = rutaFoto;
                                    if (rutaFoto.startsWith('http')) {
                                        urlFoto = rutaFoto;
                                    } else if (!rutaFoto.startsWith('/storage')) {
                                        urlFoto = '/storage/' + (rutaFoto.startsWith('/') ? rutaFoto.substring(1) : rutaFoto);
                                    }
                                    
                                    const btnBorrar = foto.id 
                                        ? `<button type="button" onclick="borrarImagenPrenda(${foto.id}, this)" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 14px;" title="Eliminar imagen">✕</button>`
                                        : `<button type="button" onclick="this.closest('div').remove()" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 14px;" title="Eliminar imagen">✕</button>`;
                                    
                                    fotoDiv.innerHTML = `
                                        <img src="${urlFoto}" style="width: 100%; height: 100%; object-fit: cover;" alt="Foto prenda ${idx + 1}" data-foto-id="${foto.id || ''}">
                                        ${btnBorrar}
                                    `;
                                    
                                    fotosContainer.appendChild(fotoDiv);
                                    
                                    if (foto.id && window.imagenesEnMemoria && window.imagenesEnMemoria.prendaConIndice) {
                                        window.imagenesEnMemoria.prendaConIndice.push({
                                            prendaIndex: prendaIndexActual,
                                            file: urlFoto,  // Ruta de la imagen guardada
                                            esGuardada: true,
                                            fotoId: foto.id
                                        });

                                    }
                                    

                                });
                            } else {

                            }
                        }, 500);
                    }
                    
                    // Cargar fotos de tela CON DISTRIBUCIÓN POR ÍNDICE
                    if (prenda.tela_fotos && Array.isArray(prenda.tela_fotos) && prenda.tela_fotos.length > 0) {

                        
                        // Delay mayor para esperar a que se creen todas las filas de telas
                        setTimeout(() => {
                            // Agrupar fotos por tela_index
                            const fotosPorTela = {};
                            prenda.tela_fotos.forEach((fotoData) => {
                                const telaIdx = parseInt(fotoData.tela_index) || 0;
                                if (!fotosPorTela[telaIdx]) {
                                    fotosPorTela[telaIdx] = [];
                                }
                                fotosPorTela[telaIdx].push(fotoData);
                            });
                            

                            
                            // Buscar todas las filas de telas que tengan input de tela_id
                            const filasTabla = Array.from(productoActual.querySelectorAll('table tbody tr')).filter(tr => {
                                return tr.querySelector('input[name*="[tela_id]"]') !== null;
                            });

                            
                            // Distribuir fotos a cada fila según su índice
                            filasTabla.forEach((fila, filaIdx) => {
                                const fotosContainer = fila.querySelector('.foto-tela-preview');
                                
                                if (fotosContainer && fotosPorTela[filaIdx]) {
                                    fotosContainer.innerHTML = ''; // Limpiar contenedor
                                    
                                    fotosPorTela[filaIdx].forEach((foto, fotoIdx) => {
                                        const fotoDiv = document.createElement('div');
                                        fotoDiv.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; background: #f0f0f0;';
                                        
                                        const rutaFoto = foto.ruta_webp || foto.ruta_original || foto.url;
                                        let urlFoto = rutaFoto;
                                        if (rutaFoto.startsWith('http')) {
                                            urlFoto = rutaFoto;
                                        } else if (!rutaFoto.startsWith('/storage')) {
                                            urlFoto = '/storage/' + (rutaFoto.startsWith('/') ? rutaFoto.substring(1) : rutaFoto);
                                        }
                                        
                                        const btnBorrar = foto.id 
                                            ? `<button type="button" onclick="borrarImagenTela(${foto.id}, this)" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 14px;" title="Eliminar imagen de tela">✕</button>`
                                            : `<button type="button" onclick="this.closest('div').remove()" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 14px;" title="Eliminar imagen de tela">✕</button>`;
                                        
                                        fotoDiv.innerHTML = `
                                            <img src="${urlFoto}" style="width: 100%; height: 100%; object-fit: cover;" alt="Foto tela ${fotoIdx + 1}" data-foto-id="${foto.id || ''}">
                                            ${btnBorrar}
                                        `;
                                        
                                        fotosContainer.appendChild(fotoDiv);
                                        
                                        if (foto.id && window.imagenesEnMemoria && window.imagenesEnMemoria.telaConIndice) {
                                            window.imagenesEnMemoria.telaConIndice.push({
                                                prendaIndex: prendaIndexActual,
                                                telaIndex: filaIdx,
                                                file: urlFoto,
                                                esGuardada: true,
                                                fotoId: foto.id
                                            });

                                        }
                                        

                                    });
                                }
                            });
                        }, 1500); // Delay de 1500ms para esperar creación secuencial de todas las filas
                    }
            };
            
            setTimeout(() => intentarCargar(), 500);
        });
    } else {

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

            }
        });
    }
    
    // Cargar imágenes guardadas desde productos/prendas
    if (cotizacion.productos && Array.isArray(cotizacion.productos)) {

        
        cotizacion.productos.forEach((prenda, prendaIdx) => {

            
            // Cargar fotos de prenda en window.imagenesEnMemoria
            if (prenda.fotos && Array.isArray(prenda.fotos)) {

                prenda.fotos.forEach((foto, fotoIdx) => {
                    // Las fotos guardadas son objetos con ruta_original, ruta_webp, etc.
                    // Agregar a window.imagenesEnMemoria como referencias (no File objects)
                    if (foto.ruta_original || foto.ruta_webp) {
                        const rutaFoto = foto.ruta_original || foto.ruta_webp;

                        
                        // Agregar a window.imagenesEnMemoria.prendaConIndice
                        if (!window.imagenesEnMemoria.prendaConIndice) {
                            window.imagenesEnMemoria.prendaConIndice = [];
                        }
                        
                        window.imagenesEnMemoria.prendaConIndice.push({
                            file: rutaFoto, // Guardar la ruta como string, no como File object
                            prendaIndex: prendaIdx,
                            esGuardada: true // Marcar como imagen guardada
                        });
                        

                    }
                });
            }
            
            // Cargar telas en window.imagenesEnMemoria
            if (prenda.tela_fotos && Array.isArray(prenda.tela_fotos)) {

                prenda.tela_fotos.forEach((tela, telaIdx) => {
                    if (tela.ruta_original || tela.ruta_webp) {
                        const rutaTela = tela.ruta_original || tela.ruta_webp;

                        
                        // Agregar a window.imagenesEnMemoria.telaConIndice
                        if (!window.imagenesEnMemoria.telaConIndice) {
                            window.imagenesEnMemoria.telaConIndice = [];
                        }
                        
                        window.imagenesEnMemoria.telaConIndice.push({
                            file: rutaTela,
                            prendaIndex: prendaIdx,
                            esGuardada: true
                        });
                        

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

                        });
                    } else {

                    }
                    
                    // Cargar fotos de telas (desde tela_fotos) - DISTRIBUIDAS POR TELA_INDEX
                    const fotoTelaPreview = card.querySelector('.foto-tela-preview');
                    console.log(` DEBUG Tela Preview:`, {
                        encontrado: !!fotoTelaPreview,
                        selector: '.foto-tela-preview',
                        tela_fotos_existe: !!prenda.tela_fotos,
                        tela_fotos_es_array: Array.isArray(prenda.tela_fotos),
                        tela_fotos_count: prenda.tela_fotos ? (Array.isArray(prenda.tela_fotos) ? prenda.tela_fotos.length : 'no es array') : 0
                    });
                    
                    if (prenda.tela_fotos && Array.isArray(prenda.tela_fotos) && prenda.tela_fotos.length > 0) {

                        
                        // Esperar a que las filas de telas estén renderizadas
                        setTimeout(() => {
                            // Agrupar fotos por tela_index
                            const fotosPorTela = {};
                            prenda.tela_fotos.forEach((fotoData) => {
                                const telaIdx = fotoData.tela_index !== undefined && fotoData.tela_index !== null 
                                    ? parseInt(fotoData.tela_index) 
                                    : 0; // Default a 0 si no tiene tela_index
                                
                                if (!fotosPorTela[telaIdx]) {
                                    fotosPorTela[telaIdx] = [];
                                }
                                fotosPorTela[telaIdx].push(fotoData);
                            });
                            

                            
                            // Obtener todas las filas de telas
                            const filasTabla = card.querySelectorAll('tbody[id^="tabla-telas-"] tr');

                            
                            // Para cada fila de tela, agregar sus fotos correspondientes
                            filasTabla.forEach((fila, filaIdx) => {
                                const fotosContainer = fila.querySelector('.foto-tela-preview');
                                if (fotosContainer && fotosPorTela[filaIdx]) {
                                    fotosContainer.innerHTML = ''; // Limpiar
                                    
                                    fotosPorTela[filaIdx].forEach((fotoData, fotoIdx) => {
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
                                        fotosContainer.appendChild(img);

                                    });
                                }
                            });
                        }, 500); // Esperar a que las filas de telas se rendericen
                    }
                }
            }, 1000 + (prendaIdx * 200));
        });
    }
    
    // Cargar datos del logo (Paso 4)
    if (cotizacion.logo_cotizacion) {

        
        // Cargar tipo de venta del logo (PASO 3)
        if (cotizacion.logo_cotizacion.tipo_venta) {
            const tipoVentaPaso3 = document.getElementById('tipo_venta_paso3');
            if (tipoVentaPaso3) {
                tipoVentaPaso3.value = cotizacion.logo_cotizacion.tipo_venta;
                tipoVentaPaso3.dispatchEvent(new Event('change', { bubbles: true }));

            }
        }
        
        // Cargar descripción del logo
        if (cotizacion.logo_cotizacion.descripcion) {
            const descLogoInput = document.getElementById('descripcion_logo') || document.querySelector('textarea[name="descripcion_logo"]');
            if (descLogoInput) {
                descLogoInput.value = cotizacion.logo_cotizacion.descripcion;
                descLogoInput.dispatchEvent(new Event('input', { bubbles: true }));

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
                // Guardar en variable global para Paso 4
                window.tecnicasGuardadas = tecnicas;
                window.obsTecnicasGuardadas = cotizacion.logo_cotizacion.observaciones_tecnicas || '';

                
                setTimeout(() => {
                    const tecnicasContainer = document.getElementById('tecnicas_seleccionadas');

                    if (tecnicasContainer) {
                        tecnicas.forEach(tecnica => {
                            const div = document.createElement('div');
                            div.style.cssText = 'background: rgb(52, 152, 219); color: white; padding: 6px 12px; border-radius: 20px; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600;';
                            div.innerHTML = `
                                <input type="hidden" name="tecnicas[]" value="${tecnica}">
                                <span>${tecnica}</span>
                                <button type="button" onclick="this.closest('div').remove()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem; padding: 0; line-height: 1;">✕</button>
                            `;
                            tecnicasContainer.appendChild(div);
                        });

                    }
                }, 1500);
            }
        }
        
        // Cargar observaciones de técnica
        if (cotizacion.logo_cotizacion.observaciones_tecnicas) {
            setTimeout(() => {
                const obsTecnicasTextarea = document.querySelector('textarea[name="observaciones_tecnicas"]');

                if (obsTecnicasTextarea) {
                    obsTecnicasTextarea.value = cotizacion.logo_cotizacion.observaciones_tecnicas;
                    obsTecnicasTextarea.dispatchEvent(new Event('input', { bubbles: true }));

                }
            }, 1500);
        }
        
        // Cargar ubicaciones del logo
        if (cotizacion.logo_cotizacion.secciones) {
            let ubicaciones = cotizacion.logo_cotizacion.secciones;
            if (typeof ubicaciones === 'string') {
                try {
                    ubicaciones = JSON.parse(ubicaciones);
                } catch (e) {
                    ubicaciones = [];
                }
            }
            
            if (Array.isArray(ubicaciones) && ubicaciones.length > 0) {
                // Guardar ubicaciones en variable global para Paso 4
                window.ubicacionesGuardadas = ubicaciones;

                
                setTimeout(() => {

                    // Cargar en seccionesSeleccionadasFriendly para que renderizarSeccionesFriendly() las dibuje
                    if (typeof window.seccionesSeleccionadasFriendly !== 'undefined') {
                        window.seccionesSeleccionadasFriendly = [];
                        ubicaciones.forEach(ubicacion => {
                            window.seccionesSeleccionadasFriendly.push({
                                ubicacion: ubicacion.seccion || ubicacion.ubicacion || ubicacion,
                                opciones: ubicacion.ubicaciones_seleccionadas || ubicacion.opciones || [],
                                observaciones: ubicacion.observaciones || '',
                                tallas: ubicacion.tallas || []
                            });
                        });
                        // Renderizar usando la función existente (mismo diseño que crear nuevo)
                        if (typeof renderizarSeccionesFriendly === 'function') {
                            renderizarSeccionesFriendly();

                        }
                    }
                }, 500);
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
                setTimeout(() => {
                    const obsContainer = document.getElementById('observaciones_lista');

                    if (obsContainer) {
                        // Limpiar observaciones existentes
                        obsContainer.innerHTML = '';
                        
                        obsGenerales.forEach(obs => {
                            let texto = obs.texto || obs;
                            let tipo = obs.tipo || 'texto';
                            let valor = obs.valor || '';
                            
                            // Crear el mismo HTML que agregarObservacion()
                            const fila = document.createElement('div');
                            fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
                            fila.innerHTML = `
                                <input type="text" name="observaciones_generales[]" class="input-large" value="${texto}" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
                                    <div class="obs-checkbox-mode" style="display: ${tipo === 'checkbox' ? 'flex' : 'none'}; align-items: center; gap: 5px;">
                                        <input type="checkbox" name="observaciones_check[]" style="width: 20px; height: 20px; cursor: pointer;" ${valor === 'on' ? 'checked' : ''}>
                                    </div>
                                    <div class="obs-text-mode" style="display: ${tipo === 'texto' ? 'flex' : 'none'}; flex: 1;">
                                        <input type="text" name="observaciones_valor[]" placeholder="Valor..." value="${valor}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                    <button type="button" class="obs-toggle-btn" style="background: #3498db; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">✓/✎</button>
                                </div>
                                <button type="button" onclick="this.closest('div').remove()" style="background: #ef4444; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; flex-shrink: 0;">✕</button>
                            `;
                            
                            // Agregar evento al toggle button
                            const toggleBtn = fila.querySelector('.obs-toggle-btn');
                            toggleBtn.onclick = function() {
                                const checkboxMode = fila.querySelector('.obs-checkbox-mode');
                                const textMode = fila.querySelector('.obs-text-mode');
                                const isCheckboxMode = checkboxMode.style.display !== 'none';
                                
                                if (isCheckboxMode) {
                                    checkboxMode.style.display = 'none';
                                    textMode.style.display = 'flex';
                                } else {
                                    checkboxMode.style.display = 'flex';
                                    textMode.style.display = 'none';
                                }
                            };
                            
                            obsContainer.appendChild(fila);
                        });

                    }
                }, 500);
            }
        }
    }
    
    // Cargar imágenes generales (del logo cotización)
    if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.fotos) {

        
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
                
                // Si tiene ID, usar el endpoint de borrado; si no, usar eliminación local
                const btnBorrar = fotoData.id
                    ? `<button type="button" style="position: absolute; top: 0; right: 0; background: rgba(0,0,0,0.7); color: white; border: none; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; opacity: 0; transition: opacity 0.2s;" data-foto-id="${fotoData.id}">✕</button>`
                    : `<button type="button" style="position: absolute; top: 0; right: 0; background: rgba(0,0,0,0.7); color: white; border: none; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; opacity: 0; transition: opacity 0.2s;">✕</button>`;
                
                div.innerHTML = `
                    <img src="${srcUrl}" 
                         data-ruta="${rutaFoto}"
                         style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" 
                         alt="Imagen general"
                         title="Haz clic para eliminar"
                         data-foto-id="${fotoData.id || ''}">
                    ${btnBorrar}
                `;
                
                // 📌 IMPORTANTE: Agregar a window.imagenesEnMemoria.logo para que se envíe al hacer click en ENVIAR
                if (fotoData.id && window.imagenesEnMemoria && window.imagenesEnMemoria.logo) {
                    // Crear un objeto Blob-like o File-like con la ruta
                    window.imagenesEnMemoria.logo.push({
                        ruta: srcUrl,
                        esGuardada: true,
                        fotoId: fotoData.id
                    });

                }
                
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
                    
                    // Si tiene ID de BD, usar el nuevo endpoint
                    if (fotoData.id) {
                        borrarImagenLogo(fotoData.id, btn);
                    } else {
                        // Si es nueva (sin guardar), solo eliminar del DOM
                        eliminarFotoLogoInmediatamente(srcUrl, window.cotizacionIdActual);
                    }
                };
                
                galeriaImagenes.appendChild(div);

            });
        }
    }
    

    if (typeof actualizarResumenFriendly === 'function') {
        actualizarResumenFriendly();
    } else {

    }
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

            window.showToast('Foto eliminada correctamente', 'success');
        } else {

            window.showToast('Error al eliminar la foto: ' + data.message, 'error');
        }
    } catch (error) {

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

                    
                    //  PASO 1: Eliminar TODOS los elementos con data-foto-logo del DOM que coincidan
                    // Buscar más robustamente por atributo data-ruta
                    const galeriaImagenes = document.getElementById('galeria_imagenes');
                    if (galeriaImagenes) {
                        // Buscar TODOS los elementos - usar selector más específico
                        const allElements = galeriaImagenes.querySelectorAll('div[data-foto-guardada="true"][data-foto-logo="true"]');
                        let eliminados = 0;
                        
                        allElements.forEach(element => {
                            const img = element.querySelector('img');
                            if (img) {
                                const dataRuta = img.getAttribute('data-ruta');
                                if (dataRuta) {
                                    // Comparar rutas (normalizar para eliminar /storage/)
                                    let rutaNormalizada1 = (rutaFoto || '').replace(/^\/storage\//, '').trim();
                                    let rutaNormalizada2 = (dataRuta || '').replace(/^\/storage\//, '').trim();
                                    
                                    // Comparaciones múltiples para asegurar que coincida
                                    if (rutaNormalizada1 === rutaNormalizada2 || 
                                        rutaFoto === dataRuta ||
                                        rutaFoto.endsWith(dataRuta) ||
                                        dataRuta.endsWith(rutaFoto)) {

                                        element.remove();
                                        eliminados++;
                                    }
                                }
                            }
                        });

                        
                        // Si no encontró con ambos atributos, buscar solo con data-ruta
                        if (eliminados === 0) {

                            const allDivs = galeriaImagenes.querySelectorAll('div');
                            allDivs.forEach(div => {
                                const img = div.querySelector('img');
                                if (img) {
                                    const dataRuta = img.getAttribute('data-ruta');
                                    if (dataRuta) {
                                        let rutaNormalizada1 = (rutaFoto || '').replace(/^\/storage\//, '').trim();
                                        let rutaNormalizada2 = (dataRuta || '').replace(/^\/storage\//, '').trim();
                                        
                                        if (rutaNormalizada1 === rutaNormalizada2 || rutaFoto === dataRuta) {

                                            div.remove();
                                            eliminados++;
                                        }
                                    }
                                }
                            });

                        }
                    }
                    
                    //  PASO 2: Eliminar también de window.imagenesEnMemoria.logo
                    if (window.imagenesEnMemoria && window.imagenesEnMemoria.logo && Array.isArray(window.imagenesEnMemoria.logo)) {
                        const beforeCount = window.imagenesEnMemoria.logo.length;


                        
                        // Extraer solo el nombre del archivo para comparación más flexible
                        const nombreArchivo = rutaFoto.split('/').pop();

                        
                        window.imagenesEnMemoria.logo = window.imagenesEnMemoria.logo.filter((imagen, idx) => {

                            
                            // Si es un string (ruta completa)
                            if (typeof imagen === 'string') {
                                let nombreEnMemoria = imagen.split('/').pop();

                                
                                // Comparar por nombre de archivo
                                if (nombreEnMemoria === nombreArchivo) {

                                    return false;  // Eliminar
                                }
                                return true;  // Mantener
                            }
                            
                            // Si es un objeto (con propiedades de ruta)
                            if (imagen && typeof imagen === 'object') {
                                const rutas = [imagen.ruta, imagen.file, imagen.url].filter(Boolean);
                                let coincide = false;
                                
                                rutas.forEach(ruta => {
                                    let nombreEnRuta = (ruta || '').split('/').pop();

                                    if (nombreEnRuta === nombreArchivo || ruta === rutaFoto) {
                                        coincide = true;

                                    }
                                });
                                
                                if (coincide) {

                                    return false;  // Eliminar
                                }
                                return true;  // Mantener
                            }
                            
                            return true;  // Mantener otros tipos
                        });
                        


                    }
                    
                    Swal.fire({
                        title: '¡Eliminada!',
                        text: 'La imagen ha sido eliminada correctamente.',
                        icon: 'success',
                        confirmButtonColor: '#1e40af',
                        timer: 2000
                    });
                } else {

                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo eliminar la imagen.',
                        icon: 'error',
                        confirmButtonColor: '#1e40af'
                    });
                }
            })
            .catch(error => {

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


