# Entwicklung von SymconECharts

Der gemeinsame [JSLive-Export-/Importvertrag 1.0](JSLIVE_MIGRATION_CONTRACT.md)
ist gemäß [ADR 0109](adr/0109-jslive-migration-contract.md) und JSLive ADR 0007
spezifiziert. Die öffentliche Übernahmezusage bleibt bis zur Implementierung
und gemeinsamen Abnahme offen; die erste Beta ist dafür zurückgestellt.
JSLive erfasst fachliche Konfiguration, Steuerzustände, Quellen, Archivregeln,
Darstellung und Ressourcen. Profile werden vollständig in profilunabhängige
native Darstellungen und Chart-Metadaten übersetzt. Bestehende Quellen, Actions,
Archive und Designs bleiben erhalten. Feldbilanz, Vorschau, ausdrückliche
Bestätigung und wiederaufnehmbares Importjournal gehören zur Implementierung.
Eine Spezifikation belegt noch keine vorhandene Importfunktion. Die abgeschlossene
Nutzungsgrenzen-Roadmap wird dadurch nicht rückwirkend wieder geöffnet.

Die zentrale Gateway-Zustandsnormalisierung nutzt gemäß
[ADR 0108](adr/0108-duration-aware-archive-states.md) die native Archiv-Dauer,
um mehrere Wechsel derselben Sekunde ausschließlich bei genau einem positiven
Dauereintrag aufzulösen. StateTimeline und CalendarPie teilen diese Prüfung;
unaufgelöste Widersprüche bleiben Fehler. Rohbudgets, Protokoll, Vorgänger,
Abschnittsbildung und Designs bleiben erhalten. Archive werden nicht bereinigt.

Der gemeinsame Gateway-Hook wird gemäß [ADR 0107](adr/0107-shared-gateway-hook-ownership.md)
koordiniert und routet zum tatsächlichen Datenflussparent. Peer-Aufrufe erfolgen
nur bei aktiven Schnittstellen, damit ein Bibliotheksreload keine noch nicht
verfügbaren Modulinterfaces anspricht. Die gemeinsame Beta-Abnahme steht im
[Bericht vom 28.10.2026](BETA_ABNAHME-2026-10-28.md), die vorbereiteten deutschen
Store-Texte und verbleibenden Eigentümerschritte unter [Beta-Store](BETA_STORE.md).

Heatmap ergänzt gemäß [ADR 0106](adr/0106-heatmap-comparison-views.md) optionale
1–4-Vergleichsansichten. Ein familienlokaler Plan kombiniert die bestehende
globale oder eigene absolute Zeitraumplanung mit flüchtiger Navigation.
Identische Abfragen werden geteilt; sieben Chunks bleiben ein Gesamtbudget.
Beide Ausgaben teilen die bisherige native Heatmap-Optionsfunktion, beide SVGs
die bisherige synthetische Geometrie. Gemeinsame oder getrennte Farbskalen ändern
keine Originalwerte. Lokale Prüfungen und die funktionale MCP-Abnahme sind beschrieben; der
Eigentümer bestätigt die Vergleichsansichten in Kachel und tatsächlichem IPSView.

Candlestick ergänzt gemäß [ADR 0105](adr/0105-candlestick-comparison-panels.md)
einen ausdrücklich aktivierten 1–4-Panel-Vertrag. Quellenidentität und Metadaten,
zentrale Zeitplanung, bestehender Rohwertleser mit gemeinsamem 63-/64000-Budget,
Zoomsteuerung und verlustfreie Startzustands-Komprimierung werden wiederverwendet.
Jedes Panel behält eigene Werteskala, Einheit, Rohwertstatistik und Lücken. Beide
Ausgabeadapter teilen die native Mehrfach-Grid-Runtime; beide reinen SVG-Entwürfe
komponieren dieselbe vorhandene Kerzengeometrie. Kleine Flächen scrollen, das
Mausrad zoomt über jedem Koordinatensystem. Lokale Tests und synthetische Frames
sind geprüft; MCP-Abnahme und Eigentümerbestätigung in Kachel/IPSView liegen vor.
Echte Touchgeräte bleiben ungeprüft.

## Ziel und Abgrenzung

Symcon-Variablen und Archivdaten sollen ohne eigene JavaScript-Programmierung
als Messinstrumente und Diagramme dargestellt werden können. SymconECharts
entsteht unabhängig von JSLive; bestehende JSLive-Installationen werden nicht
verändert und JSLive ist keine Voraussetzung.

Native Symcon-Kacheln und einzelne IPSView-HTML-Widgets sind gleichwertige
Ausgabeziele. Eine Chart-Instanz soll beide Ausgabewege gleichzeitig bedienen
können. Die Anordnung mehrerer Charts übernimmt die jeweilige Visualisierung,
nicht eine eigene große Dashboard-Seite des Moduls.

Die native Kachelüberschrift wird zentral im `ResponsiveVisualizationHelper`
aus `Symcon_ModuleHelper` ausgewertet. Alle 29 Diagrammmodule übernehmen mit
`ResponsiveVisualizationLayoutOptions(!$ipsView)` die Bootstrap-Felder
`tileHeaderVisible` und `tileHeaderInset`. Sichtbare native Kachelköpfe
reservieren 58 Pixel; ausgeblendete Kachelköpfe und IPSView null Pixel.
Die Renderer lesen ausschließlich den gelieferten Inset. Ihre eigenen
Abstände für Diagrammtitel und Bedienelemente bleiben erhalten. Insbesondere
behalten Multi-Gauges ihren zusätzlichen Abstand von sechs Pixeln.
Die gemeinsame JavaScript-Designbasis `libs/echarts-design.js` liefert mit
`chartTitleLayout()` weiterhin die fachliche Position des Diagrammtitels.
Ein sichtbarer Kachelname unterdrückt diesen nicht. Renderer mit eigenem
Frame reichen keinen zweiten nativen Inset an die Titelberechnung weiter.
Der optionale Helper-CSS-Frame ist hier nicht aktiviert, weil die Diagramme
ihre bestehende Geometrie selbst verwalten.

Die zentrale Zuständigkeit und die unveränderten Ausgabegeometrien stehen in
[ADR 0087](adr/0087-shared-native-tile-layout-contract.md).

