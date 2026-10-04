<?php

declare(strict_types=1);

const IPS_KERNELSTARTED = 10001;
const VM_UPDATE = 10603;
const KR_READY = 10103;
const IS_ACTIVE = 102;
const IS_INACTIVE = 104;
const SYMCON_OUTPUT_BUFFER_LIMIT = 1048576;

$GLOBALS['symconTestVariables'] = [
    4711 => [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000000,
        'Value'           => 42.5,
        'Name'            => 'Living room temperature'
    ],
    4712 => [
        'VariableType'    => 3,
        'VariableUpdated' => 1780000001,
        'Value'           => 'not numeric',
        'Name'            => 'Text value'
    ],
    4713 => [
        'VariableType'    => 1,
        'VariableUpdated' => 1780000002,
        'Value'           => 58,
        'Name'            => 'Living room humidity'
    ],
    4714 => [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000003,
        'Value'           => 1013.25,
        'Name'            => 'Air pressure'
    ]
];

function IPS_GetKernelRunlevel(): int
{
    return KR_READY;
}

function IPS_VariableExists(int $variableID): bool
{
    return isset($GLOBALS['symconTestVariables'][$variableID]);
}

/** @return array<string, int> */
function IPS_GetVariable(int $variableID): array
{
    $variable = $GLOBALS['symconTestVariables'][$variableID];

    return [
        'VariableType'    => $variable['VariableType'],
        'VariableUpdated' => $variable['VariableUpdated']
    ];
}

function IPS_GetName(int $objectID): string
{
    return (string) $GLOBALS['symconTestVariables'][$objectID]['Name'];
}

function GetValue(int $variableID): mixed
{
    return $GLOBALS['symconTestVariables'][$variableID]['Value'];
}

abstract class IPSModuleStrict
{
    /** @var null|callable(string): string */
    public static $ParentResponder = null;

    /** @var array<string, mixed> */
    private array $properties = [];

    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var list<int> */
    private array $references = [];

    /** @var list<array{SenderID:int, Message:int}> */
    private array $messages = [];

    /** @var list<mixed> */
    private array $visualizationUpdates = [];

    /** @var list<array{Field:string, Parameter:string, Value:mixed}> */
    private array $formUpdates = [];

    private int $status = IS_INACTIVE;
    private bool $parentActive = true;
    private string $summary = '';
    private int $visualizationType = 0;

    public function Create(): void
    {
    }

    public function ApplyChanges(): void
    {
    }

    public function SetTestProperty(string $name, mixed $value): void
    {
        $this->properties[$name] = $value;
    }

    public function SetTestParentActive(bool $active): void
    {
        $this->parentActive = $active;
    }

    public function GetTestStatus(): int
    {
        return $this->status;
    }

    /** @return list<int> */
    public function GetTestReferences(): array
    {
        return $this->references;
    }

    public function GetTestSummary(): string
    {
        return $this->summary;
    }

    public function GetTestVisualizationType(): int
    {
        return $this->visualizationType;
    }

    /** @return list<array{SenderID:int, Message:int}> */
    public function GetTestMessages(): array
    {
        return $this->messages;
    }

    /** @return list<mixed> */
    public function GetTestVisualizationUpdates(): array
    {
        return $this->visualizationUpdates;
    }

    /** @return list<array{Field:string, Parameter:string, Value:mixed}> */
    public function GetTestFormUpdates(): array
    {
        return $this->formUpdates;
    }

    protected function RegisterPropertyInteger(string $name, int $default): bool
    {
        $this->properties[$name] ??= $default;

        return true;
    }

    protected function RegisterPropertyFloat(string $name, float $default): bool
    {
        $this->properties[$name] ??= $default;

        return true;
    }

    protected function RegisterPropertyString(string $name, string $default): bool
    {
        $this->properties[$name] ??= $default;

        return true;
    }

    protected function ReadPropertyInteger(string $name): int
    {
        return (int) $this->properties[$name];
    }

    protected function ReadPropertyFloat(string $name): float
    {
        return (float) $this->properties[$name];
    }

    protected function ReadPropertyString(string $name): string
    {
        return (string) $this->properties[$name];
    }

    protected function RegisterAttributeInteger(string $name, int $default): bool
    {
        $this->attributes[$name] ??= $default;

        return true;
    }

    protected function RegisterAttributeString(string $name, string $default): bool
    {
        $this->attributes[$name] ??= $default;

        return true;
    }

    protected function ReadAttributeInteger(string $name): int
    {
        return (int) $this->attributes[$name];
    }

    protected function WriteAttributeInteger(string $name, int $value): bool
    {
        $this->attributes[$name] = $value;

        return true;
    }

    protected function WriteAttributeString(string $name, string $value): bool
    {
        $this->attributes[$name] = $value;

        return true;
    }

    protected function ReadAttributeString(string $name): string
    {
        return (string) $this->attributes[$name];
    }

    protected function RegisterMessage(int $senderID, int $message): bool
    {
        $registration = ['SenderID' => $senderID, 'Message' => $message];
        if (!in_array($registration, $this->messages, true)) {
            $this->messages[] = $registration;
        }

        return true;
    }

    protected function UnregisterMessage(int $senderID, int $message): bool
    {
        $this->messages = array_values(array_filter(
            $this->messages,
            static fn (array $registration): bool => $registration !== [
                'SenderID' => $senderID,
                'Message'  => $message
            ]
        ));

        return true;
    }

    protected function SetVisualizationType(int $visualizationType): bool
    {
        $this->visualizationType = $visualizationType;

        return true;
    }

    protected function UpdateVisualizationValue(mixed $value): bool
    {
        $this->visualizationUpdates[] = $value;

        return true;
    }

