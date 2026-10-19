# EChartsBarPolar

## Beschriftungsoptionen

Zusätzlich zu Mitte, innen am Anfang/Ende und außerhalb stehen die ECharts-Positionen Anfang und Ende zur Verfügung. Der Abstand kann von 0 bis 100 px gesetzt werden; -1 bewahrt die bisherige automatische Berechnung.

Die Optionen gelten für Kacheln und – bei unabhängigem Design – auch für
IPSView. Bei geerbtem Design folgt IPSView den Kacheleinstellungen.

`EChartsBarPolar` stellt aktuelle Zahlenwerte als Polar-Balken dar. Die Kachel zeigt wahlweise radial nach außen gerichtete Balken oder konzentrische Kreisbögen. Das Modul akzeptiert 1 bis 100 eindeutige numerische Symcon-Variablen mit derselben effektiven Einheit oder mit ausdrücklich aktivierter Min/Max-Normalisierung. Ein Archiv ist nicht nötig.

## Quellenauswahl und Seiten

**Quellenauswahl aktivieren** ist zunächst ausgeschaltet. Aktiviert erscheint
in Kachel und IPSView die Schaltfläche **Quellen** mit einzeln schaltbaren
Quellen sowie **Alle** und **Umkehren**. **Anfangs sichtbar** legt je Quelle
den Startzustand fest; ohne Angabe ist sie sichtbar. Bei ausgeschalteter Auswahl
wird dieses Feld ignoriert.

Große Listen werden auf lesbare Seiten verteilt. Vorherige/nächste Seite und
eine Bereichsanzeige erschließen alle ausgewählten Quellen; die Seitenlänge
passt sich Platz, Polar-Anordnung und Schriftgröße an. Auch ohne Quellenauswahl
erhalten Listen über 16 Quellen eine Seitennavigation. Bestehende Listen bis
16 Quellen bleiben bei ausgeschalteter Auswahl ohne zusätzliche Bedienelemente.

Auswahl und Seiten ändern nur die Ansicht: Alle konfigurierten Quellen werden
weiterhin gelesen, aktualisiert und validiert. Die automatische Werteskala
berücksichtigt immer das vollständige Modell einschließlich ausgeblendeter
und anderer Seiten sowie den Nullpunkt. Eine feste Skala bleibt unverändert;
bei Normalisierung gilt weiter 0–100. Es gibt keine Teilsummen oder Restgruppen.
Originalwerte und Einheiten bleiben im Tooltip erhalten.

In Ansichten mit Auswahl oder Seiten bleiben Auswahl und automatische Farben
während einer geöffneten Ansicht anhand der Quellenidentität erhalten, auch
bei Aktualisierung, Sortierung, Themewechsel oder Größenänderung. Die erste
sichtbare Quelle bleibt erhalten, soweit die neue Fenstergröße es zulässt.
Kleine Altlisten ohne Auswahl behalten ihre bisherige Farbzuordnung.
Ein neues Laden startet mit der
gespeicherten Anfangssichtbarkeit. Entfernte Quellen verschwinden aus der Auswahl.
Überlange Quellenmetadaten werden mit einer ausdrücklichen Fehlermeldung
abgewiesen statt still gekürzt; dann Beschriftungen oder Einheiten kürzen.

## Optionaler Min/Max-Vergleich

**Werte anhand Quellen-Min/Max normalisieren** ist zunächst ausgeschaltet.
Aktiviert bildet es jede Quelle mit ihren eigenen Feldern **Min
(Normalisierung)** und **Max (Normalisierung)** auf 0–100 ab:
`100 × (Wert − Min) / (Max − Min)`. Vorgabe ist 0–100, Min muss
kleiner als Max sein. So lassen sich beispielsweise Temperatur
(−20 bis 40 °C), Luftfeuchtigkeit (0 bis 100 %) und Signalqualität
(0 bis 255 LQI) gemeinsam vergleichen.

