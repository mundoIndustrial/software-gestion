<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Ups! Algo salió mal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-animation {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .details-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .details-container.open {
            max-height: 1000px;
        }
        
        .error-code {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.4;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-red-50 to-orange-50 min-h-screen flex items-center justify-center p-4" data-status="{{ $statusCode ?? 500 }}" data-error-code="{{ $errorCode ?? '' }}">
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-500 to-orange-500 p-6 text-white text-center">
            <div class="error-animation inline-block text-6xl mb-4">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="text-3xl font-bold mb-2">¡Ups! Algo salió mal</h1>
            <p class="text-red-100">No te preocupes, estamos trabajando para solucionarlo</p>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- User-friendly message -->
            <div class="mb-6">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-info-circle text-red-500"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">¿Qué pasó?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            {{ $friendlyMessage ?? 'Ocurrió un problema inesperado en el sistema. Por favor, intenta nuevamente en unos momentos.' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Error code display -->
            @if(isset($errorCode))
            <div class="mb-6 p-4 bg-gray-50 rounded-lg border-l-4 border-red-400">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-hashtag text-gray-500"></i>
                    <span class="text-sm font-medium text-gray-700">Código de error:</span>
                    <span class="text-sm font-mono text-red-600 font-bold">{{ $errorCode }}</span>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3 mb-6">
                <a href="{{ url('/') }}" 
                   class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center space-x-2">
                    <i class="fas fa-home"></i>
                    <span>Ir al inicio</span>
                </a>
            </div>

            <!-- Technical details toggle -->
            @if(isset($technicalDetails) && !empty($technicalDetails))
            <div class="border-t pt-6">
                <button id="toggleDetails" 
                        class="w-full text-left flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-code text-gray-500"></i>
                        <span class="font-medium text-gray-700">Ver detalles técnicos</span>
                    </div>
                    <i id="toggleIcon" class="fas fa-chevron-down text-gray-500 transition-transform duration-200"></i>
                </button>
                
                <div id="technicalDetails" class="details-container mt-3">
                    <div class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto">
                        <div class="error-code">
                            <div class="text-red-400 font-bold mb-2">
                                <i class="fas fa-bug"></i> Detalles del Error:
                            </div>
                            <div class="whitespace-pre-wrap">{{ $technicalDetails }}</div>
                            
                            @if(isset($file) && isset($line))
                            <div class="mt-4 pt-4 border-t border-gray-700">
                                <div class="text-yellow-400 font-bold mb-1">
                                    <i class="fas fa-file-code"></i> Ubicación:
                                </div>
                                <div class="text-blue-300">
                                    Archivo: {{ $file }}<br>
                                    Línea: {{ $line }}
                                </div>
                            </div>
                            @endif
                            
                            @if(isset($trace) && !empty($trace))
                            <div class="mt-4 pt-4 border-t border-gray-700">
                                <div class="text-purple-400 font-bold mb-2">
                                    <i class="fas fa-list"></i> Stack Trace:
                                </div>
                                <div class="text-gray-300 text-xs">
                                    {{ $trace }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 text-center text-sm text-gray-500">
            <p>Si el problema persiste, contacta al administrador del sistema</p>
            <p class="mt-1">{{ now()->format('d/m/Y h:i:s A') }}</p>
        </div>
    </div>

    <script>
        function handleBackButton() {
            // Detectar error 403 de múltiples formas
            const statusAttr = document.body.getAttribute('data-status');
            const statusCode = statusAttr ? parseInt(statusAttr, 10) : null;
            
            console.log('[DEBUG-BACK-BUTTON] Status Code:', statusCode);
            console.log('[DEBUG-BACK-BUTTON] Document Title:', document.title);
            console.log('[DEBUG-BACK-BUTTON] Body Data-Status:', statusAttr);
            
            const is403Error = 
                statusCode === 403 ||  // Principal: por data-status
                document.title.includes('403') ||
                document.querySelector('h1')?.textContent.includes('403') ||
                document.body.textContent.includes('No tienes permisos para acceder');
            
            console.log('[DEBUG-BACK-BUTTON] Es 403?:', is403Error);
            
            if (is403Error) {
                // Para error 403, siempre ir a login
                console.log('[DEBUG-BACK-BUTTON] Redirigiendo a /login');
                window.location.href = '/login';
            } else {
                // Para otros errores, retroceder en historial
                console.log('[DEBUG-BACK-BUTTON] Retrocediendo en historial');
                window.history.back();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('[DEBUG-ERROR-PAGE] Página de error cargada');
            console.log('[DEBUG-ERROR-PAGE] Status Code:', document.body.getAttribute('data-status'));
            console.log('[DEBUG-ERROR-PAGE] Error Code:', document.body.getAttribute('data-error-code'));
            
            const toggleButton = document.getElementById('toggleDetails');
            const detailsContainer = document.getElementById('technicalDetails');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (toggleButton && detailsContainer) {
                toggleButton.addEventListener('click', function() {
                    const isOpen = detailsContainer.classList.contains('open');
                    
                    if (isOpen) {
                        detailsContainer.classList.remove('open');
                        toggleIcon.style.transform = 'rotate(0deg)';
                        toggleButton.querySelector('span').textContent = 'Ver detalles técnicos';
                    } else {
                        detailsContainer.classList.add('open');
                        toggleIcon.style.transform = 'rotate(180deg)';
                        toggleButton.querySelector('span').textContent = 'Ocultar detalles técnicos';
                    }
                });
            }
        });
    </script>
</body>
</html>
