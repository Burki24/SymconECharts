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
Navigation. Live-Prüfungen in Symcon-Kachel und IPSView sind
nach Installation durch den Anwender erforderlich; sie sind noch nicht erfolgt.
