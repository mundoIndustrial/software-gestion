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
        // Obtener usuario en este momento
        const usuarioActual = this.obtenerUsuarioActual();
        
        const ahora = new Date();
        // Formato DD-MM-YYYY
        const dia = String(ahora.getDate()).padStart(2, '0');
        const mes = String(ahora.getMonth() + 1).padStart(2, '0');
        const año = ahora.getFullYear();
        const fecha = `${dia}-${mes}-${año}`;
        
        // Formato HH:MM:SS
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        const hora = `${horas}:${minutos}:${segundos}`;
        
        // Obtener rol con nombre legible
        const rolTecnico = (usuarioActual.rol || 'Sin Rol');
        const rolLegible = this.obtenerNombreRolLegible(rolTecnico);
        
        // Formato: [rol-DD-MM-YYYY HH:MM:SS] descripción
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

        // � DEBUG: Verificar qué contiene prendaData al llegar
        console.log('[modal-novedad-edicion] 🔍 DEBUG prendaData recibido:', {
            prendaDataCompleto: prendaData,
            prenda_pedido_id: prendaData?.prenda_pedido_id,
            id: prendaData?.id,
            tipo: prendaData?.tipo,
            nombre_prenda: prendaData?.nombre_prenda
        });

        // � CRÍTICO: Inicializar window.imagenesPrendaStorage con las imágenes ACTUALES de la prenda
        // Esto asegura que cuando la galería se abre, tenga las imágenes correctas
        console.log('[modal-novedad-edicion] 🔍 DEBUG prendaData.imagenes:', {
            existe: !!prendaData.imagenes,
            esArray: Array.isArray(prendaData.imagenes),
            cantidad: prendaData.imagenes?.length || 0,
            datos: prendaData.imagenes
        });
        
        if (window.imagenesPrendaStorage && prendaData && prendaData.imagenes) {
            // Limpiar el storage antes de cargar nuevas imágenes
            window.imagenesPrendaStorage.limpiar();
            
            // Establecer las imágenes de la prenda actual
            window.imagenesPrendaStorage.establecerImagenes(prendaData.imagenes);
            console.log('[modal-novedad-edicion] ✅ [INIT-SYNC] window.imagenesPrendaStorage inicializado con', prendaData.imagenes.length, 'imágenes');
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
                    console.log('🔔 [MODAL-NOVEDAD] didOpen iniciado');
                    this.forzarZIndexMaximo();
                    const textarea = document.getElementById('modalNovedadEdicion');
                    if (textarea) textarea.focus();
                    
                    // 🔝 Asegurar que el modal esté centrado
                    const swalContainer = document.querySelector('.swal2-container');
                    const swalPopup = document.querySelector('.swal2-popup');
                    
                    console.log('🔔 [MODAL-NOVEDAD] swalContainer existe?:', !!swalContainer);
                    console.log('🔔 [MODAL-NOVEDAD] swalPopup existe?:', !!swalPopup);
                    
                    if (swalContainer) {
                        swalContainer.style.display = 'flex';
                        swalContainer.style.alignItems = 'center';
                        swalContainer.style.justifyContent = 'center';
                        swalContainer.style.position = 'fixed';
                        swalContainer.style.top = '0';
                        swalContainer.style.left = '0';
                        swalContainer.style.width = '100%';
                        swalContainer.style.height = '100%';
                        
                        console.log('🔔 [MODAL-NOVEDAD] Estilos aplicados a container:');
                        console.log('   - display:', window.getComputedStyle(swalContainer).display);
                        console.log('   - position:', window.getComputedStyle(swalContainer).position);
                        console.log('   - alignItems:', window.getComputedStyle(swalContainer).alignItems);
                    }
                    if (swalPopup) {
                        swalPopup.style.position = 'relative';
                        console.log('🔔 [MODAL-NOVEDAD] Position del popup:', window.getComputedStyle(swalPopup).position);
                        console.log('🔔 [MODAL-NOVEDAD] Size del popup:', swalPopup.offsetWidth + 'x' + swalPopup.offsetHeight);
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
                        console.log('[modal-novedad-edicion] ✅ Buffer de procesos aplicado');
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
            
            // 🔧 FIX: Leer origen del SELECT actualizado en el modal (NO de this.prendaData que es estático)
            const origenSelect = document.getElementById('nueva-prenda-origen-select');
            const origenActual = origenSelect?.value || this.prendaData.origen || 'bodega';
            const deBodegaValue = origenActual === 'bodega' ? 1 : 0;
            
            formData.append('origen', origenActual);
            formData.append('de_bodega', deBodegaValue);
            
            console.log('[modal-novedad-edicion] ✅ Origen guardado:', {
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
                        for (const [talla, cantidad] of Object.entries(tallas)) {
                            if (cantidad > 0) {
                                tallasArray.push({
                                    genero: genero.toLowerCase(),
                                    talla: talla,
                                    cantidad: parseInt(cantidad)
                                });
                            }
                        }
                    }
                }
                if (tallasArray.length > 0) {
                    formData.append('tallas', JSON.stringify(tallasArray));
                    console.log('[modal-novedad-edicion] ✅ Tallas ACTUALIZADAS enviadas:', tallasArray);
                }
            }
            
            // Agregar variantes si existen
            // Leer variantes ACTUALES del formulario, no de this.prendaData
            const variantesActuales = await this.obtenerVariantesDelFormulario();
            
            if (variantesActuales && Object.keys(variantesActuales).some(key => variantesActuales[key] !== null && variantesActuales[key] !== '')) {
                // Convertir variantes a formato esperado por backend: array de objetos
                const variantesArray = this.convertirVariantesAlFormatoBackend(variantesActuales);
                formData.append('variantes', JSON.stringify(variantesArray));
                console.log('[modal-novedad-edicion] Variantes enviadas:', variantesArray);
            } else {
                console.log('[modal-novedad-edicion]  No hay variantes para enviar');
            }
            
            // ========== NUEVO: LEER FILAS DE TELAS NUEVAS DEL MODAL ==========
            // Capturar las filas que el usuario agregó manualmente con agregarFilaTela()
            const filasTelasDOMNuevas = document.querySelectorAll('.fila-tela');
            if (filasTelasDOMNuevas && filasTelasDOMNuevas.length > 0) {
                console.log('[modal-novedad-edicion] 📋 Leyendo', filasTelasDOMNuevas.length, 'filas de telas del DOM');
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
                        
                        console.log('[modal-novedad-edicion] ✅ Tela nueva detectada:', telaNueva);
                        
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
                            console.log('[modal-novedad-edicion] ✅ Tela nueva agregada a window.telasAgregadas');
                        }
                    }
                });
            }
            
            // NUEVO: Enviar telas (MERGE pattern - conservar telas existentes + agregar nuevas)
            // FLUJO EDICIÓN: usar window.telasAgregadas (nuevo) o window.telasEdicion (legacy)
            const telasParaEnviar = (window.telasAgregadas && window.telasAgregadas.length > 0) 
                ? window.telasAgregadas 
                : window.telasEdicion;
            
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
                    
                    // ✅ AGREGAR IDs PARA MERGE PATTERN
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
                            if (img instanceof File) {
                                // Imagen nueva (File object)
                                formData.append(`telas[${idx}][imagenes][${imgIdx}]`, img);
                            } else if (img.urlDesdeDB || img.url) {
                                // Imagen existente de BD - guardar para preservar
                                obj.imagenes.push({
                                    url: img.url || img.urlDesdeDB,
                                    nombre: img.nombre || ''
                                });
                            }
                        });
                    }
                    
                    return obj;
                });
                formData.append('colores_telas', JSON.stringify(telasArray));
                console.log('[modal-novedad-edicion] Telas enviadas (MERGE):', telasArray);
                
                // ✅ ENVIAR FOTOS DE TELAS CON ESTRUCTURA CORRECTA (MERGE PATTERN)
                const fotosTelaArray = [];
                let fotoTelaFileIndex = 0;
                
                telasParaEnviar.forEach((tela, telaIdx) => {
                    if (tela.imagenes && tela.imagenes.length > 0) {
                        tela.imagenes.forEach((img, imgIdx) => {
                            if (img instanceof File) {
                                // Subir imagen a FormData
                                formData.append(`fotos_tela[${fotoTelaFileIndex}]`, img);
                                
                                // Registrar metadatos en array para backend
                                fotosTelaArray.push({
                                    color_id: tela.color_id || null,
                                    tela_id: tela.tela_id || null,
                                    id: img.id || null,  // ID de foto existente si está siendo actualizada
                                    orden: imgIdx + 1
                                });
                                fotoTelaFileIndex++;
                            } else if (img.id && (img.urlDesdeDB || img.url)) {
                                // Foto existente - preservar referencia
                                fotosTelaArray.push({
                                    id: img.id,
                                    color_id: tela.color_id || null,
                                    tela_id: tela.tela_id || null,
                                    ruta_original: img.url || img.urlDesdeDB,
                                    orden: imgIdx + 1
                                });
                            }
                        });
                    }
                });
                
                if (fotosTelaArray.length > 0) {
                    formData.append('fotosTelas', JSON.stringify(fotosTelaArray));
                    console.log('[modal-novedad-edicion] ✅ Fotos de telas enviadas (MERGE):', fotosTelaArray);
                }
            }

            // IMPORTANTE: Leer procesos ACTUALIZADOS (window.procesosSeleccionados)
            // NO del this.prendaData inicial que no incluye procesos nuevos agregados en el modal
            const procesosParaEnviar = window.procesosSeleccionados || this.prendaData.procesos || {};
            const procesosArray = this._transformarProcesosAArray(procesosParaEnviar);
            formData.append('procesos', JSON.stringify(procesosArray)); // Usar array transformado
            console.log('[modal-novedad-edicion] ✅ Procesos ACTUALIZADOS enviados:', procesosArray);
            formData.append('novedad', novedad);
            
            // Obtener prenda_id - puede venir en diferentes propiedades
            const prendaId = this.prendaData.prenda_pedido_id || this.prendaData.id;




            
            if (!prendaId || isNaN(prendaId)) {
                throw new Error('ID de prenda inválido o no disponible. Recibido: ' + prendaId);
            }
            
            const prendaIdInt = parseInt(prendaId);

            formData.append('prenda_id', prendaIdInt);
            
            // 🔧 FIX: Obtener imágenes ACTUALIZADAS desde window.imagenesPrendaStorage (que incluye eliminaciones)
            // NO desde this.prendaData.imagenes que es estático
            let imagenesActuales = this.prendaData.imagenes || [];
            
            // Si existen imágenes en el storage (editadas por el usuario), usar esas
            if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.obtenerImagenes === 'function') {
                const imagenesDelStorage = window.imagenesPrendaStorage.obtenerImagenes();
                if (imagenesDelStorage && imagenesDelStorage.length > 0) {
                    console.log('[modal-novedad-edicion] ✅ Usando imágenes del storage (incluye eliminaciones):', imagenesDelStorage.length);
                    imagenesActuales = imagenesDelStorage;
                } else if (imagenesDelStorage && imagenesDelStorage.length === 0) {
                    // El usuario eliminó todas las imágenes
                    console.log('[modal-novedad-edicion] ⚠️ El usuario eliminó todas las imágenes');
                    imagenesActuales = [];
                }
            }
            
            // Separar imágenes nuevas (File objects) de imágenes existentes (DB)
            const imagenesNuevas = [];
            const imagenesDB = [];
            
            if (imagenesActuales && imagenesActuales.length > 0) {
                imagenesActuales.forEach((img, idx) => {
                    if (img instanceof File) {
                        imagenesNuevas.push(img);
                        formData.append(`imagenes[${imagenesNuevas.length - 1}]`, img);
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
                    }
                });
            }
            
            console.log('[modal-novedad-edicion] 📊 Resumen de imágenes a guardar:', {
                imagenesNuevas: imagenesNuevas.length,
                imagenesExistentes: imagenesDB.length,
                total: imagenesActuales.length
            });
            
            // Enviar imágenes existentes como JSON para que backend las preserve
            if (imagenesDB.length > 0) {
                formData.append('imagenes_existentes', JSON.stringify(imagenesDB));
            } else if (imagenesDB.length === 0 && imagenesActuales.length === 0) {
                // Si no hay imágenes (el usuario las eliminó), enviar array vacío
                // Esto le indica al backend que quita todas las imágenes
                formData.append('imagenes_existentes', JSON.stringify([]));
            }
            

            // ==================== NUEVO: APLICAR CAMBIOS DE PROCESOS EDITADOS ====================
            // ANTES de guardar la prenda, aplicamos los PATCH de procesos editados
            const procesosEditados = window.gestorEditacionProcesos?.obtenerProcesosEditados();
            if (procesosEditados && procesosEditados.length > 0) {
                console.log('[modal-novedad-edicion] 🔄 Aplicando cambios de procesos editados ANTES de guardar prenda:', procesosEditados);
                
                const prendaIdInt = parseInt(this.prendaData.prenda_pedido_id || this.prendaData.id);
                
                // Ejecutar PATCH de cada proceso de forma secuencial
                for (const procesoEditado of procesosEditados) {
                    try {
                        const prendaIdInt = parseInt(this.prendaData.prenda_pedido_id || this.prendaData.id);
                        
                        // ✅ VALIDACIÓN CRÍTICA: El proceso debe tener un ID válido (debe estar guardado en BD)
                        if (!procesoEditado.id || isNaN(procesoEditado.id)) {
                            console.warn('[modal-novedad-edicion] ⚠️ SKIPPING: Proceso sin ID válido. No se puede actualizar un proceso que aún no está guardado en BD.', {
                                tipo: procesoEditado.tipo,
                                id: procesoEditado.id,
                                razon: 'Los procesos nuevos NO SE DEBEN PARCHEAR durante la edición de prenda. Deben guardarse como parte de la prenda completa.'
                            });
                            continue;
                        }
                        
                        // ✅ Determinar si hay cambios (incluyendo imágenes)
                        const tieneImagenesNuevas = window.imagenesProcesoActual?.some(img => img instanceof File);
                        const tieneImagenesExistentes = window.imagenesProcesoExistentes?.length > 0;
                        const tieneCambiosOtros = Object.keys(procesoEditado.cambios || {}).length > 0;
                        
                        // ✅ FIX: Incluir ubicaciones y observaciones actuales en la verificación
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
                            console.log('[modal-novedad-edicion] ℹ️ Sin cambios para este proceso, saltando PATCH');
                            continue;
                        }
                        
                        
                        // ✅ CAMBIO: Usar FormData en lugar de JSON para permitir subir archivos
                        const patchFormData = new FormData();
                        
                        // ✅ FIX CRITICAL: Agregar _method=PATCH para que Laravel parsee FormData correctamente
                        // Cuando se envía FormData con PATCH, Laravel/PHP no lo parsea. 
                        // Solución: enviar como POST con _method=PATCH en el FormData
                        patchFormData.append('_method', 'PATCH');
                        
                        // ✅ FIX: Incluir datos ACTUALES del proceso, no solo "cambios"
                        // Esto asegura que las ubicaciones y observaciones se envíen siempre
                        
                        // Ubicaciones: usar las del cambio si existen, sino usar las actuales de window
                        let ubicacionesAEnviar = procesoEditado.cambios.ubicaciones || 
                                                 window.ubicacionesProcesoSeleccionadas || 
                                                 [];
                        
                        // ✅ IMPORTANTE: Limpiar ubicaciones de comillas escapadas
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
                            console.log('[modal-novedad-edicion] 🔍 Ubicaciones ANTES de stringify:', {
                                tipo: typeof ubicacionesAEnviar,
                                esArray: Array.isArray(ubicacionesAEnviar),
                                contenido: ubicacionesAEnviar,
                                limpias: true
                            });
                            // Enviar ubicaciones como array individual (NO stringify)
                            ubicacionesAEnviar.forEach((ub, idx) => {
                                patchFormData.append(`ubicaciones[${idx}]`, ub);
                            });
                            console.log('[modal-novedad-edicion] 📍 Ubicaciones añadidas al PATCH (limpias):', ubicacionesAEnviar);
                        }
                        
                        // Observaciones: usar las del cambio si existen, sino intentar del DOM
                        const observacionesAEnviar = procesoEditado.cambios.observaciones || 
                                                     (obsTextarea?.value) || 
                                                     '';
                        if (observacionesAEnviar) {
                            patchFormData.append('observaciones', observacionesAEnviar);
                            console.log('[modal-novedad-edicion] Observaciones añadidas al PATCH:', observacionesAEnviar);
                        }
                        
                        // Tallas: usar las del cambio si existen
                        if (procesoEditado.cambios.tallas) {
                            patchFormData.append('tallas', JSON.stringify(procesoEditado.cambios.tallas));
                        }
                        
                        // Imágenes: usar las del cambio si existen
                        if (procesoEditado.cambios.imagenes) {
                            patchFormData.append('imagenes', JSON.stringify(procesoEditado.cambios.imagenes));
                        }
                        
                        // ✅ Incluir imágenes existentes (URLs) si las hay
                        if (window.imagenesProcesoExistentes && Array.isArray(window.imagenesProcesoExistentes) && window.imagenesProcesoExistentes.length > 0) {
                            console.log(`[modal-novedad-edicion] 🖼️ Imágenes existentes encontradas:`, window.imagenesProcesoExistentes);
                            patchFormData.append('imagenes_existentes', JSON.stringify(window.imagenesProcesoExistentes));
                        }
                        
                        // ✅ Incluir archivos nuevos de imágenes de proceso desde window.imagenesProcesoActual
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
                                    // ✅ FIX: Usar nombre simple 'imagenes_nuevas' en lugar de índices con corchetes
                                    // FormData maneja mejor esto automáticamente
                                    patchFormData.append('imagenes_nuevas', img);
                                }
                            });
                        }
                        
                        const patchResponse = await fetch(`/api/prendas-pedido/${prendaIdInt}/procesos/${procesoEditado.id}`, {
                            method: 'POST',  // ✅ FIX: Usar POST en lugar de PATCH, Laravel lo procesará con _method=PATCH
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
                        
                        console.log('[modal-novedad-edicion] ✅ PATCH aplicado exitosamente para proceso:', procesoEditado.id);
                    } catch (error) {
                        console.error('[modal-novedad-edicion] ❌ Error al aplicar PATCH:', error);
                        throw error; // Detener el proceso si algún PATCH falla
                    }
                }
                
                // Limpiar gestor de edición después de aplicar
                window.gestorEditacionProcesos?.limpiar();
                console.log('[modal-novedad-edicion] 🧹 Gestor de edición limpiado');
            }

            const response = await fetch(`/asesores/pedidos/${this.pedidoId}/actualizar-prenda`, {
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
            title: '⏳ Actualizando...',
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
            return {
                id: datosProc.id || undefined,
                tipo_proceso_id: datosProc.tipo_proceso_id || undefined,
                tipo: datosProc.tipo || tipoProceso,
                nombre: datosProc.nombre || tipoProceso,
                ubicaciones: datosProc.ubicaciones || [],
                observaciones: datosProc.observaciones || '',
                estado: datosProc.estado || 'PENDIENTE'
            };
        }).filter(proc => proc.tipo_proceso_id); // Solo retornar procesos con tipo_proceso_id válido
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

