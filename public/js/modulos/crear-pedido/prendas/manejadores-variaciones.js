/**
 * manejadores-variaciones.js
 * 
 * Maneja los eventos de variaciones en prendas (manga, bolsillos, broche)
 * Habilita/deshabilita inputs según los checkboxes seleccionados
 * También maneja telas y colores con auto-creación
 */

// Variables globales para almacenar catálogos cargados
let tiposMangaDisponibles = [];
let telasDisponibles = [];
let coloresDisponibles = [];

// Manejar cambio de variaciones (manga, bolsillos, broche)
window.manejarCheckVariacion = function(checkbox) {
    const idCheckbox = checkbox.id;


    
    let inputIds = [];
    
    if (idCheckbox === 'aplica-manga') {
        inputIds = ['manga-input', 'manga-obs'];
        
        // Cargar tipos de manga cuando se activa el checkbox
        if (checkbox.checked) {
            cargarTiposMangaDisponibles();
        }
    }
    else if (idCheckbox === 'aplica-bolsillos') {
        inputIds = ['bolsillos-obs'];

    }
    else if (idCheckbox === 'aplica-broche') inputIds = ['broche-input', 'broche-obs'];
    
    inputIds.forEach(inputId => {
        const input = document.getElementById(inputId);

        if (input) {
            input.disabled = !checkbox.checked;
            input.style.opacity = checkbox.checked ? '1' : '0.5';

        }
    });
};

// Cache para tipos de manga
let tiposMangaCache = null;
let tiposMangaPromise = null;

/**
 * Cargar tipos de manga disponibles desde la BD
 * Con sistema de caché para evitar múltiples llamadas
 */
async function cargarTiposMangaDisponibles() {
    // Si ya tenemos datos en caché, retornar inmediatamente
    if (tiposMangaCache) {
        return tiposMangaCache;
    }
    
    // Si hay una petición en curso, esperar a que termine
    if (tiposMangaPromise) {
        return await tiposMangaPromise;
    }
    
    // Crear nueva petición
    tiposMangaPromise = (async () => {
        try {
            // Usar ruta pública accesible para todos los roles
            const response = await fetch('/api/public/tipos-manga');
            const result = await response.json();
            
            if (result.success && result.data) {
                tiposMangaCache = result.data;
                
                // Actualizar datalist
                const datalist = document.getElementById('opciones-manga');
                if (datalist) {
                    datalist.innerHTML = '';
                    result.data.forEach(tipo => {
                        const option = document.createElement('option');
                        option.value = tipo.nombre;
                        option.dataset.id = tipo.id;
                        datalist.appendChild(option);
                    });
                }
                
                return result.data;
            }
        } catch (error) {
            return [];
        } finally {
            // Limpiar la promesa cuando termine
            tiposMangaPromise = null;
        }
    })();
    
    return await tiposMangaPromise;
}

/**
 * Limpiar caché de tipos de manga (útil después de crear/editar)
 */
function limpiarCacheTiposManga() {
    tiposMangaCache = null;
    tiposMangaPromise = null;
}

// Exportar como funciones globales para uso en otros módulos
window.cargarTiposMangaDisponibles = cargarTiposMangaDisponibles;
window.procesarMangaInput = procesarMangaInput;
window.limpiarCacheTiposManga = limpiarCacheTiposManga;

// Cache para tipos de broche/botón
let tiposBrocheCache = null;
let tiposBrochePromise = null;

/**
 * Cargar tipos de broche/botón disponibles desde la BD
 * Con sistema de caché para evitar múltiples llamadas
 */
async function cargarTiposBrocheBotonDisponibles() {
    // Si ya tenemos datos en caché, retornar inmediatamente
    if (tiposBrocheCache) {
        return tiposBrocheCache;
    }
    
    // Si hay una petición en curso, esperar a que termine
    if (tiposBrochePromise) {
        return await tiposBrochePromise;
    }
    
    // Crear nueva petición
    tiposBrochePromise = (async () => {
        try {
            const response = await fetch('/api/public/tipos-broche-boton');
            const result = await response.json();
            
            if (result.success && result.data) {
                tiposBrocheCache = result.data;
                
                // Actualizar datalist
                const datalist = document.getElementById('opciones-broche');
                if (datalist) {
                    datalist.innerHTML = '';
                    result.data.forEach(tipo => {
                        const option = document.createElement('option');
                        option.value = tipo.nombre;
                        option.dataset.id = tipo.id;
                        datalist.appendChild(option);
                    });
                }
                
                return result.data;
            }
        } catch (error) {
            return [];
        } finally {
            // Limpiar la promesa cuando termine
            tiposBrochePromise = null;
        }
    })();
    
    return await tiposBrochePromise;
}

/**
 * Limpiar caché de tipos de broche (útil después de crear/editar)
 */
function limpiarCacheTiposBroche() {
    tiposBrocheCache = null;
    tiposBrochePromise = null;
}

// Exportar función global
window.cargarTiposBrocheBotonDisponibles = cargarTiposBrocheBotonDisponibles;
window.limpiarCacheTiposBroche = limpiarCacheTiposBroche;

