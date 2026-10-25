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
die nachfolgende Abnahme ist durch den Eigentümer für beide Ausgaben bestätigt.

## Designer-Erweiterung vom 17.10.2026

Der Eigentümer hat die erste Version in Kachel und IPSView bestätigt.
Die anschließend anhand der offiziellen Dokumentation ergänzten
Designfelder verändern weder Rohwerte noch Zeitfenster oder Quellidentität.

| Designer | ECharts-Option |
| --- | --- |
| Feste Kerzenbreite (0 = automatisch) | series.barWidth, begrenzt durch bisherige min/max und verfügbaren Platz |
| Getrennte Anstiegs-/Abfallsrahmen | itemStyle.borderColor / borderColor0 |
| Hohle Anstiegs-/Abfallskörper | itemStyle.color / color0 = transparent; Rahmen unabhängig |
| Hover-Rahmenbreite, -farbe, -deckkraft | emphasis.itemStyle.borderWidth / borderColor / borderColor0 / borderColorDoji / opacity |
| Zeitachsenfarbe und -linienart | categoryAxis.axisLine.lineStyle.color / type |
| Beschriftungsabstand | axisLabel.margin |
| Zeitachse umkehren | categoryAxis.inverse |
| Nullpunkt einschließen | valueAxis.scale = false |
| Lokale Schriftart, Titel-/Achsenkursivschrift | textStyle.fontFamily / fontStyle |

Die 16 neuen Felder haben neutrale Defaults: automatische Breite, gefüllte
Körper, bestehende Rahmenfarben, unveränderte Hover-Deckkraft, normale
Zeitreihenfolge, automatische Werteskala, Arial und keine Kursivschrift.
Hover-Rahmenbreite 2 und Achsenabstand 8 entsprechen dem bisherigen
ECharts-Verhalten. Bestehende Properties werden weder migriert noch überschrieben.
Die feste Breite muss zwischen Minimum und Maximum liegen und darf im
responsiven Renderer weiter schrumpfen. Neue Rahmenfarben haben Vorrang vor
der gemeinsamen Rahmenfarbe; die Neutralfarbe bleibt separat.

Die vier bereits bei Funnel verwendeten lokalen Schriftfamilien werden
über denselben begrenzten Wertevertrag angeboten. Gemeinsame Formular-,
Transport- und Animationsbausteine bleiben unverändert. Es gibt keine
externen Schriftressourcen, freie CSS- oder JavaScript-Eingaben.

Auswahlzustände, Markierungen, Brush/DataZoom, freie Formatter, andere
Koordinatensysteme und Large/Progressive sind keine weiteren Designschalter
dieses Schritts. Sie benötigen bei Bedarf einen eigenen Interaktions-,
Daten- beziehungsweise Performancevertrag. Die gepinnte Runtime und das
Gateway werden hier nicht verändert.

Quellen des Abgleichs:
[Candlestick](https://echarts.apache.org/en/option.html#series-candlestick),
[Originaldokumentation](https://github.com/apache/echarts-doc/blob/master/en/option/series/candlestick.md),
[Schriftgestaltung](https://github.com/apache/echarts-doc/blob/master/en/option/partial/text-style.md)
sowie die unveränderten Achsen-Typverträge der gepinnten ECharts-6.1.0-Abhängigkeit.
PHP-Integration, echte ECharts-Geometrie und lokale Browserdarstellung werden
für die Ergänzung erneut geprüft. Die nachfolgende Live-Abnahme der neuen Optionen in Kachel und IPSView ist
durch den Eigentümer bestätigt, zusätzlich zur früheren Abnahme der Basisversion.
