/**
 * Sistema de Variantes de Prendas
 * Integración con formulario de cotización
 */

let tiposPrendaCache = [];
let variacionesCache = {};

/**
 * Inicializar sistema de variantes
 */
function inicializarVariantes() {
    console.log('🔵 Inicializando sistema de variantes de prendas...');
    cargarTiposPrenda();
}

/**
 * Cargar tipos de prenda desde API
 */
function cargarTiposPrenda() {
    fetch('/api/tipos-prenda')
        .then(res => res.json())
        .then(tipos => {
            tiposPrendaCache = tipos;
            console.log(' Tipos de prenda cargados:', tipos.length);
        })
        .catch(err => console.error(' Error cargando tipos:', err));
}

/**
 * Reconocer tipo de prenda por nombre
 */
function reconocerTipoPrenda(nombrePrenda) {
    if (!nombrePrenda || nombrePrenda.trim() === '') {
        return null;
    }

    const nombreUpper = nombrePrenda.toUpperCase();
    
    for (let tipo of tiposPrendaCache) {
        if (!tipo.palabras_clave) continue;
        
        let palabras = [];
        
        // Manejar diferentes formatos de palabras_clave
        if (Array.isArray(tipo.palabras_clave)) {
            palabras = tipo.palabras_clave;
        } else if (typeof tipo.palabras_clave === 'string') {
            // Intentar parsear como JSON primero
            try {
                palabras = JSON.parse(tipo.palabras_clave);
            } catch (e) {
                // Si falla, separar por coma
                palabras = tipo.palabras_clave.split(',').map(p => p.trim());
            }
        }
        
        for (let palabra of palabras) {
            if (nombreUpper.includes(palabra.toUpperCase())) {
                return tipo;
            }
        }
    }
    
    return null;
}

/**
 * Cargar variaciones disponibles para un tipo de prenda
 */
function cargarVariacionesPrenda(tipoPrendaId) {
    if (variacionesCache[tipoPrendaId]) {
        return Promise.resolve(variacionesCache[tipoPrendaId]);
    }

    // Endpoint - usar la ruta correcta sin /api/
    // Nota: Es normal que retorne 404 si no existen variaciones predefinidas
    // El sistema creará automáticamente lo que sea necesario
    return fetch(`/prenda-variaciones/${tipoPrendaId}`)
        .then(res => {
            // Si el endpoint no existe (404) o hay error, es OK
            // El sistema crea automáticamente lo que falta
            if (!res.ok) {
                return Promise.resolve(null);
            }
            return res.json();
        })
        .then(data => {
            if (data && data.success) {
                variacionesCache[tipoPrendaId] = data.variaciones;
                return data.variaciones;
            }
            return null;
        })
        .catch(err => {
            // Ignorar el error - es esperado si el endpoint no existe
            // console.debug() no muestra en consola por defecto
            return null;
        });
}

/**
 * Mostrar selector de variantes para una prenda
 */
function mostrarSelectorVariantes(inputElement) {
    const nombrePrenda = inputElement.value;
    const tipoPrenda = reconocerTipoPrenda(nombrePrenda);
    
    // Mostrar selector de JEAN/PANTALÓN incluso si no se reconoce el tipo
    mostrarSelectorJeanPantalon(inputElement, nombrePrenda);
    
    // Si no se reconoce el tipo, mostrar campos básicos
    if (!tipoPrenda) {
        console.log(' Tipo de prenda no reconocido, mostrando campos básicos de variantes');
        crearSelectorVariantesBasico(inputElement, nombrePrenda);
        return;
    }

    console.log(' Tipo reconocido:', tipoPrenda.nombre);
    
    // IMPORTANTE: Mostrar campos de variantes PRIMERO (siempre)
    // Luego intentar cargar variaciones específicas si existen
    crearSelectorVariantesBasico(inputElement, nombrePrenda);
    
    // Cargar variaciones específicas (opcional)
    cargarVariacionesPrenda(tipoPrenda.id).then(variaciones => {
        // Las variaciones específicas se cargarían aquí si fueran necesarias
        // Por ahora, los campos básicos ya están visibles
        if (variaciones) {
            console.log(' Variaciones cargadas para:', tipoPrenda.nombre);
        }
    });
}

