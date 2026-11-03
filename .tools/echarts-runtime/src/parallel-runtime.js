import * as echarts from 'echarts/core';
import {ParallelChart} from 'echarts/charts';
import {AriaComponent, ParallelComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([ParallelChart, ParallelComponent, AriaComponent, TitleComponent, TooltipComponent, CanvasRenderer]);

window.echarts = echarts;
