# EChartsRadar

Vergleicht aktuelle numerische Symcon-Variablen mehrerer Räume oder Geräte auf
gemeinsamen Radar-Achsen. Es wird kein Archiv benötigt.

## Einrichtung

1. Eine EChartsRadar-Instanz anlegen und das gemeinsame EChartsGateway wählen.
2. Unter **Radar-Achsen** 3–10 Achsen mit eindeutigen Schlüsseln anlegen,
   beispielsweise `temperature`, `humidity` und `co2`. Schlüssel beginnen mit
   einem Buchstaben und enthalten höchstens 40 Buchstaben, Ziffern, Unterstriche
   oder Bindestriche. Je Achse Beschriftung, Minimum, Maximum, optional Einheit
   und 0–6 Nachkommastellen festlegen. Das Maximum muss größer sein als das Minimum.
3. Unter **Vergleichswerte** für jede benannte Gruppe eine numerische Variable
   je Achse auswählen. Beispielsweise erhält „Büro“ je eine Temperatur-,
   Feuchtigkeits- und CO₂-Variable; „Küche“ erhält dieselben Achsen mit eigenen
   Variablen. 1–8 vollständige Gruppen sind möglich. Gruppennamen müssen genau
   übereinstimmen. Fehlende Werte werden nicht als Null ergänzt.
4. Änderungen übernehmen. Änderungen der Quellwerte aktualisieren das Diagramm.

Der Radius zeigt den relativen Stand zwischen Minimum (Mitte, 0 %) und Maximum
(außen, 100 %). Unterschiedliche Einheiten werden nicht addiert. Ein größerer
Radius ist nicht automatisch besser oder schlechter. Werte außerhalb des
Bereichs werden nur optisch begrenzt; ein Hinweis erscheint und der Tooltip
zeigt weiterhin die Originalwerte mit der jeweiligen Einheit.

## Gestaltung

Kacheldesigner und unabhängiger IPSView-Designer bieten Themes, Vieleck oder
Kreis, Startwinkel und Drehrichtung, Legende, Tooltip, Achsennamen und
normierte Prozentbeschriftung, Rasterflächen und -teilungen, Linienbreite,
Linien- und Flächendeckkraft, Symbolgröße, Schriftgrößen, Farben und Animation.
Zusätzlich stehen zur Verfügung:

- individuelle Farben je Vergleichsgruppe; die Auswahl folgt auch
  ungespeicherten Gruppennamen. Nicht mehr verwendete Farbzuordnungen bleiben
  erhalten, werden aber nicht angezeigt. Automatisch folgt dem Theme;
- durchgezogene, gestrichelte oder gepunktete Verbindungslinien sowie
  Flächenschatten mit eigener Farbe und Weichzeichnung;
- Kreis, leerer Kreis, Quadrat, abgerundetes Quadrat, Dreieck, Raute oder
  keine Punktsymbole, mit Drehung, Randbreite, Randfarbe und Deckkraft;
- Originalwerte an sichtbaren Punkten mit Position, Abstand, Schriftgröße,
  Farbe und Fettschrift. Die Angaben behalten ihre jeweiligen Einheiten,
  auch wenn die Geometrie außerhalb des Achsenbereichs begrenzt wird;
- separat sichtbare Achsenbereiche, Achsen- und Rasterlinien mit Breite und
  Deckkraft, Rasterlinienarten und zwei eigene Rasterflächenfarben;
- Legende oben oder unten mit eigener Schriftgröße und Farbe; Fettschrift
  für Titel und Achsennamen sowie abschaltbare Hover-Hervorhebung.

Die aktuellen SVG-Vorschauen reagieren bereits auf ungespeicherte Änderungen.
Sie sind schematisch; die endgültige Anordnung hängt von der Diagrammgröße ab.
Wertebeschriftungen sind zunächst ausgeschaltet. Der standardmäßig aktive,
abschaltbare Überlappungsschutz blendet bei ähnlichen Werten einzelne Texte aus;
die vollständigen Werte bleiben im Tooltip. Bei Punktsymbol „Keine“ oder Größe 0 erscheinen auch
keine Punktbeschriftungen; Tooltips bleiben verfügbar.

Ein konfigurierter Diagrammtitel steht in der Kachel unter dem Symcon-Kachelnamen
und Vergrößerungsbutton. Lange Achsentexte werden platzabhängig gekürzt;
vollständige Achsenbeschriftungen und Messwerte stehen im Tooltip.

## IPSView

Die IPSView-Ausgabe erzeugt eine eigene WebContent-Variable. Sie übernimmt
standardmäßig das Kacheldesign oder verwendet den unabhängigen Designer.
„Kacheldesign nach IPSView kopieren und unabhängig bearbeiten“ übernimmt den
aktuellen Stand einmalig. Datenquellen bleiben für beide Ausgabewege gleich.

## Diagnose

Unvollständige Gruppen, doppelte Gruppe-Achse-Zuordnungen, unbekannte Achsen,
ungültige Bereiche und nichtnumerische Variablen werden mit Diagnose abgewiesen.
Variablennamen dürfen gleich sein; die Zuordnung verwendet die Objekt-ID.
Eine Variable kann mehrfach verwendet werden, wird aber nur einmal abonniert.
Bei vorübergehenden Transportfehlern bleibt die letzte Darstellung mit Hinweis
sichtbar. Aktuelle Werte können unterschiedliche Messzeitpunkte haben; eine
atomare gemeinsame Messung wird nicht zugesichert.
