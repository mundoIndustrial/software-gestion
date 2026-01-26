/**
 * CONFIGURACIÓN DINÁMICA DEL STEPPER
 * Ajusta qué pasos se muestran según el tipo de cotización (tipo parámetro en URL)
 */

(function() {
    'use strict';

    /**
     * Obtener el tipo de cotización desde URL
     * @returns {string} 'P', 'B', 'RF', 'PB'
     */
    function obtenerTipoCotizacionDesdeLaURL() {
        const params = new URLSearchParams(window.location.search);
        return params.get('tipo') || 'PB'; // Default a combinada
    }

    /**
     * Configurar el stepper según el tipo de cotización
     */
    function configurarStepperPorTipo(tipo) {
        console.log(' Configurando stepper para tipo:', tipo);

        const step3 = document.getElementById('step-3');
        const step4 = document.getElementById('step-4');
        const stepLine3 = document.getElementById('step-line-3');
        const stepLine4 = document.getElementById('step-line-4');
        const step5Number = document.getElementById('step-5-number');
        const paso3 = document.querySelector('.form-step[data-step="3"]');
        const paso4 = document.querySelector('.form-step[data-step="4"]');

        // Por defecto, ocultar pasos 3 y 4
        if (step3) step3.style.display = 'none';
        if (step4) step4.style.display = 'none';
        if (stepLine3) stepLine3.style.display = 'none';
        if (stepLine4) stepLine4.style.display = 'none';
        if (paso3) paso3.style.display = 'none';
        if (paso4) paso4.style.display = 'none';

        // Ajustar el número del paso revisar
        if (step5Number) {
            step5Number.textContent = '3'; // Para prenda: paso 3 es revisar
        }

        // Según el tipo, mostrar los pasos necesarios
        switch (tipo) {
            case 'P':
                // PRENDA SOLO: Paso 1, 2, Revisar
                console.log('  → Tipo PRENDA: Solo pasos 1, 2 y revisar');
                // El paso 3 (logo) y 4 (reflectivo) ya están ocultos
                // El paso 5 (revisar) ya está visible y es el paso 3
                break;

            case 'B':
                // LOGO SOLO: Paso 1, 2, Logo (ahora es 3), Revisar (ahora es 4)
                console.log('  → Tipo LOGO: Pasos 1, 2, 3(Logo) y revisar');
                if (step3) step3.style.display = '';
                if (stepLine3) stepLine3.style.display = '';
                if (paso3) paso3.style.display = '';

                if (step5Number) {
                    step5Number.textContent = '4'; // Para logo: paso 4 es revisar
                }
                break;

            case 'RF':
                // REFLECTIVO SOLO: Paso 1, 2, Reflectivo (visualmente es 3), Revisar (visualmente es 4)
                console.log('  → Tipo REFLECTIVO: Pasos 1, 2, 3(Reflectivo) y revisar');
                
                // Ocultamos el paso 3 (logo) pero mostramos el paso 4 (reflectivo)
                if (step3) step3.style.display = 'none';
                if (stepLine3) stepLine3.style.display = 'none';
                if (paso3) paso3.style.display = 'none';

                if (step4) step4.style.display = '';
                if (stepLine4) stepLine4.style.display = '';
                if (paso4) paso4.style.display = '';

                // Renombrar el paso 4 a "Reflectivo" visualmente (cambiar número a 3)
                const step4Number = step4?.querySelector('.step-number');
                const step4Label = step4?.querySelector('.step-label');
                if (step4Number) step4Number.textContent = '3';
                if (step4Label) step4Label.textContent = 'REFLECTIVO';

                if (step5Number) {
                    step5Number.textContent = '4'; // Para reflectivo: paso 4 es revisar
                }
                break;

            case 'PB':
            default:
                // COMBINADA: Paso 1, 2, 3 (Logo), 4 (Reflectivo), Revisar
                console.log('  → Tipo COMBINADA: Todos los pasos');
                if (step3) step3.style.display = '';
                if (step4) step4.style.display = '';
                if (stepLine3) stepLine3.style.display = '';
                if (stepLine4) stepLine4.style.display = '';
                if (paso3) paso3.style.display = '';
                if (paso4) paso4.style.display = '';

                if (step5Number) {
                    step5Number.textContent = '5'; // Para combinada: paso 5 es revisar
                }
                break;
        }

        // Guardar el tipo en variable global
        window.tipoCotizacionDesdeURL = tipo;
    }

    /**
     * Ajustar la función irAlPaso para saltarse pasos ocultos
     */
    function patchFuncionIrAlPaso() {
        const funcionOriginal = window.irAlPaso;

        window.irAlPaso = function(paso) {
            const tipo = window.tipoCotizacionDesdeURL || 'PB';

            // Lógica para saltar pasos según el tipo
            if (tipo === 'P') {
                // Para prenda: solo pasos 1, 2, 5
                if (paso === 3) paso = 5; // Logo -> Revisar
                if (paso === 4) paso = 5; // Reflectivo -> Revisar
            } else if (tipo === 'B') {
                // Para logo: pasos 1, 2, 3(logo), 4(revisar)
                if (paso === 4) paso = 5; // Reflectivo -> Revisar
            } else if (tipo === 'RF') {
                // Para reflectivo: pasos 1, 2, 3(reflectivo), 4(revisar)
                if (paso === 3) paso = 4; // Logo -> Reflectivo
            }
            // Para PB (combinada), no hay restricciones

            // Llamar la función original
            funcionOriginal(paso);
        };
    }

    /**
     * Ajustar botones en paso 2 según tipo de cotización
     * - Para prenda: Next en paso 2 va directo a revisar (paso 5)
     * - Para logo/reflectivo: Next va al paso 3
     * - Para combinada: Next va al paso 3
     */
    function ajustarBotonesPaso2() {
        const tipo = window.tipoCotizacionDesdeURL || 'PB';

        const btnNextPaso2 = document.querySelector('.form-step[data-step="2"] .btn-next');
        if (btnNextPaso2) {
            if (tipo === 'P') {
                // Para prenda, el siguiente va directo a revisar
                btnNextPaso2.setAttribute('onclick', 'if(typeof irAlPaso === "function") irAlPaso(5)');
                btnNextPaso2.textContent = 'REVISAR ➜';
            } else if (tipo === 'B') {
                // Para logo: va al paso 3 (logo)
                btnNextPaso2.setAttribute('onclick', 'if(typeof irAlPaso === "function") irAlPaso(3)');
                btnNextPaso2.textContent = 'SIGUIENTE ➜';
            } else if (tipo === 'RF') {
                // Para reflectivo: va al paso 4 (reflectivo)
                btnNextPaso2.setAttribute('onclick', 'if(typeof irAlPaso === "function") irAlPaso(4)');
                btnNextPaso2.textContent = 'SIGUIENTE ➜';
            }
        }
    }

    /**
     * Ajustar botones en paso 3 según tipo de cotización
     * - Para logo: Next va al paso 5 (revisar)
     * - Para combinada: Next va al paso 4 (reflectivo)
     * 
     * Nota: Para RF (reflectivo), el paso 3 está oculto así que no se muestra este botón
     */
    function ajustarBotonesPaso3() {
        const tipo = window.tipoCotizacionDesdeURL || 'PB';

        const btnNextPaso3 = document.querySelector('.form-step[data-step="3"] .btn-next');
        if (btnNextPaso3) {
            if (tipo === 'B') {
                // Para logo: el siguiente va directo a revisar
                btnNextPaso3.setAttribute('onclick', 'if(typeof irAlPaso === "function") irAlPaso(5)');
                btnNextPaso3.textContent = 'REVISAR ➜';
            }
            // Para combinada y reflectivo, se queda con onclick="irAlPaso(4)" que es lo por defecto
        }
    }

    /**
     * Ajustar botones en paso 4 según tipo de cotización
     * - Para reflectivo: Anterior va al paso 2 (saltando paso 3 que está oculto)
     * - Para combinada: Anterior va al paso 3
     */
    function ajustarBotonesPaso4() {
        const tipo = window.tipoCotizacionDesdeURL || 'PB';

        const btnPrevPaso4 = document.querySelector('.form-step[data-step="4"] .btn-prev');
        if (btnPrevPaso4) {
            if (tipo === 'RF') {
                // Para reflectivo: anterior va al paso 2 (saltando paso 3)
                btnPrevPaso4.setAttribute('onclick', 'if(typeof irAlPaso === "function") irAlPaso(2)');
            }
            // Para combinada, se queda con onclick="irAlPaso(3)" que es lo por defecto
        }

        // Ajustar botón siguiente
        const btnNextPaso4 = document.querySelector('.form-step[data-step="4"] .btn-next');
        if (btnNextPaso4) {
            if (tipo === 'RF') {
                // Para reflectivo: siguiente va al paso 5 (revisar)
                btnNextPaso4.setAttribute('onclick', 'if(typeof irAlPaso === "function") irAlPaso(5)');
                btnNextPaso4.textContent = 'REVISAR ➜';
            }
            // Para combinada, se queda con onclick="irAlPaso(5)" que es lo por defecto
        }
    }

    /**
     * Ajustar el título del paso 5 (Revisar) según el tipo de cotización
     */
    function ajustarTituloPaso5() {
        const tipo = window.tipoCotizacionDesdeURL || 'PB';
        const paso5Header = document.querySelector('.form-step[data-step="5"] .step-header h2');

        if (paso5Header) {
            if (tipo === 'P') {
                paso5Header.textContent = 'PASO 3: REVISAR COTIZACIÓN';
            } else if (tipo === 'B') {
                paso5Header.textContent = 'PASO 4: REVISAR COTIZACIÓN';
            } else if (tipo === 'RF') {
                paso5Header.textContent = 'PASO 4: REVISAR COTIZACIÓN';
            } else {
                // PB (combinada)
                paso5Header.textContent = 'PASO 5: REVISAR COTIZACIÓN';
            }
        }
    }

    /**
     * Inicializar cuando el DOM esté listo
     */
    document.addEventListener('DOMContentLoaded', function() {
        const tipo = obtenerTipoCotizacionDesdeLaURL();
        configurarStepperPorTipo(tipo);
        ajustarBotonesPaso2();
        ajustarBotonesPaso3();
        ajustarBotonesPaso4();
        ajustarTituloPaso5();
        patchFuncionIrAlPaso();

        console.log('✅ Stepper configurado para tipo:', tipo);
    });
})();
