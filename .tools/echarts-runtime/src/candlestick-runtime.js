import * as echarts from 'echarts/core';
import {CandlestickChart} from 'echarts/charts';
import {GridComponent, AriaComponent, TitleComponent, TooltipComponent, DataZoomComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([CandlestickChart, GridComponent, AriaComponent, TitleComponent, TooltipComponent, DataZoomComponent, CanvasRenderer]);
window.echarts = echarts;
