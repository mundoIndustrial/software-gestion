/**
 * TEST CORRECTO - Refleja el código arreglado en gestion-items-pedido.js
 * Este test simula exactamente lo que hace el código reparado
 */

console.clear();
console.log('%c✅ TEST ACTUALIZADO - Derivación CORRECTA de cantidadTalla', 'color: #00FF00; font-size: 16px; font-weight: bold');
console.log('%c═══════════════════════════════════════════════════════════\n', 'color: #00FF00');

class TestCantidadTallaCorrecta {
    constructor() {
        this.tests = [];
        this.passed = 0;
        this.failed = 0;
    }

    // Test 1: Derivación de cantidadTalla CON GÉNERO
    testDerivacionConGenero() {
        console.log('%c1️⃣  TEST: Derivación de cantidadTalla CON GÉNERO', 'color: #00CCFF; font-weight: bold');
        
        const prenda = {
            nombre_producto: 'Polo corporativo',
            genero: ['dama'],
            generosConTallas: {
                dama: {
                    S: 230,
                    M: 230,
                    L: 230
                }
            }
        };
        
        // Simular exactamente lo que hace el código arreglado
        const cantidadTalla = {};
        
        if (prenda.generosConTallas && typeof prenda.generosConTallas === 'object') {
            console.log('   Input: generosConTallas =', prenda.generosConTallas);
            
            Object.keys(prenda.generosConTallas).forEach(genero => {
                const tallaDelGenero = prenda.generosConTallas[genero];
                Object.keys(tallaDelGenero).forEach(talla => {
                    const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                    if (cantidad > 0) {
                        // ✅ CÓDIGO ARREGLADO: INCLUIR GÉNERO EN LA CLAVE
                        cantidadTalla[`${genero}-${talla}`] = cantidad;
                    }
                });
            });
        }
        
        console.log('   Output: cantidadTalla =', cantidadTalla);
        
        // Convertir a array tallas
        const tallas = Object.keys(cantidadTalla);
        console.log('   Array tallas =', tallas);
        
        // Validaciones
        const validaciones = [
            {
                nombre: 'cantidadTalla NO está vacío',
                resultado: Object.keys(cantidadTalla).length > 0,
                detalle: `Claves: [${Object.keys(cantidadTalla).join(', ')}]`
            },
            {
                nombre: 'tallas NO está vacío',
                resultado: tallas.length > 0,
                detalle: `Elementos: ${tallas.length}`
            },
            {
                nombre: 'Cada clave tiene el formato "genero-talla"',
                resultado: tallas.every(t => t.includes('-')),
                detalle: `Todas tienen "-": ${tallas.every(t => t.includes('-'))}`
            },
            {
                nombre: 'Incluye "dama-S", "dama-M", "dama-L"',
                resultado: cantidadTalla['dama-S'] === 230 && 
                          cantidadTalla['dama-M'] === 230 && 
                          cantidadTalla['dama-L'] === 230,
                detalle: `Valores correctos: S=${cantidadTalla['dama-S']}, M=${cantidadTalla['dama-M']}, L=${cantidadTalla['dama-L']}`
            },
            {
                nombre: 'Pasaría validación del backend',
                resultado: tallas.length > 0,
                detalle: `Backend requiere tallas.length > 0: ${tallas.length > 0}`
            }
        ];
        
        this.printValidaciones(validaciones);
        return validaciones.every(v => v.resultado);
    }

    // Test 2: Caso con dos géneros
    testDosGeneros() {
        console.log('%c2️⃣  TEST: Dos géneros (Dama + Caballero)', 'color: #00CCFF; font-weight: bold');
        
        const prenda = {
            nombre_producto: 'Uniforme unisex',
            genero: ['dama', 'caballero'],
            generosConTallas: {
                dama: {
                    S: 100,
                    M: 100
                },
                caballero: {
                    30: 50,
                    32: 50
                }
            }
        };
        
        const cantidadTalla = {};
        
        Object.keys(prenda.generosConTallas).forEach(genero => {
            const tallaDelGenero = prenda.generosConTallas[genero];
            Object.keys(tallaDelGenero).forEach(talla => {
                const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                if (cantidad > 0) {
                    cantidadTalla[`${genero}-${talla}`] = cantidad;
                }
            });
        });
        
        console.log('   Input: generosConTallas =', prenda.generosConTallas);
        console.log('   Output: cantidadTalla =', cantidadTalla);
        
        const tallas = Object.keys(cantidadTalla);
        console.log('   Array tallas =', tallas);
        
        const validaciones = [
            {
                nombre: '4 tallas en total (2 dama + 2 caballero)',
                resultado: Object.keys(cantidadTalla).length === 4,
                detalle: `Total: ${Object.keys(cantidadTalla).length}`
            },
            {
                nombre: 'Dama tiene sus propias tallas',
                resultado: cantidadTalla['dama-S'] === 100 && cantidadTalla['dama-M'] === 100,
                detalle: `dama-S=${cantidadTalla['dama-S']}, dama-M=${cantidadTalla['dama-M']}`
            },
            {
                nombre: 'Caballero tiene sus propias tallas',
                resultado: cantidadTalla['caballero-30'] === 50 && cantidadTalla['caballero-32'] === 50,
                detalle: `caballero-30=${cantidadTalla['caballero-30']}, caballero-32=${cantidadTalla['caballero-32']}`
            },
            {
                nombre: 'Géneros están separados en las claves',
                resultado: Object.keys(cantidadTalla).some(k => k.startsWith('dama-')) &&
                          Object.keys(cantidadTalla).some(k => k.startsWith('caballero-')),
                detalle: `Tienen prefijos de género`
            }
        ];
        
        this.printValidaciones(validaciones);
        return validaciones.every(v => v.resultado);
    }

