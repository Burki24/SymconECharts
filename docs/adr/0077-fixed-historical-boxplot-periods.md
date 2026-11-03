# ADR 0077: Feste historische Von-/Bis-Zeiträume für Boxplot

- Status: Angenommen
- Datum: 2026-10-20
- Entscheider: Burki24
- Ergänzt: ADR 0054, 0061 und 0036

## Vertrag und Abgrenzung

Boxplot erhält zusätzlich `Range=absolute`. `24h`, `7d`, `30d` und `custom`
bleiben unverändert; der Default ist weiterhin `7d`. Die neuen String-Properties
`AbsoluteStart` und `AbsoluteEnd` starten mit `{}` ohne ausgewählten Zeitpunkt.
Sie speichern das native SelectDateTime-JSON aus `year`, `month`, `day`, `hour`,
`minute` und `second`. Quelle ist die offizielle
[SelectDateTime-Dokumentation](https://www.symcon.de/en/service/documentation/developer-area/sdk-tools/sdk-php/configuration-forms/selectdatetime/),
am 20.10.2026 über `symcon_documentation Forms/SelectDateTime` gelesen.
Es werden keine gespeicherten Konfigurationen migriert oder automatisch befüllt.

Der Beginn ist eingeschlossen, das Ende ausgeschlossen. `01.10. 00:00` bis
`08.10. 00:00` bezeichnet damit sieben vollständige Tage. Beide Zeitpunkte
werden in der aktuellen Symcon-Zeitzone interpretiert; diese steht im Formular.
Das Ende darf nicht in der Zukunft liegen, der Beginn muss vor dem Ende liegen.
Nicht gewählte, ungültige oder außerhalb positiver Archivzeitstempel liegende
Datumsangaben werden vor einem Archivzugriff abgewiesen. Inaktive Datumsfelder
haben auf die bisherigen Zeiträume keinen Einfluss, ebenso haben inaktive
Custom-Werte auf den festen Zeitraum keinen Einfluss.

Nur vollständig enthaltene abgeschlossene Stunden werden verwendet. Ein
teilweiser Beginn wird zur nächsten tatsächlichen Stunde aufgerundet, ein
teilweises Ende zur vorangehenden Stundengrenze abgerundet. Ohne vollständige
Stunde bleibt das Modell gültig und leer, ohne Archivabfrage oder Nullfüllung.
Ein Ende gleich dem aktuellen Zeitpunkt ist zulässig; die laufende Stunde
bleibt ausgeschlossen. Feste Zeitgrenzen verschieben sich beim Timer nicht.

Lokale Datumsangaben werden strikt geprüft, nicht durch PHP normalisiert.
Nicht existierende Frühlingszeiten und doppeldeutige Herbstzeiten sind als
ausgewählte Grenzen ungültig; ein Hinweis fordert einen eindeutigen Zeitpunkt.
Ein Zeitraum über die Zeitumstellung bleibt zulässig und umfasst alle
tatsächlich enthaltenen Stunden, einschließlich beider Herbststunden.
Die Stundenplanung weist nichtleere Fenster über nicht ganzstündige
Offsetwechsel ausdrücklich ab, ebenso daraus resultierende verschobene
Stundengrenzen. Die native Stundenarchiv-Semantik solcher Übergänge ist nicht
hinreichend abgesichert; eine falsche Teilstunde darf nicht als volle Stunde
einfließen. Ganze lokale Kalendertage der vorbereiteten Tagesplanung bleiben
ein davon getrennter Vertrag.

## Zentrale Planung, Ausgabe und Ressourcen

`EChartsArchiveQuery` ergänzt zwei explizite, reine Methoden:

- `ParseLocalDateTime(mixed, timezone)` liest das native Datum als JSON,
  assoziatives Array oder `stdClass`; sechs Integer-Felder, gültiges gregorianisches
  Datum, begrenzte Eingabe und eindeutige Zuordnung zu einem positiven Zeitstempel.
- `ResolveAbsolute(start, endExclusive, interval, pointBudget, sourceCount, now,
  timezone)` bildet vollständig enthaltene Stunden oder Kalendertage auf die
  bestehende Queryform mit eingeschlossenem Archivende ab. Die neue Tagesplanung
  berücksichtigt lokale 23-/25-Stunden-Tage, ist aber noch kein weiterer Modulmodus.

Bestehende `Resolve`-, `IsValidRange`- und `CompletedIntervals`-Verträge werden
nicht erweitert oder umgedeutet. Weitere Familien müssen die neue Planung
ausdrücklich integrieren; ihre Properties, Formulare und Datenmodelle bleiben
in diesem Schritt unverändert. Kein Gateway-, Runtime- oder Helper-Update.

Boxplot behält 1–32 Quellen, 1000 Stundenmittel je Quelle, 10000 insgesamt,
R7-Quartile, Min/Max-Whisker und unabhängige Lücken je Quelle. Ein zu großes
Archiv oder eine gekürzte Gatewayantwort wird weiterhin vollständig abgewiesen,
statt Teilstatistik anzuzeigen. Lange feste Zeitspannen heben kein Budget auf.

Schema 1 behält die vorhandenen `range`-Felder. Nur im absoluten Modus kommen
`requestedStartTimestamp`, `requestedEndTimestamp` (ausschließlich) und
`timezone` hinzu; die bisherigen Felder benennen die tatsächlich abgefragten
Grenzen mit eingeschlossenem Ende. Bei einem leeren Stundenfenster bleiben
die angefragten Grenzen als gültiges geschlossenes Metadatenfenster erhalten;
`Empty=true` verhindert jeden Archivzugriff.

Kachel und IPSView teilen den Zeitraum, nicht zwingend das Design. Die
vorhandene Renderer-Geometrie, Quellenidentität, Kategoriennavigation und
Statistik bleiben unverändert. Die SVG-Entwurfsvorschau zeigt gültige Grenzen
mit Offset/Zeitzone und weiterhin ausschließlich gekennzeichnete Beispieldaten.
Ungültige Entwürfe zeigen eine verständliche Meldung statt plausibler Boxen.
Formularaktionen lesen keine Archive und schreiben weder Properties noch Status.

## Nachweise

- `php tests/archive_absolute_windows.php`: Parsing, Grenzen, leere Fenster,
  Fortschreiten der Uhr, Zukunft, ungültige Daten, DST-Lücken/-Mehrdeutigkeit,
  tatsächliche Herbststunden, UTC und nicht ganzstündige Zonenoffsets sowie
  lokale 23-/25-Stunden-Tage. Anfangs gezieltes RED wegen fehlender Methode.
- `php tests/archive_windows.php`: unveränderte bisherige Zeitplanung.
- `php tests/gateway_gauges.php --boxplot-absolute`: echter Modul-/Gateway-Pfad,
  effektive und angefragte Grenzen, R7/gültige Nullwerte/Lücken, verschiedene
  Ausgabe-Designs, unveränderte Altverträge, zeitstabile historische Abfragen,
  zustandsneutrale Entwürfe und die bisherigen Quellen-/Archivbudgets.
- `node tests/boxplot_layout.js`: bestehende gepinnte Renderer-Geometrie und
  Navigation; die JavaScript-Ausgabe wird nicht geändert.
- Neue native Datumswahl und tatsächliche feste Archive in Symcon-Kachel und
  IPSView müssen nach Modulupdate durch den Eigentümer live bestätigt werden.
  Es wurden keine laufende Installation, Archivierung oder Quellwerte geändert.
