# Unveröffentlicht

## Behoben

- Die Scatter-Farbskala verwendet nun die ECharts-Abmessungen korrekt:
  eine flache, an die Kachel- oder IPSView-Breite angepasste horizontale
  Legende statt eines schmalen senkrechten Streifens. Die Formularvorschau
  zeigt ebenfalls eine breitere Skala.

## Hinzugefügt

- Scatter/Bubble kann Punkte über die ECharts-Farbskala nach X-, Y- oder
  Blasengrößenwert einfärben. Farbgrenzen, Endfarben und Legende sind in
  Kachel und IPSView getrennt einstellbar und in beiden Vorschauen sichtbar;
  bestehende Instanzen behalten die einheitliche Punktfarbe.

- Scatter/Bubble bietet einen getrennten interaktiven X/Y-Zoom mit schlanken
  Schiebereglern, Mausradsteuerung und Erhalt beider Ausschnitte bei passenden
  Datenaktualisierungen. Die Einstellung steht im Kachel- und im unabhängigen
  IPSView-Designer mit Live-Vorschau zur Verfügung.

- Scatter/Bubble kann Punktbeschriftungen mit X-Wert, Y-Wert oder beiden Werten
  samt jeweiliger Einheit anzeigen. Die Auswahl ist im Kachel- und im
  unabhängigen IPSView-Designer verfügbar; bestehende Instanzen bleiben bei Y.

- `EChartsScatter` zeigt Archivwerte als X/Y-Streudiagramm oder mit optionaler
  dritter Quelle als Bubble-Diagramm in Kachel und IPSView. Punkte entstehen
  ausschließlich aus gemeinsamen abgeschlossenen Intervallen; fehlende Werte
  werden weder interpoliert noch fortgeschrieben. Beide Designer besitzen
  Live-Vorschau, Farben, Beschriftungen und Animation.

- Pie/Donut/Nightingale, Balken- und TimeSeries-Designer bieten zusätzliche
  Positionen und Abstände für Wertbeschriftungen; Pie erhält eigene Regler für
  Außen-Ausrichtung und Führungslinien. Die Gauge-Designer für Multi, Tacho
  und Chronograph können Skalenabstand sowie Titel- und Wertposition relativ
  zur Vorlage verschieben. Kachel und unabhängiges IPSView-Design besitzen
  dieselben Regler. Gemeinsame Verträge liegen in `libs`; bisherige
  Automatiken bleiben voreingestellt.

- Das neue Modul `EChartsPie` zeigt aktuelle Anteile als Kreis, Donut oder
  Nightingale-Rose in Kachel und IPSView. Beide Designer bieten Live-Vorschauen,
  unabhängige Gestaltung und Animation. Negative Werte und Nullsummen werden
  verständlich gemeldet; Quellen bleiben auch bei gleichen Namen ID-getrennt.

## Behoben

- Die horizontalen und vertikalen Scatter-Zoomregler haben jetzt dieselbe
  zentral definierte Stärke wie die Zoomleisten von TimeSeries und BarHistory:
  12 px, in kompakten Ansichten 8 px.

- Die Zoomleiste von TimeSeries ist in Kachel und IPSView nun genauso schlank
  wie bei BarHistory; auch kurze Ansichten erhalten den kompakten Regler.

- Das Scatter-Instanzformular bezeichnet die Darstellung verständlich als
  „Streudiagramm (optional mit Blasengröße)“; technische Modulnamen bleiben
  unverändert.

- Scatter/Bubble nutzt die Diagrammfläche mit datenangepassten X- und
  Y-Achsen statt beide Skalen unnötig bei null beginnen zu lassen. Beide
  Achsen können unabhängig null einschließen oder manuell begrenzt werden.
  Bei dichten Punktwolken werden kollidierende Wertbeschriftungen verborgen;
  der Tooltip zeigt weiterhin alle Messwerte. Die Formularvorschau platziert
  Beispielpunkte, Skalen und Y-Achsentitel nun innerhalb einer konsistenten
  Diagrammgeometrie und reagiert auf die Achsen- und Beschriftungsoptionen.

- `EChartsPie` passt Kreisgröße und Beschriftungen an die tatsächlich freie
  Fläche an. In mittelgroßen Kacheln steht die Legende unter dem Diagramm,
  statt mit Außenbeschriftungen zu kollidieren; der Kachelkopf bleibt frei.

