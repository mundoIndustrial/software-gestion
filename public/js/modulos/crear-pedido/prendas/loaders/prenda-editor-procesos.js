/**
 *  Módulo de Procesos
 * Responsabilidad: Cargar y mostrar procesos (reflectivo, bordado, etc.)
 */

class PrendaEditorProcesos {
    /**
     * Cargar procesos en el modal
     */
    static cargar(prenda) {
        this._registrarInicioDebug(prenda);
        
        if (!this._validarProcesoExiste(prenda)) {
            return;
        }
        
        this._procesarYReplicarProcesos(prenda);
        this._autoAplicarTallasACotizacion(prenda);
        this._ejecutarRenderizacionYConfiguracion();
    }

    /**
     * Registrar inicio de carga para debugging
     * @private
     */
    static _registrarInicioDebug(prenda) {
        console.log(' [PROCESOS-LOADER] ===== INICIO CARGA =====');
        console.log(' [PROCESOS-LOADER] prenda.id:', prenda.id);
        console.log(' [PROCESOS-LOADER] prenda.procesos EXISTS:', !!prenda.procesos);
        console.log(' [PROCESOS-LOADER] prenda.procesos type:', typeof prenda.procesos);
        console.log(' [PROCESOS-LOADER] prenda.procesos isArray:', Array.isArray(prenda.procesos));
        console.log(' [PROCESOS-LOADER] prenda.procesos CONTENIDO COMPLETO:');
        console.log(prenda.procesos);
        console.log(' [Procesos] Cargando:', {
            cantidad: prenda.procesos?.length || Object.keys(prenda.procesos || {}).length || 0,
            tipo: Array.isArray(prenda.procesos) ? 'array' : typeof prenda.procesos,
            procesos: prenda.procesos
        });
    }

    /**
     * Validar que procesos existen
     * @private
     */
    static _validarProcesoExiste(prenda) {
        if (!prenda.procesos) {
            console.log(' [PROCESOS-LOADER] procesos es NULL/UNDEFINED');
            globalThis.procesosSeleccionados = {};
            return false;
        }
        if (Array.isArray(prenda.procesos)) {
            console.log(' [PROCESOS-LOADER] Es ARRAY con', prenda.procesos.length, 'elementos');
        }
        return true;
    }

    /**
     * Procesar y replicar procesos a globalThis
     * @private
     */
    static _procesarYReplicarProcesos(prenda) {
        if (!prenda.procesos || typeof prenda.procesos !== 'object') {
            return;
        }
        
        globalThis.procesosSeleccionados = {};
        
        if (Array.isArray(prenda.procesos)) {
            this._procesarProcesoDesdeArray(prenda.procesos);
        } else {
            this._procesarProcesoDesdeObjeto(prenda.procesos);
        }
        
        console.log('[Carga]  Procesos replicados en globalThis.procesosSeleccionados:', {
            keys: Object.keys(globalThis.procesosSeleccionados),
            count: Object.keys(globalThis.procesosSeleccionados).length,
            contenido: globalThis.procesosSeleccionados
        });
    }

    /**
     * Procesar procesos cuando vienen como array
     * @private
     */
    static _procesarProcesoDesdeArray(procesos) {
        procesos.forEach((proceso, idx) => {
            this._procesarYGuardarProceso(proceso, idx.toString());
        });
    }

    /**
     * Procesar procesos cuando vienen como objeto
     * @private
     */
    static _procesarProcesoDesdeObjeto(procesos) {
        Object.entries(procesos).forEach(([key, proceso]) => {
            if (proceso && typeof proceso === 'object' && (proceso.datos || proceso.tipo || proceso.ubicaciones)) {
                this._procesarYGuardarProceso(proceso, key);
            } else if (proceso === true || proceso === 1) {
                this._crearProcesoDesdeBandera(key);
            } else if (globalThis.procesosSeleccionados[key]) {
                // Reasignar con clave correcta
                const procesoActual = globalThis.procesosSeleccionados[key];
                delete globalThis.procesosSeleccionados[key];
                globalThis.procesosSeleccionados[procesoActual.tipo] = procesoActual;
            }
        });
    }

    /**
     * Procesar y guardar un proceso
     * @private
     */
    static _procesarYGuardarProceso(proceso, keyOIdx) {
        const tipo = this._extraerYNormalizarTipo(proceso, keyOIdx);
        const datosNormalizados = this._normalizarDatosProceso(proceso, tipo);
        
        globalThis.procesosSeleccionados[tipo] = {
            tipo: tipo,
            datos: datosNormalizados
        };
        
        this._registrarProcesoDebug(tipo, datosNormalizados);
    }

