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



    
    if (!window.modoEdicion || !window.pedidoEditarId) {

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

        cargarDatosEdicion();
        datosEditacionCargados = true;
    } else if (intentos < 30) {
        setTimeout(() => esperarElementosYCargar(intentos + 1), 200);
    } else {

    }
}

function cargarDatosEdicion() {
    try {

        
        // Si window.pedidoEditarData está dentro de un objeto 'pedido', acceder correctamente
        let pedido = window.pedidoEditarData;
        
        // Si llega como { pedido: {...}, estados: [...], areas: [...] }
        if (pedido && typeof pedido === 'object' && pedido.pedido && !Array.isArray(pedido)) {
            pedido = pedido.pedido;
        }
        
        if (!pedido) {

            return;
        }

        // 1. Cargar información general
        cargarInformacionGeneral(pedido);

        // 2. Cargar prendas si existen
        if (pedido.prendas && Array.isArray(pedido.prendas) && pedido.prendas.length > 0) {

            cargarPrendas(pedido.prendas);
        }

        // 2.5. Cargar EPPs si existen
        const datosCompletos = window.pedidoEditarData;
        if (datosCompletos && datosCompletos.epps && Array.isArray(datosCompletos.epps) && datosCompletos.epps.length > 0) {

            cargarEPPs(datosCompletos.epps);
        }

        // 2.6. Renderizar tarjetas registradas en gestionItemsUI
        renderizarItemsRegistrados();

        // 3. Actualizar título
        const titulo = `Editando Pedido #${window.pedidoEditarId}`;
        const pageHeader = document.querySelector('.page-header h1');
        if (pageHeader) {
            pageHeader.textContent = titulo;
        }
        document.title = titulo;



    } catch (error) {

    }
}

function cargarInformacionGeneral(pedido) {

    
    try {
        const campos = {
            'cliente_editable': pedido.cliente,
            'orden_compra_editable': pedido.orden_compra,
            'forma_de_pago_editable': pedido.forma_de_pago,
        };

        Object.entries(campos).forEach(([id, valor]) => {
            if (!valor) return;

            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.value = valor;
                elemento.dispatchEvent(new Event('change', { bubbles: true }));

            }
        });

    } catch (error) {

    }
}

