/**
 * Preview de Factura en Tiempo Real
 * Captura datos del formulario de creación de pedido y los muestra en la factura
 * sin necesidad de guardar en la base de datos
 */



/**
 * Almacenamiento global de galerías de imágenes para el preview
 * Esto evita tener que serializar arrays de blob URLs en el onclick
 */
window._galeríasPreview = {};
window._idGaleriaPreview = 0;

/**
 * Helper para extraer URL de una imagen (que puede ser string u objeto)
 */
window._extraerURLImagen = function(img) {
    if (!img) {

        return '';
    }
    
    let url = '';
    let origen = '';
    
    if (typeof img === 'string') {
        url = img;
        origen = 'string directo';
    } else {
        // Loguear todas las propiedades disponibles
        console.log('[EXTRAER-URL-IMAGEN] Objeto imagen recibido:', {
            tipo: typeof img,
            propiedades: Object.keys(img),
            url: img.url,
            ruta: img.ruta,
            path: img.path,
            src: img.src,
            blobUrl: img.blobUrl,
            previewUrl: img.previewUrl,
            ruta_webp: img.ruta_webp,
            ruta_original: img.ruta_original
        });
        
        if (img.ruta_webp) {
            url = img.ruta_webp;
            origen = 'img.ruta_webp';
        } else if (img.ruta_original) {
            url = img.ruta_original;
            origen = 'img.ruta_original';
        } else if (img.url) {
            url = img.url;
            origen = 'img.url';
        } else if (img.ruta) {
            url = img.ruta;
            origen = 'img.ruta';
        } else if (img.path) {
            url = img.path;
            origen = 'img.path';
        } else if (img.src) {
            url = img.src;
            origen = 'img.src';
        }
    }
    
    console.log('[EXTRAER-URL-IMAGEN] URL extraída:', {
        origen: origen,
        url_original: url,
        comienza_con_storage: url.startsWith('/storage/'),
        comienza_con_storage_sin_slash: url.startsWith('storage/'),
        comienza_con_slash: url.startsWith('/')
    });
    
    // Procesar la URL para evitar duplicación de /storage/
    if (url) {
        // Si comienza con /storage/, devolverla tal cual
        if (url.startsWith('/storage/')) {
            console.log('[EXTRAER-URL-IMAGEN] Ya tiene /storage/, retornando:', url);
            return url;
        }
        // Si comienza con storage/ (sin /), agregar / al inicio
        else if (url.startsWith('storage/')) {
            url = '/' + url;
            console.log('[EXTRAER-URL-IMAGEN] Agregado / inicial, retornando:', url);
            return url;
        }
        // Si no comienza con / ni con storage/, agregar /storage/
        else {
            url = '/storage/' + url;
            console.log('[EXTRAER-URL-IMAGEN] Agregado /storage/, retornando:', url);
            return url;
        }
    }
    
    return '';
};

/**
 * Registra una galería de imágenes y retorna un ID único
 */
window._registrarGalería = function(imagenes, titulo) {
    if (!Array.isArray(imagenes) || imagenes.length === 0) return null;
    
    const id = window._idGaleriaPreview++;
    window._galeríasPreview[id] = { imagenes, titulo };
    

    return id;
};

/**
 * Abre una galería usando su ID registrado
 */
window._abrirGaleriaImagenesDesdeID = function(galeriaId) {
    if (galeriaId === null || galeriaId === undefined || !window._galeríasPreview[galeriaId]) {

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
    
    // Normalizar imagenes - convertir objetos a URLs usando la misma función que el renderizado
    const imagenesNormalizadas = imagenes.map(img => {
        if (typeof img === 'string') {
            // Si es string, aplicar la misma lógica de extracción de URL
            return window._extraerURLImagen(img);
        } else {
            // Si es objeto, usar la función de extracción que maneja ruta_webp, ruta_original, etc.
            return window._extraerURLImagen(img);
        }
    });
    
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
    imagen.src = imagenesNormalizadas[indiceActual];
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
        indiceActual = (indiceActual - 1 + imagenesNormalizadas.length) % imagenesNormalizadas.length;
        imagen.src = imagenesNormalizadas[indiceActual];
        contador.textContent = `${indiceActual + 1} / ${imagenesNormalizadas.length}`;
    };
    
    const contador = document.createElement('span');
    contador.textContent = `${indiceActual + 1} / ${imagenesNormalizadas.length}`;
    
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
        indiceActual = (indiceActual + 1) % imagenesNormalizadas.length;
        imagen.src = imagenesNormalizadas[indiceActual];
        contador.textContent = `${indiceActual + 1} / ${imagenesNormalizadas.length}`;
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

    
    // Capturar datos del formulario
    const datosFormulario = capturarDatosFormulario();
    
    if (!datosFormulario) {
        alert('Por favor completa los datos básicos del pedido');
        return;
    }
    

    
    // Crear modal con la vista previa
    crearModalPreviewFactura(datosFormulario);
};

/**
 * Captura los datos del formulario de creación de pedido
 */
