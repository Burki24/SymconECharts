# ADR 0044: Tree als begrenzte Symcon-Objekthierarchie

- Status: Angenommen
- Datum: 2026-10-13
- Entscheider: Burki24
- Ergänzt: ADR 0001, ADR 0017, ADR 0036 und ADR 0037

## Kontext

Nach der Wochentag-Stunden-Heatmap soll Apache ECharts Tree als nächste
Diagrammfamilie erschlossen werden. Der Eigentümer hat eine vom Anwender
ausgewählte IP-Symcon-Objekthierarchie mit Kategorien, Instanzen und Variablen
als ersten Datenvertrag bestätigt. Das ist eine Hierarchie aus Eltern-/Kind-
Beziehungen, nicht ein frei verknüpftes Netzwerk; Graph und die weiteren
Familien bleiben eigene Roadmap-Einträge.

## Entscheidung

Ein eigenes Gerätemodul `EChartsTree` nutzt Präfix `ECTR` und die Modul-GUID
`{2B2C26A3-6F92-48D0-ADE1-5F03D7282EF8}`. Der Anwender wählt eine Kategorie
oder Instanz anhand ihrer Objekt-ID als Wurzel. Die Auswahl erfasst darunter
lesend Kategorien, Instanzen und Variablen in der von Symcon gelieferten
Kinderreihenfolge. Andere Objekttypen, insbesondere Links und Skripte, werden
nicht als Knoten oder alternative Quellen aufgelöst. Ein ausgeblendeter Zweig
entfällt standardmäßig insgesamt; seine Einbeziehung ist optional. Die
konfigurierte maximale Tiefe liegt zwischen 1 und 6, die Knotengrenze
zwischen 10 und 200. Erreicht die Traversierung die Knotengrenze vor weiteren
passenden Objekten, wird die Kürzung sichtbar gemeldet. Die Tiefengrenze ist
hingegen eine bewusst gewählte Ansicht und keine Fehlermeldung.

Die Objekt-ID ist die alleinige technische Identität eines Knotens.
Variablennamen, Elternnamen und Pfade werden nicht als Schlüssel verwendet;
gleichnamige Variablen bleiben getrennt. Der aktuelle formatierte Wert ist
nur Anzeigeinhalt einer Variablen, keine Gewichtung der Baumgeometrie.
Objektstruktur und Werte werden mindestens alle 60 Sekunden neu gelesen;
die gewählte Wurzel wird niemals verändert. Beim Entfernen oder Verschieben
der Wurzel meldet die Instanz eine ungültige Konfiguration. Teilfehler beim
Lesen erzeugen einen kontrollierten Fehlerzustand statt eines stillschweigend
unvollständigen Baums.

Tree nutzt den bestehenden Gateway-Parent, den gemeinsamen HTML-/IPSView-
Transport, die Theme- und Animationsbausteine, die read-only-Vorschau-
Verdrahtung und die Designer-Unterbereiche. Die Projektion der Symcon-
Objektstruktur bleibt familienbezogen in `EChartsTree/TreeHierarchy.php`.
Keine synchronisierten Helper werden verändert. Ein separater lokaler
ECharts-6.1.0-Build enthält `TreeChart`, Title, Tooltip, Aria und Canvas;
andere Runtime-Bundles bleiben unverändert. Orthogonale und radiale Layouts,
vier orthogonale Richtungen, Kurven/Polylinien, Auf-/Zuklappen sowie
Pan/Zoom gehören zum ersten Designvertrag. Formularvorschauen sind
schematische SVGs; eine reale Kachel- und IPSView-Prüfung folgt nach dem
Modulupdate.

## Abgrenzung und Nachweise

Dies ist weder ein generischer Objektbrowser mit Schreibaktionen noch eine
Graph-, Treemap- oder Sunburst-Datenmodellierung. Frei gewählte Einzelknoten,
Links als Quellauflösung und weitere fachliche Gewichtungen benötigen eigene
Entscheidungen. Tests prüfen Tiefe, Begrenzung, ausgeblendete Zweige,
gleichnamige Variablen, Modell und Ausgabegrenze, Designvererbung, sichere
Tooltips und die ECharts-Optionen. Symcon- und IPSView-Laufzeitdarstellung
bleiben bis zur Prüfung auf der Testinstallation offen.
