<?php

declare(strict_types=1);

namespace SymconECharts;

/** Shared property and form contract for ECharts entrance and update animations. */
trait EChartsAnimationDesign
{
    public function UpdateAnimationForm(bool $IPSView, bool $AnimationEnabled): void
    {
        $prefix = $IPSView ? 'IPSView' : '';
        $this->UpdateFormField($prefix . 'AnimationDuration', 'visible', $AnimationEnabled);
        $this->UpdateFormField($prefix . 'AnimationDurationUpdate', 'visible', $AnimationEnabled);
    }
    private function RegisterAnimationProperties(string $prefix, bool $enabled, int $initial, int $update): void
    {
        $this->RegisterPropertyBoolean($prefix . 'AnimationEnabled', $enabled);
        $this->RegisterPropertyInteger($prefix . 'AnimationDuration', $initial);
        $this->RegisterPropertyInteger($prefix . 'AnimationDurationUpdate', $update);
    }

    private function AnimationIsValid(string $prefix = ''): bool
    {
        foreach (['AnimationDuration', 'AnimationDurationUpdate'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < 0 || $value > 3000) {
                return false;
            }
        }

        return true;
    }

    /** @return array{animationEnabled:bool,animationDuration:int,animationDurationUpdate:int} */
    private function ReadAnimationStyle(string $prefix = ''): array
    {
        return [
            'animationEnabled'        => $this->ReadPropertyBoolean($prefix . 'AnimationEnabled'),
            'animationDuration'       => $this->ReadPropertyInteger($prefix . 'AnimationDuration'),
            'animationDurationUpdate' => $this->ReadPropertyInteger($prefix . 'AnimationDurationUpdate')
        ];
    }

    /** @param array<string,mixed> $values @return array{animationEnabled:bool,animationDuration:int,animationDurationUpdate:int} */
    private function AnimationStyleFromFormValues(array $values, string $prefix = ''): array
    {
        $saved = $this->ReadAnimationStyle($prefix);
        return [
            'animationEnabled'        => (bool) ($values[$prefix . 'AnimationEnabled'] ?? $saved['animationEnabled']),
            'animationDuration'       => (int) ($values[$prefix . 'AnimationDuration'] ?? $saved['animationDuration']),
            'animationDurationUpdate' => (int) ($values[$prefix . 'AnimationDurationUpdate'] ?? $saved['animationDurationUpdate'])
        ];
    }

    /** @param list<array<string,mixed>> $elements @return list<array<string,mixed>> */
    private function WithAnimationFormVisibility(array $elements): array
    {
        foreach (['', 'IPSView'] as $prefix) {
            $elements = $this->SetFormFieldVisibility(
                $elements,
                [$prefix . 'AnimationDuration', $prefix . 'AnimationDurationUpdate'],
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
