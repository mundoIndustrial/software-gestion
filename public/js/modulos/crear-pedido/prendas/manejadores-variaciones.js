/**
 * manejadores-variaciones.js
 * 
 * Maneja los eventos de variaciones en prendas (manga, bolsillos, broche)
 * Habilita/deshabilita inputs según los checkboxes seleccionados
 */

// Variable global para almacenar tipos de manga cargados
let tiposMangaDisponibles = [];

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

/**
 * Cargar tipos de manga disponibles desde la BD
 */
async function cargarTiposMangaDisponibles() {
    try {
        const response = await fetch('/asesores/api/tipos-manga');
        const result = await response.json();
        
        if (result.success && result.data) {
            tiposMangaDisponibles = result.data;
            
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
        }
    } catch (error) {
        console.warn('[Manga] Error cargando tipos de manga:', error);
    }
}

// Exportar como función global para uso en otros módulos
window.cargarTiposMangaDisponibles = cargarTiposMangaDisponibles;
window.procesarMangaInput = procesarMangaInput;

/**
 * Procesar input de manga cuando pierde el foco
 * Si no existe en la BD, lo crea automáticamente
 */
async function procesarMangaInput(input) {
    const valor = input.value.trim();
    if (!valor) return;
    
    try {
        // Verificar si ya existe en el datalist
        const datalist = document.getElementById('opciones-manga');
        let existe = false;
        
        if (datalist) {
            for (let option of datalist.options) {
                if (option.value.toLowerCase() === valor.toLowerCase()) {
                    existe = true;
                    break;
                }
            }
        }
        
        if (!existe) {
            // Crear el nuevo tipo de manga
            const response = await fetch('/asesores/api/tipos-manga', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ nombre: valor })
            });
            
            const result = await response.json();
            if (result.success && result.data) {
                // Agregar al datalist
                const newOption = document.createElement('option');
                newOption.value = result.data.nombre;
                newOption.dataset.id = result.data.id;
                
                if (datalist) {
                    datalist.appendChild(newOption);
                }
                
                // Actualizar array global
                tiposMangaDisponibles.push(result.data);
                
                console.log('✅ Tipo de manga creado:', result.data);
            }
        }
    } catch (error) {
        console.error('[Manga] Error procesando manga:', error);
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
});


