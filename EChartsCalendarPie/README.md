# EChartsCalendarPie

Zeigt einen Monatskalender mit einem Kreisdiagramm je abgeschlossenem Tag.
Die Anteile können Tagesmittelwerte, Tagesverbrauch oder Zustandsdauern sein.
Das Modul liest ausschließlich: Quellen, Archivaufzeichnung und Archivwerte
werden weder verändert noch automatisch ergänzt.

## Einrichtung

1. Eine Instanz `EChartsCalendarPie` anlegen und mit dem vorhandenen
   `EChartsGateway` verbinden. Ein gemeinsames Gateway genügt für alle Diagramme.
2. Unter **Datengrundlage** einen der drei Modi wählen und die dazugehörigen
   Quellen konfigurieren. Beim Wechsel bleiben die Einstellungen der anderen
   Modi gespeichert; abgefragt werden nur die aktiven Quellen.
3. **Monat** leer lassen, um dem aktuellen Monat zu folgen, oder einen Monat
   im Format `JJJJ-MM` eintragen. Erlaubt sind Januar 1971 bis zum aktuellen
   Monat. Titel und gewünschtes Design einstellen.

Die Pfeile laden frühere Monate beziehungsweise kehren zum ausgewählten
Monat zurück. Bis zu 120 Monate vor der konfigurierten Auswahl sind erreichbar.
Diese Navigation verändert die gespeicherte Konfiguration nicht. Weiter
zurückliegende Monate können direkt in der Konfiguration ausgewählt werden.

## Datengrundlagen

### Tagesmittelwerte

Eine bis fünf numerische Integer-/Float-Variablen aus Standardarchiven wählen.
Die Anzahl wird über Hinzufügen und Entfernen in der Quellenliste bestimmt;
es sind keine drei festen Quellen vorgegeben. Die Vorschau folgt der Auswahl.
Jede Quelle bildet einen Kreisanteil mit optionalem Namen und eigener Farbe.
Ohne eigenen Namen folgt die Beschriftung dem aktuellen Variablennamen.
Gleichnamige Quellen bleiben getrennt erhalten.

Verwendet werden die unveränderten Tagesmittelwerte des Symcon-Archivs,
nicht neu berechnete Mittelwerte von Stundenmitteln. Ohne Normalisierung müssen
alle Quellen dieselbe wirksame Einheit besitzen; Einheit und Nachkommastellen können aus
der Variablendarstellung übernommen oder gemeinsam vorgegeben werden.
Für Anteile sind nur nichtnegative Werte geeignet. Beispielsweise lassen sich
die durchschnittlichen Leistungen mehrerer Verbraucher vergleichen; daraus
wird keine Energieverbrauchsmessung abgeleitet.

### Tagesverbrauch

Hier werden ebenfalls eine bis fünf numerische Quellen (ohne Normalisierung derselben Einheit)
gewählt, jedoch ausschließlich mit **Zähleraggregation** im Symcon-Archiv.
Die Kreisanteile zeigen dessen Tagesverbrauch: die vom Archiv berechnete Summe
positiver Zählerdifferenzen. Kumulative Zählerstände werden nicht addiert.
Zählerrücksetzungen behandelt das Archiv nach seiner eigenen Zählerlogik;
das Modul schätzt keine fehlenden Verbräuche. Ein Standardarchiv wird nicht
automatisch in einen Zähler umgedeutet.

### Zustandsdauer

Eine archivierte Boolean- oder Integer-Variable mit Standardaggregation wählen.
Unter **Zustandszuordnung** können bis zu 32 verschiedene Werte mit Namen und
Farben hinterlegt werden. Boolean verwendet `0` für falsch und `1` für wahr.
Nicht zugeordnete Werte bleiben als Zahlen sichtbar.

Jeder protokollierte Zustand gilt bis zum nächsten Wechsel. Ein vorhandener
Archivwert vor Monatsbeginn bestimmt den Anfangszustand. Fehlt er, bleibt die
Zeit bis zum ersten Wert **Unbekannt**. Diese Zeit erhält einen eigenen Anteil
und wird nicht aus der Prozentberechnung entfernt. Der Tooltip zeigt Stunden
und Anteile an der tatsächlichen Tageslänge; Sommerzeitwechsel ergeben deshalb
23 oder 25 statt 24 Stunden. Aktuelle Variablenwerte ersetzen keine Archivwerte.
Historisch vollständig übersprungene lokale Datumswerte, etwa bei einem
Wechsel der Datumsgrenze, erhalten keinen erfundenen Zeitanteil.

Pro Monat werden höchstens 2000 Rohwechsel gelesen. Zu viele Wechsel oder
ungültige Archivantworten erzeugen eine sichtbare Meldung statt einer
ausgedünnten oder geschätzten Darstellung. Ein Zustandsarchiv kann verlorene
Zwischenwerte oder deaktivierte Aufzeichnung nicht zuverlässig als Ausfall
erkennen: Ein bekannter Zustand wird bis zum nächsten protokollierten Wechsel
fortgeführt.

### Optionale Normalisierung für unterschiedliche Messgrößen

Für **Tagesmittelwerte** und **Tagesverbrauch** kann **Numerische Quellen anhand
ihres Min/Max normalisieren** aktiviert werden. Dann dürfen die ein bis fünf
Quellen unterschiedliche Einheiten haben. Je Quelle werden **Min** und **Max**
festgelegt; ohne Eintrag gelten 0 und 100. Max muss größer als Min sein.
Die Grenzen gelten für alle angezeigten Tage und Monate, nicht nur für die
aktuell beobachteten Messwerte.

