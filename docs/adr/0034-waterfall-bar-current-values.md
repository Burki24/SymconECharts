# ADR 0034: Current-value Waterfall Bar

## Status

Accepted

## Context

ADR 0029 separates category, historical, waterfall and polar bars by their
source contracts. Waterfall steps need a meaningful order and arithmetic;
neither `EChartsBarCategory` nor `EChartsBarHistory` has that contract.

## Decision

`EChartsBarWaterfall` is a separate device module with GUID
`{792B12B3-E608-4301-868D-5FE96DDB8864}` and prefix `ECBW`. It uses the
existing Gateway data-flow IDs and `current.read` operation. One ordered
`Sources` list contains two to sixteen distinct numeric variable IDs. The
first is an absolute start value; the remaining one to fifteen are signed
changes. An end total is calculated, not configured as another source.
Sources share one effective unit, resolved by the existing presentation
helper. Configured step names win; otherwise the current Symcon variable name
is shown. Names and object paths are never source identities. A non-finite
source or cumulative value is rejected.

The PHP device owns validation, ordered arithmetic, live references and the
chart model. The browser renderer owns the ECharts-specific stacked-bar
representation. Positive and negative transparent bases plus visible segments
represent intervals above, below and across zero without changing the
semantic model. The existing Cartesian runtime, HTML-SDK, IPSView transport,
theme assets and background/design-form helpers are reused. The full IPSView
HTML page is not rewritten on each value update; only chart state changes.
Tile and IPSView share sources but may have independent design options.
`libs/EChartsCurrentSources.php` now centralizes identical current-read and
ID-based reference logic used by Category Bar and Waterfall. Source display
names use the existing `EChartsSourceIdentity` resolver.
This follows the official [ECharts Waterfall guidance](https://echarts.apache.org/handbook/en/how-to/chart-types/bar/waterfall/)
for stacked bars and transparent helper series, extended to negative totals
and zero-crossing steps.

## Consequences

No existing module identity or property changes. Waterfall does not provide
historical steps, archive queries, multi-unit axes or polar geometry. These
would need a separate decision and contract. Browser and Symcon 9.0/9.1
runtime behavior must be verified on reachable installations; automated
contract/layout tests alone do not establish those runtime claims.
