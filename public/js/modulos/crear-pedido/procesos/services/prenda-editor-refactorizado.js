/**
 * PrendaEditor (REFACTORIZADO) - Orquestador de edición de prendas
 * 
 * Responsabilidad única: Orquestar las operaciones entre:
 * - Servicio de negocio (PrendaEditorService)
 * - Adaptador DOM (PrendaDOMAdapter)
 * - API (PrendaAPI)
 * - EventBus (para comunicación desacoplada)
 * 
 * CAMBIOS PRINCIPALES:
 * ✅ Sin lógica de negocio (→ ve al service)
 * ✅ Sin acceso directo al DOM (→ va al adapter)
 * ✅ Sin dependencias globales
 * ✅ Inyección de dependencias
 * ✅ Altamente testeable
 */
class PrendaEditor {
    constructor(opciones = {}) {
        // Inyección de dependencias
        this.notificationService = opciones.notificationService;
        
        // Services y adapters
        this.api = opciones.api || new PrendaAPI();
        this.eventBus = opciones.eventBus || new PrendaEventBus();
        this.domAdapter = opciones.domAdapter || new PrendaDOMAdapter();
        this.service = opciones.service || new PrendaEditorService({
            api: this.api,
            eventBus: this.eventBus
        });

        this.modalId = opciones.modalId || 'modal-agregar-prenda-nueva';

        // Registrar listeners de eventos
        this.registrarListenersEventos();

        console.log('[PrendaEditor] Inicializado con inyección de dependencias', {
            tieneApi: !!this.api,
            tieneEventBus: !!this.eventBus,
            tieneDomAdapter: !!this.domAdapter,
            tieneService: !!this.service
        });
    }

    /**
     * Registrar listeners de eventos del servicio
     * @private
     */
    registrarListenersEventos() {
        if (!this.eventBus) return;

        // Mostrar notificaciones de eventos importantes
        this.eventBus.on(PrendaEventBus.EVENTOS.ERROR_OCURRIDO, (error) => {
            this.mostrarNotificacion(`Error: ${error.mensaje}`, 'error');
        });

        this.eventBus.on(PrendaEventBus.EVENTOS.PRENDA_CARGADA, (datos) => {
            console.log('[PrendaEditor] Evento: Prenda cargada', datos);
        });

        this.eventBus.on(PrendaEventBus.EVENTOS.COTIZACION_ASIGNADA, (cotizacion) => {
            console.log('[PrendaEditor] Evento: Cotización asignada', cotizacion);
        });

        this.eventBus.on(PrendaEventBus.EVENTOS.TELAS_DESDE_COTIZACION, (telas) => {
            console.log('[PrendaEditor] Evento: Telas desde cotización', telas);
            this.actualizarPreviewTelasCotizacion(telas);
        });

        this.eventBus.on(PrendaEventBus.EVENTOS.PROCESOS_CARGADOS, (procesos) => {
            console.log('[PrendaEditor] Evento: Procesos cargados', procesos);
        });
    }

    /**
     * Abrirmodal para agregar o editar prenda
     */
    abrirModal(esEdicion = false, prendaIndex = null, cotizacionSeleccionada = null) {
        console.log('[PrendaEditor.abrirModal]', {
            esEdicion,
            prendaIndex,
            tieneCotizacion: !!cotizacionSeleccionada
        });

        // Asignar cotización si se proporciona
        if (cotizacionSeleccionada) {
            this.service.asignarCotizacion(cotizacionSeleccionada);
        }

        // Preparar modal según si es nueva o edición
        this.prepararModal(esEdicion, prendaIndex);

        // Cargar tipos de manga
        this.cargarTiposManga();

        // Abrir modal
        this.domAdapter.abrirModal();
        this.eventBus.emit(PrendaEventBus.EVENTOS.MODAL_ABIERTO);
    }

