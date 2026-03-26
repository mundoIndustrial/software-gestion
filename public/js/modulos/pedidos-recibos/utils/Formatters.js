/**
 * Formatters.js
 * Formatea descripciones de prendas y datos para los recibos
 */

export class Formatters {
    /**
     * Construir descripción de COSTURA dinámicamente
     * Formato especializado para recibos de costura
     */
    static construirDescripcionCostura(prenda) {
        console.log('[Formatters.construirDescripcionCostura] 🎯 INPUT recibido:', prenda);
        console.log('[Formatters.construirDescripcionCostura] nombre:', prenda?.nombre);
        console.log('[Formatters.construirDescripcionCostura] tela:', prenda?.tela);
        console.log('[Formatters.construirDescripcionCostura] color:', prenda?.color);
        console.log('[Formatters.construirDescripcionCostura] manga:', prenda?.manga);
        console.log('[Formatters.construirDescripcionCostura] descripcion:', prenda?.descripcion);
        console.log('[Formatters.construirDescripcionCostura] variantes:', prenda?.variantes);
        console.log('[Formatters.construirDescripcionCostura] tallas:', prenda?.tallas);
        
        //  DEBUG: Verificar de_bodega y procesos
        console.log('[Formatters.construirDescripcionCostura]  de_bodega:', prenda?.de_bodega);
        console.log('[Formatters.construirDescripcionCostura]  procesos:', prenda?.procesos);

        // ⭐ DEBUG: Información detallada de variantes
        if (prenda.variantes && Array.isArray(prenda.variantes)) {
            console.log('[Formatters]  Variantes cantidad:', prenda.variantes.length);
            prenda.variantes.forEach((v, idx) => {
                console.log(`[Formatters]  Variante ${idx}:`, v);
                console.log(`[Formatters]  Variante ${idx} - manga:`, v.manga);
                console.log(`[Formatters]  Variante ${idx} - manga_obs:`, v.manga_obs);
                console.log(`[Formatters]  Variante ${idx} - bolsillos_obs:`, v.bolsillos_obs);
                console.log(`[Formatters]  Variante ${idx} - broche_obs:`, v.broche_obs);
                console.log(`[Formatters]  Variante ${idx} - broche:`, v.broche);
                console.log(`[Formatters]  Variante ${idx} - boton_obs:`, v.boton_obs);
                // 🎯 DEBUG: Mostrar todos los campos disponibles
                console.log(`[Formatters] 🔑 Todas las claves en Variante ${idx}:`, Object.keys(v));
            });
        }

        
        const lineas = [];

        // 1. Nombre de la prenda
        if (prenda.nombre) {
            const numeroPrenda = prenda.numero || 1;
            lineas.push(`<strong style="font-size: 13.4px;">PRENDA ${numeroPrenda}: ${prenda.nombre.toUpperCase()}</strong>`);
        }

        // 2. Línea técnica - Manejo de múltiples telas
        const partes = [];
        
        // Verificar si hay múltiples telas
        if (prenda.telas_array && Array.isArray(prenda.telas_array) && prenda.telas_array.length > 0) {
            console.log('[Formatters]  Múltiples telas encontradas:', prenda.telas_array.length);
            
            // Construir string con todas las telas y colores
            const telasInfo = prenda.telas_array
                .filter(t => t.tela_nombre || t.color_nombre) // Filtrar telas válidas
                .map(t => {
                    const tela = t.tela_nombre || '';
                    const rawColor = t.color_nombre || '';
                    // Si el color es "Sin color" o vacío, usar "-"
                    const color = (!rawColor || rawColor.toLowerCase() === 'sin color') ? '' : rawColor;
                    const ref = t.referencia ? ` | REF: ${t.referencia}` : '';
                    if (tela && color) {
                        return `${tela} / ${color}${ref}`;
                    } else if (tela) {
                        return `${tela}${ref}`;
                    } else if (color) {
                        return `${color}${ref}`;
                    }
                    return '';
                })
                .filter(t => t) // Filtrar strings vacíos
                .join(' | ');
            
            if (telasInfo) {
                partes.push(`<strong>TELAS:</strong> ${telasInfo.toUpperCase()}`);
                console.log('[Formatters]  Telas múltiples agregadas:', telasInfo);
            }
        } else if (prenda.tela && prenda.color) {
            // Fallback: si solo hay una tela
            partes.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
            partes.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
            if (prenda.ref) partes.push(`<strong>REF:</strong> ${prenda.ref.toUpperCase()}`);
            console.log('[Formatters]  Usando tela única (fallback)');
        } else if (prenda.tela) {
            partes.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
            if (prenda.color) partes.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
            if (prenda.ref) partes.push(`<strong>REF:</strong> ${prenda.ref.toUpperCase()}`);
        }
        
        // Manga desde variantes
        console.log('[Formatters]  Buscando manga en variantes...');
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const primerVariante = prenda.variantes[0];
            console.log('[Formatters]  Primer variante:', primerVariante);
            console.log('[Formatters]  manga en variante:', primerVariante.manga);
            console.log('[Formatters]  manga_obs en variante:', primerVariante.manga_obs);
            
            // Intentar primero manga, luego manga_obs
            let mangaTexto = primerVariante.manga || primerVariante.manga_obs;
            
            if (mangaTexto) {
                mangaTexto = mangaTexto.toUpperCase();
                // Agregar observaciones si existen
                let mangaConObs = mangaTexto;
                if (primerVariante.manga_obs && primerVariante.manga_obs.trim()) {
                    mangaConObs += ` (${primerVariante.manga_obs.toUpperCase()})`;
                }
                partes.push(`<strong>MANGA:</strong> ${mangaConObs}`);
                console.log('[Formatters]  MANGA agregado:', mangaConObs);
            } else {
                console.log('[Formatters]  No hay manga en variante');
            }
        } else {
            console.log('[Formatters]  No hay variantes');
        }
        
        if (partes.length > 0) {
            lineas.push(partes.join(' | '));
        }

        // 3. Descripción base - pegada a la línea técnica sin título
        if (prenda.descripcion && prenda.descripcion.trim()) {
            let desc = prenda.descripcion.toUpperCase().trim();
            
            // Filtrar líneas de basura: solo texto aleatorio de 5+ letras sin espacios
            desc = desc.split('\n')
                .map(linea => linea.trim())
                .filter(linea => {
                    if (!linea) return false;
                    // Si es solo DSFSDFS o similar (5+ letras sin espacios, sin palabras conocidas)
                    if (linea.match(/^[A-Z]{5,}$/) && !linea.match(/^(PRENDA|TALLA|TELA|COLOR|MANGA|BOLSILLO|BOTÓN|BROCHE|CREMALLERA|DAMA|HOMBRE)/)) {
                        console.log('[Formatters] 🚫 Filtrando línea basura:', linea);
                        return false;
                    }
                    return true;
                })
                .join('\n');
            
            if (desc.trim()) {
                lineas.push(desc);
                console.log('[Formatters]  Descripción agregada (después de limpiar)');
            }
        }

        // 4. Detalles técnicos
        const detalles = [];
        console.log('[Formatters]  Buscando detalles en variantes...');
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const primerVariante = prenda.variantes[0];
            console.log('[Formatters]  Detalles disponibles en variante:', {
                bolsillos_obs: primerVariante.bolsillos_obs,
                tiene_bolsillos: primerVariante.tiene_bolsillos,
                broche_boton_obs: primerVariante.broche_boton_obs,
                tipo_broche_boton_id: primerVariante.tipo_broche_boton_id,
                manga_obs: primerVariante.manga_obs,
                tipo_manga_id: primerVariante.tipo_manga_id
            });
            
            // BOLSILLOS - Mostrar si tiene_bolsillos=true O si existe observación
            if (primerVariante.tiene_bolsillos === true || primerVariante.tiene_bolsillos === 1 || (primerVariante.bolsillos_obs && primerVariante.bolsillos_obs.trim())) {
                const obsTexto = primerVariante.bolsillos_obs && primerVariante.bolsillos_obs.trim() ? primerVariante.bolsillos_obs.toUpperCase() : '';
                if (obsTexto) {
                    detalles.push(`• <strong>BOLSILLOS:</strong> ${obsTexto}`);
                } else {
                    detalles.push(`• <strong>BOLSILLOS:</strong>`);
                }
                console.log('[Formatters]  BOLSILLOS agregados');
            }
            