Die gemeinsame Gauge-Runtime registriert seit der Korrektur vom 20.10.2026
auch den offiziellen `TitleComponent`. GaugeMulti, Tacho und Chronograph
zeichnen damit die bereits konfigurierten äußeren Diagrammtitel. GaugeSingle
behält seinen instrumenteigenen Titel. ECharts bleibt bei 6.1.0; der gezielte
Gauge-Neubau verändert keine anderen Familien, Konfigurationen oder Skalen.
`tests/gauge_runtime_titles.js` prüft bytegleiche Reproduzierbarkeit und echte
Canvas-Zeichenaufrufe für Kachel und IPSView statt nur vorhandener Titeloptionen.

## Aktueller Stand und Zielarchitektur

PictorialBar ergänzt gemäß [ADR 0104](adr/0104-pictorialbar-multiple-source-import.md)
die formulargebundene Mehrfachübernahme nach Objektzweig, Name und genauer Einheit.
Die vorhandene zentrale `VariableSearchHelper`-Suche und native/Legacy-Metadaten
werden wiederverwendet; gemeinsame Library-Adapter liegen in `EChartsSourceImport`
und `EChartsSourceImportForm`. Frische ID-Prüfung, append-only Entwürfe und die
bisherige vollständige Quellenvalidierung schützen bestehende Listen und Designs.
100 Quellen/64 KiB, begrenzte Suche sowie Renderer und Archive bleiben unverändert.
Die funktionale MCP-Prüfung mit Speicherung von 100 Quellen ist bestanden.
Der dabei gefundene Rückmeldefehler wird durch `echo` in den Buttoncallbacks
korrigiert; Tests prüfen nun die tatsächliche Skriptausgabe statt `eval`-Returns.
Die korrigierten Fehlermeldungen sind inzwischen als echte Konsolendialoge
bestätigt. Der verbliebene Erfolgspräfix `MESSAGE:` wird ausschließlich bei der
Formularausgabe entfernt; der Modulfunktionsvertrag bleibt gleich. Exakte
Textregressionen sichern neue und wiederholte Auswahlen ab. Nur diese kosmetische
Bereinigung bleibt nach dem nächsten Modulupdate live zu bestätigen.
Graph-Gewichte sind inzwischen per MCP funktional geprüft und vom
Eigentümer in Kachel und tatsächlichem IPSView bestätigt.

Graph ergänzt gemäß [ADR 0103](adr/0103-weighted-graph-relationships.md)
optionale Gewichte je Verbindung aus manuellen Zahlen oder aktuellen Quellen.
Die gemeinsame `GraphModel`-Berechnung verwendet den bestehenden numerischen
Bereichsvertrag und die Min/Max-Projektion; Originale und Metadaten bleiben
getrennt. `EChartsCurrentSources` liefert Gateway-Lesezugriff und Referenzen/
VM_UPDATE; dieselbe ID darf mehrere Kanten versorgen und wird je Modell nur
einmal gelesen. Die Quellen-ID muss kein Graph-Knoten sein. Vorschauen lesen
die aktuellen Kernelwerte ohne statusändernde Gateway-Aufrufe. Beide Pfade
verwenden dasselbe Modell und die gemeinsame Metadaten-/Zahlenformatierung.
Formatierte Gewichtswerte entstehen aus dem gelesenen Snapshot, nicht aus
einem zweiten möglicherweise bereits geänderten Variablenwert.
Kachel/IPSView besitzen unabhängige Pixelbreiten bei gemeinsamem festen
Gewichtsbezug. Die native Runtime erhält diese Breiten, keine fachlichen
Kräftewerte. Ein pro Zeile neu initialisiertes Formular vermeidet alte
Dialogwerte bei bisher ungewichteten Altzeilen, ohne ganze Listen mit neuen
Defaultfeldern aufzublähen. Runtime, bestehende Designs und Schutzbudgets
bleiben erhalten. MCP-Abnahme ist bestanden; der Eigentümer bestätigt die
gewichteten Beziehungen in Kachel und tatsächlichem IPSView.

Graph ergänzt gemäß [ADR 0102](adr/0102-directed-graph-relationships.md)
`DirectedEdges=false`. Dieselbe `GraphModel`-Prüfung versorgt gespeicherte
Modelle und Entwurfsvorschauen: ungeordnete Dubletten im Altmodus, geordnete
Dubletten im gerichteten Modus, Selbstkanten und unbekannte Endpunkte bleiben
ungültig. Gegenrichtungen erhalten gemeinsam berechnete Metadaten; native Pfeile,
getrennte Kurven und Richtungstooltips ändern weder Knotenschlüssel noch Designs.
Die geteilte Graph-/Z2M-Ansichtsverwaltung bleibt unverändert. Gerichtetes
Kreislayout verwendet explizite Kreispositionen mit nativer einfacher
Kantenprojektion, da ECharts' radiale Kreis-Kurven antipodische Gegenrichtungen
nicht trennen können. Runtime-Version/-Datei und sämtliche Budgets bleiben
erhalten. MCP funktional geprüft; der Eigentümer bestätigt gerichtete
Beziehungen in Kachel und tatsächlichem IPSView. Live-Push war in der statischen
MCP-Vorschau separat nicht nachweisbar und gilt damit nicht als dort geprüft.

Radar ergänzt gemäß [ADR 0101](adr/0101-radar-incomplete-groups.md)
`AllowIncompleteGroups=false`. Nur nicht konfigurierte Gruppe-Achse-Zuordnungen
werden optional. Alle konfigurierten Quellen bleiben in Validierung, Gateway,
Abonnements und Fehlermeldungen erhalten. `RadarModel` liefert gemeinsam für
Live-Ausgaben und SVGs ausgerichtete Nullplätze, fehlende Achsenschlüssel und
benachbarte belegte Kanten. Die native Radar-Serie bleibt für vollständige
Gruppen erhalten; Teilgruppen nutzen den offiziellen CustomChart ohne Fläche.
Fragezeichen, Legendenzusatz, Tooltip/ARIA und dauerhafter Hinweis erklären
die Lücken. Keine Änderungen gespeicherter Designs oder der Schutzbudgets.
Die gezielt neu gebaute Radar-Runtime bleibt bei ECharts 6.1.0 und unter
550000 Bytes. Die funktionale MCP-Prüfung ist bestanden; der Eigentümer
bestätigt die Teilgruppen in Kachel und tatsächlichem IPSView.

