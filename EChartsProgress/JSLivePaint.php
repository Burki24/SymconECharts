<?php

declare(strict_types=1);

/*
 * loading-bar 0.1.1 gradient resource geometry, adapted to inert native paints.
 * No loading-bar runtime or arbitrary resource generator is executed.
 *
 * The MIT License (MIT)
 * Copyright (c) 2017, loading.io
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SymconECharts;

use InvalidArgumentException;

require_once __DIR__ . '/../libs/EChartsSvgImage.php';

/** A closed paint grammar; never fetch URLs or execute source resource generators. */
final class JSLiveProgressPaint
{
    public static function Build(array $settings): array
    {
        $paints = [];
        if ($settings['fill_backgroundFile'] !== '') {
            if (strtolower(trim($settings['fill_backgroundFileType'])) !== 'image/svg+xml') {
                throw new InvalidArgumentException('FORMAT_UNSUPPORTED: only embedded SVG fill images are supported.');
            }
            $paints['fill'] = self::Image($settings['fill_backgroundFile']);
        }
        foreach (['fill', 'stroke'] as $key) {
            $input = $settings['override_' . $key];
            if ($input === '') {
                continue;
            }
            if (str_starts_with($input, 'data:image/svg+xml;base64,')) {
                $paints[$key] = self::Image($input);
            } elseif (preg_match('/^#[a-fA-F0-9]{3}(?:[a-fA-F0-9]{1}|[a-fA-F0-9]{3}|[a-fA-F0-9]{5})?$/D', $input) === 1) {
                $paints[$key] = ['kind' => 'color', 'color' => $input];
            } else {
                $paints[$key] = self::Gradient($input);
            }
        }
        return $paints;
    }

    private static function Image(string $input): array
    {
        try {
            $image = EChartsSvgImage::ImportPaint($input);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: invalid inert SVG paint: ' . $exception->getMessage(), 0, $exception);
        }
        return ['kind' => 'image', 'dataUri' => $image['dataUri'], 'width' => $image['pixelWidth'], 'height' => $image['pixelHeight']];
    }

    private static function Gradient(string $input): array
    {
        if (strlen($input) > 2048 || preg_match('/^data:ldbar\/res,gradient\(([^()]*)\)$/D', $input, $match) !== 1) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: paint must be an embedded SVG, hex color or bounded loading-bar gradient.');
        }
        $args = array_map('trim', explode(',', $match[1]));
        if (count($args) < 4 || count($args) > 18) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: gradients require two to sixteen colors.');
        }
        foreach (array_slice($args, 0, 2) as $number) {
            if (!is_numeric($number) || !is_finite((float) $number)) {
                throw new InvalidArgumentException('FORMAT_UNSUPPORTED: gradient direction and duration must be finite.');
            }
        }
        [$angle, $duration] = array_map('floatval', array_slice($args, 0, 2));
        if ($angle < 0 || $angle > 360 || $duration < 0 || $duration > 3600) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: gradient direction or duration is outside the supported range.');
        }
        $colors = array_slice($args, 2);
        foreach ($colors as $color) {
            if (preg_match('/^#(?:[a-fA-F0-9]{3}|[a-fA-F0-9]{4}|[a-fA-F0-9]{6}|[a-fA-F0-9]{8})$/D', $color) !== 1) {
                throw new InvalidArgumentException('FORMAT_UNSUPPORTED: gradient colors must be explicit hexadecimal colors.');
            }
        }
        // Reconstruct the pinned loading-bar resource's initial static image,
        // including its four palette repetitions and default square image size.
        // The original cyclic movement is reported as a separate approximation.
        $radians = deg2rad($angle);
        $gx = cos($radians) ** 2;
        $gy = sqrt(max(0, $gx - $gx ** 2));
        if ($angle > 45) {
            $gy = sin($radians) ** 2;
            $gx = sqrt(max(0, $gy - $gy ** 2));
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 100 100"><defs><linearGradient id="g" x1="0" y1="0" x2="' . $gx . '" y2="' . $gy . '">';
        $count = count($colors) * 4;
        for ($i = 0; $i <= $count; ++$i) {
            $svg .= '<stop offset="' . ($i / $count) . '" stop-color="' . $colors[$i % count($colors)] . '"/>';
        }
        $svg .= '</linearGradient></defs><rect width="400" height="400" fill="url(#g)"/></svg>';
        return ['kind' => 'gradient', 'angle' => $angle, 'duration' => $duration, 'colors' => $colors,
            'dataUri'  => 'data:image/svg+xml;base64,' . base64_encode($svg), 'width' => 150, 'height' => 150];
    }
}
