# EChartsBoxplot

Vergleicht die Verteilung archivierter Messwerte in einer nativen Symcon-Kachel
und optional als eigenes IPSView-WebContent-Widget.

## Einrichtung

1. Das vorhandene gemeinsame EChartsGateway auswählen.
2. Unter „Boxplot-Variablen“ 1–32 numerische Standard-Archivvariablen derselben
   Einheit hinzufügen, beispielsweise Raumtemperaturen. Anzeigename und
   Boxfarbe sind optional. Eine Variable darf nur einmal vorkommen;
   unterschiedliche Variablen dürfen denselben Namen haben.
3. Den Zeitraum wählen: letzte 24 Stunden, 7 Tage oder 30 Tage. Alternativ
   „Benutzerdefiniert“ wählen und 1–1000 Minuten, Stunden, Tage oder Wochen angeben.
4. Optional Titel und Design einstellen und Änderungen übernehmen.

Einheit und Nachkommastellen stammen aus der Variablendarstellung beziehungsweise
einem kompatiblen Profil. Zählervariablen werden nicht unterstützt.

## Bedeutung der Boxen

Jede Box gehört einer Variable. Ihre Stichproben sind die gleich gewichteten,
abgeschlossenen **Stundenmittelwerte** des Archivs im gewählten Zeitraum,
nicht die einzelnen Rohwert-Ereignisse. Die laufende Stunde bleibt unberücksichtigt.
Die Darstellung wird minütlich aktualisiert.

Benutzerdefinierte Zeiträume sind rollende Zeitfenster bis zum Ende der letzten
abgeschlossenen Stunde, keine frei gewählten Anfangs-/Enddaten. Auch bei einer
Angabe in Minuten bleiben die Stichproben Stundenmittelwerte. Beispielsweise
reichen 90 Minuten bei letzter abgeschlossener Stunde um 12:00 Uhr von 10:30 bis
11:59:59 Uhr; verwendet wird darin nur der Stundenwert ab 11:00 Uhr. Sehr kurze
Zeiträume können deshalb ohne Stichproben bleiben. Ein Tag entspricht 24 Stunden,
eine Woche sieben solchen Tagen, auch bei einer Zeitumstellung.

Die Box reicht vom ersten zum dritten Quartil; die Linie darin ist der Median.
Die beiden Enden zeigen Minimum und Maximum der Stundenmittelwerte. Quartile
werden linear interpoliert (R7). Es handelt sich nicht um 1,5-IQR-Grenzen;
Ausreißer werden nicht gesondert klassifiziert. Kurzzeitige Rohwertspitzen sind
in dieser Verteilung der Stundenmittelwerte nicht als solche sichtbar.

Fehlende Stunden bleiben fehlend und werden weder interpoliert noch durch Null
ersetzt. Die Quellen müssen keine gemeinsamen Stunden besitzen. Eine einzelne
Stichprobe ergibt eine zusammengefallene Box, eine leere Quelle bleibt ohne
Box. Sind alle Archive leer, erscheint ein Hinweis. Der Tooltip zeigt die fünf
Kennzahlen, Stichprobenanzahl sowie ersten und letzten erfassten Zeitpunkt.
Teilarchive lassen sich damit erkennen; sie sind nicht automatisch vollständig.

Pro Aktualisierung werden höchstens 10000 Stundenmittelwerte insgesamt und
1000 je Quelle geladen. 32 vollständige Quellen über sieben Tage passen in
dieses Budget. Bei vielen dicht aufgezeichneten 30-Tage-Archiven kann ein
kürzerer Zeitraum erforderlich sein; das Diagramm meldet dies ausdrücklich,
statt Quartile aus abgeschnittenen Daten zu berechnen.
Dieselbe Begrenzung gilt auch bei benutzerdefinierten langen Zeiträumen;
eine größere Zeiteinheit erhöht das Archivbudget nicht.

## Gestaltung und IPSView

Kachel und IPSView besitzen getrennte Designer mit gekennzeichneten synthetischen
Beispielvorschauen. Einstellbar sind Ausrichtung, ECharts-Theme, Animation,
maximale Boxbreite, Randbreite und -farbe, Boxdeckkraft, Raster, Tooltip,
Achsenbeschriftung sowie Schriftgrößen und Farben für Titel und Achsen.
Die maximale Boxbreite wird auf schmalen Flächen automatisch begrenzt.
Lange Kategorienamen werden bei Platzmangel gekürzt; im Tooltip bleiben sie vollständig.

Bei vielen Kategorien erscheint automatisch eine Navigation per Schieberegler
oder Mausrad. Das gilt horizontal und vertikal. Boxbreite und Schriftgröße
bestimmen, wie viele Kategorien gleichzeitig lesbar sind. Der Ausschnitt bleibt
bei Aktualisierungen und Größenänderungen an derselben Quelle verankert.
Die Werteskala berücksichtigt weiterhin alle Quellen, nicht nur die sichtbaren.
Leere Archive behalten ihren Kategorieplatz, ohne eine Null-Box zu erzeugen.

Der Designer bietet außerdem:

- minimale und maximale Boxbreite sowie durchgezogene, gestrichelte oder
  gepunktete Konturen für Boxen, Median und Enden;
- Schatten mit Farbe, Unschärfe und X-/Y-Versatz;
- abschaltbare Hover-Hervorhebung und die Deckkraft der übrigen Boxen;
- Kategorien-Teilstriche, Kategorien-Achsenbreite, Drehung und maximale
  Textbreite der Kategoriennamen (0 = automatisch);
- gewünschte Unterteilungsanzahl der Werteachse sowie Rasterlinienart,
  -breite, -farbe und -deckkraft; ECharts bestimmt die tatsächlichen
  Skalenabstände anhand der Werte;
- Fettschrift für Titel und Achsenbeschriftungen.

Die minimale Boxbreite darf nicht größer als die maximale sein. Beide Breiten
und die Textbreite werden bei Platzmangel responsiv begrenzt. Die Vorschau
zeigt statische Gestaltung mit Beispieldaten; Hover-Effekte sind erst im
Diagramm sichtbar. Die Vorschau zeigt je nach Schriftgröße und Ausrichtung
höchstens zehn Quellen und kennzeichnet die gezeigte Anzahl gegenüber der
Gesamtzahl. Datenfenster und Statistik werden durch diese Optionen
nicht verändert.

IPSView übernimmt standardmäßig das Kacheldesign. Für ein unabhängiges Design
die Übernahme ausschalten oder das Kacheldesign ausdrücklich kopieren und
anschließend separat bearbeiten. Die Datenquellen und der Zeitraum bleiben
für beide Ausgaben gemeinsam. Optional kann der IPSView-Hintergrund durchscheinen.

Der Diagrammtitel steht unter dem Symcon-Kachelkopf. Eine Änderung der
Gestaltung verändert weder Variablenwerte noch Archivdaten.
