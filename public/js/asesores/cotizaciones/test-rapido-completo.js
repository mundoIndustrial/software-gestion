/**
 * SCRIPT DE PRUEBA RÁPIDA COMPLETO - COTIZACIONES
 * Llena TODOS los campos del formulario create-friendly.blade.php
 * Solo necesitas hacer clic en un botón
 */

console.log('🔵 Script test-rapido-completo.js cargado correctamente');

// ============ PASO 1: CLIENTE ============
function llenarPaso1() {
    console.log('%c📋 PASO 1: LLENANDO CLIENTE', 'color: #3498db; font-size: 12px; font-weight: bold;');
    
    const clienteInput = document.getElementById('cliente');
    if (clienteInput) {
        clienteInput.value = 'CLIENTE PRUEBA ' + Date.now();
        clienteInput.dispatchEvent(new Event('input', { bubbles: true }));
        clienteInput.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('✅ Cliente:', clienteInput.value);
    } else {
        console.error('❌ No se encontró #cliente');
    }
    
    // Ir al paso 2
    setTimeout(() => irAlPaso(2), 500);
}

// ============ PASO 2: PRENDAS ============
function llenarPaso2() {
    console.log('%c📦 PASO 2: LLENANDO PRENDAS', 'color: #3498db; font-size: 12px; font-weight: bold;');
    
    // Activar paso 2
    const btnAplica = document.getElementById('btnAplicaPaso2');
    if (btnAplica && btnAplica.textContent === 'APLICA') {
        btnAplica.click();
        console.log('✅ Paso 2 activado');
    }
    
    // Esperar a que se cree el contenedor
    setTimeout(() => {
        // Agregar prenda
        const btnFlotante = document.getElementById('btnFlotante');
        if (btnFlotante) {
            const menu = document.getElementById('menuFlotante');
            if (menu) {
                menu.style.display = 'block';
                btnFlotante.style.transform = 'scale(1) rotate(45deg)';
                
                setTimeout(() => {
                    const btnAgregarPrenda = menu.querySelector('button:first-child');
                    if (btnAgregarPrenda) {
                        btnAgregarPrenda.click();
                        console.log('✅ Prenda agregada');
                        
                        setTimeout(() => {
                            llenarDatosPrenda();
                        }, 500);
                    }
                }, 200);
            }
        }
    }, 300);
}

