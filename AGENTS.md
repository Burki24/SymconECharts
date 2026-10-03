# Projektregeln für SymconECharts

Vor Arbeiten in diesem Repository sind zuerst die zentralen Vorgaben aus
`../SymconDevelopment/AGENTS.md` und
`../SymconDevelopment/ARCHITECTURE.md` zu lesen. Die dort referenzierten
Prinzipien und die für die konkrete Aufgabe relevanten Standards gelten auch
für SymconECharts. Projektspezifische Ziele und der vorhandene Stand stehen in
`README.md`, `docs/ENTWICKLUNG.md` und den README-Dateien der Module.

## Produktumfang und Abgrenzung

- SymconECharts ist die eigenständige Neuentwicklung für Diagramme auf Basis
  von Apache ECharts. JSLive bleibt ein getrenntes Wartungsprojekt und wird aus
  diesem Repository heraus nicht geändert.
- Zielplattform sind IP-Symcon 9.0/9.1 und PHP 8.5. Eine gesonderte
  IP-Symcon-9.0-Testinstallation ist nicht erforderlich; nicht auf der
  erreichbaren Testebene geprüfte Varianten werden sichtbar als Testlücke
  benannt.
- Der aktuelle Repository-Stand ist ein Modulgerüst. Nicht implementierte
  Funktionen, Store-Freigaben oder Laufzeitkompatibilität dürfen nicht als
  vorhanden dargestellt werden.
- Native Symcon-Kacheln und einzelne IPSView-HTML-Widgets sind vorgesehene
  Ausgabewege. IPSView-Laufzeittests sind auf der vorhandenen Testebene mangels
  Lizenz nicht möglich und bleiben ausdrücklich als Lücke dokumentiert.

## Architekturgrenzen

- Die eingecheckten Module `EChartsGateway` und `EChartsWidget`, ihre GUIDs,
  Präfixe und Datenfluss-IDs sind der vorhandene Ausgangsstand. Sie werden
  nicht beiläufig umbenannt, umgewidmet oder ersetzt.
- Noch offen sind insbesondere die Aufteilung in eine Universalinstanz oder
  getrennte Diagrammfamilien sowie Notwendigkeit und genaue Zuständigkeit eines
  Splitters. Vor einer fachlichen Implementierung ist diese Entscheidung mit
  dem Eigentümer abzustimmen und bei langfristiger Wirkung als ADR zu
  dokumentieren.
- Gauge ist der bevorzugte Pilot. Daraus folgt noch keine Entscheidung über
  Modulaufteilung, Splitter, öffentliche Properties oder weitere
  Diagrammfamilien.
- Symcon-Datenanbindung, Archivzugriff, fachliches Chart-Modell, Formatierung,
  Ausgabeadapter und ECharts-Renderer werden als getrennte Zuständigkeiten
  behandelt. Gemeinsame Abstraktionen entstehen nur für belegte gemeinsame
  Anwendungsfälle.
- Repositoryweit geteilter Code liegt unter `libs/helper`; Tests liegen unter
  `tests`. Vorhandene Funktionen aus `Symcon_ModuleHelper` werden
  wiederverwendet und nicht lokal kopiert.

## ECharts und Fremdkomponenten

- Vor der Festlegung oder Aktualisierung einer ECharts-Abhängigkeit sind die
  aktuelle stabile Version, die benötigten APIs, Lizenz und Lieferartefakte
  anhand der offiziellen Einstiege
  `https://echarts.apache.org/en/index.html` und
  `https://echarts.apache.org/en/llms.txt` zu prüfen.
- ECharts wird lokal, reproduzierbar und mit festgeschriebener Version
  ausgeliefert. Im Betrieb wird weder `latest` noch eine externe CDN-Ressource
  automatisch geladen.
- Der ECharts-Kern wird nicht projektspezifisch verändert. Herkunft, Version,
  Integrität und Lizenz der ausgelieferten Fremdkomponenten werden
  dokumentiert.

## JSLive-Export und Übernahme

- Maßgebliche Produktentscheidung ist
  `../JSLive/docs/adr/0006-maintenance-scope.md`. JSLive dient ausschließlich
  als lesende Referenz, sofern kein eigener Auftrag für dieses Repository
  erteilt wurde.
- JSLive liefert den Export bestehender Charts; SymconECharts erhält die
  passende Übernahmefunktion. Vor der Implementierung ist ein gemeinsamer,
  versionierter Austauschvertrag zu spezifizieren.
- Variablen- und Archivbezüge werden erhalten. Originalinstanzen,
  Variablenwerte und Archivdaten werden weder gelöscht noch überschrieben.
- Eine Übernahme benötigt Vorschau und ausdrückliche Bestätigung. Sie muss bei
  Wiederholung sicher bleiben, Teilfehler kontrolliert melden und das
  JSLive-Original als Vergleich und Rückfall erhalten.
- Nicht übertragbare Einstellungen, eigene Templates, Skripte sowie
  Chart.js-spezifische Erweiterungen werden sichtbar gemeldet. Skript- oder
  Templateinhalte werden nicht stillschweigend ausgeführt oder als verlustfrei
  übertragbar behandelt.
- Es wird keine pixelgleiche oder vollständige 1:1-Migration beliebiger
  Chart.js-Konfigurationen versprochen.

## Umsetzung, Prüfung und Git

- Vor Änderungen sind Arbeitsbaum, vorhandene Architektur, öffentliche
  Verträge, relevante Dokumentation, vergleichbare Implementierungen und die
  verfügbaren Tests zu prüfen. Fremde oder nicht zur Aufgabe gehörende
  Änderungen bleiben erhalten.
- Konfiguration, Import und Migration müssen deterministisch, idempotent und
  bei Teilfehlern wiederaufnehmbar sein. Veröffentlichte Properties, Idents,
  GUIDs und persistierte Formate ändern sich nur mit dokumentierter Migration.
- `ApplyChanges()` wird beim Modulupdate automatisch ausgeführt und nicht ohne
  konkreten Prüfgrund zusätzlich aufgerufen.
- Keine schreibenden Aktionen in einer Symcon-Installation ohne passenden
  Auftrag. Commit, Push, Merge, Tag, Release und Veröffentlichung führt der
  Eigentümer aus.
- Entwicklung erfolgt auf dem dauerhaften Branch `dev`; freigegebene Stände
  gelangen per Pull Request nach `main`.
- Prüfungen folgen den dokumentierten Repositorybefehlen und der gemeinsamen
  CI-Basis. Abschlussberichte nennen ausgeführte Prüfungen und verbleibende
  Testlücken getrennt; Syntax-, Style- und Strukturprüfungen ersetzen keine
  Laufzeit- oder Browserprüfung.
- Bei commitbereiten Änderungen werden Commit-Betreff und Commit-Erläuterung
  immer auf Englisch und in zwei getrennten kopierbaren Textblöcken geliefert.
