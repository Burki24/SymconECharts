# ADR 0006: Ersten Gauge-Single-Kacheldesigner festlegen

- Status: Angenommen
- Datum: 2026-10-04
- Entscheider: Burki24
- Ergänzt: ADR 0003 und ADR 0005
- Ergänzt durch: ADR 0007 hinsichtlich der getrennten Theme-Auswahl
- Ersetzt durch: –

## Kontext

Gauge Single besitzt eine responsive native Kachel und eine SVG-Vorschau im
Instanzformular. Bisher existierte nur eine fest eingebaute Darstellung. Die
offiziellen Apache-ECharts-Beispiele zeigen mehrere Single-Gauge-Layouts, die
mit demselben Einquellenmodell auskommen. Layoutwahl, spätere ECharts-Themes und
der noch fehlende IPSView-Ausgabeadapter müssen getrennte Verträge bleiben.

Eine freie ECharts-JSON-Eingabe würde interne Rendererdetails zu einem
öffentlichen Vertrag machen, ungeprüfte Skript- und Formatterinhalte zulassen
und eine kontrollierte Weiterentwicklung erschweren. Ein universeller
Kacheldesigner-Helper ist ebenfalls noch nicht belegt; die vorhandenen zentralen
Formular-, Vorschau-, Theme- und Responsive-Helper decken die allgemeinen
Aufgaben bereits ab.

## Entscheidung

`EChartsGaugeSingle` erhält die persistente String-Property `GaugePreset`. Die
stabilen Preset-IDs der ersten Stufe sind:

- `basic`: klassische Gauge mit Zeiger ohne Fortschrittsbogen;
- `simple`: Gauge mit Zeiger und Fortschrittsbogen;
- `progress`: stärker auf Fortschritt und Wert fokussierte Gauge;
- `speed`: halbkreisförmige Speed-Gauge mit hervorgehobener Wertbox.

`simple` ist der Default und erhält die bisherige sichtbare Darstellung. Damit
ändern vorhandene Instanzen beim Modulupdate ihr Layout nicht unbeabsichtigt.
Unbekannte Preset-IDs führen zum Modulstatus `205` und werden nicht still auf
einen anderen Stil abgebildet.

Das ausgewählte Preset wird additiv im vorhandenen Gauge-Datenmodell unter
`gauge.preset` übertragen. Die SVG-Vorschau und der native ECharts-Renderer
werten dieselbe ID aus. Die Vorschau reagiert auf noch nicht gespeicherte
Formularwerte; erst das Übernehmen persistiert die Property. Presets definieren
nur Layoutoptionen wie Winkel, Fortschrittsbogen, Skalenaufteilung und
Wertposition.

Farben bleiben in dieser Stufe bei den gemeinsamen Symcon-Designtokens. Die
später ergänzte Auswahl offizieller ECharts-Themes ist als eigener Vertrag in
[`ADR 0007`](0007-echarts-theme-assets-and-selection.md) festgelegt. Auch
IPSView bleibt ein separater Ausgabeadapter und wird nicht durch
Designer-Properties vorweggenommen.

## Alternativen

- **Freie ECharts-Option als JSON:** bietet maximale Freiheit, macht aber
  interne APIs, Validierung, Migration und sichere Ausgabe unbeherrschbar.
- **Jedes Beispiel als eigenes Gerätemodul:** vervielfacht Instanzen trotz
  identischem Datenmodell und widerspricht der Gauge-Familienentscheidung.
- **Sofortiger zentraler Kacheldesigner-Helper:** wäre ohne einen zweiten
  stabilen Verbraucher eine vorsorgliche Abstraktion.
- **Themes und Layout gemeinsam einführen:** koppelt zwei unabhängige
  Konfigurationsachsen und erschwert Fehlersuche sowie Kompatibilität.

## Folgen

Benutzer können das Gauge-Layout im Instanzformular auswählen und unmittelbar
in der SVG-Vorschau beurteilen. Nach dem Übernehmen verwendet die native Kachel
dieselbe Auswahl. Weitere Single-Presets können unter neuen stabilen IDs
ergänzt werden; bestehende IDs ändern ihre grundlegende Bedeutung nicht.

Die SVG-Vorschau bleibt eine gezielte Annäherung an den Canvas-Renderer und
kein pixelidentischer Browserersatz. Reale Symcon-Client- und Browserprüfungen
bleiben erforderlich. Erst wenn mindestens ein weiterer Diagrammtyp denselben
allgemeinen Designervertrag benötigt, wird eine Ergänzung der zentralen
ModuleHelper erneut geprüft.

## Nachweise

- Property-, Formular- und Statusverträge unter `tests/symcon_strict.php`
- Preset-Datenmodell, SVG-Vorschau und native Kachel unter
  `tests/gateway_gauges.php`
- Formularschema und Repositorystruktur unter `tests/validate_structure.php`
- Offizielle Gauge-Beispiele unter
  `https://echarts.apache.org/examples/en/index.html#chart-type-gauge`
