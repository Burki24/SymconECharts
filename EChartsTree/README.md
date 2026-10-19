# EChartsTree

`EChartsTree` zeigt einen ausgewählten Teil des IP-Symcon-Objektbaums als
interaktiven Apache-ECharts-Baum in einer nativen Kachel oder einem optionalen
IPSView-HTML-Widget. Benötigt werden IP-Symcon 9.0/9.1 und ein verbundenes,
aktives `EChartsGateway`. Ein Archiv ist nicht erforderlich.

Wählen Sie als Wurzel eine Kategorie oder Instanz. Für den gesamten Objektbaum
aktivieren Sie stattdessen „Symcon-Wurzel verwenden“; das Objekt-Auswahlfeld
wird dann deaktiviert. Eine dort zuvor gewählte Kategorie oder Instanz bleibt
gespeichert und wird beim Ausschalten der Option wieder verwendet. Ohne diese
Option und ohne ausgewähltes Objekt bleibt die Instanz unkonfiguriert.
Unter der aktiven Wurzel werden Kategorien, Instanzen und Variablen in der
Symcon-Reihenfolge aufgenommen; Skripte, Links,
Medien und andere Objekttypen bleiben außen vor. Ausgeblendete Objekte und
deren Zweige erscheinen standardmäßig nicht. „Ausgeblendete Objekte
einbeziehen“ hebt diesen Filter auf. Maximale Tiefe und Knotenzahl begrenzen
die Darstellung auf 1–12 Nachkommensebenen beziehungsweise 10–3000 Knoten.
Die gewählte Wurzel zählt als Ebene 0. Bestehende Instanzen behalten ihre
gespeicherten Grenzen; der Standard bleibt drei Nachkommensebenen und 80 Knoten.
Wenn die Knotengrenze weitere passende Objekte abschneidet, zeigt das
Diagramm eine Meldung. Beide Ausgabewege verwenden dieselbe Wurzel und
dieselben Grenzen.

Mit „Variablenname enthält“ lässt sich der Baum auf Variablen mit einem
bestimmten Namensbestandteil einschränken, beispielsweise „Batterie“. Die
Suche unterscheidet nicht zwischen Groß- und Kleinschreibung. Sichtbar bleiben
die Treffer sowie ihre übergeordneten Kategorien und Instanzen; andere
Variablen und leere Zweige entfallen. Bei keinem Treffer bleibt nur die
gewählte Wurzel sichtbar. Ein leeres Feld zeigt den bisherigen vollständigen
Teilbaum. Der Filter respektiert Tiefe und ausgeblendete Objekte. Die
Knotengrenze gilt für das gefilterte Ergebnis, nicht für die zuerst
durchsuchten Objekte. Für sehr große Hierarchien ist der Suchdurchlauf auf
30 000 Objekte begrenzt; wird er überschritten, wählen Sie eine engere Wurzel
oder geringere Tiefe.

Jeder Knoten trägt seinen aktuellen Symcon-Namen. Bei Variablen zeigt der
Tooltip zusätzlich den formatierten aktuellen Wert; dieser kann optional auch
an der Beschriftung stehen. Gleiche Variablennamen werden nicht
zusammengeführt. Änderungen an Namen, Struktur und Werten werden spätestens
mit der nächsten Aktualisierung nach 60 Sekunden sichtbar. Die Abfrage liest
Objekte und Werte ausschließlich; sie verändert die ausgewählte Hierarchie
nicht.

Für eine Bewertung ausgewählter Zustandsvariablen mit Warn- und Kritisch-Schwellen
verwenden Sie das separate Modul [`EChartsTreeSystemHealth`](../EChartsTreeSystemHealth/README.md).

Der Kacheldesigner bietet orthogonale oder radiale Anordnung. Für orthogonale
Bäume sind vier Richtungen verfügbar, für die Verbindungslinien Kurven oder
Polylinien. Anfangs geöffnete Ebenen, Verschieben/Zoomen, Knotengröße,
Beschriftung, Farben, Theme und Animation lassen sich einstellen. Knoten
können in der Laufzeitansicht per Klick auf- und zugeklappt werden.
Anfänglich geöffnete Ebenen (0–12) sind unabhängig von der maximal geladenen
Tiefe: Eingeklappte Zweige können später geöffnet werden, ohne die Auswahl
zu verändern. Geöffnete Zweige und Pan/Zoom bleiben bei Aktualisierung und
Größenänderung erhalten; eine neue Wurzel, Anordnung oder Anfangstiefe setzt
die Ansicht zurück. Lange Namen bleiben vollständig im Tooltip erreichbar.
Der unabhängige IPSView-Designer besitzt dieselben Optionen; standardmäßig
übernimmt er das Kacheldesign. Beide Designer zeigen eine unmittelbar auf
Formularänderungen reagierende schematische Vorschau. Ohne gültige Wurzel
verwendet diese ausdrücklich eine Beispielstruktur.
Die Vorschau zeigt echte Eltern-Kind-Verbindungen mit höchstens 80 Knoten
und berücksichtigt die anfänglich geöffneten Ebenen. Sie ist eine begrenzte
Struktur-/Designvorschau, keine vollständige Zustands- oder Objektübersicht.

Für IPSView aktivieren Sie die optionale WebContent-Ausgabe und platzieren die
erzeugte Variable in einem HTML-Widget. Das WebContent-Dokument wird bei
Datenaktualisierungen nicht neu geladen. Der Baum und das Design werden im
bestehenden Diagramm aktualisiert.
