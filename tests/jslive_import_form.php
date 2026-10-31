<?php

declare(strict_types=1);

require_once __DIR__ . '/jslive_import_fixture.php';

use Burki24\SymconModuleHelper\VariablePresentationNormalizationHelper;
use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveRuntime;

set_error_handler(static function (int $severity, string $message): bool
{
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new RuntimeException($message);
});

class IPSModuleStrict
{
    public array $fields = [];
    public array $debug = [];
    public function __construct(protected int $InstanceID)
    {
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Data;
        return true;
    }
    protected function UpdateFormField(string $name, string $field, mixed $value): void
    {
        $this->fields[$name][$field] = $value;
    }
    protected function Translate(string $text): string
    {
        static $locale = null;
        $locale ??= json_decode(file_get_contents(__DIR__ . '/../EChartsGateway/locale.json'), true, 512, JSON_THROW_ON_ERROR)['translations']['de'];
        return $locale[$text] ?? $text;
    }
}

$GLOBALS['previewSourceState'] = ['installation' => '22222222-2222-4222-8222-222222222222', 'logging' => true, 'type' => 0, 'writes' => 0];
function IPS_VariableExists(int $id): bool
{
    return in_array($id, [13000, 14000], true);
}
function IPS_GetVariable(int $id): array
{
    return ['VariableType' => 2, 'VariableProfile' => '', 'VariableCustomProfile' => '', 'VariableAction' => 0, 'VariableCustomAction' => 0];
}
function IPS_GetVariablePresentation(int $id): array
{
    return ['PRESENTATION' => VariablePresentationNormalizationHelper::VALUE, 'SUFFIX' => ' %', 'DIGITS' => 0];
}
function IPS_GetObject(int $id): array
{
    return ['ObjectName' => 'Repeated name', 'ObjectIcon' => ''];
}
function IPS_GetInstance(int $id): array
{
    return ['ModuleInfo' => ['ModuleID' => $id === 2 ? '{43192F0B-135B-4CE7-A0A7-1475603F3060}' : '{}']];
}
function IPS_GetInstanceListByModuleID(string $module): array
{
    return [20000];
}
function SymconJSLive_GetMigrationInstallationID(int $id): string
{
    return $GLOBALS['previewSourceState']['installation'];
}
function AC_GetLoggingStatus(int $archive, int $id): bool
{
    return $GLOBALS['previewSourceState']['logging'];
}
function AC_GetAggregationType(int $archive, int $id): int
{
    return $GLOBALS['previewSourceState']['type'];
}
function AC_GetCounterIgnoreZeros(int $archive, int $id): bool
{
    return true;
}
function IPS_SetConfiguration(int $id, string $configuration): void
{
    ++$GLOBALS['previewSourceState']['writes'];
    throw new RuntimeException('Unexpected configuration write.');
}
function SetValue(int $id, mixed $value): void
{
    ++$GLOBALS['previewSourceState']['writes'];
    throw new RuntimeException('Unexpected value write.');
}
function AC_SetLoggingStatus(int $archive, int $id, bool $logging): void
{
    ++$GLOBALS['previewSourceState']['writes'];
    throw new RuntimeException('Unexpected archive write.');
}
function AC_GetLoggedValues(int $archive, int $id, int $from, int $until, int $limit): never
{
    throw new LogicException('Preview must not read archive points.');
}