Radar ergänzt gemäß [ADR 0100](adr/0100-radar-inverse-axes.md) das optionale
Boolean-Feld `Axes[].Inverse` mit Default false. `RadarModel` projiziert allein
den begrenzten Radius invers; Originalwerte, Min/Max, Einheiten, Präzision,
Quell-IDs und Zeitstempel bleiben erhalten. Die numerische Bereichsprüfung
verwendet jetzt den vorhandenen `EChartsValueRange`, die fachliche Projektion
bleibt familienlokal und versorgt beide Live-Ausgaben und SVGs. Achsennamen,
Tooltip und ARIA kennzeichnen die Umkehr; Bereichstexte laufen innen nach außen.
Bestehende Designer und 3–10-/16-/160-/Bytebudgets ändern sich nicht.
Die funktionale MCP-Abnahme auf Symcon 9.1 ist bestanden; der Eigentümer
bestätigt inverse Achsen in Kachel und tatsächlichem IPSView.

CalendarPie ergänzt gemäß [ADR 0099](adr/0099-calendar-pie-incomplete-days.md)
`ShowIncompleteDays` mit kompatiblem Default false. Die familienlokale
Tagespolicy versorgt Live-Modell und beide synthetischen SVGs; der Renderer
verwendet das additive `showPie` und erhält für alte Modelle einen strengen
Fallback. Teilkreise behalten Status, fehlende Quell-IDs und Originalformate.
Raute und dauerhafter Hinweis markieren den nur auf verfügbare Quellen
bezogenen Nenner auch ohne Tooltip/Legende. Keine Nullauffüllung oder Änderung
an Archivprüfung, Zustandsdauern, Designs oder Budgets. Die vorhandene zentrale
Formular-/SVG-Infrastruktur wird ohne lokale Helper-Änderungen weiterverwendet.
Die funktionale MCP-Prüfung wurde bestanden; der Eigentümer bestätigt die
optionalen Teilquellen in Kachel und IPSView.

BarWaterfall ergänzt gemäß [ADR 0098](adr/0098-waterfall-subtotals-and-restarts.md)
`SubtotalAfter` und `Restart` je Quellenzeile mit Default false. Der vorhandene
familienlokale `WaterfallData` bildet jetzt den vollständigen, ID-stabilen
Schrittplan statt nur einer Summenliste. Live-Modell und beide SVG-Entwürfe
teilen diese Planung; die bisher doppelte Modellbildung wurde entfernt.
Zwischensummen sind berechnete Nullbasis-Säulen ohne neue Quellen/Zeitstempel.
Neustarts ersetzen den lokalen Abschnittsstand durch den ausgewählten Rohwert;
vorherige Abschlüsse bleiben erhalten, die letzte Summe wird ausdrücklich
abschnittsbezogen gekennzeichnet. Alle Quellen bleiben in Prüfung, Gateway,
Referenzen und VM_UPDATE-Abonnements. Die vollständige Skala umfasst frühere
Abschnitte; der bestehende Kategorieanker wird unverändert wiederverwendet.
2–100 Quellen, maximal 201 Schritte und 200000 Modellbytes/1 MiB HTML bleiben
begrenzt. Neue Säulen erben vorhandene Start-/Summenfarben. Die geprüften
Upstream-Helper besitzen keinen Waterfall-Bilanzvertrag; deren Formular-/SVG-
Infrastruktur bleibt unverändert in Gebrauch. Keine Helper-Kopie oder neue
Fremdabhängigkeit. Die Vorschau projiziert höchstens fünf Schritte plus
Endsumme aus dem vollständigen Plan und zählt tatsächlich gezeigte Quellen.
Die funktionale MCP-Prüfung ist bestanden; der Eigentümer bestätigt
Zwischensummen und Neustarts in Kachel und tatsächlichem IPSView.

Sankey ergänzt gemäß [ADR 0097](adr/0097-sankey-calculated-balances.md)
`Nodes[].Balance` als ausdrückliches Opt-in mit kompatiblem Default false.
Der bestehende `SankeyModel` berechnet ausschließlich aus gemessenen effektiven
Flüssen je ausgewähltem Knoten eine Differenz und hängt höchstens einen
Terminalknoten samt Verbindung an. Abgeleitete Flüsse besitzen keine Variablen-ID
oder Messzeitstempel; ihre Grundlage bleibt als Metadatum erhalten. Kein
generierter Fluss beeinflusst die Berechnung benachbarter Knoten. Die bestehenden
100-/200-Grenzen umfassen konfigurierte und zusätzliche Elemente gemeinsam;
zu große Modelle erhalten Diagnose statt Teilgeometrie. Relative Rundungsreste
bis 1e-12 der größeren Knotensumme erzeugen keinen Zusatzfluss.
Live-Ausgaben und beide SVG-Vorschauen teilen dieselbe Modellbildung und
statusfreie Entwurfsleselogik. Gemeinsame Formular-/SVG-Bausteine werden
wiederverwendet; neue fachfremde Abstraktionen oder Helper-Kopien sind unnötig.
Bestehende Designs bleiben unverändert; nur bei berechneten Flüssen wird Raum
für einen dauerhaften Kennzeichnungshinweis reserviert. Die anschließende
funktionale MCP-Prüfung auf Symcon 9.1 ist bestanden; der Eigentümer bestätigt
Rest-/Bilanzknoten in tatsächlicher Kachel und IPSView.

Sankey ergänzt gemäß [ADR 0096](adr/0096-sankey-negative-flow-reversal.md)
das optionale Boolean-Feld `ReverseNegative` je Quellenzeile (Default false).
`SankeyModel` bildet Betragsbreiten und tatsächliche Richtungen; konfigurierte
und effektive Topologie teilen dieselbe begrenzte DAG-Prüfung. Negative
Originalwerte, IDs, Zeitstempel und konfigurierte Endpunkte bleiben erhalten.
Live-Ausgaben und beide SVG-Vorschauen verwenden denselben Modellbauer;
Formdiagnose und Vorschauen teilen einen statusfreien Momentanwertleser.
Die vorhandene kompakte Callback-Erzeugung verhindert bei maximalen signierten
Modellen ein zu großes Formular. Keine Helper-Kopie, keine Änderung an Gateway,
Runtime, Designs oder Schutzgrenzen. Die MCP-Prüfung und Eigentümerbestätigung
für tatsächliche Kachel und IPSView liegen für die Kantenumkehr vor.

