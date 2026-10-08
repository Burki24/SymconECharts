# EChartsBarHistory

`EChartsBarHistory` displays archived values of one numeric IP-Symcon
variable as bars along a time axis. It is deliberately separate from
`EChartsBarCategory`, which compares current values as categories.

## Initial feature set

- Native Symcon HTML-SDK tile
- One archive source with variable presentation, label, color and reducer
- Raw values, automatic aggregation or an explicitly selected aggregation
- Fixed, calendar-aligned and custom time ranges
- Configurable point budget and time-axis labels
- Theme, value labels, grid, rounded bars and bar width

Raw mode remains available to the user. If more raw points exist than the
configured budget, the gateway reports the result as truncated instead of
silently replacing raw data with aggregates.
