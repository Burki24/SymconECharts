# EChartsBarCategory

`EChartsBarCategory` compares the current values of one to sixteen numeric
IP-Symcon variables as a category bar chart. All configured sources must use
the same effective unit so that the shared value axis remains meaningful.

## Output

- Native Symcon HTML-SDK tile with live value updates
- Optional standalone IPSView WebContent output
- Vertical or horizontal bars
- Simple, grouped or stacked bars with an explicit optional category per
  source
- Configured, ascending or descending value order
- Per-source color or automatic theme colors
- Automatic light/dark value-label contrast on stacked bar segments
- Independent Tile and IPSView design settings

The module reads current values through the connected `EChartsGateway`. It
does not read archive data; historical bar charts belong to a separate chart
family.

In grouped and stacked modes, `Label` names the data series shown in the
legend and the optional `Category` places its value on the category axis.
Sources without a category share one group. Categories may contain different
data series; a missing combination is left empty instead of invalidating the
chart. Only duplicate category/label pairs are rejected. In value-based
ordering, categories are sorted by the sum of their available values.
Existing configurations remain in the compatible `Simple` mode by default.
