# EChartsTree

`EChartsTree` zeigt einen ausgewählten Teil des IP-Symcon-Objektbaums als
interaktiven Apache-ECharts-Baum in einer nativen Kachel oder einem optionalen
IPSView-HTML-Widget. Benötigt werden IP-Symcon 9.0/9.1 und ein verbundenes,
aktives `EChartsGateway`. Ein Archiv ist nicht erforderlich.

Wählen Sie als Wurzel eine Kategorie oder Instanz. Darunter werden Kategorien,
Instanzen und Variablen in der Symcon-Reihenfolge aufgenommen; Skripte, Links,
Medien und andere Objekttypen bleiben außen vor. Ausgeblendete Objekte und
deren Zweige erscheinen standardmäßig nicht. „Ausgeblendete Objekte
einbeziehen“ hebt diesen Filter auf. Maximale Tiefe und Knotenzahl begrenzen
die Darstellung auf 1–6 Nachkommensebenen beziehungsweise 10–500 Knoten.
Wenn die Knotengrenze weitere passende Objekte abschneidet, zeigt das Diagramm eine
Meldung. Beide Ausgabewege verwenden dieselbe Wurzel und dieselben Grenzen.

Jeder Knoten trägt seinen aktuellen Symcon-Namen. Bei Variablen zeigt der
Tooltip zusätzlich den formatierten aktuellen Wert; dieser kann optional auch
an der Beschriftung stehen. Gleiche Variablennamen werden nicht
zusammengeführt. Änderungen an Namen, Struktur und Werten werden spätestens
mit der nächsten Aktualisierung nach 60 Sekunden sichtbar. Die Abfrage liest
Objekte und Werte ausschließlich; sie verändert die ausgewählte Hierarchie
nicht.

Der Kacheldesigner bietet orthogonale oder radiale Anordnung. Für orthogonale
Bäume sind vier Richtungen verfügbar, für die Verbindungslinien Kurven oder
Polylinien. Anfangs geöffnete Ebenen, Verschieben/Zoomen, Knotengröße,
Beschriftung, Farben, Theme und Animation lassen sich einstellen. Knoten
können in der Laufzeitansicht per Klick auf- und zugeklappt werden.
Der unabhängige IPSView-Designer besitzt dieselben Optionen; standardmäßig
übernimmt er das Kacheldesign. Beide Designer zeigen eine unmittelbar auf
Formularänderungen reagierende schematische Vorschau. Ohne gültige Wurzel
verwendet diese ausdrücklich eine Beispielstruktur.

Für IPSView aktivieren Sie die optionale WebContent-Ausgabe und platzieren die
erzeugte Variable in einem HTML-Widget. Das WebContent-Dokument wird bei
Datenaktualisierungen nicht neu geladen. Der Baum und das Design werden im
bestehenden Diagramm aktualisiert.
