<?php

declare(strict_types=1);

namespace SymconECharts;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;

/**
 * Imports a bounded SVG document for use as an embedded visualization image.
 *
 * Scriptable content, external references, CSS blocks and unsupported markup
 * are rejected. The returned data URI contains only the validated document.
 */
final class EChartsSvgImage
{
    private const MAX_FILE_BYTES = 262144;
    private const MAX_INPUT_BYTES = 393216;
    private const MAX_ELEMENTS = 512;
    private const MAX_DEPTH = 32;
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    private const ALLOWED_ELEMENTS = [
        'svg',
        'g',
        'defs',
        'path',
        'rect',
        'circle',
        'ellipse',
        'line',
        'polyline',
        'polygon',
        'linearGradient',
        'radialGradient',
        'stop'
    ];
    private const ALLOWED_ATTRIBUTES = [
        'xmlns',
        'viewBox',
        'preserveAspectRatio',
        'width',
        'height',
        'id',
        'd',
        'x',
        'y',
        'x1',
        'y1',
        'x2',
        'y2',
        'cx',
        'cy',
        'r',
        'rx',
        'ry',
        'fx',
        'fy',
        'fr',
        'points',
        'offset',
        'fill',
        'fill-opacity',
        'fill-rule',
        'stroke',
        'stroke-opacity',
        'stroke-width',
        'stroke-linecap',
        'stroke-linejoin',
        'stroke-miterlimit',
        'stroke-dasharray',
        'stroke-dashoffset',
        'opacity',
        'stop-color',
        'stop-opacity',
        'clip-rule',
        'vector-effect',
        'transform',
        'gradientTransform',
        'gradientUnits',
        'spreadMethod',
        'style'
    ];
    private const ALLOWED_STYLE_PROPERTIES = [
        'fill',
        'fill-opacity',
        'fill-rule',
        'stroke',
        'stroke-opacity',
        'stroke-width',
        'stroke-linecap',
        'stroke-linejoin',
        'stroke-miterlimit',
        'stroke-dasharray',
        'stroke-dashoffset',
        'opacity',
        'stop-color',
        'stop-opacity',
        'clip-rule',
        'vector-effect'
    ];
    private const PAINT_ATTRIBUTES = ['fill', 'stroke', 'stop-color'];
    private const NUMERIC_ATTRIBUTES = [
        'width',
        'height',
        'x',
        'y',
        'x1',
        'y1',
        'x2',
        'y2',
        'cx',
        'cy',
        'r',
        'rx',
        'ry',
        'fx',
        'fy',
        'fr',
        'offset',
        'fill-opacity',
        'stroke-opacity',
        'stroke-width',
        'stroke-miterlimit',
        'stroke-dasharray',
        'stroke-dashoffset',
        'opacity',
        'stop-opacity'
    ];

