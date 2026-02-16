/**
 * Renderizador de Tarjetas de Procesos
 * Muestra las tarjetas de procesos configurados dentro del modal de prenda
 */

const iconosProcesos = {
    reflectivo: '<span class="material-symbols-rounded" style="color: #f59e0b;">wb_twilight</span>',
    bordado: '<span class="material-symbols-rounded" style="color: #1e40af;">auto_awesome</span>',
    estampado: '<span class="material-symbols-rounded" style="color: #ec4899;">format_paint</span>',
    dtf: '<span class="material-symbols-rounded" style="color: #06b6d4;">print</span>',
    sublimado: '<span class="material-symbols-rounded" style="color: #3b82f6;">water_drop</span>'
};

const nombresProcesos = {
    reflectivo: 'Reflectivo',
    bordado: 'Bordado',
    estampado: 'Estampado',
    dtf: 'DTF',
    sublimado: 'Sublimado'
};

/**
 * Renderizar todas las tarjetas de procesos en el modal de prenda - OPTIMIZADO
 * Usa batch rendering para evitar reflows múltiples
 */
window.renderizarTarjetasProcesos = function() {
    const container = document.getElementById('contenedor-tarjetas-procesos');
    
    if (!container) {
        console.error('🔴 [RENDER-PROCESOS] No se encontró contenedor', {
            contenedorId: 'contenedor-tarjetas-procesos',
            documento: document.body ? 'cargado' : 'no cargado'
        });
        return false;
    }

    const procesos = window.procesosSeleccionados || {};
    console.log('📊 [RENDER-PROCESOS] Iniciando renderización', {
        contenedorEncontrado: true,
        procesosKey: Object.keys(procesos),
        procesosLength: Object.keys(procesos).length,
        displayActual: container.style.display
    });
    
    // Filtrar procesos que tengan datos
    const procesosConDatos = Object.keys(procesos).filter(tipo => {
        const tieneData = procesos[tipo]?.datos !== null && procesos[tipo]?.datos !== undefined;
        if (tieneData) {
            console.log(`  ✅ Tipo: ${tipo} → Tiene datos`, procesos[tipo]?.datos);
        } else {
            console.log(`  ❌ Tipo: ${tipo} → Sin datos`);
        }
        return tieneData;
    });

    console.log('✅ [RENDER-PROCESOS] Procesos a renderizar:', {
        total: procesosConDatos.length,
        tipos: procesosConDatos
    });

    if (procesosConDatos.length === 0) {
        console.log('[RENDER-PROCESOS] Sin procesos con datos, mostrando mensaje vacío');
        container.innerHTML = `
            <div style="text-align: center; padding: 1.5rem; color: #9ca3af; font-size: 0.875rem;">
                <span class="material-symbols-rounded" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;">add_circle</span>
                No hay procesos configurados. Marca un checkbox arriba para agregar procesos.
            </div>
        `;
        container.style.display = 'block';
        return false;
    }
    
    //  OPTIMIZACIÓN: Construir TODO el HTML en memoria ANTES de tocar el DOM
    let html = '';
    procesosConDatos.forEach(tipo => {
        const datosProcess = procesos[tipo].datos;
        console.log(`🎨 [RENDER-PROCESOS] Generando tarjeta para: ${tipo}`, {
            ubicaciones: datosProcess.ubicaciones?.length || 0,
            tallas: Object.keys(datosProcess.tallas?.dama || {}).length + Object.keys(datosProcess.tallas?.caballero || {}).length,
            observaciones: datosProcess.observaciones ? 'sí' : 'no',
            imagenes: datosProcess.imagenes?.length || 0
        });
        html += generarTarjetaProceso(tipo, datosProcess);
    });

    console.log('📝 [RENDER-PROCESOS] HTML generado:', {
        htmlLength: html.length,
        htmlPreview: html.substring(0, 100)
    });

    //  UN SOLO REFLOW: Asignar todo el HTML de una vez
    container.innerHTML = html;
    
    // Añadir atributos data-tipo-proceso a las tarjetas para debugging
    container.querySelectorAll('.tarjeta-proceso').forEach(tarjeta => {
        const tipoMatch = tarjeta.className.match(/tipo-([a-z]+)/);
        if (tipoMatch) {
            tarjeta.setAttribute('data-tipo-proceso', tipoMatch[1]);
        }
    });
    
    // 🔴 CRÍTICO: FORZAR display = 'block' cuando hay procesos
    container.style.display = 'block';
    container.style.visibility = 'visible';
    container.style.opacity = '1';
    
    console.log('✅ [RENDER-PROCESOS] Renderización completada', {
        tarjetasRenderizadas: container.querySelectorAll('.tarjeta-proceso').length,
        displayStyle: container.style.display,
        visibilityStyle: container.style.visibility,
        opacityStyle: container.style.opacity
    });
    return true;
};

/**
 * Generar HTML de una tarjeta de proceso - VERSIÓN SIMPLIFICADA
 */
