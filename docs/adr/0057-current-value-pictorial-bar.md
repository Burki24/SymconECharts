# ADR 0057: PictorialBar mit aktuellen Variablenwerten

- Status: Angenommen
- Datum: 2026-10-17
- Ergänzt: ADR 0001, ADR 0002, ADR 0005, ADR 0017, ADR 0036 und ADR 0037

## Entscheidung

`EChartsPictorialBar` erhält Präfix `ECPB` (Pictorial Bar) und GUID `{CF6B1F8C-7219-4D59-94EA-19CF7570637F}`.
Es ist eine eigene Diagrammfamilie, kein Modus von Category Bar oder Polar Bar.
1–16 manuell gewählte numerische Quellen derselben Einheit werden über ihre
IDs identifiziert. Labels und Farben sind optional. Doppelte Namen sind
zulässig, doppelte IDs nicht. Alle Werte müssen endlich und nichtnegativ
sein; Null bleibt ein gültiger sichtbarer Wert.

## Daten und Wiederverwendung

Funnel belegt denselben aktuellen Quellenvertrag. `EChartsCurrentSources`,
`EChartsSourceIdentity` und `EChartsVariablePresentation` übernehmen unverändert
Gateway-Zugriff, Referenzen, VM_UPDATE-Abonnements, Identität und Formatierung.
Formular-, SVG-, Theme-, Animations- und IPSView-Bausteine werden wie bei den
anderen Familien verwendet. Geometrie, Vorschau und Validierung bleiben in
der neuen Familie. Es gibt keine Archive, Aggregate, Skriptausführung oder
Zustandsbewertung und keine Änderung bestehender Quellwerte.

Die Listenreihenfolge kann beibehalten oder stabil nach Wert sortiert werden.
Jede Quelle behält dabei ihre Identität und Farbe. Ein gemeinsames Maximum
von 0 bedeutet automatische Skalierung; positive Maxima sind eine rein
optische Begrenzung. Oberhalb davon bleibt der Originalwert in Modell,
Wertbeschriftung und Tooltip erhalten. Nullwerte zeichnen kein Symbol,
behalten aber Kategorie und Wertbeschriftung. Bei ausschließlich Nullen
verwendet die Skala 1 als technische Obergrenze.

## Darstellung und Ressourcen

Die erste Version bietet rect, roundRect, circle, diamond und triangle.
Externe Bilder, freie Pfade und SVG-Importe benötigen einen eigenen
abgesicherten Vertrag und sind hier ausgeschlossen. Ein Symbol kann an die
Balkengeometrie angepasst oder entlang der Werteachse wiederholt werden.
Wiederholungen verwenden ein gemeinsames BoundingData-Maximum und Clipping.
Die Zahl der Symbole ist eine proportionale Darstellung und bedeutet nicht
„ein Symbol = eine Einheit“. Bis zu 128 Symbole je Quelle begrenzen die
Zeichenlast; bei großen Ansichten vergrößert sich nötigenfalls die Symbolhöhe.

Die Ausgabe verwendet den unveränderten gepinnten Apache ECharts 6.1.0 mit
PictorialBarChart, Grid, Title, Tooltip, Aria und CanvasRenderer als separates
Subset mit LabelLayout für den Beschriftungsschutz. Andere Runtimes und das Gateway werden nicht vergrößert.
Die nativen Kacheln schützen den 58-px-Kopfbereich; Diagrammtitel stehen
darunter. Beide Designer erhalten eigene Live-Vorschauen und Vererbung.
Vorschauen lesen höchstens 16 aktuelle Werte; ungültige Entwürfe erzeugen
keine erfundenen Ersatzwerte. Nur die leere Konfiguration zeigt Beispiele.

## Prüfung

Vertrags- und Gateway-Integration prüfen positive/Null-/negative/ungültige
Werte, Quellidentität, Einheiten, Abonnements, Aktualisierung, Fehlererholung,
Designvererbung und Output-Limit. Renderer-Tests verwenden die echte
ECharts-Engine für Formen, Wiederholung, beide Ausrichtungen, Nullwerte,
Sortierung und optische Begrenzung. Lokale Browserprüfungen verwenden
synthetische Daten. Live-Kachel und IPSView bleiben nach dem Modulupdate
durch den Eigentümer separat abzunehmen.

## Grundlagen

Am 17.10.2026 wurden die offiziellen Einstiege
[Startseite](https://echarts.apache.org/en/index.html),
[llms.txt](https://echarts.apache.org/en/llms.txt) und
[PictorialBar](https://echarts.apache.org/en/option.html#series-pictorialBar)
sowie die unveränderte npm-Runtime geprüft. Lizenz und Herkunft bleiben
Apache-2.0 gemäß THIRD_PARTY_NOTICES.md.
