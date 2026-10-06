# SymconECharts

Folgende Module beinhaltet das SymconECharts Repository:

- __EChartsGateway__ ([Dokumentation](EChartsGateway))  
  Gemeinsame Zentrale als Splitter. Sie stellt den Chartfamilien aktuell den
  validierten Zugriff auf numerische Momentanwerte sowie normalisierte rohe
  und aggregierte Archivwerte bereit. Identische Archivabfragen werden kurz
  und begrenzt zwischengespeichert. Weitere gemeinsame Dienste folgen bei
  belegtem Bedarf.
  Das Gateway benötigt keine eigene I/O-Instanz.

- __EChartsGaugeSingle__ ([Dokumentation](EChartsGaugeSingle))
  Gerätemodul für ein einzelnes, unabhängig konfigurierbares Gauge-Diagramm
  mit genau einer numerischen Quellvariable. Vier responsive Layout-Presets
  und sieben unabhängig wählbare Theme-Modi werden als native Symcon-Kachel
  ausgegeben und bei Wertänderungen aktualisiert; Zeigerform einschließlich
  eines abgesicherten pfadbasierten SVG-Imports, Skalenbogen und
  optionale Gauge-Farben lassen sich im Kacheldesigner mit live aktualisierter
  SVG-Vorschau festlegen. Die am Zeigerdrehpunkt gebundene Nabe kann als Kreis,
  Ring oder validiertes eigenes SVG gestaltet beziehungsweise ausgeblendet
  werden. Eine optionale runde oder dem Skalenbogen folgende Zifferblattplatte
  besitzt eine eigene Füllung einschließlich linearer und radialer
  Farbverläufe, ein sicher importiertes und zugeschnittenes SVG-Motiv,
  Kontur, Größe und Schatten. Farbige Wertebereiche, frei abstimmbare Skalen,
  responsive Positionierung, elementweise Sichtbarkeit und ein gestaltbarer
  Wertekasten vervollständigen den Designer. Die Titelposition ist oben oder
  unten wählbar; optional übernimmt Gauge Single Wertebereich, Einheit und
  Nachkommastellen aus der nativen Variablendarstellung beziehungsweise einem
  kompatiblen Legacy-Profil.
  Ein optionales, separat platzierbares WebContent-Widget in IPSView verwendet
  dieselbe Datenkonfiguration und denselben Renderer. Sein Design erbt
  standardmäßig das Kacheldesign oder kann vollständig unabhängig gestaltet
  werden. Laufzeitwerte aktualisieren das bestehende Diagramm über den
  gemeinsamen Gateway-Transport, ohne das WebContent-Dokument neu zu laden.

- __EChartsGaugeMulti__ ([Dokumentation](EChartsGaugeMulti))
  Gerätemodul für zusammengesetzte Gauge-Darstellungen mit 2 bis 16
  Datenquellen. Jede Quelle besitzt eine
  eigene Beschriftung, Skala, Einheit und Formatierung. Multi Title,
  Ringraster, konzentrische Ringe und ein Wetterstations-Instrumentenpanel
  sind als responsive Vorlagen mit gemeinsamem Design implementiert.
  Wertebereich, Einheit und
  Nachkommastellen können je Quelle optional aus deren Variablendarstellung
  übernommen werden.

- __EChartsGaugeTacho__ ([Dokumentation](EChartsGaugeTacho))
  Responsives Tacho-Cockpit für 2 bis 5 Quellen. Jedes Instrument kann Zeiger,
  Nabe, Skala, Farben und Zifferblatt einschließlich eigener SVG-Assets
  unabhängig vom gemeinsamen Grunddesign überschreiben.

- __EChartsGaugeChronograph__ ([Dokumentation](EChartsGaugeChronograph))
  Chronograph mit einem Hauptinstrument und bis zu vier eingebetteten
  Nebeninstrumenten. Für jede der 2 bis 5 Quellen steht dieselbe individuelle
  Design- und SVG-Konfiguration wie beim Tacho zur Verfügung.

- __EChartsTimeSeries__ ([Dokumentation](EChartsTimeSeries))
  Erste historische Chartfamilie für 1 bis 8 archivierte numerische Quellen.
  Rohwerte bleiben ausdrücklich wählbar; alternativ stehen automatische oder
  feste Symcon-Aggregationsstufen, Linien und Flächen, bis zu acht automatisch
  oder explizit links/rechts angeordnete Einheitengruppen, Punktbudget,
  Tooltip und einen eigenen Kacheldesigner für Legende, Zoom,
  Linien, Flächen, Raster und Achsen in einer nativen Kachel bereit. Ein
  eigener Echtzeitmodus zeichnet auch nicht archivierte Variablen ab dem
  Öffnen der Kachel fort. Optional kann jede Reihe Linie, Datenpunkte und
  Flächenfüllung einschließlich Verlauf oder sicher importiertem SVG-Muster
  individuell gestalten.

