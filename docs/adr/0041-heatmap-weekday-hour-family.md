# ADR 0041: Heatmap als eigene Wochentag-Stunden-Familie

- Status: Angenommen
- Datum: 2026-10-13
- Entscheider: Burki24
- Ergänzt: ADR 0001, ADR 0017, ADR 0018, ADR 0036 und ADR 0037
- Ersetzt durch: –

## Kontext

ECharts unterstützt Heatmap-Serien auf kartesischen, Kalender- und weiteren
Koordinatensystemen. Die Symcon-Quellenverträge dieser Varianten unterscheiden
sich jedoch erheblich. Eine gemischte Universalinstanz würde Zeitaggregation,
freie X/Y-Kategorien und Kalenderlogik unter einem instabilen Property-Modell
vereinen. Der Eigentümer hat für die erste Vertikale ausdrücklich einen
Archivwert nach Wochentag und Stunde gewählt.

## Entscheidung

Die erste Heatmap ist **ein** eigenständiges Gerätemodul `EChartsHeatmap` mit
Präfix `ECHM` und Modul-GUID `{62F06D1A-873F-4B0E-9F3E-6AA9AA1A8274}`.
Sie nutzt eine numerische Archivvariable, identifiziert ausschließlich durch
deren Variablen-ID. Sie ist weder ein Scatter-Modus noch eine Kalenderansicht.

Die X-Achse zeigt lokale Stunden 0–23, die Y-Achse Montag–Sonntag. Das
Gateway liefert abgeschlossene Stundenaggregate für die letzten 7 oder 30
Tage. Pro Wochentag/Stunde wird das arithmetische Mittel der vorhandenen
Stundenwerte gebildet. Für nicht vorhandene Kombinationen entsteht **keine**
Null oder interpolierte Zelle. Bei der Zeitumstellung im Herbst gehen beide
tatsächlich vorhandenen lokalen 02-Uhr-Intervalle in dieselbe Zelle ein; die
im Frühjahr ausgefallene Stunde wird nicht ergänzt. Maßgeblich ist die
Zeitzone der Symcon-Installation. Zellenwerte und Tooltip übernehmen
Einheit/Nachkommastellen aus der Variablendarstellung oder manuellen Feldern.

Die Kachel und das optionale IPSView-HTML nutzen denselben Datenvertrag.
Theme, Skalenfarben, Skalen-/Wertesichtbarkeit, Textgrößen/-farben und
Animation können im Kacheldesigner oder im unabhängigen IPSView-Designer
gewählt werden. Beide zeigen eine direkt auf Formularänderungen reagierende,
ausdrücklich schematische SVG-Vorschau mit Beispieldaten. Die tatsächliche
ECharts-Ausgabe bleibt ein gesonderter Browser-Nachweis.

## Wiederverwendung und Laufzeit

`archive.read`, Abfragefenster, Animation, Themes, Designer-Gliederung,
Vorschauverdrahtung, IPSView-Hintergrund und Transport verwenden bestehende
Bausteine aus `libs` beziehungsweise unveränderte synchronisierte Helper.
Die fachliche Gruppierung nach lokaler Woche/Stunde verbleibt im Heatmap-
Modul. Ein separater, lokaler ECharts-6.1.0-Build enthält `HeatmapChart`,
`GridComponent`, `VisualMapComponent`, Titel, Tooltip, Aria und Canvas; er
vergrößert die bestehenden Familien-Bundles nicht. Herkunft, Integrität und
Lizenz werden wie bei den anderen Builds dokumentiert.

## Abgrenzung und Folgen

Die 7×24-Matrix ist nicht mit einem Kalender-Heatmap-Layout gleichzusetzen.
Ein späteres Kalenderdiagramm bekommt bei belegtem Datenvertrag eine eigene
Familienentscheidung. Freie X/Y-Kategorien, mehrere Quellen und alternative
Reduzierer werden nicht vorab in `ECHM` versprochen. Die ersten beiden
Zeiträume halten die Archivabfrage unter dem Gateway-Limit von 2000
Stundenpunkten. Tests für Gruppierung, Lücken und Sommerzeit sind Pflicht;
Kachel/IPSView und tatsächliche Archivdaten werden nach Modulupdate live
geprüft.
