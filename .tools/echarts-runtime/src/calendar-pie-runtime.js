import * as echarts from 'echarts/core';
import {PieChart, ScatterChart} from 'echarts/charts';
import {AriaComponent, CalendarComponent, LegendComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';
import {LabelLayout} from 'echarts/features';

echarts.use([PieChart, ScatterChart, AriaComponent, CalendarComponent, LegendComponent, TitleComponent, TooltipComponent, CanvasRenderer, LabelLayout]);

window.echarts = echarts;
