<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mundo Industrial</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('mundo_icon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('mundo_icon.png') }}">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-white min-h-screen relative overflow-hidden font-sans">

    <!-- Header -->
    <header class="absolute top-6 left-6 right-6 flex justify-end items-center z-50">
        <div class="flex gap-4 sm:gap-6">
            <a href="{{ route('login') }}" class="px-6 py-2.5 border-2 border-white/80 rounded-lg hover:bg-white hover:text-gray-900 transition-all duration-300 transform hover:scale-105 shadow-lg backdrop-blur-sm bg-black/20 font-medium">
                Iniciar Sesión
            </a>
            {{-- Botón de registro desactivado - Solo el administrador puede registrar --}}
            {{-- <a href="{{ route('register') }}" class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:from-orange-600 hover:to-orange-700 transition-all duration-300 transform hover:scale-105 shadow-lg font-medium">
                Registrarse
            </a> --}}
        </div>
    </header>

    <!-- Main Content -->
    <div class="relative flex flex-col md:flex-row h-screen">

        <!-- Imagen de fondo -->
        <div class="absolute inset-0 md:relative md:w-1/2">
            <img src="{{ asset('images/slider1.png') }}" class="w-full h-full object-cover brightness-90">
            <!-- Overlay degradado mejorado -->
            <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-transparent md:bg-gradient-to-r md:from-black/70 md:via-black/30 md:to-transparent"></div>
        </div>

        <!-- Contenido central -->
        <div class="relative z-10 flex flex-col items-center justify-center text-center px-6 sm:px-16 py-16 md:w-1/2 mt-24 sm:mt-32 md:mt-40">
            
            <!-- Logo separado con más margen -->
            <div class="mb-8 sm:mb-10 md:mb-12 animate-fadeIn">
                <img src="{{ asset('logo.png') }}" alt="Mundo Industrial" class="h-16 sm:h-20 md:h-24 lg:h-28 w-auto drop-shadow-2xl">
            </div>

            <!-- Contenido de texto -->
            <div class="space-y-4 sm:space-y-5 animate-slideIn">
                <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold text-white drop-shadow-2xl leading-tight">
                    Bienvenido al <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-500">
                        Sistema de Gestión
                    </span>
                    <br>
                    <span class="text-xl sm:text-2xl md:text-3xl lg:text-4xl">Mundo Industrial</span>
                </h1>

                <div class="h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 mx-auto rounded-full animate-pulse"></div>

                <p class="text-sm sm:text-base md:text-lg text-gray-200 drop-shadow-lg max-w-lg mx-auto leading-relaxed px-4">
                    Somos líderes en distribución de elementos de protección personal y proveedores directos de marcas certificadas en seguridad industrial.
                </p>

                
            </div>
        </div>

    </div>


</body>
</html>