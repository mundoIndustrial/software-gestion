/**
 * ⚙️ Módulo de Procesos
 * Responsabilidad: Cargar y mostrar procesos (reflectivo, bordado, etc.)
 */

class PrendaEditorProcesos {
    /**
     * Cargar procesos en el modal
     */
    static cargar(prenda) {
        console.log('⚙️ [PROCESOS-LOADER] ===== INICIO CARGA =====');
        console.log('⚙️ [PROCESOS-LOADER] prenda.id:', prenda.id);
        console.log('⚙️ [PROCESOS-LOADER] prenda.procesos EXISTS:', !!prenda.procesos);
        console.log('⚙️ [PROCESOS-LOADER] prenda.procesos type:', typeof prenda.procesos);
        console.log('⚙️ [PROCESOS-LOADER] prenda.procesos isArray:', Array.isArray(prenda.procesos));
        console.log('⚙️ [PROCESOS-LOADER] prenda.procesos CONTENIDO COMPLETO:');
        console.log(prenda.procesos);
        
        if (!prenda.procesos) {
            console.log('⚠️ [PROCESOS-LOADER] procesos es NULL/UNDEFINED');
            window.procesosSeleccionados = {};
            return;
        }
        
        if (Array.isArray(prenda.procesos)) {
            console.log(' [PROCESOS-LOADER] Es ARRAY con', prenda.procesos.length, 'elementos');
        }
        
        console.log('⚙️ [Procesos] Cargando:', {
            cantidad: prenda.procesos?.length || Object.keys(prenda.procesos || {}).length || 0,
            tipo: Array.isArray(prenda.procesos) ? 'array' : typeof prenda.procesos,
            procesos: prenda.procesos
        });
        
        // 🔥 CRÍTICO: Replicar a global PRIMERO para que renderizarTarjetasProcesos() encuentre los datos
        if (prenda.procesos && typeof prenda.procesos === 'object') {
            // Convertir a formato plano de window.procesosSeleccionados
            window.procesosSeleccionados = {};
            
            if (Array.isArray(prenda.procesos)) {
                // Si es array, convertir a objeto con keys
                prenda.procesos.forEach((proceso, idx) => {
                    // 🔴 CRÍTICO: Extraer nombre del tipo de proceso - puede venir en diferentes formatos
                    let tipoOriginal = proceso.tipo 
                        || proceso.nombre 
                        || proceso.tipo_proceso
                        || (proceso.tipoProceso && proceso.tipoProceso.nombre)  // ← Nombre desde relación anidada (servidor)
                        || `proceso_${idx}`;
                    
                    // 🔴 Normalizar a slug lowercase para que matchee iconos/nombres del renderizador
                    const tipo = String(tipoOriginal)
                        .toLowerCase()
                        .trim()
                        .replace(/\s+/g, '-');
                    
                    // 🔴 Normalizar imágenes: asegurar prefijo /storage/ para rutas de servidor
                    const datosNormalizados = { ...proceso, tipo: tipo };
                    if (datosNormalizados.imagenes && Array.isArray(datosNormalizados.imagenes)) {
                        console.log(`[PROCESOS-LOADER] 🖼️ Imágenes recibidas para ${tipo}:`, {
                            cantidad: datosNormalizados.imagenes.length,
                            primeraprimera: datosNormalizados.imagenes[0],
                            tipo_primera: typeof datosNormalizados.imagenes[0],
                            esObjeto: datosNormalizados.imagenes[0] instanceof Object
                        });
                        datosNormalizados.imagenes = datosNormalizados.imagenes.map(img => {
                            if (typeof img === 'string') {
                                if (img.startsWith('/') || img.startsWith('http') || img.startsWith('blob:') || img.startsWith('data:')) return img;
                                return '/storage/' + img;
                            }
                            // Si es objeto, asegurar que tiene las rutas normalizadas
                            if (typeof img === 'object' && img !== null) {
                                if (img.ruta_webp && !img.ruta_webp.startsWith('/') && !img.ruta_webp.startsWith('http') && !img.ruta_webp.startsWith('blob:') && !img.ruta_webp.startsWith('data:')) img.ruta_webp = '/storage/' + img.ruta_webp;
                                if (img.ruta_original && !img.ruta_original.startsWith('/') && !img.ruta_original.startsWith('http') && !img.ruta_original.startsWith('blob:') && !img.ruta_original.startsWith('data:')) img.ruta_original = '/storage/' + img.ruta_original;
                                if (img.url && !img.url.startsWith('/') && !img.url.startsWith('http') && !img.url.startsWith('blob:') && !img.url.startsWith('data:')) img.url = '/storage/' + img.url;
                            }
                            return img;
                        });
                    }
                    
                    window.procesosSeleccionados[tipo] = {
                        tipo: tipo,
                        datos: datosNormalizados
                    };
                    
                    // 🔴 CRÍTICO: Verificar que modo_tallas existe y se propagó correctamente
                    console.log(`[PROCESOS-LOADER] 🎯 Proceso "${tipo}" cargado:`, {
                        tipo: tipo,
                        procesoId: datosNormalizados.id,
                        modo_tallas_desde_servidor: datosNormalizados.modo_tallas,
                        modo_tallas_en_window: window.procesosSeleccionados[tipo].datos.modo_tallas,
                        modoTallas_desde_servidor: datosNormalizados.modoTallas,
                        modoTallas_en_window: window.procesosSeleccionados[tipo].datos.modoTallas,
                        tipoProcesoNested: datosNormalizados.tipoProceso?.nombre,
                        datosKeys: Object.keys(datosNormalizados).slice(0, 15),  // Primeros 15 campos
                        estructuraCompleta: {
                            datosNormalizados: datosNormalizados,
                            windowDatos: window.procesosSeleccionados[tipo]?.datos
                        }
                    });
                    
                    // DEBUG: Registrar datosExtendidos si existen
                    if (datosNormalizados.datosExtendidos) {
                        console.log(`[PROCESOS-LOADER] 📊 datosExtendidos para ${tipo}:`, {
                            tiene: true,
                            estructura: Object.keys(datosNormalizados.datosExtendidos),
                            contenido: datosNormalizados.datosExtendidos
                        });
                    }
                });
            } else {
                // Si ya es objeto, procesarlo
                Object.entries(prenda.procesos).forEach(([key, proceso]) => {
                    // Si el valor es un objeto con datos, usarlo directamente
                    if (proceso && typeof proceso === 'object' && (proceso.datos || proceso.tipo || proceso.ubicaciones)) {
                        const datosProceso = proceso.datos || proceso;
                        const tipoNormalizado = String(
                            datosProceso.tipo ||
                            datosProceso.tipo_proceso ||
                            datosProceso.nombre ||
                            datosProceso.nombre_proceso ||
                            datosProceso.tipoProceso?.nombre ||
                            key
                        ).toLowerCase().trim().replace(/\s+/g, '-');

                        window.procesosSeleccionados[tipoNormalizado] = {
                            tipo: tipoNormalizado,
                            datos: {
                                ...datosProceso,
                                tipo: tipoNormalizado,
                                nombre: datosProceso.nombre || datosProceso.tipo_proceso || datosProceso.nombre_proceso || datosProceso.tipoProceso?.nombre || tipoNormalizado
                            }
                        };
                    } else if (proceso === true || proceso === 1) {
                        // Si es solo un boolean/flag, crear objeto mínimo
                        const tipoNormalizado = String(key).toLowerCase().trim().replace(/\s+/g, '-');
                        window.procesosSeleccionados[tipoNormalizado] = {
                            tipo: tipoNormalizado,
                            datos: {
                                tipo: tipoNormalizado,
                                ubicaciones: [],
                                tallas: { dama: {}, caballero: {}, sobremedida: {} },
                                observaciones: '',
                                imagenes: []
                            }
                        };
                        return;
                    }

                    if (window.procesosSeleccionados[key]) {
                        const procesoActual = window.procesosSeleccionados[key];
                        delete window.procesosSeleccionados[key];
                        window.procesosSeleccionados[procesoActual.tipo] = procesoActual;
                    }
                });
            }
            
            console.log('[Carga] ⚙️ Procesos replicados en window.procesosSeleccionados:', {
                keys: Object.keys(window.procesosSeleccionados),
                count: Object.keys(window.procesosSeleccionados).length,
                contenido: window.procesosSeleccionados
            });
            
            // 🔴 CRÍTICO: Para procesos desde cotización, auto-aplicar "todas las tallas" si están vacías
            // Los procesos de cotización vienen con talla_cantidad vacío - por defecto aplican a TODAS las tallas
            const tallasRelacionales = window.tallasRelacionales || {};
            const hayTallasEnPrenda = Object.keys(tallasRelacionales).length > 0;
            
            if (hayTallasEnPrenda && prenda.tipo === 'cotizacion') {
                console.log('[Procesos] 🎯 Cotización detectada - auto-aplicando tallas a procesos sin tallas');
                
                // Construir objeto de tallas en formato proceso (lowercase keys)
                const tallasParaProceso = {
                    dama: tallasRelacionales.DAMA ? { ...tallasRelacionales.DAMA } : {},
                    caballero: tallasRelacionales.CABALLERO ? { ...tallasRelacionales.CABALLERO } : {},
                    sobremedida: tallasRelacionales.SOBREMEDIDA ? { ...tallasRelacionales.SOBREMEDIDA } : {}
                };
                
                Object.entries(window.procesosSeleccionados).forEach(([key, proceso]) => {
                    const datos = proceso.datos;
                    // Verificar si tallas están vacías
                    const tallasVacias = !datos.tallas || 
                        (Object.keys(datos.tallas?.dama || {}).length === 0 && 
                         Object.keys(datos.tallas?.caballero || {}).length === 0 &&
                         Object.keys(datos.tallas?.sobremedida || {}).length === 0);
                    const tallaCantidadVacia = !datos.talla_cantidad || 
                        (Array.isArray(datos.talla_cantidad) && datos.talla_cantidad.length === 0) ||
                        (typeof datos.talla_cantidad === 'object' && Object.keys(datos.talla_cantidad).length === 0);
                    
                    if (tallasVacias && tallaCantidadVacia) {
                        datos.tallas = JSON.parse(JSON.stringify(tallasParaProceso));
                        datos._aplicarTodasTallas = true; // Flag para indicar que fue auto-asignado
                        console.log(`[Procesos]  ${key}: tallas auto-asignadas (todas las de la prenda)`, datos.tallas);
                    }
                });
            }
        }
        
        // 🎨 CRÍTICO: Usar el nuevo renderizador de tarjetas
        if (window.renderizarTarjetasProcesos) {
            console.log(' [Procesos] Función renderizarTarjetasProcesos() disponible');
            console.log('[Procesos]  window.procesosSeleccionados actual:', window.procesosSeleccionados);
            
            // Ejecutar inmediatamente (sin delay)
            console.log('[Procesos] Ejecutando renderización AHORA...');
            const exito = window.renderizarTarjetasProcesos();
            
            console.log('[Procesos] Resultado renderización:', {
                exito: exito,
                container: document.getElementById('contenedor-tarjetas-procesos'),
                containerDisplay: document.getElementById('contenedor-tarjetas-procesos')?.style.display,
                containerHTML: document.getElementById('contenedor-tarjetas-procesos')?.innerHTML.substring(0, 100)
            });
            
            if (exito) {
                console.log(' [Procesos] Completado - Tarjetas renderizadas correctamente');
                
                // 🔴 CRÍTICO: Marcar los checkboxes de procesos correspondientes
                this._marcarCheckboxesProcesos(window.procesosSeleccionados);
                
                // 🔴 NUEVO: Configurar drag & drop para procesos
                // El renderizador debe llamar a esto después de renderizar
                console.log('[PROCESOS-LOADER] 🔄 Verificando configurarDragDropProcesos');
                console.log('[PROCESOS-LOADER] 📊 Timestamp:', new Date().toISOString());
                console.log('[PROCESOS-LOADER] 🔍 Stack trace:', new Error().stack);
                
                if (typeof configurarDragDropProcesos === 'function') {
                    console.log('[PROCESOS-LOADER] 🚀 Llamando a configurarDragDropProcesos desde loader');
                    configurarDragDropProcesos();
                    console.log('[PROCESOS-LOADER]  configurarDragDropProcesos ejecutado');
                } else {
                    console.warn('[PROCESOS-LOADER] ⚠️ configurarDragDropProcesos no disponible');
                }
                
                // Verificación final: asegurar que el contenedor es visible
                const container = document.getElementById('contenedor-tarjetas-procesos');
                if (container) {
                    console.log('[Procesos]  Contenedor visible:', {
                        display: container.style.display,
                        visibility: container.style.visibility,
                        innerHTML_length: container.innerHTML.length
                    });
                }
                return;
            } else {
                console.warn('⚠️ [Procesos] renderizarTarjetasProcesos() retornó false');
            }
        } else {
            console.warn('⚠️ [Procesos] renderizarTarjetasProcesos() NO DISPONIBLE');
        }
        
        // Fallback: Si no existe renderizador, crear tarjetas simples
        let container = document.getElementById('contenedor-tarjetas-procesos');
        if (!container) {
            container = document.getElementById('procesos-agregados');
        }
        if (!container) {
            container = document.querySelector('.procesos-container, [class*="procesos"]');
        }
        
        if (!container) {
            console.warn(' [Procesos] No encontrado contenedor');
            return;
        }
        
        // Convertir procesos a array
        const procesosArray = this._convertirAArray(prenda.procesos);
        
        if (!procesosArray || procesosArray.length === 0) {
            console.log('ℹ️ [Procesos] Sin procesos para cargar');
            container.innerHTML = '';
            container.style.display = 'none';
            return;
        }
        
        // Mostrar procesos (fallback simple)
        container.innerHTML = '';
        container.style.display = 'block';
        
        procesosArray.forEach((proceso, idx) => {
            const tarjeta = this._crearTarjeta(proceso, idx);
            container.appendChild(tarjeta);
            console.log(` [Procesos] ${idx + 1}: ${proceso.nombre}`);
        });
        
        console.log(' [Procesos] Completado (modo fallback)');
    }

