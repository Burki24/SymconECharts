# ADR 0028: Konfigurierbare Datenlückenerkennung für TimeSeries

- Status: Angenommen
- Datum: 2026-10-07
- Entscheider: Burki24
- Ergänzt: ADR 0018, ADR 0019 und ADR 0027
- Ersetzt durch: –

## Kontext

ECharts unterbricht eine Linie nur an ausdrücklich leeren Datenpunkten. Das
TimeSeries-Modell liefert dagegen ausschließlich tatsächlich vorhandene
Archiv- oder Echtzeitwerte. Ohne zusätzliche Semantik verbindet der Renderer
daher auch Werte vor und nach einem längeren Sensorausfall. Bei
ereignisgesteuerten Quellen kann ein langer Abstand zugleich beabsichtigt sein;
eine Unterbrechung darf deshalb nicht ungefragt aktiviert werden.

## Entscheidung

EChartsTimeSeries erhält die additiven Properties `GapDetectionMode` und
`GapThresholdMinutes`. Der Modus ist `off`, `automatic` oder `custom`.
Bestehende Instanzen verwenden standardmäßig `off` und behalten damit ihr
bisheriges Verhalten.

Im automatischen Modus bestimmt der Browserrenderer für jede Reihe den
typischen positiven Abstand als unteren Median der vorhandenen Intervalle. Ein
Abstand von mehr als dem Dreifachen dieses Werts wird als Lücke dargestellt.
Mindestens drei Punkte und zwei positive Intervalle sind erforderlich. Der
benutzerdefinierte Modus verwendet für alle Reihen den festgelegten Abstand
von 1 bis 10.080 Minuten.

Die vorhandenen Messpunkte bleiben im versionierten Modell unverändert. Das
Modell transportiert Modus und festen Grenzwert unter `range`; der gemeinsame
Renderer fügt nur für die ECharts-Daten zwischen den betroffenen Punkten einen
Punkt mit leerem Wert ein. Derselbe Pfad gilt für Kachel, IPSView und live
angehängte Werte. Das Gateway und sein Archivvertrag werden nicht erweitert.

## Folgen

Die automatische Erkennung ist robust gegen einzelne große Ausreißer, bleibt
bei unregelmäßig oder ausschließlich bei Wertänderungen meldenden Quellen aber
eine fachliche Annahme. Anwender können sie deshalb deaktiviert lassen oder
einen bekannten festen Abstand wählen. Eingefügte Unterbrechungspunkte zählen
nicht zum Archiv-Punktbudget und verändern keine Quelldaten.

Ungültige Modi und benutzerdefinierte Abstände außerhalb des zulässigen
Bereichs erzeugen den bestehenden Konfigurationsstatus 202.

## Nachweise

- Property-, Formular- und Modellvertrag in `EChartsTimeSeries/module.php`
- gemeinsamer Renderer in `EChartsTimeSeries/visualization/app.js`
- PHP-Vertrags- und Integrationstests in `tests/gateway_gauges.php`
- Renderer-Verhalten in `tests/time_series_layout.js`
