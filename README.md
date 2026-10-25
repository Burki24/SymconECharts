# SymconECharts

SymconECharts stellt Symcon-Werte und ausgewählte Objektbeziehungen als
responsive Apache-ECharts-Diagramme in nativen Symcon-Kacheln und optionalen
IPSView-HTML-Widgets dar.
Die Library enthält Gauges für einzelne und mehrere Werte, historische und
live fortgeschriebene Zeitreihen, aktuelle Kategorienvergleiche, historische
Balken, Waterfall- und Polar-Diagramme, Kreisdiagramme für aktuelle Werte sowie
historische Streu- und Bubble-Diagramme, eine Tages-/Wochentag-Stunden-Heatmap
eine lesende IP-Symcon-Objekthierarchie als Tree, einen allgemeinen Graphen
für ausdrücklich verbundene Symcon-Objekte sowie einen separaten Graphen
für exportierte Zigbee2MQTT-Netzwerkkarten sowie Treemap und Sunburst mit
manuell festgelegten Gruppen und aktuellen numerischen Variablenwerten.
Der allgemeine Graph bietet optional gerichtete Beziehungen und Gewichte aus
festen Zahlen oder aktuellen Variablenwerten mit festem Bezug für die Linienbreite.
Treemap und Sunburst erlauben optionale Gruppenpfade mit bis zu acht Ebenen
einschließlich Quellvariable; bestehende Gruppe-/Untergruppe-Zuordnungen bleiben nutzbar.
Beide Familien bieten außerdem optionale Min/Max-normalisierte Blattgewichte
und eigene Originaleinheiten sowie Nachkommastellen je Quelle. Originalwerte
bleiben sichtbar; normalisierte Gruppengewichte sind keine physikalischen Summen.
GaugeMulti unterstützt bis zu 32 Quellen mit optionaler Quellenauswahl und
responsiven Seiten; individuelle Wertebereiche und Einheiten bleiben erhalten.
Scatter/Bubble unterstützt bis zu 16 benannte X/Y-Vergleichsgruppen mit
optionaler Größenquelle je Gruppe. Alle Gruppen teilen sich das vorhandene
Archivbudget; bestehende einzelne Punktwolken bleiben unverändert nutzbar.
Candlestick bietet optional ein bis vier benannte Archiv-Vergleichspanels mit
getrennten Werteskalen und gemeinsamem Zeitzoom; Einzelquellen bleiben nutzbar.
Feste Min-/Max-Bezüge für Bubble-Größen und Originalwerte im Tooltip erlauben
stabile Größenvergleiche über verschiedene Zeiträume.
Tree und TreeSystemHealth unterstützen bis zu zwölf Nachkommensebenen des
Symcon-Objektbaums; bestehende Tiefen- und Knoteneinstellungen bleiben erhalten.
Parallelkoordinaten verbinden archivierte Variablenwerte je gemeinsamem
Stunden- oder Tagesintervall, wahlweise auch in festen historischen Von-/Bis-Zeiträumen.
Parallel unterstützt zwei bis 16 Achsen mit horizontal verschiebbarer Ansicht;
jede Achse kann automatisch oder mit eigenen festen Min-/Max-Grenzen skalieren.
Zusätzlich ist je Quelle Automatisch, Mittelwert, Minimum, Maximum oder
Zählerverbrauch wählbar. Explizite Kennzahlen müssen zum Archivtyp passen.
Alle Achsen teilen sich weiterhin höchstens 10000 Archivaggregate.
Sankey zeigt aktuelle Mengenflüsse zwischen manuell angelegten Knoten.
Je Verbindung lassen sich negative Werte ausdrücklich als umgekehrte Flüsse
darstellen; Originalwerte bleiben im Tooltip erhalten und die Struktur bleibt kreisfrei.
Optional ergänzen ausdrücklich ausgewählte Knoten ihre Zu-/Abflussdifferenz
als klar gekennzeichneten berechneten Fluss, nicht als Messung oder bewiesenen Verlust.
Calendar stellt archivierte Tageskennzahlen als farbigen Jahres-, Quartals-
oder Monatskalender sowie für feste historische Von-/Bis-Zeiträume dar,
jeweils mit Navigation zu früheren Zeiträumen.
Calendar und Heatmap bieten Mittelwert, Minimum und Maximum für Standardarchive
oder ausdrücklich ausgewählten Zählerverbrauch für Zählerarchive.
Heatmap ergänzt ihre bisherigen Kalenderzeiträume um benutzerdefinierte Dauern
und feste historische Von-/Bis-Grenzen mit Navigation. Die neuen Fenster
umfassen höchstens 366 × 24 Stunden und berücksichtigen nur vollständig
enthaltene, abgeschlossene Archivstunden.
Radar vergleicht aktuelle Messwerte von bis zu 16 manuell angelegten Gruppen
auf 3–10 gemeinsam festgelegten Achsen mit eigenen Wertebereichen und Einheiten.
Jede Messgröße lässt sich optional invers darstellen: Maximum innen, Minimum außen.
Optional können Gruppen einzelne Messgrößen auslassen; fehlende Zuordnungen
werden gekennzeichnet und ohne gefüllte Fläche dargestellt.
Eine optionale Gruppenauswahl erhält die Achsenskalen und die gewählte Ansicht
bei Aktualisierungen.
Boxplot vergleicht die Verteilung abgeschlossener Archiv-Stundenmittelwerte.
Optional sind 1,5-IQR-Whisker mit separaten Ausreißerpunkten wählbar;
Minimum-/Maximum-Enden bleiben der Standard.
Zusätzlich zu rollierenden Zeiträumen erlaubt Boxplot feste historische
Von-/Bis-Zeiträume in der Symcon-Zeitzone bei unveränderten Archivbudgets.
Funnel vergleicht aktuelle, nichtnegative Stufenwerte derselben Einheit.
Bis zu 32 Quellen erhalten optional eine Quellenauswahl und responsive
Stufennavigation; die vollständige Breitenskala bleibt auch auf Teilseiten erhalten.
PictorialBar vergleicht aktuelle, nichtnegative Werte als Symbolbalken.
Quellen lassen sich dort auch gesammelt nach Objektzweig, Variablenname und
Einheit suchen, auswählen und zur bestehenden Liste hinzufügen.
Pie, BarCategory, BarPolar und PictorialBar können Quellen optional anhand
eigener Min/Max-Grenzen auf 0–100 normalisieren. So sind relative Vergleiche
unterschiedlicher Messgrößen möglich; Originalwerte und Einheiten bleiben
im Tooltip erhalten. Ohne diese Auswahl gelten die bisherigen Rohwertvergleiche.
Pie unterstützt bis zu 100 Quellen. Eine optionale erweiterte Quellenauswahl
bietet Anfangssichtbarkeit, Alle/Umkehren und eine bei Aktualisierung erhaltene
Auswahl. Tooltips unterscheiden Auswahlanteil und Anteil aller Quellen.
Candlestick zeigt ersten und letzten protokollierten Wert sowie Minimum
und Maximum abgeschlossener 15-/30-Minuten-, Stunden-, Tages- oder
Kalenderwochenintervalle, wahlweise auch in
festen historischen Von-/Bis-Zeiträumen mit unveränderten Rohwertbudgets.
StateTimeline zeigt archivierte Boolean-/Integer-Zustände als farbige
Zeitabschnitte mit eigenen Zustandsnamen und ausdrücklich unbekannten Anfängen.
Zusätzlich sind feste historische Von-/Bis-Zeiträume mit sekundengenauen
Grenzen und unveränderten Vorgängerregeln wählbar.
Graph verbindet bis zu 300 manuell ausgewählte Symcon-Objekte mit bis zu
1.000 ungerichteten Kanten. Große Graphen lassen sich zoomen und verschieben;
die Ansicht bleibt bei Datenaktualisierungen erhalten.

