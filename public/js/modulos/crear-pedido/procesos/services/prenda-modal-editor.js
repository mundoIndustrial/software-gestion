/**
 * PrendaModalEditor - Maneja la carga de prendas en modal para edición/creación
 * 
 * Responsabilidad: Gestionar la precarga de datos cuando se edita una prenda
 * Patrón: Service + Manager
 */

class PrendaModalEditor {
    constructor(notificationService = null) {
        this.notificationService = notificationService;
        this.prendaEnEdicion = null;
        this.prendaEditIndex = null;
    }

    /**
     * Cargar prenda en modal para edición
     */
    cargarPrendaEnModal(prenda, prendaIndex) {
        try {
            console.log('[PrendaModalEditor] 📝 Cargando prenda para edición:', {
                index: prendaIndex,
                nombre: prenda.nombre || prenda.nombre_prenda
            });

            // Guardar referencia
            this.prendaEditIndex = prendaIndex;
            this.prendaEnEdicion = prenda;

            // Cargar cada sección
            console.log('[PrendaModalEditor] 🔍 Verificando elementos del DOM...');
            this._cargarDatosBasicos(prenda);
            this._cargarTelas(prenda);
            this._cargarTallas(prenda);
            this._cargarVariantes(prenda);
            this._cargarProcesos(prenda);
            this._cargarImagenes(prenda);

            console.log('[PrendaModalEditor] ✅ Prenda cargada en modal correctamente');
        } catch (error) {
            console.error('[PrendaModalEditor] ❌ Error:', error);
            this.notificationService?.error('Error al cargar prenda: ' + error.message);
        }
    }

