import * as echarts from 'echarts/core';
import {BarChart} from 'echarts/charts';
import {AriaComponent, PolarComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([BarChart, AriaComponent, PolarComponent, TitleComponent, TooltipComponent, CanvasRenderer]);

window.echarts = echarts;
