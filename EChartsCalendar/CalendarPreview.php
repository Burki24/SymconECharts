<?php

declare(strict_types=1);

namespace SymconECharts;

/** Synthetic calendar preview: draft changes never trigger archive queries. */
trait CalendarPreview
{
    private function CalendarPreviewSvg(array $values, bool $ipsView): string
    {
        $get = fn (string $name, string $type = 'integer'): mixed => $this->BarPreviewValue($values, $name, $type, $ipsView);
        $palette = EChartsBarPreview::Palette($get('EChartsTheme', 'string'));
        $color = fn (string $name, string $fallback): string => EChartsBarPreview::Color($get($name), $fallback);
        $metricLabel = match ((string) ($values['Metric'] ?? $this->ReadPropertyString('Metric'))) {
            'minimum' => 'Daily minimum', 'maximum' => 'Daily maximum', 'consumption' => 'Daily consumption',
            default   => 'Daily mean'
        };
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            true,
            $ipsView,
            $values,
            $this->Translate('Example data') . ' · ' . $this->Translate($metricLabel),
            $this->Translate('Calendar preview'),
            $color('TitleColor', $palette['text']),
            $get('TitleFontSizePercent')
        );
        $svg = str_replace('<svg xmlns', '<svg font-family="Arial, sans-serif" xmlns', $svg);
        $svg .= '<desc>' . EChartsBarPreview::E($this->Translate($metricLabel)) . '</desc>';
        try {
            $window = $this->ResolveCalendarWindow(0, $values);
        } catch (\Throwable $exception) {
            return $svg . '<text x="24" y="100" font-size="12" fill="' . EChartsBarPreview::E($palette['text']) . '">'
                . EChartsBarPreview::E($this->Translate($exception->getMessage())) . '</text></svg>';
        }
        $svg = str_replace('<svg ', '<svg data-period="' . $window['Period'] . '" data-start="' . $window['StartDate']
            . '" data-end="' . $window['EndDate'] . '" ', $svg);
        if ($window['Period'] !== 'year') {
            $svg .= '<text x="360" y="58" text-anchor="middle" font-size="11" fill="' . EChartsBarPreview::E($palette['text']) . '">'
                . EChartsBarPreview::E($window['Label'] . ' · ' . date_default_timezone_get()) . '</text>';
        }
        $vertical = $get('Orientation', 'string') === 'vertical';
        $axisColor = $color('AxisColor', $palette['text']);
        $axisSize = max(6, 11 * $get('AxisFontSizePercent') / 100);
        $labelSize = 9 * $get('ValueFontSizePercent') / 100;
        $weight = $get('LabelBold', 'boolean') ? 'bold' : 'normal';
        $text = static fn (float $x, float $y, string $content, string $fill, float $size, string $anchor = 'start'): string => '<text x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y)
            . '" fill="' . EChartsBarPreview::E($fill) . '" font-size="' . EChartsBarPreview::N($size)
            . '" text-anchor="' . $anchor . '" font-weight="' . $weight . '">' . EChartsBarPreview::E($content) . '</text>';
        $monthEnd = $get('MonthLabelPosition', 'string') === 'end';
        $weekEnd = $get('WeekdayLabelPosition', 'string') === 'end';
        $monthMargin = $get('MonthLabelMargin');
        $weekMargin = $get('WeekdayLabelMargin');
        $monthFormat = $get('MonthLabelFormat', 'string');
        $monthSpace = ($vertical ? $axisSize * ($monthFormat === 'year-month' ? 4.8 : 2.2) : $axisSize) + $monthMargin + 8;
        $weekSpace = ($vertical ? $axisSize : $axisSize * 2.2) + $weekMargin + 8;
        $left = 20.0;
        $right = 20.0;
        $top = $window['Period'] === 'year' ? 56.0 : 76.0;
        $bottom = $get('ShowColorScale', 'boolean') ? 48.0 : 16.0;
        if ($get('ShowWeekdayLabels', 'boolean')) {
            if ($vertical) {
                if ($weekEnd) {
                    $bottom += $weekSpace;
                } else {
                    $top += $weekSpace;
                }
            }
            elseif ($weekEnd) {
                $right += $weekSpace;
            } else {
                $left += $weekSpace;
            }
        }
        if ($get('ShowMonthLabels', 'boolean')) {
            if ($vertical) {
                if ($monthEnd) {
                    $right += $monthSpace;
                } else {
                    $left += $monthSpace;
                }
            }
            elseif ($monthEnd) {
                $bottom += $monthSpace;
            } else {
                $top += $monthSpace;
            }
        }
        $yearPosition = $get('YearLabelPosition', 'string');
        $yearPosition = $yearPosition === 'auto' ? ($vertical ? 'top' : 'left') : $yearPosition;
        $yearSize = 18 * $get('YearFontSizePercent') / 100;
        $yearMargin = $get('YearLabelMargin');
        if ($get('ShowYearLabel', 'boolean')) {
            $extra = $yearSize + $yearMargin + 4;
            match ($yearPosition) {
                'top'   => $top += $extra, 'bottom' => $bottom += $extra,
                'right' => $right += $extra, default => $left += $extra
            };
        }
        $plotWidth = max(40, 720 - $left - $right);
        $plotHeight = max(40, 360 - $top - $bottom);
        $first = $get('FirstWeekday');
        $offset = ($window['StartWeekday'] - $first + 7) % 7;
        $weeks = (int) ceil(($window['DayCount'] + $offset) / 7);
        $width = $plotWidth / ($vertical ? 7 : $weeks);
        $height = $plotHeight / ($vertical ? $weeks : 7);
        $low = $get('LowColor') < 0 ? 0x5070DD : $get('LowColor');
        $high = $get('HighColor') < 0 ? 0xFB628B : $get('HighColor');
        if ($get('ReverseColorScale', 'boolean')) {
            [$low, $high] = [$high, $low];
        }
        $min = $get('UseFixedColorRange', 'boolean') ? $get('ColorScaleMinimum', 'float') : 10.0;
        $max = $get('UseFixedColorRange', 'boolean') ? $get('ColorScaleMaximum', 'float') : 30.0;
        if (!is_finite($min) || !is_finite($max) || $min >= $max || !is_finite($max - $min)) {
            return $svg . '</svg>';
        }
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $borderType = $get('CellBorderType', 'string');
        $dash = static fn (string $type): string => match ($type) {
            'dashed' => ' stroke-dasharray="6 4"', 'dotted' => ' stroke-dasharray="2 3"', default => ''
        };
        $monthRows = [];
        $civilStart = new \DateTimeImmutable($window['StartDate'] . ' 12:00:00', new \DateTimeZone('UTC'));
        foreach ($window['Days'] as $index => $day) {
            $position = (int) $civilStart->diff(new \DateTimeImmutable($day['date'] . ' 12:00:00', new \DateTimeZone('UTC')))->format('%a');
            $week = intdiv($position + $offset, 7);
            $weekday = ($position + $offset) % 7;
            $x = $left + ($vertical ? $weekday : $week) * $width;
            $y = $top + ($vertical ? $week : $weekday) * $height;
            $value = 20 + 10 * sin($index / 366 * 2 * M_PI - 1.5);
            $ratio = max(0.0, min(1.0, ($value - $min) / ($max - $min)));
            $rgb = 0;
            foreach ([16, 8, 0] as $shift) {
                $rgb |= (int) round((($low >> $shift) & 255) * (1 - $ratio) + (($high >> $shift) & 255) * $ratio) << $shift;
            }
            $fill = $index % 17 === 0 ? $color('EmptyCellColor', $palette['surface']) : EChartsAsset::ColorToHex($rgb);
            $svg .= '<rect x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y)
                . '" width="' . EChartsBarPreview::N($width) . '" height="' . EChartsBarPreview::N($height)
                . '" fill="' . EChartsBarPreview::E($fill) . '" opacity="' . EChartsBarPreview::N($get('CellOpacityPercent') / 100)
                . '" stroke="' . EChartsBarPreview::E($color('CellBorderColor', $palette['background']))
                . '" stroke-width="' . $get('CellBorderWidth') . '"' . $dash($borderType) . '/>';
            if ($index === 0 || substr($day['date'], 8) === '01') {
                $monthRows[] = [$index, $week, $weekday, $day];
            }
            if ($width >= $labelSize * 2 && $height >= $labelSize + 2 && $index % 17 !== 0) {
                $label = $get('ShowValues', 'boolean') ? (string) round($value)
                    : ($get('ShowDayNumbers', 'boolean') ? (string) (int) substr($day['date'], 8) : '');
                $svg .= $text($x + $width / 2, $y + $height / 2 + 3, $label, $color('ValueColor', $palette['text']), $labelSize, 'middle');
            }
        }
        foreach ($monthRows as $monthIndex => [$index, $week, $weekday, $day]) {
            $x = $left + $week * $width;
            $y = $top + $week * $height;
            if ($get('ShowMonthSeparators', 'boolean')) {
                $points = $vertical
                    ? [[$left, $y + $height], [$left + $weekday * $width, $y + $height], [$left + $weekday * $width, $y], [$left + $plotWidth, $y]]
                    : [[$x + $width, $top], [$x + $width, $top + $weekday * $height], [$x, $top + $weekday * $height], [$x, $top + $plotHeight]];
                $svg .= '<polyline fill="none" points="' . implode(' ', array_map(static fn (array $p): string => EChartsBarPreview::N($p[0]) . ',' . EChartsBarPreview::N($p[1]), $points))
                    . '" stroke="' . EChartsBarPreview::E($color('MonthLineColor', $color('CellBorderColor', $palette['border'])))
                    . '" stroke-width="' . ($get('MonthLineWidth') ?: $get('CellBorderWidth')) . '"' . $dash($get('MonthLineType', 'string')) . '/>';
            }
            $monthStep = max(1, (int) ceil($axisSize * ($vertical ? 1.2 : ($monthFormat === 'year-month' ? 7 : 3) * 0.65) / max(1, ($vertical ? $plotHeight : $plotWidth) / $window['MonthCount'])));
            if ($get('ShowMonthLabels', 'boolean') && $monthIndex % $monthStep === 0) {
                $month = (int) substr($day['date'], 5, 2);
                $name = match ($monthFormat) {
                    'number' => sprintf('%02d', $month), 'year-month' => substr($day['date'], 0, 7),
                    default  => $this->Translate($months[$month - 1])
                };
                $endWeek = $monthRows[$monthIndex + 1][1] ?? $weeks;
                $along = $get('MonthLabelAlign', 'string') === 'center' ? ($week + $endWeek) / 2 : $week;
                $svg .= $vertical
                    ? $text(
                        $monthEnd ? $left + $plotWidth + $monthMargin : $left - $monthMargin,
                        $top + $along * $height + $axisSize / 3,
                        $name,
                        $axisColor,
                        $axisSize,
                        $monthEnd ? 'start' : 'end'
                    )
                    : $text(
                        $left + $along * $width,
                        $monthEnd ? $top + $plotHeight + $monthMargin + $axisSize : $top - $monthMargin,
                        $name,
                        $axisColor,
                        $axisSize,
                        $get('MonthLabelAlign', 'string') === 'center' ? 'middle' : 'start'
                    );
            }
        }
        if ($get('ShowWeekdayLabels', 'boolean')) {
            for ($day = 0; $day < 7; ++$day) {
                $weekdayStep = max(1, (int) ceil($axisSize * ($vertical ? 2 : 1.2) / max(1, ($vertical ? $plotWidth : $plotHeight) / 7)));
                if ($day % $weekdayStep !== 0) {
                    continue;
                }
                $name = $this->Translate($weekdays[($day + $first) % 7]);
                $svg .= $vertical ? $text(
                    $left + ($day + 0.5) * $width,
                    $weekEnd ? $top + $plotHeight + $weekMargin + $axisSize : $top - $weekMargin,
                    $name,
                    $axisColor,
                    $axisSize,
                    'middle'
                )
                    : $text(
                        $weekEnd ? $left + $plotWidth + $weekMargin : $left - $weekMargin,
                        $top + ($day + 0.6) * $height,
                        $name,
                        $axisColor,
                        $axisSize,
                        $weekEnd ? 'start' : 'end'
                    );
            }
        }
        if ($get('ShowYearLabel', 'boolean')) {
            $yearLabel = substr($window['StartDate'], 0, 4);
            if ($yearLabel !== substr($window['EndDate'], 0, 4)) {
                $yearLabel .= '/' . substr($window['EndDate'], 0, 4);
            }
            $yearText = $text(0, 0, $yearLabel, $color('YearColor', $axisColor), $yearSize, 'middle');
            $svg .= match ($yearPosition) {
                'top'    => '<g transform="translate(' . EChartsBarPreview::N($left + $plotWidth / 2) . ',' . EChartsBarPreview::N(($window['Period'] === 'year' ? 56 : 76) + $yearSize) . ')">' . $yearText . '</g>',
                'bottom' => '<g transform="translate(' . EChartsBarPreview::N($left + $plotWidth / 2) . ',' . ($get('ShowColorScale', 'boolean') ? '310' : '348') . ')">' . $yearText . '</g>',
                'right'  => '<g transform="translate(700,' . EChartsBarPreview::N($top + $plotHeight / 2) . ') rotate(-90)">' . $yearText . '</g>',
                default  => '<g transform="translate(20,' . EChartsBarPreview::N($top + $plotHeight / 2) . ') rotate(90)">' . $yearText . '</g>'
            };
        }
        if ($get('ShowColorScale', 'boolean')) {
            $svg .= '<defs><linearGradient id="calendar-scale"><stop stop-color="' . EChartsAsset::ColorToHex($low)
                . '"/><stop offset="1" stop-color="' . EChartsAsset::ColorToHex($high) . '"/></linearGradient></defs>'
                . '<rect x="270" y="332" width="180" height="9" fill="url(#calendar-scale)"/>'
                . $text(255, 341, EChartsBarPreview::FormatValue($min, 1, '', 'de'), $axisColor, 11, 'end')
                . $text(465, 341, EChartsBarPreview::FormatValue($max, 1, '', 'de'), $axisColor, 11);
        }
        return $svg . '</svg>';
    }
}
