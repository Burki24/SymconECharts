# Unveröffentlicht

## Neu

- EChartsStateTimeline ergänzt beide Designer und Vorschauen um Rahmenarten,
  Eckenrundung, innere Textausrichtung, Schriftarten und Fett-/Kursivschrift.
  Zeit-/Quellenlabels, Zeitformate, Achsenlinien, Teilstriche und Raster sind
  gestaltbar; optionaler Abschnittsfokus mit einstellbarer Restdeckkraft
  verbessert die Hervorhebung. Quellen und Archivintervalle bleiben unverändert.

- EChartsStateTimeline zeigt rohe archivierte Boolean- und Integer-Zustände
  als farbige Zeitabschnitte je Quelle. Zustandsnamen/-farben, Zeitraum und
  Zoom sind konfigurierbar; unbekannte Anfänge bleiben ausdrücklich sichtbar.
  Kachel und IPSView besitzen getrennte Designer mit Vorschauen. Quelldaten
  und Archive werden nicht verändert.

- ThemeRiver erweitert beide Designer um Bandhöhe, Konturarten, Schatten,
  Hervorhebungsfokus und Restdeckkraft sowie Achsenlinien, Zeitformate und
  Rastergestaltung. Legendenposition/-ausrichtung, Symbolform/-größe und
  Abstände sind wählbar; Schriftart, Fett- und Kursivschrift ergänzen die
  Typografie. Vorschauen, Designvererbung und Kopierfunktion berücksichtigen
  die neuen Einstellungen, ohne Quellen oder Archivwerte zu verändern.

- EChartsThemeRiver zeigt nichtnegative Stunden- oder Tagesmittelwerte aus
  bis zu acht Standard-Archivvariablen derselben Einheit als farbige Bänder.
  Archivlücken unterbrechen die Darstellung, ohne Nullwerte vorzutäuschen.
  Kachel und IPSView bieten getrennte Designer mit Live-Vorschauen,
  Schrift-/Farbwahl, Legende, Bandkontur und Animation.

- PictorialBar importiert eigene pfadbasierte SVG-Formen über eine Dateiauswahl
  in beiden Designern. Kachel, IPSView und Live-Vorschauen unterstützen sie
  als Einzel- oder Wiederholungsform mit allen Farbmodi einschließlich
  skalenbezogenem Verlauf. Der begrenzte Import übernimmt nur geprüfte
  Geometrie und weist unsichere oder nicht unterstützte SVG-Inhalte ab.
  Die Anleitung erläutert geeignete Größen, Seitenverhältnisse und den
  Export und enthält eine direkt importierbare Batterie-Beispieldatei.

- Die Modulauswahl zeigt Boxplot, Candlestick, Funnel, PictorialBar und Radar
  wieder unter ihren einheitlichen Namen ohne Leerzeichen, beispielsweise
  `EChartsPictorialBar`. Abweichende Anzeige-Aliase sind entfernt;
  technische Modulidentitäten und Konfigurationen bleiben unverändert.

- PictorialBar bietet Symcon-Icons als zusätzliche Symbolform mit grafischer
  nativer Icon-Auswahl in beiden Designern. Wiederholung, Einzelform,
  Vorschauen und alle Farbmodi einschließlich des skalenbezogenen Verlaufs
  verwenden die ausgewählte Form. Bestehende Konfigurationen bleiben unverändert.

- PictorialBar bietet zusätzlich einen Farbverlauf innerhalb jedes
  Symbolbalkens. Er ist fest an die frei einstellbaren Farbgrenzen gebunden
  (etwa 0–100 % oder 0–255 LQI), läuft über wiederholte Symbole hinweg und
  zeigt bei kurzen Balken nur den passenden Anfang. Einzelformen, beide
  Ausrichtungen und Vorschauen sind unterstützt. Die bisherige
  wertabhängige Einheitsfarbe und gespeicherte Konfigurationen bleiben erhalten.

