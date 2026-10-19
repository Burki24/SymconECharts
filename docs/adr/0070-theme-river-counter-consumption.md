# ADR 0070: Zählerverbrauch als ThemeRiver-Datengrundlage

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0058, 0062 und 0069

## Entscheidung und Kompatibilität

Die globale Property `Metric` bietet `average` (bisheriger Default) und
`consumption`. Standardarchive liefern Mittelwerte, Zählerarchive liefern
Verbrauch je abgeschlossenem Stunden-/Tagesintervall. Die Datengrundlage gilt
für alle Quellen und beide Ausgaben. Keine Mischung von Archivtypen, kein
automatischer Moduswechsel und keine Migration oder Umschreibung bestehender
Quellen, Designs, Zeiträume, IDs oder Properties. Schema 1 erhält additiv
`themeRiver.metric`; fehlende Felder älterer Renderermodelle bedeuten Mittelwert.

## Wiederverwendung und Semantik

`ThemeRiverData::ArchiveContract` ordnet die Kennzahl dem ausdrücklich
angeforderten Gateway-Reducer und erwarteten Archivtyp zu:

| Metric | Reducer | Archivtyp |
| --- | --- | --- |
| `average` | `average` | `standard` |
| `consumption` | `sum` | `counter` |

Die vorhandene Operation `archive.read`, ihr Cache und ihre Zählerlogik bleiben
unverändert. Die am 19.10.2026 über MCP geprüfte offizielle Symcon-Referenz
[AC_GetAggregatedValues](https://www.symcon.de/en/service/documentation/module-reference/core-instances/archive-control/ac-getaggregatedvalues/)
definiert `Avg` bei Zählern als Summe positiver Änderungen des Intervalls.
Diese Semantik verwenden bereits Calendar, Heatmap und CalendarPie.
Keine eigene Resetkompensation, Rohwertdifferenz oder Integration von Leistung.

Die Antwort muss zur Quelle, zum Zeitfenster, zum Archivtyp und zum effektiven
Reducer passen. Mismatches, ungültige/negative/nichtendliche Werte und gekürzte
Antworten erzeugen explizite Fehler statt Teilmodelle oder automatische Umdeutung.
Einheit und Präzision bleiben die Originaldarstellung der Variablen.

## Unveränderte Schutz- und Darstellungsregeln

Ein bis 16 eindeutige numerische Quellen derselben Einheit, maximal 720
abgeschlossene Intervalle, 1000 geladene Aggregate je Quelle und 16000 insgesamt
bleiben unverändert. Zeitfenster und DST-Logik werden geteilt. Der bestehende
strenge Join verbindet ausschließlich vollständige gemeinsame Intervalle;
fehlende Werte versteckter Quellen bleiben Lücken. Null ist echter Verbrauch,
kein fehlender Wert. Auswahl projiziert nur die sichtbare Bandgeometrie.

Die bestehenden Auswahl-, Startkompressions- und Ausgabehelfer gelten auch
für Verbrauch. Die Modellgrenze von 900000 Bytes und 1-MiB-HTML-Guard bleiben.
Renderer/Aria/Nullhinweise benennen Verbrauch statt Mittelwerte. Tooltips zeigen
die tatsächlichen Intervalle und Originalwerte samt Einheit. Die begrenzte
SVG-Vorschau verarbeitet ungespeicherte Metric-Entwürfe synthetisch und ist im
Verbrauchsmodus entsprechend beschriftet; sie liest keine Archive.

## Prüfung und verbleibende Grenze

Gezielte Regressionen prüfen Auswahl/defaultkompatible Modelle, wirkliche
Gateway-Zähleraggregate statt Roh-/Momentanwerte, falsche Archivtypen/Reducer,
Fehler und Wiederanlauf, negative/Nullwerte, versteckte Lücken, Budgets,
Stunden-/Tages-/DST-/Custom-Grenzen sowie Entwürfe ohne Schreibzugriffe.
Die native gepinnte ECharts-Runtime prüft beide Ausgaben und Originaltooltips.
Neue Fremdkomponenten oder geänderte Runtimeartefakte sind nicht notwendig.
Live-Abnahme in Symcon und IPSView erfolgt nach Modulupdate durch den Eigentümer;
Live-Instanzen und Archive werden bei dieser Umsetzung nicht verändert.