/**
 * Mostrar selector de Tipo de JEAN/PANTALÓN basado en el texto escrito
 * Solo si JEAN o PANTALÓN es la palabra PRINCIPAL
 */
function mostrarSelectorJeanPantalon(inputElement, nombrePrenda) {
    const productoCard = inputElement.closest('.producto-card');
    if (!productoCard) return;
    
    const tipoJeanPantalon_inline = productoCard.querySelector('.tipo-jean-pantalon-inline');
    const tipoJeanPantalon_inline_container = productoCard.querySelector('.tipo-jean-pantalon-inline-container');
    
    // Si el input está vacío, ocultar el selector
    if (!nombrePrenda || nombrePrenda.trim() === '') {
        tipoJeanPantalon_inline.style.display = 'none';
        tipoJeanPantalon_inline_container.innerHTML = '';
        return;
    }
    
    const nombreUpper = nombrePrenda.toUpperCase().trim();
    
    // Obtener la primera palabra (palabra principal)
    const palabraPrincipal = nombreUpper.split(/\s+/)[0];
    
    console.log(' DEBUG mostrarSelectorJeanPantalon:', {
        nombrePrenda: nombrePrenda,
        nombreUpper: nombreUpper,
        palabraPrincipal: palabraPrincipal
    });
    
    // Verificar si la palabra principal es JEAN, JEANS, PANTALÓN o PANTALONES
    const esJean = /^JEAN/.test(palabraPrincipal);
    const esPantalon = /^PANTALÓ?N/.test(palabraPrincipal);
    
    console.log(' DEBUG esJean:', esJean, 'esPantalon:', esPantalon);
    
    if (esJean || esPantalon) {
        const tipoLabel = esJean ? 'JEAN' : 'PANTALÓN';
        tipoJeanPantalon_inline_container.innerHTML = `
            <label style="font-weight: 600; color: #0066cc; font-size: 0.8rem; white-space: nowrap; margin-bottom: 2px; display: block;">
                <i class="fas fa-link"></i> Tipo de ${tipoLabel}
            </label>
            <input type="hidden" class="es-jean-pantalon-hidden" name="productos_friendly[][variantes][es_jean_pantalon]" value="1">
            <select name="productos_friendly[][variantes][tipo_jean_pantalon]" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%; font-size: 0.9rem; height: 36px; box-sizing: border-box; background-color: white; cursor: pointer; font-weight: 500;">
                <option value="" style="color: #999;">Seleccionar...</option>
                <option value="METÁLICO" style="color: #1e293b;">METÁLICO</option>
                <option value="PLÁSTICO" style="color: #1e293b;">PLÁSTICO</option>
                <option value="NO APLICA" style="color: #1e293b;">NO APLICA</option>
            </select>
        `;
        // Asegurar que el contenedor sea visible
        tipoJeanPantalon_inline.style.display = 'flex';
        tipoJeanPantalon_inline.style.visibility = 'visible';
        tipoJeanPantalon_inline.style.opacity = '1';
        console.log(' Selector Tipo de JEAN/PANTALÓN mostrado para:', nombrePrenda);
    } else {
        tipoJeanPantalon_inline.style.display = 'none';
        tipoJeanPantalon_inline.style.visibility = 'hidden';
        tipoJeanPantalon_inline.style.opacity = '0';
        tipoJeanPantalon_inline_container.innerHTML = '';
        console.log(' Selector Tipo de JEAN/PANTALÓN ocultado para:', nombrePrenda);
    }
}

/**
 * Habilitar/Deshabilitar input de MANGA según el checkbox
 */
function toggleMangaInput(checkbox) {
    // Buscar el contenedor más cercano (puede ser tr o div)
    let container = checkbox.closest('tr') || checkbox.closest('td') || checkbox.closest('div');
    
    if (!container) {
        console.warn(' No se encontró contenedor para el checkbox de manga');
        return;
    }
    
    const mangaInput = container.querySelector('.manga-input');
    const mangaIdInput = container.querySelector('.manga-id-input');
    
    if (!mangaInput) {
        console.warn(' No se encontró .manga-input en el contenedor');
        return;
    }
    
    if (checkbox.checked) {
        // Habilitar input
        mangaInput.disabled = false;
        mangaInput.style.opacity = '1';
        mangaInput.style.pointerEvents = 'auto';
    } else {
        // Deshabilitar input
        mangaInput.disabled = true;
        mangaInput.style.opacity = '0.5';
        mangaInput.style.pointerEvents = 'none';
        mangaInput.value = '';
        if (mangaIdInput) {
            mangaIdInput.value = '';
        }
    }
}

