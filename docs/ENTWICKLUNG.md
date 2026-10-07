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

Die Library besitzt mit Gateway, Gauge Single, Gauge Multi, Gauge Tacho,
Gauge Chronograph, Time Series und Bar Category eine technische Modulstruktur. Alle sieben Module
verwenden `IPSModuleStrict`;
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
Gauge-spezifische Farbrollen sind ebenfalls validiert konfigurierbar. Eine
optionale native ECharts-Graphic bildet eine runde oder dem Skalenbogen
folgende Zifferblattplatte mit eigener Gestaltung einschließlich linearer und
radialer Farbverläufe sowie eines bereinigten, auf die Plattenform
zugeschnittenen SVG-Hintergrunds. Gauge Single unterstützt außerdem validierte farbige Wertebereiche,
konfigurierbare Skalenunterteilungen und -abstände, beide Drehrichtungen,
responsive Positionsversätze, elementweise Sichtbarkeit sowie einen
gestaltbaren Wertekasten und optionale Schatteneffekte. Gauge Multi besitzt
mit Multi Title, Ringraster, konzentrischen Ringen und einer Wetterstations-
Vorlage responsive native Kacheln für 2 bis 16 Quellen. Seine gemeinsame
Designschicht steuert Zeiger, Naben, Skalenunterteilungen, Farbrollen und
Zifferblattplatten für alle Instrumente einer Ausgabe. Tacho und Chronograph
sind eigenständige Module für 2 bis 5 Quellen. Dort können einzelne Quellen
das Grunddesign einschließlich SVG-Zeiger, SVG-Nabe und SVG-Zifferblatt
überschreiben.
Alle Gauge-Module erzeugen optional
eine IPSView-WebContent-Variable mit vererbtem oder unabhängigem Design auf
demselben Renderer. Die gemeinsame ECharts-Hintergrundlogik unter
`libs/EChartsIPSViewBackground.php` steuert für Gauge und TimeSeries das
Durchscheinen des IPSView-Hintergrunds sowie optionale Farbe und Deckkraft.
Weitere klassische Multi-Instrumentenpanel-Vorlagen fehlen noch. Das
Time-Series-Modul
nutzt den Gateway-Vertrag `archive.read` für rohe oder aggregierte Werte und
rendert daraus die erste native Zeitreihen-Kachel sowie optional eine getrennt
gestaltbare IPSView-WebContent-Ausgabe.
Quellen können den gemeinsamen Designer optional durch einen zentral in
`libs/EChartsTimeSeriesDesign.php` geführten Einzelvertrag für Linie,
Datenpunkte und Fläche überschreiben. Eigene SVG-Flächenmuster passieren dabei
ausschließlich den vorhandenen sicheren Import aus `EChartsSvgImage`.
Der gleiche quellbezogene Vertrag führt die persistierten Optionen für
automatische, aus der Variablendarstellung gelesene oder manuell festgelegte
Wertachsenbereiche. Die Zusammenführung je effektiver Einheit verbleibt als
fachliche Validierung im Zeitreihenmodul.
Ein getrenntes `Annotations`-Modell ergänzt bis zu 32 quellengebundene
Referenzlinien und Wertebereiche. Die wiederverwendbaren Formularverträge
liegen ebenfalls in `libs/EChartsTimeSeriesDesign.php`; Validierung,
Normalisierung und Zuordnung zur konkreten Reihe verbleiben im Gerätemodul.
Die Testsuite prüft zusätzlich
zu Struktur und Metadaten die Strict-Verträge, das Protokoll, die
ECharts-Integrität und den Gauge→Gateway→Visualisierungs-Datenweg unter PHP
8.5 mit Test-Doppeln.

| Bestandteil | Name | Aufgabe |
|---|---|---|
| Library | SymconECharts | Gemeinsames installierbares Paket |
| Splitter | EChartsGateway | Gemeinsame Dienste für mehrere Chart-Instanzen |
| Gerät | EChartsGaugeSingle | Ein Gauge-Chart mit genau einer Quellvariable und zwei Ausgabewegen |
| Gerät | EChartsGaugeMulti | Ein zusammengesetzter Gauge-Chart mit 2 bis 16 Quellen |
| Gerät | EChartsGaugeTacho | Tacho-Cockpit mit 2 bis 5 individuell gestaltbaren Instrumenten |
| Gerät | EChartsGaugeChronograph | Chronograph mit 2 bis 5 individuell gestaltbaren Instrumenten |
| Gerät | EChartsTimeSeries | Historische und live fortgeschriebene Linien und Flächen für 1 bis 8 Quellen, bis zu acht links/rechts angeordnete Einheitengruppen sowie getrenntes Kachel- und IPSView-Design |
| Gerät | EChartsBarCategory | Aktuelle Kategorienvergleiche für 1 bis 16 Quellen mit gemeinsamer Einheit, vertikal/horizontal und mit getrenntem Kachel- und IPSView-Design |

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
| Modul EChartsGaugeMulti | {E667D9C1-379D-44ED-A313-FF21FEFC355F} |
| Modul EChartsGaugeTacho | `{9D072BFE-45B4-4442-B1D2-4FC458C3BABD}` |
| Modul EChartsGaugeChronograph | `{B99C3ADA-9A90-486F-97E3-96C39554D9A6}` |
| Modul EChartsTimeSeries | `{EF172F3B-50F5-41D1-B18E-6BCDEAECCABA}` |
| Modul EChartsBarCategory | `{F9A88437-FE9E-4038-ADAF-775623D2DDC1}` |
| Datenfluss Gauge → Gateway | `{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}` |
| Datenfluss Gateway → Gauge | `{E4749B72-912B-E3E3-1C57-D19019FFDD84}` |

