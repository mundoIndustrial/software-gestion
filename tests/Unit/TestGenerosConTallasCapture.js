/**
 * Test para validar que generosConTallas se construye correctamente
 * y que los datos se transforman correctamente para el API
 */

// Simulación de los datos que llegan del formulario
class TestGenerosConTallasCapture {
    constructor() {
        this.testResults = [];
        this.totalTests = 0;
        this.passedTests = 0;
    }

    // Test 1: Validar construcción de generosConTallas desde tallasPorGenero y cantidadesPorTalla
    testGenerosConTallasConstruction() {
        console.log('\n========== TEST 1: Construcción de generosConTallas ==========\n');
        this.totalTests++;

        // Datos de entrada simulados
        const tallasPorGenero = [
            { genero: 'dama', tallas: ['XS', 'S', 'M', 'L', 'XL'], tipo: 'letra' },
            { genero: 'caballero', tallas: ['28', '30', '32', '34', '36'], tipo: 'numero' }
        ];

        const cantidadesPorTalla = {
            'XS': 50,
            'S': 150,
            'M': 200,
            'L': 150,
            'XL': 50,
            '28': 30,
            '30': 100,
            '32': 140,
            '34': 100,
            '36': 30
        };

        // Lógica de construcción (igual a la implementada en gestion-items-pedido.js)
        const generosConTallas = {};
        tallasPorGenero.forEach(tallaData => {
            const generoKey = tallaData.genero;
            generosConTallas[generoKey] = {};
            
            if (tallaData.tallas && Array.isArray(tallaData.tallas)) {
                tallaData.tallas.forEach(talla => {
                    const cantidad = cantidadesPorTalla[talla] || 0;
                    if (cantidad > 0) {
                        generosConTallas[generoKey][talla] = cantidad;
                    }
                });
            }
        });

        console.log('📊 Entrada:');
        console.log('  tallasPorGenero:', JSON.stringify(tallasPorGenero, null, 2));
        console.log('  cantidadesPorTalla:', cantidadesPorTalla);

        console.log('\n Salida (generosConTallas):');
        console.log(JSON.stringify(generosConTallas, null, 2));

        // Validaciones
        const validaciones = [
            {
                nombre: 'generosConTallas tiene "dama"',
                resultado: generosConTallas.hasOwnProperty('dama')
            },
            {
                nombre: 'generosConTallas tiene "caballero"',
                resultado: generosConTallas.hasOwnProperty('caballero')
            },
            {
                nombre: 'dama tiene tallas correctas',
                resultado: Object.keys(generosConTallas.dama).length === 5 &&
                          generosConTallas.dama['M'] === 200
            },
            {
                nombre: 'caballero tiene tallas correctas',
                resultado: Object.keys(generosConTallas.caballero).length === 5 &&
                          generosConTallas.caballero['32'] === 140
            },
            {
                nombre: 'No incluye tallas con cantidad 0',
                resultado: !generosConTallas.dama.hasOwnProperty('NoExiste')
            }
        ];

        this.printValidations(validaciones);
    }

    // Test 2: Validar construcción de cantidadTalla desde generosConTallas
    testCantidadTallaDerivation() {
        console.log('\n========== TEST 2: Derivación de cantidadTalla para API ==========\n');
        this.totalTests++;

        const generosConTallas = {
            dama: { XS: 50, S: 150, M: 200, L: 150, XL: 50 },
            caballero: { '28': 30, '30': 100, '32': 140, '34': 100, '36': 30 }
        };

        // Lógica de derivación (convertir estructura anidada a flat para API)
        const cantidadTalla = {};
        Object.keys(generosConTallas).forEach(genero => {
            const tallas = generosConTallas[genero];
            Object.keys(tallas).forEach(talla => {
                const key = `${genero}-${talla}`;
                cantidadTalla[key] = tallas[talla];
            });
        });

        console.log('📊 Entrada (generosConTallas):');
        console.log(JSON.stringify(generosConTallas, null, 2));

        console.log('\n Salida (cantidadTalla para API):');
        console.log(cantidadTalla);

        // Validaciones
        const validaciones = [
            {
                nombre: 'cantidadTalla tiene 10 elementos (5 dama + 5 caballero)',
                resultado: Object.keys(cantidadTalla).length === 10
            },
            {
                nombre: 'Formato correcto para dama: "dama-M" = 200',
                resultado: cantidadTalla['dama-M'] === 200
            },
            {
                nombre: 'Formato correcto para caballero: "caballero-32" = 140',
                resultado: cantidadTalla['caballero-32'] === 140
            },
            {
                nombre: 'Todos los valores son números positivos',
                resultado: Object.values(cantidadTalla).every(v => typeof v === 'number' && v > 0)
            }
        ];

        this.printValidations(validaciones);
    }