Sankey ergänzt gemäß [ADR 0095](adr/0095-sankey-source-unit-validation.md)
`UnitMode=manual|automatic|checked`. `manual` ist unveränderter Default;
`Unit` bleibt gespeichert und wird automatisch nur als inaktives Feld verborgen.
Die familienlokale Unit-Policy in `SankeyModel` und `GetValidatedConfiguration`
werden für Live-Ausgaben, Diagnosen und beide echten Momentanwertvorschauen
verwendet. `EChartsVariablePresentation::Resolve` erhält einen optionalen
strikten Zugriff; bestehende Consumer behalten den bisherigen toleranten
Default. Der geprüfte Symcon_ModuleHelper erzeugt Darstellungsarrays, bietet
aber keine solche lesende ECharts-Metadatenauflösung. Synchronisierte Helper,
Gateway und gepinnte Runtime bleiben unverändert.
Alle Quellen einschließlich Nullflüssen werden geprüft; IDs, Rohwerte,
Abonnements und 100-/200-/65536-/1-MiB-Schutzgrenzen bleiben erhalten.
Gemeinsame Formular-/SVG-/Rendererbausteine bleiben in Gebrauch. Die neue
Auswahl ist auf Symcon 9.1 per MCP geprüft; der Eigentümer bestätigt Kachel und IPSView.

GraphZ2M ergänzt gemäß [ADR 0094](adr/0094-zigbee-link-selection.md) die
additive Verbindungsauswahl `strongest`, `weakest` und `range`.
`ZigbeeGraphModel` prüft Auswahl und Grenzen, löst gerichtete Dubletten auf,
filtert danach einschließlich und wendet erst zuletzt das unveränderte
Anzeigebudget an. Live-Ausgaben und schematische, exportfreie SVG-Entwürfe
verwenden denselben Baustein. Formularsichtbarkeit nutzt den vorhandenen
ConfigurationFormHelper; Graph-Ansichtserhaltung bleibt in `echarts-graph.js`.
Keine Änderung an Gateway, Fremdruntime, synchronisierten Helpern oder Exportleser.
Alle Geräte, unabhängige Designer, absolute LQI-Farben und 500/10000/2000-/16-MiB-
Schutzgrenzen bleiben erhalten. Die funktionale MCP-Abnahme auf Symcon 9.1
liegt vor; der Eigentümer bestätigt die Auswahl in Kachel und tatsächlichem IPSView.

Candlestick ergänzt gemäß [ADR 0093](adr/0093-candlestick-minute-week-intervals.md)
15-/30-Minuten- und lokale ISO-Wochenintervalle. `EChartsArchiveQuery` teilt
die begrenzte Enumeration mit den bisherigen Stunden-/Tagesfenstern;
zusätzliche Rohintervalle erweitern nicht die Symcon-Aggregatmodi anderer
Familien. Rohwertleser, Cache, Renderer und 720/63/64000-Budgets bleiben erhalten.
Die neuen Intervallansichten sind per MCP funktional geprüft; der Eigentümer
bestätigt Kachel und tatsächliches IPSView. Echte Touchgeräte bleiben ungeprüft.

Candlestick ergänzt gemäß [ADR 0092](adr/0092-candlestick-time-zoom.md)
optionalen Zeitzoom als reine Ansicht. `libs/echarts-zoom.js` ergänzt additive
kategoriezeitbasierte Optionen mit `filterMode=none` und nutzt die bestehende
Erfassung/Wiederherstellung sowie die zentrale Wheel-Router-Anbindung.
Die vorhandenen TimeSeries-/BarHistory-/Scatter-Verträge bleiben erhalten.
ECharts-spezifische Optionen bleiben im Projekt; allgemeine Frame- und
Eventnormalisierung bleibt unverändert im Symcon_ModuleHelper.
Der Eigentümer bestätigt den Zeitzoom einschließlich Mausrad in der nativen
App und im tatsächlichen IPSView-Client. Echte Touchprüfung bleibt offen.

Treemap und Sunburst teilen gemäß [ADR 0090](adr/0090-normalized-hierarchy-weights.md)
optionale normalisierte Gewichte und Originalformatierung je Blatt.
`EChartsHierarchySources`, `TreemapModel`, `EChartsNormalization` und
`EChartsVariablePresentation` übernehmen Prüfung und Modellbildung; nur beide
Hierarchiefamilien laden `echarts-hierarchy-values.js` für Formatierung/Tooltips.
Ihre Geometrien und Designer bleiben lokal. Alte Feldabwesenheit erhält das
alte Rohwertmodell, alle Quellen-/Tiefen-/Knoten-/Bytebudgets gelten unverändert.

Der native Canvas-Graph-Layouttest verwendet reproduzierbare Force-Zufallswerte
und einen zusätzlichen Randfall mit 81 explizit positionierten festen Knoten.
Dieser reproduziert eine native affine Rundung auf `416.00000000000006` statt
exakt `416` Pixel. Nur der numerische Footer-Grenzvergleich erlaubt deshalb
`1e-9` Pixel Toleranz; bereits `1e-6` Pixel tatsächlicher Überlauf wird weiterhin
abgewiesen, ebenso nicht endliche Koordinaten. Renderer, Footerabstände,
Konfigurationen, Designs und Fremdkomponenten bleiben unverändert.

Parallel ergänzt gemäß [ADR 0086](adr/0086-parallel-axis-metrics.md) die additive
Quellenzeilenauswahl `Metric`: `auto` (fehlendes Feld ebenso), `average`,
`minimum`, `maximum`, `consumption`. Bestehende Auto-Modelle und Beschriftungen
bleiben unverändert. Nur explizite Modellachsen erhalten `metric`; die Renderer
kennzeichnen sie übersetzt an Achse und Tooltip. Ein reiner Quellenparser wird
für gespeicherte Quellen und Vorschauentwürfe wiederverwendet. Vorschauen bleiben
schematisch und archivfrei. Die bestehende gemeinsame Zeitraumplanung und
Gateway-Reducer werden genutzt; `consumption` wird ausschließlich zu `sum` auf
Zählerarchiven. Explizite Kennzahlen prüfen Typ, effektiven Reducer und Fenster
der Gateway-Antwort. Gemeinsame Zeitstempel, Lücken und sämtliche Budgets bleiben
erhalten; keine Änderung an Gateway, synchronisierten Helpern oder Runtime.
Neue Modul-/Gateway- und Renderertests ergänzen die bestehenden 16×625-,
Zeitraum- und Skalenregressionen. Die neue Kennzahlwahl ist per MCP funktional geprüft
und vom Eigentümer in Kachel und tatsächlichem IPSView bestätigt.

