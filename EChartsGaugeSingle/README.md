# EChartsGaugeSingle

Gerätemodul der SymconECharts-Library für ein Gauge-Diagramm mit genau einer
numerischen Quellvariable. Eine Gauge-Single-Instanz soll die native
Symcon-Kacheldarstellung und ein separat
platzierbares HTML-Widget in IPSView bereitstellen können. Datenquelle und
Diagrammkonfiguration werden dabei nur einmal gepflegt.

**Entwicklungsstand:** Technische Datenbasis. Quellvariable und minimale
Gauge-Einstellungen sind konfigurierbar; das Modul ruft den Momentanwert über
EChartsGateway ab und erzeugt daraus ein versioniertes Gauge-Datenmodell. Es
werden noch keine Diagramme, Kacheln oder IPSView-HTML-Ausgabevariablen erzeugt.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in Symcon](#4-einrichten-der-instanzen-in-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [Visualisierung](#6-visualisierung)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

**Vorhanden:** Moduldefinition als Gerät (`type: 3`) auf Basis von
`IPSModuleStrict`, wiederverwendbare Verbindung zu EChartsGateway,
Variablenauswahl, Wertebereich, Titel, Einheit und Nachkommastellen. Die
Quellvariable wird als Symcon-Referenz registriert. `GetGaugeData()` liest den
aktuellen Wert über das versionierte Gateway-Protokoll und liefert ein
familienbezogenes JSON-Datenmodell.

**Geplant:** Sichtbare Live-Darstellung, ECharts-spezifische Skalen- und
Gestaltungsoptionen sowie die Ausgabe als native Symcon-Kachel und optionales
IPSView-Widget. Archivdarstellung wird erst mit einem dafür festgelegten
Chart-Anwendungsfall umgesetzt.

Eine Instanz bildet genau einen unabhängig konfigurierbaren Single-Gauge-Chart
ab. Zusammengesetzte Gauges mit mehreren Werten gehören zu
[EChartsGaugeMulti](../EChartsGaugeMulti). Weitere Chartfamilien werden bei
Bedarf als eigene Gerätemodule ergänzt.

### 2. Voraussetzungen

Das Entwicklungsziel und die deklarierte Mindestversion sind
**Symcon 9.0 / PHP 8.5**. Eine Kompatibilität zu älteren Symcon-Versionen wird
nicht versprochen. Die Modulverträge werden lokal mit Test-Doppeln geprüft;
ein Laufzeitnachweis in einer realen Symcon-Installation steht noch aus.

Erforderlich sind eine Verbindung zu einer aktiven
[EChartsGateway-Instanz](../EChartsGateway) und eine vorhandene Integer- oder
Float-Variable als Datenquelle. Ein reines Momentanwert-Widget benötigt keine
Historie. IPSView soll nur für den zusätzlichen IPSView-Ausgabeweg erforderlich
sein, nicht für native Kacheln.

### 3. Software-Installation

Das Gauge-Modul wird gemeinsam mit EChartsGateway über die Library
**SymconECharts** eingebunden. Es gibt kein separat zu installierendes
Gerätepaket.

Für Entwicklungsarbeiten im Module Control das Repository hinzufügen und
`dev` auswählen:

```text
https://github.com/Burki24/SymconECharts
```

Die Beschreibung gilt für das Entwicklungsgerüst, nicht für eine bereits
funktionsfähige oder produktiv freigegebene Visualisierung. Eine Veröffentlichung
im Module Store wird nicht vorausgesetzt.

### 4. Einrichten der Instanzen in Symcon

**Zielmodell:** Eine EChartsGaugeSingle-Instanz je unabhängig konfigurierbarem
Gauge-Chart. Mehrere Gauge-Instanzen sollen ein gemeinsames Gateway verwenden
können. Für die parallele Anzeige desselben Charts als Kachel und in IPSView
ist keine zweite Gauge-Instanz vorgesehen.

**Aktueller Stand:** Über die kompatiblen Parent-Verbindungen kann eine
vorhandene EChartsGateway-Instanz ausgewählt und von mehreren Gauges verwendet
werden. Ohne aktive Verbindung bleibt die Gauge-Instanz in einem eindeutigen
Fehlerstatus.

**Konfigurationsseite:** Ausgewählt werden eine numerische Quellvariable,
Minimum und Maximum, Titel, Einheit sowie 0 bis 6 Nachkommastellen. Die Aktion
„Aktuelle Gauge-Daten lesen“ gibt das gegenwärtige JSON-Datenmodell zu
Diagnosezwecken aus. Minimum muss kleiner als Maximum sein.

### 5. Statusvariablen und Profile

#### Statusvariablen

Aktuell werden keine eigenen Variablen angelegt. Für IPSView ist künftig
je aktivierter Widget-Ausgabe eine eigene Stringvariable mit HTML-Inhalt
vorgesehen. Die native Kachel soll diese Variable nicht benötigen.

Quellvariablen sollen referenziert und nicht als Messwertkopien unter dem
Gauge-Modul dupliziert werden. Beim späteren Abschalten der IPSView-Ausgabe sollen
bestehende HTML-Variablen erhalten bleiben; ein Löschen soll ausdrücklich
bestätigt werden müssen.

#### Profile

Aktuell werden noch keine Profile oder eigenen Variablendarstellungen angelegt.
Die künftige Ausgabe soll auf den gemeinsamen Variablen- und
Darstellungs-Helpern aufbauen.

### 6. Visualisierung

**Beide Ausgabewege gehören zum Projektziel und sind noch nicht implementiert.**

#### Native Symcon-Kachel

Vorgesehen ist eine eigene Darstellung der Chart-Instanz über das Symcon-HTML-SDK.
Sie soll ohne aktivierte IPSView-Ausgabe funktionieren. Datenaktualisierungen
und Interaktionen erhalten eine native Anbindung, getrennt vom IPSView-Datenkanal.

#### IPSView

Vorgesehen ist ein kleines, eigenständig platzierbares HTML-Widget pro Chart,
keine gemeinsame Dashboard-Seite. Die Größe und Platzierung werden in IPSView
festgelegt; das Diagramm soll sich an die verfügbare Fläche anpassen.

#### Gemeinsame Darstellung

ECharts-Konfiguration, Datenaufbereitung und Zeichenlogik sollen gemeinsam
verwendet werden. Ausgabespezifische Anpassungen wie Hintergrund, Transparenz
und Schriftgrößen dürfen getrennt eingestellt werden, ohne die andere
Visualisierung unbeabsichtigt zu verändern.

### 7. PHP-Befehlsreferenz

Das festgelegte Funktionspräfix lautet `ECGS`.

```php
$json = ECGS_GetGaugeData($InstanceID);
```

`ECGS_GetGaugeData()` validiert Konfiguration und aktive Gateway-Verbindung
und liefert das aktuelle Gauge-Datenmodell als JSON. Das Modell enthält
`schemaVersion`, `family`, Quellvariable und Zeitstempel, die minimale
Gauge-Konfiguration sowie den numerischen Wert. Es ist die technische Grenze
für die späteren Ausgabeadapter und noch kein gerendertes ECharts-Diagramm.

Weitere Projektgrundsätze: [Entwicklung](../docs/ENTWICKLUNG.md).  
Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).