// ============ LLENAR DATOS DE PRENDA ============
function llenarDatosPrenda() {
    console.log('%c👕 LLENANDO DATOS DE PRENDA', 'color: #9b59b6; font-size: 12px; font-weight: bold;');
    
    const productoCard = document.querySelector('.producto-card');
    if (!productoCard) {
        console.error('❌ No se encontró .producto-card');
        return;
    }
    
    console.log('📍 Producto card encontrado');
    
    // Expandir si está colapsado
    const btnToggle = productoCard.querySelector('.btn-toggle-product');
    const productoBody = productoCard.querySelector('.producto-body');
    if (btnToggle && productoBody && productoBody.style.display === 'none') {
        btnToggle.click();
        console.log('✅ Producto expandido');
    }
    
    // 1. Nombre de prenda
    console.log('🔍 Buscando input nombre...');
    const inputNombre = productoCard.querySelector('input[name*="nombre_producto"]');
    if (inputNombre) {
        inputNombre.value = 'CAMISA DRILL';
        inputNombre.dispatchEvent(new Event('input', { bubbles: true }));
        inputNombre.dispatchEvent(new Event('change', { bubbles: true }));
        inputNombre.dispatchEvent(new Event('keyup', { bubbles: true }));
        console.log('✅ Nombre: CAMISA DRILL');
    } else {
        console.error('❌ No se encontró input nombre');
    }
    
    // 2. Descripción
    console.log('🔍 Buscando textarea descripción...');
    const textareaDesc = productoCard.querySelector('textarea[name*="descripcion"]');
    if (textareaDesc) {
        textareaDesc.value = 'Camisa drill con bordado en pecho y espalda, manga larga, con reflectivo gris 2" de 25 ciclos';
        textareaDesc.dispatchEvent(new Event('input', { bubbles: true }));
        console.log('✅ Descripción agregada');
    } else {
        console.error('❌ No se encontró textarea descripción');
    }
    
    // 3. Seleccionar tallas
    setTimeout(() => {
        console.log('📏 Seleccionando tallas...');
        const tallas = ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
        
        // Buscar botones de talla
        const tallaBtns = productoCard.querySelectorAll('[data-talla]');
        console.log(`   Encontrados ${tallaBtns.length} botones de talla`);
        
        let tallasSeleccionadas = 0;
        tallas.forEach(talla => {
            const btn = productoCard.querySelector(`.talla-btn[data-talla="${talla}"]`);
            if (btn && !btn.classList.contains('selected')) {
                btn.click();
                tallasSeleccionadas++;
                console.log(`   ✅ Talla ${talla} seleccionada`);
            }
        });
        
        if (tallasSeleccionadas === 0) {
            console.warn('⚠️ No se seleccionaron tallas');
        }
        
        // 4. Color
        setTimeout(() => {
            console.log('🎨 Llenando color...');
            const colorInput = productoCard.querySelector('.color-input');
            if (colorInput) {
                colorInput.value = 'Naranja';
                colorInput.dispatchEvent(new Event('input', { bubbles: true }));
                colorInput.dispatchEvent(new Event('change', { bubbles: true }));
                console.log('✅ Color: Naranja');
            } else {
                console.warn('⚠️ No se encontró .color-input');
            }
            
            // 5. Tela
            setTimeout(() => {
                console.log('🧵 Llenando tela...');
                const telaInput = productoCard.querySelector('.tela-input');
                if (telaInput) {
                    telaInput.value = 'DRILL BORNEO';
                    telaInput.dispatchEvent(new Event('input', { bubbles: true }));
                    telaInput.dispatchEvent(new Event('change', { bubbles: true }));
                    console.log('✅ Tela: DRILL BORNEO');
                } else {
                    console.warn('⚠️ No se encontró .tela-input');
                }
                
                // 6. Manga
                setTimeout(() => {
                    console.log('👕 Llenando manga...');
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
                    
                    // 7. Reflectivo
                    setTimeout(() => {
                        console.log('✨ Llenando reflectivo...');
                        const reflectivoCheckbox = productoCard.querySelector('input[name*="aplica_reflectivo"]');
                        if (reflectivoCheckbox && !reflectivoCheckbox.checked) {
                            reflectivoCheckbox.click();
                            console.log('✅ Reflectivo checkbox activado');
                        }
                        
                        const reflectivoInput = productoCard.querySelector('input[name*="obs_reflectivo"]');
                        if (reflectivoInput) {
                            reflectivoInput.value = 'Gris 2" en pecho y espalda';
                            reflectivoInput.dispatchEvent(new Event('input', { bubbles: true }));
                            console.log('✅ Reflectivo: Gris 2" en pecho y espalda');
                        }
                        
                        console.log('%c✅ PRENDA COMPLETAMENTE LLENADA', 'color: green; font-size: 12px; font-weight: bold;');
                        
                        // Verificar que los datos se capturaron correctamente
                        console.log('🔍 Verificando datos capturados:');
                        console.log('   Color input:', colorInput?.value);
                        console.log('   Tela input:', telaInput?.value);
                        console.log('   Manga checkbox:', mangaCheckbox?.checked);
                        console.log('   Manga input:', mangaInput?.value);
                        console.log('   Reflectivo checkbox:', reflectivoCheckbox?.checked);
                        console.log('   Reflectivo input:', reflectivoInput?.value);
                        
                        // Ir al paso 3
                        setTimeout(() => irAlPaso(3), 500);
                    }, 300);
                }, 300);
            }, 300);
        }, 300);
    }, 500);
}