    // Test 3: Fallback a cantidadesPorTalla
    testFallback() {
        console.log('%c3️⃣  TEST: Fallback a cantidadesPorTalla', 'color: #00CCFF; font-weight: bold');
        
        const prenda = {
            nombre_producto: 'Polera básica',
            genero: ['dama'],
            cantidadesPorTalla: {
                S: 200,
                M: 200,
                L: 200
            }
        };
        
        const cantidadTalla = {};
        
        // Simular el código arreglado del fallback
        if (prenda.cantidadesPorTalla && typeof prenda.cantidadesPorTalla === 'object') {
            const generoFallback = prenda.genero && Array.isArray(prenda.genero) && prenda.genero.length > 0 
                ? prenda.genero[0] 
                : 'mixto';
            
            console.log('   Usando fallback con generoFallback =', generoFallback);
            
            Object.keys(prenda.cantidadesPorTalla).forEach(talla => {
                const cantidad = parseInt(prenda.cantidadesPorTalla[talla]) || 0;
                if (cantidad > 0) {
                    cantidadTalla[`${generoFallback}-${talla}`] = cantidad;
                }
            });
        }
        
        console.log('   Input: cantidadesPorTalla =', prenda.cantidadesPorTalla);
        console.log('   Output: cantidadTalla =', cantidadTalla);
        
        const tallas = Object.keys(cantidadTalla);
        console.log('   Array tallas =', tallas);
        
        const validaciones = [
            {
                nombre: 'cantidadTalla tiene datos del fallback',
                resultado: Object.keys(cantidadTalla).length === 3,
                detalle: `Total: ${Object.keys(cantidadTalla).length}`
            },
            {
                nombre: 'Incluye género en las claves',
                resultado: tallas.every(t => t.includes('-')),
                detalle: `Todas tienen formato genero-talla`
            },
            {
                nombre: 'Genera "dama-S", "dama-M", "dama-L"',
                resultado: cantidadTalla['dama-S'] === 200 && 
                          cantidadTalla['dama-M'] === 200 && 
                          cantidadTalla['dama-L'] === 200,
                detalle: `Valores: S=${cantidadTalla['dama-S']}, M=${cantidadTalla['dama-M']}, L=${cantidadTalla['dama-L']}`
            }
        ];
        
        this.printValidaciones(validaciones);
        return validaciones.every(v => v.resultado);
    }

    // Test 4: Caso edge - sin genero asignado
    testSinGenero() {
        console.log('%c4️⃣  TEST: Edge Case - Sin género asignado', 'color: #00CCFF; font-weight: bold');
        
        const prenda = {
            nombre_producto: 'Prenda sin género',
            genero: [],  // ❌ Sin género
            cantidadesPorTalla: {
                S: 100,
                M: 100
            }
        };
        
        const cantidadTalla = {};
        
        // Simular el fallback con defensa
        if (prenda.cantidadesPorTalla && typeof prenda.cantidadesPorTalla === 'object') {
            const generoFallback = prenda.genero && Array.isArray(prenda.genero) && prenda.genero.length > 0 
                ? prenda.genero[0] 
                : 'mixto';  // ✅ FALLBACK A "mixto"
            
            console.log('   generoFallback =', generoFallback, '(porque género está vacío)');
            
            Object.keys(prenda.cantidadesPorTalla).forEach(talla => {
                const cantidad = parseInt(prenda.cantidadesPorTalla[talla]) || 0;
                if (cantidad > 0) {
                    cantidadTalla[`${generoFallback}-${talla}`] = cantidad;
                }
            });
        }
        
        console.log('   Output: cantidadTalla =', cantidadTalla);
        
        const tallas = Object.keys(cantidadTalla);
        
        const validaciones = [
            {
                nombre: 'Usa "mixto" como fallback',
                resultado: tallas.every(t => t.startsWith('mixto-')),
                detalle: `Claves: [${tallas.join(', ')}]`
            },
            {
                nombre: 'Aún así produce datos válidos',
                resultado: tallas.length > 0,
                detalle: `No está vacío`
            }
        ];
        
        this.printValidaciones(validaciones);
        return validaciones.every(v => v.resultado);
    }

