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
oder Instanz anhand ihrer Objekt-ID als Wurzel oder aktiviert ausdrücklich
`UseSymconRoot` für den gesamten Symcon-Objektbaum. Diese zusätzliche Property
ist standardmäßig `false`: Eine bisher nicht gewählte `RootObjectID = 0` wird
dadurch nicht stillschweigend zur gültigen Wurzel. Bei aktivierter Option
verwendet die Projektion intern die Symcon-Root-ID `0` und eine benannte
Wurzel „Symcon“; eine zuvor gewählte positive Objekt-ID bleibt gespeichert.
Die Auswahl erfasst darunter lesend Kategorien, Instanzen und Variablen in
der von Symcon gelieferten Kinderreihenfolge. Andere Objekttypen,
insbesondere Links und Skripte, werden
nicht als Knoten oder alternative Quellen aufgelöst. Ein ausgeblendeter Zweig
entfällt standardmäßig insgesamt; seine Einbeziehung ist optional. Die
konfigurierte maximale Tiefe liegt zwischen 1 und 6, die Knotengrenze
zwischen 10 und 3000. Die Obergrenze von 3000 wird zunächst im Testbetrieb
erprobt; sie ist kein zugesicherter Leistungswert für jede Kachelgröße.
Erreicht die Traversierung die Knotengrenze vor weiteren passenden Objekten,
wird die Kürzung sichtbar gemeldet. Die Tiefengrenze ist
hingegen eine bewusst gewählte Ansicht und keine Fehlermeldung.

Ein optionaler Filter `VariableNameFilter` sucht ohne Beachtung der
Groß-/Kleinschreibung nach einem Teilstring im sichtbaren Variablennamen.
Nur passende Variablen und ihre übergeordneten Kategorien oder Instanzen
werden ausgegeben; die Wurzel bleibt auch ohne Treffer erhalten. Ein leerer
Filter wahrt den bisherigen Datenvertrag. Tiefe und Sichtbarkeit gelten
weiterhin. Bei aktivem Filter wird vor der Ausgabegrenze gesucht, damit
Treffer hinter den ersten 3000 Objekten nicht verloren gehen. Der Suchlauf
selbst ist auf 30 000 besuchte Objekte begrenzt und meldet bei Überschreitung
einen Fehler statt stillschweigend unvollständige Treffer auszugeben.
Die Objekt-ID bleibt die Identität; Namen dienen nur als Auswahlkriterium.

Die Objekt-ID ist die alleinige technische Identität eines Knotens; die
synthetisch benannte Symcon-Wurzel verwendet intern `object-0`.
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
Objektstruktur bleibt familienbezogen. Seit der Modultrennung gemäß
[`ADR 0046`](0046-tree-system-health-module-split.md) nutzen beide
Tree-Module die gemeinsame Projektion in `libs/TreeHierarchy.php`.
Große Startzustände werden nur innerhalb der Tree-HTML-Seite gzip-komprimiert
eingebettet und im Browser vor dem ersten Rendern entpackt. Das öffentliche
Tree-Datenmodell und spätere Aktualisierungen bleiben unverändert. Für diesen
Startpfad benötigt der Browser `DecompressionStream`; fehlt die API, erscheint
ein kontrollierter Ladefehler.
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
