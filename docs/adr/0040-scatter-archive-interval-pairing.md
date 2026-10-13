# ADR 0040: Scatter/Bubble aus gemeinsamen Archivintervallen

- Status: Angenommen
- Datum: 2026-10-13
- Entscheider: Burki24
- Ergänzt: ADR 0017, ADR 0018, ADR 0032, ADR 0036 und ADR 0037

## Kontext

Scatter vergleicht zwei numerische Größen unabhängig von einer Zeitachse. Die
Quellen kommen jedoch aus separaten Symcon-Archiven und besitzen nicht zwingend
für jedes Intervall einen Wert. Ein zeitlich nächstgelegener, gehaltener oder
interpolierter Wert würde eine nicht gemessene Kombination vortäuschen.

## Entscheidung

`EChartsScatter` ist eine eigene Geräteinstanz mit der GUID
`{9865CDB4-5CA9-4C34-A148-983E0B5F2FA6}` und dem Präfix `ECSC`.
Genau eine numerische Variable übernimmt X und eine andere Y. Eine dritte,
ebenfalls eigene Variable kann die Bubble-Größe steuern. Die Variablen-ID ist
die interne Identität; sichtbare Texte verwenden zuerst die konfigurierte
Beschriftung und sonst den aktuellen Symcon-Namen.

Alle Quellen werden mit derselben Aggregationsstufe und demselben Zeitraum über
den bestehenden Gateway-Vertrag `archive.read` abgefragt. Nur abgeschlossene
Intervalle werden berücksichtigt. `EChartsScatter/ScatterPairing.php` bildet
die Schnittmenge **identischer Intervall-Zeitstempel**; im Bubble-Modus muss
auch für die Größenquelle genau dieser Zeitstempel vorhanden sein. Es gibt
keine Interpolation, Vorwärtsfüllung, Rundung auf einen benachbarten
Zeitstempel oder Verknüpfung nach Listenposition. Null- und negative X/Y-Werte
sind gültige Messwerte. Ein leerer Schnitt ergibt ein leeres Diagramm und
keinen erfundenen Punkt.

Der Gateway bleibt familienübergreifende Infrastruktur. Zeitraumplanung,
Animation, Beschriftungsregler, Formularvorschau, responsive Ausgabe und
IPSView-Transport verwenden vorhandene `libs`-Bausteine. Die fachliche
X/Y/Größen-Verknüpfung bleibt im Scatter-Modul, bis eine zweite Familie
denselben Vertrag tatsächlich benötigt. Die ECharts-Runtime wird unverändert
und versioniert mit `ScatterChart` im kartesischen Bundle ausgeliefert.

## Folgen und Prüfung

Das Punktbudget wird vor der Verknüpfung auf die Archivabfragen verteilt;
die Anzahl sichtbarer Punkte kann dadurch deutlich kleiner sein. Tests decken
fehlende Intervalle, Null-/Negativwerte, optionale Größe, Quellidentität,
Gateway-Daten, beide Ausgaben und Rendereroptionen ab. Die reale Darstellung
in Symcon-Kachel und nativer IPSView-App benötigt einen Modulupdate-Test.