    /**
     * Preparar modal para edición
     * @private
     */
    prepararModal(esEdicion, prendaIndex) {
        // Llamar a cleanup global si existe (compatible con sistema anterior)
        if (window.ModalCleanup) {
            if (esEdicion) {
                window.ModalCleanup.prepararParaEditar(prendaIndex);
            } else {
                window.ModalCleanup.prepararParaNueva();
            }
        }
    }

    /**
     * Cargar tipos de manga desde API
     * @private
     */
    cargarTiposManga() {
        // Llamar a función global si existe (compat bilidad)
        if (typeof window.cargarTiposMangaDisponibles === 'function') {
            window.cargarTiposMangaDisponibles();
        }
    }

    /**
     * Cargar prenda en el modal para edición
     */
    async cargarPrendaEnModal(prenda, prendaIndex) {
        console.log('[PrendaEditor.cargarPrendaEnModal]', {
            nombre: prenda.nombre_prenda,
            index: prendaIndex
        });

        if (!prenda) {
            this.mostrarNotificacion('Prenda no válida', 'error');
            return;
        }

        try {
            // Asignar prenda al servicio
            this.service.asignarPrenda(prenda, prendaIndex);

            // Aplicar origen automático desde cotización
            const prendaProcesada = this.service.aplicarOrigenAutomaticoDesdeCotizacion({ ...prenda });

            // Abrir modal
            this.abrirModal(true, prendaIndex);

            // Llenar campos
            this.llenarCamposBasicos(prendaProcesada);
            this.cargarImagenes(prendaProcesada);
            this.cargarTelas(prendaProcesada);

            // Si es cotización Reflectivo/Logo, cargar desde cotización
            const { reflectivo, logo } = this.service.esCotizacionReflectivoOLogo();
            if (reflectivo || logo) {
                await this.service.cargarTelasDesdeCotizacion(prendaProcesada);
            }

            // Cargar variaciones
            this.service.procesarVariaciones(prendaProcesada);
            this.cargarVariaciones(prendaProcesada);

            // Cargar tallas
            this.cargarTallasYCantidades(prendaProcesada);

            // Cargar procesos
            this.cargarProcesos(prendaProcesada);

            // Cambiar botón
            this.cambiarBotonAGuardarCambios();

            this.mostrarNotificacion('Prenda cargada para editar', 'success');
        } catch (error) {
            console.error('[PrendaEditor] Error:', error);
            this.mostrarNotificacion(`Error al cargar prenda: ${error.message}`, 'error');
        }
    }

    /**
     * Llenar campos básicos
     * @private
     */
    llenarCamposBasicos(prenda) {
        // Aplica origen automático si hay cotización
        const prendaProcesada = this.service.aplicarOrigenAutomaticoDesdeCotizacion(prenda);

        // Llenar campos principales
        this.domAdapter.establecerNombrePrenda(prendaProcesada.nombre_prenda || '');
        this.domAdapter.establecerDescripcion(prendaProcesada.descripcion || '');

        // FORZAR origen aquí también si hay cotización (lógica del original)
        if (this.service.cotizacionActual) {
            const tipoCotizacionId = this.service.cotizacionActual.tipo_cotizacion_id;
            let nombreTipo = this.service.cotizacionActual.tipo_cotizacion?.nombre || 
                           this.service.cotizacionActual.tipo_nombre;
            
            const esReflectivo = (nombreTipo && nombreTipo.toLowerCase() === 'reflectivo') || 
                               tipoCotizacionId === 'Reflectivo' || 
                               tipoCotizacionId === 2 || 
                               tipoCotizacionId === 4;
            const esLogo = (nombreTipo && nombreTipo.toLowerCase() === 'logo') || 
                          tipoCotizacionId === 'Logo' || 
                          tipoCotizacionId === 3;
            
            if (esReflectivo || esLogo) {
                prendaProcesada.origen = 'bodega';
            }
        }

        // Establecer origen
        const origenAEstablecer = prendaProcesada.origen || 
                                 (prendaProcesada.de_bodega ? 'bodega' : 'confeccion');
        
        const exito = this.domAdapter.establecerOrigen(origenAEstablecer);
        
        if (!exito) {
            console.warn('[PrendaEditor] No se pudo establecer origen:', origenAEstablecer);
        }

        this.eventBus.emit(PrendaEventBus.EVENTOS.PRENDA_CARGADA, prendaProcesada);
    }

