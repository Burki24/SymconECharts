# Tests

Die lokale Testsuite wird aus dem Repository-Stamm gestartet:

```text
git submodule update --init --recursive
php tests/run.php
```

Der GitHub-Workflow `.github/workflows/tests.yml` verwendet zusätzlich
`Burki24/Symcon_ModuleCI/php-tests@v1.0.0`. Die gemeinsame Action prüft unter
PHP 8.5 alle PHP-Dateien auf Syntax, validiert die JSON-Dateien und startet
anschließend denselben lokalen Testeinstieg. Der Workflow
`.github/workflows/style.yml` verwendet
`Burki24/Symcon_ModuleCI/style@v1.0.0` für die offiziellen Symcon-Prüfungen mit
StylePHP und PHP CS Fixer.

## Enthaltene Prüfungen

- `validate_structure.php` prüft die erwarteten Projekt-, Modul-, Test- und
  Workflowdateien, JSON-Grundstrukturen und die Einbindung der gemeinsamen
  CI-Basis einschließlich des Style-Workflows.
- `module_contracts.php` charakterisiert die vorhandenen Library- und
  Modulidentitäten, die Symcon-9.0-Mindestversion sowie die beiden
  Datenflussrichtungen.
- `symcon_strict.php` prüft Strict-Modulbasis, typisierte öffentliche
  Verträge, wiederverwendbare Gateway-Verbindung und die minimale
  Gauge-Konfiguration.
- `data_protocol.php` prüft Versionierung sowie Erfolgs- und Fehlerantworten
  des internen Gateway-Protokolls.
- `gateway_gauge.php` führt den ersten vollständigen Momentanwertabruf mit
  Symcon-Test-Doppeln aus und prüft Referenzen, Status sowie Fehlerfälle.
- `helper_integrity.py` stellt sicher, dass Subscription, Manifest,
  Helper-Dokumentation und der zentrale `DataFlowHelper` vollständig
  übereinstimmen.
- `test_update_library_metadata.py` prüft Versionsfortschreibung, Build- und
  Datumsableitung sowie den Schutz vor einer Versionsrückstufung.

Die Suite belegt die PHP-seitigen Verträge und den Datenweg nur gegen lokale
Symcon-Test-Doppel. Sie ersetzt keinen Laufzeittest in Symcon und belegt noch
keine Diagramm- oder Browserdarstellung. Renderer- und Browsertests werden mit
der jeweiligen Implementierung ergänzt.
