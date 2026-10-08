<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;

/** Shared form state for the chart-wide Tile/IPSView design inheritance contract. */
trait EChartsIPSViewDesignForm
{
    private const IPSVIEW_COPY_BUTTON = 'CopyTileDesignToIPSViewButton';

    private const DESIGN_CONTROL_TYPES = [
        'Button', 'CheckBox', 'HorizontalSlider', 'List', 'NumberSpinner',
        'Select', 'SelectColor', 'SelectFile', 'SelectVariable', 'ValidationTextBox'
    ];

    /** Immediately follows an unsaved change to the design inheritance switch. */
    public function UpdateIPSViewDesignAvailability(bool $UseTileDesign, array $FieldNames = []): void
    {
        $seen = [];
        foreach ($FieldNames === [] ? [self::IPSVIEW_COPY_BUTTON] : $FieldNames as $name) {
            if (!is_string($name) || preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name) !== 1) {
                throw new \InvalidArgumentException('Invalid IPSView design field name.');
            }
            if (isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            $this->UpdateFormField($name, 'enabled', !$UseTileDesign);
        }
    }

    /** @param list<array<string, mixed>> $elements @param list<array<string, mixed>> $timeItems @return list<array<string, mixed>> */
    private function WithIPSViewTimeSettingsByRange(array $elements, array $timeItems): array
    {
        foreach ($elements as $index => $element) {
            if (($element['type'] ?? null) !== 'RowLayout'
                || !in_array('Range', array_column($element['items'] ?? [], 'name'), true)) {
                continue;
            }

            $insertAfter = $index;
            $next = $elements[$index + 1] ?? null;
            if (is_array($next) && ($next['type'] ?? null) === 'RowLayout'
                && in_array('DataMode', array_column($next['items'] ?? [], 'name'), true)) {
                $insertAfter++;
            }
            array_splice($elements, $insertAfter + 1, 0, $timeItems);

            return $elements;
        }

        throw new RuntimeException('The Tile time range form section is missing.');
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

            $checkboxIndex = null;
            $buttonIndex = null;
            $independentIndex = null;
            foreach ($element['items'] as $index => $item) {
                if (($item['type'] ?? null) === 'CheckBox'
                    && ($item['name'] ?? null) === 'IPSViewUseTileDesign') {
                    $checkboxIndex = $index;
                }
                if (($item['type'] ?? null) === 'Button'
                    && ($item['caption'] ?? null) === 'Copy Tile design to IPSView and edit independently') {
                    $buttonIndex = $index;
                }
                if (($item['type'] ?? null) === 'ExpansionPanel'
                    && ($item['caption'] ?? null) === 'Independent IPSView designer') {
                    $independentIndex = $index;
                }
            }
            if ($checkboxIndex === null || $independentIndex === null || $buttonIndex === null) {
                throw new RuntimeException('The IPSView design inheritance controls are incomplete.');
            }

            $copyButton = $element['items'][$buttonIndex];
            $copyButton['name'] = self::IPSVIEW_COPY_BUTTON;
            array_splice($element['items'], $buttonIndex, 1);
            if ($buttonIndex < $independentIndex) {
                $independentIndex--;
            }
            array_unshift($element['items'][$independentIndex]['items'], $copyButton);

            $enabled = !$this->ReadPropertyBoolean('IPSViewUseTileDesign');
            $fieldNames = [];
            $unnamedButton = 0;
            $this->SetIndependentDesignerAvailability(
                $element['items'][$independentIndex]['items'],
                $enabled,
                $fieldNames,
                $unnamedButton
            );
            $action = $modulePrefix . '_UpdateIPSViewDesignAvailability($id, $IPSViewUseTileDesign, '
                . var_export(array_values(array_unique($fieldNames)), true) . ');';
            $existingAction = trim((string) ($element['items'][$checkboxIndex]['onChange'] ?? ''));
            $element['items'][$checkboxIndex]['onChange'] = $action
                . ($existingAction === '' ? '' : ' ' . $existingAction);

            return $form;
        }
        unset($element);

        throw new RuntimeException('The IPSView design form section is missing.');
    }

    /** @param list<array<string, mixed>> $items @param list<string> $fieldNames */
    private function SetIndependentDesignerAvailability(
        array &$items,
        bool $enabled,
        array &$fieldNames,
        int &$unnamedButton
    ): void {
        foreach ($items as &$item) {
            $type = $item['type'] ?? null;
            if (in_array($type, self::DESIGN_CONTROL_TYPES, true)) {
                if ($type === 'Button' && !isset($item['name'])) {
                    $unnamedButton++;
                    $item['name'] = 'IPSViewDesignAction' . $unnamedButton;
                }
                if (!is_string($item['name'] ?? null) || $item['name'] === '') {
                    throw new RuntimeException('An independent IPSView design control has no name.');
                }
                $item['enabled'] = $enabled;
                $fieldNames[] = $item['name'];
            }
            if ($type !== 'List' && isset($item['items']) && is_array($item['items'])) {
                $this->SetIndependentDesignerAvailability($item['items'], $enabled, $fieldNames, $unnamedButton);
            }
        }
        unset($item);
    }
}
