# ADR 0015: Shared design layer for Gauge Multi

- Status: Accepted
- Date: 2026-10-05
- Extends: ADR 0004, ADR 0009 and ADR 0014

## Context

Gauge Multi has six responsive presets, but its designer previously exposed
only ring width and font sizes. Pointer, hub, scale, color and dial-plate
decisions were fixed inside each preset. Gauge Single already demonstrates that
these visual roles can be user-configurable without changing the source model.

## Decision

- Add one shared design layer per output. It controls pointer shape and size,
  hub shape and size, major and minor divisions, semantic color roles and the
  optional circular dial plate.
- Use the project-specific `libs/EChartsGaugeDesign.php` contract for behavior
  shared with Gauge Single: RGB conversion, validated SVG pointer import and
  pivot resolution, and sanitized SVG plate-background import. Synchronized
  general-purpose helpers under `libs/helper` remain unchanged.
- Allow one validated SVG pointer and one sanitized SVG plate background per
  output. They apply consistently to every dial; per-source overrides remain a
  separate source-contract extension.
- Keep the tile and an independently designed IPSView output separate. The
  existing copy action copies all shared design properties together with preset
  and theme.
- Use `preset` and `theme` defaults so existing installations retain their
  current appearance after `ApplyChanges()`. The change is additive and needs
  no persisted-data migration.
- Apply the shared layer after constructing a preset. Presets continue to own
  responsive placement and their characteristic geometry; user choices replace
  only the selected visual roles.
- Render the same design roles on every dial in the SVG configuration preview
  and refresh both tile and IPSView previews immediately from unsaved form
  values, matching the Gauge Single interaction model.
- Ring-grid and concentric presets retain their ring layout and do not receive
  dial-plate graphics. Custom progress and ring colors still apply.
- Keep per-source overrides out of this increment. They require an explicit,
  versioned extension of the source-row contract rather than implicit special
  cases in the renderer.

## Consequences

All instruments in one Gauge Multi output can now be restyled consistently,
including the main and embedded chronograph dials. Validation rejects unknown
modes, out-of-range percentages and invalid division counts before rendering.
PHP integration tests cover persistence and output separation; JavaScript tests
cover renderer behavior. The form contract additionally covers immediate,
independent tile and IPSView SVG preview updates. Real IPSView runtime verification remains unavailable
without a license, and source-specific overrides remain a documented follow-up.
