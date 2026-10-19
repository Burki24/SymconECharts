# Entwicklung von SymconECharts

## Ziel und Abgrenzung

Symcon-Variablen und Archivdaten sollen ohne eigene JavaScript-Programmierung
als Messinstrumente und Diagramme dargestellt werden können. SymconECharts
entsteht unabhängig von JSLive; bestehende JSLive-Installationen werden nicht
verändert und JSLive ist keine Voraussetzung.

Native Symcon-Kacheln und einzelne IPSView-HTML-Widgets sind gleichwertige
Ausgabeziele. Eine Chart-Instanz soll beide Ausgabewege gleichzeitig bedienen
können. Die Anordnung mehrerer Charts übernimmt die jeweilige Visualisierung,
nicht eine eigene große Dashboard-Seite des Moduls.

Die gemeinsame JavaScript-Designbasis `libs/echarts-design.js` liefert mit
`chartTitleLayout()` den Abstand für den Symcon-Kachelkopf und den unabhängig
konfigurierten Diagrammtitel. Ein sichtbarer Kachelname unterdrückt den
Diagrammtitel nicht; die fachlichen Renderer platzieren ihre Inhalte unterhalb
beider Titel. Ein ausgeblendeter Kachelkopf gibt seinen Platz frei, während
IPSView nur den Diagrammtitel benötigt.

## Aktueller Stand und Zielarchitektur

BarWaterfall erweitert gemäß [ADR 0071](adr/0071-waterfall-category-navigation.md)
auf 2–100 Quellen und 101 modellierte Schritte einschließlich berechneter
Endsumme. `WaterfallData::Totals` zentralisiert die endliche geordnete Addition
für Modell und vollständige Vorschau. Ausschnitte verändern keine Zwischenstände
oder Gesamtbilanz. `echarts-category-navigation.js` teilt ausschließlich flüchtige
ID-Anker und Fensterbegrenzung mit BarCategory; Familiengeometrie und Bilanz bleiben
lokal. Die Einbettung erfolgt nur bei diesen beiden Familien über `EChartsAsset`.
Das Modell ist auf 200000 JSON-Bytes begrenzt, das vollständige HTML auf 1 MiB.

ThemeRiver ergänzt gemäß [ADR 0070](adr/0070-theme-river-counter-consumption.md)
die globale Property `Metric`: `average` bleibt Default, `consumption` fordert
explizit `sum` und Zählerarchive. `ThemeRiverData::ArchiveContract` besitzt
die familienbezogene Zuordnung; Gateway, Cache und dessen zentrale Übernahme
der Symcon-`Avg`-Zähleraggregate bleiben unverändert. Keine eigenen Rohwertdifferenzen,
Resetkorrekturen oder Leistungsintegration. Derselbe Join, Auswahl-, Zeitfenster-
und Budgetpfad gilt für beide Datengrundlagen; Renderer/Vorschau benennen Verbrauch
ausdrücklich und behandeln fehlende alte Modellfelder weiterhin als Mittelwert.

ThemeRiver erweitert gemäß [ADR 0069](adr/0069-theme-river-band-selection.md)
auf 16 Quellen. `EnableBandSelection` ist standardmäßig aus; das optionale
Quellenfeld `InitiallyVisible` wirkt nur bei Aktivierung. Die flüchtige
ID-Auswahl und Legendenanker nutzen den vorhandenen
`echarts-series-selection.js`-Baustein, große Starts `EChartsStartupState`
und `echarts-startup.js`. Die Geometrie projiziert lediglich sichtbare Werte
aus dem vollständig verbundenen Zeilenmodell; ursprüngliche Nullzeilen bleiben
Lücken. `ThemeRiverData` zentralisiert Quellen- und Ladegrenzen einschließlich
`CountLoadedAggregates`: 16000 empfangene Aggregate, höchstens 1000 je Quelle,
vor dem Join gezählt. 720 vollständige Intervalle bleiben die Darstellungsgrenze.
Modell- und HTML-Guards liegen bei 900000 Bytes beziehungsweise 1 MiB.
Die Vorschau zeigt höchstens acht wirksam anfangs sichtbare Bänder mit
Original-Farbindizes und gekennzeichneten Gesamtzahlen ohne Archivzugriff.

EChartsBarHistory erweitert gemäß
[ADR 0068](adr/0068-historical-bar-series-selection.md) auf 32 Quellen und
200–32000 Gesamtpunkte, behält aber Default 1000, 2000 je Abfrage/Reihe und
bis zu 16 effektive Einheitengruppen. Archivzeitstempel werden nicht
ausgerichtet oder ergänzt. Die gemeinsame ID-stabile Auswahllogik von
TimeSeries und BarHistory liegt in `echarts-series-selection.js`, eingebettet
über `EChartsAsset::SeriesSelectionJavaScript()`. Native Auswahl und
Legendenanker bleiben flüchtig; Zeitzoom nutzt weiter `echarts-zoom.js`.
BarHistory verwendet außerdem den vorhandenen `EChartsStartupState` und
`echarts-startup.js`, mit Modellgrenze 900000 Bytes und finalem 1-MiB-HTML-Guard.
Die Vorschau zeichnet höchstens drei anfangs sichtbare Quellen mit ursprünglichen
Farbindizes, ohne Archive zu lesen. Familiengeometrie und Einheitengrenzen
werden nicht in den Auswahlbaustein verschoben.

EChartsTimeSeries unterstützt gemäß
[ADR 0067](adr/0067-timeseries-more-series-and-selection.md) 32 Quellen und
200–32000 Gesamtpunkte bei unverändertem Default 2000 und höchstens 2000 je
Gateway-Anfrage/Reihe. Acht effektive Einheitengruppen bleiben die Grenze.
ID-stabile Reihenauswahl ist flüchtiger Rendererzustand, keine serverseitige
Quellenfilterung. Versteckte Reihen bleiben abonniert und im Archivbudget.
Das Modell ist auf 900000 JSON-Bytes begrenzt; ein zusätzlicher HTML-Guard
liefert bei Überschreitung von 1 MiB ein begrenztes Fehlerdokument.
Vorschauen zeichnen vier anfangs sichtbare Reihen, kennzeichnen die Gesamtzahl
und vereinfachen zu große SVG-Muster ausschließlich mit sichtbarem Hinweis.

EChartsGraph unterstützt gemäß [ADR 0066](adr/0066-larger-manual-graphs.md)
300 Knoten und 1000 ungerichtete Kanten. Die bestehenden Quellen-/Kantenregeln
bleiben in GraphModel, Z2M-Projektion und Exportbudgets bleiben getrennt.
`echarts-graph.js` zentralisiert ausschließlich native Ansichtserhaltung,
Tooltip- und Beschriftungsgrundlagen sowie den geschützten Kachelkopf.
Das Script wird nur in den beiden Graph-Familien eingebettet.
`EChartsStartupState` und `echarts-startup.js` verpacken große HTML-sichere
Startzustände oberhalb von 200000 Bytes verlustfrei komprimiert und entpacken
sie abbrechbar. TimeSeries und die Graph-Familien verwenden diese gemeinsame
Logik, ebenso BarHistory; `EChartsGraphOutput` bleibt kompatibler Adapter. Kleine Zustände bleiben
direkt. Neuere Vollzustände und Unload verwerfen veraltete Entpackergebnisse;
TimeSeries sammelt währenddessen höchstens 32000 Append-Punkte und spielt sie
nach dem Start gebündelt ein. Numeric-Typen einschließlich Float-Null bleiben
bei der JSON-Serialisierung erhalten.