/**
 * Crear selector de variantes
 */
function crearSelectorVariantes(inputElement, tipoPrenda, variaciones) {
    const productoCard = inputElement.closest('.producto-card');
    if (!productoCard) return;

    // Eliminar selector anterior si existe
    let selectorExistente = productoCard.querySelector('.variantes-selector');
    if (selectorExistente) {
        selectorExistente.remove();
    }

    // Crear contenedor de variantes
    const selectorHTML = `
        <div class="variantes-selector" style="background: #f0f7ff; border: 2px solid #0066cc; border-radius: 8px; padding: 15px; margin-top: 15px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                <i class="fas fa-check-circle" style="color: #10b981; font-size: 1.2rem;"></i>
                <span style="font-weight: 600; color: #0066cc;">Variaciones de ${tipoPrenda.nombre}</span>
            </div>
            
            <div class="variantes-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                ${variaciones.tiene_manga ? `
                    <div>
                        <label style="font-weight: 600; color: #0066cc; font-size: 0.9rem; display: block; margin-bottom: 6px;">
                            <i class="fas fa-shirt"></i> Manga
                        </label>
                        <select class="variante-select" data-variante="tipo_manga_id" name="productos_prenda[][variantes][tipo_manga_id]" style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px;">
                            <option value="">Seleccionar...</option>
                            <option value="1">Larga</option>
                            <option value="2">Corta</option>
                            <option value="3">3/4</option>
                        </select>
                    </div>
                ` : ''}
                
                ${variaciones.tiene_bolsillos ? `
                    <div>
                        <label style="font-weight: 600; color: #0066cc; font-size: 0.9rem; display: block; margin-bottom: 6px;">
                            <i class="fas fa-square"></i> Bolsillos
                        </label>
                        <select class="variante-select" data-variante="tiene_bolsillos" name="productos_prenda[][variantes][tiene_bolsillos]" style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px;">
                            <option value="">Seleccionar...</option>
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                ` : ''}
                
                ${variaciones.tiene_broche ? `
                    <div>
                        <label style="font-weight: 600; color: #0066cc; font-size: 0.9rem; display: block; margin-bottom: 6px;">
                            <i class="fas fa-link"></i> Broche
                        </label>
                        <select class="variante-select" data-variante="tipo_broche_id" name="productos_prenda[][variantes][tipo_broche_id]" style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px;">
                            <option value="">Seleccionar...</option>
                            <option value="1">Metálico</option>
                            <option value="2">Plástico</option>
                        </select>
                    </div>
                ` : ''}
                
                <div>
                    <label style="font-weight: 600; color: #0066cc; font-size: 0.9rem; display: block; margin-bottom: 6px;">
                        <i class="fas fa-venus-mars"></i> Género
                    </label>
                    <select class="variante-select" data-variante="genero_id" name="productos_prenda[][variantes][genero_id]" style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px;">
                        <option value="">Seleccionar...</option>
                        <option value="1">Dama</option>
                        <option value="2">Caballero</option>
                        <option value="3">Unisex</option>
                    </select>
                </div>
                
                <div>
                    <label style="font-weight: 600; color: #0066cc; font-size: 0.9rem; display: block; margin-bottom: 6px;">
                        <i class="fas fa-palette"></i> Color
                    </label>
                    <select class="variante-select" data-variante="color_id" name="productos_prenda[][variantes][color_id]" style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px;">
                        <option value="">Seleccionar...</option>
                        <option value="1">Azul</option>
                        <option value="2">Negro</option>
                        <option value="3">Gris</option>
                        <option value="4">Blanco</option>
                        <option value="5">Naranja</option>
                        <option value="6">Rojo</option>
                        <option value="7">Verde</option>
                        <option value="8">Amarillo</option>
                    </select>
                </div>
                
                <div>
                    <label style="font-weight: 600; color: #0066cc; font-size: 0.9rem; display: block; margin-bottom: 6px;">
                        <i class="fas fa-cloth"></i> Tela
                    </label>
                    <select class="variante-select" data-variante="tela_id" name="productos_prenda[][variantes][tela_id]" style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px;">
                        <option value="">Seleccionar...</option>
                        <option value="1">NAPOLES (REF-NAP-001)</option>
                        <option value="2">DRILL BORNEO (REF-DB-001)</option>
                        <option value="3">OXFORD (REF-OX-001)</option>
                        <option value="4">JERSEY (REF-JER-001)</option>
                        <option value="5">LINO (REF-LIN-001)</option>
                    </select>
                </div>
                
                ${variaciones.tiene_reflectivo ? `
                    <div>
                        <label style="font-weight: 600; color: #0066cc; font-size: 0.9rem; display: block; margin-bottom: 6px;">
                            <i class="fas fa-star"></i> Reflectivo
                        </label>
                        <select class="variante-select" data-variante="tiene_reflectivo" name="productos_prenda[][variantes][tiene_reflectivo]" style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px;">
                            <option value="">Seleccionar...</option>
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                ` : ''}
            </div>
            
            <input type="hidden" class="tipo-prenda-id" value="${tipoPrenda.id}">
        </div>
    `;

    // Insertar después del input de nombre
    inputElement.parentElement.insertAdjacentHTML('afterend', selectorHTML);
    
    console.log(' Selector de variantes creado para:', tipoPrenda.nombre);
}

