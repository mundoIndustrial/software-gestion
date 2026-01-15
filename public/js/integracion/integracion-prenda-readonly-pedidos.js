/**
 * INTEGRACIÓN - TARJETA DE PRENDA READONLY EN FLUJO DE PEDIDOS
 * 
 * Este archivo proporciona la integración del componente prenda-card-readonly.js
 * en el flujo existente de gestion-items-pedido.js
 * 
 * INSTRUCCIONES DE INSTALACIÓN:
 * 
 * 1. En el layout base (resources/views/layouts/app.blade.php o similar):
 *    Agregar DESPUÉS de SweetAlert2:
 * 
 *    <link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}">
 *    <script src="{{ asset('js/componentes/prenda-card-readonly.js') }}"></script>
 * 
 * 2. En recursos/views/asesores/pedidos/components/prendas-editable.blade.php:
 *    El container ya existe con ID 'prendas-container-editable'
 *    No requiere cambios adicionales.
 * 
 * 3. En public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js:
 *    Ya integrado en el método agregarPrendaNueva()
 *    Automáticamente detecta y usa generarTarjetaPrendaReadOnly si existe.
 */

/**
 * FLUJO AUTOMÁTICO:
 * 
 * 1. Usuario hace click en "Agregar prenda"
 * 2. Se abre modal de prenda nueva
 * 3. Usuario completa datos y hace click en "Guardar"
 * 4. agregarPrendaNueva() se ejecuta:
 *    - Recopila datos del formulario
 *    - Agrega prenda al GestorPrendaSinCotizacion
 *    - Detecta que generarTarjetaPrendaReadOnly existe
 *    - Renderiza la tarjeta con datos de la prenda
 *    - Oculta el placeholder "No hay ítems agregados"
 * 5. Usuario ve la tarjeta readonly con:
 *    - Foto (clickeable para galería)
 *    - Información básica
 *    - 3 secciones expandibles
 *    - Menú de Editar/Eliminar
 */

/**
 * VERIFICAR QUE FUNCIONE - EN CONSOLA DEL NAVEGADOR:
 */

console.log(`
╔═══════════════════════════════════════════════════════════════╗
║  INTEGRACIÓN TARJETA READONLY - PEDIDOS                      ║
╚═══════════════════════════════════════════════════════════════╝

✅ Para verificar que todo está integrado, ejecuta en consola:

// 1. Ver si el componente está cargado
console.log('¿Componente cargado?', typeof generarTarjetaPrendaReadOnly === 'function');

// 2. Ver si el gestor existe
console.log('¿Gestor existe?', !!window.gestorPrendaSinCotizacion);

// 3. Ver prendas agregadas
console.log('Prendas:', window.gestorPrendaSinCotizacion?.obtenerActivas());

// 4. Renderizar manualmente
if (window.generarTarjetaPrendaReadOnly && window.gestorPrendaSinCotizacion) {
    const container = document.getElementById('prendas-container-editable');
    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
    if (prendas.length > 0) {
        container.innerHTML = prendas.map((p, i) => 
            generarTarjetaPrendaReadOnly(p, i)
        ).join('');
    }
}
`);

/**
 * TROUBLESHOOTING:
 */

if (typeof generarTarjetaPrendaReadOnly !== 'function') {
    console.warn(`
⚠️  ADVERTENCIA: generarTarjetaPrendaReadOnly NO está disponible.

Posibles causas:
1. El archivo prenda-card-readonly.js no se está cargando
2. Se carga ANTES de que esté listo el DOM
3. Hay un error en la sintaxis del archivo

SOLUCIÓN:
- Verificar que está en resources/views/layouts/app.blade.php:
  <link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}">
  <script src="{{ asset('js/componentes/prenda-card-readonly.js') }}"></script>
- Verificar que está DESPUÉS de SweetAlert2
- Verificar en DevTools > Network si se carga el archivo
- Verificar en DevTools > Console si hay errores de sintaxis
    `);
}

/**
 * PERSONALIZACIÓN POST-INTEGRACIÓN (Opcional)
 */

// Para cambiar estilos, editar: public/css/componentes/prenda-card-readonly.css
// Para cambiar funcionalidad, editar: public/js/componentes/prenda-card-readonly.js

// Para agregar más interacciones, usar event delegation:
document.addEventListener('custom-event-prenda', (e) => {
    console.log('Evento en prenda:', e.detail);
});

console.log('✅ Integración cargada correctamente');
