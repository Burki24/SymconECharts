# EChartsPictorialBar

PictorialBar vergleicht aktuelle Symcon-Werte als Symbolbalken.
Es verwendet das gemeinsame EChartsGateway und benötigt kein Archiv.

## Einrichtung

1. Instanz anlegen und das vorhandene EChartsGateway auswählen.
2. 1–16 numerische Variablen derselben Einheit hinzufügen, etwa
   Batteriestände in Prozent. Beschriftung und Farbe sind optional.
3. Titel sowie Symbolform, Ausrichtung und Sortierung festlegen.
4. Optional die IPSView-Ausgabe einschalten und deren Design unabhängig gestalten.

Es werden ausschließlich endliche, nichtnegative Werte unterstützt. Null ist
gültig und bleibt als Kategorie mit Wert erhalten, zeichnet aber keinen
Symbolbalken. Negative Werte und ungeeignete Quellen werden sichtbar gemeldet.
Gleiche Anzeigenamen sind erlaubt; jede Variable bleibt separat erhalten.
Änderungen der Quellen aktualisieren die Anzeige automatisch.

## Skala und Symbole

Die Skala beginnt bei null. Ein Skalenmaximum von 0 wählt die gemeinsame
Obergrenze automatisch; ein positives Maximum begrenzt die Darstellung.
Darüberliegende Werte bleiben in Beschriftung und Tooltip unverändert,
der Balken wird nur optisch begrenzt.

Rechteck, abgerundetes Rechteck, Kreis, Raute und Dreieck stehen bereit.
Mit „Symbole wiederholen“ wird die Balkenlänge durch mehrere gleich große
Symbole gefüllt; ein letztes Symbol kann angeschnitten sein.
Ein Symbol bedeutet dabei nicht eine bestimmte Anzahl von Einheiten.
Ohne Wiederholung wird eine einzelne Form an die Balkengeometrie angepasst.
Symbolgröße und Abstand wirken auf den Wiederholungsmodus.
In großen Ansichten kann die Größe zur Begrenzung der Zeichenlast steigen.
Eigene Bilder und SVG-Importe werden nicht unterstützt.

## Gestaltung und Ausgabe

Beide Designer bieten Ausrichtung, Sortierung, Form, Wiederholung,
Symbolgröße, Abstand, Balkenbreite, Skalenmaximum, Raster, Beschriftungen,
Werte, Tooltip, Schriftgrößen, Farben, Rahmen und Deckkraft.
Die Live-Vorschauen reagieren auch auf noch nicht übernommene Änderungen
und verwenden bei ausgewählten Quellen deren aktuelle Werte. Eine leere
Konfiguration zeigt gekennzeichnete Beispiele.

Der Kacheltitel bleibt unter dem Symcon-Kachelnamen und Vergrößerungsbutton.
Die optionale WebContent-Variable „PictorialBar für IPSView“ enthält die
eigenständige IPSView-Ausgabe. Änderungen an den Designern verändern keine
Quellwerte oder Archive.
