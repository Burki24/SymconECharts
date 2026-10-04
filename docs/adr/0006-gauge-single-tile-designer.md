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
- `speed`: halbkreisförmige Speed-Gauge, die den charakteristischen
  SVG-Zeiger, den Schatten am Fortschrittsbogen, die feine Tick-Geometrie und
  die getrennte Typografie für Wert und Einheit aus dem offiziellen
  ECharts-Beispiel übernimmt.

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

Die native Kachel berechnet die Gauge-Geometrie aus einer gemeinsamen
virtuellen Referenzfläche von 440 x 400 Einheiten. Der kleinere Skalierungswert
aus tatsächlicher Breite und Höhe bestimmt Radius, Linienstärken, Zeiger,
Typografie und vertikale Abstände. Freie Resthöhe wird gleichmäßig verteilt.
Damit bleiben die Preset-Proportionen in schmalen, quadratischen und breiten
Kacheln stabil und quadratische Kacheln nutzen ihre Breite besser aus.
Fortschrittsbogen und Achslinie teilen sich denselben Radius;
Teilstriche, Hauptteiler und Beschriftung werden mit positiven ECharts-Abständen
in dieser Reihenfolge innerhalb der Achslinie angeordnet. Die SVG-Vorschau
verwendet dieselbe radiale Reihenfolge.

Der Kacheldesigner erlaubt zusätzlich validierte relative Anpassungen für
Skalen-, Wert-, Einheiten- und Titelschrift sowie Ringstärke, Zeigerstärke,
Zeigerlänge, Nebenstrichlänge und Hauptteilerlänge. Alle Werte werden als Prozentwert der
jeweiligen Presetvorgabe im Bereich von 50 bis 150 gespeichert. `100` ist der
kompatible Standardwert und stellt die Presetvorgabe wieder her. Die relativen
Werte werden erst nach der responsiven Skalierung angewendet und bleiben damit
von der konkreten Kachelgröße unabhängig. Eine freie ECharts-Option wird daraus
nicht abgeleitet.

Als additive, validierte Designwerte stehen die Zeigerformen `preset`,
`needle`, `line`, `arrow` und `custom` zur Verfügung. `custom` verwendet eine
über `SelectFile` gespeicherte SVG-Datei. Der Import akzeptiert höchstens
128 KiB große SVGs mit endlicher `viewBox` und maximal 32 reinen
`path`-Elementen. Transformationen, Dokumenttypen, Entitäten, Skripte,
Ereignishandler, externe Referenzen und sonstige SVG-Elemente werden
abgewiesen. Nur ein auf 64 KiB begrenzter validierter Pfad und die `viewBox`
gelangen in das Gauge-Datenmodell; das Originalmarkup wird weder an Vorschau
noch Kachel weitergegeben. Eine optionale numerische Wurzelangabe
`data-echarts-pivot="x y"` wird nur übernommen, wenn beide Koordinaten innerhalb
der `viewBox` liegen. Sie definiert den Drehpunkt des Pfades; ohne Angabe gilt
unten mittig. Bei `100 %` Zeigerstärke wird die `viewBox` proportional auf die
konfigurierte Zeigerlänge skaliert. Die additive Pivot-Konfiguration bietet
einen kompatiblen Modus `svg` und einen Modus `custom`. `svg` übernimmt den
SVG-Drehpunkt beziehungsweise unten mittig und blendet die native Nabe bei
einem ausdrücklich gesetzten SVG-Drehpunkt wie bisher aus. `custom` übersetzt
horizontale und vertikale Prozentwerte von 0 bis 100 in `viewBox`-Koordinaten,
überschreibt damit einen eingebetteten SVG-Drehpunkt und hält die native
ECharts-Nabe sichtbar. So kann sie mit einem gezeichneten Zeigerring zur
Deckung gebracht werden, ohne die Nabe unabhängig vom rotierenden Zeiger zu
verschieben. Der Skalenbogen
verwendet entweder die Presetgeometrie oder einen Voll-, Dreiviertel-, Halb-, Viertel- oder
benutzerdefinierten Bogen. Anwenderpositionen werden wie auf einem Zifferblatt
gespeichert (`0°` oben, im Uhrzeigersinn) und sind auf 22,5-Grad-Schritte
begrenzt. Der Renderer übersetzt diese Positionen in die ECharts-Winkel; die
Formulardaten legen damit keine Bibliothekskoordinaten als öffentlichen Vertrag
fest. Bei benutzerdefinierten Bögen müssen Start und Ende verschieden sein.

Die später ergänzte Auswahl offizieller ECharts-Themes ist als eigener Vertrag in
[`ADR 0007`](0007-echarts-theme-assets-and-selection.md) festgelegt. Farben
folgen standardmäßig weiterhin diesem Theme. Ein ausdrücklich aktivierter
Custom-Modus überschreibt ausschließlich die Gauge-Rollen Zeiger,
Fortschrittsbogen, Ring, Skala und Teiler, Wert und Einheit sowie Titel mit
validierten RGB-Werten. Auch
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

Benutzer können Gauge-Layout, Zeiger, Bogen und Farben im Instanzformular auswählen und unmittelbar
in der SVG-Vorschau beurteilen. Nach dem Übernehmen verwendet die native Kachel
dieselbe Auswahl und dieselben relativen Feinabstimmungen. Weitere
Single-Presets können unter neuen stabilen IDs
ergänzt werden; bestehende IDs ändern ihre grundlegende Bedeutung nicht.

Die SVG-Vorschau bleibt eine gezielte Annäherung an den Canvas-Renderer und
kein pixelidentischer Browserersatz. Reale Symcon-Client- und Browserprüfungen
bleiben erforderlich. Die grundlegenden Preset-Proportionen wie Mittelpunkt,
Radius, Zeigerlänge, Skalenabstand sowie Wert- und Titelposition werden jedoch
zwischen Vorschau und nativer Kachel bewusst angeglichen. Gemischte
prozentuale und ausschließlich breitenabhängige Positionen werden dabei
vermieden, weil sie bei unterschiedlichen Seitenverhältnissen zu voneinander
abweichenden Radien und Abständen führen. Erst wenn mindestens
ein weiterer Diagrammtyp denselben allgemeinen Designervertrag benötigt, wird
eine Ergänzung der zentralen ModuleHelper erneut geprüft.

Die Anzahl der Hauptabschnitte des Speed-Presets wird an den konfigurierten
Wertebereich angepasst. Der Demo-Bereich 0 bis 240 erhält zwölf Abschnitte mit
20er-Schritten; beispielsweise 0 bis 1000 erhält zehn Abschnitte mit
100er-Schritten. Dadurch bleibt das Beispiel wiedererkennbar, ohne bei frei
konfigurierten Bereichen schlecht lesbare Zwischenwerte zu erzwingen.

## Nachweise

- Property-, Formular- und Statusverträge unter `tests/symcon_strict.php`
- Preset-Datenmodell, SVG-Vorschau und native Kachel unter
  `tests/gateway_gauges.php`
- SVG-Dekodierung, Allowlist und Größenlimits unter `tests/svg_path.php`
- Formularschema und Repositorystruktur unter `tests/validate_structure.php`
- Offizielle Gauge-Beispiele unter
  `https://echarts.apache.org/examples/en/index.html#chart-type-gauge`