Tree und danach TreeSystemHealth unterstützen gemäß
[ADR 0065](adr/0065-twelve-level-object-trees.md) zwölf Nachkommensebenen.
Gemeinsam sind die begrenzte Objektprojektion in `TreeHierarchy`, rekursive
Vorschauzeichnung in `EChartsTreePreview` und flüchtige native Ansichtsverwaltung
in `echarts-tree.js`. Dieses Script wird nur in den Tree-Ausgaben geladen.
Health-Regeln und deren Rohwertbewertung bleiben familienbezogen. Die bisherige
200000-Byte-Schwelle betrifft Startkompression, nicht eine harte Modellablehnung.

Treemap und anschließend Sunburst nutzen gemäß
[ADR 0064](adr/0064-flexible-manual-hierarchies.md) einen gemeinsamen rekursiven
Hierarchievertrag. Zentralisiert sind Quellen-/Pfadvalidierung und synthetische
Vorschaudaten in `EChartsHierarchySources`, Modellbau/Budgets in `TreemapModel`
sowie native Wurzelwiederherstellung in `echarts-design.js`. Familienbezogene
Geometrie, Ebenendesign und SVG-Zeichnung bleiben getrennt. Acht Ebenen zählen
das Blatt, aber nicht die künstliche Wurzel; alte Konfigurationen werden nicht
umgeschrieben.

Die [systematische Prüfung der Nutzungsgrenzen](NUTZUNGSGRENZEN.md) erfasst
alle 29 Diagrammfamilien und das Gateway. Sie trennt fachliche Verträge und
Ressourcenschutz von ausbaubaren Produktgrenzen. PictorialBar und BarCategory
unterstützen jetzt 100 Quellen mit Kategoriennavigation; PictorialBar besitzt
zusätzlich eine anpassbare Bezeichnungsspalte. BarCategory ergänzt einen
optionalen, an einer festen Min-/Max-Skala ausgerichteten Balkenverlauf.
StateTimeline unterstützt 32 Quellen mit unabhängiger Zeilennavigation und
einem Gesamtbudget von 16000 Rohwerten. Weitere Erweiterungen sind dort
priorisiert, aber noch nicht umgesetzt.

Die optionale Normalisierung aktueller Vergleiche ist für Pie, BarCategory,
BarPolar und PictorialBar gemäß
[ADR 0063](adr/0063-normalized-current-comparisons.md) umgesetzt. Die reine
Min/Max-Projektion liegt unter `libs`; Quellenvalidierung, Formular und
Geometrie bleiben in den Familien. Originalmetadaten und ID-Identität bleiben
erhalten; Gateway, Runtime und Mengenflussfamilien werden nicht verändert.

CalendarPie (`ECCP`) ergänzt gemäß [ADR 0060](adr/0060-calendar-pie-data-modes.md)
einen Monatskalender mit Tages-Kreisdiagrammen und den auswählbaren
Datengrundlagen Tagesmittelwerte, Zählerverbrauch und Zustandsdauer. Die
vorhandenen Archivoperationen bleiben unverändert. Die neue Gestaltung und
alle drei Modi müssen nach Modulupdate in Symcon und IPSView live geprüft
werden; lokale Nachweise sind in `tests/README.md` dokumentiert.

StateTimeline (`ECST`) ergänzt diskrete Boolean-/Integer-Roharchive gemäß
[ADR 0059](adr/0059-archive-state-timeline.md). Das Gateway stellt die additive
Operation `archive.states.read` mit einem Vorgänger bereit; Intervallbildung
und Zustandszuordnung bleiben im Gerät. Die Datengrenze von 250000 Bytes
bezieht sich auf HTML-sicheres JSON, um auch expandierende Sonderzeichen
innerhalb des Symcon-Ausgabepuffers zu halten. Lokale Tests ersetzen keine
Live-Abnahme nach Modulupdate in Symcon und IPSView.

ThemeRiver (`ECRV`) ergänzt abgeschlossene Standard-Archivmittelwerte gemäß
[ADR 0058](adr/0058-archive-theme-river.md). Eigene Intervallaufbereitung und
Unterbrechung vollständiger Abschnitte verhindern ECharts-Nullauffüllung.
Gemeinsame Quellen-, Designer-, Vorschau- und Transportbausteine bleiben
unverändert. Die Modul-GUID ist `{6767D036-5E2B-4D33-B7F1-C9F1B4376C04}`.
Live-Abnahmen in Symcon und IPSView sind nach dem Modulupdate erforderlich.

PictorialBar ergänzt aktuelle Vergleiche als eigene Symbolbalkenfamilie.
Der Quellenvertrag wurde mit Funnel in
`libs/EChartsCurrentComparisonSources.php` zentralisiert; Gateway-Zugriff,
Abonnements und Ausgaben verwenden die vorhandenen Bausteine.
Identität, Begrenzungen und Prüfplan stehen in
[ADR 0057](adr/0057-current-value-pictorial-bar.md).

Candlestick ergänzt die Archivfamilien um tatsächliche erste/letzte
Rohmesswerte und Extrema abgeschlossener Intervalle. Identität, Grenzen und
Wiederverwendung stehen in [ADR 0056](adr/0056-archive-candlestick.md).
Die Gateway-Operation bleibt unverändert; Aggregatmittelwerte ersetzen keine
Kerzen-Endpunkte. Die lokale Prüfung umfasst Datenberechnung, Integration,
beide Designer und echte ECharts-Geometrie. Die installierte Symcon-Kachel
und IPSView sind nach dem nächsten Modulupdate gesondert abzunehmen.

