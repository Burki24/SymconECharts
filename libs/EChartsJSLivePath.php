<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

require_once __DIR__ . '/EChartsSvgPath.php';

/** Additional import-family geometry budget and grammar; does not change existing PictorialBar SVG contracts. */
final class EChartsJSLivePath
{
    public static function Validate(string $path): void
    {
        EChartsSvgPath::Import('<svg viewBox="0 0 100 100"><path d="' . htmlspecialchars($path, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"/></svg>');
        $pattern = '/[MmZzLlHhVvCcSsQqTtAa]|[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?/';
        preg_match_all($pattern, $path, $matches);
        if (preg_match('/^[,\s]*$/D', preg_replace($pattern, '', $path)) !== 1
            || preg_match('/,\s*,|[MmZzLlHhVvCcSsQqTtAa]\s*,|,\s*[MmZzLlHhVvCcSsQqTtAa]/', $path) === 1) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: invalid SVG path separators.');
        }
        $arity = ['M' => 2, 'L' => 2, 'H' => 1, 'V' => 1, 'C' => 6, 'S' => 4, 'Q' => 4, 'T' => 2, 'A' => 7, 'Z' => 0];
        $command = null;
        $values = [];
        $segments = 0;
        $numbers = 0;
        $flush = static function () use (&$command, &$values, &$segments, $arity): void
        {
            if ($command === null) {
                return;
            }
            $count = $arity[strtoupper($command)];
            if (($count > 0 && ($values === [] || count($values) % $count !== 0)) || ($count === 0 && $values !== [])) {
                throw new InvalidArgumentException('FORMAT_UNSUPPORTED: incomplete SVG path command.');
            }
            $segments += $count === 0 ? 1 : intdiv(count($values), $count);
            if (strtoupper($command) === 'A') {
                foreach (array_chunk($values, 7) as $arc) {
                    if ((float) $arc[0] < 0 || (float) $arc[1] < 0 || !in_array($arc[3], ['0', '1'], true) || !in_array($arc[4], ['0', '1'], true)) {
                        throw new InvalidArgumentException('FORMAT_UNSUPPORTED: invalid SVG arc radius or flag.');
                    }
                }
            }
            $values = [];
        };
        foreach ($matches[0] as $token) {
            if (strlen($token) === 1 && isset($arity[strtoupper($token)])) {
                $flush();
                $command = $token;
            } else {
                if ($command === null || strtoupper($command) === 'Z' || abs((float) $token) > 1000000) {
                    throw new InvalidArgumentException('FORMAT_UNSUPPORTED: invalid or excessive SVG path coordinate.');
                }
                $values[] = $token;
                ++$numbers;
            }
        }
        $flush();
        if ($segments > 2048 || $numbers > 8192) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: SVG path exceeds 2048 segments or 8192 coordinates.');
        }
    }
}
