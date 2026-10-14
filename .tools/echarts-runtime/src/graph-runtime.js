import * as echarts from 'echarts/core';
import {GraphChart} from 'echarts/charts';
import {AriaComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([GraphChart, AriaComponent, TitleComponent, TooltipComponent, CanvasRenderer]);

window.echarts = echarts;
