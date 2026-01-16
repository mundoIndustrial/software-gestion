/**
 * Preview de Factura en Tiempo Real
 * Captura datos del formulario de creación de pedido y los muestra en la factura
 * sin necesidad de guardar en la base de datos
 */

console.log('📄 [INVOICE PREVIEW] Cargando invoice-preview-live.js');

/**
 * Almacenamiento global de galerías de imágenes para el preview
 * Esto evita tener que serializar arrays de blob URLs en el onclick
 */
window._galeríasPreview = {};
window._idGaleriaPreview = 0;

/**
 * Registra una galería de imágenes y retorna un ID único
 */
window._registrarGalería = function(imagenes, titulo) {
    if (!Array.isArray(imagenes) || imagenes.length === 0) return null;
    
    const id = window._idGaleriaPreview++;
    window._galeríasPreview[id] = { imagenes, titulo };
    
    console.log(`🖼️ Galería registrada con ID: ${id}, título: "${titulo}", imágenes: ${imagenes.length}`);
    return id;
};

/**
 * Abre una galería usando su ID registrado
 */
window._abrirGaleriaImagenesDesdeID = function(galeriaId) {
    if (galeriaId === null || galeriaId === undefined || !window._galeríasPreview[galeriaId]) {
        console.warn(`⚠️ Galería ID ${galeriaId} no encontrada`);
        return;
    }
    
    const { imagenes, titulo } = window._galeríasPreview[galeriaId];
    window._abrirGaleriaImagenes(imagenes, titulo);
};

/**
 * Abre una galería de imágenes en un modal
 */
window._abrirGaleriaImagenes = function(imagenes, titulo = 'Galería') {
    if (!Array.isArray(imagenes) || imagenes.length === 0) return;
    
    // Crear modal de galería
    const modalGaleria = document.createElement('div');
    modalGaleria.id = 'galeria-imagenes-modal';
    modalGaleria.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        z-index: 10002;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    let indiceActual = 0;
    
    const contenido = document.createElement('div');
    contenido.style.cssText = `
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        align-items: center;
    `;
    
    const imagen = document.createElement('img');
    imagen.src = imagenes[indiceActual];
    imagen.style.cssText = `
        max-width: 100%;
        max-height: 75vh;
        object-fit: contain;
        border-radius: 4px;
    `;
    
    const navegacion = document.createElement('div');
    navegacion.style.cssText = `
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 10px;
        color: white;
        font-size: 12px;
    `;
    
    const btnAnterior = document.createElement('button');
    btnAnterior.textContent = '← Anterior';
    btnAnterior.style.cssText = `
        padding: 8px 12px;
        background: #27ae60;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-weight: 600;
    `;
    btnAnterior.onclick = () => {
        indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
        imagen.src = imagenes[indiceActual];
        contador.textContent = `${indiceActual + 1} / ${imagenes.length}`;
    };
    
    const contador = document.createElement('span');
    contador.textContent = `${indiceActual + 1} / ${imagenes.length}`;
    
    const btnSiguiente = document.createElement('button');
    btnSiguiente.textContent = 'Siguiente →';
    btnSiguiente.style.cssText = `
        padding: 8px 12px;
        background: #27ae60;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-weight: 600;
    `;
    btnSiguiente.onclick = () => {
        indiceActual = (indiceActual + 1) % imagenes.length;
        imagen.src = imagenes[indiceActual];
        contador.textContent = `${indiceActual + 1} / ${imagenes.length}`;
    };
    
    const btnCerrar = document.createElement('button');
    btnCerrar.textContent = '✕ Cerrar';
    btnCerrar.style.cssText = `
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 8px 12px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-weight: 600;
        z-index: 10003;
    `;
    btnCerrar.onclick = () => modalGaleria.remove();
    
    const titulo_elem = document.createElement('div');
    titulo_elem.textContent = titulo;
    titulo_elem.style.cssText = `
        color: white;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 10px;
    `;
    
    navegacion.appendChild(btnAnterior);
    navegacion.appendChild(contador);
    navegacion.appendChild(btnSiguiente);
    
    contenido.appendChild(btnCerrar);
    contenido.appendChild(titulo_elem);
    contenido.appendChild(imagen);
    contenido.appendChild(navegacion);
    
    modalGaleria.appendChild(contenido);
    
    // Cerrar al clickear en el fondo
    modalGaleria.addEventListener('click', (e) => {
        if (e.target === modalGaleria) {
            modalGaleria.remove();
        }
    });
    
    // Soporte para teclado
    const manejadorTeclado = (e) => {
        if (document.getElementById('galeria-imagenes-modal')) {
            if (e.key === 'ArrowLeft') btnAnterior.click();
            if (e.key === 'ArrowRight') btnSiguiente.click();
            if (e.key === 'Escape') {
                document.removeEventListener('keydown', manejadorTeclado);
                modalGaleria.remove();
            }
        }
    };
    document.addEventListener('keydown', manejadorTeclado);
    
    document.body.appendChild(modalGaleria);
};


/**
 * Abre una vista previa en vivo de la factura con datos del formulario actual
 */
window.abrirPreviewFacturaEnVivo = function() {
    console.log('👁️ [PREVIEW] Abriendo vista previa en vivo de factura');
    
    // Capturar datos del formulario
    const datosFormulario = capturarDatosFormulario();
    
    if (!datosFormulario) {
        alert('Por favor completa los datos básicos del pedido');
        return;
    }
    
    console.log('📋 [PREVIEW] Datos capturados:', datosFormulario);
    
    // Crear modal con la vista previa
    crearModalPreviewFactura(datosFormulario);
};

/**
 * Captura los datos del formulario de creación de pedido
 */
function capturarDatosFormulario() {
    console.log('📝 [PREVIEW] Capturando datos del formulario...');
    
    // PRIMERO: Asegurar que las tallas estén en la variable de backup permanente
    if (window.cantidadesTallas && Object.keys(window.cantidadesTallas).length > 0) {
        window._TALLAS_BACKUP_PERMANENTE = JSON.parse(JSON.stringify(window.cantidadesTallas));
        console.log('💾 [PREVIEW] Tallas capturadas en window._TALLAS_BACKUP_PERMANENTE:',  window._TALLAS_BACKUP_PERMANENTE);
    }
    
    // Información básica
    const cliente = document.getElementById('cliente_editable')?.value || 'Cliente Nuevo';
    const asesora = document.getElementById('asesora_editable')?.value || 'Sin asignar';
    const formaPago = document.getElementById('forma_de_pago_editable')?.value || 'No especificada';
    
    if (!cliente || cliente.trim() === '') {
        console.error('❌ Cliente es requerido');
        return null;
    }
    
    // Capturar prendas/ítems
    const prendas = capturarPrendas();
    
    // Capturar procesos seleccionados
    const procesos = capturarProcesos();
    
    // Capturar EPP seleccionado
    const epp = capturarEPP();
    
    // Fecha actual
    const fechaHoy = new Date();
    
    const datos = {
        cliente: cliente.trim(),
        asesora: asesora.trim(),
        forma_de_pago: formaPago.trim(),
        fecha_creacion: fechaHoy.toLocaleDateString('es-ES', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        }),
        prendas: prendas,
        procesos: procesos,
        epp: epp,
        numero_pedido_temporal: Math.floor(Math.random() * 90000) + 10000
    };
    
    console.log('✅ [PREVIEW] Datos capturados correctamente');
    return datos;
}

