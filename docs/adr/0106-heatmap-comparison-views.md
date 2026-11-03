# ADR 0106: Getrennte Heatmap-Vergleichsansichten

Status: Angenommen; funktional in Kachel und IPSView abgenommen.

## Vertrag

`UseComparisonPanels=false` erhält die bisherige Einzelquelle einschließlich
aller Zeiträume, Kennzahlen und Designs. Im eingeschalteten Modus enthält
`Sources` ein bis vier benannte Ansichten, höchstens 64 KiB JSON. Jede Zeile
hat eine positive numerische `VariableID`, optional `Label` (120 Zeichen),
`PeriodOffset` (0–120, Default 0), `UseOwnPeriod` (Default false) und native
`AbsoluteStart`/`AbsoluteEnd`-Datumsstrings. Namen sind niemals Schlüssel.
Dieselbe ID darf mehrere Perioden oder Ansichten belegen.

Ohne eigenen Zeitraum verwendet die Ansicht die vorhandene globale
Zeitraumplanung, verschoben um `PeriodOffset` frühere Perioden. Eigene Grenzen
verwenden stattdessen den vorhandenen absoluten Heatmap-Vertrag. Navigation
addiert ihren flüchtigen Offset zu jeder Zeile, auch bei eigenen Grenzen.
Die gemeinsame Rückwärtsgrenze berücksichtigt alle Quellen und die Epoche.
Keine neue Datumsarithmetik, Interpolation oder Nullauffüllung.

Die Kennzahl bleibt global. Standard-/Zählerarchive, vollständige Stunden und
Wochentag-/Stundenreduktion folgen unverändert ADR 0062/0083. Ein Tag bleibt
eine datierte Zeile nur im bestehenden Wochenmodus, nicht in längeren Fenstern.

## Budget und Darstellung

Alle Ansichten teilen **sieben** Abfragen mit unverändert höchstens 2000
Aggregaten je 60-Tage-Chunk. `HeatmapComparison` plant und dedupliziert exakte
ID-/Grenzenkombinationen vor dem ersten Zugriff. Überschreitungen werden
abgewiesen, niemals still gekürzt. Jede Ansicht darf weiterhin höchstens
366 × 24 Stunden umfassen; mehrere volle Jahre erhöhen das Budget nicht.

`SharedColorScale=true` verwendet gemeinsame Grenzen aller vorhandenen
Zellwerte. Native Einheiten werden streng aufgelöst und müssen gleich sein;
andernfalls muss ausdrücklich die getrennte Skala gewählt werden. Getrennte
Skalen erlauben unterschiedliche Einheiten und bleiben sichtbar bezeichnet.
Feste Celsius-Farbklassen behalten ihre Grenzen und erfordern in diesem neuen
Modus °C in allen Ansichten. Originalwerte und Präzision bleiben unverändert.

Beide Ausgaben komponieren getrennte native ECharts-Flächen aus derselben
bisherigen Optionsfunktion. Zeitraum, Einheit, Kennzahl, Farbbezug und leere
Ansichten bleiben erkennbar. Die Scrollfläche beginnt unter Kachelkopf und
Navigation; große Schriften erhalten zusätzlich horizontalen Platz. Refresh
erhält Scrollpositionen, temporäre Fehler die zuletzt gültigen Werte.
SVG-Entwürfe komponieren dieselbe bisherige synthetische Geometrie mit dem
gemeinsamen Plan, ohne Archive zu lesen oder Konfiguration zu speichern.

## Wiederverwendung und Nachweise

Zentrale `EChartsArchiveQuery`, `HeatmapWindow`, `HeatmapAggregation`, native
Variablenmetadaten, Quellenidentität, Formular-/SVG-Helfer, Transport und
Refresh werden weiterverwendet. Fachliche Vergleichsplanung verbleibt in der
Heatmap-Familie. Referenzen werden ID-eindeutig nachgeführt; die alte
Einzelquellen-Property und ihr Attribut bleiben kompatibel erhalten.

Plan-, Modul-/Gateway-, Formular-, SVG- und native Rendererprüfungen decken
die neuen Verträge ab. Lokale Browser-Frames verwenden synthetische Daten.
Die nachfolgende MCP-Abnahme ist erfolgt; der Eigentümer bestätigt die
Vergleichsansichten in echter Kachel und tatsächlichem IPSView. Echte Touchgeräte,
gesonderte horizontale Mausradprüfung und eine allgemeine Live-Lastfreigabe
bleiben davon getrennt und sind nicht nachgewiesen.
