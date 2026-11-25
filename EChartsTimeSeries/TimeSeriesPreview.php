<?php

declare(strict_types=1);

namespace eCharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

require_once __DIR__ . '/../libs/EChartsPreviewSourceCount.php';
require_once __DIR__ . '/../libs/EChartsPeriodPreview.php';
require_once __DIR__ . '/../libs/EChartsLabelDesign.php';
require_once __DIR__ . '/../libs/EChartsPreviewSymbol.php';
require_once __DIR__ . '/../libs/EChartsValueAxisLayout.php';
require_once __DIR__ . '/../libs/EChartsSliderSize.php';

final class EChartsTimeSeriesPreview
{
    /**
     * @param list<array{variableID?:int,label:string,color:string,style:string,stackGroup?:int,unit?:string,axisPosition?:string,design?:array<string,mixed>}> $series
     * @param array<string,mixed> $design
     * @param list<array<string,mixed>> $annotations
     */
    public static function CreateSvg(
        array $series,
        string $title,
        string $theme,
        array $design,
        bool $adaptToBackground = false,
        string $backgroundColor = '',
        int $backgroundOpacityPercent = 35,
        array $annotations = [],
        string $timeAxisLabelFormat = 'auto',
        string $sourceCountCaption = 'Preview shows %d of %d sources',
        string $patternNoticeCaption = 'Large SVG patterns are simplified in the preview.',
        string $periodLabel = ''
    ): string {
        $palette = EChartsAsset::ThemePreviewPalette($theme);
        $allSeries = $series !== [] ? $series : [
            ['label' => 'Temperature', 'color' => '', 'style' => 'line'],
            ['label' => 'Humidity', 'color' => '', 'style' => 'area']
        ];
        $axisSources = [];
        foreach ($allSeries as $item) {
            $position = $item['axisPosition'] ?? 'auto';
            $axisSources[] = [
                'Unit'         => (string) ($item['unit'] ?? ''),
                'OwnAxis'      => ($item['ownAxis'] ?? false) === true,
                'AxisRange'    => $item['axisRange'] ?? null,
                'AxisPosition' => in_array($position, ['auto', 'left', 'right'], true) ? $position : 'auto'
            ];
        }
        try {
            $axisModel = EChartsUnitAxes::Build($axisSources);
        } catch (\InvalidArgumentException) {
            foreach ($axisSources as &$axisSource) {
                $axisSource['AxisPosition'] = 'auto';
                $axisSource['AxisRange'] = null;
            }
            unset($axisSource);
            $axisModel = EChartsUnitAxes::Build($axisSources);
        }
        foreach ($allSeries as $index => &$item) {
            $item['previewPaletteIndex'] = $index;
            $item['previewAxisIndex'] = $axisModel['SeriesIndexes'][$index];
        }
        unset($item);
        $series = array_slice(array_values(array_filter(
            $allSeries,
            static fn (array $item): bool => ($item['initiallyVisible'] ?? true) !== false
        )), 0, 4);
        $visibleAxes = [];
        $previewPatternBytes = 0;
        $simplifiedPatterns = false;
        foreach ($series as $index => $item) {
            $visibleAxes[$item['previewAxisIndex']] ??= $index;
        }
        $fallbackColors = $palette['seriesColors'];
        $effectiveColors = [];
        foreach ($series as $index => $item) {
            $effectiveColors[] = preg_match('/^#[0-9A-F]{6}$/i', $item['color']) === 1
                ? $item['color']
                : $fallbackColors[$item['previewPaletteIndex'] % count($fallbackColors)];
        }
        $legendPosition = (string) ($design['legendPosition'] ?? 'top');
        $lineWidth = 2.5 * max(50, min(200, (int) ($design['lineWidthPercent'] ?? 100))) / 100;
        $symbolRadius = 3.5 * max(50, min(200, (int) ($design['symbolSizePercent'] ?? 100))) / 100;
        $showSymbols = (bool) ($design['showSymbols'] ?? false);
        $showValues = (bool) ($design['showValues'] ?? false);
        $valueLabelPosition = (string) ($design['valueLabelPosition'] ?? 'top');
        $valueLabelDistance = (int) ($design['valueLabelDistance'] ?? 5);
        $valueColor = self::DesignColor($design['valueColor'] ?? '', $palette['text']);
        $valueFontSize = self::FontSize($design['valueFontSizePercent'] ?? 100, 12);
        $smoothLines = (bool) ($design['smoothLines'] ?? false);
        $showGrid = (bool) ($design['showGrid'] ?? true);
        $showXAxis = (bool) ($design['showXAxis'] ?? true);
        $showYAxis = (bool) ($design['showYAxis'] ?? true);
        $titleColor = self::DesignColor($design['titleColor'] ?? '', $palette['text']);
        $legendColor = self::DesignColor($design['legendColor'] ?? '', $palette['text']);
        $axisColor = self::DesignColor($design['axisColor'] ?? '', '');
        $gridColor = self::DesignColor($design['gridColor'] ?? '', $palette['track']);
        $titleFontSize = self::FontSize($design['titleFontSizePercent'] ?? 100, 20);
        $legendFontSize = self::FontSize($design['legendFontSizePercent'] ?? 100, 13);
        $axisFontSize = self::FontSize($design['axisFontSizePercent'] ?? 100, 11);
        $timeAxisLabelFormat = in_array($timeAxisLabelFormat, ['auto', 'time', 'date', 'date-time'], true)
            ? $timeAxisLabelFormat
            : 'auto';
        $areaOpacity = max(0, min(100, (int) ($design['areaOpacityPercent'] ?? 22))) / 100;
        $background = EChartsIPSViewBackground::ResolvedPreviewBackground(
            $palette,
            $adaptToBackground,
            $backgroundColor,
            $backgroundOpacityPercent,
            (int) ($design['chartBackgroundColor'] ?? -1)
        );
        $backgroundOpacityPercent = (int) round($background['opacity'] * 100);
        $titleBaseline = max(31, $titleFontSize + 6);
        $legendTop = max(64, $titleBaseline + $legendFontSize + 6);
        $plotTop = $legendPosition === 'top' ? max(92, $legendTop + 20) : max(62, $titleBaseline + 16);
        $sampleBottom = $legendPosition === 'bottom' ? 300 : 330;
        $sliderSize = ($design['enableZoom'] ?? true)
            ? EChartsSliderSize::Resolve((int) ($design['zoomSliderSize'] ?? 0), 12) : 0;
        $plotBottom = $sampleBottom - max(0, $sliderSize - 12);
        $sampleY = static fn (float $y): float => $plotTop + ($y - $plotTop) * ($plotBottom - $plotTop) / ($sampleBottom - $plotTop);
        $sideCounts = ['left' => 0, 'right' => 0];
        if ($showYAxis) {
            foreach (array_keys($visibleAxes) as $axisIndex) {
                ++$sideCounts[$axisModel['Axes'][$axisIndex]['position']];
            }
        }
        // The 750-wide sample keeps a margin for the outer labels on each side.
        $axisStep = EChartsValueAxisLayout::Spacing((int) ($design['valueAxisSpacing'] ?? 0), 90, 120, max($sideCounts) - 1);
        $plotLeft = 70 + $axisStep * max(0, $sideCounts['left'] - 1);
        $plotRight = 680 - $axisStep * max(0, $sideCounts['right'] - 1);
        $plotWidth = $plotRight - $plotLeft;
        $sampleX = static fn (int $x): int => (int) round($plotLeft + ($x - 70) * $plotWidth / 610);
        $content = '';
        $definitions = '';

        if ($showGrid) {
            for ($index = 0; $index < 5; ++$index) {
                $y = $plotTop + ($plotBottom - $plotTop) * $index / 4;
                $content .= '<line x1="' . self::N($plotLeft) . '" y1="' . self::N($y) . '" x2="' . self::N($plotRight) . '" y2="' . self::N($y)
                    . '" stroke="' . SVGPreviewHelper::escape($gridColor) . '" stroke-width="1"/>';
            }
        }
        if ($design['showVerticalGrid'] ?? false) {
            $verticalGridColor = self::DesignColor($design['verticalGridColor'] ?? '', $palette['track']);
            for ($index = 0; $index < 3; ++$index) {
                $x = $plotLeft + $plotWidth * $index / 2;
                $content .= '<line x1="' . $x . '" y1="' . self::N($plotTop) . '" x2="' . $x . '" y2="' . self::N($plotBottom)
                    . '" stroke="' . SVGPreviewHelper::escape($verticalGridColor) . '" stroke-width="1"/>';
            }
        }
        if ($showXAxis) {
            $content .= '<line x1="' . self::N($plotLeft) . '" y1="' . self::N($plotBottom) . '" x2="' . self::N($plotRight) . '" y2="'
                . self::N($plotBottom) . '" stroke="' . SVGPreviewHelper::escape($axisColor !== '' ? $axisColor : $palette['border']) . '"/>';
            foreach (self::TimeAxisLabels($timeAxisLabelFormat) as $index => $label) {
                $x = $plotLeft + $plotWidth * $index / 2;
                $content .= '<text x="' . self::N($x) . '" y="' . self::N($plotBottom + 19)
                    . '" text-anchor="' . ($index === 0 ? 'start' : ($index === 2 ? 'end' : 'middle'))
                    . '" fill="' . SVGPreviewHelper::escape($axisColor !== '' ? $axisColor : $palette['muted'])
                    . '" font-size="' . $axisFontSize . '">'
                    . SVGPreviewHelper::escape($label) . '</text>';
            }
        }
        if ($showYAxis) {
            $visiblePositions = ['left' => 0, 'right' => 0];
            foreach ($axisModel['Axes'] as $axisIndex => $axis) {
                if (!array_key_exists($axisIndex, $visibleAxes)) {
                    continue;
                }
                $sourceIndex = $visibleAxes[$axisIndex];
                $color = $axisColor !== '' ? $axisColor : $effectiveColors[$sourceIndex];
                $left = $axis['position'] === 'left';
                $visiblePosition = $visiblePositions[$axis['position']]++;
                $x = $left ? $plotLeft - $visiblePosition * $axisStep : $plotRight + $visiblePosition * $axisStep;
                $content .= '<line data-preview-axis="' . $axis['position'] . '" x1="' . $x
                    . '" y1="' . self::N($plotTop) . '" x2="' . $x . '" y2="'
                    . self::N($plotBottom) . '" stroke="' . SVGPreviewHelper::escape($color) . '"/>';
                $captionX = $left ? $x + 4 : $x - 4;
                // Leave room for each unit and range label when the axis lines are close together.
                $captionTop = $plotTop + ($axisStep < 8 * $axisFontSize ? $visiblePosition * (2 * $axisFontSize + 12) : 0);
                if (isset($axis['minimum'], $axis['maximum'])) {
                    $content .= '<text data-preview-axis-range="' . $axis['position'] . '" x="' . $captionX
                        . '" y="' . self::N($captionTop + $axisFontSize + 3) . '" text-anchor="' . ($left ? 'start' : 'end')
                        . '" fill="' . SVGPreviewHelper::escape($color) . '" font-size="' . $axisFontSize . '">'
                        . SVGPreviewHelper::escape($axis['minimum'] . ' … ' . $axis['maximum']) . '</text>';
                }
                if ($axis['unit'] !== '') {
                    $content .= '<text data-preview-axis-unit="' . $axis['position'] . '" x="' . $captionX
                        . '" y="' . self::N($captionTop - 7) . '" text-anchor="' . ($left ? 'start' : 'end')
                        . '" fill="' . SVGPreviewHelper::escape($color) . '" font-size="' . $axisFontSize . '">'
                        . SVGPreviewHelper::escape(mb_strimwidth($axis['unit'], 0, 8, '…')) . '</text>';
                }
            }
        }

        $annotationAreas = '';
        $annotationLines = '';
        $annotationCount = count($annotations);
        foreach ($annotations as $annotationIndex => $annotation) {
            $seriesIndex = (int) ($annotation['seriesIndex'] ?? -1);
            if ($seriesIndex < 0 || !isset($effectiveColors[$seriesIndex])) {
                continue;
            }
            $color = is_string($annotation['color'] ?? null)
                && preg_match('/^#[0-9A-F]{6}$/i', $annotation['color']) === 1
                ? $annotation['color']
                : $effectiveColors[$seriesIndex];
            $label = trim(is_string($annotation['label'] ?? null) ? $annotation['label'] : '');
            $y = $plotTop + ($plotBottom - $plotTop) * ($annotationIndex + 1) / ($annotationCount + 1);
            if (($annotation['type'] ?? 'line') === 'area') {
                $opacity = max(0, min(100, (int) ($annotation['opacityPercent'] ?? 18))) / 100;
                $annotationAreas .= '<rect x="' . self::N($plotLeft) . '" y="' . self::N($y - 13) . '" width="' . self::N($plotWidth) . '" height="26"'
                    . ' fill="' . SVGPreviewHelper::escape($color) . '" opacity="' . self::N($opacity) . '"'
                    . ' data-annotation-type="area"/>';
                if ($label !== '') {
                    $annotationAreas .= '<text x="' . self::N($plotRight - 5) . '" y="' . self::N($y - 17) . '" text-anchor="end"'
                        . ' fill="' . SVGPreviewHelper::escape($color) . '" font-size="11">'
                        . SVGPreviewHelper::escape($label) . '</text>';
                }
                continue;
            }
            $lineWidth = 2.0 * max(50, min(200, (int) ($annotation['lineWidthPercent'] ?? 100))) / 100;
            $dashArray = match ((string) ($annotation['lineType'] ?? 'solid')) {
                'dashed' => '10 7',
                'dotted' => '2 6',
                default  => ''
            };
            $annotationLines .= '<line x1="' . self::N($plotLeft) . '" y1="' . self::N($y) . '" x2="' . self::N($plotRight) . '" y2="' . self::N($y)
                . '" stroke="' . SVGPreviewHelper::escape($color) . '" stroke-width="' . self::N($lineWidth) . '"'
                . ($dashArray !== '' ? ' stroke-dasharray="' . $dashArray . '"' : '')
                . ' data-annotation-type="line"/>';
            if ($label !== '') {
                $annotationLines .= '<text x="' . self::N($plotRight - 5) . '" y="' . self::N($y - 6) . '" text-anchor="end"'
                    . ' fill="' . SVGPreviewHelper::escape($color) . '" font-size="11">'
                    . SVGPreviewHelper::escape($label) . '</text>';
            }
        }
        $content .= $annotationAreas;

        $positions = array_map($sampleX, [70, 190, 310, 430, 540, 680]);
        $stackBands = self::StackedSampleBands($series, $positions, $plotTop, $plotBottom, $smoothLines);
        foreach ($series as $index => $item) {
            $color = $effectiveColors[$index];
            $sourceDesign = is_array($item['design'] ?? null) ? $item['design'] : [];
            $sourceLineWidth = $sourceDesign !== []
                ? 2.5 * max(50, min(200, (int) ($sourceDesign['lineWidthPercent'] ?? 100))) / 100
                : $lineWidth;
            $sourceSmooth = $sourceDesign !== []
                ? (bool) ($sourceDesign['smoothLine'] ?? false)
                : $smoothLines;
            $sourceSymbol = $sourceDesign !== []
                ? (string) ($sourceDesign['pointSymbol'] ?? 'none')
                : ($showSymbols ? 'circle' : 'none');
            $sourceSymbolRadius = $sourceDesign !== []
                ? 3.5 * max(50, min(200, (int) ($sourceDesign['pointSizePercent'] ?? 100))) / 100
                : $symbolRadius;
            $sourceAreaOpacity = $sourceDesign !== []
                ? max(0, min(100, (int) ($sourceDesign['areaOpacityPercent'] ?? 22))) / 100
                : $areaOpacity;
            $offset = $index * 24.0;
            $band = $stackBands[$index] ?? null;
            $valueY = $band['valueY'] ?? $sampleY(185 + $offset / 2);
            if ($band !== null) {
                $path = $band['edge'];
            } elseif ($sourceSmooth) {
                $path = 'M ' . $positions[0] . ' ' . self::N($sampleY(250 - $offset))
                    . ' C ' . $sampleX(155) . ' ' . self::N($sampleY(210 + $offset)) . ', ' . $sampleX(225) . ' ' . self::N($sampleY(270 - $offset))
                    . ', ' . $positions[2] . ' ' . self::N($sampleY(185 + $offset / 2))
                    . ' S ' . $sampleX(470) . ' ' . self::N($sampleY(225 - $offset)) . ', ' . $positions[4] . ' ' . self::N($sampleY(135 + $offset))
                    . ' S ' . $sampleX(625) . ' ' . self::N($sampleY(170 + $offset / 2)) . ', ' . $positions[5] . ' ' . self::N($sampleY(105 + $offset));
            } else {
                $path = 'M ' . $positions[0] . ' ' . self::N($sampleY(250 - $offset))
                    . ' L ' . $positions[1] . ' ' . self::N($sampleY(215 + $offset))
                    . ' L ' . $positions[2] . ' ' . self::N($sampleY(185 + $offset / 2))
                    . ' L ' . $positions[3] . ' ' . self::N($sampleY(225 - $offset))
                    . ' L ' . $positions[4] . ' ' . self::N($sampleY(135 + $offset))
                    . ' L ' . $positions[5] . ' ' . self::N($sampleY(105 + $offset));
            }
            if ($band !== null || ($item['style'] ?? 'line') === 'area') {
                $fill = $color;
                $fillMode = (string) ($sourceDesign['areaFillMode'] ?? 'color');
                if ($fillMode === 'gradient') {
                    $gradientID = 'area-gradient-' . $index;
                    $gradientEnd = preg_match('/^#[0-9A-F]{6}$/i', (string) ($sourceDesign['areaGradientColor'] ?? '')) === 1
                        ? (string) $sourceDesign['areaGradientColor']
                        : $color;
                    $gradientEndOpacity = $gradientEnd === $color ? '0' : '1';
                    $definitions .= '<linearGradient id="' . $gradientID . '" x1="0" y1="0" x2="0" y2="1">'
                        . '<stop offset="0%" stop-color="' . SVGPreviewHelper::escape($color) . '"/>'
                        . '<stop offset="100%" stop-color="' . SVGPreviewHelper::escape($gradientEnd)
                        . '" stop-opacity="' . $gradientEndOpacity . '"/></linearGradient>';
                    $fill = 'url(#' . $gradientID . ')';
                } elseif ($fillMode === 'svg'
                    && str_starts_with((string) ($sourceDesign['areaPatternImage'] ?? ''), 'data:image/svg+xml;base64,')) {
                    $patternBytes = strlen($sourceDesign['areaPatternImage']);
                    if ($patternBytes > 12000 || $previewPatternBytes + $patternBytes > 24000) {
                        $simplifiedPatterns = true;
                    } else {
                        $previewPatternBytes += $patternBytes;
                        $patternID = 'area-pattern-' . $index;
                        $patternWidth = 64.0 * max(25, min(400, (int) ($sourceDesign['areaSVGSizePercent'] ?? 100))) / 100;
                        $aspectRatio = max(0.05, min(20.0, (float) ($sourceDesign['areaPatternAspectRatio'] ?? 1.0)));
                        $patternHeight = $patternWidth / $aspectRatio;
                        $definitions .= '<pattern id="' . $patternID . '" width="' . self::N($patternWidth)
                            . '" height="' . self::N($patternHeight) . '" patternUnits="userSpaceOnUse">'
                            . '<image href="' . SVGPreviewHelper::escape((string) $sourceDesign['areaPatternImage'])
                            . '" width="' . self::N($patternWidth) . '" height="' . self::N($patternHeight)
                            . '" preserveAspectRatio="xMidYMid meet"/></pattern>';
                        $fill = 'url(#' . $patternID . ')';
                    }
                }
                $returnPath = $band['returnPath'] ?? ('L ' . $positions[5] . ' ' . self::N($plotBottom) . ' L ' . $positions[0] . ' ' . self::N($plotBottom));
                $content .= '<path d="' . $path . ' ' . $returnPath . ' Z" fill="' . SVGPreviewHelper::escape($fill)
                    . '" opacity="' . self::N($sourceAreaOpacity) . '"/>';
            }
            $dashArray = match ((string) ($sourceDesign['lineType'] ?? 'solid')) {
                'dashed' => '10 7',
                'dotted' => '2 6',
                default  => ''
            };
            $content .= '<path d="' . $path . '" fill="none" stroke="' . SVGPreviewHelper::escape($color)
                . '" stroke-width="' . self::N($sourceLineWidth) . '" stroke-linecap="round"'
                . ($dashArray !== '' ? ' stroke-dasharray="' . $dashArray . '"' : '') . '/>';
            if ($sourceSymbol !== 'none') {
                $content .= self::Symbol(
                    $sourceSymbol,
                    $positions[2],
                    $valueY,
                    $sourceSymbolRadius,
                    $color
                );
            }
            if ($showValues) {
                $unit = trim((string) ($item['unit'] ?? ''));
                $placement = EChartsLabelDesign::PreviewRect(
                    $positions[2],
                    $valueY,
                    0,
                    0,
                    $valueLabelPosition === 'auto' ? 'top' : $valueLabelPosition,
                    $valueLabelDistance
                );
                $content .= '<text data-preview-value-label="true" x="' . self::N($placement['x'])
                    . '" y="' . self::N($placement['y']) . '" text-anchor="' . $placement['anchor']
                    . '" fill="' . SVGPreviewHelper::escape($valueColor) . '" font-size="' . $valueFontSize . '">'
                    . SVGPreviewHelper::escape(($band['value'] ?? 42) . ($unit !== '' ? ' ' . $unit : '')) . '</text>';
            }
        }
        $content .= $annotationLines;

        if ($sliderSize > 0) {
            $sliderY = $plotBottom + 23;
            $content .= '<rect data-preview-zoom="horizontal" x="' . self::N($plotLeft) . '" y="' . $sliderY
                . '" width="' . self::N($plotWidth) . '" height="' . $sliderSize . '" rx="2" fill="'
                . SVGPreviewHelper::escape($palette['track']) . '" stroke="' . SVGPreviewHelper::escape($palette['border']) . '"/>'
                . '<rect x="' . self::N($plotLeft + $plotWidth * 0.15) . '" y="' . $sliderY
                . '" width="' . self::N($plotWidth * 0.7) . '" height="' . $sliderSize . '" rx="2" fill="'
                . SVGPreviewHelper::escape($palette['accent']) . '" fill-opacity="0.4"/>';
        }

        if ($legendPosition !== 'hidden') {
            $legendY = $legendPosition === 'bottom' ? 352 : $legendTop;
            $legendX = 80.0;
            foreach ($series as $index => $item) {
                $color = $effectiveColors[$index];
                $label = trim($item['label']) !== '' ? $item['label'] : 'Series ' . ($index + 1);
                $content .= '<line x1="' . self::N($legendX) . '" y1="' . self::N($legendY)
                    . '" x2="' . self::N($legendX + 22) . '" y2="' . self::N($legendY)
                    . '" stroke="' . SVGPreviewHelper::escape($color) . '" stroke-width="3"/>';
                $content .= '<text x="' . self::N($legendX + 29) . '" y="' . self::N($legendY + 5)
                    . '" fill="' . SVGPreviewHelper::escape($legendColor) . '" font-size="' . $legendFontSize . '">'
                    . SVGPreviewHelper::escape($label) . '</text>';
                $legendX += min(190.0, 65.0 + strlen($label) * 7.0 * $legendFontSize / 13);
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 750 390" role="img"'
            . ' data-legend-position="' . SVGPreviewHelper::escape($legendPosition) . '"'
            . ' data-smooth-lines="' . ($smoothLines ? 'true' : 'false') . '"'
            . ' data-show-symbols="' . ($showSymbols ? 'true' : 'false') . '"'
            . ' data-time-axis-label-format="' . SVGPreviewHelper::escape($timeAxisLabelFormat) . '"'
            . ' data-adapt-to-background="' . ($adaptToBackground ? 'true' : 'false') . '"'
            . ' data-background-opacity="' . $backgroundOpacityPercent . '"'
            . ' data-area-opacity="' . self::N($areaOpacity) . '"'
            . ' data-axis-color="'
            . SVGPreviewHelper::escape($axisColor !== '' ? $axisColor : ($effectiveColors[0] ?? $palette['border'])) . '">'
            . '<rect width="750" height="390" rx="18" fill="' . SVGPreviewHelper::escape($background['color']) . '"'
            . ($adaptToBackground ? ' fill-opacity="' . self::N($backgroundOpacityPercent / 100) . '"' : '') . '/>'
            . ($definitions !== '' ? '<defs>' . $definitions . '</defs>' : '')
            . '<text x="375" y="' . $titleBaseline . '" fill="' . SVGPreviewHelper::escape($titleColor)
            . '" font-size="' . $titleFontSize . '" font-weight="600" text-anchor="middle">'
            . SVGPreviewHelper::escape(trim($title) !== '' ? $title : 'Time Series') . '</text>'
            . $content . '</svg>';

        if ($simplifiedPatterns) {
            $svg = substr($svg, 0, -strlen('</svg>'))
                . '<text x="20" y="380" font-size="11" fill="' . SVGPreviewHelper::escape($palette['muted'])
                . '" data-preview-patterns-simplified="true">' . SVGPreviewHelper::escape($patternNoticeCaption) . '</text></svg>';
        }
        return EChartsPreviewSourceCount::Append(
            EChartsPeriodPreview::Apply($svg, $periodLabel !== '', $periodLabel, $palette),
            count($series),
            count($allSeries),
            $sourceCountCaption,
            $palette['muted'],
            730,
            380
        );
    }

    /**
     * @param list<array<string,mixed>> $series visible preview sources
     * @param list<int> $positions sample X coordinates within the visible plot
     * @return array<int,array{edge:string,returnPath:string,valueY:float,value:int}>
     */
    private static function StackedSampleBands(array $series, array $positions, int $top, int $bottom, bool $smoothLines): array
    {
        $groups = [];
        foreach ($series as $index => $item) {
            $group = $item['stackGroup'] ?? 0;
            if ($group > 0) {
                $groups[$group][] = $index;
            }
        }
        if ($groups === []) {
            return [];
        }
        $profile = [8, 28, 42, 34, 20, 5];
        $scale = ($bottom - $top) / (50 * max(array_map('count', $groups)));
        $bands = [];
        foreach ($groups as $indices) {
            $lower = array_fill_keys($positions, (float) $bottom);
            $lowerSmooth = false;
            foreach ($indices as $ordinal => $index) {
                $sample = $ordinal % 2 === 0 ? $profile : array_reverse($profile);
                $upper = [];
                foreach ($positions as $point => $x) {
                    $upper[$x] = $lower[$x] - $sample[$point] * $scale;
                }
                $smooth = $series[$index]['design']['smoothLine'] ?? $smoothLines;
                $bands[$index] = [
                    'edge'       => self::SamplePath($upper, $smooth, 'M'),
                    'returnPath' => self::SamplePath(array_reverse($lower, true), $lowerSmooth, 'L'),
                    'valueY'     => $upper[$positions[2]],
                    'value'      => $sample[2]
                ];
                $lower = $upper;
                $lowerSmooth = $smooth;
            }
        }
        return $bands;
    }

    /** @param array<int,float> $points ordered x => y coordinates of one edge */
    private static function SamplePath(array $points, bool $smooth, string $firstCommand): string
    {
        $segments = [];
        $previousX = null;
        foreach ($points as $x => $y) {
            $command = $previousX === null ? $firstCommand : 'L';
            if ($smooth && $previousX !== null) {
                $middle = self::N(($previousX + $x) / 2);
                $command = 'C ' . $middle . ' ' . self::N($points[$previousX]) . ' ' . $middle . ' ' . self::N($y);
            }
            $segments[] = $command . ' ' . $x . ' ' . self::N($y);
            $previousX = $x;
        }
        return implode(' ', $segments);
    }

    private static function DesignColor(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9A-F]{6}$/i', $value) === 1 ? $value : $fallback;
    }

    private static function FontSize(mixed $value, int $base): int
    {
        $percent = is_numeric($value) ? (int) $value : 100;
        return (int) round($base * max(50, min(200, $percent)) / 100);
    }

    /** @return list<string> */
    private static function TimeAxisLabels(string $format): array
    {
        return match ($format) {
            'time'      => ['06:00', '12:00', '18:00'],
            'date'      => ['05.10.26', '06.10.26', '07.10.26'],
            'date-time' => ['05.10. 06:00', '06.10. 12:00', '07.10. 18:00'],
            default     => ['-24 h', '-12 h', 'Now']
        };
    }

    private static function Symbol(string $symbol, float $x, float $y, float $radius, string $color): string
    {
        return EChartsPreviewSymbol::Symbol($symbol, $x, $y, $radius, $color);
    }

    private static function N(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
