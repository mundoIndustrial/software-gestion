/**
 * CARGADOR DE DATOS EN MODO EDICIÓN - CREAR PEDIDO NUEVO
 * 
 * Carga los datos del pedido existente en el formulario de creación
 * para permitir edición en la interfaz crear-pedido-nuevo.blade.php
 */

let datosEditacionCargados = false;

// Esperar a que el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarCargaEdicion);
} else {
    iniciarCargaEdicion();
}

function iniciarCargaEdicion() {
    console.log('[EDICIÓN] Inicializando modo de edición en crear-pedido-nuevo...');
    console.log('[EDICIÓN] window.modoEdicion:', window.modoEdicion);
    console.log('[EDICIÓN] window.pedidoEditarId:', window.pedidoEditarId);
    
    if (!window.modoEdicion || !window.pedidoEditarId) {
        console.log('[EDICIÓN] No en modo edición, saltando');
        return;
    }

    // Esperar a que estén listos los elementos del DOM
    esperarElementosYCargar();
}

function esperarElementosYCargar(intentos = 0) {
    const clienteInput = document.getElementById('cliente_editable');
    const plendasContainer = document.getElementById('prendas-container-editable') || 
                             document.querySelector('[data-prendas-container]');

    if ((clienteInput || intentos > 20) && datosEditacionCargados === false) {
        console.log('[EDICIÓN] ✅ Elementos listos, cargando datos...');
        cargarDatosEdicion();
        datosEditacionCargados = true;
    } else if (intentos < 30) {
        setTimeout(() => esperarElementosYCargar(intentos + 1), 200);
    } else {
        console.warn('[EDICIÓN] ⚠️ Timeout esperando elementos del DOM');
    }
}

function cargarDatosEdicion() {
    try {
        console.log('[EDICIÓN] 🔄 Cargando datos para edición');
        
        // Si window.pedidoEditarData está dentro de un objeto 'pedido', acceder correctamente
        let pedido = window.pedidoEditarData;
        
        // Si llega como { pedido: {...}, estados: [...], areas: [...] }
        if (pedido && typeof pedido === 'object' && pedido.pedido && !Array.isArray(pedido)) {
            pedido = pedido.pedido;
        }
        
        if (!pedido) {
            console.warn('[EDICIÓN] No hay datos del pedido');
            return;
        }

        // 1. Cargar información general
        cargarInformacionGeneral(pedido);

        // 2. Cargar prendas si existen
        if (pedido.prendas && Array.isArray(pedido.prendas) && pedido.prendas.length > 0) {
            console.log('[EDICIÓN] 📦 Encontradas', pedido.prendas.length, 'prendas');
            cargarPrendas(pedido.prendas);
        }

        // 2.5. Cargar EPPs si existen
        const datosCompletos = window.pedidoEditarData;
        if (datosCompletos && datosCompletos.epps && Array.isArray(datosCompletos.epps) && datosCompletos.epps.length > 0) {
            console.log('[EDICIÓN] 🛡️ Encontrados', datosCompletos.epps.length, 'EPPs');
            cargarEPPs(datosCompletos.epps);
        }

        // 3. Actualizar título
        const titulo = `Editando Pedido #${window.pedidoEditarId}`;
        const pageHeader = document.querySelector('.page-header h1');
        if (pageHeader) {
            pageHeader.textContent = titulo;
        }
        document.title = titulo;

        console.log('[EDICIÓN] ✅ Datos cargados correctamente');

    } catch (error) {
        console.error('[EDICIÓN] ❌ Error cargando datos:', error);
    }
}

function cargarInformacionGeneral(pedido) {
    console.log('[EDICIÓN] 📝 Cargando información general...');
    
    try {
        const campos = {
            'cliente_editable': pedido.cliente,
            'forma_de_pago_editable': pedido.forma_de_pago,
        };

        Object.entries(campos).forEach(([id, valor]) => {
            if (!valor) return;

            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.value = valor;
                elemento.dispatchEvent(new Event('change', { bubbles: true }));
                console.log(`[EDICIÓN] ✓ ${id}: ${valor}`);
            }
        });

    } catch (error) {
        console.error('[EDICIÓN] ❌ Error cargando información general:', error);
    }
}

