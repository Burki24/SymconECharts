# Projektregeln für SymconECharts

Vor Arbeiten in diesem Repository sind zuerst die zentralen Vorgaben aus
`../SymconDevelopment/AGENTS.md` und
`../SymconDevelopment/ARCHITECTURE.md` zu lesen. Die dort referenzierten
Prinzipien und die für die konkrete Aufgabe relevanten Standards gelten auch
für SymconECharts. Projektspezifische Ziele und der vorhandene Stand stehen in
`README.md`, `docs/ENTWICKLUNG.md` und den README-Dateien der Module.

## Produktumfang und Abgrenzung

- SymconECharts ist die eigenständige Neuentwicklung für Diagramme auf Basis
  von Apache ECharts. JSLive bleibt ein getrenntes Wartungsprojekt und wird aus
  diesem Repository heraus nicht geändert.
- Zielplattform sind IP-Symcon 9.0/9.1 und PHP 8.5. Eine gesonderte
  IP-Symcon-9.0-Testinstallation ist nicht erforderlich; nicht auf der
  erreichbaren Testebene geprüfte Varianten werden sichtbar als Testlücke
  benannt.
- Der aktuelle Repository-Stand besitzt eine getestete technische
  Gauge→Gateway-Datenbasis, die Archivoperation `archive.read` sowie native
  HTML-SDK-Ausgaben für Gauge Single, Gauge Multi, Gauge Tacho, Gauge
  Chronograph und die erste Time-Series-Vertikale. Nicht
  implementierte Funktionen, Store-Freigaben oder nicht real geprüfte
  Laufzeitkompatibilität dürfen nicht als vorhanden dargestellt werden.
- Native Symcon-Kacheln und einzelne IPSView-HTML-Widgets sind vorgesehene
  Ausgabewege. IPSView-Laufzeittests sind auf der vorhandenen Testebene mangels
  Lizenz nicht möglich und bleiben ausdrücklich als Lücke dokumentiert.

## Architekturgrenzen

