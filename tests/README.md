# Tests

Die lokale Testsuite wird aus dem Repository-Stamm gestartet:

```text
git submodule update --init --recursive
php tests/run.php
```

Für den vollständigen lokalen Qualitätslauf einschließlich automatischer,
rein mechanischer PHP- und JSON-Formatkorrekturen wird verwendet:

```text
php tests/quality.php --fix
```

Ohne `--fix` arbeitet der Qualitätslauf schreibgeschützt. Beide Modi werten
jeden Teilprozess einzeln aus, damit ein später erfolgreicher Befehl keinen
vorherigen Fehlerstatus verdeckt. Vor einer JSON-Korrektur wird die Syntax
aller versionierten JSON-Dateien separat validiert; ungültiges JSON wird nicht
automatisch überschrieben. Zusätzlich zur Basissuite laufen PHP-Lint,
PHP-CS-Fixer, JSON-Style, `git diff --check` und alle Gauge-Layouttests.

Der optionale versionierte Pre-Push-Hook wird einmalig mit
`git config core.hooksPath .githooks` aktiviert. Wenn sein Qualitätslauf eine
Formatkorrektur erzeugt, wird der Push gestoppt, bis die Korrektur committet
wurde.

Die Gauge-Layouttests benötigen zusätzlich Node.js und werden separat mit
`node tests/gauge_single_layout.js`, `node tests/gauge_multi_layout.js`,
`node tests/gauge_tacho_layout.js` und `node tests/gauge_chronograph_layout.js`
ausgeführt. Der Single-Test prüft die vergrößerte Darstellung und die Grenzen
der Zifferblattplatte in Kachel und IPSView. Der Multi-Test prüft die freigehaltene
Kachelkopfzeile sowie die Layouts beider Ausgabewege anhand gerenderter
ECharts-Optionen. Er deckt auch Ringraster und konzentrische Ringe mit 2 bzw.
16 Quellen und das Wetterstationspanel ab. Eigene Renderer-Suiten prüfen die
Tacho- und Chronograph-Module einschließlich quellbezogener Designwerte und
SVG-Verarbeitung für ihre maximal fünf Quellen. Der Tacho-Test prüft außerdem,
dass breite Kacheln mit vier oder fünf Quellen alle Nebeninstrumente gleich
groß, überlappungsfrei und innerhalb der verfügbaren Fläche anordnen. Die
Die übrigen PHP-Vertragstests benötigen kein Node.js; der vollständige
Qualitätslauf führt zusätzlich die Renderer-Tests mit Node.js aus.

`node tests/echarts_design.js` prüft die gemeinsame Deckkraftumrechnung;
`node tests/time_series_layout.js` prüft unter anderem Flächen und
Annotationen. Beide laufen auch im vollständigen Qualitätslauf.

`tests/fixtures/tacho-browser.html` und
`tests/fixtures/chronograph-browser.html` sind manuelle Browser-Fixierungen
für die Tacho- und Chronograph-Vorlagen. Sie laden die lokal gebündelte
ECharts-Runtime und den jeweiligen echten Renderer mit drei synthetischen
Quellen, ohne eine Symcon-Instanz zu benötigen. Sie ersetzen keinen Test in
der nativen Kachel oder in IPSView.

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
  bleiben. Für Gauge Multi werden außerdem die gemeinsame Zeiger-, Naben-,
  Skalen-, Farb- und Plattengestaltung, deren Validierung sowie getrennte
  Kachel- und IPSView-Einstellungen geprüft. Der JavaScript-Layouttest sichert
  die Anwendung dieser Designschicht auf Raster-, Ring- und Chronographenserie
  einschließlich der Zeichenreihenfolge ab. Die Formularintegration prüft,
  dass ungespeicherte Änderungen beide SVG-Vorschauen sofort und bei
  unabhängigem IPSView-Design getrennt aktualisieren.
- Für TimeSeries prüft derselbe Integrationstest zusätzlich die optionale
  WebContent-Variable, die Vererbung des Kacheldesigns, ein unabhängiges
  IPSView-Theme und -Design sowie die sofortige Aktualisierung beider
  Formularvorschauen. Quellenbezogene Referenzlinien und Wertebereiche werden
  einschließlich Normalisierung, ungültiger Bereichsgrenzen, SVG-Vorschau und
  ECharts-Optionen geprüft.
- Für Category Bar prüft der Integrationstest den aktuellen Gateway-Datenweg,
  die gemeinsame Einheit, vollständige Gruppierungs-/Stapelmatrizen,
  getrennte Kachel-/IPSView-Designs und Live-Updates.
  `bar_category_layout.js` sichert Orientierung, Sortierung, einfache,
  gruppierte und gestapelte Reihen, Farben, Balkenbreite, Beschriftung und
  abgerundete Balken im Renderer ab.
- Für Historical Bar prüft der Integrationstest den Archivdatenweg mit
  automatischer Verdichtung und ausdrücklich unveränderten Rohwerten sowie
  die Ein-Quellen-Validierung. `bar_history_layout.js` sichert Zeitachse,
  Werteformatierung, Raster, Balkenform, Farbe und Kürzungswarnung im nativen
  Renderer ab.
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
- `gauge_design.php` prüft den ausschließlich für ECharts bestimmten,
  gemeinsamen Gauge-Designvertrag für Farben, SVG-Zeiger, Ankerpunkte und
  bereinigte SVG-Zifferblatthintergründe.
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
