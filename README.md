# SymconECharts

SymconECharts stellt numerische Symcon-Werte als responsive Apache-ECharts-
Diagramme in nativen Symcon-Kacheln und optionalen IPSView-HTML-Widgets dar.
Die Library enthält Gauges für einzelne und mehrere Werte, historische und
live fortgeschriebene Zeitreihen, aktuelle Kategorienvergleiche, historische
Balken sowie ein Waterfall-Diagramm für aktuelle Werte.

## Voraussetzungen

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5;
- numerische Integer- oder Float-Variablen als Datenquellen;
- ein Symcon-Archiv für historische Zeitreihen; der Echtzeitmodus funktioniert
  auch ohne aktiviertes Archiv;
- IPSView nur bei Verwendung der optionalen IPSView-Ausgabe.

## Enthaltene Module

Folgende Module sind enthalten:

- __EChartsGateway__ ([Dokumentation](EChartsGateway/README.md))
  Gemeinsame Zentrale als Splitter. Sie stellt den Chartfamilien aktuell den
  validierten Zugriff auf numerische Momentanwerte sowie normalisierte rohe
  und aggregierte Archivwerte bereit. Identische Archivabfragen werden kurz
  und begrenzt zwischengespeichert. Weitere gemeinsame Dienste folgen bei
  belegtem Bedarf.
  Das Gateway benötigt keine eigene I/O-Instanz.

- __EChartsGaugeSingle__ ([Dokumentation](EChartsGaugeSingle/README.md))
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

- __EChartsGaugeMulti__ ([Dokumentation](EChartsGaugeMulti/README.md))
  Gerätemodul für zusammengesetzte Gauge-Darstellungen mit 2 bis 16
  Datenquellen. Jede Quelle besitzt eine
  eigene Beschriftung, Skala, Einheit und Formatierung. Multi Title,
  Ringraster, konzentrische Ringe und ein Wetterstations-Instrumentenpanel
  sind als responsive Vorlagen mit gemeinsamem Design implementiert.
  Wertebereich, Einheit und
  Nachkommastellen können je Quelle optional aus deren Variablendarstellung
  übernommen werden.

- __EChartsGaugeTacho__ ([Dokumentation](EChartsGaugeTacho/README.md))
  Responsives Tacho-Cockpit für 2 bis 5 Quellen. Jedes Instrument kann Zeiger,
  Nabe, Skala, Farben und Zifferblatt einschließlich eigener SVG-Assets
  unabhängig vom gemeinsamen Grunddesign überschreiben.

- __EChartsGaugeChronograph__ ([Dokumentation](EChartsGaugeChronograph/README.md))
  Chronograph mit einem Hauptinstrument und bis zu vier eingebetteten
  Nebeninstrumenten. Für jede der 2 bis 5 Quellen steht dieselbe individuelle
  Design- und SVG-Konfiguration wie beim Tacho zur Verfügung.

- __EChartsTimeSeries__ ([Dokumentation](EChartsTimeSeries/README.md))
  Erste historische Chartfamilie für 1 bis 8 archivierte numerische Quellen.
  Rohwerte bleiben ausdrücklich wählbar; alternativ stehen automatische oder
  feste Symcon-Aggregationsstufen, Linien und Flächen, bis zu acht automatisch
  oder explizit links/rechts angeordnete Einheitengruppen, Punktbudget,
  konfigurierbare Unterbrechungen bei zeitlichen Datenlücken, Tooltip und einen
  eigenen Kacheldesigner für Legende, Zoom,
  Linien, Flächen, Raster und Achsen in einer nativen Kachel bereit. Ein
  eigener Echtzeitmodus zeichnet auch nicht archivierte Variablen ab dem
  Öffnen der Kachel fort. Optional kann jede Reihe Linie, Datenpunkte und
  Flächenfüllung einschließlich Verlauf oder sicher importiertem SVG-Muster
  individuell gestalten. Quellenbezogene Referenzlinien und Wertebereiche
  markieren Grenz-, Ziel- und Komfortwerte in beiden Ausgabewegen.

- __EChartsBarCategory__ ([Dokumentation](EChartsBarCategory/README.md))
  Vergleicht die aktuellen Werte von 1 bis 16 numerischen Variablen mit einer
  gemeinsamen Einheit. Einfache, gruppierte oder gestapelte vertikale und
  horizontale Balken, konfigurierbare Reihenfolge, individuelle Farben,
  Live-Aktualisierung sowie getrennte Kachel- und IPSView-Designs gehören zum
  Category-Bar-Vertrag.

