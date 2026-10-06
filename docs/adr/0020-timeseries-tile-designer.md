# ADR 0020: Kacheldesign der Zeitreihe als eigenen Vertrag führen

- Status: Angenommen
- Datum: 2026-10-06
- Entscheider: Burki24
- Ergänzt: ADR 0017, ADR 0018 und ADR 0019
- Ersetzt durch: –

## Kontext

`EChartsTimeSeries` besitzt nach der ersten Datenvertikale bereits Theme,
Legende, Zoom, Linien und Flächen. Diese Möglichkeiten waren jedoch nur
teilweise konfigurierbar und hatten keine unmittelbare Vorschau im
Instanzformular. Die fertigen Gauge-Module zeigen, dass ein kollabierter
Designer mit lokaler SVG-Vorschau die Wirkung nicht gespeicherter Einstellungen
verständlich machen kann. Die Gauge-Geometrie selbst ist für Zeitreihen nicht
wiederverwendbar; die vorhandene zentrale Vorschau-Infrastruktur ist es.

## Entscheidung

Die native Kachel erhält einen standardmäßig kollabierten Kacheldesigner. Sein
persistenter Vertrag umfasst:

- Theme sowie Legende oben, unten oder ausgeblendet;
- internen Zoom;
- Linienstärke von 50 bis 200 Prozent und optionale Glättung;
- optionale Datenpunkte mit einer Größe von 50 bis 200 Prozent;
- Flächendeckkraft von 0 bis 100 Prozent;
- getrennte Sichtbarkeit von Raster, Zeitachse und Wertachsen.

Die Einstellungen werden als `chart.design` gemeinsam mit dem fachlichen
Zeitreihenmodell an den Browser übertragen. Der Renderer besitzt für ältere
Modelle ohne `design` dieselben Standardwerte wie vor dieser Erweiterung.

Das Formular verwendet den bereits synchronisierten `SVGPreviewHelper`; die
zeitreihenspezifische SVG-Erzeugung verbleibt in
`EChartsTimeSeries/TimeSeriesPreview.php`. Änderungen an Quellen, Titel, Theme
oder Design aktualisieren die Vorschau sofort, ohne `ApplyChanges()`
aufzurufen. ECharts selbst wird in der Konfigurationsvorschau nicht geladen.

Der Designer gilt zunächst ausschließlich für die native Symcon-Kachel. Die
spätere IPSView-Ausgabe soll denselben fachlichen Designvertrag verwenden,
erhält aber entsprechend ADR 0018 eine getrennte persistente Konfiguration und
Vorschau. Dadurch kann ein Anwender beide Ausgabewege unabhängig gestalten.

## Folgen

Die Standarddarstellung bleibt kompatibel: Legende oben, Zoom aktiv,
Linienstärke 100 Prozent, keine Glättung oder Punkte, Flächendeckkraft
22 Prozent sowie sichtbares Raster und sichtbare Achsen. Ungültige Positionen
oder Prozentwerte setzen die Instanz in den vorhandenen Konfigurationsfehler
202.

Die Designerlogik bleibt familienbezogen im Zeitreihenmodul. Nur die bereits
belegte, diagrammunabhängige SVG-Daten-URI- und Formularinfrastruktur wird aus
`Symcon_ModuleHelper` wiederverwendet.
