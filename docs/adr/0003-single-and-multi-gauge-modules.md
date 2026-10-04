# ADR 0003: Gauge-Familie in Single und Multi aufteilen

- Status: Angenommen
- Datum: 2026-10-03
- Entscheider: Burki24
- Ersetzt: ADR 0001 hinsichtlich Gauge-Modulname und Gauge-Präfix; ADR 0002
  hinsichtlich Gauge-Modulname
- Ersetzt durch: –

## Kontext

Die offiziellen Apache-ECharts-Beispiele umfassen sowohl Gauges mit genau
einem Messwert als auch zusammengesetzte Darstellungen mit mehreren Werten und
Zeigern. Diese Varianten benötigen unterschiedliche Konfigurations- und
Datenmodelle. Ein einziges Gauge-Modul würde dadurch dauerhaft zwischen einem
einfachen Quellenvertrag und dynamischen Mehrquellenlisten umschalten müssen.

Das bisherige `EChartsGauge` besitzt bereits einen getesteten Vertrag für eine
numerische Quellvariable. Die sichtbare Diagrammausgabe ist noch nicht
implementiert und das Projekt befindet sich weiterhin vor seiner ersten
Funktionsfreigabe.

## Entscheidung

Die Gauge-Familie wird durch zwei Gerätemodule repräsentiert:

- `EChartsGaugeSingle` besitzt genau eine numerische Quellvariable. Es übernimmt
  die bisherige Modul-GUID `{0CAA2780-342F-E5B9-2865-CB8DCED0BE7C}` und erhält
  das Funktionspräfix `ECGS`.
- `EChartsGaugeMulti` ist für zusammengesetzte Gauges mit mehreren Werten
  vorgesehen. Es erhält die neue Modul-GUID
  `{E667D9C1-379D-44ED-A313-FF21FEFC355F}` und das Funktionspräfix `ECGM`.
- Beide Module bleiben Geräte (`type: 3`), verwenden dieselben versionierten
  Datenfluss-IDs und können eine vorhandene `EChartsGateway`-Instanz gemeinsam
  nutzen.
- Das bisherige Präfix `ECGA` wird vor der ersten Freigabe durch `ECGS` ersetzt
  und nicht neu vergeben.

Als erster struktureller Schritt wird `EChartsGaugeMulti` aus dem bestehenden
Single-Vertrag abgeleitet. Dadurch ist das Modul eigenständig installier- und
prüfbar. Das persistente Mehrquellenmodell wird in einer nachfolgenden
Entscheidung festgelegt; bis dahin ist die derzeitige Einzelquellenkonfiguration
des Multi-Moduls ausdrücklich nur ein technisches Gerüst.
Die nachfolgende Entscheidung ist in
[`ADR 0004`](0004-gauge-multi-source-contract.md) dokumentiert.

## Alternativen

- **Ein Gauge-Modul mit umschaltbarem Modus:** vermeidet eine weitere
  Moduldefinition, koppelt aber zwei unterschiedliche persistente
  Konfigurationsverträge und erschwert Formular, Validierung und Migration.
- **Ein Modul pro ECharts-Beispiel:** würde eng verwandte Darstellungsvarianten
  ohne eigenes Datenmodell unnötig vervielfachen.
- **Sofortiges Mehrquellenmodell:** würde der strukturellen Aufteilung eine noch
  nicht entschiedene Listen-, Zeit- und Validierungsstruktur hinzufügen.

## Folgen

Single- und Multi-Gauges können getrennt weiterentwickelt und getestet werden.
Gemeinsame Transportlogik verwendet weiterhin den synchronisierten
`DataFlowHelper`. Gauge-spezifische Gemeinsamkeiten werden erst dann direkt
unter `libs` abstrahiert, wenn die endgültigen Datenmodelle ihre tatsächlich
gemeinsame Grenze zeigen; sie gehören nicht in die zentralen ModuleHelper.

Die Umbenennung ändert den PHP-Funktionspräfix von `ECGA` zu `ECGS`. Da das
Gerüst noch nicht freigegeben wurde und die bestehende Modul-GUID beim
Single-Modul verbleibt, wird keine Laufzeitmigration angelegt. Reale
Symcon-Installationen müssen nach dem Update dennoch gesondert geprüft werden.

## Nachweise

- Eigentümerentscheidung zur Gauge-Aufteilung vom 03.10.2026
- Modulverträge unter `tests/module_contracts.php`
- Strict- und Formularverträge unter `tests/symcon_strict.php`
- Gateway-Integration unter `tests/gateway_gauges.php`
