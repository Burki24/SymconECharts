# EChartsThemeRiver

Zeigt mehrere archivierte Messreihen als farbige Bänder auf einer Zeitachse.
Die Bandbreite entspricht dem jeweiligen Stunden- oder Tagesmittelwert.
Geeignet ist das zum Beispiel für die Leistungsaufnahme mehrerer Verbraucher
in Watt. Es werden keine Energieverbräuche aus Leistungswerten berechnet.

## Voraussetzungen und Einrichtung

1. Eine Instanz `EChartsThemeRiver` anlegen und mit dem vorhandenen
   `EChartsGateway` verbinden.
2. Unter **ThemeRiver-Quellen** ein bis 16 unterschiedliche Integer- oder
   Float-Variablen hinzufügen, die im Archiv als Standardvariablen aufgezeichnet
   werden. Alle Quellen müssen dieselbe Einheit haben. Zählerarchive werden
   nicht unterstützt.
3. Optional pro Quelle eine eigene Beschriftung und Farbe festlegen.
   Ohne Beschriftung gilt der aktuelle Variablenname; gleiche Namen sind
   zulässig. Automatische Farben folgen dem gewählten Theme.
4. Den Titel, Zeitraum und das Intervall wählen. Standardmäßig werden die
   letzten sieben Tage in Stundenintervallen angezeigt.
5. Optional **Bandauswahl aktivieren** einschalten und pro Quelle
   **Anfangs sichtbar** festlegen. Ohne Aktivierung werden weiterhin alle
   konfigurierten Bänder dargestellt.

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

Alternativ ist eine **benutzerdefinierte Dauer** von 1–1000 Minuten, Stunden,
Tagen oder Wochen möglich. Sie endet an der letzten abgeschlossenen
Intervallgrenze und enthält nur ganze Stunden beziehungsweise lokale Tage
innerhalb dieser verstrichenen Dauer, höchstens 720 Intervalle pro Quelle.
Bei Überschreitung bitte den Zeitraum verkürzen oder Tagesintervalle wählen.
Sehr kurze Zeiträume können leer sein; dann werden keine Archivwerte gelesen
und keine Ersatzwerte erzeugt.

Eine benutzerdefinierte Tagesangabe bedeutet 24 verstrichene Stunden, eine
Woche sieben solche Tage. Feste Tages-Vorgaben zählen dagegen weiterhin lokale
Kalendertage. Beim Herbstwechsel passt deshalb kein vollständiger
25-Stunden-Tag in eine benutzerdefinierte Dauer von einem Tag.
Die Zeitkonfiguration gilt gemeinsam für Kachel und IPSView.

Nur **nichtnegative, endliche Mittelwerte** sind zulässig. Echte Nullwerte
bleiben gültig; negative Mittelwerte führen zu einer sichtbaren Fehlermeldung.
Die Darstellung ist weder eine Prozentnormierung noch eine Qualitätsbewertung.

Ein Intervall wird nur dargestellt, wenn **alle konfigurierten Quellen** dafür
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

Das gemeinsame Ladebudget beträgt höchstens **16.000 Aggregate pro
Aktualisierung**, maximal 1.000 je Quelle. Auch ausgeblendete Quellen zählen
dazu. Die Grenze von 720 abgeschlossenen Intervallen je Quelle bleibt
unverändert; bei 16 vollständigen Quellen sind das höchstens 11.520
Intervallwerte. Gekürzte oder übergroße Antworten werden als Fehler gemeldet,
nicht als vollständige Teilansicht ausgegeben. Kürzere Zeiträume oder
Tagesintervalle helfen bei zu großen Abfragen.

## Optionale Bandauswahl

Standardmäßig ist **Bandauswahl aktivieren** aus. Vorhandene Konfigurationen
zeigen damit unverändert alle Bänder; gespeicherte Anfangssichtbarkeit wird
erst nach Aktivierung wirksam. Fehlende Angaben gelten als sichtbar.

Bei aktivierter Auswahl schaltet ein Klick auf einen Legendeneintrag dieses
Band um. **Alle** wählt sämtliche Bänder, **Umkehren** kehrt die Auswahl um.
Nach **Alle** und **Umkehren** ist keines gewählt; ein Hinweis unterscheidet
diesen Zustand von echten Nullwerten. Die Legende bleibt bei aktiver
Bandauswahl auch dann sichtbar, wenn sie im Designer ausgeschaltet wurde,
damit sich ausgeblendete Bänder wieder einblenden lassen.

Auswahl und Legendenposition bleiben bei Datenaktualisierung, Umsortierung
und Größenänderung anhand der Quell-IDs erhalten. Gleichnamige Quellen bleiben
getrennt. Die Browserauswahl wird nicht in die Instanz geschrieben; Neuladen
verwendet wieder die konfigurierten Anfangswerte.

Die Auswahl verändert nur die gezeichneten Bänder und deren gemeinsamen
Darstellungsmaßstab. Originalwerte und Einheiten bleiben im Tooltip erhalten.
Alle konfigurierten Quellen werden weiter geladen und gemeinsam auf Lücken
geprüft. Das Ausblenden einer Quelle kann deshalb **keine Archivlücke schließen**.

Zusätzlich gelten 900.000 Bytes für das serialisierte Modell und 1 MiB für
das vollständige HTML-Dokument. Wird diese Grenze erreicht, fordert eine
Fehlermeldung einen kürzeren Zeitraum; es werden keine Werte heimlich gerundet
oder entfernt. Große Startzustände werden verlustfrei komprimiert. Dafür
benötigt der Browser die native gzip-Dekomprimierung (`DecompressionStream`).

## Kachel und IPSView gestalten

Beide Designer bieten Theme, Tooltip, Banddeckkraft (0–100 %, Standard 85 %),
Konturbreite/-farbe, Legende, optionale Bandbeschriftungen, Zeitbeschriftungen,
Teilstriche sowie Schriftgrößen und Farben für Titel, Achse, Bänder und Legende.
Bandbeschriftungen sind zunächst aus; die Legende zeigt die Quellen auch bei
kleinen Flächen. Sie kann geblättert werden; ohne aktivierte Bandauswahl
blendet sie keine Quellen aus.
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
Die Vorschau zeichnet höchstens acht anfangs sichtbare Bänder und nennt bei
einem Ausschnitt die gezeichnete und konfigurierte Anzahl. Bei deaktivierter
Bandauswahl werden Anfangssichtbarkeiten ignoriert. Vollständig ausgeblendet
bedeutet keine erfundenen Beispielbänder; die Legende bleibt verfügbar.

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
