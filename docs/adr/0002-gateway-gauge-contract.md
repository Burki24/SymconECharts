# ADR 0002: Gateway- und Gauge-Vertrag festlegen

- Status: Teilweise ersetzt
- Datum: 2026-10-03
- Entscheider: Burki24
- Ersetzt: –
- Ersetzt durch: ADR 0003 hinsichtlich der Gauge-Modulnamen

## Kontext

Nach der Festlegung getrennter Chartfamilien benötigt `EChartsGauge` einen
stabilen technischen Vertrag zum gemeinsamen `EChartsGateway`. Das
Generatorgerüst verwendete noch `IPSModule`, `RequireParent()` und untypisierte
Beispielmethoden. Dadurch konnte jede neue Geräteinstanz ein zusätzliches
Gateway erzeugen und es gab weder validierte Konfiguration noch ein
versioniertes Datenformat.

## Entscheidung

Beide Module verwenden `IPSModuleStrict` und vollständig typisierte öffentliche
Methoden. `EChartsGauge::GetCompatibleParents()` beschreibt ausschließlich
`EChartsGateway` mit `type: connect`. Dadurch bietet die Verwaltungskonsole
bereits vorhandene Gateway-Instanzen auch dann an, wenn andere Geräte mit ihnen
verbunden sind. Im regulären Betrieb verwenden alle ECharts-Geräte eine
gemeinsame Gateway-Instanz. Das erste Gerät kann sie neu anlegen; jedes weitere
Gerät soll das vorhandene Gateway auswählen. Die von Symcon bei `connect`
weiterhin angebotene Neuanlage wird nicht technisch verhindert, weil mehrere
Gateways zulässig bleiben und keine harte Singleton-Abhängigkeit eingeführt
wird. `RequireParent()` wird nicht mehr verwendet.

Der Symcon-Datenfluss behält seine vorhandenen GUIDs. Die Transporthülle wird
über `DataFlowHelper` aus `Symcon_ModuleHelper` validiert. Innerhalb der Hülle
verwendet SymconECharts ein eigenes Protokoll mit folgenden Pflichtfeldern:

- `ProtocolVersion`: aktuell `1`;
- `Operation`: stabiler Operationsname;
- `Payload`: operationsspezifisches Objekt.

Synchron zurückgegebene Antworten enthalten zusätzlich `Success` und entweder
`Payload` oder ein strukturiertes `Error`-Objekt. Version 1 implementiert
`current.read`: Das Gauge übermittelt eine numerische `VariableID`; das Gateway
validiert die Variable und liefert Wert, Variablentyp und Änderungszeitpunkt.

Die Zuständigkeiten sind:

- `EChartsGauge` besitzt Quellvariable, Titel, Einheit, Nachkommastellen und
  Wertebereich sowie das daraus entstehende Gauge-Datenmodell.
- `EChartsGateway` besitzt den gemeinsamen Zugriff auf aktuelle Werte.
- Künftige Archivabfragen und ihr begrenzter Cache gehören ausschließlich in
  das Gateway. Sie werden erst mit einer Chartfamilie implementiert, die
  historische Daten tatsächlich verwendet; Version 1 erfindet dafür noch
  keinen ungenutzten Vertrag.
- Renderer und Ausgabeadapter konsumieren das familienbezogene Datenmodell und
  gehören nicht zum Gateway.

`EChartsGauge::GetGaugeData()` stellt den ersten stabilen technischen Einstieg
bereit. Die Methode liefert ein JSON-Objekt mit `schemaVersion: 1`, Familie,
Quellidentität, Gauge-Konfiguration, aktuellem Wert und Zeitstempel. Sie dient
dem späteren nativen sowie dem IPSView-Adapter; ECharts-Assets und sichtbares
Rendering sind noch nicht Bestandteil dieser Entscheidung.

## Alternativen

- **`RequireParent()` beibehalten:** erzeugt bei neuen Gauge-Instanzen leicht
  weitere Gateways und ist in `IPSModuleStrict` nicht verfügbar.
- **Aktuelle Werte direkt im Gauge lesen:** wäre kurzfristig einfacher, würde
  aber die gemeinsame Datengrenze umgehen und spätere Familien unterschiedlich
  anbinden.
- **Archiv- und Cache-API vorab implementieren:** schafft ungenutzte
  Komplexität ohne einen bereits festgelegten historischen Consumer.
- **Unversioniertes JSON verwenden:** spart ein Feld, verhindert aber eine
  kontrollierte Weiterentwicklung zwischen unabhängig aktualisierten
  Instanzen.

## Folgen

Mehrere Gauge-Instanzen können bewusst dasselbe Gateway verwenden. Ungültige
Variablen, Wertebereiche, Elternverbindungen und Gateway-Antworten führen zu
eindeutigen Instanzstatuswerten. Die Quellvariable wird als Symcon-Referenz
registriert und bei Konfigurationsänderungen deterministisch ersetzt.

Zusätzliche Gateways bilden voneinander getrennte Infrastruktur und später
auch getrennte Caches. Sie sind technisch möglich, aber nicht der dokumentierte
Standard. Die Geräteformulare weisen deshalb sichtbar auf die gemeinsame
Gateway-Nutzung hin.

Das erste Datenmodell ist noch keine sichtbare Gauge. Native HTML-SDK-Ausgabe,
IPSView-Ausgabe und ECharts-Auslieferung benötigen eigene nachfolgende
Implementierung und Browser- beziehungsweise Laufzeitnachweise.

## Nachweise

- Offizielle Symcon-Dokumentation zu `IPSModuleStrict` und
  `GetCompatibleParents()`
- Protokolltests unter `tests/data_protocol.php`
- Historischer Gateway-/Gauge-Vertrag, heute geprüft unter
  `tests/gateway_gauges.php`
- Strict- und Formularverträge unter `tests/symcon_strict.php`