Radiale Länge, Bogenlänge und Sortierung folgen dem normalisierten Wert.
Die Grafik verwendet dann eine feste Skala 0–100 und kennzeichnet den
normalisierten Vergleich. Gespeicherte manuelle Skalen werden nicht geändert
und gelten wieder, sobald die Normalisierung ausgeschaltet wird.
Nur die gezeichnete Länge wird auf 0–100 begrenzt: Wertbeschriftungen und
Tooltip behalten den Originalwert samt je Quelle gewählter Einheit und
Genauigkeit. Der Tooltip nennt zusätzlich Normalwert und Min/Max-Bereich.
Negative Originalwerte sind bei aktivierter Normalisierung zulässig.
Kachel, IPSView und Live-Vorschauen verwenden dasselbe Quellenmodell.
Ist die Funktion ausgeschaltet, werden die zusätzlichen Min/Max-Felder
ignoriert und die bisherige Darstellung bleibt erhalten.

## Vorübergehende Ladefehler

Können Werte kurzzeitig nicht geladen werden, bleibt die letzte gültige Grafik
in Kachel und IPSView sichtbar. Bei wiederholtem Fehler erscheint ein Hinweis;
das Modul versucht nach 60 Sekunden erneut zu laden. Ohne zuvor geladene Daten
oder bei ungültiger Konfiguration erscheint weiterhin eine Fehlermeldung.

Kachel- und unabhängiger IPSView-Designer gliedern Anordnung, Beschriftung,
Geometrie, Balkeneffekte, Skala und Winkel sowie Schrift und Farben in
einklappbare Bereiche.
Die Live-Vorschau folgt darunter.

## Live-Vorschau

Kachel- und IPSView-Designer zeigen eine SVG-Vorschau für radiale Balken und
konzentrische Bögen. Sie reagiert auf noch nicht gespeicherte Titel-, Quellen-,
Theme-, Skalen- und Geometrieänderungen; Kategoriebeschriftungen verwenden
konfigurierte Namen vor Symcon-Namen, niemals automatisch Variablen-IDs.
**Kacheldesign verwenden** gilt auch für die IPSView-Vorschau. Ohne gültige
Quellen sind Beispieldaten markiert. Wertbeschriftungen übernehmen die wirksame
Einheit und Nachkommastellen der Quellen. Die Vorschau ist schematisch:
Startwinkel und Drehrichtung entsprechen der Kachel: 0° liegt rechts, 90°
oben. Das Raster folgt dem gewählten Winkelumfang und der Drehrichtung; bei
360° bleiben die Rasterlinien vollständige Kreise. Eine zusätzliche
Orientierungshilfe markiert in der Formularvorschau 0°/360°, 90°, 180° und
270° ab dem eingestellten Startpunkt in der gewählten Laufrichtung. Diese
Gradmarken gehören nicht zur Kachel oder zum IPSView-Widget.
Die Vorschau zeigt höchstens sechs anfangs sichtbare Quellen gemäß der
gewählten Sortierung und nennt die angezeigte sowie die gesamte Quellenzahl.
Die vollständige Werteskala wird vor diesem Ausschnitt und unabhängig von der
Anfangsauswahl ermittelt. Auch eine einzelne spät konfigurierte Quelle behält
ihre Farbe und denselben Maßstab. Eine leere Anfangsauswahl wird als solche
angezeigt, nicht durch Beispieldaten ersetzt. Die eigentliche Grafik erschließt
alle Quellen über Auswahl und Seiten.
Hover-Hervorhebung, Animation und das responsive Beschriftungslayout prüfen
Sie an der echten Kachel beziehungsweise im IPSView-Widget.

## Einrichtung

Für alle ECharts-Diagramme genügt ein gemeinsames `EChartsGateway`. Wählen Sie bei weiteren Instanzen das vorhandene Gateway.

