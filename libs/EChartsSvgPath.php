<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/**
 * Imports a path-only SVG as a bounded ECharts path symbol.
 *
 * The original SVG markup is never forwarded to a visualization. Only the
 * validated path data and viewBox required by the trusted preview are kept.
 */
final class EChartsSvgPath
{
    private const MAX_FILE_BYTES = 131072;
    private const MAX_INPUT_BYTES = 196608;
    private const MAX_PATH_BYTES = 65535;
    private const MAX_PATHS = 32;
    private const ALLOWED_ELEMENTS = ['svg', 'g', 'path'];

    /** @return array{path: string, viewBox: string, pivotX?: float, pivotY?: float} */
    public static function Import(string $fileData): array
    {
        $svg = self::DecodeFileData($fileData);
        if (strlen($svg) > self::MAX_FILE_BYTES) {
            throw new InvalidArgumentException('The SVG file exceeds the 128 KiB import limit.');
        }
        if (preg_match('/<\s*svg\b/i', $svg) !== 1) {
            throw new InvalidArgumentException('The selected file is not an SVG document.');
        }
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg) === 1) {
            throw new InvalidArgumentException('SVG document types and entities are not supported.');
        }
        if (preg_match('/\btransform\s*=/i', $svg) === 1) {
            throw new InvalidArgumentException('SVG transformations are not supported; convert them to paths before importing.');
        }
        if (preg_match('/\bon[a-z]+\s*=|\b(?:href|xlink:href)\s*=/i', $svg) === 1) {
            throw new InvalidArgumentException('SVG event handlers and external references are not supported.');
        }

        preg_match_all('/<\s*\/?\s*([A-Za-z][A-Za-z0-9:_-]*)\b/', $svg, $elementMatches);
        foreach ($elementMatches[1] ?? [] as $element) {
            if (!in_array(strtolower((string) $element), self::ALLOWED_ELEMENTS, true)) {
                throw new InvalidArgumentException('The SVG may contain only svg, g and path elements.');
            }
        }

        if (preg_match('/<\s*svg\b([^>]*)>/is', $svg, $svgMatch) !== 1
            || preg_match('/\bviewBox\s*=\s*(["\'])(.*?)\1/is', $svgMatch[1], $viewBoxMatch) !== 1) {
            throw new InvalidArgumentException('The SVG requires a finite viewBox with positive width and height.');
        }
        $viewBox = self::NormalizeViewBox($viewBoxMatch[2]);
        $pivot = self::ReadPivot($svgMatch[1], $viewBox);

        preg_match_all('/<\s*path\b([^>]*)\/?>/is', $svg, $pathMatches);
        if (count($pathMatches[1] ?? []) === 0 || count($pathMatches[1]) > self::MAX_PATHS) {
            throw new InvalidArgumentException('The SVG must contain between one and 32 path elements.');
        }

        $paths = [];
        foreach ($pathMatches[1] as $attributes) {
            if (preg_match('/\bd\s*=\s*(["\'])(.*?)\1/is', $attributes, $pathMatch) !== 1) {
                throw new InvalidArgumentException('Every SVG path requires path data.');
            }
            $path = trim((string) (preg_replace('/\s+/', ' ', $pathMatch[2]) ?? ''));
            if ($path === ''
                || preg_match('/^[MmZzLlHhVvCcSsQqTtAa0-9eE+.,\-\s]+$/D', $path) !== 1
                || preg_match('/^[Mm]/', $path) !== 1) {
                throw new InvalidArgumentException('The SVG contains unsupported or invalid path data.');
            }
            preg_match_all('/[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?/', $path, $numberMatches);
            foreach ($numberMatches[0] ?? [] as $number) {
                if (!is_finite((float) $number)) {
                    throw new InvalidArgumentException('The SVG path contains a non-finite coordinate.');
                }
            }
            $paths[] = $path;
        }

        $path = implode(' ', $paths);
        if (strlen($path) > self::MAX_PATH_BYTES) {
            throw new InvalidArgumentException('The combined SVG path exceeds the 64 KiB import limit.');
        }

        $result = ['path' => $path, 'viewBox' => $viewBox];
        if ($pivot !== null) {
            $result['pivotX'] = $pivot[0];
            $result['pivotY'] = $pivot[1];
        }

        return $result;
    }

    private static function DecodeFileData(string $fileData): string
    {
        $fileData = trim($fileData);
        if ($fileData === '' || strlen($fileData) > self::MAX_INPUT_BYTES) {
            throw new InvalidArgumentException('The selected SVG file is empty or too large.');
        }

        if (preg_match('/^data:image\/svg\+xml(?:;[^,]*)?;base64,(.*)$/is', $fileData, $matches) === 1) {
            $fileData = $matches[1];
        }
        if (preg_match('/^(?:<\?xml\b[^?]*\?>\s*)?<svg\b/is', ltrim($fileData)) === 1) {
            return $fileData;
        }

        $compact = preg_replace('/\s+/', '', $fileData) ?? '';
        $decoded = base64_decode($compact, true);
        if ($decoded === false || preg_match('/^(?:<\?xml\b[^?]*\?>\s*)?<svg\b/is', ltrim($decoded)) !== 1) {
            throw new InvalidArgumentException('The selected file does not contain readable SVG data.');
        }

        return $decoded;
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

    /** @return array{float, float}|null */
    private static function ReadPivot(string $svgAttributes, string $viewBox): ?array
    {
        if (preg_match('/\bdata-echarts-pivot\s*=/i', $svgAttributes) !== 1) {
            return null;
        }
        if (preg_match('/\bdata-echarts-pivot\s*=\s*(["\'])(.*?)\1/is', $svgAttributes, $pivotMatch) !== 1) {
            throw new InvalidArgumentException('The optional ECharts pivot must contain two finite coordinates.');
        }

        $parts = preg_split('/[\s,]+/', trim($pivotMatch[2])) ?: [];
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
