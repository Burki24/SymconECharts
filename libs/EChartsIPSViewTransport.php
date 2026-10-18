<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;
use RuntimeException;
use Throwable;

/**
 * Adds the shared persistent IPSView transport to ECharts device modules.
 */
trait EChartsIPSViewTransport
{
    private const ECHARTS_IPSVIEW_CHANNEL_ATTRIBUTE = 'IPSViewTransportChannel';
    private const ECHARTS_IPSVIEW_HOOK = '/hook/SymconECharts';

    protected function RegisterEChartsIPSViewTransport(): void
    {
        $this->RegisterAttributeString(self::ECHARTS_IPSVIEW_CHANNEL_ATTRIBUTE, '');
    }

    /** @return array{statePath:string,socketPath:string} */
    protected function EChartsIPSViewTransportOptions(): array
    {
        $channel = $this->EChartsIPSViewChannel();
        $suffix = '/' . $this->InstanceID . '/' . $channel;

        return [
            'statePath'  => self::ECHARTS_IPSVIEW_HOOK . '/state' . $suffix,
            'socketPath' => self::ECHARTS_IPSVIEW_HOOK . '/WS' . $suffix
        ];
    }

    protected function EChartsIPSViewTransportJavaScript(): string
    {
        $script = file_get_contents(__DIR__ . '/echarts/ipsview-transport.js');
        if (!is_string($script) || $script === '') {
            throw new RuntimeException('The IPSView transport asset could not be loaded.');
        }

        return $script;
    }

    /** @param array<string, mixed> $message */
    protected function PushEChartsIPSViewMessage(array $message): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            if (!$this->IsIPSViewHTMLPageEnabled() || !$this->HasActiveParent()) {
                return;
            }

            try {
                $response = $this->DebugEChartsSendToParent($this->EncodeDataFlowMessage(
                    self::DATA_ID_TO_PARENT,
                    EChartsDataProtocol::CreateRequest(EChartsDataProtocol::OPERATION_IPSVIEW_PUSH, [
                        'InstanceID' => $this->InstanceID,
                        'Channel'    => $this->EChartsIPSViewChannel(),
                        'Message'    => $message
                    ])
                ));
                EChartsDataProtocol::DecodeResponse($response, EChartsDataProtocol::OPERATION_IPSVIEW_PUSH);
            } catch (Throwable $exception) {
                $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    /** @param Closure(): array<string, mixed> $stateBuilder */
    protected function PushEChartsIPSViewState(Closure $stateBuilder): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            if (!$this->IsIPSViewHTMLPageEnabled()) {
                return;
            }

            $this->PushEChartsIPSViewMessage($stateBuilder());
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    /**
     * @param Closure(array<string,mixed>): array<string, mixed> $stateBuilder
     */
    protected function HandleEChartsIPSViewRequest(
        string $json,
        string $dataID,
        Closure $stateBuilder,
        bool $allowTile = false
    ): string {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            try {
                $message = $this->DecodeDataFlowMessage($json, $dataID);
                $request = EChartsDataProtocol::DecodeRequest($message);
                if ($request['Operation'] !== EChartsDataProtocol::OPERATION_IPSVIEW_STATE) {
                    return $debugFlowResult = '';
                }

                $payload = $request['Payload'];
                $output = $payload['Output'] ?? 'ipsview';
                $offset = $payload['WindowOffset'] ?? 0;
                if (($payload['InstanceID'] ?? null) !== $this->InstanceID
                    || !is_string($payload['Channel'] ?? null)
                    || !hash_equals($this->EChartsIPSViewChannel(), $payload['Channel'])
                    || !in_array($output, ['tile', 'ipsview'], true)
                    || !is_int($offset) || $offset < 0 || $offset > EChartsDataProtocol::MAX_STATE_WINDOW_OFFSET
                    || ($output === 'tile'
                        ? !$allowTile : !$this->IsIPSViewHTMLPageEnabled())
                ) {
                    return $debugFlowResult = '';
                }

                return $debugFlowResult = EChartsDataProtocol::EncodeSuccessResponse(
                    EChartsDataProtocol::OPERATION_IPSVIEW_STATE,
                    ['State' => $stateBuilder($payload)]
                );
            } catch (Throwable $exception) {
                $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);

                return $debugFlowResult = '';
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    private function EChartsIPSViewChannel(): string
    {
        $channel = $this->ReadAttributeString(self::ECHARTS_IPSVIEW_CHANNEL_ATTRIBUTE);
        if (preg_match('/^[a-f0-9]{32}$/D', $channel) === 1) {
            return $channel;
        }

        try {
            $channel = bin2hex(random_bytes(16));
        } catch (Throwable $exception) {
            throw new RuntimeException('The IPSView transport channel could not be created.', 0, $exception);
        }
        $this->WriteAttributeString(self::ECHARTS_IPSVIEW_CHANNEL_ATTRIBUTE, $channel);

        return $channel;
    }
}