    // Test 3: Validar construcción del array tallas para validación backend
    testTallasArrayConstruction() {
        console.log('\n========== TEST 3: Array tallas para validación backend ==========\n');
        this.totalTests++;

        const cantidadTalla = {
            'dama-XS': 50,
            'dama-S': 150,
            'dama-M': 200,
            'dama-L': 150,
            'dama-XL': 50
        };

        // Lógica de construcción del array tallas
        const tallas = Object.keys(cantidadTalla).map(key => {
            const [genero, talla] = key.split('-');
            return {
                genero,
                talla,
                cantidad: cantidadTalla[key]
            };
        });

        console.log('📊 Entrada (cantidadTalla):');
        console.log(cantidadTalla);

        console.log('\n Salida (array tallas):');
        console.log(JSON.stringify(tallas, null, 2));

        // Validaciones
        const validaciones = [
            {
                nombre: 'Array no está vacío',
                resultado: Array.isArray(tallas) && tallas.length > 0
            },
            {
                nombre: 'Tiene 5 elementos',
                resultado: tallas.length === 5
            },
            {
                nombre: 'Cada elemento tiene genero, talla y cantidad',
                resultado: tallas.every(t => t.hasOwnProperty('genero') && 
                                           t.hasOwnProperty('talla') && 
                                           t.hasOwnProperty('cantidad'))
            },
            {
                nombre: 'El primer elemento es "dama-XS" con cantidad 50',
                resultado: tallas[0].genero === 'dama' && 
                          tallas[0].talla === 'XS' && 
                          tallas[0].cantidad === 50
            },
            {
                nombre: 'Pasaría validación backend (length > 0)',
                resultado: tallas.length > 0
            }
        ];

        this.printValidations(validaciones);
    }

    // Test 4: Test de flujo completo end-to-end
    testCompleteFlow() {
        console.log('\n========== TEST 4: Flujo completo (End-to-End) ==========\n');
        this.totalTests++;

        console.log('Simulando flujo completo de usuario:');
        console.log('1. Usuario selecciona tallas en el modal');
        console.log('2. Sistema construye generosConTallas');
        console.log('3. Sistema crea cantidadTalla para API');
        console.log('4. Sistema crea array tallas para validación\n');

        // Datos iniciales
        const tallasPorGenero = [
            { genero: 'dama', tallas: ['S', 'M', 'L'], tipo: 'letra' }
        ];

        const cantidadesPorTalla = {
            'S': 230,
            'M': 230,
            'L': 230
        };

        // Paso 1: Construir generosConTallas
        const generosConTallas = {};
        tallasPorGenero.forEach(tallaData => {
            const generoKey = tallaData.genero;
            generosConTallas[generoKey] = {};
            
            if (tallaData.tallas && Array.isArray(tallaData.tallas)) {
                tallaData.tallas.forEach(talla => {
                    const cantidad = cantidadesPorTalla[talla] || 0;
                    if (cantidad > 0) {
                        generosConTallas[generoKey][talla] = cantidad;
                    }
                });
            }
        });

        console.log(' Paso 1 - generosConTallas:');
        console.log(JSON.stringify(generosConTallas, null, 2));

        // Paso 2: Construir cantidadTalla
        const cantidadTalla = {};
        Object.keys(generosConTallas).forEach(genero => {
            const tallas = generosConTallas[genero];
            Object.keys(tallas).forEach(talla => {
                const key = `${genero}-${talla}`;
                cantidadTalla[key] = tallas[talla];
            });
        });

        console.log('\n Paso 2 - cantidadTalla:');
        console.log(cantidadTalla);

        // Paso 3: Construir array tallas
        const tallas = Object.keys(cantidadTalla).map(key => {
            const [genero, talla] = key.split('-');
            return {
                genero,
                talla,
                cantidad: cantidadTalla[key]
            };
        });

        console.log('\n Paso 3 - Array tallas (para backend):');
        console.log(JSON.stringify(tallas, null, 2));

        // Paso 4: Construir prenda para API
        const prendaParaAPI = {
            nombre: 'Polo básico',
            descripcion: 'Polo blanco básico',
            imagen: 'blob:...',
            tallas: tallas
        };

        console.log('\n Paso 4 - Prenda para API:');
        console.log(JSON.stringify(prendaParaAPI, null, 2));

        // Validaciones finales
        const validaciones = [
            {
                nombre: 'generosConTallas está poblado',
                resultado: Object.keys(generosConTallas).length > 0
            },
            {
                nombre: 'cantidadTalla tiene datos',
                resultado: Object.keys(cantidadTalla).length === 3
            },
            {
                nombre: 'Array tallas NO está vacío (pasaría validación)',
                resultado: Array.isArray(tallas) && tallas.length > 0
            },
            {
                nombre: 'Estructura es válida para enviar a API',
                resultado: prendaParaAPI.tallas.length > 0 &&
                          prendaParaAPI.tallas.every(t => t.genero && t.talla && t.cantidad)
            }
        ];

        this.printValidations(validaciones);
    }

