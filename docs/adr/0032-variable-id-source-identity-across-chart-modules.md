# ADR 0032: Variable IDs identify sources across all chart modules

## Status

Accepted

## Context

IP-Symcon variable names and parent paths are user-facing metadata. They may
coincide or change while the configured source remains the same variable.
Earlier ECharts modules already stored variable IDs, but some multi-source
outputs exposed duplicate labels. ECharts legends use series names, so equal
TimeSeries labels made independently identified sources ambiguous to operate.
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
- Gauge Multi, Tacho, Chronograph and TimeSeries use one local label-collision
  function from `libs/EChartsSourceIdentity.php`. If labels coincide, it adds
  `(#<VariableID>)` only to the affected visible labels. Saved `Label`
  properties remain unchanged. Form previews use the same rule for complete
  valid source lists.
- Category Bar retains its separate category/series model. Categories and
  shared series names are intentional presentation groups, not source keys.
  Its rows retain variable IDs and its per-source series keys remain ID-based
  when category/series labels collide. Group labels can still represent
  multiple different variable IDs across categories.
- Gauge Single and Bar History have exactly one source per instance, so no
  within-chart label collision is possible. Gateway has no chart source list.
  These modules retain their existing ID-based source contracts unchanged.

## Compatibility and verification

No module GUID, property, source-list schema or saved source label is changed.
Existing instances continue to refer to the same variables. Only visible
labels that collide gain an ID suffix. Source order continues to control
presentation layout, not source identity.

Tests cover duplicate names and explicit labels, stable renderer IDs,
TimeSeries live updates by variable ID, label-suffix collisions and all eight
module contracts. Browser and installed-Symcon behavior remain separate
runtime verification steps.
