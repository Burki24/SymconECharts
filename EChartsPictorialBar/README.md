# EChartsPictorialBar

PictorialBar vergleicht aktuelle Symcon-Werte als Symbolbalken.
Es verwendet das gemeinsame EChartsGateway und benötigt kein Archiv.

## Einrichtung

1. Instanz anlegen und das vorhandene EChartsGateway auswählen.
2. 1–100 numerische Variablen derselben Einheit hinzufügen, etwa
   Batteriestände in Prozent. Beschriftung und Farbe sind optional.
   Unterschiedliche Einheiten lassen sich mit der optionalen Min/Max-Normalisierung vergleichen.
3. Titel sowie Symbolform, Ausrichtung und Sortierung festlegen.
4. Optional die IPSView-Ausgabe einschalten und deren Design unabhängig gestalten.

Es werden ausschließlich endliche, nichtnegative Werte unterstützt. Null ist
gültig und bleibt als Kategorie mit Wert erhalten, zeichnet aber keinen
Symbolbalken. Negative Werte und ungeeignete Quellen werden sichtbar gemeldet.
Gleiche Anzeigenamen sind erlaubt; jede Variable bleibt separat erhalten.
Änderungen der Quellen aktualisieren die Anzeige automatisch.

## Mehrere Quellen übernehmen

Unter **Mehrere Quellen übernehmen** lassen sich numerische Variablen gesammelt
zur Quellenliste hinzufügen, etwa alle Batterievariablen mit der Einheit `%`.

1. Einen **Suchzweig** (Kategorie oder Instanz) wählen; **0 = Symcon** sucht ab
   der Wurzel. Unterobjekte und ausgeblendete Objekte lassen sich getrennt einbeziehen.
2. Optional einen Bestandteil des **Variablennamens** und eine **genaue Einheit**
   angeben. Namen werden ohne Beachtung der Groß-/Kleinschreibung gesucht.
   Einheiten stammen aus der Variablendarstellung beziehungsweise dem Legacy-Profil;
   beispielsweise `%`, `V` oder `LQI`. Leer bedeutet alle Einheiten.
3. **Variablen suchen** anklicken. Name, Pfad und Einheit helfen bei der Auswahl;
   bereits eingerichtete Variablen sind gekennzeichnet. Zunächst ist nichts ausgewählt.
   Einzelne Treffer oder **Alle neuen Treffer auswählen** verwenden.
4. **Ausgewählte Quellen hinzufügen** ergänzt die noch ungespeicherte Quellenliste
   und aktualisiert beide Vorschauen. Beschriftungen sowie gegebenenfalls
   Normalisierungsbereiche prüfen und anschließend mit **Übernehmen** speichern.

Bestehende Reihenfolge, Beschriftungen, Farben, Min/Max und beide Designs bleiben
erhalten. Bereits vorhandene Variablen werden anhand ihrer ID erkannt; gleiche
Namen sind erlaubt. Neue Einträge erhalten eine leere individuelle Beschriftung
(Anzeige des Variablennamens), automatische Farbe und Normalisierungsgrenzen
0–100. Andere Bereiche müssen vor dem Speichern angepasst werden.
Ohne aktivierte Normalisierung müssen alle Quellen weiterhin dieselbe Einheit haben.

Die Suche umfasst höchstens 5000 Objekte, 500 numerische Treffer vor dem
Einheitenfilter und bei Unterobjekten bis zu 100 Ebenen. Bei Erreichen einer
Objekt-/Treffergrenze wird keine gekürzte Trefferliste angeboten: Suchzweig oder
Namensfilter enger wählen. Ausgeblendete Zweige sind standardmäßig ausgeschlossen;
Verknüpfungen werden nicht verfolgt. Die Suche wird vor der Übernahme wiederholt.
Geänderte Filter leeren die Trefferliste; veraltete Auswahlen werden abgewiesen.
Auch bei der Übernahme gelten maximal 100 Quellen und 64 KiB für die Quellenliste;
bei Überschreitung wird die gesamte Auswahl abgewiesen und nichts teilweise ergänzt.

Suche und Auswahl sind nur für das geöffnete Formular bestimmt. Das Formular
zum Bearbeiten möglichst nur einmal gleichzeitig öffnen, da Symcon dynamische
Formularaktualisierungen an alle geöffneten Konfigurationen derselben Instanz sendet.

## Optionale Min/Max-Normalisierung

**Werte anhand Quellen-Min/Max normalisieren** erlaubt Quellen mit
unterschiedlichen Einheiten und auch negativen Originalwerten. Jede Quelle
besitzt ein eigenes **Minimum (Normalisierung)** und **Maximum (Normalisierung)**
(bei fehlenden Angaben 0 und 100). Es muss ein endlicher, aufsteigender
Bereich sein. Aus `100 × (Wert − Minimum) / (Maximum − Minimum)` entsteht
eine dimensionslose Vergleichsskala von 0–100. Werte außerhalb des Bereichs
werden nur für die Balkengeometrie auf 0 beziehungsweise 100 begrenzt.