    /**
     * Extraer y normalizar tipo de proceso
     * @private
     */
    static _extraerYNormalizarTipo(proceso, keyOIdx) {
        const tipoOriginal = proceso.tipo 
            || proceso.nombre 
            || proceso.tipo_proceso
            || proceso.tipoProceso?.nombre
            || `proceso_${keyOIdx}`;
        
        return String(tipoOriginal)
            .toLowerCase()
            .trim()
            .replaceAll(/\s+/g, '-');
    }

    /**
     * Normalizar datos del proceso
     * @private
     */
    static _normalizarDatosProceso(proceso, tipo) {
        const datos = {
            ...proceso,
            tipo: tipo,
            modo_tallas: proceso?.modo_tallas || 'generico'
        };
        delete datos.modoTallas;
        
        if (datos.imagenes?.length > 0) {
            this._registrarImagenesDebug(tipo, datos.imagenes);
            datos.imagenes = this._normalizarImagenes(datos.imagenes);
        }
        
        return datos;
    }

    /**
     * Registrar información de imágenes para debugging
     * @private
     */
    static _registrarImagenesDebug(tipo, imagenes) {
        console.log(`[PROCESOS-LOADER]  Imágenes recibidas para ${tipo}:`, {
            cantidad: imagenes.length,
            primeraprimera: imagenes[0],
            tipo_primera: typeof imagenes[0],
            esObjeto: imagenes[0] instanceof Object
        });
    }

    /**
     * Normalizar array de imágenes agregando prefijo /storage/ si necesario
     * @private
     */
    static _normalizarImagenes(imagenes) {
        return imagenes.map(img => {
            if (typeof img === 'string') {
                if (this._esUrlAbsoluta(img)) return img;
                return '/storage/' + img;
            }
            
            if (typeof img === 'object' && img !== null) {
                if (img.ruta_webp) img.ruta_webp = this._normalizarRutaImagen(img.ruta_webp);
                if (img.ruta_original) img.ruta_original = this._normalizarRutaImagen(img.ruta_original);
                if (img.url) img.url = this._normalizarRutaImagen(img.url);
            }
            return img;
        });
    }

    /**
     * Verificar si URL es absoluta (comienza con /, http, blob:, data:)
     * @private
     */
    static _esUrlAbsoluta(url) {
        return url.startsWith('/') || url.startsWith('http') || url.startsWith('blob:') || url.startsWith('data:');
    }

    /**
     * Normalizar ruta individual de imagen
     * @private
     */
    static _normalizarRutaImagen(ruta) {
        if (!ruta || this._esUrlAbsoluta(ruta)) return ruta;
        return '/storage/' + ruta;
    }

    /**
     * Registrar información del proceso para debugging
     * @private
     */
    static _registrarProcesoDebug(tipo, datos) {
        console.log(`[PROCESOS-LOADER]  Proceso "${tipo}" cargado:`, {
            tipo: tipo,
            procesoId: datos.id,
            modo_tallas_desde_servidor: datos.modo_tallas,
            modo_tallas_en_globalThis: globalThis.procesosSeleccionados[tipo].datos.modo_tallas,
            tipoProcesoNested: datos.tipoProceso?.nombre,
            datosKeys: Object.keys(datos).slice(0, 15),
            estructuraCompleta: {
                datosNormalizados: datos,
                globalThisDatos: globalThis.procesosSeleccionados[tipo]?.datos
            }
        });
        
        if (datos.datosExtendidos) {
            console.log(`[PROCESOS-LOADER]datosExtendidos para ${tipo}:`, {
                tiene: true,
                estructura: Object.keys(datos.datosExtendidos),
                contenido: datos.datosExtendidos
            });
        }
    }

    /**
     * Crear proceso desde bandera booleana
     * @private
     */
    static _crearProcesoDesdeBandera(key) {
        const tipo = String(key).toLowerCase().trim().replaceAll(/\s+/g, '-');
        globalThis.procesosSeleccionados[tipo] = {
            tipo: tipo,
            datos: {
                tipo: tipo,
                modo_tallas: 'generico',
                ubicaciones: [],
                tallas: { dama: {}, caballero: {}, sobremedida: {} },
                observaciones: '',
                imagenes: []
            }
        };
    }
    /**
     * Auto-aplicar tallas a procesos de cotización si están vacías
     * @private
     */
    static _autoAplicarTallasACotizacion(prenda) {
        const tallasRelacionales = globalThis.tallasRelacionales || {};
        const hayTallasEnPrenda = Object.keys(tallasRelacionales).length > 0;
        
        if (!hayTallasEnPrenda || prenda.tipo !== 'cotizacion') {
            return;
        }
        
        console.log('[Procesos]  Cotización detectada - auto-aplicando tallas a procesos sin tallas');
        
        const tallasParaProceso = {
            dama: tallasRelacionales.DAMA ? { ...tallasRelacionales.DAMA } : {},
            caballero: tallasRelacionales.CABALLERO ? { ...tallasRelacionales.CABALLERO } : {},
            sobremedida: tallasRelacionales.SOBREMEDIDA ? { ...tallasRelacionales.SOBREMEDIDA } : {}
        };
        
        Object.entries(globalThis.procesosSeleccionados).forEach(([key, proceso]) => {
            this._aplicarTallasAlProceso(key, proceso.datos, tallasParaProceso);
        });
    }

