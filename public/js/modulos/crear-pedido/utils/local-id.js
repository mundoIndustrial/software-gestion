(function () {
    "use strict";

    function generarLocalId(prefix) {
        var pref = prefix || "item";
        var rand = Math.random().toString(36).slice(2, 8);
        return pref + "_" + Date.now() + "_" + rand;
    }

    function asegurarLocalId(item, prefix) {
        if (!item || typeof item !== "object") {
            return item;
        }

        if (!item._local_id) {
            item._local_id = generarLocalId(prefix || item.tipo || "item");
        }

        return item;
    }

    window.generarLocalId = generarLocalId;
    window.asegurarLocalId = asegurarLocalId;
})();

