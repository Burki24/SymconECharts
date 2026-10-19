# EChartsStateTimeline

Zeigt archivierte Zustände mehrerer Geräte als farbige Zeitabschnitte.
Jede Variable erhält eine eigene Zeile. Geeignet ist das beispielsweise
für Fensterkontakte, Pumpenbetrieb oder Betriebsarten eines Geräts.
Das Modul liest nur; es schaltet keine Geräte und verändert keine Archivdaten.

## Einrichtung

1. Eine Instanz `EChartsStateTimeline` anlegen und mit dem vorhandenen
   `EChartsGateway` verbinden. Ein gemeinsames Gateway genügt für alle Diagramme.
2. Unter **Zustandsquellen** ein bis 32 unterschiedliche Boolean- oder
   Integer-Variablen hinzufügen, die in einem Standardarchiv aufgezeichnet werden.
   Float-, String- und Zählerarchive sind nicht unterstützt.
3. Optional pro Quelle eine Beschriftung festlegen. Ohne eigene Beschriftung
   wird der aktuelle Variablenname verwendet. Gleiche Namen sind zulässig.
4. Im Bearbeitungsdialog jeder Quelle unter **Zustandszuordnung** die gewünschten
   Werte mit Namen und Farben anlegen. Bis zu 32 verschiedene Zustände je
   Quelle sind möglich. Boolean verwendet `0` für falsch und `1` für wahr.
   Beispielsweise kann ein Kontakt `0 = Geschlossen` und `1 = Offen` verwenden,
   eine andere Quelle unabhängig davon `0 = Aus` und `1 = Ein`.
5. Titel und Zeitraum wählen. Standardmäßig werden die letzten sieben Tage
   angezeigt; alternativ stehen 24 Stunden, 30 Tage oder **Benutzerdefiniert**
   zur Verfügung. Für eigene Zeiträume sind 1–1000 Minuten, Stunden, Tage oder
   Wochen wählbar, beispielsweise 90 Minuten oder zwei Wochen.

Nicht zugeordnete Zustände bleiben als Zahlen sichtbar. Automatische Farben
folgen dem gewählten Theme. Zustandsnamen und -farben werden nicht automatisch
aus Variablenprofilen übernommen. So ist ihre Bedeutung ausdrücklich festgelegt.
Aktuelle Variablenwerte ersetzen keine Archivzustände.

## Zustände und Zeitabschnitte

Ein protokollierter Zustand gilt bis zum nächsten protokollierten Wechsel
beziehungsweise bis zum Ende des angezeigten Fensters. Wiederholte gleiche
Werte werden zu einem Abschnitt zusammengefasst. Es werden keine Mittelwerte
gebildet und keine Zwischenzustände interpoliert.

Ein vorhandener Archivwert vor dem Zeitfenster bestimmt dessen Anfangszustand.
Fehlt ein solcher Wert, bleibt der Abschnitt bis zum ersten Wert ausdrücklich
**Unbekannt**. Auch eine vollständig leere Quelle bleibt unbekannt statt als
ausgeschaltet zu erscheinen. Bekannte Teile eines unvollständigen Archivs
bleiben sichtbar. Ein Punkt genau am Fensterende erzeugt keinen weiteren
Abschnitt ohne Dauer.

Die Zeiträume umfassen verstrichene Zeit bis zum aktuellen Abfragezeitpunkt;
ein Tag entspricht 24 Stunden, eine Woche sieben Tagen, auch bei einer
Zeitumstellung. Die Beschriftung verwendet die Symcon-Zeitzone.
Aktualisiert wird minütlich. Der Tooltip nennt Quelle, Zustand, Wert, Beginn,
Ende und Dauer des angezeigten Abschnitts. Am Fensterrand abgeschnittene
Abschnitte zeigen entsprechend nur die Dauer innerhalb des Fensters.

Ein Zustandsarchiv kann verlorene Zwischenwerte, ausgeschaltete Aufzeichnung
oder nachträglich gelöschte Messungen nicht zuverlässig als Ausfall erkennen.
Das Diagramm stellt die aufgezeichneten Zustände dar und ist kein Nachweis
einer lückenlosen Geräteüberwachung.

Pro Quelle werden höchstens 2000 Rohwerte innerhalb des Fensters gelesen,
zusammen höchstens 16000 Rohwerte für alle Quellen. Ein vorhandener Vorgänger
vor dem Fenster wird je Quelle zusätzlich berücksichtigt.
Sehr viele Wechsel oder umfangreiche Beschriftungen können zusätzlich die
gemeinsame Ausgabebegrenzung erreichen. Dann erscheint die Aufforderung,
einen kürzeren Zeitraum zu wählen. Werte werden nicht stillschweigend
reduziert oder durch eine geschätzte Fortsetzung ersetzt.

## Kachel und IPSView gestalten

Beide Designer bieten Theme, Zoom, Tooltip, Zustandsbeschriftungen, Raster,
Abschnittshöhe (20–100 %, Standard 65 %), Deckkraft (0–100 %, Standard 90 %)
und Rahmenbreite (0–5 px, Standard 0). Titel, Achsen und Zustandsnamen besitzen
eigene Schriftgrößen (50–200 %) und Farben. Die Farbe unbekannter Abschnitte
ist separat einstellbar. Automatische Textfarben innerhalb der Abschnitte
berücksichtigen den Hintergrundkontrast. Zu schmale Abschnitte lassen ihren
Text aus; ihre Werte bleiben im Tooltip erreichbar.

