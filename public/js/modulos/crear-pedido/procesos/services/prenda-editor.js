/**
 * PrendaEditor - Gestor de Edición de Prendas
 * 
 * Responsabilidad única: Gestionar carga, edición y guardado de prendas en modal
 * 
 * Integración: Origen automático desde cotización (CotizacionPrendaHandler)
 * Arquitectura: DDD con API endpoints
 */
class PrendaEditor {
    constructor(options = {}) {
        this.notificationService = options.notificationService;
        this.modalId = options.modalId || 'modal-agregar-prenda-nueva';
        this.prendaEditIndex = null;
        this.cotizacionActual = options.cotizacionActual || null;
        
        // Inicializar servicio legacy
        if (window.prendaEditorLegacy) {
            window.prendaEditorLegacy.init({
                notificationService: this.notificationService,
                cotizacionActual: this.cotizacionActual
            });
        }
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
            // Actualizar servicio legacy con nueva cotización
            if (window.prendaEditorLegacy) {
                window.prendaEditorLegacy.cotizacionActual = cotizacionSeleccionada;
            }
        }

        // Mostrar modal
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.style.display = 'flex';
        } else {
            console.error('[PrendaEditor] Modal no encontrado:', this.modalId);
            return;
        }

        // Actualizar título modal según modo (si la función está disponible)
        if (typeof window.actualizarTituloModalPrenda === 'function') {
            window.actualizarTituloModalPrenda(esEdicion);
        }
    }

    /**
     * Cargar prenda en modal (MÉTODO LEGADO PARA COMPATIBILIDAD)
     * Este método carga una prenda existente directamente desde el objeto prenda
     * sin hacer llamada a la API (usado para edición local)
     */
    cargarPrendaEnModal(prenda, prendaIndex) {
        console.log(' [CARGAR-PRENDA] Iniciando carga de prenda en modal:', {
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
            //  APLICAR ORIGEN AUTOMÁTICO DESDE COTIZACIÓN usando servicio legacy
            const prendaProcesada = window.prendaEditorLegacy.aplicarOrigenAutomaticoDesdeCotizacion(prenda);

            this.prendaEditIndex = prendaIndex;
            this.abrirModal(true, prendaIndex);
            
            // Usar servicio legacy para cargar datos
            window.prendaEditorLegacy.llenarCamposBasicos(prendaProcesada);
            window.prendaEditorLegacy.cargarImagenes(prendaProcesada);
            window.prendaEditorLegacy.cargarTelas(prendaProcesada);
            
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
                window.prendaEditorLegacy.cargarTelasDesdeCtizacion(prendaProcesada);
            }
            
            window.prendaEditorLegacy.cargarVariaciones(prendaProcesada);  // CARGAR PRIMERO para que genero_id esté seleccionado antes de las tallas
            window.prendaEditorLegacy.cargarTallasYCantidades(prendaProcesada);
            
            console.log(' [CARGAR-PRENDA] Sobre de cargar procesos...');
            window.prendaEditorLegacy.cargarProcesos(prendaProcesada);
            
            window.prendaEditorLegacy.cambiarBotonAGuardarCambios();
            console.log(' [CARGAR-PRENDA] Prenda cargada completamente');
            this.mostrarNotificacion('Prenda cargada para editar', 'success');
        } catch (error) {
            console.error(' [CARGAR-PRENDA] Error:', error);
            this.mostrarNotificacion(`Error al cargar prenda: ${error.message}`, 'error');
        }
    }

    /**
     * Cargar prenda en modal usando API DDD (MÉTODO PRINCIPAL)
     */
    async cargarPrendaEnModalDDD(prendaId, prendaIndex = null, cotizacionId = null) {
        console.log(' [CARGAR-PRENDA-DDD] Iniciando carga con API DDD:', {
            prendaId,
            prendaIndex,
            cotizacionId,
            timestamp: new Date().toLocaleTimeString()
        });
        
        try {
            // 1. Obtener datos transformados desde backend
            const response = await fetch(`/api/prendas/${prendaId}/editar${cotizacionId ? `?cotizacion_id=${cotizacionId}` : ''}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Error del servidor');
            }
            
            // 2. Usar datos transformados del backend
            const prendaTransformada = result.data;
            console.log('[CARGAR-PRENDA-DDD]  Datos recibidos del backend:', {
                resumen: {
                    id: prendaTransformada.id,
                    nombre: prendaTransformada.nombre_prenda,
                    origen: prendaTransformada.origen
                }
            });
            
            // 3. Llenar campos básicos
            await this.llenarCamposBasicosDDD(prendaTransformada);
            
            // 4. Cargar imágenes
            await this.cargarImagenesDDD(prendaTransformada);
            
            // 5. Cargar telas
            await this.cargarTelasDDD(prendaTransformada);
            
            // 6. Cargar datos adicionales de cotización si aplica
            if (prendaTransformada.cotizacion_id) {
                await this.cargarDatosCotizacionAdicionales(prendaTransformada.cotizacion_id, prendaTransformada.id);
            }
            
            // 7. Cargar componentes DDD
            await this.cargarTallasYCantidades(prendaTransformada);
            await this.cargarVariaciones(prendaTransformada);
            await this.cargarProcesos(prendaTransformada);
            
            // 8. Cambiar botón a guardar cambios
            this.cambiarBotonAGuardarCambios();
            
            console.log('[CARGAR-PRENDA-DDD]  Prenda cargada exitosamente con DDD');
            
        } catch (error) {
            console.error('[CARGAR-PRENDA-DDD]  Error:', error);
            this.mostrarErrorAlUsuario('Error cargando prenda. Por favor intente nuevamente.');
        }
    }

    /**
     * Mostrar notificación (delegar al servicio legacy)
     */
    mostrarNotificacion(mensaje, tipo) {
        if (window.prendaEditorLegacy) {
            window.prendaEditorLegacy.mostrarNotificacion(mensaje, tipo);
        } else {
            // console.log(`[PrendaEditor] ${tipo}: ${mensaje}`);
        }
    }

    /**
     * Limpiar formulario (delegar al servicio legacy)
     */
    limpiarFormulario() {
        if (window.prendaEditorLegacy) {
            window.prendaEditorLegacy.limpiarFormulario();
        }
    }

    /**
     * Validar datos de prenda (delegar al servicio legacy)
     */
    validarDatosPrenda(prenda) {
        if (window.prendaEditorLegacy) {
            return window.prendaEditorLegacy.validarDatosPrenda(prenda);
        }
        return false;
    }

    /**
     * Cargar prenda en modal usando API DDD (MÉTODO PRINCIPAL)
     */
    async llenarCamposBasicosDDD(prenda) {
        console.log(' [llenarCamposBasicosDDD] Cargando campos básicos con datos DDD:', {
            nombre: prenda.nombre_prenda,
            origen: prenda.origen
        });
        
        // Nombre de la prenda
        const nombreInput = document.getElementById('nueva-prenda-nombre');
        if (nombreInput) {
            nombreInput.value = prenda.nombre_prenda || '';
        }
        
        // Origen de la prenda
        const origenSelect = document.getElementById('nueva-prenda-origen-select');
        if (origenSelect) {
            origenSelect.value = prenda.origen || 'confeccion';
        }
        
        // Descripción
        const descripcionInput = document.getElementById('nueva-prenda-descripcion');
        if (descripcionInput) {
            descripcionInput.value = prenda.descripcion || '';
        }
    }

    /**
     * Cargar imágenes usando API DDD
     */
    async cargarImagenesDDD(prenda) {
        console.log('🖼️ [cargarImagenesDDD] Cargando imágenes con datos DDD:', {
            cantidad: prenda.imagenes?.length || 0
        });
        
        const contenedorImagenes = document.getElementById('contenedor-imagenes-prenda');
        if (!contenedorImagenes) return;
        
        // Limpiar contenedor
        contenedorImagenes.innerHTML = '';
        
        // Cargar cada imagen
        if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
            prenda.imagenes.forEach((imagen, index) => {
                const img = document.createElement('img');
                img.src = imagen;
                img.alt = `Imagen ${index + 1} de ${prenda.nombre_prenda}`;
                img.className = 'imagen-prenda';
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
                img.onclick = () => this.mostrarImagenEnModal(imagen);
                
                contenedorImagenes.appendChild(img);
            });
        }
    }

    /**
     * Cargar telas usando API DDD
     */
    async cargarTelasDDD(prenda) {
        console.log('🧵 [cargarTelasDDD] Cargando telas con datos DDD:', {
            cantidad: prenda.telasAgregadas?.length || 0
        });
        
        // Las telas ya vienen procesadas del backend, solo las usamos
        console.log('[cargarTelasDDD]  Telas procesadas del backend:', {
            total: prenda.telasAgregadas.length,
            con_referencia: prenda.telasAgregadas.filter(t => t.referencia).length,
            con_imagenes: prenda.telasAgregadas.filter(t => t.imagenes?.length > 0)
        });
    }

    /**
     * Cargar tallas y cantidades usando API DDD
     */
    async cargarTallasYCantidades(prenda) {
        console.log('📏 [cargarTallasYCantidades] Cargando tallas con API DDD:', {
            prendaId: prenda.id,
            timestamp: new Date().toLocaleTimeString()
        });
        
        try {
            // Llamar al endpoint de procesamiento de tallas
            const response = await fetch(`/api/prendas/${prenda.id}/procesar-tallas`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Error procesando tallas');
            }
            
            const tallasDTO = result.data;
            
            console.log('[cargarTallasYCantidades]  Tallas procesadas del backend:', {
                genero_principal: tallasDTO.genero_principal,
                tipo_talla: tallasDTO.tipo_talla,
                total_general: tallasDTO.total_general,
                generos_activos: tallasDTO.generos_activos
            });
            
            // Aplicar tallas procesadas
            this.aplicarTallasProcesadas(tallasDTO);
            
            console.log('[cargarTallasYCantidades]  Tallas cargadas exitosamente con DDD');
            
        } catch (error) {
            console.error('[cargarTallasYCantidades]  Error:', error);
            this.mostrarErrorAlUsuario('Error cargando tallas. Por favor intente nuevamente.');
            this.desactivarFuncionalidadTallas();
        }
    }

    /**
     * Aplicar tallas procesadas desde el backend
     */
    aplicarTallasProcesadas(tallasDTO) {
        // Inicializar estructura global de tallas
        if (!window.tallasRelacionales) {
            window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        }
        
        // Cargar tallas procesadas del backend
        Object.entries(tallasDTO.tallas_por_genero).forEach(([genero, tallas]) => {
            if (genero !== 'SOBREMEDIDA' && typeof tallas === 'object') {
                window.tallasRelacionales[genero] = { ...tallas };
                console.log(`[aplicarTallasProcesadas] ✓✓ ${genero} cargado:`, window.tallasRelacionales[genero]);
            }
        });
        
        // Cargar sobremedida si existe
        if (tallasDTO.tiene_sobremedida && !empty(tallasDTO.sobremedida)) {
            window.tallasRelacionales.SOBREMEDIDA = { ...tallasDTO.sobremedida };
            console.log('[aplicarTallasProcesadas] ✓✓ Sobremedida cargada:', window.tallasRelacionales.SOBREMEDIDA);
        }
        
        // Guardar tallas desde cotización para pre-selección
        if (tallasDTO.tallas_desde_cotizacion && !empty(tallasDTO.tallas_desde_cotizacion)) {
            window.tallasDesdeCotizacion = tallasDTO.tallas_desde_cotizacion;
            console.log('[aplicarTallasProcesadas] 📋 Tallas para pre-selección:', window.tallasDesdeCotizacion);
        }
    }

    /**
     * Cargar variaciones usando API DDD
     */
    async cargarVariaciones(prenda) {
        console.log(' [cargarVariaciones] Cargando variaciones con API DDD:', {
            prendaId: prenda.id,
            timestamp: new Date().toLocaleTimeString()
        });
        
        try {
            // Llamar al endpoint de procesamiento de variaciones
            const response = await fetch(`/api/prendas/${prenda.id}/procesar-variaciones`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Error procesando variaciones');
            }
            
            const variacionesDTO = result.data;
            
            console.log('[cargarVariaciones]  Variaciones procesadas del backend:', {
                genero_principal: variacionesDTO.genero.nombre,
                tipos_detectados: variacionesDTO.tipos_detectados,
                tiene_variaciones: variacionesDTO.tiene_variaciones,
                es_valida: variacionesDTO.es_valida
            });
            
            // Aplicar configuración UI generada por el backend
            this.aplicarConfiguracionVariacionesUI(variacionesDTO.configuracion_ui);
            
            console.log('[cargarVariaciones]  Variaciones cargadas exitosamente con DDD');
            
        } catch (error) {
            console.error('[cargarVariaciones]  Error:', error);
            this.mostrarErrorAlUsuario('Error cargando variaciones. Por favor intente nuevamente.');
        }
    }

    /**
     * Aplicar configuración de variaciones UI desde el backend
     */
    aplicarConfiguracionVariacionesUI(configuracionUI) {
        console.log('[aplicarConfiguracionVariacionesUI]  Aplicando configuración UI:', {
            total_elementos: Object.keys(configuracionUI).length
        });
        
        // Aplicar género si existe
        if (configuracionUI.genero) {
            const generoConfig = configuracionUI.genero;
            const checkboxGenero = document.querySelector(generoConfig.checkbox_selector);
            
            if (checkboxGenero) {
                console.log(`[aplicarConfiguracionVariacionesUI] ✓ Marcando género: ${generoConfig.valor}`);
                checkboxGenero.checked = true;
                checkboxGenero.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                console.warn(`[aplicarConfiguracionVariacionesUI] No encontré checkbox para género: ${generoConfig.valor}`);
            }
        }
        
        // Aplicar cada variación
        Object.entries(configuracionUI).forEach(([tipo, config]) => {
            if (tipo === 'genero') return; // Ya procesado arriba
            
            // Marcar checkbox
            const checkbox = document.getElementById(config.checkbox_id);
            if (checkbox && config.checkbox_marcado) {
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                
                console.log(`[aplicarConfiguracionVariacionesUI] ✓ Checkbox marcado: ${config.checkbox_id}`);
            }
            
            // Llenar input si existe
            if (config.tiene_input && config.input_id) {
                const input = document.getElementById(config.input_id);
                if (input && config.input_valor) {
                    input.value = config.input_valor;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    console.log(`[aplicarConfiguracionVariacionesUI] ✓ Input llenado: ${config.input_id} = ${config.input_valor}`);
                }
            }
            
            // Llenar observación si existe
            if (config.tiene_observacion && config.observacion_id) {
                const obsInput = document.getElementById(config.observacion_id);
                if (obsInput && config.observacion_valor) {
                    obsInput.value = config.observacion_valor;
                    obsInput.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    console.log(`[aplicarConfiguracionVariacionesUI] ✓ Observación llenada: ${config.observacion_id} = ${config.observacion_valor}`);
                }
            }
        });
        
        console.log('[aplicarConfiguracionVariacionesUI]  Configuración UI aplicada completamente');
    }

    /**
     * Cargar procesos usando API DDD
     */
    async cargarProcesos(prenda) {
        console.log('📋 [cargarProcesos] Cargando procesos con API DDD:', {
            prendaId: prenda.id,
            timestamp: new Date().toLocaleTimeString()
        });
        
        try {
            // Llamar al endpoint de procesamiento de procesos
            const response = await fetch(`/api/prendas/${prenda.id}/procesar-procesos`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Error procesando procesos');
            }
            
            const procesosDTO = result.data;
            
            console.log('[cargarProcesos]  Procesos procesados del backend:', {
                total_procesos: procesosDTO.resumen.total_procesos,
                tipos_detectados: procesosDTO.resumen.tipos_detectados,
                tiene_procesos: procesosDTO.tiene_procesos,
                es_valida: procesosDTO.es_valida
            });
            
            // Aplicar configuración UI generada por el backend
            this.aplicarConfiguracionProcesosUI(procesosDTO);
            
            console.log('[cargarProcesos]  Procesos cargados exitosamente con DDD');
            
        } catch (error) {
            console.error('[cargarProcesos]  Error:', error);
            this.mostrarErrorAlUsuario('Error cargando procesos. Por favor intente nuevamente.');
        }
    }

    /**
     * Aplicar configuración de procesos UI desde el backend
     */
    aplicarConfiguracionProcesosUI(procesosDTO) {
        console.log('[aplicarConfiguracionProcesosUI]  Aplicando configuración UI:', {
            total_procesos: procesosDTO.resumen.total_procesos,
            tipos_detectados: procesosDTO.resumen.tipos_detectados
        });
        
        // Inicializar estructura global de procesos
        window.procesosSeleccionados = {};
        
        // Procesar cada proceso
        procesosDTO.procesos_procesados.forEach((proceso) => {
            const slug = proceso.slug;
            
            // Guardar en estructura global
            window.procesosSeleccionados[slug] = {
                datos: proceso
            };
            
            console.log(`[aplicarConfiguracionProcesosUI] 📌 Proceso "${slug}" guardado:`, proceso);
        });
        
        // Aplicar configuración UI
        Object.entries(procesosDTO.configuracion_ui).forEach(([slug, config]) => {
            // Marcar checkbox del proceso
            const checkboxProceso = document.getElementById(config.checkbox_id);
            
            if (checkboxProceso && config.checkbox_marcado) {
                console.log(`[aplicarConfiguracionProcesosUI] ☑️ Marcando checkbox para "${slug}"`);
                checkboxProceso.checked = true;
                checkboxProceso._ignorarOnclick = true;
                checkboxProceso.dispatchEvent(new Event('change', { bubbles: true }));
                checkboxProceso._ignorarOnclick = false;
            } else if (!checkboxProceso) {
                console.warn(`[aplicarConfiguracionProcesosUI] No se encontró checkbox para "${slug}". Buscando por data-tipo...`);
                
                // Intentar encontrar por data-tipo
                const checkboxPorTipo = document.querySelector(`[data-tipo="${config.data_tipo}"]`);
                if (checkboxPorTipo) {
                    console.log(`[aplicarConfiguracionProcesosUI] Encontrado por data-tipo, marcando...`);
                    checkboxPorTipo.checked = true;
                    checkboxPorTipo._ignorarOnclick = true;
                    checkboxPorTipo.dispatchEvent(new Event('change', { bubbles: true }));
                    checkboxPorTipo._ignorarOnclick = false;
                } else {
                    console.warn(`[aplicarConfiguracionProcesosUI] Tampoco encontrado checkbox por data-tipo="${config.data_tipo}"`);
                }
            }
        });
        
        console.log('[aplicarConfiguracionProcesosUI] 📋 Procesos seleccionados finales:', window.procesosSeleccionados);
        
        // Renderizar tarjetas de procesos
        if (window.renderizarTarjetasProcesos) {
            console.log('[aplicarConfiguracionProcesosUI] Renderizando tarjetas...');
            window.renderizarTarjetasProcesos();
        } else {
            console.error('[aplicarConfiguracionProcesosUI] window.renderizarTarjetasProcesos no existe');
        }
        
        console.log('[aplicarConfiguracionProcesosUI]  Configuración UI aplicada completamente');
    }

    /**
     * Aplicar origen automático desde cotización
     * FUERZA origen = 'bodega' si cotización es Reflectivo o Logo
     */
    aplicarOrigenAutomaticoDesdeCotizacion(prenda) {
        // Solo aplicar si hay cotización actual
        if (!this.cotizacionActual) {
            console.debug('[PrendaEditor] No hay cotización actual, omitiendo origen automático');
            return prenda;
        }

        const cotizacion = this.cotizacionActual;
        const tipoCotizacionId = cotizacion.tipo_cotizacion_id;
        const tiposQueFuerzanBodega = ['Reflectivo', 'Logo'];
        
        // Obtener el nombre del tipo de cotización
        let nombreTipo = null;
        if (cotizacion.tipo_cotizacion && cotizacion.tipo_cotizacion.nombre) {
            nombreTipo = cotizacion.tipo_cotizacion.nombre;
        } else if (cotizacion.tipo_nombre) {
            nombreTipo = cotizacion.tipo_nombre;
        }

        console.log('[aplicarOrigenAutomaticoDesdeCotizacion] Analizando cotización:', {
            tipoCotizacionId,
            nombreTipo,
            origenActual: prenda.origen
        });

        // Si es Reflectivo o Logo → FORZAR bodega sin importar de_bodega
        const esReflectivo = (nombreTipo && nombreTipo.toLowerCase() === 'reflectivo') || tipoCotizacionId === 'Reflectivo' || tipoCotizacionId === 2;
        const esLogo = (nombreTipo && nombreTipo.toLowerCase() === 'logo') || tipoCotizacionId === 'Logo' || tipoCotizacionId === 3;

        if (esReflectivo || esLogo) {
            prenda.origen = 'bodega';
            console.log('[aplicarOrigenAutomaticoDesdeCotizacion] FORZANDO origen = "bodega" (tipo:', nombreTipo || tipoCotizacionId, ')');
        } else {
            console.log('[aplicarOrigenAutomaticoDesdeCotizacion] Manteniendo origen original:', prenda.origen);
        }

        return prenda;
    }

    /**
     * Cargar datos adicionales de cotización
     */
    async cargarDatosCotizacionAdicionales(cotizacionId, prendaId) {
        console.log('[cargarDatosCotizacionAdicionales] Cargando datos adicionales:', {
            cotizacionId,
            prendaId
        });
        
        try {
            await this.cargarTelasDesdeCtizacion({ cotizacion_id: cotizacionId, prenda_id: prendaId });
        } catch (error) {
            console.warn('[cargarDatosCotizacionAdicionales] Error cargando datos adicionales:', error);
        }
    }

    /**
     * Cargar telas desde cotización
     */
    async cargarTelasDesdeCtizacion(prenda) {
        if (!prenda.prenda_id || !prenda.cotizacion_id) {
            console.debug('[cargarTelasDesdeCtizacion] No hay prenda_id o cotizacion_id');
            return;
        }

        console.log('[cargarTelasDesdeCtizacion] 🧵 Cargando telas y variaciones de cotización:', {
            prenda_cot_id: prenda.prenda_id,
            cotizacion_id: prenda.cotizacion_id
        });

        try {
            const response = await fetch(`/api/cotizaciones/${prenda.cotizacion_id}/prendas/${prenda.prenda_id}/telas-cotizacion`);
            
            if (!response.ok) {
                console.warn('[cargarTelasDesdeCtizacion] Endpoint no disponible');
                return;
            }
            
            const data = await response.json();
            const telas = data.telas || data.data?.telas || [];
            const variaciones = data.variaciones || data.data?.variaciones || [];
            
            console.log('[cargarTelasDesdeCtizacion] Datos cargados:', {
                telas_count: telas.length,
                variaciones_count: variaciones.length
            });

            // Procesar variaciones si existen
            if (variaciones && Array.isArray(variaciones) && variaciones.length > 0) {
                this.aplicarVariacionesReflectivo(variaciones);
            }
            
        } catch (error) {
            console.error('[cargarTelasDesdeCtizacion] Error:', error);
        }
    }

    /**
     * Aplicar variaciones de reflectivo
     */
    aplicarVariacionesReflectivo(variaciones) {
        console.log('[aplicarVariacionesReflectivo]  Aplicando variaciones:', variaciones);
        
        // Implementar lógica para aplicar variaciones al formulario
        variaciones.forEach(variacion => {
            // Aplicar cada variación según su tipo
            if (variacion.tipo === 'genero') {
                // Marcar género
                const checkbox = document.querySelector(`input[value="${variacion.valor}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
    }

    /**
     * Cambiar botón a modo de edición
     */
    cambiarBotonAGuardarCambios() {
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = '<span class="material-symbols-rounded">save</span>Guardar Cambios';
            btnGuardar.setAttribute('data-editing', 'true');
        }
    }

    /**
     * Mostrar imagen en modal
     */
    mostrarImagenEnModal(imagenUrl) {
        // Implementar modal de imagen si es necesario
        console.log('[mostrarImagenEnModal] Mostrando imagen:', imagenUrl);
    }

    /**
     * Mostrar error al usuario
     */
    mostrarErrorAlUsuario(mensaje) {
        // Implementar notificación de error al usuario
        if (window.mostrarNotificacion) {
            window.mostrarNotificacion(mensaje, 'error');
        } else {
            alert(mensaje);
        }
    }

    /**
     * Desactivar funcionalidad de tallas
     */
    desactivarFuncionalidadTallas() {
        // Desactivar inputs y botones relacionados con tallas
        const inputsTallas = document.querySelectorAll('[data-talla-input]');
        inputsTallas.forEach(input => {
            input.disabled = true;
            input.placeholder = 'No disponible';
        });
    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    /**
     * Resetear estado de edición
     */
    resetearEdicion() {
        this.prendaEditIndex = null;
        
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = '<span class="material-species-rounded">check</span>Agregar Prenda';
            btnGuardar.removeAttribute('data-editing');
        }
    }
    
    /**
     * Obtener índice de prenda siendo editada
     */
    obtenerPrendaEditIndex() {
        return this.prendaEditIndex;
    }
}

// Exportar la clase para uso global
window.PrendaEditor = PrendaEditor;

// NOTA: Este archivo depende de prenda-editor-legacy.js para métodos legados
// Asegurar que se cargue primero el archivo legacy
// console.log('[PrendaEditor]  PrendaEditor cargado (requiere prenda-editor-legacy.js)');
