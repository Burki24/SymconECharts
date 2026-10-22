# ADR 0088: Feste Größenbezüge für Scatter/Bubble

- Status: Angenommen
- Datum: 2026-10-22
- Ergänzt: ADR 0040

## Kontext

Die beobachteten Extrema eines Archivfensters bestimmen bisher die
Blasendurchmesser. Damit kann derselbe Messwert bei einer Aktualisierung
oder einem anderen Zeitraum unterschiedlich groß erscheinen.

## Entscheidung

Scatter ergänzt `BubbleRangeMode` (`data` als kompatibler Default, `manual`),
`BubbleMinimum` (0.0) und `BubbleMaximum` (100.0), jeweils auch mit
`IPSView`-Präfix im bestehenden unabhängigen Designvertrag. Designkopie und
beide reinen Formularvorschauen verwenden diese Properties ebenfalls.
Neue Properties mit kompatiblen Defaults benötigen keine Umschreibung alter
Konfigurationen. Automatisch erhält die bisherige Extrema-/Gleichwertberechnung.

Bei festen Grenzen entspricht das Minimum der Pixel-Punktgröße und das
Maximum der maximalen Pixel-Blasengröße. Dazwischen wird der Durchmesser
linear skaliert; außerhalb wird nur die Geometrie begrenzt. Die Grenzen
gehören zur Einheit der Größenquelle, nicht zu X/Y, Farbskala oder Zeitraum.
Ohne Größenquelle bleiben normale Scatter-Punkte erhalten. Originalwerte,
Einheiten, Zeitstempel und die gemeinsame Intervallverknüpfung bleiben unverändert.

Der vorhandene `EChartsValueRange` prüft endliche, streng aufsteigende Grenzen
mit endlicher Differenz; Vorschauen nutzen die bestehende reine Projektion
aus `EChartsNormalization`. Es entstehen keine neuen gemeinsamen Helper oder
lokalen Kopien synchronisierter Helper. Die native Durchmesserberechnung
bleibt Scatter-spezifisch. Archivoperation, 2–3 Rollen, Abfrageaufteilung,
200–2000-Punktbudget und HTML-Budget werden nicht verändert.

## Nachweise und Grenzen

Öffentliche Modul-/Gateway-Tests prüfen Originalmodelle, getrennte Designer,
Entwürfe, Kopie, Fehler vor Archiv-I/O und unveränderte Budgets. JavaScript-
Tests prüfen Größenstabilität, Begrenzung, gleiche Werte und beide Adapter;
die gepinnte Engine bestätigt reale Symbolgrößen. Live-Abnahme nach Update
und ein tatsächlicher IPSView-Client bleiben separat erforderlich.
Mehrere benannte X/Y-Gruppen sind nicht Bestandteil dieses Schritts.
