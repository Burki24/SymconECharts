<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

require_once __DIR__ . '/../libs/EChartsSvgPath.php';

/** Reads selected icon data, never executes or redistributes the installed JS library. */
final class PictorialBarIcon
{
    private const MAX_LIBRARY_BYTES = 8388608;
    private const MAX_RECORD_BYTES = 131072;

    /** @return array{name:string,path:string,width:int,height:int} */
    public static function Resolve(string $name): array
    {
        self::ValidateName($name);
        $file = rtrim(IPS_GetKernelDirEx(), '/\\') . '/console/icons.js';
        $length = @filesize($file);
        if (!is_int($length) || $length <= 0 || $length > self::MAX_LIBRARY_BYTES) {
            throw new InvalidArgumentException('The installed Symcon icon library could not be read.');
        }
        static $cache = [];
        $key = $file . ':' . (string) @filemtime($file) . ':' . $length;
        if (!isset($cache[$key])) {
            $script = @file_get_contents($file, false, null, 0, self::MAX_LIBRARY_BYTES + 1);
            if (!is_string($script) || strlen($script) > self::MAX_LIBRARY_BYTES) {
                throw new InvalidArgumentException('The installed Symcon icon library could not be read.');
            }
            $cache = [$key => $script];
        }
        static $resolved = [];
        if (!isset($resolved[$key][$name])) {
            // Per-request cache only; replacing the library invalidates all entries.
            $resolved = [$key => [...($resolved[$key] ?? []), $name => self::ResolveFromScript($name, $cache[$key])]];
            if (count($resolved[$key]) > 32) {
                $resolved = [$key => [$name => $resolved[$key][$name]]];
            }
        }
        return $resolved[$key][$name];
    }

    /** Pure parser seam; accepts icon tuples, not executable JavaScript. @return array{name:string,path:string,width:int,height:int} */
    public static function ResolveFromScript(string $name, string $script): array
    {
        self::ValidateName($name);
        if (strlen($script) > self::MAX_LIBRARY_BYTES) {
            throw new InvalidArgumentException('The installed Symcon icon library could not be read.');
        }
        $packs = [];
        // The shipped packs register a literal data object under these prefixes.
        // Variable names may change on minification; no JavaScript is evaluated.
        foreach (['fal', 'fab'] as $prefix) {
            if (preg_match('/[A-Za-z_$][A-Za-z0-9_$]*\(["\']' . $prefix . '["\'],\s*([A-Za-z_$][A-Za-z0-9_$]*)\)/', $script, $registration, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }
            $before = substr($script, 0, $registration[0][1]);
            preg_match_all('/(?:\b(?:var|let|const)\s+|[,;])\s*' . preg_quote($registration[1][0], '/') . '\s*=\s*\{/', $before, $definitions, PREG_OFFSET_CAPTURE);
            if (($definitions[0] ?? []) !== []) {
                $start = $definitions[0][array_key_last($definitions[0])][1];
                $packs[] = substr($before, $start);
            }
        }
        // Symcon-specific kit: literal dictionary immediately precedes its prefix list.
        if (preg_match('/[A-Za-z_$][A-Za-z0-9_$]*\s*=\s*\[\s*null\s*,\s*["\']fak["\']/', $script, $kit, PREG_OFFSET_CAPTURE) === 1) {
            $before = substr($script, 0, $kit[0][1]);
            preg_match_all('/(?:\b(?:var|let|const)\s+|[,;])\s*[A-Za-z_$][A-Za-z0-9_$]*\s*=\s*\{/', $before, $definitions, PREG_OFFSET_CAPTURE);
            if (($definitions[0] ?? []) !== []) {
                $start = $definitions[0][array_key_last($definitions[0])][1];
                $packs[] = substr($before, $start);
            }
        }
        foreach ($packs as $pack) {
            $key = preg_quote($name, '/');
            if (preg_match('/(?:[,{])\s*(?:"' . $key . '"|' . $key . ')\s*:\s*(\[)/', $pack, $match, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }
            $tuple = self::ReadTuple($pack, $match[1][1]);
            if (count($tuple) !== 5 || !is_int($tuple[0]) || !is_int($tuple[1])
                || $tuple[0] < 1 || $tuple[0] > 16384 || $tuple[1] < 1 || $tuple[1] > 16384) {
                break;
            }
            $paths = is_string($tuple[4]) ? [$tuple[4]] : $tuple[4];
            if (!is_array($paths) || !array_is_list($paths) || count($paths) < 1 || count($paths) > 32
                || array_filter($paths, static fn (mixed $path): bool => !is_string($path)) !== []) {
                break;
            }
            $svg = '<svg viewBox="0 0 ' . $tuple[0] . ' ' . $tuple[1] . '">';
            foreach ($paths as $path) {
                $svg .= '<path d="' . htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"/>';
            }
            try {
                $parsed = EChartsSvgPath::Import($svg . '</svg>');
            } catch (InvalidArgumentException) {
                break;
            }
            if (strlen($parsed['path']) > 16384) {
                break;
            }
            return ['name' => $name, 'path' => $parsed['path'], 'width' => $tuple[0], 'height' => $tuple[1]];
        }
        throw new InvalidArgumentException('The selected Symcon icon is unavailable or unsupported.');
    }

    private static function ValidateName(string $name): void
    {
        if (preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/D', $name) !== 1) {
            throw new InvalidArgumentException('Select a Symcon icon from the icon picker.');
        }
    }

    /** @return array<mixed> */
    private static function ReadTuple(string $script, int $start): array
    {
        $quoted = false;
        $escaped = false;
        $depth = 0;
        $end = min(strlen($script), $start + self::MAX_RECORD_BYTES);
        for ($index = $start; $index < $end; ++$index) {
            $char = $script[$index];
            if ($quoted) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $quoted = false;
                }
            } elseif ($char === '"') {
                $quoted = true;
            } elseif ($char === '[') {
                if (++$depth > 8) {
                    break;
                }
            } elseif ($char === ']' && --$depth === 0) {
                $tuple = json_decode(substr($script, $start, $index - $start + 1), true);
                if (is_array($tuple) && array_is_list($tuple)) {
                    return $tuple;
                }
                break;
            }
        }
        throw new InvalidArgumentException('The selected Symcon icon is unavailable or unsupported.');
    }
}
