<?php

declare(strict_types=1);

namespace SymconECharts;

use Throwable;

require_once __DIR__ . '/EChartsJSLiveImport.php';
require_once __DIR__ . '/EChartsJSLiveRuntime.php';

/** Nonpersistent Gateway actions for package/plan review; no import execution entry point. */
trait EChartsJSLiveImportForm
{
    public function PreviewJSLiveImport(string $Package, string $Options = '{}'): string
    {
        $failed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $options = EChartsImportJson::Decode($Options);
            if (!$options instanceof \stdClass) {
                throw new \InvalidArgumentException('PACKAGE_INVALID: preview options must be an object.');
            }
            foreach (get_object_vars($options) as $mapping) {
                if (!$mapping instanceof \stdClass) {
                    throw new \InvalidArgumentException('PACKAGE_INVALID: each mapping must be an object.');
                }
            }
            $report = EChartsJSLiveImport::Analyze(
                $Package,
                EChartsJSLivePackage::ArrayValue($options),
                EChartsJSLiveRuntime::Source(...),
                EChartsJSLiveRuntime::InstallationID()
            );
            return EChartsImportJson::Canonical($report);
        } catch (Throwable $exception) {
            $failed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            throw $exception;
        } finally {
            // Package contents and report values must never enter the debug channel.
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, null, $failed, false);
        }
    }

    public function PreviewJSLiveImportFromForm(string $FileData, string $Mappings = '{}', int $Chart = 0, int $Page = 0, bool $Download = false): string
    {
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        $failed = false;
        try {
            try {
                $encoded = $this->PreviewJSLiveImport(EChartsImportJson::Upload($FileData), $Mappings);
                $report = json_decode($encoded, true, 64, JSON_THROW_ON_ERROR);
                $charts = [];
                $sources = [];
                $archives = [];
                foreach ($report['charts'] as $index => $chart) {
                    $charts[] = ['Index' => $index + 1, 'Name' => $chart['name'], 'Family' => $chart['family'],
                        'Target'         => $chart['target']['module'] ?? '—', 'Status' => $this->Translate('Blocked; review differences'),
                        'Sources'        => $this->Translate($chart['verdicts']['sourcesArchive']), 'Formats' => $this->Translate($chart['verdicts']['presentations']), 'Design' => $this->Translate($chart['verdicts']['design'])];
                    foreach ($chart['sources'] as $source) {
                        $sources[$source['sourceKey']] = ['SourceKey' => $source['sourceKey'], 'Label' => $source['label'], 'TargetID' => $source['variableID'] ?? 0];
                        if ($source['archiveKey'] !== '') {
                            $archives[$source['archiveKey']] = ['SourceKey' => $source['archiveKey'], 'TargetID' => $source['archiveID'] ?? 0];
                        }
                    }
                }
                $Chart = max(0, min(count($report['charts']) - 1, $Chart));
                $chart = $report['charts'][$Chart];
                $all = array_merge($chart['fieldCoverage'], $chart['metadataCoverage']);
                $allIssues = array_merge($report['issues'], $chart['issues']);
                $pages = max(1, (int) ceil(max(count($all), count($allIssues)) / 100));
                $Page = max(0, min($pages - 1, $Page));
                $fields = [];
                foreach (array_slice($all, $Page * 100, 100) as $field) {
                    $fields[] = ['Pointer' => $field['pointer'] . (isset($field['elementPointer']) ? '#' . $field['elementPointer'] : ''),
                        'Status'           => $this->Translate($field['status']), 'Target' => $field['target'] ?? '—', 'Rule' => $field['rule'],
                        'Reason'           => $this->Translate($field['reason'])];
                }
                $issues = [];
                foreach (array_slice($allIssues, $Page * 100, 100) as $issue) {
                    $issues[] = ['Code' => $issue['code'], 'Pointer' => $issue['pointer'], 'Message' => $this->Translate($issue['message']),
                        'Resolution'    => $this->Translate($issue['resolution'])];
                }
                $this->UpdateFormField('JSLiveImportCharts', 'values', json_encode($charts, JSON_THROW_ON_ERROR));
                $this->UpdateFormField('JSLiveImportSources', 'values', json_encode(array_values($sources), JSON_THROW_ON_ERROR));
                $this->UpdateFormField('JSLiveImportArchives', 'values', json_encode(array_values($archives), JSON_THROW_ON_ERROR));
                $this->UpdateFormField('JSLiveImportFields', 'values', json_encode($fields, JSON_THROW_ON_ERROR));
                $this->UpdateFormField('JSLiveImportIssues', 'values', json_encode($issues, JSON_THROW_ON_ERROR));
                $draft = EChartsImportJson::Canonical($chart['target']['configuration']);
                $this->UpdateFormField('JSLiveImportDraft', 'caption', mb_substr($draft, 0, 4000) . (mb_strlen($draft) > 4000 ? ' …' : ''));
                $this->UpdateFormField('JSLiveImportChart', 'value', $Chart + 1);
                $this->UpdateFormField('JSLiveImportPage', 'value', $Page + 1);
                $this->UpdateFormField('JSLiveImportSummary', 'caption', sprintf(
                    $this->Translate('%d charts; %d field verdicts; chart %d; report page %d of %d; %d differences for this chart. No objects were created. Plan: %s'),
                    count($charts),
                    $report['summary']['fields'],
                    $Chart + 1,
                    $Page + 1,
                    $pages,
                    count($allIssues),
                    $report['planHash']
                ));
                return $Download ? 'data:application/json;base64,' . base64_encode($encoded) : '';
            } catch (Throwable $exception) {
                $failed = true;
                foreach (['Charts', 'Sources', 'Archives', 'Fields', 'Issues'] as $name) {
                    $this->UpdateFormField('JSLiveImport' . $name, 'values', '[]');
                }
                $this->UpdateFormField('JSLiveImportDraft', 'caption', '');
                $this->UpdateFormField('JSLiveImportSummary', 'caption', $this->Translate('The package could not be analyzed. No objects were changed.'));
                return $exception instanceof \InvalidArgumentException ? $exception->getMessage() : $this->Translate('The package could not be analyzed. No objects were changed.');
            }
        } catch (Throwable $exception) {
            $failed = true;
            throw $exception;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, null, $failed, false);
        }
    }
}
