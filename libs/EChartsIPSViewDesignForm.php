<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;

/** Shared form state for the chart-wide Tile/IPSView design inheritance contract. */
trait EChartsIPSViewDesignForm
{
    private const IPSVIEW_COPY_BUTTON = 'CopyTileDesignToIPSViewButton';

    /** Immediately follows an unsaved change to the design inheritance switch. */
    public function UpdateIPSViewDesignAvailability(bool $UseTileDesign): void
    {
        $this->UpdateFormField(self::IPSVIEW_COPY_BUTTON, 'enabled', !$UseTileDesign);
    }

    /** @param array<string, mixed> $form @return array<string, mixed> */
    private function WithIPSViewDesignFormState(array $form, string $modulePrefix): array
    {
        if (!isset($form['elements']) || !is_array($form['elements'])) {
            throw new RuntimeException('The IPSView design form section is missing.');
        }

        foreach ($form['elements'] as &$element) {
            if (($element['type'] ?? null) !== 'ExpansionPanel'
                || ($element['caption'] ?? null) !== 'IPSView design') {
                continue;
            }

            $checkboxFound = false;
            $buttonFound = false;
            foreach ($element['items'] as &$item) {
                if (($item['type'] ?? null) === 'CheckBox'
                    && ($item['name'] ?? null) === 'IPSViewUseTileDesign') {
                    $action = $modulePrefix . '_UpdateIPSViewDesignAvailability($id, $IPSViewUseTileDesign);';
                    $existingAction = trim((string) ($item['onChange'] ?? ''));
                    $item['onChange'] = $action . ($existingAction === '' ? '' : ' ' . $existingAction);
                    $checkboxFound = true;
                }

                if (($item['type'] ?? null) === 'Button'
                    && ($item['caption'] ?? null) === 'Copy Tile design to IPSView and edit independently') {
                    $item['name'] = self::IPSVIEW_COPY_BUTTON;
                    $item['enabled'] = !$this->ReadPropertyBoolean('IPSViewUseTileDesign');
                    $buttonFound = true;
                }
            }
            unset($item);

            if (!$checkboxFound || !$buttonFound) {
                throw new RuntimeException('The IPSView design inheritance controls are incomplete.');
            }

            return $form;
        }
        unset($element);

        throw new RuntimeException('The IPSView design form section is missing.');
    }
}
