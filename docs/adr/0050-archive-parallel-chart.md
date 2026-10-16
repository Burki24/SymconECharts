# ADR 0050: Parallelkoordinaten mit gemeinsamen Archivintervallen

- Status: Angenommen
- Datum: 2026-10-16
- Entscheider: Burki24
- Ergänzt: ADR 0001, ADR 0002, ADR 0005, ADR 0036 und ADR 0037

## Kontext

Nach Sunburst soll die Parallelfamilie entstehen. Eine Linie soll einen
Zeitpunkt mit mehreren ausgewählten Variablenwerten darstellen. Stunden- und
Tageswerte sollen wählbar sein. Für die erste Version bleiben Zeitfenster
bewusst auf 24 Stunden, sieben und 30 Tage begrenzt.

## Entscheidung

`EChartsParallel` besitzt die Modul-GUID
`{2B3B30E7-F7F2-47CF-A8AB-1E27F896A7DD}` und das Präfix `ECPA`.
Es ist kein Scatter- oder Time-Series-Modus. Die Instanz nutzt das
gemeinsame Gateway mit `archive.read`, bildet ihre Achsen jedoch selbst.
Eine reproduzierbare, lokal ausgelieferte ECharts-6.1.0-Runtime enthält
`ParallelChart`, `ParallelComponent`, Tooltip, Titel, Aria und Canvas.

`Sources` enthält zwei bis zehn eindeutige numerische Variablen-IDs in
konfigurierbarer Achsenreihenfolge und optionale Bezeichnungen. Pro Quelle
werden nur abgeschlossene aggregierte Archivintervalle gelesen. Der
Gateway-Reducer `auto` verwendet für Standardwerte Mittelwerte und für
Zähler Summen. Nur exakt gleiche Intervall-Zeitstempel werden zu einer
Linie verbunden. Fehlende Intervalle führen nicht zu Interpolation oder
Verschiebung. Es gibt keine gemeinsamen physischen Einheitenpflicht;
jede Achse zeigt ihre eigene Einheit.

Das Daten- und Renderbudget ist über `LineBudget` (1–1000, Standard 750)
begrenzt. Bei mehr Daten bleiben die jüngsten gemeinsamen Intervalle
erhalten; gatewayseitige Kürzung wird zusätzlich angezeigt. Kachel und
IPSView teilen die Datenquelle, erhalten aber optional getrennte Designs.
Die Formularvorschau arbeitet nur mit gekennzeichneten Beispieldaten.

## Grenzen und Prüfung

Ein fehlendes Archiv oder ein nicht gemeinsames Intervall ergibt eine
leere Ansicht beziehungsweise einen sichtbaren Ladefehler. Die grafische
Lesbarkeit bei sehr vielen Linien hängt von Größe und Transparenz ab.
Automatisches Aktualisieren erfolgt per Timer; das Modul löst keine
Schreiboperation auf Archiv oder Variablen aus.

Lokale Prüfungen decken Modul- und Asset-Verträge, Zeitstempelverknüpfung,
Gateway-Integration, Optionsaufbau und Ausgabelimit ab. Eine reale
Laufzeitprüfung in Symcon-Kachel und IPSView steht noch aus.
