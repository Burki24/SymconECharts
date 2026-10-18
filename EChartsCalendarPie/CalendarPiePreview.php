<?php

declare(strict_types=1);

namespace SymconECharts;

/** Schematic monthly design preview; never reads archives or current variable values. */
trait CalendarPiePreview
{
    private function CalendarPiePreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $e = EChartsBarPreview::E(...);
        $n = EChartsBarPreview::N(...);
        $number = fn (string $name, int $min, int $max): int => max($min, min($max, $this->BarPreviewValue($values, $name, 'integer', $ipsView)));
        $color = fn (string $name, string $fallback): string => EChartsBarPreview::Color($number($name, -1, 0xFFFFFF), $fallback);
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('CalendarPie preview'),
            $color('TitleColor', $palette['text']),
            $number('TitleFontSizePercent', 50, 200)
        );
        $svg = str_replace('<svg xmlns', '<svg font-family="Arial, sans-serif" xmlns', $svg);
        $mode = $values['DataMode'] ?? $this->ReadPropertyString('DataMode');
        $normalize = $mode !== 'states' && (bool) ($values['NormalizeValues'] ?? $this->ReadPropertyBoolean('NormalizeValues'));
        try {
            $window = CalendarPieData::Resolve(
                (string) ($values['Month'] ?? $this->ReadPropertyString('Month')),
                0,
                $this->CurrentTimestamp(),
                date_default_timezone_get()
            );
        } catch (\Throwable) {
            $window = CalendarPieData::Resolve('', 0, $this->CurrentTimestamp(), date_default_timezone_get());
        }
        $categories = [];
        if ($mode === 'states') {
            $raw = $values['States'] ?? $this->ReadPropertyString('States');
            $raw = is_string($raw) ? json_decode($raw, true) : $raw;
            try {
                $definitions = StateTimelineData::Definitions($raw ?? [], false);
            } catch (\Throwable) {
                $definitions = [];
            }
            foreach ($definitions as $value => $definition) {
                $categories[] = ['label' => $definition['Label'], 'color' => EChartsBarPreview::Color(
                    $definition['Color'],
                    $palette['seriesColors'][count($categories) % count($palette['seriesColors'])]
                )];
            }
            if ($categories === []) {
                $categories = [['label' => '0', 'color' => $palette['seriesColors'][0]], ['label' => '1', 'color' => $palette['seriesColors'][1]]];
            }
            $categories[] = ['label' => $this->Translate('Unknown'), 'color' => $color('UnknownColor', $palette['muted'])];
        } else {
            $raw = $values['Sources'] ?? $this->ReadPropertyString('Sources');
            $raw = is_string($raw) ? json_decode($raw, true) : $raw;
            if (is_array($raw) && count($raw) > 5) {
                return $svg . '<text x="360" y="180" text-anchor="middle" font-size="14" fill="' . $e($palette['text']) . '">'
                    . $e($this->Translate('Select one to five numeric archive variables.')) . '</text></svg>';
            }
            $ranges = [];
            if ($normalize && is_array($raw)) {
                try {
                    foreach ($raw as $entry) {
                        if (!is_array($entry)) {
                            throw new \InvalidArgumentException();
                        }
                        [$minimum, $maximum] = CalendarPieData::NormalizationRange($entry);
                        $ranges[(int) ($entry['VariableID'] ?? 0)] = ['Minimum' => $minimum, 'Maximum' => $maximum];
                    }
                } catch (\Throwable) {
                    return $svg . '<text x="360" y="180" text-anchor="middle" font-size="12" fill="' . $e($palette['text']) . '">'
                        . $e($this->Translate('Normalization requires finite source limits with minimum below maximum.')) . '</text></svg>';
                }
            }
            $items = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', false)['items'];
            foreach ($items as $index => $item) {
                $categories[] = ['label' => $item['label'], 'color' => $item['color'] ?: $palette['seriesColors'][$index % count($palette['seriesColors'])],
                    'range'              => $ranges[$item['VariableID'] ?? 0] ?? ['Minimum' => 0, 'Maximum' => 100],
                    'sample'             => $item['value'], 'unit' => $item['unit'], 'decimals' => $item['decimals']];
            }
        }
        $axisFont = 12 * $number('AxisFontSizePercent', 50, 200) / 100;
        $valueFont = 12 * $number('ValueFontSizePercent', 50, 200) / 100;
        $axisColor = $color('AxisColor', $palette['text']);
        $firstDay = $number('FirstWeekday', 0, 6);
        $civilMonth = new \DateTimeImmutable($window['Month'] . '-01 12:00:00', new \DateTimeZone('UTC'));
        $weekday = (int) $civilMonth->format('w');
        $offset = ($weekday - $firstDay + 7) % 7;
        $weeks = (int) ceil(($offset + (int) $civilMonth->format('t')) / 7);
        $left = 16;
        $top = 88;
        $cellWidth = 688 / 7;
        $cellHeight = (($this->BarPreviewValue($values, 'ShowLegend', 'boolean', $ipsView) ? 310 : 342) - $top) / $weeks;
        $showDays = $this->BarPreviewValue($values, 'ShowDayNumbers', 'boolean', $ipsView)
            && $cellWidth >= $valueFont * 2.2 && $cellHeight >= $valueFont * 3 + 12;
        $radius = max(0.25, min($cellWidth, $cellHeight) / 2 - 3) * $number('PieRadiusPercent', 20, 90) / 100;
        if ($showDays) {
            $radius = min($radius, max(0.25, $cellHeight / 2 - $valueFont - 6));
        }
        $inner = $radius * $number('InnerRadiusPercent', 0, 80) / 100;
        $svg .= '<text x="360" y="58" text-anchor="middle" font-size="' . $n($axisFont) . '" fill="' . $e($axisColor) . '">'
            . $e($window['Month'] . ($normalize ? ' · ' . $this->Translate('Normalized comparison (0–100)') : '')) . '</text>';
        $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        for ($index = 0; $index < 7; ++$index) {
            $svg .= '<text x="' . $n($left + ($index + 0.5) * $cellWidth) . '" y="80" text-anchor="middle" font-size="'
                . $n($axisFont) . '" fill="' . $e($axisColor) . '">' . $e($this->Translate($weekdays[($index + $firstDay) % 7])) . '</text>';
        }
        foreach ($window['Days'] as $index => $day) {
            $calendarDay = (int) substr($day['date'], 8);
            $slot = $offset + $calendarDay - 1;
            $x = $left + ($slot % 7) * $cellWidth;
            $y = $top + intdiv($slot, 7) * $cellHeight;
            $cx = $x + $cellWidth / 2;
            $cy = $y + $cellHeight / 2;
            $status = !$day['completed'] ? 'pending' : ($index % 11 === 5 ? 'missing' : ($index % 13 === 4 ? 'zero' : ($index % 7 === 3 ? 'partial' : 'complete')));
            $weights = [];
            $originals = [];
            if ($normalize && $status === 'complete') {
                foreach ($categories as $part => $category) {
                    $originals[$part] = $category['sample'] + ($index % 5) - 2;
                    $weights[] = CalendarPieData::NormalizedValue($originals[$part], $category['range']);
                }
                if (array_sum($weights) === 0.0) {
                    $status = 'zero';
                }
            }
            $svg .= '<rect data-calendar-day="' . $e($day['date']) . '" data-status="' . $status . '" x="' . $n($x) . '" y="' . $n($y)
                . '" width="' . $n($cellWidth) . '" height="' . $n($cellHeight) . '" fill="' . $e($color('EmptyCellColor', $palette['surface']))
                . '" fill-opacity="' . $n($number('CellOpacityPercent', 0, 100) / 100) . '" stroke="' . $e($color('CellBorderColor', $palette['border']))
                . '" stroke-width="' . $number('CellBorderWidth', 0, 4) . '"/>';
            if ($showDays) {
                $svg .= '<text x="' . $n($x + 4) . '" y="' . $n($y + $valueFont + 3) . '" font-size="' . $n($valueFont)
                    . '" fill="' . $e($axisColor) . '">' . $calendarDay . '</text>';
            }
            if ($status === 'complete' || ($mode === 'states' && $status !== 'pending')) {
                foreach ($categories as $part => $category) {
                    if ($normalize) {
                        continue;
                    }
                    $unknown = $mode === 'states' && $part === count($categories) - 1;
                    $weights[] = $unknown ? ($status === 'missing' ? 24 : ($status === 'partial' ? 6 : 0))
                        : ($mode === 'states' && $status === 'missing' ? 0 : 1 + ($part + $index) % 5);
                }
                $total = array_sum($weights);
                $start = -$number('StartAngle', 0, 360) * M_PI / 180;
                $clockwise = $this->BarPreviewValue($values, 'Clockwise', 'boolean', $ipsView);
                $labelBoxes = [];
                foreach ($categories as $part => $category) {
                    if ($weights[$part] <= 0 || $total <= 0) {
                        continue;
                    }
                    $span = $weights[$part] / $total * 2 * M_PI;
                    $end = $start + ($clockwise ? 1 : -1) * $span;
                    $svg .= '<path data-calendar-pie="true"' . ($normalize ? ' data-weight="' . $n($weights[$part]) . '" data-original-value="' . $n($originals[$part]) . '"' : '')
                        . ' d="' . EChartsBarPreview::Sector($cx, $cy, $inner, $radius, $start, $end, $clockwise)
                        . '" fill="' . $e($category['color']) . '" stroke="' . $e($color('SliceBorderColor', $palette['surface']))
                        . '" stroke-width="' . $number('SliceBorderWidth', 0, 5) . '"/>';
                    if ($this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView) && $radius >= $valueFont * 2.5 && $span >= 0.8) {
                        $middle = ($start + $end) / 2;
                        $labelRadius = ($radius + $inner) / 2;
                        $lx = $cx + cos($middle) * $labelRadius;
                        $ly = $cy + sin($middle) * $labelRadius;
                        $text = $normalize ? EChartsBarPreview::FormatValue($originals[$part], $category['decimals'], '', $this->ResolveHelperTranslationLanguage())
                            : (string) $weights[$part];
                        $textWidth = mb_strlen($text) * $valueFont * 0.75;
                        $box = [$lx - $textWidth / 2, $ly - $valueFont * 0.65, $lx + $textWidth / 2, $ly + $valueFont * 0.65];
                        $fits = $textWidth <= $radius * 0.9
                            && hypot(max($box[0] - $cx, 0, $cx - $box[2]), max($box[1] - $cy, 0, $cy - $box[3])) >= $inner + 1;
                        foreach ([[$box[0], $box[1]], [$box[2], $box[1]], [$box[2], $box[3]], [$box[0], $box[3]]] as [$px, $py]) {
                            $angle = atan2($py - $cy, $px - $cx);
                            $relative = fmod(($clockwise ? $angle - $start : $start - $angle) + 8 * M_PI, 2 * M_PI);
                            $fits = $fits && hypot($px - $cx, $py - $cy) <= $radius - 1 && $relative <= $span;
                        }
                        foreach ($labelBoxes as $previous) {
                            if ($box[0] < $previous[2] + 1 && $box[2] > $previous[0] - 1 && $box[1] < $previous[3] + 1 && $box[3] > $previous[1] - 1) {
                                $fits = false;
                            }
                        }
                        if ($fits) {
                            $labelBoxes[] = $box;
                            $fill = $category['color'];
                            $brightness = hexdec(substr($fill, 1, 2)) * 0.299 + hexdec(substr($fill, 3, 2)) * 0.587 + hexdec(substr($fill, 5, 2)) * 0.114;
                            $svg .= '<text data-slice-value="true" text-anchor="middle" x="' . $n($lx) . '" y="' . $n($ly + $valueFont / 3)
                                . '" font-size="' . $n($valueFont) . '" fill="' . $e($color('ValueColor', $brightness > 150 ? '#111111' : '#FFFFFF')) . '">' . $e($text) . '</text>';
                        }
                    }
                    $start = $end;
                }
            } else {
                $stroke = $status === 'partial' ? '#FF994D' : $palette['muted'];
                $svg .= match ($status) {
                    'zero'    => '<circle data-marker="zero" cx="' . $n($cx) . '" cy="' . $n($cy) . '" r="3" fill="none" stroke="' . $e($stroke) . '"/>',
                    'partial' => '<path data-marker="partial" d="M' . $n($cx) . ' ' . $n($cy - 4) . 'l4 4 -4 4 -4 -4Z" fill="' . $e($stroke) . '"/>',
                    'pending' => '<path data-marker="pending" d="M' . $n($cx) . ' ' . $n($cy - 4) . 'l4 8h-8Z" fill="' . $e($stroke) . '" opacity="0.4"/>',
                    default   => '<rect data-marker="missing" x="' . $n($cx - 3) . '" y="' . $n($cy - 3) . '" width="6" height="6" fill="' . $e($stroke) . '"/>'
                };
            }
        }
        if ($this->BarPreviewValue($values, 'ShowLegend', 'boolean', $ipsView)) {
            foreach (array_slice($categories, 0, 8) as $index => $category) {
                $x = 18 + ($index % 4) * 174;
                $y = 328 + intdiv($index, 4) * 16;
                $svg .= '<rect x="' . $x . '" y="' . ($y - 8) . '" width="10" height="8" fill="' . $e($category['color']) . '"/>'
                    . '<text x="' . ($x + 14) . '" y="' . $y . '" font-size="10" fill="' . $e($axisColor) . '">'
                    . $e(mb_strimwidth($category['label'], 0, 23, '…')) . '</text>';
            }
            if (count($categories) > 8) {
                $svg .= '<text x="696" y="356" text-anchor="end" font-size="10" fill="' . $e($axisColor) . '">+' . (count($categories) - 8) . '</text>';
            }
        }
        return $svg . '</svg>';
    }
}
