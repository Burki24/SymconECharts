<?php

declare(strict_types=1);

namespace eCharts;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

/** Additional static SVG features used exclusively in wind compass images. */
final class EChartsSvgCompassProfile
{
    // The copper engraving contains 556 elements, including its individual hatch strokes.
    public const MAX_ELEMENTS = 576;
    public const ELEMENTS = ['title', 'desc', 'text', 'clipPath', 'filter', 'feDropShadow'];

    /** Return false for attributes handled by the common SVG image profile. */
    public static function AcceptAttribute(DOMElement $element, string $name, string $value): bool
    {
        $tag = $element->localName;
        if ($tag === 'filter' && in_array($name, ['x', 'y', 'width', 'height'], true)) {
            $dimension = $name === 'width' || $name === 'height';
            $valid = preg_match('/^-?\d+(?:\.\d+)?%$/D', $value) === 1
                && ($dimension ? (float) $value > 0 && (float) $value <= 200 : abs((float) $value) <= 50);
        } elseif ($tag === 'feDropShadow') {
            $valid = match ($name) {
                'dx', 'dy'      => is_numeric($value) && abs((float) $value) <= 32,
                'stdDeviation'  => is_numeric($value) && (float) $value >= 0 && (float) $value <= 8,
                'flood-opacity' => is_numeric($value) && (float) $value >= 0 && (float) $value <= 1,
                'flood-color'   => preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/iD', $value) === 1,
                default         => false
            };
        } elseif (in_array($name, ['filter', 'clip-path'], true)) {
            $valid = $tag === 'g' && preg_match('/^url\(#[A-Za-z_][A-Za-z0-9_.:-]{0,127}\)$/D', $value) === 1;
        } elseif ($name === 'role' || $name === 'aria-labelledby') {
            $valid = $tag === 'svg' && ($name === 'role' ? $value === 'img'
                : preg_match('/^[A-Za-z_][A-Za-z0-9_.: -]{0,255}$/D', $value) === 1);
        } elseif ($tag === 'text' || $tag === 'g') {
            $valid = match ($name) {
                'font-family'       => preg_match('/^[\pL][\pL\pN ,_-]{0,127}$/uD', $value) === 1,
                'font-size'         => is_numeric($value) && (float) $value > 0 && (float) $value <= 1024,
                'font-weight'       => preg_match('/^(?:normal|bold|[1-9]00)$/D', $value) === 1,
                'font-style'        => in_array($value, ['normal', 'italic', 'oblique'], true),
                'text-anchor'       => in_array($value, ['start', 'middle', 'end'], true),
                'dominant-baseline' => in_array($value, ['auto', 'central', 'middle', 'alphabetic'], true),
                'letter-spacing'    => is_numeric($value) && abs((float) $value) <= 128,
                'dy', 'dx'          => preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:em|px)?$/D', $value) === 1,
                default             => null
            };
            if ($valid === null) {
                return false;
            }
        } else {
            return false;
        }
        if (!$valid) {
            throw new InvalidArgumentException('The compass SVG contains an unsupported attribute value.');
        }
        return true;
    }

    /** Allow only geometric local clips and one drop-shadow pass per top-level group. */
    public static function ValidateEffects(DOMDocument $document): void
    {
        $ids = [];
        $references = [];
        $shadows = 0;
        foreach ($document->getElementsByTagName('*') as $element) {
            $id = $element->getAttribute('id');
            if ($id !== '') {
                if (isset($ids[$id])) {
                    throw new InvalidArgumentException('Compass SVG identifiers must be unique.');
                }
                $ids[$id] = $element->localName;
            }
            $parent = $element->parentNode;
            $parentName = $parent instanceof DOMElement ? $parent->localName : '';
            if (($element->localName === 'defs' && $parentName !== 'svg')
                || (in_array($element->localName, ['filter', 'clipPath'], true) && $parentName !== 'defs')
                || ($parentName === 'clipPath' && !in_array($element->localName, ['circle', 'ellipse', 'rect', 'path', 'polygon'], true))
                || ($element->localName === 'filter' && $element->childElementCount !== 1)
                || (($element->localName === 'feDropShadow') !== ($parentName === 'filter'))
                || (in_array($parentName, ['title', 'desc', 'text', 'feDropShadow', 'path', 'rect', 'circle', 'ellipse', 'line', 'polygon', 'polyline'], true))) {
                throw new InvalidArgumentException('The compass SVG contains an unsupported drawing structure.');
            }
            if ($element->hasAttribute('filter') && ($parentName !== 'svg' || ++$shadows > 4)) {
                throw new InvalidArgumentException('Compass SVG drop shadows are limited to four top-level groups.');
            }
            foreach (['clip-path' => 'clipPath', 'filter' => 'filter'] as $attribute => $target) {
                if ($element->hasAttribute($attribute)) {
                    $references[] = [substr(trim($element->getAttribute($attribute)), 5, -1), $target];
                }
            }
        }
        foreach ($references as [$id, $target]) {
            if (($ids[$id] ?? '') !== $target) {
                throw new InvalidArgumentException('The compass SVG contains an unresolved local effect.');
            }
        }
    }
}
