import * as echarts from 'echarts/core';
import {PieChart} from 'echarts/charts';
import {AriaComponent, LegendComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([PieChart, AriaComponent, LegendComponent, TitleComponent, TooltipComponent, CanvasRenderer]);

window.echarts = echarts;
