# ADR 0049: Sunburst mit manuellen Gruppen und aktuellen Variablenwerten

- Status: Angenommen
- Datum: 2026-10-16
- Entscheider: Burki24
- Ergänzt: ADR 0001, ADR 0002, ADR 0005, ADR 0036, ADR 0037 und ADR 0048

## Kontext

Nach der Treemap soll eine konzentrische Darstellung derselben fachlichen
Hierarchie entstehen. Die Anwenderentscheidung ist: Gruppen und optionale
Untergruppen werden manuell benannt; Gewichte stammen ausschließlich aus
aktuellen numerischen Symcon-Variablenwerten.

## Entscheidung

`EChartsSunburst` erhält die eigene GUID
`{A90CEE66-8708-4C2B-A475-1BAEDC74C359}` und das Präfix `ECSB`.
Das Modul ist kein Darstellungsmodus der Treemap. Es nutzt denselben
validierten Hierarchieaufbau aus `TreemapModel`, jedoch ein eigenes Formular,
eigene Sunburst-ECharts-Optionen und einen separaten, reproduzierbaren
ECharts-6.1.0-Bundle. Der gemeinsame Modellbau ist auf Datenbildung
beschränkt und verändert die Treemap-Konfiguration nicht.

Die persistierte `Sources`-Liste enthält 1–100 eindeutige Integer- oder
Float-Variablen mit `Group`, optionalem `Subgroup` und optionalem `Label`.
Technische Blattidentität ist die Variablen-ID. Gruppen und Untergruppen
summieren ihre Kinder. Zulässig sind nur endliche, nichtnegative Rohwerte.
Nullwerte bleiben erhalten, belegen jedoch keinen sichtbaren Winkel; wenn
alle Werte null sind, wird eine leere Ansicht gezeigt. Es erfolgen keine
Einheitenumrechnung, Absolutwertbildung oder Archivabfragen.

Kachel und optionales IPSView verwenden dasselbe Datenmodell, aber auf
Wunsch getrennte Designs. Anpassbar sind Theme, Gruppenfarben, Titel- und
Beschriftungsfarbe/-größe, Wertanzeige, Innen-/Außenradius,
Segmentrahmen und Klicknavigation. Die Formularvorschau zeigt
ausdrücklich schematische Beispieldaten. Änderungen an Quellvariablen
aktualisieren die Ausgabe.

Ergänzend sind Tooltip-Anzeige, Startwinkel, Drehrichtung und optionale
Sortierung nach Wert sowie radiale, tangentiale oder waagerechte Labels
konfigurierbar. Der Mindestwinkel für Labels und Sichtbarkeit/Schriftgröße
je Ring erlauben eine lesbare Darstellung dichter Hierarchien. Ein
Segment-Eckenradius kann unabhängig von der Rahmenbreite gesetzt werden.
Alle Optionen gelten für Kachel und unabhängiges IPSView-Design getrennt;
die bisherigen Darstellungswerte sind Standard.

## Grenzen und Prüfung

Die 100-Quellen-Grenze begrenzt Daten- und Renderbudget. Gemischte
physikalische Einheiten sind technisch zulässig, fachlich aber oft nicht
vergleichbar. Die lokale Prüfung umfasst Modulvertrag, Datenhierarchie,
Optionsaufbau, Bundle-Integrität und Symcon-Ausgabelimit. Die visuelle
Bedienung der Grunddarstellung in realer Kachel und IPSView wurde vom
Anwender bestätigt. Ein Livetest der ergänzten Designoptionen steht aus.