    /**
     * Cargar imágenes
     * @private
     */
    cargarImagenes(prenda) {
        console.log('[PrendaEditor.cargarImagenes]', {
            tieneImagenes: !!prenda.imagenes,
            tieneFotos: !!prenda.fotos,
            tieneProcesos: !!prenda.procesos
        });

        // CREAR ImageStorageService SI NO EXISTE (crítico)
        if (!window.imagenesPrendaStorage) {
            console.log('[PrendaEditor] Creando imagenesPrendaStorage...');
            
            try {
                // Intenta usar ImageStorageService si está disponible
                if (typeof ImageStorageService !== 'undefined') {
                    window.imagenesPrendaStorage = new ImageStorageService(3);
                    console.log('[PrendaEditor] ImageStorageService creado');
                }
                // Si no, usa fallback
                else if (typeof ImageStorageFallback !== 'undefined') {
                    window.imagenesPrendaStorage = new ImageStorageFallback(3);
                    console.log('[PrendaEditor] ImageStorageFallback creado');
                }
                // Si ninguno existe, crear fallback manual inline
                else {
                    console.warn('[PrendaEditor] Ni ImageStorageService ni ImageStorageFallback disponibles, creando fallback manual');
                    window.imagenesPrendaStorage = {
                        images: [],
                        limpiar: function() { 
                            this.images.forEach(img => {
                                if (img.previewUrl?.startsWith('blob:')) {
                                    URL.revokeObjectURL(img.previewUrl);
                                }
                            });
                            this.images = []; 
                        },
                        agregarImagen: function(file) {
                            return new Promise((resolve, reject) => {
                                if (!file?.type.startsWith('image/')) {
                                    reject(new Error('INVALID_FILE'));
                                    return;
                                }
                                if (this.images.length >= 3) {
                                    reject(new Error('MAX_LIMIT'));
                                    return;
                                }
                                this.images.push({
                                    previewUrl: URL.createObjectURL(file),
                                    nombre: file.name,
                                    tamaño: file.size,
                                    file: file
                                });
                                resolve({ success: true, images: this.images });
                            });
                        },
                        agregarUrl: function(url, nombre = 'imagen') {
                            this.images.push({
                                previewUrl: url,
                                nombre: nombre,
                                tamaño: 0,
                                file: null,
                                urlDesdeDB: true
                            });
                        },
                        obtenerImagenes: function() { return this.images; }
                    };
                }
            } catch (error) {
                console.error('[PrendaEditor] Error creando storage:', error);
                return;
            }
        }

        // Prioridad: imagenes → fotos → procesos
        let imagenesACargar = [];

        if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
            imagenesACargar = prenda.imagenes;
        } else if (prenda.fotos && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {
            imagenesACargar = prenda.fotos;
        } else if (prenda.procesos && typeof prenda.procesos === 'object') {
            // Buscar en procesos
            for (const [_, dataProceso] of Object.entries(prenda.procesos)) {
                if (dataProceso.imagenes && Array.isArray(dataProceso.imagenes)) {
                    imagenesACargar = dataProceso.imagenes;
                    break;
                }
            }
        }

        if (imagenesACargar.length > 0 && window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage.limpiar();
            
            imagenesACargar.forEach((img) => this.procesarImagen(img));
            
            // Actualizar preview con evento onClick
            this.actualizarPreviewImagenesConGaleria(imagenesACargar);
        }

        this.eventBus.emit(PrendaEventBus.EVENTOS.IMAGENES_CARGADAS, imagenesACargar);
    }

