<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use Throwable;

require_once __DIR__ . '/EChartsSourceImport.php';

/** Form-only source import wiring shared independently of a chart's geometry. */
trait EChartsSourceImportForm
{
    /** Executes search/selection/append using only the submitted draft, never saved properties. */
    public function UpdateSourceImportFromForm(string $Configuration): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            try {
                if (strlen($Configuration) > 2097152) {
                    throw new InvalidArgumentException('The source import draft is too large.');
                }
                $draft = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
                if (is_array($draft) && ($draft['Action'] ?? '') === 'reset') {
                    $this->UpdateFormField('SourceImportCandidates', 'values', '[]');
                    $this->UpdateFormField('SourceImportSummary', 'caption', $this->Translate('Search again after correcting the filters.'));
                    return $debugFlowResult = '';
                }
                if (!is_array($draft) || !is_array($draft['Preview'] ?? null)
                    || !is_array($draft['Preview']['Sources'] ?? null)
                    || !array_is_list($draft['Preview']['Sources'])
                    || !is_bool($draft['Preview']['NormalizeValues'] ?? null)) {
                    throw new InvalidArgumentException('The source import draft is invalid.');
                }
                $preview = $draft['Preview'];
                $sources = $preview['Sources'];
                if ($sources !== []) {
                    $this->ValidateSourceImport(json_encode($sources, JSON_THROW_ON_ERROR), $preview['NormalizeValues']);
                }
                $rows = EChartsSourceImport::Search($draft, $sources);
                $action = $draft['Action'] ?? '';
                if (in_array($action, ['search', 'all', 'none'], true)) {
                    foreach ($rows as &$row) {
                        $row['Selected'] = $action === 'all' && !$row['AlreadyPresent'];
                    }
                    unset($row);
                    $this->UpdateFormField('SourceImportCandidates', 'values', json_encode($rows, JSON_THROW_ON_ERROR));
                    $this->UpdateFormField('SourceImportSummary', 'caption', sprintf(
                        $this->Translate('%d matching variables; %d already configured.'),
                        count($rows),
                        count(array_filter($rows, static fn (array $row): bool => $row['AlreadyPresent']))
                    ));
                    return $debugFlowResult = '';
                }
                if ($action !== 'append' || !is_array($draft['Candidates'] ?? null)) {
                    throw new InvalidArgumentException('The source import action is invalid.');
                }
                $merged = EChartsSourceImport::Append($sources, $draft['Candidates'], $rows, 100);
                $encoded = json_encode($merged, JSON_THROW_ON_ERROR);
                $this->ValidateSourceImport($encoded, $preview['NormalizeValues']);
                $preview['Sources'] = $merged;
                // Validate and render the entire draft before updating the source list.
                $this->UpdateBarPreviewFromForm(json_encode($preview, JSON_THROW_ON_ERROR));
                $this->UpdateFormField('Sources', 'values', $encoded);
                $this->UpdateFormField('SourceImportCandidates', 'values', '[]');
                $this->UpdateFormField('SourceImportSummary', 'caption', $this->Translate('Search again to select further variables.'));
                return $debugFlowResult = 'MESSAGE:' . sprintf(
                    $this->Translate('%d sources added. Review labels and Min/Max, then apply the form to save.'),
                    count($merged) - count($sources)
                );
            } catch (Throwable $exception) {
                if (($draft['Action'] ?? 'search') !== 'append') {
                    $this->UpdateFormField('SourceImportCandidates', 'values', '[]');
                    $this->UpdateFormField('SourceImportSummary', 'caption', $this->Translate('Search again after correcting the filters.'));
                }
                return $debugFlowResult = $this->Translate($exception->getMessage());
            }
        } catch (Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }
    abstract protected function ValidateSourceImport(string $sources, bool $normalize): void;

    /** @param list<string> $previewFields */
    private function WithSourceImportForm(array $form, string $prefix, array $previewFields): array
    {
        $options = ['RootID', 'Recursive', 'IncludeHidden', 'NameFilter', 'UnitFilter'];
        $pairs = array_map(static fn (string $name): string => var_export($name, true) . ' => $SourceImport' . $name, $options);
        $previewFields = array_values(array_diff($previewFields, ['Sources']));
        $setup = '$sourceImportSources = []; foreach ($Sources as $sourceImportRow) { $sourceImportSources[] = $sourceImportRow; } '
            . '$sourceImportCandidates = []; foreach ($SourceImportCandidates as $sourceImportRow) { $sourceImportCandidates[] = $sourceImportRow; } ';
        $buttons = [];
        foreach (['search' => 'Search variables', 'all' => 'Select all new matches', 'none' => 'Clear selection', 'append' => 'Add selected sources'] as $action => $caption) {
            $buttons[] = ['type' => 'Button', 'caption' => $caption, 'name' => 'SourceImport' . ucfirst($action),
                'onClick'        => $setup . 'return ' . $prefix . '_UpdateSourceImportFromForm($id, json_encode(['
                    . "'Action' => " . var_export($action, true) . ', ' . implode(', ', $pairs)
                    . ", 'Candidates' => \$sourceImportCandidates, 'Preview' => ['Sources' => \$sourceImportSources] + compact("
                    . implode(', ', array_map(static fn (string $name): string => var_export($name, true), $previewFields)) . ')]));'];
        }
        $columns = [['name' => 'Selected', 'caption' => 'Add', 'width' => '70px', 'edit' => ['type' => 'CheckBox']],
            ['name' => 'Name', 'caption' => 'Variable', 'width' => '200px', 'save' => true],
            ['name' => 'Path', 'caption' => 'Path', 'width' => 'auto', 'save' => true],
            ['name' => 'Unit', 'caption' => 'Unit', 'width' => '90px', 'save' => true],
            ['name' => 'AlreadyPresent', 'caption' => 'Already configured', 'width' => '140px', 'save' => true],
            ['name' => 'VariableID', 'visible' => false, 'save' => true]];
        $reset = 'return ' . $prefix . '_UpdateSourceImportFromForm($id, ' . var_export('{"Action":"reset"}', true) . ');';
        $items = [
            ['type' => 'Label', 'caption' => 'Find numeric variables, select matches and add them to the unsaved source list. Existing sources and designs are retained.'],
            ['type' => 'SelectObject', 'name' => 'SourceImportRootID', 'caption' => 'Search branch (0 = Symcon)', 'value' => 0],
            ['type' => 'CheckBox', 'name' => 'SourceImportRecursive', 'caption' => 'Include subobjects (up to 100 levels)', 'value' => true],
            ['type' => 'CheckBox', 'name' => 'SourceImportIncludeHidden', 'caption' => 'Include hidden objects', 'value' => false],
            ['type' => 'ValidationTextBox', 'name' => 'SourceImportNameFilter', 'caption' => 'Variable name contains (optional)', 'value' => ''],
            ['type' => 'ValidationTextBox', 'name' => 'SourceImportUnitFilter', 'caption' => 'Exact unit (empty = all)', 'value' => ''],
            ['type' => 'Label', 'name' => 'SourceImportSummary', 'caption' => 'Search checks up to 5000 objects and 500 numeric matches. Narrow the branch or name if a limit is reached.'],
            ['type'   => 'List', 'name' => 'SourceImportCandidates', 'caption' => 'Matches (select rows to add)', 'rowCount' => 8,
                'add' => false, 'delete' => false, 'values' => [], 'columns' => $columns]
        ];
        foreach ($items as &$item) {
            if (in_array($item['name'] ?? '', array_map(static fn (string $name): string => 'SourceImport' . $name, $options), true)) {
                $item['onChange'] = $reset;
            }
        }
        unset($item);
        array_splice($items, 6, 0, [$buttons[0]]);
        array_push($items, ...array_slice($buttons, 1));
        $panel = ['type' => 'ExpansionPanel', 'caption' => 'Import multiple sources', 'expanded' => false, 'items' => $items];
        foreach ($form['elements'] as $index => $element) {
            if (($element['name'] ?? '') === 'Sources') {
                array_splice($form['elements'], $index + 1, 0, [$panel]);
                return $form;
            }
        }
        throw new InvalidArgumentException('The source list is missing from the configuration form.');
    }
}