Die Library besitzt mit Gateway und eigenständigen Diagrammmodulen eine technische
Modulstruktur. Alle Module
verwenden `IPSModuleStrict`;
Gauge-Instanzen können vorhandene Gateways wiederverwenden. Ein versioniertes
Protokoll liefert geprüfte numerische Momentanwerte an ein minimales
Gauge-Datenmodell. Gauge Multi besitzt eine geordnete und validierte Liste aus
2 bis 16 Quellen. Gauge Single rendert sein Modell mit der lokal gebündelten
Apache-ECharts-Runtime 6.1.0 als responsive native HTML-SDK-Kachel und sendet
Wertänderungen ohne einen vollständigen Neuaufbau der Seite an den Browser.
Das Gauge-Single-Formular besitzt außerdem einen ersten Kacheldesigner. Er
wählt die layoutbezogenen Presets Basic, Simple, Progress und Speed und erzeugt
eine live aktualisierte SVG-Vorschau der noch nicht gespeicherten Einstellungen.
Unabhängig davon kann die Instanz zwischen dem automatischen Symcon-Design und
sechs lokal gebündelten offiziellen ECharts-Themes wählen. Zeigerform samt
abgesichertem pfadbasiertem SVG-Import, Skalenbogen mit Start- und
Endpositionen in 22,5-Grad-Schritten sowie sechs
Gauge-spezifische Farbrollen sind ebenfalls validiert konfigurierbar. Eine
optionale native ECharts-Graphic bildet eine runde oder dem Skalenbogen
folgende Zifferblattplatte mit eigener Gestaltung einschließlich linearer und
radialer Farbverläufe sowie eines bereinigten, auf die Plattenform
zugeschnittenen SVG-Hintergrunds. Gauge Single unterstützt außerdem validierte farbige Wertebereiche,
konfigurierbare Skalenunterteilungen und -abstände, beide Drehrichtungen,
responsive Positionsversätze, elementweise Sichtbarkeit sowie einen
gestaltbaren Wertekasten und optionale Schatteneffekte. Gauge Multi besitzt
mit Multi Title, Ringraster, konzentrischen Ringen und einer Wetterstations-
Vorlage responsive native Kacheln für 2 bis 16 Quellen. Seine gemeinsame
Designschicht steuert Zeiger, Naben, Skalenunterteilungen, Farbrollen und
Zifferblattplatten für alle Instrumente einer Ausgabe. Tacho und Chronograph
sind eigenständige Module für 2 bis 5 Quellen. Dort können einzelne Quellen
das Grunddesign einschließlich SVG-Zeiger, SVG-Nabe und SVG-Zifferblatt
überschreiben.
Alle Gauge-Module erzeugen optional
eine IPSView-WebContent-Variable mit vererbtem oder unabhängigem Design auf
demselben Renderer. Die gemeinsame ECharts-Hintergrundlogik unter
`libs/EChartsIPSViewBackground.php` steuert für Gauge und TimeSeries das
Durchscheinen des IPSView-Hintergrunds sowie optionale Farbe und Deckkraft.
Weitere klassische Multi-Instrumentenpanel-Vorlagen fehlen noch. Das
Time-Series-Modul
nutzt den Gateway-Vertrag `archive.read` für rohe oder aggregierte Werte und
rendert daraus die erste native Zeitreihen-Kachel sowie optional eine getrennt
gestaltbare IPSView-WebContent-Ausgabe.
Quellen können den gemeinsamen Designer optional durch einen zentral in
`libs/EChartsTimeSeriesDesign.php` geführten Einzelvertrag für Linie,
Datenpunkte und Fläche überschreiben. Eigene SVG-Flächenmuster passieren dabei
ausschließlich den vorhandenen sicheren Import aus `EChartsSvgImage`.
Der gleiche quellbezogene Vertrag führt die persistierten Optionen für
automatische, aus der Variablendarstellung gelesene oder manuell festgelegte
Wertachsenbereiche. Die Zusammenführung je effektiver Einheit verbleibt als
fachliche Validierung im Zeitreihenmodul.
Ein getrenntes `Annotations`-Modell ergänzt bis zu 32 quellengebundene
Referenzlinien und Wertebereiche. Die wiederverwendbaren Formularverträge
liegen ebenfalls in `libs/EChartsTimeSeriesDesign.php`; Validierung,
Normalisierung und Zuordnung zur konkreten Reihe verbleiben im Gerätemodul.
Die Testsuite prüft zusätzlich
zu Struktur und Metadaten die Strict-Verträge, das Protokoll, die
ECharts-Integrität und den Gauge→Gateway→Visualisierungs-Datenweg unter PHP
8.5 mit Test-Doppeln.

| Bestandteil | Name | Aufgabe |
|---|---|---|
| Library | SymconECharts | Gemeinsames installierbares Paket |
| Splitter | EChartsGateway | Gemeinsame Dienste für mehrere Chart-Instanzen |
| Gerät | EChartsGaugeSingle | Ein Gauge-Chart mit genau einer Quellvariable und zwei Ausgabewegen |
| Gerät | EChartsGaugeMulti | Ein zusammengesetzter Gauge-Chart mit 2 bis 16 Quellen |
| Gerät | EChartsGaugeTacho | Tacho-Cockpit mit 2 bis 5 individuell gestaltbaren Instrumenten |
| Gerät | EChartsGaugeChronograph | Chronograph mit 2 bis 5 individuell gestaltbaren Instrumenten |
| Gerät | EChartsTimeSeries | Historische und live fortgeschriebene Linien und Flächen für 1 bis 32 Quellen, Reihenauswahl, bis zu acht links/rechts angeordnete Einheitengruppen sowie getrenntes Kachel- und IPSView-Design |
| Gerät | EChartsBarCategory | Aktuelle einfache, gruppierte oder gestapelte Kategorienvergleiche für 1 bis 100 Quellen mit gemeinsamer Einheit, Kategoriennavigation, optionalem Min-/Max-Farbverlauf und getrenntem Kachel- und IPSView-Design |
| Gerät | EChartsBarHistory | Historische rohe oder aggregierte Balken von 1 bis 32 Archivquellen, bis 16 Einheitengruppen und Reihenauswahl in nativer Kachel und optionalem IPSView-HTML |
| Gerät | EChartsBarWaterfall | Aktueller Startwert mit 1 bis 99 signierten Änderungen, berechneter Gesamtbilanz und ID-stabiler Kategoriennavigation in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsBarPolar | Aktuelle Werte aus 1 bis 16 Quellen als radiale Balken oder konzentrische Bögen in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsPie | Aktuelle nichtnegative Anteile aus 1 bis 16 Quellen als Kreis, Donut oder Nightingale-Rose in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsScatter | Archivierte X/Y-Werte mit optionaler Bubble-Größe, ausschließlich zeitgleiche abgeschlossene Intervalle ohne Interpolation, in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsHeatmap | Eine Archivvariable als lokale Tages-/Wochentag-Stunden-Matrix mit separaten Kachel- und IPSView-Designern |
| Gerät | EChartsTree | Begrenzte, lesende IP-Symcon-Objekthierarchie unter einer Kategorie oder Instanz in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsTreeSystemHealth | Lesende Objekthierarchie mit expliziten Zustandsregeln, Schwellen und aggregiertem sichtbarem Anlagenzustand |
| Gerät | EChartsGraph | Frei konfigurierte, ungerichtete Verbindungen zwischen ausgewählten Symcon-Objekten in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsGraphZ2M | Zuletzt exportierte Zigbee2MQTT-Netzwerkkarte mit gerichteten LQI-Verbindungen in Kachel und optionalem IPSView-HTML; ohne Scan-Auslösung |
| Gerät | EChartsTreemap | Manuell gruppierte aktuelle numerische Variablenwerte als Treemap in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsSunburst | Manuell gruppierte aktuelle numerische Variablenwerte als Sunburst in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsParallel | Gemeinsame abgeschlossene Archivintervalle von 2–10 Variablen als Parallelkoordinaten in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsSankey | Aktuelle nichtnegative Mengenflüsse zwischen manuellen Knoten und Verbindungen in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsCalendar | Tagesmittel/-minimum/-maximum einer Standardvariable oder Tagesverbrauch eines Zählers als Jahreskalender mit lesender Jahresnavigation |
| Gerät | EChartsRadar | Aktuelle Vergleichsgruppen auf 3–10 ausdrücklich begrenzten Achsen mit eigenen Einheiten |
| Gerät | EChartsBoxplot | Verteilungen abgeschlossener Stundenmittelwerte aus 1–32 Standard-Archivvariablen derselben Einheit mit Kategoriennavigation |
| Gerät | EChartsFunnel | 1–16 manuell benannte Stufen mit aktuellen nichtnegativen Werten derselben Einheit |

