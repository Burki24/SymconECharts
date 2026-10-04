<?php

declare(strict_types=1);

use SymconECharts\EChartsSvgImage;

require_once __DIR__ . '/../libs/EChartsSvgImage.php';

/** @throws RuntimeException */
function assertSvgImage(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @throws RuntimeException */
function assertSvgImageRejected(string $svg, string $message): void
{
    try {
        EChartsSvgImage::Import(base64_encode($svg));
    } catch (InvalidArgumentException) {
        return;
    }

    throw new RuntimeException($message);
}

$imported = EChartsSvgImage::Import(base64_encode(<<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180" viewBox="0 0 320 180">
  <defs>
    <linearGradient id="dial-gradient" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#123456"/>
      <stop offset="100%" style="stop-color:#ABCDEF;stop-opacity:0.8"/>
    </linearGradient>
  </defs>
  <g transform="translate(10 5) rotate(3 150 85)" opacity="0.9">
    <rect x="0" y="0" width="300" height="170" rx="12" fill="url(#dial-gradient)"/>
    <circle cx="150" cy="85" r="35" style="fill:none;stroke:#FFFFFF;stroke-width:2"/>
  </g>
</svg>
SVG));

assertSvgImage($imported['viewBox'] === '0 0 320 180', 'SVG image viewBox changed.');
assertSvgImage($imported['width'] === 320.0 && $imported['height'] === 180.0, 'SVG image dimensions changed.');
assertSvgImage(
    str_starts_with($imported['dataUri'], 'data:image/svg+xml;base64,'),
    'SVG image import must return an embeddable data URI.'
);
$sanitized = base64_decode(substr($imported['dataUri'], strlen('data:image/svg+xml;base64,')), true);
assertSvgImage(is_string($sanitized), 'SVG image data URI must contain valid base64 data.');
assertSvgImage(
    str_contains($sanitized, 'viewBox="0 0 320 180"')
        && !str_contains($sanitized, 'width="320"')
        && !str_contains($sanitized, 'height="180"')
        && str_contains($sanitized, 'fill="url(#dial-gradient)"')
        && str_contains($sanitized, 'transform="translate(10 5) rotate(3 150 85)"'),
    'SVG image import must preserve safe vector styling while normalizing its viewport.'
);

assertSvgImageRejected(
    '<svg viewBox="0 0 10 10"><script>alert(1)</script><path d="M0 0L10 10"/></svg>',
    'SVG image import must reject scripts.'
);
assertSvgImageRejected(
    '<svg viewBox="0 0 10 10"><image href="https://example.invalid/image.png"/></svg>',
    'SVG image import must reject external image references.'
);
assertSvgImageRejected(
    '<svg viewBox="0 0 10 10"><style>path{fill:red}</style><path d="M0 0L10 10"/></svg>',
    'SVG image import must reject CSS blocks.'
);
assertSvgImageRejected(
    '<svg viewBox="0 0 10 10"><path d="M0 0L10 10" fill="url(https://example.invalid/fill.svg)"/></svg>',
    'SVG image import must reject external paint references.'
);
assertSvgImageRejected(
    '<svg viewBox="0 0 10 10"><foreignObject width="10" height="10"><div>unsafe</div></foreignObject></svg>',
    'SVG image import must reject foreign objects.'
);

echo "ECharts SVG image import verified.\n";
