/**
 * SCRIPT DE PRUEBA RÁPIDA - COTIZACIONES
 * Llena el formulario automáticamente y envía la cotización
 * Solo necesitas hacer clic en un botón
 */

console.log('🔵 Script test-rapido.js cargado correctamente');

function llenarFormularioRapido() {
    console.log('%c🚀 INICIANDO LLENADO RÁPIDO DEL FORMULARIO', 'color: orange; font-size: 14px; font-weight: bold;');

    // PASO 1: Llenar cliente
    const clienteInput = document.getElementById('cliente');
    if (clienteInput) {
        clienteInput.value = 'CLIENTE PRUEBA ' + Date.now();
        clienteInput.dispatchEvent(new Event('input', { bubbles: true }));
        clienteInput.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('✅ Cliente llenado:', clienteInput.value);
    } else {
        console.error('❌ No se encontró #cliente');
    }

    // PASO 2: Llenar tipo de cotización
    const tipoCotizacion = document.getElementById('tipo_cotizacion');
    if (tipoCotizacion) {
        tipoCotizacion.value = 'M';
        tipoCotizacion.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('✅ Tipo de cotización: M');
    } else {
        console.error('❌ No se encontró #tipo_cotizacion');
    }

    // PASO 3: Agregar producto
    console.log('📝 Buscando botón flotante...');
    const btnFlotante = document.getElementById('btnFlotante');
    if (btnFlotante) {
        console.log('✅ Botón flotante encontrado');
        // Mostrar menú
        const menu = document.getElementById('menuFlotante');
        if (menu) {
            menu.style.display = 'block';
            btnFlotante.style.transform = 'scale(1) rotate(45deg)';
            console.log('✅ Menú flotante mostrado');
            
            // Esperar y hacer clic en "Agregar Prenda"
            setTimeout(() => {
                const btnAgregarPrenda = menu.querySelector('button:first-child');
                if (btnAgregarPrenda) {
                    console.log('✅ Botón "Agregar Prenda" encontrado, haciendo clic...');
                    btnAgregarPrenda.click();
                    console.log('✅ Prenda agregada');
                    
                    // Esperar a que se cree el producto
                    setTimeout(() => {
                        console.log('📝 Llenando datos del producto...');
                        llenarProducto();
                    }, 500);
                } else {
                    console.error('❌ No se encontró botón "Agregar Prenda"');
                }
            }, 200);
        } else {
            console.error('❌ No se encontró #menuFlotante');
        }
    } else {
        console.error('❌ No se encontró #btnFlotante');
    }
}

function llenarProducto() {
    console.log('📝 Llenando datos del producto...');

    const productoCard = document.querySelector('.producto-card');
    if (!productoCard) {
        console.error('❌ No se encontró producto-card');
        return;
    }

    // Expandir producto si está colapsado
    const btnToggle = productoCard.querySelector('.btn-toggle-product');
    if (btnToggle && productoCard.querySelector('.producto-body').style.display === 'none') {
        btnToggle.click();
        console.log('✅ Producto expandido');
    }

    // Nombre de prenda
    const inputNombre = productoCard.querySelector('input[name*="nombre_producto"]');
    if (inputNombre) {
        inputNombre.value = 'CAMISA DRILL';
        inputNombre.dispatchEvent(new Event('input', { bubbles: true }));
        inputNombre.dispatchEvent(new Event('change', { bubbles: true }));
        inputNombre.dispatchEvent(new Event('keyup', { bubbles: true }));
        inputNombre.dispatchEvent(new Event('blur', { bubbles: true }));
        console.log('✅ Nombre: CAMISA DRILL');
        console.log('   Valor en input:', inputNombre.value);
    } else {
        console.error('❌ No se encontró input[name*="nombre_producto"]');
    }

    // Descripción
    const textareaDesc = productoCard.querySelector('textarea[name*="descripcion"]');
    if (textareaDesc) {
        textareaDesc.value = 'Camisa drill con bordado en pecho y espalda, manga larga, con reflectivo gris';
        console.log('✅ Descripción agregada');
    }

    // Seleccionar tallas
    setTimeout(() => {
        const tallas = ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
        console.log('📏 Buscando botones de tallas...');
        
        // Buscar todos los botones de talla
        const tallaBtns = productoCard.querySelectorAll('[data-talla]');
        console.log(`   Encontrados ${tallaBtns.length} botones de talla`);
        
        if (tallaBtns.length === 0) {
            console.error('❌ No se encontraron botones de talla. Buscando alternativas...');
            // Buscar por clase
            const btnsPorClase = productoCard.querySelectorAll('.talla-btn');
            console.log(`   Encontrados ${btnsPorClase.length} elementos con clase .talla-btn`);
        }
        
        tallas.forEach(talla => {
            const btn = productoCard.querySelector(`.talla-btn[data-talla="${talla}"]`);
            if (btn) {
                if (!btn.classList.contains('selected')) {
                    btn.click();
                    console.log(`✅ Talla ${talla} seleccionada`);
                } else {
                    console.log(`⚠️ Talla ${talla} ya estaba seleccionada`);
                }
            } else {
                console.warn(`⚠️ No se encontró botón para talla: ${talla}`);
            }
        });

        // Llenar color
        setTimeout(() => {
            const colorInput = productoCard.querySelector('.color-input');
            if (colorInput) {
                colorInput.value = 'Naranja';
                colorInput.dispatchEvent(new Event('input', { bubbles: true }));
                console.log('✅ Color: Naranja');
            }

            // Llenar tela
            setTimeout(() => {
                const telaInput = productoCard.querySelector('.tela-input');
                if (telaInput) {
                    telaInput.value = 'DRILL BORNEO';
                    telaInput.dispatchEvent(new Event('input', { bubbles: true }));
                    console.log('✅ Tela: DRILL BORNEO');
                }

                // Llenar manga
                setTimeout(() => {
                    const mangaCheckbox = productoCard.querySelector('input[name*="aplica_manga"]');
                    if (mangaCheckbox && !mangaCheckbox.checked) {
                        mangaCheckbox.click();
                        console.log('✅ Manga checkbox activado');
                    }

                    const mangaInput = productoCard.querySelector('.manga-input');
                    if (mangaInput) {
                        mangaInput.disabled = false;
                        mangaInput.style.opacity = '1';
                        mangaInput.style.pointerEvents = 'auto';
                        mangaInput.value = 'Larga';
                        mangaInput.dispatchEvent(new Event('input', { bubbles: true }));
                        console.log('✅ Manga: Larga');
                    }

                    // Llenar reflectivo
                    setTimeout(() => {
                        const reflectivoCheckbox = productoCard.querySelector('input[name*="aplica_reflectivo"]');
                        if (reflectivoCheckbox && !reflectivoCheckbox.checked) {
                            reflectivoCheckbox.click();
                            console.log('✅ Reflectivo checkbox activado');
                        }

                        const reflectivoInput = productoCard.querySelector('input[name*="obs_reflectivo"]');
                        if (reflectivoInput) {
                            reflectivoInput.value = 'Gris 2" en pecho y espalda';
                            console.log('✅ Reflectivo: Gris 2" en pecho y espalda');
                        }

                        console.log('✅ Producto completamente llenado');
                        console.log('🎯 Ahora puedes hacer clic en SIGUIENTE para continuar');
                    }, 300);
                }, 300);
            }, 300);
        }, 300);
    }, 500);
}

