<?php

declare(strict_types=1);

namespace SymconECharts;

/** Shared property and form contract for ECharts entrance and update animations. */
trait EChartsAnimationDesign
{
    private const ANIMATION_EASING_OPTIONS = [
        'cubicInOut'      => 'ECharts default (cubicInOut)',
        'linear'          => 'Linear',
        'quadraticInOut'  => 'Gentle (quadraticInOut)',
        'cubicOut'        => 'Ease out (cubicOut)',
        'sinusoidalInOut' => 'Sine (sinusoidalInOut)',
        'bounceOut'       => 'Bounce',
        'elasticOut'      => 'Elastic'
    ];

    public function UpdateAnimationForm(bool $IPSView, bool $AnimationEnabled): void
    {
        $prefix = $IPSView ? 'IPSView' : '';
        foreach ([
            'AnimationDuration', 'AnimationDurationUpdate',
            'AnimationEasing', 'AnimationEasingUpdate',
            'AnimationDelay', 'AnimationDelayUpdate'
        ] as $name) {
            $this->UpdateFormField($prefix . $name, 'visible', $AnimationEnabled);
        }
    }

    /** @param list<array<string,mixed>> $elements @return list<array<string,mixed>> */
    private function WithAnimationFormControls(array $elements): array
    {
        $result = [];
        foreach ($elements as $element) {
            if (isset($element['items']) && is_array($element['items'])) {
                $element['items'] = $this->WithAnimationFormControls($element['items']);
            }
            $result[] = $element;
            if (($element['type'] ?? '') !== 'RowLayout') {
                continue;
            }
            $names = array_column($element['items'] ?? [], 'name');
            foreach (['', 'IPSView'] as $prefix) {
                if (in_array($prefix . 'AnimationDurationUpdate', $names, true)) {
                    array_push($result, ...$this->AnimationControlRows($prefix));
                    break;
                }
            }
        }

        return $result;
    }

    /** @return list<array<string,mixed>> */
    private function AnimationControlRows(string $prefix): array
    {
        $options = [];
        foreach (self::ANIMATION_EASING_OPTIONS as $value => $caption) {
            $options[] = ['caption' => $caption, 'value' => $value];
        }

        return [
            [
                'type'  => 'RowLayout',
                'items' => [
                    ['type' => 'Select', 'name' => $prefix . 'AnimationEasing', 'caption' => 'Initial easing', 'width' => '225px', 'options' => $options],
                    ['type' => 'Select', 'name' => $prefix . 'AnimationEasingUpdate', 'caption' => 'Update easing', 'width' => '225px', 'options' => $options]
                ]
            ],
            [
                'type'  => 'RowLayout',
                'items' => [
                    ['type' => 'NumberSpinner', 'name' => $prefix . 'AnimationDelay', 'caption' => 'Initial delay', 'minimum' => 0, 'maximum' => 3000, 'suffix' => 'ms'],
                    ['type' => 'NumberSpinner', 'name' => $prefix . 'AnimationDelayUpdate', 'caption' => 'Update delay', 'minimum' => 0, 'maximum' => 3000, 'suffix' => 'ms']
                ]
            ]
        ];
    }

    private function RegisterAnimationProperties(string $prefix, bool $enabled, int $initial, int $update): void
    {
        $this->RegisterPropertyBoolean($prefix . 'AnimationEnabled', $enabled);
        $this->RegisterPropertyInteger($prefix . 'AnimationDuration', $initial);
        $this->RegisterPropertyInteger($prefix . 'AnimationDurationUpdate', $update);
        $this->RegisterPropertyString($prefix . 'AnimationEasing', 'cubicInOut');
        $this->RegisterPropertyString($prefix . 'AnimationEasingUpdate', 'cubicInOut');
        $this->RegisterPropertyInteger($prefix . 'AnimationDelay', 0);
        $this->RegisterPropertyInteger($prefix . 'AnimationDelayUpdate', 0);
    }