- Die deutschen Instanzformulare zeigen in BarCategory, BarHistory,
  BarWaterfall und BarPolar die Schrift- und Farboptionen nun vollständig
  übersetzt. Auch die individuellen Designs von Gauge Tacho und Gauge
  Chronograph enthalten keine englischen Feldnamen oder internen
  Farbbezeichner mehr; ihre Auswahlwerte für Nabe, SVG-Ankerpunkt und
  Theme-Farben sind ebenfalls deutsch. In den Quellenlisten von Gauge Tacho,
  Gauge Chronograph und TimeSeries erscheint „Individuelles Design“ nun als
  Ankreuzfeld statt als `true`/`false`.

- Begrenzte Formularvorschauen von TimeSeries, BarCategory, BarHistory,
  BarWaterfall und BarPolar nennen jetzt die Zahl der gezeigten und insgesamt
  konfigurierten Quellen. Die Kachel und das IPSView-Widget verarbeiten
  weiterhin alle Quellen; Gauge Multi nutzt für denselben Hinweis nun den
  gemeinsamen Vorschau-Baustein. BarCategory und BarPolar wählen die
  Vorschauquellen bei aktiver Sortierung jetzt in derselben Reihenfolge wie
  die Kachel aus.

- Gauge Multi kennzeichnet in beiden Formularvorschauen jetzt, wie viele der
  konfigurierten Quellen die jeweilige Vorlage exemplarisch zeigt. Zuvor
  verschwanden weitere Quellen dort ohne Hinweis, obwohl Kachel und IPSView
  alle Quellen darstellten. Neue, noch unkonfigurierte Instanzen zeigen statt
  unmarkierter Beispielinstrumente einen Konfigurationshinweis.

- Die Kachel- und IPSView-Designer aller Diagrammmodule nutzen nun die
  verfügbare Breite der Instanzkonfiguration, statt auf 700–760 px begrenzt
  zu sein.

- Die Polar-Formularvorschau kennzeichnet den Winkelverlauf nun mit
  0°/360°, 90°, 180° und 270° ab dem eingestellten Startpunkt. Die Marken
  folgen der gewählten Laufrichtung in Kachel- und IPSView-Designer.

- Die Polar-Formularvorschau verwendet für Startwinkel und Drehrichtung nun
  dieselbe Winkelkonvention wie die ECharts-Kachel. Teilkreise und Balken
  erscheinen dadurch im Kachel- und unabhängigen IPSView-Designer an der
  richtigen Position.

- Das Raster der Polar-Formularvorschau folgt bei Teilkreisen nun dem
  eingestellten Winkelumfang und der Drehrichtung, auch bei unabhängigem
  IPSView-Design; 360° bleiben vollständige Kreise.

- Die TimeSeries-Formularvorschauen zeigen jetzt für unterschiedliche
  effektive Einheiten getrennte Wertachsen mit richtiger Seite und Farbe,
  statt nur einer neutralen Y-Achse ohne Einheiten.

- Die BarHistory-Formularvorschauen zeigen bei mehreren Einheiten die
  zugehörigen Wertachsen auf der konfigurierten Seite und in der wirksamen
  Achsenfarbe statt einer einzigen neutralen Skizzenachse.

- Die BarHistory-Formularvorschau formatiert Beispielwert-Beschriftungen jetzt
  mit der wirksamen Einheit und Nachkommastellen jeder Quelle statt mit einer
  festen Dezimaldarstellung, getrennt für Kachel und unabhängiges IPSView.

- Die Formularvorschauen von BarCategory, BarPolar und BarWaterfall übernehmen
  jetzt wirksame Einheiten und Nachkommastellen aus der Variablendarstellung;
  Waterfall formatiert damit auch Änderungen und Endsumme wie die Kachel.

- Die BarHistory-Formularvorschau zeigt jetzt auch 0–9 % Balkendeckkraft
  korrekt, getrennt für Kachel und unabhängiges IPSView.

- Die Waterfall-Formularvorschau setzt Werte fallender Balken und negativer
  Endsummen wie die Kachel unter die Säule; bei negativen Wertebereichen bleiben
  Kategorie- und Wertbeschriftungen getrennt.