Parallel ergänzt gemäß [ADR 0085](adr/0085-parallel-fixed-axis-ranges.md) je
Quellenzeile `AxisRangeMode` (Default `auto`), `AxisMinimum` und `AxisMaximum`.
Nur `manual` wertet explizite endliche Grenzen aus. `EChartsValueRange` teilt die
bisherige strikte Werte-/Spannenprüfung mit Normalisierung ohne deren Verhalten
oder Meldungen zu ändern. Modellachsen erhalten nur im festen Modus additive
`minimum`-/`maximum`-Felder. Originalzeilen und native Achsenmarkierungen bleiben
unverändert, `outOfRangeCount` zählt Überschreitungen erst nach der gemeinsamen
Paarung. Native feste Skalen begrenzen lediglich den Sichtbereich; Tooltip und
Warnung nennen die unveränderten Originalwerte beziehungsweise Überschreitungen.
Beide SVG-Vorschauen prüfen alle Grenzen einschließlich ausgelassener Achsen und
verwenden ausschließlich Beispieldaten. Die festen Skalen sind funktional per
MCP mit 16 Achsen und 43 gemeinsamen Intervallen geprüft; alle 33 tatsächlichen
Überschreitungen wurden korrekt gezählt, Originalwerte und Konfigurationen
blieben unverändert. Temporäre Testobjekte wurden entfernt. Der Eigentümer
bestätigt anschließend Kachel und tatsächliches IPSView. Keine volle
10000-Aggregate-Live-Lastprüfung wegen der kurzen Archive.

Parallel unterstützt gemäß [ADR 0084](adr/0084-sixteen-axis-parallel-viewport.md)
2–16 Achsen. `EChartsArchiveQuery` erhält für jeden Zeitraum höchstens
`min(LineBudget × Achsenanzahl, 10000)` als gemeinsames Punktbudget; daraus folgt
bei 16 Achsen ein Limit von höchstens 625 je Achse. Alte Listen bis zehn behalten
ihre Ladelimits und bisherigen Range-Felder; neue effektive Budgetfelder sind
additiv für größere Listen. Zu große Antworten werden vor dem Join abgewiesen.
Alle Achsen und Zeilen bleiben in der nativen Parallel-Koordinate. Ein echter
horizontaler DOM-Viewport statt Dimensionsauswahl verwendet die bestehende
`SymconEChartsCategoryNavigation` für ID-stabile Anker. Titelupdates beim Scrollen
ersetzen nur die Titelposition; native Achsenmarkierungen und Skalen bleiben
erhalten. Reine SVG-Entwurfsvorschauen kennzeichnen ihren Sechs-Achsen-Ausschnitt.
Die 16-Achsen-Ansicht wurde funktional per MCP auf Symcon 9.1 mit temporärer
Instanz geprüft. Der Eigentümer bestätigt anschließend Kachel und tatsächliches
IPSView. 42 gemeinsame Stundenlinien, Originalwertvergleich, kleine Limits,
Tagesintervalle und 17-Achsen-Ablehnung sind live geprüft; kein voller
10000-Punkte-Live-Lasttest wegen kurzer Archive. Testobjekte wurden entfernt,
bestehende Konfigurationen/Designs und Archivierung blieben unverändert.

Heatmap ergänzt gemäß [ADR 0083](adr/0083-flexible-heatmap-periods.md) `custom`
und `absolute`. `HeatmapWindow` verwendet die zentrale strikte Datumsauswahl,
exakte Grenzprüfung und Vollstundenplanung aus `EChartsArchiveQuery`; alte
Kalenderzeiträume bleiben unverändert. Neue Fenster sind auf 366 × 24 Stunden
beschränkt; die bisherigen 60-Tage-Chunks mit je 2000 Aggregaten passen
weiterhin in höchstens sieben Archivabfragen. Alle vier Kennzahlen und
die Wochentag-/Stundenreduktion bleiben erhalten. Keine Gateway-Protokoll- oder
Helper-Änderung ist erforderlich. Ausgabe und reine Entwurfsvorschauen verwenden
dieselbe Modulplanung. Ein stabiler Ansichtsschlüssel schützt Navigation gegen
veraltete Antworten, ohne einen laufenden relativen Zeitraum einzufrieren.
Die lokale Canvas-Prüfung mit synthetischen Daten ergänzt automatisierte
Modul-/Gateway-, Budget- und Rendererprüfungen. Die funktionale MCP-Abnahme
erfolgte auf Symcon 9.1 mit temporärer Testinstanz; der Eigentümer bestätigt
anschließend Kachel und tatsächliches IPSView. Bestehende Konfigurationen/
Designs blieben unverändert, Testobjekte wurden entfernt. Kein Live-Lasttest.

Calendar bietet gemäß [ADR 0082](adr/0082-flexible-calendar-periods.md) Jahr,
Quartal, Monat und feste historische Datumsfenster. `Period=year` erhält alle
Altinstanzen; die neuen Month-/Quarter-/Date-Properties beeinflussen nur ihre
aktive Ansicht. `CalendarData::ResolveView` teilt Validierung, Ausgabe und
Entwurfsvorschau. `EChartsArchiveQuery::ParseLocalDate` ergänzt native SelectDate-
Felder und verwendet die vorhandene strikte Datumsprüfung; Datumsauswahl folgt
lokalen Kalendertagsgrenzen, ohne Uhrzeit-DST-Regeln anderer Familien zu ändern.
Die begrenzte `CalendarDays`-Enumeration wird auch von CalendarPie verwendet,
ohne dessen Datenmodell zu ändern. Maximal 366 Tage/Aggregate, alte Kennzahlen,
Teilarchive und unabhängige Designs bleiben erhalten. Navigation speichert
nichts und verschiebt freie Fenster um ihre zivilen Kalendertage. Ein
Ansichtsschlüssel verwirft veraltete Antworten bei Konfigurationswechseln.
Die neue Calendar-Live-Abnahme erfolgte per MCP; der Eigentümer bestätigt
anschließend Kachel und tatsächliches IPSView. Dies ist keine Live-Lastfreigabe.

StateTimeline integriert gemäß [ADR 0081](adr/0081-fixed-historical-state-timeline-periods.md)
feste historische Von-/Bis-Zeiträume. `ParseLocalDateTime` wird wiederverwendet;
`ResolveAbsoluteWindow` extrahiert die gemeinsame strikte Grenzprüfung aus
`ResolveAbsolute` und liefert ungerundete Zeitstempel. Aggregatfamilien behalten
ihre vollständigen Stunden-/Tagesintervalle und bisherigen Ergebnisformen.
StateTimeline behält `archive.states.read` einschließlich Ereignis am Endrand,
Vorgängersuche und halboffener Segmentbildung unverändert. Auch 1-Sekunden-
Fenster und nicht ganzstündige Offsetwechsel sind ohne Aggregationsannahmen
möglich. Alle Rohwert-, Modell- und Ausgabebudgets bleiben erhalten.