/**
 * Datos de variaciones disponibles
 */
const mangasDisponibles = [
    { id: 1, nombre: 'Larga' },
    { id: 2, nombre: 'Corta' },
    { id: 3, nombre: '3/4' }
];

const brochesDisponibles = [
    { id: 1, nombre: 'Broche' },
    { id: 2, nombre: 'Botón' }
];

let proximoMangaId = 4;
let proximoBrocheId = 3;

/**
 * Inicializar la tabla de variantes
 * La tabla ya está en HTML, solo necesitamos inicializar los listeners
 */
function crearSelectorVariantesEnSeccion(inputElement, tipoPrenda, variaciones) {
    // La tabla ya está en HTML, no necesitamos generarla
    // Solo inicializamos los listeners de búsqueda de manga
    const productoCard = inputElement.closest('.producto-card');
    if (!productoCard) return;
    
    // Mostrar el selector de Tipo de JEAN/PANTALÓN si aplica
    const nombrePrenda = tipoPrenda.nombre.toUpperCase();
    const esJean = /JEAN/.test(nombrePrenda);
    const esPantalon = /PANTALÓ?N/.test(nombrePrenda);
    
    const tipoJeanPantalon_inline = productoCard.querySelector('.tipo-jean-pantalon-inline');
    
    if (esJean || esPantalon) {
        const tipoLabel = esJean ? 'JEAN' : 'PANTALÓN';
        const tipoJeanPantalon_inline_container = productoCard.querySelector('.tipo-jean-pantalon-inline-container');
        tipoJeanPantalon_inline_container.innerHTML = `
            <label style="font-weight: 600; color: #0066cc; font-size: 0.8rem; white-space: nowrap; margin-bottom: 2px; display: block;">
                <i class="fas fa-link"></i> Tipo de ${tipoLabel}
            </label>
            <input type="hidden" class="es-jean-pantalon-hidden" name="productos_friendly[][variantes][es_jean_pantalon]" value="1">
            <select name="productos_friendly[][variantes][tipo_jean_pantalon]" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%; font-size: 0.9rem; height: 36px; box-sizing: border-box; background-color: white; cursor: pointer; font-weight: 500;">
                <option value="" style="color: #999;">Seleccionar...</option>
                <option value="METÁLICO" style="color: #1e293b;">METÁLICO</option>
                <option value="PLÁSTICO" style="color: #1e293b;">PLÁSTICO</option>
                <option value="NO APLICA" style="color: #1e293b;">NO APLICA</option>
            </select>
        `;
        tipoJeanPantalon_inline.style.display = 'flex';
        tipoJeanPantalon_inline.style.visibility = 'visible';
        tipoJeanPantalon_inline.style.opacity = '1';
    } else {
        tipoJeanPantalon_inline.style.display = 'none';
    }
    
    console.log(' Tabla de variaciones lista para:', tipoPrenda.nombre);
}

