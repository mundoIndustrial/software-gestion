import './bootstrap';

// jQuery y Bootstrap 4 ya están disponibles desde el CDN en base.blade.php
// Solo verificar que estén disponibles globalmente
if (typeof jQuery !== 'undefined') {
    window.jQuery = jQuery;
    window.$ = jQuery;
    console.log('✅ jQuery disponible desde CDN');
}

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();
