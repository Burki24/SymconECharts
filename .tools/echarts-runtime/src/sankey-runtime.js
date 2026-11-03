import * as echarts from 'echarts/core';
import {SankeyChart} from 'echarts/charts';
import {AriaComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';
import {LabelLayout} from 'echarts/features';

echarts.use([SankeyChart, AriaComponent, TitleComponent, TooltipComponent, CanvasRenderer, LabelLayout]);

window.echarts = echarts;