Boxplot, Parallel, Candlestick, ThemeRiver und StateTimeline bieten neben
ihren festen Zeiträumen auch rollierende Zeitspannen mit 1–1000 Minuten,
Stunden, Tagen oder Wochen. Die jeweiligen Archiv- und Intervallgrenzen bleiben
wirksam; Einzelheiten stehen in den Modulbeschreibungen.

Alle Diagrammformulare zeigen eine unmittelbar aktualisierte SVG-Vorschau
im Kachel- und IPSView-Designer. Sie reagiert auf noch nicht gespeicherte
Titel-, Quellen- und Designänderungen; die endgültige responsive Darstellung
prüfen Sie in der Kachel beziehungsweise im IPSView-Widget. Historische
Vorschauen verwenden gekennzeichnete Beispieldaten statt einer Archivabfrage.
Ein konfigurierter Diagrammtitel bleibt auch in der Kachel sichtbar und steht
unter dem Symcon-Kachelnamen samt Vergrößerungsbutton. IPSView zeigt den
Diagrammtitel ohne diesen Kachel-Kopfbereich.

## Voraussetzungen

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5;
- numerische Integer- oder Float-Variablen für wertbasierte Diagramme;
  Tree und Graph können auch andere Symcon-Objekte anzeigen;
- ein Symcon-Archiv für historische Zeitreihen; der Echtzeitmodus funktioniert
  auch ohne aktiviertes Archiv;