Das Kreisgewicht ist `100 × (Wert − Min) / (Max − Min)`, begrenzt auf 0–100.
Beispiel: 20 °C im Bereich 0–40 °C ergeben Gewicht 50; 60 % Feuchte im Bereich
0–100 % ergeben Gewicht 60. Die Kreisflächen betragen damit rund 45,5 % und
54,5 %. Das sind relative Vergleichsgewichte, keine Anteile einer gemeinsamen
physikalischen Gesamtmenge.

Negative Temperaturen sind bei aktiver Normalisierung erlaubt. Werte außerhalb
der Grenzen werden nur für die Kreisfläche begrenzt. Der Tooltip bewahrt den
Original-Tageswert mit seiner eigenen Einheit und Nachkommastellenzahl; zusätzlich
zeigt er normalisiertes Gewicht, Bereich und gegebenenfalls Kreisanteil.
Auch optionale Segmentzahlen zeigen die Originalwerte, nicht die Gewichte.
Einheiten und Nachkommastellen werden je Quelle aus der Symcon-Darstellung
beziehungsweise dem Legacy-Profil gelesen. Ohne solche Angaben gelten keine
Einheit und eine Nachkommastelle. Die globalen Formatierungsfelder sind während
der Normalisierung inaktiv; ihre gespeicherten Einstellungen bleiben erhalten.

Fehlende Quellen bleiben fehlend, und ein vollständig auf null begrenzter Tag
erhält keinen künstlichen Kreis. Ohne Normalisierung bleiben unveränderte,
nichtnegative Werte derselben Einheit erforderlich. Der Modus **Zustandsdauer**
ignoriert die Normalisierung und die numerischen Min/Max-Eingaben.

## Fehlende Daten und Kalendergrenzen

Es werden nur abgeschlossene lokale Tage in der Symcon-Zeitzone ausgewertet.
Der laufende Tag und zukünftige Tage bleiben als nicht abgeschlossen markiert.
Ein vollständiges Monatsarchiv ist nicht erforderlich.

Bei Mittelwerten und Verbrauch wird ein Kreis nur gezeichnet, wenn alle
konfigurierten Quellen für diesen Tag vorliegen. Eine Raute markiert einen
unvollständigen Tag; verfügbare Werte und fehlende Quellen stehen im Tooltip.
Ein Quadrat kennzeichnet vollständig fehlende Daten, ein leerer Kreis einen
vollständigen Tag mit ausschließlich echten Nullwerten. Es werden keine
gleich großen Ersatzsegmente erzeugt. Nicht abgeschlossene Tage tragen ein
abgeschwächtes Dreieck.

Ein vorhandenes Tagesaggregat kann sich auch auf einen erst im Tagesverlauf
begonnenen Aufzeichnungszeitraum beziehen. Es wird unverändert übernommen;
das Vorhandensein aller Quellen beweist keine lückenlose Tagesaufzeichnung.
Im Zustandsmodus erscheinen fehlende oder teilweise bekannte Tage als Kreise
mit ausdrücklich unbekanntem Zeitanteil.

## Kachel, IPSView und Vorschau

Beide Designer bieten Theme, Tooltip, Legende, Wochenbeginn und Tagesnummern.
Kreisradius (20–90 %), Donut-Innenradius (0–80 % des Außenradius), Startwinkel,
Drehrichtung, Segment- und Zellrahmen sowie Zelldeckkraft sind einstellbar.
Titel, Kalenderbeschriftung und Werte besitzen eigene Schriftgrößen und Farben.
Kategorienfarben stehen bei den Quellen beziehungsweise Zustandszuordnungen;
unbekannte Zustandszeit besitzt eine eigene Designfarbe.

Der Diagrammtitel hält Abstand zum nativen Kachelnamen und Vergrößerungsbutton.
Bei kleinen Zellen entfallen Tagesnummern oder Segmentwerte zugunsten einer
überlappungsfreien Darstellung. Werte bleiben im Tooltip erreichbar. Die
Segmentbeschriftung zeigt nur die Zahl; die Einheit steht im Tooltip. Texte,
die nicht vollständig in ihren Kreis beziehungsweise Ring passen, entfallen.
Die Legende ist bei vielen Kategorien blätterbar; ihre Reihenfolge und Farben
bleiben im gesamten Monat gleich. Animation einschließlich Dauer und
Bewegungskurve ist einstellbar.

IPSView erbt zunächst das Kacheldesign. Nach Deaktivieren der Vererbung lässt
es sich unabhängig gestalten; ein Kopierknopf übernimmt das gespeicherte
Kacheldesign als Ausgangspunkt. Die optionale Stringvariable
`IPSViewCalendarPie` enthält das WebContent-Dokument für ein IPSView-HTML-Widget
und wird nicht bedient. Die Daten werden minütlich aktualisiert.

Beide Formularvorschauen reagieren auf ungespeicherte Änderungen, einschließlich
Datengrundlage und Zustandszuordnung. Sie verwenden gekennzeichnete synthetische
Beispiele, keine tatsächlichen Archivwerte. Mausinteraktion und Animation
sind in der statischen Vorschau nicht enthalten. Gespeichert wird erst beim
Übernehmen der Konfiguration.

## Öffentliche Funktionen

- `ECCP_GetCalendarPieData(int $InstanzID): string` liefert das aktuelle
  Monatsmodell mit Tagesanteilen, Fehlstellen, Datengrundlage und Kacheldesign.
- `ECCP_GetCalendarPieDiagnostic(int $InstanzID): string` liefert die
  Konfigurationsdiagnose als JSON.
- `ECCP_GetIPSViewHTML(int $InstanzID): string` liefert das vollständige
  HTML-Dokument der optionalen IPSView-Ausgabe.
- `ECCP_RefreshArchive(int $InstanzID): void` aktualisiert die Ausgaben,
  ohne Quellen oder Archivdaten zu verändern.
