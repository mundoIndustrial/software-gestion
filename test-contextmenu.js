// Prueba simple de contextmenu para procesos
function probarContextmenuProcesos() {
    console.log('[PRUEBA] 🧪 Iniciando prueba de contextmenu...');
    
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        
        if (preview) {
            console.log(`[PRUEBA] 🔍 Configurando contextmenu para proceso ${i}`);
            
            // Remover cualquier evento existente
            preview.oncontextmenu = null;
            
            // Agregar nuevo evento contextmenu
            preview.addEventListener('contextmenu', function(e) {
                console.log(`[PRUEBA] 🎉 ¡Contextmenu detectado en proceso ${i}!`);
                e.preventDefault();
                e.stopPropagation();
                
                // Crear menú simple de prueba
                const menu = document.createElement('div');
                menu.style.cssText = `
                    position: fixed;
                    left: ${e.clientX}px;
                    top: ${e.clientY}px;
                    background: white;
                    border: 2px solid #3b82f6;
                    border-radius: 8px;
                    padding: 10px;
                    z-index: 999999;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    font-size: 14px;
                `;
                menu.innerHTML = `
                    <div style="padding: 5px 10px; cursor: pointer;" onclick="alert('Proceso ${i} - Pegar imagen')">
                        📋 Pegar imagen ${i}
                    </div>
                `;
                
                document.body.appendChild(menu);
                
                // Cerrar al hacer clic fuera
                setTimeout(() => {
                    document.addEventListener('click', function closeMenu() {
                        if (document.body.contains(menu)) {
                            document.body.removeChild(menu);
                            document.removeEventListener('click', closeMenu);
                        }
                    });
                }, 100);
                
                console.log(`[PRUEBA] ✅ Menú creado para proceso ${i}`);
            });
            
            console.log(`[PRUEBA] ✅ Contextmenu configurado para proceso ${i}`);
        } else {
            console.log(`[PRUEBA] ❌ Proceso ${i} no encontrado`);
        }
    }
    
    console.log('[PRUEBA] 🏁 Prueba completada. Intenta hacer clic derecho en los previews.');
}

// Ejecutar la prueba
probarContextmenuProcesos();
