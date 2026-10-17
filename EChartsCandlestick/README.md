# EChartsCandlestick

Candlestick zeigt die Entwicklung einer numerischen Standard-Archivvariable
als Kerzen in einer Symcon-Kachel und optional als IPSView-HTML-Widget.
Es verwendet das gemeinsame EChartsGateway.

## Einrichtung

1. Instanz anlegen und das vorhandene EChartsGateway auswählen.
2. Eine archivierte Integer- oder Float-Variable auswählen, etwa Temperatur.
3. Titel, Zeitraum und Stunden- oder Tagesintervall festlegen.
4. Kacheldesign einstellen; für IPSView die Ausgabe einschalten und bei
   Bedarf ein unabhängiges Design verwenden.

Jede Kerze enthält den ersten und letzten tatsächlich protokollierten
Messwert sowie Minimum und Maximum des Intervalls. Der Körper verbindet
ersten und letzten Wert, die dünne Linie zeigt den gesamten Wertebereich.
Standardmäßig bedeutet Grün einen Anstieg, Rot einen Abfall und die
neutrale Farbe einen gleichen Anfangs- und Endwert. Das ist keine
Gut-/Schlechtbewertung der Messwerte.

## Zeit und fehlende Werte

Es werden ausschließlich abgeschlossene Intervalle angezeigt. Zur Auswahl
stehen 24 Stunden, 7 oder 30 Tage. Im Stundenmodus sind das 24, 168 oder 720
reale Stunden; im Tagesmodus 1, 7 oder 30 lokale Kalendertage.
Die Zeitzone der Symcon-Installation gilt, einschließlich Sommerzeit.
Stundenbeschriftungen enthalten den UTC-Offset, damit doppelte Stunden beim
Herbstwechsel unterscheidbar bleiben.

Die Werte sind keine Stunden-/Tagesmittelwerte und keine geschätzten Werte
an den Intervallgrenzen. Symcon protokolliert Änderungen: Ein konstanter Wert
ohne neues Archivereignis erzeugt keine neue Kerze. Fehlende Intervalle
bleiben leer; ein einzelner protokollierter Wert ergibt eine flache Kerze.
Ein nur teilweise vorhandenes Archiv wird mit seinen vorhandenen Werten
angezeigt. Zählerarchive werden nicht unterstützt.

Die Rohdatenabfrage ist auf 63 Teilabfragen und 64000 Messwerte pro
Aktualisierung begrenzt. Zu dichte Archive erzeugen eine sichtbare Meldung;
dann bitte einen kürzeren Zeitraum wählen. Es werden keine abgeschnittenen
Teilergebnisse als vollständige Kerzen dargestellt. Die Ausgabe wird
minütlich aktualisiert; Kachel und IPSView teilen einen kurzen Datencache.

## Gestaltung

Beide Designer bieten Ausrichtung, Theme, Kerzenbreite und Deckkraft,
Anstiegs-, Abfalls- und Neutralfarbe, Rahmen, Schatten und Hervorhebung.
Schriftgrößen, Textfarben, Fettschrift, Achsenbeschriftung, Drehung und
Raster lassen sich unabhängig anpassen. Die Live-Vorschauen verwenden
gekennzeichnete Beispieldaten und verändern weder Einstellungen noch Archivwerte.

IPSView erzeugt bei aktivierter Ausgabe eine WebContent-Variable
„Candlestick für IPSView“. Der Diagrammtitel erscheint in der Kachel
unterhalb des Kachelnamens und Vergrößerungsbuttons.