Die Funktionspräfixe sind `ECGW` für das Gateway, `ECGS` für Gauge Single,
`ECGM` für Gauge Multi, `ECGT` für Gauge Tacho, `ECGC` für Gauge
Chronograph, `ECTS` für Time Series und `ECBC` für Bar Category. Nach der verbindlichen Präfixkonvention steht `EC` für
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
Die optionale IPSView-WebContent-Ausgabe und ihr getrenntes Design dokumentiert
[`ADR 0008`](adr/0008-gauge-single-ipsview-output.md).
Den persistenten Gateway-Transport für flackerfreie IPSView-Aktualisierungen
dokumentiert [`ADR 0024`](adr/0024-persistent-ipsview-transport.md).
Den ersten sichtbaren Gauge-Multi-Renderer dokumentiert
[`ADR 0009`](adr/0009-gauge-multi-title-renderer.md).
Die beiden ergänzenden Ring-Vorlagen dokumentiert
[`ADR 0010`](adr/0010-gauge-multi-ring-presets.md).
Die erste analoge Wetterstations-Vorlage dokumentiert
[`ADR 0011`](adr/0011-weather-station-gauge-preset.md).
Das zusätzliche Tacho-Cockpit dokumentiert
[`ADR 0012`](adr/0012-tacho-cockpit-gauge-preset.md).
Den eingebetteten Chronographen dokumentiert
[`ADR 0014`](adr/0014-embedded-chronograph-gauge-preset.md).
Die eigenständigen Tacho- und Chronograph-Module dokumentiert
[`ADR 0016`](adr/0016-dedicated-tacho-and-chronograph-modules.md).
Die verbindliche Prüfung und Zentralisierung bereits vorhandener
Chart-Funktionen vor einer Neuentwicklung dokumentiert
[`ADR 0017`](adr/0017-reuse-before-new-chart-development.md).
Den Umfang der ersten Zeitreihenfamilie und der dafür benötigten
Gateway-Archivoperation dokumentiert
[`ADR 0018`](adr/0018-first-time-series-family-and-archive-contract.md).
Die Aufteilung der Bar-Familien und den ersten Category-Bar-Vertrag
dokumentiert [`ADR 0029`](adr/0029-bar-chart-families-and-category-bar.md).

## Daten, Konfiguration und Ausgabe

Symcon-Datenanbindung, Diagrammkonfiguration und ECharts-Darstellung werden
getrennt. Die Konfiguration der jeweiligen Chartfamilien-Instanz bleibt die
maßgebliche Quelle für ihre Variablen, Datenreihen und Auswertungsregeln.
Layout-Presets und ECharts-Themes bleiben getrennte Konfigurationsachsen. Die
Theme-Auswahl gehört zur Diagramminstanz, während die geprüften offiziellen
Theme-Assets und ihre stabilen IDs repositoryweit gemeinsam genutzt werden.

Das Gateway stellt den gemeinsamen Zugriff auf numerische Momentanwerte sowie
die für die erste Zeitreihenfamilie festgelegte Operation `archive.read`
bereit. Sie normalisiert rohe und aggregierte Archivwerte und verwendet einen
kurzlebigen, auf 32 Einträge begrenzten Cache. Ein
HTML-Dokument wird nicht bei jeder Messwertänderung neu erzeugt. Datenmenge,
Aktualisierungsrate und Animationen werden insbesondere für mehrere
gleichzeitig sichtbare Charts begrenzt und getestet.

Time Series trennt archivfreie Echtzeitwerte gemäß
[`ADR 0019`](adr/0019-timeseries-realtime-without-archive.md) ausdrücklich von
Rohwerten. `realtime` beginnt mit `current.read` und sammelt nachfolgende
`VM_UPDATE`-Punkte nur im offenen Browser; `raw` bleibt eine unveränderte
Archivabfrage.

