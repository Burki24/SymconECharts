# EChartsBarCategory

`EChartsBarCategory` compares the current values of one to sixteen numeric
IP-Symcon variables as a category bar chart. All configured sources must use
the same effective unit so that the shared value axis remains meaningful.

## Output

- Native Symcon HTML-SDK tile with live value updates
- Optional standalone IPSView WebContent output
- Vertical or horizontal bars
- Simple, grouped or stacked bars; without explicit series names, grouped and
  stacked modes place every source as a separate series in one shared category
- Configured, ascending or descending value order
- Per-source color or automatic theme colors
- Automatic light/dark value-label contrast on stacked bar segments
- Independent Tile and IPSView design settings

The module reads current values through the connected `EChartsGateway`. It
does not read archive data; historical bar charts belong to a separate chart
family.

For a category/series matrix, set the series field on every source. This
explicit form requires at least two named series and exactly one source for
every category/series combination. In value-based ordering, the categories
are sorted by the sum of all their series values. Existing configurations
remain in the compatible `Simple` mode by default.
