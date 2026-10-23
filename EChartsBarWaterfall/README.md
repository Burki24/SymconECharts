# EChartsBarWaterfall

## Beschriftungsoptionen

Die automatische Wertposition folgt weiter dem Vorzeichen des Schritts. Alternativ lässt sich die Position oberhalb, unterhalb, links, rechts oder innerhalb des Balkens festlegen. Ein eigener Abstand von 0 bis 100 px ist möglich.

Die Optionen gelten für Kacheln und – bei unabhängigem Design – auch für
IPSView. Bei geerbtem Design folgt IPSView den Kacheleinstellungen.

`EChartsBarWaterfall` zeigt, wie sich ein aktueller Wert durch mehrere
Änderungen zu einer Endsumme entwickelt. Das Modul benötigt zwei bis 100
eindeutige numerische Symcon-Variablen mit derselben effektiven Einheit.
Die erste Quelle ist der Startwert; bis zu 99 weitere Quellen sind standardmäßig Änderungen.
Die berechnete Endsumme ist keine zusätzliche Quellvariable.

## Optionale Zwischensummen und Neustarts

In jeder Quellenzeile stehen zwei zusätzliche, standardmäßig ausgeschaltete
Auswahlen zur Verfügung. Bestehende Listen funktionieren ohne Änderung weiter.

- **„Berechnete Zwischensumme danach“** ergänzt direkt nach dieser Quelle eine
  eigene Säule mit dem laufenden Stand. Sie beginnt an der Nulllinie und ist
  als „Zwischensumme“ gekennzeichnet. Sie benötigt keine weitere Variable und
  wird **nicht erneut addiert**. Die folgenden Änderungen laufen normal weiter.
- **„Neustart mit diesem absoluten Wert“** beginnt mit dem aktuellen Wert
  dieser Quelle einen neuen, unabhängigen Bilanzabschnitt. Er wird nicht zum
  bisherigen Stand addiert. Die erste Quellenzeile ist bereits der Anfangsstart;
  dort ist diese Auswahl nicht zulässig. Auch negative oder null Startwerte
  sind möglich. Weitere Zeilen verändern den neuen Abschnitt, bis erneut ein
  Neustart gewählt wird.

Beispiel: Start `100`, Änderung `−20`, Zwischensumme `80`, Neustart `−5`,
Änderung `+2` ergibt als letzte Abschnittssumme `−3` – **nicht** `77` oder
eine Summe der beiden Abschnitte. Für eine eigene Abschlusssäule vor dem
Neustart aktivieren Sie die Zwischensumme bei der vorhergehenden Quelle.
Eine Zwischensumme direkt nach einem Neustart zeigt dessen neuen Startwert.

Bei Neustarts wird die letzte Säule als **„Letzter Abschnitt: …“** beschriftet;
Ihre eingetragene Endsummentext-Beschriftung bleibt dabei erhalten. Ein
dauerhafter Hinweis erklärt, dass die Endsumme zum letzten Abschnitt gehört.
Er bleibt auch ohne angezeigte Werte sichtbar. Der Tooltip nennt beim
Neustart den vorherigen Abschlusswert und den neuen Bilanzabschnitt; bei
Zwischensummen weist er auf die reine Berechnung hin. Gemessene Variablenwerte,
ihre Zeitstempel und Einheiten werden nicht verändert. Berechnete Zwischensummen
erhalten keinen erfundenen Messzeitstempel und erzeugen keine Symcon-Variablen.

Start- und Neustartsäulen verwenden die vorhandene Startfarbe; Zwischensummen
die vorhandene Summenfarbe. Dafür müssen Sie keine Designs neu einrichten.
Kachel, IPSView und beide Vorschauen verwenden dieselbe vollständige Rechnung.

## Große Bilanzen und Kategoriennavigation

