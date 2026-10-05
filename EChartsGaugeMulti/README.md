# EChartsGaugeMulti

Gerätemodul der SymconECharts-Library für einen zusammengesetzten Gauge-Chart
mit mehreren Werten oder Zeigern. Eine Gauge-Multi-Instanz soll die native
Symcon-Kacheldarstellung und ein separat platzierbares HTML-Widget in IPSView
bereitstellen können. Datenquellen und Diagrammkonfiguration werden dabei nur
einmal gepflegt.

**Entwicklungsstand:** Multi-Title-, zwei Ring-, Wetterstations- und Tacho-Vorlage mit optionaler
IPSView-WebContent-Ausgabe. Eine geordnete Liste aus
2 bis 16 numerischen Quellvariablen ist konfigurierbar, wird als Referenzen
registriert und über EChartsGateway gelesen. Das Modul erzeugt daraus ein
versioniertes Multi-Datenmodell und rendert jede Quelle mit eigener Skala,
Einheit und Wertanzeige in einem responsiven Instrumentenraster. Beide
Ausgaben verwenden dieselben Quellen und denselben Renderer; IPSView kann
das Kacheldesign erben oder ein eigenes Design verwenden.

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

**Vorhanden:** Multi Title, Ringraster, konzentrische Ringe, Wetterstation und
Tacho-Cockpit als native
Symcon-Kachel und optionales IPSView-Widget mit offiziellen ECharts-Themes,
Live-Aktualisierung und erster Feinabstimmung.

**Geplant:** Weitere klassische Instrumentenpanel-Vorlagen.

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

Optional kann jeder Listeneintrag Wertebereich, Einheit und Nachkommastellen
aus der nativen Variablendarstellung übernehmen. Kompatible Legacy-Profile
werden ebenfalls ausgewertet. Fehlen einzelne gültige Darstellungsangaben,
bleiben die manuellen Werte der betreffenden Zeile als Rückfall erhalten.

### 3. Software-Installation

Das Gauge-Modul wird gemeinsam mit EChartsGateway über die Library
**SymconECharts** eingebunden. Es gibt kein separat zu installierendes
Gerätepaket.

Für Entwicklungsarbeiten im Module Control das Repository hinzufügen und
`dev` auswählen:

```text
https://github.com/Burki24/SymconECharts
```

Die Beschreibung gilt für den Entwicklungsstand der ersten sichtbaren
Multi-Kachel. Eine Veröffentlichung im Module Store wird nicht vorausgesetzt.

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
Nachkommastellen festgelegt. Mit **Variablendarstellung verwenden** können
Wertebereich, Einheit und Nachkommastellen stattdessen aus der Quellvariable
übernommen werden. Eine leere Beschriftung verwendet automatisch den Namen der
Symcon-Variable. Die Reihenfolge bestimmt später die Zuordnung zu den Positionen
der gewählten Multi-Vorlage.

Das in Version 1.8 kurzzeitig enthaltene Einzelquellen-Gerüst wird nicht
automatisch übernommen: Eine einzelne Quelle erfüllt den Multi-Vertrag nicht.
Bereits angelegte Entwicklungsinstanzen müssen ihre Quellenliste neu
konfigurieren.

### 5. Statusvariablen und Profile

#### Statusvariablen

Standardmäßig werden keine eigenen Variablen angelegt. Bei aktivierter
IPSView-Ausgabe entsteht eine Stringvariable mit WebContent-Darstellung und
vollständigem HTML-Inhalt. Die native Kachel benötigt diese Variable nicht.

Quellvariablen werden als Referenzen registriert und bei Änderungen der Liste
deterministisch ergänzt oder entfernt. Messwertkopien unter dem Gauge-Modul
werden nicht angelegt. Beim Abschalten der IPSView-Ausgabe bleibt eine
bestehende HTML-Variable erhalten. Sie wird nur über die ausdrücklich
bestätigte Löschfunktion des gemeinsamen Helpers entfernt.

#### Profile

Aktuell werden noch keine Profile oder eigenen Variablendarstellungen angelegt.
Die künftige Ausgabe soll auf den gemeinsamen Variablen- und
Darstellungs-Helpern aufbauen.

### 6. Visualisierung

**Fünf Multi-Vorlagen und die optionale IPSView-Ausgabe sind implementiert.**

#### Native Symcon-Kachel

