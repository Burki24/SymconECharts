# EChartsBarCategory

`EChartsBarCategory` compares the current values of one to sixteen numeric
IP-Symcon variables as a category bar chart. All configured sources must use
the same effective unit so that the shared value axis remains meaningful.

Both designers group bar layout, labels and geometry, and fill patterns in
collapsible sections. The live preview remains below these sections.

## Live preview

The Tile and IPSView designers each show an SVG preview. Unsaved changes to
the title, sources, theme, orientation, bar mode, ordering and basic appearance
update it immediately. Configured category names take precedence over variable
names; variable IDs are never displayed. With **Use Tile design** enabled,
the IPSView preview follows the Tile design. Without valid sources, the preview
is explicitly marked as example data. The preview illustrates the layout;
an imported SVG motif is shown only as a schematic hatch. The actual chart in
the tile or IPSView widget remains the visual reference.

## Animation

Initial and update transitions also have separate **easing** and a fixed
**delay** (0–3000 ms). Both default to cubicInOut without delay. The delay
applies to the whole chart transition, not to each data point in sequence.

In the Tile and independent IPSView designers, **Animate chart**, **Initial animation**, and **Update animation** control ECharts transitions independently. Durations range from 0 to 3000 ms; 0 ms suppresses that transition. The system's reduced-motion setting always disables animation. IPSView inherits Tile settings while **Use Tile design** is enabled.

## Output

- Native Symcon HTML-SDK tile with live value updates
- Optional standalone IPSView WebContent output
- Vertical or horizontal bars
- Simple, grouped or stacked bars with separately editable categories and
  data-series names per source
- Configured, ascending or descending value order
- Per-source color or automatic theme colors
- Optional repeated SVG pattern for all bars, with adjustable pattern size;
  source colors remain the fallback if the browser cannot load the SVG
- Automatic light/dark value-label contrast on stacked bar segments
- Independent Tile and IPSView design settings

In **IPSView design**, **Use Tile design** is enabled by default. While it is
enabled, the independent design inputs and the **Copy Tile design to IPSView
and edit independently** button are shown directly in **IPSView design** but
disabled. Turn off **Use Tile design** first; the inputs and button become
available immediately. Clicking it copies the current
Tile design once and replaces previously saved independent IPSView design
values. Subsequent Tile design changes are not copied automatically. Sources
and live values remain shared. Save changes to the Tile designer before copying.

The module reads current values through the connected `EChartsGateway`. It
does not read archive data; historical bar charts belong to a separate chart
family.

Select **SVG pattern** under **Bar fill** in the Tile designer to reveal the
SVG file and pattern-size controls. The selected motif repeats inside the
normal rectangular bars; it does not change their shape. Only self-contained,
validated SVG files are accepted. The file may be at most 256 KiB and may not
contain scripts or external references. An independent IPSView design can use
a different motif after disabling **Use Tile design**.

Every source row provides an explicit `Category` and an optional `Series`
field in both the table and its edit dialog. `Category` labels the category
axis; `Series` names the legend entry in grouped and stacked modes. An empty
category uses the variable name in Simple mode and the shared group in grouped
or stacked mode. An empty series uses the variable name. The variable ID is
always the technical identity of a source; neither names nor object paths are
used to identify it. If multiple sources have the same category and series
label, the legend keeps that label without exposing variable IDs.
This also applies to explicitly named series. Thus four variables named
`Temperature` can share the `Temperature` category and still appear as four
distinct bars. Categories may contain different data series; a missing
combination remains empty instead of invalidating the chart. In
value-based ordering, categories are sorted by the sum of their available
values. Existing configurations remain in the compatible `Simple` mode by
default.
