(function initSharedEchoReady() {
    'use strict';

    if (window.SharedEchoReady && typeof window.SharedEchoReady.wait === 'function') {
        return;
    }

    function wait(callback) {
        if (typeof callback !== 'function') return;

        if (typeof window.waitForEcho === 'function') {
            try {
                const result = window.waitForEcho(callback);
                if (result && typeof result.then === 'function') {
                    result.then(() => callback()).catch(() => {});
                }
                return;
            } catch (e) {
                // noop
            }
        }

        callback();
    }

    window.SharedEchoReady = Object.freeze({
        wait
    });
})();