/**
 * Captura las prendas del formulario usando el GestorPrendaSinCotizacion
 */
function capturarPrendas() {
    console.log('👕 [PREVIEW] Capturando prendas del gestor...');
    
    const prendas = [];
    
    // Verificar si el gestor existe, con múltiples intentos
    let gestor = window.GestorPrendaSinCotizacion || window.gestorPrendaSinCotizacion;
    
    if (!gestor && window.parent && window.parent.GestorPrendaSinCotizacion) {
        gestor = window.parent.GestorPrendaSinCotizacion;
    }
    
    if (!gestor && window.parent && window.parent.gestorPrendaSinCotizacion) {
        gestor = window.parent.gestorPrendaSinCotizacion;
    }
    
    if (!gestor) {
        console.warn('⚠️  [PREVIEW] GestorPrendaSinCotizacion no disponible - intentando acceso alternativo');
        // Intentar obtener del elemento data si existe
        const formElement = document.querySelector('[data-gestor-prendas]');
        if (formElement && formElement.__gestorPrendas) {
            gestor = formElement.__gestorPrendas;
        }
    }
    
    if (!gestor) {
        console.error('❌ [PREVIEW] GestorPrendaSinCotizacion no disponible en ninguna ubicación');
        return prendas;
    }
    
    try {
        // Obtener todas las prendas del gestor
        const prendasDelGestor = gestor.obtenerActivas ? gestor.obtenerActivas() : 
                                 (gestor.prendas ? Object.values(gestor.prendas) : []);
        
        console.log(`📦 [PREVIEW] Prendas en gestor: ${prendasDelGestor.length}`);
        
        prendasDelGestor.forEach((prenda, index) => {
            // Extraer tallas del prenda preservando géneros CON CANTIDADES REALES
            const tallasConGenero = {};
            
            // Caso 1: generosConTallas es un objeto { "Masculino": {S:2, M:5}, "Femenino": {XS:1} }
            if (prenda.generosConTallas && typeof prenda.generosConTallas === 'object' && !Array.isArray(prenda.generosConTallas)) {
                Object.assign(tallasConGenero, prenda.generosConTallas);
            }
            // Caso 2: generosConTallas es un array [{ genero: "Masculino", cantidades: {S:2} }, ...]
            else if (prenda.generosConTallas && Array.isArray(prenda.generosConTallas)) {
                prenda.generosConTallas.forEach(item => {
                    if (item.genero && item.cantidades) {
                        tallasConGenero[item.genero] = item.cantidades;
                    }
                });
            }
            
            // Caso 3: Tallas es array de objetos [{ genero, tallas[], tipo }] - necesitamos las CANTIDADES
            let tallas = {};
            if (prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0) {
                // Estructura: [{genero: "dama", tallas: ["M", "L"], tipo: "letra"}]
                prenda.tallas.forEach(tallaObj => {
                    if (tallaObj.genero && tallaObj.tallas && Array.isArray(tallaObj.tallas)) {
                        const generoKey = tallaObj.genero.charAt(0).toUpperCase() + tallaObj.genero.slice(1);
                        tallas[generoKey] = {};
                        
                        tallaObj.tallas.forEach(talla => {
                            const cantidadKey = `${tallaObj.genero}-${talla}`;
                            const cantidad = window.cantidadesTallasPorGenero?.[cantidadKey] || 0;
                            if (cantidad > 0) {
                                tallas[generoKey][talla] = cantidad;
                            }
                        });
                    }
                });
            }
            
            // Fallback a otros formatos
            if (Object.keys(tallas).length === 0) {
                if (Object.keys(tallasConGenero).length > 0) {
                    tallas = tallasConGenero;
                } else if (prenda.tallas && typeof prenda.tallas === 'object' && !Array.isArray(prenda.tallas)) {
                    tallas = prenda.tallas;
                }
            }
            
            // Extraer procesos con todos sus detalles
            const procesos = [];
            console.log(`     🔧 DEBUG Procesos para prenda ${index + 1}:`, prenda.procesos);
            if (prenda.procesos && typeof prenda.procesos === 'object') {
                Object.entries(prenda.procesos).forEach(([key, proc]) => {
                    console.log(`        - Proceso "${key}":`, proc);
                    console.log(`          Propiedades disponibles:`, Object.keys(proc || {}));
                    console.log(`          - tipo: ${proc?.tipo}`);
                    console.log(`          - datos: ${JSON.stringify(proc?.datos)}`);
                    
                    // Obtener los datos del proceso (pueden estar en proc.datos o directamente en proc)
                    const procDatos = proc?.datos || proc;
                    const procTipo = proc?.tipo || procDatos?.tipo;
                    
                    console.log(`          ✅ procTipo: ${procTipo}`);
                    console.log(`          ✅ procDatos:`, procDatos);
                    
                    if (procTipo && procDatos) {
                        // Extraer tallas por género para este proceso
                        const tallasProceso = {};
                        if (procDatos.generosConTallas && typeof procDatos.generosConTallas === 'object') {
                            if (!Array.isArray(procDatos.generosConTallas)) {
                                // Es un objeto: { "Masculino": {S:2}, "Femenino": {XS:1} }
                                Object.assign(tallasProceso, procDatos.generosConTallas);
                            } else {
                                // Es un array: [{ genero: "Masculino", cantidades: {S:2} }, ...]
                                procDatos.generosConTallas.forEach(item => {
                                    if (item.genero && item.cantidades) {
                                        tallasProceso[item.genero] = item.cantidades;
                                    }
                                });
                            }
                        } else if (procDatos.tallas && typeof procDatos.tallas === 'object') {
                            // Usar tallas directas si existen
                            if (Array.isArray(procDatos.tallas)) {
                                // Si son arrays separados por género: {dama: ["M", "L"], caballero: []}
                                // O si es un array plano: ["M", "L"]
                                // En este caso, buscar la estructura de generosConTallas como fallback
                                console.log(`        ⚠️  procDatos.tallas es array:`, procDatos.tallas);
                            } else {
                                // Es un objeto: puede ser {dama: {M: 20, L: 20}} o similar
                                Object.assign(tallasProceso, procDatos.tallas);
                            }
                        }
                        
                        console.log(`        ✅ tallasProceso final:`, tallasProceso);
                        
                        // Extraer ubicaciones como array si viene como string
                        let ubicaciones = procDatos.ubicaciones || [];
                        if (typeof ubicaciones === 'string') {
                            ubicaciones = [ubicaciones];
                        } else if (!Array.isArray(ubicaciones)) {
                            ubicaciones = [];
                        }
                        
                        // Extraer imágenes como array
                        let imagenes = procDatos.imagenes || [];
                        if (typeof imagenes === 'string') {
                            imagenes = [imagenes];
                        } else if (!Array.isArray(imagenes)) {
                            imagenes = [];
                        }
                        
                        const procObj = {
                            tipo: procTipo,
                            ubicaciones: ubicaciones,
                            observaciones: procDatos.observaciones || '',
                            imagenes: imagenes,
                            tallas: tallasProceso
                        };
                        console.log(`           ✅ Proceso capturado:`, procObj);
                        procesos.push(procObj);
                    }
                });
            }
            console.log(`     ✅ Total de procesos para prenda ${index + 1}:`, procesos);
            
            // Extraer datos de variantes si existen
            const variantes = prenda.variantes || {};
            const tipoManga = variantes.tipo_manga || prenda.tipo_manga || '';
            const obsManga = variantes.obs_manga || prenda.obs_manga || '';
            const tipoBroche = variantes.tipo_broche || prenda.tipo_broche || '';
            const obsBroche = variantes.obs_broche || prenda.obs_broche || '';
            const tieneBolsillos = variantes.tiene_bolsillos !== undefined ? variantes.tiene_bolsillos : (prenda.tiene_bolsillos || false);
            const obsBolsillos = variantes.obs_bolsillos || prenda.obs_bolsillos || '';
            const tienereflectivo = variantes.tiene_reflectivo !== undefined ? variantes.tiene_reflectivo : (prenda.tiene_reflectivo || false);
            
            // Calcular cantidad total de tallas
            const cantidadTotal = Object.keys(tallas).reduce((sum, genero) => {
                if (typeof tallas[genero] === 'object') {
                    return sum + Object.values(tallas[genero]).reduce((s, v) => s + (parseInt(v) || 0), 0);
                }
                return sum + (parseInt(tallas[genero]) || 0);
            }, 0);
            
            // Detectar imagen con múltiples fallbacks - EXTRAER src si es objeto
            let imagenCapturada = '';
            
            // Log detallado de debugging
            console.log(`     🔍 DEBUG Imagen para prenda ${index + 1}:`);
            console.log(`        - prenda.imagen: ${prenda.imagen}`);
            console.log(`        - prenda.imagen_prenda: ${prenda.imagen_prenda}`);
            console.log(`        - prenda.fotos: ${prenda.fotos}`);
            console.log(`        - prenda.imagenes: ${prenda.imagenes}`);
            if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
                console.log(`        - prenda.imagenes[0]: `, prenda.imagenes[0]);
                console.log(`        - Propiedades: ${Object.keys(prenda.imagenes[0]).join(', ')}`);
            }
            
            if (prenda.imagen) {
                imagenCapturada = typeof prenda.imagen === 'string' ? prenda.imagen : prenda.imagen?.src || '';
                console.log(`        ✅ Usando prenda.imagen: ${imagenCapturada}`);
            } else if (prenda.imagen_prenda) {
                imagenCapturada = typeof prenda.imagen_prenda === 'string' ? prenda.imagen_prenda : prenda.imagen_prenda?.src || '';
                console.log(`        ✅ Usando prenda.imagen_prenda: ${imagenCapturada}`);
            } else if (prenda.fotos && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {
                const firstFoto = prenda.fotos[0];
                imagenCapturada = typeof firstFoto === 'string' ? firstFoto : (firstFoto?.blobUrl || firstFoto?.src || firstFoto?.url || '');
                console.log(`        ✅ Usando prenda.fotos[0]: ${imagenCapturada}`);
            } else if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
                const firstImagen = prenda.imagenes[0];
                console.log(`        📸 DEBUG firstImagen:`, firstImagen);
                console.log(`        📸 DEBUG firstImagen type:`, typeof firstImagen);
                console.log(`        📸 DEBUG firstImagen constructor:`, firstImagen?.constructor?.name);
                console.log(`        📸 DEBUG firstImagen keys:`, Object.keys(firstImagen || {}));
                
                // El componente ImagenesFormDataComponent devuelve: {file, nombre, tamaño}
                // Sin previewUrl (blob URLs son efímeros)
                // Generar blob URL on-demand si existe File object
                if (firstImagen?.file instanceof File) {
                    imagenCapturada = URL.createObjectURL(firstImagen.file);
                    console.log(`        ✅ Blob URL generado on-demand desde File object: ${imagenCapturada}`);
                } else {
                    // Fallback a otras propiedades
                    imagenCapturada = firstImagen?.previewUrl || firstImagen?.blobUrl || firstImagen?.src || firstImagen?.url || firstImagen?.data || (typeof firstImagen === 'string' ? firstImagen : '');
                    console.log(`        ⚠️ Usando fallback: ${imagenCapturada}`);
                }
                
                console.log(`        📸 DEBUG - propiedades encontradas en firstImagen:`);
                console.log(`           - file instanceof File: ${firstImagen?.file instanceof File}`);
                console.log(`           - previewUrl: ${firstImagen?.previewUrl}`);
                console.log(`           - nombre: ${firstImagen?.nombre}`);
                console.log(`           - tamaño: ${firstImagen?.tamaño}`);
                
                // Si aún no hay imagen
                if (!imagenCapturada) {
                    console.log(`        ⚠️ No se pudo generar imagen:`, firstImagen);
                }
            } else {
                console.log(`        ❌ No se encontró imagen en ninguna propiedad`);
            }
            
            // Detectar imagen de tela con múltiples fallbacks - EXTRAER src si es objeto
            let imagenTelaCapturada = '';
            
            console.log(`     🧵 DEBUG Imagen Tela para prenda ${index + 1}:`);
            console.log(`        - prenda.imagen_tela: ${prenda.imagen_tela}`);
            console.log(`        - prenda.muestra_tela: ${prenda.muestra_tela}`);
            console.log(`        - prenda.imagenes_tela: ${prenda.imagenes_tela}`);
            console.log(`        - prenda.telaFotos: ${prenda.telaFotos}`);
            if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0) {
                console.log(`        - prenda.telasAgregadas[0].imagenes: `, prenda.telasAgregadas[0].imagenes);
            }
            
            if (prenda.imagen_tela) {
                imagenTelaCapturada = typeof prenda.imagen_tela === 'string' ? prenda.imagen_tela : prenda.imagen_tela?.src || '';
                console.log(`        ✅ Usando prenda.imagen_tela: ${imagenTelaCapturada}`);
            } else if (prenda.muestra_tela) {
                imagenTelaCapturada = typeof prenda.muestra_tela === 'string' ? prenda.muestra_tela : prenda.muestra_tela?.src || '';
                console.log(`        ✅ Usando prenda.muestra_tela: ${imagenTelaCapturada}`);
            } else if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela) && prenda.imagenes_tela.length > 0) {
                const firstTela = prenda.imagenes_tela[0];
                imagenTelaCapturada = typeof firstTela === 'string' ? firstTela : (firstTela?.blobUrl || firstTela?.src || firstTela?.url || firstTela?.data || '');
                console.log(`        ✅ Usando prenda.imagenes_tela[0]: ${imagenTelaCapturada}`);
            } else if (prenda.telaFotos && Array.isArray(prenda.telaFotos) && prenda.telaFotos.length > 0) {
                const firstTelaFoto = prenda.telaFotos[0];
                imagenTelaCapturada = typeof firstTelaFoto === 'string' ? firstTelaFoto : (firstTelaFoto?.blobUrl || firstTelaFoto?.src || firstTelaFoto?.url || firstTelaFoto?.data || '');
                console.log(`        ✅ Usando prenda.telaFotos[0]: ${imagenTelaCapturada}`);
            } else if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0 && 
                       prenda.telasAgregadas[0].imagenes && Array.isArray(prenda.telasAgregadas[0].imagenes)) {
                const firstTelaAg = prenda.telasAgregadas[0].imagenes[0];
                console.log(`        📸 DEBUG firstTelaAg:`, firstTelaAg);
                console.log(`        📸 DEBUG firstTelaAg type:`, typeof firstTelaAg);
                console.log(`        📸 DEBUG firstTelaAg constructor:`, firstTelaAg?.constructor?.name);
                
                // Generar blob URL on-demand si es File object
                if (firstTelaAg instanceof File) {
                    imagenTelaCapturada = URL.createObjectURL(firstTelaAg);
                    console.log(`        ✅ Blob URL generado on-demand desde File (tela): ${imagenTelaCapturada}`);
                } else {
                    // Fallback si es objeto con propiedades
                    imagenTelaCapturada = typeof firstTelaAg === 'string' ? firstTelaAg : (firstTelaAg?.blobUrl || firstTelaAg?.previewUrl || firstTelaAg?.src || firstTelaAg?.url || firstTelaAg?.data || '');
                    console.log(`        ⚠️ Fallback (tela): ${imagenTelaCapturada}`);
                }
            } else {
                console.log(`        ❌ No se encontró imagen de tela en ninguna propiedad`);
            }
            
            // Detectar tela y color desde telasAgregadas (array de telas)
            let telaCapturada = prenda.tela || variantes.tela || '';
            let colorCapturado = prenda.color || variantes.color || '';
            let refCapturada = prenda.ref || '';
            
            if (!refCapturada || !telaCapturada || !colorCapturado) {
                if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0) {
                    const primeTela = prenda.telasAgregadas[0];
                    if (!telaCapturada) telaCapturada = primeTela.tela || '';
                    if (!colorCapturado) colorCapturado = primeTela.color || '';
                    if (!refCapturada) refCapturada = primeTela.referencia || '';
                    
                    // Si hay imagen en la tela, usarla para imagen_tela
                    if (!imagenTelaCapturada && primeTela.imagenes && Array.isArray(primeTela.imagenes) && primeTela.imagenes.length > 0) {
                        const telaImg = primeTela.imagenes[0];
                        
                        // Generar blob URL on-demand si es File object
                        if (telaImg instanceof File) {
                            imagenTelaCapturada = URL.createObjectURL(telaImg);
                            console.log(`        ✅ Blob URL generado on-demand desde File (tela fallback): ${imagenTelaCapturada}`);
                        } else {
                            imagenTelaCapturada = typeof telaImg === 'string' ? telaImg : (telaImg?.blobUrl || telaImg?.previewUrl || telaImg?.src || telaImg?.url || '');
                            console.log(`        ⚠️ Fallback (tela fallback): ${imagenTelaCapturada}`);
                        }
                    }
                }
            }
            
            // RECONSTRUIR tallas con cantidades reales desde window.cantidadesTallasPorGenero
            let tallasReconstruidas = {};
            
            // Estrategia PRINCIPAL: Usar window._TALLAS_BACKUP_PERMANENTE (guardadas siempre)
            // Esto tiene los datos reales: {dama-S: 20, dama-M: 20, ...}
            const tallasCapturadasDisponibles = window._TALLAS_BACKUP_PERMANENTE || window.cantidadesTallas || window._TALLAS_CAPTURADAS_PREVIEW || window.cantidadesTallasPorGenero;
            console.log(`     📊 DEBUG TALLAS - tallasCapturadasDisponibles:`, tallasCapturadasDisponibles);
            
            if (tallasCapturadasDisponibles && typeof tallasCapturadasDisponibles === 'object' && Object.keys(tallasCapturadasDisponibles).length > 0) {
                console.log(`     ✅ Encontradas tallas, agrupando por género...`);
                // Agrupar por género
                Object.entries(tallasCapturadasDisponibles).forEach(([clave, cantidad]) => {
                    // clave es formato "dama-S", "dama-M", etc
                    const partes = clave.split('-');
                    if (partes.length === 2) {
                        const genero = partes[0];
                        const talla = partes[1];
                        const generoKey = genero.charAt(0).toUpperCase() + genero.slice(1);
                        
                        if (!tallasReconstruidas[generoKey]) {
                            tallasReconstruidas[generoKey] = {};
                        }
                        
                        if (cantidad > 0) {
                            tallasReconstruidas[generoKey][talla] = cantidad;
                            console.log(`        - ${generoKey}: ${talla} = ${cantidad}`);
                        }
                    }
                });
            } else {
                console.log(`     ❌ Tallas no disponibles, intentando fallbacks...`);
            }
            
            // Fallback: Si aún no tenemos tallas, intentar desde generosConTallas
            if (Object.keys(tallasReconstruidas).length === 0 && prenda.generosConTallas && typeof prenda.generosConTallas === 'object' && !Array.isArray(prenda.generosConTallas)) {
                Object.entries(prenda.generosConTallas).forEach(([genero, generoData]) => {
                    if (generoData && generoData.cantidades && typeof generoData.cantidades === 'object') {
                        const generoKey = genero.charAt(0).toUpperCase() + genero.slice(1);
                        tallasReconstruidas[generoKey] = generoData.cantidades;
                    }
                });
            }
            
            // Fallback 2: Si prenda.tallas es array [{genero, tallas: [], tipo}]
            if (Object.keys(tallasReconstruidas).length === 0 && prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0) {
                prenda.tallas.forEach(item => {
                    if (item && item.genero && Array.isArray(item.tallas) && item.tallas.length > 0) {
                        const generoKey = item.genero.charAt(0).toUpperCase() + item.genero.slice(1);
                        tallasReconstruidas[generoKey] = {};
                        
                        item.tallas.forEach(talla => {
                            const cantidadKey = `${item.genero}-${talla}`;
                            const cantidad = window.cantidadesTallasPorGenero?.[cantidadKey] || 0;
                            if (cantidad > 0) {
                                tallasReconstruidas[generoKey][talla] = cantidad;
                            }
                        });
                    }
                });
            }
            
            // Fallback 3: Si prenda.tallas es objeto {Genero: {talla: cantidad}}
            if (Object.keys(tallasReconstruidas).length === 0 && prenda.tallas && typeof prenda.tallas === 'object' && !Array.isArray(prenda.tallas)) {
                Object.entries(prenda.tallas).forEach(([genero, tallasObj]) => {
                    if (typeof tallasObj === 'object' && !Array.isArray(tallasObj) && Object.keys(tallasObj).length > 0) {
                        tallasReconstruidas[genero] = tallasObj;
                    }
                });
            }
            
            // Fallback final: usar variable local tallas
            if (Object.keys(tallasReconstruidas).length === 0) {
                console.log(`     📊 Fallback final: Usando variable local tallas`);
                tallasReconstruidas = tallas;
            }
            
            console.log(`     ✅ Tallas finales reconstruidas:`, tallasReconstruidas);
            
            prendas.push({
                numero: index + 1,
                nombre: prenda.nombre_producto || prenda.nombre || `Prenda ${index + 1}`,
                descripcion: prenda.descripcion || '',
                ref: refCapturada,
                imagen: imagenCapturada,
                imagenes: prenda.imagenes && Array.isArray(prenda.imagenes) ? prenda.imagenes.map(img => {
                    if (img instanceof File) {
                        return URL.createObjectURL(img);
                    }
                    return img.blobUrl || img.previewUrl || img.src || img;
                }) : (imagenCapturada ? [imagenCapturada] : []),
                imagen_tela: imagenTelaCapturada,
                imagenes_tela: prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) ? prenda.telasAgregadas.filter(t => t.imagenes && t.imagenes.length > 0).flatMap(t => t.imagenes.map(img => {
                    if (img instanceof File) {
                        return URL.createObjectURL(img);
                    }
                    return img.blobUrl || img.previewUrl || img.src || img;
                })) : (imagenTelaCapturada ? [imagenTelaCapturada] : []),
                manga: tipoManga && tipoManga !== 'No aplica' ? tipoManga : '',
                obs_manga: obsManga,
                broche: tipoBroche && tipoBroche !== 'No aplica' ? tipoBroche : '',
                obs_broche: obsBroche,
                color: colorCapturado,
                tela: telaCapturada,
                talla_referencia: prenda.talla_referencia || '',
                variantes: prenda.variantes || {},
                origen: prenda.origen || 'Confección',
                tallas: tallasReconstruidas,
                cantidad: cantidadTotal,
                tiene_bolsillos: tieneBolsillos,
                obs_bolsillos: obsBolsillos,
                tiene_reflectivo: tienereflectivo,
                procesos: procesos
            });
            
            console.log(`  📌 Prenda ${index + 1}: ${prenda.nombre_producto}`);
            console.log(`     🖼️  prenda.imagenes:`, prenda.imagenes);
            console.log(`     🖼️  imagenCapturada type:`, typeof imagenCapturada);
            console.log(`     🖼️  imagenCapturada value:`, imagenCapturada);
            console.log(`     🖼️  Imagen CAPTURADA: ${imagenCapturada || '❌ no'}`);
            console.log(`     🧵 Tela CAPTURADA: ${telaCapturada || '❌ no'} | Color CAPTURADO: ${colorCapturado || '❌ no'}`);
            console.log(`     📋 Ref CAPTURADA: ${refCapturada || '❌ no'}`);
            console.log(`     📏 Tallas: ${JSON.stringify(tallasReconstruidas)}`);
            console.log(`     🔧 Procesos: ${procesos.length}`);
        });
    } catch (error) {
        console.error('❌ [PREVIEW] Error capturando prendas:', error);
    }
    
    console.log(`✅ [PREVIEW] ${prendas.length} prenda(s) capturada(s) del gestor`);
    return prendas;
}

