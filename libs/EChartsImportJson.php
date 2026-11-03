<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use JsonException;
use stdClass;

/** Bounded JSON boundary for inert migration packages; objects never become lists. */
final class EChartsImportJson
{
    public const MAXIMUM_BYTES = 16777216;
    private int $offset = 0;
    private int $nodes = 0;

    private function __construct(private string $json)
    {
    }

    public static function Decode(string $json): mixed
    {
        if (strlen($json) > self::MAXIMUM_BYTES) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: package exceeds 16 MiB.');
        }
        if (str_starts_with($json, "\xEF\xBB\xBF")) {
            throw new InvalidArgumentException('PACKAGE_INVALID: UTF-8 without BOM is required.');
        }
        $parser = new self($json);
        $parser->Value(1);
        $parser->Whitespace();
        if ($parser->offset !== strlen($json)) {
            $parser->Invalid();
        }
        try {
            return json_decode($json, false, 33, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('PACKAGE_INVALID: invalid JSON or UTF-8.');
        }
    }

    public static function Canonical(mixed $value): string
    {
        $precision = ini_get('serialize_precision');
        try {
            ini_set('serialize_precision', '-1');
            return json_encode(self::Ordered($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR, 64);
        } finally {
            ini_set('serialize_precision', $precision);
        }
    }

    public static function Hash(mixed $value): string
    {
        return hash('sha256', self::Canonical($value));
    }

    public static function Pointer(string $key): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $key);
    }

    /** The SelectFile action supplies Base64; no upload is persisted in a property. */
    public static function Upload(string $data): string
    {
        if (strlen($data) > 22369624) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: upload exceeds the package limit.');
        }
        $bytes = base64_decode($data, true);
        if ($bytes === false || base64_encode($bytes) !== $data) {
            throw new InvalidArgumentException('PACKAGE_INVALID: the upload is not canonical Base64.');
        }
        return $bytes;
    }

    private static function Ordered(mixed $value): mixed
    {
        if ($value instanceof stdClass || (is_array($value) && !array_is_list($value))) {
            $fields = (array) $value;
            ksort($fields, SORT_STRING);
            $object = new stdClass();
            foreach ($fields as $key => $field) {
                $object->{(string) $key} = self::Ordered($field);
            }
            return $object;
        }
        if (is_array($value)) {
            return array_map(self::Ordered(...), $value);
        }
        if (is_float($value) && !is_finite($value)) {
            throw new InvalidArgumentException('PACKAGE_INVALID: numbers must be finite.');
        }
        if (is_int($value) && abs($value) > 9007199254740991) {
            throw new InvalidArgumentException('PACKAGE_INVALID: integer exceeds the lossless JSON range.');
        }
        return $value;
    }

    private function Value(int $depth): void
    {
        $this->Whitespace();
        $char = $this->json[$this->offset] ?? '';
        if (($depth > 32 && ($char === '{' || $char === '[')) || ++$this->nodes > 100000) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: package nesting or structural complexity exceeds the preview budget.');
        }
        if ($char === '') {
            $this->Invalid();
        }
        if ($char === '{' || $char === '[') {
            ++$this->offset;
            $this->Whitespace();
            $end = $char === '{' ? '}' : ']';
            if (($this->json[$this->offset] ?? '') === $end) {
                ++$this->offset;
                return;
            }
            $keys = [];
            while (true) {
                if ($char === '{') {
                    $this->Whitespace();
                    $key = $this->StringToken(true);
                    if (isset($keys['key:' . $key])) {
                        throw new InvalidArgumentException('PACKAGE_INVALID: duplicate JSON object key.');
                    }
                    $keys['key:' . $key] = true;
                    $this->Whitespace();
                    if (($this->json[$this->offset++] ?? '') !== ':') {
                        $this->Invalid();
                    }
                }
                $this->Value($depth + 1);
                $this->Whitespace();
                $next = $this->json[$this->offset++] ?? '';
                if ($next === $end) {
                    return;
                }
                if ($next !== ',') {
                    $this->Invalid();
                }
            }
        }
        if ($char === '"') {
            $this->StringToken(false);
            return;
        }
        foreach (['true', 'false', 'null'] as $literal) {
            if (substr_compare($this->json, $literal, $this->offset, strlen($literal)) === 0) {
                $this->offset += strlen($literal);
                return;
            }
        }
        if (preg_match('/\G-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/', $this->json, $match, 0, $this->offset) !== 1) {
            $this->Invalid();
        }
        $number = $match[0];
        if (!is_finite((float) $number) || ((float) $number === 0.0 && preg_match('/[1-9]/', explode('e', strtolower($number))[0]) === 1)
            || (!str_contains(strtolower($number), 'e') && !str_contains($number, '.') && abs((float) $number) > 9007199254740991)) {
            throw new InvalidArgumentException('PACKAGE_INVALID: number exceeds the supported JSON range.');
        }
        $this->offset += strlen($number);
    }

    private function StringToken(bool $decode): string
    {
        $start = $this->offset;
        if (($this->json[$this->offset++] ?? '') !== '"') {
            $this->Invalid();
        }
        $length = strlen($this->json);
        while ($this->offset < $length) {
            $char = $this->json[$this->offset++];
            if ($char === '"') {
                if (!$decode) {
                    return '';
                }
                try {
                    return json_decode(substr($this->json, $start, $this->offset - $start), true, 2, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    $this->Invalid();
                }
            }
            if (ord($char) < 32) {
                $this->Invalid();
            }
            if ($char === '\\') {
                $escape = $this->json[$this->offset++] ?? '';
                if ($escape === 'u') {
                    if (preg_match('/\G[0-9a-fA-F]{4}/', $this->json, $match, 0, $this->offset) !== 1) {
                        $this->Invalid();
                    }
                    $this->offset += 4;
                } elseif (!str_contains('"\\/bfnrt', $escape) || $escape === '') {
                    $this->Invalid();
                }
            }
        }
        $this->Invalid();
    }

    private function Whitespace(): void
    {
        $this->offset += strspn($this->json, " \t\r\n", $this->offset);
    }

    private function Invalid(): never
    {
        throw new InvalidArgumentException('PACKAGE_INVALID: invalid JSON structure at byte ' . $this->offset . '.');
    }
}
