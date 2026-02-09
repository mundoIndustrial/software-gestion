// Variables globales para el visor de costos
let visorCostosActual = {
    cotizacionId: null,
    cliente: null,
    cotizacionData: null,
    prendas: [],
    prendaDetalles: {},
    indiceActual: 0
};

/**
 * Abre el modal visor de costos
 */
function abrirModalVisorCostos(cotizacionId, cliente) {
    visorCostosActual = { cotizacionId: cotizacionId, cliente: cliente, cotizacionData: null, prendas: [], prendaDetalles: {}, indiceActual: 0 };
    
    // Obtener datos de cotización Y costos en paralelo
    Promise.all([
        fetch(`/contador/cotizacion/${cotizacionId}`).then(response => response.json()),
        fetch(`/contador/cotizacion/${cotizacionId}/costos`).then(response => response.json())
    ])
    .then(([cotizacionData, costosData]) => {
        // Guardar cotizacionData para usar en mostrarPrendaVisor
        visorCostosActual.cotizacionData = cotizacionData;
        
        // Declarar prendaDetalles
        const prendaDetalles = {};
        
        if (cotizacionData.prendas_cotizaciones && Array.isArray(cotizacionData.prendas_cotizaciones)) {
            cotizacionData.prendas_cotizaciones.forEach((prenda, idx) => {
                // Construir descripción concatenada igual que en el modal de cotización
                let descripcionCompleta = prenda.descripcion_formateada || prenda.descripcion || '';
                
                // Si hay técnicas de logo para esta prenda, agregar ubicaciones
                const tecnicasPrendaArray = cotizacionData.logo_cotizacion && cotizacionData.logo_cotizacion.tecnicas_prendas 
                    ? cotizacionData.logo_cotizacion.tecnicas_prendas.filter(tp => tp.prenda_id === prenda.id)
                    : [];
                
                if (tecnicasPrendaArray && tecnicasPrendaArray.length > 0) {
                    // Consolidar ubicaciones por técnica
                    const ubicacionesPorTecnica = {};
                    tecnicasPrendaArray.forEach(tp => {
                        const tecnicaNombre = tp.tipo_logo_nombre || 'Logo';
                        if (tp.ubicaciones) {
                            let ubicacionesArray = Array.isArray(tp.ubicaciones) ? tp.ubicaciones : [String(tp.ubicaciones)];
                            // Filtrar vacíos y remover corchetes
                            ubicacionesArray = ubicacionesArray
                                .map(u => String(u).replace(/[\[\]]/g, '').trim())
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
                if (prenda.prenda_cot_reflectivo) {
                    const pcrRef = prenda.prenda_cot_reflectivo;
                    
                    // Agregar descripción del reflectivo
                    if (pcrRef.descripcion) {
                        if (descripcionCompleta) {
                            descripcionCompleta += ', ';
                        }
                        descripcionCompleta += pcrRef.descripcion;
                    }
                    
                    // Agregar ubicaciones del reflectivo con negrita
                    if (pcrRef.ubicaciones && Array.isArray(pcrRef.ubicaciones)) {
                        if (descripcionCompleta) {
                            descripcionCompleta += ', ';
                        }
                        const ubicacionesReflectivo = pcrRef.ubicaciones
                            .map(u => u.ubicacion ? '<strong>' + u.ubicacion + '</strong>' + (u.descripcion ? ': ' + u.descripcion : '') : '')
                            .filter(u => u)
                            .join(', ');
                        descripcionCompleta += ubicacionesReflectivo;
                    }
                }
                
                prendaDetalles[idx] = {
                    nombre_prenda: prenda.nombre_prenda || `Prenda ${idx + 1}`,
                    descripcion_formateada: descripcionCompleta,
                    fotos: prenda.fotos || [],
                    tela_fotos: prenda.tela_fotos || [],
                    reflectivo: prenda.reflectivo || null,
                    color: (prenda.variantes && prenda.variantes[0]) ? prenda.variantes[0].color : '',
                    tela: (prenda.telas && prenda.telas[0]) ? prenda.telas[0].nombre_tela : '',
                    tela_referencia: (prenda.telas && prenda.telas[0]) ? prenda.telas[0].referencia : '',
                    telas_info: (Array.isArray(prenda.telas) ? prenda.telas : []).map(t => ({
                        nombre_tela: t?.nombre_tela || '',
                        referencia: t?.referencia || ''
                    })).filter(t => (t.nombre_tela || '').trim() !== '' || (t.referencia || '').trim() !== ''),
                    manga_nombre: (prenda.variantes && prenda.variantes[0]) ? prenda.variantes[0].tipo_manga_nombre : ''
                };
            });
        }
        
        visorCostosActual.prendaDetalles = prendaDetalles;
        
        if (costosData.success && costosData.prendas.length > 0) {
            // Asignar nombres y detalles a las prendas de costos
            costosData.prendas.forEach((prenda, idx) => {
                if (!prenda.nombre_producto || prenda.nombre_producto === 'Prenda sin nombre') {
                    prenda.nombre_producto = prendaDetalles[idx]?.nombre_prenda || `Prenda ${idx + 1}`;
                }
            });
            
            visorCostosActual.prendas = costosData.prendas;

            document.getElementById('visorCostosModal').style.display = 'flex';
            
            // Resetear scroll al abrir
            setTimeout(() => {
                const contenido = document.getElementById('visorCostosContenido');
                if (contenido) {
                    contenido.scrollTop = 0;
                }
            }, 0);
            
            mostrarPrendaVisor(0);
        } else {
            // Mostrar modal de "sin costos" en lugar de alerta
            mostrarModalSinCostos(cliente);
        }
    })
    .catch(error => {
        mostrarModalErrorCostos(error.message);
    });
}

/**
 * Muestra un modal cuando no hay costos calculados
 */
function mostrarModalSinCostos(cliente) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5000;
    `;
    
    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 8px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
        ">
            <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
            <h2 style="color: #1e5ba8; margin: 0 0 1rem 0; font-size: 1.5rem;">Sin Costos Calculados</h2>
            <p style="color: #666; margin: 0 0 1.5rem 0; line-height: 1.6;">
                No hay costos calculados para la cotización del cliente <strong>${cliente}</strong>.
            </p>
            <p style="color: #999; margin: 0 0 2rem 0; font-size: 0.9rem;">
                Por favor, calcula los costos de las prendas primero usando la opción "Calcular Costos".
            </p>
            <button onclick="this.closest('div').parentElement.remove()" style="
                background: #1e5ba8;
                color: white;
                border: none;
                padding: 0.75rem 2rem;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 600;
                font-size: 1rem;
            ">
                Entendido
            </button>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/**
 * Muestra un modal cuando hay error al cargar costos
 */
function mostrarModalErrorCostos(mensaje) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5000;
    `;
    
    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 8px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
        ">
            <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
            <h2 style="color: #ef4444; margin: 0 0 1rem 0; font-size: 1.5rem;">Error al Cargar Costos</h2>
            <p style="color: #666; margin: 0 0 1.5rem 0; line-height: 1.6;">
                Ocurrió un error al intentar cargar los costos de la cotización.
            </p>
            <p style="color: #999; margin: 0 0 2rem 0; font-size: 0.9rem;">
                ${mensaje || 'Por favor, intenta de nuevo más tarde.'}
            </p>
            <button onclick="this.closest('div').parentElement.remove()" style="
                background: #ef4444;
                color: white;
                border: none;
                padding: 0.75rem 2rem;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 600;
                font-size: 1rem;
            ">
                Cerrar
            </button>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/**
 * Cierra el modal visor de costos
 */
function cerrarVisorCostos() {
    document.getElementById('visorCostosModal').style.display = 'none';
    
    // Limpiar tabs
    const tabsContainer = document.getElementById('visorCostosTabsContainer');
    if (tabsContainer) {
        tabsContainer.innerHTML = '';
    }
    
    visorCostosActual = {
        cotizacionId: null,
        cliente: null,
        prendas: [],
        indiceActual: 0
    };
}

/**
 * Navega a la prenda anterior
 */
function visorCostosAnterior() {
    if (visorCostosActual.indiceActual > 0) {
        visorCostosActual.indiceActual--;
        mostrarPrendaVisor(visorCostosActual.indiceActual);
    }
}

/**
 * Navega a la próxima prenda
 */
function visorCostosProximo() {
    if (visorCostosActual.indiceActual < visorCostosActual.prendas.length - 1) {
        visorCostosActual.indiceActual++;
        mostrarPrendaVisor(visorCostosActual.indiceActual);
    }
}

/**
 * Genera los tabs de prendas dinámicamente
 */
function generarTabsPrendas() {
    const tabsContainer = document.getElementById('visorCostosTabsContainer');
    if (!tabsContainer) {

        return;
    }
    
    // Limpiar tabs existentes antes de generar nuevos
    tabsContainer.innerHTML = '';
    

    
    const esPrendaLogoPorId = (prendaId) => {
        const tecnicas = visorCostosActual?.cotizacionData?.logo_cotizacion?.tecnicas_prendas;
        if (!tecnicas || !Array.isArray(tecnicas) || !prendaId) return false;
        return tecnicas.some(tp => tp.prenda_id === prendaId);
    };

    visorCostosActual.prendas.forEach((prenda, idx) => {
        const tab = document.createElement('button');
        tab.setAttribute('data-prenda-tab', idx);
        
        // Determinar el nombre de la prenda
        let nombrePrenda = prenda.nombre_producto || prenda.nombre || `Prenda ${idx + 1}`;
        
        // Si el nombre está vacío o es muy corto, usar un nombre genérico
        if (!nombrePrenda || nombrePrenda.trim() === '') {
            nombrePrenda = `Prenda ${idx + 1}`;
        }
        
        // Aplicar sufijo "- logo" si la prenda corresponde a técnicas de logo
        const esLogo = esPrendaLogoPorId(prenda.id);
        const nombreBase = (nombrePrenda || '').trim();
        tab.textContent = esLogo && !/\s-\slogo\s*$/i.test(nombreBase) ? `${nombreBase} - logo` : nombreBase;
        tab.style.cssText = `
            padding: 12px 24px;
            background: ${idx === 0 ? 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)' : '#6b7280'};
            color: white;
            border: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s;
            white-space: nowrap;
            text-transform: lowercase;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;
        
        tab.onclick = () => mostrarPrendaVisor(idx);
        
        tab.onmouseover = function() {
            if (idx !== visorCostosActual.indiceActual) {
                this.style.background = '#4b5563';
            }
        };
        
        tab.onmouseout = function() {
            if (idx !== visorCostosActual.indiceActual) {
                this.style.background = '#374151';
            }
        };
        
        tabsContainer.appendChild(tab);
    });
}

/**
 * Muestra la prenda en el visor
 */
function mostrarPrendaVisor(indice) {
    const prenda = visorCostosActual.prendas[indice];
    const detalles = visorCostosActual.prendaDetalles[indice] || {};
    
    if (!prenda) return;
    
    // Actualizar índice actual
    visorCostosActual.indiceActual = indice;
    
    // Generar tabs de prendas si no existen
    generarTabsPrendas();
    
    // Actualizar estilo del tab activo
    const tabs = document.querySelectorAll('[data-prenda-tab]');
    tabs.forEach((tab, idx) => {
        if (idx === indice) {
            tab.style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
            tab.style.color = 'white';
        } else {
            tab.style.background = '#374151';
            tab.style.color = '#d1d5db';
        }
    });
    
    // Construir detalles en una línea compacta
    let detallesLinea = [];
    if (detalles.color) detallesLinea.push(`<strong>Color:</strong> ${detalles.color}`);
    if (detalles.telas_info && Array.isArray(detalles.telas_info) && detalles.telas_info.length > 0) {
        const telasHtml = detalles.telas_info.map(t => {
            const nombre = (t.nombre_tela || '').trim();
            const ref = (t.referencia || '').trim();
            const label = ref ? `${nombre} (Ref: ${ref})` : nombre;
            return `<span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; background: #e5e7eb; color: #111827; font-weight: 600; font-size: 0.78rem; white-space: nowrap;">${label}</span>`;
        }).join(' ');
        detallesLinea.push(`<strong>Telas:</strong> <span style="display: inline-flex; gap: 6px; flex-wrap: wrap; vertical-align: middle;">${telasHtml}</span>`);
    } else if (detalles.tela) {
        const tela = detalles.tela_referencia ? `${detalles.tela} (Ref: ${detalles.tela_referencia})` : detalles.tela;
        detallesLinea.push(`<strong>Tela:</strong> ${tela}`);
    }
    if (detalles.manga_nombre) detallesLinea.push(`<strong>Manga:</strong> ${detalles.manga_nombre}`);
    
    // Recopilar todas las imágenes (logo, tela, prenda, reflectivo) como lo hace el modal de cotización
    const imagenesParaMostrar = [];
    
    // DETECTAR SI ES COTIZACIÓN COMBINADA (tiene logo_cotizacion)
    const esCotizacionCombinada = visorCostosActual.cotizacionData && visorCostosActual.cotizacionData.logo_cotizacion;
    const tecnicas = visorCostosActual?.cotizacionData?.logo_cotizacion?.tecnicas_prendas;
    const esPrendaLogo = Array.isArray(tecnicas) ? tecnicas.some(tp => tp.prenda_id === prenda.id) : false;

    // En cotizaciones combinadas:
    // - Si la prenda es de logo: mostrar imágenes de logo
    // - Si no: mostrar imágenes de prenda
    if (esCotizacionCombinada) {
        if (esPrendaLogo) {
            const urlsLogoAgregadas = new Set();
            if (Array.isArray(tecnicas)) {
                tecnicas.forEach(tp => {
                    if (tp.prenda_id === prenda.id && tp.fotos && tp.fotos.length > 0) {
                        tp.fotos.forEach((foto) => {
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
        } else {
            if (detalles.fotos && detalles.fotos.length > 0) {
                detalles.fotos.forEach((foto, idx) => {
                    imagenesParaMostrar.push({
                        grupo: 'Prenda',
                        url: foto,
                        titulo: `${detalles.nombre_prenda || 'Prenda'} ${idx + 1}`,
                        color: '#1e5ba8'
                    });
                });
            }
        }
    } else {
        // En cotizaciones normales, mostrar todas las imágenes
        
        // Recolectar imágenes de logo para esta prenda
        // Usar un Set para deduplicar URLs de logo
        const urlsLogoAgregadas = new Set();
        
        if (visorCostosActual.cotizacionData && visorCostosActual.cotizacionData.logo_cotizacion && visorCostosActual.cotizacionData.logo_cotizacion.tecnicas_prendas) {
            visorCostosActual.cotizacionData.logo_cotizacion.tecnicas_prendas.forEach(tp => {
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
        if (detalles.tela_fotos && detalles.tela_fotos.length > 0) {
            detalles.tela_fotos.forEach((foto, idx) => {
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
        
        // Recolectar imágenes de prenda
        if (detalles.fotos && detalles.fotos.length > 0) {
            detalles.fotos.forEach((foto, idx) => {
                imagenesParaMostrar.push({
                    grupo: 'Prenda',
                    url: foto,
                    titulo: `${detalles.nombre_prenda || 'Prenda'} ${idx + 1}`,
                    color: '#1e5ba8'
                });
            });
        }
        
        // Recolectar imágenes de reflectivo
        if (detalles.reflectivo && detalles.reflectivo.fotos && detalles.reflectivo.fotos.length > 0) {
            detalles.reflectivo.fotos.forEach((foto, idx) => {
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
    }
    
    // Calcular cantidad de filas: items + 1 fila de total
    const cantidadItems = prenda.items ? prenda.items.length : 0;
    const cantidadFilas = Math.max(cantidadItems, 1); // Mínimo 1 fila
    
    // Construir HTML del contenido - Diseño compacto con descripción concatenada e imágenes
    let html = `
        <div style="padding: 0; margin-top: -1.5rem; transform: scale(0.8); transform-origin: top left; width: 125%;">
            <!-- Sección Detalles Compacta -->
            <div style="margin-bottom: 1.5rem; margin-top: 0.5rem;">
                <!-- Línea de atributos -->
                <div style="color: #333; font-size: 0.9rem; line-height: 1.6; margin-bottom: 0.75rem; height: 1.5rem; position: relative; top: 22px;">
                    ${detallesLinea.join(' | ')}
                </div>
                
                <!-- Descripción Concatenada según tipo de cotización -->
                ${detalles.descripcion_formateada ? `<div id="descripcionPrenda" style="color: #333; font-size: 0.9rem; line-height: 1.8; white-space: pre-wrap; word-wrap: break-word; margin-top: 22px;">${detalles.descripcion_formateada.replace(/\n/g, '<br>')}</div>` : '<div id="descripcionPrenda" style="color: #999; font-size: 0.9rem; margin-top: 22px;">Sin descripción</div>'}
            </div>
            
            <!-- Contenedor de Tabla e Imágenes -->
            <div style="display: flex; gap: 2rem; margin-bottom: 1rem;">
                <!-- Tabla de Costos con filas dinámicas -->
                <div style="flex: 0 0 480px; overflow-x: auto;">
                    <div style="background: white; border-radius: 8px; padding: 0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <thead>
                                <tr style="background: #1e40af; border-bottom: 2px solid #1e40af;">
                                    <th style="padding: 6px 8px; text-align: left; font-weight: 700; color: white; border-right: 1px solid #163a8f; font-size: 0.8rem; word-wrap: break-word; width: 65%;">CONCEPTO</th>
                                    <th style="padding: 6px 8px; text-align: right; font-weight: 700; color: white; font-size: 0.8rem; width: 35%;">VALOR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Filas dinámicas según items -->
                                ${Array(cantidadFilas).fill(0).map((_, idx) => {
                                    const item = prenda.items && prenda.items[idx];
                                    return `
                                        <tr style="border-bottom: 1px solid #e2e8f0;">
                                            <td style="padding: 5px 8px; color: #333; font-weight: 500; border-right: 1px solid #e2e8f0; font-size: 0.75rem; word-wrap: break-word; white-space: normal;">
                                                ${item ? item.item : ''}
                                            </td>
                                            <td style="padding: 5px 8px; text-align: right; color: #333; font-weight: 600; font-size: 0.75rem;">
                                                ${item ? '$' + parseFloat(item.precio || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : ''}
                                            </td>
                                        </tr>
                                    `;
                                }).join('')}
                                
                                <!-- Fila de Total -->
                                <tr style="background: #1e40af; border-top: 2px solid #1e40af;">
                                    <td style="padding: 6px 8px; color: white; font-weight: 700; border-right: 1px solid #163a8f; font-size: 0.8rem; word-wrap: break-word; white-space: normal;">
                                        TOTAL COSTO
                                    </td>
                                    <td style="padding: 6px 8px; text-align: right; color: white; font-weight: 700; font-size: 0.9rem;">
                                        $${parseFloat(prenda.costo_total || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Sección de Imágenes -->
                <div style="display: flex; flex-direction: column; gap: 1rem; justify-content: flex-start; align-items: flex-start; min-width: auto; padding: 0.5rem; margin-top: -5px;">
                    ${(() => {
                        if (imagenesParaMostrar.length === 0) {
                            return '<div style="width: 700px; height: 700px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.75rem;">Sin imágenes</div>';
                        }
                        const stackVertical = esCotizacionCombinada && esPrendaLogo;
                        return `
                            <div style="display: flex; gap: 1.5rem; flex-wrap: ${stackVertical ? 'nowrap' : 'wrap'}; flex-direction: ${stackVertical ? 'column' : 'row'}; justify-content: flex-start;">
                                ${imagenesParaMostrar.map((img, idx) => `
                                    <div style="display: flex; flex-direction: column; align-items: center;">
                                        <img src="${img.url}" 
                                             alt="${img.titulo}"
                                             style="width: 250px; height: 250px; object-fit: contain; border-radius: 8px; border: 2px solid ${img.color}; cursor: pointer; transition: all 0.3s;"
                                             onmouseover="this.style.boxShadow='0 4px 12px rgba(30, 91, 168, 0.4)'; this.style.transform='scale(1.05)';"
                                             onmouseout="this.style.boxShadow='none'; this.style.transform='scale(1)';"/>
                                        <div style="margin-top: 0.75rem; background: linear-gradient(to right, ${img.color}, ${img.color}); padding: 0.5rem 0.75rem; border-radius: 4px; color: white; text-align: center; font-weight: 600; font-size: 0.75rem; white-space: nowrap;">
                                            ${img.grupo}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    })()}
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('visorCostosContenido').innerHTML = html;
    
    // Ajustar altura del modal automáticamente
    setTimeout(() => {
        const modalContent = document.getElementById('visorCostosModalContent');
        const contenido = document.getElementById('visorCostosContenido');
        
        if (modalContent && contenido) {
            // Calcular altura total del contenido
            const headerHeight = modalContent.querySelector('div:first-child').offsetHeight;
            const contentHeight = contenido.scrollHeight;
            const totalHeight = headerHeight + contentHeight;
            
            // Establecer altura máxima pero permitir que crezca según el contenido
            const maxHeight = window.innerHeight * 0.9; // 90vh
            
            if (totalHeight < maxHeight) {
                // Si el contenido cabe, usar altura automática
                modalContent.style.height = 'auto';
                contenido.style.overflowY = 'visible';
            } else {
                // Si no cabe, usar max-height y scroll
                modalContent.style.maxHeight = maxHeight + 'px';
                contenido.style.overflowY = 'auto';
            }
        }
    }, 50);
}

// Cerrar modal al presionar ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarVisorCostos();
    }
});

// Cerrar modal al hacer clic en el fondo
document.getElementById('visorCostosModal')?.addEventListener('click', function(event) {
    if (event.target === this) {
        cerrarVisorCostos();
    }
});

// Navegación con flechas del teclado
document.addEventListener('keydown', function(event) {
    if (document.getElementById('visorCostosModal').style.display === 'flex') {
        if (event.key === 'ArrowLeft') {
            visorCostosAnterior();
        } else if (event.key === 'ArrowRight') {
            visorCostosProximo();
        }
    }
});

