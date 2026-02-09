/**
 * PrendaEditor - Gestor de Edición de Prendas
 * 
 * Responsabilidad única: Gestionar carga, edición y guardado de prendas en modal
 * 
 * Integración: Origen automático desde cotización (CotizacionPrendaHandler)
 */
class PrendaEditor {
    constructor(options = {}) {
        this.notificationService = options.notificationService;
        this.modalId = options.modalId || 'modal-agregar-prenda-nueva';
        this.prendaEditIndex = null;
        this.cotizacionActual = options.cotizacionActual || null;
    }

    /**
     * Abrir modal para agregar o editar prenda
     */
    abrirModal(esEdicion = false, prendaIndex = null, cotizacionSeleccionada = null) {

        
        if (esEdicion && prendaIndex !== null && prendaIndex !== undefined) {
            this.prendaEditIndex = prendaIndex;
        } else {
            this.prendaEditIndex = null;
        }

        // Guardar cotización actual
        if (cotizacionSeleccionada) {
            this.cotizacionActual = cotizacionSeleccionada;
            console.log('[PrendaEditor.abrirModal] Cotización asignada:', {
                id: cotizacionSeleccionada.id,
                tipo_id: cotizacionSeleccionada.tipo_cotizacion_id
            });
        }

        // Preparar modal
        if (window.ModalCleanup) {

            if (esEdicion) {
                window.ModalCleanup.prepararParaEditar(prendaIndex);
            } else {
                window.ModalCleanup.prepararParaNueva();
            }
        } else {

        }

        // Cargar tipos de manga disponibles desde la BD
        if (typeof window.cargarTiposMangaDisponibles === 'function') {
            window.cargarTiposMangaDisponibles();
        }

        // Actualizar título modal según modo (si la función está disponible)
        if (typeof window.actualizarTituloModalPrenda === 'function') {
            window.actualizarTituloModalPrenda(esEdicion);
        }

        // Mostrar modal

        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.style.display = 'flex';
        } else {

        }
    }

    /**
     * Aplicar origen automático desde cotización
     * FUERZA origen = 'bodega' si cotización es Reflectivo o Logo
     * @private
     */
    aplicarOrigenAutomaticoDesdeCotizacion(prenda) {
        // Solo aplicar si hay cotización actual
        if (!this.cotizacionActual) {
            console.debug('[PrendaEditor] No hay cotización actual, omitiendo origen automático');
            return prenda;
        }

        // LÓGICA DIRECTA: Verificar tipo de cotización
        const cotizacion = this.cotizacionActual;
        const tipoCotizacionId = cotizacion.tipo_cotizacion_id;
        const tiposQueFuerzanBodega = ['Reflectivo', 'Logo'];
        
        // Obtener el nombre del tipo de cotización (puede estar en cotizacion.tipo_cotizacion.nombre o cotizacion.tipo_nombre)
        let nombreTipo = null;
        if (cotizacion.tipo_cotizacion && cotizacion.tipo_cotizacion.nombre) {
            nombreTipo = cotizacion.tipo_cotizacion.nombre;
        } else if (cotizacion.tipo_nombre) {
            nombreTipo = cotizacion.tipo_nombre;
        }

        console.log('[PrendaEditor] Analizando origen automático:', {
            tipoCotizacionId: tipoCotizacionId,
            nombreTipo: nombreTipo,
            esReflectivo: nombreTipo === 'Reflectivo' || tipoCotizacionId === 'Reflectivo',
            esLogo: nombreTipo === 'Logo' || tipoCotizacionId === 'Logo'
        });

        // Si es Reflectivo o Logo → FORZAR bodega sin importar de_bodega
        const esReflectivo = (nombreTipo && nombreTipo.toLowerCase() === 'reflectivo') || tipoCotizacionId === 'Reflectivo' || tipoCotizacionId === 2;
        const esLogo = (nombreTipo && nombreTipo.toLowerCase() === 'logo') || tipoCotizacionId === 'Logo' || tipoCotizacionId === 3;

        if (esReflectivo || esLogo) {
            prenda.origen = 'bodega';
            console.log('[PrendaEditor]  FORZANDO origen = "bodega" (tipo:', nombreTipo || tipoCotizacionId, ')');
        } else {
            // Para otros tipos, mantener comportamiento normal
            prenda.origen = prenda.origen || 'confeccion';
            console.log('[PrendaEditor] Origen = "' + prenda.origen + '" (tipo:', nombreTipo || tipoCotizacionId, ')');
        }

        console.log('[PrendaEditor] Origen aplicado:', {
            prenda: prenda.nombre_prenda || prenda.nombre,
            origen: prenda.origen,
            cotizacion: this.cotizacionActual.numero_cotizacion || this.cotizacionActual.id,
            tipo: nombreTipo || tipoCotizacionId
        });

        return prenda;
    }

    /**
     * Cargar telas desde prenda_telas_cot para cotizaciones REFLECTIVO/LOGO
     * Extrae: tela, color, referencia y fotos
     * @private
     */
    cargarTelasDesdeCtizacion(prenda) {
        if (!prenda.prenda_id || !prenda.cotizacion_id) {
            console.debug('[cargarTelasDesdeCtizacion] No hay prenda_id o cotizacion_id');
            return;
        }

        console.log('[cargarTelasDesdeCtizacion] 🧵 Cargando telas y variaciones de cotización:', {
            prenda_cot_id: prenda.prenda_id,
            cotizacion_id: prenda.cotizacion_id
        });

        // Fetch a API para obtener telas, variaciones y ubicaciones
        fetch(`/api/cotizaciones/${prenda.cotizacion_id}/prendas/${prenda.prenda_id}/telas-cotizacion`)
            .then(response => {
                if (!response.ok) {
                    console.warn('[cargarTelasDesdeCtizacion]  Endpoint no disponible, intentando fallback');
                    throw new Error('No endpoint');
                }
                return response.json();
            })
            .then(data => {
                const telas = data.telas || data.data?.telas || [];
                const variaciones = data.variaciones || data.data?.variaciones || [];
                const ubicaciones = data.ubicaciones || data.data?.ubicaciones || [];
                const descripcion = data.descripcion || data.data?.descripcion || '';
                
                console.log('[cargarTelasDesdeCtizacion]  Datos de cotización cargados:', {
                    telas_count: telas.length,
                    variaciones_count: variaciones.length,
                    ubicaciones_count: ubicaciones.length,
                    tiene_descripcion: !!descripcion
                });

                if (telas.length > 0) {
                    // Procesar telas y construir estructura
                    const telasAgregadas = telas.map(tela => {
                        // Extraer nombre de tela
                        const nombreTela = tela.nombre_tela || 
                                         tela.tela?.nombre || 
                                         tela.tela_nombre || 
                                         'N/A';

                        // Extraer color (ya viene como string directo del backend)
                        const colorNombre = tela.color || 'N/A';

                        // Extraer referencia
                        const referencia = tela.referencia || 
                                         tela.tela?.referencia || 
                                         'N/A';

                        // Procesar fotos de tela
                        const fotos = (tela.fotos || []).map(foto => ({
                            ruta_original: foto.ruta_original || '',
                            ruta_webp: foto.ruta_webp || '',
                            ruta_miniatura: foto.ruta_miniatura || '',
                            url: foto.ruta_webp || foto.ruta_original || ''
                        }));

                        return {
                            id: tela.id,
                            prenda_tela_cot_id: tela.id,
                            nombre_tela: nombreTela,
                            color: colorNombre,
                            referencia: referencia,
                            tela_id: tela.tela_id,
                            color_id: tela.color_id,
                            variante_prenda_cot_id: tela.variante_prenda_cot_id,
                            fotos: fotos
                        };
                    });

                    // Asignar telas a la prenda
                    prenda.telasAgregadas = telasAgregadas;

                    console.log('[cargarTelasDesdeCtizacion]  Telas procesadas:', {
                        cantidad: telasAgregadas.length,
                        telas: telasAgregadas.map(t => `${t.nombre_tela} - ${t.color}`)
                    });

                    // Actualizar display si existe
                    this.actualizarPreviewTelasCotizacion(telasAgregadas);
                }
                
                // Procesar variaciones desde la cotización
                if (variaciones && Array.isArray(variaciones) && variaciones.length > 0) {
                    console.log('[cargarTelasDesdeCtizacion] 🔄 Procesando variaciones:', {
                        cantidad: variaciones.length,
                        variaciones: variaciones
                    });
                    
                    // Asignar variaciones a la prenda
                    prenda.variacionesReflectivo = variaciones;
                    
                    // Aplicar variaciones al formulario
                    this.aplicarVariacionesReflectivo(variaciones);
                }
                
                // Procesar ubicaciones desde la cotización
                if (ubicaciones && Array.isArray(ubicaciones) && ubicaciones.length > 0) {
                    console.log('[cargarTelasDesdeCtizacion] 📍 Procesando ubicaciones:', {
                        cantidad: ubicaciones.length,
                        ubicaciones: ubicaciones
                    });
                    
                    // Asignar ubicaciones a la prenda
                    prenda.ubicacionesReflectivo = ubicaciones;
                    
                    // Aplicar ubicaciones al formulario
                    this.aplicarUbicacionesReflectivo(ubicaciones);
                }
                
                // Procesar descripción desde la cotización
                if (descripcion) {
                    console.log('[cargarTelasDesdeCtizacion] 📝 Descripción desde cotización:', descripcion);
                    prenda.descripcionReflectivo = descripcion;
                }
                
                // Re-cargar telas en el formulario después de procesar
                if (telas.length > 0) {
                    console.log('[cargarTelasDesdeCtizacion] 🔄 Re-cargando telas en el formulario');
                    this.cargarTelas(prenda);
                }
            })
            .catch(error => {
                console.warn('[cargarTelasDesdeCtizacion]  Error cargando datos de cotización:', error.message);
                // Continuar sin datos - no bloquear flujo
            });
    }

    /**
     * Aplicar variaciones de reflectivo al formulario
     * @private
     */
    aplicarVariacionesReflectivo(variaciones) {
        if (!variaciones || variaciones.length === 0) return;
        
        console.log('[aplicarVariacionesReflectivo] 🎨 Aplicando variaciones al formulario');
        
        // Mapeo de variaciones con sus elementos del formulario
        const mapeoVariaciones = {
            'manga': {
                checkbox: '#aplica-manga',
                input: '#manga-input',
                obs: '#manga-obs'
            },
            'bolsillos': {
                checkbox: '#aplica-bolsillos',
                input: null, // Bolsillos solo tiene observaciones
                obs: '#bolsillos-obs'
            },
            'broche': {
                checkbox: '#aplica-broche',
                input: '#broche-input',
                obs: '#broche-obs'
            },
            'broche/botón': {
                checkbox: '#aplica-broche',
                input: '#broche-input',
                obs: '#broche-obs'
            }
        };
        
        // Para cada variación, buscar el checkbox correspondiente y marcar si está checked
        variaciones.forEach((variacion) => {
            const varKey = variacion.variacion?.toLowerCase().trim();
            const config = mapeoVariaciones[varKey];
            
            if (!config) {
                console.warn(`[aplicarVariacionesReflectivo]  Variación desconocida: ${variacion.variacion}`);
                return;
            }
            
            // Marcar el checkbox si está checked
            if (variacion.checked) {
                const checkbox = document.querySelector(config.checkbox);
                if (checkbox) {
                    // Marcar el checkbox
                    checkbox.checked = true;
                    
                    // IMPORTANTE: Disparar el evento 'change' para que se ejecute manejarCheckVariacion()
                    // Esto es necesario para habilitar los campos de entrada
                    const changeEvent = new Event('change', { bubbles: true });
                    checkbox.dispatchEvent(changeEvent);
                    
                    // Pequeño delay para permitir que se ejecute manejarCheckVariacion()
                    setTimeout(() => {
                        // Habilitar y llenar los campos asociados
                        if (config.input) {
                            const inputField = document.querySelector(config.input);
                            if (inputField) {
                                inputField.disabled = false;
                                inputField.style.opacity = '1';
                                // Si hay un valor específico, llenarlo
                                // Por ahora dejaremos vacío si no hay datos específicos
                            }
                        }
                        
                        if (config.obs) {
                            const obsField = document.querySelector(config.obs);
                            if (obsField) {
                                obsField.disabled = false;
                                obsField.style.opacity = '1';
                                // Llenar la observación desde la BD
                                if (variacion.observacion) {
                                    obsField.value = variacion.observacion.toUpperCase();
                                }
                            }
                        }
                        
                        console.log(`[aplicarVariacionesReflectivo]  Variación "${variacion.variacion}" completamente configurada con observación: "${variacion.observacion}"`);
                    }, 50);
                    
                } else {
                    console.warn(`[aplicarVariacionesReflectivo]  No se encontró checkbox para: ${config.checkbox}`);
                }
            }
        });
    }

    /**
     * Aplicar ubicaciones de reflectivo al formulario
     * @private
     */
    aplicarUbicacionesReflectivo(ubicaciones) {
        if (!ubicaciones || ubicaciones.length === 0) return;
        
        console.log('[aplicarUbicacionesReflectivo] 📍 Aplicando ubicaciones al formulario');
        
        // Buscar contenedor de ubicaciones
        const contenedorUbicaciones = document.getElementById('ubicaciones-reflectivo') ||
                                     document.querySelector('[data-role="ubicaciones-reflectivo"]');
        
        if (!contenedorUbicaciones) {
            console.debug('[aplicarUbicacionesReflectivo] Contenedor de ubicaciones no encontrado');
            return;
        }
        
        // Limpiar y agregar ubicaciones
        let html = '';
        ubicaciones.forEach((ubi, idx) => {
            html += `
                <div class="ubicacion-item" style="margin-bottom: 1rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 4px;">
                    <div><strong>📍 ${ubi.ubicacion || 'Ubicación'}</strong></div>
                    <div style="color: #666; font-size: 0.9rem;">${ubi.descripcion || ''}</div>
                </div>
            `;
        });
        
        if (html) {
            contenedorUbicaciones.innerHTML = html;
            console.log(`[aplicarUbicacionesReflectivo]  ${ubicaciones.length} ubicaciones agregadas`);
        }
    }

    /**
     * Actualizar preview de telas desde cotización
     * @private
     */
    actualizarPreviewTelasCotizacion(telas) {
        const contenedorTelas = document.getElementById('contenedor-telas-cotizacion') || 
                               document.querySelector('[data-role="telas-cotizacion"]');
        
        if (!contenedorTelas) {
            console.debug('[actualizarPreviewTelasCotizacion] Contenedor de telas no encontrado');
            return;
        }

        console.log('[actualizarPreviewTelasCotizacion] 🎨 Actualizando preview de telas:', telas.length);

        // Limpiar contenedor
        contenedorTelas.innerHTML = '';

        // Agregar cada tela
        telas.forEach((tela, idx) => {
            const telaHTML = document.createElement('div');
            telaHTML.className = 'tela-cotizacion-item';
            telaHTML.innerHTML = `
                <div class="tela-info">
                    <span class="tela-nombre"><strong>${tela.nombre_tela}</strong></span>
                    <span class="tela-color">${tela.color}</span>
                    <span class="tela-referencia">Ref: ${tela.referencia}</span>
                </div>
                ${tela.fotos.length > 0 ? `
                    <div class="tela-fotos">
                        ${tela.fotos.map((foto, fidx) => `
                            <img 
                                src="${foto.url}" 
                                alt="Foto tela ${idx + 1}" 
                                title="${tela.nombre_tela}"
                                style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;"
                                onclick="console.log('Foto tela:', '${tela.nombre_tela}')"
                            />
                        `).join('')}
                    </div>
                ` : '<span class="sin-fotos">Sin fotos</span>'}
            `;
            contenedorTelas.appendChild(telaHTML);
        });

        console.log('[actualizarPreviewTelasCotizacion]  Preview actualizado');
    }
    cargarPrendaEnModal(prenda, prendaIndex) {
        console.log('🔄 [CARGAR-PRENDA] Iniciando carga de prenda en modal:', {
            prendaIndex,
            nombre: prenda.nombre_prenda,
            tieneProcesos: !!prenda.procesos,
            countProcesos: prenda.procesos?.length || 0
        });
        
        // Guardar referencia global para detectar si es cotización
        window.prendaActual = prenda;
        console.log('[cargarTallasYCantidades] 📋 window.prendaActual asignado:', {
            cotizacion_id: prenda.cotizacion_id,
            tipo: prenda.tipo,
            nombre: prenda.nombre_prenda
        });
        
        if (!prenda) {
            this.mostrarNotificacion('Prenda no válida', 'error');
            return;
        }

        try {
            // 🔴 APLICAR ORIGEN AUTOMÁTICO DESDE COTIZACIÓN
            const prendaProcesada = this.aplicarOrigenAutomaticoDesdeCotizacion(prenda);

            this.prendaEditIndex = prendaIndex;
            this.abrirModal(true, prendaIndex);
            this.llenarCamposBasicos(prendaProcesada);
            this.cargarImagenes(prendaProcesada);
            this.cargarTelas(prendaProcesada);
            
            // 🟠 CARGAR TELAS DESDE COTIZACIÓN (si es REFLECTIVO/LOGO)
            // Detectar tipos con múltiples formas: por nombre o por ID
            // tipo_cotizacion_id: 4 = Reflectivo, 3 = Logo, 2 = Bordado, 1 = Prenda
            const esReflectivo = this.cotizacionActual && (
                this.cotizacionActual.tipo_nombre === 'Reflectivo' ||
                (this.cotizacionActual.tipo_cotizacion && this.cotizacionActual.tipo_cotizacion.nombre === 'Reflectivo') ||
                this.cotizacionActual.tipo_cotizacion_id === 'Reflectivo' ||
                this.cotizacionActual.tipo_cotizacion_id === 4  // ID correcto para Reflectivo
            );
            const esLogo = this.cotizacionActual && (
                this.cotizacionActual.tipo_nombre === 'Logo' ||
                (this.cotizacionActual.tipo_cotizacion && this.cotizacionActual.tipo_cotizacion.nombre === 'Logo') ||
                this.cotizacionActual.tipo_cotizacion_id === 'Logo' ||
                this.cotizacionActual.tipo_cotizacion_id === 3  // ID para Logo
            );
            
            if (esReflectivo || esLogo) {
                this.cargarTelasDesdeCtizacion(prendaProcesada);
            }
            
            this.cargarVariaciones(prendaProcesada);  // CARGAR PRIMERO para que genero_id esté seleccionado antes de las tallas
            this.cargarTallasYCantidades(prendaProcesada);
            
            console.log(' [CARGAR-PRENDA] Sobre de cargar procesos...');
            this.cargarProcesos(prendaProcesada);
            
            this.cambiarBotonAGuardarCambios();
            console.log(' [CARGAR-PRENDA] Prenda cargada completamente');
            this.mostrarNotificacion('Prenda cargada para editar', 'success');
        } catch (error) {
            console.error(' [CARGAR-PRENDA] Error:', error);
            this.mostrarNotificacion(`Error al cargar prenda: ${error.message}`, 'error');
        }
    }

    /**
     * Llenar campos básicos del formulario
     * @private
     */
    llenarCamposBasicos(prenda) {
        console.log('🟦🟦🟦 [llenarCamposBasicos] INICIANDO CARGA DE CAMPOS BÁSICOS 🟦🟦🟦', {
            nombre: prenda.nombre_prenda,
            de_bodega: prenda.de_bodega,
            origen: prenda.origen,
            timestamp: new Date().toLocaleTimeString()
        });
        
        // ⏱️ ASEGURAR QUE EL DOM ESTÁ LISTO (pequeño delay para rendering)
        setTimeout(() => {
            this._llenarCamposBasicosInternal(prenda);
        }, 50);
    }

    _llenarCamposBasicosInternal(prenda) {
        const nombreField = document.getElementById('nueva-prenda-nombre');
        const descripcionField = document.getElementById('nueva-prenda-descripcion');
        const origenField = document.getElementById('nueva-prenda-origen-select');

        console.log('[llenarCamposBasicos] 🕐 DOM LISTO PARA LLENAR (después de timeout)');
        console.log('[llenarCamposBasicos] DEBUG Elementos encontrados:', {
            nombreField: !!nombreField,
            descripcionField: !!descripcionField,
            origenField: !!origenField,
            origenFieldTagName: origenField?.tagName,
            origenFieldOptions: origenField?.options?.length,
            modalVisible: !!document.getElementById('modal-agregar-prenda-nueva')?.offsetParent
        });
        
        if (nombreField) nombreField.value = prenda.nombre_prenda || '';
        if (descripcionField) descripcionField.value = prenda.descripcion || '';
        
        if (origenField) {
            console.log('[llenarCamposBasicos] Datos de origen ANTES:', {
                prendaOrigen: prenda.origen,
                prendaDeBodega: prenda.de_bodega,
                tipoDeBodega: typeof prenda.de_bodega
            });
            
            // 🔴 APLICAR ORIGEN AUTOMÁTICO AQUÍ TAMBIÉN
            // Si hay cotización, FUERZA el origen antes de llenar el campo
            if (this.cotizacionActual) {
                const tipoCotizacionId = this.cotizacionActual.tipo_cotizacion_id;
                let nombreTipo = null;
                if (this.cotizacionActual.tipo_cotizacion && this.cotizacionActual.tipo_cotizacion.nombre) {
                    nombreTipo = this.cotizacionActual.tipo_cotizacion.nombre;
                } else if (this.cotizacionActual.tipo_nombre) {
                    nombreTipo = this.cotizacionActual.tipo_nombre;
                }
                
                console.log('[llenarCamposBasicos] Detectada cotización:', {
                    tipo: tipoCotizacionId,
                    nombreTipo: nombreTipo,
                    esReflectivo: nombreTipo === 'Reflectivo' || tipoCotizacionId === 2,
                    esLogo: nombreTipo === 'Logo' || tipoCotizacionId === 3
                });
                
                // Verificar si es Reflectivo o Logo por NOMBRE o por ID
                const esReflectivo = nombreTipo === 'Reflectivo' || tipoCotizacionId === 'Reflectivo' || tipoCotizacionId === 2;
                const esLogo = nombreTipo === 'Logo' || tipoCotizacionId === 'Logo' || tipoCotizacionId === 3;
                
                if (esReflectivo || esLogo) {
                    prenda.origen = 'bodega';
                    console.log('[llenarCamposBasicos]  FORZANDO origen = "bodega" (cotización: ' + (nombreTipo || tipoCotizacionId) + ')');
                }
            }
            
            console.log('[llenarCamposBasicos] Datos de origen DESPUÉS:', {
                prendaOrigen: prenda.origen,
                prendaDeBodega: prenda.de_bodega
            });
            
            // Función para normalizar texto (remover acentos)
            const normalizarTexto = (texto) => {
                return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
            };
            
            // Determinar origen: PRIORIDAD CORRECTA
            let origen = null;
            
            // PRIMERO: usar prenda.origen (que ya puede haber sido forzado arriba)
            if (prenda.origen) {
                origen = prenda.origen;
                console.log('[llenarCamposBasicos] Usando prenda.origen:', origen);
            }
            // SEGUNDO: verificar de_bodega (campo de la BD)
            //  IMPORTANTE: Usar == para comparación flexible (1 == true, 0 == false)
            else if (prenda.de_bodega !== undefined && prenda.de_bodega !== null) {
                // Si de_bodega es 1, true o '1' → bodega
                // Si de_bodega es 0, false o '0' → confeccion
                if (prenda.de_bodega == 1 || prenda.de_bodega === true || prenda.de_bodega === '1') {
                    origen = 'bodega';
                    console.log('[llenarCamposBasicos]  Usando de_bodega=true → origen: bodega');
                } else {
                    // Cualquier otro valor falsy (0, false, '0', null) → confeccion
                    origen = 'confeccion';
                    console.log('[llenarCamposBasicos]  Usando de_bodega=false → origen: confeccion');
                }
            }
            
            // SI NO hay ninguno, usar default
            if (!origen) {
                origen = 'confeccion';
                console.log('[llenarCamposBasicos] Usando default: confeccion');
            }
            
            console.log('[llenarCamposBasicos] Origen final determinado:', {
                origen: origen,
                normalizado: normalizarTexto(origen),
                de_bodega: prenda.de_bodega,
                comparacion_numerica: (prenda.de_bodega == 1) ? 'bodega' : 'confeccion'
            });
            
            const origenNormalizado = normalizarTexto(origen);
            let encontrado = false;
            
            // DEBUG: Mostrar todas las opciones disponibles
            console.log('[llenarCamposBasicos] Opciones disponibles en SELECT:');
            for (let i = 0; i < origenField.options.length; i++) {
                const opt = origenField.options[i];
                const optTextNormalizado = normalizarTexto(opt.textContent);
                const optValueNormalizado = normalizarTexto(opt.value);
                console.log(`  [${i}] value="${opt.value}" (${optValueNormalizado}) | text="${opt.textContent}" (${optTextNormalizado})`);
            }
            
            // PASO 1: INTENTAR COINCIDIR por value exacto (sin normalización)
            console.log('[llenarCamposBasicos] PASO 1: Buscando coincidencia exacta...');
            for (let opt of origenField.options) {
                if (opt.value === origen) {
                    console.log('[llenarCamposBasicos]  PASO 1: Coincidencia exacta por VALUE:', {
                        optValue: opt.value,
                        origen: origen
                    });
                    origenField.value = opt.value;
                    origenField.selectedIndex = Array.from(origenField.options).indexOf(opt);
                    encontrado = true;
                    break;
                }
            }
            
            // PASO 2: Si no encontró, intentar por value normalizado
            if (!encontrado) {
                console.log('[llenarCamposBasicos] PASO 2: Buscando coincidencia normalizada...');
                for (let opt of origenField.options) {
                    const optValueNormalizado = normalizarTexto(opt.value);
                    if (optValueNormalizado === origenNormalizado) {
                        console.log('[llenarCamposBasicos]  PASO 2: Coincidencia normalizada:', {
                            optValue: opt.value,
                            origenNormalizado: origenNormalizado,
                            asignando: opt.value
                        });
                        origenField.value = opt.value;
                        origenField.selectedIndex = Array.from(origenField.options).indexOf(opt);
                        encontrado = true;
                        break;
                    }
                }
            }
            
            // PASO 3: Si aún no encontró, intentar asignación directa
            if (!encontrado) {
                console.log('[llenarCamposBasicos] PASO 3: Asignación directa del valor...');
                origenField.value = origen;
                
                // Forzar con setAttribute si value no funcionó
                if (origenField.value !== origen) {
                    console.log('[llenarCamposBasicos]  value no funcionó, intentando setAttribute...');
                    for (let i = 0; i < origenField.options.length; i++) {
                        if (origenField.options[i].value === origen) {
                            origenField.selectedIndex = i;
                            console.log('[llenarCamposBasicos]  PASO 3B: setAttribute funcionó, selectedIndex=', i);
                            encontrado = true;
                            break;
                        }
                    }
                } else {
                    console.log('[llenarCamposBasicos]  PASO 3: Asignación directa exitosa');
                    encontrado = true;
                }
            }
            
            // VERIFICACIÓN FINAL
            const valorFinal = origenField.value;
            const coincide = (valorFinal === origen) || (normalizarTexto(valorFinal) === origenNormalizado);
            
            console.log('[llenarCamposBasicos]  VERIFICACIÓN FINAL:', {
                origenEsperado: origen,
                valorEnSelect: valorFinal,
                coincide: coincide,
                selectedIndex: origenField.selectedIndex,
                selectedOption: origenField.options[origenField.selectedIndex]?.value,
                encontradoCorrectamente: encontrado
            });
            
            if (!coincide) {
                console.error('[llenarCamposBasicos] ❌❌ FALLO: El select no tiene el valor correcto!', {
                    origen,
                    valorEnSelect: valorFinal,
                    opcionesDisponibles: Array.from(origenField.options).map((opt, idx) => ({idx, value: opt.value, text: opt.textContent}))
                });
            } else {
                console.log('🟢🟢🟢 [llenarCamposBasicos]  ÉXITO: SELECT ORIGEN ESTABLECIDO CORRECTAMENTE 🟢🟢🟢', {
                    origenEsperado: origen,
                    valorEnSelect: valorFinal,
                    selectedIndex: origenField.selectedIndex,
                    selectedOptionText: origenField.options[origenField.selectedIndex]?.textContent,
                    coincideConOrigen: coincide
                });
            }
            
            // Disparar evento de cambio para que se actualice la UI
            origenField.dispatchEvent(new Event('change', { bubbles: true }));
            
            //  Forzar reflow para asegurar que el navegador renderice el cambio
            void origenField.offsetHeight;
        } else {
            console.error('[llenarCamposBasicos] ❌ SELECT #nueva-prenda-origen-select NO encontrado en el DOM');
        }
    }

    /**
     * Cargar imágenes en el modal
     * @private
     */
    cargarImagenes(prenda) {
        console.log('🖼️ [CARGAR-IMAGENES] Iniciando carga de imágenes:', {
            tieneImagenes: !!prenda.imagenes,
            count: prenda.imagenes?.length || 0,
            tienefotos: !!prenda.fotos,
            countFotos: prenda.fotos?.length || 0,
            tieneProcesos: !!prenda.procesos,
            procesosCount: prenda.procesos ? Object.keys(prenda.procesos).length : 0
        });

        //  VERIFICAR/CREAR SERVICIO SI NO EXISTE (para supervisor de pedidos)
        if (!window.imagenesPrendaStorage) {
            console.log(' [CARGAR-IMAGENES] Creando imagenesPrendaStorage para supervisor de pedidos...');
            try {
                // Verificar si ImageStorageService está disponible
                if (typeof ImageStorageService !== 'undefined') {
                    window.imagenesPrendaStorage = new ImageStorageService(3);
                    console.log(' [CARGAR-IMAGENES] imagenesPrendaStorage creado exitosamente');
                } else {
                    console.warn(' [CARGAR-IMAGENES] ImageStorageService no disponible, creando fallback manual');
                    // Crear fallback manual básico
                    window.imagenesPrendaStorage = {
                        images: [],
                        limpiar: function() {
                            this.images = [];
                            console.log('🧹 [CARGAR-IMAGENES] Storage limpiado (fallback)');
                        },
                        agregarImagen: function(file) {
                            //  IMPORTANTE: Retornar una Promise para mantener consistencia
                            return new Promise((resolve, reject) => {
                                if (!file || !file.type.startsWith('image/')) {
                                    reject(new Error('INVALID_FILE'));
                                    return;
                                }
                                
                                if (this.images.length >= 3) {
                                    reject(new Error('MAX_LIMIT'));
                                    return;
                                }
                                
                                if (file instanceof File) {
                                    this.images.push({
                                        previewUrl: URL.createObjectURL(file),
                                        nombre: file.name,
                                        tamaño: file.size,
                                        file: file
                                    });
                                    resolve({ success: true, images: this.images });
                                } else {
                                    reject(new Error('INVALID_FILE'));
                                }
                            });
                        },
                        agregarUrl: function(urlOImagen, nombre = 'imagen') {
                            // Si es un objeto completo de imagen, preservar todos los campos
                            // Si es solo una URL, crear objeto básico
                            let imagenObj;
                            if (typeof urlOImagen === 'string') {
                                imagenObj = {
                                    previewUrl: urlOImagen,
                                    nombre: nombre,
                                    tamaño: 0,
                                    file: null,
                                    urlDesdeDB: true
                                };
                            } else {
                                // Es un objeto - preservar TODOS los campos
                                imagenObj = {
                                    id: urlOImagen.id,
                                    prenda_foto_id: urlOImagen.prenda_foto_id,
                                    previewUrl: urlOImagen.previewUrl || urlOImagen.url || urlOImagen.ruta,
                                    url: urlOImagen.url,
                                    ruta_original: urlOImagen.ruta_original,
                                    ruta_webp: urlOImagen.ruta_webp,
                                    nombre: urlOImagen.nombre || nombre,
                                    tamaño: urlOImagen.tamaño || 0,
                                    file: null,
                                    urlDesdeDB: true
                                };
                            }
                            this.images.push(imagenObj);
                        },
                        obtenerImagenes: function() {
                            return this.images;
                        },
                        establecerImagenes: function(nuevasImagenes) {
                            if (!Array.isArray(nuevasImagenes)) {
                                console.warn(' [ImageStorageService (fallback).establecerImagenes] No es un array válido');
                                return;
                            }
                            
                            // Limpiar URLs de imágenes que serán reemplazadas
                            this.images.forEach(img => {
                                if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                                    URL.revokeObjectURL(img.previewUrl);
                                }
                            });
                            
                            // Normalizar nuevas imágenes: asegurar que tienen previewUrl
                            const imagenesNormalizadas = nuevasImagenes.map(img => {
                                // Si no tiene previewUrl, usar url, ruta, o ruta_webp
                                if (!img.previewUrl && (img.url || img.ruta || img.ruta_webp)) {
                                    return {
                                        ...img,
                                        previewUrl: img.url || img.ruta || img.ruta_webp
                                    };
                                }
                                return img;
                            });
                            
                            // Reemplazar el array
                            this.images = imagenesNormalizadas || [];
                            console.log(' [ImageStorageService (fallback).establecerImagenes] Array sincronizado y normalizado, ahora hay', this.images.length, 'imágenes');
                        }
                    };
                    console.log(' [CARGAR-IMAGENES] Fallback manual creado');
                }
            } catch (error) {
                console.error('❌ [CARGAR-IMAGENES] Error creando storage:', error);
                return;
            }
        }

        // PRIORIDAD 0: imagenes (formulario con archivos)
        let imagenesACargar = null;
        let origen = 'desconocido';

        if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
            // Verificar si son File/Blob o URL (formulario vs BD)
            const primerItem = prenda.imagenes[0];
            
            if (primerItem instanceof File || primerItem.file instanceof File) {
                // Formulario: archivos File
                console.log(' [CARGAR-IMAGENES] Detectado: imagenes de FORMULARIO (File objects)');
                imagenesACargar = prenda.imagenes;
                origen = 'formulario';
            } else if (typeof primerItem === 'string' || (primerItem && (primerItem.url || primerItem.ruta))) {
                // BD: URLs/strings
                console.log(' [CARGAR-IMAGENES] Detectado: imagenes de BD (URLs)');
                imagenesACargar = prenda.imagenes;
                origen = 'bd-urls';
            }
        }

        // PRIORIDAD 1: fotos (BD alternativo)
        if (!imagenesACargar && prenda.fotos && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {
            console.log(' [CARGAR-IMAGENES] Detectado: fotos de BD (alternativo)');
            imagenesACargar = prenda.fotos;
            origen = 'bd-fotos';
        }

        // ✨ NUEVO: PRIORIDAD 2: Imágenes de procesos (reflectivo, logo, etc)
        // Si viene de una cotización con procesos, usar esas imágenes del proceso
        if (!imagenesACargar && prenda.procesos && typeof prenda.procesos === 'object' && Object.keys(prenda.procesos).length > 0) {
            console.log('✨ [CARGAR-IMAGENES] Detectado: procesos con imágenes (reflectivo/logo)');
            
            // Buscar el primer proceso que tenga imágenes
            for (const [tipoProceso, dataProceso] of Object.entries(prenda.procesos)) {
                if (dataProceso.imagenes && Array.isArray(dataProceso.imagenes) && dataProceso.imagenes.length > 0) {
                    console.log(` [CARGAR-IMAGENES] Encontradas ${dataProceso.imagenes.length} imágenes del proceso "${tipoProceso}"`);
                    imagenesACargar = dataProceso.imagenes;
                    origen = `procesos-${tipoProceso}`;
                    break;
                }
            }
        }

        // Si no hay imágenes, retornar
        if (!imagenesACargar || imagenesACargar.length === 0) {
            console.log(' [CARGAR-IMAGENES] No hay imágenes para cargar');
            return;
        }

        if (window.imagenesPrendaStorage) {
            console.log(`🔄 [CARGAR-IMAGENES] Limpiando y cargando ${imagenesACargar.length} imágenes (origen: ${origen})`);
            window.imagenesPrendaStorage.limpiar();

            imagenesACargar.forEach((img, idx) => {
                this.procesarImagen(img, idx);
            });

            this.actualizarPreviewImagenes(imagenesACargar);
            
            // ACTUALIZAR PREVIEW DIRECTAMENTE SIN DEPENDER DE actualizarPreviewPrenda
            setTimeout(() => {
                console.log('[cargarImagenes] 🎬 Actualizando preview directamente...');
                const preview = document.getElementById('nueva-prenda-foto-preview');
                if (preview && window.imagenesPrendaStorage.images && window.imagenesPrendaStorage.images.length > 0) {
                    const primerImg = window.imagenesPrendaStorage.images[0];
                    const urlImg = primerImg.previewUrl;
                    
                    console.log('[cargarImagenes] 🖼️ URL imagen:', urlImg);
                    
                    preview.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = urlImg;
                    img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; cursor: pointer;';
                    preview.appendChild(img);
                    
                    // Agregar evento click para abrir galería
                    preview.onclick = (e) => {
                        e.stopPropagation();
                        if (window.mostrarGaleriaImagenesPrenda) {
                            const imagenes = window.imagenesPrendaStorage.images.map(img => ({
                                ...img,
                                url: img.previewUrl || img.url || img.ruta
                            }));
                            window.mostrarGaleriaImagenesPrenda(imagenes, 0, 0);
                        }
                    };
                    
                    console.log('[cargarImagenes]  Imagen insertada en el DOM con evento click');
                } else {
                    console.warn('[cargarImagenes]  Preview no encontrado o sin imágenes');
                }
            }, 100);
            
            console.log(` [CARGAR-IMAGENES] ${imagenesACargar.length} imágenes cargadas desde ${origen}`);
        } else {
            console.error(' [CARGAR-IMAGENES] Aún no hay imagenesPrendaStorage disponible después del intento de creación');
        }
    }

    /**
     * Procesar una imagen individual
     * @private
     */
    procesarImagen(img, idx = 0) {
        if (!img) {
            console.log(`   [PROCESAR-IMAGEN] Imagen ${idx} es null/undefined`);
            return;
        }

        // CASO 1: img es un File directamente (formulario)
        if (img instanceof File) {
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: File object detectado`);
            window.imagenesPrendaStorage.agregarImagen(img);
        }
        // CASO 2: img es objeto con .file que es un File (wrapper)
        else if (img.file instanceof File) {
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: Wrapper con File detectado`);
            window.imagenesPrendaStorage.agregarImagen(img.file);
        }
        // CASO 3: img es objeto con URL (BD)
        else if (img.url || img.ruta || img.ruta_webp || img.ruta_original) {
            const urlImagen = img.url || img.ruta || img.ruta_webp || img.ruta_original;
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: URL de BD:`, urlImagen);
            
            // Usar el método agregarUrl si existe (fallback manual) o agregar directamente
            // Pasar el objeto completo para preservar todos los campos (id, ruta_original, etc.)
            if (window.imagenesPrendaStorage.agregarUrl) {
                window.imagenesPrendaStorage.agregarUrl(img, `imagen_${idx}.webp`);
            } else {
                // Método original para ImageStorageService
                if (!window.imagenesPrendaStorage.images) {
                    window.imagenesPrendaStorage.images = [];
                }
                window.imagenesPrendaStorage.images.push({
                    previewUrl: urlImagen,
                    nombre: `imagen_${idx}.webp`,
                    tamaño: 0,
                    file: null,
                    urlDesdeDB: true
                });
            }
        }
        // CASO 4: img es string URL (BD alternativo)
        else if (typeof img === 'string') {
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: String URL:`, img);
            
            // Usar el método agregarUrl si existe (fallback manual) o agregar directamente
            if (window.imagenesPrendaStorage.agregarUrl) {
                window.imagenesPrendaStorage.agregarUrl(img, `imagen_${idx}.webp`);
            } else {
                // Método original para ImageStorageService
                if (!window.imagenesPrendaStorage.images) {
                    window.imagenesPrendaStorage.images = [];
                }
                window.imagenesPrendaStorage.images.push({
                    previewUrl: img,
                    nombre: `imagen_${idx}.webp`,
                    tamaño: 0,
                    file: null,
                    urlDesdeDB: true
                });
            }
        }
        // CASO 5: Blob (también formulario)
        else if (img instanceof Blob) {
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: Blob object detectado`);
            window.imagenesPrendaStorage.agregarImagen(img);
        }
        else {
            console.warn(`   [PROCESAR-IMAGEN] Imagen ${idx}: Formato desconocido:`, typeof img, img);
        }
    }

    /**
     * Actualizar preview de imágenes
     * @private
     */
    actualizarPreviewImagenes(imagenes) {
        console.log('[actualizarPreviewImagenes] 📸 Actualizando preview con', imagenes?.length || 0, 'imágenes');
        console.log('[actualizarPreviewImagenes] 📦 Storage tiene:', window.imagenesPrendaStorage?.images?.length || 0, 'imágenes');
        
        if (window.actualizarPreviewPrenda) {
            console.log('[actualizarPreviewImagenes]  Llamando a window.actualizarPreviewPrenda()');
            window.actualizarPreviewPrenda();
            return;
        }

        const preview = document.getElementById('nueva-prenda-foto-preview');
        const contador = document.getElementById('nueva-prenda-foto-contador');
        
        console.log('[actualizarPreviewImagenes] 🔍 Preview element:', preview ? 'ENCONTRADO' : 'NO ENCONTRADO');

        if (preview && window.imagenesPrendaStorage.images.length > 0) {
            const primerImg = window.imagenesPrendaStorage.images[0];
            const urlImg = primerImg.previewUrl || primerImg.url;
            
            console.log('[actualizarPreviewImagenes] 🖼️ Mostrando imagen:', urlImg);

            preview.style.backgroundImage = `url('${urlImg}')`;
            preview.style.cursor = 'pointer';

            if (contador && window.imagenesPrendaStorage.images.length > 1) {
                contador.textContent = window.imagenesPrendaStorage.images.length;
            }
        } else {
            console.log('[actualizarPreviewImagenes]  No hay imágenes para mostrar');
        }
    }

    /**
     * Cargar telas en el modal
     * @private
     */
    cargarTelas(prenda) {
        console.log('[cargarTelas]  Cargando telas:', prenda.telasAgregadas);
        
        // ===== DEBUG: Ver estructura completa de prenda =====
        console.group('[cargarTelas] 🔍 ESTRUCTURA COMPLETA DE PRENDA');
        console.log('prenda.telasAgregadas:', prenda.telasAgregadas);
        console.log('prenda.colores_telas:', prenda.colores_telas);
        console.log('prenda keys:', Object.keys(prenda));
        console.groupEnd();
        
        // ===== TRANSFORMAR colores_telas (BD) a telasAgregadas (frontend) =====
        if (!prenda.telasAgregadas || prenda.telasAgregadas.length === 0) {
            if (prenda.colores_telas && prenda.colores_telas.length > 0) {
                console.log('[cargarTelas] 🔄 Transformando colores_telas a telasAgregadas');
                console.log('[cargarTelas] colores_telas ANTES:', JSON.stringify(prenda.colores_telas, null, 2));
                console.log('[cargarTelas] Total de colores_telas:', prenda.colores_telas.length);
                
                prenda.telasAgregadas = prenda.colores_telas.map((ct, idx) => {
                    console.log(`[cargarTelas] Procesando colorTela ${idx}:`, {
                        id: ct.id,
                        color: ct.color_nombre,
                        tela: ct.tela_nombre,
                        fotos_count: ct.fotos ? ct.fotos.length : (ct.fotos_tela ? ct.fotos_tela.length : 0),
                    });
                    
                    // Obtener fotos: puede venir en ct.fotos (nuevo) o ct.fotos_tela (antiguo)
                    let fotosArray = [];
                    if (ct.fotos && Array.isArray(ct.fotos) && ct.fotos.length > 0) {
                        fotosArray = ct.fotos;
                        console.log(`[cargarTelas] CT ${idx}: Usando ct.fotos (${ct.fotos.length} items)`);
                    } else if (ct.fotos_tela && Array.isArray(ct.fotos_tela) && ct.fotos_tela.length > 0) {
                        fotosArray = ct.fotos_tela;
                        console.log(`[cargarTelas] CT ${idx}: Usando ct.fotos_tela (${ct.fotos_tela.length} items)`);
                    } else {
                        console.log(`[cargarTelas] CT ${idx}: SIN FOTOS`);
                    }
                    
                    const transformed = {
                        id: ct.id,
                        nombre_tela: ct.tela_nombre || '(Sin nombre)',
                        color: ct.color_nombre || '(Sin color)',
                        referencia: ct.referencia || ct.tela_referencia || '',
                        imagenes: fotosArray.map(foto => ({
                            url: foto.ruta_webp || foto.ruta_original || foto.url || '',
                            ruta_webp: foto.ruta_webp || '',
                            ruta_original: foto.ruta_original || '',
                            previewUrl: foto.ruta_webp || foto.ruta_original || foto.url || ''
                        }))
                    };
                    console.log(`[cargarTelas] Tela ${idx} transformada:`, {
                        nombre: transformed.nombre_tela,
                        color: transformed.color,
                        referencia: transformed.referencia,
                        fotosCount: transformed.imagenes.length,
                        fotos: transformed.imagenes
                    });
                    return transformed;
                });
                console.log('[cargarTelas]  Transformación completada:', prenda.telasAgregadas);
            } else if (prenda.variantes && Array.isArray(prenda.variantes)) {
                // ===== EXTRAER TELAS DESDE TODAS LAS VARIANTES (solución directa) =====
                console.log('[cargarTelas] 🔄 Extrayendo telas desde TODAS las variantes');
                console.log('[cargarTelas]  Total de variantes a procesar:', prenda.variantes.length);
                
                // Inicializar array para telas
                const telasAgregadasTemp = [];
                
                // Recorremos todas las variantes
                prenda.variantes.forEach((variante, varianteIndex) => {
                    console.log(`[cargarTelas] 📦 [Variante ${varianteIndex}] Procesando variante:`, {
                        tipo_manga: variante.tipo_manga,
                        tiene_bolsillos: variante.tiene_bolsillos,
                        tiene_telas_multiples: !!(variante.telas_multiples),
                        telas_multiples_count: variante.telas_multiples?.length || 0
                    });
                    
                    // Verificar si esta variante tiene telas_multiples
                    if (variante.telas_multiples && Array.isArray(variante.telas_multiples)) {
                        console.log(`[cargarTelas] 🧵 [Variante ${varianteIndex}] Encontradas ${variante.telas_multiples.length} telas`);
                        
                        // Recorrer todas las telas de esta variante
                        variante.telas_multiples.forEach((tela, telaIndex) => {
                            console.log(`[cargarTelas]  [Tela ${telaIndex}] Extrayendo:`, {
                                tela: tela.tela,
                                color: tela.color,
                                referencia: tela.referencia,
                                descripcion: tela.descripcion,
                                imagenes_count: tela.imagenes?.length || 0
                            });
                            
                            // Extraer y validar la referencia directamente
                            const referenciaExtraida = (tela.referencia !== undefined && tela.referencia !== null && tela.referencia !== '') 
                                ? String(tela.referencia).trim() 
                                : '';
                            
                            // Crear objeto de tela con todas las propiedades
                            const telaCompleta = {
                                id: tela.id || null,
                                nombre_tela: tela.tela || tela.nombre_tela || '',
                                color: tela.color || '',
                                referencia: referenciaExtraida, // <-- AQUÍ SE ASEGURA DE COPIAR LA REFERENCIA
                                descripcion: tela.descripcion || '',
                                grosor: tela.grosor || '',
                                composicion: tela.composicion || '',
                                imagenes: Array.isArray(tela.imagenes) ? tela.imagenes : [],
                                origen: 'variante_directa_modal',
                                variante_index: varianteIndex,
                                tela_index: telaIndex
                            };
                            
                            // Agregar al array de telas
                            telasAgregadasTemp.push(telaCompleta);
                            
                            console.log(`[cargarTelas]  [Tela ${telaIndex}] Agregada correctamente:`, {
                                nombre: telaCompleta.nombre_tela,
                                color: telaCompleta.color,
                                referencia: `"${telaCompleta.referencia}"`,
                                descripcion: telaCompleta.descripcion
                            });
                        });
                    } else {
                        console.log(`[cargarTelas]  [Variante ${varianteIndex}] No tiene telas_multiples válido`);
                    }
                });
                
                // Asignar el resultado final
                prenda.telasAgregadas = telasAgregadasTemp;
                
                console.log('[cargarTelas]  RESULTADO FINAL DE EXTRACCIÓN DIRECTA:');
                console.log(`[cargarTelas]  Total de telas extraídas: ${prenda.telasAgregadas.length}`);
                
                // LOG FINAL: Verificar referencias extraídas
                console.log('[cargarTelas] � RESUMEN DE REFERENCIAS EXTRAÍDAS:');
                prenda.telasAgregadas.forEach((tela, idx) => {
                    console.log(`  [${idx}] "${tela.nombre_tela}" - "${tela.color}" -> referencia: "${tela.referencia}" | descripción: "${tela.descripcion}"`);
                });
            } else {
                console.warn('[cargarTelas]  No hay colores_telas ni telas_multiples para procesar');
                prenda.telasAgregadas = [];
            }
        } else {
            console.log('[cargarTelas]  telasAgregadas ya tiene datos, no transformar');
        }
        
        // Intentar cargar desde telasAgregadas (prendas nuevas Y prendas de BD editadas)
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            console.log('[cargarTelas] ✓ Telas disponibles:', prenda.telasAgregadas.length);
            
            // 🔍 NUEVA LÓGICA: Verificar si las referencias están vacías y buscar en variantes
            const referenciasVacias = prenda.telasAgregadas.some(tela => !tela.referencia || tela.referencia === '');
            
            if (referenciasVacias) {
                console.log('[cargarTelas] 🔄 Referencias vacías detectadas, buscando en variantes para enriquecer');
                console.log('[cargarTelas] 🔍 ESTRUCTURA DE VARIANTES:', {
                    tiene_variantes: !!prenda.variantes,
                    variantes_es_array: Array.isArray(prenda.variantes),
                    variantes_tiene_telas_multiples: !!(prenda.variantes?.telas_multiples),
                    variantes_tipo: typeof prenda.variantes,
                    variantes_keys: prenda.variantes ? Object.keys(prenda.variantes) : [],
                    variantes_completo: prenda.variantes
                });
                
                // DEBUG: Mostrar estructura completa de variantes
                if (prenda.variantes) {
                    console.log('[cargarTelas] 🔍 ESTRUCTURA COMPLETA DE VARIANTES:');
                    console.log(JSON.stringify(prenda.variantes, null, 2));
                }
                
                let variantesParaProcesar = [];
                
                // CASO 1: variantes es un array de objetos
                if (Array.isArray(prenda.variantes)) {
                    variantesParaProcesar = prenda.variantes;
                    console.log('[cargarTelas] 📦 Usando variantes como array');
                }
                // CASO 2: variantes es un objeto con telas_multiples
                else if (prenda.variantes && typeof prenda.variantes === 'object' && !Array.isArray(prenda.variantes)) {
                    // Verificar si tiene telas_multiples directamente o si es un array de variantes
                    if (prenda.variantes.telas_multiples && Array.isArray(prenda.variantes.telas_multiples)) {
                        variantesParaProcesar = [prenda.variantes]; // Envolver en array para procesamiento uniforme
                        console.log('[cargarTelas] 📦 Usando variantes como objeto con telas_multiples');
                    } else if (Array.isArray(prenda.variantes)) {
                        variantesParaProcesar = prenda.variantes;
                        console.log('[cargarTelas] 📦 Usando variantes como array (corrección)');
                    } else {
                        console.log('[cargarTelas]  Estructura de variantes no reconocida, mostrando todas las propiedades:');
                        console.log('[cargarTelas] 🔍 Propiedades de variantes:', Object.keys(prenda.variantes));
                        
                        // Buscar telas_multiples en cualquier propiedad
                        for (const [key, value] of Object.entries(prenda.variantes)) {
                            if (key.includes('tela') || key.includes('multiple')) {
                                console.log(`[cargarTelas] 🔍 Propiedad candidata:`, { key, value, isArray: Array.isArray(value) });
                            }
                        }
                    }
                }
                // CASO 3: prenda tiene telas_multiples directamente
                else if (prenda.telas_multiples && Array.isArray(prenda.telas_multiples)) {
                    variantesParaProcesar = [{ telas_multiples: prenda.telas_multiples }]; // Crear estructura artificial
                    console.log('[cargarTelas] 📦 Usando telas_multiples directamente en prenda');
                }
                
                if (variantesParaProcesar.length > 0) {
                    console.log(`[cargarTelas]  Procesando ${variantesParaProcesar.length} estructuras de variantes`);
                    
                    // Crear mapa de telas existentes para enriquecer
                    const mapaTelasExistentes = new Map();
                    prenda.telasAgregadas.forEach((tela, index) => {
                        const clave = `${tela.nombre_tela}|${tela.color}`;
                        const claveNormalizada = clave.toLowerCase().trim();
                        mapaTelasExistentes.set(claveNormalizada, { index, tela, claveOriginal: clave });
                        console.log(`[cargarTelas] 📍 Tela existente registrada: "${clave}" -> normalizada: "${claveNormalizada}" (índice ${index})`);
                    });
                    
                    // Recorrer variantes para buscar referencias faltantes
                    variantesParaProcesar.forEach((variante, varianteIndex) => {
                        console.log(`[cargarTelas] 🔍 [Estructura ${varianteIndex}] Buscando referencias faltantes`);
                        
                        if (variante.telas_multiples && Array.isArray(variante.telas_multiples)) {
                            variante.telas_multiples.forEach((tela, telaIndex) => {
                                const nombre_tela = tela.tela || tela.nombre_tela || '';
                                const color = tela.color || '';
                                const referencia = tela.referencia || '';
                                const clave = `${nombre_tela}|${color}`;
                                
                                console.log(`[cargarTelas]  Analizando tela:`, {
                                    nombre_tela,
                                    color,
                                    referencia: `"${referencia}"`,
                                    clave,
                                    claveNormalizada: clave.toLowerCase().trim()
                                });
                                
                                // Buscar si existe una tela con esta combinación y sin referencia
                                const claveNormalizada = clave.toLowerCase().trim();
                                if (mapaTelasExistentes.has(claveNormalizada)) {
                                    const telaExistente = mapaTelasExistentes.get(claveNormalizada);
                                    
                                    if (!telaExistente.tela.referencia || telaExistente.tela.referencia === '') {
                                        if (referencia && referencia !== '') {
                                            // Enriquecer la tela existente con la referencia
                                            const indiceOriginal = telaExistente.index;
                                            prenda.telasAgregadas[indiceOriginal].referencia = String(referencia).trim();
                                            prenda.telasAgregadas[indiceOriginal].origen = 'enriquecido_desde_variantes';
                                            
                                            console.log(`[cargarTelas]  Tela enriquecida:`, {
                                                nombre: nombre_tela,
                                                color: color,
                                                referencia_anterior: '""',
                                                referencia_nueva: `"${referencia}"`,
                                                variante_index: varianteIndex,
                                                tela_index: telaIndex
                                            });
                                        } else {
                                            console.log(`[cargarTelas]  Referencia vacía en variante también para:`, {
                                                nombre: nombre_tela,
                                                color: color,
                                                referencia_en_variante: `"${referencia}"`
                                            });
                                        }
                                    } else {
                                        console.log(`[cargarTelas]  Tela ya tiene referencia:`, {
                                            nombre: nombre_tela,
                                            color: color,
                                            referencia_existente: `"${telaExistente.tela.referencia}"`
                                        });
                                    }
                                } else {
                                    console.log(`[cargarTelas]  Tela no encontrada en existentes: ${clave}`);
                                }
                            });
                        } else {
                            console.log(`[cargarTelas]  [Estructura ${varianteIndex}] No tiene telas_multiples válido`);
                        }
                    });
                    
                    // LOG FINAL de enriquecimiento
                    console.log('[cargarTelas]  RESULTADO DESPUÉS DE ENRIQUECIMIENTO:');
                    prenda.telasAgregadas.forEach((tela, idx) => {
                        console.log(`  [${idx}] "${tela.nombre_tela}" - "${tela.color}" -> referencia: "${tela.referencia}" (origen: ${tela.origen || 'backend'})`);
                    });
                } else {
                    console.log('[cargarTelas]  No se encontró estructura de variantes válida para procesar');
                    
                    // 🚨 SOLUCIÓN DE RESPALDO: Buscar directamente en la estructura del selector
                    console.log('[cargarTelas] 🔄 Intentando solución de respaldo directa...');
                    
                    // Buscar en la estructura original que viene del selector
                    // El selector tiene la estructura correcta con telas_multiples
                    if (window.prendaOriginalDesdeSelector) {
                        console.log('[cargarTelas] 🔍 Usando prenda original desde selector');
                        console.log('[cargarTelas] 🔍 Estructura original:', window.prendaOriginalDesdeSelector);
                        
                        const prendaOriginal = window.prendaOriginalDesdeSelector;
                        
                        // Buscar en variantes array del selector
                        if (prendaOriginal.variantes && Array.isArray(prendaOriginal.variantes)) {
                            prendaOriginal.variantes.forEach((variante, idx) => {
                                if (variante.telas_multiples && Array.isArray(variante.telas_multiples)) {
                                    console.log(`[cargarTelas]  [Original] Encontradas ${variante.telas_multiples.length} telas en variante ${idx}`);
                                    
                                    variante.telas_multiples.forEach((tela, telaIdx) => {
                                        const nombre_tela = tela.tela || tela.nombre_tela || '';
                                        const color = tela.color || '';
                                        const referencia = tela.referencia || '';
                                        const clave = `${nombre_tela}|${color}`;
                                        
                                        console.log(`[cargarTelas]  [Original] Analizando tela ${telaIdx}:`, {
                                            nombre_tela,
                                            color,
                                            referencia: `"${referencia}"`,
                                            clave
                                        });
                                        
                                        // Buscar y enriquecer telas existentes
                                        const mapaTelasExistentes = new Map();
                                        prenda.telasAgregadas.forEach((telaExistente, index) => {
                                            const claveExistente = `${telaExistente.nombre_tela}|${telaExistente.color}`;
                                            // Normalizar clave para comparación (ignorar mayúsculas/minúsculas y espacios)
                                            const claveNormalizada = claveExistente.toLowerCase().trim();
                                            mapaTelasExistentes.set(claveNormalizada, { index, tela: telaExistente, claveOriginal: claveExistente });
                                            console.log(`[cargarTelas] 📍 Tela existente registrada: "${claveExistente}" -> normalizada: "${claveNormalizada}" -> índice ${index}, referencia: "${telaExistente.referencia}"`);
                                        });
                                        
                                        // Normalizar clave de búsqueda también
                                        const claveNormalizada = clave.toLowerCase().trim();
                                        
                                        console.log(`[cargarTelas] 🔍 Buscando clave "${clave}" -> normalizada: "${claveNormalizada}" en mapa de telas existentes:`, {
                                            existe: mapaTelasExistentes.has(claveNormalizada),
                                            totalTelas: mapaTelasExistentes.size,
                                            clavesDisponibles: Array.from(mapaTelasExistentes.keys()),
                                            claveBuscada: `"${clave}"`,
                                            claveNormalizadaBuscada: `"${claveNormalizada}"`,
                                            referenciaBuscada: `"${referencia}"`
                                        });
                                        
                                        if (mapaTelasExistentes.has(claveNormalizada)) {
                                            const telaExistente = mapaTelasExistentes.get(claveNormalizada);
                                            console.log(`[cargarTelas]  Tela coincidente encontrada:`, {
                                                indice: telaExistente.index,
                                                clave_original: telaExistente.claveOriginal,
                                                referencia_actual: `"${telaExistente.tela.referencia}"`,
                                                referencia_vacia: !telaExistente.tela.referencia || telaExistente.tela.referencia === '',
                                                referencia_a_enriquecer: `"${referencia}"`
                                            });
                                            
                                            if (!telaExistente.tela.referencia || telaExistente.tela.referencia === '') {
                                                if (referencia && referencia !== '') {
                                                    const indiceOriginal = telaExistente.index;
                                                    const referenciaAnterior = prenda.telasAgregadas[indiceOriginal].referencia;
                                                    
                                                    prenda.telasAgregadas[indiceOriginal].referencia = String(referencia).trim();
                                                    prenda.telasAgregadas[indiceOriginal].origen = 'enriquecido_desde_selector';
                                                    
                                                    console.log(`[cargarTelas]  [Original] Tela enriquecida:`, {
                                                        nombre: nombre_tela,
                                                        color: color,
                                                        referencia_anterior: `"${referenciaAnterior}"`,
                                                        referencia_nueva: `"${referencia}"`,
                                                        indice: indiceOriginal
                                                    });
                                                    
                                                    // Verificar que se guardó correctamente
                                                    const referenciaVerificada = prenda.telasAgregadas[indiceOriginal].referencia;
                                                    console.log(`[cargarTelas] 🔍 Verificación post-enriquecimiento:`, {
                                                        indice: indiceOriginal,
                                                        referencia_guardada: `"${referenciaVerificada}"`,
                                                        exito: referenciaVerificada === String(referencia).trim()
                                                    });
                                                } else {
                                                    console.log(`[cargarTelas]  Referencia a enriquecer está vacía: "${referencia}"`);
                                                }
                                            } else {
                                                console.log(`[cargarTelas]  Tela ya tiene referencia, no se enriquece: "${telaExistente.tela.referencia}"`);
                                            }
                                        } else {
                                            console.log(`[cargarTelas]  No se encontró tela coincidente para clave: "${clave}"`);
                                        }
                                    });
                                }
                            });
                        }
                    } else {
                        console.log('[cargarTelas]  No hay prenda original desde selector disponible');
                        
                        // ÚLTIMO RESPALDO: Buscar en cualquier propiedad que contenga "tela" o "multiple"
                        if (prenda.variantes && typeof prenda.variantes === 'object') {
                            console.log('[cargarTelas] 🔍 Búsqueda de última opción en todas las propiedades...');
                            
                            // Buscar telas_multiples en cualquier propiedad
                            for (const [key, value] of Object.entries(prenda.variantes)) {
                                if (key.includes('tela') || key.includes('multiple')) {
                                    console.log(`[cargarTelas] 🔍 Propiedad candidata:`, { key, value, isArray: Array.isArray(value) });
                                    
                                    if (Array.isArray(value) && value.length > 0) {
                                        value.forEach((tela, idx) => {
                                            if (tela.referencia) {
                                                console.log(`[cargarTelas]  [Último] Referencia encontrada en ${key}[${idx}]:`, tela.referencia);
                                                
                                                // Intentar enriquecer con esta referencia
                                                const nombre_tela = tela.tela || tela.nombre_tela || '';
                                                const color = tela.color || '';
                                                const clave = `${nombre_tela}|${color}`;
                                                
                                                prenda.telasAgregadas.forEach((telaExistente, index) => {
                                                    const claveExistente = `${telaExistente.nombre_tela}|${telaExistente.color}`;
                                                    if (claveExistente === clave && (!telaExistente.referencia || telaExistente.referencia === '')) {
                                                        prenda.telasAgregadas[index].referencia = String(tela.referencia).trim();
                                                        prenda.telasAgregadas[index].origen = 'enriquecido_ultimo_respaldo';
                                                        
                                                        console.log(`[cargarTelas]  [Último] Tela enriquecida:`, {
                                                            nombre: nombre_tela,
                                                            color: color,
                                                            referencia_anterior: '""',
                                                            referencia_nueva: `"${tela.referencia}"`
                                                        });
                                                    }
                                                });
                                            }
                                        });
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                console.log('[cargarTelas]  Todas las telas ya tienen referencias o no hay variantes para enriquecer');
            }
                
            // LOG FINAL: Mostrar estado final de telasAgregadas
            console.log('[cargarTelas]  ESTADO FINAL DE telasAgregadas:');
            prenda.telasAgregadas.forEach((tela, idx) => {
                console.log(`  [${idx}] "${tela.nombre_tela}" - "${tela.color}" -> referencia: "${tela.referencia}" (origen: ${tela.origen || 'backend'})`);
            });          
            
            // Verificar estructura de cada tela
            prenda.telasAgregadas.forEach((tela, idx) => {
                console.log(`[cargarTelas] Tela ${idx}:`, {
                    nombre: tela.nombre_tela,
                    color: tela.color,
                    imagenes_count: tela.imagenes ? tela.imagenes.length : 0,
                    imagenes: tela.imagenes
                });
            });

            
            // Limpiar storage de telas y inputs
            if (window.imagenesTelaStorage) {
                window.imagenesTelaStorage.limpiar();
            }
            
            // Limpiar inputs de tela para que estén vacíos (no precargados)
            const inputTela = document.getElementById('nueva-prenda-tela');
            const inputColor = document.getElementById('nueva-prenda-color');
            const inputRef = document.getElementById('nueva-prenda-referencia');
            
            if (inputTela) inputTela.value = '';
            if (inputColor) inputColor.value = '';
            if (inputRef) inputRef.value = '';
            
            // Limpiar preview temporal de imágenes de tela
            const previewTemporal = document.getElementById('nueva-prenda-tela-preview');
            if (previewTemporal) {
                previewTemporal.innerHTML = '';
                previewTemporal.style.display = 'none';
            }
            
            //  NO cargar imágenes de telas de BD en window.imagenesTelaStorage
            // Las imágenes de telas existentes SOLO se muestran en la tabla (gestion-telas.js)
            // El storage debe estar limpio para AGREGAR TELAS NUEVAS
            // Esto evita que aparezcan precargadas en el input de agregar
            
            // Actualizar tabla de telas - Asignar a window.telasAgregadas para que se muestre en la tabla
            window.telasAgregadas = [...prenda.telasAgregadas];
            console.log('[cargarTelas]  window.telasAgregadas asignadas:', window.telasAgregadas);

            
            // Actualizar tabla de telas
            if (window.actualizarTablaTelas) {
                console.log('[cargarTelas] 🔄 Llamando a actualizarTablaTelas()');
                window.actualizarTablaTelas();

            }
            
            // Actualizar preview de tela
            if (window.actualizarPreviewTela) {
                window.actualizarPreviewTela();
            }
            
            return;
        }


    }

    /**
     * Cargar tallas y cantidades
     * @private
     */
    cargarTallasYCantidades(prenda) {
        if (!window.tallasRelacionales) {
            window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        }
        window.tallasRelacionales.DAMA = {};
        window.tallasRelacionales.CABALLERO = {};
        window.tallasRelacionales.UNISEX = {};
        window.tallasRelacionales.SOBREMEDIDA = {};

        console.log('[cargarTallasYCantidades] 🔍 Analizando prenda:', {
            tiene_tallas: !!prenda.tallas,
            tallas: prenda.tallas,
            tiene_generosConTallas: !!prenda.generosConTallas,
            generosConTallas: prenda.generosConTallas,
            tiene_cantidad_talla: !!prenda.cantidad_talla,
            cantidad_talla: prenda.cantidad_talla,
            tiene_tallas_disponibles: !!prenda.tallas_disponibles,
            tallas_disponibles: prenda.tallas_disponibles,
            tiene_genero_id: !!prenda.variantes?.genero_id,
            genero_id: prenda.variantes?.genero_id
        });

        // MAPEO de genero_id a nombre
        const generoMap = {
            1: 'DAMA',
            2: 'CABALLERO'
        };

        // Determinar género de la prenda desde genero_id
        const generoActual = prenda.variantes?.genero_id ? generoMap[prenda.variantes.genero_id] : null;
        
        console.log('[cargarTallasYCantidades] 👥 Género seleccionado:', generoActual);

        // CARGAR TALLAS DESDE:
        // 1. generosConTallas (edición de BD) - Formato: {DAMA: {S: 20}, SOBREMEDIDA: {DAMA: 34}}
        // 2. cantidad_talla (prendas nuevas creadas en formulario)
        // 3. tallas (array)
        // 4. tallas_disponibles (prendas nuevas sin cantidades)
        if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
            console.log('[cargarTallasYCantidades] ✓ Cargando tallas desde generosConTallas:', prenda.generosConTallas);
            
            Object.entries(prenda.generosConTallas).forEach(([generoKey, tallaData]) => {
                const generoUpper = generoKey.toUpperCase();
                
                // Validar que tallaData es un objeto válido con datos
                if (!tallaData || typeof tallaData !== 'object' || Object.keys(tallaData).length === 0) {
                    return; // Saltar géneros vacíos
                }
                
                // CASO ESPECIAL: SOBREMEDIDA
                if (generoUpper === 'SOBREMEDIDA') {
                    // generosConTallas[SOBREMEDIDA] tiene estructura: {DAMA: 34, CABALLERO: 20}
                    window.tallasRelacionales.SOBREMEDIDA = { ...tallaData };
                    console.log('[cargarTallasYCantidades] ✓✓ Sobremedida cargada:', window.tallasRelacionales.SOBREMEDIDA);
                } else {
                    // Géneros normales: {S: 20, M: 30, L: 25}
                    // 🔥 FIX: Si hay SOBREMEDIDA anidada dentro de este género (número u objeto), EXTRAERLA
                    const tallaDataLimpia = {};
                    for (const [talla, valor] of Object.entries(tallaData)) {
                        if (talla === 'SOBREMEDIDA') {
                            if (typeof valor === 'number') {
                                // SOBREMEDIDA como número: {DAMA: 344}
                                window.tallasRelacionales.SOBREMEDIDA[generoUpper] = valor;
                            } else if (typeof valor === 'object' && valor !== null) {
                                // SOBREMEDIDA anidada: {DAMA: 34}
                                for (const [generoSobremedida, cantidad] of Object.entries(valor)) {
                                    window.tallasRelacionales.SOBREMEDIDA[generoSobremedida] = cantidad;
                                }
                            }
                            console.log(`[cargarTallasYCantidades] 🔧 ${generoUpper} SOBREMEDIDA extraída:`, valor);
                        } else {
                            tallaDataLimpia[talla] = valor;
                        }
                    }
                    
                    if (window.tallasRelacionales[generoUpper]) {
                        window.tallasRelacionales[generoUpper] = tallaDataLimpia;
                        console.log(`[cargarTallasYCantidades] ✓✓ ${generoUpper} cargado:`, window.tallasRelacionales[generoUpper]);
                    }
                }
            });
        } else if (prenda.cantidad_talla && typeof prenda.cantidad_talla === 'object' && Object.keys(prenda.cantidad_talla).length > 0) {
            console.log('[cargarTallasYCantidades] ✓ Cargando tallas desde cantidad_talla (prendas nuevas):', prenda.cantidad_talla);
            
            // cantidad_talla tiene estructura: { DAMA: {S: 20, M: 20}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} }
            Object.entries(prenda.cantidad_talla).forEach(([generoKey, tallasObj]) => {
                const generoUpper = generoKey.toUpperCase();
                
                // CASO ESPECIAL: SOBREMEDIDA
                if (generoUpper === 'SOBREMEDIDA' && tallasObj && typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0) {
                    window.tallasRelacionales.SOBREMEDIDA = { ...tallasObj };
                    console.log('[cargarTallasYCantidades] ✓✓ Sobremedida cargada:', window.tallasRelacionales.SOBREMEDIDA);
                } else if (window.tallasRelacionales[generoUpper] && tallasObj && typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0) {
                    // 🔥 FIX: Detectar si SOBREMEDIDA viene anidada en este género (número u objeto)
                    const tallasObjLimpia = {};
                    for (const [talla, valor] of Object.entries(tallasObj)) {
                        if (talla === 'SOBREMEDIDA') {
                            if (typeof valor === 'number') {
                                // SOBREMEDIDA como número: es para este género
                                window.tallasRelacionales.SOBREMEDIDA[generoUpper] = valor;
                            } else if (typeof valor === 'object' && valor !== null) {
                                // SOBREMEDIDA anidada: extraer
                                for (const [generoSobremedida, cantidad] of Object.entries(valor)) {
                                    window.tallasRelacionales.SOBREMEDIDA[generoSobremedida] = cantidad;
                                }
                            }
                            console.log(`[cargarTallasYCantidades] 🔧 ${generoUpper} SOBREMEDIDA extraída:`, valor);
                        } else {
                            tallasObjLimpia[talla] = valor;
                        }
                    }
                    window.tallasRelacionales[generoUpper] = tallasObjLimpia;
                }
            });
        } else if (prenda.cotizacion_id) {
            // SOLO para cotizaciones: Pre-seleccionar tallas que vienen de la base de datos
            console.log('[cargarTallasYCantidades] ✓ Detectada cotización, intentando cargar tallas');
            console.log('[cargarTallasYCantidades] 📋 prenda.tallas:', prenda.tallas);
            console.log('[cargarTallasYCantidades] 📋 prenda.procesos:', prenda.procesos);
            
            // Inicializar estructura para tallas desde cotización
            window.tallasDesdeCotizacion = window.tallasDesdeCotizacion || {};
            
            let tallasEncontradas = false;
            let tallasArray = [];
            
            // OPCIÓN 1: Si vienen en prenda.tallas (desde BD prenda_tallas_cot)
            if (prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0) {
                console.log('[cargarTallasYCantidades] ✓ Cargando tallas desde prenda.tallas (BD):', prenda.tallas);
                
                // NUEVO: Agrupar por género si vienen con genero_id/genero relacionado
                const tallasAgrupadas = {};
                const generoMap = { 1: 'DAMA', 2: 'CABALLERO', 3: 'UNISEX' };
                
                prenda.tallas.forEach(tallaObj => {
                    // Obtener género del objeto talla relacionado
                    let generoKey = 'DAMA'; // Por defecto
                    
                    // Si viene con genero.id o genero.nombre
                    if (tallaObj.genero && tallaObj.genero.id) {
                        generoKey = generoMap[tallaObj.genero.id] || 'DAMA';
                    } else if (tallaObj.genero && tallaObj.genero.nombre) {
                        generoKey = tallaObj.genero.nombre.toUpperCase();
                    } else if (tallaObj.genero_id) {
                        generoKey = generoMap[tallaObj.genero_id] || 'DAMA';
                    }
                    
                    // Agrupar
                    if (!tallasAgrupadas[generoKey]) {
                        tallasAgrupadas[generoKey] = [];
                    }
                    tallasAgrupadas[generoKey].push(tallaObj);
                });
                
                console.log('[cargarTallasYCantidades] 📊 Tallas agrupadas por género:', tallasAgrupadas);
                
                // NUEVO: Detectar géneros múltiples en las variantes
                const generosEnVariantes = new Set();
                if (prenda.variantes) {
                    // Si es array de variantes
                    if (Array.isArray(prenda.variantes)) {
                        prenda.variantes.forEach(v => {
                            if (v.genero_id && typeof v.genero_id === 'string') {
                                try {
                                    const ids = JSON.parse(v.genero_id);
                                    if (Array.isArray(ids)) {
                                        ids.forEach(id => generosEnVariantes.add(parseInt(id)));
                                    } else {
                                        generosEnVariantes.add(parseInt(id));
                                    }
                                } catch (e) {
                                    const id = parseInt(v.genero_id);
                                    if (!isNaN(id)) generosEnVariantes.add(id);
                                }
                            }
                        });
                    } else if (typeof prenda.variantes === 'object') {
                        // Si es un objeto único de variante
                        const v = prenda.variantes;
                        if (v.genero_id && typeof v.genero_id === 'string') {
                            try {
                                const ids = JSON.parse(v.genero_id);
                                if (Array.isArray(ids)) {
                                    ids.forEach(id => generosEnVariantes.add(parseInt(id)));
                                } else {
                                    generosEnVariantes.add(parseInt(id));
                                }
                            } catch (e) {
                                const id = parseInt(v.genero_id);
                                if (!isNaN(id)) generosEnVariantes.add(id);
                            }
                        }
                    }
                }
                
                console.log('[cargarTallasYCantidades] 👥 Géneros detectados en variantes:', Array.from(generosEnVariantes).map(id => generoMap[id]));
                
                // Procesar cada género encontrado EN LAS TALLAS
                Object.entries(tallasAgrupadas).forEach(([generoKey, tallasList]) => {
                    window.tallasDesdeCotizacion = window.tallasDesdeCotizacion || {};
                    window.tallasDesdeCotizacion[generoKey] = new Set();
                    
                    tallasList.forEach(tallaObj => {
                        const talla = tallaObj.talla;
                        const cantidad = tallaObj.cantidad || 0;
                        console.log(`[cargarTallasYCantidades] 📏 Agregando ${generoKey} - ${talla}: ${cantidad}`);
                        window.tallasRelacionales[generoKey][talla] = cantidad;
                        window.tallasDesdeCotizacion[generoKey].add(talla);
                    });
                });
                
                // NUEVO: Si hay géneros en variantes que NO tienen tallas en BD, duplicar las primeras tallas encontradas
                if (generosEnVariantes.size > 0) {
                    const generosConTallas = Object.keys(tallasAgrupadas);
                    const primerGeneroConTallas = generosConTallas.length > 0 ? generosConTallas[0] : null;
                    
                    for (const generoId of generosEnVariantes) {
                        const generoNombre = generoMap[generoId];
                        
                        // Si este género NO está en las tallas agrupadas pero SÍ en las variantes
                        if (generoNombre && !tallasAgrupadas[generoNombre] && primerGeneroConTallas) {
                            console.log(`[cargarTallasYCantidades] 🔄 Duplicando tallas de ${primerGeneroConTallas} a ${generoNombre} (desde variantes)`);
                            
                            window.tallasDesdeCotizacion[generoNombre] = new Set();
                            
                            // Copiar tallas del primer género con tallas
                            Object.entries(window.tallasRelacionales[primerGeneroConTallas]).forEach(([talla, cantidad]) => {
                                window.tallasRelacionales[generoNombre][talla] = cantidad;
                                window.tallasDesdeCotizacion[generoNombre].add(talla);
                                console.log(`[cargarTallasYCantidades] 📏 Duplicada ${generoNombre} - ${talla}: ${cantidad}`);
                            });
                        }
                    }
                }
                
                tallasEncontradas = true;
                console.log('[cargarTallasYCantidades] 📋 Tallas de cotización FINAL para pre-selección:', window.tallasDesdeCotizacion);
            } 
            // OPCIÓN 2: Si vienen en procesos[PROCESO].talla_cantidad (desde logo_cotizacion_tecnica_prendas)
            else if (prenda.procesos && typeof prenda.procesos === 'object') {
                console.log('[cargarTallasYCantidades] 🔍 prenda.tallas vacío, buscando en procesos...');
                
                // Buscar en todos los procesos
                for (const [procesoKey, procesoData] of Object.entries(prenda.procesos)) {
                    if (procesoData && procesoData.talla_cantidad) {
                        console.log(`[cargarTallasYCantidades] ✓ Encontradas tallas en procesos.${procesoKey}:`, procesoData.talla_cantidad);
                        
                        // Convertir a array si es necesario
                        if (Array.isArray(procesoData.talla_cantidad)) {
                            tallasArray = procesoData.talla_cantidad;
                        } else if (typeof procesoData.talla_cantidad === 'object') {
                            // Si es objeto {talla: cantidad}, convertir a array
                            tallasArray = Object.entries(procesoData.talla_cantidad).map(([talla, cantidad]) => ({
                                talla: talla,
                                cantidad: cantidad
                            }));
                        }
                        tallasEncontradas = true;
                        break;  // Usar el primer proceso que tenga tallas
                    }
                }
            }
            
            if (tallasEncontradas && tallasArray && tallasArray.length > 0) {
                // Usar el género único determinado antes
                let generoPrenda = 'DAMA'; // valor por defecto
                if (prenda.genero) {
                    if (typeof prenda.genero === 'string') {
                        generoPrenda = prenda.genero.toUpperCase();
                    } else if (prenda.genero.nombre) {
                        generoPrenda = prenda.genero.nombre.toUpperCase();
                    }
                }
                
                window.tallasDesdeCotizacion = window.tallasDesdeCotizacion || {};
                window.tallasDesdeCotizacion[generoPrenda] = new Set();
                
                tallasArray.forEach(tallaObj => {
                    const talla = tallaObj.talla;
                    const cantidad = tallaObj.cantidad || 0;
                    console.log(`[cargarTallasYCantidades] 📏 Agregando ${generoPrenda} - ${talla}: ${cantidad}`);
                    window.tallasRelacionales[generoPrenda][talla] = cantidad;
                    window.tallasDesdeCotizacion[generoPrenda].add(talla);
                });
                
                console.log('[cargarTallasYCantidades] 📋 Tallas de cotización para pre-selección:', window.tallasDesdeCotizacion);
            } else if (!tallasEncontradas) {
                console.log('[cargarTallasYCantidades]  No se encontraron tallas en BD ni en procesos');
            }
        } else if (prenda.tallas_disponibles && Array.isArray(prenda.tallas_disponibles) && prenda.tallas_disponibles.length > 0) {
            console.log('[cargarTallasYCantidades] ✓ Cargando tallas disponibles:', prenda.tallas_disponibles);
            
            // Cargar tallas en el género actual SIN cantidades (dejar vacío para que user digitee)
            if (generoActual) {
                prenda.tallas_disponibles.forEach(talla => {
                    window.tallasRelacionales[generoActual][talla] = 0;  // 0 = no pre-llenado
                });
            }
        } else {
            console.log('[cargarTallasYCantidades]  No hay tallas disponibles en la prenda');
            return;
        }

        console.log('[cargarTallasYCantidades]  window.tallasRelacionales:', window.tallasRelacionales);

        // Renderizar tallas desde estructura relacional
        Object.entries(window.tallasRelacionales).forEach(([genero, tallasObj]) => {
            const tallasList = Object.keys(tallasObj);  // TODOS los que están en el objeto, incluso con valor 0
            
            if (tallasList && tallasList.length > 0) {
                const generoLower = genero.toLowerCase();

                if (window.mostrarTallasDisponibles) {
                    window.mostrarTallasDisponibles('letra');
                }
                
                // Crear tarjeta de género con tallas
                setTimeout(() => {
                    // Detectar si es SOBREMEDIDA y usar la función correcta
                    if (genero.toUpperCase() === 'SOBREMEDIDA') {
                        // SOBREMEDIDA - buscar el primer género con cantidad
                        for (const [generoSobremedida, cantidad] of Object.entries(tallasObj)) {
                            if (cantidad > 0) {
                                console.log(`[cargarTallasYCantidades] 📐 Creando tarjeta SOBREMEDIDA: ${generoSobremedida} = ${cantidad}`);
                                if (window.crearTarjetaSobremedida) {
                                    window.crearTarjetaSobremedida(generoSobremedida, cantidad);
                                }
                                break; // Solo una entrada de sobremedida
                            }
                        }
                    } else {
                        // GÉNEROS NORMALES
                        if (window.crearTarjetaGenero) {
                            window.crearTarjetaGenero(generoLower, tallasObj);
                            console.log(`[cargarTallasYCantidades] ✓ Tarjeta creada para género: ${generoLower}`);
                        }
                    }
                    
                    // PRE-CARGAR CANTIDADES Y PRE-SELECCIONAR TALLAS (SOLO para cotizaciones)
                    const tieneCantidades = Object.values(tallasObj).some(cantidad => cantidad > 0);
                    const esDesdeCotizacion = window.tallasDesdeCotizacion && window.tallasDesdeCotizacion[genero.toUpperCase()];
                    
                    if (esDesdeCotizacion) {
                        console.log('[cargarTallasYCantidades] 📋 Pre-cargando cantidades y pre-seleccionando tallas desde cotización');
                        
                        // Esperar a que se renderice la tarjeta y luego llenar cantidades y seleccionar checkboxes
                        setTimeout(() => {
                            Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                                // Pre-cargar cantidad
                                const input = document.querySelector(`input[data-talla="${talla}"][data-genero="${generoLower}"]`);
                                if (input) {
                                    if (cantidad > 0) {
                                        input.value = cantidad;
                                        console.log(`[cargarTallasYCantidades] ✏️ Pre-cargada ${generoLower} - ${talla}: ${cantidad}`);
                                    }
                                    
                                    // Pre-seleccionar checkbox si viene de cotización
                                    if (esDesdeCotizacion && window.tallasDesdeCotizacion[genero.toUpperCase()].has(talla)) {
                                        const checkbox = document.querySelector(`input[type="checkbox"][value="${talla}"][data-genero="${generoLower}"]`);
                                        if (checkbox && !checkbox.checked) {
                                            checkbox.checked = true;
                                            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                                            console.log(`[cargarTallasYCantidades] ☑️ Pre-seleccionado ${generoLower} - ${talla}`);
                                        }
                                    }
                                }
                            });
                        }, 300);
                    } else {
                        console.log('[cargarTallasYCantidades] 📋 Tallas mostradas sin pre-carga (no es cotización)');
                    }
                }, 150);
            }
        });
        
        // Disparar eventos de cambio en los checkboxes de género
        ['dama', 'caballero', 'unisex'].forEach(genero => {
            const checkboxGenero = document.querySelector(`input[value="${genero}"]`);
            if (checkboxGenero) {
                checkboxGenero.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        
        //  AUTOMÁTICO PARA COTIZACIONES: Si vinieron tallas desde procesos de cotización, aplicarlas automáticamente
        if (prenda.cotizacion_id && prenda.procesos && window.tallasRelacionales) {
            console.log('[cargarTallasYCantidades] 🔄 COTIZACIÓN DETECTADA: Preparando aplicación automática de tallas a procesos...');
            
            // Esperar a que se renderice todo y LUEGO aplicar tallas a procesos
            setTimeout(() => {
                // Obtener tallas de la prenda (ya cargadas en window.tallasRelacionales)
                const tallasDama = window.tallasRelacionales.DAMA || {};
                const tallasCaballero = window.tallasRelacionales.CABALLERO || {};
                
                console.log('[cargarTallasYCantidades]  Tallas a aplicar:', { 
                    dama: tallasDama, 
                    caballero: tallasCaballero 
                });
                
                // Recorrer todos los procesos y aplicar automáticamente las tallas
                Object.keys(prenda.procesos).forEach(procesoSlug => {
                    const procesoData = prenda.procesos[procesoSlug];
                    
                    if (procesoData && procesoData.talla_cantidad) {
                        console.log(`[cargarTallasYCantidades]  PROCESO "${procesoSlug}" con talla_cantidad:`, procesoData.talla_cantidad);
                        
                        // Aplicar tallas directamente a window.tallasCantidadesProceso
                        // para cada género disponible
                        if (Object.keys(tallasDama).length > 0) {
                            if (!window.tallasCantidadesProceso) {
                                window.tallasCantidadesProceso = { dama: {}, caballero: {} };
                            }
                            window.tallasCantidadesProceso.dama = { ...tallasDama };
                            console.log(`[cargarTallasYCantidades] ✏️ Aplicadas tallas DAMA al proceso ${procesoSlug}:`, tallasDama);
                        }
                        
                        if (Object.keys(tallasCaballero).length > 0) {
                            if (!window.tallasCantidadesProceso) {
                                window.tallasCantidadesProceso = { dama: {}, caballero: {} };
                            }
                            window.tallasCantidadesProceso.caballero = { ...tallasCaballero };
                            console.log(`[cargarTallasYCantidades] ✏️ Aplicadas tallas CABALLERO al proceso ${procesoSlug}:`, tallasCaballero);
                        }
                    }
                });
                
                // Sincronizar las tallas seleccionadas del proceso
                if (window.tallasSeleccionadasProceso) {
                    window.tallasSeleccionadasProceso.dama = Object.keys(tallasDama);
                    window.tallasSeleccionadasProceso.caballero = Object.keys(tallasCaballero);
                    console.log('[cargarTallasYCantidades]  Tallas seleccionadas sincronizadas:', window.tallasSeleccionadasProceso);
                }
                
                // 🔴 FIX CRÍTICO: Sincronizar tallas a window.procesosSeleccionados ANTES de re-renderizar
                console.log('[cargarTallasYCantidades] 🔄 SINCRONIZANDO TALLAS A window.procesosSeleccionados...');
                if (window.procesosSeleccionados && Object.keys(window.procesosSeleccionados).length > 0) {
                    Object.keys(window.procesosSeleccionados).forEach(tipoProceso => {
                        const proceso = window.procesosSeleccionados[tipoProceso];
                        if (proceso && proceso.datos) {
                            // Actualizar las tallas del proceso con las del relacional
                            proceso.datos.tallas = {
                                dama: { ...tallasDama },
                                caballero: { ...tallasCaballero }
                            };
                            console.log(`[cargarTallasYCantidades] ✏️ Tallas sincronizadas en proceso "${tipoProceso}":`, proceso.datos.tallas);
                        }
                    });
                }
                
                // 🔴 RE-RENDERIZAR TARJETAS CON LAS TALLAS ACTUALIZADAS
                if (window.renderizarTarjetasProcesos) {
                    console.log('[cargarTallasYCantidades] 🎨 RE-RENDERIZANDO TARJETAS CON TALLAS...');
                    window.renderizarTarjetasProcesos();
                } else {
                    console.warn('[cargarTallasYCantidades]  window.renderizarTarjetasProcesos no disponible');
                }
                
                console.log('[cargarTallasYCantidades]  TALLAS AUTOMÁTICAMENTE APLICADAS CON "done_all" ');
            }, 600);
        }
    }

    /**
     * Cargar variaciones de la prenda
     * @private
     */
    cargarVariaciones(prenda) {
        // Si viene de cotización Logo, buscar variaciones en los procesos
        let variantes = prenda.variantes || {};
        
        // Si variantes está vacío pero hay procesos (Logo/Reflectivo), extraer de los procesos
        if ((!variantes || Object.keys(variantes).length === 0) && prenda.procesos) {
            const procesosArray = Array.isArray(prenda.procesos) ? prenda.procesos : Object.values(prenda.procesos);
            if (procesosArray.length > 0 && procesosArray[0].variaciones_prenda) {
                console.log('[cargarVariaciones] 🔍 Variaciones extraídas desde procesos Logo/Reflectivo');
                variantes = procesosArray[0].variaciones_prenda;
            }
        }
        
        const aplicaManga = document.getElementById('aplica-manga');
        const aplicaBolsillos = document.getElementById('aplica-bolsillos');
        const aplicaBroche = document.getElementById('aplica-broche');
        const aplicaReflectivo = document.getElementById('aplica-reflectivo');
        
        // CARGAR GÉNERO DESDE VARIANTES (genero_id: 1=DAMA, 2=CABALLERO)
        if (variantes.genero_id) {
            console.log('[cargarVariaciones] 👥 Cargando género desde variantes:', {
                genero_id: variantes.genero_id,
                genero_nombre: variantes.genero
            });
            
            const generoMap = {
                1: 'DAMA',
                2: 'CABALLERO'
            };
            
            const generoSeleccionado = generoMap[variantes.genero_id];
            
            if (generoSeleccionado) {
                // Marcar checkbox del género
                const checkboxGenero = document.querySelector(`input[value="${generoSeleccionado.toLowerCase()}"]`);
                if (checkboxGenero) {
                    console.log(`[cargarVariaciones] ✓ Marcando checkbox género: ${generoSeleccionado}`);
                    checkboxGenero.checked = true;
                    checkboxGenero.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    console.warn(`[cargarVariaciones]  No encontré checkbox para género: ${generoSeleccionado}`);
                }
            }
        }

        // MANGA
        // Los datos pueden venir en varios formatos:
        // 1. variantes.manga.opcion / variantes.manga.tipo_manga (objeto)
        // 2. variantes.tipo_manga (string directo)
        // 3. variantes.tipo_manga_id (solo ID, sin nombre - CASO A FIJAR)
        let mangaData = variantes.manga || {};
        let mangaOpcion = '';
        let mangaObs = variantes.obs_manga || '';
        
        // Prioridad 1: Si viene tipo_manga como string directo (caso nuevo del servidor)
        if (typeof variantes.tipo_manga === 'string' && variantes.tipo_manga) {
            mangaOpcion = variantes.tipo_manga;
        }
        // Prioridad 2: Si viene como objeto (caso antiguo)
        else if (typeof mangaData === 'object') {
            mangaOpcion = mangaData.opcion || mangaData.tipo_manga || mangaData.manga || '';
        }
        
        // Fallback: Si solo tenemos ID pero sin nombre (no debería pasar porque lo arreglamos arriba)
        if (!mangaOpcion && variantes.tipo_manga_id) {
            console.warn('[cargarVariaciones] ⚠️ tipo_manga_id sin nombre - esto no debería pasar', variantes.tipo_manga_id);
            // No hacer nada, dejar vacío para que el usuario llene
        }
        
        if (aplicaManga && mangaOpcion) {
            aplicaManga.checked = true;
            aplicaManga.dispatchEvent(new Event('change', { bubbles: true }));
            
            const mangaInput = document.getElementById('manga-input');
            if (mangaInput) {
                // Normalizar el valor: convertir a minúscula y sin acentos
                let valorManga = mangaOpcion || '';
                valorManga = valorManga.toLowerCase()
                    .replace(/á/g, 'a')
                    .replace(/é/g, 'e')
                    .replace(/í/g, 'i')
                    .replace(/ó/g, 'o')
                    .replace(/ú/g, 'u');
                
                mangaInput.value = valorManga;
                console.log('[cargarVariaciones] ✓ manga-input asignado:', valorManga);
                mangaInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            const mangaObsInput = document.getElementById('manga-obs');
            if (mangaObsInput) {
                mangaObsInput.value = mangaObs || '';
                console.log('[cargarVariaciones] ✓ manga-obs asignado:', mangaObs);
                mangaObsInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // BOLSILLOS
        const bolsillosData = variantes.bolsillos || {};
        const bolsillosOpcion = bolsillosData.opcion || '';
        const bolsillosObs = bolsillosData.observacion || variantes.obs_bolsillos || '';
        
        if (aplicaBolsillos && (bolsillosOpcion || bolsillosObs)) {
            aplicaBolsillos.checked = true;
            aplicaBolsillos.dispatchEvent(new Event('change', { bubbles: true }));
            
            const bolsillosObsInput = document.getElementById('bolsillos-obs');
            if (bolsillosObsInput) {
                bolsillosObsInput.value = bolsillosObs || '';
                bolsillosObsInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // BROCHE/BOTÓN
        // Los datos pueden venir en varios formatos:
        // 1. variantes.broche_boton.opcion / variantes.broche_boton.tipo_broche (objeto)
        // 2. variantes.tipo_broche (string directo)
        // 3. variantes.tipo_broche_id (solo ID, sin nombre - CASO A FIJAR)
        let brocheData = variantes.broche_boton || variantes.broche || {};
        let brocheOpcion = '';
        let brocheObs = variantes.obs_broche || variantes.broche_boton_obs || '';
        
        // Prioridad 1: Si viene tipo_broche como string directo (caso nuevo del servidor)
        if (typeof variantes.tipo_broche === 'string' && variantes.tipo_broche) {
            brocheOpcion = variantes.tipo_broche;
        }
        // Prioridad 2: Si viene como objeto (caso antiguo)
        else if (typeof brocheData === 'object') {
            brocheOpcion = brocheData.opcion || brocheData.tipo_broche || brocheData.broche || '';
        }
        
        // Fallback: Si solo tenemos ID pero sin nombre (no debería pasar porque lo arreglamos arriba)
        if (!brocheOpcion && variantes.tipo_broche_id) {
            console.warn('[cargarVariaciones] ⚠️ tipo_broche_id sin nombre - esto no debería pasar', variantes.tipo_broche_id);
            // No hacer nada, dejar vacío para que el usuario llene
        }
        
        if (aplicaBroche && (brocheOpcion || brocheObs)) {
            aplicaBroche.checked = true;
            aplicaBroche.dispatchEvent(new Event('change', { bubbles: true }));
            
            console.log('[cargarVariaciones] 🔗 Broche/Botón encontrado:', {
                opcion: brocheOpcion,
                observacion: brocheObs
            });
            
            const brocheInput = document.getElementById('broche-input');
            if (brocheInput) {
                // Normalizar el valor: convertir a minúscula y sin acentos
                let valorBroche = brocheOpcion || '';
                valorBroche = valorBroche.toLowerCase()
                    .replace(/á/g, 'a')
                    .replace(/é/g, 'e')
                    .replace(/í/g, 'i')
                    .replace(/ó/g, 'o')
                    .replace(/ú/g, 'u');
                
                brocheInput.value = valorBroche;
                console.log('[cargarVariaciones] ✓ broche-input asignado:', brocheInput.value);
                brocheInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            const brocheObsInput = document.getElementById('broche-obs');
            if (brocheObsInput) {
                brocheObsInput.value = brocheObs || '';
                console.log('[cargarVariaciones] ✓ broche-obs asignado:', brocheObsInput.value);
                brocheObsInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // REFLECTIVO
        if (aplicaReflectivo && (variantes.tiene_reflectivo === true || variantes.obs_reflectivo)) {
            aplicaReflectivo.checked = true;
            // No disparar evento change para evitar abrir el modal automáticamente
            
            const reflectivoObs = document.getElementById('reflectivo-obs');
            if (reflectivoObs) {
                reflectivoObs.value = variantes.obs_reflectivo || '';
                // No disparar evento change aquí tampoco
            }
        }
    }

    /**
     * Normalizar procesos: convierte objeto a array si es necesario
     * Maneja ambos formatos: objeto {tipo: {...}} y array [{...}]
     * @private
     */
    normalizarProcesos(procesos) {
        if (!procesos) return [];
        
        // Si ya es un array (viene de BD), retornarlo tal cual
        if (Array.isArray(procesos)) {
            return procesos;
        }
        
        // Si es un objeto (viene de frontend nuevo), convertir a array
        if (typeof procesos === 'object') {
            return Object.values(procesos).filter(p => p !== null && p !== undefined);
        }
        
        return [];
    }

    /**
     * Cargar procesos de la prenda
     * IMPORTANTE: Maneja tanto procesos del formulario como de BD
     * Formulario: estructura puede tener ubicaciones como array
     * BD: puede venir con ubicaciones como array o string
     * @private
     */
    cargarProcesos(prenda) {
        // Normalizar procesos: maneja tanto array como objeto
        const procesosNormalizados = this.normalizarProcesos(prenda.procesos);
        
        if (!procesosNormalizados || procesosNormalizados.length === 0) {
            console.log(' [CARGAR-PROCESOS] Sin procesos en la prenda');
            return;
        }

        console.log('📋 [CARGAR-PROCESOS] Cargando procesos:', {
            total: procesosNormalizados.length,
            procesos: procesosNormalizados.map(p => ({
                id: p.id,
                tipo: p.tipo_proceso,
                nombre: p.nombre,
                nombre_proceso: p.nombre_proceso,
                tieneImagenes: !!p.imagenes,
                countImagenes: p.imagenes?.length || 0,
                tieneUbicaciones: !!p.ubicaciones,
                countUbicaciones: Array.isArray(p.ubicaciones) ? p.ubicaciones.length : 0
            }))
        });

        // Copiar procesos completos con todos sus datos
        window.procesosSeleccionados = {};
        
        // Los procesos vienen como array desde el backend
        procesosNormalizados.forEach((proceso, idx) => {
            if (proceso) {
                // IMPORTANTE: Si viene del formulario, proceso es {datos: {...}}
                // Si viene de BD, proceso es {...}
                const datosReales = proceso.datos ? proceso.datos : proceso;
                
                // El backend retorna 'tipo' directamente (ej: 'Bordado')
                // También puede traer 'slug' para facilitar la identificación
                const tipoBackend = datosReales.tipo || datosReales.tipo_proceso || datosReales.nombre || `Proceso ${idx}`;
                const slugDirecto = datosReales.slug || null;  // Si viene el slug directamente
                const tipoProceso = slugDirecto || tipoBackend.toLowerCase().replace(/\s+/g, '-');
                
                console.log(`📌 [CARGAR-PROCESOS] Procesando [${idx}] tipo="${tipoProceso}"`, {
                    tipoBackend: tipoBackend,
                    slug: slugDirecto,
                    nombreProceso: datosReales.nombre_proceso,
                    tipoProceso: datosReales.tipo_proceso,
                    nombre: datosReales.nombre,
                    tieneImagenes: !!datosReales.imagenes,
                    countImagenes: datosReales.imagenes?.length || 0,
                    tieneUbicaciones: !!datosReales.ubicaciones,
                    procesoId: datosReales.id,
                    tipo_proceso_id: datosReales.tipo_proceso_id || 'N/A'
                });

                // Detectar y cargar ubicaciones de forma adaptativa
                let ubicacionesFormato = [];
                
                if (datosReales.ubicaciones) {
                    if (Array.isArray(datosReales.ubicaciones)) {
                        // Ya es array, usarlo directamente (puede venir del formulario o BD)
                        ubicacionesFormato = datosReales.ubicaciones;
                        console.log(`   [UBICACIONES] Detectado ARRAY:`, ubicacionesFormato);
                    } else if (typeof datosReales.ubicaciones === 'string') {
                        // String separado por comas, convertir a array
                        ubicacionesFormato = datosReales.ubicaciones
                            .split(',')
                            .map(u => u.trim())
                            .filter(u => u && u.length > 0);
                        console.log(`   [UBICACIONES] Detectado STRING, convertido a ARRAY:`, ubicacionesFormato);
                    } else if (typeof datosReales.ubicaciones === 'object') {
                        // Objeto (unlikely but defensive), extraer valores
                        ubicacionesFormato = Object.values(datosReales.ubicaciones)
                            .filter(u => u && (typeof u === 'string' || typeof u === 'object'));
                        console.log(`   [UBICACIONES] Detectado OBJETO, extraído:`, ubicacionesFormato);
                    }
                }

                // Convertir tallas si es necesario
                let tallasFormato = datosReales.tallas || { dama: {}, caballero: {}, sobremedida: {} };
                if (Array.isArray(tallasFormato) && tallasFormato.length === 0) {
                    tallasFormato = { dama: {}, caballero: {}, sobremedida: {} };
                }
                
                const datosProces = {
                    id: datosReales.id,
                    tipo: tipoProceso,
                    nombre: tipoBackend,
                    nombre_proceso: datosReales.nombre_proceso,
                    tipo_proceso: datosReales.tipo_proceso,
                    tipo_proceso_id: datosReales.tipo_proceso_id, // 🔴 IMPORTANTE: Guardar el ID para enviar al servidor
                    ubicaciones: ubicacionesFormato,
                    observaciones: datosReales.observaciones || '',
                    tallas: tallasFormato,
                    // NUEVO: Variaciones de prenda (ej: manga, bolsillos, broche)
                    variaciones_prenda: datosReales.variaciones_prenda || {},
                    // NUEVO: Talla cantidad desde técnicas de logo
                    talla_cantidad: datosReales.talla_cantidad || {},
                    imagenes: (datosReales.imagenes || []).map(img => {
                        // Extraer URL de imagen, manejando distintos formatos
                        if (typeof img === 'string') {
                            // Si es string, validar que tenga /storage/
                            let url = img;
                            if (url && !url.startsWith('/')) {
                                url = '/storage/' + url;
                            }
                            return url;
                        }
                        if (img instanceof File) {
                            return img;
                        }
                        
                        // Si es objeto, obtener la URL correcta con prioridad
                        // Prioridad: ruta_original > ruta > ruta_webp > url
                        let urlExtraida = img.ruta_original || img.ruta || img.ruta_webp || img.url || '';
                        
                        // Validar después de seleccionar
                        if (!urlExtraida && (img.ruta_original || img.ruta || img.ruta_webp || img.url)) {
                            // Si la seleccionada es vacía, intentar otra
                            urlExtraida = img.ruta || img.ruta_webp || img.url || img.ruta_original || '';
                        }
                        
                        // Agregar /storage/ si es necesario
                        if (urlExtraida && typeof urlExtraida === 'string' && !urlExtraida.startsWith('/')) {
                            urlExtraida = '/storage/' + urlExtraida;
                        }
                        
                        console.log(`    🖼️ Imagen de proceso procesada:`, {
                            ruta_original: img.ruta_original || 'NULL',
                            ruta: img.ruta || 'NULL',
                            ruta_webp: img.ruta_webp || 'NULL',
                            url: img.url || 'NULL',
                            urlExtraida: urlExtraida || 'VACÍA'
                        });
                        return urlExtraida;
                    }).filter(url => url)  // Filtrar URLs vacías
                };
                
                window.procesosSeleccionados[tipoProceso] = {
                    datos: datosProces
                };
                
                console.log(` [CARGAR-PROCESOS] Proceso "${tipoProceso}" cargado:`, datosProces);

                // Marcar checkbox del proceso
                const checkboxProceso = document.getElementById(`checkbox-${tipoProceso}`);
                if (checkboxProceso) {
                    console.log(`☑️ [CARGAR-PROCESOS] Marcando checkbox para "${tipoProceso}"`);
                    checkboxProceso.checked = true;
                    // Evitar que el onclick se dispare automáticamente
                    checkboxProceso._ignorarOnclick = true;
                    checkboxProceso.dispatchEvent(new Event('change', { bubbles: true }));
                    checkboxProceso._ignorarOnclick = false;
                } else {
                    console.warn(` [CARGAR-PROCESOS] No se encontró checkbox para "${tipoProceso}". Buscando por data-tipo...`);
                    // Intentar encontrar por data-tipo
                    const checkboxPorTipo = document.querySelector(`[data-tipo="${tipoProceso}"]`);
                    if (checkboxPorTipo) {
                        console.log(`☑️ [CARGAR-PROCESOS] Encontrado por data-tipo, marcando...`);
                        checkboxPorTipo.checked = true;
                        checkboxPorTipo._ignorarOnclick = true;
                        checkboxPorTipo.dispatchEvent(new Event('change', { bubbles: true }));
                        checkboxPorTipo._ignorarOnclick = false;
                    } else {
                        console.warn(` [CARGAR-PROCESOS] Tampoco encontrado checkbox por data-tipo="${tipoProceso}"`);
                    }
                }
            }
        });
        
        console.log(' [CARGAR-PROCESOS] Procesos seleccionados finales:', window.procesosSeleccionados);

        // Renderizar tarjetas de procesos
        if (window.renderizarTarjetasProcesos) {
            console.log(' [CARGAR-PROCESOS] Renderizando tarjetas...');
            window.renderizarTarjetasProcesos();
        } else {
            console.error(' [CARGAR-PROCESOS] window.renderizarTarjetasProcesos no existe');
        }
    }

    /**
     * Cargar múltiples prendas desde una cotización
     * Aplica origen automático a cada prenda
     * 
     * @param {Array<Object>} prendas - Array de prendas desde cotización
     * @param {Object} cotizacion - Datos de la cotización
     * @returns {Array<Object>} - Prendas procesadas
     */
    cargarPrendasDesdeCotizacion(prendas, cotizacion) {
        if (!Array.isArray(prendas) || !cotizacion) {
            console.error('[PrendaEditor] Parámetros inválidos para cargar prendas desde cotización');
            return [];
        }

        console.info('[PrendaEditor] Cargando prendas desde cotización:', {
            cantidad: prendas.length,
            cotizacion: cotizacion.numero_cotizacion || cotizacion.id
        });

        // Asignar cotización actual
        this.cotizacionActual = cotizacion;

        // Procesar cada prenda
        const prendasProcesadas = prendas.map((prenda, index) => {
            const procesada = this.aplicarOrigenAutomaticoDesdeCotizacion(prenda);
            console.log(`  ✓ Prenda ${index + 1}: ${procesada.nombre_prenda} (origen: ${procesada.origen})`);
            return procesada;
        });

        console.info('[PrendaEditor] Prendas procesadas:', prendasProcesadas.length);
        return prendasProcesadas;
    }

    /**
     * Cambiar botón de "Guardar Prenda" a "Guardar Cambios"
     * @private
     */
    cambiarBotonAGuardarCambios() {
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            // Si viene de una cotización, mantener "Agregar Prenda"
            if (window.prendaActual && window.prendaActual.cotizacion_id) {
                console.log('[cargarTallasYCantidades] 🏷️ Viene de cotización, manteniendo "Agregar Prenda"');
                // No cambiar el texto, mantener "Agregar Prenda"
                btnGuardar.setAttribute('data-editing', 'false');
            } else {
                // Si es edición normal, cambiar a "Guardar Cambios"
                console.log('[cargarTallasYCantidades] 📝 Es edición normal, cambiando a "Guardar Cambios"');
                btnGuardar.innerHTML = '<span class="material-symbols-rounded">save</span>Guardar Cambios';
                btnGuardar.setAttribute('data-editing', 'true');
            }
        }
    }

    /**
     * Resetear estado de edición
     */
    resetearEdicion() {
        this.prendaEditIndex = null;
        
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = '<span class="material-symbols-rounded">check</span>Agregar Prenda';
            btnGuardar.removeAttribute('data-editing');
        }
    }

    /**
     * Obtener índice de prenda siendo editada
     */
    obtenerPrendaEditIndex() {
        return this.prendaEditIndex;
    }

    /**
     * Verificar si está en modo edición
     */
    estaEditando() {
        return this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;
    }

    /**
     * Mostrar notificación
     * @private
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
        if (this.notificationService) {
            this.notificationService.mostrar(mensaje, tipo);
        } else {

        }
    }
}

window.PrendaEditor = PrendaEditor;
