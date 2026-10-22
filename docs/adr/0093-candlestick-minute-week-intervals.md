# ADR 0093: Minuten- und Wochenintervalle für Candlestick

- Status: Angenommen
- Datum: 2026-10-22
- Ergänzt: ADR 0056, 0079 und 0092

## Vertrag

`Interval` erlaubt zusätzlich `fifteen-minutes`, `thirty-minutes` und `week`.
`hour`, `day`, Default `day`, Properties, Identitäten und Designs bleiben erhalten;
eine Migration ist nicht erforderlich. Die Zeitkonfiguration ist gemeinsam für
Kachel/IPSView, ihre Designer und Zoomoptionen bleiben unabhängig.

Minutenintervalle beginnen lokal bei :00/:15/:30/:45 beziehungsweise :00/:30.
Die Grenze wird über die tatsächliche UTC-Verschiebung berechnet, nicht über
ein globales UTC-Raster oder eine mehrdeutige `setTime`-Normalisierung.
Beide Herbststunden bleiben mit Offset im Label unterscheidbar. Offsetwechsel
müssen ein Vielfaches der Intervalllänge sein; nicht passende historische
Übergänge werden ausdrücklich abgewiesen. Viertelstunden unterstützen damit
auch einen 15-Minuten-Wechsel, Halbstunden nicht. Bestehende Stundenregeln
einschließlich ihrer strengeren historischen Einschränkung bleiben erhalten.

Wochen beginnen montags 00:00 Uhr in der Symcon-Zeitzone und enden am folgenden
Montag. Labels verwenden das ISO-Jahr und die ISO-Woche (`o-\WW`). Echte
167-/169-Stunden-Wochen bleiben vollständig. Keine Aufteilung nach UTC-Tagen
und keine angenommene feste Wochenlänge bei Kalendergrenzen.

Alle relativen Fenster enden an der letzten abgeschlossenen Intervallgrenze.
Wochen-Presets zählen wie Tages-Presets lokale Daten: 24 h → keine ganze Woche,
7 d → eine, 30 d → vier. Custom bleibt verstrichene Dauer, also 1 Woche =
604800 Sekunden, und kann daher keine vollständige Herbstwoche enthalten.
Absolute Grenzen sind weiterhin Beginn eingeschlossen/Ende ausgeschlossen;
nur vollständig enthaltene Intervalle werden ausgegeben. Leere Fenster sind
gültig und führen keine Archivabfrage aus. Kein automatischer Intervallwechsel.

## Zentralisierung und Kompatibilität

`EChartsArchiveQuery::CompletedIntervals` und `AbsoluteIntervals` ergänzen die
Rohintervallarten und teilen weiterhin `IntervalList`. `ResolveAbsolute` und
alle Symcon-Aggregatmodi bleiben auf ihren bisherigen Verträgen. Neue
Rohintervalllisten besitzen `AggregationLevel=null`, sie behaupten auch für
15 Minuten kein aggregiertes Archivmodell. Die bestehenden Hour-/Day-Aufrufer
behalten Metadaten, Grenzen und Budgets. Der Helper-Bestand wurde geprüft:
`Symcon_ModuleHelper::DateHelper` formatiert Datumsstrings, bietet aber keine
strikte ECharts-Archivplanung; diese bleibt im vorhandenen Projektbaustein.

`CandlestickData::Read`/`Summarize`, Gateway und Datenprotokoll bleiben unverändert.
Nur echte Rohpunkte liefern ersten/letzten Wert sowie Minimum/Maximum. Keine
Vorgängerfortschreibung, Aggregate, Interpolation oder aktuelle Ersatzwerte.
Null/negative Werte, Einzelwertkerzen und Lücken bleiben unverändert.
Es gelten weiterhin höchstens 720 Intervalle, 2000 Punkte pro Rohabfrage,
63 Teilabfragen und 64000 Rohpunkte. 30-Tage-Minutenfenster scheitern bereits
in der Planung. Cache-Schlüssel und Zoom-Kompatibilität enthalten die Intervallart.
Keine neue ECharts-Abhängigkeit und keine Änderung am Renderer erforderlich.

## Vorschau und Nachweise

Neue SVG-Entwürfe verwenden höchstens sieben synthetische Kerzen auf den
zentral geplanten Intervallbeschriftungen. Zu große/ungültige und leere Entwürfe
zeigen Hinweise statt scheinbar gültiger Kerzen. Keine Archivzugriffe,
Property- oder Statusänderungen. Beide Designer reagieren auf Intervalländerungen.

`php tests/candlestick_intervals.php` begann RED an der fehlenden Minutenart.
Es prüft exakte Raster/ISO-Wochen, historische Teilgrenzen, 720/721-Intervalle,
Minuten-DST einschließlich Lord Howe/Kathmandu, ISO-Jahreswechsel und Custom-DST.
`php tests/gateway_gauges.php --candlestick-intervals` prüft den echten
Modul-/Gateway-Pfad mit synthetischen Kernelarchiven, Rohwert-Endpunkte,
Lücken, Cache, unabhängige Designs, beide HTML-Ausgaben und reine SVG-Entwürfe.
Bestehende Daten-/Absoluttests sichern Rohwertbudgets und Hour-/Day-Verträge.
Native ECharts-Tests prüfen Labels, Tooltips, Geometrie, Lücken und Zoomansicht
für alle neuen Intervalle in beiden Ausrichtungen und Ausgabeadaptern.
Die lokale verschachtelte Canvas-Browserprüfung bestätigt für alle drei
Intervallarten echten Mausrad-Zoom in beide Richtungen bei unveränderter
Werteskala. Halbstunden-/Wochenansichten behalten ihren Zoom nach einem
Fixture-Refresh; im Browserprotokoll erschienen keine Warnungen/Fehler.
Diese synthetischen Frames ersetzen nicht den tatsächlichen Symcon-/IPSView-Host.

Der bisherige Zeitzoom ist vom Eigentümer in Kachel/nativer App und IPSView
einschließlich Mausrad bestätigt. Die neuen Intervallarten sind nach
Modulupdate separat live abzunehmen; echte Touchgeräte bleiben ungeprüft.
Keine laufende Instanz, kein Design und keine Archivierung wurde geändert.