// ============ PASO 3: BORDADO/ESTAMPADO ============
function llenarPaso3() {
    console.log('%c🎨 PASO 3: LLENANDO BORDADO/ESTAMPADO', 'color: #3498db; font-size: 12px; font-weight: bold;');
    
    // Activar paso 3
    const btnAplica = document.getElementById('btnAplicaPaso3');
    if (btnAplica && btnAplica.textContent === 'APLICA') {
        btnAplica.click();
        console.log('✅ Paso 3 activado');
    }
    
    setTimeout(() => {
        // Agregar técnica de bordado
        const btnAgregarTecnica = document.querySelector('[onclick*="agregarTecnica"]');
        if (btnAgregarTecnica) {
            btnAgregarTecnica.click();
            console.log('✅ Técnica agregada');
            
            setTimeout(() => {
                // Llenar datos de técnica
                const tecnicaRow = document.querySelector('.tecnica-row');
                if (tecnicaRow) {
                    const inputs = tecnicaRow.querySelectorAll('input, select, textarea');
                    if (inputs.length > 0) {
                        inputs[0].value = 'Bordado pecho';
                        inputs[0].dispatchEvent(new Event('input', { bubbles: true }));
                        console.log('✅ Técnica: Bordado pecho');
                    }
                }
                
                // Ir al paso 4
                setTimeout(() => irAlPaso(4), 500);
            }, 300);
        } else {
            // Si no hay botón, ir directamente al paso 4
            setTimeout(() => irAlPaso(4), 500);
        }
    }, 300);
}

// ============ PASO 4: REVISAR Y ENVIAR ============
function llenarPaso4() {
    console.log('%c✅ PASO 4: REVISAR Y ENVIAR', 'color: #3498db; font-size: 12px; font-weight: bold;');
    console.log('📝 Formulario completamente llenado. Ahora puedes:');
    console.log('   1. Hacer clic en "GUARDAR" para guardar como borrador');
    console.log('   2. Hacer clic en "ENVIAR" para enviar la cotización');
}

// ============ FLUJO COMPLETO ============
function llenarFormularioCompleto() {
    console.log('%c🚀 INICIANDO LLENADO COMPLETO DEL FORMULARIO', 'color: orange; font-size: 14px; font-weight: bold;');
    console.log('Este script llenará TODOS los campos automáticamente\n');
    
    llenarPaso1();
    
    // Esperar a que se complete cada paso
    setTimeout(() => {
        console.log('\n');
        llenarPaso2();
    }, 1500);
    
    setTimeout(() => {
        console.log('\n');
        llenarPaso3();
    }, 4000);
    
    setTimeout(() => {
        console.log('\n');
        llenarPaso4();
    }, 6000);
}

// ============ CREAR BOTONES DE PRUEBA ============
function crearBotonesPrueba() {
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

    // Botón 1: Llenar todo
    const btn1 = document.createElement('button');
    btn1.textContent = '⚡ Llenar TODO';
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
    btn1.onclick = llenarFormularioCompleto;

    // Botón 2: Limpiar
    const btn2 = document.createElement('button');
    btn2.textContent = '🗑️ Limpiar';
    btn2.style.cssText = `
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
    btn2.onmouseover = () => btn2.style.boxShadow = '0 6px 20px rgba(239, 68, 68, 0.6)';
    btn2.onmouseout = () => btn2.style.boxShadow = '0 4px 12px rgba(239, 68, 68, 0.4)';
    btn2.onclick = () => {
        document.getElementById('formCrearPedidoFriendly').reset();
        document.getElementById('productosContainer').innerHTML = '';
        console.log('✅ Formulario limpiado');
    };

    container.appendChild(btn1);
    container.appendChild(btn2);

    document.body.appendChild(container);
    console.log('%c✅ BOTONES DE PRUEBA CREADOS', 'color: green; font-size: 16px; font-weight: bold;');
    console.log('%cMira en la esquina inferior izquierda de la pantalla', 'color: blue; font-size: 14px;');
}

// ============ INICIALIZAR ============
function inicializarBotones() {
    if (document.body) {
        crearBotonesPrueba();
    } else {
        setTimeout(inicializarBotones, 100);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarBotones);
} else {
    inicializarBotones();
}

setTimeout(inicializarBotones, 500);
setTimeout(inicializarBotones, 2000);