ThemeRiver integriert gemäß [ADR 0080](adr/0080-fixed-historical-theme-river-periods.md)
feste historische Von-/Bis-Zeiträume. `ParseLocalDateTime` und `AbsoluteIntervals`
werden ohne Änderungen wiederverwendet. Die gemeinsame vollständige Stunden-/
Tagesliste gilt für Standardmittel (`average`) und Zählerverbrauch (`sum`);
`ThemeRiverData`, Quellenprüfung, strenger Join und Ladebudgets bleiben unverändert.
Auch ausgeblendete Quellen bestimmen weiterhin Lücken und Ladeverbrauch.
Native Date-Felder und reine Entwurfsvorschauen folgen den vorhandenen Familien.
Bei oben liegender Vorschaulegende reserviert der absolute Modus Platz unter
seiner Datumskopfzeile; bestehende Vorschaugeometrie bleibt unverändert.

Die anschließende Teilarchivkorrektur liegt ausschließlich im gemeinsamen
`ThemeRiverData::Join`: Symcons erstes Aggregat kann bei Beginn der Aufzeichnung
mitten in einem geplanten Intervall dessen tatsächlichen Startzeitstempel tragen.
Nur ein solcher erster, innerhalb eines geplanten Intervalls liegender Punkt
wird nach vollständiger Wertprüfung verworfen; der Slot bleibt eine gemeinsame
Lücke. Es gibt keine Zeitstempelrundung, Nullfüllung oder Übernahme eines
anfänglichen Zählerstands als Intervallverbrauch. Spätere versetzte Punkte,
Reihenfolgefehler, Duplikate, ungültige Werte und Punkte außerhalb des Fensters
bleiben Fehler. Die bereits vor dem Join gezählten Ladebudgets bleiben erhalten.
`tests/theme_river_data.php` und die bestehende Consumption-Integration prüfen
synthetische Teilarchive für Mittelwert/Verbrauch, Stunde/Tag, feste/rollierende
Fenster, versteckte Quellen, echte Nullen und beide unabhängigen Ausgaben.
Die Regressionen reproduzierten vor der Korrektur den Intervallrasterfehler.
Properties, Gateway und Renderer bleiben unverändert. Die Korrektur wurde nach
Modulupdate per MCP in einer temporären Testinstanz nachgeprüft: erster Teilslot
bleibt Lücke, nachfolgende native Werte einschließlich Null bleiben erhalten,
auch mit versteckten Quellen und in den bisherigen rollierenden Fenstern.
Die ursprünglichen Instanzkonfigurationen und Archiveinstellungen blieben
unverändert; Testobjekte wurden entfernt. Der Eigentümer bestätigt anschließend
die tatsächliche Kachel und IPSView.

Candlestick integriert gemäß [ADR 0079](adr/0079-fixed-historical-candlestick-periods.md)
feste historische Von-/Bis-Zeiträume. Die zentralen `ParseLocalDateTime`- und
`ResolveAbsolute`-Verträge werden wiederverwendet; `AbsoluteIntervals` liefert
die begrenzte vollständige Stunden-/Tagesliste. Deren Enumeration wird mit den
bisherigen rollierenden `CompletedIntervals` geteilt, ohne deren Ergebnisform
oder Zeitregeln zu ändern. `CandlestickData::Read` und `Summarize` bleiben
unverändert: tatsächliche Rohwert-Endpunkte, kein Aggregatersatz und keine
Fortschreibung. Maximal 720 Intervalle, 63 Anfragen und 64000 Rohwerte bleiben
erhalten. Formular und zustandsneutrale Vorschauen folgen Boxplot/Parallel.

Parallel integriert gemäß [ADR 0078](adr/0078-fixed-historical-parallel-periods.md)
die zentrale `ParseLocalDateTime`-/`ResolveAbsolute`-Planung aus Boxplot für
Stunden und lokale Kalendertage. `Range=absolute`, `AbsoluteStart` und
`AbsoluteEnd` sind additive Properties; Altzeiträume und deren Modellform bleiben
unverändert. Ausschließlich identische Zeitstempel aller Achsen bilden Linien;
`auto`-Reducer, Kürzungshinweis und maximal 10×1000 Aggregate bleiben erhalten.
Kachel, IPSView und reine Entwurfsvorschau nutzen dieselben Zeitgrenzen.
Die fünf geplanten Familien Boxplot, Parallel, Candlestick, ThemeRiver und
StateTimeline sind damit technisch um feste historische Zeiträume erweitert.
Die gemeinsame funktionale Live-Abnahme aller fünf Familien wurde per MCP
durchgeführt und nach ThemeRivers Teilarchivkorrektur vervollständigt.
Vollständige Grenz-, Budget- und Fehlermatrizen sind weiterhin automatisiert
abgesichert; daraus folgt keine Live-Lastfreigabe. Lokale Modul- und Gesamttests
laufen weiterhin je Änderung.

Boxplot bietet gemäß [ADR 0077](adr/0077-fixed-historical-boxplot-periods.md)
zusätzlich `Range=absolute` und die additiven String-Properties `AbsoluteStart`
und `AbsoluteEnd` für native SelectDateTime-Objekte. Die gemeinsame reine
Zeitplanung ergänzt `ParseLocalDateTime` und `ResolveAbsolute`; bestehende
Methoden und Consumer bleiben unverändert. Lokale Daten werden streng geprüft,
DST-Lücken und doppeldeutige Grenzen ausdrücklich abgewiesen, vollständige
Stunden aufgerundet/abgerundet und zukünftige Enden nicht still gekappt.
32 Quellen, 1000 Stundenmittel je Quelle und 10000 insgesamt bleiben erhalten.
Vorschauen validieren Entwurfsdaten ohne Archivzugriff oder Zustandsänderung.
Die zentrale Planung ist für weitere Familien vorbereitet; deren Formulare
und Datenmodelle sind in diesem Schritt nicht erweitert.

Radar erweitert gemäß [ADR 0076](adr/0076-radar-group-selection.md) auf
16 vollständige Gruppen mit weiterhin 3–10 expliziten Achsen (höchstens 160
Zuordnungen). Die globalen additiven Properties `EnableGroupSelection=false`
und `GroupVisibility=[]` verändern keine gespeicherten Quellen oder Designs.
Die bestehende Namensidentität der fachlichen Gruppe bleibt erhalten;
Quellvariablen bleiben ID-basiert. `echarts-series-selection.js` verwaltet
Auswahl und Legendenanker. Alle Gruppen bleiben im Modell, in aktuellen
Gateway-Abfragen, Referenzen und Abonnements. Vorschauen validieren zuerst
alle Quellen und zeichnen danach höchstens acht anfangs sichtbare Gruppen
mit ausdrücklich genannten Gesamtzahlen und ursprünglichen Farben.
Modell und finales HTML sind auf 200000 Bytes beziehungsweise 1 MiB begrenzt.

