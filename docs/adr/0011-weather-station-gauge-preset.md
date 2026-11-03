# ADR 0011: Analog weather-station panel for Gauge Multi

- Status: Accepted
- Date: 2026-10-05
- Extends: ADR 0004, ADR 0009 and ADR 0010

## Context

The existing dial and ring layouts treat all sources with equal visual weight.
A weather-station test layout benefits from one prominent reading with smaller
companion instruments. Source data must not be tied to a specific manufacturer
or assumed physical quantity.

## Decision

- Add the stable `weather-station` Gauge preset without changing the default
  `multi-title` or the versioned source model.
- Source order controls placement: the first source is the main analog dial;
  every remaining source is a smaller dial. Values, ranges, labels and units
  remain independent. No source is silently omitted, including at 16 sources.
- Place companion dials beside the main dial in wide areas and below it in
  narrow areas. Draw a bezel and inner frame around each dial. Use the existing
  theme palette, sizing controls, renderer, tile and IPSView output adapters.
- The configuration preview shows at most four sources; the runtime renders all
  configured sources. A small tile with many sources may be too dense to read,
  so the user must choose a suitable size or one of the other layouts.

## Consequences

This is an additive preset requiring no migration. It can be used for pressure,
temperature and humidity, but those semantics are supplied by the source list,
not inferred from variable names. PHP contract tests and JavaScript option-level
layout tests cover both output modes and boundary source counts. Real Symcon
browser and IPSView visual checks remain separate runtime verification steps.
