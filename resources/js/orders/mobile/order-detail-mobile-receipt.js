window.llenarReciboCosturaMobile = function(data) {
    console.log('📱 [RECIBO MOBILE]  ========== INICIANDO llenarReciboCosturaMobile ==========');
    console.log('📱 [RECIBO MOBILE] Datos recibidos:', data);
    console.log('📱 [RECIBO MOBILE] procesoCarouselIndex ACTUAL:', window.procesoCarouselIndex);
    console.log('📱 [RECIBO MOBILE] todosProcesosDisponibles ACTUAL:', window.todosProcesosDisponibles);

    const tipoReciboDataset = document.getElementById('factura-container-mobile')?.getAttribute('data-tipo-recibo') || '';
    const tipoReciboUpper = (tipoReciboDataset || '').toString().trim().toUpperCase();
    const urlParams = new URLSearchParams(window.location.search);
    const consecutivoParcialParam = String(urlParams.get('consecutivo_parcial') || '').trim();
    const pedidoParcialIdParam = String(
        urlParams.get('pedido_parcial_id') || urlParams.get('parcial_id') || ''
    ).trim();
    const esReciboParcial = pedidoParcialIdParam !== '' || consecutivoParcialParam !== '' || tipoReciboUpper === 'PARCIAL';

    // Unificar con la fuente que usa recibos-reflectivo:
    // hidratar datos del parcial desde /api/recibos-parciales/{id} antes de renderizar.
    if (esReciboParcial && pedidoParcialIdParam !== '' && !data?._parcialHydratedOperario) {
        const parcialIdNum = Number(pedidoParcialIdParam);
        if (Number.isFinite(parcialIdNum) && parcialIdNum > 0) {
            data._parcialHydratedOperario = true;
            window.OrderDetailMobileService.getReciboParcial(parcialIdNum)
            .then(json => {
                if (!json?.success || !json?.data) {
                    window.llenarReciboCosturaMobile(data);
                    return;
                }

                const tallasParcial = Array.isArray(json.data.tallas) ? json.data.tallas : [];
                const tallaColoresParcial = tallasParcial
                    .filter(t => (t?.color_nombre || '').toString().trim() !== '')
                    .map(t => ({
                        genero: (t?.genero || 'CABALLERO').toString().toUpperCase(),
                        talla: (t?.talla || '').toString().toUpperCase(),
                        color_nombre: (t?.color_nombre || '').toString().trim(),
                        cantidad: parseInt(t?.cantidad || 0, 10) || 0,
                    }))
                    .filter(t => t.talla !== '' && t.cantidad > 0);

                if (Array.isArray(data?.prendas)) {
                    data.prendas = data.prendas.map((prenda) => {
                        const prendaParcialId = String(
                            prenda?.recibos?.PARCIAL?.pedido_parcial_id ||
                            prenda?.recibos?.PARCIAL?.id ||
                            prenda?.procesos?.[0]?.pedido_parcial_id ||
                            ''
                        ).trim();

                        if (prendaParcialId !== pedidoParcialIdParam) return prenda;

                        if (tallasParcial.length > 0) {
                            prenda.tallas = tallasParcial;
                        }

                        if (tallaColoresParcial.length > 0) {
                            prenda.talla_colores = tallaColoresParcial;
                        }

                        if (Array.isArray(prenda.procesos)) {
                            prenda.procesos = prenda.procesos.map((proc) => {
                                const procParcialId = String(proc?.pedido_parcial_id || proc?.id || '').trim();
                                if (procParcialId !== pedidoParcialIdParam && !proc?.es_parcial) return proc;
                                if (tallaColoresParcial.length > 0) {
                                    proc.talla_colores = tallaColoresParcial;
                                }
                                if (json?.data?.tallas_formato_colores && typeof json.data.tallas_formato_colores === 'object') {
                                    proc.tallas = json.data.tallas_formato_colores;
                                }
                                return proc;
                            });
                        }

                        if (prenda.recibos && typeof prenda.recibos === 'object') {
                            const keys = Object.keys(prenda.recibos);
                            keys.forEach((k) => {
                                const rec = prenda.recibos[k];
                                if (!rec || typeof rec !== 'object') return;
                                const recParcialId = String(rec?.pedido_parcial_id || rec?.id || '').trim();
                                if (recParcialId !== pedidoParcialIdParam) return;
                                if (tallasParcial.length > 0) rec.tallas = tallasParcial;
                                if (tallaColoresParcial.length > 0) rec.talla_colores = tallaColoresParcial;
                                if (json?.data?.tallas_formato_colores && typeof json.data.tallas_formato_colores === 'object') {
                                    rec.tallas_estructura = json.data.tallas_formato_colores;
                                }
                            });
                        }

                        return prenda;
                    });
                }

                window.llenarReciboCosturaMobile(data);
            })
            .catch(() => {
                window.llenarReciboCosturaMobile(data);
            });
            return;
        }
    }

    const fmt = window.OrderDetailMobileFormatters || {};
    const normalizarTituloRecibo = fmt.normalizarTituloRecibo;
    const normalizarUbicaciones = fmt.normalizarUbicaciones;
    const transformarVariantesAEstructura = fmt.transformarVariantesAEstructura;
    const derivarTallaColoresDesdeVariantes = fmt.derivarTallaColoresDesdeVariantes;
    const transformarTallaColoresAEstructura = fmt.transformarTallaColoresAEstructura;
    const transformarTallasListaParcialAEstructura = fmt.transformarTallasListaParcialAEstructura;

    if (
        typeof normalizarTituloRecibo !== 'function' ||
        typeof normalizarUbicaciones !== 'function' ||
        typeof transformarVariantesAEstructura !== 'function' ||
        typeof derivarTallaColoresDesdeVariantes !== 'function' ||
        typeof transformarTallaColoresAEstructura !== 'function' ||
        typeof transformarTallasListaParcialAEstructura !== 'function'
    ) {
        console.error('[RECIBO MOBILE] Formatters no disponibles');
        return;
    }

    const receiptTitleEl = document.getElementById('receipt-title-mobile');
    if (receiptTitleEl) {
        const fallbackTitulo = String(window.procesoActualSeleccionado || '').trim().toUpperCase();
        const tituloRecibo = normalizarTituloRecibo(tipoReciboUpper, fallbackTitulo);
        receiptTitleEl.textContent = `RECIBO DE ${tituloRecibo}`;
    }

    // ===== NAVEGACIÓN DE PROCESOS =====
    // Inicializar índice de proceso si no existe
    if (!window.procesoCarouselIndex) {
        window.procesoCarouselIndex = 0;
    }
    
    console.log('📱 [RECIBO MOBILE] Índice de proceso actual (window.procesoCarouselIndex):', window.procesoCarouselIndex);
    
    // Obtener lista de procesos únicos del pedido
    // Buscar en recibos primero, luego en procesos
    const todosProcesos = [];
    const userRole = document.getElementById('factura-container-mobile')?.getAttribute('data-user-role');
    const esVistaControlCalidad = (window.location?.pathname || '').toString().includes('/control-calidad/');
    const esRolControlCalidad = (userRole || '').toString().trim().toLowerCase() === 'control de calidad';
    const disableNavigation = esVistaControlCalidad || esRolControlCalidad;

    // En Control de Calidad no se necesita navegación de procesos/prendas
    if (disableNavigation) {
        const processNavContainer = document.getElementById('process-navigation-mobile');
        if (processNavContainer) {
            processNavContainer.style.display = 'none';
            processNavContainer.innerHTML = '';
        }
        
        // IMPORTANTE: Aún sin navegación, debemos guardar los procesos disponibles
        // para que el filtrado de prendas funcione correctamente con el tipo_recibo seleccionado
        const tieneCostu = todosProcesos.some((proceso) => String(proceso).trim().toUpperCase() === 'COSTURA');
        const tieneReflectivo = todosProcesos.some((proceso) => String(proceso).trim().toUpperCase() === 'REFLECTIVO');
        let procesosCC = [];
        if (tieneCostu) procesosCC.push('COSTURA');
        if (tieneReflectivo) procesosCC.push('REFLECTIVO');
        if (procesosCC.length === 0) procesosCC = todosProcesos;
        
        window.todosProcesosDisponibles = procesosCC;
        
        // Determinar procesoCarouselIndex basado en tipo_recibo de la URL
        const tipoReciboCC = tipoReciboUpper;
        if (tipoReciboCC && procesosCC.includes(tipoReciboCC)) {
            window.procesoCarouselIndex = procesosCC.indexOf(tipoReciboCC);
            window.procesoActualSeleccionado = tipoReciboCC;
            console.log('📱 [CONTROL CALIDAD] procesoCarouselIndex fijado a', window.procesoCarouselIndex, 'para tipo_recibo:', tipoReciboCC);
        } else {
            window.procesoActualSeleccionado = procesosCC[window.procesoCarouselIndex || 0] || null;
        }
        console.log('📱 [CONTROL CALIDAD] todosProcesosDisponibles:', window.todosProcesosDisponibles);
        console.log('📱 [CONTROL CALIDAD] procesoActualSeleccionado:', window.procesoActualSeleccionado);
    }
    
    if (data.prendas && Array.isArray(data.prendas)) {
        data.prendas.forEach(function(prenda) {
            // Opción 1: Usar recibos (si existen)
            if (prenda.recibos && typeof prenda.recibos === 'object') {
                Object.keys(prenda.recibos).forEach(function(proceso) {
                    // Solo agregar si tiene valor (no es null)
                    if (prenda.recibos[proceso] !== null && !todosProcesos.includes(proceso)) {
                        todosProcesos.push(proceso);
                    }
                });
            }
            // Opción 2: Usar procesos (fallback)
            // IMPORTANTE: algunos payloads usan tipo_proceso / nombre_proceso (ej: anexos)
            if (prenda.procesos && Array.isArray(prenda.procesos)) {
                prenda.procesos.forEach(function(proceso) {
                    const tipoProc = (proceso.proceso || proceso.tipo_proceso || proceso.nombre_proceso || '').toString().trim();
                    if (tipoProc && !todosProcesos.includes(tipoProc)) {
                        todosProcesos.push(tipoProc);
                    }
                });
            }
        });
    }
    
    // Filtrar procesos según el rol del usuario y la vista actual
    let procesosFiltrados = todosProcesos;
    const esVistaOperario = (window.location?.pathname || '').toString().includes('/operario/');
    const resolverProcesoRealParcial = () => {
        let procesoParcialReal = null;
        const parcialIdParam = String(new URLSearchParams(window.location.search).get('parcial_id') || '').trim();

        if (Array.isArray(data?.prendas)) {
            for (const prenda of data.prendas) {
                if (prenda?.recibos && typeof prenda.recibos === 'object' && !Array.isArray(prenda.recibos)) {
                    for (const [key, reciboVal] of Object.entries(prenda.recibos)) {
                        if (!reciboVal || typeof reciboVal !== 'object') continue;
                        const keyUpper = String(key || '').trim().toUpperCase();
                        if (keyUpper === 'PARCIAL') continue;

                        const parcialInterno = String(reciboVal.pedido_parcial_id || reciboVal.parcial_id || reciboVal.id || '').trim();
                        const coincideParcial = parcialIdParam !== '' && parcialInterno === parcialIdParam;
                        const tipoInterno = String(reciboVal.tipo_recibo || keyUpper || '').trim().toUpperCase();

                        if (coincideParcial && tipoInterno) {
                            procesoParcialReal = tipoInterno;
                            break;
                        }
                    }
                }

                if (procesoParcialReal) break;

                if (Array.isArray(prenda?.procesos)) {
                    for (const proc of prenda.procesos) {
                        const procTipo = String(proc?.proceso || proc?.tipo_proceso || proc?.nombre_proceso || '').trim().toUpperCase();
                        const parcialProceso = String(proc?.pedido_parcial_id || proc?.parcial_id || '').trim();
                        const coincideParcial = parcialIdParam !== '' && parcialProceso === parcialIdParam;
                        if (procTipo && procTipo !== 'PARCIAL' && (coincideParcial || !!proc?.es_parcial)) {
                            procesoParcialReal = procTipo;
                            break;
                        }
                    }
                }

                if (procesoParcialReal) break;
            }
        }

        return procesoParcialReal;
    };

    console.log(' [FILTRO PROCESOS] Rol del usuario:', userRole);
    console.log(' [FILTRO PROCESOS] Es vista operario:', esVistaOperario);
    console.log(' [FILTRO PROCESOS] Todos los procesos encontrados:', todosProcesos);
    
    if (userRole === 'costura-reflectivo' || userRole === 'vista-costura' || userRole === 'lider-reflectivo') {
        // Para costura-reflectivo, lider-reflectivo y vista-costura, mostrar COSTURA y REFLECTIVO en ese orden
        const tieneCostu = todosProcesos.includes('COSTURA');
        const tieneReflectivo = todosProcesos.includes('REFLECTIVO');
        procesosFiltrados = [];

        if (tipoReciboUpper === 'PARCIAL') {
            const procesoParcialReal = resolverProcesoRealParcial();
            if (procesoParcialReal && todosProcesos.includes(procesoParcialReal)) {
                procesosFiltrados = [procesoParcialReal];
                window.procesoCarouselIndex = 0;
                window.procesoActualSeleccionado = procesoParcialReal;
                console.log(' [FILTRO PROCESOS] Vista costura/reflectivo - tipo_recibo=PARCIAL mapeado a proceso:', procesoParcialReal);
            }
        }

        if (procesosFiltrados.length === 0) {
            if (tieneCostu) procesosFiltrados.push('COSTURA');
            if (tieneReflectivo) procesosFiltrados.push('REFLECTIVO');
        }
        
        console.log(' [FILTRO PROCESOS] tieneCostu:', tieneCostu);
        console.log(' [FILTRO PROCESOS] tieneReflectivo:', tieneReflectivo);
        
        // Si se solicita un tipo_recibo específico, ajustar el índice
        if (tipoReciboUpper === 'REFLECTIVO' && tieneReflectivo) {
            window.procesoCarouselIndex = procesosFiltrados.indexOf('REFLECTIVO');
            window.procesoActualSeleccionado = 'REFLECTIVO';
            console.log(' [FILTRO PROCESOS] Ajustando índice a REFLECTIVO:', window.procesoCarouselIndex);
        } else if (tipoReciboUpper === 'COSTURA' && tieneCostu) {
            window.procesoCarouselIndex = procesosFiltrados.indexOf('COSTURA');
            window.procesoActualSeleccionado = 'COSTURA';
            console.log(' [FILTRO PROCESOS] Ajustando índice a COSTURA:', window.procesoCarouselIndex);
        }
    } else if (esVistaControlCalidad || esRolControlCalidad) {
        // Para control de calidad: mostrar COSTURA y REFLECTIVO (mismos tipos que costura-reflectivo)
        const tieneCostu = todosProcesos.includes('COSTURA');
        const tieneReflectivo = todosProcesos.includes('REFLECTIVO');
        procesosFiltrados = [];
        if (tieneCostu) procesosFiltrados.push('COSTURA');
        if (tieneReflectivo) procesosFiltrados.push('REFLECTIVO');
        
        // Si el tipoRecibo es REFLECTIVO, ajustar el índice para mostrar ese proceso
        if (tipoReciboUpper === 'REFLECTIVO' && tieneReflectivo) {
            window.procesoCarouselIndex = procesosFiltrados.indexOf('REFLECTIVO');
            console.log(' [FILTRO PROCESOS] Control Calidad: Ajustando índice a REFLECTIVO:', window.procesoCarouselIndex);
        } else if (tipoReciboUpper === 'COSTURA' && tieneCostu) {
            window.procesoCarouselIndex = procesosFiltrados.indexOf('COSTURA');
        }
        
        console.log(' [FILTRO PROCESOS] Control Calidad - tieneCostu:', tieneCostu, 'tieneReflectivo:', tieneReflectivo);
    } else if (esVistaOperario) {
        // Vista operario:
        // - Por defecto mostrar COSTURA/COSTURA-BODEGA
        // - PERO si viene tipo_recibo en URL y existe en los procesos disponibles, mostrar ese tipo solicitado
        // - Caso especial: tipo_recibo=PARCIAL no es un proceso real, se debe mapear al tipo de proceso del parcial
        const tieneCostu = todosProcesos.includes('COSTURA');
        const tieneCosturaBodega = todosProcesos.includes('COSTURA-BODEGA');

        procesosFiltrados = [];
        if (tieneCostu) procesosFiltrados.push('COSTURA');
        if (tieneCosturaBodega) procesosFiltrados.push('COSTURA-BODEGA');

        if (tipoReciboUpper === 'PARCIAL') {
            // Resolver dinámicamente el proceso real del parcial (puede ser COSTURA, REFLECTIVO, etc.)
            let procesoParcialReal = null;
            const parcialIdParam = String(new URLSearchParams(window.location.search).get('parcial_id') || '').trim();

            if (Array.isArray(data?.prendas)) {
                for (const prenda of data.prendas) {
                    // Prioridad 1: recibos[key] que coincidan con el parcial_id y no sean la llave PARCIAL
                    if (prenda?.recibos && typeof prenda.recibos === 'object' && !Array.isArray(prenda.recibos)) {
                        for (const [key, reciboVal] of Object.entries(prenda.recibos)) {
                            if (!reciboVal || typeof reciboVal !== 'object') continue;
                            const keyUpper = String(key || '').trim().toUpperCase();
                            if (keyUpper === 'PARCIAL') continue;

                            const parcialInterno = String(reciboVal.pedido_parcial_id || reciboVal.parcial_id || reciboVal.id || '').trim();
                            const coincideParcial = parcialIdParam !== '' && parcialInterno === parcialIdParam;
                            const tipoInterno = String(reciboVal.tipo_recibo || keyUpper || '').trim().toUpperCase();

                            if (coincideParcial && tipoInterno) {
                                procesoParcialReal = tipoInterno;
                                break;
                            }
                        }
                    }

                    if (procesoParcialReal) break;

                    // Prioridad 2: proceso parcial dentro de prenda.procesos
                    if (Array.isArray(prenda?.procesos)) {
                        for (const proc of prenda.procesos) {
                            const procTipo = String(proc?.proceso || proc?.tipo_proceso || proc?.nombre_proceso || '').trim().toUpperCase();
                            const parcialProceso = String(proc?.pedido_parcial_id || proc?.parcial_id || '').trim();
                            const coincideParcial = parcialIdParam !== '' && parcialProceso === parcialIdParam;
                            if (procTipo && procTipo !== 'PARCIAL' && (coincideParcial || !!proc?.es_parcial)) {
                                procesoParcialReal = procTipo;
                                break;
                            }
                        }
                    }

                    if (procesoParcialReal) break;
                }
            }

            if (procesoParcialReal && todosProcesos.includes(procesoParcialReal)) {
                procesosFiltrados = [procesoParcialReal];
            } else if (todosProcesos.includes('REFLECTIVO')) {
                procesosFiltrados = ['REFLECTIVO'];
            } else if (tieneCostu) {
                procesosFiltrados = ['COSTURA'];
            } else if (tieneCosturaBodega) {
                procesosFiltrados = ['COSTURA-BODEGA'];
            } else if (todosProcesos.length > 0) {
                procesosFiltrados = [todosProcesos[0]];
            }
            window.procesoCarouselIndex = 0;
            window.procesoActualSeleccionado = procesosFiltrados[0] || null;
            console.log(' [FILTRO PROCESOS] Vista operario - tipo_recibo=PARCIAL mapeado a proceso:', window.procesoActualSeleccionado);
        } else if (tipoReciboUpper && todosProcesos.includes(tipoReciboUpper)) {
            procesosFiltrados = [tipoReciboUpper];
            window.procesoCarouselIndex = 0;
            window.procesoActualSeleccionado = tipoReciboUpper;
            console.log(' [FILTRO PROCESOS] Vista operario - mostrando tipo_recibo solicitado:', tipoReciboUpper);
            console.log(' [FILTRO PROCESOS] tipoReciboUpper:', tipoReciboUpper, 'está en todosProcesos:', todosProcesos);
        } else {
            console.log(' [FILTRO PROCESOS] Vista operario - por defecto COSTURA:', tieneCostu, 'COSTURA-BODEGA:', tieneCosturaBodega);
            console.log(' [FILTRO PROCESOS] tipoReciboUpper:', tipoReciboUpper, 'NO está en todosProcesos:', todosProcesos);
            console.log(' [FILTRO PROCESOS] tipoReciboUpper es truthy?:', !!tipoReciboUpper);
            console.log(' [FILTRO PROCESOS] todosProcesos.includes(tipoReciboUpper)?:', todosProcesos.includes(tipoReciboUpper));
        }
    }
    
    console.log(' [FILTRO PROCESOS] Procesos filtrados FINAL:', procesosFiltrados);
    console.log(' [FILTRO PROCESOS] Índice actual (procesoCarouselIndex):', window.procesoCarouselIndex);
    console.log(' [FILTRO PROCESOS] Proceso que se debe mostrar:', procesosFiltrados[window.procesoCarouselIndex || 0]);
    
    // Verificar si se pasó recibo_id en la URL (significa que se abrió un recibo específico)
    const reciboIdParam = urlParams.get('recibo_id');
    const tieneReciboIdEspecifico = reciboIdParam !== null && reciboIdParam !== '';
    
    // Mostrar navegación de procesos si hay al menos 2 procesos Y no se pasó recibo_id específico
    if (!disableNavigation && procesosFiltrados.length >= 2 && !tieneReciboIdEspecifico) {
        const processNavContainer = document.getElementById('process-navigation-mobile');
        if (processNavContainer) {
            processNavContainer.innerHTML = '';
            processNavContainer.style.display = 'flex';
            processNavContainer.style.justifyContent = 'center';
            processNavContainer.style.alignItems = 'center';
            processNavContainer.style.gap = '8px';
            processNavContainer.style.flexDirection = 'row';
            
            const procesoActualIndex = window.procesoCarouselIndex || 0;
            const procesoActual = procesosFiltrados[procesoActualIndex] || '';
            
            console.log('📱 [NAVEGACION] procesoActualIndex:', procesoActualIndex);
            console.log('📱 [NAVEGACION] procesoActual:', procesoActual);
            console.log('📱 [NAVEGACION] procesosFiltrados.length:', procesosFiltrados.length);
            
            // Botón anterior de procesos
            if (procesoActualIndex > 0) {
                const prevProcBtn = document.createElement('button');
                prevProcBtn.style.background = '#EF5350';
                prevProcBtn.style.border = 'none';
                prevProcBtn.style.color = 'white';
                prevProcBtn.style.cursor = 'pointer';
                prevProcBtn.style.padding = '6px 8px';
                prevProcBtn.style.borderRadius = '4px';
                prevProcBtn.style.fontSize = '12px';
                prevProcBtn.style.fontWeight = '600';
                prevProcBtn.style.transition = 'all 0.2s ease';
                prevProcBtn.title = 'Proceso anterior';
                prevProcBtn.innerHTML = '<span style="font-size: 16px;">◀</span>';
                prevProcBtn.onmouseover = function() {
                    this.style.transform = 'scale(1.1)';
                    this.style.boxShadow = '0 2px 8px rgba(239, 83, 80, 0.3)';
                };
                prevProcBtn.onmouseout = function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                };
                prevProcBtn.onclick = function() {
                    console.log('🔘 [CLICK BOTÓN] ANTERIOR presionado');
                    window.procesoCarouselIndex = Math.max(0, window.procesoCarouselIndex - 1);
                    const nuevoProceso = procesosFiltrados[window.procesoCarouselIndex];
                    console.log('🔘 [CLICK BOTÓN] Nuevo índice:', window.procesoCarouselIndex);
                    console.log('🔘 [CLICK BOTÓN] Nuevo proceso:', nuevoProceso);
                    // Recargar datos dinámicamente para el nuevo proceso
                    cargarReciboDinamico(data.pedido_id, nuevoProceso);
                };
                processNavContainer.appendChild(prevProcBtn);
            }
            
            // Guardar procesos en variable global para usar en filtrado posterior
            window.todosProcesosDisponibles = procesosFiltrados;
            window.procesoActualSeleccionado = procesoActual;
        }
    } else {
        const processNavContainer = document.getElementById('process-navigation-mobile');
        if (processNavContainer) {
            processNavContainer.style.display = 'none';
        }
        // Aún sin navegación visible, guardar procesos para filtrado
        window.todosProcesosDisponibles = procesosFiltrados;
        window.procesoActualSeleccionado = procesosFiltrados[window.procesoCarouselIndex || 0] || null;
    }
    
    // Fecha - parsear correctamente
    if (data.fecha && data.fecha !== 'N/A') {
        let fecha;
        
        // Intentar parsear diferentes formatos de fecha
        if (typeof data.fecha === 'string') {
            // Formato DD/MM/YYYY
            if (data.fecha.includes('/')) {
                const [day, month, year] = data.fecha.split('/');
                fecha = new Date(year, parseInt(month) - 1, day);
            }
            // Formato YYYY-MM-DD o YYYY-MM-DD HH:MM:SS
            else if (data.fecha.includes('-')) {
                // Separar fecha de hora si existe
                const fechaParte = data.fecha.split(' ')[0];
                const [year, month, day] = fechaParte.split('-');
                fecha = new Date(year, parseInt(month) - 1, parseInt(day));
            } else {
                fecha = new Date(data.fecha);
            }
        } else {
            fecha = new Date(data.fecha);
        }
        
        // Validar que sea una fecha válida
        if (!isNaN(fecha)) {
            const dayBox = document.getElementById('fecha-dia');
            const monthBox = document.getElementById('fecha-mes');
            const yearBox = document.getElementById('fecha-year');
            if (dayBox) {
                dayBox.textContent = fecha.getDate();
                console.log(' Día actualizado:', fecha.getDate());
            }
            if (monthBox) {
                monthBox.textContent = (fecha.getMonth() + 1);
                console.log(' Mes actualizado:', fecha.getMonth() + 1);
            }
            if (yearBox) {
                yearBox.textContent = fecha.getFullYear();
                console.log(' Año actualizado:', fecha.getFullYear());
            }
        } else {
        }
    } else {
    }

    // Información básica
    const asesora = document.getElementById('mobile-asesora');
    const formaPago = document.getElementById('mobile-forma-pago');
    const cliente = document.getElementById('mobile-cliente');
    const numeroPedido = document.getElementById('mobile-numero-pedido');
    const encargado = document.getElementById('mobile-encargado');
    const prendasEntregadas = document.getElementById('mobile-prendas-entregadas');
    if (asesora) asesora.textContent = data.asesora || 'N/A';
    if (formaPago) formaPago.textContent = data.formaPago || 'N/A';
    if (cliente) cliente.textContent = data.cliente || 'N/A';
    if (numeroPedido) numeroPedido.textContent = '#' + (data.numeroPedido || '');
    
    // Ocultar campos innecesarios para recibos de bodega
    const tipoReciboEsBodega = tipoReciboUpper === 'CORTE-PARA-BODEGA';
    const tipoParcialReal = tipoReciboUpper === 'PARCIAL' ? resolverProcesoRealParcial() : '';
    const parcialEsBodega = String(tipoParcialReal || '').toUpperCase() === 'CORTE-PARA-BODEGA';
    const isBodega = (
        (data.cliente === 'SERVICIO' && data.asesor === 'SISTEMA') ||
        data.asesora === 'SISTEMA' ||
        tipoReciboEsBodega ||
        parcialEsBodega
    );
    if (document.getElementById('order-asesora')) document.getElementById('order-asesora').style.display = isBodega ? 'none' : 'block';
    if (document.getElementById('order-forma-pago')) document.getElementById('order-forma-pago').style.display = isBodega ? 'none' : 'block';
    if (document.getElementById('order-cliente')) document.getElementById('order-cliente').style.display = isBodega ? 'none' : 'block';
    
    // VALIDAR CONDICIONES PARA MOSTRAR ENCARGADO
    // Solo mostrar si:
    // 1. Area es "costura"
    // 2. Estado es "En Ejecución"
    // 3. Usuario tiene rol "costura" o "costura-reflectivo"
    // 4. El proceso tiene encargado (nombre de usuario)
    const mostraEncargado = data.area && 
                            data.area.toLowerCase() === 'costura' && 
                            data.estado === 'En Ejecución' && 
                            (userRole === 'costura' || userRole === 'costura-reflectivo') && 
                            data.encargado && 
                            data.encargado.trim() !== '' && 
                            data.encargado !== '-' && 
                            data.encargado !== 'Operario';
    
    if (encargado) {
        if (mostraEncargado) {
            encargado.textContent = data.encargado;
            console.log('📱 [ENCARGADO]  Mostrando encargado:', data.encargado);
        } else {
            encargado.textContent = '-';
            console.log('📱 [ENCARGADO]  No aplican las condiciones para mostrar encargado', {
                area: data.area,
                estado: data.estado,
                userRole: userRole,
                encargado: data.encargado
            });
        }
    }
    if (prendasEntregadas) prendasEntregadas.textContent = data.prendasEntregadas || '0/0';
    
    // ===== ANCHO Y METRAJE =====
    const anchoMetrajeContainer = document.getElementById('order-ancho-metraje');
    const anchoMetrajeManoContainer = document.getElementById('order-ancho-metraje-mano');
    const anchoValorMobile = document.getElementById('ancho-valor-mobile');
    const metrajeValorMobile = document.getElementById('metraje-valor-mobile');
    const metragesColorContainer = document.getElementById('metrajes-por-color-container-mobile');
    const contenidoManoMobile = document.getElementById('contenido-mano-mobile');
    const observacionesManoMobile = document.getElementById('observaciones-mano-mobile');
    const contenidoObservacionesMobile = document.getElementById('contenido-observaciones-mobile');
    
    // Obtener datos de ancho y metraje EXACTAMENTE como order-detail-modal (recibos-costura)
    const metrajeLabelMobile = metrajeValorMobile
        ? (metrajeValorMobile.closest('.metraje-label') || metrajeValorMobile.parentElement)
        : null;

    const resetAnchoMetrajeMobile = () => {
        if (anchoMetrajeContainer) anchoMetrajeContainer.style.display = 'none';
        if (anchoMetrajeManoContainer) anchoMetrajeManoContainer.style.display = 'none';
        if (anchoValorMobile) anchoValorMobile.textContent = '--';
        if (metrajeValorMobile) metrajeValorMobile.textContent = '--';
        if (metragesColorContainer) metragesColorContainer.innerHTML = '';
        if (contenidoManoMobile) contenidoManoMobile.textContent = '';
        if (observacionesManoMobile) observacionesManoMobile.style.display = 'none';
        if (contenidoObservacionesMobile) contenidoObservacionesMobile.textContent = '';
        if (metrajeLabelMobile) metrajeLabelMobile.style.display = 'block';
    };

    const renderAnchoMetrajeMobile = (payload) => {
        const tipoModo = String(payload?.tipo_modo || '').trim().toLowerCase();
        const metrajesValidos = Array.isArray(payload?.data)
            ? payload.data.filter(item => item?.color && item?.metraje)
            : [];

        if (!tipoModo || (!payload?.ancho && !payload?.metraje && !payload?.contenido_mano && metrajesValidos.length === 0)) {
            resetAnchoMetrajeMobile();
            return;
        }

        if (anchoValorMobile && payload?.ancho) {
            anchoValorMobile.textContent = `${payload.ancho} m`;
        }

        if (tipoModo === 'mano') {
            if (anchoMetrajeContainer) anchoMetrajeContainer.style.display = 'none';
            if (anchoMetrajeManoContainer) anchoMetrajeManoContainer.style.display = 'block';
            if (contenidoManoMobile) contenidoManoMobile.textContent = payload?.contenido_mano || '';
            if (observacionesManoMobile) observacionesManoMobile.style.display = 'none';
            return;
        }

        if (anchoMetrajeManoContainer) anchoMetrajeManoContainer.style.display = 'none';
        if (anchoMetrajeContainer) anchoMetrajeContainer.style.display = 'block';

        if (tipoModo === 'normal') {
            if (metrajeLabelMobile) metrajeLabelMobile.style.display = 'block';
            if (metrajeValorMobile) {
                const metrajeGeneral = payload?.metraje || null;
                metrajeValorMobile.textContent = metrajeGeneral ? `${metrajeGeneral} m` : '--';
            }
        } else {
            if (metrajeLabelMobile) metrajeLabelMobile.style.display = 'none';
        }

        if (metragesColorContainer) {
            metragesColorContainer.innerHTML = '';
            if (metrajesValidos.length > 0) {
                metrajesValidos.forEach(item => {
                    const row = document.createElement('div');
                    row.style.fontSize = '0.75rem';
                    row.style.color = '#666';
                    row.textContent = `${String(item.color).toUpperCase()}: ${item.metraje} m`;
                    metragesColorContainer.appendChild(row);
                });
            }
        }
    };

    resetAnchoMetrajeMobile();

    const prendaIdFromUrl = Number(urlParams.get('prenda_id') || 0);
    const prendaObjetivo = Array.isArray(data.prendas)
        ? (prendaIdFromUrl > 0
            ? data.prendas.find(p => Number(p?.id || p?.prenda_pedido_id || p?.prenda_id || 0) === prendaIdFromUrl)
            : data.prendas[0])
        : null;
    const pedidoIdAncho = Number(data?.id || data?.pedido_id || data?.pedido_produccion_id || prendaObjetivo?.pedido_produccion_id || 0);
    const prendaIdAncho = Number(prendaObjetivo?.id || prendaObjetivo?.prenda_pedido_id || prendaObjetivo?.prenda_id || 0);

    if (pedidoIdAncho > 0 && prendaIdAncho > 0) {
        const requestId = (window.__anchoMetrajeMobileRequestId || 0) + 1;
        window.__anchoMetrajeMobileRequestId = requestId;

        const publicEndpoint = `/pedidos-public/${pedidoIdAncho}/ancho-metraje-prenda/${prendaIdAncho}`;
        const insumosEndpoint = `/insumos/materiales/${pedidoIdAncho}/obtener-ancho-metraje-prenda/${prendaIdAncho}`;

        window.OrderDetailMobileService.getAnchoMetraje(publicEndpoint, insumosEndpoint)
            .then(payload => {
                if (window.__anchoMetrajeMobileRequestId !== requestId) return;
                if (!payload?.success) return;
                renderAnchoMetrajeMobile(payload);
            })
            .catch(error => {
                console.warn('[ANCHO-METRAJE] Error cargando ancho/metraje (mobile):', error);
            });
    }
    
    // Función helper para convertir markdown bold *** a <strong>
    const convertMarkdownBold = (texto) => {
        // Convertir ***texto*** a <strong>texto</strong>
        return texto.replace(/\*\*\*(.*?)\*\*\*/g, '<strong>$1</strong>')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    };

    // Inicializar índice del carrusel si no existe
    if (!window.prendaCarouselIndex) {
        window.prendaCarouselIndex = 0;
    }

    // Descripción - IGUAL QUE ASESORES: Priorizar descripcion_prendas del controlador
    let descripcionHTML = '';
    const descripcionPrendasCompleta = data.descripcion || '';
    let todasLasPrendas = data.prendas || [];
    // Cada recibo = 1 prenda, mostrar de a una
    const PRENDAS_POR_PAGINA = 1;
    
    // FILTRAR PRENDAS POR PROCESO SELECCIONADO
    const procesoActualIndex = window.procesoCarouselIndex || 0;
    const procesosDisponibles = window.todosProcesosDisponibles || [];
    const procesoActualSeleccionado = procesosDisponibles[procesoActualIndex] || null;
    
    console.log('📱 [RECIBO MOBILE] =========================================');
    console.log('📱 [RECIBO MOBILE] Proceso actual seleccionado:', procesoActualSeleccionado);
    console.log('📱 [RECIBO MOBILE] Índice del proceso:', procesoActualIndex);
    console.log('📱 [RECIBO MOBILE] Procesos disponibles:', procesosDisponibles);
    console.log('📱 [RECIBO MOBILE] Total prendas ANTES de filtrar:', todasLasPrendas.length);
    
    // SIEMPRE filtrar prendas por el proceso seleccionado (cada recibo = 1 prenda)
    if (procesoActualSeleccionado && todasLasPrendas.length > 0) {
        console.log('📱 [RECIBO MOBILE]  FILTRANDO prendas para proceso:', procesoActualSeleccionado);
        todasLasPrendas = todasLasPrendas.filter(function(prenda) {
            // Opción 1: Buscar en recibos
            if (prenda.recibos && typeof prenda.recibos === 'object') {
                const tieneProc = prenda.recibos[procesoActualSeleccionado] !== null && prenda.recibos[procesoActualSeleccionado] !== undefined;
                console.log('📱 [RECIBO MOBILE] Prenda:', prenda.nombre, '- Tiene', procesoActualSeleccionado + '?:', tieneProc, 'Valor:', prenda.recibos[procesoActualSeleccionado]);
                return tieneProc;
            }
            // Opción 2: Buscar en procesos (fallback)
            if (!prenda.procesos || !Array.isArray(prenda.procesos)) {
                return false;
            }
            return prenda.procesos.some(function(proc) {
                return proc.proceso === procesoActualSeleccionado;
            });
        });
        console.log('📱 [RECIBO MOBILE] Total prendas DESPUÉS de filtrar:', todasLasPrendas.length);
    } else if (!procesoActualSeleccionado && todasLasPrendas.length > 0) {
        // Primera carga sin proceso definido: filtrar prendas que tengan al menos un recibo no-null
        console.log('📱 [RECIBO MOBILE]  Primera carga sin proceso - filtrando prendas con recibos activos');
        todasLasPrendas = todasLasPrendas.filter(function(prenda) {
            if (prenda.recibos && typeof prenda.recibos === 'object') {
                return Object.values(prenda.recibos).some(function(v) { return v !== null && v !== undefined; });
            }
            return true;
        });
    }
    
    // LIMPIAR CONTENEDOR DE RECIBO ANTES DE RECONSTRUIR
    const reciboDOMContainer = document.getElementById('mobile-descripcion');
    if (reciboDOMContainer) {
        console.log('📱 [RECIBO MOBILE] Limpiando contenedor #mobile-descripcion');
        reciboDOMContainer.innerHTML = '';
    }
    
    // Declarar prendasActuales al inicio para que esté disponible en todo el scope
    let prendasActuales = [];
    
    console.log('📱 [RECIBO MOBILE] descripcionPrendasCompleta existe?:', !!descripcionPrendasCompleta);
    console.log('📱 [RECIBO MOBILE] descripcionPrendasCompleta trim():', descripcionPrendasCompleta ? descripcionPrendasCompleta.trim().substring(0, 100) : 'NULL');
    
    // SIEMPRE usar la rama dinámica para que cada recibo muestre solo su prenda con sus procesos
    // La descripción pre-construida mezclaba datos de diferentes prendas
    const debeUsarDescripcionPreConstruida = false;
    
    if (debeUsarDescripcionPreConstruida) {
        console.log('📱 [RECIBO MOBILE]  USANDO RAMA: descripcionPrendasCompleta (pre-construida)');
        
        // Limpiar espacios al inicio de cada línea
        const descripcionLimpia = descripcionPrendasCompleta
            .split('\n')
            .map(linea => linea.trimStart())
            .join('\n');
        
        // Dividir por "PRENDA " para obtener bloques individuales
        let bloquesPrendas = [];
        
        if (descripcionLimpia.includes('PRENDA ')) {
            // Hay formato PRENDA X: - dividir por eso
            const partes = descripcionLimpia.split('PRENDA ');
            
            bloquesPrendas = partes
                .map((parte, idx) => {
                    if (idx === 0 && !parte.trim()) return null;
                    return (idx > 0 ? 'PRENDA ' : '') + parte.trim();
                })
                .filter(b => b && b.trim() !== '');
        } else {
            // No hay formato PRENDA - dividir por \n\n pero agrupar tallas con su contenido
            const bloques = descripcionLimpia
                .split('\n\n')
                .filter(b => b && b.trim() !== '');
            
            bloquesPrendas = [];
            let bloqueActual = '';
            
            for (let i = 0; i < bloques.length; i++) {
                const bloque = bloques[i];
                
                if (/^(TALLAS?:|CANTIDAD TOTAL:)/i.test(bloque.trim())) {
                    bloqueActual += '\n\n' + bloque;
                } else {
                    if (bloqueActual) {
                        bloquesPrendas.push(bloqueActual.trim());
                    }
                    bloqueActual = bloque;
                }
            }
            
            if (bloqueActual) {
                bloquesPrendas.push(bloqueActual.trim());
            }
        }
        // Aplicar paginación
        const startIndex = window.prendaCarouselIndex || 0;
        const endIndex = startIndex + PRENDAS_POR_PAGINA;
        const bloquesActuales = bloquesPrendas.slice(startIndex, endIndex);
        
        // Formatear bloques actuales con estilos
        const descripcionFormateada = bloquesActuales
            .map((bloque) => {
                // Limpiar espacios al inicio y final del bloque completo
                bloque = bloque.trim();
                const lineas = bloque.split('\n').map(l => l.trim()).filter(l => l !== '');
                
                const lineasProcesadas = [];
                let hayTallasYa = false;
                
                for (let i = 0; i < lineas.length; i++) {
                    let linea = lineas[i];
                    if (linea === '') continue;
                    
                    // FILTRAR: No mostrar líneas de CANTIDAD TOTAL
                    if (/^CANTIDAD TOTAL:/i.test(linea)) {
                        continue;
                    }
                    
                    // FILTRAR: Si hay "TALLAS:", ignorar "Talla:" (evitar duplicados)
                    if (/^Talla:/i.test(linea) && hayTallasYa) {
                        continue;
                    }
                    
                    if (/^TALLAS:/i.test(linea)) {
                        hayTallasYa = true;
                    }
                    
                    // NEGRILLA en títulos
                    linea = linea.replace(/^(PRENDA \d+:)/g, '<strong>$1</strong>');
                    linea = linea.replace(/(Color:|Tela:|Manga:|DESCRIPCION:)/g, '<strong>$1</strong>');
                    
                    // NEGRILLA en viñetas
                    linea = linea.replace(/^(•\s+(Reflectivo:|Bolsillos:|BOTÓN:|[A-Z]+:))/g, '<strong>$1</strong>');
                    
                    // ROJO en tallas
                    if (/^TALLAS?:/i.test(linea)) {
                        linea = linea.replace(/^(TALLAS?:)\s+(.+)$/i, '$1 <span style="color: #d32f2f; font-weight: bold;">$2</span>');
                    }
                    
                    lineasProcesadas.push(linea);
                }
                
                return lineasProcesadas.join('<br>');
            })
            .join('<br><br>');
        
        // Reorganizar: Extraer TALLAS y ponerlas al final
        let descSinTallas = descripcionFormateada;
        let tallasExtraidas = '';

        // Buscar y extraer TALLAS usando regex
        const tallasRegex = /(<strong>TALLAS?<\/strong><br>.*?)(?=<\/div>|$)/is;
        const tallasMatch = descripcionFormateada.match(tallasRegex);

        if (tallasMatch) {
            tallasExtraidas = tallasMatch[1].trim();
            // Remover TALLAS de la descripción
            descSinTallas = descripcionFormateada.replace(tallasMatch[0], '').trim();
        }

        // Si la prenda tiene talla_colores, SIEMPRE preferir construir el bloque de tallas desde talla_colores
        // para garantizar el agrupamiento por color (ej: ROJO: M-5) aunque la descripción ya venga con tallas simples.
        try {
            if (todasLasPrendas && Array.isArray(todasLasPrendas) && todasLasPrendas.length > 0) {
                const prendaRefPreferida = todasLasPrendas[0];
                if (prendaRefPreferida?.talla_colores && Array.isArray(prendaRefPreferida.talla_colores) && prendaRefPreferida.talla_colores.length > 0) {
                    console.log('📱 [TALLAS OVERRIDE] Forzando bloque de TALLAS desde talla_colores (agrupado por color)');
                    const tallasStruct = transformarTallaColoresAEstructura(prendaRefPreferida.talla_colores);

                    const lineas = [];
                    const generos = Object.keys(tallasStruct || {});
                    generos.forEach((genero) => {
                        const generoLabel = (genero || '').toString().toUpperCase();
                        const tallasGenero = tallasStruct[genero] || {};
                        const porColor = {};

                        Object.entries(tallasGenero).forEach(([tallaRaw, val]) => {
                            const tallaKey = (tallaRaw || '').toString().trim().toUpperCase();
                            if (!tallaKey) return;
                            if (!Array.isArray(val)) return;
                            val.forEach((item) => {
                                const colorRaw = (item?.color || item?.color_nombre || '').toString().trim();
                                const colorKey = (colorRaw && colorRaw.toLowerCase() !== 'sin color') ? colorRaw.toUpperCase() : '__SIN_COLOR__';
                                const qty = parseInt(item?.cantidad || 0, 10) || 0;
                                if (!qty) return;
                                if (!porColor[colorKey]) porColor[colorKey] = [];
                                porColor[colorKey].push({ talla: tallaKey, cantidad: qty });
                            });
                        });

                        const coloresReales = Object.keys(porColor).filter(c => c !== '__SIN_COLOR__');
                        const sinColor = porColor['__SIN_COLOR__'] || [];

                        if (coloresReales.length > 0) {
                            lineas.push(`<strong>${generoLabel}:</strong>`);
                            coloresReales.forEach((color) => {
                                const arr = (porColor[color] || []).filter(x => x && x.talla && x.cantidad);
                                if (!arr.length) return;
                                const tallasStr = arr.map(t => `${t.talla}-${t.cantidad}`).join(', ');
                                lineas.push(`<span style="color: #d32f2f; font-weight: bold;"><strong>${color}:</strong> ${tallasStr}</span>`);
                            });
                            if (sinColor.length > 0) {
                                const tallasStr = sinColor.map(t => `${t.talla}-${t.cantidad}`).join(', ');
                                lineas.push(`<span style="color: #d32f2f; font-weight: bold;"><strong>SIN COLOR:</strong> ${tallasStr}</span>`);
                            }
                        }
                    });

                    if (lineas.length > 0) {
                        tallasExtraidas = `<strong>TALLAS</strong><br>` + lineas.join('<br>');
                    }
                }
            }
        } catch (e) {
            console.warn(' [TALLAS OVERRIDE] Error forzando tallas por color:', e);
        }

        // Fallback: si no hay bloque de TALLAS en la descripción, construirlo desde prenda.tallas o prenda.talla_colores
        if (!tallasExtraidas && todasLasPrendas && Array.isArray(todasLasPrendas) && todasLasPrendas.length > 0) {
            const prendaRef = todasLasPrendas[0];
            
            // PRIORIZAR talla_colores si está disponible
            let tallasParaUsar = null;
            if (prendaRef.talla_colores && Array.isArray(prendaRef.talla_colores) && prendaRef.talla_colores.length > 0) {
                console.log('📱 [FALLBACK] Usando talla_colores de la prenda:', prendaRef.talla_colores);
                tallasParaUsar = transformarTallaColoresAEstructura(prendaRef.talla_colores);
            } else if (prendaRef && prendaRef.tallas && typeof prendaRef.tallas === 'object') {
                console.log('📱 [FALLBACK] Usando tallas de la prenda (fallback):', prendaRef.tallas);
                tallasParaUsar = prendaRef.tallas;
            }
            
            if (tallasParaUsar) {
                const lineas = [];
                const generos = Object.keys(tallasParaUsar);
                generos.forEach((genero) => {
                    const tallasGenero = tallasParaUsar[genero] || {};
                    const generoLabel = (genero || '').toString().toUpperCase();

                    // Si el valor por talla es array (estructura con colores), agrupar por color y renderizar como:
                    // ROJO: M-5, S-2
                    const tieneColores = Object.values(tallasGenero).some(v => Array.isArray(v));
                    if (tieneColores) {
                        const porColor = {};
                        Object.entries(tallasGenero).forEach(([tallaRaw, val]) => {
                            const tallaKey = (tallaRaw || '').toString().trim().toUpperCase();
                            if (!tallaKey) return;
                            if (!Array.isArray(val)) return;
                            val.forEach((item) => {
                                const colorRaw = (item?.color || item?.color_nombre || '').toString().trim();
                                const colorKey = (colorRaw && colorRaw.toLowerCase() !== 'sin color') ? colorRaw.toUpperCase() : '__SIN_COLOR__';
                                const qty = parseInt(item?.cantidad || 0, 10) || 0;
                                if (!qty) return;
                                if (!porColor[colorKey]) porColor[colorKey] = [];
                                porColor[colorKey].push({ talla: tallaKey, cantidad: qty });
                            });
                        });

                        const coloresReales = Object.keys(porColor).filter(c => c !== '__SIN_COLOR__');
                        const sinColor = porColor['__SIN_COLOR__'] || [];

                        if (coloresReales.length > 0) {
                            lineas.push(`<strong>${generoLabel}:</strong>`);
                            coloresReales.forEach((color) => {
                                const arr = (porColor[color] || []).filter(x => x && x.talla && x.cantidad);
                                if (!arr.length) return;
                                const tallasStr = arr.map(t => `${t.talla}-${t.cantidad}`).join(', ');
                                lineas.push(`<span style="color: #d32f2f; font-weight: bold;"><strong>${color}:</strong> ${tallasStr}</span>`);
                            });
                            if (sinColor.length > 0) {
                                const tallasStr = sinColor.map(t => `${t.talla}-${t.cantidad}`).join(', ');
                                lineas.push(`<span style="color: #d32f2f; font-weight: bold;"><strong>SIN COLOR:</strong> ${tallasStr}</span>`);
                            }
                        } else if (sinColor.length > 0) {
                            const tallasSimple = sinColor.map(t => `${t.talla}: <span style="color: #d32f2f;"><strong>${t.cantidad}</strong></span>`);
                            lineas.push(`<strong>${generoLabel}:</strong> ${tallasSimple.join(', ')}`);
                        }
                    } else {
                        // Sin colores - formato simple
                        const tallas = [];
                        Object.keys(tallasGenero).forEach((talla) => {
                            const cantidad = parseInt(tallasGenero[talla] || 0, 10) || 0;
                            if (cantidad > 0) {
                                tallas.push(`${talla}: <span style="color: #d32f2f;"><strong>${cantidad}</strong></span>`);
                            }
                        });
                        if (tallas.length > 0) {
                            lineas.push(`<strong>${generoLabel}:</strong> ${tallas.join(', ')}`);
                        }
                    }
                });

                if (lineas.length > 0) {
                    tallasExtraidas = `<strong>TALLAS</strong><br>` + lineas.join('<br>');
                }
            }
        }
        
        descripcionHTML = `<div style="line-height: 1.3; font-size: 0.75rem; color: #333; word-break: break-word; overflow-wrap: break-word; max-width: 100%; margin: 0; padding: 0; text-align: left;">${descSinTallas}</div>`;
        
        //  AGREGAR DATOS DE PROCESOS (Ubicaciones, Observaciones) 
        // Incluso aunque usamos descripcionPrendasCompleta, debemos incluir datos dinámicos de procesos
        const procStartIndex = window.prendaCarouselIndex || 0;
        const procEndIndex = procStartIndex + PRENDAS_POR_PAGINA;
        const prendasConProcesos = todasLasPrendas.slice(procStartIndex, procEndIndex);
        
        let datosProcesoHTML = '';
        let tallasIncluidasEnDatosProceso = false;
        prendasConProcesos.forEach((prenda) => {
            if (prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0) {
                prenda.procesos.forEach((proceso) => {
                    // UBICACIONES
                    if (proceso.ubicaciones) {
                        const ubicacionesArray = normalizarUbicaciones(proceso.ubicaciones);
                        if (ubicacionesArray.length > 0) {
                            datosProcesoHTML += `<strong>UBICACIONES:</strong><br>`;
                            ubicacionesArray.forEach(ub => {
                                datosProcesoHTML += `• ${(ub || '').toString().toUpperCase()}<br>`;
                            });
                        }
                    }
                    
                    const observacionProceso = String(proceso.observaciones || '').trim();

                    // TALLAS DEL PROCESO (como en /registros)
                    if (proceso.tallas) {
                        const grupos = {
                            DAMA: [],
                            CABALLERO: [],
                            UNISEX: [],
                        };

                        const pushTalla = (grupo, talla, cantidad) => {
                            const c = parseInt(cantidad) || 0;
                            if (c > 0) {
                                grupos[grupo].push(`${talla}: ${c}`);
                            }
                        };

                        if (proceso.tallas.dama && Object.keys(proceso.tallas.dama).length > 0) {
                            Object.entries(proceso.tallas.dama).forEach(([talla, cantidad]) => pushTalla('DAMA', talla, cantidad));
                        }
                        if (proceso.tallas.caballero && Object.keys(proceso.tallas.caballero).length > 0) {
                            Object.entries(proceso.tallas.caballero).forEach(([talla, cantidad]) => pushTalla('CABALLERO', talla, cantidad));
                        }
                        if (proceso.tallas.unisex && Object.keys(proceso.tallas.unisex).length > 0) {
                            Object.entries(proceso.tallas.unisex).forEach(([talla, cantidad]) => pushTalla('UNISEX', talla, cantidad));
                        }

                        const lineasTallas = [];
                        Object.keys(grupos).forEach((g) => {
                            if (grupos[g].length > 0) {
                                lineasTallas.push(`<strong>${g}:</strong> <span style=\"color: #d32f2f;\">${grupos[g].join(', ')}</span>`);
                            }
                        });

                        if (lineasTallas.length > 0) {
                            tallasIncluidasEnDatosProceso = true;
                            datosProcesoHTML += `<strong>TALLAS</strong><br>${lineasTallas.join('<br>')}<br>`;
                        }
                    }

                    // OBSERVACIONES (debajo de tallas)
                    if (observacionProceso) {
                        datosProcesoHTML += `<strong>OBSERVACIONES:</strong><br>${observacionProceso.toUpperCase()}<br>`;
                    }
                });
            }
        });
        
        // Combinar: Descripción base + Ubicaciones/Observaciones
        if (datosProcesoHTML) {
            descripcionHTML += `<div style="line-height: 1.3; font-size: 0.75rem; color: #333; word-break: break-word; overflow-wrap: break-word; max-width: 100%; margin-top: 0.5rem; padding: 0; text-align: left;">${datosProcesoHTML}</div>`;
        }
        
        // Mostrar TALLAS al final (solo si NO se mostraron desde procesos)
        // y si el contenido tiene datos reales (no solo el encabezado).
        const tallasSoloEncabezado = /^<strong>\s*TALLAS\s*<\/strong><br>\s*$/i.test((tallasExtraidas || '').trim());
        if (!tallasIncluidasEnDatosProceso && tallasExtraidas && !tallasSoloEncabezado) {
            descripcionHTML += `<div style="line-height: 1.3; font-size: 0.75rem; color: #333; word-break: break-word; overflow-wrap: break-word; max-width: 100%; margin-top: 0.5rem; padding: 0; text-align: left;">${tallasExtraidas}</div>`;
        }
        
        // Actualizar total de bloques para el carousel
        window.totalBloquesPrendas = bloquesPrendas.length;
        
    } else if (todasLasPrendas.length > 0) {
        // FALLBACK: Generar descripción dinámica desde prendas (igual que asesores)
        console.log('📱 [RECIBO MOBILE]  USANDO RAMA: Fallback dinámico (descripcion_prendas vacía)');
        console.log(' [MOBILE] Usando lógica de construcción dinámica (descripcion_prendas vacía)');
        console.log(' [DEBUG] Rol del usuario:', userRole);
        console.log(' [DEBUG] Datos de prendas:', todasLasPrendas);
        if (todasLasPrendas.length > 0) {
            const pr = todasLasPrendas[0];
            console.log(' [DEBUG] Primera prenda - tallas:', pr.tallas);
            console.log(' [DEBUG] Primera prenda - talla_colores:', pr.talla_colores);
            console.log(' [DEBUG] Primera prenda - variantes:', pr.variantes);
        }
        
        const startIndex = window.prendaCarouselIndex || 0;
        const endIndex = startIndex + PRENDAS_POR_PAGINA;
        prendasActuales = todasLasPrendas.slice(startIndex, endIndex);
        
        console.log('📱 [RECIBO MOBILE]  Fallback - prendasActuales rellenadas:', prendasActuales.length);
        
        // Generar descripción dinámica para cada prenda (igual que asesores)
        prendasActuales.forEach((prenda, index) => {
            console.log(' [PRENDA] Datos completos:', JSON.stringify(prenda, null, 2));
            console.log(' [PRENDA] Keys disponibles:', Object.keys(prenda));
            
            let html = '';
            
            // 1. Nombre de la prenda (con estilo consistente)
            const isPrendaBodega = data.cliente === 'SERVICIO' && (data.asesor === 'SISTEMA' || data.asesora === 'SISTEMA');
            if (isPrendaBodega) {
                html += `<strong style="font-size: 13.4px;">PRENDA ${prenda.numero || prenda.numero_prenda || (startIndex + index + 1)}</strong><br>`;
            } else {
                html += `<strong style="font-size: 13.4px;">PRENDA ${prenda.numero || prenda.numero_prenda || (startIndex + index + 1)}: ${(prenda.nombre || prenda.nombre_prenda || 'SIN NOMBRE').toUpperCase()}</strong><br>`;
            }

            // 2. Telas completas con referencia (colores_telas tiene toda la info)
            if (prenda.colores_telas && Array.isArray(prenda.colores_telas) && prenda.colores_telas.length > 0) {
                const telasTexto = prenda.colores_telas.map(function(ct) {
                    const tela = (ct.tela_nombre || '').toUpperCase();
                    const color = (ct.color_nombre || '').toUpperCase();
                    const esColorValido = color && color !== 'SIN COLOR' && color !== 'NO COLOR' && color !== '';
                    const ref = ct.referencia || '';
                    let parte = tela;
                    if (esColorValido) {
                        parte += ' / ' + color;
                    }
                    if (ref) {
                        parte += ' | REF: ' + ref;
                    }
                    return parte;
                }).join(' | ');
                html += `<strong>TELAS:</strong> ${telasTexto}<br>`;
            } else {
                // Fallback a campos individuales
                const atributos = [];
                if (prenda.color) {
                    atributos.push(`<strong>Color:</strong> ${prenda.color.toUpperCase()}`);
                }
                if (prenda.tela) {
                    let telaTexto = prenda.tela.toUpperCase();
                    if (prenda.ref || prenda.tela_referencia) {
                        telaTexto += ` REF:${(prenda.ref || prenda.tela_referencia).toString().toUpperCase()}`;
                    }
                    atributos.push(`<strong>Tela:</strong> ${telaTexto}`);
                }
                if (atributos.length > 0) {
                    html += atributos.join(' | ') + '<br>';
                }
            }

            // 2.5. Descripción de la prenda (campo descripcion de prendas_pedido)
            if (prenda.descripcion && prenda.descripcion.trim()) {
                html += `<span style="display: block; margin-top: 2px; white-space: pre-line;">— ${prenda.descripcion.trim().toUpperCase()}</span>`;
            }

            // 3. Manga
            if (prenda.manga) {
                let mangaTexto = (prenda.manga || '').toUpperCase();
                // Incluir observación de manga si existe y tiene contenido
                if (prenda.obs_manga) {
                    const obsMangaTrimmed = (prenda.obs_manga || '').toString().trim();
                    if (obsMangaTrimmed && obsMangaTrimmed !== '' && obsMangaTrimmed !== 'undefined') {
                        mangaTexto += ` (${obsMangaTrimmed.toUpperCase()})`;
                    }
                }
                html += `<strong>MANGA:</strong> ${mangaTexto}<br>`;
            } else if (prenda.tipo_manga) {
                let mangaTexto = prenda.tipo_manga.toUpperCase();
                if (prenda.descripcion_variaciones) {
                    const mangaMatch = prenda.descripcion_variaciones.match(/Manga:\s*(.+?)(?:\s*\||$)/i);
                    if (mangaMatch) {
                        const observacionManga = mangaMatch[1].trim().toUpperCase();
                        if (observacionManga !== mangaTexto) {
                            mangaTexto += ` (${observacionManga})`;
                        }
                    }
                }
                html += `<strong>MANGA:</strong> ${mangaTexto}<br>`;
            }

            // 4. Bolsillos
            if (prenda.obs_bolsillos) {
                html += `• <strong>BOLSILLOS:</strong> ${prenda.obs_bolsillos}<br>`;
            }

            // 5. Broche/Botón
            if (prenda.obs_broche && prenda.broche) {
                html += `• <strong>${prenda.broche.toUpperCase()}:</strong> ${prenda.obs_broche}<br>`;
            }
            
            // =========================================================
            // DATOS DEL PROCESO - Lógica idéntica a recibos-costura
            // Un recibo = una prenda, filtrado por proceso seleccionado
            // =========================================================
            const esReciboCostura = !procesoActualSeleccionado || 
                procesoActualSeleccionado.toUpperCase() === 'COSTURA' || 
                procesoActualSeleccionado.toUpperCase() === 'COSTURA-BODEGA';
            
            console.log('📱 [RECIBO MOBILE] esReciboCostura:', esReciboCostura, 'procesoActualSeleccionado:', procesoActualSeleccionado);
            
            if (esReciboCostura) {
                // === COSTURA: Tallas de la prenda + REFLECTIVO si aplica ===
                // Agrupado por COLOR: AZUL CELESTE: L-3, M-3, S-3
                
                // Tallas a nivel de PRENDA (no de proceso)
                // EXCEPCIÓN: si estamos viendo un ANEXO de COSTURA (es_parcial=true),
                // se deben mostrar SOLO las tallas del anexo.
                let tallasFuente = prenda.tallas;
                
                // PRIORIZAR talla_colores si está disponible (como en recibos de costura)
                // Si viene vacío pero las variantes tienen `colores_detalle`, derivarlo para agrupar por color.
                const tallaColoresDerivada = (!prenda?.talla_colores || (Array.isArray(prenda.talla_colores) && prenda.talla_colores.length === 0))
                    ? derivarTallaColoresDesdeVariantes(prenda?.variantes)
                    : [];

                if (tallaColoresDerivada.length > 0) {
                    console.log('📱 [RECIBO MOBILE] Derivando talla_colores desde variantes:', tallaColoresDerivada);
                    prenda.talla_colores = tallaColoresDerivada;
                }

                if (prenda.talla_colores && Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0) {
                    console.log('📱 [RECIBO MOBILE] Usando talla_colores de la prenda:', prenda.talla_colores);
                    console.log(' [DEBUG] Rol actual:', userRole, '- talla_colores encontrado:', prenda.talla_colores.length, 'items');
                    tallasFuente = transformarTallaColoresAEstructura(prenda.talla_colores);
                } else if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
                    console.log('📱 [RECIBO MOBILE] Usando variantes de la prenda:', prenda.variantes);
                    console.log(' [DEBUG] Rol actual:', userRole, '- variantes encontrado:', prenda.variantes.length, 'items');
                    tallasFuente = transformarVariantesAEstructura(prenda.variantes);
                } else {
                    console.log(' [DEBUG] Rol actual:', userRole, '- NO hay talla_colores ni variantes, usando tallas normales');
                }
                try {
                    if (procesoActualSeleccionado && procesoActualSeleccionado.toUpperCase() === 'COSTURA' && prenda.procesos && Array.isArray(prenda.procesos)) {
                        const procCosturaAnexo = prenda.procesos.find(p => {
                            const tipo = (p.proceso || p.tipo_proceso || p.nombre_proceso || '').toString().trim().toUpperCase();
                            return tipo === 'COSTURA' && !!p.es_parcial;
                        });

                        if (procCosturaAnexo) {
                            console.log('📱 [RECIBO MOBILE] COSTURA ANEXO detectado, usando tallas del anexo:', procCosturaAnexo);
                            if (procCosturaAnexo.talla_colores && Array.isArray(procCosturaAnexo.talla_colores) && procCosturaAnexo.talla_colores.length > 0) {
                                tallasFuente = transformarTallaColoresAEstructura(procCosturaAnexo.talla_colores);
                            } else if (procCosturaAnexo.tallas && typeof procCosturaAnexo.tallas === 'object') {
                                tallasFuente = procCosturaAnexo.tallas;
                            } else {
                                tallasFuente = {};
                            }
                        }
                    }
                } catch (e) {
                    console.warn('📱 [RECIBO MOBILE] Error detectando costura anexo:', e);
                }

                if (tallasFuente && typeof tallasFuente === 'object') {
                    const tallasLineas = [];
                    
                    const generos = Object.keys(tallasFuente);
                    generos.forEach((genero) => {
                        const tallasGenero = tallasFuente[genero] || {};
                        
                        if (typeof tallasGenero === 'object' && !Array.isArray(tallasGenero)) {
                            // Detectar si hay sobremedida
                            const tieneSobremedida = tallasGenero.hasOwnProperty('SOBREMEDIDA');
                            
                            // Si solo hay sobremedida, mostrar formato especial
                            if (tieneSobremedida && Object.keys(tallasGenero).length === 1) {
                                const cantidad = tallasGenero['SOBREMEDIDA'];
                                tallasLineas.push(`<strong>SOBREMEDIDA</strong><br>${(genero || '').toString().toUpperCase()}: ${cantidad}`);
                                console.log(`📱 [RECIBO MOBILE] ${genero} SOBREMEDIDA: ${cantidad}`);
                                return; // Salir del forEach para no procesar más
                            }
                            
                            // Detectar si hay colores reales (no "SIN COLOR")
                            let tieneColoresReales = false;
                            const coloresMap = {}; // { COLOR: [{talla, cantidad}] }
                            const sinColorItems = []; // Para tallas sin color
                            
                            Object.entries(tallasGenero).forEach(([talla, val]) => {
                                if (Array.isArray(val)) {
                                    val.forEach((item) => {
                                        if (item && typeof item === 'object') {
                                            const c = parseInt(item.cantidad) || 0;
                                            const color = (item.color || '').trim().toUpperCase();
                                            if (c > 0) {
                                                if (color && color !== 'SIN COLOR' && color !== 'NO COLOR' && color !== '') {
                                                    tieneColoresReales = true;
                                                    if (!coloresMap[color]) coloresMap[color] = [];
                                                    coloresMap[color].push({ talla, cantidad: c });
                                                } else {
                                                    sinColorItems.push({ talla, cantidad: c });
                                                }
                                            }
                                        }
                                    });
                                } else if (val && typeof val === 'object') {
                                    const c = parseInt(val.cantidad) || 0;
                                    const color = (val.color || '').trim().toUpperCase();
                                    if (c > 0) {
                                        if (color && color !== 'SIN COLOR' && color !== 'NO COLOR' && color !== '') {
                                            tieneColoresReales = true;
                                            if (!coloresMap[color]) coloresMap[color] = [];
                                            coloresMap[color].push({ talla, cantidad: c });
                                        } else {
                                            sinColorItems.push({ talla, cantidad: c });
                                        }
                                    }
                                } else {
                                    const c = parseInt(val) || 0;
                                    if (c > 0) {
                                        sinColorItems.push({ talla, cantidad: c });
                                    }
                                }
                            });
                            
                            if (tieneColoresReales) {
                                // Agrupar por color: AZUL CELESTE: L-3, M-3, S-3
                                const colorLineas = [];
                                Object.entries(coloresMap).forEach(([color, tallasArr]) => {
                                    const tallasTexto = tallasArr.map(t => `${t.talla}-${t.cantidad}`).join(', ');
                                    colorLineas.push(`<span style="color: #d32f2f; font-weight: bold;">${color}:</span> ${tallasTexto}`);
                                });
                                if (colorLineas.length > 0) {
                                    tallasLineas.push(`<strong>${(genero || '').toString().toUpperCase()}:</strong><br>${colorLineas.join('<br>')}`);
                                }
                            } else if (sinColorItems.length > 0) {
                                // Sin colores: mostrar tallas normalmente (L: 3, M: 3)
                                const items = sinColorItems.map(t => `${t.talla}: <span style="color: #d32f2f; font-weight: bold;">${t.cantidad}</span>`);
                                tallasLineas.push(`<strong>${(genero || '').toString().toUpperCase()}:</strong> ${items.join(', ')}`);
                            }
                        }
                    });
                    
                    if (tallasLineas.length > 0) {
                        html += `<br><strong>TALLAS</strong><br>${tallasLineas.join('<br>')}<br>`;
                    }
                }
                
                // REFLECTIVO: Solo mostrar si la prenda NO es de bodega y tiene proceso reflectivo
                // (Igual que Formatters.construirDescripcionCostura)
                if (!prenda.de_bodega && prenda.procesos && Array.isArray(prenda.procesos)) {
                    const procesoReflectivo = prenda.procesos.find(p => {
                        const tipo = (p.proceso || p.tipo_proceso || p.nombre_proceso || '').toUpperCase();
                        return tipo === 'REFLECTIVO';
                    });
                    
                    if (procesoReflectivo) {
                        console.log('📱 [RECIBO MOBILE] Proceso REFLECTIVO encontrado para prenda:', prenda.nombre);
                        html += `<br><strong style="font-size: 13.4px;">PROCESO: REFLECTIVO</strong><br>`;
                        
                        // Ubicaciones del reflectivo
                        if (procesoReflectivo.ubicaciones) {
                            const ubicacionesNorm = normalizarUbicaciones(procesoReflectivo.ubicaciones);
                            if (ubicacionesNorm.length > 0) {
                                html += `<strong>UBICACIONES:</strong><br>`;
                                ubicacionesNorm.forEach(ub => {
                                    html += `• ${(ub || '').toString().toUpperCase()}<br>`;
                                });
                            }
                        }
                        
                        const observacionReflectivo = String(procesoReflectivo.observaciones || '').trim();
                        
                        // Tallas del reflectivo
                        // Lógica: 
                        // - Si de_bodega=FALSE (COSTURA normal): omitir tallas del reflectivo si son iguales a la prenda
                        // - Si de_bodega=TRUE (REFLECTIVO recibo): SIEMPRE mostrar tallas del reflectivo
                        if (procesoReflectivo.tallas && typeof procesoReflectivo.tallas === 'object') {
                            // Función para normalizar y extraer valores de cantidad
                            const normalizarValor = (val) => {
                                if (Array.isArray(val) && val.length > 0 && typeof val[0] === 'object' && val[0].cantidad) {
                                    return parseInt(val[0].cantidad) || 0;
                                }
                                return parseInt(val) || 0;
                            };
                            
                            // Función para comparar dos objetos de tallas (sin importar orden ni case)
                            const sonTallasIguales = (tallas1, tallas2) => {
                                if (!tallas1 && !tallas2) return true;
                                if (!tallas1 || !tallas2) return false;
                                
                                const generos = ['dama', 'caballero', 'unisex'];
                                for (let genero of generos) {
                                    // Obtener las tallas normalizando el case del género
                                    const t1Data = tallas1[genero] || tallas1[genero.toUpperCase()] || {};
                                    const t2Data = tallas2[genero] || tallas2[genero.toUpperCase()] || {};
                                    
                                    const t1Keys = Object.keys(t1Data);
                                    const t2Keys = Object.keys(t2Data);
                                    
                                    // Si la cantidad de tallas es diferente, no son iguales
                                    if (t1Keys.length !== t2Keys.length) return false;
                                    
                                    // Comparar valores para cada talla (sin importar orden)
                                    for (let talla of t1Keys) {
                                        const val1 = normalizarValor(t1Data[talla]);
                                        const val2 = normalizarValor(t2Data[talla]);
                                        if (val1 !== val2) return false;
                                    }
                                }
                                return true;
                            };
                            
                            // Determinar si se deben mostrar las tallas del reflectivo
                            let mostrarTallasReflectivo = true;
                            
                            // Si prenda NO es de bodega (de_bodega=FALSE), aplicar comparación
                            // Si prenda SÍ es de bodega (de_bodega=TRUE), SIEMPRE mostrar tallas
                            if (!prenda.de_bodega) {
                                // COSTURA normal: solo mostrar tallas si son diferentes
                                const tallasIguales = sonTallasIguales(prenda.tallas, procesoReflectivo.tallas);
                                mostrarTallasReflectivo = !tallasIguales;
                                console.log('📱 [TALLAS COMPARACIÓN] COSTURA normal (de_bodega=FALSE) - ¿Son iguales?:', tallasIguales, '→ Mostrar:', mostrarTallasReflectivo);
                            } else {
                                // REFLECTIVO recibo (de_bodega=TRUE): siempre mostrar
                                console.log('📱 [TALLAS COMPARACIÓN] REFLECTIVO recibo (de_bodega=TRUE) → SIEMPRE mostrar tallas');
                            }
                            
                            // Mostrar tallas del reflectivo según la lógica anterior
                            if (mostrarTallasReflectivo) {
                                const tallasRefLineas = [];
                                ['dama', 'caballero', 'unisex'].forEach((genero) => {
                                    if (procesoReflectivo.tallas[genero] && typeof procesoReflectivo.tallas[genero] === 'object') {
                                        const items = [];
                                        Object.entries(procesoReflectivo.tallas[genero]).forEach(([talla, cantidad]) => {
                                            const c = parseInt(cantidad) || 0;
                                            if (c > 0) {
                                                items.push(`${talla}: <span style="color: #d32f2f; font-weight: bold;">${c}</span>`);
                                            }
                                        });
                                        if (items.length > 0) {
                                            tallasRefLineas.push(`<strong>${genero.toUpperCase()}:</strong> ${items.join(', ')}`);
                                        }
                                    }
                                });
                                
                                if (tallasRefLineas.length > 0) {
                                    html += `<strong>TALLAS</strong><br>${tallasRefLineas.join('<br>')}<br>`;
                                }
                            }
                        }

                        // Observaciones del reflectivo (debajo de tallas)
                        if (observacionReflectivo) {
                            html += `<strong>OBSERVACIONES:</strong><br>${observacionReflectivo.toUpperCase()}<br>`;
                        }
                    }
                }
            } else {
                // === NO-COSTURA (ESTAMPADO, BORDADO, DTF, etc.): Solo el proceso seleccionado ===
                // (Igual que Formatters.construirDescripcionProceso en recibos-costura)
                if (prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0) {
                    const procesosFiltradosRaw = prenda.procesos.filter(p => {
                        const tipo = (p.proceso || p.tipo_proceso || p.nombre_proceso || '').toUpperCase();
                        return tipo === procesoActualSeleccionado.toUpperCase();
                    });

                    let procesosFiltrados = procesosFiltradosRaw;
                    if (esReciboParcial) {
                        const procesosParciales = procesosFiltradosRaw.filter((p) => {
                            const parcialIdProceso = String(p.pedido_parcial_id || p.parcial_id || '').trim();
                            const matchById = pedidoParcialIdParam !== '' && parcialIdProceso === pedidoParcialIdParam;
                            return matchById || !!p.es_parcial;
                        });

                        if (procesosParciales.length > 0) {
                            procesosFiltrados = procesosParciales;
                        }
                    }
                    
                    // Fallback para anexos/parciales: puede no existir `prenda.procesos` con el tipo actual
                    // (ej. REFLECTIVO parcial), pero sí venir en `prenda.recibos[REFLECTIVO]`.
                    if (procesosFiltrados.length === 0) {
                        let reciboParcial = null;
                        const recibosPrenda = prenda?.recibos;
                        const procesoActualUpper = String(procesoActualSeleccionado || '').trim().toUpperCase();
                        const parcialIdEsperado = String(pedidoParcialIdParam || '').trim();

                        if (recibosPrenda && typeof recibosPrenda === 'object' && !Array.isArray(recibosPrenda)) {
                            reciboParcial = recibosPrenda[procesoActualSeleccionado] || null;

                            if (!reciboParcial) {
                                for (const [key, value] of Object.entries(recibosPrenda)) {
                                    if (String(key || '').trim().toUpperCase() === procesoActualUpper && value) {
                                        reciboParcial = value;
                                        break;
                                    }
                                }
                            }

                            if (!reciboParcial) {
                                for (const value of Object.values(recibosPrenda)) {
                                    if (!value || typeof value !== 'object') continue;
                                    const tipoInterno = String(value.tipo_recibo || value.proceso || '').trim().toUpperCase();
                                    const parcialInterno = String(value.parcial_id || value.pedido_parcial_id || value.id || '').trim();
                                    const coincideTipo = tipoInterno !== '' && tipoInterno === procesoActualUpper;
                                    const coincideParcial = parcialIdEsperado !== '' && parcialInterno === parcialIdEsperado;
                                    if (coincideTipo || coincideParcial) {
                                        reciboParcial = value;
                                        break;
                                    }
                                }
                            }
                        }

                        if (reciboParcial && typeof reciboParcial === 'object') {
                            procesosFiltrados = [{
                                proceso: procesoActualSeleccionado,
                                tipo_proceso: procesoActualSeleccionado,
                                nombre_proceso: procesoActualSeleccionado,
                                es_parcial: true,
                                pedido_parcial_id: reciboParcial?.id || pedidoParcialIdParam || null,
                                tallas: reciboParcial?.tallas || [],
                                talla_colores: reciboParcial?.talla_colores || [],
                                ubicaciones: reciboParcial?.ubicaciones || null,
                                observaciones: reciboParcial?.observaciones || ''
                            }];

                            console.log('📱 [RECIBO MOBILE] Fallback parcial desde prenda.recibos aplicado:', procesosFiltrados[0]);
                        }
                    }

                    console.log('📱 [RECIBO MOBILE] Procesos filtrados para', procesoActualSeleccionado + ':', procesosFiltrados.length);
                    
                    procesosFiltrados.forEach((proceso) => {
                        // UBICACIONES del proceso
                        if (proceso.ubicaciones) {
                            const ubicacionesNorm = normalizarUbicaciones(proceso.ubicaciones);
                            if (ubicacionesNorm.length > 0) {
                                html += `<strong>UBICACIONES:</strong><br>`;
                                ubicacionesNorm.forEach(ub => {
                                    html += `• ${(ub || '').toString().toUpperCase()}<br>`;
                                });
                            }
                        }
                        
                        const observacionProceso = String(proceso.observaciones || '').trim();
                        
                        // TALLAS del proceso específico (SIEMPRE mostrar para procesos NO-COSTURA)
                        // En parciales, la fuente válida debe ser exclusivamente el anexo resuelto.
                        let tallasObj = proceso.tallas;
                        if (esReciboParcial) {
                            if (Array.isArray(proceso.talla_colores) && proceso.talla_colores.length > 0) {
                                console.log('[OPERARIO] Parcial detectado, usando proceso.talla_colores:', proceso.talla_colores);
                                tallasObj = transformarTallaColoresAEstructura(proceso.talla_colores);
                            } else if (Array.isArray(proceso.tallas) && proceso.tallas.length > 0) {
                                console.log('[OPERARIO] Parcial detectado, usando proceso.tallas:', proceso.tallas);
                                tallasObj = transformarTallasListaParcialAEstructura(proceso.tallas);
                            }

                            if ((!tallasObj || Object.keys(tallasObj).length === 0) && Array.isArray(proceso.tallas_detalle) && proceso.tallas_detalle.length > 0) {
                                console.log('[OPERARIO] Parcial detectado, usando proceso.tallas_detalle:', proceso.tallas_detalle);
                                tallasObj = transformarTallasListaParcialAEstructura(
                                    proceso.tallas_detalle.map((t) => ({
                                        genero: t?.genero,
                                        talla: t?.talla,
                                        cantidad: t?.cantidad,
                                        color_nombre: t?.color_nombre || ''
                                    }))
                                );
                            }
                        }
                        
                        // Si el proceso tiene talla_colores, transformarlas a estructura enriquecida
                        if ((!tallasObj || Object.keys(tallasObj).length === 0) && proceso.talla_colores && Array.isArray(proceso.talla_colores) && proceso.talla_colores.length > 0) {
                            console.log('📱 [OPERARIO] Transformando talla_colores a estructura enriquecida:', proceso.talla_colores);
                            tallasObj = transformarTallaColoresAEstructura(proceso.talla_colores);
                        } else if ((!tallasObj || Object.keys(tallasObj).length === 0) && !proceso.es_parcial) {
                            // Si no hay tallas en el proceso y NO es anexo, usar fallback de la prenda
                            // Para anexos solo deben mostrarse tallas del anexo.
                            // PRIORIZAR talla_colores de la prenda si está disponible
                            if (prenda.talla_colores && Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0) {
                                console.log('📱 [OPERARIO] Usando talla_colores de la prenda como fallback:', prenda.talla_colores);
                                tallasObj = transformarTallaColoresAEstructura(prenda.talla_colores);
                            } else {
                                tallasObj = prenda.tallas;
                            }
                        }
                        
                        // OVERRIDE: si la prenda trae talla_colores, preferir este detalle para agrupar por color,
                        // incluso si el proceso trae tallas genéricas (ej: REFLECTIVO).
                        if (
                            !esReciboParcial &&
                            (!proceso.talla_colores || !Array.isArray(proceso.talla_colores) || proceso.talla_colores.length === 0) &&
                            prenda.talla_colores && Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0
                        ) {
                            tallasObj = transformarTallaColoresAEstructura(prenda.talla_colores);
                        }

                        if (tallasObj && typeof tallasObj === 'object') {
                            const tallasLineas = [];
	                            ['dama', 'caballero', 'unisex'].forEach((generoBase) => {
	                                const generoKey = (tallasObj && typeof tallasObj === 'object' && tallasObj[generoBase])
	                                    ? generoBase
	                                    : generoBase.toUpperCase();

	                                if (tallasObj[generoKey] && typeof tallasObj[generoKey] === 'object') {
	                                    const tallasGenero = tallasObj[generoKey];
	                                    const generoLabel = generoKey.toString().toUpperCase();
	                                    
	                                    // Detectar si hay colores (datos son arrays de objetos)
	                                    const tieneColores = Object.values(tallasGenero).some(datos => Array.isArray(datos));
	                                    
	                                    if (tieneColores) {
	                                        // Agrupar por color: AZUL CELESTE: L-3, M-3, S-3
	                                        const porColor = {};
	                                        Object.entries(tallasGenero).forEach(([talla, datos]) => {
	                                            if (Array.isArray(datos)) {
	                                                datos.forEach(d => {
	                                                    const esColorValido = d.color && d.color.toLowerCase() !== 'sin color' && d.color.trim() !== '';
	                                                    const color = esColorValido ? d.color.toUpperCase() : '__SIN_COLOR__';
	                                                    if (!porColor[color]) porColor[color] = [];
	                                                    porColor[color].push({ talla, cantidad: d.cantidad || 0 });
	                                                });
	                                            } else {
	                                                if (!porColor['__SIN_COLOR__']) porColor['__SIN_COLOR__'] = [];
	                                                porColor['__SIN_COLOR__'].push({ talla, cantidad: datos });
	                                            }
	                                        });
	                                        
	                                        // Renderizar agrupado por color
	                                        const coloresReales = Object.entries(porColor).filter(([c]) => c !== '__SIN_COLOR__');
	                                        const sinColor = porColor['__SIN_COLOR__'] || [];
	                                        
	                                        if (coloresReales.length > 0) {
	                                            let colorTexto = `<strong>${generoLabel}:</strong>`;
	                                            coloresReales.forEach(([color, tallasArr]) => {
	                                                const tallasStr = tallasArr.map(t => `${t.talla}-${t.cantidad}`).join(', ');
	                                                colorTexto += `<br><span style="color: #d32f2f;"><strong>${color}:</strong> ${tallasStr}</span>`;
	                                            });
	                                            tallasLineas.push(colorTexto);
	                                        } else if (sinColor.length > 0) {
	                                            const tallasStr = sinColor.map(t => `${t.talla}: <span style="color: #d32f2f; font-weight: bold;">${t.cantidad}</span>`).join(', ');
	                                            tallasLineas.push(`<strong>${generoLabel}:</strong> ${tallasStr}`);
	                                        }
	                                    } else {
	                                        // Sin colores - formato simple
	                                        const items = [];
	                                        Object.entries(tallasGenero).forEach(([talla, val]) => {
	                                            let cantidad = 0;
	                                            if (Array.isArray(val)) {
	                                                cantidad = val.reduce((acc, item) => {
	                                                    const c = (item && typeof item === 'object') ? (parseInt(item.cantidad) || 0) : (parseInt(item) || 0);
	                                                    return acc + c;
	                                                }, 0);
	                                            } else if (val && typeof val === 'object') {
	                                                cantidad = parseInt(val.cantidad) || 0;
	                                            } else {
	                                                cantidad = parseInt(val) || 0;
	                                            }
	                                            if (cantidad > 0) {
	                                                items.push(`${talla}: <span style="color: #d32f2f; font-weight: bold;">${cantidad}</span>`);
	                                            }
	                                        });
	                                        if (items.length > 0) {
	                                            tallasLineas.push(`<strong>${generoLabel}:</strong> ${items.join(', ')}`);
	                                        }
	                                    }
	                                }
	                            });
                            
                            // Also check top-level keys that look like sizes (for non-nested formats)
                            if (tallasLineas.length === 0) {
                                const generos = Object.keys(tallasObj);
                                generos.forEach((genero) => {
                                    const tallasGenero = tallasObj[genero] || {};
                                    const items = [];
                                    if (typeof tallasGenero === 'object' && !Array.isArray(tallasGenero)) {
                                        Object.entries(tallasGenero).forEach(([talla, val]) => {
                                            let cantidad = 0;
                                            if (Array.isArray(val)) {
                                                cantidad = val.reduce((acc, item) => {
                                                    const c = (item && typeof item === 'object') ? (parseInt(item.cantidad) || 0) : (parseInt(item) || 0);
                                                    return acc + c;
                                                }, 0);
                                            } else if (val && typeof val === 'object') {
                                                cantidad = parseInt(val.cantidad) || 0;
                                            } else {
                                                cantidad = parseInt(val) || 0;
                                            }
                                            if (cantidad > 0) {
                                                items.push(`${talla}: <span style="color: #d32f2f; font-weight: bold;">${cantidad}</span>`);
                                            }
                                        });
                                    }
                                    if (items.length > 0) {
                                        tallasLineas.push(`<strong>${(genero || '').toString().toUpperCase()}:</strong> ${items.join(', ')}`);
                                    }
                                });
                            }
                            
                            if (tallasLineas.length > 0) {
                                html += `<strong>TALLAS</strong><br>${tallasLineas.join('<br>')}<br>`;
                            }
                        }

                        // OBSERVACIONES del proceso (debajo de tallas)
                        if (observacionProceso) {
                            html += `<strong>OBSERVACIONES:</strong><br>${observacionProceso.toUpperCase()}<br>`;
                        }
                    });
                }
            }
            
            descripcionHTML += `<div class="prenda-item" style="margin-bottom: 16px; line-height: 1.4; font-size: 0.75rem; color: #333;">
                ${html}
            </div>`;
            
            // Agregar separador solo entre prendas mostradas
            if (index < prendasActuales.length - 1) {
                descripcionHTML += `<hr style="border: none; border-top: 2px solid #ccc; margin: 16px 0;">`;
            }
        });
        
        // Actualizar total de prendas para el carousel
        window.totalBloquesPrendas = todasLasPrendas.length;
    }
    
    // Inyectar descripción en el DOM
    const descElement = document.getElementById('mobile-descripcion');
    if (descElement) {
        if (descripcionHTML) {
            descElement.innerHTML = descripcionHTML;
        } else {
            descElement.innerHTML = '<em style="font-size: 10px; color: #999;">Sin descripción</em>';
        }
    }
    
    // ACTUALIZAR TÍTULO DEL RECIBO CON EL PROCESO ACTUAL
    // Actualizar SIEMPRE que haya un procesoActualSeleccionado (incluso cuando regresas a índice 0)
    window.anexarObservacionReciboProcesoMobile({
        pedidoId: Number(data?.id || data?.pedido_id || data?.pedido_produccion_id || 0),
        tipoProceso: String(procesoActualSeleccionado || '').trim().toUpperCase(),
        prendasMostradas: Array.isArray(prendasActuales) ? prendasActuales : []
    });

    if (procesoActualSeleccionado) {
        const titleElement = document.querySelector('.receipt-title');
        if (titleElement) {
            const procesoTitulo = normalizarTituloRecibo(
                procesoActualSeleccionado,
                tipoReciboUpper
            );
            titleElement.textContent = 'RECIBO DE ' + procesoTitulo;
        }
        
        // ACTUALIZAR NÚMERO DE RECIBO CON EL CONSECUTIVO DEL PROCESO
        // FIX: Usar la prenda actualmente visible en el carousel, no la primera del array
        const numeroPedidoElement = document.getElementById('mobile-numero-pedido');
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const tipoReciboParam = String(urlParams.get('tipo_recibo') || '').trim().toUpperCase();
            const consecutivoParcialParam = String(urlParams.get('consecutivo_parcial') || '').trim();
            if (numeroPedidoElement && tipoReciboParam === 'PARCIAL' && consecutivoParcialParam) {
                numeroPedidoElement.textContent = '#' + consecutivoParcialParam;
                return;
            }
        } catch (e) {
            // noop
        }
        if (numeroPedidoElement && todasLasPrendas && todasLasPrendas.length > 0) {
            let reciboBuscado = null;
            
            // Obtener las prendas actualmente visibles en el carousel
            const reciboStartIdx = window.prendaCarouselIndex || 0;
            const reciboEndIdx = reciboStartIdx + PRENDAS_POR_PAGINA;
            const prendasVisibles = todasLasPrendas.slice(reciboStartIdx, reciboEndIdx);
            
            console.log('🔢 [NUMERO RECIBO] Buscando recibo para proceso:', procesoActualSeleccionado);
            console.log('🔢 [NUMERO RECIBO] Prendas visibles (carousel idx=' + reciboStartIdx + '):', prendasVisibles.map(function(p) { return p.nombre; }));
            
            // Primero buscar en las prendas visibles del carousel
            for (let i = 0; i < prendasVisibles.length; i++) {
                const prenda = prendasVisibles[i];
                
                console.log('🔢 [NUMERO RECIBO] Prenda visible', i, ':', prenda.nombre, '- Tiene recibos?:', !!prenda.recibos);
                
                if (prenda.recibos && typeof prenda.recibos === 'object' && !Array.isArray(prenda.recibos)) {
                    let reciboProceso = prenda.recibos[procesoActualSeleccionado];
                    
                    // Si no encuentra con el nombre exacto, buscar case-insensitive
                    if (!reciboProceso) {
                        const procesoBuscado = procesoActualSeleccionado.toUpperCase();
                        for (const [key, value] of Object.entries(prenda.recibos)) {
                            if (key.toUpperCase() === procesoBuscado && value !== null && value !== undefined) {
                                reciboProceso = value;
                                console.log('🔢 [NUMERO RECIBO] Encontrado con match case-insensitive:', key, '→', procesoBuscado);
                                break;
                            }
                        }
                    }
                    
                    console.log('🔢 [NUMERO RECIBO] reciboProceso para', procesoActualSeleccionado + ':', reciboProceso);
                    
                    if (reciboProceso) {
                        let numeroRecibo = null;
                        if (typeof reciboProceso === 'number') {
                            numeroRecibo = reciboProceso;
                        } else if (typeof reciboProceso === 'object' && reciboProceso.consecutivo_actual) {
                            numeroRecibo = reciboProceso.consecutivo_actual;
                        }
                        
                        if (numeroRecibo !== null && numeroRecibo !== undefined) {
                            numeroPedidoElement.textContent = '#' + numeroRecibo;
                            console.log(' [NUMERO RECIBO ACTUALIZADO] prenda:', prenda.nombre, procesoActualSeleccionado, '→ #' + numeroRecibo);
                            reciboBuscado = reciboProceso;
                            break;
                        }
                    }
                }
            }
            
            // Fallback: Si no se encontró en las prendas visibles, buscar en todas
            if (!reciboBuscado) {
                for (let i = 0; i < todasLasPrendas.length; i++) {
                    const prenda = todasLasPrendas[i];
                    if (prenda.recibos && typeof prenda.recibos === 'object' && !Array.isArray(prenda.recibos)) {
                        let reciboProceso = prenda.recibos[procesoActualSeleccionado];
                        if (!reciboProceso) {
                            const procesoBuscado = procesoActualSeleccionado.toUpperCase();
                            for (const [key, value] of Object.entries(prenda.recibos)) {
                                if (key.toUpperCase() === procesoBuscado && value !== null && value !== undefined) {
                                    reciboProceso = value;
                                    break;
                                }
                            }
                        }
                        if (reciboProceso) {
                            let numeroRecibo = null;
                            if (typeof reciboProceso === 'number') {
                                numeroRecibo = reciboProceso;
                            } else if (typeof reciboProceso === 'object' && reciboProceso.consecutivo_actual) {
                                numeroRecibo = reciboProceso.consecutivo_actual;
                            }
                            if (numeroRecibo !== null && numeroRecibo !== undefined) {
                                numeroPedidoElement.textContent = '#' + numeroRecibo;
                                console.log(' [NUMERO RECIBO FALLBACK]', prenda.nombre, procesoActualSeleccionado, '→ #' + numeroRecibo);
                                reciboBuscado = reciboProceso;
                                break;
                            }
                        }
                    }
                }
            }
            
            if (!reciboBuscado) {
                console.log(' [NUMERO RECIBO] No se encontró recibo para', procesoActualSeleccionado);
            }
        }
    }
    
    // La navegación por flechas de prendas fue removida (UX: evitar overlays/botones flotantes en mobile).
    
    console.log('📱 [RECIBO MOBILE]  ========== FIN llenarReciboCosturaMobile ==========');
};
