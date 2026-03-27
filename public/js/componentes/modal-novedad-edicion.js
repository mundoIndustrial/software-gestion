/**
 * Modal Novedad Edición - Componente Reutilizable
 * Maneja modales para registrar novedades antes de actualizar una prenda existente
 */



class ModalNovedadEdicion {
    constructor() {
        this.pedidoId = null;
        this.prendaData = null;
        this.prendaIndex = null;
        this.zIndexMaximoForzado = 999999;
        
        // Inicializar arrays separados por flujo (NO afectarse mutuamente)
        if (!window.telasCreacion) {
            window.telasCreacion = [];  // Para flujo de CREACIÓN
        }
        if (!window.telasEdicion) {
            window.telasEdicion = [];   // Para flujo de EDICIÓN
        }
        // NO obtener usuario aquí - hacerlo cada vez que se necesite
    }

    /**
     * Obtener información del usuario actual (cada vez que se llame)
     * @private
     */
    obtenerUsuarioActual() {
        // Obtener directamente de window.usuarioAutenticado (se define en layout.blade.php)
        if (window.usuarioAutenticado) {
            return window.usuarioAutenticado;
        }
        
        // Fallback por si no está disponible
        return {
            nombre: 'Usuario',
            rol: 'Sin Rol',
            email: ''
        };
    }

    /**
     * Obtener nombre legible del rol
     * @private
     */
    obtenerNombreRolLegible(rolTecnico) {
        const mapeoRoles = {
            'supervisor_pedidos': 'Supervisor de Pedidos',
            'supervisor_asesores': 'Supervisor de Asesores',
            'supervisor-admin': 'Supervisor Administrador',
            'asesor': 'Asesor',
            'contador': 'Contador',
            'cortador': 'Cortador',
            'supervisor': 'Supervisor',
            'costurero': 'Costurero',
            'patron': 'Patronista',
            'patronista': 'Patronista',
            'bordado': 'Bordado',
            'despacho': 'Despacho',
            'cartera': 'Cartera',
            'produccion': 'Producción',
            'admin': 'Administrador',
        };
        
        return mapeoRoles[rolTecnico] || (rolTecnico || 'Sin Rol');
    }

    /**
     * Construir novedad con información de usuario, rol, fecha/hora y razón
     * Formato: [rol-DD-MM-YYYY HH:MM:SS] descripción
     * @private
     */
    construirNovedadConMetadata(razonDelCambio) {
        const usuarioActual = this.obtenerUsuarioActual();
        
        const ahora = new Date();
        const dia = String(ahora.getDate()).padStart(2, '0');
        const mes = String(ahora.getMonth() + 1).padStart(2, '0');
        const año = ahora.getFullYear();
        const fecha = `${dia}-${mes}-${año}`;
        
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        const hora = `${horas}:${minutos}:${segundos}`;
        
        const rolTecnico = (usuarioActual.rol || 'Sin Rol');
        const rolLegible = this.obtenerNombreRolLegible(rolTecnico);
        
        const novedad = `[${rolLegible}-${fecha} ${hora}] ${razonDelCambio}`;
        return novedad;
    }

    forzarZIndexMaximo() {
        const container = document.querySelector('.swal2-container');
        const popup = document.querySelector('.swal2-popup');
        const backdrop = document.querySelector('.swal2-backdrop');
        if (container) container.style.zIndex = this.zIndexMaximoForzado;
        if (popup) popup.style.zIndex = this.zIndexMaximoForzado;
        if (backdrop) backdrop.style.zIndex = (this.zIndexMaximoForzado - 1);
    }