function generarTarjetaProceso(tipo, datos) {
    const icono = iconosProcesos[tipo] || '<span class="material-symbols-rounded">settings</span>';
    const nombre = nombresProcesos[tipo] || datos.nombre || datos.nombre_proceso || datos.descripcion || datos.tipo_proceso || tipo.toUpperCase();
    
    // Función auxiliar para agregar /storage/ a URLs
    const agregarStorage = (url) => {
        if (!url) return '';
        if (url.startsWith('/')) return url;
        if (url.startsWith('http')) return url;
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
        tallasHTML = `
            <div style="margin-top: 0.75rem;">
                <strong style="font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">TALLAS (${totalTallas})</strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${datos.tallas.dama && Object.keys(datos.tallas.dama).length > 0 ? `
                        ${Object.entries(datos.tallas.dama).map(([talla, cantidad]) => {
                            return `<span style="background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                                DAMA ${talla}: ${cantidad}
                            </span>`;
                        }).join('')}
                    ` : ''}
                    ${datos.tallas.caballero && Object.keys(datos.tallas.caballero).length > 0 ? `
                        ${Object.entries(datos.tallas.caballero).map(([talla, cantidad]) => {
                            return `<span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                                CABALLERO ${talla}: ${cantidad}
                            </span>`;
                        }).join('')}
                    ` : ''}
                    ${datos.tallas.sobremedida && Object.keys(datos.tallas.sobremedida).length > 0 ? `
                        ${Object.entries(datos.tallas.sobremedida).map(([talla, cantidad]) => {
                            return `<span style="background: #fef3c7; color: #92400e; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                                SOBREMEDIDA ${talla}: ${cantidad}
                            </span>`;
                        }).join('')}
                    ` : ''}
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
    
    // HTML de imágenes
    let imagenesHTML = '';
    if (datos.imagenes && datos.imagenes.length > 0) {
        const imagenesValidas = datos.imagenes.filter(img => img !== null && img !== undefined);
        console.log(`🖼️ [RENDER-TARJETA-${tipo}] Renderizando ${imagenesValidas.length} imágenes`, {
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
                    <strong style="font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">IMÁGENES (${imagenesValidas.length})</strong>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        ${imagenesValidas.slice(0, 4).map((img, idx) => {
                            // Determinar la URL según el tipo de objeto
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
                                imgSrc = agregarStorage(img.url);
                            } else if (img.ruta_original) {
                                imgSrc = agregarStorage(img.ruta_original);
                            } else if (typeof img === 'string') {
                                imgSrc = agregarStorage(img);
                            }
                            
                            console.log(`  [RENDER-TARJETA-${tipo}] Imagen ${idx}: ${typeof img} → src="${imgSrc.substring(0, 100)}"`);
                            
                            return imgSrc ? `
                                <div style="position: relative; width: 70px; height: 70px; border-radius: 4px; overflow: hidden; border: 2px solid #e5e7eb; cursor: pointer;" 
                                     onclick="abrirGaleriaImagenesProceso('${tipo}', ${idx})"
                                     title="Click para ver galería">
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
            console.log(`⚠️ [RENDER-TARJETA-${tipo}] Imágenes array existe pero está vacío`);
        }
    } else {
        console.log(`⚠️ [RENDER-TARJETA-${tipo}] NO hay imágenes en datos.imagenes`, {
            tieneImagenes: !!datos.imagenes,
            esArray: Array.isArray(datos.imagenes),
            longitud: datos.imagenes?.length || 0
        });
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
                <div style="margin-bottom: 0.75rem;">
                    <strong style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">UBICACIONES</strong>
                    <div>${ubicacionesHTML}</div>
                </div>
                
                ${tallasHTML}
                ${observacionesHTML}
                ${imagenesHTML}
            </div>
        </div>
    `;
}

/**
 * Editar un proceso existente (desde modal de edición de prenda)
 * 
 * FLUJO:
 * 1. Detecta que es edición (el proceso ya existe en window.procesosSeleccionados)
 * 2. Inicia buffer de edición en procesosEditor
 * 3. Abre modal en modo EDICIÓN
 * 4. Cuando se guarda, aplica cambios sin duplicar
 */
window.editarProcesoDesdeModal = function(tipo) {
    console.log('✏️ [EDITAR-PROCESO-MODAL] Iniciando edición de proceso existente:', tipo);
    
    //  DEBUG CRÍTICO: Verificar dónde estamos
    const swalPopup = document.querySelector('.swal2-popup');
    const mainModal = document.querySelector('[data-main-modal="prenda-editor"]');
    const customModal = document.querySelector('.modal-overlay');
    console.log(' [EDITAR-PROCESO-MODAL] ESTRUCTURA DEL DOM:');
    console.log('   - Swal2 popup abierto?:', !!swalPopup);
    console.log('   - Modal principal [data-main-modal]?:', !!mainModal);
    console.log('   - Modal custom (.modal-overlay)?:', !!customModal);
    console.log('   - Body children count:', document.body.children.length);

    // Obtener datos del proceso
    const proceso = window.procesosSeleccionados[tipo];
    
    console.log(' [EDITAR-PROCESO-MODAL] Datos encontrados:', {
        tipo: tipo,
        procesoExiste: !!proceso,
        tieneDatos: !!proceso?.datos,
        procesoId: proceso?.datos?.id,
        tieneUbicaciones: !!proceso?.datos?.ubicaciones,
        countUbicaciones: Array.isArray(proceso?.datos?.ubicaciones) ? proceso.datos.ubicaciones.length : 0,
        countImagenes: (proceso?.datos?.imagenes?.length || 0)
    });

    if (!proceso?.datos) {
        console.error(' [EDITAR-PROCESO-MODAL] No hay datos para el proceso:', tipo);
        return;
    }
    
    //  PASO 1: Iniciar el gestor de edición (marca como "en edición")
    if (window.gestorEditacionProcesos) {
        window.gestorEditacionProcesos.iniciarEdicion(tipo, false); // false = no es nuevo
        console.log(' [EDITAR-PROCESO-MODAL] Gestor de edición iniciado para:', tipo);
    }
    
    //  PASO 2: Iniciar editor de procesos (captura estado original)
    if (window.procesosEditor) {
        const exito = window.procesosEditor.iniciarEdicion(tipo, proceso.datos);
        if (!exito) {
            console.error(' [EDITAR-PROCESO-MODAL] No se pudo iniciar editor de procesos');
            return;
        }
        console.log(' [EDITAR-PROCESO-MODAL] Editor de procesos iniciado en modo EDICIÓN');
    }
    
    //  PASO 3: Cargar datos en el modal ANTES de abrirlo
    console.log(' [EDITAR-PROCESO-MODAL] Cargando datos en modal...');
    cargarDatosProcesoEnModal(tipo, proceso.datos);
    
    //  PASO 4: Abrir modal en modo EDICIÓN
    if (window.abrirModalProcesoGenerico) {
        console.log('🪟 [EDITAR-PROCESO-MODAL] Abriendo modal genérico en modo EDICIÓN');
        
        const swalContainer = document.querySelector('.swal2-container');
        const swalPopup = document.querySelector('.swal2-popup');
        console.log('🪟 [EDITAR-PROCESO-MODAL] Swal2 visible?:', !!swalContainer);
        console.log('🪟 [EDITAR-PROCESO-MODAL] Swal2 popup existe?:', !!swalPopup);
        if (swalContainer) {
            console.log('🪟 [EDITAR-PROCESO-MODAL] Swal2 z-index:', window.getComputedStyle(swalContainer).zIndex);
        }
        
        window.abrirModalProcesoGenerico(tipo, true); // true = esEdicion
        
        //  NUEVO: Aplicar automáticamente tallas desde la prenda SI es una cotización
        setTimeout(() => {
            // Copiar tallas de window.tallasRelacionales a window.tallasCantidadesProceso
            if (window.tallasRelacionales) {
                console.log('[EDITAR-PROCESO-MODAL]  Sincronizando tallas desde prenda a proceso...');
                console.log('[EDITAR-PROCESO-MODAL]  window.tallasRelacionales:', window.tallasRelacionales);
                
                // Inicializar si no existe
                if (!window.tallasCantidadesProceso) {
                    window.tallasCantidadesProceso = { dama: {}, caballero: {}, unisex: {}, sobremedida: {} };
                }
                
                if (!window.tallasSeleccionadasProceso) {
                    window.tallasSeleccionadasProceso = { dama: [], caballero: [], unisex: [], sobremedida: {} };
                }
                
                // Copiar DAMA - PROCESAR CORRECTAMENTE si tiene SOBREMEDIDA anidada
                if (window.tallasRelacionales.DAMA && Object.keys(window.tallasRelacionales.DAMA).length > 0) {
                    window.tallasCantidadesProceso.dama = {};
                    const tallasDama = [];
                    
                    // 🔥 FIX: Si DAMA tiene SOBREMEDIDA (número o objeto anidado), EXTRAERLA
                    for (const [talla, valor] of Object.entries(window.tallasRelacionales.DAMA)) {
                        if (talla === 'SOBREMEDIDA') {
                            // SOBREMEDIDA puede ser:
                            // 1. Un NÚMERO directo: 344 → significa DAMA sobremedida
                            // 2. Un OBJETO anidado: {DAMA: 34} → extraer por género
                            
                            if (typeof valor === 'number') {
                                // SOBREMEDIDA como número: es para DAMA (género actual)
                                window.tallasCantidadesProceso.sobremedida['DAMA'] = valor;
                                console.log('[EDITAR-PROCESO-MODAL] 🔧 DAMA SOBREMEDIDA (número) extraída:', valor);
                            } else if (typeof valor === 'object' && valor !== null) {
                                // SOBREMEDIDA anidada: {DAMA: 34, CABALLERO: 20}
                                for (const [genero, cantidad] of Object.entries(valor)) {
                                    window.tallasCantidadesProceso.sobremedida[genero] = cantidad;
                                }
                                console.log('[EDITAR-PROCESO-MODAL] 🔧 DAMA SOBREMEDIDA (objeto) extraída:', valor);
                            }
                        } else {
                            // Otras tallas: copiar directamente
                            window.tallasCantidadesProceso.dama[talla] = valor;
                            tallasDama.push(talla);
                        }
                    }
                    window.tallasSeleccionadasProceso.dama = tallasDama;
                    console.log('[EDITAR-PROCESO-MODAL] ✏️ Tallas DAMA copiadas al proceso:', window.tallasCantidadesProceso.dama);
                }
                
                // Copiar CABALLERO
                if (window.tallasRelacionales.CABALLERO && Object.keys(window.tallasRelacionales.CABALLERO).length > 0) {
                    window.tallasCantidadesProceso.caballero = {};
                    const tallasCaballero = [];
                    
                    // 🔥 FIX: Mismo tratamiento para CABALLERO (número o objeto anidado)
                    for (const [talla, valor] of Object.entries(window.tallasRelacionales.CABALLERO)) {
                        if (talla === 'SOBREMEDIDA') {
                            // SOBREMEDIDA puede ser número o objeto
                            if (typeof valor === 'number') {
                                // SOBREMEDIDA como número: es para CABALLERO
                                window.tallasCantidadesProceso.sobremedida['CABALLERO'] = valor;
                                console.log('[EDITAR-PROCESO-MODAL] 🔧 CABALLERO SOBREMEDIDA (número) extraída:', valor);
                            } else if (typeof valor === 'object' && valor !== null) {
                                // SOBREMEDIDA anidada: extraer por género
                                for (const [genero, cantidad] of Object.entries(valor)) {
                                    window.tallasCantidadesProceso.sobremedida[genero] = cantidad;
                                }
                                console.log('[EDITAR-PROCESO-MODAL] 🔧 CABALLERO SOBREMEDIDA (objeto) extraída:', valor);
                            }
                        } else {
                            window.tallasCantidadesProceso.caballero[talla] = valor;
                            tallasCaballero.push(talla);
                        }
                    }
                    window.tallasSeleccionadasProceso.caballero = tallasCaballero;
                    console.log('[EDITAR-PROCESO-MODAL] ✏️ Tallas CABALLERO copiadas al proceso:', window.tallasCantidadesProceso.caballero);
                }
                
                // Copiar UNISEX si existe
                if (window.tallasRelacionales.UNISEX && Object.keys(window.tallasRelacionales.UNISEX).length > 0) {
                    window.tallasCantidadesProceso.unisex = { ...window.tallasRelacionales.UNISEX };
                    window.tallasSeleccionadasProceso.unisex = Object.keys(window.tallasRelacionales.UNISEX);
                    console.log('[EDITAR-PROCESO-MODAL] ✏️ Tallas UNISEX copiadas al proceso:', window.tallasCantidadesProceso.unisex);
                }
                
                console.log('[EDITAR-PROCESO-MODAL]  Tallas seleccionadas sincronizadas:', {
                    dama: window.tallasSeleccionadasProceso.dama,
                    caballero: window.tallasSeleccionadasProceso.caballero,
                    unisex: window.tallasSeleccionadasProceso.unisex,
                    sobremedida: window.tallasCantidadesProceso.sobremedida
                });
            }
            
            // Renderizar el resumen con las tallas ya aplicadas
            if (window.actualizarResumenTallasProceso && typeof window.actualizarResumenTallasProceso === 'function') {
                console.log('[EDITAR-PROCESO-MODAL]  Renderizando resumen de tallas automáticamente con "done_all"...');
                window.actualizarResumenTallasProceso();
                console.log('[EDITAR-PROCESO-MODAL]  Resumen de tallas renderizado con tallas aplicadas');
            }
        }, 200);
        
        // Verificar z-index después de abrir
        setTimeout(() => {
            const modalProceso = document.getElementById('modal-proceso-generico');
            const swal = document.querySelector('.swal2-container');
            
            // Forzar z-index máximo para asegurar que esté encima de todo
            if (modalProceso) {
                modalProceso.style.setProperty('z-index', '9999999999', 'important');
                console.log(' [EDITAR-PROCESO-MODAL] Z-index forzado dinámicamente:', window.getComputedStyle(modalProceso).zIndex);
            }
            
            console.log('🪟 [EDITAR-PROCESO-MODAL] DESPUÉS de abrirModalProcesoGenerico:');
            console.log('   - Modal proceso existe?:', !!modalProceso);
            if (modalProceso) {
                console.log('   - Modal proceso z-index (inline):', modalProceso.style.zIndex);
                console.log('   - Modal proceso z-index (computed):', window.getComputedStyle(modalProceso).zIndex);
                console.log('   - Modal proceso display:', window.getComputedStyle(modalProceso).display);
                console.log('   - Modal proceso classList:', modalProceso.className);
            }
            console.log('   - Swal2 existe?:', !!swal);
            if (swal) {
                console.log('   - Swal2 z-index:', window.getComputedStyle(swal).zIndex);
            }
            console.log('   - Elementos en body:', document.body.children.length);
            
            // Listar top 5 elementos con z-index alto
            const elementos = document.querySelectorAll('[style*="z-index"]');
            console.log('   - Elementos con z-index:', elementos.length);
            const conZAlto = Array.from(elementos).filter(el => {
                const z = parseInt(window.getComputedStyle(el).zIndex);
                return z > 90000;
            }).sort((a, b) => {
                const zA = parseInt(window.getComputedStyle(a).zIndex);
                const zB = parseInt(window.getComputedStyle(b).zIndex);
                return zB - zA;
            });
            console.log('   - Top elementos con z-index alto:');
            conZAlto.slice(0, 5).forEach(el => {
                console.log(`     ✓ ${el.tagName}#${el.id || '(sin-id)'}.${el.className || '(sin-class)'}: z=${window.getComputedStyle(el).zIndex}`);
            });
        }, 100);
        
        // Marcar claramente que estamos en modo edición
        const modalProceso = document.getElementById('modal-proceso-generico');
        if (modalProceso) {
            modalProceso.setAttribute('data-modo-edicion', 'true');
            modalProceso.setAttribute('data-tipo-proceso-editando', tipo);
            console.log('🏷️ [EDITAR-PROCESO-MODAL] Modal marcado como modo edición');
        }
    } else {
        console.error(' [EDITAR-PROCESO-MODAL] No existe window.abrirModalProcesoGenerico');
    }
};

