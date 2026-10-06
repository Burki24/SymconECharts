# EChartsTimeSeries

Gerätemodul für historische numerische Symcon-Werte als responsive Apache-
ECharts-Zeitreihe. Eine Instanz verarbeitet eine bis acht eindeutige Quellen,
bis zu acht Einheitengruppen und rendert eine native Symcon-Kachel.

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
- kollabierter Kacheldesigner mit sofortiger SVG-Vorschau für Theme,
  Legendenposition, Zoom, Linienstärke, Glättung, Datenpunkte,
  Flächendeckkraft, Raster und Achsensichtbarkeit;
- Rohwertfortschreibung über `VM_UPDATE`, ohne bei jedem Messwert das Archiv
  erneut zu laden;
- aggregierte Reihen enden am letzten abgeschlossenen Zeitfenster und werden
  nach der nächsten Intervallgrenze neu geladen.

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
   Seite.
5. Optional den Kacheldesigner öffnen und Darstellung sowie Vorschau anpassen.

Bis zu acht unterschiedliche effektive Einheiten sind möglich. Automatisch
verteilt die Einheitenachsen auf beide Seiten; mehrere Achsen derselben Seite
werden versetzt dargestellt.
`raw` bleibt immer eine echte Rohwertabfrage; `realtime` ist der ausdrücklich
archivfreie Live-Modus und nur `auto` wählt selbstständig eine
Verdichtungsstufe.

## Entwicklungsstand und Testgrenzen

Die erste native Vertikale vom Archivvertrag bis zur Kachel ist implementiert
und durch lokale Vertrags- und Integrationstests abgesichert. Ein realer
Symcon-Archiv- und Browsertest sowie die getrennte IPSView-Ausgabe stehen noch
aus. Der aktuelle Designer wirkt ausschließlich auf die native Kachel; das
spätere IPSView-Design verwendet denselben fachlichen Designvertrag, bleibt
aber unabhängig konfigurierbar. Die Zielplattform bleibt Symcon 9.0/9.1 mit
PHP 8.5.
