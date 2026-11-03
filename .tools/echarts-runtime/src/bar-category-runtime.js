import * as echarts from 'echarts/core';
import {BarChart} from 'echarts/charts';
import {
    AriaComponent,
    DataZoomComponent,
    GridComponent,
    LegendComponent,
    TitleComponent,
    TooltipComponent
} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([
    BarChart,
    AriaComponent,
    DataZoomComponent,
    GridComponent,
    LegendComponent,
    TitleComponent,
    TooltipComponent,
    CanvasRenderer
]);

window.echarts = echarts;
