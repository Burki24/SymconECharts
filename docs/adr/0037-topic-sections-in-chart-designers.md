# ADR 0037: Fachliche Unterbereiche in allen Diagramm-Designern

- Status: Angenommen
- Datum: 2026-10-11
- Entscheider: Burki24
- Ergänzt: ADR 0006 und ADR 0036
- Ersetzt durch: –

## Kontext

Gauge Single zeigt seine vielen Kacheleinstellungen bereits in benannten,
einklappbaren Themenbereichen. Andere Kachel- und unabhängige IPSView-Designer
haben teils nur wenige Bereiche oder eine lange, flache Folge von Feldern.
Gleiche Aufgaben sind dadurch in verschiedenen Diagrammen unterschiedlich
leicht auffindbar. Die Designer besitzen aber fachlich verschiedene Optionen;
ein identischer Satz von Rubriken wäre irreführend.

## Entscheidung

Jedes bestehende und künftige Diagrammgerät gliedert **beide** Designer in
benannte, standardmäßig eingeklappte `ExpansionPanel`-Unterbereiche. Jede
Rubrik bündelt zusammengehörige Einstellungen wie Geometrie, Beschriftung,
Farben, Skalen oder Effekte. Die konkreten Rubriken und ihre Reihenfolge
werden pro Diagrammfamilie festgelegt; ein globales Schema der Überschriften
oder der zugehörigen Properties gibt es nicht. Kleine, übergreifende
Einstiegseinstellungen wie Animation, Vorlage und Theme dürfen oberhalb der
Rubriken direkt sichtbar bleiben. Die Live-Vorschau bleibt am Ende des
jeweiligen Designers unmittelbar erreichbar.

Der unabhängige IPSView-Designer verwendet dieselben fachlichen Rubriken und
dieselbe Reihenfolge wie der Kacheldesigner. IPSView-Ausgabe, Hintergrund und
Vererbung bleiben vor den Rubriken. Im vererbten Modus bleiben sämtliche
unabhängigen Eingaben einschließlich derjenigen in eingeklappten Bereichen
deaktiviert; der Kopierknopf und die Vorschau behalten ihre bisherige Wirkung.
Eine Rubrik ändert weder gespeicherte Properties noch deren Sichtbarkeit,
Validierung, Änderungsaktionen oder die Bedeutung der Vorschau.

Nur das wiederkehrende, rein strukturelle Einhängen vorhandener Felder darf
in `libs` liegen. Die Zuordnung von Rubriken und Feldern bleibt im jeweiligen
Gerätemodul. Der allgemeine Symcon-Formularstandard in `SymconDevelopment`
und die Grundreihenfolge samt Live-Vorschau aus ADR 0036 gelten weiter.
`EChartsGateway` hat keinen Diagramm-Designer und ist ausgenommen.

## Umsetzung und Prüfung

Die vorhandene `form.json` bleibt Ausgangsbasis. Die Module ergänzen ihren
bereits dynamischen Formularaufbau nach der IPSView-Vererbungslogik um die
lokal festgelegten Rubriken. Das gemeinsame Einhängen bewahrt Feldobjekte
unverändert, bricht bei fehlenden oder vertauschten Ankerfeldern mit einem
klaren Fehler ab und lässt bestehende Gruppen sowie die Vorschau außerhalb
neu eingefügter Bereiche. Es erzeugt keine persistente Konfiguration.

Vertragstests prüfen für alle neun Diagrammgeräte Rubriken, Reihenfolge und
eingeklappten Ausgangszustand in Kachel und IPSView. Bestehende Modul- und
Vorschautests prüfen die verschachtelten Felder sowie Vererbung, Sichtbarkeit
und Änderungsaktionen. Ein realer Symcon-Formulartest bleibt für die
Darstellung und Bedienbarkeit der Panels nötig; eine reine JSON-Prüfung
ersetzt ihn nicht.

## Folgen

Die Designer sind konsistent gegliedert, ohne Diagrammfamilien in ein
universelles Feldschema zu zwingen. Neue Optionen werden in ihrer fachlich
passenden Rubrik ergänzt. Eine weitere Verschachtelungsebene ist nur bei
nachgewiesenem Bediennutzen vorgesehen.
