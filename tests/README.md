# Tests

Die lokale Testsuite wird aus dem Repository-Stamm gestartet:

```text
php tests/run.php
```

Der GitHub-Workflow `.github/workflows/tests.yml` verwendet zusätzlich
`Burki24/Symcon_ModuleCI/php-tests@v1.0.0`. Die gemeinsame Action prüft unter
PHP 8.5 alle PHP-Dateien auf Syntax, validiert die JSON-Dateien und startet
anschließend denselben lokalen Testeinstieg.

## Enthaltene Prüfungen

- `validate_structure.php` prüft die erwarteten Projekt-, Modul-, Test- und
  Workflowdateien, JSON-Grundstrukturen und die Einbindung der gemeinsamen
  CI-Basis.
- `module_contracts.php` charakterisiert die vorhandenen Library- und
  Modulidentitäten sowie die beiden Datenflussrichtungen des aktuellen
  Gerüsts.
- `test_update_library_metadata.py` prüft Versionsfortschreibung, Build- und
  Datumsableitung sowie den Schutz vor einer Versionsrückstufung.

Die Suite belegt noch keine Symcon-Lauffähigkeit, Diagrammfunktion oder
Browserdarstellung. Fachliche Modul-, Renderer- und Browsertests werden mit
der jeweiligen Implementierung ergänzt.
