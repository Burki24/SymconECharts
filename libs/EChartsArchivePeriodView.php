<?php

declare(strict_types=1);

namespace eCharts;

use InvalidArgumentException;

require_once __DIR__ . '/EChartsPeriodPreview.php';

/** Existing rolling presets for archived distributions, intervals and state histories. */
trait EChartsArchivePeriodView
{
    private const ARCHIVE_PERIOD_LABELS = [
        '24h'    => 'Last 24 hours', '7d' => 'Last 7 days', '30d' => 'Last 30 days',
        'custom' => 'Custom', 'absolute' => 'Fixed historical period'
    ];

    private function WithArchivePeriodPreview(string $svg, array $values, bool $ipsView): string
    {
        return EChartsPeriodPreview::Apply(
            $svg,
            (bool) $this->BarPreviewValue($values, 'EnablePeriodSelection', 'boolean', $ipsView),
            $this->Translate(self::ARCHIVE_PERIOD_LABELS[$values['Range'] ?? $this->ReadPropertyString('Range')] ?? 'Time range'),
            EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView))
        );
    }

    private function ArchivePeriodChoices(bool $ipsView): array
    {
        if (!$this->EChartsPeriodSelectionEnabled($ipsView)) {
            return [];
        }
        $configured = $this->ReadPropertyString('Range');
        $choices = ['' => $this->Translate(self::ARCHIVE_PERIOD_LABELS[$configured] ?? 'Time range')];
        foreach (['24h', '7d', '30d'] as $key) {
            if ($configured !== $key && $this->SupportsArchivePeriod($key)) {
                $choices[$key] = $this->Translate(self::ARCHIVE_PERIOD_LABELS[$key]);
            }
        }
        return $choices;
    }

    private function SupportsArchivePeriod(string $period): bool
    {
        return true;
    }

    private function BuildArchivePeriodView(array $payload): array
    {
        $ipsView = ($payload['Output'] ?? 'ipsview') !== 'tile';
        $period = $payload['Period'] ?? '';
        if ($period !== '' && (!array_key_exists($period, $this->ArchivePeriodChoices($ipsView)) || ($payload['WindowOffset'] ?? 0) !== 0)) {
            throw new InvalidArgumentException('Invalid archive view period.');
        }
        return $this->BuildVisualizationState($ipsView, $period);
    }
}
