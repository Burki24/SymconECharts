# ADR 0025: IPSView-Hintergrund für ECharts-Ausgaben zentral behandeln

- Status: Angenommen
- Datum: 2026-10-06
- Entscheider: Burki24
- Ergänzt: ADR 0008, ADR 0017 und ADR 0022
- Ersetzt durch: –

## Kontext

TimeSeries kann seinen äußeren Diagrammhintergrund bereits an eine IPSView-
Gestaltung anpassen. Gauge Single, Gauge Multi, Gauge Tacho und Gauge
Chronograph werden in denselben frei gestaltbaren IPSView-Flächen eingesetzt
und benötigen denselben Farb- und Transparenzvertrag. Getrennte Kopien dieser
Logik würden Formular, Validierung, Vorschau und Laufzeit-CSS auseinanderlaufen
lassen.

## Entscheidung

- Alle ECharts-Gerätemodule verwenden die Properties
  `IPSViewAdaptToBackground`, `IPSViewBackgroundColor` und
  `IPSViewBackgroundOpacityPercent` mit identischen Typen und Defaults.
- Die Checkbox macht ausschließlich den Dokumenthintergrund transparent. Die
  gewählte Theme- oder Benutzerfarbe wird mit 0 bis 100 Prozent Deckkraft auf
  der äußeren Chartfläche gemischt. Native Kacheln bleiben unverändert.
- Validierung, Farbumwandlung, Formularelemente, Vorschauparameter und CSS-
  Erzeugung liegen zentral in `libs/EChartsIPSViewBackground.php`. Die
  sichtbaren Properties verbleiben als Verträge in den jeweiligen Modulen.
- Der Baustein bleibt projektspezifisch unter `libs`, da sein Vertrag ECharts-
  Theme-Paletten und die konkreten Chart-Wurzelelemente voraussetzt.

## Folgen

Alle vorhandenen ECharts-Familien bieten dieselbe IPSView-Hintergrundsteuerung
und sofort aktualisierte Formularvorschauen. Bestehende Instanzen behalten mit
`false`, automatischer Theme-Farbe und 35 Prozent Deckkraft ihr bisheriges
Erscheinungsbild, solange die Anpassung nicht aktiviert wird. Ein realer
IPSView-Laufzeittest bleibt mangels Lizenz eine dokumentierte Testlücke.