- Der PictorialBar-Designer bietet eine stufenlose wertabhängige Färbung
  mit drei frei wählbaren Farben und festen Farbgrenzen, beispielsweise
  Rot–Gelb–Grün für Batteriestände. Schriftfamilie, Werteposition/-abstand,
  Fettschrift, Überlappungsschutz, gedrehte Kategorienbeschriftung,
  Achsenmarkierungen, Rasterlinien, Rahmenlinienarten, Schatten und
  Hover-Fokus sind in Kachel und IPSView unabhängig einstellbar.
  Vorschauen zeigen die neuen Stile; bestehende Konfigurationen bleiben erhalten.

## Hinzugefügt

- `EChartsPictorialBar` (`ECPB`) zeigt aktuelle, nichtnegative Werte von
  1–16 Variablen derselben Einheit als Symbolbalken. Grundformen,
  Wiederholung, Skala, Sortierung, Ausrichtung, Schriftgrößen und Farben
  sind für Kachel und IPSView unabhängig einstellbar. Live-Vorschauen
  verwenden aktuelle Werte; bestehende Instanzen und Quellwerte bleiben unverändert.

- Der Candlestick-Designer bietet getrennte Rahmenfarben und hohle Körper
  für steigende/fallende Kerzen, eine feste responsive Kerzenbreite,
  einstellbare Hover-Rahmen/-deckkraft, Zeitachsenrichtung und -linien,
  optionale Nullpunkteinbindung, Beschriftungsabstände, lokale Schriftarten
  sowie Kursivschrift. Beide Designer und Vorschauen sind erweitert;
  bestehende Quellen, Archivberechnung und gespeicherte Einstellungen bleiben erhalten.

- `EChartsCandlestick` (`ECCA`) zeigt den ersten und letzten tatsächlich
  protokollierten Wert sowie Minimum und Maximum je abgeschlossenem Stunden-
  oder Tagesintervall einer Standard-Archivvariable. Kachel und IPSView
  besitzen unabhängige Designer und Beispielvorschauen. Teilarchive bleiben
  sichtbar, fehlende Intervalle leer; begrenzte Rohdatenabfragen verhindern
  unvollständige Kerzen bei dichten Archiven.

- Der Funnel-Designer bietet minimale/maximale optische Stufengröße,
  Rahmenlinienarten, Schatten, lokale Schriftarten, Außenbeschriftung auf
  beiden Seiten, Textbreite und abschaltbaren Überlappungsschutz.
  Führungslinien sowie Hover-Hervorhebung sind individuell einstellbar.
  Kachel und IPSView besitzen unabhängige Einstellungen und aktualisierte
  Vorschauen; vorhandene Quellen und Konfigurationen bleiben erhalten.

- `EChartsFunnel` (`ECFU`) zeigt 1–16 manuell benannte Stufen mit aktuellen,
  nichtnegativen Variablenwerten derselben Einheit. Kachel und IPSView besitzen
  getrennte Designer für Ausrichtung, Reihenfolge, Abstände, Beschriftungen,
  Farben, Schriftgrößen, Rahmen und Deckkraft sowie aktuelle Vorschauen.

- Der Boxplot-Designer bietet minimale Boxbreite, Randlinienarten,
  Schatten mit Farbe und Versatz, abschaltbare Hover-Hervorhebung,
  feinere Kategorienbeschriftungen und Achsen, Rastergestaltung sowie
  Fettschrift. Beide Ausgabewege besitzen unabhängige Einstellungen und
  aktualisierte Beispielvorschauen; bestehende Quellen und Einstellungen bleiben erhalten.

- `EChartsBoxplot` (`ECBX`) vergleicht abgeschlossene Stundenmittelwerte von
  1–10 Standard-Archivvariablen derselben Einheit über 24 Stunden, 7 oder 30 Tage.
  Boxen zeigen R7-Quartile und Median, die Enden Minimum und Maximum.
  Fehlende Stunden bleiben leer; Stichprobenumfang und erfasste Zeitspanne
  stehen im Tooltip. Kachel und IPSView bieten getrennte Designer mit
  Ausrichtung, Farben, Schriftgrößen und synthetischen Beispielvorschauen.