/**
 * Extrae tallas de un contenedor
 */
function extraerTallas(container) {
    const tallas = {};
    
    // Buscar inputs de talla
    const tallasInputs = container.querySelectorAll('input[name*="talla"], input[name*="size"], [data-talla]');
    
    tallasInputs.forEach(input => {
        const valor = input.value || input.textContent;
        if (valor) {
            const match = valor.match(/([A-Z]+)\s*[:=]?\s*(\d+)/i);
            if (match) {
                tallas[match[1]] = match[2];
            }
        }
    });
    
    return Object.keys(tallas).length > 0 ? tallas : { 'S': '1', 'M': '1', 'L': '1', 'XL': '1' };
}

/**
 * Captura los procesos seleccionados globales
 * Nota: Los procesos por prenda se capturan en capturarPrendas()
 * Esta función captura procesos globales aplicables a todo el pedido
 */
function capturarProcesos() {
    console.log('🔧 [PREVIEW] Capturando procesos globales...');
    
    const procesos = [];
    
    // Buscar procesos globales en el formulario (si existen)
    const procesosCheckboxes = document.querySelectorAll('input[type="checkbox"][name*="proceso_general"], input[type="checkbox"][name*="process_general"], input[type="checkbox"][name*="procesos"]');
    
    procesosCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const label = document.querySelector(`label[for="${checkbox.id}"]`)?.textContent || checkbox.value;
            procesos.push(label.trim());
        }
    });
    
    console.log(`✅ [PREVIEW] ${procesos.length} proceso(s) global(es) capturado(s)`);
    return procesos;
}

