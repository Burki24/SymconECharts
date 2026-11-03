<?php

declare(strict_types=1);

namespace SymconECharts;

/**
 * Color samples reconstructed from figures 1 and 5 of Jochen Schiewe,
 * "Farbschemata für Temperaturkarten" (KN – Info und Praxis 3/2023).
 * These are historical examples, not current or official provider palettes.
 * The paper contains no machine-readable color table; the RGB samples are
 * approximate. Figure 1 locates the sampled palettes on a Celsius axis;
 * except for NOAA's explicitly stated -50..50 °C span, the bounds below are
 * rounded readings of that figure, not official provider thresholds.
 * Figure 5 gives Tempera colors without temperature bounds, so its range is
 * an application-defined -20..40 °C (5 °C / 10 °C per class).
 *
 * @see https://kartdok.staatsbibliothek-berlin.de/servlets/MCRFileNodeServlet/kartdok_derivate_00000704/Schiewe_Farbschemata%20f%C3%BCr%20Temperaturkarten_Was%20liegt%20zwischen%20Blau%20und%20Rot_KN-IP_2023.pdf
 */
final class HeatmapColorScales
{
    public const CUSTOM = 'custom';

    /** @var array<string,list<string>> */
    private const COLORS = [
        'tempera12' => ['#2A388D', '#1577B9', '#48AED3', '#98D4E6', '#D9EEF5', '#D4E8D1', '#E7EAA1', '#FFDE90', '#F6A866', '#ED6B4A', '#E6352B', '#C21815'],
        'tempera6'  => ['#333D8D', '#39A9D1', '#D8EDF2', '#E5E89C', '#F6A662', '#E7362D'],
        'noaa2023'  => ['#7A8484', '#C2C3C3', '#ECF7FE', '#FBE0ED', '#C198C5', '#6F3A8F', '#223F92', '#008CCE', '#00ADE4', '#9DD7F1', '#009641', '#78B927', '#B0CB01', '#F1E400', '#FDC100', '#F29019', '#E73C2F', '#E11213', '#BD4693', '#F082B2'],
        'saws2023'  => ['#0092CC', '#32A7B8', '#87B7A3', '#BAD48A', '#E7E776', '#FFE750', '#F8AA39', '#F07F3B', '#EA5334', '#E6282B'],
        'dwd2023'   => ['#004E9A', '#0057A3', '#84AAB8', '#66AFE2', '#52C2E5', '#00A287', '#7EBB50', '#DDE8B9', '#FDF6C6', '#F2E400', '#E2DE00', '#FFE9B3', '#F19980', '#E57F8D', '#E52826'],
        'ts2023'    => ['#00ACE9', '#009588', '#359535', '#F2911A', '#EC6028', '#E83633'],
        'faz2023'   => ['#114482', '#007169', '#57A73C', '#8ABE22', '#BDCF00', '#E6E000', '#FBCF00', '#F6A300', '#F18517', '#EC6426', '#E94A2B'],
        'usa2023'   => ['#F6E0EE', '#D9C0DD', '#93B2D9', '#00ABE8', '#6AC7E4', '#6EB62F', '#FFF06C', '#FFCA00', '#F59C1D', '#EB5C2B', '#E6272F', '#D6141A'],
        'china2023' => ['#51266E', '#243265', '#0050A0', '#206CB4', '#00A4E4', '#00A8B5', '#4AC0E3', '#8FD1E4', '#FAF4B6', '#FDCF7D', '#F6A314', '#EF7E21', '#EA4D1D', '#C23716'],
        'abc2023'   => ['#9D2C87', '#C55C9F', '#EFB7D4', '#CBCCC5', '#5561AA', '#00B1E0', '#7DCBDB', '#009A3E', '#2AA836', '#B8DAB8', '#FCF7C8', '#F2E400', '#FDC95F', '#F9CFCD', '#F29499', '#E83C43', '#E30711', '#B46E32', '#B72F16']
    ];

    /** @var array<string,array{min:int,max:int}> */
    private const RANGES = [
        'tempera12' => ['min' => -20, 'max' => 40],
        'tempera6'  => ['min' => -20, 'max' => 40],
        'noaa2023'  => ['min' => -50, 'max' => 50],
        'saws2023'  => ['min' => 25, 'max' => 45],
        'dwd2023'   => ['min' => -5, 'max' => 25],
        'ts2023'    => ['min' => -5, 'max' => 25],
        'faz2023'   => ['min' => -15, 'max' => 40],
        'usa2023'   => ['min' => -12, 'max' => 43],
        'china2023' => ['min' => -32, 'max' => 18],
        'abc2023'   => ['min' => -10, 'max' => 47]
    ];

    public static function IsSupported(string $id): bool
    {
        return $id === self::CUSTOM || isset(self::COLORS[$id]);
    }

    /** @return list<string> */
    public static function Colors(string $id): array
    {
        return self::COLORS[$id] ?? [];
    }

    /** @return array{min:int,max:int}|null */
    public static function Range(string $id): ?array
    {
        return self::RANGES[$id] ?? null;
    }
}