- Der Radar-Designer bietet individuelle Gruppenfarben, Linienarten,
  Flächenschatten, Symbolformen mit Drehung, Rand und Deckkraft,
  Originalwert-Beschriftungen mit abschaltbarem Überlappungsschutz,
  feinere Achsen- und Rastergestaltung sowie
  Legendenposition, Schriftgröße und Farbe. Beide Ausgabewege besitzen eigene
  Einstellungen und aktuelle Vorschauen; bestehende Konfigurationen bleiben erhalten.

- `EChartsRadar` (`ECRA`) vergleicht 1–8 manuelle Gruppen anhand aktueller
  Variablenwerte auf 3–10 gemeinsamen Achsen. Jede Achse hat einen eigenen
  Wertebereich, eine Einheit und Nachkommastellen. Kachel und IPSView bieten
  getrennte Designer und aktuelle Vorschauen. Werte außerhalb des Bereichs
  werden optisch begrenzt und mit Hinweis angezeigt; Originalwerte bleiben erhalten.

- Der Calendar-Designer bietet unabhängige Monatstrennlinien, Zellrandarten
  und Zelldeckkraft, Hover-Rahmen, Position und Abstand der Kalendernamen,
  Monatsformate, eine zusätzliche gestaltbare Jahreszahl und Fettschrift.
  Farbreihenfolge und feste Farbgrenzen sind getrennt für Kachel und IPSView
  einstellbar; bestehende Konfigurationen bleiben erhalten.

- `EChartsCalendar` (`ECCL`) zeigt archivierte Tagesmittelwerte einer
  numerischen Standardvariable als Jahreskalender mit lesender
  Jahresnavigation. Fehlende und laufende Tage bleiben leer; Zähler sind
  ausgeschlossen. Kachel und IPSView besitzen getrennte Designer für
  Ausrichtung, Beschriftung, Schriftgrößen, Farben, Zellränder und Animation.

- Sankey-Designer bieten individuelle Knotenfarben, Rahmen, Rundungen,
  Knotendeckkraft und Schatten, Beschriftungsposition und -abstand,
  Zeilenumbruch, Fettschrift und Überlappungsschutz sowie Verbindungswerte.
  Layout-Optimierung und Hervorhebung direkter Verbindungen oder vollständiger
  Flusspfade sind einstellbar. Alle Optionen gelten getrennt für Kachel und
  IPSView; die bisherigen Standardeinstellungen bleiben erhalten.

- `EChartsSankey` (`ECSA`) zeigt aktuelle Mengenflüsse zwischen manuell
  angelegten Knoten. Start und Ziel jeder Verbindung werden ausgewählt;
  eine numerische Symcon-Variable bestimmt die Breite. Kachel und IPSView
  besitzen getrennte Designer und Vorschauen. Kreise, doppelte Verbindungen
  und negative Werte werden mit Diagnose abgewiesen.

- Der Parallel-Designer bietet für Kachel und IPSView getrennt Linienarten,
  Transparenz ausgewählter und übriger Linien beim Eingrenzen, Achsenwerte,
  Teilstriche, Achsenbreite, Titelabstand und Animationseinstellungen.

- `EChartsParallel` (`ECPA`) verbindet archivierte Werte von zwei bis zehn
  Variablen je gemeinsamem abgeschlossenen Stunden- oder Tagesintervall zu
  Parallelkoordinaten-Linien. Zeitfenster, Linienbudget und getrennte
  Kachel-/IPSView-Designs sind einstellbar.

- Sunburst-Designer bieten nun Startwinkel, Drehrichtung und Sortierung,
  radiale/tangentiale/waagerechte Beschriftung, Mindestwinkel für Labels,
  Beschriftungsschalter und Schriftgröße je Ring, abgerundete Segmentkanten
  sowie einen Tooltip-Schalter. Kachel und IPSView sind getrennt einstellbar;
  die bisherigen Standarddarstellungen bleiben erhalten.