/**
 * Crear selector básico de variantes (para prendas no reconocidas)
 * Muestra los mismos campos pero sin dependencias de tipos específicos
 */
function crearSelectorVariantesBasico(inputElement, nombrePrenda) {
    const productoCard = inputElement.closest('.producto-card');
    if (!productoCard) return;
    
    // Mostrar la sección de variantes
    const variantesSection = productoCard.querySelector('.variantes-section');
    if (variantesSection) {
        variantesSection.style.display = 'block';
    }
    
    // Mostrar selector de JEAN/PANTALÓN si aplica (JEAN/JEANS/PANTALON/PANTALONES)
    const nombreUpper = nombrePrenda.toUpperCase().trim();
    const esJean = /^JEAN/.test(nombreUpper.split(/\s+/)[0]);
    const esPantalon = /^PANTALÓ?N/.test(nombreUpper.split(/\s+/)[0]);
    
    const tipoJeanPantalon_inline = productoCard.querySelector('.tipo-jean-pantalon-inline');
    
    if (esJean || esPantalon) {
        const tipoLabel = esJean ? 'JEAN' : 'PANTALÓN';
        const tipoJeanPantalon_inline_container = productoCard.querySelector('.tipo-jean-pantalon-inline-container');
        tipoJeanPantalon_inline_container.innerHTML = `
            <label style="font-weight: 600; color: #0066cc; font-size: 0.8rem; white-space: nowrap; margin-bottom: 2px; display: block;">
                <i class="fas fa-link"></i> Tipo de ${tipoLabel}
            </label>
            <input type="hidden" class="es-jean-pantalon-hidden" name="productos_friendly[][variantes][es_jean_pantalon]" value="1">
            <select name="productos_friendly[][variantes][tipo_jean_pantalon]" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%; font-size: 0.9rem; height: 36px; box-sizing: border-box; background-color: white; cursor: pointer; font-weight: 500;">
                <option value="" style="color: #999;">Seleccionar...</option>
                <option value="METÁLICO" style="color: #1e293b;">METÁLICO</option>
                <option value="PLÁSTICO" style="color: #1e293b;">PLÁSTICO</option>
                <option value="NO APLICA" style="color: #1e293b;">NO APLICA</option>
            </select>
        `;
        tipoJeanPantalon_inline.style.display = 'flex';
        tipoJeanPantalon_inline.style.visibility = 'visible';
        tipoJeanPantalon_inline.style.opacity = '1';
    } else {
        tipoJeanPantalon_inline.style.display = 'none';
    }
    
    console.log(' Tabla de variaciones básica mostrada para:', nombrePrenda);
}

/**
 * Ocultar selector de variantes
 */
function ocultarSelectorVariantes(inputElement) {
    const productoCard = inputElement.closest('.producto-card');
    if (!productoCard) return;
    
    const variantesSection = productoCard.querySelector('.variantes-section');
    if (variantesSection) {
        variantesSection.style.display = 'none';
    }
}

/**
 * Obtener variantes seleccionadas de una prenda
 */
function obtenerVariantesSeleccionadas(productoCard) {
    const variantesSection = productoCard.querySelector('.variantes-section');
    if (!variantesSection || variantesSection.style.display === 'none') {
        return null;
    }

    const variantes = {};

    // Recopilar valores de todos los selects en la sección de variantes
    variantesSection.querySelectorAll('select').forEach(select => {
        const name = select.getAttribute('name');
        // Extraer el nombre de la variante del atributo name
        // Formato: productos_friendly[][variantes][tipo_manga_id]
        const match = name.match(/\[variantes\]\[(\w+)\]/);
        if (match) {
            const variante = match[1];
            const valor = select.value;
            if (valor) {
                variantes[variante] = valor;
            }
        }
    });

    return Object.keys(variantes).length > 0 ? variantes : null;
}

