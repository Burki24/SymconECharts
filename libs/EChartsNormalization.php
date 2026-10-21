<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

require_once __DIR__ . '/EChartsValueRange.php';

/** Explicit source ranges project geometry only; original display metadata stays separate. */
final class EChartsNormalization
{
    /** @return array{0:float,1:float} */
    public static function Range(array $source): array
    {
        $minimum = array_key_exists('Minimum', $source) ? $source['Minimum'] : 0;
        $maximum = array_key_exists('Maximum', $source) ? $source['Maximum'] : 100;
        if (!EChartsValueRange::IsValid($minimum, $maximum)) {
            throw new InvalidArgumentException('Normalization requires finite source limits with minimum below maximum.');
        }
        return [(float) $minimum, (float) $maximum];
    }

    public static function Value(float $value, array $source): float
    {
        [$minimum, $maximum] = self::Range($source);
        if (!is_finite($value)) {
            throw new InvalidArgumentException('Normalization requires finite source values.');
        }
        // Divide before multiplying to preserve large but finite valid ranges.
        return (max($minimum, min($maximum, $value)) - $minimum) / ($maximum - $minimum) * 100;
    }

    /** @return array{originalValue:float,minimum:float,maximum:float} */
    public static function Metadata(float $value, array $source): array
    {
        [$minimum, $maximum] = self::Range($source);
        if (!is_finite($value)) {
            throw new InvalidArgumentException('Normalization requires finite source values.');
        }
        return ['originalValue' => $value, 'minimum' => $minimum, 'maximum' => $maximum];
    }
}