Beispielsweise ergeben −5 °C im Bereich −20 bis 10 °C einen Balkenwert von
50; 75 % im Bereich 0 bis 100 % ergeben 75. Das ist keine gemeinsame
physikalische Einheit oder automatische Bewertung. Tooltip und
Wertebeschriftungen behalten Originalwert und Einheit; der Tooltip nennt
zusätzlich den normalisierten Wert und den gewählten Quellbereich.

Die Einstellung gilt gemeinsam für Kachel, IPSView und beide Vorschauen.
Sortierung, wertabhängige Farben und Farbverläufe verwenden dann die
normalisierte Skala. Für Rot–Gelb–Grün von 0 bis 100 die Farbgrenzen
entsprechend auf 0 und 100 setzen. Das gespeicherte Skalenmaximum wird
während der Normalisierung ignoriert, aber nicht verändert. Symcon-Icons,
eigene SVG-Formen sowie einzelne und wiederholte Symbole bleiben nutzbar.

Standardmäßig ist die Normalisierung aus. In diesem Fall werden die
Quellen-Min/Max ignoriert und das bisherige Verhalten einschließlich
gemeinsamer Einheit, nichtnegativer Werte und eigener Skala bleibt erhalten.

## Skala und Symbole

Bei mehr als 16 Quellen erscheint automatisch eine Kategoriennavigation:
rechts bei horizontalen Balken, unten bei vertikalen Balken. Der sichtbare
Ausschnitt passt sich an Platz und Schriftgröße an und enthält höchstens
16 Kategorien. Alle konfigurierten Werte bleiben enthalten und sind über
Schieberegler, Mausrad oder Ziehen erreichbar. Der Ausschnitt bleibt bei
Wertaktualisierungen erhalten; die Quellenkonfiguration wird dadurch nicht
verändert. Für viele Batteriestände ist die horizontale Darstellung besonders
gut lesbar. Skala, Farben und Sortierung beziehen sich weiterhin auf alle Quellen,
nicht nur auf den sichtbaren Ausschnitt.

Bei großen Listen zeigen die statischen Formularvorschauen die ersten acht
Kategorien nach der gewählten Sortierung. Ein Hinweis nennt die Gesamtzahl;
dies ist keine Begrenzung der tatsächlichen Darstellung. Ungültige Quellen
werden auch außerhalb dieses Vorschauausschnitts geprüft.

Unter **Beschriftung und Schrift** lässt sich die **Breite der
Bezeichnungsspalte** für horizontale Balken einstellen. **0 = automatisch**
berücksichtigt die längste Bezeichnung, Schriftart und Schriftgröße. Ein Wert
von **1–80 %** reserviert diesen Anteil der Diagrammbreite für die Spalte.
Auf schmalen Ansichten wird der Anteil nötigenfalls verkleinert, damit der
Wertebereich nutzbar bleibt. Zu lange Texte werden weiterhin gekürzt;
der Tooltip zeigt die vollständige Bezeichnung. Vertikale Balken bleiben von
dieser Einstellung unberührt. Kachel und unabhängiges IPSView-Design besitzen
jeweils eine eigene Einstellung; Vererbung, Designkopie und Vorschau beachten sie.

Die Skala beginnt bei null. Ein Skalenmaximum von 0 wählt die gemeinsame
Obergrenze automatisch; ein positives Maximum begrenzt die Darstellung.
Darüberliegende Werte bleiben in Beschriftung und Tooltip unverändert,
der Balken wird nur optisch begrenzt.

Rechteck, abgerundetes Rechteck, Kreis, Raute, Dreieck, Symcon-Icons und eigene SVG-Formen stehen bereit.
Mit „Symbole wiederholen“ wird die Balkenlänge durch mehrere gleich große
Symbole gefüllt; ein letztes Symbol kann angeschnitten sein.
Ein Symbol bedeutet dabei nicht eine bestimmte Anzahl von Einheiten.
Ohne Wiederholung wird eine einzelne Form an die Balkengeometrie angepasst.
Symbolgröße und Abstand wirken auf den Wiederholungsmodus.
In großen Ansichten kann die Größe zur Begrenzung der Zeichenlast steigen.
Rasterbilder und vollständige SVG-Illustrationen werden nicht unterstützt.

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
werden gemeldet. Alte Skin-Bilder sind nicht enthalten; eigene Formen werden
über den getrennten SVG-Import ausgewählt.
Für komplexe Icons kann die Größe automatisch steigen: maximal 64 Wiederholungen,
bei langen Pfaden entsprechend weniger. Bestehende Formen und Einstellungen bleiben erhalten.

### Eigene SVG-Form importieren

Unter **Anordnung und Symbole → Symbolform → Eigene SVG-Form** wählen
und im gleichnamigen Dateifeld eine `.svg`-Datei auswählen. Die Vorschau zeigt
die Form schon vor dem Übernehmen. Der unabhängige IPSView-Designer kann
eine andere Datei verwenden; „Kacheldesign übernehmen“ kopiert auch die SVG-Auswahl.

