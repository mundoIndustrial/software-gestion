window.echoReady = false;
window.echoReadyCallbacks = [];

window.waitForEcho = function (callback) {
    if (window.echoReady && window.EchoInstance) {
        callback();
    } else {
        window.echoReadyCallbacks.push(callback);
    }
};

window.notifyEchoReady = function () {
    window.echoReady = true;
    while (window.echoReadyCallbacks.length > 0) {
        var callback = window.echoReadyCallbacks.shift();
        try {
            callback();
        } catch (error) {
            console.error('[Echo] Error ejecutando callback:', error);
        }
    }
};

