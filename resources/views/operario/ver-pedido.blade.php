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
        <!-- Vista: LA ORDEN - Recibo unificado para móvil y desktop -->
        <div id="tab-orden" class="tab-content active">
            <div class="pedido-modal-section">
                <!-- Recibo Unificado -->
                <div id="factura-container-mobile" 
                     style="display: none; width: 100%; display: flex; justify-content: center;"
                     data-numero-pedido="{{ $pedido['numero_pedido'] }}"
                     data-version="v2"
                     data-user-role="{{ auth()->user()->roles->pluck('name')->first() }}"
                     data-tipo-recibo="{{ request('tipo_recibo', '') }}">
                    @include('components.orders-components.order-detail-modal-mobile')
                </div>
            </div>
        </div>

        <!-- Vista: FOTOS - Galería de Fotos -->
        <div id="tab-fotos" class="tab-content">
            <div class="fotos-grid" id="fotos-grid">
                <!-- Las fotos se cargarán dinámicamente desde la API -->
                <div class="empty-fotos">
                    <span class="material-symbols-rounded">hourglass_empty</span>
                    <p>Cargando fotos...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ===== MODAL DE GALERÍA FULLSCREEN ===== -->
    <div id="modal-galeria" class="modal-galeria" style="display: none;">
        <!-- Fondo oscuro clickeable -->
        <div class="galeria-overlay" onclick="cerrarGaleria()"></div>
        
        <!-- Contenedor de la galería -->
        <div class="galeria-container">
            <!-- Botón cerrar -->
            <button class="btn-cerrar-galeria" onclick="cerrarGaleria()">
                <span class="material-symbols-rounded">close</span>
            </button>
            
            <!-- Flecha anterior -->
            <button class="btn-nav btn-anterior" onclick="fotoAnterior()" id="btn-anterior">
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
            
            <!-- Imagen principal -->
            <div class="galeria-imagen-container">
                <img id="galeria-imagen-principal" src="" alt="Foto" class="galeria-imagen" />
                <div class="galeria-info">
                    <span id="galeria-contador">1/3</span>
                </div>
            </div>
            
            <!-- Flecha siguiente -->
            <button class="btn-nav btn-siguiente" onclick="fotoSiguiente()" id="btn-siguiente">
                <span class="material-symbols-rounded">chevron_right</span>
            </button>
        </div>
        
        <!-- Miniaturas (thumbnails) en la parte inferior -->
        <div class="galeria-thumbnails" id="galeria-thumbnails">
            <!-- Se llena dinámicamente -->
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
                <p>{{ $pedido['descripcion_prendas'] ?? $pedido['descripcion'] ?? 'N/A' }}</p>
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
        @if(!(strtolower($pedido['estado']) === 'completada' || strtolower($pedido['estado']) === 'completado'))
        <button class="btn-accion secondary" onclick="marcarCompletado()">
            <span class="material-symbols-rounded">check_circle</span>
            Marcar Completado
        </button>
        @endif
        <button class="btn-accion warning" onclick="abrirModalReportarNovedad()">
            <span class="material-symbols-rounded">error_outline</span>
            Reportar Novedad
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
        max-height: 60vh;
        border-bottom: 1px solid #eee;
        margin: 0 auto;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding: 1rem;
    }

    /* Contenedor de Factura */
    .factura-container {
        width: 100%;
        max-width: 800px;
        max-height: 600px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
        touch-action: none;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .factura-img {
        display: block;
        max-width: 100%;
        height: auto;
        width: 764px;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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

    /* ===== ESTILOS MODAL GALERÍA ===== */
    .modal-galeria {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.98);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    }

    .galeria-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .galeria-container {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 90%;
        max-width: 800px;
        height: 70%;
        z-index: 10001;
    }

    .btn-cerrar-galeria {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 1.5rem;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.3s ease;
        z-index: 10002;
    }

    .btn-cerrar-galeria:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: scale(1.1);
    }

    .galeria-imagen-container {
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        user-select: none;
    }

    .galeria-imagen {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        cursor: grab;
    }

    .galeria-imagen:active {
        cursor: grabbing;
    }

    .galeria-info {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 1.5rem;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.3s ease;
        z-index: 10001;
    }

    .btn-nav:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: translateY(-50%) scale(1.1);
    }

    .btn-nav:active {
        transform: translateY(-50%) scale(0.95);
    }

    .btn-anterior {
        left: 10px;
    }

    .btn-siguiente {
        right: 10px;
    }

    .galeria-thumbnails {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.5);
        overflow-x: auto;
        z-index: 10001;
    }

    .thumbnail {
        width: 60px;
        height: 60px;
        border-radius: 4px;
        overflow: hidden;
        cursor: pointer;
        opacity: 0.6;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .thumbnail.activo {
        opacity: 1;
        border-color: #0066cc;
    }

    .thumbnail:hover {
        opacity: 0.8;
    }

    .thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .galeria-container {
            width: 95%;
            height: 60%;
        }

        .btn-cerrar-galeria,
        .btn-nav {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
        }

        .btn-anterior {
            left: 5px;
        }

        .btn-siguiente {
            right: 5px;
        }

        .galeria-info {
            font-size: 0.8rem;
            padding: 6px 12px;
        }

        .galeria-thumbnails {
            height: auto;
            max-height: 100px;
        }

        .thumbnail {
            width: 50px;
            height: 50px;
        }
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

    function llenarDatosModal() {
        // Esta función ahora solo es usada por el API
        // Los datos se llenan a través de llenarReciboCosturaMobile()
    }

    // Detectar si es móvil
    function esMobile() {
        return window.innerWidth < 768;
    }

    // Generar imagen al cargar la página - Ejecutar inmediatamente
    /**
     * Función: llenarFotos
     * Carga las fotos en la galería desde la API
     */
    let fotosGlobales = []; // Variable global para guardar las fotos
    let indiceActualGaleria = 0; // Índice de la foto actual en la galería
    
    function llenarFotos(fotos) {
        fotosGlobales = fotos; // Guardar fotos globalmente
        
        const fotosGrid = document.getElementById('fotos-grid');
        if (!fotosGrid) {
            return;
        }
        
        // ACTUALIZAR CONTADOR DE FOTOS EN EL BADGE
        const tabFotosBtn = document.querySelector('.tab-btn[onclick="cambiarTab(\'fotos\')"]');
        if (tabFotosBtn && fotos && fotos.length > 0) {
            // Encontrar el span con el contador y actualizarlo
            const contador = tabFotosBtn.querySelector('.tab-foto-contador');
            if (contador) {
                contador.textContent = fotos.length;
            } else {
                // Si no existe, buscar el texto y reemplazarlo
                tabFotosBtn.innerHTML = tabFotosBtn.innerHTML.replace(/FOTOS \(\d+\)/, `FOTOS (${fotos.length})`);
            }
            console.log('📸 [CONTADOR ACTUALIZADO]', fotos.length, 'fotos');
        }
        
        // Limpiar contenido actual
        fotosGrid.innerHTML = '';
        
        if (!fotos || fotos.length === 0) {
            fotosGrid.innerHTML = `
                <div class="empty-fotos">
                    <span class="material-symbols-rounded">image_not_supported</span>
                    <p>No hay fotos disponibles para este pedido</p>
                </div>
            `;
            return;
        }
        
        // Generar HTML para cada foto
        fotos.forEach((foto, index) => {
            const fotoCard = document.createElement('div');
            fotoCard.className = 'foto-card';
            fotoCard.style.cursor = 'pointer';
            fotoCard.onclick = function() {
                abrirGaleria(index);
            };
            
            // Usar lazy loading nativo del navegador para optimizar carga
            fotoCard.innerHTML = `
                <img 
                    src="${foto}" 
                    alt="Foto del pedido ${index + 1}" 
                    class="foto-imagen"
                    loading="lazy"
                    decoding="async"
                />
            `;
            fotosGrid.appendChild(fotoCard);
        });
        
        console.log(' Galería de fotos cargada con', fotos.length, 'imagen(es) - Lazy loading activado');
    }
    
    /**
     * Función: abrirGaleria
     * Abre el modal de galería en el índice especificado
     */
    function abrirGaleria(indice = 0) {
        indiceActualGaleria = indice;
        const modal = document.getElementById('modal-galeria');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Evitar scroll
        actualizarGaleria();
        inicializarSwipe();
    }
    
    /**
     * Función: cerrarGaleria
     * Cierra el modal de galería
     */
    function cerrarGaleria() {
        const modal = document.getElementById('modal-galeria');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restaurar scroll
    }
    
    /**
     * Función: fotoAnterior
     * Navega a la foto anterior
     */
    function fotoAnterior() {
        if (indiceActualGaleria > 0) {
            indiceActualGaleria--;
            actualizarGaleria();
        }
    }
    
    /**
     * Función: fotoSiguiente
     * Navega a la foto siguiente
     */
    function fotoSiguiente() {
        if (indiceActualGaleria < fotosGlobales.length - 1) {
            indiceActualGaleria++;
            actualizarGaleria();
        }
    }
    
    /**
     * Función: actualizarGaleria
     * Actualiza la visualización de la galería con preloading de siguiente/anterior
     */
    let imagenesPreCargadas = {}; // Cache de imágenes precargadas
    
    function actualizarGaleria() {
        const imagen = document.getElementById('galeria-imagen-principal');
        const contador = document.getElementById('galeria-contador');
        const btnAnterior = document.getElementById('btn-anterior');
        const btnSiguiente = document.getElementById('btn-siguiente');
        
        if (!imagen) return;
        
        // Actualizar imagen principal
        imagen.src = fotosGlobales[indiceActualGaleria];
        
        // Actualizar contador
        contador.textContent = `${indiceActualGaleria + 1}/${fotosGlobales.length}`;
        
        // Mostrar/ocultar botones de navegación
        btnAnterior.style.opacity = indiceActualGaleria === 0 ? '0.3' : '1';
        btnAnterior.style.pointerEvents = indiceActualGaleria === 0 ? 'none' : 'auto';
        
        btnSiguiente.style.opacity = indiceActualGaleria === fotosGlobales.length - 1 ? '0.3' : '1';
        btnSiguiente.style.pointerEvents = indiceActualGaleria === fotosGlobales.length - 1 ? 'none' : 'auto';
        
        // ===== PRECARGAR SIGUIENTE Y ANTERIOR (Optimización) =====
        precargarImagenes(indiceActualGaleria);
    }
    
    /**
     * Función: precargarImagenes
     * Precarga la imagen siguiente y anterior en memoria del navegador
     * Esto hace que la navegación sea mucho más rápida
     */
    function precargarImagenes(indiceActual) {
        // Precargar imagen anterior
        if (indiceActual > 0) {
            const urlAnterior = fotosGlobales[indiceActual - 1];
            if (urlAnterior && !imagenesPreCargadas[urlAnterior]) {
                const imgAnterior = new Image();
                imgAnterior.src = urlAnterior;
                imagenesPreCargadas[urlAnterior] = true;
            }
        }
        
        // Precargar imagen siguiente
        if (indiceActual < fotosGlobales.length - 1) {
            const urlSiguiente = fotosGlobales[indiceActual + 1];
            if (urlSiguiente && !imagenesPreCargadas[urlSiguiente]) {
                const imgSiguiente = new Image();
                imgSiguiente.src = urlSiguiente;
                imagenesPreCargadas[urlSiguiente] = true;
            }
        }
    }
    
    /**
     * Función: inicializarSwipe
     * Inicializa el sistema de swipe/deslizar
     */
    let xInicio = 0;
    let yInicio = 0;
    
    function inicializarSwipe() {
        const galeria = document.getElementById('modal-galeria');
        const container = document.querySelector('.galeria-imagen-container');
        
        if (!container) return;
        
        // Eventos de mouse
        container.addEventListener('mousedown', function(e) {
            xInicio = e.clientX;
            yInicio = e.clientY;
        });
        
        container.addEventListener('mouseup', function(e) {
            handleSwipe(e.clientX, e.clientY);
        });
        
        // Eventos de touch
        container.addEventListener('touchstart', function(e) {
            xInicio = e.touches[0].clientX;
            yInicio = e.touches[0].clientY;
        });
        
        container.addEventListener('touchend', function(e) {
            handleSwipe(e.changedTouches[0].clientX, e.changedTouches[0].clientY);
        });
    }
    
    /**
     * Función: handleSwipe
     * Maneja el evento de swipe
     */
    function handleSwipe(xFinal, yFinal) {
        const diferenciax = xInicio - xFinal;
        const diferenciay = yInicio - yFinal;
        
        // Si el movimiento vertical es mayor, ignorar (scroll vertical)
        if (Math.abs(diferenciay) > Math.abs(diferenciax)) {
            return;
        }
        
        // Umbral mínimo de deslizamiento
        const umbral = 50;
        
        if (Math.abs(diferenciax) > umbral) {
            if (diferenciax > 0) {
                // Deslizó a la izquierda -> siguiente foto
                fotoSiguiente();
            } else {
                // Deslizó a la derecha -> anterior foto
                fotoAnterior();
            }
        }
    }
    
    // Cerrar galería con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarGaleria();
        }
    });
    
    // Generar imagen al cargar la página - Ejecutar inmediatamente
    // Esperar un poco para asegurar que el DOM esté listo
    setTimeout(function() {
        console.log('🎬 [VER-PEDIDO] ===== INICIALIZANDO PÁGINA =====');
        
        llenarDatosModal();
        const containerMobile = document.getElementById('factura-container-mobile');
        
        console.log('🔎 Buscando containerMobile:', {
            existe: !!containerMobile,
            elemento: containerMobile,
            totalIds: document.querySelectorAll('[id]').length
        });
        
        if (!containerMobile) {
            console.error('❌ ERROR: No se encontró #factura-container-mobile');
            console.log('📋 IDs disponibles en el documento:', Array.from(document.querySelectorAll('[id]')).map(el => el.id));
            return;
        }
        
        // Mostrar el contenedor
        console.log('📦 Mostrando containerMobile');
        containerMobile.style.display = 'block';
        
        const numeroPedido = containerMobile.dataset.numeroPedido;
        const tipoRecibo = containerMobile.dataset.tipoRecibo || '';
        console.log('📌 Número de pedido del dataset:', numeroPedido);
        console.log('📌 Tipo de recibo del dataset:', tipoRecibo);
        
        if (!numeroPedido) {
            console.error('❌ ERROR: numeroPedido no encontrado en dataset');
            return;
        }
        
        // USAR EL NUEVO ENDPOINT: /api/operario/pedido/{numeroPedido}
        // Retorna exactamente la misma estructura que /pedidos-public/{id}/recibos-datos
        let apiUrl = '/api/operario/pedido/' + numeroPedido;
        if (tipoRecibo) {
            apiUrl += '?tipo_recibo=' + encodeURIComponent(tipoRecibo);
        }
        console.log('🌐 URL API (nuevo endpoint operario):', apiUrl);
        
        console.log('🚀 Iniciando fetch a:', apiUrl);
        fetch(apiUrl)
            .then(function(response) {
                console.log('📨 Respuesta del servidor:', {
                    ok: response.ok,
                    status: response.status,
                    statusText: response.statusText,
                    contentType: response.headers.get('content-type')
                });
                
                if (!response.ok) {
                    console.error('❌ ERROR: Status no OK:', response.status);
                    throw new Error('API error: ' + response.status);
                }
                return response.json();
            })
            .then(function(response) {
                console.log('📦 Datos JSON recibidos:', response);
                console.log('✅ ¿Tiene success?', response.success);
                console.log('✅ ¿Tiene data?', !!response.data);
                
                // El endpoint retorna {success: true, data: {...}}
                if (!response.success || !response.data) {
                    console.error('❌ ERROR: Respuesta inválida');
                    throw new Error('Respuesta inválida del API');
                }

                const data = response.data;
                console.log('✅ Datos válidos:', data);
                
                // Procesar prendas para construir descripción formateada
                let descripcionFormateada = '';
                if (data.prendas && Array.isArray(data.prendas)) {
                    data.prendas.forEach(function(prenda, idx) {
                        if (!descripcionFormateada) {
                            const lineas = [];
                            
                            // Prenda
                            lineas.push('<strong style="font-size: 13.4px;">PRENDA ' + (idx + 1) + ': ' + (prenda.nombre || 'N/A') + '</strong>');
                            
                            // Telas
                            if (prenda.colores_telas && prenda.colores_telas.length > 0) {
                                const telas = prenda.colores_telas.map(function(ct) {
                                    return (ct.tela_nombre || '') + ' / ' + (ct.color_nombre || '') + ' | REF: ' + (ct.referencia || '');
                                }).join(' | ');
                                lineas.push('<strong>TELAS:</strong> ' + telas);
                            }
                            
                            // Manga
                            if (prenda.manga) {
                                lineas.push('<strong>MANGA:</strong> ' + (prenda.manga || '').toUpperCase());
                            }
                            
                            // Bolsillos
                            if (prenda.obs_bolsillos) {
                                lineas.push('• <strong>BOLSILLOS:</strong> ' + (prenda.obs_bolsillos || ''));
                            }
                            
                            // Broche/Botón - Mostrar label dinámico según el tipo
                            if (prenda.obs_broche && prenda.broche) {
                                const labelBroche = prenda.broche.toUpperCase();
                                lineas.push('• <strong>' + labelBroche + ':</strong> ' + (prenda.obs_broche || ''));
                            }
                            
                            // Unir las líneas principales con <br>
                            descripcionFormateada = lineas.join('<br>');
                            
                            // Agregar tallas si existen
                            if (prenda.tallas && typeof prenda.tallas === 'object') {
                                descripcionFormateada += '<br><br><strong>TALLAS</strong><br>';
                                const tallasLineas = [];
                                Object.keys(prenda.tallas).forEach(function(genero) {
                                    const tallasCantidades = [];
                                    Object.keys(prenda.tallas[genero]).forEach(function(talla) {
                                        const cantidad = prenda.tallas[genero][talla];
                                        tallasCantidades.push(talla + ': <span style="color: red;"><strong>' + cantidad + '</strong></span>');
                                    });
                                    if (tallasCantidades.length > 0) {
                                        tallasLineas.push(genero.toUpperCase() + ': ' + tallasCantidades.join(', '));
                                    }
                                });
                                descripcionFormateada += tallasLineas.join('<br>');
                            }
                        }
                    });
                }

                // Extraer el consecutivo de recibo COSTURA para operarios costura-reflectivo
                let numeroReciboCostura = null;
                const userRole = document.querySelector('[data-user-role]')?.getAttribute('data-user-role');
                
                // Obtener del blade (inyectado por el controlador)
                numeroReciboCostura = '{{ $pedido["numero_recibo_costura"] ?? null }}';
                
                console.log('🔢 [NUMERO RECIBO COSTURA]', numeroReciboCostura, 'tipo:', typeof numeroReciboCostura);

                // Construir objeto para llenarReciboCosturaMobile con la misma estructura
                const pedidoData = {
                    fecha: data.fecha_creacion || new Date().toISOString().split('T')[0],
                    asesora: data.asesor || 'N/A',
                    formaPago: data.forma_de_pago || 'N/A',
                    cliente: data.cliente || 'N/A',
                    numeroPedido: (numeroReciboCostura && numeroReciboCostura !== 'null' && numeroReciboCostura !== '') ? numeroReciboCostura : (data.numero_pedido || numeroPedido),
                    encargado: 'Operario',
                    prendasEntregadas: data.total_prendas + '/' + data.total_prendas,
                    descripcion: descripcionFormateada,
                    prendas: data.prendas || [],
                    numeroReciboCostura: numeroReciboCostura  // Agregar para referencia
                };

                // Resetear índice del carrusel para empezar desde el primer proceso
                window.procesoCarouselIndex = 0;

                if (window.llenarReciboCosturaMobile) {
                    window.llenarReciboCosturaMobile(pedidoData);
                } else {
                    console.warn(' llenarReciboCosturaMobile no está disponible');
                }
                
                // ===== CARGAR FOTOS DIRECTAMENTE DESDE LOS DATOS DEL PEDIDO =====
                // Fuentes de fotos (SIN DUPLICAR):
                // 1. prendas[].imagenes (FOTOS DIRECTAS DE PRENDA - NUEVAS)
                // 2. prendas[].telas_array[].fotos_tela (FOTOS PRINCIPALES DE TELAS)
                // 3. prendas[].procesos[].imagenes (FOTOS DE PROCESOS/PROCEDIMIENTOS)
                const todasLasFotos = [];
                
                if (data.prendas && Array.isArray(data.prendas)) {
                    data.prendas.forEach(function(prenda) {
                        // 1. Fotos directas de prenda (NUEVAS - prenda_fotos_pedido)
                        if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
                            prenda.imagenes.forEach(function(img) {
                                if (img.ruta_webp || img.url) {
                                    todasLasFotos.push(img.ruta_webp || img.url);
                                }
                            });
                        }
                        
                        // 2. Fotos de telas_array (FUENTE PRINCIPAL PARA TELAS)
                        if (prenda.telas_array && Array.isArray(prenda.telas_array)) {
                            prenda.telas_array.forEach(function(tela) {
                                // Usar solo fotos_tela (que es la fuente única de verdad)
                                if (tela.fotos_tela && Array.isArray(tela.fotos_tela)) {
                                    tela.fotos_tela.forEach(function(img) {
                                        if (img.ruta_webp || img.url) {
                                            todasLasFotos.push(img.ruta_webp || img.url);
                                        }
                                    });
                                }
                            });
                        }
                        
                        // 3. Fotos de procesos (FUENTE ÚNICA PARA PROCESOS)
                        if (prenda.procesos && Array.isArray(prenda.procesos)) {
                            prenda.procesos.forEach(function(proceso) {
                                if (proceso.imagenes && Array.isArray(proceso.imagenes)) {
                                    proceso.imagenes.forEach(function(img) {
                                        if (img.ruta_webp) {
                                            todasLasFotos.push(img.ruta_webp);
                                        }
                                    });
                                }
                            });
                        }
                    });
                }
                
                // Eliminar duplicados usando Set
                const fotosUnicas = Array.from(new Set(todasLasFotos));
                console.log('📸 Fotos cargadas:', fotosUnicas.length);
                llenarFotos(fotosUnicas);

            })
            .catch(function(error) {
                console.error('❌ ERROR en fetch/then:', {
                    mensaje: error.message,
                    stack: error.stack,
                    error: error
                });
            });
    }, 500);

    function marcarEnProceso() {
        alert('Funcionalidad de marcar en proceso será implementada');
    }

    function marcarCompletado() {
        const numeroPedido = '{{ $pedido['numero_pedido'] }}';
        
        if (!confirm('¿Marcar este proceso como completado?')) {
            return;
        }

        fetch(`/api/operario/completar-proceso/${numeroPedido}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Proceso marcado como completado');
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'No se pudo completar el proceso'));
            }
        })
        .catch(error => {
            alert('Error al completar el proceso');
        });
    }

    // ========================================
    // SISTEMA DE ZOOM Y PAN PARA FACTURA
    // ========================================
    (function() {
        let currentZoom = 1;
        const MIN_ZOOM = 0.5;
        const MAX_ZOOM = 2;
        const ZOOM_STEP = 0.1;
        
        const facturaImg = document.getElementById('factura-imagen');
        const facturaContainer = document.getElementById('factura-container');
        
        let lastDistance = 0;
        
        function updateZoom() {
            // Aplicar zoom solo con scale
            facturaImg.style.transform = `scale(${currentZoom})`;
            facturaImg.style.transformOrigin = 'center';
            facturaImg.style.cursor = currentZoom > 1 ? 'grab' : 'default';
        }
        
        // Función para calcular distancia entre dos puntos táctiles
        function getDistance(touches) {
            if (touches.length < 2) return 0;
            const dx = touches[0].clientX - touches[1].clientX;
            const dy = touches[0].clientY - touches[1].clientY;
            return Math.sqrt(dx * dx + dy * dy);
        }
        
        // ========== PINCH ZOOM (dos dedos) ==========
        if (facturaContainer) {
            facturaContainer.addEventListener('touchstart', (e) => {
                if (e.touches.length === 2) {
                    lastDistance = getDistance(e.touches);
                }
            }, { passive: true });
            
            facturaContainer.addEventListener('touchmove', (e) => {
                if (e.touches.length === 2) {
                    const currentDistance = getDistance(e.touches);
                    
                    if (lastDistance > 0) {
                        const ratio = currentDistance / lastDistance;
                        let newZoom = currentZoom * ratio;
                        
                        if (newZoom >= MIN_ZOOM && newZoom <= MAX_ZOOM) {
                            currentZoom = newZoom;
                            updateZoom();
                        }
                    }
                    
                    lastDistance = currentDistance;
                }
            }, { passive: true });
            
            facturaContainer.addEventListener('touchend', (e) => {
                lastDistance = 0;
            }, { passive: true });
        }
        
        // ========== ZOOM CON RUEDA (Ctrl+Scroll) ==========
        if (facturaContainer) {
            facturaContainer.addEventListener('wheel', (e) => {
                if (e.ctrlKey) {
                    e.preventDefault();
                    const direction = e.deltaY > 0 ? -1 : 1;
                    const newZoom = currentZoom + (direction * ZOOM_STEP);
                    
                    if (newZoom >= MIN_ZOOM && newZoom <= MAX_ZOOM) {
                        currentZoom = newZoom;
                        updateZoom();
                    }
                }
            }, { passive: false });
        }
    })();

    // ========================================
    // SISTEMA DE REPORTAR NOVEDAD
    // ========================================
    function abrirModalReportarNovedad() {
        const modalReportar = document.getElementById('modal-reportar-novedad');
        if (modalReportar) {
            modalReportar.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function cerrarModalReportarNovedad() {
        const modalReportar = document.getElementById('modal-reportar-novedad');
        if (modalReportar) {
            modalReportar.style.display = 'none';
            document.body.style.overflow = 'auto';
            // Limpiar el formulario
            document.getElementById('form-reportar-novedad').reset();
        }
    }

    function enviarNovedad() {
        const numeroPedido = document.getElementById('numero-pedido-novedad').value;
        const novedadTexto = document.getElementById('textarea-novedad').value.trim();

        if (!novedadTexto) {
            mostrarModalRespuesta('Error', 'Por favor escribe la novedad', 'error');
            return;
        }

        // Mostrar loading
        mostrarModalRespuesta('Enviando...', 'Por favor espera mientras se guarda la novedad', 'loading');

        fetch('/reportar-pendiente', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                numero_pedido: numeroPedido,
                novedad: novedadTexto
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar estado en la UI
                actualizarEstadoEnUI(data.estado_nuevo);
                
                mostrarModalRespuesta(' Éxito', data.message, 'success');
                
                // Limpiar formulario
                document.getElementById('form-reportar-novedad').reset();
                
                // Recargar después de 2 segundos para mostrar novedades actualizadas
                setTimeout(() => {
                    cerrarModalReportarNovedad();
                    location.reload();
                }, 2000);
            } else {
                mostrarModalRespuesta(' Error', data.message, 'error');
            }
        })
        .catch(error => {
            mostrarModalRespuesta(' Error', 'Error al enviar la novedad', 'error');
        });
    }

    function actualizarEstadoEnUI(estadoNuevo) {
        // Buscar el badge de estado en la ficha
        const estadoBadges = document.querySelectorAll('.estado-badge, .info-val');
        
        estadoBadges.forEach(badge => {
            // Actualizar si contiene un estado
            if (badge.textContent === 'Pendiente' || badge.textContent === 'En Ejecución' || 
                badge.textContent === 'Completado' || badge.textContent === 'Pausado') {
                badge.textContent = estadoNuevo;
                badge.className = 'info-val'; // o el class que use
            }
        });

        // También actualizar el badge de estado principal si existe
        const estadoPrincipal = document.querySelector('.estado-badge');
        if (estadoPrincipal) {
            const claseAntigua = estadoPrincipal.className;
            estadoPrincipal.className = 'estado-badge pendiente';
            estadoPrincipal.textContent = estadoNuevo;
        }
    }

    function mostrarModalRespuesta(titulo, mensaje, tipo) {
        const modal = document.getElementById('modal-respuesta-novedad');
        const tituloEl = document.getElementById('respuesta-titulo');
        const mensajeEl = document.getElementById('respuesta-mensaje');
        const iconoEl = document.getElementById('respuesta-icono');

        tituloEl.textContent = titulo;
        mensajeEl.textContent = mensaje;

        // Cambiar icono según tipo
        if (tipo === 'success') {
            iconoEl.textContent = 'check_circle';
            iconoEl.style.color = '#4caf50';
        } else if (tipo === 'error') {
            iconoEl.textContent = 'error';
            iconoEl.style.color = '#f44336';
        } else if (tipo === 'loading') {
            iconoEl.textContent = 'hourglass_empty';
            iconoEl.style.color = '#2196f3';
            iconoEl.classList.add('spinner');
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalRespuesta() {
        const modal = document.getElementById('modal-respuesta-novedad');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Cerrar modal al hacer click fuera
    document.addEventListener('click', function(e) {
        const modalReportar = document.getElementById('modal-reportar-novedad');
        if (modalReportar && e.target === modalReportar) {
            cerrarModalReportarNovedad();
        }
    });

</script>

<!-- ===== MODAL REPORTAR NOVEDAD ===== -->
<div id="modal-reportar-novedad" class="modal-reportar-novedad" style="display: none;">
    <div class="modal-reportar-contenido">
        <div class="modal-reportar-header">
            <h2>Reportar Novedad</h2>
            <button class="btn-cerrar-modal" onclick="cerrarModalReportarNovedad()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div class="modal-reportar-body">
            <form id="form-reportar-novedad" onsubmit="event.preventDefault(); enviarNovedad();">
                <!-- Input oculto con número de pedido -->
                <input type="hidden" id="numero-pedido-novedad" value="{{ $pedido['numero_pedido'] }}">

                <!-- Novedades Anteriores -->
                <div class="novedades-previas">
                    <label>Novedades Anteriores</label>
                    <div class="novedades-previas-contenido">
                        @if($pedido['novedades'])
                            <pre>{{ $pedido['novedades'] }}</pre>
                        @else
                            <p class="sin-novedades">Sin novedades registradas</p>
                        @endif
                    </div>
                </div>

                <!-- Área de texto para nueva novedad -->
                <div class="formulario-novedad">
                    <label for="textarea-novedad">Nueva Novedad</label>
                    <textarea 
                        id="textarea-novedad" 
                        placeholder="Describe la novedad..." 
                        minlength="5"
                        maxlength="500"
                        rows="6"
                        required
                    ></textarea>
                    <small>Mínimo 5 caracteres, máximo 500</small>
                </div>

                <!-- Botones -->
                <div class="modal-reportar-footer">
                    <button type="button" class="btn-cancelar" onclick="cerrarModalReportarNovedad()">
                        <span class="material-symbols-rounded">cancel</span>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-enviar">
                        <span class="material-symbols-rounded">send</span>
                        Enviar Novedad
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* ===== ESTILOS MODAL REPORTAR NOVEDAD ===== */
    .modal-reportar-novedad {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 10001;
    }

    .modal-reportar-contenido {
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-reportar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid #eee;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0;
    }

    .modal-reportar-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .btn-cerrar-modal {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .btn-cerrar-modal:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }

    .modal-reportar-body {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
    }

    .novedades-previas {
        margin-bottom: 2rem;
    }

    .novedades-previas label {
        display: block;
        font-weight: 700;
        margin-bottom: 0.75rem;
        color: #333;
        font-size: 0.95rem;
    }

    .novedades-previas-contenido {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 1rem;
        max-height: 200px;
        overflow-y: auto;
        border-left: 4px solid #667eea;
    }

    .novedades-previas-contenido pre {
        margin: 0;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        color: #555;
        white-space: pre-wrap;
        word-wrap: break-word;
        line-height: 1.6;
    }

    .novedades-previas-contenido .sin-novedades {
        margin: 0;
        color: #999;
        font-style: italic;
    }

    .formulario-novedad {
        margin-bottom: 1.5rem;
    }

    .formulario-novedad label {
        display: block;
        font-weight: 700;
        margin-bottom: 0.75rem;
        color: #333;
        font-size: 0.95rem;
    }

    .formulario-novedad textarea {
        width: 100%;
        padding: 1rem;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 0.95rem;
        resize: vertical;
        transition: all 0.3s ease;
    }

    .formulario-novedad textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .formulario-novedad small {
        display: block;
        margin-top: 0.5rem;
        color: #999;
        font-size: 0.8rem;
    }

    .modal-reportar-footer {
        display: flex;
        gap: 1rem;
        padding: 1.5rem;
        border-top: 1px solid #eee;
        background: #f9f9f9;
        border-radius: 0 0 12px 12px;
    }

    .btn-cancelar,
    .btn-enviar {
        flex: 1;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-cancelar {
        background: #f0f0f0;
        color: #333;
        border: 1px solid #ddd;
    }

    .btn-cancelar:hover {
        background: #e0e0e0;
        transform: translateY(-2px);
    }

    .btn-enviar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-enviar:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-enviar:active {
        transform: translateY(0);
    }

    .btn-accion.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .btn-accion.warning:hover {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modal-reportar-contenido {
            width: 95%;
            max-height: 85vh;
        }

        .modal-reportar-header {
            padding: 1rem;
        }

        .modal-reportar-header h2 {
            font-size: 1.25rem;
        }

        .modal-reportar-body {
            padding: 1rem;
        }

        .modal-reportar-footer {
            flex-direction: column;
        }

        .btn-cancelar,
        .btn-enviar {
            width: 100%;
        }
    }
</style>
@endsection

<!-- ===== MODAL RESPUESTA NOVEDAD ===== -->
<div id="modal-respuesta-novedad" class="modal-respuesta-novedad" style="display: none;">
    <div class="modal-respuesta-contenido">
        <div class="respuesta-icon-container">
            <span id="respuesta-icono" class="material-symbols-rounded respuesta-icono">info</span>
        </div>
        <h2 id="respuesta-titulo">Título</h2>
        <p id="respuesta-mensaje">Mensaje</p>
        <button class="btn-respuesta-ok" onclick="cerrarModalRespuesta()">
            <span class="material-symbols-rounded">check</span>
            Aceptar
        </button>
    </div>
</div>

<style>
    /* ===== ESTILOS MODAL RESPUESTA ===== */
    .modal-respuesta-novedad {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 10002;
    }

    .modal-respuesta-contenido {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        max-width: 400px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        animation: scaleIn 0.3s ease;
    }

    @keyframes scaleIn {
        from {
            transform: scale(0.9);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .respuesta-icon-container {
        margin-bottom: 1.5rem;
    }

    .respuesta-icono {
        font-size: 3rem;
        display: block;
    }

    .respuesta-icono.spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    .modal-respuesta-contenido h2 {
        margin: 1rem 0 0.5rem 0;
        font-size: 1.5rem;
        color: #333;
    }

    .modal-respuesta-contenido p {
        margin: 0 0 2rem 0;
        color: #666;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .btn-respuesta-ok {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 2rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-respuesta-ok:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
    }

    .btn-respuesta-ok:active {
        transform: translateY(0);
    }

    @media (max-width: 768px) {
        .modal-respuesta-contenido {
            max-width: 90%;
            padding: 1.5rem;
        }

        .respuesta-icono {
            font-size: 2.5rem;
        }

        .modal-respuesta-contenido h2 {
            font-size: 1.25rem;
        }
    }
</style>

