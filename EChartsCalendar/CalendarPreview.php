<?php

declare(strict_types=1);

namespace SymconECharts;

/** Synthetic annual preview: draft changes never trigger archive queries. */
trait CalendarPreview
{
    private function CalendarPreviewSvg(array $values, bool $ipsView): string
    {
        $get = fn (string $name, string $type = 'integer'): mixed => $this->BarPreviewValue($values, $name, $type, $ipsView);
        $palette = EChartsBarPreview::Palette($get('EChartsTheme', 'string'));
        $color = fn (string $name, string $fallback): string => EChartsBarPreview::Color($get($name), $fallback);
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Calendar preview'),
            $color('TitleColor', $palette['text']),
            $get('TitleFontSizePercent')
        );
        $year = (int) ($values['Year'] ?? $this->ReadPropertyInteger('Year'));
        try {
            $window = CalendarData::Resolve($year, 0, $this->CurrentTimestamp(), date_default_timezone_get());
        } catch (\Throwable) {
            return $svg . '</svg>';
        }
        $vertical = $get('Orientation', 'string') === 'vertical';
        $axisColor = EChartsBarPreview::E($color('AxisColor', $palette['text']));
        $axisSize = max(6, 11 * $get('AxisFontSizePercent') / 100);
        $first = $get('FirstWeekday');
        $offset = ($window['Days'][0]['weekday'] + 1 - $first + 7) % 7;
        $weeks = (int) ceil((count($window['Days']) + $offset) / 7);
        $width = $vertical ? 600 / 7 : 600 / $weeks;
        $height = $vertical ? 230 / $weeks : 230 / 7;
        $low = $get('LowColor') < 0 ? 0x5070DD : $get('LowColor');
        $high = $get('HighColor') < 0 ? 0xFB628B : $get('HighColor');
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ($window['Days'] as $index => $day) {
            $week = intdiv($index + $offset, 7);
            $weekday = ($index + $offset) % 7;
            $x = 64 + ($vertical ? $weekday : $week) * $width;
            $y = 72 + ($vertical ? $week : $weekday) * $height;
            $ratio = (sin($index / 366 * 2 * M_PI - 1.5) + 1) / 2;
            $rgb = 0;
            foreach ([16, 8, 0] as $shift) {
                $rgb |= (int) round((($low >> $shift) & 255) * (1 - $ratio) + (($high >> $shift) & 255) * $ratio) << $shift;
            }
            $fill = $index % 17 === 0 ? $color('EmptyCellColor', $palette['surface']) : EChartsAsset::ColorToHex($rgb);
            $svg .= '<rect x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y)
                . '" width="' . EChartsBarPreview::N($width) . '" height="' . EChartsBarPreview::N($height)
                . '" fill="' . EChartsBarPreview::E($fill) . '" stroke="'
                . EChartsBarPreview::E($color('CellBorderColor', $palette['background']))
                . '" stroke-width="' . $get('CellBorderWidth') . '"/>';
            if ($get('ShowMonthLabels', 'boolean') && substr($day['date'], 8) === '01') {
                $svg .= '<text x="' . EChartsBarPreview::N($vertical ? 8 : $x) . '" y="'
                    . EChartsBarPreview::N($vertical ? $y + 5 : 65) . '" fill="' . $axisColor
                    . '" font-size="' . EChartsBarPreview::N($axisSize) . '">'
                    . EChartsBarPreview::E($this->Translate($months[(int) substr($day['date'], 5, 2) - 1])) . '</text>';
            }
            $labelSize = 9 * $get('ValueFontSizePercent') / 100;
            if ($width >= $labelSize * 2 && $height >= $labelSize + 2 && $index % 17 !== 0) {
                $label = $get('ShowValues', 'boolean') ? (string) round(10 + 20 * $ratio)
                    : ($get('ShowDayNumbers', 'boolean') ? (string) (int) substr($day['date'], 8) : '');
                $svg .= '<text x="' . EChartsBarPreview::N($x + $width / 2) . '" y="'
                    . EChartsBarPreview::N($y + $height / 2 + 3) . '" text-anchor="middle" fill="'
                    . EChartsBarPreview::E($color('ValueColor', $palette['text'])) . '" font-size="'
                    . EChartsBarPreview::N($labelSize) . '">' . $label . '</text>';
            }
        }
        if ($get('ShowWeekdayLabels', 'boolean')) {
            for ($day = 0; $day < 7; ++$day) {
                $svg .= '<text x="' . EChartsBarPreview::N($vertical ? 64 + $day * $width : 24)
                    . '" y="' . EChartsBarPreview::N($vertical ? 65 : 72 + ($day + 0.6) * $height)
                    . '" fill="' . $axisColor . '" font-size="' . EChartsBarPreview::N($axisSize) . '">'
                    . EChartsBarPreview::E($this->Translate($weekdays[($day + $first) % 7])) . '</text>';
            }
        }
        if ($get('ShowColorScale', 'boolean')) {
            $svg .= '<defs><linearGradient id="calendar-scale"><stop stop-color="' . EChartsAsset::ColorToHex($low)
                . '"/><stop offset="1" stop-color="' . EChartsAsset::ColorToHex($high) . '"/></linearGradient></defs>'
                . '<rect x="270" y="326" width="180" height="9" fill="url(#calendar-scale)"/>'
                . '<text x="240" y="335" fill="' . $axisColor . '" font-size="11">10</text>'
                . '<text x="460" y="335" fill="' . $axisColor . '" font-size="11">30</text>';
        }
        return $svg . '</svg>';
    }
}
