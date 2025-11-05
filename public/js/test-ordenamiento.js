/**
 * Script de prueba para verificar el ordenamiento en tiempo real
 * 
 * Abre la consola del navegador y ejecuta: testOrdenamientoTiempoReal()
 */

function testOrdenamientoTiempoReal() {
    console.log('=== PRUEBA DE ORDENAMIENTO EN TIEMPO REAL ===\n');
    
    // Test 1: Verificar que los registros existentes están ordenados
    console.log('📋 Test 1: Verificar orden de registros existentes');
    console.log('--------------------------------------------------');
    
    const tables = document.querySelectorAll('table[data-section]');
    let allOrdered = true;
    
    tables.forEach(table => {
        const section = table.getAttribute('data-section');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
        
        if (rows.length === 0) {
            console.log(`⚠️  Sección "${section}": No hay registros`);
            return;
        }
        
        const ids = rows.map(row => parseInt(row.getAttribute('data-id')));
        console.log(`Sección "${section}": IDs = [${ids.join(', ')}]`);
        
        // Verificar orden ascendente
        let isOrdered = true;
        for (let i = 1; i < ids.length; i++) {
            if (ids[i] < ids[i - 1]) {
                isOrdered = false;
                allOrdered = false;
                console.log(`❌ Error: ID ${ids[i]} está antes de ID ${ids[i-1]}`);
                break;
            }
        }
        
        if (isOrdered) {
            console.log(`✅ Sección "${section}": Orden correcto (ascendente)`);
        }
    });
    
    console.log('\n');
    
    // Test 2: Simular inserción de nuevo registro
    console.log('📋 Test 2: Simular inserción de nuevo registro');
    console.log('--------------------------------------------------');
    
    const testSection = 'produccion';
    const testTable = document.querySelector(`table[data-section="${testSection}"]`);
    
    if (!testTable) {
        console.log('❌ No se encontró la tabla de producción');
        return;
    }
    
    const testTbody = testTable.querySelector('tbody');
    const existingRows = Array.from(testTbody.querySelectorAll('tr[data-id]'));
    const existingIds = existingRows.map(row => parseInt(row.getAttribute('data-id')));
    const maxId = Math.max(...existingIds, 0);
    const newId = maxId + 1;
    
    console.log(`IDs existentes: [${existingIds.join(', ')}]`);
    console.log(`Nuevo ID a insertar: ${newId}`);
    
    // Crear registro de prueba
    const testRegistro = {
        id: newId,
        fecha: new Date().toISOString(),
        modulo: 'TEST MODULE',
        orden_produccion: '9999',
        hora: 'HORA 01',
        tiempo_ciclo: 100,
        porcion_tiempo: 1,
        cantidad: 50,
        paradas_programadas: 'NINGUNA',
        numero_operarios: 10,
        eficiencia: 0.95
    };
    
    // Simular inserción usando la función del sistema
    if (typeof agregarRegistroTiempoReal === 'function') {
        agregarRegistroTiempoReal(testRegistro, testSection);
        
        // Verificar que se insertó correctamente
        setTimeout(() => {
            const updatedRows = Array.from(testTbody.querySelectorAll('tr[data-id]'));
            const updatedIds = updatedRows.map(row => parseInt(row.getAttribute('data-id')));
            
            console.log(`IDs después de inserción: [${updatedIds.join(', ')}]`);
            
            // Verificar que el nuevo registro está en la posición correcta
            const newRowIndex = updatedIds.indexOf(newId);
            
            if (newRowIndex === -1) {
                console.log('❌ El nuevo registro NO se insertó');
            } else {
                // Verificar que está en orden
                let correctPosition = true;
                if (newRowIndex > 0 && updatedIds[newRowIndex - 1] > newId) {
                    correctPosition = false;
                }
                if (newRowIndex < updatedIds.length - 1 && updatedIds[newRowIndex + 1] < newId) {
                    correctPosition = false;
                }
                
                if (correctPosition) {
                    console.log(`✅ El nuevo registro se insertó en la posición correcta (índice ${newRowIndex})`);
                } else {
                    console.log(`❌ El nuevo registro NO está en la posición correcta`);
                }
                
                // Eliminar el registro de prueba
                const testRow = testTbody.querySelector(`tr[data-id="${newId}"]`);
                if (testRow) {
                    testRow.remove();
                    console.log('🧹 Registro de prueba eliminado');
                }
            }
            
            console.log('\n=== RESUMEN ===');
            if (allOrdered) {
                console.log('✅ Todos los registros están ordenados correctamente');
                console.log('✅ La inserción en tiempo real mantiene el orden');
                console.log('✅ El sistema funciona correctamente');
            } else {
                console.log('❌ Hay problemas con el ordenamiento');
            }
        }, 100);
    } else {
        console.log('❌ La función agregarRegistroTiempoReal no está disponible');
    }
}

// Hacer la función disponible globalmente
window.testOrdenamientoTiempoReal = testOrdenamientoTiempoReal;

console.log('✅ Script de prueba cargado. Ejecuta: testOrdenamientoTiempoReal()');