    /**
     * Procesar imagen individual - MANEJA TODOS LOS CASOS
     * @private
     */
    procesarImagen(img) {
        if (!img || !window.imagenesPrendaStorage) return;

        // CASO 1: img es un File directamente (formulario)
        if (img instanceof File) {
            console.log('[PrendaEditor] Procesando File object');
            window.imagenesPrendaStorage.agregarImagen(img);
        }
        // CASO 2: img es objeto con .file que es un File (wrapper)
        else if (img.file instanceof File) {
            console.log('[PrendaEditor] Procesando File dentro de objeto');
            window.imagenesPrendaStorage.agregarImagen(img.file);
        }
        // CASO 3: img es string URL directo (BD)
        else if (typeof img === 'string') {
            console.log('[PrendaEditor] Procesando string URL:', img);
            if (window.imagenesPrendaStorage.agregarUrl) {
                window.imagenesPrendaStorage.agregarUrl(img);
            } else {
                // Fallback manual
                if (!window.imagenesPrendaStorage.images) {
                    window.imagenesPrendaStorage.images = [];
                }
                window.imagenesPrendaStorage.images.push({
                    previewUrl: img,
                    nombre: 'imagen_bd',
                    tamaño: 0,
                    file: null,
                    urlDesdeDB: true
                });
            }
        }
        // CASO 4: img es objeto con URL/ruta (BD)
        else if (img.url || img.ruta || img.ruta_webp || img.ruta_original) {
            const urlImagen = img.url || img.ruta || img.ruta_webp || img.ruta_original;
            console.log('[PrendaEditor] Procesando objeto con URL:', urlImagen);
            
            if (window.imagenesPrendaStorage.agregarUrl) {
                window.imagenesPrendaStorage.agregarUrl(img);
            } else {
                if (!window.imagenesPrendaStorage.images) {
                    window.imagenesPrendaStorage.images = [];
                }
                window.imagenesPrendaStorage.images.push({
                    previewUrl: urlImagen,
                    nombre: img.nombre || 'imagen_bd',
                    tamaño: 0,
                    file: null,
                    urlDesdeDB: true,
                    ...img
                });
            }
        }
    }

    /**
     * Actualizar preview de imágenes CON galería interactiva
     * @private
     */
    actualizarPreviewImagenesConGaleria(imagenes) {
        if (!window.imagenesPrendaStorage?.images?.length) {
            return;
        }

        const primerImg = window.imagenesPrendaStorage.images[0];
        const urlImg = primerImg.previewUrl || primerImg.url;

        this.domAdapter.establecerPreviewImagen(urlImg);
        this.domAdapter.establecerContadorImagenes(window.imagenesPrendaStorage.images.length);

        // AGREGAR onClick PARA ABRIR GALERÍA (LÓGICA DEL ORIGINAL)
        setTimeout(() => {
            const preview = this.domAdapter.obtenerElemento('nueva-prenda-foto-preview');
            if (preview) {
                preview.onclick = (e) => {
                    e.stopPropagation();
                    
                    // Si existe función de galería global, usarla
                    if (typeof window.mostrarGaleriaImagenesPrenda === 'function') {
                        const imagenesConURL = window.imagenesPrendaStorage.images.map(img => ({
                            ...img,
                            url: img.previewUrl || img.url || img.ruta
                        }));
                        window.mostrarGaleriaImagenesPrenda(imagenesConURL, 0, 0);
                    } else {
                        console.log('[PrendaEditor] Función mostrarGaleriaImagenesPrenda no disponible');
                    }
                };
                console.log('[PrendaEditor] Handler onClick configurado en preview');
            }
        }, 100);
    }