Die native Kachel und IPSView erhalten getrennte Ausgabeadapter zum gemeinsamen
Darstellungskern. Gauge Single schreibt ein vollständiges, eigenständiges
Startdokument in eine lokale WebContent-Variable. Laufzeitwerte erreichen das
bestehende ECharts-Objekt anschließend über den zentralen, kanalgebundenen
Gateway-WebHook und WebSocket, ohne die Variable neu zu schreiben. Eine
abgeschaltete IPSView-Ausgabe beeinträchtigt den nativen Ausgabeweg nicht und
lässt eine bereits angelegte Ausgabevariable bis zu einer ausdrücklich
bestätigten Löschung bestehen.

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
Die diagrammfamilienspezifischen Browser-Builds 6.1.0 sind lokal festgeschrieben, werden aus
der offiziellen Tree-Shaking-API reproduzierbar erzeugt und mit Prüfsumme,
Originallizenz sowie NOTICE ausgeliefert. Vor der Einbettung wird er auf
Integrität geprüft. Die getrennten Gauge- und kartesischen Einstiegspunkte
enthalten jeweils nur die benötigten Charts und Komponenten und halten das vollständige HTML-Dokument unter dem
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

Vor der Programmierung einer neuen Funktion oder Chartfamilie werden die
fertigen Charts und vorhandenen Bibliotheken auf eine bereits bewährte Lösung
geprüft. Eine kompatible, innerhalb von SymconECharts wiederverwendbare
Funktion wird als projektspezifischer Baustein direkt unter `libs`
zentralisiert. ECharts-unabhängige Funktionen mit Nutzen für weitere
Symcon-Module entstehen dagegen in `Symcon_ModuleHelper` und gelangen nur über
den festgelegten Synchronisierungsweg nach `libs/helper`. Die Abgrenzung und
die erforderlichen Vertragsprüfungen legt
[`ADR 0017`](adr/0017-reuse-before-new-chart-development.md) fest.

Gemeinsame ECharts-Gauge-Designsemantik liegt in
`libs/EChartsGaugeDesign.php`. Der Baustein vereinheitlicht für Single und
Multi insbesondere RGB-Farben sowie den validierten Import von SVG-Zeigern
einschließlich Ankerpunkt und von bereinigten SVG-Zifferblatthintergründen.
Property-Registrierung, Formulare, Vorlagengeometrie und Renderer bleiben im
jeweiligen Gerätemodul, weil sie dessen öffentlichen Vertrag bestimmen.

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

Vor der Übergabe commitbereiter Änderungen wird der vollständige lokale Lauf
gestartet:

```text
php tests/quality.php --fix
```

Er korrigiert ausschließlich mechanische PHP- und JSON-Formatierung, prüft
anschließend die Korrekturen erneut und führt Syntax-, Vertrags-, Integrations-
und Layouttests jeweils mit eigenem Fehlerstatus aus. Der reine Prüfmodus
`php tests/quality.php` verändert keine Dateien. Optional aktiviert
`git config core.hooksPath .githooks` den versionierten Pre-Push-Hook. Der Hook
stoppt einen Push, wenn der Qualitätslauf fehlschlägt oder noch nicht
committete Formatkorrekturen erzeugt.

Die offizielle Symcon-Stylekonfiguration wird als Git-Submodul unter `.style`
eingebunden. Sie bleibt dadurch auf einen nachvollziehbaren StylePHP-Commit
festgeschrieben und kann über den normalen Submodulablauf aktualisiert werden.