Unter **Abschnitte und Darstellung** können Rahmen durchgezogen, gestrichelt
oder gepunktet erscheinen. Der Eckenradius reicht von 0 bis 20 px und wird bei
schmalen Abschnitten automatisch begrenzt. Zustandsnamen lassen sich innerhalb
des Abschnitts links, mittig oder rechts ausrichten. Die Breite steht weiterhin
exakt für die Dauer; Rundungen verbreitern kurze Abschnitte nicht.

**Achsen und Raster** bietet getrennte Schalter für Zeit- und Quellenbeschriftung,
Zeitachsenlinie und Teilstriche. Zeitangaben sind automatisch, als Uhrzeit,
Datum oder zweizeilig als Datum und Uhrzeit wählbar. Linienart/-breite (0–5 px),
Beschriftungsabstand (0–30 px) sowie Rasterfarbe, -linienart, -breite und
-deckkraft sind einstellbar. Automatisch erscheinen bei langen Zeiträumen
Datumsangaben statt wiederholter Mitternachtszeiten.

Die Schriftart ist zwischen Arial, Verdana, Georgia und nichtproportionaler
Schrift wählbar. Titel, Achsenbeschriftungen und Zustandsnamen unterstützen
jeweils Fett- und Kursivschrift. Verwendet werden die auf dem Anzeigegerät
verfügbaren Schriften; es werden keine externen Schriftdateien geladen.

Unter **Hervorhebung** lässt sich die Reaktion beim Überfahren eines Abschnitts
abschalten oder auf diesen Abschnitt fokussieren. Beim Fokus bestimmt die
Restdeckkraft (0–100 %, Standard 20 %) die Darstellung der übrigen Abschnitte.
Ohne Fokus bleiben sie unverändert. Der Tooltip kann unabhängig davon aktiv
bleiben. Diese Mausinteraktion ist in der statischen Vorschau nicht darstellbar.

Der Diagrammtitel steht in der Kachel unter dem nativen Kachelnamen und
Vergrößerungsbutton. IPSView reserviert diesen zusätzlichen Kopfbereich nicht.
Zoom vergrößert den Zeitbereich innerhalb des Diagramms, ohne die Konfiguration
zu ändern. Animation einschließlich Dauer und Bewegungskurve ist einstellbar.

Bei mehr als acht Quellen zeigt das Diagramm einen lesbaren Ausschnitt von
höchstens acht Zeilen. Mit der senkrechten **Zeilennavigation rechts** sind
alle Quellen erreichbar. Sie ist unabhängig vom Zeitzoom und bleibt auch bei
ausgeschaltetem Zeitzoom verfügbar. Aktualisierungen und Größenänderungen
behalten die ausgewählte erste Quelle und den gewählten Zeitzoom bei, soweit
die Quelle und der Zeitraum weiterhin vorhanden sind. Alle Quellen bleiben
Teil des Diagramms; auch unbekannte Zeitabschnitte werden nicht weggelassen.

IPSView erbt zunächst das Kacheldesign. Mit deaktivierter Vererbung kann es
unabhängig gestaltet werden; der Kopierknopf übernimmt das Kacheldesign als
Ausgangspunkt. Die gemeinsame Quellen- und Zustandszuordnung bleibt gleich.
Die optional erzeugte Stringvariable `IPSViewStateTimeline` enthält das
WebContent-Dokument für ein IPSView-HTML-Widget und wird nicht bedient.

Die Vorschauen reagieren auf ungespeicherte Titel-, Quellen-, Zustands- und
Designänderungen. Sie verwenden gekennzeichnete Beispieldaten, nicht den
tatsächlichen Archivverlauf. Das Speichern erfolgt erst beim Übernehmen.
Auch eigene Zeiträume ändern die Zeitachse der Vorschauen bereits vor dem
Speichern, ohne auf das Archiv zuzugreifen. Die Rohwert- und Ausgabegrenzen
gelten unabhängig von der gewählten Dauer unverändert; bei sehr vielen
Zustandswechseln muss der Zeitraum verkürzt werden.
Bei mehr als acht Quellen kennzeichnet die statische Vorschau ausdrücklich
die ersten acht von insgesamt bis zu 32 Zeilen. Scrollen und Zeitzoom sind
nur im eigentlichen Diagramm bedienbar.

## Öffentliche Funktionen

- `ECST_GetStateTimelineData(int $InstanzID): string` liefert das aktuelle
  Diagrammmodell mit Zeilen, Zustandsabschnitten, Zeitraum und Kacheldesign als JSON.
- `ECST_GetIPSViewHTML(int $InstanzID): string` liefert das vollständige
  HTML-Dokument der optionalen IPSView-Ausgabe.
- `ECST_RefreshArchive(int $InstanzID): void` fordert die Aktualisierung der
  Diagrammausgaben an. Die Quellen und ihre Archivdaten bleiben unverändert.
