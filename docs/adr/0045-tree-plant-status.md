# ADR 0045: Anlagenzustand als explizite Tree-Überlagerung

- Status: Angenommen
- Datum: 2026-10-13
- Entscheider: Burki24
- Ergänzt: ADR 0044

## Kontext

Die reine Objekthierarchie zeigt Zugehörigkeiten, aber keinen verdichteten
Anlagenzustand. Beliebige Symcon-Variablen dürfen nicht automatisch als
Alarmquelle interpretiert werden: Temperatur, Schaltzustand und Batterie
haben unterschiedliche Bedeutungen. Tree bleibt eine lesende Hierarchie;
Graph, Treemap und Sankey erhalten dadurch keinen neuen Vertrag.

## Entscheidung

`EChartsTree` erhält die optionale Property `StatusRules` (JSON-Liste, leerer
Default). Nur ausdrücklich ausgewählte Bool-, Integer- oder Float-Variablen
werden bewertet. Ihre Variable-ID ist der einzige Zuordnungsschlüssel.
Jede Variable darf nur eine Regel besitzen. Eine Regel enthält Richtung
(`high` oder `low`) sowie Warn- und Kritisch-Schwelle für den **rohen**
Zahlenwert. Bei `high` muss Kritisch ≥ Warnung sein, bei `low` Kritisch ≤
Warnung. Die kritischere Schwelle wird zuerst geprüft. Gleiche Schwellen
ermöglichen ein direkt kritisches Bool-Signal. Maximal 3000 Regeln sind
zulässig, entsprechend der maximalen Knotenzahl.
Das Formular macht Regelverstöße mit Zeile, Sensorname und Variablen-ID sichtbar; die
Schwellenreihenfolge wird bereits im Zeilendialog begrenzt. Diese
Formularprüfung ersetzt die verbindliche Validierung gespeicherter Properties
in `ApplyChanges()` nicht.

Nur sichtbare und innerhalb von Tiefe und Knotengrenze tatsächlich gelesene
Variablen wirken auf den Baum. Ein bewerteter Variablenknoten erhält `ok`,
`warning` oder `critical`; Eltern übernehmen jeweils den höchsten Zustand
ihrer sichtbaren Nachkommen. Nicht bewertete Zweige behalten ihre bisherige
Typfarbe. Eine abgeschnittene Hierarchie zeigt weiterhin die vorhandene
Kürzungswarnung; ihr Aggregat behauptet keine Aussage über ausgelassene
Knoten. Fehlende oder ungültige Statusquellen machen die Konfiguration
sichtbar ungültig; ein Lesefehler führt in den bestehenden kontrollierten
Tree-Fehlerpfad. Es werden keine Quellen oder Objekte verändert.

Kachel und IPSView verwenden dieselben Regeln und denselben Zustand, können
aber ihre drei Zustandsfarben getrennt gestalten. Der Tooltip benennt den
Zustand; die Formularvorschau zeigt ihn ebenfalls schematisch oder aus den
sichtbaren Objekten. Leere `StatusRules` erhalten Modell, Typfarben und
Verhalten bestehender Instanzen unverändert. Eine Migration ist nicht nötig.

## Grenzen und Prüfung

Die Funktion erzeugt keine fachliche Gebäude-/Raumhierarchie unabhängig vom
Symcon-Objektbaum und nimmt keine Aktor-Schreibaktionen vor. Für fachliche
Gruppen außerhalb des Objektbaums, Querverbindungen, gewichtete Flächen und
Flüsse sind eigene Entscheidungen erforderlich. Tests prüfen beide
Schwellenrichtungen, gleichnamige Variablen, Nullregeln, Aggregation,
ungültige Regeln, Ausgabe und Renderer. Reale Kachel- und IPSView-Darstellung
bleiben bis zur Prüfung auf der Testinstallation offen.
