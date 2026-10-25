# ADR 0079: Feste historische Von-/Bis-Zeiträume für Candlestick

- Status: Angenommen
- Datum: 2026-10-20
- Entscheider: Burki24
- Ergänzt: ADR 0056, 0061, 0077 und 0078

## Vertrag

Candlestick ergänzt `Range=absolute` sowie die String-Properties `AbsoluteStart`
und `AbsoluteEnd` mit Default `{}`. Native SelectDateTime-Objekte werden gemäß
ADR 0077 in der Symcon-Zeitzone streng interpretiert. Die offizielle
`Forms/SelectDateTime`-Dokumentation wurde am 20.10.2026 erneut per MCP gelesen.
Alte Properties, Defaults, Designs und Modulidentitäten ändern sich nicht;
eine Migration oder Änderung bestehender Instanzen ist nicht erforderlich.

Der Beginn ist eingeschlossen, das Ende ausgeschlossen und höchstens jetzt.
Nur vollständig enthaltene abgeschlossene Stunden beziehungsweise lokale
Kalendertage bilden Intervalle. Fehlende, ungültige, doppeldeutige und nicht
existierende Datumsgrenzen werden vor Archivzugriffen abgewiesen. Vollständige
23-/25-Stunden-Tage und beide tatsächlichen Herbststunden bleiben erhalten;
Stundenlabels enthalten den Offset. Die bestehende zentrale Einschränkung für
nicht ganzstündige Offsetwechsel bleibt wirksam. Gültige Fenster ohne ganzes
Intervall bleiben leer und führen keine Archivabfrage aus.

## Zentrale Planung und unveränderte Rohwerte

`EChartsArchiveQuery::ParseLocalDateTime` und `ResolveAbsolute` werden unverändert
wiederverwendet. `AbsoluteIntervals` ergänzt ausschließlich eine begrenzte Liste
der geplanten Intervalle. Die private Enumeration ist mit `CompletedIntervals`
geteilt; alle bisherigen Aufrufer behalten Ergebnisform, Grenzen und Budgets.
Die historischen Grenzen wandern nicht mit dem Timer weiter. Inaktive Custom-
Felder beeinflussen absolute Fenster nicht, inaktive Dates keine Altzeiträume.

`CandlestickData::Read` und `Summarize` bleiben unverändert. Erster und letzter
Wert sowie Minimum und Maximum stammen ausschließlich aus tatsächlichen
protokollierten Standard-Rohwerten im Intervall. Keine Mittelwert-Approximation,
Zähler, Vorgängerfortschreibung, Interpolation oder aktuellen Ersatzwerte.
Lücken bleiben leer, Einzelwerte ergeben flache Kerzen, Null-/Negativwerte bleiben.

Höchstens 720 Intervalle; 2000 Punkte je Rohabfrage; höchstens 63 disjunkte
Teilabfragen und 64000 akzeptierte Rohpunkte pro Aktualisierung. Überschrittene
Intervallbudgets scheitern bereits in der Planung. Bekannte Trunkierung führt
weiter zur begrenzten Halbierung, niemals zur Ausgabe unvollständiger Kerzen.
Der bestehende 60-Sekunden-Cache und beide begrenzten Ausgaben bleiben erhalten.
Gateway, Datenprotokoll und ausgelieferte ECharts-Runtime ändern sich nicht.

## Ausgabe und Vorschau

Schema 1 und alte `range`-Felder bleiben unverändert. Nur absolute Fenster
ergänzen `requestedStartTimestamp` und `requestedEndTimestamp` (ausschließlich).
`timezone` bleibt wie bisher vorhanden; effektive Archivgrenzen verwenden ein
eingeschlossenes Ende. Leere Fenster behalten gültige angefragte Metadaten.

Kachel und IPSView erhalten dieselben Daten mit ihren bisherigen unabhängigen
Designs. Beide SVG-Entwürfe validieren die Dates rein, zeigen den angefragten
Zeitraum samt Offset/Zone und gekennzeichnete Beispieldaten. Fehlerhafte Entwürfe
zeigen Hinweise statt Kerzen; keine Archiv-/Gatewayabfragen, Property- oder
Statusänderungen. Datums- und Intervallfelder aktualisieren beide Vorschauen.

## Nachweise und Abgrenzung

`php tests/gateway_gauges.php --candlestick-absolute` verwendet die vorhandene
Candlestick-Testinstanzklasse, das reale Modul und Gateway mit Kernel-Doppeln.
Der erste Lauf war RED wegen fehlendem absoluten Modus. Geprüft werden
Stunden/Tage, partielle Grenzen, OCLH, Null-/Negativwerte, Einzelwerte, Lücken,
DST, invalides Datum, leere Fenster, Legacy-Verträge, reine SVG-Entwürfe,
getrennte Designs, 720/721 Intervalle und 10000 Rohpunkte mit echter Halbierung.
Die bisherigen Candlestick-Tests sichern weiterhin 63 Anfragen und sichtbares
Scheitern bei zu dichten Archiven. Die reine zentrale Regression und die
ThemeRiver-Fenstertests sichern den gemeinsamen Enumerationsbaustein.

ThemeRiver und StateTimeline sind noch nicht auf absolute Fenster umgestellt.
Die automatisierte Qualitätssicherung erfolgt je Änderung; die gemeinsame
Die gemeinsame funktionale Live-Matrix des Archivzeiträume-Blocks ist inzwischen
per MCP geprüft; der Eigentümer bestätigt die neuen Zeiträume in Kachel/IPSView.
Es wurden keine laufenden Instanzen, Designs oder Archive verändert.
