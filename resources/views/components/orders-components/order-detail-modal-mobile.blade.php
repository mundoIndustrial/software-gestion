<link rel="stylesheet" href="{{ asset('css/order-detail-modal-mobile.css') }}">

<div class="order-detail-modal-container" style="
    max-width: 100%;
    padding: 0.5rem;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    background: transparent;
">
    <div class="order-detail-card" style="
        position: relative;
        width: 100%;
        max-width: 600px;
        margin: 20px auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    ">
        <!-- Logo -->
        <img src="{{ asset('images/logo.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
        
        <!-- Botón de navegación de procesos (esquina superior derecha) -->
        <div id="process-navigation-mobile" style="position: absolute; top: 15px; right: 15px; display: none; z-index: 100;"></div>
        
        <!-- Botón de navegación de prendas (esquina superior derecha, debajo de procesos) -->
        <div id="arrow-container-mobile" style="position: absolute; top: 55px; right: 15px; display: none; z-index: 100;"></div>
        
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
        <div id="order-descripcion" class="order-descripcion" style="margin-bottom: 50px;">
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
    if (!pedidoElement) {
        return;
    }
    
    const pedidoText = pedidoElement.textContent;
    const pedidoMatch = pedidoText.match(/\d+/);
    const pedido = pedidoMatch ? pedidoMatch[0] : null;
    if (!pedido) {
        return;
    }
    
    currentPedidoNumeroMobile = pedido;
    
    // Cargar imágenes
    const url = `/registros/${pedido}/images`;
    fetch(url)
        .then(response => {
            return response.json();
        })
        .then(data => {

            // Construir array de todas las imágenes para el visor
            allImagesMobile = [];
            let html = '<div style="background: linear-gradient(135deg, #1e40af, #0ea5e9); padding: 12px; margin: 0; border-radius: 0; width: 100%; box-sizing: border-box; position: sticky; top: 0; z-index: 100;">';
            html += '<h2 style="text-align: center; margin: 0; font-size: 1.6rem; font-weight: 700; color: white; letter-spacing: 1px;">GALERIA</h2>';
            html += '</div>';
            html += '<div style="padding: 20px; flex: 1; overflow-y: auto;">';
            // Mostrar prendas con sus imágenes (separando fotos de prenda/tela de fotos de logo)
            let fotosLogo = [];
            
            if (data.prendas && data.prendas.length > 0) {
                data.prendas.forEach((prenda, idx) => {
                    if (prenda.imagenes && prenda.imagenes.length > 0) {
                        // Separar fotos de logo de las demás
                        const fotosPrendaTela = prenda.imagenes.filter(img => img.type !== 'logo');
                        const fotosLogoPrend = prenda.imagenes.filter(img => img.type === 'logo');
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
            } else {
                html += '<p style="text-align: center; color: #999; padding: 2rem;">No hay imágenes para este pedido</p>';
            }
            
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(error => {
            container.innerHTML = '<p style="text-align: center; color: #999;">Error al cargar imágenes</p>';
        });
}

function openImageViewerMobile(index) {
    currentImageIndexMobile = index;
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
// Función para cargar recibos dinámicamente cuando se navega entre procesos
window.cargarReciboDinamico = async function(pedidoId, tipoProceso) {
    try {
        console.log(' [CARGAR DINAMICO] ========== INICIANDO ==========');
        console.log(' [CARGAR DINAMICO] Datos:', { pedidoId, tipoProceso });
        console.log(' [CARGAR DINAMICO] Índice actual:', window.procesoCarouselIndex);
        console.log(' [CARGAR DINAMICO] Procesos disponibles:', window.todosProcesosDisponibles);
        
        // Hacer fetch a la API para obtener datos actualizados
        const url = `/api/operario/pedido/${pedidoId}`;
        console.log(' [CARGAR DINAMICO] URL API:', url);
        
        const response = await fetch(url);
        
        console.log(' [CARGAR DINAMICO] Respuesta HTTP:', {
            ok: response.ok,
            status: response.status,
            statusText: response.statusText,
            contentType: response.headers.get('content-type')
        });
        
        if (!response.ok) {
            throw new Error(`Error en API: ${response.status}`);
        }
        
        const result = await response.json();
        
        console.log(' [CARGAR DINAMICO] JSON recibido:', {
            success: result.success,
            tieneData: !!result.data,
            dataKeys: result.data ? Object.keys(result.data).slice(0, 10) : null
        });
        
        if (result.success && result.data) {
            console.log(' [CARGAR DINAMICO] Datos válidos obtenidos');
            console.log(' [CARGAR DINAMICO] Data.prendas:', result.data.prendas?.length);
            
            // Resetear prendaCarouselIndex para que muestre desde el principio
            window.prendaCarouselIndex = 0;
            
            console.log(' [CARGAR DINAMICO] Llamando a llenarReciboCosturaMobile...');
            
            // Llenar con los nuevos datos
            window.llenarReciboCosturaMobile(result.data);
            
            console.log(' [CARGAR DINAMICO] llenarReciboCosturaMobile completado');
        } else {
            throw new Error('Respuesta inválida de la API: ' + JSON.stringify(result));
        }
    } catch (error) {
        console.error(' [CARGAR DINAMICO] Error:', error);
        console.error(' [CARGAR DINAMICO] Stack:', error.stack);
        alert('Error al cargar el recibo: ' + error.message);
    }
};

// Función para llenar el recibo móvil
window.llenarReciboCosturaMobile = function(data) {
    console.log('📱 [RECIBO MOBILE]  ========== INICIANDO llenarReciboCosturaMobile ==========');
    console.log('📱 [RECIBO MOBILE] Datos recibidos:', data);
    console.log('📱 [RECIBO MOBILE] procesoCarouselIndex ACTUAL:', window.procesoCarouselIndex);
    console.log('📱 [RECIBO MOBILE] todosProcesosDisponibles ACTUAL:', window.todosProcesosDisponibles);
    
    // ===== NAVEGACIÓN DE PROCESOS =====
    // Inicializar índice de proceso si no existe
    if (!window.procesoCarouselIndex) {
        window.procesoCarouselIndex = 0;
    }
    
    console.log('📱 [RECIBO MOBILE] Índice de proceso actual (window.procesoCarouselIndex):', window.procesoCarouselIndex);
    
    // Obtener lista de procesos únicos del pedido
    // Buscar en recibos primero, luego en procesos
    const todosProcesos = [];
    const userRole = document.getElementById('factura-container-mobile')?.getAttribute('data-user-role');
    
    if (data.prendas && Array.isArray(data.prendas)) {
        data.prendas.forEach(function(prenda) {
            // Opción 1: Usar recibos (si existen)
            if (prenda.recibos && typeof prenda.recibos === 'object') {
                Object.keys(prenda.recibos).forEach(function(proceso) {
                    // Solo agregar si tiene valor (no es null)
                    if (prenda.recibos[proceso] !== null && !todosProcesos.includes(proceso)) {
                        todosProcesos.push(proceso);
                    }
                });
            }
            // Opción 2: Usar procesos (fallback)
            if (prenda.procesos && Array.isArray(prenda.procesos)) {
                prenda.procesos.forEach(function(proceso) {
                    if (proceso.proceso && !todosProcesos.includes(proceso.proceso)) {
                        todosProcesos.push(proceso.proceso);
                    }
                });
            }
        });
    }
    
    // Filtrar procesos según el rol del usuario
    let procesosFiltrados = todosProcesos;
    console.log(' [FILTRO PROCESOS] Rol del usuario:', userRole);
    console.log(' [FILTRO PROCESOS] Todos los procesos encontrados:', todosProcesos);
    
    if (userRole === 'costura-reflectivo') {
        // Para costura-reflectivo, mostrar COSTURA y REFLECTIVO en ese orden
        const tieneCostu = todosProcesos.includes('COSTURA');
        const tieneReflectivo = todosProcesos.includes('REFLECTIVO');
        procesosFiltrados = [];
        if (tieneCostu) procesosFiltrados.push('COSTURA');
        if (tieneReflectivo) procesosFiltrados.push('REFLECTIVO');
        
        console.log(' [FILTRO PROCESOS] tieneCostu:', tieneCostu);
        console.log(' [FILTRO PROCESOS] tieneReflectivo:', tieneReflectivo);
    }
    
    console.log(' [FILTRO PROCESOS] Procesos filtrados FINAL:', procesosFiltrados);
    console.log(' [FILTRO PROCESOS] Índice actual (procesoCarouselIndex):', window.procesoCarouselIndex);
    console.log(' [FILTRO PROCESOS] Proceso que se debe mostrar:', procesosFiltrados[window.procesoCarouselIndex || 0]);
    
    // Mostrar navegación de procesos si hay al menos 1 proceso
    if (procesosFiltrados.length >= 1) {
        const processNavContainer = document.getElementById('process-navigation-mobile');
        if (processNavContainer) {
            processNavContainer.innerHTML = '';
            processNavContainer.style.display = 'flex';
            processNavContainer.style.justifyContent = 'center';
            processNavContainer.style.alignItems = 'center';
            processNavContainer.style.gap = '8px';
            processNavContainer.style.flexDirection = 'row';
            
            const procesoActualIndex = window.procesoCarouselIndex || 0;
            const procesoActual = procesosFiltrados[procesoActualIndex] || '';
            
            console.log('📱 [NAVEGACION] procesoActualIndex:', procesoActualIndex);
            console.log('📱 [NAVEGACION] procesoActual:', procesoActual);
            console.log('📱 [NAVEGACION] procesosFiltrados.length:', procesosFiltrados.length);
            
            // Botón anterior de procesos
            if (procesoActualIndex > 0) {
                const prevProcBtn = document.createElement('button');
                prevProcBtn.style.background = '#EF5350';
                prevProcBtn.style.border = 'none';
                prevProcBtn.style.color = 'white';
                prevProcBtn.style.cursor = 'pointer';
                prevProcBtn.style.padding = '6px 8px';
                prevProcBtn.style.borderRadius = '4px';
                prevProcBtn.style.fontSize = '12px';
                prevProcBtn.style.fontWeight = '600';
                prevProcBtn.style.transition = 'all 0.2s ease';
                prevProcBtn.title = 'Proceso anterior';
                prevProcBtn.innerHTML = '<span style="font-size: 16px;">◀</span>';
                prevProcBtn.onmouseover = function() {
                    this.style.transform = 'scale(1.1)';
                    this.style.boxShadow = '0 2px 8px rgba(239, 83, 80, 0.3)';
                };
                prevProcBtn.onmouseout = function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                };
                prevProcBtn.onclick = function() {
                    console.log('🔘 [CLICK BOTÓN] ANTERIOR presionado');
                    window.procesoCarouselIndex = Math.max(0, window.procesoCarouselIndex - 1);
                    const nuevoProceso = procesosFiltrados[window.procesoCarouselIndex];
                    console.log('🔘 [CLICK BOTÓN] Nuevo índice:', window.procesoCarouselIndex);
                    console.log('🔘 [CLICK BOTÓN] Nuevo proceso:', nuevoProceso);
                    // Recargar datos dinámicamente para el nuevo proceso
                    cargarReciboDinamico(data.pedido_id, nuevoProceso);
                };
                processNavContainer.appendChild(prevProcBtn);
            }
            
            // Indicador del proceso actual
            const processIndicator = document.createElement('div');
            processIndicator.style.background = '#EF5350';
            processIndicator.style.color = 'white';
            processIndicator.style.padding = '4px 10px';
            processIndicator.style.borderRadius = '4px';
            processIndicator.style.fontSize = '11px';
            processIndicator.style.fontWeight = 'bold';
            processIndicator.style.whiteSpace = 'nowrap';
            processIndicator.style.textAlign = 'center';
            processIndicator.textContent = (procesoActualIndex + 1) + '/' + procesosFiltrados.length;
            processNavContainer.appendChild(processIndicator);
            
            // Botón siguiente de procesos - SOLO SI HAY MÁS DE UN PROCESO
            if (procesosFiltrados.length > 1) {
                const nextProcBtn = document.createElement('button');
                nextProcBtn.style.background = '#EF5350';
                nextProcBtn.style.border = 'none';
                nextProcBtn.style.color = 'white';
                nextProcBtn.style.cursor = procesoActualIndex < procesosFiltrados.length - 1 ? 'pointer' : 'not-allowed';
                nextProcBtn.style.padding = '6px 8px';
                nextProcBtn.style.borderRadius = '4px';
                nextProcBtn.style.fontSize = '12px';
                nextProcBtn.style.fontWeight = '600';
                nextProcBtn.style.transition = 'all 0.2s ease';
                nextProcBtn.style.opacity = procesoActualIndex < procesosFiltrados.length - 1 ? '1' : '0.5';
                nextProcBtn.title = 'Proceso siguiente';
                nextProcBtn.innerHTML = '<span style="font-size: 16px;">▶</span>';
                nextProcBtn.onmouseover = function() {
                    if (procesoActualIndex < procesosFiltrados.length - 1) {
                        this.style.transform = 'scale(1.1)';
                        this.style.boxShadow = '0 2px 8px rgba(239, 83, 80, 0.3)';
                    }
                };
                nextProcBtn.onmouseout = function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                };
                nextProcBtn.onclick = function() {
                    console.log('🔘 [CLICK BOTÓN] SIGUIENTE presionado');
                    if (procesoActualIndex < procesosFiltrados.length - 1) {
                        window.procesoCarouselIndex = Math.min(procesosFiltrados.length - 1, window.procesoCarouselIndex + 1);
                        const nuevoProceso = procesosFiltrados[window.procesoCarouselIndex];
                        console.log('🔘 [CLICK BOTÓN] Nuevo índice:', window.procesoCarouselIndex);
                        console.log('🔘 [CLICK BOTÓN] Nuevo proceso:', nuevoProceso);
                        // Recargar datos dinámicamente para el nuevo proceso
                        cargarReciboDinamico(data.pedido_id, nuevoProceso);
                    }
                };
                processNavContainer.appendChild(nextProcBtn);
            }
            
            // Guardar procesos en variable global para usar en filtrado posterior
            window.todosProcesosDisponibles = procesosFiltrados;
            window.procesoActualSeleccionado = procesoActual;
        }
    } else {
        const processNavContainer = document.getElementById('process-navigation-mobile');
        if (processNavContainer) {
            processNavContainer.style.display = 'none';
        }
    }
    
    // Fecha - parsear correctamente
    if (data.fecha && data.fecha !== 'N/A') {
        let fecha;
        
        // Intentar parsear diferentes formatos de fecha
        if (typeof data.fecha === 'string') {
            // Formato DD/MM/YYYY
            if (data.fecha.includes('/')) {
                const [day, month, year] = data.fecha.split('/');
                fecha = new Date(year, parseInt(month) - 1, day);
            }
            // Formato YYYY-MM-DD o YYYY-MM-DD HH:MM:SS
            else if (data.fecha.includes('-')) {
                // Separar fecha de hora si existe
                const fechaParte = data.fecha.split(' ')[0];
                const [year, month, day] = fechaParte.split('-');
                fecha = new Date(year, parseInt(month) - 1, parseInt(day));
            } else {
                fecha = new Date(data.fecha);
            }
        } else {
            fecha = new Date(data.fecha);
        }
        
        // Validar que sea una fecha válida
        if (!isNaN(fecha)) {
            const dayBox = document.getElementById('fecha-dia');
            const monthBox = document.getElementById('fecha-mes');
            const yearBox = document.getElementById('fecha-year');
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
        }
    } else {
    }

    // Información básica
    const asesora = document.getElementById('mobile-asesora');
    const formaPago = document.getElementById('mobile-forma-pago');
    const cliente = document.getElementById('mobile-cliente');
    const numeroPedido = document.getElementById('mobile-numero-pedido');
    const encargado = document.getElementById('mobile-encargado');
    const prendasEntregadas = document.getElementById('mobile-prendas-entregadas');
    if (asesora) asesora.textContent = data.asesora || 'N/A';
    if (formaPago) formaPago.textContent = data.formaPago || 'N/A';
    if (cliente) cliente.textContent = data.cliente || 'N/A';
    if (numeroPedido) numeroPedido.textContent = '#' + (data.numeroPedido || '');
    
    // VALIDAR CONDICIONES PARA MOSTRAR ENCARGADO
    // Solo mostrar si:
    // 1. Area es "costura"
    // 2. Estado es "En Ejecución"
    // 3. Usuario tiene rol "costura" o "costura-reflectivo"
    // 4. El proceso tiene encargado (nombre de usuario)
    const mostraEncargado = data.area && 
                            data.area.toLowerCase() === 'costura' && 
                            data.estado === 'En Ejecución' && 
                            (userRole === 'costura' || userRole === 'costura-reflectivo') && 
                            data.encargado && 
                            data.encargado.trim() !== '' && 
                            data.encargado !== '-' && 
                            data.encargado !== 'Operario';
    
    if (encargado) {
        if (mostraEncargado) {
            encargado.textContent = data.encargado;
            console.log('📱 [ENCARGADO]  Mostrando encargado:', data.encargado);
        } else {
            encargado.textContent = '-';
            console.log('📱 [ENCARGADO]  No aplican las condiciones para mostrar encargado', {
                area: data.area,
                estado: data.estado,
                userRole: userRole,
                encargado: data.encargado
            });
        }
    }
    if (prendasEntregadas) prendasEntregadas.textContent = data.prendasEntregadas || '0/0';
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
    let descripcionHTML = '';
    const descripcionPrendasCompleta = data.descripcion || '';
    let todasLasPrendas = data.prendas || [];
    const PRENDAS_POR_PAGINA = 2;
    
    // FILTRAR PRENDAS POR PROCESO SELECCIONADO
    const procesoActualIndex = window.procesoCarouselIndex || 0;
    const procesosDisponibles = window.todosProcesosDisponibles || [];
    const procesoActualSeleccionado = procesosDisponibles[procesoActualIndex] || null;
    
    console.log('📱 [RECIBO MOBILE] =========================================');
    console.log('📱 [RECIBO MOBILE] Proceso actual seleccionado:', procesoActualSeleccionado);
    console.log('📱 [RECIBO MOBILE] Índice del proceso:', procesoActualIndex);
    console.log('📱 [RECIBO MOBILE] Procesos disponibles:', procesosDisponibles);
    console.log('📱 [RECIBO MOBILE] Total prendas ANTES de filtrar:', todasLasPrendas.length);
    
    // Solo filtrar por proceso si el usuario está navegando entre procesos (no en primera carga)
    // Detectar si ya hay prendas mostradas (significa que es llamada desde evento de click de arrow)
    const esNavegacionDeProc = window.procesoCarouselIndex !== undefined && window.procesoCarouselIndex > 0;
    
    console.log('📱 [RECIBO MOBILE] ¿Es navegación de proceso?:', esNavegacionDeProc);
    
    if (esNavegacionDeProc && procesoActualSeleccionado && todasLasPrendas.length > 0) {
        console.log('📱 [RECIBO MOBILE]  FILTRANDO prendas para proceso:', procesoActualSeleccionado);
        // Filtrar prendas que tengan el proceso seleccionado
        todasLasPrendas = todasLasPrendas.filter(function(prenda) {
            // Opción 1: Buscar en recibos
            if (prenda.recibos && typeof prenda.recibos === 'object') {
                const tieneProc = prenda.recibos[procesoActualSeleccionado] !== null && prenda.recibos[procesoActualSeleccionado] !== undefined;
                console.log('📱 [RECIBO MOBILE] Prenda:', prenda.nombre, '- Tiene', procesoActualSeleccionado + '?:', tieneProc, 'Valor:', prenda.recibos[procesoActualSeleccionado]);
                return tieneProc;
            }
            // Opción 2: Buscar en procesos (fallback)
            if (!prenda.procesos || !Array.isArray(prenda.procesos)) {
                return false;
            }
            return prenda.procesos.some(function(proc) {
                return proc.proceso === procesoActualSeleccionado;
            });
        });
        console.log('📱 [RECIBO MOBILE] Total prendas DESPUÉS de filtrar:', todasLasPrendas.length);
    } else {
        console.log('📱 [RECIBO MOBILE]  Primera carga - SIN filtrar, mostrando todas las prendas');
    }
    
    // LIMPIAR CONTENEDOR DE RECIBO ANTES DE RECONSTRUIR
    const reciboDOMContainer = document.getElementById('mobile-descripcion');
    if (reciboDOMContainer) {
        console.log('📱 [RECIBO MOBILE] Limpiando contenedor #mobile-descripcion');
        reciboDOMContainer.innerHTML = '';
    }
    
    // Declarar prendasActuales al inicio para que esté disponible en todo el scope
    let prendasActuales = [];
    
    console.log('📱 [RECIBO MOBILE] descripcionPrendasCompleta existe?:', !!descripcionPrendasCompleta);
    console.log('📱 [RECIBO MOBILE] descripcionPrendasCompleta trim():', descripcionPrendasCompleta ? descripcionPrendasCompleta.trim().substring(0, 100) : 'NULL');
    
    //  PRIMERO: Si existe descripcion_prendas construida en el controlador, usarla directamente (IGUAL QUE ASESORES)
    // PERO: Si estamos navegando entre procesos, IGNORAR descripcionPrendasCompleta y usar fallback dinámico
    // para que se reconstruya el recibo con solo las prendas del proceso seleccionado
    const debeUsarDescripcionPreConstruida = descripcionPrendasCompleta && descripcionPrendasCompleta.trim() !== '' && descripcionPrendasCompleta !== 'N/A' && !esNavegacionDeProc;
    
    if (debeUsarDescripcionPreConstruida) {
        console.log('📱 [RECIBO MOBILE]  USANDO RAMA: descripcionPrendasCompleta (pre-construida)');
        
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
        
        // Reorganizar: Extraer TALLAS y ponerlas al final
        let descSinTallas = descripcionFormateada;
        let tallasExtraidas = '';
        
        // Buscar y extraer TALLAS usando regex
        const tallasRegex = /(<strong>TALLAS?<\/strong><br>.*?)(?=<\/div>|$)/is;
        const tallasMatch = descripcionFormateada.match(tallasRegex);
        
        if (tallasMatch) {
            tallasExtraidas = tallasMatch[1].trim();
            // Remover TALLAS de la descripción
            descSinTallas = descripcionFormateada.replace(tallasMatch[0], '').trim();
        }
        
        descripcionHTML = `<div style="line-height: 1.3; font-size: 0.75rem; color: #333; word-break: break-word; overflow-wrap: break-word; max-width: 100%; margin: 0; padding: 0; text-align: left;">${descSinTallas}</div>`;
        
        // 🔧 AGREGAR DATOS DE PROCESOS (Ubicaciones, Observaciones) 
        // Incluso aunque usamos descripcionPrendasCompleta, debemos incluir datos dinámicos de procesos
        const procStartIndex = window.prendaCarouselIndex || 0;
        const procEndIndex = procStartIndex + PRENDAS_POR_PAGINA;
        const prendasConProcesos = todasLasPrendas.slice(procStartIndex, procEndIndex);
        
        let datosProcesoHTML = '';
        prendasConProcesos.forEach((prenda) => {
            if (prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0) {
                prenda.procesos.forEach((proceso) => {
                    // UBICACIONES
                    if (proceso.ubicaciones) {
                        let ubicacionesArray = [];
                        try {
                            if (typeof proceso.ubicaciones === 'string') {
                                ubicacionesArray = JSON.parse(proceso.ubicaciones);
                            } else if (Array.isArray(proceso.ubicaciones)) {
                                ubicacionesArray = proceso.ubicaciones;
                            }
                        } catch (e) {
                            ubicacionesArray = [proceso.ubicaciones];
                        }
                        
                        if (ubicacionesArray && ubicacionesArray.length > 0) {
                            datosProcesoHTML += `<strong>UBICACIONES:</strong><br>`;
                            ubicacionesArray.forEach(ub => {
                                datosProcesoHTML += `• ${ub.toUpperCase()}<br>`;
                            });
                        }
                    }
                    
                    // OBSERVACIONES
                    if (proceso.observaciones) {
                        datosProcesoHTML += `<strong>OBSERVACIONES:</strong><br>${proceso.observaciones.toUpperCase()}<br>`;
                    }
                });
            }
        });
        
        // Combinar: Descripción base + Ubicaciones/Observaciones
        if (datosProcesoHTML) {
            descripcionHTML += `<div style="line-height: 1.3; font-size: 0.75rem; color: #333; word-break: break-word; overflow-wrap: break-word; max-width: 100%; margin-top: 0.5rem; padding: 0; text-align: left;">${datosProcesoHTML}</div>`;
        }
        
        // Mostrar TALLAS al final
        if (tallasExtraidas) {
            descripcionHTML += `<div style="line-height: 1.3; font-size: 0.75rem; color: #333; word-break: break-word; overflow-wrap: break-word; max-width: 100%; margin-top: 0.5rem; padding: 0; text-align: left;">${tallasExtraidas}</div>`;
        }
        
        // Actualizar total de bloques para el carousel
        window.totalBloquesPrendas = bloquesPrendas.length;
        
    } else if (todasLasPrendas.length > 0) {
        // FALLBACK: Generar descripción dinámica desde prendas (igual que asesores)
        console.log('📱 [RECIBO MOBILE]  USANDO RAMA: Fallback dinámico (descripcion_prendas vacía)');
        console.log(' [MOBILE] Usando lógica de construcción dinámica (descripcion_prendas vacía)');
        
        const startIndex = window.prendaCarouselIndex || 0;
        const endIndex = startIndex + PRENDAS_POR_PAGINA;
        prendasActuales = todasLasPrendas.slice(startIndex, endIndex);
        
        console.log('📱 [RECIBO MOBILE]  Fallback - prendasActuales rellenadas:', prendasActuales.length);
        
        // Generar descripción dinámica para cada prenda (igual que asesores)
        prendasActuales.forEach((prenda, index) => {
            console.log(' [PRENDA] Datos completos:', JSON.stringify(prenda, null, 2));
            console.log(' [PRENDA] Keys disponibles:', Object.keys(prenda));
            
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
            
            // 3. DATOS ESPECÍFICOS DEL PROCESO (Ubicaciones, Observaciones, Tallas del proceso)
            // Mostrar para TODOS los procesos disponibles en la prenda (no solo navegación)
            if (prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0) {
                // Si hay solo un proceso, mostrarlo directo. Si hay varios, mostrar todos en la primera carga
                prenda.procesos.forEach((proceso, procIdx) => {
                    // UBICACIONES del proceso
                    if (proceso.ubicaciones) {
                        let ubicacionesArray = [];
                        try {
                            if (typeof proceso.ubicaciones === 'string') {
                                ubicacionesArray = JSON.parse(proceso.ubicaciones);
                            } else if (Array.isArray(proceso.ubicaciones)) {
                                ubicacionesArray = proceso.ubicaciones;
                            }
                        } catch (e) {
                            ubicacionesArray = [proceso.ubicaciones];
                        }
                        
                        if (ubicacionesArray && ubicacionesArray.length > 0) {
                            html += `<strong>UBICACIONES:</strong><br>`;
                            ubicacionesArray.forEach(ub => {
                                html += `• ${ub.toUpperCase()}<br>`;
                            });
                        }
                    }
                    
                    // OBSERVACIONES DEL PROCESO
                    if (proceso.observaciones) {
                        html += `<strong>OBSERVACIONES:</strong><br>${proceso.observaciones.toUpperCase()}<br>`;
                    }
                    
                    // TALLAS DEL PROCESO
                    if (proceso.tallas) {
                        const tallasProc = [];
                        if (proceso.tallas.dama && Object.keys(proceso.tallas.dama).length > 0) {
                            Object.entries(proceso.tallas.dama).forEach(([talla, cantidad]) => {
                                if (cantidad > 0) {
                                    tallasProc.push(`DAMA ${talla}: ${cantidad}`);
                                }
                            });
                        }
                        if (proceso.tallas.caballero && Object.keys(proceso.tallas.caballero).length > 0) {
                            Object.entries(proceso.tallas.caballero).forEach(([talla, cantidad]) => {
                                if (cantidad > 0) {
                                    tallasProc.push(`CABALLERO ${talla}: ${cantidad}`);
                                }
                            });
                        }
                        if (proceso.tallas.unisex && Object.keys(proceso.tallas.unisex).length > 0) {
                            Object.entries(proceso.tallas.unisex).forEach(([talla, cantidad]) => {
                                if (cantidad > 0) {
                                    tallasProc.push(`UNISEX ${talla}: ${cantidad}`);
                                }
                            });
                        }
                        
                        if (tallasProc.length > 0) {
                            html += `<strong>TALLAS</strong><br><span style="color: #d32f2f; font-weight: bold;">${tallasProc.join(', ')}</span><br>`;
                        }
                    }
                });
            }
            
            //  COMENTADO: Las tallas ya se muestran en la sección de DATOS ESPECÍFICOS DEL PROCESO
            // const tallasFormateadas = [];
            // prenda.tallas.forEach((tallaObj) => {
            //     if (tallaObj.cantidad > 0) {
            //         tallasFormateadas.push(`${tallaObj.genero}-${tallaObj.talla}: ${tallaObj.cantidad}`);
            //     }
            // });
            // if (tallasFormateadas.length > 0) {
            //     html += `<strong>Tallas:</strong> <span style="color: #d32f2f; font-weight: bold;">${tallasFormateadas.join(', ')}</span>`;
            // }
            
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
        } else {
            descElement.innerHTML = '<em style="font-size: 10px; color: #999;">Sin descripción</em>';
        }
    }
    
    // ACTUALIZAR TÍTULO DEL RECIBO CON EL PROCESO ACTUAL
    // Actualizar SIEMPRE que haya un procesoActualSeleccionado (incluso cuando regresas a índice 0)
    if (procesoActualSeleccionado) {
        const titleElement = document.querySelector('.receipt-title');
        if (titleElement) {
            titleElement.textContent = 'RECIBO DE ' + procesoActualSeleccionado.toUpperCase();
        }
        
        // ACTUALIZAR NÚMERO DE RECIBO CON EL CONSECUTIVO DEL PROCESO
        const numeroPedidoElement = document.getElementById('mobile-numero-pedido');
        if (numeroPedidoElement && data.prendas && data.prendas.length > 0) {
            // Buscar el primer recibo que coincida con el proceso actual
            let reciboBuscado = null;
            
            console.log('🔢 [NUMERO RECIBO] Buscando recibo para proceso:', procesoActualSeleccionado);
            console.log('🔢 [NUMERO RECIBO] Total prendas:', data.prendas.length);
            
            for (let i = 0; i < data.prendas.length; i++) {
                const prenda = data.prendas[i];
                
                console.log('🔢 [NUMERO RECIBO] Prenda', i, ':', prenda.nombre, '- Tiene recibos?:', !!prenda.recibos);
                
                // Los recibos pueden ser: { 'COSTURA': 3, 'REFLECTIVO': 4 } o { 'COSTURA': {...}, 'REFLECTIVO': {...} }
                if (prenda.recibos && typeof prenda.recibos === 'object' && !Array.isArray(prenda.recibos)) {
                    // Acceder al recibo del proceso actual - probar diferentes variantes de capitalización
                    let reciboProceso = prenda.recibos[procesoActualSeleccionado];
                    
                    // Si no encuentra con el nombre exacto, buscar case-insensitive
                    if (!reciboProceso) {
                        const procesoBuscado = procesoActualSeleccionado.toUpperCase();
                        for (const [key, value] of Object.entries(prenda.recibos)) {
                            if (key.toUpperCase() === procesoBuscado && value !== null && value !== undefined) {
                                reciboProceso = value;
                                console.log('🔢 [NUMERO RECIBO] Encontrado con match case-insensitive:', key, '→', procesoBuscado);
                                break;
                            }
                        }
                    }
                    
                    console.log('🔢 [NUMERO RECIBO] reciboProceso para', procesoActualSeleccionado + ':', reciboProceso);
                    console.log('🔢 [NUMERO RECIBO] Tipo de reciboProceso:', typeof reciboProceso);
                    
                    if (reciboProceso) {
                        // El recibo puede ser un número directo o un objeto con consecutivo_actual
                        let numeroRecibo = null;
                        if (typeof reciboProceso === 'number') {
                            numeroRecibo = reciboProceso;
                        } else if (typeof reciboProceso === 'object' && reciboProceso.consecutivo_actual) {
                            numeroRecibo = reciboProceso.consecutivo_actual;
                        }
                        
                        if (numeroRecibo !== null && numeroRecibo !== undefined) {
                            numeroPedidoElement.textContent = '#' + numeroRecibo;
                            console.log(' [NUMERO RECIBO ACTUALIZADO]', procesoActualSeleccionado, '→ #' + numeroRecibo);
                            reciboBuscado = reciboProceso;
                            break;
                        }
                    }
                }
            }
            
            // Si no encontró recibo específico, mantener el numero inicial
            if (!reciboBuscado) {
                console.log(' [NUMERO RECIBO] No se encontró recibo para', procesoActualSeleccionado);
            }
        }
    }
    
    // Implementar carousel de prendas basado en bloques (igual que asesores)
    const totalBloques = window.totalBloquesPrendas || 0;
    const totalPaginas = Math.ceil(totalBloques / PRENDAS_POR_PAGINA);
    if (totalBloques > PRENDAS_POR_PAGINA) {
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
                    window.llenarReciboCosturaMobile(data);
                };
                
                arrowContainer.appendChild(prevBtn);
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
                    window.llenarReciboCosturaMobile(data);
                };
                
                arrowContainer.appendChild(nextBtn);
            }
        }
    } else {
        // Ocultar el contenedor de flechas si no hay más de 2 bloques
        const arrowContainer = document.getElementById('arrow-container-mobile');
        if (arrowContainer) {
            arrowContainer.style.display = 'none';
        }
        console.log('🎪 Carousel no requerido - solo', totalBloques, 'bloque(s)');
    }
    
    console.log('📱 [RECIBO MOBILE]  ========== FIN llenarReciboCosturaMobile ==========');
};
</script>

