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
- Konfigurierte oder wertsortierte Reihenfolge, Kategorie- und Wertbeschriftungen, Raster, Balkenbreite, Innen- und Außenradius, Startwinkel, Drehrichtung und abgerundete Enden.
- ECharts-Theme und die je Quelle konfigurierte Balkenfarbe.

Die Werteskala bestimmt ECharts automatisch. Negative Werte werden nicht in positive Werte umgerechnet. Bei vielen Quellen oder langen Namen können Beschriftungen in kleinen Kacheln überlappen; Kachelgröße und Anzeigeoptionen lassen sich passend wählen.
Wertbeschriftungen bleiben waagerecht lesbar; Kategoriebeschriftungen stehen mit Abstand zum äußeren Ring.

Die Kachel aktualisiert sich bei Änderungen einer Quellvariablen. Optional erzeugt das Modul eine eigenständige WebContent-Variable `IPSViewBarPolar` für ein IPSView-HTML-Widget. Deren HTML-Dokument bleibt bei Wertänderungen bestehen; nur der Diagrammzustand wird übertragen. Für IPSView sind Hintergrundfarbe und Deckkraft einstellbar.

**Kacheldesign verwenden** ist in IPSView zunächst aktiv. Dann sind die eigenen Designfelder und **Kacheldesign nach IPSView kopieren und unabhängig bearbeiten** deaktiviert. Nach Abschalten des Schalters kann das zuletzt gespeicherte Kacheldesign einmalig kopiert und anschließend unabhängig geändert werden. Quellen und aktuelle Werte bleiben gemeinsam.

Die öffentliche Funktion `ECBP_GetPolarData($InstanzID)` liefert das aktuelle Chart-Modell als JSON. Sie liest Werte, verändert aber keine Quellvariablen.