/**
 * Editar un proceso existente
 */
window.editarProceso = function(tipo) {

    
    // Abrir modal del proceso
    if (window.abrirModalProcesoGenerico) {
        window.abrirModalProcesoGenerico(tipo);
        
        // Cargar datos existentes en el modal
        const proceso = window.procesosSeleccionados[tipo];
        if (proceso?.datos) {
            cargarDatosProcesoEnModal(tipo, proceso.datos);
        }
    }
};

/**
 * Cargar datos de un proceso en el modal para editar
 */
function cargarDatosProcesoEnModal(tipo, datos) {
    console.log(' [CARGAR-DATOS-PROCESO] Cargando datos en modal para:', tipo, datos);

    //  CRÍTICO: Inicializar window.imagenesProcesoActual SIEMPRE al cargar un proceso
    window.imagenesProcesoActual = [null, null, null];
    
    //  CRÍTICO: Inicializar window.imagenesProcesoExistentes para procesos editados
    window.imagenesProcesoExistentes = [];
    
    //  CRÍTICO: Inicializar ubicaciones si no existen
    if (!window.ubicacionesProcesoSeleccionadas) {
        window.ubicacionesProcesoSeleccionadas = [];
    }
    
    // Limpiar imágenes anteriores
    if (window.imagenesProcesoActual) {
        window.imagenesProcesoActual = [null, null, null];

    }
    
    //  NUEVO: Mantener imágenes existentes (URLs) separadas de las nuevas (Files)
    if (!window.imagenesProcesoExistentes) {
        window.imagenesProcesoExistentes = [];
    }
    window.imagenesProcesoExistentes = [];
    
    // Cargar imágenes (soporte para formato antiguo 'imagen' y nuevo 'imagenes')
    const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
    imagenes.forEach((img, idx) => {
        if (img && idx < 3) {
            const indice = idx + 1;
            //  Detectar si es URL o File (ANTES de usarlo)
            const isFile = img instanceof File;
            const hasEmbeddedFile = !isFile && img && img.file instanceof File;
            const preview = document.getElementById(`proceso-foto-preview-${indice}`);
            
            if (preview) {
                let imgUrl;
                if (isFile) {
                    imgUrl = URL.createObjectURL(img);
                } else if (hasEmbeddedFile) {
                    // Objeto { file: File, previewUrl: '...' } → regenerar blob fresco
                    if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                        try { URL.revokeObjectURL(img.previewUrl); } catch(e) {}
                    }
                    imgUrl = URL.createObjectURL(img.file);
                    img.previewUrl = imgUrl;
                } else if (typeof img === 'string') {
                    imgUrl = img;
                } else if (img && img.previewUrl) {
                    imgUrl = img.previewUrl;
                } else if (img && (img.url || img.ruta_original)) {
                    imgUrl = img.url || img.ruta_original;
                } else {
                    imgUrl = '';
                    console.warn(`[cargarDatosProcesoEnModal] Imagen ${indice} tipo no reconocido:`, img);
                }
                
                preview.innerHTML = `
                    <img src="${imgUrl}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                    <button type="button" onclick="eliminarImagenProceso(${indice}); event.stopPropagation();" 
                        style="position: absolute; top: 4px; right: 4px; background: #dc2626; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                        ×
                    </button>
                `;
            }
            
            //  Guardar según tipo
            if (isFile) {
                // Es un File nuevo → guardar en imagenesProcesoActual
                if (window.imagenesProcesoActual) {
                    window.imagenesProcesoActual[idx] = img;
                }
            } else {
                // Es una URL existente → guardar en imagenesProcesoExistentes
                window.imagenesProcesoExistentes.push(img);
            }
        }
    });
    
    // Cargar ubicaciones
    if (datos.ubicaciones && window.ubicacionesProcesoSeleccionadas) {
        
        // Función para limpiar ubicaciones - MANTIENE OBJETOS COMPLETOS
        const limpiarUbicaciones = (raw) => {
            if (!raw) return [];
            
            // Si es string, tratar como JSON
            if (typeof raw === 'string') {
                try {
                    const parsed = JSON.parse(raw);
                    if (Array.isArray(parsed)) {
                        // Si es array de objetos con 'ubicacion', mantener como está
                        if (parsed.length > 0 && typeof parsed[0] === 'object' && parsed[0].ubicacion) {
                            return parsed;
                        }
                        // Si es array de strings, retornar limpio (no como JSON)
                        return parsed.map(u => {
                            // Limpiar comillas escapadas
                            if (typeof u === 'string') {
                                return u.replace(/^["\\]*|["\\]*$/g, '').trim();
                            }
                            return typeof u === 'string' ? u : String(u);
                        });
                    }
                    return Array.isArray(parsed) ? parsed : [parsed];
                } catch (e) {
                    // Si no parsea como JSON, limpiar comillas escapadas y retornar
                    const cleaned = raw.replace(/^["\\]*|["\\]*$/g, '').trim();
                    return cleaned ? [cleaned] : [];
                }
            }
            
            // Si es array
            if (Array.isArray(raw)) {
                // Mapear cada elemento
                return raw.map(ub => {
                    // Si es objeto con 'ubicacion', devolverlo completo
                    if (typeof ub === 'object' && ub !== null && ub.ubicacion) {
                        return ub; // DEVOLVER OBJETO COMPLETO
                    }
                    // Si es string, retornar limpio (no en JSON)
                    if (typeof ub === 'string') {
                        // Limpiar comillas escapadas primero
                        ub = ub.replace(/^["\\]*|["\\]*$/g, '').trim();
                        
                        // Si el string parece un JSON array, parsearlo
                        if (ub.startsWith('[') || ub.startsWith('{')) {
                            try {
                                const parsed = JSON.parse(ub);
                                if (Array.isArray(parsed)) {
                                    return parsed[0]; // Tomar primer elemento si es array
                                }
                                return String(parsed);
                            } catch (e) {
                                return ub; // Mantener original si no es JSON válido
                            }
                        }
                        return ub;
                    }
                    return String(ub);
                });
            }
            
            return [String(raw)];
        };
        
        const ubicacionesLimpias = limpiarUbicaciones(datos.ubicaciones);

        window.ubicacionesProcesoSeleccionadas.length = 0;
        window.ubicacionesProcesoSeleccionadas.push(...ubicacionesLimpias);

        if (window.renderizarListaUbicaciones) {

            window.renderizarListaUbicaciones();
        } else {

        }
    } else {

    }
    
    // Cargar observaciones
    const obsInput = document.getElementById('proceso-observaciones');
    if (obsInput && datos.observaciones) {
        obsInput.value = datos.observaciones;
    }
    
    // Cargar tallas
    if (datos.tallas && window.tallasSeleccionadasProceso) {

        // Convertir objetos de tallas a arrays de strings para géneros normales
        let damaTallas = datos.tallas.dama || {};
        let caballeroTallas = datos.tallas.caballero || {};
        let sobremedidaTallas = datos.tallas.sobremedida || {};
        
        // 🔥 FIX: Si DAMA o CABALLERO tienen SOBREMEDIDA anidada, EXTRAERLA
        // Estructura incorrecta: {DAMA: {SOBREMEDIDA: {DAMA: 34}}} 
        // Debe convertirse a: {DAMA: {}} y sobremedidaTallas = {DAMA: 34}
        
        // Procesar DAMA
        const damaTallasLimpias = {};
        for (const [talla, valor] of Object.entries(damaTallas)) {
            if (talla === 'SOBREMEDIDA') {
                // SOBREMEDIDA puede ser número o objeto anidado
                if (typeof valor === 'number') {
                    sobremedidaTallas['DAMA'] = valor;
                    console.log('[cargarDatosProcesoEnModal] 🔧 DAMA SOBREMEDIDA (número) extraída:', valor);
                } else if (typeof valor === 'object' && valor !== null) {
                    for (const [genero, cantidad] of Object.entries(valor)) {
                        sobremedidaTallas[genero] = cantidad;
                    }
                    console.log('[cargarDatosProcesoEnModal] 🔧 DAMA SOBREMEDIDA (objeto) extraída:', valor);
                }
            } else {
                damaTallasLimpias[talla] = valor;
            }
        }
        damaTallas = damaTallasLimpias;
        
        // Procesar CABALLERO
        const caballeroTallasLimpias = {};
        for (const [talla, valor] of Object.entries(caballeroTallas)) {
            if (talla === 'SOBREMEDIDA') {
                // SOBREMEDIDA puede ser número o objeto anidado
                if (typeof valor === 'number') {
                    sobremedidaTallas['CABALLERO'] = valor;
                    console.log('[cargarDatosProcesoEnModal] 🔧 CABALLERO SOBREMEDIDA (número) extraída:', valor);
                } else if (typeof valor === 'object' && valor !== null) {
                    for (const [genero, cantidad] of Object.entries(valor)) {
                        sobremedidaTallas[genero] = cantidad;
                    }
                    console.log('[cargarDatosProcesoEnModal] 🔧 CABALLERO SOBREMEDIDA (objeto) extraída:', valor);
                }
            } else {
                caballeroTallasLimpias[talla] = valor;
            }
        }
        caballeroTallas = caballeroTallasLimpias;
        
        // Extraer solo las claves (tallas) del objeto
        window.tallasSeleccionadasProceso.dama = Object.keys(damaTallas);
        window.tallasSeleccionadasProceso.caballero = Object.keys(caballeroTallas);
        
        // SOBREMEDIDA: Es diferente - guardar el objeto completo {DAMA: 34, CABALLERO: 20}
        if (Object.keys(sobremedidaTallas).length > 0) {
            window.tallasSeleccionadasProceso.sobremedida = sobremedidaTallas;
            console.log('[cargarDatosProcesoEnModal] 📐 Sobremedida cargada:', sobremedidaTallas);
        } else {
            window.tallasSeleccionadasProceso.sobremedida = null;
        }

        
        // IMPORTANTE: Guardar las cantidades en la estructura del PROCESO (NO en tallasRelacionales)
        // tallasCantidadesProceso: estructura independiente para las cantidades del proceso
        if (!window.tallasCantidadesProceso) {
            window.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };
        }
        
        // Poblar con datos del proceso (estructura del PROCESO, no de la PRENDA)
        window.tallasCantidadesProceso.dama = { ...damaTallas };
        window.tallasCantidadesProceso.caballero = { ...caballeroTallas };
        window.tallasCantidadesProceso.sobremedida = { ...sobremedidaTallas };
        

        
        if (window.actualizarResumenTallasProceso) {

            window.actualizarResumenTallasProceso();
        } else {

        }
    } else {

    }
}

