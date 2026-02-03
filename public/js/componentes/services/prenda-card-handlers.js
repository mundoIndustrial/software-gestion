/**
 * PrendaCardHandlers - Gestión de eventos para tarjetas de prenda
 * Maneja clicks en menús, botones, fotos y galerías
 */

console.log('🚀🚀🚀 [PRENDA-CARD-HANDLERS] ARCHIVO CARGADO'); // Log al inicio del archivo

window.PrendaCardHandlers = {
    _listenersRegistrados: false,

    inicializar(tarjeta, prenda, indice, callbacks = {}) {

        // 🔥 CRÍTICO: Solo registrar listeners UNA SOLA VEZ
        if (!this._listenersRegistrados) {
            console.log('[PrendaCardHandlers] 🚀 Registrando listeners globales por primera vez...');
            this._setupEventListeners();
            this._listenersRegistrados = true;
            console.log('[PrendaCardHandlers] ✅ Listeners registrados correctamente');
        } else {
            console.log('[PrendaCardHandlers] ⏭️ Listeners ya fueron registrados, saltando...');
        }
    },

    _setupEventListeners() {
        console.log('🔥🔥🔥 [PrendaCardHandlers] _setupEventListeners() INICIÁNDOSE...'); // DEBUG CRÍTICO
        
        // Debug: Capturar todos los clicks en botones de tres puntos para diagnóstico
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-menu-tres-puntos')) {
                console.log('[DEBUG GLOBAL] 🖱️ Click capturado en listener global - btn-menu-tres-puntos');
                console.log('[DEBUG GLOBAL] 📦 Target:', e.target);
                console.log('[DEBUG GLOBAL] 📦 Closest:', e.target.closest('.btn-menu-tres-puntos'));
            }
        }, true); // Use capture phase
        
        // Listener específico para menú de 3 puntos - PRIORIDAD MÁXIMA
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-menu-tres-puntos');
            if (btn) {
                console.log('[PrendaCardHandlers] 🖱️ CLICK en btn-menu-tres-puntos (listener específico)');
                e.stopPropagation();
                e.preventDefault();
                e.stopImmediatePropagation(); // Evitar otros listeners
                
                const submenu = btn.nextElementSibling;
                
                console.log('[PrendaCardHandlers] 📦 Botón:', btn);
                console.log('[PrendaCardHandlers] 📦 Submenú:', submenu);
                
                document.querySelectorAll('.submenu-prenda').forEach(menu => {
                    if (menu !== submenu) menu.style.display = 'none';
                });
                
                if (submenu) {
                    submenu.style.display = submenu.style.display === 'none' ? 'flex' : 'none';
                    console.log('[PrendaCardHandlers] 🔄 Submenú display cambiado a:', submenu.style.display);
                } else {
                    console.error('[PrendaCardHandlers] ❌ No se encontró el submenú');
                }
                return; // Salir para no procesar otros listeners
            }
        }, true); // Use capture phase con máxima prioridad
        
        // Expandir/contraer secciones
        // Listener general para TODOS los clicks en la tarjeta - MÁXIMA PRIORIDAD
        document.addEventListener('click', (e) => {
            console.log('🖱️ [PrendaCardHandlers] CLICK GLOBAL DETECTADO en:', e.target, 'Closest prenda-card:', e.target.closest('.prenda-card-readonly'));
            
            if (e.target.closest('.seccion-expandible-header')) {
                e.stopPropagation(); // Evitar que otros listeners se ejecuten
                // e.preventDefault();  // Removido para permitir comportamiento del botón
                
                // Prevenir doble click rápido
                const header = e.target.closest('.seccion-expandible-header');
                if (header.dataset.toggleDisabled === 'true') {
                    console.log('[PrendaCardHandlers] 🚫 Toggle deshabilitado temporalmente');
                    return;
                }
                
                // Deshabilitar temporalmente para prevenir doble click
                header.dataset.toggleDisabled = 'true';
                setTimeout(() => {
                    header.dataset.toggleDisabled = 'false';
                }, 300); // 300ms de protección
                
                console.log('[PrendaCardHandlers] 🖱️ CLICK detectado en sección expandible');
                console.log('[PrendaCardHandlers] 📦 Target:', e.target);
                console.log('[PrendaCardHandlers] 📦 Closest header:', header);

                const content = header.nextElementSibling;
                
                console.log('[PrendaCardHandlers] 📄 Content element:', content);
                console.log('[PrendaCardHandlers] 📄 Content classes:', content ? content.className : 'No encontrado');
                
                if (content && content.classList.contains('seccion-expandible-content')) {
                    console.log('[PrendaCardHandlers] ✅ Content válido, toggling classes');
                    
                    const wasActive = content.classList.contains('active');
                    content.classList.toggle('active');
                    header.classList.toggle('active');
                    
                    const isActive = content.classList.contains('active');
                    console.log('[PrendaCardHandlers] 🔄 Toggle completado:', { wasActive, isActive });
                    console.log('[PrendaCardHandlers] 📄 Nuevas clases content:', content.className);
                    console.log('[PrendaCardHandlers] 📄 Nuevas clases header:', header.className);
                    
                    // Usar clases CSS en lugar de estilos inline para evitar conflictos con !important
                    if (isActive) {
                        // LIMPIAR COMPLETAMENTE todos los estilos inline
                        content.style.cssText = '';
                        
                        // Forzar display:block inmediatamente si las clases CSS no funcionan
                        setTimeout(() => {
                            const computedDisplay = window.getComputedStyle(content).display;
                            if (computedDisplay === 'none') {
                                console.log('[PrendaCardHandlers] ⚠️ CSS no funcionó, forzando display:block con JavaScript');
                                content.style.setProperty('display', 'block', 'important');
                                
                                // Verificar después de forzar
                                setTimeout(() => {
                                    const newDisplay = window.getComputedStyle(content).display;
                                    console.log('[PrendaCardHandlers] ✅ Después de forzar - display:', newDisplay, 'scrollHeight:', content.scrollHeight);
                                }, 50);
                            } else {
                                console.log('[PrendaCardHandlers] ✅ CSS funcionó - display:', computedDisplay, 'scrollHeight:', content.scrollHeight);
                            }
                        }, 50);
                        
                        console.log('[PrendaCardHandlers]  Estilos inline limpiados completamente, usando clases CSS');
                    } else {
                        // LIMPIAR COMPLETAMENTE todos los estilos inline
                        content.style.cssText = '';
                        
                        // Forzar display:none inmediatamente si las clases CSS no funcionan
                        setTimeout(() => {
                            const computedDisplay = window.getComputedStyle(content).display;
                            if (computedDisplay !== 'none') {
                                console.log('[PrendaCardHandlers] ⚠️ CSS no funcionó, forzando display:none con JavaScript');
                                content.style.setProperty('display', 'none', 'important');
                                
                                // Verificar después de forzar
                                setTimeout(() => {
                                    const newDisplay = window.getComputedStyle(content).display;
                                    console.log('[PrendaCardHandlers] ✅ Después de forzar cierre - display:', newDisplay, 'scrollHeight:', content.scrollHeight);
                                }, 50);
                            } else {
                                console.log('[PrendaCardHandlers] ✅ CSS funcionó para cierre - display:', computedDisplay, 'scrollHeight:', content.scrollHeight);
                            }
                        }, 50);
                        
                        console.log('[PrendaCardHandlers]  Estilos inline limpiados completamente, dejando CSS controlar el display');
                    }
                    
                    // Verificar estilos computados con más tiempo para asegurar que se aplique
                    setTimeout(() => {
                        const computedStyle = window.getComputedStyle(content);
                        const displayStyle = computedStyle.getPropertyValue('display');
                        const inlineStyle = content.getAttribute('style');
                        console.log('[PrendaCardHandlers]  Computed style display:', displayStyle);
                        console.log('[PrendaCardHandlers]  Inline style actual:', inlineStyle);
                        console.log('[PrendaCardHandlers]  Computed style visibility:', computedStyle.getPropertyValue('visibility'));
                        console.log('[PrendaCardHandlers]  Computed style height:', computedStyle.getPropertyValue('height'));
                        
                        // Medición inicial
                        console.log('[PrendaCardHandlers] 📏 Medición inicial - scrollHeight:', content.scrollHeight, 'clientHeight:', content.clientHeight, 'offsetHeight:', content.offsetHeight);
                        
                        // Si scrollHeight es 0, hacer una segunda medición después de más tiempo
                        if (content.scrollHeight === 0 && isActive) {
                            console.log('[PrendaCardHandlers] ⚠️ scrollHeight es 0, haciendo segunda medición...');
                            setTimeout(() => {
                                console.log('[PrendaCardHandlers] 📏 Medición diferida - scrollHeight:', content.scrollHeight, 'clientHeight:', content.clientHeight, 'offsetHeight:', content.offsetHeight);
                                
                                // Forzar un reflow si es necesario
                                if (content.scrollHeight === 0) {
                                    console.log('[PrendaCardHandlers] 🔧 Forzando reflow...');
                                    content.style.display = 'none';
                                    content.offsetHeight; // Forzar reflow
                                    content.style.display = '';
                                    content.offsetHeight; // Forzar reflow again
                                    
                                    setTimeout(() => {
                                        console.log('[PrendaCardHandlers] 📏 Medición después de reflow - scrollHeight:', content.scrollHeight, 'clientHeight:', content.clientHeight, 'offsetHeight:', content.offsetHeight);
                                    }, 50);
                                }
                            }, 200);
                        }
                        
                        // Verificar si hay otros estilos aplicados
                        const allStyles = window.getComputedStyle(content, null);
                        console.log('[PrendaCardHandlers]  Todos los estilos aplicados:', {
                            display: allStyles.display,
                            visibility: allStyles.visibility,
                            height: allStyles.height,
                            maxHeight: allStyles.maxHeight,
                            overflow: allStyles.overflow,
                            opacity: allStyles.opacity,
                            position: allStyles.position,
                            top: allStyles.top,
                            left: allStyles.left,
                            transform: allStyles.transform,
                            clip: allStyles.clip,
                            clipPath: allStyles.clipPath
                        });
                        
                        // Verificar contenido de la sección
                        console.log('[PrendaCardHandlers] 📦 Contenido HTML de la sección:', content.innerHTML.substring(0, 200) + '...');
                        console.log('[PrendaCardHandlers] 📦 Contenido HTML completo:', content.innerHTML);
                        console.log('[PrendaCardHandlers] 📦 Número de elementos hijos:', content.children.length);
                        console.log('[PrendaCardHandlers] 📦 Altura scrollHeight:', content.scrollHeight);
                        console.log('[PrendaCardHandlers] 📦 Altura clientHeight:', content.clientHeight);
                        console.log('[PrendaCardHandlers] 📦 OffsetHeight:', content.offsetHeight);
                        
                        // Verificar si hay elementos específicos según el tipo de sección
                        const section = header.getAttribute('data-section');
                        console.log('[PrendaCardHandlers] 🏷️ Tipo de sección:', section);
                        
                        if (section === 'variaciones') {
                            const variantes = content.querySelectorAll('.variacion-item');
                            console.log('[PrendaCardHandlers] 👗 Variaciones encontradas:', variantes.length);
                            
                            // Verificar filas de la tabla
                            const tableRows = content.querySelectorAll('table tr');
                            console.log('[PrendaCardHandlers] 📊 Filas de tabla encontradas:', tableRows.length);
                            console.log('[PrendaCardHandlers] 📊 Filas de tabla:', tableRows);
                            
                            // Verificar si hay tbody con datos
                            const tbody = content.querySelector('table tbody');
                            if (tbody) {
                                console.log('[PrendaCardHandlers] 📊 TBODY encontrado:', tbody.innerHTML);
                                const dataRows = tbody.querySelectorAll('tr');
                                console.log('[PrendaCardHandlers] 📊 Filas de datos en TBODY:', dataRows.length);
                            } else {
                                console.log('[PrendaCardHandlers] 📊 No se encontró TBODY en la tabla');
                            }
                        } else if (section === 'tallas-y-cantidades') {
                            const tallas = content.querySelectorAll('.talla-item, .talla-row');
                            console.log('[PrendaCardHandlers] 📏 Tallas encontradas:', tallas.length);
                        } else if (section === 'procesos') {
                            const procesos = content.querySelectorAll('.proceso-item');
                            console.log('[PrendaCardHandlers] ⚙️ Procesos encontrados:', procesos.length);
                        }
                        
                        // 🔍 DIAGNÓSTICO AVANZADO - Verificar elementos padres
                        console.log('[PrendaCardHandlers] 🔍 DIAGNÓSTICO DE PADRES:');
                        let parent = content.parentElement;
                        let level = 0;
                        while (parent && level < 8) { // Aumenté a 8 niveles
                            const parentStyle = window.getComputedStyle(parent);
                            const parentRect = parent.getBoundingClientRect();
                            console.log(`[PrendaCardHandlers] 📁 Padre Nivel ${level}:`, {
                                tagName: parent.tagName,
                                className: parent.className,
                                display: parentStyle.display,
                                visibility: parentStyle.visibility,
                                height: parentStyle.height,
                                maxHeight: parentStyle.maxHeight,
                                overflow: parentStyle.overflow,
                                position: parentStyle.position,
                                opacity: parentStyle.opacity,
                                rect: {
                                    width: parentRect.width,
                                    height: parentRect.height,
                                    top: parentRect.top,
                                    left: parentRect.left
                                }
                            });
                            
                            // Verificar si este padre está oculto
                            if (parentStyle.display === 'none' || 
                                parentStyle.visibility === 'hidden' || 
                                parentStyle.opacity === '0' ||
                                parentRect.height === 0) {
                                console.error(`[PrendaCardHandlers] ❌ PADRE OCULTO EN NIVEL ${level}:`, parent);
                                console.error(`[PrendaCardHandlers] ❌ Razón: display=${parentStyle.display}, visibility=${parentStyle.visibility}, opacity=${parentStyle.opacity}, height=${parentRect.height}`);
                            }
                            
                            parent = parent.parentElement;
                            level++;
                        }
                        
                        // Verificar si el elemento está fuera del viewport
                        const rect = content.getBoundingClientRect();
                        console.log('[PrendaCardHandlers] 📍 Posición en viewport:', {
                            top: rect.top,
                            left: rect.left,
                            bottom: rect.bottom,
                            right: rect.right,
                            width: rect.width,
                            height: rect.height,
                            isInViewport: rect.top >= 0 && rect.left >= 0 && rect.bottom <= window.innerHeight && rect.right <= window.innerWidth
                        });
                    }, 50);
                } else {
                    console.log('[PrendaCardHandlers] ❌ Content no válido o no tiene clase seccion-expandible-content');
                }
            }

            // Botón EDITAR
            if (e.target.closest('.btn-editar-prenda')) {

                e.stopPropagation();
                const btn = e.target.closest('.btn-editar-prenda');
                const prendaIndex = parseInt(btn.dataset.prendaIndex);

                
                let prenda = null;
                let esCrearNuevo = false;
                
                // Prioridad 1: Obtener desde GestionItemsUI (crear-nuevo)
                if (window.gestionItemsUI) {
                    const itemsOrdenados = window.gestionItemsUI.obtenerItemsOrdenados();
                    if (itemsOrdenados && itemsOrdenados[prendaIndex]) {
                        prenda = itemsOrdenados[prendaIndex];
                        esCrearNuevo = true;

                    }
                }
                
                // Prioridad 2: Obtener desde itemsPedido (fallback)
                if (!prenda && window.itemsPedido && window.itemsPedido[prendaIndex]) {
                    prenda = window.itemsPedido[prendaIndex];

                }
                
                // Prioridad 3: Obtener desde gestor (pedidos guardados)
                if (!prenda && window.gestorPrendaSinCotizacion) {
                    prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);

                }
                


                
                if (prenda) {
                    // Si es crear-nuevo, abrir el modal de creación con datos precargados
                    if (esCrearNuevo && window.gestionItemsUI) {

                        window.gestionItemsUI.prendaEditIndex = prendaIndex;
                        window.gestionItemsUI.abrirModalAgregarPrendaNueva();
                        
                        // Esperar a que el modal se abra, se limpie y luego cargar datos
                        // Aumentar delay para asegurar que modal-cleanup termine
                        setTimeout(() => {

                            window.gestionItemsUI.prendaEditor?.cargarPrendaEnModal(prenda, prendaIndex);
                        }, 500);
                    }
                    // Si es pedido guardado, abrir modal simple de edición
                    else if (window.abrirEditarPrendaModal) {
                        // Obtener pedidoId de múltiples fuentes
                        let pedidoId = null;
                        
                        // 1. Desde body dataset (establecido en editar-pedido.blade.php)
                        if (document.body.dataset.pedidoIdEdicion) {
                            pedidoId = document.body.dataset.pedidoIdEdicion;
                        }
                        // 2. Desde window global (editar-pedido.blade.php)
                        else if (window.pedidoEdicionId) {
                            pedidoId = window.pedidoEdicionId;
                        }
                        // 3. Desde elemento con data-pedido-id
                        else {
                            pedidoId = document.querySelector('[data-pedido-id]')?.dataset.pedidoId || null;
                        }

                        console.log('🔥 [btn-editar-prenda] Llamando abrirEditarPrendaModal:', {
                            prendaIndex,
                            prendaId: prenda.id,
                            pedidoId
                        });
                        
                        window.abrirEditarPrendaModal(prenda, prendaIndex, pedidoId);
                    }
                }
                
                const submenu = btn.closest('.submenu-prenda');
                if (submenu) submenu.style.display = 'none';
            }

            // Botón ELIMINAR
            if (e.target.closest('.btn-eliminar-prenda')) {

                e.stopPropagation();
                const btn = e.target.closest('.btn-eliminar-prenda');
                const prendaIndex = parseInt(btn.dataset.prendaIndex);

                console.log('🔍 [ELIMINAR-PRENDA] Iniciando eliminación de prenda:', prendaIndex);
                console.log('🔍 [ELIMINAR-PRENDA] window.gestionItemsUI existe:', !!window.gestionItemsUI);
                console.log('🔍 [ELIMINAR-PRENDA] window.gestorPrendaSinCotizacion existe:', !!window.gestorPrendaSinCotizacion);
                
                Swal.fire({
                    title: '¿Eliminar prenda?',
                    text: 'Esta acción no se puede deshacer',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('✅ [ELIMINAR-PRENDA] Usuario confirmó eliminación');
                        
                        // 🟢 NUEVO: LIMPIAR PROCESOS ANTES DE ELIMINAR
                        console.log('🧹 [ELIMINAR-PRENDA] Limpiando procesos seleccionados...');
                        if (window.limpiarProcesosSeleccionados) {
                            window.limpiarProcesosSeleccionados();
                            console.log('✅ [ELIMINAR-PRENDA] Procesos limpiados');
                        } else {
                            console.warn('⚠️ [ELIMINAR-PRENDA] window.limpiarProcesosSeleccionados NO disponible');
                        }
                        
                        // Obtener instancia de GestionItemsUI si existe
                        if (window.gestionItemsUI) {
                            console.log('✅ [ELIMINAR-PRENDA] Eliminando desde gestionItemsUI');
                            // 🔴 CRÍTICO: Usar el nuevo método eliminarPrendaDelOrden
                            if (window.gestionItemsUI.eliminarPrendaDelOrden) {
                                console.log('✅ [ELIMINAR-PRENDA] Método eliminarPrendaDelOrden disponible');
                                window.gestionItemsUI.eliminarPrendaDelOrden(prendaIndex);
                                console.log('✅ [ELIMINAR-PRENDA] Prenda eliminada del estado interno');
                            } else {
                                console.error('❌ [ELIMINAR-PRENDA] Método eliminarPrendaDelOrden NO DISPONIBLE');
                            }
                        } else {
                            console.warn('⚠️ [ELIMINAR-PRENDA] window.gestionItemsUI NO disponible');
                        }
                        
                        // También eliminar desde gestor si existe
                        if (window.gestorPrendaSinCotizacion?.eliminar) {
                            console.log('✅ [ELIMINAR-PRENDA] Eliminando desde gestorPrendaSinCotizacion');
                            window.gestorPrendaSinCotizacion.eliminar(prendaIndex);
                        }
                        
                        // 🔴 NUEVO: RE-RENDERIZAR LA LISTA PARA REFLEJAR LOS CAMBIOS
                        console.log('🔄 [ELIMINAR-PRENDA] Re-renderizando lista de items...');
                        if (window.gestionItemsUI && window.gestionItemsUI.renderer) {
                            const itemsOrdenados = window.gestionItemsUI.obtenerItemsOrdenados();
                            console.log('📦 [ELIMINAR-PRENDA] Items restantes para renderizar:', itemsOrdenados.length);
                            window.gestionItemsUI.renderer.actualizar(itemsOrdenados);
                            console.log('✅ [ELIMINAR-PRENDA] Lista re-renderizada correctamente');
                        } else {
                            console.warn('⚠️ [ELIMINAR-PRENDA] No se pudo re-renderizar (renderer no disponible)');
                            // Fallback: eliminar del DOM manualmente
                            const prendaCard = document.querySelector(`.prenda-card-readonly[data-prenda-index="${prendaIndex}"]`);
                            if (prendaCard) {
                                console.log('✅ [ELIMINAR-PRENDA] ELIMINANDO TARJETA DEL DOM (FALLBACK)');
                                prendaCard.remove();
                            }
                        }
                    }
                });
                
                const submenu = btn.closest('.submenu-prenda');
                if (submenu) submenu.style.display = 'none';
            }

            // Cerrar menú al hacer click fuera
            if (!e.target.closest('.prenda-menu-contextual')) {
                document.querySelectorAll('.submenu-prenda').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });

        // Galerías de fotos
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('foto-principal-readonly')) {

                e.stopPropagation();
                const prendaIndex = parseInt(e.target.dataset.prendaIndex);

                
                let prenda = null;
                // Obtener desde GestionItemsUI (fuente principal)
                if (window.gestionItemsUI && window.gestionItemsUI.prendas && window.gestionItemsUI.prendas[prendaIndex]) {
                    prenda = window.gestionItemsUI.prendas[prendaIndex];

                } else if (window.itemsPedido && window.itemsPedido[prendaIndex]) {
                    prenda = window.itemsPedido[prendaIndex];

                } else if (window.gestorPrendaSinCotizacion) {
                    prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);

                }
                

                if (prenda && prenda.imagenes && prenda.imagenes.length > 0) {
                    this._abrirGaleriaFotos(prenda, prendaIndex);
                } else {

                }
            }

            if (e.target.classList.contains('foto-tela-readonly')) {

                e.stopPropagation();
                const prendaIndex = parseInt(e.target.dataset.prendaIndex);

                
                let prenda = null;
                // Obtener desde GestionItemsUI (fuente principal)
                if (window.gestionItemsUI && window.gestionItemsUI.prendas && window.gestionItemsUI.prendas[prendaIndex]) {
                    prenda = window.gestionItemsUI.prendas[prendaIndex];

                } else if (window.itemsPedido && window.itemsPedido[prendaIndex]) {
                    prenda = window.itemsPedido[prendaIndex];

                } else if (window.gestorPrendaSinCotizacion) {
                    prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);

                }
                

                if (prenda && prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
                    this._abrirGaleriaTelas(prenda, prendaIndex);
                } else {

                }
            }
        });
    },

    _abrirGaleriaFotos(prenda, prendaIndex) {

        
        let imagenes = (prenda.imagenes?.length > 0 ? prenda.imagenes : null) || 
                       (prenda.fotos?.length > 0 ? prenda.fotos : null) || 
                       [];

        
        const fotosUrls = imagenes.map((img, idx) => {
            // Usar servicio centralizado para convertir imágenes
            const url = window.ImageConverterService ? 
                window.ImageConverterService.convertirAUrl(img) : 
                null;
            
            if (url) {
                return url;
            }
            
            // Fallback si ImageConverterService no está disponible
            if (img && img.blobUrl && typeof img.blobUrl === 'string') {
                return img.blobUrl;
            }
            else if (img && img.file instanceof File) {
                return URL.createObjectURL(img.file);
            } else if (img instanceof File) {
                return URL.createObjectURL(img);
            } else if (typeof img === 'string') {
                return img;
            }
            return null;
        }).filter(url => url !== null);
        

        
        if (fotosUrls.length === 0) {

            Swal.fire({
                title: ' Sin fotos',
                html: '<p style="color: #666;">Esta prenda no tiene fotos cargadas</p>',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#0ea5e9'
            });
            return;
        }

        let indiceActual = 0;

        const generarContenidoGaleria = (idx) => {
            return `
                <div style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div id="galeria-foto-container" style="position: relative; display: flex; justify-content: center; align-items: center; width: 500px; height: 350px; background: #f9fafb; border-radius: 12px; padding: 1rem;">
                        <img 
                            id="galeria-foto-actual"
                            src="${fotosUrls[idx]}" 
                            alt="Foto prenda"
                            style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px; image-rendering: crisp-edges;"
                        />
                        ${fotosUrls.length > 1 ? `
                            <button id="btn-foto-anterior" type="button" 
                                style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); 
                                       background: rgba(0,0,0,0.6); color: white; border: none; 
                                       width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s; z-index: 10;">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button id="btn-foto-siguiente" type="button" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                       background: rgba(0,0,0,0.6); color: white; border: none; 
                                       width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s; z-index: 10;">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        ` : ''}
                    </div>
                    <div style="text-align: center; color: #666; font-size: 0.9rem; margin-top: 1rem;">
                        <i class="fas fa-images"></i> Foto ${idx + 1} de ${fotosUrls.length}
                    </div>
                </div>
            `;
        };

        Swal.fire({
            title: `${prenda.nombre_producto || prenda.nombre_prenda || 'Galería de Fotos'}`,
            html: generarContenidoGaleria(indiceActual),
            width: 'auto',
            padding: '1.5rem',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#0ea5e9',
            allowOutsideClick: true,
            allowEscapeKey: true,
            backdrop: 'rgba(0, 0, 0, 0.4)',
            position: 'center',
            scrollbarPadding: false,
            didOpen: () => {
                // Centrar el modal perfectamente en la vista
                const popup = Swal.getPopup();
                if (popup) {
                    popup.style.width = 'auto';
                    popup.style.height = 'auto';
                    popup.style.maxWidth = '600px';
                    popup.style.display = 'flex';
                    popup.style.flexDirection = 'column';
                    popup.style.justifyContent = 'center';
                    popup.style.alignItems = 'center';
                    popup.style.position = 'fixed';
                    popup.style.top = '50%';
                    popup.style.left = '50%';
                    popup.style.transform = 'translate(-50%, -50%)';
                    popup.style.minHeight = 'auto';
                }
                
                const actualizarGaleria = () => {
                    const container = document.querySelector('.swal2-html-container');
                    if (container) {
                        container.innerHTML = generarContenidoGaleria(indiceActual);
                        
                        const btnAnterior = document.getElementById('btn-foto-anterior');
                        const btnSiguiente = document.getElementById('btn-foto-siguiente');


                        if (btnAnterior) {
                            btnAnterior.addEventListener('click', (e) => {
                                e.stopPropagation();
                                indiceActual = (indiceActual - 1 + fotosUrls.length) % fotosUrls.length;
                                actualizarGaleria();
                            });
                        }

                        if (btnSiguiente) {
                            btnSiguiente.addEventListener('click', (e) => {
                                e.stopPropagation();
                                indiceActual = (indiceActual + 1) % fotosUrls.length;
                                actualizarGaleria();
                            });
                        }
                    }
                };
                
                actualizarGaleria();
            }
        });
    },

    _abrirGaleriaTelas(prenda, prendaIndex) {

        
        const telas = prenda.telasAgregadas || [];

        
        if (telas.length === 0) {

            Swal.fire({
                title: ' Sin telas',
                html: '<p style="color: #666;">Esta prenda no tiene telas cargadas</p>',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#0ea5e9'
            });
            return;
        }

        const telasConFotos = [];
        telas.forEach((tela, telaIdx) => {
            if (tela.imagenes && Array.isArray(tela.imagenes)) {
                const fotosUrlsTela = tela.imagenes.map((img) => {
                    // Usar servicio centralizado para convertir imágenes
                    const url = window.ImageConverterService ? 
                        window.ImageConverterService.convertirAUrl(img) : 
                        null;
                    
                    if (url) {
                        return url;
                    }
                    
                    // Fallback si ImageConverterService no está disponible
                    if (img.blobUrl && typeof img.blobUrl === 'string') {
                        return img.blobUrl;
                    }
                    else if (img.file instanceof File) {
                        return URL.createObjectURL(img.file);
                    } else if (img instanceof File) {
                        return URL.createObjectURL(img);
                    } else if (typeof img === 'string') {
                        return img;
                    }
                    return null;
                }).filter(url => url !== null);

                if (fotosUrlsTela.length > 0) {
                    telasConFotos.push({
                        nombre: tela.tela || `Tela ${telaIdx + 1}`,
                        color: tela.color || 'N/A',
                        referencia: tela.referencia || 'N/A',
                        fotos: fotosUrlsTela
                    });
                }
            }
        });



        if (telasConFotos.length === 0) {

            Swal.fire({
                title: ' Sin fotos',
                html: '<p style="color: #666;">Las telas no tienen fotos cargadas</p>',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#0ea5e9'
            });
            return;
        }

        let telaActualIdx = 0;
        let fotoActualIdx = 0;

        const generarContenidoGaleriaTela = (telaIdx, fotoIdx) => {
            const tela = telasConFotos[telaIdx];
            const foto = tela.fotos[fotoIdx];

            return `
                <div style="max-width: 500px; margin: 0 auto;">
                    <div style="background: #f0f9ff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #0ea5e9;">
                        <div style="font-weight: 700; color: #0369a1; margin-bottom: 0.5rem;">
                            <i class="fas fa-cube"></i> ${tela.nombre}
                        </div>
                        <div style="font-size: 0.85rem; color: #4b5563;">
                            <div><strong>Color:</strong> ${tela.color}</div>
                            <div><strong>Ref:</strong> ${tela.referencia}</div>
                        </div>
                    </div>

                    <div id="galeria-tela-container" style="position: relative; margin-bottom: 1rem;">
                        <img 
                            id="galeria-tela-actual"
                            src="${foto}" 
                            alt="Foto tela"
                            style="width: 100%; border-radius: 8px; max-height: 400px; object-fit: contain; border: 2px solid #e5e7eb;"
                        />
                        ${tela.fotos.length > 1 ? `
                            <button id="btn-tela-anterior" type="button" 
                                style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); 
                                       background: rgba(0,0,0,0.6); color: white; border: none; 
                                       width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s;">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button id="btn-tela-siguiente" type="button" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                       background: rgba(0,0,0,0.6); color: white; border: none; 
                                       width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s;">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        ` : ''}
                    </div>

                    <div style="text-align: center; color: #666; font-size: 0.9rem;">
                        <i class="fas fa-images"></i> Tela ${telaIdx + 1} de ${telasConFotos.length} | Foto ${fotoIdx + 1} de ${tela.fotos.length}
                    </div>
                </div>
            `;
        };

        Swal.fire({
            title: ` Telas - ${prenda.nombre_producto}`,
            html: generarContenidoGaleriaTela(telaActualIdx, fotoActualIdx),
            width: '600px',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#0ea5e9',
            didOpen: () => {
                const actualizarGaleriaTela = () => {
                    const container = document.querySelector('.swal2-html-container');
                    if (container) {
                        container.innerHTML = generarContenidoGaleriaTela(telaActualIdx, fotoActualIdx);
                        
                        const btnAnterior = document.getElementById('btn-tela-anterior');
                        const btnSiguiente = document.getElementById('btn-tela-siguiente');

                        if (btnAnterior) {
                            btnAnterior.addEventListener('click', (e) => {
                                e.stopPropagation();
                                fotoActualIdx = (fotoActualIdx - 1 + telasConFotos[telaActualIdx].fotos.length) % telasConFotos[telaActualIdx].fotos.length;
                                actualizarGaleriaTela();
                            });
                        }

                        if (btnSiguiente) {
                            btnSiguiente.addEventListener('click', (e) => {
                                e.stopPropagation();
                                fotoActualIdx = (fotoActualIdx + 1) % telasConFotos[telaActualIdx].fotos.length;
                                actualizarGaleriaTela();
                            });
                        }
                    }
                };
                
                actualizarGaleriaTela();
            }
        });
    }
};


