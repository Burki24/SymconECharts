# ADR 0105: Benannte Candlestick-Vergleichspanels

- Status: Angenommen; funktional in Kachel und IPSView abgenommen
- Datum: 2026-10-25
- Ergänzt: ADR 0056, 0079, 0092 und 0093

## Vertrag und Kompatibilität

`UseComparisonPanels=false` erhält die bisherige einzelne `VariableID` mitsamt
Designs, Zeitverträgen und Ausgabe-Ident. Bei ausdrücklicher Aktivierung verwendet
das Modul `Sources`, eine Liste von ein bis vier Zeilen mit numerischer
`VariableID` und optionalem `Label` (höchstens 120 Zeichen). Die Einzel-ID
bleibt gespeichert, wird im Panelmodus nicht gelesen und wird nach Abschalten
wieder wirksam. Keine Migration oder Änderung von GUID, Präfix oder Alias.

IDs sind positiv, vorhanden, numerisch und eindeutig. Gleiche Namen bleiben
getrennt. Namen/Pfade sind keine technischen Schlüssel. Die bestehende
Quellenidentität löst leere Labels auf; native Darstellungen und Legacy-Profile
liefern Originaleinheit und Nachkommastellen. Eigene Einheiten benötigen keine
Normalisierung, weil jedes Panel sein eigenes Koordinatensystem besitzt.

## Gemeinsame Zeit und Budgets

Alle Panels verwenden dieselbe zentrale `EChartsArchiveQuery`-Planung und die
bestehenden 15-/30-Minuten-, Stunden-, Tages- und ISO-Wochenintervalle. Relative,
Custom- und absolute Fenster, DST, abgeschlossene Intervalle und echte
Rohwert-Endpunkte bleiben unverändert. Höchstens 720 Zeitintervalle gelten für
das gemeinsame Raster.

`CandlestickData::ReadMany` verwendet denselben Rohwertleser wie `Read`, mit
insgesamt höchstens 63 tatsächlichen Gateway-Aufrufen und 64000 zurückbehaltenen
Rohpunkten über sämtliche Quellen. Verworfene gekürzte Fragmente verbrauchen
Abfragen, werden nicht als Endpunkte verwendet. Dichte Archive verursachen einen
Fehler der gesamten Aktualisierung; kein Panel wird still ausgelassen. Leere
Fenster fragen keine Quelle ab. Cache und Referenzen verwenden alle aktiven IDs;
beide Ausgabewege teilen den Cache. Keine automatische Archivierungsänderung.

## Ausgabe und Wiederverwendung

Ein natives ECharts-Diagramm enthält je Panel ein Grid, eine Zeit- und eine
Wertachse sowie eine Candlestick-Reihe. Die bisherige Optionsbildung wird für
alle Panels wiederverwendet. Vertikale Kerzen stehen untereinander, horizontale
nebeneinander. Mindestgrößen erhalten lesbare Panels; kleine Flächen scrollen.
Der Diagrammtitel bleibt von Panelnamen getrennt. Designs gelten weiterhin je
Ausgabe für alle Panels, ohne automatische Farbzuordnungen oder Normalisierung.

Die zentrale Kategorie-Zoomfunktion akzeptiert optional mehrere Achsenindizes.
Ein gemeinsamer Regler und der bestehende hostfähige Mausradadapter steuern alle
Zeitachsen. Der Adapter berücksichtigt alle angegebenen Grids; freie Bereiche
bleiben scrollbar. Vollständige Werte bestimmen jede Werteskala auch nach Zoom.
Kompatible Updates, Resize und Themewechsel erhalten den Ausschnitt. Änderungen
aktiver Quellen oder Zeitmodelle setzen den inkompatiblen Ausschnitt zurück.
Die unabhängige Designeroption `EnableZoom` bleibt standardmäßig aus.

Lücken bleiben leer; ein gänzlich leeres Panel erhält einen Hinweis. Tooltips
enthalten Panelname, originale Statistik, Einheit, Anzahl und Messzeitpunkte.
Beide SVG-Vorschauen komponieren denselben vorhandenen reinen Kerzenrenderer
mit synthetischen Daten und escaped Panelnamen. Listen-Callbacks nutzen die
zentrale Vorschauverkabelung. Keine Archivabfrage oder persistente Änderung.

Vier vollständige Panels überschreiten unkomprimiert die vorhandene HTML-Grenze.
Deshalb verwendet Candlestick `EChartsStartupState` und dessen vorhandenen
verlustfreien Browserdecoder. Keine neue ECharts-Version oder Auslieferung;
kleine Dokumente bleiben unkomprimiert. Beide maximalen HTML-Ausgaben bleiben
unter 1 MiB. Die offizielle [ECharts-Dokumentation](https://echarts.apache.org/en/option.html#dataZoom-slider.xAxisIndex)
beschreibt mehrere Achsenindizes je DataZoom. Der lokale Build wird zusätzlich
mit echter Mehrfach-Grid-Geometrie geprüft.

## Nachweise und Grenzen

Der gezielte Test begann RED am fehlenden Panel-Formularfeld.
`php tests/gateway_gauges.php --candlestick-panels` prüft Originalstatistiken,
verschiedene Einheiten, gleiche Namen/verschiedene IDs, Referenzen, Cache,
Entwürfe, Fehlerfälle, Rückkehr zur Einzelquelle, leere Fenster und maximale
HTML-Ausgaben. `php tests/candlestick_data.php` prüft zusätzlich 64000 gemeinsame
Rohwerte bei 62 Abfragen und die gemeinsame 63er-Grenze einschließlich Fragmenten.
`node tests/candlestick_layout.js` verwendet den echten ECharts-Build für beide
Ausrichtungen/Adapter, getrennte vollständige Skalen, gemeinsame Zoomfenster,
Tooltip-Metadaten, Lücken, Resize/Refresh und Mehrfach-Grid-Mausradsteuerung.
Verlustfreier Start und Abbruch bei neuem Zustand/Unload sind separat geprüft.

Die lokale Browserfixture nutzt synthetische komprimierte Zustände in getrennten
Frames. Echte Mausradaktionen bestätigen beide Zoomrichtungen über nachfolgenden
Panels, stabile Einzelwerteskalen und Ansichtserhalt nach Fixture-Refresh.
Die Scrollprüfung erreicht auch das vierte Panel; der feste Kachelkopf bleibt
ausgespart. IAB meldete außerdem nicht zugeordnete `MutationObserver`-Fehler;
ein fehlerfreies Browserprotokoll wird nicht behauptet.
Die nachfolgende funktionale MCP-Prüfung und Eigentümerbestätigung in echter
Symcon-Kachel und tatsächlichem IPSView liegen vor. Diese Live-Abnahme ist von
den synthetischen Frames getrennt; echte Touchgeräte bleiben offen. Keine bestehende Installation,
Quelle, Archivierung oder Designkonfiguration wurde geändert.
