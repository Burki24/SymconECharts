<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsPictorialBar/module.php';

$importer = new EChartsPictorialBar();
$importer->Create();
$GLOBALS['symconTestTreeObjects'][9100] = ['ObjectType' => 0, 'ObjectName' => 'Devices'];
$GLOBALS['symconTestTreeObjects'][9101] = ['ObjectType' => 1, 'ObjectName' => 'Room'];
$GLOBALS['symconTestTreeChildren'][0] = [9100];
$GLOBALS['symconTestTreeChildren'][9100] = [9101, 9110, 9112];
$GLOBALS['symconTestTreeChildren'][9101] = [9111, 9113, 9114];
foreach ([9110, 9111, 9112, 9113, 9114] as $id) {
    $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => 2, 'ObjectName' => 'Batterie', 'ObjectIsHidden' => $id === 9114];
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => $id === 9113 ? 0 : 1,
        'Name'                                             => 'Batterie', 'Value' => 50, 'VariableUpdated' => 1780000000,
        'Presentation'                                     => ['SUFFIX' => $id === 9112 ? ' V' : ' %']];
}
$old = [['VariableID' => 9110, 'Label' => 'My battery', 'Color' => 0x123456, 'Minimum' => -10, 'Maximum' => 110]];
$importer->SetTestProperty('Sources', json_encode($old));
$properties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($importer);
$queryCount = $GLOBALS['symconTestArchiveQueryCount'];
$request = ['Action' => 'search', 'RootID' => 9100, 'Recursive' => true, 'IncludeHidden' => false,
    'NameFilter'     => 'bAtTeRiE', 'UnitFilter' => '%', 'Candidates' => [],
    'Preview'        => ['Sources' => $old, 'NormalizeValues' => false, 'Title' => 'Unsaved title',
        'IPSViewUseTileDesign'     => false, 'Orientation' => 'vertical', 'IPSViewOrientation' => 'horizontal']];
