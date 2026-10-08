# ADR 0032: Variable IDs identify sources across all chart modules

## Status

Accepted

## Context

IP-Symcon variable names and parent paths are user-facing metadata. They may
coincide or change while the configured source remains the same variable.
Earlier ECharts modules already stored variable IDs, but some multi-source
outputs exposed duplicate labels. ECharts legends use series names as keys,
so visible labels must be separated from technical renderer identifiers.
The cross-project rule is defined in
`../../../SymconDevelopment/standards/SOURCE_VARIABLE_IDENTITY.md`.

## Decision

- The configured variable ID is the sole technical source identity. Instance
  IDs identify their respective instances, not child variables. Names, paths,
  labels, units and source positions never replace a variable ID as a key.
- Gateway current/archive requests and cache keys continue to include the
  variable ID. Device references, update subscriptions and live matching use
  variable IDs. Renderer item IDs are derived from those IDs as
  `variable-<VariableID>`.
- Gauge Multi, Tacho, Chronograph and TimeSeries resolve display names through
  `libs/EChartsSourceIdentity.php`: an explicit label wins, otherwise the
  current Symcon variable name is used. Equal visible names remain equal;
  variable IDs are never appended to labels. Form previews use the same rule.
- Category Bar retains its separate category/series model. Categories and
  shared series names are intentional presentation groups, not source keys.
  Its rows retain variable IDs and its per-source series keys remain ID-based
  when category/series labels collide. Group labels can still represent
  multiple different variable IDs across categories.
- TimeSeries and Category Bar use technical series names and a legend formatter
  that resolves those names to visible labels. Equal visible labels therefore
  do not merge series or expose variable IDs. Simple Category Bar labels may
  repeat while their values remain separate by position.
- Gauge Single and Bar History have exactly one source per instance, so no
  within-chart label collision is possible. Gateway has no chart source list.
  These modules retain their existing ID-based source contracts unchanged.

## Compatibility and verification

No module GUID, property, source-list schema or saved source label is changed.
Existing instances continue to refer to the same variables. Visible labels
never gain an automatic ID suffix. Source order continues to control
presentation layout, not source identity.

Tests cover duplicate names and explicit labels, stable renderer IDs,
TimeSeries live updates by variable ID, equal visible labels and all eight
module contracts. Browser and installed-Symcon behavior remain separate
runtime verification steps.
