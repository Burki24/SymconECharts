# EChartsBarHistory

## Value-label options

Value labels are optional. Their default position remains above the bars; alternatively they can appear below, left, right or inside, with a 0–100 px gap.

These options apply to the tile and the independent IPSView design. In
inherited mode, IPSView follows the tile settings.

`EChartsBarHistory` displays archived values of 1 to 32 numeric IP-Symcon
variables as bars along a time axis. It is deliberately separate from
`EChartsBarCategory`, which compares current values as categories.

Both designers group axes and display, bar fill and patterns, and typography
and colors in collapsible sections. The live preview remains below them.

## Live preview

The Tile and IPSView designers each show an SVG preview. Unsaved changes to
the title, source labels, theme, time range and bar design (including the
configured corner radius when rounded bars are enabled) update both
previews. The historical waveform is always marked as example data: editing
the form does not trigger an archive query. **Use Tile design** makes the
IPSView preview follow Tile appearance. Check the real tile and IPSView widget
for the responsive chart, detailed labels, zoom and SVG bar patterns.
Example value labels use each source's effective unit and decimal places,
including the variable presentation when enabled.
The schematic previews also show a separate value axis for each displayed
effective unit, on its configured side. With multiple sources, automatic axis
colors follow the first source of each unit. Numeric axis ticks and archived
values are shown only in the actual chart.
The preview shows up to three initially visible sources and states the shown
and total counts when they differ. If all sources start hidden, no example
bars are drawn. Colors remain associated with the original source order;
the actual chart still loads every configured source.

## Animation

Initial and update transitions also have separate **easing** and a fixed
**delay** (0–3000 ms). Both default to cubicInOut without delay. The delay
applies to the whole chart transition, not to each data point in sequence.

In the Tile and independent IPSView designers, **Animate chart**, **Initial animation**, and **Update animation** control ECharts transitions independently. Durations range from 0 to 3000 ms; 0 ms suppresses that transition. The system's reduced-motion setting always disables animation. IPSView inherits Tile settings while **Use Tile design** is enabled.

## Current feature set

- Native Symcon HTML-SDK tile and optional IPSView WebContent output
- 1 to 32 archive sources with individual variable presentation, label, color
  and reducer; bars at the same time are shown side by side
- A separate value axis per effective unit, automatically balanced left/right
  or explicitly placed on a side in the source editor
- Raw values, automatic aggregation or an explicitly selected aggregation
- Fixed, calendar-aligned and custom time ranges
- Total point budget from 200 to 32,000 (unchanged default: 1,000), at most
  2,000 points per source, and configurable time-axis labels
- Scrollable legend with individual selection, **All**, **Invert** and
  **Initially visible** per source
- Optional time-axis zoom with a slider and mouse wheel in the tile and IPSView
- Theme, value labels, grid, bar width, optional gradient or repeated SVG
  pattern fill, opacity and rounded corners
- Adjustable font sizes and colors for title, legend, axes and value labels,
  plus grid color. The axis size also applies to its unit caption.
- Independent IPSView time range, time-axis labels and appearance, or inheritance
  from the tile; optional background color and opacity

The Tile and independent IPSView configuration previews follow the selected
bar opacity down to 0%, so fully transparent bars disappear there as they do
in the chart.

Raw mode remains available to the user. The total point budget is shared by
all sources. If more raw points exist than the per-source allowance, the
gateway reports the result as truncated instead of silently replacing raw data
with aggregates. The sources keep their own timestamps; different timestamps
are not artificially aligned.
Zoom changes only the visible part of the already loaded time range; it does
not fetch additional archive points. The selected zoom remains in place during
normal archive refreshes and resets when the configured time range changes.
If an archive refresh temporarily fails, the last available chart and its
zoom remain visible. Repeated failures show a warning until data loads again;
an initial load failure still shows an error instead of an empty chart.
The slider is slim in regular views, slimmer in short tiles and spaced from
the lower tile edge. In a short tile, the chart also reduces axis ticks and
hides overlapping labels to keep the bars readable. Enlarging the tile gives
the axes more room again.