/**
 * Abrir galería de imágenes del proceso
 */
window.abrirGaleriaImagenesProceso = function(tipoProceso) {
    console.log('🖼️ [GALERIA] Abriendo galería para proceso:', tipoProceso);
    
    const proceso = window.procesosSeleccionados[tipoProceso];
    
    console.log('🖼️ [GALERIA] Datos del proceso:', {
        tipoProceso: tipoProceso,
        procesoExiste: !!proceso,
        tieneDatos: !!proceso?.datos,
        tieneImagenes: !!proceso?.datos?.imagenes,
        countImagenes: proceso?.datos?.imagenes?.length || 0
    });
    
    if (!proceso?.datos?.imagenes || proceso.datos.imagenes.length === 0) {
        console.error(' [GALERIA] No hay imágenes para mostrar en proceso:', tipoProceso);
        return;
    }
    
    const imagenes = proceso.datos.imagenes;
    console.log('📸 [GALERIA] Imágenes encontradas:', imagenes.length, imagenes);
    
    const galeria = document.createElement('div');
    galeria.id = 'galeria-proceso-modal';
    galeria.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); z-index: 999999; display: flex; flex-direction: column; align-items: center; justify-content: center;';
    
    // Procesar URLs de imágenes
    const procesarUrlImagen = (img) => {
        console.log('🔧 [GALERIA-PROCESAR] Procesando imagen:', {
            tipo: img instanceof File ? 'File' : typeof img,
            tienePreviewUrl: !!img?.previewUrl,
            tieneDataURL: !!img?.dataURL,
            tieneUrl: !!img?.url,
            tieneRuta: !!img?.ruta_original,
            claves: typeof img === 'object' ? Object.keys(img) : 'N/A'
        });
        
        if (img instanceof File) {
            console.log('  → Generando ObjectURL para File');
            return URL.createObjectURL(img);
        }
        
        // Primero intentar con previewUrl (nuevas imágenes del storage)
        if (img?.previewUrl) {
            console.log('  → Usando previewUrl:', img.previewUrl.substring(0, 50));
            return img.previewUrl;
        }
        
        // Luego dataURL
        if (img?.dataURL) {
            console.log('  → Usando dataURL');
            return img.dataURL;
        }
        
        // Luego URLs de backend
        if (typeof img === 'string') {
            const url = img.startsWith('/') || img.startsWith('http') ? img : '/storage/' + img;
            console.log('  → Usando string directo:', url);
            return url;
        }
        
        if (typeof img === 'object' && img) {
            const url = img.url || img.ruta || img.ruta_webp || img.ruta_original;
            if (!url) {
                console.warn('  → No se encontró URL en objeto:', Object.keys(img));
                return '';
            }
            const urlProcesada = typeof url === 'string' ? (url.startsWith('/') || url.startsWith('http') ? url : '/storage/' + url) : '';
            console.log('  → Usando URL de objeto:', urlProcesada);
            return urlProcesada;
        }
        
        console.warn('  → Imagen en formato no reconocido');
        return '';
    };
    
    const urlPrimeraImagen = procesarUrlImagen(imagenes[0]);
    console.log('🖼️ [GALERIA] URL primera imagen procesada:', urlPrimeraImagen);
    
    galeria.innerHTML = `
        <div style="position: absolute; top: 0; left: 0; right: 0; background: rgba(0,0,0,0.8); padding: 1rem; display: flex; justify-content: space-between; align-items: center; z-index: 999999;">
            <div style="color: white; font-size: 1rem; font-weight: 600;">
                <i class="fas fa-images" style="margin-right: 0.5rem;"></i>
                Galería - ${tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1)}
            </div>
            <div style="color: white; font-size: 0.9rem;"><span id="galeria-contador">1</span> / ${imagenes.length}</div>
            <button onclick="cerrarGaleriaImagenesProceso()" style="background: #dc2626; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 1.5rem; cursor: pointer;">×</button>
        </div>
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 4rem 2rem 2rem 2rem; width: 100%;">
            <img id="galeria-imagen-actual" src="${urlPrimeraImagen}" style="max-width: 85vw; max-height: 80vh; border-radius: 8px; object-fit: contain;" onerror="console.error(' Error al cargar imagen de galería:', this.src);">
        </div>
        ${imagenes.length > 1 ? `
            <button onclick="navegarGaleriaImagenesProceso(-1)" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 2rem; cursor: pointer;">‹</button>
            <button onclick="navegarGaleriaImagenesProceso(1)" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 2rem; cursor: pointer;">›</button>
            <div style="position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.5rem; padding: 0.75rem; background: rgba(0,0,0,0.6); border-radius: 8px;">
                ${imagenes.map((img, idx) => {
                    const urlMiniatura = procesarUrlImagen(img);
                    return `<img src="${urlMiniatura}" onclick="irAImagenProceso(${idx})" class="miniatura-galeria-proceso" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid ${idx === 0 ? '#0ea5e9' : 'transparent'}; opacity: ${idx === 0 ? '1' : '0.6'};" onerror="console.error(' Error en miniatura:', this.src);">`;
                }).join('')}
            </div>
        ` : ''}
    `;
    
    galeria.dataset.indiceActual = '0';
    window.imagenesGaleriaProceso = imagenes;
    console.log('🖼️ [GALERIA] Galería modal creada y agregada al DOM');
    document.body.appendChild(galeria);
};

