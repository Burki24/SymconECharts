import * as echarts from 'echarts/core';
import {VisualMapComponent} from 'echarts/components';
import './cartesian-runtime.js';

echarts.use([VisualMapComponent]);

window.echarts = echarts;
