# EChartsBarHistory

`EChartsBarHistory` displays archived values of one numeric IP-Symcon
variable as bars along a time axis. It is deliberately separate from
`EChartsBarCategory`, which compares current values as categories.

## Current feature set

- Native Symcon HTML-SDK tile and optional IPSView WebContent output
- One archive source with variable presentation, label, color and reducer
- Raw values, automatic aggregation or an explicitly selected aggregation
- Fixed, calendar-aligned and custom time ranges
- Configurable point budget and time-axis labels
- Theme, value labels, grid, rounded bars and bar width
- Independent IPSView time range, time-axis labels and appearance, or inheritance
  from the tile; optional background color and opacity

Raw mode remains available to the user. If more raw points exist than the
configured budget, the gateway reports the result as truncated instead of
silently replacing raw data with aggregates.

IPSView is enabled in the **IPSView design** section of the instance form.
The WebContent variable can then be used as an HTML widget. Archive refreshes
update the open chart through the shared connection without replacing the HTML
document. Both outputs use the same source and data mode, but may show different
time ranges.

The module currently accepts exactly one source. Additional sources are planned
for this same `EChartsBarHistory` module; no separate Single/Multi module is
planned. The eventual source limit and multi-series presentation remain to be
decided before implementation.
