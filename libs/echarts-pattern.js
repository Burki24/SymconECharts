(function () {
    'use strict';

    window.SymconEChartsPattern = {
        create: function (onChange) {
            var cache = Object.create(null);

            function key(source, sizePercent, aspectRatio) {
                if (typeof source !== 'string' || source.indexOf('data:image/svg+xml;base64,') !== 0) {
                    return null;
                }
                return source + '|' + sizePercent + '|' + aspectRatio;
            }

            function ensure(source, sizePercent, aspectRatio) {
                var patternKey = key(source, sizePercent, aspectRatio);
                if (patternKey === null || typeof window.Image !== 'function') { return true; }
                if (cache[patternKey]) { return cache[patternKey].status !== 'loading'; }

                var image = new window.Image();
                cache[patternKey] = { status: 'loading', pattern: null };
                image.onload = function () {
                    var patternImage = image;
                    var size = Math.max(8, Math.round(64 * Math.max(25, Math.min(400, Number(sizePercent) || 100)) / 100));
                    var ratio = Math.max(0.05, Math.min(20, Number(aspectRatio) || 1));
                    if (typeof document.createElement === 'function') {
                        var canvas = document.createElement('canvas');
                        if (canvas && typeof canvas.getContext === 'function') {
                            canvas.width = size;
                            canvas.height = Math.max(8, Math.round(size / ratio));
                            var context = canvas.getContext('2d');
                            if (context && typeof context.drawImage === 'function') {
                                context.drawImage(image, 0, 0, canvas.width, canvas.height);
                                patternImage = canvas;
                            }
                        }
                    }
                    cache[patternKey] = {
                        status: 'ready', pattern: { image: patternImage, repeat: 'repeat' }
                    };
                    onChange();
                };
                image.onerror = function () {
                    cache[patternKey] = { status: 'failed', pattern: null };
                    onChange();
                };
                image.src = source;
                return false;
            }

            function resolve(source, sizePercent, aspectRatio) {
                var patternKey = key(source, sizePercent, aspectRatio);
                var cached = patternKey === null ? null : cache[patternKey];
                return cached && cached.status === 'ready' ? cached.pattern : null;
            }

            return { ensure: ensure, resolve: resolve };
        }
    };
}());
