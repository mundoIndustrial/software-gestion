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
// Función para llenar el recibo móvil
window.llenarReciboCosturaMobile = function(data) {
    console.log('🎨 === INICIANDO llenarReciboCosturaMobile ===');
    console.log('🎨 Datos recibidos:', data);
    
    // Fecha - parsear correctamente
    if (data.fecha) {
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
            // Formato YYYY-MM-DD
            else if (data.fecha.includes('-')) {
                const [year, month, day] = data.fecha.split('-');
                fecha = new Date(year, parseInt(month) - 1, day);
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
            console.log('✅ Fecha válida:', fecha);
            const dayBox = document.getElementById('fecha-dia');
            const monthBox = document.getElementById('fecha-mes');
            const yearBox = document.getElementById('fecha-year');
            
            console.log('✅ Elementos encontrados - dayBox:', !!dayBox, 'monthBox:', !!monthBox, 'yearBox:', !!yearBox);
            
            if (dayBox) {
                dayBox.textContent = fecha.getDate();
                console.log('✅ Día actualizado:', fecha.getDate());
            }
            if (monthBox) {
                monthBox.textContent = (fecha.getMonth() + 1);
                console.log('✅ Mes actualizado:', fecha.getMonth() + 1);
            }
            if (yearBox) {
                yearBox.textContent = fecha.getFullYear();
                console.log('✅ Año actualizado:', fecha.getFullYear());
            }
        } else {
            console.error('❌ Fecha inválida');
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
    
    console.log('✅ Información básica actualizada');

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

    // Descripción - mostrar como lista formateada con saltos de línea y viñetas
    console.log('📋 Procesando descripción...');
    console.log('📋 ¿Existe descripción?', !!data.descripcion, 'Valor:', data.descripcion);
    
    if (data.descripcion && data.descripcion !== 'N/A') {
        console.log('📋 Descripción detectada, largo:', data.descripcion.length);
        const lineas = data.descripcion.split('\n');
        console.log('📋 Total de líneas:', lineas.length);
        
        let htmlResultado = '';
        let lineaCount = 0;
        
        lineas.forEach((linea, index) => {
            const lineaTrimmed = linea.trim();
            
            if (lineaTrimmed === '') {
                htmlResultado += '<br>';
            } else if (lineaTrimmed.startsWith('PRENDA')) {
                lineaCount++;
                htmlResultado += '<strong style="font-size: 11px; display: block; margin-top: 8px;">' + convertMarkdownBold(lineaTrimmed) + '</strong>';
            } else if (lineaTrimmed.startsWith('TALLAS') || lineaTrimmed.startsWith('*** TALLAS')) {
                lineaCount++;
                htmlResultado += '<strong style="font-size: 10px; display: block; margin-top: 6px; color: #d32f2f;">' + convertMarkdownBold(lineaTrimmed) + '</strong>';
            } else if ((lineaTrimmed.startsWith('-') && lineaTrimmed.includes(':')) || /^\d+:\s*\d+/.test(lineaTrimmed) || /^-\s*\d+:\s*\d+/.test(lineaTrimmed)) {
                lineaCount++;
                let textoTalla = lineaTrimmed;
                if (textoTalla.startsWith('- ')) {
                    textoTalla = textoTalla.substring(2);
                }
                htmlResultado += '<div style="font-size: 10px; margin: 4px 0; font-weight: bold; color: #d32f2f;">Talla: ' + textoTalla + '</div>';
            } else if (lineaTrimmed.startsWith('Talla:') || lineaTrimmed.startsWith('Cantidad:')) {
                lineaCount++;
                htmlResultado += '<div style="font-size: 10px; margin: 4px 0; font-weight: bold; color: #d32f2f;">' + convertMarkdownBold(lineaTrimmed) + '</div>';
            } else if (lineaTrimmed.includes(':') && (lineaTrimmed.includes('DESCRIPCION') || lineaTrimmed.includes('Reflectivo') || lineaTrimmed.includes('Bolsillos'))) {
                lineaCount++;
                htmlResultado += '<strong style="font-size: 10px; display: block; margin-top: 6px;">' + convertMarkdownBold(lineaTrimmed) + '</strong>';
            } else if (lineaTrimmed.startsWith('•') || lineaTrimmed.startsWith('.')) {
                lineaCount++;
                htmlResultado += '<div style="margin-left: 12px; font-size: 10px;">' + convertMarkdownBold(lineaTrimmed) + '</div>';
            } else if (lineaTrimmed.includes(':') && lineaTrimmed.includes('|')) {
                lineaCount++;
                htmlResultado += '<div style="font-size: 10px; margin: 2px 0;">' + convertMarkdownBold(lineaTrimmed) + '</div>';
            } else {
                lineaCount++;
                htmlResultado += '<div style="font-size: 10px; margin: 2px 0;">' + convertMarkdownBold(lineaTrimmed) + '</div>';
            }
        });
        
        const descElement = document.getElementById('mobile-descripcion');
        if (descElement) {
            descElement.innerHTML = htmlResultado;
            console.log('✅ Descripción inyectada en el DOM');
        }
    } else {
        console.log('⚠️ Sin descripción válida');
        const descElement = document.getElementById('mobile-descripcion');
        if (descElement) {
            descElement.innerHTML = '<em style="font-size: 10px; color: #999;">Sin descripción</em>';
        }
    }
    
    // Implementar carousel de prendas si hay múltiples
    console.log('🎪 Procesando prendas...');
    if (data.prendas && Array.isArray(data.prendas) && data.prendas.length > 0) {
        console.log('🎪 Total de prendas:', data.prendas.length);
        
        // Crear contenedor de carousel si hay más de 2 prendas
        if (data.prendas.length > 2) {
            console.log('🎪 Carousel requerido - mostrar 2 de', data.prendas.length, 'prendas');
            
            // Obtener o crear el contenedor de flechas en la esquina superior derecha
            const arrowContainer = document.getElementById('arrow-container-mobile');
            if (arrowContainer) {
                // Limpiar botones anteriores
                arrowContainer.innerHTML = '';
                arrowContainer.style.display = 'flex';
                arrowContainer.style.justifyContent = 'center';
                arrowContainer.style.alignItems = 'center';
                arrowContainer.style.gap = '10px';
                
                // Determinar si mostrar botón anterior
                const puedeRetroceder = window.prendaCarouselIndex > 0;
                
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
                        window.prendaCarouselIndex = Math.max(0, window.prendaCarouselIndex - 2);
                        console.log('🎪 Navegación a prenda índice:', window.prendaCarouselIndex);
                        window.llenarReciboCosturaMobile(data);
                    };
                    
                    arrowContainer.appendChild(prevBtn);
                    console.log('✅ Botón anterior agregado');
                }
                
                // Botón siguiente (> derecha)
                const puedeAvanzar = (window.prendaCarouselIndex + 2) < data.prendas.length;
                
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
                        window.prendaCarouselIndex = Math.min(data.prendas.length - 1, window.prendaCarouselIndex + 2);
                        console.log('🎪 Navegación a prenda índice:', window.prendaCarouselIndex);
                        window.llenarReciboCosturaMobile(data);
                    };
                    
                    arrowContainer.appendChild(nextBtn);
                    console.log('✅ Botón siguiente agregado');
                }
                
                console.log('✅ Botones de navegación actualizados - Retroceder:', puedeRetroceder, 'Avanzar:', puedeAvanzar);
            }
        } else {
            // Ocultar el contenedor de flechas si no hay más de 2 prendas
            const arrowContainer = document.getElementById('arrow-container-mobile');
            if (arrowContainer) {
                arrowContainer.style.display = 'none';
            }
        }
    } else {
        console.log('⚠️ Sin prendas en data');
        // Ocultar el contenedor de flechas
        const arrowContainer = document.getElementById('arrow-container-mobile');
        if (arrowContainer) {
            arrowContainer.style.display = 'none';
        }
    }
    
    console.log('🎨 === llenarReciboCosturaMobile COMPLETADO ===');
};
</script>
