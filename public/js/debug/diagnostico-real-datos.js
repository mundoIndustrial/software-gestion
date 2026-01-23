/**
 * TEST DE DIAGNÓSTICO REAL - Captura el estado actual de los datos
 * Pega esto en la consola cuando hayas agregado una prenda
 */
// PASO 1: Ver qué hay en el gestor
', 'color: #FF6600; font-weight: bold');

if (window.gestorPrendaSinCotizacion) {
    const prendasActivas = window.gestorPrendaSinCotizacion.obtenerActivas();

    
    prendasActivas.forEach((prenda, index) => {






    });
}

// PASO 2: Simular el proceso de derivación de cantidadTalla
if (window.gestorPrendaSinCotizacion) {
    const prendasActivas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    prendasActivas.forEach((prenda, prendaIndex) => {

        
        // Simular el código actual (QUE ESTÁ FALLANDO)

        const cantidadTallaActual = {};
        
        if (prenda.generosConTallas && typeof prenda.generosConTallas === 'object') {

            
            Object.keys(prenda.generosConTallas).forEach(genero => {
                const tallaDelGenero = prenda.generosConTallas[genero];

                
                Object.keys(tallaDelGenero).forEach(talla => {
                    const cantidad = parseInt(tallaDelGenero[talla]) || 0;

                    if (cantidad > 0) {
                        cantidadTallaActual[talla] = cantidad;  //  SOLO TALLA
                    }
                });
            });
        }
        


        
        // Simular el código CORRECTO

        const cantidadTallaCorrecta = {};
        
        if (prenda.generosConTallas && typeof prenda.generosConTallas === 'object') {
            Object.keys(prenda.generosConTallas).forEach(genero => {
                const tallaDelGenero = prenda.generosConTallas[genero];
                Object.keys(tallaDelGenero).forEach(talla => {
                    const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                    if (cantidad > 0) {
                        const key = `${genero}-${talla}`;  //  CON GÉNERO
                        cantidadTallaCorrecta[key] = cantidad;

                    }
                });
            });
        }
        


        
        // PASO 3: Comparar arrays



        
        // PASO 4: Ver qué espera el backend




    });
}

// PASO 5: Ver el payload que se enviaría
if (window.gestorPrendaSinCotizacion) {
    const prendasActivas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    prendasActivas.forEach((prenda, index) => {

        
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
            cantidad_talla: cantidadTallaIncorrecto,  // FORMATO LEGACY - EVITAR
            tallas: Object.keys(cantidadTallaIncorrecto)
        };
        


        
        // Mostrar el payload correcto (formato relacional)

        
        const cantidadTallaCorrect = {};
        if (prenda.generosConTallas) {
            Object.keys(prenda.generosConTallas).forEach(genero => {
                const generoMayus = genero.toUpperCase();
                cantidadTallaCorrect[generoMayus] = {};
                const tallaDelGenero = prenda.generosConTallas[genero];
                Object.keys(tallaDelGenero).forEach(talla => {
                    const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                    if (cantidad > 0) {
                        cantidadTallaCorrect[generoMayus][talla] = cantidad;
                    }
                });
            });
        }
        
        const payloadCorrecto = {
            tipo: 'prenda_nueva',
            prenda: prenda.nombre_producto,
            cantidad_talla: cantidadTallaCorrect,  // FORMATO RELACIONAL - CORRECTO
            tallas: cantidadTallaCorrect  // Misma estructura relacional
        };
        


    });
}

// RESUMEN
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

 CAMBIO A HACER:
   Línea 1022 - Cambiar:
   cantidadTalla[talla] = cantidad
   
   Por:
   cantidadTalla[\`\${genero}-\${talla}\`] = cantidad
`);



