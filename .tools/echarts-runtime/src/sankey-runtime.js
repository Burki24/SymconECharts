import * as echarts from 'echarts/core';
import {SankeyChart} from 'echarts/charts';
import {AriaComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([SankeyChart, AriaComponent, TitleComponent, TooltipComponent, CanvasRenderer]);

window.echarts = echarts;
