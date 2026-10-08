# ADR 0031: Expand Historical Bar within one module

## Status

Accepted

## Context

ADR 0030 established the first `EChartsBarHistory` vertical with exactly one
archive source and a list-shaped persisted `Sources` property. Gauge Single and
Multi were split because they represent different instrument contracts. A
historical bar with one or several sources remains the same time-based chart
family; splitting it would add module identities and migration work without a
corresponding user benefit.

## Decision

`EChartsBarHistory` remains one module and keeps its GUID and `ECBH` prefix.
The current one-source implementation is an incremental feature stage, not a
separate Single product. Its native tile and optional IPSView WebContent use
the same source and archive data mode. IPSView may inherit the tile's time
settings and design or use its own time range, axis-label format and design.
Archive refreshes update the open IPSView chart via the shared persistent
transport rather than rewriting the HTML document.

Several sources will later be added to this same module through the existing
`Sources` list. The precise limit (8 or 16), grouping semantics, axes and
compatibility rules require a separate explicit design decision and tests
before implementation; this ADR does not claim multi-source support exists.

## Consequences

- One module identity is retained throughout the historical-bar expansion.
- The first stage can be verified independently before changing the source
  count and renderer semantics.
- Tile and IPSView remain separate presentation choices over the same archive
  contract, including independently selectable displayed time ranges.