function cargarPrendas(prendas) {
    console.log('[EDICIÓN] 📦 Cargando', prendas.length, 'prendas...');
    console.log('[EDICIÓN] 📊 Prendas raw del backend:', prendas);
    
    // Asegurar que el gestor está inicializado
    if (!window.gestorPrendaSinCotizacion) {
        console.log('[EDICIÓN] ⚠️ Inicializando gestor de prendas...');
        if (typeof window.inicializarGestorSinCotizacion === 'function') {
            window.inicializarGestorSinCotizacion();
        } else if (window.GestorPrendaSinCotizacion) {
            window.gestorPrendaSinCotizacion = new GestorPrendaSinCotizacion();
        }
    }

    prendas.forEach((prenda, index) => {
        try {
            console.log(`[EDICIÓN] 📌 Agregando prenda ${index + 1}:`, prenda.nombre_prenda || 'Sin nombre');
            console.log(`[EDICIÓN] 📊 Datos raw de prenda:`, prenda);
            
            // Parsear datos que vienen como strings JSON desde Blade
            let generosConTallas = prenda.generosConTallas;
            console.log(`[EDICIÓN] 🔍 generosConTallas antes de parsear:`, generosConTallas, typeof generosConTallas);
            
            if (typeof generosConTallas === 'string') {
                try {
                    generosConTallas = JSON.parse(generosConTallas);
                    console.log(`[EDICIÓN] ✓ generosConTallas parseado como JSON`);
                } catch (e) {
                    console.warn('[EDICIÓN] ⚠️ No se pudo parsear generosConTallas:', generosConTallas);
                    generosConTallas = {};
                }
            }
            console.log(`[EDICIÓN] 🔍 generosConTallas después de parsear:`, generosConTallas);
            
            let variaciones = prenda.variaciones;
            if (typeof variaciones === 'string') {
                try {
                    variaciones = JSON.parse(variaciones);
                } catch (e) {
                    console.warn('[EDICIÓN] ⚠️ No se pudo parsear variaciones:', variaciones);
                    variaciones = {};
                }
            }
            
            let procesos = prenda.procesos;
            if (typeof procesos === 'string') {
                try {
                    procesos = JSON.parse(procesos);
                } catch (e) {
                    console.warn('[EDICIÓN] ⚠️ No se pudo parsear procesos:', procesos);
                    procesos = {};
                }
            }
            
            // Parsear genero si viene como string vacío
            let genero = prenda.genero;
            if (typeof genero === 'string' && (genero === '' || genero === '[]')) {
                genero = [];
            }
            
            console.log('[EDICIÓN] 📊 Datos parseados:', {
                generosConTallas,
                variaciones,
                procesos,
                genero
            });
            
            // ✅ Extraer tallas de generosConTallas
            const tallas = [];
            if (generosConTallas && typeof generosConTallas === 'object') {
                for (const genero in generosConTallas) {
                    if (generosConTallas[genero] && typeof generosConTallas[genero] === 'object') {
                        for (const talla in generosConTallas[genero]) {
                            if (!tallas.includes(talla)) {
                                tallas.push(talla);
                            }
                        }
                    }
                }
            }
            console.log('[EDICIÓN] 📏 Tallas extraídas:', tallas);
            
            // Agregar la prenda al gestor con datos correctos
            const prendasIndex = window.gestorPrendaSinCotizacion.agregarPrenda({
                nombre_producto: prenda.nombre_prenda || '',
                descripcion: prenda.descripcion || '',
                genero: genero,
                generosConTallas: generosConTallas,
                tallas: tallas,  // ✅ Pasar tallas extraídas
                cantidadesPorTalla: prenda.cantidadesPorTalla || {},
                telas: prenda.telas || [],
                telasAgregadas: prenda.telasAgregadas || [],
                fotos: prenda.fotos || [],
                telaFotos: prenda.telaFotos || [],
                imagenes: prenda.imagenes || prenda.fotos || [],  // ✅ Asegurar imagenes
                origen: prenda.origen || 'bodega',
                de_bodega: prenda.de_bodega || 1,
                procesos: procesos,
                variaciones: variaciones,
                tipo_manga: prenda.tipo_manga,
                obs_manga: prenda.obs_manga,
                tipo_broche: prenda.tipo_broche,
                obs_broche: prenda.obs_broche,
                tiene_bolsillos: prenda.tiene_bolsillos,
                obs_bolsillos: prenda.obs_bolsillos,
                tiene_reflectivo: prenda.tiene_reflectivo,
                obs_reflectivo: prenda.obs_reflectivo,
            });

            console.log(`[EDICIÓN] ✓ Prenda ${index + 1} agregada al gestor`);
            console.log(`[EDICIÓN] 📸 Fotos: ${(prenda.fotos || []).length}, 📋 Telas: ${(prenda.telas || []).length}, ⚙️  Procesos: ${Object.keys(procesos || {}).length}`);

        } catch (error) {
            console.error(`[EDICIÓN] ❌ Error procesando prenda ${index + 1}:`, error);
        }
    });

    // Renderizar todas las prendas
    try {
        // La función debería estar disponible en init-gestor-sin-cotizacion.js
        // Pero si no está, intentar renderizar directamente
        
        console.log('[EDICIÓN] 🔍 Buscando función de renderizado...');
        console.log('[EDICIÓN] typeof window.renderizarPrendasSinCotizacion:', typeof window.renderizarPrendasSinCotizacion);
        console.log('[EDICIÓN] typeof window.generarTarjetaPrendaReadOnly:', typeof window.generarTarjetaPrendaReadOnly);
        
        if (typeof window.renderizarPrendasSinCotizacion === 'function') {
            console.log('[EDICIÓN] 🎨 Renderizando prendas (window.renderizarPrendasSinCotizacion)...');
            window.renderizarPrendasSinCotizacion();
            console.log('[EDICIÓN] ✅ Prendas renderizadas');
            return;
        }
        
        // Alternativa: Si hay generador de tarjetas, usarlo directamente
        if (typeof window.generarTarjetaPrendaReadOnly === 'function' && window.gestorPrendaSinCotizacion) {
            console.log('[EDICIÓN] 🎨 Renderizando prendas (generarTarjetaPrendaReadOnly)...');
            
            const container = document.querySelector('[data-prendas-container]') || 
                            document.getElementById('prendas-container-editable') ||
                            document.querySelector('.prendas-items-container');
            
            if (container) {
                container.innerHTML = '';
                const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
                prendas.forEach((prenda, index) => {
                    const tarjeta = window.generarTarjetaPrendaReadOnly(prenda, index);
                    if (tarjeta) {
                        // Si es un string HTML, convertir a elemento
                        if (typeof tarjeta === 'string') {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = tarjeta;
                            while (tempDiv.firstChild) {
                                container.appendChild(tempDiv.firstChild);
                            }
                        } else {
                            // Si ya es un elemento
                            container.appendChild(tarjeta);
                        }
                    }
                });
                console.log('[EDICIÓN] ✅ Prendas renderizadas (alternativa)');
                return;
            }
        }
        
        // ⭐ NUEVO: Esperar a que se carguen los módulos de prenda-tarjeta
        console.warn('[EDICIÓN] ⚠️ Módulos prenda-tarjeta no disponibles, esperando evento "prenda-tarjeta-cargado"...');
        
        function intentarRenderizarPrendas() {
            if (typeof window.generarTarjetaPrendaReadOnly === 'function' && window.gestorPrendaSinCotizacion) {
                console.log('[EDICIÓN] 🎨 Renderizando prendas después de cargar módulos...');
                
                const container = document.querySelector('[data-prendas-container]') || 
                                document.getElementById('prendas-container-editable') ||
                                document.querySelector('.prendas-items-container');
                
                if (container) {
                    container.innerHTML = '';
                    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
                    prendas.forEach((prenda, index) => {
                        const tarjeta = window.generarTarjetaPrendaReadOnly(prenda, index);
                        if (tarjeta) {
                            if (typeof tarjeta === 'string') {
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = tarjeta;
                                while (tempDiv.firstChild) {
                                    container.appendChild(tempDiv.firstChild);
                                }
                            } else {
                                container.appendChild(tarjeta);
                            }
                        }
                    });
                    console.log('[EDICIÓN] ✅ Prendas renderizadas después de evento');
                    return true;
                }
            }
            return false;
        }
        
        // Escuchar el evento del loader
        document.addEventListener('prenda-tarjeta-cargado', () => {
            console.log('[EDICIÓN] 📢 Evento "prenda-tarjeta-cargado" recibido');
            if (!intentarRenderizarPrendas()) {
                // Reintentar en 100ms si aún no está disponible
                setTimeout(intentarRenderizarPrendas, 100);
            }
        }, { once: true });
        
        // Timeout como fallback (en caso que el evento no se dispare)
        setTimeout(() => {
            if (typeof window.generarTarjetaPrendaReadOnly !== 'function') {
                console.error('[EDICIÓN] ❌ generarTarjetaPrendaReadOnly aún no disponible después de 3 segundos');
            } else {
                intentarRenderizarPrendas();
            }
        }, 3000);
        
    } catch (error) {
        console.error('[EDICIÓN] ❌ Error renderizando prendas:', error);
    }
}

