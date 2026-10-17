# ADR 0056: Candlestick aus abgeschlossenen Archivintervallen

- Status: Angenommen
- Datum: 2026-10-17
- Ergänzt: ADR 0002, ADR 0005, ADR 0017, ADR 0036 und ADR 0037

## Entscheidung

`EChartsCandlestick` erhält die GUID `{A5C60024-BB95-4417-9632-763CBE9C14D7}` und das Präfix `ECCA`.
Eine numerische Standard-Archivvariable liefert pro abgeschlossenem Stunden-
oder Tagesintervall den ersten und letzten tatsächlich protokollierten Wert
sowie Minimum und Maximum. ECharts erhält [erster, letzter, Minimum, Maximum].
Zähler sind ausgeschlossen. Es gibt keine Interpolation, Fortschreibung,
Boundary-Haltewerte oder ersatzweise Nutzung von Mittelwerten. Ein einzelner
Messwert ergibt eine flache Kerze; ein leeres Intervall bleibt leer.
Steigende/fallende Werte sind keine fachliche Gut-/Schlechtbewertung.

Zeiträume sind 24 Stunden, 7 und 30 Tage: im Stundenmodus 24/168/720
abgeschlossene reale Stunden, im Tagesmodus 1/7/30 abgeschlossene lokale
Kalendertage. Die Instanz verwendet die Symcon-Zeitzone. Tagesgrenzen beachten
Sommerzeit; Stundenbeschriftungen enthalten den UTC-Offset und unterscheiden
dadurch doppelte Stunden beim Herbstwechsel.

## Wiederverwendung und Grenzen

Boxplot belegt den Archiv-, Ausgabe-, Fehler- und Designerablauf. Die vorhandenen
Transport-, Animations-, Preview-, Theme-, SourceIdentity- und Presentation-
Bausteine werden unverändert verwendet. Nur Candlestick-Zeitfenster, Rohdaten-
Zusammenfassung und Kerzengeometrie verbleiben in der neuen Familie.
Das Gateway bleibt unverändert: `archive.read` im Rohdatenmodus liefert maximal
2000 Punkte. Abgeschnittene Antworten werden nicht ausgewertet, sondern das
Zeitfenster wird in disjunkte Abschnitte geteilt. Höchstens 63 Anfragen und
64000 akzeptierte Rohpunkte pro Modell sind erlaubt. Eine nicht weiter teilbare
oder zu dichte Abfrage scheitert vollständig und sichtbar statt Teilkerzen zu
zeigen. Es wird weder ein Scan noch eine Archiv-Neuberechnung gestartet.

Ein familienlokaler 60-Sekunden-Cache vermeidet doppelte Archivabfragen für
Kachel und IPSView. Er enthält ausschließlich validierte, begrenzte Daten,
ist an Variable, Zeitfenster und Intervall gebunden und wird bei ApplyChanges
geleert. Keine persistenten Archivkopien oder Änderungen an Quellwerten.

Die separate Runtime enthält CandlestickChart, Grid, Title, Tooltip, Aria und
CanvasRenderer aus dem unveränderten Apache ECharts 6.1.0. Herkunft, Lizenz,
Hash und Build werden wie bei den vorhandenen Subsets geprüft.

## Prüfung und verbleibende Lücken

Reine Tests prüfen Rohpunktgrenzen, Begrenzung und Trunkierung, OCLH-Reihenfolge,
fehlende Intervalle, Einzelwerte, negative Werte und Sommerzeit. Integrations-
tests prüfen Gateway, Formular, Referenzen, beide Ausgabewege und Fehlerfälle.
Die echte ECharts-Engine prüft die erzeugten Optionen. Lokale Browserprüfung
ersetzt keine Abnahme der installierten Symcon-Kachel und des IPSView-Widgets;
diese folgen nach Modulupdate und Instanzanlage durch den Eigentümer.
