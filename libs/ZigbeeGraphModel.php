<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Projects a saved Zigbee2MQTT RAW network map without starting a scan. */
final class ZigbeeGraphModel
{
    /** @param array<string,mixed> $topology @return array<string,mixed> */
    public static function Build(
        array $topology,
        int $minimumLQI,
        int $maximumLinks,
        string $linkSelection = 'strongest',
        int $maximumLQI = 255
    ): array {
        if (!self::IsValidFilter($minimumLQI, $maximumLinks, $linkSelection, $maximumLQI)) {
            throw new InvalidArgumentException('The Zigbee connection filter is invalid.');
        }
        $rawNodes = $topology['nodes'] ?? null;
        $rawLinks = $topology['links'] ?? null;
        if (!is_array($rawNodes) || !array_is_list($rawNodes) || count($rawNodes) < 1 || count($rawNodes) > 500
            || !is_array($rawLinks) || !array_is_list($rawLinks) || count($rawLinks) > 10000) {
            throw new InvalidArgumentException('No valid saved Zigbee network map is available.');
        }

        $nodes = [];
        $identities = [];
        foreach ($rawNodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $address = self::Address($node['ieeeAddr'] ?? null);
            if ($address === '' || isset($identities[$address])) {
                continue;
            }
            $id = 'device-' . hash('sha256', $address);
            $identities[$address] = $id;
            $name = trim((string) ($node['friendlyName'] ?? ''));
            $name = mb_strimwidth($name !== '' ? $name : $address, 0, 120, '…', 'UTF-8');
            $kind = (string) ($node['type'] ?? '');
            $nodes[] = [
                'id'             => $id,
                'name'           => $name,
                'kind'           => in_array($kind, ['Coordinator', 'Router', 'EndDevice'], true) ? $kind : 'Unknown',
                'networkAddress' => is_int($node['networkAddress'] ?? null) ? $node['networkAddress'] : null
            ];
        }
        if ($nodes === []) {
            throw new InvalidArgumentException('No valid saved Zigbee network map is available.');
        }

        $pairs = [];
        foreach ($rawLinks as $link) {
            if (!is_array($link)) {
                continue;
            }
            $source = self::Address($link['source']['ieeeAddr'] ?? $link['sourceIeeeAddr'] ?? null);
            $target = self::Address($link['target']['ieeeAddr'] ?? $link['targetIeeeAddr'] ?? null);
            if ($source === '' || $target === '' || $source === $target
                || !isset($identities[$source], $identities[$target])) {
                continue;
            }
            $lqi = $link['lqi'] ?? $link['linkquality'] ?? null;
            if ((!is_int($lqi) && !is_float($lqi) && !is_numeric($lqi))
                || !is_finite((float) $lqi) || (float) $lqi < 0 || (float) $lqi > 255) {
                continue;
            }
            $lqi = (int) $lqi;
            $key = $source . '>' . $target;
            if (isset($pairs[$key]) && $pairs[$key]['lqi'] >= $lqi) {
                continue;
            }
            $pairs[$key] = ['source' => $identities[$source], 'target' => $identities[$target], 'lqi' => $lqi];
        }
        // A range must never revive a weaker duplicate of an excluded directed pair.
        $maximumLQI = $linkSelection === 'range' ? $maximumLQI : 255;
        $links = array_values(array_filter($pairs, static fn (array $link): bool => $link['lqi'] >= $minimumLQI && $link['lqi'] <= $maximumLQI));
        usort($links, static fn (array $a, array $b): int => ($linkSelection === 'weakest'
            ? $a['lqi'] <=> $b['lqi'] : $b['lqi'] <=> $a['lqi'])
            ?: strcmp($a['source'] . $a['target'], $b['source'] . $b['target']));
        $filteredCount = count($links);
        $links = array_slice($links, 0, $maximumLinks);

        $capturedAt = $topology['captured_at'] ?? 0;
        return [
            'nodes'          => $nodes,
            'links'          => $links,
            'capturedAt'     => is_int($capturedAt) && $capturedAt > 0 ? $capturedAt : 0,
            'routesIncluded' => ($topology['routes_included'] ?? false) === true,
            'totalLinks'     => count($rawLinks),
            'filteredLinks'  => $filteredCount,
            'linkSelection'  => $linkSelection,
            'minimumLQI'     => $minimumLQI,
            'maximumLQI'     => $maximumLQI
        ];
    }

    public static function IsValidFilter(int $minimumLQI, int $maximumLinks, string $linkSelection, int $maximumLQI): bool
    {
        return $minimumLQI >= 0 && $minimumLQI <= 255 && $maximumLinks >= 1 && $maximumLinks <= 2000
            && in_array($linkSelection, ['strongest', 'weakest', 'range'], true)
            && ($linkSelection !== 'range' || ($maximumLQI >= $minimumLQI && $maximumLQI <= 255));
    }

    private static function Address(mixed $address): string
    {
        if (!is_string($address) || strlen($address) > 32) {
            return '';
        }
        $address = strtolower(trim($address));
        return preg_match('/^0x[0-9a-f]{16}$/', $address) === 1 ? $address : '';
    }
}