    async mostrarModalYActualizar(pedidoId, prendaData, prendaIndex) {
        this.pedidoId = pedidoId;
        this.prendaData = prendaData;
        this.prendaIndex = prendaIndex;
        
        // CRITICO: Guardar variantes ORIGINALES para detectar si fueron modificadas
        // Si solo se edita sobremedida, no enviar variantes al backend
        this.variantesOriginalesAlAbrirModal = prendaData?.variantes 
            ? JSON.parse(JSON.stringify(prendaData.variantes))
            : [];
        
        // CRITICO: Guardar imagenes ORIGINALES para detectar eliminaciones
        // IMPORTANTE: Leer de window.imagenesPrendaStorage.snapshotOriginal (estado en memoria guardado al cargar)
        // NO de prendaData.imagenes (que puede haber cambiado en el servidor)
        // El snapshot captura el estado REAL cuando se cargó la prenda inicialmente
        let snapshotRaw = window.imagenesPrendaStorage?.snapshotOriginal 
            ? JSON.parse(JSON.stringify(window.imagenesPrendaStorage.snapshotOriginal)) 
            : (prendaData?.imagenes ? JSON.parse(JSON.stringify(prendaData.imagenes)) : []);
        
        //  FIX: Limpiar imágenes vacías del snapshot (pueden ser placeholders sin datos)
        this.imagenesOriginalesAlAbrirModal = snapshotRaw.filter(img => 
            img && Object.keys(img).length > 0 && (img.previewUrl || img.url || img.ruta_webp || img.ruta_original)
        );
        
        console.log('[modal-novedad-edicion] Imagenes originales guardadas (desde SNAPSHOT o prendaData):', {
            cantidad: this.imagenesOriginalesAlAbrirModal.length,
            cantidadRaw: snapshotRaw.length,
            filtradas: snapshotRaw.length - this.imagenesOriginalesAlAbrirModal.length,
            datos: this.imagenesOriginalesAlAbrirModal
        });

        // DEBUG: Verificar qué contiene prendaData al llegar
        console.log('[modal-novedad-edicion]  DEBUG prendaData recibido:', {
            prendaDataCompleto: prendaData,
            prenda_pedido_id: prendaData?.prenda_pedido_id,
            id: prendaData?.id,
            tipo: prendaData?.tipo,
            nombre_prenda: prendaData?.nombre_prenda
        });

        // CRÍTICO: LÓGICA CORREGIDA
        // Si el usuario eliminó imágenes en la galería ANTES de abrir el modal,
        // debemos preservar esos cambios en el storage.
        // Solo reinicializar si el storage está vacío.
        console.log('[modal-novedad-edicion]  DEBUG prendaData.imagenes:', {
            existe: !!prendaData.imagenes,
            esArray: Array.isArray(prendaData.imagenes),
            cantidad: prendaData.imagenes?.length || 0,
            datos: prendaData.imagenes,
            primerImage_id: prendaData.imagenes?.[0]?.id,
            primerImage_previewUrl: prendaData.imagenes?.[0]?.previewUrl
        });
        
        //  DEBUG CRÍTICO: Comparación snapshot vs prendaData
        console.log('[modal-novedad-edicion]  COMPARACION_SNAPSHOT_VS_PRENDADATA:', {
            snapshot_cantidad: this.imagenesOriginalesAlAbrirModal?.length || 0,
            prendaData_cantidad: prendaData.imagenes?.length || 0,
            coinciden: (this.imagenesOriginalesAlAbrirModal?.length || 0) === (prendaData.imagenes?.length || 0),
            snapshot_ids: this.imagenesOriginalesAlAbrirModal?.map(i => i.id),
            prendaData_ids: prendaData.imagenes?.map(i => i.id || i.previewUrl)
        });
        
        if (window.imagenesPrendaStorage && prendaData && prendaData.imagenes) {
            const imagenesActualesEnStorage = window.imagenesPrendaStorage.obtenerImagenes();
            
            //  CRITICAL FIX: Actualizar snapshotOriginal con las imágenes actuales del servidor
            // Esto asegura que cuando el usuario abre el modal por segunda vez,
            // las imágenes originales sean las correctas para detectar eliminaciones
            window.imagenesPrendaStorage.snapshotOriginal = JSON.parse(JSON.stringify(prendaData.imagenes));
            console.log('[modal-novedad-edicion]  [SNAPSHOT-SYNC-INICIAL] Snapshot sincronizado con', prendaData.imagenes.length, 'imágenes del servidor');
            
            //  FIX CRÍTICO: No sobrescribir snapshot válido desde prenda-editor-modal.js
            // El snapshot debería tener IDs porque prendaData.imagenes ya viene mapeado
            const snapshotValido = window.imagenesPrendaStorage.snapshotOriginal && window.imagenesPrendaStorage.snapshotOriginal.length > 0;
            
            if (!imagenesActualesEnStorage || imagenesActualesEnStorage.length === 0) {
                // Storage vacío → inicializar con imágenes del servidor
                console.log('[modal-novedad-edicion]  [INIT-SYNC-VACÍO] Storage está vacío, inicializando con', prendaData.imagenes.length, 'imágenes del servidor');
                // Las imágenes ahora vienen mapeadas con IDs desde prenda-editor-modal.js
                window.imagenesPrendaStorage.establecerImagenes(prendaData.imagenes);
                console.log('[modal-novedad-edicion]  [INIT-SYNC-RESULTADO] Snapshot establecido con', prendaData.imagenes.length, 'imágenes (con IDs)');
            } else {
                // Storage tiene imágenes → el usuario ya las modificó en la galería
                // PRESERVAR los cambios del usuario
                console.log('[modal-novedad-edicion]  [INIT-SYNC-PRESERVAR] Storage ya tiene', imagenesActualesEnStorage.length, 'imágenes - PRESERVANDO cambios del usuario (servidor tenía', prendaData.imagenes.length + ')');
            }
        }

        //  FIX CRÍTICO: Cargar procesos existentes en window.procesosSeleccionados
        // Esto asegura que cuando el usuario edite procesos existentes, se puedan guardar las ubicaciones nuevas
        if (prendaData && prendaData.procesos && typeof prendaData.procesos === 'object') {
            console.log('[modal-novedad-edicion]  [CARGAR-PROCESOS] Cargando procesos existentes de la prenda:', {
                prendaId: prendaData.prenda_pedido_id || prendaData.id,
                tieneProcesosbool: !!prendaData.procesos,
                esObjeto: typeof prendaData.procesos === 'object',
                procesosKeys: Array.isArray(prendaData.procesos) ? prendaData.procesos.map(p => p.tipo) : Object.keys(prendaData.procesos)
            });

            // Inicializar window.procesosSeleccionados si no existe
            if (!window.procesosSeleccionados || typeof window.procesosSeleccionados !== 'object') {
                window.procesosSeleccionados = {};
            }

            // Cargar procesos existentes
            if (Array.isArray(prendaData.procesos)) {
                // Si viene como array, convertir a objeto indexado por tipo
                prendaData.procesos.forEach(proc => {
                    // Obtener el tipo del proceso - puede venir como 'tipo_proceso' o 'tipo'
                    // 'tipo_proceso' es el nombre (ej: "Bordado"), necesitamos convertir a slug
                    let tipoSlug = proc.tipo;
                    if (!tipoSlug && proc.tipo_proceso) {
                        // Convertir nombre a slug: "Bordado" -> "bordado"
                        tipoSlug = proc.tipo_proceso.toLowerCase().replace(/\s+/g, '-');
                    }
                    
                    if (proc && tipoSlug) {
                        // Mapear tallas_detalles a datosExtendidos si existen
                        let datosExtendidos = {};
                        if (proc.tallas_detalles && typeof proc.tallas_detalles === 'object') {
                            Object.entries(proc.tallas_detalles).forEach(([genero, tallas]) => {
                                datosExtendidos[genero.toLowerCase()] = tallas || {};
                            });
                        }
                        
                        window.procesosSeleccionados[tipoSlug] = {
                            id: proc.id,
                            tipo: tipoSlug,
                            tipo_proceso_id: proc.tipo_proceso_id,
                            modoTallas: proc.modo_tallas || 'generico',
                            datos: {
                                id: proc.id,
                                tipo_proceso_id: proc.tipo_proceso_id,
                                tipo: tipoSlug,
                                nombre: proc.tipo_proceso,
                                ubicaciones: proc.ubicaciones || [],
                                observaciones: proc.observaciones || '',
                                estado: proc.estado || 'PENDIENTE',
                                tipo_proceso: proc.tipo_proceso,
                                tallas: proc.tallas || {},
                                imagenes: proc.imagenes || [],
                                datosExtendidos: datosExtendidos,
                                tallas_detalles: proc.tallas_detalles || {},
                                modoTallas: proc.modo_tallas || 'generico',
                                modo_tallas: proc.modo_tallas || 'generico',
                                created_at: proc.created_at
                            }
                        };
                        console.log('[modal-novedad-edicion]  [CARGAR-PROCESOS] Proceso cargado:', {
                            tipoSlug: tipoSlug,
                            id: proc.id,
                            tipo_proceso_id: proc.tipo_proceso_id,
                            tipo_proceso: proc.tipo_proceso,
                            modo_tallas: proc.modo_tallas,
                            tieneTallasDetalles: !!proc.tallas_detalles,
                            tallesDetallesKeys: proc.tallas_detalles ? Object.keys(proc.tallas_detalles) : [],
                            ubicacionesCount: Array.isArray(proc.ubicaciones) ? proc.ubicaciones.length : (proc.ubicaciones ? 1 : 0),
                            ubicacionesValue: proc.ubicaciones,
                            imagenesCount: Array.isArray(proc.imagenes) ? proc.imagenes.length : (proc.imagenes ? 1 : 0),
                            imagenesValue: proc.imagenes
                        });
                    }
                });
            } else {
                // Si viene como objeto, copiar directamente
                console.log('[modal-novedad-edicion]  [CARGAR-PROCESOS-OBJETO] Estructura recibida:', {
                    procesosKeys: Object.keys(prendaData.procesos),
                    primerProcesoKeys: prendaData.procesos[Object.keys(prendaData.procesos)[0]] ? Object.keys(prendaData.procesos[Object.keys(prendaData.procesos)[0]]) : 'N/A',
                    primerProcesoStructure: prendaData.procesos[Object.keys(prendaData.procesos)[0]]
                });
                
                Object.keys(prendaData.procesos).forEach(tipo => {
                    const proc = prendaData.procesos[tipo];
                    if (proc) {
                        // Normalizar: puede venir como {datos: {...}} o como {...}
                        const datosProc = proc.datos || proc;
                        const procId = datosProc.id;
                        const procTipoProceso = datosProc.tipo_proceso_id;
                        
                        // Mapear tallas_detalles a datosExtendidos si existen
                        let datosExtendidos = {};
                        if (datosProc.tallas_detalles && typeof datosProc.tallas_detalles === 'object') {
                            Object.entries(datosProc.tallas_detalles).forEach(([genero, tallas]) => {
                                datosExtendidos[genero.toLowerCase()] = tallas || {};
                            });
                        }
                        
                        // Asegurar que id y tipo_proceso_id están en datos
                        const datosNormalizados = {
                            ...datosProc,
                            id: procId,
                            tipo_proceso_id: procTipoProceso,
                            datosExtendidos: datosExtendidos,
                            modoTallas: datosProc.modo_tallas || datosProc.modoTallas || 'generico',
                            modo_tallas: datosProc.modo_tallas || datosProc.modoTallas || 'generico'
                        };
                        
                        window.procesosSeleccionados[tipo] = {
                            id: procId,
                            tipo: tipo,
                            tipo_proceso_id: procTipoProceso,
                            modoTallas: datosProc.modo_tallas || 'generico',
                            datos: datosNormalizados
                        };
                        
                        console.log('[modal-novedad-edicion]  [CARGAR-PROCESOS] Proceso objeto cargado:', {
                            tipo: tipo,
                            id: procId,
                            tipo_proceso_id: procTipoProceso,
                            tieneUbicaciones: !!datosNormalizados.ubicaciones,
                            modo_tallas: datosNormalizados.modo_tallas,
                            tieneTallasDetalles: !!datosNormalizados.tallas_detalles,
                            datosKeys: Object.keys(datosNormalizados)
                        });
                    }
                });
            }

            console.log('[modal-novedad-edicion]  [CARGAR-PROCESOS] Procesos cargados en window.procesosSeleccionados:', {
                cantidad: Object.keys(window.procesosSeleccionados).length,
                tipos: Object.keys(window.procesosSeleccionados)
            });
        }

        //  FIX CRÍTICO: Cargar telas existentes en window.telasAgregadas (no telasEdicion)
        // Esto permite que al editar una prenda existente que tiene telas, se carguen en el storage
        // para que cuando el usuario agregue/elimine telas, se envíen TODAS al backend
        // El código en gestion-telas.js detecta modo edición por window.telasAgregadas
        
        // Buscar telas en múltiples ubicaciones posibles (colores_telas, telasAgregadas, telas_array)
        const telasExistentes = prendaData?.colores_telas || prendaData?.telasAgregadas || prendaData?.telas_array || [];
        
        if (telasExistentes && Array.isArray(telasExistentes) && telasExistentes.length > 0) {
            console.log('[modal-novedad-edicion]  [CARGAR-TELAS] Cargando telas existentes de la prenda:', {
                prendaId: prendaData.prenda_pedido_id || prendaData.id,
                telasExistentes: telasExistentes.length,
                origen: prendaData?.colores_telas ? 'colores_telas' : (prendaData?.telasAgregadas ? 'telasAgregadas' : 'telas_array'),
                datosConImagines: telasExistentes.map(t => ({
                    id: t.id,
                    color: t.color || t.color_nombre || '',
                    tela: t.tela || t.tela_nombre || t.nombre || '',
                    imagenes: (t.imagenes_tela || t.fotos_tela || t.imagenes || t.fotos || []).length
                }))
            });

            // Inicializar window.telasAgregadas si no existe (para modo edición)
            if (!window.telasAgregadas) {
                window.telasAgregadas = [];
            }

            //  CRÍTICO: Solo cargar las telas existentes SI window.telasAgregadas está vacío
            // Si el usuario ya agregó telas nuevas, las conservamos sin limpiar
            if (window.telasAgregadas.length === 0) {
                telasExistentes.forEach(telaDeserv => {
                    // Crear objeto tela con estructura esperada por formulario
                    // Soportar múltiples formatos posibles
                    const telaObj = {
                        id: telaDeserv.id,                          // ID de relación (prenda_pedido_colores_telas.id)
                        color_id: telaDeserv.color_id || null,     // ID del color
                        tela_id: telaDeserv.tela_id || null,       // ID de la tela
                        color: telaDeserv.color || telaDeserv.color_nombre || '',              // Nombre del color
                        tela: telaDeserv.tela || telaDeserv.tela_nombre || telaDeserv.nombre || '',                // Nombre de la tela
                        referencia: telaDeserv.referencia || telaDeserv.tela_referencia || '',    // Referencia
                        color_nombre: telaDeserv.color || telaDeserv.color_nombre || '',
                        tela_nombre: telaDeserv.tela || telaDeserv.tela_nombre || telaDeserv.nombre || '',         // Normalizar para que sea compatible
                        nombre_tela: telaDeserv.tela || telaDeserv.tela_nombre || telaDeserv.nombre || '',
                        imagenes: []
                    };

                    // Cargar imágenes de tela si existen - soportar múltiples nombres de propiedad
                    const imagenesTelaRaw = telaDeserv.imagenes_tela || telaDeserv.fotos_tela || telaDeserv.imagenes || telaDeserv.fotos || [];
                    
                    if (Array.isArray(imagenesTelaRaw) && imagenesTelaRaw.length > 0) {
                        telaObj.imagenes = imagenesTelaRaw.map(img => {
                            //  CRÍTICO: Detectar si es imagen nueva (tiene previewUrl/file) o de BD
                            const esImagenNueva = img.file instanceof File || (img.previewUrl && img.previewUrl.startsWith('blob:'));
                            
                            if (esImagenNueva) {
                                //  IMAGEN NUEVA: Preservar previewUrl y file
                                return {
                                    id: img.id || undefined,
                                    previewUrl: img.previewUrl,  // Blob URL válida
                                    url: img.url || img.previewUrl,  // Fallback
                                    file: img.file instanceof File ? img.file : null,
                                    nombre: img.nombre || 'imagen_nueva',
                                    tamano: img.tamano,
                                    urlDesdeDB: false,  // Marca correctamente que es NUEVA
                                    ruta_original: img.ruta_original,
                                    ruta_webp: img.ruta_webp
                                };
                            } else {
                                //  IMAGEN DE BD: Los datos vienen del servidor
                                return {
                                    id: img.id,
                                    url: img.url || img.ruta_webp || img.ruta_original,
                                    ruta: img.url || img.ruta_webp || img.ruta_original,
                                    urlDesdeDB: true,  // Marca correctamente que viene de BD
                                    nombre: img.nombre || `imagen_${img.id}`,
                                    prenda_pedido_colores_telas_id: telaDeserv.id,
                                    ruta_original: img.ruta_original,
                                    ruta_webp: img.ruta_webp,
                                    file: null  // No hay File objeto para imágenes de BD
                                };
                            }
                        });
                    }

                    window.telasAgregadas.push(telaObj);
                    console.log('[modal-novedad-edicion]  [CARGAR-TELAS] Tela cargada:', {
                        id: telaObj.id,
                        color: telaObj.color,
                        tela: telaObj.tela,
                        imagenes: telaObj.imagenes.length,
                        tiposImagenes: telaObj.imagenes.map(i => ({ esNueva: !i.urlDesdeDB, previewUrl: !!i.previewUrl }))
                    });
                });

                console.log('[modal-novedad-edicion]  [CARGAR-TELAS] Telas cargadas en window.telasAgregadas:', {
                    cantidad: window.telasAgregadas.length,
                    telas: window.telasAgregadas.map(t => ({ id: t.id, color: t.color, tela: t.tela }))
                });

                // Actualizar tabla de telas si existe
                if (window.actualizarTablaTelas) {
                    window.actualizarTablaTelas();
                }
            } else {
                console.log('[modal-novedad-edicion]  [CARGAR-TELAS] window.telasAgregadas ya tiene telas, preservando:', {
                    cantidad: window.telasAgregadas.length
                });
            }
        }

        return new Promise((resolve) => {
            const html = `
                <div style="text-align: left;">
                    <textarea id="modalNovedadEdicion" placeholder="Ej: Se cambió el color a rojo..." 
                              style="width: 100%; padding: 0.75rem; border: 2px solid #3b82f6; border-radius: 6px; 
                                     font-size: 0.95rem; min-height: 120px; font-family: inherit; resize: vertical;"></textarea>
                </div>
            `;

            Swal.fire({
                title: 'Registrar Cambios en Prenda',
                html: html,
                icon: 'info',
                confirmButtonText: '✓ Guardar Cambios',
                confirmButtonColor: '#3b82f6',
                cancelButtonText: 'Cancelar',
                showCancelButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                position: 'center',
                didOpen: () => {
                    console.log(' [MODAL-NOVEDAD] didOpen iniciado');
                    this.forzarZIndexMaximo();
                    const textarea = document.getElementById('modalNovedadEdicion');
                    if (textarea) textarea.focus();
                    
                    //  Asegurar que el modal esté centrado
                    const swalContainer = document.querySelector('.swal2-container');
                    const swalPopup = document.querySelector('.swal2-popup');
                    
                    console.log(' [MODAL-NOVEDAD] swalContainer existe?:', !!swalContainer);
                    console.log(' [MODAL-NOVEDAD] swalPopup existe?:', !!swalPopup);
                    
                    if (swalContainer) {
                        swalContainer.style.display = 'flex';
                        swalContainer.style.alignItems = 'center';
                        swalContainer.style.justifyContent = 'center';
                        swalContainer.style.position = 'fixed';
                        swalContainer.style.top = '0';
                        swalContainer.style.left = '0';
                        swalContainer.style.width = '100%';
                        swalContainer.style.height = '100%';
                        
                        console.log(' [MODAL-NOVEDAD] Estilos aplicados a container:');
                        console.log('   - display:', window.getComputedStyle(swalContainer).display);
                        console.log('   - position:', window.getComputedStyle(swalContainer).position);
                        console.log('   - alignItems:', window.getComputedStyle(swalContainer).alignItems);
                    }
                    if (swalPopup) {
                        swalPopup.style.position = 'relative';
                        console.log(' [MODAL-NOVEDAD] Position del popup:', window.getComputedStyle(swalPopup).position);
                        console.log(' [MODAL-NOVEDAD] Size del popup:', swalPopup.offsetWidth + 'x' + swalPopup.offsetHeight);
                    }
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const novedad = document.getElementById('modalNovedadEdicion').value.trim();
                    if (!novedad) {
                        Swal.fire({
                            title: ' Campo requerido',
                            html: '<p>Por favor escribe una novedad</p>',
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        }).then(() => {
                            resolve(this.mostrarModalYActualizar(pedidoId, prendaData, prendaIndex));
                        });
                        return;
                    }
                    // NUEVO: Aplicar cambios del buffer de procesos ANTES de guardar
                    if (typeof window.aplicarCambiosProcesosDesdeBuffer === 'function') {
                        window.aplicarCambiosProcesosDesdeBuffer();
                        console.log('[modal-novedad-edicion]  Buffer de procesos aplicado');
                    }
                    // NUEVO: Construir novedad con metadata del usuario
                    const novedadConMetadata = this.construirNovedadConMetadata(novedad);
                    await this.actualizarPrendaConNovedad(novedadConMetadata);
                    resolve();
                } else {
                    resolve();
                }
            });
        });
    }

