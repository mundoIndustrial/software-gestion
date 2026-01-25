<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    <meta name="google" content="notranslate">
    <meta http-equiv="Content-Language" content="es">
    <meta name="description" content="Plataforma integral de gestión de producción textil con seguimiento en tiempo real y análisis de datos.">
    <meta name="theme-color" content="#0066cc">
    <title>@yield('title', config('app.name', 'Mundo Industrial'))</title>

    @yield('meta')

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('mundo_icon.ico') }}">

    <!-- Script crítico para prevenir flash de tema - DEBE estar ANTES de CSS -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark-theme');
                document.documentElement.setAttribute('data-theme', 'dark');
                // Marcar para que el body también aplique la clase cuando esté listo
                document.documentElement.setAttribute('data-pending-theme', 'dark');
            }
        })();
    </script>

    <!-- Estilo crítico inline -->
    <style>
        html[data-theme="dark"] body {
            background-color: #0f172a !important;
            color: #F1F5F9 !important;
        }
    </style>

    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Global (crítico) -->
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    
    <!-- CSS no-crítico (diferido) -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'" crossorigin="anonymous" referrerpolicy="no-referrer">
    <noscript>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    </noscript>

    <!-- Vite (contiene app.css y app.js críticos) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Laravel Echo & Pusher JS cargado vía Vite en app.js (bootstrap.js) -->
    
    <!-- SweetAlert2 JS (diferido) -->
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Toast Notifications Global (diferido) -->
    <script defer src="{{ asset('js/configuraciones/toast-notifications.js') }}"></script>

    <!-- Page-specific styles -->
    @stack('styles')
    
    <style>
        /* Loading overlay global */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.3s ease;
            flex-direction: column;
            gap: 30px;
            pointer-events: auto;
        }
        
        #loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="
    {{ isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : '' }}
    {{ request()->routeIs('cotizaciones-prenda.create') ? 'cotizaciones-prenda-create' : '' }}
