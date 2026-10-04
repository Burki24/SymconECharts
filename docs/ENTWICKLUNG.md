# Entwicklung von SymconECharts

## Ziel und Abgrenzung

Symcon-Variablen und Archivdaten sollen ohne eigene JavaScript-Programmierung
als Messinstrumente und Diagramme dargestellt werden können. SymconECharts
entsteht unabhängig von JSLive; bestehende JSLive-Installationen werden nicht
verändert und JSLive ist keine Voraussetzung.

Native Symcon-Kacheln und einzelne IPSView-HTML-Widgets sind gleichwertige
Ausgabeziele. Eine Chart-Instanz soll beide Ausgabewege gleichzeitig bedienen
können. Die Anordnung mehrerer Charts übernimmt die jeweilige Visualisierung,
nicht eine eigene große Dashboard-Seite des Moduls.

## Aktueller Stand und Zielarchitektur

Die Library besitzt mit Gateway sowie Gauge Single und Gauge Multi eine erste
technische Modulstruktur. Alle drei Module verwenden `IPSModuleStrict`;
Gauge-Instanzen können vorhandene Gateways wiederverwenden. Ein versioniertes
Protokoll liefert geprüfte numerische Momentanwerte an ein minimales
Gauge-Datenmodell. Gauge Multi besitzt eine geordnete und validierte Liste aus
2 bis 16 Quellen. Gauge Single rendert sein Modell mit der lokal gebündelten
Apache-ECharts-Runtime 6.1.0 als responsive native HTML-SDK-Kachel und sendet
Wertänderungen ohne einen vollständigen Neuaufbau der Seite an den Browser.
Das Gauge-Single-Formular besitzt außerdem einen ersten Kacheldesigner. Er
wählt die layoutbezogenen Presets Basic, Simple, Progress und Speed und erzeugt
eine live aktualisierte SVG-Vorschau der noch nicht gespeicherten Einstellungen.
Unabhängig davon kann die Instanz zwischen dem automatischen Symcon-Design und
sechs lokal gebündelten offiziellen ECharts-Themes wählen. Zeigerform samt
abgesichertem pfadbasiertem SVG-Import, Skalenbogen mit Start- und
Endpositionen in 22,5-Grad-Schritten sowie sechs
Gauge-spezifische Farbrollen sind ebenfalls validiert konfigurierbar. Der Multi-Renderer,
Archivverarbeitung, IPSView-Ausgabe sowie reale
Symcon-Laufzeit- und Browsertests fehlen noch. Die Testsuite prüft zusätzlich
zu Struktur und Metadaten die Strict-Verträge, das Protokoll, die
ECharts-Integrität und den Gauge→Gateway→Visualisierungs-Datenweg unter PHP
8.5 mit Test-Doppeln.

| Bestandteil | Name | Aufgabe |
|---|---|---|
| Library | SymconECharts | Gemeinsames installierbares Paket |
| Splitter | EChartsGateway | Gemeinsame Dienste für mehrere Chart-Instanzen |
| Gerät | EChartsGaugeSingle | Ein Gauge-Chart mit genau einer Quellvariable und zwei Ausgabewegen |
| Gerät | EChartsGaugeMulti | Ein zusammengesetzter Gauge-Chart mit 2 bis 16 Quellen |

Eine eigene I/O-Instanz, ein Konfigurator und Discovery gehören nicht zum
Anfangsumfang. Datenquellen sind Teil der jeweiligen Gauge-Konfiguration und
keine eigenen Geräteinstanzen. Mehrere Gauge-Instanzen sollen einen vorhandenen
Gateway gemeinsam verwenden. Im regulären Betrieb genügt eine gemeinsame
Gateway-Instanz für sämtliche ECharts-Diagramminstanzen. Die von Symcon bei
`type: connect` weiterhin angebotene Neuanlage wird nicht technisch gesperrt;
zusätzliche Gateways bilden jedoch getrennte Infrastruktur und sind kein
Standard. Weitere Chartfamilien erhalten eigene Gerätemodule; sie werden nicht
als Modi eines universellen Widgets ergänzt.

## Festgelegte Identitäten

Diese bereits vergebenen IDs werden bei der weiteren Entwicklung beibehalten.
Modul-IDs und Datenfluss-IDs haben unterschiedliche Aufgaben.

