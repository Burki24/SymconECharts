# ADR 0033: Historical Bar sources and value axes

## Status

Accepted

## Context

ADR 0031 keeps historical bars in one module. The first stage accepted one
archive source. The user wants several sources without a new module, initially
up to 16, with adjacent bars and more than one unit/value axis.

## Decision

`EChartsBarHistory` accepts 1–16 distinct numeric variable IDs in its existing
ordered `Sources` list. The ID alone identifies a source; equal display labels
remain equal and never acquire an ID suffix. A configured label wins, otherwise
the current Symcon variable name is shown. Existing one-source configurations
remain valid without a migration or changes to GUID, prefix or property name.

Each source retains its own display unit, decimal precision, color and archive
reducer. The existing total point budget is divided across the configured
sources by `EChartsArchiveQuery`; it is not multiplied by the source count.
Every source is queried independently for the selected output's time range.
Raw timestamps are neither resampled nor silently aligned: bars appear at
their actual times; bars sharing a time position are arranged side by side,
not stacked. A truncated result from any source raises the existing warning.

One value axis is generated per effective unit string. Sources with the same
unit share that axis without value conversion. Axis sides can be selected per
source (`Automatic`, `Left`, `Right`); automatic groups are balanced between
left and right, and conflicting explicit sides for one unit are invalid. The
ordered first occurrence of a unit determines its axis order. Distinct units
are allowed up to the 16-source limit; crowded layouts, especially in narrow
tiles, are a presentation constraint rather than a silent source rejection.
Legend entries use technical IDs internally and display only user labels.

Sixteen is the initial upper limit, not permission to lower it silently. A
future lower limit requires an explicit compatibility/migration decision for
instances already storing more sources than the new limit.

## Consequences

- A single saved source keeps the previous visible chart layout.
- Tile and IPSView share the source list and data mode, while retaining their
  independently selectable time range and design.
- Live Symcon, browser and IPSView checks remain separate from repository tests.
