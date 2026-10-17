<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsCandlestick/CandlestickData.php';

use SymconECharts\CandlestickData;

function candleAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$window = CandlestickData::Window('24h', 'hour', strtotime('2026-10-17 12:34:00 Europe/Berlin'), 'Europe/Berlin');
candleAssert(count($window['Intervals']) === 24 && $window['EndTimestamp'] === strtotime('2026-10-17 12:00:00 Europe/Berlin') - 1, 'Only completed hours.');
$start = $window['StartTimestamp'];
$data = CandlestickData::Summarize([[$start, 2], [$start + 1, -4], [$start + 2, 9], [$start + 3, 3], [$start + 7200, 0]], $window);
candleAssert($data[0]['statistics'] === [2.0, 3.0, -4.0, 9.0] && $data[0]['count'] === 4, 'OCLH uses actual first/last samples.');
candleAssert($data[1]['statistics'] === null && $data[2]['statistics'] === [0.0, 0.0, 0.0, 0.0], 'Missing is not zero; single zero is a doji.');
foreach (['2026-03-30' => 23, '2026-10-26' => 25] as $date => $hours) {
    $day = CandlestickData::Window('24h', 'day', strtotime($date . ' 12:00 Europe/Berlin'), 'Europe/Berlin');
    candleAssert(count($day['Intervals']) === 1 && $day['EndTimestamp'] - $day['StartTimestamp'] + 1 === $hours * 3600, 'Local days honor DST.');
}
$fall = CandlestickData::Window('24h', 'hour', strtotime('2026-10-25 12:00 Europe/Berlin'), 'Europe/Berlin');
$labels = array_column($fall['Intervals'], 'label');
candleAssert(count(array_unique($labels)) === 24, 'Repeated local hour has distinct offset.');
$calls = 0;
$points = CandlestickData::Read(1, 100, 10099, static function (array $request) use (&$calls): array
{
    ++$calls;
    $all = [];
    for ($time = $request['StartTimestamp']; $time <= $request['EndTimestamp']; ++$time) {
        $all[] = [$time, (float) $time];
    }
    return ['VariableID'         => 1, 'VariableType' => 2, 'StartTimestamp' => $request['StartTimestamp'], 'EndTimestamp' => $request['EndTimestamp'],
        'ArchiveAggregationType' => 'standard', 'EffectiveReducer' => 'raw', 'Truncated' => count($all) > 2000, 'Points' => array_slice($all, -2000)];
});
candleAssert(count($points) === 10000 && $points[0] === [100, 100.0] && $points[9999] === [10099, 10099.0] && $calls === 15, 'Split windows preserve all samples.');
foreach ([[[$start, NAN]], [[$start, 1], [$start, 2]], [[$start - 1, 1]], [[$start + 1, 2], [$start, 1]]] as $bad) {
    try {
        CandlestickData::Summarize($bad, $window);
        throw new LogicException('Invalid samples accepted.');
    } catch (InvalidArgumentException) {
    }
}
$calls = 0;
try {
    CandlestickData::Read(1, 100, 9999999, static function (array $r) use (&$calls): array
    {
        ++$calls;
        return ['VariableID'         => 1, 'VariableType' => 2, 'StartTimestamp' => $r['StartTimestamp'], 'EndTimestamp' => $r['EndTimestamp'],
            'ArchiveAggregationType' => 'standard', 'EffectiveReducer' => 'raw', 'Truncated' => true, 'Points' => []];
    });
    throw new LogicException('Unlimited truncation accepted.');
} catch (RuntimeException $e) {
    candleAssert($calls <= 63 && str_contains($e->getMessage(), 'dense'), 'Hard request limit with visible failure.');
}
$calls = 0;
try {
    CandlestickData::Read(1, 100, 200099, static function (array $r) use (&$calls): array
    {
        ++$calls;
        $count = $r['EndTimestamp'] - $r['StartTimestamp'] + 1;
        $values = [];
        for ($time = max($r['StartTimestamp'], $r['EndTimestamp'] - 1999); $time <= $r['EndTimestamp']; ++$time) {
            $values[] = [$time, 1.0];
        }
        return ['VariableID'         => 1, 'VariableType' => 2, 'StartTimestamp' => $r['StartTimestamp'], 'EndTimestamp' => $r['EndTimestamp'],
            'ArchiveAggregationType' => 'standard', 'EffectiveReducer' => 'raw', 'Truncated' => $count > 2000, 'Points' => $values];
    });
    throw new LogicException('Request budget exceeded without failure.');
} catch (RuntimeException $exception) {
    candleAssert($calls === 63 && str_contains($exception->getMessage(), 'dense'), 'Exactly 63 gateway calls maximum.');
}
echo "Candlestick data tests passed.\n";
