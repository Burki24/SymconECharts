# ADR 0014: Embedded chronograph as a Gauge Multi preset

- Status: Superseded by ADR 0016
- Date: 2026-10-05
- Extends: ADR 0004, ADR 0009 and ADR 0012

## Context

The existing weather-station and tacho presets arrange separate dials beside
or below one another. A chronograph-inspired instrument instead places several
independent subdials inside one dominant dial. The visual reference is a layout
idea, not an artwork or a source of fixed units and values.

## Decision

- Add `chronograph` as an optional preset without changing existing IDs or the
  `multi-title` default. No persisted format or data protocol changes.
- Source 1 drives the main dial; sources 2–6 drive embedded subdials. Each
  source retains its own center, pointer, range, scale, value, label and unit.
- Reject more than six sources when this preset is selected for the native tile
  or an enabled, independently designed IPSView output. Other presets continue
  to support up to 16 sources. Nothing is silently omitted.
- Use the locally bundled ECharts Gauge series with separate centers and
  responsive radii. Preserve the native tile header inset, its Symcon 9.1
  hidden-title behavior, and the independent IPSView design path.
- Render a matching SVG form preview from the same source order. No imagery,
  logos or instrument graphics are copied from the reference.

## Consequences

The preset is additive and needs no migration. Large tiles or widgets are
recommended because a small tile cannot make five embedded scales equally
legible. PHP contract tests and JavaScript layout checks cover source ordering,
limits and geometry. Real Symcon browser and IPSView visual verification remain
separate tests; IPSView runtime testing is currently unavailable without a
license.