Unterstützt werden pfadbasierte SVG-Silhouetten mit einer gültigen `viewBox`
und 1–32 `path`-Elementen, optional in `g`-Gruppen. Relative Pfadbefehle,
mehrere Teilpfade und verschobene `viewBox`-Ursprünge sind erlaubt.
Texte, Kreise, Rechtecke und andere Grundformen im Grafikprogramm zuerst
in Pfade umwandeln; Transformationen vorher auf die Pfadkoordinaten anwenden.
Für reine Konturlinien den Strich in eine gefüllte Kontur umwandeln.
Die Pfadgeometrie soll innerhalb der angegebenen `viewBox` liegen.

Es wird ausschließlich die Form übernommen: Ursprüngliche SVG-Farben,
Linienstile und andere Gestaltung gehen nicht in die Darstellung ein.
Verwendet werden gefüllte Silhouetten mit der normalen Nichtnull-Füllregel.
Farben, Deckkraft, Rahmen und Schatten stellt der Designer bereit. Alle
drei Farbmodi einschließlich des festen Verlaufs entlang der Skala
funktionieren wie bei Symcon-Icons. Wiederholte Formen behalten ihre
Proportionen; einzelne Formen passen sich an die Balkengeometrie an.

#### Geeignete Größe und Vorbereitung

Eine feste ideale Pixelgröße oder DPI-Zahl gibt es bei SVG nicht: Das Modul
skaliert die Vektorform. Eine größere Zeichenfläche erzeugt deshalb nicht
automatisch eine schärfere Darstellung. Als übersichtliche Arbeitsvorlage
eignet sich `viewBox="0 0 100 100"` für ein quadratisches Symbol.
Für ein breites Motiv ist beispielsweise `viewBox="0 0 100 50"` geeignet,
für ein hohes Motiv `viewBox="0 0 50 100"`. Die vier Zahlen bedeuten
linker Ursprung, oberer Ursprung, Breite und Höhe in SVG-Koordinaten.
Auch andere Größen sind innerhalb der unten genannten Grenzen zulässig.

- Die Zeichenfläche knapp an die Form anpassen. Ein kleiner Rand ist in
  Ordnung; viel leerer Platz innerhalb der `viewBox` lässt das Symbol kleiner wirken.
- Einfache, geschlossene und gefüllte Silhouetten mit wenigen Pfadpunkten
  verwenden. Dünne Linien, kleine Schrift und sehr feine Aussparungen sind
  in einer Kachel schlecht erkennbar.
- Zum Einstieg im Designer eine wiederholte Symbolgröße von etwa 20–24 px
  wählen und die Form anschließend in der kleinsten geplanten Kachel prüfen.
  Der tatsächlich verfügbare Platz und die Balkenbreite können die Größe begrenzen.
- Das gewünschte Seitenverhältnis in der Datei anlegen. Die Diagrammausrichtung
  dreht die Form nicht automatisch; ein stehendes Motiv daher bereits stehend zeichnen.
- Vor dem Export Texte und Grundformen in Pfade, reine Striche in gefüllte
  Konturen umwandeln und Transformationen auf die Koordinaten anwenden.
  Nur die benötigte Pfadform exportieren, ohne Editor-Metadaten, zusätzliche
  Elemente oder externe Ressourcen. Eine kleine Datei mit wenigen Kilobytes
  ist ein sinnvoller Ausgangspunkt; die Importgrenzen sind keine Zielgröße.

#### Beispiel: einfache Batterieform

Die mitgelieferte [battery.svg](examples/battery.svg) ist eine direkt
importierbare, waagerechte Batteriesilhouette mit dem Seitenverhältnis 2:1.
Im Repository die Datei öffnen und als SVG herunterladen oder den folgenden
Inhalt als UTF-8-Datei `battery.svg` speichern, nicht als `.svg.txt`:

```svg
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" viewBox="0 0 100 50">
  <path d="M0 0H90V15H100V35H90V50H0Z"/>
</svg>
```

Das Beispiel besteht aus einem einzigen geschlossenen Pfad. `width` und
`height` bestimmen nur die Standardanzeige der Datei; für das Modul ist
die `viewBox` maßgeblich. Die Farbe wird bewusst nicht festgelegt.
Nach dem Import **Eigene SVG-Form** auswählen und zum Beispiel
**Farbverlauf entlang der Skala**, Farbminimum **0**, Farbmaximum **100**
und Skalenmaximum **100** einstellen. Bei wiederholten Symbolen läuft der
Verlauf über den gesamten Balken, nicht erneut durch jede einzelne Batterie.
Für ein einziges an den Balken angepasstes Motiv **Symbole wiederholen** ausschalten.

#### Technische Importgrenzen

Skripte, Ereignisbehandler, externe Referenzen, eingebettete Bilder, eigene
SVG-Verläufe/Filter und nicht unterstützte Elemente werden abgewiesen.
Grenzen: 128 KiB Dateigröße und 16 KiB normalisierte Pfaddaten;
Koordinaten und `viewBox`-Ursprung maximal ±1.000.000, Breite und Höhe
zwischen 0,000001 und 1.000.000. Komplexe Formen können weniger, größere
Wiederholungen ergeben. Bei einer ungültigen Datei erscheint eine Meldung
statt einer Ersatzform. Bestehende Quellen und Designs bleiben unverändert.

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