    async actualizarPrendaConNovedad(novedad) {
        this.mostrarCargando();

        try {
            const formData = new FormData();
            formData.append('nombre_prenda', this.prendaData.nombre_prenda);
            formData.append('descripcion', this.prendaData.descripcion);
            
            //  FIX: Leer origen del SELECT actualizado en el modal (NO de this.prendaData que es estático)
            const origenSelect = document.getElementById('nueva-prenda-origen-select');
            const origenActual = origenSelect?.value || this.prendaData.origen || 'bodega';
            const deBodegaValue = origenActual === 'bodega' ? 1 : 0;
            
            formData.append('origen', origenActual);
            formData.append('de_bodega', deBodegaValue);
            
            console.log('[modal-novedad-edicion]  Origen guardado:', {
                origenActual: origenActual,
                de_bodega: deBodegaValue,
                deBodegaType: typeof deBodegaValue,
                selectValue: origenSelect?.value,
                prendaDataOrigen: this.prendaData.origen,
                prendaDataDeBodega: this.prendaData.de_bodega,
                tipoDeSelect: typeof origenSelect?.value
            });
            
            // IMPORTANTE: Leer tallas ACTUALIZADAS del modal (window.tallasRelacionales)
            // NO del this.prendaData inicial que puede estar desactualizado
            let tallasParaEnviar = window.tallasRelacionales || this.prendaData.tallas || {};
            
            if (tallasParaEnviar && Object.keys(tallasParaEnviar).length > 0) {
                const tallasArray = [];
                for (const [genero, tallas] of Object.entries(tallasParaEnviar)) {
                    if (typeof tallas === 'object' && tallas !== null) {
                        // CASO ESPECIAL: SOBREMEDIDA
                        if (genero.toUpperCase() === 'SOBREMEDIDA') {
                            // SOBREMEDIDA: {DAMA: 14, CABALLERO: 20}
                            // Convertir a: [{genero: DAMA, talla: null, cantidad: 14, es_sobremedida: true}, ...]
                            for (const [subGenero, cantidad] of Object.entries(tallas)) {
                                if (cantidad > 0) {
                                    tallasArray.push({
                                        genero: subGenero.toUpperCase(),
                                        talla: null,
                                        cantidad: parseInt(cantidad),
                                        es_sobremedida: true
                                    });
                                }
                            }
                        } else {
                            // GÉNEROS NORMALES: {DAMA: {S: 10, M: 20}}
                            for (const [talla, cantidad] of Object.entries(tallas)) {
                                if (cantidad > 0) {
                                    tallasArray.push({
                                        genero: genero.toUpperCase(),
                                        talla: talla,
                                        cantidad: parseInt(cantidad)
                                    });
                                }
                            }
                        }
                    }
                }
                if (tallasArray.length > 0) {
                    formData.append('tallas', JSON.stringify(tallasArray));
                    console.log('[modal-novedad-edicion]  Tallas ACTUALIZADAS enviadas:', tallasArray);
                }
            }
            
            // Agregar variantes SOLO SI fueron modificadas
            // Leer variantes ACTUALES del formulario
            const variantesActuales = await this.obtenerVariantesDelFormulario();
            
            // Comparar con las originales
            const variantesModificadas = this.compararVariantes(
                this.variantesOriginalesAlAbrirModal, 
                variantesActuales
            );
            
            if (variantesModificadas) {
                // Solo enviar si realmente fueron modificadas
                const variantesArray = this.convertirVariantesAlFormatoBackend(variantesActuales);
                formData.append('variantes', JSON.stringify(variantesArray));
                console.log('[modal-novedad-edicion] ✏️ Variantes MODIFICADAS enviadas:', variantesArray);
            } else {
                console.log('[modal-novedad-edicion]  Variantes NO modificadas - no se envían');
            }
            
            // ========== NUEVO: LEER FILAS DE TELAS NUEVAS DEL MODAL ==========
            // Capturar las filas que el usuario agregó manualmente con agregarFilaTela()
            const filasTelasDOMNuevas = document.querySelectorAll('.fila-tela');
            if (filasTelasDOMNuevas && filasTelasDOMNuevas.length > 0) {
                console.log('[modal-novedad-edicion]  Leyendo', filasTelasDOMNuevas.length, 'filas de telas del DOM');
                filasTelasDOMNuevas.forEach((fila, idx) => {
                    const nombreInput = fila.querySelector('.tela-name');
                    const colorInput = fila.querySelector('.tela-color');
                    const refInput = fila.querySelector('.tela-ref');
                    const imagenInput = fila.querySelector('.tela-imagen');
                    
                    const nombreTela = nombreInput?.value?.trim() || '';
                    const colorTela = colorInput?.value?.trim() || '';
                    const refTela = refInput?.value?.trim() || '';
                    const imagenFile = imagenInput?.files?.[0];
                    
                    if (nombreTela || colorTela || refTela || imagenFile) {
                        // Esta es una tela nueva (sin id de BD)
                        const telaNueva = {
                            // Sin id, ya que es nueva
                            tela: nombreTela,
                            color: colorTela,
                            referencia: refTela,
                            imagenes: imagenFile ? [imagenFile] : [],
                            tela_nombre: nombreTela,
                            color_nombre: colorTela,
                            esNueva: true  // Marcar como nueva para el backend
                        };
                        
                        console.log('[modal-novedad-edicion]  Tela nueva detectada:', telaNueva);
                        
                        // Agregar a window.telasAgregadas si aún no está
                        if (!window.telasAgregadas) {
                            window.telasAgregadas = [];
                        }
                        
                        // Verificar si ya existe (por si acaso)
                        const yaExiste = window.telasAgregadas.some(t => 
                            t.tela === nombreTela && t.color === colorTela && t.referencia === refTela
                        );
                        
                        if (!yaExiste) {
                            window.telasAgregadas.push(telaNueva);
                            console.log('[modal-novedad-edicion]  Tela nueva agregada a window.telasAgregadas');
                        }
                    }
                });
            }
            
            // NUEVO: Enviar telas (MERGE pattern - conservar telas existentes + agregar nuevas)
            // FLUJO EDICIÓN: usar window.telasAgregadas (nuevo) o window.telasEdicion (legacy)
            const telasParaEnviar = (window.telasAgregadas && window.telasAgregadas.length > 0) 
                ? window.telasAgregadas 
                : window.telasEdicion;
            
            //  FIX: MERGE PATTERN para telas
            // IMPORTANTE: Solo enviar colores_telas si el usuario REALMENTE modificó las telas
            // - Si hay telas en storage (usuario editó) → enviar lo que haya (preserve o delete)
            // - Si storage está vacío (usuario NO tocó telas) → NO enviar nada (deja NULL en DTO = no modifica)
            if (telasParaEnviar && telasParaEnviar.length > 0) {
                const telasArray = telasParaEnviar.map((tela, idx) => {
                    const obj = {
                        // Datos visibles
                        color: tela.color || '',
                        tela: tela.tela || '',
                        referencia: tela.referencia || '',
                        
                        // Nombres para fallback si faltan IDs
                        color_nombre: tela.color_nombre || tela.color || '',
                        tela_nombre: tela.tela_nombre || tela.tela || ''
                    };
                    
                    //  AGREGAR IDs PARA MERGE PATTERN
                    // Si tiene ID de relación, es tela existente → UPDATE
                    if (tela.id) {
                        obj.id = tela.id;  // ID de relación (prenda_pedido_colores_telas.id)
                    }
                    
                    // Agregar IDs del color y tela (para búsqueda en backend)
                    if (tela.color_id) {
                        obj.color_id = tela.color_id;
                    }
                    if (tela.tela_id) {
                        obj.tela_id = tela.tela_id;
                    }
                    
                    // Procesar imágenes
                    if (tela.imagenes && tela.imagenes.length > 0) {
                        obj.imagenes = [];
                        tela.imagenes.forEach((img, imgIdx) => {
                            //  FIX: Detectar File en múltiples formas
                            const fileObject = img instanceof File ? img : (img.file instanceof File ? img.file : null);
                            
                            if (fileObject) {
                                //  IMAGEN NUEVA: Subir File object real
                                formData.append(`telas[${idx}][imagenes][${imgIdx}]`, fileObject);
                                console.log('[modal-novedad-edicion]  📤 Imagen nueva de tela agregada a FormData:', {
                                    telaIdx: idx,
                                    imgIdx: imgIdx,
                                    fileName: fileObject.name,
                                    fileSize: fileObject.size
                                });
                            } else if (img.urlDesdeDB && img.url) {
                                //  IMAGEN DE BD: Solo guardar referencia (no es blob URL)
                                obj.imagenes.push({
                                    url: img.url,
                                    nombre: img.nombre || '',
                                    id: img.id  // Preservar ID si existe
                                });
                                console.log('[modal-novedad-edicion]  📌 Imagen de BD preservada:', {
                                    telaIdx: idx,
                                    imgIdx: imgIdx,
                                    id: img.id,
                                    url: img.url
                                });
                            }
                            // Las imágenes nuevas SIN File object (podrían ser blob URLs de preview) se ignoran
                            // porque ya fueron enviadas como File objects arriba
                        });
                    }
                    
                    return obj;
                });
                formData.append('colores_telas', JSON.stringify(telasArray));
                console.log('[modal-novedad-edicion]  Telas enviadas (MERGE):', telasArray);
                
                //  ENVIAR FOTOS DE TELAS CON ESTRUCTURA CORRECTA (MERGE PATTERN)
                const fotosTelaArray = [];
                let fotoTelaFileIndex = 0;
                
                telasParaEnviar.forEach((tela, telaIdx) => {
                    if (tela.imagenes && tela.imagenes.length > 0) {
                        tela.imagenes.forEach((img, imgIdx) => {
                            //  FIX: Detectar si es File directo O si es objeto con propiedad 'file'
                            const fileObject = img instanceof File ? img : (img.file instanceof File ? img.file : null);
                            
                            if (fileObject) {
                                //  NUEVA IMAGEN: Subir imagen a FormData
                                formData.append(`fotos_tela[${fotoTelaFileIndex}]`, fileObject);
                                
                                // Registrar metadatos en array para backend
                                fotosTelaArray.push({
                                    color_id: tela.color_id || null,
                                    tela_id: tela.tela_id || null,
                                    orden: imgIdx + 1
                                    //  NO enviar 'id' para fotos nuevas - backend las creará
                                });
                                fotoTelaFileIndex++;
                                
                                console.log('[modal-novedad-edicion]  📤 Foto de tela nueva agregada (MERGE):', {
                                    telaIdx: telaIdx,
                                    imgIdx: imgIdx,
                                    fotoTelaFileIndex: fotoTelaFileIndex - 1,
                                    fileName: fileObject.name,
                                    fileSize: fileObject.size,
                                    color_id: tela.color_id,
                                    tela_id: tela.tela_id
                                });
                            } else if (img.id && (img.urlDesdeDB || (img.url && !img.url.startsWith('blob:')))) {
                                //  FOTO EXISTENTE: Preservar referencia
                                fotosTelaArray.push({
                                    id: img.id,
                                    prenda_pedido_colores_telas_id: tela.id,  // FK para encontrar la relación
                                    color_id: tela.color_id || null,
                                    tela_id: tela.tela_id || null,
                                    ruta_original: img.url || img.urlDesdeDB,
                                    orden: imgIdx + 1
                                });
                            } else {
                                console.warn('[modal-novedad-edicion]   Imagen de tela ignorada (sin file ni datos válidos):', {
                                    telaIdx: telaIdx,
                                    imgIdx: imgIdx,
                                    tieneFile: !!img.file,
                                    tieneId: !!img.id,
                                    tieneUrl: !!img.url,
                                    esBlob: img.url?.startsWith('blob:'),
                                    urlDesdeDB: img.urlDesdeDB
                                });
                            }
                        });
                    }
                });
                
                if (fotosTelaArray.length > 0) {
                    formData.append('fotosTelas', JSON.stringify(fotosTelaArray));
                    console.log('[modal-novedad-edicion]  Fotos de telas enviadas (MERGE):', fotosTelaArray);
                }
            } else {
                console.log('[modal-novedad-edicion]  Usuario NO modificó telas - no enviar colores_telas para preservar datos existentes');
            }
            // IMPORTANTE: Solo enviar procesos si el usuario REALMENTE modificó los procesos
            // - Si hay procesos en window.procesosSeleccionados (usuario editó) → enviar lo que haya
            // - Si solo están en prendaData inicial (usuario NO tocó) → NO enviar (deja NULL en DTO = no modifica)
            const procesosParaEnviar = window.procesosSeleccionados || {};
            const procesosArray = this._transformarProcesosAArray(procesosParaEnviar);
            
            if (procesosArray && procesosArray.length > 0) {
                formData.append('procesos', JSON.stringify(procesosArray));
                console.log('[modal-novedad-edicion]  Procesos enviados (MERGE):', procesosArray);
                
                //  FIX CRÍTICO: Enviar imágenes de procesos nuevos
                // Las imágenes se capturan en window.imagenesProcesoActual cuando el usuario las agrega
                if (window.imagenesProcesoActual && Array.isArray(window.imagenesProcesoActual) && window.imagenesProcesoActual.length > 0) {
                    console.log('[modal-novedad-edicion] 📸 Imágenes de proceso nuevo detectadas:', {
                        cantidad: window.imagenesProcesoActual.length,
                        tipos: window.imagenesProcesoActual.map(img => img instanceof File ? 'File' : typeof img)
                    });
                    
                    // Agregar cada imagen de proceso al FormData
                    window.imagenesProcesoActual.forEach((img, idx) => {
                        if (img instanceof File) {
                            // La imagen es un File object (nueva)
                            formData.append(`fotosProcesoNuevo_${idx}`, img);
                            console.log(`[modal-novedad-edicion] 📸 Imagen de proceso nuevo ${idx} agregada:`, {
                                nombre: img.name,
                                tamano: img.size,
                                tipo: img.type
                            });
                        }
                    });
                    
                    // Agregar información sobre las imágenes de proceso para que el backend sepa dónde asociarlas
                    formData.append('fotosProcesoNuevoCount', window.imagenesProcesoActual.filter(img => img instanceof File).length.toString());
                    console.log('[modal-novedad-edicion] 📸 Total imágenes de proceso nuevo a guardar:', window.imagenesProcesoActual.filter(img => img instanceof File).length);
                } else {
                    console.log('[modal-novedad-edicion]  No hay imágenes de proceso nuevo para enviar');
                }
            } else {
                console.log('[modal-novedad-edicion]  Usuario NO modificó procesos - no enviar procesos para preservar datos existentes');
            }
            
            formData.append('novedad', novedad);
            
            // Obtener prenda_id - puede venir en diferentes propiedades
            const prendaId = this.prendaData.prenda_pedido_id || this.prendaData.id;




            
            if (!prendaId || isNaN(prendaId)) {
                throw new Error('ID de prenda inválido o no disponible. Recibido: ' + prendaId);
            }
            
            const prendaIdInt = parseInt(prendaId);

            formData.append('prenda_id', prendaIdInt);
            
            //  FIX CRÍTICO: Obtener imágenes del storage (donde se guardan las nuevas)
            // NO de this.prendaData.imagenes (que es estático y no refleja cambios de la galería)
            let imagenesActuales = [];
            if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.obtenerImagenes === 'function') {
                imagenesActuales = window.imagenesPrendaStorage.obtenerImagenes() || [];
                console.log('[modal-novedad-edicion]  Imágenes desde STORAGE (incluye nuevas):', {
                    cantidad: imagenesActuales.length,
                    datos: imagenesActuales
                });
            } else {
                // Fallback a this.prendaData.imagenes si el storage no existe
                imagenesActuales = this.prendaData.imagenes || [];
                console.log('[modal-novedad-edicion]  Storage NO disponible, usando prendaData.imagenes:', {
                    cantidad: imagenesActuales.length
                });
            }
            
            // Trackear imágenes eliminadas para enviar al servidor
            let imagenesEliminadas = [];
            
            // IMPORTANTE: Usar this.imagenesOriginalesAlAbrirModal que guardamos al abrir el modal
            // Esto tiene las imágenes correctas ANTES de que el usuario las eliminara
            const imagenesOriginales = this.imagenesOriginalesAlAbrirModal || this.prendaData.imagenes || [];
            
            console.log('[modal-novedad-edicion] 📸 COMPARACIÓN DE IMÁGENES:', {
                originales: imagenesOriginales.length,
                actuales: imagenesActuales.length
            });
            
            // Si existen imágenes en el storage (editadas por el usuario), usar esas
            if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.obtenerImagenes === 'function') {
                const imagenesDelStorage = window.imagenesPrendaStorage.obtenerImagenes();
                if (imagenesDelStorage && imagenesDelStorage.length > 0) {
                    console.log('[modal-novedad-edicion]  Usando imágenes del storage (incluye eliminaciones):', imagenesDelStorage.length);
                    imagenesActuales = imagenesDelStorage;
                    
                    // DEBUG: Log estructura completa de imágenes originales (snapshot)
                    console.log('[modal-novedad-edicion] 📸 ESTRUCTURA DE IMÁGENES ORIGINALES:');
                    console.log('[modal-novedad-edicion]  ANÁLISIS DE IMÁGENES:');
                    imagenesOriginales.forEach((img, idx) => {
                        const tieneContenido = Object.keys(img).length > 0;
                        const campos = Object.keys(img);
                        console.log(`  Imagen ${idx}: {tieneContenido: ${tieneContenido}, campos: ${JSON.stringify(campos)}}`, JSON.stringify(img, null, 2));
                        if (!tieneContenido) {
                            console.warn(`   IMAGEN ${idx} ESTÁ VACÍA - Posible imagen borrada o sin datos`);
                        }
                    });
                    
                    //  Detectar imágenes eliminadas comparando originales vs actuales
                    if (imagenesOriginales.length > imagenesDelStorage.length) {
                        imagenesOriginales.forEach(imgOriginal => {
                            const existeEnActuales = imagenesDelStorage.some(imgActual => {
                                // Comparar por URL o ID
                                const urlOriginal = imgOriginal.url || imgOriginal.ruta_webp || imgOriginal.ruta_original;
                                const urlActual = imgActual.previewUrl || imgActual.url || imgActual.ruta_webp;
                                return urlOriginal === urlActual;
                            });
                            
                            if (!existeEnActuales && (imgOriginal.id || imgOriginal.url || imgOriginal.ruta_webp)) {
                                imagenesEliminadas.push({
                                    id: imgOriginal.id,
                                    prenda_foto_id: imgOriginal.id, // Alias para el backend
                                    ruta_original: imgOriginal.ruta_original || imgOriginal.url || imgOriginal.ruta_webp,
                                    ruta_webp: imgOriginal.ruta_webp,
                                    url: imgOriginal.url || imgOriginal.ruta_webp || imgOriginal.ruta_original
                                });
                                console.log('[modal-novedad-edicion]  Imagen eliminada detectada:', {
                                    id: imgOriginal.id,
                                    ruta_original: imgOriginal.ruta_original,
                                    ruta_webp: imgOriginal.ruta_webp
                                });
                            }
                        });
                    }
                } else if (imagenesDelStorage && imagenesDelStorage.length === 0) {
                    // El usuario eliminó todas las imágenes
                    console.log('[modal-novedad-edicion]  El usuario eliminó todas las imágenes');
                    imagenesActuales = [];
                    
                    // Todas las originales fueron eliminadas
                    imagenesOriginales.forEach(img => {
                        if (img.id || img.url || img.ruta_webp) {
                            imagenesEliminadas.push({
                                id: img.id,
                                prenda_foto_id: img.id, // Alias para el backend
                                ruta_original: img.ruta_original || img.url || img.ruta_webp,
                                ruta_webp: img.ruta_webp,
                                url: img.url || img.ruta_webp || img.ruta_original
                            });
                        }
                    });
                }
            }
            