- IPSView nur bei Verwendung der optionalen IPSView-Ausgabe.

## Enthaltene Module

Folgende Module sind enthalten:

- __EChartsCalendarPie__ ([Dokumentation](EChartsCalendarPie/README.md))

  Ein Monatskalender mit Kreisdiagrammen je abgeschlossenem Tag. Wählbar sind
  Tagesmittelwerte mehrerer Archivvariablen, Tagesverbrauch von Zählern oder
  Zeitanteile archivierter Zustände. Fehlende Daten und unbekannte Zeiten
  bleiben erkennbar; Kachel und IPSView besitzen eigene Designer und Vorschauen.
  Numerische Teilarchive lassen sich optional als klar gekennzeichnete
  Teilkreise darstellen; der strenge Standard bleibt erhalten.

- __EChartsStateTimeline__ ([Dokumentation](EChartsStateTimeline/README.md))
  Archivierte Boolean-/Integer-Zustände von bis zu 32 Quellen als farbige
  Zeitabschnitte mit Zeilennavigation für große Listen.
  Eigene Zustandsnamen/-farben, ausdrücklich unbekannte Anfänge und Zoom;
  getrennte Kachel- und IPSView-Designer ohne Mittelwertbildung.

Die Namen in der Modulauswahl entsprechen den hier aufgeführten Modulnamen
ohne Leerzeichen, etwa `EChartsPictorialBar`. Namen vorhandener Instanzen
können unabhängig davon individuell vergeben werden.

- __EChartsThemeRiver__ ([Dokumentation](EChartsThemeRiver/README.md))
  Nichtnegative Stunden-/Tagesmittelwerte aus Standardarchiven oder ausdrücklich
  ausgewählter Zählerverbrauch aus 1–16 Archivvariablen derselben Einheit
  als farbige Bänder. Archivlücken bleiben sichtbar;
  Kachel und IPSView besitzen getrennte Designer und Vorschauen.
  Eine optionale Bandauswahl erhält die Quell-IDs und ursprünglichen
  Archivlücken; das gemeinsame Ladebudget beträgt 16.000 Aggregate.
  Neben rollierenden Dauern sind feste historische Von-/Bis-Zeiträume wählbar.

- __EChartsPictorialBar__ ([Dokumentation](EChartsPictorialBar/README.md))
  Aktuelle Werte aus 1–100 Variablen derselben Einheit als einzelne oder
  wiederholte Grundformen, Icons oder eigene SVGs. Kategoriennavigation und
  anpassbare Bezeichnungsspalte; getrennte Designer für Kachel und IPSView.

- __EChartsCandlestick__ ([Dokumentation](EChartsCandlestick/README.md))
  Eine Standard-Archivvariable als Stunden- oder Tageskerzen über 24 Stunden,
  7 oder 30 Tage, eine benutzerdefinierte Dauer oder feste historische
  Von-/Bis-Zeiträume. Fehlende Intervalle bleiben leer; Kachel und IPSView
  besitzen getrennte Designer.

- __EChartsFunnel__ ([Dokumentation](EChartsFunnel/README.md))
  Manuell benannte Stufen aus 1–32 aktuellen Variablenwerten derselben Einheit
  mit optionaler Quellenauswahl und Stufennavigation.
  Listenreihenfolge oder Wertsortierung, getrennte Designer für Kachel und IPSView.

