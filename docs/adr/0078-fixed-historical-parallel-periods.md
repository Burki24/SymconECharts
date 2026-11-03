# ADR 0078: Feste historische Von-/Bis-Zeiträume für Parallel

- Status: Angenommen
- Datum: 2026-10-20
- Entscheider: Burki24
- Ergänzt: ADR 0077, 0061 und 0036

## Vertrag

Parallel ergänzt `Range=absolute` und die String-Properties `AbsoluteStart`
und `AbsoluteEnd`, standardmäßig `{}`. Native SelectDateTime-Objekte mit den
sechs Integer-Feldern aus ADR 0077 werden in der Symcon-Zeitzone interpretiert.
Die offizielle `Forms/SelectDateTime`-SDK-Dokumentation wurde am 20.10.2026
erneut per MCP gelesen. Es gibt keine neue Runtime-Abhängigkeit oder Migration.

Beginn eingeschlossen, Ende ausgeschlossen, vollständig enthaltene abgeschlossene
Stunden oder Kalendertage. Ungültige, fehlende, doppeldeutige, nicht existierende
und zukünftige Grenzen werden vor Archivabfragen abgewiesen. Ein Fenster ohne
vollständiges Intervall bleibt gültig und leer, ohne Nullfüllung oder Archivzugriff.
Stundenfenster über nicht ganzstündige Offsetwechsel bleiben gemäß ADR 0077
ausgeschlossen. Lokale 23-/25-Stunden-Tage und tatsächliche doppelte Herbststunden
bleiben getrennt erhalten. Timer-Aktualisierungen verschieben die Grenzen nicht.

`ParseLocalDateTime` und `ResolveAbsolute` aus `EChartsArchiveQuery` werden
unverändert wiederverwendet. Relative Altzeiträume durchlaufen weiterhin `Resolve`
mit unveränderten Argumenten; inaktive Datumsfelder beeinflussen sie nicht.
Inaktive Custom-Felder beeinflussen den neuen Modus ebenfalls nicht.

Die fachliche Schnittmenge in `ParallelPairing` bleibt unverändert: eine Linie
verbindet nur exakt identische Zeitstempel aller 2–10 Achsen in Quellenreihenfolge.
Keine Interpolation, Nullfüllung oder Ersatz durch aktuelle Variablenwerte.
Unterschiedliche Einheiten bleiben auf getrennten Achsen erlaubt. Der bisherige
Gateway-Reducer `auto` liefert Standardmittel beziehungsweise Zählerverbrauch;
keine lokale Zählerdifferenzbildung.

`LineBudget` bleibt 1–1000, Default 750. Jede Achse erhält höchstens dieses
Ladelimit, insgesamt maximal 10000 Aggregate bei zehn Achsen. Native Gateway-
Trunkierung und Auswahl der jüngsten gemeinsamen Linien bleiben samt Hinweis
erhalten; anders als Boxplot wird eine bekannte Kürzung nicht pauschal abgewiesen.

## Ausgabe und Vorschau

Schema 1 und die bisherigen `range`-Felder bleiben erhalten. Nur im absoluten
Modus kommen `requestedStartTimestamp`, `requestedEndTimestamp` (ausschließlich)
und `timezone` hinzu. `startTimestamp` und `endTimestamp` beschreiben die
effektiven Archivgrenzen mit eingeschlossenem Ende; leere Fenster verwenden
das gültige angefragte Metadatenfenster, führen aber keine Abfragen aus.

Kachel und IPSView teilen Daten und Zeitgrenzen, behalten unabhängige Designs.
Entwürfe validieren die Grenzen rein, zeigen angefragte Daten samt Offset/Zone
und ausschließlich gekennzeichnete Beispieldaten. Ungültige Entwürfe zeigen
einen Hinweis statt plausibler Linien. Sie ändern keine Properties, Status oder
Archive und kontaktieren keinen Gateway. Beide Datumsfelder und die Intervallwahl
sind unmittelbar in die vorhandenen Vorschauaktionen eingebunden.

## Prüfstrategie und Abgrenzung

`php tests/gateway_gauges.php --parallel-absolute` verwendet den realen Modul-/
Gateway-Pfad mit Kernel-Doppeln. Zunächst RED wegen fehlendem absolutem Modus;
danach gemeinsame Stunden-/Tageswerte, Lücken, Zähler, Null-/Negativwerte,
Zeitfortschritt, ungültige Dates, DST, leere Fenster, zustandsneutrale Entwürfe,
Altmodelle, getrennte Ausgaben und zehnmal 1000 Aggregate mit Kürzungshinweis.
`archive_absolute_windows.php`, `archive_windows.php` und die Boxplot-Regression
sichern die gemeinsame Basis; Renderer und Pairing bleiben unverändert.

Der Roadmap-Punkt „Archivzeiträume“ ist damit noch nicht vollständig umgesetzt:
Candlestick, ThemeRiver und StateTimeline benötigen ihre eigene Anbindung.
Gezielte automatisierte Tests erfolgen je Modul und zusätzlich repositoryweit.
Die manuelle Live-Abnahme kann nach Fertigstellung als gemeinsame Matrix über
alle Familien und beide Ausgabewege erfolgen, statt jedes Zwischenmodul einzeln
zu installieren. Dabei sind relative/feste Fenster, Stunde/Tag beziehungsweise
exakte Zustandsgrenzen, DST, Teilarchive, Lücken, Fehler und Budgets zu prüfen.
Die gemeinsame funktionale Live-Abnahme ist inzwischen per MCP erfolgt; der
Eigentümer bestätigt Kachel und tatsächliches IPSView. Lokale Tests bleiben
davon getrennt und sichern die vollständige Grenz-/Fehlermatrix.
Es wurden keine laufenden Instanzen, Konfigurationen oder Archive geändert.