require_once __DIR__ . '/../EChartsGateway/module.php';
$gateway = new EChartsGateway(21000);
function ECGW_PreviewJSLiveImportFromForm(int $id, string $file, string $mapping = '{}', int $chart = 0, int $page = 0, bool $download = false): string
{
    return $GLOBALS['gateway']->PreviewJSLiveImportFromForm($file, $mapping, $chart, $page, $download);
}
function ECGW_PreviewJSLiveImport(int $id, string $package, string $mapping = '{}'): string
{
    return $GLOBALS['gateway']->PreviewJSLiveImport($package, $mapping);
}
function assertImportForm(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$stateBefore = $GLOBALS['previewSourceState'];
$package = jsliveImportPackage();
$raw = EChartsImportJson::Canonical($package);
$upload = base64_encode($raw);
$form = json_decode(file_get_contents(__DIR__ . '/../EChartsGateway/form.json'), true, 64, JSON_THROW_ON_ERROR);
$items = $form['actions'][0]['items'];
$inputs = array_column(array_filter($items, static fn (array $item): bool => isset($item['name'])), null, 'name');
assertImportForm(!str_contains(file_get_contents(__DIR__ . '/../EChartsGateway/module.php'), "RegisterPropertyString('JSLive"), 'Preview drafts must not become persistent instance properties.');
assertImportForm($gateway->PreviewJSLiveImportFromForm($upload) === '', 'Valid SelectFile package did not populate native preview tables.');
$fields = $gateway->fields;
assertImportForm(count(json_decode($fields['JSLiveImportCharts']['values'], true, 32, JSON_THROW_ON_ERROR)) === 1, 'Native chart mapping table is empty.');
assertImportForm(str_contains($fields['JSLiveImportSummary']['caption'], 'Plan:') && $fields['JSLiveImportPage']['value'] === 1, 'Plan summary or report page is missing.');
$full = json_decode($gateway->PreviewJSLiveImport($raw), true, 64, JSON_THROW_ON_ERROR);
$allFields = array_merge($full['charts'][0]['fieldCoverage'], $full['charts'][0]['metadataCoverage']);
assertImportForm(count(json_decode($fields['JSLiveImportFields']['values'], true, 64, JSON_THROW_ON_ERROR)) === min(100, count($allFields)), 'UI ledger is not explicitly paginated.');
$pages = max(1, (int) ceil(count($allFields) / 100));
$seen = 0;
for ($page = 0; $page < $pages; ++$page) {
    $gateway->PreviewJSLiveImportFromForm($upload, '{}', 0, $page);
    $seen += count(json_decode($gateway->fields['JSLiveImportFields']['values'], true, 64, JSON_THROW_ON_ERROR));
}
assertImportForm($seen === count($allFields), 'Native ledger pagination omitted report entries.');

// Execute the exact native callbacks, including their message-output contract.
$id = 21000;
$JSLiveImportFile = $upload;
$JSLiveImportChart = 1;
$JSLiveImportPage = 1;
$JSLiveImportSources = json_decode($gateway->fields['JSLiveImportSources']['values'], true, 32, JSON_THROW_ON_ERROR);
$JSLiveImportArchives = json_decode($gateway->fields['JSLiveImportArchives']['values'], true, 32, JSON_THROW_ON_ERROR);
ob_start();
eval($inputs['JSLiveImportFile']['onChange']);
$output = ob_get_clean();
assertImportForm($output === '', 'Successful preview should update tables without a redundant dialog.');
$download = array_values(array_filter($items, static fn (array $item): bool => isset($item['download'])))[0];
ob_start();
eval($download['onClick']);
$output = ob_get_clean();
assertImportForm(str_starts_with($output, 'data:application/json;base64,'), 'Complete report download is not a native data URL.');
$downloaded = json_decode(base64_decode(substr($output, strlen('data:application/json;base64,')), true), true, 64, JSON_THROW_ON_ERROR);
assertImportForm(count($downloaded['charts'][0]['fieldCoverage']) === count($full['charts'][0]['fieldCoverage']), 'Downloaded report is silently truncated to the visible table page.');
$JSLiveImportFile = base64_encode('{"format":1,"format":2}');
ob_start();
eval($inputs['JSLiveImportFile']['onChange']);
$output = ob_get_clean();
assertImportForm(str_starts_with($output, 'PACKAGE_INVALID:') && $gateway->fields['JSLiveImportCharts']['values'] === '[]'
    && $gateway->fields['JSLiveImportDraft']['caption'] === '', 'Invalid upload callback lost its error message or retained a stale usable plan.');
assertImportForm(!str_contains(implode('', $gateway->debug), 'Synthetic chart'), 'Debug output leaked package contents.');
assertImportForm($stateBefore === $GLOBALS['previewSourceState'], 'Preview modified installation state.');
foreach (['{"sourceMapping":[]}', '{"sourceMapping":null}', '{"sourceMapping":{"variable:999":13000}}'] as $options) {
    try {
        $gateway->PreviewJSLiveImport($raw, $options);
        throw new LogicException('Malformed or unknown mapping was accepted.');
    } catch (InvalidArgumentException) {
    }
}
assertImportForm(EChartsJSLiveRuntime::Source(13000, 2)['archive']['aggregationType'] === 0, 'Runtime adapter did not preserve archive metadata.');
$GLOBALS['previewSourceState']['installation'] = '';
assertImportForm(EChartsJSLiveRuntime::InstallationID() === null, 'Unconfirmed installation origin was inferred from names.');
echo "Gateway JSLive preview, complete report download, native callbacks, paging and read-only metadata adapter verified.\n";
