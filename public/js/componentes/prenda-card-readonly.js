/**
 * TARJETA DE PRENDA - SOLO LECTURA (Read-Only)
 * 
 * Componente visual para mostrar una prenda en el formulario de pedidos
 * - Foto expandible en galería modal
 * - 3 secciones expandibles: Variaciones, Tallas, Procesos
 * - Menú contextual: Editar, Eliminar
 * - Totalmente de solo lectura hasta usar botón Editar
 */

/**
 * Generar HTML de tarjeta de prenda (solo lectura)
 * @param {Object} prenda - Objeto de prenda
 * @param {number} indice - Índice de la prenda en la lista
 * @returns {string} HTML de la tarjeta
 */
function generarTarjetaPrendaReadOnly(prenda, indice) {
    console.log('🔍 DEBUG: generarTarjetaPrendaReadOnly - Prenda recibida:', prenda);
    
    // Usar las propiedades correctas
    const imagenes = prenda.imagenes || prenda.fotos || [];
    
    // Convertir File objects a blob URLs
    let fotoPrincipal = null;
    if (imagenes.length > 0) {
        const img = imagenes[0];
        //  Primero intentar usar blobUrl si ya existe (creado al guardar)
        if (img && img.blobUrl && typeof img.blobUrl === 'string') {
            console.log('🔄 Usando blob URL ya creado');
            fotoPrincipal = img.blobUrl;
        }
        // Si es un objeto con propiedad 'file', es un File object
        else if (img && img.file instanceof File) {
            console.log('🔄 Convirtiendo File object a blob URL');
            fotoPrincipal = URL.createObjectURL(img.file);
        } else if (img instanceof File) {
            // Si es directamente un File
            console.log('🔄 File directo, convirtiendo a blob URL');
            fotoPrincipal = URL.createObjectURL(img);
        } else if (typeof img === 'string') {
            // Si ya es una URL
            fotoPrincipal = img;
        } else if (img && img.imagenes && Array.isArray(img.imagenes) && img.imagenes[0]) {
            // Si es un objeto con array de imagenes (como las telas)
            const innerImg = img.imagenes[0];
            if (innerImg instanceof File) {
                console.log('🔄 Imagen anidada es File, convirtiendo');
                fotoPrincipal = URL.createObjectURL(innerImg);
            } else if (innerImg.blobUrl) {
                fotoPrincipal = innerImg.blobUrl;
            } else {
                fotoPrincipal = innerImg;
            }
        }
    }
    
    const descripcion = prenda.descripcion || '';
    
    // Obtener información de tela
    let tela = 'N/A';
    let color = 'N/A';
    let referencia = 'N/A';
    let telaFoto = null;
    
    // PRIMERO: Intentar desde propiedades raíz (prendas recuperadas de BD con estructura nueva)
    // Esta es la estructura correcta para prendas guardadas: {tela, color, ref, imagenes_tela}
    if ((prenda.tela || prenda.color) && prenda.imagenes_tela) {
        tela = prenda.tela || 'N/A';
        color = prenda.color || 'N/A';
        referencia = prenda.ref || prenda.referencia || 'N/A';  // BD usa 'ref', no 'referencia'
        
        console.log('📋 Tela obtenida de propiedades raíz (BD):', {tela, color, referencia});
        
        // Obtener foto de tela desde imagenes_tela
        // La segunda imagen es la de tela real (primera es imagen_tela de portada)
        if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
            // Preferir la segunda imagen si existe (es la foto de tela)
            const telaIndex = prenda.imagenes_tela.length > 1 ? 1 : 0;
            const fotoDatos = prenda.imagenes_tela[telaIndex];
            // Las fotos de BD tienen estructura: {url, ruta_webp, ruta_original, ...}
            telaFoto = fotoDatos.url || fotoDatos.ruta_webp || fotoDatos.ruta_original || null;
            console.log('📸 Foto de tela obtenida de imagenes_tela[' + telaIndex + ']:', telaFoto);
        }
    }
    // SEGUNDO: Intentar desde telasAgregadas (prendas nuevas recién creadas)
    else if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0) {
        const telaPrincipal = prenda.telasAgregadas[0];
        tela = telaPrincipal.tela || 'N/A';
        color = telaPrincipal.color || 'N/A';
        referencia = telaPrincipal.referencia || 'N/A';
        
        console.log('📋 Tela obtenida de telasAgregadas:', {tela, color, referencia});
        
        // Obtener foto de tela y convertir si es necesario
        if (telaPrincipal.imagenes && Array.isArray(telaPrincipal.imagenes) && telaPrincipal.imagenes.length > 0) {
            const imgTela = telaPrincipal.imagenes[0];
            //  Primero intentar usar blobUrl si ya existe
            if (imgTela && imgTela.blobUrl && typeof imgTela.blobUrl === 'string') {
                console.log('🔄 Usando blob URL de tela ya creado');
                telaFoto = imgTela.blobUrl;
            }
            // Convertir si es File object (igual que con imágenes de prenda)
            else if (imgTela && imgTela.file instanceof File) {
                console.log('🔄 Convirtiendo File object de tela a blob URL');
                telaFoto = URL.createObjectURL(imgTela.file);
            } else if (imgTela instanceof File) {
                telaFoto = URL.createObjectURL(imgTela);
            } else if (typeof imgTela === 'string') {
                telaFoto = imgTela;
            }
        }
    } 
    // TERCERO: Intentar desde prenda.telas (prendas recuperadas de BD con estructura antigua)
    else if (prenda.telas && Array.isArray(prenda.telas) && prenda.telas.length > 0) {
        const telaPrincipal = prenda.telas[0];
        // Las telas de BD tienen estructura: {nombre_tela, color, referencia, telaFotos, ...}
        tela = telaPrincipal.nombre_tela || telaPrincipal.tela || 'N/A';
        color = telaPrincipal.color || 'N/A';
        referencia = telaPrincipal.referencia || 'N/A';
        
        console.log('📋 Tela obtenida de prenda.telas (BD antigua):', {tela, color, referencia});
        
        // Obtener foto de tela desde telaFotos (relación de BD)
        if (telaPrincipal.telaFotos && Array.isArray(telaPrincipal.telaFotos) && telaPrincipal.telaFotos.length > 0) {
            const primerFoto = telaPrincipal.telaFotos[0];
            // Las fotos de BD tienen estructura: {url, ruta_webp, ruta_original, ...}
            telaFoto = primerFoto.url || primerFoto.ruta_webp || primerFoto.ruta_original || null;
            console.log('📸 Foto de tela obtenida de telaFotos:', telaFoto);
        }
    }
    // CUARTO: Fallback a variantes si existen
    else {
        tela = prenda.variantes?.tela || prenda.tela || 'N/A';
        color = prenda.variantes?.color || prenda.color || 'N/A';
        referencia = prenda.variantes?.referencia || prenda.referencia || prenda.ref || 'N/A';
        console.log('📋 Tela obtenida de fallback variantes:', {tela, color, referencia});
    }

    console.log('📸 Foto principal:', fotoPrincipal);
    console.log('📋 Tela:', tela, 'Color:', color, 'Referencia:', referencia);
    console.log('📸 Foto tela:', telaFoto);
    console.log('📊 Tallas disponibles:', prenda.tallas || prenda.generosConTallas);
    console.log('⚙️  Procesos:', prenda.procesos);

    // Construir HTML de variaciones expandible
    const variacionesHTML = construirSeccionVariaciones(prenda, indice);
    
    // Construir HTML combinado de tallas y cantidades
    const tallasYCantidadesHTML = construirSeccionTallasYCantidades(prenda, indice);
    console.log(`📋 HTML Tallas y Cantidades generado. Largo: ${tallasYCantidadesHTML.length} caracteres`);
    
    // Construir HTML de procesos expandible
    const procesosHTML = construirSeccionProcesos(prenda, indice);

    const html = `
        <div class="prenda-card-readonly" data-prenda-index="${indice}" data-prenda-id="${prenda.id || ''}">
            <!-- Encabezado: Etiqueta, Nombre y Menú -->
            <div class="prenda-card-header">
                <div class="prenda-card-title-section">
                    <span class="prenda-label">Prenda ${indice + 1}</span>
                    <h3 class="prenda-name">${prenda.nombre_producto || 'Sin nombre'}</h3>
                </div>
                
                <div class="prenda-menu-contextual">
                    <button class="btn-menu-tres-puntos" type="button" data-prenda-index="${indice}">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="submenu-prenda" style="display: none;">
                        <button class="submenu-option btn-editar-prenda" type="button" data-prenda-index="${indice}">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="submenu-option btn-eliminar-prenda" type="button" data-prenda-index="${indice}">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Contenedor principal: Foto IZQUIERDA + Info DERECHA -->
            <div class="prenda-card-content">
                <!-- Foto de prenda GRANDE a la izquierda -->
                <div class="foto-prenda-izquierda">
                    ${fotoPrincipal ? `
                        <div style="position: relative; display: inline-block;">
                            <img 
                                src="${fotoPrincipal}" 
                                alt="${prenda.nombre_producto}" 
                                class="foto-principal-readonly"
                                data-prenda-index="${indice}"
                                data-foto-index="0"
                                style="cursor: pointer; width: 120px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                onload="console.log(' Imagen de prenda cargada:', '${fotoPrincipal}')"
                                onerror="console.error('❌ Error al cargar imagen de prenda:', '${fotoPrincipal}')"
                                onmouseover="this.style.boxShadow='0 4px 16px rgba(14,165,233,0.3)'; this.style.borderColor='#0ea5e9'; this.parentElement.querySelector('.foto-overlay-icon').style.opacity='1';"
                                onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'; this.style.borderColor='#e5e7eb'; this.parentElement.querySelector('.foto-overlay-icon').style.opacity='0';"
                            />
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0; transition: opacity 0.2s; pointer-events: none; background: rgba(0,0,0,0.4); width: 100%; height: 100%; border-radius: 8px; display: flex; align-items: center; justify-content: center;" class="foto-overlay-icon">
                                <i class="fas fa-search-plus" style="font-size: 2rem; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"></i>
                            </div>
                            ${imagenes && imagenes.length > 1 ? `<span style="position: absolute; top: 5px; right: 5px; background: rgba(14,165,233,0.9); color: white; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700;"><i class="fas fa-images"></i> ${imagenes.length}</span>` : ''}
                        </div>
                    ` : `
                        <div style="width: 120px; height: 150px; background: #f3f4f6; border-radius: 8px; border: 2px dashed #d1d5db; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9ca3af; gap: 0.5rem;">
                            <i class="fas fa-image" style="font-size: 2rem;"></i>
                            <small>Sin foto</small>
                        </div>
                    `}
                </div>

                <!-- Info a la derecha -->
                <div class="prenda-card-info">
                    <!-- Descripción -->
                    ${descripcion ? `<p class="prenda-descripcion">${descripcion}</p>` : ''}

                    <!-- Specs en HORIZONTAL: Tela + Color + Ref + Foto tela pequeña -->
                    <div class="prenda-specs-horizontal">
                        <!-- Specs en línea -->
                        <div class="specs-content">
                            <div class="spec-item">
                                <strong>Tela:</strong>
                                <span>${tela}</span>
                            </div>
                            <div class="spec-item">
                                <strong>Color:</strong>
                                <span>${color}</span>
                            </div>
                            <div class="spec-item">
                                <strong>Ref:</strong>
                                <span>${referencia}</span>
                            </div>
                        </div>
                        
                        <!-- Foto pequeña de tela a la derecha -->
                        <div class="foto-tela-pequena">
                            ${telaFoto ? `
                                <img 
                                    src="${telaFoto}" 
                                    alt="Tela" 
                                    class="foto-tela-readonly"
                                    data-prenda-index="${indice}"
                                    style="cursor: pointer; width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 2px solid #e5e7eb; transition: all 0.2s; box-shadow: 0 2px 6px rgba(0,0,0,0.08);"
                                    title="Click para ver galería de telas"
                                    onload="console.log(' Foto de tela cargada')"
                                    onerror="console.error('❌ Error al cargar foto de tela')"
                                    onmouseover="this.style.boxShadow='0 4px 12px rgba(14,165,233,0.3)'; this.style.borderColor='#0ea5e9'; this.style.transform='scale(1.05)';"
                                    onmouseout="this.style.boxShadow='0 2px 6px rgba(0,0,0,0.08)'; this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)';"
                                />
                            ` : `
                                <div style="width: 70px; height: 70px; background: #f3f4f6; border-radius: 6px; border: 2px dashed #d1d5db; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 1rem;">
                                    <i class="fas fa-image"></i>
                                </div>
                            `}
                        </div>
                    </div>

                    <!-- SECCIÓN 1: VARIACIONES (Expandible) -->
                    ${variacionesHTML}

                    <!-- SECCIÓN 2: TALLAS Y CANTIDADES (Expandible) -->
                    ${tallasYCantidadesHTML}

                    <!-- SECCIÓN 3: PROCESOS (Expandible) -->
                    ${procesosHTML}
                </div>
            </div>
        </div>
    `;

    console.log('📝 HTML generado para prenda:', html.substring(0, 200) + '...');
    return html;
}