- Die BarHistory-Formularvorschau zeigt Änderungen der SVG-Mustergröße jetzt
  getrennt für Kachel und unabhängiges IPSView statt eines festen Musterrasters.

- Die BarHistory-Formularvorschau berücksichtigt jetzt den eingestellten
  Eckradius abgerundeter Balken getrennt für Kachel und unabhängiges IPSView.

- Vorübergehende Ladefehler lassen auch bei Gauge Single, Multi, Tacho,
  Chronograph, TimeSeries, BarCategory, BarWaterfall und BarPolar die letzte
  gültige Kachel- oder IPSView-Grafik stehen. Bei wiederholtem Fehler erscheint
  ein Hinweis; die Module versuchen nach 60 Sekunden erneut zu laden.
  Ungültige Konfigurationen bleiben weiterhin als Fehler sichtbar.

- Gauge Multi aktualisiert beide Formularvorschauen jetzt auch bei noch nicht
  gespeicherten Änderungen der Quellenliste (Hinzufügen, Bearbeiten, Löschen und
  Umsortieren). Unvollständige Listen zeigen einen Hinweis statt veralteter Gauges.

- Die Diagramm-Instanzformulare beginnen einheitlich mit Titel und Quellen;
  Waterfall ordnet die Beschriftung der Endsumme danach ein, Polar zeigt die
  Animation am Anfang beider Designer. Alle Diagrammmodule erklären die
  IPSView-Designvererbung direkt am Schalter und verwenden gleiche
  IPSView-Hilfetexte. Der bislang englische Gauge-Multi-Hinweis ist wieder
  deutsch übersetzt.

- Ein vorübergehend fehlgeschlagenes Archiv-Update lässt BarHistory-Kachel
  und IPSView nicht mehr verschwinden: Diagramm und Zoom bleiben erhalten.
  Wiederholte Fehler werden als Hinweis angezeigt, bis neue Daten eintreffen.

- Kurze BarHistory-Kacheln reservieren wieder Platz für Balken und reduzieren
  überlappende Zeit- und Wertachsenbeschriftungen. Die Zoom-Leiste ist in
  normalen Ansichten flacher, in kompakten Kacheln nochmals schmaler und mit
  Abstand zum unteren Kachelrand platziert.

- Unveränderte Größenmeldungen unterbrechen auch bei Gauges, TimeSeries und
  den übrigen Balkendiagrammen keine laufenden Chart-Animationen mehr. Echte
  Größenänderungen bleiben wirksam.

- Start- und Aktualisierungsanimationen der Polar-Balken werden in Kachel und
  IPSView nicht mehr durch Größenmeldungen ohne tatsächliche Größenänderung abgebrochen.

- Kategoriebeschriftungen im Polar-IPSView nutzen bei ausreichend Platz die
  verfügbare Breite auch bei größerer Schrift, statt schon nach wenigen
  Zeichen abgeschnitten zu werden. Schmale Widgets behalten Platz für den Kreis.

- Hintergrundspuren konzentrischer Polar-Bögen bleiben bei einem begrenzten
  Winkelumfang innerhalb des gewählten Teilkreises, auch nach Größenänderungen.

- Beim Verkleinern einer Polar-Kachel werden Kategoriebeschriftungen nicht
  mehr durch das automatische Achsenintervall übersprungen, solange sie
  tatsächlich Platz haben.

- Bei konzentrischen Polar-Bögen bleiben Kategoriebeschriftungen vor den
  Balken und Hintergrundspuren sichtbar. Werte an abgerundeten Bogenenden
  rücken in die farbige Fläche, damit die automatische Schriftfarbe dort passt.

- Bei Polar-Teilkreisen folgen Raster und Hintergrundspuren nun demselben
  Winkelbereich und derselben Drehrichtung wie die Balken; der Vollkreisrand
  erscheint außerhalb des gewählten Ausschnitts nicht mehr.

- Polar-Balken und konzentrische Bögen beginnen bei einer festen Werteskala am eingestellten Minimum statt am ECharts-Standardwert 0.

## Neu

- Gauge Multi, Tacho und Chronograph bieten in Kachel und unabhängigem
  IPSView-Design eine eigene Rubrik für Farbe und Größe des äußeren
  Diagrammtitels. Automatische Farbe und 100 % Größe erhalten die bisherige
  Darstellung; Instrumentbeschriftungen bleiben davon unabhängig.