| Identität | GUID |
|---|---|
| Library SymconECharts | `{66BE21BE-988A-10EB-1BBB-1E2F444E9F85}` |
| Modul EChartsGateway | `{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}` |
| Modul EChartsGaugeSingle | `{0CAA2780-342F-E5B9-2865-CB8DCED0BE7C}` |
| Modul EChartsGaugeMulti | `{E667D9C1-379D-44ED-A313-FF21FEFC355F}` |
| Datenfluss Gauge → Gateway | `{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}` |
| Datenfluss Gateway → Gauge | `{E4749B72-912B-E3E3-1C57-D19019FFDD84}` |

Die Funktionspräfixe sind `ECGW` für das Gateway, `ECGS` für Gauge Single und
`ECGM` für Gauge Multi. Nach der verbindlichen Präfixkonvention steht `EC` für
ECharts; die letzten zwei Buchstaben bezeichnen Aufgabe oder Modultyp. Die
Entscheidungen zur Modulstruktur und zu den GUIDs dokumentieren
[`ADR 0001`](adr/0001-chart-family-modules.md) und
[`ADR 0003`](adr/0003-single-and-multi-gauge-modules.md).
Den Parent-, Zuständigkeits- und Datenvertrag der ersten technischen Vertikale
dokumentiert [`ADR 0002`](adr/0002-gateway-gauge-contract.md).
Das persistente Multi-Quellenmodell dokumentiert
[`ADR 0004`](adr/0004-gauge-multi-source-contract.md).
Die festgeschriebene ECharts-Runtime und den ersten nativen Renderer
dokumentiert [`ADR 0005`](adr/0005-echarts-runtime-and-native-renderer.md).
Den Vertrag des ersten Kacheldesigners dokumentiert
[`ADR 0006`](adr/0006-gauge-single-tile-designer.md).
Die gemeinsame Theme-Assetbasis und die instanzbezogene Auswahl dokumentiert
[`ADR 0007`](adr/0007-echarts-theme-assets-and-selection.md).

## Daten, Konfiguration und Ausgabe

Symcon-Datenanbindung, Diagrammkonfiguration und ECharts-Darstellung werden
getrennt. Die Konfiguration der jeweiligen Chartfamilien-Instanz bleibt die
maßgebliche Quelle für ihre Variablen, Datenreihen und Auswertungsregeln.
Layout-Presets und ECharts-Themes bleiben getrennte Konfigurationsachsen. Die
Theme-Auswahl gehört zur Diagramminstanz, während die geprüften offiziellen
Theme-Assets und ihre stabilen IDs repositoryweit gemeinsam genutzt werden.

Das Gateway stellt aktuell den gemeinsamen Zugriff auf numerische Momentanwerte
bereit. Wiederverwendbare Archivabfragen und begrenzte Caches werden erst für
einen tatsächlich festgelegten historischen Anwendungsfall ergänzt. Ein
HTML-Dokument wird nicht bei jeder Messwertänderung neu erzeugt. Datenmenge,
Aktualisierungsrate und Animationen werden insbesondere für mehrere
gleichzeitig sichtbare Charts begrenzt und getestet.

Die native Kachel und IPSView erhalten getrennte Kommunikationsadapter zum
gemeinsamen Darstellungskern. Der IPSView-Datenkanal benötigt eine eigene
Zugriffsprüfung; seine Berechtigungen werden nicht einfach aus der Anmeldung
an einer anderen Oberfläche abgeleitet. Eine abgeschaltete IPSView-Ausgabe
darf den nativen Ausgabeweg nicht beeinträchtigen.

## Plattform und Modulbasis

Entwicklungsziel und deklarierte Mindestversion sind **Symcon 9.0 / PHP 8.5**.
Eine Kompatibilität zu älteren Symcon-Versionen wird nicht versprochen.

Die technische Umsetzung verwendet die moderne Modulbasis `IPSModuleStrict`
mit passenden Typdeklarationen. Die Parent-Auswahl wird über
`GetCompatibleParents()` mit `type: connect` aufgebaut. Dadurch können neue
Geräteinstanzen bestehende Gateways mit bereits verbundenen Kindern
wiederverwenden. `RequireParent()` wird nicht verwendet.

Die zugehörigen Schnittstellen sind in der
[Symcon-Moduldokumentation](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/)
und unter
[GetCompatibleParents](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/getcompatibleparents/)
beschrieben.

## Bibliotheken und gemeinsame Helper

