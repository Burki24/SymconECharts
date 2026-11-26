<?php

declare(strict_types=1);

use eCharts\EChartsIdentity;
use eCharts\EChartsImportJson;

function IPS_SemaphoreEnter(string $name, int $timeout): bool
{
    assertGatewayGauge($name === 'eCharts.JSLiveImport.1', 'Startup must use the existing import lock.');
    if ($GLOBALS['startupImportLocked'] ?? false) {
        return false;
    }
    $GLOBALS['startupImportLocked'] = true;
    return true;
}

function IPS_SemaphoreLeave(string $name): void
{
    assertGatewayGauge($name === 'eCharts.JSLiveImport.1' && $GLOBALS['startupImportLocked'], 'Unbalanced import lock.');
    $GLOBALS['startupImportLocked'] = false;
}

final class JournalStartupGateway extends EChartsGateway
{
    public int $journalWrites = 0;

    public function StartFrom(string $saved): string
    {
        $this->Create();
        $persistence = json_encode(['attributes' => ['JSLiveImportJournal' => $saved],
            'configuration'                      => (object) []], JSON_THROW_ON_ERROR);
        $migration = $this->Migrate($persistence);
        assertGatewayGauge(strlen($migration) < 1048576, 'Gateway startup exceeded the native 1 MiB output limit during Migrate.');
        assertGatewayGauge($migration === '', 'Journal migration must leave the native persistence envelope alone.');
        parent::WriteAttributeString('JSLiveImportJournal', $saved);
        $this->ApplyChanges();
        assertGatewayGauge($this->GetTestStatus() === IS_ACTIVE, 'The restored gateway must become active.');
        return $this->ReadAttributeString('JSLiveImportJournal');
    }

    protected function WriteAttributeString(string $name, string $value): bool
    {
        if ($name === 'JSLiveImportJournal') {
            assertGatewayGauge($GLOBALS['startupImportLocked'] ?? false, 'Journal writes must be protected against concurrent imports.');
            ++$this->journalWrites;
        }
        return parent::WriteAttributeString($name, $value);
    }
}

$old = $expected = ['version' => 1, 'entries' => []];
for ($i = 1; $i <= 107; ++$i) {
    $id = sprintf('%032x', $i);
    $identity = 'journal-startup-fixture:' . $i;
    $suffix = ':JSLive:1:' . hash('sha256', $identity) . ':' . $id;
    $legacy = EChartsIdentity::LegacyProductName() . $suffix;
    $current = 'eCharts' . $suffix;
    $entry = ['sequence' => $i, 'identity' => $identity, 'revision' => hash('sha256', 'revision' . $i),
        'phase'          => 'complete', 'status' => 'complete', 'moduleID' => 'fixture', 'targetHash' => hash('sha256', 'target' . $i),
        'targetID'       => 20000 + $i, 'creationIntent' => null, 'pending' => null, 'applyIntent' => true, 'marker' => $legacy,
        'snapshot'       => ['info' => $legacy, 'configuration' => ['Title' => $legacy, 'model' => str_repeat('Chart data ', 2200)]],
        'receipt'        => ['outputs' => [['ident' => 'Widget', 'object' => ['ObjectInfo' => $legacy . ':output:Widget']]]]];
    if ($i === 107) {
        $entry['phase'] = 'created';
        $entry['status'] = 'failed';
        $entry['pending'] = ['info' => $legacy, 'configuration' => ['Title' => $legacy]];
    }
    $old['entries'][$id] = $entry;
    $entry['marker'] = $entry['snapshot']['info'] = $current;
    $entry['receipt']['outputs'][0]['object']['ObjectInfo'] = $current . ':output:Widget';
    $entry['v1Seal'] = 0;
    if ($entry['pending'] !== null) {
        $entry['pending']['info'] = $current;
    }
    $expected['entries'][$id] = $entry;
}
$saved = EChartsImportJson::Canonical($old);
assertGatewayGauge(strlen($saved) > 1048576, 'The stored journal itself must exceed the native migration return limit.');
$first = new JournalStartupGateway();
$upgraded = $first->StartFrom($saved);
assertGatewayGauge(
    $first->journalWrites === 1 && $upgraded === EChartsImportJson::Canonical($expected),
    'First startup must migrate ownership fields once and retain all 107 entries, IDs, snapshots, settings and receipts.'
);
$second = new JournalStartupGateway();
assertGatewayGauge(
    $second->StartFrom($upgraded) === $upgraded && $second->journalWrites === 0,
    'A fresh gateway must start from the upgraded journal without rewriting a byte.'
);
$GLOBALS['startupImportLocked'] = true;
$busy = new JournalStartupGateway();
assertGatewayGauge(
    $busy->StartFrom($saved) === $saved && $busy->journalWrites === 0,
    'Startup must preserve the journal when an import is already in progress.'
);
$GLOBALS['startupImportLocked'] = false;
$busy->ApplyChanges();
assertGatewayGauge($busy->journalWrites === 1, 'An uncontended ApplyChanges must retry a deferred journal migration.');
foreach (['{"version":2,"entries":[]}', '{invalid'] as $invalid) {
    $recovery = new JournalStartupGateway();
    assertGatewayGauge(
        $recovery->StartFrom($invalid) === $invalid && $recovery->journalWrites === 0,
        'Unreadable or unsupported journals must survive startup for recovery.'
    );
}
echo "Large legacy journal retained across two gateway starts (107 entries).\n";
