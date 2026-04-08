/**
 * INPUT FORMATTER INITIALIZATION
 * ═══════════════════════════════════════════════════════════════
 * Handles uppercase conversion for input fields with cursor position preservation
 * 
 * Functionality:
 * - Converts text input to uppercase in real-time
 * - Preserves cursor position during conversion
 * - Applies to: cliente, asesora, forma_de_pago, observaciones
 * - Multiple event handlers for comprehensive coverage (input, keyup, change, paste, blur)
 * - Fallback interval check for 10 seconds (resource-friendly)
 * 
 * Global Functions Exposed:
 * - window.InitializeInputFormatters() - Setup uppercase for all inputs
 */

(function() {
    'use strict';

    /**
     * Configure an input field to enforce uppercase with cursor preservation
     * @param {string} inputId - The ID of the input element to configure
     */
    function setupUpperCaseInput(inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            console.log(' Configurando input para mayúsculas:', inputId);
            
            // Función para convertir a mayúsculas preservando posición del cursor
            function forceUpperCase() {
                const currentValue = input.value;
                const upperValue = currentValue.toUpperCase();
                if (currentValue !== upperValue) {
                    // Guardar posición del cursor
                    const start = input.selectionStart;
                    const end = input.selectionEnd;
                    
                    // Actualizar valor
                    input.value = upperValue;
                    
                    // Restaurar posición del cursor
                    input.setSelectionRange(start, end);
                    
                    console.log(' Convertido a mayúsculas:', currentValue, '→', upperValue);
                }
            }
            
            // Eventos para cubrir todos los casos
            input.addEventListener('input', forceUpperCase);
            input.addEventListener('keyup', forceUpperCase);
            input.addEventListener('change', forceUpperCase);
            input.addEventListener('paste', function(e) {
                setTimeout(forceUpperCase, 10);
            });
            input.addEventListener('blur', forceUpperCase);
            
            // Convertir valor inicial si existe
            if (input.value) {
                input.value = input.value.toUpperCase();
                console.log(' Valor inicial convertido:', input.value);
            }
            
            // Forzar mayúsculas cada segundo por si acaso
            const intervalId = setInterval(forceUpperCase, 1000);
            
            // Limpiar intervalo después de 10 segundos para no consumir recursos
            setTimeout(() => clearInterval(intervalId), 10000);
        } else {
            console.warn(' Input no encontrado:', inputId);
        }
    }

    /**
     * Initialize uppercase formatters for all required input fields
     */
    window.InitializeInputFormatters = function() {
        console.log('[input-formatter-init] Inicializando formatters de mayúsculas...');
        
        // Configurar asesora al cargar
        const asesoraInput = document.getElementById('asesora_editable');
        if (asesoraInput) {
            asesoraInput.value = document.querySelector('meta[name="user-name"]')?.getAttribute('content') || 
                                 (window.asesorActualNombre || '');
        }
        
        // Aplicar a los inputs especificados
        setupUpperCaseInput('cliente_editable');
        setupUpperCaseInput('asesora_editable');
        setupUpperCaseInput('forma_de_pago_editable');
        setupUpperCaseInput('observaciones_editable');
        
        console.log('[input-formatter-init] Formatters de mayúsculas inicializados ✓');
    };

})();
