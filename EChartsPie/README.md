# EChartsPie

## Beschriftungsoptionen

Außenbeschriftungen lassen sich natürlich, entlang der Führungslinie oder am Containerrand ausrichten. Die beiden Linienlängen, der Textabstand zur Linie und der Randabstand sind getrennt einstellbar. Innenbeschriftungen bleiben davon unberührt; die bisherige Randausrichtung ist der Standard.

Die Optionen gelten für Kacheln und – bei unabhängigem Design – auch für
IPSView. Bei geerbtem Design folgt IPSView den Kacheleinstellungen.

`EChartsPie` vergleicht Anteile aktueller numerischer Symcon-Werte als Kreis,
Donut oder Nightingale-Rose. Für alle ECharts-Diagramme genügt ein gemeinsames
`EChartsGateway`; bei weiteren Instanzen wählen Sie das vorhandene Gateway.
Ein Archiv ist nicht erforderlich.

## Quellen

Wählen Sie 1 bis 16 eindeutige Integer- oder Float-Variablen. Die
Variablen-ID dient ausschließlich der internen Zuordnung. Ein ausdrücklich
konfigurierter Anzeigename hat Vorrang, sonst erscheint der aktuelle
Symcon-Name. Gleiche sichtbare Namen bleiben getrennte Sektoren; IDs werden
in der Grafik nicht angezeigt. Alle Quellen müssen dieselbe wirksame Einheit
verwenden. Einheit und Nachkommastellen können aus der Variablendarstellung
übernommen oder je Quelle gesetzt werden; jede Quelle kann eine eigene Farbe
erhalten.

Negative Werte sind für Anteile nicht sinnvoll und werden nicht stillschweigend
in positive Werte umgerechnet: Das Diagramm zeigt dann einen Fehler. Bei einer
Summe von null zeigt es „Keine positiven Werte“ statt gleich großer
Scheibensegmente. Nullwerte bleiben als Quellen erhalten, besitzen aber keinen
sichtbaren Sektor.

## Design und Ausgabe

Der Kacheldesigner bietet Kreis, Donut und Nightingale-Rose sowie Sortierung,
Innenradius in Prozent des Außenradius, Außenradius, Legende, Beschriftungen, Prozentwerte, Schriftgrößen,
Farben, Theme und Start-/Aktualisierungsanimation. Bei der Nightingale-Rose
haben die Sektoren gleiche Winkel und linear wertabhängige Radien. Die Legende
steht in mittelgroßen Kacheln unter dem Diagramm, in breiten Ansichten daneben.
Kreis und Außenbeschriftungen lassen Platz für Legende und Kachelkopf. In sehr
kleinen Kacheln wird die Legende zugunsten der Grafik ausgeblendet; Werte und
Namen bleiben über den Tooltip erreichbar.

Kachel und optionales IPSView-HTML-Widget verwenden dieselben Quellen.
IPSView übernimmt zunächst das Kacheldesign; bei deaktiviertem
„Kacheldesign verwenden“ ist ein eigenständiger Designer aktiv. Der
Kopierknopf überträgt die gespeicherten Kacheleinstellungen einmalig.
Der IPSView-Hintergrund ist separat einstellbar. Die HTML-Variable
`IPSViewPie` wird nur bei aktivierter IPSView-Ausgabe bereitgestellt.

Beide Designer enthalten eine SVG-Live-Vorschau, die auf ungespeicherte
Formularänderungen reagiert. Ohne gültige Quellen nutzt sie gekennzeichnete
Beispieldaten; bei mehr als sechs Quellen zeigt sie nur die ersten sechs und
nennt die Gesamtzahl. Die Vorschau ist schematisch und ersetzt keine Prüfung
der responsiven ECharts-Kachel oder des IPSView-Widgets.

Vorübergehende Gateway-Ladefehler lassen den letzten gültigen Chart zunächst
stehen; nach wiederholtem Fehler erscheint ein Hinweis und nach 60 Sekunden
wird erneut gelesen. Die öffentliche Funktion `ECPI_GetPieData($InstanzID)`
liefert das aktuelle Modell als JSON und verändert keine Quellvariable.