    /** @return array{dataUri: string, viewBox: string, width: float, height: float} */
    public static function Import(string $fileData): array
    {
        $svg = self::DecodeFileData($fileData);
        if (strlen($svg) > self::MAX_FILE_BYTES) {
            throw new InvalidArgumentException('The SVG background exceeds the 256 KiB import limit.');
        }
        if (preg_match('/<\s*svg\b/i', $svg) !== 1) {
            throw new InvalidArgumentException('The selected file is not an SVG document.');
        }
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg) === 1) {
            throw new InvalidArgumentException('SVG document types and entities are not supported.');
        }
        if (!class_exists(DOMDocument::class)) {
            throw new InvalidArgumentException('SVG background import requires the PHP DOM extension.');
        }

        $previousErrors = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            $document->resolveExternals = false;
            $document->substituteEntities = false;
            if (!$document->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT)) {
                throw new InvalidArgumentException('The selected file does not contain readable SVG data.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }

        $root = $document->documentElement;
        if (!$root instanceof DOMElement
            || $root->localName !== 'svg'
            || !in_array($root->namespaceURI, [null, '', self::SVG_NAMESPACE], true)) {
            throw new InvalidArgumentException('The selected file is not an SVG document.');
        }
        $viewBox = self::NormalizeViewBox($root->getAttribute('viewBox'));
        $viewBoxParts = array_map('floatval', explode(' ', $viewBox));
        $root->setAttribute('xmlns', self::SVG_NAMESPACE);
        $root->setAttribute('viewBox', $viewBox);
        $root->removeAttribute('width');
        $root->removeAttribute('height');

        $elementCount = 0;
        self::ValidateElement($root, 0, $elementCount);
        $sanitized = $document->saveXML($root);
        if (!is_string($sanitized) || $sanitized === '') {
            throw new InvalidArgumentException('The selected file does not contain readable SVG data.');
        }

        return [
            'dataUri' => 'data:image/svg+xml;base64,' . base64_encode($sanitized),
            'viewBox' => $viewBox,
            'width'   => $viewBoxParts[2],
            'height'  => $viewBoxParts[3]
        ];
    }

    private static function DecodeFileData(string $fileData): string
    {
        $fileData = trim($fileData);
        if ($fileData === '' || strlen($fileData) > self::MAX_INPUT_BYTES) {
            throw new InvalidArgumentException('The selected SVG background is empty or too large.');
        }
        if (preg_match('/^data:image\/svg\+xml(?:;[^,]*)?;base64,(.*)$/is', $fileData, $matches) === 1) {
            $fileData = $matches[1];
        }
        if (preg_match('/^(?:<\?xml\b[^?]*\?>\s*)?<svg\b/is', ltrim($fileData)) === 1) {
            return $fileData;
        }

        $compact = preg_replace('/\s+/', '', $fileData) ?? '';
        $decoded = base64_decode($compact, true);
        if ($decoded === false || preg_match('/^(?:<\?xml\b[^?]*\?>\s*)?<svg\b/is', ltrim($decoded)) !== 1) {
            throw new InvalidArgumentException('The selected file does not contain readable SVG data.');
        }

        return $decoded;
    }

    private static function ValidateElement(DOMElement $element, int $depth, int &$elementCount): void
    {
        ++$elementCount;
        if ($depth > self::MAX_DEPTH || $elementCount > self::MAX_ELEMENTS) {
            throw new InvalidArgumentException('The SVG background is too complex.');
        }
        if (!in_array($element->localName, self::ALLOWED_ELEMENTS, true)
            || !in_array($element->namespaceURI, [null, '', self::SVG_NAMESPACE], true)
            || ($depth > 0 && $element->localName === 'svg')) {
            throw new InvalidArgumentException('The SVG background contains unsupported elements.');
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (!in_array($attribute->localName, self::ALLOWED_ATTRIBUTES, true)
                || ($attribute->localName !== 'xmlns'
                    && $attribute->namespaceURI !== null
                    && $attribute->namespaceURI !== '')) {
                throw new InvalidArgumentException('The SVG background contains unsupported attributes.');
            }
            $value = trim($attribute->value);
            if ($attribute->localName === 'style') {
                $element->setAttribute('style', self::SanitizeStyle($value));
            } else {
                self::ValidateAttribute($attribute->localName, $value);
            }
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                self::ValidateElement($child, $depth + 1, $elementCount);
            } elseif ($child->nodeType === XML_TEXT_NODE && trim($child->textContent) === '') {
                continue;
            } elseif (in_array($child->nodeType, [XML_COMMENT_NODE], true)) {
                $element->removeChild($child);
            } else {
                throw new InvalidArgumentException('The SVG background contains unsupported content.');
            }
        }
    }

    private static function ValidateAttribute(string $name, string $value): void
    {
        if ($name === 'xmlns') {
            if ($value !== self::SVG_NAMESPACE) {
                throw new InvalidArgumentException('The SVG background contains an unsupported namespace.');
            }

            return;
        }
        if ($name === 'viewBox') {
            self::NormalizeViewBox($value);

            return;
        }
        if ($name === 'id') {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_.:-]{0,127}$/D', $value) !== 1) {
                throw new InvalidArgumentException('The SVG background contains an invalid identifier.');
            }

            return;
        }
        if ($name === 'd') {
            if ($value === ''
                || strlen($value) > 65535
                || preg_match('/^[MmZzLlHhVvCcSsQqTtAa0-9eE+.,\-\s]+$/D', $value) !== 1) {
                throw new InvalidArgumentException('The SVG background contains invalid path data.');
            }

            return;
        }
        if ($name === 'points') {
            if (preg_match('/^[0-9eE+.,\-\s]+$/D', $value) !== 1) {
                throw new InvalidArgumentException('The SVG background contains invalid point data.');
            }

            return;
        }
        if (in_array($name, ['transform', 'gradientTransform'], true)) {
            self::ValidateTransform($value);

            return;
        }
        if (in_array($name, self::PAINT_ATTRIBUTES, true)) {
            self::ValidatePaint($value);

            return;
        }
        if (in_array($name, self::NUMERIC_ATTRIBUTES, true)) {
            if ($value === '' || preg_match('/^[0-9eE+.,%\-\s]+$/D', $value) !== 1) {
                throw new InvalidArgumentException('The SVG background contains invalid numeric attributes.');
            }

            return;
        }
        if ($name === 'preserveAspectRatio') {
            if (preg_match('/^(?:none|x(?:Min|Mid|Max)Y(?:Min|Mid|Max)(?:\s+(?:meet|slice))?)$/D', $value) !== 1) {
                throw new InvalidArgumentException('The SVG background contains an invalid aspect-ratio rule.');
            }

            return;
        }
        if ($name === 'gradientUnits') {
            if (!in_array($value, ['objectBoundingBox', 'userSpaceOnUse'], true)) {
                throw new InvalidArgumentException('The SVG background contains invalid gradient units.');
            }

            return;
        }
        if ($name === 'spreadMethod') {
            if (!in_array($value, ['pad', 'reflect', 'repeat'], true)) {
                throw new InvalidArgumentException('The SVG background contains an invalid gradient spread method.');
            }

            return;
        }
        if (in_array($name, ['fill-rule', 'clip-rule'], true)) {
            if (!in_array($value, ['nonzero', 'evenodd', 'inherit'], true)) {
                throw new InvalidArgumentException('The SVG background contains an invalid fill rule.');
            }

            return;
        }
        if ($name === 'stroke-linecap') {
            if (!in_array($value, ['butt', 'round', 'square', 'inherit'], true)) {
                throw new InvalidArgumentException('The SVG background contains an invalid stroke line cap.');
            }

            return;
        }
        if ($name === 'stroke-linejoin') {
            if (!in_array($value, ['miter', 'round', 'bevel', 'inherit'], true)) {
                throw new InvalidArgumentException('The SVG background contains an invalid stroke line join.');
            }

            return;
        }
        if ($name === 'vector-effect' && !in_array($value, ['none', 'non-scaling-stroke'], true)) {
            throw new InvalidArgumentException('The SVG background contains an invalid vector effect.');
        }
    }

    private static function SanitizeStyle(string $style): string
    {
        $sanitized = [];
        foreach (explode(';', $style) as $declaration) {
            $declaration = trim($declaration);
            if ($declaration === '') {
                continue;
            }
            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) {
                throw new InvalidArgumentException('The SVG background contains invalid inline styles.');
            }
            $name = strtolower(trim($parts[0]));
            $value = trim($parts[1]);
            if (!in_array($name, self::ALLOWED_STYLE_PROPERTIES, true)) {
                throw new InvalidArgumentException('The SVG background contains unsupported inline styles.');
            }
            self::ValidateAttribute($name, $value);
            $sanitized[] = $name . ':' . $value;
        }

        return implode(';', $sanitized);
    }

    private static function ValidatePaint(string $value): void
    {
        if (preg_match('/^url\(#[A-Za-z_][A-Za-z0-9_.:-]{0,127}\)$/D', $value) === 1) {
            return;
        }
        if ($value === ''
            || preg_match('/url\s*\(|javascript|expression|var\s*\(/i', $value) === 1
            || preg_match('/^[A-Za-z0-9#(),.%+\-\s]+$/D', $value) !== 1) {
            throw new InvalidArgumentException('The SVG background contains an invalid paint value.');
        }
    }

    private static function ValidateTransform(string $value): void
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9eE+.,\-\s()]+$/D', $value) !== 1) {
            throw new InvalidArgumentException('The SVG background contains an invalid transformation.');
        }
        $remaining = preg_replace(
            '/(?:matrix|translate|scale|rotate|skewX|skewY)\s*\([0-9eE+.,\-\s]+\)/',
            '',
            $value
        );
        if ($remaining === null || trim($remaining) !== '') {
            throw new InvalidArgumentException('The SVG background contains an invalid transformation.');
        }
    }

    private static function NormalizeViewBox(string $viewBox): string
    {
        $parts = preg_split('/[\s,]+/', trim($viewBox)) ?: [];
        if (count($parts) !== 4) {
            throw new InvalidArgumentException('The SVG background requires a finite viewBox with positive width and height.');
        }

        $numbers = [];
        foreach ($parts as $part) {
            if (preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/D', $part) !== 1) {
                throw new InvalidArgumentException('The SVG background requires a finite viewBox with positive width and height.');
            }
            $number = (float) $part;
            if (!is_finite($number)) {
                throw new InvalidArgumentException('The SVG background requires a finite viewBox with positive width and height.');
            }
            $numbers[] = $number;
        }
        if ($numbers[2] <= 0.0 || $numbers[3] <= 0.0) {
            throw new InvalidArgumentException('The SVG background requires a finite viewBox with positive width and height.');
        }

        return implode(' ', array_map(self::FormatNumber(...), $numbers));
    }

    private static function FormatNumber(float $number): string
    {
        $formatted = rtrim(rtrim(number_format($number, 8, '.', ''), '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
}
