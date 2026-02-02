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
        console.error('[RENDER-PROCESOS] No se encontró contenedor');
        return false;
    }

    const procesos = window.procesosSeleccionados || {};
    
    // Filtrar procesos que tengan datos
    const procesosConDatos = Object.keys(procesos).filter(tipo => {
        return procesos[tipo]?.datos !== null && procesos[tipo]?.datos !== undefined;
    });

    if (procesosConDatos.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 1.5rem; color: #9ca3af; font-size: 0.875rem;">
                <span class="material-symbols-rounded" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;">add_circle</span>
                No hay procesos configurados. Marca un checkbox arriba para agregar procesos.
            </div>
        `;
        container.style.display = 'block';
        return false;
    }
    
    // ✅ OPTIMIZACIÓN: Construir TODO el HTML en memoria ANTES de tocar el DOM
    let html = '';
    procesosConDatos.forEach(tipo => {
        html += generarTarjetaProceso(tipo, procesos[tipo].datos);
    });

    // ✅ UN SOLO REFLOW: Asignar todo el HTML de una vez
    container.innerHTML = html;
    
    // Añadir atributos data-tipo-proceso a las tarjetas para debugging
    container.querySelectorAll('.tarjeta-proceso').forEach(tarjeta => {
        const tipoMatch = tarjeta.className.match(/tipo-([a-z]+)/);
        if (tipoMatch) {
            tarjeta.setAttribute('data-tipo-proceso', tipoMatch[1]);
        }
    });
    
    container.style.display = 'block';
    return true;
};

/**
 * Generar HTML de una tarjeta de proceso
 */
function generarTarjetaProceso(tipo, datos) {
    const icono = iconosProcesos[tipo] || '<span class="material-symbols-rounded">settings</span>';
    // Intentar obtener nombre de múltiples fuentes
    const nombre = nombresProcesos[tipo] || datos.nombre || datos.nombre_proceso || datos.descripcion || datos.tipo_proceso || tipo.toUpperCase();
    
    // Función auxiliar para agregar /storage/ a URLs
    const agregarStorage = (url) => {
        if (!url) return '';
        if (url.startsWith('/')) return url;
        if (url.startsWith('http')) return url;
        return '/storage/' + url;
    };
    
    // Calcular totalTallas como suma de cantidades en objetos, no length
    const damaObj = datos.tallas?.dama || {};
    const caballeroObj = datos.tallas?.caballero || {};
    const totalTallas = Object.keys(damaObj).length + Object.keys(caballeroObj).length;
    
    // Procesar ubicaciones: si es array, convertir a string; si es string JSON, parsear
    let ubicacionesArray = datos.ubicaciones || [];
    
    // Función para limpiar y parsear ubicaciones
    const limpiarYparsearUbicaciones = (raw) => {
        if (!raw) return [];
        
        // Si es string, tratar como JSON
        if (typeof raw === 'string') {
            try {
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [parsed];
            } catch (e) {
                return [raw];
            }
        }
        
        // Si es array
        if (Array.isArray(raw)) {
            // Si el array contiene objetos con 'ubicacion' y 'descripcion'
            if (raw.length > 0 && typeof raw[0] === 'object' && raw[0].ubicacion) {
                // Es un array de objetos del backend - retornar como está
                return raw;
            }
            
            // Si contiene strings, intentar parsear cada uno
            const resultado = raw.map(ub => {
                // Si es string, intentar parsearlo como JSON
                if (typeof ub === 'string') {
                    try {
                        const parsed = JSON.parse(ub);
                        // Si parsea correctamente, es un JSON
                        return Array.isArray(parsed) ? parsed[0] : parsed;
                    } catch (e) {
                        // Si no parsea, es un string plano
                        return ub;
                    }
                }
                // Si es objeto, retornar como está
                if (typeof ub === 'object' && ub !== null) {
                    return ub;
                }
                return ub;
            });
            
            // Aplanar array en caso de que haya arrays anidados
            return resultado.flat();
        }
        
        return [String(raw)];
    };
    
    ubicacionesArray = limpiarYparsearUbicaciones(ubicacionesArray);
    
    const ubicacionesTexto = Array.isArray(ubicacionesArray) && ubicacionesArray.length > 0
        ? ubicacionesArray.map(ub => {
            // Si es objeto con ubicacion y descripcion
            if (typeof ub === 'object' && ub.ubicacion) {
                const ubicacion = ub.ubicacion;
                const descripcion = ub.descripcion ? ub.descripcion.replace(/\n/g, ' ').substring(0, 100) + '...' : '';
                return descripcion 
                    ? `<div style="margin-bottom: 0.75rem;"><strong>📍 ${ubicacion}</strong><br><span style="color: #6b7280; font-size: 0.8rem;">${descripcion}</span></div>` 
                    : `<strong>📍 ${ubicacion}</strong>`;
            }
            // Si es string con nombre o descripcion
            if (typeof ub === 'object' && (ub.nombre || ub.descripcion)) {
                return ub.nombre || ub.descripcion;
            }
            // Si es string plano
            if (typeof ub === 'string') return `<strong>📍 ${ub}</strong>`;
            // Fallback
            return `<strong>📍 ${String(ub)}</strong>`;
        }).join('') 
        : 'Sin ubicaciones';
    
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
                    <strong style="color: #111827; font-size: 1rem;">${nombre}</strong>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" onclick="editarProcesoDesdeModal('${tipo}')" 
                        style="background: #f3f4f6; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer; display: flex; align-items: center;" 
                        title="Editar proceso">
                        <i class="fas fa-edit" style="font-size: 1rem; color: #6b7280;"></i>
                    </button>
                    <button type="button" onclick="eliminarTarjetaProceso('${tipo}')" 
                        style="background: #fee2e2; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer; display: flex; align-items: center;" 
                        title="Eliminar proceso">
                        <i class="fas fa-trash-alt" style="font-size: 1rem; color: #dc2626;"></i>
                    </button>
                </div>
            </div>
            
            <!-- Contenido -->
            <div style="display: grid; gap: 0.75rem;">
                ${(datos.imagenes?.length > 0) ? `
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">IMÁGENES</div>
                        <div style="position: relative; display: inline-block;" onclick="abrirGaleriaImagenesProceso('${tipo}')">
                            <img src="${
                                datos.imagenes[0] instanceof File 
                                    ? URL.createObjectURL(datos.imagenes[0]) 
                                    : agregarStorage(datos.imagenes[0].url || datos.imagenes[0].ruta || datos.imagenes[0])
                            }" 
                                style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 2px solid #e5e7eb;" 
                                alt="Imagen del proceso">
                            ${datos.imagenes.length > 1 ? `
                                <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0, 0, 0, 0.75); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                    +${datos.imagenes.length - 1}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}
                
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">UBICACIONES</div>
                    <div style="color: #374151; font-size: 0.875rem;">${ubicacionesTexto}</div>
                </div>
                
                ${totalTallas > 0 ? `
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">TALLAS (${totalTallas})</div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem;">
                            ${datos.tallas.dama && Object.keys(datos.tallas.dama).length > 0 ? `
                                <div>
                                    <strong style="color: #be185d; margin-right: 0.5rem;"><i class="fas fa-female"></i> Dama:</strong>
                                    ${Object.entries(datos.tallas.dama).map(([talla, cantidad]) => {
                                        return `<span style="background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            ${talla}
                                            <span style="background: #be185d; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
                                        </span>`;
                                    }).join('')}
                                </div>
                            ` : ''}
                            ${datos.tallas.caballero && Object.keys(datos.tallas.caballero).length > 0 ? `
                                <div>
                                    <strong style="color: #1d4ed8; margin-right: 0.5rem;"><i class="fas fa-male"></i> Caballero:</strong>
                                    ${Object.entries(datos.tallas.caballero).map(([talla, cantidad]) => {
                                        return `<span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            ${talla}
                                            <span style="background: #1d4ed8; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
                                        </span>`;
                                    }).join('')}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}
                
                ${datos.observaciones ? `
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">OBSERVACIONES</div>
                        <div style="color: #374151; font-size: 0.875rem; font-style: italic;">${datos.observaciones}</div>
                    </div>
                ` : ''}
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
    
    // 🔍 DEBUG CRÍTICO: Verificar dónde estamos
    const swalPopup = document.querySelector('.swal2-popup');
    const mainModal = document.querySelector('[data-main-modal="prenda-editor"]');
    const customModal = document.querySelector('.modal-overlay');
    console.log('🔍 [EDITAR-PROCESO-MODAL] ESTRUCTURA DEL DOM:');
    console.log('   - Swal2 popup abierto?:', !!swalPopup);
    console.log('   - Modal principal [data-main-modal]?:', !!mainModal);
    console.log('   - Modal custom (.modal-overlay)?:', !!customModal);
    console.log('   - Body children count:', document.body.children.length);

    // Obtener datos del proceso
    const proceso = window.procesosSeleccionados[tipo];
    
    console.log('📦 [EDITAR-PROCESO-MODAL] Datos encontrados:', {
        tipo: tipo,
        procesoExiste: !!proceso,
        tieneDatos: !!proceso?.datos,
        procesoId: proceso?.datos?.id,
        tieneUbicaciones: !!proceso?.datos?.ubicaciones,
        countUbicaciones: Array.isArray(proceso?.datos?.ubicaciones) ? proceso.datos.ubicaciones.length : 0,
        countImagenes: (proceso?.datos?.imagenes?.length || 0)
    });

    if (!proceso?.datos) {
        console.error('❌ [EDITAR-PROCESO-MODAL] No hay datos para el proceso:', tipo);
        return;
    }
    
    // ✅ PASO 1: Iniciar el gestor de edición (marca como "en edición")
    if (window.gestorEditacionProcesos) {
        window.gestorEditacionProcesos.iniciarEdicion(tipo, false); // false = no es nuevo
        console.log('✅ [EDITAR-PROCESO-MODAL] Gestor de edición iniciado para:', tipo);
    }
    
    // ✅ PASO 2: Iniciar editor de procesos (captura estado original)
    if (window.procesosEditor) {
        const exito = window.procesosEditor.iniciarEdicion(tipo, proceso.datos);
        if (!exito) {
            console.error('❌ [EDITAR-PROCESO-MODAL] No se pudo iniciar editor de procesos');
            return;
        }
        console.log('✅ [EDITAR-PROCESO-MODAL] Editor de procesos iniciado en modo EDICIÓN');
    }
    
    // ✅ PASO 3: Cargar datos en el modal ANTES de abrirlo
    console.log('📊 [EDITAR-PROCESO-MODAL] Cargando datos en modal...');
    cargarDatosProcesoEnModal(tipo, proceso.datos);
    
    // ✅ PASO 4: Abrir modal en modo EDICIÓN
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
        
        // ✅ NUEVO: Aplicar automáticamente tallas desde la prenda SI es una cotización
        setTimeout(() => {
            // Copiar tallas de window.tallasRelacionales a window.tallasCantidadesProceso
            if (window.tallasRelacionales) {
                console.log('[EDITAR-PROCESO-MODAL] 🔄 Sincronizando tallas desde prenda a proceso...');
                console.log('[EDITAR-PROCESO-MODAL] 📊 window.tallasRelacionales:', window.tallasRelacionales);
                
                // Inicializar si no existe
                if (!window.tallasCantidadesProceso) {
                    window.tallasCantidadesProceso = { dama: {}, caballero: {} };
                }
                
                // Copiar DAMA
                if (window.tallasRelacionales.DAMA && Object.keys(window.tallasRelacionales.DAMA).length > 0) {
                    window.tallasCantidadesProceso.dama = { ...window.tallasRelacionales.DAMA };
                    console.log('[EDITAR-PROCESO-MODAL] ✏️ Tallas DAMA copiadas al proceso:', window.tallasCantidadesProceso.dama);
                }
                
                // Copiar CABALLERO
                if (window.tallasRelacionales.CABALLERO && Object.keys(window.tallasRelacionales.CABALLERO).length > 0) {
                    window.tallasCantidadesProceso.caballero = { ...window.tallasRelacionales.CABALLERO };
                    console.log('[EDITAR-PROCESO-MODAL] ✏️ Tallas CABALLERO copiadas al proceso:', window.tallasCantidadesProceso.caballero);
                }
                
                // Sincronizar tallas seleccionadas
                if (!window.tallasSeleccionadasProceso) {
                    window.tallasSeleccionadasProceso = { dama: [], caballero: [] };
                }
                window.tallasSeleccionadasProceso.dama = Object.keys(window.tallasCantidadesProceso.dama || {});
                window.tallasSeleccionadasProceso.caballero = Object.keys(window.tallasCantidadesProceso.caballero || {});
                console.log('[EDITAR-PROCESO-MODAL] ✅ Tallas seleccionadas sincronizadas:', window.tallasSeleccionadasProceso);
            }
            
            // Renderizar el resumen con las tallas ya aplicadas
            if (window.actualizarResumenTallasProceso && typeof window.actualizarResumenTallasProceso === 'function') {
                console.log('[EDITAR-PROCESO-MODAL] 📊 Renderizando resumen de tallas automáticamente con "done_all"...');
                window.actualizarResumenTallasProceso();
                console.log('[EDITAR-PROCESO-MODAL] ✅ Resumen de tallas renderizado con tallas aplicadas');
            }
        }, 200);
        
        // Verificar z-index después de abrir
        setTimeout(() => {
            const modalProceso = document.getElementById('modal-proceso-generico');
            const swal = document.querySelector('.swal2-container');
            
            // Forzar z-index máximo para asegurar que esté encima de todo
            if (modalProceso) {
                modalProceso.style.setProperty('z-index', '9999999999', 'important');
                console.log('🔝 [EDITAR-PROCESO-MODAL] Z-index forzado dinámicamente:', window.getComputedStyle(modalProceso).zIndex);
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
        console.error('❌ [EDITAR-PROCESO-MODAL] No existe window.abrirModalProcesoGenerico');
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
    console.log('🔄 [CARGAR-DATOS-PROCESO] Cargando datos en modal para:', tipo, datos);

    // ✅ CRÍTICO: Inicializar window.imagenesProcesoActual SIEMPRE al cargar un proceso
    window.imagenesProcesoActual = [null, null, null];
    
    // ✅ CRÍTICO: Inicializar window.imagenesProcesoExistentes para procesos editados
    window.imagenesProcesoExistentes = [];
    
    // ✅ CRÍTICO: Inicializar ubicaciones si no existen
    if (!window.ubicacionesProcesoSeleccionadas) {
        window.ubicacionesProcesoSeleccionadas = [];
    }
    
    // Limpiar imágenes anteriores
    if (window.imagenesProcesoActual) {
        window.imagenesProcesoActual = [null, null, null];

    }
    
    // ✅ NUEVO: Mantener imágenes existentes (URLs) separadas de las nuevas (Files)
    if (!window.imagenesProcesoExistentes) {
        window.imagenesProcesoExistentes = [];
    }
    window.imagenesProcesoExistentes = [];
    
    // Cargar imágenes (soporte para formato antiguo 'imagen' y nuevo 'imagenes')
    const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
    imagenes.forEach((img, idx) => {
        if (img && idx < 3) {
            const indice = idx + 1;
            // ✅ Detectar si es URL o File (ANTES de usarlo)
            const isFile = img instanceof File;
            const preview = document.getElementById(`proceso-foto-preview-${indice}`);
            
            if (preview) {
                const imgUrl = isFile ? URL.createObjectURL(img) : img;
                
                preview.innerHTML = `
                    <img src="${imgUrl}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                    <button type="button" onclick="eliminarImagenProceso(${indice}); event.stopPropagation();" 
                        style="position: absolute; top: 4px; right: 4px; background: #dc2626; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                        ×
                    </button>
                `;
            }
            
            // ✅ Guardar según tipo
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

        // Convertir objetos de tallas a arrays de strings
        const damaTallas = datos.tallas.dama || {};
        const caballeroTallas = datos.tallas.caballero || {};
        
        // Extraer solo las claves (tallas) del objeto
        window.tallasSeleccionadasProceso.dama = Object.keys(damaTallas);
        window.tallasSeleccionadasProceso.caballero = Object.keys(caballeroTallas);
        

        
        // IMPORTANTE: Guardar las cantidades en la estructura del PROCESO (NO en tallasRelacionales)
        // tallasCantidadesProceso: estructura independiente para las cantidades del proceso
        if (!window.tallasCantidadesProceso) {
            window.tallasCantidadesProceso = { dama: {}, caballero: {} };
        }
        
        // Poblar con datos del proceso (estructura del PROCESO, no de la PRENDA)
        window.tallasCantidadesProceso.dama = { ...damaTallas };
        window.tallasCantidadesProceso.caballero = { ...caballeroTallas };
        

        
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
        if (img instanceof File) {
            return URL.createObjectURL(img);
        }
        if (typeof img === 'string') {
            return img.startsWith('/') || img.startsWith('http') ? img : '/storage/' + img;
        }
        if (typeof img === 'object' && img) {
            const url = img.url || img.ruta || img.ruta_webp || img.ruta_original;
            if (!url) return '';
            return typeof url === 'string' ? (url.startsWith('/') || url.startsWith('http') ? url : '/storage/' + url) : '';
        }
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
    console.log('🔄 [GALERIA] Navegando galería en dirección:', direccion);
    
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
        if (img instanceof File) return URL.createObjectURL(img);
        if (typeof img === 'string') return img.startsWith('/') || img.startsWith('http') ? img : '/storage/' + img;
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
    
    console.log('✅ [GALERIA] Navegación completada');
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
        if (img instanceof File) return URL.createObjectURL(img);
        if (typeof img === 'string') return img.startsWith('/') || img.startsWith('http') ? img : '/storage/' + img;
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
    
    console.log('✅ [GALERIA] Imagen mostrada');
};

window.cerrarGaleriaImagenesProceso = function() {
    console.log(' [GALERIA] Cerrando galería');
    const galeria = document.getElementById('galeria-proceso-modal');
    if (galeria) {
        galeria.remove();
        console.log('✅ [GALERIA] Galería removida del DOM');
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
        html: `<p>Está a punto de eliminar el proceso <strong>${nombresProcesos[tipo] || tipo}</strong></p>`,
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
            // Si el proceso tiene ID (ya fue guardado), eliminar de la DB
            if (proceso.datos?.id) {
                eliminarProcesoDelBackend(proceso.datos.id);
            } else {
                // Solo está en el estado local, eliminar localmente
                eliminarProcesoLocalmente(tipo);
            }
        }
    });
};

// Eliminar proceso del backend
function eliminarProcesoDelBackend(procesoId) {
    // Obtener el número de pedido del estado global o del DOM
    const numeroPedido = window.numeroPedidoActual || 
                         document.querySelector('[data-numero-pedido]')?.getAttribute('data-numero-pedido');
    
    if (!numeroPedido) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo identificar el pedido'
        });
        return;
    }
    
    // Llamada al endpoint
    fetch(`/api/procesos/${procesoId}/eliminar`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            numero_pedido: numeroPedido
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Error en la solicitud');
        return response.json();
    })
    .then(data => {
        if (data.success || data.message?.includes('éxito')) {
            // Encontrar el tipo de proceso y eliminarlo localmente
            const tipo = Object.keys(window.procesosSeleccionados).find(
                t => window.procesosSeleccionados[t].datos?.id === procesoId
            );
            
            if (tipo) {
                eliminarProcesoLocalmente(tipo);
            }
            
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'El proceso ha sido eliminado correctamente',
                timer: 1500
            });
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al eliminar proceso:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo eliminar el proceso. ' + error.message
        });
    });
}

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