window.navegarGaleriaImagenesProceso = function(direccion) {
    console.log(' [GALERIA] Navegando galería en dirección:', direccion);
    
    const galeria = document.getElementById('galeria-proceso-modal');
    if (!galeria || !window.imagenesGaleriaProceso) {
        console.error(' [GALERIA] Galería o imágenes no encontradas');
        return;
    }
    
    let indice = parseInt(galeria.dataset.indiceActual) + direccion;
    console.log(' [GALERIA] Índice calculado:', {
        anterior: parseInt(galeria.dataset.indiceActual),
        direccion: direccion,
        nuevo: indice,
        total: window.imagenesGaleriaProceso.length
    });
    
    if (indice < 0) indice = window.imagenesGaleriaProceso.length - 1;
    if (indice >= window.imagenesGaleriaProceso.length) indice = 0;
    
    galeria.dataset.indiceActual = indice;
    
    const procesarUrlImagen = (img) => {
        // Mismo procesamiento que en abrirGaleriaImagenesProceso
        if (img instanceof File) {
            return URL.createObjectURL(img);
        }
        if (img?.previewUrl) {
            return img.previewUrl;
        }
        if (img?.dataURL) {
            return img.dataURL;
        }
        if (typeof img === 'string') {
            return img.startsWith('/') || img.startsWith('http') ? img : '/storage/' + img;
        }
        if (typeof img === 'object' && img) {
            const url = img.url || img.ruta || img.ruta_webp || img.ruta_original;
            return (typeof url === 'string') ? (url.startsWith('/') || url.startsWith('http') ? url : '/storage/' + url) : '';
        }
        return '';
    };
    
    const img = window.imagenesGaleriaProceso[indice];
    const imgElement = document.getElementById('galeria-imagen-actual');
    if (imgElement) {
        const urlProcesada = procesarUrlImagen(img);
        console.log('🖼️ [GALERIA] Cambiando imagen a índice', indice, 'URL:', urlProcesada);
        imgElement.src = urlProcesada;
    }
    
    const contador = document.getElementById('galeria-contador');
    if (contador) contador.textContent = indice + 1;
    
    document.querySelectorAll('.miniatura-galeria-proceso').forEach((m, i) => {
        m.style.border = i === indice ? '2px solid #0ea5e9' : '2px solid transparent';
        m.style.opacity = i === indice ? '1' : '0.6';
    });
    
    console.log(' [GALERIA] Navegación completada');
};

