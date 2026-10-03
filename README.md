# SymconECharts

Folgende Module beinhaltet das SymconECharts Repository:

- __EChartsGateway__ ([Dokumentation](EChartsGateway))  
  Gemeinsame Zentrale als Splitter. Vorgesehen für Datenbereitstellung,
  Archivabfragen, Zwischenspeicherung und die technische Anbindung der einzelnen
  Chart-Instanzen. Das Gateway benötigt keine eigene I/O-Instanz.

- __EChartsWidget__ ([Dokumentation](EChartsWidget))  
  Gerätemodul für ein einzelnes, unabhängig konfigurierbares Diagramm.
  Vorgesehen für die native Symcon-Kacheldarstellung und zusätzlich für ein
  separat platzierbares HTML-Widget in IPSView. Beide Ausgabewege sollen dieselbe
  Diagrammkonfiguration und Datenaufbereitung verwenden.

## Entwicklungsstand

**Grundgerüst – noch keine nutzbaren Diagramme.** Die Library und beide
Modulgerüste sind angelegt. Apache ECharts, Daten- und Archivverarbeitung,
native Kachelausgabe und IPSView-HTML-Ausgabe sind noch nicht implementiert.

Als erste Diagrammtypen sind ein radiales Messinstrument und ein Liniendiagramm
mit mehreren Datenreihen vorgesehen. Eine Widget-Instanz soll jeweils einen
Chart liefern; eine gemeinsame Dashboard-Seite ist nicht vorgesehen.

## Entwicklung

Die Entwicklung erfolgt auf `dev`. Geprüfte Stände gelangen später per Pull
Request nach `main`; `dev` bleibt erhalten. Projektziele, Architektur und
Entwicklungsgrundsätze stehen in [Entwicklung](docs/ENTWICKLUNG.md).

Nach normalen Pushes auf `dev` pflegt ein Workflow Version, Build und Datum in
`library.json` und legt die Metadaten als getrennten Bot-Commit ab. Tags und
Releases werden dadurch nicht automatisch erzeugt.

Die Basistests werden lokal mit `php tests/run.php` ausgeführt. Der
Tests-Workflow prüft dieselbe Suite unter PHP 8.5 und ergänzt PHP-Syntax- sowie
JSON-Validierung über die gemeinsame `Symcon_ModuleCI`-Basis.

## Lizenz

Die eigenen Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](LICENSE).
SPDX-Identifier: `PolyForm-Noncommercial-1.0.0`.

Required Notice: Copyright 2026 Burkhard Kneiseler. SymconECharts.

Fremdkomponenten behalten ihre Originallizenzen. Hinweise zur vorgesehenen
Einbindung von Apache ECharts stehen in
[THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

SymconECharts ist kein offizielles Projekt der Apache Software Foundation.