ECharts wird ohne projektspezifische Änderungen am Bibliothekskern verwendet.
Der Gauge-spezifische Browser-Build 6.1.0 ist lokal festgeschrieben, wird aus
der offiziellen Tree-Shaking-API reproduzierbar erzeugt und mit Prüfsumme,
Originallizenz sowie NOTICE ausgeliefert. Vor der Einbettung wird er auf
Integrität geprüft. Seine Auswahl aus ECharts Core, Gauge Chart, Aria, Tooltip
und Canvas Renderer hält das vollständige HTML-Dokument unter dem
Symcon-Output-Buffer-Limit. Im Betrieb wird weder `latest` noch eine externe
CDN-Ressource geladen. Aktualisierungen werden erneut gegen APIs, Lizenz,
Lieferartefakte, Größe und die vorhandenen Renderer geprüft.

Dark, Vintage, Macarons, Infographic, Shine und Roma werden unverändert aus
demselben ECharts-6.1.0-Paket übernommen, mit eigenen SHA-256-Werten geprüft
und lokal registriert. Der gemeinsame projektspezifische Katalog liegt in
`libs/EChartsAsset.php`; die synchronisierten Helper enthalten weder
ECharts-Theme-Dateien noch Theme-IDs.

Vorhandene zentrale Helper aus `Symcon_ModuleHelper` werden wiederverwendet
und über den vorgesehenen Synchronisierungsweg eingebunden. Allgemeine
Erweiterungen gehören in das zentrale Helper-Projekt, nicht in abweichende
lokale Kopien. Synchronisierte Helper liegen ausschließlich unter
`libs/helper`; projektspezifische Verträge und Chart-Logik liegen direkt unter
`libs` beziehungsweise in den zuständigen Modulen.

Eine gemeinsame Dateiauslieferung bedeutet nicht, dass mehrere getrennte
Browserflächen eine einzige ECharts-Laufzeit teilen. Ressourcenbedarf und
Aktualisierungsverhalten werden auf den Zielgeräten geprüft.

## Entwicklungsablauf und Prüfungen

Die Entwicklung und Erprobung erfolgen auf `dev`. Geprüfte Stände gelangen
per Pull Request nach `main`; der Entwicklungsbranch wird nicht automatisch
gelöscht.

Nach einem normalen Push auf `dev` erhöht der Metadatenworkflow die gemeinsame
Library-Version im Format `Hauptversion.Nebenstand`, leitet `build` aus dem
Quellcommit ab und setzt `date` auf dessen Commit-Zeit. Der getrennte
Metadatencommit löst keine weitere Erhöhung aus. Tags, Releases und
Veröffentlichungen bleiben manuelle, ausdrücklich freizugebende Schritte.

Für die technische Projektbasis sind Struktur- und JSON-Prüfungen,
PHP-Syntaxprüfungen, Modultests und der gemeinsame StylePHP-/CI-Ablauf
eingerichtet. Änderungen an den Visualisierungen benötigen zusätzlich
Browserprüfungen in beiden Ausgabewegen. Ein PHP-Lint ersetzt keinen
Symcon-Laufzeittest und keine Visualisierungsprüfung.

Die offizielle Symcon-Stylekonfiguration wird als Git-Submodul unter `.style`
eingebunden. Sie bleibt dadurch auf einen nachvollziehbaren StylePHP-Commit
festgeschrieben und kann über den normalen Submodulablauf aktualisiert werden.

Der erste Funktionsumfang umfasst ein radiales Messinstrument in
`EChartsGaugeSingle` und zusammengesetzte Gauges auf Basis des vorhandenen
Mehrquellenmodells in `EChartsGaugeMulti`. Bei der sichtbaren Umsetzung werden
mehrere getrennte Gauge-Instanzen, beide Ausgabewege gleichzeitig,
Größenwechsel sowie abgeschaltete IPSView-Ausgabe getestet. Zeitreihen und
weitere Chartfamilien sind spätere, getrennt zu entscheidende Ausbaustufen.

## Dokumentation und Lizenzen

Die [Haupt-README](../README.md) folgt dem Aufbau der bereitgestellten
`README1.md`: Modulübersicht mit Kurzbeschreibungen und Links. Die ausführliche
Nutzungsdokumentation steht in den README-Dateien der Modulordner.
`README1.md` bleibt als unveränderte Vorlage erhalten.

Geplanter und tatsächlich vorhandener Funktionsumfang werden ausdrücklich
getrennt. Beispiel-APIs, Store-Veröffentlichungen oder Versionsfreigaben
werden nicht vor ihrer Umsetzung behauptet.

Eigene Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](../LICENSE). Fremdkomponenten behalten
ihre Originallizenzen; die konkrete Einbindung wird in
[THIRD_PARTY_NOTICES.md](../THIRD_PARTY_NOTICES.md) dokumentiert.

Required Notice: Copyright 2026 Burkhard Kneiseler. SymconECharts.
