<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mundo Industrial</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen relative overflow-hidden font-sans">


    <!-- Header -->
    <header class="absolute top-6 left-6 right-6 flex justify-end items-center z-50">
        <div class="flex gap-4 sm:gap-6">
            <a href="{{ route('login') }}" class="px-6 py-2.5 border-2 border-white/80 rounded-lg hover:bg-white hover:text-gray-900 transition-all duration-300 transform hover:scale-105 shadow-lg backdrop-blur-sm bg-black/20 font-medium">
                Iniciar Sesión
            </a>
            <a href="{{ route('register') }}" class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:from-orange-600 hover:to-orange-700 transition-all duration-300 transform hover:scale-105 shadow-lg font-medium">
                Registrarse
            </a>
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
        <div class="relative z-10 flex flex-col items-center justify-center text-center px-6 sm:px-16 py-16 md:w-1/2">
           
            <!-- Logo separado con más margen -->
            <div class="mb-16 sm:mb-20 animate-fadeIn">
                <img src="{{ asset('logo.png') }}" alt="Mundo Industrial" class="h-24 sm:h-32 w-auto drop-shadow-2xl">
            </div>


            <!-- Contenido de texto -->
            <div class="space-y-6 animate-slideIn">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-white drop-shadow-2xl leading-tight">
                    Bienvenido al <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-500">
                        Sistema de Gestión
                    </span>
                    <br>
                    <span class="text-3xl sm:text-4xl md:text-5xl">Mundo Industrial</span>
                </h1>


                <div class="h-1 w-24 bg-gradient-to-r from-orange-400 to-orange-600 mx-auto rounded-full animate-pulse"></div>


                <p class="text-base sm:text-lg md:text-xl text-gray-200 drop-shadow-lg max-w-xl mx-auto leading-relaxed px-4">
                    Somos líderes en distribución de elementos de protección personal y proveedores directos de marcas certificadas en seguridad industrial.
                </p>


               
            </div>
        </div>


    </div>


    <!-- Animaciones mejoradas -->
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fadeIn {
            animation: fadeIn 1s ease-out forwards;
        }


        @keyframes slideIn {
            from {
                transform: translateY(40px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .animate-slideIn {
            animation: slideIn 1s ease-out forwards;
            animation-delay: 0.3s;
            opacity: 0;
        }


        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fadeInUp {
            animation: fadeInUp 0.8s ease-out forwards;
            opacity: 0;
        }


        /* Efecto de brillo sutil en el fondo */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
    </style>


</body>
</html>



