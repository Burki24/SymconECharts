/*! Apache ECharts 6.1.0 Gauge runtime; Apache-2.0; see libs/echarts/6.1.0/LICENSE.txt and NOTICE.txt. */

import * as echarts from 'echarts/core';
import { GaugeChart } from 'echarts/charts';
import { AriaComponent, TooltipComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';

echarts.use([
    GaugeChart,
    AriaComponent,
    TooltipComponent,
    CanvasRenderer
]);

window.echarts = echarts;
