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

Rechteck, abgerundetes Rechteck, Kreis, Raute, Dreieck und Symcon-Icons stehen bereit.
Mit „Symbole wiederholen“ wird die Balkenlänge durch mehrere gleich große
Symbole gefüllt; ein letztes Symbol kann angeschnitten sein.
Ein Symbol bedeutet dabei nicht eine bestimmte Anzahl von Einheiten.
Ohne Wiederholung wird eine einzelne Form an die Balkengeometrie angepasst.
Symbolgröße und Abstand wirken auf den Wiederholungsmodus.
In großen Ansichten kann die Größe zur Begrenzung der Zeichenlast steigen.
Eigene Bilder und SVG-Importe werden nicht unterstützt.

### Symcon-Icons auswählen

Unter **Anordnung und Symbole** die **Symbolform → Symcon-Icon** wählen.
Im Feld **Symcon-Icon** öffnet sich Symcons grafischer Icon-Auswahldialog.
Das gewählte Icon gilt für alle Balken dieses Designers. Beispielsweise kann
ein Batterie-Icon die bisherigen Rechtecke ersetzen. Der unabhängige
IPSView-Designer besitzt dieselbe Auswahl; bei Designvererbung gilt die Kachelauswahl.

Wiederholte Icons behalten ihre Proportionen. Ohne Wiederholung wird das
Icon wie die Grundformen an die Balkengeometrie angepasst. Alle drei Farbmodi
funktionieren auch mit Icons, insbesondere der gemeinsame skalenbezogene
Farbverlauf. Die Vorschauen reagieren bereits auf die ungespeicherte Auswahl.

Verwendet wird die auf dem Symcon-Server installierte moderne Icon-Bibliothek
(Light, Brands und Symcon-Icons). Nur die ausgewählte Pfadform wird in die
Ausgabe übernommen; der Browser benötigt keine zusätzlichen Schriften oder
Internetverbindung. Fehlende, nicht lesbare oder nicht unterstützte Icons
werden gemeldet. Alte Skin-Bilder und eigene Uploads sind nicht enthalten.
Für komplexe Icons kann die Größe automatisch steigen: maximal 64 Wiederholungen,
bei langen Pfaden entsprechend weniger. Bestehende Formen und Einstellungen bleiben erhalten.

## Gestaltung und Ausgabe

Beide Designer bieten Ausrichtung, Sortierung, Form, Wiederholung,
Symbolgröße, Abstand, Balkenbreite, Skalenmaximum, Raster, Beschriftungen,
Werte, Tooltip, Schriftgrößen, Farben, Rahmen und Deckkraft.
Zusätzlich lassen sich Schriftfamilie, Werteposition und -abstand,
Fettschrift, Überlappungsschutz und die Drehung der Kategorienbeschriftung
einstellen. Werte können außen, innen mittig, innen am Anfang oder innen am
Ende stehen. Achsenwerte und Markierungen sind schaltbar; Rasterbreite,
Linienart und Deckkraft sowie Rahmenlinienart und Schatten sind anpassbar.
Die Hervorhebung beim Überfahren kann deaktiviert werden; alternativ lassen
sich der Fokus auf eine Kategorie und die Deckkraft der übrigen Kategorien wählen.
Die Live-Vorschauen reagieren auch auf noch nicht übernommene Änderungen
und verwenden bei ausgewählten Quellen deren aktuelle Werte. Eine leere
Konfiguration zeigt gekennzeichnete Beispiele.

Der Kacheltitel bleibt unter dem Symcon-Kachelnamen und Vergrößerungsbutton.
Die optionale WebContent-Variable „PictorialBar für IPSView“ enthält die
eigenständige IPSView-Ausgabe. Änderungen an den Designern verändern keine
Quellwerte oder Archive.

## Wertabhängige Einheitsfarbe

Unter **Wertabhängige Farben** die Symbolfärbung **Stufenlos nach Wert**
wählen. Das Minimum, die Skalenmitte und das Maximum erhalten jeweils eine
eigene Farbe; dazwischen wird kontinuierlich gemischt. Standardmäßig ist
der Farbbereich 0–100 mit Rot, Gelb und Grün vorbelegt. Ein Batteriestand
von 25 % erscheint damit orange, 50 % gelb und 100 % grün.

Die Farbe gilt für den gesamten Symbolbalken, nicht als wiederholter
Miniverlauf innerhalb jedes Symbols. Der Farbbereich ist unabhängig vom
Skalenmaximum und von den gerade vorhandenen Messwerten. Außerhalb des
Farbbereichs gilt dessen jeweilige Randfarbe; die Messwerte bleiben unverändert.
Für eine feste Batterie-Längenskala zusätzlich das Skalenmaximum auf 100 setzen.
Die Farbgrenzen dürfen Nachkommastellen haben; es gilt 0 ≤ Minimum < Maximum ≤ 10¹².

In diesem Modus haben die drei Skalenfarben Vorrang vor den optionalen
Quellfarben. Die Auswahl **Kategoriefarben** stellt die bisherigen Quell-
beziehungsweise Theme-Farben wieder her. Kachel und unabhängiges IPSView-Design
können verschiedene Farbmodi verwenden. Beim Update werden keine vorhandenen
Einstellungen oder Quellen zurückgesetzt.

## Farbverlauf innerhalb des Symbolbalkens

Die zusätzliche Symbolfärbung **Farbverlauf entlang der Skala** verteilt die
drei Farben räumlich über den eingestellten Farbbereich. Der Verlauf läuft
über den gesamten Balken und beginnt nicht in jedem wiederholten Symbol neu.
Die vorhandene Auswahl **Stufenlos nach Wert** bleibt erhalten und färbt
weiterhin jeden Balken einheitlich nach seinem aktuellen Wert.

Für Batterien Minimum **0**, Maximum **100** und die Farben Rot–Gelb–Grün
wählen. Rot liegt bei 0 %, Gelb bei 50 % und Grün bei 100 %. Ein Balken mit
16 % zeigt nur den roten bis rötlich-orangen Anfang; ein voller Balken zeigt
den gesamten Verlauf. Für LQI beispielsweise Minimum **0** und Maximum **255**
verwenden; Gelb liegt dann bei 127,5. Für eine feste Längenskala das
Skalenmaximum ebenfalls auf 100 beziehungsweise 255 setzen.

Der Farbbereich bleibt auch bei automatischer Längenskalierung fest. Ein
kurzer Balken wird deshalb nicht bis Grün umgefärbt. Vor dem Farbminimum
und oberhalb des Farbmaximums gilt die jeweilige Randfarbe. Der Modus
funktioniert horizontal und vertikal sowie mit einzelnen und wiederholten
Symbolen; Nullwerte und optisch begrenzte Originalwerte bleiben erhalten.
Kachel und unabhängiges IPSView-Design können unterschiedliche Modi nutzen.