            // BROCHE/BOTÓN - Mostrar si tipo_broche_boton_id existe O si existe observación
            // Chequear ambos campos de observación (broche_obs y broche_boton_obs)
            const brocheObs = (primerVariante.broche_obs && primerVariante.broche_obs.trim()) || 
                              (primerVariante.broche_boton_obs && primerVariante.broche_boton_obs.trim());
            
            if (primerVariante.tipo_broche_boton_id || brocheObs) {
                let etiqueta = primerVariante.broche || 'BROCHE/BOTÓN';
                if (etiqueta.toLowerCase().includes('botón')) {
                    etiqueta = 'BOTÓN';
                } else if (etiqueta.toLowerCase().includes('broche')) {
                    etiqueta = 'BROCHE';
                }
                
                if (brocheObs) {
                    detalles.push(`• <strong>${etiqueta}:</strong> ${brocheObs.toUpperCase()}`);
                } else {
                    detalles.push(`• <strong>${etiqueta}:</strong>`);
                }
                console.log('[Formatters]  BROCHE/BOTÓN agregado:', etiqueta);
            }
        }
        
        if (detalles.length > 0) {
            detalles.forEach((detalle) => {
                lineas.push(detalle);
            });
        }

        // 5. Tallas / Sobremedida
        // Regla:
        // - Si existen tallas con es_sobremedida => mostrar SOLO la sección SOBREMIDIDA (agrupada por género)
        // - Si NO existen => mantener render normal de TALLAS (como antes)
        console.log('[Formatters]  Procesando tallas/sobremedida...');

        const variantes = Array.isArray(prenda?.variantes) ? prenda.variantes : [];
        const sobremedidas = variantes.filter(v => v && (v.es_sobremedida === true || v.es_sobremedida === 1));

        if (sobremedidas.length > 0) {
            const porGenero = sobremedidas.reduce((acc, v) => {
                const genero = String(v.genero || 'SIN_GENERO').toUpperCase();
                const cantidad = parseInt(v.cantidad, 10) || 0;
                acc[genero] = (acc[genero] || 0) + cantidad;
                return acc;
            }, {});

            const ordenGeneros = ['CABALLERO', 'DAMA', 'UNISEX', 'GENERICO', 'SIN_GENERO'];
            const generos = Object.keys(porGenero)
                .sort((a, b) => {
                    const ia = ordenGeneros.indexOf(a);
                    const ib = ordenGeneros.indexOf(b);
                    if (ia === -1 && ib === -1) return a.localeCompare(b, 'es');
                    if (ia === -1) return 1;
                    if (ib === -1) return -1;
                    return ia - ib;
                });

            lineas.push('');
            lineas.push('<strong>SOBREMEDIDA:</strong>');
            generos.forEach((g) => {
                const qty = porGenero[g] || 0;
                if (qty > 0) {
                    lineas.push(`${g}: <span style="color: #d32f2f; font-weight: bold;">${qty}</span>`);
                }
            });
        } else {
            // Render normal de tallas (flujo anterior)
            console.log('[Formatters]  No hay sobremedida. Render normal de tallas...');
            console.log('[Formatters]  Tipo de tallas:', typeof prenda.tallas, 'Es array:', Array.isArray(prenda.tallas));
            console.log('[Formatters]  🎨 talla_colores disponible:', !!prenda.talla_colores, 'es array:', Array.isArray(prenda.talla_colores));

            // Determinar qué estructura de tallas usar (prioridad: talla_colores > tallas)
            let tallasParaRenderizar = null;

            // Primero verificar si hay talla_colores (flujo 2 - tallas con colores)
            if (Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0) {
                console.log('[Formatters]  📊 Usando talla_colores (flujo 2 - tallas con colores)');
                tallasParaRenderizar = this._transformarTallaColoresAEstructura(prenda.talla_colores);
                console.log('[Formatters]  Estructura de tallas transformada:', tallasParaRenderizar);
            } else if (prenda.tallas) {
                // Fallback a prenda.tallas (flujo 1 - tallas simples)
                let tienesTallas = false;

                if (Array.isArray(prenda.tallas)) {
                    tienesTallas = prenda.tallas.length > 0;
                    console.log('[Formatters]  Tallas es ARRAY con', prenda.tallas.length, 'elementos');
                } else {
                    tienesTallas = Object.keys(prenda.tallas).length > 0;
                    console.log('[Formatters]  Tallas es OBJETO con', Object.keys(prenda.tallas).length, 'claves');
                }

                if (tienesTallas) {
                    console.log('[Formatters]  📊 Usando tallas (flujo 1 - tallas simples)');
                    tallasParaRenderizar = prenda.tallas;
                }
            }

            if (tallasParaRenderizar) {
                console.log('[Formatters]  Tallas encontradas');
                lineas.push('');
                lineas.push('<strong>TALLAS</strong>');
                this._agregarTallasFormato(lineas, tallasParaRenderizar, prenda.genero, prenda);
            } else {
                console.log('[Formatters]  No hay tallas (vacío)');
            }
        }

        // 6. REFLECTIVO - Si existe un proceso reflectivo, agregarlo después de tallas
        // SOLO si la prenda NO es de bodega (de_bodega = false)
        console.log('[Formatters]  Verificando REFLECTIVO:', {
            de_bodega: prenda.de_bodega,
            no_es_de_bodega: !prenda.de_bodega,
            tieneProces: !!prenda.procesos,
            esArray: Array.isArray(prenda.procesos),
            cantidadProcesos: prenda.procesos?.length || 0
        });
        
        if (!prenda.de_bodega && prenda.procesos && Array.isArray(prenda.procesos)) {
            console.log('[Formatters]  Condición de REFLECTIVO cumplida (de_bodega = false), buscando proceso...');
            
            // DEBUG: Ver estructura completa del proceso
            if (prenda.procesos.length > 0) {
                console.log('[Formatters]  Proceso completo objeto:', prenda.procesos[0]);
                console.log('[Formatters]  Claves del proceso:', Object.keys(prenda.procesos[0]));
            }
            
            const procesoReflectivo = prenda.procesos.find(p => {
                const nombreProceso = (p.tipo_proceso || '').toLowerCase();
                console.log('[Formatters] 🔎 Analizando proceso:', {
                    tipo_proceso: p.tipo_proceso,
                    nombreProceso,
                    esReflectivo: nombreProceso === 'reflectivo'
                });
                return nombreProceso === 'reflectivo';
            });
            
            if (procesoReflectivo) {
                console.log('[Formatters]  Proceso REFLECTIVO encontrado:', procesoReflectivo);
                
                lineas.push('');
                lineas.push('<strong style="font-size: 13.4px;">PROCESO: REFLECTIVO</strong>');
                
                // Ubicaciones del reflectivo
                let ubicacionesArray = [];
                if (procesoReflectivo.ubicaciones) {
                    if (typeof procesoReflectivo.ubicaciones === 'string') {
                        try {
                            ubicacionesArray = JSON.parse(procesoReflectivo.ubicaciones);
                        } catch (e) {
                            console.warn('[Formatters] Error parseando ubicaciones reflectivo:', procesoReflectivo.ubicaciones, e);
                        }
                    } else if (Array.isArray(procesoReflectivo.ubicaciones)) {
                        ubicacionesArray = procesoReflectivo.ubicaciones;
                    }
                }
                
                // Crear layout en columnas para ubicaciones y observaciones
                let ubicacionesHTML = '';
                if (ubicacionesArray && ubicacionesArray.length > 0) {
                    ubicacionesHTML = '<span>UBICACIONES:</span><br>';
                    ubicacionesArray.forEach((ubicacion) => {
                        ubicacionesHTML += `• ${String(ubicacion).toUpperCase()}<br>`;
                    });
                }
                
                let observacionesHTML = '';
                if (procesoReflectivo.observaciones && procesoReflectivo.observaciones.trim()) {
                    observacionesHTML = '<strong>OBSERVACIONES:</strong><br>';
                    observacionesHTML += procesoReflectivo.observaciones.toUpperCase();
                }
                
                // Mostrar en columnas (lado a lado)
                if (ubicacionesHTML || observacionesHTML) {
                    lineas.push('<div style="display: flex; gap: 30px;">');
                    if (ubicacionesHTML) {
                        lineas.push(`<div style="flex: 1;">${ubicacionesHTML}</div>`);
                    }
                    if (observacionesHTML) {
                        lineas.push(`<div style="flex: 1;">${observacionesHTML}</div>`);
                    }
                    lineas.push('</div>');
                }
                
                // Tallas del reflectivo - SOLO si son diferentes a las de la prenda principal
                let tallasAMostrar = null;
                
                // Preferir tallas del proceso reflectivo si existen
                if (procesoReflectivo.tallas && Object.keys(procesoReflectivo.tallas).length > 0) {
                    tallasAMostrar = procesoReflectivo.tallas;
                } else if (prenda.tallas && Object.keys(prenda.tallas).length > 0) {
                    tallasAMostrar = prenda.tallas;
                }
                
                if (tallasAMostrar) {
                    // Comparar si son diferentes a las de la prenda
                    console.log('[Formatters]  Comparando tallas para REFLECTIVO:', {
                        tallasProceso: tallasAMostrar,
                        tallasPrenda: prenda.tallas,
                        de_bodega: prenda.de_bodega
                    });
                    
                    // Para el caso específico: si es reflectivo y de_bodega = false, 
                    // y las tallas parecen ser las mismas, omitir
                    const esCasoEspecifico = !prenda.de_bodega && 
                                          procesoReflectivo && 
                                          procesoReflectivo.tipo_proceso.toLowerCase() === 'reflectivo';
                    
                    let tallasIguales = false;
                    
                    if (esCasoEspecifico) {
                        // Comparación simplificada para el caso específico
                        tallasIguales = this._comparacionSimpleTallas(tallasAMostrar, prenda.tallas);
                        console.log('[Formatters] 🎯 Usando comparación simplificada para caso específico');
                    } else {
                        tallasIguales = this._sonTallasIguales(tallasAMostrar, prenda.tallas);
                    }
                    
                    console.log('[Formatters] 🎯 Resultado comparación tallas:', {
                        sonIguales: tallasIguales,
                        mostrarTallas: !tallasIguales,
                        esCasoEspecifico
                    });
                    
                    if (!tallasIguales) {
                        lineas.push('<strong>TALLAS</strong>');
                        this._agregarTallasFormato(lineas, tallasAMostrar, prenda.genero, prenda);
                        console.log('[Formatters]  Tallas del reflectivo son DIFERENTES, mostrando');
                    } else {
                        console.log('[Formatters]  Tallas del reflectivo son IGUALES a la prenda, omitiendo duplicado');
                    }
                }
                
                console.log('[Formatters]  Proceso REFLECTIVO agregado');
            } else {
                console.log('[Formatters]  No se encontró proceso REFLECTIVO en los procesos disponibles');
            }
        } else {
            console.log('[Formatters]  Condición de REFLECTIVO NO cumplida:', {
                razon: !prenda.de_bodega ? 'de_bodega es false (correcto)' : 'de_bodega es true (NO mostrar)',
                tieneProc: prenda.procesos ? 'sí' : 'no',
                esArray: Array.isArray(prenda.procesos) ? 'sí' : 'no'
            });
        }

        const resultado = lineas.join('<br>') || '<em>Sin información</em>';
        console.log('[Formatters.construirDescripcionCostura]  OUTPUT completo:', resultado);
        console.log('[Formatters.construirDescripcionCostura]  Cantidad de líneas:', lineas.length);
        return resultado;
    }

    /**
     * Construir descripción de PROCESO (bordado, estampado, dtf, etc.)
     * Formato específico para procesos productivos
     */
    static construirDescripcionProceso(prenda, proceso) {

        
        const lineas = [];

        // 1. Nombre de la prenda
        if (prenda.nombre) {
            lineas.push(`<strong style="font-size: 13.4px;">${prenda.nombre.toUpperCase()}</strong>`);
        }

        // 2. Línea técnica
        const partes = [];

        if (prenda.telas_array && Array.isArray(prenda.telas_array) && prenda.telas_array.length > 0) {
            const telasInfo = prenda.telas_array
                .filter(t => t.tela_nombre || t.color_nombre)
                .map(t => {
                    const tela = t.tela_nombre || '';
                    const rawColor = t.color_nombre || '';
                    const color = (!rawColor || rawColor.toLowerCase() === 'sin color') ? '' : rawColor;
                    const ref = t.referencia ? ` | REF: ${t.referencia}` : '';
                    if (tela && color) return `${tela} / ${color}${ref}`;
                    if (tela) return `${tela}${ref}`;
                    if (color) return `${color}${ref}`;
                    return '';
                })
                .filter(t => t)
                .join(' | ');

            if (telasInfo) {
                partes.push(`<strong>TELAS:</strong> ${telasInfo.toUpperCase()}`);
            }
        } else {
            if (prenda.tela) partes.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
            if (prenda.color) partes.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
            if (prenda.ref) partes.push(`<strong>REF:</strong> ${prenda.ref.toUpperCase()}`);
        }
        
        // Manga desde variantes
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const primerVariante = prenda.variantes[0];
            if (primerVariante.manga) {
                let mangaTexto = primerVariante.manga.toUpperCase();
                if (primerVariante.manga_obs && primerVariante.manga_obs.trim()) {
                    mangaTexto += ` (${primerVariante.manga_obs.toUpperCase()})`;
                }
                partes.push(`<strong>MANGA:</strong> ${mangaTexto}`);
            }
        }
        
        if (partes.length > 0) {
            lineas.push(partes.join(' | '));
        }

        // 2.5. Descripción de la prenda (antes de ubicaciones) - pegado a la línea técnica
        if (prenda.descripcion && prenda.descripcion.trim()) {
            lineas.push(prenda.descripcion);
        }

        // 2.6. Variaciones de prenda (antes de ubicaciones)
        // Incluye BOTÓN/BROCHE y BOLSILLOS desde prenda.variantes (chequea IDs, no solo observaciones)
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const primerVariante = prenda.variantes[0];
            const detalles = [];

            // BOLSILLOS - Mostrar si tiene_bolsillos=true O si existe observación
            if (primerVariante.tiene_bolsillos === true || primerVariante.tiene_bolsillos === 1 || (primerVariante.bolsillos_obs && primerVariante.bolsillos_obs.trim())) {
                const obsTexto = primerVariante.bolsillos_obs && primerVariante.bolsillos_obs.trim() ? primerVariante.bolsillos_obs.toUpperCase() : '';
                if (obsTexto) {
                    detalles.push(`• <strong>BOLSILLOS:</strong> ${obsTexto}`);
                } else {
                    detalles.push(`• <strong>BOLSILLOS:</strong>`);
                }
            }

            // BROCHE/BOTÓN - Mostrar si tipo_broche_boton_id existe O si existe observación
            // Chequear ambos campos de observación (broche_obs y broche_boton_obs)
            const brocheObs = (primerVariante.broche_obs && primerVariante.broche_obs.trim()) || 
                              (primerVariante.broche_boton_obs && primerVariante.broche_boton_obs.trim());
            
            if (primerVariante.tipo_broche_boton_id || brocheObs) {
                let etiqueta = primerVariante.broche || 'BROCHE/BOTÓN';
                if (etiqueta.toLowerCase().includes('botón')) {
                    etiqueta = 'BOTÓN';
                } else if (etiqueta.toLowerCase().includes('broche')) {
                    etiqueta = 'BROCHE';
                }
                
                if (brocheObs) {
                    detalles.push(`• <strong>${etiqueta}:</strong> ${brocheObs.toUpperCase()}`);
                } else {
                    detalles.push(`• <strong>${etiqueta}:</strong>`);
                }
            }

            if (detalles.length > 0) {
                lineas.push('');
                detalles.forEach((detalle) => lineas.push(detalle));
            }
        }

        // 3. Ubicaciones
        let ubicacionesArray = [];
        if (proceso.ubicaciones) {
            // Si es string JSON, parsearlo
            if (typeof proceso.ubicaciones === 'string') {
                try {
                    ubicacionesArray = JSON.parse(proceso.ubicaciones);
                } catch (e) {
                    console.warn('[Formatters] Error parseando ubicaciones:', proceso.ubicaciones, e);
                }
            } else if (Array.isArray(proceso.ubicaciones)) {
                ubicacionesArray = proceso.ubicaciones;
            }
        }
        
        if (ubicacionesArray && ubicacionesArray.length > 0) {
            lineas.push('');
            lineas.push('<span>UBICACIONES:</span>');
            ubicacionesArray.forEach((ubicacion) => {
                lineas.push(`• ${String(ubicacion).toUpperCase()}`);
            });
        }

        // 4. Observaciones
        if (proceso.observaciones && proceso.observaciones.trim()) {
            lineas.push('');
            lineas.push('<strong>OBSERVACIONES:</strong>');
            lineas.push(proceso.observaciones.toUpperCase());
        }

        // 5. Tallas - ENRIQUECIDAS CON COLORES si disponibles
        let tallasAMostrar = proceso.tallas || {};
        
        // 🎨 NUEVO: Si el proceso tiene talla_colores (colores por talla), transformar la estructura
        if (proceso.talla_colores && Array.isArray(proceso.talla_colores) && proceso.talla_colores.length > 0) {
            console.log('[Formatters.construirDescripcionProceso] 🎨 Transformando talla_colores a estructura enriquecida');
            tallasAMostrar = this._transformarTallaColoresAEstructura(proceso.talla_colores);
            console.log('[Formatters.construirDescripcionProceso] 🎨 Estructura enriquecida:', tallasAMostrar);
        }
        
        if (tallasAMostrar && Object.keys(tallasAMostrar).length > 0) {
            // Si el proceso trae tallas_detalle con es_sobremedida, aplicar lógica de SOBREMIDIDA
            const tallasDetalle = proceso.tallas_detalle;
            const esSobremedidaFlag = (val) => {
                if (val === true) return true;
                if (val === 1) return true;
                if (val === '1') return true;
                if (typeof val === 'string' && val.trim().toLowerCase() === 'true') return true;
                return false;
            };

            const esSobremedidaPorTalla = (t) => {
                if (!t) return false;
                if (esSobremedidaFlag(t.es_sobremedida)) return true;
                // Fallback: si talla viene null/vacía, suele representar sobremedida
                const tallaVal = t.talla;
                return tallaVal === null || tallaVal === undefined || String(tallaVal).trim() === '';
            };

            const tallasSobremedida = Array.isArray(tallasDetalle)
                ? tallasDetalle.filter(esSobremedidaPorTalla)
                : [];

            if (tallasSobremedida.length > 0) {
                const porGenero = tallasSobremedida.reduce((acc, t) => {
                    const genero = String(t.genero || 'SIN_GENERO').toUpperCase();
                    const cantidad = parseInt(t.cantidad, 10) || 0;
                    acc[genero] = (acc[genero] || 0) + cantidad;
                    return acc;
                }, {});

                const ordenGeneros = ['CABALLERO', 'DAMA', 'UNISEX', 'SIN_GENERO'];
                const generos = Object.keys(porGenero)
                    .sort((a, b) => {
                        const ia = ordenGeneros.indexOf(a);
                        const ib = ordenGeneros.indexOf(b);
                        if (ia === -1 && ib === -1) return a.localeCompare(b, 'es');
                        if (ia === -1) return 1;
                        if (ib === -1) return -1;
                        return ia - ib;
                    });

                lineas.push('');
                lineas.push('<strong>SOBREMEDIDA:</strong>');
                generos.forEach((g) => {
                    const qty = porGenero[g] || 0;
                    if (qty > 0) {
                        lineas.push(`${g}: <span style="color: #d32f2f; font-weight: bold;">${qty}</span>`);
                    }
                });
            } else {
                lineas.push('');
                lineas.push('<strong>TALLAS</strong>');
                this._agregarTallasFormato(lineas, tallasAMostrar, prenda.genero, prenda);
            }

            // NUEVO: Ubicaciones por talla (solo para procesos que manejan ubicaciones por talla)
            // Se apoya en `proceso.tallas_detalle` del backend: [{genero,talla,cantidad,ubicaciones,...}, ...]
            if (Array.isArray(tallasDetalle) && tallasDetalle.length > 0) {
                const normalizarUbicaciones = (raw) => {
                    if (!raw) return [];
                    if (Array.isArray(raw)) return raw;
                    if (typeof raw === 'string') {
                        const s = raw.trim();
                        if (!s) return [];
                        // Caso común: viene como string JSON: "[\"UBI 1\", \"UBI 2\"]"
                        if ((s.startsWith('[') && s.endsWith(']')) || (s.startsWith('{') && s.endsWith('}'))) {
                            try {
                                const parsed = JSON.parse(s);
                                if (Array.isArray(parsed)) return parsed;
                            } catch (e) {
                                // ignore
                            }
                        }
                        // Fallback: string simple
                        return [s];
                    }
                    return [];
                };

                const normalizarObservaciones = (raw) => {
                    if (!raw) return [];
                    if (Array.isArray(raw)) {
                        return raw
                            .map(v => String(v ?? '').trim())
                            .filter(v => v);
                    }
                    if (typeof raw === 'string') {
                        const s = raw.trim();
                        if (!s) return [];
                        // Permitir string JSON (array de strings)
                        if ((s.startsWith('[') && s.endsWith(']')) || (s.startsWith('{') && s.endsWith('}'))) {
                            try {
                                const parsed = JSON.parse(s);
                                if (Array.isArray(parsed)) {
                                    return parsed
                                        .map(v => String(v ?? '').trim())
                                        .filter(v => v);
                                }
                            } catch (e) {
                                // ignore
                            }
                        }
                        // Permitir multi-línea
                        return s
                            .split(/\r?\n/)
                            .map(v => String(v ?? '').trim())
                            .filter(v => v);
                    }
                    return [];
                };

                const conUbicaciones = tallasDetalle
                    .map(t => ({
                        ...t,
                        _ubicaciones_normalizadas: normalizarUbicaciones(t?.ubicaciones)
                    }))
                    .map(t => ({
                        ...t,
                        _observaciones_normalizadas: normalizarObservaciones(t?.observaciones)
                    }))
                    .filter(t => (parseInt(t?.cantidad, 10) || 0) > 0);

                const modo = String(proceso?.modo_tallas || '').toLowerCase();
                const esGeneral = modo === 'general';
                const esEspecifico = modo === 'especifico';

                const itemsAMostrar = conUbicaciones.filter((t) => {
                    if (esGeneral) {
                        return Array.isArray(t?._observaciones_normalizadas) && t._observaciones_normalizadas.length > 0;
                    }
                    // especifico (o cualquier otro modo) prioriza ubicaciones
                    return Array.isArray(t?._ubicaciones_normalizadas) && t._ubicaciones_normalizadas.length > 0;
                });

                if (itemsAMostrar.length > 0) {
                    lineas.push('');
                    lineas.push(
                        esGeneral
                            ? '<strong>OBSERVACIONES POR TALLA</strong>'
                            : '<strong>UBICACIONES POR TALLA</strong>'
                    );

                    const compararTallas = (tallaA, tallaB) => {
                        const a = String(tallaA).toUpperCase().trim();
                        const b = String(tallaB).toUpperCase().trim();
                        const numA = parseFloat(a);
                        const numB = parseFloat(b);
                        if (!isNaN(numA) && !isNaN(numB)) return numA - numB;
                        return a.localeCompare(b, 'es');
                    };

                    const construirBloqueGenero = (generoBuscado) => {
                        const items = itemsAMostrar
                            .filter(t => String(t.genero || '').toUpperCase() === generoBuscado)
                            .slice()
                            .sort((a, b) => compararTallas(a.talla || '', b.talla || ''));

                        if (items.length === 0) return '';

                        const partes = [];
                        partes.push(`<div style="font-weight: 800; margin-bottom: 6px;">${generoBuscado}</div>`);

                        items.forEach((t) => {
                            const tallaBaseLabel = (t.es_sobremedida ? 'SOBREMEDIDA' : (t.talla ?? '')).toString().toUpperCase();
                            const colorLabel = (t?.color_nombre ?? '').toString().trim().toUpperCase();
                            const tallaLabel = colorLabel ? `${tallaBaseLabel} - ${colorLabel}` : tallaBaseLabel;
                            partes.push(`<div style="margin-bottom: 8px;">`);
                            partes.push(`<div style="font-weight: 700;">${tallaLabel}</div>`);

                            if (esGeneral) {
                                (t._observaciones_normalizadas || []).forEach((o) => {
                                    partes.push(`<div>• ${String(o).toUpperCase()}</div>`);
                                });
                            } else {
                                const ubicacionesFlat = [];
                                (t._ubicaciones_normalizadas || []).forEach((u) => {
                                    const str = String(u ?? '').trim();
                                    if (!str) return;
                                    str.split(/\r?\n/).forEach((linea) => {
                                        const l = String(linea ?? '').trim();
                                        if (l) ubicacionesFlat.push(l);
                                    });
                                });

                                ubicacionesFlat.forEach((u) => {
                                    partes.push(`<div>• ${String(u).toUpperCase()}</div>`);
                                });
                            }

                            partes.push(`</div>`);
                        });

                        return partes.join('');
                    };

                    const colCaballero = construirBloqueGenero('CABALLERO') || construirBloqueGenero('UNISEX');
                    const colDama = construirBloqueGenero('DAMA');

                    let columnasHtml = '';
                    if (colCaballero && colDama) {
                        columnasHtml = `
                            <div style="display: flex; gap: 14px; align-items: flex-start;">
                                <div style="width: 50%;">${colCaballero}</div>
                                <div style="width: 50%;">${colDama}</div>
                            </div>
                        `;
                    } else if (colCaballero) {
                        columnasHtml = `
                            <div style="display: flex; gap: 14px; align-items: flex-start;">
                                <div style="width: 100%;">${colCaballero}</div>
                            </div>
                        `;
                    } else if (colDama) {
                        columnasHtml = `
                            <div style="display: flex; gap: 14px; align-items: flex-start;">
                                <div style="width: 100%;">${colDama}</div>
                            </div>
                        `;
                    }

                    lineas.push(columnasHtml);
                }
            }
        } else if (prenda.tallas && Object.keys(prenda.tallas).length > 0) {
            // Fallback: usar tallas de la prenda si el proceso no tiene
            lineas.push('');
            lineas.push('<strong>TALLAS</strong>');
            this._agregarTallasFormato(lineas, prenda.tallas, prenda.genero, prenda);
        }

        return lineas.join('<br>') || '<em>Sin información</em>';
    }

    /**
     * Comparar si dos objetos de tallas son idénticos
     */
    static _sonTallasIguales(tallas1, tallas2) {
        console.log('[Formatters._sonTallasIguales]  Comparando tallas:', {
            tallas1,
            tallas2
        });
        
        // Validación básica
        if (!tallas1 || !tallas2) {
            console.log('[Formatters._sonTallasIguales]  Una de las tallas es null/undefined');
            return false;
        }
        
        // Convertir ambas a objetos normalizados (ignorando colores)
        const norm1 = this._normalizarTallasParaComparacion(tallas1);
        const norm2 = this._normalizarTallasParaComparacion(tallas2);
        
        console.log('[Formatters._sonTallasIguales] 📊 Tallas normalizadas para comparación:', {
            norm1,
            norm2
        });
        
        // Comparar estructura
        const keys1 = Object.keys(norm1).sort();
        const keys2 = Object.keys(norm2).sort();
        
        if (keys1.length !== keys2.length) {
            console.log('[Formatters._sonTallasIguales]  Diferente cantidad de géneros:', { keys1, keys2 });
            return false;
        }
        
        // Comparar cada clave y valor
        for (let i = 0; i < keys1.length; i++) {
            if (keys1[i] !== keys2[i]) {
                console.log('[Formatters._sonTallasIguales]  Diferentes géneros:', keys1[i], 'vs', keys2[i]);
                return false;
            }
            const val1 = norm1[keys1[i]];
            const val2 = norm2[keys2[i]];
            if (JSON.stringify(val1) !== JSON.stringify(val2)) {
                console.log('[Formatters._sonTallasIguales]  Diferentes valores:', {
                    genero: keys1[i],
                    val1,
                    val2
                });
                return false;
            }
        }
        
        console.log('[Formatters._sonTallasIguales]  Tallas son IGUALES');
        return true;
    }

    /**
     * Comparación simplificada de tallas (solo talla y cantidad, ignorar colores)
     */
    static _comparacionSimpleTallas(tallas1, tallas2) {
        console.log('[Formatters._comparacionSimpleTallas] 🎯 Comparación simple:', { tallas1, tallas2 });
        
        const extraerTallasSimple = (tallas) => {
            const resultado = { dama: {}, caballero: {} };
            
            if (!tallas) return resultado;
            
            if (Array.isArray(tallas)) {
                tallas.forEach(item => {
                    if (item && typeof item === 'object') {
                        const genero = (item.genero || 'dama').toLowerCase();
                        const talla = item.talla || '';
                        const cantidad = item.cantidad || 0;
                        resultado[genero][talla] = cantidad;
                    }
                });
            } else if (typeof tallas === 'object') {
                Object.entries(tallas).forEach(([genero, datos]) => {
                    const gen = genero.toLowerCase();
                    if (typeof datos === 'object' && datos !== null) {
                        Object.entries(datos).forEach(([talla, valores]) => {
                            if (Array.isArray(valores)) {
                                // Sumar cantidades ignorando colores
                                const total = valores.reduce((sum, v) => sum + (v.cantidad || 0), 0);
                                resultado[gen][talla] = total;
                            } else {
                                resultado[gen][talla] = valores;
                            }
                        });
                    }
                });
            }
            
            return resultado;
        };
        
        const norm1 = extraerTallasSimple(tallas1);
        const norm2 = extraerTallasSimple(tallas2);
        
        console.log('[Formatters._comparacionSimpleTallas] 📊 Normalizado:', { norm1, norm2 });
        
        // Comparar DAMA
        const damaKeys1 = Object.keys(norm1.dama).sort();
        const damaKeys2 = Object.keys(norm2.dama).sort();
        
        if (damaKeys1.length !== damaKeys2.length) {
            console.log('[Formatters._comparacionSimpleTallas]  Diferente cantidad de tallas DAMA');
            return false;
        }
        
        for (const talla of damaKeys1) {
            if (!norm2.dama[talla] || norm1.dama[talla] !== norm2.dama[talla]) {
                console.log('[Formatters._comparacionSimpleTallas]  Diferente talla DAMA:', talla);
                return false;
            }
        }
        
        // Comparar CABALLERO
        const cabKeys1 = Object.keys(norm1.caballero).sort();
        const cabKeys2 = Object.keys(norm2.caballero).sort();
        
        if (cabKeys1.length !== cabKeys2.length) {
            console.log('[Formatters._comparacionSimpleTallas]  Diferente cantidad de tallas CABALLERO');
            return false;
        }
        
        for (const talla of cabKeys1) {
            if (!norm2.caballero[talla] || norm1.caballero[talla] !== norm2.caballero[talla]) {
                console.log('[Formatters._comparacionSimpleTallas]  Diferente talla CABALLERO:', talla);
                return false;
            }
        }
        
        console.log('[Formatters._comparacionSimpleTallas]  Tallas son IGUALES (comparación simple)');
        return true;
    }

    /**
     * Normalizar tallas específicamente para comparación (ignorar colores)
     */
    static _normalizarTallasParaComparacion(tallas) {
        const resultado = {};
        
        if (Array.isArray(tallas)) {
            tallas.forEach((item) => {
                if (item && typeof item === 'object') {
                    const genero = String(item.genero || 'dama').toLowerCase();
                    const talla = item.talla || '';
                    const cantidad = item.cantidad || 0;
                    
                    if (!resultado[genero]) resultado[genero] = {};
                    resultado[genero][talla] = cantidad;
                }
            });
        } else if (typeof tallas === 'object') {
            Object.entries(tallas).forEach(([key, value]) => {
                const genero = key.toLowerCase();
                if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                    resultado[genero] = {};
                    Object.entries(value).forEach(([talla, datos]) => {
                        if (Array.isArray(datos)) {
                            // Sumar todas las cantidades ignorando colores
                            const cantidadTotal = datos.reduce((sum, item) => sum + (item.cantidad || 0), 0);
                            resultado[genero][talla] = cantidadTotal;
                        } else {
                            resultado[genero][talla] = datos;
                        }
                    });
                }
            });
        }
        
        return resultado;
    }

    /**
     * Normalizar estructura de tallas a un formato consistente
     */
    static _normalizarTallas(tallas) {
        const resultado = {};
        
        if (Array.isArray(tallas)) {
            tallas.forEach((item) => {
                if (item && typeof item === 'object') {
                    const genero = String(item.genero || 'dama').toLowerCase();
                    const talla = item.talla || '';
                    const cantidad = item.cantidad || 0;
                    
                    if (!resultado[genero]) resultado[genero] = {};
                    resultado[genero][talla] = cantidad;
                }
            });
        } else if (typeof tallas === 'object') {
            Object.entries(tallas).forEach(([key, value]) => {
                const genero = key.toLowerCase();
                if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                    // Nuevo formato: {DAMA: {M: [{cantidad: 1, color: "AQUA"}]}}
                    resultado[genero] = {};
                    Object.entries(value).forEach(([talla, datos]) => {
                        if (Array.isArray(datos)) {
                            // Nuevo formato con colores: sumar todas las cantidades ignorando colores
                            const cantidadTotal = datos.reduce((sum, item) => sum + (item.cantidad || 0), 0);
                            resultado[genero][talla] = cantidadTotal;
                        } else {
                            // Formato antiguo: {DAMA: {M: 1}}
                            resultado[genero][talla] = datos;
                        }
                    });
                }
            });
        }
        
        return resultado;
    }

    /**
     * Agregar tallas al formato de forma reutilizable
     */
    static _agregarTallasFormato(lineas, tallas, generoDefault = 'dama', prenda = null) {
        console.log('[Formatters._agregarTallasFormato] 🎯 INPUT:', { tallas, generoDefault });
        console.log('[Formatters._agregarTallasFormato] 🎯 Tipo tallas:', typeof tallas, 'Es array:', Array.isArray(tallas));
        
        // Si tallas es un string JSON, parsearlo primero
        if (typeof tallas === 'string' && tallas.trim()) {
            try {
                console.log('[Formatters._agregarTallasFormato] 🔧 Parseando tallas desde string JSON...');
                tallas = JSON.parse(tallas);
                console.log('[Formatters._agregarTallasFormato]  Tallas parseadas:', tallas);
                console.log('[Formatters._agregarTallasFormato] 🎯 Nuevo tipo tallas:', typeof tallas, 'Es array:', Array.isArray(tallas));
            } catch (error) {
                console.error('[Formatters._agregarTallasFormato]  Error parseando tallas:', error);
                tallas = {};
            }
        }
        
        // Debug detallado de la estructura
        if (typeof tallas === 'object' && tallas !== null) {
            console.log('[Formatters._agregarTallasFormato]  Claves de tallas:', Object.keys(tallas));
            Object.keys(tallas).forEach(key => {
                const value = tallas[key];
                console.log(`[Formatters._agregarTallasFormato]  Key "${key}":`, {
                    tipo: typeof value,
                    esArray: Array.isArray(value),
                    valor: value,
                    clavesInterna: typeof value === 'object' && value !== null ? Object.keys(value) : []
                });
            });
        }
        
        const tallasDama = {};
        const tallasCalballero = {};

        const insertarTallaConColor = (destino, tallaKey, cantidad) => {
            const qty = parseInt(cantidad, 10) || 0;
            if (!tallaKey || qty <= 0) return;

            const strKey = String(tallaKey);
            if (strKey.includes('__')) {
                const [tallaRaw, colorRaw] = strKey.split('__');
                const talla = String(tallaRaw || '').trim().toUpperCase();
                const color = String(colorRaw || '').trim().toUpperCase();
                if (!talla) return;

                if (!destino[talla] || !Array.isArray(destino[talla])) {
                    destino[talla] = [];
                }

                const existente = destino[talla].find(d => (d?.color || '').toUpperCase() === color);
                if (existente) {
                    existente.cantidad = (parseInt(existente.cantidad, 10) || 0) + qty;
                } else {
                    destino[talla].push({ color: color || 'SIN COLOR', cantidad: qty });
                }
                return;
            }

            const talla = strKey.trim().toUpperCase();
            if (!talla) return;
            if (destino[talla] && Array.isArray(destino[talla])) {
                const existente = destino[talla].find(d => (d?.color || '').toUpperCase() === 'SIN COLOR');
                if (existente) {
                    existente.cantidad = (parseInt(existente.cantidad, 10) || 0) + qty;
                } else {
                    destino[talla].push({ color: 'SIN COLOR', cantidad: qty });
                }
            } else {
                destino[talla] = qty;
            }
        };
        
        // Si es array, convertir a estructura procesable
        if (Array.isArray(tallas)) {
            console.log('[Formatters._agregarTallasFormato]  Convirtiendo array a objeto...');
            console.log('[Formatters._agregarTallasFormato]  Array completo:', JSON.stringify(tallas, null, 2));
            
            tallas.forEach((item, idx) => {
                console.log(`[Formatters._agregarTallasFormato] Array[${idx}]:`, item);
                console.log(`[Formatters._agregarTallasFormato] Array[${idx}] - tipo:`, typeof item);
                console.log(`[Formatters._agregarTallasFormato] Array[${idx}] - esSobremedida:`, item?.es_sobremedida);
                console.log(`[Formatters._agregarTallasFormato] Array[${idx}] - talla:`, item?.talla);
                
                if (typeof item === 'object' && item !== null) {
                    const genero = String(item.genero || generoDefault).toLowerCase();
                    const talla = item.talla || '';
                    const cantidad = item.cantidad || 0;
                    const esSobremedida = item.es_sobremedida || 0;
                    
                    console.log(`[Formatters._agregarTallasFormato]   → genero=${genero}, talla=${talla}, cantidad=${cantidad}, es_sobremedida=${esSobremedida}`);
                    
                    // Si es sobremedida, usar "SOBREMEDIDA" como talla
                    const tallaFinal = esSobremedida ? 'SOBREMEDIDA' : talla;
                    
                    console.log(`[Formatters._agregarTallasFormato]   → tallaFinal="${tallaFinal}"`);
                    
                    if (genero === 'dama') {
                        insertarTallaConColor(tallasDama, tallaFinal, cantidad);
                        console.log(`[Formatters._agregarTallasFormato]   → Agregado a DAMA: ${tallaFinal}=${cantidad}`);
                    } else if (genero === 'caballero') {
                        insertarTallaConColor(tallasCalballero, tallaFinal, cantidad);
                        console.log(`[Formatters._agregarTallasFormato]   → Agregado a CABALLERO: ${tallaFinal}=${cantidad}`);
                    }
                } else {
                    console.log(`[Formatters._agregarTallasFormato]   → Item no es objeto válido:`, item);
                }
            });
        } else {
            // Procesar como objeto normal
            console.log('[Formatters._agregarTallasFormato]  Procesando como objeto...');
            Object.entries(tallas).forEach(([key, value]) => {
                console.log(`[Formatters._agregarTallasFormato]  Procesando: key=${key}, value=${value}, type=${typeof value}`);
                
                // Normalizar género a mayúsculas (vienen como "dama"/"DAMA", "caballero"/"CABALLERO", etc.)
                const generoNormalizado = String(key).toUpperCase();
                
                if (typeof value === 'object' && value !== null) {
                    const genero = generoNormalizado.toLowerCase(); // Para logging
                    
                    // Si es array de objetos (estructura del backend)
                    if (Array.isArray(value)) {
                        console.log(`[Formatters._agregarTallasFormato]     Procesando array de tallas para género: ${genero}`);
                        value.forEach((item, idx) => {
                            console.log(`[Formatters._agregarTallasFormato]      Item[${idx}]:`, item);
                            
                            if (typeof item === 'object' && item !== null) {
                                // Para sobremedida, buscar si el item tiene cantidad pero no talla específica
                                if (item.cantidad && (!item.talla || item.talla === null || item.talla === '')) {
                                    // Es sobremedida
                                    const cantidad = item.cantidad || 0;
                                    console.log(`[Formatters._agregarTallasFormato]       Sobremedida detectada: ${genero}=${cantidad}`);
                                    
                                    if (generoNormalizado === 'DAMA') {
                                        tallasDama['SOBREMEDIDA'] = cantidad;
                                    } else if (generoNormalizado === 'CABALLERO') {
                                        tallasCalballero['SOBREMEDIDA'] = cantidad;
                                    }
                                } else if (item.talla && item.cantidad) {
                                    // Talla específica
                                    const talla = item.talla;
                                    const cantidad = item.cantidad || 0;
                                    console.log(`[Formatters._agregarTallasFormato]       Talla específica: ${genero}=${talla}=${cantidad}`);
                                    
                                    if (generoNormalizado === 'DAMA') {
                                        tallasDama[talla] = cantidad;
                                    } else if (generoNormalizado === 'CABALLERO') {
                                        tallasCalballero[talla] = cantidad;
                                    }
                                }
                            }
                        });
                    } else {
                        // Procesamiento original para objetos simples
                        console.log(`[Formatters._agregarTallasFormato]     Procesando objeto simple para género: ${genero}`);
                        Object.entries(value).forEach(([tallaKey, cantidadRaw]) => {
                            //  NUEVO: Detectar si es array de colores
                            if (Array.isArray(cantidadRaw) && cantidadRaw.length > 0 && cantidadRaw[0] && cantidadRaw[0].color) {
                                // Es array de colores [{color: 'AZUL', cantidad: 1}, ...]
                                console.log(`[Formatters._agregarTallasFormato]      → tallaKey="${tallaKey}", ES ARRAY DE COLORES, PRESERVAR`);
                                
                                const talla = String(tallaKey).trim().toUpperCase();
                                if (!talla) return;
                                
                                if (generoNormalizado === 'DAMA') {
                                    tallasDama[talla] = cantidadRaw;
                                } else if (generoNormalizado === 'CABALLERO') {
                                    tallasCalballero[talla] = cantidadRaw;
                                }
                                return;
                            }
                            
                            // Manejar ambas estructuras: objeto con cantidad/color o número directo
                            let cantidad = cantidadRaw;
                            
                            // Si es array de objetos (estructura antigua)
                            if (Array.isArray(cantidadRaw)) {
                                cantidad = cantidadRaw.reduce((sum, item) => sum + (item.cantidad || 0), 0);
                                console.log(`[Formatters._agregarTallasFormato]      → tallaKey="${tallaKey}", cantidadRaw es array, cantidad total="${cantidad}"`);
                            } else if (typeof cantidadRaw === 'object' && cantidadRaw !== null) {
                                // Si es objeto con cantidad
                                cantidad = cantidadRaw.cantidad || 0;
                                console.log(`[Formatters._agregarTallasFormato]      → tallaKey="${tallaKey}", cantidadRaw es objeto, cantidad="${cantidad}"`);
                            } else {
                                // Es número directo
                                cantidad = parseInt(cantidadRaw, 10) || 0;
                                console.log(`[Formatters._agregarTallasFormato]      → tallaKey="${tallaKey}", cantidad="${cantidad}"`);
                            }
                            
                            if (generoNormalizado === 'DAMA') {
                                insertarTallaConColor(tallasDama, tallaKey, cantidad);
                            } else if (generoNormalizado === 'CABALLERO') {
                                insertarTallaConColor(tallasCalballero, tallaKey, cantidad);
                            }
                        });
                    }
                }
            });
        }
        
        console.log('[Formatters._agregarTallasFormato]  Resultado final:', { tallasDama, tallasCalballero });
        
        // Función helper para ordenar tallas de menor a mayor
        const ordenTallasLetra = ['XXXS', 'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '2XL', '3XL', '4XL', '5XL'];
        const compararTallas = (tallaA, tallaB) => {
            const a = String(tallaA).toUpperCase().trim();
            const b = String(tallaB).toUpperCase().trim();
            const numA = parseFloat(a);
            const numB = parseFloat(b);
            // Ambas numéricas
            if (!isNaN(numA) && !isNaN(numB)) return numA - numB;
            // Ambas de letra
            const idxA = ordenTallasLetra.indexOf(a);
            const idxB = ordenTallasLetra.indexOf(b);
            if (idxA !== -1 && idxB !== -1) return idxA - idxB;
            // Una conocida y otra no
            if (idxA !== -1) return -1;
            if (idxB !== -1) return 1;
            // Fallback alfabético
            return a.localeCompare(b);
        };

        // Función helper para renderizar tallas agrupadas por color
        const renderizarTallasGenero = (tallasObj, generoLabel) => {
            if (Object.keys(tallasObj).length === 0) return;
            
            // Detectar si hay sobremedida
            const tieneSobremedida = tallasObj.hasOwnProperty('SOBREMEDIDA');
            
            // Si solo hay sobremedida, mostrar formato especial
            if (tieneSobremedida && Object.keys(tallasObj).length === 1) {
                const cantidad = tallasObj['SOBREMEDIDA'];
                lineas.push(`<strong>SOBREMEDIDA</strong>`);
                lineas.push(`${generoLabel}: ${cantidad}`);
                console.log(`[Formatters._agregarTallasFormato]  ${generoLabel} SOBREMEDIDA: ${cantidad}`);
                return;
            }
            
            // Detectar si hay colores (datos son arrays de objetos)
            const tieneColores = Object.values(tallasObj).some(datos => Array.isArray(datos));
            
            if (tieneColores) {
                // Agrupar por color: { 'AZUL CELESTE': [{talla: 'S', cantidad: 3}, ...], ... }
                const porColor = {};
                
                Object.entries(tallasObj).forEach(([talla, datos]) => {
                    if (Array.isArray(datos)) {
                        datos.forEach(d => {
                            const esColorValido = d.color && d.color.toLowerCase() !== 'sin color' && d.color.trim() !== '';
                            if (esColorValido) {
                                const color = d.color.toUpperCase();
                                if (!porColor[color]) porColor[color] = [];
                                porColor[color].push({ talla, cantidad: d.cantidad || 0 });
                            } else {
                                if (!porColor['__SIN_COLOR__']) porColor['__SIN_COLOR__'] = [];
                                porColor['__SIN_COLOR__'].push({ talla, cantidad: d.cantidad || 0 });
                            }
                        });
                    } else {
                        if (!porColor['__SIN_COLOR__']) porColor['__SIN_COLOR__'] = [];
                        porColor['__SIN_COLOR__'].push({ talla, cantidad: datos });
                    }
                });
                
                console.log(`[Formatters._agregarTallasFormato]  ${generoLabel} agrupado por color:`, porColor);
                
                // Renderizar agrupado por color
                const coloresReales = Object.entries(porColor).filter(([c]) => c !== '__SIN_COLOR__');
                const sinColor = porColor['__SIN_COLOR__'] || [];
                
                if (coloresReales.length > 0) {
                    lineas.push(`<strong>${generoLabel}:</strong>`);
                    coloresReales.forEach(([color, tallasArr]) => {
                        tallasArr.sort((a, b) => compararTallas(a.talla, b.talla));
                        const tallasStr = tallasArr.map(t => `${t.talla}-${t.cantidad}`).join(', ');
                        lineas.push(`<span style="color: red;"><strong>${color}:</strong> ${tallasStr}</span>`);
                    });
                } else if (sinColor.length > 0) {
                    // Solo tallas sin color - formato simple
                    sinColor.sort((a, b) => compararTallas(a.talla, b.talla));
                    const tallasStr = sinColor.map(t => `<span style="color: red;"><strong>${t.talla}: ${t.cantidad}</strong></span>`).join(', ');
                    lineas.push(`${generoLabel}: ${tallasStr}`);
                }
            } else {
                // Sin colores - formato simple (ordenado de menor a mayor)
                const tallasStr = Object.entries(tallasObj)
                    .sort(([tallaA], [tallaB]) => compararTallas(tallaA, tallaB))
                    .map(([talla, cantidad]) => `<span style="color: red;"><strong>${talla}: ${cantidad}</strong></span>`)
                    .join(', ');
                lineas.push(`${generoLabel}: ${tallasStr}`);
            }
            
            console.log(`[Formatters._agregarTallasFormato]  ${generoLabel} agregado`);
        };
        
        // Renderizar DAMA
        renderizarTallasGenero(tallasDama, 'DAMA');
        
        // Renderizar CABALLERO
        renderizarTallasGenero(tallasCalballero, 'CABALLERO');
        
        console.log('[Formatters._agregarTallasFormato]  Lineas después:', lineas);
    }

    /**
     * Transformar array de talla_colores a estructura compatible con renderizado
     * 
     * Input: [{genero, talla, color_nombre, cantidad, ...}, ...]
     * Output: { DAMA: { TALLA: [{color, cantidad}, ...] }, CABALLERO: {...} }
     */
    static _transformarTallaColoresAEstructura(tallasColoresArray) {
        console.log('[Formatters._transformarTallaColoresAEstructura] 🎨 INPUT:', tallasColoresArray);
        
        if (!Array.isArray(tallasColoresArray) || tallasColoresArray.length === 0) {
            console.warn('[Formatters._transformarTallaColoresAEstructura] ⚠️ Array vacío o inválido');
            return {};
        }

        const estructura = {
            DAMA: {},
            CABALLERO: {},
            UNISEX: {}
        };

        // Procesar cada registro de talla_colores
        tallasColoresArray.forEach((registro, idx) => {
            console.log(`[Formatters._transformarTallaColoresAEstructura] Procesando registro ${idx}:`, registro);
            
            const genero = String(registro.genero || '').toUpperCase();
            const talla = String(registro.talla || '').trim().toUpperCase();
            const colorNombre = String(registro.color_nombre || '').trim().toUpperCase();
            const cantidad = parseInt(registro.cantidad || 0, 10);

            console.log(`[Formatters._transformarTallaColoresAEstructura]   genero=${genero}, talla=${talla}, color=${colorNombre}, cantidad=${cantidad}`);

            // Validar datos mínimos
            if (!genero || !talla || cantidad <= 0) {
                console.warn(`[Formatters._transformarTallaColoresAEstructura]   ⚠️ Saltando registro inválido`);
                return;
            }

            // Verificar que el género sea válido
            if (!estructura.hasOwnProperty(genero)) {
                console.warn(`[Formatters._transformarTallaColoresAEstructura]   ⚠️ Género inválido: ${genero}`);
                return;
            }

            // Inicializar talla si no existe
            if (!estructura[genero][talla]) {
                estructura[genero][talla] = [];
            }

            // Si la estructura de la talla es un array, agregar el color
            if (Array.isArray(estructura[genero][talla])) {
                // Buscar si el color ya existe
                const colorExistente = estructura[genero][talla].find(c => 
                    c.color === (colorNombre || 'SIN COLOR')
                );

                if (colorExistente) {
                    // Sumar cantidad si el color ya existe
                    colorExistente.cantidad += cantidad;
                    console.log(`[Formatters._transformarTallaColoresAEstructura]    Color existente actualizado: ${colorNombre} → ${colorExistente.cantidad}`);
                } else {
                    // Agregar nuevo color
                    estructura[genero][talla].push({
                        color: colorNombre || 'SIN COLOR',
                        cantidad: cantidad
                    });
                    console.log(`[Formatters._transformarTallaColoresAEstructura]    Nuevo color agregado: ${colorNombre || 'SIN COLOR'} = ${cantidad}`);
                }
            }
        });

        console.log('[Formatters._transformarTallaColoresAEstructura] 📊 Estructura final:', estructura);
        return estructura;
    }

    /**
     * Parsear fecha en diferentes formatos
     */
    static parsearFecha(fechaStr) {
        if (!fechaStr) return new Date();
        
        console.log('[Formatters.parsearFecha] Procesando fecha:', fechaStr);
        
        let fecha = null;
        
        // Formato ISO completo con hora (ej: "2026-03-10 17:49:21")
        if (fechaStr.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
            // Separar fecha y hora para evitar problemas de zona horaria
            const [fechaPart, horaPart] = fechaStr.split(' ');
            const [year, month, day] = fechaPart.split('-');
            const [hours, minutes, seconds] = horaPart.split(':');
            
            // Crear fecha usando UTC para evitar desfases
            fecha = new Date(Date.UTC(
                parseInt(year),
                parseInt(month) - 1, // Los meses en JS son 0-11
                parseInt(day),
                parseInt(hours),
                parseInt(minutes),
                parseInt(seconds)
            ));
            
            console.log('[Formatters.parsearFecha] Fecha ISO completa parseada:', {
                original: fechaStr,
                parsed: fecha,
                utcString: fecha.toUTCString(),
                localString: fecha.toLocaleString()
            });
        }
        // Formato d/m/Y (ej: "19/01/2026")
        else if (fechaStr.includes('/')) {
            const [day, month, year] = fechaStr.split('/');
            fecha = new Date(year, parseInt(month) - 1, day);
            
            console.log('[Formatters.parsearFecha] Fecha d/m/Y parseada:', {
                original: fechaStr,
                parsed: fecha
            });
        }
        // Formato Y-m-d sin hora (ej: "2026-01-19")
        else if (fechaStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
            // Crear fecha sin hora para evitar problemas de zona horaria
            const [year, month, day] = fechaStr.split('-');
            fecha = new Date(Date.UTC(parseInt(year), parseInt(month) - 1, parseInt(day)));
            
            console.log('[Formatters.parsearFecha] Fecha Y-m-d parseada:', {
                original: fechaStr,
                parsed: fecha
            });
        }
        // Otros formatos (incluyendo ISO con T)
        else {
            fecha = new Date(fechaStr);
            console.log('[Formatters.parsearFecha] Fecha con formato estándar parseada:', {
                original: fechaStr,
                parsed: fecha
            });
        }
        
        // Si la fecha es inválida, usar fecha actual
        if (isNaN(fecha.getTime())) {
            console.warn('[Formatters.parsearFecha] Fecha inválida, usando fecha actual:', fechaStr);
            fecha = new Date();
        }
        
        return fecha;
    }

    /**
     * Formatea fecha a objeto {day, month, year}
     */
    static formatearFecha(fecha) {
        const resultado = {
            day: String(fecha.getUTCDate()).padStart(2, '0'),
            month: String(fecha.getUTCMonth() + 1).padStart(2, '0'),
            year: fecha.getUTCFullYear()
        };
        
        console.log('[Formatters.formatearFecha] Fecha formateada:', {
            fechaOriginal: fecha,
            fechaUTCString: fecha.toUTCString(),
            fechaLocalString: fecha.toLocaleString(),
            resultado
        });
        
        return resultado;
    }
}
