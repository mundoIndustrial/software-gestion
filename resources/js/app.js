import './bootstrap';

// jQuery y Bootstrap 4 se cargan desde CDN en base.blade.php (cdnjs.cloudflare.com)

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();