- __EChartsBoxplot__ ([Dokumentation](EChartsBoxplot/README.md))
  Verteilungen aus 1–32 Standard-Archivvariablen derselben Einheit über
  24 Stunden, 7 oder 30 Tage. Jede Box zeigt Quartile und Median der
  Stundenmittelwerte; ihre Enden zeigen Minimum und Maximum.
  Kachel und IPSView besitzen getrennte Designer und eine automatische
  Kategoriennavigation bei Platzbedarf.

- __EChartsRadar__ ([Dokumentation](EChartsRadar/README.md))
  Aktuelle Vergleichsgruppen auf 3–10 gemeinsamen Achsen. Die Radien zeigen den
  relativen Stand im jeweiligen Wertebereich; Tooltips enthalten Originalwerte.
  Kachel und IPSView besitzen getrennte Designer und aktuelle Vorschauen.

- __EChartsCalendar__ ([Dokumentation](EChartsCalendar/README.md))
  Jahreskalender für Tagesmittelwerte, -minima, -maxima oder Zählerverbrauch
  einer numerischen Archivvariable.
  Fehlende Tage bleiben leer; die Jahresnavigation verändert keine
  Konfiguration. Kachel und IPSView besitzen getrennte Designer.

- __EChartsSankey__ ([Dokumentation](EChartsSankey/README.md))
  Aktuelle Mengenflüsse zwischen manuellen Knoten und Verbindungen. Die
  Verbindungsbreite folgt einer numerischen Symcon-Variable. Kachel und
  IPSView bieten getrennte Designer.
  Optional sind eine automatische gemeinsame Quelleinheit oder eine geprüfte
  manuelle Einheit aus nativen Symcon-Darstellungen und Legacy-Profilen wählbar.
  Die bisherige ungeprüfte Anzeigeeinheit bleibt Standard; Werte werden nicht umgerechnet.

- __EChartsGateway__ ([Dokumentation](EChartsGateway/README.md))
  Gemeinsame Zentrale als Splitter. Sie stellt den Chartfamilien aktuell den
  validierten Zugriff auf numerische Momentanwerte sowie normalisierte rohe
  und aggregierte Archivwerte bereit. Identische Archivabfragen werden kurz
  und begrenzt zwischengespeichert. Weitere gemeinsame Dienste folgen bei
  belegtem Bedarf.
  Das Gateway benötigt keine eigene I/O-Instanz.

- __EChartsGaugeSingle__ ([Dokumentation](EChartsGaugeSingle/README.md))
  Gerätemodul für ein einzelnes, unabhängig konfigurierbares Gauge-Diagramm
  mit genau einer numerischen Quellvariable. Vier responsive Layout-Presets
  und sieben unabhängig wählbare Theme-Modi werden als native Symcon-Kachel
  ausgegeben und bei Wertänderungen aktualisiert; Zeigerform einschließlich
  eines abgesicherten pfadbasierten SVG-Imports, Skalenbogen und
  optionale Gauge-Farben lassen sich im Kacheldesigner mit live aktualisierter
  SVG-Vorschau festlegen. Die am Zeigerdrehpunkt gebundene Nabe kann als Kreis,
  Ring oder validiertes eigenes SVG gestaltet beziehungsweise ausgeblendet
  werden. Eine optionale runde oder dem Skalenbogen folgende Zifferblattplatte
  besitzt eine eigene Füllung einschließlich linearer und radialer
  Farbverläufe, ein sicher importiertes und zugeschnittenes SVG-Motiv,
  Kontur, Größe und Schatten. Farbige Wertebereiche, frei abstimmbare Skalen,
  responsive Positionierung, elementweise Sichtbarkeit und ein gestaltbarer
  Wertekasten vervollständigen den Designer. Die Titelposition ist oben oder
  unten wählbar; optional übernimmt Gauge Single Wertebereich, Einheit und
  Nachkommastellen aus der nativen Variablendarstellung beziehungsweise einem
  kompatiblen Legacy-Profil.
  Ein optionales, separat platzierbares WebContent-Widget in IPSView verwendet
  dieselbe Datenkonfiguration und denselben Renderer. Sein Design erbt
  standardmäßig das Kacheldesign oder kann vollständig unabhängig gestaltet
  werden. Laufzeitwerte aktualisieren das bestehende Diagramm über den
  gemeinsamen Gateway-Transport, ohne das WebContent-Dokument neu zu laden.

