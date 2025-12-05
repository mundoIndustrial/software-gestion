/**
 * SISTEMA DE COTIZACIONES - ORQUESTACIÓN E INICIALIZACIÓN
 * Responsabilidad: Inicializar el sistema, gestionar el ciclo de vida
 */

// Variables globales
window.imagenesEnMemoria = { 
    prenda: [], 
    tela: [], 
    logo: [],
    prendaConIndice: [],  // Fotos de prendas con índice
    telaConIndice: []     // Fotos de telas con índice
};
window.especificacionesSeleccionadas = {};

console.log('🔵 Sistema de cotizaciones inicializado');
console.log('📸 imagenesEnMemoria inicializado:', window.imagenesEnMemoria);

// ============ INICIALIZACIÓN ============

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM cargado - Inicializando cotizaciones');
    
    // Ocultar navbar
    const topNav = document.querySelector('.top-nav');
    if (topNav) topNav.style.display = 'none';
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) pageHeader.style.display = 'none';
    
    // Inicializar funciones
    cargarDatosDelBorrador();
    mostrarFechaActual();
    configurarDragAndDrop();
});

window.addEventListener('beforeunload', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) topNav.style.display = '';
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) pageHeader.style.display = '';
});

// ============ CONVERTIR IMÁGENES A BASE64 ============

/**
 * Convertir un File object a Data URL (Base64)
 */
function convertirArchivoABase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            console.log(`✓ Archivo convertido a Base64:`, file.name, `(${(reader.result.length / 1024).toFixed(2)} KB)`);
            resolve({
                nombre: file.name,
                base64: reader.result,
                tipo: file.type,
                size: file.size
            });
        };
        reader.onerror = (error) => {
            console.error('❌ Error al leer archivo:', file.name, error);
            reject(error);
        };
        reader.readAsDataURL(file);
    });
}

/**
 * Convertir todas las imágenes de un producto a Base64
 */
async function convertirImagenesProducto(producto) {
    console.log(`📸 Convirtiendo imágenes del producto: ${producto.nombre_producto}`);
    
    // Convertir fotos de prenda
    if (producto.fotos && producto.fotos.length > 0) {
        try {
            producto.fotos_base64 = await Promise.all(
                producto.fotos.map(foto => convertirArchivoABase64(foto))
            );
            console.log(`✓ ${producto.fotos_base64.length} fotos de prenda convertidas`);
        } catch (error) {
            console.error('❌ Error al convertir fotos de prenda:', error);
            producto.fotos_base64 = [];
        }
    } else {
        producto.fotos_base64 = [];
    }
    
    // Convertir telas
    if (producto.telas && producto.telas.length > 0) {
        try {
            producto.telas_base64 = await Promise.all(
                producto.telas.map(tela => convertirArchivoABase64(tela))
            );
            console.log(`✓ ${producto.telas_base64.length} telas convertidas`);
        } catch (error) {
            console.error('❌ Error al convertir telas:', error);
            producto.telas_base64 = [];
        }
    } else {
        producto.telas_base64 = [];
    }
    
    // Eliminar los File objects originales (no se pueden serializar en JSON)
    delete producto.fotos;
    delete producto.telas;
    
    return producto;
}

// ============ NAVEGACIÓN ============

function irAlPaso(paso) {
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    const formStep = document.querySelector(`.form-step[data-step="${paso}"]`);
    if (formStep) formStep.classList.add('active');
    
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    const stepElement = document.querySelector(`.step[data-step="${paso}"]`);
    if (stepElement) stepElement.classList.add('active');
    
    if (paso === 4) setTimeout(() => actualizarResumenFriendly(), 100);
}

// ============ UTILIDADES ============

function mostrarFechaActual() {
    const el = document.getElementById('fechaActual');
    if (el) {
        const hoy = new Date();
        el.textContent = hoy.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
    }
}

