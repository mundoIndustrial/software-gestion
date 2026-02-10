// Función de prueba para verificar si los elementos de proceso existen y pueden configurarse
function probarConfiguracionProcesos() {
    console.log('[TEST] 🧪 Iniciando prueba de configuración de procesos...');
    
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        console.log(`[TEST] 🔍 Proceso ${i}:`, preview ? '✅ encontrado' : '❌ no encontrado');
        
        if (preview) {
            console.log(`[TEST] 📋 Atributos del proceso ${i}:`, {
                id: preview.id,
                class: preview.className,
                tabindex: preview.getAttribute('tabindex'),
                hasContextmenu: preview.oncontextmenu !== null,
                eventListeners: preview.getAttribute('data-has-listeners')
            });
            
            // Intentar agregar un evento de prueba
            preview.addEventListener('click', function() {
                console.log(`[TEST] 🖱️ Click detectado en proceso ${i}`);
            });
            
            // Intentar agregar evento contextmenu de prueba
            preview.addEventListener('contextmenu', function(e) {
                console.log(`[TEST] 🖱️ Contextmenu detectado en proceso ${i}`);
                e.preventDefault();
                alert(`Menú contextual para proceso ${i}`);
            });
            
            console.log(`[TEST] ✅ Eventos de prueba agregados al proceso ${i}`);
        }
    }
    
    console.log('[TEST] 🏁 Prueba completada. Intenta hacer clic derecho en los previews.');
}

// Ejecutar la prueba
probarConfiguracionProcesos();
