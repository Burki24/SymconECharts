import * as echarts from 'echarts/core';
import {HeatmapChart} from 'echarts/charts';
import {AriaComponent, CalendarComponent, TitleComponent, TooltipComponent, VisualMapComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([HeatmapChart, AriaComponent, CalendarComponent, TitleComponent, TooltipComponent, VisualMapComponent, CanvasRenderer]);

window.echarts = echarts;
