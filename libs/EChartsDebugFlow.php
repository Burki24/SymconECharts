<?php

declare(strict_types=1);

namespace SymconECharts;

/** Explains the project protocol without disclosing chart content or transport channels. */
trait EChartsDebugFlow
{
    /** Routes legacy helper diagnostics through the safe German module envelope. */
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        if (str_starts_with($Message, static::class . ' | ')) {
            return parent::SendDebug($Message, $Data, $Format);
        }
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $standalone = $this->debugFlowCalls === [];
        if ($standalone) {
            $this->DebugFlowStart(__FUNCTION__, __LINE__);
        }
        try {
            $this->DebugFlowEvent($trace[1]['function'] ?? 'Modulhilfe', (int) ($trace[0]['line'] ?? __LINE__), 'FEHLER', 'Die gemeinsame Modulhilfe konnte den Vorgang nicht abschließen; private Diagnoseinhalte bleiben geschützt.');
        } finally {
            if ($standalone) {
                $this->DebugFlowEnd(__FUNCTION__, __LINE__);
            }
        }
        return true;
    }

    /** Keeps the native status transition and explains the resulting module state. */
    private function DebugEChartsSetStatus(int $status): void
    {
        $this->SetStatus($status);
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $this->DebugFlowEvent($caller[1]['function'] ?? __FUNCTION__, $caller[0]['line'] ?? __LINE__, $status >= 200 ? 'FEHLER' : 'AKTION', $status >= 200
            ? 'Diagrammausgabe ist durch einen Konfigurations- oder Datenfehler eingeschränkt; Statuscode bezeichnet die Ursache im Modul.'
            : 'Modulstatus an Symcon übergeben.', ['Statuscode' => $status]);
    }

    /** Sends one existing request and preserves its native response. */
    private function DebugEChartsSendToParent(string $json): string
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $function = $caller[1]['function'] ?? __FUNCTION__;
        $line = $caller[0]['line'] ?? __LINE__;
        $this->DebugFlowEvent($function, $line, 'SENDEN', 'Anfrage an das ECharts-Gateway übergeben; Annahme wird anhand der Antwort geprüft.', $this->DebugEChartsProtocolContext($json));
        $response = $this->SendDataToParent($json);
        $this->DebugEChartsProtocolResult($response, $function, $line);
        return $response;
    }

    /** Describes the actual local protocol result, including rejected requests. */
    private function DebugEChartsProtocolResult(mixed $json, string $function, int $line): void
    {
        if (!is_string($json)) {
            return;
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !array_key_exists('Success', $decoded)) {
            $this->DebugFlowEvent($function, $line, 'EMPFANG', 'Antwort erhalten; Protokollprüfung erfolgt im aufrufenden Ablauf.', ['Bytes' => strlen($json)]);
            return;
        }
        $success = $decoded['Success'] === true;
        $context = $this->DebugEChartsProtocolContext($json);
        $causes = [
            'INVALID_REQUEST'           => 'Anfrage entspricht nicht dem Gateway-Protokoll.',
            'INVALID_VARIABLE_ID'       => 'Eine gültige positive Variablen-ID fehlt.',
            'VARIABLE_NOT_FOUND'        => 'Quellvariable ist nicht vorhanden.',
            'VARIABLE_NOT_NUMERIC'      => 'Quellvariable ist nicht numerisch.',
            'INVALID_VARIABLE_VALUE'    => 'Quellwert ist kein gültiger Messwert.',
            'INVALID_TIME_RANGE'        => 'Abfragezeitraum ist ungültig.',
            'INVALID_ARCHIVE_MODE'      => 'Archivmodus ist ungültig.',
            'INVALID_ARCHIVE_REDUCER'   => 'Archiv-Auswertung ist ungültig.',
            'INVALID_ARCHIVE_LIMIT'     => 'Abfragegrenze liegt außerhalb des erlaubten Bereichs.',
            'INVALID_AGGREGATION_LEVEL' => 'Archiv-Verdichtung ist ungültig.',
            'ARCHIVE_NOT_FOUND'         => 'Archive Control ist nicht vorhanden.',
            'SOURCE_NOT_ARCHIVED'       => 'Für diese Quelle liegen keine Archivdaten vor.',
            'UNSUPPORTED_ARCHIVE_TYPE'  => 'Archivtyp wird nicht unterstützt.',
            'REDUCER_NOT_SUPPORTED'     => 'Auswertung passt nicht zum Archivtyp.',
            'ARCHIVE_QUERY_FAILED'      => 'Archive Control konnte die Abfrage nicht ausführen.',
            'INVALID_ARCHIVE_DATA'      => 'Archive Control lieferte ungültige Daten.',
            'INVALID_IPSVIEW_MESSAGE'   => 'Aktualisierung entspricht nicht dem IPSView-Protokoll.',
            'WEBHOOK_CONTROL_NOT_FOUND' => 'WebHook Control ist nicht vorhanden.',
            'IPSVIEW_PUSH_FAILED'       => 'Aktualisierung konnte nicht an WebHook Control übergeben werden.'
        ];
        $errorCode = $decoded['Error']['Code'] ?? null;
        if (is_string($errorCode) && isset($causes[$errorCode])) {
            $context['Ursache'] = $causes[$errorCode];
        }
        $payload = $decoded['Payload'] ?? [];
        if (is_array($payload)) {
            foreach (['Value', 'VariableID', 'Delivered', 'Truncated'] as $key) {
                if (isset($payload[$key]) && (is_int($payload[$key]) || is_float($payload[$key]) || is_bool($payload[$key]))) {
                    $context[$key] = $payload[$key];
                }
            }
            foreach (['Points', 'States'] as $key) {
                if (is_array($payload[$key] ?? null)) {
                    $context['Anzahl' . $key] = count($payload[$key]);
                }
            }
        }
        $this->DebugFlowEvent($function, $line, $success ? 'EMPFANG' : 'FEHLER', $success
            ? 'Gateway-Antwort meldet erfolgreiche Verarbeitung; eine Darstellung im Browser ist damit noch nicht bestätigt.'
            : 'Gateway hat die Anfrage abgelehnt; es werden keine Ersatzwerte als Messwerte ausgegeben.', $context);
    }

    /** Selects only fixed operations and numeric source identities, never arbitrary payload fields. */
    private function DebugEChartsProtocolContext(string $json): array
    {
        $decoded = json_decode($json, true);
        $context = ['Schnittstelle' => 'ECharts-Gateway', 'Bytes' => strlen($json)];
        if (!is_array($decoded)) {
            return $context;
        }
        $operation = $decoded['Operation'] ?? null;
        if (in_array($operation, ['current.read', 'archive.read', 'archive.states.read', 'ipsview.push', 'ipsview.state'], true)) {
            $context['Operation'] = $operation;
        }
        $payload = $decoded['Payload'] ?? [];
        if (is_array($payload)) {
            foreach (['VariableID', 'InstanceID', 'StartTimestamp', 'EndTimestamp', 'Limit'] as $key) {
                if (is_int($payload[$key] ?? null)) {
                    $context[$key] = $payload[$key];
                }
            }
        }
        return $context;
    }
}
