/**
 * PrendaCardService - Generación de HTML para tarjetas de prenda
 * Servicio centralizado para construir tarjetas en modo solo lectura
 */

window.PrendaCardService = {
    generar(prenda, indice) {

        
        // Usar las propiedades correctas
        const imagenes = prenda.imagenes || prenda.fotos || [];
        
        // Usar servicio centralizado para convertir imágenes
        const fotoPrincipal = window.ImageConverterService ? 
            window.ImageConverterService.obtenerPrimeraImagen(imagenes) : 
            null;
        
        const descripcion = prenda.descripcion || '';
        
        // Obtener información de tela
        let tela = 'N/A';
        let color = 'N/A';
        let referencia = 'N/A';
        let telaFoto = null;
        
        // Obtener información de tela desde múltiples fuentes
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0) {
            const telaPrincipal = prenda.telasAgregadas[0];
            tela = telaPrincipal.tela || 'N/A';
            color = telaPrincipal.color || 'N/A';
            referencia = telaPrincipal.referencia || 'N/A';
            telaFoto = window.ImageConverterService ? 
                window.ImageConverterService.obtenerImagenTela(telaPrincipal) : 
                null;

        }
        else if ((prenda.tela || prenda.color) && prenda.imagenes_tela) {
            tela = prenda.tela || 'N/A';
            color = prenda.color || 'N/A';
            referencia = prenda.ref || prenda.referencia || 'N/A';
            telaFoto = window.ImageConverterService ? 
                window.ImageConverterService.obtenerPrimeraImagen(prenda.imagenes_tela) : 
                null;

        }
        else if (prenda.telas && Array.isArray(prenda.telas) && prenda.telas.length > 0) {
            const telaPrincipal = prenda.telas[0];
            tela = telaPrincipal.nombre_tela || telaPrincipal.tela || 'N/A';
            color = telaPrincipal.color || 'N/A';
            referencia = telaPrincipal.referencia || 'N/A';
            telaFoto = window.ImageConverterService ? 
                window.ImageConverterService.obtenerImagenTela(telaPrincipal) : 
                null;

        }
        else {
            tela = prenda.variantes?.tela || prenda.tela || 'N/A';
            color = prenda.variantes?.color || prenda.color || 'N/A';
            referencia = prenda.variantes?.referencia || prenda.referencia || prenda.ref || 'N/A';

        }







        // Construir secciones
        const variacionesHTML = this._construirVariaciones(prenda, indice);
        const procesosHTML = this._construirProcesos(prenda, indice);
        
        // Detectar si hay asignaciones de colores → combinar tela + tallas en una sola sección
        const tieneAsignaciones = prenda.asignacionesColoresPorTalla && Object.keys(prenda.asignacionesColoresPorTalla).length > 0;
        
        let tablaTelasHTML = '';
        let tallasYCantidadesHTML = '';
        
        if (tieneAsignaciones) {
            // Sección combinada: Tela, Tallas y Colores en un solo expandible
            tablaTelasHTML = ''; // No mostrar tabla telas por separado
            tallasYCantidadesHTML = this._construirSeccionCombinada(prenda, indice);
        } else {
            // Flujo normal: tabla telas + tallas separadas
            tablaTelasHTML = this._construirTablaTelas(prenda, indice);
            tallasYCantidadesHTML = this._construirTallasYCantidades(prenda, indice);
        }

        // Calcular número de item global (considerando prendas y EPPs)
        let numeroItem = indice + 1;
        if (window.gestionItemsUI && window.gestionItemsUI.ordenItems) {
            // Contar cuántas prendas hay antes de esta
            let prendaCount = 0;
            for (let i = 0; i < window.gestionItemsUI.ordenItems.length; i++) {
                const item = window.gestionItemsUI.ordenItems[i];
                if (item.tipo === 'prenda' && item.index < indice) {
                    prendaCount++;
                }
            }
            numeroItem = prendaCount + 1;
        }

        const html = `
            <div class="prenda-card-readonly" data-prenda-index="${indice}" data-prenda-id="${prenda.id || ''}">
                <div class="prenda-card-header">
                    <div class="prenda-card-title-section">
                        <span class="prenda-label">Prenda ${numeroItem}</span>
                        <h3 class="prenda-name">${prenda.nombre_prenda || prenda.nombre_producto || 'Sin nombre'}</h3>
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

                <div class="prenda-card-content">
                    <div class="foto-prenda-izquierda">
                        ${fotoPrincipal ? `
                            <div style="position: relative; display: inline-block;">
                                <img 
                                    src="${fotoPrincipal}" 
                                    alt="${prenda.nombre_prenda || prenda.nombre_producto || 'Prenda'}" 
                                    class="foto-principal-readonly"
                                    data-prenda-index="${indice}"
                                    data-foto-index="0"
                                    style="cursor: pointer; width: 120px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                    onload="console.log(' Imagen de prenda cargada:', '${fotoPrincipal}')"
                                    onerror="console.error(' Error al cargar imagen de prenda:', '${fotoPrincipal}')"
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

                    <div class="prenda-card-info">
                        ${descripcion ? `<p class="prenda-descripcion">${descripcion}</p>` : ''}

                        ${tablaTelasHTML}

                        ${variacionesHTML}
                        ${tallasYCantidadesHTML}
                        ${procesosHTML}
                    </div>
                </div>
            </div>
        `;


        return html;
    },

    _construirVariaciones(prenda, indice) {

        const variantes = prenda.variantes || {};

        
        const variacionesMapeo = [
            { label: 'Manga', valKey: 'tipo_manga', obsKey: 'obs_manga' },
            { label: 'Bolsillos', valKey: 'tiene_bolsillos', obsKey: 'obs_bolsillos' },
            { label: 'Broche/Botón', valKey: 'tipo_broche', obsKey: 'obs_broche' },
            { label: 'Reflectivo', valKey: 'tiene_reflectivo', obsKey: 'obs_reflectivo' }
        ];
        
        const variacionesAplicadas = variacionesMapeo.filter(({ valKey, obsKey }) => {
            const valor = variantes[valKey];
            return valor && valor !== 'No aplica' && valor !== false;
        });
        

        
        if (variacionesAplicadas.length === 0) {

            return '';
        }

        let tablasFilasHTML = '';
        variacionesAplicadas.forEach(({ label, valKey, obsKey }) => {
            const valor = variantes[valKey];
            const observaciones = variantes[obsKey] || '';
            

            
            if (label === 'Bolsillos') {



            }
            
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
    },

    _construirTallasYCantidades(prenda, indice) {

        
        let tallas = prenda.tallas;
        let generosConTallas = prenda.generosConTallas;
        let cantidadTallaRelacional = prenda.cantidad_talla; // { DAMA: { S: 20, M: 20 } }
        
        // Intentar obtener cantidades desde cantidad_talla (nuevo formato relacional)
        let cantidadesPorTalla = prenda.cantidadesPorTalla || {};
        
        //  Si viene formato relacional, convertirlo
        if (cantidadTallaRelacional && typeof cantidadTallaRelacional === 'object' && !Array.isArray(cantidadTallaRelacional)) {
            // Convertir { DAMA: { S: 20, M: 20 } } → { 'dama-S': 20, 'dama-M': 20 }
            Object.entries(cantidadTallaRelacional).forEach(([genero, tallasObj]) => {
                if (typeof tallasObj === 'object') {
                    Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                        cantidadesPorTalla[`${genero.toLowerCase()}-${talla}`] = cantidad;
                    });
                }
            });
            
            // Construir generosConTallas si no existe
            if (!generosConTallas || Object.keys(generosConTallas).length === 0) {
                generosConTallas = {};
                Object.entries(cantidadTallaRelacional).forEach(([genero, tallasObj]) => {
                    if (typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0) {
                        generosConTallas[genero.toLowerCase()] = {
                            tallas: Object.keys(tallasObj)
                        };
                    }
                });
            }
        }

        
        let tallasByGeneroMap = {};
        let cantidadesPorGenero = {};
        let totalTallas = 0;
        
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
        
        // Si no hay generosConTallas pero sí hay cantidades, extraer géneros de las cantidades
        if (totalTallas === 0 && Object.keys(cantidadesPorTalla).length > 0) {

            const generosMap = {};
            Object.keys(cantidadesPorTalla).forEach(clave => {
                const [genero, talla] = clave.split('-');
                if (genero && talla) {
                    if (!generosMap[genero]) {
                        generosMap[genero] = [];
                    }
                    if (!generosMap[genero].includes(talla)) {
                        generosMap[genero].push(talla);
                    }
                }
            });
            tallasByGeneroMap = generosMap;
            totalTallas = Object.values(generosMap).reduce((sum, tallas) => sum + tallas.length, 0);
        }
        
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

        
        if (totalTallas === 0) {

            return '';
        }

        let generoHTML = '';
        
        Object.keys(tallasByGeneroMap).forEach((genero, idx) => {
            const tallasList = tallasByGeneroMap[genero] || [];
            const cantidadesGen = cantidadesPorGenero[genero] || {};
            
            if (tallasList.length === 0) return;
            
            // Obtener asignaciones de colores
            const asignacionesColores = prenda.asignacionesColoresPorTalla || {};
            
            const tallasConCantidad = tallasList.map(talla => {
                const cantidad = cantidadesGen[talla] || 0;
                
                // Buscar colores asignados para esta talla-género
                let coloresHTML = '';
                const asignacion = this._buscarAsignacionColor(asignacionesColores, genero, talla);
                if (asignacion && asignacion.colores && asignacion.colores.length > 0) {
                    const coloresItems = asignacion.colores.map(c => {
                        const nombre = c.nombre || c.color || 'Sin nombre';
                        const cant = c.cantidad || 0;
                        return `<div style="font-size: 0.65rem; color: #475569; padding: 0.15rem 0.4rem; background: rgba(255,255,255,0.8); border-radius: 3px; display: flex; align-items: center; gap: 0.25rem;">
                            <span style="display: inline-block; width: 6px; height: 6px; background: #0ea5e9; border-radius: 50%;"></span>
                            <span>${nombre}</span>
                            <span style="color: #6b7280; font-weight: 600;">×${cant}</span>
                        </div>`;
                    }).join('');
                    coloresHTML = `<div style="margin-top: 0.4rem; border-top: 1px solid rgba(203,213,225,0.4); padding-top: 0.3rem;">${coloresItems}</div>`;
                }
                
                return `
                    <div style="background: #dbeafe; padding: 0.5rem 0.75rem; border-radius: 6px; font-weight: 600; color: #0369a1; border: 1px solid #7dd3fc; min-width: 80px;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-ruler" style="font-size: 0.85rem;"></i>
                            ${talla}
                            <span style="background: #0369a1; color: white; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">${cantidad}</span>
                        </div>
                        ${coloresHTML}
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
    },

    /**
     * Buscar asignación de color para un género y talla específicos
     * Soporta claves: "genero-tipo-talla" (ej: "dama-Letra-M") o "genero-talla" (ej: "dama-M")
     */
    _buscarAsignacionColor(asignacionesColores, genero, talla) {
        if (!asignacionesColores || Object.keys(asignacionesColores).length === 0) {
            return null;
        }
        
        // Método 1: Buscar por objeto con genero y talla 
        const clavePorObjeto = Object.keys(asignacionesColores).find(clave => {
            const asig = asignacionesColores[clave];
            return asig && asig.genero && asig.genero.toLowerCase() === genero.toLowerCase() && asig.talla === talla;
        });
        if (clavePorObjeto) return asignacionesColores[clavePorObjeto];
        
        // Método 2: Buscar por clave "genero-...-talla" (última parte es la talla)
        const clavePorFormato = Object.keys(asignacionesColores).find(clave => {
            const partes = clave.split('-');
            if (partes.length >= 2) {
                return partes[0].toLowerCase() === genero.toLowerCase() && partes[partes.length - 1] === talla;
            }
            return false;
        });
        if (clavePorFormato) {
            const valor = asignacionesColores[clavePorFormato];
            return valor.genero ? valor : { genero, talla, colores: Array.isArray(valor) ? valor : [valor] };
        }
        
        return null;
    },

    /**
     * Sección combinada: Tela + Tallas + Colores en un solo expandible
     * Se usa cuando hay asignaciones de colores (flujo wizard)
     */
    _construirSeccionCombinada(prenda, indice) {
        // ── Obtener telas ──
        let telas = [];
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
            telas = prenda.telasAgregadas;
        } else if (prenda.telas && Array.isArray(prenda.telas)) {
            telas = prenda.telas;
        }

        // ── Obtener tallas (misma lógica de _construirTallasYCantidades) ──
        let generosConTallas = prenda.generosConTallas;
        let cantidadTallaRelacional = prenda.cantidad_talla;
        let cantidadesPorTalla = prenda.cantidadesPorTalla || {};

        if (cantidadTallaRelacional && typeof cantidadTallaRelacional === 'object' && !Array.isArray(cantidadTallaRelacional)) {
            Object.entries(cantidadTallaRelacional).forEach(([genero, tallasObj]) => {
                if (typeof tallasObj === 'object') {
                    Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                        cantidadesPorTalla[`${genero.toLowerCase()}-${talla}`] = cantidad;
                    });
                }
            });
            if (!generosConTallas || Object.keys(generosConTallas).length === 0) {
                generosConTallas = {};
                Object.entries(cantidadTallaRelacional).forEach(([genero, tallasObj]) => {
                    if (typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0) {
                        generosConTallas[genero.toLowerCase()] = { tallas: Object.keys(tallasObj) };
                    }
                });
            }
        }

        let tallasByGeneroMap = {};
        let cantidadesPorGenero = {};
        let totalTallas = 0;

        const generoKeys = Object.keys(generosConTallas || {});
        if (generoKeys.length > 0) {
            Object.entries(generosConTallas).forEach(([genero, data]) => {
                if (data && data.tallas && Array.isArray(data.tallas) && data.tallas.length > 0) {
                    tallasByGeneroMap[genero] = data.tallas;
                    totalTallas += data.tallas.length;
                }
            });
        }

        if (totalTallas === 0 && Object.keys(cantidadesPorTalla).length > 0) {
            const generosMap = {};
            Object.keys(cantidadesPorTalla).forEach(clave => {
                const [genero, talla] = clave.split('-');
                if (genero && talla) {
                    if (!generosMap[genero]) generosMap[genero] = [];
                    if (!generosMap[genero].includes(talla)) generosMap[genero].push(talla);
                }
            });
            tallasByGeneroMap = generosMap;
            totalTallas = Object.values(generosMap).reduce((s, t) => s + t.length, 0);
        }

        Object.entries(cantidadesPorTalla).forEach(([clave, cantidad]) => {
            const [genero, talla] = clave.split('-');
            if (genero && talla) {
                if (!cantidadesPorGenero[genero]) cantidadesPorGenero[genero] = {};
                cantidadesPorGenero[genero][talla] = cantidad;
            }
        });

        if (totalTallas === 0 && telas.length === 0) return '';

        const asignacionesColores = prenda.asignacionesColoresPorTalla || {};

        // ── Construir HTML de telas (mini-badges en vez de tabla) ──
        let telasInfoHTML = '';
        if (telas.length > 0) {
            const telasBadges = telas.map(t => {
                const nombre = t.tela || t.nombre_tela || 'N/A';
                const col = t.color || '';
                const ref = t.referencia || t.ref || '';
                const telaFoto = window.ImageConverterService ? window.ImageConverterService.obtenerImagenTela(t) : null;

                let detalles = '';
                if (col && col !== 'N/A' && col !== '') detalles += `<span style="color: #64748b;">Color: <b>${col}</b></span>`;
                if (ref && ref !== 'N/A' && ref !== '') detalles += `${detalles ? ' · ' : ''}<span style="color: #64748b;">Ref: <b>${ref}</b></span>`;

                return `
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.85rem; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px;">
                        ${telaFoto ? `
                            <img src="${telaFoto}" alt="${nombre}" style="width: 36px; height: 36px; object-fit: cover; border-radius: 5px; border: 1px solid #e0e7ff; flex-shrink: 0;" />
                        ` : `
                            <div style="width: 36px; height: 36px; background: #e0f2fe; border-radius: 5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-scroll" style="color: #0284c7; font-size: 0.85rem;"></i>
                            </div>
                        `}
                        <div>
                            <div style="font-weight: 700; color: #0369a1; font-size: 0.9rem;">${nombre}</div>
                            ${detalles ? `<div style="font-size: 0.75rem; margin-top: 0.15rem;">${detalles}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');

            telasInfoHTML = `
                <div style="margin-bottom: 1rem;">
                    <div style="font-weight: 600; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                        <i class="fas fa-scroll" style="color: #0ea5e9; font-size: 0.75rem;"></i> Tela${telas.length > 1 ? 's' : ''}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        ${telasBadges}
                    </div>
                </div>
                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0.75rem 0;">
            `;
        }

        // ── Construir HTML de tallas por género con colores ──
        let generoHTML = '';
        Object.keys(tallasByGeneroMap).forEach(genero => {
            const tallasList = tallasByGeneroMap[genero] || [];
            const cantidadesGen = cantidadesPorGenero[genero] || {};
            if (tallasList.length === 0) return;

            const tallasConCantidad = tallasList.map(talla => {
                const cantidad = cantidadesGen[talla] || 0;
                let coloresHTML = '';
                const asignacion = this._buscarAsignacionColor(asignacionesColores, genero, talla);
                if (asignacion && asignacion.colores && asignacion.colores.length > 0) {
                    const coloresItems = asignacion.colores.map(c => {
                        const nombre = c.nombre || c.color || 'Sin nombre';
                        const cant = c.cantidad || 0;
                        return `<div style="font-size: 0.65rem; color: #475569; padding: 0.15rem 0.4rem; background: rgba(255,255,255,0.8); border-radius: 3px; display: flex; align-items: center; gap: 0.25rem;">
                            <span style="display: inline-block; width: 6px; height: 6px; background: #0ea5e9; border-radius: 50%;"></span>
                            <span>${nombre}</span>
                            <span style="color: #6b7280; font-weight: 600;">×${cant}</span>
                        </div>`;
                    }).join('');
                    coloresHTML = `<div style="margin-top: 0.4rem; border-top: 1px solid rgba(203,213,225,0.4); padding-top: 0.3rem;">${coloresItems}</div>`;
                }

                return `
                    <div style="background: #dbeafe; padding: 0.5rem 0.75rem; border-radius: 6px; font-weight: 600; color: #0369a1; border: 1px solid #7dd3fc; min-width: 80px;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-ruler" style="font-size: 0.85rem;"></i>
                            ${talla}
                            <span style="background: #0369a1; color: white; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">${cantidad}</span>
                        </div>
                        ${coloresHTML}
                    </div>
                `;
            }).join('');

            generoHTML += `
                <div style="margin-bottom: 0.75rem;">
                    <div style="font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-users" style="color: #0ea5e9; font-size: 0.9rem;"></i>
                        ${genero}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                        ${tallasConCantidad}
                    </div>
                </div>
            `;
        });

        // ── Sección expandible combinada ──
        return `
            <div class="seccion-expandible tallas-y-cantidades-section">
                <button class="seccion-expandible-header" type="button" data-section="tallas-y-cantidades" data-prenda-index="${indice}">
                    <h4 style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-layer-group" style="color: #0ea5e9;"></i>
                        Tela, Tallas & Colores
                        <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280; font-weight: 500;">(<span class="tallas-cantidades-count">${totalTallas}</span>)</span>
                    </h4>
                    <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="seccion-expandible-content tallas-y-cantidades-content">
                    <div style="padding: 1.25rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 8px; border-left: 4px solid #0ea5e9;">
                        ${telasInfoHTML}
                        ${generoHTML}
                    </div>
                </div>
            </div>
        `;
    },

    _construirProcesos(prenda, indice) {

        const procesos = prenda.procesos || {};

        
        const procesosConDatos = Object.entries(procesos).filter(([_, proc]) => proc && (proc.datos !== null || proc.tipo));

        
        if (procesosConDatos.length === 0) {

            return '';
        }

        const iconosProcesos = {
            'reflectivo': '<i class="fas fa-lightbulb" style="color: #f59e0b;"></i>',
            'bordado': '<i class="fas fa-gem" style="color: #1e40af;"></i>',
            'estampado': '<i class="fas fa-paint-brush" style="color: #ec4899;"></i>',
            'dtf': '<i class="fas fa-print" style="color: #06b6d4;"></i>',
            'sublimado': '<i class="fas fa-tint" style="color: #3b82f6;"></i>'
        };

        let procesosItemsHTML = '';
        procesosConDatos.forEach(([tipoProceso, proceso]) => {
            const datos = proceso.datos || {};
            const icono = iconosProcesos[tipoProceso] || '<i class="fas fa-cog"></i>';
            const nombreProceso = tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1);
            
            let ubicacionesHTML = '';
            if (datos.ubicaciones && datos.ubicaciones.length > 0) {
                ubicacionesHTML = datos.ubicaciones
                    .map(ubi => {
                        // Extraer texto según el tipo de dato
                        const texto = typeof ubi === 'object' && ubi !== null && ubi.ubicacion 
                            ? ubi.ubicacion 
                            : (typeof ubi === 'string' ? ubi : '');
                        
                        // Si no hay texto válido, no renderizar
                        if (!texto) return '';
                        
                        return `<span style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; display: inline-block; margin: 0.25rem;">
                            <i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>${texto}
                        </span>`;
                    })
                    .filter(html => html) // Eliminar spans vacíos
                    .join('');
            }
            
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
    },

    /**
     * Construir tabla de telas con todas las variaciones
     */
    _construirTablaTelas(prenda, indice) {
        let telas = [];

        // Obtener telas de diferentes fuentes
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
            telas = prenda.telasAgregadas;
        } else if (prenda.telas && Array.isArray(prenda.telas)) {
            telas = prenda.telas;
        } else if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
            // Convertir imagenes_tela a formato de telas
            telas = prenda.imagenes_tela.map(img => ({
                tela: prenda.tela || 'N/A',
                color: prenda.color || 'N/A',
                referencia: prenda.referencia || prenda.ref || 'N/A',
                imagenes: [img]
            }));
        }

        if (telas.length === 0) {

            return '';
        }



        // Construir tabla de telas
        const tablaTelasHTML = telas.map((telaItem, telaIndex) => {
            const nombreTela = telaItem.tela || telaItem.nombre_tela || 'N/A';
            const color = telaItem.color || 'N/A';
            const referencia = telaItem.referencia || telaItem.ref || 'N/A';
            
            // Usar servicio centralizado para obtener imagen de tela
            const telaFoto = window.ImageConverterService ? 
                window.ImageConverterService.obtenerImagenTela(telaItem) : 
                null;
            

            
            if (!telaFoto) {

            }

            return `
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem; color: #1f2937; font-weight: 500;">${nombreTela}</td>
                    <td style="padding: 0.75rem; color: #1f2937; font-weight: 500;">${color}</td>
                    <td style="padding: 0.75rem; color: #1f2937; font-weight: 500;">${referencia}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        ${telaFoto ? `
                            <img 
                                src="${telaFoto}" 
                                alt="Tela ${telaIndex}" 
                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; cursor: pointer;"
                                onload="console.log('📸 Imagen de tela ${telaIndex} cargada:', '${telaFoto.substring(0, 50)}')"
                                onerror="console.error(' Error cargando imagen de tela ${telaIndex}:', '${telaFoto.substring(0, 50)}')"
                                onmouseover="this.style.boxShadow='0 2px 8px rgba(14,165,233,0.3)'; this.style.borderColor='#0ea5e9';"
                                onmouseout="this.style.boxShadow='none'; this.style.borderColor='#e5e7eb';"
                            />
                        ` : `
                            <div style="width: 50px; height: 50px; background: #f3f4f6; border-radius: 4px; border: 1px dashed #d1d5db; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                                <i class="fas fa-image" style="font-size: 0.8rem;"></i>
                            </div>
                        `}
                    </td>
                </tr>
            `;
        }).join('');

        return `
            <div class="prenda-specs-horizontal" style="margin-top: 1rem; margin-bottom: 1rem;">
                <div style="width: 100%; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">TELA</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">COLOR</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">REF</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">IMAGEN</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tablaTelasHTML}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
};


