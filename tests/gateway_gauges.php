<?php

declare(strict_types=1);

const IPS_KERNELSTARTED = 10001;
const KR_READY = 10103;
const IS_ACTIVE = 102;
const IS_INACTIVE = 104;

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

    private int $status = IS_INACTIVE;
    private bool $parentActive = true;
    private string $summary = '';

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

    protected function RegisterMessage(int $senderID, int $message): bool
    {
        return true;
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

foreach ([EChartsGaugeSingle::class, EChartsGaugeMulti::class] as $gaugeClass) {
    $gauge = new $gaugeClass();
    $gauge->Create();
    $gauge->SetTestProperty('SourceVariableID', 4711);
    $gauge->SetTestProperty('Minimum', -20.0);
    $gauge->SetTestProperty('Maximum', 80.0);
    $gauge->SetTestProperty('Title', 'Room climate');
    $gauge->SetTestProperty('Unit', '°C');
    $gauge->SetTestProperty('Decimals', 1);
    $gauge->ApplyChanges();

    assertGatewayGauge($gauge->GetTestStatus() === IS_ACTIVE, $gaugeClass . ' must become active.');
    assertGatewayGauge($gauge->GetTestReferences() === [4711], $gaugeClass . ' must register its source reference.');
    assertGatewayGauge($gauge->GetTestSummary() === 'Living room temperature', $gaugeClass . ' summary changed.');

    $gaugeData = json_decode($gauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(($gaugeData['schemaVersion'] ?? null) === 1, $gaugeClass . ' data schema version changed.');
    assertGatewayGauge(($gaugeData['family'] ?? null) === 'gauge', $gaugeClass . ' data family changed.');
    assertGatewayGauge(($gaugeData['value'] ?? null) === 42.5, $gaugeClass . ' did not receive the source value.');
    assertGatewayGauge(($gaugeData['source']['timestamp'] ?? null) === 1780000000, $gaugeClass . ' timestamp changed.');
    assertGatewayGauge(($gaugeData['gauge']['minimum'] ?? null) === -20.0, $gaugeClass . ' minimum changed.');
    assertGatewayGauge(($gaugeData['gauge']['maximum'] ?? null) === 80.0, $gaugeClass . ' maximum changed.');
}

$singleGauge = new EChartsGaugeSingle();
$singleGauge->Create();
$singleGauge->SetTestProperty('SourceVariableID', 4711);
$singleGauge->ApplyChanges();
$singleGauge->SetTestProperty('SourceVariableID', 4712);
$singleGauge->ApplyChanges();
assertGatewayGauge($singleGauge->GetTestStatus() === 201, 'Non-numeric Gauge source must set status 201.');
assertGatewayGauge($singleGauge->GetTestReferences() === [4712], 'Gauge must replace its source reference.');
assertGatewayGauge($singleGauge->GetTestSummary() === '', 'Invalid Gauge configuration must clear the source summary.');

$invalidRange = new EChartsGaugeSingle();
$invalidRange->Create();
$invalidRange->SetTestProperty('SourceVariableID', 4711);
$invalidRange->SetTestProperty('Minimum', 100.0);
$invalidRange->SetTestProperty('Maximum', 0.0);
$invalidRange->ApplyChanges();
assertGatewayGauge($invalidRange->GetTestStatus() === 202, 'Invalid Gauge range must set status 202.');

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