- __EChartsBarHistory__ ([Dokumentation](EChartsBarHistory/README.md))
  Stellt rohe oder aggregierte Archivwerte von 1 bis 16 numerischen Variablen
  als vertikale Balken auf einer Zeitachse dar. Mehrere Reihen stehen
  nebeneinander; verschiedene Einheiten erhalten eigene Wertachsen. Zeitraum,
  Punktbudget, Beschriftung, Reduzierer, Farbe und Kacheldesign sind konfigurierbar.
  Der Zeitraum kann in Kachel und IPSView mit Regler oder Mausrad gezoomt werden.
  IPSView kann optional einen eigenen Zeitraum und ein eigenes Design verwenden;
  eine Begrenzung von Rohwerten wird sichtbar gemeldet.

- __EChartsBarWaterfall__ ([Dokumentation](EChartsBarWaterfall/README.md))
  Zeigt einen aktuellen Startwert und bis zu 15 geordnete Änderungen mit
  Vorzeichen als Waterfall-Diagramm. Die Endsumme wird berechnet; ein Archiv
  ist nicht erforderlich. Kachel und IPSView können getrennt gestaltet werden.

## Kacheldesign und IPSView-Design

Jede Datenquelle wird technisch ausschließlich über ihre Symcon-Variablen-ID
zugeordnet. Namen, Objektpfade und frei gewählte Beschriftungen dienen nur der
Anzeige oder fachlichen Gruppierung. Ein ausdrücklich konfigurierter Name hat
Vorrang, sonst gilt der aktuelle Symcon-Name der Variablen. Auch bei gleichen
Beschriftungen bleiben die Quellen intern über ihre IDs getrennt; IDs werden
nicht automatisch in Kachel, IPSView oder Vorschau angezeigt.

Bei allen ECharts-Diagrammen ist unter **IPSView-Design** die Option
**Kacheldesign verwenden** standardmäßig aktiv. Solange sie aktiv ist, folgt
die Diagrammgestaltung in IPSView dem Kacheldesigner. Die eigenen Designfelder
und der Knopf **Kacheldesign nach IPSView kopieren und unabhängig bearbeiten**
stehen direkt im Abschnitt **IPSView-Design**, sind dann aber deaktiviert.
Die HTML-Ausgabe und die IPSView-Hintergrundeinstellungen bleiben bedienbar.

Für ein eigenes IPSView-Design zuerst **Kacheldesign verwenden** ausschalten.
Danach ist der Kopierknopf sofort aktiv: Er übernimmt das zuletzt gespeicherte
Kacheldesign einmalig als Ausgangspunkt und überschreibt dabei die bisherigen
unabhängigen IPSView-Designwerte. Anschließend können diese Werte separat
bearbeitet werden; spätere Änderungen am Kacheldesign werden nicht mehr
automatisch übernommen. Datenquellen bleiben gemeinsam. Eigenständige
IPSView-Einstellungen wie Hintergrund und – sofern angeboten – Zeitraum
werden getrennt gesteuert. Bei TimeSeries und BarHistory liegen die
IPSView-Zeiteinstellungen neben den allgemeinen Zeiteinstellungen, nicht im
Designer. Änderungen im Kacheldesigner vor dem Kopieren erst speichern.

Für eine reguläre Installation genügt eine gemeinsame EChartsGateway-Instanz
für alle Gauge- und späteren Diagramminstanzen. Beim ersten Diagramm kann ein
neues Gateway angelegt werden; bei jedem weiteren Diagramm wird das bereits
vorhandene Gateway ausgewählt. Die von Symcon weiterhin angebotene Neuanlage
eines Parents ist für den Normalbetrieb nicht erforderlich.

## Installation

Die Library wird über die Symcon-Modulverwaltung aus folgendem Repository
installiert:

```text
https://github.com/Burki24/SymconECharts
```

Anschließend wird beim ersten Diagramm eine `EChartsGateway`-Instanz als Parent
angelegt. Weitere Diagramme verwenden dieselbe Gateway-Instanz.

## Konfiguration und Verwendung

Für jedes unabhängig zu konfigurierende Diagramm wird eine Instanz des
passenden Moduls angelegt. Datenquellen, Wertebereiche, Darstellung und die
optionale IPSView-Ausgabe werden in dieser Instanz festgelegt. Die ausführliche
Einrichtung ist in den oben verlinkten Modul-Readmes beschrieben.

## Änderungen

Die Versionshistorie steht in der [CHANGELOG.md](CHANGELOG.md).

## Lizenz

Die eigenen Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](LICENSE).
SPDX-Identifier: `PolyForm-Noncommercial-1.0.0`.

Required Notice: Copyright 2026 Burkhard Kneiseler. SymconECharts.

Fremdkomponenten behalten ihre Originallizenzen. Version, Herkunft, Integrität
und Lizenz der eingebundenen Apache-ECharts-Runtime stehen in
[THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

SymconECharts ist kein offizielles Projekt der Apache Software Foundation.