function enviarCotizacionRapida() {
    console.log('📤 Enviando cotización rápida...');

    // Ir al paso 4 (revisar)
    irAlPaso(4);

    // Esperar a que cargue el paso 4
    setTimeout(() => {
        // Hacer clic en "ENVIAR"
        const btnEnviar = document.querySelector('button[onclick*="enviarCotizacion"]');
        if (btnEnviar) {
            btnEnviar.click();
            console.log('✅ Cotización enviada');
        } else {
            console.error('❌ No se encontró botón ENVIAR');
        }
    }, 500);
}

// Crear botón flotante para pruebas
function crearBotonesPrueba() {
    // Verificar si ya existen
    if (document.getElementById('botonesPrueba')) {
        console.log('⚠️ Botones ya existen');
        return;
    }

    const container = document.createElement('div');
    container.id = 'botonesPrueba';
    container.style.cssText = `
        position: fixed;
        bottom: 100px;
        left: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    `;

    // Botón 1: Llenar formulario
    const btn1 = document.createElement('button');
    btn1.textContent = '⚡ Llenar Formulario';
    btn1.style.cssText = `
        padding: 12px 16px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        transition: all 0.3s;
    `;
    btn1.onmouseover = () => btn1.style.boxShadow = '0 6px 20px rgba(16, 185, 129, 0.6)';
    btn1.onmouseout = () => btn1.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.4)';
    btn1.onclick = llenarFormularioRapido;

    // Botón 2: Enviar cotización
    const btn2 = document.createElement('button');
    btn2.textContent = '📤 Enviar Cotización';
    btn2.style.cssText = `
        padding: 12px 16px;
        background: #0066cc;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        box-shadow: 0 4px 12px rgba(0, 102, 204, 0.4);
        transition: all 0.3s;
    `;
    btn2.onmouseover = () => btn2.style.boxShadow = '0 6px 20px rgba(0, 102, 204, 0.6)';
    btn2.onmouseout = () => btn2.style.boxShadow = '0 4px 12px rgba(0, 102, 204, 0.4)';
    btn2.onclick = enviarCotizacionRapida;

    // Botón 3: Limpiar
    const btn3 = document.createElement('button');
    btn3.textContent = '🗑️ Limpiar';
    btn3.style.cssText = `
        padding: 12px 16px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        transition: all 0.3s;
    `;
    btn3.onmouseover = () => btn3.style.boxShadow = '0 6px 20px rgba(239, 68, 68, 0.6)';
    btn3.onmouseout = () => btn3.style.boxShadow = '0 4px 12px rgba(239, 68, 68, 0.4)';
    btn3.onclick = () => {
        document.getElementById('formCrearPedidoFriendly').reset();
        document.getElementById('productosContainer').innerHTML = '';
        console.log('✅ Formulario limpiado');
    };

    container.appendChild(btn1);
    container.appendChild(btn2);
    container.appendChild(btn3);

    document.body.appendChild(container);
    console.log('%c✅ BOTONES DE PRUEBA CREADOS', 'color: green; font-size: 16px; font-weight: bold;');
    console.log('%cMira en la esquina inferior izquierda de la pantalla', 'color: blue; font-size: 14px;');
}

// Crear botones cuando el DOM esté listo
function inicializarBotones() {
    if (document.body) {
        crearBotonesPrueba();
    } else {
        setTimeout(inicializarBotones, 100);
    }
}

// Intentar crear botones inmediatamente
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarBotones);
} else {
    inicializarBotones();
}

// También intentar después de un tiempo
setTimeout(inicializarBotones, 500);
setTimeout(inicializarBotones, 2000);
