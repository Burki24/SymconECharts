# EChartsGaugeChronograph

`EChartsGaugeChronograph` rendert zwei bis fünf numerische Symcon-Variablen in
einem gemeinsamen Chronographen. Die erste Quelle bildet das Hauptinstrument;
bis zu vier weitere Quellen erscheinen als eingebettete Hilfszifferblätter.

## Voraussetzungen

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5;
- eine aktive [EChartsGateway-Instanz](../EChartsGateway/README.md);
- zwei bis fünf Integer- oder Float-Variablen als Datenquellen;
- IPSView nur für das optionale WebContent-Widget.

## Installation und Einrichtung

Das Modul wird mit der Library **SymconECharts** installiert. Für jeden
Chronographen wird eine EChartsGaugeChronograph-Instanz angelegt und mit dem
gemeinsamen Gateway verbunden. Anschließend werden die Quellen in der
gewünschten Anzeigereihenfolge hinzugefügt.

## Konfiguration

Jede Quellenzeile enthält Variable, Beschriftung, Minimum, Maximum, Einheit
und Nachkommastellen. Optional werden Wertebereich, Einheit und Formatierung
aus der Variablendarstellung übernommen.

Der Kacheldesigner definiert das gemeinsame Grunddesign. Im Dialog einer
Quellenzeile kann **Individuelles Gauge-Design** aktiviert werden. Für dieses
Instrument lassen sich Zeiger und Nabe, Skala und Schrift, Farbrollen sowie
Zifferblatt und Kontur abweichend gestalten. Eigene SVG-Zeiger mit justierbarem
Drehpunkt, eigene SVG-Naben und zugeschnittene SVG-Zifferblattmotive werden
über die zentralen, validierten ECharts-Importadapter verarbeitet. Die Designwerte gehören zur
Quellenzeile und bleiben auch nach einer Umsortierung dem Instrument zugeordnet.
Für das unabhängige IPSView-Grunddesign kann jede Quelle ihr Kacheldesign
übernehmen oder eine eigene individuelle IPSView-Variante speichern.

Das optionale IPSView-WebContent-Widget nutzt dieselben Quellen. Sein
Grunddesign erbt standardmäßig das Kacheldesign oder kann unabhängig
konfiguriert werden. Wertänderungen aktualisieren das bestehende Diagramm über
den gemeinsamen Gateway-WebSocket, ohne das WebContent-Dokument neu zu laden.
Der Hintergrund kann transparent an IPSView angepasst und mit einer Theme-
oder Benutzerfarbe in frei wählbarer Deckkraft getönt werden.

## Variablen

Standardmäßig legt das Modul keine Statusvariablen an. Bei aktivierter
IPSView-Ausgabe wird die Stringvariable **Gauge für IPSView** mit dem Ident
`IPSViewGauge` und einer WebContent-Darstellung erzeugt. Beim Abschalten bleibt
eine bereits angelegte Variable erhalten, bis sie im Formular ausdrücklich
gelöscht wird.
## Funktionen

Das Modulpräfix ist `ECGC`:

```php
$json = ECGC_GetGaugeData($InstanceID);
$html = ECGC_GetIPSViewHTML($InstanceID);
```

Eine gemeinsame `EChartsGateway`-Instanz genügt für alle Chart-Instanzen.

## Beispielhintergründe

Unter `examples` liegen drei zurückhaltende SVG-Zifferblattmotive für
Temperatur, Luftfeuchtigkeit und Luftdruck. Sie besitzen einen transparenten
Grund und sind für die Kombination mit einer eigenen Plattenfarbe oder einem
Plattenverlauf vorgesehen.

## Lizenz

Lizenz der eigenen Beiträge:
[PolyForm Noncommercial License 1.0.0](../LICENSE).
