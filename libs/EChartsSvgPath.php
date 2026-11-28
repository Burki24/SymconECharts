<?php

declare(strict_types=1);

namespace eCharts;

use DOMElement;
use InvalidArgumentException;

require_once __DIR__ . '/EChartsSvgInput.php';

/**
 * Imports a path-only SVG as a bounded ECharts path symbol.
 *
 * The original SVG markup is never forwarded to a visualization. Only the
 * validated path data and viewBox required by the trusted preview are kept.
 */
final class EChartsSvgPath
{
    public const INVALID_PATH_DATA = 'The SVG contains unsupported or invalid path data.';
    private const MAX_FILE_BYTES = 131072;
    private const MAX_INPUT_BYTES = 196608;
    private const MAX_PATH_BYTES = 65535;
    private const MAX_PATHS = 32;
    private const ALLOWED_ELEMENTS = ['svg', 'g', 'path'];

    /** @return array{path: string, viewBox: string, pivotX?: float, pivotY?: float} */
    public static function Import(string $fileData): array
    {
        return self::Read($fileData, self::MAX_PATH_BYTES, null);
    }

    /** Produce explicit symbol commands within the caller's stricter geometry and size budgets. */
    public static function ImportNormalizedSymbol(string $fileData, int $maxPathBytes, float $coordinateLimit): array
    {
        return self::Read($fileData, min($maxPathBytes, self::MAX_PATH_BYTES), $coordinateLimit);
    }

    private static function Read(string $fileData, int $maxPathBytes, ?float $symbolCoordinateLimit): array
    {
        $root = EChartsSvgInput::Load(
            $fileData,
            self::MAX_INPUT_BYTES,
            self::MAX_FILE_BYTES,
            'The selected SVG file is empty or too large.',
            'The SVG file exceeds the 128 KiB import limit.'
        );
        $viewBox = self::NormalizeViewBox($root->getAttribute('viewBox'));
        $pivot = $root->hasAttribute('data-echarts-pivot') ? self::ReadPivot($root->getAttribute('data-echarts-pivot'), $viewBox) : null;

        $paths = [];
        self::CollectPaths($root, $paths);
        if ($paths === []) {
            throw new InvalidArgumentException('The SVG must contain between one and 32 path elements.');
        }
        if (strlen(implode(' ', $paths)) > $maxPathBytes) {
            throw new InvalidArgumentException('The combined SVG path exceeds the 64 KiB import limit.');
        }
        foreach ($paths as $index => $path) {
            $paths[$index] = self::ValidatePath($path, $index > 0, $symbolCoordinateLimit);
        }
        $path = implode(' ', $paths);
        // A corrected implicit relative line can add one command per joined path.
        // A command letter already separates two paths, even in a full-size compact Gauge.
        if ($symbolCoordinateLimit === null && strlen($path) > $maxPathBytes) {
            $path = implode('', $paths);
        }
        if (strlen($path) > $maxPathBytes) {
            throw new InvalidArgumentException('The combined SVG path exceeds the 64 KiB import limit.');
        }
        $result = ['path' => $path, 'viewBox' => $viewBox];
        if ($pivot !== null) {
            $result['pivotX'] = $pivot[0];
            $result['pivotY'] = $pivot[1];
        }

        return $result;
    }

