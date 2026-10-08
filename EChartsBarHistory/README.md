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
- Theme, value labels, grid, bar width, optional gradient fill, opacity and
  rounded corners
- Adjustable font sizes and colors for title, axes and value labels, plus grid
  color
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
The tile and independent IPSView designers offer the same bar and typography
controls. Selecting a gradient reveals its end-color picker; enabling rounded
bars reveals the corner-radius setting. Automatic colors follow the selected
ECharts theme. If the gradient end color is automatic, it fades toward the
theme background.
**Use Tile design** is enabled by default. While it is enabled, independent
IPSView design fields have no effect and **Copy Tile design to IPSView and edit
independently** is disabled. Turn off **Use Tile design** first; the button
becomes available immediately. Clicking it copies the current Tile design once
and replaces previously saved independent IPSView design values. Later Tile
design changes no longer propagate. IPSView time settings are controlled
separately. Save changes to the Tile designer before copying.

The module currently accepts exactly one source. Additional sources are planned
for this same `EChartsBarHistory` module; no separate Single/Multi module is
planned. The eventual source limit and multi-series presentation remain to be
decided before implementation.