Unter **Polar-Quellen** wählen Sie die Variablen. Jede Zeile ist ein eigenständiger Wert, nicht die Änderung eines vorherigen Werts. Die Listenreihenfolge lässt sich verschieben. Eine optionale Kategoriebeschriftung hat Vorrang; ist sie leer, erscheint der aktuelle Symcon-Name. Gleichlautende Beschriftungen sind erlaubt und führen nicht zum Verlust eines Werts. Variablen-IDs dienen nur der internen Zuordnung und werden nicht automatisch angezeigt.

Einheit und Nachkommastellen können aus der Variablendarstellung übernommen oder je Quelle gesetzt werden. Ohne Normalisierung müssen alle Quellen dieselbe effektive Einheit verwenden. Eine Quellenfarbe kann individuell gewählt werden; **Automatisch (Theme)** verwendet die Farbpalette des gewählten Themes.

## Darstellung

Im **Kacheldesigner** und im unabhängigen **IPSView-Design** stehen zur Verfügung:

- **Radiale Balken**: Kategorien liegen um den Kreis; Werte erstrecken sich vom inneren zum äußeren Radius.
- **Konzentrische Bögen**: Kategorien liegen auf getrennten Kreisbahnen; Werte bestimmen die Bogenlänge.
- Konfigurierte oder wertsortierte Reihenfolge, Kategorie- und Wertbeschriftungen, Raster, Balkenbreite, Innen- und Außenradius, Startwinkel, Winkelumfang, Drehrichtung und abgerundete Enden.
- ECharts-Theme, die je Quelle konfigurierte Balkenfarbe und wahlweise Vollfarbe oder Farbverlauf.
- Titelgröße (50–200 %) sowie Farben für Titel, Kategorien, Werteskala,
  Raster und Werte außerhalb der Balken. Werte innerhalb farbiger Balken
  behalten ihre automatische Hell-Dunkel-Kontrastfarbe.