    /**
     * Cargar datos básicos (nombre, descripción, origen)
     */
    _cargarDatosBasicos(prenda) {
        // 1. Nombre
        const inputNombre = document.getElementById('nueva-prenda-nombre');
        if (inputNombre) {
            inputNombre.value = prenda.nombre_prenda || prenda.nombre || '';
            console.log('[PrendaModalEditor] ✅ Nombre cargado:', inputNombre.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento nueva-prenda-nombre NO encontrado');
        }

        // 2. Descripción
        const inputDesc = document.getElementById('nueva-prenda-descripcion');
        if (inputDesc) {
            inputDesc.value = prenda.descripcion || '';
            console.log('[PrendaModalEditor] ✅ Descripción cargada:', inputDesc.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento nueva-prenda-descripcion NO encontrado');
        }

        // 3. Origen
        const selectOrigen = document.getElementById('nueva-prenda-origen-select');
        if (selectOrigen) {
            selectOrigen.value = prenda.origen || 'confeccion';
            console.log('[PrendaModalEditor] ✅ Origen cargado:', selectOrigen.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento nueva-prenda-origen-select NO encontrado');
        }
    }

    /**
     * Cargar telas en window.telasCreacion Y RENDERIZAR LA TABLA
     */
    _cargarTelas(prenda) {
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
            window.telasCreacion = [...prenda.telasAgregadas];
            console.log('[PrendaModalEditor] 🧵 Telas cargadas:', window.telasCreacion.length);
            
            // ✅ RENDERIZAR LA TABLA DE TELAS
            setTimeout(() => {
                if (typeof window.actualizarTablaTelas === 'function') {
                    console.log('[PrendaModalEditor] 🎨 Renderizando tabla de telas...');
                    window.actualizarTablaTelas();
                } else {
                    console.warn('[PrendaModalEditor] ⚠️ window.actualizarTablaTelas no disponible');
                }
            }, 100);
        } else {
            console.log('[PrendaModalEditor] ⚠️ No hay telas agregadas');
        }
    }

    /**
     * Cargar tallas en window.tallasRelacionales Y RENDERIZAR TARJETAS DE GÉNEROS
     */
    _cargarTallas(prenda) {
        if (prenda.cantidad_talla && typeof prenda.cantidad_talla === 'object') {
            window.tallasRelacionales = { ...prenda.cantidad_talla };
            console.log('[PrendaModalEditor] 📏 Tallas cargadas:', Object.keys(window.tallasRelacionales));
            
            // ✅ RENDERIZAR LAS TARJETAS DE GÉNEROS CON TALLAS
            setTimeout(() => {
                // Renderizar tarjeta para cada género que tenga tallas
                if (typeof window.crearTarjetaGenero === 'function') {
                    Object.keys(window.tallasRelacionales).forEach(genero => {
                        const tallas = window.tallasRelacionales[genero];
                        if (Object.keys(tallas).length > 0) {
                            console.log(`[PrendaModalEditor] 🎨 Renderizando tarjeta para género: ${genero}`);
                            window.crearTarjetaGenero(genero);
                        }
                    });
                    
                    // Actualizar total de prendas
                    if (typeof window.actualizarTotalPrendas === 'function') {
                        window.actualizarTotalPrendas();
                    }
                } else {
                    console.warn('[PrendaModalEditor] ⚠️ window.crearTarjetaGenero no disponible');
                }
            }, 100);
        } else {
            console.log('[PrendaModalEditor] ⚠️ No hay tallas');
        }
    }

    /**
     * Cargar variantes (género, manga, broche, bolsillos, reflectivo)
     */
    _cargarVariantes(prenda) {
        if (!prenda.variantes || typeof prenda.variantes !== 'object') {
            console.log('[PrendaModalEditor] ⚠️ No hay variantes');
            return;
        }

        const variantes = prenda.variantes;
        console.log('[PrendaModalEditor] 👗 Variantes a cargar:', variantes);

        // Género
        this._cargarGenero(variantes);
        
        // Manga
        this._cargarManga(variantes);
        
        // Broche
        this._cargarBroche(variantes);
        
        // Bolsillos
        this._cargarBolsillos(variantes);
        
        // Reflectivo
        this._cargarReflectivo(variantes);
    }

    /**
     * Cargar género (DAMA/CABALLERO)
     */
    _cargarGenero(variantes) {
        const damaCb = document.getElementById('dama');
        const caballeroCb = document.getElementById('caballero');
        
        if (damaCb && damaCb.type === 'checkbox') {
            damaCb.checked = (variantes.genero_id === 1);
            console.log('[PrendaModalEditor] ✅ Género DAMA:', damaCb.checked);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento dama NO encontrado');
        }
        
        if (caballeroCb && caballeroCb.type === 'checkbox') {
            caballeroCb.checked = (variantes.genero_id === 2);
            console.log('[PrendaModalEditor] ✅ Género CABALLERO:', caballeroCb.checked);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento caballero NO encontrado');
        }
    }

    /**
     * Cargar tipo de manga Y OBSERVACIONES
     */
    _cargarManga(variantes) {
        // Cargar checkbox APLICA-MANGA
        const aplicaMangaCheck = document.getElementById('aplica-manga');
        if (aplicaMangaCheck) {
            const tieneManga = variantes.tipo_manga && variantes.tipo_manga !== 'No aplica';
            aplicaMangaCheck.checked = tieneManga;
            console.log('[PrendaModalEditor] ✅ Aplica-Manga:', tieneManga);
        }

        // Cargar tipo de manga
        const mangaInput = document.getElementById('manga-input');
        if (mangaInput) {
            mangaInput.value = variantes.tipo_manga || '';
            mangaInput.disabled = false;
            mangaInput.style.opacity = '1';
            console.log('[PrendaModalEditor] ✅ Tipo-Manga cargada:', mangaInput.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento manga-input NO encontrado');
        }

        // ✅ CARGAR OBSERVACIÓN DE MANGA
        const mangaObs = document.getElementById('manga-obs');
        if (mangaObs) {
            mangaObs.value = variantes.obs_manga || '';
            mangaObs.disabled = false;
            mangaObs.style.opacity = '1';
            mangaObs.removeAttribute('readonly');
            console.log('[PrendaModalEditor] ✅ Observación-Manga cargada:', mangaObs.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento manga-obs NO encontrado');
        }
    }

    /**
     * Cargar tipo de broche Y OBSERVACIONES
     */
    _cargarBroche(variantes) {
        // Cargar checkbox APLICA-BROCHE
        const aplicaBrocheCheck = document.getElementById('aplica-broche');
        if (aplicaBrocheCheck) {
            const tieneBroche = variantes.tipo_broche && variantes.tipo_broche !== 'No aplica';
            aplicaBrocheCheck.checked = tieneBroche;
            console.log('[PrendaModalEditor] ✅ Aplica-Broche:', tieneBroche);
        }

        // Cargar tipo de broche
        const brocheInput = document.getElementById('broche-input');
        if (brocheInput) {
            brocheInput.value = variantes.tipo_broche || '';
            brocheInput.disabled = false;
            brocheInput.style.opacity = '1';
            console.log('[PrendaModalEditor] ✅ Tipo-Broche cargado:', brocheInput.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento broche-input NO encontrado');
        }

        // ✅ CARGAR OBSERVACIÓN DE BROCHE
        const brocheObs = document.getElementById('broche-obs');
        if (brocheObs) {
            brocheObs.value = variantes.obs_broche || '';
            brocheObs.disabled = false;
            brocheObs.style.opacity = '1';
            brocheObs.removeAttribute('readonly');
            console.log('[PrendaModalEditor] ✅ Observación-Broche cargada:', brocheObs.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento broche-obs NO encontrado');
        }
    }

    /**
     * Cargar aplicación de bolsillos Y OBSERVACIONES
     */
    _cargarBolsillos(variantes) {
        // Cargar checkbox APLICA-BOLSILLOS
        const bolsillosCheck = document.getElementById('aplica-bolsillos');
        if (bolsillosCheck && bolsillosCheck.type === 'checkbox') {
            bolsillosCheck.checked = (variantes.tiene_bolsillos === true || variantes.aplica_bolsillos === true);
            console.log('[PrendaModalEditor] ✅ Bolsillos:', bolsillosCheck.checked);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento aplica-bolsillos NO encontrado');
        }

        // ✅ CARGAR OBSERVACIÓN DE BOLSILLOS
        const bolsillosObs = document.getElementById('bolsillos-obs');
        if (bolsillosObs) {
            bolsillosObs.value = variantes.obs_bolsillos || '';
            bolsillosObs.disabled = false;
            bolsillosObs.style.opacity = '1';
            bolsillosObs.removeAttribute('readonly');
            console.log('[PrendaModalEditor] ✅ Observación-Bolsillos cargada:', bolsillosObs.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento bolsillos-obs NO encontrado');
        }
    }

    /**
     * Cargar aplicación de reflectivo Y OBSERVACIONES
     */
    _cargarReflectivo(variantes) {
        // Cargar checkbox APLICA-REFLECTIVO
        const reflectivoCheck = document.getElementById('aplica-reflectivo');
        if (reflectivoCheck && reflectivoCheck.type === 'checkbox') {
            reflectivoCheck.checked = (variantes.tiene_reflectivo === true || variantes.aplica_reflectivo === true);
            console.log('[PrendaModalEditor] ✅ Reflectivo:', reflectivoCheck.checked);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento aplica-reflectivo NO encontrado');
        }

        // ✅ CARGAR OBSERVACIÓN DE REFLECTIVO
        const reflectivoObs = document.getElementById('reflectivo-obs');
        if (reflectivoObs) {
            reflectivoObs.value = variantes.obs_reflectivo || '';
            reflectivoObs.disabled = false;
            reflectivoObs.style.opacity = '1';
            reflectivoObs.removeAttribute('readonly');
            console.log('[PrendaModalEditor] ✅ Observación-Reflectivo cargada:', reflectivoObs.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento reflectivo-obs NO encontrado');
        }
    }

    /**
     * Cargar procesos en window.procesosSeleccionados Y RENDERIZAR TARJETAS DE PROCESOS
     */
    _cargarProcesos(prenda) {
        if (prenda.procesos && typeof prenda.procesos === 'object') {
            window.procesosSeleccionados = { ...prenda.procesos };
            console.log('[PrendaModalEditor] ⚙️ Procesos cargados:', Object.keys(window.procesosSeleccionados).length);
            
            // ✅ MARCAR CHECKBOXES DE PROCESOS
            const procesosDisponibles = ['reflectivo', 'bordado', 'estampado', 'dtf', 'sublimado'];
            procesosDisponibles.forEach(proceso => {
                const checkbox = document.getElementById(`checkbox-${proceso}`);
                if (checkbox) {
                    // Usar _ignorarOnclick para evitar disparar el evento onclick
                    checkbox._ignorarOnclick = true;
                    checkbox.checked = proceso in window.procesosSeleccionados;
                    checkbox._ignorarOnclick = false;
                    console.log(`[PrendaModalEditor] ✅ Checkbox ${proceso} marcado:`, checkbox.checked);
                }
            });
            
            // ✅ RENDERIZAR LAS TARJETAS DE PROCESOS
            setTimeout(() => {
                if (typeof window.renderizarTarjetasProcesos === 'function') {
                    console.log('[PrendaModalEditor] 🎨 Renderizando tarjetas de procesos...');
                    window.renderizarTarjetasProcesos();
                } else {
                    console.warn('[PrendaModalEditor] ⚠️ window.renderizarTarjetasProcesos no disponible');
                }
            }, 100);
        } else {
            console.log('[PrendaModalEditor] ⚠️ No hay procesos');
        }
    }

    /**
     * Cargar imágenes en ImageStorageService
     */
    _cargarImagenes(prenda) {
        // Limpiar primero (revoca URLs pero no afecta los Files guardados en prenda.imagenes)
        if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.limpiar === 'function') {
            window.imagenesPrendaStorage.limpiar();
            console.log('[PrendaModalEditor] 🧹 ImagenStorage limpiado');
        }

        // Cargar nuevas imágenes
        if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
            if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.establecerImagenes === 'function') {
                console.log('[PrendaModalEditor] 📸 Cargando', prenda.imagenes.length, 'imágenes');
                
                // Reconstruir imágenes: Si tienen File, crear NUEVAS blob URLs
                const imagenesReconstruidas = prenda.imagenes.map(img => {
                    if (img.file && img.file instanceof File) {
                        // ✅ Crear NUEVA blob URL desde el File preservado
                        const nuevaPreviewUrl = URL.createObjectURL(img.file);
                        console.log('[PrendaModalEditor] 🔄 Recreando blob URL desde File:', img.nombre);
                        return {
                            ...img,
                            previewUrl: nuevaPreviewUrl  // Nueva URL válida
                        };
                    } else if (typeof img === 'string') {
                        // Si es solo una URL string (modo servidor), convertir a objeto
                        return { previewUrl: img, url: img };
                    } else if (img && !img.previewUrl && (img.url || img.ruta || img.ruta_webp)) {
                        // Si es objeto sin previewUrl, agregarlo
                        return { ...img, previewUrl: img.url || img.ruta || img.ruta_webp };
                    }
                    return img;
                });
                
                window.imagenesPrendaStorage.establecerImagenes(imagenesReconstruidas);
                
                // Actualizar preview
                setTimeout(() => {
                    if (typeof window.actualizarPreviewPrenda === 'function') {
                        console.log('[PrendaModalEditor] 🎨 Actualizando preview de imágenes...');
                        window.actualizarPreviewPrenda();
                    }
                }, 100);
            }
        } else {
            console.log('[PrendaModalEditor] ⚠️ No hay imágenes');
        }
    }

    /**
     * Obtener referencias actuales (para saber si estamos editando)
     */
    estamosEditando() {
        return this.prendaEditIndex !== null;
    }

    /**
     * Obtener índice de prenda en edición
     */
    obtenerIndicePrendaEdicion() {
        return this.prendaEditIndex;
    }

    /**
     * Limpiar referencias de edición
     */
    limpiarEdicion() {
        this.prendaEditIndex = null;
        this.prendaEnEdicion = null;
        console.log('[PrendaModalEditor] 🧹 Referencias de edición limpiadas');
    }
}

// Exportar a window si no existe
if (typeof window.PrendaModalEditor === 'undefined') {
    window.PrendaModalEditor = PrendaModalEditor;
    console.log('[PrendaModalEditor] ✅ Clase PrendaModalEditor exportada a window');
}