Bei Platzmangel zeigt das Diagramm einen verschiebbaren Ausschnitt mit einer
horizontalen Kategoriennavigation. Sichtbare Kategorien passen sich an die
Kachel-/Widgetbreite, Balkenbreite, Schriftgröße und Wertbeschriftung an.
Schmalere Balken können mehr Schritte sichtbar machen, solange Beschriftungen
lesbar bleiben. Der Ausschnitt lässt sich am Regler und innerhalb des
Diagrammbereichs verschieben.

Navigation ändert nur die Ansicht: Sämtliche Quellen werden weiter gelesen.
Jeder Zwischenstand berücksichtigt seinen vollständigen Bilanzabschnitt, auch
außerhalb des Ausschnitts. Die Werteskala berücksichtigt alle Abschnitte.
Ohne Neustarts bleibt die Endsumme die Bilanz der gesamten Liste; mit Neustarts
ist sie der Abschluss des letzten Abschnitts. Die Endsumme
steht als letzte Kategorie zur Verfügung. Tooltips nennen den Originalschritt
mit Änderung sowie Stand davor und danach. Gleichnamige Schritte bleiben
getrennt; der sichtbare Bereich folgt bei Aktualisierung und Umsortierung
seinen Quell-IDs. Bei Größenänderung wird der Ausschnitt passend begrenzt.

Bestehende Quellenlisten und Designeinstellungen bleiben nutzbar. Es gibt
keine automatische Sortierung, Normalisierung oder ungefragte Abschnittsbildung.
Es bleiben maximal 100 echte Quellvariablen erlaubt. Bis zu 100 ausdrücklich
gewählte Zwischensummen ergeben einschließlich Endsumme höchstens 201 Säulen,
ohne zusätzliche Datenabfragen. Auch frühere Abschnitte werden vollständig gelesen
und geprüft; ein Neustart verdeckt keine fehlerhafte Quelle oder Summenüberläufe.
Sehr große Beschriftungen oder Einheiten können die Ausgabegrenze erreichen:
Das Modell ist auf 200.000 JSON-Bytes und das HTML-Dokument auf 1 MiB begrenzt.
Dann erscheint eine Fehlermeldung mit der Bitte, Beschriftungen oder Einheiten
zu verkürzen; die Bilanz wird nicht stillschweigend abgeschnitten.

## Vorübergehende Ladefehler

Können Werte kurzzeitig nicht geladen werden, bleibt die letzte gültige Grafik
in Kachel und IPSView sichtbar. Bei wiederholtem Fehler erscheint ein Hinweis;
das Modul versucht nach 60 Sekunden erneut zu laden. Ohne zuvor geladene Daten
oder bei ungültiger Konfiguration erscheint weiterhin eine Fehlermeldung.

Die beiden Designer fassen Beschriftung und Geometrie, Schrittfarben sowie
Schrift und Textfarben in einklappbaren Bereichen zusammen. Die Live-Vorschau
steht darunter.

## Live-Vorschau

Kachel- und IPSView-Designer zeigen jeweils eine SVG-Vorschau. Noch nicht
gespeicherte Änderungen an Titel, Quellen, Endsummentext, Theme und Farben
werden übernommen. Der erste Wert ist der Startwert, weitere Werte sind
vorzeichenbehaftete Änderungen oder ausdrücklich gewählte Neustartwerte;
Zwischen- und Endsummen werden berechnet. Wertbeschriftungen
stehen bei fallenden Balken und negativen Endsummen unter der Säule. Ohne gültige
Quellen sind Beispieldaten ausdrücklich markiert. Bei aktiviertem
**Kacheldesign verwenden** folgt die IPSView-Vorschau dem Kacheldesign.
Wertbeschriftungen verwenden die wirksame gemeinsame Einheit und die höchste
Nachkommastellenzahl der Quellen. Die responsive Laufzeitdarstellung prüfen
Sie anschließend in Kachel und Widget.
Die Vorschau zeigt bis zu fünf Schritte (einschließlich gewählter Zwischensummen),
bei Bedarf ein Auslassungszeichen und die korrekt berechnete Endsumme.
Ohne neue Auswahlen sind dies wie bisher die ersten fünf Quellen.
Bei Neustarts verwendet auch die Vorschau den letzten vollständigen Abschnitt.
Sie nennt
die angezeigte und gesamte Quellenzahl. Auch die Vorschau-Werteskala berücksichtigt
alle Zwischenstände, nicht nur den Ausschnitt. Die eigentliche Grafik nutzt alle
Quellen; in der Vorschau ausgelassene Änderungen werden nicht als Null behandelt.