/**
 * Procesar input de manga cuando pierde el foco
 * Si no existe en la BD, lo crea automáticamente
 */
async function procesarMangaInput(input) {
    const valor = input.value.trim();
    if (!valor) return;
    
    try {
        // Asegurarse que los tipos de manga estén cargados (usando caché)
        const tiposManga = await cargarTiposMangaDisponibles();
        
        // Verificar si ya existe usando el caché
        let existe = tiposManga.some(tipo => 
            tipo.nombre.toLowerCase() === valor.toLowerCase()
        );
        
        if (!existe) {
            
            // Crear el nuevo tipo de manga
            const response = await fetch('/api/public/tipos-manga', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ nombre: valor })
            });
            
            const result = await response.json();
            if (result.success && result.data) {
                // Invalidar caché para que se recargue la próxima vez
                tiposMangaCache = null;
                
                // Agregar al datalist
                const datalist = document.getElementById('opciones-manga');
                if (datalist) {
                    const newOption = document.createElement('option');
                    newOption.value = result.data.nombre;
                    newOption.dataset.id = result.data.id;
                    datalist.appendChild(newOption);
                }
            }
        }
    } catch (error) {
    }
}

// Configurar evento blur para el input de manga cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    const mangaInput = document.getElementById('manga-input');
    if (mangaInput) {
        mangaInput.addEventListener('blur', function() {
            procesarMangaInput(this);
        });
    }
    
    // 🔄 Catálogos se cargan bajo demanda cuando se abre el modal
});

/**
 * Cargar telas disponibles desde la BD
 */
async function cargarTelasDisponibles() {
    try {
        const response = await fetch('/api/public/telas');
        const result = await response.json();
        
        if (result.success && result.data) {
            telasDisponibles = result.data;
            
            // Actualizar datalist
            const datalist = document.getElementById('opciones-telas');
            
            if (datalist) {
                datalist.innerHTML = '';
                result.data.forEach(tela => {
                    const option = document.createElement('option');
                    option.value = tela.nombre;
                    option.dataset.id = tela.id;
                    option.dataset.referencia = tela.referencia || '';
                    datalist.appendChild(option);
                });
            }
        } else {
            console.warn('[Telas] Respuesta no válida:', result);
        }
    } catch (error) {
        console.error('[Telas] Error cargando telas:', {
            message: error.message,
            stack: error.stack
        });
    }
}

/**
 * Cargar colores disponibles desde la BD
 */
async function cargarColoresDisponibles() {
    try {
        const response = await fetch('/api/public/colores');
        const result = await response.json();
        
        if (result.success && result.data) {
            coloresDisponibles = result.data;
            
            // Actualizar datalist
            const datalist = document.getElementById('opciones-colores');
            
            if (datalist) {
                datalist.innerHTML = '';
                result.data.forEach(color => {
                    const option = document.createElement('option');
                    option.value = color.nombre;
                    option.dataset.id = color.id;
                    option.dataset.codigo = color.codigo || '';
                    datalist.appendChild(option);
                });
            }
        } else {
            console.warn('[Colores] Respuesta no válida:', result);
        }
    } catch (error) {
        console.error('[Colores] Error cargando colores:', {
            message: error.message,
            stack: error.stack
        });
    }
}

/**
 * ================================================
 * FASE 1: DEDUPLICACIÓN DE PROMISES
 * ================================================
 * 
 * Cambios:
 * - Eliminado flags globales (_telasCargadas, _coloresCargados)
 * - Agregada deduplicación con PromiseCache
 * - Múltiples llamadas simultáneas reutilizan la misma promise
 * 
 * Requiere: promise-cache.js cargado antes
 */

/**
 * Cargar catálogos bajo demanda (solo una vez)
 * 
 * Si hay una carga en progreso, reutiliza esa promise
 * Múltiples llamadas simultáneas = 1 solo fetch
 * 
 * @returns {Promise<object>} { telas, colores }
 */
window.cargarCatalogosModal = async function() {
    const CACHE_KEY = 'catalogs:telas-colores';
    
    // Guard 1: Si hay una promise en flight, reutilizarla
    if (typeof PromiseCache !== 'undefined' && PromiseCache.has(CACHE_KEY)) {
        return PromiseCache.get(CACHE_KEY);
    }

    // Crear nueva promise de carga
    const catalogsPromise = (async () => {
        try {
            
            // Cargar en paralelo (Promise.all)
            const [telas, colores] = await Promise.all([
                cargarTelasDisponibles(),
                cargarColoresDisponibles()
            ]);
            
            return { telas, colores };
        } catch (error) {
            console.error('[Catálogos]  Error cargando catálogos:', {
                message: error.message,
                stack: error.stack
            });
            throw error;
        }
    })();

    // Guardar en caché (se limpia automáticamente al terminar)
    if (typeof PromiseCache !== 'undefined') {
        PromiseCache.set(CACHE_KEY, catalogsPromise);
    }

    return catalogsPromise;
};

// Exportar funciones globales (para compatibilidad)
window.cargarTelasDisponibles = cargarTelasDisponibles;
window.cargarColoresDisponibles = cargarColoresDisponibles;