- Die Modulstruktur besteht aus `EChartsGateway` und getrennten
  Geräteinstanzen je Diagrammfamilie. Die erste Familie ist in
  `EChartsGaugeSingle` für genau eine Quellvariable, `EChartsGaugeMulti` für
  allgemeine Mehrquellenmodelle sowie `EChartsGaugeTacho` und
  `EChartsGaugeChronograph` für individuell gestaltbare Instrumentenpanels
  aufgeteilt. Historische Linien- und Flächendiagramme liegen im eigenen
  Gerätemodul `EChartsTimeSeries`. Aktuelle Kategorienvergleiche liegen im
  eigenen Gerätemodul `EChartsBarCategory`; historische Balken liegen im
  eigenen Gerätemodul `EChartsBarHistory`. Waterfall-Balken mit aktuellem
  Startwert, signierten Änderungen und berechneter Endsumme liegen im eigenen
  Gerätemodul `EChartsBarWaterfall`. Polar-Balken für aktuelle Werte liegen im
  eigenen Gerätemodul `EChartsBarPolar` und sind kein Modus dieser Instanzen.
  Kreis-, Donut- und Nightingale-Diagramme für aktuelle Anteile liegen im
  eigenen Gerätemodul `EChartsPie` und sind kein Balkenmodus.
  Pie, BarCategory, BarPolar und PictorialBar erlauben gemäß ADR 0063 eine
  ausdrücklich aktivierte Min/Max-Normalisierung je Quelle auf 0–100.
  Originalwerte, Einheiten und Quellenidentitäten bleiben erhalten;
  unterschiedliche Einheiten sind nur im normalisierten Modus zulässig.
  Ohne Aktivierung gelten unverändert die Rohwertverträge der Familien.
  Historische X/Y-Vergleiche und optionale Bubble-Größen liegen im eigenen
  Gerätemodul `EChartsScatter` und werden ohne Interpolation nach gemeinsamen
  abgeschlossenen Archivintervallen verbunden.
  Eine archivbasierte Tages- beziehungsweise Wochentag-Stunden-Heatmap liegt im eigenen Gerätemodul
  `EChartsHeatmap`. Sie ist kein Kalenderdiagramm und kein Scatter-Modus.
  Eine begrenzte, lesende IP-Symcon-Objekthierarchie liegt im eigenen
  Gerätemodul `EChartsTree` und ist kein Graph-Modus. Die ausdrückliche
  Zustandsbewertung dieser Hierarchie liegt getrennt in
  `EChartsTreeSystemHealth`; der einfache Tree enthält keine Zustandsregeln.
  Frei festgelegte Knoten und Kanten zwischen ausgewählten Symcon-Objekten
  liegen in `EChartsGraph`. Die zuletzt exportierte Zigbee2MQTT-Netzwerktopologie
  liegt im getrennten, nur lesenden Gerätemodul `EChartsGraphZ2M`; es löst keinen
  Netzwerkscan aus und ist kein manueller Kantenmodus des allgemeinen Graphen.
  Eine Treemap aus manuell gewählten Gruppen und aktuellen numerischen
  Symcon-Variablenwerten liegt im eigenen Gerätemodul `EChartsTreemap`.
  Ein Sunburst mit derselben manuell benannten Hierarchie und aktuellen
  numerischen Variablenwerten liegt getrennt in `EChartsSunburst`.
  Beide verwenden gemäß ADR 0064 denselben manuellen Hierarchievertrag mit
  optionalen Gruppenpfaden, höchstens acht Ebenen einschließlich Blatt,
  100 Quellen und 800 Knoten. Alte Group-/Subgroup-Quellen bleiben unverändert
  verwendbar; gemeinsame Quellenprüfung und Modellbildung liegen unter `libs`.
  Archivierte Werte mehrerer Variablen werden als Parallelkoordinaten nach
  gemeinsamen abgeschlossenen Stunden- oder Tagesintervallen im eigenen
  Gerätemodul `EChartsParallel` dargestellt.
  Aktuelle Mengenflüsse zwischen manuell angelegten Knoten liegen im eigenen
  Gerätemodul `EChartsSankey`; Verbindungsbreiten folgen numerischen
  Quellvariablen, Zyklen und negative Werte sind unzulässig.
  Archivierte Tageskennzahlen einer einzelnen numerischen Variable liegen im
  eigenen Gerätemodul `EChartsCalendar`; Mittelwert, Minimum und Maximum
  erfordern Standardarchive, der ausdrücklich gewählte Zählerverbrauch ein
  Zählerarchiv gemäß ADR 0062. Laufende Tage bleiben ausgeschlossen.
  Das Jahreslayout ist kein Modus der Stunden-Heatmap. Auch deren stündliche
  Kennzahlen und Wochentag-/Stundenreduktion folgen ADR 0062.
  Aktuelle Vergleichsgruppen auf expliziten normierten Achsen liegen im eigenen
  Gerätemodul `EChartsRadar` gemäß ADR 0053; keine Archive oder automatische
  Qualitätsbewertung. Werte außerhalb des Bereichs werden nur optisch begrenzt.
  Verteilungen abgeschlossener Stundenmittelwerte von Archivvariablen liegen
  in `EChartsBoxplot` gemäß ADR 0054; Quartile werden nicht aus Tagesaggregaten
  approximiert und fehlende Intervalle nicht als Null ergänzt.
  Nichtnegative Stunden- oder Tagesmittelwerte gleicher Einheit liegen als
  Archivbänder im eigenständigen `EChartsThemeRiver` gemäß ADR 0058;
  fehlende Quellen unterbrechen ein Intervall ohne Nullauffüllung.
- Das Gateway stellt ausschließlich familienübergreifende Infrastruktur wie
  Archivzugriff, begrenztes Caching, gemeinsame Ressourcen und gegebenenfalls
  abgesicherte IPSView-Kommunikation bereit. Familienbezogene Properties,
  Validierung und ECharts-Optionen verbleiben im jeweiligen Gerätemodul.
- Im regulären Betrieb verwenden alle ECharts-Geräte eine gemeinsame
  Gateway-Instanz. Das erste Gerät kann sie neu anlegen, weitere Geräte wählen
  das vorhandene Gateway. Zusätzliche Gateways bleiben technisch zulässig,
  werden aber weder als Standard empfohlen noch durch eine harte
  Singleton-Logik verhindert.
- Weitere Diagrammfamilien erhalten bei belegtem Bedarf ein eigenes
  Gerätemodul. Sie werden nicht als Modi einer universellen Widget-Instanz
  ergänzt.
- Die bestehende Gauge-Modul-GUID gehört nach der Aufteilung zu
  `EChartsGaugeSingle`; `EChartsGaugeMulti` besitzt eine eigene Modul-GUID.
  `EChartsGaugeTacho` und `EChartsGaugeChronograph` besitzen ebenfalls eigene
  Modul-GUIDs; ihre Festlegung dokumentiert ADR 0016. `EChartsTimeSeries`
  besitzt die in ADR 0018 festgelegte eigene Identität.
  Datenfluss-IDs werden von beiden Geräten gemeinsam verwendet. Namen,
  Präfixe und Zuständigkeiten aus [`ADR 0001`](docs/adr/0001-chart-family-modules.md)
  und [`ADR 0003`](docs/adr/0003-single-and-multi-gauge-modules.md) sowie das
  Multi-Quellenmodell aus
  [`ADR 0004`](docs/adr/0004-gauge-multi-source-contract.md) sind verbindliche
  Verträge und ändern sich nur mit einer ausdrücklichen Architektur- und
  Kompatibilitätsentscheidung.
