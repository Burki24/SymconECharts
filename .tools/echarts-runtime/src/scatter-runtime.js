import * as echarts from 'echarts/core';
import {BrushComponent, ToolboxComponent, VisualMapComponent} from 'echarts/components';
import './cartesian-runtime.js';

echarts.use([BrushComponent, ToolboxComponent, VisualMapComponent]);

window.echarts = echarts;