    /**
     * Convertir procesos a array
     * @private
     */
    static _convertirAArray(procesos) {
        // Si ya es un array, devolverlo
        if (Array.isArray(procesos)) {
            return procesos;
        }
        
        // Si es un objeto, convertirlo
        if (procesos && typeof procesos === 'object') {
            return Object.entries(procesos)
                .filter(([key, value]) => {
                    // Ignorar valores falsos
                    if (value === false || value === '' || value === null) return false;
                    return true;
                })
                .map(([nombre, detalles]) => {
                    // Si es un objeto con detalles, usarlo
                    if (typeof detalles === 'object') {
                        return { nombre, ...detalles };
                    }
                    // Si es un string/boolean, solo usar el nombre
                    return { nombre, tipo: nombre };
                });
        }
        
        return [];
    }

    /**
     * Crear tarjeta de proceso
     * @private
     */
    static _crearTarjeta(proceso, idx) {
        const tarjeta = document.createElement('div');
        tarjeta.className = 'proceso-tarjeta';
        tarjeta.style.cssText = 'background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;';
        
        const nombre = proceso.nombre || proceso.tipo || 'Proceso sin nombre';
        
        tarjeta.innerHTML = `
            <div>
                <strong>${nombre}</strong>
                ${proceso.detalles ? `<p style="color: #6b7280; margin: 0.5rem 0 0 0; font-size: 0.875rem;">${proceso.detalles}</p>` : ''}
            </div>
            <button type="button" class="btn btn-sm btn-danger" 
                onclick="eliminarProceso(${idx})"
                title="Eliminar proceso"
                style="flex-shrink: 0; margin-left: 1rem;">
                ✕
            </button>
        `;
        
        return tarjeta;
    }

