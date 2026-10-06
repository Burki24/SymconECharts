# EChartsTimeSeries

Gerätemodul für historische numerische Symcon-Werte als responsive Apache-
ECharts-Zeitreihe. Eine Instanz verarbeitet eine bis acht eindeutige Quellen,
bis zu acht Einheitengruppen und rendert eine native Symcon-Kachel sowie
optional ein eigenständiges IPSView-WebContent-Widget.

## Aktueller Funktionsumfang

- rollende Zeiträume von 1 Stunde, 6 Stunden, 24 Stunden, 7 Tagen und 30 Tagen;
- ausdrücklich wählbare Rohwerte, automatische Verdichtung oder feste
  Verdichtung auf Minute, 5 Minuten, 15 Minuten, Stunde oder Tag;
- Echtzeitdarstellung nicht archivierter Variablen ab dem Öffnen der Kachel;
- Linien- oder Flächendarstellung je Quelle;
- automatische Übernahme von Einheit und Nachkommastellen aus der
  Variablendarstellung mit manuellen Rückfallwerten;
- eine gemeinsame Y-Achse je effektiver Einheit mit automatischer oder
  expliziter Anordnung links beziehungsweise rechts;
- farblich gekoppelte Wertachsen: Achsenlinie, Teilstriche, Skalenwerte und
  Einheit übernehmen die Farbe der ersten zugeordneten Datenreihe;
- gemeinsames Punktbudget von 200 bis 8.000 Punkten, maximal 2.000 je Reihe;
- Durchschnitt, Zählersumme, Minimum und Maximum entsprechend dem
  versionierten Archivvertrag;
- native Kachel mit lokalen, integritätsgeprüften ECharts- und Theme-Dateien;
- Mausrad-Zoom in der nativen Kachel und im IPSView-WebContent-Widget;
- kollabierter Kacheldesigner mit sofortiger SVG-Vorschau für Theme,
  Legendenposition, Zoom, Linienstärke, Glättung, Datenpunkte,
  Flächendeckkraft, Raster und Achsensichtbarkeit;
- optionales IPSView-WebContent-Widget mit demselben Datenmodell und wahlweise
  geerbtem oder vollständig unabhängigem Zeitreihendesign;
- Rohwertfortschreibung über `VM_UPDATE`, ohne bei jedem Messwert das Archiv
  erneut zu laden;
- aggregierte Reihen enden am letzten abgeschlossenen Zeitfenster und werden
  nach der nächsten Intervallgrenze neu geladen.

Das Theme **Automatisch (Symcon-Design)** übernimmt in der nativen Kachel
Hintergrund, Text- und Oberflächenfarben aus dem aktiven hellen oder dunklen
Symcon-Design. Fest gewählte ECharts-Themes verwenden weiterhin ihre eigenen
Farben.

Archivmodi lesen das Symcon-Archiv ausschließlich über die gemeinsame
`EChartsGateway`-Operation `archive.read`. Der getrennte Echtzeitmodus beginnt
mit `current.read` und sammelt danach Variablenänderungen nur im geöffneten
Browser. Er benötigt kein Archiv, besitzt nach einem Neuladen aber bewusst
keine rückwirkende Historie. Das Modul aktiviert kein Logging und
verändert weder Archivkonfiguration noch Archivdaten. Eine leere gültige
Archivantwort bleibt eine leere Datenreihe. Bei gekürzten Rohwerten zeigt die
Kachel einen Hinweis auf das wirksame Punktbudget.

## Einrichtung

1. Eine bestehende gemeinsame `EChartsGateway`-Instanz als Parent auswählen.
2. Eine bis acht Integer- oder Float-Variablen konfigurieren. Archivmodi
   benötigen archivierte Quellen; `realtime` funktioniert auch ohne Archiv.
3. Zeitraum, Datenmodus und Punktbudget wählen.
4. Optional Beschriftung, Einheit, Nachkommastellen, Farbe, Stil, Reducer und
   Achsenseite pro Quelle anpassen. Quellen mit derselben effektiven Einheit
   teilen eine Achse und verwenden deshalb dieselbe ausdrücklich gewählte
   Seite. Der Farbwähler verwendet **Automatisch** für die Farbfolge des
   gewählten Themes; eine ausgewählte Farbe überschreibt sie für diese Reihe.
5. Optional den Kacheldesigner öffnen und Darstellung sowie Vorschau anpassen.
6. Optional im Abschnitt **IPSView-Design** die WebContent-Ausgabe aktivieren.
   Standardmäßig übernimmt sie das Kacheldesign. Für eine abweichende
   Gestaltung das Kacheldesign kopieren und anschließend den unabhängigen
   IPSView-Designer bearbeiten. **An Hintergrundfarbe anpassen** lässt den
   tatsächlichen IPSView-Hintergrund durchscheinen. Die **Hintergrundfarbe**
   verwendet automatisch die Theme-Farbe oder eine frei gewählte Tönung; die
   **Hintergrunddeckkraft** dosiert den darüberliegenden Theme-Hintergrund von
   0 bis 100 Prozent. Die erzeugte Variable **Zeitreihe für
   IPSView** wird als HTML-Widget in IPSView platziert.

Beim Abschalten bleibt eine bereits angelegte WebContent-Variable erhalten,
wird aber nicht weiter aktualisiert. Sie kann im selben Formular ausdrücklich
gelöscht oder bei aktivierter Ausgabe neu erzeugt werden. Für eigene Skripte
liefert `ECTS_GetIPSViewHTML($InstanceID)` das vollständige eigenständige
HTML-Dokument.

Bis zu acht unterschiedliche effektive Einheiten sind möglich. Automatisch
verteilt die Einheitenachsen auf beide Seiten; mehrere Achsen derselben Seite
werden versetzt dargestellt.
`raw` bleibt immer eine echte Rohwertabfrage; `realtime` ist der ausdrücklich
archivfreie Live-Modus und nur `auto` wählt selbstständig eine
Verdichtungsstufe.

## Entwicklungsstand und Testgrenzen

Die Vertikale vom Archivvertrag bis zur nativen Kachel und zur optionalen
IPSView-Ausgabe ist implementiert und durch lokale Vertrags- und
Integrationstests abgesichert. Ein realer Symcon-Archiv- und Browsertest sowie
ein IPSView-Laufzeittest stehen noch aus. Im Echtzeitmodus wird das
WebContent-Dokument bei einer Wertänderung mit dem aktuellen Punkt erneuert;
eine fortlaufende, nicht persistierte Browserhistorie sammelt weiterhin nur
die geöffnete native Kachel. Die Zielplattform bleibt Symcon 9.0/9.1 mit PHP
8.5.