    /** @param list<string> $paths */
    private static function CollectPaths(DOMElement $element, array &$paths): void
    {
        if (!in_array($element->localName, self::ALLOWED_ELEMENTS, true)
            || !in_array($element->namespaceURI, [null, '', EChartsSvgInput::SVG_NAMESPACE], true)) {
            throw new InvalidArgumentException('The SVG may contain only svg, g and path elements.');
        }
        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->localName);
            if ($name === 'transform') {
                throw new InvalidArgumentException('SVG transformations are not supported; convert them to paths before importing.');
            }
            if (str_contains($name, ':') || $name === 'href' || preg_match('/^on[a-z]+$/D', $name) === 1) {
                throw new InvalidArgumentException('SVG event handlers and external references are not supported.');
            }
        }
        if ($element->localName === 'path') {
            if (count($paths) >= self::MAX_PATHS) {
                throw new InvalidArgumentException('The SVG must contain between one and 32 path elements.');
            }
            if (!$element->hasAttribute('d')) {
                throw new InvalidArgumentException('Every SVG path requires path data.');
            }
            $paths[] = trim((string) preg_replace('/\s+/', ' ', $element->getAttribute('d')));
        }
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                self::CollectPaths($child, $paths);
            } elseif ($child->nodeType !== XML_COMMENT_NODE && !($child->nodeType === XML_TEXT_NODE && trim($child->nodeValue) === '')) {
                throw new InvalidArgumentException('The SVG may contain only svg, g and path elements.');
            }
        }
    }

    /** Validate each command group, keeping compact Gauge paths and explicit PictorialBar symbols. */
    private static function ValidatePath(string $path, bool $followsPath, ?float $symbolCoordinateLimit): string
    {
        $error = self::INVALID_PATH_DATA;
        if ($path === '' || !in_array($path[0], ['M', 'm'], true)
            || preg_match('/[A-Za-z,]\s*,|,\s*(?:[A-Za-z]|$)/', $path) === 1) {
            throw new InvalidArgumentException($error);
        }
        $arities = ['M' => 2, 'L' => 2, 'H' => 1, 'V' => 1, 'C' => 6, 'S' => 4, 'Q' => 4, 'T' => 2, 'A' => 7, 'Z' => 0];
        $offset = 0;
        $length = strlen($path);
        $command = null;
        $normalized = '';
        $first = true;
        while ($offset < $length) {
            $offset += strspn($path, ' ,', $offset);
            if ($offset >= $length) {
                break;
            }
            if (ctype_alpha($path[$offset])) {
                $command = $path[$offset++];
            }
            $upper = strtoupper($command ?? '');
            if (!isset($arities[$upper])) {
                throw new InvalidArgumentException($error);
            }
            if ($upper === 'Z') {
                $normalized .= $command;
                $command = null;
                continue;
            }
            $args = [];
            for ($index = 0; $index < $arities[$upper]; ++$index) {
                $offset += strspn($path, ' ,', $offset);
                // SVG arc flags can be adjacent to one another and to the following coordinate.
                $pattern = $upper === 'A' && in_array($index, [3, 4], true)
                    ? '/\G[01]/' : '/\G[-+]?(?:\d*\.\d+|\d+\.?\d*)(?:[eE][-+]?\d+)?/';
                if (preg_match($pattern, $path, $match, 0, $offset) !== 1) {
                    throw new InvalidArgumentException($error);
                }
                $value = (float) $match[0];
                if (!is_finite($value)) {
                    throw new InvalidArgumentException('The SVG path contains a non-finite coordinate.');
                }
                if (($upper === 'A' && $index < 2 && $value < 0)
                    || ($symbolCoordinateLimit !== null && abs($value) > $symbolCoordinateLimit)) {
                    throw new InvalidArgumentException($error);
                }
                if ($symbolCoordinateLimit !== null) {
                    $args[] = json_encode($value, JSON_THROW_ON_ERROR);
                }
                $offset += strlen($match[0]);
            }
            if ($symbolCoordinateLimit !== null) {
                $normalized .= ($first ? 'M' : $command) . implode(' ', $args) . ' ';
            } elseif ($first && $followsPath && $command === 'm') {
                // Every element starts at (0,0). Only its first pair becomes absolute;
                // subsequent implicit pairs after a relative moveto remain relative lines.
                $path[0] = 'M';
                $next = $offset + strspn($path, ' ,', $offset);
                if ($next < $length && !ctype_alpha($path[$next])) {
                    $path = substr($path, 0, $offset) . 'l' . substr($path, $next);
                    $length = strlen($path);
                }
            }
            $first = false;
            if ($upper === 'M') {
                $command = $command === 'M' ? 'L' : 'l';
            }
        }
        return $symbolCoordinateLimit === null ? $path : trim($normalized);
    }

    private static function NormalizeViewBox(string $viewBox): string
    {
        $parts = preg_split('/[\s,]+/', trim($viewBox)) ?: [];
        if (count($parts) !== 4) {
            throw new InvalidArgumentException('The SVG requires a finite viewBox with positive width and height.');
        }

        $numbers = [];
        foreach ($parts as $part) {
            if (preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/D', $part) !== 1) {
                throw new InvalidArgumentException('The SVG requires a finite viewBox with positive width and height.');
            }
            $number = (float) $part;
            if (!is_finite($number)) {
                throw new InvalidArgumentException('The SVG requires a finite viewBox with positive width and height.');
            }
            $numbers[] = $number;
        }
        if ($numbers[2] <= 0.0 || $numbers[3] <= 0.0) {
            throw new InvalidArgumentException('The SVG requires a finite viewBox with positive width and height.');
        }

        return implode(' ', array_map(self::FormatNumber(...), $numbers));
    }

    /** @return array{float, float} */
    private static function ReadPivot(string $attribute, string $viewBox): array
    {
        $parts = preg_split('/[\s,]+/', trim($attribute)) ?: [];
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('The optional ECharts pivot must contain two finite coordinates.');
        }
        $pivot = [];
        foreach ($parts as $part) {
            if (preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/D', $part) !== 1
                || !is_finite((float) $part)) {
                throw new InvalidArgumentException('The optional ECharts pivot must contain two finite coordinates.');
            }
            $pivot[] = (float) $part;
        }

        $viewBoxParts = array_map('floatval', explode(' ', $viewBox));
        [$minimumX, $minimumY, $width, $height] = $viewBoxParts;
        if ($pivot[0] < $minimumX || $pivot[0] > $minimumX + $width
            || $pivot[1] < $minimumY || $pivot[1] > $minimumY + $height) {
            throw new InvalidArgumentException('The optional ECharts pivot must be inside the SVG viewBox.');
        }

        return [$pivot[0], $pivot[1]];
    }

    private static function FormatNumber(float $number): string
    {
        $formatted = rtrim(rtrim(number_format($number, 8, '.', ''), '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
}