- __EChartsGaugeMulti__ ([Dokumentation](EChartsGaugeMulti/README.md))
  Gerätemodul für zusammengesetzte Gauge-Darstellungen mit 2 bis 32
  Datenquellen. Jede Quelle besitzt eine
  eigene Beschriftung, Skala, Einheit und Formatierung. Multi Title,
  Ringraster, konzentrische Ringe und ein Wetterstations-Instrumentenpanel
  sind als responsive Vorlagen mit gemeinsamem Design implementiert.
  Wertebereich, Einheit und
  Nachkommastellen können je Quelle optional aus deren Variablendarstellung
  übernommen werden.

- __EChartsGaugeTacho__ ([Dokumentation](EChartsGaugeTacho/README.md))
  Responsives Tacho-Cockpit für 2 bis 5 Quellen. Jedes Instrument kann Zeiger,
  Nabe, Skala, Farben und Zifferblatt einschließlich eigener SVG-Assets
  unabhängig vom gemeinsamen Grunddesign überschreiben.

- __EChartsGaugeChronograph__ ([Dokumentation](EChartsGaugeChronograph/README.md))
  Chronograph mit einem Hauptinstrument und bis zu vier eingebetteten
  Nebeninstrumenten. Für jede der 2 bis 5 Quellen steht dieselbe individuelle
  Design- und SVG-Konfiguration wie beim Tacho zur Verfügung.

- __EChartsTimeSeries__ ([Dokumentation](EChartsTimeSeries/README.md))
  Historische Chartfamilie für 1 bis 32 numerische Quellen, mit scrollbarer
  Reihenauswahl und anfänglicher Sichtbarkeit je Quelle. Das gemeinsame
  Punktbudget ist bis 32.000 einstellbar (Standard 2.000, maximal 2.000 je Reihe).
  Rohwerte bleiben ausdrücklich wählbar; alternativ stehen automatische oder
  feste Symcon-Aggregationsstufen, Linien und Flächen, bis zu acht automatisch
  oder explizit links/rechts angeordnete Einheitengruppen, Punktbudget,
  konfigurierbare Unterbrechungen bei zeitlichen Datenlücken, Tooltip und einen
  eigenen Kacheldesigner für Legende, Zoom,
  Linien, Flächen, Raster, Achsen, Schriftgrößen und Farben in einer nativen
  Kachel bereit. Ein
  eigener Echtzeitmodus zeichnet auch nicht archivierte Variablen ab dem
  Öffnen der Kachel fort. Optional kann jede Reihe Linie, Datenpunkte und
  Flächenfüllung einschließlich Verlauf oder sicher importiertem SVG-Muster
  individuell gestalten. Quellenbezogene Referenzlinien und Wertebereiche
  markieren Grenz-, Ziel- und Komfortwerte in beiden Ausgabewegen.

- __EChartsBarCategory__ ([Dokumentation](EChartsBarCategory/README.md))
  Vergleicht die aktuellen Werte von 1 bis 100 numerischen Variablen mit einer
  gemeinsamen Einheit. Einfache, gruppierte oder gestapelte vertikale und
  horizontale Balken, konfigurierbare Reihenfolge, individuelle Farben,
  Live-Aktualisierung sowie getrennte Kachel- und IPSView-Designs gehören zum
  Category-Bar-Vertrag. Große Kategorienlisten besitzen eine Navigation.

- __EChartsBarHistory__ ([Dokumentation](EChartsBarHistory/README.md))
  Stellt rohe oder aggregierte Archivwerte von 1 bis 32 numerischen Variablen
  als vertikale Balken auf einer Zeitachse dar. Mehrere Reihen stehen
  nebeneinander; verschiedene Einheiten erhalten eigene Wertachsen. Zeitraum,
  Punktbudget, Beschriftung, Reduzierer, Farbe und Kacheldesign sind konfigurierbar.
  Der Zeitraum kann in Kachel und IPSView mit Regler oder Mausrad gezoomt werden.
  IPSView kann optional einen eigenen Zeitraum und ein eigenes Design verwenden;
  eine Begrenzung von Rohwerten wird sichtbar gemeldet.
  Die scrollbare Legende erlaubt Reihenauswahl und Anfangssichtbarkeit.
  Das Gesamtpunktbudget reicht bis 32.000 (Standard weiterhin 1.000,
  höchstens 2.000 je Quelle); ausgeblendete Reihen behalten ihren Budgetanteil.

