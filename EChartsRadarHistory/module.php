<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsJSLiveStandalone.php';

class EChartsRadarHistory extends IPSModuleStrict
{
    use \SymconECharts\EChartsJSLiveStandalone;

    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewRadarHistory';
}
