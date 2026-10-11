# EChartsBarPolar

`EChartsBarPolar` stellt aktuelle Zahlenwerte als Polar-Balken dar. Die Kachel zeigt wahlweise radial nach außen gerichtete Balken oder konzentrische Kreisbögen. Das Modul akzeptiert 1 bis 16 eindeutige numerische Symcon-Variablen mit derselben effektiven Einheit. Ein Archiv ist nicht nötig.

Kachel- und unabhängiger IPSView-Designer gliedern Anordnung, Beschriftung,
Geometrie, Balkeneffekte sowie Skala und Winkel in einklappbare Bereiche.
Die Live-Vorschau folgt darunter.

## Live-Vorschau

Kachel- und IPSView-Designer zeigen eine SVG-Vorschau für radiale Balken und
konzentrische Bögen. Sie reagiert auf noch nicht gespeicherte Titel-, Quellen-,
Theme-, Skalen- und Geometrieänderungen; Kategoriebeschriftungen verwenden
konfigurierte Namen vor Symcon-Namen, niemals automatisch Variablen-IDs.
**Kacheldesign verwenden** gilt auch für die IPSView-Vorschau. Ohne gültige
Quellen sind Beispieldaten markiert. Die Vorschau ist schematisch:
Hover-Hervorhebung, Animation und das responsive Beschriftungslayout prüfen
Sie an der echten Kachel beziehungsweise im IPSView-Widget.

## Einrichtung

Für alle ECharts-Diagramme genügt ein gemeinsames `EChartsGateway`. Wählen Sie bei weiteren Instanzen das vorhandene Gateway.

Unter **Polar-Quellen** wählen Sie die Variablen. Jede Zeile ist ein eigenständiger Wert, nicht die Änderung eines vorherigen Werts. Die Listenreihenfolge lässt sich verschieben. Eine optionale Kategoriebeschriftung hat Vorrang; ist sie leer, erscheint der aktuelle Symcon-Name. Gleichlautende Beschriftungen sind erlaubt und führen nicht zum Verlust eines Werts. Variablen-IDs dienen nur der internen Zuordnung und werden nicht automatisch angezeigt.

Einheit und Nachkommastellen können aus der Variablendarstellung übernommen oder je Quelle gesetzt werden. Alle Quellen müssen dieselbe effektive Einheit verwenden. Eine Quellenfarbe kann individuell gewählt werden; **Automatisch (Theme)** verwendet die Farbpalette des gewählten Themes.

## Darstellung

Im **Kacheldesigner** und im unabhängigen **IPSView-Design** stehen zur Verfügung:

- **Radiale Balken**: Kategorien liegen um den Kreis; Werte erstrecken sich vom inneren zum äußeren Radius.
- **Konzentrische Bögen**: Kategorien liegen auf getrennten Kreisbahnen; Werte bestimmen die Bogenlänge.
- Konfigurierte oder wertsortierte Reihenfolge, Kategorie- und Wertbeschriftungen, Raster, Balkenbreite, Innen- und Außenradius, Startwinkel, Winkelumfang, Drehrichtung und abgerundete Enden.
- ECharts-Theme, die je Quelle konfigurierte Balkenfarbe und wahlweise Vollfarbe oder Farbverlauf.

