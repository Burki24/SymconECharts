# ADR 0043: Gleitende Heatmap-Zeitfenster und Navigation

- Status: Angenommen
- Datum: 2026-10-13
- Entscheider: Burki24
- Ergänzt und präzisiert: ADR 0041
- Ersetzt durch: –

## Kontext

Die ursprüngliche 7-Tage-Abfrage verwendete 168 Stunden rückwärts ab der
aktuellen Uhrzeit. Dadurch konnten der erste und letzte Wochentag nur teilweise
enthalten sein und in derselben Wochentag-Stunden-Zelle vermischt werden. Eine
30-Tage-Ansicht hatte dieselben sieben Achsenzeilen wie die 7-Tage-Ansicht;
ein Datumsbezug und der Wechsel in frühere Zeitfenster fehlten. Der Eigentümer
möchte bei sieben Tagen einen Verlauf bis heute, bei dreißig Tagen aber
weiterhin nach Mo–So gemittelte Wochentage.

## Entscheidung

`Range` bleibt als vorhandene Property mit `7d` und `30d` erhalten. Beide
Werte bezeichnen nun lokale **Kalendertage einschließlich heute**, nicht eine
feste Anzahl Sekunden. Der jüngste Tag enthält nur abgeschlossene Stunden.
Der Modus `7d` zeigt die sieben konkreten Tage chronologisch und beschriftet
sie mit Wochentag und Datum. Jeder Wochentag kommt genau einmal vor; die
bisherige Aggregation nach Wochentag und Stunde bleibt somit verwendbar.
Der Modus `30d` mittelt weiterhin alle vorhandenen Stundenwerte pro lokalem
Wochentag und Stunde auf sieben Zeilen. Der angezeigte Datumsbereich macht
den größeren Erfassungszeitraum erkennbar. Lücken bleiben in beiden Modi leer.

Zurück/Vor/Heute sind in Kachel und IPSView lokale, nicht persistierte
Bedienzustände. Ein Schritt verschiebt das Fenster um die jeweils gewählten
sieben oder dreißig Kalendertage. Die Navigation reicht höchstens 120 Fenster
zurück; „Vor“ endet beim aktuellen Zeitraum. Nach einem Neuladen beginnt die
Ansicht wieder beim aktuellen Fenster. Regelmäßige Datenaktualisierungen
ersetzen ein ausgewähltes historisches Fenster nicht durch die Gegenwart.

## Transport und Kompatibilität

Der vorhandene, kanalisierte Gateway-State-Endpunkt nimmt optional einen
streng begrenzten `offset` und die Ausgabeart `tile` oder `ipsview` an. Nur
die Heatmap erlaubt über diesen Endpunkt zusätzlich den lesenden Kachelabruf;
die anderen Familien behalten ihre bisherige IPSView-Freigabe. Der Kanal ist
instanzbezogen und wird in der gerenderten Ausgabe bereitgestellt. Anfragen
lesen Archivdaten, verändern weder Instanz-Properties noch Variablen.
Die Antwort ergänzt den bisherigen Heatmap-Datenvertrag um Fensterindex,
Start-/Enddatum und bei `7d` um die sieben Tageszuordnungen. Vorhandene
Properties, GUIDs, Idents und Datenfluss-Version bleiben unverändert.

Die geänderte Interpretation von `7d` kann bestehende Heatmaps sichtbar
verändern: Die erste und letzte Teil-Tageszeile werden nicht länger in eine
gemeinsame Wochentag-Zelle gemittelt. Ein Rückfall ist durch das vorherige
Modul-Release möglich; eine Konfigurationsmigration ist nicht nötig.

## Nachweise

Tests prüfen lokale Tagesgrenzen, Sommerzeit, lückenlose Vorperioden, den
Gateway-Abruf samt ungültiger Fensterindizes und die Browser-Darstellung der
datierten Zeilen sowie die Navigation. Eine reale Kachel-/IPSView-Prüfung
bleibt nach dem Modulupdate erforderlich.
