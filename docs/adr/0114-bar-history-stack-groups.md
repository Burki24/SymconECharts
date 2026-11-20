# ADR 0114: Optionale Stapelgruppen für historische Balken

- Status: Angenommen
- Datum: 2026-11-20
- Ergänzt: [ADR 0068](0068-historical-bar-series-selection.md)

## Entscheidung

In der regulären BarHistory-Quellenliste steht `StackGroup` (Integer, 1–32)
für einen gemeinsamen Balken. Fehlend oder 0 erhält die Einzelreihe. Alle
Gruppenmitglieder benötigen die gleiche wirksame Einheit. Automatische und
feste Verdichtung nutzen die vorhandene gemeinsame Archivplanung. Der
Rohwertmodus mit unabhängigen Messzeitpunkten ist für Stapel ausgeschlossen.

Das Schema-1-Modell liefert die ursprünglichen Punkte jeder Quelle, bei
Gruppen ergänzt um `stackGroup`. Pro Gruppenquelle ist höchstens ein Punkt je
Intervallzeitstempel zulässig. Auf der kontinuierlichen Zeitachse stapelt die
native ECharts-Runtime nach Datenindex. Deshalb verwendet der Renderer für
jede Gruppe den Schnitt ihrer sortierten Zeitstempel, einschließlich der
ausgeblendeten Mitglieder. Der Schnitt erweitert die Datenmenge nicht.
Die Legende steuert anschließend die sichtbaren Beiträge. Positive und
negative Beiträge verwenden die native, nach Vorzeichen getrennte Stapelung.
Eine unvollständige Gruppe lässt die übrigen Gruppen und Einzelreihen unberührt.

Kachel und IPSView verwenden denselben Renderer. Die beiden Formularvorschauen
bilden die Gruppierung mit synthetischen Werten ab. Gateway, Punktverteilung,
Modell-/HTML-Grenzen, Reihenauswahl, Zoom und JSLive-Importmodell folgen
weiterhin ihren bisherigen Verträgen.

## Prüfnachweis

Die Integrationstests prüfen Quellenprüfung, gemeinsames Abfragelimit und
Segmentpositionen beider SVG-Vorschauen. Die nativen ECharts-Layouttests
prüfen versetzte Zeitstempel, unabhängige Gruppen, Nullwerte, Vorzeichen
und das Ausblenden einer Reihe. Im lokalen Edge-Canvas-Test ergeben zwei
synthetische PV-Reihen vier Stapel zu je 5 kW. Mausradzoom funktioniert im
Kachel- und im IPSView-Ausgabemodus. Die Live-Abnahme der Stapelgruppen auf
der Symcon-Testinstallation steht aus.