function cargarPrendas(prendas) {


    
    // Asegurar que el gestor está inicializado
    if (!window.gestorPrendaSinCotizacion) {

        if (typeof window.inicializarGestorSinCotizacion === 'function') {
            window.inicializarGestorSinCotizacion();
        } else if (window.GestorPrendaSinCotizacion) {
            window.gestorPrendaSinCotizacion = new GestorPrendaSinCotizacion();
        }
    }

    prendas.forEach((prenda, index) => {
        try {


            
            // Parsear datos que vienen como strings JSON desde Blade
            let generosConTallas = prenda.generosConTallas;

            
            if (typeof generosConTallas === 'string') {
                try {
                    generosConTallas = JSON.parse(generosConTallas);

                } catch (e) {

                    generosConTallas = {};
                }
            }

            
            // Buscar variantes (nombre correcto desde backend MapearPedidoEdicionService)
            let variantes = prenda.variantes || prenda.variaciones || {};
            if (typeof variantes === 'string') {
                try {
                    variantes = JSON.parse(variantes);
                } catch (e) {

                    variantes = {};
                }
            }
            
            let procesos = prenda.procesos;
            if (typeof procesos === 'string') {
                try {
                    procesos = JSON.parse(procesos);
                } catch (e) {

                    procesos = {};
                }
            }
            
            // Parsear genero si viene como string vacío
            let genero = prenda.genero;
            if (typeof genero === 'string' && (genero === '' || genero === '[]')) {
                genero = [];
            }

            // Contrato estricto de edición: no usar alias legacy ni inferencias.
            let asignacionesColoresPorTalla = prenda.asignacionesColoresPorTalla || {};
            if (typeof asignacionesColoresPorTalla === 'string') {
                try {
                    asignacionesColoresPorTalla = JSON.parse(asignacionesColoresPorTalla);
                } catch (e) {
                    asignacionesColoresPorTalla = {};
                }
            }
            if (!asignacionesColoresPorTalla || typeof asignacionesColoresPorTalla !== 'object' || Array.isArray(asignacionesColoresPorTalla)) {
                asignacionesColoresPorTalla = {};
            }

            let tallaColores = prenda.talla_colores || [];
            if (typeof tallaColores === 'string') {
                try {
                    tallaColores = JSON.parse(tallaColores);
                } catch (e) {
                    tallaColores = [];
                }
            }
            if (!Array.isArray(tallaColores)) {
                tallaColores = [];
            }

            const coloresTelas = Array.isArray(prenda.colores_telas) ? prenda.colores_telas : [];
            const telasAgregadasDesdeFuenteOficial = coloresTelas.map((ct) => {
                const fotosTela = Array.isArray(ct.fotos_tela) ? ct.fotos_tela : [];
                const imagenes = fotosTela.map((f) => ({
                    ruta: f?.url || f?.ruta_original || f?.ruta_webp || '',
                    ruta_original: f?.ruta_original || f?.url || '',
                    ruta_webp: f?.ruta_webp || f?.url || '',
                    prenda_pedido_colores_telas_id: ct?.id || null,
                })).filter((img) => img.ruta || img.ruta_original || img.ruta_webp);

                return {
                    id: ct?.id || null,
                    tela_id: ct?.tela_id || null,
                    color_id: ct?.color_id || null,
                    tela: ct?.tela_nombre || ct?.tela || '',
                    nombre_tela: ct?.tela_nombre || ct?.tela || '',
                    color: ct?.color_nombre || ct?.color || '',
                    referencia: ct?.tela_referencia || ct?.referencia || '',
                    imagenes,
                };
            });

            const tipoFlujoTallas = String(prenda.tipo_flujo_tallas || '').toLowerCase();
            if (!['normal', 'talla_color', 'sin_tallas'].includes(tipoFlujoTallas)) {
                console.error('[cargar-datos-edicion] Contrato inválido: tipo_flujo_tallas ausente o inválido', {
                    prendaId: prenda.id || null,
                    tipo_flujo_tallas: prenda.tipo_flujo_tallas,
                });
            }
            //  Extraer tallas de generosConTallas
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

            
            // Agregar la prenda al gestor con datos correctos
            const datosPrenda = {
                id: prenda.id || null,
                nombre_producto: prenda.nombre_prenda || '',
                nombre_prenda: prenda.nombre_prenda || '',
                descripcion: prenda.descripcion || '',
                genero: genero,
                // cantidad_talla es el formato que usa PrendaCardService._construirTallasYCantidades
                // El backend devuelve generosConTallas: { DAMA: { S: 5, M: 3 } } — misma estructura
                // FIX: Backend envía [] (PHP empty array) cuando no hay tallas — [] es truthy en JS
                cantidad_talla: (Array.isArray(generosConTallas) ? {} : generosConTallas) || {},
                // generosConTallas vacío para que el renderer lo construya desde cantidad_talla
                generosConTallas: {},
                tallas: tallas,
                cantidadesPorTalla: {},
                telas: telasAgregadasDesdeFuenteOficial.length > 0 ? telasAgregadasDesdeFuenteOficial : (prenda.telas || []),
                telasAgregadas: telasAgregadasDesdeFuenteOficial.length > 0 ? telasAgregadasDesdeFuenteOficial : (prenda.telasAgregadas || []),
                colores_telas: coloresTelas,
                fotos: prenda.fotos || [],
                telaFotos: prenda.telaFotos || [],
                imagenes: prenda.imagenes || prenda.fotos || [],
                origen: prenda.origen || 'bodega',
                de_bodega: prenda.de_bodega || 1,
                procesos: procesos,
                talla_colores: Array.isArray(tallaColores) ? tallaColores : [],
                asignacionesColoresPorTalla: (asignacionesColoresPorTalla && typeof asignacionesColoresPorTalla === 'object') ? asignacionesColoresPorTalla : {},
                tipo_flujo_tallas: tipoFlujoTallas,
                variantes: variantes,
                variaciones: variantes,
                tipo_manga: prenda.tipo_manga,
                obs_manga: prenda.obs_manga,
                tipo_broche: prenda.tipo_broche,
                obs_broche: prenda.obs_broche,
                tiene_bolsillos: prenda.tiene_bolsillos,
                obs_bolsillos: prenda.obs_bolsillos,
                tiene_reflectivo: prenda.tiene_reflectivo,
                obs_reflectivo: prenda.obs_reflectivo,
            };
            const prendasIndex = window.gestorPrendaSinCotizacion.agregarPrenda(datosPrenda);

            // Registrar también en gestionItemsUI para renderizado unificado con EPPs
            if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarPrendaAlOrden === 'function') {
                const prendaAlmacenada = window.gestorPrendaSinCotizacion.prendas[prendasIndex];
                if (prendaAlmacenada) {
                    window.gestionItemsUI.agregarPrendaAlOrden(prendaAlmacenada);
                }
            }




        } catch (error) {

        }
    });

    // Renderizar todas las prendas
    try {
        // La función debería estar disponible en init-gestor-sin-cotizacion.js
        // Pero si no está, intentar renderizar directamente
        



        
        if (typeof window.renderizarPrendasSinCotizacion === 'function') {

            window.renderizarPrendasSinCotizacion();

            return;
        }
        
        // Alternativa: Si hay generador de tarjetas, usarlo directamente
        if (typeof window.generarTarjetaPrendaReadOnly === 'function' && window.gestorPrendaSinCotizacion) {

            
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

                return;
            }
        }
        
        //  NUEVO: Esperar a que se carguen los módulos de prenda-tarjeta

        
        function intentarRenderizarPrendas() {
            if (typeof window.generarTarjetaPrendaReadOnly === 'function' && window.gestorPrendaSinCotizacion) {

                
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

                    return true;
                }
            }
            return false;
        }
        
        // Escuchar el evento del loader
        document.addEventListener('prenda-tarjeta-cargado', () => {

            if (!intentarRenderizarPrendas()) {
                // Reintentar en 100ms si aún no está disponible
                setTimeout(intentarRenderizarPrendas, 100);
            }
        }, { once: true });
        
        // Timeout como fallback (en caso que el evento no se dispare)
        setTimeout(() => {
            if (typeof window.generarTarjetaPrendaReadOnly !== 'function') {

            } else {
                intentarRenderizarPrendas();
            }
        }, 3000);
        
    } catch (error) {

    }
}