    protected function UpdateFormField(string $field, string $parameter, mixed $value): bool
    {
        $this->formUpdates[] = [
            'Field'     => $field,
            'Parameter' => $parameter,
            'Value'     => $value
        ];

        return true;
    }

    protected function Translate(string $text): string
    {
        return $text;
    }

    protected function SetStatus(int $status): bool
    {
        $this->status = $status;

        return true;
    }

    protected function SetSummary(string $summary): bool
    {
        $this->summary = $summary;

        return true;
    }

    protected function RegisterReference(int $id): bool
    {
        if (!in_array($id, $this->references, true)) {
            $this->references[] = $id;
        }

        return true;
    }

    protected function UnregisterReference(int $id): bool
    {
        $this->references = array_values(array_filter(
            $this->references,
            static fn (int $reference): bool => $reference !== $id
        ));

        return true;
    }

    protected function HasActiveParent(): bool
    {
        return $this->parentActive;
    }

    protected function SendDataToParent(string $data): string
    {
        if (!is_callable(self::$ParentResponder)) {
            throw new RuntimeException('No parent responder configured.');
        }

        return (self::$ParentResponder)($data);
    }

    protected function SendDebug(string $message, string $data, int $format): bool
    {
        return true;
    }
}

require_once dirname(__DIR__) . '/EChartsGateway/module.php';
require_once dirname(__DIR__) . '/EChartsGaugeSingle/module.php';
require_once dirname(__DIR__) . '/EChartsGaugeMulti/module.php';

