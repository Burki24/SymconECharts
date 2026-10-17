import * as echarts from 'echarts/core';
import {CustomChart} from 'echarts/charts';
import {AriaComponent, DataZoomComponent, GridComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([CustomChart, GridComponent, DataZoomComponent, TitleComponent, TooltipComponent, AriaComponent, CanvasRenderer]);

window.echarts = echarts;
