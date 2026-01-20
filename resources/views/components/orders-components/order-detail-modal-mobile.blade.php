<link rel="stylesheet" href="{{ asset('css/order-detail-modal-mobile.css') }}">

<div class="order-detail-modal-container" style="max-width: 100%; padding: 0.5rem;">
    <div class="order-detail-card" style="position: relative;">
        <!-- Logo -->
        <img src="{{ asset('images/logo.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
        
        <!-- Botón de navegación (esquina superior derecha) - FUERA de la descripción -->
        <div id="arrow-container-mobile" style="position: absolute; top: 15px; right: 15px; display: none; z-index: 100;"></div>
        
        <!-- Fecha -->
        <div id="order-date" class="order-date">
            <div class="fec-label">FECHA</div>
            <div class="date-boxes">
                <div class="date-box day-box" id="fecha-dia"></div>
                <div class="date-box month-box" id="fecha-mes"></div>
                <div class="date-box year-box" id="fecha-year"></div>
            </div>
        </div>
        
        <!-- Información Básica -->
        <div id="order-asesora" class="order-asesora">ASESORA: <span id="mobile-asesora"></span></div>
        <div id="order-forma-pago" class="order-forma-pago">FORMA DE PAGO: <span id="mobile-forma-pago"></span></div>
        <div id="order-cliente" class="order-cliente">CLIENTE: <span id="mobile-cliente"></span></div>
        
        <!-- Descripción -->
        <div id="order-descripcion" class="order-descripcion">
            <div id="mobile-descripcion"></div>
        </div>
        
        <!-- Título Recibo -->
        <h2 class="receipt-title">RECIBO DE COSTURA</h2>
        
        <!-- Número Pedido -->
        <div class="pedido-number" id="mobile-numero-pedido"></div>

        <!-- Separador -->
        <div class="separator-line"></div>

        <!-- Footer -->
        <div class="signature-section">
            <div class="signature-field">
                <span>ENCARGADO DE ORDEN:</span>
                <span id="mobile-encargado"></span>
            </div>
            <div class="signature-field">
                <span>PRENDAS ENTREGADAS:</span>
                <span id="mobile-prendas-entregadas"></span>
            </div>
        </div>
    </div>
</div>

<script>
let allImagesMobile = [];
let currentImageIndexMobile = 0;
let currentPedidoNumeroMobile = null;

