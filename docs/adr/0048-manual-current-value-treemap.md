# ADR 0048: Treemap mit manuellen Gruppen und aktuellen Variablenwerten

- Status: Angenommen
- Datum: 2026-10-16
- Entscheider: Burki24
- Ergänzt: ADR 0001, ADR 0002, ADR 0005, ADR 0036 und ADR 0037

## Kontext

Eine Treemap benötigt eine ausdrücklich definierte Hierarchie und ein
numerisches Flächengewicht. Die Anwenderentscheidung lautet: Gruppen werden
manuell festgelegt; Gewichte stammen ausschließlich aus aktuellen
Symcon-Variablenwerten. Automatische Objekthierarchie oder manuelle
Konstantgewichte sind kein Bestandteil dieser ersten Treemap.

## Entscheidung

`EChartsTreemap` erhält die eigene GUID
`{9A4CDA7E-00FE-49FB-A017-20FDA75E7C04}` und das Präfix `ECTM`. Die
persistierte `Sources`-Liste enthält 1–100 Zeilen mit `VariableID`, `Group`,
optionalem `Subgroup` und optionalem `Label`. Die Variable muss existieren,
eindeutig sein und Integer oder Float liefern. Gruppen und Untergruppen
werden anhand ihrer vollständigen Namen zusammengeführt. Technische Blatt-ID
ist `variable-<ID>`; Namen sind nur Anzeigeinhalt.

Der Gateway liest je Quelle den aktuellen Rohwert. Nur endliche,
nichtnegative Werte sind zulässig; negative Werte ergeben einen sichtbaren
Fehler, Nullwerte bleiben im Modell, besitzen aber keine Fläche. Alle
Nullwerte ergeben eine leere Ansicht mit Hinweis. Es gibt keine stillschweigende
Absolutwertbildung, Verschiebung, Normierung oder Einheitenumrechnung. Die
Knotenwerte der Gruppen und Untergruppen sind Summen ihrer Kinder.

Ein eigener, aus dem festgeschriebenen ECharts-6.1.0-Paket reproduzierbar
gebauter Browser-Bundle enthält Treemap, Titel, Tooltip, Aria und Canvas.
Kachel und optionales IPSView nutzen denselben fachlichen Datensatz und
getrennte Designs. Die Formularvorschau verwendet ausdrücklich markierte
Beispielgrößen und die noch nicht gespeicherten Gruppennamen.

Ein erster Designausbau erlaubt optional feste Farben je exakt benannter
Gruppe, getrennte Schriftgrößen und -farben für Gruppen- und
Untergruppentitel sowie numerische Blattwerte. Nicht zugeordnete Gruppen
nutzen weiter die Theme-Palette. Die bisherigen Beschriftungseinstellungen
bleiben die Vererbungsbasis; zusätzliche Größen nutzen 0 als „übernehmen“
und Farben -1. Die neuen Werte sind additive Design-Properties für Kachel
und unabhängiges IPSView. Die bestehende Darstellung bleibt ohne aktive
Neukonfiguration erhalten. Ein Blattwert wird nur bei sichtbaren Labels
unter dem Namen ausgegeben; Flächenberechnung und Tooltip bleiben unverändert.

## Grenzen und Prüfung

Die 100-Quellen-Grenze schützt Daten- und Browserbudget. Ein Update liest
derzeit alle konfigurierten Werte neu; häufig ändernde große Quellmengen sind
auf der Symcon-Testebene hinsichtlich Latenz und Ausgabegröße zu prüfen.
Automatische Hierarchie, historische Summen und importierte Gewichte sind
separate künftige Anforderungen. Der lokale Test prüft Gruppierung,
Validierung, Bundle-Integrität und Browser-Optionsaufbau. Die grundlegende
Darstellung wurde am 16.10.2026 vom Anwender in Symcon-Kachel und IPSView
geprüft. Die daraufhin korrigierte Beschriftungsdarstellung benötigt nach
dem Modulupdate eine erneute visuelle Prüfung in beiden Ausgabewegen.
