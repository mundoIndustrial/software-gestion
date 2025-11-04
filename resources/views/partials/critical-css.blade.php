{{-- CSS Crítico Inline - Solo estilos esenciales para el primer render --}}
<style>
    /* Reset y Base */
    *,*::before,*::after{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,sans-serif;line-height:1.5}
    
    /* Layout Crítico */
    .container{display:flex;min-height:100vh}
    .main-content{flex:1;padding:20px;background:var(--color-bg-main,#f8f9fa)}
    
    /* Sidebar Crítico */
    .sidebar{width:250px;background:var(--color-bg-sidebar,#fff);border-right:1px solid var(--color-border,#e5e7eb);position:fixed;height:100vh;overflow-y:auto;z-index:100}
    .sidebar-collapsed{width:70px}
    
    /* Grid Básico */
    .prendas-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
    
    /* Card Básico */
    .prenda-card{background:#fff;border-radius:12px;border:1px solid #e5e7eb;cursor:pointer;overflow:hidden}
    .prenda-card__image{height:180px;background:#fff;position:relative;display:flex;align-items:center;justify-content:center}
    .prenda-card__content{padding:20px}
    
    /* Texto */
    .prenda-card__title{margin:0 0 8px;font-size:20px;font-weight:700}
    .metric-label{margin:0;font-size:11px;text-transform:uppercase;font-weight:600;color:#6b7280}
    .metric-value{margin:4px 0 0;font-weight:700;color:#ff9d58;font-size:18px}
    
    /* Loading Skeleton */
    .skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:loading 1.5s infinite}
    @keyframes loading{0%{background-position:200% 0}100%{background-position:-200% 0}}
    
    /* Ocultar contenido no crítico inicialmente */
    .lazy-load{opacity:0;transition:opacity .3s}
    .lazy-load.loaded{opacity:1}
    
    /* Dark Theme Crítico */
    html[data-theme="dark"] body{background:#0f172a;color:#f1f5f9}
    html[data-theme="dark"] .main-content{background:#1e293b}
    html[data-theme="dark"] .sidebar{background:#1e293b;border-color:#334155}
    html[data-theme="dark"] .prenda-card{background:#1e293b;border-color:#334155;color:#f1f5f9}
    
    /* Spinner de carga */
    .spinner{width:40px;height:40px;margin:100px auto;border:4px solid #f3f3f3;border-top:4px solid #ff9d58;border-radius:50%;animation:spin 1s linear infinite}
    @keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
    
    /* Ocultar elementos que cargarán después */
    .defer-load{visibility:hidden}
    .defer-load.show{visibility:visible}
</style>

{{-- Preload de fuentes críticas --}}
<link rel="preload" href="https://fonts.gstatic.com/s/materialsymbolsrounded/v1/syl0-zNym6YjUruM-QrEh7-nyTnjDwKNJ_190Fjzag.woff2" as="font" type="font/woff2" crossorigin>