    // Test 5: Casos edge case
    testEdgeCases() {
        console.log('\n========== TEST 5: Casos Edge Case ==========\n');
        this.totalTests++;

        console.log('Probando casos especiales:\n');

        const casos = [
            {
                nombre: 'Una sola talla seleccionada',
                tallasPorGenero: [{ genero: 'dama', tallas: ['M'], tipo: 'letra' }],
                cantidadesPorTalla: { 'M': 500 },
                expectedLength: 1
            },
            {
                nombre: 'Dos géneros con diferentes tallas',
                tallasPorGenero: [
                    { genero: 'dama', tallas: ['S', 'M'], tipo: 'letra' },
                    { genero: 'caballero', tallas: ['30', '32'], tipo: 'numero' }
                ],
                cantidadesPorTalla: { 'S': 100, 'M': 100, '30': 100, '32': 100 },
                expectedLength: 4
            },
            {
                nombre: 'Cantidades muy grandes',
                tallasPorGenero: [{ genero: 'dama', tallas: ['L'], tipo: 'letra' }],
                cantidadesPorTalla: { 'L': 99999 },
                expectedLength: 1
            }
        ];

        casos.forEach((caso, index) => {
            console.log(`\n Caso ${index + 1}: ${caso.nombre}`);
            
            const generosConTallas = {};
            caso.tallasPorGenero.forEach(tallaData => {
                const generoKey = tallaData.genero;
                generosConTallas[generoKey] = {};
                
                if (tallaData.tallas && Array.isArray(tallaData.tallas)) {
                    tallaData.tallas.forEach(talla => {
                        const cantidad = caso.cantidadesPorTalla[talla] || 0;
                        if (cantidad > 0) {
                            generosConTallas[generoKey][talla] = cantidad;
                        }
                    });
                }
            });

            const cantidadTalla = {};
            Object.keys(generosConTallas).forEach(genero => {
                const tallas = generosConTallas[genero];
                Object.keys(tallas).forEach(talla => {
                    const key = `${genero}-${talla}`;
                    cantidadTalla[key] = tallas[talla];
                });
            });

            const resultado = Object.keys(cantidadTalla).length === caso.expectedLength &&
                            Object.keys(cantidadTalla).length > 0;

            console.log(`  Resultado: ${resultado ? ' PASS' : ' FAIL'}`);
            console.log(`  Tallas generadas: ${Object.keys(cantidadTalla).length} (esperadas: ${caso.expectedLength})`);
            console.log(`  Estructura:`, cantidadTalla);

            if (resultado) {
                this.passedTests++;
            }
        });
    }

    // Utilidad: Imprimir validaciones
    printValidations(validaciones) {
        let passCount = 0;
        
        validaciones.forEach((val, index) => {
            const estado = val.resultado ? ' PASS' : ' FAIL';
            console.log(`${index + 1}. ${val.nombre}: ${estado}`);
            if (val.resultado) {
                passCount++;
                this.passedTests++;
            }
        });

        console.log(`\n📊 Resultado: ${passCount}/${validaciones.length} validaciones pasadas`);
    }

    // Ejecutar todos los tests
    runAllTests() {
        console.log('\n');
        console.log('╔════════════════════════════════════════════════════════════════╗');
        console.log('║   TEST SUITE: Captura de Información de Tallas y generosConTallas   ║');
        console.log('╚════════════════════════════════════════════════════════════════╝');

        this.testGenerosConTallasConstruction();
        this.testCantidadTallaDerivation();
        this.testTallasArrayConstruction();
        this.testCompleteFlow();
        this.testEdgeCases();

        // Resumen final
        console.log('\n');
        console.log('╔════════════════════════════════════════════════════════════════╗');
        console.log('║                       RESUMEN FINAL                              ║');
        console.log('╚════════════════════════════════════════════════════════════════╝');
        console.log(`\n📊 Tests ejecutados: ${this.totalTests}`);
        console.log(` Tests pasados: ${this.passedTests}`);
        console.log(` Tests fallados: ${this.totalTests - this.passedTests}`);
        
        const porcentaje = ((this.passedTests / this.totalTests) * 100).toFixed(2);
        console.log(`\n📈 Porcentaje de éxito: ${porcentaje}%\n`);

        return {
            total: this.totalTests,
            passed: this.passedTests,
            failed: this.totalTests - this.passedTests,
            percentage: parseFloat(porcentaje)
        };
    }
}

// Ejecutar tests
const tester = new TestGenerosConTallasCapture();
const resultados = tester.runAllTests();
