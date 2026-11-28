<?php

declare(strict_types=1);

namespace eCharts;

use RuntimeException;

require_once __DIR__ . '/EChartsDesignerColorGuide.php';

/** Shared form state for the chart-wide Tile/IPSView design inheritance contract. */
trait EChartsIPSViewDesignForm
{
    private const IPSVIEW_COPY_BUTTON = 'CopyTileDesignToIPSViewButton';

    private const DESIGN_CONTROL_TYPES = [
        'Button', 'CheckBox', 'HorizontalSlider', 'List', 'NumberSpinner',
        'Select', 'SelectColor', 'SelectFile', 'SelectIcon', 'SelectVariable', 'ValidationTextBox'
    ];

    /** Script entry point: stage the saved Tile values in the open form. */
    public function CopyTileDesignToIPSView(): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function (): void
        {
            $form = json_decode($this->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
            $configuration = json_decode(IPS_GetConfiguration($this->InstanceID), true, 512, JSON_THROW_ON_ERROR);
            $values = [];
            $controls = self::IndependentDesignControls($form['elements']);
            foreach (self::DesignCopyFields($form['elements'], $controls) as $name => $type) {
                $value = $configuration[$name] ?? IPS_GetProperty($this->InstanceID, $name);
                $values[$name] = $type === 'List' ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : $value;
            }
            $this->StageDesignCopy($values, $controls);
            $configuration['IPSViewUseTileDesign'] = false;
            foreach ($values as $name => $value) {
                $configuration['IPSView' . $name] = $value;
            }
            foreach (['UpdateIPSViewGaugePreviewFromForm', 'UpdateGaugePreviewFromForm',
                'UpdateTimeSeriesPreviewFromForm', 'UpdateBarPreviewFromForm'] as $preview) {
                if (method_exists($this, $preview)) {
                    $this->{$preview}(json_encode($configuration, JSON_THROW_ON_ERROR));
                    break;
                }
            }
        }, true);
    }

    /** The button supplies Tile values from the current form session. */
    public function CopyTileDesignDraftToIPSView(array $Values): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($Values): void
        {
            $form = json_decode($this->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
            $controls = self::IndependentDesignControls($form['elements']);
            $fields = self::DesignCopyFields($form['elements'], $controls);
            if (array_diff_key($Values, $fields) !== [] || array_diff_key($fields, $Values) !== []) {
                throw new \InvalidArgumentException('Invalid Tile design field selection.');
            }
            foreach ($fields as $name => $type) {
                if ($type === 'List' ? !is_array($Values[$name]) : !is_scalar($Values[$name])) {
                    throw new \InvalidArgumentException('Invalid Tile design field value.');
                }
            }
            $this->StageDesignCopy($Values, $controls);
        }, true);
    }

    /** Immediately follows an unsaved change to the design inheritance switch. */
    public function UpdateIPSViewDesignAvailability(bool $UseTileDesign, array $FieldNames = []): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
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
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    protected function UpdateIndependentDesignVisibilityAfterCopy(array $values): void
    {
        // Families with conditional design fields reuse their own visibility handlers.
    }

    private function StageDesignCopy(array $values, array $controls): void
    {
        $this->UpdateFormField('IPSViewUseTileDesign', 'value', false);
        $this->UpdateIPSViewDesignAvailability(false, array_keys($controls));
        foreach ($values as $name => $value) {
            if (is_array($value)) {
                $this->UpdateFormField('IPSView' . $name, 'values', json_encode($value, JSON_THROW_ON_ERROR));
            } else {
                $this->UpdateFormField('IPSView' . $name, 'value', $value);
            }
        }
        if (array_key_exists('AnimationEnabled', $values) && method_exists($this, 'UpdateAnimationForm')) {
            $this->UpdateAnimationForm(true, $values['AnimationEnabled']);
        }
        $this->UpdateIndependentDesignVisibilityAfterCopy($values);
    }

    /** @return array<string, array> Named input controls, excluding columns of lists. */
    private static function DesignInputs(array $items): array
    {
        $inputs = [];
        foreach ($items as $item) {
            if (isset($item['name']) && in_array($item['type'], self::DESIGN_CONTROL_TYPES, true)) {
                $inputs[$item['name']] = $item;
            }
            if (isset($item['items']) && $item['type'] !== 'List') {
                $inputs += self::DesignInputs($item['items']);
            }
        }
        return $inputs;
    }

    /** Only independent designer controls following the copy button have a Tile counterpart. */
    private static function IndependentDesignControls(array $elements): array
    {
        foreach ($elements as $panel) {
            if (($panel['caption'] ?? '') !== 'IPSView design') {
                continue;
            }
            foreach ($panel['items'] as $index => $item) {
                if (($item['name'] ?? '') === self::IPSVIEW_COPY_BUTTON) {
                    return self::DesignInputs(array_slice($panel['items'], $index));
                }
            }
        }
        throw new RuntimeException('The IPSView designer is missing.');
    }

    private static function DesignCopyFields(array $elements, array $controls): array
    {
        $inputs = self::DesignInputs($elements);
        $fields = [];
        foreach ($controls as $target => $control) {
            $source = substr($target, strlen('IPSView'));
            if ($control['type'] !== 'Button' && str_starts_with($target, 'IPSView') && isset($inputs[$source])) {
                $fields[$source] = $control['type'];
            }
        }
        return $fields;
    }

    /** Reuse the final family-specific preview action with the same, still unsaved form values. */
    private static function WireDesignCopy(array &$elements): void
    {
        $fields = self::DesignCopyFields($elements, self::IndependentDesignControls($elements));
        foreach ($elements as &$panel) {
            if (($panel['caption'] ?? '') !== 'IPSView design') {
                continue;
            }
            $inputs = self::DesignInputs($panel['items']);
            $preview = $inputs['IPSViewChartBackgroundColor']['onChange'] ?? '';
            if ($fields === [] || $preview === '') {
                throw new RuntimeException('The IPSView copy action requires design fields and a preview.');
            }
            foreach ($panel['items'] as &$button) {
                if (($button['name'] ?? '') !== self::IPSVIEW_COPY_BUTTON) {
                    continue;
                }
                $script = '$designCopy = []; $IPSViewUseTileDesign = false; ';
                foreach ($fields as $name => $type) {
                    if ($type === 'List') {
                        $script .= '$IPSView' . $name . ' = []; foreach ($' . $name . ' as $designRow) { $IPSView' . $name . '[] = $designRow; } ';
                    } else {
                        $script .= '$IPSView' . $name . ' = $' . $name . '; ';
                    }
                    $script .= '$designCopy[' . var_export($name, true) . '] = $IPSView' . $name . '; ';
                }
                $button['onClick'] = $script . $button['onClick'] . ' ' . $preview;
                return;
            }
        }
        throw new RuntimeException('The IPSView copy button is missing.');
    }

    private function EncodeChartDesignerForm(string $moduleDirectory, array $form): string
    {
        self::WireDesignCopy($form['elements']);
        $title = $this->TranslateHelperTextFromDirectory(__DIR__, 'DesignerColorGuide', 'title', 'Schematic color key');
        $help = $this->TranslateHelperTextFromDirectory(
            __DIR__,
            'DesignerColorGuide',
            'help',
            'Orange marks the area controlled by each field. The examples include optional design modes and use fixed sample colors.'
        );

        return $this->EncodeConfigurationForm(EChartsDesignerColorGuide::WithGuides($form, $moduleDirectory, $this->Translate(...), $title, $help));
    }

    private function RegisterChartBackgroundProperties(): void
    {
        $this->RegisterPropertyInteger('ChartBackgroundColor', -1);
        $this->RegisterPropertyInteger('IPSViewChartBackgroundColor', -1);
    }

    private function ChartBackgroundColorsAreValid(): bool
    {
        $tile = $this->ReadPropertyInteger('ChartBackgroundColor');
        $ipsView = $this->ReadPropertyInteger('IPSViewChartBackgroundColor');

        return $tile >= -1 && $tile <= 0xFFFFFF
            && ($this->ReadPropertyBoolean('IPSViewUseTileDesign') || ($ipsView >= -1 && $ipsView <= 0xFFFFFF));
    }

    /** @param array<string, mixed> $draft */
    private function EffectiveChartBackgroundColor(bool $ipsView, array $draft = []): int
    {
        $independent = $ipsView && !($draft['IPSViewUseTileDesign'] ?? $this->ReadPropertyBoolean('IPSViewUseTileDesign'));
        $name = $independent ? 'IPSViewChartBackgroundColor' : 'ChartBackgroundColor';
        $color = (int) ($draft[$name] ?? $this->ReadPropertyInteger($name));

        return $color >= 0 && $color <= 0xFFFFFF ? $color : -1;
    }

    private static function ChartBackgroundField(string $prefix, string $onChange): array
    {
        $field = [
            'type'               => 'SelectColor',
            'name'               => $prefix . 'ChartBackgroundColor',
            'caption'            => 'Chart background',
            'allowTransparent'   => true,
            'transparentCaption' => 'Automatic (theme)'
        ];
        if ($onChange !== '') {
            $field['onChange'] = $onChange;
        }

        return $field;
    }

    /** Places the color alongside the theme in both flat and already grouped designers. */
    private static function InsertChartBackground(array &$items, string $prefix, string $onChange): bool
    {
        $theme = $prefix . 'EChartsTheme';
        foreach ($items as $index => &$item) {
            if (($item['name'] ?? null) === $theme || (($item['type'] ?? null) === 'RowLayout'
                && in_array($theme, array_column($item['items'], 'name'), true))) {
                array_splice($items, $index + 1, 0, [self::ChartBackgroundField($prefix, $onChange)]);

                return true;
            }
            if (isset($item['items']) && self::InsertChartBackground($item['items'], $prefix, $onChange)) {
                return true;
            }
        }

        return false;
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
    private function WithIPSViewDesignFormState(
        array $form,
        string $modulePrefix,
        string $tileBackgroundAction = '',
        string $ipsViewBackgroundAction = ''
    ): array {
        if (!isset($form['elements']) || !is_array($form['elements'])) {
            throw new RuntimeException('The IPSView design form section is missing.');
        }

        foreach ($form['elements'] as &$element) {
            if (($element['type'] ?? null) === 'ExpansionPanel' && ($element['caption'] ?? null) === 'Tile designer') {
                self::InsertChartBackground($element['items'], '', $tileBackgroundAction);
            }
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

            $inheritanceHelp = 'Inherited mode follows every Tile design change. Disable it for an independent IPSView appearance.';
            $nextItem = $element['items'][$checkboxIndex + 1] ?? null;
            if (($nextItem['type'] ?? null) !== 'Label' || ($nextItem['caption'] ?? null) !== $inheritanceHelp) {
                array_splice($element['items'], $checkboxIndex + 1, 0, [[
                    'type'    => 'Label',
                    'caption' => $inheritanceHelp
                ]]);
                if ($buttonIndex > $checkboxIndex) {
                    $buttonIndex++;
                }
                if ($independentIndex > $checkboxIndex) {
                    $independentIndex++;
                }
            }

            $copyButton = $element['items'][$buttonIndex];
            $copyButton['name'] = self::IPSVIEW_COPY_BUTTON;
            $copyButton['onClick'] = $modulePrefix . '_CopyTileDesignDraftToIPSView($id, $designCopy);';
            $designerItems = $element['items'][$independentIndex]['items'];
            self::InsertChartBackground($designerItems, 'IPSView', $ipsViewBackgroundAction);
            array_unshift($designerItems, $copyButton);

            $enabled = !$this->ReadPropertyBoolean('IPSViewUseTileDesign');
            $fieldNames = [];
            $unnamedButton = 0;
            $this->SetIndependentDesignerAvailability(
                $designerItems,
                $enabled,
                $fieldNames,
                $unnamedButton
            );
            $flatItems = [];
            foreach ($element['items'] as $index => $item) {
                if ($index === $buttonIndex || $index === $independentIndex) {
                    continue;
                }
                $flatItems[] = $item;
                if (($item['type'] ?? '') === 'RowLayout'
                    && in_array('IPSViewAdaptToBackground', array_column($item['items'], 'name'), true)) {
                    $flatItems[] = ['type' => 'Label', 'caption' => $this->TranslateHelperTextFromDirectory(
                        __DIR__,
                        'DesignerColorGuide',
                        'widgetBackground',
                        'For a solid chart background, turn off IPSView background adaptation. When enabled, the widget uses its background color and opacity.'
                    )];
                }
            }
            array_push($flatItems, ...$designerItems);
            $element['items'] = $flatItems;

            $action = $modulePrefix . '_UpdateIPSViewDesignAvailability($id, $IPSViewUseTileDesign, '
                . var_export(array_values(array_unique($fieldNames)), true) . ');';
            foreach ($element['items'] as &$item) {
                if (($item['name'] ?? null) !== 'IPSViewUseTileDesign') {
                    continue;
                }
                $existingAction = trim((string) ($item['onChange'] ?? ''));
                $item['onChange'] = $action . ($existingAction === '' ? '' : ' ' . $existingAction);
                break;
            }
            unset($item);

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

    /** @param array<string, mixed> $configuration */
    private function RenderChartPage(bool $ipsView, array $configuration): string
    {
        if (!($ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'))) {
            $style = $ipsView ? 'ipsViewStyle' : 'visualizationTheme';
            $color = $this->EffectiveChartBackgroundColor($ipsView);
            if ($color !== -1) {
                $configuration[$style] .= ' :root { --symc-chart-page-background:' . EChartsAsset::ColorToHex($color) . '; }';
            }
            // Include uncovered space around the canvas, navigation and empty states.
            $configuration[$style] .= ' body > main { background-color:var(--symc-chart-page-background, transparent); }';
        }

        return $this->RenderVisualizationHTMLPage($ipsView, $configuration);
    }

    /** @param array<string, mixed> $chart @return array<string, mixed> */
    private function WithChartBackground(array $chart, bool $ipsView): array
    {
        $color = $this->EffectiveChartBackgroundColor($ipsView);
        if ($color !== -1) {
            $chart['chartBackgroundColor'] = EChartsAsset::ColorToHex($color);
        }

        return $chart;
    }
}
