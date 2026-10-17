# EChartsThemeRiver

Zeigt mehrere archivierte Messreihen als farbige Bänder auf einer Zeitachse.
Die Bandbreite entspricht dem jeweiligen Stunden- oder Tagesmittelwert.
Geeignet ist das zum Beispiel für die Leistungsaufnahme mehrerer Verbraucher
in Watt. Es werden keine Energieverbräuche aus Leistungswerten berechnet.

## Voraussetzungen und Einrichtung

1. Eine Instanz `EChartsThemeRiver` anlegen und mit dem vorhandenen
   `EChartsGateway` verbinden.
2. Unter **ThemeRiver-Quellen** ein bis acht unterschiedliche Integer- oder
   Float-Variablen hinzufügen, die im Archiv als Standardvariablen aufgezeichnet
   werden. Alle Quellen müssen dieselbe Einheit haben. Zählerarchive werden
   nicht unterstützt.
3. Optional pro Quelle eine eigene Beschriftung und Farbe festlegen.
   Ohne Beschriftung gilt der aktuelle Variablenname; gleiche Namen sind
   zulässig. Automatische Farben folgen dem gewählten Theme.
4. Den Titel, Zeitraum und das Intervall wählen. Standardmäßig werden die
   letzten sieben Tage in Stundenintervallen angezeigt.

Die Einheit und Nachkommastellen stammen aus der Variablendarstellung oder
einem kompatiblen Profil. Die Einheit muss fachlich vergleichbar sein:
Watt, Prozent und andere Größen dürfen nicht miteinander vermischt werden.
Aktuelle Variablenwerte ersetzen keine Archivdaten.

## Zeitraum, Mittelwerte und Lücken

Wählbar sind 24 Stunden, sieben oder 30 Tage mit Stunden- oder Tagesintervallen.
Stundenfenster umfassen 24, 168 oder 720 abgeschlossene Stunden; Tagesfenster
umfassen einen, sieben oder 30 abgeschlossene lokale Kalendertage. Das laufende
Intervall bleibt ausgeschlossen. Tagesgrenzen berücksichtigen die
Symcon-Zeitzone und Zeitumstellungen. Der automatische Abruf erfolgt minütlich.

Nur **nichtnegative, endliche Mittelwerte** sind zulässig. Echte Nullwerte
bleiben gültig; negative Mittelwerte führen zu einer sichtbaren Fehlermeldung.
Die Darstellung ist weder eine Prozentnormierung noch eine Qualitätsbewertung.

Ein Intervall wird nur dargestellt, wenn **alle gewählten Quellen** dafür
einen Archivwert liefern. Fehlt eine Quelle, bleibt das ganze Intervall leer;
ein Hinweis erklärt die Lücke. Das gilt auch für Archive, deren Aufzeichnung
erst innerhalb des gewählten Zeitraums begonnen hat. Zusammenhängende gültige
Abschnitte bleiben sichtbar. Fehlende Werte werden weder mit Null aufgefüllt
noch durch Verbindung über die Lücke verdeckt.

Alle Abschnitte verwenden denselben Maßstab. Die glatte Bandform verbindet
vorhandene Mittelwerte grafisch, erzeugt aber keine zusätzlichen Messungen.
Der Tooltip nennt das tatsächliche Intervall mit Zeitzonenoffset und die
Originalmittelwerte aller Quellen. Bestehen alle vorhandenen Mittelwerte aus
Null, bleiben Zeitachse, Legende und ein erläuternder Hinweis sichtbar.

## Kachel und IPSView gestalten

Beide Designer bieten Theme, Tooltip, Banddeckkraft (0–100 %, Standard 85 %),
Konturbreite/-farbe, Legende, optionale Bandbeschriftungen, Zeitbeschriftungen,
Teilstriche sowie Schriftgrößen und Farben für Titel, Achse, Bänder und Legende.
Bandbeschriftungen sind zunächst aus; die Legende zeigt die Quellen auch bei
kleinen Flächen. Sie kann geblättert werden, blendet aber keine Quellen aus.
Animationen einschließlich Dauer und Bewegungskurve sind einstellbar.
Die Zeitachse zeigt bei kurzen Stundenfenstern Uhrzeiten, bei mehrtägigen
Zeiträumen und Tageswerten das Datum. Die Beschriftungsdichte passt sich der
Breite und Schriftgröße an; zur Legende bleibt ein eigener Abstand reserviert.

