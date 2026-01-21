/**
 * SCRIPT DE DEBUG CRÍTICO - Problema de Tarjeta No Renderizada
 * 
 * Este script identifica POR QUÉ la tarjeta no aparece después de agregar prenda
 * 
 * USO: Copia todo y pega en la consola (F12 → Console)
 */

console.log(' ========== DEBUG CRÍTICO: TARJETA NO RENDERIZA ==========\n');

// ============================================
//  VERIFICAR COMPONENTES BÁSICOS
// ============================================
console.log(' Verificando componentes básicos...\n');

const componentes = {
    gestionItemsUI: typeof window.gestionItemsUI !== 'undefined',
    gestorPrendaSinCotizacion: typeof window.gestorPrendaSinCotizacion !== 'undefined',
    renderizarPrendas: typeof window.renderizarPrendasTipoPrendaSinCotizacion === 'function',
    obtenerProcesos: typeof window.obtenerProcesosConfigurables === 'function'
};

Object.entries(componentes).forEach(([nombre, existe]) => {
    console.log(`   ${existe ? '' : ''} ${nombre}`);
});

// ============================================
//  VERIFICAR ESTADO DEL GESTOR
// ============================================
console.log('\n Estado del Gestor:\n');

if (window.gestorPrendaSinCotizacion) {
    const gestor = window.gestorPrendaSinCotizacion;
    console.log(`    Prendas en gestor.prendas: ${gestor.prendas.length}`);
    console.log(`    Prendas activas: ${gestor.obtenerActivas().length}`);
    console.log(`    Prendas eliminadas: ${Array.from(gestor.prendasEliminadas).join(', ') || '[ninguna]'}`);
    
    if (gestor.prendas.length > 0) {
        console.log('\n   Detalles de prendas:');
        gestor.prendas.forEach((prenda, idx) => {
            const estado = gestor.prendasEliminadas.has(idx) ? ' (eliminada)' : '';
            console.log(`   ${estado} Prenda ${idx}: "${prenda.nombre_producto}"`);
            console.log(`       - Procesos: ${Object.keys(prenda.procesos || {}).length > 0 ? Object.keys(prenda.procesos).join(', ') : '[ninguno]'}`);
        });
    }
} else {
    console.error(' GestorPrendaSinCotizacion no existe');
}

// ============================================
//  VERIFICAR CONTAINER EN DOM
// ============================================
console.log('\n Verificar Container en DOM:\n');

const containerID = 'prendas-container-editable';
const container = document.getElementById(containerID);

if (container) {
    console.log(`    Container encontrado: #${containerID}`);
    console.log(`    Contenido HTML actual:`);
    console.log(`       Longitud: ${container.innerHTML.length} caracteres`);
    console.log(`       ¿Vacío? ${container.innerHTML.trim() === '' ? ' SÍ' : ' NO'}`);
    
    // Contar elementos dentro
    const tarjetas = container.querySelectorAll('.prenda-card-editable');
    console.log(`    Tarjetas renderizadas: ${tarjetas.length}`);
} else {
    console.error(`    Container NO encontrado: #${containerID}`);
    console.log('\n   Buscando containers alternativos...');
    document.querySelectorAll('[id*="container"], [id*="items"], [id*="prendas"]').forEach(el => {
        console.log(`   - ${el.id} (${el.tagName})`);
    });
}

// ============================================
// 4️⃣ VERIFICAR PROCESOS SELECCIONADOS
// ============================================
console.log('\n4️⃣ Procesos Seleccionados:\n');

if (typeof window.procesosSeleccionados !== 'undefined') {
    console.log(`   Procesos: ${Object.keys(window.procesosSeleccionados).length > 0 ? Object.keys(window.procesosSeleccionados).join(', ') : ' [vacío]'}`);
    console.log(`   Contenido completo:`, window.procesosSeleccionados);
} else {
    console.error('    window.procesosSeleccionados no existe');
}

// ============================================
// 5️⃣ FUNCIÓN PARA AGREGAR PRENDA DE PRUEBA
// ============================================
console.log('\n5️⃣ Función de Prueba Rápida:\n');

