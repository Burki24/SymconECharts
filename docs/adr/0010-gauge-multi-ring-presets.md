# ADR 0010: Separate ring-grid and concentric Gauge Multi presets

- Status: Accepted
- Date: 2026-10-05
- Decision maker: Burki24
- Extends: ADR 0004 and ADR 0009

## Context

Gauge Multi already presents 2 to 16 independently scaled sources as a
responsive grid of dial instruments. A single "Ring" choice would hide two
materially different layouts: separate progress rings and concentric rings.
Both should be available without changing the source list or the tile/IPSView
output contract.

## Decision

- Keep `multi-title` as the default and add `ring-grid` and
  `ring-concentric` as stable Gauge preset IDs.
- Ring grid gives each source its own circular progress Gauge and its own
  label, value, minimum, maximum and unit in a responsive cell.
- Concentric mode draws one progress ring per source around a common center.
  Ring order follows source order from outside inward. Each series retains
  its own minimum and maximum; a separate value legend identifies its label,
  value and unit. There is no misleading common scale.
- All three presets use the existing versioned Multi model, theme selection,
  source subscriptions and renderer in both native tiles and IPSView.
  IPSView may inherit or independently select any preset.
- Both ring variants support the existing 2-to-16-source contract. With many
  sources in a small area, the layout remains complete but text and ring
  strokes become small; users should enlarge the tile or widget.
- The native tile reserves its Symcon header area; IPSView does not.
  Symcon 9.1 title visibility is now read from `IPS_GetObject()` when the tile
  HTML is generated, so a hidden title releases the reserved area. Symcon 9.0
  retains the previous inset.

## Consequences

The new presets are additive and require no migration. Existing installations
retain `multi-title`. The form preview represents the selected preset with up
to four example sources; the runtime renders every configured source.
PHP-side model/form checks and JavaScript option-level layout tests cover the
new modes. Real Symcon and IPSView visual checks remain separate runtime
verification steps.