window.irAImagenProceso = function(indice) {
    console.log('👉 [GALERIA] Ir a imagen:', indice);
    
    const galeria = document.getElementById('galeria-proceso-modal');
    if (!galeria) {
        console.error(' [GALERIA] Galería modal no encontrada');
        return;
    }
    
    galeria.dataset.indiceActual = indice;
    
    const procesarUrlImagen = (img) => {
        // Mismo procesamiento que en abrirGaleriaImagenesProceso
        if (img instanceof File) {
            return URL.createObjectURL(img);
        }
        if (img?.previewUrl) {
            return img.previewUrl;
        }
        if (img?.dataURL) {
            return img.dataURL;
        }
        if (typeof img === 'string') {
            return img.startsWith('/') || img.startsWith('http') ? img : '/storage/' + img;
        }
        if (typeof img === 'object' && img) {
            const url = img.url || img.ruta || img.ruta_webp || img.ruta_original;
            return (typeof url === 'string') ? (url.startsWith('/') || url.startsWith('http') ? url : '/storage/' + url) : '';
        }
        return '';
    };
    
    const img = window.imagenesGaleriaProceso[indice];
    const imgElement = document.getElementById('galeria-imagen-actual');
    if (imgElement) {
        const urlProcesada = procesarUrlImagen(img);
        console.log('🖼️ [GALERIA] Mostrando imagen en índice', indice, 'URL:', urlProcesada);
        imgElement.src = urlProcesada;
    }
    
    const contador = document.getElementById('galeria-contador');
    if (contador) contador.textContent = indice + 1;
    
    document.querySelectorAll('.miniatura-galeria-proceso').forEach((m, i) => {
        m.style.border = i === indice ? '2px solid #0ea5e9' : '2px solid transparent';
        m.style.opacity = i === indice ? '1' : '0.6';
    });
    
    console.log(' [GALERIA] Imagen mostrada');
};

