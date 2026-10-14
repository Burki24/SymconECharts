# EChartsGraph

`EChartsGraph` zeigt frei ausgewählte IP-Symcon-Objekte und ausdrücklich
konfigurierte Verbindungen als interaktiven Apache-ECharts-Graphen. Benötigt
werden IP-Symcon 9.0/9.1 und ein verbundenes, aktives `EChartsGateway`.
Ein Archiv ist nicht erforderlich.

Wählen Sie 2 bis 80 Kategorien, Instanzen oder Variablen als Knoten aus und
verbinden Sie diese mit 1 bis 200 Kanten. Beide Enden einer Kante müssen in
der Knotenliste stehen. Selbstverbindungen und doppelte Verbindungen – auch
in umgekehrter Reihenfolge – sind ungültig. Die Kanten sind ungerichtet;
Symcon-Objektbeziehungen werden nicht automatisch zu Verbindungen. Der
optionale Anzeigename eines Knotens überschreibt dessen aktuellen
Symcon-Namen. Die Objekt-ID bleibt der technische Schlüssel, sodass
gleichnamige Objekte getrennte Knoten bleiben.

Der Tooltip einer Variablen zeigt ihren aktuellen formatierten Wert. Auf
Wunsch erscheint er zusätzlich an der Knotenbeschriftung. Namen und Werte
werden mindestens alle 60 Sekunden neu gelesen. Das Modul liest lediglich
Objekte und Werte; es ändert sie nicht.

Für Kachel und optionales IPSView-HTML-Widget stehen getrennte Designer mit
kräftebasierter oder kreisförmiger Anordnung, Verschieben/Zoomen,
Knotengröße, Beschriftung, Farben, Theme und Animation bereit. Standardmäßig
übernimmt IPSView das Kacheldesign. Beide Designer zeigen eine unmittelbar
auf Formularänderungen reagierende schematische Vorschau. Bei ungültigen
oder zu umfangreichen Entwürfen ist die Vorschau ausdrücklich als Beispiel
gekennzeichnet.

Für IPSView aktivieren Sie die optionale WebContent-Ausgabe und platzieren
die erzeugte Variable in einem HTML-Widget. Änderungen aktualisieren das
bestehende Diagramm, ohne das WebContent-Dokument neu zu laden.

`EChartsGraph` liest keine Zigbee-Netzwerkkarte. Die dafür vorgesehene
eigene Instanz `EChartsGraphZ2M` gehört nicht zu diesem Modul.
