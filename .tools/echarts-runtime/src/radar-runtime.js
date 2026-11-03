import * as echarts from 'echarts/core';
import {RadarChart, CustomChart} from 'echarts/charts';
import {RadarComponent, AriaComponent, LegendComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';
import {LabelLayout} from 'echarts/features';

echarts.use([RadarChart, CustomChart, RadarComponent, AriaComponent, LegendComponent, TitleComponent, TooltipComponent, CanvasRenderer, LabelLayout]);

window.echarts = echarts;
