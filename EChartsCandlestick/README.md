# EChartsCandlestick

Candlestick zeigt die Entwicklung einer numerischen Standard-Archivvariable
als Kerzen in einer Symcon-Kachel und optional als IPSView-HTML-Widget.
Es verwendet das gemeinsame EChartsGateway.

## Einrichtung

1. Instanz anlegen und das vorhandene EChartsGateway auswählen.
2. Eine archivierte Integer- oder Float-Variable auswählen, etwa Temperatur.
3. Titel, Zeitraum und Intervall (15 Minuten, 30 Minuten, Stunde, Tag oder
   Kalenderwoche) festlegen.
4. Kacheldesign einstellen; für IPSView die Ausgabe einschalten und bei
   Bedarf ein unabhängiges Design verwenden.

Jede Kerze enthält den ersten und letzten tatsächlich protokollierten
Messwert sowie Minimum und Maximum des Intervalls. Der Körper verbindet
ersten und letzten Wert, die dünne Linie zeigt den gesamten Wertebereich.
Standardmäßig bedeutet Grün einen Anstieg, Rot einen Abfall und die
neutrale Farbe einen gleichen Anfangs- und Endwert. Das ist keine
Gut-/Schlechtbewertung der Messwerte.

## Benannte Vergleichspanels

Mit „Benannte Vergleichspanels verwenden“ lassen sich **ein bis vier** numerische
Standard-Archivvariablen in einer Instanz vergleichen. Die Liste „Vergleichspanels“
legt Quellen, optionale Panelnamen und Reihenfolge fest. Ohne eigenen Namen wird
der aktuelle Variablenname angezeigt. Gleiche Namen sind erlaubt; jede Variable
wird durch ihre ID identifiziert und kann nur einmal vorkommen.

Jedes Panel besitzt eine eigene Werteskala und verwendet die Einheit sowie
Nachkommastellen seiner Quelle. Beispielsweise können Temperatur, Luftfeuchtigkeit
und Luftdruck gemeinsam betrachtet werden. Werte werden weder normalisiert noch
addiert. Der Tooltip enthält die originalen ersten/letzten Werte, Extrema und
Messzeitpunkte des jeweiligen Panels. Ein leeres Panel bleibt als solches sichtbar;
fehlende Intervalle werden nicht mit Nullen aufgefüllt.

Zeitraum und Intervall gelten für alle Panels. Bei eingeschaltetem Zeitzoom
steuert ein gemeinsamer Regler alle Zeitachsen; das Mausrad funktioniert über
jedem Panel. Die vollständigen geladenen Werte bestimmen weiterhin jede einzelne
Werteskala. Ein kompatibles Datenupdate oder Größenwechsel erhält das Zoomfenster.
Der vorhandene Zoomschalter bleibt je Designer unabhängig und standardmäßig aus.

Vertikale Kerzen stehen in Panels untereinander, horizontale Kerzen nebeneinander.
Kleine Kacheln können deshalb innerhalb der Diagrammfläche gescrollt werden:
über freie Bereiche, Überschriften oder mit der Scrollleiste. Über dem eigentlichen
Koordinatensystem bedient das Mausrad bei aktivem Zeitzoom die Zeitachse.
Der gemeinsame Zeitregler liegt unter beziehungsweise rechts neben den Panels.
Schriftgrößen und die übrigen Designoptionen gelten wie bisher für beide Ausgaben.
Die gekennzeichneten Vorschauen zeigen synthetische Beispiele aller Panels.

Alle Panels teilen sich **insgesamt** 63 Archivabfragen und 64000 protokollierte
Rohwerte je Aktualisierung. Die gemeinsame Zeitplanung erlaubt weiterhin höchstens
720 Intervalle. Bei einem zu dichten Archiv wird die gesamte Aktualisierung
abgewiesen; es werden keine Panels oder Daten stillschweigend weggelassen.
Dann einen kürzeren Zeitraum oder weniger Quellen wählen.

Die bisherige „Archivvariable“ bleibt gespeichert und wird bei aktivierten Panels
nicht verwendet. Beim Ausschalten der Paneloption ist sie wieder maßgeblich.
Bestehende Instanzen ändern ihr Verhalten durch das Modulupdate nicht.

## Zeit und fehlende Werte