- `EChartsSunburst` (`ECSB`) zeigt 1 bis 100 aktuelle numerische
  Symcon-Werte in manuell benannten Gruppen und Untergruppen als
  konzentrische Ringe. Kachel und IPSView besitzen getrennte Designs mit
  Live-Vorschau, Gruppenfarben, Radien und Klicknavigation.

- Treemap-Rahmen sind je Hierarchieebene einstellbar, Abstände zwischen
  Gruppen sowie innerhalb von Gruppen und Untergruppen. Auch die gemeinsame
  Rahmenfarbe, Verschieben/Zoomen, Klick-Zoom und
  Navigationspfad lassen sich für Kachel und IPSView getrennt schalten.
  Die bisherigen Werte bleiben als Standard erhalten.

- Treemap-Gruppen können eigene feste Farben erhalten. Gruppen- und
  Untergruppentitel lassen sich unabhängig von den Werte-Beschriftungen
  gestalten; Blattwerte können optional im Rechteck erscheinen. Kachel und
  IPSView unterstützen diese Einstellungen getrennt.

- `EChartsTreemap` (`ECTM`) zeigt 1 bis 100 aktuelle numerische Symcon-Werte
  als Flächen in manuell benannten Gruppen und optionalen Untergruppen.
  Kachel und IPSView besitzen getrennte Designer mit Live-Vorschau. Nullwerte
  bleiben unsichtbar; negative Werte werden als Fehler gemeldet.

- Der Zigbee2MQTT-Graph färbt Verbindungslinien nach LQI ein. Die Farben für
  LQI 0, 128 und 255 sind in Kachel und IPSView frei wählbar; Zwischenfarben
  werden berechnet und die Skala erscheint in der Legende. Achtung: Eine
  bisher eingestellte einheitliche Verbindungsfarbe wird nicht mehr verwendet.

- Der Zigbee2MQTT-Graph unterscheidet Koordinator, Router und Endgeräte durch
  eigene Knotenfarben und eine Legende. Die Farben können für Kachel und
  unabhängiges IPSView-Design einzeln angepasst werden. Achtung: Eine bisher
  eingestellte Knotenfarbe gilt nun für den Koordinator und unbekannte Typen;
  Router und Endgeräte erhalten standardmäßig eigene Theme-Farben.

- Der Zigbee2MQTT-Graph blendet überlappende Gerätenamen im Überblick aus und
  prüft sie beim Zoomen neu. Verbindungslinien sind dezenter; beim Berühren
  eines Geräts werden dessen Nachbarschaft und Name hervorgehoben. Die
  dargestellten Geräte und Verbindungen bleiben unverändert.

- `EChartsGraphZ2M` (`ECGZ`) visualisiert die zuletzt exportierte
  Zigbee2MQTT-Netzwerkkarte lesend in Kachel und optionalem IPSView-Widget.
  Gerichtete Verbindungen, LQI, Aufnahmezeit, Mindestqualität und ein
  einstellbares Verbindungsbudget bleiben eigenständig gegenüber dem
  allgemeinen Graphen; ein Scan wird nie automatisch gestartet.

- `EChartsGraph` (`ECGR`) zeigt frei ausgewählte Kategorien, Instanzen und
  Variablen mit ausdrücklich konfigurierten Verbindungen als interaktiven
  Graphen in Kachel und optionalem IPSView-Widget. Beide Ausgabewege besitzen
  getrennte Designer und Live-Vorschauen; Zigbee2MQTT-Topologien bleiben
  einem eigenen künftigen Modul vorbehalten.

- `EChartsTreeSystemHealth` kann optional nur Variablen mit Zustandsregel und
  deren übergeordnete Knoten anzeigen. Der Namensfilter wirkt zusätzlich;
  Kachel und IPSView verwenden denselben gefilterten Baum. Bestehende
  Instanzen behalten standardmäßig ihre bisherige Ansicht.

- `EChartsTreeSystemHealth` (`ECTH`) ist ein eigenes Modul für die lesende
  Objekthierarchie mit expliziten Zustandsregeln. `EChartsTree` (`ECTR`) bleibt
  der einfache Baum ohne Statusregeln und Zustandsfarben. Die gelöschte
  Testinstanz wird nicht migriert; für den Anlagenzustand ist eine neue
  Health-Instanz anzulegen.