function assertGatewayGauge(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$gateway = new EChartsGateway();
$gateway->Create();
$gateway->ApplyChanges();
IPSModuleStrict::$ParentResponder = static fn (string $json): string => $gateway->ForwardData($json);

$gauge = new EChartsGaugeSingle();
$gauge->Create();
$gauge->SetTestProperty('SourceVariableID', 4711);
$gauge->SetTestProperty('Minimum', -20.0);
$gauge->SetTestProperty('Maximum', 80.0);
$gauge->SetTestProperty('Title', 'Room climate');
$gauge->SetTestProperty('Unit', '°C');
$gauge->SetTestProperty('Decimals', 1);
$gauge->SetTestProperty('GaugePreset', 'progress');
$gauge->SetTestProperty('EChartsTheme', 'vintage');
$gauge->SetTestProperty('ScaleFontSizePercent', 150);
$gauge->SetTestProperty('ValueFontSizePercent', 125);
$gauge->SetTestProperty('UnitFontSizePercent', 75);
$gauge->SetTestProperty('TitleFontSizePercent', 110);
$gauge->SetTestProperty('RingWidthPercent', 120);
$gauge->SetTestProperty('PointerWidthPercent', 80);
$gauge->SetTestProperty('PointerLengthPercent', 130);
$gauge->SetTestProperty('MinorTickLengthPercent', 75);
$gauge->SetTestProperty('MajorTickLengthPercent', 150);
$gauge->SetTestProperty('PointerShape', 'arrow');
$gauge->SetTestProperty('GaugeArcMode', 'custom');
$gauge->SetTestProperty('GaugeStartPosition', 270.0);
$gauge->SetTestProperty('GaugeEndPosition', 67.5);
$gauge->SetTestProperty('GaugeColorMode', 'custom');
$gauge->SetTestProperty('PointerColor', 0x112233);
$gauge->SetTestProperty('ProgressColor', 0x223344);
$gauge->SetTestProperty('RingColor', 0x334455);
$gauge->SetTestProperty('ScaleColor', 0x445566);
$gauge->SetTestProperty('ValueColor', 0x556677);
$gauge->SetTestProperty('TitleColor', 0x667788);
$gauge->ApplyChanges();

assertGatewayGauge($gauge->GetTestStatus() === IS_ACTIVE, 'Gauge Single must become active.');
assertGatewayGauge($gauge->GetTestReferences() === [4711], 'Gauge Single must register its source reference.');
assertGatewayGauge($gauge->GetTestSummary() === 'Living room temperature', 'Gauge Single summary changed.');
assertGatewayGauge($gauge->GetTestVisualizationType() === 1, 'Gauge Single must expose an HTML-SDK tile.');
assertGatewayGauge(
    in_array(['SenderID' => 4711, 'Message' => VM_UPDATE], $gauge->GetTestMessages(), true),
    'Gauge Single must subscribe to source value updates.'
);

$visualizationTile = $gauge->GetVisualizationTile();
assertGatewayGauge(str_contains($visualizationTile, 'window.echarts'), 'Gauge Single tile must embed Apache ECharts.');
assertGatewayGauge(str_contains($visualizationTile, 'echarts-gauge-chart'), 'Gauge Single tile root is missing.');
assertGatewayGauge(str_contains($visualizationTile, '"echartsVersion":"6.1.0"'), 'Gauge Single ECharts version changed.');
assertGatewayGauge(str_contains($visualizationTile, '"preset":"progress"'), 'Gauge Single tile must receive the selected preset.');
assertGatewayGauge(str_contains($visualizationTile, '"theme":"vintage"'), 'Gauge Single tile must receive the selected theme.');
assertGatewayGauge(str_contains($visualizationTile, '"echartsThemes":'), 'Gauge Single tile must receive the shared theme palettes.');
assertGatewayGauge(str_contains($visualizationTile, "registerTheme('vintage'"), 'Gauge Single tile must register official ECharts themes.');
assertGatewayGauge(
    str_contains($visualizationTile, 'option.backgroundColor = colors.background'),
    'Official light themes must receive an explicit readable background.'
);
assertGatewayGauge(str_contains($visualizationTile, "case 'speed':"), 'Gauge Single tile must render the Speed preset.');
foreach ([
    'function resolveGaugeLayout(preset, width, height, style)',
    "resolveStyleScale(style, 'scaleFontSizePercent')",
    "resolveStyleScale(style, 'valueFontSizePercent')",
    "resolveStyleScale(style, 'unitFontSizePercent')",
    "resolveStyleScale(style, 'titleFontSizePercent')",
    "resolveStyleScale(style, 'ringWidthPercent')",
    "resolveStyleScale(style, 'pointerWidthPercent')",
    "resolveStyleScale(style, 'pointerLengthPercent')",
    "pointerLength: Math.round(parseFloat(definition.pointerLength) * pointerLength * 100) / 100 + '%'",
    "resolveStyleScale(style, 'minorTickLengthPercent')",
    "resolveStyleScale(style, 'majorTickLengthPercent')",
    'var scale = Math.min(width / 440, height / 400);',
    'var height = Math.max(chartElement.clientHeight, 160);',
    'center: [layout.centerX, layout.centerY]',
    'radius: layout.radius',
    'distance: layout.tickDistance',
    'distance: layout.splitDistance',
    'distance: layout.labelDistance',
    'offsetCenter: [0, layout.detailOffset]',
    'offsetCenter: [0, layout.titleOffset]',
    'function applyArcDesign(series, style)',
    'series.startAngle = 90 - startPosition;',
    'function applyPointerShape(series, style, layout)',
    "series.pointer.icon = 'path://' + path;",
    'function applyCustomColors(series, style, colors)'
] as $responsiveLayoutContract) {
    assertGatewayGauge(
        str_contains($visualizationTile, $responsiveLayoutContract),
        'Gauge Single tile must derive every preset from the shared responsive layout: ' . $responsiveLayoutContract
    );
}
assertGatewayGauge(
    !str_contains($visualizationTile, 'distance: -lineWidth'),
    'Gauge ticks and split lines must not be pushed outside the progress ring.'
);
foreach ([
    "var speedPointerIcon = 'path://M2090.36389,615.30999",
    'series.pointer.icon = speedPointerIcon;',
    'series.pointer.offsetCenter = [0, \'5%\'];',
    'series.anchor.show = false;',
    'series.axisTick.splitNumber = 2;',
    'series.splitNumber = resolveSpeedSplitNumber(minimum, maximum);',
    'series.detail.rich = {'
] as $speedExampleContract) {
    assertGatewayGauge(
        str_contains($visualizationTile, $speedExampleContract),
        'Gauge Single must retain the characteristic official Speed Gauge option: ' . $speedExampleContract
    );
}
assertGatewayGauge(!str_contains($visualizationTile, '<script src="http'), 'Gauge Single must not load ECharts from a CDN.');
assertGatewayGauge(
    strlen($visualizationTile) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Gauge Single tile must remain below the Symcon output-buffer limit.'
);

$configurationForm = json_decode($gauge->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$previewElement = null;
foreach ($configurationForm['elements'] ?? [] as $element) {
    if (($element['type'] ?? null) !== 'ExpansionPanel' || ($element['caption'] ?? null) !== 'Tile designer') {
        continue;
    }

    foreach ($element['items'] ?? [] as $item) {
        if (($item['name'] ?? null) === 'GaugePreview') {
            $previewElement = $item;
            break 2;
        }
    }
}
assertGatewayGauge(is_array($previewElement), 'Gauge Single configuration form must contain a preview image.');
assertGatewayGauge(
    str_starts_with((string) ($previewElement['image'] ?? ''), 'data:image/svg+xml;base64,'),
    'Gauge Single configuration form must initialize the preview as an SVG data URI.'
);
$initialPreviewSvg = base64_decode(
    substr((string) $previewElement['image'], strlen('data:image/svg+xml;base64,')),
    true
);
assertGatewayGauge(is_string($initialPreviewSvg), 'Gauge Single preview SVG must be valid Base64.');
assertGatewayGauge(str_contains($initialPreviewSvg, 'data-preset="progress"'), 'Gauge Single preview must use the selected preset.');
assertGatewayGauge(str_contains($initialPreviewSvg, 'data-theme="vintage"'), 'Gauge Single preview must use the selected theme.');
assertGatewayGauge(
    str_contains($initialPreviewSvg, 'data-pointer-length-percent="130"'),
    'Gauge Single preview must apply the configured pointer length.'
);
assertGatewayGauge(str_contains($initialPreviewSvg, '#FEF8EF'), 'Gauge Single preview must apply the Vintage background.');
assertGatewayGauge(str_contains($initialPreviewSvg, 'Room climate'), 'Gauge Single preview must contain the title.');
assertGatewayGauge(str_contains($initialPreviewSvg, '42.5 °C'), 'Gauge Single preview must use the current source value.');
foreach (['font-size:21px', 'font-size:57.5px', 'font-size:34.5px', 'font-size:19.8px', 'stroke-width:24.00px'] as $customPreviewStyle) {
    assertGatewayGauge(
        str_contains($initialPreviewSvg, $customPreviewStyle),
        'Gauge Single preview must apply the configured fine tuning: ' . $customPreviewStyle
    );
}

foreach (['basic', 'simple', 'progress', 'speed'] as $preset) {
    $presetSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
        42.5,
        -20.0,
        80.0,
        'Room climate',
        '°C',
        1,
        'en',
        $preset
    );
    assertGatewayGauge(
        str_contains($presetSvg, 'data-preset="' . $preset . '"'),
        'Gauge Single preview must render the ' . $preset . ' preset.'
    );
}
$shortCustomPointerSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
    50.0,
    0.0,
    100.0,
    '',
    '',
    0,
    'en',
    'simple',
    'auto',
    [
        'pointerShape'         => 'custom',
        'pointerPath'          => 'M0 0L10 100L0 90Z',
        'pointerViewBox'       => '0 0 10 100',
        'pointerLengthPercent' => 50
    ]
);
$longCustomPointerSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
    50.0,
    0.0,
    100.0,
    '',
    '',
    0,
    'en',
    'simple',
    'auto',
    [
        'pointerShape'         => 'custom',
        'pointerPath'          => 'M0 0L10 100L0 90Z',
        'pointerViewBox'       => '0 0 10 100',
        'pointerLengthPercent' => 150
    ]
);
assertGatewayGauge(
    str_contains($shortCustomPointerSvg, 'height="49.00"')
        && str_contains($longCustomPointerSvg, 'height="147.00"'),
    'Gauge Single preview must scale custom pointer length from 50 to 150 percent.'
);
foreach ([
    'basic'    => ['centerY' => 196.0, 'radius' => 132.0],
    'simple'   => ['centerY' => 196.0, 'radius' => 132.0],
    'progress' => ['centerY' => 190.0, 'radius' => 132.0],
    'speed'    => ['centerY' => 232.0, 'radius' => 142.0]
] as $preset => $geometry) {
    $presetSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
        42.5,
        -20.0,
        80.0,
        '',
        '°C',
        1,
        'en',
        $preset
    );
    $firstSplitMatches = [];
    assertGatewayGauge(
        preg_match(
            '/<line x1="([^"]+)" y1="([^"]+)" x2="[^"]+" y2="[^"]+" class="major"\/>/',
            $presetSvg,
            $firstSplitMatches
        ) === 1,
        'Gauge preview must expose a major split line for ' . $preset . '.'
    );
    $splitRadius = sqrt(
        ((float) $firstSplitMatches[1] - 360.0) ** 2
        + ((float) $firstSplitMatches[2] - $geometry['centerY']) ** 2
    );
    assertGatewayGauge(
        $splitRadius < $geometry['radius'],
        'Gauge preview must place split lines inside the progress ring for ' . $preset . '.'
    );
}
$basicPresetSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
    42.5,
    -20.0,
    80.0,
    'Room climate',
    '°C',
    1,
    'en',
    'basic'
);
assertGatewayGauge(
    preg_match('/<path d="[^"]+" class="progress"/', $basicPresetSvg) !== 1,
    'Basic Gauge must not render a progress arc.'
);
$speedPresetSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
    42.5,
    -20.0,
    80.0,
    'Room climate',
    '°C',
    1,
    'en',
    'speed'
);
assertGatewayGauge(str_contains($speedPresetSvg, 'class="detail-box"'), 'Speed Gauge must render its value box.');
assertGatewayGauge(
    str_contains($speedPresetSvg, 'class="speed-pointer speed-pointer-shadow"'),
    'Speed Gauge preview must use the sample pointer.'
);
assertGatewayGauge(str_contains($speedPresetSvg, 'class="value-unit"'), 'Speed Gauge preview must style the unit separately.');
assertGatewayGauge(str_contains($speedPresetSvg, 'data-major-splits="10"'), 'A 0–100 scale must keep ten readable divisions.');

$customDesignSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
    42.5,
    -20.0,
    80.0,
    'Custom design',
    '°C',
    1,
    'de',
    'simple',
    'dark',
    [
        'pointerShape'  => 'arrow',
        'arcMode'       => 'custom',
        'startPosition' => 270.0,
        'endPosition'   => 67.5,
        'colorMode'     => 'custom',
        'pointerColor'  => '#112233',
        'progressColor' => '#223344',
        'ringColor'     => '#334455',
        'scaleColor'    => '#445566',
        'valueColor'    => '#556677',
        'titleColor'    => '#667788'
    ]
);
assertGatewayGauge(
    str_contains($customDesignSvg, 'data-pointer-shape="arrow"'),
    'Gauge preview must render the selected pointer shape.'
);
assertGatewayGauge(
    str_contains($customDesignSvg, 'data-arc-mode="custom"')
        && str_contains($customDesignSvg, 'data-start-position="270.00"')
        && str_contains($customDesignSvg, 'data-end-position="67.50"'),
    'Gauge preview must render the selected clockwise arc positions.'
);
foreach (['#112233', '#223344', '#334455', '#445566', '#556677', '#667788'] as $customColor) {
    assertGatewayGauge(
        str_contains($customDesignSvg, $customColor),
        'Gauge preview must render custom color ' . $customColor . '.'
    );
}

$officialSpeedRangeSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
    100.0,
    0.0,
    240.0,
    '',
    'km/h',
    0,
    'en',
    'speed'
);
assertGatewayGauge(
    str_contains($officialSpeedRangeSvg, 'data-major-splits="12"'),
    'The official 0–240 Speed Gauge range must use twelve major divisions.'
);
assertGatewayGauge(
    str_contains($officialSpeedRangeSvg, '>20</text>'),
    'The official Speed Gauge preview must expose the characteristic 20-unit scale steps.'
);

$shineSpeedSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
    1.0,
    0.0,
    1000.0,
    'Wind speed',
    'km/h',
    2,
    'de',
    'speed',
    'shine'
);
assertGatewayGauge(
    substr_count($shineSpeedSvg, 'class="theme-track-segment"') === 3,
    'The Shine form preview must reproduce all three official Gauge axis-line segments.'
);
foreach (['#2B821D', '#005EAA', '#C12E34'] as $segmentColor) {
    assertGatewayGauge(
        str_contains($shineSpeedSvg, 'stroke:' . $segmentColor),
        'The Shine form preview is missing Gauge axis-line color ' . $segmentColor . '.'
    );
}
$middleLabelMatches = [];
assertGatewayGauge(
    preg_match(
        '/<text x="[^"]+" y="([^"]+)" class="axis" data-label-index="10">500<\/text>/',
        $shineSpeedSvg,
        $middleLabelMatches
    ) === 1,
    'The Speed form preview must retain the middle scale label for layout comparison.'
);
assertGatewayGauge(
    (float) $middleLabelMatches[1] > 90.0 && (float) $middleLabelMatches[1] < 232.0,
    'The Speed form preview must place scale labels inside the Gauge arc like the native renderer.'
);

$gauge->UpdateGaugePreview(4711, 0.0, 200.0, 'Wind & weather', 'km/h', 2, 'speed', 'dark');
$formUpdates = $gauge->GetTestFormUpdates();
$latestFormUpdate = end($formUpdates);
assertGatewayGauge(
    is_array($latestFormUpdate)
        && ($latestFormUpdate['Field'] ?? null) === 'GaugePreview'
        && ($latestFormUpdate['Parameter'] ?? null) === 'image',
    'Gauge Single must update the preview image when form values change.'
);
$updatedPreviewUri = is_array($latestFormUpdate) ? (string) ($latestFormUpdate['Value'] ?? '') : '';
$updatedPreviewSvg = base64_decode(
    substr($updatedPreviewUri, strlen('data:image/svg+xml;base64,')),
    true
);
assertGatewayGauge(is_string($updatedPreviewSvg), 'Updated Gauge Single preview must be valid Base64.');
assertGatewayGauge(str_contains($updatedPreviewSvg, 'data-preset="speed"'), 'Updated Gauge Single preview must switch presets.');
assertGatewayGauge(str_contains($updatedPreviewSvg, 'data-theme="dark"'), 'Updated Gauge Single preview must switch themes.');
assertGatewayGauge(
    str_contains($updatedPreviewSvg, 'Wind &amp; weather'),
    'Gauge Single preview must XML-escape user-provided labels.'
);
assertGatewayGauge(
    !str_contains($updatedPreviewSvg, '&amp;amp;'),
    'Gauge Single preview must not double-escape its accessible label.'
);
assertGatewayGauge(str_contains($updatedPreviewSvg, '42.50 km/h'), 'Gauge Single preview formatting changed.');

$gauge->UpdateGaugePreview(
    4711,
    0.0,
    200.0,
    'Configured design',
    'km/h',
    1,
    'simple',
    'dark',
    100,
    100,
    100,
    100,
    100,
    100,
    100,
    100,
    'line',
    'half',
    0.0,
    180.0,
    'custom',
    0x102030,
    0x203040,
    0x304050,
    0x405060,
    0x506070,
    0x607080
);
$formUpdates = $gauge->GetTestFormUpdates();
$configuredPreviewUpdate = end($formUpdates);
$configuredPreviewUri = is_array($configuredPreviewUpdate) ? (string) ($configuredPreviewUpdate['Value'] ?? '') : '';
$configuredPreviewSvg = base64_decode(
    substr($configuredPreviewUri, strlen('data:image/svg+xml;base64,')),
    true
);
assertGatewayGauge(
    is_string($configuredPreviewSvg)
        && str_contains($configuredPreviewSvg, 'data-pointer-shape="line"')
        && str_contains($configuredPreviewSvg, 'data-arc-mode="half"')
        && str_contains($configuredPreviewSvg, '#102030')
        && str_contains($configuredPreviewSvg, '#607080'),
    'Gauge Single form preview must apply pointer, arc and custom color values before they are persisted.'
);

$gauge->UpdateGaugePreview(4711, 100.0, 0.0, 'Invalid', '', 1);
$formUpdates = $gauge->GetTestFormUpdates();
$invalidPreviewUpdate = end($formUpdates);
$invalidPreviewUri = is_array($invalidPreviewUpdate) ? (string) ($invalidPreviewUpdate['Value'] ?? '') : '';
$invalidPreviewSvg = base64_decode(
    substr($invalidPreviewUri, strlen('data:image/svg+xml;base64,')),
    true
);
assertGatewayGauge(
    is_string($invalidPreviewSvg) && str_contains($invalidPreviewSvg, 'Minimum must be lower than maximum.'),
    'Gauge Single preview must explain an invalid range without persisting the form values.'
);