    /**
     * Marcar checkboxes de procesos según los datos cargados
     * Mapea tipos de proceso (del BD) a IDs de checkbox (en el HTML)
     * @private
     */
    static _marcarCheckboxesProcesos(procesosSeleccionados) {
        if (!procesosSeleccionados || typeof procesosSeleccionados !== 'object') return;
        
        // Mapeo de nombres de proceso (BD) → ID de checkbox (HTML)
        const mapeoCheckbox = {
            'reflectivo': 'checkbox-reflectivo',
            'bordado': 'checkbox-bordado',
            'estampado': 'checkbox-estampado',
            'dtf': 'checkbox-dtf',
            'sublimado': 'checkbox-sublimado'
        };
        
        // Primero desmarcar todos
        Object.values(mapeoCheckbox).forEach(checkboxId => {
            const cb = document.getElementById(checkboxId);
            if (cb) cb.checked = false;
        });
        
        // Marcar los que existen en procesosSeleccionados
        Object.keys(procesosSeleccionados).forEach(tipoProceso => {
            const tipoLower = tipoProceso.toLowerCase().trim();
            const checkboxId = mapeoCheckbox[tipoLower];
            
            if (checkboxId) {
                const cb = document.getElementById(checkboxId);
                if (cb) {
                    // Usar _ignorarOnclick para evitar que el onclick reabra el modal de proceso
                    cb._ignorarOnclick = true;
                    cb.checked = true;
                    cb._ignorarOnclick = false;
                    console.log(` [Procesos] Checkbox '${checkboxId}' marcado para proceso '${tipoProceso}'`);
                }
            } else {
                console.log(`ℹ️ [Procesos] No hay checkbox mapeado para proceso '${tipoProceso}' (tipo: '${tipoLower}')`);
            }
        });
    }

    /**
     * Limpiar procesos
     * ⚠️ CRÍTICO: SOLO limpiar el contenedor de tarjetas (procesos configurados)
     * NO tocar el .procesos-container (que contiene los checkboxes)
     */
    static limpiar() {
        // 🔴 SOLO limpiar contenedor de tarjetas renderizadas
        // NO limpiar procesos-container (tiene los checkboxes para seleccionar procesos)
        const contenedorTarjetas = document.getElementById('contenedor-tarjetas-procesos');
        if (contenedorTarjetas) {
            contenedorTarjetas.innerHTML = '';
            contenedorTarjetas.style.display = 'none';
        }
        
        // Limpiar otros contenedores si existen
        const procesosAgregados = document.getElementById('procesos-agregados');
        if (procesosAgregados) {
            procesosAgregados.innerHTML = '';
            procesosAgregados.style.display = 'none';
        }
        
        // ⚠️ NUNCA tocar .procesos-container (contiene los checkboxes!)
        // const procesosContainer = document.querySelector('.procesos-container');
        // NO LIMPIAR - esto causa que desaparezcan los checkboxes
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorProcesos;
}
