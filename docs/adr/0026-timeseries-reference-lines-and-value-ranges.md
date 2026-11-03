# ADR 0026: Quellenbezogene Referenzlinien und Wertebereiche in TimeSeries

- Status: Angenommen
- Datum: 2026-10-07
- Entscheider: Burki24
- Ergänzt: ADR 0017, ADR 0018, ADR 0020, ADR 0021 und ADR 0023
- Ersetzt durch: –

## Kontext

Zeitreihen benötigen neben den Messreihen statische Zielwerte, Grenzlinien und
farblich hinterlegte Wertebereiche. Die Markierung muss auch bei mehreren
Einheitengruppen eindeutig auf der richtigen Wertachse liegen. ECharts bietet
dafür mit `markLine` und `markArea` bereits native Funktionen. Nach ADR 0017
wird diese vorhandene Fähigkeit verwendet, statt Linien oder Flächen als
künstliche Datenreihen nachzubauen.

Die Quellgestaltung besitzt mit `libs/EChartsTimeSeriesDesign.php` bereits
eine familienbezogene zentrale Formularschicht. Der Gateway-Vertrag betrifft
dagegen ausschließlich gemeinsame Dateninfrastruktur und soll keine
Darstellungsoptionen erhalten.

## Entscheidung

`EChartsTimeSeries` erhält die additive String-Property `Annotations`. Sie
enthält eine geordnete Liste von höchstens 32 Einträgen. Jeder Eintrag
referenziert über `VariableID` eine vorhandene konfigurierte Quelle und ist
dadurch eindeutig deren Reihe und Wertachse zugeordnet.

Unterstützt werden:

- Referenzlinien mit Wert, optionaler Beschriftung, Farbe, Linienart und
  relativer Linienstärke;
- Wertebereiche mit Minimum, größerem Maximum, optionaler Beschriftung, Farbe
  und Deckkraft;
- die Reihenfarbe als Rückfall, wenn keine eigene Farbe gewählt ist.

Die wiederverwendbaren Auswahlwerte und der Zeileneditor liegen in
`libs/EChartsTimeSeriesDesign.php`. Persistenz, fachliche Validierung,
Quellenzuordnung und das normalisierte Chartmodell verbleiben im
TimeSeries-Gerätemodul. Eine Markierung für eine nicht konfigurierte Quelle,
ein nicht endlicher Wert oder ein Bereich mit `Minimum >= Maximum` erzeugt den
bestehenden Konfigurationsfehler 202.

Kachel und IPSView erhalten dasselbe normalisierte Modell. Der gemeinsame
Browserrenderer setzt dieses mit den nativen ECharts-Komponenten
`MarkLineComponent` und `MarkAreaComponent` um. Die lokale, festgeschriebene
TimeSeries-Runtime wird reproduzierbar um genau diese Komponenten ergänzt;
Gateway und synchronisierte ModuleHelper bleiben unverändert.

Die SVG-Formularvorschau zeigt Markierungen symbolisch, weil sie keine realen
Archivdaten und Achsenbereiche lädt. Sie bildet Typ, Farbe, Liniengestaltung,
Deckkraft und Beschriftung ab, verspricht aber keine pixelgenaue Wertposition.

## Folgen

Bestehende Instanzen bleiben kompatibel, da die neue Property standardmäßig
eine leere Liste enthält. Markierungen verändern weder Archivabfragen noch
Punktbudget, Aggregation oder Echtzeitfortschreibung. Sie werden bei jedem
Renderer-Update aus dem unveränderten Modell erneut erzeugt und bleiben damit
auch bei persistenten IPSView-Aktualisierungen erhalten.

Die TimeSeries-Runtime wird durch die zwei offiziellen ECharts-Komponenten
größer, bleibt aber unter dem im Projekt geprüften Limit von 600 KiB. Herkunft,
Größe und SHA-256 sind weiterhin dokumentiert und getestet.

## Nachweise

- Wiederverwendungsentscheidung in [ADR 0017](0017-reuse-before-new-chart-development.md)
- Mehr-Achsen-Vertrag in [ADR 0021](0021-timeseries-multiple-value-axes.md)
- Familienvertrag in `libs/EChartsTimeSeriesDesign.php`
- Normalisiertes Modell in `EChartsTimeSeries/module.php`
- Renderer in `EChartsTimeSeries/visualization/app.js`
- PHP-Vertrags- und Integrationstests sowie `tests/time_series_layout.js`
