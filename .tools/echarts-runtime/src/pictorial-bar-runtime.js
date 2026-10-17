import * as echarts from 'echarts/core';
import {PictorialBarChart} from 'echarts/charts';
import {GridComponent, AriaComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';
import {LabelLayout} from 'echarts/features';

echarts.use([PictorialBarChart, GridComponent, AriaComponent, TitleComponent, TooltipComponent, LabelLayout, CanvasRenderer]);
window.echarts = echarts;
