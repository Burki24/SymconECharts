# EChartsScatter

`EChartsScatter` vergleicht archivierte Werte zweier numerischer Symcon-Variablen
als Streudiagramm. Eine dritte Archivvariable kann die Punktgröße steuern
(Bubble-Darstellung). Ein gemeinsames `EChartsGateway` genügt für alle
ECharts-Instanzen.

## Quellen und Daten

Im Abschnitt **Streudiagramm (optional mit Blasengröße)** genau eine Variable
für **X-Achse** und eine für **Y-Achse** wählen. **Blasengröße** ist optional.
Jede Rolle benötigt eine andere Variablen-ID; gleiche angezeigte Namen sind
zulässig. Ein ausdrücklich konfigurierter Name hat Vorrang, andernfalls
erscheint der aktuelle Symcon-Name. IDs werden in der Grafik nicht angezeigt.
Einheit und Nachkommastellen können aus der Variablendarstellung stammen oder
je Quelle gesetzt werden.

Das Modul liest ausschließlich abgeschlossene, aggregierte Archivintervalle.
Ein Punkt entsteht nur, wenn X und Y für **denselben Intervall-Zeitstempel**
vorliegen. Im Bubble-Modus muss auch die Größenquelle in diesem Intervall
vorliegen. Fehlende Werte werden weder interpoliert noch mit einem älteren
Wert aufgefüllt. Der Tooltip nennt Intervallzeit und die zugehörigen Werte.
Die Punktgröße wird zwischen der kleinsten und größten vorhandenen Größe
skaliert; bei lauter gleichen Größen gilt die normale Punktgröße.

Zeitraum, automatische oder feste Archiv-Verdichtung und Punktbudget sind
einstellbar. Ein abgeschnittener Archivbereich wird in der Grafik kenntlich
gemacht. Die Anzahl sichtbarer Punkte kann durch fehlende gemeinsame
Intervalle kleiner sein als das Punktbudget.

## Design und Ausgabe

Der Kacheldesigner bietet Theme, Raster, Punkt- und maximale Blasengröße,
optionale Wertebeschriftungen, Schriftgrößen, Farben und Animation. X- und
Y-Achse sind unabhängig skalierbar: **An Daten anpassen** nutzt den sichtbaren
Wertebereich ohne erzwungenen Nullpunkt, **Null einschließen** zeigt den
Nullpunkt mit an und **Manuell** setzt Minimum und Maximum selbst. Bei vielen
Punkten werden sich überlagernde Wertbeschriftungen ausgeblendet; per Tooltip
sind alle X-/Y- und optionalen Größenwerte weiterhin erreichbar. Sichtbare
Punktbeschriftungen zeigen wahlweise den X-Wert, den Y-Wert (Voreinstellung)
oder beide Werte mit ihren jeweiligen Einheiten. Kachel und unabhängiges
IPSView-Design können die Auswahl getrennt festlegen.
Optional können die Punkte anhand von X, Y oder – mit dritter Quelle – der
Blasengröße eingefärbt werden. Der Farbbereich folgt den Daten oder verwendet
ein manuelles Minimum und Maximum; niedrige und hohe Farbe sowie eine
Farblegende sind einstellbar. Die horizontale Farblegende passt ihre Länge
an die Kachel- oder Widgetbreite an. Ohne diese Option bleibt die bisherige
einheitliche Punktfarbe erhalten. Kachel und IPSView können die Farbkodierung
unabhängig konfigurieren; beide Live-Vorschauen zeigen den Verlauf.
Ein zuschaltbarer X/Y-Zoom stellt für beide Achsen eigene schmale Schieberegler
mit derselben Reglerstärke wie bei den Zeitdiagrammen bereit. In der Kachel
lässt sich die Punktwolke zusätzlich mit dem Mausrad
zoomen; in IPSView wird das Mausrad gesondert verarbeitet. Beide Achsenausschnitte
bleiben bei einer Aktualisierung desselben Zeitraums erhalten und werden beim
Wechsel des Zeitraums zurückgesetzt. Der Zoom ist im Kachel- und im
unabhängigen IPSView-Designer getrennt abschaltbar.
Optional aktiviert die Bereichsauswahl zwei ECharts-Werkzeuge: Ein Rechteck
markiert Punkte in der X/Y-Fläche, das zweite hebt die Auswahl auf. Die
nicht gewählten Punkte werden abgeblendet. Für die Auswahl erscheinen Anzahl
sowie der kleinste und größte X- und Y-Wert mit ihren Einheiten. Die Auswahl
ist nur eine lokale Ansicht und wird bei neuen Daten oder einem Designwechsel
zurückgesetzt; sie ändert keine Symcon-Variablen. Kachel und unabhängiges
IPSView-Design können das Werkzeug getrennt aktivieren.
Die SVG-Live-Vorschau zeigt die Gestaltung schematisch mit Beispieldaten und
berücksichtigt auch die gewählten Achsenbereiche; sie liest beim Ändern des
Formulars keine Archivdaten.

Optional erstellt das Modul eine WebContent-Variable `IPSViewScatter` für
IPSView. Dort kann das Kacheldesign geerbt oder unabhängig gestaltet werden;
auch ein eigener Zeitraum ist möglich. Die Vorschau und Hintergrundanpassung
stehen in beiden Designern zur Verfügung.

Bei einem vorübergehenden Ladefehler bleibt eine zuletzt gültige Grafik zunächst
stehen. Nach wiederholtem Fehler erscheint ein Hinweis und das Modul versucht
nach 60 Sekunden erneut zu lesen. Ohne frühere Daten oder bei ungültiger
Konfiguration erscheint eine Fehlermeldung.

## Funktionen

```php
$json = ECSC_GetScatterData($InstanceID);
$html = ECSC_GetIPSViewHTML($InstanceID);
```

## Lizenz

Lizenz der eigenen Beiträge:
[PolyForm Noncommercial License 1.0.0](../LICENSE).
