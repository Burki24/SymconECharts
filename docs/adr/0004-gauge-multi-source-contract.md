# ADR 0004: Mehrquellenvertrag für Gauge Multi festlegen

- Status: Angenommen
- Datum: 2026-10-04
- Entscheider: Burki24
- Ergänzt: ADR 0003
- Ersetzt durch: –

## Kontext

`EChartsGaugeMulti` wurde zunächst als lauffähige Kopie des Single-Moduls
angelegt. Zusammengesetzte ECharts-Gauges wie Multi Title, Ring und Car
benötigen jedoch mehrere unabhängig skalierbare Werte. Das dafür persistierte
Format muss die Reihenfolge der Werte erhalten, Symcon-Referenzen sicher
pflegen und dem späteren Renderer stabile Identitäten liefern.

Eine einzelne universelle Skala reicht nicht aus: Ring- und
Dashboard-Darstellungen können beispielsweise Temperatur, Luftfeuchtigkeit
und Druck mit unterschiedlichen Einheiten und Wertebereichen kombinieren.

## Entscheidung

`EChartsGaugeMulti` registriert die String-Property `Sources`. Sie enthält eine
JSON-Liste aus mindestens 2 und höchstens 16 Einträgen. Jeder Eintrag besitzt:

- `VariableID`: eindeutige vorhandene Integer- oder Float-Variable;
- `Label`: optionale Beschriftung; leer verwendet den Variablennamen;
- `Minimum` und `Maximum`: eigener numerischer Wertebereich mit
  `Minimum < Maximum`;
- `Unit`: optionale Einheit;
- `Decimals`: Ganzzahl zwischen 0 und 6.

Die Listenreihenfolge ist fachlich relevant und bestimmt später die Zuordnung
zu den Positionen einer Multi-Vorlage. Doppelte Variablen sind nicht erlaubt.
Dadurch kann jeder Eintrag die stabile technische ID `variable-<VariableID>`
verwenden, welche ECharts-Aktualisierungen und Animationen eindeutig zuordnet.

Alle konfigurierten Variablen werden als Symcon-Referenzen registriert.
Entfernte Quellen werden beim nächsten `ApplyChanges()` wieder abgemeldet.
Fehlerhaftes JSON, eine unzulässige Quellenanzahl, fehlende oder nicht
numerische Variablen und Duplikate führen zu Status 201. Ungültige
Wertebereiche oder Nachkommastellen führen zu Status 202.

`ECGM_GetGaugeData()` liefert ein JSON-Modell mit `schemaVersion: 1`,
`family: gauge`, `variant: multi`, gemeinsamem Titel und einer geordneten
`items`-Liste. Jeder Eintrag enthält stabile ID, Quellidentität, Zeitstempel,
Skalen- und Formatierungsdaten sowie den aktuellen numerischen Wert.

Das Gateway-Protokoll `current.read` wird für jede Quelle wiederverwendet.
Eine neue Bulk-Operation wird erst eingeführt, wenn Messungen einen
tatsächlichen Bedarf oder Anforderungen an konsistente Stichzeitpunkte zeigen.
Schlägt eine Quelle fehl, schlägt der gesamte synchrone Multi-Abruf kontrolliert
fehl; unvollständige Charts werden in diesem ersten Vertrag nicht geliefert.

## Kompatibilität

Das in Version 1.8 enthaltene Multi-Modul war ausdrücklich als vorläufiges
Einzelquellen-Gerüst dokumentiert. Seine Properties `SourceVariableID`,
`Minimum`, `Maximum`, `Unit` und `Decimals` werden durch `Sources` ersetzt;
`Title` bleibt erhalten.

Eine automatische Überführung ist nicht sinnvoll, weil eine einzelne Quelle
den Mindestvertrag eines Multi-Gauges nicht erfüllt und keine zweite Quelle
erfunden werden darf. Bereits angelegte Entwicklungsinstanzen bleiben nach dem
Update mit Status 201 inaktiv, bis eine gültige Quellenliste konfiguriert ist.
Es werden keine Variablen, Werte oder Archivdaten gelöscht.

## Zentrale Helper

Der synchronisierte `DataFlowHelper` bleibt für die Transporthülle zuständig.
Die geprüften zentralen Helper enthalten keinen allgemeinen Vertrag für eine
fachliche, geordnete Gauge-Quellenliste. Parsing, Validierung und
Referenzabgleich verbleiben deshalb im Multi-Modul. Eine Zentralisierung wird
erst geprüft, wenn ein tatsächlich identischer Vertrag in weiteren Modulen
entsteht.

## Alternativen

- **Eine globale Skala für alle Werte:** einfacher, aber für gemischte
  Einheiten und Ring-/Dashboard-Gauges ungeeignet.
- **Beliebige oder doppelte Variablen:** flexibler, verhindert jedoch eine
  stabile, natürliche Item-ID und begünstigt versehentliche Duplikate.
- **Unbegrenzte Quellenanzahl:** vermeidet eine Obergrenze, erlaubt aber
  unkontrollierte Gateway-Aufrufe und nicht mehr sinnvoll darstellbare Gauges.
- **Automatische Migration der einen Gerüstquelle:** würde einen ungültigen
  Multi-Zustand konservieren oder eine zweite Quelle erfinden.

## Folgen

Gauge Multi besitzt jetzt einen eigenständigen, versionierten Datenvertrag und
kann mehrere Werte unabhängig skalieren. Vorlagen, Renderer, Theme und
Ausgabeadapter können darauf aufbauen, ohne die persistierte Quellenstruktur
neu festlegen zu müssen.

Uhrzeitbasierte Gauges ohne Symcon-Quellvariablen und andere virtuelle Quellen
sind nicht Teil dieses Vertrags. Sie benötigen vor ihrer Umsetzung eine eigene
Erweiterungsentscheidung.

## Nachweise

- Konfigurations- und Strict-Verträge unter `tests/symcon_strict.php`
- Datenabruf, Referenzen und Fehlerfälle unter `tests/gateway_gauges.php`
- Strukturprüfung unter `tests/validate_structure.php`