Es werden ausschließlich abgeschlossene Intervalle angezeigt. Zur Auswahl
stehen 24 Stunden, 7 oder 30 Tage. Im Stundenmodus sind das 24, 168 oder 720
reale Stunden; im Tagesmodus 1, 7 oder 30 lokale Kalendertage.

15-Minuten-Intervalle beginnen lokal bei :00, :15, :30 und :45,
30-Minuten-Intervalle bei :00 und :30. Ein 24-Stunden-Fenster enthält damit
96 beziehungsweise 48 vollständige Intervalle, sieben Tage 672 beziehungsweise
336. Die Vorgabe „30 Tage“ überschreitet in beiden Minutenmodi das unveränderte
720er-Limit und wird mit einem Hinweis abgewiesen, nicht still gekürzt.

Kalenderwochen beginnen montags um 00:00 Uhr und enden am folgenden Montag.
Die laufende Woche bleibt ausgeschlossen. „7 Tage“ zeigt eine vollständige
Vorwoche, „30 Tage“ vier vollständige Wochen und „24 Stunden“ keine vollständige
Woche. Die Beschriftung verwendet ISO-Wochen einschließlich ISO-Jahr, etwa
`2026-W01`, auch wenn die Woche bereits im Dezember des Vorjahres beginnt.

Zusätzlich lässt sich eine **benutzerdefinierte Dauer** von 1–1000 Minuten,
Stunden, Tagen oder Wochen wählen. Sie endet an der letzten abgeschlossenen
Intervallgrenze. Nur ganze Intervalle innerhalb dieser verstrichenen Dauer
werden berücksichtigt; ein kurzer Zeitraum kann
deshalb leer sein. Höchstens 720 Intervalle sind erlaubt. Bei Überschreitung
bitte die Dauer verkürzen oder ein längeres Intervall wählen.
Die Zeitzone der Symcon-Installation gilt, einschließlich Sommerzeit.
Minuten- und Stundenbeschriftungen enthalten den UTC-Offset, damit doppelte
Uhrzeiten beim Herbstwechsel unterscheidbar bleiben.

Bei benutzerdefinierten Angaben entspricht ein Tag 24 verstrichenen Stunden,
eine Woche sieben solchen Tagen. Die festen Tages-Vorgaben zählen dagegen
weiterhin lokale Kalendertage. Deshalb kann eine benutzerdefinierte Dauer von
einem Tag im Tagesmodus beim Herbstwechsel keinen vollständigen 25-Stunden-Tag
enthalten. Ebenso kann eine eigene Dauer von einer Woche (168 Stunden) eine
vollständige 169-Stunden-Kalenderwoche beim Herbstwechsel nicht enthalten.
Die festen Wochenvorgaben zählen lokale Daten und erhalten die ganze Vorwoche.
Ein leerer Zeitraum löst keine Archivabfrage aus. Kachel und IPSView
verwenden dieselbe Zeitkonfiguration; Designänderungen verändern sie nicht.

„Fester historischer Zeitraum“ ergänzt eine Von-/Bis-Auswahl in der angezeigten
Symcon-Zeitzone. Der Beginn ist eingeschlossen, das Ende ausgeschlossen und darf
nicht in der Zukunft liegen. Nur vollständig enthaltene abgeschlossene Intervalle
werden berücksichtigt: Von 12.10.2026 08:30 bis 13:30 entstehen
im Stundenmodus die Intervalle 09:00 bis einschließlich 12:59:59. Im Tagesmodus
enthält dieses Fenster keinen vollständigen Tag und bleibt leer.

Die Grenzen bleiben bei Aktualisierungen fest. Fehlende oder ungültige Daten,
nicht existierende Uhrzeiten und doppeldeutige Uhrzeiten beim Herbstwechsel
werden abgewiesen; bitte eindeutige Grenzen vor oder nach dem Wechsel wählen.
Ein Fenster über den Wechsel ist möglich und enthält die tatsächlichen Stunden
beziehungsweise lokale 23-/25-Stunden-Tage und 167-/169-Stunden-Wochen.
Historische Stundenfenster über Zeitzonenwechsel mit nicht ganzstündiger
Verschiebung werden nicht unterstützt.
Minutenintervalle erlauben nur zum Raster passende Offsetwechsel (etwa
30 Minuten); andere historische Verschiebungen werden sichtbar abgewiesen.
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