/**
 * Captura el EPP seleccionado
 * Solo devuelve EPP si realmente hay items seleccionados
 */
function capturarEPP() {
    console.log('🦺 [PREVIEW] Capturando EPP...');
    
    const epp = [];
    const eppCheckboxes = document.querySelectorAll('input[type="checkbox"][name*="epp"], input[type="checkbox"][name*="protection"]');
    
    eppCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const label = document.querySelector(`label[for="${checkbox.id}"]`)?.textContent || checkbox.value;
            epp.push(label.trim());
        }
    });
    
    console.log(`✅ [PREVIEW] ${epp.length} EPP capturado(s)`);
    return epp; // Devolver array vacío si no hay EPP, sin defaults
}

/**
 * Crea un modal con la vista previa de la factura
 */
function crearModalPreviewFactura(datos) {
    console.log('🖼️  [PREVIEW] Creando modal de vista previa...');
    
    // Remover modal anterior si existe
    const modalAnterior = document.getElementById('invoice-preview-modal-wrapper');
    if (modalAnterior) {
        modalAnterior.remove();
    }
    
    // Crear el modal
    const modal = document.createElement('div');
    modal.id = 'invoice-preview-modal-wrapper';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10001;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
    `;
    
    // HTML de la vista previa
    const htmlFactura = generarHTMLFactura(datos);
    
    modal.innerHTML = `
        <style>
            #invoice-preview-modal-wrapper * {
                font-family: Arial, sans-serif;
            }
            #invoice-preview-modal-wrapper table td,
            #invoice-preview-modal-wrapper table th,
            #invoice-preview-modal-wrapper table {
                font-size: 10px !important;
            }
            #invoice-preview-modal-wrapper em {
                font-size: 10px !important;
            }
        </style>
        <div style="background: white; border-radius: 6px; width: 100%; max-width: 1100px; height: 95vh; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.3); overflow: hidden;">
            <!-- Header -->
            <div style="padding: 8px 12px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; background: #f9f9f9;">
                <h3 style="margin: 0; color: #333; font-size: 12px; font-weight: 700;">
                    📋 Pedido #${datos.numero_pedido_temporal} | ${datos.cliente}
                </h3>
                <button onclick="document.getElementById('invoice-preview-modal-wrapper').remove();" 
                        style="background: none; border: none; font-size: 20px; cursor: pointer; color: #999; padding: 0; line-height: 1;">
                    ×
                </button>
            </div>
            
            <!-- Content -->
            <div id="preview-content" style="flex: 1; overflow: auto; padding: 8px 10px; background: #fafafa;">
                ${htmlFactura}
            </div>
            
            <!-- Footer -->
            <div style="padding: 8px 12px; border-top: 1px solid #ddd; display: flex; gap: 6px; justify-content: flex-end; background: #f9f9f9;">
                <button onclick="document.getElementById('invoice-preview-modal-wrapper').remove();" 
                        style="padding: 6px 12px; background: #ddd; border: none; border-radius: 3px; cursor: pointer; font-weight: 600; font-size: 11px;">
                    ✕ Cerrar
                </button>
                <button onclick="document.getElementById('preview-content').contentWindow?.print() || window.print();" 
                        style="padding: 6px 12px; background: #2c3e50; color: white; border: none; border-radius: 3px; cursor: pointer; font-weight: 600; font-size: 11px;">
                    🖨️ Imprimir
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Cerrar al hacer click en el fondo
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    console.log('✅ [PREVIEW] Modal de vista previa creado');
}

