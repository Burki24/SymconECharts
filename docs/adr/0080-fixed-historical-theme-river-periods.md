# ADR 0080: Feste historische Von-/Bis-Zeiträume für ThemeRiver

- Status: Angenommen
- Datum: 2026-10-20
- Entscheider: Burki24
- Ergänzt: ADR 0058, 0061, 0069, 0070 und 0079

## Entscheidung und Kompatibilität

ThemeRiver ergänzt `Range=absolute` und die String-Properties `AbsoluteStart`
und `AbsoluteEnd`, beide mit Default `{}`. Die nativen SelectDateTime-Objekte
werden gemäß ADR 0077 in der Symcon-Zeitzone interpretiert. Die offizielle
`Forms/SelectDateTime`-Dokumentation wurde am 20.10.2026 erneut per MCP gelesen.
Es gibt keine Migration, neue Abhängigkeit oder Umschreibung bestehender
Quellen, Zeiträume, Kennzahlen, Designs, IDs oder gespeicherter Sichtbarkeiten.

Beginn eingeschlossen, Ende ausgeschlossen und höchstens jetzt. Nur vollständig
enthaltene abgeschlossene Stunden oder lokale Kalendertage bilden Intervalle.
Die zentrale Planung weist ungültige, fehlende, mehrdeutige, nicht existierende
und zukünftige Grenzen vor Archivzugriffen ab. 23-/25-Stunden-Tage und beide
Herbststunden bleiben unterscheidbar. Der bestehende Ausschluss historischer
Stundenfenster über nicht ganzstündige Offsetwechsel bleibt wirksam.
Ein gültiges Fenster ohne Vollintervall bleibt leer und liest keine Archive.
Historische Grenzen verschieben sich nicht durch Timer-Aktualisierungen.

## Wiederverwendung und unveränderte Fachregeln

`ParseLocalDateTime` und `AbsoluteIntervals` aus `EChartsArchiveQuery` werden
unverändert wiederverwendet. Alle Zeitrechnung und begrenzte Enumeration bleiben
zentral; es gibt keinen neuen lokalen Kalenderalgorithmus. Alte Zeiträume
verwenden weiterhin `ThemeRiverData::Window` mit denselben Argumenten und
derselben Ergebnisform. Inaktive Date-/Custom-Felder beeinflussen den jeweils
anderen Modus nicht. `ThemeRiverData` und das Gateway bleiben unverändert.

Standardarchive liefern weiterhin `average`, Zählerarchive ausdrücklich `sum`
als Symcon-Intervallverbrauch; kein Mischbetrieb, Zählerstand, eigene Differenz,
Resetkompensation oder Ersatz durch Momentanwerte. Originaleinheit und Präzision
bleiben erhalten. Ein Wert muss endlich und nichtnegativ sein; echte Null bleibt.

Der strenge Join aller konfigurierten Quellen bleibt erhalten. Fehlt eine
Quelle, ist das ganze Intervall `values=null`; ausgeblendete Quellen dürfen
eine Lücke weder schließen noch aus der Validierung verschwinden. Die
vorhandene Rendererlogik zeichnet weiterhin getrennte vollständige Abschnitte,
keine Nullfüllung oder Brücke über fehlende Daten.

Ein bis 16 Quellen; maximal 720 Intervalle, 1000 geladene Aggregate je Quelle
und 16000 gemeinsam, vor dem Join gezählt. Bekannte Trunkierung, übergroße
Antworten und falsche Archivtypen scheitern weiterhin statt Teilmodelle zu
liefern. Modellgrenze 900000 Bytes, vollständiges HTML 1 MiB und verlustfreie
Startkompression bleiben wirksam, auch bei 16×720 historischen Werten.

## Ausgabe, Vorschau und Nachweise

Schema 1 und bisherige `range`-Felder bleiben erhalten. Ausschließlich im
absoluten Modus kommen `requestedStartTimestamp` und `requestedEndTimestamp`
(ausschließlich) hinzu. Effektive Archivgrenzen verwenden weiterhin ein
eingeschlossenes Ende; `timezone`, tatsächliche Lademenge und Budget bleiben.

Kachel und IPSView verwenden dieselben Zeitgrenzen bei unabhängigen Designs.
Beide SVG-Entwürfe zeigen die angefragten Dates mit Offset/Zone, die jeweilige
Beispielkennzahl und Beispiellücke. Invalides Datum erzeugt Hinweise statt
Bandgeometrie. Obere Legenden erhalten im absoluten Modus Platz unter der
Datumskopfzeile; Altgeometrie bleibt erhalten. Keine Properties, Status oder
Archive werden beim Öffnen oder Bearbeiten des Formulars geändert.

`php tests/gateway_gauges.php --theme-river-absolute` verwendet die vorhandene
ThemeRiver-Testklasse, das reale Modul und Gateway mit Kernel-Doppeln. Der
erste Lauf war RED wegen fehlendem absoluten Modus. Geprüft werden beide
Kennzahlen, echte Null, versteckte Lücken, stabile Grenzen, partielle Intervalle,
DST, ungültige Dates, leere Fenster ohne Query, alte Zeiträume, reine Vorschauen,
unabhängige Designs, 16×720 Werte, 721er-Abweisung, Trunkierung, Budgetfehler,
negative Daten, falsche Archivtypen und Wiederanlauf ohne Konfigurationsänderung.
Die bestehenden zentralen Fenster- und ThemeRiver-Rendererprüfungen bleiben aktiv.

Im Roadmap-Block „Archivzeiträume“ fehlt noch StateTimeline. Danach folgt die
gebündelte manuelle Live-Matrix; lokale automatische Prüfungen erfolgen weiter
je Änderung. Native Datumswahl und tatsächliche Symcon-Kachel/IPSView sind in
diesem Schritt noch nicht live abgenommen. Laufende Instanzen und Archive
werden nicht geändert oder neu berechnet.
