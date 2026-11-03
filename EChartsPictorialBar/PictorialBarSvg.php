<?php

declare(strict_types=1);

namespace SymconECharts;

use DOMDocument;
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
            $import = EChartsSvgPath::Import($fileData);
            if (strlen($import['path']) > 16384) {
                throw new InvalidArgumentException(self::ERROR);
            }
            [$x, $y, $width, $height] = array_map('floatval', explode(' ', $import['viewBox']));
            if (abs($x) > 1000000 || abs($y) > 1000000 || $width < 0.000001 || $height < 0.000001
                || $width > 1000000 || $height > 1000000) {
                throw new InvalidArgumentException(self::ERROR);
            }

            // The shared validator already bounded and checked the upload envelope.
            // Parse the XML too, retaining separate path origins before combining.
            $svg = trim($fileData);
            if (preg_match('/^(?:<\?xml\b[^?]*\?>\s*)?<svg\b/is', $svg) !== 1) {
                $svg = base64_decode(preg_replace('/^data:image\/svg\+xml(?:;[^,]*)?;base64,/is', '', $svg), true);
            }
            $document = new DOMDocument();
            if (!is_string($svg) || !@$document->loadXML($svg, LIBXML_NONET)
                || $document->documentElement?->nodeName !== 'svg'
                || !in_array($document->documentElement->namespaceURI, [null, '', 'http://www.w3.org/2000/svg'], true)) {
                throw new InvalidArgumentException(self::ERROR);
            }
            $paths = [];
            foreach ($document->getElementsByTagName('path') as $element) {
                $paths[] = self::NormalizePath($element->getAttribute('d'));
            }
            $path = implode(' ', $paths);
            if ($paths === [] || strlen($path) > 16384) {
                throw new InvalidArgumentException(self::ERROR);
            }
            return ['path' => $path, 'width' => $width, 'height' => $height, 'minimumX' => $x, 'minimumY' => $y];
        } catch (Throwable) {
            throw new InvalidArgumentException(self::ERROR);
        }
    }

    /** Validate argument groups and normalize independent initial relative moves. */
    private static function NormalizePath(string $path): string
    {
        $arities = ['M' => 2, 'L' => 2, 'H' => 1, 'V' => 1, 'C' => 6, 'S' => 4, 'Q' => 4, 'T' => 2, 'A' => 7, 'Z' => 0];
        $offset = 0;
        $command = null;
        $first = true;
        $normalized = '';
        $length = strlen($path);
        while ($offset < $length) {
            $offset += strspn($path, " \t\r\n,", $offset);
            if ($offset >= $length) {
                break;
            }
            if (ctype_alpha($path[$offset])) {
                $command = $path[$offset++];
            }
            $upper = strtoupper($command ?? '');
            if (!isset($arities[$upper]) || ($first && $upper !== 'M')) {
                throw new InvalidArgumentException(self::ERROR);
            }
            if ($upper === 'Z') {
                $normalized .= $command;
                $command = null;
                continue;
            }
            $args = [];
            for ($index = 0; $index < $arities[$upper]; ++$index) {
                $offset += strspn($path, " \t\r\n,", $offset);
                // SVG arc flags may be adjacent, unlike ordinary coordinates.
                $pattern = $upper === 'A' && in_array($index, [3, 4], true)
                    ? '/\G[01]/' : '/\G[-+]?(?:\d*\.\d+|\d+\.?\d*)(?:[eE][-+]?\d+)?/';
                if (preg_match($pattern, $path, $match, 0, $offset) !== 1) {
                    throw new InvalidArgumentException(self::ERROR);
                }
                $value = (float) $match[0];
                if (!is_finite($value) || abs($value) > 1000000 || ($upper === 'A' && $index < 2 && $value < 0)) {
                    throw new InvalidArgumentException(self::ERROR);
                }
                $args[] = json_encode($value, JSON_THROW_ON_ERROR);
                $offset += strlen($match[0]);
            }
            // Each SVG path starts at its own (0,0), not at the previous path's end.
            $normalized .= ($first ? 'M' : $command) . implode(' ', $args) . ' ';
            $first = false;
            if ($upper === 'M') {
                $command = $command === 'M' ? 'L' : 'l';
            }
        }
        if ($first) {
            throw new InvalidArgumentException(self::ERROR);
        }
        return trim($normalized);
    }
}
