# EChartsGaugeSingle

Gerätemodul der SymconECharts-Library für ein Gauge-Diagramm mit genau einer
numerischen Quellvariable. Eine Gauge-Single-Instanz soll die native
Symcon-Kacheldarstellung und künftig ein separat platzierbares HTML-Widget in
IPSView bereitstellen können. Datenquelle und Diagrammkonfiguration werden
dabei nur einmal gepflegt.

**Entwicklungsstand:** Erste sichtbare native Gauge mit Kacheldesigner.
Quellvariable und minimale Gauge-Einstellungen sind konfigurierbar; das Modul ruft den
Momentanwert über EChartsGateway ab, erzeugt ein versioniertes Gauge-Datenmodell
und rendert es mit Apache ECharts 6.1.0 in einer responsiven Symcon-Kachel.
Wertänderungen werden live an die geöffnete Kachel übertragen. Das
Konfigurationsformular wählt zwischen vier layoutbezogenen Presets und zeigt
zusätzlich eine live aktualisierte SVG-Vorschau der noch nicht übernommenen
Einstellungen. ECharts-Themes und IPSView-HTML-Ausgabevariablen sind noch nicht
implementiert.

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
Quellvariable wird als Symcon-Referenz registriert und auf Wertänderungen
überwacht. `GetGaugeData()` liest den aktuellen Wert über das versionierte
Gateway-Protokoll und liefert ein familienbezogenes JSON-Datenmodell. Die
native HTML-SDK-Kachel stellt dieses Modell als responsive Gauge dar und
übernimmt Symcon-Farbvariablen für helle und dunkle Oberflächen. Der
Kacheldesigner bietet Basic, Simple, Progress und Speed; Simple ist aus
Kompatibilitätsgründen die Standardauswahl und entspricht der bisherigen
Darstellung.

**Geplant:** Zusätzliche Gauge-Single-Presets, ECharts-Themes, weitere
Skalen- und Gestaltungsoptionen sowie die Ausgabe als optionales IPSView-Widget.
Archivdarstellung wird erst mit einem dafür
festgelegten Chart-Anwendungsfall umgesetzt.

Eine Instanz bildet genau einen unabhängig konfigurierbaren Single-Gauge-Chart
ab. Zusammengesetzte Gauges mit mehreren Werten gehören zu
[EChartsGaugeMulti](../EChartsGaugeMulti). Weitere Chartfamilien werden bei
Bedarf als eigene Gerätemodule ergänzt.

### 2. Voraussetzungen

Das Entwicklungsziel und die deklarierte Mindestversion sind
**Symcon 9.0 / PHP 8.5**. Eine Kompatibilität zu älteren Symcon-Versionen wird
nicht versprochen. Die Modulverträge, HTML-Erzeugung und Live-Zustände werden
lokal mit Test-Doppeln geprüft; ein visueller Laufzeitnachweis in einer realen
Symcon-Installation und den verschiedenen Symcon-Clients steht noch aus.

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

Die Beschreibung gilt für einen Entwicklungsstand mit erster nativer Gauge,
nicht für eine bereits produktiv freigegebene Visualisierung. Eine
Veröffentlichung im Module Store wird nicht vorausgesetzt.

### 4. Einrichten der Instanzen in Symcon

**Zielmodell:** Eine EChartsGaugeSingle-Instanz je unabhängig konfigurierbarem
Gauge-Chart. Mehrere Gauge-Instanzen sollen ein gemeinsames Gateway verwenden
können. Für die parallele Anzeige desselben Charts als Kachel und in IPSView
ist keine zweite Gauge-Instanz vorgesehen.

Bei der ersten ECharts-Diagramminstanz wird ein EChartsGateway neu angelegt.
Bei allen weiteren Gauge- oder anderen ECharts-Diagramminstanzen wird dieses
vorhandene Gateway ausgewählt. Symcon bietet aufgrund des Verbindungstyps
zusätzlich weiterhin eine Neuanlage an; sie ist für den regulären Betrieb nicht
notwendig.

**Aktueller Stand:** Über die kompatiblen Parent-Verbindungen kann eine
vorhandene EChartsGateway-Instanz ausgewählt und von mehreren Gauges verwendet
werden. Ohne aktive Verbindung bleibt die Gauge-Instanz in einem eindeutigen
Fehlerstatus.

