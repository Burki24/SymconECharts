# EChartsBoxplot

Vergleicht die Verteilung archivierter Messwerte in einer nativen Symcon-Kachel
und optional als eigenes IPSView-WebContent-Widget.

## Einrichtung

1. Das vorhandene gemeinsame EChartsGateway auswählen.
2. Unter „Boxplot-Variablen“ 1–10 numerische Standard-Archivvariablen derselben
   Einheit hinzufügen, beispielsweise Raumtemperaturen. Anzeigename und
   Boxfarbe sind optional. Eine Variable darf nur einmal vorkommen;
   unterschiedliche Variablen dürfen denselben Namen haben.
3. Den Zeitraum wählen: letzte 24 Stunden, 7 Tage oder 30 Tage.
4. Optional Titel und Design einstellen und Änderungen übernehmen.

Einheit und Nachkommastellen stammen aus der Variablendarstellung beziehungsweise
einem kompatiblen Profil. Zählervariablen werden nicht unterstützt.

## Bedeutung der Boxen

Jede Box gehört einer Variable. Ihre Stichproben sind die gleich gewichteten,
abgeschlossenen **Stundenmittelwerte** des Archivs im gewählten Zeitraum,
nicht die einzelnen Rohwert-Ereignisse. Die laufende Stunde bleibt unberücksichtigt.
Die Darstellung wird minütlich aktualisiert.

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

## Gestaltung und IPSView

Kachel und IPSView besitzen getrennte Designer mit gekennzeichneten synthetischen
Beispielvorschauen. Einstellbar sind Ausrichtung, ECharts-Theme, Animation,
maximale Boxbreite, Randbreite und -farbe, Boxdeckkraft, Raster, Tooltip,
Achsenbeschriftung sowie Schriftgrößen und Farben für Titel und Achsen.
Die maximale Boxbreite wird auf schmalen Flächen automatisch begrenzt.
Lange Kategorienamen werden bei Platzmangel gekürzt; im Tooltip bleiben sie vollständig.

IPSView übernimmt standardmäßig das Kacheldesign. Für ein unabhängiges Design
die Übernahme ausschalten oder das Kacheldesign ausdrücklich kopieren und
anschließend separat bearbeiten. Die Datenquellen und der Zeitraum bleiben
für beide Ausgaben gemeinsam. Optional kann der IPSView-Hintergrund durchscheinen.

Der Diagrammtitel steht unter dem Symcon-Kachelkopf. Eine Änderung der
Gestaltung verändert weder Variablenwerte noch Archivdaten.
