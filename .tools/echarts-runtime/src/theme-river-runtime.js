import * as echarts from 'echarts/core';
import {ThemeRiverChart} from 'echarts/charts';
import {AriaComponent, SingleAxisComponent, LegendComponent, GraphicComponent, TitleComponent, TooltipComponent} from 'echarts/components';
import {CanvasRenderer} from 'echarts/renderers';

echarts.use([ThemeRiverChart, SingleAxisComponent, LegendComponent, GraphicComponent, AriaComponent, TitleComponent, TooltipComponent, CanvasRenderer]);

window.echarts = echarts;
