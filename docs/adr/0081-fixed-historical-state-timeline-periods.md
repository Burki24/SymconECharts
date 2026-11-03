# ADR 0081: Feste historische Von-/Bis-Zeiträume für StateTimeline

- Status: Angenommen
- Datum: 2026-10-20
- Entscheider: Burki24
- Ergänzt: ADR 0059, 0061, 0077 und 0080

## Entscheidung und zentrale Planung

StateTimeline ergänzt `Range=absolute` und die String-Properties `AbsoluteStart`
und `AbsoluteEnd`, Default `{}`. Native SelectDateTime-Objekte werden über
`ParseLocalDateTime` in der Symcon-Zeitzone geprüft. Die offizielle SDK-Seite
`Forms/SelectDateTime` wurde am 20.10.2026 erneut per MCP gelesen. Alte Properties,
Defaults, Zustandszuordnungen, Namen und Designs bleiben ohne Migration nutzbar.

Die gemeinsame Grenzprüfung wird aus `EChartsArchiveQuery::ResolveAbsolute`
als `ResolveAbsoluteWindow` extrahiert und von beiden Methoden verwendet.
Beginn positiv und vor dem Ende; Ende höchstens jetzt; gültige Zeitzone und
Jahre 1970–9999. Die bestehende Datumsprüfung weist fehlende, ungültige,
mehrdeutige und nicht existierende lokale Uhrzeiten ab. Es gibt keinen neuen
familienlokalen Kalenderalgorithmus. Andere Archivfamilien behalten ihre
Vollintervallrundung, Budgetregeln und Ergebnisformen unverändert.

StateTimeline verwendet ungerundete Sekunden und ein halboffenes sichtbares
Fenster `[Beginn, Ende)`. Selbst eine Sekunde ist ein gültiges Fenster.
Zeitwechsel verändern lediglich die tatsächlich verstrichene Dauer, auch bei
nicht ganzstündigen Offsetänderungen. Die Stundenaggregat-Einschränkung ist
hier nicht nötig. Historische Grenzen wandern nicht mit dem Timer weiter.
Inaktive Date-/Custom-Felder beeinflussen den jeweils anderen Modus nicht.

## Unveränderte Zustands- und Ressourcenverträge

`StateTimelineData` und `archive.states.read` im Gateway bleiben unverändert.
Die bestehende Archivoperation liest Punkte einschließlich EndTimestamp und
einen strikt früheren Vorgänger; StateTimeline verwendet denselben Zeitstempel
als exklusives Segmentende. Ein Ereignis am Endrand darf weiter gelesen und
gezählt werden, erzeugt aber keinen Abschnitt ohne Dauer. Es wird nicht eine
Sekunde vom Fenster abgezogen und kein neues Gateway-Grenzmodell eingeführt.

Vorgänger initialisieren den Anfang, ein Ereignis am linken Rand hat Vorrang.
Ohne beides bleibt der Anfang unbekannt; leere Archive werden nicht durch
Momentanwerte ersetzt. Gleiche Zustände werden zusammengefasst, Integer-Werte
und Boolean-Normalisierung sowie quellenspezifische Namen/Farben bleiben.
Keine Aggregation, Interpolation, Sampling oder automatische Zustandsbewertung.

Ein bis 32 Quellen mit bis zu 32 Zustandsdefinitionen je Quelle, maximal
2000 Fenster-Rohpunkte je Quelle und 16000 insgesamt, Vorgänger zusätzlich.
Bekannte Trunkierung, widersprüchliche Zeitstempel oder ungültige Vorgänger
scheitern vollständig. Modell höchstens 250000 HTML-sicher kodierte Bytes,
vollständiges HTML höchstens 1 MiB. Quelle 32 unterliegt denselben verbleibenden
Ladebudgets wie Quelle 1; Navigation ist keine Begrenzung des Prüfungsumfangs.

## Ausgabe und Vorschau

Schema 1 und alte Range-Felder bleiben erhalten. Nur `absolute` ergänzt
`requestedStartTimestamp` und `requestedEndTimestamp`; diese stimmen hier
ohne Rundung mit Start und exklusivem Segmentende überein. Anders als bei
Aggregatdiagrammen ist `range.endTimestamp` weiterhin das exklusive Segmentende.
Kachel und IPSView teilen Daten/Grenzen bei unabhängigen Designs.

Beide Vorschauen zeigen die gewählten Dates samt Offset/Zone, die Zeitachse
folgt dem tatsächlichen Fenster. Zustandsgeometrie bleibt ausdrücklich synthetisch.
Ungültige Dates liefern Hinweise statt plausibler Zustände. Formularöffnung
und Entwürfe lesen keine Archive, ändern weder Properties noch Status und
behalten die vorhandene gekennzeichnete Acht-Zeilen-Vorschau bei.

## Nachweise und gemeinsamer Abnahmeplan

`php tests/gateway_gauges.php --state-timeline-absolute` verwendet die bestehende
Testklasse mit steuerbarer Uhr, das reale Modul und Gateway mit Kernel-Doppeln.
Erster Lauf RED wegen fehlendem absoluten Modus. Regressionen prüfen exakte
Sekunden, Vorgänger, beide Ränder, unbekannte Anfänge, leere Archive, Wiederholungen,
stabile Fenster, DST einschließlich halbstündigem Wechsel, invalide Dates,
Altmodelle, reine Vorschauen, beide Ausgaben und 32×500 Rohpunkte. Der letzte
Request erhält nur noch 500 Punkte Restbudget; übergroße, gekürzte, widersprüchliche
oder vorgängerfalsche Antworten dürfen kein Teilmodell erzeugen. Bestehende
Zustands-/Gateway-/Ausgabetests bleiben aktiv; die zentrale Regression sichert
ungerundete Grenzen und die unveränderte Aggregatplanung.

Alle fünf geplanten Familien unterstützen damit feste historische Zeiträume.
Die gemeinsame funktionale Live-Abnahme von Boxplot, Parallel, Candlestick,
ThemeRiver und StateTimeline ist inzwischen per MCP erfolgt und vom Eigentümer
in Kachel und tatsächlicher IPSView-App bestätigt. Die vollständige automatisierte
Grenz-/Fehlermatrix bleibt von dieser funktionalen Live-Abnahme getrennt:
relative/feste Fenster, Stunde/Tag oder exakte Zustände, Zeitwechsel, Teilarchive,
unbekannte Anfänge/Lücken, ungültige Dates und Ressourcenfehler. Neue feste Fenster
vorzugsweise in ausdrücklich freigegebenen temporären Testinstanzen prüfen;
bestehende Quellen, Designs und Archive nicht ungefragt umschreiben.
Laufende Instanzen und Archive wurden in diesem Schritt nicht verändert.
