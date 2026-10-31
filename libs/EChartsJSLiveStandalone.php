<?php

declare(strict_types=1);

namespace SymconECharts;

require_once __DIR__ . '/helper/DebugFlowHelper.php';
require_once __DIR__ . '/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/helper/DataFlowHelper.php';
require_once __DIR__ . '/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/EChartsDebugFlow.php';
require_once __DIR__ . '/EChartsIPSViewTransport.php';
require_once __DIR__ . '/EChartsDataProtocol.php';
require_once __DIR__ . '/EChartsJSLiveTarget.php';

/** Shared native lifecycle for the two new explicit families; no universal widget or family selector. */
trait EChartsJSLiveStandalone
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \Burki24\SymconModuleHelper\ConfigurationFormHelper;
    use \Burki24\SymconModuleHelper\DataFlowHelper;
    use \Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
    use \Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
    use EChartsDebugFlow;
    use EChartsIPSViewTransport;
    use EChartsJSLiveTarget;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';

    public function Create(): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function (): void
        {
            parent::Create();
            $this->RegisterJSLiveTarget();
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyBoolean('IPSViewUseTileDesign', true);
            $this->RegisterAttributeString('LastError', '');
            $this->RegisterIPSViewHTMLPageProperties();
            $this->RegisterEChartsIPSViewTransport();
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->SetVisualizationType(1);
            $this->SealJSLiveCreationDefaults();
        }, true);
    }

    public function ApplyChanges(): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function (): void
        {
            parent::ApplyChanges();
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Chart for IPSView'), 90);
            $this->InitializeJSLiveImport();
            if (IPS_GetKernelRunlevel() === KR_READY) {
                $this->RefreshJSLiveImport();
                if ($this->IsIPSViewHTMLPageEnabled()) {
                    $this->UpdateIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->GetIPSViewHTML());
                }
            }
        }, true);
    }

    public function GetCompatibleParents(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => json_encode(['type' => 'connect', 'moduleIDs' => [self::GATEWAY_MODULE_ID]], JSON_THROW_ON_ERROR));
    }

    public function GetConfigurationForm(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => $this->HasJSLiveImport()
            ? $this->JSLiveConfigurationForm() : $this->EncodeConfigurationForm($this->LoadConfigurationForm()));
    }

    public function GetVisualizationTile(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => $this->RenderJSLiveImportPage(false));
    }

    public function GetIPSViewHTML(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => $this->RenderJSLiveImportPage(true));
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (): array => $this->BuildJSLiveImportState(true)
        ));
    }

    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, fn () => $this->HandleJSLiveImportMessage($SenderID, $Message), true);
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($Ident, $Value): void
        {
            if (!$this->HandleIPSViewHTMLPageAction($Ident, $Value)) {
                throw new \InvalidArgumentException('Unknown action.');
            }
        }, true);
    }
}
