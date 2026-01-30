/**
 * SISTEMA DE COTIZACIONES - GUARDADO Y ENVÍO
 * Responsabilidad: Guardar, enviar cotizaciones y subir imágenes
 * Compatible con: localStorage (persistencia) y WebSockets (sin conflictos)
 */

// ============ FUNCIÓN HELPER: PROCESAR GÉNERO "AMBOS" ============

/**
 * Procesa el campo género para convertir "ambos" en ["dama", "caballero"]
 */
function procesarGenero(genero) {
    if (!genero) return null;
    
    if (typeof genero === 'string') {
        if (genero === 'ambos') {
            return ['dama', 'caballero'];
        }
        return [genero];
    }
    
    if (Array.isArray(genero)) {
        // Si el array contiene "ambos", expandirlo
        if (genero.includes('ambos')) {
            const otros = genero.filter(g => g !== 'ambos');
            return [...new Set([...otros, 'dama', 'caballero'])]; // Evitar duplicados
        }
        return genero;
    }
    
    return null;
}

/**
 * Validar si una técnica tiene información escrita válida
 * @param {Object} tecnica - Técnica con prendas
 * @returns {boolean} - true si tiene información válida
 */
function tienenInformacionValida(tecnicas) {
    if (!tecnicas || !Array.isArray(tecnicas) || tecnicas.length === 0) {
        return false;
    }
    
    return tecnicas.every(tecnica => {
        if (!tecnica.prendas || !Array.isArray(tecnica.prendas)) {
            return false;
        }
        
        // Validar que cada prenda tenga al menos una ubicación
        return tecnica.prendas.some(prenda => {
            const tieneUbicaciones = prenda.ubicaciones && 
                                    Array.isArray(prenda.ubicaciones) && 
                                    prenda.ubicaciones.some(u => u && u.trim());
            
            const tieneTallas = prenda.talla_cantidad && 
                               Array.isArray(prenda.talla_cantidad) && 
                               prenda.talla_cantidad.length > 0;
            
            const tieneImagenes = prenda.imagenes_files && 
                                 Array.isArray(prenda.imagenes_files) && 
                                 prenda.imagenes_files.length > 0;
            
            // Requiere ubicación Y (tallas O imágenes)
            return tieneUbicaciones && (tieneTallas || tieneImagenes);
        });
    });
}

// ============ GUARDAR COTIZACIÓN ============