function actualizarResumenFriendly() {
    const cliente = document.getElementById('cliente');
    if (document.getElementById('resumenCliente')) {
        document.getElementById('resumenCliente').textContent = cliente ? cliente.value || '-' : '-';
    }
    if (document.getElementById('resumenProductos')) {
        document.getElementById('resumenProductos').textContent = document.querySelectorAll('.producto-card').length;
    }
    if (document.getElementById('resumenFecha')) {
        const hoy = new Date();
        document.getElementById('resumenFecha').textContent = hoy.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
    }
}

function cargarDatosDelBorrador() {
    // Implementar si es necesario cargar datos de un borrador existente
}

function recopilarDatos() {
    const cliente = document.getElementById('cliente');
    if (!cliente) {
        console.error('❌ Campo cliente no encontrado');
        return null;
    }
    
    const clienteValue = cliente.value;
    const productos = [];
    
    console.log('📦 Total de prendas encontradas:', document.querySelectorAll('.producto-card').length);
    
    document.querySelectorAll('.producto-card').forEach((item, index) => {
        console.log(`📦 Procesando prenda ${index + 1}...`);
        const nombre = item.querySelector('input[name*="nombre_producto"]')?.value || '';
        const descripcion = item.querySelector('textarea[name*="descripcion"]')?.value || '';
        const cantidad = item.querySelector('input[name*="cantidad"]')?.value || 1;
        
        // Obtener tallas seleccionadas (desde botones activos)
        const tallasSeleccionadas = [];
        
        // Buscar tallas en el campo hidden que se actualiza con agregarTallasSeleccionadas()
        const tallasHidden = item.querySelector('input[name*="tallas"][type="hidden"]');
        if (tallasHidden && tallasHidden.value) {
            // Las tallas están separadas por comas en el campo hidden
            tallasSeleccionadas.push(...tallasHidden.value.split(', ').filter(t => t.trim()));
        }
        
        // Alternativa: buscar botones activos directamente
        if (tallasSeleccionadas.length === 0) {
            item.querySelectorAll('.talla-btn.activo').forEach(btn => {
                tallasSeleccionadas.push(btn.dataset.talla);
            });
        }
        
        // Obtener fotos de esta prenda
        const productoId = item.dataset.productoId;
        
        // Opción 1: Desde fotosSeleccionadas (archivos File objects)
        let fotos = [];
        if (fotosSeleccionadas && fotosSeleccionadas[productoId]) {
            // Guardar los archivos File completos, NO solo el nombre
            fotos = fotosSeleccionadas[productoId];
            console.log(`📸 Fotos desde fotosSeleccionadas[${productoId}]:`, fotos.length, 'archivos');
        }
        
        // Opción 2: Desde window.imagenesEnMemoria.prendaConIndice (con índice de prenda)
        let fotosConIndice = [];
        if (window.imagenesEnMemoria && window.imagenesEnMemoria.prendaConIndice) {
            fotosConIndice = window.imagenesEnMemoria.prendaConIndice.filter(p => p.prendaIndex === index);
            console.log(`📸 Fotos desde prendaConIndice (índice ${index}):`, fotosConIndice.length);
            
            // Si hay fotos con índice, usarlas en lugar de fotosSeleccionadas
            if (fotosConIndice.length > 0) {
                fotos = fotosConIndice.map(p => p.file);
                console.log(`📸 Usando fotos de prendaConIndice:`, fotos.length, 'archivos');
            }
        }
        
        // Obtener telas de esta prenda (desde telaConIndice) - TODAS las telas, no solo 1
        let telas = [];
        if (window.imagenesEnMemoria && window.imagenesEnMemoria.telaConIndice) {
            const telasEncontradas = window.imagenesEnMemoria.telaConIndice.filter(t => t.prendaIndex === index);
            if (telasEncontradas.length > 0) {
                // Guardar los archivos File completos
                telas = telasEncontradas.map(t => t.file);
                console.log(`🧵 Telas desde telaConIndice (índice ${index}):`, telas.length, 'archivos');
            }
        }
        
        console.log('📋 Recopilando prenda:', {
            nombre: nombre,
            tallas: tallasSeleccionadas,
            fotos_desde_fotosSeleccionadas: fotos,
            fotos_desde_prendaConIndice: fotosConIndice.length,
            telas: telas,
            productoId: productoId,
            prendaIndex: index
        });
        
        // Capturar variaciones (color, tela, manga, reflectivo, etc.)
        const variantes = {};
        const observacionesVariantes = [];
        
        // Género
        const generoSelect = item.querySelector('.talla-genero-select');
        if (generoSelect && generoSelect.value) {
            variantes.genero = generoSelect.value;
        }
        
        // Color
        const colorInput = item.querySelector('.color-input');
        if (colorInput && colorInput.value) {
            variantes.color = colorInput.value;
        }
        
        // Tela
        const telaInput = item.querySelector('.tela-input');
        if (telaInput && telaInput.value) {
            variantes.tela = telaInput.value;
        }
        
        // Referencia de tela
        const referenciaInput = item.querySelector('.referencia-input');
        if (referenciaInput && referenciaInput.value) {
            variantes.referencia = referenciaInput.value;
        }
        
        // Manga - SOLO SI ESTÁ CHECKED
        const mangaCheckbox = item.querySelector('input[name*="aplica_manga"]');
        if (mangaCheckbox && mangaCheckbox.checked) {
            // Buscar el select de manga (contiene el valor tipo_manga)
            const mangaSelect = item.querySelector('select[name*="tipo_manga"]');
            
            console.log('🔍 Buscando manga:', {
                checkbox_checked: mangaCheckbox.checked,
                mangaSelect_encontrado: !!mangaSelect,
                mangaSelect_value: mangaSelect?.value
            });
            
            // Guardar el tipo de manga (CORTA, LARGA, 3/4, etc.)
            if (mangaSelect && mangaSelect.value) {
                variantes.tipo_manga_id = mangaSelect.value;
                console.log('✅ tipo_manga_id capturado:', mangaSelect.value);
            }
            
            // Capturar observación de manga SOLO SI CHECKBOX ESTÁ CHECKED
            const mangaObs = item.querySelector('input[name*="obs_manga"]');
            if (mangaObs && mangaObs.value) {
                variantes.obs_manga = mangaObs.value;
                observacionesVariantes.push(`Manga: ${mangaObs.value}`);
                console.log('✅ obs_manga capturada:', mangaObs.value);
            }
        } else {
            console.log('ℹ️ Manga NO seleccionado - obs_manga NO se captura');
            variantes.tipo_manga_id = null;
        }
        
        // Bolsillos - SOLO SI ESTÁ CHECKED
        const bolsillosCheckbox = item.querySelector('input[name*="aplica_bolsillos"]');
        if (bolsillosCheckbox && bolsillosCheckbox.checked) {
            variantes.tiene_bolsillos = true;
            // Capturar observación de bolsillos SOLO SI CHECKBOX ESTÁ CHECKED
            const bolsillosObs = item.querySelector('input[name*="obs_bolsillos"]');
            if (bolsillosObs && bolsillosObs.value) {
                variantes.obs_bolsillos = bolsillosObs.value;
                observacionesVariantes.push(`Bolsillos: ${bolsillosObs.value}`);
                console.log('✅ obs_bolsillos capturada:', bolsillosObs.value);
            }
            console.log('✅ Bolsillos SELECCIONADO');
        } else {
            variantes.tiene_bolsillos = false;
            console.log('ℹ️ Bolsillos NO seleccionado - obs_bolsillos NO se captura');
        }
        
        // Broche/Botón - SOLO SI ESTÁ CHECKED
        const brocheCheckbox = item.querySelector('input[name*="aplica_broche"]');
        if (brocheCheckbox && brocheCheckbox.checked) {
            const brocheSelect = item.querySelector('select[name*="tipo_broche_id"]');
            
            console.log('🔍 Buscando broche:', {
                checkbox_checked: brocheCheckbox.checked,
                brocheSelect_encontrado: !!brocheSelect,
                brocheSelect_value: brocheSelect?.value,
                brocheSelect_text: brocheSelect?.options[brocheSelect?.selectedIndex]?.text
            });
            
            // Guardar el tipo_broche_id (1 para Broche, 2 para Botón)
            if (brocheSelect && brocheSelect.value) {
                variantes.tipo_broche_id = brocheSelect.value;
                console.log('✅ tipo_broche_id capturado:', brocheSelect.value);
            }
            
            // Capturar observación de broche SOLO SI CHECKBOX ESTÁ CHECKED
            const brocheObs = item.querySelector('input[name*="obs_broche"]');
            if (brocheObs && brocheObs.value) {
                variantes.obs_broche = brocheObs.value;
                observacionesVariantes.push(`Broche: ${brocheObs.value}`);
                console.log('✅ obs_broche capturada:', brocheObs.value);
            }
        } else {
            console.log('ℹ️ Broche NO seleccionado - obs_broche NO se captura');
            variantes.tipo_broche_id = null;
        }
        
        // Reflectivo - SOLO SI ESTÁ CHECKED
        const reflectivoCheckbox = item.querySelector('input[name*="aplica_reflectivo"]');
        if (reflectivoCheckbox && reflectivoCheckbox.checked) {
            variantes.tiene_reflectivo = true;
            // Capturar observación de reflectivo SOLO SI CHECKBOX ESTÁ CHECKED
            const reflectivoObs = item.querySelector('input[name*="obs_reflectivo"]');
            if (reflectivoObs && reflectivoObs.value) {
                variantes.obs_reflectivo = reflectivoObs.value;
                observacionesVariantes.push(`Reflectivo: ${reflectivoObs.value}`);
                console.log('✅ obs_reflectivo capturada:', reflectivoObs.value);
            }
            console.log('✅ Reflectivo SELECCIONADO');
        } else {
            variantes.tiene_reflectivo = false;
            console.log('ℹ️ Reflectivo NO seleccionado - obs_reflectivo NO se captura');
        }
        
        // Agregar todas las observaciones como descripción_adicional
        if (observacionesVariantes.length > 0) {
            variantes.descripcion_adicional = observacionesVariantes.join(' | ');
            console.log('📝 descripcion_adicional construida:', {
                observacionesCount: observacionesVariantes.length,
                observaciones: observacionesVariantes,
                descripcion_adicional: variantes.descripcion_adicional
            });
        } else {
            console.log('ℹ️ Sin observaciones de variantes para agregar a descripcion_adicional');
        }
        
        console.log('📝 RESUMEN VARIANTES CAPTURADAS:', {
            '✅ Color': variantes.color || '(vacío)',
            '✅ Tela': variantes.tela || '(vacío)',
            '✅ Referencia': variantes.referencia || '(vacío)',
            '✅ Género': variantes.genero || '(vacío)',
            '🎽 Tipo Manga ID': variantes.tipo_manga_id || '(NO CAPTURADO)',
            '🎽 Manga Nombre': variantes.manga_nombre || '(NO CAPTURADO)',
            '🎽 Obs Manga': variantes.obs_manga || '(vacío)',
            '👖 Tiene Bolsillos': variantes.tiene_bolsillos || false,
            '👖 Obs Bolsillos': variantes.obs_bolsillos || '(vacío)',
            '🔗 Tipo Broche ID': variantes.tipo_broche_id || '(vacío)',
            '🔗 Obs Broche': variantes.obs_broche || '(vacío)',
            '⭐ Tiene Reflectivo': variantes.tiene_reflectivo || false,
            '⭐ Obs Reflectivo': variantes.obs_reflectivo || '(vacío)',
            '📝 Descripción Adicional': variantes.descripcion_adicional || '(vacío)',
            'Todas las keys': Object.keys(variantes)
        });
        
        if (nombre.trim()) {
            const producto = {
                nombre_producto: nombre,
                descripcion: descripcion,
                cantidad: parseInt(cantidad) || 1,
                tallas: tallasSeleccionadas,
                fotos: fotos,
                telas: telas,
                variantes: variantes
            };
            
            console.log('✅ PRODUCTO AGREGADO:', {
                nombre: nombre,
                tallas: tallasSeleccionadas.length,
                fotos: fotos.length,
                telas: telas.length,
                variantes_keys: Object.keys(variantes)
            });
            
            productos.push(producto);
        }
    });
    
    console.log('📦 RESUMEN PRODUCTOS RECOPILADOS:');
    productos.forEach((prod, idx) => {
        console.log(`  [${idx + 1}] ${prod.nombre_producto}:`, {
            '📸 Fotos': prod.fotos.length,
            '🧵 Telas': prod.telas.length,
            '📏 Tallas': prod.tallas.length,
            '🎨 Variantes': Object.keys(prod.variantes).length
        });
    });
    
    // Verificar imágenes en memoria
    console.log('📸 IMÁGENES EN MEMORIA:', {
        'prendaConIndice': window.imagenesEnMemoria?.prendaConIndice?.length || 0,
        'telaConIndice': window.imagenesEnMemoria?.telaConIndice?.length || 0,
        'logo': window.imagenesEnMemoria?.logo?.length || 0
    });
    
    // ========== PASO 3: LOGO ==========
    
    // Recopilar técnicas
    const contenedorTecnicas = document.getElementById('tecnicas_seleccionadas');
    console.log('🎨 Contenedor técnicas encontrado:', !!contenedorTecnicas);
    if (contenedorTecnicas) {
        console.log('🎨 innerHTML del contenedor:', contenedorTecnicas.innerHTML);
        console.log('🎨 Número de children:', contenedorTecnicas.children.length);
    }
    
    const tecnicas = [];
    document.querySelectorAll('#tecnicas_seleccionadas > div').forEach(tag => {
        const input = tag.querySelector('input[name="tecnicas[]"]');
        if (input) {
            console.log('🎨 Input encontrado:', input.value);
            tecnicas.push(input.value);
        }
    });
    console.log('🎨 Técnicas recopiladas:', tecnicas);
    console.log('🎨 Elementos encontrados:', document.querySelectorAll('#tecnicas_seleccionadas > div').length);
    
    // Recopilar observaciones técnicas
    const observaciones_tecnicas = document.getElementById('observaciones_tecnicas')?.value || '';
    console.log('📝 Observaciones técnicas:', observaciones_tecnicas);
    
    // Recopilar ubicaciones por sección (solo las que estén checked)
    const ubicaciones = [];
    const seccionesAgregadas = {};
    
    document.querySelectorAll('#secciones_agregadas > div').forEach(seccionDiv => {
        const seccionInput = seccionDiv.querySelector('input[name="ubicaciones_seccion[]"]');
        if (seccionInput) {
            const seccion = seccionInput.value;
            
            if (!seccionesAgregadas[seccion]) {
                seccionesAgregadas[seccion] = {
                    ubicaciones: [],
                    observaciones: ''
                };
            }
            
            // Obtener todas las ubicaciones checked de esta sección
            seccionDiv.querySelectorAll('input[name="ubicaciones_check[]"]').forEach((checkbox) => {
                if (checkbox.checked) {
                    const ubicacionInput = checkbox.closest('tr').querySelector('input[name="ubicaciones[]"]');
                    if (ubicacionInput) {
                        seccionesAgregadas[seccion].ubicaciones.push(ubicacionInput.value.trim());
                    }
                }
            });
            
            // Obtener observaciones de esta sección
            const obsInput = seccionDiv.querySelector('input[name="ubicaciones_observaciones[]"]');
            if (obsInput) {
                seccionesAgregadas[seccion].observaciones = obsInput.value.trim();
            }
        }
    });
    
    // Convertir a array de objetos
    Object.keys(seccionesAgregadas).forEach(seccion => {
        if (seccionesAgregadas[seccion].ubicaciones.length > 0) {
            ubicaciones.push({
                seccion: seccion,
                ubicaciones_seleccionadas: seccionesAgregadas[seccion].ubicaciones,
                observaciones: seccionesAgregadas[seccion].observaciones
            });
        }
    });
    
    console.log('📍 Ubicaciones recopiladas:', ubicaciones);
    
    // Recopilar observaciones generales CON TIPO Y VALOR
    const observaciones_generales = [];
    const observaciones_check = [];
    const observaciones_valor = [];
    
    document.querySelectorAll('#observaciones_lista > div').forEach(obs => {
        const textoInput = obs.querySelector('input[name="observaciones_generales[]"]');
        const checkboxInput = obs.querySelector('input[name="observaciones_check[]"]');
        const valorInput = obs.querySelector('input[name="observaciones_valor[]"]');
        const textModeDiv = obs.querySelector('.obs-text-mode');
        
        const texto = textoInput?.value || '';
        
        if (texto.trim()) {
            observaciones_generales.push(texto);
            
            // Verificar si está en modo texto (si el div de texto está visible)
            const esModoTexto = textModeDiv && textModeDiv.style.display !== 'none';
            
            if (esModoTexto) {
                // Modo texto: no hay checkbox, guardar el valor
                observaciones_check.push(null);
                observaciones_valor.push(valorInput?.value || '');
                console.log('📝 Modo TEXTO:', texto, '=', valorInput?.value);
            } else {
                // Modo checkbox: guardar si está checked
                observaciones_check.push(checkboxInput?.checked ? 'on' : null);
                observaciones_valor.push('');
                console.log('✓ Modo CHECK:', texto, '=', checkboxInput?.checked ? 'checked' : 'unchecked');
            }
        }
    });
    console.log('💬 Observaciones generales recopiladas:', observaciones_generales);
    console.log('✓ Observaciones check:', observaciones_check);
    console.log('📝 Observaciones valor:', observaciones_valor);
    
    return { 
        cliente: clienteValue, 
        productos, 
        tecnicas, 
        observaciones_tecnicas,
        ubicaciones,
        observaciones_generales,
        observaciones_check,
        observaciones_valor,
        especificaciones: window.especificacionesSeleccionadas || {}
    };
}