Funnel erweitert gemäß [ADR 0075](adr/0075-funnel-source-selection-and-pages.md)
auf 1–32 aktuelle Quellen mit optionaler ID-stabiler Quellenauswahl und
responsiven Stufenseiten. Alle Quellen bleiben in Modell, Validierung,
Gateway-Zugriffen und Abonnements; der vollständige Maximalwert bestimmt die
Breitenskala vor Filterung oder Navigation. `EnableSourceSelection=false`
und fehlendes `InitiallyVisible=true` erhalten Altlisten; bis 16 Quellen ohne
Auswahl bleibt die alte Ansicht unverändert. Quellenauswahl, Kategorieanker und
DOM/CSS-Bedienung werden mit vorhandenen Familien geteilt. Der gemeinsame
Quellenresolver validiert Sichtbarkeitsmetadaten ausdrücklich opt-in, ohne
Grenzen oder persistierte Quellen anderer Verbraucher zu ändern.
SVG-Vorschauen filtern/sortieren vor ihrem höchstens achtteiligen Ausschnitt
und verwenden weiterhin den vollständigen Maximalwert. Modell und finales HTML
sind getrennt auf 200000 Bytes und 1 MiB begrenzt; keine Runtime-Änderung.

GaugeMulti erweitert gemäß [ADR 0074](adr/0074-gauge-multi-source-selection-and-pages.md)
auf 2–32 Quellen mit optionaler Auswahl und responsiven Seiten für alle vier
Vorlagen. Das Modell enthält weiterhin alle Quellen und individuelle Skalen;
`EnableSourceSelection=false` und fehlendes `InitiallyVisible=true` erhalten
Altlisten. Bis 16 Quellen ohne Auswahl bleibt die alte Geometrie erhalten.
Die DOM-Adapter und CSS von Auswahl/Seiten sind mit BarPolar zentralisiert;
beide verwenden unverändert die vorhandenen ID-Auswahl-/Kategorieanker.
Familiengeometrie und Gauge-Skalen bleiben lokal. Die Vorschau filtert vor
ihrem Vier-Quellen-Ausschnitt und behält vollständige Quellenzahlen/Farben.
900000 Modellbytes lassen Platz für die vorhandenen validierten SVG-Importe;
der abschließende HTML-Guard begrenzt beide Ausgaben weiterhin auf 1 MiB.

BarPolar erweitert gemäß [ADR 0073](adr/0073-polar-source-selection-and-pages.md)
auf 100 Quellen mit optionaler ID-Auswahl und responsiven Seiten für beide
Polarmodi. `EnableSourceSelection=false` und fehlendes `InitiallyVisible=true`
erhalten Altlisten; bis 16 Quellen ohne Auswahl bleibt die alte Geometrie.
Die gemeinsame Reihenwahl unterstützt jetzt auch direkte Checkbox-Aktionen,
der Kategorieanker direkte Seitenfenster. Nur diese Zustandslogik wird geteilt,
nicht Familiengeometrie oder Werteskala. Alle Quellen bleiben im Modell,
Lesezugriff und VM-Abonnement. Die vollständige nullinklusive automatische Skala
bleibt bei Auswahl/Seiten fest, manuelle Bereiche und Normalisierung unverändert.
Der maximal sechsteilige SVG-Ausschnitt berechnet seine Skala vor Auswahl und
Sampling. Modell- und HTML-Guards bleiben bei 200000 Bytes beziehungsweise 1 MiB.

Pie erweitert gemäß [ADR 0072](adr/0072-pie-source-selection.md) auf 100 Quellen.
`EnableSourceSelection` ist standardmäßig aus; `InitiallyVisible` fehlt in
Altlisten und bedeutet sichtbar. Die erweiterte Auswahl nutzt denselben
ID-/Legendenanker-Baustein wie TimeSeries, BarHistory und ThemeRiver. Fachliche
Gewichte, Originale, Abonnements und Validierung bleiben vollständig.
Auswahl- und Gesamtanteil haben getrennte overflow-sichere Nenner. Der
Vorschauausschnitt mit höchstens sechs Quellen wird erst nach dieser Berechnung
gebildet; Winkel und Nightingale-Maßstab ändern dadurch ihre Bezugsmenge nicht.
Modell- und finale HTML-Guards liegen bei 200000 Bytes beziehungsweise 1 MiB.

BarWaterfall erweitert gemäß [ADR 0071](adr/0071-waterfall-category-navigation.md)
auf 2–100 Quellen und 101 modellierte Schritte einschließlich berechneter
Endsumme. `WaterfallData::Totals` zentralisiert die endliche geordnete Addition
für Modell und vollständige Vorschau. Ausschnitte verändern keine Zwischenstände
oder Gesamtbilanz. `echarts-category-navigation.js` teilt ausschließlich flüchtige
ID-Anker und Fensterbegrenzung mit BarCategory; Familiengeometrie und Bilanz bleiben
lokal. BarPolar verwendet denselben Kategorieanker für direkte Seitenfenster;
die Einbettung erfolgt bedarfsbezogen über `EChartsAsset`.
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
Die nachfolgende Live-Abnahme in Kachel und IPSView ist durch den Eigentümer bestätigt.

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
beide Designer und echte ECharts-Geometrie. Die nachfolgende Abnahme in installierter Symcon-Kachel
und tatsächlichem IPSView ist durch den Eigentümer bestätigt.