window.debugAgregarPrendaDePrueba = function() {
    console.log('🧪 Iniciando prueba de agregar prenda...\n');
    
    if (!window.gestorPrendaSinCotizacion) {
        console.error(' Gestor no existe');
        return;
    }
    
    // Crear prenda de prueba
    const prendaPrueba = {
        nombre_producto: 'PRENDA DE PRUEBA',
        descripcion: 'Creada para debugging',
        genero: 'dama',
        origen: 'bodega',
        imagenes: [],
        telas: [],
        tallas: [{
            genero: 'dama',
            tallas: ['S', 'M', 'L'],
            tipo: 'simple'
        }],
        variaciones: {},
        procesos: { reflectivo: { tipo: 'reflectivo', datos: { tipo: 'test' } } },
        cantidadesPorTalla: {}
    };
    
    console.log('   Agregando prenda de prueba al gestor...');
    const indice = window.gestorPrendaSinCotizacion.agregarPrenda(prendaPrueba);
    
    console.log(`    Prenda agregada en índice: ${indice}`);
    console.log(`    Prendas activas ahora: ${window.gestorPrendaSinCotizacion.obtenerActivas().length}`);
    
    // Intentar renderizar
    console.log('   Intentando renderizar...');
    if (typeof window.renderizarPrendasTipoPrendaSinCotizacion === 'function') {
        window.renderizarPrendasTipoPrendaSinCotizacion();
        console.log('    Renderizado ejecutado');
        
        // Verificar resultado
        setTimeout(() => {
            const container = document.getElementById('prendas-container-editable');
            if (container) {
                const tarjetas = container.querySelectorAll('.prenda-card-editable');
                console.log(`    Resultado: ${tarjetas.length} tarjetas renderizadas`);
            }
        }, 200);
    } else {
        console.error('    Función de renderizado no encontrada');
    }
};

console.log('   Ejecuta: debugAgregarPrendaDePrueba()');

// ============================================
// 6️⃣ FUNCIÓN PARA DIAGNOSTICAR PROBLEMA
// ============================================
console.log('\n6️⃣ Función de Diagnóstico Completo:\n');

window.debugDiagnosticoCompleto = function() {
    console.log(' ========== DIAGNÓSTICO COMPLETO ==========\n');
    
    const gestor = window.gestorPrendaSinCotizacion;
    if (!gestor) {
        console.error(' Gestor no existe');
        return;
    }
    
    console.log(' ESTADO ACTUAL:');
    console.log(`   - Prendas totales: ${gestor.prendas.length}`);
    console.log(`   - Prendas activas: ${gestor.obtenerActivas().length}`);
    console.log(`   - Prendas eliminadas: ${Array.from(gestor.prendasEliminadas).length}`);
    
    console.log('\n ANÁLISIS:');
    
    if (gestor.prendas.length === 0) {
        console.log('    PROBLEMA 1: No hay PRENDAS EN EL GESTOR');
        console.log('      → Prenda no se agregó correctamente');
        console.log('      → Revisa agregarPrendaNueva() en gestion-items-pedido.js');
    } else if (gestor.obtenerActivas().length === 0) {
        console.log('    PROBLEMA 2: Todas las prendas están ELIMINADAS');
        console.log(`      → Prendas eliminadas: ${Array.from(gestor.prendasEliminadas).join(', ')}`);
        console.log('      → Algo está llamando a gestor.eliminar()');
    } else {
        console.log('    Hay prendas activas en el gestor');
        
        // Verificar container
        const container = document.getElementById('prendas-container-editable');
        if (!container) {
            console.log('    PROBLEMA 3: Container NO EXISTE');
            console.log('      → ID esperado: prendas-container-editable');
            console.log('      → Revisa el HTML de la página');
        } else {
            console.log('    Container existe en DOM');
            
            const tarjetas = container.querySelectorAll('.prenda-card-editable');
            if (tarjetas.length === 0) {
                console.log('    PROBLEMA 4: Container VACÍO - Tarjetas no renderizadas');
                console.log('      → Función renderizarPrendasTipoPrendaSinCotizacion() no renderiza');
                console.log('      → Posible error en sincronizarDatosAntesDERenderizar()');
            } else {
                console.log(`    ${tarjetas.length} tarjeta(s) renderizada(s) correctamente`);
            }
        }
    }
};

console.log('   Ejecuta: debugDiagnosticoCompleto()');

// ============================================
// 7️⃣ MONITOREAR SIGUIENTES AGREGACIONES
// ============================================
console.log('\n7️⃣ Monitoreo en Tiempo Real:\n');

console.log('   Ahora cuando agregues una prenda, automáticamente se mostrará info');
console.log('   Ejecuta después: debugDiagnosticoCompleto()');

// Interceptar agregarPrenda para logging
if (window.gestorPrendaSinCotizacion) {
    const gestorOriginal = window.gestorPrendaSinCotizacion.agregarPrenda;
    window.gestorPrendaSinCotizacion.agregarPrenda = function(datos) {
        console.log('🔔 [INTERCEPTOR] agregarPrenda() llamado');
        console.log(`   Nombre: ${datos.nombre_producto}`);
        console.log(`   Procesos: ${Object.keys(datos.procesos || {}).length > 0 ? Object.keys(datos.procesos).join(', ') : '[vacío]'}`);
        
        const resultado = gestorOriginal.call(this, datos);
        
        console.log(`    Agregada con índice: ${resultado}`);
        console.log(`   Total en gestor ahora: ${this.prendas.length}`);
        
        return resultado;
    };
    console.log('    Interceptor instalado');
}

console.log('\n Debug iniciado. Ahora agrega una prenda y luego ejecuta:');
console.log('   debugDiagnosticoCompleto()');
