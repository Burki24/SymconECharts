# EChartsBarHistory

`EChartsBarHistory` displays archived values of 1 to 16 numeric IP-Symcon
variables as bars along a time axis. It is deliberately separate from
`EChartsBarCategory`, which compares current values as categories.

## Live preview

The Tile and IPSView designers each show an SVG preview. Unsaved changes to
the title, source labels, theme, time range and basic bar design update both
previews. The historical waveform is always marked as example data: editing
the form does not trigger an archive query. **Use Tile design** makes the
IPSView preview follow Tile appearance. Check the real tile and IPSView widget
for the responsive chart, detailed labels, zoom and SVG bar patterns.

## Animation

Initial and update transitions also have separate **easing** and a fixed
**delay** (0–3000 ms). Both default to cubicInOut without delay. The delay
applies to the whole chart transition, not to each data point in sequence.

In the Tile and independent IPSView designers, **Animate chart**, **Initial animation**, and **Update animation** control ECharts transitions independently. Durations range from 0 to 3000 ms; 0 ms suppresses that transition. The system's reduced-motion setting always disables animation. IPSView inherits Tile settings while **Use Tile design** is enabled.

## Current feature set

- Native Symcon HTML-SDK tile and optional IPSView WebContent output
- 1 to 16 archive sources with individual variable presentation, label, color
  and reducer; bars at the same time are shown side by side
- A separate value axis per effective unit, automatically balanced left/right
  or explicitly placed on a side in the source editor
- Raw values, automatic aggregation or an explicitly selected aggregation
- Fixed, calendar-aligned and custom time ranges
- Configurable point budget and time-axis labels
- Optional time-axis zoom with a slider and mouse wheel in the tile and IPSView
- Theme, value labels, grid, bar width, optional gradient or repeated SVG
  pattern fill, opacity and rounded corners
- Adjustable font sizes and colors for title, axes and value labels, plus grid
  color
- Independent IPSView time range, time-axis labels and appearance, or inheritance
  from the tile; optional background color and opacity

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
With many distinct units, a narrow tile may leave little room for the plot.

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
independent IPSView design may use a different SVG.
**Use Tile design** is enabled by default. While it is enabled, independent
IPSView design inputs and the **Copy Tile design to IPSView and edit
independently** button are shown directly in **IPSView design** but disabled.
Turn off **Use Tile design** first; the inputs and button become available immediately.
Clicking it copies the saved Tile design once and replaces previously saved
independent IPSView design values. Later Tile
design changes no longer propagate. IPSView time settings are beside the main
time-range controls, outside the designers, and are controlled separately.
Save changes to the Tile designer before copying.

The source limit is initially 16. Reducing it later would require a compatible
way to handle previously saved instances with more sources.
