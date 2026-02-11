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
                    const color = t.color_nombre || '';
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

        // 3. Descripción base - Limpiar basura
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
                broche_boton_obs: primerVariante.broche_boton_obs,
                tipo_broche_boton_id: primerVariante.tipo_broche_boton_id
            });
            
            if (primerVariante.bolsillos_obs && primerVariante.bolsillos_obs.trim()) {
                detalles.push(`• <strong>BOLSILLOS:</strong> ${primerVariante.bolsillos_obs.toUpperCase()}`);
                console.log('[Formatters]  BOLSILLOS agregados');
            }
            
            // Buscar BROCHE/BOTÓN en broche_obs o broche_boton_obs
            const brocheObs = primerVariante.broche_obs || primerVariante.broche_boton_obs;
            if (brocheObs && brocheObs.trim()) {
                let etiqueta = primerVariante.broche || 'BROCHE/BOTÓN';
                if (etiqueta.toLowerCase().includes('botón')) {
                    etiqueta = 'BOTÓN';
                } else if (etiqueta.toLowerCase().includes('broche')) {
                    etiqueta = 'BROCHE';
                }
                
                detalles.push(`• <strong>${etiqueta}:</strong> ${brocheObs.toUpperCase()}`);
                console.log('[Formatters]  BROCHE/BOTÓN agregado:', etiqueta);
            } else {
                console.log('[Formatters]  No hay broche_obs o broche_boton_obs');
            }
        }
        
        if (detalles.length > 0) {
            detalles.forEach((detalle) => {
                lineas.push(detalle);
            });
        }

        // 5. Tallas
        console.log('[Formatters]  Procesando tallas...');
        console.log('[Formatters]  Tipo de tallas:', typeof prenda.tallas, 'Es array:', Array.isArray(prenda.tallas));
        
        if (prenda.tallas) {
            let tienesTallas = false;
            
            // Si es array, contar elementos
            if (Array.isArray(prenda.tallas)) {
                tienesTallas = prenda.tallas.length > 0;
                console.log('[Formatters]  Tallas es ARRAY con', prenda.tallas.length, 'elementos');
            } else {
                tienesTallas = Object.keys(prenda.tallas).length > 0;
                console.log('[Formatters]  Tallas es OBJETO con', Object.keys(prenda.tallas).length, 'claves');
            }
            
            if (tienesTallas) {
                console.log('[Formatters]  Tallas encontradas');
                lineas.push('');
                lineas.push('<strong>TALLAS</strong>');
                this._agregarTallasFormato(lineas, prenda.tallas, prenda.genero);
            } else {
                console.log('[Formatters]  No hay tallas (vacío)');
            }
        } else {
            console.log('[Formatters]  No hay tallas (undefined/null)');
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
                    ubicacionesHTML = '<strong>UBICACIONES:</strong><br>';
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
                    const tallasIguales = this._sonTallasIguales(tallasAMostrar, prenda.tallas);
                    
                    if (!tallasIguales) {
                        lineas.push('<strong>TALLAS</strong>');
                        this._agregarTallasFormato(lineas, tallasAMostrar, prenda.genero);
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
        console.log('[Formatters.construirDescripcionCostura] 📄 OUTPUT completo:', resultado);
        console.log('[Formatters.construirDescripcionCostura] 📄 Cantidad de líneas:', lineas.length);
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
        if (prenda.tela) partes.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
        if (prenda.color) partes.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
        if (prenda.ref) partes.push(`<strong>REF:</strong> ${prenda.ref.toUpperCase()}`);
        
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
            lineas.push('<strong>UBICACIONES:</strong>');
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

        // 5. Tallas
        if (prenda.tallas && Object.keys(prenda.tallas).length > 0) {
            lineas.push('');
            lineas.push('<strong>TALLAS</strong>');
            this._agregarTallasFormato(lineas, prenda.tallas, prenda.genero);
        }

        return lineas.join('<br>') || '<em>Sin información</em>';
    }

    /**
     * Comparar si dos objetos de tallas son idénticos
     */
    static _sonTallasIguales(tallas1, tallas2) {
        // Validación básica
        if (!tallas1 || !tallas2) return false;
        
        // Convertir ambas a objetos normalizados
        const norm1 = this._normalizarTallas(tallas1);
        const norm2 = this._normalizarTallas(tallas2);
        
        // Comparar estructura
        const keys1 = Object.keys(norm1).sort();
        const keys2 = Object.keys(norm2).sort();
        
        if (keys1.length !== keys2.length) return false;
        
        // Comparar cada clave y valor
        for (let i = 0; i < keys1.length; i++) {
            if (keys1[i] !== keys2[i]) return false;
            if (JSON.stringify(norm1[keys1[i]]) !== JSON.stringify(norm2[keys2[i]])) {
                return false;
            }
        }
        
        return true;
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
                    resultado[genero] = value;
                }
            });
        }
        
        return resultado;
    }

    /**
     * Agregar tallas al formato de forma reutilizable
     */
    static _agregarTallasFormato(lineas, tallas, generoDefault = 'dama') {
        console.log('[Formatters._agregarTallasFormato] 🎯 INPUT:', { tallas, generoDefault });
        console.log('[Formatters._agregarTallasFormato] 🎯 Tipo tallas:', typeof tallas, 'Es array:', Array.isArray(tallas));
        
        const tallasDama = {};
        const tallasCalballero = {};
        
        // Si es array, convertir a estructura procesable
        if (Array.isArray(tallas)) {
            console.log('[Formatters._agregarTallasFormato]  Convirtiendo array a objeto...');
            tallas.forEach((item, idx) => {
                console.log(`[Formatters._agregarTallasFormato] Array[${idx}]:`, item);
                
                if (typeof item === 'object' && item !== null) {
                    const genero = String(item.genero || generoDefault).toLowerCase();
                    const talla = item.talla || '';
                    const cantidad = item.cantidad || 0;
                    
                    console.log(`[Formatters._agregarTallasFormato]   → genero=${genero}, talla=${talla}, cantidad=${cantidad}`);
                    
                    if (genero === 'dama') {
                        tallasDama[talla] = cantidad;
                    } else if (genero === 'caballero') {
                        tallasCalballero[talla] = cantidad;
                    }
                }
            });
        } else {
            // Procesar como objeto normal
            console.log('[Formatters._agregarTallasFormato]  Procesando como objeto...');
            Object.entries(tallas).forEach(([key, value]) => {
                console.log(`[Formatters._agregarTallasFormato]  Procesando: key=${key}, value=${value}, type=${typeof value}`);
                
                if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                    const genero = key.toLowerCase();
                    Object.entries(value).forEach(([talla, cantidad]) => {
                        if (genero === 'dama') {
                            tallasDama[talla] = cantidad;
                        } else if (genero === 'caballero') {
                            tallasCalballero[talla] = cantidad;
                        }
                    });
                } 
                else if (typeof value === 'number' || typeof value === 'string') {
                    if (key.includes('-')) {
                        const [genero, talla] = key.split('-');
                        if (genero.toLowerCase() === 'dama') {
                            tallasDama[talla] = value;
                        } else if (genero.toLowerCase() === 'caballero') {
                            tallasCalballero[talla] = value;
                        }
                    } else {
                        const genero = generoDefault || 'dama';
                        if (genero.toLowerCase() === 'dama') {
                            tallasDama[key] = value;
                        } else if (genero.toLowerCase() === 'caballero') {
                            tallasCalballero[key] = value;
                        }
                    }
                }
            });
        }
        
        console.log('[Formatters._agregarTallasFormato]  Resultado final:', { tallasDama, tallasCalballero });
        
        // Renderizar DAMA
        if (Object.keys(tallasDama).length > 0) {
            const tallasStr = Object.entries(tallasDama)
                .map(([talla, cant]) => `<span style="color: red;"><strong>${talla}: ${cant}</strong></span>`)
                .join(', ');
            lineas.push(`DAMA: ${tallasStr}`);
            console.log('[Formatters._agregarTallasFormato]  DAMA agregado');
        }
        
        // Renderizar CABALLERO
        if (Object.keys(tallasCalballero).length > 0) {
            const tallasStr = Object.entries(tallasCalballero)
                .map(([talla, cant]) => `<span style="color: red;"><strong>${talla}: ${cant}</strong></span>`)
                .join(', ');
            lineas.push(`CABALLERO: ${tallasStr}`);
            console.log('[Formatters._agregarTallasFormato]  CABALLERO agregado');
        }
        
        console.log('[Formatters._agregarTallasFormato] 📄 Lineas después:', lineas);
    }

    /**
     * Parsear fecha en diferentes formatos
     */
    static parsearFecha(fechaStr) {
        if (!fechaStr) return new Date();
        
        let fecha = null;
        
        // Formato d/m/Y (ej: "19/01/2026")
        if (fechaStr.includes('/')) {
            const [day, month, year] = fechaStr.split('/');
            fecha = new Date(year, parseInt(month) - 1, day);
        }
        // Formato Y-m-d (ej: "2026-01-19")
        else if (fechaStr.includes('-')) {
            fecha = new Date(fechaStr + 'T00:00:00');
        }
        // Otros formatos
        else {
            fecha = new Date(fechaStr);
        }
        
        // Si la fecha es inválida, usar fecha actual
        if (isNaN(fecha.getTime())) {

            fecha = new Date();
        }
        
        return fecha;
    }

    /**
     * Formatea fecha a objeto {day, month, year}
     */
    static formatearFecha(fecha) {
        return {
            day: String(fecha.getDate()).padStart(2, '0'),
            month: String(fecha.getMonth() + 1).padStart(2, '0'),
            year: fecha.getFullYear()
        };
    }
}
