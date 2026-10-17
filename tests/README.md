# Tests

Die Symcon-Icon-Erweiterung wird mit `php tests/pictorial_bar_icons.php`
gegen synthetische Light-/Brands-/Kit-Tupel, Priorität, mehrere Teilpfade,
ungültige Namen, Nicht-Ausführung von JavaScript und Ressourcenlimits geprüft.
Der Integrationstest verwendet den unveränderten Produktionsleser gegen eine
synthetische installierte `console/icons.js`: native Picker beider Designer,
Entwurfsänderungen ohne Persistenz, unabhängige Pfade, Kopie, Fehlererholung
und HTML-Ausgabegrenzen bei 16 Quellen. Der echte ECharts-Renderer prüft
Icons in beiden Ausrichtungen, allen drei Farbmodi und Einzel-/Wiederholung.
SVG-Pfadtransformationen und die Lastbegrenzung komplexer Formen sind abgedeckt.
Die Browserfixture ergänzt `?icon=cell`; `/preview.svg` liefert die tatsächliche
PHP-Vorschau mit rein synthetischen Beispielen (über die lokale Integrationssuite).
Am 17.10.2026 wurden hohe Kachel, breite IPSView-Ansicht sowie horizontale und
vertikale SVG-Vorschau lokal visuell geprüft. Ausgewählte Light-/Brands-Pfade
der installierten Symcon-Bibliothek wurden zusätzlich rein lesend aufgelöst.
Native Iconauswahl und neue Live-Ausgaben nach Modulupdate sowie andere
Installationslayouts/Versionen bleiben ausdrücklich Testlücken.

PictorialBar: `pictorial_bar_integration.php` wird in der Gateway-Suite
ausgeführt und prüft Quellen, Nullwerte, Einheiten, ID-Identität, negative
Werte, Abonnements, Aktualisierung, Fehlererholung, Vorschauen, Designs und
beide Ausgaben. `node tests/pictorial_bar_layout.js` prüft Optionen,
Originalwert-Erhaltung, Clipping und alle fünf Formen mit der echten Engine
in beiden Ausrichtungen. Die Funnel-Suite sichert den gemeinsam verwendeten
Quellenvertrag weiter ab. Lokale Browserfixture:
`node tests/pictorial_bar_browser.mjs` (127.0.0.1:8801), ausschließlich
synthetische Werte. Live-Kachel und IPSView sind nach Modulupdate separat offen.

Die PictorialBar-Designerweiterung prüft kontinuierliche RGB-Interpolation an
den Rand-/Zwischenwerten, unabhängige Farbbereiche, Nachkommastellen,
Originalwerte oberhalb des Zeichenmaximums, Whitelists und Grenzen beider
Designer. Neue Optionen, Kopieren/Vererbung, gültiges und schreibfreies SVG
sowie deutsche Formulare sind abgesichert. Die echte ECharts-Engine prüft
die vier Wertepositionen in beiden Ausrichtungen und Symbolmodi.
Die Browserfixture `?design=extended` zeigt die wertabhängigen Farben und
neuen Text-/Raster-/Schattenstile, `?symbols=single` einzelne Formen und
`?theme=light` einen hellen Hintergrund. Diese lokale Prüfung ersetzt keine
Live-Abnahme der Erweiterung nach Modulupdate.

Der zusätzliche räumliche Verlauf wird für 0–100 %, 0–255 LQI, nichtnullige
Minima, automatische Zeichenskalen und optisch begrenzte Werte geprüft.
Alle fünf Formen, Einzel-/Wiederholung und beide Ausrichtungen laufen gegen
die echte Engine. Dabei werden auch die tatsächlichen Texttransformationen
am Messwertende statt an den Compound-Pfadgrenzen geprüft. Der intern
erzeugte Pfad bleibt bei sehr breiten Ansichten auf 128 Teilformen begrenzt.
SVG-Vorschau, unabhängige Modi, Kopieren und unveränderte Entwurfs-Properties
sind im Integrationstest abgedeckt. Browserfixture: `?color=gradient`,
optional `&range=lqi`, `&symbols=single` oder `&theme=light`. Dunkle hohe und
breite Wiederholungsansichten sowie helle LQI-Einzelformen wurden am
17.10.2026 lokal visuell geprüft. Die neue Live-Kachel-/IPSView-Abnahme nach
Modulupdate bleibt offen.