Each numeric variable ID may be configured only once. The variable ID is used
internally, never as a chart caption. A configured label is displayed when
present; otherwise the current Symcon variable name is used, even if two
sources then have the same visible name. Sources with the same effective unit
share an axis. Explicitly choosing opposite axis sides for the same unit is
invalid. The order in the source list controls the series and axis order.
Up to 16 effective unit groups remain supported. With many distinct units,
a narrow tile may leave little room for the plot; hiding rows also hides
unused axes and compacts the remaining axis offsets.

## Many sources and series selection

Existing source lists remain valid without changes. Missing **Initially visible**
settings mean visible. With multiple rows, the legend scrolls when space is
limited; click an entry to toggle that source. **All** selects every source,
**Invert** reverses the selection. To hide all sources, choose **All** followed
by **Invert**. An initially hidden single source also has a legend so it can
be selected. A normally visible single source keeps its existing legend-free
layout. Full names and units remain available in legend tooltips.

Selection, legend position and time zoom survive refreshes, source reordering
and resizing using variable IDs, even when display names are identical.
Reloading restores the configured initial selection. These temporary choices
are not written to the instance. Bar widths account for the number of visible
rows; time zoom provides more space for dense archives without changing values
or loading additional points.

The point budget is divided across **all configured sources**, including hidden
ones, rounded down and capped at 2,000 per row. Hiding a source does not stop
its archive reads or reassign its allowance. With 32 sources, the default
budget of 1,000 permits at most 31 points per row; 32,000 permits 1,000.
Higher budgets increase resource use and do not guarantee continuous archive
coverage. Actual timestamps, empty intervals and reducers remain unchanged.

The serialized model is limited to 900,000 bytes and the complete HTML output
to 1 MiB. Complex SVGs, long names or high-precision numbers may reach this
limit before the point budget. A clear error asks for a smaller budget or
simpler pattern; values are not silently rounded or removed to make it fit.
Large startup states use lossless gzip compression and require browser
support for `DecompressionStream`.

IPSView is enabled in the **IPSView design** section of the instance form.
The WebContent variable can then be used as an HTML widget. Archive refreshes
update the open chart through the shared connection without replacing the HTML
document. Both outputs use the same sources and data mode, but may show different
time ranges.
The tile and independent IPSView designers offer the same bar, zoom and typography
controls. Zoom is enabled by default and can be switched off independently in
IPSView after disabling **Use Tile design**. Selecting a gradient reveals its end-color picker; enabling rounded
bars reveals the corner-radius setting. Automatic colors follow the selected
ECharts theme. If the gradient end color is automatic, it fades toward the
theme background.
Selecting **SVG pattern** under **Bar fill** reveals the SVG file and
pattern-size controls. The motif repeats within the normal bars; it does not
change their shape. Only self-contained, validated SVG files up to 256 KiB are
accepted. If a browser cannot load the image, the source color remains visible.
On dense historical charts, zoom in to make the motif discernible. An
independent IPSView design may use a different SVG. The configuration previews
show a schematic pattern whose spacing follows the pattern-size setting;
the actual SVG motif is shown in the Tile or IPSView widget.
**Use Tile design** is enabled by default. While it is enabled, independent
IPSView design inputs and the **Copy Tile design to IPSView and edit
independently** button are shown directly in **IPSView design** but disabled.
Turn off **Use Tile design** first; the inputs and button become available immediately.
Clicking it copies the saved Tile design once and replaces previously saved
independent IPSView design values. Later Tile
design changes no longer propagate. IPSView time settings are beside the main
time-range controls, outside the designers, and are controlled separately.
Save changes to the Tile designer before copying.

The source limit is 32. Saved one- to sixteen-source configurations and their
point budgets are not rewritten by the update.