async function guardarCotizacion() {






    
    // Debug: Mostrar estado del contenedor antes de recopilar
    const contenedorDebug = document.getElementById('tecnicas_seleccionadas');
    if (contenedorDebug) {



        Array.from(contenedorDebug.children).forEach((child, i) => {
            const input = child.querySelector('input[name="tecnicas[]"]');
            if (input) {

            }
        });
    }
    
    const btnGuardar = document.querySelector('button[onclick="guardarCotizacion()"]');
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    if (btnGuardar) btnGuardar.disabled = true;
    if (btnEnviar) btnEnviar.disabled = true;
    

    const datos = recopilarDatos();
    if (!datos) {

        Swal.fire({
            title: 'Error',
            text: 'No se pudieron recopilar los datos del formulario',
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnEnviar) btnEnviar.disabled = false;
        return;
    }
    
    //  NO convertir a Base64 - enviar archivos directamente como File objects

    
    // Validar que tipo_venta esté seleccionado
    const tipoVentaSelect = document.getElementById('tipo_venta');
    const tipoVentaPaso3Select = document.getElementById('tipo_venta_paso3');
    const tipoVenta = tipoVentaSelect ? tipoVentaSelect.value : '';
    const tipoVentaPaso3 = tipoVentaPaso3Select ? tipoVentaPaso3Select.value : '';
    if (!tipoVenta) {

        Swal.fire({
            title: 'Tipo de cotización requerido',
            text: 'Por favor selecciona el tipo de cotización (M/D/X)',
            icon: 'warning',
            confirmButtonColor: '#1e40af'
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnEnviar) btnEnviar.disabled = false;
        return;
    }
    

    Swal.fire({
        title: 'Guardando...',
        html: '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;"><div style="width: 12px; height: 12px; border-radius: 50%; background: #1e40af; animation: pulse 1.5s infinite;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #1e40af; animation: pulse 1.5s infinite 0.3s;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #1e40af; animation: pulse 1.5s infinite 0.6s;"></div></div><style>@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }</style>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: (modal) => {
            modal.style.pointerEvents = 'none';
        }
    });
    try {

        //  USAR FormData PARA ENVIAR ARCHIVOS File
        const formData = new FormData();
        
        // Datos básicos
        formData.append('tipo', 'borrador');     // ← AGREGAR: Identificar acción GUARDAR
        formData.append('accion', 'guardar');    // ← AGREGAR: Identificar acción GUARDAR
        formData.append('es_borrador', '1');     // Marcar como borrador
        formData.append('cliente', datos.cliente);
        formData.append('tipo_venta', tipoVenta);
        formData.append('tipo_venta_paso3', tipoVentaPaso3);  // Enviar PASO 3 independiente
        formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
        // Si estamos editando un borrador, enviar el ID
        if (window.cotizacionIdActual) {
            formData.append('cotizacion_id', window.cotizacionIdActual);

        }
        
        // Enviar fotos a eliminar (marcadas como eliminadas)
        if (window.fotosAEliminar && window.fotosAEliminar.length > 0) {

            window.fotosAEliminar.forEach((foto, idx) => {
                formData.append(`fotos_a_eliminar[${idx}]`, foto.ruta);

            });
        }
        
        // Secciones de texto
        formData.append('descripcion_logo', datos.descripcion_logo || '');
        formData.append('tecnicas', JSON.stringify(datos.tecnicas || []));
        formData.append('observaciones_tecnicas', datos.observaciones_tecnicas || '');
        formData.append('secciones', JSON.stringify(datos.ubicaciones || []));
        formData.append('observaciones_generales', JSON.stringify(datos.observaciones_generales || []));
        formData.append('especificaciones', JSON.stringify(datos.especificaciones || {}));

        
        //  PRENDAS CON ARCHIVOS File
        if (datos.productos && Array.isArray(datos.productos)) {
            datos.productos.forEach((producto, index) => {
                // Datos de prenda
                formData.append(`prendas[${index}][nombre_producto]`, producto.nombre_producto || '');
                formData.append(`prendas[${index}][descripcion]`, producto.descripcion || '');
                formData.append(`prendas[${index}][cantidad]`, producto.cantidad || 1);
                formData.append(`prendas[${index}][tallas]`, JSON.stringify(producto.tallas || []));
                
                // Variantes como array (no JSON string)
                const variantes = producto.variantes || {};
                
                console.log(` DEBUG VARIANTES PRODUCTO ${index}:`, {
                    keys: Object.keys(variantes),
                    tipo_manga_id: variantes.tipo_manga_id,
                    tipo_manga: variantes.tipo_manga,
                    tiene_bolsillos: variantes.tiene_bolsillos,
                    todas_variantes: variantes
                });
                
                Object.keys(variantes).forEach(key => {
                    let value = variantes[key];
                    
                    if (key === 'telas_multiples' && Array.isArray(value)) {
                        // Caso especial: telas_multiples es un array de objetos
                        // Enviar como JSON string completo
                        formData.append(`prendas[${index}][variantes][${key}]`, JSON.stringify(value));
                    } else if (Array.isArray(value)) {
                        // Si es array, agregar cada elemento
                        value.forEach((item, idx) => {
                            if (typeof item === 'object' && item !== null) {
                                formData.append(`prendas[${index}][variantes][${key}][${idx}]`, JSON.stringify(item));
                            } else {
                                formData.append(`prendas[${index}][variantes][${key}][${idx}]`, item);
                            }
                        });

                    } else if (typeof value === 'object' && value !== null) {
                        // Si es objeto, convertir a JSON string
                        formData.append(`prendas[${index}][variantes][${key}]`, JSON.stringify(value));
                    } else if (typeof value === 'boolean') {
                        // Convertir booleanos a 1/0 para Laravel
                        formData.append(`prendas[${index}][variantes][${key}]`, value ? '1' : '0');
                    } else {
                        // Si es valor simple, agregar directamente
                        formData.append(`prendas[${index}][variantes][${key}]`, value || '');
                    }
                    
                    if (key === 'tipo_manga_id') {

                    }
                });
                
                //  FOTOS DE PRENDA (nuevas y existentes) - AL GUARDAR: enviar nuevas + IDs de existentes
                if (window.imagenesEnMemoria && window.imagenesEnMemoria.prendaConIndice) {
                    const fotosDeEstaPrenda = window.imagenesEnMemoria.prendaConIndice.filter(p => p.prendaIndex === index);
                    const fotosNuevas = [];
                    const fotosExistentes = [];
                    
                    fotosDeEstaPrenda.forEach((item, fotoIndex) => {
                        if (item.file instanceof File) {
                            fotosNuevas.push(item.file);

                        } else if (item.fotoId && typeof item.file === 'string') {
                            fotosExistentes.push(item.fotoId);

                        }
                    });
                    
                    fotosNuevas.forEach((foto) => {
                        formData.append(`prendas[${index}][fotos][]`, foto);
                    });
                    
                    if (fotosExistentes.length > 0) {
                        formData.append(`prendas[${index}][fotos_existentes]`, JSON.stringify(fotosExistentes));

                    }
                }
                
                //  TELAS (File objects desde datos.productos, window.telasSeleccionadas, o imagenesEnMemoria)


                
                let telasYaProcesadas = false;
                
                // OPCIÓN 1: Procesar telas desde datos.productos[index].telas (PRIMERO - PREFERIDA)
                // Esta opción tiene prioridad porque contiene la estructura consistente
                if (datos.productos && datos.productos[index] && datos.productos[index].telas && datos.productos[index].telas.length > 0) {

                    const telasDelProducto = datos.productos[index].telas;
                    const telasPorIndice = {};
                    
                    telasDelProducto.forEach(tela => {
                        const telaIdx = tela.telaIndex || 0;
                        if (!telasPorIndice[telaIdx]) {
                            telasPorIndice[telaIdx] = [];
                        }
                        if (tela.file instanceof File) {
                            telasPorIndice[telaIdx].push(tela.file);

                        }
                    });
                    
                    Object.keys(telasPorIndice).forEach(telaIdx => {
                        telasPorIndice[telaIdx].forEach((foto, fotoIdx) => {
                            formData.append(`prendas[${index}][telas][${telaIdx}][fotos][${fotoIdx}]`, foto);

                        });
                    });
                    telasYaProcesadas = true;

                } else {

                }
                
                // OPCIÓN 2: Buscar telas en window.telasSeleccionadas (FALLBACK SOLO SI OPCIÓN 1 NO FUNCIONÓ)
                if (!telasYaProcesadas) {
                    const prendaCard = document.querySelectorAll('.producto-card')[index];
                    if (prendaCard) {
                        const productoId = prendaCard.dataset.productoId;

                        
                        if (window.telasSeleccionadas && window.telasSeleccionadas[productoId]) {
                            const telasObj = window.telasSeleccionadas[productoId];

                            
                            // Iterar sobre cada tela (los índices son las claves del objeto)
                            for (let telaIdx in telasObj) {
                                if (telasObj.hasOwnProperty(telaIdx) && Array.isArray(telasObj[telaIdx])) {
                                    const fotosDelaTela = telasObj[telaIdx];

                                    
                                    // Agregar cada foto de esta tela al FormData
                                    fotosDelaTela.forEach((foto, fotoIdx) => {
                                        if (foto instanceof File) {
                                            formData.append(`prendas[${index}][telas][${telaIdx}][fotos][${fotoIdx}]`, foto);

                                        } else {

                                        }
                                    });
                                }
                            }
                            telasYaProcesadas = true;

                        } else {

                        }
                    }
                }
                
                // OPCIÓN 3: FALLBACK - Buscar en window.imagenesEnMemoria.telaConIndice (ÚLTIMO RECURSO)
                if (!telasYaProcesadas && window.imagenesEnMemoria && window.imagenesEnMemoria.telaConIndice) {
                    const telasDeEstaPrenda = window.imagenesEnMemoria.telaConIndice.filter(t => t.prendaIndex === index);
                    if (telasDeEstaPrenda.length > 0) {

                        const telasPorIndice = {};
                        telasDeEstaPrenda.forEach(item => {
                            const telaIdx = item.telaIndex || 0;
                            if (!telasPorIndice[telaIdx]) {
                                telasPorIndice[telaIdx] = { nuevas: [], existentes: [] };
                            }
                            if (item.file instanceof File) {
                                telasPorIndice[telaIdx].nuevas.push(item.file);
                            } else if (item.fotoId && typeof item.file === 'string') {
                                telasPorIndice[telaIdx].existentes.push(item.fotoId);
                            }
                        });
                        
                        Object.keys(telasPorIndice).forEach(telaIdx => {
                            const telaFotos = telasPorIndice[telaIdx];
                            telaFotos.nuevas.forEach((foto) => {
                                formData.append(`prendas[${index}][telas][${telaIdx}][fotos][0]`, foto);

                            });
                            if (telaFotos.existentes.length > 0) {
                                formData.append(`prendas[${index}][telas][${telaIdx}][fotos_existentes]`, JSON.stringify(telaFotos.existentes));

                            }
                        });
                        telasYaProcesadas = true;

                    }
                }
                
                if (!telasYaProcesadas) {

                }
            });
        }
        
        // 🗑️ FOTOS ELIMINADAS DEL SERVIDOR (enviar IDs para eliminar)
        if (window.fotosEliminadasServidor) {
            if (window.fotosEliminadasServidor.telas && window.fotosEliminadasServidor.telas.length > 0) {
                window.fotosEliminadasServidor.telas.forEach((fotoId, idx) => {
                    formData.append(`fotos_telas_eliminadas[${idx}]`, fotoId);

                });
            }
            if (window.fotosEliminadasServidor.prendas && window.fotosEliminadasServidor.prendas.length > 0) {
                window.fotosEliminadasServidor.prendas.forEach((fotoId, idx) => {
                    formData.append(`fotos_prendas_eliminadas[${idx}]`, fotoId);

                });
            }
        }
        
        //  LOGO - IMÁGENES (nuevas y existentes) - AL GUARDAR: enviar nuevas + IDs de existentes
        if (window.imagenesEnMemoria && window.imagenesEnMemoria.logo && Array.isArray(window.imagenesEnMemoria.logo)) {

            
            const logosNuevos = [];
            const logosExistentes = [];
            
            window.imagenesEnMemoria.logo.forEach((imagen, imagenIndex) => {
                if (imagen instanceof File) {
                    logosNuevos.push(imagen);

                } else if (imagen.fotoId && (typeof imagen.ruta === 'string' || typeof imagen.file === 'string')) {
                    logosExistentes.push(imagen.fotoId);

                }
            });
            
            logosNuevos.forEach((imagen) => {
                formData.append(`logo[imagenes][]`, imagen);
            });
            
            if (logosExistentes.length > 0) {
                formData.append(`logo_fotos_existentes`, JSON.stringify(logosExistentes));

            }
        } else {

        }
        
        //  LOGO - FOTOS GUARDADAS (Para conservar las existentes al reguardar)
        // Buscar imágenes dentro del contenedor galeria_imagenes que tengan data-foto-guardada="true"
        const galeriaImagenes = document.getElementById('galeria_imagenes');
        if (galeriaImagenes) {
            const fotosGuardadas = galeriaImagenes.querySelectorAll('[data-foto-guardada="true"]');
            if (fotosGuardadas.length > 0) {

                fotosGuardadas.forEach((div, index) => {
                    // Las rutas están en el atributo data-ruta del img dentro del div
                    const img = div.querySelector('img');
                    const ruta = img ? (img.getAttribute('data-ruta') || img.src) : null;
                    if (ruta && !ruta.includes('data:image')) {
                        formData.append(`logo_fotos_guardadas[]`, ruta);

                    }
                });
            } else {

            }
        } else {

        }
        
        //  TÉCNICAS DE LOGO (PASO 3) - Para cotizaciones combinadas (PL)
        // Las técnicas se guardan en window.tecnicasAgregadasPaso3


        
        if (window.tecnicasAgregadasPaso3 && Array.isArray(window.tecnicasAgregadasPaso3) && window.tecnicasAgregadasPaso3.length > 0) {
            //  VALIDAR que las técnicas tengan información válida (ubicaciones + tallas/imágenes)
            const tieneInfoValida = tienenInformacionValida(window.tecnicasAgregadasPaso3);
            
            if (!tieneInfoValida) {

            } else {

                
                // Enviar técnicas con toda su información (prendas, ubicaciones, tallas, etc)
                formData.append('logo[tecnicas_agregadas]', JSON.stringify(window.tecnicasAgregadasPaso3));
                
                console.log(' Técnicas agregadas al FormData:', {
                    count: window.tecnicasAgregadasPaso3.length,
                    tecnicas_json: JSON.stringify(window.tecnicasAgregadasPaso3).substring(0, 200) + '...'
                });
                
                //  PROCESAR IMÁGENES DEL PASO 3 - Enviar archivos nuevos

                console.log('🔍 DEBUG: window.tecnicasAgregadasPaso3 structure:', JSON.parse(JSON.stringify(window.tecnicasAgregadasPaso3.map(t => ({
                    tipo: t.tipo,
                    prendas_count: t.prendas ? t.prendas.length : 0,
                    prendas: t.prendas ? t.prendas.map(p => ({
                        nombre: p.nombre_prenda,
                        imagenes_count: p.imagenes ? p.imagenes.length : 0,
                        imagenes: p.imagenes ? p.imagenes.map(img => ({
                            tipo: img.tipo,
                            has_file: !!img.file,
                            has_ruta: !!img.ruta,
                            file_type: img.file ? img.file.constructor.name : 'N/A',
                            file_size: img.file ? img.file.size : 'N/A',
                            nombreCompartido: img.nombreCompartido || null
                        })) : []
                    })) : []
                }))), 
                null, 2));
                
                let totalImagenesP3 = 0;
                const archivosAgregados = [];
                const imagenesCompartidasProcesadas = new Map(); // Almacenar {clave: {file, fieldName}}
                
                // PASO 1: Identificar y procesar imágenes compartidas PRIMERO (una sola vez)
                const imagenesCompartidasEnProceso = {};
                window.tecnicasAgregadasPaso3.forEach((tecnica, tecnicaIndex) => {
                    if (tecnica.prendas && Array.isArray(tecnica.prendas)) {
                        tecnica.prendas.forEach((prenda, prendaIndex) => {
                            if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
                                prenda.imagenes.forEach((imagen, imagenIndex) => {
                                    // Buscar imágenes compartidas
                                    if (imagen.file && (imagen.file instanceof Blob || imagen.file instanceof File) && imagen.tipo === 'paso3' && imagen.nombreCompartido && imagen.tecnicasCompartidas) {
                                        const clave = imagen.nombreCompartido;
                                        
                                        // Guardar solo la primera ocurrencia
                                        if (!imagenesCompartidasEnProceso[clave]) {
                                            const fieldName = `logo[imagenes_paso3][${tecnicaIndex}][${prendaIndex}][${imagenIndex}]`;
                                            imagenesCompartidasEnProceso[clave] = {
                                                file: imagen.file,
                                                fieldName: fieldName,
                                                tecnicasCompartidas: imagen.tecnicasCompartidas
                                            };
                                            
                                            console.log('🔴 Imagen compartida encontrada:', {
                                                clave: clave,
                                                tecnicas: imagen.tecnicasCompartidas,
                                                fieldName: fieldName
                                            });
                                        }
                                    }
                                });
                            }
                        });
                    }
                });
                
                // Procesar imágenes compartidas identificadas
                for (let clave in imagenesCompartidasEnProceso) {
                    const dato = imagenesCompartidasEnProceso[clave];
                    formData.append(dato.fieldName, dato.file);
                    
                    // Agregar metadatos de imagen compartida
                    formData.append(`logo[imagenes_compartidas][${clave}]`, JSON.stringify({
                        nombreCompartido: clave,
                        tecnicasCompartidas: dato.tecnicasCompartidas
                    }));
                    
                    archivosAgregados.push({
                        fieldName: dato.fieldName,
                        size: dato.file.size,
                        type: dato.file.type,
                        esCompartida: true,
                        tecnicasCompartidas: dato.tecnicasCompartidas
                    });
                    
                    totalImagenesP3++;
                }
                
                // PASO 2: Procesar imágenes no compartidas
                window.tecnicasAgregadasPaso3.forEach((tecnica, tecnicaIndex) => {
                    if (tecnica.prendas && Array.isArray(tecnica.prendas)) {
                        tecnica.prendas.forEach((prenda, prendaIndex) => {
                            if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
                                prenda.imagenes.forEach((imagen, imagenIndex) => {
                                    // Solo procesar imágenes nuevas del PASO 3 que NO sean compartidas
                                    if (imagen.file && (imagen.file instanceof Blob || imagen.file instanceof File) && imagen.tipo === 'paso3' && !imagen.nombreCompartido) {
                                        const fieldName = `logo[imagenes_paso3][${tecnicaIndex}][${prendaIndex}][${imagenIndex}]`;
                                        formData.append(fieldName, imagen.file);
                                        archivosAgregados.push({
                                            fieldName: fieldName,
                                            size: imagen.file.size,
                                            type: imagen.file.type,
                                            esCompartida: false
                                        });
                                        totalImagenesP3++;

                                    } else if (imagen.tipo === 'paso3' && !imagen.file) {

                                    }
                                });
                            }
                        });
                    }
                });
                


            }
        } else {

        }
        
        //  REFLECTIVO (PASO 4) - Para cotizaciones combinadas (PL)
        if (window.tipoCotizacionGlobal === 'PL' || window.tipoCotizacionGlobal === 'PB' || window.tipoCotizacionGlobal === 'RF') {

            
            //  ACTUALIZAR window.prendas_reflectivo_paso4 DESDE EL DOM
            // Esta función captura los datos actuales de la UI en el PASO 4
            if (typeof capturePrendasReflectivoPaso4 === 'function') {
                const prendasCapturadas = capturePrendasReflectivoPaso4();
                // Reconstruir window.prendas_reflectivo_paso4 con los datos capturados
                window.prendas_reflectivo_paso4 = prendasCapturadas.map((prenda, idx) => ({
                    index: idx,
                    tipo_prenda: prenda.tipo_prenda,
                    descripcion: prenda.descripcion,
                    ubicaciones: prenda.ubicaciones || [],
                    tallas: prenda.tallas || [],
                    variaciones: prenda.variaciones || {},
                    observaciones_generales: prenda.observaciones_generales || [],
                    imagenes: prenda.imagenes || []
                }));

            }
            
            // Obtener descripción del reflectivo (PASO 4) - garantizar que sea string, no null
            const reflectivoElement = document.getElementById('descripcion_reflectivo');
            const reflectivoDescripcion = (reflectivoElement?.value || '').trim();
            
            //  IMPORTANTE: Obtener ubicaciones desde prendas_reflectivo_paso4 (nuevo modelo)
            // Si NO existe esa variable, fallback a window.ubicacionesReflectivo (compatibilidad)
            let ubicacionesReflectivo = [];
            
            if (typeof window.prendas_reflectivo_paso4 !== 'undefined' && window.prendas_reflectivo_paso4.length > 0) {
                // Reunir TODAS las ubicaciones de TODAS las prendas

                
                window.prendas_reflectivo_paso4.forEach((prenda) => {
                    if (prenda.ubicaciones && prenda.ubicaciones.length > 0) {

                        ubicacionesReflectivo.push(...prenda.ubicaciones);
                    }
                });
                

            } else if (typeof window.ubicacionesReflectivo !== 'undefined') {
                // Fallback: usar la versión antigua

                ubicacionesReflectivo = window.ubicacionesReflectivo || [];
            }
            
            // Obtener observaciones generales del reflectivo (si existen)
            const observacionesReflectivo = window.observacionesReflectivo || [];
            
            //  VALIDAR que reflectivo tenga información escrita válida
            // Solo incluir si hay ubicaciones O descripción + tallas/imágenes
            const tieneUbicacionesReflectivo = ubicacionesReflectivo && ubicacionesReflectivo.length > 0;
            const tieneDescripcionReflectivo = reflectivoDescripcion && reflectivoDescripcion.length > 0;
            const tieneImagenesReflectivo = window.imagenesReflectivo && window.imagenesReflectivo.length > 0;
            
            //  IMPORTANTE: También verificar directamente prendas_reflectivo_paso4
            const tienePrendasP4ConDatos = typeof window.prendas_reflectivo_paso4 !== 'undefined' && 
                                            window.prendas_reflectivo_paso4.length > 0;
            
            const refletivoTieneInfoValida = tieneUbicacionesReflectivo || 
                                            (tieneDescripcionReflectivo && tieneImagenesReflectivo) ||
                                            tienePrendasP4ConDatos;  //  Agregar esta condición
            
            console.log(' Reflectivo capturado (PASO GUARDADO):', {
                elemento_existe: !!reflectivoElement,
                valor_raw: reflectivoElement?.value,
                valor_final: reflectivoDescripcion,
                ubicaciones_raw: ubicacionesReflectivo,
                ubicaciones_count: ubicacionesReflectivo.length,
                observaciones_count: observacionesReflectivo.length,
                tienePrendasP4ConDatos,
                tieneUbicacionesReflectivo,
                tieneDescripcionReflectivo,
                tieneImagenesReflectivo,
                tieneInfoValida: refletivoTieneInfoValida,
                ubicaciones_stringified: JSON.stringify(ubicacionesReflectivo)
            });
            
            // SOLO agregar reflectivo a FormData si tiene información válida
            if (refletivoTieneInfoValida) {
                formData.append('reflectivo[descripcion]', reflectivoDescripcion);
                formData.append('reflectivo[ubicacion]', JSON.stringify(ubicacionesReflectivo));
                formData.append('reflectivo[observaciones_generales]', JSON.stringify(observacionesReflectivo));
                formData.append('ubicaciones_reflectivo', JSON.stringify(ubicacionesReflectivo));
                
                //  AGREGAR DATOS COMPLETOS DE PRENDAS DEL PASO 4
                // Esto permite al backend guardar ubicaciones específicas para cada prenda
                if (typeof window.prendas_reflectivo_paso4 !== 'undefined' && window.prendas_reflectivo_paso4.length > 0) {
                    formData.append('prendas_reflectivo_paso4', JSON.stringify(window.prendas_reflectivo_paso4));

                }
                //  IMÁGENES DEL REFLECTIVO - PASO 4
                if (window.prendas_reflectivo_paso4 && Array.isArray(window.prendas_reflectivo_paso4)) {
                    console.log('🔍 DEBUG PASO 4 - window.prendas_reflectivo_paso4:', window.prendas_reflectivo_paso4);
                    let totalImagenesP4Reflectivo = 0;
                    
                    window.prendas_reflectivo_paso4.forEach((prenda, prendaIndex) => {
                        if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
                            prenda.imagenes.forEach((imagen, imagenIndex) => {
                                // ✅ IMPORTANTE: Solo procesar imágenes del PASO 4 que tengan File object (no base64)
                                if (imagen.file && (imagen.file instanceof Blob || imagen.file instanceof File) && imagen.tipo === 'paso4') {
                                    const fieldName = `reflectivo[imagenes_paso4][${prendaIndex}][${imagenIndex}]`;
                                    console.log(` APPENDING IMAGE TO FORMDATA - Prenda ${prendaIndex}, Imagen ${imagenIndex}: ${imagen.file.name}`);
                                    formData.append(fieldName, imagen.file);
                                    totalImagenesP4Reflectivo++;
                                } else if (imagen.tipo === 'paso2') {
                                    // ✅ Las imágenes del PASO 2 NO se envían como archivos, solo se guardan la referencia
                                    // Estas ya están en la BD desde el PASO 2
                                    console.log(` Imagen del PASO 2 detectada (no se envía): ${imagen.preview?.substring(0, 50)}...`);
                                }
                            });
                        }
                    });
                    console.log(` TOTAL IMÁGENES PASO 4 AGREGADAS AL FORMDATA: ${totalImagenesP4Reflectivo}`);
                }
                
                if (window.imagenesReflectivo && Array.isArray(window.imagenesReflectivo)) {

                    window.imagenesReflectivo.forEach((imagen) => {
                        if (imagen.archivo && imagen.archivo instanceof File) {
                            formData.append(`reflectivo[imagenes][]`, imagen.archivo);

                        }
                    });
                } else {

                }
            } else {

            }
            
            //  IMÁGENES DEL REFLECTIVO
            if (window.imagenesReflectivo && Array.isArray(window.imagenesReflectivo)) {

                
                window.imagenesReflectivo.forEach((imagen) => {
                    if (imagen.archivo && imagen.archivo instanceof File) {
                        formData.append(`reflectivo[imagenes][]`, imagen.archivo);

                    }
                });
            } else {

            }
        } else {

        }
        
        console.log('📤 FORMDATA A ENVIAR:', {
            tipo: 'borrador',
            cliente: datos.cliente,
            tipo_venta: tipoVenta,
            productos_count: datos.productos?.length || 0,
            tecnicas: datos.tecnicas?.length || 0,
            especificaciones_keys: Object.keys(datos.especificaciones || {}),
            ruta: window.routes.guardarCotizacion
        });
        
        // Debug: Mostrar contenido del FormData

        for (let pair of formData.entries()) {
            if (!pair[0].includes('[fotos]')) {  // Excluir archivos para no saturar el log

            }
        }
        
        // Verificar y obtener el token CSRF
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta?.getAttribute('content') || '';
        
        if (!csrfToken) {

            Swal.fire({
                title: 'Error de seguridad',
                html: '<p>No se encontró el token CSRF.</p><p style="font-size: 0.85rem; color: #999; margin-top: 10px;">Por favor, recarga la página.</p>',
                icon: 'error',
                confirmButtonColor: '#1e40af'
            });
            if (btnGuardar) btnGuardar.disabled = false;
            if (btnEnviar) btnEnviar.disabled = false;
            return;
        }
        

        const response = await fetch(window.routes.guardarCotizacion, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
                //  NO incluir 'Content-Type': 'application/json' - FormData lo establece automáticamente
            },
            body: formData
        });
        




        
        // Verificar errores de sesión/CSRF antes de parsear
        if (response.status === 419) {

            Swal.fire({
                title: 'Sesión expirada',
                html: '<p>Tu sesión ha expirado por inactividad.</p>' +
                      '<p style="margin-top: 10px;">Por favor, recarga la página para continuar.</p>',
                icon: 'warning',
                confirmButtonColor: '#1e40af',
                confirmButtonText: 'Recargar página'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
            if (btnGuardar) btnGuardar.disabled = false;
            if (btnEnviar) btnEnviar.disabled = false;
            return;
        }
        
        if (response.status === 401) {

            Swal.fire({
                title: 'Sesión no válida',
                html: '<p>Debes iniciar sesión para continuar.</p>',
                icon: 'error',
                confirmButtonColor: '#1e40af',
                confirmButtonText: 'Ir al inicio'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/';
                }
            });
            if (btnGuardar) btnGuardar.disabled = false;
            if (btnEnviar) btnEnviar.disabled = false;
            return;
        }
        
        const responseText = await response.text();

        
        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {


            
            Swal.fire({
                title: 'Error del servidor',
                html: '<p>El servidor retornó una respuesta inválida.</p><p style="font-size: 0.8rem; color: #999; margin-top: 10px; word-break: break-all;">' + 
                      responseText.substring(0, 300) + '</p>',
                icon: 'error',
                confirmButtonColor: '#1e40af'
            });
            if (btnGuardar) btnGuardar.disabled = false;
            if (btnEnviar) btnEnviar.disabled = false;
            return;
        }
        
        if (data.success && (data.cotizacion_id !== undefined || (data.data && data.data.id !== undefined))) {
            const cotizacionId = data.cotizacion_id !== undefined ? data.cotizacion_id : (data.data && data.data.id);

            
            //  GUARDAR EL ID PARA USOS POSTERIORES
            window.cotizacionIdActual = cotizacionId;

            

            
            //  LIMPIAR TODO DESPUÉS DEL GUARDADO EXITOSO
            if (typeof limpiarFormularioCompleto === 'function') {
                limpiarFormularioCompleto();
            } else if (typeof limpiarStorage === 'function') {
                limpiarStorage();

            }
            
            //  CERRAR el modal de "Guardando..." primero
            Swal.close();
            
            //  Mostrar toast de éxito
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '¡Cotización guardada en borradores!',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            
            // Redirigir a la vista de borradores después de 2 segundos
            setTimeout(() => {
                window.location.href = '/asesores/cotizaciones?tab=borradores';
            }, 2000);
            
            if (btnGuardar) btnGuardar.disabled = false;
            if (btnEnviar) btnEnviar.disabled = false;
            
            return true;  //  Retornar true para indicar éxito
        } else {
            // Construir mensaje de error detallado
            let mensajeError = data.message || 'Error desconocido';
            let htmlError = `<p>${mensajeError}</p>`;
            
            // Si hay errores de validación, mostrarlos
            if (data.validation_errors) {
                htmlError += '<div style="text-align: left; margin-top: 10px;">';
                for (const [campo, errores] of Object.entries(data.validation_errors)) {
                    if (Array.isArray(errores)) {
                        errores.forEach(error => {
                            htmlError += `<p style="margin: 5px 0; font-size: 0.9rem;"><strong>${campo}:</strong> ${error}</p>`;
                        });
                    }
                }
                htmlError += '</div>';
            }
            

            
            Swal.fire({
                title: 'Error al guardar',
                html: htmlError,
                icon: 'error',
                confirmButtonColor: '#1e40af',
                width: '600px'
            });
            
            if (btnGuardar) btnGuardar.disabled = false;
            if (btnEnviar) btnEnviar.disabled = false;
            
            return false;  //  Retornar false para indicar error
        }
    } catch (error) {

        Swal.fire({
            title: 'Error de conexión',
            html: `<p>No se pudo completar la solicitud:</p>
                   <p style="font-size: 0.9rem; color: #d32f2f; margin-top: 10px;">${error.message}</p>`,
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnEnviar) btnEnviar.disabled = false;
        return false;  //  Retornar false para indicar error
    }
}