/**
 * Agregar variantes al formulario antes de enviar
 * Nota: Las variantes ya están en los inputs con los nombres correctos
 * (productos_friendly[][variantes][tipo_manga_id], etc.)
 * Por lo que no es necesario hacer nada adicional
 */
function agregarVariantesAlFormulario() {
    console.log(' Variantes ya están en los inputs del formulario');
}

/**
 * Hook para interceptar el envío del formulario
 */
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCrearPedidoFriendly');
    if (form) {
        // Interceptar cuando se hace click en botones que envían el formulario
        document.querySelectorAll('[onclick*="guardarCotizacion"], [onclick*="enviarCotizacion"]').forEach(btn => {
            const onclickOriginal = btn.getAttribute('onclick');
            btn.setAttribute('onclick', 'agregarVariantesAlFormulario(); ' + onclickOriginal);
        });
    }
});

// Inicializar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', inicializarVariantes);

/**
 * BÚSQUEDA Y CREACIÓN DE MANGA
 */
function buscarManga(input) {
    const valor = input.value.toLowerCase().trim();
    const suggestionsDiv = input.closest('div').querySelector('.manga-suggestions');
    
    if (!valor) {
        suggestionsDiv.style.display = 'none';
        return;
    }
    
    const coincidencias = mangasDisponibles.filter(m => 
        m.nombre.toLowerCase().includes(valor)
    );
    
    let html = '';
    
    if (coincidencias.length > 0) {
        html += coincidencias.map(m => `
            <div style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;" 
                 onmouseover="this.style.backgroundColor='#f0f0f0'" 
                 onmouseout="this.style.backgroundColor='white'"
                 onclick="seleccionarManga('${m.id}', '${m.nombre}', this)">
                <strong>${m.nombre}</strong>
            </div>
        `).join('');
    }
    
    const valorLimpio = input.value.trim();
    html += `
        <div style="padding: 8px 12px; cursor: pointer; border-top: 1px solid #0066cc; background-color: #e6f2ff;" 
             onmouseover="this.style.backgroundColor='#cce5ff'" 
             onmouseout="this.style.backgroundColor='#e6f2ff'"
             onclick="crearMangaDesdeSelector('${valorLimpio}', this)">
            <i class="fas fa-plus"></i> <strong>Crear: "${valorLimpio}"</strong>
        </div>
    `;
    
    suggestionsDiv.innerHTML = html;
    suggestionsDiv.style.display = 'block';
}

function seleccionarManga(id, nombre, element) {
    const td = element.closest('td');
    const input = td.querySelector('.manga-input');
    const idInput = td.querySelector('.manga-id-input');
    
    console.log(` DEBUG seleccionarManga:`, {
        id: id,
        nombre: nombre,
        td_encontrado: !!td,
        input_encontrado: !!input,
        idInput_encontrado: !!idInput,
        idInput_antes: idInput?.value
    });
    
    input.value = nombre;
    idInput.value = id;
    
    console.log(` DEBUG después de asignar:`, {
        input_value: input.value,
        idInput_value: idInput.value
    });
    
    td.querySelector('.manga-suggestions').style.display = 'none';
    
    console.log(` Manga seleccionada: ${nombre} (ID: ${id})`);
}

function crearMangaDesdeInput(input) {
    const td = input.closest('td');
    const valor = input.value.trim();
    
    if (!valor) return;
    
    const existe = mangasDisponibles.find(m => 
        m.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarManga(existe.id, existe.nombre, input);
    } else {
        const nuevoId = proximoMangaId++;
        const nuevaManga = { id: nuevoId, nombre: valor };
        mangasDisponibles.push(nuevaManga);
        
        const idInput = td.querySelector('.manga-id-input');
        idInput.value = nuevoId;
        td.querySelector('.manga-suggestions').style.display = 'none';
        
        console.log(` Nueva manga creada: ${valor} (ID: ${nuevoId})`);
    }
}

function crearMangaDesdeSelector(valor, element) {
    const td = element.closest('td');
    const input = td.querySelector('.manga-input');
    const idInput = td.querySelector('.manga-id-input');
    
    const existe = mangasDisponibles.find(m => 
        m.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarManga(existe.id, existe.nombre, element);
    } else {
        const nuevoId = proximoMangaId++;
        const nuevaManga = { id: nuevoId, nombre: valor };
        mangasDisponibles.push(nuevaManga);
        
        input.value = valor;
        idInput.value = nuevoId;
        td.querySelector('.manga-suggestions').style.display = 'none';
        
        console.log(` Nueva manga creada desde selector: ${valor} (ID: ${nuevoId})`);
    }
}

