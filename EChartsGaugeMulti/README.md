# EChartsGaugeMulti

Gerätemodul der SymconECharts-Library für einen zusammengesetzten Gauge-Chart
mit mehreren Werten oder Zeigern. Eine Gauge-Multi-Instanz soll die native
Symcon-Kacheldarstellung und ein separat platzierbares HTML-Widget in IPSView
bereitstellen können. Datenquellen und Diagrammkonfiguration werden dabei nur
einmal gepflegt.

## Animation

Im Kachel- und unabhängigen IPSView-Designer steuern **Animation**, **Startanimation** und **Aktualisierungsanimation** die ECharts-Übergänge. Beide Zeiten sind von 0 bis 3000 ms einstellbar; 0 ms unterdrückt den jeweiligen Übergang. Die Systemeinstellung für reduzierte Bewegung schaltet Animationen immer ab. Bei **Kacheldesign verwenden** übernimmt IPSView die Kachelwerte.

Multi-Title-, zwei Ring- und Wetterstations-Vorlage mit optionaler
IPSView-WebContent-Ausgabe. Eine geordnete Liste aus
2 bis 16 numerischen Quellvariablen ist konfigurierbar, wird als Referenzen
registriert und über EChartsGateway gelesen. Das Modul erzeugt daraus ein
versioniertes Multi-Datenmodell und rendert jede Quelle mit eigener Skala,
Einheit und Wertanzeige in einem responsiven Instrumentenraster. Beide
Ausgaben verwenden dieselben Quellen und denselben Renderer; IPSView kann
das Kacheldesign erben oder ein eigenes Design verwenden.
Der IPSView-Designer kann den Dokumenthintergrund transparent schalten und
eine automatische Theme-Farbe oder eigene Hintergrundfarbe mit einstellbarer
Deckkraft über den tatsächlichen IPSView-Hintergrund legen.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in Symcon](#4-einrichten-der-instanzen-in-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [Visualisierung](#6-visualisierung)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

Das Modul besitzt eine wiederverwendbare Verbindung zu EChartsGateway. Jede
konfigurierte Quelle besitzt eine eindeutige
numerische Variable, Beschriftung, Minimum, Maximum, Einheit und 0 bis 6
Nachkommastellen. `GetGaugeData()` liest alle aktuellen Werte über das
versionierte Gateway-Protokoll und liefert sie in der konfigurierten Reihenfolge.
Die Variablen-ID bleibt dabei die technische Identität jedes Instruments.
Gleichlautende Beschriftungen bleiben unverändert sichtbar; die Variablen-IDs
unterscheiden die Instrumente ausschließlich intern.

Multi Title, Ringraster, konzentrische Ringe und Wetterstation stehen als native
Symcon-Kachel und optionales IPSView-Widget mit offiziellen ECharts-Themes,
Live-Aktualisierung und erster Feinabstimmung.

Eine Instanz bildet genau einen zusammengesetzten Gauge-Chart ab. Gauges mit
genau einer numerischen Quellvariable gehören zu
[EChartsGaugeSingle](../EChartsGaugeSingle).

### 2. Voraussetzungen

Erforderlich sind **Symcon 9.0 oder 9.1 und PHP 8.5**. Eine Kompatibilität zu
älteren Symcon-Versionen wird nicht versprochen.

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

Die Library wird in der Modulverwaltung über folgende Repository-URL
installiert:

```text
https://github.com/Burki24/SymconECharts
```

### 4. Einrichten der Instanzen in Symcon

Eine EChartsGaugeMulti-Instanz wird je unabhängig konfigurierbarem
zusammengesetztem Gauge-Chart. Mehrere Gauge-Instanzen sollen ein gemeinsames
Gateway verwenden können. Für die parallele Anzeige desselben Charts als
Kachel und in IPSView ist keine zweite Gauge-Instanz vorgesehen.

Bei der ersten ECharts-Diagramminstanz wird ein EChartsGateway neu angelegt.
Bei allen weiteren Gauge- oder anderen ECharts-Diagramminstanzen wird dieses
vorhandene Gateway ausgewählt. Symcon bietet aufgrund des Verbindungstyps
zusätzlich weiterhin eine Neuanlage an; sie ist für den regulären Betrieb nicht
notwendig.

Über die kompatiblen Parent-Verbindungen kann eine
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
Bereits angelegte Instanzen dieses unveröffentlichten Zwischenstands müssen ihre Quellenliste neu
konfigurieren.

### 5. Statusvariablen und Profile

#### Statusvariablen

Standardmäßig werden keine eigenen Variablen angelegt. Bei aktivierter
IPSView-Ausgabe entsteht eine Stringvariable mit WebContent-Darstellung und
vollständigem HTML-Inhalt. Die native Kachel benötigt diese Variable nicht.
Wertänderungen werden anschließend über das gemeinsame Gateway an das
bestehende Diagramm übertragen; die Stringvariable bleibt dabei unverändert.

Quellvariablen werden als Referenzen registriert und bei Änderungen der Liste
deterministisch ergänzt oder entfernt. Messwertkopien unter dem Gauge-Modul
werden nicht angelegt. Beim Abschalten der IPSView-Ausgabe bleibt eine
bestehende HTML-Variable erhalten. Sie wird nur über die ausdrücklich
bestätigte Löschfunktion des gemeinsamen Helpers entfernt.

#### Profile

Das Modul legt keine Profile oder eigenen Variablendarstellungen an.

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
Wertebereich und ihre Einheit. Tacho und Chronograph sind wegen ihrer quellbezogenen Einzelgestaltung eigenständige Module; siehe `EChartsGaugeTacho` und `EChartsGaugeChronograph`. Kachel und eigenständiges IPSView-Design können die Vorlage
unabhängig wählen. Für gut lesbare Nebenskalen ist eine große Kachel sinnvoll.
Raster-Vorlagen wählen
abhängig von Quellenanzahl, Breite und Höhe eine passende Spalten- und
Zeilenaufteilung. Multi Title und Ringraster füllen ihre Zellen in Kachel und
IPSView stärker aus, ohne die Instrumente zu strecken. Änderungen einer
Quellvariable werden über `VM_UPDATE` direkt an die Kachel übertragen.
Das Multi-Raster lässt in der nativen Kachel Platz für die von Symcon
eingeblendete Kopfzeile. Wird der Objekttitel unter Symcon 9.1 ausgeblendet,
nutzt es den frei werdenden Platz automatisch. Unter Symcon 9.0 bleibt der
Abstand wie bisher erhalten; IPSView nutzt die gesamte Widgetfläche.

Der eingeklappte Kacheldesigner bietet die vier Vorlagen, das automatische
Symcon-Design und sechs gebündelte offizielle ECharts-Themes. Eine gemeinsame
Designschicht gestaltet alle Instrumente einer Ausgabe: Zeigerform, Zeigerlänge
und -stärke, Nabenform und -größe, Haupt- und Nebenunterteilungen sowie die
Farbrollen für Zeiger, Fortschritt, Ring, Skala, Wert, Titel und Nabe. Für jedes
Zifferblatt kann außerdem die Vorlagenplatte beibehalten, ausgeblendet oder
durch eine gemeinsam gestaltete runde Platte ersetzt werden. Zeiger können
alternativ als bereinigte SVG-Pfade importiert und mit einem SVG-eigenen oder
prozentual gesetzten Ankerpunkt ausgerichtet werden. Eine benutzerdefinierte
Platte kann zusätzlich ein bereinigtes, zugeschnittenes SVG-Motiv mit
einstellbarer Anpassung, Größe, Deckkraft, Drehung und Position erhalten. Die
Import- und Sicherheitsregeln sind mit Gauge Single über den zentralen
ECharts-Gauge-Design-Helper identisch. Ringstärke,
Schriftgrößen und geometrische Feinabstimmungen sind zwischen 50 und 150 Prozent
einstellbar. Die Standardwerte `preset` und `theme` erhalten die bisherige
Darstellung vollständig. Die SVG-Vorschau übernimmt die aktuell bearbeiteten
Designwerte sofort und wendet sie auf jedes dargestellte Instrument an. Sie
zeigt bis zu vier Quellen exemplarisch. Bei vielen konzentrischen Ringen oder Wetterstations-Instrumenten ist für lesbare Beschriftungen eine ausreichend große Kachel bzw. ein großes IPSView-Widget erforderlich. Zur Laufzeit werden alle 2 bis 16 konfigurierten Quellen ausgegeben.
Die gemeinsamen Einstellungen einschließlich der SVG-Assets gelten für alle
Quellen der jeweiligen Ausgabe. Ein individuelles Design je Quelle bieten die
eigenständigen Module Gauge Tacho und Gauge Chronograph.

#### IPSView

Im Abschnitt **IPSView-Design** wird die WebContent-Ausgabe aktiviert. Die
erzeugte Variable wird in IPSView als HTML-Widget platziert; Größe und Position
bestimmt IPSView. Standardmäßig erbt sie das Kacheldesign. Für eine abweichende
Gestaltung zuerst **Kacheldesign verwenden** ausschalten. Solange es aktiv ist,
stehen die eigenen Designfelder und der Knopf **Kacheldesign nach IPSView
kopieren und unabhängig bearbeiten** direkt im Abschnitt **IPSView-Design**,
sind aber deaktiviert.
Nach dem Ausschalten werden die Eingaben und der Knopf sofort aktiv; dieser
kopiert das gespeicherte Kacheldesign einmalig. Bisherige unabhängige
IPSView-Designwerte werden dabei überschrieben. Anschließend kann der
IPSView-Designer separat bearbeitet werden. Beide Ausgaben behalten dieselben
Quellen, Wertebereiche, Einheiten und Live-Daten.
Änderungen am Kacheldesigner vor dem Kopieren speichern.

#### Gemeinsame Darstellung

ECharts-Konfiguration, Datenaufbereitung und Zeichenlogik werden gemeinsam
verwendet. Ausgabespezifische Anpassungen wie Hintergrund, Transparenz
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

Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).

