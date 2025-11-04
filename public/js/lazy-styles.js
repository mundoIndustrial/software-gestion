/**
 * Lazy Loading de Estilos CSS
 * Carga estilos no críticos de forma asíncrona
 */

(function() {
    'use strict';

    // Función para cargar CSS de forma asíncrona
    function loadCSS(href, before, media, attributes) {
        var doc = window.document;
        var ss = doc.createElement('link');
        var ref;
        if (before) {
            ref = before;
        } else {
            var refs = (doc.body || doc.getElementsByTagName('head')[0]).childNodes;
            ref = refs[refs.length - 1];
        }

        var sheets = doc.styleSheets;
        
        // Establecer atributos
        if (attributes) {
            for (var attr in attributes) {
                if (attributes.hasOwnProperty(attr)) {
                    ss.setAttribute(attr, attributes[attr]);
                }
            }
        }
        
        ss.rel = 'stylesheet';
        ss.href = href;
        ss.media = 'only x';

        // Esperar hasta que el documento esté listo
        function ready(cb) {
            if (doc.body) {
                return cb();
            }
            setTimeout(function() {
                ready(cb);
            });
        }

        // Inyectar el link
        ready(function() {
            ref.parentNode.insertBefore(ss, (before ? ref : ref.nextSibling));
        });

        // Función para verificar cuando el CSS está cargado
        var onloadcssdefined = function(cb) {
            var resolvedHref = ss.href;
            var i = sheets.length;
            while (i--) {
                if (sheets[i].href === resolvedHref) {
                    return cb();
                }
            }
            setTimeout(function() {
                onloadcssdefined(cb);
            });
        };

        function loadCB() {
            if (ss.addEventListener) {
                ss.removeEventListener('load', loadCB);
            }
            ss.media = media || 'all';
        }

        // Eventos de carga
        if (ss.addEventListener) {
            ss.addEventListener('load', loadCB);
        }
        ss.onloadcssdefined = onloadcssdefined;
        onloadcssdefined(loadCB);
        
        return ss;
    }

    // Cargar estilos no críticos después del load
    window.addEventListener('load', function() {
        // Lista de estilos no críticos para cargar
        var nonCriticalStyles = [
            {
                href: '/css/orders styles/modern-table.css',
                media: 'all'
            }
        ];

        // Cargar cada estilo con un pequeño delay para no bloquear
        nonCriticalStyles.forEach(function(style, index) {
            setTimeout(function() {
                loadCSS(style.href, null, style.media);
            }, index * 50);
        });
    });

    // Precargar estilos cuando el usuario hace hover sobre links
    var preloadedStyles = new Set();
    
    document.addEventListener('mouseover', function(e) {
        var link = e.target.closest('a[href]');
        if (!link) return;
        
        var href = link.getAttribute('href');
        if (!href || preloadedStyles.has(href)) return;
        
        // Determinar qué estilos precargar según la ruta
        var stylesToPreload = [];
        
        if (href.includes('/balanceo')) {
            stylesToPreload = [
                '/css/balanceo.css',
                '/css/tableros.css'
            ];
        } else if (href.includes('/tableros')) {
            stylesToPreload = ['/css/tableros.css'];
        } else if (href.includes('/registros')) {
            stylesToPreload = ['/css/orders styles/registros.css'];
        }
        
        // Precargar estilos
        stylesToPreload.forEach(function(styleHref) {
            if (!preloadedStyles.has(styleHref)) {
                var preload = document.createElement('link');
                preload.rel = 'prefetch';
                preload.as = 'style';
                preload.href = styleHref;
                document.head.appendChild(preload);
                preloadedStyles.add(styleHref);
            }
        });
    }, { passive: true });

    // Lazy load de fuentes de iconos
    if ('IntersectionObserver' in window) {
        var iconObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var icon = entry.target;
                    if (!icon.classList.contains('loaded')) {
                        icon.classList.add('loaded');
                    }
                }
            });
        }, {
            rootMargin: '50px'
        });

        // Observar iconos
        document.addEventListener('DOMContentLoaded', function() {
            var icons = document.querySelectorAll('.material-symbols-rounded');
            icons.forEach(function(icon) {
                iconObserver.observe(icon);
            });
        });
    }

    // Optimización: Remover estilos inline después de que CSS externo cargue
    window.addEventListener('load', function() {
        setTimeout(function() {
            // Buscar elementos con estilos inline que podrían estar en CSS
            var elementsWithInlineStyles = document.querySelectorAll('[style]');
            elementsWithInlineStyles.forEach(function(element) {
                // Solo remover si tiene clase CSS equivalente
                if (element.classList.length > 0) {
                    var computedStyle = window.getComputedStyle(element);
                    var inlineStyle = element.getAttribute('style');
                    
                    // Si los estilos CSS aplicados son suficientes, remover inline
                    if (computedStyle.display !== 'inline' || computedStyle.position !== 'static') {
                        // Mantener estilos inline críticos
                        var criticalProps = ['display', 'visibility', 'opacity'];
                        var hasCritical = criticalProps.some(function(prop) {
                            return inlineStyle.includes(prop);
                        });
                        
                        if (!hasCritical) {
                            // element.removeAttribute('style'); // Descomentar con cuidado
                        }
                    }
                }
            });
        }, 1000);
    });

    // Exponer función globalmente para uso manual
    window.loadCSS = loadCSS;
})();
