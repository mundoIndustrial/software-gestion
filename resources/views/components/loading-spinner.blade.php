<!-- Loading Spinner Profesional -->
<div class="loading-spinner-overlay" id="loadingSpinner">
    <div class="loading-spinner-container">
        <!-- Spinner Animado -->
        <div class="spinner-wrapper">
            <svg class="spinner-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <!-- Círculo de fondo -->
                <circle cx="50" cy="50" r="45" class="spinner-bg" />
                
                <!-- Círculo animado principal -->
                <circle cx="50" cy="50" r="45" class="spinner-main" />
                
                <!-- Puntos decorativos -->
                <circle cx="50" cy="10" r="4" class="spinner-dot" opacity="1" />
                <circle cx="85" cy="50" r="4" class="spinner-dot" opacity="0.8" />
                <circle cx="50" cy="90" r="4" class="spinner-dot" opacity="0.6" />
                <circle cx="15" cy="50" r="4" class="spinner-dot" opacity="0.4" />
            </svg>
        </div>

        <!-- Texto -->
        <div class="loading-text">
            <h3 class="loading-title">Espere, es posible</h3>
            <p class="loading-subtitle">Procesando su solicitud...</p>
        </div>

        <!-- Indicador de progreso -->
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
    </div>
</div>

<style>
    /* Variables de color */
    :root {
        --primary-blue: #3498db;
        --dark-blue: #2c3e50;
        --light-blue: #ecf0f1;
        --white: #ffffff;
        --overlay-bg: rgba(44, 62, 80, 0.95);
    }

    /* Overlay */
    .loading-spinner-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--overlay-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 1;
        visibility: visible;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        backdrop-filter: blur(2px);
    }

    .loading-spinner-overlay.hidden {
        opacity: 0;
        visibility: hidden;
    }

    /* Contenedor principal */
    .loading-spinner-container {
        text-align: center;
        animation: slideUp 0.5s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Wrapper del spinner */
    .spinner-wrapper {
        width: 120px;
        height: 120px;
        margin: 0 auto 30px;
        position: relative;
    }

    /* SVG del spinner */
    .spinner-svg {
        width: 100%;
        height: 100%;
        filter: drop-shadow(0 4px 15px rgba(52, 152, 219, 0.3));
    }

    /* Círculo de fondo */
    .spinner-bg {
        fill: none;
        stroke: rgba(255, 255, 255, 0.1);
        stroke-width: 3;
    }

    /* Círculo animado principal */
    .spinner-main {
        fill: none;
        stroke: url(#spinnerGradient);
        stroke-width: 3;
        stroke-linecap: round;
        stroke-dasharray: 141;
        stroke-dashoffset: 0;
        animation: spin 2s linear infinite;
        transform-origin: 50% 50%;
    }

    @keyframes spin {
        0% {
            stroke-dashoffset: 0;
            transform: rotate(0deg);
        }
        100% {
            stroke-dashoffset: -141;
            transform: rotate(360deg);
        }
    }

    /* Puntos decorativos */
    .spinner-dot {
        fill: var(--primary-blue);
        animation: pulse 1.5s ease-in-out infinite;
    }

    .spinner-dot:nth-child(2) {
        animation-delay: 0.1s;
    }

    .spinner-dot:nth-child(3) {
        animation-delay: 0.2s;
    }

    .spinner-dot:nth-child(4) {
        animation-delay: 0.3s;
    }

    @keyframes pulse {
        0%, 100% {
            r: 4;
            opacity: 0.4;
        }
        50% {
            r: 6;
            opacity: 1;
        }
    }

    /* Texto */
    .loading-text {
        margin-bottom: 30px;
    }

    .loading-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--white);
        margin: 0 0 8px 0;
        letter-spacing: 0.5px;
        animation: fadeInDown 0.6s ease-out 0.2s both;
    }

    .loading-subtitle {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.7);
        margin: 0;
        font-weight: 400;
        animation: fadeInUp 0.6s ease-out 0.3s both;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Barra de progreso */
    .progress-bar {
        width: 200px;
        height: 4px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 2px;
        margin: 0 auto;
        overflow: hidden;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-blue), #2980b9, var(--primary-blue));
        background-size: 200% 100%;
        border-radius: 2px;
        animation: progress 2s ease-in-out infinite;
        box-shadow: 0 0 10px rgba(52, 152, 219, 0.5);
    }

    @keyframes progress {
        0% {
            width: 0%;
            background-position: 0% 0%;
        }
        50% {
            width: 100%;
            background-position: 100% 0%;
        }
        100% {
            width: 0%;
            background-position: 0% 0%;
        }
    }

    /* Tema oscuro */
    @media (prefers-color-scheme: dark) {
        .loading-spinner-overlay {
            --overlay-bg: rgba(20, 30, 40, 0.98);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .spinner-wrapper {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
        }

        .loading-title {
            font-size: 24px;
        }

        .loading-subtitle {
            font-size: 13px;
        }

        .progress-bar {
            width: 150px;
        }
    }

    /* Gradiente SVG */
    svg defs {
        display: none;
    }
</style>

<!-- Gradiente SVG (agregado dinámicamente) -->
<svg style="display: none;">
    <defs>
        <linearGradient id="spinnerGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color: #3498db; stop-opacity: 1" />
            <stop offset="50%" style="stop-color: #2980b9; stop-opacity: 1" />
            <stop offset="100%" style="stop-color: #3498db; stop-opacity: 0.3" />
        </linearGradient>
    </defs>
</svg>

<script>
    // Funciones para controlar el spinner
    window.showLoadingSpinner = function(message = 'Espere, es posible') {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            const title = spinner.querySelector('.loading-title');
            if (title) {
                title.textContent = message;
            }
            spinner.classList.remove('hidden');
        }
    };

    window.hideLoadingSpinner = function() {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.classList.add('hidden');
        }
    };

    window.setLoadingMessage = function(message) {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            const title = spinner.querySelector('.loading-title');
            if (title) {
                title.textContent = message;
            }
        }
    };
</script>

<!-- Auto Loading Spinner Script -->
<script src="{{ asset('js/configuraciones/auto-loading-spinner.js') }}"></script>
