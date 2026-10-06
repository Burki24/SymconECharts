# ADR 0023: Zeitreihen je Quelle gestalten und SVG-Flächenmuster absichern

- Status: Angenommen
- Datum: 2026-10-06
- Entscheider: Burki24
- Ergänzt: ADR 0017, ADR 0018, ADR 0020 und ADR 0022
- Ersetzt durch: –

## Kontext

Der erste TimeSeries-Designer steuert Linienstärke, Glättung, Datenpunkte und
Flächendeckkraft gemeinsam. Mehrere fachlich unterschiedliche Reihen sollen
zusätzlich unabhängig hervorgehoben werden können. Neben Farbe und Verlauf
soll eine Fläche auch ein eigenes SVG-Motiv wiederholen dürfen.

Die Gauge-Familie besitzt bereits einen bewährten Vertrag für umfangreiche,
aber in der Tabellenansicht ausgeblendete Zeilenoptionen. Mit
`EChartsSvgImage` existiert außerdem eine zentrale Sicherheitsgrenze für
eingebettete SVG-Bilder. Nach ADR 0017 werden beide Fähigkeiten
wiederverwendet, statt einen zweiten Dateiimport oder eine breite statische
Quellentabelle einzuführen.

## Entscheidung

`Sources` wird additiv um ein optionales individuelles Reihendesign ergänzt.
Ohne aktiviertes `UseIndividualDesign` gelten weiterhin vollständig die
gemeinsamen Designerwerte. Aktivierte Quellen können festlegen:

- durchgezogene, gestrichelte oder gepunktete Linie;
- relative Linienstärke und Glättung;
- Punktsymbol und relative Punktgröße;
- eigene Flächendeckkraft;
- einfarbige Füllung, linearen Verlauf oder wiederholtes SVG-Muster;
- optionale Verlaufsendfarbe und relative Größe des SVG-Musters.

Die zusätzlichen Listenfelder bleiben gespeichert, werden in der kompakten
Tabellenansicht bis auf den Schalter **Individual design** aber ausgeblendet.
Der Zeileneditor enthält die vollständige Gestaltung und aktualisiert nach
dem Bestätigen beide Formularvorschauen.

Der familienbezogene Vertrag wird in `libs/EChartsTimeSeriesDesign.php`
zentralisiert. Das Gerätemodul bleibt Eigentümer von `Sources`, Validierung und
fachlichem Chartmodell. `EChartsSvgImage` importiert das SVG unverändert als
einzige zentrale Sicherheitsgrenze: externe Referenzen, Skripte,
`foreignObject`, CSS-Blöcke und nicht unterstützte Inhalte werden abgewiesen.

Das Modell liefert nur normalisierte Designwerte und gegebenenfalls eine
bereinigte SVG-Data-URI. Der Browser erzeugt daraus ein lokales, wiederholtes
Canvas-Pattern. Bis das Bild geladen ist oder falls der Browser es nicht
dekodieren kann, bleibt die normale Serienfarbe als sichere Rückfalldarstellung
sichtbar. Kachel und IPSView verwenden dasselbe quellenbezogene Design;
ihre bereits getrennten globalen Designer bleiben unverändert.

## Folgen

Bestehende Instanzen und Quellen bleiben kompatibel, weil fehlende Felder dem
deaktivierten Einzeldesign entsprechen. Die bisherigen gemeinsamen Defaults
werden dadurch nicht verändert. Ungültige aktive Einzelwerte oder ein
unsicheres SVG setzen die Instanz kontrolliert in den bestehenden
Konfigurationsfehler 202.

SVG-Muster erhöhen die Modell- und HTML-Größe. Der vorhandene Import begrenzt
deshalb jede Datei auf 256 KiB und die bestehende Ausgabelimit-Prüfung bleibt
Teil der Integrationstests.

## Nachweise

- Wiederverwendungsentscheidung in [ADR 0017](0017-reuse-before-new-chart-development.md)
- Gemeinsamer TimeSeries-Designer in [ADR 0020](0020-timeseries-tile-designer.md)
- Zentraler SVG-Import in `libs/EChartsSvgImage.php`
- Quellenvertrag in `libs/EChartsTimeSeriesDesign.php`
- PHP-Vertrags- und Integrationstests sowie `tests/time_series_layout.js`
