# EChartsGaugeMulti

Gerätemodul der SymconECharts-Library für einen zusammengesetzten Gauge-Chart
mit mehreren Werten oder Zeigern. Eine Gauge-Multi-Instanz soll die native
Symcon-Kacheldarstellung und ein separat platzierbares HTML-Widget in IPSView
bereitstellen können. Datenquellen und Diagrammkonfiguration werden dabei nur
einmal gepflegt.

**Entwicklungsstand:** Technische Mehrquellenbasis. Eine geordnete Liste aus
2 bis 16 numerischen Quellvariablen ist konfigurierbar, wird als Referenzen
registriert und über EChartsGateway gelesen. Das Modul erzeugt daraus ein
versioniertes Multi-Datenmodell. Diagramme, Kacheln und
IPSView-HTML-Ausgabevariablen sind noch nicht implementiert.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in Symcon](#4-einrichten-der-instanzen-in-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [Visualisierung](#6-visualisierung)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

**Vorhanden:** Eigene Moduldefinition als Gerät (`type: 3`) auf Basis von
`IPSModuleStrict`, eigene GUID und eigenes Präfix sowie eine wiederverwendbare
Verbindung zu EChartsGateway. Jede konfigurierte Quelle besitzt eine eindeutige
numerische Variable, Beschriftung, Minimum, Maximum, Einheit und 0 bis 6
Nachkommastellen. `GetGaugeData()` liest alle aktuellen Werte über das
versionierte Gateway-Protokoll und liefert sie in der konfigurierten Reihenfolge.

**Geplant:** Vorlagen wie Multi Title, Ring und Car, sichtbare Live-Darstellung
sowie die Ausgabe als native Symcon-Kachel und optionales IPSView-Widget.

Eine Instanz bildet genau einen zusammengesetzten Gauge-Chart ab. Gauges mit
genau einer numerischen Quellvariable gehören zu
[EChartsGaugeSingle](../EChartsGaugeSingle).

### 2. Voraussetzungen

Das Entwicklungsziel und die deklarierte Mindestversion sind
**Symcon 9.0 / PHP 8.5**. Eine Kompatibilität zu älteren Symcon-Versionen wird
nicht versprochen. Die Modulverträge werden lokal mit Test-Doppeln geprüft;
ein Laufzeitnachweis in einer realen Symcon-Installation steht noch aus.

Erforderlich ist eine Verbindung zu einer aktiven
[EChartsGateway-Instanz](../EChartsGateway) sowie mindestens zwei vorhandene
Integer- oder Float-Variablen als Datenquellen.

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

**Zielmodell:** Eine EChartsGaugeMulti-Instanz je unabhängig konfigurierbarem
zusammengesetztem Gauge-Chart. Mehrere Gauge-Instanzen sollen ein gemeinsames
Gateway verwenden können. Für die parallele Anzeige desselben Charts als
Kachel und in IPSView ist keine zweite Gauge-Instanz vorgesehen.

Bei der ersten ECharts-Diagramminstanz wird ein EChartsGateway neu angelegt.
Bei allen weiteren Gauge- oder anderen ECharts-Diagramminstanzen wird dieses
vorhandene Gateway ausgewählt. Symcon bietet aufgrund des Verbindungstyps
zusätzlich weiterhin eine Neuanlage an; sie ist für den regulären Betrieb nicht
notwendig.

**Aktueller Stand:** Über die kompatiblen Parent-Verbindungen kann eine
vorhandene EChartsGateway-Instanz ausgewählt und von mehreren Gauges verwendet
werden. Ohne aktive Verbindung bleibt die Gauge-Instanz in einem eindeutigen
Fehlerstatus.

**Konfigurationsseite:** Neben dem gemeinsamen Titel wird eine geordnete Liste
aus 2 bis 16 Quellen gepflegt. Jede Variable darf nur einmal vorkommen. Pro
Quelle werden Beschriftung, Minimum, Maximum, Einheit und 0 bis 6
Nachkommastellen festgelegt. Eine leere Beschriftung verwendet automatisch den
Namen der Symcon-Variable. Die Reihenfolge bestimmt später die Zuordnung zu den
Positionen der gewählten Multi-Vorlage.

Das in Version 1.8 kurzzeitig enthaltene Einzelquellen-Gerüst wird nicht
automatisch übernommen: Eine einzelne Quelle erfüllt den Multi-Vertrag nicht.
Bereits angelegte Entwicklungsinstanzen müssen ihre Quellenliste neu
konfigurieren.

### 5. Statusvariablen und Profile

#### Statusvariablen

Aktuell werden keine eigenen Variablen angelegt. Für IPSView ist künftig
je aktivierter Widget-Ausgabe eine eigene Stringvariable mit HTML-Inhalt
vorgesehen. Die native Kachel soll diese Variable nicht benötigen.

Quellvariablen werden als Referenzen registriert und bei Änderungen der Liste
deterministisch ergänzt oder entfernt. Messwertkopien unter dem Gauge-Modul
werden nicht angelegt. Beim späteren Abschalten der IPSView-Ausgabe sollen
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

Das festgelegte Funktionspräfix lautet `ECGM`.

```php
$json = ECGM_GetGaugeData($InstanceID);
```

`ECGM_GetGaugeData()` validiert Konfiguration und aktive Gateway-Verbindung
und liefert das aktuelle Gauge-Datenmodell als JSON. Das Modell enthält
`schemaVersion: 1`, `family: gauge`, `variant: multi`, den gemeinsamen Titel
und eine geordnete `items`-Liste. Jeder Eintrag enthält eine aus der
Variablen-ID abgeleitete stabile ID, Quellvariable, Zeitstempel,
Gauge-Konfiguration und numerischen Wert. Es ist die technische Grenze für die
späteren Ausgabeadapter und noch kein gerendertes ECharts-Diagramm.

Weitere Projektgrundsätze: [Entwicklung](../docs/ENTWICKLUNG.md).  
Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).

