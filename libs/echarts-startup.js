(function () {
    'use strict';

    // A newer full state or unloading the document can cancel an asynchronous decode.
    window.SYMC_ECHARTS_STARTUP = function (bootstrap, onState, onError) {
        var active = true;
        function cancel() { active = false; }
        var compressed = bootstrap.options && bootstrap.options.compressedStateGzipBase64;
        if (!compressed) { onState(bootstrap.state || null); return cancel; }
        if (typeof window.DecompressionStream !== 'function') { onError(); return cancel; }
        try {
            var binary = window.atob(compressed);
            var bytes = new Uint8Array(binary.length);
            for (var index = 0; index < binary.length; index++) { bytes[index] = binary.charCodeAt(index); }
            var stream = new window.Blob([bytes]).stream().pipeThrough(new window.DecompressionStream('gzip'));
            new window.Response(stream).text().then(function (json) {
                if (active) { onState(JSON.parse(json)); }
            }).catch(function () { if (active) { onError(); } });
        } catch (error) { if (active) { onError(); } }
        return cancel;
    };
}());
