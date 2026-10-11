<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;
use JsonException;
use RuntimeException;

/** Shared, read-only configuration-form wiring for the four Bar previews. */
trait EChartsBarPreviewForm
{
    /** Updates only the form images; draft values are never persisted. */
    public function UpdateBarPreviewFromForm(string $Configuration): void
    {
        try {
            $values = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $values = [];
        }
        $values = array_replace($this->BarPreviewStoredValues(), is_array($values) ? $values : []);
        $this->UpdateFormField('BarPreview', 'image', SVGPreviewHelper::dataUri($this->BarPreviewSvg($values, false)));
        $this->UpdateFormField(
            'IPSViewBarPreview',
            'image',
            SVGPreviewHelper::dataUri($this->BarPreviewSvg($values, true))
        );
    }

    /** @param array<string,mixed> $values */
    abstract protected function BarPreviewSvg(array $values, bool $ipsView): string;
    /** @param array<string,mixed> $form @param list<string> $fields @return array<string,mixed> */
    private function WithBarPreviewForm(array $form, string $prefix, array $fields): array
    {
        $found = ['Tile designer' => false, 'IPSView design' => false];
        foreach ($form['elements'] as &$element) {
            $caption = $element['caption'] ?? null;
            if (!isset($found[$caption]) || ($element['type'] ?? null) !== 'ExpansionPanel') {
                continue;
            }
            $found[$caption] = true;
            $element['items'][] = [
                'type'    => 'Image',
                'name'    => $caption === 'Tile designer' ? 'BarPreview' : 'IPSViewBarPreview',
                'caption' => $caption === 'Tile designer' ? 'Tile live preview' : 'IPSView live preview',
                'width'   => '100%',
                'center'  => true
            ];
        }
        unset($element);
        if (in_array(false, $found, true)) {
            throw new RuntimeException('A Bar preview designer section is missing.');
        }
        $form['elements'] = $this->AttachBarPreviewActions($form['elements'], $prefix, $fields);
        $initial = $this->BarPreviewStoredValues();
        $form = SVGPreviewHelper::withImage($form, 'BarPreview', $this->BarPreviewSvg($initial, false));

        return SVGPreviewHelper::withImage($form, 'IPSViewBarPreview', $this->BarPreviewSvg($initial, true));
    }

    /** @param list<array<string,mixed>> $items @param list<string> $fields @return list<array<string,mixed>> */
    private function AttachBarPreviewActions(array $items, string $prefix, array $fields): array
    {
        $action = self::BarPreviewAction($prefix, $fields);
        foreach ($items as &$item) {
            $name = $item['name'] ?? null;
            if ($name === 'Sources' && ($item['type'] ?? null) === 'List') {
                foreach (['onAdd', 'onEdit', 'onDelete', 'onChangeOrder'] as $event) {
                    $previous = trim((string) ($item[$event] ?? ''));
                    $item[$event] = $previous === '' ? $action : $previous . ' ' . $action;
                }
            } elseif (is_string($name) && in_array($name, $fields, true) && $name !== 'Sources') {
                $previous = trim((string) ($item['onChange'] ?? ''));
                $item['onChange'] = $previous === '' ? $action : $previous . ' ' . $action;
            }
            if (is_array($item['items'] ?? null)) {
                $item['items'] = $this->AttachBarPreviewActions($item['items'], $prefix, $fields);
            }
        }
        unset($item);

        return $items;
    }

    /** @param list<string> $fields */
    private static function BarPreviewAction(string $prefix, array $fields): string
    {
        $pairs = array_map(
            static fn (string $name): string => var_export($name, true)
                . ' => ' . ($name === 'Sources' ? '$barPreviewSources' : '$' . $name),
            $fields
        );

        return '$barPreviewSources = []; foreach ($Sources as $barPreviewSource) {'
            . ' $barPreviewSources[] = $barPreviewSource; } '
            . $prefix . '_UpdateBarPreviewFromForm($id, json_encode([' . implode(', ', $pairs) . ']));';
    }

    /** @param array<string,mixed> $values */
    private function BarPreviewValue(array $values, string $name, string $type, bool $ipsView = false): mixed
    {
        $property = $ipsView && !($values['IPSViewUseTileDesign']
            ?? $this->ReadPropertyBoolean('IPSViewUseTileDesign')) ? 'IPSView' . $name : $name;
        if (array_key_exists($property, $values)) {
            return match ($type) {
                'boolean' => (bool) $values[$property],
                'integer' => (int) $values[$property],
                'float'   => (float) $values[$property],
                default   => (string) $values[$property]
            };
        }

        return match ($type) {
            'boolean' => $this->ReadPropertyBoolean($property),
            'integer' => $this->ReadPropertyInteger($property),
            'float'   => $this->ReadPropertyFloat($property),
            default   => $this->ReadPropertyString($property)
        };
    }

    /** @return array<string,mixed> */
    private function BarPreviewStoredValues(): array
    {
        return [
            'IPSViewUseTileDesign'             => $this->ReadPropertyBoolean('IPSViewUseTileDesign'),
            'IPSViewAdaptToBackground'         => $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            'IPSViewBackgroundColor'           => $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            'IPSViewBackgroundOpacityPercent'  => $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        ];
    }
}
