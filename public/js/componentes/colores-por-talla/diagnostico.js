/**
 * ================================================
 * SCRIPT DE DIAGNÓSTICO - Colores por Talla
 * ================================================
 * 
 * Ejecutar en consola: diagnosticarSistema()
 */

window.diagnosticarSistema = function() {
    console.clear();
    console.log('%c========== DIAGNÓSTICO DEL SISTEMA DE COLORES POR TALLA ==========', 'color: #0066cc; font-weight: bold; font-size: 14px;');
    
    // 1. Verificar módulos disponibles
    console.log('\n%c1️⃣ MÓDULOS DISPONIBLES:', 'color: #1f2937; font-weight: bold; font-size: 12px;');
    console.log({
        'ColoresPorTalla': typeof window.ColoresPorTalla,
        'WizardManager': typeof window.WizardManager,
        'UIRenderer': typeof window.UIRenderer,
        'AsignacionManager': typeof window.AsignacionManager,
        'StateManager': typeof window.StateManager
    });
    
    // 2. Verificar funciones de compatibilidad
    console.log('\n%c2️⃣ FUNCIONES DE COMPATIBILIDAD:', 'color: #1f2937; font-weight: bold; font-size: 12px;');
    console.log({
        'toggleVistaAsignacionColores': typeof window.toggleVistaAsignacionColores,
        'wizardSeleccionarGenero': typeof window.wizardSeleccionarGenero,
        'agregarColorPersonalizado': typeof window.agregarColorPersonalizado,
        'wizardReset': typeof window.wizardReset
    });
    
    // 3. Verificar elementos DOM cruciales
    console.log('\n%c3️⃣ ELEMENTOS DOM:', 'color: #1f2937; font-weight: bold; font-size: 12px;');
    const elementosCruciales = {
        'modal-agregar-prenda-nueva': document.getElementById('modal-agregar-prenda-nueva'),
        'vista-tabla-telas': document.getElementById('vista-tabla-telas'),
        'vista-asignacion-colores': document.getElementById('vista-asignacion-colores'),
        'btn-asignar-colores-tallas': document.getElementById('btn-asignar-colores-tallas'),
        'wizard-paso-1': document.getElementById('wizard-paso-1'),
        'wizard-paso-2': document.getElementById('wizard-paso-2'),
        'wizard-paso-3': document.getElementById('wizard-paso-3'),
        'wzd-btn-siguiente': document.getElementById('wzd-btn-siguiente'),
        'wzd-btn-atras': document.getElementById('wzd-btn-atras'),
        'btn-guardar-asignacion': document.getElementById('btn-guardar-asignacion')
    };
    
    Object.entries(elementosCruciales).forEach(([nombre, elemento]) => {
        console.log(`  ${nombre}: ${elemento ? ' Encontrado' : ' NO ENCONTRADO'}`);
    });
    
    // 4. Verificar estado del display
    console.log('\n%c4️⃣ ESTADO DEL DISPLAY:', 'color: #1f2937; font-weight: bold; font-size: 12px;');
    const vistaTablaTelas = document.getElementById('vista-tabla-telas');
    const vistaAsignacion = document.getElementById('vista-asignacion-colores');
    
    console.log({
        'vista-tabla-telas display': vistaTablaTelas?.style.display || 'no definido',
        'vista-asignacion-colores display': vistaAsignacion?.style.display || 'no definido',
        'Tab activa': vistaTablaTelas?.style.display === 'none' ? 'Asignación' : 'Tabla de Telas'
    });
    
    // 5. Resultado final
    console.log('\n%c5️⃣ RESULTADO:', 'color: #1f2937; font-weight: bold; font-size: 12px;');
    const todosOK = elementosCruciales['vista-tabla-telas'] && 
                    elementosCruciales['vista-asignacion-colores'] && 
                    elementosCruciales['btn-asignar-colores-tallas'] &&
                    typeof window.toggleVistaAsignacionColores === 'function';
    
    if (todosOK) {
        console.log('%c SISTEMA LISTO - El modal está cargado y funcional', 'color: #22c55e; font-weight: bold;');
    } else {
        console.log('%c COMPONENTES FALTANTES - Verifica arriba qué no está disponible', 'color: #f59e0b; font-weight: bold;');
    }
    
    console.log('\n%c========== FIN DEL DIAGNÓSTICO ==========', 'color: #0066cc; font-weight: bold; font-size: 14px;');
};

// Función para probar el toggle
window.pruebaToggle = function() {
    console.log('\n%c🎯 PRUEBA DE TOGGLE MANUAL:', 'color: #fc6500; font-weight: bold; font-size: 12px;');
    
    const vistaTablaTelas = document.getElementById('vista-tabla-telas');
    const vistaAsignacion = document.getElementById('vista-asignacion-colores');
    
    console.log('Estado ANTES:', {
        'tabla': vistaTablaTelas?.style.display || 'no definido',
        'asignacion': vistaAsignacion?.style.display || 'no definido'
    });
    
    console.log('Ejecutando: window.toggleVistaAsignacionColores()...\n');
    
    if (typeof window.toggleVistaAsignacionColores === 'function') {
        const resultado = window.toggleVistaAsignacionColores();
        
        setTimeout(() => {
            console.log('Estado DESPUÉS:', {
                'tabla': vistaTablaTelas?.style.display || 'no definido',
                'asignacion': vistaAsignacion?.style.display || 'no definido',
                'Resultado de función': resultado
            });
        }, 100);
    } else {
        console.error(' toggleVistaAsignacionColores NO es una función');
    }
};

// Función para hacer toggle manual directo
window.hacerToggleManual = function() {
    console.log('\n%c🎯 TOGGLE MANUAL DIRECTO:', 'color: #fc6500; font-weight: bold;');
    
    const vistaTablaTelas = document.getElementById('vista-tabla-telas');
    const vistaAsignacion = document.getElementById('vista-asignacion-colores');
    
    if (!vistaTablaTelas || !vistaAsignacion) {
        console.error(' Elementos no encontrados');
        return;
    }
    
    const esVistaAsignacionActiva = vistaAsignacion.style.display !== 'none';
    
    if (esVistaAsignacionActiva) {
        vistaTablaTelas.style.display = 'block';
        vistaAsignacion.style.display = 'none';
        console.log(' Toggle realizado: Tabla VISIBLE, Asignación OCULTA');
    } else {
        vistaTablaTelas.style.display = 'none';
        vistaAsignacion.style.display = 'block';
        console.log(' Toggle realizado: Tabla OCULTA, Asignación VISIBLE');
    }
};

// Función para verificar solo DOM
window.verificarDOM = function() {
    console.log('\n%c VERIFICACIÓN RÁPIDA DE DOM:', 'color: #0066cc; font-weight: bold;');
    console.log({
        'vista-tabla-telas': !!document.getElementById('vista-tabla-telas'),
        'vista-asignacion-colores': !!document.getElementById('vista-asignacion-colores'),
        'btn-asignar-colores-tallas': !!document.getElementById('btn-asignar-colores-tallas'),
        'modal principal': !!document.getElementById('modal-agregar-prenda-nueva')
    });
};

// Registrar cuando los scripts cargan
console.log('%c Diagnóstico.js cargado. Comandos disponibles: diagnosticarSistema(), pruebaToggle(), hacerToggleManual(), verificarDOM()', 'color: #22c55e; font-weight: bold;');