/**
 * Construir sección expandible de VARIACIONES
 */
function construirSeccionVariaciones(prenda, indice) {
    console.log('🔄 Construyendo VARIACIONES para prenda:', indice);
    const variantes = prenda.variantes || {};
    console.log('📊 Variantes disponibles:', variantes);
    
    // Mapeo de variaciones con sus propiedades
    const variacionesMapeo = [
        { label: 'Manga', valKey: 'tipo_manga', obsKey: 'obs_manga' },
        { label: 'Bolsillos', valKey: 'tiene_bolsillos', obsKey: 'obs_bolsillos' },
        { label: 'Broche/Botón', valKey: 'tipo_broche', obsKey: 'obs_broche' },
        { label: 'Reflectivo', valKey: 'tiene_reflectivo', obsKey: 'obs_reflectivo' }
    ];
    
    // Filtrar solo variaciones aplicadas (que no sean 'No aplica' ni false)
    const variacionesAplicadas = variacionesMapeo.filter(({ valKey, obsKey }) => {
        const valor = variantes[valKey];
        return valor && valor !== 'No aplica' && valor !== false;
    });
    
    console.log(` Variaciones aplicadas: ${variacionesAplicadas.length}`);
    
    if (variacionesAplicadas.length === 0) {
        console.log('⚠️  Sin variaciones aplicadas para prenda', indice);
        return '';
    }

    // Construir filas de la tabla
    let tablasFilasHTML = '';
    variacionesAplicadas.forEach(({ label, valKey, obsKey }) => {
        const valor = variantes[valKey];
        const observaciones = variantes[obsKey] || '';
        
        console.log(`📝 [VARIACION] ${label}: valor='${valor}', obsKey='${obsKey}', observaciones='${observaciones}'`);
        
        if (label === 'Bolsillos') {
            console.log('🔍 [BOLSILLOS RENDERIZADO] Detalles:');
            console.log('  - tiene_bolsillos:', variantes.tiene_bolsillos);
            console.log('  - obs_bolsillos:', variantes.obs_bolsillos);
        }
        
        // Para Bolsillos y Reflectivo (booleanos), no mostrar especificación
        const esBooleano = typeof valor === 'boolean';
        
        tablasFilasHTML += `
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; text-align: center;">
                    <i class="fas fa-check" style="color: #10b981; font-weight: bold;"></i>
                </td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; color: #0369a1; font-weight: 500;">
                    ${label}
                </td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; color: #374151;">
                    ${esBooleano ? '-' : valor}
                </td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 0.9rem;">
                    ${observaciones || '-'}
                </td>
            </tr>
        `;
    });

    return `
        <div class="seccion-expandible variaciones-section">
            <button class="seccion-expandible-header" type="button" data-section="variaciones" data-prenda-index="${indice}">
                <h4>Variaciones <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280;">(<span class="variaciones-count">${variacionesAplicadas.length}</span>)</span></h4>
                <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
            </button>
            <div class="seccion-expandible-content variaciones-content">
                <table style="width: 100%; border-collapse: collapse; margin: 0;">
                    <thead>
                        <tr style="background: #0ea5e9; color: white;">
                            <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.85rem;">APLICA</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">VARIACIÓN</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">ESPECIFICACIÓN</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">OBSERVACIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tablasFilasHTML}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

/**
 * Construir sección COMBINADA de TALLAS Y CANTIDADES
 */
function construirSeccionTallasYCantidades(prenda, indice) {
    console.log('👕📊 Construyendo TALLAS Y CANTIDADES para prenda:', indice);
    
    // Obtener tallas
    let tallas = prenda.tallas;
    let generosConTallas = prenda.generosConTallas;
    const cantidadesPorTalla = prenda.cantidadesPorTalla || {};
    
    console.log('📋 prenda.tallas:', tallas);
    console.log('📈 Cantidades disponibles:', cantidadesPorTalla);
    
    // Estructurar datos por género
    let tallasByGeneroMap = {};
    let cantidadesPorGenero = {};
    let totalTallas = 0;
    
    // Procesar tallas
    const generoKeys = Object.keys(generosConTallas || {});
    if (generoKeys.length > 0) {
        Object.entries(generosConTallas).forEach(([genero, data]) => {
            if (data && data.tallas && Array.isArray(data.tallas) && data.tallas.length > 0) {
                tallasByGeneroMap[genero] = data.tallas;
                totalTallas += data.tallas.length;
            }
        });
    } else if (Array.isArray(tallas) && tallas.length > 0) {
        tallas.forEach(t => {
            const genero = t.genero || 'general';
            const tallasList = t.tallas || [];
            if (tallasList.length > 0) {
                tallasByGeneroMap[genero] = tallasList;
                totalTallas += tallasList.length;
            }
        });
    }
    
    // Procesar cantidades
    Object.entries(cantidadesPorTalla).forEach(([clave, cantidad]) => {
        const [genero, talla] = clave.split('-');
        if (genero && talla) {
            if (!cantidadesPorGenero[genero]) {
                cantidadesPorGenero[genero] = {};
            }
            cantidadesPorGenero[genero][talla] = cantidad;
        }
    });
    
    const totalCantidades = Object.keys(cantidadesPorTalla).length;
    console.log(` Total tallas: ${totalTallas}, Total cantidades: ${totalCantidades}`);
    
    if (totalTallas === 0) {
        console.log('⚠️  Sin datos para prenda', indice);
        return '';
    }

    // Construir contenido con diseño profesional
    let generoHTML = '';
    
    Object.keys(tallasByGeneroMap).forEach((genero, idx) => {
        const tallasList = tallasByGeneroMap[genero] || [];
        const cantidadesGen = cantidadesPorGenero[genero] || {};
        
        if (tallasList.length === 0) return;
        
        const tallasConCantidad = tallasList.map(talla => {
            const cantidad = cantidadesGen[talla] || 0;
            return `
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: #dbeafe; padding: 0.5rem 0.75rem; border-radius: 6px; font-weight: 600; color: #0369a1; border: 1px solid #7dd3fc;">
                    <i class="fas fa-ruler" style="font-size: 0.85rem;"></i>
                    ${talla}
                    <span style="background: #0369a1; color: white; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">${cantidad}</span>
                </div>
            `;
        }).join('');
        
        generoHTML += `
            <div style="margin-bottom: 1rem;">
                <div style="font-weight: 700; color: #1e293b; margin-bottom: 0.75rem; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-users" style="color: #0ea5e9; font-size: 0.9rem;"></i>
                    ${genero}
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                    ${tallasConCantidad}
                </div>
            </div>
        `;
    });
    
    return `
        <div class="seccion-expandible tallas-y-cantidades-section">
            <button class="seccion-expandible-header" type="button" data-section="tallas-y-cantidades" data-prenda-index="${indice}">
                <h4 style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-th" style="color: #0ea5e9;"></i>
                    Tallas & Cantidades
                    <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280; font-weight: 500;">(<span class="tallas-cantidades-count">${totalTallas}</span>)</span>
                </h4>
                <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
            </button>
            <div class="seccion-expandible-content tallas-y-cantidades-content">
                <div style="padding: 1.25rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 8px; border-left: 4px solid #0ea5e9;">
                    ${generoHTML}
                </div>
            </div>
        </div>
    `;
}

/**
 * Construir sección expandible de PROCESOS
 */
function construirSeccionProcesos(prenda, indice) {
    console.log('⚙️  Construyendo PROCESOS para prenda:', indice);
    const procesos = prenda.procesos || {};
    console.log('📊 Procesos disponibles:', procesos);
    
    const procesosConDatos = Object.entries(procesos).filter(([_, proc]) => proc && (proc.datos !== null || proc.tipo));
    console.log(` Procesos con datos: ${procesosConDatos.length}`);
    
    if (procesosConDatos.length === 0) {
        console.log('⚠️  Sin procesos para prenda', indice);
        return '';
    }

    // Mapeo de iconos por tipo de proceso
    const iconosProcesos = {
        'reflectivo': '<i class="fas fa-lightbulb" style="color: #f59e0b;"></i>',
        'bordado': '<i class="fas fa-gem" style="color: #8b5cf6;"></i>',
        'estampado': '<i class="fas fa-paint-brush" style="color: #ec4899;"></i>',
        'dtf': '<i class="fas fa-print" style="color: #06b6d4;"></i>',
        'sublimado': '<i class="fas fa-tint" style="color: #3b82f6;"></i>'
    };

    let procesosItemsHTML = '';
    procesosConDatos.forEach(([tipoProceso, proceso]) => {
        const datos = proceso.datos || {};
        const icono = iconosProcesos[tipoProceso] || '<i class="fas fa-cog"></i>';
        const nombreProceso = tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1);
        
        // Construir ubicaciones
        let ubicacionesHTML = '';
        if (datos.ubicaciones && datos.ubicaciones.length > 0) {
            ubicacionesHTML = datos.ubicaciones.map(ubi => 
                `<span style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; display: inline-block; margin: 0.25rem;">
                    <i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>${ubi}
                </span>`
            ).join('');
        }
        
        // Construir tallas por género con cantidades
        let tallasHTML = '';
        if (datos.tallas) {
            const damaObj = datos.tallas.dama || {};
            const caballeroObj = datos.tallas.caballero || {};
            const damaHasTallas = Object.keys(damaObj).length > 0;
            const caballeroHasTallas = Object.keys(caballeroObj).length > 0;
            
            if (damaHasTallas || caballeroHasTallas) {
                tallasHTML = '<div style="margin-top: 0.75rem;">';
                
                if (damaHasTallas) {
                    tallasHTML += `
                        <div style="margin-bottom: 0.5rem;">
                            <span style="font-weight: 600; color: #be185d; margin-right: 0.5rem;">
                                <i class="fas fa-female" style="margin-right: 0.25rem;"></i>Dama:
                            </span>
                            ${Object.entries(damaObj).map(([talla, cantidad]) => {
                                return `<span style="background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    ${talla}
                                    <span style="background: #be185d; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
                                </span>`;
                            }).join('')}
                        </div>
                    `;
                }
                
                if (caballeroHasTallas) {
                    tallasHTML += `
                        <div>
                            <span style="font-weight: 600; color: #1d4ed8; margin-right: 0.5rem;">
                                <i class="fas fa-male" style="margin-right: 0.25rem;"></i>Caballero:
                            </span>
                            ${Object.entries(caballeroObj).map(([talla, cantidad]) => {
                                return `<span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    ${talla}
                                    <span style="background: #1d4ed8; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
                                </span>`;
                            }).join('')}
                        </div>
                    `;
                }
                
                tallasHTML += '</div>';
            }
        }
        
        // Construir observaciones
        let observacionesHTML = '';
        if (datos.observaciones) {
            observacionesHTML = `
                <div style="margin-top: 0.75rem; padding: 0.75rem; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px;">
                    <strong style="color: #92400e; display: block; margin-bottom: 0.25rem;">
                        <i class="fas fa-sticky-note" style="margin-right: 0.5rem;"></i>Observaciones:
                    </strong>
                    <span style="color: #78350f; font-size: 0.9rem;">${datos.observaciones}</span>
                </div>
            `;
        }
        
        // Construir preview de imágenes (soporte para múltiples)
        let imagenHTML = '';
        const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
        if (imagenes.length > 0) {
            imagenHTML = `
                <div style="margin-top: 0.75rem;">
                    <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                        <i class="fas fa-images" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Imágenes (${imagenes.length}):
                    </strong>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        ${imagenes.map(img => {
                            const imgSrc = img instanceof File ? URL.createObjectURL(img) : img;
                            return `
                            <img src="${imgSrc}" 
                                 alt="Imagen ${nombreProceso}" 
                                 style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; cursor: pointer;"
                                 onclick="window.mostrarImagenProcesoGrande('${imgSrc}')">
                        `;
                        }).join('')}
                    </div>
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #6b7280;">
                        <i class="fas fa-search-plus"></i> Click en las imágenes para ampliar
                    </p>
                </div>
            `;
        }
        
        procesosItemsHTML += `
            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 2px solid #0ea5e9;">
                    <span style="font-size: 1.5rem;">${icono}</span>
                    <h4 style="margin: 0; color: #0369a1; font-size: 1.1rem; font-weight: 700;">${nombreProceso}</h4>
                </div>
                
                ${ubicacionesHTML ? `
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                            <i class="fas fa-location-arrow" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Ubicaciones:
                        </strong>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            ${ubicacionesHTML}
                        </div>
                    </div>
                ` : ''}
                
                ${tallasHTML}
                ${observacionesHTML}
                ${imagenHTML}
            </div>
        `;
    });

    return `
        <div class="seccion-expandible procesos-section">
            <button class="seccion-expandible-header" type="button" data-section="procesos" data-prenda-index="${indice}">
                <h4>Procesos <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280;">(<span class="procesos-count">${procesosConDatos.length}</span>)</span></h4>
                <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
            </button>
            <div class="seccion-expandible-content procesos-content">
                <div style="padding: 1rem;">
                    ${procesosItemsHTML}
                </div>
            </div>
        </div>
    `;
}

/**
 * Manejar clicks en botones expandibles - NUEVA ESTRUCTURA
 */
document.addEventListener('click', (e) => {
    // Expandir/contraer secciones expandibles
    if (e.target.closest('.seccion-expandible-header')) {
        console.log('🔽 Click en header expandible');
        const header = e.target.closest('.seccion-expandible-header');
        const content = header.nextElementSibling;
        
        if (content && content.classList.contains('seccion-expandible-content')) {
            // Toggle de la sección
            content.classList.toggle('active');
            header.classList.toggle('active');
            console.log(' Sección expandida/contraída');
        }
    }

    if (e.target.closest('.btn-menu-tres-puntos')) {
        console.log('☰ Click en menú de 3 puntos');
        e.stopPropagation();
        const btn = e.target.closest('.btn-menu-tres-puntos');
        const submenu = btn.nextElementSibling;
        
        // Cerrar otros submenús abiertos
        document.querySelectorAll('.submenu-prenda').forEach(menu => {
            if (menu !== submenu) menu.style.display = 'none';
        });
        
        // Alternar este submenú
        submenu.style.display = submenu.style.display === 'none' ? 'flex' : 'none';
    }

    // Botón EDITAR
    if (e.target.closest('.btn-editar-prenda')) {
        console.log('  Click en botón EDITAR');
        e.stopPropagation();
        const btn = e.target.closest('.btn-editar-prenda');
        const prendaIndex = parseInt(btn.dataset.prendaIndex);
        console.log(`   Editando prenda índice: ${prendaIndex}`);
        
        // Obtener prenda del gestor
        if (window.gestorPrendaSinCotizacion) {
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
            console.log('   Prenda obtenida:', prenda);
            if (prenda) {
                //  Abrir modal simple de edición
                if (window.abrirEditarPrendaModal) {
                    const pedidoId = document.querySelector('[data-pedido-id]')?.dataset.pedidoId || null;
                    console.log('    Abriendo modal de edición, pedidoId:', pedidoId);
                    window.abrirEditarPrendaModal(prenda, prendaIndex, pedidoId);
                } else {
                    console.warn('     abrirEditarPrendaModal no disponible');
                }
            } else {
                console.warn('     Prenda no encontrada');
            }
        } else {
            console.warn('     gestorPrendaSinCotizacion no disponible');
        }
        
        // Cerrar submenú
        const submenu = btn.closest('.submenu-prenda');
        if (submenu) submenu.style.display = 'none';
    }

    // Botón ELIMINAR
    if (e.target.closest('.btn-eliminar-prenda')) {
        console.log('🗑️  Click en botón ELIMINAR');
        e.stopPropagation();
        const btn = e.target.closest('.btn-eliminar-prenda');
        const prendaIndex = parseInt(btn.dataset.prendaIndex);
        console.log(`   Eliminando prenda índice: ${prendaIndex}`);
        
        // Modal de confirmación
        Swal.fire({
            title: '¿Eliminar prenda?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('    Confirmado eliminar');
                if (window.gestorPrendaSinCotizacion?.eliminar) {
                    window.gestorPrendaSinCotizacion.eliminar(prendaIndex);
                    
                    // Re-renderizar prendas con tarjetas readonly (no código viejo)
                    //  Usar obtenerActivas() para excluir prendas eliminadas
                    const container = document.getElementById('prendas-container-editable');
                    if (container && window.generarTarjetaPrendaReadOnly) {
                        const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
                        if (prendas.length === 0) {
                            container.innerHTML = `
                                <div class="empty-state" style="text-align: center; padding: 2rem; color: #9ca3af;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                    <p>No hay ítems agregados. Selecciona un tipo de pedido para agregar nuevos ítems.</p>
                                </div>
                            `;
                        } else {
                            let html = '';
                            prendas.forEach((prenda, indice) => {
                                html += window.generarTarjetaPrendaReadOnly(prenda, indice);
                            });
                            container.innerHTML = html;
                        }
                        console.log(`    Prendas re-renderizadas. Total activas: ${prendas.length}`);
                    } else {
                        console.error('❌ Container o función de renderizado no disponibles');
                    }
                    
                    console.log(`    Prenda ${prendaIndex + 1} eliminada`);
                } else {
                    console.warn('   ⚠️  No se encontró método eliminar');
                }
            }
        });
        
        // Cerrar submenú
        const submenu = btn.closest('.submenu-prenda');
        if (submenu) submenu.style.display = 'none';
    }

    // Cerrar menú al hacer click fuera
    if (!e.target.closest('.prenda-menu-contextual')) {
        document.querySelectorAll('.submenu-prenda').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

/**
 * Manejar clicks en fotos para abrir galerías modales
 */
document.addEventListener('click', (e) => {
    // Click en foto principal de prenda
    if (e.target.classList.contains('foto-principal-readonly')) {
        console.log('📸 Click en foto principal');
        e.stopPropagation();
        const prendaIndex = parseInt(e.target.dataset.prendaIndex);
        console.log(`   Prenda índice: ${prendaIndex}`);
        
        if (window.gestorPrendaSinCotizacion) {
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
            console.log('   Prenda obtenida para galería:', prenda);
            console.log('   📸 Prenda.imagenes en obtenerPorIndice:', prenda?.imagenes);
            console.log('   📸 Prenda.imagenes?.length:', prenda?.imagenes?.length);
            if (prenda) {
                abrirGaleriaFotosModal(prenda, prendaIndex);
            }
        }
    }

    // Click en foto de tela
    if (e.target.classList.contains('foto-tela-readonly')) {
        console.log('🧵 Click en foto de tela');
        e.stopPropagation();
        const prendaIndex = parseInt(e.target.dataset.prendaIndex);
        console.log(`   Prenda índice: ${prendaIndex}`);
        
        if (window.gestorPrendaSinCotizacion) {
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
            console.log('   Prenda obtenida para galería de tela:', prenda);
            if (prenda) {
                abrirGaleriaTelasModal(prenda, prendaIndex);
            }
        }
    }
});

/**
 * Abrir modal de galería de fotos con navegación
 */
function abrirGaleriaFotosModal(prenda, prendaIndex) {
    console.log('🖼️  Abriendo galería de fotos para prenda:', prendaIndex);
    console.log('🔍 [FOTO-PRENDA] Objeto prenda completo:', prenda);
    console.log('🔍 [FOTO-PRENDA] prenda.imagenes:', prenda.imagenes);
    console.log('🔍 [FOTO-PRENDA] prenda.fotos:', prenda.fotos);
    console.log('🔍 [FOTO-PRENDA] typeof prenda.imagenes:', typeof prenda.imagenes);
    console.log('🔍 [FOTO-PRENDA] Array.isArray(prenda.imagenes):', Array.isArray(prenda.imagenes));
    console.log('🔍 [FOTO-PRENDA] prenda.imagenes?.length:', prenda.imagenes?.length);
    
    //  CORRECCIÓN: Revisar si tienen elementos, no solo si existen
    let imagenes = (prenda.imagenes?.length > 0 ? prenda.imagenes : null) || 
                   (prenda.fotos?.length > 0 ? prenda.fotos : null) || 
                   [];
    console.log(`   Imágenes brutas encontradas: ${imagenes.length}`, imagenes);
    console.log(`   Detalles de imagenes:`, {
        esFotos: !!prenda.fotos,
        esImagenes: !!prenda.imagenes,
        fotosLength: prenda.fotos?.length,
        imagenesLength: prenda.imagenes?.length,
        seleccionadas: imagenes
    });
    
    // Convertir File objects a URLs blob
    const fotosUrls = imagenes.map((img, idx) => {
        console.log(`   [${idx}] Procesando imagen:`, img);
        
        //  Primero intentar usar blobUrl si ya existe (creado al guardar)
        if (img && img.blobUrl && typeof img.blobUrl === 'string') {
            console.log(`   [${idx}] Usando blob URL ya creado: ${img.blobUrl.substring(0, 50)}...`);
            return img.blobUrl;
        }
        // Si es un objeto con propiedad 'file', es un File object
        else if (img && img.file instanceof File) {
            console.log(`   [${idx}] Convertiendo File object a blob URL`);
            return URL.createObjectURL(img.file);
        } else if (img instanceof File) {
            // Si es directamente un File
            console.log(`   [${idx}] File directo, convirtiendo a blob URL`);
            return URL.createObjectURL(img);
        } else if (typeof img === 'string') {
            // Si ya es una URL
            console.log(`   [${idx}] Ya es una URL`);
            return img;
        } else {
            console.warn(`   [${idx}] Tipo desconocido:`, typeof img, img);
            return null;
        }
    }).filter(url => url !== null);
    
    console.log(`    Fotos procesadas: ${fotosUrls.length}`, fotosUrls);
    
    if (fotosUrls.length === 0) {
        console.warn('⚠️  Sin fotos disponibles');
        Swal.fire({
            title: '📸 Sin fotos',
            html: '<p style="color: #666;">Esta prenda no tiene fotos cargadas</p>',
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#0ea5e9'
        });
        return;
    }

    let indiceActual = 0;

    const generarContenidoGaleria = (idx) => {
        return `
            <div style="max-width: 500px; margin: 0 auto;">
                <div id="galeria-foto-container" style="position: relative; margin-bottom: 1rem;">
                    <img 
                        id="galeria-foto-actual"
                        src="${fotosUrls[idx]}" 
                        alt="Foto prenda"
                        style="width: 100%; border-radius: 8px; max-height: 400px; object-fit: contain;"
                    />
                    ${fotosUrls.length > 1 ? `
                        <button id="btn-foto-anterior" type="button" 
                            style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); 
                                   background: rgba(0,0,0,0.6); color: white; border: none; 
                                   width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s;">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="btn-foto-siguiente" type="button" 
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                   background: rgba(0,0,0,0.6); color: white; border: none; 
                                   width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s;">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    ` : ''}
                </div>
                <div style="text-align: center; color: #666; font-size: 0.9rem;">
                    <i class="fas fa-images"></i> Foto ${idx + 1} de ${fotosUrls.length}
                </div>
            </div>
        `;
    };

    Swal.fire({
        title: `📸 ${prenda.nombre_producto}`,
        html: generarContenidoGaleria(indiceActual),
        width: '600px',
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#0ea5e9',
        didOpen: () => {
            const actualizarGaleria = () => {
                const container = document.querySelector('.swal2-html-container');
                if (container) {
                    container.innerHTML = generarContenidoGaleria(indiceActual);
                    
                    // Re-asignar event listeners después de actualizar
                    const btnAnterior = document.getElementById('btn-foto-anterior');
                    const btnSiguiente = document.getElementById('btn-foto-siguiente');

                    if (btnAnterior) {
                        btnAnterior.addEventListener('click', (e) => {
                            e.stopPropagation();
                            indiceActual = (indiceActual - 1 + fotosUrls.length) % fotosUrls.length;
                            actualizarGaleria();
                        });
                    }

                    if (btnSiguiente) {
                        btnSiguiente.addEventListener('click', (e) => {
                            e.stopPropagation();
                            indiceActual = (indiceActual + 1) % fotosUrls.length;
                            actualizarGaleria();
                        });
                    }
                }
            };
            
            actualizarGaleria();
        }
    });
}

/**
 * Abrir modal de galería de fotos de tela con navegación
 */
function abrirGaleriaTelasModal(prenda, prendaIndex) {
    console.log('🧵 Abriendo galería de fotos de tela para prenda:', prendaIndex);
    
    // Obtener telas de la prenda
    const telas = prenda.telasAgregadas || [];
    console.log(`   Telas disponibles: ${telas.length}`, telas);
    
    if (telas.length === 0) {
        console.warn('⚠️  Sin telas disponibles');
        Swal.fire({
            title: '🧵 Sin telas',
            html: '<p style="color: #666;">Esta prenda no tiene telas cargadas</p>',
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#0ea5e9'
        });
        return;
    }

    // Procesar todas las fotos de todas las telas
    const telasConFotos = [];
    telas.forEach((tela, telaIdx) => {
        if (tela.imagenes && Array.isArray(tela.imagenes)) {
            const fotosUrlsTela = tela.imagenes.map((img) => {
                //  Primero intentar usar blobUrl si ya existe
                if (img.blobUrl && typeof img.blobUrl === 'string') {
                    return img.blobUrl;
                }
                // Si es un objeto con propiedad 'file'
                else if (img.file instanceof File) {
                    return URL.createObjectURL(img.file);
                } else if (img instanceof File) {
                    return URL.createObjectURL(img);
                } else if (typeof img === 'string') {
                    return img;
                }
                return null;
            }).filter(url => url !== null);

            if (fotosUrlsTela.length > 0) {
                telasConFotos.push({
                    nombre: tela.tela || `Tela ${telaIdx + 1}`,
                    color: tela.color || 'N/A',
                    referencia: tela.referencia || 'N/A',
                    fotos: fotosUrlsTela
                });
            }
        }
    });

    console.log(`   Telas con fotos: ${telasConFotos.length}`, telasConFotos);

    if (telasConFotos.length === 0) {
        console.warn('⚠️  Sin fotos de telas disponibles');
        Swal.fire({
            title: '📸 Sin fotos',
            html: '<p style="color: #666;">Las telas no tienen fotos cargadas</p>',
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#0ea5e9'
        });
        return;
    }

    let telaActualIdx = 0;
    let fotoActualIdx = 0;

    const generarContenidoGaleriaTela = (telaIdx, fotoIdx) => {
        const tela = telasConFotos[telaIdx];
        const foto = tela.fotos[fotoIdx];

        return `
            <div style="max-width: 500px; margin: 0 auto;">
                <!-- Info de la tela -->
                <div style="background: #f0f9ff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #0ea5e9;">
                    <div style="font-weight: 700; color: #0369a1; margin-bottom: 0.5rem;">
                        <i class="fas fa-cube"></i> ${tela.nombre}
                    </div>
                    <div style="font-size: 0.85rem; color: #4b5563;">
                        <div><strong>Color:</strong> ${tela.color}</div>
                        <div><strong>Ref:</strong> ${tela.referencia}</div>
                    </div>
                </div>

                <!-- Galería de fotos -->
                <div id="galeria-tela-container" style="position: relative; margin-bottom: 1rem;">
                    <img 
                        id="galeria-tela-actual"
                        src="${foto}" 
                        alt="Foto tela"
                        style="width: 100%; border-radius: 8px; max-height: 400px; object-fit: contain; border: 2px solid #e5e7eb;"
                    />
                    ${tela.fotos.length > 1 ? `
                        <button id="btn-tela-anterior" type="button" 
                            style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); 
                                   background: rgba(0,0,0,0.6); color: white; border: none; 
                                   width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s;">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="btn-tela-siguiente" type="button" 
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                   background: rgba(0,0,0,0.6); color: white; border: none; 
                                   width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s;">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    ` : ''}
                </div>

                <!-- Contadores -->
                <div style="text-align: center; color: #666; font-size: 0.9rem;">
                    <i class="fas fa-images"></i> Tela ${telaIdx + 1} de ${telasConFotos.length} | Foto ${fotoIdx + 1} de ${tela.fotos.length}
                </div>
            </div>
        `;
    };

    Swal.fire({
        title: `🧵 Telas - ${prenda.nombre_producto}`,
        html: generarContenidoGaleriaTela(telaActualIdx, fotoActualIdx),
        width: '600px',
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#0ea5e9',
        didOpen: () => {
            const actualizarGaleriaTela = () => {
                const container = document.querySelector('.swal2-html-container');
                if (container) {
                    container.innerHTML = generarContenidoGaleriaTela(telaActualIdx, fotoActualIdx);
                    
                    // Re-asignar event listeners después de actualizar
                    const btnAnterior = document.getElementById('btn-tela-anterior');
                    const btnSiguiente = document.getElementById('btn-tela-siguiente');

                    if (btnAnterior) {
                        btnAnterior.addEventListener('click', (e) => {
                            e.stopPropagation();
                            fotoActualIdx = (fotoActualIdx - 1 + telasConFotos[telaActualIdx].fotos.length) % telasConFotos[telaActualIdx].fotos.length;
                            actualizarGaleriaTela();
                        });
                    }

                    if (btnSiguiente) {
                        btnSiguiente.addEventListener('click', (e) => {
                            e.stopPropagation();
                            fotoActualIdx = (fotoActualIdx + 1) % telasConFotos[telaActualIdx].fotos.length;
                            actualizarGaleriaTela();
                        });
                    }
                }
            };
            
            actualizarGaleriaTela();
        }
    });
}

console.log(' Componente prenda-card-readonly cargado con DEBUG LOGGING');
console.log('📊 Los logs mostrarán: estructura de datos, fotos, variantes, tallas y procesos');
