# ADR 0013: Use available Gauge space more fully

- Status: Accepted
- Date: 2026-10-05
- Decision maker: Burki24
- Extends: ADR 0006 and ADR 0009

## Context

The original conservative Gauge layouts leave substantial unused space in
wide and square native tiles. The same renderer is also used by IPSView, so
the correction should apply to both outputs without creating separate layout
properties or stretching circular instruments.

## Decision

- Keep the 440 × 400 reference layout and all persisted Gauge Single design
  percentages. Evaluate uniform enlargement up to 120% at render time and use
  the largest fitting step. The plate and value box and the title position
  must remain within the available widget rectangle. If no larger step fits,
  retain the old scale.
- Increase the available radius within Multi Title and ring-grid cells while
  preserving the responsive grid choice, source order and per-source scales.
- Apply the same calculations in native tiles and IPSView. Neither the data
  model nor any persisted property changes.

## Consequences

Existing designs may appear larger after an update. Explicit size and offset
settings remain effective, and constrained layouts fall back to the original
scale. Option-level JavaScript tests cover representative broad, square and
narrow surfaces. Real Symcon and IPSView visual checks remain necessary.
