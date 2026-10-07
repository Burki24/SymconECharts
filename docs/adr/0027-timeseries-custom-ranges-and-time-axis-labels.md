# ADR 0027: Benutzerdefinierte Zeiträume und Zeitachsenbeschriftung

- Status: Angenommen
- Datum: 2026-10-07
- Entscheider: Burki24
- Ergänzt: ADR 0017, ADR 0018, ADR 0019, ADR 0020 und ADR 0022
- Ersetzt durch: –

## Kontext

Die festen rollenden Zeiträume der TimeSeries decken typische Ansichten ab,
reichen aber nicht für alle Anlagen und Auswertungen. Anwender benötigen
beispielsweise drei Stunden, zwei Wochen oder andere fortlaufende Fenster.
Außerdem soll die Zeitachse je nach Einsatzzweck nur Uhrzeiten, nur ein Datum
oder beides anzeigen können.

Beide Einstellungen betreffen das fachliche Chartmodell und nicht den
Gateway-Vertrag. Kachel und IPSView sind jedoch eigenständige Ansichten:
Anwender können beispielsweise in der Kachel einen Tag und in einer
dauerhaften IPSView-Ansicht einen Monat benötigen.

## Entscheidung

Die bestehende Property `Range` behält ihre bisherigen Werte und erhält den
zusätzlichen Wert `custom`. In diesem Fall bilden die additiven Properties
`CustomRangeValue` und `CustomRangeUnit` den rollenden Zeitraum. Zulässig sind
ganzzahlige Werte von 1 bis 1.000 sowie die Einheiten Minute, Stunde, Tag und
Woche. Das Modul wandelt die Kombination vor der Archivabfrage deterministisch
in Sekunden um. Bestehende Presets und der Standard `24h` bleiben unverändert.

Die additive Property `TimeAxisLabelFormat` ist auf die Werte `auto`, `time`,
`date` und `date-time` begrenzt. `auto` überlässt ECharts die kontextabhängige
Beschriftung. Die drei ausdrücklichen Modi verwenden im Browser die lokale
Gebiets- und Zeitzoneneinstellung. Freie Formatstrings oder ausführbarer
JavaScript-Code werden nicht angenommen.

IPSView übernimmt diese Einstellungen standardmäßig über
`IPSViewUseTileTimeSettings`. Wird die Übernahme abgeschaltet, bilden die
additiven Properties `IPSViewRange`, `IPSViewCustomRangeValue`,
`IPSViewCustomRangeUnit` und `IPSViewTimeAxisLabelFormat` einen unabhängigen
Vertrag. Das Gerätemodul berechnet und lädt dann für beide Ausgaben ein eigenes
Zeitfenster. Quellen, Datenmodus, Reducer und Punktbudget bleiben gemeinsam.
Die automatische Aggregation wird für jede Ausgabe anhand ihres wirksamen
Zeitraums bestimmt; der Archivtimer berücksichtigt die früheste erforderliche
Intervallgrenze.

Der gemeinsame Browserrenderer bleibt unverändert für beide Ausgaben
zuständig und erhält jeweils das bereits normalisierte Modell. Die
SVG-Formularvorschau kennzeichnet den wirksamen Beschriftungsmodus mit
repräsentativen Achsenwerten.

## Folgen

Bestehende Instanzen bleiben kompatibel, weil IPSView die Kachelwerte
standardmäßig erbt. Benutzerdefinierte Zeiträume
verwenden weiterhin denselben Rohwert-, Echtzeit- und Aggregationspfad sowie
dasselbe Punktbudget. Sehr lange Fenster können daher wie feste Zeiträume zu
einer gröberen automatischen Verdichtung führen.

Ungültige Werte, Einheiten oder Beschriftungsmodi erzeugen den bestehenden
Konfigurationsfehler 202. Unabhängige IPSView-Zeiteinstellungen werden nur bei
aktivierter IPSView-Ausgabe validiert. Der Gateway-Vertrag, Archivdaten und
Variablenkonfiguration werden nicht verändert.

## Nachweise

- Wiederverwendungsentscheidung in [ADR 0017](0017-reuse-before-new-chart-development.md)
- TimeSeries-Vertrag in [ADR 0018](0018-time-series-module.md)
- Formular und Modell in `EChartsTimeSeries/module.php`
- gemeinsamer Renderer in `EChartsTimeSeries/visualization/app.js`
- PHP-Vertrags- und Integrationstests sowie `tests/time_series_layout.js`