Standardmäßig wird die Werteskala automatisch bestimmt. Bei Auswahl und Seiten bleibt der Maßstab anhand aller Quellen festgelegt; kleine bestehende Listen ohne Auswahl behalten die automatische ECharts-Skala. Negative Werte werden nicht in positive Werte umgerechnet. Bei langen Namen können Beschriftungen in kleinen Kacheln weiterhin überlappen; Kachelgröße und Anzeigeoptionen lassen sich passend wählen.
Der **Winkelumfang** bestimmt, welchen Teil des Kreises die Balken nutzen: 360° ergibt den bisherigen Vollkreis, 180° einen Halbkreis. Werte von 30° bis 360° sind möglich. **Startwinkel** legt den Anfang fest (0° rechts, 90° oben), **Im Uhrzeigersinn** die Laufrichtung. Balken, Hintergrundspuren und Raster nutzen auch bei konzentrischen Bögen denselben Ausschnitt und bleiben nach Größenänderungen darin. Kachel und unabhängiger IPSView-Designer können unterschiedliche Winkelumfänge verwenden; bei **Kacheldesign verwenden** übernimmt IPSView den Kachelwert.
Im **Kacheldesigner** und im unabhängigen **IPSView-Designer** kann die Werteskala alternativ als fester Bereich mit Minimum und Maximum eingestellt werden. Das ist besonders für vergleichbare Anzeigen mit dauerhaft gleicher Skala sinnvoll. Werte außerhalb dieses Bereichs werden im Diagramm abgeschnitten; die Quelldaten bleiben unverändert. Optionale **Hintergrundspuren** zeigen hinter jedem Balken den verfügbaren Skalenbereich. Ihre Farbe kann dem Theme folgen oder frei gewählt werden; die Deckkraft ist einstellbar. Ohne Änderung der Einstellungen bleiben automatische Skala und ausgeblendete Spuren erhalten. Mit **Kacheldesign verwenden** übernimmt IPSView Skala und Spuren der Kachel; nach dem Deaktivieren sind sie unabhängig einstellbar oder können mit der Kopierschaltfläche übernommen werden.
Bei einer festen Skala beginnen Balken und Kreisbögen am eingestellten Minimum; die Beschriftungen zeigen weiterhin die tatsächlichen Quellwerte.
Bei **Balkenfüllung** ist **Vollfarbe** voreingestellt. **Farbverlauf** verwendet die Farbe der jeweiligen Quelle und eine wählbare Endfarbe; **Automatisch (Theme)** führt zur Hintergrundfarbe des Themes. Der Übergang verläuft linear innerhalb der gezeichneten Form, nicht entlang des Polarwerts. Er ist ein Farbeffekt, kein zusätzlicher Wert oder eine zweite Datenreihe. Werte innerhalb eines Verlaufs erhalten einen Kontrastumriss. Kachel und unabhängiges IPSView-Design können unterschiedliche Füllungen und Endfarben verwenden; **Kacheldesign verwenden** übernimmt auch diese Einstellungen.
Die **Balkendeckkraft** lässt sich für Vollfarbe und Farbverlauf von 0 bis 100 % einstellen; 100 % entspricht der bisherigen Darstellung. Sie wirkt nur auf die farbigen Balken, nicht auf Hintergrundspuren oder Beschriftungen. Bei geringer Deckkraft erhalten Werte innerhalb der Balken einen Kontrastumriss. Kachel und unabhängiges IPSView-Design können unterschiedliche Werte verwenden; **Kacheldesign verwenden** übernimmt die Kacheleinstellung.
Die **Balkenkontur** ist standardmäßig ausgeschaltet (0 px). Ihre Breite lässt sich von 1 bis 8 px und ihre Farbe separat einstellen; **Automatisch (Theme)** verwendet die Rahmenfarbe des gewählten Themes. Die Kontur umgibt nur die farbigen Balken, nicht die Hintergrundspuren. Im unabhängigen IPSView-Designer kann sie anders eingestellt werden als in der Kachel; **Kacheldesign verwenden** übernimmt die Kacheleinstellung.
Der **Balkenschatten** ist standardmäßig ausgeschaltet (0 px). Seine Stärke lässt sich von 1 bis 20 px, die Deckkraft von 0 bis 100 % einstellen. Die Farbe kann frei gewählt werden; **Automatisch (Theme)** verwendet die Rahmenfarbe des Themes. Der Schatten betrifft nur farbige Balken, nicht Hintergrundspuren oder Beschriftungen. Im unabhängigen IPSView-Designer kann er anders eingestellt werden; **Kacheldesign verwenden** übernimmt die Kacheleinstellung.
Die **Balkenanimation** lässt sich für Kachel und unabhängiges IPSView-Design getrennt abschalten. Im eingeschalteten Zustand sind die Dauer beim ersten Anzeigen (Standard 350 ms) und bei Wertänderungen (Standard 500 ms) jeweils von 0 bis 3000 ms einstellbar. 0 ms schaltet den jeweiligen Übergang aus. Start und Aktualisierung besitzen zudem getrennte Bewegungskurven und feste Verzögerungen (0–3000 ms); ohne Änderung gilt cubicInOut ohne Verzögerung. Die Verzögerung betrifft alle Balken zugleich, nicht jeden einzeln. Wenn das Anzeigegerät „Bewegung reduzieren“ vorgibt, bleiben Animationen unabhängig von diesen Einstellungen ausgeschaltet. **Kacheldesign verwenden** übernimmt die Kacheleinstellung.
Die **Balkenhervorhebung** lässt sich auf **Standard (ECharts)**, **Balken hervorheben**, **Balken fokussieren und andere abdunkeln** oder **Aus** stellen. Standard verändert das bisherige Verhalten nicht. Die beiden aktiven Modi zeichnen beim Berühren oder Überfahren eine kontrastierende Kontur; beim Fokussieren werden andere Balken zusätzlich abgedunkelt. Ein Antippen hält den gewählten Balken hervorgehoben, erneutes Antippen löst die Auswahl. Die Hervorhebungsfarbe kann vorgegeben werden oder folgt automatisch der Theme-Textfarbe. Gleichnamige Quellen bleiben intern anhand ihrer Variablen-ID getrennt; diese ID erscheint nicht im Diagramm. Kachel und unabhängiges IPSView-Design können unterschiedliche Modi verwenden, während **Kacheldesign verwenden** auch diese Einstellung übernimmt.
Die **Position der Wertbeschriftung** lässt sich in beiden Designern auf **Mitte des Balkens** (Standard), **Innen am Anfang**, **Innen am Ende**, **Außerhalb des Balkens**, **Am Anfang** oder **Am Ende** setzen. Bei radialen Balken sind Anfang und Ende die innere beziehungsweise äußere Radiuskante; bei konzentrischen Bögen sind es Anfang und Ende des Bogens. **Außerhalb des Balkens** platziert den Wert direkt hinter dessen Ende, aber weiterhin im Diagrammbereich. Dafür rücken Balken und Kategoriebeschriftungen etwas auseinander. Die Schrift bleibt waagerecht; innerhalb des Balkens wird sie abhängig von seiner Farbe automatisch hell oder dunkel gewählt, außerhalb nutzt sie die Textfarbe des Diagramm-Themes. Bei vielen Quellen, sehr kurzen Balken oder langen Werten können Beschriftungen dennoch überlappen; dann Mitte wählen, die Kachel vergrößern oder Werte ausblenden.
**Kategorie-, Wert- und Skalenschriftgröße** sind in beiden Designern getrennt von 8 bis 24 px einstellbar (Standard jeweils 10 px). Im IPSView-Widget nutzt die Kategoriebeschriftung bei ausreichend Platz eine an Namen und Schriftgröße angepasste Breite; in schmalen Widgets wird sie zugunsten eines lesbaren Kreises weiterhin gekürzt. Die Kachel bleibt kompakt. Bei konzentrischen Bögen bleiben Kategorien vor den Balken sichtbar; ein Hintergrundumriss verbessert ihren Kontrast auch über farbigen Spuren. Bei Größenänderungen werden Kategorien nicht allein wegen eines automatisch gewählten Beschriftungsintervalls übersprungen; tatsächlich überlappende Beschriftungen können weiterhin ausgeblendet werden. Werte an abgerundeten Bogenenden werden weiter in die farbige Fläche gerückt. Bei kurzen Bögen oder vielen Quellen kann ein Wert weiterhin zu wenig Platz haben; die Position **Mitte des Balkens** oder eine kleinere Schriftgröße hilft dann.
Der Außenradius aus dem Designer ist eine Obergrenze: In kleineren Kacheln passt sich der Kreis mit den Beschriftungsabständen an den verfügbaren Platz an. Sind die Balken sehr kompakt und ihre Werte eingeblendet, entfallen die Zahlen der Werteskala, damit die Balkenwerte lesbar bleiben; Raster und Balken bleiben sichtbar. Bei einer Größenänderung wird das Diagramm neu eingepasst.

Die Kachel aktualisiert sich bei Änderungen einer Quellvariablen. Optional erzeugt das Modul eine eigenständige WebContent-Variable `IPSViewBarPolar` für ein IPSView-HTML-Widget. Deren HTML-Dokument bleibt bei Wertänderungen bestehen; nur der Diagrammzustand wird übertragen. Für IPSView sind Hintergrundfarbe und Deckkraft einstellbar.

**Kacheldesign verwenden** ist in IPSView zunächst aktiv. Dann sind die eigenen Designfelder und **Kacheldesign nach IPSView kopieren und unabhängig bearbeiten** deaktiviert. Nach Abschalten des Schalters kann das zuletzt gespeicherte Kacheldesign einmalig kopiert und anschließend unabhängig geändert werden. Quellen und aktuelle Werte bleiben gemeinsam.

Die öffentliche Funktion `ECBP_GetPolarData($InstanzID)` liefert das aktuelle Chart-Modell als JSON. Sie liest Werte, verändert aber keine Quellvariablen.