    private function AnimationIsValid(string $prefix = ''): bool
    {
        foreach (['AnimationDuration', 'AnimationDurationUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < 0 || $value > 3000) {
                return false;
            }
        }
        foreach (['AnimationEasing', 'AnimationEasingUpdate'] as $name) {
            if (!array_key_exists($this->ReadPropertyString($prefix . $name), self::ANIMATION_EASING_OPTIONS)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, bool|int|string> */
    private function ReadAnimationStyle(string $prefix = ''): array
    {
        return [
            'animationEnabled'         => $this->ReadPropertyBoolean($prefix . 'AnimationEnabled'),
            'animationDuration'        => $this->ReadPropertyInteger($prefix . 'AnimationDuration'),
            'animationDurationUpdate'  => $this->ReadPropertyInteger($prefix . 'AnimationDurationUpdate'),
            'animationEasing'          => $this->ReadPropertyString($prefix . 'AnimationEasing'),
            'animationEasingUpdate'    => $this->ReadPropertyString($prefix . 'AnimationEasingUpdate'),
            'animationDelay'           => $this->ReadPropertyInteger($prefix . 'AnimationDelay'),
            'animationDelayUpdate'     => $this->ReadPropertyInteger($prefix . 'AnimationDelayUpdate')
        ];
    }

    /** @param array<string,mixed> $values @return array<string, bool|int|string> */
    private function AnimationStyleFromFormValues(array $values, string $prefix = ''): array
    {
        $saved = $this->ReadAnimationStyle($prefix);
        return [
            'animationEnabled'         => (bool) ($values[$prefix . 'AnimationEnabled'] ?? $saved['animationEnabled']),
            'animationDuration'        => (int) ($values[$prefix . 'AnimationDuration'] ?? $saved['animationDuration']),
            'animationDurationUpdate'  => (int) ($values[$prefix . 'AnimationDurationUpdate'] ?? $saved['animationDurationUpdate']),
            'animationEasing'          => (string) ($values[$prefix . 'AnimationEasing'] ?? $saved['animationEasing']),
            'animationEasingUpdate'    => (string) ($values[$prefix . 'AnimationEasingUpdate'] ?? $saved['animationEasingUpdate']),
            'animationDelay'           => (int) ($values[$prefix . 'AnimationDelay'] ?? $saved['animationDelay']),
            'animationDelayUpdate'     => (int) ($values[$prefix . 'AnimationDelayUpdate'] ?? $saved['animationDelayUpdate'])
        ];
    }

    /** @param list<array<string,mixed>> $elements @return list<array<string,mixed>> */
    private function WithAnimationFormVisibility(array $elements): array
    {
        foreach (['', 'IPSView'] as $prefix) {
            $elements = $this->SetFormFieldVisibility(
                $elements,
                [
                    $prefix . 'AnimationDuration', $prefix . 'AnimationDurationUpdate',
                    $prefix . 'AnimationEasing', $prefix . 'AnimationEasingUpdate',
                    $prefix . 'AnimationDelay', $prefix . 'AnimationDelayUpdate'
                ],
                $this->ReadPropertyBoolean($prefix . 'AnimationEnabled')
            );
        }

        return $elements;
    }

    /** @param list<array<string,mixed>> $elements @return list<array<string,mixed>> */
    private function WithAnimationFormCallbacks(array $elements, string $modulePrefix): array
    {
        foreach ($elements as &$element) {
            $name = $element['name'] ?? null;
            if ($name === 'AnimationEnabled' || $name === 'IPSViewAnimationEnabled') {
                $ipsView = $name === 'IPSViewAnimationEnabled' ? 'true' : 'false';
                $callback = $modulePrefix . '_UpdateAnimationForm($id, ' . $ipsView . ', $' . $name . ');';
                $existing = (string) ($element['onChange'] ?? '');
                if (!str_contains($existing, '_UpdateAnimationForm(')) {
                    $element['onChange'] = $callback . $existing;
                }
            }
            if (isset($element['items']) && is_array($element['items'])) {
                $element['items'] = $this->WithAnimationFormCallbacks($element['items'], $modulePrefix);
            }
        }
        unset($element);

        return $elements;
    }
}
