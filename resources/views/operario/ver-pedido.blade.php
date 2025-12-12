@extends('operario.layout')

@section('title', 'Pedido #' . $pedido['numero_pedido'])
@section('page-title', '')

@section('content')
<div class="ver-pedido-fullscreen">
    <!-- Header Negro -->
    <div class="pedido-header-negro">
        <button class="btn-volver-header" onclick="history.back()">
            <span class="material-symbols-rounded">arrow_back</span>
        </button>
        <h1 class="pedido-numero-header">#{{ $pedido['numero_pedido'] }}</h1>
    </div>

    <!-- Tabs (Arriba para cambiar vistas) -->
    <div class="pedido-tabs">
        <button class="tab-btn active" onclick="cambiarTab('orden')">
            <span class="material-symbols-rounded">description</span>
            LA ORDEN
        </button>
        <button class="tab-btn" onclick="cambiarTab('fotos')">
            <span class="material-symbols-rounded">image</span>
            FOTOS ({{ count($fotos) }})
        </button>
    </div>

    <!-- Contenido Principal (Cambia entre Factura y Fotos) -->
    <div class="pedido-content">
        <!-- Vista: LA ORDEN - Factura como Imagen -->
        <div id="tab-orden" class="tab-content active">
            <div class="pedido-modal-section">
                <div id="factura-html" class="pedido-modal-html" style="position: absolute; left: -9999px; top: -9999px; width: 764px;">
                    @include('components.orders-components.order-detail-modal')
                </div>
                <img id="factura-imagen" src="" alt="Factura" class="factura-img">
            </div>
        </div>

        <!-- Vista: FOTOS - Galería de Fotos -->
        <div id="tab-fotos" class="tab-content">
            <div class="fotos-grid">
                @if(count($fotos) > 0)
                    @foreach($fotos as $foto)
                        <div class="foto-card">
                            <img src="{{ $foto }}" alt="Foto del pedido" class="foto-imagen">
                        </div>
                    @endforeach
                @else
                    <div class="empty-fotos">
                        <span class="material-symbols-rounded">image_not_supported</span>
                        <p>No hay fotos disponibles para este pedido</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="ver-pedido-container" style="display: none;">
    <!-- Botón Volver -->
    <div class="header-actions">
        <a href="{{ route('operario.mis-pedidos') }}" class="btn-volver">
            <span class="material-symbols-rounded">arrow_back</span>
            Volver a Mis Pedidos
        </a>
    </div>

    <!-- Información Principal del Pedido -->
    <div class="pedido-header">
        <div class="pedido-numero-section">
            <h1 class="pedido-numero">#{{ $pedido['numero_pedido'] }}</h1>
            <span class="estado-badge {{ strtolower(str_replace(' ', '-', $pedido['estado'])) }}">
                {{ $pedido['estado'] }}
            </span>
        </div>

        <div class="pedido-info-grid">
            <div class="info-card">
                <span class="info-label">Cliente</span>
                <p class="info-value">{{ $pedido['cliente'] }}</p>
            </div>
            <div class="info-card">
                <span class="info-label">Área</span>
                <p class="info-value">{{ $pedido['area'] }}</p>
            </div>
            <div class="info-card">
                <span class="info-label">Fecha de Creación</span>
                <p class="info-value">{{ $pedido['fecha_creacion'] }}</p>
            </div>
            <div class="info-card">
                <span class="info-label">Fecha Estimada de Entrega</span>
                <p class="info-value">{{ $pedido['fecha_estimada'] ?? '-' }}</p>
            </div>
        </div>
    </div>

    <!-- Detalles del Pedido -->
    <div class="pedido-details">
        <div class="details-section">
            <h2>
                <span class="material-symbols-rounded">description</span>
                Descripción
            </h2>
            <div class="description-box">
                <p>{{ $pedido['descripcion'] }}</p>
            </div>
        </div>

        <div class="details-section">
            <h2>
                <span class="material-symbols-rounded">inventory_2</span>
                Información de Cantidad
            </h2>
            <div class="cantidad-info">
                <div class="cantidad-card">
                    <span class="cantidad-label">Total de Unidades</span>
                    <p class="cantidad-value">{{ $pedido['cantidad'] }}</p>
                </div>
                <div class="cantidad-card">
                    <span class="cantidad-label">Proceso Actual</span>
                    <p class="cantidad-value">{{ $pedido['area'] }}</p>
                </div>
                <div class="cantidad-card">
                    <span class="cantidad-label">Forma de Pago</span>
                    <p class="cantidad-value">{{ $pedido['forma_pago'] }}</p>
                </div>
                <div class="cantidad-card">
                    <span class="cantidad-label">Asesora</span>
                    <p class="cantidad-value">{{ $pedido['asesora'] }}</p>
                </div>
            </div>
        </div>

        <div class="details-section">
            <h2>
                <span class="material-symbols-rounded">info</span>
                Información Adicional
            </h2>
            <div class="additional-info">
                <div class="info-row">
                    <span class="info-key">Estado Actual:</span>
                    <span class="info-val">{{ $pedido['estado'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-key">Área Asignada:</span>
                    <span class="info-val">{{ $pedido['area'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-key">Novedades:</span>
                    <span class="info-val">{{ $pedido['novedades'] ?? 'Sin novedades' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="pedido-actions">
        <button class="btn-accion primary" onclick="marcarEnProceso()">
            <span class="material-symbols-rounded">schedule</span>
            Marcar en Proceso
        </button>
        <button class="btn-accion secondary" onclick="marcarCompletado()">
            <span class="material-symbols-rounded">check_circle</span>
            Marcar Completado
        </button>
    </div>
</div>

<style>
    /* Fullscreen Container */
    .ver-pedido-fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        background: white;
        z-index: 999;
        overflow: hidden;
    }

    /* Header Negro */
    .pedido-header-negro {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: #2c2c2c;
        color: white;
        padding: 1rem 1.5rem;
        flex-shrink: 0;
        z-index: 100;
    }

    .btn-volver-header {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: transparent;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-volver-header:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .btn-volver-header .material-symbols-rounded {
        font-size: 24px;
    }

    .pedido-numero-header {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }

    /* Tabs */
    .pedido-tabs {
        display: flex;
        background: white;
        border-bottom: 1px solid #eee;
        padding: 0;
        margin: 0;
    }

    .tab-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 1rem;
        background: white;
        color: #666;
        border: none;
        border-bottom: 3px solid transparent;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .tab-btn:hover {
        color: #333;
        background: #f9f9f9;
    }

    .tab-btn.active {
        color: #EF5350;
        border-bottom-color: #EF5350;
    }

    .tab-btn .material-symbols-rounded {
        font-size: 20px;
    }

    /* Modal Section */
    .pedido-modal-section {
        flex-shrink: 0;
        overflow-y: auto;
        overflow-x: auto;
        max-height: 50%;
        border-bottom: 1px solid #eee;
        margin: 0 auto;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    /* Factura como Imagen */
    .factura-img {
        width: 764px;
        height: auto;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: block;
        margin: 0 auto;
    }

    .pedido-modal-html {
        width: 100%;
    }

    /* Contenido Tabs */
    .pedido-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    /* Fotos Grid */
    .fotos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
    }

    .foto-card {
        background: white;
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .foto-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .foto-imagen {
        width: 100%;
        height: 150px;
        object-fit: cover;
        display: block;
    }

    .empty-fotos {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem 1rem;
        color: #999;
    }

    .empty-fotos .material-symbols-rounded {
        font-size: 48px;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-fotos p {
        margin: 0;
        font-size: 0.95rem;
    }


    /* Responsive */
    @media (max-width: 768px) {
        .fotos-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }

        .tab-btn {
            font-size: 0.75rem;
        }

        /* Móvil - Factura siempre 764px (sin escalar) */
        .pedido-modal-section {
            max-height: auto;
            width: 100%;
            margin: 0;
            padding: 1rem;
            overflow-x: auto;
            overflow-y: auto;
            touch-action: manipulation;
            user-select: none;
            justify-content: flex-start;
        }

        .factura-img {
            width: 764px !important;
            min-width: 764px !important;
            max-width: 764px !important;
            margin: 0 auto;
        }

        .ver-pedido-fullscreen {
            overflow: hidden;
        }

        .pedido-content {
            overflow-x: hidden;
        }
    }

    .ver-pedido-container {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Header Actions */
    .header-actions {
        margin-bottom: 2rem;
    }

    .btn-volver {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-volver:hover {
        background: #e0e0e0;
        border-color: #999;
    }

    /* Pedido Header */
    .pedido-header {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .pedido-numero-section {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .pedido-numero {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1976d2;
        margin: 0;
    }

    .estado-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .estado-badge.en-ejecución {
        background: #fff3e0;
        color: #f57c00;
    }

    .estado-badge.completada {
        background: #e8f5e9;
        color: #388e3c;
    }

    .estado-badge.pendiente {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    /* Info Grid */
    .pedido-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
    }

    .info-card {
        padding: 1rem;
        background: #f9f9f9;
        border-radius: 8px;
        border-left: 3px solid #1976d2;
    }

    .info-label {
        display: block;
        font-size: 0.85rem;
        color: #666;
        font-weight: 600;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        margin: 0;
        font-size: 1.1rem;
        color: #333;
        font-weight: 600;
    }

    /* Pedido Details */
    .pedido-details {
        display: grid;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .details-section {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .details-section h2 {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0 0 1.5rem 0;
        font-size: 1.25rem;
        color: #333;
    }

    .details-section h2 .material-symbols-rounded {
        color: #1976d2;
    }

    /* Description Box */
    .description-box {
        background: #f9f9f9;
        padding: 1.5rem;
        border-radius: 8px;
        border-left: 3px solid #1976d2;
    }

    .description-box p {
        margin: 0;
        color: #555;
        line-height: 1.6;
        font-size: 0.95rem;
    }

    /* Cantidad Info */
    .cantidad-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.5rem;
    }

    .cantidad-card {
        padding: 1.5rem;
        background: #f9f9f9;
        border-radius: 8px;
        text-align: center;
        border-top: 3px solid #1976d2;
    }

    .cantidad-label {
        display: block;
        font-size: 0.85rem;
        color: #666;
        font-weight: 600;
        margin-bottom: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .cantidad-value {
        margin: 0;
        font-size: 1.5rem;
        color: #1976d2;
        font-weight: 700;
    }

    /* Additional Info */
    .additional-info {
        display: grid;
        gap: 1rem;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 1rem;
        background: #f9f9f9;
        border-radius: 6px;
        border-left: 3px solid #1976d2;
    }

    .info-key {
        font-weight: 600;
        color: #666;
    }

    .info-val {
        color: #333;
        font-weight: 500;
    }

    /* Acciones */
    .pedido-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }

    .btn-accion {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-accion.primary {
        background: #1976d2;
        color: white;
    }

    .btn-accion.primary:hover {
        background: #1565c0;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
    }

    .btn-accion.secondary {
        background: #388e3c;
        color: white;
    }

    .btn-accion.secondary:hover {
        background: #2e7d32;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(56, 142, 60, 0.3);
    }

    .btn-accion .material-symbols-rounded {
        font-size: 24px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .ver-pedido-container {
            padding: 1rem;
        }

        .pedido-numero {
            font-size: 1.75rem;
        }

        .pedido-info-grid {
            grid-template-columns: 1fr;
        }

        .pedido-actions {
            flex-direction: column;
        }

        .btn-accion {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    // Cambiar entre tabs
    function cambiarTab(tabName) {
        // Ocultar todos los tabs
        const tabs = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => tab.classList.remove('active'));

        // Remover clase active de todos los botones
        const btns = document.querySelectorAll('.tab-btn');
        btns.forEach(btn => btn.classList.remove('active'));

        // Mostrar el tab seleccionado
        document.getElementById('tab-' + tabName).classList.add('active');

        // Agregar clase active al botón clickeado
        event.target.closest('.tab-btn').classList.add('active');
    }

    // Convertir factura a imagen al cargar (optimizado con logs)
    function generarImagenFactura() {
        const inicioTotal = performance.now();
        const numeroPedido = document.querySelector('.pedido-numero-header').textContent.replace('#', '');
        const cacheKey = `factura_${numeroPedido}`;
        
        console.log(`⏱️ [FACTURA #${numeroPedido}] Iniciando generación de imagen...`);
        
        // Limpiar caché antiguo (blob URLs no válidos)
        sessionStorage.removeItem(cacheKey);
        
        // Verificar si ya existe en caché (solo Data URLs válidos)
        const cachedImage = sessionStorage.getItem(cacheKey);
        if (cachedImage && cachedImage.startsWith('data:')) {
            const imgElement = document.getElementById('factura-imagen');
            imgElement.src = cachedImage;
            imgElement.style.display = 'block';
            document.getElementById('factura-html').style.display = 'none';
            const tiempoTotal = performance.now() - inicioTotal;
            console.log(`✅ [FACTURA #${numeroPedido}] Cargada desde CACHÉ en ${tiempoTotal.toFixed(2)}ms`);
            return;
        }

        // Si no está en caché, generar con optimizaciones
        const htmlElement = document.querySelector('.pedido-modal-html');
        const inicioConversion = performance.now();
        
        // Mostrar indicador de carga
        const imgElement = document.getElementById('factura-imagen');
        imgElement.style.opacity = '0.5';
        
        console.log(`⏳ [FACTURA #${numeroPedido}] Convirtiendo HTML a imagen...`);
        
        // Clonar el elemento para no afectar el original
        const clonedElement = htmlElement.cloneNode(true);
        clonedElement.style.display = 'block';
        clonedElement.style.position = 'fixed';
        clonedElement.style.width = '764px';
        clonedElement.style.height = 'auto';
        clonedElement.style.zIndex = '-9999';
        clonedElement.style.left = '0';
        clonedElement.style.top = '0';
        clonedElement.style.visibility = 'visible';
        clonedElement.style.opacity = '1';
        
        // Agregar el clon al DOM
        document.body.appendChild(clonedElement);
        
        console.log(`📐 [FACTURA #${numeroPedido}] Clon creado: offsetWidth=${clonedElement.offsetWidth}px, offsetHeight=${clonedElement.offsetHeight}px`);
        
        // Esperar a que el DOM se actualice
        setTimeout(() => {
            console.log(`📐 [FACTURA #${numeroPedido}] Después de setTimeout: offsetWidth=${clonedElement.offsetWidth}px, offsetHeight=${clonedElement.offsetHeight}px`);
            
            html2canvas(clonedElement, {
                scale: 1,
                width: 764,
                height: clonedElement.offsetHeight,
                backgroundColor: '#ffffff',
                allowTaint: true,
                useCORS: true,
                logging: false,
                proxy: null,
                imageTimeout: 0,
                ignoreElements: function(element) {
                    return element.id === 'factura-imagen';
                }
            }).then(canvas => {
                console.log(`📐 [FACTURA #${numeroPedido}] Canvas generado: width=${canvas.width}px, height=${canvas.height}px`);
                
                // Remover el clon del DOM
                document.body.removeChild(clonedElement);
            const tiempoHtml2Canvas = performance.now() - inicioConversion;
            console.log(`📊 [FACTURA #${numeroPedido}] html2canvas completado en ${tiempoHtml2Canvas.toFixed(2)}ms`);
            
            const inicioDataUrl = performance.now();
            
            // Convertir a Data URL (más persistente que blob URL)
            const dataUrl = canvas.toDataURL('image/png');
            const tiempoDataUrl = performance.now() - inicioDataUrl;
            
            imgElement.src = dataUrl;
            imgElement.style.display = 'block';
            imgElement.style.opacity = '1';

            // Ocultar HTML
            document.getElementById('factura-html').style.display = 'none';

            // Guardar en caché de sesión (Data URL persiste)
            sessionStorage.setItem(cacheKey, dataUrl);

            // Guardar URL para descarga
            imgElement.dataset.downloadUrl = dataUrl;
            
            const tiempoTotal = performance.now() - inicioTotal;
            console.log(`📦 [FACTURA #${numeroPedido}] Data URL creado en ${tiempoDataUrl.toFixed(2)}ms`);
            console.log(`✅ [FACTURA #${numeroPedido}] Imagen generada y guardada en caché en ${tiempoTotal.toFixed(2)}ms`);
            console.log(`📈 [FACTURA #${numeroPedido}] Desglose: html2canvas=${tiempoHtml2Canvas.toFixed(2)}ms + dataUrl=${tiempoDataUrl.toFixed(2)}ms`);
            }).catch(error => {
                console.error(`❌ [FACTURA #${numeroPedido}] Error al generar imagen:`, error);
                imgElement.style.opacity = '1';
                // Si falla, mostrar HTML como fallback
                document.getElementById('factura-html').style.display = 'block';
                imgElement.style.display = 'none';
            });
        }, 100); // Esperar 100ms para que el DOM se actualice
    }

    // Convertir factura a imagen y descargar
    function convertirFacturaAImagen() {
        const imgElement = document.getElementById('factura-imagen');
        const numeroPedido = document.querySelector('.pedido-numero-header').textContent.replace('#', '');

        // Mostrar mensaje de carga
        const btn = event.target.closest('.tab-btn');
        const textOriginal = btn.innerHTML;
        btn.innerHTML = '<span class="material-symbols-rounded">hourglass_empty</span> DESCARGANDO...';
        btn.disabled = true;

        // Descargar imagen WebP
        const link = document.createElement('a');
        link.href = imgElement.dataset.downloadUrl;
        link.download = `Factura_${numeroPedido}.webp`;
        link.click();

        // Restaurar botón
        btn.innerHTML = textOriginal;
        btn.disabled = false;
    }

    // Llenar datos del modal
    function llenarDatosModal() {
        const pedido = {
            numero_pedido: '{{ $pedido['numero_pedido'] }}',
            cliente: '{{ $pedido['cliente'] }}',
            asesora: '{{ $pedido['asesora'] ?? 'N/A' }}',
            forma_pago: '{{ $pedido['forma_pago'] ?? 'N/A' }}',
            descripcion: '{{ $pedido['descripcion'] ?? 'N/A' }}',
            fecha_creacion: '{{ $pedido['fecha_creacion'] ?? now()->format('d/m/Y') }}'
        };

        // Llenar cliente
        document.getElementById('cliente-value').textContent = pedido.cliente;

        // Llenar asesora
        document.getElementById('asesora-value').textContent = pedido.asesora;

        // Llenar forma de pago
        document.getElementById('forma-pago-value').textContent = pedido.forma_pago;

        // Llenar descripción
        document.getElementById('descripcion-text').textContent = pedido.descripcion;

        // Llenar número de pedido
        document.getElementById('order-pedido').textContent = '#' + pedido.numero_pedido;

        // Llenar fecha
        const fecha = new Date(pedido.fecha_creacion);
        document.querySelector('.day-box').textContent = fecha.getDate();
        document.querySelector('.month-box').textContent = fecha.getMonth() + 1;
        document.querySelector('.year-box').textContent = fecha.getFullYear();

        // Llenar encargado (usuario actual)
        document.getElementById('encargado-value').textContent = '{{ auth()->user()->name ?? 'N/A' }}';

        // Llenar prendas entregadas
        document.getElementById('prendas-entregadas-value').textContent = '{{ $pedido['cantidad'] ?? 0 }}';
    }

    // Generar imagen al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        llenarDatosModal();
        setTimeout(generarImagenFactura, 500);
    });

    function marcarEnProceso() {
        alert('Funcionalidad de marcar en proceso será implementada');
    }

    function marcarCompletado() {
        alert('Funcionalidad de marcar completado será implementada');
    }
</script>
@endsection