// ============ SUBIR IMÁGENES ============

async function subirImagenesAlServidor(cotizacionId, archivos, tipo) {

    
    const formData = new FormData();
    
    // Si es prenda y tenemos información de índice, usar eso
    if (tipo === 'prenda' && Array.isArray(archivos) && archivos.length > 0 && archivos[0].prendaIndex !== undefined) {
        archivos.forEach((item, index) => {
            formData.append('imagenes[]', item.file);
            formData.append(`prendaIndex[${index}]`, item.prendaIndex);
        });

    } 
    // Si es tela y tenemos información de índice, usar eso
    else if (tipo === 'tela' && Array.isArray(archivos) && archivos.length > 0 && archivos[0].prendaIndex !== undefined) {
        archivos.forEach((item, index) => {
            formData.append('imagenes[]', item.file);
            formData.append(`prendaIndex[${index}]`, item.prendaIndex);
        });

    } 
    // Para otros tipos, enviar normalmente
    else {
        archivos.forEach((file) => {
            formData.append('imagenes[]', file);
        });
    }
    
    formData.append('tipo', tipo);
    
    try {
        const response = await fetch(`/asesores/cotizaciones/${cotizacionId}/imagenes`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        const data = await response.json();
        if (data.success) {

        } else {

        }
    } catch (error) {

    }
}

// ============ ENVIAR COTIZACIÓN ============

async function enviarCotizacion() {

    
    //  Validar datos ANTES de mostrar el modal
    const datos = recopilarDatos();
    
    if (!datos) {
        Swal.fire({
            title: 'Error',
            text: 'No se pudieron recopilar los datos del formulario',
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
        return;
    }
    
    if (!datos.cliente.trim()) {
        Swal.fire({
            title: 'Campo requerido',
            text: 'Por favor ingresa el nombre del cliente',
            icon: 'warning',
            confirmButtonColor: '#1e40af'
        });
        return;
    }
    
    // Validar que el tipo de venta esté seleccionado
    const tipoVentaSelect = document.getElementById('tipo_venta');
    const tipoVenta = tipoVentaSelect ? tipoVentaSelect.value : '';
    
    if (!tipoVenta) {
        Swal.fire({
            title: 'Tipo de cotización requerido',
            text: 'Por favor selecciona el tipo de cotización (M/D/X)',
            icon: 'warning',
            confirmButtonColor: '#1e40af'
        });
        return;
    }
    
    if (datos.productos.length === 0) {
        Swal.fire({
            title: 'Productos requeridos',
            text: 'Por favor agrega al menos un producto',
            icon: 'warning',
            confirmButtonColor: '#1e40af'
        });
        return;
    }
    
    //  VALIDAR ESPECIFICACIONES
    const especificaciones = window.especificacionesSeleccionadas || {};
    const tieneEspecificaciones = Object.keys(especificaciones).length > 0;
    
    if (!tieneEspecificaciones) {
        // Marcar botón flotante en rojo como recordatorio
        const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
        if (btnEnviar) {
            btnEnviar.style.background = '#ef4444';
            btnEnviar.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.3)';
        }
        
        Swal.fire({
            title: ' ESPECIFICACIONES REQUERIDAS',
            html: `
                <div style="text-align: left; margin: 20px 0;">
                    <p style="margin: 0 0 15px 0; font-size: 1rem; color: #ef4444; font-weight: bold;">
                         No puedes enviar sin completar las especificaciones
                    </p>
                    <p style="margin: 0 0 15px 0; font-size: 0.9rem; color: #666;">
                        Las especificaciones son <strong>OBLIGATORIAS</strong> para que el cliente entienda todos los detalles de su pedido.
                    </p>
                    <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px; border-radius: 4px; margin: 15px 0;">
                        <p style="margin: 0 0 8px 0; font-size: 0.85rem; color: #991b1b; font-weight: bold;">
                             DEBES COMPLETAR AL MENOS UNA:
                        </p>
                        <p style="margin: 0; font-size: 0.85rem; color: #991b1b;">
                            ✓ Régimen<br>
                            ✓ Se ha vendido<br>
                            ✓ Última venta<br>
                            ✓ Flete de envío
                        </p>
                    </div>
                    <p style="margin: 15px 0 0 0; font-size: 0.9rem; color: #666;">
                        Haz clic en <strong>"Ir a Especificaciones"</strong> para completarlas ahora.
                    </p>
                </div>
            `,
            icon: 'error',
            showCancelButton: false,
            confirmButtonColor: '#3498db',
            confirmButtonText: '✓ Ir a Especificaciones',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Ir a PASO 2 automáticamente
                irAlPaso(2);
                
                // Abrir modal de especificaciones
                setTimeout(() => {
                    abrirModalEspecificaciones();
                }, 300);
                
                // Mostrar toast recordatorio
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: ' Completa las especificaciones y haz clic en GUARDAR',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true
                });
            }
        });
        return;
    }
    
    // Si hay especificaciones, cambiar botón a verde
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    if (btnEnviar) {
        btnEnviar.style.background = '';
        btnEnviar.style.boxShadow = '';
    }
    
    //  MOSTRAR CONFIRMACIÓN SIN GUARDAR PRIMERO
    Swal.fire({
        title: '¿Listo para enviar?',
        html: '<p style="margin: 0; font-size: 0.95rem; color: #4b5563;">Una vez enviada la cotización <span style="color: #ef4444; font-weight: 700;">no podrá editarse</span>.</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Revisar primero',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        if (result.isConfirmed) {
            //  SOLO SI CONFIRMA, GUARDAR Y LUEGO ENVIAR
            procederEnviarCotizacion();
        } else if (result.isDismissed) {
            // Usuario canceló o cerró el modal - no hacer nada

        }
    });
}