function capturarDatosFormulario() {

    
    // Información básica
    const cliente = document.getElementById('cliente_editable')?.value || 'Cliente Nuevo';
    const asesora = document.getElementById('asesora_editable')?.value || 'Sin asignar';
    const formaPago = document.getElementById('forma_de_pago_editable')?.value || 'No especificada';
    
    if (!cliente || cliente.trim() === '') {

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
    

    return datos;
}

/**
 * Captura las prendas del formulario usando el GestorPrendaSinCotizacion
 */
function capturarPrendas() {

    
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

        // Intentar obtener del elemento data si existe
        const formElement = document.querySelector('[data-gestor-prendas]');
        if (formElement && formElement.__gestorPrendas) {
            gestor = formElement.__gestorPrendas;
        }
    }
    
    if (!gestor) {

        return prendas;
    }
    
    try {
        // Obtener todas las prendas del gestor
        const prendasDelGestor = gestor.obtenerActivas ? gestor.obtenerActivas() : 
                                 (gestor.prendas ? Object.values(gestor.prendas) : []);
        

        
        prendasDelGestor.forEach((prenda, index) => {
            // Extracción será hecha posteriormente desde prenda.tallas (estructura relacional única)
            
            // Extraer procesos con todos sus detalles
            const procesos = [];
            if (prenda.procesos && typeof prenda.procesos === 'object') {
                Object.entries(prenda.procesos).forEach(([key, proc]) => {
                    // Obtener los datos del proceso (pueden estar en proc.datos o directamente en proc)
                    const procDatos = proc?.datos || proc;
                    const procTipo = proc?.tipo || procDatos?.tipo;
                    
                    if (procTipo && procDatos) {
                        // Extraer tallas por género (estructura relacional: { GENERO: { TALLA: CANTIDAD } })
                        const tallasProceso = {};
                        if (procDatos.tallas && typeof procDatos.tallas === 'object' && !Array.isArray(procDatos.tallas)) {
                            Object.entries(procDatos.tallas).forEach(([genero, tallasObj]) => {
                                if (typeof tallasObj === 'object' && !Array.isArray(tallasObj) && Object.keys(tallasObj).length > 0) {
                                    tallasProceso[genero] = tallasObj;
                                }
                            });
                        }
                        
                        // Extraer ubicaciones como array si viene como string
                        let ubicaciones = procDatos.ubicaciones || [];
                        if (typeof ubicaciones === 'string') {
                            ubicaciones = [ubicaciones];
                        } else if (!Array.isArray(ubicaciones)) {
                            ubicaciones = [];
                        }
                        
                        // Extraer imágenes como array Y MAPEAR a URLs
                        let imagenes = procDatos.imagenes || [];
                        if (typeof imagenes === 'string') {
                            imagenes = [imagenes];
                        } else if (!Array.isArray(imagenes)) {
                            imagenes = [];
                        }
                        
                        //  MAPEAR imágenes para convertir File objects a URLs
                        imagenes = imagenes.map(img => {
                            if (img instanceof File) {
                                // Si es un File object, crear blob URL
                                return URL.createObjectURL(img);
                            } else if (img?.file instanceof File) {
                                // Si es un objeto con propiedad file
                                return URL.createObjectURL(img.file);
                            } else if (img?.blobUrl) {
                                // Si ya tiene blobUrl
                                return img.blobUrl;
                            } else if (img?.ruta_webp) {
                                // Si es un objeto con propiedad ruta_webp (desde API)
                                return '/storage/' + img.ruta_webp;
                            } else if (img?.ruta_original) {
                                // Si es un objeto con propiedad ruta_original (desde API)
                                return '/storage/' + img.ruta_original;
                            } else if (typeof img === 'string') {
                                // Si es una string URL
                                return img.startsWith('/storage/') ? img : '/storage/' + img;
                            } else if (img?.url) {
                                // Si es un objeto con propiedad url
                                return img.url;
                            } else if (img?.ruta) {
                                // Si es un objeto con propiedad ruta
                                return img.ruta;
                            } else if (img?.path) {
                                // Si es un objeto con propiedad path
                                return img.path;
                            } else if (img?.src) {
                                // Si tiene propiedad src
                                return img.src;
                            } else {
                                // Fallback: si es un objeto vacío o desconocido, retorna string vacío
                                return '';
                            }
                        }).filter(url => url); // Filtrar URLs vacías
                        
                        const procObj = {
                            tipo: procTipo,
                            ubicaciones: ubicaciones,
                            observaciones: procDatos.observaciones || '',
                            imagenes: imagenes,
                            tallas: tallasProceso
                        };
                        procesos.push(procObj);
                    }
                });
            }
            
            // Extraer datos de variantes si existen
            const variantes = prenda.variantes || {};
            const tipoManga = variantes.tipo_manga || prenda.tipo_manga || '';
            const obsManga = variantes.obs_manga || prenda.obs_manga || '';
            const tipoBroche = variantes.tipo_broche || prenda.tipo_broche || '';
            const obsBroche = variantes.obs_broche || prenda.obs_broche || '';
            const tieneBolsillos = variantes.tiene_bolsillos !== undefined ? variantes.tiene_bolsillos : (prenda.tiene_bolsillos || false);
            const obsBolsillos = variantes.obs_bolsillos || prenda.obs_bolsillos || '';
            const tienereflectivo = variantes.tiene_reflectivo !== undefined ? variantes.tiene_reflectivo : (prenda.tiene_reflectivo || false);
            
            // Calcular cantidad total desde tallasReconstruidas (estructura relacional)
            // Esto se hará DESPUÉS de reconstruir las tallas
            let cantidadTotal = 0;
            
            // Detectar imagen con múltiples fallbacks - EXTRAER src si es objeto
            let imagenCapturada = '';
            
            if (prenda.imagen) {
                imagenCapturada = typeof prenda.imagen === 'string' ? prenda.imagen : (prenda.imagen?.url || prenda.imagen?.ruta || prenda.imagen?.src || '');
            } else if (prenda.imagen_prenda) {
                imagenCapturada = typeof prenda.imagen_prenda === 'string' ? prenda.imagen_prenda : (prenda.imagen_prenda?.url || prenda.imagen_prenda?.ruta || prenda.imagen_prenda?.src || '');
            } else if (prenda.fotos && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {
                const firstFoto = prenda.fotos[0];
                imagenCapturada = typeof firstFoto === 'string' ? firstFoto : (firstFoto?.blobUrl || firstFoto?.src || firstFoto?.url || '');
            } else if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
                const firstImagen = prenda.imagenes[0];




                
                // El componente ImagenesFormDataComponent devuelve: {file, nombre, tamaño}
                // Sin previewUrl (blob URLs son efímeros)
                // Generar blob URL on-demand si existe File object
                if (firstImagen?.file instanceof File) {
                    imagenCapturada = URL.createObjectURL(firstImagen.file);
                } else {
                    // Fallback a otras propiedades - incluir url y ruta
                    imagenCapturada = firstImagen?.previewUrl || firstImagen?.blobUrl || firstImagen?.src || firstImagen?.url || firstImagen?.ruta || firstImagen?.path || firstImagen?.data || (typeof firstImagen === 'string' ? firstImagen : '');
                }
            }
            
            // Detectar imagen de tela con múltiples fallbacks - EXTRAER src si es objeto
            let imagenTelaCapturada = '';
            
            if (prenda.imagen_tela) {
                imagenTelaCapturada = typeof prenda.imagen_tela === 'string' ? prenda.imagen_tela : (prenda.imagen_tela?.url || prenda.imagen_tela?.ruta || prenda.imagen_tela?.src || '');
            } else if (prenda.muestra_tela) {
                imagenTelaCapturada = typeof prenda.muestra_tela === 'string' ? prenda.muestra_tela : (prenda.muestra_tela?.url || prenda.muestra_tela?.ruta || prenda.muestra_tela?.src || '');
            } else if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela) && prenda.imagenes_tela.length > 0) {
                const firstTela = prenda.imagenes_tela[0];
                imagenTelaCapturada = typeof firstTela === 'string' ? firstTela : (firstTela?.url || firstTela?.ruta || firstTela?.blobUrl || firstTela?.src || firstTela?.path || firstTela?.data || '');
            } else if (prenda.telaFotos && Array.isArray(prenda.telaFotos) && prenda.telaFotos.length > 0) {
                const firstTelaFoto = prenda.telaFotos[0];
                imagenTelaCapturada = typeof firstTelaFoto === 'string' ? firstTelaFoto : (firstTelaFoto?.url || firstTelaFoto?.ruta || firstTelaFoto?.blobUrl || firstTelaFoto?.src || firstTelaFoto?.path || firstTelaFoto?.data || '');
            } else if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0 && 
                       prenda.telasAgregadas[0].imagenes && Array.isArray(prenda.telasAgregadas[0].imagenes)) {
                const firstTelaAg = prenda.telasAgregadas[0].imagenes[0];
                
                // Generar blob URL on-demand si es File object
                if (firstTelaAg instanceof File) {
                    imagenTelaCapturada = URL.createObjectURL(firstTelaAg);
                } else {
                    // Fallback si es objeto con propiedades
                    imagenTelaCapturada = typeof firstTelaAg === 'string' ? firstTelaAg : (firstTelaAg?.blobUrl || firstTelaAg?.previewUrl || firstTelaAg?.src || firstTelaAg?.url || firstTelaAg?.data || '');
                }
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

                        } else {
                            imagenTelaCapturada = typeof telaImg === 'string' ? telaImg : (telaImg?.blobUrl || telaImg?.previewUrl || telaImg?.src || telaImg?.url || '');

                        }
                    }
                }
            }
            
            // EXTRAER TALLAS - ÚNICA FUENTE VÁLIDA: prenda.tallas
            // Estructura relacional esperada: { GENERO: { TALLA: CANTIDAD } }
            let tallasReconstruidas = {};
            
            if (prenda.tallas && typeof prenda.tallas === 'object' && !Array.isArray(prenda.tallas) && Object.keys(prenda.tallas).length > 0) {
                // Copiar directamente - es la estructura correcta
                Object.entries(prenda.tallas).forEach(([genero, tallasObj]) => {
                    if (typeof tallasObj === 'object' && !Array.isArray(tallasObj) && Object.keys(tallasObj).length > 0) {
                        tallasReconstruidas[genero] = tallasObj;
                    }
                });
            }
            

            
            // Calcular cantidad total desde tallasReconstruidas (estructura relacional: {GENERO: {TALLA: CANTIDAD}})
            cantidadTotal = Object.values(tallasReconstruidas).reduce((sum, generoTallas) => {
                if (typeof generoTallas === 'object' && !Array.isArray(generoTallas)) {
                    return sum + Object.values(generoTallas).reduce((s, cant) => s + (parseInt(cant) || 0), 0);
                }
                return sum;
            }, 0);
            
            prendas.push({
                numero: index + 1,
                nombre: prenda.nombre_producto || prenda.nombre || `Prenda ${index + 1}`,
                descripcion: prenda.descripcion || '',
                ref: refCapturada,
                imagen: imagenCapturada,
                imagenes: prenda.imagenes && Array.isArray(prenda.imagenes) ? prenda.imagenes.map(img => {
                    console.log('[INVOICE-PREVIEW] Procesando imagen de prenda:', {
                        tipo: typeof img,
                        esFile: img instanceof File,
                        propiedades: typeof img === 'object' ? Object.keys(img) : 'N/A',
                        url: img?.url,
                        ruta: img?.ruta,
                        blobUrl: img?.blobUrl,
                        previewUrl: img?.previewUrl,
                        src: img?.src,
                        stringValue: typeof img === 'string' ? img : 'N/A'
                    });
                    if (img instanceof File) {
                        return URL.createObjectURL(img);
                    }
                    const rutaFinal = img.blobUrl || img.previewUrl || img.src || img.url || img.ruta || img;

                    return rutaFinal;
                }) : (imagenCapturada ? [imagenCapturada] : []),
                imagen_tela: imagenTelaCapturada,
                // Extraer imagenes_tela sin ternarios anidados
                imagenes_tela: (() => {
                    let imagenesTelaArr = [];
                    if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
                        imagenesTelaArr = prenda.imagenes_tela.map(img => {
                            console.log('[INVOICE-PREVIEW] Procesando imagen de tela:', {
                                tipo: typeof img,
                                esFile: img instanceof File,
                                propiedades: typeof img === 'object' ? Object.keys(img) : 'N/A',
                                url: img?.url,
                                ruta: img?.ruta,
                                blobUrl: img?.blobUrl,
                                previewUrl: img?.previewUrl,
                                src: img?.src,
                                stringValue: typeof img === 'string' ? img : 'N/A'
                            });
                            if (img instanceof File) {
                                return URL.createObjectURL(img);
                            }
                            const rutaFinal = img.blobUrl || img.previewUrl || img.src || img.url || img.ruta || img;
                            return rutaFinal;
                        });
                    } else if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
                        imagenesTelaArr = prenda.telasAgregadas
                            .filter(t => t.imagenes && t.imagenes.length > 0)
                            .flatMap(t => t.imagenes.map(img => {
                                console.log('[INVOICE-PREVIEW] Procesando imagen de tela agregada:', {
                                    tipo: typeof img,
                                    esFile: img instanceof File,
                                    propiedades: typeof img === 'object' ? Object.keys(img) : 'N/A',
                                    url: img?.url,
                                    ruta: img?.ruta,
                                    blobUrl: img?.blobUrl,
                                    previewUrl: img?.previewUrl,
                                    src: img?.src,
                                    stringValue: typeof img === 'string' ? img : 'N/A'
                                });
                                if (img instanceof File) {
                                    return URL.createObjectURL(img);
                                }
                                const rutaFinal = img.blobUrl || img.previewUrl || img.src || img.url || img.ruta || img;
                                return rutaFinal;
                            }));
                    } else if (imagenTelaCapturada) {
                        imagenesTelaArr = [imagenTelaCapturada];
                    }
                    return imagenesTelaArr;
                })(),
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
        });
    } catch (error) {

    }
    

    return prendas;
}

