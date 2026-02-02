// ===== FUNCIONES PARA MODAL DE COTIZACIÓN =====

/**
 * Abre el modal de detalle de cotización
 * @param {number} cotizacionId - ID de la cotización
 */
function openCotizacionModal(cotizacionId) {


    fetch(`/contador/cotizacion/${cotizacionId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {


            // Actualizar header del modal con información de la cotización
            if (data.cotizacion) {
                const cot = data.cotizacion;
                document.getElementById('modalHeaderNumber').textContent = cot.numero_cotizacion || 'N/A';
                document.getElementById('modalHeaderDate').textContent = cot.created_at ? new Date(cot.created_at).toLocaleDateString('es-ES') : 'N/A';
                document.getElementById('modalHeaderClient').textContent = cot.nombre_cliente || 'N/A';
                document.getElementById('modalHeaderAdvisor').textContent = cot.asesora_nombre || 'N/A';
            }

            // Construir HTML del modal sin el encabezado (que ya está en el layout)
            let html = '';
            
            // No se crean tabs - el logo se integrará directamente en las prendas
            const tieneTabsNecesarios = false;

            // Construir contenido de prendas
            let htmlPrendas = '';

            // Contenedor de prendas
            htmlPrendas += '<div class="prendas-container" style="display: flex; flex-direction: column; gap: 1.5rem;">';

            if (data.prendas_cotizaciones && data.prendas_cotizaciones.length > 0) {
                data.prendas_cotizaciones.forEach((prenda, index) => {


                    // Construir atributos principales
                    let atributosLinea = [];

                    // Obtener color de variantes o telas
                    let color = '';
                    if (prenda.variantes && prenda.variantes.length > 0 && prenda.variantes[0].color) {
                        color = prenda.variantes[0].color;
                    }

                    // Obtener tela de telas o de logo_cotizacion.telas_prendas
                    let telaInfo = '';
                    let imgTela = '';
                    
                    // Si es cotización logo, buscar en telas_prendas
                    if (data.logo_cotizacion && data.logo_cotizacion.telas_prendas && data.logo_cotizacion.telas_prendas.length > 0) {
                        const telaPrenda = data.logo_cotizacion.telas_prendas.find(tp => tp.prenda_cot_id === prenda.id);
                        if (telaPrenda) {
                            telaInfo = telaPrenda.tela || '';
                            if (telaPrenda.color) {
                                telaInfo += telaPrenda.tela ? ` | ${telaPrenda.color}` : telaPrenda.color;
                            }
                            if (telaPrenda.ref) {
                                telaInfo += ` REF:${telaPrenda.ref}`;
                            }
                            // Obtener la imagen de la tela
                            if (telaPrenda.img) {
                                imgTela = telaPrenda.img;
                                // Convertir ruta storage/app/public/... a ruta pública
                                if (!imgTela.startsWith('http')) {
                                    imgTela = '/storage/' + imgTela.replace('storage/app/public/', '');
                                }
                            }
                        }
                    } 
                    // Si es cotización reflectivo, buscar en prenda_cot_reflectivo.color_tela_ref
                    else if (data.cotizacion && data.cotizacion.tipo_cotizacion_id === 4 && prenda.prenda_cot_reflectivo && prenda.prenda_cot_reflectivo.color_tela_ref) {
                        const colorTelaRef = Array.isArray(prenda.prenda_cot_reflectivo.color_tela_ref) ? prenda.prenda_cot_reflectivo.color_tela_ref[0] : prenda.prenda_cot_reflectivo.color_tela_ref;
                        if (colorTelaRef) {
                            telaInfo = colorTelaRef.tela || '';
                            if (colorTelaRef.color) {
                                telaInfo += colorTelaRef.tela ? ` | ${colorTelaRef.color}` : colorTelaRef.color;
                            }
                            if (colorTelaRef.referencia) {
                                telaInfo += ` REF:${colorTelaRef.referencia}`;
                            }
                            // Obtener la imagen de la tela si existe en fotos
                            if (colorTelaRef.fotos && Array.isArray(colorTelaRef.fotos) && colorTelaRef.fotos.length > 0) {
                                imgTela = colorTelaRef.fotos[0];
                                if (!imgTela.startsWith('http')) {
                                    imgTela = '/storage/' + imgTela.replace('storage/app/public/', '');
                                }
                            }
                        }
                    }
                    // Si no es logo ni reflectivo, usar telas combinadas
                    else if (prenda.telas && prenda.telas.length > 0) {
                        const tela = prenda.telas[0];
                        telaInfo = tela.nombre_tela || '';
                        if (tela.referencia) {
                            telaInfo += ` REF:${tela.referencia}`;
                        }
                    }

                    // Obtener manga de variantes
                    let manga = '';
                    if (prenda.variantes && prenda.variantes.length > 0 && prenda.variantes[0].tipo_manga) {
                        manga = prenda.variantes[0].tipo_manga;
                    }

                    // Obtener manga de variantes
                    let manguaInfo = '';
                    if (prenda.variantes && prenda.variantes.length > 0) {
                        const variante = prenda.variantes[0];
                        if (variante.manga && variante.manga.nombre) {
                            manguaInfo = variante.manga.nombre;
                        }
                    }

                    if (color) atributosLinea.push(`Color: ${color}`);
                    if (telaInfo) atributosLinea.push(`Tela: ${telaInfo}`);
                    if (manguaInfo) atributosLinea.push(`Manga: ${manguaInfo}`);

                    // Construir HTML de la prenda
                    htmlPrendas += `
                        <div class="prenda-card" style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">
                                ${prenda.nombre_prenda || 'Sin nombre'}
                            </h3>
                            <p style="margin: 0 0 0.75rem 0; color: #666; font-size: 0.9rem; font-weight: 500;">
                                ${atributosLinea.join(' | ') || ''}
                            </p>
                            <div style="margin: 0 0 1rem 0; color: #333; font-size: 0.85rem; line-height: 1.6;">
                                <span style="color: #1e5ba8; font-weight: 700;">DESCRIPCION:</span> ${(() => {
                                    let descripcionCompleta = prenda.descripcion_formateada || prenda.descripcion || '';
                                    
                                    // LIMPIEZA: Remover Bolsillos y Broche/Botón de la descripción concatenada
                                    // Patrón 1: Bolsillos: ... (sin viñeta, hasta siguiente palabra clave)
                                    descripcionCompleta = descripcionCompleta.replace(/Bolsillos:\s*[^,]*(?:,\s*)?/gi, '');
                                    // Patrón 2: Botón: ... o Broche: ... (sin viñeta, hasta siguiente palabra clave)
                                    descripcionCompleta = descripcionCompleta.replace(/(Botón|Broche):\s*[^,]*(?:,\s*)?/gi, '');
                                    // Patrón 3: • BOLSILLOS: ... (si tiene viñeta)
                                    descripcionCompleta = descripcionCompleta.replace(/\s*•\s*BOLSILLOS:.*?(?=•|$)/gi, '');
                                    // Patrón 4: • BROCHE: ... o • BOTÓN: ... (si tiene viñeta)
                                    descripcionCompleta = descripcionCompleta.replace(/\s*•\s*(BROCHE|BOTÓN):.*?(?=•|$)/gi, '');
                                    // Limpiar espacios múltiples y comas al inicio/final
                                    descripcionCompleta = descripcionCompleta.replace(/\s+/g, ' ').replace(/^,\s*|,\s*$/g, '').trim();
                                    
                                    // Si hay técnicas de logo para esta prenda, agregar ubicaciones
                                    const tecnicasPrendaArray = data.logo_cotizacion && data.logo_cotizacion.tecnicas_prendas 
                                        ? data.logo_cotizacion.tecnicas_prendas.filter(tp => tp.prenda_id === prenda.id)
                                        : [];
                                    
                                    if (tecnicasPrendaArray && tecnicasPrendaArray.length > 0) {
                                        // Consolidar ubicaciones por técnica
                                        const ubicacionesPorTecnica = {};
                                        tecnicasPrendaArray.forEach(tp => {
                                            const tecnicaNombre = tp.tipo_logo_nombre || 'Logo';
                                            if (tp.ubicaciones) {
                                                let ubicacionesArray = Array.isArray(tp.ubicaciones) ? tp.ubicaciones : [String(tp.ubicaciones)];
                                                // Filtrar vacíos y remover corchetes y comillas
                                                ubicacionesArray = ubicacionesArray
                                                    .map(u => String(u).replace(/[\[\]"']/g, '').trim())
                                                    .filter(u => u);
                                                if (ubicacionesArray.length > 0) {
                                                    if (!ubicacionesPorTecnica[tecnicaNombre]) {
                                                        ubicacionesPorTecnica[tecnicaNombre] = [];
                                                    }
                                                    ubicacionesPorTecnica[tecnicaNombre] = ubicacionesPorTecnica[tecnicaNombre].concat(ubicacionesArray);
                                                }
                                            }
                                        });
                                        
                                        // Agregar ubicaciones a la descripción SIN corchetes
                                        if (Object.keys(ubicacionesPorTecnica).length > 0) {
                                            // Solo agregar coma si ya hay descripción
                                            if (descripcionCompleta) {
                                                descripcionCompleta += ', ';
                                            }
                                            const ubicacionesTexto = Object.entries(ubicacionesPorTecnica)
                                                .map(([tecnica, ubicaciones]) => ubicaciones.join(', '))
                                                .join(', ');
                                            descripcionCompleta += ubicacionesTexto;
                                        }
                                    }
                                    
                                    // Agregar descripción y ubicaciones de prenda_cot_reflectivo
                                    console.log('Prenda:', prenda.nombre_prenda, 'prenda_cot_reflectivo:', prenda.prenda_cot_reflectivo);
                                    if (prenda.prenda_cot_reflectivo) {
                                        const pcrRef = prenda.prenda_cot_reflectivo;
                                        
                                        // Agregar descripción del reflectivo
                                        if (pcrRef.descripcion) {
                                            if (descripcionCompleta) {
                                                descripcionCompleta += ', ';
                                            }
                                            descripcionCompleta += pcrRef.descripcion;
                                        }
                                        
                                        // Agregar ubicaciones del reflectivo SIN negrita
                                        if (pcrRef.ubicaciones && Array.isArray(pcrRef.ubicaciones)) {
                                            if (descripcionCompleta) {
                                                descripcionCompleta += ', ';
                                            }
                                            const ubicacionesReflectivo = pcrRef.ubicaciones
                                                .map(u => u.ubicacion ? u.ubicacion + (u.descripcion ? ': ' + u.descripcion : '') : '')
                                                .filter(u => u)
                                                .join(', ');
                                            descripcionCompleta += ubicacionesReflectivo;
                                        }
                                    }
                                    
                                    return descripcionCompleta.replace(/\n/g, '<br>') || '-';
                                })()}
                            </div>
                    `;

                    // Mostrar tallas si existen
                    if (prenda.tallas && prenda.tallas.length > 0) {
                        const tallasTexto = prenda.tallas.map(t => t.talla).join(', ');
                        const textoPersonalizado = prenda.texto_personalizado_tallas ? ` ${prenda.texto_personalizado_tallas}` : '';
                        const textoCompleto = tallasTexto + textoPersonalizado;
                        
                        htmlPrendas += `
                            <div style="margin: 0 0 0.5rem 0;">
                                <span style="color: #1e5ba8; font-size: 0.9rem; font-weight: 700;">Tallas: </span>
                                <span 
                                    id="tallas-prenda-${prenda.id}" 
                                    ondblclick="editarTallasPersonalizado(this, ${prenda.id}, '${tallasTexto}', '${prenda.texto_personalizado_tallas || ''}')"
                                    style="color: #ef4444; font-weight: 700; font-size: 0.9rem; cursor: pointer; padding: 0.25rem 0.5rem; border-radius: 4px; transition: all 0.2s; display: inline-block;"
                                    onmouseover="this.style.backgroundColor='#fee2e2'"
                                    onmouseout="this.style.backgroundColor='transparent'"
                                    title="Doble click para editar"
                                >${textoCompleto}</span>
                            </div>
                        `;
                    }

                    // Renderizar variaciones de técnicas prendas si existen (solo para cotizaciones con logo)
                    if (data.logo_cotizacion && data.logo_cotizacion.tecnicas_prendas && Array.isArray(data.logo_cotizacion.tecnicas_prendas)) {
                        // Buscar técnicas prendas para esta prenda
                        const tecnicasPrendaArray = data.logo_cotizacion.tecnicas_prendas.filter(tp => tp.prenda_id === prenda.id);
                        
                        if (tecnicasPrendaArray.length > 0) {
                            // Consolidar todas las variaciones
                            const variacionesFormateadas = {};
                            tecnicasPrendaArray.forEach(tp => {
                                if (tp.variaciones_prenda && typeof tp.variaciones_prenda === 'object') {
                                    for (const [opcionNombre, detalles] of Object.entries(tp.variaciones_prenda)) {
                                        if (typeof detalles === 'object' && detalles.opcion) {
                                            const nombreFormato = opcionNombre.charAt(0).toUpperCase() + opcionNombre.slice(1).replace(/_/g, ' ');
                                            if (!variacionesFormateadas[nombreFormato]) {
                                                variacionesFormateadas[nombreFormato] = detalles;
                                            }
                                        }
                                    }
                                }
                            });
                            
                            // Si hay variaciones, renderizar la tabla
                            if (Object.keys(variacionesFormateadas).length > 0) {
                                htmlPrendas += `
                                    <div style="margin-top: 1rem;">
                                        <h6 style="color: #1e5ba8; font-weight: 600; margin: 0 0 0.75rem 0; font-size: 0.95rem;">VARIACIONES</h6>
                                        <table style="border-collapse: collapse; table-layout: auto; width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden;">
                                            <thead>
                                                <tr style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white;">
                                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 200px; border-right: 1px solid rgba(255,255,255,0.2);">Tipo</th>
                                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 250px; border-right: 1px solid rgba(255,255,255,0.2);">Valor</th>
                                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 200px;">Observación</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                `;
                                
                                for (const [tipo, datos] of Object.entries(variacionesFormateadas)) {
                                    const opcion = datos.opcion || '-';
                                    const observacion = datos.observacion || '-';
                                    htmlPrendas += `
                                        <tr style="border-bottom: 1px solid #e2e8f0;">
                                            <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; font-weight: 600; color: #0f172a;">${tipo}</td>
                                            <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; color: #0ea5e9; font-weight: 500;">${opcion}</td>
                                            <td style="padding: 0.75rem; color: #64748b;">${observacion}</td>
                                        </tr>
                                    `;
                                }
                                
                                htmlPrendas += `
                                            </tbody>
                                        </table>
                                    </div>
                                `;
                            }

                            // TALLAS Y CANTIDADES
                            if (tecnicasPrendaArray.some(tp => tp.talla_cantidad && (Array.isArray(tp.talla_cantidad) ? tp.talla_cantidad.length > 0 : Object.keys(tp.talla_cantidad).length > 0))) {
                                // Consolidar tallas de todas las técnicas
                                const tallasSet = new Set();
                                tecnicasPrendaArray.forEach(tp => {
                                    if (tp.talla_cantidad) {
                                        let tallaArray = [];
                                        
                                        // Si es array directo
                                        if (Array.isArray(tp.talla_cantidad)) {
                                            tallaArray = tp.talla_cantidad;
                                        } 
                                        // Si es objeto con tallas
                                        else if (typeof tp.talla_cantidad === 'object') {
                                            tallaArray = Object.values(tp.talla_cantidad);
                                        }

                                        tallaArray.forEach(item => {
                                            if (item && item.talla) {
                                                tallasSet.add(item.talla);
                                            }
                                        });
                                    }
                                });

                                if (tallasSet.size > 0) {
                                    const tallasTexto = Array.from(tallasSet).join(',');
                                    htmlPrendas += `
                                        <div style="margin-top: 1rem;">
                                            <span style="color: #1e5ba8; font-weight: 600; font-size: 0.95rem;">TALLAS </span>
                                            <span id="tallas-texto-${prenda.id}" 
                                                  data-prenda-id="${prenda.id}"
                                                  data-cotizacion-id="${data.cotizacion.id}"
                                                  ondblclick="editarTallasConParentesis(this)"
                                                  style="color: #dc2626; font-weight: 700; font-size: 1rem; cursor: pointer; padding: 0.25rem 0.5rem; border-radius: 4px; transition: all 0.2s; display: inline-block;"
                                                  onmouseover="this.style.backgroundColor='#fee2e2'"
                                                  onmouseout="this.style.backgroundColor='transparent'"
                                                  title="Doble click para editar">
                                                ${tallasTexto} ()
                                            </span>
                                        </div>
                                    `;
                                }
                            }
                        }
                    }

                    // ===== TABLA DE VARIANTES DE LA PRENDA =====
                    if (prenda.variantes && prenda.variantes.length > 0) {
                        htmlPrendas += `
                            <div style="margin-top: 1.5rem;">
                                <h6 style="color: #1e5ba8; font-weight: 600; margin: 0 0 0.75rem 0; font-size: 0.95rem;">VARIACIONES ESPECIFICAS</h6>
                                <table style="border-collapse: collapse; table-layout: auto; width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden;">
                                    <thead>
                                        <tr style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white;">
                                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); font-size: 0.85rem; min-width: 150px;">Variación</th>
                                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); font-size: 0.85rem; min-width: 200px;">Tipo</th>
                                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">Observaciones</th>
                                        </tr>
                            </thead>
                            <tbody>
                        `;

                        const variante = prenda.variantes[0];
                        
                        // Construir filas de variaciones con estructura: variacion, tipo, observacion
                        const filas = [];
                        
                        // Si es cotización de reflectivo (tipo_cotizacion_id === 4), usar variaciones de prenda_cot_reflectivo
                        if (data.cotizacion && data.cotizacion.tipo_cotizacion_id === 4 && prenda.prenda_cot_reflectivo && prenda.prenda_cot_reflectivo.variaciones && Array.isArray(prenda.prenda_cot_reflectivo.variaciones)) {
                            // Filtrar solo variaciones con checked: true
                            const variacionesSeleccionadas = prenda.prenda_cot_reflectivo.variaciones.filter(v => v.checked === true || v.checked === 'true');
                            
                            variacionesSeleccionadas.forEach(variacion => {
                                filas.push({
                                    variacion: variacion.variacion || '-',
                                    tipo: variacion.observacion || '-',
                                    obs: ''
                                });
                            });
                        } else {
                            // Caso normal (no reflectivo)
                            if (variante.tipo_prenda) filas.push({ variacion: 'Tipo Prenda', tipo: variante.tipo_prenda, obs: '' });
                            if (variante.tipo_jean_pantalon) filas.push({ variacion: 'Tipo Jean/Pantalón', tipo: variante.tipo_jean_pantalon, obs: '' });
                            
                            // Tipo Manga
                            if (variante.tipo_manga_id || variante.tipo_manga) {
                                let tipo = variante.tipo_manga_nombre || variante.tipo_manga || 'Sin especificar';
                                filas.push({ variacion: 'Tipo Manga', tipo: tipo, obs: variante.obs_manga || '' });
                            }
                            
                            // Bolsillos: si tiene_bolsillos = true, mostrar en la tabla de variaciones
                            if (variante.tiene_bolsillos !== null && variante.tiene_bolsillos) {
                                let obs = variante.obs_bolsillos || '';
                                filas.push({ variacion: 'Bolsillos', tipo: 'Sí', obs: obs });
                            } else if (variante.obs_bolsillos) {
                                // Si hay observación de bolsillos pero tiene_bolsillos es false, aún mostrar la fila
                                filas.push({ variacion: 'Bolsillos', tipo: 'Sí', obs: variante.obs_bolsillos });
                            }
                            
                            // Broche/Botón: mostrar en la tabla de variaciones si existe información
                            if (variante.aplica_broche !== null && variante.aplica_broche) {
                                let tipo = variante.tipo_broche_nombre || variante.tipo_broche || 'Sí';
                                let obs = variante.obs_broche || '';
                                filas.push({ variacion: 'Broche', tipo: tipo, obs: obs });
                            } else if (variante.obs_broche) {
                                // Si hay observación de broche pero aplica_broche es false, aún mostrar la fila
                                let tipo = variante.tipo_broche_nombre || variante.tipo_broche || 'Sí';
                                filas.push({ variacion: 'Broche', tipo: tipo, obs: variante.obs_broche });
                            }
                            
                            // NO mostrar Descripción Adicional - se elimina del modal
                        }
                        
                        // Renderizar filas
                        filas.forEach((fila, idx) => {
                            htmlPrendas += `
                                <tr style="border-bottom: 1px solid #e2e8f0; ${idx % 2 === 0 ? 'background: #f9fafb;' : ''}">
                                    <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; font-weight: 600; color: #0f172a; font-size: 0.85rem;">${fila.variacion}</td>
                                    <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; color: #0ea5e9; font-weight: 500; font-size: 0.85rem;">${fila.tipo}</td>
                                    <td style="padding: 0.75rem; color: #64748b; font-size: 0.85rem;">${fila.obs}</td>
                                </tr>
                            `;
                        });

                        htmlPrendas += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    // ===== SECCIÓN DE REFLECTIVO (si existe) =====
                    if (prenda.reflectivo) {
                        const reflectivo = prenda.reflectivo;
                        
                        // TABLA DE VARIACIONES DE REFLECTIVO
                        if (reflectivo.variaciones && Object.keys(reflectivo.variaciones).length > 0) {
                            htmlPrendas += `
                                <div style="margin-top: 1.5rem;">
                                    <h6 style="color: #ef4444; font-weight: 600; margin: 0 0 0.75rem 0; font-size: 0.95rem;">VARIACIONES REFLECTIVO</h6>
                                    <table style="border-collapse: collapse; table-layout: auto; width: 100%; border: 1px solid #fecaca; border-radius: 4px; overflow: hidden;">
                                        <thead>
                                            <tr style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white;">
                                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); font-size: 0.85rem;">Propiedad</th>
                                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;

                            for (const [key, value] of Object.entries(reflectivo.variaciones)) {
                                let displayValue = '-';
                                if (typeof value === 'string') {
                                    displayValue = value;
                                } else if (typeof value === 'object' && value !== null) {
                                    displayValue = Object.values(value).filter(v => v).join(', ');
                                }

                                htmlPrendas += `
                                    <tr style="border-bottom: 1px solid #fecaca;">
                                        <td style="padding: 0.75rem; border-right: 1px solid #fecaca; font-weight: 600; color: #7f1d1d; font-size: 0.85rem;">${key}</td>
                                        <td style="padding: 0.75rem; color: #991b1b; font-weight: 500; font-size: 0.85rem;">${displayValue}</td>
                                    </tr>
                                `;
                            }

                            htmlPrendas += `
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        }
                    }

                    // ===== IMÁGENES LADO A LADO: LOGO | PRENDA | REFLECTIVO =====
                    const imagenesParaMostrar = [];
                    
                    // Recolectar imágenes de logo para esta prenda
                    // Usar un Set para deduplicar URLs de logo
                    const urlsLogoAgregadas = new Set();
                    
                    if (data.logo_cotizacion && data.logo_cotizacion.tecnicas_prendas) {
                        data.logo_cotizacion.tecnicas_prendas.forEach(tp => {
                            if (tp.prenda_id === prenda.id && tp.fotos && tp.fotos.length > 0) {
                                tp.fotos.forEach((foto, idx) => {
                                    if (foto.url && !urlsLogoAgregadas.has(foto.url)) {
                                        urlsLogoAgregadas.add(foto.url);
                                        imagenesParaMostrar.push({
                                            grupo: 'Imagen - Logo',
                                            url: foto.url,
                                            titulo: 'Imagen - Logo',
                                            color: '#1e5ba8'
                                        });
                                    }
                                });
                            }
                        });
                    }
                    
                    // Recolectar imágenes de tela para esta prenda
                    if (prenda.tela_fotos && prenda.tela_fotos.length > 0) {
                        prenda.tela_fotos.forEach((foto, idx) => {
                            if (foto) {
                                imagenesParaMostrar.push({
                                    grupo: 'Tela',
                                    url: foto,
                                    titulo: `Tela ${idx + 1}`,
                                    color: '#1e5ba8'
                                });
                            }
                        });
                    }
                    
                    // Recolectar imagen de tela de logo_cotizacion.telas_prendas
                    if (imgTela) {
                        imagenesParaMostrar.push({
                            grupo: 'Tela',
                            url: imgTela,
                            titulo: 'Tela Logo',
                            color: '#1e5ba8'
                        });
                    }
                    
                    // Recolectar imágenes de prenda
                    if (prenda.fotos && prenda.fotos.length > 0) {
                        prenda.fotos.forEach((foto, idx) => {
                            imagenesParaMostrar.push({
                                grupo: 'Prenda',
                                url: foto,
                                titulo: `${prenda.nombre_prenda || 'Prenda'} ${idx + 1}`,
                                color: '#1e5ba8'
                            });
                        });
                    }
                    
                    // Recolectar imágenes de reflectivo
                    if (prenda.reflectivo && prenda.reflectivo.fotos && prenda.reflectivo.fotos.length > 0) {
                        prenda.reflectivo.fotos.forEach((foto, idx) => {
                            if (foto.url) {
                                imagenesParaMostrar.push({
                                    grupo: 'Reflectivo',
                                    url: foto.url,
                                    titulo: `Reflectivo ${idx + 1}`,
                                    color: '#1e5ba8'
                                });
                            }
                        });
                    }
                    
                    // Recolectar imágenes de tela de reflectivo (prenda_cot_reflectivo)
                    if (data.cotizacion && data.cotizacion.tipo_cotizacion_id === 4 && prenda.prenda_cot_reflectivo && prenda.prenda_cot_reflectivo.color_tela_ref) {
                        const colorTelaRefArray = Array.isArray(prenda.prenda_cot_reflectivo.color_tela_ref) ? prenda.prenda_cot_reflectivo.color_tela_ref : [prenda.prenda_cot_reflectivo.color_tela_ref];
                        colorTelaRefArray.forEach((item, idx) => {
                            if (item.fotos && Array.isArray(item.fotos) && item.fotos.length > 0) {
                                item.fotos.forEach((foto, fotoIdx) => {
                                    let urlFoto = foto;
                                    if (!urlFoto.startsWith('http')) {
                                        urlFoto = '/storage/' + urlFoto.replace('storage/app/public/', '');
                                    }
                                    imagenesParaMostrar.push({
                                        grupo: 'Tela Reflectivo',
                                        url: urlFoto,
                                        titulo: `Tela Reflectivo ${fotoIdx + 1}`,
                                        color: '#1e5ba8'
                                    });
                                });
                            }
                        });
                    }
                    
                    // Mostrar imágenes lado a lado si existen
                    if (imagenesParaMostrar.length > 0) {
                        htmlPrendas += `
                            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; justify-content: flex-start;">
                        `;
                        
                        imagenesParaMostrar.forEach((img, idx) => {
                            htmlPrendas += `
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <img src="${img.url}" 
                                         alt="${img.titulo}"
                                         data-gallery="galeria-${prenda.id}"
                                         data-index="${idx}"
                                         style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid ${img.color}; cursor: pointer; transition: all 0.3s;"
                                         onclick="abrirImagenGrande('${img.url}', 'galeria-${prenda.id}', ${idx})"
                                         onmouseover="this.style.boxShadow='0 4px 12px rgba(30, 91, 168, 0.4)'; this.style.transform='scale(1.05)';"
                                         onmouseout="this.style.boxShadow='none'; this.style.transform='scale(1)';"/>
                                    <div style="margin-top: 0.5rem; background: linear-gradient(to right, ${img.color}, ${img.color}); padding: 0.5rem 0.75rem; border-radius: 4px; color: white; text-align: center; font-weight: 600; font-size: 0.7rem; white-space: nowrap;">
                                        ${img.grupo}
                                    </div>
                                </div>
                            `;
                        });
                        
                        htmlPrendas += `
                            </div>
                        `;
                    }

                    htmlPrendas += `</div>`;
                });
            } else {
                htmlPrendas += '<p style="color: #999; text-align: center; padding: 2rem;">No hay prendas para mostrar</p>';
            }

            htmlPrendas += '</div>';

            // HELPER FUNCTION: Verificar si hay especificaciones reales
            const verificarEspecificaciones = (especificacionesObj) => {
                if (!especificacionesObj || typeof especificacionesObj !== 'object') {
                    return false;
                }
                const keys = Object.keys(especificacionesObj);
                return keys.length > 0 && keys.some(key => {
                    const valor = especificacionesObj[key];
                    return valor && (Array.isArray(valor) ? valor.length > 0 : true);
                });
            };

            // HELPER FUNCTION: Parsear especificaciones
            const parseEspecificaciones = (especificacionesRaw) => {
                if (!especificacionesRaw) return null;
                
                if (typeof especificacionesRaw === 'string') {
                    try {
                        return JSON.parse(especificacionesRaw);
                    } catch (e) {
                        console.log('Error al parsear especificaciones:', e);
                        return null;
                    }
                }
                
                if (typeof especificacionesRaw === 'object') {
                    return especificacionesRaw;
                }
                
                return null;
            };

            // Parsear especificaciones una sola vez
            const especificacionesObj = parseEspecificaciones(data.cotizacion?.especificaciones);
            const tieneEspecificacionesReales = verificarEspecificaciones(especificacionesObj);

            console.log('Especificaciones parseadas:', especificacionesObj);
            console.log('Tiene especificaciones reales:', tieneEspecificacionesReales);
            console.log('tiene_prendas:', data.tiene_prendas);
            console.log('tiene_logo:', data.tiene_logo);

            // SECCIÓN ESPECIFICACIONES GENERALES
            if (tieneEspecificacionesReales) {
                const especificacionesMap = {
                    'disponibilidad': 'DISPONIBILIDAD',
                    'forma_pago': 'FORMA DE PAGO',
                    'regimen': 'RÉGIMEN',
                    'se_ha_vendido': 'SE HA VENDIDO',
                    'ultima_venta': 'ÚLTIMA VENTA',
                    'flete': 'FLETE DE ENVÍO'
                };

                htmlPrendas += `
                    <div style="margin-top: 2rem;">
                        <h3 style="margin: 0 0 1rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">Especificaciones Generales</h3>
                        <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                            <thead>
                                <tr style="background: #f5f5f5; border-bottom: 2px solid #1e5ba8;">
                                    <th style="padding: 0.75rem 1rem; text-align: left; color: #1e5ba8; font-weight: 700; font-size: 0.9rem;">Especificación</th>
                                    <th style="padding: 0.75rem 1rem; text-align: left; color: #1e5ba8; font-weight: 700; font-size: 0.9rem;">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                for (const [clave, nombreEspec] of Object.entries(especificacionesMap)) {
                    const valor = especificacionesObj[clave];
                    let valorTexto = '-';

                    if (valor) {
                        if (Array.isArray(valor) && valor.length > 0) {
                            // Es un array con objetos {valor, observacion}
                            valorTexto = valor
                                .map(v => {
                                    let texto = v.valor || '';
                                    if (v.observacion && v.observacion.trim()) {
                                        texto += ` (${v.observacion})`;
                                    }
                                    return texto;
                                })
                                .filter(t => t) // Filtrar vacíos
                                .join(', ');
                            
                            // Si resultó vacío, poner '-'
                            if (!valorTexto) {
                                valorTexto = '-';
                            }
                        } else if (typeof valor === 'string') {
                            valorTexto = valor;
                        } else if (typeof valor === 'object') {
                            // Por si acaso es un objeto en lugar de array
                            valorTexto = valor.valor || String(valor);
                        }
                    }

                    htmlPrendas += `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 0.75rem 1rem; color: #333; font-weight: 600; font-size: 0.85rem;">${nombreEspec}</td>
                                    <td style="padding: 0.75rem 1rem; color: #666; font-size: 0.85rem;">${valorTexto}</td>
                                </tr>
                    `;
                }

                htmlPrendas += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
                
            // SECCIÓN DE OBSERVACIONES GENERALES (para cotización logo)
            if (data.logo_cotizacion && data.logo_cotizacion.observaciones_generales) {
                let observacionesArray = [];
                
                try {
                    if (typeof data.logo_cotizacion.observaciones_generales === 'string') {
                        observacionesArray = JSON.parse(data.logo_cotizacion.observaciones_generales);
                    } else if (Array.isArray(data.logo_cotizacion.observaciones_generales)) {
                        observacionesArray = data.logo_cotizacion.observaciones_generales;
                    }
                } catch (e) {
                    console.log('Error al parsear observaciones generales:', e);
                }

                if (observacionesArray && observacionesArray.length > 0) {
                    htmlPrendas += `
                        <div style="margin-top: 2rem; padding: 1.5rem; background: #f0f7ff; border-left: 5px solid #0ea5e9; border-radius: 4px;">
                            <h6 style="color: #1e5ba8; font-weight: 700; margin: 0 0 1rem 0; font-size: 1rem; text-transform: uppercase;">OBSERVACIONES GENERALES</h6>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    `;

                    observacionesArray.forEach(obs => {
                        if (obs && obs.texto) {
                            let checkboxHtml = '';
                            
                            if (obs.tipo === 'checkbox') {
                                const isChecked = obs.valor === true || obs.valor === 'true' || obs.valor === 1;
                                checkboxHtml = `<input type="checkbox" ${isChecked ? 'checked' : ''} disabled style="margin-right: 0.75rem; cursor: not-allowed; width: 18px; height: 18px; accent-color: #0ea5e9;" />`;
                            }
                            
                            htmlPrendas += `
                                <div style="display: flex; align-items: center; padding: 0.75rem; background: white; border-radius: 4px; border-left: 3px solid #0ea5e9;">
                                    ${checkboxHtml}
                                    <span style="color: #0f172a; font-weight: 500; font-size: 0.95rem;">${obs.texto}</span>
                                </div>
                            `;
                        }
                    });

                    htmlPrendas += `
                            </div>
                        </div>
                    `;
                }
            }

            // Construir contenido de logo
            let htmlLogo = '';
            if (data.logo_cotizacion) {
                const logo = data.logo_cotizacion;
                // Normalizar arrays que pueden venir como string o null
                const parseArray = (value) => {
                    if (!value) return [];
                    if (Array.isArray(value)) return value;
                    try {
                        const parsed = JSON.parse(value);
                        return Array.isArray(parsed) ? parsed : [];
                    } catch (e) {
                        return [];
                    }
                };

                const tecnicas = parseArray(logo.tecnicas);
                const seccionesLogo = parseArray(logo.secciones || logo.ubicaciones);
                
                htmlLogo += '<div class="logo-container" style="display: flex; flex-direction: column; gap: 1.5rem;">';
                
                // Descripción del logo
                if (logo.descripcion) {
                    htmlLogo += `
                        <div style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">
                                Descripción
                            </h3>
                            <p style="margin: 0; color: #333; font-size: 0.9rem; line-height: 1.6;">
                                ${logo.descripcion}
                            </p>
                        </div>
                    `;
                }
                
                // Técnicas utilizadas
                if (tecnicas.length > 0) {
                    const renderTecnica = (tecnica) => {
                        if (typeof tecnica === 'string') return tecnica;
                        if (typeof tecnica === 'object' && tecnica !== null) {
                            return tecnica.valor || tecnica.nombre || tecnica.tecnica || tecnica.tipo || Object.values(tecnica).join(' ');
                        }
                        return String(tecnica);
                    };

                    htmlLogo += `
                        <div style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 0.95rem; font-weight: 700; text-transform: uppercase;">
                                Técnicas
                            </h3>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                ${tecnicas.map(tecnica => `<span style="background: #1e5ba8; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">${renderTecnica(tecnica)}</span>`).join('')}
                            </div>
                        </div>
                    `;
                }
                
                // Ubicaciones/Secciones
                if (seccionesLogo.length > 0) {
                    const renderOpcion = (opcion) => {
                        if (typeof opcion === 'string') return opcion;
                        if (typeof opcion === 'object' && opcion !== null) {
                            return opcion.nombre || opcion.valor || opcion.opcion || opcion.ubicacion || Object.values(opcion).join(' ');
                        }
                        return String(opcion);
                    };
                    const extraerTallas = (seccion) => {
                        if (!seccion) return [];
                        if (Array.isArray(seccion.tallas)) return seccion.tallas;
                        if (typeof seccion.tallas === 'string' && seccion.tallas.trim() !== '') {
                            // Intentar parsear JSON; si falla, usar split por comas
                            try {
                                const parsed = JSON.parse(seccion.tallas);
                                if (Array.isArray(parsed)) return parsed;
                            } catch (e) {
                                return seccion.tallas.split(',').map(t => t.trim()).filter(Boolean);
                            }
                        }
                        if (typeof seccion.tallas === 'object' && seccion.tallas !== null) return [seccion.tallas];
                        if (seccion.talla) return [seccion.talla];
                        if (seccion.tallas_texto) return [seccion.tallas_texto];
                        return [];
                    };

                    htmlLogo += `
                        <div style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 1rem 0; color: #1e5ba8; font-size: 0.95rem; font-weight: 700; text-transform: uppercase;">
                                Secciones Prenda
                            </h3>
                    `;
                    
                    seccionesLogo.forEach((seccion, idx) => {
                        htmlLogo += `
                            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #ddd;">
                                <p style="margin: 0 0 0.5rem 0; color: #333; font-weight: 700; font-size: 0.9rem;">
                                     ${seccion.ubicacion || seccion.seccion || 'Sin ubicación'}
                                </p>
                        `;
                        
                        if (seccion.opciones && Array.isArray(seccion.opciones) && seccion.opciones.length > 0) {
                            htmlLogo += `
                                <p style="margin: 0 0 0.25rem 0; color: #666; font-size: 0.85rem;">
                                    <strong>UBICACIONES:</strong> ${seccion.opciones.map(renderOpcion).join(', ')}
                                </p>
                            `;
                        }
                        
                        const tallasArray = extraerTallas(seccion);
                        if (tallasArray.length > 0) {
                            const tallasStr = tallasArray.map(t => {
                                if (typeof t === 'string') return t;
                                if (typeof t === 'object' && t !== null) return t.talla || t.valor || t.nombre || '';
                                return String(t);
                            }).filter(Boolean).join(', ');
                            htmlLogo += `
                                <p style="margin: 0 0 0.25rem 0; color: #666; font-size: 0.85rem;">
                                    <strong>Tallas:</strong> ${tallasStr}
                                </p>
                            `;
                        }
                        
                        if (seccion.observaciones) {
                            htmlLogo += `
                                <p style="margin: 0; color: #666; font-size: 0.85rem;">
                                    <strong>Observaciones:</strong> ${seccion.observaciones}
                                </p>
                            `;
                        }
                        
                        htmlLogo += `</div>`;
                    });
                    
                    htmlLogo += `</div>`;
                }
                
                // Fotos del logo
                if (logo.fotos && Array.isArray(logo.fotos) && logo.fotos.length > 0) {
                    const galleryIdLogo = `logo-fotos-${logo.id || 'cotizacion'}`;
                    htmlLogo += `
                        <div style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 1rem 0; color: #1e5ba8; font-size: 0.95rem; font-weight: 700; text-transform: uppercase;">
                                Imágenes del Logo
                            </h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem;">
                    `;
                    
                    logo.fotos.forEach((foto, idx) => {
                        htmlLogo += `
                            <div style="position: relative;">
                                <img src="${foto.url}" 
                                     data-gallery="${galleryIdLogo}"
                                     data-index="${idx}"
                                     alt="Logo" 
                                     style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;" 
                                     onclick="abrirImagenGrande('${foto.url}', '${galleryIdLogo}', ${idx})">
                                <span style="position: absolute; top: 2px; right: 2px; background: #1e5ba8; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;">${foto.orden}</span>
                            </div>
                        `;
                    });
                    
                    htmlLogo += `
                            </div>
                        </div>
                    `;
                }
                
                htmlLogo += '</div>';
            } else {
                htmlLogo += '<p style="color: #999; text-align: center; padding: 2rem;">No hay información de logo para mostrar</p>';
            }

            // Insertar contenido en el modal sin tabs
            // El logo ahora se renderiza directamente dentro de cada prenda
            if (data.tiene_prendas) {
                html += htmlPrendas;
            }
            
            document.getElementById('modalBody').innerHTML = html;

            document.getElementById('cotizacionModal').style.display = 'flex';


        })
        .catch(error => {

            alert('Error al cargar la cotización: ' + error.message);
        });
}

/**
 * Cierra el modal de cotización
 */
function closeCotizacionModal() {
    document.getElementById('cotizacionModal').style.display = 'none';
}

/**
 * Cierra el modal de cotización (alias)
 */
function cerrarModalCotizacion() {
    document.getElementById('cotizacionModal').style.display = 'none';
}

/**
 * Cierra el modal al hacer clic fuera del contenido
 */
document.addEventListener('click', function (event) {
    const modal = document.getElementById('cotizacionModal');
    if (event.target === modal) {
        closeCotizacionModal();
    }
});

/**
 * Cierra el modal al presionar ESC
 */
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('cotizacionModal');
        if (modal && modal.style.display === 'flex') {
            closeCotizacionModal();
        }
    }
});

/**
 * Elimina una cotización con confirmación
 * @param {number} cotizacionId - ID de la cotización
 * @param {string} cliente - Nombre del cliente
 */
function eliminarCotizacion(cotizacionId, cliente) {
    // Mostrar confirmación con SweetAlert
    Swal.fire({
        title: '¿Eliminar cotización completamente?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    ¿Estás seguro de que deseas eliminar la cotización del cliente <strong>${cliente}</strong>?
                </p>
                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #92400e; font-weight: 600;">
                         Se eliminarán PERMANENTEMENTE:
                    </p>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem; font-size: 0.85rem; color: #92400e;">
                        <li><strong>Base de datos:</strong>
                            <ul style="margin: 0.25rem 0 0 0; padding-left: 1.25rem;">
                                <li>Registro de cotización</li>
                                <li>Todas las prendas relacionadas</li>
                                <li>Información de LOGO</li>
                                <li>Pedidos de producción asociados</li>
                                <li>Historial de cambios</li>
                            </ul>
                        </li>
                        <li style="margin-top: 0.5rem;"><strong>Servidor:</strong>
                            <ul style="margin: 0.25rem 0 0 0; padding-left: 1.25rem;">
                                <li>Carpeta: <code style="background: #fff3cd; padding: 0.2rem 0.4rem; border-radius: 2px;">/storage/cotizaciones/${cotizacionId}</code></li>
                                <li>Todas las imágenes de prendas</li>
                                <li>Todas las imágenes de telas</li>
                                <li>Todas las imágenes de LOGO</li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #ef4444; font-weight: 600;">
                     Esta acción NO se puede deshacer. Se eliminarán todos los datos y archivos.
                </p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, eliminar TODO',
        cancelButtonText: 'Cancelar',
        width: '550px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                html: `
                    <div style="text-align: left; color: #666;">
                        <p style="margin: 0 0 0.75rem 0; font-weight: 600;">Por favor espera mientras se elimina:</p>
                        <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                            <li>Registros de la base de datos</li>
                            <li>Carpeta de imágenes del servidor</li>
                            <li>Todos los archivos relacionados</li>
                        </ul>
                    </div>
                `,
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Proceder con la eliminación
            fetch(`/contador/cotizacion/${cotizacionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '✓ Eliminado Completamente',
                            html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-weight: 600;"> Se eliminaron:</p>
                                <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                                    <li>Cotización de la base de datos</li>
                                    <li>Todas las prendas relacionadas</li>
                                    <li>Información de LOGO</li>
                                    <li>Pedidos de producción</li>
                                    <li>Historial de cambios</li>
                                    <li>Carpeta <code style="background: #f0f0f0; padding: 0.2rem 0.4rem; border-radius: 2px;">/storage/cotizaciones/${cotizacionId}</code></li>
                                    <li>Todas las imágenes almacenadas</li>
                                </ul>
                            </div>
                        `,
                            icon: 'success',
                            confirmButtonColor: '#1e5ba8'
                        }).then(() => {
                            // Recargar la página
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar la cotización',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {

                    Swal.fire({
                        title: 'Error',
                        text: 'Error al eliminar la cotización. Por favor intenta de nuevo.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

/**
 * Aprueba la cotización directamente desde la tabla (sin abrir modal)
 * @param {number} cotizacionId - ID de la cotización
 * @param {string} estadoActual - Estado actual de la cotización (opcional)
 */
function aprobarCotizacionEnLinea(cotizacionId, estadoActual = null) {
    // Determinar el mensaje y la ruta según el estado
    let mensaje = '¿Estás seguro de que deseas aprobar esta cotización?';
    let infoAdicional = 'La cotización será enviada al área de Aprobación de Cotizaciones';
    let ruta = `/cotizaciones/${cotizacionId}/aprobar-contador`;
    
    // Si el estado es APROBADA_POR_APROBADOR, usar la ruta para aprobar para pedido
    if (estadoActual === 'APROBADA_POR_APROBADOR') {
        infoAdicional = 'La cotización cambiará a estado APROBADO PARA PEDIDO';
        ruta = `/cotizaciones/${cotizacionId}/aprobar-para-pedido`;
    }
    
    // Mostrar confirmación
    Swal.fire({
        title: '¿Aprobar cotización?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    ${mensaje}
                </p>
                <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #1e40af; font-weight: 600;">
                         ${infoAdicional}
                    </p>
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar',
        width: '450px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Aprobando cotización...',
                html: 'Por favor espera mientras se procesa la aprobación',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar solicitud de aprobación
            fetch(ruta, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Encontrar todas las filas en la tabla de Pendientes
                        const filas = document.querySelectorAll('#pedidos-section tbody tr');

                        filas.forEach(fila => {
                            // Buscar si esta fila contiene el botón de aprobar para esta cotización
                            const boton = fila.querySelector(`button[onclick*="aprobarCotizacionEnLinea(${cotizacionId})"]`);

                            if (boton) {
                                // Animar la desaparición de la fila
                                fila.style.transition = 'all 0.3s ease-out';
                                fila.style.opacity = '0';
                                fila.style.transform = 'translateX(-20px)';

                                setTimeout(() => {
                                    fila.remove();

                                    // Verificar si la tabla está vacía
                                    const tbody = document.querySelector('#pedidos-section tbody');
                                    if (tbody && tbody.children.length === 0) {
                                        // Si está vacía, mostrar mensaje
                                        tbody.innerHTML = '<tr><td colspan="100%" style="text-align: center; padding: 2rem; color: #999;">No hay cotizaciones pendientes</td></tr>';
                                    }
                                }, 300);
                            }
                        });

                        Swal.fire({
                            title: '✓ Cotización Aprobada',
                            html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem;">
                                     La cotización ha sido aprobada correctamente.
                                </p>
                                <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                                    <p style="margin: 0; font-size: 0.85rem; color: #065f46; font-weight: 600;">
                                        📧 Se ha enviado notificación al área de Aprobación de Cotizaciones
                                    </p>
                                </div>
                                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #666;">
                                    <strong>Estado actual:</strong> Enviado a Aprobador
                                </p>
                            </div>
                        `,
                            icon: 'success',
                            confirmButtonColor: '#1e5ba8',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo aprobar la cotización',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {

                    Swal.fire({
                        title: 'Error',
                        text: error.message || 'Error al aprobar la cotización. Por favor intenta de nuevo.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

/**
 * Abre una imagen en grande en un modal
 * @param {string} imagenUrl - URL de la imagen
 */
if (typeof imagenGaleraActual === 'undefined') {
    var imagenGaleraActual = [];
    var imagenIndiceActualGaleria = 0;
    var imagenGaleriaIdActual = null;
}

function abrirImagenGrande(imagenUrl, galleryId = null, index = 0) {
    // Preparar galería si viene un grupo
    if (galleryId) {
        imagenGaleriaIdActual = galleryId;
        const imgs = document.querySelectorAll(`img[data-gallery="${galleryId}"]`);
        imagenGaleraActual = Array.from(imgs).map(img => img.getAttribute('src'));
        imagenIndiceActualGaleria = Number(index) || 0;
    } else {
        imagenGaleriaIdActual = null;
        imagenGaleraActual = [imagenUrl];
        imagenIndiceActualGaleria = 0;
    }

    // Crear modal dinámicamente si no existe
    let modalImagen = document.getElementById('modalImagenGrande');
    if (!modalImagen) {
        modalImagen = document.createElement('div');
        modalImagen.id = 'modalImagenGrande';
        modalImagen.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        `;
        modalImagen.innerHTML = `
            <div style="position: relative; width: 90vw; height: 90vh; max-width: 1200px; max-height: 800px; display: flex; align-items: center; justify-content: center;">
                <button id="cerrarImagenGrandeBtn" aria-label="Cerrar" style="position: absolute; top: -50px; right: 0; background: #fff; border: none; font-size: 1.4rem; cursor: pointer; color: #111; z-index: 10001; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
                    ✕
                </button>
                <button id="imagenAnteriorBtn" aria-label="Anterior" style="position: absolute; left: -60px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; width: 44px; height: 44px; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 1.3rem; cursor: pointer; box-shadow: 0 8px 20px rgba(0,0,0,0.25); color: #111;">◀</button>
                <img id="imagenGrandeContent" src="" alt="Imagen ampliada" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                <button id="imagenSiguienteBtn" aria-label="Siguiente" style="position: absolute; right: -60px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; width: 44px; height: 44px; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 1.3rem; cursor: pointer; box-shadow: 0 8px 20px rgba(0,0,0,0.25); color: #111;">▶</button>
            </div>
        `;
        document.body.appendChild(modalImagen);

        // Eventos de botones
        modalImagen.querySelector('#cerrarImagenGrandeBtn').addEventListener('click', cerrarImagenGrande);
        modalImagen.querySelector('#imagenAnteriorBtn').addEventListener('click', mostrarAnteriorImagen);
        modalImagen.querySelector('#imagenSiguienteBtn').addEventListener('click', mostrarSiguienteImagen);
    }

    actualizarImagenGrande();
    modalImagen.style.display = 'flex';
}

function actualizarImagenGrande() {
    const modalImagen = document.getElementById('modalImagenGrande');
    if (!modalImagen) return;

    const img = modalImagen.querySelector('#imagenGrandeContent');
    img.src = imagenGaleraActual[imagenIndiceActualGaleria] || '';

    const btnPrev = modalImagen.querySelector('#imagenAnteriorBtn');
    const btnNext = modalImagen.querySelector('#imagenSiguienteBtn');

    if (imagenGaleraActual.length > 1) {
        btnPrev.style.display = 'flex';
        btnNext.style.display = 'flex';
    } else {
        btnPrev.style.display = 'none';
        btnNext.style.display = 'none';
    }
}

function mostrarAnteriorImagen() {
    if (!imagenGaleraActual.length) return;
    imagenIndiceActualGaleria = (imagenIndiceActualGaleria - 1 + imagenGaleraActual.length) % imagenGaleraActual.length;
    actualizarImagenGrande();
}

function mostrarSiguienteImagen() {
    if (!imagenGaleraActual.length) return;
    imagenIndiceActualGaleria = (imagenIndiceActualGaleria + 1) % imagenGaleraActual.length;
    actualizarImagenGrande();
}

/**
 * Cierra el modal de imagen grande
 */
function cerrarImagenGrande() {
    const modal = document.getElementById('modalImagenGrande');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Función para aprobar cotización al aprobador (desde vista aprobadas)
function aprobarAlAprobador(cotizacionId) {
    // Mostrar confirmación
    Swal.fire({
        title: '¿Enviar al Asesor?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    Esta es la aprobación final del proceso. La cotización será enviada de vuelta al asesor para que pueda proceder con la venta.
                </p>
                <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #1e40af; font-weight: 600;">
                         Una vez aprobada, la cotización estará lista para presentarse al cliente
                    </p>
                </div>
                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #666;">
                    <strong>¿Estás seguro de que deseas proceder?</strong>
                </p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, Enviar al Asesor',
        cancelButtonText: 'Cancelar',
        width: '500px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Enviando al Asesor...',
                html: 'Por favor espera mientras se procesa',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar solicitud de aprobación al aprobador
            fetch(`/cotizaciones/${cotizacionId}/aprobar-aprobador`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Encontrar la fila en la tabla de Aprobadas
                        const filas = document.querySelectorAll('#aprobadas-section .table-row');

                        filas.forEach(fila => {
                            const rowId = fila.getAttribute('data-cotizacion-id');
                            if (rowId == cotizacionId) {
                                // Animar la desaparición de la fila
                                fila.style.transition = 'all 0.3s ease-out';
                                fila.style.opacity = '0';
                                fila.style.transform = 'translateX(-20px)';

                                setTimeout(() => {
                                    fila.remove();

                                    // Verificar si la tabla está vacía
                                    const tbody = document.querySelector('#aprobadas-section .table-body');
                                    if (tbody && tbody.children.length === 0) {
                                        // Si está vacía, mostrar mensaje
                                        tbody.innerHTML = '<div style="padding: 40px; text-align: center; color: #9ca3af;"><p>No hay cotizaciones aprobadas</p></div>';
                                    }
                                }, 300);
                            }
                        });

                        Swal.fire({
                            title: '✓ Aprobación Completada',
                            html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem;">
                                     La cotización ha sido aprobada exitosamente.
                                </p>
                                <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                                    <p style="margin: 0; font-size: 0.85rem; color: #065f46; font-weight: 600;">
                                        📧 Se ha notificado al asesor
                                    </p>
                                </div>
                                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #666;">
                                    <strong>Estado actual:</strong> Lista para hacer pedido
                                </p>
                            </div>
                        `,
                            icon: 'success',
                            confirmButtonColor: '#1e5ba8',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo enviar la cotización',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {

                    Swal.fire({
                        title: 'Error',
                        text: error.message || 'Error al procesar la solicitud',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

// Cerrar modal de imagen al hacer clic fuera
document.addEventListener('click', function (event) {
    const modal = document.getElementById('modalImagenGrande');
    if (modal && event.target === modal) {
        cerrarImagenGrande();
    }
});

// Cerrar modal de imagen al presionar ESC
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        cerrarImagenGrande();
    }
});
/**
 * Función para editar tallas con paréntesis
 */
function editarTallasConParentesis(element) {
    // Evitar editar si ya está en modo edición
    if (element.querySelector('input')) {
        return;
    }

    const tallasTexto = element.textContent.trim();
    const prendasId = element.getAttribute('data-prenda-id');
    const cotizacionId = element.getAttribute('data-cotizacion-id');

    // Extraer el texto dentro de los paréntesis si existe
    const matches = tallasTexto.match(/^(.*?)\s*\((.*?)\)$/);
    const tallasParte = matches ? matches[1].trim() : tallasTexto.replace(' ()', '').trim();
    const textoDentroParentesis = matches ? matches[2] : '';

    // Crear input editable
    const input = document.createElement('input');
    input.type = 'text';
    input.value = textoDentroParentesis;
    input.style.cssText = `
        width: 200px;
        padding: 0.5rem;
        border: 2px solid #dc2626;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 700;
        color: #dc2626;
    `;

    // Reemplazar el span con el input
    element.textContent = `${tallasParte} (`;
    element.appendChild(input);
    
    // Crear el cierre de paréntesis
    const closeSpan = document.createElement('span');
    closeSpan.textContent = ')';
    element.appendChild(closeSpan);

    // Focus en el input
    input.focus();
    input.select();

    // Guardar al presionar Enter
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            guardarTallasContosCot(prendasId, cotizacionId, input.value, tallasParte);
            
            // Restaurar el elemento
            element.textContent = `${tallasParte} (${input.value})`;
        } else if (e.key === 'Escape') {
            // Cancelar edición
            element.textContent = tallasTexto;
        }
    });

    // Cancelar si pierde el focus
    input.addEventListener('blur', function() {
        if (element.querySelector('input')) {
            element.textContent = tallasTexto;
        }
    });
}

/**
 * Guardar tallas costos en la base de datos
 */
function guardarTallasContosCot(prendasId, cotizacionId, descripcion, tallasParte) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        console.error('CSRF token no encontrado');
        alert('Error: Token de seguridad no encontrado');
        return;
    }

    // Mostrar que está guardando
    const tallaElement = document.getElementById(`tallas-texto-${prendasId}`);
    if (tallaElement) {
        const originalOpacity = tallaElement.style.opacity;
        tallaElement.style.opacity = '0.6';
    }

    fetch('/contador/tallas-costos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            cotizacion_id: parseInt(cotizacionId),
            prenda_cot_id: parseInt(prendasId),
            descripcion: descripcion
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('Tallas costos guardado exitosamente', data);
            
            // Actualizar el elemento en la UI
            if (tallaElement) {
                tallaElement.textContent = `${tallasParte} (${descripcion})`;
                tallaElement.style.opacity = '1';
                // Feedback visual
                tallaElement.style.backgroundColor = '#dcfce7';
                setTimeout(() => {
                    tallaElement.style.backgroundColor = 'transparent';
                }, 1500);
            }
            
            // Mostrar notificación de éxito
            if (window.mostrarNotificacion) {
                window.mostrarNotificacion('Tallas guardadas correctamente', 'success');
            }
        } else {
            console.error('Error al guardar:', data.message);
            if (tallaElement) {
                tallaElement.style.opacity = '1';
            }
            
            alert('Error al guardar: ' + data.message);
            if (window.mostrarNotificacion) {
                window.mostrarNotificacion('Error: ' + data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error en la solicitud:', error);
        if (tallaElement) {
            tallaElement.style.opacity = '1';
        }
        
        alert('Error al guardar tallas: ' + error.message);
        if (window.mostrarNotificacion) {
            window.mostrarNotificacion('Error al guardar: ' + error.message, 'error');
        }
    });
}