            // Separar imágenes nuevas (File objects) de imágenes existentes (DB)
            const imagenesNuevas = [];
            const imagenesDB = [];
            
            if (imagenesActuales && imagenesActuales.length > 0) {
                imagenesActuales.forEach((img, idx) => {
                    //  CRITICAL FIX: ImageStorageService guarda { file, previewUrl, nombre, tamano }
                    // El File REAL está en img.file, no en img directamente
                    let archivoReal = null;
                    
                    // Caso 1: Direct File object
                    if (img instanceof File) {
                        archivoReal = img;
                    }
                    // Caso 2: Wrapper de ImageStorageService con img.file
                    else if (img && img.file && img.file instanceof File) {
                        archivoReal = img.file;
                    }
                    // Caso 3: File properties pero no instanceof (después de serializar)
                    else if (img && img.file && typeof img.file === 'object' && 
                             (img.file.name !== undefined || img.file.size !== undefined || img.file.type !== undefined)) {
                        archivoReal = img.file;
                    }
                    // Caso 4: Properties directas en el wrapper (si no hay img.file)
                    else if (img && typeof img === 'object' && 
                             (img.name !== undefined || img.size !== undefined || img.type !== undefined) &&
                             !img.previewUrl) {  // Asegurar que no es un objeto mixto
                        archivoReal = img;
                    }
                    
                    if (archivoReal) {
                        // Es un File nuevo
                        imagenesNuevas.push(archivoReal);
                        formData.append(`imagenes[${imagenesNuevas.length - 1}]`, archivoReal);
                        console.log('[modal-novedad-edicion]  Imagen nueva detectada:', {
                            esFileDirecto: img instanceof File,
                            tieneFileProperty: !!(img?.file),
                            propiedades: {
                                name: archivoReal.name, 
                                size: archivoReal.size, 
                                type: archivoReal.type
                            },
                            idx: idx
                        });
                    } else if (img && img.urlDesdeDB) {
                        // Imagen existente de la BD - guardar URL para preservarla
                        imagenesDB.push({
                            previewUrl: img.previewUrl,
                            nombre: img.nombre
                        });
                    } else if (img && (img.url || img.ruta_webp || img.ruta_original)) {
                        // Imagen URL (desde BD o precargada) - preservarla
                        const urlImagen = img.url || img.ruta_webp || img.ruta_original;
                        imagenesDB.push({
                            previewUrl: urlImagen,
                            nombre: img.nombre || ''
                        });
                    } else {
                        //  DEBUG: Imagen sin categorizar
                        console.warn('[modal-novedad-edicion]  Imagen NO CATEGORIZADA:', {
                            tipoDeObjeto: img?.constructor?.name,
                            esFile: img instanceof File,
                            tieneFile: !!(img?.file),
                            claves: Object.keys(img || {}),
                            contenido: img
                        });
                    }
                });
            }
            
