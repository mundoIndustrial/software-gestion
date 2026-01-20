/**
 * PRUEBA RÁPIDA - Validar captura de datos en tiempo real
 * Ejecuta esto en la consola del navegador mientras estás usando el formulario
 * 
 * Instrucciones:
 * 1. Abre la consola (F12 → Console)
 * 2. Copia y pega este código
 * 3. Llena el formulario y agrega una prenda
 * 4. Verifica los logs de la consola
 */

(function() {
    'use strict';

    console.clear();
    console.log('%c🧪 INICIANDO MONITOR DE DATOS EN TIEMPO REAL', 'color: #FF6600; font-size: 16px; font-weight: bold');

    // Crear un objeto para monitorear
    const monitor = {
        snapshots: [],
        
        // Capturar snapshot actual
        captureSnapshot() {
            const snapshot = {
                timestamp: new Date().toLocaleTimeString(),
                tallasPorGenero: JSON.parse(JSON.stringify(window.tallasPorGenero || [])),
                cantidadesPorTalla: JSON.parse(JSON.stringify(window.cantidadesPorTalla || {})),
                tallasPorGeneroTemp: JSON.parse(JSON.stringify(window.tallasPorGeneroTemp || [])),
                cantidadesTallaTemp: JSON.parse(JSON.stringify(window.cantidadesTallaTemp || {})),
                tallasSeleccionadas: JSON.parse(JSON.stringify(window.tallasSeleccionadas || {})),
            };
            
            this.snapshots.push(snapshot);
            return snapshot;
        },

        // Validar estructura actual
        validate() {
            console.log('\n%c VALIDACIÓN DE DATOS ACTUALES', 'color: #00CCFF; font-weight: bold');
            
            const snapshot = this.captureSnapshot();
            
            const validaciones = [
                {
                    nombre: 'window.tallasPorGenero existe',
                    resultado: snapshot.tallasPorGenero && Array.isArray(snapshot.tallasPorGenero),
                    valor: snapshot.tallasPorGenero
                },
                {
                    nombre: 'window.cantidadesPorTalla existe',
                    resultado: snapshot.cantidadesPorTalla && Object.keys(snapshot.cantidadesPorTalla).length > 0,
                    valor: snapshot.cantidadesPorTalla
                },
                {
                    nombre: 'Al menos un género seleccionado',
                    resultado: snapshot.tallasPorGenero.length > 0,
                    valor: snapshot.tallasPorGenero.length
                },
                {
                    nombre: 'Al menos una talla con cantidad > 0',
                    resultado: Object.values(snapshot.cantidadesPorTalla).some(v => v > 0),
                    valor: Object.keys(snapshot.cantidadesPorTalla).filter(k => snapshot.cantidadesPorTalla[k] > 0)
                }
            ];

            validaciones.forEach((val, i) => {
                const icono = val.resultado ? '' : '⚠️';
                console.log(`${icono} ${i+1}. ${val.nombre}`);
                console.log(`   └─ Valor:`, val.valor);
            });

            return validaciones;
        },

        // Simular construcción de generosConTallas
        simulateGenerosConTallas() {
            console.log('\n%c🔨 SIMULANDO CONSTRUCCIÓN DE generosConTallas', 'color: #FF00FF; font-weight: bold');
            
            const snapshot = this.captureSnapshot();
            
            try {
                const generosConTallas = {};
                
                snapshot.tallasPorGenero.forEach(tallaData => {
                    const generoKey = tallaData.genero;
                    generosConTallas[generoKey] = {};
                    
                    if (tallaData.tallas && Array.isArray(tallaData.tallas)) {
                        tallaData.tallas.forEach(talla => {
                            const cantidad = snapshot.cantidadesPorTalla[talla] || 0;
                            if (cantidad > 0) {
                                generosConTallas[generoKey][talla] = cantidad;
                            }
                        });
                    }
                });

                console.log(' generosConTallas simulado:');
                console.table(generosConTallas);
                
                // Validaciones finales
                const esValido = Object.keys(generosConTallas).length > 0 &&
                               Object.values(generosConTallas).every(g => Object.keys(g).length > 0);
                
                console.log(`\n${esValido ? '' : ''} Estructura válida:`, esValido);
                
                return generosConTallas;
                
            } catch (error) {
                console.error(' Error al simular:', error.message);
                return null;
            }
        },

        // Mostrar historial de cambios
        showHistory() {
            console.log('\n%c📊 HISTORIAL DE CAPTURAS', 'color: #00FF00; font-weight: bold');
            
            this.snapshots.forEach((snapshot, i) => {
                console.log(`\n🕐 Captura ${i+1} - ${snapshot.timestamp}`);
                console.log('  tallasPorGenero:', snapshot.tallasPorGenero);
                console.log('  cantidadesPorTalla:', snapshot.cantidadesPorTalla);
            });
        },

        // Panel de control
        showHelp() {
            console.clear();
            console.log('%c═══════════════════════════════════════════════════════════', 'color: #00CCFF');
            console.log('%c🧪 MONITOR DE CAPTURA DE DATOS', 'color: #00CCFF; font-size: 14px; font-weight: bold');
            console.log('%c═══════════════════════════════════════════════════════════', 'color: #00CCFF');
            
            console.log('\n%c📌 COMANDOS DISPONIBLES:', 'color: #FFD700; font-weight: bold');
            console.log(`
  window._monitor.validate()               - Validar datos actuales
  window._monitor.simulateGenerosConTallas() - Simular construcción de generosConTallas
  window._monitor.showHistory()            - Mostrar historial
  window._monitor.captureSnapshot()        - Capturar snapshot
  window._monitor.showHelp()              - Ver esta ayuda
            `);

            console.log('%c📝 CÓMO USAR:', 'color: #FFD700; font-weight: bold');
            console.log(`
  1. Copia y pega este código en la consola
  2. Llena el formulario de crear prenda
  3. Selecciona un género (Dama/Caballero)
  4. Selecciona tallas y cantidades
  5. Ejecuta: window._monitor.validate()
  6. Verifica que todo sea 
            `);

            console.log('%c🎯 QUÉ BUSCAR:', 'color: #FFD700; font-weight: bold');
            console.log(`
   tallasPorGenero debe ser un array con al menos 1 elemento
   cantidadesPorTalla debe tener claves (tallas) con valores > 0
   Al simular, generosConTallas debe estar poblado
   Si ve objetos vacíos {} o arrays vacíos [], hay un problema
            `);

            console.log('%c═══════════════════════════════════════════════════════════\n', 'color: #00CCFF');
        }
    };

    // Guardar en window para acceso global
    window._monitor = monitor;

    // Mostrar help inicial
    monitor.showHelp();

    // Interceptar cambios (opcional - más avanzado)
    console.log('\n%c💡 TIP: Ejecuta window._monitor.validate() después de agregar una prenda', 'color: #00FF00');
    console.log('%c💡 TIP: Ejecuta window._monitor.simulateGenerosConTallas() para simular la construcción\n', 'color: #00FF00');

})();
