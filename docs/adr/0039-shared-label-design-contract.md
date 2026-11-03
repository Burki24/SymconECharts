# ADR 0039: Gemeinsame Beschriftungsverträge für Diagrammfamilien

- Status: Angenommen
- Datum: 2026-10-12
- Entscheider: Burki24
- Ergänzt: ADR 0036, ADR 0037 und ADR 0038

## Kontext

Beschriftungspositionen und -abstände waren in mehreren ECharts-Modulen fest
codiert oder unterschiedlich benannt. Die Diagrammfamilien haben dennoch
verschiedene Koordinatensysteme: ein gemeinsames Formular mit allen Optionen
wäre fachlich falsch.

## Entscheidung

`libs/EChartsLabelDesign.php` definiert wiederkehrende Positionswerte,
Abstandsgrenzen, Formularbausteine und schematische SVG-Platzierung.
`libs/echarts-design.js` übersetzt gemeinsame Positions- und Abstandsangaben
in ECharts-Serienoptionen und versetzt Gauge-Beschriftungen relativ zu deren
jeweiliger Vorlage. Die Module halten ihre Properties, Defaults, fachliche
Validierung, Texte und visuellen Sonderfälle selbst. Es gibt keine universelle
Label-Konfiguration im Gateway.

Kachel und unabhängiges IPSView-Design erhalten dieselben für ihre Familie
passenden Regler. Neue Properties übernehmen den bisherigen Zustand:
`auto` erhält die automatische Balkenposition, Pie behält die Ausrichtung am
Containerrand, Polar behält seine berechneten Abstände (`-1`), TimeSeries
zeigt Punktwerte zunächst nicht, und Gauge-Verschiebungen beginnen bei null.
Konfigurierte manuelle Werte werden nur in der betreffenden Diagrammfamilie
angewandt. Anzeige-Namen stammen weiterhin aus konfigurierten Beschriftungen
oder Symcon-Namen; interne IDs werden niemals als Ersatz gezeigt.
Tacho und Chronograph erlauben bei aktiviertem individuellem Quellen-Design
zusätzlich pro Instrument eigene Skalenabstände und Textverschiebungen. Die
Quellenwerte übersteuern dann die globalen Gauge-Werte für dieses Instrument.

## Prüfung

Gemeinsame Bausteine und Rendereroptionen erhalten automatisierte Tests.
Formularvorschauen zeigen neue Werte, soweit sie den ECharts-Renderer
schematisch abbilden können; insbesondere Polar- und komplexe Gauge-Vorlagen
bleiben für genaue Positionen auf Kachel-/IPSView-Prüfungen angewiesen.
Die volle Wirkung von Randabständen, Überlappungsvermeidung und Gauge-Vorlagen
ist nach einem Modulupdate im Symcon-Testsystem bei mehreren Kachelgrößen zu
prüfen. IPSView kann zusätzlich vom Eigentümer in der nativen App geprüft
werden.
