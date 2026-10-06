<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\DataFlowHelper;
use SymconECharts\EChartsDataProtocol;

require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';

class EChartsGateway extends IPSModuleStrict
{
    use DataFlowHelper;

    private const ARCHIVE_CACHE_BUFFER = 'ArchiveReadCache';
    private const ARCHIVE_CACHE_MAX_ENTRIES = 32;
    private const ARCHIVE_CACHE_TTL_SECONDS = 15;
    private const ARCHIVE_CONTROL_MODULE_ID = '{43192F0B-135B-4CE7-A0A7-1475603F3060}';
    private const ARCHIVE_MAX_LIMIT = 2000;
    private const DATA_ID_FROM_CHILD = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_TO_CHILD = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const WEBHOOK_CONTROL_MODULE_ID = '{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}';
    private const WEBHOOK_NAME = 'SymconECharts';

    /** @var list<int> */
    private const ARCHIVE_AGGREGATION_LEVELS = [0, 1, 5, 6, 8];

    /** @var list<string> */
    private const ARCHIVE_REDUCERS = ['auto', 'average', 'sum', 'minimum', 'maximum'];

    public function Create(): void
    {
        parent::Create();

        $this->RegisterHook(self::WEBHOOK_NAME);
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->SetBuffer(self::ARCHIVE_CACHE_BUFFER, '');
        $this->SetStatus(IS_ACTIVE);
    }