$result = $importer->UpdateSourceImportFromForm(json_encode($request));
assertGatewayGauge($result === '', 'Searching must update only the form.');
$updates = $importer->GetTestFormUpdates();
$rows = json_decode($updates[count($updates) - 2]['Value'], true);
assertGatewayGauge(
    array_column($rows, 'VariableID') === [9110, 9111]
    && $rows[0]['AlreadyPresent'] && !$rows[0]['Selected'] && !$rows[1]['Selected'],
    'Search must use numeric types, branch, exact unit and case-insensitive name; selection is explicit.'
);
$request['Action'] = 'append';
$rows[1]['Selected'] = true;
$request['Candidates'] = $rows;
$result = $importer->UpdateSourceImportFromForm(json_encode($request));
assertGatewayGauge(str_starts_with($result, 'MESSAGE:'), 'Selected sources must be appended to the draft.');
$updates = $importer->GetTestFormUpdates();
$sourceUpdates = array_values(array_filter($updates, static fn ($u) => $u['Field'] === 'Sources'));
$merged = json_decode(end($sourceUpdates)['Value'], true);
assertGatewayGauge(
    array_column($merged, 'VariableID') === [9110, 9111] && $merged[0] === $old[0],
    'The unsaved list order, labels, colors and ranges must survive unchanged.'
);
assertGatewayGauge(
    count(array_filter(array_slice($updates, -5), static fn ($u) => $u['Parameter'] === 'image')) === 2,
    'Both previews must immediately use the merged draft.'
);
$request['Preview']['Sources'] = $merged;
$result = $importer->UpdateSourceImportFromForm(json_encode($request));
assertGatewayGauge(str_starts_with($result, 'MESSAGE:0'), 'Repeated submission must not duplicate IDs.');
$request['Candidates'][1]['VariableID'] = 4711;
$before = count($importer->GetTestFormUpdates());
$result = $importer->UpdateSourceImportFromForm(json_encode($request));
assertGatewayGauge(
    !str_starts_with($result, 'MESSAGE:')
    && count($importer->GetTestFormUpdates()) === $before,
    'A stale or forged selection must not partially change the sources or previews.'
);
assertGatewayGauge(
    $properties === (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($importer)
    && $queryCount === $GLOBALS['symconTestArchiveQueryCount'],
    'Import must not persist properties, apply changes, or access archives.'
);

$request['Action'] = 'search';
$request['Recursive'] = false;
$importer->UpdateSourceImportFromForm(json_encode($request));
$updates = $importer->GetTestFormUpdates();
assertGatewayGauge(
    array_column(json_decode($updates[count($updates) - 2]['Value'], true), 'VariableID') === [9110],
    'Disabling recursion must search direct children only.'
);
$request['Recursive'] = true;
$request['IncludeHidden'] = true;
$request['UnitFilter'] = '';
$importer->UpdateSourceImportFromForm(json_encode($request));
$updates = $importer->GetTestFormUpdates();
$allRows = json_decode($updates[count($updates) - 2]['Value'], true);
assertGatewayGauge(
    array_column($allRows, 'VariableID') === [9110, 9112, 9111, 9114],
    'Empty unit filter must keep distinct units; hidden branches require explicit opt-in.'
);
$request['Action'] = 'all';
$importer->UpdateSourceImportFromForm(json_encode($request));
$updates = $importer->GetTestFormUpdates();
$selectedRows = json_decode($updates[count($updates) - 2]['Value'], true);
assertGatewayGauge(
    array_column($selectedRows, 'Selected') === [false, true, false, true],
    'Select all must select only new IDs, not existing sources.'
);
$request['Action'] = 'append';
$request['Candidates'] = $selectedRows;
$before = count($importer->GetTestFormUpdates());
assertGatewayGauge(
    !str_starts_with($importer->UpdateSourceImportFromForm(json_encode($request)), 'MESSAGE:')
    && count($importer->GetTestFormUpdates()) === $before,
    'A mixed-unit selection must fail atomically with normalization off.'
);
$request['Preview']['NormalizeValues'] = true;
assertGatewayGauge(
    str_starts_with($importer->UpdateSourceImportFromForm(json_encode($request)), 'MESSAGE:2'),
    'Explicit normalization must permit mixed units using editable 0–100 defaults.'
);
$GLOBALS['symconTestVariables'][9112]['Presentation']['SUFFIX'] = ' A';
$request['UnitFilter'] = 'V';
$before = count($importer->GetTestFormUpdates());
assertGatewayGauge(
    !str_starts_with($importer->UpdateSourceImportFromForm(json_encode($request)), 'MESSAGE:')
    && count($importer->GetTestFormUpdates()) === $before,
    'A source whose unit changed since search must not be imported under the old filter.'
);

assertGatewayGauge(
    $importer->UpdateSourceImportFromForm('{"Action":"reset"}') === '',
    'Changing a filter must clear stale results without requiring a complete draft or raising a dialog.'
);

// Execute the real generated callbacks with iterable list objects, as supplied by Symcon.
$form = json_decode($importer->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
function ECPB_UpdateSourceImportFromForm(int $id, string $configuration): string
{
    $GLOBALS['importCallbackPayload'] = json_decode($configuration, true, 64, JSON_THROW_ON_ERROR);
    return $GLOBALS['importCallbackModule']->UpdateSourceImportFromForm($configuration);
}
$GLOBALS['importCallbackModule'] = $importer;
$execute = static function (string $script, array $fields): string
{
    extract($fields);
    ob_start();
    try {
        eval($script);
        return (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
};
$fields = ['id' => 5000];
$collect = static function (array $elements) use (&$collect, &$fields): void
{
    foreach ($elements as $element) {
        if (isset($element['name'])) {
            $fields[$element['name']] = $element['value'] ?? null;
        }
        if (is_array($element['items'] ?? null)) {
            $collect($element['items']);
        }
    }
};
$collect($form['elements']);
foreach ($properties as $name => $value) {
    $fields[$name] = $value;
}
$fields['Sources'] = new ArrayIterator($old);
$fields['SourceImportCandidates'] = new ArrayIterator([]);
$fields['SourceImportRootID'] = 9100;
$fields['SourceImportNameFilter'] = 'Batterie';
$fields['SourceImportUnitFilter'] = '%';
$fields['Title'] = 'Unsaved <&> title';
$fields['IPSViewUseTileDesign'] = false;
$fields['IPSViewSymbol'] = 'circle';
$fields['TitleColor'] = 0x123456;
$fields['IPSViewTitleColor'] = 0xABCDEF;
assertGatewayGauge(
    $execute(findGaugeFormElement($form['elements'], 'SourceImportSearch')['onClick'], $fields) === '',
    'A successful search must remain silent instead of opening an empty dialog.'
);
$payload = $GLOBALS['importCallbackPayload'];
assertGatewayGauge(
    $payload['Preview']['Sources'] === $old && $payload['Preview']['IPSViewSymbol'] === 'circle'
    && $payload['Preview']['Title'] === 'Unsaved <&> title' && $payload['Preview']['IPSViewUseTileDesign'] === false,
    'Callbacks must copy iterable rows and every unsaved independent designer field, including false.'
);
$before = count($importer->GetTestFormUpdates());
assertGatewayGauge(
    $execute(findGaugeFormElement($form['elements'], 'SourceImportAppend')['onClick'], $fields)
        === 'Select at least one matching variable.'
    && count($importer->GetTestFormUpdates()) === $before,
    'The generated callback must print the empty-selection error without partially changing the draft.'
);
foreach (['SourceImportAll', 'SourceImportNone'] as $button) {
    assertGatewayGauge(
        $execute(findGaugeFormElement($form['elements'], $button)['onClick'], $fields) === '',
        'Successful selection actions must not print empty dialogs.'
    );
}
$invalidSearch = $fields;
$invalidSearch['SourceImportNameFilter'] = str_repeat('x', 121);
foreach (['SourceImportSearch', 'SourceImportAll', 'SourceImportNone'] as $button) {
    assertGatewayGauge(
        $execute(findGaugeFormElement($form['elements'], $button)['onClick'], $invalidSearch)
            === 'The variable-name search may contain at most 120 characters.',
        'Every generated search/selection callback must print its validation error.'
    );
}
$mixedFields = $fields;
$mixedFields['SourceImportUnitFilter'] = '';
$mixedFields['SourceImportCandidates'] = new ArrayIterator([['VariableID' => 9112, 'Selected' => true]]);
$before = count($importer->GetTestFormUpdates());
assertGatewayGauge(
    $execute(findGaugeFormElement($form['elements'], 'SourceImportAppend')['onClick'], $mixedFields)
        === 'PictorialBar variables must use the same unit.'
    && count($importer->GetTestFormUpdates()) === $before,
    'Unit validation must reach the console output without changing sources or previews.'
);
$fields['SourceImportCandidates'] = new ArrayIterator([['VariableID' => 9111, 'Selected' => true]]);
assertGatewayGauge(
    $execute(findGaugeFormElement($form['elements'], 'SourceImportAppend')['onClick'], $fields)
        === '1 sources added. Review labels and Min/Max, then apply the form to save.',
    'The callback must print a readable success confirmation without the internal MESSAGE marker.'
);
$repeatFields = $fields;
$repeatFields['Sources'] = new ArrayIterator($merged);
assertGatewayGauge(
    $execute(findGaugeFormElement($form['elements'], 'SourceImportAppend')['onClick'], $repeatFields)
        === '0 sources added. Review labels and Min/Max, then apply the form to save.',
    'A repeated selection must also have a clean confirmation while retaining the ID-based no-op behavior.'
);
$updates = $importer->GetTestFormUpdates();
$images = array_values(array_filter(array_slice($updates, -5), static fn ($u) => $u['Parameter'] === 'image'));
$tileSvg = base64_decode(substr($images[0]['Value'], strlen('data:image/svg+xml;base64,')));
$ipsSvg = base64_decode(substr($images[1]['Value'], strlen('data:image/svg+xml;base64,')));
assertGatewayGauge(
    str_contains($tileSvg, '#123456') && str_contains($ipsSvg, '#ABCDEF')
    && str_contains($tileSvg, 'Unsaved &lt;&amp;&gt; title') && str_contains($ipsSvg, 'Unsaved &lt;&amp;&gt; title'),
    'Both SVGs must retain the current unsaved title and independent designer colors during import.'
);
assertGatewayGauge(
    $execute(findGaugeFormElement($form['elements'], 'SourceImportRootID')['onChange'], $fields) === '',
    'Reset callbacks must invalidate candidates without printing a dialog.'
);
assertGatewayGauge($GLOBALS['importCallbackPayload'] === ['Action' => 'reset'], 'Filter edits must invalidate stale candidates.');
$locale = json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsPictorialBar/locale.json'), true)['translations']['de'];
$checkCaptions = static function (array $elements) use (&$checkCaptions, $locale): void
{
    foreach ($elements as $element) {
        if (isset($element['caption'])) {
            assertGatewayGauge(isset($locale[$element['caption']]), 'Missing German import caption: ' . $element['caption']);
        }
        foreach (['items', 'columns'] as $key) {
            if (is_array($element[$key] ?? null)) {
                $checkCaptions($element[$key]);
            }
        }
    }
};
$panel = array_values(array_filter($form['elements'], static fn ($e) => ($e['caption'] ?? '') === 'Import multiple sources'))[0];
$checkCaptions([$panel]);
assertGatewayGauge(strlen(json_encode($form)) < SYMCON_OUTPUT_BUFFER_LIMIT, 'The full import form must stay below 1 MiB.');

// The entire selection is admitted or rejected; no silent clipping at 100/64 KiB/search budgets.
$GLOBALS['symconTestTreeChildren'][9100] = [];
$hundred = [];
$selected = [];
for ($i = 0; $i < 101; ++$i) {
    $id = 9200 + $i;
    $GLOBALS['symconTestTreeChildren'][9100][] = $id;
    $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => 2, 'ObjectName' => 'Batterie'];
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 1, 'Name' => 'Batterie', 'Value' => 25,
        'VariableUpdated'                                  => 1780000000, 'Presentation' => ['SUFFIX' => ' %']];
    $hundred[] = ['VariableID' => $id, 'Label' => ''];
    $selected[] = ['VariableID' => $id, 'Selected' => true];
}
$limitFields = $fields;
$limitFields['Sources'] = new ArrayIterator(array_slice($hundred, 0, 100));
$limitFields['SourceImportCandidates'] = new ArrayIterator([end($selected)]);
$before = count($importer->GetTestFormUpdates());
assertGatewayGauge(
    $execute(findGaugeFormElement($form['elements'], 'SourceImportAppend')['onClick'], $limitFields)
        === 'The selection exceeds the source limit. Select fewer variables.'
    && count($importer->GetTestFormUpdates()) === $before,
    'The live-reproduced 101-source failure must be printed and must retain the complete existing draft.'
);
$request['Preview'] = ['Sources' => [], 'NormalizeValues' => false];
$request['Candidates'] = array_slice($selected, 0, 100);
$request['UnitFilter'] = '%';
assertGatewayGauge(
    str_starts_with($importer->UpdateSourceImportFromForm(json_encode($request)), 'MESSAGE:100'),
    'An initially empty list must accept all 100 selected numeric variables.'
);
$request['Candidates'] = $selected;
$before = count($importer->GetTestFormUpdates());
assertGatewayGauge(!str_starts_with($importer->UpdateSourceImportFromForm(json_encode($request)), 'MESSAGE:')
    && count($importer->GetTestFormUpdates()) === $before, '101 sources must fail without changing the draft.');
$oversized = array_slice($hundred, 0, 99);
$oversized[0]['RetainedExtension'] = str_repeat('x', 65500);
try {
    SymconECharts\EChartsSourceImport::Append($oversized, [end($selected)], $hundred, 100);
    throw new RuntimeException('The 64 KiB guard did not reject an oversized draft.');
} catch (InvalidArgumentException $exception) {
    assertGatewayGauge(str_contains($exception->getMessage(), '64 KiB'), 'The source byte budget must remain strict.');
}
for ($i = 101; $i < 501; ++$i) {
    $id = 9200 + $i;
    $GLOBALS['symconTestTreeChildren'][9100][] = $id;
    $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => 2, 'ObjectName' => 'Batterie'];
    $GLOBALS['symconTestVariables'][$id] = $GLOBALS['symconTestVariables'][9200];
}
$request['Action'] = 'search';
$GLOBALS['symconTestTreeChildren'][9100] = array_slice($GLOBALS['symconTestTreeChildren'][9100], 0, 500);
assertGatewayGauge(
    count(SymconECharts\EChartsSourceImport::Search($request, [])) === 500,
    'A complete result at the 500-match boundary must remain usable.'
);
$GLOBALS['symconTestTreeChildren'][9100][] = 9700;
assertGatewayGauge(
    str_contains($importer->UpdateSourceImportFromForm(json_encode($request)), '500 matches'),
    'Truncated searches must be rejected, not offered as complete matches.'
);
$GLOBALS['symconTestTreeChildren'][9100] = range(10000, 15000);
foreach (range(10000, 15000) as $id) {
    $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => 0, 'ObjectName' => 'Empty'];
}
$GLOBALS['symconTestTreeChildren'][9100] = range(10000, 14998);
assertGatewayGauge(
    SymconECharts\EChartsSourceImport::Search($request, []) === [],
    'A complete search of exactly 5000 objects including its root must succeed.'
);
$GLOBALS['symconTestTreeChildren'][9100] = range(10000, 14999);
assertGatewayGauge(
    str_contains($importer->UpdateSourceImportFromForm(json_encode($request)), '5000 objects'),
    'Even searches without matching variables must obey the object budget.'
);
$GLOBALS['symconTestTreeChildren'][9100] = [9200];
$GLOBALS['symconTestTreeObjects'][9200]['ObjectName'] = 'Batterie' . str_repeat('Ü', 50000);
assertGatewayGauge(
    str_contains($importer->UpdateSourceImportFromForm(json_encode($request)), 'too large'),
    'Excessive result metadata must be bounded independently of object and match counts.'
);
assertGatewayGauge(
    $properties === (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($importer),
    'Even successful 100-source imports and failing searches must retain every saved property.'
);

foreach (['null', '[]', '"bad"', '{', str_repeat('x', 2097153)] as $badPayload) {
    assertGatewayGauge(
        !str_starts_with($importer->UpdateSourceImportFromForm($badPayload), 'MESSAGE:'),
        'Malformed and oversized action payloads must return controlled errors.'
    );
}
echo "PictorialBar source import draft workflow verified.\n";
