<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="google" content="notranslate">
        <meta http-equiv="Content-Language" content="es">

        <title>{{ config('app.name', 'Mundo Industrial') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
        <link rel="shortcut icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
        <link rel="apple-touch-icon" href="{{ asset('mundo_icon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Overlay rápido para login/guest (evita "pantalla blanca" percibida) */
            #guest-loading-overlay {
                position: fixed;
                inset: 0;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 999999;
                transition: opacity 0.2s ease;
                opacity: 1;
                flex-direction: column;
                gap: 18px;
            }

            #guest-loading-overlay.hidden {
                opacity: 0;
                pointer-events: none;
            }

            @keyframes guestSpin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>

        <script>
            (function () {
                function ensureOverlay() {
                    return document.getElementById('guest-loading-overlay');
                }

                window.showGuestLoadingOverlay = function (message) {
                    const overlay = ensureOverlay();
                    if (!overlay) return;
                    const titleEl = overlay.querySelector('[data-guest-loading-title]');
                    if (titleEl && typeof message === 'string' && message.trim()) titleEl.textContent = message.trim();
                    overlay.classList.remove('hidden');
                    overlay.style.display = 'flex';
                    overlay.style.opacity = '1';
                    overlay.style.pointerEvents = 'auto';
                };

                window.hideGuestLoadingOverlay = function () {
                    const overlay = ensureOverlay();
                    if (!overlay) return;
                    overlay.style.pointerEvents = 'none';
                    overlay.classList.add('hidden');
                    overlay.style.opacity = '0';
                    window.setTimeout(function () {
                        overlay.style.display = 'none';
                    }, 220);
                };
            })();
        </script>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div id="guest-loading-overlay" aria-hidden="true">
            <div style="position: relative; width: 72px; height: 72px;">
                <svg width="72" height="72" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="animation: guestSpin 1.6s linear infinite;">
                    <circle cx="40" cy="40" r="35" stroke="url(#guestGradient)" stroke-width="4" stroke-linecap="round"/>
                    <defs>
                        <linearGradient id="guestGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3498db;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#2ecc71;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div style="text-align:center;">
                <p data-guest-loading-title style="margin: 0 0 6px 0; color: #2c3e50; font-size: 28px; font-weight: 700; letter-spacing: -0.4px;">Cargando</p>
                <p style="margin: 0; color: #7f8c8d; font-size: 15px; font-weight: 500;">Un momento por favor…</p>
            </div>
        </div>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-800">

            <!-- Logo de la empresa -->
            <div class="mb-6">
                <a href="/">
                    <img src="{{ asset('logo.png') }}" alt="Mundo Industrial" class="h-24 w-auto" width="200" height="100">
                </a>
            </div>

            <!-- Contenedor del formulario -->
            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>