            console.log('[modal-novedad-edicion]  RESUMEN DETALLADO DE IMÁGENES A GUARDAR:', {
                imagenesNuevas: {
                    cantidad: imagenesNuevas.length,
                    tipos: imagenesNuevas.map(img => `${img.name} (${Math.round(img.size/1024)}KB)`)
                },
                imagenesExistentes: {
                    cantidad: imagenesDB.length,
                    urls: imagenesDB.map(img => img.previewUrl)
                },
                total: imagenesActuales.length,
                imagenesActuales_tipos: imagenesActuales.map((img, idx) => ({
                    idx,
                    tipo: img instanceof File ? 'FILE' : (img?.urlDesdeDB ? 'URL-DB' : (img?.url ? 'URL-OTRA' : 'DESCONOCIDO')),
                    esFile: img instanceof File
                }))
            });
            
            //  FIX: MERGE PATTERN para imágenes
            // IMPORTANTE: Solo enviar imagenes_existentes si el usuario REALMENTE modificó las imágenes
            // - Si hay imágenes en storage (usuario editó la galería) → enviar lo que haya (preserve o delete)
            // - Si storage está vacío (usuario NO tocó imágenes) → NO enviar nada (deja NULL en DTO = no modifica)
            
            // Detectar si el usuario tocó las imágenes
            const usuarioEditoImagenes = window.imagenesPrendaStorage && 
                                         typeof window.imagenesPrendaStorage.obtenerImagenes === 'function' &&
                                         window.imagenesPrendaStorage.obtenerImagenes() !== null;
            
            if (usuarioEditoImagenes) {
                // Usuario SÍ modificó imágenes → enviar el estado actual (puede ser vacío si eliminó todas)
                if (imagenesDB.length > 0) {
                    formData.append('imagenes_existentes', JSON.stringify(imagenesDB));
                    console.log('[modal-novedad-edicion]  Preservando imágenes existentes:', imagenesDB.length);
                } else {
                    // Usuario eliminó todas las imágenes explícitamente
                    formData.append('imagenes_existentes', JSON.stringify([]));
                    console.log('[modal-novedad-edicion]  Usuario eliminó todas las imágenes');
                }
                
                //  IMPORTANTE: Enviar IDs de imágenes a eliminar
                if (imagenesEliminadas.length > 0) {
                    formData.append('imagenes_a_eliminar', JSON.stringify(imagenesEliminadas));
                    console.log('[modal-novedad-edicion]  Enviando imágenes a eliminar:', imagenesEliminadas.length, imagenesEliminadas);
                }
            } else {
                // Usuario NO tocó imágenes → NO enviar imagenes_existentes (deja como NULL en DTO)
                // Esto hace que el backend NO modifique las imágenes existentes (MERGE preserva)
                console.log('[modal-novedad-edicion]  Usuario NO modificó imágenes - no enviar imagenes_existentes para preservar datos existentes');
            }
            