Candlestick: `php tests/candlestick_data.php` prüft abgeschlossene Stunden/
Tage, Sommerzeit, erste/letzte Rohpunkte, Extrema, Lücken, Einzelwerte,
Trunkierung und Abfragelimits. `candlestick_integration.php` wird im
Gateway-Suite-Kontext ausgeführt und prüft Quellen, Cache, Designs,
Vorschauen, Fehler und beide begrenzten HTML-Ausgaben.
`node tests/candlestick_layout.js` prüft Optionen und echte ECharts-Geometrie
für beide Ausrichtungen. Die installierte Kachel und das IPSView-Widget
bleiben nach dem Modulupdate separat zu prüfen.
Für synthetische lokale Browserdaten kann
`node tests/candlestick_browser.mjs` gestartet werden (127.0.0.1:8800).
Es werden keine echten Symcon-Daten gelesen oder geschrieben.

Die Candlestick-Designprüfung umfasst außerdem alle 16 ergänzten Felder,
ungespeicherte Vorschauen, beide Ausgaben, Kopieren/Vererbung, Grenzen und
Schrift-Whitelist. Die echte Engine prüft hohle Kerzen, getrennte Rahmen,
Hover-Zustände, feste Breite, umgekehrte Zeitachse und Nullpunkteinbindung
sowie enge Layouts. Browserfixture `?design=extended` verwendet die neuen
Einstellungen, `?theme=light` ein helles Layout. Die Archivtests bleiben unverändert.

Boxplot wird durch `php tests/boxplot_statistics.php`,
`php tests/gateway_gauges.php` (einschließlich `boxplot_integration.php`)
und `node tests/boxplot_layout.js` geprüft: R7-Quartile, endliche Extremwerte,
abgeschlossene Stunden, Lücken und leere Archive, einzelne Stichproben,
Einheiten-/Zähler-Abweisung, strikte Gateway-Antworten, unabhängige Designer,
entkoppelte Beispielvorschauen, Ausgabegrenzen und echte ECharts-Geometrie.
Die lokale synthetische Browserfixture startet mit `node tests/boxplot_browser.mjs`.
Sie ersetzt keine Live-Prüfung in Symcon oder IPSView.

Sankey wird durch `php tests/sankey_model.php` und
`node tests/sankey_layout.js` geprüft. Abgedeckt sind manuelle Knotenschlüssel,
aktuelle Verbindungsgewichte, Zyklen, Null- und Negativwerte sowie das Layout
mit der echten ECharts-Engine. Die Gateway-Suite prüft zusätzlich
Quellenabonnements, Wertänderungen, Formularauswahl und getrennte Ausgabewege.
Die Designer-Tests prüfen zusätzlich individuelle Farben nach Knotenschlüssel,
Rahmen und Rundungen, Beschriftungs- und Hover-Optionen, Grenzen beider Designs,
Kopieren, Draft-Vorschau und deren unveränderte gespeicherte Konfiguration.
Regressionsfälle mit einer Quelle und 24 ungleichen Verbrauchern sichern
vollständige Vorschauen, aktuelle Größenverhältnisse, geschlossene Flussbänder
innerhalb der Zeichenfläche sowie Fehler-/Nullzustände ohne Konfigurations-
oder Statusänderung ab. ECharts-Engine-Tests prüfen begrenzte und kollisionsfreie
Knotenlabels in beiden Ausrichtungen auch ohne aktiviertes `HideOverlappingLabels`.
Der Anwender hat am 16.10.2026 auch die ergänzten Designeroptionen und die
korrigierte Vorschau/Beschriftung in Symcon-Kachel und IPSView live bestätigt.
Dieser Nachweis gilt für die vorhandene Testinstallation; andere Plattform-
und Symcon-Versionsvarianten sind dadurch nicht zusätzlich geprüft.

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

`node tests/echarts_design.js` prüft die gemeinsame Deckkraftumrechnung und
die optionale Balkenkontur;
`node tests/time_series_layout.js` prüft unter anderem Flächen und
Annotationen. Beide laufen auch im vollständigen Qualitätslauf.
`node tests/pie_layout.js` prüft Kreis, Donut und Nightingale-Rose,
ID-getrennte gleichnamige Quellen, Textsicherheit, Animation und Nullsummen.
Bei installierter festgeschriebener ECharts-Buildabhängigkeit prüft es die
Sektorgeometrien zusätzlich gegen die echte ECharts-Engine im SVG-SSR-Modus.
`php tests/heatmap_aggregation.php` prüft Wochentag-/Stundenbildung,
fehlende und gemessene Nullwerte sowie beide Sommerzeitwechsel.
`node tests/heatmap_layout.js` prüft ECharts-Optionen, responsive
Zellenbeschriftungen und den Erhalt der letzten Grafik bei Lesefehlern.

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