- BarCategory und BarWaterfall bieten in Kachel und unabhängigem IPSView
  konfigurierbare Schriftgrößen und Farben für Titel, Achsen und Werte;
  Category zusätzlich für die Legende. Rasterfarben sind ebenfalls wählbar.
  BarHistory ergänzt Legendenfarbe und -größe sowie die Achseneinheit in der
  Achsenschriftgröße. BarPolar ergänzt Titelgröße und Text-/Rasterfarben;
  Werte im Balken behalten den automatischen Kontrast. Automatische Vorgaben
  erhalten die bisherige Theme-Darstellung.

- TimeSeries bietet in Kachel und unabhängigem IPSView-Designer eigene
  Schriftgrößen für Titel, Legende und Achsen sowie Farben für Titel, Legende,
  Achsen und Raster. Automatische Werte erhalten die bisherigen Theme- und
  Reihenfarben; die Live-Vorschauen berücksichtigen ungespeicherte Änderungen.

- Die Kachel- und IPSView-Designer aller neun Diagrammmodule sind jetzt in
  fachlich benannte, einklappbare Bereiche gegliedert. Die Rubriken sind je
  Chart passend gewählt; bestehende Einstellungen und Vorschauen bleiben
  erhalten.

- BarCategory, BarHistory, BarWaterfall und BarPolar besitzen jetzt wie die
  Gauges und TimeSeries eine Live-Vorschau im Kachel- und IPSView-Designer.
  Ungespeicherte Formular- und Quellenänderungen aktualisieren beide Bilder;
  historische Vorschauen kennzeichnen ihre archivfreien Beispieldaten.

- Alle neun Diagrammmodule bieten für Start und Aktualisierung getrennte
  Bewegungskurven und feste Verzögerungen (0–3000 ms) im Kachel- und
  unabhängigen IPSView-Design. Die bestehenden Übergänge bleiben mit
  cubicInOut und 0 ms Verzögerung unverändert. Reduzierte Bewegung
  unterdrückt auch Verzögerungen.

- Alle neun Diagrammmodule bieten im Kachel- und unabhängigen IPSView-Design
  einen gemeinsamen Schalter sowie getrennte Zeiten für Start- und
  Aktualisierungsanimation (0–3000 ms). Bestehende Standardwerte bleiben
  erhalten; TimeSeries ist weiterhin standardmäßig ohne Animation.
  Reduzierte Bewegung im Betriebssystem hat Vorrang.

- Für Polar-Balken lassen sich Start- und Aktualisierungsanimation in Kachel
  und unabhängigem IPSView-Design getrennt einstellen oder abschalten. Die
  Systemeinstellung für reduzierte Bewegung hat Vorrang.

- Polar-Balken erhalten optional einen Schatten mit einstellbarer Stärke,
  Farbe und Deckkraft. Kachel und unabhängiges IPSView-Design sind separat
  konfigurierbar; standardmäßig bleibt der Schatten ausgeschaltet.

- Polar-Balken lassen sich in Kachel und unabhängigem IPSView-Design bei
  Berührung oder Hover hervorheben, wahlweise mit Abdunklung der anderen
  Balken. Der gewählte Balken kann per Antippen fixiert werden; der bisherige
  ECharts-Standard bleibt voreingestellt.

- Polar-Balken und konzentrische Bögen können in Kachel und unabhängigem
  IPSView-Design eine Kontur von 1 bis 8 px mit Theme- oder eigener Farbe
  erhalten. Standardmäßig bleibt sie ausgeschaltet.

- Die Deckkraft radialer Polar-Balken und konzentrischer Bögen ist im
  Kachel- und unabhängigen IPSView-Designer von 0 bis 100 % einstellbar.
  Hintergrundspuren und Beschriftungen bleiben davon unabhängig.

- Polar-Balken und konzentrische Bögen können in Kachel und unabhängigem
  IPSView-Design statt Vollfarbe einen Farbverlauf mit wählbarer Endfarbe
  verwenden.

- Kategorie-, Wert- und Skalenschriftgröße des Polar-Diagramms sind für
  Kachel und unabhängiges IPSView-Design getrennt einstellbar.

