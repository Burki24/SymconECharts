import * as echarts from 'echarts/core';
import {BoxplotChart} from 'echarts/charts';
import {GridComponent, AriaComponent, TitleComponent, TooltipComponent, DataZoomComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([BoxplotChart, GridComponent, AriaComponent, TitleComponent, TooltipComponent, DataZoomComponent, CanvasRenderer]);
window.echarts = echarts;
