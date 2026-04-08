document.addEventListener('DOMContentLoaded', function () {
    var topNav = document.querySelector('.top-nav');

    if (!topNav) {
        console.error(' .top-nav no encontrado');
        return;
    }

    console.log(' TOP-NAV PROTECTOR ACTIVADO');

    function forceNavVisible() {
        topNav.classList.remove('hidden', 'hide', 'invisible', 'd-none', 'opacity-0');

        topNav.style.cssText = [
            'display: flex !important',
            'visibility: visible !important',
            'opacity: 1 !important',
            'height: auto !important',
            'min-height: 72px !important',
            'position: relative !important',
            'z-index: 100 !important',
            'pointer-events: auto !important',
        ].join(';');
    }

    forceNavVisible();

    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'attributes') {
                console.log('[MUTACION] Atributo ' + mutation.attributeName + ' cambio en .top-nav');
                forceNavVisible();
            }
        });
    });

    observer.observe(topNav, {
        attributes: true,
        attributeFilter: ['style', 'class'],
        subtree: false,
    });

    setInterval(function () {
        var computed = window.getComputedStyle(topNav);
        if (computed.display === 'none' || computed.visibility === 'hidden') {
            console.log(' NAV OCULTADO - RESTAURANDO');
            forceNavVisible();
        }
    }, 200);

    console.log(' Protecciones instaladas');
});

window.limpiarParametrosVacios = function (event) {
    event.preventDefault();
    var form = event.target;
    var params = {};

    new FormData(form).forEach(function (value, key) {
        if (value && String(value).trim() !== '') {
            params[key] = value;
        }
    });

    var baseUrl = form.getAttribute('action');
    var queryParams = new URLSearchParams(params).toString();
    var finalUrl = queryParams ? baseUrl + '?' + queryParams : baseUrl;

    console.log('[Search] URL final:', finalUrl);
    window.location.href = finalUrl;
};