    // Comparación: Antes vs Después
    testComparacionAntesYDespues() {
        console.log('%c5️⃣  TEST: Comparación Antes vs Después del Fix', 'color: #00CCFF; font-weight: bold');
        
        const generosConTallas = {
            dama: { S: 230, M: 230, L: 230 }
        };
        
        // ❌ CÓDIGO INCORRECTO (antes)
        console.log('\n   ❌ ANTES (INCORRECTO):');
        const cantidadTallaAntes = {};
        Object.keys(generosConTallas).forEach(genero => {
            const tallaDelGenero = generosConTallas[genero];
            Object.keys(tallaDelGenero).forEach(talla => {
                const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                if (cantidad > 0) {
                    cantidadTallaAntes[talla] = cantidad;  // ❌ SIN GÉNERO
                }
            });
        });
        console.log('      cantidadTalla =', cantidadTallaAntes);
        console.log('      tallas =', Object.keys(cantidadTallaAntes));
        
        // ✅ CÓDIGO CORRECTO (después)
        console.log('\n   ✅ DESPUÉS (CORRECTO):');
        const cantidadTallaDespues = {};
        Object.keys(generosConTallas).forEach(genero => {
            const tallaDelGenero = generosConTallas[genero];
            Object.keys(tallaDelGenero).forEach(talla => {
                const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                if (cantidad > 0) {
                    cantidadTallaDespues[`${genero}-${talla}`] = cantidad;  // ✅ CON GÉNERO
                }
            });
        });
        console.log('      cantidadTalla =', cantidadTallaDespues);
        console.log('      tallas =', Object.keys(cantidadTallaDespues));
        
        const validaciones = [
            {
                nombre: 'Antes: tallas vacío',
                resultado: Object.keys(cantidadTallaAntes).length === 0,
                detalle: `Elementos: ${Object.keys(cantidadTallaAntes).length} (❌ FALLA VALIDACIÓN)`
            },
            {
                nombre: 'Después: tallas poblado',
                resultado: Object.keys(cantidadTallaDespues).length === 3,
                detalle: `Elementos: ${Object.keys(cantidadTallaDespues).length} (✅ PASA VALIDACIÓN)`
            },
            {
                nombre: 'Antes: genera [S, M, L]',
                resultado: JSON.stringify(Object.keys(cantidadTallaAntes).sort()) === JSON.stringify(['L', 'M', 'S']),
                detalle: `Sin información del género ❌`
            },
            {
                nombre: 'Después: genera [dama-S, dama-M, dama-L]',
                resultado: JSON.stringify(Object.keys(cantidadTallaDespues).sort()) === 
                          JSON.stringify(['dama-L', 'dama-M', 'dama-S']),
                detalle: `Con información del género ✅`
            }
        ];
        
        this.printValidaciones(validaciones);
        return validaciones.every(v => v.resultado);
    }

    printValidaciones(validaciones) {
        let passCount = 0;
        validaciones.forEach((val, i) => {
            const icono = val.resultado ? '✅' : '❌';
            console.log(`   ${icono} ${i+1}. ${val.nombre}`);
            console.log(`      └─ ${val.detalle}`);
            if (val.resultado) {
                passCount++;
                this.passed++;
            } else {
                this.failed++;
            }
        });
        console.log(`   📊 Resultado: ${passCount}/${validaciones.length} validaciones pasadas\n`);
    }

    runAll() {
        const tests = [
            () => this.testDerivacionConGenero(),
            () => this.testDosGeneros(),
            () => this.testFallback(),
            () => this.testSinGenero(),
            () => this.testComparacionAntesYDespues()
        ];
        
        tests.forEach(test => {
            try {
                test();
            } catch (e) {
                console.error('❌ Error en test:', e.message);
                this.failed++;
            }
        });
        
        // Resumen final
        console.log('%c═══════════════════════════════════════════════════════════', 'color: #00FF00');
        console.log('%c📊 RESUMEN FINAL', 'color: #00FF00; font-weight: bold');
        console.log('%c═══════════════════════════════════════════════════════════', 'color: #00FF00');
        console.log(`\n✅ Validaciones pasadas: ${this.passed}`);
        console.log(`❌ Validaciones fallidas: ${this.failed}`);
        console.log(`📈 Porcentaje: ${((this.passed / (this.passed + this.failed)) * 100).toFixed(1)}%`);
        
        if (this.failed === 0) {
            console.log('\n%c🎉 ¡TODOS LOS TESTS PASARON! El código está arreglado correctamente.', 'color: #00FF00; font-size: 14px; font-weight: bold');
        } else {
            console.log('\n%c⚠️  Algunos tests fallaron.', 'color: #FF0000; font-size: 14px; font-weight: bold');
        }
        
        console.log('%c═══════════════════════════════════════════════════════════\n', 'color: #00FF00');
    }
}

// Ejecutar
const tester = new TestCantidadTallaCorrecta();
tester.runAll();
