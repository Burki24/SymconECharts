<?php

declare(strict_types=1);

namespace eCharts;

use InvalidArgumentException;
use Throwable;

require_once __DIR__ . '/../libs/EChartsSvgPath.php';

/** Path-only upload adapter. Original SVG markup never reaches a renderer. */
final class PictorialBarSvg
{
    private const ERROR = 'The custom SVG shape is invalid or unsupported. Use a path-only SVG with a viewBox.';

    /** @return array{path:string,width:float,height:float,minimumX:float,minimumY:float} */
    public static function Import(string $fileData): array
    {
        try {
            $import = EChartsSvgPath::ImportNormalizedSymbol($fileData, 16384, 1000000);
            [$x, $y, $width, $height] = array_map('floatval', explode(' ', $import['viewBox']));
            if (abs($x) > 1000000 || abs($y) > 1000000 || $width < 0.000001 || $height < 0.000001
                || $width > 1000000 || $height > 1000000) {
                throw new InvalidArgumentException(self::ERROR);
            }

            return ['path' => $import['path'], 'width' => $width, 'height' => $height, 'minimumX' => $x, 'minimumY' => $y];
        } catch (Throwable) {
            throw new InvalidArgumentException(self::ERROR);
        }
    }
}
