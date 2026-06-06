/**
 * PrendaCardSizingService
 * Render de tallas y cantidades para PrendaCardService.
 */
globalThis.PrendaCardSizingService = {
    construirTallasYCantidades(owner, prenda, indice, ctx) {
let tallas = prenda.tallas;
        let generosConTallas = prenda.generosConTallas;
        let cantidadTallaRelacional = prenda.cantidad_talla; // { DAMA: { S: 20, M: 20 } }
        
        console.debug('[PrendaCardSizing][Tallas] construirTallasYCantidades entrada', {
            prenda_id: prenda?.id || prenda?.prenda_pedido_id || null,
            indice,
            cantidad_talla: cantidadTallaRelacional,
            generosConTallas,
            tallas
        });

        // Intentar obtener cantidades desde cantidad_talla (nuevo formato relacional)
        let cantidadesPorTalla = prenda.cantidadesPorTalla || {};
        
        //  Si viene formato relacional, convertirlo
        if (cantidadTallaRelacional && typeof cantidadTallaRelacional === 'object' && !Array.isArray(cantidadTallaRelacional)) {
            // Convertir { DAMA: { S: 20, M: 20 } } → { 'dama-S': 20, 'dama-M': 20 }
            Object.entries(cantidadTallaRelacional).forEach(([genero, tallasObj]) => {
                if (typeof tallasObj === 'object') {
                    Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                        const cantidadNumerica = parseInt(cantidad, 10) || 0;
                        if (cantidadNumerica <= 0) {
                            return;
                        }

                        const tallaNormalizada = String(talla || '').trim().toUpperCase();
                        const claveTalla = tallaNormalizada || 'SOBREMEDIDA';
                        cantidadesPorTalla[`${genero.toLowerCase()}-${claveTalla}`] = cantidadNumerica;
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
                    const tallaNormalizada = String(talla || '').trim().toUpperCase();
                    const tallaMostrable = tallaNormalizada && tallaNormalizada !== 'NULL' && tallaNormalizada !== 'UNDEFINED'
                        ? tallaNormalizada
                        : 'SOBREMEDIDA';
                    if (!generosMap[genero].includes(tallaMostrable)) {
                        generosMap[genero].push(tallaMostrable);
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
                const tallaNormalizada = String(talla || '').trim().toUpperCase();
                const tallaMostrable = tallaNormalizada && tallaNormalizada !== 'NULL' && tallaNormalizada !== 'UNDEFINED'
                    ? tallaNormalizada
                    : 'SOBREMEDIDA';
                cantidadesPorGenero[genero][tallaMostrable] = cantidad;
            }
        });
        
        const totalCantidades = Object.keys(cantidadesPorTalla).length;

        console.debug('[PrendaCardSizing][Tallas] construirTallasYCantidades salida', {
            prenda_id: prenda?.id || prenda?.prenda_pedido_id || null,
            totalTallas,
            totalCantidades,
            tallasByGeneroMap,
            cantidadesPorGenero,
            cantidadesPorTalla,
            generosConTallas
        });

if (totalTallas === 0) {

            return '';
        }

        let generoHTML = '';
        
        // ── Detectar si SOLO hay GENERICO (SOLO CANTIDAD) ──
        const tieneGenerico = Object.keys(tallasByGeneroMap).some(g => g.toUpperCase() === 'GENERICO');
        const soloGenerico = tieneGenerico && Object.keys(tallasByGeneroMap).length === 1;
        
        // Si SOLO hay GENERICO, mostrar la cantidad de forma simple
        if (soloGenerico) {
            const cantidadKey = Object.keys(tallasByGeneroMap).find(g => g.toUpperCase() === 'GENERICO');
            const cantidadSolo = cantidadesPorGenero[cantidadKey]?.['SIN_ESPECIFICAR'] || 
                                 cantidadesPorGenero[cantidadKey]?.['sin_especificar'] ||
                                 Object.values(cantidadesPorGenero[cantidadKey] || {})[0] || 0;
            
            generoHTML = `
                <div style="display: flex; align-items: center; justify-content: center; padding: 1rem; background: #f0f9ff; border-radius: 8px; border: 2px solid #0ea5e9;">
                    <div style="text-align: center;">
                        <div style="font-size: 0.8rem; color: #475569; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 0.5rem;">
                            Cantidad
                        </div>
                        <div style="font-size: 2.5rem; font-weight: 700; color: #0369a1; line-height: 1;">
                            ${cantidadSolo}
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Flujo normal: iterar sobre géneros pero saltar GENERICO
            Object.keys(tallasByGeneroMap).forEach((genero, idx) => {
                //  NUEVO: NO RENDERIZAR GÉNERO "GENERICO" (SOLO CANTIDAD)
                if (genero.toUpperCase() === 'GENERICO') return;
                
                const tallasList = tallasByGeneroMap[genero] || [];
                const cantidadesGen = cantidadesPorGenero[genero] || {};
                
                if (tallasList.length === 0) return;
                
                // Obtener asignaciones de colores
                const asignacionesColores = owner._obtenerDataUtils().resolverAsignacionesColoresPorTalla(prenda);
                
                const tallasConCantidad = tallasList.map(talla => {
                    const tallaNormalizada = String(talla || '').trim().toUpperCase();
                    const tallaMostrable = tallaNormalizada && tallaNormalizada !== 'NULL' && tallaNormalizada !== 'UNDEFINED'
                        ? tallaNormalizada
                        : 'SOBREMEDIDA';
                    const cantidad = cantidadesGen[tallaMostrable] || cantidadesGen[tallaNormalizada] || cantidadesGen.SOBREMEDIDA || 0;
                    
                    // Buscar colores asignados para esta talla-género
                    let coloresHTML = '';
                    const asignacion = this._buscarAsignacionColor(asignacionesColores, genero, tallaMostrable);
                    if (asignacion && asignacion.colores && asignacion.colores.length > 0) {
                        const imagenesAsignacion = owner._normalizarImagenes(asignacion.imagenes)
                            .map((img) => owner._normalizarSrcImagen(img))
                            .filter(Boolean);
                        const totalColores = asignacion.colores.length;
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

                        const imagenesAsignacionDet = owner._normalizarImagenes(asignacion.imagenes)
                            .map((img) => owner._normalizarSrcImagen(img))
                            .filter(Boolean);
                        const totalColoresDet = asignacion.colores.length;
                        const coloresDetallados = asignacion.colores.map((color) => {
                            const nombreColor = color.nombre || color.color || 'Sin nombre';
                            const cantidadColor = parseInt(color.cantidad, 10) || 0;
                            const referenciaColor = color.referencia || asignacion.referencia || '';
                            const imagenColor = owner._obtenerImagenColorAsignacion(color, imagenesAsignacionDet[0] || null, ctx);
                            const mostrarCantidad = totalColoresDet > 1 || cantidadColor !== cantidad;

                            return `
                                <div style="display: flex; align-items: center; gap: 0.55rem; padding: 0.45rem 0.5rem; background: rgba(255,255,255,0.92); border: 1px solid rgba(125,211,252,0.45); border-radius: 8px;">
                                    ${imagenColor ? `
                                        <img src="${imagenColor}" alt="${owner._escapeHtml(nombreColor)}" style="width: 38px; height: 38px; object-fit: cover; border-radius: 7px; border: 1px solid rgba(14,165,233,0.25); flex-shrink: 0;" />
                                    ` : `
                                        <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 7px; background: #e0f2fe; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <span style="display: inline-block; width: 10px; height: 10px; background: #0ea5e9; border-radius: 999px;"></span>
                                        </span>
                                    `}
                                    <div style="min-width: 0; display: flex; flex-direction: column; gap: 0.1rem;">
                                        <span style="font-size: 0.72rem; font-weight: 700; color: #0f172a; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${owner._escapeHtml(nombreColor)}</span>
                                        ${referenciaColor ? `<span style="font-size: 0.64rem; color: #64748b; line-height: 1.1;">Ref: ${owner._escapeHtml(referenciaColor)}</span>` : ''}
                                    </div>
                                    ${mostrarCantidad ? `<span style="margin-left: auto; background: #e0f2fe; color: #0369a1; min-width: 28px; text-align: center; padding: 0.18rem 0.45rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800;">${cantidadColor}</span>` : ''}
                                </div>
                            `;
                        }).join('');

                        coloresHTML = `
                            <div style="margin-top: 0.55rem; border-top: 1px solid rgba(125,211,252,0.35); padding-top: 0.45rem; display: grid; gap: 0.4rem;">
                                ${coloresDetallados}
                            </div>
                        `;
                    }
                    
                    return `
                        <div style="background: linear-gradient(180deg, #dbeafe 0%, #eff6ff 100%); padding: 0.7rem 0.8rem; border-radius: 10px; font-weight: 600; color: #0369a1; border: 1px solid #7dd3fc; min-width: 220px; flex: 1 1 220px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.45);">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 0;">
                                    <span style="display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 999px; background: rgba(255,255,255,0.8); color: #0284c7; flex-shrink: 0;">
                                        <i class="fas fa-ruler" style="font-size: 0.8rem;"></i>
                                    </span>
                                    <span style="font-size: 0.92rem; font-weight: 800; color: #075985; letter-spacing: 0.02em;">${owner._escapeHtml(tallaMostrable)}</span>
                                </div>
                                <span style="background: #0369a1; color: white; padding: 0.24rem 0.65rem; border-radius: 999px; font-size: 0.82rem; font-weight: 800; line-height: 1;">${cantidad}</span>
                            </div>
                            ${asignacion && asignacion.colores && asignacion.colores.length > 0 ? `
                            <div style="margin-top: 0.35rem; font-size: 0.68rem; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">
                                Colores asignados
                            </div>
                            ${coloresHTML}
                            ` : ''}
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
        }
        
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

    construirSeccionCombinada(owner, prenda, indice, ctx) {
        // ── Obtener telas ──
        let telas = [];
        if (prenda.telasAgregadas) {
            telas = owner._normalizarTelas(prenda.telasAgregadas);
        } else if (prenda.telas) {
            telas = owner._normalizarTelas(prenda.telas);
        }
        const telasParaVista = owner._deduplicarTelasParaVista(telas);

        // ── Obtener tallas (misma lógica de _construirTallasYCantidades) ──
        let generosConTallas = prenda.generosConTallas;
        let cantidadTallaRelacional = prenda.cantidad_talla;
        let cantidadesPorTalla = prenda.cantidadesPorTalla || {};

        console.debug('[PrendaCardSizing][Tallas] construirSeccionCombinada entrada', {
            prenda_id: prenda?.id || prenda?.prenda_pedido_id || null,
            indice,
            cantidad_talla: cantidadTallaRelacional,
            generosConTallas,
            tallas: prenda.tallas
        });

        if (cantidadTallaRelacional && typeof cantidadTallaRelacional === 'object' && !Array.isArray(cantidadTallaRelacional)) {
            Object.entries(cantidadTallaRelacional).forEach(([genero, tallasObj]) => {
                if (typeof tallasObj === 'object') {
                    Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                        const cantidadNumerica = parseInt(cantidad, 10) || 0;
                        if (cantidadNumerica <= 0) {
                            return;
                        }

                        const tallaNormalizada = String(talla || '').trim().toUpperCase();
                        const claveTalla = tallaNormalizada || 'SOBREMEDIDA';
                        cantidadesPorTalla[`${genero.toLowerCase()}-${claveTalla}`] = cantidadNumerica;
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
                    const tallaNormalizada = String(talla || '').trim().toUpperCase();
                    const tallaMostrable = tallaNormalizada && tallaNormalizada !== 'NULL' && tallaNormalizada !== 'UNDEFINED'
                        ? tallaNormalizada
                        : 'SOBREMEDIDA';
                    if (!generosMap[genero].includes(tallaMostrable)) generosMap[genero].push(tallaMostrable);
                }
            });
            tallasByGeneroMap = generosMap;
            totalTallas = Object.values(generosMap).reduce((s, t) => s + t.length, 0);
        }

        Object.entries(cantidadesPorTalla).forEach(([clave, cantidad]) => {
            const [genero, talla] = clave.split('-');
            if (genero && talla) {
                if (!cantidadesPorGenero[genero]) cantidadesPorGenero[genero] = {};
                const tallaNormalizada = String(talla || '').trim().toUpperCase();
                const tallaMostrable = tallaNormalizada && tallaNormalizada !== 'NULL' && tallaNormalizada !== 'UNDEFINED'
                    ? tallaNormalizada
                    : 'SOBREMEDIDA';
                cantidadesPorGenero[genero][tallaMostrable] = cantidad;
            }
        });

        console.debug('[PrendaCardSizing][Tallas] construirSeccionCombinada salida', {
            prenda_id: prenda?.id || prenda?.prenda_pedido_id || null,
            totalTallas,
            tallasByGeneroMap,
            cantidadesPorGenero,
            cantidadesPorTalla
        });

        if (totalTallas === 0 && telasParaVista.length === 0) return '';

        const asignacionesColores = owner._obtenerDataUtils().resolverAsignacionesColoresPorTalla(prenda);

        // ── Construir HTML de telas (mini-badges en vez de tabla) ──
        let telasInfoHTML = '';
        if (telasParaVista.length > 0) {
            const telasBadges = telasParaVista.map((t, idx) => {
                const nombre = t.tela || t.nombre_tela || 'N/A';
                const telaFoto = owner._obtenerFotoTelaDesdeRelacion(prenda, t, ctx);

                const referencias = [t.referencia].filter(Boolean);
                const colores = Array.isArray(t._displayColores) ? t._displayColores.filter(Boolean) : [];
                const partesDetalle = [];
                if (colores.length > 0) {
                    partesDetalle.push(`<span style="color: #64748b;">Colores: <b>${owner._escapeHtml(colores.join(', '))}</b></span>`);
                }
                let detalles = partesDetalle.join(' · ');
                const ref = referencias.length > 0 ? owner._escapeHtml(referencias.join(', ')) : '';
                const obs = t._displayCount > 1 ? `Registros: ${t._displayCount}` : '';
                if (ref && ref !== 'N/A' && ref !== '') detalles += `${detalles ? ' · ' : ''}<span style="color: #64748b;">Ref: <b>${ref}</b></span>`;
                if (obs) detalles += `${detalles ? ' · ' : ''}<span style="color: #64748b;">Obs: <b>${obs}</b></span>`;

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
                            ${detalles ? `<div style="font-size: 0.73rem; margin-top: 0.15rem; color: #64748b;">${detalles}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');

            telasInfoHTML = `
                <div style="margin-bottom: 1rem;">
                    <div style="font-weight: 600; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                        <i class="fas fa-scroll" style="color: #0ea5e9; font-size: 0.75rem;"></i> Tela${telasParaVista.length > 1 ? 's' : ''}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        ${telasBadges}
                    </div>
                </div>
                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0.75rem 0;">
            `;
        }

        // ── Detectar si SOLO hay GENERICO (SOLO CANTIDAD) ──
        const tieneGenerico = Object.keys(tallasByGeneroMap).some(g => g.toUpperCase() === 'GENERICO');
        const soloGenerico = tieneGenerico && Object.keys(tallasByGeneroMap).length === 1;
        
        let generoHTML = '';
        
        // Si SOLO hay GENERICO, mostrar la cantidad de forma simple
        if (soloGenerico) {
            const cantidadKey = Object.keys(tallasByGeneroMap).find(g => g.toUpperCase() === 'GENERICO');
            const cantidadSolo = cantidadesPorGenero[cantidadKey]?.['SIN_ESPECIFICAR'] || 
                                 cantidadesPorGenero[cantidadKey]?.['sin_especificar'] ||
                                 Object.values(cantidadesPorGenero[cantidadKey] || {})[0] || 0;
            
            generoHTML = `
                <div style="display: flex; align-items: center; justify-content: center; padding: 1rem; background: #f0f9ff; border-radius: 8px; border: 2px solid #0ea5e9;">
                    <div style="text-align: center;">
                        <div style="font-size: 0.8rem; color: #475569; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 0.5rem;">
                            Cantidad
                        </div>
                        <div style="font-size: 2.5rem; font-weight: 700; color: #0369a1; line-height: 1;">
                            ${cantidadSolo}
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Flujo normal: iterar sobre géneros pero saltar GENERICO
            Object.keys(tallasByGeneroMap).forEach(genero => {
                //  NUEVO: NO RENDERIZAR GÉNERO "GENERICO" (SOLO CANTIDAD)
                if (genero.toUpperCase() === 'GENERICO') return;
                
                const tallasList = tallasByGeneroMap[genero] || [];
                const cantidadesGen = cantidadesPorGenero[genero] || {};
                if (tallasList.length === 0) return;

                const tallasConCantidad = tallasList.map(talla => {
                    const tallaNormalizada = String(talla || '').trim().toUpperCase();
                    const tallaMostrable = tallaNormalizada && tallaNormalizada !== 'NULL' && tallaNormalizada !== 'UNDEFINED'
                        ? tallaNormalizada
                        : 'SOBREMEDIDA';
                    const cantidad = cantidadesGen[tallaMostrable] || cantidadesGen[tallaNormalizada] || cantidadesGen.SOBREMEDIDA || 0;
                    let coloresHTML = '';
                    const asignacion = this._buscarAsignacionColor(asignacionesColores, genero, tallaMostrable);
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

                        const imagenesAsignacionDet = owner._normalizarImagenes(asignacion.imagenes)
                            .map((img) => owner._normalizarSrcImagen(img))
                            .filter(Boolean);
                        const totalColoresDet = asignacion.colores.length;
                        const coloresDetallados = asignacion.colores.map((color) => {
                            const nombreColor = color.nombre || color.color || 'Sin nombre';
                            const cantidadColor = parseInt(color.cantidad, 10) || 0;
                            const referenciaColor = color.referencia || asignacion.referencia || '';
                            const imagenColor = owner._obtenerImagenColorAsignacion(color, imagenesAsignacionDet[0] || null, ctx);
                            const mostrarCantidad = totalColoresDet > 1 || cantidadColor !== cantidad;

                            return `
                                <div style="display: flex; align-items: center; gap: 0.55rem; padding: 0.45rem 0.5rem; background: rgba(255,255,255,0.92); border: 1px solid rgba(125,211,252,0.45); border-radius: 8px;">
                                    ${imagenColor ? `
                                        <img src="${imagenColor}" alt="${owner._escapeHtml(nombreColor)}" style="width: 38px; height: 38px; object-fit: cover; border-radius: 7px; border: 1px solid rgba(14,165,233,0.25); flex-shrink: 0;" />
                                    ` : `
                                        <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 7px; background: #e0f2fe; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <span style="display: inline-block; width: 10px; height: 10px; background: #0ea5e9; border-radius: 999px;"></span>
                                        </span>
                                    `}
                                    <div style="min-width: 0; display: flex; flex-direction: column; gap: 0.1rem;">
                                        <span style="font-size: 0.72rem; font-weight: 700; color: #0f172a; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${owner._escapeHtml(nombreColor)}</span>
                                        ${referenciaColor ? `<span style="font-size: 0.64rem; color: #64748b; line-height: 1.1;">Ref: ${owner._escapeHtml(referenciaColor)}</span>` : ''}
                                    </div>
                                    ${mostrarCantidad ? `<span style="margin-left: auto; background: #e0f2fe; color: #0369a1; min-width: 28px; text-align: center; padding: 0.18rem 0.45rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800;">${cantidadColor}</span>` : ''}
                                </div>
                            `;
                        }).join('');

                        coloresHTML = `
                            <div style="margin-top: 0.55rem; border-top: 1px solid rgba(125,211,252,0.35); padding-top: 0.45rem; display: grid; gap: 0.4rem;">
                                ${coloresDetallados}
                            </div>
                        `;
                    }

                    return `
                        <div style="background: linear-gradient(180deg, #dbeafe 0%, #eff6ff 100%); padding: 0.7rem 0.8rem; border-radius: 10px; font-weight: 600; color: #0369a1; border: 1px solid #7dd3fc; min-width: 220px; flex: 1 1 220px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.45);">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 0;">
                                    <span style="display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 999px; background: rgba(255,255,255,0.8); color: #0284c7; flex-shrink: 0;">
                                        <i class="fas fa-ruler" style="font-size: 0.8rem;"></i>
                                    </span>
                                <span style="font-size: 0.92rem; font-weight: 800; color: #075985; letter-spacing: 0.02em;">${owner._escapeHtml(tallaMostrable)}</span>
                            </div>
                                <span style="background: #0369a1; color: white; padding: 0.24rem 0.65rem; border-radius: 999px; font-size: 0.82rem; font-weight: 800; line-height: 1;">${cantidad}</span>
                            </div>
                            ${asignacion && asignacion.colores && asignacion.colores.length > 0 ? `
                            <div style="margin-top: 0.35rem; font-size: 0.68rem; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">
                                Colores asignados
                            </div>
                            ${coloresHTML}
                            ` : ''}
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
        }

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
    
    }
};
