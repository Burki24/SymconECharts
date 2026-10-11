# EChartsTimeSeries

Gerätemodul für historische numerische Symcon-Werte als responsive Apache-
ECharts-Zeitreihe. Eine Instanz verarbeitet eine bis acht eindeutige Quellen,
bis zu acht Einheitengruppen und rendert eine native Symcon-Kachel sowie
optional ein eigenständiges IPSView-WebContent-Widget.

Linien und Datenpunkte, Flächen und Achsen sowie Schrift und Farben sind in
beiden Designern als eigene einklappbare Bereiche angeordnet; die Vorschau
folgt darunter.

## Animation

Für Start und Aktualisierung lassen sich außerdem die **Bewegungskurve** und
eine feste **Verzögerung** (0–3000 ms) getrennt wählen. Ohne Änderung gilt
jeweils cubicInOut ohne Verzögerung. Die Verzögerung betrifft das ganze Diagramm,
nicht jeden Datenpunkt einzeln.

Im Kachel- und unabhängigen IPSView-Designer steuern **Animation**, **Startanimation** und **Aktualisierungsanimation** die ECharts-Übergänge. Beide Zeiten sind von 0 bis 3000 ms einstellbar; 0 ms unterdrückt den jeweiligen Übergang. Die Systemeinstellung für reduzierte Bewegung schaltet Animationen immer ab. Bei **Kacheldesign verwenden** übernimmt IPSView die Kachelwerte. Bei Zeitreihen ist Animation wegen der Datenmenge standardmäßig ausgeschaltet.

## Voraussetzungen

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5;
- eine aktive [EChartsGateway-Instanz](../EChartsGateway/README.md);
- eine bis acht Integer- oder Float-Variablen;
- eine Symcon-Archivinstanz für historische Modi; der Echtzeitmodus benötigt
  kein aktiviertes Logging;
- IPSView nur für das optionale WebContent-Widget.

Jede Reihe bleibt über ihre Variablen-ID eindeutig, auch wenn mehrere
Variablen denselben Namen oder dieselbe eigene Beschriftung haben. Solche
gleichlautenden Reihen behalten in Legende, Tooltip und Vorschau ihre Namen
ohne ID-Zusatz; Live-Updates bleiben der richtigen ID zugeordnet.
Objektpfade werden dafür nicht ausgewertet.

## Aktueller Funktionsumfang

- rollende Zeiträume von 1 Stunde, 6 Stunden, 24 Stunden, 7 Tagen und 30 Tagen
  sowie frei definierbare Fenster in Minuten, Stunden, Tagen oder Wochen;
- kalendergebundene Ansichten für heute, gestern, die laufende Woche und den
  laufenden Monat; die Grenzen folgen der Symcon-Zeitzone und berücksichtigen
  Zeitumstellungen;
- automatische Zeitachsenbeschriftung oder ausdrückliche Anzeige von Uhrzeit,
  Datum beziehungsweise Datum und Uhrzeit;
- ausdrücklich wählbare Rohwerte, automatische Verdichtung oder feste
  Verdichtung auf Minute, 5 Minuten, 15 Minuten, Stunde oder Tag;
- Echtzeitdarstellung nicht archivierter Variablen ab dem Öffnen der Kachel;
- wahlweise durchgehende Linien, automatische Erkennung zeitlicher Datenlücken
  je Reihe oder eine feste maximale Unterbrechungsdauer in Minuten;
- Linien- oder Flächendarstellung je Quelle;
- optionales Einzeldesign je Quelle für Linienart, Linienstärke, Glättung,
  Punktsymbol und Punktgröße sowie Flächendeckkraft;
- einfarbige Flächen, lineare Farbverläufe oder sicher importierte,
  größenverstellbare SVG-Flächenmuster;
- automatische Übernahme von Einheit und Nachkommastellen aus der
  Variablendarstellung mit manuellen Rückfallwerten;
- eine gemeinsame Y-Achse je effektiver Einheit mit automatischer oder
  expliziter Anordnung links beziehungsweise rechts;
- automatische Wertachsenbereiche sowie wahlweise die Übernahme von Minimum
  und Maximum aus der Variablendarstellung oder eine manuelle Vorgabe;
- quellenbezogene Referenzlinien und Wertebereiche mit Beschriftung,
  Reihenfarbe oder eigener Farbe sowie konfigurierbarer Linienart,
  Linienstärke und Bereichsdeckkraft;
- farblich gekoppelte Wertachsen: Achsenlinie, Teilstriche, Skalenwerte und
  Einheit übernehmen standardmäßig die Farbe der ersten zugeordneten
  Datenreihe;
- separat einstellbare Titel-, Legenden-, Achsen- und Rasterfarben sowie
  Titel-, Legenden- und Achsenschriftgrößen (50 bis 200 Prozent) im Kachel-
  und unabhängigen IPSView-Designer. **Automatisch** behält Theme-Farben und
  die farbliche Kopplung der Wertachsen an ihre Datenreihe bei; eine explizite
  Achsenfarbe gilt für alle Zeit- und Wertachsen;
- gemeinsames Punktbudget von 200 bis 8.000 Punkten, maximal 2.000 je Reihe;
- Durchschnitt, Zählersumme, Minimum und Maximum entsprechend dem
  versionierten Archivvertrag;