**Konfigurationsseite:** Ausgewählt werden eine numerische Quellvariable,
Minimum und Maximum, Titel, Einheit, 0 bis 6 Nachkommastellen sowie ein
Gauge-Preset. Die stabilen Preset-IDs sind `basic`, `simple`, `progress` und
`speed`. Die Aktion
„Aktuelle Gauge-Daten lesen“ gibt das gegenwärtige JSON-Datenmodell zu
Diagnosezwecken aus. Minimum muss kleiner als Maximum sein. Eine SVG-Vorschau
reagiert unmittelbar auf Änderungen im geöffneten Formular, ohne diese Werte
vorzeitig zu speichern. Bei einer gültigen numerischen Quellvariable verwendet
sie deren aktuellen Wert, andernfalls die Mitte des konfigurierten Bereichs.

### 5. Statusvariablen und Profile

#### Statusvariablen

Für die native Kachel werden keine eigenen Variablen angelegt. Für IPSView ist künftig
je aktivierter Widget-Ausgabe eine eigene Stringvariable mit HTML-Inhalt
vorgesehen. Die native Kachel benötigt diese Variable nicht.

Quellvariablen sollen referenziert und nicht als Messwertkopien unter dem
Gauge-Modul dupliziert werden. Beim späteren Abschalten der IPSView-Ausgabe sollen
bestehende HTML-Variablen erhalten bleiben; ein Löschen soll ausdrücklich
bestätigt werden müssen.

#### Profile

Aktuell werden noch keine Profile oder eigenen Variablendarstellungen angelegt.
Die künftige Ausgabe soll auf den gemeinsamen Variablen- und
Darstellungs-Helpern aufbauen.

### 6. Visualisierung

**Die native Kachel ist als erster Ausgabeweg implementiert; IPSView folgt.**

#### Native Symcon-Kachel

Implementiert sind vier Gauge-Layouts über das Symcon-HTML-SDK. Sie funktionieren
ohne aktivierte IPSView-Ausgabe und passen sich an die verfügbare Kachelgröße an.
Die Quellvariable wird über `VM_UPDATE` beobachtet; neue Werte gelangen über
den HTML-SDK-Nachrichtenkanal in die bestehende Kachel, ohne das gesamte
HTML-Dokument neu aufzubauen. Bei ungültiger Konfiguration oder fehlendem
Gateway zeigt die Kachel einen übersetzten Hinweis statt eines leeren Charts.

Die Darstellung verwendet die lokal mitgelieferte und per SHA-256 geprüfte
Apache-ECharts-Runtime 6.1.0. Sie lädt im Betrieb keine CDN-Ressource. Die
aktuelle Implementierung ist technisch gegen Test-Doppel geprüft; die visuelle
Prüfung in realen Symcon-Clients bleibt offen.

#### Kacheldesigner und Konfigurationsvorschau

Die Vorschau im Kacheldesigner ist ein leichtgewichtiges, eigenständiges SVG
und lädt nicht die vollständige ECharts-Browser-Runtime. Dadurch bleibt das
Formular schnell und die noch nicht gespeicherte Konfiguration kann unmittelbar
dargestellt werden. Die Kachel bleibt für die tatsächliche, interaktive
ECharts-Ausgabe maßgeblich; die SVG-Vorschau bildet das ausgewählte Layout
gezielt nach. Die Presets verändern noch keine Farbpalette; dieser Vertrag
bleibt der getrennten Theme-Ausbaustufe vorbehalten.

#### IPSView

Weiterhin vorgesehen ist ein kleines, eigenständig platzierbares HTML-Widget pro Chart,
keine gemeinsame Dashboard-Seite. Die Größe und Platzierung werden in IPSView
festgelegt; das Diagramm soll sich an die verfügbare Fläche anpassen.

#### Gemeinsame Darstellung

Das gemeinsame HTML-Seitenmodell und die Gauge-Zeichenlogik sind für beide
Ausgabewege vorgesehen. Der IPSView-Datenkanal und die dafür benötigte
Zugriffsprüfung sind noch nicht implementiert. Ausgabespezifische Anpassungen
wie Hintergrund, Transparenz und Schriftgrößen dürfen getrennt eingestellt
werden, ohne die andere Visualisierung unbeabsichtigt zu verändern.

### 7. PHP-Befehlsreferenz

Das festgelegte Funktionspräfix lautet `ECGS`.

```php
$json = ECGS_GetGaugeData($InstanceID);
```

`ECGS_GetGaugeData()` validiert Konfiguration und aktive Gateway-Verbindung
und liefert das aktuelle Gauge-Datenmodell als JSON. Das Modell enthält
`schemaVersion`, `family`, Quellvariable und Zeitstempel, die minimale
Gauge-Konfiguration sowie den numerischen Wert. Dasselbe fachliche Modell wird
vom nativen Gauge-Single-Renderer verwendet.

Weitere Projektgrundsätze: [Entwicklung](../docs/ENTWICKLUNG.md).  
Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).