- Parent-Auswahl, Zuständigkeiten und das versionierte Datenprotokoll aus
  [`ADR 0002`](docs/adr/0002-gateway-gauge-contract.md) sind verbindliche
  technische Verträge und werden kompatibel weiterentwickelt.
- Symcon-Datenanbindung, Archivzugriff, fachliches Chart-Modell, Formatierung,
  Ausgabeadapter und ECharts-Renderer werden als getrennte Zuständigkeiten
  behandelt. Gemeinsame Abstraktionen entstehen nur für belegte gemeinsame
  Anwendungsfälle.
- Die gemeinsame Grundreihenfolge der Diagramm-Instanzformulare und eine
  unmittelbar auf Formularänderungen reagierende Vorschau für Kachel und
  IPSView folgen [`ADR 0036`](docs/adr/0036-consistent-chart-forms-and-live-previews.md).
  Das Gateway ohne Diagramm-Designer ist ausgenommen. Der Umsetzungsstand und
  die weiterhin erforderlichen Laufzeitprüfungen stehen in der ADR.
- Fachlich benannte, standardmäßig eingeklappte Unterbereiche in Kachel- und
  unabhängigem IPSView-Designer folgen
  [`ADR 0037`](docs/adr/0037-topic-sections-in-chart-designers.md). Rubriken
  und Feldzuordnung bleiben je Diagrammfamilie festgelegt.
- Repositoryweit geteilter projektspezifischer Code liegt direkt unter `libs`;
  `libs/helper` enthält ausschließlich die über `.helper-sync.json`
  abonnierten Kopien aus `Symcon_ModuleHelper`. Tests liegen unter `tests`.
  Vorhandene zentrale Funktionen werden wiederverwendet und synchronisierte
  Helper nicht lokal verändert.

## Modulpräfixe

- `ECCP` ist `EChartsCalendarPie` zugeordnet (`CP` = Calendar Pie), GUID
  `{8F3D4E67-A52E-46AB-976E-25EC4F37FB52}` gemäß ADR 0060. Das eigene
  Monatskalendermodul bietet Tagesmittelwerte, Zähler-Tagesverbrauch und
  Zustandsdauer als auswählbare Datengrundlagen, nicht als getrennte Module.

- `ECST` ist `EChartsStateTimeline` zugeordnet (`ST` = State Timeline), GUID
  `{581AD0EF-4E14-4796-902B-08381E299232}` gemäß ADR 0059. Archivierte
  Boolean-/Integer-Zustandswechsel werden als Zeitabschnitte dargestellt,
  ohne Aggregation oder Ersetzung durch aktuelle Werte.

- `ECRV` ist `EChartsThemeRiver` zugeordnet (`RV` = River), GUID
  `{6767D036-5E2B-4D33-B7F1-C9F1B4376C04}` gemäß ADR 0058.
  Die eigenständige Archivfamilie verwendet ausschließlich abgeschlossene
  Stunden-/Tagesmittelwerte nichtnegativer Standardvariablen gleicher Einheit.

- Technische Modulnamen und deren Anzeige folgen einheitlich `ECharts` plus
  Familienname ohne Leerzeichen, beispielsweise `EChartsPictorialBar`.
  `module.json` verwendet `aliases: []`, damit keine abweichenden
  Anzeige-Aliase entstehen. Individuelle Instanznamen bleiben Benutzerentscheidungen.

- `ECPB` ist `EChartsPictorialBar` zugeordnet (PB = Pictorial Bar), GUID `{CF6B1F8C-7219-4D59-94EA-19CF7570637F}` gemäß ADR 0057.

- `ECFU` ist `EChartsFunnel` zugeordnet (`FU` = Funnel), siehe ADR 0055.

- Modulpräfixe bestehen aus vier Großbuchstaben. `EC` bezeichnet stets
  ECharts; die dritte und vierte Stelle kennzeichnen Aufgabe oder
  Diagrammfamilie.
