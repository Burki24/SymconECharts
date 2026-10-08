# EChartsBarCategory

`EChartsBarCategory` compares the current values of one to sixteen numeric
IP-Symcon variables as a category bar chart. All configured sources must use
the same effective unit so that the shared value axis remains meaningful.

## Output

- Native Symcon HTML-SDK tile with live value updates
- Optional standalone IPSView WebContent output
- Vertical or horizontal bars
- Simple, grouped or stacked bars with separately editable categories and
  data-series names per source
- Configured, ascending or descending value order
- Per-source color or automatic theme colors
- Automatic light/dark value-label contrast on stacked bar segments
- Independent Tile and IPSView design settings

In **IPSView design**, **Use Tile design** is enabled by default. While it is
enabled, the inputs in **Independent IPSView designer** are disabled. The
**Copy Tile design to IPSView and edit independently** button is inside that
designer and is disabled as well. Turn off **Use Tile design** first; the
inputs and button become available immediately. Clicking it copies the current
Tile design once and replaces previously saved independent IPSView design
values. Subsequent Tile design changes are not copied automatically. Sources
and live values remain shared. Save changes to the Tile designer before copying.

The module reads current values through the connected `EChartsGateway`. It
does not read archive data; historical bar charts belong to a separate chart
family.

Every source row provides an explicit `Category` and an optional `Series`
field in both the table and its edit dialog. `Category` labels the category
axis; `Series` names the legend entry in grouped and stacked modes. An empty
category uses the variable name in Simple mode and the shared group in grouped
or stacked mode. An empty series also uses the variable name. Categories may
contain different data series; a missing combination remains empty instead of
invalidating the chart. Only duplicate category/series pairs are rejected. In
value-based ordering, categories are sorted by the sum of their available
values. Existing configurations remain in the compatible `Simple` mode by
default.