Die Chart-Instanz wird über das Symcon-HTML-SDK dargestellt und benötigt keine
IPSView-Ausgabe. Jede Quelle besitzt eine eigene ECharts-Gauge-Serie mit
eigenem Wertebereich. **Multi Title** zeigt Zifferblätter, **Ringraster**
einzelne Fortschrittsringe und **konzentrische Ringe** ein gemeinsames
Ringinstrument mit einer beschrifteten Werteliste. **Wetterstation** zeigt ein
analoges Instrumentenpanel: Die erste Quelle wird als großes Hauptinstrument
dargestellt, die übrigen als kleinere Zifferblätter. Das ist keine feste
Zuordnung zu Druck, Temperatur oder Feuchte; die Reihenfolge der Quellenliste
bestimmt die Platzierung. Alle Instrumente behalten ihren individuellen
Wertebereich und ihre Einheit. **Tacho-Cockpit** ordnet die erste Quelle als
großes mittleres Instrument an, die zweite links und die dritte rechts.
Weitere Quellen erscheinen in einem zusätzlichen Raster darunter. Die
Vorlage übernimmt die Idee heller Skalen mit roten Zeigern aus dem
beigefügten ECharts-Beispiel, aber keine dort fest eingetragenen Werte,
Einheiten oder Beschriftungen. Auf schmalen Flächen rücken die beiden
Nebeninstrumente unter das Hauptinstrument. Für einen dunklen Look das
ECharts-Theme **Dark** wählen; dessen Hintergrund ist nicht rein schwarz.
Raster-Vorlagen wählen
abhängig von Quellenanzahl, Breite und Höhe eine passende Spalten- und
Zeilenaufteilung. Multi Title und Ringraster füllen ihre Zellen in Kachel und
IPSView stärker aus, ohne die Instrumente zu strecken. Änderungen einer
Quellvariable werden über `VM_UPDATE` direkt an die Kachel übertragen.
Das Multi-Raster lässt in der nativen Kachel Platz für die von Symcon
eingeblendete Kopfzeile. Wird der Objekttitel unter Symcon 9.1 ausgeblendet,
nutzt es den frei werdenden Platz automatisch. Unter Symcon 9.0 bleibt der
Abstand wie bisher erhalten; IPSView nutzt die gesamte Widgetfläche.

Der eingeklappte Kacheldesigner bietet die fünf Vorlagen, das automatische
Symcon-Design und sechs gebündelte offizielle ECharts-Themes. Ringstärke sowie
Schriftgrößen von Skala, Wert und Titel sind zwischen 50 und 150 Prozent
einstellbar; Skalenbeschriftungen betreffen nur Multi Title. Eine SVG-Vorschau
zeigt bis zu vier Quellen exemplarisch. Bei vielen konzentrischen Ringen oder
Wetterstations- oder Tacho-Instrumenten ist für lesbare Beschriftungen eine
ausreichend große Kachel bzw. ein großes IPSView-Widget erforderlich. Die
Tacho-Vorschau zeigt die ersten drei Quellen; zur Laufzeit werden alle 2 bis
16 konfigurierten Quellen ausgegeben.
Die Feinabstimmung des Multi-Designers ist noch nicht abgeschlossen; insbesondere
Zeiger, Skalen und Zifferblätter bieten noch nicht die Einstellmöglichkeiten
von Gauge Single.

#### IPSView

Im Abschnitt **IPSView-Design** wird die WebContent-Ausgabe aktiviert. Die
erzeugte Variable wird in IPSView als HTML-Widget platziert; Größe und Position
bestimmt IPSView. Standardmäßig erbt sie das Kacheldesign. Für eine abweichende
Gestaltung kann das Kacheldesign kopiert und der unabhängige IPSView-Designer
bearbeitet werden. Beide Ausgaben behalten dieselben Quellen, Wertebereiche,
Einheiten und Live-Daten.

Die HTML-Erzeugung und Aktualisierung bei Quellwertänderungen sind lokal
getestet. Die tatsächliche Einbindung in IPSView bleibt mangels Lizenz auf der
verfügbaren Testinstallation ungeprüft.

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
beiden Ausgabeadapter und noch kein gerendertes ECharts-Diagramm.
`ECGM_GetIPSViewHTML($InstanceID)` liefert die eigenständige HTML-Seite für IPSView.

Weitere Projektgrundsätze: [Entwicklung](../docs/ENTWICKLUNG.md).  
Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).