/**
 * Renderizar items (prendas + EPPs) que ya fueron registrados en gestionItemsUI
 * Usa reintentos porque gestionItemsUI.renderer puede no estar listo aún (scripts defer)
 */
function renderizarItemsRegistrados(intentos = 0) {
    const MAX_INTENTOS = 30;
    
    if (window.gestionItemsUI && window.gestionItemsUI.renderer) {
        const items = window.gestionItemsUI.obtenerItemsOrdenados();
        console.log('[cargar-datos-edicion] renderizarItemsRegistrados - items:', items.length);
        
        if (items.length > 0) {
            window.gestionItemsUI.renderer.actualizar(items);
            console.log('[cargar-datos-edicion] Tarjetas renderizadas correctamente');
        } else {
            console.warn('[cargar-datos-edicion] No hay items para renderizar');
        }
        return;
    }
    
    if (intentos < MAX_INTENTOS) {
        console.log('[cargar-datos-edicion] Esperando gestionItemsUI.renderer... intento', intentos + 1);
        setTimeout(() => renderizarItemsRegistrados(intentos + 1), 200);
    } else {
        console.error('[cargar-datos-edicion] gestionItemsUI.renderer no disponible después de', MAX_INTENTOS, 'intentos');
    }
}

/**
 * Cargar EPPs al pedido
 */
