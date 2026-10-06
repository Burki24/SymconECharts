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
        if (!$this->IsIPSViewHTMLPageEnabled() || !$this->HasActiveParent()) {
            return;
        }

        try {
            $response = $this->SendDataToParent($this->EncodeDataFlowMessage(
                self::DATA_ID_TO_PARENT,
                EChartsDataProtocol::CreateRequest(EChartsDataProtocol::OPERATION_IPSVIEW_PUSH, [
                    'InstanceID' => $this->InstanceID,
                    'Channel'    => $this->EChartsIPSViewChannel(),
                    'Message'    => $message
                ])
            ));
            EChartsDataProtocol::DecodeResponse($response, EChartsDataProtocol::OPERATION_IPSVIEW_PUSH);
        } catch (Throwable $exception) {
            $this->SendDebug('PushIPSViewMessage', $exception::class, 0);
        }
    }

    /** @param Closure(): array<string, mixed> $stateBuilder */
    protected function PushEChartsIPSViewState(Closure $stateBuilder): void
    {
        if (!$this->IsIPSViewHTMLPageEnabled()) {
            return;
        }

        $this->PushEChartsIPSViewMessage($stateBuilder());
    }

    /**
     * @param Closure(): array<string, mixed> $stateBuilder
     */
    protected function HandleEChartsIPSViewRequest(
        string $json,
        string $dataID,
        Closure $stateBuilder
    ): string {
        try {
            $message = $this->DecodeDataFlowMessage($json, $dataID);
            $request = EChartsDataProtocol::DecodeRequest($message);
            if ($request['Operation'] !== EChartsDataProtocol::OPERATION_IPSVIEW_STATE) {
                return '';
            }

            $payload = $request['Payload'];
            if (($payload['InstanceID'] ?? null) !== $this->InstanceID
                || !is_string($payload['Channel'] ?? null)
                || !hash_equals($this->EChartsIPSViewChannel(), $payload['Channel'])
                || !$this->IsIPSViewHTMLPageEnabled()
            ) {
                return '';
            }

            return EChartsDataProtocol::EncodeSuccessResponse(
                EChartsDataProtocol::OPERATION_IPSVIEW_STATE,
                ['State' => $stateBuilder()]
            );
        } catch (Throwable $exception) {
            $this->SendDebug('ReceiveIPSViewState', $exception::class, 0);

            return '';
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