- `EChartsTreeSystemHealth` kann Integer- und Float-Zustandsvariablen im aktuellen
  Teilbaum per Namensbestandteil suchen, Treffer auswählen und mit gemeinsamen
  Schwellen in die bestehende Regelliste übernehmen. Vorhandene Regeln bleiben
  erhalten; gespeichert wird weiterhin erst beim Übernehmen des Formulars.

## Behoben

- Calendar zeigt auch Teilarchive, deren erster Tagesdatensatz erst im
  Tagesverlauf beginnt. Eine zu strenge Mitternachtsprüfung hatte in diesem
  Fall sämtliche Kalenderwerte verworfen. Fehlende Tage bleiben leer;
  gespeicherte Konfiguration und Archivwerte werden nicht verändert.

- Sankey-Vorschauen zeigen auch größere Konfigurationen vollständig mit
  aktuellen Variablenwerten statt eines Ersatz-Beispielnetzes. Geschlossene
  Flussflächen verhindern Überstände an engen Kurven. Fehler- und
  Nullwertzustände werden ausdrücklich angezeigt. Beschriftungen in Kachel,
  IPSView und Vorschau passen sich dem verfügbaren Knotenplatz an; schmale
  Verbraucher verursachen keinen überlappenden Textblock mehr. Gespeicherte
  Konfigurationen werden dabei nicht verändert.

- Die neuen Linien-, Achsen- und Animationsfelder im Parallel-Designer sind
  auch in der deutschen Symcon-Konsole vollständig übersetzt.

- Der Zigbee-Graph hält die Kopfzeile der Symcon-Kachel auch bei großen
  Force-Layouts und ohne eigenen Diagrammtitel frei. Knoten und Verbindungen
  werden nur unterhalb des Kachelnamens gezeichnet.

- Diagrammtitel in Kacheln von Parallel, Pie, Scatter/Bubble, Tree,
  Anlagenzustandsbaum, Graph, Zigbee-Graph, Treemap und Sunburst bleiben
  sichtbar und liegen mit ihren Diagramminhalten unter dem Symcon-Kachelkopf.
  IPSView behält seinen eigenen Titelbereich.

- Der weiße Streifen über Treemap-Gruppen übernimmt nun den gewählten
  Diagrammhintergrund oder die ausdrücklich festgelegte Rahmenfarbe. Das gilt
  für Kachel und IPSView, auch bei transparentem IPSView-Hintergrund.

- Treemap-Gruppenüberschriften stehen nun auf der jeweiligen Gruppenfarbe
  statt auf einem kontrastarmen hellen Feld. Eine manuell gewählte
  Beschriftungsfarbe wirkt jetzt auch auf Gruppen, Untergruppen und Werte;
  die automatische Farbe bleibt kontrastabhängig. Achtung: Die bisherigen
  automatisch gefärbten Überschriften können nach dem Update anders aussehen.

- Der Zigbee2MQTT-Graph liest große Netzwerkkarten aus dem serverseitigen
  JSON-Export statt über die auf 1 MiB begrenzte Modulrückgabe. Achtung: Nach
  jedem neuen Netzwerkscan müssen die Exportdateien erneut erstellt werden;
  mehrere Netzwerkkarteninstanzen mit gemeinsamem Exportordner werden nicht
  unterstützt.

- Die Health-Tree-Suche nach Zustandsvariablen verarbeitet auch leere oder noch nicht
  gespeicherte Regellisten korrekt. Im Suchdialog werden vorhandene Regeln und
  ausgewählte Treffer als Listenzeilen statt als Formularobjekte übergeben.

- Ungültige Health-Tree-Zustandsregeln nennen im Formular jetzt die betroffenen
  Regelzeilen sowie Sensorname und Variablen-ID. Doppelte Einträge werden markiert; die
  Kritisch-Schwelle lässt sich im Bearbeitungsdialog nur in der zur
  Alarmrichtung passenden Reihenfolge bestätigen.

