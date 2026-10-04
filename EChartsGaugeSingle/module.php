<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\SVGPreviewHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use SymconECharts\EChartsAsset;
use SymconECharts\EChartsDataProtocol;
use SymconECharts\EChartsGaugeSinglePreview;
use SymconECharts\EChartsSvgPath;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsSvgPath.php';
require_once __DIR__ . '/GaugePreview.php';

class EChartsGaugeSingle extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use IPSViewHTMLPageHelper;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';

    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_RANGE_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_DESIGN_INVALID = 205;
    private const STATUS_THEME_INVALID = 206;

    private const PRESET_SIMPLE = 'simple';
    private const SUPPORTED_PRESETS = ['basic', self::PRESET_SIMPLE, 'progress', 'speed'];
    private const SUPPORTED_POINTER_SHAPES = ['preset', 'needle', 'line', 'arrow', 'custom'];
    private const SUPPORTED_ARC_MODES = ['preset', 'full', 'three-quarter', 'half', 'quarter', 'custom'];
    private const SUPPORTED_COLOR_MODES = ['theme', 'custom'];
    private const ANGLE_STEP = 22.5;
    private const DESIGN_SCALE_DEFAULT = 100;
    private const DESIGN_SCALE_MINIMUM = 50;
    private const DESIGN_SCALE_MAXIMUM = 150;
    private const DESIGN_SCALE_PROPERTIES = [
        'scaleFontSizePercent'    => 'ScaleFontSizePercent',
        'valueFontSizePercent'    => 'ValueFontSizePercent',
        'unitFontSizePercent'     => 'UnitFontSizePercent',
        'titleFontSizePercent'    => 'TitleFontSizePercent',
        'ringWidthPercent'        => 'RingWidthPercent',
        'pointerWidthPercent'     => 'PointerWidthPercent',
        'minorTickLengthPercent'  => 'MinorTickLengthPercent',
        'majorTickLengthPercent'  => 'MajorTickLengthPercent'
    ];

    public function Create(): void
    {
        parent::Create();

        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterPropertyInteger('SourceVariableID', 0);
        $this->RegisterPropertyFloat('Minimum', 0.0);
        $this->RegisterPropertyFloat('Maximum', 100.0);
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Unit', '');
        $this->RegisterPropertyInteger('Decimals', 1);
        $this->RegisterPropertyString('GaugePreset', self::PRESET_SIMPLE);
        $this->RegisterPropertyString('EChartsTheme', EChartsAsset::THEME_AUTO);
        $this->RegisterPropertyString('PointerShape', 'preset');
        $this->RegisterPropertyString('CustomPointerSVG', '');
        $this->RegisterPropertyString('GaugeArcMode', 'preset');
        $this->RegisterPropertyFloat('GaugeStartPosition', 270.0);
        $this->RegisterPropertyFloat('GaugeEndPosition', 90.0);
        $this->RegisterPropertyString('GaugeColorMode', 'theme');
        $this->RegisterPropertyInteger('PointerColor', 0x55CBB5);
        $this->RegisterPropertyInteger('ProgressColor', 0x55CBB5);
        $this->RegisterPropertyInteger('RingColor', 0x45474C);
        $this->RegisterPropertyInteger('ScaleColor', 0xA7A9AE);
        $this->RegisterPropertyInteger('ValueColor', 0xF4F5F7);
        $this->RegisterPropertyInteger('TitleColor', 0xA7A9AE);
        foreach (self::DESIGN_SCALE_PROPERTIES as $propertyName) {
            $this->RegisterPropertyInteger($propertyName, self::DESIGN_SCALE_DEFAULT);
        }
        $this->RegisterAttributeInteger('RegisteredSourceVariableID', 0);
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->Initialize();
        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->PublishVisualizationState();
        }
    }

    public function GetCompatibleParents(): string
    {
        return json_encode([
            'type'      => 'connect',
            'moduleIDs' => [self::GATEWAY_MODULE_ID]
        ], JSON_THROW_ON_ERROR);
    }

    public function GetConfigurationForm(): string
    {
        $form = $this->LoadConfigurationForm();
        $form = SVGPreviewHelper::withImage(
            $form,
            'GaugePreview',
            $this->BuildGaugePreviewSvg(
                $this->ReadPropertyInteger('SourceVariableID'),
                $this->ReadPropertyFloat('Minimum'),
                $this->ReadPropertyFloat('Maximum'),
                $this->ReadPropertyString('Title'),
                $this->ReadPropertyString('Unit'),
                $this->ReadPropertyInteger('Decimals'),
                $this->ReadPropertyString('GaugePreset'),
                $this->ReadPropertyString('EChartsTheme'),
                $this->ReadGaugeStyle()
            )
        );

        return $this->EncodeConfigurationForm($form);
    }

    /**
     * Refreshes the configuration-form preview with values that are not persisted yet.
     */
    public function UpdateGaugePreview(
        int $VariableID,
        float $Minimum,
        float $Maximum,
        string $Title,
        string $Unit,
        int $Decimals,
        string $GaugePreset = self::PRESET_SIMPLE,
        string $EChartsTheme = EChartsAsset::THEME_AUTO,
        int $ScaleFontSizePercent = self::DESIGN_SCALE_DEFAULT,
        int $ValueFontSizePercent = self::DESIGN_SCALE_DEFAULT,
        int $UnitFontSizePercent = self::DESIGN_SCALE_DEFAULT,
        int $TitleFontSizePercent = self::DESIGN_SCALE_DEFAULT,
        int $RingWidthPercent = self::DESIGN_SCALE_DEFAULT,
        int $PointerWidthPercent = self::DESIGN_SCALE_DEFAULT,
        int $MinorTickLengthPercent = self::DESIGN_SCALE_DEFAULT,
        int $MajorTickLengthPercent = self::DESIGN_SCALE_DEFAULT,
        string $PointerShape = 'preset',
        string $GaugeArcMode = 'preset',
        float $GaugeStartPosition = 270.0,
        float $GaugeEndPosition = 90.0,
        string $GaugeColorMode = 'theme',
        int $PointerColor = 0x55CBB5,
        int $ProgressColor = 0x55CBB5,
        int $RingColor = 0x45474C,
        int $ScaleColor = 0xA7A9AE,
        int $ValueColor = 0xF4F5F7,
        int $TitleColor = 0xA7A9AE,
        string $CustomPointerSVG = ''
    ): void {
        $this->UpdateFormField(
            'GaugePreview',
            'image',
            SVGPreviewHelper::dataUri($this->BuildGaugePreviewSvg(
                $VariableID,
                $Minimum,
                $Maximum,
                $Title,
                $Unit,
                $Decimals,
                $GaugePreset,
                $EChartsTheme,
                [
                    'scaleFontSizePercent'   => $ScaleFontSizePercent,
                    'valueFontSizePercent'   => $ValueFontSizePercent,
                    'unitFontSizePercent'    => $UnitFontSizePercent,
                    'titleFontSizePercent'   => $TitleFontSizePercent,
                    'ringWidthPercent'       => $RingWidthPercent,
                    'pointerWidthPercent'    => $PointerWidthPercent,
                    'minorTickLengthPercent' => $MinorTickLengthPercent,
                    'majorTickLengthPercent' => $MajorTickLengthPercent,
                    'pointerShape'           => $PointerShape,
                    'arcMode'                => $GaugeArcMode,
                    'startPosition'          => $GaugeStartPosition,
                    'endPosition'            => $GaugeEndPosition,
                    'colorMode'              => $GaugeColorMode,
                    'pointerColor'           => self::ColorToHex($PointerColor),
                    'progressColor'          => self::ColorToHex($ProgressColor),
                    'ringColor'              => self::ColorToHex($RingColor),
                    'scaleColor'             => self::ColorToHex($ScaleColor),
                    'valueColor'             => self::ColorToHex($ValueColor),
                    'titleColor'             => self::ColorToHex($TitleColor),
                    ...$this->ResolveCustomPointerStyle($PointerShape, $CustomPointerSVG)
                ]
            ))
        );
    }

    public function GetGaugeData(): string
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            $this->WriteAttributeString('LastError', $configurationError['Message']);
            $this->SetStatus($configurationError['Status']);
            throw new RuntimeException($configurationError['Message']);
        }
        if (!$this->HasActiveParent()) {
            $this->WriteAttributeString('LastError', 'No active EChartsGateway is connected.');
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            throw new RuntimeException('No active EChartsGateway is connected.');
        }

        $operation = EChartsDataProtocol::OPERATION_CURRENT_READ;
        $request = EChartsDataProtocol::CreateRequest($operation, [
            'VariableID' => $this->ReadPropertyInteger('SourceVariableID')
        ]);

        try {
            $response = EChartsDataProtocol::DecodeResponse(
                $this->SendDataToParent($this->EncodeDataFlowMessage(self::DATA_ID_TO_PARENT, $request)),
                $operation
            );
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', 'INVALID_GATEWAY_RESPONSE');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            $this->SendDebug('GetGaugeData', $exception::class, 0);
            throw new RuntimeException('The gateway returned an invalid response.');
        }

        if (!$response['Success']) {
            $errorCode = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
            $this->WriteAttributeString('LastError', $errorCode);
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway request failed: ' . $errorCode);
        }

        $payload = $response['Payload'];
        $value = $payload['Value'] ?? null;
        $timestamp = $payload['Timestamp'] ?? null;
        if ((!is_int($value) && !is_float($value)) || !is_int($timestamp)) {
            $this->WriteAttributeString('LastError', 'INVALID_CURRENT_VALUE');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned invalid current-value data.');
        }

        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'gauge',
            'theme'         => $this->ReadPropertyString('EChartsTheme'),
            'source'        => [
                'variableID' => $this->ReadPropertyInteger('SourceVariableID'),
                'timestamp'  => $timestamp
            ],
            'gauge'         => [
                'title'    => $this->ReadPropertyString('Title'),
                'minimum'  => $this->ReadPropertyFloat('Minimum'),
                'maximum'  => $this->ReadPropertyFloat('Maximum'),
                'unit'     => $this->ReadPropertyString('Unit'),
                'decimals' => $this->ReadPropertyInteger('Decimals'),
                'preset'   => $this->ReadPropertyString('GaugePreset'),
                'style'    => $this->ReadGaugeStyle()
            ],
            'value'         => (float) $value
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderVisualizationHTMLPage(false, [
            'language'           => $this->NormalizeHelperTranslationLanguage(
                $this->ResolveHelperTranslationLanguage()
            ),
            'title'              => 'ECharts Gauge Single',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-gauge-root', 'echarts-gauge'),
            'state'              => $this->BuildVisualizationState(),
            'translations'       => $this->IPSViewTranslationsFor([
                'Configure a numeric source variable.',
                'Configure a valid Gauge range.',
                'Configure a valid Gauge design.',
                'Configure a valid ECharts theme.',
                'Connect an active EChartsGateway.',
                'The Gauge value could not be loaded.',
                'Apache ECharts could not be initialized.'
            ]),
            'options'            => [
                'echartsVersion' => EChartsAsset::VERSION,
                'echartsThemes'  => EChartsAsset::ThemePalettes()
            ],
            'replacements'       => [
                '{{ECHARTS_SCRIPT}}'       => EChartsAsset::JavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}' => EChartsAsset::ThemeJavaScript()
            ]
        ]);
    }

    public function ReceiveData(string $JSONString): string
    {
        try {
            $message = $this->DecodeDataFlowMessage($JSONString, self::DATA_ID_FROM_PARENT);
            EChartsDataProtocol::DecodeRequest($message);
        } catch (Throwable $exception) {
            $this->SendDebug('ReceiveData', $exception::class, 0);
        }

        return '';
    }

    /**
     * Revalidates the module after the Symcon kernel becomes ready.
     *
     * @param array<int, mixed> $Data
     */
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        if ($SenderID === 0 && $Message === IPS_KERNELSTARTED) {
            $this->Initialize();
            $this->PublishVisualizationState();

            return;
        }

        if ($Message === VM_UPDATE && $SenderID === $this->ReadPropertyInteger('SourceVariableID')) {
            $this->PublishVisualizationState();
        }
    }

    private function Initialize(): void
    {
        $this->SetStatus(IS_INACTIVE);
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

        $this->SetSummary('');
        $this->SynchronizeSourceReference();

        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            $this->WriteAttributeString('LastError', $configurationError['Message']);
            $this->SetStatus($configurationError['Status']);
            return;
        }

        if (!$this->HasActiveParent()) {
            $this->WriteAttributeString('LastError', 'No active EChartsGateway is connected.');
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            return;
        }

        $this->WriteAttributeString('LastError', '');
        $this->SetSummary(IPS_GetName($this->ReadPropertyInteger('SourceVariableID')));
        $this->SetStatus(IS_ACTIVE);
    }

    private function BuildGaugePreviewSvg(
        int $variableID,
        float $minimum,
        float $maximum,
        string $title,
        string $unit,
        int $decimals,
        string $preset,
        string $theme,
        array $style
    ): string {
        if (!is_finite($minimum) || !is_finite($maximum) || $minimum >= $maximum) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Minimum must be lower than maximum.')
            );
        }
        if (!in_array($preset, self::SUPPORTED_PRESETS, true)) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Select a supported Gauge preset.')
            );
        }
        if (!EChartsAsset::IsSupportedTheme($theme)) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Select a supported ECharts theme.')
            );
        }
        if (!in_array((string) ($style['pointerShape'] ?? 'preset'), self::SUPPORTED_POINTER_SHAPES, true)
            || !in_array((string) ($style['arcMode'] ?? 'preset'), self::SUPPORTED_ARC_MODES, true)
            || !in_array((string) ($style['colorMode'] ?? 'theme'), self::SUPPORTED_COLOR_MODES, true)) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Select supported Gauge design options.')
            );
        }
        if (($style['pointerShape'] ?? 'preset') === 'custom'
            && ((string) ($style['pointerPath'] ?? '') === '' || (string) ($style['pointerViewBox'] ?? '') === '')) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate((string) ($style['pointerError'] ?? 'Select a supported path-only SVG pointer.'))
            );
        }
        $startPosition = (float) ($style['startPosition'] ?? 270.0);
        $endPosition = (float) ($style['endPosition'] ?? 90.0);
        if (!self::IsAnglePosition($startPosition)
            || !self::IsAnglePosition($endPosition)
            || (($style['arcMode'] ?? 'preset') === 'custom' && abs($startPosition - $endPosition) < 0.000001)) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Select distinct start and end positions in 22.5 degree steps.')
            );
        }

        $value = $this->ResolveGaugePreviewValue($variableID, $minimum, $maximum);
        $language = $this->NormalizeHelperTranslationLanguage(
            $this->ResolveHelperTranslationLanguage()
        );

        return EChartsGaugeSinglePreview::CreateSvg(
            $value,
            $minimum,
            $maximum,
            $title,
            $unit,
            $decimals,
            $language,
            $preset,
            $theme,
            $style
        );
    }

    private function ResolveGaugePreviewValue(int $variableID, float $minimum, float $maximum): float
    {
        if ($variableID > 0 && IPS_VariableExists($variableID)) {
            try {
                $variable = IPS_GetVariable($variableID);
                if (in_array($variable['VariableType'] ?? null, [1, 2], true)) {
                    $value = GetValue($variableID);
                    if ((is_int($value) || is_float($value)) && is_finite((float) $value)) {
                        return (float) $value;
                    }
                }
            } catch (Throwable $exception) {
                $this->SendDebug('ResolveGaugePreviewValue', $exception::class, 0);
            }
        }

        return $minimum + (($maximum - $minimum) / 2);
    }

    /**
     * Returns the first invalid configuration field and its module status.
     *
     * @return array{Status: int, Message: string}|null
     */
    private function GetConfigurationError(): ?array
    {
        $variableID = $this->ReadPropertyInteger('SourceVariableID');
        if ($variableID <= 0 || !IPS_VariableExists($variableID)) {
            return [
                'Status'  => self::STATUS_SOURCE_INVALID,
                'Message' => 'Select an existing numeric source variable.'
            ];
        }

        $variable = IPS_GetVariable($variableID);
        if (!in_array($variable['VariableType'] ?? null, [1, 2], true)) {
            return [
                'Status'  => self::STATUS_SOURCE_INVALID,
                'Message' => 'The selected source variable must be numeric.'
            ];
        }

        if ($this->ReadPropertyFloat('Minimum') >= $this->ReadPropertyFloat('Maximum')) {
            return [
                'Status'  => self::STATUS_RANGE_INVALID,
                'Message' => 'Gauge range minimum must be lower than maximum.'
            ];
        }

        $decimals = $this->ReadPropertyInteger('Decimals');
        if ($decimals < 0 || $decimals > 6) {
            return [
                'Status'  => self::STATUS_RANGE_INVALID,
                'Message' => 'Gauge decimals must be between 0 and 6.'
            ];
        }

        if (!in_array($this->ReadPropertyString('GaugePreset'), self::SUPPORTED_PRESETS, true)) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'The selected Gauge preset is not supported.'
            ];
        }

        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString('EChartsTheme'))) {
            return [
                'Status'  => self::STATUS_THEME_INVALID,
                'Message' => 'The selected ECharts theme is not supported.'
            ];
        }

        foreach (self::DESIGN_SCALE_PROPERTIES as $propertyName) {
            $value = $this->ReadPropertyInteger($propertyName);
            if ($value < self::DESIGN_SCALE_MINIMUM || $value > self::DESIGN_SCALE_MAXIMUM) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'Gauge design scale values must be between 50 and 150 percent.'
                ];
            }
        }

        if (!in_array($this->ReadPropertyString('PointerShape'), self::SUPPORTED_POINTER_SHAPES, true)
            || !in_array($this->ReadPropertyString('GaugeArcMode'), self::SUPPORTED_ARC_MODES, true)
            || !in_array($this->ReadPropertyString('GaugeColorMode'), self::SUPPORTED_COLOR_MODES, true)) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'The selected Gauge design option is not supported.'
            ];
        }

        if ($this->ReadPropertyString('PointerShape') === 'custom') {
            try {
                EChartsSvgPath::Import($this->ReadPropertyString('CustomPointerSVG'));
            } catch (InvalidArgumentException $exception) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => $exception->getMessage()
                ];
            }
        }

        if (!self::IsAnglePosition($this->ReadPropertyFloat('GaugeStartPosition'))
            || !self::IsAnglePosition($this->ReadPropertyFloat('GaugeEndPosition'))
            || ($this->ReadPropertyString('GaugeArcMode') === 'custom'
                && abs($this->ReadPropertyFloat('GaugeStartPosition') - $this->ReadPropertyFloat('GaugeEndPosition')) < 0.000001)) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'Gauge positions must be distinct 22.5 degree steps between 0 and 337.5 degrees.'
            ];
        }

        foreach (['PointerColor', 'ProgressColor', 'RingColor', 'ScaleColor', 'ValueColor', 'TitleColor'] as $propertyName) {
            $color = $this->ReadPropertyInteger($propertyName);
            if ($color < 0 || $color > 0xFFFFFF) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'Gauge colors must be valid RGB colors.'
                ];
            }
        }

        return null;
    }

    /** @return array<string, int|float|string> */
    private function ReadGaugeStyle(): array
    {
        $style = [];
        foreach (self::DESIGN_SCALE_PROPERTIES as $fieldName => $propertyName) {
            $style[$fieldName] = $this->ReadPropertyInteger($propertyName);
        }

        $style['pointerShape'] = $this->ReadPropertyString('PointerShape');
        $style['arcMode'] = $this->ReadPropertyString('GaugeArcMode');
        $style['startPosition'] = $this->ReadPropertyFloat('GaugeStartPosition');
        $style['endPosition'] = $this->ReadPropertyFloat('GaugeEndPosition');
        $style['colorMode'] = $this->ReadPropertyString('GaugeColorMode');
        foreach ([
            'pointerColor'  => 'PointerColor',
            'progressColor' => 'ProgressColor',
            'ringColor'     => 'RingColor',
            'scaleColor'    => 'ScaleColor',
            'valueColor'    => 'ValueColor',
            'titleColor'    => 'TitleColor'
        ] as $fieldName => $propertyName) {
            $style[$fieldName] = self::ColorToHex($this->ReadPropertyInteger($propertyName));
        }
        $style = array_merge(
            $style,
            $this->ResolveCustomPointerStyle(
                $style['pointerShape'],
                $this->ReadPropertyString('CustomPointerSVG')
            )
        );

        return $style;
    }

    /** @return array{pointerPath?: string, pointerViewBox?: string, pointerError?: string} */
    private function ResolveCustomPointerStyle(string $pointerShape, string $fileData): array
    {
        if ($pointerShape !== 'custom') {
            return [];
        }

        try {
            $pointer = EChartsSvgPath::Import($fileData);

            return ['pointerPath' => $pointer['path'], 'pointerViewBox' => $pointer['viewBox']];
        } catch (InvalidArgumentException $exception) {
            $this->SendDebug('ResolveCustomPointerStyle', $exception->getMessage(), 0);

            return [
                'pointerPath'    => '',
                'pointerViewBox' => '',
                'pointerError'   => $exception->getMessage()
            ];
        }
    }

    private static function IsAnglePosition(float $position): bool
    {
        return is_finite($position)
            && $position >= 0.0
            && $position < 360.0
            && abs(($position / self::ANGLE_STEP) - round($position / self::ANGLE_STEP)) < 0.000001;
    }

    private static function ColorToHex(int $color): string
    {
        return sprintf('#%06X', max(0, min(0xFFFFFF, $color)));
    }

    private function SynchronizeSourceReference(): void
    {
        $previousVariableID = $this->ReadAttributeInteger('RegisteredSourceVariableID');
        $variableID = $this->ReadPropertyInteger('SourceVariableID');
        $variableExists = $variableID > 0 && IPS_VariableExists($variableID);

        if ($previousVariableID > 0 && ($previousVariableID !== $variableID || !$variableExists)) {
            $this->UnregisterReference($previousVariableID);
            $this->UnregisterMessage($previousVariableID, VM_UPDATE);
        }

        if ($variableExists) {
            if ($previousVariableID !== $variableID) {
                $this->RegisterReference($variableID);
            }
            $this->RegisterMessage($variableID, VM_UPDATE);
            $this->WriteAttributeInteger('RegisteredSourceVariableID', $variableID);
        } else {
            $this->WriteAttributeInteger('RegisteredSourceVariableID', 0);
        }
    }

    /** @return array<string, mixed> */
    private function BuildVisualizationState(): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'single',
                'status'        => 'error',
                'chart'         => null,
                'error'         => match ($configurationError['Status']) {
                    self::STATUS_SOURCE_INVALID => 'Configure a numeric source variable.',
                    self::STATUS_DESIGN_INVALID => 'Configure a valid Gauge design.',
                    self::STATUS_THEME_INVALID  => 'Configure a valid ECharts theme.',
                    default                     => 'Configure a valid Gauge range.'
                }
            ];
        }

        if (!$this->HasActiveParent()) {
            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'single',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Connect an active EChartsGateway.'
            ];
        }

        try {
            $chart = json_decode($this->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);

            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'single',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'The Gauge value could not be loaded.'
            ];
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'gauge',
            'variant'       => 'single',
            'status'        => 'ready',
            'chart'         => $chart,
            'error'         => null
        ];
    }

    private function PublishVisualizationState(): void
    {
        try {
            $this->UpdateVisualizationValue(json_encode(
                $this->BuildVisualizationState(),
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_PRESERVE_ZERO_FRACTION
            ));
        } catch (Throwable $exception) {
            $this->SendDebug('PublishVisualizationState', $exception::class, 0);
        }
    }
}
