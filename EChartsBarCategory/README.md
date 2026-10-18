# EChartsBarCategory

## Value-label options

Value labels keep their automatic position by default. They can also be placed above, below, left, right or inside a bar, with a 0–100 px gap. Manually placed inside labels use a contrasting text color unless a custom value color is selected.

These options apply to the tile and the independent IPSView design. In
inherited mode, IPSView follows the tile settings.

`EChartsBarCategory` compares the current values of one to one hundred numeric
IP-Symcon variables as a category bar chart. All configured sources must use
the same effective unit so that the shared value axis remains meaningful.

## Large category lists

The source limit is **100 variable assignments in total**, including all
categories and series in grouped or stacked mode. A category slider and
mouse-wheel navigation appear when the categories do not fit the available
space, in either orientation and in both Tile and IPSView. There is no fixed
sixteen-category window: its size follows the chart dimensions, **Bar width**
and the axis/value-label font sizes.

Thinner bars bring category centers closer together and expose more categories.
Larger fonts protect a minimum distance; once this minimum is reached, reducing
the width changes the bar thickness without packing the labels more tightly.
Short lists remain compact instead of stretching across the whole category
direction. Grouped bars reserve additional room for their series; if their
value labels cannot fit without overlap, the labels are omitted and the full
values remain in the tooltip. Vertical category names truncate to their slot.
A scrollable legend helps
with many series; navigation changes the visible categories, not the sources.

All configured values remain in the model. Sorting and the shared signed
value scale use the full data set, including positive and negative stack
totals, so scrolling does not change the comparison scale. Current-value
updates retain the visible category; in Simple mode this follows the source
ID even if names are equal or a value update changes the sorting order.
Sources, saved design settings and variable values are not modified by navigation.

## Gradient fill

Select **Gradient along the value axis** under **Bar fill** to color the inside
of each bar along a fixed scale. Set its **Color range minimum/maximum** and
the colors for the minimum, midpoint and maximum (default: red, yellow, green).
For batteries use 0–100; for LQI use 0–255. Fractional and signed limits are
supported. Values outside the color range keep the nearest endpoint color;
the actual values and axis scale are not clipped or normalized.

A 16% bar uses only the first 16% of a 0–100 ramp, rather than stretching the
whole red-to-green range across that short bar. Horizontal gradients run from
left to right; vertical gradients run from bottom to top. Stacked segments
continue at their position on the shared value axis, with positive and negative
stacks treated separately. Hiding a series in the legend recalculates the
remaining segments' colors. Tooltips retain the original values and units.

The option works in both designers and previews. IPSView inherits it unless
**Use Tile design** is disabled. Gradient fill replaces per-source fill colors
only while selected; legend colors still identify each series. **Solid color**
remains the default, and **SVG pattern** remains a separate fill choice.

## Temporary loading errors

If current values cannot be loaded briefly, the last valid chart remains
visible in the tile and IPSView. Repeated failures show a notice, and the
module retries after 60 seconds. With no previously loaded data or an invalid
configuration, an error is still shown.

Both designers group bar layout, labels and geometry, fill patterns, and typography and colors in
collapsible sections. The live preview remains below these sections.

## Live preview

The Tile and IPSView designers each show an SVG preview. Unsaved changes to
the title, sources, theme, orientation, bar mode, ordering and basic appearance
update it immediately. Configured category names take precedence over variable
names; variable IDs are never displayed. With **Use Tile design** enabled,
the IPSView preview follows the Tile design. Without valid sources, the preview
is explicitly marked as example data. Value labels use the effective unit and
decimal precision from the sources. The preview illustrates the layout; an
imported SVG motif is shown only as a schematic hatch. The actual chart in
the tile or IPSView widget remains the visual reference.
The preview uses the same width/font-dependent spacing principle. Its bounded
source subset adapts to the preview dimensions, orientation and fonts rather
than always showing six sources. It sorts all sources before selecting the
subset and states the shown and total counts whenever sources are omitted.
The actual chart still uses all sources; a static preview is not a navigable
or pixel-identical replacement for it.

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
- Separate title, legend, axis and value-label font sizes (50–200%) and colors,
  plus a grid color. Automatic colors follow the theme; stacked value labels
  retain automatic contrast unless a value-label color is explicitly set.
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
