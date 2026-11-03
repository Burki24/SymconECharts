import * as echarts from 'echarts/core';
import {TreemapChart} from 'echarts/charts';
import {AriaComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([TreemapChart, AriaComponent, TitleComponent, TooltipComponent, CanvasRenderer]);

window.echarts = echarts;