- Die 30-Tage-Heatmap kennzeichnet ihren Mo–So/Stunden-Durchschnitt nun im
  Diagramm, im Tooltip und im Formular ausdrücklich. Sie berücksichtigt
  weiterhin alle vorhandenen Stundenwerte des gewählten 30-Tage-Zeitraums;
  Datenaggregation und gespeicherte Einstellungen ändern sich nicht.

- Die Heatmap zählt sieben Tage jetzt als sieben datierte lokale Tage bis heute
  statt als 168 Stunden mit zwei vermischten Randtagen. Achtung: Dadurch
  können bisherige 7-Tage-Zellenwerte nach dem Modulupdate anders aussehen.

- Die automatische Wertbeschriftungsfarbe der Heatmap passt sich nun je
  Zelle an helle und dunkle Füllfarben an. Eine manuell gewählte Farbe
  bleibt unverändert. Achtung: Die bisherige automatische Schriftfarbe kann
  sich nach dem Modulupdate sichtbar ändern.

- Die Temperatur-Vorlagen der Heatmap färben Zellen jetzt nach festen
  Celsius-Klassen statt den aktuellen Archivwertebereich auf alle Farben
  zu strecken. Die Legende zeigt die festen Vorlagengrenzen. Achtung:
  Bereits ausgewählte Vorlagen ändern dadurch nach dem Modulupdate ihre
  Darstellung; die individuelle Zwei-Farben-Skala bleibt unverändert.

- Die Heatmap-Farbskala zeigt in Kachel und IPSView ihren kleinsten und größten
  Archivwert mit der konfigurierten Einheit an.

- Die beiden Scatter-Werkzeuge für Rechteckauswahl und Zurücksetzen sind in
  Kachel und IPSView kontrastreicher und wachsen mit der verfügbaren
  Diagrammfläche. Die Plotfläche hält dafür ausreichend Abstand frei.

- Alle Diagrammkacheln übernehmen nur noch vollständige Visualisierungszustände
  als Datenaktualisierung. Andere oder unvollständige Nachrichten ersetzen
  die Darstellung nicht mehr durch eine irreführende Ladefehlermeldung.

- Ausstehende Größenänderungen greifen nach dem Verlassen oder Neuaufbau einer
  Kachel nicht mehr auf bereits freigegebene ECharts-Instanzen zu.

- Die Scatter-Farbskala verwendet nun die ECharts-Abmessungen korrekt:
  eine flache, an die Kachel- oder IPSView-Breite angepasste horizontale
  Legende statt eines schmalen senkrechten Streifens. Die Formularvorschau
  zeigt ebenfalls eine breitere Skala.

## Hinzugefügt

- `EChartsTree` kann Variablen über einen Namensbestandteil filtern, etwa
  „Batterie“. Kategorien und Instanzen auf dem Weg zu passenden Variablen
  bleiben erhalten. Ohne Filter ändert sich die bestehende Ansicht nicht.

- Die maximale Knotenzahl beider Tree-Module lässt sich testweise bis 3000 statt
  bisher 500 einstellen. Der Standardwert von 80 Knoten bleibt unverändert;
  die Obergrenze für Statusregeln steigt entsprechend auf 3000.

- `EChartsTreeSystemHealth` kann ausdrücklich ausgewählte Bool- und Zahl-Variablen als
  Anlagenzustand bewerten. Pro Quelle lassen sich Alarmrichtung sowie Warn-
  und Kritisch-Schwelle festlegen; Eltern zeigen den höchsten Zustand ihrer
  sichtbaren Nachkommen. Kachel und IPSView können die Zustandsfarben getrennt
  gestalten. Der einfache Tree bleibt ohne Zustandsbewertung.

- `EChartsTree` (`ECTR`) zeigt Kategorien, Instanzen und Variablen unter einer
  gewählten Symcon-Kategorie oder -Instanz als interaktiven Baum. Tiefe,
  Knotenzahl, versteckte Objekte, Layout, Richtung, Linien, Beschriftung,
  Farben und Animation sind einstellbar; Kachel und IPSView besitzen getrennte
  Designer mit Live-Vorschau. Die Objekte und Werte werden nur gelesen.

