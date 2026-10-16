# EChartsSankey

Sankey stellt aktuelle Mengenflüsse zwischen manuell angelegten Knoten dar.
Die Breite jeder Verbindung entspricht dem aktuellen Wert einer ausgewählten
Integer- oder Float-Variable. Das Modul bietet eine native Symcon-Kachel und
optional ein unabhängig gestaltbares IPSView-HTML-Widget.

Legen Sie zuerst 2 bis 100 Knoten mit einem eindeutigen Schlüssel und einer
Beschriftung an. Schlüssel beginnen mit einem Buchstaben und enthalten bis zu
40 Buchstaben, Ziffern, Unterstriche oder Bindestriche. Die sichtbare
Beschriftung darf bis zu 80 Zeichen enthalten und bei mehreren Knoten gleich
sein. Beispiel:

| Schlüssel | Beschriftung |
| --- | --- |
| solar | Photovoltaik |
| grid | Netzbezug |
| house | Haus |
| heating | Heizung |

Legen Sie anschließend 1 bis 200 gerichtete Verbindungen an. Start- und
Zielknoten werden aus den Knotenlisten ausgewählt, auch während der
Bearbeitung noch nicht gespeicherter Knoten. Ordnen Sie jeder Verbindung eine
numerische Quellvariable zu, beispielsweise `solar → house` die aktuelle
PV-Leistung und `house → heating` die Heizungsleistung. Übernehmen Sie die
Konfiguration nach Abschluss der Eingaben.

Alle Flüsse müssen dieselbe fachliche Größe und Einheit verwenden. Die
optionale Einheit, zum Beispiel `W`, wird angezeigt; sie rechnet Werte nicht
um. Eine Variable kann bewusst mehreren Verbindungen zugeordnet werden;
auf jeder gilt dann ihr vollständiger Wert. Eingehende und ausgehende Summen
müssen vom Anwender sinnvoll gewählt werden. Unterschiede werden nicht
automatisch ausgeglichen. Ein Knotenwert entspricht der größeren dieser
beiden Summen, nicht einem zusätzlichen Messwert.

Negative Werte, Kreise, Selbstverbindungen und doppelte gerichtete Knotenpaare
sind unzulässig. Die Diagnose nennt bei fehlerhaften Einträgen die Zeile.
Nullflüsse und Knoten ohne positive Verbindung bleiben unsichtbar. Sind alle
Werte null, erscheint ein entsprechender Hinweis. Wertänderungen aktualisieren
das vorhandene Diagramm automatisch.

Die Designer bieten waagerechte oder senkrechte Anordnung, Knotenausrichtung,
Knotenbreite und -abstand, Verschieben der Knoten, Beschriftungen, Werte,
Schriftgrößen, Farben, Tooltip und Animation. Flüsse erhalten die Farbe ihres
Start- oder Zielknotens, einen Farbverlauf oder eine feste Farbe. Deckkraft und
Krümmung sind einstellbar. Bei vielen Knoten reduziert die Darstellung den
Abstand bei Bedarf. Verschobene Positionen werden nicht gespeichert.

Unter „Knotengestaltung“ können Sie einzelne Knoten gezielt einfärben sowie
Rahmenbreite, Rahmenfarbe, Eckenradius, Deckkraft und Schatten einstellen.
Individuelle Farben haben Vorrang vor der gemeinsamen Knotenfarbe und dem
Theme. Die Zuordnung verwendet den Knotenschlüssel, nicht die Beschriftung;
gleichnamige Knoten bleiben damit unabhängig. Pro Schlüssel ist genau eine
Farbzeile zulässig. Farben derzeit nicht vorhandener Knoten werden ignoriert.

Für Knotenbeschriftungen stehen automatische oder feste Positionen, Abstand,
Fettschrift und eine maximale Breite zur Verfügung. Breite `0` wählt den
responsiven Standard. Lange Texte lassen sich kürzen oder an Wörtern bzw.
Zeichen umbrechen. Überlappende Knotenbeschriftungen können ausgeblendet
werden. Verbindungswerte lassen sich zusätzlich mit eigener Schriftgröße und
Farbe anzeigen; sie verwenden ebenfalls die eingetragene Einheit.

Die Hervorhebung beim Berühren eines Knotens oder einer Verbindung kann
ausgeschaltet werden, direkte Verbindungen oder vollständige Flusspfade
zeigen. Die Deckkraft hervorgehobener und übriger Flüsse ist einstellbar.
Layout-Optimierungsdurchläufe beeinflussen die automatische Anordnung:
Standard sind 32, bei `0` bleibt die Reihenfolge der Knotenliste innerhalb
der Flussstufen erhalten.

IPSView übernimmt standardmäßig das Kacheldesign. Alternativ können Sie es
kopieren und unabhängig bearbeiten. Die Formularvorschauen reagieren auf
ungespeicherte Einstellungen und verwenden ausdrücklich gekennzeichnete
Beispielgewichte. Große oder noch ungültige Konfigurationen werden dort durch
ein kleines Beispiel ersetzt; die vollständigen aktuellen Werte erscheinen
nach Übernahme in der Kachel und im IPSView-Widget.
Die Vorschau stellt Schriftumbruch und Überlappung schematisch dar;
Layout-Optimierung und interaktive Hervorhebung werden erst im Diagramm
angewendet. Die tatsächlichen Textmaße bestimmt der Browser.

Das Modul benötigt ein aktives EChartsGateway. Es liest Werte und verändert
weder Quellvariablen noch Archivdaten.
