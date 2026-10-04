# SymconECharts

Folgende Module beinhaltet das SymconECharts Repository:

- __EChartsGateway__ ([Dokumentation](EChartsGateway))  
  Gemeinsame Zentrale als Splitter. Sie stellt den Chartfamilien aktuell den
  validierten Zugriff auf numerische Momentanwerte bereit. Archivabfragen,
  Zwischenspeicherung und weitere gemeinsame Dienste folgen bei belegtem Bedarf.
  Das Gateway benötigt keine eigene I/O-Instanz.

- __EChartsGaugeSingle__ ([Dokumentation](EChartsGaugeSingle))
  Gerätemodul für ein einzelnes, unabhängig konfigurierbares Gauge-Diagramm
  mit genau einer numerischen Quellvariable.
  Vorgesehen für die native Symcon-Kacheldarstellung und zusätzlich für ein
  separat platzierbares HTML-Widget in IPSView. Beide Ausgabewege sollen
  dieselbe Diagrammkonfiguration und Datenaufbereitung verwenden.

- __EChartsGaugeMulti__ ([Dokumentation](EChartsGaugeMulti))
  Gerätemodul für zusammengesetzte Gauge-Darstellungen mit 2 bis 16
  Datenquellen, etwa Multi Title, Ring oder Car. Jede Quelle besitzt eine
  eigene Beschriftung, Skala, Einheit und Formatierung.

## Entwicklungsstand

**Technische Datenbasis – noch keine sichtbaren Diagramme.** Gateway und die
Gauge-Module verwenden `IPSModuleStrict`, ein versioniertes Datenprotokoll und eine
wiederverwendbare Parent-Verbindung. Gauge Single kann eine numerische
Quellvariable konfigurieren und deren Momentanwert als validiertes
familienbezogenes Datenmodell abrufen. Gauge Multi verwaltet eine geordnete
Quellenliste und liefert alle Momentanwerte in einem versionierten Multi-Modell.
Apache ECharts, Archivverarbeitung, native Kachelausgabe und
IPSView-HTML-Ausgabe sind noch nicht implementiert.

Als erste Chartfamilie sind Single- und Multi-Gauges vorgesehen. Eine
Geräteinstanz soll jeweils einen Chart liefern; eine gemeinsame Dashboard-Seite
ist nicht vorgesehen. Weitere Chartfamilien erhalten eigene Gerätemodule.

## Entwicklung

Die Entwicklung erfolgt auf `dev`. Geprüfte Stände gelangen später per Pull
Request nach `main`; `dev` bleibt erhalten. Projektziele, Architektur und
Entwicklungsgrundsätze stehen in [Entwicklung](docs/ENTWICKLUNG.md).

Nach normalen Pushes auf `dev` pflegt ein Workflow Version, Build und Datum in
`library.json` und legt die Metadaten als getrennten Bot-Commit ab. Tags und
Releases werden dadurch nicht automatisch erzeugt.

Die Basistests werden lokal mit `php tests/run.php` ausgeführt. Der
Tests-Workflow prüft dieselbe Suite unter PHP 8.5 und ergänzt PHP-Syntax- sowie
JSON-Validierung über die gemeinsame `Symcon_ModuleCI`-Basis. Ein eigener
Style-Workflow führt zusätzlich die offiziellen Symcon-Prüfungen für PHP und
JSON aus.

## Lizenz

Die eigenen Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](LICENSE).
SPDX-Identifier: `PolyForm-Noncommercial-1.0.0`.

Required Notice: Copyright 2026 Burkhard Kneiseler. SymconECharts.

Fremdkomponenten behalten ihre Originallizenzen. Hinweise zur vorgesehenen
Einbindung von Apache ECharts stehen in
[THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

SymconECharts ist kein offizielles Projekt der Apache Software Foundation.