    /**
     * Cargar telas - CON ENRIQUECIMIENTO DE REFERENCIAS
     * @private
     */
    cargarTelas(prenda) {
        console.log('[PrendaEditor.cargarTelas]', {
            telasCotizacion: prenda.telasAgregadas?.length || 0,
            colorestelas: prenda.colores_telas?.length || 0
        });

        let telasParaCargar = prenda.telasAgregadas || [];

        // Transformar colores_telas (BD) a telasAgregadas (frontend)
        if (telasParaCargar.length === 0 && prenda.colores_telas?.length > 0) {
            telasParaCargar = prenda.colores_telas.map(ct => ({
                nombre_tela: ct.nombre_tela || ct.tela?.nombre || 'N/A',
                color: ct.color || 'N/A',
                referencia: ct.referencia || ct.tela?.referencia || 'N/A',
                imagenes: [],
                origen: 'bd'
            }));
            prenda.telasAgregadas = telasParaCargar;
        }

        // 🔴 ENRIQUECIMIENTO DE REFERENCIAS DESDE VARIANTES (LÓGICA CRÍTICA FALTANTE)
        // Para Reflectivo/Logo que vienen sin referencias
        if (telasParaCargar.length > 0 && prenda.variantes) {
            const referenciasVacias = telasParaCargar.some(t => !t.referencia || t.referencia === 'N/A');
            
            if (referenciasVacias) {
                console.log('[PrendaEditor] Enriqueciendo telas desde variantes...');
                telasParaCargar = this.service.enriquecerTelasDesdeVariantes(
                    telasParaCargar,
                    prenda.variantes
                );
            }
        }

        // Guardar en servicio
        this.service.telasAgregadas = telasParaCargar;

        // Asignar a window para compatibilidad con otros scripts
        window.telasAgregadas = telasParaCargar;

        // Limpiar inputs de tela
        this.domAdapter.limpiarInputsTela();

        // Actualizar tabla si existe función
        if (window.actualizarTablaTelas) {
            window.actualizarTablaTelas();
        }

        this.eventBus.emit(PrendaEventBus.EVENTOS.TELAS_CARGADAS, telasParaCargar);
    }

    /**
     * Actualizar preview de telas de cotización
     * @private
     */
    actualizarPreviewTelasCotizacion(telas) {
        const contenedor = this.domAdapter.obtenerContenedorTelas();
        if (!contenedor) return;

        contenedor.innerHTML = '';
        telas.forEach((tela) => {
            const div = document.createElement('div');
            div.className = 'tela-cotizacion-item';
            div.innerHTML = `
                <div class="tela-info">
                    <strong>${tela.nombre_tela}</strong>
                    <span>${tela.color}</span>
                    <em>Ref: ${tela.referencia}</em>
                </div>
                ${tela.fotos?.length > 0 ? `
                    <div class="tela-fotos">
                        ${tela.fotos.map((foto, idx) => `
                            <img src="${foto}" alt="Tela ${idx + 1}" style="width: 60px; height: 60px; object-fit: cover;" />
                        `).join('')}
                    </div>
                ` : ''}
            `;
            contenedor.appendChild(div);
        });
    }

    /**
     * Cargar tallas y cantidades - CON APLICACIÓN AUTOMÁTICA A PROCESOS
     * @private
     */
    cargarTallasYCantidades(prenda) {
        // Procesar tallas desde diferentes fuentes
        const generosConTallas = prenda.generosConTallas || {};
        const cantidadTalla = prenda.cantidad_talla || {};

        // Combinar datos
        let tallasACargar = generosConTallas;
        if (Object.keys(tallasACargar).length === 0) {
            tallasACargar = cantidadTalla;
        }

        // Guardar en servicio
        this.service.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {} };
        Object.entries(tallasACargar).forEach(([genero, datos]) => {
            this.service.tallasRelacionales[genero.toUpperCase()] = datos.cantidades || datos || {};
        });

        // Asignar a window para compatibilidad
        window.tallasRelacionales = this.service.tallasRelacionales;

        // Si existe función para mostrar tallas
        if (window.mostrarTallasDisponibles) {
            window.mostrarTallasDisponibles();
        }

