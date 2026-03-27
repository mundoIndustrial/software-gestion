(function () {
    function ensureJquery(callback) {
        if (typeof window.jQuery !== 'undefined') {
            callback();
            return;
        }

        console.warn('[Supervisor-Pedidos] jQuery no cargo desde CDN principal, intentando fallback...');
        var s = document.createElement('script');
        s.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
        s.onload = function () {
            callback();
        };
        s.onerror = function () {
            console.error('[Supervisor-Pedidos] No se pudo cargar jQuery desde fallback.');
            callback();
        };
        document.head.appendChild(s);
    }

    window.waitForJquery = function (cb) {
        ensureJquery(function () {
            try {
                cb && cb();
            } catch (e) {
                // noop
            }
        });
    };

    window.waitForJquery(function () {
        try {
            if (document.querySelector('script[data-bootstrap-bundle]')) return;
            var bs = document.createElement('script');
            bs.src = 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js';
            bs.setAttribute('data-bootstrap-bundle', 'true');
            document.head.appendChild(bs);
        } catch (e) {
            // noop
        }
    });
})();