function cargarEPPs(epps) {

    
    try {
        // Asegurar que hay un contenedor de EPPs
        let eppContainer = document.getElementById('epps-container') ||
                          document.querySelector('[data-epps-container]') ||
                          document.querySelector('.epps-items-container');
        
        if (!eppContainer) {

            // Si no existe, intentar encontrar dónde crear el contenedor
            const form = document.querySelector('form');
            if (form) {
                eppContainer = document.createElement('div');
                eppContainer.id = 'epps-container';
                eppContainer.className = 'epps-items-container';
                form.appendChild(eppContainer);
            } else {

                return;
            }
        }
        
        //  IMPORTANTE: Registrar EPPs en gestionItemsUI para que no se pierdan al agregar prendas
        if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarEPPAlOrden === 'function') {
            epps.forEach((epp) => {
                try {
                    window.gestionItemsUI.agregarEPPAlOrden(epp);

                } catch (error) {

                }
            });
        }
        
        // Registrar EPPs en eppAgregadosList e itemsPedido para que el modal de edición pueda encontrarlos
        if (!window.eppAgregadosList) {
            window.eppAgregadosList = [];
        }
        if (!window.itemsPedido) {
            window.itemsPedido = [];
        }
        
        epps.forEach((epp) => {
            const pedidoEppId = epp.pedido_epp_id || epp.pedidoEppId || null;
            const eppCatalogoId = epp.epp_id || epp.id || null;
            const eppNormalizado = {
                id: eppCatalogoId,
                epp_id: eppCatalogoId,
                pedido_epp_id: pedidoEppId,
                tarjetaId: String(pedidoEppId || eppCatalogoId),
                nombre: epp.nombre_epp || epp.nombre_completo || epp.nombre || '',
                nombre_epp: epp.nombre_epp || epp.nombre_completo || epp.nombre || '',
                nombre_completo: epp.nombre_completo || epp.nombre_epp || epp.nombre || '',
                cantidad: epp.cantidad || 0,
                observaciones: epp.observaciones || '',
                imagenes: epp.imagenes || [],
                imagen: '',
                tipo: 'epp'
            };
            
            window.eppAgregadosList.push(eppNormalizado);
            window.itemsPedido.push(eppNormalizado);
        });
        
        console.log('[cargar-datos-edicion] EPPs registrados en eppAgregadosList:', window.eppAgregadosList.length);
        console.log('[cargar-datos-edicion] EPPs registrados en itemsPedido:', window.itemsPedido.length);
        
        // Si el gestor tiene método para agregar EPPs
        if (window.gestorPrendaSinCotizacion && typeof window.gestorPrendaSinCotizacion.agregarEpp === 'function') {
            epps.forEach((epp, index) => {
                try {
                    window.gestorPrendaSinCotizacion.agregarEpp(epp);

                } catch (error) {

                }
            });
            
            // Intentar renderizar EPPs si existe función
            if (typeof window.renderizarEppsSinCotizacion === 'function') {
                window.renderizarEppsSinCotizacion();

            }
        } else {

            
            // Renderizar EPPs directamente
            eppContainer.innerHTML = '';
            epps.forEach((epp, index) => {
                const eppCard = generarTarjetaEpp(epp, index);
                eppContainer.appendChild(eppCard);
            });

        }
        
    } catch (error) {

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
            <div class="epp-title-section">
                <span class="epp-label">EPP ${index + 1}</span>
                <h5>${epp.nombre || epp.nombre_completo || (epp.epp_id ? `EPP #${epp.epp_id}` : 'EPP Desconocido')}</h5>
            </div>
            
            <div class="epp-menu-contextual">
                <button class="btn-menu-tres-puntos-epp" type="button" data-epp-index="${index}">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="submenu-epp" style="display: none;">
                    <button class="submenu-option btn-editar-epp" type="button" data-epp-index="${index}">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="submenu-option btn-eliminar-epp" type="button" data-epp-index="${index}">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
        
        <div class="epp-content">
            <div class="epp-info">
                <span class="epp-qty"><strong>Cantidad:</strong> ${epp.cantidad || 0}</span>
                ${epp.observaciones ? `<p class="epp-observations"><strong>Observaciones:</strong> ${epp.observaciones}</p>` : ''}
            </div>
        </div>
    `;
    return card;
}

/**
 * Inicializar event listeners para el menú de EPP
 */
function inicializarEventListenersEpp() {
    // Deshabilitado: este listener era legacy y competia con la arquitectura actual.
    // Fuente unica de verdad:
    // - EppMenuHandlerBase / EppMenuHandlerTarjeta / EppMenuHandlerTabla
    // - gestionItemsUI (estado)
    // - ItemRenderer (UI)
    console.log('[cargar-datos-edicion-nuevo] inicializarEventListenersEpp() deshabilitado (legacy)');
}

// Escuchar evento de prenda actualizada (desde modal-novedad-edicion.js)
// Este evento se dispara después de guardar cambios en una prenda editada
window.addEventListener('prendaActualizada', (event) => {
    console.log('[cargar-datos-edicion-nuevo]  Evento prendaActualizada recibido:', event.detail);
    
    try {
        const { pedidoId, prendaId } = event.detail;
        
        // Preservar los EPPs actuales ANTES de actualizar
        const eppsPersistentes = window.eppsPedido ? JSON.parse(JSON.stringify(window.eppsPedido)) : [];
        console.log('[cargar-datos-edicion-nuevo]  EPPs a preservar:', eppsPersistentes.length);
        
        // Recargar los datos del pedido para obtener la prenda actualizada
        if (pedidoId && window.datosEdicionPedido) {
            // Actualizar prendasEdicion con los datos del servidor
            // Esto asegura que tenemos la prenda actualizada
            if (window.prendasEdicion && typeof window.prendasEdicion === 'object') {
                console.log('[cargar-datos-edicion-nuevo]  Actualizando prendasEdicion desde servidor...');
                
                // Re-renderizar las prendas (se cargarán desde window.datosEdicionPedido)
                if (typeof window.renderizarPrendasSinCotizacion === 'function') {
                    window.renderizarPrendasSinCotizacion();
                    console.log('[cargar-datos-edicion-nuevo]  Prendas re-renderizadas');
                } else {
                    console.warn('[cargar-datos-edicion-nuevo]  renderizarPrendasSinCotizacion no disponible');
                }
            }
        }
        
        // Re-renderizar EPPs manteniéndolos intactos
        if (eppsPersistentes.length > 0) {
            console.log('[cargar-datos-edicion-nuevo]  Re-renderizando EPPs (preservados)...');
            
            // Restaurar los EPPs
            window.eppsPedido = eppsPersistentes;
            
            // Re-renderizar
            if (typeof window.renderizarEppsSinCotizacion === 'function') {
                window.renderizarEppsSinCotizacion();
                console.log('[cargar-datos-edicion-nuevo]  EPPs re-renderizados correctamente');
            } else {
                console.warn('[cargar-datos-edicion-nuevo]  renderizarEppsSinCotizacion no disponible');
            }
        }
        
        console.log('[cargar-datos-edicion-nuevo]  Actualización completada sin perder items');
    } catch (error) {
        console.error('[cargar-datos-edicion-nuevo]  Error al procesar prendaActualizada:', error);
    }
});

// Inicializar event listeners cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    inicializarEventListenersEpp();
});


