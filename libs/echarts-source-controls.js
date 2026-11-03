(function (global) {
    'use strict';
    // Shared DOM adapter only; selection state, page anchors and family geometry remain separate.
    global.SYMC_ECHARTS_SOURCE_CONTROLS = { create: function (options) {
        var prefix = options.prefix;
        function element(suffix) { return document.getElementById(prefix + '-' + suffix); }
        var root = element('controls');
        var signature = '';
        var translate = options.translate;
        if (!root) { return { update: function () {}, hide: function () {} }; }
        var sources = element('sources');
        element('previous').addEventListener('click', function () { options.move(-1); });
        element('next').addEventListener('click', function () { options.move(1); });
        element('all').addEventListener('click', options.all);
        element('inverse').addEventListener('click', options.inverse);
        return {
            hide: function () { root.hidden = true; },
            update: function (view) {
                root.hidden = !view.visible;
                element('selection').hidden = !view.enabled;
                element('selection-title').textContent = translate('Sources');
                element('all').textContent = translate('All');
                element('inverse').textContent = translate('Inverse');
                element('previous').setAttribute('aria-label', translate('Previous page'));
                element('next').setAttribute('aria-label', translate('Next page'));
                element('pages').setAttribute('aria-label', translate('Category navigation'));
                element('pages').hidden = !view.visible;
                element('previous').disabled = view.window.first === 0;
                element('next').disabled = view.total === 0 || view.window.last >= view.total - 1;
                element('range').textContent = view.total
                    ? String(view.window.first + 1) + '–' + (view.window.last + 1) + ' / ' + view.total
                    : translate('No sources selected');
                var nextSignature = JSON.stringify(view.items.map(function (item) { return [item.id, item.label]; }));
                if (view.enabled && nextSignature !== signature) {
                    sources.textContent = '';
                    view.items.forEach(function (item) {
                        var label = document.createElement('label');
                        var checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.value = item.id;
                        checkbox.addEventListener('change', function () { options.change(item.id, checkbox.checked); });
                        var text = document.createElement('span');
                        text.textContent = String(item.label || '');
                        label.appendChild(checkbox);
                        label.appendChild(text);
                        sources.appendChild(label);
                    });
                    signature = nextSignature;
                }
                if (view.enabled) {
                    sources.querySelectorAll('input').forEach(function (checkbox) {
                        checkbox.checked = view.selected[checkbox.value] !== false;
                    });
                }
            }
        };
    } };
}(window));