## Funnel

Die Designer-Erweiterung prüft optische Größenbegrenzung mit unveränderten
Nullwerten, Rand-/Führungslinien, Schatten, Schriftfamilie, Beschriftungsseite,
Überlappungsschalter und Hover-/Blur-Einstellungen. Modell und unabhängige
IPSView-Ausgabe, Kopie, deutsche Formulare, ungültige Bereiche/Enums und
invertierte Größen werden abgesichert. Neue PHP-SVG-Punkte werden in beiden
Ausrichtungen und drei Reihenfolgen mit dem echten ECharts-Renderer verglichen.
Die Browserfixture ergänzt `design=polished` für lokale Stilprüfungen
mit Linien, Schatten, Außenbeschriftung und Schriftfamilie.

`funnel_integration.php` ist in `gateway_gauges.php` eingebunden und prüft
aktuelle Werte über den echten Gateway mit Kernel-Doppeln, ID-Identität,
gleiche Einheiten, Null-/Negativwerte, Quellenentfernung, VM_UPDATE, Fehler-
wiederholung, unabhängige Designer, Kopie, deutsche Beschriftungen und das
1.048.576-Byte-Limit bei 16 Quellen. Vorschauen lesen nur begrenzt aktuelle
Quellen und verändern weder Status noch gespeicherte Konfiguration.

`node tests/funnel_layout.js` prüft beide Ausrichtungen, Querausrichtung,
stabile Sortierung, Farben, explizite Null-Styles, sichere Tooltips, Leerzustand
und Transientfehler. Die echte gepinnte ECharts-Engine prüft die Geometrie bei
100/350/900 Pixel Breite; PHP-Vorschaupolygone werden direkt mit den
ECharts-Punkten bei gleicher Zeichenfläche verglichen.

`node tests/funnel_browser.mjs` bietet eine ausschließlich lokale synthetische
Canvas-Fixture unter `http://127.0.0.1:8799`. Hohe Kachel und breite IPSView-
Darstellung wurden am 17.10.2026 visuell geprüft. `/chart?mode=symcon&design=extended`
zeigt 16 Stufen mit großen Schriften/Abständen; `/chart?mode=ipsview&theme=light`
prüft den hellen Hintergrund. Die reale neue MCP-Kachelvorschau und Live-Tests
in Symcon beziehungsweise IPSView bleiben nach Installation erforderlich.

## Calendar

`calendar_data.php` prüft Jahresgrenzen, Schaltjahre, Zeitumstellungen,
abgeschlossene Tage und die Ablehnung ungültiger Tagesdaten. Die im
Tagesverlauf beginnende Aufzeichnung wird als gültiges Tagesaggregat
beibehalten; doppelte lokale Datumswerte bleiben unzulässig. Die im
Gateway-Integrationstest eingebundene `calendar_integration.php` prüft den
realen Gateway-Datenweg mit Kernel-Doppeln, negative/Nullwerte, Lücken,
Zählerablehnung, schreibfreie Vorschauen, Designkopie, getrennte Ausgabewege,
WebContent und das 1-MB-Limit. `calendar_layout.js` prüft Ausrichtung,
Kopfbereich, Titel, Farben, Labelanpassung, echte ECharts-Geometrie und die
lesende Jahresnavigation.
Die Designerweiterung sichert feste Farbgrenzen, umgekehrte Farben,
Monatstrennlinien, Zellstile, Positions-/Formatoptionen und alle vier
Jahreszahl-Positionen mit dem realen ECharts-Renderer ab. Die Modultests
prüfen unabhängige Vorschauen, Float-Grenzen beim Kopieren und die
Validierung beider Designer.

Mit `node tests/calendar_browser.mjs` startet eine rein lokale Fixture unter
`http://127.0.0.1:8796` mit synthetischen Jahresdaten und der ausgelieferten
Calendar-Runtime. Am 16.10.2026 wurden hohe native und breite IPSView-Layouts,
dunkler Hintergrund, Titel/Kopfbereich, Farbskala und Jahresnavigation im
Browser geprüft. Das ersetzt keinen Live-Test in Symcon oder IPSView.

Die Suite belegt die PHP-seitigen Verträge und den Datenweg nur gegen lokale
Symcon-Test-Doppel. Sie ersetzt keinen Laufzeittest in Symcon und keinen
visuellen Browsertest. Insbesondere die tatsächliche native Kacheldarstellung,
Größenwechsel in den Symcon-Clients und IPSView bleiben auf der erreichbaren
Testebene offen.