## Animation

Für Start und Aktualisierung lassen sich außerdem die **Bewegungskurve** und
eine feste **Verzögerung** (0–3000 ms) getrennt wählen. Ohne Änderung gilt
jeweils cubicInOut ohne Verzögerung. Die Verzögerung betrifft das ganze Diagramm,
nicht jeden Datenpunkt einzeln.

Im Kachel- und unabhängigen IPSView-Designer steuern **Animation**, **Startanimation** und **Aktualisierungsanimation** die ECharts-Übergänge. Beide Zeiten sind von 0 bis 3000 ms einstellbar; 0 ms unterdrückt den jeweiligen Übergang. Die Systemeinstellung für reduzierte Bewegung schaltet Animationen immer ab. Bei **Kacheldesign verwenden** übernimmt IPSView die Kachelwerte.

## Einrichtung

Ein gemeinsames `EChartsGateway` genügt für alle Diagramme. Wählen Sie bei
weiteren Diagrammen das vorhandene Gateway. In **Waterfall-Quellen** ist die
erste Zeile die Variable für den Startwert. Jede folgende Zeile ist eine
Änderung mit Vorzeichen: positive Werte erhöhen, negative Werte vermindern
die laufende Summe. Die Reihenfolge lässt sich in der Liste verschieben. Die
letzte Säule wird berechnet und benötigt keine eigene Variable.
Eine leere Beschriftung der Endsumme verwendet „Gesamt“.

Beispiel: Start `100`, Änderungen `−20` und `+5` ergeben `85`. Auch negative
Zwischenwerte und ein Überqueren der Nulllinie werden angezeigt. Alle Werte
kommen aus den aktuellen Variablenwerten; ein Archiv ist nicht erforderlich.

Die optionale Schrittbeschriftung hat Vorrang. Ist sie leer, erscheint der
aktuelle Symcon-Name der Variable. Variablen-IDs dienen nur der internen
Zuordnung und erscheinen nicht im Diagramm. Einheit und Nachkommastellen
können aus der Variablendarstellung übernommen oder je Quelle gesetzt werden.
Die effektive Einheit muss bei allen Quellen gleich sein.

## Darstellung

- Native Symcon-Kachel mit Aktualisierung bei Variablenänderungen;
- optionales eigenständiges WebContent-Widget für IPSView;
- Kachel- und unabhängiges IPSView-Design mit Theme, Raster, Wertanzeige,
  Balkenbreite und separaten Farben für Start, Zunahme, Abnahme und Endsumme;
- Titel-, Achsen- und Werteschriftgröße von 50 bis 200 % sowie eigene Farben
  für Titel, Achsen, Werte und Raster; „Automatisch“ folgt weiterhin dem Theme;
- einstellbare Hintergrundfarbe und Deckkraft für IPSView.

In **IPSView-Design** ist **Kacheldesign verwenden** zunächst aktiv. Die
unabhängigen Designfelder und **Kacheldesign nach IPSView kopieren und
unabhängig bearbeiten** sind dabei deaktiviert. Deaktivieren Sie den Schalter,
um eigene IPSView-Farben und Darstellungsoptionen zu wählen. Der Kopierknopf
übernimmt das aktuell gespeicherte Kacheldesign einmalig und ersetzt dabei
bereits gespeicherte unabhängige IPSView-Einstellungen. Quellen und aktuelle
Werte bleiben in beiden Ausgaben gleich.

Die öffentliche Funktion `ECBW_GetWaterfallData($InstanzID)` liefert das
aktuelle Chart-Modell als JSON. Sie liest nur Werte und verändert keine
Quellvariablen.
