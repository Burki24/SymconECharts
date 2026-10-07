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
  Gerätemodul `EChartsTimeSeries`.
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
- Repositoryweit geteilter projektspezifischer Code liegt direkt unter `libs`;
  `libs/helper` enthält ausschließlich die über `.helper-sync.json`
  abonnierten Kopien aus `Symcon_ModuleHelper`. Tests liegen unter `tests`.
  Vorhandene zentrale Funktionen werden wiederverwendet und synchronisierte
  Helper nicht lokal verändert.

## Modulpräfixe

- Modulpräfixe bestehen aus vier Großbuchstaben. `EC` bezeichnet stets
  ECharts; die dritte und vierte Stelle kennzeichnen Aufgabe oder
  Diagrammfamilie.
- `ECGW` ist dem `EChartsGateway` zugeordnet (`GW` = Gateway).
- `ECGS` ist `EChartsGaugeSingle` zugeordnet (`GS` = Gauge Single).
- `ECGM` ist `EChartsGaugeMulti` zugeordnet (`GM` = Gauge Multi).
- `ECGT` ist `EChartsGaugeTacho` zugeordnet (`GT` = Gauge Tacho).
- `ECGC` ist `EChartsGaugeChronograph` zugeordnet (`GC` = Gauge Chronograph).
- `ECTS` ist `EChartsTimeSeries` zugeordnet (`TS` = Time Series).
- Das zuvor für das unveröffentlichte Gauge-Gerüst verwendete Kürzel `ECGA`
  ist abgelöst und wird nicht für ein anderes Modul wiederverwendet.
- Neue Kürzel werden vor ihrer Verwendung eindeutig festgelegt und in dieser
  Datei sowie im zugehörigen Architekturkontext dokumentiert. Ein bestehendes
  Kürzel wird nicht für eine andere Bestimmung wiederverwendet.

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
