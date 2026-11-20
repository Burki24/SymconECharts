# ADR 0115 – Gestapelte Archivflächen in EChartsTimeSeries

- Status: Angenommen
- Datum: 2026-11-20
- Ergänzt [ADR 0067](0067-timeseries-more-series-and-selection.md) und [ADR 0114](0114-bar-history-stack-groups.md)

## Entscheidung

Das optionale Quellenfeld `StackGroup` übernimmt den BarHistory-Vertrag. Gruppe
0 oder ein fehlendes Feld verwendet den Einzelstil. Die Gruppen 1–32 zeichnen
Flächen auf einer gemeinsamen Wertachse. Zur Gruppe gehören auch ihre momentan
ausgeblendeten Quellen. Die wirksame Einheit muss innerhalb der Gruppe identisch
sein. Die vorhandene automatische oder feste Verdichtung liefert gemeinsame
Intervallzeitstempel für sämtliche Gruppenmitglieder.

Der Renderer richtet die Werte einer Gruppe vor dem Stapeln nach Zeitstempeln
aus. Ein fehlender Beitrag unterbricht alle Flächen dieser Gruppe, auch wenn im
betreffenden Intervall überhaupt kein Messpunkt vorliegt. Ein gemessener
Wert von 0 ist ein vollständiger Beitrag. Für einzelne
Reihen gilt weiterhin deren bisherige Lückenerkennung.

Die Legende steuert, welche Einzelwerte zu den sichtbaren Stapeln beitragen.
Positive und negative Beiträge liegen auf verschiedenen Seiten der Nulllinie.
Der Tooltip weist die Originalwerte sowie die algebraische Summe der sichtbaren
Beiträge je Gruppe aus. Damit lassen sich etwa die Beiträge mehrerer Dachflächen
zur PV-Gesamtleistung in kW lesen. Bestehende Abfrage-, Punkt- und Bytegrenzen
sowie die eigenständige JSLive-Importdarstellung gelten weiterhin.

## Nachweis

Die lokalen Modul- und Layouttests prüfen die Gruppierung, fehlende Beiträge,
vollständig leere Intervalle, Nullwerte, Tageswechsel bei Sommer-/Winterzeit
und die Legendenauswahl. Im isolierten Edge-Browser zeigen Kachel- und
IPSView-Layout für zwei synthetische Leistungsreihen im Tooltip 2 kW, 3 kW
und zusammen 5 kW. Nach dem Ausblenden der zweiten Reihe beträgt die sichtbare
Summe 2 kW. Mausradzoom in beide Richtungen ist in beiden Layouts geprüft.
Die Live-Abnahme dieser Erweiterung auf einer Symcon-Installation steht aus.
