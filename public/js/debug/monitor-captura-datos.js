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
                const icono = val.resultado ? '' : '';


            });

            return validaciones;
        },

        // Simular construcción de generosConTallas
        simulateGenerosConTallas() {
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


                console.table(generosConTallas);
                
                // Validaciones finales
                const esValido = Object.keys(generosConTallas).length > 0 &&
                               Object.values(generosConTallas).every(g => Object.keys(g).length > 0);
                

                
                return generosConTallas;
                
            } catch (error) {

                return null;
            }
        },

        // Mostrar historial de cambios
        showHistory() {
            this.snapshots.forEach((snapshot, i) => {



            });
        },

        // Panel de control
        showHelp() {
            console.clear();

            console.log(`
  window._monitor.validate()               - Validar datos actuales
  window._monitor.simulateGenerosConTallas() - Simular construcción de generosConTallas
  window._monitor.showHistory()            - Mostrar historial
  window._monitor.captureSnapshot()        - Capturar snapshot
  window._monitor.showHelp()              - Ver esta ayuda
            `);

  4. Selecciona tallas y cantidades
  5. Ejecuta: window._monitor.validate()
  6. Verifica que todo sea 
            `);
            console.log(`
   tallasPorGenero debe ser un array con al menos 1 elemento
   cantidadesPorTalla debe tener claves (tallas) con valores > 0
   Al simular, generosConTallas debe estar poblado
   Si ve objetos vacíos {} o arrays vacíos [], hay un problema
            `);


        }
    };

    // Guardar en window para acceso global
    window._monitor = monitor;

    // Mostrar help inicial
    monitor.showHelp();

    // Interceptar cambios (opcional - más avanzado)



})();

