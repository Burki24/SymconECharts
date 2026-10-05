# ADR 0008: Separate IPSView output and design for Gauge Single

## Status

Accepted

## Context

One `EChartsGaugeSingle` instance must be usable simultaneously as a native
Symcon tile and as an individually placed IPSView HTML widget. Both outputs
show the same source value and formatting, but their visual surroundings can
differ substantially. Requiring a second chart instance would duplicate the
data binding and make both representations drift apart.

## Decision

- Gauge Single uses the synchronized `IPSViewHTMLPageHelper` for the optional
  WebContent variable, regeneration, retained-output handling and confirmed
  deletion. The helper is not modified locally.
- The native tile and IPSView share one source binding, effective range, unit,
  decimal places, title, chart model, ECharts runtime and Gauge renderer.
- IPSView inherits the complete tile design by default. This preserves the
  existing behavior for upgraded instances and keeps configuration simple.
- Users can disable inheritance and persist a second, typed set of Gauge
  design properties. A configuration action copies the current tile design
  into this set before independent editing.
- The IPSView output is one stable String/WebContent variable. Disabling the
  output retains the variable; deletion remains an explicit helper-owned
  action.
- Value changes regenerate the standalone HTML document. No external endpoint
  or separate unauthenticated data channel is introduced.
- IPSView receives explicit standalone color tokens from its effective ECharts
  theme because Symcon visualization CSS variables are not available there.

## Consequences

The two outputs can use different presets, themes, geometry, colors, SVG
assets and detail settings without duplicating their data configuration. The
module has more persisted properties, but their types and validation contract
remain visible to Symcon and no opaque executable template is stored.

The repository tests verify inherited and independent HTML models. A real
IPSView runtime test remains unavailable on the existing test system because
no IPSView license is present; this is a documented test gap.
