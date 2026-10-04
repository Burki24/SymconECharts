# ADR 0005: ECharts-Runtime und ersten nativen Renderer festlegen

- Status: Angenommen
- Datum: 2026-10-04
- Entscheider: Burki24
- Ergänzt: ADR 0002 und ADR 0003
- Ergänzt durch: ADR 0006 hinsichtlich des Gauge-Single-Kacheldesigners
- Ersetzt durch: –

## Kontext

Die Gauge→Gateway-Datenbasis liefert bereits ein versioniertes fachliches
Modell, erzeugte aber noch keine sichtbare Darstellung. Für den ersten
Renderer müssen Version und Herkunft von Apache ECharts, die lokale
Auslieferung, die Trennung zwischen gemeinsamem Framework und
familienbezogener Darstellung sowie der Aktualisierungsweg der nativen
Symcon-Kachel verbindlich festgelegt werden.

SymconECharts unterstützt Symcon 9.0 und 9.1. Deshalb darf der erste native
Ausgabeweg keine ausschließlich in 9.1 verfügbare Darstellungsart voraussetzen.
Eine externe CDN-Abhängigkeit wäre außerdem weder reproduzierbar noch für eine
lokale Symcon-Installation verlässlich verfügbar.

## Entscheidung

Apache ECharts wird in Version **6.1.0** aus dem offiziellen npm-Paket
`echarts@6.1.0` eingebunden. Da Symcon die Ausgabe von
`GetVisualizationTile()` auf 1.048.576 Byte begrenzt und der vollständige
minimierte Browser-Build dieses Budget bereits allein überschreitet, wird die
offizielle Tree-Shaking-Schnittstelle verwendet. Der Gauge-spezifische Build
enthält ECharts Core, Gauge Chart, Aria, Tooltip und Canvas Renderer. Er wird
mit den unter `.tools/echarts-runtime` festgeschriebenen Abhängigkeiten
reproduzierbar erzeugt.

Der Build liegt als `echarts.gauge.min.js` unter `libs/echarts/6.1.0`, ist
473.672 Byte groß, wird nicht von einem CDN geladen und ist über den
SHA-256-Wert
`0eef7a38f5bd44691e0d629756166ba0a7c3024a781746c1acd58d8d6b0a3f8b`
festgeschrieben. `LICENSE.txt` und `NOTICE.txt` aus demselben ECharts-Paket
werden mitgeliefert. Herkunft, Buildweg und Prüfsumme stehen zusätzlich in
`THIRD_PARTY_NOTICES.md`.

`libs/EChartsAsset.php` ist der projektspezifische Loader. Er prüft die
Prüfsumme beim Laden und gibt ausschließlich den festgeschriebenen lokalen
Build zurück. `.gitattributes` deaktiviert die Textkonvertierung für die
Fremdartefakte. Falls ein bestehender Windows-Modulcheckout die 45 originalen
LF-Zeilenenden bereits in CRLF umgewandelt hat, normalisiert der Loader sie vor
der Prüfung und Einbettung zurück. Jede darüber hinausgehende Byteänderung
schlägt weiterhin fehl. Der ECharts-Kern wird nicht verändert.

`EChartsGaugeSingle` bildet die erste sichtbare vertikale Umsetzung:

- `SetVisualizationType(1)` aktiviert die mit Symcon 9.0 kompatible
  HTML-SDK-Kachel;
- `GetVisualizationTile()` erzeugt das vollständige HTML-Dokument;
- der initiale Zustand und spätere Zustände verwenden denselben
  versionierten Visualisierungsvertrag;
- Änderungen der Quellvariable werden über `VM_UPDATE` empfangen und mit
  `UpdateVisualizationValue()` an `handleMessage()` im Browser übertragen;
- der Renderer verwendet zunächst eine responsive Basic Gauge;
- Farben stammen aus den gemeinsamen Symcon-Design-Tokens, die konkrete
  ECharts-Option bleibt im Gauge-Single-Modul;
- der Canvas-Renderer, `ResizeObserver`, reduzierte Animation bei
  `prefers-reduced-motion` und ein zugänglicher Beschriftungstext bilden die
  erste technische Basis.

Die synchronisierten zentralen Helper `IPSViewHTMLPageHelper`,
`VisualizationAssetHelper`, `VisualizationThemeHelper` und
`ResponsiveVisualizationHelper` übernehmen ihre allgemeinen Zuständigkeiten.
HTML-Struktur, CSS und JavaScript des konkreten Gauge-Renderers liegen unter
`EChartsGaugeSingle/visualization`. Die ECharts-Runtime und ihr
projektspezifischer Integritätsloader liegen direkt unter `libs`, nicht unter
`libs/helper`.

## Abgrenzung

Diese Entscheidung selbst umfasst noch keine Preset-Auswahl; der später
ergänzte Gauge-Single-Kacheldesigner ist in
[`ADR 0006`](0006-gauge-single-tile-designer.md) festgelegt. Eine
Multi-Gauge-Darstellung und eine IPSView-Ausgabevariable fehlen weiterhin. Das gemeinsame
HTML-Seitenmodell ist für IPSView wiederverwendbar; der abgesicherte
IPSView-Datenkanal und dessen Laufzeitprüfung bleiben eine eigene Ausbaustufe.

ECharts-Themes sind keine zentralen ModuleHelper. Später ausgewählte Themes
werden als versionierte Projektressourcen geführt und von den jeweiligen
Chartfamilien bewusst angewendet. Der erste Renderer verwendet stattdessen die
Symcon-Farbtokens.

## Folgen

Gauge Single besitzt eine eigenständige native Kachel und aktualisiert den
Messwert, ohne das vollständige HTML-Dokument neu zu erzeugen. Das Gateway
bleibt frei von Gauge-spezifischen ECharts-Optionen. Weitere Single-Presets
können auf demselben Zustands- und Aktualisierungsvertrag aufbauen;
Gauge Multi erhält einen eigenen Renderer auf Basis seines Mehrquellenmodells.

Der PHP-Test prüft Assets, Integrität, HTML-Erzeugung und Zustandsupdates gegen
Symcon-Test-Doppel. Eine reale Symcon- und Browserprüfung ist dadurch nicht
ersetzt. IPSView bleibt mangels verfügbarer Lizenz eine dokumentierte
Laufzeittestlücke.

## Nachweise

- Asset- und Integritätsprüfung unter `tests/echarts_assets.php`
- HTML-SDK- und Update-Vertrag unter `tests/gateway_gauges.php`
- Strict- und Strukturverträge unter `tests/symcon_strict.php` und
  `tests/validate_structure.php`
- Herkunft und Lizenz unter `THIRD_PARTY_NOTICES.md`
