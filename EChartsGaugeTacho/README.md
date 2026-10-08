# EChartsGaugeTacho

`EChartsGaugeTacho` rendert zwei bis fünf numerische Symcon-Variablen als
responsives Tacho-Cockpit. Die erste Quelle bildet das Hauptinstrument; bis zu
vier weitere Quellen werden als gleich große Nebeninstrumente angeordnet. Bei
vier und fünf Quellen nutzt eine eigene Anordnung den verfügbaren Platz breiter
Kacheln aus, ohne das Hauptinstrument zu verkleinern.

## Voraussetzungen

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5;
- eine aktive [EChartsGateway-Instanz](../EChartsGateway/README.md);
- zwei bis fünf Integer- oder Float-Variablen als Datenquellen;
- IPSView nur für das optionale WebContent-Widget.

## Installation und Einrichtung

Das Modul wird mit der Library **SymconECharts** installiert. Für jedes
Tacho-Cockpit wird eine EChartsGaugeTacho-Instanz angelegt und mit dem
gemeinsamen Gateway verbunden. Anschließend werden die Quellen in der
gewünschten Anzeigereihenfolge hinzugefügt.

## Konfiguration

Jede Quellenzeile enthält Variable, Beschriftung, Minimum, Maximum, Einheit
und Nachkommastellen. Optional werden Wertebereich, Einheit und Formatierung
aus der Variablendarstellung übernommen.

Der Kacheldesigner definiert das gemeinsame Grunddesign. Im Dialog einer
Quellenzeile kann **Individuelles Gauge-Design** aktiviert werden. Danach
überschreibt diese Quelle unabhängig Zeiger und Nabe, Skala und Texte, Farben
sowie das Zifferblatt. Eigene SVG-Zeiger mit justierbarem Drehpunkt, eigene
SVG-Naben und zugeschnittene SVG-Zifferblattmotive werden über die zentralen,
validierten ECharts-Importadapter verarbeitet. Die Designwerte bleiben Teil der
Quellenzeile und wandern beim Sortieren mit dem Instrument.
Für das unabhängige IPSView-Grunddesign kann jede Quelle ihr Kacheldesign
übernehmen oder eine eigene individuelle IPSView-Variante speichern.

Das optionale IPSView-WebContent-Widget nutzt dieselben Quellen. Sein
Grunddesign erbt standardmäßig das Kacheldesign oder kann unabhängig
konfiguriert werden. Wertänderungen aktualisieren das bestehende Diagramm über
den gemeinsamen Gateway-WebSocket, ohne das WebContent-Dokument neu zu laden.
Der Hintergrund kann transparent an IPSView angepasst und mit einer Theme-
oder Benutzerfarbe in frei wählbarer Deckkraft getönt werden.

Für ein eigenes IPSView-Grunddesign im Abschnitt **IPSView-Design** zuerst
**Kacheldesign verwenden** ausschalten. Solange die Option aktiv ist, sind die
eigenen Designfelder und der Knopf **Kacheldesign nach IPSView kopieren und
unabhängig bearbeiten** direkt im Abschnitt **IPSView-Design** deaktiviert.
Nach dem Ausschalten werden beide sofort aktiv. Der Knopf kopiert das
gespeicherte Kacheldesign einmalig und überschreibt dabei bisherige
unabhängige IPSView-Designwerte. Danach lassen sich Grunddesign
und individuelle Quellen-Designs für IPSView separat bearbeiten.
Änderungen am Kacheldesigner vor dem Kopieren speichern.

## Variablen

Standardmäßig legt das Modul keine Statusvariablen an. Bei aktivierter
IPSView-Ausgabe wird die Stringvariable **Gauge für IPSView** mit dem Ident
`IPSViewGauge` und einer WebContent-Darstellung erzeugt. Beim Abschalten bleibt
eine bereits angelegte Variable erhalten, bis sie im Formular ausdrücklich
gelöscht wird.
## Funktionen

Das Modulpräfix ist `ECGT`:

```php
$json = ECGT_GetGaugeData($InstanceID);
$html = ECGT_GetIPSViewHTML($InstanceID);
```

Eine gemeinsame `EChartsGateway`-Instanz genügt für alle Chart-Instanzen.

## Lizenz

Lizenz der eigenen Beiträge:
[PolyForm Noncommercial License 1.0.0](../LICENSE).
