<?php

declare(strict_types=1);

namespace eCharts;

/** Adds a view-local period picker after the normal renderer has registered its message handler. */
final class EChartsPeriodControl
{
    /** @param array<string,string> $choices */
    public static function Page(string $html, string $path, bool $ipsView, array $choices, bool $german): string
    {
        $default = isset($choices['']) ? $choices[''] . ($german ? ' (Standard)' : ' (default)')
            : ($german ? 'Konfigurierter Zeitraum' : 'Configured period');
        unset($choices['']);
        if ($choices === []) {
            return $html;
        }
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $options = '<option value="">' . $escape($default) . '</option>';
        foreach ($choices as $key => $label) {
            if (!EChartsDataProtocol::IsValidPeriodKey($key)) {
                throw new \InvalidArgumentException('Invalid period selection key.');
            }
            $options .= '<option value="' . $escape($key) . '">' . $escape($label) . '</option>';
        }
        $control = '<div id="echarts-period-picker" data-path="' . $escape($path)
            . '" data-output="' . ($ipsView ? 'ipsview' : 'tile') . '"><span role="status" aria-live="polite"></span>'
            . '<select aria-label="' . ($german ? 'Zeitraum wählen' : 'Select period') . '">'
            . $options . '</select></div>';
        $css = '<style>body > main:first-of-type{block-size:calc(100% - 2rem);min-block-size:0}'
            . '#echarts-period-picker{position:absolute;inset:auto .45rem 0 .45rem;height:2rem;display:flex;align-items:center;'
            . 'justify-content:flex-end;gap:.4rem;color:var(--symc-text);font:inherit}'
            . '#echarts-period-picker select{max-width:100%;min-width:0;height:1.85rem;font:inherit;font-size:.85rem;'
            . 'color:inherit;background:var(--symc-background,Canvas);border:1px solid var(--symc-border,GrayText);border-radius:.3rem}'
            . '#echarts-period-picker span{font-size:.75rem;line-height:1.05}</style>';
        $script = file_get_contents(__DIR__ . '/helper/visualization/http-origin.js') . "\n"
            . file_get_contents(__DIR__ . '/echarts-period-selection.js');
        // Replace only the terminal body tag, even if a chart title contains HTML-like text.
        $end = strripos($html, '</body>');
        if ($end === false) {
            throw new \RuntimeException('Missing chart document body.');
        }
        return substr_replace($html, $css . $control . '<script>' . $script . '</script>', $end, 0);
    }
}