Eine eigene I/O-Instanz, ein Konfigurator und Discovery gehören nicht zum
Anfangsumfang. Datenquellen sind Teil der jeweiligen Gauge-Konfiguration und
keine eigenen Geräteinstanzen. Mehrere Gauge-Instanzen sollen einen vorhandenen
Gateway gemeinsam verwenden. Im regulären Betrieb genügt eine gemeinsame
Gateway-Instanz für sämtliche ECharts-Diagramminstanzen. Die von Symcon bei
`type: connect` weiterhin angebotene Neuanlage wird nicht technisch gesperrt;
zusätzliche Gateways bilden jedoch getrennte Infrastruktur und sind kein
Standard. Weitere Chartfamilien erhalten eigene Gerätemodule; sie werden nicht
als Modi eines universellen Widgets ergänzt.

## Festgelegte Identitäten

Diese bereits vergebenen IDs werden bei der weiteren Entwicklung beibehalten.
Modul-IDs und Datenfluss-IDs haben unterschiedliche Aufgaben.

| Identität | GUID |
|---|---|
| Library SymconECharts | `{66BE21BE-988A-10EB-1BBB-1E2F444E9F85}` |
| Modul EChartsGateway | `{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}` |
| Modul EChartsGaugeSingle | `{0CAA2780-342F-E5B9-2865-CB8DCED0BE7C}` |
| Modul EChartsGaugeMulti | {E667D9C1-379D-44ED-A313-FF21FEFC355F} |
| Modul EChartsGaugeTacho | `{9D072BFE-45B4-4442-B1D2-4FC458C3BABD}` |
| Modul EChartsGaugeChronograph | `{B99C3ADA-9A90-486F-97E3-96C39554D9A6}` |
| Modul EChartsTimeSeries | `{EF172F3B-50F5-41D1-B18E-6BCDEAECCABA}` |
| Modul EChartsBarCategory | `{F9A88437-FE9E-4038-ADAF-775623D2DDC1}` |
| Modul EChartsBarHistory | `{BCC80B03-2FC9-45CA-B004-2C81D0689F50}` |
| Modul EChartsBarWaterfall | `{792B12B3-E608-4301-868D-5FE96DDB8864}` |
| Modul EChartsBarPolar | `{B22B5AB4-CDA4-416F-9FF1-4126734E078B}` |
| Modul EChartsPie | `{176F1531-ABC4-4AE5-9DE5-8F3F006C8237}` |
| Modul EChartsScatter | `{9865CDB4-5CA9-4C34-A148-983E0B5F2FA6}` |
| Modul EChartsHeatmap | `{62F06D1A-873F-4B0E-9F3E-6AA9AA1A8274}` |
| Modul EChartsTree | `{2B2C26A3-6F92-48D0-ADE1-5F03D7282EF8}` |
| Modul EChartsTreeSystemHealth | `{DE4A700B-A38A-4441-A73A-C5F1D7DCCBE2}` |
| Modul EChartsGraph | `{BD3C8351-82EA-4B6B-BD82-1E034B7E68C6}` |
| Modul EChartsGraphZ2M | `{A9C67877-1CF7-4E9E-9B17-C9AC2C200483}` |
| Modul EChartsTreemap | `{9A4CDA7E-00FE-49FB-A017-20FDA75E7C04}` |
| Modul EChartsSunburst | `{A90CEE66-8708-4C2B-A475-1BAEDC74C359}` |
| Modul EChartsParallel | `{2B3B30E7-F7F2-47CF-A8AB-1E27F896A7DD}` |
| Modul EChartsSankey | `{D0F77DFF-6B50-4901-A2F0-5A98159E2559}` |
| Modul EChartsCalendar | `{3F927445-41C7-44F4-8CEB-069581C5F3D1}` |
| Modul EChartsRadar | `{B596EE88-C7A6-4A9B-891A-DD81BFF08A94}` |
| Modul EChartsBoxplot | `{B9B5BCD1-BC63-42A6-8132-98CE744DAE1C}` |
| Modul EChartsFunnel | `{491282D5-3E26-4601-AEA7-ABD7404642B2}` |
| Datenfluss Gauge → Gateway | `{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}` |
| Datenfluss Gateway → Gauge | `{E4749B72-912B-E3E3-1C57-D19019FFDD84}` |

Die Funktionspräfixe sind `ECGW` für das Gateway, `ECGS` für Gauge Single,
`ECGM` für Gauge Multi, `ECGT` für Gauge Tacho, `ECGC` für Gauge
Chronograph, `ECTS` für Time Series, `ECBC` für Bar Category, `ECBH` für Bar History, `ECBW` für Bar Waterfall, `ECBP` für Bar Polar, `ECPI` für Pie, `ECSC` für Scatter, `ECHM` für Heatmap, `ECTR` für Tree, `ECTH` für Tree System Health, `ECGR` für den allgemeinen Graphen, `ECGZ` für den Zigbee2MQTT-Graphen, `ECTM` für Treemap, `ECSB` für Sunburst, `ECPA` für Parallel und `ECSA` für Sankey. Nach der verbindlichen Präfixkonvention steht `EC` für
ECharts; die letzten zwei Buchstaben bezeichnen Aufgabe oder Modultyp. Die
Entscheidungen zur Modulstruktur und zu den GUIDs dokumentieren
[`ADR 0001`](adr/0001-chart-family-modules.md) und
[`ADR 0003`](adr/0003-single-and-multi-gauge-modules.md).
Den Parent-, Zuständigkeits- und Datenvertrag der ersten technischen Vertikale
dokumentiert [`ADR 0002`](adr/0002-gateway-gauge-contract.md).
Das persistente Multi-Quellenmodell dokumentiert
[`ADR 0004`](adr/0004-gauge-multi-source-contract.md).
Die festgeschriebene ECharts-Runtime und den ersten nativen Renderer
dokumentiert [`ADR 0005`](adr/0005-echarts-runtime-and-native-renderer.md).
Den Vertrag des ersten Kacheldesigners dokumentiert
[`ADR 0006`](adr/0006-gauge-single-tile-designer.md).
Die gemeinsame Theme-Assetbasis und die instanzbezogene Auswahl dokumentiert
[`ADR 0007`](adr/0007-echarts-theme-assets-and-selection.md).
Die optionale IPSView-WebContent-Ausgabe und ihr getrenntes Design dokumentiert
[`ADR 0008`](adr/0008-gauge-single-ipsview-output.md).
Den persistenten Gateway-Transport für flackerfreie IPSView-Aktualisierungen
dokumentiert [`ADR 0024`](adr/0024-persistent-ipsview-transport.md).
Den ersten sichtbaren Gauge-Multi-Renderer dokumentiert
[`ADR 0009`](adr/0009-gauge-multi-title-renderer.md).
Die beiden ergänzenden Ring-Vorlagen dokumentiert
[`ADR 0010`](adr/0010-gauge-multi-ring-presets.md).
Die erste analoge Wetterstations-Vorlage dokumentiert
[`ADR 0011`](adr/0011-weather-station-gauge-preset.md).
Das zusätzliche Tacho-Cockpit dokumentiert
[`ADR 0012`](adr/0012-tacho-cockpit-gauge-preset.md).
Den eingebetteten Chronographen dokumentiert
[`ADR 0014`](adr/0014-embedded-chronograph-gauge-preset.md).
Die eigenständigen Tacho- und Chronograph-Module dokumentiert
[`ADR 0016`](adr/0016-dedicated-tacho-and-chronograph-modules.md).
Die verbindliche Prüfung und Zentralisierung bereits vorhandener
Chart-Funktionen vor einer Neuentwicklung dokumentiert
[`ADR 0017`](adr/0017-reuse-before-new-chart-development.md).
Den Umfang der ersten Zeitreihenfamilie und der dafür benötigten
Gateway-Archivoperation dokumentiert
[`ADR 0018`](adr/0018-first-time-series-family-and-archive-contract.md).
Die Aufteilung der Bar-Familien und den ersten Category-Bar-Vertrag
dokumentiert [`ADR 0029`](adr/0029-bar-chart-families-and-category-bar.md).
Die erste vollständige historische Balken-Vertikale und ihre gemeinsame
Archiv-Abfrageplanung dokumentiert
[`ADR 0030`](adr/0030-first-historical-bar-vertical.md).
Die Entscheidung für den weiteren Ausbau innerhalb desselben Moduls und den
optionalen IPSView-Ausgabeweg dokumentiert
[`ADR 0031`](adr/0031-historical-bar-one-module-expansion.md).
Der weitere Ausbau von EChartsBarHistory erfolgt innerhalb desselben Moduls:
Die Einquellen-Stufe wurde zuerst für Kachel und IPSView abgesichert;
mehrere Quellen verwenden die bestehende Quellenliste. Ein getrenntes
Single-/Multi-Modulpaar ist nicht vorgesehen. Die Regeln für bis zu 16
Quellen, benachbarte Balken und mehrere Einheitenachsen legt
[`ADR 0033`](adr/0033-historical-bar-multi-source-axes.md) fest.
Der eigene Vertrag für aktuelle Waterfall-Schritte mit Startwert, signierten
Änderungen und berechneter Endsumme ist in
[`ADR 0034`](adr/0034-waterfall-bar-current-values.md) festgelegt.
Den eigenen Polar-Bar-Vertrag für aktuelle Werte und zwei Polar-Geometrien
dokumentiert [`ADR 0035`](adr/0035-polar-bar-current-values.md).
Die gemeinsame Grundreihenfolge der Diagrammformulare, die Live-Vorschau
für beide Ausgabewege und die Grenze zwischen Modul und `libs` legt
[`ADR 0036`](adr/0036-consistent-chart-forms-and-live-previews.md) fest.
Die fachlich unterschiedlichen, aber in beiden Ausgabewegen gleich
aufgebauten Designer-Unterbereiche legt
[`ADR 0037`](adr/0037-topic-sections-in-chart-designers.md) fest.
Die eigene Pie-Familie für aktuelle Anteile und die drei Geometrien
Kreis, Donut und Nightingale-Rose legt
[`ADR 0038`](adr/0038-pie-current-values.md) fest.
Die Verknüpfung historischer X/Y- und optionaler Größenwerte nur über
identische abgeschlossene Archivintervalle ohne Interpolation legt
[`ADR 0040`](adr/0040-scatter-archive-interval-pairing.md) fest.
Die eigene Tages-/Wochentag-Stunden-Heatmap und die Abgrenzung zum Kalenderlayout
legt [`ADR 0041`](adr/0041-heatmap-weekday-hour-family.md) fest; ihre
gleitenden Zeitfenster regelt [`ADR 0043`](adr/0043-heatmap-rolling-windows-and-navigation.md).
Die Erweiterung um Mittelwert, Minimum, Maximum und ausdrücklichen Zählerverbrauch
für Heatmap und Calendar regelt [ADR 0062](adr/0062-calendar-and-heatmap-archive-metrics.md).
Die vorhandenen Gateway-Reducer bleiben unverändert; Kennzahlenauswahl,
Archivtypprüfung und zweite Wochentag-/Stundenreduktion sind familienlokal.

