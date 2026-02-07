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
     * Cargar telas en window.telasCreacion
     */
    _cargarTelas(prenda) {
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
            window.telasCreacion = [...prenda.telasAgregadas];
            console.log('[PrendaModalEditor] 🧵 Telas cargadas:', window.telasCreacion.length);
        } else {
            console.log('[PrendaModalEditor] ⚠️ No hay telas agregadas');
        }
    }

    /**
     * Cargar tallas en window.tallasRelacionales
     */
    _cargarTallas(prenda) {
        if (prenda.cantidad_talla && typeof prenda.cantidad_talla === 'object') {
            window.tallasRelacionales = { ...prenda.cantidad_talla };
            console.log('[PrendaModalEditor] 📏 Tallas cargadas:', Object.keys(window.tallasRelacionales));
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
     * Cargar tipo de manga
     */
    _cargarManga(variantes) {
        const mangaInput = document.getElementById('manga-input');
        if (mangaInput) {
            mangaInput.value = variantes.tipo_manga || '';
            mangaInput.disabled = false;
            console.log('[PrendaModalEditor] ✅ Manga cargada:', mangaInput.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento manga-input NO encontrado');
        }
    }

    /**
     * Cargar tipo de broche
     */
    _cargarBroche(variantes) {
        const brocheInput = document.getElementById('broche-input');
        if (brocheInput) {
            brocheInput.value = variantes.tipo_broche || '';
            brocheInput.disabled = false;
            console.log('[PrendaModalEditor] ✅ Broche cargado:', brocheInput.value);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento broche-input NO encontrado');
        }
    }

    /**
     * Cargar aplicación de bolsillos
     */
    _cargarBolsillos(variantes) {
        const bolsillosCheck = document.getElementById('aplica-bolsillos');
        if (bolsillosCheck && bolsillosCheck.type === 'checkbox') {
            bolsillosCheck.checked = (variantes.aplica_bolsillos === true);
            console.log('[PrendaModalEditor] ✅ Bolsillos:', bolsillosCheck.checked);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento aplica-bolsillos NO encontrado');
        }
    }

    /**
     * Cargar aplicación de reflectivo
     */
    _cargarReflectivo(variantes) {
        const reflectivoCheck = document.getElementById('aplica-reflectivo');
        if (reflectivoCheck && reflectivoCheck.type === 'checkbox') {
            reflectivoCheck.checked = (variantes.aplica_reflectivo === true);
            console.log('[PrendaModalEditor] ✅ Reflectivo:', reflectivoCheck.checked);
        } else {
            console.log('[PrendaModalEditor] ⚠️ Elemento aplica-reflectivo NO encontrado');
        }
    }

    /**
     * Cargar procesos en window.procesosSeleccionados
     */
    _cargarProcesos(prenda) {
        if (prenda.procesos && typeof prenda.procesos === 'object') {
            window.procesosSeleccionados = { ...prenda.procesos };
            console.log('[PrendaModalEditor] ⚙️ Procesos cargados:', Object.keys(window.procesosSeleccionados).length);
        } else {
            console.log('[PrendaModalEditor] ⚠️ No hay procesos');
        }
    }

    /**
     * Cargar imágenes en ImageStorageService
     */
    _cargarImagenes(prenda) {
        // Limpiar primero
        if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.limpiar === 'function') {
            window.imagenesPrendaStorage.limpiar();
            console.log('[PrendaModalEditor] 🧹 ImagenStorage limpiado');
        }

        // Cargar nuevas imágenes
        if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
            if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.cargarImagenes === 'function') {
                console.log('[PrendaModalEditor] 📸 Cargando', prenda.imagenes.length, 'imágenes');
                window.imagenesPrendaStorage.cargarImagenes(prenda.imagenes);
                
                // Actualizar preview
                if (typeof window.actualizarPreviewPrenda === 'function') {
                    window.actualizarPreviewPrenda();
                }
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
