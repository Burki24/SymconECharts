<?php

declare(strict_types=1);

use SymconECharts\EChartsDataProtocol;

const IPS_KERNELSTARTED = 10001;
const VM_UPDATE = 10603;
const KR_READY = 10103;
const IS_ACTIVE = 102;
const IS_INACTIVE = 104;
const SYMCON_OUTPUT_BUFFER_LIMIT = 1048576;
const VARIABLETYPE_STRING = 3;
const VARIABLE_PRESENTATION_WEB_CONTENT = '{6B9CAEEC-5958-C223-30F7-BD36569FC57A}';

/** @param list<array<string, mixed>> $items @return list<array<string, mixed>> */
function flattenDesignerSections(array $items): array
{
    $flat = [];
    foreach ($items as $item) {
        if (($item['type'] ?? null) === 'ExpansionPanel') {
            array_push($flat, ...flattenDesignerSections($item['items'] ?? []));
        } else {
            $flat[] = $item;
        }
    }

    return $flat;
}

/** @param list<array<string, mixed>> $items @return list<string> */
function designerControlNames(array $items): array
{
    $names = [];
    foreach ($items as $item) {
        if (isset($item['name']) && ($item['type'] ?? null) !== 'Image') {
            $names[] = $item['name'];
        }
        if (($item['type'] ?? null) !== 'List' && is_array($item['items'] ?? null)) {
            array_push($names, ...designerControlNames($item['items']));
        }
    }

    return $names;
}

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
    ],
    4715 => [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000004,
        'Value'           => 1013.25,
        'Name'            => 'Presented air pressure',
        'Presentation'    => [
            'MIN'    => 950.0,
            'MAX'    => 1050.0,
            'DIGITS' => 1,
            'SUFFIX' => ' hPa'
        ]
    ],
    4716 => [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000005,
        'Value'           => 12.34,
        'Name'            => 'Legacy wind speed',
        'Presentation'    => [
            'PROFILE' => 'Test.Wind'
        ]
    ],
    4717 => [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000006,
        'Value'           => 23.75,
        'Name'            => 'Unarchived real-time temperature'
    ],
    4720 => [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000007,
        'Value'           => 19.5,
        'Name'            => 'Additional temperature'
    ]
];
$GLOBALS['symconTestTreeObjects'] = [
    9000 => ['ObjectType' => 0, 'ObjectName' => 'Home', 'ObjectIsHidden' => false],
    9001 => ['ObjectType' => 1, 'ObjectName' => 'Sensor', 'ObjectIsHidden' => false],
    9002 => ['ObjectType' => 2, 'ObjectName' => 'Temperature', 'ObjectIsHidden' => false],
    9003 => ['ObjectType' => 2, 'ObjectName' => 'Temperature', 'ObjectIsHidden' => false],
    9004 => ['ObjectType' => 0, 'ObjectName' => 'Hidden', 'ObjectIsHidden' => true],
    9005 => ['ObjectType' => 2, 'ObjectName' => 'Private value', 'ObjectIsHidden' => false]
];
$GLOBALS['symconTestTreeChildren'] = [9000 => [9001, 9004], 9001 => [9002, 9003], 9004 => [9005]];
$GLOBALS['symconTestTreeValues'] = [9002 => '21.3 °C', 9003 => '22.4 °C', 9005 => 'secret'];
$GLOBALS['symconTestVariables'][9002] = [
    'VariableType' => 1, 'VariableUpdated' => 1780000008, 'Value' => 1, 'Name' => 'First status'
];
$GLOBALS['symconTestVariables'][9003] = [
    'VariableType' => 1, 'VariableUpdated' => 1780000008, 'Value' => 2, 'Name' => 'Second status'
];
$GLOBALS['symconTestProfiles'] = [
    'Test.Wind' => [
        'MinValue' => 0.0,
        'MaxValue' => 50.0,
        'Digits'   => 2,
        'Suffix'   => ' m/s'
    ]
];
$GLOBALS['symconTestArchiveInstances'] = [6000];
$GLOBALS['symconTestWebHookInstances'] = [6100];
$GLOBALS['symconTestWebSocketMessages'] = [];
$GLOBALS['symconTestArchiveQueryCount'] = 0;
$GLOBALS['symconTestArchiveVariables'] = [
    4711 => [
        'AggregationType' => 0,
        'LoggedValues'    => [
            ['TimeStamp' => 1780000300, 'Value' => 43.0, 'Duration' => 100],
            ['TimeStamp' => 1780000200, 'Value' => 42.0, 'Duration' => 100],
            ['TimeStamp' => 1780000100, 'Value' => 41.0, 'Duration' => 100]
        ],
        'AggregatedValues' => [
            [
                'TimeStamp' => 1780000200,
                'Avg'       => 42.5,
                'Min'       => 42.0,
                'MinTime'   => 1780000210,
                'Max'       => 43.0,
                'MaxTime'   => 1780000250,
                'Duration'  => 60
            ],
            [
                'TimeStamp' => 1780000100,
                'Avg'       => 41.5,
                'Min'       => 41.0,
                'MinTime'   => 1780000110,
                'Max'       => 42.0,
                'MaxTime'   => 1780000150,
                'Duration'  => 60
            ]
        ]
    ],
    4713 => [
        'AggregationType'  => 1,
        'LoggedValues'     => [],
        'AggregatedValues' => [
            [
                'TimeStamp' => 1780000100,
                'Avg'       => 12,
                'Min'       => 2,
                'MinTime'   => 1780000110,
                'Max'       => 7,
                'MaxTime'   => 1780000150,
                'Duration'  => 60
            ]
        ]
    ]
];

function IPS_GetKernelRunlevel(): int
{
    return KR_READY;
}

function IPS_SetProperty(int $instanceID, string $name, mixed $value): bool
{
    $target = $GLOBALS['symconTestCopyTarget'] ?? null;
    if (!$target instanceof IPSModuleStrict || $target->InstanceID !== $instanceID) {
        throw new RuntimeException('Unknown test configuration target.');
    }
    $target->SetTestProperty($name, $value);

    return true;
}

function IPS_ApplyChanges(int $instanceID): bool
{
    $target = $GLOBALS['symconTestCopyTarget'] ?? null;
    if (!$target instanceof IPSModuleStrict || $target->InstanceID !== $instanceID) {
        throw new RuntimeException('Unknown test configuration target.');
    }
    $target->ApplyChanges();

    return true;
}

function IPS_VariableExists(int $variableID): bool
{
    return isset($GLOBALS['symconTestVariables'][$variableID]);
}

/** @return list<int> */
function IPS_GetInstanceListByModuleID(string $moduleID): array
{
    if (isset($GLOBALS['symconTestModuleInstances'][$moduleID])) {
        return $GLOBALS['symconTestModuleInstances'][$moduleID];
    }
    if ($moduleID === '{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}') {
        return $GLOBALS['symconTestWebHookInstances'];
    }
    if ($moduleID !== '{43192F0B-135B-4CE7-A0A7-1475603F3060}') {
        return [];
    }

    return $GLOBALS['symconTestArchiveInstances'];
}

function WC_PushMessage(int $instanceID, string $path, string $message): void
{
    $GLOBALS['symconTestWebSocketMessages'][] = [
        'InstanceID' => $instanceID,
        'Path'       => $path,
        'Message'    => $message
    ];
}

/** @return list<array<string, mixed>> */
function AC_GetAggregationVariables(int $archiveID, bool $databaseQuery): array
{
    if (!in_array($archiveID, $GLOBALS['symconTestArchiveInstances'], true)) {
        throw new RuntimeException('Unknown test archive.');
    }

    return array_map(
        static fn (int $variableID): array => [
            'VariableID'        => $variableID,
            'AggregationType'   => $GLOBALS['symconTestArchiveVariables'][$variableID]['AggregationType'],
            'AggregationActive' => true
        ],
        array_keys($GLOBALS['symconTestArchiveVariables'])
    );
}

function AC_GetAggregationType(int $archiveID, int $variableID): int
{
    if (!in_array($archiveID, $GLOBALS['symconTestArchiveInstances'], true)
        || !isset($GLOBALS['symconTestArchiveVariables'][$variableID])
    ) {
        throw new RuntimeException('Unknown test archive variable.');
    }

    return $GLOBALS['symconTestArchiveVariables'][$variableID]['AggregationType'];
}

/** @return list<array<string, int|float>> */
function AC_GetLoggedValues(
    int $archiveID,
    int $variableID,
    int $startTimestamp,
    int $endTimestamp,
    int $limit
): array {
    ++$GLOBALS['symconTestArchiveQueryCount'];
    $values = $GLOBALS['symconTestArchiveVariables'][$variableID]['LoggedValues'] ?? [];
    $values = array_values(array_filter(
        $values,
        static fn (array $value): bool => $value['TimeStamp'] >= $startTimestamp
            && $value['TimeStamp'] <= $endTimestamp
    ));

    return array_slice($values, 0, $limit);
}

/** @return list<array<string, int|float>> */
function AC_GetAggregatedValues(
    int $archiveID,
    int $variableID,
    int $aggregationLevel,
    int $startTimestamp,
    int $endTimestamp,
    int $limit
): array {
    ++$GLOBALS['symconTestArchiveQueryCount'];
    $values = $GLOBALS['symconTestArchiveVariables'][$variableID]['AggregatedValues'] ?? [];
    $values = array_values(array_filter(
        $values,
        static fn (array $value): bool => $value['TimeStamp'] >= $startTimestamp
            && $value['TimeStamp'] <= $endTimestamp
    ));

    return array_slice($values, 0, $limit);
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

/** @return array<string, mixed> */
function IPS_GetVariablePresentation(int $variableID): array
{
    if ($GLOBALS['symconTestPresentationFailures'][$variableID] ?? false) {
        throw new RuntimeException('Synthetic unavailable presentation.');
    }
    return $GLOBALS['symconTestVariables'][$variableID]['Presentation'] ?? [];
}

/** @return array<string, mixed> */
function IPS_GetVariableProfile(string $profileName): array
{
    if (!isset($GLOBALS['symconTestProfiles'][$profileName])) {
        throw new RuntimeException('Unknown test variable profile.');
    }

    return $GLOBALS['symconTestProfiles'][$profileName];
}

function IPS_GetName(int $objectID): string
{
    return (string) $GLOBALS['symconTestVariables'][$objectID]['Name'];
}

/** @return array<string, bool> */
function IPS_GetObject(int $objectID): array
{
    if ($GLOBALS['symconTestLegacyObject'] ?? false) {
        return [];
    }

    if (isset($GLOBALS['symconTestTreeObjects'][$objectID])) {
        return $GLOBALS['symconTestTreeObjects'][$objectID];
    }

    return ['ObjectIsHiddenTitle' => $GLOBALS['symconTestHiddenTitles'][$objectID] ?? false];
}

function IPS_ObjectExists(int $objectID): bool
{
    return isset($GLOBALS['symconTestTreeObjects'][$objectID]);
}

/** @return list<int> */
function IPS_GetChildrenIDs(int $objectID): array
{
    return $GLOBALS['symconTestTreeChildren'][$objectID] ?? [];
}

function GetValueFormatted(int $variableID): string
{
    return $GLOBALS['symconTestTreeValues'][$variableID] ?? '';
}

function GetValue(int $variableID): mixed
{
    return $GLOBALS['symconTestVariables'][$variableID]['Value'];
}

abstract class IPSModuleStrict
{
    public int $InstanceID = 5000;

    public array $DebugMessages = [];
    public bool $FailDebug = false;
    public bool $CaptureDebug = false;

    /** @var null|callable(string): string */
    public static $ParentResponder = null;

    /** @var list<callable(string): string> */
    public static array $ChildResponders = [];

    /** @var array<string, mixed> */
    private array $properties = [];

    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var array<string, string> */
    private array $buffers = [];

    /** @var list<int> */
    private array $references = [];

    /** @var list<array{SenderID:int, Message:int}> */
    private array $messages = [];

    /** @var list<mixed> */
    private array $visualizationUpdates = [];

    /** @var list<array{Field:string, Parameter:string, Value:mixed}> */
    private array $formUpdates = [];

    /** @var array<string,int> */
    private array $timers = [];

    private int $status = IS_INACTIVE;
    private bool $parentActive = true;
    private string $summary = '';
    private int $visualizationType = 0;
    /** @var list<string> */
    private array $hooks = [];
    /** @var array<string, int> */
    private array $variableIDs = [];
    private static int $nextVariableID = 9000;

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

    public function GetTestProperties(): array
    {
        return $this->properties;
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

    /** @return list<string> */
    public function GetTestHooks(): array
    {
        return $this->hooks;
    }

    public function GetTestVariableValue(string $ident): mixed
    {
        $variableID = $this->variableIDs[$ident] ?? 0;

        return $GLOBALS['symconTestVariables'][$variableID]['Value'] ?? null;
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

    public function GetTestBuffer(string $name): string
    {
        return $this->buffers[$name] ?? '';
    }

    public function GetTestTimerInterval(string $ident): ?int
    {
        return $this->timers[$ident] ?? null;
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

    protected function RegisterPropertyBoolean(string $name, bool $default): bool
    {
        $this->properties[$name] ??= $default;

        return true;
    }

    protected function MaintainVariable(
        string $ident,
        string $name,
        int $type,
        array $presentation,
        int $position,
        bool $keep
    ): bool {
        if (isset($this->variableIDs[$ident])) {
            return false;
        }
        $variableID = ++self::$nextVariableID;
        $this->variableIDs[$ident] = $variableID;
        $GLOBALS['symconTestVariables'][$variableID] = [
            'VariableType'    => $type,
            'VariableUpdated' => 1780000100,
            'Value'           => '',
            'Name'            => $name,
            'Presentation'    => $presentation,
            'Position'        => $position,
            'Keep'            => $keep
        ];

        return true;
    }

    protected function SetValue(string $ident, mixed $value): bool
    {
        $variableID = $this->GetIDForIdent($ident);
        $GLOBALS['symconTestVariables'][$variableID]['Value'] = $value;

        return true;
    }

    protected function GetIDForIdent(string $ident): int
    {
        if (!isset($this->variableIDs[$ident])) {
            throw new RuntimeException('Unknown ident.');
        }

        return $this->variableIDs[$ident];
    }

    protected function VariableExists(string $ident): bool
    {
        return isset($this->variableIDs[$ident]);
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

    protected function ReadPropertyBoolean(string $name): bool
    {
        return (bool) $this->properties[$name];
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

    protected function GetBuffer(string $name): string
    {
        return $this->buffers[$name] ?? '';
    }

    protected function SetBuffer(string $name, string $value): void
    {
        $this->buffers[$name] = $value;
    }

    protected function RegisterMessage(int $senderID, int $message): bool
    {
        $registration = ['SenderID' => $senderID, 'Message' => $message];
        if (!in_array($registration, $this->messages, true)) {
            $this->messages[] = $registration;
        }

        return true;
    }

    protected function RegisterTimer(string $ident, int $interval, string $script): bool
    {
        $this->timers[$ident] = $interval;

        return true;
    }

    protected function SetTimerInterval(string $ident, int $interval): bool
    {
        $this->timers[$ident] = $interval;

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

    /** @return list<string> */
    protected function SendDataToChildren(string $data): array
    {
        return array_map(
            static fn (callable $responder): string => $responder($data),
            self::$ChildResponders
        );
    }

    protected function RegisterHook(string $hook): void
    {
        if (!in_array($hook, $this->hooks, true)) {
            $this->hooks[] = $hook;
        }
    }

    protected function SendDebug(string $message, string $data, int $format): bool
    {
        if ($this->FailDebug) {
            throw new RuntimeException('Synthetic debug failure.');
        }
        if ($this->CaptureDebug && str_contains($message, ' | ')) {
            $this->DebugMessages[] = [$message, json_decode($data, true, 512, JSON_THROW_ON_ERROR)];
        }
        return true;
    }
}

require_once dirname(__DIR__) . '/EChartsGateway/module.php';
require_once dirname(__DIR__) . '/EChartsGaugeSingle/module.php';
require_once dirname(__DIR__) . '/EChartsGaugeMulti/module.php';
require_once dirname(__DIR__) . '/EChartsGaugeTacho/module.php';
require_once dirname(__DIR__) . '/EChartsGaugeChronograph/module.php';
require_once dirname(__DIR__) . '/EChartsTimeSeries/module.php';
require_once dirname(__DIR__) . '/EChartsBarCategory/module.php';
require_once dirname(__DIR__) . '/EChartsBarHistory/module.php';
require_once dirname(__DIR__) . '/EChartsBarWaterfall/module.php';
require_once dirname(__DIR__) . '/EChartsBarPolar/module.php';
require_once dirname(__DIR__) . '/EChartsPie/module.php';
require_once dirname(__DIR__) . '/EChartsScatter/module.php';
require_once dirname(__DIR__) . '/EChartsHeatmap/module.php';
require_once dirname(__DIR__) . '/EChartsTree/module.php';
require_once dirname(__DIR__) . '/EChartsTreeSystemHealth/module.php';
require_once dirname(__DIR__) . '/EChartsGraph/module.php';
require_once dirname(__DIR__) . '/EChartsGraphZ2M/module.php';
require_once dirname(__DIR__) . '/EChartsTreemap/module.php';
require_once dirname(__DIR__) . '/EChartsSunburst/module.php';
require_once dirname(__DIR__) . '/EChartsParallel/module.php';
require_once dirname(__DIR__) . '/EChartsSankey/module.php';

final class TestableEChartsGateway extends EChartsGateway
{
    public function ProcessTestHook(): string
    {
        ob_start();
        $this->ProcessHookData();

        return (string) ob_get_clean();
    }
}

final class TestableEChartsTimeSeries extends EChartsTimeSeries
{
    private int $currentTimestamp = 1;

    public function SetTestCurrentTimestamp(int $timestamp): void
    {
        $this->currentTimestamp = $timestamp;
    }

    protected function CurrentTimestamp(): int
    {
        return $this->currentTimestamp;
    }

    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/EChartsTimeSeries/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsTimeSeries/visualization/' . $filename);
    }
}

final class TestableEChartsBarHistory extends EChartsBarHistory
{
    private int $currentTimestamp = 1;

    public function SetTestCurrentTimestamp(int $timestamp): void
    {
        $this->currentTimestamp = $timestamp;
    }

    protected function CurrentTimestamp(): int
    {
        return $this->currentTimestamp;
    }

    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/EChartsBarHistory/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsBarHistory/visualization/' . $filename);
    }
}

final class TestableEChartsScatter extends EChartsScatter
{
    private int $currentTimestamp = 1;

    public function SetTestCurrentTimestamp(int $timestamp): void
    {
        $this->currentTimestamp = $timestamp;
    }

    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/EChartsScatter/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsScatter/visualization/' . $filename);
    }

    protected function CurrentTimestamp(): int
    {
        return $this->currentTimestamp;
    }
}

final class TestableEChartsParallel extends EChartsParallel
{
    private int $currentTimestamp = 1;

    public function SetTestCurrentTimestamp(int $timestamp): void
    {
        $this->currentTimestamp = $timestamp;
    }

    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/EChartsParallel/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsParallel/visualization/' . $filename);
    }

    protected function CurrentTimestamp(): int
    {
        return $this->currentTimestamp;
    }
}

final class TestableEChartsHeatmap extends EChartsHeatmap
{
    private int $currentTimestamp = 1;

    public function SetTestCurrentTimestamp(int $timestamp): void
    {
        $this->currentTimestamp = $timestamp;
    }

    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsHeatmap/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsHeatmap/visualization/' . $filename);
    }

    protected function CurrentTimestamp(): int
    {
        return $this->currentTimestamp;
    }
}

function assertGatewayGauge(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertTransientRetry(IPSModuleStrict $module, string $name): void
{
    $responder = IPSModuleStrict::$ParentResponder;
    try {
        IPSModuleStrict::$ParentResponder = static fn (string $json): string => '';
        $module->RetryTransient();
        $updates = $module->GetTestVisualizationUpdates();
        $failedState = json_decode((string) end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            ($failedState['status'] ?? null) === 'error'
                && $module->GetTestTimerInterval('TransientRetry') === 60000,
            $name . ' must retry a temporary data-read failure after one minute.'
        );
    } finally {
        IPSModuleStrict::$ParentResponder = $responder;
    }

    $module->RetryTransient();
    $updates = $module->GetTestVisualizationUpdates();
    $readyState = json_decode((string) end($updates), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        ($readyState['status'] ?? null) === 'ready'
            && $module->GetTestTimerInterval('TransientRetry') === 0,
        $name . ' must stop retrying as soon as fresh data is available.'
    );
}

/** @param list<array<string, mixed>> $items @return array<string, mixed>|null */
function findGaugeFormElement(array $items, string $name): ?array
{
    foreach ($items as $item) {
        if (($item['name'] ?? null) === $name) {
            return $item;
        }
        if (is_array($item['items'] ?? null)) {
            $found = findGaugeFormElement($item['items'], $name);
            if ($found !== null) {
                return $found;
            }
        }
    }

    return null;
}

$interactionTemplates = glob(dirname(__DIR__) . '/ECharts*/visualization/index.html');
assertGatewayGauge(count($interactionTemplates) === 29, 'Every chart module must participate in shared interaction delivery.');
foreach ($interactionTemplates as $interactionTemplate) {
    $interactionHTML = file_get_contents($interactionTemplate);
    $interactionOffset = strpos($interactionHTML, '{{INTERACTION_SCRIPT}}');
    $moduleOffset = strpos($interactionHTML, '{{MODULE_SCRIPT}}');
    assertGatewayGauge($interactionOffset !== false && $moduleOffset !== false && $interactionOffset < $moduleOffset, 'Shared interaction must load before chart startup in every template.');
}
$assetGateway = new TestableEChartsGateway();
$assetGateway->Create();
$assetServer = $_SERVER;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/hook/SymconECharts/assets/interaction.js';
assertGatewayGauge(
    $assetGateway->ProcessTestHook() === file_get_contents(dirname(__DIR__) . '/libs/helper/visualization/interaction.min.js') && http_response_code() === 200,
    'The public interaction endpoint must serve exactly the bundled shared library.'
);
foreach ([['POST', '/hook/SymconECharts/assets/interaction.js'], ['GET', '/hook/SymconECharts/assets/interaction.js/extra'], ['GET', '/hook/SymconECharts/assets/../interaction.js']] as [$assetMethod, $assetPath]) {
    $_SERVER['REQUEST_METHOD'] = $assetMethod;
    $_SERVER['SCRIPT_NAME'] = $assetPath;
    assertGatewayGauge($assetGateway->ProcessTestHook() === '' && http_response_code() === 404, 'The interaction endpoint must reject other methods and paths.');
}
$_SERVER = $assetServer;
http_response_code(200);

$gateway = new EChartsGateway();
$gateway->Create();
$gateway->ApplyChanges();
assertGatewayGauge(
    $gateway->GetTestHooks() === ['SymconECharts'],
    'The Gateway must own the shared SymconECharts WebHook.'
);
IPSModuleStrict::$ParentResponder = static fn (string $json): string => $gateway->ForwardData($json);

if (array_slice($argv, 1) === ['--scatter-groups']) {
    require __DIR__ . '/scatter_groups.php';
    echo "Named Scatter/Bubble groups, per-group joins, shared budgets and compatibility verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--scatter-bubble-range']) {
    require __DIR__ . '/scatter_bubble_range.php';
    echo "Scatter fixed Bubble ranges, compatibility, previews and unchanged archive budgets verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--bar-history-many']) {
    require __DIR__ . '/bar_history_many.php';
    echo "BarHistory 32-source integration verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--time-series-many']) {
    require __DIR__ . '/time_series_many.php';
    echo "TimeSeries 32-source integration verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--graph-directed']) {
    require __DIR__ . '/graph_directed.php';
    echo "Directed Graph integration verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--graph-large']) {
    require __DIR__ . '/graph_large.php';
    echo "Large Graph integration verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--tree-depth']) {
    require __DIR__ . '/tree_depth.php';
    echo "Twelve-level Tree integration verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--tree-health-depth']) {
    require __DIR__ . '/tree_health_depth.php';
    echo "Twelve-level Tree System Health integration verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--hierarchy-normalization']) {
    require __DIR__ . '/hierarchy_normalization.php';
    exit(0);
}

if (array_slice($argv, 1) === ['--boxplot-iqr']) {
    require __DIR__ . '/boxplot_integration.php';
    require __DIR__ . '/boxplot_iqr.php';
    exit(0);
}

if (array_slice($argv, 1) === ['--treemap-hierarchy']) {
    require __DIR__ . '/treemap_hierarchy.php';
    echo "Treemap deep hierarchy integration verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--sunburst-hierarchy']) {
    require __DIR__ . '/sunburst_hierarchy.php';
    echo "Sunburst deep hierarchy integration verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--current-normalization']) {
    require __DIR__ . '/pie_polar_normalization.php';
    require __DIR__ . '/bar_category_normalization.php';
    require __DIR__ . '/pictorial_bar_integration.php';
    echo "Normalized current comparison families verified.\n";
    exit(0);
}

if (array_slice($argv, 1) === ['--bar-category-normalization']) {
    require __DIR__ . '/bar_category_normalization.php';
    exit(0);
}

if (array_slice($argv, 1) === ['--heatmap-periods']) {
    require __DIR__ . '/heatmap_periods.php';
    echo "Heatmap custom/fixed periods, metrics, navigation, drafts and unchanged archive budgets verified.\n";
    exit(0);
}
if (($argv[1] ?? '') === '--calendar-pie-preview') {
    require_once dirname(__DIR__) . '/EChartsCalendarPie/module.php';
    $GLOBALS['symconTestVariables'][4800] = ['VariableType' => 2, 'VariableUpdated' => 1780000000, 'Value' => 8.0, 'Name' => 'Quelle A'];
    $GLOBALS['symconTestVariables'][4801] = ['VariableType' => 2, 'VariableUpdated' => 1780000000, 'Value' => 3.0, 'Name' => 'Quelle B'];
    $GLOBALS['symconTestVariables'][4802] = ['VariableType' => 0, 'VariableUpdated' => 1780000000, 'Value' => true, 'Name' => 'Status'];
    foreach ([4803, 4804, 4805] as $id) {
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000, 'Value' => 5.0, 'Name' => 'Quelle ' . $id];
    }
    $calendarPiePreview = new EChartsCalendarPie();
    $calendarPiePreview->Create();
    $calendarPiePreview->UpdateBarPreviewFromForm($argv[2] ?? '{}');
    $previewUpdate = array_slice($calendarPiePreview->GetTestFormUpdates(), -2)[($argv[3] ?? '') === 'ipsview' ? 1 : 0];
    echo base64_decode(substr($previewUpdate['Value'], strlen('data:image/svg+xml;base64,')));
    exit(0);
}
if (array_slice($argv, 1) === ['--calendar-pie']) {
    require __DIR__ . '/calendar_pie_integration.php';
    exit(0);
}

if (($argv[1] ?? '') === '--bar-category-preview') {
    $categoryPreview = new EChartsBarCategory();
    $categoryPreview->Create();
    $draft = json_decode($argv[2] ?? '{}', true, 64, JSON_THROW_ON_ERROR);
    $draft['Sources'] = [];
    for ($index = 0; $index < 72; ++$index) {
        $id = 88500 + $index;
        $GLOBALS['symconTestVariables'][$id] = [...$GLOBALS['symconTestVariables'][4711],
            'Name' => 'Battery ' . ($index + 1), 'Value' => (float) ($index % 101)];
        $draft['Sources'][] = ['VariableID' => $id, 'Category' => 'Battery ' . ($index + 1),
            'UseVariablePresentation'       => false, 'Unit' => '%', 'Decimals' => 0];
    }
    $categoryPreview->UpdateBarPreviewFromForm(json_encode($draft, JSON_THROW_ON_ERROR));
    $previewUpdate = array_slice($categoryPreview->GetTestFormUpdates(), -2)[($argv[3] ?? '') === 'ipsview' ? 1 : 0];
    echo base64_decode(substr($previewUpdate['Value'], strlen('data:image/svg+xml;base64,')));
    exit(0);
}

if (($argv[1] ?? '') === '--state-timeline-preview') {
    require_once dirname(__DIR__) . '/EChartsStateTimeline/module.php';
    $GLOBALS['symconTestVariables'][4800] = ['VariableType' => 0, 'VariableUpdated' => 1780000000, 'Value' => false, 'Name' => 'Fenster'];
    $GLOBALS['symconTestVariables'][4801] = ['VariableType' => 1, 'VariableUpdated' => 1780000000, 'Value' => 0, 'Name' => 'Pumpe'];
    $timelinePreview = new EChartsStateTimeline();
    $timelinePreview->Create();
    $timelinePreview->UpdateBarPreviewFromForm($argv[2] ?? '{}');
    $previewUpdate = array_slice($timelinePreview->GetTestFormUpdates(), -2)[($argv[3] ?? '') === 'ipsview' ? 1 : 0];
    echo base64_decode(substr($previewUpdate['Value'], strlen('data:image/svg+xml;base64,')));
    exit(0);
}

if (array_slice($argv, 1) === ['--archive-states']) {
    require __DIR__ . '/archive_states_gateway.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--state-timeline-absolute']) {
    require __DIR__ . '/state_timeline_integration.php';
    require __DIR__ . '/state_timeline_absolute_ranges.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--state-timeline']) {
    require __DIR__ . '/state_timeline_integration.php';
    exit(0);
}

// Reuse the real Gateway and Kernel doubles without retaining every previous family's HTML fixtures.
if (($argv[1] ?? '') === '--sankey-balance') {
    require __DIR__ . '/sankey_balance_integration.php';
    exit(0);
}

if (($argv[1] ?? '') === '--sankey-reversal') {
    require __DIR__ . '/sankey_reversal_integration.php';
    exit(0);
}

if (($argv[1] ?? '') === '--sankey-units') {
    require __DIR__ . '/sankey_unit_integration.php';
    exit(0);
}

if (array_slice($argv, 1) === ['--zigbee-link-selection']) {
    require __DIR__ . '/zigbee_link_selection.php';
    exit(0);
}

if (($argv[1] ?? '') === '--theme-river-preview') {
    require_once dirname(__DIR__) . '/EChartsThemeRiver/module.php';
    $riverPreview = new EChartsThemeRiver();
    $riverPreview->Create();
    $riverPreview->UpdateBarPreviewFromForm($argv[2] ?? '{}');
    $previewUpdate = array_slice($riverPreview->GetTestFormUpdates(), -2)[0];
    echo base64_decode(substr($previewUpdate['Value'], strlen('data:image/svg+xml;base64,')));
    exit(0);
}
if (array_slice($argv, 1) === ['--pie-many']) {
    require __DIR__ . '/pie_many.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--polar-many']) {
    require __DIR__ . '/bar_polar_many.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--gauge-multi-many']) {
    require __DIR__ . '/gauge_multi_many.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--candlestick-absolute']) {
    require __DIR__ . '/candlestick_integration.php';
    require __DIR__ . '/candlestick_absolute_ranges.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--candlestick-intervals']) {
    require __DIR__ . '/candlestick_integration.php';
    require __DIR__ . '/candlestick_extended_intervals.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--parallel-absolute']) {
    require __DIR__ . '/parallel_absolute_ranges.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--parallel-many']) {
    require __DIR__ . '/parallel_many.php';
    echo "Parallel sixteen-axis limits, common intervals, drafts and output budgets verified.\n";
    exit(0);
}
if (array_slice($argv, 1) === ['--parallel-metrics']) {
    require __DIR__ . '/parallel_metrics.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--parallel-axis-ranges']) {
    require __DIR__ . '/parallel_axis_ranges.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--boxplot-absolute']) {
    require __DIR__ . '/boxplot_absolute_ranges.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--radar-many']) {
    require __DIR__ . '/radar_many.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--funnel-many']) {
    require __DIR__ . '/funnel_many.php';
    exit(0);
}
if (($argv[1] ?? '') === '--pie-preview') {
    $draft = json_decode($argv[2] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
    $draft['EnableSourceSelection'] ??= true;
    if (!isset($draft['Sources'])) {
        $draft['Sources'] = [];
        for ($index = 0; $index < 100; ++$index) {
            $id = 99000 + $index;
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'Value' => 1.0,
                'Name'                                             => 'Quelle ' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)];
            $draft['Sources'][] = ['VariableID' => $id, 'InitiallyVisible' => $index < 10,
                'Label'                         => '', 'UseVariablePresentation' => false, 'Unit' => 'W', 'Decimals' => 0];
        }
    }
    $preview = new EChartsPie();
    $preview->Create();
    $preview->UpdateBarPreviewFromForm(json_encode($draft, JSON_THROW_ON_ERROR));
    $previewUpdate = array_slice($preview->GetTestFormUpdates(), -2)[0];
    echo base64_decode(substr($previewUpdate['Value'], strlen('data:image/svg+xml;base64,')));
    exit(0);
}
if (($argv[1] ?? '') === '--waterfall-sections') {
    require __DIR__ . '/waterfall_sections.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--waterfall-many']) {
    require __DIR__ . '/bar_waterfall_many.php';
    exit(0);
}
if (($argv[1] ?? '') === '--waterfall-preview') {
    $draft = json_decode($argv[2] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
    if (!isset($draft['Sources'])) {
        $draft['Sources'] = [];
        for ($index = 0; $index < 100; ++$index) {
            $id = 99000 + $index;
            $value = match ($index) {
                0       => 50.0, 1 => 950.0, 2 => -2000.0, 3 => 1000.0,
                default => $index % 2 ? -2.0 : 3.0
            };
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'Value' => $value,
                'Name'                                             => $index === 0 ? 'Start' : 'Änderung ' . str_pad((string) $index, 2, '0', STR_PAD_LEFT)];
            $draft['Sources'][] = ['VariableID' => $id, 'Label' => '', 'UseVariablePresentation' => false, 'Unit' => '', 'Decimals' => 0];
        }
    }
    $waterfallPreview = new EChartsBarWaterfall();
    $waterfallPreview->Create();
    $waterfallPreview->UpdateBarPreviewFromForm(json_encode($draft, JSON_THROW_ON_ERROR));
    $previewUpdate = array_slice($waterfallPreview->GetTestFormUpdates(), -2)[0];
    echo base64_decode(substr($previewUpdate['Value'], strlen('data:image/svg+xml;base64,')));
    exit(0);
}
if (array_slice($argv, 1) === ['--theme-river-absolute']) {
    require __DIR__ . '/theme_river_consumption.php';
    require __DIR__ . '/theme_river_absolute_ranges.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--theme-river-consumption']) {
    require __DIR__ . '/theme_river_consumption.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--theme-river-many']) {
    require __DIR__ . '/theme_river_many.php';
    exit(0);
}
if (array_slice($argv, 1) === ['--theme-river']) {
    require __DIR__ . '/theme_river_integration.php';
    exit(0);
}

$gauge = new EChartsGaugeSingle();
$gauge->Create();
$gauge->SetTestProperty('SourceVariableID', 4711);
$gauge->SetTestProperty('Minimum', -20.0);
$gauge->SetTestProperty('Maximum', 80.0);
$gauge->SetTestProperty('Title', 'Room climate');
$gauge->SetTestProperty('TitlePosition', 'top');
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
$gauge->SetTestProperty('AnchorSizePercent', 125);
$gauge->SetTestProperty('AnchorBorderWidthPercent', 75);
$gauge->SetTestProperty('PlateSizePercent', 125);
$gauge->SetTestProperty('PlateBorderWidthPercent', 75);
$gauge->SetTestProperty('MinorTickLengthPercent', 75);
$gauge->SetTestProperty('MajorTickLengthPercent', 150);
$gauge->SetTestProperty('PointerShape', 'arrow');
$gauge->SetTestProperty('AnchorShape', 'ring');
$gauge->SetTestProperty('AnchorColorMode', 'custom');
$gauge->SetTestProperty('AnchorColor', 0x778899);
$gauge->SetTestProperty('AnchorBorderColor', 0x8899AA);
$gauge->SetTestProperty('PlateShape', 'arc');
$gauge->SetTestProperty('PlateColorMode', 'custom');
$gauge->SetTestProperty('PlateFillMode', 'radial');
$gauge->SetTestProperty('PlateGradientMiddleEnabled', true);
$gauge->SetTestProperty('PlateGradientMiddleColor', 0x405060);
$gauge->SetTestProperty('PlateGradientEndColor', 0x607080);
$gauge->SetTestProperty('PlateGradientDirection', 'diagonal-down');
$gauge->SetTestProperty('PlateGradientCenterXPercent', 40);
$gauge->SetTestProperty('PlateGradientCenterYPercent', 35);
$gauge->SetTestProperty('PlateGradientRadiusPercent', 85);
$gauge->SetTestProperty('PlateShadow', true);
$gauge->SetTestProperty('PlateColor', 0x202830);
$gauge->SetTestProperty('PlateBorderColor', 0x90A0B0);
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
$gauge->SetTestProperty('ScaleZonesEnabled', true);
$gauge->SetTestProperty('ScaleZones', '[{"EndValue":0,"Color":65280},{"EndValue":50,"Color":16776960}]');
$gauge->SetTestProperty('MajorSplitCount', 8);
$gauge->SetTestProperty('MinorSplitCount', 4);
$gauge->SetTestProperty('ScaleLabelRotation', 'radial');
$gauge->SetTestProperty('GaugeDirection', 'counterclockwise');
$gauge->SetTestProperty('GaugeRadiusPercent', 110);
$gauge->SetTestProperty('GaugeOffsetXPercent', 5);
$gauge->SetTestProperty('GaugeOffsetYPercent', -5);
$gauge->SetTestProperty('ValueOffsetXPercent', 10);
$gauge->SetTestProperty('ValueOffsetYPercent', -10);
$gauge->SetTestProperty('TitleOffsetXPercent', -10);
$gauge->SetTestProperty('TitleOffsetYPercent', 10);
$gauge->SetTestProperty('PointerVisibility', 'show');
$gauge->SetTestProperty('ProgressVisibility', 'hide');
$gauge->SetTestProperty('DetailBoxVisibility', 'show');
$gauge->SetTestProperty('DetailColorMode', 'custom');
$gauge->SetTestProperty('DetailBackgroundColor', 0x101820);
$gauge->SetTestProperty('DetailBorderColor', 0x708090);
$gauge->SetTestProperty('PointerShadow', true);
$gauge->SetTestProperty('EnableIPSView', true);
$gauge->ApplyChanges();
assertTransientRetry($gauge, 'Gauge Single');

assertGatewayGauge($gauge->GetTestStatus() === IS_ACTIVE, 'Gauge Single must become active.');
assertGatewayGauge($gauge->GetTestReferences() === [4711], 'Gauge Single must register its source reference.');
assertGatewayGauge($gauge->GetTestSummary() === 'Living room temperature', 'Gauge Single summary changed.');
assertGatewayGauge($gauge->GetTestVisualizationType() === 1, 'Gauge Single must expose an HTML-SDK tile.');
assertGatewayGauge(
    in_array(['SenderID' => 4711, 'Message' => VM_UPDATE], $gauge->GetTestMessages(), true),
    'Gauge Single must subscribe to source value updates.'
);
assertGatewayGauge(
    is_string($gauge->GetTestVariableValue('IPSViewGauge'))
        && str_contains($gauge->GetTestVariableValue('IPSViewGauge'), '"mode":"ipsview"'),
    'Enabled IPSView output must maintain and populate its WebContent variable.'
);

$visualizationTile = $gauge->GetVisualizationTile();
assertGatewayGauge(str_contains($visualizationTile, 'window.echarts'), 'Gauge Single tile must embed Apache ECharts.');
assertGatewayGauge(str_contains($visualizationTile, 'echarts-gauge-chart'), 'Gauge Single tile root is missing.');
assertGatewayGauge(str_contains($visualizationTile, '"echartsVersion":"6.1.0"'), 'Gauge Single ECharts version changed.');
assertGatewayGauge(str_contains($visualizationTile, '"preset":"progress"'), 'Gauge Single tile must receive the selected preset.');
assertGatewayGauge(str_contains($visualizationTile, '"theme":"vintage"'), 'Gauge Single tile must receive the selected theme.');
assertGatewayGauge(str_contains($visualizationTile, '"echartsThemes":'), 'Gauge Single tile must receive the shared theme palettes.');
assertGatewayGauge(
    str_contains($visualizationTile, '"tileHeaderVisible":true')
        && str_contains($visualizationTile, '"tileHeaderInset":58'),
    'Gauge Single must reserve space below a visible Symcon tile header.'
);
$GLOBALS['symconTestHiddenTitles'][$gauge->InstanceID] = true;
assertGatewayGauge(
    str_contains($gauge->GetVisualizationTile(), '"tileHeaderVisible":false')
        && str_contains($gauge->GetVisualizationTile(), '"tileHeaderInset":0')
        && str_contains($gauge->GetIPSViewHTML(), '"tileHeaderVisible":false')
        && str_contains($gauge->GetIPSViewHTML(), '"tileHeaderInset":0'),
    'A hidden Symcon header must release Gauge Single tile space without changing IPSView.'
);
unset($GLOBALS['symconTestHiddenTitles'][$gauge->InstanceID]);
assertGatewayGauge(str_contains($visualizationTile, "registerTheme('vintage'"), 'Gauge Single tile must register official ECharts themes.');
assertGatewayGauge(
    str_contains(
        $visualizationTile,
        "bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true"
    )
        && str_contains($visualizationTile, "? 'transparent'")
        && str_contains($visualizationTile, ': colors.background'),
    'Gauges must keep a readable theme background unless IPSView background adaptation is enabled.'
);
assertGatewayGauge(
    str_contains($visualizationTile, 'background: var(--symc-background);'),
    'Gauge Single must paint the Symcon card background while the automatic theme is active.'
);
assertGatewayGauge(
    str_contains($visualizationTile, "background: resolveColor('--symc-background', '#333438')"),
    'The automatic Gauge theme must resolve its canvas background from the Symcon design tokens.'
);
assertGatewayGauge(str_contains($visualizationTile, "case 'speed':"), 'Gauge Single tile must render the Speed preset.');

$inheritedIPSViewHTML = $gauge->GetIPSViewHTML();
assertGatewayGauge(
    str_contains($inheritedIPSViewHTML, '"mode":"ipsview"')
        && str_contains($inheritedIPSViewHTML, '"preset":"progress"')
        && str_contains($inheritedIPSViewHTML, '"theme":"vintage"'),
    'IPSView must inherit the Tile design by default.'
);
assertGatewayGauge(
    strlen($inheritedIPSViewHTML) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Gauge Single IPSView HTML must remain below the Symcon output-buffer limit.'
);
$gauge->SetTestProperty('IPSViewUseTileDesign', false);
$gauge->SetTestProperty('IPSViewGaugePreset', 'speed');
$gauge->SetTestProperty('IPSViewEChartsTheme', 'roma');
$gauge->SetTestProperty('IPSViewPointerColor', 0xA04020);
$gauge->SetTestProperty('IPSViewAdaptToBackground', true);
$gauge->SetTestProperty('IPSViewBackgroundColor', 0x6B4423);
$gauge->SetTestProperty('IPSViewBackgroundOpacityPercent', 35);
$independentIPSViewHTML = $gauge->GetIPSViewHTML();
assertGatewayGauge(
    str_contains($independentIPSViewHTML, '"preset":"speed"')
        && str_contains($independentIPSViewHTML, '"theme":"roma"')
        && str_contains($independentIPSViewHTML, '"pointerColor":"#A04020"')
        && str_contains($independentIPSViewHTML, '"adaptToBackground":true')
        && str_contains($independentIPSViewHTML, 'html, body { background:transparent; }')
        && str_contains(
            $independentIPSViewHTML,
            '#echarts-gauge-root { background:color-mix(in srgb, #6B4423 35%, transparent); }'
        ),
    'IPSView must render its independent design without changing the Tile design.'
);
assertGatewayGauge(
    str_contains($gauge->GetVisualizationTile(), '"preset":"progress"')
        && str_contains($gauge->GetVisualizationTile(), '"theme":"vintage"'),
    'An independent IPSView design must not alter the Tile design.'
);
$gauge->SetTestProperty('IPSViewUseTileDesign', true);
// Tree models project selected Symcon object subtrees without merging equal names.
$tree = new EChartsTree();
$tree->Create();
$tree->SetTestProperty('RootObjectID', 9000);
$tree->SetTestProperty('Title', 'Home tree');
$tree->SetTestProperty('IPSViewUseTileDesign', false);
$tree->SetTestProperty('IPSViewTreeLayout', 'radial');
$tree->ApplyChanges();
$treeData = json_decode($tree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $tree->GetTestStatus() === IS_ACTIVE
        && $treeData['family'] === 'tree'
        && $treeData['tree']['root']['children'][0]['children'][0]['id'] === 'object-9002'
        && $treeData['tree']['root']['children'][0]['children'][1]['id'] === 'object-9003'
        && $treeData['tree']['count'] === 4
        && $treeData['tree']['truncated'] === false,
    'Tree must retain same-name variables by object ID and skip hidden branches.'
);
$treeTile = $tree->GetVisualizationTile();
$treeIPSView = $tree->GetIPSViewHTML();
$treeForm = json_decode($tree->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    !array_key_exists('health', $treeData['tree']['root'])
        && findGaugeFormElement($treeForm['elements'], 'StatusRules') === null
        && findGaugeFormElement($treeForm['elements'], 'ShowOnlyRuleVariables') === null
        && !array_key_exists('healthyColor', $treeData['tree']['style']),
    'Simple Tree must not expose status rules or health colors.'
);
assertGatewayGauge(
    str_contains($treeTile, 'echarts-tree-root')
        && str_contains($treeTile, '"treeLayout":"orthogonal"')
        && str_contains($treeIPSView, '"treeLayout":"radial"')
        && str_contains($treeTile, '"compressedStateGzipBase64":null')
        && str_contains($treeIPSView, '"compressedStateGzipBase64":null')
        && strlen($treeTile) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($treeIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Tree must render independent Tile and IPSView designs below the output limit.'
);
assertGatewayGauge(
    findGaugeFormElement($treeForm['elements'], 'BarPreview')['type'] === 'Image'
        && findGaugeFormElement($treeForm['elements'], 'IPSViewBarPreview')['type'] === 'Image'
        && findGaugeFormElement($treeForm['elements'], 'IPSViewTreeLayout')['enabled'] === true,
    'Tree must expose live Tile and independent IPSView previews.'
);
$GLOBALS['symconTestTreeObjects'][9006] = [
    'ObjectType' => 2, 'ObjectName' => 'Batteriestand', 'ObjectIsHidden' => false
];
$GLOBALS['symconTestTreeChildren'][9001][] = 9006;
$GLOBALS['symconTestTreeValues'][9006] = '88 %';
try {
    $tree->SetTestProperty('VariableNameFilter', 'bAtTeRiE');
    $tree->ApplyChanges();
    $filteredTreeData = json_decode($tree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR);
    $filteredTreeForm = json_decode($tree->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $filterControl = findGaugeFormElement($filteredTreeForm['elements'], 'VariableNameFilter');
    assertGatewayGauge(
        $tree->GetTestStatus() === IS_ACTIVE
            && $filteredTreeData['tree']['count'] === 3
            && $filteredTreeData['tree']['root']['children'][0]['children'][0]['id'] === 'object-9006'
            && $filteredTreeData['tree']['root']['children'][0]['children'][0]['displayValue'] === '88 %'
            && $filterControl['type'] === 'ValidationTextBox'
            && str_contains((string) ($filterControl['onChange'] ?? ''), 'ECTR_UpdateBarPreviewFromForm')
            && str_contains($tree->GetVisualizationTile(), '"id":"object-9006"')
            && str_contains($tree->GetIPSViewHTML(), '"id":"object-9006"'),
        'Tree must filter Tile and IPSView by variable name and update both previews from the form.'
    );
    $tree->UpdateBarPreviewFromForm(json_encode(['VariableNameFilter' => 'Temperatur'], JSON_THROW_ON_ERROR));
    $filterPreviewUpdates = array_slice($tree->GetTestFormUpdates(), -2);
    $filterTilePreview = base64_decode(substr($filterPreviewUpdates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
    $filterIPSViewPreview = base64_decode(substr($filterPreviewUpdates[1]['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        is_string($filterTilePreview) && is_string($filterIPSViewPreview)
            && str_contains($filterTilePreview, 'Temperatur') && !str_contains($filterTilePreview, 'Batteriestand')
            && str_contains($filterIPSViewPreview, 'Temperatur') && !str_contains($filterIPSViewPreview, 'Batteriestand'),
        'Unsaved variable-name filtering must reach both live previews.'
    );
    $tree->SetTestProperty('VariableNameFilter', 'Kein Treffer');
    $tree->ApplyChanges();
    $emptyFilteredTree = json_decode($tree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        $emptyFilteredTree['tree']['count'] === 1 && $emptyFilteredTree['tree']['root']['children'] === [],
        'A name filter without matches must retain only the root.'
    );
    $tree->SetTestProperty('VariableNameFilter', str_repeat('x', 121));
    $tree->ApplyChanges();
    assertGatewayGauge($tree->GetTestStatus() === 201, 'Tree must reject an oversized name filter.');
} finally {
    $tree->SetTestProperty('VariableNameFilter', '');
    array_pop($GLOBALS['symconTestTreeChildren'][9001]);
    unset($GLOBALS['symconTestTreeObjects'][9006], $GLOBALS['symconTestTreeValues'][9006]);
    $tree->ApplyChanges();
}
$tree = new EChartsTreeSystemHealth();
$tree->Create();
$tree->SetTestProperty('RootObjectID', 9000);
$tree->SetTestProperty('Title', 'Home health tree');
$tree->SetTestProperty('IPSViewUseTileDesign', false);
$tree->SetTestProperty('IPSViewTreeLayout', 'radial');
$tree->SetTestProperty('StatusRules', json_encode([
    ['VariableID' => 9002, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2],
    ['VariableID' => 9003, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2]
], JSON_THROW_ON_ERROR));
$tree->SetTestProperty('HealthyColor', 0x008800);
$tree->SetTestProperty('WarningColor', 0xCC9900);
$tree->SetTestProperty('CriticalColor', 0xCC0000);
$tree->SetTestProperty('IPSViewCriticalColor', 0xDDAA00);
$tree->ApplyChanges();
$healthData = json_decode($tree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR);
$healthRoot = $healthData['tree']['root'];
$healthForm = json_decode($tree->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$healthRulesForm = findGaugeFormElement($healthForm['elements'], 'StatusRules');
assertGatewayGauge(
    $healthRoot['health'] === 'critical'
        && $healthRoot['children'][0]['health'] === 'critical'
        && $healthRoot['children'][0]['children'][0]['health'] === 'warning'
        && $healthRoot['children'][0]['children'][1]['health'] === 'critical'
        && $healthData['tree']['style']['criticalColor'] === '#CC0000'
        && findGaugeFormElement($healthForm['elements'], 'StatusRules')['type'] === 'List'
        && findGaugeFormElement($healthForm['elements'], 'IPSViewCriticalColor')['enabled'] === true,
    'Tree must expose explicit status rules, propagate severity and keep separate design colors.'
);
assertGatewayGauge(
    str_contains((string) ($healthRulesForm['form'] ?? ''), 'ECTH_GetStatusRuleEditorForm')
        && str_contains((string) ($healthRulesForm['onAdd'] ?? ''), 'ECTH_UpdateStatusRulesForm')
        && str_contains((string) ($healthRulesForm['onEdit'] ?? ''), 'ECTH_UpdateStatusRulesForm')
        && str_contains((string) ($healthRulesForm['onDelete'] ?? ''), 'ECTH_UpdateStatusRulesForm')
        && str_contains((string) ($healthRulesForm['onAdd'] ?? ''), 'ECTH_UpdateBarPreviewFromForm')
        && str_contains((string) ($healthRulesForm['onAdd'] ?? ''), "'StatusRules' => \$statusRuleRows")
        && findGaugeFormElement($healthForm['elements'], 'StatusRulesDiagnostics')['visible'] === false,
    'Tree rule changes must update validation feedback and both previews without persisting drafts.'
);
$ruleOnlyControl = findGaugeFormElement($healthForm['elements'], 'ShowOnlyRuleVariables');
assertGatewayGauge(
    $ruleOnlyControl !== null
        && $ruleOnlyControl['type'] === 'CheckBox'
        && str_contains((string) ($ruleOnlyControl['onChange'] ?? ''), 'ECTH_UpdateBarPreviewFromForm'),
    'Health Tree must expose a live-preview rule-only visibility switch.'
);
$ruleOnlyRules = json_encode([
    ['VariableID' => 9002, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2]
], JSON_THROW_ON_ERROR);
$tree->SetTestProperty('StatusRules', $ruleOnlyRules);
$tree->SetTestProperty('ShowOnlyRuleVariables', true);
$tree->ApplyChanges();
$ruleOnlyData = json_decode($tree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $ruleOnlyData['tree']['count'] === 3
        && $ruleOnlyData['tree']['root']['children'][0]['children'][0]['id'] === 'object-9002'
        && count($ruleOnlyData['tree']['root']['children'][0]['children']) === 1
        && str_contains($tree->GetVisualizationTile(), '"id":"object-9002"')
        && !str_contains($tree->GetVisualizationTile(), '"id":"object-9003"')
        && str_contains($tree->GetIPSViewHTML(), '"id":"object-9002"')
        && !str_contains($tree->GetIPSViewHTML(), '"id":"object-9003"'),
    'Health Tree must hide unruled variables in Tile and IPSView while keeping their ancestors.'
);
$tree->SearchStatusRuleCandidates('Temperature', 'numeric', false, 9000, 3, 80, false, '', $ruleOnlyRules);
$searchRows = json_decode((string) array_slice($tree->GetTestFormUpdates(), -2)[0]['Value'], true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    count($searchRows) === 1 && $searchRows[0]['VariableID'] === 9003,
    'Rule-only display filtering must not block searching for new status-rule candidates.'
);
$tree->UpdateBarPreviewFromForm(json_encode([
    'ShowOnlyRuleVariables' => true,
    'StatusRules'           => [
        ['VariableID' => 9002, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2]
    ]
], JSON_THROW_ON_ERROR));
$ruleOnlyPreviewUpdates = array_slice($tree->GetTestFormUpdates(), -2);
$ruleOnlyTileSvg = base64_decode(substr($ruleOnlyPreviewUpdates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$ruleOnlyIPSViewSvg = base64_decode(substr($ruleOnlyPreviewUpdates[1]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($ruleOnlyTileSvg) && substr_count($ruleOnlyTileSvg, '<circle ') === 3
        && is_string($ruleOnlyIPSViewSvg) && substr_count($ruleOnlyIPSViewSvg, '<circle ') === 3,
    'Unsaved rule-only filtering must update Tile and IPSView previews.'
);
$tree->SetTestProperty('StatusRules', '[]');
$tree->ApplyChanges();
$emptyRuleOnlyData = json_decode($tree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $emptyRuleOnlyData['tree']['count'] === 1
        && $emptyRuleOnlyData['tree']['root']['children'] === []
        && !isset($emptyRuleOnlyData['tree']['root']['health']),
    'Rule-only filtering without status rules must show only the root.'
);
$tree->SetTestProperty('ShowOnlyRuleVariables', false);
$tree->SetTestProperty('StatusRules', json_encode([
    ['VariableID' => 9002, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2],
    ['VariableID' => 9003, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2]
], JSON_THROW_ON_ERROR));
$tree->ApplyChanges();
$lowEditor = json_decode($tree->GetStatusRuleEditorForm('low', 40), true, 512, JSON_THROW_ON_ERROR);
$highEditor = json_decode($tree->GetStatusRuleEditorForm('high', 40), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    findGaugeFormElement($lowEditor, 'CriticalAt')['maximum'] === 40
        && !isset(findGaugeFormElement($lowEditor, 'CriticalAt')['minimum'])
        && findGaugeFormElement($highEditor, 'CriticalAt')['minimum'] === 40
        && !isset(findGaugeFormElement($highEditor, 'CriticalAt')['maximum'])
        && str_contains(
            (string) findGaugeFormElement($lowEditor, 'WarningAt')['onChange'],
            'ECTH_UpdateStatusRuleEditor'
        )
        && str_contains(
            (string) findGaugeFormElement($lowEditor, 'Direction')['onChange'],
            'ECTH_UpdateStatusRuleEditor'
        ),
    'The Tree rule dialog must reject inverse critical thresholds for both alarm directions.'
);
$tree->UpdateStatusRuleEditor('low', 40);
assertGatewayGauge(
    array_slice($tree->GetTestFormUpdates(), -2) === [
        ['Field' => 'CriticalAt', 'Parameter' => 'minimum', 'Value' => -PHP_FLOAT_MAX],
        ['Field' => 'CriticalAt', 'Parameter' => 'maximum', 'Value' => 40.0]
    ],
    'Changing a rule to low alarm direction must update the critical-value bound in the open dialog.'
);
$tree->UpdateStatusRuleEditor('high', 40);
assertGatewayGauge(
    array_slice($tree->GetTestFormUpdates(), -2) === [
        ['Field' => 'CriticalAt', 'Parameter' => 'maximum', 'Value' => PHP_FLOAT_MAX],
        ['Field' => 'CriticalAt', 'Parameter' => 'minimum', 'Value' => 40.0]
    ],
    'Changing a rule to high alarm direction must remove the previous low-direction bound.'
);
$tree->UpdateStatusRulesForm(json_encode([
    ['VariableID' => 9002, 'Direction' => 'low', 'WarningAt' => 40, 'CriticalAt' => 30],
    ['VariableID' => 9002, 'Direction' => 'low', 'WarningAt' => 40, 'CriticalAt' => 30],
    ['VariableID' => 9003, 'Direction' => 'high', 'WarningAt' => 40, 'CriticalAt' => 30]
], JSON_THROW_ON_ERROR));
$ruleFeedback = array_slice($tree->GetTestFormUpdates(), -3);
$highlightedRows = json_decode((string) $ruleFeedback[0]['Value'], true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $ruleFeedback[0]['Field'] === 'StatusRules'
        && $ruleFeedback[0]['Parameter'] === 'values'
        && $highlightedRows[0]['rowColor'] !== ''
        && $highlightedRows[1]['rowColor'] !== ''
        && $highlightedRows[2]['rowColor'] !== ''
        && str_contains((string) $ruleFeedback[1]['Value'], '9002')
        && str_contains((string) $ruleFeedback[1]['Value'], 'First status')
        && str_contains((string) $ruleFeedback[1]['Value'], '9003')
        && $ruleFeedback[2] === ['Field' => 'StatusRulesDiagnostics', 'Parameter' => 'visible', 'Value' => true],
    'Duplicate variables and reversed thresholds must identify and highlight their draft rows immediately: '
        . json_encode($ruleFeedback, JSON_THROW_ON_ERROR)
);
$GLOBALS['symconTestTreeObjects'][9010] = [
    'ObjectType' => 2, 'ObjectName' => 'Batterie Keller', 'ObjectIsHidden' => false
];
$GLOBALS['symconTestTreeObjects'][9011] = [
    'ObjectType' => 2, 'ObjectName' => 'Batterie Dach', 'ObjectIsHidden' => false
];
$GLOBALS['symconTestTreeChildren'][9001][] = 9010;
$GLOBALS['symconTestTreeChildren'][9001][] = 9011;
$GLOBALS['symconTestVariables'][9010] = [
    'VariableType' => 1, 'VariableUpdated' => 1780000008, 'Value' => 50, 'Name' => 'Batterie Keller'
];
$GLOBALS['symconTestVariables'][9011] = [
    'VariableType' => 2, 'VariableUpdated' => 1780000008, 'Value' => 49.5, 'Name' => 'Batterie Dach'
];
try {
    $importForm = json_decode($tree->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $importButton = findGaugeFormElement($importForm['elements'], 'StatusRuleImport');
    $importItems = $importButton['popup']['items'];
    $searchButton = null;
    foreach ($importItems as $item) {
        if (($item['type'] ?? null) === 'Button') {
            $searchButton = $item;
        }
    }
    $importClick = (string) ($importButton['popup']['buttons'][0]['onClick'] ?? '');
    $parsedImportClick = eval('return static function () { ' . $importClick . ' };');
    assertGatewayGauge(
        ($importButton['type'] ?? '') === 'PopupButton'
            && !isset($importButton['popup']['pages'])
            && str_contains((string) ($importButton['onClick'] ?? ''), 'InvalidateStatusRuleCandidates')
            && is_callable($parsedImportClick)
            && str_contains((string) ($searchButton['onClick'] ?? ''), 'ECTH_SearchStatusRuleCandidates')
            && str_contains(
                (string) ($importButton['popup']['buttons'][0]['onClick'] ?? ''),
                'ECTH_AddStatusRuleCandidates'
            )
            && str_contains(
                (string) ($importButton['popup']['buttons'][0]['onClick'] ?? ''),
                "'IPSViewCriticalColor' => \$IPSViewCriticalColor"
            ),
        'The Tree bulk importer must use a Symcon 9.0-compatible popup with an explicit search and selection.'
    );
    $draftRules = [
        ['VariableID' => 9002, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2],
        ['VariableID' => 9010, 'Direction' => 'low', 'WarningAt' => 40, 'CriticalAt' => 30]
    ];
    $captureImportAction = static function (string $action, string $method, array $overrides): array
    {
        preg_match_all('/\$([A-Za-z_][A-Za-z0-9_]*)/', $action, $matches);
        $values = array_replace(array_fill_keys($matches[1], null), $overrides);
        $captured = [];
        $capture = static function (...$args) use (&$captured): string
        {
            $captured = $args;
            return 'MESSAGE:captured';
        };
        $action = str_replace($method . '(', '$capture(', $action);
        $callback = eval('return static function (array $values, callable $capture) {'
            . ' extract($values); ' . $action . ' };');
        $callback($values, $capture);
        return $captured;
    };
    $blankSearchArgs = $captureImportAction(
        (string) ($searchButton['onClick'] ?? ''),
        'ECTH_SearchStatusRuleCandidates',
        ['StatusRules' => new ArrayIterator([])]
    );
    $selectedImportRows = [['VariableID' => 9011, 'Selected' => true]];
    $addArgs = $captureImportAction(
        $importClick,
        'ECTH_AddStatusRuleCandidates',
        [
            'StatusRules'      => new ArrayIterator($draftRules),
            'ImportCandidates' => new ArrayIterator($selectedImportRows)
        ]
    );
    assertGatewayGauge(
        ($blankSearchArgs[9] ?? null) === '[]'
            && json_decode((string) ($addArgs[12] ?? ''), true) === $draftRules
            && json_decode((string) ($addArgs[13] ?? ''), true) === $selectedImportRows,
        'Tree popup actions must serialize iterable Symcon List values as row arrays, including empty lists.'
    );
    $tree->SearchStatusRuleCandidates(
        'bAtTeRiE',
        'numeric',
        false,
        9000,
        3,
        80,
        false,
        '',
        json_encode($draftRules, JSON_THROW_ON_ERROR)
    );
    $candidateUpdates = array_slice($tree->GetTestFormUpdates(), -2);
    $candidateRows = json_decode((string) $candidateUpdates[0]['Value'], true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        $candidateUpdates[0]['Field'] === 'ImportCandidates'
            && count($candidateRows) === 1
            && $candidateRows[0]['VariableID'] === 9011
            && $candidateRows[0]['Selected'] === true
            && str_contains((string) $candidateUpdates[1]['Value'], '1 already'),
        'Bulk search must match variable names case-insensitively, exclude existing rules and keep IDs distinct.'
    );
    $tree->SearchStatusRuleCandidates(
        'Batterie',
        'int',
        false,
        9000,
        3,
        80,
        false,
        '',
        json_encode($draftRules, JSON_THROW_ON_ERROR)
    );
    assertGatewayGauge(
        json_decode(
            (string) array_slice($tree->GetTestFormUpdates(), -2)[0]['Value'],
            true,
            512,
            JSON_THROW_ON_ERROR
        ) === [],
        'Bulk search must honor the Integer/Float type selection.'
    );
    $tree->InvalidateStatusRuleCandidates();
    assertGatewayGauge(
        array_slice($tree->GetTestFormUpdates(), -2)[0] === [
            'Field' => 'ImportCandidates', 'Parameter' => 'values', 'Value' => '[]'
        ],
        'Reopening or changing the importer search must clear stale selections.'
    );
    $forgedRows = $candidateRows;
    $forgedRows[0]['VariableID'] = 9003;
    $forgedResult = $tree->AddStatusRuleCandidates(
        'Batterie',
        'numeric',
        'low',
        40,
        30,
        false,
        9000,
        3,
        80,
        false,
        '',
        json_encode($draftRules, JSON_THROW_ON_ERROR),
        json_encode($forgedRows, JSON_THROW_ON_ERROR),
        '{}'
    );
    assertGatewayGauge(
        !str_starts_with($forgedResult, 'MESSAGE:'),
        'Bulk import must revalidate selected IDs and reject forged or stale entries.'
    );
    $badOrder = $tree->AddStatusRuleCandidates(
        'Batterie',
        'numeric',
        'low',
        40,
        50,
        false,
        9000,
        3,
        80,
        false,
        '',
        json_encode($draftRules, JSON_THROW_ON_ERROR),
        json_encode($candidateRows, JSON_THROW_ON_ERROR),
        '{}'
    );
    assertGatewayGauge(
        !str_starts_with($badOrder, 'MESSAGE:'),
        'Bulk import must reject reversed thresholds without changing the form.'
    );
    $noSelection = $candidateRows;
    $noSelection[0]['Selected'] = false;
    $emptyResult = $tree->AddStatusRuleCandidates(
        'Batterie',
        'numeric',
        'low',
        40,
        30,
        false,
        9000,
        3,
        80,
        false,
        '',
        json_encode($draftRules, JSON_THROW_ON_ERROR),
        json_encode($noSelection, JSON_THROW_ON_ERROR),
        '{}'
    );
    assertGatewayGauge(
        !str_starts_with($emptyResult, 'MESSAGE:'),
        'Bulk import must require an explicit selection.'
    );
    $addResult = $tree->AddStatusRuleCandidates(
        'Batterie',
        'numeric',
        'low',
        40,
        30,
        false,
        9000,
        3,
        80,
        false,
        '',
        json_encode($draftRules, JSON_THROW_ON_ERROR),
        json_encode($candidateRows, JSON_THROW_ON_ERROR),
        json_encode([
            'WarningColor'         => 0x123456,
            'IPSViewUseTileDesign' => false,
            'IPSViewWarningColor'  => 0xABCDEF
        ], JSON_THROW_ON_ERROR)
    );
    $importUpdates = array_slice($tree->GetTestFormUpdates(), -5);
    $mergedRules = json_decode((string) $importUpdates[0]['Value'], true, 512, JSON_THROW_ON_ERROR);
    $importTilePreview = base64_decode(substr(
        (string) $importUpdates[3]['Value'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    $importIPSViewPreview = base64_decode(substr(
        (string) $importUpdates[4]['Value'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(
        str_starts_with($addResult, 'MESSAGE:')
            && $importUpdates[0]['Field'] === 'StatusRules'
            && array_column($mergedRules, 'VariableID') === [9002, 9010, 9011]
            && $mergedRules[2]['Direction'] === 'low'
            && $mergedRules[2]['WarningAt'] == 40
            && $mergedRules[2]['CriticalAt'] == 30
            && $importUpdates[3]['Field'] === 'BarPreview'
            && $importUpdates[4]['Field'] === 'IPSViewBarPreview'
            && is_string($importTilePreview) && str_contains($importTilePreview, 'fill="#123456"')
            && is_string($importIPSViewPreview) && str_contains($importIPSViewPreview, 'fill="#ABCDEF"')
            && array_column(findGaugeFormElement(json_decode(
                $tree->GetConfigurationForm(),
                true,
                512,
                JSON_THROW_ON_ERROR
            )['elements'], 'StatusRules')['values'], 'VariableID') === [9002, 9003],
        'Bulk import must merge into the draft only, preserve existing rules and refresh both previews.'
    );
} finally {
    array_pop($GLOBALS['symconTestTreeChildren'][9001]);
    array_pop($GLOBALS['symconTestTreeChildren'][9001]);
    unset($GLOBALS['symconTestTreeObjects'][9010], $GLOBALS['symconTestTreeObjects'][9011]);
    unset($GLOBALS['symconTestVariables'][9010], $GLOBALS['symconTestVariables'][9011]);
}
assertGatewayGauge(
    str_contains($tree->GetVisualizationTile(), '"health":"critical"')
        && str_contains($tree->GetIPSViewHTML(), '"health":"critical"')
        && str_contains($tree->GetIPSViewHTML(), '"criticalColor":"#DDAA00"'),
    'Tile and IPSView must share status while applying independent status colors.'
);
$tree->UpdateBarPreviewFromForm(json_encode([
    'StatusRules' => [
        ['VariableID' => 9002, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 1]
    ],
    'CriticalColor'        => 0xAABBCC,
    'IPSViewUseTileDesign' => false,
    'IPSViewCriticalColor' => 0x0011AA
], JSON_THROW_ON_ERROR));
$treePreviewUpdates = array_slice($tree->GetTestFormUpdates(), -2);
$treeTileSvg = base64_decode(substr($treePreviewUpdates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$treeIPSViewSvg = base64_decode(substr($treePreviewUpdates[1]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($treeTileSvg) && is_string($treeIPSViewSvg)
        && str_contains($treeTileSvg, 'fill="#AABBCC"')
        && str_contains($treeIPSViewSvg, 'fill="#0011AA"'),
    'Unsaved Tree status rules and independent colors must reach both live previews.'
);
$tree->SetTestProperty('StatusRules', '[{"VariableID":9002,"Direction":"high","WarningAt":2,"CriticalAt":1}]');
$tree->ApplyChanges();
assertGatewayGauge($tree->GetTestStatus() === 205, 'Tree must reject invalid status thresholds.');
$invalidForm = json_decode($tree->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    findGaugeFormElement($invalidForm['elements'], 'StatusRulesDiagnostics')['visible'] === true
        && str_contains(
            (string) findGaugeFormElement($invalidForm['elements'], 'StatusRulesDiagnostics')['caption'],
            'Rule 1'
        )
        && findGaugeFormElement($invalidForm['elements'], 'StatusRules')['values'][0]['rowColor'] !== '',
    'Existing invalid rules must be explained and marked when the configuration form opens.'
);
$tree->SetTestProperty('StatusRules', json_encode([
    ['VariableID' => 9002, 'Direction' => 'low', 'WarningAt' => 40, 'CriticalAt' => 30],
    ['VariableID' => 9002, 'Direction' => 'low', 'WarningAt' => 40, 'CriticalAt' => 30]
], JSON_THROW_ON_ERROR));
$tree->ApplyChanges();
$duplicateForm = json_decode($tree->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $tree->GetTestStatus() === 205
        && str_contains(
            (string) findGaugeFormElement($duplicateForm['elements'], 'StatusRulesDiagnostics')['caption'],
            'rule 1'
        )
        && findGaugeFormElement($duplicateForm['elements'], 'StatusRules')['values'][0]['rowColor'] !== ''
        && findGaugeFormElement($duplicateForm['elements'], 'StatusRules')['values'][1]['rowColor'] !== '',
    'Stored duplicate rules must identify and highlight both occurrences.'
);
$tree->SetTestProperty('StatusRules', '[{"VariableID":99999,"Direction":"high","WarningAt":1,"CriticalAt":2}]');
$tree->ApplyChanges();
assertGatewayGauge($tree->GetTestStatus() === 205, 'Tree must reject missing status variables.');
$tree->SetTestProperty('StatusRules', '[]');
$tree->ApplyChanges();
assertGatewayGauge(
    !isset(json_decode($tree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR)['tree']['root']['health'])
        && json_decode($tree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR)['tree']['count'] === 4,
    'Removing all status rules must restore the previous uncolored model.'
);
$tree = new EChartsTree();
$tree->Create();
$tree->SetTestProperty('RootObjectID', 9000);
$tree->ApplyChanges();
$GLOBALS['symconTestTreeChildren'][0] = [9000];
try {
    $unselectedTree = new EChartsTree();
    $unselectedTree->Create();
    $unselectedTree->ApplyChanges();
    $unselectedForm = json_decode($unselectedTree->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        $unselectedTree->GetTestStatus() === 201
            && findGaugeFormElement($unselectedForm['elements'], 'RootObjectID')['enabled'] === true,
        'An unselected Tree must remain invalid and offer the object selector.'
    );
    $unselectedTree->UpdateBarPreviewFromForm(json_encode([
        'UseSymconRoot' => true, 'RootObjectID' => 0
    ], JSON_THROW_ON_ERROR));
    $rootDraftSvg = base64_decode(substr(
        (string) array_slice($unselectedTree->GetTestFormUpdates(), -2)[0]['Value'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(
        is_string($rootDraftSvg) && str_contains($rootDraftSvg, 'Symcon') && str_contains($rootDraftSvg, 'Home'),
        'Unsaved Symcon-root selection must use the real hierarchy in the live preview.'
    );

    $symconTree = new EChartsTree();
    $symconTree->Create();
    $symconTree->SetTestProperty('UseSymconRoot', true);
    $symconTree->ApplyChanges();
    $symconData = json_decode($symconTree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR);
    $symconForm = json_decode($symconTree->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $rootToggle = findGaugeFormElement($symconForm['elements'], 'UseSymconRoot');
    assertGatewayGauge(
        $symconTree->GetTestStatus() === IS_ACTIVE
            && $symconData['tree']['root']['id'] === 'object-0'
            && $symconData['tree']['root']['name'] === 'Symcon'
            && $symconData['tree']['root']['children'][0]['id'] === 'object-9000'
            && findGaugeFormElement($symconForm['elements'], 'RootObjectID')['enabled'] === false
            && str_contains((string) ($rootToggle['onChange'] ?? ''), 'ECTR_UpdateTreeRootAvailability')
            && str_contains((string) ($rootToggle['onChange'] ?? ''), 'ECTR_UpdateBarPreviewFromForm'),
        'Explicit Symcon-root mode must traverse object zero and disable the unused object selector.'
    );
    assertGatewayGauge(
        str_contains($symconTree->GetVisualizationTile(), '"id":"object-0"')
            && str_contains($symconTree->GetIPSViewHTML(), '"id":"object-0"'),
        'Tile and IPSView must render the explicitly selected Symcon root.'
    );
    $symconTree->UpdateTreeRootAvailability(true);
    assertGatewayGauge(
        array_slice($symconTree->GetTestFormUpdates(), -1)[0] === [
            'Field' => 'RootObjectID', 'Parameter' => 'enabled', 'Value' => false
        ],
        'Symcon-root toggle must immediately update object-selector availability.'
    );
    $symconTree->SetTestProperty('RootObjectID', 9000);
    $symconTree->ApplyChanges();
    assertGatewayGauge(
        json_decode($symconTree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR)['tree']['root']['id'] === 'object-0',
        'Explicit Symcon-root mode must override a previously selected object without deleting it.'
    );
    $symconTree->SetTestProperty('UseSymconRoot', false);
    $symconTree->ApplyChanges();
    $restoredForm = json_decode($symconTree->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        json_decode($symconTree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR)['tree']['root']['id'] === 'object-9000'
            && findGaugeFormElement($restoredForm['elements'], 'RootObjectID')['enabled'] === true,
        'Turning off Symcon-root mode must restore the stored object selection.'
    );
} finally {
    unset($GLOBALS['symconTestTreeChildren'][0]);
}
$tree->SetTestProperty('MaxNodes', 2);
$tree->ApplyChanges();
assertGatewayGauge($tree->GetTestStatus() === 201, 'Tree must reject out-of-contract node limits.');
$tree->SetTestProperty('MaxNodes', 10);
$tree->SetTestProperty('RootObjectID', 9002);
$tree->ApplyChanges();
assertGatewayGauge($tree->GetTestStatus() === 201, 'Tree must reject variable roots.');

$GLOBALS['symconTestTreeObjects'][9100] = [
    'ObjectType' => 0, 'ObjectName' => 'Wide root', 'ObjectIsHidden' => false
];
$GLOBALS['symconTestTreeChildren'][9100] = range(20000, 23000);
foreach ($GLOBALS['symconTestTreeChildren'][9100] as $wideID) {
    $GLOBALS['symconTestTreeObjects'][$wideID] = [
        'ObjectType' => 2, 'ObjectName' => str_repeat('N', 120), 'ObjectIsHidden' => false
    ];
    $GLOBALS['symconTestTreeValues'][$wideID] = str_repeat('V', 100);
}
try {
    $wideTree = new EChartsTree();
    $wideTree->Create();
    $wideTree->SetTestProperty('RootObjectID', 9100);
    $wideTree->SetTestProperty('MaxNodes', 3000);
    $wideTree->ApplyChanges();
    $wideTreeData = json_decode($wideTree->GetTreeData(), true, 512, JSON_THROW_ON_ERROR);
    $wideTreeForm = json_decode($wideTree->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        $wideTree->GetTestStatus() === IS_ACTIVE
            && $wideTreeData['tree']['count'] === 3000
            && $wideTreeData['tree']['truncated'] === true
            && count($wideTreeData['tree']['root']['children']) === 2999
            && findGaugeFormElement($wideTreeForm['elements'], 'MaxNodes')['maximum'] === 3000,
        'Tree must accept 3000 nodes, report truncation and expose the new form limit.'
    );
    $wideTreeTileHTML = $wideTree->GetVisualizationTile();
    $wideTreeIPSViewHTML = $wideTree->GetIPSViewHTML();
    $wideTreeTileBytes = strlen($wideTreeTileHTML);
    $wideTreeIPSViewBytes = strlen($wideTreeIPSViewHTML);
    assertGatewayGauge(
        $wideTreeTileBytes < SYMCON_OUTPUT_BUFFER_LIMIT
            && $wideTreeIPSViewBytes < SYMCON_OUTPUT_BUFFER_LIMIT,
        sprintf(
            'A 3000-node Tree must fit the Tile and IPSView output limit with long names and values (data: %d, Tile: %d, IPSView: %d bytes).',
            strlen($wideTree->GetTreeData()),
            $wideTreeTileBytes,
            $wideTreeIPSViewBytes
        )
    );
    foreach ([$wideTreeTileHTML, $wideTreeIPSViewHTML] as $wideTreeHTML) {
        $bootstrapMatch = [];
        assertGatewayGauge(
            preg_match('/window\.SYMC_VISUALIZATION = ([^\r\n]+);<\/script>/', $wideTreeHTML, $bootstrapMatch) === 1,
            'A 3000-node Tree output must contain a bootstrap payload.'
        );
        $bootstrap = json_decode($bootstrapMatch[1], true, 512, JSON_THROW_ON_ERROR);
        $encodedState = $bootstrap['options']['compressedStateGzipBase64'] ?? null;
        $gzip = is_string($encodedState) ? base64_decode($encodedState, true) : false;
        $decodedState = is_string($gzip) ? gzdecode($gzip) : false;
        assertGatewayGauge(
            $bootstrap['state'] === null && is_string($decodedState)
                && json_decode($decodedState, true, 512, JSON_THROW_ON_ERROR)['chart']['tree']['count'] === 3000,
            'A 3000-node Tree bootstrap must restore the complete chart state.'
        );
    }
    $wideTree->SetTestProperty('MaxNodes', 3001);
    $wideTree->ApplyChanges();
    assertGatewayGauge($wideTree->GetTestStatus() === 201, 'Tree must reject a configured limit above 3000.');
} finally {
    foreach ($GLOBALS['symconTestTreeChildren'][9100] as $wideID) {
        unset($GLOBALS['symconTestTreeObjects'][$wideID], $GLOBALS['symconTestTreeValues'][$wideID]);
    }
    unset($GLOBALS['symconTestTreeChildren'][9100], $GLOBALS['symconTestTreeObjects'][9100]);
}

// Current-value Pie models keep duplicate visible names separate by variable ID.
$pie = new EChartsPie();
$pie->Create();
$pie->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Label' => 'Temperatur', 'UseVariablePresentation' => false,
        'Unit'    => '°C', 'Decimals' => 1, 'Color' => 0x5070DD],
    ['VariableID' => 4717, 'Label' => 'Temperatur', 'UseVariablePresentation' => false,
        'Unit'    => '°C', 'Decimals' => 1, 'Color' => -1]
], JSON_THROW_ON_ERROR));
$pie->SetTestProperty('Title', 'Anteile');
$pie->SetTestProperty('IPSViewUseTileDesign', false);
$pie->SetTestProperty('IPSViewPieMode', 'nightingale');
$pie->ApplyChanges();
$pieData = json_decode($pie->GetPieData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $pie->GetTestStatus() === IS_ACTIVE
        && $pieData['family'] === 'pie'
        && array_column($pieData['items'], 'id') === ['variable-4711', 'variable-4717']
        && array_column($pieData['items'], 'name') === ['Temperatur', 'Temperatur']
        && $pieData['items'][0]['color'] === '#5070DD',
    'Pie must retain same-name current sources separately by variable ID.'
);
$pieTile = $pie->GetVisualizationTile();
$pieIPSView = $pie->GetIPSViewHTML();
$pieUpdatesBefore = count($pie->GetTestVisualizationUpdates());
$pie->MessageSink(1780000300, 4711, VM_UPDATE, []);
assertGatewayGauge(
    count($pie->GetTestVisualizationUpdates()) > $pieUpdatesBefore,
    'Pie must publish fresh values when a configured source variable changes.'
);
$pieForm = json_decode($pie->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    str_contains($pieTile, 'echarts-pie-root')
        && str_contains($pieTile, '"mode":"pie"')
        && str_contains($pieIPSView, '"mode":"nightingale"')
        && strlen($pieTile) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($pieIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Pie must render independent Tile and IPSView designs below the output limit.'
);
assertGatewayGauge(
    findGaugeFormElement($pieForm['elements'], 'BarPreview')['type'] === 'Image'
        && findGaugeFormElement($pieForm['elements'], 'IPSViewBarPreview')['type'] === 'Image'
        && findGaugeFormElement($pieForm['elements'], 'IPSViewPieMode')['enabled'] === true
        && findGaugeFormElement($pieForm['elements'], 'CopyTileDesignToIPSViewButton')['enabled'] === true,
    'Pie must expose live Tile and independent IPSView preview controls.'
);
$pie->UpdateBarPreviewFromForm(json_encode([
    'Sources' => [
        ['VariableID' => 4711, 'Label' => 'Raum'],
        ['VariableID' => 4717, 'Label' => 'Außen']
    ],
    'PieMode' => 'donut', 'IPSViewUseTileDesign' => false, 'IPSViewPieMode' => 'nightingale'
], JSON_THROW_ON_ERROR));
$pieUpdates = array_slice($pie->GetTestFormUpdates(), -2);
$tilePreview = base64_decode(substr($pieUpdates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$ipsViewPreview = base64_decode(substr($pieUpdates[1]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($tilePreview) && str_contains($tilePreview, 'Donut')
        && str_contains($tilePreview, 'Raum')
        && is_string($ipsViewPreview) && str_contains($ipsViewPreview, 'Nightingale'),
    'Pie previews must reflect unsaved source labels and independent layouts.'
);
$negativePieValue = $GLOBALS['symconTestVariables'][4717]['Value'];
$GLOBALS['symconTestVariables'][4717]['Value'] = -1.0;
assertGatewayGauge(
    str_contains($pie->GetVisualizationTile(), 'Pie charts require non-negative values.')
        && $pie->GetTestStatus() === 205,
    'Pie must visibly reject negative values instead of converting their sign.'
);
$GLOBALS['symconTestVariables'][4717]['Value'] = $negativePieValue;
$zeroPieValue = $GLOBALS['symconTestVariables'][4711]['Value'];
$GLOBALS['symconTestVariables'][4711]['Value'] = 0.0;
$GLOBALS['symconTestVariables'][4717]['Value'] = 0.0;
assertGatewayGauge(
    str_contains($pie->GetVisualizationTile(), '"value":0'),
    'Pie must retain zero-valued source data for an explicit empty display.'
);
$GLOBALS['symconTestVariables'][4711]['Value'] = $zeroPieValue;
$GLOBALS['symconTestVariables'][4717]['Value'] = $negativePieValue;
$originalPieName = $GLOBALS['symconTestVariables'][4717]['Name'];
$GLOBALS['symconTestVariables'][4717]['Name'] = 'Aktueller Symcon-Name';
$pie->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Label' => 'Vorgabe', 'UseVariablePresentation' => false, 'Unit' => '°C'],
    ['VariableID' => 4717, 'Label' => '', 'UseVariablePresentation' => false, 'Unit' => '°C']
], JSON_THROW_ON_ERROR));
$pie->ApplyChanges();
assertGatewayGauge(
    array_column(json_decode($pie->GetPieData(), true, 512, JSON_THROW_ON_ERROR)['items'], 'name')
        === ['Vorgabe', 'Aktueller Symcon-Name'],
    'Pie must prefer an explicit label and otherwise use the current Symcon name.'
);
$GLOBALS['symconTestVariables'][4717]['Name'] = $originalPieName;
$invalidPie = new EChartsPie();
$invalidPie->Create();
$invalidPie->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711], ['VariableID' => 4711]
], JSON_THROW_ON_ERROR));
$invalidPie->ApplyChanges();
assertGatewayGauge($invalidPie->GetTestStatus() === 201, 'Pie must reject duplicate source variable IDs.');
$invalidPie->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'UseVariablePresentation' => false, 'Unit' => '°C'],
    ['VariableID' => 4717, 'UseVariablePresentation' => false, 'Unit' => '%']
], JSON_THROW_ON_ERROR));
$invalidPie->ApplyChanges();
assertGatewayGauge($invalidPie->GetTestStatus() === 201, 'Pie must reject sources with unlike units.');
$invalidPie->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPie->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPie->SetTestProperty('IPSViewInnerRadiusPercent', 90);
$invalidPie->ApplyChanges();
assertGatewayGauge($invalidPie->GetTestStatus() === 202, 'Pie must reject invalid independent IPSView geometry.');
$pieSources = [];
for ($index = 0; $index < 16; $index++) {
    $id = 5900 + $index;
    $GLOBALS['symconTestVariables'][$id] = [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000200 + $index,
        'Value'           => (float) $index,
        'Name'            => 'Pie source'
    ];
    $pieSources[] = ['VariableID' => $id, 'UseVariablePresentation' => false, 'Unit' => 'W'];
}
$maximumPie = new EChartsPie();
$maximumPie->Create();
$maximumPie->SetTestProperty('Sources', json_encode($pieSources, JSON_THROW_ON_ERROR));
$maximumPie->ApplyChanges();
assertGatewayGauge(
    $maximumPie->GetTestStatus() === IS_ACTIVE
        && count(json_decode($maximumPie->GetPieData(), true, 512, JSON_THROW_ON_ERROR)['items']) === 16,
    'Pie must preserve all 16 distinct same-name sources.'
);
$pieSources[] = ['VariableID' => 4711, 'UseVariablePresentation' => false, 'Unit' => 'W'];
$maximumPie->SetTestProperty('Sources', json_encode($pieSources, JSON_THROW_ON_ERROR));
$maximumPie->ApplyChanges();
assertGatewayGauge($maximumPie->GetTestStatus() === IS_ACTIVE, 'Pie must accept seventeen sources after the source limit extension.');
for ($index = 0; $index < 16; $index++) {
    unset($GLOBALS['symconTestVariables'][5900 + $index]);
}

require __DIR__ . '/heatmap_metrics.php';
require __DIR__ . '/heatmap_periods.php';

$heatmap = new TestableEChartsHeatmap();
$heatmap->Create();
$heatmap->SetTestCurrentTimestamp(1780004000);
$heatmap->SetTestProperty('SourceVariableID', 4711);
$heatmap->SetTestProperty('UseVariablePresentation', false);
$heatmap->SetTestProperty('Unit', '°C');
$heatmap->ApplyChanges();
$heatmapData = json_decode($heatmap->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $heatmapData['heatmap']['range'] === '1m'
        && count($heatmapData['heatmap']['days']) === 0
        && $heatmapData['heatmap']['windowOffset'] === 0,
    'New Heatmaps must default to the last complete calendar month.'
);
$heatmap->SetTestProperty('Range', '1w');
$datedHeatmap = json_decode($heatmap->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    count($datedHeatmap['heatmap']['days']) === 7
        && $datedHeatmap['heatmap']['days'][0]['date'] === $datedHeatmap['heatmap']['startDate']
        && $datedHeatmap['heatmap']['days'][6]['date'] === $datedHeatmap['heatmap']['endDate'],
    'Seven-day Heatmaps must expose dated rolling rows from the actual archive window.'
);
$heatmapNavigation = new EChartsHeatmap();
$heatmapNavigation->Create();
$heatmapNavigation->SetTestProperty('SourceVariableID', 4711);
$heatmapNavigation->SetTestProperty('Range', '1w');
$heatmapNavigation->ApplyChanges();
$navigationCurrent = json_decode($heatmapNavigation->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
$heatmapTile = $heatmapNavigation->GetVisualizationTile();
assertGatewayGauge(
    preg_match('#/hook/SymconECharts/state/5000/([a-f0-9]{32})#', $heatmapTile, $heatmapChannel) === 1,
    'The Heatmap tile must expose a scoped read-only navigation channel.'
);
$heatmapRequest = ['DataID' => '{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    + EChartsDataProtocol::CreateRequest(EChartsDataProtocol::OPERATION_IPSVIEW_STATE, [
        'InstanceID'    => 5000,
        'Channel'       => $heatmapChannel[1],
        'WindowOffset'  => 1,
        'Output'        => 'tile'
    ]);
$heatmapResponse = EChartsDataProtocol::DecodeResponse(
    $heatmapNavigation->ReceiveData(json_encode($heatmapRequest, JSON_THROW_ON_ERROR)),
    EChartsDataProtocol::OPERATION_IPSVIEW_STATE
);
assertGatewayGauge(
    ($heatmapResponse['Payload']['State']['chart']['heatmap']['windowOffset'] ?? null) === 1
        && ($heatmapResponse['Payload']['State']['chart']['heatmap']['endTimestamp'] ?? PHP_INT_MAX)
            < $navigationCurrent['heatmap']['startTimestamp'],
    'Read-only tile navigation must retrieve the preceding seven-day archive window.'
);
$heatmapRequest['Payload']['Channel'] = str_repeat('0', 32);
assertGatewayGauge(
    $heatmapNavigation->ReceiveData(json_encode($heatmapRequest, JSON_THROW_ON_ERROR)) === '',
    'Heatmap navigation must reject an unrelated channel.'
);
$heatmapHookGateway = new TestableEChartsGateway();
$heatmapHookGateway->Create();
$heatmapHookGateway->ApplyChanges();
IPSModuleStrict::$ChildResponders = [static fn (string $json): string => $heatmapNavigation->ReceiveData($json)];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/hook/SymconECharts/state/5000/' . $heatmapChannel[1];
$_GET['offset'] = '1';
$_GET['output'] = 'tile';
$navigatedHookState = json_decode($heatmapHookGateway->ProcessTestHook(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    ($navigatedHookState['chart']['heatmap']['windowOffset'] ?? null) === 1,
    'The Gateway hook must pass a bounded navigation offset to the Heatmap tile.'
);
$_GET['offset'] = '121';
assertGatewayGauge(
    $heatmapHookGateway->ProcessTestHook() === '' && http_response_code() === 400,
    'The Gateway hook must reject excessive archive navigation offsets.'
);
IPSModuleStrict::$ChildResponders = [];
unset($_SERVER['REQUEST_METHOD'], $_SERVER['SCRIPT_NAME'], $_GET['offset'], $_GET['output']);
$heatmap->SetTestProperty('Range', '1w');
$heatmapData = json_decode($heatmap->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $heatmapData['heatmap']['style']['colorScalePreset'] === 'custom'
        && $heatmapData['heatmap']['style']['colorScaleColors'] === []
        && $heatmapData['heatmap']['style']['customMiddleColors'] === [],
    'Existing Heatmap instances must retain their two-color scale by default.'
);
assertGatewayGauge(
    $heatmap->GetTestStatus() === IS_ACTIVE
        && $heatmap->GetTestReferences() === [4711]
        && $heatmapData['family'] === 'heatmap'
        && $heatmapData['heatmap']['unit'] === '°C'
        && count($heatmapData['cells']) === 1
        && $heatmapData['cells'][0]['value'] === 42.0
        && $heatmapData['cells'][0]['count'] === 2,
    'Heatmap must group completed hourly gateway archive values by local weekday and hour.'
);
$savedHeatmapArchive = $GLOBALS['symconTestArchiveVariables'][4711]['AggregatedValues'];
$savedHeatmapTimezone = date_default_timezone_get();
date_default_timezone_set('Europe/Berlin');
$heatmapZone = new DateTimeZone('Europe/Berlin');
$heatmapArchiveTime = static fn (string $date): int => (new DateTimeImmutable($date, $heatmapZone))->getTimestamp();
$GLOBALS['symconTestArchiveVariables'][4711]['AggregatedValues'] = array_map(
    static fn (array $point): array => ['TimeStamp' => $heatmapArchiveTime($point[0]),
        'Avg'                                       => $point[1], 'Min' => $point[1], 'Max' => $point[1]],
    [['2026-09-28 08:00:00', 30.0], ['2026-03-02 08:00:00', 20.0], ['2025-10-06 08:00:00', 10.0]]
);
$longHeatmap = new TestableEChartsHeatmap();
$longHeatmap->Create();
$longHeatmap->SetTestCurrentTimestamp($heatmapArchiveTime('2026-10-13 12:34:00'));
$longHeatmap->SetTestProperty('SourceVariableID', 4711);
$longHeatmap->SetTestProperty('Range', '12m');
$queryCountBefore = $GLOBALS['symconTestArchiveQueryCount'];
$longHeatmapData = json_decode($longHeatmap->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $longHeatmapData['heatmap']['startDate'] === '2025-10-01'
        && $longHeatmapData['heatmap']['endDate'] === '2026-09-30'
        && $longHeatmapData['cells'] === [[
            'hour' => 8, 'weekday' => 0, 'value' => 20.0, 'count' => 3
        ]]
        && $GLOBALS['symconTestArchiveQueryCount'] - $queryCountBefore >= 6,
    'The twelve-month Heatmap must combine all archive chunks without losing old or recent hours.'
);
$GLOBALS['symconTestArchiveVariables'][4711]['AggregatedValues'] = $savedHeatmapArchive;
$savedDenseArchive = $GLOBALS['symconTestArchiveVariables'][4713];
$denseHeatmapPoints = [];
$denseStart = $heatmapArchiveTime('2025-10-01 00:00:00');
for ($index = 2000; $index >= 0; $index--) {
    $denseHeatmapPoints[] = [
        'TimeStamp' => $denseStart + $index * 3600,
        'Avg'       => 1.0, 'Min' => 1.0, 'Max' => 1.0
    ];
}
$GLOBALS['symconTestArchiveVariables'][4713] = [
    'AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => $denseHeatmapPoints
];
$denseHeatmap = new TestableEChartsHeatmap();
$denseHeatmap->Create();
$denseHeatmap->SetTestCurrentTimestamp($heatmapArchiveTime('2026-10-13 12:34:00'));
$denseHeatmap->SetTestProperty('SourceVariableID', 4713);
$denseHeatmap->SetTestProperty('Range', '12m');
$denseHeatmapData = json_decode($denseHeatmap->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    array_sum(array_column($denseHeatmapData['cells'], 'count')) === 2001
        && $denseHeatmapData['truncated'] === false,
    'More than 2000 hourly archive values must be fully included through multiple Gateway requests.'
);
$savedHeatmapResponder = IPSModuleStrict::$ParentResponder;
IPSModuleStrict::$ParentResponder = static fn (string $json): string => EChartsDataProtocol::EncodeSuccessResponse(
    EChartsDataProtocol::OPERATION_ARCHIVE_READ,
    ['VariableID' => 4713, 'Points' => [], 'Truncated' => true]
);
$truncatedHeatmapRejected = false;
try {
    $denseHeatmap->GetHeatmapData();
} catch (RuntimeException) {
    $truncatedHeatmapRejected = true;
}
IPSModuleStrict::$ParentResponder = $savedHeatmapResponder;
assertGatewayGauge(
    $truncatedHeatmapRejected && $denseHeatmap->GetTestStatus() === 204,
    'A truncated archive chunk must fail visibly rather than publish an incomplete long-range average.'
);
$GLOBALS['symconTestArchiveVariables'][4713] = $savedDenseArchive;
date_default_timezone_set($savedHeatmapTimezone);
$heatmapOutput = new EChartsHeatmap();
$heatmapOutput->Create();
$heatmapForm = json_decode($heatmapOutput->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$heatmapRangeSelect = findGaugeFormElement($heatmapForm['elements'], 'Range');
assertGatewayGauge(
    is_array($heatmapRangeSelect)
        && array_column($heatmapRangeSelect['options'], 'value') === ['1w', '1m', '3m', '6m', '12m', 'ytd', 'custom', 'absolute'],
    'The Heatmap form must preserve six calendar ranges and add custom and fixed historical periods.'
);
$oldRangeHeatmap = new EChartsHeatmap();
$oldRangeHeatmap->Create();
$oldRangeHeatmap->SetTestProperty('SourceVariableID', 4711);
$oldRangeHeatmap->SetTestProperty('Range', '30d');
$oldRangeHeatmap->ApplyChanges();
assertGatewayGauge(
    $oldRangeHeatmap->GetTestStatus() === 202,
    'A previously saved thirty-day value must require an explicit new calendar-range selection.'
);
$heatmapPresetSelect = findGaugeFormElement($heatmapForm['elements'], 'ColorScalePreset');
assertGatewayGauge(
    is_array($heatmapPresetSelect) && count($heatmapPresetSelect['options'] ?? []) === 11,
    'The Heatmap designer must expose the custom design and all ten sourced presets.'
);
assertGatewayGauge(
    findGaugeFormElement($heatmapForm['elements'], 'IPSViewColorScalePreset') !== null,
    'An independent IPSView Heatmap design must expose the same palette selection.'
);
foreach ([1, 2, 3] as $index) {
    $tileMiddle = findGaugeFormElement($heatmapForm['elements'], 'CustomMiddleColor' . $index);
    $ipsViewMiddle = findGaugeFormElement($heatmapForm['elements'], 'IPSViewCustomMiddleColor' . $index);
    assertGatewayGauge(
        $tileMiddle !== null && $ipsViewMiddle !== null
            && str_contains((string) ($tileMiddle['onChange'] ?? ''), 'ECHM_UpdateBarPreviewFromForm')
            && str_contains((string) ($ipsViewMiddle['onChange'] ?? ''), 'ECHM_UpdateBarPreviewFromForm'),
        'Both Heatmap designers must preview each optional custom middle color live.'
    );
}
foreach ($heatmapPresetSelect['options'] as $option) {
    $id = $option['value'];
    $colors = \SymconECharts\HeatmapColorScales::Colors($id);
    $scaleRange = \SymconECharts\HeatmapColorScales::Range($id);
    assertGatewayGauge(
        \SymconECharts\HeatmapColorScales::IsSupported($id)
            && ($id === 'custom' || count($colors) >= 2)
            && count(array_filter($colors, static fn (string $color): bool => preg_match('/^#[0-9A-F]{6}$/', $color) === 1)) === count($colors)
            && ($id === 'custom' ? $scaleRange === null : is_array($scaleRange) && $scaleRange['min'] < $scaleRange['max']),
        'Every published Heatmap preset must have valid canonical colors and fixed Celsius class bounds.'
    );
}
assertGatewayGauge(
    \SymconECharts\HeatmapColorScales::Range('noaa2023') === ['min' => -50, 'max' => 50],
    'The NOAA sample must retain its documented -50 to +50 °C range.'
);
$heatmapTitleAction = (string) (findGaugeFormElement($heatmapForm['elements'], 'Title')['onChange'] ?? '');
assertGatewayGauge(
    str_contains($heatmapTitleAction, 'ECHM_UpdateBarPreviewFromForm')
        && !str_contains($heatmapTitleAction, '$Sources'),
    'A single-source Heatmap preview action must not reference a non-existent Sources list.'
);
$heatmapOutput->UpdateBarPreviewFromForm(json_encode([
    'Title' => 'Draft Heatmap', 'SourceVariableID' => 4713, 'Range' => '1w'
], JSON_THROW_ON_ERROR));
$heatmapPreview = base64_decode(substr(
    (string) array_slice($heatmapOutput->GetTestFormUpdates(), -2)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($heatmapPreview)
        && str_contains($heatmapPreview, 'Draft Heatmap')
        && str_contains($heatmapPreview, 'Living room humidity')
        && str_contains($heatmapPreview, '1 week')
        && preg_match('/<text x="74" y="82"[^>]*>[^<]+ [0-9]{2}\.[0-9]{2}\.<\/text>/', $heatmapPreview) === 1,
    'The unsaved Heatmap preview must follow title, source and range edits without an archive read.'
);
$heatmapOutput->UpdateBarPreviewFromForm(json_encode([
    'Title' => 'Draft Heatmap', 'SourceVariableID' => 4713, 'Range' => '1m'
], JSON_THROW_ON_ERROR));
$thirtyDayPreview = base64_decode(substr(
    (string) array_slice($heatmapOutput->GetTestFormUpdates(), -2)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($thirtyDayPreview) && str_contains($thirtyDayPreview, '1 month')
        && str_contains($thirtyDayPreview, 'Average of hourly means'),
    'The calendar-month form preview must identify its selected interval.'
);
$heatmapOutput->UpdateBarPreviewFromForm(json_encode([
    'ColorScalePreset' => 'tempera6'
], JSON_THROW_ON_ERROR));
$heatmapPresetPreview = base64_decode(substr(
    (string) array_slice($heatmapOutput->GetTestFormUpdates(), -2)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($heatmapPresetPreview)
        && str_contains($heatmapPresetPreview, '#333D8D')
        && str_contains($heatmapPresetPreview, '#E7362D')
        && substr_count($heatmapPresetPreview, 'y="330"') === 6
        && str_contains($heatmapPresetPreview, '-20 °C')
        && str_contains($heatmapPresetPreview, '40 °C')
        && !str_contains($heatmapPresetPreview, '<linearGradient id="scale">'),
    'The Heatmap preview must show six fixed Celsius classes for an unsaved Tempera-6 preset.'
);
$heatmapOutput->UpdateBarPreviewFromForm(json_encode([
    'ColorScalePreset' => 'noaa2023'
], JSON_THROW_ON_ERROR));
$noaaPreview = base64_decode(substr(
    (string) array_slice($heatmapOutput->GetTestFormUpdates(), -2)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($noaaPreview)
        && str_contains($noaaPreview, '-50 °C')
        && str_contains($noaaPreview, '50 °C')
        && substr_count($noaaPreview, 'y="330"') === 20,
    'The NOAA preview must show its source-defined range and 20 classes.'
);
$heatmapOutput->UpdateBarPreviewFromForm(json_encode([
    'ColorScalePreset'   => 'custom', 'LowColor' => 0x111111,
    'CustomMiddleColor1' => 0xEE1111, 'CustomMiddleColor2' => 0x11EE11,
    'CustomMiddleColor3' => 0x1111EE, 'HighColor' => 0xEEEEEE,
    'ShowValues'         => true
], JSON_THROW_ON_ERROR));
$customPreview = base64_decode(substr(
    (string) array_slice($heatmapOutput->GetTestFormUpdates(), -2)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($customPreview)
        && substr_count($customPreview, '<stop offset=') === 5
        && str_contains($customPreview, '#EE1111')
        && str_contains($customPreview, '#11EE11')
        && str_contains($customPreview, '#1111EE')
        && str_contains($customPreview, 'fill="#111111" font-size="10"'),
    'The unsaved custom preview must show five stops and dark text on bright cells.'
);
$heatmapOutput->UpdateBarPreviewFromForm(json_encode([
    'ShowValues' => true, 'ValueColor' => 0xAABBCC
], JSON_THROW_ON_ERROR));
$manualTextPreview = base64_decode(substr(
    (string) array_slice($heatmapOutput->GetTestFormUpdates(), -2)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($manualTextPreview) && str_contains($manualTextPreview, 'fill="#AABBCC" font-size="10"'),
    'An explicitly selected value text color must override the Heatmap preview contrast.'
);
$heatmapOutput->UpdateBarPreviewFromForm(json_encode([
    'IPSViewUseTileDesign' => false, 'IPSViewColorScalePreset' => 'custom',
    'IPSViewLowColor'      => 0x112233, 'IPSViewCustomMiddleColor2' => 0x445566,
    'IPSViewHighColor'     => 0x778899
], JSON_THROW_ON_ERROR));
$independentPreview = base64_decode(substr(
    (string) array_slice($heatmapOutput->GetTestFormUpdates(), -1)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($independentPreview)
        && substr_count($independentPreview, '<stop offset=') === 3
        && str_contains($independentPreview, '#445566'),
    'The independent IPSView preview must use its own configured middle colors.'
);
$heatmap->SetTestProperty('CustomMiddleColor1', 0xEE1111);
$heatmap->SetTestProperty('CustomMiddleColor3', 0x1111EE);
$customData = json_decode($heatmap->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $customData['heatmap']['style']['customMiddleColors'] === ['#EE1111', '#1111EE'],
    'Only configured middle colors must be serialized, in their selected order.'
);
$heatmap->SetTestProperty('ColorScalePreset', 'tempera12');
$presetData = json_decode($heatmap->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $presetData['heatmap']['style']['colorScalePreset'] === 'tempera12'
        && count($presetData['heatmap']['style']['colorScaleColors']) === 12
        && $presetData['heatmap']['style']['colorScaleRange'] === ['min' => -20, 'max' => 40],
    'The selected palette and its fixed temperature range must reach Tile and inherited IPSView.'
);
$heatmap->SetTestProperty('ColorScalePreset', 'noaa2023');
$noaaData = json_decode($heatmap->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $noaaData['heatmap']['style']['colorScaleRange'] === ['min' => -50, 'max' => 50],
    'The NOAA archive values must not redefine the source palette bounds.'
);
$heatmapOutput->SetTestProperty('ColorScalePreset', 'tempera12');
$heatmapPresetTile = $heatmapOutput->GetVisualizationTile();
$heatmapPresetIPSView = $heatmapOutput->GetIPSViewHTML();
assertGatewayGauge(
    strlen($heatmapPresetTile) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($heatmapPresetIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT
        && str_contains($heatmapPresetTile, 'echarts-heatmap-class-legend')
        && str_contains($heatmapPresetIPSView, 'echarts-heatmap-class-legend'),
    'Both Heatmap output routes must include the compact legend and remain below the output-buffer limit.'
);
$heatmap->SetTestProperty('CustomMiddleColor2', 0x1000000);
$heatmap->ApplyChanges();
assertGatewayGauge(
    $heatmap->GetTestStatus() === 202,
    'Invalid custom palette colors must be rejected before rendering.'
);
$heatmap->SetTestProperty('CustomMiddleColor2', -1);
$heatmap->ApplyChanges();
assertGatewayGauge($heatmap->GetTestStatus() === IS_ACTIVE, 'Corrected palette colors must recover the Heatmap.');
$heatmap->SetTestProperty('SourceVariableID', 4712);
$heatmap->ApplyChanges();
assertGatewayGauge(
    $heatmap->GetTestStatus() === 201,
    'Heatmap must reject a non-numeric source variable.'
);

foreach ([
    'function resolveGaugeLayout(preset, width, height, style, headerInset, scaleMultiplier)',
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
    'var scale = Math.min(width / 440, contentHeight / 400) * (scaleMultiplier || 1);',
    'var height = Math.max(chartElement.clientHeight, 160);',
    'center: [layout.centerX, layout.centerY]',
    'radius: layout.radius',
    'distance: layout.tickDistance',
    'distance: layout.splitDistance',
    'distance: layout.labelDistance',
    'offsetCenter: layout.detailOffset',
    'offsetCenter: layout.titleOffset',
    "var titleY = style.titlePosition === 'top' ? definition.titleTopY : definition.titleY;",
    'function applyArcDesign(series, style)',
    'series.startAngle = 90 - startPosition;',
    'function applyPointerShape(series, style, layout)',
    'function resolveCustomPointerGeometry(style, layout)',
    'pointerLength * viewBoxWidth / viewBoxHeight',
    '(1 - (pivotY - minimumY) / viewBoxHeight) * pointerLength',
    "series.pointer.icon = 'path://' + path;",
    "series.anchor.show = shape !== 'custom' || geometry.showAnchor;",
    'function applyAnchorDesign(series, style, layout, colors)',
    "series.anchor.icon = 'path://' + path;",
    "if (shape === 'hidden')",
    "series.anchor.itemStyle.color = 'transparent';",
    'function buildPlateGraphic(style, layout, series, colors)',
    'function buildPlateFill(style, colors)',
    "type: 'radial'",
    'colorStops: colorStops',
    "type: renderCircle ? 'circle' : 'polygon'",
    'fill: fill',
    'stroke: border',
    'graphic: buildPlateGraphic(style, layout, series, colors)',
    'function applyCustomColors(series, style, colors)',
    'function applyAdvancedDesign(series, style, layout, colors)',
    'series.axisLine.lineStyle.color = zones;',
    'series.axisLabel.rotate =',
    'series.clockwise ='
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
$encodedConfigurationForm = json_encode($configurationForm, JSON_THROW_ON_ERROR);
$designerExpansionStates = [];
foreach ($configurationForm['elements'] as $element) {
    $caption = $element['caption'] ?? null;
    if (in_array($caption, ['Tile designer', 'IPSView design'], true)) {
        $designerExpansionStates[$caption] = $element['expanded'] ?? null;
    }
}
assertGatewayGauge(
    $designerExpansionStates === [
        'Tile designer'  => false,
        'IPSView design' => false
    ],
    'Tile and IPSView designers must both start collapsed.'
);
assertGatewayGauge(
    str_contains($encodedConfigurationForm, '"caption":"IPSView design"')
        && str_contains($encodedConfigurationForm, '"name":"EnableIPSView"')
        && str_contains($encodedConfigurationForm, '"name":"IPSViewUseTileDesign"')
        && str_contains($encodedConfigurationForm, '"name":"IPSViewAdaptToBackground"')
        && str_contains($encodedConfigurationForm, '"name":"IPSViewBackgroundColor"')
        && str_contains($encodedConfigurationForm, '"name":"IPSViewBackgroundOpacityPercent"')
        && str_contains($encodedConfigurationForm, '"name":"IPSViewGaugePreset"')
        && str_contains($encodedConfigurationForm, '"name":"IPSViewGaugePreview"')
        && str_contains($encodedConfigurationForm, 'ECGS_UpdateIPSViewGaugePreviewFromForm'),
    'Gauge Single form must expose the helper-backed IPSView output and independent designer.'
);
assertGatewayGauge(
    strlen($encodedConfigurationForm) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'The duplicated IPSView designer must remain below the Symcon output-buffer limit.'
);
assertGatewayGauge(
    str_contains(json_encode($configurationForm, JSON_THROW_ON_ERROR), '$PlateFillMode')
        && str_contains(json_encode($configurationForm, JSON_THROW_ON_ERROR), '$PlateGradientRadiusPercent')
        && str_contains(json_encode($configurationForm, JSON_THROW_ON_ERROR), '$PlateBackgroundEnabled')
        && str_contains(json_encode($configurationForm, JSON_THROW_ON_ERROR), '$PlateBackgroundSVG')
        && str_contains(json_encode($configurationForm, JSON_THROW_ON_ERROR), '$PlateBackgroundRotation')
        && str_contains(json_encode($configurationForm, JSON_THROW_ON_ERROR), '$ScaleZones')
        && str_contains(json_encode($configurationForm, JSON_THROW_ON_ERROR), '$GaugeRadiusPercent')
        && str_contains(json_encode($configurationForm, JSON_THROW_ON_ERROR), '$DetailBoxVisibility')
        && str_contains(json_encode($configurationForm, JSON_THROW_ON_ERROR), '$UseVariablePresentation')
        && str_contains(json_encode($configurationForm, JSON_THROW_ON_ERROR), '$TitlePosition')
        && str_contains(
            json_encode($configurationForm, JSON_THROW_ON_ERROR),
            'ECGS_UpdateGaugePreviewFromForm'
        ),
    'Gauge Single form preview actions must forward the complete advanced design state.'
);
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
assertGatewayGauge(
    str_contains($initialPreviewSvg, 'data-anchor-shape="ring"')
        && str_contains($initialPreviewSvg, 'r="13.75"')
        && str_contains($initialPreviewSvg, 'style="fill:none"')
        && str_contains($initialPreviewSvg, 'fill:#778899;stroke:#8899AA;stroke-width:1.5'),
    'Gauge Single preview must apply the configured hub design.'
);
assertGatewayGauge(
    str_contains($initialPreviewSvg, 'data-plate-shape="arc"')
        && str_contains($initialPreviewSvg, 'class="plate plate-shadow"')
        && str_contains($initialPreviewSvg, '<radialGradient id="plate-fill-gradient" cx="40%" cy="35%" r="85%">')
        && str_contains($initialPreviewSvg, '<stop offset="0%" stop-color="#202830"/>')
        && str_contains($initialPreviewSvg, '<stop offset="50%" stop-color="#405060"/>')
        && str_contains($initialPreviewSvg, '<stop offset="100%" stop-color="#607080"/>')
        && str_contains($initialPreviewSvg, 'fill:url(#plate-fill-gradient);stroke:#90A0B0;stroke-width:1.5'),
    'Gauge Single preview must draw the configured radial-gradient dial plate behind the Gauge.'
);
assertGatewayGauge(str_contains($initialPreviewSvg, '#FEF8EF'), 'Gauge Single preview must apply the Vintage background.');
assertGatewayGauge(str_contains($initialPreviewSvg, 'Room climate'), 'Gauge Single preview must contain the title.');
assertGatewayGauge(
    str_contains($initialPreviewSvg, 'y="28.00" class="title"'),
    'Gauge Single preview must place the title at the selected top position.'
);
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

$gauge->UpdateGaugePreviewFromForm(json_encode([
    'SourceVariableID'        => 4711,
    'Minimum'                 => -20.0,
    'Maximum'                 => 80.0,
    'Title'                   => 'Advanced preview',
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'GaugePreset'             => 'simple',
    'EChartsTheme'            => 'dark',
    'ScaleZonesEnabled'       => true,
    'ScaleZones'              => '[{"EndValue":0,"Color":65280},{"EndValue":50,"Color":16776960}]',
    'MajorSplitCount'         => 4,
    'MinorSplitCount'         => 2,
    'GaugeOffsetXPercent'     => 10,
    'GaugeOffsetYPercent'     => 5,
    'PointerVisibility'       => 'hide',
    'ProgressVisibility'      => 'hide',
    'DetailBoxVisibility'     => 'show',
    'DetailColorMode'         => 'custom',
    'DetailBackgroundColor'   => 0x102030,
    'DetailBorderColor'       => 0x405060,
    'DetailShadow'            => true
], JSON_THROW_ON_ERROR));
$formUpdates = $gauge->GetTestFormUpdates();
$advancedPreviewUpdate = end($formUpdates);
$advancedPreviewUri = is_array($advancedPreviewUpdate) ? (string) ($advancedPreviewUpdate['Value'] ?? '') : '';
$advancedPreviewSvg = base64_decode(
    substr($advancedPreviewUri, strlen('data:image/svg+xml;base64,')),
    true
);
assertGatewayGauge(
    is_string($advancedPreviewSvg)
        && str_contains($advancedPreviewSvg, 'data-major-splits="4"')
        && str_contains($advancedPreviewSvg, 'translate(20.00 0)')
        && str_contains($advancedPreviewSvg, '#00FF00')
        && str_contains($advancedPreviewSvg, '#FFFF00')
        && str_contains($advancedPreviewSvg, 'fill:#102030')
        && str_contains($advancedPreviewSvg, 'detail-shadow')
        && !str_contains($advancedPreviewSvg, 'data-pointer-shape='),
    'Gauge Single advanced form preview must apply zones, layout, visibility and value-box styling.'
);

$gauge->UpdateGaugePreviewFromForm(json_encode([
    'SourceVariableID'        => 4715,
    'UseVariablePresentation' => true,
    'Minimum'                 => 100.0,
    'Maximum'                 => 0.0,
    'Title'                   => 'Presented pressure',
    'TitlePosition'           => 'top',
    'Unit'                    => 'manual',
    'Decimals'                => 6,
    'GaugePreset'             => 'simple',
    'EChartsTheme'            => 'dark'
], JSON_THROW_ON_ERROR));
$formUpdates = $gauge->GetTestFormUpdates();
$presentedPreviewUpdate = end($formUpdates);
$presentedPreviewUri = is_array($presentedPreviewUpdate)
    ? (string) ($presentedPreviewUpdate['Value'] ?? '')
    : '';
$presentedPreviewSvg = base64_decode(
    substr($presentedPreviewUri, strlen('data:image/svg+xml;base64,')),
    true
);
assertGatewayGauge(
    is_string($presentedPreviewSvg)
        && str_contains($presentedPreviewSvg, '1,013.3 hPa')
        && str_contains($presentedPreviewSvg, 'y="28.00" class="title"'),
    'Gauge Single form preview must apply the selected variable presentation and title position.'
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
assertGatewayGauge(($gaugeData['source']['variableID'] ?? null) === 4711, 'Gauge Single must identify its source by variable ID.');
assertGatewayGauge(($gaugeData['value'] ?? null) === 42.5, 'Gauge Single did not receive the source value.');
assertGatewayGauge(($gaugeData['source']['timestamp'] ?? null) === 1780000000, 'Gauge Single timestamp changed.');
assertGatewayGauge(($gaugeData['gauge']['minimum'] ?? null) === -20.0, 'Gauge Single minimum changed.');
assertGatewayGauge(($gaugeData['gauge']['maximum'] ?? null) === 80.0, 'Gauge Single maximum changed.');
assertGatewayGauge(($gaugeData['gauge']['preset'] ?? null) === 'progress', 'Gauge Single preset changed.');
assertGatewayGauge(
    ($gaugeData['gauge']['style'] ?? null) === [
        'animationEnabled'            => true,
        'animationDuration'           => 500,
        'animationDurationUpdate'     => 500,
        'animationEasing'             => 'cubicInOut',
        'animationEasingUpdate'       => 'cubicInOut',
        'animationDelay'              => 0,
        'animationDelayUpdate'        => 0,
        'scaleFontSizePercent'        => 150,
        'valueFontSizePercent'        => 125,
        'unitFontSizePercent'         => 75,
        'titleFontSizePercent'        => 110,
        'ringWidthPercent'            => 120,
        'pointerWidthPercent'         => 80,
        'pointerLengthPercent'        => 130,
        'anchorSizePercent'           => 125,
        'anchorBorderWidthPercent'    => 75,
        'plateSizePercent'            => 125,
        'plateBorderWidthPercent'     => 75,
        'minorTickLengthPercent'      => 75,
        'majorTickLengthPercent'      => 150,
        'progressWidthPercent'        => 100,
        'minorTickDistancePercent'    => 100,
        'majorTickDistancePercent'    => 100,
        'scaleLabelDistancePercent'   => 100,
        'gaugeRadiusPercent'          => 110,
        'detailBorderWidthPercent'    => 100,
        'detailCornerRadiusPercent'   => 100,
        'pointerShape'                => 'arrow',
        'titlePosition'               => 'top',
        'anchorShape'                 => 'ring',
        'anchorColorMode'             => 'custom',
        'plateShape'                  => 'arc',
        'plateColorMode'              => 'custom',
        'plateFillMode'               => 'radial',
        'plateGradientMiddleEnabled'  => true,
        'plateGradientDirection'      => 'diagonal-down',
        'plateGradientCenterXPercent' => 40,
        'plateGradientCenterYPercent' => 35,
        'plateGradientRadiusPercent'  => 85,
        'plateTransparent'            => false,
        'plateShadow'                 => true,
        'arcMode'                     => 'custom',
        'startPosition'               => 270.0,
        'endPosition'                 => 67.5,
        'colorMode'                   => 'custom',
        'scaleZonesEnabled'           => true,
        'scaleZones'                  => [[0.2, '#00FF00'], [0.7, '#FFFF00']],
        'majorSplitCount'             => 8,
        'minorSplitCount'             => 4,
        'gaugeOffsetXPercent'         => 5,
        'gaugeOffsetYPercent'         => -5,
        'valueOffsetXPercent'         => 10,
        'valueOffsetYPercent'         => -10,
        'titleOffsetXPercent'         => -10,
        'titleOffsetYPercent'         => 10,
        'scaleLabelRotation'          => 'radial',
        'gaugeDirection'              => 'counterclockwise',
        'pointerVisibility'           => 'show',
        'progressVisibility'          => 'hide',
        'ringVisibility'              => 'preset',
        'minorTicksVisibility'        => 'preset',
        'majorTicksVisibility'        => 'preset',
        'scaleLabelsVisibility'       => 'preset',
        'valueVisibility'             => 'preset',
        'unitVisibility'              => 'preset',
        'titleVisibility'             => 'preset',
        'detailBoxVisibility'         => 'show',
        'detailColorMode'             => 'custom',
        'detailShadow'                => false,
        'pointerShadow'               => true,
        'progressShadow'              => false,
        'ringShadow'                  => false,
        'anchorShadow'                => false,
        'pointerColor'                => '#112233',
        'progressColor'               => '#223344',
        'ringColor'                   => '#334455',
        'scaleColor'                  => '#445566',
        'valueColor'                  => '#556677',
        'titleColor'                  => '#667788',
        'anchorColor'                 => '#778899',
        'anchorBorderColor'           => '#8899AA',
        'plateColor'                  => '#202830',
        'plateBorderColor'            => '#90A0B0',
        'plateGradientMiddleColor'    => '#405060',
        'plateGradientEndColor'       => '#607080',
        'detailBackgroundColor'       => '#101820',
        'detailBorderColor'           => '#708090'
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

$presentedGauge = new EChartsGaugeSingle();
$presentedGauge->Create();
$presentedGauge->SetTestProperty('SourceVariableID', 4715);
$presentedGauge->SetTestProperty('UseVariablePresentation', true);
$presentedGauge->SetTestProperty('Minimum', 100.0);
$presentedGauge->SetTestProperty('Maximum', 0.0);
$presentedGauge->SetTestProperty('Unit', 'manual');
$presentedGauge->SetTestProperty('Decimals', 6);
$presentedGauge->ApplyChanges();
assertGatewayGauge(
    $presentedGauge->GetTestStatus() === IS_ACTIVE,
    'A valid source-variable presentation must override an invalid manual Gauge range.'
);
$presentedData = json_decode($presentedGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    ($presentedData['gauge']['minimum'] ?? null) === 950.0
        && ($presentedData['gauge']['maximum'] ?? null) === 1050.0
        && ($presentedData['gauge']['unit'] ?? null) === 'hPa'
        && ($presentedData['gauge']['decimals'] ?? null) === 1,
    'Gauge Single must inherit range, unit and decimals from a modern source-variable presentation.'
);

$legacyPresentedGauge = new EChartsGaugeSingle();
$legacyPresentedGauge->Create();
$legacyPresentedGauge->SetTestProperty('SourceVariableID', 4716);
$legacyPresentedGauge->SetTestProperty('UseVariablePresentation', true);
$legacyPresentedGauge->ApplyChanges();
$legacyPresentedData = json_decode($legacyPresentedGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    ($legacyPresentedData['gauge']['minimum'] ?? null) === 0.0
        && ($legacyPresentedData['gauge']['maximum'] ?? null) === 50.0
        && ($legacyPresentedData['gauge']['unit'] ?? null) === 'm/s'
        && ($legacyPresentedData['gauge']['decimals'] ?? null) === 2,
    'Gauge Single must inherit range, unit and decimals through a legacy variable profile.'
);

$emptyMultiGauge = new EChartsGaugeMulti();
$emptyMultiGauge->Create();
$emptyMultiForm = json_decode($emptyMultiGauge->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$emptyMultiPreview = findGaugeFormElement($emptyMultiForm['elements'], 'GaugePreview');
$emptyMultiSvg = base64_decode(substr(
    (string) ($emptyMultiPreview['image'] ?? ''),
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($emptyMultiSvg)
        && str_contains($emptyMultiSvg, 'Configure 2 to 32 unique numeric sources.')
        && !str_contains($emptyMultiSvg, 'Temperature'),
    'A new Gauge Multi instance must show a configuration hint instead of unmarked example gauges.'
);

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
$multiGauge->SetTestProperty('EnableIPSView', true);
$multiGauge->ApplyChanges();
assertTransientRetry($multiGauge, 'Gauge Multi');

assertGatewayGauge($multiGauge->GetTestStatus() === IS_ACTIVE, 'Gauge Multi must become active with two valid sources.');
assertGatewayGauge($multiGauge->GetTestReferences() === [4711, 4713], 'Gauge Multi must register every source reference.');
assertGatewayGauge($multiGauge->GetTestSummary() === '2 sources', 'Gauge Multi summary must identify its source count.');
assertGatewayGauge($multiGauge->GetTestVisualizationType() === 1, 'Gauge Multi must expose an HTML-SDK tile.');
assertGatewayGauge(
    is_string($multiGauge->GetTestVariableValue('IPSViewGauge'))
        && str_contains($multiGauge->GetTestVariableValue('IPSViewGauge'), '"mode":"ipsview"'),
    'Enabled Gauge Multi IPSView output must maintain and populate its WebContent variable.'
);
assertGatewayGauge(
    in_array(['SenderID' => 4711, 'Message' => VM_UPDATE], $multiGauge->GetTestMessages(), true)
        && in_array(['SenderID' => 4713, 'Message' => VM_UPDATE], $multiGauge->GetTestMessages(), true),
    'Gauge Multi must subscribe to every source value update.'
);

$multiData = json_decode($multiGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(($multiData['schemaVersion'] ?? null) === 1, 'Gauge Multi data schema version changed.');
assertGatewayGauge(($multiData['family'] ?? null) === 'gauge', 'Gauge Multi data family changed.');
assertGatewayGauge(($multiData['variant'] ?? null) === 'multi', 'Gauge Multi variant is missing.');
assertGatewayGauge(($multiData['theme'] ?? null) === 'auto', 'Gauge Multi default theme changed.');
assertGatewayGauge(($multiData['gauge']['title'] ?? null) === 'Room climate', 'Gauge Multi title changed.');
assertGatewayGauge(($multiData['gauge']['preset'] ?? null) === 'multi-title', 'Gauge Multi preset changed.');
assertGatewayGauge(count($multiData['items'] ?? []) === 2, 'Gauge Multi must return every configured source.');
assertGatewayGauge(($multiData['items'][0]['id'] ?? null) === 'variable-4711', 'Gauge Multi item ID changed.');
assertGatewayGauge(($multiData['items'][0]['gauge']['label'] ?? null) === 'Temperature', 'Gauge Multi label changed.');
assertGatewayGauge(($multiData['items'][0]['value'] ?? null) === 42.5, 'Gauge Multi first value changed.');
assertGatewayGauge(
    ($multiData['items'][1]['gauge']['label'] ?? null) === 'Living room humidity',
    'Gauge Multi must fall back to the variable name for an empty label.'
);
assertGatewayGauge(($multiData['items'][1]['value'] ?? null) === 58.0, 'Gauge Multi second value changed.');

$duplicateGaugeSources = json_decode($multiSources, true, 512, JSON_THROW_ON_ERROR);
foreach ($duplicateGaugeSources as &$duplicateGaugeSource) {
    $duplicateGaugeSource['Label'] = 'Sensor';
}
unset($duplicateGaugeSource);
foreach ([EChartsGaugeMulti::class, EChartsGaugeTacho::class, EChartsGaugeChronograph::class] as $gaugeClass) {
    $duplicateGauge = new $gaugeClass();
    $duplicateGauge->Create();
    $duplicateGauge->SetTestProperty('Sources', json_encode($duplicateGaugeSources, JSON_THROW_ON_ERROR));
    $duplicateGauge->ApplyChanges();
    $duplicateGaugeData = json_decode($duplicateGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        array_column($duplicateGaugeData['items'], 'id') === ['variable-4711', 'variable-4713']
            && array_column(array_column($duplicateGaugeData['items'], 'gauge'), 'label') === [
                'Sensor', 'Sensor'
            ],
        $gaugeClass . ' must retain source IDs while displaying equal names without ID suffixes.'
    );
}

$designedMultiGauge = new EChartsGaugeMulti();
$designedMultiGauge->Create();
$designedMultiGauge->SetTestProperty('Sources', $multiSources);
$designedMultiGauge->SetTestProperty('GaugePreset', 'weather-station');
$designedMultiGauge->SetTestProperty('PointerShape', 'arrow');
$designedMultiGauge->SetTestProperty('AnchorShape', 'ring');
$designedMultiGauge->SetTestProperty('PointerWidthPercent', 140);
$designedMultiGauge->SetTestProperty('PointerLengthPercent', 120);
$designedMultiGauge->SetTestProperty('AnchorSizePercent', 110);
$designedMultiGauge->SetTestProperty('MajorSplitCount', 12);
$designedMultiGauge->SetTestProperty('MinorSplitCount', 3);
$designedMultiGauge->SetTestProperty('GaugeColorMode', 'custom');
$designedMultiGauge->SetTestProperty('PointerColor', 0x112233);
$designedMultiGauge->SetTestProperty('PlateDesignMode', 'custom');
$designedMultiGauge->SetTestProperty('PlateSizePercent', 115);
$designedMultiGauge->SetTestProperty('PlateColor', 0x223344);
$designedMultiGauge->SetTestProperty('EnableIPSView', true);
$designedMultiGauge->SetTestProperty('IPSViewUseTileDesign', false);
$designedMultiGauge->SetTestProperty('IPSViewPointerShape', 'line');
$designedMultiGauge->SetTestProperty('IPSViewPlateDesignMode', 'hidden');
$designedMultiGauge->ApplyChanges();
assertGatewayGauge($designedMultiGauge->GetTestStatus() === IS_ACTIVE, 'Gauge Multi must accept the shared design layer.');
$designedMultiData = json_decode($designedMultiGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    ($designedMultiData['gauge']['style']['pointerShape'] ?? null) === 'arrow'
        && ($designedMultiData['gauge']['style']['anchorShape'] ?? null) === 'ring'
        && ($designedMultiData['gauge']['style']['pointerWidthPercent'] ?? null) === 140
        && ($designedMultiData['gauge']['style']['pointerLengthPercent'] ?? null) === 120
        && ($designedMultiData['gauge']['style']['majorSplitCount'] ?? null) === 12
        && ($designedMultiData['gauge']['style']['minorSplitCount'] ?? null) === 3
        && ($designedMultiData['gauge']['style']['colorMode'] ?? null) === 'custom'
        && ($designedMultiData['gauge']['style']['pointerColor'] ?? null) === '#112233'
        && ($designedMultiData['gauge']['style']['plateMode'] ?? null) === 'custom'
        && ($designedMultiData['gauge']['style']['plateColor'] ?? null) === '#223344',
    'Gauge Multi must expose the complete shared design in its chart model.'
);
assertGatewayGauge(
    str_contains($designedMultiGauge->GetVisualizationTile(), '"pointerShape":"arrow"')
        && str_contains($designedMultiGauge->GetVisualizationTile(), '"plateMode":"custom"')
        && str_contains($designedMultiGauge->GetIPSViewHTML(), '"pointerShape":"line"')
        && str_contains($designedMultiGauge->GetIPSViewHTML(), '"plateMode":"hidden"'),
    'Tile and independent IPSView outputs must keep separate shared Gauge Multi designs.'
);

$multiPointerSvg = file_get_contents(__DIR__ . '/fixtures/gauge-pointer-ornate.svg');
assertGatewayGauge(is_string($multiPointerSvg), 'The shared SVG pointer fixture must be readable.');
$multiPlateSvg = '<svg viewBox="0 0 200 100"><rect width="200" height="100" fill="#123456"/></svg>';
$svgMultiGauge = new EChartsGaugeMulti();
$svgMultiGauge->Create();
$svgMultiGauge->SetTestProperty('Sources', $multiSources);
$svgMultiGauge->SetTestProperty('PointerShape', 'custom');
$svgMultiGauge->SetTestProperty('CustomPointerSVG', $multiPointerSvg);
$svgMultiGauge->SetTestProperty('CustomPointerPivotMode', 'custom');
$svgMultiGauge->SetTestProperty('CustomPointerPivotXPercent', 40.0);
$svgMultiGauge->SetTestProperty('CustomPointerPivotYPercent', 90.0);
$svgMultiGauge->SetTestProperty('PlateDesignMode', 'custom');
$svgMultiGauge->SetTestProperty('PlateBackgroundEnabled', true);
$svgMultiGauge->SetTestProperty('PlateBackgroundSVG', $multiPlateSvg);
$svgMultiGauge->SetTestProperty('PlateBackgroundFit', 'contain');
$svgMultiGauge->SetTestProperty('PlateBackgroundSizePercent', 125);
$svgMultiGauge->SetTestProperty('PlateBackgroundOpacityPercent', 60);
$svgMultiGauge->ApplyChanges();
assertGatewayGauge($svgMultiGauge->GetTestStatus() === IS_ACTIVE, 'Gauge Multi must accept shared SVG design assets.');
$svgMultiData = json_decode($svgMultiGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    ($svgMultiData['gauge']['style']['pointerShape'] ?? null) === 'custom'
        && ($svgMultiData['gauge']['style']['pointerViewBox'] ?? null) === '22 0 56 397'
        && ($svgMultiData['gauge']['style']['pointerPivotX'] ?? null) === 44.4
        && ($svgMultiData['gauge']['style']['pointerPivotY'] ?? null) === 357.3
        && ($svgMultiData['gauge']['style']['plateBackgroundFit'] ?? null) === 'contain'
        && ($svgMultiData['gauge']['style']['plateBackgroundAspectRatio'] ?? null) === 2.0
        && str_starts_with(
            (string) ($svgMultiData['gauge']['style']['plateBackgroundImage'] ?? ''),
            'data:image/svg+xml;base64,'
        ),
    'Gauge Multi must expose sanitized SVG pointer and plate assets through the shared design contract.'
);
assertGatewayGauge(
    str_contains($svgMultiGauge->GetVisualizationTile(), 'function resolveCustomPointerGeometry')
        && str_contains($svgMultiGauge->GetVisualizationTile(), 'custom-plate-background-'),
    'Gauge Multi runtime must render shared SVG pointer and plate assets.'
);
$svgMultiForm = json_decode($svgMultiGauge->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$svgMultiPreview = (string) (findGaugeFormElement($svgMultiForm['elements'], 'GaugePreview')['image'] ?? '');
$svgMultiPreviewSource = base64_decode(
    substr($svgMultiPreview, strlen('data:image/svg+xml;base64,')),
    true
);
assertGatewayGauge(
    is_string($svgMultiPreviewSource)
        && str_contains($svgMultiPreviewSource, 'data-pointer-shape="custom"')
        && str_contains($svgMultiPreviewSource, '<image href="data:image/svg+xml;base64,'),
    'Gauge Multi SVG assets must be visible in the configuration preview.'
);

$ringGauge = new EChartsGaugeMulti();
$ringGauge->Create();
$ringGauge->SetTestProperty('Sources', $multiSources);
$ringGauge->SetTestProperty('EnableIPSView', true);
foreach (['ring-grid', 'ring-concentric', 'weather-station'] as $ringPreset) {
    $ringGauge->SetTestProperty('GaugePreset', $ringPreset);
    $ringGauge->ApplyChanges();
    assertGatewayGauge($ringGauge->GetTestStatus() === IS_ACTIVE, 'Gauge Multi must accept all additional presets.');
    $ringData = json_decode($ringGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        ($ringData['gauge']['preset'] ?? null) === $ringPreset
            && ($ringData['items'][0]['gauge']['minimum'] ?? null) === -20.0
            && ($ringData['items'][1]['gauge']['maximum'] ?? null) === 100.0,
        'Ring presets must preserve the per-source Gauge contract.'
    );
    assertGatewayGauge(
        str_contains($ringGauge->GetVisualizationTile(), '"preset":"' . $ringPreset . '"')
            && str_contains($ringGauge->GetIPSViewHTML(), '"preset":"' . $ringPreset . '"'),
        'Both output adapters must receive the selected ring preset.'
    );
    $ringForm = json_decode($ringGauge->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $ringPreview = findGaugeFormElement($ringForm['elements'], 'GaugePreview')['image'] ?? '';
    assertGatewayGauge(
        is_string($ringPreview) && str_starts_with($ringPreview, 'data:image/svg+xml;base64,')
            && str_contains(
                (string) base64_decode(substr($ringPreview, strlen('data:image/svg+xml;base64,')), true),
                'data-preset="' . $ringPreset . '"'
            ),
        'Additional presets must provide an SVG form preview.'
    );
}
$ringGauge->SetTestProperty('IPSViewUseTileDesign', false);
$ringGauge->SetTestProperty('IPSViewGaugePreset', 'ring-grid');
assertGatewayGauge(
    str_contains($ringGauge->GetVisualizationTile(), '"preset":"weather-station"')
        && str_contains($ringGauge->GetIPSViewHTML(), '"preset":"ring-grid"'),
    'An independent IPSView preset must not change the tile preset.'
);

$individualSources = json_decode($multiSources, true, 512, JSON_THROW_ON_ERROR);
$individualSources[0] = array_merge($individualSources[0], [
    'UseIndividualDesign'        => true,
    'PointerShape'               => 'custom',
    'CustomPointerSVG'           => $multiPointerSvg,
    'CustomPointerPivotMode'     => 'custom',
    'CustomPointerPivotXPercent' => 40.0,
    'CustomPointerPivotYPercent' => 90.0,
    'AnchorShape'                => 'custom',
    'CustomAnchorSVG'            => $multiPointerSvg,
    'PlateDesignMode'            => 'custom',
    'PlateBackgroundEnabled'     => true,
    'PlateBackgroundSVG'         => $multiPlateSvg,
    'PlateBackgroundFit'         => 'contain',
    'IPSViewUseTileDesign'       => false,
    'IPSViewPointerShape'        => 'line',
    'IPSViewPlateDesignMode'     => 'hidden'
]);
foreach ([
    EChartsGaugeTacho::class       => ['preset' => 'tacho', 'variant' => 'tacho'],
    EChartsGaugeChronograph::class => ['preset' => 'chronograph', 'variant' => 'chronograph']
] as $dedicatedClass => $contract) {
    $dedicatedGauge = new $dedicatedClass();
    $dedicatedGauge->Create();
    $dedicatedGauge->SetTestProperty('Sources', json_encode($individualSources, JSON_THROW_ON_ERROR));
    $dedicatedGauge->SetTestProperty('AnchorShape', 'custom');
    $dedicatedGauge->SetTestProperty('CustomAnchorSVG', $multiPointerSvg);
    $dedicatedGauge->SetTestProperty('IPSViewUseTileDesign', false);
    $dedicatedGauge->SetTestProperty('IPSViewAdaptToBackground', true);
    $dedicatedGauge->SetTestProperty('IPSViewBackgroundColor', 0x6B4423);
    $dedicatedGauge->SetTestProperty('IPSViewBackgroundOpacityPercent', 35);
    $dedicatedGauge->SetTestProperty('IPSViewAnchorShape', 'custom');
    $dedicatedGauge->SetTestProperty('IPSViewCustomAnchorSVG', $multiPointerSvg);
    $dedicatedGauge->ApplyChanges();
    assertTransientRetry($dedicatedGauge, $dedicatedClass);
    assertGatewayGauge($dedicatedGauge->GetTestStatus() === IS_ACTIVE, $dedicatedClass . ' must accept two sources.');
    $dedicatedData = json_decode($dedicatedGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        ($dedicatedData['variant'] ?? null) === $contract['variant']
            && ($dedicatedData['gauge']['preset'] ?? null) === $contract['preset']
            && ($dedicatedData['gauge']['style']['anchorPath'] ?? null) !== ''
            && ($dedicatedData['gauge']['style']['anchorViewBox'] ?? null) === '22 0 56 397',
        $dedicatedClass . ' must expose its fixed layout contract.'
    );
    assertGatewayGauge(
        ($dedicatedData['items'][0]['style']['pointerShape'] ?? null) === 'custom'
            && ($dedicatedData['items'][0]['style']['pointerViewBox'] ?? null) === '22 0 56 397'
            && ($dedicatedData['items'][0]['style']['anchorShape'] ?? null) === 'custom'
            && ($dedicatedData['items'][0]['style']['anchorViewBox'] ?? null) === '22 0 56 397'
            && ($dedicatedData['items'][0]['style']['plateBackgroundFit'] ?? null) === 'contain'
            && ($dedicatedData['items'][1]['style'] ?? null) === [],
        $dedicatedClass . ' must attach individual SVG design only to the configured source.'
    );
    if ($dedicatedClass === EChartsGaugeTacho::class) {
        $fivePreviewItems = array_fill(0, 5, $dedicatedData['items'][1]);
        $fiveGaugePreview = \SymconECharts\EChartsGaugeTachoPreview::CreateSvg(
            $fivePreviewItems,
            '',
            'auto',
            'tacho',
            []
        );
        assertGatewayGauge(
            substr_count($fiveGaugePreview, 'r="82.08"') === 4
                && str_contains($fiveGaugePreview, 'cx="125.00" cy="110.00"')
                && str_contains($fiveGaugePreview, 'cx="595.00" cy="300.00"'),
            'Gauge Tacho preview must give all four secondary instruments the same useful size.'
        );
    }
    assertGatewayGauge(
        str_contains($dedicatedGauge->GetVisualizationTile(), 'Object.assign({}, style, items[index].style || {})'),
        $dedicatedClass . ' renderer must merge source-specific design settings.'
    );
    assertGatewayGauge(
        str_contains($dedicatedGauge->GetVisualizationTile(), 'opacityFromPercent')
            && str_contains($dedicatedGauge->GetIPSViewHTML(), 'opacityFromPercent')
            && strpos($dedicatedGauge->GetVisualizationTile(), 'function opacityFromPercent(')
                < strpos($dedicatedGauge->GetVisualizationTile(), 'window.SYMC_ECHARTS_DESIGN'),
        $dedicatedClass . ' must embed shared design utilities in Tile and IPSView.'
    );
    assertGatewayGauge(
        str_contains($dedicatedGauge->GetVisualizationTile(), '"pointerShape":"custom"')
            && str_contains($dedicatedGauge->GetIPSViewHTML(), '"pointerShape":"line"')
            && str_contains($dedicatedGauge->GetIPSViewHTML(), '"plateMode":"hidden"')
            && str_contains($dedicatedGauge->GetIPSViewHTML(), '"adaptToBackground":true')
            && str_contains($dedicatedGauge->GetIPSViewHTML(), 'html, body { background:transparent; }')
            && str_contains(
                $dedicatedGauge->GetIPSViewHTML(),
                '#echarts-gauge-root { background:color-mix(in srgb, #6B4423 35%, transparent); }'
            ),
        $dedicatedClass . ' must keep source-specific tile and IPSView designs independent.'
    );
    $dedicatedForm = json_decode($dedicatedGauge->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $dedicatedFormJson = json_encode($dedicatedForm, JSON_THROW_ON_ERROR);
    $dedicatedSources = findGaugeFormElement($dedicatedForm['elements'], 'Sources');
    $dedicatedPointer = findGaugeFormElement($dedicatedForm['elements'], 'PointerShape');
    $dedicatedIPSViewPointer = findGaugeFormElement($dedicatedForm['elements'], 'IPSViewPointerShape');
    $dedicatedPrefix = $dedicatedClass === EChartsGaugeTacho::class ? 'ECGT' : 'ECGC';
    assertGatewayGauge(
        strlen($dedicatedFormJson) < SYMCON_OUTPUT_BUFFER_LIMIT
            &&
        str_contains($dedicatedFormJson, '"form":[')
            && str_contains($dedicatedFormJson, 'UseIndividualDesign')
            && str_contains($dedicatedFormJson, 'CustomPointerSVG')
            && str_contains($dedicatedFormJson, 'CustomAnchorSVG')
            && str_contains($dedicatedFormJson, 'PlateBackgroundSVG')
            && str_contains($dedicatedFormJson, 'IPSViewAdaptToBackground')
            && str_contains($dedicatedFormJson, 'IPSViewBackgroundColor')
            && str_contains($dedicatedFormJson, 'IPSViewBackgroundOpacityPercent')
            && str_contains($dedicatedFormJson, '_UpdateGaugePreviewSourceFromForm')
            && !str_contains($dedicatedFormJson, '"name":"GaugePreset"')
            && !str_contains($dedicatedFormJson, '"name":"IPSViewGaugePreset"'),
        $dedicatedClass . ' source editor must offer individual pointer and dial SVG configuration.'
    );
    assertGatewayGauge(
        is_array($dedicatedSources)
            && str_starts_with($dedicatedSources['onAdd'] ?? '', $dedicatedPrefix . '_UpdateGaugePreviewSourceFromForm(')
            && str_ends_with($dedicatedSources['onDelete'] ?? '', "]), 'delete');")
            && is_array($dedicatedPointer)
            && str_starts_with($dedicatedPointer['onChange'] ?? '', $dedicatedPrefix . '_UpdateGaugePreviewFromForm(')
            && !str_contains($dedicatedPointer['onChange'] ?? '', "'GaugePreset' =>")
            && is_array($dedicatedIPSViewPointer)
            && ($dedicatedIPSViewPointer['onChange'] ?? null) === ($dedicatedPointer['onChange'] ?? null),
        $dedicatedClass . ' must update both previews without referencing a removed preset selector.'
    );
    $editedSource = $individualSources[0];
    $editedSource['PointerShape'] = 'arrow';
    $dedicatedGauge->UpdateGaugePreviewSourceFromForm(json_encode([
        'Sources'              => $editedSource,
        'Title'                => 'Edited instrument',
        'EChartsTheme'         => 'auto',
        'IPSViewUseTileDesign' => false,
        'IPSViewEChartsTheme'  => 'auto'
    ], JSON_THROW_ON_ERROR), 'edit');
    $sourcePreviewUpdates = array_slice($dedicatedGauge->GetTestFormUpdates(), -2);
    $sourceTilePreview = base64_decode(
        substr((string) ($sourcePreviewUpdates[0]['Value'] ?? ''), strlen('data:image/svg+xml;base64,')),
        true
    );
    assertGatewayGauge(
        is_string($sourceTilePreview)
            && str_contains($sourceTilePreview, '<polygon points="')
            && str_contains($sourceTilePreview, 'data-anchor-shape="custom"'),
        $dedicatedClass . ' must refresh its preview when an individual source design is confirmed.'
    );
}
$sixSources = [];
for ($index = 0; $index < 6; $index++) {
    $variableID = 4800 + $index;
    $GLOBALS['symconTestVariables'][$variableID] = [
        'VariableType' => 2, 'VariableUpdated' => 1780000000, 'Value' => 10.0, 'Name' => 'Instrument ' . $index
    ];
    $sixSources[] = [
        'VariableID' => $variableID, 'Label' => '', 'Minimum' => 0.0, 'Maximum' => 100.0,
        'Unit'       => 'u', 'Decimals' => 0
    ];
}
foreach ([EChartsGaugeTacho::class, EChartsGaugeChronograph::class] as $dedicatedClass) {
    $limitedGauge = new $dedicatedClass();
    $limitedGauge->Create();
    $limitedGauge->SetTestProperty('Sources', json_encode($sixSources, JSON_THROW_ON_ERROR));
    $limitedGauge->ApplyChanges();
    assertGatewayGauge($limitedGauge->GetTestStatus() === 201, $dedicatedClass . ' must reject a sixth source.');
}
foreach ($sixSources as $source) {
    unset($GLOBALS['symconTestVariables'][$source['VariableID']]);
}

$multiTile = $multiGauge->GetVisualizationTile();
assertGatewayGauge(
    str_contains($multiTile, '"tileHeaderVisible":true'),
    'Gauge Multi must reserve space when the Symcon tile title is visible.'
);
$GLOBALS['symconTestHiddenTitles'][$multiGauge->InstanceID] = true;
$hiddenHeaderTile = $multiGauge->GetVisualizationTile();
assertGatewayGauge(
    str_contains($hiddenHeaderTile, '"tileHeaderVisible":false')
        && str_contains($multiGauge->GetIPSViewHTML(), '"tileHeaderVisible":false'),
    'A hidden Symcon title must release tile space without changing IPSView.'
);
unset($GLOBALS['symconTestHiddenTitles'][$multiGauge->InstanceID]);
$GLOBALS['symconTestLegacyObject'] = true;
assertGatewayGauge(
    str_contains($multiGauge->GetVisualizationTile(), '"tileHeaderVisible":true'),
    'Symcon 9.0 object data must retain the previous header clearance.'
);
unset($GLOBALS['symconTestLegacyObject']);
$multiGauge->SetTestProperty('EChartsTheme', 'vintage');
$inheritedMultiIPSView = $multiGauge->GetIPSViewHTML();
assertGatewayGauge(
    str_contains($inheritedMultiIPSView, '"theme":"vintage"')
        && str_contains($inheritedMultiIPSView, '"preset":"multi-title"'),
    'Gauge Multi IPSView must inherit the tile design by default.'
);
assertGatewayGauge(
    strlen($inheritedMultiIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Gauge Multi IPSView HTML must remain below the Symcon output-buffer limit.'
);
assertGatewayGauge(
    preg_match(
        '#/hook/SymconECharts/state/5000/([a-f0-9]{32})#',
        $inheritedMultiIPSView,
        $multiTransportMatch
    ) === 1
        && str_contains($inheritedMultiIPSView, 'new WebSocket')
        && str_contains($inheritedMultiIPSView, '/hook/SymconECharts/WS/5000/'),
    'Gauge Multi IPSView HTML must bootstrap the shared persistent transport.'
);
$multiStateRequest = ['DataID' => '{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    + EChartsDataProtocol::CreateRequest(EChartsDataProtocol::OPERATION_IPSVIEW_STATE, [
        'InstanceID' => 5000,
        'Channel'    => $multiTransportMatch[1]
    ]);
$multiStateResponse = EChartsDataProtocol::DecodeResponse(
    $multiGauge->ReceiveData(json_encode($multiStateRequest, JSON_THROW_ON_ERROR)),
    EChartsDataProtocol::OPERATION_IPSVIEW_STATE
);
assertGatewayGauge(
    ($multiStateResponse['Payload']['State']['status'] ?? null) === 'ready',
    'The targeted Gauge must return a fresh IPSView state through the Gateway data contract.'
);
$hookGateway = new TestableEChartsGateway();
$hookGateway->Create();
$hookGateway->ApplyChanges();
IPSModuleStrict::$ChildResponders = [static fn (string $json): string => $multiGauge->ReceiveData($json)];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/hook/SymconECharts/state/5000/' . $multiTransportMatch[1];
$hookState = json_decode($hookGateway->ProcessTestHook(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    ($hookState['status'] ?? null) === 'ready',
    'The Gateway state endpoint must route a fresh state from the targeted chart.'
);
IPSModuleStrict::$ChildResponders = [];
unset($_SERVER['REQUEST_METHOD'], $_SERVER['SCRIPT_NAME']);
$multiStateRequest['Payload']['Channel'] = str_repeat('0', 32);
assertGatewayGauge(
    $multiGauge->ReceiveData(json_encode($multiStateRequest, JSON_THROW_ON_ERROR)) === '',
    'A Gauge must reject IPSView state requests for another transport channel.'
);
$multiGauge->SetTestProperty('IPSViewUseTileDesign', false);
$multiGauge->SetTestProperty('IPSViewEChartsTheme', 'roma');
$multiGauge->SetTestProperty('IPSViewRingWidthPercent', 125);
$multiGauge->SetTestProperty('IPSViewAdaptToBackground', true);
$multiGauge->SetTestProperty('IPSViewBackgroundColor', 0x6B4423);
$multiGauge->SetTestProperty('IPSViewBackgroundOpacityPercent', 35);
$independentMultiIPSView = $multiGauge->GetIPSViewHTML();
assertGatewayGauge(
    str_contains($independentMultiIPSView, '"theme":"roma"')
        && str_contains($independentMultiIPSView, '"ringWidthPercent":125')
        && str_contains($independentMultiIPSView, '"adaptToBackground":true')
        && str_contains($independentMultiIPSView, 'html, body { background:transparent; }')
        && str_contains(
            $independentMultiIPSView,
            '#echarts-gauge-root { background:color-mix(in srgb, #6B4423 35%, transparent); }'
        ),
    'Gauge Multi IPSView must render its independent design.'
);
assertGatewayGauge(
    str_contains($multiGauge->GetVisualizationTile(), '"theme":"vintage"')
        && str_contains($multiGauge->GetVisualizationTile(), '"ringWidthPercent":100'),
    'Independent IPSView settings must not change the Gauge Multi tile.'
);
$multiGauge->SetTestProperty('IPSViewUseTileDesign', true);
assertGatewayGauge(str_contains($multiTile, 'window.echarts'), 'Gauge Multi tile must embed Apache ECharts.');
assertGatewayGauge(str_contains($multiTile, 'echarts-gauge-chart'), 'Gauge Multi tile root is missing.');
assertGatewayGauge(str_contains($multiTile, '"preset":"multi-title"'), 'Gauge Multi tile must receive its preset.');
assertGatewayGauge(
    str_contains($multiTile, '"id":"variable-4711"') && str_contains($multiTile, '"id":"variable-4713"'),
    'Gauge Multi tile must receive every configured source.'
);
assertGatewayGauge(
    str_contains($multiTile, 'function resolveGrid(count, width, height, hasTitle, headerInset)')
        && str_contains($multiTile, '"tileHeaderInset":58')
        && str_contains($multiTile, "type: 'gauge'")
        && str_contains($multiTile, 'items.map(function (item, index)'),
    'Gauge Multi tile must render responsive independent Gauge series.'
);
assertGatewayGauge(
    strlen($multiTile) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Gauge Multi tile must remain below the Symcon output-buffer limit.'
);
$multiForm = json_decode($multiGauge->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$multiFormJson = json_encode($multiForm, JSON_THROW_ON_ERROR);
$multiPreset = findGaugeFormElement($multiForm['elements'], 'GaugePreset');
$multiSourceList = findGaugeFormElement($multiForm['elements'], 'Sources');
$multiIPSViewPointer = findGaugeFormElement($multiForm['elements'], 'IPSViewPointerShape');
assertGatewayGauge(
    is_array($multiSourceList)
        && array_reduce(
            ['onAdd', 'onEdit', 'onDelete', 'onChangeOrder'],
            static fn (bool $valid, string $event): bool => $valid
                && str_contains((string) ($multiSourceList[$event] ?? ''), 'ECGM_UpdateGaugePreviewFromForm')
                && str_contains((string) ($multiSourceList[$event] ?? ''), 'foreach ($Sources'),
            true
        ),
    'Gauge Multi must refresh its previews after every source-list edit, including reordering.'
);
assertGatewayGauge(
    is_array($multiPreset)
        && str_starts_with($multiPreset['onChange'] ?? '', 'ECGM_UpdateGaugePreviewFromForm(')
        && str_contains($multiPreset['onChange'] ?? '', "'GaugePreset' => $" . 'GaugePreset')
        && is_array($multiIPSViewPointer)
        && ($multiIPSViewPointer['onChange'] ?? null) === ($multiPreset['onChange'] ?? null),
    'Gauge Multi must refresh both previews when shared design fields or its preset change.'
);
assertGatewayGauge(
    str_contains($multiFormJson, '"caption":"Tile designer","expanded":false')
        && str_contains($multiFormJson, '"name":"GaugePreset"')
        && str_contains($multiFormJson, '"name":"EChartsTheme"')
        && str_contains($multiFormJson, '"name":"PointerShape"')
        && str_contains($multiFormJson, '"name":"CustomPointerSVG"')
        && str_contains($multiFormJson, '"name":"CustomPointerPivotMode"')
        && str_contains($multiFormJson, '"name":"AnchorShape"')
        && str_contains($multiFormJson, '"name":"GaugeColorMode"')
        && str_contains($multiFormJson, '"name":"PlateDesignMode"')
        && str_contains($multiFormJson, '"name":"PlateBackgroundEnabled"')
        && str_contains($multiFormJson, '"name":"PlateBackgroundSVG"')
        && str_contains($multiFormJson, '"name":"GaugePreview"')
        && str_contains($multiFormJson, 'ECGM_UpdateGaugePreviewFromForm')
        && str_contains($multiFormJson, 'data:image\\/svg+xml;base64,'),
    'Gauge Multi form must expose a collapsed designer with an SVG preview.'
);
assertGatewayGauge(
    str_contains($multiFormJson, '"caption":"IPSView design","expanded":false')
        && str_contains($multiFormJson, '"name":"EnableIPSView"')
        && str_contains($multiFormJson, '"name":"IPSViewUseTileDesign"')
        && str_contains($multiFormJson, '"name":"IPSViewAdaptToBackground"')
        && str_contains($multiFormJson, '"name":"IPSViewBackgroundColor"')
        && str_contains($multiFormJson, '"name":"IPSViewBackgroundOpacityPercent"')
        && str_contains($multiFormJson, '"name":"IPSViewEChartsTheme"')
        && str_contains($multiFormJson, '"name":"IPSViewPointerShape"')
        && str_contains($multiFormJson, '"name":"IPSViewCustomPointerSVG"')
        && str_contains($multiFormJson, '"name":"IPSViewGaugeColorMode"')
        && str_contains($multiFormJson, '"name":"IPSViewPlateDesignMode"')
        && str_contains($multiFormJson, '"name":"IPSViewPlateBackgroundSVG"')
        && str_contains($multiFormJson, '"name":"IPSViewGaugePreview"'),
    'Gauge Multi form must expose the helper-backed independent IPSView designer.'
);
assertGatewayGauge(
    strlen($multiGauge->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Gauge Multi form must remain below the Symcon output-buffer limit.'
);
$multiGauge->UpdateGaugePreviewFromForm(json_encode([
    'Title'                           => 'Live preview',
    'GaugePreset'                     => 'multi-title',
    'EChartsTheme'                    => 'dark',
    'PointerShape'                    => 'arrow',
    'PointerWidthPercent'             => 150,
    'PointerLengthPercent'            => 120,
    'AnchorShape'                     => 'ring',
    'GaugeColorMode'                  => 'custom',
    'PointerColor'                    => 0x112233,
    'ProgressColor'                   => 0x223344,
    'RingColor'                       => 0x334455,
    'ScaleColor'                      => 0x445566,
    'ValueColor'                      => 0x556677,
    'TitleColor'                      => 0x667788,
    'AnchorColor'                     => 0x778899,
    'AnchorBorderColor'               => 0x8899AA,
    'PlateDesignMode'                 => 'custom',
    'PlateColor'                      => 0x99AABB,
    'PlateBorderColor'                => 0xAABBCC,
    'IPSViewUseTileDesign'            => false,
    'IPSViewAdaptToBackground'        => true,
    'IPSViewBackgroundColor'          => 0x6B4423,
    'IPSViewBackgroundOpacityPercent' => 35,
    'IPSViewGaugePreset'              => 'weather-station',
    'IPSViewEChartsTheme'             => 'roma',
    'IPSViewPointerShape'             => 'line',
    'IPSViewAnchorShape'              => 'none',
    'IPSViewGaugeColorMode'           => 'custom',
    'IPSViewPointerColor'             => 0x010203,
    'IPSViewPlateDesignMode'          => 'hidden'
], JSON_THROW_ON_ERROR));
$multiPreviewUpdates = array_slice($multiGauge->GetTestFormUpdates(), -2);
assertGatewayGauge(
    array_column($multiPreviewUpdates, 'Field') === ['GaugePreview', 'IPSViewGaugePreview'],
    'Gauge Multi must refresh both previews immediately when form values change.'
);
$tilePreviewSvg = base64_decode(
    substr((string) ($multiPreviewUpdates[0]['Value'] ?? ''), strlen('data:image/svg+xml;base64,')),
    true
);
$ipsViewPreviewSvg = base64_decode(
    substr((string) ($multiPreviewUpdates[1]['Value'] ?? ''), strlen('data:image/svg+xml;base64,')),
    true
);
assertGatewayGauge(
    is_string($tilePreviewSvg)
        && str_contains($tilePreviewSvg, 'data-preset="multi-title"')
        && str_contains($tilePreviewSvg, 'data-pointer-shape="arrow"')
        && str_contains($tilePreviewSvg, 'data-anchor-shape="ring"')
        && str_contains($tilePreviewSvg, 'data-plate-mode="custom"')
        && str_contains($tilePreviewSvg, '#112233')
        && str_contains($tilePreviewSvg, '#99AABB'),
    'Gauge Multi tile preview must render the currently edited shared design on its dials.'
);
assertGatewayGauge(
    is_string($ipsViewPreviewSvg)
        && str_contains($ipsViewPreviewSvg, 'data-preset="weather-station"')
        && str_contains($ipsViewPreviewSvg, 'data-pointer-shape="line"')
        && str_contains($ipsViewPreviewSvg, 'data-anchor-shape="none"')
        && str_contains($ipsViewPreviewSvg, 'data-plate-mode="hidden"')
        && str_contains($ipsViewPreviewSvg, '#010203')
        && str_contains($ipsViewPreviewSvg, 'fill="#6B4423" fill-opacity="0.35"'),
    'Gauge Multi IPSView preview must render its independently edited design immediately.'
);
$draftMultiSources = json_decode($multiSources, true, 512, JSON_THROW_ON_ERROR);
$draftMultiSources[0]['Label'] = 'Draft temperature';
$draftMultiSources[1]['Label'] = 'Draft humidity';
$draftMultiSources[] = [
    'VariableID' => 4714, 'Label' => 'Draft pressure', 'Minimum' => 900.0,
    'Maximum'    => 1100.0, 'Unit' => 'hPa', 'Decimals' => 0
];
$draftMultiSources = [$draftMultiSources[2], $draftMultiSources[1], $draftMultiSources[0]];
$multiGauge->UpdateGaugePreviewFromForm(json_encode(['Sources' => $draftMultiSources], JSON_THROW_ON_ERROR));
$draftPreviewUpdates = array_slice($multiGauge->GetTestFormUpdates(), -2);
foreach ($draftPreviewUpdates as $draftPreviewUpdate) {
    $draftSvg = base64_decode(
        substr((string) ($draftPreviewUpdate['Value'] ?? ''), strlen('data:image/svg+xml;base64,')),
        true
    );
    assertGatewayGauge(
        is_string($draftSvg)
            && str_contains($draftSvg, 'Draft pressure')
            && str_contains($draftSvg, 'Draft humidity')
            && str_contains($draftSvg, 'Draft temperature')
            && strpos($draftSvg, 'Draft pressure') < strpos($draftSvg, 'Draft humidity')
            && strpos($draftSvg, 'Draft humidity') < strpos($draftSvg, 'Draft temperature')
            && !str_contains($draftSvg, 'Living room humidity'),
        'Gauge Multi tile and IPSView previews must use the complete unsaved source list in its current order.'
    );
}
assertGatewayGauge(
    array_column(
        json_decode($multiGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR)['items'],
        'id'
    ) === ['variable-4711', 'variable-4713'],
    'Gauge Multi draft preview must not persist the edited source list.'
);
$multiGauge->UpdateGaugePreviewFromForm(json_encode(['Sources' => []], JSON_THROW_ON_ERROR));
$emptyDraftSvg = base64_decode(substr(
    (string) (array_slice($multiGauge->GetTestFormUpdates(), -2)[0]['Value'] ?? ''),
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($emptyDraftSvg) && str_contains($emptyDraftSvg, 'Configure 2 to 32 unique numeric sources.'),
    'Gauge Multi must show a placeholder for an incomplete draft rather than stale saved gauges.'
);
$multiGauge->UpdateGaugePreviewFromForm(json_encode(['Sources' => array_slice($draftMultiSources, 1)], JSON_THROW_ON_ERROR));
$deletedDraftSvg = base64_decode(substr(
    (string) (array_slice($multiGauge->GetTestFormUpdates(), -2)[0]['Value'] ?? ''),
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($deletedDraftSvg)
        && !str_contains($deletedDraftSvg, 'Draft pressure')
        && str_contains($deletedDraftSvg, 'Draft humidity'),
    'Gauge Multi draft preview must remove deleted sources immediately.'
);
$GLOBALS['symconTestVariables'][4718] = [
    'VariableType'    => 2,
    'VariableUpdated' => 1780000007,
    'Value'           => 37.0,
    'Name'            => 'Preview-only seventh source'
];
$manyDraftSources = [];
foreach ([4711, 4713, 4714, 4715, 4716, 4717, 4718] as $index => $variableID) {
    $manyDraftSources[] = [
        'VariableID' => $variableID,
        'Label'      => 'Draft source ' . ($index + 1),
        'Minimum'    => 0.0,
        'Maximum'    => 2000.0,
        'Unit'       => '',
        'Decimals'   => 1
    ];
}
$multiGauge->UpdateGaugePreviewFromForm(json_encode(['Sources' => $manyDraftSources], JSON_THROW_ON_ERROR));
foreach (array_slice($multiGauge->GetTestFormUpdates(), -2) as $previewUpdate) {
    $limitedSvg = base64_decode(substr(
        (string) ($previewUpdate['Value'] ?? ''),
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(
        is_string($limitedSvg)
            && str_contains($limitedSvg, 'data-preview-sources="4/7"')
            && str_contains($limitedSvg, 'Preview shows 4 of 7 sources')
            && str_contains($limitedSvg, 'Draft source 4')
            && !str_contains($limitedSvg, 'Draft source 5'),
        'Gauge Multi tile and IPSView previews must disclose when only a bounded subset of draft sources is shown.'
    );
}
unset($GLOBALS['symconTestVariables'][4718]);
$ringPreviewSvg = \SymconECharts\EChartsGaugeMultiPreview::CreateSvg(
    [
        ['label' => 'A', 'minimum' => 0.0, 'maximum' => 100.0, 'unit' => '%', 'decimals' => 0, 'value' => 25.0],
        ['label' => 'B', 'minimum' => 0.0, 'maximum' => 100.0, 'unit' => '%', 'decimals' => 0, 'value' => 75.0]
    ],
    '',
    'dark',
    'ring-grid',
    ['colorMode' => 'custom', 'progressColor' => '#223344', 'ringColor' => '#334455']
);
assertGatewayGauge(
    substr_count($ringPreviewSvg, '#223344') >= 2 && substr_count($ringPreviewSvg, '#334455') >= 2,
    'Gauge Multi ring preview must apply shared progress and ring colors to every Gauge.'
);
$previewLimitItems = array_map(
    static fn (int $index): array => [
        'label' => 'Source ' . $index, 'minimum' => 0.0, 'maximum' => 100.0,
        'unit'  => '%', 'decimals' => 0, 'value' => (float) ($index * 10)
    ],
    range(1, 7)
);
foreach ([
    'multi-title'     => 4, 'ring-grid' => 4, 'ring-concentric' => 4,
    'weather-station' => 4, 'tacho' => 3, 'chronograph' => 6
] as $preset => $visibleCount) {
    $limitedPresetSvg = \SymconECharts\EChartsGaugeMultiPreview::CreateSvg(
        $previewLimitItems,
        '',
        'dark',
        $preset
    );
    assertGatewayGauge(
        str_contains($limitedPresetSvg, 'data-preview-sources="' . $visibleCount . '/7"')
            && str_contains($limitedPresetSvg, 'Preview shows ' . $visibleCount . ' of 7 sources'),
        'Gauge Multi ' . $preset . ' preview must disclose its actual source limit.'
    );
}
$completePresetSvg = \SymconECharts\EChartsGaugeMultiPreview::CreateSvg(
    array_slice($previewLimitItems, 0, 3),
    '',
    'dark',
    'multi-title'
);
assertGatewayGauge(
    !str_contains($completePresetSvg, 'data-preview-sources='),
    'Gauge Multi preview must not imply omitted sources when all configured gauges are visible.'
);
$multiIPSViewBeforeUpdate = $multiGauge->GetTestVariableValue('IPSViewGauge');
$multiUpdateCount = count($multiGauge->GetTestVisualizationUpdates());
$multiSocketUpdateCount = count($GLOBALS['symconTestWebSocketMessages']);
$GLOBALS['symconTestVariables'][4711]['Value'] = 43.5;
$multiGauge->MessageSink(1780000200, 4711, VM_UPDATE, []);
assertGatewayGauge(
    count($multiGauge->GetTestVisualizationUpdates()) === $multiUpdateCount + 1,
    'Gauge Multi must publish a fresh visualization state when a source changes.'
);
assertGatewayGauge(
    $multiGauge->GetTestVariableValue('IPSViewGauge') === $multiIPSViewBeforeUpdate,
    'Gauge Multi must keep the persistent IPSView document unchanged when a source changes.'
);
$multiSocketUpdate = $GLOBALS['symconTestWebSocketMessages'][$multiSocketUpdateCount] ?? null;
assertGatewayGauge(
    is_array($multiSocketUpdate)
        && $multiSocketUpdate['InstanceID'] === 6100
        && str_starts_with($multiSocketUpdate['Path'], '/hook/SymconECharts/WS/5000/')
        && str_contains($multiSocketUpdate['Message'], '"value":43.5'),
    'Gauge Multi must send source changes to the persistent IPSView chart through the Gateway.'
);
$retainedMultiIPSView = $multiGauge->GetTestVariableValue('IPSViewGauge');
$multiGauge->SetTestProperty('EnableIPSView', false);
$multiGauge->ApplyChanges();
assertGatewayGauge(
    $multiGauge->GetTestVariableValue('IPSViewGauge') === $retainedMultiIPSView,
    'Disabling Gauge Multi IPSView must retain the existing WebContent variable.'
);
$GLOBALS['symconTestVariables'][4711]['Value'] = 42.5;

$presentedMultiGauge = new EChartsGaugeMulti();
$presentedMultiGauge->Create();
$presentedMultiGauge->SetTestProperty('Sources', json_encode([
    [
        'VariableID'              => 4715,
        'Label'                   => 'Pressure',
        'Minimum'                 => 0.0,
        'Maximum'                 => 1.0,
        'Unit'                    => 'fallback',
        'Decimals'                => 4,
        'UseVariablePresentation' => true
    ],
    [
        'VariableID'              => 4716,
        'Label'                   => 'Wind',
        'Minimum'                 => 0.0,
        'Maximum'                 => 1.0,
        'Unit'                    => 'fallback',
        'Decimals'                => 4,
        'UseVariablePresentation' => true
    ]
], JSON_THROW_ON_ERROR));
$presentedMultiGauge->ApplyChanges();
assertGatewayGauge(
    $presentedMultiGauge->GetTestVariableValue('IPSViewGauge') === null,
    'Gauge Multi must not create an IPSView output variable by default.'
);
$presentedMultiData = json_decode($presentedMultiGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    ($presentedMultiData['items'][0]['gauge'] ?? null) === [
        'label'    => 'Pressure',
        'minimum'  => 950.0,
        'maximum'  => 1050.0,
        'unit'     => 'hPa',
        'decimals' => 1
    ],
    'Gauge Multi must use the native presentation of an opted-in source.'
);
assertGatewayGauge(
    ($presentedMultiData['items'][1]['gauge'] ?? null) === [
        'label'    => 'Wind',
        'minimum'  => 0.0,
        'maximum'  => 50.0,
        'unit'     => 'm/s',
        'decimals' => 2
    ],
    'Gauge Multi must support opted-in legacy variable profiles.'
);

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

$invalidMultiDesign = new EChartsGaugeMulti();
$invalidMultiDesign->Create();
$invalidMultiDesign->SetTestProperty('Sources', $multiSources);
$invalidMultiDesign->SetTestProperty('EChartsTheme', 'unsupported');
$invalidMultiDesign->ApplyChanges();
assertGatewayGauge($invalidMultiDesign->GetTestStatus() === 205, 'Gauge Multi must reject an unsupported design.');

$invalidMultiDesign->SetTestProperty('EChartsTheme', 'auto');
$invalidMultiDesign->SetTestProperty('PointerShape', 'unsupported');
$invalidMultiDesign->ApplyChanges();
assertGatewayGauge($invalidMultiDesign->GetTestStatus() === 205, 'Gauge Multi must reject an unsupported pointer shape.');

$invalidMultiDesign->SetTestProperty('PointerShape', 'preset');
$invalidMultiDesign->SetTestProperty('MajorSplitCount', 1);
$invalidMultiDesign->ApplyChanges();
assertGatewayGauge($invalidMultiDesign->GetTestStatus() === 205, 'Gauge Multi must reject a single major division.');

$singleGauge = new EChartsGaugeSingle();
$singleGauge->Create();
$singleGauge->SetTestProperty('SourceVariableID', 4711);
$singleGauge->ApplyChanges();
$defaultSingleData = json_decode($singleGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    array_values(array_slice($defaultSingleData['gauge']['style'] ?? [], 7, 9)) === array_fill(0, 9, 100)
        && ($defaultSingleData['gauge']['style']['pointerShape'] ?? null) === 'preset'
        && ($defaultSingleData['gauge']['style']['titlePosition'] ?? null) === 'bottom'
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
$embeddedPointerData = json_decode($customPointerGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    ($embeddedPointerData['gauge']['style']['pointerPivotX'] ?? null) === 50.0
        && ($embeddedPointerData['gauge']['style']['pointerPivotY'] ?? null) === 370.0
        && !isset($embeddedPointerData['gauge']['style']['pointerShowAnchor']),
    'The compatible SVG pivot mode must retain the imported pivot and its own hub.'
);

$customPointerGauge->SetTestProperty('CustomPointerPivotMode', 'custom');
$customPointerGauge->SetTestProperty('CustomPointerPivotXPercent', 50.0);
$customPointerGauge->SetTestProperty('CustomPointerPivotYPercent', 93.2);
$customPointerGauge->ApplyChanges();
$customPointerData = json_decode($customPointerGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    str_contains((string) ($customPointerData['gauge']['style']['pointerPath'] ?? ''), 'M50 397 A27 27')
        && str_contains((string) ($customPointerData['gauge']['style']['pointerPath'] ?? ''), 'M48.5 57 C48.8 40')
        && ($customPointerData['gauge']['style']['pointerViewBox'] ?? null) === '22 0 56 397'
        && ($customPointerData['gauge']['style']['pointerPivotX'] ?? null) === 50.0
        && abs(($customPointerData['gauge']['style']['pointerPivotY'] ?? 0.0) - 370.004) < 0.000001
        && ($customPointerData['gauge']['style']['pointerShowAnchor'] ?? null) === true
        && !str_contains(json_encode($customPointerData, JSON_THROW_ON_ERROR), '<svg'),
    'Gauge Single must expose only the validated path and viewBox, never the imported SVG markup.'
);
$customPointerTile = $customPointerGauge->GetVisualizationTile();
assertGatewayGauge(
    str_contains($customPointerTile, 'M50 397 A27 27')
        && !str_contains($customPointerTile, '<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"22 0 56 397\"')
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
    is_string($customPointerPreviewSvg)
        && str_contains($customPointerPreviewSvg, 'data-pointer-shape="custom"')
        && str_contains($customPointerPreviewSvg, 'data-pointer-pivot-x="50.00"')
        && str_contains($customPointerPreviewSvg, 'data-pointer-pivot-y="370.00"')
        && str_contains($customPointerPreviewSvg, 'x="353.09" y="104.66" width="13.82" height="98.00"')
        && str_contains($customPointerPreviewSvg, 'class="anchor"'),
    'The Gauge form preview must place the native ECharts anchor at the configured custom SVG pivot.'
);

$customAnchorFile = base64_encode(
    '<svg viewBox="0 0 100 100"><path d="M50 0L100 50L50 100L0 50Z M50 25L75 50L50 75L25 50Z"/></svg>'
);
$customAnchorGauge = new EChartsGaugeSingle();
$customAnchorGauge->Create();
$customAnchorGauge->SetTestProperty('SourceVariableID', 4711);
$customAnchorGauge->SetTestProperty('AnchorShape', 'custom');
$customAnchorGauge->SetTestProperty('CustomAnchorSVG', $customAnchorFile);
$customAnchorGauge->ApplyChanges();
assertGatewayGauge($customAnchorGauge->GetTestStatus() === IS_ACTIVE, 'A valid custom SVG hub must be accepted.');
$customAnchorData = json_decode($customAnchorGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    ($customAnchorData['gauge']['style']['anchorPath'] ?? null)
        === 'M50 0L100 50L50 100L0 50Z M50 25L75 50L50 75L25 50Z'
        && ($customAnchorData['gauge']['style']['anchorViewBox'] ?? null) === '0 0 100 100'
        && !str_contains(json_encode($customAnchorData, JSON_THROW_ON_ERROR), '<svg'),
    'Gauge Single must expose only the validated custom hub path and viewBox.'
);
$customAnchorPreviewSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
    50.0,
    0.0,
    100.0,
    '',
    '',
    0,
    'en',
    'simple',
    'auto',
    $customAnchorData['gauge']['style']
);
assertGatewayGauge(
    str_contains($customAnchorPreviewSvg, 'data-anchor-shape="custom"')
        && str_contains($customAnchorPreviewSvg, 'preserveAspectRatio="xMidYMid meet"')
        && str_contains($customAnchorPreviewSvg, 'M50 0L100 50L50 100L0 50Z'),
    'Gauge Single preview must render a validated custom SVG hub at the pointer pivot.'
);
$hiddenAnchorPreviewSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
    50.0,
    0.0,
    100.0,
    '',
    '',
    0,
    'en',
    'simple',
    'auto',
    ['anchorShape' => 'hidden']
);
assertGatewayGauge(
    !str_contains($hiddenAnchorPreviewSvg, 'class="anchor"'),
    'Gauge Single preview must hide the hub when explicitly configured.'
);
$transparentPlatePreviewSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
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
        'plateShape'              => 'circle',
        'plateTransparent'        => true,
        'plateSizePercent'        => 100,
        'plateBorderWidthPercent' => 150
    ]
);
assertGatewayGauge(
    str_contains($transparentPlatePreviewSvg, 'data-plate-shape="circle"')
        && str_contains($transparentPlatePreviewSvg, '.plate{fill:none;')
        && str_contains($transparentPlatePreviewSvg, 'stroke-width:3'),
    'Gauge Single preview must support a transparent circular plate with a visible border.'
);
$linearPlatePreviewSvg = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
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
        'plateShape'             => 'circle',
        'plateColorMode'         => 'custom',
        'plateFillMode'          => 'linear',
        'plateColor'             => '#102030',
        'plateGradientEndColor'  => '#A0B0C0',
        'plateGradientDirection' => 'diagonal-up'
    ]
);
assertGatewayGauge(
    str_contains(
        $linearPlatePreviewSvg,
        '<linearGradient id="plate-fill-gradient" x1="0%" y1="100%" x2="100%" y2="0%">'
    )
        && str_contains($linearPlatePreviewSvg, '<stop offset="0%" stop-color="#102030"/>')
        && str_contains($linearPlatePreviewSvg, '<stop offset="100%" stop-color="#A0B0C0"/>')
        && !str_contains($linearPlatePreviewSvg, '<stop offset="50%"'),
    'Gauge Single preview must support a two-color linear plate gradient in the selected direction.'
);
$plateBackgroundFile = base64_encode(<<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100">
  <defs><linearGradient id="background"><stop offset="0%" stop-color="#102030"/><stop offset="100%" stop-color="#90A0B0"/></linearGradient></defs>
  <rect x="0" y="0" width="200" height="100" fill="url(#background)"/>
  <circle cx="100" cy="50" r="25" fill="none" stroke="#FFFFFF" stroke-width="3"/>
</svg>
SVG);
$plateBackgroundGauge = new EChartsGaugeSingle();
$plateBackgroundGauge->Create();
$plateBackgroundGauge->SetTestProperty('SourceVariableID', 4711);
$plateBackgroundGauge->SetTestProperty('PlateShape', 'circle');
$plateBackgroundGauge->SetTestProperty('PlateFillMode', 'linear');
$plateBackgroundGauge->SetTestProperty('PlateBackgroundEnabled', true);
$plateBackgroundGauge->SetTestProperty('PlateBackgroundSVG', $plateBackgroundFile);
$plateBackgroundGauge->SetTestProperty('PlateBackgroundFit', 'contain');
$plateBackgroundGauge->SetTestProperty('PlateBackgroundSizePercent', 120);
$plateBackgroundGauge->SetTestProperty('PlateBackgroundOffsetXPercent', 10);
$plateBackgroundGauge->SetTestProperty('PlateBackgroundOffsetYPercent', -5);
$plateBackgroundGauge->SetTestProperty('PlateBackgroundOpacityPercent', 65);
$plateBackgroundGauge->SetTestProperty('PlateBackgroundRotation', 15.0);
$plateBackgroundGauge->ApplyChanges();
assertGatewayGauge(
    $plateBackgroundGauge->GetTestStatus() === IS_ACTIVE,
    'A valid SVG plate background must be accepted.'
);
$plateBackgroundData = json_decode($plateBackgroundGauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
$plateBackgroundStyle = $plateBackgroundData['gauge']['style'] ?? [];
assertGatewayGauge(
    ($plateBackgroundStyle['plateFillMode'] ?? null) === 'linear'
        && ($plateBackgroundStyle['plateBackgroundEnabled'] ?? null) === true
        && ($plateBackgroundStyle['plateBackgroundFit'] ?? null) === 'contain'
        && ($plateBackgroundStyle['plateBackgroundSizePercent'] ?? null) === 120
        && ($plateBackgroundStyle['plateBackgroundOffsetXPercent'] ?? null) === 10
        && ($plateBackgroundStyle['plateBackgroundOffsetYPercent'] ?? null) === -5
        && ($plateBackgroundStyle['plateBackgroundOpacityPercent'] ?? null) === 65
        && ($plateBackgroundStyle['plateBackgroundRotation'] ?? null) === 15.0
        && ($plateBackgroundStyle['plateBackgroundAspectRatio'] ?? null) === 2.0
        && str_starts_with(
            (string) ($plateBackgroundStyle['plateBackgroundImage'] ?? ''),
            'data:image/svg+xml;base64,'
        )
        && !str_contains(json_encode($plateBackgroundData, JSON_THROW_ON_ERROR), '<svg'),
    'Gauge Single must expose the sanitized SVG plate background and its layout settings.'
);
$plateBackgroundForm = json_decode(
    $plateBackgroundGauge->GetConfigurationForm(),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$plateBackgroundFormPreview = null;
foreach ($plateBackgroundForm['elements'] ?? [] as $element) {
    if (($element['caption'] ?? null) !== 'Tile designer') {
        continue;
    }
    foreach ($element['items'] ?? [] as $item) {
        if (($item['name'] ?? null) === 'GaugePreview') {
            $plateBackgroundFormPreview = $item;
            break 2;
        }
    }
}
$plateBackgroundFormSvg = is_array($plateBackgroundFormPreview)
    ? base64_decode(
        substr(
            (string) ($plateBackgroundFormPreview['image'] ?? ''),
            strlen('data:image/svg+xml;base64,')
        ),
        true
    )
    : false;
assertGatewayGauge(
    is_string($plateBackgroundFormSvg)
        && str_contains($plateBackgroundFormSvg, '<clipPath id="plate-background-clip">')
        && str_contains($plateBackgroundFormSvg, '<image href="data:image/svg+xml;base64,'),
    'The Gauge configuration form must preview the imported and clipped SVG plate background.'
);
$plateBackgroundPreview = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
    50.0,
    0.0,
    100.0,
    '',
    '',
    0,
    'en',
    'simple',
    'auto',
    $plateBackgroundStyle
);
assertGatewayGauge(
    str_contains($plateBackgroundPreview, '<clipPath id="plate-background-clip">')
        && str_contains($plateBackgroundPreview, '<image href="data:image/svg+xml;base64,')
        && str_contains($plateBackgroundPreview, 'clip-path="url(#plate-background-clip)"')
        && str_contains($plateBackgroundPreview, 'opacity="0.65"')
        && str_contains($plateBackgroundPreview, 'transform="rotate(15.00 ')
        && str_contains($plateBackgroundPreview, 'class="plate-outline"'),
    'Gauge Single preview must position and clip the SVG background inside the dial plate.'
);
$arcPlateBackgroundPreview = \SymconECharts\EChartsGaugeSinglePreview::CreateSvg(
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
        ...$plateBackgroundStyle,
        'plateShape'   => 'arc',
        'arcMode'      => 'half',
        'startPosition'=> 270.0,
        'endPosition'  => 90.0
    ]
);
assertGatewayGauge(
    preg_match(
        '/<clipPath id="plate-background-clip"><path d="M360 [^"]+ Z"\/><\/clipPath>/',
        $arcPlateBackgroundPreview
    ) === 1,
    'Gauge Single preview must clip an SVG background to the configured scale arc.'
);
assertGatewayGauge(
    str_contains($plateBackgroundGauge->GetVisualizationTile(), "id: 'gauge-plate-background'")
        && str_contains($plateBackgroundGauge->GetVisualizationTile(), 'clipPath: clipPath')
        && strlen($plateBackgroundGauge->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'The Gauge tile must render the clipped SVG background and remain below the output-buffer limit.'
);
$largeBackgroundPath = 'M0 0 ' . str_repeat('L1 1 ', 9500);
$largeBackgroundSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
    . str_repeat('<path d="' . $largeBackgroundPath . '" fill="none" stroke="#123456"/>', 5)
    . '</svg>';
assertGatewayGauge(
    strlen($largeBackgroundSvg) > 230000 && strlen($largeBackgroundSvg) < 262144,
    'The large SVG background fixture must exercise the documented import boundary.'
);
$largeBackgroundGauge = new EChartsGaugeSingle();
$largeBackgroundGauge->Create();
$largeBackgroundGauge->SetTestProperty('SourceVariableID', 4711);
$largeBackgroundGauge->SetTestProperty('PlateShape', 'circle');
$largeBackgroundGauge->SetTestProperty('PlateBackgroundEnabled', true);
$largeBackgroundGauge->SetTestProperty('PlateBackgroundSVG', base64_encode($largeBackgroundSvg));
$largeBackgroundGauge->ApplyChanges();
assertGatewayGauge(
    $largeBackgroundGauge->GetTestStatus() === IS_ACTIVE
        && strlen($largeBackgroundGauge->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'A near-limit SVG plate background must keep the complete tile below the Symcon output-buffer limit.'
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

$invalidScaleZones = new EChartsGaugeSingle();
$invalidScaleZones->Create();
$invalidScaleZones->SetTestProperty('SourceVariableID', 4711);
$invalidScaleZones->SetTestProperty('Minimum', 0.0);
$invalidScaleZones->SetTestProperty('Maximum', 100.0);
$invalidScaleZones->SetTestProperty('ScaleZonesEnabled', true);
$invalidScaleZones->SetTestProperty(
    'ScaleZones',
    '[{"EndValue":80,"Color":65280},{"EndValue":40,"Color":16711680}]'
);
$invalidScaleZones->ApplyChanges();
assertGatewayGauge(
    $invalidScaleZones->GetTestStatus() === 205,
    'Descending Gauge value ranges must set status 205.'
);

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

$invalidAnchor = new EChartsGaugeSingle();
$invalidAnchor->Create();
$invalidAnchor->SetTestProperty('SourceVariableID', 4711);
$invalidAnchor->SetTestProperty('AnchorShape', 'unknown');
$invalidAnchor->ApplyChanges();
assertGatewayGauge($invalidAnchor->GetTestStatus() === 205, 'Unknown Gauge hub shapes must set status 205.');

$invalidPlate = new EChartsGaugeSingle();
$invalidPlate->Create();
$invalidPlate->SetTestProperty('SourceVariableID', 4711);
$invalidPlate->SetTestProperty('PlateShape', 'unknown');
$invalidPlate->ApplyChanges();
assertGatewayGauge($invalidPlate->GetTestStatus() === 205, 'Unknown Gauge plate shapes must set status 205.');

$invalidPlateColorMode = new EChartsGaugeSingle();
$invalidPlateColorMode->Create();
$invalidPlateColorMode->SetTestProperty('SourceVariableID', 4711);
$invalidPlateColorMode->SetTestProperty('PlateColorMode', 'unknown');
$invalidPlateColorMode->ApplyChanges();
assertGatewayGauge($invalidPlateColorMode->GetTestStatus() === 205, 'Unknown Gauge plate color modes must set status 205.');

$invalidPlateFillMode = new EChartsGaugeSingle();
$invalidPlateFillMode->Create();
$invalidPlateFillMode->SetTestProperty('SourceVariableID', 4711);
$invalidPlateFillMode->SetTestProperty('PlateFillMode', 'unknown');
$invalidPlateFillMode->ApplyChanges();
assertGatewayGauge($invalidPlateFillMode->GetTestStatus() === 205, 'Unknown Gauge plate fill modes must set status 205.');

$invalidPlateGradientRadius = new EChartsGaugeSingle();
$invalidPlateGradientRadius->Create();
$invalidPlateGradientRadius->SetTestProperty('SourceVariableID', 4711);
$invalidPlateGradientRadius->SetTestProperty('PlateGradientRadiusPercent', 151);
$invalidPlateGradientRadius->ApplyChanges();
assertGatewayGauge(
    $invalidPlateGradientRadius->GetTestStatus() === 205,
    'Out-of-range Gauge plate gradient radii must set status 205.'
);

$invalidPlateBackgroundFit = new EChartsGaugeSingle();
$invalidPlateBackgroundFit->Create();
$invalidPlateBackgroundFit->SetTestProperty('SourceVariableID', 4711);
$invalidPlateBackgroundFit->SetTestProperty('PlateBackgroundFit', 'unknown');
$invalidPlateBackgroundFit->ApplyChanges();
assertGatewayGauge(
    $invalidPlateBackgroundFit->GetTestStatus() === 205,
    'Unknown SVG plate background fitting modes must set status 205.'
);

$invalidPlateBackgroundSize = new EChartsGaugeSingle();
$invalidPlateBackgroundSize->Create();
$invalidPlateBackgroundSize->SetTestProperty('SourceVariableID', 4711);
$invalidPlateBackgroundSize->SetTestProperty('PlateBackgroundSizePercent', 201);
$invalidPlateBackgroundSize->ApplyChanges();
assertGatewayGauge(
    $invalidPlateBackgroundSize->GetTestStatus() === 205,
    'Out-of-range SVG plate background sizes must set status 205.'
);

$invalidPlateBackground = new EChartsGaugeSingle();
$invalidPlateBackground->Create();
$invalidPlateBackground->SetTestProperty('SourceVariableID', 4711);
$invalidPlateBackground->SetTestProperty('PlateBackgroundEnabled', true);
$invalidPlateBackground->SetTestProperty(
    'PlateBackgroundSVG',
    base64_encode('<svg viewBox="0 0 10 10"><script>alert(1)</script><path d="M0 0L10 10"/></svg>')
);
$invalidPlateBackground->ApplyChanges();
assertGatewayGauge(
    $invalidPlateBackground->GetTestStatus() === 205,
    'Unsafe SVG plate backgrounds must set status 205.'
);

$invalidPointerPivotMode = new EChartsGaugeSingle();
$invalidPointerPivotMode->Create();
$invalidPointerPivotMode->SetTestProperty('SourceVariableID', 4711);
$invalidPointerPivotMode->SetTestProperty('CustomPointerPivotMode', 'unknown');
$invalidPointerPivotMode->ApplyChanges();
assertGatewayGauge($invalidPointerPivotMode->GetTestStatus() === 205, 'Unknown custom pointer pivot modes must set status 205.');

$invalidPointerPivot = new EChartsGaugeSingle();
$invalidPointerPivot->Create();
$invalidPointerPivot->SetTestProperty('SourceVariableID', 4711);
$invalidPointerPivot->SetTestProperty('PointerShape', 'custom');
$invalidPointerPivot->SetTestProperty('CustomPointerSVG', base64_encode('<svg viewBox="0 0 10 10"><path d="M5 0L10 10L0 10Z"/></svg>'));
$invalidPointerPivot->SetTestProperty('CustomPointerPivotXPercent', 100.1);
$invalidPointerPivot->ApplyChanges();
assertGatewayGauge($invalidPointerPivot->GetTestStatus() === 205, 'Invalid custom pointer pivot percentages must set status 205.');

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

$invalidCustomAnchor = new EChartsGaugeSingle();
$invalidCustomAnchor->Create();
$invalidCustomAnchor->SetTestProperty('SourceVariableID', 4711);
$invalidCustomAnchor->SetTestProperty('AnchorShape', 'custom');
$invalidCustomAnchor->SetTestProperty(
    'CustomAnchorSVG',
    base64_encode('<svg viewBox="0 0 10 10"><script>alert(1)</script><path d="M0 0L1 1Z"/></svg>')
);
$invalidCustomAnchor->ApplyChanges();
assertGatewayGauge($invalidCustomAnchor->GetTestStatus() === 205, 'Unsafe custom SVG hubs must set status 205.');

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

$rawArchiveRequest = json_encode([
    'DataID'          => '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}',
    'ProtocolVersion' => 1,
    'Operation'       => 'archive.read',
    'Payload'         => [
        'VariableID'      => 4711,
        'StartTimestamp'  => 1780000000,
        'EndTimestamp'    => 1780000400,
        'Mode'            => 'raw',
        'Reducer'         => 'auto',
        'Limit'           => 2
    ]
], JSON_THROW_ON_ERROR);
$rawArchiveResponse = json_decode($gateway->ForwardData($rawArchiveRequest), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge($rawArchiveResponse['Success'] === true, 'Gateway must return raw archive values.');
assertGatewayGauge(
    $rawArchiveResponse['Payload']['Points'] === [
        [1780000200, 42.0],
        [1780000300, 43.0]
    ],
    'Raw archive values must remain unaggregated and be sorted oldest first.'
);
assertGatewayGauge(
    $rawArchiveResponse['Payload']['EffectiveReducer'] === 'raw'
        && $rawArchiveResponse['Payload']['Truncated'] === true,
    'Raw archive responses must expose their effective mode and truncation.'
);
$archiveQueryCount = $GLOBALS['symconTestArchiveQueryCount'];
$cachedRawArchiveResponse = json_decode(
    $gateway->ForwardData($rawArchiveRequest),
    true,
    512,
    JSON_THROW_ON_ERROR
);
assertGatewayGauge(
    $cachedRawArchiveResponse === $rawArchiveResponse
        && $GLOBALS['symconTestArchiveQueryCount'] === $archiveQueryCount,
    'Identical archive requests must use the bounded Gateway cache.'
);

$minimumArchiveRequest = json_encode([
    'DataID'          => '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}',
    'ProtocolVersion' => 1,
    'Operation'       => 'archive.read',
    'Payload'         => [
        'VariableID'       => 4711,
        'StartTimestamp'   => 1780000000,
        'EndTimestamp'     => 1780000400,
        'Mode'             => 'aggregated',
        'AggregationLevel' => 6,
        'Reducer'          => 'minimum',
        'Limit'            => 2
    ]
], JSON_THROW_ON_ERROR);
$minimumArchiveResponse = json_decode(
    $gateway->ForwardData($minimumArchiveRequest),
    true,
    512,
    JSON_THROW_ON_ERROR
);
assertGatewayGauge(
    $minimumArchiveResponse['Payload']['Points'] === [
        [1780000100, 41.0],
        [1780000200, 42.0]
    ],
    'Aggregated minimum values must retain their common bucket timestamps.'
);
assertGatewayGauge(
    $minimumArchiveResponse['Payload']['EffectiveReducer'] === 'minimum'
        && $minimumArchiveResponse['Payload']['ArchiveAggregationType'] === 'standard',
    'Standard archive aggregation metadata changed.'
);

$counterArchiveRequest = json_encode([
    'DataID'          => '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}',
    'ProtocolVersion' => 1,
    'Operation'       => 'archive.read',
    'Payload'         => [
        'VariableID'       => 4713,
        'StartTimestamp'   => 1780000000,
        'EndTimestamp'     => 1780000400,
        'Mode'             => 'aggregated',
        'AggregationLevel' => 6,
        'Reducer'          => 'auto',
        'Limit'            => 2
    ]
], JSON_THROW_ON_ERROR);
$counterArchiveResponse = json_decode(
    $gateway->ForwardData($counterArchiveRequest),
    true,
    512,
    JSON_THROW_ON_ERROR
);
assertGatewayGauge(
    $counterArchiveResponse['Payload']['Points'] === [[1780000100, 12]]
        && $counterArchiveResponse['Payload']['EffectiveReducer'] === 'sum'
        && $counterArchiveResponse['Payload']['ArchiveAggregationType'] === 'counter',
    'Counter archive auto mode must expose Symcon positive-delta sums.'
);

$invalidCounterReducerRequest = json_decode($counterArchiveRequest, true, 512, JSON_THROW_ON_ERROR);
$invalidCounterReducerRequest['Payload']['Reducer'] = 'average';
$invalidCounterReducerResponse = json_decode(
    $gateway->ForwardData(json_encode($invalidCounterReducerRequest, JSON_THROW_ON_ERROR)),
    true,
    512,
    JSON_THROW_ON_ERROR
);
assertGatewayGauge(
    $invalidCounterReducerResponse['Success'] === false
        && ($invalidCounterReducerResponse['Error']['Code'] ?? null) === 'REDUCER_NOT_SUPPORTED',
    'Gateway must not silently reinterpret an explicit average reducer as a counter sum.'
);

for ($cacheIndex = 1; $cacheIndex <= 33; ++$cacheIndex) {
    $boundedCacheRequest = json_decode($rawArchiveRequest, true, 512, JSON_THROW_ON_ERROR);
    $boundedCacheRequest['Payload']['EndTimestamp'] += $cacheIndex;
    $boundedCacheResponse = json_decode(
        $gateway->ForwardData(json_encode($boundedCacheRequest, JSON_THROW_ON_ERROR)),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    assertGatewayGauge($boundedCacheResponse['Success'] === true, 'Bounded cache test query failed.');
}
$archiveCache = json_decode($gateway->GetTestBuffer('ArchiveReadCache'), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    is_array($archiveCache) && count($archiveCache) === 32,
    'Gateway archive cache must remain bounded to 32 entries.'
);

$timeSeries = new EChartsTimeSeries();
$timeSeries->Create();
$timeSeries->SetTestProperty('Sources', json_encode([
    [
        'VariableID'               => 4711,
        'Label'                    => 'Temperature',
        'UseVariablePresentation'  => false,
        'Unit'                     => '°C',
        'Decimals'                 => 1,
        'Color'                    => 0x55CBB5,
        'Style'                    => 'area',
        'Reducer'                  => 'auto',
        'AxisPosition'             => 'left',
        'UseIndividualDesign'      => true,
        'SeriesLineType'           => 'dashed',
        'SeriesLineWidthPercent'   => 175,
        'SeriesSmoothLine'         => false,
        'SeriesPointSymbol'        => 'diamond',
        'SeriesPointSizePercent'   => 150,
        'SeriesAreaOpacityPercent' => 55,
        'SeriesAreaFillMode'       => 'svg',
        'SeriesAreaSVG'            => '<svg viewBox="0 0 40 20"><circle cx="10" cy="10" r="4" fill="#AABBCC"/></svg>',
        'SeriesAreaSVGSizePercent' => 125
    ],
    [
        'VariableID'              => 4713,
        'Label'                   => 'Humidity counter',
        'UseVariablePresentation' => false,
        'Unit'                    => '%',
        'Decimals'                => 0,
        'Color'                   => -1,
        'Style'                   => 'line',
        'Reducer'                 => 'auto',
        'AxisPosition'            => 'right'
    ]
], JSON_THROW_ON_ERROR));
$timeSeries->SetTestProperty('Range', '1h');
$timeSeries->SetTestProperty('Annotations', json_encode([
    [
        'VariableID'       => 4711,
        'Type'             => 'line',
        'Label'            => 'Comfort target',
        'Value'            => 22.5,
        'Maximum'          => 0.0,
        'Color'            => 0xE5754F,
        'LineType'         => 'dashed',
        'LineWidthPercent' => 150,
        'OpacityPercent'   => 20
    ],
    [
        'VariableID'       => 4713,
        'Type'             => 'area',
        'Label'            => 'Humidity warning',
        'Value'            => 60.0,
        'Maximum'          => 80.0,
        'Color'            => '#123456',
        'LineType'         => 'solid',
        'LineWidthPercent' => 100,
        'OpacityPercent'   => 25
    ]
], JSON_THROW_ON_ERROR));
$timeSeries->SetTestProperty('DataMode', 'auto');
$timeSeries->SetTestProperty('PointBudget', 2000);
$timeSeries->SetTestProperty('GapDetectionMode', 'custom');
$timeSeries->SetTestProperty('GapThresholdMinutes', 30);
$timeSeries->SetTestProperty('TimeAxisLabelFormat', 'date-time');
$timeSeries->SetTestProperty('LegendPosition', 'bottom');
$timeSeries->SetTestProperty('LineWidthPercent', 150);
$timeSeries->SetTestProperty('SmoothLines', true);
$timeSeries->SetTestProperty('ShowSymbols', true);
$timeSeries->SetTestProperty('SymbolSizePercent', 125);
$timeSeries->SetTestProperty('AreaOpacityPercent', 40);
$timeSeries->SetTestProperty('ShowGrid', false);
$timeSeries->SetTestProperty('ShowXAxis', false);
$timeSeries->SetTestProperty('ShowYAxis', true);
$timeSeries->SetTestProperty('TitleFontSizePercent', 125);
$timeSeries->SetTestProperty('LegendFontSizePercent', 110);
$timeSeries->SetTestProperty('AxisFontSizePercent', 150);
$timeSeries->SetTestProperty('TitleColor', 0x123456);
$timeSeries->SetTestProperty('LegendColor', 0x234567);
$timeSeries->SetTestProperty('AxisColor', 0x345678);
$timeSeries->SetTestProperty('GridColor', 0x456789);
$timeSeries->SetTestProperty('EnableIPSView', true);
$timeSeries->ApplyChanges();
$timeSeriesData = json_decode($timeSeries->GetTimeSeriesData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $timeSeries->GetTestStatus() === IS_ACTIVE
        && $timeSeries->GetTestVisualizationType() === 1
        && $timeSeries->GetTestReferences() === [4711, 4713],
    'Time Series must initialize as a native visualization with deterministic source references.'
);
assertGatewayGauge(
    $timeSeriesData['family'] === 'time-series'
        && $timeSeriesData['chart']['timeAxisLabelFormat'] === 'date-time'
        && $timeSeriesData['range']['aggregationLevel'] === 6
        && $timeSeriesData['range']['gapDetectionMode'] === 'custom'
        && $timeSeriesData['range']['gapThresholdSeconds'] === 1800
        && count($timeSeriesData['axes']) === 2
        && $timeSeriesData['axes'][0]['position'] === 'left'
        && $timeSeriesData['axes'][0]['positionIndex'] === 0
        && $timeSeriesData['axes'][1]['position'] === 'right'
        && $timeSeriesData['axes'][1]['positionIndex'] === 0
        && $timeSeriesData['series'][0]['effectiveReducer'] === 'average'
        && $timeSeriesData['series'][0]['color'] === '#55CBB5'
        && $timeSeriesData['series'][0]['design']['lineType'] === 'dashed'
        && $timeSeriesData['series'][0]['design']['lineWidthPercent'] === 175
        && $timeSeriesData['series'][0]['design']['pointSymbol'] === 'diamond'
        && $timeSeriesData['series'][0]['design']['areaFillMode'] === 'svg'
        && str_starts_with($timeSeriesData['series'][0]['design']['areaPatternImage'], 'data:image/svg+xml;base64,')
        && $timeSeriesData['series'][0]['design']['areaPatternAspectRatio'] === 2.0
        && $timeSeriesData['series'][1]['effectiveReducer'] === 'sum'
        && $timeSeriesData['series'][1]['color'] === ''
        && $timeSeriesData['series'][1]['design'] === []
        && $timeSeriesData['annotations'][0] === [
            'type'             => 'line',
            'variableID'       => 4711,
            'seriesIndex'      => 0,
            'label'            => 'Comfort target',
            'value'            => 22.5,
            'color'            => '#E5754F',
            'lineType'         => 'dashed',
            'lineWidthPercent' => 150,
            'opacityPercent'   => 20
        ]
        && $timeSeriesData['annotations'][1]['type'] === 'area'
        && $timeSeriesData['annotations'][1]['seriesIndex'] === 1
        && $timeSeriesData['annotations'][1]['value'] === 60.0
        && $timeSeriesData['annotations'][1]['maximum'] === 80.0
        && $timeSeriesData['annotations'][1]['color'] === '#123456'
        && $timeSeriesData['annotations'][1]['opacityPercent'] === 25
        && $timeSeriesData['chart']['design'] === [
            'animationEnabled'        => false,
            'animationDuration'       => 350,
            'animationDurationUpdate' => 500,
            'animationEasing'         => 'cubicInOut',
            'animationEasingUpdate'   => 'cubicInOut',
            'animationDelay'          => 0,
            'animationDelayUpdate'    => 0,
            'legendPosition'          => 'bottom',
            'lineWidthPercent'        => 150,
            'smoothLines'             => true,
            'showSymbols'             => true,
            'symbolSizePercent'       => 125,
            'showValues'              => false,
            'valueLabelPosition'      => 'top',
            'valueLabelDistance'      => 5,
            'areaOpacityPercent'      => 40,
            'showGrid'                => false,
            'showXAxis'               => false,
            'showYAxis'               => true,
            'titleFontSizePercent'    => 125,
            'legendFontSizePercent'   => 110,
            'axisFontSizePercent'     => 150,
            'valueFontSizePercent'    => 100,
            'titleColor'              => '#123456',
            'legendColor'             => '#234567',
            'axisColor'               => '#345678',
            'gridColor'               => '#456789',
            'valueColor'              => ''
        ],
    'Time Series must build the accepted positioned-axis aggregated chart model.'
);
$timeSeriesForm = $timeSeries->GetConfigurationForm();
assertGatewayGauge(
    str_contains($timeSeriesForm, 'ECTS_UpdateTimeSeriesPreviewFromForm')
        && str_contains($timeSeriesForm, 'AxisPosition')
        && str_contains($timeSeriesForm, 'AxisRangeMode')
        && str_contains($timeSeriesForm, 'AxisMinimum')
        && str_contains($timeSeriesForm, 'AxisMaximum')
        && str_contains($timeSeriesForm, 'CustomRangeValue')
        && str_contains($timeSeriesForm, 'CustomRangeUnit')
        && str_contains($timeSeriesForm, 'current-week')
        && str_contains($timeSeriesForm, 'current-month')
        && str_contains($timeSeriesForm, 'TimeAxisLabelFormat')
        && str_contains($timeSeriesForm, 'GapDetectionMode')
        && str_contains($timeSeriesForm, 'GapThresholdMinutes')
        && str_contains($timeSeriesForm, 'Annotations')
        && str_contains($timeSeriesForm, 'Reference line')
        && str_contains($timeSeriesForm, 'Value range')
        && str_contains($timeSeriesForm, 'ECTS_UpdateTimeSeriesPreviewAnnotationFromForm')
        && str_contains($timeSeriesForm, 'SelectColor')
        && str_contains($timeSeriesForm, 'transparentCaption')
        && str_contains($timeSeriesForm, 'UseIndividualDesign')
        && str_contains($timeSeriesForm, 'SeriesLineType')
        && str_contains($timeSeriesForm, 'SeriesPointSymbol')
        && str_contains($timeSeriesForm, 'SeriesAreaFillMode')
        && str_contains($timeSeriesForm, 'SeriesAreaSVG')
        && str_contains($timeSeriesForm, 'ECTS_UpdateTimeSeriesPreviewSourceFromForm')
        && str_contains($timeSeriesForm, 'ECTS_GetTimeSeriesDiagnostic')
        && str_contains($timeSeriesForm, 'ECTS_CopyTileDesignToIPSView')
        && str_contains($timeSeriesForm, 'IPSViewUseTileDesign')
        && str_contains($timeSeriesForm, 'IPSViewUseTileTimeSettings')
        && str_contains($timeSeriesForm, 'IPSViewRange')
        && str_contains($timeSeriesForm, 'IPSViewCustomRangeValue')
        && str_contains($timeSeriesForm, 'IPSViewCustomRangeUnit')
        && str_contains($timeSeriesForm, 'IPSViewTimeAxisLabelFormat')
        && str_contains($timeSeriesForm, 'IPSViewAdaptToBackground')
        && str_contains($timeSeriesForm, 'IPSViewBackgroundColor')
        && str_contains($timeSeriesForm, 'IPSViewBackgroundOpacityPercent')
        && str_contains($timeSeriesForm, 'IPSViewTimeSeriesPreview')
        && str_contains($timeSeriesForm, 'IPSViewTitleFontSizePercent')
        && str_contains($timeSeriesForm, 'IPSViewLegendColor')
        && str_contains($timeSeriesForm, 'Typography and colors')
        && str_contains($timeSeriesForm, 'data:image/svg+xml;base64,'),
    'Time Series form must provide axis positioning, diagnostics and separate live Tile/IPSView previews.'
);
$timeSeriesFormData = json_decode($timeSeriesForm, true, 512, JSON_THROW_ON_ERROR);
$timeSeriesRows = array_values(array_filter(
    $timeSeriesFormData['elements'],
    static fn (array $element): bool => ($element['type'] ?? '') === 'RowLayout'
));
$timeSeriesRangeFields = array_column($timeSeriesRows[0]['items'], null, 'name');
$timeSeriesGapFields = array_column($timeSeriesRows[1]['items'], null, 'name');
assertGatewayGauge(
    ($timeSeriesRangeFields['CustomRangeValue']['visible'] ?? null) === false
        && ($timeSeriesRangeFields['CustomRangeUnit']['visible'] ?? null) === false
        && ($timeSeriesGapFields['GapThresholdMinutes']['visible'] ?? null) === true,
    'Time Series form must use the shared helper for nested range and gap field visibility.'
);
$timeSeries->SetTestProperty('Range', 'custom');
$timeSeries->SetTestProperty('GapDetectionMode', 'off');
$timeSeriesFormData = json_decode($timeSeries->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$timeSeriesRows = array_values(array_filter(
    $timeSeriesFormData['elements'],
    static fn (array $element): bool => ($element['type'] ?? '') === 'RowLayout'
));
$timeSeriesRangeFields = array_column($timeSeriesRows[0]['items'], null, 'name');
$timeSeriesGapFields = array_column($timeSeriesRows[1]['items'], null, 'name');
assertGatewayGauge(
    ($timeSeriesRangeFields['CustomRangeValue']['visible'] ?? null) === true
        && ($timeSeriesRangeFields['CustomRangeUnit']['visible'] ?? null) === true
        && ($timeSeriesGapFields['GapThresholdMinutes']['visible'] ?? null) === false,
    'Time Series form must update nested visibility for custom and fixed settings.'
);
$timeSeries->SetTestProperty('Range', '1h');
$timeSeries->SetTestProperty('GapDetectionMode', 'custom');
$timeSeries->UpdateTimeSeriesPreviewFromForm(json_encode([
    'Title'              => 'Edited preview',
    'Sources'            => [[
        'VariableID' => 4711,
        'Label'      => 'Outdoor temperature',
        'Color'      => 0xE5754F,
        'Style'      => 'area'
    ]],
    'Annotations'        => [[
        'VariableID'       => 4711,
        'Type'             => 'line',
        'Label'            => 'Preview target',
        'Value'            => 21.5,
        'Maximum'          => 0.0,
        'Color'            => -1,
        'LineType'         => 'dotted',
        'LineWidthPercent' => 100,
        'OpacityPercent'   => 18
    ]],
    'EChartsTheme'          => 'dark',
    'LegendPosition'        => 'hidden',
    'LineWidthPercent'      => 80,
    'SmoothLines'           => false,
    'ShowSymbols'           => true,
    'SymbolSizePercent'     => 150,
    'AreaOpacityPercent'    => 55,
    'ShowGrid'              => true,
    'ShowXAxis'             => true,
    'ShowYAxis'             => false,
    'TitleFontSizePercent'  => 150,
    'LegendFontSizePercent' => 125,
    'AxisFontSizePercent'   => 175,
    'TitleColor'            => 0xABCDEF,
    'LegendColor'           => 0x123456,
    'AxisColor'             => 0x234567,
    'GridColor'             => 0x345678,
    'TimeAxisLabelFormat'   => 'date'
], JSON_THROW_ON_ERROR));
$timeSeriesPreviewUpdates = $timeSeries->GetTestFormUpdates();
$timeSeriesPreviewFields = array_column(array_slice($timeSeriesPreviewUpdates, -2), 'Field');
$timeSeriesPreviewUpdate = end($timeSeriesPreviewUpdates);
$timeSeriesPreviewUri = is_array($timeSeriesPreviewUpdate)
    ? (string) ($timeSeriesPreviewUpdate['Value'] ?? '')
    : '';
$timeSeriesPreviewSvg = base64_decode(
    substr($timeSeriesPreviewUri, strlen('data:image/svg+xml;base64,')),
    true
);
assertGatewayGauge(
    $timeSeriesPreviewFields === ['TimeSeriesPreview', 'IPSViewTimeSeriesPreview']
        && is_string($timeSeriesPreviewSvg)
        && str_contains($timeSeriesPreviewSvg, 'Edited preview')
        && str_contains($timeSeriesPreviewSvg, '#E5754F')
        && str_contains($timeSeriesPreviewSvg, 'data-legend-position="hidden"')
        && str_contains($timeSeriesPreviewSvg, 'data-smooth-lines="false"')
        && str_contains($timeSeriesPreviewSvg, 'data-show-symbols="true"')
        && str_contains($timeSeriesPreviewSvg, 'data-time-axis-label-format="date"')
        && str_contains($timeSeriesPreviewSvg, 'data-area-opacity="0.55"')
        && str_contains($timeSeriesPreviewSvg, 'data-axis-color="#234567"')
        && str_contains($timeSeriesPreviewSvg, 'data-annotation-type="line"')
        && str_contains($timeSeriesPreviewSvg, 'fill="#ABCDEF" font-size="30"')
        && str_contains($timeSeriesPreviewSvg, 'stroke="#345678"')
        && str_contains($timeSeriesPreviewSvg, 'Preview target'),
    'Time Series Tile and IPSView previews must react immediately to unpersisted source and design values.'
);
$timeSeries->UpdateTimeSeriesPreviewFromForm(json_encode([
    'Sources' => array_map(
        static fn (int $variableID): array => ['VariableID' => $variableID, 'Label' => 'Source ' . $variableID],
        [4711, 4713, 4714, 4715, 4716]
    )
], JSON_THROW_ON_ERROR));
foreach (array_slice($timeSeries->GetTestFormUpdates(), -2) as $update) {
    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        is_string($svg)
            && str_contains($svg, 'data-preview-sources="4/5"')
            && str_contains($svg, 'Preview shows 4 of 5 sources'),
        'Time Series must disclose when its Tile or IPSView preview omits configured sources.'
    );
}
$timeSeriesAxesPreview = new EChartsTimeSeries();
$timeSeriesAxesPreview->Create();
$timeSeriesAxesPreview->UpdateTimeSeriesPreviewFromForm(json_encode([
    'Sources' => [
        ['VariableID'      => 4711, 'Unit' => '°C', 'UseVariablePresentation' => false,
            'AxisPosition' => 'right', 'Color' => 0x123456],
        ['VariableID'      => 4713, 'Unit' => '%', 'UseVariablePresentation' => false,
            'AxisPosition' => 'left', 'Color' => 0x654321],
        ['VariableID'      => 4714, 'Unit' => '°C', 'UseVariablePresentation' => false,
            'AxisPosition' => 'right', 'Color' => 0xABCDEF],
        ['VariableID'      => 4715, 'Unit' => '', 'UseVariablePresentation' => true,
            'AxisPosition' => 'auto', 'Color' => 0x2468AC]
    ],
    'IPSViewUseTileDesign' => false
], JSON_THROW_ON_ERROR));
foreach (array_slice($timeSeriesAxesPreview->GetTestFormUpdates(), -2) as $axisPreviewUpdate) {
    $axisPreviewSvg = base64_decode(substr(
        $axisPreviewUpdate['Value'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(is_string($axisPreviewSvg), 'Time Series multi-axis preview must produce an SVG image.');
    $axisPreviewDocument = new DOMDocument();
    assertGatewayGauge(
        $axisPreviewDocument->loadXML($axisPreviewSvg, LIBXML_NONET),
        'Time Series multi-axis preview SVG must be valid XML.'
    );
    $axes = [];
    foreach ($axisPreviewDocument->getElementsByTagName('line') as $line) {
        if ($line->hasAttribute('data-preview-axis')) {
            $axes[] = [
                'side'  => $line->getAttribute('data-preview-axis'),
                'x'     => (float) $line->getAttribute('x1'),
                'color' => $line->getAttribute('stroke')
            ];
        }
    }
    $units = [];
    foreach ($axisPreviewDocument->getElementsByTagName('text') as $label) {
        if ($label->hasAttribute('data-preview-axis-unit')) {
            $units[] = $label->textContent;
        }
    }
    assertGatewayGauge(
        count($axes) === 3
            && $axes[0]['side'] === 'right' && $axes[0]['color'] === '#123456'
            && $axes[1]['side'] === 'left' && $axes[1]['color'] === '#654321'
            && $axes[2]['side'] === 'left' && $axes[2]['color'] === '#2468AC'
            && $axes[2]['x'] < $axes[1]['x']
            && $units === ['°C', '%', 'hPa'],
        'Time Series previews must show one color-coded axis per effective unit on its assigned side.'
    );
}
$timeSeriesTile = $timeSeries->GetVisualizationTile();
assertGatewayGauge(
    str_contains($timeSeriesTile, 'echarts-timeseries-root'),
    'Time Series native tile must embed its visualization root.'
);
assertGatewayGauge(
    str_contains($timeSeriesTile, 'time-series'),
    'Time Series native tile must embed its chart model.'
);
assertGatewayGauge(
    strlen($timeSeriesTile) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Time Series native tile must remain below the Symcon output limit (actual: '
        . strlen($timeSeriesTile) . ').'
);
$inheritedTimeSeriesIPSView = $timeSeries->GetIPSViewHTML();
assertGatewayGauge(
    is_string($timeSeries->GetTestVariableValue('IPSViewTimeSeries'))
        && str_contains((string) $timeSeries->GetTestVariableValue('IPSViewTimeSeries'), '"mode":"ipsview"')
        && str_contains($inheritedTimeSeriesIPSView, '"key":"1h"')
        && str_contains($inheritedTimeSeriesIPSView, '"timeAxisLabelFormat":"date-time"')
        && str_contains($inheritedTimeSeriesIPSView, '"legendPosition":"bottom"')
        && str_contains($inheritedTimeSeriesIPSView, '"lineWidthPercent":150')
        && str_contains($inheritedTimeSeriesIPSView, '"titleColor":"#123456"')
        && strlen($inheritedTimeSeriesIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Time Series must publish an optional IPSView WebContent page that inherits the Tile design.'
);
$timeSeries->SetTestProperty('IPSViewUseTileDesign', false);
$timeSeries->SetTestProperty('IPSViewUseTileTimeSettings', false);
$timeSeries->SetTestProperty('IPSViewRange', 'custom');
$timeSeries->SetTestProperty('IPSViewCustomRangeValue', 2);
$timeSeries->SetTestProperty('IPSViewCustomRangeUnit', 'hour');
$timeSeries->SetTestProperty('IPSViewTimeAxisLabelFormat', 'time');
$timeSeries->SetTestProperty('IPSViewAdaptToBackground', true);
$timeSeries->SetTestProperty('IPSViewBackgroundColor', 0x6B4423);
$timeSeries->SetTestProperty('IPSViewBackgroundOpacityPercent', 35);
$timeSeries->SetTestProperty('IPSViewEChartsTheme', 'dark');
$timeSeries->SetTestProperty('IPSViewLegendPosition', 'hidden');
$timeSeries->SetTestProperty('IPSViewEnableZoom', false);
$timeSeries->SetTestProperty('IPSViewLineWidthPercent', 80);
$timeSeries->SetTestProperty('IPSViewSmoothLines', false);
$timeSeries->SetTestProperty('IPSViewShowSymbols', false);
$timeSeries->SetTestProperty('IPSViewSymbolSizePercent', 75);
$timeSeries->SetTestProperty('IPSViewAreaOpacityPercent', 10);
$timeSeries->SetTestProperty('IPSViewShowGrid', true);
$timeSeries->SetTestProperty('IPSViewShowXAxis', true);
$timeSeries->SetTestProperty('IPSViewShowYAxis', false);
$timeSeries->SetTestProperty('IPSViewTitleFontSizePercent', 80);
$timeSeries->SetTestProperty('IPSViewLegendFontSizePercent', 90);
$timeSeries->SetTestProperty('IPSViewAxisFontSizePercent', 110);
$timeSeries->SetTestProperty('IPSViewTitleColor', 0xABCDEF);
$timeSeries->SetTestProperty('IPSViewLegendColor', 0xBCDEFA);
$timeSeries->SetTestProperty('IPSViewAxisColor', 0xCDEFAB);
$timeSeries->SetTestProperty('IPSViewGridColor', 0xDEFABC);
$timeSeries->UpdateTimeSeriesPreviewFromForm(json_encode([
    'Title'                     => 'Independent IPSView preview',
    'Sources'                   => [[
        'VariableID' => 4711,
        'Label'      => 'Outdoor temperature',
        'Color'      => 0xE5754F,
        'Style'      => 'line'
    ]],
    'EChartsTheme'                    => 'vintage',
    'LegendPosition'                  => 'bottom',
    'IPSViewUseTileDesign'            => false,
    'IPSViewUseTileTimeSettings'      => false,
    'IPSViewRange'                    => 'custom',
    'IPSViewCustomRangeValue'         => 2,
    'IPSViewCustomRangeUnit'          => 'hour',
    'IPSViewTimeAxisLabelFormat'      => 'time',
    'IPSViewAdaptToBackground'        => true,
    'IPSViewBackgroundColor'          => 0x6B4423,
    'IPSViewBackgroundOpacityPercent' => 35,
    'IPSViewEChartsTheme'             => 'dark',
    'IPSViewLegendPosition'           => 'hidden',
    'IPSViewLineWidthPercent'         => 80,
    'IPSViewSmoothLines'              => false,
    'IPSViewShowSymbols'              => false,
    'IPSViewSymbolSizePercent'        => 75,
    'IPSViewAreaOpacityPercent'       => 10,
    'IPSViewShowGrid'                 => true,
    'IPSViewShowXAxis'                => true,
    'IPSViewShowYAxis'                => false,
    'IPSViewTitleFontSizePercent'     => 80,
    'IPSViewLegendFontSizePercent'    => 90,
    'IPSViewAxisFontSizePercent'      => 110,
    'IPSViewTitleColor'               => 0xABCDEF,
    'IPSViewLegendColor'              => 0xBCDEFA,
    'IPSViewAxisColor'                => 0xCDEFAB,
    'IPSViewGridColor'                => 0xDEFABC
], JSON_THROW_ON_ERROR));
$independentPreviewUpdates = $timeSeries->GetTestFormUpdates();
$independentPreviewUpdate = end($independentPreviewUpdates);
$independentPreviewUri = is_array($independentPreviewUpdate)
    ? (string) ($independentPreviewUpdate['Value'] ?? '')
    : '';
$independentPreviewSvg = base64_decode(
    substr($independentPreviewUri, strlen('data:image/svg+xml;base64,')),
    true
);
assertGatewayGauge(
    is_string($independentPreviewSvg)
        && str_contains($independentPreviewSvg, 'Independent IPSView preview')
        && str_contains($independentPreviewSvg, 'data-legend-position="hidden"')
        && str_contains($independentPreviewSvg, 'data-time-axis-label-format="time"')
        && str_contains($independentPreviewSvg, 'data-adapt-to-background="true"')
        && str_contains($independentPreviewSvg, 'data-background-opacity="35"')
        && str_contains($independentPreviewSvg, 'fill="#6B4423"')
        && str_contains($independentPreviewSvg, 'fill-opacity="0.35"')
        && str_contains($independentPreviewSvg, 'data-area-opacity="0.1"')
        && str_contains($independentPreviewSvg, 'stroke-width="2"')
        && str_contains($independentPreviewSvg, 'fill="#ABCDEF" font-size="16"'),
    'Time Series preview must render unsaved independent IPSView design values.'
);
$timeSeries->ApplyChanges();
$independentTimeSeriesIPSView = $timeSeries->GetIPSViewHTML();
assertGatewayGauge(
    str_contains($independentTimeSeriesIPSView, '"theme":"dark"')
        && str_contains($independentTimeSeriesIPSView, '"key":"custom"')
        && str_contains($independentTimeSeriesIPSView, '"durationSeconds":7200')
        && str_contains($independentTimeSeriesIPSView, '"timeAxisLabelFormat":"time"')
        && str_contains($independentTimeSeriesIPSView, '"adaptToBackground":true')
        && str_contains($independentTimeSeriesIPSView, 'html, body { background:transparent; }')
        && str_contains($independentTimeSeriesIPSView, '#echarts-timeseries-root { background:color-mix(in srgb, ')
        && str_contains($independentTimeSeriesIPSView, '#6B4423 35%, transparent); }')
        && str_contains($independentTimeSeriesIPSView, '"enableZoom":false')
        && str_contains($independentTimeSeriesIPSView, '"legendPosition":"hidden"')
        && str_contains($independentTimeSeriesIPSView, '"lineWidthPercent":80')
        && str_contains($independentTimeSeriesIPSView, '"showYAxis":false')
        && str_contains($timeSeries->GetVisualizationTile(), '"key":"1h"')
        && str_contains($timeSeries->GetVisualizationTile(), '"timeAxisLabelFormat":"date-time"')
        && str_contains($timeSeries->GetVisualizationTile(), '"legendPosition":"bottom"')
        && str_contains($timeSeries->GetVisualizationTile(), '"adaptToBackground":false'),
    'Time Series must keep independent IPSView time, theme and design settings separate from the Tile.'
);

$rawTimeSeries = new EChartsTimeSeries();
$rawTimeSeries->Create();
$rawTimeSeries->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4711,
    'Label'                   => '',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'maximum'
]], JSON_THROW_ON_ERROR));
$rawTimeSeries->SetTestProperty('Range', '1h');
$rawTimeSeries->SetTestProperty('DataMode', 'raw');
$rawTimeSeries->ApplyChanges();
assertTransientRetry($rawTimeSeries, 'Time Series');
$rawTimeSeriesData = json_decode($rawTimeSeries->GetTimeSeriesData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $rawTimeSeriesData['range']['aggregationLevel'] === null
        && $rawTimeSeriesData['series'][0]['effectiveReducer'] === 'raw'
        && $rawTimeSeriesData['series'][0]['points'] === []
        && $rawTimeSeriesData['chart']['design']['titleFontSizePercent'] === 100
        && $rawTimeSeriesData['chart']['design']['legendFontSizePercent'] === 100
        && $rawTimeSeriesData['chart']['design']['axisFontSizePercent'] === 100
        && $rawTimeSeriesData['chart']['design']['titleColor'] === ''
        && $rawTimeSeriesData['chart']['design']['axisColor'] === '',
    'Explicit raw mode must preserve logged values without applying a reducer.'
);
$rawTimeSeries->MessageSink(1780000500, 4711, VM_UPDATE, []);
$lastTimeSeriesUpdate = $rawTimeSeries->GetTestVisualizationUpdates();
$lastTimeSeriesUpdate = json_decode((string) end($lastTimeSeriesUpdate), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $lastTimeSeriesUpdate['messageType'] === 'append'
        && $lastTimeSeriesUpdate['variableID'] === 4711,
    'Raw Time Series updates must append one live point instead of reloading the archive.'
);

$sameNameTimeSeries = new EChartsTimeSeries();
$sameNameTimeSeries->Create();
$sameNameTimeSeries->SetTestProperty('Sources', json_encode([
    [
        'VariableID'              => 4711,
        'Label'                   => 'Temperatur',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => -1,
        'Style'                   => 'line',
        'Reducer'                 => 'auto'
    ],
    [
        'VariableID'              => 4717,
        'Label'                   => 'Temperatur',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => -1,
        'Style'                   => 'line',
        'Reducer'                 => 'auto'
    ]
], JSON_THROW_ON_ERROR));
$sameNameTimeSeries->SetTestProperty('DataMode', 'realtime');
$sameNameTimeSeries->ApplyChanges();
$sameNameTimeSeriesData = json_decode($sameNameTimeSeries->GetTimeSeriesData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    array_column($sameNameTimeSeriesData['series'], 'id') === ['variable-4711', 'variable-4717']
        && array_column($sameNameTimeSeriesData['series'], 'label') === [
            'Temperatur', 'Temperatur'
        ],
    'Time Series must retain distinct source IDs without exposing them in equal legend labels.'
);

$archiveQueriesBeforeRealtime = $GLOBALS['symconTestArchiveQueryCount'];
$realtimeSeries = new EChartsTimeSeries();
$realtimeSeries->Create();
$realtimeSeries->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4717,
    'Label'                   => 'Live temperature',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 2,
    'Color'                   => '#E5754F',
    'Style'                   => 'line',
    'Reducer'                 => 'auto'
]], JSON_THROW_ON_ERROR));
$realtimeSeries->SetTestProperty('Range', '1h');
$realtimeSeries->SetTestProperty('DataMode', 'realtime');
$realtimeSeries->ApplyChanges();
$realtimeData = json_decode($realtimeSeries->GetTimeSeriesData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    !isset($GLOBALS['symconTestArchiveVariables'][4717])
        && $realtimeSeries->GetTestStatus() === IS_ACTIVE
        && $realtimeData['range']['dataMode'] === 'realtime'
        && $realtimeData['series'][0]['archiveAggregationType'] === 'none'
        && $realtimeData['series'][0]['effectiveReducer'] === 'realtime'
        && count($realtimeData['series'][0]['points']) === 1
        && $realtimeData['series'][0]['points'][0][1] === 23.75
        && $realtimeData['series'][0]['points'][0][0] === $realtimeData['range']['endTimestamp']
        && $GLOBALS['symconTestArchiveQueryCount'] === $archiveQueriesBeforeRealtime,
    'Real-time mode must start from the current value without requiring or querying an archive.'
);
$realtimeSeries->MessageSink(1780000600, 4717, VM_UPDATE, []);
$realtimeUpdates = $realtimeSeries->GetTestVisualizationUpdates();
$realtimeUpdate = json_decode((string) end($realtimeUpdates), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $realtimeUpdate['messageType'] === 'append'
        && $realtimeUpdate['variableID'] === 4717
        && $realtimeUpdate['value'] === 23.75,
    'Unarchived real-time sources must append VM_UPDATE values to the open chart.'
);

$longRangeTimeSeries = new EChartsTimeSeries();
$longRangeTimeSeries->Create();
$longRangeTimeSeries->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4711,
    'Label'                   => '',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'auto'
]], JSON_THROW_ON_ERROR));
$longRangeTimeSeries->SetTestProperty('Range', '30d');
$longRangeTimeSeries->SetTestProperty('DataMode', 'auto');
$longRangeTimeSeries->SetTestProperty('PointBudget', 200);
$longRangeTimeSeries->ApplyChanges();
$longRangeData = json_decode($longRangeTimeSeries->GetTimeSeriesData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $longRangeData['range']['aggregationLevel'] === 1,
    'Automatic aggregation must select the finest level that fits the per-series point budget.'
);

$customRangeTimeSeries = new EChartsTimeSeries();
$customRangeTimeSeries->Create();
$customRangeTimeSeries->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4711,
    'Label'                   => 'Custom range temperature',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'auto'
]], JSON_THROW_ON_ERROR));
$customRangeTimeSeries->SetTestProperty('Range', 'custom');
$customRangeTimeSeries->SetTestProperty('CustomRangeValue', 3);
$customRangeTimeSeries->SetTestProperty('CustomRangeUnit', 'week');
$customRangeTimeSeries->SetTestProperty('TimeAxisLabelFormat', 'time');
$customRangeTimeSeries->SetTestProperty('DataMode', 'auto');
$customRangeTimeSeries->SetTestProperty('PointBudget', 200);
$customRangeTimeSeries->ApplyChanges();
$customRangeData = json_decode(
    $customRangeTimeSeries->GetTimeSeriesData(),
    true,
    512,
    JSON_THROW_ON_ERROR
);
assertGatewayGauge(
    $customRangeTimeSeries->GetTestStatus() === IS_ACTIVE
        && $customRangeTimeSeries->GetTestSummary() === '1 sources · 3w'
        && $customRangeData['range']['key'] === 'custom'
        && $customRangeData['range']['durationSeconds'] === 1814400
        && $customRangeData['range']['aggregationLevel'] === 1
        && $customRangeData['chart']['timeAxisLabelFormat'] === 'time',
    'A custom rolling range and the selected time-axis label format must reach the shared chart model.'
);

$previousTimezone = date_default_timezone_get();
date_default_timezone_set('Europe/Berlin');
$calendarTimezone = new DateTimeZone('Europe/Berlin');
$calendarNow = (new DateTimeImmutable('2026-10-07 14:30:00', $calendarTimezone))->getTimestamp();
$calendarSource = [[
    'VariableID'              => 4711,
    'Label'                   => 'Calendar temperature',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'auto'
]];
$calendarRanges = [
    'today' => [
        'start' => '2026-10-07 00:00:00',
        'end'   => '2026-10-07 14:30:00',
        'live'  => true
    ],
    'yesterday' => [
        'start' => '2026-10-06 00:00:00',
        'end'   => '2026-10-06 23:59:59',
        'live'  => false
    ],
    'current-week' => [
        'start' => '2026-10-05 00:00:00',
        'end'   => '2026-10-07 14:30:00',
        'live'  => true
    ],
    'current-month' => [
        'start' => '2026-10-01 00:00:00',
        'end'   => '2026-10-07 14:30:00',
        'live'  => true
    ]
];
foreach ($calendarRanges as $range => $expected) {
    $calendarTimeSeries = new TestableEChartsTimeSeries();
    $calendarTimeSeries->Create();
    $calendarTimeSeries->SetTestCurrentTimestamp($calendarNow);
    $calendarTimeSeries->SetTestProperty('Sources', json_encode($calendarSource, JSON_THROW_ON_ERROR));
    $calendarTimeSeries->SetTestProperty('Range', $range);
    $calendarTimeSeries->SetTestProperty('DataMode', 'raw');
    $calendarTimeSeries->ApplyChanges();
    $calendarData = json_decode($calendarTimeSeries->GetTimeSeriesData(), true, 512, JSON_THROW_ON_ERROR);
    $expectedStart = (new DateTimeImmutable($expected['start'], $calendarTimezone))->getTimestamp();
    $expectedEnd = (new DateTimeImmutable($expected['end'], $calendarTimezone))->getTimestamp();
    assertGatewayGauge(
        $calendarTimeSeries->GetTestStatus() === IS_ACTIVE
            && $calendarData['range']['key'] === $range
            && $calendarData['range']['calendarAligned'] === true
            && $calendarData['range']['acceptLiveUpdates'] === $expected['live']
            && $calendarData['range']['startTimestamp'] === $expectedStart
            && $calendarData['range']['endTimestamp'] === $expectedEnd
            && $calendarData['range']['durationSeconds'] === $expectedEnd - $expectedStart + 1,
        'Calendar range ' . $range . ' must use local calendar boundaries instead of a rolling duration.'
    );
}

$aggregatedCalendarTimeSeries = new TestableEChartsTimeSeries();
$aggregatedCalendarTimeSeries->Create();
$aggregatedCalendarTimeSeries->SetTestCurrentTimestamp($calendarNow);
$aggregatedCalendarTimeSeries->SetTestProperty('Sources', json_encode($calendarSource, JSON_THROW_ON_ERROR));
$aggregatedCalendarTimeSeries->SetTestProperty('Range', 'yesterday');
$aggregatedCalendarTimeSeries->SetTestProperty('DataMode', 'hour');
$aggregatedCalendarTimeSeries->ApplyChanges();
$aggregatedCalendarData = json_decode(
    $aggregatedCalendarTimeSeries->GetTimeSeriesData(),
    true,
    512,
    JSON_THROW_ON_ERROR
);
assertGatewayGauge(
    $aggregatedCalendarData['range']['startTimestamp']
        === (new DateTimeImmutable('2026-10-06 00:00:00', $calendarTimezone))->getTimestamp()
        && $aggregatedCalendarData['range']['endTimestamp']
        === (new DateTimeImmutable('2026-10-06 23:59:59', $calendarTimezone))->getTimestamp()
        && $aggregatedCalendarData['range']['aggregationLevel'] === 0,
    'Completed calendar periods must retain their complete boundaries when aggregated.'
);

$independentCalendarTimeSeries = new EChartsTimeSeries();
$independentCalendarTimeSeries->Create();
$independentCalendarTimeSeries->SetTestProperty('Sources', json_encode($calendarSource, JSON_THROW_ON_ERROR));
$independentCalendarTimeSeries->SetTestProperty('Range', 'today');
$independentCalendarTimeSeries->SetTestProperty('DataMode', 'raw');
$independentCalendarTimeSeries->SetTestProperty('EnableIPSView', true);
$independentCalendarTimeSeries->SetTestProperty('IPSViewUseTileTimeSettings', false);
$independentCalendarTimeSeries->SetTestProperty('IPSViewRange', 'yesterday');
$independentCalendarTimeSeries->ApplyChanges();
$independentCalendarTile = json_decode(
    $independentCalendarTimeSeries->GetTimeSeriesData(),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$independentCalendarIPSView = $independentCalendarTimeSeries->GetIPSViewHTML();
assertGatewayGauge(
    $independentCalendarTile['range']['key'] === 'today'
        && str_contains($independentCalendarIPSView, '"key":"yesterday"')
        && str_contains($independentCalendarIPSView, '"acceptLiveUpdates":false'),
    'Tile and IPSView must resolve independent calendar ranges through the shared range contract.'
);

$dstCalendarTimeSeries = new TestableEChartsTimeSeries();
$dstCalendarTimeSeries->Create();
$dstCalendarTimeSeries->SetTestCurrentTimestamp(
    (new DateTimeImmutable('2026-10-26 12:00:00', $calendarTimezone))->getTimestamp()
);
$dstCalendarTimeSeries->SetTestProperty('Sources', json_encode($calendarSource, JSON_THROW_ON_ERROR));
$dstCalendarTimeSeries->SetTestProperty('Range', 'yesterday');
$dstCalendarTimeSeries->SetTestProperty('DataMode', 'raw');
$dstCalendarTimeSeries->ApplyChanges();
$dstCalendarData = json_decode($dstCalendarTimeSeries->GetTimeSeriesData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $dstCalendarData['range']['durationSeconds'] === 90000,
    'Calendar ranges must respect the 25-hour day when daylight saving time ends.'
);
date_default_timezone_set($previousTimezone);

$invalidRealtimeYesterday = new EChartsTimeSeries();
$invalidRealtimeYesterday->Create();
$invalidRealtimeYesterday->SetTestProperty('Sources', json_encode($calendarSource, JSON_THROW_ON_ERROR));
$invalidRealtimeYesterday->SetTestProperty('Range', 'yesterday');
$invalidRealtimeYesterday->SetTestProperty('DataMode', 'realtime');
$invalidRealtimeYesterday->ApplyChanges();
assertGatewayGauge(
    $invalidRealtimeYesterday->GetTestStatus() === 202,
    'The completed Yesterday range must reject archive-free real-time mode.'
);

$invalidCustomRangeTimeSeries = new EChartsTimeSeries();
$invalidCustomRangeTimeSeries->Create();
$invalidCustomRangeTimeSeries->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4717,
    'Label'                   => '',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'auto'
]], JSON_THROW_ON_ERROR));
$invalidCustomRangeTimeSeries->SetTestProperty('Range', 'custom');
$invalidCustomRangeTimeSeries->SetTestProperty('CustomRangeValue', 0);
$invalidCustomRangeTimeSeries->ApplyChanges();
assertGatewayGauge(
    $invalidCustomRangeTimeSeries->GetTestStatus() === 202,
    'An invalid custom rolling range must be rejected as an invalid Time Series configuration.'
);

$invalidTimeAxisFormat = new EChartsTimeSeries();
$invalidTimeAxisFormat->Create();
$invalidTimeAxisFormat->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4717,
    'Label'                   => '',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'auto'
]], JSON_THROW_ON_ERROR));
$invalidTimeAxisFormat->SetTestProperty('TimeAxisLabelFormat', 'javascript');
$invalidTimeAxisFormat->ApplyChanges();
assertGatewayGauge(
    $invalidTimeAxisFormat->GetTestStatus() === 202,
    'Unsupported time-axis label formats must not enter the renderer contract.'
);

$invalidGapDetection = new EChartsTimeSeries();
$invalidGapDetection->Create();
$invalidGapDetection->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4717,
    'Label'                   => '',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'auto'
]], JSON_THROW_ON_ERROR));
$invalidGapDetection->SetTestProperty('GapDetectionMode', 'custom');
$invalidGapDetection->SetTestProperty('GapThresholdMinutes', 0);
$invalidGapDetection->ApplyChanges();
assertGatewayGauge(
    $invalidGapDetection->GetTestStatus() === 202,
    'A custom gap detection interval outside the supported range must be rejected.'
);

$invalidIPSViewTimeSettings = new EChartsTimeSeries();
$invalidIPSViewTimeSettings->Create();
$invalidIPSViewTimeSettings->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4717,
    'Label'                   => '',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'auto'
]], JSON_THROW_ON_ERROR));
$invalidIPSViewTimeSettings->SetTestProperty('EnableIPSView', true);
$invalidIPSViewTimeSettings->SetTestProperty('IPSViewUseTileTimeSettings', false);
$invalidIPSViewTimeSettings->SetTestProperty('IPSViewRange', 'custom');
$invalidIPSViewTimeSettings->SetTestProperty('IPSViewCustomRangeValue', 0);
$invalidIPSViewTimeSettings->ApplyChanges();
assertGatewayGauge(
    $invalidIPSViewTimeSettings->GetTestStatus() === 202,
    'Invalid independent IPSView time settings must invalidate the active Time Series configuration.'
);

$axisRangeTimeSeries = new EChartsTimeSeries();
$axisRangeTimeSeries->Create();
$axisRangeTimeSeries->SetTestProperty('Sources', json_encode([
    [
        'VariableID'              => 4711,
        'Label'                   => 'Manual temperature range',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => '',
        'Style'                   => 'line',
        'Reducer'                 => 'auto',
        'AxisRangeMode'           => 'manual',
        'AxisMinimum'             => -20.0,
        'AxisMaximum'             => 50.0
    ],
    [
        'VariableID'              => 4717,
        'Label'                   => 'Automatic temperature range',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => '',
        'Style'                   => 'line',
        'Reducer'                 => 'auto',
        'AxisRangeMode'           => 'auto'
    ]
], JSON_THROW_ON_ERROR));
$axisRangeTimeSeries->SetTestProperty('DataMode', 'realtime');
$axisRangeTimeSeries->ApplyChanges();
$axisRangeData = json_decode($axisRangeTimeSeries->GetTimeSeriesData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $axisRangeTimeSeries->GetTestStatus() === IS_ACTIVE
        && count($axisRangeData['axes']) === 1
        && $axisRangeData['axes'][0]['minimum'] === -20.0
        && $axisRangeData['axes'][0]['maximum'] === 50.0
        && $axisRangeData['series'][0]['axisIndex'] === 0
        && $axisRangeData['series'][1]['axisIndex'] === 0,
    'An explicit manual range must govern the shared unit axis while automatic sources adopt it.'
);

$presentationRangeTimeSeries = new EChartsTimeSeries();
$presentationRangeTimeSeries->Create();
$presentationRangeTimeSeries->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4715,
    'Label'                   => 'Presented pressure range',
    'UseVariablePresentation' => false,
    'Unit'                    => 'hPa',
    'Decimals'                => 2,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'auto',
    'AxisRangeMode'           => 'presentation',
    'AxisMinimum'             => 0.0,
    'AxisMaximum'             => 100.0
]], JSON_THROW_ON_ERROR));
$presentationRangeTimeSeries->SetTestProperty('DataMode', 'realtime');
$presentationRangeTimeSeries->ApplyChanges();
$presentationRangeData = json_decode(
    $presentationRangeTimeSeries->GetTimeSeriesData(),
    true,
    512,
    JSON_THROW_ON_ERROR
);
assertGatewayGauge(
    $presentationRangeTimeSeries->GetTestStatus() === IS_ACTIVE
        && $presentationRangeData['axes'][0]['minimum'] === 950.0
        && $presentationRangeData['axes'][0]['maximum'] === 1050.0
        && $presentationRangeData['axes'][0]['unit'] === 'hPa',
    'Presentation axis mode must read its range independently from unit and decimal presentation settings.'
);

$conflictingRangeTimeSeries = new EChartsTimeSeries();
$conflictingRangeTimeSeries->Create();
$conflictingRangeTimeSeries->SetTestProperty('Sources', json_encode([
    [
        'VariableID'              => 4711,
        'Label'                   => '',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => '',
        'Style'                   => 'line',
        'Reducer'                 => 'auto',
        'AxisRangeMode'           => 'manual',
        'AxisMinimum'             => -20.0,
        'AxisMaximum'             => 50.0
    ],
    [
        'VariableID'              => 4717,
        'Label'                   => '',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => '',
        'Style'                   => 'line',
        'Reducer'                 => 'auto',
        'AxisRangeMode'           => 'manual',
        'AxisMinimum'             => -10.0,
        'AxisMaximum'             => 40.0
    ]
], JSON_THROW_ON_ERROR));
$conflictingRangeTimeSeries->SetTestProperty('DataMode', 'realtime');
$conflictingRangeTimeSeries->ApplyChanges();
assertGatewayGauge(
    $conflictingRangeTimeSeries->GetTestStatus() === 202,
    'Time Series must reject conflicting explicit ranges for a shared unit axis.'
);

$multiAxesTimeSeries = new EChartsTimeSeries();
$multiAxesTimeSeries->Create();
$multiAxisSources = [];
foreach (range(0, 7) as $axisIndex) {
    $variableID = 4800 + $axisIndex;
    $GLOBALS['symconTestVariables'][$variableID] = [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000700 + $axisIndex,
        'Value'           => 10.0 + $axisIndex,
        'Name'            => 'Axis source ' . ($axisIndex + 1)
    ];
    $multiAxisSources[] = [
        'VariableID'              => $variableID,
        'Label'                   => '',
        'UseVariablePresentation' => false,
        'Unit'                    => 'unit-' . ($axisIndex + 1),
        'Decimals'                => 1,
        'Color'                   => '',
        'Style'                   => 'line',
        'Reducer'                 => 'auto',
        'AxisPosition'            => ['auto', 'right', 'left', 'auto', 'auto', 'auto', 'auto', 'auto'][$axisIndex]
    ];
}
$multiAxesTimeSeries->SetTestProperty('Sources', json_encode($multiAxisSources, JSON_THROW_ON_ERROR));
$multiAxesTimeSeries->SetTestProperty('DataMode', 'realtime');
$multiAxesTimeSeries->ApplyChanges();
$multiAxesData = json_decode($multiAxesTimeSeries->GetTimeSeriesData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $multiAxesTimeSeries->GetTestStatus() === IS_ACTIVE
        && count($multiAxesData['axes']) === 8
        && array_column($multiAxesData['axes'], 'position')
            === ['left', 'right', 'left', 'right', 'left', 'right', 'left', 'right']
        && array_column($multiAxesData['axes'], 'positionIndex') === [0, 0, 1, 1, 2, 2, 3, 3]
        && array_column($multiAxesData['series'], 'axisIndex') === range(0, 7),
    'Time Series must support one positioned value axis for every one of its eight allowed sources.'
);
foreach (array_column($multiAxisSources, 'VariableID') as $variableID) {
    unset($GLOBALS['symconTestVariables'][$variableID]);
}

$conflictingAxesTimeSeries = new EChartsTimeSeries();
$conflictingAxesTimeSeries->Create();
$conflictingAxesTimeSeries->SetTestProperty('Sources', json_encode([
    [
        'VariableID'              => 4711,
        'Label'                   => '',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => '',
        'Style'                   => 'line',
        'Reducer'                 => 'auto',
        'AxisPosition'            => 'left'
    ],
    [
        'VariableID'              => 4713,
        'Label'                   => '',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => '',
        'Style'                   => 'line',
        'Reducer'                 => 'auto',
        'AxisPosition'            => 'right'
    ]
], JSON_THROW_ON_ERROR));
$conflictingAxesTimeSeries->ApplyChanges();
$conflictingAxesDiagnostic = json_decode(
    $conflictingAxesTimeSeries->GetTimeSeriesDiagnostic(),
    true,
    512,
    JSON_THROW_ON_ERROR
);
assertGatewayGauge(
    $conflictingAxesTimeSeries->GetTestStatus() === 202
        && $conflictingAxesDiagnostic['success'] === false
        && $conflictingAxesDiagnostic['status'] === 202
        && str_contains($conflictingAxesDiagnostic['error'], 'same unit'),
    'Time Series must reject conflicting sides for a shared unit and return the error diagnostically.'
);

$invalidDesignTimeSeries = new EChartsTimeSeries();
$invalidDesignTimeSeries->Create();
$invalidDesignTimeSeries->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4711,
    'Label'                   => '',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'auto'
]], JSON_THROW_ON_ERROR));
$invalidDesignTimeSeries->SetTestProperty('LegendPosition', 'sideways');
$invalidDesignTimeSeries->ApplyChanges();
assertGatewayGauge(
    $invalidDesignTimeSeries->GetTestStatus() === 202,
    'Time Series must reject unsupported tile design values.'
);
$invalidDesignTimeSeries->SetTestProperty('LegendPosition', 'top');
$invalidDesignTimeSeries->SetTestProperty('TitleFontSizePercent', 201);
$invalidDesignTimeSeries->ApplyChanges();
assertGatewayGauge($invalidDesignTimeSeries->GetTestStatus() === 202, 'Time Series must reject oversized title fonts.');
$invalidDesignTimeSeries->SetTestProperty('TitleFontSizePercent', 100);
$invalidDesignTimeSeries->SetTestProperty('GridColor', 0x1000000);
$invalidDesignTimeSeries->ApplyChanges();
assertGatewayGauge($invalidDesignTimeSeries->GetTestStatus() === 202, 'Time Series must reject invalid grid colors.');
$invalidDesignTimeSeries->SetTestProperty('GridColor', -1);
$invalidDesignTimeSeries->SetTestProperty('EnableIPSView', true);
$invalidDesignTimeSeries->SetTestProperty('IPSViewUseTileDesign', false);
$invalidDesignTimeSeries->SetTestProperty('IPSViewAxisFontSizePercent', 49);
$invalidDesignTimeSeries->ApplyChanges();
assertGatewayGauge($invalidDesignTimeSeries->GetTestStatus() === 202, 'Time Series must validate independent IPSView typography.');

$invalidSourceDesignTimeSeries = new EChartsTimeSeries();
$invalidSourceDesignTimeSeries->Create();
$invalidSourceDesignTimeSeries->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4711,
    'Label'                   => '',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'area',
    'Reducer'                 => 'auto',
    'UseIndividualDesign'     => true,
    'SeriesAreaFillMode'      => 'svg',
    'SeriesAreaSVG'           => '<svg viewBox="0 0 10 10"><script>alert(1)</script></svg>'
]], JSON_THROW_ON_ERROR));
$invalidSourceDesignTimeSeries->ApplyChanges();
assertGatewayGauge(
    $invalidSourceDesignTimeSeries->GetTestStatus() === 202,
    'Time Series must reject unsafe SVG area patterns in an active individual source design.'
);

$invalidAnnotationTimeSeries = new EChartsTimeSeries();
$invalidAnnotationTimeSeries->Create();
$invalidAnnotationTimeSeries->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4711,
    'Label'                   => '',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Color'                   => '',
    'Style'                   => 'line',
    'Reducer'                 => 'auto'
]], JSON_THROW_ON_ERROR));
$invalidAnnotationTimeSeries->SetTestProperty('Annotations', json_encode([[
    'VariableID'       => 4711,
    'Type'             => 'area',
    'Label'            => 'Invalid range',
    'Value'            => 25.0,
    'Maximum'          => 20.0,
    'Color'            => -1,
    'LineType'         => 'solid',
    'LineWidthPercent' => 100,
    'OpacityPercent'   => 18
]], JSON_THROW_ON_ERROR));
$invalidAnnotationTimeSeries->ApplyChanges();
assertGatewayGauge(
    $invalidAnnotationTimeSeries->GetTestStatus() === 202,
    'Time Series must reject a value range whose maximum is not greater than its minimum.'
);

$barCategory = new EChartsBarCategory();
$barCategory->Create();
$barCategory->SetTestProperty('Sources', json_encode([
    [
        'VariableID'              => 4711,
        'Label'                   => 'Living room',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => 0xF0442D
    ],
    [
        'VariableID'              => 4717,
        'Label'                   => 'Outside',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => -1
    ]
], JSON_THROW_ON_ERROR));
$barCategory->SetTestProperty('Title', 'Temperatures');
$barCategory->SetTestProperty('Orientation', 'horizontal');
$barCategory->SetTestProperty('SortOrder', 'descending');
$barCategory->SetTestProperty('EnableIPSView', true);
$barCategory->SetTestProperty('IPSViewUseTileDesign', false);
$barCategory->SetTestProperty('IPSViewOrientation', 'vertical');
$barCategory->SetTestProperty('IPSViewEChartsTheme', 'dark');
$barCategory->ApplyChanges();
assertTransientRetry($barCategory, 'Category Bar');
$barData = json_decode($barCategory->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $barCategory->GetTestStatus() === IS_ACTIVE
        && $barCategory->GetTestVisualizationType() === 1
        && $barData['family'] === 'bar'
        && $barData['variant'] === 'category'
        && $barData['bar']['orientation'] === 'horizontal'
        && $barData['bar']['sortOrder'] === 'descending'
        && $barData['bar']['unit'] === '°C'
        && $barData['items'][0]['color'] === '#F0442D'
        && $barData['items'][1]['color'] === '',
    'Category Bar must expose current same-unit values and its Tile design.'
);
$barTile = $barCategory->GetVisualizationTile();
$barIPSView = $barCategory->GetIPSViewHTML();
$barForm = $barCategory->GetConfigurationForm();
$barFormDefinition = json_decode($barForm, true, 512, JSON_THROW_ON_ERROR);
$barSourcesForm = current(array_filter(
    $barFormDefinition['elements'],
    static fn (array $element): bool => ($element['name'] ?? '') === 'Sources'
));
assertGatewayGauge(
    str_contains($barTile, 'echarts-bar-category-root')
        && str_contains($barTile, '"orientation":"horizontal"')
        && str_contains($barIPSView, '"orientation":"vertical"')
        && str_contains($barIPSView, '"theme":"dark"')
        && strlen($barTile) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($barIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Category Bar must render independent Tile and IPSView designs below the output limit.'
);
assertGatewayGauge(
    is_array($barSourcesForm)
        && array_column($barSourcesForm['columns'], 'name') === [
            'VariableID', 'Category', 'Series', 'UseVariablePresentation', 'Unit', 'Decimals', 'Minimum', 'Maximum', 'Color'
        ]
        && array_column($barSourcesForm['form'], 'name') === [
            'VariableID', 'Category', 'Series', 'UseVariablePresentation', 'Unit', 'Decimals', 'Minimum', 'Maximum', 'Color'
        ]
        && str_contains($barForm, '"name":"BarMode"')
        && str_contains($barForm, '"name":"IPSViewBarMode"')
        && str_contains($barForm, '"name":"BarFillMode"')
        && str_contains($barForm, '"name":"IPSViewBarSVG"'),
    'Category Bar configuration must provide an explicit Category and Series row editor.'
);
$barUpdatesBefore = count($barCategory->GetTestVisualizationUpdates());
$barCategory->MessageSink(1780000100, 4711, VM_UPDATE, []);
assertGatewayGauge(
    count($barCategory->GetTestVisualizationUpdates()) > $barUpdatesBefore,
    'Category Bar must publish a new visualization state after a source update.'
);
$validBarSVG = '<svg viewBox="0 0 40 20"><circle cx="10" cy="10" r="4" fill="#AABBCC"/></svg>';
$otherBarSVG = '<svg viewBox="0 0 20 20"><rect width="8" height="8" fill="#112233"/></svg>';
$barCategory->SetTestProperty('BarFillMode', 'svg');
$barCategory->SetTestProperty('BarSVG', $validBarSVG);
$barCategory->SetTestProperty('BarSVGSizePercent', 150);
$barCategory->SetTestProperty('IPSViewBarFillMode', 'svg');
$barCategory->SetTestProperty('IPSViewBarSVG', $otherBarSVG);
$barCategory->ApplyChanges();
$categorySVGForm = json_decode($barCategory->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    (findGaugeFormElement($categorySVGForm['elements'], 'BarSVG')['visible'] ?? null) === true
        && (findGaugeFormElement($categorySVGForm['elements'], 'IPSViewBarSVG')['visible'] ?? null) === true,
    'Category Bar form must reveal the saved SVG choices in both designers.'
);
$barCategory->UpdateBarDesignForm(false, 'svg');
assertGatewayGauge(
    array_slice($barCategory->GetTestFormUpdates(), -2) === [
        ['Field' => 'BarSVG', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'BarSVGSizePercent', 'Parameter' => 'visible', 'Value' => true]
    ],
    'Category Bar must reveal its SVG controls immediately.'
);
$categoryPatternData = json_decode($barCategory->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
$categoryPatternIPSView = $barCategory->GetIPSViewHTML();
assertGatewayGauge(
    $barCategory->GetTestStatus() === IS_ACTIVE
        && $categoryPatternData['bar']['style']['barFillMode'] === 'svg'
        && $categoryPatternData['bar']['style']['barSVGSizePercent'] === 150
        && $categoryPatternData['bar']['style']['barPatternAspectRatio'] === 2.0
        && str_starts_with($categoryPatternData['bar']['style']['barPatternImage'], 'data:image/svg+xml;base64,')
        && str_contains($categoryPatternIPSView, '"barPatternAspectRatio":1')
        && str_contains($categoryPatternIPSView, 'SymconEChartsPattern'),
    'Category Bar must use safe, independent SVG patterns for Tile and IPSView.'
);
$barCategory->SetTestProperty('BarSVG', base64_encode($largeBackgroundSvg));
$barCategory->ApplyChanges();
$largeBarCategoryTile = $barCategory->GetVisualizationTile();
assertGatewayGauge(
    $barCategory->GetTestStatus() === IS_ACTIVE && strlen($largeBarCategoryTile) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'A near-limit SVG Category Bar pattern must fit the output buffer (actual: '
        . strlen($largeBarCategoryTile) . ').'
);
$maximumBarSVG = '<svg viewBox="0 0 10 10">'
    . str_repeat('<path d="M0 0 ' . str_repeat('L1 1 ', 10300) . '" fill="#123456"/>', 5)
    . '</svg>';
assertGatewayGauge(strlen($maximumBarSVG) < 262144, 'The maximal SVG Bar fixture exceeds the import limit.');
$barCategory->SetTestProperty('BarSVG', base64_encode($maximumBarSVG));
$barCategory->ApplyChanges();
$maximumBarCategoryTile = $barCategory->GetVisualizationTile();
assertGatewayGauge(
    $barCategory->GetTestStatus() === IS_ACTIVE && strlen($maximumBarCategoryTile) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'A maximum-size SVG Category Bar pattern must fit the output buffer (actual: '
        . strlen($maximumBarCategoryTile) . ').'
);
$barCategory->SetTestProperty('BarSVG', '<svg viewBox="0 0 10 10"><script>alert(1)</script></svg>');
$barCategory->ApplyChanges();
assertGatewayGauge($barCategory->GetTestStatus() === 202, 'Category Bar must reject unsafe active SVG patterns.');
$invalidBarCategory = new EChartsBarCategory();
$invalidBarCategory->Create();
$invalidBarCategory->SetTestProperty('Sources', json_encode([
    [
        'VariableID'              => 4711,
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => -1
    ],
    [
        'VariableID'              => 4713,
        'UseVariablePresentation' => false,
        'Unit'                    => '%',
        'Decimals'                => 0,
        'Color'                   => -1
    ]
], JSON_THROW_ON_ERROR));
$invalidBarCategory->ApplyChanges();
assertGatewayGauge(
    $invalidBarCategory->GetTestStatus() === 201,
    'Category Bar must reject sources with different effective units.'
);

$sameNameSources = [];
foreach ([
    [4920, 8.5], [4921, 21.0], [4922, 20.5], [4923, 19.0]
] as [$variableID, $value]) {
    $GLOBALS['symconTestVariables'][$variableID] = [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000200,
        'Value'           => $value,
        'Name'            => 'Temperatur'
    ];
    $sameNameSources[] = [
        'VariableID'              => $variableID,
        'Category'                => 'Temperatur',
        'Series'                  => '',
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => -1
    ];
}
$sameNameBar = new EChartsBarCategory();
$sameNameBar->Create();
$sameNameBar->SetTestProperty('Sources', json_encode($sameNameSources, JSON_THROW_ON_ERROR));
$sameNameBar->SetTestProperty('BarMode', 'grouped');
$sameNameBar->ApplyChanges();
assertGatewayGauge(
    $sameNameBar->GetTestStatus() === IS_ACTIVE,
    'Category Bar must accept equal variable names in one category when source IDs differ.'
);
$sameNameData = json_decode($sameNameBar->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    array_column($sameNameData['items'], 'series') === [
        'Temperatur', 'Temperatur', 'Temperatur', 'Temperatur'
    ] && array_column($sameNameData['items'], 'id') === [
        'variable-4920', 'variable-4921', 'variable-4922', 'variable-4923'
    ] && array_column($sameNameData['items'], 'seriesKey') === [
        'variable-4920', 'variable-4921', 'variable-4922', 'variable-4923'
    ],
    'Category Bar must distinguish identical labels only by variable ID and keep every source.'
);
$GLOBALS['symconTestVariables'][4920]['Name'] = 'Raumtemperatur';
$renamedData = json_decode($sameNameBar->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $renamedData['items'][0]['id'] === 'variable-4920'
        && $renamedData['items'][0]['variableID'] === 4920
        && $renamedData['items'][0]['series'] === 'Raumtemperatur'
        && $renamedData['items'][1]['series'] === 'Temperatur',
    'Renaming a variable may change its label but never its technical source identity.'
);
foreach ($sameNameSources as $source) {
    unset($GLOBALS['symconTestVariables'][$source['VariableID']]);
}

$groupedBarSources = [];
foreach ([
    [4900, 'Kitchen today', 18.0, 'Kitchen', 'Today'],
    [4901, 'Kitchen yesterday', 20.0, 'Kitchen', 'Yesterday'],
    [4902, 'Office today', 24.0, 'Office', 'Today'],
    [4903, 'Office yesterday', 22.0, 'Office', 'Yesterday']
] as [$variableID, $name, $value, $category, $series]) {
    $GLOBALS['symconTestVariables'][$variableID] = [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000200,
        'Value'           => $value,
        'Name'            => $name
    ];
    $groupedBarSources[] = [
        'VariableID'              => $variableID,
        'Category'                => $category,
        'Series'                  => $series,
        'UseVariablePresentation' => false,
        'Unit'                    => '°C',
        'Decimals'                => 1,
        'Color'                   => $series === 'Today' ? 0xAA0000 : 0x0000AA
    ];
}
$groupedBar = new EChartsBarCategory();
$groupedBar->Create();
$groupedBar->SetTestProperty('Sources', json_encode($groupedBarSources, JSON_THROW_ON_ERROR));
$groupedBar->SetTestProperty('BarMode', 'grouped');
$groupedBar->SetTestProperty('IPSViewUseTileDesign', false);
$groupedBar->SetTestProperty('IPSViewBarMode', 'stacked');
$groupedBar->ApplyChanges();
$groupedBarData = json_decode($groupedBar->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $groupedBar->GetTestStatus() === IS_ACTIVE
        && $groupedBarData['bar']['mode'] === 'grouped'
        && $groupedBarData['items'][0]['category'] === 'Kitchen'
        && $groupedBarData['items'][0]['series'] === 'Today'
        && str_contains($groupedBar->GetVisualizationTile(), '"mode":"grouped"')
        && str_contains($groupedBar->GetIPSViewHTML(), '"mode":"stacked"'),
    'Category Bar must support independent grouped and stacked output modes.'
);
$automaticStackedBar = new EChartsBarCategory();
$automaticStackedBar->Create();
$automaticStackedBar->SetTestProperty(
    'Sources',
    json_encode(array_map(
        static function (array $source): array
        {
            $source['Series'] = $source['Category'] . ' ' . $source['Series'];
            $source['Category'] = '';

            return $source;
        },
        array_slice($groupedBarSources, 0, 3)
    ), JSON_THROW_ON_ERROR)
);
$automaticStackedBar->SetTestProperty('BarMode', 'stacked');
$automaticStackedBar->ApplyChanges();
assertGatewayGauge(
    $automaticStackedBar->GetTestStatus() === IS_ACTIVE
        && str_contains($automaticStackedBar->GetVisualizationTile(), '"mode":"stacked"'),
    'Grouped and stacked Category Bars must accept ordinary source lists without explicit categories.'
);
$legacyCategorySources = array_slice($groupedBarSources, 0, 2);
foreach ($legacyCategorySources as &$legacyCategorySource) {
    $legacyCategorySource['Label'] = $legacyCategorySource['Category'];
    unset($legacyCategorySource['Category']);
}
unset($legacyCategorySource);
$legacyCategoryBar = new EChartsBarCategory();
$legacyCategoryBar->Create();
$legacyCategoryBar->SetTestProperty('Sources', json_encode($legacyCategorySources, JSON_THROW_ON_ERROR));
$legacyCategoryBar->SetTestProperty('BarMode', 'grouped');
$legacyCategoryBar->ApplyChanges();
$legacyCategoryData = json_decode($legacyCategoryBar->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $legacyCategoryBar->GetTestStatus() === IS_ACTIVE
        && $legacyCategoryData['items'][0]['category'] === 'Kitchen'
        && $legacyCategoryData['items'][0]['series'] === 'Today',
    'The original unpublished Label/Series source fields must remain readable.'
);
$interimCategorySources = array_slice($groupedBarSources, 0, 2);
foreach ($interimCategorySources as &$interimCategorySource) {
    $interimCategorySource['Label'] = $interimCategorySource['Series'];
    unset($interimCategorySource['Series']);
}
unset($interimCategorySource);
$interimCategoryBar = new EChartsBarCategory();
$interimCategoryBar->Create();
$interimCategoryBar->SetTestProperty('Sources', json_encode($interimCategorySources, JSON_THROW_ON_ERROR));
$interimCategoryBar->SetTestProperty('BarMode', 'grouped');
$interimCategoryBar->ApplyChanges();
$interimCategoryData = json_decode($interimCategoryBar->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $interimCategoryBar->GetTestStatus() === IS_ACTIVE
        && $interimCategoryData['items'][0]['category'] === 'Kitchen'
        && $interimCategoryData['items'][0]['series'] === 'Today',
    'The interim unpublished Category/Label source fields must remain readable.'
);
$incompleteGroupedBar = new EChartsBarCategory();
$incompleteGroupedBar->Create();
$incompleteGroupedBar->SetTestProperty(
    'Sources',
    json_encode(array_slice($groupedBarSources, 0, 3), JSON_THROW_ON_ERROR)
);
$incompleteGroupedBar->SetTestProperty('BarMode', 'grouped');
$incompleteGroupedBar->ApplyChanges();
assertGatewayGauge(
    $incompleteGroupedBar->GetTestStatus() === IS_ACTIVE,
    'Grouped and stacked Category Bars must allow categories with different series.'
);
$duplicateGroupedBar = new EChartsBarCategory();
$duplicateGroupedBar->Create();
$duplicateSources = array_slice($groupedBarSources, 0, 2);
$duplicateSources[1]['Series'] = $duplicateSources[0]['Series'];
$duplicateGroupedBar->SetTestProperty('Sources', json_encode($duplicateSources, JSON_THROW_ON_ERROR));
$duplicateGroupedBar->SetTestProperty('BarMode', 'grouped');
$duplicateGroupedBar->ApplyChanges();
$duplicateGroupedData = json_decode($duplicateGroupedBar->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $duplicateGroupedBar->GetTestStatus() === IS_ACTIVE
        && array_column($duplicateGroupedData['items'], 'seriesKey') === ['variable-4900', 'variable-4901']
        && array_column($duplicateGroupedData['items'], 'series') === ['Today', 'Today'],
    'Grouped and stacked Category Bars must retain equal category/series labels as distinct source IDs.'
);
$collidingSeriesSources = array_slice($groupedBarSources, 0, 3);
$collidingSeriesSources[1]['Series'] = 'Today';
$collidingSeriesSources[2]['Series'] = 'Today (#4900)';
$collidingSeriesBar = new EChartsBarCategory();
$collidingSeriesBar->Create();
$collidingSeriesBar->SetTestProperty('Sources', json_encode($collidingSeriesSources, JSON_THROW_ON_ERROR));
$collidingSeriesBar->SetTestProperty('BarMode', 'grouped');
$collidingSeriesBar->ApplyChanges();
$collidingSeriesData = json_decode($collidingSeriesBar->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    array_column($collidingSeriesData['items'], 'series') === [
        'Today', 'Today', 'Today (#4900)'
    ],
    'Category Bar must preserve configured group names without adding source IDs.'
);
foreach ($groupedBarSources as $source) {
    unset($GLOBALS['symconTestVariables'][$source['VariableID']]);
}

require __DIR__ . '/bar_category_large_sources.php';
require __DIR__ . '/bar_category_gradient.php';

// These completed fixtures retain inline runtimes and preview/update histories.
// Release them before the remaining families run under PHP's normal memory limit.
unset(
    $barCategory, $invalidBarCategory, $sameNameBar, $groupedBar, $automaticStackedBar,
    $legacyCategoryBar, $interimCategoryBar, $incompleteGroupedBar, $duplicateGroupedBar,
    $collidingSeriesBar, $barTile, $barIPSView, $barForm, $categorySVGForm,
    $categoryPatternIPSView, $largeBarCategoryTile, $maximumBarCategoryTile
);
gc_collect_cycles();

$barHistory = new TestableEChartsBarHistory();
$barHistory->Create();
$barHistory->SetTestCurrentTimestamp(1780000400);
$barHistory->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4711,
    'Label'                   => 'Temperature history',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Reducer'                 => 'average',
    'Color'                   => 0xE5754F
]], JSON_THROW_ON_ERROR));
$barHistory->SetTestProperty('Range', '1h');
$barHistory->SetTestProperty('DataMode', 'auto');
$barHistory->SetTestProperty('PointBudget', 1000);
$barHistory->SetTestProperty('TimeAxisLabelFormat', 'date-time');
$barHistory->SetTestProperty('ShowValues', true);
$barHistory->SetTestProperty('ShowGrid', false);
$barHistory->SetTestProperty('RoundedBars', true);
$barHistory->SetTestProperty('BarWidthPercent', 60);
$barHistory->SetTestProperty('BarFillMode', 'gradient');
$barHistory->SetTestProperty('BarGradientColor', 0x55CCAA);
$barHistory->SetTestProperty('BarOpacityPercent', 65);
$barHistory->SetTestProperty('BarCornerRadius', 10);
$barHistory->SetTestProperty('TitleFontSizePercent', 130);
$barHistory->SetTestProperty('AxisFontSizePercent', 115);
$barHistory->SetTestProperty('ValueFontSizePercent', 125);
$barHistory->SetTestProperty('TitleColor', 0xFFEEDD);
$barHistory->SetTestProperty('AxisColor', 0xDDEEFF);
$barHistory->SetTestProperty('ValueColor', 0x112233);
$barHistory->SetTestProperty('GridColor', 0x445566);
$barHistory->ApplyChanges();
$barHistoryData = json_decode($barHistory->GetBarHistoryData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $barHistory->GetTestStatus() === IS_ACTIVE
        && $barHistory->GetTestVisualizationType() === 1
        && $barHistory->GetTestReferences() === [4711],
    'Historical Bar must initialize as a native visualization with one deterministic source reference.'
);
assertGatewayGauge(
    $barHistoryData['family'] === 'bar'
        && $barHistoryData['variant'] === 'history'
        && $barHistoryData['series'][0]['id'] === 'variable-4711'
        && $barHistoryData['series'][0]['variableID'] === 4711
        && $barHistoryData['range']['aggregationLevel'] === 6
        && $barHistoryData['range']['pointLimitPerSeries'] === 1000
        && $barHistoryData['series'][0]['label'] === 'Temperature history'
        && $barHistoryData['series'][0]['color'] === '#E5754F'
        && $barHistoryData['series'][0]['effectiveReducer'] === 'average'
        && $barHistoryData['bar']['style']['showValues'] === true
        && $barHistoryData['bar']['style']['showGrid'] === false
        && $barHistoryData['bar']['style']['enableZoom'] === true
        && $barHistoryData['bar']['style']['roundedBars'] === true
        && $barHistoryData['bar']['style']['barWidthPercent'] === 60
        && $barHistoryData['bar']['style']['barFillMode'] === 'gradient'
        && $barHistoryData['bar']['style']['barGradientColor'] === '#55CCAA'
        && $barHistoryData['bar']['style']['barOpacityPercent'] === 65
        && $barHistoryData['bar']['style']['barCornerRadius'] === 10
        && $barHistoryData['bar']['style']['titleFontSizePercent'] === 130
        && $barHistoryData['bar']['style']['axisFontSizePercent'] === 115
        && $barHistoryData['bar']['style']['valueFontSizePercent'] === 125
        && $barHistoryData['bar']['style']['titleColor'] === '#FFEEDD'
        && $barHistoryData['bar']['style']['axisColor'] === '#DDEEFF'
        && $barHistoryData['bar']['style']['valueColor'] === '#112233'
        && $barHistoryData['bar']['style']['gridColor'] === '#445566',
    'Historical Bar must preserve archive, presentation and tile-design settings in its chart model.'
);
$barHistory->SetTestProperty('BarFillMode', 'svg');
$barHistory->SetTestProperty('BarSVG', $validBarSVG);
$barHistory->SetTestProperty('BarSVGSizePercent', 175);
$barHistory->ApplyChanges();
$historySVGFormInstance = new EChartsBarHistory();
$historySVGFormInstance->Create();
$historySVGFormInstance->SetTestProperty('BarFillMode', 'svg');
$historySVGForm = json_decode($historySVGFormInstance->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    (findGaugeFormElement($historySVGForm['elements'], 'BarSVG')['visible'] ?? null) === true,
    'Historical Bar form must reveal the saved Tile SVG pattern.'
);
$historyPatternData = json_decode($barHistory->GetBarHistoryData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $barHistory->GetTestStatus() === IS_ACTIVE
        && $historyPatternData['bar']['style']['barFillMode'] === 'svg'
        && $historyPatternData['bar']['style']['barSVGSizePercent'] === 175
        && $historyPatternData['bar']['style']['barPatternAspectRatio'] === 2.0
        && str_starts_with($historyPatternData['bar']['style']['barPatternImage'], 'data:image/svg+xml;base64,'),
    'Historical Bar must expose a sanitized SVG pattern without changing the archive series.'
);
$barHistory->SetTestProperty('BarSVG', base64_encode($largeBackgroundSvg));
$barHistory->ApplyChanges();
$largeBarHistoryTile = $barHistory->GetVisualizationTile();
assertGatewayGauge(
    $barHistory->GetTestStatus() === IS_ACTIVE && strlen($largeBarHistoryTile) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'A near-limit SVG Historical Bar pattern must fit the output buffer (actual: '
        . strlen($largeBarHistoryTile) . ').'
);
$barHistory->SetTestProperty('BarSVG', base64_encode($maximumBarSVG));
$barHistory->ApplyChanges();
$maximumBarHistoryTile = $barHistory->GetVisualizationTile();
assertGatewayGauge(
    $barHistory->GetTestStatus() === IS_ACTIVE && strlen($maximumBarHistoryTile) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'A maximum-size SVG Historical Bar pattern must fit the output buffer (actual: '
        . strlen($maximumBarHistoryTile) . ').'
);
$barHistory->SetTestProperty('BarSVG', '<svg viewBox="0 0 10 10"><script>alert(1)</script></svg>');
$barHistory->ApplyChanges();
assertGatewayGauge($barHistory->GetTestStatus() === 202, 'Historical Bar must reject unsafe active SVG patterns.');
$barHistoryTile = new EChartsBarHistory();
$barHistoryTile->Create();
$barHistoryTile->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4711,
    'Label'                   => 'Temperature history',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Reducer'                 => 'average',
    'Color'                   => 0xE5754F
]], JSON_THROW_ON_ERROR));
$barHistoryTile->SetTestProperty('Range', '1h');
$barHistoryTile->SetTestProperty('DataMode', 'raw');
$barHistoryTile->SetTestProperty('TimeAxisLabelFormat', 'date-time');
$barHistoryTile->SetTestProperty('EnableIPSView', true);
$barHistoryTile->ApplyChanges();
$barHistoryIPSView = $barHistoryTile->GetTestVariableValue('IPSViewBarHistory');
// Inspect the published page, because a fresh render could cover a later archive second.
assertGatewayGauge(
    is_string($barHistoryIPSView) && $barHistoryIPSView !== '',
    'Historical Bar must publish a nonempty IPSView WebContent document.'
);
assertGatewayGauge(
    str_contains($barHistoryIPSView, '"mode":"ipsview"')
        && str_contains($barHistoryIPSView, '"variant":"history"')
        && str_contains($barHistoryIPSView, '"timeAxisLabelFormat":"date-time"'),
    'Historical Bar IPSView must embed its chart and inherit the Tile time-axis format.'
);
assertGatewayGauge(
    str_contains($barHistoryIPSView, 'new WebSocket')
        && strlen($barHistoryIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Historical Bar IPSView must use the persistent transport within the output limit.'
);
assertGatewayGauge(
    preg_match('#/hook/SymconECharts/state/5000/([a-f0-9]{32})#', $barHistoryIPSView, $barTransportMatch) === 1,
    'Historical Bar IPSView must provide a targeted persistent state channel.'
);
$barStateRequest = ['DataID' => '{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    + EChartsDataProtocol::CreateRequest(EChartsDataProtocol::OPERATION_IPSVIEW_STATE, [
        'InstanceID' => 5000,
        'Channel'    => $barTransportMatch[1]
    ]);
$barStateResponse = EChartsDataProtocol::DecodeResponse(
    $barHistoryTile->ReceiveData(json_encode($barStateRequest, JSON_THROW_ON_ERROR)),
    EChartsDataProtocol::OPERATION_IPSVIEW_STATE
);
assertGatewayGauge(
    ($barStateResponse['Payload']['State']['status'] ?? null) === 'ready',
    'Historical Bar must return a fresh state through the shared IPSView transport.'
);
$barStateRequest['Payload']['Channel'] = str_repeat('0', 32);
assertGatewayGauge(
    $barHistoryTile->ReceiveData(json_encode($barStateRequest, JSON_THROW_ON_ERROR)) === '',
    'Historical Bar must reject requests from a different IPSView channel.'
);
$barIPSViewBeforeRefresh = $barHistoryTile->GetTestVariableValue('IPSViewBarHistory');
$barSocketUpdateCount = count($GLOBALS['symconTestWebSocketMessages']);
$barHistoryTile->RefreshArchive();
assertGatewayGauge(
    $barHistoryTile->GetTestVariableValue('IPSViewBarHistory') === $barIPSViewBeforeRefresh
        && count($GLOBALS['symconTestWebSocketMessages']) === $barSocketUpdateCount + 1,
    'Historical Bar archive refresh must update IPSView state without replacing its HTML document.'
);
$barHistoryForm = json_decode($barHistoryTile->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$barHistoryIPSViewPanels = array_values(array_filter(
    $barHistoryForm['elements'],
    static fn (array $element): bool => ($element['caption'] ?? '') === 'IPSView design'
));
$barHistoryTilePanels = array_values(array_filter(
    $barHistoryForm['elements'],
    static fn (array $element): bool => ($element['caption'] ?? '') === 'Tile designer'
));
assertGatewayGauge(
    count($barHistoryIPSViewPanels) === 1
        && findGaugeFormElement($barHistoryIPSViewPanels[0]['items'], 'IPSViewUseTileTimeSettings') === null
        && str_contains(json_encode($barHistoryForm['elements'], JSON_THROW_ON_ERROR), 'IPSViewUseTileTimeSettings')
        && str_contains(json_encode($barHistoryIPSViewPanels[0], JSON_THROW_ON_ERROR), 'IPSViewBarWidthPercent')
        && str_contains(json_encode($barHistoryIPSViewPanels[0], JSON_THROW_ON_ERROR), 'IPSViewBarFillMode')
        && str_contains(json_encode($barHistoryIPSViewPanels[0], JSON_THROW_ON_ERROR), 'IPSViewBarSVG')
        && str_contains(json_encode($barHistoryIPSViewPanels[0], JSON_THROW_ON_ERROR), 'IPSViewEnableZoom')
        && str_contains(json_encode($barHistoryIPSViewPanels[0], JSON_THROW_ON_ERROR), 'IPSViewAxisColor')
        && str_contains(json_encode($barHistoryIPSViewPanels[0], JSON_THROW_ON_ERROR), 'ECBH_UpdateBarDesignForm')
        && count($barHistoryTilePanels) === 1
        && str_contains(json_encode($barHistoryTilePanels[0], JSON_THROW_ON_ERROR), '"name":"BarFillMode"')
        && str_contains(json_encode($barHistoryTilePanels[0], JSON_THROW_ON_ERROR), '"name":"BarSVG"')
        && str_contains(json_encode($barHistoryTilePanels[0], JSON_THROW_ON_ERROR), '"name":"EnableZoom"')
        && str_contains(json_encode($barHistoryTilePanels[0], JSON_THROW_ON_ERROR), '"name":"AxisFontSizePercent"'),
    'Historical Bar form must separate IPSView time settings from design controls.'
);
$barHistoryTile->UpdateBarDesignForm(false, true, 'gradient');
assertGatewayGauge(
    array_slice($barHistoryTile->GetTestFormUpdates(), -4) === [
        ['Field' => 'BarGradientColor', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'BarSVG', 'Parameter' => 'visible', 'Value' => false],
        ['Field' => 'BarSVGSizePercent', 'Parameter' => 'visible', 'Value' => false],
        ['Field' => 'BarCornerRadius', 'Parameter' => 'visible', 'Value' => true]
    ],
    'Historical Bar must show dependent fill and corner controls immediately.'
);
$barHistoryRangeRows = array_values(array_filter(
    $barHistoryForm['elements'],
    static fn (array $element): bool => ($element['type'] ?? '') === 'RowLayout'
));
$barHistoryRangeFields = array_column($barHistoryRangeRows[0]['items'], null, 'name');
assertGatewayGauge(
    ($barHistoryRangeFields['CustomRangeValue']['visible'] ?? null) === false
        && ($barHistoryRangeFields['CustomRangeUnit']['visible'] ?? null) === false
        && str_contains((string) ($barHistoryRangeFields['Range']['onChange'] ?? ''), 'ECBH_UpdateTimeRangeForm'),
    'Historical Bar configuration form must hide custom range controls for a fixed range.'
);
$barHistoryTile->SetTestProperty('Range', 'custom');
$barHistoryForm = json_decode($barHistoryTile->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$barHistoryRangeRows = array_values(array_filter(
    $barHistoryForm['elements'],
    static fn (array $element): bool => ($element['type'] ?? '') === 'RowLayout'
));
$barHistoryRangeFields = array_column($barHistoryRangeRows[0]['items'], null, 'name');
assertGatewayGauge(
    ($barHistoryRangeFields['CustomRangeValue']['visible'] ?? null) === true
        && ($barHistoryRangeFields['CustomRangeUnit']['visible'] ?? null) === true,
    'Historical Bar configuration form must reveal custom range controls for a custom range.'
);
$barHistoryTile->UpdateTimeRangeForm('custom');
assertGatewayGauge(
    array_slice($barHistoryTile->GetTestFormUpdates(), -2) === [
        ['Field' => 'CustomRangeValue', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'CustomRangeUnit', 'Parameter' => 'visible', 'Value' => true]
    ],
    'Historical Bar must reveal both custom range controls as soon as the range changes.'
);
$barHistoryTile->SetTestProperty('Range', '1h');
$barHistoryTileHTML = $barHistoryTile->GetVisualizationTile();
assertGatewayGauge(
    str_contains($barHistoryTileHTML, 'echarts-bar-history-root')
        && str_contains($barHistoryTileHTML, 'history')
        && strlen($barHistoryTileHTML) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Historical Bar must render its ready model into the native visualization document.'
);
$barHistoryTile->SetTestProperty('IPSViewUseTileTimeSettings', false);
$barHistoryTile->SetTestProperty('IPSViewRange', 'yesterday');
$barHistoryTile->SetTestProperty('IPSViewTimeAxisLabelFormat', 'date');
$barHistoryTile->SetTestProperty('IPSViewUseTileDesign', false);
$barHistoryTile->SetTestProperty('IPSViewEnableZoom', false);
$barHistoryTile->SetTestProperty('IPSViewEChartsTheme', 'dark');
$barHistoryTile->SetTestProperty('IPSViewBarWidthPercent', 45);
$barHistoryTile->SetTestProperty('IPSViewBarFillMode', 'gradient');
$barHistoryTile->SetTestProperty('IPSViewBarGradientColor', 0x55CCAA);
$barHistoryTile->SetTestProperty('IPSViewAxisColor', 0xAAEECC);
$barHistoryTile->SetTestProperty('IPSViewAdaptToBackground', true);
$barHistoryTile->SetTestProperty('IPSViewBackgroundColor', 0x997755);
$barHistoryTile->SetTestProperty('IPSViewBackgroundOpacityPercent', 50);
$barHistoryTile->ApplyChanges();
$independentBarHistoryTile = json_decode($barHistoryTile->GetBarHistoryData(), true, 512, JSON_THROW_ON_ERROR);
$independentBarHistoryIPSView = $barHistoryTile->GetIPSViewHTML();
assertGatewayGauge(
    $independentBarHistoryTile['range']['key'] === '1h'
        && $independentBarHistoryTile['theme'] === 'auto'
        && $independentBarHistoryTile['bar']['style']['enableZoom'] === true
        && str_contains($independentBarHistoryIPSView, '"key":"yesterday"')
        && str_contains($independentBarHistoryIPSView, '"timeAxisLabelFormat":"date"')
        && str_contains($independentBarHistoryIPSView, '"theme":"dark"')
        && str_contains($independentBarHistoryIPSView, '"barWidthPercent":45')
        && str_contains($independentBarHistoryIPSView, '"barFillMode":"gradient"')
        && str_contains($independentBarHistoryIPSView, '"barGradientColor":"#55CCAA"')
        && str_contains($independentBarHistoryIPSView, '"axisColor":"#AAEECC"')
        && str_contains($independentBarHistoryIPSView, '"enableZoom":false')
        && str_contains($independentBarHistoryIPSView, '"adaptToBackground":true'),
    'Historical Bar IPSView settings must not change the native Tile range or appearance.'
);
$barHistoryTile->SetTestProperty('IPSViewBarFillMode', 'svg');
$barHistoryTile->SetTestProperty('IPSViewBarSVG', $otherBarSVG);
$barHistoryTile->SetTestProperty('IPSViewBarSVGSizePercent', 125);
$barHistoryTile->ApplyChanges();
$independentBarHistoryIPSView = $barHistoryTile->GetIPSViewHTML();
$historyIPSViewSVGForm = json_decode($barHistoryTile->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $barHistoryTile->GetTestStatus() === IS_ACTIVE
        && (findGaugeFormElement($historyIPSViewSVGForm['elements'], 'IPSViewBarSVG')['visible'] ?? null) === true
        && str_contains($independentBarHistoryIPSView, '"barFillMode":"svg"')
        && str_contains($independentBarHistoryIPSView, '"barPatternAspectRatio":1')
        && str_contains($independentBarHistoryIPSView, '"barSVGSizePercent":125')
        && !str_contains($barHistoryTile->GetBarHistoryData(), '"barPatternImage"'),
    'Independent Historical Bar IPSView patterns must not affect the Tile.'
);
$barHistoryTile->UpdateIPSViewTimeRangeForm(false, 'custom');
assertGatewayGauge(
    array_slice($barHistoryTile->GetTestFormUpdates(), -4) === [
        ['Field' => 'IPSViewRange', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'IPSViewTimeAxisLabelFormat', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'IPSViewCustomRangeValue', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'IPSViewCustomRangeUnit', 'Parameter' => 'visible', 'Value' => true]
    ],
    'Historical Bar form must reveal the independent custom IPSView range immediately.'
);

$rawBarHistory = new TestableEChartsBarHistory();
$rawBarHistory->Create();
$rawBarHistory->SetTestCurrentTimestamp(1780000400);
$rawBarHistory->SetTestProperty('Sources', json_encode([[
    'VariableID'              => 4711,
    'Label'                   => '',
    'UseVariablePresentation' => false,
    'Unit'                    => '°C',
    'Decimals'                => 1,
    'Reducer'                 => 'auto',
    'Color'                   => -1
]], JSON_THROW_ON_ERROR));
$rawBarHistory->SetTestProperty('Range', '1h');
$rawBarHistory->SetTestProperty('DataMode', 'raw');
$rawBarHistory->SetTestProperty('PointBudget', 200);
$rawBarHistory->ApplyChanges();
$rawBarHistoryData = json_decode($rawBarHistory->GetBarHistoryData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $rawBarHistoryData['range']['aggregationLevel'] === null
        && $rawBarHistoryData['range']['dataMode'] === 'raw'
        && $rawBarHistoryData['series'][0]['effectiveReducer'] === 'raw'
        && $rawBarHistoryData['series'][0]['points'] === [
            [1780000100, 41.0], [1780000200, 42.0], [1780000300, 43.0]
        ],
    'Historical Bar raw mode must remain raw and preserve normalized archive points.'
);

$GLOBALS['symconTestArchiveVariables'][4714] = [
    'AggregationType'  => 0,
    'LoggedValues'     => [['TimeStamp' => 1780000100, 'Value' => 1013.25, 'Duration' => 100]],
    'AggregatedValues' => []
];
$multiBarHistory = new TestableEChartsBarHistory();
$multiBarHistory->Create();
$multiBarHistory->SetTestCurrentTimestamp(1780000400);
$multiBarSources = [
    [
        'VariableID'              => 4711, 'Label' => 'Climate', 'Unit' => '°C',
        'UseVariablePresentation' => false, 'Decimals' => 1, 'AxisPosition' => 'left'
    ],
    [
        'VariableID'              => 4713, 'Label' => 'Climate', 'Unit' => '%',
        'UseVariablePresentation' => false, 'Decimals' => 0, 'AxisPosition' => 'right'
    ],
    [
        'VariableID'              => 4714, 'Label' => '', 'Unit' => 'hPa',
        'UseVariablePresentation' => false, 'Decimals' => 2
    ]
];
$multiBarHistory->SetTestProperty('Sources', json_encode($multiBarSources, JSON_THROW_ON_ERROR));
$multiBarHistory->SetTestProperty('Range', '1h');
$multiBarHistory->SetTestProperty('PointBudget', 1000);
$multiBarHistory->ApplyChanges();
$multiBarData = json_decode($multiBarHistory->GetBarHistoryData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $multiBarHistory->GetTestStatus() === IS_ACTIVE
        && $multiBarHistory->GetTestReferences() === [4711, 4713, 4714]
        && array_column($multiBarData['series'], 'variableID') === [4711, 4713, 4714]
        && array_column($multiBarData['series'], 'label') === ['Climate', 'Climate', 'Air pressure']
        && array_column($multiBarData['series'], 'axisIndex') === [0, 1, 2]
        && array_column($multiBarData['axes'], 'position') === ['left', 'right', 'left']
        && $multiBarData['range']['pointLimitPerSeries'] === 333,
    'Historical Bar must keep source IDs internal, group axes by unit and divide the point budget.'
);
$multiBarSources[] = $multiBarSources[0];
$multiBarHistory->SetTestProperty('Sources', json_encode($multiBarSources, JSON_THROW_ON_ERROR));
$multiBarHistory->ApplyChanges();
assertGatewayGauge(
    $multiBarHistory->GetTestStatus() === 201,
    'Historical Bar must reject duplicate variable IDs even when labels match.'
);
$multiBarSources = array_slice($multiBarSources, 0, 3);
$multiBarSources[2]['Unit'] = '°C';
$multiBarSources[2]['AxisPosition'] = 'right';
$multiBarHistory->SetTestProperty('Sources', json_encode($multiBarSources, JSON_THROW_ON_ERROR));
$multiBarHistory->ApplyChanges();
assertGatewayGauge(
    $multiBarHistory->GetTestStatus() === 201,
    'Historical Bar must reject conflicting axis sides for the same unit.'
);
unset($GLOBALS['symconTestArchiveVariables'][4714]);

$limitBarHistory = new EChartsBarHistory();
$limitBarHistory->Create();
$limitBarSources = [];
for ($index = 0; $index < 16; ++$index) {
    $variableID = 5100 + $index;
    $GLOBALS['symconTestVariables'][$variableID] = [
        'VariableType' => 2, 'VariableUpdated' => 1780000200,
        'Value'        => 20.0, 'Name' => 'Source ' . $index
    ];
    $limitBarSources[] = [
        'VariableID' => $variableID, 'Label' => '', 'UseVariablePresentation' => false,
        'Unit'       => '°C', 'Decimals' => 1
    ];
}
$limitBarHistory->SetTestProperty('Sources', json_encode($limitBarSources, JSON_THROW_ON_ERROR));
$limitBarHistory->ApplyChanges();
assertGatewayGauge(
    json_decode($limitBarHistory->GetBarHistoryDiagnostic(), true, 512, JSON_THROW_ON_ERROR)['valid'] === true
        && count($limitBarHistory->GetTestReferences()) === 16,
    'Historical Bar must continue accepting legacy 16-source lists.'
);
// 32/33-source stress coverage runs in the isolated --bar-history-many integration.
for ($variableID = 5100; $variableID < 5116; ++$variableID) {
    unset($GLOBALS['symconTestVariables'][$variableID]);
}

$invalidBarHistory = new EChartsBarHistory();
$invalidBarHistory->Create();
$invalidBarHistory->SetTestProperty('Sources', '[]');
$invalidBarHistory->ApplyChanges();
assertGatewayGauge(
    $invalidBarHistory->GetTestStatus() === 201,
    'Historical Bar must reject configurations without a numeric source.'
);

$invalidBarDesign = new EChartsBarHistory();
$invalidBarDesign->Create();
$invalidBarDesign->SetTestProperty('Sources', json_encode([[
    'VariableID' => 4711, 'UseVariablePresentation' => false, 'Unit' => '°C',
    'Decimals'   => 1, 'Reducer' => 'auto', 'Color' => -1
]], JSON_THROW_ON_ERROR));
$invalidBarDesign->SetTestProperty('BarOpacityPercent', 101);
$invalidBarDesign->ApplyChanges();
assertGatewayGauge(
    $invalidBarDesign->GetTestStatus() === 202,
    'Historical Bar must reject design values outside their supported range.'
);

// Historical Bar assertions above are complete. Retain only the tile used by
// the later cross-family opacity checks, not old inline HTML/form snapshots.
unset(
    $barHistory, $rawBarHistory, $multiBarHistory, $limitBarHistory, $invalidBarHistory,
    $invalidBarDesign, $historySVGFormInstance, $barHistoryForm, $historySVGForm,
    $barHistoryIPSView, $barHistoryTileHTML, $barIPSViewBeforeRefresh, $independentBarHistoryIPSView
);
gc_collect_cycles();

$waterfall = new EChartsBarWaterfall();
$waterfall->Create();
$waterfall->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Label' => 'Start', 'UseVariablePresentation' => false, 'Unit' => '°C'],
    ['VariableID' => 4717, 'Label' => 'Loss', 'UseVariablePresentation' => false, 'Unit' => '°C'],
    ['VariableID' => 4716, 'Label' => '', 'UseVariablePresentation' => false, 'Unit' => '°C']
], JSON_THROW_ON_ERROR));
$waterfall->SetTestProperty('Title', 'Balance');
$waterfall->SetTestProperty('EnableIPSView', true);
$waterfall->SetTestProperty('IPSViewUseTileDesign', false);
$waterfall->SetTestProperty('IPSViewEChartsTheme', 'dark');
$waterfall->SetTestProperty('IPSViewIncreaseColor', 0x123456);
$originalWaterfallValue = $GLOBALS['symconTestVariables'][4717]['Value'];
$GLOBALS['symconTestVariables'][4717]['Value'] = -50.0;
$waterfall->ApplyChanges();
assertTransientRetry($waterfall, 'Waterfall Bar');
$waterfallData = json_decode($waterfall->GetWaterfallData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $waterfall->GetTestStatus() === IS_ACTIVE
        && $waterfall->GetTestReferences() === [4711, 4716, 4717]
        && $waterfallData['variant'] === 'waterfall'
        && $waterfallData['bar']['unit'] === '°C'
        && $waterfallData['steps'][0]['after'] === 42.5
        && $waterfallData['steps'][1]['before'] === 42.5
        && $waterfallData['steps'][1]['after'] === -7.5
        && $waterfallData['steps'][2]['label'] === 'Legacy wind speed'
        && $waterfallData['steps'][3]['after'] === 4.84,
    'Waterfall must calculate ordered signed changes by source ID, including a zero crossing.'
);
$waterfallTile = $waterfall->GetVisualizationTile();
$waterfallIPSView = $waterfall->GetIPSViewHTML();
assertGatewayGauge(
    str_contains($waterfallTile, 'echarts-bar-waterfall-root')
        && str_contains($waterfallTile, '"theme":"auto"')
        && str_contains($waterfallTile, 'function resizeChartIfNeeded(')
        && strlen($waterfallTile) < SYMCON_OUTPUT_BUFFER_LIMIT
        && str_contains($waterfallIPSView, '"theme":"dark"')
        && str_contains($waterfallIPSView, 'function resizeChartIfNeeded(')
        && str_contains($waterfallIPSView, '#123456')
        && $waterfall->GetTestVariableValue('IPSViewBarWaterfall') !== null,
    'Waterfall must provide separate Tile and persistent IPSView output.'
);
$waterfallForm = json_decode($waterfall->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$waterfallSourceForm = current(array_filter(
    $waterfallForm['elements'],
    static fn (array $element): bool => ($element['name'] ?? '') === 'Sources'
));
assertGatewayGauge(
    ($waterfallSourceForm['changeOrder'] ?? false) === true
        && in_array('Label', array_column($waterfallSourceForm['columns'], 'name'), true),
    'Waterfall form must expose ordered sources and editable step labels.'
);
$waterfall->MessageSink(0, 4717, VM_UPDATE, []);
$waterfallUpdates = $waterfall->GetTestVisualizationUpdates();
$lastWaterfallUpdate = end($waterfallUpdates);
assertGatewayGauge(
    is_string($lastWaterfallUpdate) && str_contains($lastWaterfallUpdate, '"variant":"waterfall"'),
    'Waterfall must update its chart state when a source variable changes.'
);
$GLOBALS['symconTestVariables'][4717]['Value'] = $originalWaterfallValue;

$invalidWaterfall = new EChartsBarWaterfall();
$invalidWaterfall->Create();
$invalidWaterfall->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Unit' => '°C', 'UseVariablePresentation' => false],
    ['VariableID' => 4713, 'Unit' => '%', 'UseVariablePresentation' => false]
], JSON_THROW_ON_ERROR));
$invalidWaterfall->ApplyChanges();
assertGatewayGauge($invalidWaterfall->GetTestStatus() === 201, 'Waterfall must reject mixed units.');

$duplicateWaterfall = new EChartsBarWaterfall();
$duplicateWaterfall->Create();
$duplicateWaterfall->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711],
    ['VariableID' => 4711]
], JSON_THROW_ON_ERROR));
$duplicateWaterfall->ApplyChanges();
assertGatewayGauge($duplicateWaterfall->GetTestStatus() === 201, 'Waterfall must reject duplicate variable IDs.');

$sameNameWaterfall = new EChartsBarWaterfall();
$sameNameWaterfall->Create();
$sameNameWaterfall->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Label' => 'Configured start', 'UseVariablePresentation' => false],
    ['VariableID' => 4717, 'UseVariablePresentation' => false]
], JSON_THROW_ON_ERROR));
$originalWaterfallName = $GLOBALS['symconTestVariables'][4717]['Name'];
$GLOBALS['symconTestVariables'][4717]['Name'] = $GLOBALS['symconTestVariables'][4711]['Name'];
$sameNameWaterfall->ApplyChanges();
$sameNameWaterfallData = json_decode($sameNameWaterfall->GetWaterfallData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $sameNameWaterfallData['steps'][0]['label'] === 'Configured start'
        && $sameNameWaterfallData['steps'][1]['label'] === 'Living room temperature'
        && $sameNameWaterfallData['steps'][0]['id'] !== $sameNameWaterfallData['steps'][1]['id'],
    'Waterfall must keep IDs as source identity while showing configured or current names.'
);
$GLOBALS['symconTestVariables'][4717]['Name'] = $originalWaterfallName;
$sameNameWaterfall->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'UseVariablePresentation' => false],
    ['VariableID' => 4716, 'UseVariablePresentation' => false]
], JSON_THROW_ON_ERROR));
$sameNameWaterfall->ApplyChanges();
$sameNameWaterfall->ApplyChanges();
assertGatewayGauge(
    $sameNameWaterfall->GetTestReferences() === [4711, 4716],
    'Waterfall must remove stale references and keep repeated ApplyChanges idempotent.'
);

$polar = new EChartsBarPolar();
$polar->Create();
$polar->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Label' => 'Living room', 'UseVariablePresentation' => false, 'Unit' => '°C', 'Decimals' => 2, 'Color' => 0x123456],
    ['VariableID' => 4717, 'Label' => 'Living room', 'UseVariablePresentation' => false, 'Unit' => '°C', 'Decimals' => 0, 'Color' => -1],
    ['VariableID' => 4716, 'Label' => '', 'UseVariablePresentation' => false, 'Unit' => '°C', 'Color' => -1]
], JSON_THROW_ON_ERROR));
$polar->SetTestProperty('Title', 'Current temperatures');
$polar->SetTestProperty('EnableIPSView', true);
$polar->SetTestProperty('IPSViewUseTileDesign', false);
$polar->SetTestProperty('IPSViewPolarMode', 'tangential');
$polar->SetTestProperty('IPSViewEChartsTheme', 'dark');
$polar->ApplyChanges();
assertTransientRetry($polar, 'Polar Bar');
$polarData = json_decode($polar->GetPolarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $polar->GetTestStatus() === IS_ACTIVE
        && $polar->GetTestReferences() === [4711, 4716, 4717]
        && $polarData['variant'] === 'polar'
        && $polarData['polar']['unit'] === '°C'
        && $polarData['polar']['style']['mode'] === 'radial'
        && $polarData['polar']['style']['valueAxisRangeMode'] === 'auto'
        && $polarData['polar']['style']['showBarBackground'] === false
        && $polarData['polar']['style']['barFillMode'] === 'solid'
        && $polarData['polar']['style']['barGradientColor'] === ''
        && $polarData['polar']['style']['barOpacityPercent'] === 100
        && $polarData['polar']['style']['barOutlineWidth'] === 0
        && $polarData['polar']['style']['barOutlineColor'] === ''
        && $polarData['polar']['style']['barShadowBlur'] === 0
        && $polarData['polar']['style']['barShadowColor'] === ''
        && $polarData['polar']['style']['barShadowOpacityPercent'] === 35
        && $polarData['polar']['style']['animationEnabled'] === true
        && $polarData['polar']['style']['animationDuration'] === 350
        && $polarData['polar']['style']['animationDurationUpdate'] === 500
        && $polarData['polar']['style']['barHighlightMode'] === 'standard'
        && $polarData['polar']['style']['barHighlightColor'] === ''
        && $polarData['polar']['style']['valueLabelPosition'] === 'middle'
        && $polarData['polar']['style']['categoryLabelFontSize'] === 10
        && $polarData['polar']['style']['valueLabelFontSize'] === 10
        && $polarData['polar']['style']['scaleLabelFontSize'] === 10
        && $polarData['polar']['style']['angularSpan'] === 360
        && array_column($polarData['items'], 'label') === ['Living room', 'Living room', 'Legacy wind speed']
        && $polarData['items'][0]['color'] === '#123456'
        && array_column($polarData['items'], 'decimals') === [2, 0, 1]
        && $polarData['items'][0]['value'] === (float) $GLOBALS['symconTestVariables'][4711]['Value']
        && $polarData['items'][0]['id'] !== $polarData['items'][1]['id'],
    'Polar Bar must keep current values separate by variable ID, even when labels match.'
);
$polarTile = $polar->GetVisualizationTile();
$polarIPSView = $polar->GetIPSViewHTML();
foreach ([$gauge, $multiGauge, $timeSeries, $barHistoryTile, $polar] as $opacityChart) {
    assertGatewayGauge(
        str_contains($opacityChart->GetVisualizationTile(), 'function opacityFromPercent(')
            && str_contains($opacityChart->GetIPSViewHTML(), 'function opacityFromPercent(')
            && strpos($opacityChart->GetVisualizationTile(), 'function opacityFromPercent(')
                < strpos($opacityChart->GetVisualizationTile(), 'window.SYMC_ECHARTS_DESIGN'),
        $opacityChart::class . ' must embed shared design utilities in Tile and IPSView.'
    );
}
assertGatewayGauge(
    str_contains($polarTile, 'echarts-bar-polar-root')
        && str_contains($polarTile, 'SYMC_ECHARTS_DESIGN')
        && str_contains($polarTile, 'function barOutlineStyle(')
        && str_contains($polarTile, '"mode":"radial"')
        && str_contains($polarIPSView, 'SYMC_ECHARTS_DESIGN')
        && str_contains($polarIPSView, 'function barOutlineStyle(')
        && str_contains($polarIPSView, '"mode":"tangential"')
        && str_contains($polarIPSView, '"theme":"dark"')
        && $polar->GetTestVariableValue('IPSViewBarPolar') !== null,
    'Polar Bar must provide separate Tile and persistent IPSView output.'
);
$polarForm = json_decode($polar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$polarSourceForm = current(array_filter(
    $polarForm['elements'],
    static fn (array $element): bool => ($element['name'] ?? '') === 'Sources'
));
assertGatewayGauge(
    ($polarSourceForm['changeOrder'] ?? false) === true
        && in_array('Color', array_column($polarSourceForm['columns'], 'name'), true),
    'Polar Bar form must expose ordered sources and individual colors.'
);
$polarTileDesigner = current(array_filter(
    $polarForm['elements'],
    static fn (array $element): bool => ($element['caption'] ?? '') === 'Tile designer'
));
$polarTileDesigner['items'] = flattenDesignerSections($polarTileDesigner['items']);
$polarScaleRow = current(array_filter(
    $polarTileDesigner['items'],
    static fn (array $item): bool => in_array('ValueAxisRangeMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarScaleFields = array_column($polarScaleRow['items'], null, 'name');
$polarFillRow = current(array_filter(
    $polarTileDesigner['items'],
    static fn (array $item): bool => in_array('BarFillMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarFillFields = array_column($polarFillRow['items'], null, 'name');
$polarOutlineRow = current(array_filter(
    $polarTileDesigner['items'],
    static fn (array $item): bool => in_array('BarOutlineWidth', array_column($item['items'] ?? [], 'name'), true)
));
$polarOutlineFields = array_column($polarOutlineRow['items'], null, 'name');
$polarShadowRow = current(array_filter(
    $polarTileDesigner['items'],
    static fn (array $item): bool => in_array('BarShadowBlur', array_column($item['items'] ?? [], 'name'), true)
));
$polarShadowFields = array_column($polarShadowRow['items'], null, 'name');
$polarAnimationRow = current(array_filter(
    $polarTileDesigner['items'],
    static fn (array $item): bool => in_array('AnimationEnabled', array_column($item['items'] ?? [], 'name'), true)
));
$polarAnimationFields = array_column($polarAnimationRow['items'], null, 'name');
$polarHighlightRow = current(array_filter(
    $polarTileDesigner['items'],
    static fn (array $item): bool => in_array('BarHighlightMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarHighlightFields = array_column($polarHighlightRow['items'], null, 'name');
$polarLabelPositionField = current(array_filter(
    $polarTileDesigner['items'],
    static fn (array $item): bool => ($item['name'] ?? '') === 'ValueLabelPosition'
));
assertGatewayGauge(
    ($polarScaleFields['ValueAxisMinimum']['visible'] ?? null) === false
        && ($polarScaleFields['ValueAxisMaximum']['visible'] ?? null) === false
        && str_contains((string) ($polarScaleFields['ValueAxisRangeMode']['onChange'] ?? ''), 'ECBP_UpdateValueScaleForm'),
    'Polar Bar Tile form must hide fixed-scale limits until manual mode is selected.'
);
assertGatewayGauge(
    ($polarFillFields['BarGradientColor']['visible'] ?? null) === false
        && ($polarFillFields['BarOpacityPercent']['minimum'] ?? null) === 0
        && ($polarFillFields['BarOpacityPercent']['maximum'] ?? null) === 100
        && str_contains((string) ($polarFillFields['BarFillMode']['onChange'] ?? ''), 'ECBP_UpdateBarFillForm'),
    'Polar Tile form must hide the gradient color until gradient fill is selected.'
);
assertGatewayGauge(
    ($polarOutlineFields['BarOutlineWidth']['minimum'] ?? null) === 0
        && ($polarOutlineFields['BarOutlineWidth']['maximum'] ?? null) === 8
        && ($polarOutlineFields['BarOutlineColor']['transparentCaption'] ?? null) === 'Automatic (theme)',
    'Polar Tile form must offer an optional theme-aware bar outline.'
);
assertGatewayGauge(
    ($polarShadowFields['BarShadowBlur']['minimum'] ?? null) === 0
        && ($polarShadowFields['BarShadowBlur']['maximum'] ?? null) === 20
        && ($polarShadowFields['BarShadowColor']['transparentCaption'] ?? null) === 'Automatic (theme)'
        && ($polarShadowFields['BarShadowOpacityPercent']['minimum'] ?? null) === 0
        && ($polarShadowFields['BarShadowOpacityPercent']['maximum'] ?? null) === 100,
    'Polar Tile form must offer a theme-aware optional bar shadow.'
);
assertGatewayGauge(
    ($polarAnimationFields['AnimationDuration']['minimum'] ?? null) === 0
        && ($polarAnimationFields['AnimationDuration']['maximum'] ?? null) === 3000
        && ($polarAnimationFields['AnimationDurationUpdate']['minimum'] ?? null) === 0
        && ($polarAnimationFields['AnimationDurationUpdate']['maximum'] ?? null) === 3000
        && str_contains((string) ($polarAnimationFields['AnimationEnabled']['onChange'] ?? ''), 'ECBP_UpdateAnimationForm'),
    'Polar Tile form must configure entry and update animation independently.'
);
assertGatewayGauge(
    array_column($polarHighlightFields['BarHighlightMode']['options'], 'value')
        === ['standard', 'outline', 'focus', 'off']
        && ($polarHighlightFields['BarHighlightColor']['visible'] ?? null) === false,
    'Polar Tile form must offer highlight modes without showing an unused color by default.'
);
assertGatewayGauge(
    ($polarLabelPositionField['type'] ?? '') === 'Select'
        && array_column($polarLabelPositionField['options'], 'value')
            === ['middle', 'insideStart', 'insideEnd', 'outside', 'start', 'end'],
    'Polar Tile designer must offer all supported value-label positions.'
);
$polarDefaultIPSViewPanel = current(array_filter(
    $polarForm['elements'],
    static fn (array $element): bool => ($element['caption'] ?? '') === 'IPSView design'
));
$polarDefaultIPSViewPanel['items'] = flattenDesignerSections($polarDefaultIPSViewPanel['items']);
$polarDefaultIPSViewScaleRow = current(array_filter(
    $polarDefaultIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewValueAxisRangeMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarDefaultIPSViewScaleFields = array_column($polarDefaultIPSViewScaleRow['items'], null, 'name');
$polarDefaultIPSViewFillRow = current(array_filter(
    $polarDefaultIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewBarFillMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarDefaultIPSViewFillFields = array_column($polarDefaultIPSViewFillRow['items'], null, 'name');
$polarDefaultIPSViewOutlineRow = current(array_filter(
    $polarDefaultIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewBarOutlineWidth', array_column($item['items'] ?? [], 'name'), true)
));
$polarDefaultIPSViewOutlineFields = array_column($polarDefaultIPSViewOutlineRow['items'], null, 'name');
$polarDefaultIPSViewShadowRow = current(array_filter(
    $polarDefaultIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewBarShadowBlur', array_column($item['items'] ?? [], 'name'), true)
));
$polarDefaultIPSViewShadowFields = array_column($polarDefaultIPSViewShadowRow['items'], null, 'name');
$polarDefaultIPSViewAnimationRow = current(array_filter(
    $polarDefaultIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewAnimationEnabled', array_column($item['items'] ?? [], 'name'), true)
));
$polarDefaultIPSViewAnimationFields = array_column($polarDefaultIPSViewAnimationRow['items'], null, 'name');
$polarDefaultIPSViewHighlightRow = current(array_filter(
    $polarDefaultIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewBarHighlightMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarDefaultIPSViewHighlightFields = array_column($polarDefaultIPSViewHighlightRow['items'], null, 'name');
assertGatewayGauge(
    ($polarDefaultIPSViewScaleFields['IPSViewValueAxisMinimum']['visible'] ?? null) === false
        && ($polarDefaultIPSViewScaleFields['IPSViewValueAxisMaximum']['visible'] ?? null) === false,
    'Polar IPSView form must hide fixed-scale limits in automatic mode.'
);
assertGatewayGauge(
    ($polarDefaultIPSViewFillFields['IPSViewBarGradientColor']['visible'] ?? null) === false
        && ($polarDefaultIPSViewFillFields['IPSViewBarOpacityPercent']['enabled'] ?? null) === true
        && ($polarDefaultIPSViewFillFields['IPSViewBarFillMode']['enabled'] ?? null) === true,
    'Independent Polar IPSView form must start with solid fill and editable fill mode.'
);
assertGatewayGauge(
    ($polarDefaultIPSViewOutlineFields['IPSViewBarOutlineWidth']['enabled'] ?? null) === true
        && ($polarDefaultIPSViewOutlineFields['IPSViewBarOutlineColor']['enabled'] ?? null) === true,
    'Independent Polar IPSView form must expose editable outline controls.'
);
assertGatewayGauge(
    ($polarDefaultIPSViewShadowFields['IPSViewBarShadowBlur']['enabled'] ?? null) === true
        && ($polarDefaultIPSViewShadowFields['IPSViewBarShadowColor']['enabled'] ?? null) === true
        && ($polarDefaultIPSViewShadowFields['IPSViewBarShadowOpacityPercent']['enabled'] ?? null) === true,
    'Independent Polar IPSView form must expose editable shadow controls.'
);
assertGatewayGauge(
    ($polarDefaultIPSViewAnimationFields['IPSViewAnimationEnabled']['enabled'] ?? null) === true
        && ($polarDefaultIPSViewAnimationFields['IPSViewAnimationDuration']['visible'] ?? null) !== false
        && ($polarDefaultIPSViewAnimationFields['IPSViewAnimationDurationUpdate']['visible'] ?? null) !== false,
    'Independent Polar IPSView form must expose its own animation controls.'
);
assertGatewayGauge(
    ($polarDefaultIPSViewHighlightFields['IPSViewBarHighlightMode']['enabled'] ?? null) === true
        && ($polarDefaultIPSViewHighlightFields['IPSViewBarHighlightColor']['visible'] ?? null) === false,
    'Independent IPSView must offer its own highlight mode.'
);
$polar->SetTestProperty('ValueAxisRangeMode', 'manual');
$polar->SetTestProperty('ValueAxisMinimum', -10.5);
$polar->SetTestProperty('ValueAxisMaximum', 30.25);
$polar->SetTestProperty('ShowBarBackground', true);
$polar->SetTestProperty('BarBackgroundColor', 0x778899);
$polar->SetTestProperty('BarBackgroundOpacityPercent', 40);
$polar->SetTestProperty('BarFillMode', 'gradient');
$polar->SetTestProperty('BarGradientColor', 0x55CCAA);
$polar->SetTestProperty('BarOpacityPercent', 65);
$polar->SetTestProperty('BarOutlineWidth', 2);
$polar->SetTestProperty('BarOutlineColor', 0x123456);
$polar->SetTestProperty('BarShadowBlur', 12);
$polar->SetTestProperty('BarShadowColor', 0x223344);
$polar->SetTestProperty('BarShadowOpacityPercent', 60);
$polar->SetTestProperty('AnimationDuration', 700);
$polar->SetTestProperty('AnimationDurationUpdate', 1200);
$polar->SetTestProperty('BarHighlightMode', 'focus');
$polar->SetTestProperty('BarHighlightColor', 0xCC00AA);
$polar->SetTestProperty('AngularSpan', 180);
$polar->SetTestProperty('ValueLabelPosition', 'insideEnd');
$polar->SetTestProperty('CategoryLabelFontSize', 9);
$polar->SetTestProperty('ValueLabelFontSize', 11);
$polar->SetTestProperty('ScaleLabelFontSize', 8);
$polar->ApplyChanges();
$polarDesignData = json_decode($polar->GetPolarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $polar->GetTestStatus() === IS_ACTIVE
        && $polarDesignData['polar']['style']['valueAxisRangeMode'] === 'manual'
        && $polarDesignData['polar']['style']['valueAxisMinimum'] === -10.5
        && $polarDesignData['polar']['style']['valueAxisMaximum'] === 30.25
        && $polarDesignData['polar']['style']['showBarBackground'] === true
        && $polarDesignData['polar']['style']['barBackgroundColor'] === '#778899'
        && $polarDesignData['polar']['style']['barBackgroundOpacityPercent'] === 40
        && $polarDesignData['polar']['style']['barFillMode'] === 'gradient'
        && $polarDesignData['polar']['style']['barGradientColor'] === '#55CCAA'
        && $polarDesignData['polar']['style']['barOpacityPercent'] === 65
        && $polarDesignData['polar']['style']['barOutlineWidth'] === 2
        && $polarDesignData['polar']['style']['barOutlineColor'] === '#123456'
        && $polarDesignData['polar']['style']['barShadowBlur'] === 12
        && $polarDesignData['polar']['style']['barShadowColor'] === '#223344'
        && $polarDesignData['polar']['style']['barShadowOpacityPercent'] === 60
        && $polarDesignData['polar']['style']['animationEnabled'] === true
        && $polarDesignData['polar']['style']['animationDuration'] === 700
        && $polarDesignData['polar']['style']['animationDurationUpdate'] === 1200
        && $polarDesignData['polar']['style']['barHighlightMode'] === 'focus'
        && $polarDesignData['polar']['style']['barHighlightColor'] === '#CC00AA'
        && $polarDesignData['polar']['style']['angularSpan'] === 180
        && $polarDesignData['polar']['style']['valueLabelPosition'] === 'insideEnd'
        && $polarDesignData['polar']['style']['categoryLabelFontSize'] === 9
        && $polarDesignData['polar']['style']['valueLabelFontSize'] === 11
        && $polarDesignData['polar']['style']['scaleLabelFontSize'] === 8
        && str_contains($polar->GetIPSViewHTML(), '"valueAxisRangeMode":"auto"')
        && str_contains($polar->GetIPSViewHTML(), '"valueLabelPosition":"middle"')
        && str_contains($polar->GetIPSViewHTML(), '"categoryLabelFontSize":10')
        && str_contains($polar->GetIPSViewHTML(), '"angularSpan":360')
        && str_contains($polar->GetIPSViewHTML(), '"showBarBackground":false'),
    'Polar Bar must expose a fixed value scale and configurable background tracks in Tile data.'
);
$polar->SetTestProperty('IPSViewValueAxisRangeMode', 'manual');
$polar->SetTestProperty('IPSViewValueAxisMinimum', 5.0);
$polar->SetTestProperty('IPSViewValueAxisMaximum', 100.0);
$polar->SetTestProperty('IPSViewShowBarBackground', true);
$polar->SetTestProperty('IPSViewBarBackgroundColor', 0x8B5A2B);
$polar->SetTestProperty('IPSViewBarBackgroundOpacityPercent', 35);
$polar->SetTestProperty('IPSViewBarFillMode', 'gradient');
$polar->SetTestProperty('IPSViewBarGradientColor', 0x223344);
$polar->SetTestProperty('IPSViewBarOpacityPercent', 35);
$polar->SetTestProperty('IPSViewBarOutlineWidth', 3);
$polar->SetTestProperty('IPSViewBarOutlineColor', -1);
$polar->SetTestProperty('IPSViewBarShadowBlur', 8);
$polar->SetTestProperty('IPSViewBarShadowColor', -1);
$polar->SetTestProperty('IPSViewBarShadowOpacityPercent', 35);
$polar->SetTestProperty('IPSViewAnimationEnabled', false);
$polar->SetTestProperty('IPSViewAnimationDuration', 150);
$polar->SetTestProperty('IPSViewAnimationDurationUpdate', 250);
$polar->SetTestProperty('IPSViewBarHighlightMode', 'outline');
$polar->SetTestProperty('IPSViewBarHighlightColor', -1);
$polar->SetTestProperty('IPSViewAngularSpan', 270);
$polar->SetTestProperty('IPSViewValueLabelPosition', 'insideStart');
$polar->SetTestProperty('IPSViewCategoryLabelFontSize', 12);
$polar->SetTestProperty('IPSViewValueLabelFontSize', 13);
$polar->SetTestProperty('IPSViewScaleLabelFontSize', 14);
$polar->ApplyChanges();
$polarIPSViewDesign = $polar->GetIPSViewHTML();
$polarCurrentTileDesign = json_decode($polar->GetPolarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $polar->GetTestStatus() === IS_ACTIVE
        && str_contains($polarIPSViewDesign, '"valueAxisRangeMode":"manual"')
        && str_contains($polarIPSViewDesign, '"valueAxisMinimum":5.0')
        && str_contains($polarIPSViewDesign, '"valueAxisMaximum":100.0')
        && str_contains($polarIPSViewDesign, '"showBarBackground":true')
        && str_contains($polarIPSViewDesign, '"barBackgroundColor":"#8B5A2B"')
        && str_contains($polarIPSViewDesign, '"barBackgroundOpacityPercent":35')
        && str_contains($polarIPSViewDesign, '"barFillMode":"gradient"')
        && str_contains($polarIPSViewDesign, '"barGradientColor":"#223344"')
        && str_contains($polarIPSViewDesign, '"barOpacityPercent":35')
        && str_contains($polarIPSViewDesign, '"barOutlineWidth":3')
        && str_contains($polarIPSViewDesign, '"barOutlineColor":""')
        && str_contains($polarIPSViewDesign, '"barShadowBlur":8')
        && str_contains($polarIPSViewDesign, '"barShadowColor":""')
        && str_contains($polarIPSViewDesign, '"barShadowOpacityPercent":35')
        && str_contains($polarIPSViewDesign, '"animationEnabled":false')
        && str_contains($polarIPSViewDesign, '"animationDuration":150')
        && str_contains($polarIPSViewDesign, '"animationDurationUpdate":250')
        && str_contains($polarIPSViewDesign, '"barHighlightMode":"outline"')
        && str_contains($polarIPSViewDesign, '"barHighlightColor":""')
        && str_contains($polarIPSViewDesign, '"angularSpan":270')
        && str_contains($polarIPSViewDesign, '"valueLabelPosition":"insideStart"')
        && str_contains($polarIPSViewDesign, '"categoryLabelFontSize":12')
        && str_contains($polarIPSViewDesign, '"valueLabelFontSize":13')
        && str_contains($polarIPSViewDesign, '"scaleLabelFontSize":14')
        && $polarCurrentTileDesign['polar']['style']['valueAxisMinimum'] === -10.5
        && $polarCurrentTileDesign['polar']['style']['valueLabelPosition'] === 'insideEnd'
        && $polarCurrentTileDesign['polar']['style']['angularSpan'] === 180
        && $polarCurrentTileDesign['polar']['style']['barBackgroundColor'] === '#778899',
    'Polar Bar must keep independent IPSView scale and tracks separate from the Tile design.'
);
$polarIPSViewForm = json_decode($polar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$polarIPSViewPanel = current(array_filter(
    $polarIPSViewForm['elements'],
    static fn (array $element): bool => ($element['caption'] ?? '') === 'IPSView design'
));
$polarIPSViewPanel['items'] = flattenDesignerSections($polarIPSViewPanel['items']);
$polarIPSViewScaleRow = current(array_filter(
    $polarIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewValueAxisRangeMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarIPSViewScaleFields = array_column($polarIPSViewScaleRow['items'], null, 'name');
$polarIPSViewFillRow = current(array_filter(
    $polarIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewBarFillMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarIPSViewFillFields = array_column($polarIPSViewFillRow['items'], null, 'name');
$polarIPSViewAnimationRow = current(array_filter(
    $polarIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewAnimationEnabled', array_column($item['items'] ?? [], 'name'), true)
));
$polarIPSViewAnimationFields = array_column($polarIPSViewAnimationRow['items'], null, 'name');
$polarIPSViewHighlightRow = current(array_filter(
    $polarIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewBarHighlightMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarIPSViewHighlightFields = array_column($polarIPSViewHighlightRow['items'], null, 'name');
assertGatewayGauge(
    ($polarIPSViewAnimationFields['IPSViewAnimationEnabled']['enabled'] ?? null) === true
        && ($polarIPSViewAnimationFields['IPSViewAnimationDuration']['visible'] ?? null) === false
        && ($polarIPSViewAnimationFields['IPSViewAnimationDurationUpdate']['visible'] ?? null) === false,
    'Disabled independent IPSView animation must hide both timing controls.'
);
$polarIPSViewLabelPositionField = current(array_filter(
    $polarIPSViewPanel['items'],
    static fn (array $item): bool => ($item['name'] ?? '') === 'IPSViewValueLabelPosition'
));
assertGatewayGauge(
    ($polarIPSViewScaleFields['IPSViewValueAxisMinimum']['visible'] ?? null) === true
        && ($polarIPSViewScaleFields['IPSViewValueAxisMaximum']['visible'] ?? null) === true
        && ($polarIPSViewScaleFields['IPSViewValueAxisMinimum']['enabled'] ?? null) === true
        && str_contains(
            (string) ($polarIPSViewScaleFields['IPSViewValueAxisRangeMode']['onChange'] ?? ''),
            'ECBP_UpdateIPSViewValueScaleForm'
        )
        && str_contains(json_encode($polarIPSViewPanel, JSON_THROW_ON_ERROR), 'IPSViewShowBarBackground')
        && str_contains(json_encode($polarIPSViewPanel, JSON_THROW_ON_ERROR), 'IPSViewBarBackgroundColor')
        && str_contains(json_encode($polarIPSViewPanel, JSON_THROW_ON_ERROR), 'IPSViewBarBackgroundOpacityPercent')
        && str_contains(json_encode($polarIPSViewPanel, JSON_THROW_ON_ERROR), 'IPSViewAngularSpan')
        && str_contains(json_encode($polarIPSViewPanel, JSON_THROW_ON_ERROR), 'IPSViewValueLabelPosition')
        && str_contains(json_encode($polarIPSViewPanel, JSON_THROW_ON_ERROR), 'IPSViewCategoryLabelFontSize')
        && str_contains(json_encode($polarIPSViewPanel, JSON_THROW_ON_ERROR), 'IPSViewValueLabelFontSize')
        && str_contains(json_encode($polarIPSViewPanel, JSON_THROW_ON_ERROR), 'IPSViewScaleLabelFontSize'),
    'Independent Polar IPSView design must expose an enabled fixed scale and background-track controls.'
);
assertGatewayGauge(
    ($polarIPSViewFillFields['IPSViewBarGradientColor']['visible'] ?? null) === true
        && ($polarIPSViewFillFields['IPSViewBarGradientColor']['enabled'] ?? null) === true,
    'Independent Polar IPSView designer must reveal an editable gradient color.'
);
assertGatewayGauge(
    ($polarIPSViewHighlightFields['IPSViewBarHighlightColor']['visible'] ?? null) === true
        && ($polarIPSViewHighlightFields['IPSViewBarHighlightColor']['enabled'] ?? null) === true
        && str_contains(
            (string) ($polarIPSViewHighlightFields['IPSViewBarHighlightMode']['onChange'] ?? ''),
            'ECBP_UpdateBarHighlightForm'
        ),
    'Independent Polar IPSView designer must reveal and enable its highlight color.'
);
$polar->UpdateBarHighlightForm(true, 'standard');
assertGatewayGauge(
    array_slice($polar->GetTestFormUpdates(), -1) === [
        ['Field' => 'IPSViewBarHighlightColor', 'Parameter' => 'visible', 'Value' => false]
    ],
    'The IPSView highlight color must disappear immediately in standard mode.'
);
$polar->UpdateBarFillForm(true, 'solid');
assertGatewayGauge(
    array_slice($polar->GetTestFormUpdates(), -1) === [
        ['Field' => 'IPSViewBarGradientColor', 'Parameter' => 'visible', 'Value' => false]
    ],
    'Independent Polar IPSView designer must react immediately to fill-mode changes.'
);
assertGatewayGauge(
    ($polarIPSViewLabelPositionField['enabled'] ?? null) === true
        && array_column($polarIPSViewLabelPositionField['options'], 'value')
            === ['middle', 'insideStart', 'insideEnd', 'outside', 'start', 'end'],
    'Independent IPSView must allow the same value-label positions as the Tile.'
);
$polarIPSViewFontRow = current(array_filter(
    $polarIPSViewPanel['items'],
    static fn (array $item): bool => in_array('IPSViewCategoryLabelFontSize', array_column($item['items'] ?? [], 'name'), true)
));
assertGatewayGauge(
    array_column($polarIPSViewFontRow['items'], 'enabled') === [true, true, true],
    'Independent IPSView font-size controls must be enabled.'
);
$polar->UpdateIPSViewValueScaleForm('auto');
assertGatewayGauge(
    array_slice($polar->GetTestFormUpdates(), -2) === [
        ['Field' => 'IPSViewValueAxisMinimum', 'Parameter' => 'visible', 'Value' => false],
        ['Field' => 'IPSViewValueAxisMaximum', 'Parameter' => 'visible', 'Value' => false]
    ],
    'Polar IPSView form must update its fixed-scale controls immediately when the mode changes.'
);
$polar->UpdateAnimationForm(true, true);
assertGatewayGauge(
    array_slice($polar->GetTestFormUpdates(), -6) === [
        ['Field' => 'IPSViewAnimationDuration', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'IPSViewAnimationDurationUpdate', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'IPSViewAnimationEasing', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'IPSViewAnimationEasingUpdate', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'IPSViewAnimationDelay', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'IPSViewAnimationDelayUpdate', 'Parameter' => 'visible', 'Value' => true]
    ],
    'Polar IPSView animation controls must appear immediately when enabled.'
);
$polar->SetTestProperty('IPSViewUseTileDesign', true);
$polar->SetTestProperty('IPSViewValueAxisMinimum', 100.0);
$polar->SetTestProperty('IPSViewValueAxisMaximum', 100.0);
$polar->SetTestProperty('IPSViewAngularSpan', 0);
$polar->ApplyChanges();
$polarInheritedHTML = $polar->GetIPSViewHTML();
$polarInheritedForm = json_decode($polar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$polarInheritedPanel = current(array_filter(
    $polarInheritedForm['elements'],
    static fn (array $element): bool => ($element['caption'] ?? '') === 'IPSView design'
));
$polarInheritedPanel['items'] = flattenDesignerSections($polarInheritedPanel['items']);
$polarInheritedScaleRow = current(array_filter(
    $polarInheritedPanel['items'],
    static fn (array $item): bool => in_array('IPSViewValueAxisRangeMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarInheritedScaleFields = array_column($polarInheritedScaleRow['items'], null, 'name');
$polarInheritedFillRow = current(array_filter(
    $polarInheritedPanel['items'],
    static fn (array $item): bool => in_array('IPSViewBarFillMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarInheritedFillFields = array_column($polarInheritedFillRow['items'], null, 'name');
$polarInheritedHighlightRow = current(array_filter(
    $polarInheritedPanel['items'],
    static fn (array $item): bool => in_array('IPSViewBarHighlightMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarInheritedHighlightFields = array_column($polarInheritedHighlightRow['items'], null, 'name');
$polarInheritedOutlineRow = current(array_filter(
    $polarInheritedPanel['items'],
    static fn (array $item): bool => in_array('IPSViewBarOutlineWidth', array_column($item['items'] ?? [], 'name'), true)
));
$polarInheritedOutlineFields = array_column($polarInheritedOutlineRow['items'], null, 'name');
$polarInheritedShadowRow = current(array_filter(
    $polarInheritedPanel['items'],
    static fn (array $item): bool => in_array('IPSViewBarShadowBlur', array_column($item['items'] ?? [], 'name'), true)
));
$polarInheritedAnimationRow = current(array_filter(
    $polarInheritedPanel['items'],
    static fn (array $item): bool => in_array('IPSViewAnimationEnabled', array_column($item['items'] ?? [], 'name'), true)
));
$polarInheritedLabelPositionField = current(array_filter(
    $polarInheritedPanel['items'],
    static fn (array $item): bool => ($item['name'] ?? '') === 'IPSViewValueLabelPosition'
));
$polarInheritedFontRow = current(array_filter(
    $polarInheritedPanel['items'],
    static fn (array $item): bool => in_array('IPSViewCategoryLabelFontSize', array_column($item['items'] ?? [], 'name'), true)
));
assertGatewayGauge(
    $polar->GetTestStatus() === IS_ACTIVE
        && str_contains($polarInheritedHTML, '"valueAxisMinimum":-10.5')
        && str_contains($polarInheritedHTML, '"barBackgroundColor":"#778899"')
        && str_contains($polarInheritedHTML, '"angularSpan":180')
        && str_contains($polarInheritedHTML, '"valueLabelPosition":"insideEnd"')
        && str_contains($polarInheritedHTML, '"categoryLabelFontSize":9')
        && str_contains($polarInheritedHTML, '"valueLabelFontSize":11')
        && str_contains($polarInheritedHTML, '"scaleLabelFontSize":8')
        && str_contains($polarInheritedHTML, '"barGradientColor":"#55CCAA"')
        && str_contains($polarInheritedHTML, '"barOpacityPercent":65')
        && str_contains($polarInheritedHTML, '"barOutlineWidth":2')
        && str_contains($polarInheritedHTML, '"barOutlineColor":"#123456"')
        && str_contains($polarInheritedHTML, '"barShadowBlur":12')
        && str_contains($polarInheritedHTML, '"barShadowColor":"#223344"')
        && str_contains($polarInheritedHTML, '"barShadowOpacityPercent":60')
        && str_contains($polarInheritedHTML, '"animationEnabled":true')
        && str_contains($polarInheritedHTML, '"animationDuration":700')
        && str_contains($polarInheritedHTML, '"animationDurationUpdate":1200')
        && str_contains($polarInheritedHTML, '"barHighlightMode":"focus"')
        && str_contains($polarInheritedHTML, '"barHighlightColor":"#CC00AA"')
        && ($polarInheritedScaleFields['IPSViewValueAxisRangeMode']['enabled'] ?? null) === false
        && ($polarInheritedScaleFields['IPSViewValueAxisMinimum']['enabled'] ?? null) === false
        && ($polarInheritedFillFields['IPSViewBarFillMode']['enabled'] ?? null) === false
        && ($polarInheritedFillFields['IPSViewBarGradientColor']['enabled'] ?? null) === false
        && ($polarInheritedFillFields['IPSViewBarOpacityPercent']['enabled'] ?? null) === false
        && ($polarInheritedOutlineFields['IPSViewBarOutlineWidth']['enabled'] ?? null) === false
        && ($polarInheritedOutlineFields['IPSViewBarOutlineColor']['enabled'] ?? null) === false
        && array_column($polarInheritedShadowRow['items'], 'enabled') === [false, false, false]
        && array_column($polarInheritedAnimationRow['items'], 'enabled') === [false, false, false]
        && ($polarInheritedHighlightFields['IPSViewBarHighlightMode']['enabled'] ?? null) === false
        && ($polarInheritedHighlightFields['IPSViewBarHighlightColor']['enabled'] ?? null) === false
        && ($polarInheritedLabelPositionField['enabled'] ?? null) === false
        && array_column($polarInheritedFontRow['items'], 'enabled') === [false, false, false],
    'Inherited Polar IPSView design must follow the Tile scale and tracks while disabling independent controls.'
);
$GLOBALS['symconTestCopyTarget'] = $polar;
try {
    $polar->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
$polarCopiedHTML = $polar->GetIPSViewHTML();
assertGatewayGauge(
    $polar->GetTestStatus() === IS_ACTIVE
        && str_contains($polarCopiedHTML, '"valueAxisRangeMode":"manual"')
        && str_contains($polarCopiedHTML, '"valueAxisMinimum":-10.5')
        && str_contains($polarCopiedHTML, '"valueAxisMaximum":30.25')
        && str_contains($polarCopiedHTML, '"showBarBackground":true')
        && str_contains($polarCopiedHTML, '"barBackgroundColor":"#778899"')
        && str_contains($polarCopiedHTML, '"barBackgroundOpacityPercent":40')
        && str_contains($polarCopiedHTML, '"barFillMode":"gradient"')
        && str_contains($polarCopiedHTML, '"barGradientColor":"#55CCAA"')
        && str_contains($polarCopiedHTML, '"barOpacityPercent":65')
        && str_contains($polarCopiedHTML, '"barOutlineWidth":2')
        && str_contains($polarCopiedHTML, '"barOutlineColor":"#123456"')
        && str_contains($polarCopiedHTML, '"barShadowBlur":12')
        && str_contains($polarCopiedHTML, '"barShadowColor":"#223344"')
        && str_contains($polarCopiedHTML, '"barShadowOpacityPercent":60')
        && str_contains($polarCopiedHTML, '"animationEnabled":true')
        && str_contains($polarCopiedHTML, '"animationDuration":700')
        && str_contains($polarCopiedHTML, '"animationDurationUpdate":1200')
        && str_contains($polarCopiedHTML, '"barHighlightMode":"focus"')
        && str_contains($polarCopiedHTML, '"barHighlightColor":"#CC00AA"')
        && str_contains($polarCopiedHTML, '"angularSpan":180')
        && str_contains($polarCopiedHTML, '"valueLabelPosition":"insideEnd"')
        && str_contains($polarCopiedHTML, '"categoryLabelFontSize":9')
        && str_contains($polarCopiedHTML, '"valueLabelFontSize":11')
        && str_contains($polarCopiedHTML, '"scaleLabelFontSize":8'),
    'Copying the Polar Tile design must copy the fixed scale and tracks, including floating-point limits.'
);
$polarForm = json_decode($polar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$polarTileDesigner = current(array_filter(
    $polarForm['elements'],
    static fn (array $element): bool => ($element['caption'] ?? '') === 'Tile designer'
));
$polarTileDesigner['items'] = flattenDesignerSections($polarTileDesigner['items']);
$polarTileDesignJSON = json_encode($polarTileDesigner, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    str_contains($polarTileDesignJSON, 'ValueAxisRangeMode')
        && str_contains($polarTileDesignJSON, 'ValueAxisMinimum')
        && str_contains($polarTileDesignJSON, 'ValueAxisMaximum')
        && str_contains($polarTileDesignJSON, 'ShowBarBackground')
        && str_contains($polarTileDesignJSON, 'BarBackgroundColor')
        && str_contains($polarTileDesignJSON, 'BarBackgroundOpacityPercent')
        && str_contains($polarTileDesignJSON, 'AngularSpan')
        && str_contains($polarTileDesignJSON, 'ValueLabelPosition')
        && str_contains($polarTileDesignJSON, 'CategoryLabelFontSize')
        && str_contains($polarTileDesignJSON, 'ValueLabelFontSize')
        && str_contains($polarTileDesignJSON, 'ScaleLabelFontSize'),
    'Polar Bar Tile designer must expose the fixed scale and background-track controls.'
);
$polarFillRow = current(array_filter(
    $polarTileDesigner['items'],
    static fn (array $item): bool => in_array('BarFillMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarFillFields = array_column($polarFillRow['items'], null, 'name');
assertGatewayGauge(
    ($polarFillFields['BarGradientColor']['visible'] ?? null) === true,
    'Polar Tile designer must reveal the end color for gradient fill.'
);
$polarHighlightRow = current(array_filter(
    $polarTileDesigner['items'],
    static fn (array $item): bool => in_array('BarHighlightMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarHighlightFields = array_column($polarHighlightRow['items'], null, 'name');
assertGatewayGauge(
    ($polarHighlightFields['BarHighlightColor']['visible'] ?? null) === true,
    'Polar Tile designer must reveal the chosen highlight color.'
);
$polar->UpdateBarHighlightForm(false, 'off');
assertGatewayGauge(
    array_slice($polar->GetTestFormUpdates(), -1) === [
        ['Field' => 'BarHighlightColor', 'Parameter' => 'visible', 'Value' => false]
    ],
    'Polar Tile designer must hide the unused highlight color immediately.'
);
$polar->UpdateBarFillForm(false, 'solid');
assertGatewayGauge(
    array_slice($polar->GetTestFormUpdates(), -1) === [
        ['Field' => 'BarGradientColor', 'Parameter' => 'visible', 'Value' => false]
    ],
    'Polar Tile designer must react immediately to fill-mode changes.'
);
$polarScaleRow = current(array_filter(
    $polarTileDesigner['items'],
    static fn (array $item): bool => in_array('ValueAxisRangeMode', array_column($item['items'] ?? [], 'name'), true)
));
$polarScaleFields = array_column($polarScaleRow['items'], null, 'name');
assertGatewayGauge(
    ($polarScaleFields['ValueAxisMinimum']['visible'] ?? null) === true
        && ($polarScaleFields['ValueAxisMaximum']['visible'] ?? null) === true,
    'Polar Bar Tile form must reveal the fixed-scale limits for manual mode.'
);
$polar->UpdateValueScaleForm('auto');
assertGatewayGauge(
    array_slice($polar->GetTestFormUpdates(), -2) === [
        ['Field' => 'ValueAxisMinimum', 'Parameter' => 'visible', 'Value' => false],
        ['Field' => 'ValueAxisMaximum', 'Parameter' => 'visible', 'Value' => false]
    ],
    'Polar Bar Tile form must update both fixed-scale controls immediately when the mode changes.'
);
$polar->MessageSink(0, 4717, VM_UPDATE, []);
$polarUpdates = $polar->GetTestVisualizationUpdates();
$lastPolarUpdate = end($polarUpdates);
assertGatewayGauge(
    is_string($lastPolarUpdate) && str_contains($lastPolarUpdate, '"variant":"polar"'),
    'Polar Bar must update its chart state when a source variable changes.'
);
$polar->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'UseVariablePresentation' => false, 'Unit' => '°C']
], JSON_THROW_ON_ERROR));
$polar->ApplyChanges();
$polar->ApplyChanges();
assertGatewayGauge(
    $polar->GetTestReferences() === [4711] && $polar->GetTestStatus() === IS_ACTIVE,
    'Polar Bar must remove stale references and keep repeated ApplyChanges idempotent.'
);

$outsidePolar = new EChartsBarPolar();
$outsidePolar->Create();
$outsidePolar->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$outsidePolar->SetTestProperty('EnableIPSView', true);
$outsidePolar->SetTestProperty('ValueLabelPosition', 'outside');
$outsidePolar->ApplyChanges();
assertGatewayGauge(
    $outsidePolar->GetTestStatus() === IS_ACTIVE
        && json_decode($outsidePolar->GetPolarData(), true, 512, JSON_THROW_ON_ERROR)['polar']['style']['valueLabelPosition']
            === 'outside'
        && str_contains($outsidePolar->GetIPSViewHTML(), '"valueLabelPosition":"outside"'),
    'Polar Bar must accept outside labels in the Tile and inherited IPSView design.'
);
$outsidePolar->SetTestProperty('ValueLabelPosition', 'middle');
$outsidePolar->SetTestProperty('IPSViewUseTileDesign', false);
$outsidePolar->SetTestProperty('IPSViewValueLabelPosition', 'outside');
$outsidePolar->ApplyChanges();
assertGatewayGauge(
    $outsidePolar->GetTestStatus() === IS_ACTIVE
        && json_decode($outsidePolar->GetPolarData(), true, 512, JSON_THROW_ON_ERROR)['polar']['style']['valueLabelPosition']
            === 'middle'
        && str_contains($outsidePolar->GetIPSViewHTML(), '"valueLabelPosition":"outside"'),
    'Independent IPSView outside labels must not change the Tile position.'
);

$invalidPolarUnits = new EChartsBarPolar();
$invalidPolarUnits->Create();
$invalidPolarUnits->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'UseVariablePresentation' => false, 'Unit' => '°C'],
    ['VariableID' => 4713, 'UseVariablePresentation' => false, 'Unit' => '%']
], JSON_THROW_ON_ERROR));
$invalidPolarUnits->ApplyChanges();
assertGatewayGauge($invalidPolarUnits->GetTestStatus() === 201, 'Polar Bar must reject mixed units.');

$invalidPolarDuplicates = new EChartsBarPolar();
$invalidPolarDuplicates->Create();
$invalidPolarDuplicates->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711], ['VariableID' => 4711]
], JSON_THROW_ON_ERROR));
$invalidPolarDuplicates->ApplyChanges();
assertGatewayGauge($invalidPolarDuplicates->GetTestStatus() === 201, 'Polar Bar must reject duplicate IDs.');

$invalidPolarDesign = new EChartsBarPolar();
$invalidPolarDesign->Create();
$invalidPolarDesign->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarDesign->SetTestProperty('InnerRadiusPercent', 80);
$invalidPolarDesign->ApplyChanges();
assertGatewayGauge($invalidPolarDesign->GetTestStatus() === 202, 'Polar Bar must reject overlapping radii.');

$invalidPolarScale = new EChartsBarPolar();
$invalidPolarScale->Create();
$invalidPolarScale->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarScale->SetTestProperty('ValueAxisRangeMode', 'manual');
$invalidPolarScale->SetTestProperty('ValueAxisMinimum', 25.0);
$invalidPolarScale->SetTestProperty('ValueAxisMaximum', 25.0);
$invalidPolarScale->ApplyChanges();
assertGatewayGauge($invalidPolarScale->GetTestStatus() === 202, 'Polar Bar must reject a non-increasing fixed scale.');

$invalidPolarScaleMode = new EChartsBarPolar();
$invalidPolarScaleMode->Create();
$invalidPolarScaleMode->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarScaleMode->SetTestProperty('ValueAxisRangeMode', 'unknown');
$invalidPolarScaleMode->ApplyChanges();
assertGatewayGauge($invalidPolarScaleMode->GetTestStatus() === 202, 'Polar Bar must reject an unknown scale mode.');

$invalidPolarTrack = new EChartsBarPolar();
$invalidPolarTrack->Create();
$invalidPolarTrack->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarTrack->SetTestProperty('BarBackgroundOpacityPercent', 101);
$invalidPolarTrack->ApplyChanges();
assertGatewayGauge($invalidPolarTrack->GetTestStatus() === 202, 'Polar Bar must reject invalid background opacity.');

$invalidPolarFill = new EChartsBarPolar();
$invalidPolarFill->Create();
$invalidPolarFill->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarFill->SetTestProperty('BarFillMode', 'unsupported');
$invalidPolarFill->ApplyChanges();
assertGatewayGauge($invalidPolarFill->GetTestStatus() === 202, 'Polar Bar must reject unknown fill modes.');

$invalidPolarGradientColor = new EChartsBarPolar();
$invalidPolarGradientColor->Create();
$invalidPolarGradientColor->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarGradientColor->SetTestProperty('BarGradientColor', 0x1000000);
$invalidPolarGradientColor->ApplyChanges();
assertGatewayGauge($invalidPolarGradientColor->GetTestStatus() === 202, 'Polar Bar must reject invalid gradient colors.');

$invalidPolarOpacity = new EChartsBarPolar();
$invalidPolarOpacity->Create();
$invalidPolarOpacity->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarOpacity->SetTestProperty('BarOpacityPercent', 101);
$invalidPolarOpacity->ApplyChanges();
assertGatewayGauge($invalidPolarOpacity->GetTestStatus() === 202, 'Polar Bar must reject invalid bar opacity.');

$invalidPolarHighlight = new EChartsBarPolar();
$invalidPolarHighlight->Create();
$invalidPolarHighlight->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarHighlight->SetTestProperty('BarHighlightMode', 'unknown');
$invalidPolarHighlight->ApplyChanges();
assertGatewayGauge($invalidPolarHighlight->GetTestStatus() === 202, 'Polar Bar must reject unknown highlight modes.');

$invalidPolarHighlightColor = new EChartsBarPolar();
$invalidPolarHighlightColor->Create();
$invalidPolarHighlightColor->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarHighlightColor->SetTestProperty('BarHighlightColor', 0x1000000);
$invalidPolarHighlightColor->ApplyChanges();
assertGatewayGauge($invalidPolarHighlightColor->GetTestStatus() === 202, 'Polar Bar must reject invalid highlight colors.');

$invalidPolarOutline = new EChartsBarPolar();
$invalidPolarOutline->Create();
$invalidPolarOutline->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarOutline->SetTestProperty('BarOutlineWidth', 9);
$invalidPolarOutline->ApplyChanges();
assertGatewayGauge($invalidPolarOutline->GetTestStatus() === 202, 'Polar Bar must reject an oversized outline.');

$invalidPolarOutlineColor = new EChartsBarPolar();
$invalidPolarOutlineColor->Create();
$invalidPolarOutlineColor->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarOutlineColor->SetTestProperty('BarOutlineColor', 0x1000000);
$invalidPolarOutlineColor->ApplyChanges();
assertGatewayGauge($invalidPolarOutlineColor->GetTestStatus() === 202, 'Polar Bar must reject an invalid outline color.');

foreach ([
    ['BarShadowBlur', 21],
    ['BarShadowColor', 0x1000000],
    ['BarShadowOpacityPercent', -1]
] as [$property, $value]) {
    $invalidPolarShadow = new EChartsBarPolar();
    $invalidPolarShadow->Create();
    $invalidPolarShadow->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
    $invalidPolarShadow->SetTestProperty($property, $value);
    $invalidPolarShadow->ApplyChanges();
    assertGatewayGauge($invalidPolarShadow->GetTestStatus() === 202, 'Polar Bar must reject invalid ' . $property . '.');
}

foreach ([
    ['AnimationDuration', -1],
    ['AnimationDuration', 3001],
    ['AnimationDurationUpdate', -1],
    ['AnimationDurationUpdate', 3001],
    ['AnimationDelay', -1],
    ['AnimationDelay', 3001],
    ['AnimationDelayUpdate', -1],
    ['AnimationDelayUpdate', 3001],
    ['AnimationEasing', 'not-supported'],
    ['AnimationEasingUpdate', 'not-supported']
] as [$property, $value]) {
    $invalidPolarAnimation = new EChartsBarPolar();
    $invalidPolarAnimation->Create();
    $invalidPolarAnimation->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
    $invalidPolarAnimation->SetTestProperty($property, $value);
    $invalidPolarAnimation->ApplyChanges();
    assertGatewayGauge(
        $invalidPolarAnimation->GetTestStatus() === 202,
        'Polar Bar must reject invalid ' . $property . '.'
    );
}

$invalidPolarSpan = new EChartsBarPolar();
$invalidPolarSpan->Create();
$invalidPolarSpan->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarSpan->SetTestProperty('AngularSpan', 0);
$invalidPolarSpan->ApplyChanges();
assertGatewayGauge($invalidPolarSpan->GetTestStatus() === 202, 'Polar Bar must reject a zero angular span.');

$invalidPolarLabelPosition = new EChartsBarPolar();
$invalidPolarLabelPosition->Create();
$invalidPolarLabelPosition->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarLabelPosition->SetTestProperty('ValueLabelPosition', 'off-canvas');
$invalidPolarLabelPosition->ApplyChanges();
assertGatewayGauge(
    $invalidPolarLabelPosition->GetTestStatus() === 202,
    'Polar Bar must reject an unsupported Tile value-label position.'
);

$invalidPolarFontSize = new EChartsBarPolar();
$invalidPolarFontSize->Create();
$invalidPolarFontSize->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarFontSize->SetTestProperty('ValueLabelFontSize', 25);
$invalidPolarFontSize->ApplyChanges();
assertGatewayGauge(
    $invalidPolarFontSize->GetTestStatus() === 202,
    'Polar Bar must reject an unsupported Tile font size.'
);

$invalidPolarIPSViewFontSize = new EChartsBarPolar();
$invalidPolarIPSViewFontSize->Create();
$invalidPolarIPSViewFontSize->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarIPSViewFontSize->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPolarIPSViewFontSize->SetTestProperty('IPSViewCategoryLabelFontSize', 7);
$invalidPolarIPSViewFontSize->ApplyChanges();
assertGatewayGauge(
    $invalidPolarIPSViewFontSize->GetTestStatus() === 202,
    'Polar Bar must reject an unsupported independent IPSView font size.'
);

$invalidPolarIPSViewLabelPosition = new EChartsBarPolar();
$invalidPolarIPSViewLabelPosition->Create();
$invalidPolarIPSViewLabelPosition->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarIPSViewLabelPosition->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPolarIPSViewLabelPosition->SetTestProperty('IPSViewValueLabelPosition', 'off-canvas');
$invalidPolarIPSViewLabelPosition->ApplyChanges();
assertGatewayGauge(
    $invalidPolarIPSViewLabelPosition->GetTestStatus() === 202,
    'Polar Bar must reject an unsupported independent IPSView value-label position.'
);

$invalidPolarIPSViewSpan = new EChartsBarPolar();
$invalidPolarIPSViewSpan->Create();
$invalidPolarIPSViewSpan->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarIPSViewSpan->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPolarIPSViewSpan->SetTestProperty('IPSViewAngularSpan', 361);
$invalidPolarIPSViewSpan->ApplyChanges();
assertGatewayGauge(
    $invalidPolarIPSViewSpan->GetTestStatus() === 202,
    'Polar Bar must reject an independent IPSView angular span above a full circle.'
);

$invalidPolarIPSViewScale = new EChartsBarPolar();
$invalidPolarIPSViewScale->Create();
$invalidPolarIPSViewScale->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarIPSViewScale->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPolarIPSViewScale->SetTestProperty('IPSViewValueAxisRangeMode', 'manual');
$invalidPolarIPSViewScale->SetTestProperty('IPSViewValueAxisMinimum', 25.0);
$invalidPolarIPSViewScale->SetTestProperty('IPSViewValueAxisMaximum', 25.0);
$invalidPolarIPSViewScale->ApplyChanges();
assertGatewayGauge(
    $invalidPolarIPSViewScale->GetTestStatus() === 202,
    'Polar Bar must reject a non-increasing independent IPSView scale.'
);

$invalidPolarIPSViewTrack = new EChartsBarPolar();
$invalidPolarIPSViewTrack->Create();
$invalidPolarIPSViewTrack->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarIPSViewTrack->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPolarIPSViewTrack->SetTestProperty('IPSViewBarBackgroundOpacityPercent', 101);
$invalidPolarIPSViewTrack->ApplyChanges();
assertGatewayGauge(
    $invalidPolarIPSViewTrack->GetTestStatus() === 202,
    'Polar Bar must reject invalid independent IPSView track opacity.'
);

$invalidPolarIPSViewOpacity = new EChartsBarPolar();
$invalidPolarIPSViewOpacity->Create();
$invalidPolarIPSViewOpacity->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarIPSViewOpacity->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPolarIPSViewOpacity->SetTestProperty('IPSViewBarOpacityPercent', -1);
$invalidPolarIPSViewOpacity->ApplyChanges();
assertGatewayGauge(
    $invalidPolarIPSViewOpacity->GetTestStatus() === 202,
    'Polar Bar must reject invalid independent IPSView bar opacity.'
);

$invalidPolarIPSViewOutline = new EChartsBarPolar();
$invalidPolarIPSViewOutline->Create();
$invalidPolarIPSViewOutline->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarIPSViewOutline->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPolarIPSViewOutline->SetTestProperty('IPSViewBarOutlineWidth', -1);
$invalidPolarIPSViewOutline->ApplyChanges();
assertGatewayGauge(
    $invalidPolarIPSViewOutline->GetTestStatus() === 202,
    'Polar Bar must reject a negative independent IPSView outline width.'
);

$invalidPolarIPSViewShadow = new EChartsBarPolar();
$invalidPolarIPSViewShadow->Create();
$invalidPolarIPSViewShadow->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarIPSViewShadow->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPolarIPSViewShadow->SetTestProperty('IPSViewBarShadowBlur', -1);
$invalidPolarIPSViewShadow->ApplyChanges();
assertGatewayGauge(
    $invalidPolarIPSViewShadow->GetTestStatus() === 202,
    'Polar Bar must reject an invalid independent IPSView shadow.'
);

$invalidPolarIPSViewAnimation = new EChartsBarPolar();
$invalidPolarIPSViewAnimation->Create();
$invalidPolarIPSViewAnimation->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarIPSViewAnimation->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPolarIPSViewAnimation->SetTestProperty('IPSViewAnimationDurationUpdate', 3001);
$invalidPolarIPSViewAnimation->ApplyChanges();
assertGatewayGauge(
    $invalidPolarIPSViewAnimation->GetTestStatus() === 202,
    'Polar Bar must reject invalid independent IPSView animation timing.'
);

$invalidPolarIPSViewHighlight = new EChartsBarPolar();
$invalidPolarIPSViewHighlight->Create();
$invalidPolarIPSViewHighlight->SetTestProperty('Sources', json_encode([['VariableID' => 4711]], JSON_THROW_ON_ERROR));
$invalidPolarIPSViewHighlight->SetTestProperty('IPSViewUseTileDesign', false);
$invalidPolarIPSViewHighlight->SetTestProperty('IPSViewBarHighlightMode', 'unknown');
$invalidPolarIPSViewHighlight->ApplyChanges();
assertGatewayGauge(
    $invalidPolarIPSViewHighlight->GetTestStatus() === 202,
    'Polar Bar must reject an unsupported independent IPSView highlight mode.'
);

$sixteenPolarSources = [];
for ($index = 0; $index < 16; $index++) {
    $variableID = 5800 + $index;
    $GLOBALS['symconTestVariables'][$variableID] = [
        'VariableType'    => 2,
        'VariableUpdated' => 1780000100 + $index,
        'Value'           => (float) $index,
        'Name'            => 'Polar source ' . $index
    ];
    $sixteenPolarSources[] = ['VariableID' => $variableID, 'UseVariablePresentation' => false, 'Unit' => 'W'];
}
$maximumPolar = new EChartsBarPolar();
$maximumPolar->Create();
$maximumPolar->SetTestProperty('Sources', json_encode($sixteenPolarSources, JSON_THROW_ON_ERROR));
$maximumPolar->ApplyChanges();
assertGatewayGauge(
    $maximumPolar->GetTestStatus() === IS_ACTIVE
        && count(json_decode($maximumPolar->GetPolarData(), true, 512, JSON_THROW_ON_ERROR)['items']) === 16,
    'Polar Bar must accept and preserve all 16 distinct sources.'
);
$sixteenPolarSources[] = ['VariableID' => 4711, 'UseVariablePresentation' => false, 'Unit' => 'W'];
$maximumPolar->SetTestProperty('Sources', json_encode($sixteenPolarSources, JSON_THROW_ON_ERROR));
$maximumPolar->ApplyChanges();
assertGatewayGauge(
    $maximumPolar->GetTestStatus() === IS_ACTIVE
    && count(json_decode($maximumPolar->GetPolarData(), true, 512, JSON_THROW_ON_ERROR)['items']) === 17,
    'Polar Bar must accept the seventeenth distinct source after extending its former limit.'
);
for ($index = 0; $index < 16; $index++) {
    unset($GLOBALS['symconTestVariables'][5800 + $index]);
}

$typographyCases = [
    [EChartsBarCategory::class, 'LegendFontSizePercent', 'LegendColor', ''],
    [EChartsBarWaterfall::class, 'ValueFontSizePercent', 'ValueColor', ''],
    [EChartsBarHistory::class, 'LegendFontSizePercent', 'LegendColor', false],
    [EChartsBarPolar::class, 'TitleFontSizePercent', 'CategoryLabelColor', ''],
    [EChartsPie::class, 'LegendFontSizePercent', 'LegendColor', '']
];
foreach ($typographyCases as [$moduleClass, $fontName, $colorName, $styleArgument]) {
    $module = new $moduleClass();
    $module->Create();
    $module->SetTestProperty($fontName, 150);
    $module->SetTestProperty($colorName, 0x123456);
    $module->SetTestProperty('IPSView' . $fontName, 175);
    $module->SetTestProperty('IPSView' . $colorName, 0x654321);
    $module->SetTestProperty('TitleFontSizePercent', 150);
    $module->SetTestProperty('TitleColor', 0xA1B2C3);
    $module->SetTestProperty('IPSViewTitleFontSizePercent', 175);
    $module->SetTestProperty('IPSViewTitleColor', 0xA2B3C4);
    $module->SetTestProperty('IPSViewUseTileDesign', false);
    $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        findGaugeFormElement($form['elements'], $fontName) !== null
            && findGaugeFormElement($form['elements'], $colorName) !== null
            && (findGaugeFormElement($form['elements'], 'IPSView' . $fontName)['enabled'] ?? null) === true
            && (findGaugeFormElement($form['elements'], 'IPSView' . $colorName)['enabled'] ?? null) === true,
        $moduleClass . ' must expose independent Tile and IPSView typography controls.'
    );
    $tilePreview = (string) (findGaugeFormElement($form['elements'], 'BarPreview')['image'] ?? '');
    $ipsViewPreview = (string) (findGaugeFormElement($form['elements'], 'IPSViewBarPreview')['image'] ?? '');
    assertGatewayGauge(
        str_contains((string) base64_decode(substr($tilePreview, strlen('data:image/svg+xml;base64,')), true), 'fill="#A1B2C3" font-size="30"')
            && str_contains((string) base64_decode(substr($ipsViewPreview, strlen('data:image/svg+xml;base64,')), true), 'fill="#A2B3C4" font-size="35"'),
        $moduleClass . ' must preview the independent title color and size without saving.'
    );
    $module->UpdateBarPreviewFromForm(json_encode([
        'TitleColor'                  => 0x0A0B0C, 'TitleFontSizePercent' => 200,
        'IPSViewUseTileDesign'        => false, 'IPSViewTitleColor' => 0x0D0E0F,
        'IPSViewTitleFontSizePercent' => 50
    ], JSON_THROW_ON_ERROR));
    $previewUpdates = array_slice($module->GetTestFormUpdates(), -2);
    assertGatewayGauge(
        str_contains((string) base64_decode(substr((string) $previewUpdates[0]['Value'], strlen('data:image/svg+xml;base64,')), true), 'fill="#0A0B0C" font-size="40"')
            && str_contains((string) base64_decode(substr((string) $previewUpdates[1]['Value'], strlen('data:image/svg+xml;base64,')), true), 'fill="#0D0E0F" font-size="10"'),
        $moduleClass . ' must update both typography previews from unsaved form values.'
    );
    $styleMethod = new ReflectionMethod($module, 'ReadDesignStyle');
    $tileStyle = $styleMethod->invoke($module, $styleArgument);
    $ipsViewStyle = $styleMethod->invoke($module, $moduleClass === EChartsBarHistory::class ? true : 'IPSView');
    assertGatewayGauge(
        $tileStyle[lcfirst($fontName)] === 150
            && $tileStyle[lcfirst($colorName)] === ($colorName === 'TitleColor' ? '#A1B2C3' : '#123456')
            && $ipsViewStyle[lcfirst($fontName)] === 175
            && $ipsViewStyle[lcfirst($colorName)] === ($colorName === 'TitleColor' ? '#A2B3C4' : '#654321'),
        $moduleClass . ' must keep typography styles separate for Tile and IPSView.'
    );
    $validation = new ReflectionMethod($module, 'IsValidDesign');
    $module->SetTestProperty($fontName, 201);
    assertGatewayGauge(!$validation->invoke($module, ''), $moduleClass . ' must reject an oversized font.');
    $module->SetTestProperty($fontName, 150);
    $module->SetTestProperty('IPSView' . $colorName, 0x1000000);
    assertGatewayGauge(!$validation->invoke($module, 'IPSView'), $moduleClass . ' must reject an invalid IPSView color.');
}

$expectedDesignerSections = [
    EChartsGaugeSingle::class      => [
        'Geometry and pointer', 'Hub design', 'Dial plate', 'Gauge colors',
        'Scale and value ranges', 'Position and visibility', 'Value display and effects', 'Fine tuning'
    ],
    EChartsGaugeMulti::class       => ['Chart title', 'Fine tuning', 'Pointer and hub', 'Scale and colors', 'Dial plate'],
    EChartsGaugeTacho::class       => ['Chart title', 'Fine tuning', 'Pointer and hub', 'Scale and colors', 'Dial plate'],
    EChartsGaugeChronograph::class => ['Chart title', 'Fine tuning', 'Pointer and hub', 'Scale and colors', 'Dial plate'],
    EChartsTimeSeries::class       => ['Lines and symbols', 'Area and axes', 'Typography and colors'],
    EChartsBarCategory::class      => [
        'Bar layout and order', 'Labels and geometry', 'Bar fill and pattern', 'Typography and colors'
    ],
    EChartsBarHistory::class       => ['Axes and display', 'Bar fill and pattern', 'Typography and colors'],
    EChartsBarWaterfall::class     => ['Labels and geometry', 'Step colors', 'Typography and colors'],
    EChartsPie::class              => ['Geometry and order', 'Labels and visibility', 'Typography and colors'],
    EChartsBarPolar::class         => [
        'Polar layout and order', 'Labels and visibility', 'Bar geometry',
        'Bar appearance and effects', 'Scale and angular range', 'Typography and colors'
    ],
    EChartsTree::class             => ['Layout and interaction', 'Labels and typography', 'Node and line colors'],
    EChartsTreeSystemHealth::class => ['Layout and interaction', 'Labels and typography', 'Node and line colors', 'Status colors'],
    EChartsGraph::class            => ['Layout and interaction', 'Labels and typography', 'Node and line colors']
];
$requiredGermanFormTexts = [
    'EChartsBarCategory' => [
        'Typography and colors', 'Title size', 'Legend size', 'Axis label size', 'Value label size',
        'Title color', 'Legend color', 'Axis color', 'Value label color', 'Grid color'
    ],
    'EChartsBarHistory'   => ['Legend size', 'Legend color'],
    'EChartsBarWaterfall' => [
        'Typography and colors', 'Title size', 'Axis label size', 'Value label size',
        'Title color', 'Axis color', 'Value label color', 'Grid color'
    ],
    'EChartsBarPolar' => [
        'Typography and colors', 'Title size', 'Title color', 'Category color',
        'Scale color', 'Outside value color', 'Grid color'
    ],
    'EChartsPie' => [
        'Typography and colors', 'Title size', 'Legend size', 'Label size',
        'Title color', 'Legend color', 'Label color'
    ],
    'EChartsParallel' => [
        'Animate Parallel', 'Initial animation', 'Update animation', 'Initial easing', 'Update easing',
        'Initial delay', 'Update delay', 'ECharts default (cubicInOut)', 'Gentle (quadraticInOut)',
        'Ease out (cubicOut)', 'Sine (sinusoidalInOut)', 'Bounce', 'Elastic',
        'Line type', 'Solid', 'Dashed', 'Dotted', 'Selected line opacity', 'Other line opacity',
        'Axes', 'Show axis values', 'Show axis ticks', 'Axis width', 'Axis title distance'
    ],
    'EChartsGaugeTacho' => [
        'SVG pivot', 'Hub border', 'Colors', 'Scale font', 'Value font', 'Title font',
        'PointerColor', 'AnchorColor', 'AnchorBorderColor', 'RingColor', 'ScaleColor',
        'ValueColor', 'TitleColor', 'ProgressColor', 'Plate design', 'Plate border',
        'Fitting', 'Size', 'Offset X', 'Offset Y', 'Opacity', 'Rotation',
        'None', 'Svg', 'Theme'
    ],
    'EChartsGaugeChronograph' => [
        'SVG pivot', 'Hub border', 'Colors', 'Scale font', 'Value font', 'Title font',
        'PointerColor', 'AnchorColor', 'AnchorBorderColor', 'RingColor', 'ScaleColor',
        'ValueColor', 'TitleColor', 'ProgressColor', 'Plate design', 'Plate border',
        'Fitting', 'Size', 'Offset X', 'Offset Y', 'Opacity', 'Rotation',
        'None', 'Svg', 'Theme'
    ]
];
foreach ($requiredGermanFormTexts as $moduleName => $texts) {
    $locale = json_decode(
        (string) file_get_contents(__DIR__ . '/../' . $moduleName . '/locale.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    $translations = $locale['translations']['de'] ?? [];
    foreach ($texts as $sourceText) {
        assertGatewayGauge(
            isset($translations[$sourceText]) && $translations[$sourceText] !== $sourceText,
            $moduleName . ' must translate the form text ' . $sourceText . ' into German.'
        );
    }
}
foreach ([
    \SymconECharts\EChartsGaugeDesign::class,
    \SymconECharts\EChartsTimeSeriesDesign::class
] as $designClass) {
    $individualColumn = null;
    foreach ($designClass::SourceDesignColumns() as $column) {
        if (($column['name'] ?? null) === 'UseIndividualDesign') {
            $individualColumn = $column;
            break;
        }
    }
    assertGatewayGauge(
        ($individualColumn['edit']['type'] ?? null) === 'CheckBox',
        $designClass . ' must display the individual-design state as a checkbox in the source list.'
    );
}
foreach ([EChartsGaugeMulti::class, EChartsGaugeTacho::class, EChartsGaugeChronograph::class] as $moduleClass) {
    $gauge = new $moduleClass();
    $gauge->Create();
    $gauge->SetTestProperty('Title', 'Climate');
    $gauge->SetTestProperty('Sources', $multiSources);
    $gauge->SetTestProperty('ChartTitleFontSizePercent', 150);
    $gauge->SetTestProperty('ChartTitleColor', 0x123456);
    $gauge->SetTestProperty('IPSViewUseTileDesign', false);
    $gauge->SetTestProperty('IPSViewChartTitleFontSizePercent', 75);
    $gauge->SetTestProperty('IPSViewChartTitleColor', 0x654321);
    $form = json_decode($gauge->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    foreach (['ChartTitleFontSizePercent', 'ChartTitleColor', 'IPSViewChartTitleFontSizePercent', 'IPSViewChartTitleColor'] as $name) {
        $field = findGaugeFormElement($form['elements'], $name);
        assertGatewayGauge(is_array($field) && isset($field['onChange']), $moduleClass . ' must live-update ' . $name . '.');
    }
    $tileImage = (string) findGaugeFormElement($form['elements'], 'GaugePreview')['image'];
    $ipsImage = (string) findGaugeFormElement($form['elements'], 'IPSViewGaugePreview')['image'];
    $tileSvg = base64_decode(substr($tileImage, strlen('data:image/svg+xml;base64,')), true);
    $ipsSvg = base64_decode(substr($ipsImage, strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        is_string($tileSvg) && is_string($ipsSvg)
            && str_contains($tileSvg, 'fill="#123456" font-size="27"')
            && str_contains($ipsSvg, 'fill="#654321" font-size="14"'),
        $moduleClass . ' must preview separate Tile and IPSView chart-title typography.'
    );
    $tileData = json_decode($gauge->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
    $tileStyle = $tileData['gauge']['style'] ?? [];
    assertGatewayGauge(
        ($tileStyle['chartTitleFontSizePercent'] ?? null) === 150
            && ($tileStyle['chartTitleColor'] ?? null) === '#123456'
            && ($tileStyle['titleFontSizePercent'] ?? null) === 100,
        $moduleClass . ' must expose chart-title settings without changing instrument-title settings.'
    );
    $ipsViewStyle = (new ReflectionMethod($gauge, 'ReadGaugeStyle'))->invoke($gauge, 'IPSView');
    assertGatewayGauge(
        ($ipsViewStyle['chartTitleFontSizePercent'] ?? null) === 75
            && ($ipsViewStyle['chartTitleColor'] ?? null) === '#654321',
        $moduleClass . ' must retain independent IPSView chart-title settings.'
    );
    $gauge->UpdateGaugePreviewFromForm(json_encode([
        'Title'                             => 'Changed',
        'ChartTitleFontSizePercent'         => 120,
        'ChartTitleColor'                   => 0xABCDEF,
        'IPSViewUseTileDesign'              => true,
        'IPSViewChartTitleFontSizePercent'  => 80,
        'IPSViewChartTitleColor'            => 0x654321
    ], JSON_THROW_ON_ERROR));
    $updates = array_slice($gauge->GetTestFormUpdates(), -2);
    $liveTileSvg = base64_decode(substr((string) $updates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
    $liveIPSViewSvg = base64_decode(substr((string) $updates[1]['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        is_string($liveTileSvg) && is_string($liveIPSViewSvg)
            && str_contains($liveTileSvg, 'fill="#ABCDEF" font-size="22"')
            && str_contains($liveIPSViewSvg, 'fill="#ABCDEF" font-size="22"'),
        $moduleClass . ' must preview unsaved chart-title changes and IPSView inheritance.'
    );
    $validate = new ReflectionMethod($gauge, 'ValidateGaugeDesign');
    assertGatewayGauge(
        $validate->invoke($gauge, '') === null && $validate->invoke($gauge, 'IPSView') === null,
        $moduleClass . ' must accept valid Tile and IPSView chart-title settings.'
    );
    $gauge->SetTestProperty('ChartTitleFontSizePercent', 151);
    assertGatewayGauge($validate->invoke($gauge, '') !== null, $moduleClass . ' must reject oversized chart titles.');
    $gauge->SetTestProperty('ChartTitleFontSizePercent', 100);
    $gauge->SetTestProperty('IPSViewChartTitleColor', 0x1000000);
    assertGatewayGauge($validate->invoke($gauge, 'IPSView') !== null, $moduleClass . ' must reject invalid IPSView title colors.');
    $gauge->SetTestProperty('ChartTitleFontSizePercent', 150);
    $GLOBALS['symconTestCopyTarget'] = $gauge;
    try {
        $gauge->CopyTileDesignToIPSView();
    } finally {
        unset($GLOBALS['symconTestCopyTarget']);
    }
    $copiedStyle = (new ReflectionMethod($gauge, 'ReadGaugeStyle'))->invoke($gauge, 'IPSView');
    assertGatewayGauge(
        $gauge->GetTestStatus() === IS_ACTIVE
            && ($copiedStyle['chartTitleFontSizePercent'] ?? null) === 150
            && ($copiedStyle['chartTitleColor'] ?? null) === '#123456',
        $moduleClass . ' must copy the chart-title settings with the Tile design.'
    );
}
foreach ([
    EChartsGaugeSingle::class       => 'ECGS',
    EChartsGaugeMulti::class        => 'ECGM',
    EChartsGaugeTacho::class        => 'ECGT',
    EChartsGaugeChronograph::class  => 'ECGC',
    EChartsTimeSeries::class        => 'ECTS',
    EChartsBarCategory::class       => 'ECBC',
    EChartsBarHistory::class        => 'ECBH',
    EChartsBarWaterfall::class      => 'ECBW',
    EChartsBarPolar::class          => 'ECBP',
    EChartsPie::class               => 'ECPI',
    EChartsTree::class              => 'ECTR',
    EChartsTreeSystemHealth::class  => 'ECTH',
    EChartsGraph::class             => 'ECGR'
] as $moduleClass => $modulePrefix) {
    $designModule = new $moduleClass();
    $designModule->Create();
    foreach ([true, false] as $useTileDesign) {
        $designModule->SetTestProperty('IPSViewUseTileDesign', $useTileDesign);
        $designForm = json_decode($designModule->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['Tile designer', 'IPSView design'] as $designerCaption) {
            $matchingDesigners = array_values(array_filter(
                $designForm['elements'],
                static fn (array $element): bool => ($element['caption'] ?? null) === $designerCaption
            ));
            assertGatewayGauge(count($matchingDesigners) === 1, $moduleClass . ' must have one ' . $designerCaption . '.');
            assertGatewayGauge(
                !isset($matchingDesigners[0]['width']),
                $moduleClass . ' must let the ' . $designerCaption . ' use the available form width.'
            );
            $sectionPanels = array_values(array_filter(
                $matchingDesigners[0]['items'],
                static fn (array $item): bool => ($item['type'] ?? null) === 'ExpansionPanel'
            ));
            assertGatewayGauge(
                array_column($sectionPanels, 'caption') === $expectedDesignerSections[$moduleClass]
                    && count(array_filter(
                        $sectionPanels,
                        static fn (array $item): bool => ($item['expanded'] ?? null) !== false
                    )) === 0,
                $moduleClass . ' must use its own collapsed topic sections in ' . $designerCaption . '.'
            );
            assertGatewayGauge(
                ($matchingDesigners[0]['items'][array_key_last($matchingDesigners[0]['items'])]['type'] ?? null)
                    === 'Image',
                $moduleClass . ' must keep the ' . $designerCaption . ' preview below its topic sections.'
            );
        }
        $moduleDirectory = $moduleClass;
        $baseForm = json_decode(
            (string) file_get_contents(__DIR__ . '/../' . $moduleDirectory . '/form.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $baseTileDesigner = current(array_filter(
            $baseForm['elements'],
            static fn (array $element): bool => ($element['caption'] ?? null) === 'Tile designer'
        ));
        $actualTileDesigner = current(array_filter(
            $designForm['elements'],
            static fn (array $element): bool => ($element['caption'] ?? null) === 'Tile designer'
        ));
        $missingBaseFields = array_diff(
            designerControlNames($baseTileDesigner['items']),
            designerControlNames($actualTileDesigner['items'])
        );
        if (in_array($moduleClass, [EChartsGaugeTacho::class, EChartsGaugeChronograph::class], true)) {
            $missingBaseFields = array_diff($missingBaseFields, ['GaugePreset']);
        }
        assertGatewayGauge(
            $missingBaseFields === [],
            $moduleClass . ' must preserve every Tile designer field while grouping it: '
                . implode(', ', $missingBaseFields)
        );
        $designPanels = array_values(array_filter(
            $designForm['elements'],
            static fn (array $element): bool => ($element['caption'] ?? null) === 'IPSView design'
        ));
        assertGatewayGauge(count($designPanels) === 1, $moduleClass . ' must have one IPSView designer.');
        $designItems = $designPanels[0]['items'];
        $designSwitches = array_values(array_filter(
            $designItems,
            static fn (array $item): bool => ($item['name'] ?? null) === 'IPSViewUseTileDesign'
        ));
        $independentPanels = array_values(array_filter(
            $designItems,
            static fn (array $item): bool => ($item['caption'] ?? null) === 'Independent IPSView designer'
        ));
        assertGatewayGauge(count($independentPanels) === 0, $moduleClass . ' must show IPSView design fields directly.');
        $copyButtons = array_values(array_filter(
            $designItems,
            static fn (array $item): bool => ($item['name'] ?? null) === 'CopyTileDesignToIPSViewButton'
        ));
        $copyButtonIndex = null;
        foreach ($designItems as $index => $item) {
            if (($item['name'] ?? null) === 'CopyTileDesignToIPSViewButton') {
                $copyButtonIndex = $index;
                break;
            }
        }
        assertGatewayGauge($copyButtonIndex !== null, $moduleClass . ' must place the copy button directly in IPSView design.');
        $directDesignItems = array_slice($designItems, $copyButtonIndex);
        $interactiveFields = [];
        $visitDesignFields = static function (array $items) use (&$visitDesignFields, &$interactiveFields): void
        {
            foreach ($items as $item) {
                if (in_array($item['type'] ?? '', [
                    'Button', 'CheckBox', 'HorizontalSlider', 'List', 'NumberSpinner',
                    'Select', 'SelectColor', 'SelectFile', 'SelectVariable', 'ValidationTextBox'
                ], true)) {
                    $interactiveFields[] = $item;
                }
                if (($item['type'] ?? null) !== 'List' && is_array($item['items'] ?? null)) {
                    $visitDesignFields($item['items']);
                }
            }
        };
        $visitDesignFields($directDesignItems);
        $designFields = $interactiveFields;
        $interactiveFields = [];
        $unaffectedFields = [];
        foreach (array_slice($designItems, 0, $copyButtonIndex) as $item) {
            $visitDesignFields([$item]);
        }
        foreach ($interactiveFields as $field) {
            if (in_array($field['name'] ?? null, [
                'IPSViewAdaptToBackground', 'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent',
                'EnableIPSView', 'IPSViewUseTileDesign'
            ], true)) {
                $unaffectedFields[] = $field;
            }
        }
        $interactiveFields = $designFields;
        assertGatewayGauge(
            count($designSwitches) === 1
                && count($copyButtons) === 1
                && count(array_intersect([
                    'IPSViewAdaptToBackground', 'IPSViewBackgroundColor',
                    'IPSViewBackgroundOpacityPercent', 'EnableIPSView', 'IPSViewUseTileDesign'
                ], array_column($unaffectedFields, 'name'))) === 5
                && count(array_filter(
                    $unaffectedFields,
                    static fn (array $item): bool => ($item['enabled'] ?? true) === false
                )) === 0
                && ($directDesignItems[0]['name'] ?? null) === 'CopyTileDesignToIPSViewButton'
                && count($interactiveFields) >= 4
                && count(array_filter(
                    $interactiveFields,
                    static fn (array $item): bool => ($item['enabled'] ?? null) !== !$useTileDesign
                )) === 0
                && ($copyButtons[0]['enabled'] ?? null) === !$useTileDesign
                && str_contains(
                    $designSwitches[0]['onChange'] ?? '',
                    $modulePrefix . '_UpdateIPSViewDesignAvailability($id, $IPSViewUseTileDesign,'
                ),
            $moduleClass . ' must disable direct IPSView design fields while inheriting Tile design.'
        );
        $designAction = $designSwitches[0]['onChange'];
        $compiledDesignAction = eval('return static function () {' . $designAction . '};');
        assertGatewayGauge(is_callable($compiledDesignAction), $moduleClass . ' has an invalid design callback.');
        foreach ($interactiveFields as $field) {
            assertGatewayGauge(
                str_contains($designAction, var_export($field['name'], true)),
                $moduleClass . ' must update every independent design field when inheritance changes.'
            );
        }
        if (in_array($moduleClass, [
            EChartsGaugeMulti::class, EChartsGaugeTacho::class, EChartsGaugeChronograph::class
        ], true)) {
            assertGatewayGauge(
                !in_array('Sources', array_column($directDesignItems, 'name'), true),
                $moduleClass . ' must not duplicate the shared Sources list inside the independent designer.'
            );
        }
        if ($moduleClass === EChartsGaugeSingle::class) {
            assertGatewayGauge(
                str_contains($designSwitches[0]['onChange'], 'ECGS_UpdateIPSViewGaugePreviewFromForm('),
                'The shared IPSView form state must preserve Gauge Single preview updates.'
            );
        }
    }
    $designModule->UpdateIPSViewDesignAvailability(true, [
        'CopyTileDesignToIPSViewButton', 'IPSViewEChartsTheme'
    ]);
    $designModule->UpdateIPSViewDesignAvailability(false, [
        'CopyTileDesignToIPSViewButton', 'IPSViewEChartsTheme'
    ]);
    assertGatewayGauge(
        array_slice($designModule->GetTestFormUpdates(), -4) === [
            ['Field' => 'CopyTileDesignToIPSViewButton', 'Parameter' => 'enabled', 'Value' => false],
            ['Field' => 'IPSViewEChartsTheme', 'Parameter' => 'enabled', 'Value' => false],
            ['Field' => 'CopyTileDesignToIPSViewButton', 'Parameter' => 'enabled', 'Value' => true],
            ['Field' => 'IPSViewEChartsTheme', 'Parameter' => 'enabled', 'Value' => true]
        ],
        $moduleClass . ' must update the independent designer immediately when inheritance changes.'
    );
}

foreach ([EChartsTimeSeries::class, EChartsBarHistory::class] as $timeModuleClass) {
    $timeModule = new $timeModuleClass();
    $timeModule->Create();
    $timeModule->SetTestProperty('IPSViewUseTileTimeSettings', false);
    $timeForm = json_decode($timeModule->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $rangeIndex = null;
    $timeSwitchIndex = null;
    foreach ($timeForm['elements'] as $index => $element) {
        if (($element['type'] ?? null) === 'RowLayout'
            && in_array('Range', array_column($element['items'] ?? [], 'name'), true)) {
            $rangeIndex = $index;
        }
        if (($element['name'] ?? null) === 'IPSViewUseTileTimeSettings') {
            $timeSwitchIndex = $index;
        }
    }
    $timeRow = $timeForm['elements'][$timeSwitchIndex + 2]['items'] ?? [];
    $independentRange = array_values(array_filter(
        $timeRow,
        static fn (array $item): bool => ($item['name'] ?? null) === 'IPSViewRange'
    ));
    assertGatewayGauge(
        $rangeIndex !== null && $timeSwitchIndex !== null && $timeSwitchIndex > $rangeIndex
            && count($independentRange) === 1
            && ($independentRange[0]['visible'] ?? null) === true
            && ($independentRange[0]['enabled'] ?? true) === true,
        $timeModuleClass . ' must place IPSView time settings beside the main time controls.'
    );
}

$graph = new EChartsGraph();
$graph->Create();
$graph->ApplyChanges();
assertGatewayGauge($graph->GetTestStatus() === 201, 'Unconfigured Graph must report invalid nodes and connections.');
$graphNodes = [
    ['ObjectID' => 9000, 'Label' => ''],
    ['ObjectID' => 9002, 'Label' => 'Sensor A'],
    ['ObjectID' => 9003, 'Label' => '']
];
$graphEdges = [
    ['SourceObjectID' => 9000, 'TargetObjectID' => 9002],
    ['SourceObjectID' => 9000, 'TargetObjectID' => 9003]
];
$graph->SetTestProperty('Nodes', json_encode($graphNodes, JSON_THROW_ON_ERROR));
$graph->SetTestProperty('Edges', json_encode($graphEdges, JSON_THROW_ON_ERROR));
$graph->ApplyChanges();
$graphData = json_decode($graph->GetGraphData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $graph->GetTestStatus() === IS_ACTIVE
        && $graphData['family'] === 'graph'
        && $graphData['graph']['nodes'][1]['id'] === 'object-9002'
        && $graphData['graph']['nodes'][2]['id'] === 'object-9003'
        && $graphData['graph']['nodes'][1]['displayValue'] === '21.3 °C'
        && $graphData['graph']['links'][1]['target'] === 'object-9003',
    'Graph must keep object identities, read formatted values and project explicit edges.'
);
assertGatewayGauge(
    str_contains($graph->GetVisualizationTile(), 'echarts-graph-root')
        && str_contains($graph->GetIPSViewHTML(), 'echarts-graph-root'),
    'Graph must provide both native Tile and IPSView HTML.'
);
$graphForm = json_decode($graph->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    str_contains((string) findGaugeFormElement($graphForm['elements'], 'Nodes')['onAdd'], 'ECGR_UpdateBarPreviewFromForm')
        && str_contains((string) findGaugeFormElement($graphForm['elements'], 'Edges')['onEdit'], 'ECGR_UpdateBarPreviewFromForm')
        && findGaugeFormElement($graphForm['elements'], 'IPSViewGraphLayout')['enabled'] === false,
    'Unsaved Graph node and edge edits must refresh both form previews.'
);
$zigbeeGraph = new EChartsGraphZ2M();
$zigbeeGraph->Create();
$zigbeeForm = json_decode($zigbeeGraph->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$zigbeeDesigner = null;
foreach ($zigbeeForm['elements'] as $element) {
    if (($element['caption'] ?? null) === 'Tile designer') {
        $zigbeeDesigner = $element;
        break;
    }
}
$zigbeeColorsSection = null;
foreach ($zigbeeDesigner['items'] ?? [] as $section) {
    if (($section['caption'] ?? null) === 'Node and line colors') {
        $zigbeeColorsSection = $section;
        break;
    }
}
assertGatewayGauge(
    $zigbeeColorsSection !== null
        && findGaugeFormElement($zigbeeColorsSection['items'], 'LqiLowColor') !== null
        && findGaugeFormElement($zigbeeColorsSection['items'], 'LqiMidColor') !== null
        && findGaugeFormElement($zigbeeColorsSection['items'], 'LqiHighColor') !== null
        && findGaugeFormElement($zigbeeForm['elements'], 'IPSViewLqiLowColor') !== null
        && findGaugeFormElement($zigbeeForm['elements'], 'EdgeColor') === null
        && findGaugeFormElement($zigbeeForm['elements'], 'LinkColorMode') === null,
    'Zigbee LQI anchor colors must appear in both designers and in the visible color section.'
);
$zigbeeGraph->UpdateBarPreviewFromForm(json_encode([
    'LqiLowColor'          => 0x102030, 'LqiMidColor' => 0x405060, 'LqiHighColor' => 0x708090,
    'IPSViewUseTileDesign' => false, 'IPSViewLqiLowColor' => 0xA0B0C0,
    'IPSViewLqiMidColor'   => 0xB0C0D0, 'IPSViewLqiHighColor' => 0xC0D0E0
], JSON_THROW_ON_ERROR));
$zigbeePreviewUpdates = array_slice($zigbeeGraph->GetTestFormUpdates(), -2);
$zigbeeTilePreview = base64_decode(substr((string) $zigbeePreviewUpdates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$zigbeeIPSViewPreview = base64_decode(substr((string) $zigbeePreviewUpdates[1]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    str_contains((string) $zigbeeTilePreview, 'stroke="#102030"')
        && str_contains((string) $zigbeeTilePreview, 'stroke="#405060"')
        && str_contains((string) $zigbeeTilePreview, 'stroke="#708090"')
        && str_contains((string) $zigbeeIPSViewPreview, 'stroke="#A0B0C0"')
        && str_contains((string) $zigbeeIPSViewPreview, 'stroke="#B0C0D0"')
        && str_contains((string) $zigbeeIPSViewPreview, 'stroke="#C0D0E0"'),
    'Unsaved LQI anchor colors must update Tile and independent IPSView previews.'
);
$zigbeeGraph->SetTestProperty('LqiLowColor', 0x102030);
$zigbeeGraph->SetTestProperty('IPSViewUseTileDesign', false);
$zigbeeGraph->SetTestProperty('IPSViewLqiLowColor', 0xA0B0C0);
$zigbeeStyleMethod = new ReflectionMethod($zigbeeGraph, 'ReadDesignStyle');
assertGatewayGauge(
    $zigbeeStyleMethod->invoke($zigbeeGraph, false)['lqiLowColor'] === '#102030'
        && $zigbeeStyleMethod->invoke($zigbeeGraph, true)['lqiLowColor'] === '#A0B0C0',
    'Saved LQI colors must reach Tile and independent IPSView renderers separately.'
);
$draftNodes = $graphNodes;
$draftNodes[1]['Label'] = '<script>Draft</script>';
$graph->UpdateBarPreviewFromForm(json_encode(['Nodes' => $draftNodes, 'Edges' => $graphEdges], JSON_THROW_ON_ERROR));
$graphUpdates = array_slice($graph->GetTestFormUpdates(), -2);
$draftSvg = base64_decode(substr((string) $graphUpdates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($draftSvg)
        && str_contains($draftSvg, '&lt;script&gt;Draft')
        && !str_contains($draftSvg, '<script>')
        && json_decode($graph->GetGraphData(), true, 512, JSON_THROW_ON_ERROR)['graph']['nodes'][1]['name'] === 'Sensor A',
    'Graph draft preview must escape labels and leave the saved configuration untouched.'
);
$graph->SetTestProperty('Edges', json_encode([['SourceObjectID' => 9002, 'TargetObjectID' => 9002]], JSON_THROW_ON_ERROR));
$graph->ApplyChanges();
assertGatewayGauge($graph->GetTestStatus() === 201, 'Graph must reject self connections on ApplyChanges.');

foreach ([
    EChartsGaugeSingle::class, EChartsGaugeMulti::class, EChartsGaugeTacho::class,
    EChartsGaugeChronograph::class, EChartsTimeSeries::class, EChartsBarCategory::class,
    EChartsBarHistory::class, EChartsBarWaterfall::class, EChartsBarPolar::class,
    EChartsPie::class, EChartsTreemap::class, EChartsSunburst::class
] as $animationModuleClass) {
    $animationModule = new $animationModuleClass();
    $animationModule->Create();
    $animationForm = json_decode($animationModule->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    foreach (['', 'IPSView'] as $prefix) {
        $toggle = findGaugeFormElement($animationForm['elements'], $prefix . 'AnimationEnabled');
        $initial = findGaugeFormElement($animationForm['elements'], $prefix . 'AnimationDuration');
        $update = findGaugeFormElement($animationForm['elements'], $prefix . 'AnimationDurationUpdate');
        $initialEasing = findGaugeFormElement($animationForm['elements'], $prefix . 'AnimationEasing');
        $updateEasing = findGaugeFormElement($animationForm['elements'], $prefix . 'AnimationEasingUpdate');
        $initialDelay = findGaugeFormElement($animationForm['elements'], $prefix . 'AnimationDelay');
        $updateDelay = findGaugeFormElement($animationForm['elements'], $prefix . 'AnimationDelayUpdate');
        assertGatewayGauge(
            $toggle !== null && $initial !== null && $update !== null
                && $initialEasing !== null && $updateEasing !== null
                && $initialDelay !== null && $updateDelay !== null
                && str_contains((string) ($toggle['onChange'] ?? ''), '_UpdateAnimationForm(')
                && ($initial['visible'] ?? true) === ($animationModuleClass !== EChartsTimeSeries::class)
                && ($update['visible'] ?? true) === ($animationModuleClass !== EChartsTimeSeries::class)
                && ($initialEasing['visible'] ?? true) === ($animationModuleClass !== EChartsTimeSeries::class)
                && ($updateEasing['visible'] ?? true) === ($animationModuleClass !== EChartsTimeSeries::class)
                && ($initialDelay['visible'] ?? true) === ($animationModuleClass !== EChartsTimeSeries::class)
                && ($updateDelay['visible'] ?? true) === ($animationModuleClass !== EChartsTimeSeries::class)
                && array_column($initialEasing['options'] ?? [], 'value') === [
                    'cubicInOut', 'linear', 'quadraticInOut', 'cubicOut',
                    'sinusoidalInOut', 'bounceOut', 'elasticOut'
                ]
                && array_column($updateEasing['options'] ?? [], 'value') === array_column($initialEasing['options'] ?? [], 'value')
                && ($initialDelay['maximum'] ?? null) === 3000
                && ($updateDelay['maximum'] ?? null) === 3000,
            $animationModuleClass . ' must expose synchronized animation controls in both designers.'
        );
    }
    $animationModule->UpdateAnimationForm(true, false);
    assertGatewayGauge(
        array_slice($animationModule->GetTestFormUpdates(), -6) === [
            ['Field' => 'IPSViewAnimationDuration', 'Parameter' => 'visible', 'Value' => false],
            ['Field' => 'IPSViewAnimationDurationUpdate', 'Parameter' => 'visible', 'Value' => false],
            ['Field' => 'IPSViewAnimationEasing', 'Parameter' => 'visible', 'Value' => false],
            ['Field' => 'IPSViewAnimationEasingUpdate', 'Parameter' => 'visible', 'Value' => false],
            ['Field' => 'IPSViewAnimationDelay', 'Parameter' => 'visible', 'Value' => false],
            ['Field' => 'IPSViewAnimationDelayUpdate', 'Parameter' => 'visible', 'Value' => false]
        ],
        $animationModuleClass . ' must update the IPSView animation fields immediately.'
    );
}

foreach ([
    EChartsGaugeSingle::class, EChartsGaugeMulti::class, EChartsGaugeTacho::class,
    EChartsGaugeChronograph::class, EChartsTimeSeries::class, EChartsBarCategory::class,
    EChartsBarHistory::class, EChartsBarWaterfall::class, EChartsBarPolar::class,
    EChartsPie::class
] as $formModuleClass) {
    $formModule = new $formModuleClass();
    $formModule->Create();
    $formData = json_decode($formModule->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $persistentFields = array_values(array_filter(
        $formData['elements'],
        static fn (array $element): bool => isset($element['name'])
    ));
    assertGatewayGauge(
        ($persistentFields[0]['name'] ?? null) === 'Title',
        $formModuleClass . ' must put the chart title before source configuration.'
    );
    if ($formModuleClass === EChartsBarWaterfall::class) {
        assertGatewayGauge(
            array_search('Sources', array_column($persistentFields, 'name'), true)
                < array_search('TotalLabel', array_column($persistentFields, 'name'), true),
            'Waterfall must put its source list before the final-total option.'
        );
    }

    $tilePanel = current(array_filter(
        $formData['elements'],
        static fn (array $element): bool => ($element['caption'] ?? null) === 'Tile designer'
    ));
    $firstTileRow = $tilePanel['items'][0]['items'] ?? [];
    assertGatewayGauge(
        in_array('AnimationEnabled', array_column($firstTileRow, 'name'), true),
        $formModuleClass . ' must start the Tile designer with animation controls.'
    );

    $ipsViewPanel = current(array_filter(
        $formData['elements'],
        static fn (array $element): bool => ($element['caption'] ?? null) === 'IPSView design'
    ));
    $ipsViewItems = $ipsViewPanel['items'] ?? [];
    $inheritanceIndex = null;
    foreach ($ipsViewItems as $index => $item) {
        if (($item['name'] ?? null) === 'IPSViewUseTileDesign') {
            $inheritanceIndex = $index;
            break;
        }
    }
    assertGatewayGauge(
        $inheritanceIndex !== null
            && ($ipsViewItems[$inheritanceIndex + 1]['caption'] ?? null)
                === 'Inherited mode follows every Tile design change. Disable it for an independent IPSView appearance.',
        $formModuleClass . ' must explain Tile design inheritance next to the switch.'
    );
}

$multiLocale = json_decode(file_get_contents(__DIR__ . '/../EChartsGaugeMulti/locale.json'), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    isset($multiLocale['translations']['de'][
        'Choose independent dials, ring Gauges, concentric rings, or a weather station. Up to 32 sources share the selected design; source order determines placement and every source keeps its own range and unit.'
    ]),
    'Gauge Multi must translate its current designer explanation.'
);

foreach ([
    'EChartsGaugeSingle', 'EChartsGaugeMulti', 'EChartsGaugeTacho',
    'EChartsGaugeChronograph', 'EChartsTimeSeries', 'EChartsBarCategory',
    'EChartsBarHistory', 'EChartsBarWaterfall', 'EChartsBarPolar', 'EChartsPie'
] as $moduleDirectory) {
    $locale = json_decode(
        file_get_contents(__DIR__ . '/../' . $moduleDirectory . '/locale.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    foreach ([
        'Inherited mode follows every Tile design change. Disable it for an independent IPSView appearance.' => 'Im vererbten Modus folgt IPSView jeder Änderung des Kacheldesigns. Für ein eigenes IPSView-Design deaktivieren.',
        'Creates a standalone WebContent variable for use as an IPSView HTML widget.'                        => 'Erzeugt eine eigenständige WebContent-Variable als HTML-Widget für IPSView.',
        'Adapt to IPSView background'                                                                        => 'An IPSView-Hintergrund anpassen'
    ] as $source => $translation) {
        assertGatewayGauge(
            ($locale['translations']['de'][$source] ?? null) === $translation,
            $moduleDirectory . ' must use the shared German IPSView wording.'
        );
    }
    $gatewayExplanations = array_filter(
        $locale['translations']['de'],
        static fn (string $source): bool => str_starts_with($source, 'One shared EChartsGateway is sufficient'),
        ARRAY_FILTER_USE_KEY
    );
    assertGatewayGauge(
        count($gatewayExplanations) === 1
            && current($gatewayExplanations) === 'Ein gemeinsames EChartsGateway genügt für alle ECharts-Diagramminstanzen. Bei weiteren Diagrammen das vorhandene Gateway auswählen, anstatt ein neues anzulegen.',
        $moduleDirectory . ' must explain the shared gateway consistently.'
    );
    if (in_array($moduleDirectory, [
        'EChartsGaugeMulti', 'EChartsGaugeTacho', 'EChartsGaugeChronograph'
    ], true)) {
        assertGatewayGauge(
            ($locale['translations']['de']['Use variable presentation'] ?? null) === 'Variablendarstellung',
            $moduleDirectory . ' must use the common source-table wording.'
        );
    }
}

foreach ([
    EChartsBarCategory::class, EChartsBarHistory::class,
    EChartsBarWaterfall::class, EChartsBarPolar::class
] as $barPreviewClass) {
    $barPreviewModule = new $barPreviewClass();
    $barPreviewModule->Create();
    $barPreviewForm = json_decode($barPreviewModule->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $images = [];
    $collectImages = static function (array $items) use (&$collectImages, &$images): void
    {
        foreach ($items as $item) {
            if (($item['type'] ?? null) === 'Image') {
                $images[$item['name'] ?? ''] = $item['image'] ?? '';
            }
            if (is_array($item['items'] ?? null)) {
                $collectImages($item['items']);
            }
        }
    };
    $collectImages($barPreviewForm['elements']);
    foreach (['BarPreview', 'IPSViewBarPreview'] as $field) {
        assertGatewayGauge(
            str_starts_with((string) ($images[$field] ?? ''), 'data:image/svg+xml;base64,'),
            $barPreviewClass . ' must initialize the ' . $field . ' image.'
        );
        $svg = base64_decode(substr($images[$field], strlen('data:image/svg+xml;base64,')), true);
        $document = new DOMDocument();
        assertGatewayGauge(
            is_string($svg) && $document->loadXML($svg, LIBXML_NONET)
                && $document->documentElement?->localName === 'svg',
            $barPreviewClass . ' must emit valid SVG in the ' . $field . ' image.'
        );
    }
    $titleControl = findGaugeFormElement($barPreviewForm['elements'], 'Title');
    $sourceControl = findGaugeFormElement($barPreviewForm['elements'], 'Sources');
    $inheritanceControl = findGaugeFormElement($barPreviewForm['elements'], 'IPSViewUseTileDesign');
    assertGatewayGauge(
        str_contains((string) ($titleControl['onChange'] ?? ''), 'UpdateBarPreviewFromForm')
            && str_contains((string) ($sourceControl['onAdd'] ?? ''), 'foreach ($Sources as $barPreviewSource)')
            && str_contains((string) ($sourceControl['onEdit'] ?? ''), 'UpdateBarPreviewFromForm')
            && str_contains((string) ($sourceControl['onDelete'] ?? ''), 'UpdateBarPreviewFromForm')
            && str_contains((string) ($sourceControl['onChangeOrder'] ?? ''), 'UpdateBarPreviewFromForm')
            && str_contains((string) ($inheritanceControl['onChange'] ?? ''), 'UpdateIPSViewDesignAvailability')
            && str_contains((string) ($inheritanceControl['onChange'] ?? ''), 'UpdateBarPreviewFromForm'),
        $barPreviewClass . ' must refresh both previews from draft and source-row actions without losing inheritance callbacks.'
    );
    preg_match_all(
        '/\'([A-Za-z][A-Za-z0-9]*)\' => \$[A-Za-z][A-Za-z0-9]*/',
        (string) ($titleControl['onChange'] ?? ''),
        $previewFieldMatches
    );
    foreach ($previewFieldMatches[1] as $referencedField) {
        assertGatewayGauge(
            findGaugeFormElement($barPreviewForm['elements'], $referencedField) !== null,
            $barPreviewClass . ' must not reference missing form field ' . $referencedField . ' in preview actions.'
        );
    }

    $barPreviewModule->UpdateBarPreviewFromForm(json_encode([
        'Title'                    => 'Entwurf <A>',
        'EChartsTheme'             => 'vintage',
        'IPSViewUseTileDesign'     => false,
        'IPSViewEChartsTheme'      => 'dark',
        'IPSViewAdaptToBackground' => true,
        'IPSViewBackgroundColor'   => 0x123456
    ], JSON_THROW_ON_ERROR));
    $updates = array_slice($barPreviewModule->GetTestFormUpdates(), -2);
    $tileSvg = base64_decode(substr($updates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
    $ipsViewSvg = base64_decode(substr($updates[1]['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        $updates[0]['Field'] === 'BarPreview'
            && $updates[1]['Field'] === 'IPSViewBarPreview'
            && is_string($tileSvg) && is_string($ipsViewSvg)
            && str_contains($tileSvg, 'Entwurf &lt;A&gt;')
            && str_contains($ipsViewSvg, 'Entwurf &lt;A&gt;')
            && str_contains($tileSvg, '#FEF8EF')
            && str_contains($ipsViewSvg, '#123456'),
        $barPreviewClass . ' must apply unsaved title, independent IPSView theme and background to both SVG previews.'
    );
    $unchanged = json_decode($barPreviewModule->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $storedTileSvg = base64_decode(substr(
        findGaugeFormElement($unchanged['elements'], 'BarPreview')['image'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(
        is_string($storedTileSvg) && !str_contains($storedTileSvg, 'Entwurf &lt;A&gt;'),
        $barPreviewClass . ' must not persist live-preview draft changes.'
    );

    $sourceRow = ['VariableID' => 4711, 'Label' => 'Sensor', 'Category' => 'Küche'];
    $barPreviewModule->UpdateBarPreviewFromForm(json_encode([
        'Sources' => [$sourceRow]
    ], JSON_THROW_ON_ERROR));
    $sourceUpdate = array_slice($barPreviewModule->GetTestFormUpdates(), -2)[0];
    $sourceSvg = base64_decode(substr($sourceUpdate['Value'], strlen('data:image/svg+xml;base64,')), true);
    $expectedLabel = $barPreviewClass === EChartsBarCategory::class ? 'Küche' : 'Sensor';
    assertGatewayGauge(
        is_string($sourceSvg) && str_contains($sourceSvg, $expectedLabel),
        $barPreviewClass . ' must show the configured display name after an unsaved source-row addition.'
    );
    $secondRow = ['VariableID' => 4717, 'Label' => 'Zweiter Sensor', 'Category' => 'Zweites Zimmer'];
    $barPreviewModule->UpdateBarPreviewFromForm(json_encode([
        'Sources' => [$sourceRow, $secondRow]
    ], JSON_THROW_ON_ERROR));
    $multipleSvg = base64_decode(substr(
        array_slice($barPreviewModule->GetTestFormUpdates(), -2)[0]['Value'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(
        is_string($multipleSvg)
            && str_contains($multipleSvg, $expectedLabel)
            && str_contains(
                $multipleSvg,
                $barPreviewClass === EChartsBarCategory::class ? 'Zweites' : 'Zweiter'
            ),
        $barPreviewClass . ' must preserve multiple unsaved sources in the preview.'
    );
    $previewLimit = match ($barPreviewClass) {
        EChartsBarCategory::class  => 5,
        EChartsBarHistory::class   => 3,
        EChartsBarWaterfall::class => 5,
        default                    => 6
    };
    $manyRows = array_map(
        static fn (int $variableID): array => [
            'VariableID' => $variableID,
            'Label'      => 'Source ' . $variableID,
            'Category'   => 'Category ' . $variableID
        ],
        [4711, 4713, 4714, 4715, 4716, 4717, 4720]
    );
    $barPreviewModule->UpdateBarPreviewFromForm(json_encode(['Sources' => $manyRows,
        ...($barPreviewClass === EChartsBarCategory::class ? ['Orientation' => 'horizontal',
            'BarWidthPercent'                                               => 100, 'IPSViewOrientation' => 'horizontal', 'IPSViewBarWidthPercent' => 100] : [])
    ], JSON_THROW_ON_ERROR));
    foreach (array_slice($barPreviewModule->GetTestFormUpdates(), -2) as $update) {
        $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
        assertGatewayGauge(
            is_string($svg)
                && str_contains($svg, 'data-preview-sources="' . $previewLimit . '/7"')
                && str_contains($svg, 'Preview shows ' . $previewLimit . ' of 7 sources'),
            $barPreviewClass . ' must disclose when its Tile or IPSView preview omits configured sources.'
        );
    }
    if (in_array($barPreviewClass, [EChartsBarCategory::class, EChartsBarPolar::class], true)) {
        $sortedRows = array_map(
            static fn (int $variableID): array => [
                'VariableID' => $variableID,
                'Label'      => 'S' . $variableID,
                'Category'   => 'C' . $variableID
            ],
            [4711, 4713, 4716, 4717, 4720, 4714, 4715]
        );
        $barPreviewModule->UpdateBarPreviewFromForm(json_encode([
            'Sources'   => $sortedRows,
            'SortOrder' => 'descending'
        ], JSON_THROW_ON_ERROR));
        $sortedSvg = base64_decode(substr(
            array_slice($barPreviewModule->GetTestFormUpdates(), -2)[0]['Value'],
            strlen('data:image/svg+xml;base64,')
        ), true);
        assertGatewayGauge(
            is_string($sortedSvg) && str_contains(
                $sortedSvg,
                $barPreviewClass === EChartsBarCategory::class ? 'C4715' : 'S4715'
            ),
            $barPreviewClass . ' must select preview sources after sorting all sources like the Tile renderer.'
        );
        if ($barPreviewClass === EChartsBarCategory::class) {
            $barPreviewModule->UpdateBarPreviewFromForm(json_encode([
                'Sources'   => $sortedRows,
                'BarMode'   => 'grouped',
                'SortOrder' => 'descending'
            ], JSON_THROW_ON_ERROR));
            $groupedSvg = base64_decode(substr(
                array_slice($barPreviewModule->GetTestFormUpdates(), -2)[0]['Value'],
                strlen('data:image/svg+xml;base64,')
            ), true);
            assertGatewayGauge(
                is_string($groupedSvg) && str_contains($groupedSvg, 'C4715'),
                'EChartsBarCategory must sort all grouped category totals before limiting the preview.'
            );
        }
    }
    $editedRow = ['VariableID' => 4711, 'Label' => 'Neuer Sensor', 'Category' => 'Neues Bad'];
    $barPreviewModule->UpdateBarPreviewFromForm(json_encode([
        'Sources' => [$editedRow]
    ], JSON_THROW_ON_ERROR));
    $editedSvg = base64_decode(substr(
        array_slice($barPreviewModule->GetTestFormUpdates(), -2)[0]['Value'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(
        is_string($editedSvg) && str_contains(
            $editedSvg,
            $barPreviewClass === EChartsBarCategory::class ? 'Neues Bad' : 'Neuer Sensor'
        ),
        $barPreviewClass . ' must reflect an unsaved source-row edit.'
    );
    $barPreviewModule->UpdateBarPreviewFromForm(json_encode([
        'Sources' => []
    ], JSON_THROW_ON_ERROR));
    $deletedSvg = base64_decode(substr(
        array_slice($barPreviewModule->GetTestFormUpdates(), -2)[0]['Value'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(
        is_string($deletedSvg) && str_contains($deletedSvg, 'Example data'),
        $barPreviewClass . ' must show a labelled sample after deleting the last source in the preview.'
    );
    $barPreviewModule->UpdateBarPreviewFromForm(json_encode([
        'Sources'      => [['VariableID' => -1, 'Label' => '<unsafe>']],
        'EChartsTheme' => 'unsupported-theme'
    ], JSON_THROW_ON_ERROR));
    $invalidSvg = base64_decode(substr(
        array_slice($barPreviewModule->GetTestFormUpdates(), -2)[0]['Value'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(
        is_string($invalidSvg)
            && str_contains($invalidSvg, 'Example data')
            && !str_contains($invalidSvg, '<unsafe>'),
        $barPreviewClass . ' must use a safe sample for invalid draft sources and themes.'
    );
}

foreach ([EChartsBarCategory::class, EChartsBarPolar::class] as $presentedBarClass) {
    $presentedBar = new $presentedBarClass();
    $presentedBar->Create();
    $presentedBar->UpdateBarPreviewFromForm(json_encode([
        'Sources' => [[
            'VariableID'             => 4715,
            'Category'               => 'Air pressure',
            'Label'                  => 'Air pressure',
            'UseVariablePresentation'=> true,
            'Unit'                   => 'manual',
            'Decimals'               => 6
        ]],
        'IPSViewUseTileDesign' => false
    ], JSON_THROW_ON_ERROR));
    foreach (array_slice($presentedBar->GetTestFormUpdates(), -2) as $presentedBarUpdate) {
        $presentedBarSvg = base64_decode(substr(
            $presentedBarUpdate['Value'],
            strlen('data:image/svg+xml;base64,')
        ), true);
        assertGatewayGauge(
            is_string($presentedBarSvg) && str_contains($presentedBarSvg, '1,013.3 hPa'),
            $presentedBarClass . ' previews must use effective source presentation for value labels.'
        );
    }
}
assertGatewayGauge(
    \SymconECharts\EChartsBarPreview::FormatValue(1234.5, 2, '°C', 'de') === '1.234,50 °C',
    'Shared Bar preview values must use German decimal and grouping separators when configured.'
);

$historyFormatPreview = new EChartsBarHistory();
$historyFormatPreview->Create();
$historyFormatPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources' => [[
        'VariableID'              => 4711,
        'Label'                   => 'Sensor',
        'UseVariablePresentation' => false,
        'Unit'                    => 'kWh',
        'Decimals'                => 3
    ]],
    'ShowValues'            => true,
    'IPSViewUseTileDesign'  => false,
    'IPSViewShowValues'     => true
], JSON_THROW_ON_ERROR));
foreach (array_slice($historyFormatPreview->GetTestFormUpdates(), -2) as $historyFormatUpdate) {
    $historyFormatSvg = base64_decode(substr(
        $historyFormatUpdate['Value'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(
        is_string($historyFormatSvg)
            && preg_match('/<text[^>]*>\d+\.\d{3} kWh<\/text>/', $historyFormatSvg) === 1,
        'Historical Bar Tile and independent IPSView previews must format example labels with source decimals and unit.'
    );
}

$historyAxesPreview = new EChartsBarHistory();
$historyAxesPreview->Create();
$historyAxisSources = [
    ['VariableID' => 4711, 'Label' => 'Temperature', 'UseVariablePresentation' => false,
        'Unit'    => '°C', 'AxisPosition' => 'right', 'Color' => 0x123456],
    ['VariableID' => 4713, 'Label' => 'Humidity', 'UseVariablePresentation' => false,
        'Unit'    => '%', 'AxisPosition' => 'left', 'Color' => 0x654321],
    ['VariableID' => 4714, 'Label' => 'Other temperature', 'UseVariablePresentation' => false,
        'Unit'    => '°C', 'AxisPosition' => 'right', 'Color' => 0xABCDEF]
];
$historyAxesPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources'              => $historyAxisSources,
    'IPSViewUseTileDesign' => false
], JSON_THROW_ON_ERROR));
foreach (array_slice($historyAxesPreview->GetTestFormUpdates(), -2) as $historyAxesUpdate) {
    $historyAxesSvg = base64_decode(substr(
        $historyAxesUpdate['Value'],
        strlen('data:image/svg+xml;base64,')
    ), true);
    assertGatewayGauge(is_string($historyAxesSvg), 'Historical Bar preview must produce a valid SVG image.');
    $historyAxesDocument = new DOMDocument();
    assertGatewayGauge(
        $historyAxesDocument->loadXML($historyAxesSvg, LIBXML_NONET),
        'Historical Bar multi-axis preview SVG must be valid XML.'
    );
    $drawnAxes = [];
    foreach ($historyAxesDocument->getElementsByTagName('line') as $line) {
        if ($line->hasAttribute('data-preview-axis')) {
            $drawnAxes[$line->getAttribute('data-preview-axis')] = [
                'x'     => (float) $line->getAttribute('x1'),
                'color' => $line->getAttribute('stroke')
            ];
        }
    }
    $axisCaptions = [];
    foreach ($historyAxesDocument->getElementsByTagName('text') as $label) {
        if ($label->hasAttribute('data-preview-axis-unit')) {
            $axisCaptions[] = $label->textContent;
        }
    }
    assertGatewayGauge(
        count($drawnAxes) === 2
            && ($drawnAxes['right']['x'] ?? 0) > 360
            && ($drawnAxes['right']['color'] ?? '') === '#123456'
            && ($drawnAxes['left']['x'] ?? 720) < 360
            && ($drawnAxes['left']['color'] ?? '') === '#654321'
            && $axisCaptions === ['°C', '%'],
        'Historical Bar previews must show one color-coded axis per effective unit on its configured side.'
    );
}
$historyAxisSources[2]['Unit'] = 'hPa';
$historyAxisSources[2]['AxisPosition'] = 'auto';
$historyAxesPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources'              => $historyAxisSources,
    'IPSViewUseTileDesign' => false
], JSON_THROW_ON_ERROR));
foreach (array_slice($historyAxesPreview->GetTestFormUpdates(), -2) as $historyAxesUpdate) {
    $svg = base64_decode(substr($historyAxesUpdate['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(is_string($svg), 'Historical Bar three-unit preview must produce an SVG image.');
    $document = new DOMDocument();
    assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Historical Bar three-unit SVG must be valid XML.');
    $positions = [];
    foreach ($document->getElementsByTagName('line') as $line) {
        if ($line->hasAttribute('data-preview-axis')) {
            $positions[] = [$line->getAttribute('data-preview-axis'), (float) $line->getAttribute('x1')];
        }
    }
    assertGatewayGauge(
        count($positions) === 3
            && $positions[0][0] === 'right'
            && $positions[1][0] === 'left'
            && $positions[2][0] === 'left'
            && $positions[2][1] < $positions[1][1],
        'Historical Bar previews must show additional units as separate offset axes on the assigned side.'
    );
}

$waterfallFormatPreview = new EChartsBarWaterfall();
$waterfallFormatPreview->Create();
$originalWaterfallFormatValue = $GLOBALS['symconTestVariables'][4717]['Value'];
try {
    $GLOBALS['symconTestVariables'][4717]['Value'] = -50.0;
    $waterfallFormatPreview->UpdateBarPreviewFromForm(json_encode([
        'Sources' => [
            ['VariableID' => 4711, 'Label' => 'Start', 'UseVariablePresentation' => false,
                'Unit'    => '°C', 'Decimals' => 2],
            ['VariableID' => 4717, 'Label' => 'Decrease', 'UseVariablePresentation' => false,
                'Unit'    => '°C', 'Decimals' => 1]
        ],
        'IPSViewUseTileDesign' => false
    ], JSON_THROW_ON_ERROR));
    foreach (array_slice($waterfallFormatPreview->GetTestFormUpdates(), -2) as $waterfallFormatUpdate) {
        $waterfallFormatSvg = base64_decode(substr(
            $waterfallFormatUpdate['Value'],
            strlen('data:image/svg+xml;base64,')
        ), true);
        assertGatewayGauge(
            is_string($waterfallFormatSvg)
                && str_contains($waterfallFormatSvg, '-50.00 °C')
                && str_contains($waterfallFormatSvg, '-7.50 °C'),
            'Waterfall previews must use the common unit and maximum source decimals for signed and total values.'
        );
    }
} finally {
    $GLOBALS['symconTestVariables'][4717]['Value'] = $originalWaterfallFormatValue;
}

$waterfallPreview = new EChartsBarWaterfall();
$waterfallPreview->Create();
$waterfallPreviewForm = json_decode($waterfallPreview->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$waterfallPreviewSvg = base64_decode(substr(
    findGaugeFormElement($waterfallPreviewForm['elements'], 'BarPreview')['image'],
    strlen('data:image/svg+xml;base64,')
), true);
$waterfallPreviewLabels = static function (string $svg): array
{
    $document = new DOMDocument();
    $document->loadXML($svg, LIBXML_NONET);
    $bars = [];
    foreach ($document->getElementsByTagName('rect') as $rect) {
        if ($rect->hasAttribute('x') && $rect->hasAttribute('y') && $rect->hasAttribute('height')) {
            $bars[] = [
                'top'    => (float) $rect->getAttribute('y'),
                'bottom' => (float) $rect->getAttribute('y') + (float) $rect->getAttribute('height')
            ];
        }
    }
    $labels = [];
    foreach ($document->getElementsByTagName('text') as $item) {
        $labels[trim($item->textContent)] = (float) $item->getAttribute('y');
    }

    return [$bars, $labels];
};
[$sampleBars, $sampleLabels] = $waterfallPreviewLabels($waterfallPreviewSvg);
assertGatewayGauge(
    count($sampleBars) === 4
        && ($sampleLabels['18.0 °C'] ?? INF) < $sampleBars[0]['top']
        && ($sampleLabels['-5.5 °C'] ?? -INF) > $sampleBars[1]['bottom']
        && ($sampleLabels['20.5 °C'] ?? INF) < $sampleBars[3]['top'],
    'Waterfall preview must place rising values above and falling values below their bars.'
);
$originalWaterfallPreviewValue = $GLOBALS['symconTestVariables'][4717]['Value'];
try {
    $GLOBALS['symconTestVariables'][4717]['Value'] = -50.0;
    $waterfallPreview->UpdateBarPreviewFromForm(json_encode([
        'Sources'              => [
            ['VariableID' => 4711, 'Label' => 'Start'],
            ['VariableID' => 4717, 'Label' => 'Decrease']
        ],
        'IPSViewUseTileDesign' => false,
        'IPSViewShowValues'    => true
    ], JSON_THROW_ON_ERROR));
    foreach (array_slice($waterfallPreview->GetTestFormUpdates(), -2) as $negativeTotalUpdate) {
        $negativeTotalSvg = base64_decode(substr(
            $negativeTotalUpdate['Value'],
            strlen('data:image/svg+xml;base64,')
        ), true);
        [$negativeTotalBars, $negativeTotalLabels] = $waterfallPreviewLabels($negativeTotalSvg);
        assertGatewayGauge(
            count($negativeTotalBars) === 3
                && ($negativeTotalLabels['-50.0'] ?? -INF) > $negativeTotalBars[1]['bottom']
                && ($negativeTotalLabels['-7.5'] ?? -INF) > $negativeTotalBars[2]['bottom']
                // Kernel doubles use English; preview and live model now share the translated final label.
                && ($negativeTotalLabels['Total'] ?? -INF) > ($negativeTotalLabels['-7.5'] ?? INF) + 20,
            'Waterfall Tile and independent IPSView previews must place negative values without label collisions.'
        );
    }
} finally {
    $GLOBALS['symconTestVariables'][4717]['Value'] = $originalWaterfallPreviewValue;
}

$historyCornerPreview = new EChartsBarHistory();
$historyCornerPreview->Create();
$historyCornerPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources'                        => [['VariableID' => 4711, 'Label' => 'Sensor']],
    'BarOpacityPercent'              => 0,
    'IPSViewUseTileDesign'           => false,
    'IPSViewBarOpacityPercent'       => 5
], JSON_THROW_ON_ERROR));
$historyOpacityUpdates = array_slice($historyCornerPreview->GetTestFormUpdates(), -2);
$historyOpacityTileSvg = base64_decode(substr(
    $historyOpacityUpdates[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
$historyOpacityIPSViewSvg = base64_decode(substr(
    $historyOpacityUpdates[1]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
$historyPreviewBarOpacity = static function (string $svg): ?string
{
    $document = new DOMDocument();
    if (!$document->loadXML($svg, LIBXML_NONET)) {
        return null;
    }
    foreach ($document->getElementsByTagName('rect') as $rect) {
        if ($rect->hasAttribute('x') && $rect->hasAttribute('y') && $rect->hasAttribute('fill-opacity')) {
            return $rect->getAttribute('fill-opacity');
        }
    }

    return null;
};
assertGatewayGauge(
    is_string($historyOpacityTileSvg) && is_string($historyOpacityIPSViewSvg)
        && $historyPreviewBarOpacity($historyOpacityTileSvg) === '0'
        && $historyPreviewBarOpacity($historyOpacityIPSViewSvg) === '0.05',
    'Historical Bar previews must reflect 0% Tile and 5% independent IPSView bar opacity.'
);
$historyCornerPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources'                      => [['VariableID' => 4711, 'Label' => 'Sensor']],
    'RoundedBars'                  => true,
    'BarCornerRadius'              => 0,
    'IPSViewUseTileDesign'         => false,
    'IPSViewRoundedBars'           => true,
    'IPSViewBarCornerRadius'       => 12
], JSON_THROW_ON_ERROR));
$historyCornerUpdates = array_slice($historyCornerPreview->GetTestFormUpdates(), -2);
$historyTileSvg = base64_decode(substr(
    $historyCornerUpdates[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
$historyIPSViewSvg = base64_decode(substr(
    $historyCornerUpdates[1]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($historyTileSvg) && is_string($historyIPSViewSvg)
        && str_contains($historyTileSvg, 'rx="0"')
        && str_contains($historyIPSViewSvg, 'rx="12"'),
    'Historical Bar previews must reflect independent unsaved corner radii.'
);
$historyCornerPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources'                => [['VariableID' => 4711, 'Label' => 'Sensor']],
    'RoundedBars'            => true,
    'BarCornerRadius'        => 9,
    'IPSViewUseTileDesign'   => true,
    'IPSViewRoundedBars'     => true,
    'IPSViewBarCornerRadius' => 12
], JSON_THROW_ON_ERROR));
$historyInheritedUpdates = array_slice($historyCornerPreview->GetTestFormUpdates(), -2);
$historyInheritedTileSvg = base64_decode(substr(
    $historyInheritedUpdates[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
$historyInheritedIPSViewSvg = base64_decode(substr(
    $historyInheritedUpdates[1]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($historyInheritedTileSvg) && is_string($historyInheritedIPSViewSvg)
        && str_contains($historyInheritedTileSvg, 'rx="9"')
        && str_contains($historyInheritedIPSViewSvg, 'rx="9"'),
    'Historical Bar IPSView preview must inherit the unsaved Tile corner radius.'
);

$historyCornerPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources'                      => [['VariableID' => 4711, 'Label' => 'Sensor']],
    'BarFillMode'                  => 'svg',
    'BarSVGSizePercent'            => 50,
    'IPSViewUseTileDesign'         => false,
    'IPSViewBarFillMode'           => 'svg',
    'IPSViewBarSVGSizePercent'     => 200
], JSON_THROW_ON_ERROR));
$historyPatternUpdates = array_slice($historyCornerPreview->GetTestFormUpdates(), -2);
$historyPatternTileSvg = base64_decode(substr(
    $historyPatternUpdates[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
$historyPatternIPSViewSvg = base64_decode(substr(
    $historyPatternUpdates[1]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($historyPatternTileSvg) && is_string($historyPatternIPSViewSvg)
        && str_contains($historyPatternTileSvg, 'pattern id="bar-history-preview-0" width="4" height="4"')
        && str_contains($historyPatternIPSViewSvg, 'pattern id="bar-history-preview-0" width="14" height="14"'),
    'Historical Bar previews must reflect independent unsaved SVG pattern sizes.'
);

$categoryPreview = new EChartsBarCategory();
$categoryPreview->Create();
$categorySources = [
    ['VariableID' => 4711, 'Category' => 'Wohnraum', 'Series' => 'Temperatur'],
    ['VariableID' => 4717, 'Category' => 'Wohnraum', 'Series' => 'Luftfeuchte']
];
$categoryPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources' => $categorySources, 'BarMode' => 'grouped'
], JSON_THROW_ON_ERROR));
$groupedSvg = base64_decode(substr(
    array_slice($categoryPreview->GetTestFormUpdates(), -2)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
$categoryPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources' => $categorySources, 'BarMode' => 'stacked'
], JSON_THROW_ON_ERROR));
$stackedSvg = base64_decode(substr(
    array_slice($categoryPreview->GetTestFormUpdates(), -2)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
$barXs = static function (string $svg): array
{
    $document = new DOMDocument();
    $document->loadXML($svg, LIBXML_NONET);
    $xs = [];
    foreach ($document->getElementsByTagName('rect') as $rect) {
        if (in_array($rect->getAttribute('fill'), ['#5070DD', '#B6D634'], true)
            && (float) $rect->getAttribute('y') >= 80) {
            $xs[] = $rect->getAttribute('x');
        }
    }

    return $xs;
};
assertGatewayGauge(
    is_string($groupedSvg) && is_string($stackedSvg)
        && count($barXs($groupedSvg)) === 2 && count($barXs($stackedSvg)) === 2
        && $barXs($groupedSvg)[0] !== $barXs($groupedSvg)[1]
        && $barXs($stackedSvg)[0] === $barXs($stackedSvg)[1],
    'Category live preview must distinguish grouped from stacked bar geometry.'
);
$categoryPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources'                  => $categorySources,
    'BarFillMode'              => 'svg',
    'BarSVGSizePercent'        => 50,
    'IPSViewUseTileDesign'     => false,
    'IPSViewBarFillMode'       => 'svg',
    'IPSViewBarSVGSizePercent' => 200
], JSON_THROW_ON_ERROR));
$categoryPatternUpdates = array_slice($categoryPreview->GetTestFormUpdates(), -2);
$categoryPatternTileSvg = base64_decode(substr(
    $categoryPatternUpdates[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
$categoryPatternIPSViewSvg = base64_decode(substr(
    $categoryPatternUpdates[1]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($categoryPatternTileSvg) && is_string($categoryPatternIPSViewSvg)
        && str_contains($categoryPatternTileSvg, 'pattern id="bar-category-preview-0" width="4" height="4"')
        && str_contains($categoryPatternIPSViewSvg, 'pattern id="bar-category-preview-0" width="14" height="14"'),
    'Category Bar previews must preserve independent SVG pattern sizing.'
);
$polarPreview = new EChartsBarPolar();
$polarPreview->Create();
$polarSources = [
    ['VariableID' => 4711, 'Label' => 'Außen'],
    ['VariableID' => 4717, 'Label' => 'Innen']
];
$polarPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources' => $polarSources, 'PolarMode' => 'radial'
], JSON_THROW_ON_ERROR));
$radialSvg = base64_decode(substr(
    array_slice($polarPreview->GetTestFormUpdates(), -2)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
$polarPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources' => $polarSources, 'PolarMode' => 'tangential', 'AngularSpan' => 180
], JSON_THROW_ON_ERROR));
$tangentialSvg = base64_decode(substr(
    array_slice($polarPreview->GetTestFormUpdates(), -2)[0]['Value'],
    strlen('data:image/svg+xml;base64,')
), true);
assertGatewayGauge(
    is_string($radialSvg) && is_string($tangentialSvg)
        && str_contains($radialSvg, '<path') && str_contains($tangentialSvg, '<path')
        && $radialSvg !== $tangentialSvg,
    'Polar live preview must draw different radial and tangential geometry.'
);
$polarPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources'               => $polarSources,
    'ShowGrid'              => true,
    'AngularSpan'           => 180,
    'Clockwise'             => true,
    'IPSViewUseTileDesign'  => false,
    'IPSViewShowGrid'       => true,
    'IPSViewAngularSpan'    => 270,
    'IPSViewClockwise'      => false
], JSON_THROW_ON_ERROR));
$polarGridUpdates = array_slice($polarPreview->GetTestFormUpdates(), -2);
$polarGridGeometry = static function (string $uri): array
{
    $svg = base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(is_string($svg), 'Polar grid preview must produce an SVG image.');
    $document = new DOMDocument();
    assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Polar grid preview SVG must be valid XML.');
    $arcs = [];
    $bars = [];
    foreach ($document->getElementsByTagName('path') as $path) {
        if ($path->hasAttribute('data-preview-grid-arc')) {
            $arcs[] = $path->getAttribute('d');
        } elseif ($path->hasAttribute('fill-opacity') && $path->hasAttribute('stroke-width')) {
            $bars[] = $path->getAttribute('d');
        }
    }
    $fullCircles = 0;
    foreach ($document->getElementsByTagName('circle') as $circle) {
        if ($circle->getAttribute('fill') === 'none') {
            ++$fullCircles;
        }
    }

    return [$arcs, $fullCircles, $bars];
};
[$tileGridArcs, $tileFullCircles] = $polarGridGeometry($polarGridUpdates[0]['Value']);
[$ipsViewGridArcs, $ipsViewFullCircles] = $polarGridGeometry($polarGridUpdates[1]['Value']);
assertGatewayGauge(
    count($tileGridArcs) === 4 && $tileFullCircles === 0
        && preg_match('/ A [\d.]+ [\d.]+ 0 0 1 /', $tileGridArcs[0]) === 1
        && count($ipsViewGridArcs) === 4 && $ipsViewFullCircles === 0
        && preg_match('/ A [\d.]+ [\d.]+ 0 1 0 /', $ipsViewGridArcs[0]) === 1,
    'Polar Tile and independent IPSView preview grids must follow their angular spans and directions.'
);
$polarPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources'      => $polarSources,
    'ShowGrid'     => true,
    'AngularSpan'  => 360
], JSON_THROW_ON_ERROR));
[$fullSpanGridArcs, $fullSpanCircles] = $polarGridGeometry(
    array_slice($polarPreview->GetTestFormUpdates(), -2)[0]['Value']
);
assertGatewayGauge(
    $fullSpanGridArcs === [] && $fullSpanCircles === 4,
    'A 360-degree Polar preview must retain complete circular grid rings.'
);
$polarPreview->UpdateBarPreviewFromForm(json_encode([
    'Sources'               => $polarSources,
    'PolarMode'             => 'radial',
    'ShowGrid'              => true,
    'StartAngle'            => 0,
    'AngularSpan'           => 180,
    'Clockwise'             => false,
    'IPSViewUseTileDesign'  => false,
    'IPSViewPolarMode'      => 'tangential',
    'IPSViewShowGrid'       => true,
    'IPSViewStartAngle'     => 90,
    'IPSViewAngularSpan'    => 180,
    'IPSViewClockwise'      => true
], JSON_THROW_ON_ERROR));
$polarAngleUpdates = array_slice($polarPreview->GetTestFormUpdates(), -2);
[$tileAngleArcs, , $tileAngleBars] = $polarGridGeometry($polarAngleUpdates[0]['Value']);
[$ipsViewAngleArcs, , $ipsViewAngleBars] = $polarGridGeometry($polarAngleUpdates[1]['Value']);
$polarPreviewAngleLabels = static function (string $uri): array
{
    $svg = base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(is_string($svg), 'Polar angle guide must produce an SVG image.');
    $document = new DOMDocument();
    assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Polar angle guide SVG must be valid XML.');
    $labels = [];
    foreach ($document->getElementsByTagName('text') as $element) {
        if (!$element->hasAttribute('data-preview-angle')) {
            continue;
        }
        $angle = (int) $element->getAttribute('data-preview-angle');
        $labels[$angle] = [
            'x'    => (float) $element->getAttribute('x'),
            'y'    => (float) $element->getAttribute('y'),
            'text' => $element->textContent
        ];
    }

    return $labels;
};
$tileAngleLabels = $polarPreviewAngleLabels($polarAngleUpdates[0]['Value']);
$ipsViewAngleLabels = $polarPreviewAngleLabels($polarAngleUpdates[1]['Value']);
assertGatewayGauge(
    count($tileAngleLabels) === 4 && ($tileAngleLabels[0]['text'] ?? '') === '0°/360°'
        && $tileAngleLabels[0]['x'] > 350 && abs($tileAngleLabels[0]['y'] - 190) < 5
        && abs($tileAngleLabels[90]['x'] - 350) < 5 && $tileAngleLabels[90]['y'] < 190
        && $tileAngleLabels[180]['x'] < 350 && abs($tileAngleLabels[180]['y'] - 190) < 5
        && abs($tileAngleLabels[270]['x'] - 350) < 5 && $tileAngleLabels[270]['y'] > 190,
    'Counterclockwise Polar preview angle marks must advance from the configured start point.'
);
assertGatewayGauge(
    count($ipsViewAngleLabels) === 4 && ($ipsViewAngleLabels[0]['text'] ?? '') === '0°/360°'
        && abs($ipsViewAngleLabels[0]['x'] - 350) < 5 && $ipsViewAngleLabels[0]['y'] < 190
        && $ipsViewAngleLabels[90]['x'] > 350 && abs($ipsViewAngleLabels[90]['y'] - 190) < 5
        && abs($ipsViewAngleLabels[180]['x'] - 350) < 5 && $ipsViewAngleLabels[180]['y'] > 190
        && $ipsViewAngleLabels[270]['x'] < 350 && abs($ipsViewAngleLabels[270]['y'] - 190) < 5,
    'Clockwise independent IPSView preview angle marks must advance in the opposite direction.'
);
assertGatewayGauge(
    preg_match('/^M ([\d.]+) ([\d.]+) A [\d.]+ [\d.]+ 0 0 0 ([\d.]+) ([\d.]+)$/', $tileAngleArcs[0] ?? '', $tileArc) === 1
        && (float) $tileArc[1] > 350 && abs((float) $tileArc[2] - 190) < 0.02
        && (float) $tileArc[3] < 350 && abs((float) $tileArc[4] - 190) < 0.02
        && preg_match('/^M ([\d.]+) ([\d.]+) A /', $tileAngleBars[0] ?? '', $tileBar) === 1
        && (float) $tileBar[1] > 350 && (float) $tileBar[2] < 190,
    'A zero-degree counterclockwise Tile preview must occupy the upper half like ECharts.'
);
assertGatewayGauge(
    preg_match('/^M ([\d.]+) ([\d.]+) A [\d.]+ [\d.]+ 0 0 1 ([\d.]+) ([\d.]+)$/', $ipsViewAngleArcs[0] ?? '', $ipsViewArc) === 1
        && abs((float) $ipsViewArc[1] - 350) < 0.02 && (float) $ipsViewArc[2] < 190
        && abs((float) $ipsViewArc[3] - 350) < 0.02 && (float) $ipsViewArc[4] > 190
        && preg_match('/^M ([\d.]+) ([\d.]+) A [\d.]+ [\d.]+ 0 0 1 ([\d.]+) ([\d.]+)/', $ipsViewAngleBars[0] ?? '', $ipsViewBar) === 1
        && abs((float) $ipsViewBar[1] - 350) < 0.02 && (float) $ipsViewBar[2] < 190
        && abs((float) $ipsViewBar[3] - 350) < 0.02 && (float) $ipsViewBar[4] > 190,
    'A ninety-degree clockwise independent IPSView preview must occupy the right half like ECharts.'
);

$scatter = new TestableEChartsScatter();
$scatter->Create();
$scatter->SetTestCurrentTimestamp(1780000400);
$scatter->SetTestProperty('Range', '1h');
$scatter->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Role' => 'x', 'Label' => 'Outside', 'UseVariablePresentation' => false,
        'Unit'    => '°C', 'Decimals' => 1, 'Reducer' => 'average'],
    ['VariableID' => 4713, 'Role' => 'y', 'Label' => 'Humidity', 'UseVariablePresentation' => false,
        'Unit'    => '%', 'Decimals' => 0, 'Reducer' => 'sum']
], JSON_THROW_ON_ERROR));
$scatter->ApplyChanges();
$scatterData = json_decode($scatter->GetScatterData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $scatter->GetTestStatus() === IS_ACTIVE
        && $scatter->GetTestReferences() === [4711, 4713]
        && $scatterData['family'] === 'scatter'
        && $scatterData['variant'] === 'scatter'
        && $scatterData['scatter']['style']['valueLabelContent'] === 'y'
        && $scatterData['scatter']['style']['enableZoom'] === true
        && $scatterData['scatter']['style']['xAxisRangeMode'] === 'data'
        && $scatterData['scatter']['style']['yAxisRangeMode'] === 'data'
        && $scatterData['roles']['x']['label'] === 'Outside'
        && $scatterData['roles']['y']['label'] === 'Humidity'
        && count($scatterData['points']) === 1
        && $scatterData['points'][0][0] === 1780000100
        && $scatterData['points'][0][1] === 41.5
        && $scatterData['points'][0][2] === 12.0,
    'Scatter must join the gateway archive only at the same completed interval without interpolation.'
);
$scatter->SetTestProperty('XAxisRangeMode', 'manual');
$scatter->SetTestProperty('XAxisMinimum', -10.5);
$scatter->SetTestProperty('XAxisMaximum', 20.5);
$scatter->SetTestProperty('YAxisRangeMode', 'zero');
$scatter->ApplyChanges();
$scaledScatter = json_decode($scatter->GetScatterData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $scaledScatter['scatter']['style']['xAxisRangeMode'] === 'manual'
        && $scaledScatter['scatter']['style']['xAxisMinimum'] === -10.5
        && $scaledScatter['scatter']['style']['xAxisMaximum'] === 20.5
        && $scaledScatter['scatter']['style']['yAxisRangeMode'] === 'zero',
    'Scatter must expose independent validated X/Y axis ranges to the renderer.'
);
assertGatewayGauge(
    $scaledScatter['scatter']['style']['adaptiveValueLabels'] === false,
    'Adaptive Scatter labels must be disabled by default for existing instances.'
);
$scatter->SetTestProperty('AdaptiveValueLabels', true);
$scatter->ApplyChanges();
assertGatewayGauge(
    json_decode($scatter->GetScatterData(), true, 512, JSON_THROW_ON_ERROR)['scatter']['style']['adaptiveValueLabels'] === true,
    'Scatter must pass the optional adaptive-label setting to its renderer.'
);
$scatter->SetTestProperty('AdaptiveValueLabels', false);
$scatter->ApplyChanges();
$scatter->SetTestProperty('YAxisRangeMode', 'manual');
$scatter->SetTestProperty('YAxisMinimum', 100.0);
$scatter->SetTestProperty('YAxisMaximum', 40.0);
$scatter->ApplyChanges();
assertGatewayGauge($scatter->GetTestStatus() === 202, 'Scatter must reject reversed manual Y bounds.');
$scatter->SetTestProperty('YAxisRangeMode', 'zero');
$scatter->ApplyChanges();
$scatter->SetTestProperty('ValueLabelContent', 'both');
$scatter->ApplyChanges();
assertGatewayGauge(
    json_decode($scatter->GetScatterData(), true, 512, JSON_THROW_ON_ERROR)['scatter']['style']['valueLabelContent'] === 'both',
    'Scatter must pass the configured label content to its renderer.'
);
$scatter->SetTestProperty('ValueLabelContent', 'invalid');
$scatter->ApplyChanges();
assertGatewayGauge($scatter->GetTestStatus() === 202, 'Scatter must reject an unknown label content mode.');
$scatter->SetTestProperty('ValueLabelContent', 'y');
$scatter->ApplyChanges();
$scatter->SetTestProperty('ColorMapEnabled', true);
$scatter->SetTestProperty('ColorMapDimension', 'x');
$scatter->SetTestProperty('ColorMapRangeMode', 'manual');
$scatter->SetTestProperty('ColorMapMinimum', -5.5);
$scatter->SetTestProperty('ColorMapMaximum', 45.5);
$scatter->ApplyChanges();
$scatterColorData = json_decode($scatter->GetScatterData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $scatterColorData['scatter']['style']['colorMapEnabled'] === true
        && $scatterColorData['scatter']['style']['colorMapDimension'] === 'x'
        && $scatterColorData['scatter']['style']['colorMapMinimum'] === -5.5
        && $scatterColorData['scatter']['style']['colorMapMaximum'] === 45.5,
    'Scatter must expose its independent continuous color scale to ECharts.'
);
$scatter->SetTestProperty('ColorMapMaximum', -6.0);
$scatter->ApplyChanges();
assertGatewayGauge($scatter->GetTestStatus() === 202, 'Scatter must reject reversed manual color bounds.');
$scatter->SetTestProperty('ColorMapRangeMode', 'data');
$scatter->SetTestProperty('ColorMapDimension', 'size');
$scatter->ApplyChanges();
assertGatewayGauge($scatter->GetTestStatus() === 202, 'Size-based colors require a bubble source.');
$scatter->SetTestProperty('ColorMapEnabled', false);
$scatter->SetTestProperty('ColorMapDimension', 'y');
$scatter->ApplyChanges();
$scatterOutput = new EChartsScatter();
$scatterOutput->Create();
$scatterForm = json_decode($scatterOutput->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$scatterLocale = json_decode(file_get_contents(__DIR__ . '/../EChartsScatter/locale.json'), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    findGaugeFormElement($scatterForm['elements'], 'Sources')['caption'] === 'Scatter plot (optional bubble size)'
        && $scatterLocale['translations']['de']['Scatter plot (optional bubble size)'] === 'Streudiagramm (optional mit Blasengröße)'
        && in_array('Check scatter plot settings.', array_column($scatterForm['status'], 'caption'), true)
        && $scatterLocale['translations']['de']['Check scatter plot settings.'] === 'Streudiagramm-Einstellungen prüfen.',
    'Scatter must describe its optional bubble variant plainly in the configuration form.'
);
foreach (['ValueLabelContent', 'IPSViewValueLabelContent'] as $field) {
    $selector = findGaugeFormElement($scatterForm['elements'], $field);
    assertGatewayGauge(
        ($selector['type'] ?? null) === 'Select'
            && array_column($selector['options'], 'value') === ['x', 'y', 'both']
            && str_contains((string) ($selector['onChange'] ?? ''), 'UpdateBarPreviewFromForm'),
        'Scatter must offer X, Y and both label values with live previews in Tile and IPSView.'
    );
}
foreach (['AdaptiveValueLabels', 'IPSViewAdaptiveValueLabels'] as $field) {
    $control = findGaugeFormElement($scatterForm['elements'], $field);
    assertGatewayGauge(
        ($control['type'] ?? null) === 'CheckBox'
            && str_contains((string) ($control['onChange'] ?? ''), 'UpdateBarPreviewFromForm'),
        'Scatter must offer optional adaptive labels with live previews in Tile and IPSView.'
    );
}
foreach (['EnableZoom', 'IPSViewEnableZoom'] as $field) {
    $control = findGaugeFormElement($scatterForm['elements'], $field);
    assertGatewayGauge(
        ($control['type'] ?? null) === 'CheckBox'
            && str_contains((string) ($control['onChange'] ?? ''), 'UpdateBarPreviewFromForm'),
        'Scatter must offer X/Y zoom with live previews in Tile and IPSView.'
    );
}
foreach (['EnableBrush', 'IPSViewEnableBrush'] as $field) {
    $control = findGaugeFormElement($scatterForm['elements'], $field);
    assertGatewayGauge(
        ($control['type'] ?? null) === 'CheckBox'
            && str_contains((string) ($control['onChange'] ?? ''), 'UpdateBarPreviewFromForm'),
        'Scatter must offer optional area selection with live previews in Tile and IPSView.'
    );
}
foreach (['ColorMapEnabled', 'IPSViewColorMapEnabled'] as $field) {
    $control = findGaugeFormElement($scatterForm['elements'], $field);
    assertGatewayGauge(
        ($control['type'] ?? null) === 'CheckBox'
            && str_contains((string) ($control['onChange'] ?? ''), 'UpdateBarPreviewFromForm'),
        'Scatter must offer value-based colors with live previews in both designers.'
    );
}
foreach (['ColorMapRangeMode', 'IPSViewColorMapRangeMode'] as $field) {
    $control = findGaugeFormElement($scatterForm['elements'], $field);
    assertGatewayGauge(
        ($control['type'] ?? null) === 'Select'
            && str_contains((string) ($control['onChange'] ?? ''), 'UpdateColorMapRangeForm')
            && str_contains((string) ($control['onChange'] ?? ''), 'UpdateBarPreviewFromForm'),
        'Both Scatter color ranges must update their bounds and previews.'
    );
}
foreach (['X', 'Y'] as $axis) {
    $tileMode = findGaugeFormElement($scatterForm['elements'], $axis . 'AxisRangeMode');
    $ipsViewMode = findGaugeFormElement($scatterForm['elements'], 'IPSView' . $axis . 'AxisRangeMode');
    assertGatewayGauge(
        $tileMode !== null
            && findGaugeFormElement($scatterForm['elements'], $axis . 'AxisMinimum')['visible'] === false
            && $ipsViewMode !== null
            && findGaugeFormElement($scatterForm['elements'], 'IPSView' . $axis . 'AxisMinimum')['visible'] === false
            && str_contains((string) ($tileMode['onChange'] ?? ''), 'UpdateAxisRangeForm')
            && str_contains((string) ($tileMode['onChange'] ?? ''), 'UpdateBarPreviewFromForm')
            && str_contains((string) ($ipsViewMode['onChange'] ?? ''), 'UpdateAxisRangeForm')
            && str_contains((string) ($ipsViewMode['onChange'] ?? ''), 'UpdateBarPreviewFromForm'),
        'Scatter axis selectors must update bounds and previews in both designers.'
    );
}
$scatterOutput->UpdateAxisRangeForm(false, 'manual', 'data');
assertGatewayGauge(
    array_slice($scatterOutput->GetTestFormUpdates(), -4) === [
        ['Field' => 'XAxisMinimum', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'XAxisMaximum', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'YAxisMinimum', 'Parameter' => 'visible', 'Value' => false],
        ['Field' => 'YAxisMaximum', 'Parameter' => 'visible', 'Value' => false]
    ],
    'Scatter must reveal only the selected manual axis bounds while editing the form.'
);
$scatterOutput->UpdateColorMapRangeForm(true, 'manual');
assertGatewayGauge(
    array_slice($scatterOutput->GetTestFormUpdates(), -2) === [
        ['Field' => 'IPSViewColorMapMinimum', 'Parameter' => 'visible', 'Value' => true],
        ['Field' => 'IPSViewColorMapMaximum', 'Parameter' => 'visible', 'Value' => true]
    ],
    'Independent IPSView color bounds must be revealed on demand.'
);
$scatterOutput->UpdateBarPreviewFromForm(json_encode([
    'Sources' => [
        ['VariableID' => 4711, 'Role' => 'x', 'Label' => 'Outside', 'Unit' => '°C', 'UseVariablePresentation' => false],
        ['VariableID' => 4713, 'Role' => 'y', 'Label' => 'Humidity', 'Unit' => '%', 'UseVariablePresentation' => false]
    ],
    'ShowValues'           => true,
    'ColorMapEnabled'      => true, 'ColorMapShowLegend' => true,
    'ColorMapLowColor'     => 0x112233, 'ColorMapHighColor' => 0xDDEEFF,
    'ValueLabelContent'    => 'both',
    'XAxisRangeMode'       => 'manual', 'XAxisMinimum' => 0.0, 'XAxisMaximum' => 20.0,
    'YAxisRangeMode'       => 'manual', 'YAxisMinimum' => 40.0, 'YAxisMaximum' => 100.0,
    'IPSViewUseTileDesign' => false, 'IPSViewYAxisRangeMode' => 'zero',
    'IPSViewShowValues'    => true, 'IPSViewValueLabelContent' => 'x'
], JSON_THROW_ON_ERROR));
$scatterPreviewUpdates = array_slice($scatterOutput->GetTestFormUpdates(), -2);
$scatterTileSvg = base64_decode(substr($scatterPreviewUpdates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$scatterIPSViewSvg = base64_decode(substr($scatterPreviewUpdates[1]['Value'], strlen('data:image/svg+xml;base64,')), true);
$scatterSvgDocument = new DOMDocument();
assertGatewayGauge(
    is_string($scatterTileSvg) && is_string($scatterIPSViewSvg)
        && $scatterSvgDocument->loadXML($scatterTileSvg, LIBXML_NONET)
        && $scatterSvgDocument->loadXML($scatterIPSViewSvg, LIBXML_NONET)
        && str_contains($scatterTileSvg, 'rotate(-90 26 178)')
        && str_contains($scatterTileSvg, 'clip-path="url(#scatter-preview-plot)"')
        && str_contains($scatterTileSvg, 'M 105 320 H 670')
        && str_contains($scatterIPSViewSvg, 'M 105 320 H 670')
        && str_contains($scatterTileSvg, '>40</text>')
        && str_contains($scatterIPSViewSvg, '>0</text>')
        && str_contains($scatterTileSvg, '°C / ')
        && str_contains($scatterIPSViewSvg, '°C</text>')
        && !str_contains($scatterIPSViewSvg, '°C / ')
        && str_contains($scatterTileSvg, '%</text>')
        && str_contains($scatterTileSvg, 'scatter-preview-color-map')
        && str_contains($scatterTileSvg, 'x="250" y="44" width="220" height="8"')
        && !str_contains($scatterIPSViewSvg, 'scatter-preview-color-map'),
    'Scatter previews must keep points inside the plot and reflect independent axis and value-label settings.'
);
$scatterOutput->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Role' => 'x'], ['VariableID' => 4713, 'Role' => 'y']
], JSON_THROW_ON_ERROR));
$scatterOutput->SetTestProperty('IPSViewUseTileDesign', false);
$scatterOutput->SetTestProperty('ValueLabelContent', 'both');
$scatterOutput->SetTestProperty('IPSViewValueLabelContent', 'x');
$scatterOutput->SetTestProperty('AdaptiveValueLabels', true);
$scatterOutput->SetTestProperty('IPSViewAdaptiveValueLabels', false);
$scatterOutput->SetTestProperty('IPSViewYAxisRangeMode', 'manual');
$scatterOutput->SetTestProperty('IPSViewYAxisMinimum', 40.5);
$scatterOutput->SetTestProperty('IPSViewYAxisMaximum', 99.5);
$scatterOutput->SetTestProperty('IPSViewEnableZoom', false);
assertGatewayGauge(
    str_contains($scatterOutput->GetIPSViewHTML(), '"yAxisRangeMode":"manual"')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"valueLabelContent":"x"')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"yAxisMinimum":40.5')
        && str_contains($scatterOutput->GetVisualizationTile(), '"valueLabelContent":"both"')
        && str_contains($scatterOutput->GetVisualizationTile(), '"adaptiveValueLabels":true')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"adaptiveValueLabels":false')
        && str_contains($scatterOutput->GetVisualizationTile(), '"enableZoom":true')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"enableZoom":false')
        && str_contains($scatterOutput->GetVisualizationTile(), '"yAxisRangeMode":"data"'),
    'Scatter must use independent IPSView scale settings without changing the Tile scale.'
);
$scatterOutput->SetTestProperty('XAxisRangeMode', 'manual');
$scatterOutput->SetTestProperty('XAxisMinimum', -5.25);
$scatterOutput->SetTestProperty('XAxisMaximum', 25.75);
$scatterOutput->SetTestProperty('ColorMapEnabled', true);
$scatterOutput->SetTestProperty('ColorMapRangeMode', 'manual');
$scatterOutput->SetTestProperty('ColorMapMinimum', 0.5);
$scatterOutput->SetTestProperty('ColorMapMaximum', 99.5);
$scatterOutput->SetTestProperty('EnableBrush', true);
$GLOBALS['symconTestCopyTarget'] = $scatterOutput;
try {
    $scatterOutput->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(
    str_contains($scatterOutput->GetIPSViewHTML(), '"xAxisRangeMode":"manual"')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"valueLabelContent":"both"')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"adaptiveValueLabels":true')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"xAxisMinimum":-5.25')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"xAxisMaximum":25.75')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"colorMapEnabled":true')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"enableBrush":true')
        && str_contains($scatterOutput->GetIPSViewHTML(), '"colorMapMinimum":0.5'),
    'Scatter must copy floating axis and color bounds to the independent IPSView design.'
);
assertGatewayGauge(
    str_contains($scatterOutput->GetVisualizationTile(), 'echarts-scatter-chart')
        && str_contains($scatterOutput->GetVisualizationTile(), 'cartesianOptions'),
    'Scatter must render the native Tile output.'
);
assertGatewayGauge(
    str_contains($scatterOutput->GetIPSViewHTML(), 'echarts-scatter-chart')
        && str_contains($scatterOutput->GetIPSViewHTML(), 'attachIPSViewWheelCartesian'),
    'Scatter must render the IPSView output.'
);
assertGatewayGauge(
    strlen($scatterOutput->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($scatterOutput->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Scatter Tile and IPSView payloads must stay below the Symcon HTML output limit.'
);
assertTransientRetry($scatter, 'Scatter');
$scatter->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Role' => 'x'], ['VariableID' => 4711, 'Role' => 'y']
], JSON_THROW_ON_ERROR));
$scatter->ApplyChanges();
assertGatewayGauge($scatter->GetTestStatus() === 201, 'Scatter must reject reused variable IDs between roles.');

foreach ([
    EChartsGaugeSingle::class      => ['GaugePreview', 'IPSViewGaugePreview'],
    EChartsGaugeMulti::class       => ['GaugePreview', 'IPSViewGaugePreview'],
    EChartsGaugeTacho::class       => ['GaugePreview', 'IPSViewGaugePreview'],
    EChartsGaugeChronograph::class => ['GaugePreview', 'IPSViewGaugePreview'],
    EChartsTimeSeries::class       => ['TimeSeriesPreview', 'IPSViewTimeSeriesPreview'],
    EChartsBarCategory::class      => ['BarPreview', 'IPSViewBarPreview'],
    EChartsBarHistory::class       => ['BarPreview', 'IPSViewBarPreview'],
    EChartsBarWaterfall::class     => ['BarPreview', 'IPSViewBarPreview'],
    EChartsBarPolar::class         => ['BarPreview', 'IPSViewBarPreview'],
    EChartsPie::class              => ['BarPreview', 'IPSViewBarPreview'],
    EChartsTreemap::class          => ['BarPreview', 'IPSViewBarPreview'],
    EChartsSunburst::class         => ['BarPreview', 'IPSViewBarPreview'],
    EChartsParallel::class         => ['BarPreview', 'IPSViewBarPreview'],
    EChartsScatter::class          => ['BarPreview', 'IPSViewBarPreview'],
    EChartsHeatmap::class          => ['BarPreview', 'IPSViewBarPreview'],
    EChartsTree::class             => ['BarPreview', 'IPSViewBarPreview'],
    EChartsTreeSystemHealth::class => ['BarPreview', 'IPSViewBarPreview'],
    EChartsGraph::class            => ['BarPreview', 'IPSViewBarPreview']
] as $chartClass => $previewFields) {
    $chart = new $chartClass();
    $chart->Create();
    $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    foreach ($previewFields as $field) {
        $image = findGaugeFormElement($form['elements'], $field);
        assertGatewayGauge(
            ($image['type'] ?? null) === 'Image'
                && str_starts_with((string) ($image['image'] ?? ''), 'data:image/svg+xml;base64,'),
            $chartClass . ' must expose an initialized ' . $field . ' preview.'
        );
    }
    $labelFields = match ($chartClass) {
        EChartsPie::class => ['OutsideLabelAlignment', 'LeaderLineLength', 'LeaderLineLength2',
            'LabelLineGap', 'LabelEdgeDistance'],
        EChartsBarCategory::class, EChartsBarHistory::class, EChartsBarWaterfall::class => ['ValueLabelPosition', 'ValueLabelDistance'],
        EChartsBarPolar::class                                                          => ['ValueLabelPosition', 'ValueLabelDistance'],
        EChartsTimeSeries::class                                                        => ['ShowValues', 'ValueLabelPosition', 'ValueLabelDistance',
            'ValueFontSizePercent', 'ValueColor'],
        EChartsGaugeMulti::class, EChartsGaugeTacho::class, EChartsGaugeChronograph::class => ['ScaleLabelDistanceOffset', 'TitleOffsetXPercent', 'TitleOffsetYPercent',
            'ValueOffsetXPercent', 'ValueOffsetYPercent'],
        default => []
    };
    foreach ($labelFields as $field) {
        assertGatewayGauge(
            findGaugeFormElement($form['elements'], $field) !== null
                && findGaugeFormElement($form['elements'], 'IPSView' . $field) !== null,
            $chartClass . ' must offer ' . $field . ' in Tile and IPSView designers.'
        );
    }
}

$treemap = new EChartsTreemap();
$treemap->Create();
$treemap->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Group' => 'House', 'Subgroup' => 'Climate', 'Label' => 'Room'],
    ['VariableID' => 4717, 'Group' => 'House', 'Subgroup' => 'Climate', 'Label' => ''],
    ['VariableID' => 4720, 'Group' => 'Garden', 'Subgroup' => '', 'Label' => 'Outside']
], JSON_THROW_ON_ERROR));
$treemap->SetTestProperty('Title', 'Grouped current values');
$treemap->SetTestProperty('IPSViewUseTileDesign', false);
$treemap->SetTestProperty('IPSViewShowLabels', false);
$treemap->SetTestProperty('IPSViewGroupColors', json_encode([
    ['Group' => 'Garden', 'Color' => 0xFEDCBA]
], JSON_THROW_ON_ERROR));
$treemap->SetTestProperty('GroupColors', json_encode([
    ['Group' => 'House', 'Color' => 0x123456], ['Group' => 'Garden', 'Color' => -1]
], JSON_THROW_ON_ERROR));
$treemap->SetTestProperty('ShowValues', true);
$treemap->SetTestProperty('GroupLabelFontSizePercent', 150);
$treemap->SetTestProperty('SubgroupLabelColor', 0xABCDEF);
$treemap->SetTestProperty('FrameColor', 0x123456);
$treemap->SetTestProperty('GroupGapWidth', 8);
$treemap->SetTestProperty('Level1BorderWidth', 7);
$treemap->SetTestProperty('Level2GapWidth', 6);
$treemap->SetTestProperty('AllowPanZoom', false);
$treemap->SetTestProperty('ShowBreadcrumb', false);
$treemap->SetTestProperty('IPSViewClickToZoom', false);
$treemap->ApplyChanges();
$treemapData = json_decode($treemap->GetTreemapData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $treemap->GetTestStatus() === IS_ACTIVE
        && $treemapData['family'] === 'treemap'
        && count($treemapData['treemap']['data']) === 2
        && $treemapData['treemap']['data'][0]['children'][0]['children'][0]['id'] === 'variable-4711'
        && $treemapData['treemap']['data'][0]['children'][0]['children'][1]['id'] === 'variable-4717'
        && $treemapData['treemap']['data'][1]['children'][0]['id'] === 'variable-4720'
        && $treemapData['treemap']['style']['groupColors']['House'] === '#123456'
        && $treemapData['treemap']['style']['showValues'] === true
        && $treemapData['treemap']['style']['groupLabelFontSizePercent'] === 150
        && $treemapData['treemap']['style']['subgroupLabelColor'] === '#ABCDEF'
        && $treemapData['treemap']['style']['frameColor'] === '#123456'
        && $treemapData['treemap']['style']['groupGapWidth'] === 8
        && $treemapData['treemap']['style']['level1BorderWidth'] === 7
        && $treemapData['treemap']['style']['level2GapWidth'] === 6
        && $treemapData['treemap']['style']['allowPanZoom'] === false
        && $treemapData['treemap']['style']['showBreadcrumb'] === false,
    'Treemap must group distinct current variables without changing their IDs.'
);
$treemapTile = $treemap->GetVisualizationTile();
$treemapIPSView = $treemap->GetIPSViewHTML();
$treemapForm = json_decode($treemap->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    str_contains($treemapTile, 'echarts-treemap-root')
        && str_contains($treemapTile, '"showLabels":true')
        && str_contains($treemapIPSView, '"showLabels":false')
        && str_contains($treemapIPSView, '#FEDCBA')
        && str_contains($treemapIPSView, '"clickToZoom":false')
        && !str_contains($treemapTile, '#FEDCBA')
        && strlen($treemapTile) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($treemapIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT
        && findGaugeFormElement($treemapForm['elements'], 'BarPreview')['type'] === 'Image'
        && findGaugeFormElement($treemapForm['elements'], 'IPSViewBarPreview')['type'] === 'Image'
        && findGaugeFormElement($treemapForm['elements'], 'GroupColors')['type'] === 'List'
        && str_contains(findGaugeFormElement($treemapForm['elements'], 'GroupColors')['onAdd'], 'ECTM_UpdateBarPreviewFromForm')
        && findGaugeFormElement($treemapForm['elements'], 'IPSViewGroupColors')['type'] === 'List'
        && findGaugeFormElement($treemapForm['elements'], 'Level1BorderWidth')['type'] === 'NumberSpinner'
        && findGaugeFormElement($treemapForm['elements'], 'GroupGapWidth')['type'] === 'NumberSpinner'
        && findGaugeFormElement($treemapForm['elements'], 'IPSViewLevel1BorderWidth')['type'] === 'NumberSpinner'
        && findGaugeFormElement($treemapForm['elements'], 'ShowBreadcrumb')['type'] === 'CheckBox'
        && findGaugeFormElement($treemapForm['elements'], 'IPSViewShowBreadcrumb')['type'] === 'CheckBox',
    'Treemap must render separate Tile and IPSView designs below Symcon output limit.'
);
$updatesBefore = count($treemap->GetTestVisualizationUpdates());
$treemap->MessageSink(1780000300, 4711, VM_UPDATE, []);
assertGatewayGauge(
    count($treemap->GetTestVisualizationUpdates()) > $updatesBefore,
    'Treemap must publish a fresh state when a selected variable changes.'
);
$treemap->UpdateBarPreviewFromForm(json_encode([
    'Sources'     => [['VariableID' => 4711, 'Group' => '<Room>', 'Subgroup' => '', 'Label' => '']],
    'GroupColors' => [['Group' => '<Room>', 'Color' => 0xBADA55]]
], JSON_THROW_ON_ERROR));
$previewUpdate = array_slice($treemap->GetTestFormUpdates(), -2)[0]['Value'];
$previewSvg = base64_decode(substr($previewUpdate, strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($previewSvg) && str_contains($previewSvg, '&lt;Room&gt;')
        && str_contains($previewSvg, '#BADA55')
        && str_contains($previewSvg, 'stroke="#123456" stroke-width="7"')
        && !str_contains($previewSvg, 'Navigation path')
        && !str_contains($previewSvg, '<Room>'),
    'Treemap draft preview must escape group names.'
);
$originalTreemapValue = $GLOBALS['symconTestVariables'][4717]['Value'];
$GLOBALS['symconTestVariables'][4717]['Value'] = -1.0;
assertGatewayGauge(
    str_contains($treemap->GetVisualizationTile(), 'Treemap values must be non-negative.')
        && $treemap->GetTestStatus() === 205,
    'Treemap must reject negative values.'
);
$GLOBALS['symconTestVariables'][4717]['Value'] = $originalTreemapValue;
$treemap->SetTestProperty('Level1BorderWidth', 13);
$treemap->ApplyChanges();
assertGatewayGauge($treemap->GetTestStatus() === 202, 'Treemap must reject excessive frame widths.');
$treemap->SetTestProperty('Level1BorderWidth', 7);
$treemap->SetTestProperty('GroupColors', json_encode([
    ['Group' => 'House', 'Color' => 0x123456], ['Group' => 'House', 'Color' => 0x654321]
], JSON_THROW_ON_ERROR));
$treemap->ApplyChanges();
assertGatewayGauge($treemap->GetTestStatus() === 202, 'Treemap must reject duplicate fixed group colors.');
$treemap->SetTestProperty('GroupColors', '[]');
$treemap->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Group' => 'House'], ['VariableID' => 4711, 'Group' => 'Garden']
], JSON_THROW_ON_ERROR));
$treemap->ApplyChanges();
assertGatewayGauge($treemap->GetTestStatus() === 201, 'Treemap must reject duplicate variable IDs.');
$manyTreemapSources = [];
for ($index = 0; $index < 100; ++$index) {
    $id = 85000 + $index;
    $GLOBALS['symconTestVariables'][$id] = [
        'VariableType' => 2, 'VariableUpdated' => 1780000300,
        'Value'        => (float) ($index + 1), 'Name' => 'Current value ' . $index
    ];
    $manyTreemapSources[] = [
        'VariableID' => $id, 'Group' => 'Group ' . intdiv($index, 10),
        'Subgroup'   => 'Subgroup ' . intdiv($index % 10, 5), 'Label' => ''
    ];
}
try {
    $manyTreemap = new EChartsTreemap();
    $manyTreemap->Create();
    $manyTreemap->SetTestProperty('Sources', json_encode($manyTreemapSources, JSON_THROW_ON_ERROR));
    $manyTreemap->ApplyChanges();
    assertGatewayGauge(
        $manyTreemap->GetTestStatus() === IS_ACTIVE
            && strlen($manyTreemap->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
            && strlen($manyTreemap->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT,
        'The maximum 100-source Treemap must remain below the Symcon output limit.'
    );
    $manyTreemapSources[] = ['VariableID' => 4711, 'Group' => 'Extra'];
    $manyTreemap->SetTestProperty('Sources', json_encode($manyTreemapSources, JSON_THROW_ON_ERROR));
    $manyTreemap->ApplyChanges();
    assertGatewayGauge($manyTreemap->GetTestStatus() === 201, 'Treemap must reject more than 100 variables.');
} finally {
    for ($index = 0; $index < 100; ++$index) {
        unset($GLOBALS['symconTestVariables'][85000 + $index]);
    }
}

require __DIR__ . '/parallel_custom_ranges.php';
$parallel = new TestableEChartsParallel();
$parallel->Create();
$parallel->SetTestCurrentTimestamp(1780004000);
$parallel->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Label' => 'Outside'],
    ['VariableID' => 4713, 'Label' => 'Humidity']
], JSON_THROW_ON_ERROR));
$parallel->SetTestProperty('IPSViewUseTileDesign', false);
$parallel->SetTestProperty('IPSViewLineColor', 0x123456);
$parallel->ApplyChanges();
$parallelData = json_decode($parallel->GetParallelData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $parallel->GetTestStatus() === IS_ACTIVE
        && $parallel->GetTestReferences() === [4711, 4713]
        && $parallelData['family'] === 'parallel'
        && $parallelData['parallel']['aggregation'] === 'hour'
        && $parallelData['parallel']['axes'][0]['label'] === 'Outside'
        && $parallelData['parallel']['axes'][1]['label'] === 'Humidity'
        && count($parallelData['parallel']['rows']) === 1
        && $parallelData['parallel']['rows'][0]['values'] === [41.5, 12.0],
    'Parallel must join archived values by completed common hourly intervals.'
);
$parallelFormModule = new EChartsParallel();
$parallelFormModule->Create();
$parallelFormModule->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Label' => 'Outside'],
    ['VariableID' => 4713, 'Label' => 'Humidity']
], JSON_THROW_ON_ERROR));
$parallelFormModule->SetTestProperty('IPSViewUseTileDesign', false);
$parallelFormModule->SetTestProperty('IPSViewLineColor', 0x123456);
$parallelFormModule->SetTestProperty('LineType', 'dashed');
$parallelFormModule->SetTestProperty('IPSViewLineType', 'dotted');
$parallelFormModule->SetTestProperty('IPSViewInactiveLineOpacityPercent', 12);
$parallelFormModule->SetTestProperty('IPSViewAxisLineWidth', 3);
$parallelFormModule->SetTestProperty('IPSViewShowAxisLabels', false);
$parallelFormModule->SetTestProperty('IPSViewShowAxisTicks', false);
$parallelFormModule->SetTestProperty('IPSViewAxisNameGap', 28);
$parallelFormModule->SetTestProperty('IPSViewAnimationEnabled', false);
$parallelFormModule->ApplyChanges();
$parallelTile = $parallelFormModule->GetVisualizationTile();
$parallelIPSView = $parallelFormModule->GetIPSViewHTML();
$parallelForm = json_decode($parallelFormModule->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    str_contains($parallelTile, 'echarts-parallel-root')
        && str_contains($parallelIPSView, '"lineColor":"#123456"')
        && strlen($parallelTile) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($parallelIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT
        && findGaugeFormElement($parallelForm['elements'], 'Aggregation')['type'] === 'Select'
        && findGaugeFormElement($parallelForm['elements'], 'IPSViewLineColor')['type'] === 'SelectColor',
    'Parallel must expose hourly/daily form controls and bounded Tile/IPSView output.'
);
assertGatewayGauge(
    str_contains($parallelTile, '"lineType":"dashed"')
        && str_contains($parallelIPSView, '"lineType":"dotted"')
        && str_contains($parallelIPSView, '"inactiveLineOpacityPercent":12')
        && str_contains($parallelIPSView, '"axisLineWidth":3')
        && str_contains($parallelIPSView, '"showAxisLabels":false')
        && str_contains($parallelIPSView, '"showAxisTicks":false')
        && str_contains($parallelIPSView, '"axisNameGap":28')
        && str_contains($parallelIPSView, '"animationEnabled":false')
        && findGaugeFormElement($parallelForm['elements'], 'LineType')['type'] === 'Select'
        && findGaugeFormElement($parallelForm['elements'], 'IPSViewAxisNameGap')['type'] === 'NumberSpinner'
        && findGaugeFormElement($parallelForm['elements'], 'IPSViewAnimationEnabled')['type'] === 'CheckBox',
    'Parallel must expose separate Tile and IPSView line, axis and animation designs.'
);
$parallelTilePreview = findGaugeFormElement($parallelForm['elements'], 'BarPreview');
$parallelIPSViewPreview = findGaugeFormElement($parallelForm['elements'], 'IPSViewBarPreview');
$parallelTilePreviewSVG = base64_decode(substr((string) ($parallelTilePreview['image'] ?? ''), strlen('data:image/svg+xml;base64,')), true);
$parallelIPSViewPreviewSVG = base64_decode(substr((string) ($parallelIPSViewPreview['image'] ?? ''), strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($parallelTilePreviewSVG)
        && str_contains($parallelTilePreviewSVG, 'stroke-dasharray="8 4"')
        && is_string($parallelIPSViewPreviewSVG)
        && str_contains($parallelIPSViewPreviewSVG, 'stroke-dasharray="2 4"')
        && !str_contains($parallelIPSViewPreviewSVG, '>100</text>'),
    'Parallel previews must reflect separate line types and axis-label visibility.'
);
$parallelFormModule->SetTestProperty('AnimationEnabled', false);
$parallelFormModule->SetTestProperty('AnimationDuration', 700);
$GLOBALS['symconTestCopyTarget'] = $parallelFormModule;
try {
    $parallelFormModule->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
$parallelCopiedIPSView = $parallelFormModule->GetIPSViewHTML();
assertGatewayGauge(
    $parallelFormModule->GetTestStatus() === IS_ACTIVE
        && str_contains($parallelCopiedIPSView, '"lineType":"dashed"')
        && str_contains($parallelCopiedIPSView, '"animationEnabled":false')
        && str_contains($parallelCopiedIPSView, '"animationDuration":700'),
    'Parallel must copy line and animation settings from Tile to IPSView.'
);
$parallel->SetTestProperty('Aggregation', 'day');
$parallel->ApplyChanges();
assertGatewayGauge(
    json_decode($parallel->GetParallelData(), true, 512, JSON_THROW_ON_ERROR)['parallel']['aggregation'] === 'day',
    'Parallel must support daily archive intervals.'
);
$parallel->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711], ['VariableID' => 4711]
], JSON_THROW_ON_ERROR));
$parallel->ApplyChanges();
assertGatewayGauge($parallel->GetTestStatus() === 201, 'Parallel must reject duplicate variable IDs.');
$parallelFormModule->SetTestProperty('AxisNameGap', 61);
$parallelFormModule->ApplyChanges();
assertGatewayGauge($parallelFormModule->GetTestStatus() === 202, 'Parallel must reject an out-of-range axis title distance.');

$sunburst = new EChartsSunburst();
$sunburst->Create();
$sunburst->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Group' => 'House', 'Subgroup' => 'Climate', 'Label' => 'Room'],
    ['VariableID' => 4717, 'Group' => 'House', 'Subgroup' => 'Climate', 'Label' => ''],
    ['VariableID' => 4720, 'Group' => 'Garden', 'Subgroup' => '', 'Label' => 'Outside']
], JSON_THROW_ON_ERROR));
$sunburst->SetTestProperty('IPSViewUseTileDesign', false);
$sunburst->SetTestProperty('IPSViewShowLabels', false);
$sunburst->SetTestProperty('GroupColors', json_encode([['Group' => 'House', 'Color' => 0x123456]], JSON_THROW_ON_ERROR));
$sunburst->SetTestProperty('InnerRadiusPercent', 15);
$sunburst->SetTestProperty('OuterRadiusPercent', 90);
$sunburst->SetTestProperty('ClickToRoot', false);
$sunburst->SetTestProperty('StartAngle', 210);
$sunburst->SetTestProperty('SortMode', 'desc');
$sunburst->SetTestProperty('LabelMinAngle', 8);
$sunburst->SetTestProperty('IPSViewShowMiddleRingLabels', false);
$sunburst->ApplyChanges();
$sunburstData = json_decode($sunburst->GetSunburstData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $sunburst->GetTestStatus() === IS_ACTIVE
        && $sunburstData['family'] === 'sunburst'
        && count($sunburstData['sunburst']['data']) === 2
        && $sunburstData['sunburst']['data'][0]['children'][0]['children'][0]['id'] === 'variable-4711'
        && $sunburstData['sunburst']['style']['groupColors']['House'] === '#123456'
        && $sunburstData['sunburst']['style']['innerRadiusPercent'] === 15
        && $sunburstData['sunburst']['style']['startAngle'] === 210
        && $sunburstData['sunburst']['style']['sortMode'] === 'desc'
        && $sunburstData['sunburst']['style']['labelMinAngle'] === 8
        && $sunburstData['sunburst']['style']['clickToRoot'] === false,
    'Sunburst must retain current-value hierarchy and independent design options.'
);
$sunburstTile = $sunburst->GetVisualizationTile();
$sunburstIPSView = $sunburst->GetIPSViewHTML();
$sunburstForm = json_decode($sunburst->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    str_contains($sunburstTile, 'echarts-sunburst-root')
        && str_contains($sunburstTile, '"showLabels":true')
        && str_contains($sunburstIPSView, '"showLabels":false')
        && str_contains($sunburstIPSView, '"showMiddleRingLabels":false')
        && strlen($sunburstTile) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($sunburstIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT
        && findGaugeFormElement($sunburstForm['elements'], 'InnerRadiusPercent')['type'] === 'NumberSpinner'
        && findGaugeFormElement($sunburstForm['elements'], 'SortMode')['type'] === 'Select'
        && findGaugeFormElement($sunburstForm['elements'], 'LabelRotation')['type'] === 'Select'
        && findGaugeFormElement($sunburstForm['elements'], 'IPSViewShowMiddleRingLabels')['type'] === 'CheckBox'
        && findGaugeFormElement($sunburstForm['elements'], 'IPSViewClickToRoot')['type'] === 'CheckBox',
    'Sunburst must render Tile and IPSView below the Symcon output limit.'
);
$sunburstUpdatesBefore = count($sunburst->GetTestVisualizationUpdates());
$sunburst->MessageSink(1780000300, 4711, VM_UPDATE, []);
assertGatewayGauge(
    count($sunburst->GetTestVisualizationUpdates()) > $sunburstUpdatesBefore,
    'Sunburst must refresh on source updates.'
);
$originalSunburstValue = $GLOBALS['symconTestVariables'][4717]['Value'];
$GLOBALS['symconTestVariables'][4717]['Value'] = -1.0;
assertGatewayGauge(
    str_contains($sunburst->GetVisualizationTile(), 'Sunburst values must be non-negative.'),
    'Sunburst must reject negative values.'
);
$GLOBALS['symconTestVariables'][4717]['Value'] = $originalSunburstValue;
$sunburst->SetTestProperty('InnerRadiusPercent', 55);
$sunburst->ApplyChanges();
assertGatewayGauge($sunburst->GetTestStatus() === 202, 'Sunburst must reject invalid ring radii.');
$sunburst->SetTestProperty('InnerRadiusPercent', 15);
$sunburst->SetTestProperty('LabelRotation', 'unsupported');
$sunburst->ApplyChanges();
assertGatewayGauge($sunburst->GetTestStatus() === 202, 'Sunburst must reject an unsupported label rotation.');
$sunburst->SetTestProperty('LabelRotation', 'radial');
$sunburst->SetTestProperty('LabelMinAngle', 46);
$sunburst->ApplyChanges();
assertGatewayGauge($sunburst->GetTestStatus() === 202, 'Sunburst must reject an excessive label threshold.');
$sunburst->SetTestProperty('LabelMinAngle', 8);
$sunburst->SetTestProperty('SortMode', 'other');
$sunburst->ApplyChanges();
assertGatewayGauge($sunburst->GetTestStatus() === 202, 'Sunburst must reject an unsupported sort mode.');
$sunburst->SetTestProperty('SortMode', 'desc');
$sunburst->SetTestProperty('InnerRingLabelFontSizePercent', 30);
$sunburst->ApplyChanges();
assertGatewayGauge($sunburst->GetTestStatus() === 202, 'Sunburst must reject an invalid ring label size.');
$sunburst->SetTestProperty('InnerRingLabelFontSizePercent', 0);
$sunburst->SetTestProperty('StartAngle', 361);
$sunburst->ApplyChanges();
assertGatewayGauge($sunburst->GetTestStatus() === 202, 'Sunburst must reject an invalid start angle.');
$sunburst->SetTestProperty('StartAngle', 210);
$sunburst->SetTestProperty('BorderRadius', 21);
$sunburst->ApplyChanges();
assertGatewayGauge($sunburst->GetTestStatus() === 202, 'Sunburst must reject an invalid segment radius.');
$sunburst->SetTestProperty('BorderRadius', 0);
$sunburst->SetTestProperty('InnerRadiusPercent', 15);
$sunburst->SetTestProperty('Sources', json_encode([
    ['VariableID' => 4711, 'Group' => 'House'], ['VariableID' => 4711, 'Group' => 'Garden']
], JSON_THROW_ON_ERROR));
$sunburst->ApplyChanges();
assertGatewayGauge($sunburst->GetTestStatus() === 201, 'Sunburst must reject duplicate variable IDs.');
$sunburst->UpdateBarPreviewFromForm(json_encode([
    'Sources'     => [['VariableID' => 4711, 'Group' => '<Room>', 'Subgroup' => '', 'Label' => '']],
    'GroupColors' => [['Group' => '<Room>', 'Color' => 0xBADA55]]
], JSON_THROW_ON_ERROR));
$sunburstPreviewUpdate = array_slice($sunburst->GetTestFormUpdates(), -2)[0]['Value'];
$sunburstPreviewSvg = base64_decode(substr($sunburstPreviewUpdate, strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($sunburstPreviewSvg)
        && str_contains($sunburstPreviewSvg, '&lt;Room&gt;')
        && str_contains($sunburstPreviewSvg, '#BADA55')
        && !str_contains($sunburstPreviewSvg, '<Room>'),
    'Sunburst draft preview must escape group names.'
);
$sunburst->UpdateBarPreviewFromForm(json_encode([
    'Sources'             => [['VariableID' => 4711, 'Group' => '<Room>', 'Subgroup' => '', 'Label' => '']],
    'StartAngle'          => 210,
    'Clockwise'           => false,
    'ShowInnerRingLabels' => false,
    'ShowMiddleRingLabels'=> false,
    'ShowOuterRingLabels' => false
], JSON_THROW_ON_ERROR));
$sunburstPreviewUpdate = array_slice($sunburst->GetTestFormUpdates(), -2)[0]['Value'];
$sunburstPreviewSvg = base64_decode(substr($sunburstPreviewUpdate, strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($sunburstPreviewSvg)
        && str_contains($sunburstPreviewSvg, 'rotate(-210 360 195)')
        && !str_contains($sunburstPreviewSvg, '&lt;Room&gt;'),
    'Sunburst draft preview must reflect direction and ring label controls.'
);
$manySunburstSources = [];
for ($index = 0; $index < 100; ++$index) {
    $id = 86000 + $index;
    $GLOBALS['symconTestVariables'][$id] = [
        'VariableType' => 2, 'VariableUpdated' => 1780000300,
        'Value'        => (float) ($index + 1), 'Name' => 'Current value ' . $index
    ];
    $manySunburstSources[] = [
        'VariableID' => $id, 'Group' => 'Group ' . intdiv($index, 10),
        'Subgroup'   => 'Subgroup ' . intdiv($index % 10, 5), 'Label' => ''
    ];
}
try {
    $sunburst->SetTestProperty('Sources', json_encode($manySunburstSources, JSON_THROW_ON_ERROR));
    $sunburst->ApplyChanges();
    assertGatewayGauge(
        $sunburst->GetTestStatus() === IS_ACTIVE
            && strlen($sunburst->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
            && strlen($sunburst->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT,
        'The maximum 100-source Sunburst must remain below the Symcon output limit.'
    );
} finally {
    for ($index = 0; $index < 100; ++$index) {
        unset($GLOBALS['symconTestVariables'][86000 + $index]);
    }
}

$sankey = new EChartsSankey();
$sankey->Create();
$sankeyNodes = [['Key' => 'supply', 'Label' => 'Supply'], ['Key' => 'load', 'Label' => 'Load'],
    ['Key' => 'other', 'Label' => 'Load']];
$sankeySources = [['Source' => 'supply', 'Target' => 'load', 'VariableID' => 4711],
    ['Source' => 'supply', 'Target' => 'other', 'VariableID' => 4713]];
$sankey->SetTestProperty('Nodes', json_encode($sankeyNodes, JSON_THROW_ON_ERROR));
$sankey->SetTestProperty('Sources', json_encode($sankeySources, JSON_THROW_ON_ERROR));
$sankey->SetTestProperty('Title', 'Current power');
$sankey->SetTestProperty('Unit', 'W');
$sankey->SetTestProperty('IPSViewUseTileDesign', false);
$sankey->SetTestProperty('IPSViewOrientation', 'vertical');
$sankey->SetTestProperty('IPSViewEdgeColorMode', 'fixed');
$sankey->SetTestProperty('IPSViewEdgeColor', 0x123456);
$sankeyColors = [['Key' => 'load', 'Color' => 0xFF0000], ['Key' => 'other', 'Color' => 0x00FF00]];
$sankey->SetTestProperty('NodeColors', json_encode($sankeyColors, JSON_THROW_ON_ERROR));
$sankey->SetTestProperty('NodeBorderRadius', 8);
$sankey->SetTestProperty('LabelPosition', 'inside');
$sankey->SetTestProperty('ShowEdgeValues', true);
$sankey->SetTestProperty('IPSViewNodeColors', '[{"Key":"load","Color":255}]');
$sankey->SetTestProperty('IPSViewNodeBorderWidth', 3);
$sankey->SetTestProperty('EnableIPSView', true);
$sankey->ApplyChanges();
$sankeyData = json_decode($sankey->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR);
$sankeyTile = $sankey->GetVisualizationTile();
$sankeyIPSView = $sankey->GetIPSViewHTML();
assertGatewayGauge(
    $sankey->GetTestStatus() === IS_ACTIVE && $sankey->GetTestReferences() === [4711, 4713]
        && $sankeyData['family'] === 'sankey' && $sankeyData['sankey']['unit'] === 'W'
        && $sankeyData['sankey']['positiveCount'] === 2
        && $sankeyData['sankey']['style']['nodeColors'] === ['load' => '#FF0000', 'other' => '#00FF00']
        && $sankeyData['sankey']['style']['nodeBorderRadius'] === 8
        && $sankeyData['sankey']['style']['showEdgeValues'] === true
        && $sankeyData['sankey']['links'][0]['value'] === (float) GetValue(4711)
        && $sankeyData['sankey']['data'][1]['label'] === $sankeyData['sankey']['data'][2]['label']
        && str_contains($sankeyIPSView, '"orientation":"vertical"')
        && str_contains($sankeyIPSView, '"edgeColor":"#123456"')
        && str_contains($sankeyTile, '"orientation":"horizontal"')
        && str_contains($sankeyIPSView, '"nodeColors":{"load":"#0000FF"}')
        && str_contains($sankeyIPSView, '"nodeBorderWidth":3')
        && str_contains($sankeyIPSView, '"nodeBorderRadius":0')
        && strlen($sankeyTile) < SYMCON_OUTPUT_BUFFER_LIMIT && strlen($sankeyIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT
        && is_string($sankey->GetTestVariableValue('IPSViewSankey')) && $sankey->GetTestVariableValue('IPSViewSankey') !== '',
    'Sankey must publish current edge values, distinct node identities, independent designs and bounded WebContent.'
);
$sankeyForm = json_decode($sankey->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$sankeyConnections = findGaugeFormElement($sankeyForm['elements'], 'Sources');
$sankeyColorField = findGaugeFormElement($sankeyForm['elements'], 'NodeColors');
assertGatewayGauge(
    $sankeyConnections['form'][0]['options'][1]['value'] === 'supply'
        && $sankeyColorField['form'][0]['options'][2]['value'] === 'load'
        && findGaugeFormElement($sankeyForm['elements'], 'IPSViewNodeColors')['columns'][0]['edit']['options'][3]['value'] === 'other'
        && !isset($sankeyColorField['onChange']) && str_contains($sankeyColorField['onEdit'], 'foreach ($NodeColors as')
        && findGaugeFormElement($sankeyForm['elements'], 'IPSViewOrientation')['enabled'] === true
        && str_starts_with(findGaugeFormElement($sankeyForm['elements'], 'BarPreview')['image'], 'data:image/svg+xml;base64,')
        && str_starts_with(findGaugeFormElement($sankeyForm['elements'], 'IPSViewBarPreview')['image'], 'data:image/svg+xml;base64,'),
    'Sankey forms must offer configured nodes and separate initialized designer previews.'
);
$sankey->UpdateNodeOptions(json_encode([...$sankeyNodes, ['Key' => 'draft', 'Label' => 'Unsaved']], JSON_THROW_ON_ERROR));
$sankeyUpdates = array_slice($sankey->GetTestFormUpdates(), -2);
assertGatewayGauge(
    $sankeyUpdates[0]['Parameter'] === 'columns'
        && json_decode($sankeyUpdates[1]['Value'], true, 512, JSON_THROW_ON_ERROR)[0]['options'][4]['value'] === 'draft',
    'Sankey connection selectors must include unsaved node changes.'
);
$sankeyColorUpdates = array_slice($sankey->GetTestFormUpdates(), -6, 4);
assertGatewayGauge(
    $sankeyColorUpdates[0]['Field'] === 'NodeColors' && $sankeyColorUpdates[2]['Field'] === 'IPSViewNodeColors'
        && json_decode($sankeyColorUpdates[3]['Value'], true, 512, JSON_THROW_ON_ERROR)[0]['options'][4]['value'] === 'draft',
    'Both node-color selectors must follow unsaved node changes without persisting them.'
);
$sankeyNodeField = findGaugeFormElement($sankeyForm['elements'], 'Nodes');
assertGatewayGauge(
    !isset($sankeyNodeField['onChange']) && str_contains($sankeyNodeField['onEdit'], 'ECSA_UpdateBarPreviewFromForm'),
    'Sankey must use supported List edit events for its node selectors.'
);
$sankey->UpdateBarPreviewFromForm(json_encode(['Nodes' => $sankeyNodes,
    'Sources'                                          => [$sankeySources[0], $sankeySources[0]]], JSON_THROW_ON_ERROR));
$sankeyDraftUpdates = $sankey->GetTestFormUpdates();
$sankeyDraftDiagnostic = array_values(array_filter(
    $sankeyDraftUpdates,
    static fn (array $update): bool => $update['Field'] === 'ConfigurationDiagnostics'
));
assertGatewayGauge(
    str_contains($sankeyDraftDiagnostic[0]['Value'], 'Connection 2:')
        && json_decode($sankey->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey']['positiveCount'] === 2,
    'Draft Sankey errors must identify the row and leave the saved configuration untouched.'
);
$sankey->UpdateBarPreviewFromForm(json_encode([
    'Nodes'                 => $sankeyNodes, 'Sources' => $sankeySources, 'NodeColors' => [['Key' => 'load', 'Color' => 0xABCDEF]],
    'NodeBorderWidth'       => 3, 'NodeBorderRadius' => 6, 'NodeOpacityPercent' => 70, 'NodeShadowBlur' => 8,
    'LabelPosition'         => 'inside', 'LabelBold' => true, 'LabelMaxWidth' => 40, 'LabelOverflow' => 'breakAll',
    'HideOverlappingLabels' => true, 'ShowEdgeValues' => true, 'EdgeLabelColor' => 0x654321
], JSON_THROW_ON_ERROR));
$sankeyPreviewUpdate = array_slice($sankey->GetTestFormUpdates(), -2)[0]['Value'];
$sankeyPreviewSvg = base64_decode(substr($sankeyPreviewUpdate, strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($sankeyPreviewSvg) && str_contains($sankeyPreviewSvg, '#ABCDEF')
        && str_contains($sankeyPreviewSvg, 'rx="6" opacity="0.7"')
        && str_contains($sankeyPreviewSvg, 'stroke-width="3" filter="url(#node-shadow)"')
        && str_contains($sankeyPreviewSvg, 'font-weight="bold"') && str_contains($sankeyPreviewSvg, '#654321')
        && json_decode($sankey->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey']['style']['nodeColors'] === ['load' => '#FF0000', 'other' => '#00FF00'],
    'Draft Sankey appearance must reach the SVG preview without changing saved per-node colors.'
);
$sankey->UpdateBarPreviewFromForm(json_encode(['Nodes' => $sankeyNodes, 'Sources' => $sankeySources,
    'NodeColors'                                       => [$sankeyColors[0], $sankeyColors[0]]], JSON_THROW_ON_ERROR));
$sankeyColorDiagnostic = array_values(array_filter(
    $sankey->GetTestFormUpdates(),
    static fn (array $update): bool => $update['Field'] === 'ConfigurationDiagnostics'
));
assertGatewayGauge(str_contains(end($sankeyColorDiagnostic)['Value'], 'Node color 2:'), 'Duplicate color rows must report their row in draft diagnostics.');
$sankey->UpdateBarPreviewFromForm(json_encode(['Nodes' => $sankeyNodes, 'Sources' => $sankeySources,
    'IPSViewUseTileDesign'                             => true, 'IPSViewNodeColors' => [$sankeyColors[0], $sankeyColors[0]]], JSON_THROW_ON_ERROR));
$sankeyInheritedDiagnostic = array_values(array_filter(
    $sankey->GetTestFormUpdates(),
    static fn (array $update): bool => $update['Field'] === 'ConfigurationDiagnostics'
));
assertGatewayGauge(end($sankeyInheritedDiagnostic)['Value'] === 'Sankey configuration is valid.', 'Disabled independent color drafts must not invalidate inherited IPSView design.');
$sankeySavedProperties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($sankey);
$sankeyDraftNodes = [['Key' => 'previewSource', 'Label' => 'Actual preview source']];
$sankeyDraftSources = [];
for ($index = 0; $index < 24; ++$index) {
    $sankeyDraftNodes[] = ['Key' => 'previewSink' . $index, 'Label' => 'Actual preview consumer ' . $index];
    $sankeyDraftSources[] = ['Source' => 'previewSource', 'Target' => 'previewSink' . $index, 'VariableID' => $index === 0 ? 4711 : 4713];
}
$sankeyReadPreview = static function (array $draft) use ($sankey): array
{
    $sankey->UpdateBarPreviewFromForm(json_encode($draft, JSON_THROW_ON_ERROR));
    return array_map(
        static fn (array $update): string => base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true),
        array_slice($sankey->GetTestFormUpdates(), -2)
    );
};
$sankeyPreviewValuesBefore = [GetValue(4711), GetValue(4713)];
try {
    $GLOBALS['symconTestVariables'][4711]['Value'] = 200.0;
    $GLOBALS['symconTestVariables'][4713]['Value'] = 50.0;
    $sankeyDraft = ['Nodes' => $sankeyDraftNodes, 'Sources' => $sankeyDraftSources,
        'Orientation'       => 'vertical', 'IPSViewUseTileDesign' => false, 'IPSViewOrientation' => 'horizontal'];
    foreach ($sankeyReadPreview($sankeyDraft) as $output => $svg) {
        $document = new DOMDocument();
        assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Current-value Sankey previews must be valid XML.');
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
        $rectangles = $xpath->query('//svg:rect[@data-node]');
        $flows = $xpath->query('//svg:path[@data-source]');
        assertGatewayGauge(
            $rectangles->length === 25 && $flows->length === 24
            && str_contains($svg, 'Actual preview consumer 23') && !str_contains($svg, 'Source A'),
            'Large Sankey previews must retain all actual nodes and connections instead of replacing them with an example.'
        );
        $first = $xpath->query('//svg:rect[@data-node="previewSink0"]')->item(0);
        $second = $xpath->query('//svg:rect[@data-node="previewSink1"]')->item(0);
        $extent = $output === 0 ? 'width' : 'height';
        assertGatewayGauge(
            abs((float) $first->getAttribute($extent) / (float) $second->getAttribute($extent) - 4) < 0.05,
            'Preview node proportions must reflect current variable values, including reused sources.'
        );
        assertGatewayGauge(
            $first->getAttribute('fill') === '#AECE41',
            'Automatic preview colors must follow Sankey value interpolation (200 between 50 and 1350), not node index.'
        );
        foreach ($flows as $flow) {
            assertGatewayGauge(
                str_ends_with($flow->getAttribute('d'), ' Z') && !$flow->hasAttribute('stroke-width'),
                'Preview flows must be closed ribbons, not thick strokes protruding beyond node endpoints.'
            );
            preg_match_all('/-?\d+(?:\.\d+)?/', $flow->getAttribute('d'), $coordinates);
            foreach ($coordinates[0] as $index => $coordinate) {
                $limit = $index % 2 === 0 ? 580 : 220;
                assertGatewayGauge(
                    (float) $coordinate >= -0.01 && (float) $coordinate <= $limit + 0.01,
                    'All ribbon boundary control points must remain within the preview plot, even on tight vertical curves.'
                );
            }
        }
    }
    $GLOBALS['symconTestVariables'][4711]['Value'] = 0.0;
    $GLOBALS['symconTestVariables'][4713]['Value'] = 0.0;
    assertGatewayGauge(str_contains($sankeyReadPreview($sankeyDraft)[0], 'No positive flows'), 'All-zero previews must match the empty runtime state.');
    $GLOBALS['symconTestVariables'][4711]['Value'] = -1.0;
    $sankeyInvalidPreview = $sankeyReadPreview($sankeyDraft)[0];
    assertGatewayGauge(
        str_contains($sankeyInvalidPreview, 'finite and non-negative') && !str_contains($sankeyInvalidPreview, 'data-source='),
        'Invalid current values must be reported without inventing example weights.'
    );
} finally {
    $GLOBALS['symconTestVariables'][4711]['Value'] = $sankeyPreviewValuesBefore[0];
    $GLOBALS['symconTestVariables'][4713]['Value'] = $sankeyPreviewValuesBefore[1];
}
assertGatewayGauge(
    (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($sankey) === $sankeySavedProperties
    && $sankey->GetTestStatus() === IS_ACTIVE && $sankey->GetTestReferences() === [4711, 4713],
    'Preview reads, invalid current values and unsaved graph edits must not modify saved configuration, status or subscriptions.'
);
$GLOBALS['symconTestCopyTarget'] = $sankey;
try {
    $sankey->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(
    str_contains($sankey->GetIPSViewHTML(), '"orientation":"horizontal"')
        && str_contains($sankey->GetIPSViewHTML(), '"nodeColors":{"load":"#FF0000","other":"#00FF00"}')
        && str_contains($sankey->GetIPSViewHTML(), '"labelPosition":"inside"')
        && str_contains($sankey->GetIPSViewHTML(), '"showEdgeValues":true'),
    'Sankey must copy Tile settings into an independent IPSView design.'
);
foreach ([['NodeBorderWidth', 9, 0], ['NodeBorderRadius', 21, 8], ['NodeOpacityPercent', -1, 100],
    ['NodeShadowBlur', 21, 0], ['LayoutIterations', 65, 32], ['LabelDistance', -1, 5], ['LabelMaxWidth', 401, 0],
    ['EdgeLabelFontSizePercent', 201, 100], ['HoverFocus', 'invalid', 'adjacency'], ['LabelPosition', 'invalid', 'inside'],
    ['LabelOverflow', 'invalid', 'truncate'], ['NodeBorderColor', 0x1000000, -1],
    ['NodeShadowColor', -2, -1], ['EdgeLabelColor', -2, -1], ['HighlightEdgeOpacityPercent', 101, 85],
    ['BlurEdgeOpacityPercent', -1, 5], ['NodeColors', '[{"Key":"load","Color":-1}]', json_encode($sankeyColors, JSON_THROW_ON_ERROR)]] as [$name, $invalid, $restore]) {
    foreach (['', 'IPSView'] as $prefix) {
        $sankey->SetTestProperty($prefix . $name, $invalid);
        $sankey->ApplyChanges();
        assertGatewayGauge($sankey->GetTestStatus() === 202, 'Sankey must reject invalid design setting: ' . $prefix . $name);
        $sankey->SetTestProperty($prefix . $name, $restore);
        $sankey->ApplyChanges();
        assertGatewayGauge($sankey->GetTestStatus() === IS_ACTIVE, 'Sankey must recover after correcting design setting: ' . $prefix . $name);
    }
}
$sankeyValueBefore = GetValue(4711);
try {
    $GLOBALS['symconTestVariables'][4711]['Value'] = -1.0;
    $sankey->MessageSink(1780000010, 4711, VM_UPDATE, [-1.0, true]);
    assertGatewayGauge($sankey->GetTestStatus() === 205, 'Sankey must reject negative current flow widths.');
    $GLOBALS['symconTestVariables'][4711]['Value'] = 25.0;
    $sankey->MessageSink(1780000011, 4711, VM_UPDATE, [25.0, true]);
    $sankeyChanged = json_decode($sankey->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        $sankey->GetTestStatus() === IS_ACTIVE && $sankeyChanged['sankey']['links'][0]['value'] === 25.0,
        'Sankey must recover and update its existing chart after a source change.'
    );
} finally {
    $GLOBALS['symconTestVariables'][4711]['Value'] = $sankeyValueBefore;
}
assertTransientRetry($sankey, 'Sankey');
$sankey->SetTestProperty('Sources', json_encode([...$sankeySources,
    ['Source' => 'load', 'Target' => 'supply', 'VariableID' => 4711]], JSON_THROW_ON_ERROR));
$sankey->ApplyChanges();
assertGatewayGauge(
    $sankey->GetTestStatus() === 201
        && str_contains($sankey->GetSankeyDiagnostic(), 'cycle'),
    'Sankey must reject cycles before rendering, including zero-width connections.'
);
$sankey->SetTestProperty('Sources', json_encode([$sankeySources[0]], JSON_THROW_ON_ERROR));
$sankey->ApplyChanges();
assertGatewayGauge($sankey->GetTestReferences() === [4711], 'Removing a Sankey connection must unregister unused sources.');
$sankey->SetTestProperty('Sources', json_encode([array_replace($sankeySources[0], ['VariableID' => 4712])], JSON_THROW_ON_ERROR));
$sankey->ApplyChanges();
assertGatewayGauge($sankey->GetTestStatus() === 201, 'Sankey must reject nonnumeric source variables.');
$sankeyLocale = json_decode(file_get_contents(__DIR__ . '/../EChartsSankey/locale.json'), true, 512, JSON_THROW_ON_ERROR)['translations']['de'];
$sankeyStaticForm = json_decode(file_get_contents(__DIR__ . '/../EChartsSankey/form.json'), true, 512, JSON_THROW_ON_ERROR);
$sankeyCaptionCheck = static function (array $items) use (&$sankeyCaptionCheck, $sankeyLocale): void
{
    foreach ($items as $key => $item) {
        if (in_array($key, ['caption', 'transparentCaption'], true) && is_string($item)) {
            assertGatewayGauge(isset($sankeyLocale[$item]), 'Sankey must localize form caption: ' . $item);
        } elseif (is_array($item)) {
            $sankeyCaptionCheck($item);
        }
    }
};
$sankeyCaptionCheck($sankeyStaticForm);

$maximumSankey = new EChartsSankey();
$maximumSankey->Create();
$maximumSankeyNodes = [];
$maximumSankeySources = [];
for ($index = 0; $index < 100; ++$index) {
    $maximumSankeyNodes[] = ['Key' => 'node' . $index, 'Label' => str_repeat('N', 80)];
}
for ($source = 0; $source < 100 && count($maximumSankeySources) < 200; ++$source) {
    for ($target = $source + 1; $target < 100 && count($maximumSankeySources) < 200; ++$target) {
        $maximumSankeySources[] = ['Source' => 'node' . $source, 'Target' => 'node' . $target, 'VariableID' => 4711];
    }
}
$maximumSankey->SetTestProperty('Nodes', json_encode($maximumSankeyNodes, JSON_THROW_ON_ERROR));
$maximumSankey->SetTestProperty('NodeColors', json_encode(array_map(static fn (array $node): array => [
    'Key' => $node['Key'], 'Color' => 0x55CBB5
], $maximumSankeyNodes), JSON_THROW_ON_ERROR));
$maximumSankey->SetTestProperty('Sources', json_encode($maximumSankeySources, JSON_THROW_ON_ERROR));
$maximumSankey->ApplyChanges();
assertGatewayGauge(
    $maximumSankey->GetTestStatus() === IS_ACTIVE && $maximumSankey->GetTestReferences() === [4711]
        && strlen($maximumSankey->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($maximumSankey->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($maximumSankey->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Sankey must deduplicate shared source subscriptions and fit the output limit at maximum configuration size.'
);

require __DIR__ . '/calendar_integration.php';
require __DIR__ . '/radar_integration.php';
require __DIR__ . '/boxplot_integration.php';
require __DIR__ . '/candlestick_integration.php';
require __DIR__ . '/funnel_integration.php';
require __DIR__ . '/pictorial_bar_integration.php';

echo "Gateway and Gauge module integration verified.\n";

require __DIR__ . '/debug_output.php';
