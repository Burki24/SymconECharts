# ADR 0052: Calendar mit archivierten Tagesmittelwerten

- Status: Angenommen
- Datum: 2026-10-16
- Entscheider: Burki24
- Ergänzt: ADR 0001, 0002, 0005, 0036 und 0037

## Entscheidung

`EChartsCalendar` erhält die GUID `{3F927445-41C7-44F4-8CEB-069581C5F3D1}` und
das freie Präfix `ECCL` (Calendar). Eine Instanz zeigt genau eine numerische,
normal aggregierte Archivvariable als Kalenderjahr. `Year=0` folgt dem
aktuellen Jahr, andernfalls gilt ein Jahr zwischen 1971 und dem aktuellen
Jahr. Es gibt keine zukünftigen Jahre. Navigation lädt frühere Kalenderjahre
über den vorhandenen lesenden und abgesicherten `WindowOffset`-Vertrag;
sie speichert keine Properties und bleibt je Browser unabhängig.

Ein Feld entspricht einem lokalen Kalendertag. Die Werte stammen unverändert
aus `archive.read`, `Mode=aggregated`, `AggregationLevel=1`, `Reducer=average`,
mit höchstens 366 Punkten. Der aktuelle Tag wird vollständig ausgeschlossen.
Ein Tageswert ist der Symcon-Archivmittelwert, kein selbst berechneter
Mittelwert von Stundenmitteln. Zählerarchive werden durch den Gateway-Vertrag
abgewiesen; es gibt keinen stillen Wechsel auf Verbrauch oder Summe.
Fehlende Tage bleiben Lücken, echte Nullen und negative Mittelwerte bleiben
erhalten. Auch ein nur teilweise aufgezeichnetes Jahr wird dargestellt.
Ein Tagesaggregat darf beim Aufzeichnungsbeginn im Tagesverlauf starten;
sein Zeitstempel wird dem lokalen Datum zugeordnet, der Mittelwert unverändert
übernommen. Es wird kein vollständiger 24-Stunden-Messzeitraum vorausgesetzt.
Trunkierung, doppelte Datumswerte oder formal ungültige Tagesdaten werden
nicht als gültiges Kalenderjahr ausgegeben.
Tagesgrenzen und Datumsschlüssel folgen der Symcon-PHP-Zeitzone einschließlich
Sommerzeit und Schaltjahren. Das Kalenderlayout ist kein 7×24-Heatmap-Modus.

Kachel und IPSView erhalten eigene Designer, die vorhandene Designvererbung,
Animation, Schrift-/Farbfelder, eine Farbskala und thematische Unterbereiche.
Historische SVG-Vorschauen verwenden gekennzeichnete Beispieldaten und lösen
beim Formularbearbeiten keine Archivabfragen aus. Kopfbereich und Titel
bleiben unabhängig frei. Eine eigene reproduzierbare Runtime aus dem
gepinnten ECharts 6.1.0 bindet `CalendarComponent`, `HeatmapChart`, Titel,
Tooltip, Aria, VisualMap und Canvas ein; der ECharts-Kern bleibt unverändert.

## Nachweise und Grenzen

### Ergänzter Designerumfang vom 16.10.2026

Die offizielle Calendar-, Heatmap- und VisualMap-Dokumentation wurde mit der
lokal gepinnten 6.1.0-Implementierung abgeglichen:

- [Calendar-Optionen](https://echarts.apache.org/en/option.html#calendar):
  getrennte `splitLine`-Stile, Start-/Endposition und Abstand von Tages- und
  Monatsnamen, Monatsausrichtung/-format und optionale Jahresbeschriftung.
- [Heatmap-Optionen](https://echarts.apache.org/en/option.html#series-heatmap):
  Zellrandart/-deckkraft, Schriftgewicht und deaktivierbare Hervorhebung.
- [VisualMap-Optionen](https://echarts.apache.org/en/option.html#visualMap-continuous):
  fester Wertebereich und umgekehrte `inRange.color`-Farbreihenfolge. Letzteres
  ändert bewusst die Wert-Farb-Zuordnung, nicht nur die Legendenrichtung.

Alle Properties gehören zur Gerätefamilie und gelten unabhängig für Kachel
und IPSView einschließlich Übernahme, Validierung und SVG-Vorschau. Die
Voreinstellungen erhalten automatische Farbgrenzen, normale Schrift, volle
Deckkraft, durchgezogene Ränder und die bisherige Positionierung. Eine
Jahreszahl ist zunächst ausgeschaltet; ihre Position berücksichtigt die
bereits belegten Namensränder. Feste Zellgrößen werden nicht angeboten, weil
sie den responsiven Kalender über den geschützten Darstellungsbereich hinaus
vergrößern könnten. Geo-only-Optionen wie `pointSize`/`blurSize`, interne
Ereignis-/Performancefelder und ausführbare Formatter werden nicht angeboten.
Hover wird in der statischen Vorschau nicht simuliert. Frei gestaltbare
Schatten, beliebige Rich-Text-Templates und interaktive Farbfilter bleiben
außerhalb dieses Designerumfangs.

Der Anwender hat die Grundversion einschließlich Teilarchivkorrektur am
16.10.2026 in Kachel und IPSView live bestätigt. Die zusätzlichen
Designeroptionen wurden anschließend ebenfalls vom Anwender in Kachel und
IPSView erfolgreich live bestätigt (16.10.2026).
Lokal wurden die ergänzten Stile am selben Tag mit der unveränderten
gebündelten Canvas-Runtime im Browser geprüft: hohe Standardkachel und
breites unabhängiges Design mit Jahreszahl, gegenüberliegenden Namen,
gestrichelten Monatsgrenzen, Zellstilen und umgekehrter fester Farbskala.

Die offiziellen Calendar-Optionen wurden am 16.10.2026 anhand von
<https://echarts.apache.org/en/llms.txt> und
<https://echarts.apache.org/en/option.html#calendar> geprüft.
Die Symcon-Semantik von Tagesaggregation, `Avg` und `TimeStamp` wurde anhand
der [offiziellen AC_GetAggregatedValues-Dokumentation](https://www.symcon.de/de/service/dokumentation/modulreferenz/kern-instanzen/archive-control/ac-getaggregatedvalues/)
geprüft. `calendar_data.php`, die Calendar-Integration im Gateway-Test und
`calendar_layout.js` sichern Jahresgrenzen, DST, Schaltjahre, fehlende/ungültige
Tageswerte, Gateway-Aggregation, Jahresnavigation, unabhängige Designs und den
realen ECharts-Renderer ab. Eine lokale Browserprüfung mit synthetischen
Schaltjahresdaten und der ausgelieferten Runtime bestätigte am 16.10.2026
beide Ausrichtungen, dunklen Hintergrund, Kopfbereich, Titel, Farbskala und
Navigation. Die oben genannte Anwenderbestätigung gilt nur für die vorhandene
Testinstallation, nicht allgemein für alle Symcon- und Clientvarianten.