Für eine reguläre Installation genügt eine gemeinsame EChartsGateway-Instanz
für alle Gauge- und späteren Diagramminstanzen. Beim ersten Diagramm kann ein
neues Gateway angelegt werden; bei jedem weiteren Diagramm wird das bereits
vorhandene Gateway ausgewählt. Die von Symcon weiterhin angebotene Neuanlage
eines Parents ist für den Normalbetrieb nicht erforderlich.

## Entwicklungsstand

**Erste sichtbare Gauge-Single-Vertikale.** Gateway und die Gauge-Module
verwenden `IPSModuleStrict`, ein versioniertes Datenprotokoll und eine
wiederverwendbare Parent-Verbindung. Gauge Single rendert sein validiertes
Momentanwertmodell mit dem lokal gebündelten Apache ECharts 6.1.0 als native,
responsive Symcon-Kachel. Der erste Kacheldesigner wählt zwischen Basic,
Simple, Progress und Speed sowie `auto` und sechs lokal gebündelten offiziellen
Apache-ECharts-Themes. Dazu kommen validierte Zeigerformen einschließlich
eines pfadbasierten SVG-Imports, Skalenbögen mit
22,5-Grad-Positionen, ein konfigurierbares Zifferblatt einschließlich
abgesichertem SVG-Hintergrund und optionale Farbrollen.
Simple, `auto` und die jeweiligen
Presetvorgaben erhalten die bisherige
Standarddarstellung.

Gauge Multi verwaltet eine geordnete Quellenliste, liefert ein versioniertes
Multi-Modell und rendert die Quellen als Multi-Title-Zifferblätter, Ringraster,
konzentrische Ringe oder als Wetterstations-Instrumentenpanel. Eine gemeinsame Designschicht
konfiguriert Zeiger einschließlich validiertem SVG-Import, Naben,
Skalenunterteilungen, Farbrollen und Zifferblattplatten einschließlich
bereinigtem SVG-Hintergrund
für alle Instrumente einer Ausgabe. Tacho und Chronograph sind eigenständige
Module für jeweils 2 bis 5 Quellen und ergänzen diese Basis um quellbezogene
Designüberschreibungen einschließlich SVG-Zeiger, SVG-Nabe und SVG-Zifferblatt.
Der Gateway-Vertrag für Archivdaten und die darauf aufbauende native
Zeitreihen-Kachel sowie ihre getrennt gestaltbare IPSView-WebContent-Ausgabe
sind implementiert. Wertachsen können je Einheitengruppe automatisch, anhand
der Variablendarstellung oder mit manuellem Minimum und Maximum skaliert
werden. Weiterführende Zeitreihen-Designoptionen folgen auf dieser Basis.
Alle Gauge-Module besitzen optionale IPSView-WebContent-Ausgaben und können
ihr Kacheldesign erben oder unabhängig gestaltet werden. Wie TimeSeries können
sie den IPSView-Hintergrund transparent durchscheinen lassen und ihn mit einer
Theme- oder Benutzerfarbe in einstellbarer Deckkraft tönen. Der reale
IPSView-Laufzeittest bleibt mangels Lizenz eine dokumentierte Testlücke.
Gauge- und Time-Series-Ausgaben behalten ihr geöffnetes IPSView-Dokument bei
Wertänderungen bei und aktualisieren nur den ECharts-Zustand.

Als erste Chartfamilie sind Single- und Multi-Gauges vorgesehen. Eine
Geräteinstanz soll jeweils einen Chart liefern; eine gemeinsame Dashboard-Seite
ist nicht vorgesehen. Weitere Chartfamilien erhalten eigene Gerätemodule.

## Entwicklung

Die Entwicklung erfolgt auf `dev`. Geprüfte Stände gelangen später per Pull
Request nach `main`; `dev` bleibt erhalten. Projektziele, Architektur und
Entwicklungsgrundsätze stehen in [Entwicklung](docs/ENTWICKLUNG.md).

Nach normalen Pushes auf `dev` pflegt ein Workflow Version, Build und Datum in
`library.json` und legt die Metadaten als getrennten Bot-Commit ab. Tags und
Releases werden dadurch nicht automatisch erzeugt.

Die Basistests werden lokal mit `php tests/run.php` ausgeführt. Der
Tests-Workflow prüft dieselbe Suite unter PHP 8.5 und ergänzt PHP-Syntax- sowie
JSON-Validierung über die gemeinsame `Symcon_ModuleCI`-Basis. Ein eigener
Style-Workflow führt zusätzlich die offiziellen Symcon-Prüfungen für PHP und
JSON aus.

## Lizenz

Die eigenen Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](LICENSE).
SPDX-Identifier: `PolyForm-Noncommercial-1.0.0`.

Required Notice: Copyright 2026 Burkhard Kneiseler. SymconECharts.

Fremdkomponenten behalten ihre Originallizenzen. Version, Herkunft, Integrität
und Lizenz der eingebundenen Apache-ECharts-Runtime stehen in
[THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

SymconECharts ist kein offizielles Projekt der Apache Software Foundation.