- Polar-Wertbeschriftungen können in Kachel und IPSView auch direkt außerhalb
  des jeweiligen Balkens, aber innerhalb des Diagrammbereichs stehen. Die
  Außenbeschriftung nutzt die Theme-Textfarbe und erhält zusätzlichen Abstand
  zu den Kategoriebeschriftungen.

- Polar-Wertbeschriftungen können in Kachel und unabhängigem IPSView-Design
  mittig, innen am Balkenanfang oder innen am Balkenende platziert werden.
  Die bisherige mittige Position bleibt Standard.

- Polar-Balken können in Kachel und IPSView auf einen frei einstellbaren
  Winkelbereich von 30° bis 360° begrenzt werden. Ein Halbkreis ist damit ebenso
  möglich wie der bisherige Vollkreis; Startwinkel und Drehrichtung bleiben
  separat einstellbar.

- EChartsBarPolar vergleicht 1 bis 16 aktuelle Werte mit gemeinsamer Einheit
  als radiale Balken oder konzentrische Bögen. Kachel und optionales IPSView
  bieten getrennte Einstellungen für Reihenfolge, Farben und Polar-Geometrie.

- Der Polar-Designer bietet in Kachel und IPSView eine feste Werteskala mit
  Minimum und Maximum sowie optionale Hintergrundspuren mit Theme- oder
  Benutzerfarbe und eigener Deckkraft. IPSView kann das Kacheldesign übernehmen
  oder diese Optionen unabhängig einstellen. Die automatische Skala ohne Spuren
  bleibt Standard.

- EChartsBarWaterfall zeigt einen aktuellen Startwert, bis zu 15 geordnete
  positive oder negative Änderungen und eine berechnete Endsumme. Die native
  Kachel aktualisiert sich bei Variablenänderungen; IPSView kann ein eigenes
  Design und einen anpassbaren Hintergrund verwenden.

- BarCategory und BarHistory können ihre Balken in Kachel und IPSView mit
  einem wiederholten, sicher geprüften SVG-Muster füllen. Muster und Größe
  sind bei unabhängigem IPSView-Design separat wählbar; bisherige Farben
  und Farbverläufe bleiben unverändert.

- BarHistory bietet einen abschaltbaren Zeitachsen-Zoom mit Regler und
  Mausrad in Kachel und IPSView; ein unabhängiges IPSView-Design kann Zoom
  getrennt steuern. Die ECharts-spezifische Bedienlogik liegt gemeinsam unter
  `libs` und bewahrt den gewählten Ausschnitt bei normalen Datenaktualisierungen
  auch in TimeSeries.

- EChartsBarHistory unterstützt nun 1 bis 16 Archivquellen in einer Instanz.
  Balken mit gleichem Zeitpunkt stehen nebeneinander; verschiedene Einheiten
  erhalten eigene, links oder rechts platzierbare Wertachsen. Das Punktbudget
  wird auf die Quellen verteilt. Bestehende Einquellen-Konfigurationen bleiben
  gültig.

- Alle ECharts-Module verwenden Variablen-IDs ausschließlich für die interne
  Quellenzuordnung. Auch bei gleichen Beschriftungen bleiben in Diagrammen,
  Legenden und Vorschauen die konfigurierten beziehungsweise aktuellen
  Symcon-Namen ohne automatisch ergänzte IDs sichtbar.

- Category-Bar verwendet die Variablen-ID als technische Quellenidentität.
  Gleich benannte Variablen in derselben Kategorie bleiben getrennt erhalten;
  auch bei kollidierenden Beschriftungen erscheinen keine IDs in Achse oder
  Legende.

- Die eigenen IPSView-Designfelder und der Kopierknopf stehen in allen
  ECharts-Diagrammen direkt im Abschnitt IPSView-Design, ohne zusätzliche
  Aufklappebene. Solange IPSView das Kacheldesign übernimmt, sind sie
  deaktiviert; nach dem Ausschalten der Vererbung werden sie sofort aktiv.
  Bei TimeSeries und BarHistory liegen die unabhängigen IPSView-Zeiteinstellungen
  nun neben den allgemeinen Zeitraumoptionen statt im Designer. Die gemeinsame
  Formularlogik ist lokal zentralisiert und die Bedienfolge in allen
  Modul-READMEs erläutert.

