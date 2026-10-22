# ADR 0091: Optionales IQR-Statistikmodell für Boxplot

- Status: Angenommen; Live-Abnahme nach Modulupdate offen
- Datum: 2026-10-22
- Entscheider: Burki24
- Ergänzt: ADR 0054, 0061 und 0077

## Vertrag

`EChartsBoxplot` erhält die gemeinsame Datenproperty `WhiskerMode` mit den
Werten `minmax` (kompatibler Standard) und `iqr`. Sie gehört nicht zu den
unabhängigen Designs und wird bei einer Designkopie nicht verändert. GUID,
Quellen-JSON, Einheiten, R7-Quartile, Zeitplanung und Gateway bleiben unverändert.

Im IQR-Modus verwenden Q1/Median/Q3 weiterhin alle abgeschlossenen
Stundenmittel. Ausreißer liegen strikt außerhalb von Q1 − 1,5 × (Q3 − Q1)
und Q3 + 1,5 × (Q3 − Q1). Whisker enden an den äußersten tatsächlich vorhandenen
Stichproben innerhalb dieser Grenzen, nicht an künstlichen Grenzwerten.
Gleichheit gehört zum Inlierbereich. Bei IQR = 0 sind alle vom Quartil
abweichenden Werte Ausreißer. Einzelsamples und konstante Verteilungen bleiben
zulässig, leere Quellen haben weder Box noch Punkte. Fehlende Stunden werden
nicht aufgefüllt. Es gibt keine Interpretation als Rohwertspitzen oder Alarme.

`BoxplotStatistics::Summarize` erhält einen optionalen Modusparameter. Ohne
Aktivierung bleiben Ergebnisstruktur und Statistik erhalten. Im IQR-Modus
bleibt `statistics` die ECharts-Fünferfolge mit beobachteten Whiskern; zusätzlich
werden `sampleMinimum`, `sampleMaximum`, `outlierCount` und `outliers` geliefert.
Ausreißer sind kompakte `[timestamp, value]`-Paare in Zeitreihenfolge. Auch
gleiche Werte mehrerer Stunden bleiben einzeln erhalten. Klassifizierung erfolgt
skaliert, um Überlauf bei endlichen großen Stichproben zu vermeiden.

## Wiederverwendung und Ausgabe

Live-Modell und synthetische Vorschau verwenden denselben Statistikbaustein.
Vorhandene zentrale Query-, Vorschau-, Designer-, Identitäts- und HTML-/IPSView-
Helper werden wiederverwendet. Kein neuer allgemeiner Statistik-Helper wird für
einen einzelnen Familienvertrag eingeführt; synchronisierte Helper bleiben unverändert.

Die bestehende Boxplot-Serie und eine zusätzliche native Scatter-Serie teilen
sich dieselben Kategorien-IDs, Achsen und DataZoom-Filter. Punktidentität ist
Quellen-ID plus Stundenzeitstempel. Tooltips lösen Quellen nicht anhand eines
gefilterten Positionsindex auf. Die vollständige Werteskala enthält auch
unsichtbare Ausreißer. Min/Max-Modelle erzeugen keine Scatter-Serie.

Die gepinnte ECharts-Version bleibt 6.1.0, Apache-2.0. Offizielle Einstiege,
Boxplot-/Scatter-Dokumentation sowie npm-Version und Integrität wurden erneut
geprüft. Ausschließlich der Boxplot-Subset-Build erhält `ScatterChart`;
Herkunft, Größe und SHA-256 stehen in `THIRD_PARTY_NOTICES.md`.
Andere Runtimes bleiben unverändert. Referenzen:
[Boxplot](https://echarts.apache.org/en/option.html#series-boxplot),
[Scatter](https://echarts.apache.org/en/option.html#series-scatter).

## Grenzen und Nachweise

32 Quellen, 1000 Stundenmittel je Quelle und 10000 insgesamt bleiben unverändert.
Ausreißer entstehen ausschließlich aus diesen geladenen Werten; keine weitere
Archivabfrage, keine stille Kürzung und kein zusätzliches Datenbudget. Der
1-MiB-Ausgabeguard bleibt bestehen. Die reine Vorschau kennzeichnet Beispieldaten
und ihren höchstens zehn Quellen umfassenden Ausschnitt.

Automatisierte Prüfungen: unabhängige R7/IQR-Beispiele, exakte Fence-Gleichheit,
Null-IQR, leere/einzelne/negative/extreme Werte; echter Gateway mit Kernel-Doppeln,
beide Ausgaben und veröffentlichte HTML-Variable; ungültiger Modus und neutrale
Entwürfe; 32 Quellen/9984 Stundenmittel/4864 Ausreißer unter dem Ausgabelimit.
Die ausgelieferte ECharts-Runtime prüft echte Box-/Punktgeometrie, beide
Ausrichtungen, Quellidentität nach Navigation, vollständige Skala, Resize,
Updates, Leerzustände und Rückkehr zum Min/Max-Modell.

Bei der echten Browser-Mausradprüfung wurde festgestellt, dass ECharts 6.1
bei `zoomLock=true` den Wheel-Listener des Inside-Controllers nicht registriert.
Boxplot sperrt dessen Ausschnittsgröße nun mit gleichen `minValueSpan` und
`maxValueSpan` statt `zoomLock`. Slider und Mausrad bleiben gekoppelt, Zoomen
per Mausrad bleibt abgeschaltet. Der native Engine-Regressionstest reproduziert
den alten Stillstand und prüft danach die Verschiebung bei unveränderter Spanne.
Die Komponentenkonfiguration bleibt familienlokal; für Browser-/Frame-Ereignisse
wird ausschließlich der bestehende zentrale Interaktions-Helper eingebunden.

Lokale Browserprüfungen mit synthetischen Daten bestätigen Ausreißer und
Mausradempfang mit sichtbarer Verschiebung in beiden Richtungen für vertikale
Kachel- und horizontale IPSView-Testansichten, ohne JavaScript-Fehler. Die
neue Funktion sowie echte Mausrad-/Scrollbedienung in der nativen Symcon-Kachel
und im tatsächlichen IPSView-Client bleiben nach Modulupdate live zu bestätigen.
