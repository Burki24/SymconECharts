# EChartsBarPolar

`EChartsBarPolar` stellt aktuelle Zahlenwerte als Polar-Balken dar. Die Kachel zeigt wahlweise radial nach außen gerichtete Balken oder konzentrische Kreisbögen. Das Modul akzeptiert 1 bis 16 eindeutige numerische Symcon-Variablen mit derselben effektiven Einheit. Ein Archiv ist nicht nötig.

## Einrichtung

Für alle ECharts-Diagramme genügt ein gemeinsames `EChartsGateway`. Wählen Sie bei weiteren Instanzen das vorhandene Gateway.

Unter **Polar-Quellen** wählen Sie die Variablen. Jede Zeile ist ein eigenständiger Wert, nicht die Änderung eines vorherigen Werts. Die Listenreihenfolge lässt sich verschieben. Eine optionale Kategoriebeschriftung hat Vorrang; ist sie leer, erscheint der aktuelle Symcon-Name. Gleichlautende Beschriftungen sind erlaubt und führen nicht zum Verlust eines Werts. Variablen-IDs dienen nur der internen Zuordnung und werden nicht automatisch angezeigt.

Einheit und Nachkommastellen können aus der Variablendarstellung übernommen oder je Quelle gesetzt werden. Alle Quellen müssen dieselbe effektive Einheit verwenden. Eine Quellenfarbe kann individuell gewählt werden; **Automatisch (Theme)** verwendet die Farbpalette des gewählten Themes.

## Darstellung

Im **Kacheldesigner** und im unabhängigen **IPSView-Design** stehen zur Verfügung:

- **Radiale Balken**: Kategorien liegen um den Kreis; Werte erstrecken sich vom inneren zum äußeren Radius.
- **Konzentrische Bögen**: Kategorien liegen auf getrennten Kreisbahnen; Werte bestimmen die Bogenlänge.
- Konfigurierte oder wertsortierte Reihenfolge, Kategorie- und Wertbeschriftungen, Raster, Balkenbreite, Innen- und Außenradius, Startwinkel, Winkelumfang, Drehrichtung und abgerundete Enden.
- ECharts-Theme und die je Quelle konfigurierte Balkenfarbe.

Standardmäßig bestimmt ECharts die Werteskala automatisch. Negative Werte werden nicht in positive Werte umgerechnet. Bei vielen Quellen oder langen Namen können Beschriftungen in kleinen Kacheln überlappen; Kachelgröße und Anzeigeoptionen lassen sich passend wählen.
Der **Winkelumfang** bestimmt, welchen Teil des Kreises die Balken nutzen: 360° ergibt den bisherigen Vollkreis, 180° einen Halbkreis. Werte von 30° bis 360° sind möglich. **Startwinkel** legt den Anfang fest, **Im Uhrzeigersinn** die Laufrichtung. Balken, Hintergrundspuren und Raster nutzen denselben Ausschnitt. Kachel und unabhängiger IPSView-Designer können unterschiedliche Winkelumfänge verwenden; bei **Kacheldesign verwenden** übernimmt IPSView den Kachelwert.
Im **Kacheldesigner** und im unabhängigen **IPSView-Designer** kann die Werteskala alternativ als fester Bereich mit Minimum und Maximum eingestellt werden. Das ist besonders für vergleichbare Anzeigen mit dauerhaft gleicher Skala sinnvoll. Werte außerhalb dieses Bereichs werden im Diagramm abgeschnitten; die Quelldaten bleiben unverändert. Optionale **Hintergrundspuren** zeigen hinter jedem Balken den verfügbaren Skalenbereich. Ihre Farbe kann dem Theme folgen oder frei gewählt werden; die Deckkraft ist einstellbar. Ohne Änderung der Einstellungen bleiben automatische Skala und ausgeblendete Spuren erhalten. Mit **Kacheldesign verwenden** übernimmt IPSView Skala und Spuren der Kachel; nach dem Deaktivieren sind sie unabhängig einstellbar oder können mit der Kopierschaltfläche übernommen werden.
Bei einer festen Skala beginnen Balken und Kreisbögen am eingestellten Minimum; die Beschriftungen zeigen weiterhin die tatsächlichen Quellwerte.
Die **Position der Wertbeschriftung** lässt sich in beiden Designern auf **Mitte des Balkens** (Standard), **Innen am Anfang** oder **Innen am Ende** setzen. Bei radialen Balken sind Anfang und Ende die innere beziehungsweise äußere Radiuskante; bei konzentrischen Bögen sind es Anfang und Ende des Bogens. Die Schrift bleibt waagerecht und wird abhängig von der Balkenfarbe automatisch hell oder dunkel gewählt. Bei sehr kurzen oder schmalen Balken können randnahe Beschriftungen dennoch überstehen; dann Mitte wählen, die Kachel vergrößern oder Werte ausblenden. Kategoriebeschriftungen stehen mit Abstand zum äußeren Ring.
Der Außenradius aus dem Designer ist eine Obergrenze: In kleineren Kacheln passt sich der Kreis mit den Beschriftungsabständen an den verfügbaren Platz an. Sind die Balken sehr kompakt und ihre Werte eingeblendet, entfallen die Zahlen der Werteskala, damit die Balkenwerte lesbar bleiben; Raster und Balken bleiben sichtbar. Bei einer Größenänderung wird das Diagramm neu eingepasst.

Die Kachel aktualisiert sich bei Änderungen einer Quellvariablen. Optional erzeugt das Modul eine eigenständige WebContent-Variable `IPSViewBarPolar` für ein IPSView-HTML-Widget. Deren HTML-Dokument bleibt bei Wertänderungen bestehen; nur der Diagrammzustand wird übertragen. Für IPSView sind Hintergrundfarbe und Deckkraft einstellbar.

**Kacheldesign verwenden** ist in IPSView zunächst aktiv. Dann sind die eigenen Designfelder und **Kacheldesign nach IPSView kopieren und unabhängig bearbeiten** deaktiviert. Nach Abschalten des Schalters kann das zuletzt gespeicherte Kacheldesign einmalig kopiert und anschließend unabhängig geändert werden. Quellen und aktuelle Werte bleiben gemeinsam.

Die öffentliche Funktion `ECBP_GetPolarData($InstanzID)` liefert das aktuelle Chart-Modell als JSON. Sie liest Werte, verändert aber keine Quellvariablen.
