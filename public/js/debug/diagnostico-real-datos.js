/**
 * TEST DE DIAGNÓSTICO REAL - Captura el estado actual de los datos
 * Pega esto en la consola cuando hayas agregado una prenda
 */

console.log('\n%c🔍 DIAGNÓSTICO REAL DE DATOS EN TIEMPO REAL', 'color: #FF0000; font-size: 16px; font-weight: bold');
console.log('%c═══════════════════════════════════════════════════════════', 'color: #FF0000');

// PASO 1: Ver qué hay en el gestor
console.log('\n%c1️⃣  DATOS EN GESTOR (antes de enviar)', 'color: #FF6600; font-weight: bold');

if (window.gestorPrendaSinCotizacion) {
    const prendasActivas = window.gestorPrendaSinCotizacion.obtenerActivas();
    console.log(`📊 Total de prendas activas: ${prendasActivas.length}`);
    
    prendasActivas.forEach((prenda, index) => {
        console.log(`\n🏷️  PRENDA ${index}:`);
        console.log(`   ├─ Nombre: ${prenda.nombre_producto}`);
        console.log(`   ├─ Género: ${prenda.genero}`);
        console.log(`   ├─ generosConTallas:`, prenda.generosConTallas);
        console.log(`   ├─ cantidadesPorTalla:`, prenda.cantidadesPorTalla);
        console.log(`   └─ tallas array:`, prenda.tallas);
    });
}

// PASO 2: Simular el proceso de derivación de cantidadTalla
console.log('\n%c2️⃣  SIMULANDO DERIVACIÓN DE cantidadTalla', 'color: #FF6600; font-weight: bold');

if (window.gestorPrendaSinCotizacion) {
    const prendasActivas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    prendasActivas.forEach((prenda, prendaIndex) => {
        console.log(`\n🔧 Procesando prenda ${prendaIndex}: "${prenda.nombre_producto}"`);
        
        // Simular el código actual (QUE ESTÁ FALLANDO)
        console.log('\n   MÉTODO ACTUAL (INCORRECTO):');
        const cantidadTallaActual = {};
        
        if (prenda.generosConTallas && typeof prenda.generosConTallas === 'object') {
            console.log(`     └─ Usando generosConTallas:`, prenda.generosConTallas);
            
            Object.keys(prenda.generosConTallas).forEach(genero => {
                const tallaDelGenero = prenda.generosConTallas[genero];
                console.log(`        Procesando género: "${genero}"`, tallaDelGenero);
                
                Object.keys(tallaDelGenero).forEach(talla => {
                    const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                    console.log(`           ${talla}: ${cantidad}`);
                    if (cantidad > 0) {
                        cantidadTallaActual[talla] = cantidad;  //  SOLO TALLA
                    }
                });
            });
        }
        
        console.log(`     Resultado: cantidadTalla =`, cantidadTallaActual);
        console.log(`      PROBLEMA: Faltan los géneros en las claves`);
        
        // Simular el código CORRECTO
        console.log('\n   MÉTODO CORRECTO:');
        const cantidadTallaCorrecta = {};
        
        if (prenda.generosConTallas && typeof prenda.generosConTallas === 'object') {
            Object.keys(prenda.generosConTallas).forEach(genero => {
                const tallaDelGenero = prenda.generosConTallas[genero];
                Object.keys(tallaDelGenero).forEach(talla => {
                    const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                    if (cantidad > 0) {
                        const key = `${genero}-${talla}`;  //  CON GÉNERO
                        cantidadTallaCorrecta[key] = cantidad;
                        console.log(`        ${key}: ${cantidad}`);
                    }
                });
            });
        }
        
        console.log(`     Resultado: cantidadTalla =`, cantidadTallaCorrecta);
        console.log(`      CORRECTO: Incluye géneros en las claves`);
        
        // PASO 3: Comparar arrays
        console.log('\n  📊 COMPARACIÓN:');
        console.log(`     Actual (incorrecto):   [${Object.keys(cantidadTallaActual).join(', ')}]`);
        console.log(`     Correcto:               [${Object.keys(cantidadTallaCorrecta).join(', ')}]`);
        
        // PASO 4: Ver qué espera el backend
        console.log('\n  🔄 QUÉ ESPERA EL BACKEND:');
        console.log(`     tallas array (keys de cantidadTalla):`);
        console.log(`      Incorrecto: [${Object.keys(cantidadTallaActual).join(', ')}]`);
        console.log(`      Correcto:   [${Object.keys(cantidadTallaCorrecta).join(', ')}]`);
    });
}