- `ECGW` ist dem `EChartsGateway` zugeordnet (`GW` = Gateway).
- `ECGS` ist `EChartsGaugeSingle` zugeordnet (`GS` = Gauge Single).
- `ECGM` ist `EChartsGaugeMulti` zugeordnet (`GM` = Gauge Multi).
- `ECGT` ist `EChartsGaugeTacho` zugeordnet (`GT` = Gauge Tacho).
- `ECGC` ist `EChartsGaugeChronograph` zugeordnet (`GC` = Gauge Chronograph).
- `ECTS` ist `EChartsTimeSeries` zugeordnet (`TS` = Time Series).
- `ECBC` ist `EChartsBarCategory` zugeordnet (`BC` = Bar Category).
- `ECBH` ist `EChartsBarHistory` zugeordnet (`BH` = Bar History).
- `ECBW` ist `EChartsBarWaterfall` zugeordnet (`BW` = Bar Waterfall).
- `ECBP` ist `EChartsBarPolar` zugeordnet (`BP` = Bar Polar).
- `ECPI` ist `EChartsPie` zugeordnet (`PI` = Pie).
- `ECSC` ist `EChartsScatter` zugeordnet (`SC` = Scatter).
- `ECHM` ist `EChartsHeatmap` zugeordnet (`HM` = Heatmap).
- `ECTR` ist `EChartsTree` zugeordnet (`TR` = Tree).
- `ECTH` ist `EChartsTreeSystemHealth` zugeordnet (`TH` = Tree System Health).
- `ECGR` ist `EChartsGraph` zugeordnet (`GR` = Graph).
- `ECGZ` ist `EChartsGraphZ2M` zugeordnet (`GZ` = Graph Zigbee2MQTT).
- `ECTM` ist `EChartsTreemap` zugeordnet (`TM` = Treemap).
- `ECSB` ist `EChartsSunburst` zugeordnet (`SB` = Sunburst).
- `ECPA` ist `EChartsParallel` zugeordnet (`PA` = Parallel).
- `ECSA` ist `EChartsSankey` zugeordnet (`SA` = Sankey).
- `ECCL` ist `EChartsCalendar` zugeordnet (`CL` = Calendar).
- `ECRA` ist `EChartsRadar` zugeordnet (`RA` = Radar).
- `ECBX` ist `EChartsBoxplot` zugeordnet (`BX` = Boxplot).
- Das zuvor für das unveröffentlichte Gauge-Gerüst verwendete Kürzel `ECGA`
  ist abgelöst und wird nicht für ein anderes Modul wiederverwendet.
- Neue Kürzel werden vor ihrer Verwendung eindeutig festgelegt und in dieser
  Datei sowie im zugehörigen Architekturkontext dokumentiert. Ein bestehendes
  Kürzel wird nicht für eine andere Bestimmung wiederverwendet.

- `ECCA` ist `EChartsCandlestick` zugeordnet (CA = Candlestick Archive); GUID `{A5C60024-BB95-4417-9632-763CBE9C14D7}` gemäß ADR 0056.

## ECharts und Fremdkomponenten

- Vor der Festlegung oder Aktualisierung einer ECharts-Abhängigkeit sind die
  aktuelle stabile Version, die benötigten APIs, Lizenz und Lieferartefakte
  anhand der offiziellen Einstiege
  `https://echarts.apache.org/en/index.html` und
  `https://echarts.apache.org/en/llms.txt` zu prüfen.
- ECharts wird lokal, reproduzierbar und mit festgeschriebener Version
  ausgeliefert. Im Betrieb wird weder `latest` noch eine externe CDN-Ressource
  automatisch geladen.
- Der ECharts-Kern wird nicht projektspezifisch verändert. Herkunft, Version,
  Integrität und Lizenz der ausgelieferten Fremdkomponenten werden
  dokumentiert.
- Offizielle ECharts-Themes liegen versioniert bei der ECharts-Runtime unter
  `libs/echarts`; ihre gemeinsamen IDs, Integritätswerte und
  layoutkompatiblen Grundfarben verwaltet `libs/EChartsAsset.php`. Die Auswahl bleibt
  eine Property der jeweiligen Diagramminstanz und ist keine
  Gateway-Konfiguration. Layout-Preset und Theme sind getrennte Verträge.
- Theme-Builder-Dateien sind ausführbarer JavaScript-Code. Ein späterer Import
  eigener Themes benötigt deshalb einen ausdrücklich festgelegten,
  validierten Importvertrag und wird nicht als ungeprüfter Dateiinhalt in eine
  Visualisierung übernommen.
- Die festgelegte Runtime- und Rendererbasis aus
  [`ADR 0005`](docs/adr/0005-echarts-runtime-and-native-renderer.md) ist ein
  technischer Vertrag. Aktualisierungen der ECharts-Version oder Änderungen am
  Auslieferungsmodell benötigen eine erneute Herkunfts-, Integritäts-, Lizenz-
  und Kompatibilitätsprüfung.

