<x-modal name="order-detail" :show="false" maxWidth="4xl">
    <div class="order-detail-modal-container">
        <!-- Header con Logo y Navegación -->
        <div class="ficha-header">
            <div class="header-left">
                <img src="{{ asset('images/logo.png') }}" alt="Mundo Industrial Logo" class="ficha-logo">
            </div>
            <div class="header-center">
                <h1 class="ficha-title">FICHA TÉCNICA DE ORDEN</h1>
                <div id="order-pedido" class="pedido-number-header"></div>
            </div>
            <div class="header-right">
                <div class="navigation-arrows">
                    <button id="prev-arrow" class="arrow-btn" title="Orden anterior" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <button id="next-arrow" class="arrow-btn" title="Siguiente orden" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="ficha-content">
            <!-- Sección 1: Información General -->
            <div class="ficha-section">
                <h2 class="section-title">📋 INFORMACIÓN GENERAL</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Fecha de Creación:</label>
                        <div id="order-date" class="info-value date-display"></div>
                    </div>
                    <div class="info-item">
                        <label>Cliente:</label>
                        <div id="cliente-value" class="info-value"></div>
                    </div>
                    <div class="info-item">
                        <label>Asesora:</label>
                        <div id="asesora-value" class="info-value"></div>
                    </div>
                    <div class="info-item">
                        <label>Forma de Pago:</label>
                        <div id="forma-pago-value" class="info-value"></div>
                    </div>
                </div>
            </div>

            <!-- Sección 2: Descripción -->
            <div class="ficha-section">
                <h2 class="section-title">📝 DESCRIPCIÓN</h2>
                <div id="descripcion-text" class="descripcion-box"></div>
            </div>

            <!-- Sección 3: Responsables -->
            <div class="ficha-section">
                <h2 class="section-title">👤 RESPONSABLES</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Encargado de Orden:</label>
                        <div id="encargado-value" class="info-value"></div>
                    </div>
                    <div class="info-item">
                        <label>Prendas Entregadas:</label>
                        <div id="prendas-entregadas-value" class="info-value"></div>
                    </div>
                </div>
            </div>

            <!-- Sección 3.5: Prendas Detalladas -->
            <div class="ficha-section" id="prendas-section" style="display: none;">
                <h2 class="section-title">👕 PRENDAS DETALLADAS</h2>
                <div id="prendas-detalladas" class="prendas-detalladas-container">
                    <!-- Las prendas se cargarán aquí dinámicamente -->
                </div>
            </div>

            <!-- Sección 3.6: Imágenes -->
            <div class="ficha-section" id="imagenes-section" style="display: none;">
                <h2 class="section-title">🖼️ IMÁGENES</h2>
                <div class="imagenes-grid" id="imagenes-grid">
                    <!-- Las imágenes se cargarán aquí dinámicamente -->
                </div>
            </div>

            <!-- Sección 4: Acciones -->
            <div class="ficha-section">
                <h2 class="section-title">⚙️ ACCIONES</h2>
                <div class="actions-container">
                    <a href="#" id="ver-entregas" class="action-btn action-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        Ver Entregas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Zoom para Imágenes -->
    <div id="image-zoom-modal" class="image-zoom-modal" style="display: none;">
        <div class="image-zoom-overlay" id="image-zoom-overlay"></div>
        <div class="image-zoom-container">
            <button class="zoom-close-btn" id="zoom-close-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <button class="zoom-nav-btn zoom-prev-btn" id="zoom-prev-btn" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <img id="zoom-image" class="zoom-image" src="" alt="Imagen ampliada" draggable="true">
            <button class="zoom-nav-btn zoom-next-btn" id="zoom-next-btn" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
            <div class="zoom-counter" id="zoom-counter"></div>
        </div>
    </div>

    <style>
        .order-detail-modal-container {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: auto;
            max-height: 85vh;
            width: 100%;
            overflow-y: auto;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 0;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1000;
        }

        /* Header */
        .ficha-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 12px 12px 0 0;
            gap: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            flex-wrap: wrap;
        }

        .header-left {
            flex: 0 0 auto;
        }

        .ficha-logo {
            height: 45px;
            width: auto;
            object-fit: contain;
        }

        .header-center {
            flex: 1;
            text-align: center;
            min-width: 200px;
        }

        .ficha-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .pedido-number-header {
            font-size: 12px;
            color: #ecf0f1;
            margin-top: 2px;
            font-weight: 600;
        }

        .header-right {
            flex: 0 0 auto;
        }

        .navigation-arrows {
            display: flex;
            gap: 8px;
        }

        /* Contenido */
        .ficha-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        /* Secciones */
        .ficha-section {
            background: white;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #3498db;
        }

        .ficha-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            margin: 0 0 12px 0;
            font-size: 14px;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Grid de Información */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            font-size: 11px;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 13px;
            color: #2c3e50;
            font-weight: 500;
            padding: 6px 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid #3498db;
            min-height: 28px;
            display: flex;
            align-items: center;
        }

        .date-display {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        /* Descripción */
        .descripcion-box {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 12px;
            border-left: 3px solid #3498db;
            font-size: 13px;
            color: #2c3e50;
            line-height: 1.5;
            white-space: pre-wrap;
            word-wrap: break-word;
            min-height: 60px;
            max-height: 120px;
            overflow-y: auto;
        }

        /* Botones de Acción */
        .actions-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .action-btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .action-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(52, 152, 219, 0.4);
        }

        /* Botones de Navegación */
        .arrow-btn {
            background: white;
            border: 2px solid #3498db;
            color: #3498db;
            cursor: pointer;
            padding: 6px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            width: 36px;
            height: 36px;
        }

        .arrow-btn:hover {
            transform: scale(1.1);
            background-color: #3498db;
            color: white;
        }

        .arrow-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Galería de Imágenes */
        .imagenes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(48px, 1fr));
            gap: 8px;
        }

        .imagen-thumbnail {
            position: relative;
            cursor: pointer;
            border-radius: 3px;
            overflow: hidden;
            background: #f0f0f0;
            aspect-ratio: 1;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .imagen-thumbnail:hover {
            transform: scale(1.2);
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.4);
        }

        .imagen-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .imagen-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            display: none;
        }

        .imagen-thumbnail:hover .imagen-overlay {
            opacity: 1;
        }

        .imagen-overlay svg {
            width: 32px;
            height: 32px;
            color: white;
        }

        /* Modal de Zoom */
        .image-zoom-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-zoom-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            cursor: pointer;
        }

        .image-zoom-container {
            position: relative;
            z-index: 2001;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 70%;
            height: 60%;
            max-width: 450px;
            max-height: 350px;
        }

        .zoom-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            cursor: grab;
            user-select: none;
            border-radius: 6px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .zoom-image:active {
            cursor: grabbing;
        }

        .zoom-close-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 2002;
        }

        .zoom-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .zoom-close-btn svg {
            width: 20px;
            height: 20px;
        }

        .zoom-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 2002;
        }

        .zoom-prev-btn {
            left: 12px;
        }

        .zoom-next-btn {
            right: 12px;
        }

        .zoom-nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .zoom-nav-btn svg {
            width: 24px;
            height: 24px;
        }

        .zoom-counter {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            z-index: 2002;
        }

        /* Prendas Detalladas */
        .prendas-detalladas-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .prenda-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 16px;
            transition: all 0.3s ease;
        }

        .prenda-card:hover {
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
            border-color: #3498db;
        }

        .prenda-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid #3498db;
        }

        .prenda-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .prenda-card-cantidad {
            background: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .prenda-card-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .prenda-detail-item {
            display: flex;
            flex-direction: column;
        }

        .prenda-detail-label {
            font-size: 11px;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .prenda-detail-value {
            font-size: 13px;
            color: #2c3e50;
            font-weight: 500;
            padding: 6px 10px;
            background: white;
            border-radius: 4px;
            border-left: 3px solid #3498db;
        }

        .prenda-tallas-grid {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e0e0e0;
        }

        .prenda-tallas-title {
            font-size: 12px;
            font-weight: 600;
            color: #3498db;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .prenda-tallas-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 8px;
        }

        .talla-badge {
            background: white;
            border: 1px solid #3498db;
            border-radius: 4px;
            padding: 6px 8px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #3498db;
        }

        /* Descripción Completa */
        .prenda-descripcion-full {
            grid-column: 1 / -1;
            margin-bottom: 12px;
        }

        .prenda-descripcion-text {
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.5;
        }

        /* Imágenes de Referencia */
        .prenda-imagenes-grid {
            grid-column: 1 / -1;
            margin-bottom: 12px;
            padding: 12px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }

        .prenda-imagenes-title {
            font-size: 12px;
            font-weight: 600;
            color: #3498db;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .prenda-imagenes-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .prenda-imagen-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .prenda-imagen-label {
            font-size: 11px;
            font-weight: 600;
            color: #7f8c8d;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .prenda-imagen {
            width: 100%;
            max-width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #3498db;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .prenda-imagen:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ficha-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
                padding: 12px 16px;
            }

            .header-left, .header-right {
                flex: 1;
            }

            .ficha-content {
                padding: 14px;
            }

            .ficha-section {
                padding: 12px;
                margin-bottom: 10px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .ficha-title {
                font-size: 16px;
            }

            .section-title {
                font-size: 13px;
                margin-bottom: 10px;
            }

            .info-value {
                font-size: 12px;
                padding: 5px 8px;
            }
        }
    </style>
</x-modal>