/**
 * ProcesosBuilder - Construye sección de procesos
 * 
 * Responsabilidad: Generar HTML de procesos expandible
 * Patrón: Builder + Template Method
 */

class ProcesosBuilder {
    static ICONOS = {
        'reflectivo': '<i class="fas fa-lightbulb" style="color: #f59e0b;"></i>',
        'bordado': '<i class="fas fa-gem" style="color: #1e40af;"></i>',
        'estampado': '<i class="fas fa-paint-brush" style="color: #ec4899;"></i>',
        'dtf': '<i class="fas fa-print" style="color: #06b6d4;"></i>',
        'sublimado': '<i class="fas fa-tint" style="color: #3b82f6;"></i>'
    };

    static construir(prenda, indice) {
        console.log('[ProcesosBuilder] 🏗️ Construyendo procesos para prenda:', {
            nombre: prenda.nombre_prenda,
            indice: indice,
            tieneProcesos: !!prenda.procesos,
            procesos: prenda.procesos
        });

        const procesos = prenda.procesos || {};
        console.log('[ProcesosBuilder]   - Procesos extraídos:', procesos);
        console.log('[ProcesosBuilder]   - Tipo:', typeof procesos, 'esArray:', Array.isArray(procesos));
        
        const procesosConDatos = Object.entries(procesos).filter(
            ([tipoProceso, proc]) => {
                const valido = proc && (proc.datos !== null || proc.tipo);
                console.log(`[ProcesosBuilder]   - Proceso "${tipoProceso}":`, {
                    proceso: proc,
                    tieneDatos: !!(proc && proc.datos),
                    tieneTipo: !!(proc && proc.tipo),
                    esValido: valido
                });
                return valido;
            }
        );
        
        console.log('[ProcesosBuilder]   - Procesos válidos:', procesosConDatos.length);

        if (procesosConDatos.length === 0) {
            console.log('[ProcesosBuilder]    No hay procesos válidos, retornando vacío');
            return '';
        }

        console.log('[ProcesosBuilder]   - Generando HTML para cada proceso...');
        const itemsHTML = procesosConDatos.map(([tipoProceso, proceso]) => {
            console.log(`[ProcesosBuilder]     - Construyendo proceso "${tipoProceso}"`);
            const html = this._construirItemProceso(tipoProceso, proceso);
            console.log(`[ProcesosBuilder]     - HTML generado (${html.length} caracteres)`);
            return html;
        });
        
        console.log('[ProcesosBuilder]   - itemsHTML es array:', Array.isArray(itemsHTML), 'length:', itemsHTML?.length);
        console.log('[ProcesosBuilder]   - Llamando .join() sobre:', itemsHTML);
        
        try {
            const htmlUnido = itemsHTML.join('');
            console.log('[ProcesosBuilder]   HTML unido exitosamente');
        } catch (joinError) {
            console.error('[ProcesosBuilder]  ERROR EN JOIN:', joinError);
            console.error('[ProcesosBuilder]  itemsHTML era:', itemsHTML);
            console.error('[ProcesosBuilder]  Stack:', joinError.stack);
            throw joinError; // Re-lanzar para ver el error completo
        }

        return `
            <div class="seccion-expandible procesos-section">
                <button class="seccion-expandible-header" type="button" data-section="procesos" data-prenda-index="${indice}">
                    <h4>Procesos <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280;">(${procesosConDatos.length})</span></h4>
                    <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="seccion-expandible-content procesos-content">
                    <div style="padding: 1rem;">
                        ${htmlUnido}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Construir item individual de proceso
     * @private
     */
    static _construirItemProceso(tipoProceso, proceso) {
        const datos = proceso.datos || {};
        const icono = this.ICONOS[tipoProceso] || '<i class="fas fa-cog"></i>';
        const nombreProceso = tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1);
        const modoTallas = proceso.modoTallas || datos.modoTallas || datos.modo_tallas || 'generico';
        const esPorTallas = !!(datos.datosExtendidos) || modoTallas === 'por_tallas' || modoTallas === 'especifico';
        const esGeneral = modoTallas === 'general' || modoTallas === 'generico';

        // ─── Modo POR TALLAS ESPECÍFICO ───
        if (esPorTallas && datos.datosExtendidos && !esGeneral) {
            let porTallasHTML = '';
            const generos = { dama: { label: 'DAMA', icon: '<i class="fas fa-female"></i>', color: '#be185d', bg: '#fdf2f8', border: '#fbcfe8' }, caballero: { label: 'CABALLERO', icon: '<i class="fas fa-male"></i>', color: '#1d4ed8', bg: '#eff6ff', border: '#bfdbfe' } };

            Object.entries(generos).forEach(([genero, cfg]) => {
                const tallasGenero = datos.datosExtendidos[genero];
                if (!tallasGenero || Object.keys(tallasGenero).length === 0) return;

                Object.entries(tallasGenero).forEach(([tallaKey, datosTalla]) => {
                    const parts = String(tallaKey).split('__');
                    const tallaDisplay = parts[0] || tallaKey;
                    const cantidad = datos.tallas?.[genero]?.[tallaKey] ?? '';

                    let ubicHTML = '';
                    if (datosTalla.ubicaciones && datosTalla.ubicaciones.length > 0) {
                        const chips = datosTalla.ubicaciones.map(u => {
                            const texto = typeof u === 'string' ? u : (u?.ubicacion || '');
                            return texto ? `<span style="background: ${cfg.bg}; color: ${cfg.color}; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">${texto}</span>` : '';
                        }).filter(Boolean).join('');
                        if (chips) ubicHTML = `<div style="margin-top: 0.35rem;"><span style="font-size: 0.75rem; color: #6b7280; font-weight: 600;"><i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>Ubicaciones:</span><div style="display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.2rem;">${chips}</div></div>`;
                    }

                    let obsHTML = '';
                    if (datosTalla.observaciones) {
                        obsHTML = `<div style="margin-top: 0.35rem; font-size: 0.8rem; color: #374151; background: #f9fafb; padding: 0.35rem 0.5rem; border-radius: 4px; border-left: 2px solid ${cfg.color};"><i class="fas fa-sticky-note" style="margin-right: 0.25rem; color: ${cfg.color};"></i>${datosTalla.observaciones}</div>`;
                    }

                    let imgHTML = '';
                    const imgs = datosTalla.imagenes || [];
                    if (imgs.length > 0) {
                        const thumbs = imgs.map(img => {
                            let src = '';
                            if (typeof img === 'string') src = img;
                            else if (img instanceof File) src = URL.createObjectURL(img);
                            else if (img?.url || img?.previewUrl || img?.blobUrl) src = img.url || img.previewUrl || img.blobUrl;
                            return src ? `<img src="${src}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid ${cfg.border};">` : '';
                        }).filter(Boolean).join('');
                        if (thumbs) imgHTML = `<div style="display: flex; gap: 0.3rem; margin-top: 0.35rem;">${thumbs}</div>`;
                    }

                    porTallasHTML += `
                        <div style="border: 1px solid ${cfg.border}; border-radius: 8px; padding: 0.6rem; background: ${cfg.bg}; min-width: 180px; flex: 1;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.3rem;">
                                <div style="display: flex; align-items: center; gap: 0.35rem;">
                                    <span style="color: ${cfg.color};">${cfg.icon}</span>
                                    <span style="font-weight: 900; color: #0f172a;">${tallaDisplay}</span>
                                </div>
                                ${cantidad ? `<span style="background: ${cfg.color}; color: white; padding: 0.1rem 0.45rem; border-radius: 6px; font-weight: 900; font-size: 0.75rem;">${cantidad}</span>` : ''}
                            </div>
                            ${ubicHTML}
                            ${obsHTML}
                            ${imgHTML}
                        </div>
                    `;
                });
            });

            return `
                <div style="background: white; border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 2px solid #0ea5e9;">
                        <span style="font-size: 1.5rem;">${icono}</span>
                        <h4 style="margin: 0; color: #0369a1; font-size: 1.1rem; font-weight: 700;">${nombreProceso}</h4>
                        <span style="background: #7c3aed; color: white; padding: 0.15rem 0.5rem; border-radius: 12px; font-size: 0.7rem; font-weight: 700;">Por Tallas</span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                        ${porTallasHTML}
                    </div>
                </div>
            `;
        }

        // ─── Modo GENERAL ───
        if (esGeneral) {
            return this._construirModoGeneral(tipoProceso, nombreProceso, icono, datos);
        }

        // ─── Modo GENÉRICO (fallback para formatos antiguos) ───
        const ubicacionesHTML = this._construirUbicaciones(datos);
        const tallasHTML = this._construirTallasDeProcesos(datos);
        const observacionesHTML = this._construirObservaciones(datos);
        const imagenHTML = this._construirImagenes(datos);

        return `
            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 2px solid #0ea5e9;">
                    <span style="font-size: 1.5rem;">${icono}</span>
                    <h4 style="margin: 0; color: #0369a1; font-size: 1.1rem; font-weight: 700;">${nombreProceso}</h4>
                </div>
                
                ${ubicacionesHTML}
                ${tallasHTML}
                ${observacionesHTML}
                ${imagenHTML}
            </div>
        `;
    }

    /**
     * Construir modo GENERAL con ubicación general, fotos generales y tallas
     * @private
     */
    static _construirModoGeneral(tipoProceso, nombreProceso, icono, datos) {
        // Ubicación General
        const ubicacionGeneral = datos.ubicacionGeneral || (datos.ubicaciones && (
            Array.isArray(datos.ubicaciones) 
                ? datos.ubicaciones.filter(u => u && typeof u === 'string').join(', ')
                : String(datos.ubicaciones)
        )) || '';
        
        let ubicacionGeneralHTML = '';
        if (ubicacionGeneral) {
            ubicacionGeneralHTML = `
                <div style="margin-bottom: 0.75rem;">
                    <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                        <i class="fas fa-location-arrow" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Ubicaciones:
                    </strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        <span style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; display: inline-block; margin: 0.25rem;">
                            <i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>${ubicacionGeneral}
                        </span>
                    </div>
                </div>
            `;
        }

        // Tallas agrupadas por género
        const tallasGenerales = datos.tallas || {};
        let tallasHTML = '';
        
        const generosConfig = {
            dama: { label: 'DAMA', icon: '<i class="fas fa-female" style="color: #be185d;"></i>', color: '#be185d' },
            caballero: { label: 'CABALLERO', icon: '<i class="fas fa-male" style="color: #1d4ed8;"></i>', color: '#1d4ed8' },
            sobremedida: { label: 'SOBREMEDIDA', icon: '<i class="fas fa-ruler" style="color: #92400e;"></i>', color: '#92400e' }
        };
        
        if (Object.keys(tallasGenerales).length > 0) {
            let tallasContent = '';
            Object.entries(generosConfig).forEach(([genero, cfg]) => {
                const tallasGenero = tallasGenerales[genero];
                if (!tallasGenero || Object.keys(tallasGenero).length === 0) return;
                
                let tallaCards = '';
                Object.entries(tallasGenero).forEach(([tallaKey, cantidad]) => {
                    const parts = String(tallaKey).split('__');
                    const talla = parts[0] || tallaKey;
                    
                    tallaCards += `
                        <div style="min-width: 140px; border: 1px solid #bfdbfe; border-radius: 8px; padding: 0.6rem; background: #ffffff;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.35rem;">
                                <div style="display: flex; align-items: center; gap: 0.35rem;">
                                    <span style="font-weight: 900; color: #0f172a;">${talla}</span>
                                </div>
                                <span style="background: ${cfg.color}; color: white; padding: 0.1rem 0.45rem; border-radius: 6px; font-weight: 900; font-size: 0.75rem;">${cantidad}</span>
                            </div>
                        </div>
                    `;
                });
                
                if (tallaCards) {
                    tallasContent += `
                        <div style="margin-top: 0.6rem;">
                            <div style="display: flex; align-items: center; gap: 0.4rem; font-weight: 900; color: ${cfg.color}; margin-bottom: 0.4rem;">
                                ${cfg.icon}
                                <span>${cfg.label}</span>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                                ${tallaCards}
                            </div>
                        </div>
                    `;
                }
            });
            
            if (tallasContent) {
                tallasHTML = `<div style="margin-top: 0.75rem;">${tallasContent}</div>`;
            }
        }

        // Observaciones generales
        let observacionesGeneralesHTML = '';
        if (datos.observaciones) {
            observacionesGeneralesHTML = `
                <div style="margin-top: 0.75rem; padding: 0.75rem; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px;">
                    <strong style="color: #92400e; display: block; margin-bottom: 0.25rem;">
                        <i class="fas fa-sticky-note" style="margin-right: 0.5rem;"></i>Observaciones:
                    </strong>
                    <span style="color: #78350f; font-size: 0.9rem;">${datos.observaciones}</span>
                </div>
            `;
        }

        // Fotos Generales
        const fotosGenerales = datos.fotosGenerales || datos.imagenes || [];
        let fotosGeneralesHTML = '';
        if (fotosGenerales && fotosGenerales.length > 0) {
            const fotosHTML = fotosGenerales.map((img, idx) => {
                let src = '';
                if (img instanceof File) {
                    src = URL.createObjectURL(img);
                } else if (typeof img === 'object' && img !== null) {
                    src = img.previewUrl || img.dataURL || img.src || img.url || img.blobUrl || img.ruta_webp || img.ruta_original || img.ruta || '';
                } else if (typeof img === 'string') {
                    src = img.startsWith('blob:') || img.startsWith('http') || img.startsWith('/') ? img : '/storage/' + img;
                }
                
                return src ? `<img src="${src}" alt="Imagen ${nombreProceso}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; cursor: pointer;" onclick="window.mostrarImagenProcesoGrande('${src}')">` : '';
            }).filter(Boolean).join('');
            
            if (fotosHTML) {
                fotosGeneralesHTML = `
                    <div style="margin-top: 0.75rem;">
                        <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                            <i class="fas fa-images" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Imágenes (${fotosGenerales.length}):
                        </strong>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            ${fotosHTML}
                        </div>
                    </div>
                `;
            }
        }

        // Observaciones por talla (si existen)
        const datosExtendidos = datos.datosExtendidos || {};
        let observacionesPorTallaHTML = '';
        
        const tieneObservacionesPorTalla = Object.keys(datosExtendidos).some(genero => 
            datosExtendidos[genero] && Object.values(datosExtendidos[genero]).some(d => d.observaciones)
        );
        
        if (tieneObservacionesPorTalla) {
            let tarjetasObs = '';
            Object.entries(generosConfig).forEach(([genero, cfg]) => {
                const extendidosGenero = datosExtendidos[genero] || {};
                const tallasGenero = tallasGenerales[genero] || {};
                
                Object.entries(extendidosGenero).forEach(([tallaKey, detalle]) => {
                    const observacion = detalle?.observaciones || '';
                    if (!observacion) return;
                    
                    const parts = String(tallaKey).split('__');
                    const talla = parts[0] || tallaKey;
                    const cantidad = tallasGenero[tallaKey] || 0;
                    
                    tarjetasObs += `
                        <div style="border: 1px solid #fce7f3; border-radius: 6px; background: white; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.3rem;">
                            <div style="display: flex; align-items: center; gap: 0.3rem;">
                                <span style="font-weight: 700; font-size: 0.8rem; color: ${cfg.color};">${cfg.label} - ${talla}</span>
                                <span style="font-size: 0.65rem; background: ${cfg.color}; color: white; padding: 0.1rem 0.3rem; border-radius: 9999px; font-weight: 700;">${cantidad}</span>
                            </div>
                            <div style="padding: 0.3rem 0.5rem; background: #fef3c7; border-left: 2px solid #f59e0b; border-radius: 4px; font-size: 0.7rem; color: #78350f;">${observacion}</div>
                        </div>
                    `;
                });
            });
            
            if (tarjetasObs) {
                observacionesPorTallaHTML = `
                    <div style="margin-top: 0.75rem;">
                        <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                            <i class="fas fa-sticky-note" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Observaciones por Talla:
                        </strong>
                        <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                            ${tarjetasObs}
                        </div>
                    </div>
                `;
            }
        }

        return `
            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 2px solid #0ea5e9;">
                    <span style="font-size: 1.5rem;">${icono}</span>
                    <h4 style="margin: 0; color: #0369a1; font-size: 1.1rem; font-weight: 700;">${nombreProceso}</h4>
                </div>
                
                ${ubicacionGeneralHTML}
                ${tallasHTML}
                ${observacionesGeneralesHTML}
                ${fotosGeneralesHTML}
                ${observacionesPorTallaHTML}
            </div>
        `;
    }

    /**
     * Construir sección de ubicaciones
     * Maneja tanto strings como objetos {ubicacion: "texto", descripcion: "texto"}
     * @private
     */
    static _construirUbicaciones(datos) {
        if (!datos.ubicaciones || datos.ubicaciones.length === 0) {
            return '';
        }

        const ubicacionesHTML = datos.ubicaciones
            .map(ub => {
                // Extraer texto según el tipo de dato
                const texto = typeof ub === 'object' && ub !== null && ub.ubicacion 
                    ? ub.ubicacion 
                    : (typeof ub === 'string' ? ub : '');
                
                // Si no hay texto válido, no renderizar
                if (!texto) return '';
                
                return `<span style="background: #dbeafe; color: #0369a1; padding: 0.4rem 0.8rem; border-radius: 16px; font-size: 0.85rem; font-weight: 600;">${texto}</span>`;
            })
            .filter(html => html) // Eliminar spans vacíos
            .join('');

        // Si no hay ubicaciones válidas después del filtrado, retornar vacío
        if (!ubicacionesHTML) {
            return '';
        }

        return `
            <div style="margin-bottom: 0.75rem;">
                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Ubicaciones:
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    ${ubicacionesHTML}
                </div>
            </div>
        `;
    }

    /**
     * Construir sección de tallas de proceso
     * @private
     */
    static _construirTallasDeProcesos(datos) {
        if (!datos.tallas || Object.keys(datos.tallas).length === 0) {
            return '';
        }

        let generoHTML = '';

        Object.entries(datos.tallas).forEach(([genero, tallasData]) => {
            if (!tallasData || !tallasData.tallas || tallasData.tallas.length === 0) return;

            const tallasHTML = tallasData.tallas
                .map(talla => `<span style="background: #fef3c7; color: #92400e; padding: 0.3rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">${talla}</span>`)
                .join('');

            generoHTML += `
                <div style="margin-bottom: 0.5rem;">
                    <strong style="color: #0369a1; font-size: 0.9rem;">${genero}:</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.25rem;">
                        ${tallasHTML}
                    </div>
                </div>
            `;
        });

        return `
            <div style="margin-bottom: 0.75rem;">
                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-ruler" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Tallas:
                </strong>
                ${generoHTML}
            </div>
        `;
    }

    /**
     * Construir sección de observaciones
     * @private
     */
    static _construirObservaciones(datos) {
        if (!datos.observaciones) {
            return '';
        }

        return `
            <div style="margin-bottom: 0.75rem; background: #eff6ff; padding: 0.75rem; border-radius: 6px; border-left: 3px solid #0ea5e9;">
                <strong style="color: #0369a1; display: block; margin-bottom: 0.25rem;">
                    <i class="fas fa-sticky-note" style="margin-right: 0.5rem;"></i>Observaciones:
                </strong>
                <p style="margin: 0; color: #374151; font-size: 0.9rem;">${datos.observaciones}</p>
            </div>
        `;
    }

    /**
     * Construir sección de imágenes
     * @private
     */
    static _construirImagenes(datos) {
        const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
        if (imagenes.length === 0) {
            return '';
        }

        const imagenesHTML = imagenes
            .map((img, idx) => {
                // Intentar obtener URL directamente del objeto
                let url = null;
                
                // Si ImageProcessor está disponible, usarlo
                if (typeof ImageProcessor !== 'undefined' && ImageProcessor.procesarImagen) {
                    url = ImageProcessor.procesarImagen(img);
                } else {
                    // Fallback: procesar manualmente si ImageProcessor no está disponible
                    if (typeof img === 'string') {
                        url = img;
                    } else if (img && typeof img === 'object') {
                        // Intentar obtener URL de múltiples propiedades
                        url = img.previewUrl || img.dataURL || img.src || img.url || img.blobUrl || null;
                    }
                }
                
                console.log(`[ProcesosBuilder] 🖼️ Procesando imagen ${idx}:`, {tipo: typeof img, tieneUrl: !!url, urlPreview: url ? url.substring(0, 50) : 'null'});
                
                return url ? `<img src="${url}" alt="Proceso ${idx}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 2px solid #e5e7eb; cursor: pointer;" />` : '';
            })
            .filter(html => html)
            .join('');

        return `
            <div style="margin-top: 0.75rem;">
                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-images" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Imágenes:
                </strong>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    ${imagenesHTML}
                </div>
            </div>
        `;
    }
}

window.ProcesosBuilder = ProcesosBuilder;

