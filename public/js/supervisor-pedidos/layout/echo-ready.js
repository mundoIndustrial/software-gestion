window.echoReady = false;
window.echoReadyCallbacks = [];

window.waitForEcho = function (callback) {
    const isReady = window.echoReady && (window.EchoInstance || window.Echo);

    // Compatibilidad 1: estilo callback -> waitForEcho(() => {})
    if (typeof callback === 'function') {
        if (isReady) {
            callback();
        } else {
            window.echoReadyCallbacks.push(callback);
        }
        return;
    }

    // Compatibilidad 2: estilo Promise -> waitForEcho().then(...)
    if (isReady) {
        return Promise.resolve(window.EchoInstance || window.Echo);
    }

    return new Promise((resolve) => {
        window.echoReadyCallbacks.push(resolve);
    });
};

window.notifyEchoReady = function () {
    window.echoReady = true;
    while (window.echoReadyCallbacks.length > 0) {
        var callback = window.echoReadyCallbacks.shift();
        try {
            if (typeof callback === 'function') {
                callback(window.EchoInstance || window.Echo);
            }
        } catch (error) {
            console.error('[Echo] Error ejecutando callback:', error);
        }
    }
};