/**
 * Procesar imágenes del formulario y convertirlas a Base64
 * Retorna una promesa con el data actualizado
 */
async function procesarImagenesABase64(datos) {
    console.log('🖼️ Iniciando procesamiento de imágenes a Base64...');
    
    if (!datos.productos || datos.productos.length === 0) {
        console.log('✓ Sin productos a procesar');
        return datos;
    }
    
    try {
        // Procesar cada producto
        for (let i = 0; i < datos.productos.length; i++) {
            const producto = datos.productos[i];
            console.log(`📦 Procesando producto ${i + 1}/${datos.productos.length}: ${producto.nombre_producto}`);
            
            // Procesar fotos de prenda
            if (producto.fotos && producto.fotos.length > 0) {
                console.log(`  📸 Convirtiendo ${producto.fotos.length} foto(s) de prenda...`);
                producto.fotos_base64 = await Promise.all(
                    producto.fotos.map((foto, idx) => {
                        console.log(`    [${idx + 1}/${producto.fotos.length}] Procesando foto prenda...`);
                        return convertirArchivoABase64(foto);
                    })
                );
                console.log(`  ✅ ${producto.fotos_base64.length} foto(s) de prenda procesadas`);
            } else {
                producto.fotos_base64 = [];
            }
            
            // Procesar telas
            if (producto.telas && producto.telas.length > 0) {
                console.log(`  🧵 Convirtiendo ${producto.telas.length} tela(s)...`);
                producto.telas_base64 = await Promise.all(
                    producto.telas.map((tela, idx) => {
                        console.log(`    [${idx + 1}/${producto.telas.length}] Procesando tela...`);
                        return convertirArchivoABase64(tela);
                    })
                );
                console.log(`  ✅ ${producto.telas_base64.length} tela(s) procesada(s)`);
            } else {
                producto.telas_base64 = [];
            }
            
            // Eliminar los File objects (no se pueden serializar en JSON)
            delete producto.fotos;
            delete producto.telas;
        }
        
        console.log('✅ TODAS LAS IMÁGENES PROCESADAS', {
            'productos': datos.productos.length,
            'fotos_procesadas': datos.productos.reduce((sum, p) => sum + (p.fotos_base64?.length || 0), 0),
            'telas_procesadas': datos.productos.reduce((sum, p) => sum + (p.telas_base64?.length || 0), 0)
        });
        
        return datos;
    } catch (error) {
        console.error('❌ Error al procesar imágenes:', error);
        throw error;
    }
}