    /**
     * Aplicar tallas a un proceso si están vacías
     * @private
     */
    static _aplicarTallasAlProceso(key, datos, tallasParaProceso) {
        const tallasVacias = !datos.tallas || 
            (Object.keys(datos.tallas?.dama || {}).length === 0 && 
             Object.keys(datos.tallas?.caballero || {}).length === 0 &&
             Object.keys(datos.tallas?.sobremedida || {}).length === 0);
        const tallaCantidadVacia = !datos.talla_cantidad || 
            (Array.isArray(datos.talla_cantidad) && datos.talla_cantidad.length === 0) ||
            (typeof datos.talla_cantidad === 'object' && Object.keys(datos.talla_cantidad).length === 0);
        
        if (tallasVacias && tallaCantidadVacia) {
            datos.tallas = structuredClone(tallasParaProceso);
            datos._aplicarTodasTallas = true;
            console.log(`[Procesos]  ${key}: tallas auto-asignadas (todas las de la prenda)`, datos.tallas);
        }
    }

    /**
     * Ejecutar renderización y configuración de procesos
     * @private
     */
    static _ejecutarRenderizacionYConfiguracion() {
        if (!globalThis.renderizarTarjetasProcesos) {
            this._ejecutarFallback();
            return;
        }
        
        console.log(' [Procesos] Función renderizarTarjetasProcesos() disponible');
        console.log('[Procesos]  globalThis.procesosSeleccionados actual:', globalThis.procesosSeleccionados);
        console.log('[Procesos] Ejecutando renderización AHORA...');
        
        const exito = globalThis.renderizarTarjetasProcesos();
        
        if (!this._registrarYVerificarRenderizacion(exito)) {
            this._ejecutarFallback();
            return;
        }
        
        this._marcarCheckboxesProcesos(globalThis.procesosSeleccionados);
        this._configurarDragDropYVerificar();
    }

    /**
     * Registrar y verificar resultado de renderización
     * @private
     */
    static _registrarYVerificarRenderizacion(exito) {
        const container = document.getElementById('contenedor-tarjetas-procesos');
        console.log('[Procesos] Resultado renderización:', {
            exito: exito,
            container: container,
            containerDisplay: container?.style.display,
            containerHTML: container?.innerHTML.substring(0, 100)
        });
        return exito;
    }

    /**
     * Configurar drag & drop y verificar
     * @private
     */
    static _configurarDragDropYVerificar() {
        console.log('[PROCESOS-LOADER]  Verificando configurarDragDropProcesos');
        console.log('[PROCESOS-LOADER]Timestamp:', new Date().toISOString());
        console.log('[PROCESOS-LOADER]  Llamando a configurarDragDropProcesos');
        
        if (typeof configurarDragDropProcesos === 'function') {
            console.log('[PROCESOS-LOADER]  Llamando a configurarDragDropProcesos desde loader');
            configurarDragDropProcesos();
            console.log('[PROCESOS-LOADER]  configurarDragDropProcesos ejecutado');
        } else {
            console.warn('[PROCESOS-LOADER]  configurarDragDropProcesos no disponible');
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
        
        console.log(' [Procesos] Completado - Tarjetas renderizadas correctamente');
    }

    /**
     * Ejecutar modo fallback si renderizador no está disponible
     * @private
     */
    static _ejecutarFallback() {
        console.warn(' [Procesos] renderizarTarjetasProcesos() no disponible o retornó false');
        
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
        
        const procesosArray = this._convertirAArray(globalThis.procesosSeleccionados);
        
        if (!procesosArray || procesosArray.length === 0) {
            console.log(' [Procesos] Sin procesos para cargar');
            container.innerHTML = '';
            container.style.display = 'none';
            return;
        }
        
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
                .filter(([key, value]) => value !== false && value !== '' && value !== null)
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
                console.log(` [Procesos] No hay checkbox mapeado para proceso '${tipoProceso}' (tipo: '${tipoLower}')`);
            }
        });
    }

    /**
     * Limpiar procesos
     *  CRÍTICO: SOLO limpiar el contenedor de tarjetas (procesos configurados)
     * NO tocar el .procesos-container (que contiene los checkboxes)
     */
    static limpiar() {
        //  SOLO limpiar contenedor de tarjetas renderizadas
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
        
        //  NUNCA tocar .procesos-container (contiene los checkboxes!)
        // const procesosContainer = document.querySelector('.procesos-container');
        // NO LIMPIAR - esto causa que desaparezcan los checkboxes
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorProcesos;
}