/**
 * Captura los procesos seleccionados globales
 * Nota: Los procesos por prenda se capturan en capturarPrendas()
 * Esta función captura procesos globales aplicables a todo el pedido
 */
function capturarProcesos() {

    
    const procesos = [];
    
    // Buscar procesos globales en el formulario (si existen)
    const procesosCheckboxes = document.querySelectorAll('input[type="checkbox"][name*="proceso_general"], input[type="checkbox"][name*="process_general"], input[type="checkbox"][name*="procesos"]');
    
    procesosCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const label = document.querySelector(`label[for="${checkbox.id}"]`)?.textContent || checkbox.value;
            procesos.push(label.trim());
        }
    });
    

    return procesos;
}

/**
 * Captura el EPP seleccionado del DOM
 * Lee los items EPP agregados en la lista lista-items-pedido
 */
function capturarEPP() {

    
    const epp = [];
    
    // Obtener todos los items EPP del DOM
    const itemsEPP = document.querySelectorAll('.item-epp[data-item-tipo="epp"]');
    
    itemsEPP.forEach(item => {
        const id = item.dataset.itemId;
        const categoria = item.querySelector('[style*="color: #0066cc"]')?.textContent || '';
        const nombre = item.querySelector('h4')?.textContent || '';
        
        // Extraer información de la etiqueta p que contiene código, talla y cantidad
        const infoTexto = item.querySelector('p[style*="color: #6b7280"]')?.textContent || '';
        
        // Extraer imágenes
        const imagenesDiv = item.querySelector('[style*="grid-template-columns"]');
        const imagenes = [];
        if (imagenesDiv) {
            const imgs = imagenesDiv.querySelectorAll('img');
            imgs.forEach(img => {
                if (img.src) imagenes.push(img.src);
            });
        }
        
        epp.push({
            id: id,
            nombre: nombre,
            categoria: categoria,
            info: infoTexto,
            imagenes: imagenes
        });
    });
    

    return epp;
}