// PASO 5: Ver el payload que se enviaría
console.log('\n%c3️⃣  PAYLOAD QUE SE ENVIARÍA AL BACKEND', 'color: #FF6600; font-weight: bold');

if (window.gestorPrendaSinCotizacion) {
    const prendasActivas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    prendasActivas.forEach((prenda, index) => {
        console.log(`\n📦 Item ${index} (con formato actual - INCORRECTO):`);
        
        // Simular el payload incorrecto
        const cantidadTallaIncorrecto = {};
        if (prenda.generosConTallas) {
            Object.keys(prenda.generosConTallas).forEach(genero => {
                const tallaDelGenero = prenda.generosConTallas[genero];
                Object.keys(tallaDelGenero).forEach(talla => {
                    const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                    if (cantidad > 0) {
                        cantidadTallaIncorrecto[talla] = cantidad;  // 
                    }
                });
            });
        }
        
        const payloadIncorrecto = {
            tipo: 'prenda_nueva',
            prenda: prenda.nombre_producto,
            cantidad_talla: cantidadTallaIncorrecto,
            tallas: Object.keys(cantidadTallaIncorrecto)  //  VACÍO O SIN GÉNERO
        };
        
        console.log(JSON.stringify(payloadIncorrecto, null, 2));
        console.log(` PROBLEMA: tallas = ${JSON.stringify(payloadIncorrecto.tallas)} (VACÍO o SIN GÉNERO)`);
        
        // Simular el payload correcto
        console.log(`\n📦 Item ${index} (con formato correcto - ARREGLADO):`);
        
        const cantidadTallaCorrect = {};
        if (prenda.generosConTallas) {
            Object.keys(prenda.generosConTallas).forEach(genero => {
                const tallaDelGenero = prenda.generosConTallas[genero];
                Object.keys(tallaDelGenero).forEach(talla => {
                    const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                    if (cantidad > 0) {
                        cantidadTallaCorrect[`${genero}-${talla}`] = cantidad;  // 
                    }
                });
            });
        }
        
        const payloadCorrecto = {
            tipo: 'prenda_nueva',
            prenda: prenda.nombre_producto,
            cantidad_talla: cantidadTallaCorrect,
            tallas: Object.keys(cantidadTallaCorrect)  //  CON GÉNERO
        };
        
        console.log(JSON.stringify(payloadCorrecto, null, 2));
        console.log(` CORRECTO: tallas = ${JSON.stringify(payloadCorrecto.tallas)}`);
    });
}

// RESUMEN
console.log('\n%c═══════════════════════════════════════════════════════════', 'color: #FF0000');
console.log('%c RESUMEN DEL PROBLEMA', 'color: #FF0000; font-weight: bold');
console.log('%c═══════════════════════════════════════════════════════════', 'color: #FF0000');

console.log(`
 PROBLEMA ENCONTRADO:
   En línea 1022 de gestion-items-pedido.js
   
   Código actual (INCORRECTO):
   ├─ cantidadTalla['S'] = 230
   ├─ cantidadTalla['M'] = 230
   └─ cantidadTalla['L'] = 230
   
   Resultado: tallas = ['S', 'M', 'L']
   Backend espera: tallas != [] ✓ (pasa)
   PERO falta información del género!

 SOLUCIÓN:
   Código corregido:
   ├─ cantidadTalla['dama-S'] = 230
   ├─ cantidadTalla['dama-M'] = 230
   └─ cantidadTalla['dama-L'] = 230
   
   Resultado: tallas = ['dama-S', 'dama-M', 'dama-L']
   Backend espera: tallas != [] ✓ (pasa)
   Y contiene información del género ✓

🔧 CAMBIO A HACER:
   Línea 1022 - Cambiar:
   cantidadTalla[talla] = cantidad
   
   Por:
   cantidadTalla[\`\${genero}-\${talla}\`] = cantidad
`);

console.log('%c═══════════════════════════════════════════════════════════\n', 'color: #FF0000');