- EChartsBarHistory stellt rohe, automatisch verdichtete oder ausdrücklich
  aggregierte Archivwerte einer numerischen Quelle als vertikale Balken auf
  einer Zeitachse dar. Die erste native Kachel unterstützt feste,
  kalendergebundene und benutzerdefinierte Zeiträume, Punktbudget,
  Variablendarstellung, Reduzierer, Farbe und ein kompaktes Kacheldesign.
  Eine optionale IPSView-Ausgabe nutzt denselben Datenvertrag und erlaubt
  einen eigenen Zeitraum, ein eigenes Design sowie einstellbare Hintergrundfarbe
  und -deckkraft. Aktualisierungen ersetzen das HTML-Dokument nicht.
  Für Kachel und IPSView sind nun zusätzlich Balkenfüllung mit optionalem
  Farbverlauf, Deckkraft und Eckenradius sowie Schriftgrößen und Farben für
  Titel, Achsen, Werte und Raster einstellbar.

- EChartsBarCategory vergleicht 1 bis 16 aktuelle numerische Werte mit
  gemeinsamer Einheit als einfache, gruppierte oder gestapelte vertikale und
  horizontale Balken und bietet Live-Aktualisierung sowie getrennte Designs
  für Kachel und IPSView. Gruppierte und gestapelte Ansichten funktionieren
  sowohl direkt mit einer gewöhnlichen Quellenliste als auch mit einer
  ausdrücklich pro Quelle getrennt bearbeitbaren Kategorien und Datenreihen.
  Der Zeileneditor benennt beide Felder eindeutig; Kategorien dürfen
  unterschiedlich viele Datenreihen enthalten. Wertbeschriftungen
  in gestapelten Segmenten wählen abhängig von der Balkenfarbe automatisch
  eine kontrastreiche helle oder dunkle Schriftfarbe.

- EChartsTimeSeries unterstützt frei definierbare rollende Zeiträume in
  Minuten, Stunden, Tagen oder Wochen sowie wählbare Beschriftungen der
  Zeitachse mit Uhrzeit, Datum oder beidem. IPSView kann diese Einstellungen
  von der Kachel übernehmen oder einen eigenen Zeitraum und Achsenmodus
  verwenden. Zusätzlich stehen kalendergebundene Ansichten für heute,
  gestern, die laufende Woche und den laufenden Monat zur Verfügung.
- EChartsTimeSeries bietet quellenbezogene Referenzlinien und Wertebereiche
  zur Kennzeichnung von Grenz-, Ziel- und Komfortwerten.
- Wertachsen von EChartsTimeSeries können automatisch, aus der
  Variablendarstellung oder mit einem manuellen Minimum und Maximum skaliert
  sowie ausdrücklich links oder rechts angeordnet werden.
- Die IPSView-Ausgabe aller Diagrammmodule kann ihren Hintergrund transparent
  darstellen und mit einer Theme- oder Benutzerfarbe in einstellbarer
  Deckkraft tönen.
- EChartsTimeSeries kann Linien bei automatisch erkannten Datenlücken oder
  nach einem festgelegten maximalen Zeitabstand sichtbar unterbrechen.

## Fixes

- Polar-Bar passt den Kreis an kleine Kacheln und Größenänderungen an, ohne
  ihn durch starre Abstände auf Miniaturgröße zu verkleinern. Die Skala
  überlagert nicht mehr den Kacheltitel und ihre Zahlen werden in sehr
  kompakten Ansichten zugunsten lesbarer Balkenwerte ausgeblendet; äußere
  Kategorien bleiben innerhalb des verfügbaren Bereichs.
- Polar-Bar-Werte bleiben auch im unteren Kreisbereich waagerecht lesbar und
  stehen mit automatisch kontrastierender Schrift mittig im Balken statt auf
  dessen Kante; Kategoriebeschriftungen erhalten mehr Abstand zum äußeren Ring.
- Das Konfigurationsformular von EChartsBarHistory lässt sich wieder öffnen;
  die Felder für einen benutzerdefinierten Zeitraum werden bei der Auswahl
  unmittelbar ein- oder ausgeblendet.
- Laufzeitwerte aktualisieren in IPSView nur noch das bestehende Diagramm,
  ohne das vollständige HTML-Dokument neu zu laden und sichtbar zu flackern.