/**
 * Crea un modal con la vista previa de la factura
 */
function crearModalPreviewFactura(datos) {

    
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
                     Pedido #${datos.numero_pedido || datos.numero_pedido_temporal} | ${datos.cliente}
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
    

}

/**
 * Genera el HTML de la factura con los datos en tiempo real
 */
function generarHTMLFactura(datos) {

    
    // Validar que datos y prendas existan
    if (!datos || !datos.prendas || !Array.isArray(datos.prendas)) {

        return '<div style="color: #dc2626; padding: 1rem; border: 1px solid #fca5a5; border-radius: 6px; background: #fee2e2;">❌ Error: No se pudieron cargar las prendas del pedido. Estructura de datos inválida.</div>';
    }

    // Si no hay prendas, mostrar mensaje
    if (datos.prendas.length === 0) {

        return '<div style="color: #f59e0b; padding: 1rem; border: 1px solid #fed7aa; border-radius: 6px; background: #fffbeb;">⚠️ Advertencia: El pedido no contiene prendas.</div>';
    }
    
    // Generar las tarjetas de prendas con todos los detalles
    const prendasHTML = datos.prendas.map((prenda, idx) => {
        console.log(`[FACTURA-PRENDA-${idx}] Prenda:`, prenda);
        console.log(`[FACTURA-PRENDA-${idx}] Tiene variantes array?`, Array.isArray(prenda.variantes), prenda.variantes?.length);
        console.log(`[FACTURA-PRENDA-${idx}] Tiene tallas?`, prenda.tallas ? Object.keys(prenda.tallas) : 'NO');
        console.log(`[FACTURA-PRENDA-${idx}] Manga:`, prenda.manga);
        console.log(`[FACTURA-PRENDA-${idx}] Broche:`, prenda.broche);
        console.log(`[FACTURA-PRENDA-${idx}] Bolsillos:`, prenda.tiene_bolsillos);
        
        // Tabla de Variantes (Tallas con especificaciones)
        let variantesHTML = '';
        
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            console.log(`[FACTURA-PRENDA-${idx}] ✅ Usando variantes del servidor`);
            console.log(`[FACTURA-PRENDA-${idx}] Variantes completas:`, prenda.variantes);
            
            // Variantes desde el servidor (tienen estructura completa)
            // Agrupar variantes por talla para crear tabla
            const variantesAgrupadas = {};
            prenda.variantes.forEach(var_item => {
                console.log(`[FACTURA-PRENDA-${idx}] Procesando variante:`, {
                    talla: var_item.talla,
                    cantidad: var_item.cantidad,
                    manga: var_item.manga,
                    manga_obs: var_item.manga_obs,
                    broche: var_item.broche,
                    broche_obs: var_item.broche_obs,
                    bolsillos: var_item.bolsillos,
                    bolsillos_obs: var_item.bolsillos_obs
                });
                
                if (!variantesAgrupadas[var_item.talla]) {
                    variantesAgrupadas[var_item.talla] = {
                        cantidad_total: 0,
                        especificaciones: []
                    };
                }
                variantesAgrupadas[var_item.talla].cantidad_total += var_item.cantidad || 0;
                variantesAgrupadas[var_item.talla].especificaciones.push(var_item);
            });
            
            variantesHTML = `
                <div style="margin: 12px 0; padding: 0; background: #ffffff; border-radius: 6px; border: 1px solid #e0e7ff; overflow: hidden;">
                    <div style="font-size: 11px !important; font-weight: 700; color: #1e40af; background: #eff6ff; margin: 0; padding: 12px 12px; border-bottom: 2px solid #bfdbfe;"> VARIANTES (Tallas con Especificaciones)</div>
                    <table style="width: 100%; font-size: 10px !important; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f0f9ff; border-bottom: 2px solid #bfdbfe;">
                                <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Talla</th>
                                <th style="padding: 8px 12px; text-align: center; font-weight: 600; color: #1e40af;">Cantidad</th>
                                <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Manga</th>
                                <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Broche</th>
                                <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Bolsillos</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${Object.entries(variantesAgrupadas).map(([talla, data], tallaIdx) => {
                                const firstSpec = data.especificaciones[0];
                                return `
                                    <tr style="background: ${tallaIdx % 2 === 0 ? '#ffffff' : '#f8fafc'}; border-bottom: 1px solid #e0e7ff;">
                                        <td style="padding: 8px 12px; font-weight: 600; color: #334155;">${talla}</td>
                                        <td style="padding: 8px 12px; text-align: center; color: #475569;">${data.cantidad_total}</td>
                                        <td style="padding: 8px 12px; color: #475569; font-size: 9px !important;">
                                            ${firstSpec.manga ? `<strong>${firstSpec.manga}</strong>` : '—'}
                                            ${firstSpec.manga_obs ? `<br><em style="color: #64748b;">${firstSpec.manga_obs}</em>` : ''}
                                        </td>
                                        <td style="padding: 8px 12px; color: #475569; font-size: 9px !important;">
                                            ${firstSpec.broche ? `<strong>${firstSpec.broche}</strong>` : '—'}
                                            ${firstSpec.broche_obs ? `<br><em style="color: #64748b;">${firstSpec.broche_obs}</em>` : ''}
                                        </td>
                                        <td style="padding: 8px 12px; color: #475569; font-size: 9px !important;">
                                            ${firstSpec.bolsillos ? `<strong>Sí</strong>` : '—'}
                                            ${firstSpec.bolsillos_obs ? `<br><em style="color: #64748b;">${firstSpec.bolsillos_obs}</em>` : ''}
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else if (prenda.tallas && typeof prenda.tallas === 'object' && Object.keys(prenda.tallas).length > 0) {
            console.log(`[FACTURA-PRENDA-${idx}] ✅ Usando tallas locales del formulario`);
            // Datos desde el formulario local (estructura relacional de tallas por género)
            // Crear variantes simplificadas con el manga/broche/bolsillos de la prenda
            let variantesSimples = [];
            
            Object.entries(prenda.tallas).forEach(([genero, tallasObj]) => {
                if (typeof tallasObj === 'object') {
                    Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                        variantesSimples.push({
                            talla: talla,
                            cantidad: cantidad,
                            manga: prenda.manga || '',
                            manga_obs: prenda.obs_manga || '',
                            broche: prenda.broche || '',
                            broche_obs: prenda.obs_broche || '',
                            bolsillos: prenda.tiene_bolsillos || false,
                            bolsillos_obs: prenda.obs_bolsillos || ''
                        });
                    });
                }
            });
            
            console.log(`[FACTURA-PRENDA-${idx}] Variantes simples creadas:`, variantesSimples);
            
            if (variantesSimples.length > 0) {
                variantesHTML = `
                    <div style="margin: 12px 0; padding: 0; background: #ffffff; border-radius: 6px; border: 1px solid #e0e7ff; overflow: hidden;">
                        <div style="font-size: 11px !important; font-weight: 700; color: #1e40af; background: #eff6ff; margin: 0; padding: 12px 12px; border-bottom: 2px solid #bfdbfe;"> VARIANTES (Tallas con Especificaciones)</div>
                        <table style="width: 100%; font-size: 10px !important; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f0f9ff; border-bottom: 2px solid #bfdbfe;">
                                    <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Talla</th>
                                    <th style="padding: 8px 12px; text-align: center; font-weight: 600; color: #1e40af;">Cantidad</th>
                                    <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Manga</th>
                                    <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Broche</th>
                                    <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Bolsillos</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${variantesSimples.map((var_item, idx) => `
                                    <tr style="background: ${idx % 2 === 0 ? '#ffffff' : '#f8fafc'}; border-bottom: 1px solid #e0e7ff;">
                                        <td style="padding: 8px 12px; font-weight: 600; color: #334155;">${var_item.talla}</td>
                                        <td style="padding: 8px 12px; text-align: center; color: #475569;">${var_item.cantidad}</td>
                                        <td style="padding: 8px 12px; color: #475569; font-size: 9px !important;">
                                            ${var_item.manga ? `<strong>${var_item.manga}</strong>` : '—'}
                                            ${var_item.manga_obs ? `<br><em style="color: #64748b;">${var_item.manga_obs}</em>` : ''}
                                        </td>
                                        <td style="padding: 8px 12px; color: #475569; font-size: 9px !important;">
                                            ${var_item.broche ? `<strong>${var_item.broche}</strong>` : '—'}
                                            ${var_item.broche_obs ? `<br><em style="color: #64748b;">${var_item.broche_obs}</em>` : ''}
                                        </td>
                                        <td style="padding: 8px 12px; color: #475569; font-size: 9px !important;">
                                            ${var_item.bolsillos ? `<strong>Sí</strong>` : '—'}
                                            ${var_item.bolsillos_obs ? `<br><em style="color: #64748b;">${var_item.bolsillos_obs}</em>` : ''}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                console.log(`[FACTURA-PRENDA-${idx}] ⚠️ No hay variantes simples`);
            }
        } else {
            console.log(`[FACTURA-PRENDA-${idx}] ⚠️ No hay variantes ni tallas`);
        }
        
        // Especificaciones principales (Tabla compacta) - MANTENER PARA COMPATIBILIDAD
        const variacionesArray = [
            prenda.manga ? { nombre: 'Manga', valor: prenda.manga, obs: prenda.obs_manga } : null,
            prenda.broche ? { nombre: 'Broche', valor: prenda.broche, obs: prenda.obs_broche } : null,
            prenda.tiene_bolsillos && prenda.obs_bolsillos ? { nombre: 'Bolsillo', valor: '', obs: prenda.obs_bolsillos } : null
        ].filter(v => v !== null);
        
        const especificacionesHTML = (variacionesArray.length > 0 && !variantesHTML) ? `
            <div style="margin: 12px 0; padding: 0; background: #ffffff; border-radius: 6px; border: 1px solid #e0e7ff; overflow: hidden;">
                <div style="font-size: 11px !important; font-weight: 700; color: #1e40af; background: #eff6ff; margin: 0; padding: 12px 12px; border-bottom: 2px solid #bfdbfe;"> ESPECIFICACIONES</div>
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
                ${(prenda.imagenes_tela && prenda.imagenes_tela.length > 0) ? `
                    <div>
                        <div style="font-size: 10px; text-transform: uppercase; color: #999; margin-bottom: 4px; font-weight: 700;">Muestra Tela</div>
                        <img src="${window._extraerURLImagen(prenda.imagenes_tela[0])}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                ` : ''}
            </div>
            <div style="height: 1px; background: #e0e0e0; margin-bottom: 15px;"></div>
        ` : '';
        
        // Tallas por género (estructura relacional: { GENERO: { TALLA: CANTIDAD } })
        let generosTallasHTML = '';
        let totalItems = 0;
        
        if (prenda.tallas && typeof prenda.tallas === 'object' && Object.keys(prenda.tallas).length > 0) {
            // Iterar por géneros y filtrar aquellos con tallas
            const generosConTallas = Object.entries(prenda.tallas).filter(([gen, tallasObj]) => 
                typeof tallasObj === 'object' && !Array.isArray(tallasObj) && Object.keys(tallasObj).length > 0
            );
            
            if (generosConTallas.length > 0) {
                // Calcular total_items sumando todas las cantidades
                totalItems = generosConTallas.reduce((sum, [gen, tallasObj]) => {
                    return sum + Object.values(tallasObj).reduce((s, cant) => s + (parseInt(cant) || 0), 0);
                }, 0);
                
                generosTallasHTML = `
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px; table-layout: fixed;">
                        <tbody>
                            ${generosConTallas.map(([genero, tallasObj]) => `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 4px 4px; font-weight: 600; color: #374151; width: 35%; word-break: break-word; font-size: 11px; overflow: hidden;">${genero}</td>
                                    <td style="padding: 4px 4px; color: #374151; word-break: break-word; overflow: hidden; font-size: 11px; font-weight: 600;">${Object.entries(tallasObj).map(([talla, cant]) => `${talla}:${cant}`).join(', ')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else {
                generosTallasHTML = '<span style="color: #999; font-size: 9px;">Sin tallas</span>';
            }
        } else {
            generosTallasHTML = '<span style="color: #999; font-size: 9px;">Sin tallas</span>';
        }
        
        // Procesos
        const procesosListaHTML = prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0
            ? prenda.procesos.map(proc => {
                // Renderizar tallas del proceso (estructura relacional: { GENERO: { TALLA: CANTIDAD } })
                let tallasProcHTML = '';
                if (proc.tallas && Object.keys(proc.tallas).length > 0) {
                    // Por género - FILTRAR géneros vacíos
                    const generosConTallasProc = Object.entries(proc.tallas).filter(([gen, tallasObj]) => 
                        typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0
                    );
                    
                    if (generosConTallasProc.length > 0) {
                        tallasProcHTML = `
                            <div style="margin: 2px 0; padding: 4px; background: white; border-radius: 2px; font-size: 10px;">
                                ${generosConTallasProc.map(([gen, tallasObj]) => {
                                    return `<div><strong>${gen}:</strong> ${Object.entries(tallasObj).map(([t, c]) => `${t}:${c}`).join(', ')}</div>`;
                                }).join('')}
                            </div>
                        `;
                    }
                }
                
                return `
                    <div style="background: #f9f9f9; padding: 6px; margin: 4px 0; border-left: 3px solid #9ca3af; border-radius: 2px; font-size: 10px;">
                        <div style="font-weight: 700; color: #3b82f6; margin-bottom: 4px; text-transform: uppercase;">Reflectivo: ${proc.tipo || 'Proceso sin tipo'}</div>
                        
                        ${(proc.ubicaciones?.length > 0 || proc.observaciones) ? `
                            <table style="width: 100%; font-size: 10px; margin-bottom: 4px; border-collapse: collapse;">
                                ${proc.ubicaciones && proc.ubicaciones.length > 0 ? `
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 2px 3px; font-weight: 600; color: #6b7280; width: 25%;">Ubicación:</td>
                                        <td style="padding: 2px 3px;">${proc.ubicaciones.join(', ')}</td>
                                    </tr>
                                ` : ''}
                                ${proc.observaciones ? `
                                    <tr>
                                        <td style="padding: 2px 3px; font-weight: 600; color: #6b7280; width: 25%;">Observaciones:</td>
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
                                        <img src="${window._extraerURLImagen(proc.imagenes[0])}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 2px; border: 1px solid #ddd;">
                                        ${proc.imagenes.length > 1 ? `
                                            <div style="position: absolute; top: 0; right: 0; background: #3b82f6; color: white; font-size: 9px; font-weight: 700; padding: 2px 4px; border-radius: 0 2px 0 2px; cursor: pointer;">
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
                <!-- ENCABEZADO CON NUMERACIÓN DE PRENDA -->
                <div style="background: #f0f0f0; padding: 6px 8px; margin: -8px -8px 8px -8px; border-radius: 3px 3px 0 0; border-bottom: 2px solid #2c3e50;">
                    <span style="font-weight: 700; color: #2c3e50; font-size: 11px;"> PRENDA ${idx + 1}</span>
                </div>
                
                <!-- LAYOUT 4 COLUMNAS PRINCIPALES -->
                <div style="display: grid; grid-template-columns: 160px 180px 180px 160px; gap: 12px;">
                    
                    <!-- COLUMNA 1: Imagen + Nombre/Descripción -->
                    <div style="display: flex; gap: 8px; align-items: flex-start;">
                        <div style="flex-shrink: 0;">
                            ${(prenda.imagenes && prenda.imagenes.length > 0) ? `
                                <img src="${window._extraerURLImagen(prenda.imagenes[0])}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 3px; border: 1px solid #ddd; cursor: pointer;" onclick="window._abrirGaleriaImagenesDesdeID(${window._registrarGalería(prenda.imagenes, 'Imágenes de Prenda')})" title="Click para ver todas las imágenes">
                            ` : `
                                <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 3px; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; font-size: 32px;"></div>
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
                        ${(prenda.imagenes_tela && prenda.imagenes_tela.length > 0) ? `
                            <div>
                                <img src="${window._extraerURLImagen(prenda.imagenes_tela[0])}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 2px; border: 1px solid #ddd; cursor: pointer;" onclick="window._abrirGaleriaImagenesDesdeID(${window._registrarGalería(prenda.imagenes_tela, 'Imágenes de Tela')})" title="Click para ver todas las imágenes de tela">
                            </div>
                        ` : ''}
                    </div>
                    
                    <!-- COLUMNA 3: Variantes (Manga, Broche, Bolsillos) -->
                    <div style="font-size: 10px;">
                        ${(() => {
                            // Si viene de la BD (tiene array de variantes)
                            if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
                                const firstVar = prenda.variantes[0];
                                const specs = [];
                                
                                if (firstVar.manga) {
                                    specs.push(`<div><strong>Manga:</strong> ${firstVar.manga}${firstVar.manga_obs ? ` <span style="color: #64748b; font-style: italic;">(${firstVar.manga_obs})</span>` : ''}</div>`);
                                }
                                if (firstVar.broche) {
                                    specs.push(`<div><strong>Broche:</strong> ${firstVar.broche}${firstVar.broche_obs ? ` <span style="color: #64748b; font-style: italic;">(${firstVar.broche_obs})</span>` : ''}</div>`);
                                }
                                if (firstVar.bolsillos) {
                                    specs.push(`<div><strong>Bolsillo:</strong> Sí${firstVar.bolsillos_obs ? ` <span style="color: #64748b; font-style: italic;">(${firstVar.bolsillos_obs})</span>` : ''}</div>`);
                                }
                                
                                return specs.length > 0 ? specs.join('') : '<span style="color: #999; font-size: 9px;">Sin especificaciones</span>';
                            }
                            // Si viene del formulario (estructura antigua)
                            else if (prenda.manga || prenda.broche || prenda.tiene_bolsillos) {
                                const specs = [];
                                
                                if (prenda.manga) {
                                    specs.push(`<div><strong>Manga:</strong> ${prenda.manga}${prenda.obs_manga ? ` <span style="color: #64748b; font-style: italic;">(${prenda.obs_manga})</span>` : ''}</div>`);
                                }
                                if (prenda.broche) {
                                    specs.push(`<div><strong>Broche:</strong> ${prenda.broche}${prenda.obs_broche ? ` <span style="color: #64748b; font-style: italic;">(${prenda.obs_broche})</span>` : ''}</div>`);
                                }
                                if (prenda.tiene_bolsillos) {
                                    specs.push(`<div><strong>Bolsillo:</strong> Sí${prenda.obs_bolsillos ? ` <span style="color: #64748b; font-style: italic;">(${prenda.obs_bolsillos})</span>` : ''}</div>`);
                                }
                                
                                return specs.join('');
                            } else {
                                return '<span style="color: #999; font-size: 9px;">Sin variantes</span>';
                            }
                        })()}
                    </div>
                    
                    <!-- COLUMNA 4: Tallas por Género -->
                    <div style="font-size: 10px;">
                        ${generosTallasHTML}
                    </div>
                </div>
                
                <!-- FILA INFERIOR: Procesos -->
                ${procesosListaHTML ? `
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee;">
                        <div style="font-size: 10px; font-weight: 700; color: #2c3e50; margin-bottom: 4px;"> Procesos ${prenda.procesos && Array.isArray(prenda.procesos) ? `(${prenda.procesos.length})` : ''}</div>
                        ${procesosListaHTML}
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
    
    return `
        <div style="background: white; padding: 8px; border-radius: 4px; max-width: 100%; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px;">
            <!-- Header Profesional COMPACTO -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 2px solid #ddd; align-items: start;">
                <!-- Lado Izquierdo: Cliente y Asesor -->
                <div style="font-size: 10px;">
                    <div style="font-weight: 700; color: #1a3a52; font-size: 11px; margin-bottom: 2px;">${datos.cliente}</div>
                    <div style="color: #666; font-size: 9px;">Asesor: ${datos.asesora}</div>
                </div>
                
                <!-- Lado Derecho: Recibo de Pedido -->
                <div style="text-align: right; font-size: 10px;">
                    <div style="font-weight: 700; color: #1a3a52; font-size: 11px; margin-bottom: 2px;">
                        RECIBO DE PEDIDO #${datos.numero_pedido || datos.numero_pedido_temporal}
                    </div>
                    <div style="color: #666; font-size: 9px;">${datos.fecha_creacion}</div>
                </div>
            </div>
            
            <!-- Items (Prendas) -->
            <div style="margin-top: 6px;">
                ${prendasHTML}
            </div>
            
            <!-- EPP Items -->
            ${datos.epps && datos.epps.length > 0 ? `
                <div style="margin-top: 12px; padding-top: 12px; border-top: 2px solid #6b7280;">
                    <div style="font-weight: 700; color: #374151; font-size: 11px; margin-bottom: 8px;">
                        EQUIPO DE PROTECCIÓN PERSONAL (${datos.epps.length})
                    </div>
                    ${datos.epps.map((epp, idx) => `
                        <div style="background: white; border: 1px solid #d1d5db; border-left: 4px solid #6b7280; padding: 8px; border-radius: 4px; margin-bottom: 8px; page-break-inside: avoid;">
                            <!-- HEADER EPP -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                                <!-- COLUMNA 1: Nombre -->
                                <div style="font-size: 11px;">
                                    <div style="font-weight: 700; color: #374151; margin-bottom: 2px;">${epp.epp_nombre || 'Sin nombre'}</div>
                                </div>
                                
                                <!-- COLUMNA 2: Cantidad -->
                                <div style="font-size: 11px;">
                                    <div style="color: #6b7280; font-size: 9px; text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">Cantidad</div>
                                    <div style="font-weight: 600; color: #374151;"><strong>${epp.cantidad || 0}</strong></div>
                                </div>
                            </div>
                            
                            <!-- Observaciones (si hay) -->
                            ${epp.observaciones ? `
                                <div style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #e5e7eb;">
                                    <div style="color: #6b7280; font-size: 9px; text-transform: uppercase; margin-bottom: 2px; font-weight: 600;">Observaciones</div>
                                    <div style="color: #555; font-size: 10px; font-style: italic;">${epp.observaciones}</div>
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * Guarda el HTML de la factura
 */
function guardarComoHTML(nombreArchivo) {

    
    const contenido = document.getElementById('preview-content').innerHTML;
    const elemento = document.createElement('a');
    
    elemento.setAttribute('href', 'data:text/html;charset=utf-8,' + encodeURIComponent(contenido));
    elemento.setAttribute('download', nombreArchivo);
    elemento.style.display = 'none';
    
    document.body.appendChild(elemento);
    elemento.click();
    document.body.removeChild(elemento);
    

}

// ========================================
// AGREGAR BOTÓN A FORMULARIO
// ========================================

/**
 * Agregar botón de vista previa al formulario
 */
