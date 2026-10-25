# ADR 0084: Parallel mit 16 Achsen und horizontaler Ansichtsverschiebung

- Status: Angenommen
- Datum: 2026-10-21
- Entscheider: Burki24
- Ergänzt: ADR 0050, 0061, 0078 und 0036

## Entscheidung und Kompatibilität

EChartsParallel erweitert `Sources` von 2–10 auf 2–16 eindeutige numerische
Quellvariablen. GUID, Präfix, Reihenfolge, Label-/Einheitenauflösung und alle
Property-Namen/Defaults bleiben erhalten. Es gibt weder Migration noch neue
persistente Ansichtsproperties. Die gespeicherten Designs für Kachel und IPSView
bleiben unabhängig. Individuelle Achsenbereiche und Kennzahlen sind ausdrücklich
noch nicht Teil dieses Schritts.

## Gemeinsame Datenplanung und Ladebudget

`LineBudget` bleibt 1–1000, Default 750. Die zentrale `EChartsArchiveQuery` erhält
vor sämtlichen Abfragen `min(LineBudget × Achsenanzahl, 10000)` als Punktbudget.
Die vorhandene gleichmäßige Verteilung begrenzt jede Achse auf den abgerundeten
Anteil, maximal die konfigurierte Linienzahl: bei 11/13/15/16 Achsen höchstens
909/769/666/625 Aggregate. Restpunkte werden nicht nachträglich verteilt, auch
nicht zugunsten gerade sichtbarer oder lückenfreier Achsen. Unterhalb von elf
Achsen bleiben die bisherigen Ladelimits unverändert.

Dasselbe gilt für Stunden/Tage, bisherige/benutzerdefinierte/feste Zeiträume.
Die letzte Achse wird ebenso budgetiert und geprüft wie die erste. Eine Antwort
mit mehr Punkten als angefordert oder ohne Listenform wird vor der Paarung
abgewiesen. Native Gateway-Kürzung bleibt ausdrücklich zulässig und sichtbar;
es werden weiterhin die jüngsten gemeinsamen Intervalle dargestellt.

`ParallelPairing` behält seine exakte Schnittmenge aller Achsen ohne
Interpolation, Nullfüllung oder Ersetzung durch aktuelle Werte. Auch nicht
sichtbare Achsen bestimmen Lücken. Standardarchive verwenden weiterhin native
Mittelwerte, Zähler native Verbräuche; es gibt keine lokale Differenzbildung.

Schema 1 bleibt erhalten. Alte Listen behalten auch ihre bisherigen `range`-
Felder; ab elf Achsen kommen `effectiveLineBudget` und `aggregateBudget` additiv
hinzu. Konfigurierte und wirksame Grenzen sind damit getrennt, ohne Properties
zurückzuschreiben. Modell-/HTML-Ausgaben bleiben durch die vorhandenen Grenzen
geschützt und werden mit dem vollständigen 10000er-Datenmodell geprüft.

## Vollständige native Koordinate statt Dimensionsauswahl

Alle Achsen und jede vollständige Wertezeile bleiben im nativen Parallel-Chart.
Ein horizontaler DOM-Viewport zeigt einen Teil des breiteren Canvas. Es werden
weder Dimensionen entfernt noch Zeilen nach Sichtbarkeit gefiltert oder Skalen
aus Ausschnitten neu berechnet. Zwei-Achsen-Vergleiche bleiben gemeinsam sichtbar.
Bei größeren Listen berücksichtigen Breite und Randabstände Achsentitel und
Schriftgröße; überlange Namen werden abgekürzt, im Tooltip aber vollständig
erhalten. Tooltip-Position und -Größe sind auf den sichtbaren Bereich begrenzt.

Die lokal gepinnte ECharts-6.1.0-Runtime bleibt unverändert. Als Referenzen wurden
die offiziellen versionierten Quellen
[ParallelModel](https://github.com/apache/echarts/blob/6.1.0/src/coord/parallel/ParallelModel.ts),
[Parallel](https://github.com/apache/echarts/blob/6.1.0/src/coord/parallel/Parallel.ts)
und [ParallelView](https://github.com/apache/echarts/blob/6.1.0/src/component/parallel/ParallelView.ts)
geprüft. Insbesondere wird das dort als noch nicht für Benutzer freigegeben
gekennzeichnete `axisExpandWindow` nicht zu einem eigenen Modulvertrag gemacht.
Keine neue Abhängigkeit, Runtime-Aktualisierung oder Änderung am Fremdcode.

Die vorhandene `SymconEChartsCategoryNavigation` hält den variablen-ID-stabilen
Achsenanker. Nur Pixelgeometrie und Bruchteil innerhalb eines Achsenabstands
bleiben familienlokal. Datenrefresh, Größen-/Schriftänderung und Quellenumordnung
erhalten die Ansicht; eine zwischenzeitlich vollständig sichtbare Darstellung
löscht den Anker nicht. Navigation ist browserlokal, nicht gespeichert.

Native Scrollleiste/Wischgeste, vorherige/nächste Achse und Tastatur
(Links/Rechts, Bild auf/ab, Pos1/Ende) teilen denselben Scrollmechanismus.
Die Positionsanzeige nennt sichtbare Achsen und die wirksame Liniengrenze.
Beim Scrollen wird nur die Titelposition aktualisiert. Native Achsenmarkierungen
bleiben bestehen; der Diagrammtitel bleibt unterhalb des Symcon-Kachelkopfs
zentriert sichtbar. Alle Texte sind lokalisierbar, Scrollleisten folgen dem Theme.

Beide SVG-Entwurfsvorschauen bleiben reine Beispieldarstellungen ohne Archivzugriff.
Bei mehr als sechs Achsen kennzeichnen Metadaten und Beschriftung den Sechs-
Achsen-Ausschnitt. Die gespeicherte Quellenliste wird nicht verkürzt.

## Nachweise und Roadmap

`parallel_many.php` begann RED an der alten Achsengrenze. Der echte Modul-/
Gateway-Pfad mit Kernel-Doppeln prüft alle relevanten Verteilungen bis 16×625,
kleinere Nutzerlimits, Lücken auf der letzten Achse, Tagesintervalle, gemischte
Einheiten, Zähler, ungültige Listen und zu große Antworten. Wiederholung,
getrennte Designs, veröffentlichte WebContent-Ausgabe und reine Vorschauen sind
enthalten. Maximale Unicode-Labels und 10000 große endliche Werte sichern beide
Ausgabebudgets. Alte absolute Tests behalten insbesondere 10×1000 als Regression.

`parallel_layout.js` begann RED wegen des fehlenden breiten Viewports. Es prüft
Steuerung, ID-stabile Anker, Resize, volle Daten, Fehlerschutz und die echte
gepinnte ECharts-Geometrie einschließlich Skalen/Markierungen und Titelposition.
Die lokale Canvas-Fixture prüft beide Ausgabeadapter bei 400×520/790×440 sowie
200-%-Schrift, Achsenknöpfe und Pos1/Ende. Das ist keine reale IPSView-Abnahme.

Heatmap ist inzwischen per MCP funktional geprüft und durch den Eigentümer in
Kachel/IPSView bestätigt. Auch die 16-Achsen-Ansicht ist inzwischen funktional per MCP geprüft und vom
Eigentümer in Kachel/IPSView bestätigt. Die später ergänzten individuellen
Achsenbereiche und Kennzahlen je Achse sind ebenfalls umgesetzt und abgenommen. Bestehende
gemeinsame Intervalle und Ressourcenbudgets bleiben dafür Voraussetzung.
