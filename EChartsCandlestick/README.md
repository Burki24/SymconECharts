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
Zusätzlich lässt sich eine **benutzerdefinierte Dauer** von 1–1000 Minuten,
Stunden, Tagen oder Wochen wählen. Sie endet an der letzten abgeschlossenen
Intervallgrenze. Nur ganze Stunden beziehungsweise lokale Tage innerhalb
dieser verstrichenen Dauer werden berücksichtigt; ein kurzer Zeitraum kann
deshalb leer sein. Höchstens 720 Intervalle sind erlaubt. Bei Überschreitung
bitte die Dauer verkürzen oder Tagesintervalle wählen.
Die Zeitzone der Symcon-Installation gilt, einschließlich Sommerzeit.
Stundenbeschriftungen enthalten den UTC-Offset, damit doppelte Stunden beim
Herbstwechsel unterscheidbar bleiben.

Bei benutzerdefinierten Angaben entspricht ein Tag 24 verstrichenen Stunden,
eine Woche sieben solchen Tagen. Die festen Tages-Vorgaben zählen dagegen
weiterhin lokale Kalendertage. Deshalb kann eine benutzerdefinierte Dauer von
einem Tag im Tagesmodus beim Herbstwechsel keinen vollständigen 25-Stunden-Tag
enthalten. Ein leerer Zeitraum löst keine Archivabfrage aus. Kachel und IPSView
verwenden dieselbe Zeitkonfiguration; Designänderungen verändern sie nicht.

„Fester historischer Zeitraum“ ergänzt eine Von-/Bis-Auswahl in der angezeigten
Symcon-Zeitzone. Der Beginn ist eingeschlossen, das Ende ausgeschlossen und darf
nicht in der Zukunft liegen. Nur vollständig enthaltene abgeschlossene Stunden
oder Kalendertage werden berücksichtigt: Von 12.10.2026 08:30 bis 13:30 entstehen
im Stundenmodus die Intervalle 09:00 bis einschließlich 12:59:59. Im Tagesmodus
enthält dieses Fenster keinen vollständigen Tag und bleibt leer.

Die Grenzen bleiben bei Aktualisierungen fest. Fehlende oder ungültige Daten,
nicht existierende Uhrzeiten und doppeldeutige Uhrzeiten beim Herbstwechsel
werden abgewiesen; bitte eindeutige Grenzen vor oder nach dem Wechsel wählen.
Ein Fenster über den Wechsel ist möglich und enthält die tatsächlichen Stunden
beziehungsweise lokale 23-/25-Stunden-Tage. Historische Stundenfenster über
Zeitzonenwechsel mit nicht ganzstündiger Verschiebung werden nicht unterstützt.
Auch im festen Zeitraum gelten höchstens 720 Intervalle und dieselben
Rohwertbudgets. Die Vorschauen zeigen die gewählten Grenzen mit Beispieldaten;
bestehende Zeiträume und Designs bleiben unverändert nutzbar.

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

Zusätzlich können steigende und fallende Kerzen getrennte Rahmenfarben und
hohle Körper erhalten. Eine spezielle Rahmenfarbe hat Vorrang vor der
gemeinsamen Rahmenfarbe. Die Neutralfarbe gilt weiterhin für Kerzen mit
gleichem Anfangs- und Endwert.

„Feste Kerzenbreite“ legt die Breite innerhalb der eingestellten Minimal- und
Maximalbreite fest; 0 verwendet die automatische Breite. In kleinen oder
sehr dichten Diagrammen wird auch eine feste Breite passend verkleinert.
Hover-Rahmenbreite, -farbe und -deckkraft lassen sich eigens einstellen;
bei Deckkraft -1 bleibt das normale Verhalten bestehen. Ohne aktivierte
Hover-Hervorhebung werden diese Hover-Einstellungen nicht angewendet.

Die Zeitachse kann umgekehrt und ihre Linienfarbe/-art angepasst werden.
Die Werteachse kann den Nullpunkt einschließen, ohne Messwerte zu ändern.

„Zeitzoom aktivieren“ ergänzt einen Zeitbereichsregler und Zoom per Mausrad
über der Zeichenfläche. Im Diagramm kann der Ausschnitt außerdem gezogen
und auf Touchgeräten mit zwei Fingern gezoomt werden. Bei horizontalen Kerzen
steht der Regler rechts, sonst unten. Beide Designer können die Option
unabhängig verwenden; standardmäßig ist sie ausgeschaltet.

Der Zoom ist ausschließlich eine flüchtige Ansicht der bereits geladenen
Intervalle. Die vollständigen Kerzen bestimmen weiterhin die Werteskala;
Archivabfragen, Datenlücken, Tooltips und Ladebudgets ändern sich nicht.
Auch die Kerzenbreiten passen sich an den sichtbaren Ausschnitt an.
Aktualisierungen, Größen- und Themewechsel erhalten den prozentualen
Ausschnitt bei derselben Quelle und überlappenden Zeitfenstern. Eine andere
Quelle, Intervallart, Zeitraumvorgabe oder feste Von-/Bis-Auswahl setzt ihn
zurück; ebenso ein leeres Fenster oder das Ausschalten der Option.
Neuladen der Seite startet mit dem vollständigen Zeitraum. Die statische
Vorschau zeigt den Regler schematisch; interaktiv ist er im echten Diagramm.

Abstände der Achsenbeschriftungen, lokale Schriftart sowie Kursivschrift für
Titel und Achsen sind in beiden Designern getrennt wählbar. Es werden keine
Schriftdateien aus dem Internet geladen. Interaktive Hover-Effekte werden
in der echten Kachel beziehungsweise in IPSView geprüft, nicht im statischen SVG.

IPSView erzeugt bei aktivierter Ausgabe eine WebContent-Variable
„Candlestick für IPSView“. Der Diagrammtitel erscheint in der Kachel
unterhalb des Kachelnamens und Vergrößerungsbuttons.