async function procederEnviarCotizacion() {
    const btnGuardar = document.querySelector('button[onclick="guardarCotizacion()"]');
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    
    if (btnGuardar) btnGuardar.disabled = true;
    if (btnEnviar) btnEnviar.disabled = true;
    

    
    //  GUARDAR PRIMERO COMO BORRADOR
    const guardadoExitoso = await guardarCotizacion();
    
    if (!guardadoExitoso) {

        Swal.fire({
            title: 'Error',
            text: 'No se pudieron guardar los cambios. Por favor intenta de nuevo.',
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnEnviar) btnEnviar.disabled = false;
        return;
    }
    

    
    if (btnGuardar) btnGuardar.disabled = true;
    if (btnEnviar) btnEnviar.disabled = true;
    
    Swal.fire({
        title: 'Enviando...',
        html: '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;"><div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite 0.3s;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite 0.6s;"></div></div><style>@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }</style>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
    });
    
    //  Recopilar datos nuevamente para asegurar que están actualizados
    const datos = recopilarDatos();
    if (!datos) {
        Swal.fire({
            title: 'Error',
            text: 'No se pudieron recopilar los datos del formulario',
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnEnviar) btnEnviar.disabled = false;
        return;
    }
    
    //  NO convertir a Base64 - enviar archivos directamente como File objects
    // Base64 es ineficiente (aumenta tamaño 33%) y mala práctica

    
    // Obtener tipo de venta
    const tipoVentaSelect = document.getElementById('tipo_venta');
    const tipoVentaPaso3Select = document.getElementById('tipo_venta_paso3');
    const tipoVentaValue = tipoVentaSelect ? tipoVentaSelect.value : '';
    const tipoVentaPaso3Value = tipoVentaPaso3Select ? tipoVentaPaso3Select.value : '';
    
    // Obtener especificaciones (puede ser objeto o array)
    const especificaciones = window.especificacionesSeleccionadas || {};
    





    
    // LOG DETALLADO DE VARIANTES
    if (datos.productos && datos.productos.length > 0) {

        datos.productos.forEach((prod, idx) => {

        });
    }
    
    try {
        //  USAR FormData PARA ENVIAR ARCHIVOS File
        const formData = new FormData();
        
        // Datos básicos
        formData.append('tipo', 'enviada');           //  Identificar acción ENVIAR
        formData.append('accion', 'enviar');          // ← AGREGAR: Identificar acción ENVIAR
        formData.append('es_borrador', '0');          // ← AGREGAR: Marcar que NO es borrador
        
        // 🔑 CRÍTICO: Incluir el cotizacion_id si existe (para actualizar borrador existente)
        if (window.cotizacionIdActual) {
            formData.append('cotizacion_id', window.cotizacionIdActual);

        } else {

        }
        
        formData.append('cliente', datos.cliente);
        formData.append('tipo_venta', tipoVentaValue);
        formData.append('tipo_venta_paso3', tipoVentaPaso3Value);  // Enviar PASO 3 independiente
        formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
        
        // Secciones de texto
        formData.append('descripcion_logo', datos.descripcion_logo || '');
        formData.append('tecnicas', JSON.stringify(datos.tecnicas || []));
        formData.append('observaciones_tecnicas', datos.observaciones_tecnicas || '');
        formData.append('ubicaciones', JSON.stringify(datos.ubicaciones || []));
        formData.append('observaciones_generales', JSON.stringify(datos.observaciones_generales || []));
        
        // Enviar observaciones_check y observaciones_valor como arrays (no JSON strings)
        const obsCheck = datos.observaciones_check || [];
        const obsValor = datos.observaciones_valor || [];
        
        // Agregar cada elemento del array por separado
        obsCheck.forEach((item, idx) => {
            formData.append(`observaciones_check[${idx}]`, item || '');
        });
        obsValor.forEach((item, idx) => {
            formData.append(`observaciones_valor[${idx}]`, item || '');
        });
        
        formData.append('especificaciones', JSON.stringify(especificaciones || {}));
        formData.append('imagenes', JSON.stringify(datos.logo?.imagenes || []));
        
        //  PRENDAS CON ARCHIVOS File
        if (datos.productos && Array.isArray(datos.productos)) {
            datos.productos.forEach((producto, index) => {
                // Datos de prenda
                formData.append(`prendas[${index}][nombre_producto]`, producto.nombre_producto || '');
                formData.append(`prendas[${index}][descripcion]`, producto.descripcion || '');
                formData.append(`prendas[${index}][cantidad]`, producto.cantidad || 1);
                formData.append(`prendas[${index}][tallas]`, JSON.stringify(producto.tallas || []));
                
                // Variantes como array (no JSON string)
                const variantes = producto.variantes || {};
                Object.keys(variantes).forEach(key => {
                    const value = variantes[key];
                    if (key === 'telas_multiples' && Array.isArray(value)) {
                        // Caso especial: telas_multiples es un array de objetos
                        // Enviar como JSON string completo
                        formData.append(`prendas[${index}][variantes][${key}]`, JSON.stringify(value));
                    } else if (Array.isArray(value)) {
                        // Si es array (pero no telas_multiples), agregar cada elemento
                        value.forEach((item, idx) => {
                            if (typeof item === 'object' && item !== null) {
                                formData.append(`prendas[${index}][variantes][${key}][${idx}]`, JSON.stringify(item));
                            } else {
                                formData.append(`prendas[${index}][variantes][${key}][${idx}]`, item);
                            }
                        });
                    } else if (typeof value === 'object' && value !== null) {
                        // Si es objeto, convertir a JSON string
                        formData.append(`prendas[${index}][variantes][${key}]`, JSON.stringify(value));
                    } else if (typeof value === 'boolean') {
                        // Convertir booleanos a 1/0 para Laravel
                        formData.append(`prendas[${index}][variantes][${key}]`, value ? '1' : '0');
                    } else {
                        // Si es valor simple, agregar directamente
                        formData.append(`prendas[${index}][variantes][${key}]`, value || '');
                    }
                });
                
                //  FOTOS DE PRENDA - EN ENVÍO: SIEMPRE ENVIAR TODAS (no omitir guardadas)
                if (window.imagenesEnMemoria && window.imagenesEnMemoria.prendaConIndice) {
                    const fotosDeEstaPrenda = window.imagenesEnMemoria.prendaConIndice.filter(p => p.prendaIndex === index);
                    const fotosNuevas = [];
                    const fotosExistentes = [];
                    
                    fotosDeEstaPrenda.forEach((item, fotoIndex) => {
                        if (item.file instanceof File) {
                            // 🔑 CRÍTICO: Cuando se ENVÍA, se envían TODAS las fotos nuevas (File objects)
                            fotosNuevas.push(item.file);

                        } else if (item.fotoId && typeof item.file === 'string') {
                            // ES UNA FOTO YA GUARDADA (con URL string) - GUARDAR SU ID para que backend la copie
                            fotosExistentes.push(item.fotoId);

                        }
                    });
                    
                    // Enviar fotos nuevas al FormData
                    fotosNuevas.forEach((foto) => {
                        formData.append(`prendas[${index}][fotos][]`, foto);
                    });
                    
                    // Enviar IDs de fotos existentes para que backend las copie - SOLO EN CREAR, NO EN UPDATE
                    // En UPDATE, no enviar IDs porque ya existen en la prenda y crearían duplicados
                    if (fotosExistentes.length > 0 && !window.cotizacionIdActual) {
                        formData.append(`prendas[${index}][fotos_existentes]`, JSON.stringify(fotosExistentes));

                    } else if (fotosExistentes.length > 0 && window.cotizacionIdActual) {

                    }
                }
                
                //   TELAS YA FUERON GUARDADAS EN guardarCotizacion()
                // Las telas se procesaron y guardaron en la BD durante guardarCotizacion()
                // NO RE-PROCESAR aquí para evitar DUPLICACIÓN



            });
        }
        
        //  LOGO - IMÁGENES (File objects desde imagenesEnMemoria + rutas guardadas desde DOM)
        if (window.imagenesEnMemoria && window.imagenesEnMemoria.logo && Array.isArray(window.imagenesEnMemoria.logo)) {

            
            window.imagenesEnMemoria.logo.forEach((imagen, imagenIndex) => {
                if (imagen instanceof File) {
                    // Es un File object nuevo
                    formData.append(`logo[imagenes][]`, imagen);

                } else if (imagen.esGuardada && imagen.ruta) {
                    // Es una imagen guardada en BD - enviar la ruta para conservarla
                    formData.append(`logo_fotos_guardadas[]`, imagen.ruta);

                }
            });
        }
        
        //  LOGO - FOTOS GUARDADAS EN BD DESDE DOM (por si acaso no estén en memory)
        // Estas son las imágenes que ya están guardadas en BD y necesitan ser conservadas
        const galeriaImagenes = document.getElementById('galeria_imagenes');
        if (galeriaImagenes) {
            const fotosExistentes = galeriaImagenes.querySelectorAll('[data-foto-guardada="true"]');
            if (fotosExistentes.length > 0) {

                fotosExistentes.forEach((div, idx) => {
                    const img = div.querySelector('img');
                    const ruta = img ? img.getAttribute('data-ruta') : null;
                    if (ruta && !ruta.includes('data:image')) {
                        // Enviar la ruta para que el backend sepa cuál conservar
                        formData.append(`logo_fotos_guardadas[]`, ruta);

                    }
                });
            } else {

            }
        } else {

        }
        
        //  TÉCNICAS DE LOGO (PASO 3) - Para cotizaciones combinadas (PL) EN ENVÍO
        // Las técnicas se guardan en window.tecnicasAgregadasPaso3


        
        if (window.tecnicasAgregadasPaso3 && Array.isArray(window.tecnicasAgregadasPaso3) && window.tecnicasAgregadasPaso3.length > 0) {
            //  VALIDAR que las técnicas tengan información válida (ubicaciones + tallas/imágenes)
            const tieneInfoValida = tienenInformacionValida(window.tecnicasAgregadasPaso3);
            
            if (!tieneInfoValida) {

            } else {

                
                // Enviar técnicas con toda su información (prendas, ubicaciones, tallas, etc)
                formData.append('logo[tecnicas_agregadas]', JSON.stringify(window.tecnicasAgregadasPaso3));
                
                console.log(' Técnicas agregadas al FormData en envío:', {
                    count: window.tecnicasAgregadasPaso3.length,
                    tecnicas_json: JSON.stringify(window.tecnicasAgregadasPaso3).substring(0, 200) + '...'
                });
            }
        } else {

        }
        
        //  REFLECTIVO (PASO 4) - Para cotizaciones combinadas (PL)
        // Solo procesar si el tipo de cotización incluye reflectivo Y hay información válida


        
        // Tipo PL/PB significa que PUEDE tener reflectivo
        if (window.tipoCotizacionGlobal === 'PL' || window.tipoCotizacionGlobal === 'PB' || window.tipoCotizacionGlobal === 'RF') {

            
            //  ACTUALIZAR window.prendas_reflectivo_paso4 DESDE EL DOM
            // Esta función captura los datos actuales de la UI en el PASO 4
            if (typeof capturePrendasReflectivoPaso4 === 'function') {
                const prendasCapturadas = capturePrendasReflectivoPaso4();
                // Reconstruir window.prendas_reflectivo_paso4 con los datos capturados
                window.prendas_reflectivo_paso4 = prendasCapturadas.map((prenda, idx) => ({
                    index: idx,
                    tipo_prenda: prenda.tipo_prenda,
                    descripcion: prenda.descripcion,
                    ubicaciones: prenda.ubicaciones || [],
                    tallas: prenda.tallas || [],
                    variaciones: prenda.variaciones || {},
                    observaciones_generales: prenda.observaciones_generales || [],
                    imagenes: prenda.imagenes || []
                }));

            }
            
            // Obtener descripción del reflectivo (PASO 4) - garantizar que sea string, no null
            const reflectivoElement = document.getElementById('descripcion_reflectivo');
            const reflectivoDescripcion = (reflectivoElement?.value || '').trim();
            
            //  IMPORTANTE: Obtener ubicaciones desde prendas_reflectivo_paso4 (nuevo modelo)
            // Si NO existe esa variable, fallback a window.ubicacionesReflectivo (compatibilidad)
            let ubicacionesReflectivo = [];
            
            if (typeof window.prendas_reflectivo_paso4 !== 'undefined' && window.prendas_reflectivo_paso4.length > 0) {
                // Reunir TODAS las ubicaciones de TODAS las prendas

                
                window.prendas_reflectivo_paso4.forEach((prenda, idx) => {
                    if (prenda.ubicaciones && prenda.ubicaciones.length > 0) {

                        ubicacionesReflectivo.push(...prenda.ubicaciones);
                    }
                });
                

            } else if (typeof window.ubicacionesReflectivo !== 'undefined') {
                // Fallback: usar la versión antigua

                ubicacionesReflectivo = window.ubicacionesReflectivo || [];
            }
            
            // Obtener observaciones generales del reflectivo (si existen)
            const observacionesReflectivo = window.observacionesReflectivo || [];
            
            //  VALIDAR que reflectivo tenga información escrita válida
            // Solo incluir si hay ubicaciones O descripción + tallas/imágenes
            const tieneUbicacionesReflectivo = ubicacionesReflectivo && ubicacionesReflectivo.length > 0;
            const tieneDescripcionReflectivo = reflectivoDescripcion && reflectivoDescripcion.length > 0;
            const tieneImagenesReflectivo = window.imagenesReflectivo && window.imagenesReflectivo.length > 0;
            
            //  IMPORTANTE: También verificar directamente prendas_reflectivo_paso4
            const tienePrendasP4ConDatos = typeof window.prendas_reflectivo_paso4 !== 'undefined' && 
                                            window.prendas_reflectivo_paso4.length > 0;
            
            const refletivoTieneInfoValida = tieneUbicacionesReflectivo || 
                                            (tieneDescripcionReflectivo && tieneImagenesReflectivo) ||
                                            tienePrendasP4ConDatos;  //  Agregar esta condición
            
            console.log(' Reflectivo capturado (PASO GUARDADO):', {
                elemento_existe: !!reflectivoElement,
                valor_raw: reflectivoElement?.value,
                valor_final: reflectivoDescripcion,
                ubicaciones_raw: ubicacionesReflectivo,
                ubicaciones_count: ubicacionesReflectivo.length,
                observaciones_count: observacionesReflectivo.length,
                tienePrendasP4ConDatos,
                tieneUbicacionesReflectivo,
                tieneDescripcionReflectivo,
                tieneImagenesReflectivo,
                tieneInfoValida: refletivoTieneInfoValida,
                ubicaciones_stringified: JSON.stringify(ubicacionesReflectivo)
            });
            
            // SOLO agregar reflectivo a FormData si tiene información válida
            if (refletivoTieneInfoValida) {
                formData.append('reflectivo[descripcion]', reflectivoDescripcion);
                formData.append('reflectivo[ubicacion]', JSON.stringify(ubicacionesReflectivo));
                formData.append('reflectivo[observaciones_generales]', JSON.stringify(observacionesReflectivo));
                formData.append('ubicaciones_reflectivo', JSON.stringify(ubicacionesReflectivo));
                
                //  AGREGAR DATOS COMPLETOS DE PRENDAS DEL PASO 4
                // Esto permite al backend guardar ubicaciones específicas para cada prenda
                if (typeof window.prendas_reflectivo_paso4 !== 'undefined' && window.prendas_reflectivo_paso4.length > 0) {
                    formData.append('prendas_reflectivo_paso4', JSON.stringify(window.prendas_reflectivo_paso4));

                }
                //  IMÁGENES DEL REFLECTIVO - solo si hay información válida
                if (window.imagenesReflectivo && Array.isArray(window.imagenesReflectivo)) {

                    
                    window.imagenesReflectivo.forEach((imagen, index) => {
                        if (imagen.archivo && imagen.archivo instanceof File) {
                            formData.append(`reflectivo[imagenes][]`, imagen.archivo);

                        }
                    });
                } else {

                }
            } else {

            }
            
            //  IMÁGENES DEL REFLECTIVO
            if (window.imagenesReflectivo && Array.isArray(window.imagenesReflectivo)) {

                
                window.imagenesReflectivo.forEach((imagen, index) => {
                    if (imagen.archivo && imagen.archivo instanceof File) {
                        formData.append(`reflectivo[imagenes][]`, imagen.archivo);

                    }
                });
            } else {

            }
        } else {

        }
        
        
        console.log('📤 FORMDATA A ENVIAR:', {
            tipo: 'enviada',
            cliente: datos.cliente,
            tipo_venta: tipoVentaValue,
            productos_count: datos.productos?.length || 0,
            tecnicas: datos.tecnicas?.length || 0,
            especificaciones_keys: Object.keys(especificaciones || {})
        });
        
        const response = await fetch(window.routes.guardarCotizacion, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                //  NO incluir 'Content-Type': 'application/json' - FormData lo establece automáticamente
            },
            body: formData
        });
        


        
        const responseText = await response.text();

        
        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {


            
            Swal.fire({
                title: 'Error del servidor',
                html: '<p>El servidor retornó una respuesta inválida.</p><p style="font-size: 0.8rem; color: #999; margin-top: 10px; word-break: break-all;">' + 
                      responseText.substring(0, 300) + '</p>',
                icon: 'error',
                confirmButtonColor: '#1e40af'
            });
            if (btnGuardar) btnGuardar.disabled = false;
            if (btnEnviar) btnEnviar.disabled = false;
            return;
        }
        
        if (data.success && (data.cotizacion_id !== undefined || (data.data && data.data.id !== undefined))) {
            const cotizacionId = data.cotizacion_id !== undefined ? data.cotizacion_id : (data.data && data.data.id);


            
            //  LIMPIAR TODO DESPUÉS DEL ENVÍO EXITOSO
            if (typeof limpiarFormularioCompleto === 'function') {
                limpiarFormularioCompleto();
            } else if (typeof limpiarStorage === 'function') {
                limpiarStorage();

            }
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '¡Cotización enviada!',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            
            setTimeout(() => {
                // Redirigir a la vista de cotizaciones
                window.location.href = '/asesores/cotizaciones?tab=cotizaciones';
            }, 2000);
        } else {
            // Construir mensaje de error detallado
            let mensajeError = data.message || 'Error desconocido';
            let htmlError = `<p>${mensajeError}</p>`;
            
            // Si hay errores de validación, mostrarlos
            if (data.validation_errors) {
                htmlError += '<div style="text-align: left; margin-top: 10px;">';
                for (const [campo, errores] of Object.entries(data.validation_errors)) {
                    if (Array.isArray(errores)) {
                        errores.forEach(error => {
                            htmlError += `<p style="margin: 5px 0; font-size: 0.9rem;"><strong>${campo}:</strong> ${error}</p>`;
                        });
                    }
                }
                htmlError += '</div>';
            }
            

            
            Swal.fire({
                title: 'Error al enviar',
                html: htmlError,
                icon: 'error',
                confirmButtonColor: '#1e40af',
                width: '600px'
            });
        }
    } catch (error) {

        Swal.fire({
            title: 'Error de conexión',
            html: `<p>No se pudo completar la solicitud:</p>
                   <p style="font-size: 0.9rem; color: #d32f2f; margin-top: 10px;">${error.message}</p>`,
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
    }
}

// ============ TOGGLE APLICA/NO APLICA ============

function toggleAplicaPaso(paso, btn) {
    const isAplica = btn.textContent.trim() === 'APLICA';
    
    if (isAplica) {
        // Cambiar a "NO APLICA"
        btn.textContent = 'NO APLICA';
        btn.style.background = '#ffc107';
        btn.style.color = '#333';
        
        // Ir al siguiente paso
        if (paso === 2) {
            irAlPaso(3);
        } else if (paso === 3) {
            irAlPaso(4);
        }
    } else {
        // Cambiar a "APLICA"
        btn.textContent = 'APLICA';
        btn.style.background = '#10b981';
        btn.style.color = 'white';
    }
}

// ============ INICIALIZACIÓN DE VALIDACIÓN DE TIPO DE VENTA ============

document.addEventListener('DOMContentLoaded', function() {
    // Obtener elementos
    const tipoVentaSelect = document.getElementById('tipo_venta');
    const btnGuardar = document.querySelector('button[onclick="guardarCotizacion()"]');
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    
    // Función para actualizar estado de botones
    function actualizarEstadoBotones() {
        const tipoSeleccionado = tipoVentaSelect && tipoVentaSelect.value;
        const deshabilitado = !tipoSeleccionado;
        
        if (btnGuardar) {
            btnGuardar.disabled = deshabilitado;
            btnGuardar.style.opacity = deshabilitado ? '0.5' : '1';
            btnGuardar.style.cursor = deshabilitado ? 'not-allowed' : 'pointer';
            btnGuardar.title = deshabilitado ? 'Selecciona un tipo de cotización (M, D, X) para continuar' : '';
        }
        
        if (btnEnviar) {
            btnEnviar.disabled = deshabilitado;
            btnEnviar.style.opacity = deshabilitado ? '0.5' : '1';
            btnEnviar.style.cursor = deshabilitado ? 'not-allowed' : 'pointer';
            btnEnviar.title = deshabilitado ? 'Selecciona un tipo de cotización (M, D, X) para continuar' : '';
        }
    }
    
    // Deshabilitar botones inicialmente
    if (tipoVentaSelect) {
        actualizarEstadoBotones();
        
        // Escuchar cambios en el select
        tipoVentaSelect.addEventListener('change', actualizarEstadoBotones);
    }
});