## JSLive-Export und Übernahme

- Maßgebliche Produktentscheidung ist
  `../JSLive/docs/adr/0006-maintenance-scope.md`. JSLive dient ausschließlich
  als lesende Referenz, sofern kein eigener Auftrag für dieses Repository
  erteilt wurde.
- JSLive liefert den Export bestehender Charts; SymconECharts erhält die
  passende Übernahmefunktion. Vor der Implementierung ist ein gemeinsamer,
  versionierter Austauschvertrag zu spezifizieren.
- Variablen- und Archivbezüge werden erhalten. Originalinstanzen,
  Variablenwerte und Archivdaten werden weder gelöscht noch überschrieben.
- Eine Übernahme benötigt Vorschau und ausdrückliche Bestätigung. Sie muss bei
  Wiederholung sicher bleiben, Teilfehler kontrolliert melden und das
  JSLive-Original als Vergleich und Rückfall erhalten.
- Nicht übertragbare Einstellungen, eigene Templates, Skripte sowie
  Chart.js-spezifische Erweiterungen werden sichtbar gemeldet. Skript- oder
  Templateinhalte werden nicht stillschweigend ausgeführt oder als verlustfrei
  übertragbar behandelt.
- Es wird keine pixelgleiche oder vollständige 1:1-Migration beliebiger
  Chart.js-Konfigurationen versprochen.

## Umsetzung, Prüfung und Git

- Vor Änderungen sind Arbeitsbaum, vorhandene Architektur, öffentliche
  Verträge, relevante Dokumentation, vergleichbare Implementierungen und die
  verfügbaren Tests zu prüfen. Fremde oder nicht zur Aufgabe gehörende
  Änderungen bleiben erhalten.
- Konfiguration, Import und Migration müssen deterministisch, idempotent und
  bei Teilfehlern wiederaufnehmbar sein. Veröffentlichte Properties, Idents,
  GUIDs und persistierte Formate ändern sich nur mit dokumentierter Migration.
- `ApplyChanges()` wird beim Modulupdate automatisch ausgeführt und nicht ohne
  konkreten Prüfgrund zusätzlich aufgerufen.
- Keine schreibenden Aktionen in einer Symcon-Installation ohne passenden
  Auftrag. Commit, Push, Merge, Tag, Release und Veröffentlichung führt der
  Eigentümer aus.
- Entwicklung erfolgt auf dem dauerhaften Branch `dev`; freigegebene Stände
  gelangen per Pull Request nach `main`.
- Die Library verwendet eine gemeinsame Version im Format
  `Hauptversion.Nebenstand`. Nach normalen Pushes auf `dev` pflegt der
  Metadatenworkflow `version`, `build` und `date` in `library.json` und erzeugt
  dafür einen getrennten Bot-Commit. Manuelle Änderungen dieser Felder erfolgen
  nur als ausdrücklich beauftragter Versions- oder Migrationsschritt.
- Der Metadatenworkflow erzeugt keine Tags, Releases oder Veröffentlichungen.
  Solche Schritte bleiben eine gesonderte Entscheidung des Eigentümers.
- Prüfungen folgen den dokumentierten Repositorybefehlen und der gemeinsamen
  CI-Basis. Abschlussberichte nennen ausgeführte Prüfungen und verbleibende
  Testlücken getrennt; Syntax-, Style- und Strukturprüfungen ersetzen keine
  Laufzeit- oder Browserprüfung.
- Die Haupt-README und alle Modul-READMEs bleiben Benutzerdokumentation.
  Entwicklung, Tests, CI und interne Architektur werden ausschließlich in
  `docs`, ADRs oder Testdokumenten beschrieben.
- Jede für Benutzer sichtbare Änderung wird im selben fachlichen Zusammenhang
  in `CHANGELOG.md` unter `# Unveröffentlicht` dokumentiert. Reine interne oder
  mechanische Änderungen erhalten keinen Eintrag.
- Vor der Übergabe commitbereiter Änderungen ist
  `php tests/quality.php --fix` auszuführen. Der Befehl korrigiert ausschließlich
  mechanische PHP- und JSON-Formatierung und prüft danach jeden Style-, Syntax-,
  Vertrags-, Integrations- und Layoutschritt mit eigenem Fehlerstatus erneut.
- Bei commitbereiten Änderungen werden Commit-Betreff und Commit-Erläuterung
  immer auf Englisch und in zwei getrennten kopierbaren Textblöcken geliefert.
