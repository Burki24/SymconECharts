import * as echarts from 'echarts/core';
import {RadarChart} from 'echarts/charts';
import {RadarComponent, AriaComponent, LegendComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';
import {LabelLayout} from 'echarts/features';

echarts.use([RadarChart, RadarComponent, AriaComponent, LegendComponent, TitleComponent, TooltipComponent, CanvasRenderer, LabelLayout]);

window.echarts = echarts;
