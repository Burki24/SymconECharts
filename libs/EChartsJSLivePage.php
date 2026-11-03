<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use RuntimeException;

require_once __DIR__ . '/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/EChartsAsset.php';
require_once __DIR__ . '/EChartsStartupState.php';
require_once __DIR__ . '/EChartsJSLiveLocale.php';

/** Shared trusted HTML adapter for real target output and a no-object, no-transport import preview. */
final class EChartsJSLivePage
{
    use IPSViewHTMLPageHelper;

    public static function Render(array $state, bool $ipsView, array $layout, ?array $transport = null, string $language = 'de'): string
    {
        $family = $state['chart']['family'];
        [$file, $runtime] = match ($family) {
            'gauge'             => ['EChartsGaugeSingle/JSLiveRenderer.js', EChartsAsset::JavaScript()],
            'progress'          => ['EChartsProgress/ProgressRenderer.js', EChartsAsset::JavaScript()],
            'pie'               => ['EChartsPie/JSLiveRenderer.js', EChartsAsset::PieJavaScript()],
            'cartesian-history' => ['EChartsTimeSeries/JSLiveRenderer.js', EChartsAsset::CartesianJavaScript()],
            'radar-history'     => ['EChartsRadarHistory/RadarHistoryRenderer.js', EChartsAsset::RadarJavaScript()],
            default             => throw new RuntimeException('FORMAT_UNSUPPORTED: unknown rendering family.')
        };
        $options = ['ipsViewTransport' => $transport, 'locale' => $language] + $layout + EChartsStartupState::Startup($state);
        $page = new self();
        $html = $page->RenderVisualizationHTMLPage($ipsView, ['language' => $language, 'title' => 'ECharts',
            'translations'                                               => EChartsJSLiveLocale::Dictionary($language),
            'state'                                                      => $options['state'], 'options' => $options,
            'replacements'                                               => ['{{ECHARTS_SCRIPT}}' => $runtime, '{{ECHARTS_DESIGN_SCRIPT}}' => EChartsAsset::DesignJavaScript()
                . "\n" . EChartsAsset::StartupJavaScript(),
                '{{FAMILY_SCRIPT}}'            => file_get_contents(dirname(__DIR__) . '/' . $file),
                '{{FORMAT_SCRIPT}}'            => file_get_contents(__DIR__ . '/jslive/format.js'),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $transport === null ? '' : file_get_contents(__DIR__ . '/echarts/ipsview-transport.js')]]);
        if (strlen($html) > 1048576) {
            throw new RuntimeException('LIMIT_EXCEEDED: native HTML document exceeds 1 MiB.');
        }
        return $html;
    }

    protected function VisualizationAsset(string $filename): string
    {
        if (!in_array($filename, ['index.html', 'style.css', 'app.js'], true)) {
            throw new RuntimeException('Invalid import visualization asset.');
        }
        return file_get_contents(__DIR__ . '/jslive/' . $filename);
    }

    protected function SendDebug(string $message, string $data, int $format): bool
    {
        return true;
    }
}
