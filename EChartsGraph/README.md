# EChartsGraph

`EChartsGraph` zeigt frei ausgewählte IP-Symcon-Objekte und ausdrücklich
konfigurierte Verbindungen als interaktiven Apache-ECharts-Graphen. Benötigt
werden IP-Symcon 9.0/9.1 und ein verbundenes, aktives `EChartsGateway`.
Ein Archiv ist nicht erforderlich.

Wählen Sie 2 bis 300 Kategorien, Instanzen oder Variablen als Knoten aus und
verbinden Sie diese mit 1 bis 1.000 Kanten. Beide Enden einer Kante müssen in
der Knotenliste stehen. Standardmäßig sind die Kanten ungerichtet;
Selbstverbindungen und doppelte Verbindungen – auch in umgekehrter Reihenfolge –
sind ungültig. Mit **Gerichtete Verbindungen** folgen Pfeile ausdrücklich
**Von → Nach**. Dann dürfen A → B und B → A gleichzeitig bestehen;
dieselbe Richtung darf weiterhin nur einmal vorkommen. Gegenrichtungen werden
mit getrennten Kurven dargestellt. Auch gerichtete Kreise sind zulässig;
die Verbindungen stellen Beziehungen ohne automatische Flussbilanz dar.
Die Richtung erscheint zusätzlich im Verbindungstooltip und in beiden Vorschauen.
Die Einstellung gilt gemeinsam für Kachel und IPSView; vorhandene Designs,
Knoten und Kanten bleiben beim Aktivieren unverändert.
Symcon-Objektbeziehungen werden nicht automatisch zu Verbindungen. Der
optionale Anzeigename eines Knotens überschreibt dessen aktuellen
Symcon-Namen. Die Objekt-ID bleibt der technische Schlüssel, sodass
gleichnamige Objekte getrennte Knoten bleiben.

Jede Verbindung kann außerdem eine **Gewichtsquelle** erhalten:
**Ungewichtet** bleibt der Standard. **Festes Gewicht** verwendet den manuell
eingetragenen Wert mit eigener Einheit und Nachkommastellen. **Aktueller
Variablenwert** liest eine Integer- oder Float-Variable anhand ihrer ID;
diese muss selbst kein Graph-Knoten sein. Dieselbe Variable darf mehrere
Verbindungen gewichten. Die jeweils anderen Eingabefelder bleiben ohne Wirkung.

**Minimaler/Maximaler Gewichtsbezug** legen einen gemeinsamen festen Zahlenbereich
fest, beispielsweise 0–100 oder 0–255. Dieser Bereich muss endlich, nichtnegativ
und aufsteigend sein. Innerhalb des Bereichs wächst die Breite linear zwischen
**Minimale/Maximale gewichtete Linienbreite** im jeweiligen Designer (1–20 Pixel).
Die Grenzen werden nicht automatisch aus den aktuellen Werten bestimmt.
Null bleibt als dünne Verbindung sichtbar; Werte außerhalb des Bereichs werden
nur bei der Breite begrenzt. Negative oder ungültige Werte werden abgewiesen.
Ungewichtete Verbindungen behalten ihre bisherige Breite.

Im Verbindungstooltip bleiben Originalgewicht und Einheit erhalten; bei
Variablen werden deren Nachkommastellen und Einheit verwendet. Gewichte ändern
weder die Richtung noch die Kräfteparameter und bilden keine Flussbilanz.
Die Zahlenbereiche gelten für sämtliche gewichteten Kanten; eine automatische
Einheitenumrechnung findet nicht statt. Kachel und IPSView können unabhängige
Breiten verwenden, während der Gewichtsbezug gemeinsam bleibt. Beide Vorschauen
zeigen dieselbe Berechnung. Die bisherige 64-KiB-Grenze je Liste gilt weiterhin;
umfangreichere Gewichtseinstellungen können sie vor der maximalen Kantenzahl erreichen.

Der Tooltip einer Variablen zeigt ihren aktuellen formatierten Wert. Auf
Wunsch erscheint er zusätzlich an der Knotenbeschriftung. Namen und Werte
werden mindestens alle 60 Sekunden neu gelesen. Das Modul liest lediglich
Objekte und Werte; es ändert sie nicht.
Änderungen aktiver Gewichtsvariablen aktualisieren zusätzlich unmittelbar den Graphen.

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