- __EChartsBarWaterfall__ ([Dokumentation](EChartsBarWaterfall/README.md))
  Zeigt einen aktuellen Startwert und bis zu 99 geordnete Änderungen mit
  Vorzeichen als Waterfall-Diagramm. Die Endsumme wird berechnet; ein Archiv
  ist nicht erforderlich. Responsive Kategoriennavigation erhält die vollständige
  Bilanz und Werteskala. Kachel und IPSView können getrennt gestaltet werden.
  Optional lassen sich berechnete Zwischensummen und ausdrücklich unabhängige
  Bilanzabschnitte durch absolute Neustartwerte ergänzen. Bei Neustarts wird
  die Endsumme klar als Abschluss des letzten Abschnitts gekennzeichnet.

- __EChartsBarPolar__ ([Dokumentation](EChartsBarPolar/README.md))
  Vergleicht 1 bis 100 aktuelle numerische Werte als radiale Balken oder
  konzentrische Bögen. Optionale Quellenauswahl und responsive Seiten erhalten
  die vollständige Werteskala. Reihenfolge, Farben und Polar-Geometrie lassen
  sich für Kachel und IPSView getrennt gestalten.

- __EChartsPie__ ([Dokumentation](EChartsPie/README.md))
  Zeigt aktuelle Anteile aus 1 bis 100 Quellen als Kreis, Donut oder
  Nightingale-Rose mit optionaler erweiterter Quellenauswahl. Kachel und IPSView besitzen eigene Designer und
  Live-Vorschauen; ohne Normalisierung werden negative Werte sichtbar abgewiesen.

- __EChartsScatter__ ([Dokumentation](EChartsScatter/README.md))
  Verknüpft X und Y aus zwei Archivvariablen zu einem Streudiagramm; eine
  dritte Quelle steuert optional die Bubble-Größe. Ein Punkt entsteht nur bei
  exakt gemeinsamem abgeschlossenem Archivintervall, ohne Interpolation.
  Kachel und IPSView besitzen eigene Designer und Live-Vorschauen.

- __EChartsHeatmap__ ([Dokumentation](EChartsHeatmap/README.md))
  Zeigt abgeschlossene stündliche Archivwerte einer numerischen Variable als
  sieben datierte Tage oder über längere Kalenderzeiträume zusammengefasste
  Wochentage. Wählbar sind Mittelwert, Minimum, Maximum und Zählerverbrauch.
  Kachel und optionales IPSView-Widget besitzen Zeitraum-Navigation,
  getrennte Designer und Live-Vorschauen.

- __EChartsTree__ ([Dokumentation](EChartsTree/README.md))
  Zeigt die Kategorien, Instanzen und Variablen unter einer gewählten
  Kategorie, Instanz oder der obersten Symcon-Ebene als begrenzten,
  interaktiven Baum ohne Zustandsbewertung. Kachel und optionales IPSView-Widget
  besitzen getrennte Designer und Live-Vorschauen.

- __EChartsTreeSystemHealth__ ([Dokumentation](EChartsTreeSystemHealth/README.md))
  Ergänzt die lesende Objekthierarchie um ausdrücklich gewählte Zustandsvariablen
  mit Warn- und Kritisch-Schwellen. Eltern zeigen den höchsten Zustand ihrer
  sichtbaren Nachkommen; mehrere Variablen lassen sich per Suche übernehmen.

- __EChartsGraph__ ([Dokumentation](EChartsGraph/README.md))
  Verknüpft 2 bis 80 frei ausgewählte Symcon-Objekte über 1 bis 200
  ausdrücklich konfigurierte Kanten. Kachel und optionales IPSView-Widget
  besitzen getrennte Designer und Live-Vorschauen. Zigbee-Netzwerkkarten
  gehören nicht zu dieser allgemeinen Instanz.