Die lesende Objekt-Hierarchie und ihre Grenzen legt
[`ADR 0044`](adr/0044-tree-symcon-object-hierarchy.md) fest. Die explizite
Zustandsbewertung beschreibt [`ADR 0045`](adr/0045-tree-plant-status.md),
ihre Auslagerung in `EChartsTreeSystemHealth` regelt
[`ADR 0046`](adr/0046-tree-system-health-module-split.md).
Den allgemeinen Graphen mit manuell gewählten Kanten und seine Trennung von
der Zigbee2MQTT-Topologie regelt
[`ADR 0047`](adr/0047-general-and-zigbee-graph-split.md).
Die manuell gruppierte Treemap mit aktuellen Variablenwerten beschreibt
[`ADR 0048`](adr/0048-manual-current-value-treemap.md).
Die davon getrennte Sunburst-Familie beschreibt
[`ADR 0049`](adr/0049-manual-current-value-sunburst.md).
Die archivbasierte Parallelfamilie beschreibt
[`ADR 0050`](adr/0050-archive-parallel-chart.md).
Die aktuelle Sankey-Familie mit manuellen Knoten und Verbindungen beschreibt
[`ADR 0051`](adr/0051-manual-current-value-sankey.md).
Den Jahreskalender mit Tagesmittelwerten beschreibt
[`ADR 0052`](adr/0052-calendar-daily-archive-means.md). Er nutzt die vorhandene
Archivoperation und eigene Familienvalidierung; Gateway und Stunden-Heatmap
werden nicht um Kalender-Modi erweitert. Das Präfix lautet `ECCL`.
Radar (`ECRA`) vergleicht aktuelle Werte vollständiger manueller Gruppen
gemäß [ADR 0053](adr/0053-current-value-radar.md). Normierung und begrenzte
Geometrie bleiben familienlokal; aktuelle Quellen und Transport werden geteilt.
Boxplot (`ECBX`) vergleicht Verteilungen abgeschlossener Archiv-Stundenmittelwerte
gemäß [ADR 0054](adr/0054-hourly-archive-boxplot.md). Der gemeinsame Gateway-Vertrag
bleibt unverändert; R7-Quartile, Min-/Max-Enden, Einheitenvalidierung und
synthetische Vorschaugeometrie liegen lokal in der Familie. Ein gesonderter
Runtime-Build enthält nur die benötigten Komponenten. Die Erweiterung auf
32 Quellen nutzt native DataZoom-Kategoriennavigation mit ID-stabilem Ausschnitt
und vollständiger Werteskala. Das gemeinsame Budget von 10000 Stundenmitteln
ergänzt das bisherige Limit von 1000 je Quelle; überschrittene oder gekürzte
Antworten führen ausdrücklich zum Fehler statt zu verfälschten Quartilen.

Benutzerdefinierte rollierende Zeiträume für Boxplot, Parallel, Candlestick,
ThemeRiver und StateTimeline folgen [ADR 0061](adr/0061-custom-archive-durations.md).
Die Properties ergänzen vorhandene Zeitfenster kompatibel; `EChartsArchiveQuery`
bleibt die zentrale Planungsbasis. Sein neuer begrenzter Whole-Interval-Pfad
ersetzt ausschließlich die doppelte Candlestick-/ThemeRiver-Fensterplanung.
Der bisherige Querypfad anderer Familien und ihre Budgets bleiben unverändert.

## Weitere ECharts-Familien im Plan

Funnel (`ECFU`) ist gemäß [ADR 0055](adr/0055-current-value-funnel.md)
als eigenständige aktuelle Familie ergänzt. Quellenabonnements, Darstellung,
Designer und Transport werden wiederverwendet; keine neue Gateway-Operation.
Die neuen MCP-SDK-Seiten für Listenspeicherung, Referenzen und Nachrichten
wurden während der Entwicklung genutzt. Reale MCP-Kachelvorschau benötigt
erst die vom Eigentümer installierte Version und eine passende Instanz.

