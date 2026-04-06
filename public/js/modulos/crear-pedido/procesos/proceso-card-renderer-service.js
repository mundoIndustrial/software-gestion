/**
 * Servicio para construir HTML de tarjetas de proceso.
 * Mantiene compatibilidad con API global existente.
 */
(function() {
    'use strict';

    function agregarStorageUrl(url) {
        if (!url || typeof url !== 'string') return '';
        if (url.startsWith('/')) return url;
        if (url.startsWith('http')) return url;
        if (url.startsWith('blob:')) return url;
        if (url.startsWith('data:')) return url;
        return '/storage/' + url;
    }

function generarTarjetaProceso(tipo, datos) {
    const icono = (globalThis.iconosProcesos || {})[tipo] || '<span class="material-symbols-rounded">settings</span>';
    const nombre = (globalThis.nombresProcesos || {})[tipo] || datos.nombre || datos.nombre_proceso || datos.descripcion || datos.tipo_proceso || tipo.toUpperCase();

    const formatearTallaKey = (tallaKey) => {
        const parts = String(tallaKey).split('__');
        const talla = (parts[0] || tallaKey);
        const color = (parts[1] || null);
        return color ? `${talla} - ${color}` : talla;
    };

    const renderGrupoTallas = (tituloGrupo, entries, chipStyle) => {
        if (!entries || entries.length === 0) return '';
        const chips = entries.map(([tallaKey, cantidad]) => {
            return `<span style="${chipStyle}">
                ${formatearTallaKey(tallaKey)}: ${cantidad}
            </span>`;
        }).join('');

        return `
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <div style="font-size: 0.75rem; font-weight: 800; color: #374151; letter-spacing: 0.02em;">${tituloGrupo}</div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${chips}
                </div>
            </div>
        `;
    };
    
    // Funci�n auxiliar para agregar /storage/ a URLs
    const agregarStorage = (url) => {
        if (!url) return '';
        if (url.startsWith('/')) return url;
        if (url.startsWith('http')) return url;
        if (url.startsWith('blob:')) return url;
        if (url.startsWith('data:')) return url;
        return '/storage/' + url;
    };
    
    // Calcular totalTallas
    const damaObj = datos.tallas?.dama || {};
    const caballeroObj = datos.tallas?.caballero || {};
    const sobremedidaObj = datos.tallas?.sobremedida || {};
    const totalTallas = Object.keys(damaObj).length + Object.keys(caballeroObj).length + Object.keys(sobremedidaObj).length;
    
    // Procesar ubicaciones
    let ubicacionesArray = datos.ubicaciones || [];
    const limpiarYparsearUbicaciones = (raw) => {
        if (!raw) return [];
        if (typeof raw === 'string') {
            try {
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [parsed];
            } catch (e) {
                return [raw];
            }
        }
        if (Array.isArray(raw)) {
            if (raw.length > 0 && typeof raw[0] === 'object' && raw[0].ubicacion) {
                return raw;
            }
            const resultado = raw.map(ub => {
                if (typeof ub === 'string') {
                    try {
                        const parsed = JSON.parse(ub);
                        return Array.isArray(parsed) ? parsed[0] : parsed;
                    } catch (e) {
                        return ub;
                    }
                }
                if (typeof ub === 'object' && ub !== null) {
                    return ub;
                }
                return ub;
            });
            return resultado.flat();
        }
        return [String(raw)];
    };
    
    ubicacionesArray = limpiarYparsearUbicaciones(ubicacionesArray);
    
    // HTML de ubicaciones
    const ubicacionesHTML = Array.isArray(ubicacionesArray) && ubicacionesArray.length > 0
        ? ubicacionesArray.map(ub => {
            if (typeof ub === 'object' && ub.ubicacion) {
                const ubicacion = ub.ubicacion;
                const descripcion = ub.descripcion ? ub.descripcion.replace(/\n/g, ' ').substring(0, 100) : '';
                return descripcion 
                    ? `<div style="margin-bottom: 0.5rem;"><strong>${ubicacion}</strong> - <span style="color: #6b7280; font-size: 0.8rem;">${descripcion}</span></div>` 
                    : `<div style="margin-bottom: 0.5rem;"><strong>${ubicacion}</strong></div>`;
            }
            if (typeof ub === 'string') {
                return `<div style="margin-bottom: 0.5rem;"><strong>${ub}</strong></div>`;
            }
            return `<div style="margin-bottom: 0.5rem;"><strong>${String(ub)}</strong></div>`;
        }).join('') 
        : '<div style="color: #9ca3af;">Sin ubicaciones</div>';
    
    // HTML de tallas
    let tallasHTML = '';
    if (totalTallas > 0) {
        const damaEntries = Object.entries(damaObj);
        const cabEntries = Object.entries(caballeroObj);
        const sobreEntries = Object.entries(sobremedidaObj);

        tallasHTML = `
            <div style="margin-top: 0.75rem;">
                <strong style="font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">TALLAS (${totalTallas})</strong>
                <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                    ${renderGrupoTallas('DAMA', damaEntries, 'background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;')}
                    ${renderGrupoTallas('CABALLERO', cabEntries, 'background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;')}
                    ${renderGrupoTallas('SOBREMEDIDA', sobreEntries, 'background: #fef3c7; color: #92400e; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;')}
                </div>
            </div>
        `;
    }
    
    // HTML de observaciones
    let observacionesHTML = '';
    if (datos.observaciones) {
        observacionesHTML = `
            <div style="margin-top: 0.75rem; padding: 0.5rem; background: #fef3c7; border-left: 2px solid #f59e0b; border-radius: 4px;">
                <strong style="font-size: 0.75rem; color: #92400e; display: block; margin-bottom: 0.25rem;">OBSERVACIONES</strong>
                <div style="color: #78350f; font-size: 0.8rem;">${datos.observaciones}</div>
            </div>
        `;
    }
    
    // HTML de Imagenes
    let imagenesHTML = '';
    
    // ?? PRIORIDAD: Usar imagenesFiles si est�n disponibles (para archivos que a�n no se subieron)
    // Esto evita depender de blob URLs antiguos que se invalidan
    let imagenesParaRenderizar = datos.imagenes || [];
    if (datos.imagenesFiles && Array.isArray(datos.imagenesFiles) && datos.imagenesFiles.length > 0) {
        console.log(`??? [RENDER-TARJETA-${tipo}] Usando imagenesFiles (${datos.imagenesFiles.length}) en lugar de imagenes (${imagenesParaRenderizar.length})`);
        imagenesParaRenderizar = datos.imagenesFiles;
    }
    
    if (imagenesParaRenderizar && imagenesParaRenderizar.length > 0) {
        // ?? CR�TICO: Filtrar Imagenes eliminadas usando imagenesEliminadas
        // imagenesEliminadas contiene null para Imagenes eliminadas, objeto para v�lidas
        // IMPORTANTE: imagenesEliminadas solo contiene las Imagenes ORIGINALES (de BD)
        // Las Imagenes nuevas (File objects) no est�n en imagenesEliminadas
        
        let imagenesValidas = [];
        
        // Si hay imagenesEliminadas, usarla para filtrar las Imagenes originales
        if (datos.imagenesEliminadas && datos.imagenesEliminadas.length > 0) {
            // Filtrar solo las Imagenes originales usando imagenesEliminadas
            // Las primeras N Imagenes corresponden a imagenesEliminadas
            const cantidadOriginales = datos.imagenesEliminadas.length;
            const imagenesOriginales = imagenesParaRenderizar.slice(0, cantidadOriginales);
            const imagenesNuevas = imagenesParaRenderizar.slice(cantidadOriginales);
            
            // Filtrar originales: solo incluir si no est� marcada como null en imagenesEliminadas
            const originalesFiltradas = imagenesOriginales.filter((img, idx) => {
                return datos.imagenesEliminadas[idx] !== null;
            });
            
            // Combinar: originales filtradas + todas las nuevas
            imagenesValidas = [...originalesFiltradas, ...imagenesNuevas];
            
            console.log(`??? [RENDER-TARJETA-${tipo}] Filtrando con imagenesEliminadas: ${imagenesValidas.length} v�lidas (${originalesFiltradas.length} originales + ${imagenesNuevas.length} nuevas) de ${imagenesParaRenderizar.length} totales`);
        } else {
            // Sin imagenesEliminadas: incluir todas las Imagenes v�lidas
            imagenesValidas = imagenesParaRenderizar.filter(img => img !== null && img !== undefined);
            console.log(`??? [RENDER-TARJETA-${tipo}] Sin imagenesEliminadas: ${imagenesValidas.length} Imagenes v�lidas`);
        }
        
        console.log(`??? [RENDER-TARJETA-${tipo}] Renderizando ${imagenesValidas.length} Imagenes`, {
            imagenesArray: imagenesValidas.map(img => ({
                tipo: img instanceof File ? 'File' : typeof img,
                nombre: img?.nombre || img?.name || 'sin-nombre',
                tienePreviewUrl: !!img?.previewUrl,
                tieneDataURL: !!img?.dataURL,
                tieneSrc: !!img?.src,
                tieneUrl: !!img?.url || !!img?.ruta_original
            }))
        });
        if (imagenesValidas.length > 0) {
            imagenesHTML = `
                <div style="margin-top: 0.75rem;">
                    <strong style="font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">Imagenes (${imagenesValidas.length})</strong>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        ${imagenesValidas.slice(0, 4).map((img, idx) => {
                            // Determinar la URL seg�n el tipo de objeto
                            let imgSrc = '';
                            if (img instanceof File) {
                                imgSrc = URL.createObjectURL(img);
                            } else if (img.file instanceof File) {
                                // Objeto con File embebido: regenerar blob URL fresco
                                if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                                    try { URL.revokeObjectURL(img.previewUrl); } catch(e) {}
                                }
                                imgSrc = URL.createObjectURL(img.file);
                                img.previewUrl = imgSrc; // actualizar referencia
                            } else if (img.previewUrl) {
                                // Imagen con preview (desde storage)
                                imgSrc = img.previewUrl;
                            } else if (img.dataURL) {
                                // Imagen con dataURL
                                imgSrc = img.dataURL;
                            } else if (img.src) {
                                imgSrc = img.src;
                            } else if (img.url) {
                                imgSrc = agregarStorageUrl(img.url);
                            } else if (img.ruta_original) {
                                imgSrc = agregarStorageUrl(img.ruta_original);
                            } else if (img.ruta_webp) {
                                imgSrc = agregarStorageUrl(img.ruta_webp);
                            } else if (img.ruta) {
                                imgSrc = agregarStorageUrl(img.ruta);
                            } else if (typeof img === 'string') {
                                imgSrc = agregarStorageUrl(img);
                            }
                            
                            console.log(`  [RENDER-TARJETA-${tipo}] Imagen ${idx}: ${typeof img} ? src="${imgSrc.substring(0, 100)}"`);
                            
                            return imgSrc ? `
                                <div style="position: relative; width: 70px; height: 70px; border-radius: 4px; overflow: hidden; border: 2px solid #e5e7eb; cursor: pointer;" 
                                     onclick="abrirGaleriaImagenesProceso('${tipo}', ${idx})"
                                     title="Click para ver galer�a">
                                    <img src="${imgSrc}" 
                                        style="width: 100%; height: 100%; object-fit: cover;" 
                                        alt="Imagen ${idx + 1}">
                                </div>
                            ` : '';
                        }).join('')}
                        ${imagenesValidas.length > 4 ? `
                            <div style="display: flex; align-items: center; justify-content: center; width: 70px; height: 70px; background: #f3f4f6; border-radius: 4px; border: 2px dashed #d1d5db; font-weight: 600; color: #6b7280; font-size: 0.75rem; cursor: pointer;"
                                 onclick="abrirGaleriaImagenesProceso('${tipo}')"
                                 title="Ver todas">
                                +${imagenesValidas.length - 4}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        } else {
            console.log(`?? [RENDER-TARJETA-${tipo}] Imagenes array existe pero est� vac�o`);
        }
    } else {
        console.log(`?? [RENDER-TARJETA-${tipo}] NO hay Imagenes en datos.imagenes`, {
            tieneImagenes: !!datos.imagenes,
            esArray: Array.isArray(datos.imagenes),
            longitud: datos.imagenes?.length || 0
        });
    }
    
    // --- Detectar modo "por_tallas" con datosExtendidos ---
    const esGeneralMode = datos.modoTallas === 'general' || datos.modoTallas === 'generico';
    const esEspecificoMode = datos.modoTallas === 'especifico';
    const datosExtendidos = datos.datosExtendidos || {};
    const tieneDetallePorTalla = esEspecificoMode && Object.keys(datosExtendidos).some(genero => 
        datosExtendidos[genero] && Object.keys(datosExtendidos[genero]).length > 0
    );

    // --- HTML de tallas con observaciones para modo GENERAL ---
    let tallasConObservacionesHTML = '';
    if (esGeneralMode && totalTallas > 0) {
        const tieneObservacionesPorTalla = Object.keys(datosExtendidos).some(genero => 
            datosExtendidos[genero] && Object.values(datosExtendidos[genero]).some(d => d.observaciones)
        );
        
        if (tieneObservacionesPorTalla) {
            const generosConfig = {
                dama: { titulo: 'DAMA', color: '#be185d', bg: '#fce7f3' },
                caballero: { titulo: 'CABALLERO', color: '#1d4ed8', bg: '#dbeafe' },
                sobremedida: { titulo: 'SOBREMEDIDA', color: '#92400e', bg: '#fef3c7' }
            };
            
            let tarjetasTallasGeneral = '';
            ['dama', 'caballero', 'sobremedida'].forEach(genero => {
                const tallasGenero = datos.tallas?.[genero] || {};
                const extendidosGenero = datosExtendidos[genero] || {};
                const entries = Object.entries(tallasGenero);
                if (entries.length === 0) return;
                
                const cfg = generosConfig[genero];
                entries.forEach(([tallaKey, cantidad]) => {
                    const detalle = extendidosGenero[tallaKey];
                    const observacion = detalle?.observaciones || '';
                    if (!observacion) return; // Solo mostrar si tiene observaciones
                    
                    tarjetasTallasGeneral += `
                        <div style="border: 1px solid ${cfg.bg}; border-radius: 6px; background: white; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.3rem;">
                            <div style="display: flex; align-items: center; gap: 0.3rem;">
                                <span style="font-weight: 700; font-size: 0.8rem; color: ${cfg.color};">${cfg.titulo} - ${formatearTallaKey(tallaKey)}</span>
                                <span style="font-size: 0.65rem; background: ${cfg.color}; color: white; padding: 0.1rem 0.3rem; border-radius: 9999px; font-weight: 700;">${cantidad}</span>
                            </div>
                            <div style="padding: 0.3rem 0.5rem; background: #fef3c7; border-left: 2px solid #f59e0b; border-radius: 4px; font-size: 0.7rem; color: #78350f;">${observacion}</div>
                        </div>
                    `;
                });
            });
            
            if (tarjetasTallasGeneral) {
                tallasConObservacionesHTML = `
                    <div style="margin-top: 0.75rem;">
                        <strong style="font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">OBSERVACIONES POR TALLA</strong>
                        <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                            ${tarjetasTallasGeneral}
                        </div>
                    </div>
                `;
            }
        }
    }

    let contenidoPorTallasHTML = '';
    if (tieneDetallePorTalla) {
        const generosConfig = {
            dama: { titulo: 'DAMA', color: '#6b7280', bg: '#f3f4f6', border: '#d1d5db', chipBg: '#e5e7eb' },
            caballero: { titulo: 'CABALLERO', color: '#1d4ed8', bg: '#eff6ff', border: '#93c5fd', chipBg: '#dbeafe' },
            sobremedida: { titulo: 'SOBREMEDIDA', color: '#92400e', bg: '#fffbeb', border: '#fcd34d', chipBg: '#fef3c7' }
        };

        let tarjetasTallas = '';
        let totalDetalle = 0;

        ['dama', 'caballero', 'sobremedida'].forEach(genero => {
            const tallasGenero = datosExtendidos[genero];
            if (!tallasGenero || Object.keys(tallasGenero).length === 0) return;

            const cfg = generosConfig[genero];
            const tallasData = datos.tallas?.[genero] || {};

            Object.entries(tallasGenero).forEach(([tallaKey, detalle]) => {
                totalDetalle++;
                
                // Buscar la cantidad: intentar primero con tallaKey tal cual, luego con espacios convertidos a guiones bajos
                let cantidad = tallasData[tallaKey];
                if (cantidad === undefined) {
                    // Si no encuentra, intentar con guiones bajos (por si fue guardado de forma diferente)
                    const tallasDataKeys = Object.keys(tallasData);
                    const keyAdjustada = tallasDataKeys.find(k => k.replace(/_/g, ' ') === tallaKey.replace(/_/g, ' '));
                    cantidad = keyAdjustada ? tallasData[keyAdjustada] : 0;
                }
                if (cantidad === undefined) cantidad = 0;
                
                const tallaDisplay = formatearTallaKey(tallaKey);

                // Ubicaciones - cada l�nea en su propia fila
                const ubicsTalla = (detalle.ubicaciones || []).filter(u => u);
                // Expandir: si una ubicaci�n tiene saltos de l�nea, separar en l�neas individuales
                const lineasUbic = [];
                ubicsTalla.forEach(u => {
                    String(u).split(/\n/).forEach(linea => {
                        const l = linea.trim();
                        if (l) lineasUbic.push(l);
                    });
                });
                const ubicsHTML = lineasUbic.length > 0
                    ? `<div style="display: flex; flex-direction: column; gap: 0.2rem; width: 100%;">${lineasUbic.map(l => `<span style="background: ${cfg.chipBg}; color: ${cfg.color}; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; display: block;">${l}</span>`).join('')}</div>`
                    : '<span style="color: #9ca3af; font-size: 0.75rem;">Sin ubicaciones</span>';

                // Observaciones
                const obsTalla = detalle.observaciones || '';
                const obsHTML = obsTalla
                    ? `<div style="padding: 0.3rem 0.5rem; background: #fef3c7; border-left: 2px solid #f59e0b; border-radius: 4px; font-size: 0.75rem; color: #78350f;">${obsTalla}</div>`
                    : '';

                // ?? PRIORIDAD: Combinar Imagenes existentes con Files nuevos
                let imgsTalla = [];
                
                // 1. Agregar Imagenes existentes (URLs del servidor)
                if (detalle.imagenes && Array.isArray(detalle.imagenes)) {
                    imgsTalla = detalle.imagenes.filter(img => {
                        if (!img) return false;
                        if (typeof img === 'string') {
                            return !img.startsWith('blob:') && !img.startsWith('data:');
                        }
                        return true;
                    });
                }
                
                // 2. Agregar Files nuevos (tienen prioridad)
                if (detalle.imagenesFiles && Array.isArray(detalle.imagenesFiles) && detalle.imagenesFiles.length > 0) {
                    console.log(`??? [RENDER-TALLA-${genero}-${tallaKey}] Agregando imagenesFiles (${detalle.imagenesFiles.length}) a imgsTalla (${imgsTalla.length} existentes)`);
                    imgsTalla = [...imgsTalla, ...detalle.imagenesFiles];
                } else {
                    console.log(`??? [RENDER-TALLA-${genero}-${tallaKey}] Usando imagenes directamente:`, {
                        imagenesCount: imgsTalla.length,
                        imagenesFilesExists: !!detalle.imagenesFiles,
                        imagenesFilesCount: detalle.imagenesFiles?.length || 0,
                        imagenesFilesIsArray: Array.isArray(detalle.imagenesFiles),
                        imagenesSample: imgsTalla.slice(0, 2).map(img => typeof img === 'string' ? img.substring(0, 50) : (img instanceof File ? 'File:' + img.name : typeof img))
                    });
                }
                
                // Compatibilidad con campo antiguo 'imagen' singular
                if (imgsTalla.length === 0 && detalle.imagen) {
                    imgsTalla.push(detalle.imagen);
                }
                let imgHTML = '';
                if (imgsTalla.length > 0) {
                    imgHTML = `<div style="display:flex; flex-wrap:wrap; gap:0.25rem; margin-top:0.2rem;">` +
                        imgsTalla.map(src => {
                            let imgSrc = src;
                            if (src instanceof File) {
                                imgSrc = URL.createObjectURL(src);
                            } else if (typeof src === 'object' && src !== null) {
                                // Extraer URL del objeto imagen
                                imgSrc = src.ruta_webp || src.url || src.ruta_original || src.ruta || src.previewUrl || src.src || String(src);
                            } else if (typeof src === 'string') {
                                // Si es una blob URL, usarla directamente; si no, agregar /storage/
                                imgSrc = src.startsWith('blob:') ? src : agregarStorageUrl(src);
                            }
                            return `<div style="width:40px;height:40px;border-radius:4px;overflow:hidden;border:1.5px solid ${cfg.border};flex-shrink:0;">
                                <img src="${imgSrc}" style="width:100%;height:100%;object-fit:cover;">
                            </div>`;
                        }).join('') + `</div>`;
                }

                tarjetasTallas += `
                    <div style="border: 1.5px solid ${cfg.border}; border-radius: 8px; background: ${cfg.bg}; padding: 0.6rem; display: flex; flex-direction: column; gap: 0.4rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                <span style="font-weight: 800; font-size: 0.85rem; color: ${cfg.color};">${cfg.titulo} - ${tallaDisplay}</span>
                                <span style="font-size: 0.7rem; background: ${cfg.color}; color: white; padding: 0.1rem 0.4rem; border-radius: 9999px; font-weight: 700;">${cantidad} und</span>
                            </div>
                        </div>
                        ${(datos.modoTallas === 'general' || datos.modoTallas === 'generico') ? '' : `
                        <div style="display: flex; flex-direction: column; gap: 0.2rem;">
                            <span style="font-size: 0.7rem; font-weight: 700; color: #374151;">Ubic:</span>
                            ${ubicsHTML}
                        </div>
                        `}
                        ${obsHTML}
                        ${imgHTML}
                    </div>
                `;
            });
        });

        if (totalDetalle > 0) {
            contenidoPorTallasHTML = `
                <div>
                    <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.5rem;">
                        <span class="material-symbols-rounded" style="font-size: 1rem; color: #6b7280;">view_list</span>
                        <strong style="font-size: 0.875rem;">DETALLE POR TALLA (${totalDetalle})</strong>
                        <span style="font-size: 0.65rem; background: #8b5cf6; color: white; padding: 0.1rem 0.4rem; border-radius: 9999px; font-weight: 700;">${esEspecificoMode ? 'Por Tallas (Específico)' : 'Por Tallas'}</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        ${tarjetasTallas}
                    </div>
                </div>
            `;
        }
    }

    // --- Contenido normal vs por tallas ---
    let ubicacionGeneralHTML = '';
    let fotosGeneralesHTML = '';
    
    // Mapear ubicaciones y fotos desde la BD cuando viene de un proceso existente
    const ubicacionesDisplay = datos.ubicacionGeneral || (datos.ubicaciones && (
        Array.isArray(datos.ubicaciones) 
            ? datos.ubicaciones.filter(u => u && typeof u === 'string').join(', ')
            : String(datos.ubicaciones)
    )) || '';
    
    // ?? PRIORIDAD: Combinar Imagenes existentes (del servidor) con Files nuevos (no subidos)
    let fotosDisplay = [];
    
    // 1. Agregar Imagenes existentes del servidor (URLs v�lidas)
    if (datos.fotosGenerales && Array.isArray(datos.fotosGenerales) && datos.fotosGenerales.length > 0) {
        fotosDisplay = [...datos.fotosGenerales];
    } else if (datos.imagenes && Array.isArray(datos.imagenes) && datos.imagenes.length > 0) {
        // Filtrar blob URLs de imagenes
        fotosDisplay = datos.imagenes.filter(img => {
            if (typeof img === 'string') {
                return !img.startsWith('blob:') && !img.startsWith('data:');
            }
            return true;
        });
    }
    
    // 2. Agregar Files nuevos (tienen prioridad para renderizado)
    if (datos.fotosGeneralesFiles && Array.isArray(datos.fotosGeneralesFiles) && datos.fotosGeneralesFiles.length > 0) {
        console.log(`??? [RENDER-TARJETA-${tipo}] Agregando fotosGeneralesFiles (${datos.fotosGeneralesFiles.length}) a fotosDisplay (${fotosDisplay.length} existentes)`);
        fotosDisplay = [...fotosDisplay, ...datos.fotosGeneralesFiles];
    } else if (datos.imagenesFiles && Array.isArray(datos.imagenesFiles) && datos.imagenesFiles.length > 0) {
        console.log(`??? [RENDER-TARJETA-${tipo}] Agregando imagenesFiles (${datos.imagenesFiles.length}) a fotosDisplay (${fotosDisplay.length} existentes)`);
        fotosDisplay = [...fotosDisplay, ...datos.imagenesFiles];
    }
    
    console.log(`?? [GENERAR-TARJETA-${tipo.toUpperCase()}] Mapeo de ubicaciones y fotos:`, {
        tipo: tipo,
        modoTallas: datos.modoTallas,
        datosUbicacionGeneral: datos.ubicacionGeneral,
        datosUbicaciones: datos.ubicaciones,
        ubicacionesDisplay: ubicacionesDisplay,
        datosImagenes: Array.isArray(datos.imagenes) ? `Array[${datos.imagenes.length}]` : typeof datos.imagenes,
        fotosGenerales: Array.isArray(datos.fotosGenerales) ? `Array[${datos.fotosGenerales.length}]` : typeof datos.fotosGenerales,
        fotosGeneralesFiles: Array.isArray(datos.fotosGeneralesFiles) ? `Array[${datos.fotosGeneralesFiles.length}]` : typeof datos.fotosGeneralesFiles,
        fotosDisplay: Array.isArray(fotosDisplay) ? `Array[${fotosDisplay.length}]` : fotosDisplay,
        debeRenderizarUbicacionGeneral: (datos.modoTallas === 'general' || datos.modoTallas === 'generico') && !!ubicacionesDisplay,
        debeRenderizarFotosGenerales: (datos.modoTallas === 'general' || datos.modoTallas === 'generico') && fotosDisplay && fotosDisplay.length > 0
    });
    
    // Renderizar ubicaci�n general si est� en modo "general"
    if ((datos.modoTallas === 'general' || datos.modoTallas === 'generico') && ubicacionesDisplay) {
        ubicacionGeneralHTML = `
            <div style="background: #f3f4f6; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem; border: 1px solid #d1d5db;">
                <strong style="font-size: 0.8rem; color: #333; display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.35rem;">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">location_on</span>UBICACI�N GENERAL
                </strong>
                <span style="color: #6b7280; font-size: 0.8rem; line-height: 1.4;">${ubicacionesDisplay}</span>
            </div>
        `;
    }
    
    // Renderizar fotos generales si est� en modo "general"
    if ((datos.modoTallas === 'general' || datos.modoTallas === 'generico') && fotosDisplay && fotosDisplay.length > 0) {
        const fotosHTML = fotosDisplay.map((src, idx) => {
            let imgSrc = src;
            if (src instanceof File) {
                imgSrc = URL.createObjectURL(src);
            } else if (typeof src === 'object' && src !== null) {
                // Desde BD: ruta_webp, ruta_original, url, ruta
                imgSrc = src.ruta_webp || src.url || src.ruta_original || src.ruta || src.previewUrl || src.src || String(src);
            } else if (typeof src === 'string') {
                imgSrc = src.startsWith('blob:') ? src : agregarStorageUrl(src);
            }
            return `<div style="width:50px;height:50px;border-radius:4px;overflow:hidden;border:1px solid #d1d5db;flex-shrink:0;">
                <img src="${imgSrc}" style="width:100%;height:100%;object-fit:cover;">
            </div>`;
        }).join('');
        
        fotosGeneralesHTML = `
            <div style="margin-bottom: 0.75rem;">
                <strong style="font-size: 0.8rem; color: #333; display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.35rem;">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">photo_camera</span>FOTOS GENERALES (${fotosDisplay.length})
                </strong>
                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                    ${fotosHTML}
                </div>
            </div>
        `;
    }

    // --- Construir contenido seg�n el modo ---
    let contenidoHTML;
    if (esGeneralMode) {
        // Modo GENERAL: ubicaci�n general + fotos generales + tallas + observaciones por talla
        contenidoHTML = `${ubicacionGeneralHTML}${fotosGeneralesHTML}${tallasHTML}${tallasConObservacionesHTML}`;
    } else if (contenidoPorTallasHTML) {
        // Modo ESPEC�FICO: ubicaci�n general (si existe) + fotos generales (si existen) + detalles por talla
        contenidoHTML = `${ubicacionGeneralHTML}${fotosGeneralesHTML}${contenidoPorTallasHTML}`;
    } else {
        // Modo GEN�RICO (sin tallas o formato antiguo)
        contenidoHTML = `<div style="margin-bottom: 0.75rem;">
                <strong style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">UBICACIONES</strong>
                <div>${ubicacionesHTML}</div>
            </div>
            ${tallasHTML}
            ${observacionesHTML}
            ${imagenesHTML}`;
    }
    
    return `
        <div class="tarjeta-proceso" data-tipo="${tipo}" style="
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        ">
            <!-- Header -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">${icono}</span>
                    <div>
                        <strong style="color: #111827; font-size: 1rem; display: block;">${nombre}</strong>
                        <span style="color: #9ca3af; font-size: 0.7rem;">${tipo}</span>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" onclick="editarProcesoDesdeModal('${tipo}')" 
                        style="background: #f3f4f6; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer; display: flex; align-items: center;" 
                        title="Editar">
                        <i class="fas fa-edit" style="font-size: 1rem; color: #6b7280;"></i>
                    </button>
                    <button type="button" onclick="eliminarTarjetaProceso('${tipo}')" 
                        style="background: #fee2e2; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer; display: flex; align-items: center;" 
                        title="Eliminar">
                        <i class="fas fa-trash-alt" style="font-size: 1rem; color: #dc2626;"></i>
                    </button>
                </div>
            </div>
            
            <!-- Contenido -->
            <div style="color: #374151; font-size: 0.875rem;">
                ${contenidoHTML}
            </div>
        </div>
    `;
}


    globalThis.ProcesoCardRendererService = Object.freeze({
        generarTarjetaProceso
    });
})();

