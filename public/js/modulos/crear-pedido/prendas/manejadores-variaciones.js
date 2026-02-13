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
        console.log('[Manga] Usando cache de tipos de manga');
        return tiposMangaCache;
    }
    
    // Si hay una petición en curso, esperar a que termine
    if (tiposMangaPromise) {
        console.log('[Manga] Esperando petición existente...');
        return await tiposMangaPromise;
    }
    
    // Crear nueva petición
    console.log('[Manga] Cargando tipos de manga desde BD...');
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
                
                console.log('[Manga] Tipos cargados y cacheados:', result.data.length);
                return result.data;
            }
        } catch (error) {
            console.warn('[Manga] Error cargando tipos de manga:', error);
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
    console.log('[Manga] Caché limpiado');
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
        console.log('[Broche] Usando cache de tipos de broche/botón');
        return tiposBrocheCache;
    }
    
    // Si hay una petición en curso, esperar a que termine
    if (tiposBrochePromise) {
        console.log('[Broche] Esperando petición existente...');
        return await tiposBrochePromise;
    }
    
    // Crear nueva petición
    console.log('[Broche] Cargando tipos de broche/botón desde BD...');
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
                
                console.log('[Broche] Tipos cargados y cacheados:', result.data.length);
                return result.data;
            }
        } catch (error) {
            console.warn('[Broche] Error cargando tipos de broche:', error);
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
    console.log('[Broche] Caché limpiado');
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
            console.log('[Manga] Creando nuevo tipo de manga:', valor);
            
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
                
                console.log('[Manga] Tipo de manga creado:', result.data);
            }
        } else {
            console.log('[Manga] Tipo de manga ya existe:', valor);
        }
    } catch (error) {
        console.warn('[Manga] Error procesando input:', error);
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
    console.log('[DOMContentLoaded] Catálogos de telas y colores se cargarán bajo demanda');
});

/**
 * Cargar telas disponibles desde la BD
 */
async function cargarTelasDisponibles() {
    console.log('[Telas] Iniciando carga de telas disponibles...');
    try {
        const response = await fetch('/api/public/telas');
        const result = await response.json();
        
        console.log('[Telas] Respuesta de API:', { 
            success: result.success, 
            count: result.data?.length || 0,
            status: response.status
        });
        
        if (result.success && result.data) {
            telasDisponibles = result.data;
            console.log('[Telas] Telas cargadas en memoria:', result.data.length);
            
            // Actualizar datalist
            const datalist = document.getElementById('opciones-telas');
            console.log('[Telas] Buscando datalist con id "opciones-telas":', datalist ? '✅ Encontrado' : '❌ NO ENCONTRADO');
            
            if (datalist) {
                datalist.innerHTML = '';
                result.data.forEach(tela => {
                    const option = document.createElement('option');
                    option.value = tela.nombre;
                    option.dataset.id = tela.id;
                    option.dataset.referencia = tela.referencia || '';
                    datalist.appendChild(option);
                });
                console.log('[Telas] Datalist actualizado con', result.data.length, 'telas');
            } else {
                console.warn('[Telas] Datalist "opciones-telas" no existe en el DOM');
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
    console.log('[Colores] Iniciando carga de colores disponibles...');
    try {
        const response = await fetch('/api/public/colores');
        const result = await response.json();
        
        console.log('[Colores] Respuesta de API:', { 
            success: result.success, 
            count: result.data?.length || 0,
            status: response.status
        });
        
        if (result.success && result.data) {
            coloresDisponibles = result.data;
            console.log('[Colores] Colores cargados en memoria:', result.data.length);
            
            // Actualizar datalist
            const datalist = document.getElementById('opciones-colores');
            console.log('[Colores] Buscando datalist con id "opciones-colores":', datalist ? '✅ Encontrado' : '❌ NO ENCONTRADO');
            
            if (datalist) {
                datalist.innerHTML = '';
                result.data.forEach(color => {
                    const option = document.createElement('option');
                    option.value = color.nombre;
                    option.dataset.id = color.id;
                    option.dataset.codigo = color.codigo || '';
                    datalist.appendChild(option);
                });
                console.log('[Colores] Datalist actualizado con', result.data.length, 'colores');
            } else {
                console.warn('[Colores] Datalist "opciones-colores" no existe en el DOM');
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

// 🔄 Flags de control para cargar bajo demanda
window._telasCargadas = false;
window._coloresCargados = false;

/**
 * Cargar catálogos bajo demanda (solo una vez)
 */
window.cargarCatalogosModal = async function() {
    // Cargar telas si no se han cargado
    if (!window._telasCargadas) {
        await cargarTelasDisponibles();
        window._telasCargadas = true;
    } else {
        console.log('[Catálogos] Telas ya cargadas, usando cache');
    }
    
    // Cargar colores si no se han cargado
    if (!window._coloresCargados) {
        await cargarColoresDisponibles();
        window._coloresCargados = true;
    } else {
        console.log('[Catálogos] Colores ya cargados, usando cache');
    }
};

// Exportar funciones globales
window.cargarTelasDisponibles = cargarTelasDisponibles;
window.cargarColoresDisponibles = cargarColoresDisponibles;