window.cerrarGaleriaImagenesProceso = function() {
    console.log(' [GALERIA] Cerrando galería');
    const galeria = document.getElementById('galeria-proceso-modal');
    if (galeria) {
        galeria.remove();
        console.log(' [GALERIA] Galería removida del DOM');
    }
    window.imagenesGaleriaProceso = null;
};

// Eliminar proceso con confirmación
window.eliminarTarjetaProceso = function(tipo) {
    const proceso = window.procesosSeleccionados?.[tipo];
    
    if (!proceso) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró el proceso para eliminar'
        });
        return;
    }
    
    // Mostrar modal de confirmación
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar proceso?',
        html: `<p>Está a punto de eliminar el proceso <strong>${nombresProcesos[tipo] || tipo}</strong></p>
               <p style="font-size: 0.9em; color: #666; margin-top: 0.5rem;"> El cambio se aplicará cuando guardes los cambios de la prenda.</p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        confirmButtonColor: '#ef4444',
        cancelButtonText: 'Cancelar',
        cancelButtonColor: '#6b7280',
        width: '400px',
        customClass: {
            container: 'swal-container-centered',
            popup: 'swal-popup-compact'
        },
        didOpen: (modal) => {
            // Asegurar z-index máximo
            modal.style.zIndex = '999999';
            const backdrop = document.querySelector('.swal2-container');
            if (backdrop) {
                backdrop.style.zIndex = '999998';
            }
            // Centrar modal
            const popup = modal.closest('.swal2-popup');
            if (popup) {
                popup.style.margin = 'auto';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            //  NUEVO: Marcar proceso como "eliminado" en lugar de eliminarlo inmediatamente
            // El backend solo se eliminará cuando el usuario guarde los cambios
            marcarProcesoParaEliminar(tipo, proceso);
        }
    });
};

/**
 * 🗑️ STORAGE GLOBAL para procesos a eliminar
 * Se mantiene separado de window.procesosSeleccionados que se recarga
 */
window.procesosParaEliminarIds = new Set();

/**
 *  NUEVO: Marcar un proceso como "eliminado" sin enviarlo al backend inmediatamente
 * Se eliminará del backend cuando se guarden los cambios de la prenda
 */
function marcarProcesoParaEliminar(tipo, proceso) {
    console.log('\n🗑️ ===== [MARCAR-ELIMINAR] INICIO =====');
    console.log('🗑️ Tipo recibido:', tipo);
    console.log('🗑️ Proceso recibido:', proceso);
    
    //  NUEVO: Guardar en Set separado que NO se borra al recargar procesos
    if (proceso.datos?.id) {
        window.procesosParaEliminarIds.add(proceso.datos.id);
        console.log(' ID agregado a window.procesosParaEliminarIds:', {
            id: proceso.datos.id,
            procesosActuales: Array.from(window.procesosParaEliminarIds)
        });
    }
    
    // También marcar en el objeto local (para UI)
    proceso.marcadoParaEliminar = true;
    console.log(' Proceso marcado en estado local:', proceso.marcadoParaEliminar);
    
    // ===== BÚSQUEDA EN EL DOM =====
    console.log('\n BUSCANDO TARJETA EN DOM:');
    console.log(`   Buscando: [data-proceso-tipo="${tipo}"]`);
    
    // Listar TODAS las tarjetas del DOM PRIMERO
    const allTarjetas = document.querySelectorAll('div[data-proceso-tipo]');
    console.log(`\n Tarjetas disponibles en el DOM: ${allTarjetas.length}`);
    allTarjetas.forEach((t, idx) => {
        const tipo_attr = t.getAttribute('data-proceso-tipo');
        const classes = t.className;
        const parent = t.parentElement?.tagName;
        console.log(`   [${idx}] tipo="${tipo_attr}" | clases="${classes.substring(0, 50)}" | parent=${parent}`);
    });
    
    // Intentar encontrar la tarjeta por varios selectores
    let tarjeta = null;
    let selectorUsado = '';
    
    console.log('\n🔎 Probando selectores:');
    
    // Selector 1
    console.log('   1️⃣  Intentando: document.querySelector(`[data-proceso-tipo="${tipo}"]`)');
    tarjeta = document.querySelector(`[data-proceso-tipo="${tipo}"]`);
    if (tarjeta) {
        selectorUsado = 'data-proceso-tipo';
        console.log('    ENCONTRADA con selector 1');
    }
    
    // Selector 2
    if (!tarjeta) {
        console.log('   2️⃣  Intentando: document.querySelector(`[data-tipo="${tipo}"]`)');
        tarjeta = document.querySelector(`[data-tipo="${tipo}"]`);
        if (tarjeta) {
            selectorUsado = 'data-tipo';
            console.log('    ENCONTRADA con selector 2');
        } else {
            console.log('    No encontrada');
        }
    }
    
    // Selector 3
    if (!tarjeta) {
        console.log('   3️⃣  Intentando: document.querySelector(`[data-process-type="${tipo}"]`)');
        tarjeta = document.querySelector(`[data-process-type="${tipo}"]`);
        if (tarjeta) {
            selectorUsado = 'data-process-type';
            console.log('    ENCONTRADA con selector 3');
        } else {
            console.log('    No encontrada');
        }
    }
    
    // ===== MANIPULACIÓN DEL DOM =====
    if (tarjeta) {
        console.log('\n TARJETA ENCONTRADA');
        console.log('   Selector usado:', selectorUsado);
        console.log('   Elemento:', tarjeta.tagName);
        console.log('   ID:', tarjeta.id || 'sin ID');
        console.log('   Clases:', tarjeta.className);
        console.log('   Atributos:', {
            'data-proceso-tipo': tarjeta.getAttribute('data-proceso-tipo'),
            'data-tipo': tarjeta.getAttribute('data-tipo'),
            'data-process-type': tarjeta.getAttribute('data-process-type')
        });
        
        console.log('\n🗑️  INICIANDO REMOCIÓN DEL DOM:');
        console.log('   Aplicando: display = none');
        tarjeta.style.display = 'none';
        
        console.log('   Esperando 200ms...');
        setTimeout(() => {
            console.log('   Ejecutando: remove()');
            try {
                tarjeta.remove();
                console.log('    remove() ejecutado exitosamente');
                
                // Verificar que fue removida
                const verificacion = document.querySelector(`[data-proceso-tipo="${tipo}"]`);
                if (!verificacion) {
                    console.log('    VERIFICACIÓN: Elemento removido del DOM correctamente');
                } else {
                    console.warn('     VERIFICACIÓN: Elemento AÚN existe en el DOM!');
                    console.log('   Elemento restante:', verificacion);
                }
            } catch (error) {
                console.error('    ERROR en remove():', error);
            }
        }, 200);
        
    } else {
        console.error('\n TARJETA NO ENCONTRADA');
        console.error('   Ningún selector funcionó para tipo:', tipo);
        console.error('   window.procesosSeleccionados:', window.procesosSeleccionados);
        console.error('   Claves disponibles:', Object.keys(window.procesosSeleccionados || {}));
    }
    
    console.log('🗑️ ===== [MARCAR-ELIMINAR] FIN =====\n');
    
    Swal.fire({
        icon: 'success',
        title: 'Marcado para eliminar',
        html: `<p>El proceso <strong>${nombresProcesos[tipo] || tipo}</strong> será eliminado cuando guardes los cambios.</p>`,
        timer: 1500
    });
}


/**
 *  NUEVO: Eliminar procesos marcados para eliminación del backend
 * Se ejecuta cuando el usuario guarda los cambios de la prenda
 */
window.eliminarProcesossMarcadosDelBackend = async function() {
    console.log('🗑️ [ELIMINAR-BACKEND] ========== INICIANDO ELIMINACIÓN DE PROCESOS ==========');
    
    console.log('🗑️ [ELIMINAR-BACKEND] Procesos marcados para eliminar (Set):', Array.from(window.procesosParaEliminarIds || new Set()));
    
    //  NUEVO: Usar el Set que se mantiene separado y no se recarga
    const idsParaEliminar = Array.from(window.procesosParaEliminarIds || new Set());
    
    if (idsParaEliminar.length === 0) {
        console.log(' [ELIMINAR-BACKEND] No hay procesos marcados para eliminar');
        return true; // Sin errores
    }
    
    console.log(`🗑️ [ELIMINAR-BACKEND] Total de procesos a eliminar: ${idsParaEliminar.length}`);
    console.log('🗑️ [ELIMINAR-BACKEND] IDs a eliminar:', idsParaEliminar);
    
    //  Obtener el número de pedido de forma más confiable
    const numeroPedido = window.prendaEnEdicion?.pedidoId ||
                         window.numeroPedidoActual || 
                         document.querySelector('[data-numero-pedido]')?.getAttribute('data-numero-pedido') ||
                         document.querySelector('[data-pedido-id]')?.getAttribute('data-pedido-id');
    
    console.log('🗑️ [ELIMINAR-BACKEND] Número/ID de pedido:', {
        numeroPedido,
        prendaEnEdicion: window.prendaEnEdicion?.pedidoId,
        numeroPedidoActual: window.numeroPedidoActual
    });
    
    try {
        // Eliminar cada proceso del backend
        for (const id of idsParaEliminar) {
            const nombreProceso = Object.entries(window.procesosSeleccionados || {})
                .find(([tipo, proc]) => proc.datos?.id === id)?.[0] || `Proceso ${id}`;
            
            console.log(`🗑️ [ELIMINAR-BACKEND] Enviando DELETE para: ${nombreProceso} (ID: ${id})`);
            
            const response = await fetch(`/api/procesos/${id}/eliminar`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    numero_pedido: numeroPedido
                })
            });
            
            console.log(`🗑️ [ELIMINAR-BACKEND] Response status: ${response.status}`);
            
            if (!response.ok) {
                const errorData = await response.json();
                console.error(` [ELIMINAR-BACKEND] Error en response:`, errorData);
                console.error(` [ELIMINAR-BACKEND] Errores de validación:`, errorData.errors);
                throw new Error(`Error eliminando ${nombreProceso}: ${errorData.message || 'Error desconocido'}`);
            }
            
            const data = await response.json();
            console.log(` [ELIMINAR-BACKEND] ${nombreProceso} eliminado exitosamente`);
            console.log(` [ELIMINAR-BACKEND] Response data:`, data);
        }
        
        // Limpiar el Set después de eliminar exitosamente
        console.log('🗑️ [ELIMINAR-BACKEND] Limpiando Set de procesos para eliminar');
        window.procesosParaEliminarIds.clear();
        console.log(' [ELIMINAR-BACKEND] Set limpiado');
        
        console.log(' [ELIMINAR-BACKEND] ========== TODOS LOS PROCESOS ELIMINADOS CORRECTAMENTE ==========');
        return true;
        
    } catch (error) {
        console.error(' [ELIMINAR-BACKEND] Error completo:', error);
        throw error;
    }
};

// Eliminar proceso localmente (UI)
function eliminarProcesoLocalmente(tipo) {
    // Eliminar del estado
    if (window.procesosSeleccionados && window.procesosSeleccionados[tipo]) {
        delete window.procesosSeleccionados[tipo];
    }
    
    // Desmarcar checkbox
    const checkbox = document.getElementById(`checkbox-${tipo}`);
    if (checkbox) {
        checkbox.checked = false;
    }
    
    // Re-renderizar
    window.renderizarTarjetasProcesos();
    
    // Actualizar resumen
    if (window.actualizarResumenProcesos) {
        window.actualizarResumenProcesos();
    }
}