Zusätzlich stehen in beiden Designern folgende Einstellungen zur Verfügung:

- **Bänder und Darstellung:** Bandhöhe 20–100 % (Standard 84 %) relativ zum
  verfügbaren Diagrammbereich und durchgezogene, gestrichelte oder gepunktete
  Konturen. Alle Abschnitte behalten weiterhin denselben Wertmaßstab.
- **Schatten und Hervorhebung:** Schattenunschärfe 0–20 px (Standard 0),
  Farbe und horizontaler/vertikaler Versatz −20 bis 20 px (Standard 0).
  Hervorhebung beim Überfahren lässt sich abschalten. Der Fokus kann das
  überfahrene Band, seinen zusammenhängenden Abschnitt oder keine Abdunkelung
  betreffen. Die Restdeckkraft anderer Bänder beträgt standardmäßig 10 %
  ihrer normalen Deckkraft, einstellbar von 0–100 %.
- **Zeitachse:** Achsenlinie ein-/ausblenden, Breite 0–5 px (Standard 1),
  Linienart und Beschriftungsabstand 0–30 px (Standard 12). Das Zeitformat
  ist automatisch oder ausdrücklich Datum beziehungsweise Datum und Uhrzeit.
  Letzteres erscheint zweizeilig. Optionale Rasterlinien haben eigene Farbe,
  Linienart, Breite 0–5 px und Deckkraft 0–100 %; standardmäßig sind sie aus,
  gestrichelt, 1 px breit und 20 % deckend.
- **Legende:** oben unter dem Titel oder unten (Standard), linksbündig
  (Standard), mittig oder rechtsbündig. Als Symbol dienen abgerundete Rechtecke
  (Standard), Rechtecke, Kreise oder Rauten. Symbolbreite 6–40 px (Standard 25),
  Symbolhöhe 6–30 px (Standard 14) und Eintragsabstand 0–30 px (Standard 10)
  sind wählbar. Bei Platzmangel bleibt die Legende blätterbar.
- **Typografie und Farben:** Arial (Standard), Verdana, Georgia oder
  nichtproportionale Schrift. Titel, Zeitbeschriftungen, Bandbeschriftungen
  und Legende können jeweils fett oder kursiv sein. Standardmäßig ist nur
  der Titel fett; Kursivschrift ist aus.

Automatische Schattenfarbe ist teiltransparentes Schwarz; automatische
Rasterfarbe folgt dem Theme. Neue Einstellungen übernehmen diese bisherigen
Grundwerte, ohne Quellen, Zeitraum oder bereits gewählte Farben zu verändern.
Die Bandglättung ist fest; sie ist kein zusätzlicher Messwert oder ein
einstellbares Interpolationsverfahren. Überfahreinstellungen wirken in der
laufenden Darstellung, nicht in der schematischen Formularvorschau.

Die Formularvorschauen verwenden ausdrücklich gekennzeichnete Beispieldaten
einschließlich einer Lücke. Änderungen werden vor dem Speichern sichtbar,
ohne Archivabfragen auszulösen. Kachel und IPSView passen sich ihrer Größe an;
der Diagrammtitel steht in der Kachel unter dem Symcon-Kachelkopf.

Unter **IPSView-Design** lässt sich eine eigenständige WebContent-Variable
aktivieren (`IPSViewThemeRiver`, String, nur lesend). Standardmäßig erbt sie
das Kacheldesign. Für eine unabhängige Gestaltung **Kacheldesign verwenden**
ausschalten; bei Bedarf das gespeicherte Kacheldesign einmalig kopieren.
Quellen und Zeitraum bleiben gemeinsam. Hintergrundfarbe, Deckkraft und
Anpassung an den IPSView-Hintergrund sind separat einstellbar.

## Öffentliche Funktionen

- `ECRV_GetThemeRiverData(int $InstanceID): string` liefert das aktuelle
  Diagrammmodell als JSON, einschließlich Quellen, Intervallwerten und Lücken.
- `ECRV_RefreshArchive(int $InstanceID): void` aktualisiert die Ausgabe lesend.
- `ECRV_GetThemeRiverDiagnostic(int $InstanceID): string` liefert den
  Konfigurationszustand als JSON.
- `ECRV_GetIPSViewHTML(int $InstanceID): string` liefert das IPSView-Dokument.

Das Modul verändert weder Messwerte noch Archivdaten oder deren Konfiguration.
