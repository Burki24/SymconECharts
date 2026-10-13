import * as echarts from 'echarts/core';
import {HeatmapChart} from 'echarts/charts';
import {AriaComponent, GridComponent, TitleComponent, TooltipComponent, VisualMapComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([HeatmapChart, AriaComponent, GridComponent, TitleComponent, TooltipComponent, VisualMapComponent, CanvasRenderer]);

window.echarts = echarts;
