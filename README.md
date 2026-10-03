# SymconECharts

Folgende Module beinhaltet das SymconECharts Repository:

- __EChartsGateway__ ([Dokumentation](EChartsGateway))  
  Gemeinsame Zentrale als Splitter. Sie stellt den Chartfamilien aktuell den
  validierten Zugriff auf numerische Momentanwerte bereit. Archivabfragen,
  Zwischenspeicherung und weitere gemeinsame Dienste folgen bei belegtem Bedarf.
  Das Gateway benötigt keine eigene I/O-Instanz.

- __EChartsGauge__ ([Dokumentation](EChartsGauge))
  Gerätemodul für ein einzelnes, unabhängig konfigurierbares Gauge-Diagramm.
  Vorgesehen für die native Symcon-Kacheldarstellung und zusätzlich für ein
  separat platzierbares HTML-Widget in IPSView. Beide Ausgabewege sollen
  dieselbe Diagrammkonfiguration und Datenaufbereitung verwenden.

## Entwicklungsstand

**Technische Datenbasis – noch keine sichtbaren Diagramme.** Gateway und Gauge
verwenden `IPSModuleStrict`, ein versioniertes Datenprotokoll und eine
wiederverwendbare Parent-Verbindung. Eine Gauge-Instanz kann eine numerische
Quellvariable konfigurieren und deren Momentanwert als validiertes
familienbezogenes Datenmodell abrufen. Apache ECharts, Archivverarbeitung,
native Kachelausgabe und IPSView-HTML-Ausgabe sind noch nicht implementiert.

Als erste Chartfamilie ist ein radiales Messinstrument vorgesehen. Eine
Gauge-Instanz soll jeweils einen Chart liefern; eine gemeinsame Dashboard-Seite
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