Die Library besitzt mit Gateway und eigenständigen Diagrammmodulen eine technische
Modulstruktur. Alle Module
verwenden `IPSModuleStrict`;
Gauge-Instanzen können vorhandene Gateways wiederverwenden. Ein versioniertes
Protokoll liefert geprüfte numerische Momentanwerte an ein minimales
Gauge-Datenmodell. Gauge Multi besitzt eine geordnete und validierte Liste aus
2 bis 32 Quellen. Gauge Single rendert sein Modell mit der lokal gebündelten
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
Vorlage responsive native Kacheln für 2 bis 32 Quellen mit optionaler Auswahl
und Seiten. Seine gemeinsame
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
| Gerät | EChartsGaugeMulti | Zwei bis 32 individuell skalierte Quellen mit optionaler Quellenauswahl und responsiven Instrumentenseiten |
| Gerät | EChartsGaugeTacho | Tacho-Cockpit mit 2 bis 5 individuell gestaltbaren Instrumenten |
| Gerät | EChartsGaugeChronograph | Chronograph mit 2 bis 5 individuell gestaltbaren Instrumenten |
| Gerät | EChartsTimeSeries | Historische und live fortgeschriebene Linien und Flächen für 1 bis 32 Quellen, Reihenauswahl, bis zu acht links/rechts angeordnete Einheitengruppen sowie getrenntes Kachel- und IPSView-Design |
| Gerät | EChartsBarCategory | Aktuelle einfache, gruppierte oder gestapelte Kategorienvergleiche für 1 bis 100 Quellen mit gemeinsamer Einheit, Kategoriennavigation, optionalem Min-/Max-Farbverlauf und getrenntem Kachel- und IPSView-Design |
| Gerät | EChartsBarHistory | Historische rohe oder aggregierte Balken von 1 bis 32 Archivquellen, bis 16 Einheitengruppen und Reihenauswahl in nativer Kachel und optionalem IPSView-HTML |
| Gerät | EChartsBarWaterfall | Aktueller Startwert mit 1 bis 99 signierten Änderungen, berechneter Gesamtbilanz und ID-stabiler Kategoriennavigation in Kachel und optionalem IPSView-HTML |
| Gerät | EChartsBarPolar | Aktuelle Werte aus 1 bis 100 Quellen als radiale Balken oder konzentrische Bögen mit optionaler Quellenauswahl, Seiten und vollständiger Werteskala in Kachel und IPSView |
| Gerät | EChartsPie | Aktuelle Anteile aus 1 bis 100 Quellen als Kreis, Donut oder Nightingale-Rose mit optionaler ID-stabiler Quellenauswahl und getrennten Auswahl-/Gesamtanteilen |
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
| Gerät | EChartsRadar | 1–16 aktuelle vollständige Vergleichsgruppen auf 3–10 ausdrücklich begrenzten Achsen mit eigenen Einheiten und optionaler Gruppenauswahl |
| Gerät | EChartsBoxplot | Verteilungen abgeschlossener Stundenmittelwerte aus 1–32 Standard-Archivvariablen derselben Einheit mit Kategoriennavigation |
| Gerät | EChartsFunnel | 1–32 manuell benannte Stufen mit aktuellen nichtnegativen Werten gleicher Einheit, optionaler Quellenauswahl und Stufennavigation |

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
Optionale feste Größenbezüge für Bubble folgen
[ADR 0088](adr/0088-fixed-bubble-size-references.md). Die bestehenden
Min/Max-Prüfungen und die Vorschauprojektion werden wiederverwendet; die
Durchmesserberechnung bleibt lokal in Scatter. Quellen, Archivabfragen,
Originalwerte und unabhängige Designs bleiben unverändert.
Bis zu 16 benannte Vergleichsgruppen aus X/Y und optional Größe folgen
[ADR 0089](adr/0089-scatter-comparison-groups.md). Alle Zuordnungen teilen die
bisherige zentrale Zeitraumplanung und das 200–2000er-Budget. Der bestehende
Intervall-Join wird je Gruppe wiederverwendet, die flüchtige Legendenansicht
verwendet `echarts-series-selection.js`. Alte Quellenlisten werden nicht migriert.
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
Gemäß [ADR 0091](adr/0091-boxplot-iqr-outliers.md) erlaubt die gemeinsame
Datenproperty `WhiskerMode` zusätzlich `iqr`. Live- und Vorschauwerte verwenden
denselben `BoxplotStatistics`-Baustein; Whisker enden an beobachteten Inliern,
zeitgestempelte Ausreißer werden verlustfrei als Scatter-Punkte ausgegeben.
Die Min/Max-Standardeinstellung und alle Archiv-/Ausgabegrenzen bleiben erhalten.

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

Die ausdrücklich mit `<!-- symconecharts:prepared-beta -->` markierte
Beta-Überschrift im Changelog folgt über `--prepared-beta-changelog` derselben
berechneten Version. Der Bot committet beide Dateien gemeinsam; Datum und
Inhalte bleiben erhalten. Ohne Markierung bleiben historische Überschriften
unverändert. Nach erfolgter Veröffentlichung die Markierung im folgenden
Entwicklungsstand entfernen. Die Store-Unterlagen wiederholen keine feste
Versionsnummer im kopierbaren Freitext; der genaue Store-Commit bestimmt sie.
Aktuelle CI-Nachweise und der Freigabeablauf stehen unter [Beta-Store](BETA_STORE.md).

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

## Zentrale Mausradbehandlung am 22.10.2026

Die Ausgabe verwendet die kanonische Interaktionsressource aus `VisualizationAssetHelper`, die `IPSViewHTMLPageHelper` automatisch vor dem Modulskript einbindet. Generische Scroll-/Delta-/Pointer-/Frame-Mechanik wird nicht mehr modulweise neu implementiert. Die verbindliche Prüfregel einschließlich getrenntem Webclient-/App-Nachweis steht in `SymconDevelopment/standards/TESTING.md`.

Einordnung des Praxisbefunds: Chrome- und Edge-Webclients konnten die WoIst-Karte zoomen und die Geräteliste scrollen. Die native Windows-IPSView-App 6.5.19 ignorierte dieselben echten Mausradaktionen im damaligen Stand. Die gemeinsame Korrektur ist eingebunden; die anschließende native WoIst-Mausradprüfung wurde bereits vom Eigentümer durchgeführt und bestätigt. Sie ist keine verbleibende Testlücke. Synthetische Regressionen sichern umadressierte Ziele mit `(0, 0)`, korrigierte Zeigerposition, Delta-Modi, Ctrl-/Meta-Ausnahmen und skalierte direkte Frame-Weitergabe ab. Browserprüfungen mit überlaufenden synthetischen Geräten bestätigten normalen Kartenzoom sowie Scrollen der inneren Liste über den äußeren Loader, ohne gleichzeitigen Kartenzoom. Die Eigentümerbestätigung der nativen App bleibt von diesen synthetischen Browsernachweisen getrennt.

Die vorhandenen kartesischen Zoomaktionen registrieren sich am zentralen Router. Bestehende Grafikhandler können korrigierte Eingaben empfangen, ohne rekursive Weitergabe. Die Ressource wird minifiziert ausgeliefert; der große BarCategory-/SVG-Ausgabebudgettest bleibt unter Symcons 1-MB-Grenze.
