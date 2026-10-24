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
   **Inverse Achse (Minimum außen)** kehrt bei Bedarf die Richtung dieser
   Messgröße für alle Gruppen um; standardmäßig ist die Option ausgeschaltet.
3. Unter **Vergleichswerte** für jede benannte Gruppe eine numerische Variable
   je Achse auswählen. Beispielsweise erhält „Büro“ je eine Temperatur-,
   Feuchtigkeits- und CO₂-Variable; „Küche“ erhält dieselben Achsen mit eigenen
   Variablen. 1–16 vollständige Gruppen sind möglich. Gruppennamen müssen genau
   übereinstimmen. Fehlende Werte werden nicht als Null ergänzt.
4. Änderungen übernehmen. Änderungen der Quellwerte aktualisieren das Diagramm.

Der Radius zeigt den relativen Stand zwischen Minimum (Mitte, 0 %) und Maximum
(außen, 100 %). Unterschiedliche Einheiten werden nicht addiert. Ein größerer
Radius ist nicht automatisch besser oder schlechter. Werte außerhalb des
Bereichs werden nur optisch begrenzt; ein Hinweis erscheint und der Tooltip
zeigt weiterhin die Originalwerte mit der jeweiligen Einheit.

Auf einer inversen Achse liegt das Maximum in der Mitte und das Minimum außen.
Beispielsweise liegt bei einem Verbrauchsbereich von 0–100 W ein Wert von
20 W bei 80 % des Radius. Eine normale Batterieachse von 0–100 % kann daneben
weiterhin hohe Ladestände außen zeigen. Min/Max bleiben aufsteigend konfiguriert;
Achsenbeschriftung und Tooltip kennzeichnen inverse Achsen mit „(invers)“.
Der angezeigte Bereich verläuft von innen nach außen, bei inversen Achsen also
vom Maximum zum Minimum. Optionale Prozentmarkierungen beschreiben immer den
Radius, nicht den unveränderten Originalwert. Es entsteht keine automatische
Bewertung. Beide Ausgabewege und die Vorschauen verwenden dieselbe Achsenrichtung.

## Gruppenauswahl

**Gruppenauswahl aktivieren** ergänzt die scrollbare Legende um **Alle** und
**Umkehren**. Ein Klick auf eine Gruppe blendet ihre Fläche ein oder aus. Die
Auswahl bleibt bei Werteaktualisierungen und Größenänderungen erhalten; nach
einem Neuladen gilt wieder die gespeicherte Anfangssichtbarkeit.

Unter **Anfängliche Gruppensichtbarkeit (optional)** lassen sich bis zu 16
Gruppen einmalig zuordnen und deren Startsichtbarkeit festlegen. Ohne Eintrag
ist eine Gruppe anfangs sichtbar. Die Liste gilt gemeinsam für Kachel und
IPSView, unabhängig von deren Design. Ohne aktivierte Gruppenauswahl werden
diese Vorgaben nicht angewendet. Bestehende Konfigurationen bleiben unverändert
nutzbar; die Auswahl ist standardmäßig ausgeschaltet.

Die Auswahl verändert keine Achsenskala und keine Werte. Auch ausgeblendete
Gruppen werden weiter aktualisiert und geprüft. Die 3–10 Achsen und ihre
Min/Max-Grenzen bleiben immer vollständig erhalten. Wenn alle Gruppen
ausgeblendet sind, bleibt die Legende zur erneuten Auswahl verfügbar – auch
wenn „Legende anzeigen“ im Designer ausgeschaltet wurde. Nicht mehr vorhandene
Gruppen werden in gespeicherten Sichtbarkeits- oder Farbzuordnungen nicht
automatisch gelöscht; nach einer Umbenennung die Zuordnungen bei Bedarf anpassen.

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
Bei aktivierter Auswahl folgt die Vorschau der gespeicherten beziehungsweise
gerade bearbeiteten Anfangssichtbarkeit. Höchstens acht ausgewählte Gruppen
werden gezeichnet; ein Hinweis nennt gezeichnete, ausgewählte und konfigurierte
Gruppen. Der Ausschnitt verändert weder Achsenbereiche noch Gruppenfarben.
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

## Grenzen und Hinweise

Unvollständige Gruppen, doppelte Gruppe-Achse-Zuordnungen, unbekannte Achsen,
ungültige Bereiche und nichtnumerische Variablen werden mit Diagnose abgewiesen.
Variablennamen dürfen gleich sein; die Zuordnung verwendet die Objekt-ID.
Eine Variable kann mehrfach verwendet werden, wird aber nur einmal abonniert.
Bei vorübergehenden Transportfehlern bleibt die letzte Darstellung mit Hinweis
sichtbar. Aktuelle Werte können unterschiedliche Messzeitpunkte haben; eine
atomare gemeinsame Messung wird nicht zugesichert.
Übermäßig große Diagrammmodelle oder HTML-Ausgaben werden ausdrücklich
abgewiesen, nicht still gekürzt. In diesem Fall Titel oder Gruppenangaben
verkürzen.
