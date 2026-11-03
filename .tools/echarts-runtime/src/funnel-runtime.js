import * as echarts from 'echarts/core';
import {FunnelChart} from 'echarts/charts';
import {AriaComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {LabelLayout} from 'echarts/features';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([FunnelChart, AriaComponent, TitleComponent, TooltipComponent, LabelLayout, CanvasRenderer]);
window.echarts = echarts;
