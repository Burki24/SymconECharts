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

Die Library und beide Module sind als Grundgerüste vorhanden. ECharts,
fachliche Datenverarbeitung, Visualisierung, zentrale Helper-Anbindung sowie
fachliche Laufzeit- und Browsertests sind noch nicht eingebunden. Eine
Basistestsuite charakterisiert Projektstruktur, Modulidentitäten, Datenflüsse
und Metadatenautomatik; der Tests-Workflow führt sie unter PHP 8.5 über die
gemeinsame CI-Basis aus. Die folgenden Festlegungen beschreiben das
Entwicklungsziel, keine bereits ausgelieferten Funktionen.

| Bestandteil | Name | Aufgabe |
|---|---|---|
| Library | SymconECharts | Gemeinsames installierbares Paket |
| Splitter | EChartsGateway | Gemeinsame Dienste für mehrere Chart-Instanzen |
| Gerät | EChartsWidget | Ein unabhängig konfigurierbarer Chart mit zwei Ausgabewegen |

Eine eigene I/O-Instanz, ein Konfigurator und Discovery gehören nicht zum
Anfangsumfang. Eine Datenreihe ist Teil der Widget-Konfiguration und keine
eigene Geräteinstanz. Mehrere Widget-Instanzen sollen einen vorhandenen
Gateway gemeinsam verwenden können.

## Festgelegte Identitäten

Diese bereits vergebenen IDs werden bei der weiteren Entwicklung beibehalten.
Modul-IDs und Datenfluss-IDs haben unterschiedliche Aufgaben.

| Identität | GUID |
|---|---|
| Library SymconECharts | `{66BE21BE-988A-10EB-1BBB-1E2F444E9F85}` |
| Modul EChartsGateway | `{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}` |
| Modul EChartsWidget | `{0CAA2780-342F-E5B9-2865-CB8DCED0BE7C}` |
| Datenfluss Widget → Gateway | `{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}` |
| Datenfluss Gateway → Widget | `{E4749B72-912B-E3E3-1C57-D19019FFDD84}` |

Die Funktionspräfixe sind `SECG` für das Gateway und `SECW` für das Widget.

## Daten, Konfiguration und Ausgabe

Symcon-Datenanbindung, Diagrammkonfiguration und ECharts-Darstellung werden
getrennt. Die Konfiguration der jeweiligen Widget-Instanz bleibt die
maßgebliche Quelle für ihre Variablen, Datenreihen und Auswertungsregeln.

Das Gateway soll wiederverwendbare Archivabfragen und begrenzte Caches
bereitstellen. Ein HTML-Dokument wird nicht bei jeder Messwertänderung neu
erzeugt. Datenmenge, Aktualisierungsrate und Animationen werden insbesondere
für mehrere gleichzeitig sichtbare Charts begrenzt und getestet.

Die native Kachel und IPSView erhalten getrennte Kommunikationsadapter zum
gemeinsamen Darstellungskern. Der IPSView-Datenkanal benötigt eine eigene
Zugriffsprüfung; seine Berechtigungen werden nicht einfach aus der Anmeldung
an einer anderen Oberfläche abgeleitet. Eine abgeschaltete IPSView-Ausgabe
darf den nativen Ausgabeweg nicht beeinträchtigen.

## Plattform und Modulbasis

Entwicklungsziel und deklarierte Mindestversion sind **Symcon 9.0 / PHP 8.5**.
Eine Kompatibilität zu älteren Symcon-Versionen wird nicht versprochen.

Für die technische Umsetzung ist die moderne Modulbasis `IPSModuleStrict`
mit passenden Typdeklarationen vorgesehen. Die Parent-Auswahl wird über den
Datenfluss und bei Bedarf `GetCompatibleParents()` mit `type: connect`
aufgebaut. Der bisherige `RequireParent()`-Aufruf des Generatorgerüsts darf
nicht unverändert übernommen werden, da er für neue Widgets zusätzliche
Gateways erzeugt. Ein bloßer Wechsel der Basisklasse reicht nicht aus:
Legacy-Methoden und ihre Verträge müssen dabei ebenfalls angepasst werden.

Die zugehörigen Schnittstellen sind in der
[Symcon-Moduldokumentation](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/)
und unter
[GetCompatibleParents](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/getcompatibleparents/)
beschrieben. Siehe auch
[RequireParent](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/requireparent/).

## Bibliotheken und gemeinsame Helper

ECharts wird ohne projektspezifische Änderungen am Bibliothekskern verwendet.
Bibliotheksversionen werden festgeschrieben, mit dem Modul ausgeliefert und
vor Aktualisierungen getestet. Im Betrieb wird nicht automatisch `latest`
geladen. Der verwendete Build und seine Abhängigkeiten werden dokumentiert.

Vorhandene zentrale Helper aus `Symcon_ModuleHelper` werden wiederverwendet
und über den vorgesehenen Synchronisierungsweg eingebunden. Allgemeine
Erweiterungen gehören in das zentrale Helper-Projekt, nicht in abweichende
lokale Kopien. Projektspezifische Chart-Logik bleibt in SymconECharts.

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

Der erste Funktionsumfang umfasst ein radiales Messinstrument und ein
Liniendiagramm mit mehreren Datenreihen. Bereits dabei werden mehrere
getrennte Charts, beide Ausgabewege gleichzeitig, Größenwechsel sowie
abgeschaltete IPSView-Ausgabe getestet. Weitere Diagrammtypen sind spätere
Ausbaustufen.

## Dokumentation und Lizenzen

Die [Haupt-README](../README.md) folgt dem Aufbau der bereitgestellten
`README1.md`: Modulübersicht mit Kurzbeschreibungen und Links. Die ausführliche
Nutzungsdokumentation steht in den README-Dateien der beiden Modulordner.
`README1.md` bleibt als unveränderte Vorlage erhalten.

Geplanter und tatsächlich vorhandener Funktionsumfang werden ausdrücklich
getrennt. Beispiel-APIs, Store-Veröffentlichungen oder Versionsfreigaben
werden nicht vor ihrer Umsetzung behauptet.

Eigene Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](../LICENSE). Fremdkomponenten behalten
ihre Originallizenzen; die konkrete Einbindung wird in
[THIRD_PARTY_NOTICES.md](../THIRD_PARTY_NOTICES.md) dokumentiert.

Required Notice: Copyright 2026 Burkhard Kneiseler. SymconECharts.
