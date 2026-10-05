# Tests

Die lokale Testsuite wird aus dem Repository-Stamm gestartet:

```text
git submodule update --init --recursive
php tests/run.php
```

Die Gauge-Layouttests benötigen zusätzlich Node.js und werden separat mit
`node tests/gauge_single_layout.js` und `node tests/gauge_multi_layout.js`
ausgeführt. Der Single-Test prüft die vergrößerte Darstellung und die Grenzen
der Zifferblattplatte in Kachel und IPSView. Der Multi-Test prüft die freigehaltene
Kachelkopfzeile sowie die Layouts beider Ausgabewege anhand gerenderter
ECharts-Optionen. Er deckt auch Ringraster und konzentrische Ringe mit 2 bzw.
16 Quellen, Wetterstations- und Tacho-Panel mit 2, 3 und 16 Quellen sowie den
Chronographen mit 2 bis 6 Quellen ab. Die
PHP-Basissuite benötigt weiterhin kein Node.js.

`tests/fixtures/tacho-browser.html` ist eine manuelle Browser-Fixierung für
die Tacho-Vorlage. Sie lädt die lokal gebündelte ECharts-Runtime und den echten
Gauge-Multi-Renderer mit drei synthetischen Quellen, ohne eine Symcon-Instanz
zu benötigen. Sie ersetzt keinen Test in der nativen Kachel oder in IPSView.

Der GitHub-Workflow `.github/workflows/tests.yml` verwendet zusätzlich
`Burki24/Symcon_ModuleCI/php-tests@v1.0.0`. Die gemeinsame Action prüft unter
PHP 8.5 alle PHP-Dateien auf Syntax, validiert die JSON-Dateien und startet
anschließend denselben lokalen Testeinstieg. Der Workflow
`.github/workflows/style.yml` verwendet
`Burki24/Symcon_ModuleCI/style@v1.0.0` für die offiziellen Symcon-Prüfungen mit
StylePHP und PHP CS Fixer.

## Enthaltene Prüfungen

- `validate_structure.php` prüft die erwarteten Projekt-, Modul-, Test- und
  Workflowdateien, die von Symcon erlaubten Verzeichnisse im Library-Stamm,
  JSON-Grundstrukturen und die Einbindung der gemeinsamen CI-Basis
  einschließlich des Style-Workflows.
- `module_contracts.php` charakterisiert die vorhandenen Library- und
  Modulidentitäten, die Symcon-9.0-Mindestversion sowie die beiden
  Datenflussrichtungen.
- `symcon_strict.php` prüft Strict-Modulbasis, typisierte öffentliche
  Verträge, wiederverwendbare Gateway-Verbindung, die minimale
  Gauge-Konfiguration und den HTML-SDK-Vertrag von Gauge Single.
- `data_protocol.php` prüft Versionierung sowie Erfolgs- und Fehlerantworten
  des internen Gateway-Protokolls.
- `gateway_gauges.php` führt den vollständigen Momentanwertabruf für Gauge
  Single und Gauge Multi mit Symcon-Test-Doppeln aus. Für Multi werden außerdem
  geordnete Quellen, Referenzwechsel, stabile Item-IDs, Beschriftungen,
  Duplikate, Wertebereiche und fehlerhafte Konfigurationen geprüft. Für Gauge
  Single werden außerdem Kacheldesigner-Presets, Theme-Auswahl, Zeigerformen
  einschließlich des abgesicherten SVG-Pfadimports,
  Skalenbögen, Farbrollen, SVG-Vorschau, HTML-Erzeugung, lokale
  ECharts-Einbettung und die Zustandsaktualisierung nach
  `VM_UPDATE` charakterisiert. Das erzeugte
  HTML-Dokument muss unter dem Symcon-Output-Buffer-Limit von 1.048.576 Byte
  bleiben.
- `echarts_assets.php` prüft Version, SHA-256-Integrität, Lizenz und NOTICE der
  lokal gebündelten Apache-ECharts-Runtime und der sechs offiziellen Themes.
  Zusätzlich werden der Gauge-spezifische Browser-Export, das gemeinsame
  512-KiB-Budget und der tolerierte Windows-Zeilenendenfall geprüft.
- `svg_path.php` prüft Rohtext-, Base64- und Data-URI-Importe sowie die
  Ablehnung nicht unterstützter oder aktiver SVG-Inhalte. Die ornamentale
  Beispielnadel unter `fixtures/gauge-pointer-ornate.svg` wird zusätzlich als
  direkt importierbare Testdatei einschließlich ihres optionalen Drehpunkts
  geprüft. Die Gauge-Integration prüft außerdem, dass die prozentuale
  Pivot-Justierung die native ECharts-Nabe sichtbar in ihrem Zeigerring
  zentriert. Kreis, Ring, ausgeblendete und eigene SVG-Naben werden über
  denselben Vorschau- und Datenmodellvertrag abgesichert; aktiver Inhalt in
  einem Naben-SVG wird abgewiesen.
  Runde und skalenbogenabhängige Zifferblattplatten werden einschließlich
  Theme- oder eigener Farben, transparenter Füllung, Rand, Größe und Schatten
  in Datenmodell, SVG-Vorschau und nativem ECharts-Renderer geprüft.
- `helper_integrity.py` stellt sicher, dass Subscription, Manifest,
  Helper-Dokumentation und alle abonnierten Datenfluss- und
  Visualisierungshelper vollständig übereinstimmen.
- `test_update_library_metadata.py` prüft Versionsfortschreibung, Build- und
  Datumsableitung sowie den Schutz vor einer Versionsrückstufung.

Die Suite belegt die PHP-seitigen Verträge und den Datenweg nur gegen lokale
Symcon-Test-Doppel. Sie ersetzt keinen Laufzeittest in Symcon und keinen
visuellen Browsertest. Insbesondere die tatsächliche native Kacheldarstellung,
Größenwechsel in den Symcon-Clients und IPSView bleiben auf der erreichbaren
Testebene offen.
