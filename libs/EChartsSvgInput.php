<?php

declare(strict_types=1);

namespace eCharts;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

/** Opens a bounded SVG upload. The consuming importer validates its supported drawing elements. */
final class EChartsSvgInput
{
    public const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    private const UNREADABLE = 'The selected file does not contain readable SVG data.';

    public static function Load(
        string $fileData,
        int $maxInputBytes,
        int $maxFileBytes,
        string $inputSizeError,
        string $fileSizeError
    ): DOMElement {
        $svg = trim($fileData);
        if ($svg === '' || strlen($svg) > $maxInputBytes) {
            throw new InvalidArgumentException($inputSizeError);
        }
        if (preg_match('/^data:image\/svg\+xml(?:;[^,]*)?;base64,/i', $svg, $prefix) === 1) {
            $svg = substr($svg, strlen($prefix[0]));
        }
        if (!str_starts_with($svg, '<') && !str_starts_with($svg, "\xEF\xBB\xBF")) {
            $svg = base64_decode(preg_replace('/\s+/', '', $svg) ?? '', true);
        }
        if ($svg === false || $svg === '' || str_contains($svg, "\0")) {
            throw new InvalidArgumentException(self::UNREADABLE);
        }
        // Count the complete decoded file, including its BOM and XML comments.
        if (strlen($svg) > $maxFileBytes) {
            throw new InvalidArgumentException($fileSizeError);
        }
        // Skip XML comments and instructions as complete tokens when looking for real declarations.
        if (preg_match('~<!--.*?-->(*SKIP)(*F)|<\?.*?\?>(*SKIP)(*F)|<!DOCTYPE|<!ENTITY~is', $svg) !== 0) {
            throw new InvalidArgumentException('SVG document types and entities are not supported.');
        }
        if (!class_exists(DOMDocument::class)) {
            throw new InvalidArgumentException('SVG import requires the PHP DOM extension.');
        }

        $previousErrors = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            $document->resolveExternals = false;
            $document->substituteEntities = false;
            if (!$document->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT)) {
                throw new InvalidArgumentException(self::UNREADABLE);
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        $root = $document->documentElement;
        if (!$root instanceof DOMElement || $root->tagName !== 'svg'
            || !in_array($root->namespaceURI, [null, '', self::SVG_NAMESPACE], true)) {
            throw new InvalidArgumentException('The selected file is not an SVG document.');
        }
        foreach ($document->childNodes as $child) {
            if ($child !== $root && $child->nodeType !== XML_COMMENT_NODE) {
                throw new InvalidArgumentException(self::UNREADABLE);
            }
        }
        return $root;
    }
}
