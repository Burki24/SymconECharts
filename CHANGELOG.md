# Unveröffentlicht

## Behoben

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
