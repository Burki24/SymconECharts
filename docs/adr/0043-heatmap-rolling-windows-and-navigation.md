# ADR 0043: Heatmap-Kalenderzeiträume und Navigation

- Status: Angenommen
- Datum: 2026-10-13
- Entscheider: Burki24
- Ergänzt und präzisiert: ADR 0041
- Ersetzt durch: –

## Kontext

Die erste Heatmap bot sieben gleitende Tage und einen 30-Tage-Durchschnitt.
Gewünscht sind stattdessen eine Woche, vollständige Kalendermonate in vier
Längen und ein Vergleich seit dem 1. Januar. Die sieben Mo–So-Zeilen der
längeren Ansichten dürfen nicht als nur sieben berücksichtigte Tage
missverstanden werden.

## Entscheidung

Die Property `Range` bietet genau `1w`, `1m`, `3m`, `6m`, `12m` und `ytd`:

- `1w` zeigt sieben lokale Kalendertage einschließlich heute als datierte
  Zeilen. Der jüngste Tag enthält nur abgeschlossene Stunden. Jeder
  Wochentag kommt genau einmal vor.
- `1m`, `3m`, `6m` und `12m` umfassen die letzten vollständig abgeschlossenen
  Kalender-Monate. Am 13. Oktober 2026 zeigt `1m` den 1.–30. September,
  `3m` den 1. Juli–30. September. Der laufende, unvollständige Monat wird
  nicht einbezogen.
- `ytd` reicht im aktuellen Jahr vom 1. Januar bis heute; heute enthält
  ebenfalls nur abgeschlossene Stunden. Ein Schritt zurück zeigt den
  1. Januar bis zum gleichen Monat/Tag des Vorjahres. Der 29. Februar wird
  in einem Nicht-Schaltjahr auf den 28. Februar begrenzt.

Alle Ansichten außer `1w` mitteln **alle verfügbaren, abgeschlossenen
Archiv-Stundenwerte des angezeigten Zeitraums** je lokalem Wochentag und
Stunde auf sieben Mo–So-Zeilen. Ein Montag um 08:00 enthält beispielsweise
nur die vorhandenen Montag-08:00-Werte. Fehlende Archivwerte zählen nicht
als null; eine Zelle ohne Messung bleibt leer. Die Zeitraumbeschriftung
nennt den tatsächlichen Datumsbereich und kennzeichnet die Mittelung. Der
Tooltip nennt die Zahl der berücksichtigten Stundenintervalle.

Zurück/Vor/Heute sind in Kachel und IPSView lokale, nicht persistierte
Bedienzustände. Ein Schritt verschiebt `1w` um sieben Tage, Monatsansichten
um die gewählte Anzahl vollständiger Monate und `ytd` um ein Kalenderjahr.
Die Navigation reicht höchstens 120 Fenster und nie vor gültige
Archiv-Zeitstempel zurück; „Vor“ endet beim aktuellen Zeitraum. Nach einem Neuladen beginnt die
Ansicht wieder beim aktuellen Fenster. Regelmäßige Datenaktualisierungen
ersetzen ein ausgewähltes historisches Fenster nicht durch die Gegenwart.

## Transport und Kompatibilität

Der vorhandene, kanalisierte Gateway-State-Endpunkt nimmt optional einen
streng begrenzten `offset` und die Ausgabeart `tile` oder `ipsview` an. Nur
die Heatmap erlaubt über diesen Endpunkt zusätzlich den lesenden Kachelabruf;
die anderen Familien behalten ihre bisherige IPSView-Freigabe. Der Kanal ist
instanzbezogen und wird in der gerenderten Ausgabe bereitgestellt. Anfragen
lesen Archivdaten, verändern weder Instanz-Properties noch Variablen.
Die Antwort enthält Fensterindex, Start-/Enddatum, einen zur Laufzeit
zulässigen höchsten Fensterindex und bei `1w` die sieben Tageszuordnungen.
Die Wochentag/Stunde-Aggregation mit bis zu 7 × 24 Zellen bleibt für die
längeren Zeiträume erhalten; deren `count` zählt die tatsächlich
einbezogenen Stundenintervalle. Abfragen von mehr als 60 Tagen werden in
aneinander anschließende Abschnitte geteilt, damit das Gateway-Limit von
2.000 Archivwerten pro Anfrage nicht zu abgeschnittenen Mittelwerten führt.
Eine dennoch als gekürzt gemeldete Teilantwort ist ein Ladefehler, kein
vollständiges Diagramm.

Die bisherigen gespeicherten Werte `7d` und `30d` werden nicht als weitere
Auswahl oder Laufzeit-Alias fortgeführt. Eine betroffene bestehende Instanz
erhält nach dem Modulupdate einen Konfigurationsfehler, bis der Anwender im
Formular einen der sechs neuen Zeiträume auswählt und übernimmt. Die
Property-Identität, GUID, Idents und Datenfluss-Version bleiben unverändert;
Archivwerte werden nicht verändert. Der alte 30-Tage-Zeitraum ist nicht
gleichbedeutend mit dem letzten vollständigen Kalendermonat.

## Nachweise

Tests prüfen lokale Tagesgrenzen, Sommerzeit, Monats- und Jahreswechsel,
Schalttage, begrenzte und lückenlose Archivabschnitte, den Gateway-Abruf
samt ungültiger Fensterindizes und die Browser-Darstellung aller sechs
Zeitraumbeschriftungen sowie der Navigation. Eine reale Kachel-/IPSView-
Prüfung bleibt nach dem Modulupdate erforderlich.
