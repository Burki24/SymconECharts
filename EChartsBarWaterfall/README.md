# EChartsBarWaterfall

`EChartsBarWaterfall` zeigt, wie sich ein aktueller Wert durch mehrere
Änderungen zu einer Endsumme entwickelt. Das Modul benötigt zwei bis sechzehn
eindeutige numerische Symcon-Variablen mit derselben effektiven Einheit.

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
vorzeichenbehaftete Änderungen; die Endsumme wird berechnet. Wertbeschriftungen
stehen bei fallenden Balken und negativen Endsummen unter der Säule. Ohne gültige
Quellen sind Beispieldaten ausdrücklich markiert. Bei aktiviertem
**Kacheldesign verwenden** folgt die IPSView-Vorschau dem Kacheldesign.
Wertbeschriftungen verwenden die wirksame gemeinsame Einheit und die höchste
Nachkommastellenzahl der Quellen. Die responsive Laufzeitdarstellung prüfen
Sie anschließend in Kachel und Widget.
Bei mehr als fünf Quellen zeigt die Vorschau die ersten fünf Schritte und nennt
die angezeigte sowie die gesamte Quellenzahl; die eigentliche Grafik nutzt alle.

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