/**
 * Genera el HTML de la factura con los datos en tiempo real
 */
function generarHTMLFactura(datos) {
    console.log('✏️  [PREVIEW] Generando HTML de factura...');
    
    // Generar las tarjetas de prendas con todos los detalles
    const prendasHTML = datos.prendas.map((prenda, idx) => {
        // Especificaciones principales (Tabla compacta)
        const variacionesArray = [
            prenda.manga ? { nombre: 'Manga', valor: prenda.manga, obs: prenda.obs_manga } : null,
            prenda.broche ? { nombre: 'Broche', valor: prenda.broche, obs: prenda.obs_broche } : null,
            prenda.tiene_bolsillos && prenda.obs_bolsillos ? { nombre: 'Bolsillo', valor: '', obs: prenda.obs_bolsillos } : null
        ].filter(v => v !== null);
        
        const especificacionesHTML = variacionesArray.length > 0 ? `
            <div style="margin: 12px 0; padding: 0; background: #ffffff; border-radius: 6px; border: 1px solid #e0e7ff; overflow: hidden;">
                <div style="font-size: 11px !important; font-weight: 700; color: #1e40af; background: #eff6ff; margin: 0; padding: 12px 12px; border-bottom: 2px solid #bfdbfe;">📋 ESPECIFICACIONES</div>
                <table style="width: 100%; font-size: 11px !important; border-collapse: collapse;">
                    <tbody>
                        ${variacionesArray.map((spec, idx) => `
                            <tr style="background: ${idx % 2 === 0 ? '#ffffff' : '#f8fafc'}; border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px 12px; font-weight: 600; color: #334155; width: 35%; font-size: 11px !important;">${spec.nombre}</td>
                                <td style="padding: 12px 12px; color: #475569; font-size: 11px !important;">${spec.valor}${spec.obs ? ` <span style="color: #64748b; font-style: italic; font-size: 10px !important;">(${spec.obs})</span>` : ''}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        ` : '';
        
        // Información de tela, color y referencia (SIEMPRE mostrar)
        const telaHTML = (prenda.tela || prenda.color || prenda.ref || prenda.imagen_tela) ? `
            <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                ${prenda.ref ? `
                    <div>
                        <div style="font-size: 10px; text-transform: uppercase; color: #999; margin-bottom: 4px; font-weight: 700;">Referencia</div>
                        <div style="font-size: 13px; color: #2c3e50; font-weight: 600;">${prenda.ref}</div>
                    </div>
                ` : ''}
                ${prenda.tela ? `
                    <div>
                        <div style="font-size: 10px; text-transform: uppercase; color: #999; margin-bottom: 4px; font-weight: 700;">Tela</div>
                        <div style="font-size: 13px; color: #555;">${prenda.tela}</div>
                    </div>
                ` : ''}
                ${prenda.color ? `
                    <div>
                        <div style="font-size: 10px; text-transform: uppercase; color: #999; margin-bottom: 4px; font-weight: 700;">Color</div>
                        <div style="font-size: 13px; color: #555;">${prenda.color}</div>
                    </div>
                ` : ''}
                ${prenda.imagen_tela ? `
                    <div>
                        <div style="font-size: 10px; text-transform: uppercase; color: #999; margin-bottom: 4px; font-weight: 700;">Muestra Tela</div>
                        <img src="${prenda.imagen_tela}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                ` : ''}
            </div>
            <div style="height: 1px; background: #e0e0e0; margin-bottom: 15px;"></div>
        ` : '';
        
        // Tallas por género (mejorado)
        let generosTallasHTML = '';
        console.log(`     📏 DEBUG TALLAS PARA PRENDA ${idx + 1}:`, prenda.tallas);
        if (prenda.tallas && typeof prenda.tallas === 'object' && Object.keys(prenda.tallas).length > 0) {
            // prenda.tallas debería ser {Dama: {S: 20, M: 20}, Caballero: {}}
            console.log(`     ✅ Tallas encontradas, estructura:`, JSON.stringify(prenda.tallas));
            const primeraClave = Object.keys(prenda.tallas)[0];
            console.log(`     🔍 Primera clave: ${primeraClave}`);
            const esGenero = ['Masculino', 'Femenino', 'Unisex', 'Dama', 'Caballero', 'Niño', 'Niña', 'Unisex'].includes(primeraClave);
            const tieneSubobjetos = typeof prenda.tallas[primeraClave] === 'object' && !Array.isArray(prenda.tallas[primeraClave]);
            const esOrganizadaPorGenero = esGenero && tieneSubobjetos;
            
            console.log(`     📊 esGenero: ${esGenero}, tieneSubobjetos: ${tieneSubobjetos}, esOrganizadaPorGenero: ${esOrganizadaPorGenero}`);
            
            if (esOrganizadaPorGenero) {
                // Tallas por género { Dama: {S: 20, M: 20}, Caballero: {} }
                const generosConTallas = Object.entries(prenda.tallas).filter(([gen, tallasObj]) => 
                    typeof tallasObj === 'object' && !Array.isArray(tallasObj) && Object.keys(tallasObj).length > 0
                );
                
                console.log(`     🎯 Géneros con tallas después de filtrar:`, generosConTallas);
                
                if (generosConTallas.length > 0) {
                    generosTallasHTML = `
                        <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                            <tbody>
                                ${generosConTallas.map(([genero, tallasObj]) => `
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 3px 0; font-weight: 600; color: #333;">${genero}</td>
                                        <td style="padding: 3px 0; color: #666;">${Object.entries(tallasObj).map(([talla, cant]) => `${talla}:${cant}`).join(', ')}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    generosTallasHTML = '<span style="color: #999; font-size: 9px;">Sin tallas</span>';
                }
            } else {
                // Tallas planas { S: 20, M: 20 }
                generosTallasHTML = `
                    <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                        <tbody>
                            ${Object.entries(prenda.tallas).map(([talla, cant]) => `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 3px 0; font-weight: 600; color: #333;">${talla}</td>
                                    <td style="padding: 3px 0; color: #666;">${cant}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }
        } else {
            generosTallasHTML = '<span style="color: #999; font-size: 9px;">Sin tallas</span>';
        }
        
        // Procesos
        console.log(`     📋 Renderizando procesos para prenda ${idx + 1}:`, prenda.procesos);
        const procesosListaHTML = prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0
            ? prenda.procesos.map(proc => {
                console.log(`        - Renderizando proceso: `, proc);
                // Renderizar tallas del proceso (también pueden ser por género)
                let tallasProcHTML = '';
                console.log(`        📊 DEBUG proc.tallas:`, proc.tallas);
                if (proc.tallas && Object.keys(proc.tallas).length > 0) {
                    const procPrimeraClave = Object.keys(proc.tallas)[0];
                    console.log(`        🔍 procPrimeraClave: ${procPrimeraClave}, tipo:`, typeof proc.tallas[procPrimeraClave]);
                    const procEsGenero = typeof proc.tallas[procPrimeraClave] === 'object' && 
                                         !Array.isArray(proc.tallas[procPrimeraClave]);
                    
                    console.log(`        📌 procEsGenero: ${procEsGenero}`);
                    
                    if (procEsGenero) {
                        // Por género - FILTRAR géneros vacíos
                        const generosConTallasProc = Object.entries(proc.tallas).filter(([gen, tallasObj]) => 
                            typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0
                        );
                        
                        console.log(`        ✅ generosConTallasProc:`, generosConTallasProc);
                        
                        if (generosConTallasProc.length > 0) {
                            tallasProcHTML = `
                                <div style="margin: 2px 0; padding: 4px; background: white; border-radius: 2px; font-size: 10px;">
                                    ${generosConTallasProc.map(([gen, tallasObj]) => {
                                        return `<div><strong>${gen}:</strong> ${Object.entries(tallasObj).map(([t, c]) => `${t}:${c}`).join(', ')}</div>`;
                                    }).join('')}
                                </div>
                            `;
                        }
                    } else {
                        // Planas
                        tallasProcHTML = `
                            <div style="margin: 2px 0; padding: 4px; background: white; border-radius: 2px; font-size: 10px;">
                                ${Object.entries(proc.tallas).map(([talla, cant]) => 
                                  `${talla}:${cant}`
                                ).join(' | ')}
                            </div>
                        `;
                    }
                }
                
                return `
                    <div style="background: #f9f9f9; padding: 6px; margin: 4px 0; border-left: 3px solid #27ae60; border-radius: 2px; font-size: 10px;">
                        <div style="font-weight: 700; color: #27ae60; margin-bottom: 4px; text-transform: uppercase;">Reflectivo: ${proc.tipo || 'Proceso sin tipo'}</div>
                        
                        ${(proc.ubicaciones?.length > 0 || proc.observaciones) ? `
                            <table style="width: 100%; font-size: 10px; margin-bottom: 4px; border-collapse: collapse;">
                                ${proc.ubicaciones && proc.ubicaciones.length > 0 ? `
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 2px 3px; font-weight: 600; color: #27ae60; width: 25%;">Ubicación:</td>
                                        <td style="padding: 2px 3px;">${proc.ubicaciones.join(', ')}</td>
                                    </tr>
                                ` : ''}
                                ${proc.observaciones ? `
                                    <tr>
                                        <td style="padding: 2px 3px; font-weight: 600; color: #27ae60; width: 25%;">Observaciones:</td>
                                        <td style="padding: 2px 3px; font-size: 10px;">${proc.observaciones}</td>
                                    </tr>
                                ` : ''}
                            </table>
                        ` : ''}
                        
                        ${tallasProcHTML}
                        
                        ${proc.imagenes && proc.imagenes.length > 0 ? `
                            <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid #eee; display: flex; gap: 4px; position: relative;">
                                ${Array.isArray(proc.imagenes) ? 
                                    `<div style="position: relative; cursor: pointer;" onclick="window._abrirGaleriaImagenes(${JSON.stringify(proc.imagenes).replace(/"/g, '&quot;')}, 'Imágenes de ${proc.tipo}')">
                                        <img src="${proc.imagenes[0]}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 2px; border: 1px solid #ddd;">
                                        ${proc.imagenes.length > 1 ? `
                                            <div style="position: absolute; top: 0; right: 0; background: #27ae60; color: white; font-size: 9px; font-weight: 700; padding: 2px 4px; border-radius: 0 2px 0 2px; cursor: pointer;">
                                                ${proc.imagenes.length}+
                                            </div>
                                        ` : ''}
                                    </div>`
                                    : ''
                                }
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('')
            : '<div style="color: #999; font-size: 10px; font-style: italic;">Sin procesos asociados</div>';
        
        return `
            <div style="background: white; border: 1px solid #ddd; border-radius: 3px; padding: 8px; margin-bottom: 8px; page-break-inside: avoid; font-size: 10px;">
                <!-- LAYOUT 4 COLUMNAS PRINCIPALES -->
                <div style="display: grid; grid-template-columns: 160px 180px 180px 160px; gap: 12px;">
                    
                    <!-- COLUMNA 1: Imagen + Nombre/Descripción -->
                    <div style="display: flex; gap: 8px; align-items: flex-start;">
                        <div style="flex-shrink: 0;">
                            ${prenda.imagen ? `
                                <img src="${prenda.imagen}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 3px; border: 1px solid #ddd; cursor: pointer;" onclick="window._abrirGaleriaImagenesDesdeID(${window._registrarGalería(prenda.imagenes, 'Imágenes de Prenda')})" title="Click para ver todas las imágenes">
                            ` : `
                                <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 3px; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; font-size: 32px;">📦</div>
                            `}
                        </div>
                        <div style="flex: 1; font-size: 10px;">
                            <div style="font-weight: 700; color: #2c3e50; margin-bottom: 3px; line-height: 1.3;">${prenda.nombre}</div>
                            ${prenda.descripcion ? `<div style="color: #666; font-size: 9px; line-height: 1.3;">${prenda.descripcion}</div>` : ''}
                        </div>
                    </div>
                    
                    <!-- COLUMNA 2: Tela, Color, Ref + Imagen Tela -->
                    <div style="font-size: 10px;">
                        ${prenda.tela ? `<div style="margin-bottom: 4px;"><strong>Tela:</strong> ${prenda.tela}</div>` : ''}
                        ${prenda.color ? `<div style="margin-bottom: 4px;"><strong>Color:</strong> ${prenda.color}</div>` : ''}
                        ${prenda.ref ? `<div style="margin-bottom: 6px;"><strong>Ref:</strong> ${prenda.ref}</div>` : ''}
                        ${prenda.imagen_tela ? `
                            <div>
                                <img src="${prenda.imagen_tela}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 2px; border: 1px solid #ddd; cursor: pointer;" onclick="window._abrirGaleriaImagenesDesdeID(${window._registrarGalería(prenda.imagenes_tela, 'Imágenes de Tela')})" title="Click para ver todas las imágenes de tela">
                            </div>
                        ` : ''}
                    </div>
                    
                    <!-- COLUMNA 3: Variantes (Manga, Broche, Bolsillos) -->
                    <div style="font-size: 10px;">
                        ${variacionesArray.length > 0 ? `
                            <table style="width: 100%; border-collapse: collapse;">
                                <tbody>
                                    ${variacionesArray.map(spec => `
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td style="padding: 3px 0; font-weight: 600; color: #333; font-size: 10px; width: 50%;">${spec.nombre}</td>
                                            <td style="padding: 3px 0; color: #666; font-size: 10px;">${spec.valor}${spec.obs ? ` (${spec.obs})` : ''}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : '<span style="color: #999; font-size: 9px;">Sin variantes</span>'}
                    </div>
                    
                    <!-- COLUMNA 4: Tallas por Género -->
                    <div style="font-size: 10px;">
                        ${generosTallasHTML}
                    </div>
                </div>
                
                <!-- FILA INFERIOR: Procesos -->
                ${procesosListaHTML ? `
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee;">
                        <div style="font-size: 10px; font-weight: 700; color: #2c3e50; margin-bottom: 4px;">🔧 Procesos ${prenda.procesos && Array.isArray(prenda.procesos) ? `(${prenda.procesos.length})` : ''}</div>
                        ${procesosListaHTML}
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
    
    return `
        <div style="background: white; padding: 12px; border-radius: 4px; max-width: 100%; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px;">
            <!-- Header Profesional COMPACTO -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;">
                <!-- Logo y Cliente -->
                <div style="display: flex; gap: 12px; align-items: center;">
                    <img src="/favicon.svg" alt="Mundo Industrial" style="height: 40px; object-fit: contain;">
                    <div style="font-size: 11px;">
                        <div style="font-weight: 700; color: #1a3a52; margin-bottom: 3px;">${datos.cliente}</div>
                        <div style="color: #666; font-size: 10px;">${datos.asesora}</div>
                    </div>
                </div>
                
                <!-- Pedido (derecha) -->
                <div style="text-align: right; font-size: 10px;">
                    <div style="font-weight: 700; color: #1a3a52; margin-bottom: 3px;">
                        PEDIDO #2026-${String(datos.numero_pedido_temporal).padStart(5, '0')}
                    </div>
                    <div style="color: #666;">${datos.fecha_creacion}</div>
                </div>
            </div>
            
            <!-- Items (Prendas) -->
            <div style="margin-top: 8px;">
                ${prendasHTML}
            </div>
        </div>
    `;
}

/**
 * Guarda el HTML de la factura
 */
function guardarComoHTML(nombreArchivo) {
    console.log('💾 [PREVIEW] Guardando como HTML:', nombreArchivo);
    
    const contenido = document.getElementById('preview-content').innerHTML;
    const elemento = document.createElement('a');
    
    elemento.setAttribute('href', 'data:text/html;charset=utf-8,' + encodeURIComponent(contenido));
    elemento.setAttribute('download', nombreArchivo);
    elemento.style.display = 'none';
    
    document.body.appendChild(elemento);
    elemento.click();
    document.body.removeChild(elemento);
    
    console.log('✅ [PREVIEW] Archivo guardado');
}

// ========================================
// AGREGAR BOTÓN A FORMULARIO
// ========================================

/**
 * Agregar botón de vista previa al formulario
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [PREVIEW] Inicializando invoice-preview-live.js');
    
    // Esperar a que el formulario esté completamente cargado
    setTimeout(() => {
        const form = document.getElementById('formCrearPedidoEditable') || document.querySelector('form');
        
        if (form) {
            // Crear botón de vista previa
            const btnPreview = document.createElement('button');
            btnPreview.type = 'button';
            btnPreview.innerHTML = 'Vista Previa del Pedido';
            btnPreview.style.cssText = `
                padding: 10px 16px;
                background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.9rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                transition: all 0.3s;
                box-shadow: 0 2px 4px rgba(107, 114, 128, 0.2);
            `;
            
            btnPreview.onmouseover = function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(107, 114, 128, 0.3)';
            };
            
            btnPreview.onmouseout = function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(107, 114, 128, 0.2)';
            };
            
            btnPreview.onclick = function(e) {
                e.preventDefault();
                abrirPreviewFacturaEnVivo();
            };
            
            // Buscar dónde insertar el botón (buscar un contenedor de botones o insertar antes del submit)
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && submitBtn.parentElement) {
                submitBtn.parentElement.insertBefore(btnPreview, submitBtn);
            } else {
                form.appendChild(btnPreview);
            }
            
            console.log('✅ [PREVIEW] Botón de vista previa agregado al formulario');
        } else {
            console.warn('⚠️  [PREVIEW] Formulario no encontrado');
        }
    }, 500);
});

console.log('✅ [INVOICE PREVIEW] invoice-preview-live.js cargado');