// Esta función será llamada desde ver-pedido.blade.php cuando se carguen las fotos
function loadGaleriaMobile(container) {
    // Obtener número de pedido
    const pedidoElement = document.getElementById('mobile-numero-pedido');
    console.log(' [GALERIA MOBILE] Elemento pedido:', pedidoElement);
    if (!pedidoElement) {
        console.error(' [GALERIA MOBILE] No se encontró elemento mobile-numero-pedido');
        return;
    }
    
    const pedidoText = pedidoElement.textContent;
    const pedidoMatch = pedidoText.match(/\d+/);
    const pedido = pedidoMatch ? pedidoMatch[0] : null;
    
    console.log(' [GALERIA MOBILE] Número de pedido extraído:', pedido);
    if (!pedido) {
        console.error(' [GALERIA MOBILE] No se pudo extraer número de pedido');
        return;
    }
    
    currentPedidoNumeroMobile = pedido;
    
    // Cargar imágenes
    const url = `/registros/${pedido}/images`;
    console.log(' [GALERIA MOBILE] Haciendo fetch a:', url);
    
    fetch(url)
        .then(response => {
            console.log(' [GALERIA MOBILE] Respuesta recibida:', response.status);
            return response.json();
        })
        .then(data => {
            console.log(' [GALERIA MOBILE] Datos recibidos:', data);
            console.log('📊 [GALERIA MOBILE] Total prendas:', data.prendas?.length || 0);
            
            // Construir array de todas las imágenes para el visor
            allImagesMobile = [];
            let html = '<div style="background: linear-gradient(135deg, #1e40af, #0ea5e9); padding: 12px; margin: 0; border-radius: 0; width: 100%; box-sizing: border-box; position: sticky; top: 0; z-index: 100;">';
            html += '<h2 style="text-align: center; margin: 0; font-size: 1.6rem; font-weight: 700; color: white; letter-spacing: 1px;">GALERIA</h2>';
            html += '</div>';
            html += '<div style="padding: 20px; flex: 1; overflow-y: auto;">';
            
            console.log('📦 [GALERIA MOBILE] Iniciando construcción de galería...');
            
            // Mostrar prendas con sus imágenes (separando fotos de prenda/tela de fotos de logo)
            let fotosLogo = [];
            
            if (data.prendas && data.prendas.length > 0) {
                data.prendas.forEach((prenda, idx) => {
                    if (prenda.imagenes && prenda.imagenes.length > 0) {
                        // Separar fotos de logo de las demás
                        const fotosPrendaTela = prenda.imagenes.filter(img => img.type !== 'logo');
                        const fotosLogoPrend = prenda.imagenes.filter(img => img.type === 'logo');
                        
                        console.log(`📍 [GALERIA MOBILE] PRENDA ${idx + 1}:`, {
                            nombre: prenda.nombre,
                            total_imagenes: prenda.imagenes.length,
                            fotos_prenda_tela: fotosPrendaTela.length,
                            fotos_logo: fotosLogoPrend.length
                        });
                        
                        // Guardar fotos de logo para mostrar al final
                        if (fotosLogoPrend.length > 0) {
                            fotosLogo.push({
                                prenda: prenda,
                                fotos: fotosLogoPrend
                            });
                        }
                        
                        // Mostrar solo fotos de prenda y tela
                        if (fotosPrendaTela.length > 0) {
                            const fotosAMostrar = fotosPrendaTela.slice(0, 4);
                            const fotosOcultas = Math.max(0, fotosPrendaTela.length - 4);
                            
                            html += `<div style="margin-bottom: 1.5rem; display: flex; gap: 12px; align-items: flex-start; padding: 0 20px;">
                                <div style="border-left: 4px solid #1e40af; padding-left: 12px; display: flex; flex-direction: column; justify-content: flex-start; min-width: 120px;">
                                    <h3 style="font-size: 0.65rem; font-weight: 700; color: #1e40af; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                                        PRENDA ${prenda.numero}:<br>${prenda.nombre.toUpperCase()}
                                    </h3>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex: 1;">`;
                            
                            fotosAMostrar.forEach(image => {
                                const imageIndex = allImagesMobile.length;
                                allImagesMobile.push(image);
                                
                                html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid #e5e5e5; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08);" 
                                    onmouseover="this.style.borderColor='#1e40af'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(30,64,175,0.2)';"
                                    onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                    onclick="openImageViewerMobile(${imageIndex})">
                                    <img src="${image.url}" alt="Foto ${prenda.nombre}" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>`;
                            });
                            
                            if (fotosOcultas > 0) {
                                const firstOccultaIndex = allImagesMobile.length;
                                fotosPrendaTela.slice(4).forEach(image => {
                                    allImagesMobile.push(image);
                                });
                                
                                html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #1e40af; cursor: pointer; border: 2px solid #1e40af; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center;" 
                                    onmouseover="this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(30,64,175,0.2)';"
                                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                    onclick="openImageViewerMobile(${firstOccultaIndex})">
                                    <span style="color: white; font-weight: bold; font-size: 18px;">+${fotosOcultas}</span>
                                </div>`;
                            }
                            
                            html += '</div></div>';
                        }
                    }
                });
                
                // Mostrar fotos de logo al final
                if (fotosLogo.length > 0) {
                    console.log(' [GALERIA MOBILE] Mostrando fotos de logo. Total grupos:', fotosLogo.length);
                    
                    fotosLogo.forEach(item => {
                        const fotosAMostrar = item.fotos.slice(0, 4);
                        const fotosOcultas = Math.max(0, item.fotos.length - 4);
                        
                        html += `<div style="margin-top: 1.5rem; display: flex; gap: 12px; align-items: flex-start; padding: 0 20px;">
                            <div style="border-left: 4px solid #dc2626; padding-left: 12px; display: flex; flex-direction: column; justify-content: flex-start; min-width: 120px;">
                                <h3 style="font-size: 0.65rem; font-weight: 700; color: #dc2626; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                                    LOGO/BORDADO
                                </h3>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex: 1;">`;
                        
                        fotosAMostrar.forEach(image => {
                            const imageIndex = allImagesMobile.length;
                            allImagesMobile.push(image);
                            
                            html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid #e5e5e5; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08);" 
                                onmouseover="this.style.borderColor='#dc2626'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(220,38,38,0.2)';"
                                onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                onclick="openImageViewerMobile(${imageIndex})">
                                <img src="${image.url}" alt="Foto Logo" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>`;
                        });
                        
                        if (fotosOcultas > 0) {
                            const firstOccultaIndex = allImagesMobile.length;
                            item.fotos.slice(4).forEach(image => {
                                allImagesMobile.push(image);
                            });
                            
                            html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #dc2626; cursor: pointer; border: 2px solid #dc2626; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center;" 
                                onmouseover="this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(220,38,38,0.2)';"
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                onclick="openImageViewerMobile(${firstOccultaIndex})">
                                <span style="color: white; font-weight: bold; font-size: 18px;">+${fotosOcultas}</span>
                            </div>`;
                        }
                        
                        html += '</div></div>';
                    });
                }
                
                console.log(' [GALERIA MOBILE] Total de imágenes cargadas:', allImagesMobile.length);
            } else {
                console.warn('⚠️ [GALERIA MOBILE] No hay imágenes para mostrar');
                html += '<p style="text-align: center; color: #999; padding: 2rem;">No hay imágenes para este pedido</p>';
            }
            
            html += '</div>';
            container.innerHTML = html;
            console.log(' [GALERIA MOBILE] HTML de galería generado y renderizado en el DOM');
        })
        .catch(error => {
            console.error(' [GALERIA MOBILE] Error al cargar imágenes:', error);
            container.innerHTML = '<p style="text-align: center; color: #999;">Error al cargar imágenes</p>';
        });
}

function openImageViewerMobile(index) {
    currentImageIndexMobile = index;
    console.log(' [VIEWER MOBILE] Abriendo imagen:', index);
    
    // Crear modal si no existe
    let modal = document.getElementById('image-viewer-modal-mobile');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'image-viewer-modal-mobile';
        document.body.appendChild(modal);
    }
    
    // Estilos del modal
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100001;
        animation: fadeIn 0.3s ease;
    `;
    
    // HTML del visor
    let html = `
        <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .image-viewer-container-mobile {
                position: relative;
                width: 90%;
                max-width: 900px;
                height: 80vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .image-viewer-content-mobile {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .image-viewer-content-mobile img {
                width: 700px;
                height: 700px;
                object-fit: contain;
                border-radius: 8px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            }
            @media (max-width: 900px) {
                .image-viewer-content-mobile img {
                    width: 500px;
                    height: 500px;
                }
            }
            @media (max-width: 600px) {
                .image-viewer-content-mobile img {
                    width: 350px;
                    height: 350px;
                }
            }
        </style>
        
        <div class="image-viewer-container-mobile">
            <button onclick="closeImageViewerMobile()" style="position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; background: rgba(255, 255, 255, 0.2); border: 2px solid white; color: white; cursor: pointer; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease;">
                ×
            </button>
            
            <div class="image-viewer-content-mobile">
                <img src="${allImagesMobile[index].url}" alt="Imagen ampliada">
            </div>
            
            <div style="position: absolute; top: 50%; width: 100%; display: flex; justify-content: space-between; padding: 0 20px; pointer-events: none; transform: translateY(-50%);">
                <button onclick="previousImageMobile()" ${index === 0 ? 'disabled' : ''} style="pointer-events: auto; width: 50px; height: 50px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); border: 2px solid white; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease;">
                    ‹
                </button>
                <button onclick="nextImageMobile()" ${index === allImagesMobile.length - 1 ? 'disabled' : ''} style="pointer-events: auto; width: 50px; height: 50px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); border: 2px solid white; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease;">
                    ›
                </button>
            </div>
            
            <div style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: white; background: rgba(0, 0, 0, 0.5); padding: 8px 16px; border-radius: 20px; font-size: 14px;">${index + 1} / ${allImagesMobile.length}</div>
        </div>
    `;
    
    modal.innerHTML = html;
    modal.style.display = 'flex';
}

function closeImageViewerMobile() {
    const modal = document.getElementById('image-viewer-modal-mobile');
    if (modal) {
        modal.style.display = 'none';
    }
}

function nextImageMobile() {
    if (currentImageIndexMobile < allImagesMobile.length - 1) {
        openImageViewerMobile(currentImageIndexMobile + 1);
    }
}

function previousImageMobile() {
    if (currentImageIndexMobile > 0) {
        openImageViewerMobile(currentImageIndexMobile - 1);
    }
}
</script>

<script>
// Función para llenar el recibo móvil
window.llenarReciboCosturaMobile = function(data) {
    console.log('🎨 === INICIANDO llenarReciboCosturaMobile ===');
    console.log('🎨 Datos recibidos:', data);
    console.log('🎨 data.fecha:', data.fecha);
    console.log('🎨 typeof data.fecha:', typeof data.fecha);
    
    // Fecha - parsear correctamente
    if (data.fecha && data.fecha !== 'N/A') {
        console.log('📅 Procesando fecha:', data.fecha);
        let fecha;
        
        // Intentar parsear diferentes formatos de fecha
        if (typeof data.fecha === 'string') {
            // Formato DD/MM/YYYY
            if (data.fecha.includes('/')) {
                const [day, month, year] = data.fecha.split('/');
                fecha = new Date(year, parseInt(month) - 1, day);
                console.log('📅 Formato DD/MM/YYYY - Day:', day, 'Month:', month, 'Year:', year);
            }
            // Formato YYYY-MM-DD o YYYY-MM-DD HH:MM:SS
            else if (data.fecha.includes('-')) {
                // Separar fecha de hora si existe
                const fechaParte = data.fecha.split(' ')[0];
                const [year, month, day] = fechaParte.split('-');
                fecha = new Date(year, parseInt(month) - 1, parseInt(day));
                console.log('📅 Formato YYYY-MM-DD - Year:', year, 'Month:', month, 'Day:', day);
            } else {
                fecha = new Date(data.fecha);
                console.log('📅 Formato default');
            }
        } else {
            fecha = new Date(data.fecha);
        }
        
        // Validar que sea una fecha válida
        if (!isNaN(fecha)) {
            console.log(' Fecha válida:', fecha);
            const dayBox = document.getElementById('fecha-dia');
            const monthBox = document.getElementById('fecha-mes');
            const yearBox = document.getElementById('fecha-year');
            
            console.log(' Elementos encontrados - dayBox:', !!dayBox, 'monthBox:', !!monthBox, 'yearBox:', !!yearBox);
            
            if (dayBox) {
                dayBox.textContent = fecha.getDate();
                console.log(' Día actualizado:', fecha.getDate());
            }
            if (monthBox) {
                monthBox.textContent = (fecha.getMonth() + 1);
                console.log(' Mes actualizado:', fecha.getMonth() + 1);
            }
            if (yearBox) {
                yearBox.textContent = fecha.getFullYear();
                console.log(' Año actualizado:', fecha.getFullYear());
            }
        } else {
            console.error(' Fecha inválida');
        }
    } else {
        console.log('⚠️ Sin fecha en data');
    }

    // Información básica
    console.log('📝 Llenando información básica...');
    const asesora = document.getElementById('mobile-asesora');
    const formaPago = document.getElementById('mobile-forma-pago');
    const cliente = document.getElementById('mobile-cliente');
    const numeroPedido = document.getElementById('mobile-numero-pedido');
    const encargado = document.getElementById('mobile-encargado');
    const prendasEntregadas = document.getElementById('mobile-prendas-entregadas');
    
    console.log('📝 Elementos encontrados - asesora:', !!asesora, 'forma_pago:', !!formaPago, 'cliente:', !!cliente, 'numero:', !!numeroPedido, 'encargado:', !!encargado, 'prendas:', !!prendasEntregadas);
    
    if (asesora) asesora.textContent = data.asesora || 'N/A';
    if (formaPago) formaPago.textContent = data.formaPago || 'N/A';
    if (cliente) cliente.textContent = data.cliente || 'N/A';
    if (numeroPedido) numeroPedido.textContent = '#' + (data.numeroPedido || '');
    if (encargado) encargado.textContent = data.encargado || '-';
    if (prendasEntregadas) prendasEntregadas.textContent = data.prendasEntregadas || '0/0';
    
    console.log(' Información básica actualizada');

    // Función helper para convertir markdown bold *** a <strong>
    const convertMarkdownBold = (texto) => {
        // Convertir ***texto*** a <strong>texto</strong>
        return texto.replace(/\*\*\*(.*?)\*\*\*/g, '<strong>$1</strong>')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    };

    // Inicializar índice del carrusel si no existe
    if (!window.prendaCarouselIndex) {
        window.prendaCarouselIndex = 0;
    }

    // Descripción - IGUAL QUE ASESORES: Priorizar descripcion_prendas del controlador
    console.log(' Procesando descripción...');
    console.log(' data.descripcion:', data.descripcion);
    console.log(' data.prendas:', data.prendas);
    console.log(' data.prendas?.length:', data.prendas?.length);
    
    let descripcionHTML = '';
    const descripcionPrendasCompleta = data.descripcion || '';
    const todasLasPrendas = data.prendas || [];
    const PRENDAS_POR_PAGINA = 2;
    
    //  PRIMERO: Si existe descripcion_prendas construida en el controlador, usarla directamente (IGUAL QUE ASESORES)
    if (descripcionPrendasCompleta && descripcionPrendasCompleta.trim() !== '' && descripcionPrendasCompleta !== 'N/A') {
        console.log(' [MOBILE] Usando descripcion_prendas del controlador con paginación');
        console.log('📝 [DESCRIPCION COMPLETA]:\n' + descripcionPrendasCompleta);
        
        // Limpiar espacios al inicio de cada línea
        const descripcionLimpia = descripcionPrendasCompleta
            .split('\n')
            .map(linea => linea.trimStart())
            .join('\n');
        
        // Dividir por "PRENDA " para obtener bloques individuales
        let bloquesPrendas = [];
        
        if (descripcionLimpia.includes('PRENDA ')) {
            // Hay formato PRENDA X: - dividir por eso
            const partes = descripcionLimpia.split('PRENDA ');
            
            bloquesPrendas = partes
                .map((parte, idx) => {
                    if (idx === 0 && !parte.trim()) return null;
                    return (idx > 0 ? 'PRENDA ' : '') + parte.trim();
                })
                .filter(b => b && b.trim() !== '');
        } else {
            // No hay formato PRENDA - dividir por \n\n pero agrupar tallas con su contenido
            const bloques = descripcionLimpia
                .split('\n\n')
                .filter(b => b && b.trim() !== '');
            
            bloquesPrendas = [];
            let bloqueActual = '';
            
            for (let i = 0; i < bloques.length; i++) {
                const bloque = bloques[i];
                
                if (/^(TALLAS?:|CANTIDAD TOTAL:)/i.test(bloque.trim())) {
                    bloqueActual += '\n\n' + bloque;
                } else {
                    if (bloqueActual) {
                        bloquesPrendas.push(bloqueActual.trim());
                    }
                    bloqueActual = bloque;
                }
            }
            
            if (bloqueActual) {
                bloquesPrendas.push(bloqueActual.trim());
            }
        }
        
        console.log('📊 [MOBILE] Total bloques de prendas:', bloquesPrendas.length);
        
        // Aplicar paginación
        const startIndex = window.prendaCarouselIndex || 0;
        const endIndex = startIndex + PRENDAS_POR_PAGINA;
        const bloquesActuales = bloquesPrendas.slice(startIndex, endIndex);
        
        // Formatear bloques actuales con estilos
        const descripcionFormateada = bloquesActuales
            .map((bloque) => {
                // Limpiar espacios al inicio y final del bloque completo
                bloque = bloque.trim();
                const lineas = bloque.split('\n').map(l => l.trim()).filter(l => l !== '');
                
                const lineasProcesadas = [];
                let hayTallasYa = false;
                
                for (let i = 0; i < lineas.length; i++) {
                    let linea = lineas[i];
                    if (linea === '') continue;
                    
                    // FILTRAR: No mostrar líneas de CANTIDAD TOTAL
                    if (/^CANTIDAD TOTAL:/i.test(linea)) {
                        continue;
                    }
                    
                    // FILTRAR: Si hay "TALLAS:", ignorar "Talla:" (evitar duplicados)
                    if (/^Talla:/i.test(linea) && hayTallasYa) {
                        continue;
                    }
                    
                    if (/^TALLAS:/i.test(linea)) {
                        hayTallasYa = true;
                    }
                    
                    // NEGRILLA en títulos
                    linea = linea.replace(/^(PRENDA \d+:)/g, '<strong>$1</strong>');
                    linea = linea.replace(/(Color:|Tela:|Manga:|DESCRIPCION:)/g, '<strong>$1</strong>');
                    
                    // NEGRILLA en viñetas
                    linea = linea.replace(/^(•\s+(Reflectivo:|Bolsillos:|BOTÓN:|[A-Z]+:))/g, '<strong>$1</strong>');
                    
                    // ROJO en tallas
                    if (/^TALLAS?:/i.test(linea)) {
                        linea = linea.replace(/^(TALLAS?:)\s+(.+)$/i, '$1 <span style="color: #d32f2f; font-weight: bold;">$2</span>');
                    }
                    
                    lineasProcesadas.push(linea);
                }
                
                return lineasProcesadas.join('<br>');
            })
            .join('<br><br>');
        
        descripcionHTML = `<div style="line-height: 1.8; font-size: 0.75rem; color: #333; word-break: break-word; overflow-wrap: break-word; max-width: 100%; margin: 0; padding: 0; text-align: left;">${descripcionFormateada}</div>`;
        
        // Actualizar total de bloques para el carousel
        window.totalBloquesPrendas = bloquesPrendas.length;
        
    } else if (todasLasPrendas.length > 0) {
        // FALLBACK: Generar descripción dinámica desde prendas (igual que asesores)
        console.log('⚠️ [MOBILE] Usando lógica de construcción dinámica (descripcion_prendas vacía)');
        
        const startIndex = window.prendaCarouselIndex || 0;
        const endIndex = startIndex + PRENDAS_POR_PAGINA;
        const prendasActuales = todasLasPrendas.slice(startIndex, endIndex);
        
        // Generar descripción dinámica para cada prenda (igual que asesores)
        prendasActuales.forEach((prenda, index) => {
            console.log('🔍 [PRENDA] Datos completos:', JSON.stringify(prenda, null, 2));
            console.log('🔍 [PRENDA] Keys disponibles:', Object.keys(prenda));
            
            let html = '';
            
            // 1. Nombre de la prenda
            html += `<strong>PRENDA ${prenda.numero || prenda.numero_prenda || (index + 1)}: ${(prenda.nombre || prenda.nombre_prenda || 'SIN NOMBRE').toUpperCase()}</strong><br>`;
            
            // 2. Línea de atributos: Color | Tela | Manga
            const atributos = [];
            if (prenda.color) {
                atributos.push(`<strong>Color:</strong> ${prenda.color.toUpperCase()}`);
            }
            if (prenda.tela) {
                let telaTexto = prenda.tela.toUpperCase();
                if (prenda.tela_referencia) {
                    telaTexto += ` REF:${prenda.tela_referencia.toUpperCase()}`;
                }
                atributos.push(`<strong>Tela:</strong> ${telaTexto}`);
            }
            if (prenda.tipo_manga) {
                let mangaTexto = prenda.tipo_manga.toUpperCase();
                // Agregar observación de manga si existe en descripcion_variaciones
                if (prenda.descripcion_variaciones) {
                    const mangaMatch = prenda.descripcion_variaciones.match(/Manga:\s*(.+?)(?:\s*\||$)/i);
                    if (mangaMatch) {
                        const observacionManga = mangaMatch[1].trim().toUpperCase();
                        // Solo agregar si es diferente al tipo de manga
                        if (observacionManga !== mangaTexto) {
                            mangaTexto += ` (${observacionManga})`;
                        }
                    }
                }
                atributos.push(`<strong>Manga:</strong> ${mangaTexto}`);
            }
            
            if (atributos.length > 0) {
                html += atributos.join(' | ') + '<br>';
            }
            
            // 3. DESCRIPCION - Priorizar descripción completa guardada en BD
            if (prenda.descripcion && prenda.descripcion !== '-') {
                // Usar la descripción completa de la BD (incluye ubicaciones del reflectivo)
                const descripcionCompleta = prenda.descripcion.toUpperCase();
                
                // Formatear la descripción: si tiene saltos de línea, convertirlos a <br>
                const descripcionFormateada = descripcionCompleta.replace(/\n/g, '<br>');
                
                html += `<strong>DESCRIPCION:</strong><br>${descripcionFormateada}<br>`;
            } else if (prenda.descripcion_variaciones) {
                // Fallback: usar descripcion_variaciones si no hay descripción completa
                const descripcionVar = prenda.descripcion_variaciones;
                const partes = [];
                
                // Reflectivo
                const reflectivoMatch = descripcionVar.match(/Reflectivo:\s*(.+?)(?:\s*\||$)/i);
                if (reflectivoMatch) {
                    partes.push(`<strong style="margin-left: 1.5em;">•</strong> <strong style="color: #000;">Reflectivo:</strong> ${reflectivoMatch[1].trim().toUpperCase()}`);
                }
                
                // Bolsillos
                const bolsillosMatch = descripcionVar.match(/Bolsillos:\s*(.+?)(?:\s*\||$)/i);
                if (bolsillosMatch) {
                    partes.push(`<strong style="margin-left: 1.5em;">•</strong> <strong style="color: #000;">Bolsillos:</strong> ${bolsillosMatch[1].trim().toUpperCase()}`);
                }
                
                // Broche/Botón - SOLO si existe tipo_broche en los datos
                if (prenda.tipo_broche) {
                    const brocheMatch = descripcionVar.match(/Broche:\s*(.+?)(?:\s*\||$)/i);
                    if (brocheMatch) {
                        const tipoLabel = prenda.tipo_broche.toUpperCase();
                        const observacion = brocheMatch[1].trim().toUpperCase();
                        partes.push(`<strong style="margin-left: 1.5em;">•</strong> <strong style="color: #000;">${tipoLabel}:</strong> ${observacion}`);
                    }
                }
                
                if (partes.length > 0) {
                    html += '<strong>DESCRIPCION:</strong><br>';
                    html += partes.join('<br>') + '<br>';
                }
            }
            
            // 4. Tallas
            if (prenda.cantidad_talla && prenda.cantidad_talla !== '-') {
                try {
                    const tallas = typeof prenda.cantidad_talla === 'string' 
                        ? JSON.parse(prenda.cantidad_talla) 
                        : prenda.cantidad_talla;
                    
                    const tallasFormateadas = [];
                    for (const [talla, cantidad] of Object.entries(tallas)) {
                        if (cantidad > 0) {
                            tallasFormateadas.push(`${talla}: ${cantidad}`);
                        }
                    }
                    
                    if (tallasFormateadas.length > 0) {
                        html += `<strong>Tallas:</strong> <span style="color: #d32f2f; font-weight: bold;">${tallasFormateadas.join(', ')}</span>`;
                    }
                } catch (e) {
                    html += `<strong>Tallas:</strong> <span style="color: #d32f2f; font-weight: bold;">${prenda.cantidad_talla}</span>`;
                }
            }
            
            descripcionHTML += `<div class="prenda-item" style="margin-bottom: 16px; line-height: 1.4; font-size: 0.75rem; color: #333;">
                ${html}
            </div>`;
            
            // Agregar separador solo entre prendas mostradas
            if (index < prendasActuales.length - 1) {
                descripcionHTML += `<hr style="border: none; border-top: 2px solid #ccc; margin: 16px 0;">`;
            }
        });
        
        // Actualizar total de prendas para el carousel
        window.totalBloquesPrendas = todasLasPrendas.length;
    }
    
    // Inyectar descripción en el DOM
    const descElement = document.getElementById('mobile-descripcion');
    if (descElement) {
        if (descripcionHTML) {
            descElement.innerHTML = descripcionHTML;
            console.log(' Descripción inyectada en el DOM');
        } else {
            descElement.innerHTML = '<em style="font-size: 10px; color: #999;">Sin descripción</em>';
            console.log('⚠️ Sin descripción válida');
        }
    }
    
    // Implementar carousel de prendas basado en bloques (igual que asesores)
    console.log('🎪 Procesando carousel de prendas...');
    const totalBloques = window.totalBloquesPrendas || 0;
    const totalPaginas = Math.ceil(totalBloques / PRENDAS_POR_PAGINA);
    
    console.log('🎪 Total bloques:', totalBloques, '| Páginas:', totalPaginas);
    
    if (totalBloques > PRENDAS_POR_PAGINA) {
        console.log('🎪 Carousel requerido - mostrar', PRENDAS_POR_PAGINA, 'de', totalBloques, 'bloques');
        
        // Obtener o crear el contenedor de flechas en la esquina superior derecha
        const arrowContainer = document.getElementById('arrow-container-mobile');
        if (arrowContainer) {
            // Limpiar botones anteriores
            arrowContainer.innerHTML = '';
            arrowContainer.style.display = 'flex';
            arrowContainer.style.justifyContent = 'center';
            arrowContainer.style.alignItems = 'center';
            arrowContainer.style.gap = '10px';
            
            const currentPage = Math.floor((window.prendaCarouselIndex || 0) / PRENDAS_POR_PAGINA);
            
            // Determinar si mostrar botón anterior
            const puedeRetroceder = currentPage > 0;
            
            // Botón anterior (< izquierda)
            if (puedeRetroceder) {
                const prevBtn = document.createElement('button');
                prevBtn.id = 'prev-arrow-mobile';
                prevBtn.style.background = 'none';
                prevBtn.style.border = 'none';
                prevBtn.style.color = 'red';
                prevBtn.style.cursor = 'pointer';
                prevBtn.style.padding = '5px';
                prevBtn.style.transition = 'all 0.2s ease';
                prevBtn.style.display = 'inline-flex';
                prevBtn.style.alignItems = 'center';
                prevBtn.style.justifyContent = 'center';
                prevBtn.style.borderRadius = '50%';
                prevBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>';
                prevBtn.onmouseover = function() {
                    this.style.transform = 'scale(1.15)';
                    this.style.backgroundColor = 'rgba(255, 0, 0, 0.1)';
                };
                prevBtn.onmouseout = function() {
                    this.style.transform = 'scale(1)';
                    this.style.backgroundColor = 'transparent';
                };
                prevBtn.onclick = function() {
                    window.prendaCarouselIndex = Math.max(0, window.prendaCarouselIndex - PRENDAS_POR_PAGINA);
                    console.log('🎪 Navegación a índice:', window.prendaCarouselIndex);
                    window.llenarReciboCosturaMobile(data);
                };
                
                arrowContainer.appendChild(prevBtn);
                console.log(' Botón anterior agregado');
            }
            
            // Botón siguiente (> derecha)
            const puedeAvanzar = currentPage < totalPaginas - 1;
            
            if (puedeAvanzar) {
                const nextBtn = document.createElement('button');
                nextBtn.id = 'next-arrow-mobile';
                nextBtn.style.background = 'none';
                nextBtn.style.border = 'none';
                nextBtn.style.color = 'red';
                nextBtn.style.cursor = 'pointer';
                nextBtn.style.padding = '5px';
                nextBtn.style.transition = 'all 0.2s ease';
                nextBtn.style.display = 'inline-flex';
                nextBtn.style.alignItems = 'center';
                nextBtn.style.justifyContent = 'center';
                nextBtn.style.borderRadius = '50%';
                nextBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                nextBtn.onmouseover = function() {
                    this.style.transform = 'scale(1.15)';
                    this.style.backgroundColor = 'rgba(255, 0, 0, 0.1)';
                };
                nextBtn.onmouseout = function() {
                    this.style.transform = 'scale(1)';
                    this.style.backgroundColor = 'transparent';
                };
                nextBtn.onclick = function() {
                    window.prendaCarouselIndex = Math.min(totalBloques - 1, window.prendaCarouselIndex + PRENDAS_POR_PAGINA);
                    console.log('🎪 Navegación a índice:', window.prendaCarouselIndex);
                    window.llenarReciboCosturaMobile(data);
                };
                
                arrowContainer.appendChild(nextBtn);
                console.log(' Botón siguiente agregado');
            }
            
            console.log(' Botones de navegación actualizados - Página:', currentPage + 1, '/', totalPaginas, '| Retroceder:', puedeRetroceder, '| Avanzar:', puedeAvanzar);
        }
    } else {
        // Ocultar el contenedor de flechas si no hay más de 2 bloques
        const arrowContainer = document.getElementById('arrow-container-mobile');
        if (arrowContainer) {
            arrowContainer.style.display = 'none';
        }
        console.log('🎪 Carousel no requerido - solo', totalBloques, 'bloque(s)');
    }
    
    console.log('🎨 === llenarReciboCosturaMobile COMPLETADO ===');
};
</script>