$gaugeData = json_decode($gauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(($gaugeData['schemaVersion'] ?? null) === 1, 'Gauge Single data schema version changed.');
assertGatewayGauge(($gaugeData['family'] ?? null) === 'gauge', 'Gauge Single data family changed.');
assertGatewayGauge(($gaugeData['value'] ?? null) === 42.5, 'Gauge Single did not receive the source value.');
assertGatewayGauge(($gaugeData['source']['timestamp'] ?? null) === 1780000000, 'Gauge Single timestamp changed.');
assertGatewayGauge(($gaugeData['gauge']['minimum'] ?? null) === -20.0, 'Gauge Single minimum changed.');
assertGatewayGauge(($gaugeData['gauge']['maximum'] ?? null) === 80.0, 'Gauge Single maximum changed.');
assertGatewayGauge(($gaugeData['gauge']['preset'] ?? null) === 'progress', 'Gauge Single preset changed.');
assertGatewayGauge(
    ($gaugeData['gauge']['style'] ?? null) === [
        'scaleFontSizePercent'   => 150,
        'valueFontSizePercent'   => 125,
        'unitFontSizePercent'    => 75,
        'titleFontSizePercent'   => 110,
        'ringWidthPercent'       => 120,
        'pointerWidthPercent'    => 80,
        'pointerLengthPercent'   => 130,
        'minorTickLengthPercent' => 75,
        'majorTickLengthPercent' => 150,
        'pointerShape'           => 'arrow',
        'arcMode'                => 'custom',
        'startPosition'          => 270.0,
        'endPosition'            => 67.5,
        'colorMode'              => 'custom',
        'pointerColor'           => '#112233',
        'progressColor'          => '#223344',
        'ringColor'              => '#334455',
        'scaleColor'             => '#445566',
        'valueColor'             => '#556677',
        'titleColor'             => '#667788'
    ],
    'Gauge Single must expose the validated tile-designer fine tuning in its chart model.'
);
assertGatewayGauge(($gaugeData['theme'] ?? null) === 'vintage', 'Gauge Single theme changed.');

$GLOBALS['symconTestVariables'][4711]['Value'] = 44.75;
$GLOBALS['symconTestVariables'][4711]['VariableUpdated'] = 1780000100;
$gauge->MessageSink(1780000100, 4711, VM_UPDATE, []);
$visualizationUpdates = $gauge->GetTestVisualizationUpdates();
$latestVisualization = end($visualizationUpdates);
assertGatewayGauge(
    is_string($latestVisualization),
    'Gauge Single must publish HTML-SDK updates as a JSON scalar string.'
);
$latestVisualizationState = json_decode($latestVisualization, true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    ($latestVisualizationState['status'] ?? null) === 'ready'
        && ($latestVisualizationState['chart']['value'] ?? null) === 44.75
        && ($latestVisualizationState['chart']['theme'] ?? null) === 'vintage',
    'Gauge Single must publish changed source values to the HTML-SDK tile.'
);
$GLOBALS['symconTestVariables'][4711]['Value'] = 42.5;
$GLOBALS['symconTestVariables'][4711]['VariableUpdated'] = 1780000000;

$multiSources = json_encode([
    [
        'VariableID' => 4711,
        'Label'      => 'Temperature',
        'Minimum'    => -20.0,
        'Maximum'    => 80.0,
        'Unit'       => '°C',
        'Decimals'   => 1
    ],
    [
        'VariableID' => 4713,
        'Label'      => '',
        'Minimum'    => 0.0,
        'Maximum'    => 100.0,
        'Unit'       => '%',
        'Decimals'   => 0
    ]
], JSON_THROW_ON_ERROR);
$multiGauge = new EChartsGaugeMulti();
$multiGauge->Create();
$multiGauge->SetTestProperty('Sources', $multiSources);
$multiGauge->SetTestProperty('Title', 'Room climate');
$multiGauge->ApplyChanges();

assertGatewayGauge($multiGauge->GetTestStatus() === IS_ACTIVE, 'Gauge Multi must become active with two valid sources.');
assertGatewayGauge($multiGauge->GetTestReferences() === [4711, 4713], 'Gauge Multi must register every source reference.');
assertGatewayGauge($multiGauge->GetTestSummary() === '2 sources', 'Gauge Multi summary must identify its source count.');

$multiData = json_decode($multiGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(($multiData['schemaVersion'] ?? null) === 1, 'Gauge Multi data schema version changed.');
assertGatewayGauge(($multiData['family'] ?? null) === 'gauge', 'Gauge Multi data family changed.');
assertGatewayGauge(($multiData['variant'] ?? null) === 'multi', 'Gauge Multi variant is missing.');
assertGatewayGauge(($multiData['gauge']['title'] ?? null) === 'Room climate', 'Gauge Multi title changed.');
assertGatewayGauge(count($multiData['items'] ?? []) === 2, 'Gauge Multi must return every configured source.');
assertGatewayGauge(($multiData['items'][0]['id'] ?? null) === 'variable-4711', 'Gauge Multi item ID changed.');
assertGatewayGauge(($multiData['items'][0]['gauge']['label'] ?? null) === 'Temperature', 'Gauge Multi label changed.');
assertGatewayGauge(($multiData['items'][0]['value'] ?? null) === 42.5, 'Gauge Multi first value changed.');
assertGatewayGauge(
    ($multiData['items'][1]['gauge']['label'] ?? null) === 'Living room humidity',
    'Gauge Multi must fall back to the variable name for an empty label.'
);
assertGatewayGauge(($multiData['items'][1]['value'] ?? null) === 58.0, 'Gauge Multi second value changed.');

$multiGauge->SetTestProperty('Sources', json_encode([
    json_decode($multiSources, true, 512, JSON_THROW_ON_ERROR)[1],
    [
        'VariableID' => 4714,
        'Label'      => 'Pressure',
        'Minimum'    => 900.0,
        'Maximum'    => 1100.0,
        'Unit'       => 'hPa',
        'Decimals'   => 1
    ]
], JSON_THROW_ON_ERROR));
$multiGauge->ApplyChanges();
assertGatewayGauge($multiGauge->GetTestReferences() === [4713, 4714], 'Gauge Multi must replace removed source references.');

$multiGauge->SetTestProperty('Sources', '{invalid');
$multiGauge->ApplyChanges();
assertGatewayGauge($multiGauge->GetTestStatus() === 201, 'Gauge Multi must reject malformed source JSON.');
assertGatewayGauge($multiGauge->GetTestReferences() === [], 'Gauge Multi must remove references for malformed source JSON.');

$tooFewSources = new EChartsGaugeMulti();
$tooFewSources->Create();
$tooFewSources->SetTestProperty('Sources', json_encode([
    json_decode($multiSources, true, 512, JSON_THROW_ON_ERROR)[0]
], JSON_THROW_ON_ERROR));
$tooFewSources->ApplyChanges();
assertGatewayGauge($tooFewSources->GetTestStatus() === 201, 'Gauge Multi must reject fewer than two sources.');

$duplicateSources = new EChartsGaugeMulti();
$duplicateSources->Create();
$duplicateSources->SetTestProperty('Sources', json_encode([
    json_decode($multiSources, true, 512, JSON_THROW_ON_ERROR)[0],
    json_decode($multiSources, true, 512, JSON_THROW_ON_ERROR)[0]
], JSON_THROW_ON_ERROR));
$duplicateSources->ApplyChanges();
assertGatewayGauge($duplicateSources->GetTestStatus() === 201, 'Gauge Multi must reject duplicate source variables.');

$invalidMultiRange = new EChartsGaugeMulti();
$invalidMultiRange->Create();
$invalidSources = json_decode($multiSources, true, 512, JSON_THROW_ON_ERROR);
$invalidSources[1]['Minimum'] = 100.0;
$invalidSources[1]['Maximum'] = 10.0;
$invalidMultiRange->SetTestProperty('Sources', json_encode($invalidSources, JSON_THROW_ON_ERROR));
$invalidMultiRange->ApplyChanges();
assertGatewayGauge($invalidMultiRange->GetTestStatus() === 202, 'Gauge Multi must reject an invalid item range.');

$invalidMultiDecimals = new EChartsGaugeMulti();
$invalidMultiDecimals->Create();
$invalidSources = json_decode($multiSources, true, 512, JSON_THROW_ON_ERROR);
$invalidSources[1]['Decimals'] = 7;
$invalidMultiDecimals->SetTestProperty('Sources', json_encode($invalidSources, JSON_THROW_ON_ERROR));
$invalidMultiDecimals->ApplyChanges();
assertGatewayGauge($invalidMultiDecimals->GetTestStatus() === 202, 'Gauge Multi must reject invalid item decimals.');

$singleGauge = new EChartsGaugeSingle();
$singleGauge->Create();
$singleGauge->SetTestProperty('SourceVariableID', 4711);
$singleGauge->ApplyChanges();
$defaultSingleData = json_decode($singleGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    array_values(array_slice($defaultSingleData['gauge']['style'] ?? [], 0, 9)) === array_fill(0, 9, 100)
        && ($defaultSingleData['gauge']['style']['pointerShape'] ?? null) === 'preset'
        && ($defaultSingleData['gauge']['style']['arcMode'] ?? null) === 'preset'
        && ($defaultSingleData['gauge']['style']['colorMode'] ?? null) === 'theme',
    'Gauge Single designer must preserve every preset default for existing instances.'
);

$customPointerSvg = file_get_contents(__DIR__ . '/fixtures/gauge-pointer-ornate.svg');
assertGatewayGauge(is_string($customPointerSvg), 'The ornate custom SVG pointer fixture must be readable.');
$customPointerFile = base64_encode($customPointerSvg);
$customPointerGauge = new EChartsGaugeSingle();
$customPointerGauge->Create();
$customPointerGauge->SetTestProperty('SourceVariableID', 4711);
$customPointerGauge->SetTestProperty('PointerShape', 'custom');
$customPointerGauge->SetTestProperty('CustomPointerSVG', $customPointerFile);
$customPointerGauge->ApplyChanges();
assertGatewayGauge($customPointerGauge->GetTestStatus() === IS_ACTIVE, 'A valid custom SVG pointer must be accepted.');
$customPointerData = json_decode($customPointerGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    str_contains((string) ($customPointerData['gauge']['style']['pointerPath'] ?? ''), 'M50 397 A27 27')
        && str_contains((string) ($customPointerData['gauge']['style']['pointerPath'] ?? ''), 'M48.5 57 C48.8 40')
        && ($customPointerData['gauge']['style']['pointerViewBox'] ?? null) === '0 0 100 400'
        && !str_contains(json_encode($customPointerData, JSON_THROW_ON_ERROR), '<svg'),
    'Gauge Single must expose only the validated path and viewBox, never the imported SVG markup.'
);
$customPointerTile = $customPointerGauge->GetVisualizationTile();
assertGatewayGauge(
    str_contains($customPointerTile, 'M50 397 A27 27')
        && !str_contains($customPointerTile, '<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 400\"')
        && strlen($customPointerTile) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'The custom pointer tile must embed only its bounded path and remain below the output-buffer limit.'
);
$customPointerForm = json_decode($customPointerGauge->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$customPointerPreview = null;
foreach ($customPointerForm['elements'] ?? [] as $element) {
    if (($element['caption'] ?? null) !== 'Tile designer') {
        continue;
    }
    foreach ($element['items'] ?? [] as $item) {
        if (($item['name'] ?? null) === 'GaugePreview') {
            $customPointerPreview = $item;
            break 2;
        }
    }
}
$customPointerPreviewSvg = is_array($customPointerPreview)
    ? base64_decode(
        substr((string) ($customPointerPreview['image'] ?? ''), strlen('data:image/svg+xml;base64,')),
        true
    )
    : false;
assertGatewayGauge(
    is_string($customPointerPreviewSvg) && str_contains($customPointerPreviewSvg, 'data-pointer-shape="custom"'),
    'The Gauge form preview must render the imported custom SVG pointer.'
);
$singleGauge->SetTestProperty('SourceVariableID', 4712);
$singleGauge->ApplyChanges();
assertGatewayGauge($singleGauge->GetTestStatus() === 201, 'Non-numeric Gauge source must set status 201.');
assertGatewayGauge($singleGauge->GetTestReferences() === [4712], 'Gauge must replace its source reference.');
assertGatewayGauge($singleGauge->GetTestSummary() === '', 'Invalid Gauge configuration must clear the source summary.');
$singleGauge->SetTestProperty('SourceVariableID', 9999);
$singleGauge->ApplyChanges();
assertGatewayGauge($singleGauge->GetTestReferences() === [], 'Gauge must remove a reference to a missing source.');
assertGatewayGauge(
    !in_array(['SenderID' => 4712, 'Message' => VM_UPDATE], $singleGauge->GetTestMessages(), true),
    'Gauge must unsubscribe from the replaced source value.'
);

$invalidRange = new EChartsGaugeSingle();
$invalidRange->Create();
$invalidRange->SetTestProperty('SourceVariableID', 4711);
$invalidRange->SetTestProperty('Minimum', 100.0);
$invalidRange->SetTestProperty('Maximum', 0.0);
$invalidRange->ApplyChanges();
assertGatewayGauge($invalidRange->GetTestStatus() === 202, 'Invalid Gauge range must set status 202.');

$invalidDesign = new EChartsGaugeSingle();
$invalidDesign->Create();
$invalidDesign->SetTestProperty('SourceVariableID', 4711);
$invalidDesign->SetTestProperty('GaugePreset', 'unknown');
$invalidDesign->ApplyChanges();
assertGatewayGauge($invalidDesign->GetTestStatus() === 205, 'Unknown Gauge presets must set status 205.');

$invalidDesignScale = new EChartsGaugeSingle();
$invalidDesignScale->Create();
$invalidDesignScale->SetTestProperty('SourceVariableID', 4711);
$invalidDesignScale->SetTestProperty('RingWidthPercent', 151);
$invalidDesignScale->ApplyChanges();
assertGatewayGauge($invalidDesignScale->GetTestStatus() === 205, 'Invalid Gauge design scales must set status 205.');

$invalidPointerLength = new EChartsGaugeSingle();
$invalidPointerLength->Create();
$invalidPointerLength->SetTestProperty('SourceVariableID', 4711);
$invalidPointerLength->SetTestProperty('PointerLengthPercent', 151);
$invalidPointerLength->ApplyChanges();
assertGatewayGauge($invalidPointerLength->GetTestStatus() === 205, 'Invalid Gauge pointer length must set status 205.');

$invalidArc = new EChartsGaugeSingle();
$invalidArc->Create();
$invalidArc->SetTestProperty('SourceVariableID', 4711);
$invalidArc->SetTestProperty('GaugeArcMode', 'custom');
$invalidArc->SetTestProperty('GaugeStartPosition', 45.0);
$invalidArc->SetTestProperty('GaugeEndPosition', 45.0);
$invalidArc->ApplyChanges();
assertGatewayGauge($invalidArc->GetTestStatus() === 205, 'A zero-length custom Gauge arc must set status 205.');

$invalidPointer = new EChartsGaugeSingle();
$invalidPointer->Create();
$invalidPointer->SetTestProperty('SourceVariableID', 4711);
$invalidPointer->SetTestProperty('PointerShape', 'unknown');
$invalidPointer->ApplyChanges();
assertGatewayGauge($invalidPointer->GetTestStatus() === 205, 'Unknown Gauge pointer shapes must set status 205.');

$invalidCustomPointer = new EChartsGaugeSingle();
$invalidCustomPointer->Create();
$invalidCustomPointer->SetTestProperty('SourceVariableID', 4711);
$invalidCustomPointer->SetTestProperty('PointerShape', 'custom');
$invalidCustomPointer->SetTestProperty(
    'CustomPointerSVG',
    base64_encode('<svg viewBox="0 0 10 10"><script>alert(1)</script><path d="M0 0L1 1Z"/></svg>')
);
$invalidCustomPointer->ApplyChanges();
assertGatewayGauge($invalidCustomPointer->GetTestStatus() === 205, 'Unsafe custom SVG pointers must set status 205.');

$invalidColor = new EChartsGaugeSingle();
$invalidColor->Create();
$invalidColor->SetTestProperty('SourceVariableID', 4711);
$invalidColor->SetTestProperty('PointerColor', 0x1000000);
$invalidColor->ApplyChanges();
assertGatewayGauge($invalidColor->GetTestStatus() === 205, 'Invalid Gauge RGB colors must set status 205.');

$invalidTheme = new EChartsGaugeSingle();
$invalidTheme->Create();
$invalidTheme->SetTestProperty('SourceVariableID', 4711);
$invalidTheme->SetTestProperty('EChartsTheme', 'unknown');
$invalidTheme->ApplyChanges();
assertGatewayGauge($invalidTheme->GetTestStatus() === 206, 'Unknown ECharts themes must set status 206.');

$missingParent = new EChartsGaugeSingle();
$missingParent->Create();
$missingParent->SetTestProperty('SourceVariableID', 4711);
$missingParent->SetTestParentActive(false);
$missingParent->ApplyChanges();
assertGatewayGauge($missingParent->GetTestStatus() === 203, 'Missing active gateway must set status 203.');

$nonNumericRequest = json_encode([
    'DataID'          => '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}',
    'ProtocolVersion' => 1,
    'Operation'       => 'current.read',
    'Payload'         => ['VariableID' => 4712]
], JSON_THROW_ON_ERROR);
$nonNumericResponse = json_decode($gateway->ForwardData($nonNumericRequest), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge($nonNumericResponse['Success'] === false, 'Gateway must reject non-numeric source variables.');
assertGatewayGauge(
    ($nonNumericResponse['Error']['Code'] ?? null) === 'VARIABLE_NOT_NUMERIC',
    'Gateway returned the wrong non-numeric-variable error.'
);

echo "Gateway and Gauge module integration verified.\n";