- Die maximale Knotenzahl von `EChartsTree` lässt sich testweise bis 500 statt
  bisher 200 einstellen. Der Standardwert von 80 Knoten bleibt unverändert.

- `EChartsTree` kann jetzt ausdrücklich an der obersten Symcon-Ebene starten.
  Die bestehende Objektauswahl bleibt gespeichert und wird nur während dieser
  Option ignoriert; eine bisher leere Auswahl wird nicht automatisch aktiviert.

- Die Heatmap bietet eine Woche, die letzten vollständigen 1/3/6/12
  Kalendermonate und den Zeitraum seit dem 1. Januar. Für längere Zeiträume
  werden alle Archivstunden in mehreren begrenzten Abfragen berücksichtigt;
  die Navigation folgt Wochen, Monatsblöcken beziehungsweise dem gleichen
  Jahresabschnitt im Vorjahr. Achtung: Bestehende Instanzen mit `7d` oder
  `30d` müssen nach dem Modulupdate einen neuen Zeitraum auswählen; „1 Monat“
  entspricht nicht den bisherigen gleitenden 30 Tagen.

- Die Heatmap zeigt den tatsächlichen Datumsbereich und bietet in Kachel und
  IPSView Zurück/Vor/Heute für lückenlos anschließende 7- oder 30-Tage-Fenster.
  Die 30-Tage-Ansicht bleibt nach Wochentag und Stunde gemittelt.

- Die individuelle Heatmap-Farbskala bietet bis zu drei optionale
  Zwischenfarben und damit zwei bis fünf Farbstützpunkte. Ohne zusätzliche
  Farben bleibt der bisherige Zwei-Farben-Verlauf bestehen; Kachel und
  unabhängiges IPSView-Design können getrennte Paletten nutzen.

- Die Heatmap bietet zehn mehrfarbige Temperatur-Vorlagen: Tempera-12/-6
  sowie acht aus Schiewe (2023) näherungsweise rekonstruierte Beispiele vom
  April 2023. Kachel, IPSView und Formularvorschau nutzen dieselben Klassenfarben;
  die bisherige Zwei-Farben-Skala bleibt die Voreinstellung.

- `EChartsHeatmap` (`ECHM`) zeigt eine archivbasierte Wochentag-Stunden-Matrix
  mit Farbskala, Tooltip, optionalen Zellenwerten und eigenem Kachel- und
  IPSView-Designer. Fehlende Archivstunden bleiben leer; die Vorschauen
  verwenden gekennzeichnete Beispieldaten.

- Scatter/Bubble kann Punktbeschriftungen optional an Diagrammgröße und
  Punktdichte anpassen. Kachel und unabhängiges IPSView-Design besitzen
  getrennte Schalter; bisherige Instanzen bleiben unverändert. Die Punkte
  und Tooltips bleiben vollständig erhalten.

- Scatter/Bubble bietet in Kachel und IPSView optional die ECharts-
  Rechteckauswahl. Ausgewählte Punkte werden mit Anzahl und X/Y-Wertebereich
  zusammengefasst; die übrigen Punkte werden abgeblendet. Beide Designer
  können die Auswahl unabhängig aktivieren.

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

- Der gemeinsame IPSView-Transport ermittelt die Serveradresse auch in
  isolierten `data:`-Einbettungen ohne eigenes Basis-Element. Frischer Zustand
  und laufende Wertänderungen erreichen dadurch wieder die Diagramme, statt
  beim gespeicherten WebContent-Startzustand stehenzubleiben. Instanzkonfiguration,
  Quellen und Archivdaten bleiben unverändert.

- ThemeRiver beschriftet mehrtägige Zeitachsen mit dem Datum ohne wiederholte
  Mitternachtszeiten. Achse und Legende erhalten auch bei vergrößerten Schriften
  getrennte Bereiche mit mehr Abstand; die Vorschau berücksichtigt beides.

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
