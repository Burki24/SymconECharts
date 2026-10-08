# ADR 0029: Bar chart families and the first Category Bar

## Status

Accepted

## Context

Apache ECharts groups many materially different diagrams under the `bar`
series type. A current-value category comparison, a historical column chart,
a waterfall calculation and a polar bar chart do not share the same source
contract, validation or user workflow. Putting all variants into one device
would recreate a universal widget with conditional properties and ambiguous
persistent configuration.

The existing `EChartsTimeSeries` module already owns historical line and area
series. Its published prefix `ECTS` describes the time-series responsibility,
not a single ECharts series type, and remains unchanged when further line or
area layouts are added within that responsibility.

## Decision

Bar diagrams are split by their data and configuration contracts:

- `EChartsBarCategory` is the first implementation. It compares the current
  values of one to sixteen unique numeric variables with one common effective
  unit. Its module GUID is `{F9A88437-FE9E-4038-ADAF-775623D2DDC1}` and its
  prefix is `ECBC`.
- Historical/category-over-time bars use the separate `EChartsBarHistory`
  device with prefix `ECBH`; its first implemented contract is specified by
  ADR 0030.
- Waterfall and polar bars remain candidates for separate devices with the
  reserved working prefixes `ECBW` and `ECBP`; this ADR does not assign module
  GUIDs or claim those modules are implemented.

`EChartsBarCategory` reads current values through the existing versioned
`current.read` Gateway operation. It provides vertical and horizontal
orientation, stable configured or value-based ordering, per-source colors,
live updates and separate Tile/IPSView designs. Every source exposes category
and data-series names as separate editable fields. Simple bars use the category
as their axis label and fall back to the variable name. Grouped and stacked
bars use the category for the category axis and the series for the legend;
empty categories share one group and empty series fall back to the variable
name. The source variable ID is always the technical identity. When two sources
have the same category and series label, their variable IDs are appended to
the displayed legend labels, including explicitly configured names. Paths and
parent instance IDs are not used as source keys. Categories may contain
different series or equal labels from different source IDs; no value is lost
because of a label collision. Value ordering uses the sum of the values
available in each category. The default remains `simple`, so existing
configurations stay valid.
The two earlier unpublished source layouts (`Label`/`Series` and
`Category`/`Label`) remain readable during development but are not written by
the new form.
Archive access, historical grouping, waterfall arithmetic and polar geometry
remain outside this contract.

Line and bar renderers share one reproducibly built Cartesian ECharts runtime
containing `LineChart`, `BarChart` and their common Cartesian components. The
runtime is repository infrastructure; source validation and ECharts options
remain in the owning device module.

## Consequences

- Existing Time Series instances, properties and the `ECTS` prefix remain
  compatible.
- Category Bar has a small deterministic source contract and can evolve
  without exposing archive-only controls.
- New bar variants require an explicit need and their own identity decision;
  they are not added as unrelated modes of `EChartsBarCategory`.
- The larger shared Cartesian runtime replaces the former Line-only build,
  while the PHP Time Series loader remains a compatibility alias.
