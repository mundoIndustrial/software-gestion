/**
 * TEST: Verificar que modo_tallas se carga correctamente en window.procesosSeleccionados
 * 
 * PASOS:
 * 1. Abre una página de edición de pedido en el navegador
 * 2. Abre DevTools (F12)
 * 3. Copia TODO este archivo y pégalo en la consola
 * 4. Presiona Enter
 * 5. Revisa los logs de salida
 */

(function() {
    console.log('\n═══════════════════════════════════════════════════════════');
    console.log('🧪 TEST: Validación de modo_tallas en Procesos Cargados');
    console.log('═══════════════════════════════════════════════════════════\n');

    // 1. Verificar datos globales disponibles
    console.log('📋 PASO 1: Verificar datos globales');
    console.log('─────────────────────────────────────────────────────────');
    
    const verificarGlobal = (varName, varValue) => {
        if (varValue !== undefined) {
            console.log(`✅ ${varName}: disponible`);
            return true;
        } else {
            console.log(`❌ ${varName}: NO disponible`);
            return false;
        }
    };

    const tieneDataEdicion = verificarGlobal('window.pedidoEdicionData', window.pedidoEdicionData);
    const tienePrendas = verificarGlobal('window.datosEdicionPedido', window.datosEdicionPedido);
    const tieneProc = verificarGlobal('window.procesosSeleccionados', window.procesosSeleccionados);
    
    console.log('\n');

    // 2. Verificar estructura de server data
    if (tieneDataEdicion) {
        console.log('📋 PASO 2: Verificar datos del servidor');
        console.log('─────────────────────────────────────────────────────────');
        
        const datosServ = window.pedidoEdicionData;
        if (datosServ.pedido && datosServ.pedido.prendas) {
            console.log(`✅ Prendas del servidor: ${datosServ.pedido.prendas.length}`);
            
            datosServ.pedido.prendas.forEach((prenda, idx) => {
                if (prenda.procesos && Array.isArray(prenda.procesos)) {
                    console.log(`   📦 Prenda ${idx}: ${prenda.nombre_prenda || 'N/A'}`);
                    console.log(`      Procesos: ${prenda.procesos.length}`);
                    
                    prenda.procesos.forEach(proc => {
                        const tipoNombre = proc.tipoProceso?.nombre || proc.tipo || proc.nombre || 'DESCONOCIDO';
                        const modoTallas = proc.modo_tallas;
                        console.log(`      ├─ ${tipoNombre}: modo_tallas = ${modoTallas ? `✅ ${modoTallas}` : '❌ FALTA'}`);
                    });
                }
            });
        } else {
            console.log('❌ No hay estructura pedido.prendas');
        }
        console.log('\n');
    }

    // 3. Verificar procesosSeleccionados
    if (tieneProc) {
        console.log('📋 PASO 3: Verificar window.procesosSeleccionados');
        console.log('─────────────────────────────────────────────────────────');
        
        const procesos = window.procesosSeleccionados || {};
        const keys = Object.keys(procesos);
        
        if (keys.length === 0) {
            console.log('❌ window.procesosSeleccionados está VACÍO');
        } else {
            console.log(`✅ Procesos cargados: ${keys.length}`);
            console.log(`   Claves: ${keys.join(', ')}\n`);
            
            keys.forEach(tipo => {
                const proc = procesos[tipo];
                const datos = proc.datos || {};
                const modoTallas = datos.modo_tallas;
                const modoTallasExistente = 'modo_tallas' in datos;
                
                console.log(`   📌 ${tipo}:`);
                console.log(`      ├─ modo_tallas: ${modoTallasExistente ? `✅ ${modoTallas}` : '❌ FALTA (undefined)'}`);
                console.log(`      ├─ tipoProceso.nombre: ${datos.tipoProceso?.nombre || 'N/A'}`);
                console.log(`      ├─ id: ${datos.id || 'N/A'}`);
                console.log(`      └─ campos totales: ${Object.keys(datos).length}`);
            });
        }
        console.log('\n');
    }

    // 4. RESUMEN FINAL
    console.log('📊 RESUMEN FINAL');
    console.log('═════════════════════════════════════════════════════════');
    
    if (tieneProc && Object.keys(window.procesosSeleccionados || {}).length > 0) {
        const procesos = window.procesosSeleccionados;
        let conModoTallas = 0;
        let sinModoTallas = 0;
        
        Object.values(procesos).forEach(proc => {
            if (proc.datos?.modo_tallas) {
                conModoTallas++;
            } else {
                sinModoTallas++;
            }
        });
        
        console.log(`✅ Procesos con modo_tallas: ${conModoTallas}`);
        console.log(`❌ Procesos sin modo_tallas: ${sinModoTallas}`);
        
        if (sinModoTallas > 0) {
            console.log('\n⚠️  ADVERTENCIA: Algunos procesos NO tienen modo_tallas');
            console.log('   Esto causará que el modal muestre el default "general"');
            console.log('   Verifica que el servidor esté devolviendo este campo');
        } else {
            console.log('\n✅ RESULTADO: Todos los procesos tienen modo_tallas');
            console.log('   El modal debería mostrar el valor correcto');
        }
    }
    
    console.log('\n═════════════════════════════════════════════════════════\n');
})();
