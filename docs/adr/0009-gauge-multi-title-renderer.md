# ADR 0009: First Gauge Multi renderer as responsive Multi Title grid

- Status: Accepted
- Date: 2026-10-05
- Decision maker: Burki24
- Extends: ADR 0004, ADR 0005 and ADR 0007

## Context

Gauge Multi already persists and validates 2 to 16 ordered numeric sources.
Every source has its own range, unit and decimal formatting. The first visible
renderer must preserve these independent semantics and remain readable at
different tile sizes.

Drawing several pointers against one apparent common scale would be misleading
when sources use different units or ranges. A fixed dashboard layout would
also stop scaling once more sources are configured.

## Decision

- The first renderer preset is `multi-title`.
- Every source becomes one ECharts Gauge series with its own minimum, maximum,
  value, unit and label.
- The renderer calculates a responsive grid from source count, width and
  height. It supports the complete existing range of 2 to 16 sources.
- Series IDs continue to use the stable `variable-<VariableID>` contract from
  ADR 0004.
- Gauge Multi uses the same pinned ECharts runtime, official theme assets,
  visualization page helper and Symcon design tokens as Gauge Single.
- The first designer exposes the preset, ECharts theme, ring width and font
  scaling. It starts collapsed and includes an SVG preview.
- Every configured source is subscribed to `VM_UPDATE`; a change republishes
  the complete versioned visualization state.
- Ring and composed classic-instrument presets remain later additions. They
  extend the family-specific renderer instead of changing the source contract.

## Consequences

Gauge Multi now has a visible native Symcon tile without pretending that
unrelated measurement scales are shared. The renderer is deliberately smaller
than the mature Gauge Single designer, but its model and output adapter provide
the tested foundation for further Multi presets and a later IPSView output.

The current environment verifies PHP contracts, generated HTML, responsive
renderer structure and buffer limits through test doubles. Real Symcon browser
behavior remains a runtime test to perform on the reachable installation.