Standardmäßig bestimmt ECharts die Werteskala automatisch. Negative Werte werden nicht in positive Werte umgerechnet. Bei vielen Quellen oder langen Namen können Beschriftungen in kleinen Kacheln überlappen; Kachelgröße und Anzeigeoptionen lassen sich passend wählen.
Der **Winkelumfang** bestimmt, welchen Teil des Kreises die Balken nutzen: 360° ergibt den bisherigen Vollkreis, 180° einen Halbkreis. Werte von 30° bis 360° sind möglich. **Startwinkel** legt den Anfang fest, **Im Uhrzeigersinn** die Laufrichtung. Balken, Hintergrundspuren und Raster nutzen auch bei konzentrischen Bögen denselben Ausschnitt und bleiben nach Größenänderungen darin. Kachel und unabhängiger IPSView-Designer können unterschiedliche Winkelumfänge verwenden; bei **Kacheldesign verwenden** übernimmt IPSView den Kachelwert.
Im **Kacheldesigner** und im unabhängigen **IPSView-Designer** kann die Werteskala alternativ als fester Bereich mit Minimum und Maximum eingestellt werden. Das ist besonders für vergleichbare Anzeigen mit dauerhaft gleicher Skala sinnvoll. Werte außerhalb dieses Bereichs werden im Diagramm abgeschnitten; die Quelldaten bleiben unverändert. Optionale **Hintergrundspuren** zeigen hinter jedem Balken den verfügbaren Skalenbereich. Ihre Farbe kann dem Theme folgen oder frei gewählt werden; die Deckkraft ist einstellbar. Ohne Änderung der Einstellungen bleiben automatische Skala und ausgeblendete Spuren erhalten. Mit **Kacheldesign verwenden** übernimmt IPSView Skala und Spuren der Kachel; nach dem Deaktivieren sind sie unabhängig einstellbar oder können mit der Kopierschaltfläche übernommen werden.
Bei einer festen Skala beginnen Balken und Kreisbögen am eingestellten Minimum; die Beschriftungen zeigen weiterhin die tatsächlichen Quellwerte.
Bei **Balkenfüllung** ist **Vollfarbe** voreingestellt. **Farbverlauf** verwendet die Farbe der jeweiligen Quelle und eine wählbare Endfarbe; **Automatisch (Theme)** führt zur Hintergrundfarbe des Themes. Der Übergang verläuft linear innerhalb der gezeichneten Form, nicht entlang des Polarwerts. Er ist ein Farbeffekt, kein zusätzlicher Wert oder eine zweite Datenreihe. Werte innerhalb eines Verlaufs erhalten einen Kontrastumriss. Kachel und unabhängiges IPSView-Design können unterschiedliche Füllungen und Endfarben verwenden; **Kacheldesign verwenden** übernimmt auch diese Einstellungen.
Die **Balkendeckkraft** lässt sich für Vollfarbe und Farbverlauf von 0 bis 100 % einstellen; 100 % entspricht der bisherigen Darstellung. Sie wirkt nur auf die farbigen Balken, nicht auf Hintergrundspuren oder Beschriftungen. Bei geringer Deckkraft erhalten Werte innerhalb der Balken einen Kontrastumriss. Kachel und unabhängiges IPSView-Design können unterschiedliche Werte verwenden; **Kacheldesign verwenden** übernimmt die Kacheleinstellung.
Die **Balkenkontur** ist standardmäßig ausgeschaltet (0 px). Ihre Breite lässt sich von 1 bis 8 px und ihre Farbe separat einstellen; **Automatisch (Theme)** verwendet die Rahmenfarbe des gewählten Themes. Die Kontur umgibt nur die farbigen Balken, nicht die Hintergrundspuren. Im unabhängigen IPSView-Designer kann sie anders eingestellt werden als in der Kachel; **Kacheldesign verwenden** übernimmt die Kacheleinstellung.
Der **Balkenschatten** ist standardmäßig ausgeschaltet (0 px). Seine Stärke lässt sich von 1 bis 20 px, die Deckkraft von 0 bis 100 % einstellen. Die Farbe kann frei gewählt werden; **Automatisch (Theme)** verwendet die Rahmenfarbe des Themes. Der Schatten betrifft nur farbige Balken, nicht Hintergrundspuren oder Beschriftungen. Im unabhängigen IPSView-Designer kann er anders eingestellt werden; **Kacheldesign verwenden** übernimmt die Kacheleinstellung.
Die **Balkenanimation** lässt sich für Kachel und unabhängiges IPSView-Design getrennt abschalten. Im eingeschalteten Zustand sind die Dauer beim ersten Anzeigen (Standard 350 ms) und bei Wertänderungen (Standard 500 ms) jeweils von 0 bis 3000 ms einstellbar. 0 ms schaltet den jeweiligen Übergang aus. Start und Aktualisierung besitzen zudem getrennte Bewegungskurven und feste Verzögerungen (0–3000 ms); ohne Änderung gilt cubicInOut ohne Verzögerung. Die Verzögerung betrifft alle Balken zugleich, nicht jeden einzeln. Wenn das Anzeigegerät „Bewegung reduzieren“ vorgibt, bleiben Animationen unabhängig von diesen Einstellungen ausgeschaltet. **Kacheldesign verwenden** übernimmt die Kacheleinstellung.
Die **Balkenhervorhebung** lässt sich auf **Standard (ECharts)**, **Balken hervorheben**, **Balken fokussieren und andere abdunkeln** oder **Aus** stellen. Standard verändert das bisherige Verhalten nicht. Die beiden aktiven Modi zeichnen beim Berühren oder Überfahren eine kontrastierende Kontur; beim Fokussieren werden andere Balken zusätzlich abgedunkelt. Ein Antippen hält den gewählten Balken hervorgehoben, erneutes Antippen löst die Auswahl. Die Hervorhebungsfarbe kann vorgegeben werden oder folgt automatisch der Theme-Textfarbe. Gleichnamige Quellen bleiben intern anhand ihrer Variablen-ID getrennt; diese ID erscheint nicht im Diagramm. Kachel und unabhängiges IPSView-Design können unterschiedliche Modi verwenden, während **Kacheldesign verwenden** auch diese Einstellung übernimmt.
Die **Position der Wertbeschriftung** lässt sich in beiden Designern auf **Mitte des Balkens** (Standard), **Innen am Anfang**, **Innen am Ende** oder **Außerhalb des Balkens** setzen. Bei radialen Balken sind Anfang und Ende die innere beziehungsweise äußere Radiuskante; bei konzentrischen Bögen sind es Anfang und Ende des Bogens. **Außerhalb des Balkens** platziert den Wert direkt hinter dessen Ende, aber weiterhin im Diagrammbereich. Dafür rücken Balken und Kategoriebeschriftungen etwas auseinander. Die Schrift bleibt waagerecht; innerhalb des Balkens wird sie abhängig von seiner Farbe automatisch hell oder dunkel gewählt, außerhalb nutzt sie die Textfarbe des Diagramm-Themes. Bei vielen Quellen, sehr kurzen Balken oder langen Werten können Beschriftungen dennoch überlappen; dann Mitte wählen, die Kachel vergrößern oder Werte ausblenden.
**Kategorie-, Wert- und Skalenschriftgröße** sind in beiden Designern getrennt von 8 bis 24 px einstellbar (Standard jeweils 10 px). Im IPSView-Widget nutzt die Kategoriebeschriftung bei ausreichend Platz eine an Namen und Schriftgröße angepasste Breite; in schmalen Widgets wird sie zugunsten eines lesbaren Kreises weiterhin gekürzt. Die Kachel bleibt kompakt. Bei konzentrischen Bögen bleiben Kategorien vor den Balken sichtbar; ein Hintergrundumriss verbessert ihren Kontrast auch über farbigen Spuren. Bei Größenänderungen werden Kategorien nicht allein wegen eines automatisch gewählten Beschriftungsintervalls übersprungen; tatsächlich überlappende Beschriftungen können weiterhin ausgeblendet werden. Werte an abgerundeten Bogenenden werden weiter in die farbige Fläche gerückt. Bei kurzen Bögen oder vielen Quellen kann ein Wert weiterhin zu wenig Platz haben; die Position **Mitte des Balkens** oder eine kleinere Schriftgröße hilft dann.
Der Außenradius aus dem Designer ist eine Obergrenze: In kleineren Kacheln passt sich der Kreis mit den Beschriftungsabständen an den verfügbaren Platz an. Sind die Balken sehr kompakt und ihre Werte eingeblendet, entfallen die Zahlen der Werteskala, damit die Balkenwerte lesbar bleiben; Raster und Balken bleiben sichtbar. Bei einer Größenänderung wird das Diagramm neu eingepasst.

Die Kachel aktualisiert sich bei Änderungen einer Quellvariablen. Optional erzeugt das Modul eine eigenständige WebContent-Variable `IPSViewBarPolar` für ein IPSView-HTML-Widget. Deren HTML-Dokument bleibt bei Wertänderungen bestehen; nur der Diagrammzustand wird übertragen. Für IPSView sind Hintergrundfarbe und Deckkraft einstellbar.

**Kacheldesign verwenden** ist in IPSView zunächst aktiv. Dann sind die eigenen Designfelder und **Kacheldesign nach IPSView kopieren und unabhängig bearbeiten** deaktiviert. Nach Abschalten des Schalters kann das zuletzt gespeicherte Kacheldesign einmalig kopiert und anschließend unabhängig geändert werden. Quellen und aktuelle Werte bleiben gemeinsam.

Die öffentliche Funktion `ECBP_GetPolarData($InstanzID)` liefert das aktuelle Chart-Modell als JSON. Sie liest Werte, verändert aber keine Quellvariablen.