- native Kachel mit lokalen, integritätsgeprüften ECharts- und Theme-Dateien;
- Mausrad-Zoom in der nativen Kachel und im IPSView-WebContent-Widget;
- der gewählte Zoom-Ausschnitt bleibt bei normalen Datenaktualisierungen erhalten;
- kollabierter Kacheldesigner mit sofortiger SVG-Vorschau für Theme,
  Legendenposition, Zoom, Linienstärke, Glättung, Datenpunkte,
  Flächendeckkraft, Raster, Achsensichtbarkeit, Schrift und Farben;
- optionales IPSView-WebContent-Widget mit demselben Datenmodell und wahlweise
  geerbtem oder vollständig unabhängigem Zeitreihendesign sowie einem
  wahlweise von der Kachel abweichenden Zeitraum und Zeitachsenformat;
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
3. Zeitraum, Beschriftung der Zeitachse, Datenmodus und Punktbudget wählen.
   Bei **Benutzerdefiniert** werden Zeitraumwert und -einheit verwendet.
   **Heute**, **Gestern**, **Diese Woche** und **Dieser Monat** verwenden lokale
   Kalendergrenzen statt eines rollenden Sekundenfensters. **Gestern** ist ein
   abgeschlossener Zeitraum und benötigt deshalb einen Archivdatenmodus. Die
   Zeitachse kann ECharts automatisch beschriften oder ausdrücklich Uhrzeit,
   Datum beziehungsweise beides anzeigen.
   Die Datenlückenerkennung ist standardmäßig deaktiviert. **Automatisch**
   unterbricht eine Linie, wenn ein Abstand größer als das Dreifache des
   typischen Messabstands dieser Reihe ist. Alternativ lässt sich ein fester
   Maximalabstand in Minuten für alle Reihen festlegen.
4. Optional Beschriftung, Einheit, Nachkommastellen, Farbe, Stil, Reducer,
   Achsenseite und Achsenbereich pro Quelle anpassen. Der Bereich bleibt
   standardmäßig automatisch, kann aber aus der Variablendarstellung oder aus
   manuell eingegebenem Minimum und Maximum stammen. Quellen mit derselben
   effektiven Einheit teilen eine Achse und verwenden deshalb dieselbe
   ausdrücklich gewählte Seite und denselben expliziten Bereich. Automatische
   Quellen übernehmen dabei den expliziten Bereich ihrer Einheitengruppe.
   Der Farbwähler verwendet **Automatisch** für die Farbfolge des
   gewählten Themes; eine ausgewählte Farbe überschreibt sie für diese Reihe.
   Über das Zahnrad einer Quelle kann außerdem das **Einzeldesign** aktiviert
   werden. Ohne Einzeldesign erbt die Reihe weiterhin alle gemeinsamen Werte
   des Kachel- beziehungsweise IPSView-Designers. SVG-Flächenmuster werden nur
   für den Flächenstil verwendet und vor der Ausgabe sicher validiert.
5. Optional den Kacheldesigner öffnen und Darstellung sowie Vorschau anpassen.
6. Optional unter **Referenzlinien und Wertebereiche** bis zu 32 Markierungen
   ergänzen. Jede Markierung ist einer konfigurierten Quelle zugeordnet und
   verwendet damit deren Wertachse. Eine Referenzlinie benötigt einen Wert;
   ein Wertebereich ein Minimum und ein größeres Maximum. Ohne eigene Farbe
   übernimmt die Markierung die Reihenfarbe.
7. Optional im Abschnitt **IPSView-Design** die WebContent-Ausgabe aktivieren.
   Standardmäßig übernimmt sie Kacheldesign und Zeiteinstellungen. Wird
   **Zeiteinstellungen der Kachel verwenden** deaktiviert, erhält IPSView
   einen eigenen Zeitraum und ein eigenes Format der Zeitachsenbeschriftung.
   Diese Zeiteinstellungen stehen bei den allgemeinen Zeitraumoptionen,
   außerhalb der beiden Designer. Für eine abweichende Gestaltung zuerst
   **Kacheldesign verwenden** ausschalten. Solange es aktiv ist, sind die
   eigenen Designfelder und der Knopf **Kacheldesign nach IPSView kopieren und
   unabhängig bearbeiten** direkt im Abschnitt **IPSView-Design** deaktiviert.
   Nach dem Ausschalten werden beide sofort aktiv. Der Knopf kopiert das gespeicherte Kacheldesign
   einmalig und überschreibt bisherige unabhängige IPSView-Designwerte; diese
   können anschließend separat
   bearbeitet werden. Änderungen am Kacheldesigner vor dem Kopieren speichern.
   **An Hintergrundfarbe anpassen** lässt den tatsächlichen
   IPSView-Hintergrund durchscheinen. Die **Hintergrundfarbe**
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

## Variablen

Standardmäßig legt das Modul keine Statusvariablen an. Bei aktivierter
IPSView-Ausgabe wird die Stringvariable **Zeitreihe für IPSView** mit dem Ident
`IPSViewTimeSeries` und einer WebContent-Darstellung erzeugt. Beim Abschalten
bleibt eine bereits angelegte Variable erhalten, bis sie im Formular
ausdrücklich gelöscht wird.

## Öffentliche Funktionen

```php
$json = ECTS_GetTimeSeriesData($InstanceID);
$html = ECTS_GetIPSViewHTML($InstanceID);
```

`ECTS_GetTimeSeriesData()` liefert das aktuelle versionierte Datenmodell als
JSON. `ECTS_GetIPSViewHTML()` liefert das vollständige eigenständige
HTML-Dokument für IPSView.

## Lizenz

Lizenz der eigenen Beiträge:
[PolyForm Noncommercial License 1.0.0](../LICENSE).