" 
      data-user-role="{{ auth()->user()->role?->name ?? 'guest' }}"
      data-module="@yield('module', 'default')">

    <!-- Sincronizar tema con localStorage -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            const body = document.body;
            
            // Aplicar tema al body
            if (theme === 'dark') {
                body.classList.add('dark-theme');
                html.classList.add('dark-theme');
                html.setAttribute('data-theme', 'dark');
            } else {
                body.classList.remove('dark-theme');
                html.classList.remove('dark-theme');
                html.setAttribute('data-theme', 'light');
            }
            
            // Limpiar atributo de tema pendiente
            html.removeAttribute('data-pending-theme');
        })();
    </script>

    @yield('body')

    <!-- Core JS - Crítico para funcionalidad (sin defer) -->
    <script src="{{ asset('js/configuraciones/sidebar.js') }}"></script>
    
    <!-- Sistema de refresh automático de token CSRF (Previene error 419) -->
    <script src="{{ asset('js/configuraciones/csrf-refresh.js') }}"></script>
    
    <!-- Non-critical JS (diferido) -->
    <script defer src="{{ asset('js/configuraciones/sidebar-notifications.js') }}"></script>
    <script defer src="{{ asset('js/configuraciones/top-nav.js') }}"></script>

    <!-- Page-specific scripts -->
    @stack('scripts')
    
    <!-- Modals -->
    @stack('modals')
    
    <!-- 🔧 PAYLOAD NORMALIZER - SOLUCIÓN DEFINITIVA SIN CICLOS -->
    <script>
    console.debug('[PayloadNormalizer] Inicializando versión con clonación segura...');
    
    window.PayloadNormalizer = {
      /**
       * FUNCIONA COMO:
       * 1. Clonar manualmente CADA propiedad (evita ciclos estructuredClone)
       * 2. Filtrar Files/Blobs durante clonación
       * 3. Mantener exactamente tallas/procesos/telas intactos
       * 4. NO mutate original
       */
      normalizePedido: function(pedido) {
        console.debug('[normalizePedido] INICIO - Clonando estructura...');
        
        if (!pedido || typeof pedido !== 'object') {
          console.error('[normalizePedido] ❌ Pedido inválido');
          return { items: [] };
        }

        // Clonar objeto raíz SIN funciones ni proxies
        const clean = {
          cliente: pedido.cliente ?? '',
          asesora: pedido.asesora ?? '',
          forma_de_pago: pedido.forma_de_pago ?? 'Contado',
          items: []
        };

        if (!Array.isArray(pedido.items)) {
          return clean;
        }

        // Procesar cada item
        clean.items = pedido.items.map((item, idx) => {
          console.debug(`[normalizePedido] Procesando item ${idx}...`);
          
          const itemLimpio = {
            tipo: item.tipo ?? 'prenda_nueva',
            nombre_prenda: item.nombre_prenda ?? '',
            descripcion: item.descripcion ?? '',
            origen: item.origen ?? 'bodega',
            cantidad_talla: {},
            variaciones: {},
            telas: [],
            procesos: {},
            tallas: []  // Mantener array vacío si no viene
          };

          // ====== 1. CANTIDAD_TALLA (copiar exactamente) ======
          if (item.cantidad_talla && typeof item.cantidad_talla === 'object') {
            Object.entries(item.cantidad_talla).forEach(([genero, tallasObj]) => {
              if (typeof tallasObj === 'object' && tallasObj !== null) {
                itemLimpio.cantidad_talla[genero] = {};
                Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                  // CRÍTICO: No pasar por JSON.stringify aquí, copiar directamente
                  itemLimpio.cantidad_talla[genero][talla] = Number(cantidad) || 0;
                });
              }
            });
          }

          // ====== 2. VARIACIONES (mantener IDs y strings) ======
          if (item.variaciones && typeof item.variaciones === 'object') {
            itemLimpio.variaciones = {
              tipo_manga: item.variaciones.tipo_manga ?? '',
              obs_manga: item.variaciones.obs_manga ?? '',
              tipo_manga_id: item.variaciones.tipo_manga_id ?? null,
              tiene_bolsillos: Boolean(item.variaciones.tiene_bolsillos ?? false),
              obs_bolsillos: item.variaciones.obs_bolsillos ?? '',
              tipo_broche: item.variaciones.tipo_broche ?? '',
              obs_broche: item.variaciones.obs_broche ?? '',
              tipo_broche_boton_id: item.variaciones.tipo_broche_boton_id ?? null,
              tiene_reflectivo: Boolean(item.variaciones.tiene_reflectivo ?? false),
              obs_reflectivo: item.variaciones.obs_reflectivo ?? ''
            };
          }

          // ====== 3. TELAS (SOLO datos, NO imagenes) ======
          if (Array.isArray(item.telas)) {
            itemLimpio.telas = item.telas.map((tela, telaIdx) => {
              console.debug(`[normalizePedido] Item ${idx} Tela ${telaIdx}`);
              return {
                tela_id: Number(tela.tela_id) || null,
                color_id: Number(tela.color_id) || null,
                referencia: tela.referencia ?? ''
                // ❌ NUNCA imagenes aquí - van en FormData
              };
            });
          }

          // ====== 4. PROCESOS (CRÍTICO: estructura EXACTA) ======
          if (item.procesos && typeof item.procesos === 'object' && !Array.isArray(item.procesos)) {
            Object.entries(item.procesos).forEach(([procesoKey, proceso]) => {
              console.debug(`[normalizePedido] Item ${idx} Proceso[${procesoKey}]`);
              
              if (proceso && typeof proceso === 'object') {
                // Crear nuevo objeto proceso SIN referencias
                const procesoLimpio = {
                  tipo: proceso.tipo ?? '',
                  ubicaciones: Array.isArray(proceso.ubicaciones) 
                    ? [...proceso.ubicaciones]  // Copiar array
                    : [],
                  observaciones: proceso.observaciones ?? ''
                };

                // DATOS - Copiar tallas EXACTAMENTE como están
                if (proceso.datos && typeof proceso.datos === 'object') {
                  procesoLimpio.datos = {
                    tipo: proceso.datos.tipo ?? '',
                    ubicaciones: Array.isArray(proceso.datos.ubicaciones)
                      ? [...proceso.datos.ubicaciones]
                      : [],
                    observaciones: proceso.datos.observaciones ?? '',
                    tallas: {}  // Se llenará abajo
                  };

                  // TALLAS - Copiar recursivamente SIN JSON.stringify
                  if (proceso.datos.tallas && typeof proceso.datos.tallas === 'object') {
                    this._clonarObjeto(proceso.datos.tallas, procesoLimpio.datos.tallas);
                  }
                } else {
                  procesoLimpio.datos = {
                    tipo: '',
                    ubicaciones: [],
                    observaciones: '',
                    tallas: {}
                  };
                }

                itemLimpio.procesos[procesoKey] = procesoLimpio;
              }
            });
          }

          console.debug(`[normalizePedido] ✅ Item ${idx} completo`);
          return itemLimpio;
        });

        console.debug('[normalizePedido] ✅ COMPLETO - Items:', clean.items.length);
        return clean;
      },

      /**
       * Clonar objeto recursivamente sin JSON.stringify
       * Evita la trampa de "Over 9 levels deep"
       * @private
       */
      _clonarObjeto: function(origen, destino, profundidad = 0) {
        if (profundidad > 20) {
          console.warn('[_clonarObjeto] ⚠️ Profundidad máxima alcanzada');
          return;
        }

        Object.entries(origen).forEach(([key, valor]) => {
          if (valor === null || valor === undefined) {
            destino[key] = valor;
          } else if (typeof valor === 'string' || typeof valor === 'number' || typeof valor === 'boolean') {
            destino[key] = valor;
          } else if (Array.isArray(valor)) {
            destino[key] = valor.map(v => {
              if (typeof v === 'object' && v !== null && !(v instanceof File)) {
                const cloned = {};
                this._clonarObjeto(v, cloned, profundidad + 1);
                return cloned;
              }
              return v;
            });
          } else if (typeof valor === 'object') {
            destino[key] = {};
            this._clonarObjeto(valor, destino[key], profundidad + 1);
          }
        });
      },

      /**
       * Construye FormData con JSON limpio + archivos en rutas correctas
       */
      buildFormData: function(pedido, filesExtraidos) {
        console.debug('[buildFormData] INICIO');
        const fd = new FormData();
        
        // 1. JSON sin archivos
        const jsonString = JSON.stringify(pedido);
        fd.append('pedido', jsonString);
        console.debug(`[buildFormData] ✅ JSON: ${jsonString.length} bytes`);

        if (!filesExtraidos || !filesExtraidos.prendas) {
          console.debug('[buildFormData] ⚠️ Sin archivos extraídos');
          return fd;
        }

        // 2. Iterar prendas y agregar TODOS los archivos
        filesExtraidos.prendas.forEach(prenda => {
          const idx = prenda.idx;
          
          // IMÁGENES DE PRENDA: prendas[0][imagenes][0]
          if (Array.isArray(prenda.imagenes)) {
            prenda.imagenes.forEach((file, fileIdx) => {
              const key = `prendas[${idx}][imagenes][${fileIdx}]`;
              fd.append(key, file);
              console.debug(`[buildFormData] ✅ ${key} = ${file.name}`);
            });
          }
          
          // IMÁGENES DE TELAS: prendas[0][telas][0][imagenes][0]
          if (Array.isArray(prenda.telas)) {
            prenda.telas.forEach((telaFiles, telaIdx) => {
              if (Array.isArray(telaFiles)) {
                telaFiles.forEach((file, fileIdx) => {
                  const key = `prendas[${idx}][telas][${telaIdx}][imagenes][${fileIdx}]`;
                  fd.append(key, file);
                  console.debug(`[buildFormData] ✅ ${key} = ${file.name}`);
                });
              }
            });
          }
          
          // IMÁGENES DE PROCESOS: prendas[0][procesos][costura][imagenes][0]
          if (prenda.procesos && typeof prenda.procesos === 'object') {
            Object.entries(prenda.procesos).forEach(([procesoKey, procesoFiles]) => {
              if (Array.isArray(procesoFiles)) {
                procesoFiles.forEach((file, fileIdx) => {
                  const key = `prendas[${idx}][procesos][${procesoKey}][imagenes][${fileIdx}]`;
                  fd.append(key, file);
                  console.debug(`[buildFormData] ✅ ${key} = ${file.name}`);
                });
              }
            });
          }
        });

        console.debug('[buildFormData] ✅ COMPLETO - FormData construido');
        return fd;
      }
    };

    console.debug('[PayloadNormalizer] ✅ Sistema inicializado correctamente');
    </script>
</body>
</html>