            // ==================== NUEVO: APLICAR CAMBIOS DE PROCESOS EDITADOS ====================
            // ANTES de guardar la prenda, aplicamos los PATCH de procesos editados
            const procesosEditados = window.gestorEditacionProcesos?.obtenerProcesosEditados();
            if (procesosEditados && procesosEditados.length > 0) {
                console.log('[modal-novedad-edicion]  Aplicando cambios de procesos editados ANTES de guardar prenda:', procesosEditados);
                
                const prendaIdInt = parseInt(this.prendaData.prenda_pedido_id || this.prendaData.id);
                
                // Ejecutar PATCH de cada proceso de forma secuencial
                for (const procesoEditado of procesosEditados) {
                    try {
                        const prendaIdInt = parseInt(this.prendaData.prenda_pedido_id || this.prendaData.id);
                        
                        //  VALIDACIÓN CRÍTICA: El proceso debe tener un ID válido (debe estar guardado en BD)
                        if (!procesoEditado.id || isNaN(procesoEditado.id)) {
                            console.warn('[modal-novedad-edicion]  SKIPPING: Proceso sin ID válido. No se puede actualizar un proceso que aún no está guardado en BD.', {
                                tipo: procesoEditado.tipo,
                                id: procesoEditado.id,
                                razon: 'Los procesos nuevos NO SE DEBEN PARCHEAR durante la edición de prenda. Deben guardarse como parte de la prenda completa.'
                            });
                            continue;
                        }
                        
                        //  Determinar si hay cambios (incluyendo imágenes)
                        const tieneImagenesNuevas = window.imagenesProcesoActual?.some(img => img instanceof File);
                        const tieneImagenesExistentes = window.imagenesProcesoExistentes?.length > 0;
                        const tieneCambiosOtros = Object.keys(procesoEditado.cambios || {}).length > 0;
                        
                        //  FIX: Incluir ubicaciones y observaciones actuales en la verificación
                        const tieneUbicacionesActuales = window.ubicacionesProcesoSeleccionadas?.length > 0;
                        const obsTextarea = document.getElementById('proceso-observaciones');
                        const tieneObservacionesActuales = obsTextarea?.value?.trim?.() ? true : false;
                        
                        const hayAlgunCambio = tieneCambiosOtros || tieneImagenesNuevas || tieneImagenesExistentes || 
                                               tieneUbicacionesActuales || tieneObservacionesActuales;
                        
                        console.log('[modal-novedad-edicion] 📤 Enviando PATCH para proceso:', {
                            prendaId: prendaIdInt,
                            procesoId: procesoEditado.id,
                            cambios: procesoEditado.cambios,
                            tieneImagenesNuevas,
                            tieneImagenesExistentes,
                            tieneUbicacionesActuales,
                            tieneObservacionesActuales,
                            tieneCambiosOtros,
                            hayAlgunCambio,
                            ubicacionesSeleccionadas: window.ubicacionesProcesoSeleccionadas?.length || 0,
                            observacionesValor: obsTextarea?.value?.substring?.(0, 50) || 'vacío'
                        });
                        
                        // Si no hay cambios de ningún tipo, saltar este proceso
                        if (!hayAlgunCambio) {
                            console.log('[modal-novedad-edicion]  Sin cambios para este proceso, saltando PATCH');
                            continue;
                        }
                        
                        
                        //  CAMBIO: Usar FormData en lugar de JSON para permitir subir archivos
                        const patchFormData = new FormData();
                        
                        //  FIX CRITICAL: Agregar _method=PATCH para que Laravel parsee FormData correctamente
                        // Cuando se envía FormData con PATCH, Laravel/PHP no lo parsea. 
                        // Solución: enviar como POST con _method=PATCH en el FormData
                        patchFormData.append('_method', 'PATCH');
                        
                        //  FIX: Incluir datos ACTUALES del proceso, no solo "cambios"
                        // Esto asegura que las ubicaciones y observaciones se envíen siempre
                        
                        // Ubicaciones: usar las del cambio si existen, sino usar las actuales de window
                        let ubicacionesAEnviar = procesoEditado.cambios.ubicaciones || 
                                                 window.ubicacionesProcesoSeleccionadas || 
                                                 [];
                        
                        //  IMPORTANTE: Limpiar ubicaciones de comillas escapadas
                        // Si es un string, parsearlo
                        if (typeof ubicacionesAEnviar === 'string') {
                            try {
                                ubicacionesAEnviar = JSON.parse(ubicacionesAEnviar);
                            } catch (e) {
                                ubicacionesAEnviar = [ubicacionesAEnviar];
                            }
                        }
                        // Asegurar que es array
                        if (!Array.isArray(ubicacionesAEnviar)) {
                            ubicacionesAEnviar = [];
                        }
                        
                        // Limpiar cada ubicación de comillas escapadas
                        ubicacionesAEnviar = ubicacionesAEnviar.map(u => {
                            if (typeof u === 'string') {
                                // Remover comillas escapadas: "\"valor\"" → "valor"
                                return u.replace(/^["\\]*|["\\]*$/g, '').trim();
                            }
                            return u;
                        }).filter(u => u && u.length > 0);
                        
                        if (ubicacionesAEnviar && ubicacionesAEnviar.length > 0) {
                            console.log('[modal-novedad-edicion]  Ubicaciones ANTES de stringify:', {
                                tipo: typeof ubicacionesAEnviar,
                                esArray: Array.isArray(ubicacionesAEnviar),
                                contenido: ubicacionesAEnviar,
                                limpias: true
                            });
                            // Enviar ubicaciones como array individual (NO stringify)
                            ubicacionesAEnviar.forEach((ub, idx) => {
                                patchFormData.append(`ubicaciones[${idx}]`, ub);
                            });
                            console.log('[modal-novedad-edicion]  Ubicaciones añadidas al PATCH (limpias):', ubicacionesAEnviar);
                        }
                        
                        // Observaciones: usar las del cambio si existen, sino intentar del DOM
                        const observacionesAEnviar = procesoEditado.cambios.observaciones || 
                                                     (obsTextarea?.value) || 
                                                     '';
                        if (observacionesAEnviar) {
                            patchFormData.append('observaciones', observacionesAEnviar);
                            console.log('[modal-novedad-edicion] Observaciones añadidas al PATCH:', observacionesAEnviar);
                        }
                        
                        //  Tallas: SIEMPRE enviar tallas (cambios del editor OR actuales de window)
                        // El usuario modificó tallas en el modal - SIEMPRE enviarlas
                        let tallasAEnviar = procesoEditado.cambios.tallas || window.tallasCantidadesProceso || { dama: {}, caballero: {} };
                        
                        if (tallasAEnviar && (Object.keys(tallasAEnviar.dama || {}).length > 0 || Object.keys(tallasAEnviar.caballero || {}).length > 0)) {
                            console.log('[modal-novedad-edicion] 📏 Tallas enviadas al PATCH:', tallasAEnviar);
                            patchFormData.append('tallas', JSON.stringify(tallasAEnviar));
                        } else {
                            console.log('[modal-novedad-edicion]  Sin tallas para enviar');
                        }
                        
                        // Imágenes: usar las del cambio si existen
                        if (procesoEditado.cambios.imagenes) {
                            patchFormData.append('imagenes', JSON.stringify(procesoEditado.cambios.imagenes));
                        }
                        
                        //  Incluir imágenes existentes (URLs) si las hay
                        if (window.imagenesProcesoExistentes && Array.isArray(window.imagenesProcesoExistentes) && window.imagenesProcesoExistentes.length > 0) {
                            console.log(`[modal-novedad-edicion] 🖼️ Imágenes existentes encontradas:`, window.imagenesProcesoExistentes);
                            patchFormData.append('imagenes_existentes', JSON.stringify(window.imagenesProcesoExistentes));
                        }
                        
                        //  Incluir archivos nuevos de imágenes de proceso desde window.imagenesProcesoActual
                        if (window.imagenesProcesoActual && Array.isArray(window.imagenesProcesoActual)) {
                            const imagenesNuevasCount = window.imagenesProcesoActual.filter(img => img instanceof File).length;
                            console.log(`[modal-novedad-edicion] 📎 Imágenes nuevas a procesar:`, imagenesNuevasCount);
                            
                            window.imagenesProcesoActual.forEach((img, idx) => {
                                if (img instanceof File) {
                                    console.log(`[modal-novedad-edicion] 📎 Agregando archivo de proceso al FormData:`, {
                                        indice: idx,
                                        nombre: img.name,
                                        tamano: img.size
                                    });
                                    //  FIX: Usar nombre simple 'imagenes_nuevas' en lugar de índices con corchetes
                                    // FormData maneja mejor esto automáticamente
                                    patchFormData.append('imagenes_nuevas', img);
                                }
                            });
                        }
                        
                        // ========== FIX: USAR RUTA CORRECTA SEGÚN ROL DEL USUARIO ==========
                        const usuarioActual = this.obtenerUsuarioActual();
                        const rolUsuario = usuarioActual?.rol || 'asesor';
                        
                        // Si el usuario es supervisor_pedidos, usar ruta de supervisor-pedidos
                        // Si no, usar ruta de API general
                        const urlPatch = rolUsuario === 'supervisor_pedidos' 
                            ? `/supervisor-pedidos/${prendaIdInt}/procesos/${procesoEditado.id}`
                            : `/api/prendas-pedido/${prendaIdInt}/procesos/${procesoEditado.id}`;
                        
                        console.log('[modal-novedad-edicion]  PATCH usando ruta según rol:', {
                            rol: rolUsuario,
                            urlUsada: urlPatch,
                            prendaId: prendaIdInt,
                            procesoId: procesoEditado.id
                        });
                        
                        const patchResponse = await fetch(urlPatch, {
                            method: 'POST',  //  FIX: Usar POST en lugar de PATCH, Laravel lo procesará con _method=PATCH
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: patchFormData
                        });
                        
                        const patchResult = await patchResponse.json();
                        
                        if (!patchResponse.ok) {
                            // Log detallado de error
                            console.error('[modal-novedad-edicion] 🚨 Error 422 del servidor:', {
                                status: patchResponse.status,
                                message: patchResult.message,
                                errors: patchResult.errors,
                                patchResult: patchResult
                            });
                            
                            // Construir mensaje de error detallado
                            let errorMsg = `Error ${patchResponse.status}: ${patchResult.message || 'Desconocido'}`;
                            if (patchResult.errors) {
                                const errorDetails = Object.entries(patchResult.errors).map(([field, msgs]) => {
                                    return `${field}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`;
                                }).join('\n');
                                errorMsg += `\n\nDetalles:\n${errorDetails}`;
                            }
                            throw new Error(errorMsg);
                        }
                        
                        console.log('[modal-novedad-edicion]  PATCH aplicado exitosamente para proceso:', procesoEditado.id);
                        
                        //  CRITICAL FIX: Limpiar imágenes de proceso después de PATCH exitoso
                        // Para evitar que se vuelvan a enviar al guardar la prenda (duplicación)
                        if (window.imagenesProcesoActual && Array.isArray(window.imagenesProcesoActual)) {
                            const imagenesFileCount = window.imagenesProcesoActual.filter(img => img instanceof File).length;
                            if (imagenesFileCount > 0) {
                                console.log('[modal-novedad-edicion] 🧹 Limpiando imágenes de proceso después de PATCH:', {
                                    cantidad_limpiadas: imagenesFileCount
                                });
                                // Remover solo los archivos File, mantener las imágenes existentes de BD
                                window.imagenesProcesoActual = window.imagenesProcesoActual.filter(img => !(img instanceof File));
                                console.log('[modal-novedad-edicion]  Imágenes de proceso limpiadas');
                            }
                        }
                    } catch (error) {
                        console.error('[modal-novedad-edicion]  Error al aplicar PATCH:', error);
                        throw error; // Detener el proceso si algún PATCH falla
                    }
                }
                
                // Limpiar gestor de edición después de aplicar
                window.gestorEditacionProcesos?.limpiar();
                console.log('[modal-novedad-edicion] 🧹 Gestor de edición limpiado');
                
                //  CRITICAL FIX: Remover fotosProcesoNuevo_* del FormData después de PATCH exitoso
                // Ya fueron procesadas en el PATCH, no deben enviarse nuevamente en el POST final
                try {
                    // Obtener todas las keys del FormData
                    const keysParaEliminar = [];
                    for (let pair of formData.entries()) {
                        if (pair[0].startsWith('fotosProcesoNuevo_')) {
                            keysParaEliminar.push(pair[0]);
                        }
                    }
                    
                    // Remover cada clave encontrada
                    keysParaEliminar.forEach(key => {
                        formData.delete(key);
                    });
                    
                    // También remover el contador
                    formData.delete('fotosProcesoNuevoCount');
                    
                    if (keysParaEliminar.length > 0) {
                        console.log('[modal-novedad-edicion] 🧹 Campos de imágenes de proceso removidos del FormData:', {
                            campos_eliminados: keysParaEliminar.length,
                            contador_también_eliminado: true
                        });
                    }
                } catch (error) {
                    console.warn('[modal-novedad-edicion]  No se pudo remover fotosProcesoNuevo del FormData (puede que no exista):', error.message);
                }
            }