- __EChartsGraphZ2M__ ([Dokumentation](EChartsGraphZ2M/README.md))
  Zeigt die zuletzt exportierte Zigbee2MQTT-Netzwerkkarte mit gerichteten
  Verbindungen und LQI. Stärkste, schwächste oder nach LQI-Bereich gefilterte
  Verbindungen sind auswählbar; alle Geräte bleiben sichtbar. Die Instanz
  startet keinen Scan und behält ihre Export- und Verbindungsbudgets.

- __EChartsTreemap__ ([Dokumentation](EChartsTreemap/README.md))
  Ordnet bis zu 100 aktuelle Integer- und Float-Variablen manuell benannten
  Gruppen und optionalen Untergruppen zu. Die nichtnegativen Werte bestimmen
  die Rechteckflächen in Kachel und optionalem IPSView-Widget.

- __EChartsSunburst__ ([Dokumentation](EChartsSunburst/README.md))
  Zeigt dieselbe manuell benannte Hierarchie als konzentrische Ringe. Aktuelle
  nichtnegative Variablenwerte bestimmen die Winkel der Segmente.

- __EChartsParallel__ ([Dokumentation](EChartsParallel/README.md))
  Verbindet zwei bis zehn Archivvariablen in wählbaren Stunden- oder
  Tagesintervallen als Parallelkoordinaten-Linien. Nur gemeinsame abgeschlossene
  Intervalle erscheinen; Kachel und IPSView sind getrennt gestaltbar.

## Kacheldesign und IPSView-Design

In beiden Designern finden Sie die Einstellungen in aufklappbaren,
chartbezogenen Themenbereichen. Animation und grundlegende Auswahlfelder
stehen davor; die Live-Vorschau folgt den Bereichen. Die Designer-Panels
nutzen die verfügbare Breite der Instanzkonfiguration statt einer festen
Pixelbreite. Bei aktiviertem „Kacheldesign verwenden“ sind die unabhängigen
IPSView-Einstellungen deaktiviert, die Vorschau zeigt aber weiterhin das
wirksame Design.
Category, History, Waterfall, Polar und Pie bieten eigene Rubriken für Schrift und
Farben. Automatische Farben folgen weiterhin dem gewählten Theme.

Alle Diagrammmodule bieten im Kacheldesigner **Animation**, **Startanimation**
und **Aktualisierungsanimation**. Die beiden Zeiten sind unabhängig von 0 bis
3000 ms einstellbar; 0 ms unterdrückt die jeweilige Übergangsbewegung.
Für Start und Aktualisierung stehen jeweils eine Bewegungskurve und eine feste
Verzögerung (0–3000 ms) zur Wahl. Standardmäßig gilt cubicInOut ohne
Verzögerung. Die Verzögerung betrifft den ganzen Chart-Übergang, nicht
aufeinanderfolgende einzelne Datenpunkte. Bei 0 ms Übergangsdauer entfällt
auch dessen Verzögerung.
TimeSeries startet aus Rücksicht auf viele historische Datenpunkte ohne
Animation, die anderen Diagramme behalten ihre bisherige Animation. Das
unabhängige IPSView-Design kann andere Werte verwenden. Eine
Systemeinstellung für reduzierte Bewegung schaltet Animationen immer ab.

Jede Datenquelle wird technisch ausschließlich über ihre Symcon-Variablen-ID
zugeordnet. Namen, Objektpfade und frei gewählte Beschriftungen dienen nur der
Anzeige oder fachlichen Gruppierung. Ein ausdrücklich konfigurierter Name hat
Vorrang, sonst gilt der aktuelle Symcon-Name der Variablen. Auch bei gleichen
Beschriftungen bleiben die Quellen intern über ihre IDs getrennt; IDs werden
nicht automatisch in Kachel, IPSView oder Vorschau angezeigt.

Bei allen ECharts-Diagrammen ist unter **IPSView-Design** die Option
**Kacheldesign verwenden** standardmäßig aktiv. Solange sie aktiv ist, folgt
die Diagrammgestaltung in IPSView dem Kacheldesigner. Die eigenen Designfelder
und der Knopf **Kacheldesign nach IPSView kopieren und unabhängig bearbeiten**
stehen direkt im Abschnitt **IPSView-Design**, sind dann aber deaktiviert.
Die HTML-Ausgabe und die IPSView-Hintergrundeinstellungen bleiben bedienbar.

