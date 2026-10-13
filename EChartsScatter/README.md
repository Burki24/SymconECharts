# EChartsScatter

`EChartsScatter` vergleicht archivierte Werte zweier numerischer Symcon-Variablen
als Streudiagramm. Eine dritte Archivvariable kann die Punktgröße steuern
(Bubble-Darstellung). Ein gemeinsames `EChartsGateway` genügt für alle
ECharts-Instanzen.

## Quellen und Daten

Im Abschnitt **Scatter-Quellen** genau eine Variable für **X-Achse** und eine
für **Y-Achse** wählen. **Blasengröße** ist optional. Jede Rolle benötigt eine
andere Variablen-ID; gleiche angezeigte Namen sind zulässig. Ein ausdrücklich
konfigurierter Name hat Vorrang, andernfalls erscheint der aktuelle
Symcon-Name. IDs werden in der Grafik nicht angezeigt. Einheit und
Nachkommastellen können aus der Variablendarstellung stammen oder je Quelle
gesetzt werden.

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
optionale Wertebeschriftungen, Schriftgrößen, Farben und Animation.
Die SVG-Live-Vorschau zeigt die Gestaltung schematisch mit Beispieldaten;
sie liest beim Ändern des Formulars keine Archivdaten.

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