Nach Heatmap, Tree, Graph, Treemap, Sunburst, Parallel, Sankey und Calendar
steht die Bewertung gemeinsamer Datenverarbeitung an. **Dataset**
ist in ECharts eine familienübergreifende Datenkomponente, keine eigene
Diagrammfamilie oder Symcon-Geräteinstanz; seine Nutzung wird bei passenden
Serien auf Datenfluss und Wiederverwendung geprüft. Diese Einträge sind
Roadmap, keine Behauptung bereits implementierter Module.

### Dataset-Bewertung vom 16.10.2026

Laut [offiziellem Dataset-Handbuch](https://echarts.apache.org/handbook/en/concepts/dataset/)
können tabellarische Daten über `dataset.source` und `series.encode` mehreren
Serien zugeordnet werden. Der Bestand verwendet aktuell familienbezogene
`series.data`: Category hat individuelle Datenpunktstile, TimeSeries eigene
Zeitpunkte und Datenlücken, Scatter abgeschlossene Punktzuordnungen und
Parallel genau eine bereits zusammengeführte Tabelle. Ein gemeinsam benötigter
Transformationsweg ist bisher nicht belegt. Deshalb erfolgt jetzt weder ein
pauschaler Rendererumbau noch eine Gateway-/Propertymigration oder ein
Dataset-Modul. Bei einem konkreten Mehrfachnutzen wird die Bewertung mit
Vergleichstests und gemessenem Auslieferungsbudget wieder aufgenommen.

Für die vier Bar-Module bündelt `libs/EChartsBarPreviewForm.php` die
read-only-Formularverdrahtung, einschließlich zweier `Image`-Felder,
erhaltener bestehender Änderungsaktionen und der vollständigen ungespeicherten,
ID-basierten Quellenliste bei Hinzufügen, Bearbeiten, Löschen und Umordnen.
`libs/EChartsBarPreview.php` teilt ausschließlich Vorschau-Palette,
SVG-Rahmen, begrenzte Quellen- und Darstellungsauflösung, formatierte
Wertbeschriftungen sowie die schematische Größe wiederholter
SVG-Muster in Category und History; Modus, Skala und Diagrammgeometrie
verbleiben in den jeweiligen Bar-Modulen. Der synchronisierte
`SVGPreviewHelper` wird unverändert wiederverwendet. History nutzt
gekennzeichnete Beispielverläufe und fragt beim Formularbearbeiten kein
Archiv ab. Die SVG-Vorschau ist kein Ersatz für visuelle Laufzeitprüfungen.
`libs/EChartsBarTypography.php` bündelt die wiederkehrende Registrierung,
Validierung, Modellabbildung und Formularverdrahtung optionaler Schrift- und
Farbfelder für Category, Waterfall und Polar. Die konkrete Feldliste und
Rubrik bleibt je Diagrammfamilie lokal; History behält seine bereits
veröffentlichten Schrift-Properties und ergänzt nur die Legende. Der
Browserbaustein `libs/echarts-design.js` teilt die prozentuale Schriftgröße,
während die jeweiligen Renderer ihre Theme-Rückfälle und Geometrie bestimmen.
Bei begrenzten konzentrischen Polar-Bögen zeichnet ECharts 6.1.0 die
eingebauten Balkenhintergründe intern zunächst als Vollkreise. Der Polar-
Renderer passt die `BarView._backgroundEls` nach `setOption()` und beim
Resize an die tatsächliche Winkelachse an. Da dies eine interne ECharts-
Struktur nutzt, ist der zugehörige Render-Test bei jedem Runtime-Update
erneut auszuführen.
Die SVG-Balkenmuster von BarCategory und BarHistory verwenden denselben
begrenzten Import über `libs/EChartsSvgImage.php` wie TimeSeries-Flächen.
Die Browserdekodierung und wiederholte Pattern-Erzeugung liegt nun für alle
drei Renderer in `libs/echarts-pattern.js`. Muster verändern nur die Füllung
normaler Balken, nicht deren Geometrie. Ein Ladefehler fällt auf die bisherige
Serienfarbe zurück; Kachel- und unabhängiges IPSView-Design besitzen eigene
Datei- und Größenwerte.

## Daten, Konfiguration und Ausgabe

Symcon-Datenanbindung, Diagrammkonfiguration und ECharts-Darstellung werden
getrennt. Die Konfiguration der jeweiligen Chartfamilien-Instanz bleibt die
maßgebliche Quelle für ihre Variablen, Datenreihen und Auswertungsregeln.
Layout-Presets und ECharts-Themes bleiben getrennte Konfigurationsachsen. Die
Theme-Auswahl gehört zur Diagramminstanz, während die geprüften offiziellen
Theme-Assets und ihre stabilen IDs repositoryweit gemeinsam genutzt werden.

Das Gateway stellt den gemeinsamen Zugriff auf numerische Momentanwerte sowie
die für die erste Zeitreihenfamilie festgelegte Operation `archive.read`
bereit. Sie normalisiert rohe und aggregierte Archivwerte und verwendet einen
kurzlebigen, auf 32 Einträge begrenzten Cache. Ein
HTML-Dokument wird nicht bei jeder Messwertänderung neu erzeugt. Datenmenge,
Aktualisierungsrate und Animationen werden insbesondere für mehrere
gleichzeitig sichtbare Charts begrenzt und getestet.

Die Zoomsteuerung für TimeSeries, BarHistory und Scatter liegt als
projektspezifischer Browserbaustein in `libs/echarts-zoom.js`. Sie stellt
`dataZoom`-Optionen, die IPSView-Mausradbehandlung und die Übernahme des
gewählten Ausschnitts über gleichartige Datenaktualisierungen bereit. Die
jeweilige Diagramminstanz entscheidet weiterhin über Aktivierung, Layout und
ihre eigenen Zeitbereichs- und Design-Properties; der gemeinsame Baustein
stellt für horizontale und vertikale Regler dieselbe Stärke von 12 px bzw.
8 px in kompakten Ansichten bereit und ändert
keine Archive oder Symcon-Daten.
Scatter nutzt getrennte X/Y-`dataZoom`-Fenster und Schieberegler. Der
ursprüngliche Zeitachsenvertrag bleibt unverändert;
X/Y-Ausschnitte werden nur bei kompatiblem Zeitraum übernommen.

Time Series trennt archivfreie Echtzeitwerte gemäß
[`ADR 0019`](adr/0019-timeseries-realtime-without-archive.md) ausdrücklich von
Rohwerten. `realtime` beginnt mit `current.read` und sammelt nachfolgende
`VM_UPDATE`-Punkte nur im offenen Browser; `raw` bleibt eine unveränderte
Archivabfrage.

Die native Kachel und IPSView erhalten getrennte Ausgabeadapter zum gemeinsamen
Darstellungskern. Gauge Single schreibt ein vollständiges, eigenständiges
Startdokument in eine lokale WebContent-Variable. Laufzeitwerte erreichen das
bestehende ECharts-Objekt anschließend über den zentralen, kanalgebundenen
Gateway-WebHook und WebSocket, ohne die Variable neu zu schreiben. Eine
abgeschaltete IPSView-Ausgabe beeinträchtigt den nativen Ausgabeweg nicht und
lässt eine bereits angelegte Ausgabevariable bis zu einer ausdrücklich
bestätigten Löschung bestehen.

## Plattform und Modulbasis

Entwicklungsziel und deklarierte Mindestversion sind **Symcon 9.0 / PHP 8.5**.
Eine Kompatibilität zu älteren Symcon-Versionen wird nicht versprochen.

Die technische Umsetzung verwendet die moderne Modulbasis `IPSModuleStrict`
mit passenden Typdeklarationen. Die Parent-Auswahl wird über
`GetCompatibleParents()` mit `type: connect` aufgebaut. Dadurch können neue
Geräteinstanzen bestehende Gateways mit bereits verbundenen Kindern
wiederverwenden. `RequireParent()` wird nicht verwendet.

Die zugehörigen Schnittstellen sind in der
[Symcon-Moduldokumentation](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/)
und unter
[GetCompatibleParents](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/getcompatibleparents/)
beschrieben.

## Bibliotheken und gemeinsame Helper

ECharts wird ohne projektspezifische Änderungen am Bibliothekskern verwendet.
Die diagrammfamilienspezifischen Browser-Builds 6.1.0 sind lokal festgeschrieben, werden aus
der offiziellen Tree-Shaking-API reproduzierbar erzeugt und mit Prüfsumme,
Originallizenz sowie NOTICE ausgeliefert. Vor der Einbettung wird er auf
Integrität geprüft. Die getrennten Gauge- und kartesischen Einstiegspunkte
enthalten jeweils nur die benötigten Charts und Komponenten und halten das vollständige HTML-Dokument unter dem
Symcon-Output-Buffer-Limit. Im Betrieb wird weder `latest` noch eine externe
CDN-Ressource geladen. Aktualisierungen werden erneut gegen APIs, Lizenz,
Lieferartefakte, Größe und die vorhandenen Renderer geprüft.

Dark, Vintage, Macarons, Infographic, Shine und Roma werden unverändert aus
demselben ECharts-6.1.0-Paket übernommen, mit eigenen SHA-256-Werten geprüft
und lokal registriert. Der gemeinsame projektspezifische Katalog liegt in
`libs/EChartsAsset.php`; die synchronisierten Helper enthalten weder
ECharts-Theme-Dateien noch Theme-IDs.

Vorhandene zentrale Helper aus `Symcon_ModuleHelper` werden wiederverwendet
und über den vorgesehenen Synchronisierungsweg eingebunden. Allgemeine
Erweiterungen gehören in das zentrale Helper-Projekt, nicht in abweichende
lokale Kopien. Synchronisierte Helper liegen ausschließlich unter
`libs/helper`; projektspezifische Verträge und Chart-Logik liegen direkt unter
`libs` beziehungsweise in den zuständigen Modulen.

Vor der Programmierung einer neuen Funktion oder Chartfamilie werden die
fertigen Charts und vorhandenen Bibliotheken auf eine bereits bewährte Lösung
geprüft. Eine kompatible, innerhalb von SymconECharts wiederverwendbare
Funktion wird als projektspezifischer Baustein direkt unter `libs`
zentralisiert. ECharts-unabhängige Funktionen mit Nutzen für weitere
Symcon-Module entstehen dagegen in `Symcon_ModuleHelper` und gelangen nur über
den festgelegten Synchronisierungsweg nach `libs/helper`. Die Abgrenzung und
die erforderlichen Vertragsprüfungen legt
[`ADR 0017`](adr/0017-reuse-before-new-chart-development.md) fest.

Der projektspezifische Browserbaustein `libs/echarts-design.js` wandelt
konfigurierte Deckkraftwerte von 0 bis 100 Prozent für mehrere Chartfamilien
einheitlich in ECharts-Werte von 0 bis 1 um. History- und Polar-Balken,
TimeSeries-Flächen und -Annotationen sowie Gauge-Hintergrundplatten nutzen
denselben Baustein. Die jeweiligen Defaults, Properties, Validierungen und
ECharts-Ziele verbleiben in den Gerätemodulen. Der Baustein wird in Kachel und
IPSView vor dem jeweiligen Renderer eingebettet.
Die Abfrage der Betriebssystemvorgabe für reduzierte Bewegung und die
Umsetzung von `animation`, `animationDuration` und
`animationDurationUpdate` liegen ebenfalls in `libs/echarts-design.js`.
`libs/EChartsAnimationDesign.php` vereinheitlicht Property-Registrierung,
Validierung und Formularreaktionen aller zehn Gerätemodule. Defaults und
ECharts-Ziele verbleiben in den jeweiligen Chartfamilien. Die
Gauge-`detail.valueAnimation` folgt dem gemeinsamen Animationsschalter;
TimeSeries bleibt wegen vieler historischer Datenpunkte standardmäßig aus.
Easing und feste Verzögerung für Start und Aktualisierung werden ebenfalls
zentral in libs/echarts-design.js umgesetzt. Die Verzögerung gilt bewusst für
den ganzen Übergang statt als datenpunktweise Staffelung; dies verhindert bei
Archivdiagrammen mit vielen Punkten eine kumulativ lange Wartezeit. Bei 0 ms
Übergangsdauer entfällt auch die jeweilige Verzögerung. Die Formularfelder
und ihre Auswahlwerte erzeugt der PHP-Baustein zentral. Der
ECharts-Schwellenwert bleibt unverändert.
Die optionale ECharts-Balkenkontur wird dort ebenfalls aus Breite, eigener oder
Theme-Farbe als `itemStyle` erzeugt. Aktuell nutzt Polar Bar diesen gemeinsamen
Vertrag; weitere Balkenmodule können ihn ohne Kopie der Rendererlogik nutzen.
Zulässige Eingabebereiche und Formularfelder bleiben pro Modul definiert.

Gemeinsame ECharts-Gauge-Designsemantik liegt in
`libs/EChartsGaugeDesign.php`. Der Baustein vereinheitlicht für Single und
Multi insbesondere RGB-Farben sowie den validierten Import von SVG-Zeigern
einschließlich Ankerpunkt und von bereinigten SVG-Zifferblatthintergründen.
Für Gauge Multi, Tacho und Chronograph teilt er außerdem Validierung,
Formularfelder, Stilabbildung und SVG-Vorschau des äußeren Diagrammtitels.
Die Eigenschaften der einzelnen Instrumenttitel bleiben davon getrennt;
der Browser nutzt die vorhandene prozentuale Schriftgröße aus
`libs/echarts-design.js`.
Property-Registrierung, die fachliche Formularstruktur, Vorlagengeometrie und
Renderer bleiben im jeweiligen Gerätemodul, weil sie dessen öffentlichen
Vertrag bestimmen.

Eine gemeinsame Dateiauslieferung bedeutet nicht, dass mehrere getrennte
Browserflächen eine einzige ECharts-Laufzeit teilen. Ressourcenbedarf und
Aktualisierungsverhalten werden auf den Zielgeräten geprüft.

## Entwicklungsablauf und Prüfungen

Die Entwicklung und Erprobung erfolgen auf `dev`. Geprüfte Stände gelangen
per Pull Request nach `main`; der Entwicklungsbranch wird nicht automatisch
gelöscht.

Nach einem normalen Push auf `dev` erhöht der Metadatenworkflow die gemeinsame
Library-Version im Format `Hauptversion.Nebenstand`, leitet `build` aus dem
Quellcommit ab und setzt `date` auf dessen Commit-Zeit. Der getrennte
Metadatencommit löst keine weitere Erhöhung aus. Tags, Releases und
Veröffentlichungen bleiben manuelle, ausdrücklich freizugebende Schritte.

Für die technische Projektbasis sind Struktur- und JSON-Prüfungen,
PHP-Syntaxprüfungen, Modultests und der gemeinsame StylePHP-/CI-Ablauf
eingerichtet. Änderungen an den Visualisierungen benötigen zusätzlich
Browserprüfungen in beiden Ausgabewegen. Ein PHP-Lint ersetzt keinen
Symcon-Laufzeittest und keine Visualisierungsprüfung.

Vor der Übergabe commitbereiter Änderungen wird der vollständige lokale Lauf
gestartet:

```text
php tests/quality.php --fix
```

Er korrigiert ausschließlich mechanische PHP- und JSON-Formatierung, prüft
anschließend die Korrekturen erneut und führt Syntax-, Vertrags-, Integrations-
und Layouttests jeweils mit eigenem Fehlerstatus aus. Der reine Prüfmodus
`php tests/quality.php` verändert keine Dateien. Optional aktiviert
`git config core.hooksPath .githooks` den versionierten Pre-Push-Hook. Der Hook
stoppt einen Push, wenn der Qualitätslauf fehlschlägt oder noch nicht
committete Formatkorrekturen erzeugt.

Die offizielle Symcon-Stylekonfiguration wird als Git-Submodul unter `.style`
eingebunden. Sie bleibt dadurch auf einen nachvollziehbaren StylePHP-Commit
festgeschrieben und kann über den normalen Submodulablauf aktualisiert werden.

Der erste Funktionsumfang umfasst ein radiales Messinstrument in
`EChartsGaugeSingle` und zusammengesetzte Gauges auf Basis des vorhandenen
Mehrquellenmodells in `EChartsGaugeMulti`. Bei der sichtbaren Umsetzung werden
mehrere getrennte Gauge-Instanzen, beide Ausgabewege gleichzeitig,
Größenwechsel sowie abgeschaltete IPSView-Ausgabe getestet. Die erste
Zeitreihen-Vertikale einschließlich optionaler IPSView-Ausgabe ist ebenfalls
implementiert; weitere Chartfamilien sind
spätere, getrennt zu entscheidende Ausbaustufen.
Vor ihrer Umsetzung erfolgt die Wiederverwendungsprüfung nach
[`ADR 0017`](adr/0017-reuse-before-new-chart-development.md).
Für die erste Zeitreihenfamilie legt
[`ADR 0018`](adr/0018-first-time-series-family-and-archive-contract.md) den
Ausgangsumfang und den implementierten Gateway-Vertrag `archive.read` fest.
[`ADR 0019`](adr/0019-timeseries-realtime-without-archive.md) ergänzt den
archivfreien Echtzeitmodus, [`ADR 0020`](adr/0020-timeseries-tile-designer.md)
den Kacheldesigner und [`ADR 0021`](adr/0021-timeseries-multiple-value-axes.md)
die Mehr-Achsen-Anordnung. Die getrennte IPSView-Ausgabe und ihr unabhängiger
Designvertrag sind in
[`ADR 0022`](adr/0022-timeseries-ipsview-output.md) festgelegt.
Der gemeinsame persistente IPSView-Transport ist in
[`ADR 0024`](adr/0024-persistent-ipsview-transport.md) festgelegt.
Das quellenbezogene Einzeldesign einschließlich sicherer SVG-Flächenmuster
dokumentiert
[`ADR 0023`](adr/0023-timeseries-source-design-and-svg-area-patterns.md).
Quellenbezogene Referenzlinien und Wertebereiche sowie die dafür erweiterte
lokale ECharts-Runtime dokumentiert
[`ADR 0026`](adr/0026-timeseries-reference-lines-and-value-ranges.md).
Benutzerdefinierte rollende Zeiträume und wahlweise geerbte oder unabhängige
Zeiteinstellungen für Kachel und IPSView folgen
[`ADR 0027`](adr/0027-timeseries-custom-ranges-and-time-axis-labels.md).
Dieselbe Entscheidung umfasst kalendergebundene Zeiträume für heute, gestern,
die laufende Woche und den laufenden Monat. Deren Grenzen werden in der
Symcon-Zeitzone gebildet und bei Zeitumstellungen nicht auf feste
24-Stunden-Tage reduziert.
Die additive Datenlückenerkennung aus
[`ADR 0028`](adr/0028-timeseries-data-gap-detection.md) überführt fehlende
Messintervalle erst im Browserrenderer in explizite ECharts-Unterbrechungen.
Das archivierte beziehungsweise live fortgeschriebene Quellmodell bleibt
dabei unverändert und die Funktion ist für bestehende Instanzen deaktiviert.
Das Gerätemodul setzt davon Quellen, Roh-, Aggregat- und Echtzeitmodus,
Punktbudget, Mehr-Achsen-Modell, native Kachel und die optionale IPSView-
Ausgabe um.

## Laufzeitnachweise

Am 07.10.2026 wurde die native TimeSeries-Ausgabe auf der erreichbaren
Symcon-9.1-Testebene ausschließlich lesend geprüft. Eine aktive Instanz mit
drei Quellen lieferte ein gültiges Echtzeitmodell mit getrennten Achsen für
Temperatur, Luftfeuchtigkeit und Luftdruck. Konfigurationsformular,
Funktionsregistrierung und Browser-IFrame waren erreichbar; die zugängliche
ECharts-Beschreibung bestätigte drei dargestellte Reihen und fortgeschriebene
Echtzeitpunkte. Seit dem letzten Statistik-Reset lagen keine Kernel-Fehler vor.

Die Prüfung änderte weder Instanzkonfigurationen noch Variablenwerte. Ein
Laufzeitnachweis unter Symcon 9.0 sowie ein Test der bei dieser Instanz
deaktivierten IPSView-Ausgabe bleiben ausdrücklich offen. Der im äußeren
Symcon-WebFront auftretende `MutationObserver`-Fehler lag außerhalb des
Diagramm-IFrames und wurde nicht dem ECharts-Renderer zugerechnet.

## Dokumentation und Lizenzen

Die [Haupt-README](../README.md) und die README-Dateien der Module sind reine
Benutzerdokumentation. Sie beschreiben Voraussetzungen, Installation,
Konfiguration, Variablen, Bedienung und öffentliche Funktionen. Interne
Architektur, Entwicklung, Tests und CI werden in diesem Dokument, den ADRs
oder den Testdokumenten gepflegt. `README1.md` bleibt als unveränderte
Ausgangsvorlage erhalten.

Jede sichtbare Änderung wird im selben fachlichen Zusammenhang in der
[CHANGELOG.md](../CHANGELOG.md) unter `# Unveröffentlicht` eingetragen. Erst
eine ausdrücklich beauftragte Release-Vorbereitung ordnet diese Einträge einer
Version und einem Veröffentlichungsdatum zu. Interne Refactorings und reine
Formatkorrekturen benötigen keinen Changelog-Eintrag.

Geplanter und tatsächlich vorhandener Funktionsumfang werden ausdrücklich
getrennt. Beispiel-APIs, Store-Veröffentlichungen oder Versionsfreigaben
werden nicht vor ihrer Umsetzung behauptet.

Eigene Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](../LICENSE). Fremdkomponenten behalten
ihre Originallizenzen; die konkrete Einbindung wird in
[THIRD_PARTY_NOTICES.md](../THIRD_PARTY_NOTICES.md) dokumentiert.

Required Notice: Copyright 2026 Burkhard Kneiseler. SymconECharts.
