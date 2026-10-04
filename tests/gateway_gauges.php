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
assertGatewayGauge(str_contains($initialPreviewSvg, '#FEF8EF'), 'Gauge Single preview must apply the Vintage background.');
assertGatewayGauge(str_contains($initialPreviewSvg, 'Room climate'), 'Gauge Single preview must contain the title.');
assertGatewayGauge(str_contains($initialPreviewSvg, '42.5 °C'), 'Gauge Single preview must use the current source value.');

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
        '/<text x="[^"]+" y="([^"]+)" class="axis" data-label-index="25">500<\/text>/',
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
