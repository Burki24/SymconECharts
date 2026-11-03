'use strict';

const assert = require('node:assert/strict');
globalThis.SYMC_VISUALIZATION = {options:{locale:'de-DE'}};
const f = require('../libs/jslive/format.js');
assert.equal(f.format(-12.5, {parameters:{DIGITS:1, PERCENTAGE:false, PREFIX:' ', SUFFIX:' °C', DECIMAL_SEPARATOR:'.', THOUSANDS_SEPARATOR:''}}), ' -12.5 °C');
assert.equal(f.format(128, {parameters:{DIGITS:2, PERCENTAGE:false, MIN:0, MAX:255, SUFFIX:' LQI'}}), '128,00 LQI');
assert.equal(f.format(128, {parameters:{DIGITS:2, PERCENTAGE:true, MIN:0, MAX:255, SUFFIX:' %'}}), '50,20 %');
assert.equal(f.format(50, {parameters:{DIGITS:0, PERCENTAGE:true, MIN:0, MAX:100, SUFFIX:' %'}}), '50 %');
const native = {parameters:{DIGITS:0, SUFFIX:' W', INTERVALS_ACTIVE:true, INTERVALS:[
    {IntervalMinValue:0, IntervalMaxValue:1000, ConversionFactor:1},
    {IntervalMinValue:1000, IntervalMaxValue:999999, ConversionFactor:1000, SuffixActive:true, SuffixValue:' kW', DigitsActive:true, DigitsValue:2}
]}};
assert.equal(f.format(999, native), '999 W');
assert.equal(f.format(1000, native), '1,00 kW', 'A starting interval wins at the previous interval end.');
assert.equal(f.format(12345, native), '12,35 kW');
assert.equal(f.format(false, {parameters:{OPTIONS:[{Value:false,Caption:'Aus'},{Value:true,Caption:'An'}]}}), 'Aus');
assert.equal(f.format(true, {parameters:{OPTIONS:[{Value:false,Caption:'Aus'},{Value:true,Caption:'An'}]}}), 'An');
assert.equal(f.format(0, {parameters:{INTERVALS_ACTIVE:true,INTERVALS:[{IntervalMinValue:0,IntervalMaxValue:0,ConstantActive:true,ConstantValue:'Bereit'}]}}), 'Bereit');
assert.equal(f.format(1234.5, {parameters:{DIGITS:1,DECIMAL_SEPARATOR:'.',THOUSANDS_SEPARATOR:''}}), '1234.5');
assert.equal(f.escape('<img src=x onerror=alert(1)>'), '&lt;img src=x onerror=alert(1)&gt;');
console.log('JSLive native formatting: original units, percentages, LQI, interval boundaries, conversion, Boolean captions and escaping verified.');
