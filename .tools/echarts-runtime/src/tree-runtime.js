import * as echarts from 'echarts/core';
import {TreeChart} from 'echarts/charts';
import {AriaComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([TreeChart, AriaComponent, TitleComponent, TooltipComponent, CanvasRenderer]);

window.echarts = echarts;
