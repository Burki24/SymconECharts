# ADR 0012: Tacho cockpit as an additional Gauge Multi preset

- Status: Accepted
- Date: 2026-10-05
- Extends: ADR 0004, ADR 0009 and ADR 0011

## Context

The weather-station preset emphasizes one dial but arranges the remaining
instruments as a regular companion grid. The supplied ECharts example instead
shows an automotive cockpit with a dominant middle dial and smaller left and
right instruments. Its fixed speed, fuel and temperature values cannot be
copied into the generic Gauge Multi source contract.

## Decision

- Add `tacho` as an optional preset; preserve the existing default and all
  previously stored preset IDs.
- Source order defines the three primary positions: first in the middle,
  second on the left, third on the right. Further sources appear in a compact
  grid below. All sources retain their independent range, unit and value.
- Use the existing Gauge series and local ECharts runtime, with light tick
  marks, red pointers and circular frames. Theme selection still controls
  background and text colors. Do not copy the example's hard-coded telemetry,
  imported icons or path data.
- Arrange the primary dials horizontally on wide surfaces and place the side
  dials below the main dial on narrow surfaces. Preserve the native tile header
  inset and the independent IPSView layout.
- The form preview illustrates the first three instruments; the runtime shows
  all configured sources, up to the existing limit of 16.

## Consequences

The new preset is additive and needs no migration. A large, wide tile best
resembles the reference. The layout remains complete in a small tile, but
many gauges will be too small to read comfortably. PHP model/form tests and
JavaScript option-level layout checks cover the preset. Real Symcon browser
and IPSView visual verification remain separate steps.