console.log('✅ [EDICIÓN] Módulo cargar-datos-edicion-nuevo.js cargado');

/**
 * Cargar EPPs al pedido
 */
function cargarEPPs(epps) {
    console.log('[EDICIÓN] 🛡️ Cargando', epps.length, 'EPPs...');
    
    try {
        // Asegurar que hay un contenedor de EPPs
        let eppContainer = document.getElementById('epps-container') ||
                          document.querySelector('[data-epps-container]') ||
                          document.querySelector('.epps-items-container');
        
        if (!eppContainer) {
            console.warn('[EDICIÓN] ⚠️ No hay contenedor de EPPs, creando uno...');
            // Si no existe, intentar encontrar dónde crear el contenedor
            const form = document.querySelector('form');
            if (form) {
                eppContainer = document.createElement('div');
                eppContainer.id = 'epps-container';
                eppContainer.className = 'epps-items-container';
                form.appendChild(eppContainer);
            } else {
                console.error('[EDICIÓN] ❌ No se puede encontrar contenedor de EPPs ni formulario');
                return;
            }
        }
        
        // Si el gestor tiene método para agregar EPPs
        if (window.gestorPrendaSinCotizacion && typeof window.gestorPrendaSinCotizacion.agregarEpp === 'function') {
            epps.forEach((epp, index) => {
                try {
                    window.gestorPrendaSinCotizacion.agregarEpp(epp);
                    console.log(`[EDICIÓN] ✓ EPP ${index + 1} agregado: ${epp.nombre}`);
                } catch (error) {
                    console.error(`[EDICIÓN] ❌ Error agregando EPP ${index + 1}:`, error);
                }
            });
            
            // Intentar renderizar EPPs si existe función
            if (typeof window.renderizarEppsSinCotizacion === 'function') {
                window.renderizarEppsSinCotizacion();
                console.log('[EDICIÓN] ✅ EPPs renderizados');
            }
        } else {
            console.log('[EDICIÓN] ℹ️ Gestor no tiene método agregarEpp, renderizando EPPs directamente...');
            
            // Renderizar EPPs directamente
            eppContainer.innerHTML = '';
            epps.forEach((epp, index) => {
                const eppCard = generarTarjetaEpp(epp, index);
                eppContainer.appendChild(eppCard);
            });
            console.log('[EDICIÓN] ✅ EPPs renderizados (forma directa)');
        }
        
    } catch (error) {
        console.error('[EDICIÓN] ❌ Error cargando EPPs:', error);
    }
}

/**
 * Generar tarjeta de EPP
 */
function generarTarjetaEpp(epp, index) {
    const card = document.createElement('div');
    card.className = 'epp-item-card';
    card.innerHTML = `
        <div class="epp-header">
            <h5>${epp.nombre || 'EPP Desconocido'}</h5>
            <span class="epp-qty">Cantidad: ${epp.cantidad || 0}</span>
        </div>
        ${epp.descripcion ? `<p class="epp-description">${epp.descripcion}</p>` : ''}
        ${epp.observaciones ? `<p class="epp-observations"><strong>Observaciones:</strong> ${epp.observaciones}</p>` : ''}
        ${epp.imagenes && epp.imagenes.length > 0 ? `
            <div class="epp-images">
                ${epp.imagenes.map(img => `<img src="${img.url || img}" alt="Imagen EPP" class="epp-img" />`).join('')}
            </div>
        ` : ''}
    `;
    return card;
}