Der erste Funktionsumfang umfasst ein radiales Messinstrument in
`EChartsGaugeSingle` und zusammengesetzte Gauges auf Basis des vorhandenen
Mehrquellenmodells in `EChartsGaugeMulti`. Bei der sichtbaren Umsetzung werden
mehrere getrennte Gauge-Instanzen, beide Ausgabewege gleichzeitig,
Größenwechsel sowie abgeschaltete IPSView-Ausgabe getestet. Die erste
Zeitreihen-Vertikale einschließlich optionaler IPSView-Ausgabe ist ebenfalls
implementiert; weitere Chartfamilien sind
spätere, getrennt zu entscheidende Ausbaustufen.
Vor ihrer Umsetzung erfolgt die Wiederverwendungsprüfung nach
[`ADR 0017`](adr/0017-reuse-before-new-chart-development.md).
Für die erste Zeitreihenfamilie legt
[`ADR 0018`](adr/0018-first-time-series-family-and-archive-contract.md) den
Ausgangsumfang und den implementierten Gateway-Vertrag `archive.read` fest.
[`ADR 0019`](adr/0019-timeseries-realtime-without-archive.md) ergänzt den
archivfreien Echtzeitmodus, [`ADR 0020`](adr/0020-timeseries-tile-designer.md)
den Kacheldesigner und [`ADR 0021`](adr/0021-timeseries-multiple-value-axes.md)
die Mehr-Achsen-Anordnung. Die getrennte IPSView-Ausgabe und ihr unabhängiger
Designvertrag sind in
[`ADR 0022`](adr/0022-timeseries-ipsview-output.md) festgelegt.
Der gemeinsame persistente IPSView-Transport ist in
[`ADR 0024`](adr/0024-persistent-ipsview-transport.md) festgelegt.
Das quellenbezogene Einzeldesign einschließlich sicherer SVG-Flächenmuster
dokumentiert
[`ADR 0023`](adr/0023-timeseries-source-design-and-svg-area-patterns.md).
Quellenbezogene Referenzlinien und Wertebereiche sowie die dafür erweiterte
lokale ECharts-Runtime dokumentiert
[`ADR 0026`](adr/0026-timeseries-reference-lines-and-value-ranges.md).
Benutzerdefinierte rollende Zeiträume und wahlweise geerbte oder unabhängige
Zeiteinstellungen für Kachel und IPSView folgen
[`ADR 0027`](adr/0027-timeseries-custom-ranges-and-time-axis-labels.md).
Dieselbe Entscheidung umfasst kalendergebundene Zeiträume für heute, gestern,
die laufende Woche und den laufenden Monat. Deren Grenzen werden in der
Symcon-Zeitzone gebildet und bei Zeitumstellungen nicht auf feste
24-Stunden-Tage reduziert.
Die additive Datenlückenerkennung aus
[`ADR 0028`](adr/0028-timeseries-data-gap-detection.md) überführt fehlende
Messintervalle erst im Browserrenderer in explizite ECharts-Unterbrechungen.
Das archivierte beziehungsweise live fortgeschriebene Quellmodell bleibt
dabei unverändert und die Funktion ist für bestehende Instanzen deaktiviert.
Das Gerätemodul setzt davon Quellen, Roh-, Aggregat- und Echtzeitmodus,
Punktbudget, Mehr-Achsen-Modell, native Kachel und die optionale IPSView-
Ausgabe um.

## Laufzeitnachweise

Am 07.10.2026 wurde die native TimeSeries-Ausgabe auf der erreichbaren
Symcon-9.1-Testebene ausschließlich lesend geprüft. Eine aktive Instanz mit
drei Quellen lieferte ein gültiges Echtzeitmodell mit getrennten Achsen für
Temperatur, Luftfeuchtigkeit und Luftdruck. Konfigurationsformular,
Funktionsregistrierung und Browser-IFrame waren erreichbar; die zugängliche
ECharts-Beschreibung bestätigte drei dargestellte Reihen und fortgeschriebene
Echtzeitpunkte. Seit dem letzten Statistik-Reset lagen keine Kernel-Fehler vor.

Die Prüfung änderte weder Instanzkonfigurationen noch Variablenwerte. Ein
Laufzeitnachweis unter Symcon 9.0 sowie ein Test der bei dieser Instanz
deaktivierten IPSView-Ausgabe bleiben ausdrücklich offen. Der im äußeren
Symcon-WebFront auftretende `MutationObserver`-Fehler lag außerhalb des
Diagramm-IFrames und wurde nicht dem ECharts-Renderer zugerechnet.

## Dokumentation und Lizenzen

Die [Haupt-README](../README.md) und die README-Dateien der Module sind reine
Benutzerdokumentation. Sie beschreiben Voraussetzungen, Installation,
Konfiguration, Variablen, Bedienung und öffentliche Funktionen. Interne
Architektur, Entwicklung, Tests und CI werden in diesem Dokument, den ADRs
oder den Testdokumenten gepflegt. `README1.md` bleibt als unveränderte
Ausgangsvorlage erhalten.

Jede sichtbare Änderung wird im selben fachlichen Zusammenhang in der
[CHANGELOG.md](../CHANGELOG.md) unter `# Unveröffentlicht` eingetragen. Erst
eine ausdrücklich beauftragte Release-Vorbereitung ordnet diese Einträge einer
Version und einem Veröffentlichungsdatum zu. Interne Refactorings und reine
Formatkorrekturen benötigen keinen Changelog-Eintrag.

Geplanter und tatsächlich vorhandener Funktionsumfang werden ausdrücklich
getrennt. Beispiel-APIs, Store-Veröffentlichungen oder Versionsfreigaben
werden nicht vor ihrer Umsetzung behauptet.

Eigene Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](../LICENSE). Fremdkomponenten behalten
ihre Originallizenzen; die konkrete Einbindung wird in
[THIRD_PARTY_NOTICES.md](../THIRD_PARTY_NOTICES.md) dokumentiert.

Required Notice: Copyright 2026 Burkhard Kneiseler. SymconECharts.