Für ein eigenes IPSView-Design zuerst **Kacheldesign verwenden** ausschalten.
Danach ist der Kopierknopf sofort aktiv: Er übernimmt das zuletzt gespeicherte
Kacheldesign einmalig als Ausgangspunkt und überschreibt dabei die bisherigen
unabhängigen IPSView-Designwerte. Anschließend können diese Werte separat
bearbeitet werden; spätere Änderungen am Kacheldesign werden nicht mehr
automatisch übernommen. Datenquellen bleiben gemeinsam. Eigenständige
IPSView-Einstellungen wie Hintergrund und – sofern angeboten – Zeitraum
werden getrennt gesteuert. Bei TimeSeries und BarHistory liegen die
IPSView-Zeiteinstellungen neben den allgemeinen Zeiteinstellungen, nicht im
Designer. Änderungen im Kacheldesigner vor dem Kopieren erst speichern.

Für eine reguläre Installation genügt eine gemeinsame EChartsGateway-Instanz
für alle Gauge- und späteren Diagramminstanzen. Beim ersten Diagramm kann ein
neues Gateway angelegt werden; bei jedem weiteren Diagramm wird das bereits
vorhandene Gateway ausgewählt. Die von Symcon weiterhin angebotene Neuanlage
eines Parents ist für den Normalbetrieb nicht erforderlich.

## Installation

Die Library wird über die Symcon-Modulverwaltung aus folgendem Repository
installiert:

```text
https://github.com/Burki24/SymconECharts
```

Anschließend wird beim ersten Diagramm eine `EChartsGateway`-Instanz als Parent
angelegt. Weitere Diagramme verwenden dieselbe Gateway-Instanz.

## Konfiguration und Verwendung

Für jedes unabhängig zu konfigurierende Diagramm wird eine Instanz des
passenden Moduls angelegt. Datenquellen, Wertebereiche, Darstellung und die
optionale IPSView-Ausgabe werden in dieser Instanz festgelegt. Die ausführliche
Einrichtung ist in den oben verlinkten Modul-Readmes beschrieben.

Die Diagrammformulare beginnen nach einem Gateway-Hinweis mit Titel und
Quellen. Danach folgen die fachlichen Daten- und Zeiteinstellungen, der
Kacheldesigner und das IPSView-Design; Diagnoseaktionen stehen am Ende. Unter
**IPSView-Design** erklärt der Schalter **Kacheldesign verwenden** die
Vererbung unmittelbar neben der Einstellung. Diagrammspezifische Felder
bleiben im jeweiligen Modul unterschiedlich.

## Änderungen

Die Versionshistorie steht in der [CHANGELOG.md](CHANGELOG.md).

## Lizenz

Die eigenen Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](LICENSE).
SPDX-Identifier: `PolyForm-Noncommercial-1.0.0`.

Required Notice: Copyright 2026 Burkhard Kneiseler. SymconECharts.

Fremdkomponenten behalten ihre Originallizenzen. Version, Herkunft, Integrität
und Lizenz der eingebundenen Apache-ECharts-Runtime stehen in
[THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

SymconECharts ist kein offizielles Projekt der Apache Software Foundation.

### Mausradbedienung in IPSView

Kachel und IPSView-Ausgabe nutzen die gemeinsame Eingabebehandlung aus `Symcon_ModuleHelper`. Sie normalisiert Mausrad-Deltas und ermittelt bei von IPSView umadressierten Ereignissen den Bereich unter der letzten tatsächlichen Mausposition. Listen und Dialoginhalte scrollen innerhalb ihres Bereichs; vorhandener Karten-/Diagrammzoom bleibt komponentenspezifisch. Verschachtelte teilnehmende HTML-Frames geben ausschließlich UI-Eingaben über den zentralen Vertrag weiter. Ctrl-/Meta-Mausrad bleibt beim Host. Eine vollständige Funktionsprüfung unterscheidet den IPSView-Webclient von der nativen IPSView-App.
