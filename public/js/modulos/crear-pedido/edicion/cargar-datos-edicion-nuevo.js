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

            
            let variaciones = prenda.variaciones;
            if (typeof variaciones === 'string') {
                try {
                    variaciones = JSON.parse(variaciones);
                } catch (e) {

                    variaciones = {};
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
            const prendasIndex = window.gestorPrendaSinCotizacion.agregarPrenda({
                nombre_producto: prenda.nombre_prenda || '',
                descripcion: prenda.descripcion || '',
                genero: genero,
                generosConTallas: generosConTallas,
                tallas: tallas,  //  Pasar tallas extraídas
                cantidadesPorTalla: prenda.cantidadesPorTalla || {},
                telas: prenda.telas || [],
                telasAgregadas: prenda.telasAgregadas || [],
                fotos: prenda.fotos || [],
                telaFotos: prenda.telaFotos || [],
                imagenes: prenda.imagenes || prenda.fotos || [],  //  Asegurar imagenes
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
                <h5>${epp.nombre || 'EPP Desconocido'}</h5>
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
    // Menú de 3 puntos
    document.addEventListener('click', (e) => {
        // Toggle del menú
        if (e.target.closest('.btn-menu-tres-puntos-epp')) {
            e.stopPropagation();
            const btn = e.target.closest('.btn-menu-tres-puntos-epp');
            const submenu = btn.nextElementSibling;
            
            // Cerrar otros submenús
            document.querySelectorAll('.submenu-epp').forEach(menu => {
                if (menu !== submenu) menu.style.display = 'none';
            });
            
            // Toggle del menú actual
            submenu.style.display = submenu.style.display === 'none' ? 'flex' : 'none';
        }
        // Cerrar menú si se hace clic fuera
        else {
            document.querySelectorAll('.submenu-epp').forEach(menu => {
                menu.style.display = 'none';
            });
        }
        
        // Botón EDITAR EPP
        if (e.target.closest('.btn-editar-epp')) {
            e.stopPropagation();
            const btn = e.target.closest('.btn-editar-epp');
            const eppIndex = parseInt(btn.dataset.eppIndex);
            
            console.log('[EPP] 📝 Editando EPP con índice:', eppIndex);
            
            // Obtener EPP desde window.eppsPedido (donde se almacenan)
            if (window.eppsPedido && window.eppsPedido[eppIndex]) {
                const epp = window.eppsPedido[eppIndex];
                console.log('[EPP] 📋 EPP encontrado:', epp);
                
                // TODO: Abrir modal para editar EPP
                // Por ahora solo log
                alert(`Editar EPP: ${epp.nombre} (ID: ${epp.id || 'nuevo'})`);
            } else {
                console.warn('[EPP]  EPP no encontrado en índice:', eppIndex);
                alert('EPP no encontrado');
            }
        }
        
        // Botón ELIMINAR EPP
        if (e.target.closest('.btn-eliminar-epp')) {
            e.stopPropagation();
            const btn = e.target.closest('.btn-eliminar-epp');
            const eppIndex = parseInt(btn.dataset.eppIndex);
            
            console.log('[EPP] 🗑️ Eliminando EPP con índice:', eppIndex);
            
            // Confirmar eliminación
            if (confirm('¿Estás seguro de que deseas eliminar este EPP?')) {
                // Obtener EPP desde window.eppsPedido
                if (window.eppsPedido) {
                    // Eliminar del array
                    window.eppsPedido.splice(eppIndex, 1);
                    console.log('[EPP] EPP eliminado. Eppspedido ahora:', window.eppsPedido);
                    
                    // Recargar la lista de EPPs
                    cargarEPPs(window.eppsPedido);
                } else {
                    alert('Error: No se puede eliminar el EPP');
                }
            }
        }
    });
}

// Inicializar event listeners cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    inicializarEventListenersEpp();
});


