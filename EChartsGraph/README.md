# EChartsGraph

`EChartsGraph` zeigt frei ausgewählte IP-Symcon-Objekte und ausdrücklich
konfigurierte Verbindungen als interaktiven Apache-ECharts-Graphen. Benötigt
werden IP-Symcon 9.0/9.1 und ein verbundenes, aktives `EChartsGateway`.
Ein Archiv ist nicht erforderlich.

Wählen Sie 2 bis 300 Kategorien, Instanzen oder Variablen als Knoten aus und
verbinden Sie diese mit 1 bis 1.000 Kanten. Beide Enden einer Kante müssen in
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
auf Formularänderungen reagierende schematische Vorschau. Große gültige
Entwürfe zeigen höchstens 40 echte Knoten und 80 Verbindungen, deren beide
Enden im Ausschnitt liegen. Die Vorschau nennt dargestellte und gesamte
Anzahlen; die Live-Darstellung enthält weiterhin sämtliche konfigurierten
Knoten und Verbindungen. Ungültige Entwürfe verwenden ein gekennzeichnetes
Beispielnetzwerk.

Bei dichten Graphen werden überlappende Beschriftungen ausgeblendet. Zoomen
Sie für Details hinein oder berühren Sie einen Knoten, um seine Nachbarschaft
hervorzuheben. Vollständige Namen und formatierte Werte bleiben im Tooltip.
Große Kräftegraphen benötigen einige Sekunden für ihre Anordnung. Nach deren
Abschluss wird einmalig eine Gesamtansicht eingestellt, solange Sie noch nicht
selbst verschoben, gezoomt oder einen Knoten angefasst haben.
Verschiebung und Zoom bleiben bei Werteaktualisierungen und Größenänderungen
erhalten. Im Kräfte-Layout startet die weitere Bewegung an den vorherigen
Positionen der noch vorhandenen Knoten; die Simulation darf sie anschließend
weiter anordnen. Das Kreislayout ordnet Knoten weiterhin automatisch an.
Ein Wechsel des Layouts oder der Zoom-Bedienung setzt die Ansicht zurück.
Diese Ansicht ist flüchtig und wird nicht in der Instanzkonfiguration gespeichert.
Die bisherigen 64 KiB je gespeicherter Knoten-/Kantenliste bleiben als zusätzliche
Größengrenze wirksam. Große Startzustände werden ohne Kürzung komprimiert
eingebettet; dafür ist ein aktueller Browser mit gzip-Entpackfunktion erforderlich.

Für IPSView aktivieren Sie die optionale WebContent-Ausgabe und platzieren
die erzeugte Variable in einem HTML-Widget. Änderungen aktualisieren das
bestehende Diagramm, ohne das WebContent-Dokument neu zu laden.

`EChartsGraph` liest keine Zigbee-Netzwerkkarte. Die dafür vorgesehene
eigene Instanz `EChartsGraphZ2M` gehört nicht zu diesem Modul.
