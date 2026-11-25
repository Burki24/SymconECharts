<?php

declare(strict_types=1);

namespace eCharts;

/** Read-only archive window overrides for historical Cartesian series. */
trait EChartsCartesianPeriodSelection
{
    private function CartesianPeriodChoices(bool $ipsView): array
    {
        if (!$this->EChartsPeriodSelectionEnabled($ipsView) || (method_exists($this, 'HasJSLiveImport') && $this->HasJSLiveImport())
            || $this->ReadPropertyString('DataMode') === 'realtime') {
            return [];
        }
        $presets = array_map(fn (string $caption): string => $this->Translate($caption), EChartsArchiveQuery::PERIOD_PRESETS);
        $configured = $this->EffectiveRange($ipsView);
        $label = $presets[$configured] ?? $this->Translate('Custom');
        unset($presets[$configured]);
        return ['' => $label] + $presets;
    }

    private function CartesianPeriodPreviewLabel(array $values, bool $ipsView): string
    {
        $ownDesign = $ipsView && !($values['IPSViewUseTileDesign'] ?? $this->ReadPropertyBoolean('IPSViewUseTileDesign'));
        $field = $ownDesign ? 'IPSViewEnablePeriodSelection' : 'EnablePeriodSelection';
        if (!($values[$field] ?? $this->ReadPropertyBoolean($field))
            || ($values['DataMode'] ?? $this->ReadPropertyString('DataMode')) === 'realtime') {
            return '';
        }
        $ownTime = $ipsView && !($values['IPSViewUseTileTimeSettings'] ?? $this->ReadPropertyBoolean('IPSViewUseTileTimeSettings'));
        $field = $ownTime ? 'IPSViewRange' : 'Range';
        $range = $values[$field] ?? $this->ReadPropertyString($field);
        return $this->Translate(EChartsArchiveQuery::PERIOD_PRESETS[$range] ?? 'Custom');
    }

    private function CartesianTransportState(array $payload): array
    {
        $ipsView = ($payload['Output'] ?? 'ipsview') !== 'tile';
        $period = $payload['Period'] ?? '';
        if ($period !== '' && ($this->CartesianPeriodChoices($ipsView) === [] || !isset(EChartsArchiveQuery::PERIOD_PRESETS[$period]))) {
            throw new \InvalidArgumentException('The selected archive period is unavailable.');
        }
        return $this->BuildVisualizationState($ipsView, $period);
    }
}
