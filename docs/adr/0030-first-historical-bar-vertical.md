# ADR 0030: First Historical Bar vertical

## Status

Accepted

## Context

`EChartsBarCategory` compares current values and therefore cannot represent a
variable over time without mixing incompatible source, validation and update
contracts. `EChartsTimeSeries` already proves the archive contract, range
selection and raw-versus-aggregated choice, but its renderer and source design
belong to line and area diagrams.

Before implementation, the finished chart families and project libraries were
reviewed according to ADR 0017. The Gateway operation `archive.read`,
`EChartsDataProtocol`, `EChartsVariablePresentation`, the Cartesian ECharts
runtime and the theme assets are directly reusable. Archive-query planning is
also identical for historical Cartesian charts and is therefore centralized
in `libs/EChartsArchiveQuery.php`. Bar-specific validation, properties and
ECharts options remain in the device module.

## Decision

The new device is named `EChartsBarHistory`, uses module GUID
`{BCC80B03-2FC9-45CA-B004-2C81D0689F50}` and prefix `ECBH`.

The first complete vertical supports exactly one numeric archive source in a
native Symcon tile. The persisted source is already stored as a list so later
multi-series support can be added without replacing the property contract.
Users choose raw values, automatic aggregation or an explicit Symcon
aggregation level. Raw mode is never silently converted into aggregation; the
point budget can instead produce an explicitly reported truncated result.

Fixed, calendar-aligned and custom ranges use the shared archive-query planner.
The renderer uses the existing pinned Cartesian runtime and a time axis. It
offers theme selection, time-label format, value labels, grid, rounded bars and
bar width. IPSView and multiple sources are deliberate follow-up verticals and
are not claimed by this decision.

## Consequences

- Historical bars remain separate from both current Category Bar and line/area
  Time Series contracts.
- The first release is useful end to end without introducing premature grouped
  or stacked archive semantics.
- Time Series and Historical Bar now share one deterministic query-planning
  implementation while retaining independent renderers and configuration.
- The existing Cartesian runtime is sufficient; no dependency or third-party
  asset update is required.
