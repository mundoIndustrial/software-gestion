/**
 * ⚡ CARGA RÁPIDA - QUÉ INCLUIR EN EL HTML
 * 
 * Agregar esto en tu layout blade (resources/views/layouts/app.blade.php)
 * o en la vista específica del formulario de pedidos.
 * 
 * IMPORTANTE: Debe estar DESPUÉS de SweetAlert2 y FontAwesome
 */

// ============================================
// OPCIÓN 1: EN resources/views/layouts/app.blade.php (RECOMENDADO)
// ============================================

/*
<!-- Estilos del componente tarjeta readonly -->
<link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}">

<!-- Lógica del componente tarjeta readonly -->
<script src="{{ asset('js/componentes/prenda-card-readonly.js') }}"></script>

<!-- Integración con el flujo de pedidos (opcional pero recomendado) -->
<script src="{{ asset('js/integracion/integracion-prenda-readonly-pedidos.js') }}"></script>
*/

// ============================================
// OPCIÓN 2: EN resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php
// ============================================

/*
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/componentes/prenda-card-readonly.js') }}"></script>
    <script src="{{ asset('js/integracion/integracion-prenda-readonly-pedidos.js') }}"></script>
@endpush
*/

// ============================================
// ORDEN DE CARGA CORRECTO
// ============================================

/*
HTML:
1. SweetAlert2 (ya cargado)
2. FontAwesome (ya cargado)
3. prenda-card-readonly.css      ← NUEVO
4. renderizador-prenda-sin-cotizacion.js (ya cargado)
5. gestion-items-pedido.js (ya cargado)
6. prenda-card-readonly.js        ← NUEVO
7. integracion-prenda-readonly-pedidos.js ← NUEVO (opcional)

IMPORTANTE: prenda-card-readonly.js debe ir DESPUÉS de gestion-items-pedido.js
*/

// ============================================
// VERIFICACIÓN
// ============================================

// Para verificar que está todo cargado correctamente:

console.log('✅ SweetAlert2:', typeof Swal !== 'undefined');
console.log('✅ FontAwesome:', typeof FontAwesome !== 'undefined');
console.log('✅ generarTarjetaPrendaReadOnly:', typeof generarTarjetaPrendaReadOnly === 'function');
console.log('✅ GestionItemsUI:', !!window.gestionItemsUI);
console.log('✅ GestorPrendaSinCotizacion:', !!window.gestorPrendaSinCotizacion);

if (typeof generarTarjetaPrendaReadOnly === 'function' && 
    typeof Swal !== 'undefined' && 
    window.gestionItemsUI) {
    console.log('\n✨ TODO ESTÁ LISTO - Sistema de tarjetas readonly operacional\n');
} else {
    console.warn('\n⚠️  Falta cargar algo - Revisa el orden de scripts en el HTML\n');
}
