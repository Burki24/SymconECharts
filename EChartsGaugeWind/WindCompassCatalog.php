<?php

declare(strict_types=1);

namespace eCharts;

use InvalidArgumentException;

/** Stable identifiers and display order for the bundled compass rose artwork. */
final class WindCompassCatalog
{
    private const ROSES = [
        '01_holz_hell'        => 'Color – Maple',
        '02_holz_dunkel'      => 'Color – Walnut',
        '03_holz_kirschbaum'  => 'Color – Cherry wood',
        '04_silber'           => 'Color – Silver',
        '05_gold'             => 'Color – Gold',
        '06_rot'              => 'Color – Soft red',
        '07_gruen'            => 'Color – Sage green',
        '08_blau'             => 'Color – Soft blue',
        '09_bronze'           => 'Color – Bronze',
        '10_anthrazit'        => 'Color – Anthracite',
        '11_tuerkis'          => 'Color – Turquoise',
        '12_violett'          => 'Color – Lavender',
        '01_pergament'        => 'Historic – Parchment',
        '02_messing'          => 'Historic – Brass',
        '03_bronze_patina'    => 'Historic – Bronze with patina',
        '04_seekarte_blau'    => 'Historic – Blue nautical chart',
        '05_heraldik_rotgold' => 'Historic – Red and gold heraldry',
        '06_antiksilber'      => 'Historic – Antique silver',
        '07_elfenbein_oliv'   => 'Historic – Ivory and olive',
        '08_schwarz_gold'     => 'Historic – Black and gold',
        '01_portolan'         => 'Classic – Portolan chart',
        '02_renaissance'      => 'Classic – Renaissance',
        '03_kupferstich'      => 'Classic – Copper engraving',
        '04_admiral'          => 'Classic – Admiral’s compass',
        '05_seefahrer'        => 'Classic – Sailor’s rose',
        '06_nordstern'        => 'Classic – North Star',
        '07_goldgravur'       => 'Classic – Gold engraving',
        '08_silbergravur'     => 'Classic – Silver engraving',
        '09_bronzerelief'     => 'Classic – Bronze relief',
        '10_eiche'            => 'Classic – Carved oak',
        '11_nussbaum'         => 'Classic – Carved walnut',
        '12_kirschbaum'       => 'Classic – Carved cherry wood',
        '01_lilienrose'       => 'Transparent – Fleur-de-lis rose (dark)',
        '02_windstern'        => 'Transparent – Wind star (light)',
        '03_nautischer_ring'  => 'Transparent – Nautical ring (dark)',
        '04_kompasssiegel'    => 'Transparent – Compass seal (light)',
        '05_graduierte_rose'  => 'Transparent – Graduated rose (dark)'
    ];

    /** @return list<array{caption: string, value: string}> */
    public static function Options(): array
    {
        $options = [];
        foreach (self::ROSES as $id => $caption) {
            $options[] = ['caption' => $caption, 'value' => $id];
        }
        return $options;
    }

    public static function Read(string $id): string
    {
        if (!isset(self::ROSES[$id])) {
            throw new InvalidArgumentException('Unknown compass rose preset.');
        }
        $contents = @file_get_contents(__DIR__ . '/roses/' . $id . '.svg');
        if ($contents === false || $contents === '') {
            throw new InvalidArgumentException('The selected compass rose preset could not be loaded.');
        }
        return $contents;
    }
}
