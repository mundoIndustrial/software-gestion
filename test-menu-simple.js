// Versión simplificada del menú para procesos
function probarMenuSimpleProcesos() {
    console.log('[MENÚ SIMPLE] 🧪 Creando menú simple para procesos...');
    
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        
        if (preview) {
            console.log(`[MENÚ SIMPLE] 🔍 Configurando menú simple para proceso ${i}`);
            
            // Remover eventos existentes
            preview.oncontextmenu = null;
            
            // Agregar nuevo evento contextmenu con menú simple
            preview.addEventListener('contextmenu', function(e) {
                console.log(`[MENÚ SIMPLE] 🎉 Contextmenu detectado en proceso ${i}`);
                
                e.preventDefault();
                e.stopPropagation();
                
                // Crear menú muy simple
                const menu = document.createElement('div');
                menu.style.position = 'fixed';
                menu.style.left = e.clientX + 'px';
                menu.style.top = e.clientY + 'px';
                menu.style.background = 'red';
                menu.style.color = 'white';
                menu.style.padding = '10px';
                menu.style.borderRadius = '5px';
                menu.style.zIndex = '999999';
                menu.style.fontSize = '14px';
                menu.style.cursor = 'pointer';
                menu.innerHTML = `📋 Pegar imagen ${i}`;
                
                console.log(`[MENÚ SIMPLE] ✅ Menú simple creado para proceso ${i}`);
                
                // Agregar al DOM
                document.body.appendChild(menu);
                console.log(`[MENÚ SIMPLE] 📌 Menú agregado al DOM`);
                
                // Cerrar al hacer clic
                menu.addEventListener('click', function() {
                    console.log(`[MENÚ SIMPLE] 🖱️ Click en menú proceso ${i}`);
                    alert(`Menú simple funciona para proceso ${i}`);
                    document.body.removeChild(menu);
                });
                
                // Cerrar al hacer clic fuera
                setTimeout(() => {
                    document.addEventListener('click', function closeMenu(e) {
                        if (!menu.contains(e.target) && document.body.contains(menu)) {
                            console.log(`[MENÚ SIMPLE] 🗑️ Cerrando menú proceso ${i}`);
                            document.body.removeChild(menu);
                            document.removeEventListener('click', closeMenu);
                        }
                    });
                }, 100);
            });
            
            console.log(`[MENÚ SIMPLE] ✅ Configuración completada para proceso ${i}`);
        }
    }
    
    console.log('[MENÚ SIMPLE] 🏁 Prueba completada. Haz clic derecho en los previews.');
}

// Ejecutar la prueba
probarMenuSimpleProcesos();
