<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsJSLiveStandalone.php';

class EChartsProgress extends IPSModuleStrict
{
    use \SymconECharts\EChartsJSLiveStandalone;

    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewProgress';
}