    public function ForwardData(string $JSONString): string
    {
        $operation = 'invalid';

        try {
            $message = $this->DecodeDataFlowMessage($JSONString, self::DATA_ID_FROM_CHILD);
            $request = EChartsDataProtocol::DecodeRequest($message);
            $operation = $request['Operation'];
        } catch (Throwable) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'INVALID_REQUEST',
                'The gateway request is invalid.'
            );
        }

        return match ($operation) {
            EChartsDataProtocol::OPERATION_CURRENT_READ => $this->ReadCurrentValue($request['Payload']),
            EChartsDataProtocol::OPERATION_ARCHIVE_READ => $this->ReadArchiveValues($request['Payload']),
            EChartsDataProtocol::OPERATION_IPSVIEW_PUSH => $this->PushIPSViewMessage($request['Payload']),
            default                                     => EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'UNSUPPORTED_OPERATION',
                'The gateway operation is not supported.'
            )
        };
    }

    protected function ProcessHookData(): void
    {
        $path = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        if (preg_match('#^/hook/SymconECharts/WS/[1-9][0-9]*/[a-f0-9]{32}$#D', $path) === 1) {
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || preg_match(
                '#^/hook/SymconECharts/state/([1-9][0-9]*)/([a-f0-9]{32})$#D',
                $path,
                $matches
            ) !== 1
        ) {
            $this->SendHookResponse(404, '');

            return;
        }

        $request = EChartsDataProtocol::CreateRequest(EChartsDataProtocol::OPERATION_IPSVIEW_STATE, [
            'InstanceID' => (int) $matches[1],
            'Channel'    => $matches[2]
        ]);
        $responses = $this->SendDataToChildren($this->EncodeDataFlowMessage(self::DATA_ID_TO_CHILD, $request));
        if (!is_array($responses)) {
            $this->SendHookResponse(404, '');

            return;
        }

        foreach ($responses as $response) {
            if (!is_string($response) || $response === '') {
                continue;
            }
            try {
                $decoded = EChartsDataProtocol::DecodeResponse(
                    $response,
                    EChartsDataProtocol::OPERATION_IPSVIEW_STATE
                );
                $state = $decoded['Payload']['State'] ?? null;
                if (($decoded['Success'] ?? false) === true && is_array($state)) {
                    $this->SendHookResponse(200, json_encode(
                        $state,
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    ), 'application/json; charset=utf-8');

                    return;
                }
            } catch (Throwable) {
                continue;
            }
        }

        $this->SendHookResponse(404, '');
    }

    /** @param array<string, mixed> $payload */
    private function PushIPSViewMessage(array $payload): string
    {
        $operation = EChartsDataProtocol::OPERATION_IPSVIEW_PUSH;
        $instanceID = $payload['InstanceID'] ?? null;
        $channel = $payload['Channel'] ?? null;
        $message = $payload['Message'] ?? null;
        if (!is_int($instanceID) || $instanceID <= 0
            || !is_string($channel) || preg_match('/^[a-f0-9]{32}$/D', $channel) !== 1
            || !is_array($message)
        ) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'INVALID_IPSVIEW_MESSAGE',
                'The IPSView update is invalid.'
            );
        }

        $webHookControls = IPS_GetInstanceListByModuleID(self::WEBHOOK_CONTROL_MODULE_ID);
        sort($webHookControls, SORT_NUMERIC);
        $webHookControlID = $webHookControls[0] ?? null;
        if (!is_int($webHookControlID) || $webHookControlID <= 0) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'WEBHOOK_CONTROL_NOT_FOUND',
                'No WebHook Control instance is available.'
            );
        }

        try {
            $encodedMessage = json_encode(
                $message,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            WC_PushMessage(
                $webHookControlID,
                '/hook/SymconECharts/WS/' . $instanceID . '/' . $channel,
                $encodedMessage
            );
        } catch (Throwable) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'IPSVIEW_PUSH_FAILED',
                'The IPSView update could not be sent.'
            );
        }

        return EChartsDataProtocol::EncodeSuccessResponse($operation, ['Delivered' => true]);
    }

    private function SendHookResponse(int $status, string $body, string $contentType = 'text/plain; charset=utf-8'): void
    {
        http_response_code($status);
        header('Content-Type: ' . $contentType);
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        echo $body;
    }

    /** @param array<string, mixed> $payload */
    private function ReadCurrentValue(array $payload): string
    {
        $variableID = $payload['VariableID'] ?? null;
        if (!is_int($variableID) || $variableID <= 0) {
            return EChartsDataProtocol::EncodeErrorResponse(
                EChartsDataProtocol::OPERATION_CURRENT_READ,
                'INVALID_VARIABLE_ID',
                'A positive integer VariableID is required.'
            );
        }

        if (!IPS_VariableExists($variableID)) {
            return EChartsDataProtocol::EncodeErrorResponse(
                EChartsDataProtocol::OPERATION_CURRENT_READ,
                'VARIABLE_NOT_FOUND',
                'The selected variable does not exist.'
            );
        }

        $variable = IPS_GetVariable($variableID);
        $variableType = $variable['VariableType'] ?? null;
        if (!in_array($variableType, [1, 2], true)) {
            return EChartsDataProtocol::EncodeErrorResponse(
                EChartsDataProtocol::OPERATION_CURRENT_READ,
                'VARIABLE_NOT_NUMERIC',
                'The selected variable is not numeric.'
            );
        }

        $value = GetValue($variableID);
        if (!is_int($value) && !is_float($value)) {
            return EChartsDataProtocol::EncodeErrorResponse(
                EChartsDataProtocol::OPERATION_CURRENT_READ,
                'INVALID_VARIABLE_VALUE',
                'The selected variable does not contain a numeric value.'
            );
        }

        return EChartsDataProtocol::EncodeSuccessResponse(EChartsDataProtocol::OPERATION_CURRENT_READ, [
            'VariableID'   => $variableID,
            'VariableType' => $variableType,
            'Value'        => $value,
            'Timestamp'    => (int) ($variable['VariableUpdated'] ?? 0)
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function ReadArchiveValues(array $payload): string
    {
        $operation = EChartsDataProtocol::OPERATION_ARCHIVE_READ;
        $variableID = $payload['VariableID'] ?? null;
        if (!is_int($variableID) || $variableID <= 0) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'INVALID_VARIABLE_ID',
                'A positive integer VariableID is required.'
            );
        }
        if (!IPS_VariableExists($variableID)) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'VARIABLE_NOT_FOUND',
                'The selected variable does not exist.'
            );
        }

        $variable = IPS_GetVariable($variableID);
        $variableType = $variable['VariableType'] ?? null;
        if (!in_array($variableType, [1, 2], true)) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'VARIABLE_NOT_NUMERIC',
                'The selected variable is not numeric.'
            );
        }

        $startTimestamp = $payload['StartTimestamp'] ?? null;
        $endTimestamp = $payload['EndTimestamp'] ?? null;
        if (!is_int($startTimestamp) || !is_int($endTimestamp)
            || $startTimestamp <= 0 || $endTimestamp <= $startTimestamp
        ) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'INVALID_TIME_RANGE',
                'StartTimestamp and EndTimestamp must define a positive increasing time range.'
            );
        }

        $mode = $payload['Mode'] ?? null;
        if (!is_string($mode) || !in_array($mode, ['raw', 'aggregated'], true)) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'INVALID_ARCHIVE_MODE',
                'Mode must be raw or aggregated.'
            );
        }

        $reducer = $payload['Reducer'] ?? null;
        if (!is_string($reducer) || !in_array($reducer, self::ARCHIVE_REDUCERS, true)) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'INVALID_ARCHIVE_REDUCER',
                'Reducer must be auto, average, sum, minimum or maximum.'
            );
        }

        $limit = $payload['Limit'] ?? null;
        if (!is_int($limit) || $limit <= 0 || $limit > self::ARCHIVE_MAX_LIMIT) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'INVALID_ARCHIVE_LIMIT',
                'Limit must be between 1 and 2000.'
            );
        }

        $aggregationLevel = null;
        if ($mode === 'aggregated') {
            $aggregationLevel = $payload['AggregationLevel'] ?? null;
            if (!is_int($aggregationLevel)
                || !in_array($aggregationLevel, self::ARCHIVE_AGGREGATION_LEVELS, true)
            ) {
                return EChartsDataProtocol::EncodeErrorResponse(
                    $operation,
                    'INVALID_AGGREGATION_LEVEL',
                    'AggregationLevel must be one of 0, 1, 5, 6 or 8.'
                );
            }
        }

        $normalizedRequest = [
            'VariableID'       => $variableID,
            'StartTimestamp'   => $startTimestamp,
            'EndTimestamp'     => $endTimestamp,
            'Mode'             => $mode,
            'AggregationLevel' => $aggregationLevel,
            'Reducer'          => $reducer,
            'Limit'            => $limit
        ];
        $cacheKey = hash('sha256', json_encode($normalizedRequest, JSON_THROW_ON_ERROR));
        $cachedPayload = $this->ReadArchiveCache($cacheKey);
        if ($cachedPayload !== null) {
            return EChartsDataProtocol::EncodeSuccessResponse($operation, $cachedPayload);
        }

        $archiveIDs = IPS_GetInstanceListByModuleID(self::ARCHIVE_CONTROL_MODULE_ID);
        sort($archiveIDs, SORT_NUMERIC);
        $archiveID = $archiveIDs[0] ?? null;
        if (!is_int($archiveID) || $archiveID <= 0) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'ARCHIVE_NOT_FOUND',
                'No Archive Control instance is available.'
            );
        }

        try {
            $archiveVariables = AC_GetAggregationVariables($archiveID, false);
            $archiveVariable = null;
            foreach ($archiveVariables as $candidate) {
                if (($candidate['VariableID'] ?? null) === $variableID) {
                    $archiveVariable = $candidate;
                    break;
                }
            }
            if ($archiveVariable === null) {
                return EChartsDataProtocol::EncodeErrorResponse(
                    $operation,
                    'SOURCE_NOT_ARCHIVED',
                    'The selected variable has no archive data.'
                );
            }

            $aggregationType = AC_GetAggregationType($archiveID, $variableID);
            if (!in_array($aggregationType, [0, 1], true)) {
                return EChartsDataProtocol::EncodeErrorResponse(
                    $operation,
                    'UNSUPPORTED_ARCHIVE_TYPE',
                    'The selected variable uses an unsupported archive aggregation type.'
                );
            }
            if ($mode === 'aggregated'
                && (($aggregationType === 0 && $reducer === 'sum')
                    || ($aggregationType === 1 && $reducer === 'average'))
            ) {
                return EChartsDataProtocol::EncodeErrorResponse(
                    $operation,
                    'REDUCER_NOT_SUPPORTED',
                    'The selected reducer is not supported by the archive aggregation type.'
                );
            }

            $queryLimit = $limit + 1;
            if ($mode === 'raw') {
                $archiveValues = AC_GetLoggedValues(
                    $archiveID,
                    $variableID,
                    $startTimestamp,
                    $endTimestamp,
                    $queryLimit
                );
                $effectiveReducer = 'raw';
                $valueField = 'Value';
            } else {
                $archiveValues = AC_GetAggregatedValues(
                    $archiveID,
                    $variableID,
                    $aggregationLevel,
                    $startTimestamp,
                    $endTimestamp,
                    $queryLimit
                );
                $effectiveReducer = match ($reducer) {
                    'minimum' => 'minimum',
                    'maximum' => 'maximum',
                    'sum'     => 'sum',
                    default   => $aggregationType === 1 ? 'sum' : 'average'
                };
                $valueField = match ($effectiveReducer) {
                    'minimum' => 'Min',
                    'maximum' => 'Max',
                    default   => 'Avg'
                };
            }
        } catch (Throwable) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'ARCHIVE_QUERY_FAILED',
                'The archive query failed.'
            );
        }

        $truncated = count($archiveValues) > $limit;
        if ($truncated) {
            $archiveValues = array_slice($archiveValues, 0, $limit);
        }

        $points = [];
        foreach (array_reverse($archiveValues) as $archiveValue) {
            $timestamp = $archiveValue['TimeStamp'] ?? null;
            $value = $archiveValue[$valueField] ?? null;
            if (!is_int($timestamp) || $timestamp <= 0
                || (!is_int($value) && !is_float($value))
                || !is_finite((float) $value)
            ) {
                return EChartsDataProtocol::EncodeErrorResponse(
                    $operation,
                    'INVALID_ARCHIVE_DATA',
                    'The archive returned an invalid data row.'
                );
            }
            $points[] = [$timestamp, $value];
        }

        $responsePayload = [
            'VariableID'             => $variableID,
            'VariableType'           => $variableType,
            'ArchiveAggregationType' => $aggregationType === 1 ? 'counter' : 'standard',
            'EffectiveReducer'       => $effectiveReducer,
            'StartTimestamp'         => $startTimestamp,
            'EndTimestamp'           => $endTimestamp,
            'Points'                 => $points,
            'Truncated'              => $truncated
        ];
        $this->WriteArchiveCache($cacheKey, $responsePayload);

        return EChartsDataProtocol::EncodeSuccessResponse($operation, $responsePayload);
    }

    /** @return array<string, mixed>|null */
    private function ReadArchiveCache(string $cacheKey): ?array
    {
        $entries = $this->DecodeArchiveCache();
        $now = microtime(true);
        $result = null;

        foreach ($entries as &$entry) {
            if (($entry['ExpiresAt'] ?? 0) <= $now) {
                continue;
            }
            if (($entry['Key'] ?? '') === $cacheKey && is_array($entry['Payload'] ?? null)) {
                $entry['LastAccess'] = $now;
                $result = $entry['Payload'];
            }
        }
        unset($entry);

        $entries = array_values(array_filter(
            $entries,
            static fn (array $entry): bool => ($entry['ExpiresAt'] ?? 0) > $now
        ));
        $this->EncodeArchiveCache($entries);

        return $result;
    }

    /** @param array<string, mixed> $payload */
    private function WriteArchiveCache(string $cacheKey, array $payload): void
    {
        $now = microtime(true);
        $entries = array_values(array_filter(
            $this->DecodeArchiveCache(),
            static fn (array $entry): bool => ($entry['ExpiresAt'] ?? 0) > $now
                && ($entry['Key'] ?? '') !== $cacheKey
        ));
        $entries[] = [
            'Key'        => $cacheKey,
            'ExpiresAt'  => $now + self::ARCHIVE_CACHE_TTL_SECONDS,
            'LastAccess' => $now,
            'Payload'    => $payload
        ];
        usort(
            $entries,
            static fn (array $left, array $right): int => ($right['LastAccess'] ?? 0)
                <=> ($left['LastAccess'] ?? 0)
        );
        $this->EncodeArchiveCache(array_slice($entries, 0, self::ARCHIVE_CACHE_MAX_ENTRIES));
    }

    /** @return list<array<string, mixed>> */
    private function DecodeArchiveCache(): array
    {
        $raw = $this->GetBuffer(self::ARCHIVE_CACHE_BUFFER);
        if ($raw === '') {
            return [];
        }

        try {
            $entries = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($entries) ? array_values(array_filter($entries, 'is_array')) : [];
    }

    /** @param list<array<string, mixed>> $entries */
    private function EncodeArchiveCache(array $entries): void
    {
        $this->SetBuffer(
            self::ARCHIVE_CACHE_BUFFER,
            json_encode($entries, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION)
        );
    }
}