/**
 * BÚSQUEDA Y CREACIÓN DE BROCHE
 */
function buscarBroche(input) {
    const valor = input.value.toLowerCase().trim();
    const suggestionsDiv = input.closest('div').querySelector('.broche-suggestions');
    
    if (!valor) {
        suggestionsDiv.style.display = 'none';
        return;
    }
    
    const coincidencias = brochesDisponibles.filter(b => 
        b.nombre.toLowerCase().includes(valor)
    );
    
    let html = '';
    
    if (coincidencias.length > 0) {
        html += coincidencias.map(b => `
            <div style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;" 
                 onmouseover="this.style.backgroundColor='#f0f0f0'" 
                 onmouseout="this.style.backgroundColor='white'"
                 onclick="seleccionarBroche('${b.id}', '${b.nombre}', this)">
                <strong>${b.nombre}</strong>
            </div>
        `).join('');
    }
    
    const valorLimpio = input.value.trim();
    html += `
        <div style="padding: 8px 12px; cursor: pointer; border-top: 1px solid #0066cc; background-color: #e6f2ff;" 
             onmouseover="this.style.backgroundColor='#cce5ff'" 
             onmouseout="this.style.backgroundColor='#e6f2ff'"
             onclick="crearBrocheDesdeSelector('${valorLimpio}', this)">
            <i class="fas fa-plus"></i> <strong>Crear: "${valorLimpio}"</strong>
        </div>
    `;
    
    suggestionsDiv.innerHTML = html;
    suggestionsDiv.style.display = 'block';
}

function seleccionarBroche(id, nombre, element) {
    const div = element.closest('div[style*="position: relative"]');
    const input = div.querySelector('.broche-input');
    const idInput = div.querySelector('.broche-id-input');
    
    input.value = nombre;
    idInput.value = id;
    div.querySelector('.broche-suggestions').style.display = 'none';
    
    console.log(` Broche seleccionado: ${nombre} (ID: ${id})`);
}

function crearBrocheDesdeInput(input) {
    const div = input.closest('div[style*="position: relative"]');
    const valor = input.value.trim();
    
    if (!valor) return;
    
    const existe = brochesDisponibles.find(b => 
        b.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarBroche(existe.id, existe.nombre, input);
    } else {
        const nuevoId = proximoBrocheId++;
        const nuevoBroche = { id: nuevoId, nombre: valor };
        brochesDisponibles.push(nuevoBroche);
        
        const idInput = div.querySelector('.broche-id-input');
        idInput.value = nuevoId;
        div.querySelector('.broche-suggestions').style.display = 'none';
        
        console.log(` Nuevo broche creado: ${valor} (ID: ${nuevoId})`);
    }
}

function crearBrocheDesdeSelector(valor, element) {
    const div = element.closest('div[style*="position: relative"]');
    const input = div.querySelector('.broche-input');
    const idInput = div.querySelector('.broche-id-input');
    
    const existe = brochesDisponibles.find(b => 
        b.nombre.toLowerCase() === valor.toLowerCase()
    );
    
    if (existe) {
        seleccionarBroche(existe.id, existe.nombre, element);
    } else {
        const nuevoId = proximoBrocheId++;
        const nuevoBroche = { id: nuevoId, nombre: valor };
        brochesDisponibles.push(nuevoBroche);
        
        input.value = valor;
        idInput.value = nuevoId;
        div.querySelector('.broche-suggestions').style.display = 'none';
        
        console.log(` Nuevo broche creado desde selector: ${valor} (ID: ${nuevoId})`);
    }
}

/**
 * Cerrar sugerencias al hacer click fuera
 */
document.addEventListener('click', function(e) {
    if (!e.target.closest('div[style*="position: relative"]')) {
        document.querySelectorAll('.manga-suggestions, .broche-suggestions').forEach(div => {
            div.style.display = 'none';
        });
    }
});

