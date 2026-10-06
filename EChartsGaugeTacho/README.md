# EChartsGaugeTacho

`EChartsGaugeTacho` rendert zwei bis fünf numerische Symcon-Variablen als
responsives Tacho-Cockpit. Die erste Quelle bildet das Hauptinstrument; bis zu
vier weitere Quellen werden als gleich große Nebeninstrumente angeordnet. Bei
vier und fünf Quellen nutzt eine eigene Anordnung den verfügbaren Platz breiter
Kacheln aus, ohne das Hauptinstrument zu verkleinern.

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
konfiguriert werden. Ein realer IPSView-Laufzeittest ist mangels Lizenz nicht
möglich.

## Funktionen

Das Modulpräfix ist `ECGT`:

```php
$json = ECGT_GetGaugeData($InstanceID);
$html = ECGT_GetIPSViewHTML($InstanceID);
```

Eine gemeinsame `EChartsGateway`-Instanz genügt für alle Chart-Instanzen.