        // 🔴 APLICACIÓN AUTOMÁTICA DE TALLAS A PROCESOS (LÓGICA CRÍTICA DEL ORIGINAL)
        if (prenda.cotizacion_id && prenda.procesos && window.tallasRelacionales) {
            console.log('[PrendaEditor] 🔄 COTIZACIÓN DETECTADA: Aplicando tallas automáticamente a procesos...');
            
            setTimeout(() => {
                // Obtener tallas desde window.tallasRelacionales
                const tallasDama = window.tallasRelacionales.DAMA || {};
                const tallasCaballero = window.tallasRelacionales.CABALLERO || {};
                
                console.log('[PrendaEditor] Tallas a aplicar:', { 
                    dama: tallasDama, 
                    caballero: tallasCaballero 
                });
                
                // Recorrer todos los procesos y aplicar automáticamente las tallas
                const procesosArray = Array.isArray(prenda.procesos) ? 
                                     prenda.procesos : 
                                     Object.values(prenda.procesos);
                
                procesosArray.forEach(procesoSlug => {
                    const procesoDatos = procesoSlug.datos || procesoSlug;
                    
                    // Copiar tallas
                    procesoDatos.tallas = {
                        dama: { ...tallasDama },
                        caballero: { ...tallasCaballero }
                    };
                    
                    // Copiar talla_cantidad
                    procesoDatos.talla_cantidad = {
                        dama: { ...tallasDama },
                        caballero: { ...tallasCaballero }
                    };
                    
                    console.log('[PrendaEditor] Tallas aplicadas a proceso:', procesoDatos.tipo);
                });
                
                // Sincronizar con window.procesosSeleccionados
                if (window.procesosSeleccionados) {
                    Object.entries(window.procesosSeleccionados).forEach(([tipoProceso, procData]) => {
                        if (procData.datos) {
                            procData.datos.tallas = {
                                dama: { ...tallasDama },
                                caballero: { ...tallasCaballero }
                            };
                            procData.datos.talla_cantidad = {
                                dama: { ...tallasDama },
                                caballero: { ...tallasCaballero }
                            };
                        }
                    });
                }
                
                // Re-renderizar tarjetas con las tallas actualizadas
                if (window.renderizarTarjetasProcesos) {
                    console.log('[PrendaEditor] 🔄 Re-renderizando tarjetas con tallas...');
                    window.renderizarTarjetasProcesos();
                } else {
                    console.warn('[PrendaEditor] window.renderizarTarjetasProcesos no disponible');
                }
                
                this.eventBus.emit(PrendaEventBus.EVENTOS.TALLAS_CARGADAS, this.service.tallasRelacionales);
                console.log('[PrendaEditor]  TALLAS AUTOMÁTICAMENTE APLICADAS A PROCESOS ✓');
            }, 600); // DELAY para permitir rendering
        } else {
            this.eventBus.emit(PrendaEventBus.EVENTOS.TALLAS_CARGADAS, this.service.tallasRelacionales);
        }
    }

    /**
     * Cargar variaciones - LÓGICA COMPLETA CON NORMALIZACIÓN Y OBSERVACIONES
     * @private
     */
    cargarVariaciones(prenda) {
        const variantes = this.service.procesarVariaciones(prenda);

        console.log('[cargarVariaciones] 🔍 Variantes cargadas:', variantes);

        // Establecer género
        if (variantes.genero_id) {
            const generoMap = { 1: 'DAMA', 2: 'CABALLERO' };
            const genero = generoMap[variantes.genero_id];
            if (genero) {
                this.domAdapter.marcarGenero(genero, true);
            }
        }

        // MANGA - CON OBSERVACIONES
        if (variantes.tipo_manga || variantes.obs_manga) {
            console.log('[cargarVariaciones] 📝 Cargando MANGA:', { tipo: variantes.tipo_manga, obs: variantes.obs_manga });
            
            const mangaOpcion = variantes.tipo_manga;
            this.domAdapter.marcarVariacion('manga', true);
            
            if (mangaOpcion && mangaOpcion !== 'No aplica') {
                const mangaNormalizado = this.service.normalizarValorVariacion(mangaOpcion);
                this.domAdapter.establecerVariacionInput('manga', mangaNormalizado);
            }
            
            // APLICAR CON DELAY MAYOR para asegurar que todo se procese
            this.aplicarVariacionRefleXitaConDelay('manga', mangaOpcion, variantes.obs_manga, 100);
        }

        // BOLSILLOS - SOLO OBSERVACIONES
        if (variantes.haben_bolsillos === true || variantes.tiene_bolsillos === true || variantes.obs_bolsillos) {
            console.log('[cargarVariaciones] 📝 Cargando BOLSILLOS:', { tiene: variantes.tiene_bolsillos, obs: variantes.obs_bolsillos });
            
            this.domAdapter.marcarVariacion('bolsillos', true);
            
            // BOLSILLOS solo tiene observaciones, no input
            this.aplicarVariacionRefleXitaConDelay('bolsillos', null, variantes.obs_bolsillos, 100);
        }

        // BROCHE/BOTÓN - CON OBSERVACIONES
        if (variantes.tipo_broche || variantes.obs_broche) {
            console.log('[cargarVariaciones] 📝 Cargando BROCHE:', { tipo: variantes.tipo_broche, obs: variantes.obs_broche });
            
            const brocheOpcion = variantes.tipo_broche;
            this.domAdapter.marcarVariacion('broche', true);
            
            if (brocheOpcion && brocheOpcion !== 'No aplica') {
                const brocheNormalizado = this.service.normalizarValorVariacion(brocheOpcion);
                this.domAdapter.establecerVariacionInput('broche', brocheNormalizado);
            }
            
            this.aplicarVariacionRefleXitaConDelay('broche', brocheOpcion, variantes.obs_broche, 100);
        }

        // REFLECTIVO - CON OBSERVACIONES
        if (variantes.tiene_reflectivo === true || variantes.obs_reflectivo) {
            console.log('[cargarVariaciones] 📝 Cargando REFLECTIVO:', { tiene: variantes.tiene_reflectivo, obs: variantes.obs_reflectivo });
            
            this.domAdapter.marcarVariacion('reflectivo', true);
            this.aplicarVariacionRefleXitaConDelay('reflectivo', null, variantes.obs_reflectivo, 100);
        }

        console.log('[cargarVariaciones] ✅ Variaciones cargadas completamente');
    }

    /**
     * Aplicar variación refleCtiva con delay (LÓGICA DEL ORIGINAL)
     * Habilita campos de input y observaciones después de marcar checkbox
     * @private
     */
    aplicarVariacionRefleXitaConDelay(nombreVariacion, valor, observacion, delayMs = 100) {
        // Esperar a que el event handler del checkbox se ejecute y habilite los campos
        setTimeout(() => {
            const config = {
                'manga': { input: '#manga-input', obs: '#manga-obs' },
                'bolsillos': { input: null, obs: '#bolsillos-obs' },
                'broche': { input: '#broche-input', obs: '#broche-obs' },
                'reflectivo': { input: null, obs: '#reflectivo-obs' }
            };

            const cfg = config[nombreVariacion];
            if (!cfg) return;

            // Habilitar input si existe y tiene valor
            if (cfg.input) {
                const inputEl = document.querySelector(cfg.input);
                if (inputEl) {
                    inputEl.disabled = false;
                    inputEl.style.opacity = '1';
                    if (valor && valor !== 'No aplica') {
                        inputEl.value = valor;
                        inputEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    console.log(`[aplicarVariacionRefleXitaConDelay] ✅ Input '${nombreVariacion}' habilitado`);
                }
            }

            // IMPORTANTE: Habilitar y llenar observaciones (aplica a TODOS los tipos)
            if (cfg.obs) {
                const obsEl = document.querySelector(cfg.obs);
                if (obsEl) {
                    obsEl.disabled = false;
                    obsEl.style.opacity = '1';
                    // Remove readonly attribute if present
                    obsEl.removeAttribute('readonly');
                    
                    if (observacion && observacion.trim()) {
                        obsEl.value = observacion.trim();
                        obsEl.dispatchEvent(new Event('change', { bubbles: true }));
                        console.log(`[aplicarVariacionRefleXitaConDelay] ✅ Observación '${nombreVariacion}' cargada: "${observacion.substring(0, 50)}..."`);
                    } else {
                        obsEl.value = '';
                        console.log(`[aplicarVariacionRefleXitaConDelay] ℹ️ Campo observación '${nombreVariacion}' habilitado pero vacío`);
                    }
                } else {
                    console.warn(`[aplicarVariacionRefleXitaConDelay] ⚠️ Campo observación '${cfg.obs}' NO ENCONTRADO en el DOM`);
                }
            }
        }, delayMs); // DELAY CONFIGURABLE (default 100ms, suficiente para procesar eventos)
    }

    /**
     * Cargar procesos
     * @private
     */
    cargarProcesos(prenda) {
        console.log('[PrendaEditor.cargarProcesos]', {
            tieneProcesos: !!prenda.procesos
        });

        if (!prenda.procesos) return;

        // Procesar procesos con validaciones
        this.service.procesarProcesos(prenda.procesos);

        // Marcar checkboxes de procesos
        Object.keys(this.service.procesosSeleccionados).forEach(tipoProceso => {
            this.domAdapter.marcarProceso(tipoProceso, true);
        });

        // Asignar a window para compatibilidad
        window.procesosSeleccionados = this.service.procesosSeleccionados;

        // Renderizar tarjetas si existe función
        if (window.renderizarTarjetasProcesos) {
            window.renderizarTarjetasProcesos();
        }

        this.eventBus.emit(PrendaEventBus.EVENTOS.PROCESOS_CARGADOS, this.service.procesosSeleccionados);
    }

    /**
     * Cambiar botón a "Guardar Cambios"
     * @private
     */
    cambiarBotonAGuardarCambios() {
        const prenda = this.service.obtenerPrendaActual();
        if (prenda && prenda.cotizacion_id) {
            // Si viene de cotización, mantener "Agregar Prenda"
            this.domAdapter.establecerBotoGuardar(
                '<span class="material-symbols-rounded">check</span>Agregar Prenda'
            );
        } else {
            // Edición normal
            this.domAdapter.establecerBotoGuardar(
                '<span class="material-symbols-rounded">save</span>Guardar Cambios',
                { editing: 'true' }
            );
        }
    }

    /**
     * Resetear estado de edición
     */
    resetearEdicion() {
        this.service.resetear();
        this.domAdapter.limpiarCache();
        this.domAdapter.cerrarModal();
        this.domAdapter.establecerBotoGuardar(
            '<span class="material-symbols-rounded">check</span>Agregar Prenda'
        );
        this.eventBus.emit(PrendaEventBus.EVENTOS.PRENDA_CERRADA);
    }

    /**
     * Obtener índice de prenda siendo editada
     */
    obtenerPrendaEditIndex() {
        return this.service.prendaEditIndex;
    }

    /**
     * Verificar si está en modo edición
     */
    estaEditando() {
        return this.service.prendaEditIndex !== null && this.service.prendaEditIndex !== undefined;
    }

    /**
     * Cargar múltiples prendas desde cotización
     */
    cargarPrendasDesdeCotizacion(prendas, cotizacion) {
        return this.service.cargarPrendasDesdeCotizacion(prendas, cotizacion);
    }

    /**
     * Obtener estado actual (para debugging)
     */
    obtenerEstado() {
        return this.service.obtenerEstado();
    }

    /**
     * Obtener servicio (para acceso en casos especiales)
     */
    obtenerServicio() {
        return this.service;
    }

    /**
     * Mostrar notificación
     * @private
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
        if (this.notificationService) {
            this.notificationService.mostrar(mensaje, tipo);
        } else {
            console.log(`[Notificación - ${tipo.toUpperCase()}] ${mensaje}`);
        }
    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        this.domAdapter.cerrarModal();
        this.eventBus.emit(PrendaEventBus.EVENTOS.MODAL_CERRADO);
    }
}

window.PrendaEditor = PrendaEditor;