            // ==================== NUEVO: ELIMINAR PROCESOS MARCADOS ====================
            // Eliminar los procesos que el usuario marcó para eliminar
            if (typeof window.eliminarProcesossMarcadosDelBackend === 'function') {
                try {
                    console.log('[modal-novedad-edicion]  Eliminando procesos marcados...');
                    await window.eliminarProcesossMarcadosDelBackend();
                    console.log('[modal-novedad-edicion]  Procesos marcados eliminados');
                } catch (error) {
                    console.error('[modal-novedad-edicion]  Error eliminando procesos marcados:', error);
                    throw error;
                }
            }

            // Determinar la ruta correcta según el contexto
            let urlActualizar = `/asesores/pedidos/${this.pedidoId}/actualizar-prenda`;
            
            // Si estamos en supervisor-pedidos, usar ruta específica para supervisores
            if (window.location.pathname.includes('supervisor-pedidos')) {
                urlActualizar = `/supervisor-pedidos/${this.pedidoId}/actualizar-prenda`;
            }

            const response = await fetch(urlActualizar, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                body: formData
            });
            
            const resultado = await response.json();
            console.log('[modal-novedad-edicion] Response del servidor:', {
                ok: response.ok,
                status: response.status,
                success: resultado.success,
                message: resultado.message,
                resultado: resultado
            });
            
            if (!response.ok || !resultado.success) {
                throw new Error(resultado.message || 'Error desconocido al actualizar la prenda');
            }
            
            //  FIX CRÍTICO: Actualizar snapshot con datos del servidor después de guardar
            // Esto evita que imágenes eliminadas sigan siendo contadas en la próxima apertura
            if (resultado.prenda && resultado.prenda.fotos) {
                const fotosActualizadas = resultado.prenda.fotos
                    .filter(foto => foto && Object.keys(foto).length > 0 && (foto.previewUrl || foto.url || foto.ruta_webp || foto.ruta_original));
                
                console.log('[modal-novedad-edicion]  SINCRONIZANDO SNAPSHOT CON RESPUESTA DEL SERVIDOR:', {
                    fotosAntes: window.imagenesPrendaStorage?.snapshotOriginal?.length || 0,
                    fotosAhora: fotosActualizadas.length,
                    datos: fotosActualizadas
                });
                
                if (window.imagenesPrendaStorage) {
                    window.imagenesPrendaStorage.snapshotOriginal = JSON.parse(JSON.stringify(fotosActualizadas));
                }
            }
            


            
            // IMPORTANTE: Recargar datos completos del pedido para asegurar que telasAgregadas y datos relacionados se actualizan correctamente
            // NOTA: Se omite la recarga para supervisores de pedidos ya que no es necesaria en ese flujo
            // TEMPORALMENTE DESHABILITADO: La ruta de recarga está dando 404, pero el guardado funciona correctamente
            // const usuarioActual = this.obtenerUsuarioActual();
            // const esSupervisor = usuarioActual.rol === 'supervisor_pedidos';
            
            // if (window.prendaEnEdicion && !esSupervisor) {
            //     const pedidoId = window.prendaEnEdicion.pedidoId;

            //     
            //     try {
            //         const respDataEdicion = await fetch(`/asesores/pedidos-produccion/${pedidoId}/datos-edicion`);
                    
            //         // Verificar si la respuesta es exitosa (status 200-299)
            //         if (!respDataEdicion.ok) {
            //             console.warn('[modal-novedad-edicion] Recarga de datos fallida (status: ' + respDataEdicion.status + '), continuando sin actualización');
            //         } else {
            //             const resultadoDataEdicion = await respDataEdicion.json();
                        
            //             if (resultadoDataEdicion.success && resultadoDataEdicion.datos) {

            //                 window.datosEdicionPedido = resultadoDataEdicion.datos;
                            
            //                 // Actualizar en prendasEdicion también
            //                 if (window.prendasEdicion) {
            //                     window.prendasEdicion.prendas = resultadoDataEdicion.datos.prendas;
            //                     window.prendasEdicion.pedidoId = resultadoDataEdicion.datos.id || resultadoDataEdicion.datos.numero_pedido;
            //                 }
            //             }
            //         }
            //     } catch (e) {

            //         // Si falla la recarga automática, al menos actualizar la prenda con los datos que vinieron
            //         console.warn('[modal-novedad-edicion] Error al recargar datos:', e.message);
            //         if (resultado.prenda && window.datosEdicionPedido && window.prendaEnEdicion) {
            //             const prendasIndex = window.prendaEnEdicion.prendasIndex;
            //             if (prendasIndex !== null && prendasIndex !== undefined) {
            //                 window.datosEdicionPedido.prendas[prendasIndex] = resultado.prenda;
            //             }
            //         }
            //     }
            // }
            
            this.mostrarExito();
        } catch (error) {
            console.error('[modal-novedad-edicion] Error al actualizar prenda:', {
                message: error.message,
                stack: error.stack,
                error: error
            });
            this.mostrarError(error.message);
        }
    }

    mostrarCargando() {
        Swal.fire({
            title: ' Actualizando...',
            html: '<p>Por favor espera</p>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => this.forzarZIndexMaximo()
        });
    }

    mostrarExito() {
        Swal.fire({
            title: ' ¡Éxito!',
            html: '<p>Prenda actualizada correctamente</p>',
            icon: 'success',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#3b82f6',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => this.forzarZIndexMaximo()
        }).then((result) => {
            if (result.isConfirmed) {
                // NUEVO: Disparar evento para actualizar tabla en tiempo real
                const evento = new CustomEvent('prendaActualizada', {
                    detail: {
                        pedidoId: this.pedidoId,
                        prendaId: this.prendaData.prenda_pedido_id || this.prendaData.id,
                        timestamp: new Date()
                    }
                });
                window.dispatchEvent(evento);
                console.log('[modal-novedad-edicion] 📢 Evento disparado: prendaActualizada', evento.detail);
                
                // 🧹 CRÍTICO: Limpiar storages de imágenes después de guardar exitosamente
                // Esto solo aplica cuando se guarda en BD (pedido existente)
                // En modo CREACIÓN (memory-only), no limpiamos porque aún se necesitan los datos
                const enPedidoExistente = window.datosEdicionPedido && (window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido);
                
                if (enPedidoExistente) {
                    // SOLO en modo DB: limpiar todos los storages
                    if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.limpiar === 'function') {
                        window.imagenesPrendaStorage.limpiar();
                        console.log('🧹 [mostrarExito] Storage de imágenes de prenda limpiado (BD)');
                    }
                    if (window.imagenesTelaStorage && typeof window.imagenesTelaStorage.limpiar === 'function') {
                        window.imagenesTelaStorage.limpiar();
                        console.log('🧹 [mostrarExito] Storage de imágenes de tela limpiado (BD)');
                    }
                } else {
                    // En modo CREACIÓN: solo limpiar imágenes de prenda, NO las de tela
                    if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.limpiar === 'function') {
                        window.imagenesPrendaStorage.limpiar();
                        console.log('🧹 [mostrarExito] Storage de imágenes de prenda limpiado (CREACIÓN)');
                    }
                    // NO limpiar imagenesTelaStorage - se necesitan para guardar telas en prendaData
                    console.log('⚠️ [mostrarExito] imagenesTelaStorage NO limpiado (modo CREACIÓN - se preserva)');
                }
                
                // IMPORTANTE: Solo cerrar el modal de prenda, NO abrir otro modal
                // El usuario estaba editando dentro del modal de prenda y ya finalizó
                if (typeof window.cerrarModalPrendaNueva === 'function') {
                    window.cerrarModalPrendaNueva();
                }
            }
        });
    }

    mostrarError(mensaje) {
        Swal.fire({
            title: ' Error',
            html: `<p>${mensaje}</p>`,
            icon: 'error',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#ef4444',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => this.forzarZIndexMaximo()
        });
    }

    convertirTallasAlFormatoJson(tallas) {
        // Convierte array de tallas {genero, talla, cantidad} a JSON {GENERO: {talla: cantidad}}
        if (!Array.isArray(tallas)) return {};
        
        const resultado = {};
        tallas.forEach(tallaObj => {
            if (tallaObj.genero && tallaObj.talla && tallaObj.cantidad) {
                const genero = tallaObj.genero.toUpperCase();
                if (!resultado[genero]) {
                    resultado[genero] = {};
                }
                resultado[genero][tallaObj.talla] = tallaObj.cantidad;
            }
        });
        return resultado;
    }

    convertirVariantesAlFormatoBackend(variantes) {
        // Convierte variantes (objeto o array) al formato esperado por backend
        // Formato esperado: [ { tipo_manga_id, tipo_broche_boton_id, manga_obs, broche_boton_obs, tiene_bolsillos, bolsillos_obs } ]
        
        // Si ya es un array, validar que tenga los campos correctos
        if (Array.isArray(variantes)) {
            return variantes.map(v => ({
                tipo_manga_id: v.tipo_manga_id || null,
                tipo_broche_boton_id: v.tipo_broche_boton_id || null,
                manga_obs: v.manga_obs || v.obs_manga || v.manga || '',
                broche_boton_obs: v.broche_boton_obs || v.obs_broche || v.broche || '',
                tiene_bolsillos: v.tiene_bolsillos || false,
                bolsillos_obs: v.bolsillos_obs || v.obs_bolsillos || '',
                tiene_reflectivo: v.tiene_reflectivo || false,
                reflectivo_obs: v.reflectivo_obs || v.obs_reflectivo || ''
            }));
        }
        
        // Si es un objeto con propiedades de variantes, convertir a array
        if (variantes && typeof variantes === 'object') {
            // Crear un único objeto de variante con todas las propiedades
            const varianteObject = {
                tipo_manga_id: variantes.tipo_manga_id || null,
                tipo_broche_boton_id: variantes.tipo_broche_boton_id || null,
                manga_obs: variantes.obs_manga || variantes.manga || variantes.manga_obs || '',
                broche_boton_obs: variantes.obs_broche || variantes.broche || variantes.broche_boton_obs || '',
                tiene_bolsillos: variantes.tiene_bolsillos || false,
                bolsillos_obs: variantes.obs_bolsillos || variantes.bolsillos_obs || '',
                tiene_reflectivo: variantes.tiene_reflectivo || false,
                reflectivo_obs: variantes.obs_reflectivo || variantes.reflectivo_obs || ''
            };
            
            // Retornar como array con un único elemento
            return [varianteObject];
        }
        
        // Si está vacío, retornar array vacío
        return [];
    }

    /**
     * Obtener variantes actuales del formulario
     */
    async obtenerVariantesDelFormulario() {
        const variante = {};

        // Manga
        const mangaCheckbox = document.getElementById('aplica-manga');
        if (mangaCheckbox && mangaCheckbox.checked) {
            const mangaInput = document.getElementById('manga-input');
            const mangaObs = document.getElementById('manga-obs');
            
            // Procesar el input de manga (crea automáticamente si no existe)
            if (mangaInput && mangaInput.value && typeof window.procesarMangaInput === 'function') {
                await window.procesarMangaInput(mangaInput);
            }
            
            // Obtener el ID de la manga seleccionada
            let tipo_manga_id = null;
            if (mangaInput && mangaInput.value) {
                // Buscar el ID en el datalist basado en el nombre seleccionado
                const datalist = document.getElementById('opciones-manga');
                if (datalist) {
                    const option = Array.from(datalist.options).find(opt => opt.value === mangaInput.value);
                    if (option && option.dataset.id) {
                        tipo_manga_id = parseInt(option.dataset.id);
                    }
                }
            }
            
            variante.tipo_manga_id = tipo_manga_id;
            variante.manga_obs = mangaObs?.value || '';
        }

        // Broche/Botón
        const brocheCheckbox = document.getElementById('aplica-broche');
        if (brocheCheckbox && brocheCheckbox.checked) {
            const brocheInput = document.getElementById('broche-input');
            const brocheObs = document.getElementById('broche-obs');
            
            // Mapear valor del select a ID
            let tipo_broche_boton_id = null;
            if (brocheInput && brocheInput.value) {
                if (brocheInput.value === 'broche') {
                    tipo_broche_boton_id = 1;
                } else if (brocheInput.value === 'boton') {
                    tipo_broche_boton_id = 2;
                }
            }
            
            variante.tipo_broche_boton_id = tipo_broche_boton_id;
            variante.broche_boton_obs = brocheObs?.value || '';
        }

        // Bolsillos
        const bolsillosCheckbox = document.getElementById('aplica-bolsillos');
        if (bolsillosCheckbox && bolsillosCheckbox.checked) {
            const bolsillosObs = document.getElementById('bolsillos-obs');
            variante.tiene_bolsillos = true;
            variante.bolsillos_obs = bolsillosObs?.value || '';
        } else {
            variante.tiene_bolsillos = false;
            variante.bolsillos_obs = '';
        }

        // Reflectivo
        const reflectivoCheckbox = document.getElementById('checkbox-reflectivo');
        if (reflectivoCheckbox && reflectivoCheckbox.checked) {
            const reflectivoObs = document.getElementById('obs-reflectivo');
            variante.tiene_reflectivo = true;
            variante.reflectivo_obs = reflectivoObs?.value || '';
        } else {
            variante.tiene_reflectivo = false;
            variante.reflectivo_obs = '';
        }

        return variante;
    }

    /**
     * Transformar procesos de estructura de objeto a array
     * De: { 'reflectivo': { datos: {...} }, 'estampado': { datos: {...} } }
     * A:  [ { tipo_proceso_id: 1, ubicaciones: [...], ... }, { tipo_proceso_id: 2, ... } ]
     */
    _transformarProcesosAArray(procesosObj) {
        if (!procesosObj || typeof procesosObj !== 'object' || Array.isArray(procesosObj)) {
            return Array.isArray(procesosObj) ? procesosObj : [];
        }

        return Object.entries(procesosObj).map(([tipoProceso, procInfo]) => {
            const datosProc = procInfo?.datos || procInfo || {};
            
            //  FIX: Permitir procesos que tengan AMBOS:
            // - id (proceso existente en BD)
            // - tipo_proceso_id (tipo del proceso)
            // O procesos nuevos que tengan tipo_proceso_id asignado
            // NO rechazar procesos válidos solo porque falte un campo
            
            return {
                id: datosProc.id || undefined,
                tipo_proceso_id: datosProc.tipo_proceso_id || undefined,
                tipo: datosProc.tipo || tipoProceso,
                nombre: datosProc.nombre || tipoProceso,
                ubicaciones: datosProc.ubicaciones || [],
                observaciones: datosProc.observaciones || '',
                estado: datosProc.estado || 'PENDIENTE'
            };
        }).filter(proc => {
            //  ARREGLO: Filtro más permisivo
            // Aceptar procesos que tengan:
            // 1. tipo_proceso_id válido (proceso nuevo con tipo asignado)
            // 2. O id válido (proceso existente en BD)
            // 3. O tipo válido (proceso nuevo sin ID, agregado por usuario)
            const tieneId = proc.id && proc.id > 0;
            const tieneTipoProceso = proc.tipo_proceso_id && proc.tipo_proceso_id > 0;
            const tieneTipo = proc.tipo && proc.tipo.length > 0;
            
            //  FIX CRÍTICO: El proceso es válido si tiene CUALQUIERA de estos:
            // - ID de BD (existente)
            // - ID de tipo de proceso (nuevo pero con tipo asignado)
            // - Tipo formateado (nuevo agregado por usuario en edición)
            return tieneId || tieneTipoProceso || tieneTipo;
        });
    }

    /**
     * Comparar variantes originales con las actuales
     * Retorna true si fueron modificadas, false si son iguales
     * @private
     */
    compararVariantes(variantesOriginales = [], variantesActuales = {}) {
        // Si no hay originales, cualquier valor actual es una modificación
        if (!variantesOriginales || variantesOriginales.length === 0) {
            const tieneValores = Object.keys(variantesActuales).some(
                key => variantesActuales[key] !== null && variantesActuales[key] !== ''
            );
            return tieneValores;
        }
        
        // Convertir variantesActuales a formato de array para comparar
        const variantesActualesArray = this.convertirVariantesAlFormatoBackend(variantesActuales);
        
        // Comparación simple: mismo número de items?
        if (variantesActualesArray.length !== variantesOriginales.length) {
            return true; // Fueron modificadas
        }
        
        // Comparar cada propiedad
        for (let i = 0; i < variantesOriginales.length; i++) {
            const orig = variantesOriginales[i];
            const actual = variantesActualesArray[i];
            
            // Comparar propiedades clave
            if ((orig.tipo_manga_id || 0) !== (actual.tipo_manga_id || 0) ||
                (orig.tipo_broche_boton_id || 0) !== (actual.tipo_broche_boton_id || 0) ||
                (orig.manga_obs || '') !== (actual.manga_obs || '') ||
                (orig.broche_boton_obs || '') !== (actual.broche_boton_obs || '') ||
                (orig.tiene_bolsillos || false) !== (actual.tiene_bolsillos || false) ||
                (orig.bolsillos_obs || '') !== (actual.bolsillos_obs || '')) {
                return true; // Fueron modificadas
            }
        }
        
        // Sin cambios
        return false;
    }
}

// Instanciar modal cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.modalNovedadEditacion = new ModalNovedadEdicion();
    });
} else {
    window.modalNovedadEditacion = new ModalNovedadEdicion();
}

