# EChartsGauge

Gerätemodul der SymconECharts-Library für ein einzelnes Gauge-Diagramm. Eine
Gauge-Instanz soll die native Symcon-Kacheldarstellung und ein separat
platzierbares HTML-Widget in IPSView bereitstellen können. Datenquelle und
Diagrammkonfiguration werden dabei nur einmal gepflegt.

**Entwicklungsstand:** Modulgerüst. Es werden noch keine Diagramme, Kacheln
oder IPSView-HTML-Ausgabevariablen erzeugt.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in Symcon](#4-einrichten-der-instanzen-in-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [Visualisierung](#6-visualisierung)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

**Vorhanden:** Moduldefinition als Gerät (`type: 3`), Datenfluss-Zuordnung zu
EChartsGateway und ein PHP-Grundgerüst mit generierten Sende- und
Empfangsbeispielen. Die Konfigurationsform ist noch leer.

**Geplant:** Konfiguration eines radialen Messinstruments, Referenzierung
bestehender Symcon-Variablen, Live- und Archivdarstellung sowie
Gauge-spezifische Skalen-, Wertebereichs- und Gestaltungsoptionen.

Eine Instanz bildet genau einen unabhängig konfigurierbaren Gauge-Chart ab.
Weitere Chartfamilien werden bei Bedarf als eigene Gerätemodule ergänzt und
nicht als umschaltbare Modi dieser Instanz implementiert.

### 2. Voraussetzungen

Das Entwicklungsziel und die deklarierte Mindestversion sind
**Symcon 9.0 / PHP 8.5**. Eine Kompatibilität zu älteren Symcon-Versionen wird
nicht versprochen. Die Laufzeitfähigkeit des aktuellen Modulgerüsts ist damit
noch nicht belegt.

Vorgesehen ist eine Verbindung zu einer [EChartsGateway-Instanz](../EChartsGateway).
Für historische Daten werden aufgezeichnete Werte im Symcon-Archiv benötigt;
ein reines Momentanwert-Widget benötigt keine Historie. IPSView soll nur für
den zusätzlichen IPSView-Ausgabeweg erforderlich sein, nicht für native Kacheln.

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

**Zielmodell:** Eine EChartsGauge-Instanz je unabhängig konfigurierbarem
Gauge-Chart. Mehrere Gauge-Instanzen sollen ein gemeinsames Gateway verwenden
können. Für die parallele Anzeige desselben Charts als Kachel und in IPSView
ist keine zweite Gauge-Instanz vorgesehen.

**Aktueller Stand:** Der Code verbindet sich über `RequireParent()` mit einer
neu erzeugten Gateway-Instanz, sofern noch keine Verbindung besteht. Auch ein
bereits vorhandenes Gateway verhindert dessen Neuanlage nicht. Dieser
Generatorcode ist vor der vorgesehenen komfortablen Mehrfachanlage anzupassen.
Siehe [offizielle RequireParent-Dokumentation](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/requireparent/).

**Konfigurationsseite:** Noch keine Variablenauswahl, Diagrammeinstellungen
oder Ausgabeschalter vorhanden.

### 5. Statusvariablen und Profile

#### Statusvariablen

Aktuell werden noch keine eigenen Variablen angelegt. Für IPSView ist künftig
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

Es ist noch keine fachliche PHP-Befehlsschnittstelle für Charts freigegeben.
Das festgelegte Funktionspräfix lautet `ECGA`.

`Send()` und `ReceiveData()` stammen noch aus der generierten Vorlage. Sie
implementieren kein vollständiges Chart-Protokoll und sind nicht als stabile
Anwenderbefehle zu verwenden. Die dokumentierte Schnittstelle wird mit der
jeweiligen tatsächlichen Implementierung ergänzt.

Weitere Projektgrundsätze: [Entwicklung](../docs/ENTWICKLUNG.md).  
Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).
