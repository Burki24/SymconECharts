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
Konfigurationsformular wählt zwischen vier layoutbezogenen Presets, Zeigerformen,
Skalenbögen und optionalen Gauge-Farben und zeigt
zusätzlich eine live aktualisierte SVG-Vorschau der noch nicht übernommenen
Einstellungen. Davon unabhängig stehen das automatische Symcon-Design und
sechs lokal gebündelte offizielle ECharts-Themes zur Auswahl.
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

**Vorhanden:** Moduldefinition als Gerät (`type: 3`) auf Basis von
`IPSModuleStrict`, wiederverwendbare Verbindung zu EChartsGateway,
Variablenauswahl, Wertebereich, Titel, Einheit und Nachkommastellen. Die
Quellvariable wird als Symcon-Referenz registriert und auf Wertänderungen
überwacht. `GetGaugeData()` liest den aktuellen Wert über das versionierte
Gateway-Protokoll und liefert ein familienbezogenes JSON-Datenmodell. Die
native HTML-SDK-Kachel stellt dieses Modell als responsive Gauge dar und
übernimmt im Theme-Modus `auto` Symcon-Farbvariablen für helle und dunkle
Oberflächen. Der
Kacheldesigner bietet Basic, Simple, Progress und Speed; Simple ist aus
Kompatibilitätsgründen die Standardauswahl und entspricht der bisherigen
Darstellung. Die unabhängige Theme-Auswahl bietet `auto`, Dark, Vintage,
Macarons, Infographic, Shine und Roma. Alle sechs Apache-Themes werden lokal
und mit festgeschriebener Integrität ausgeliefert.

Das Speed-Preset übernimmt die charakteristischen Layoutmerkmale des
offiziellen Apache-ECharts-Beispiels: den eigenen SVG-Zeiger, Schatten an
Fortschritt und Zeiger, zwei Unterteilungen je Hauptabschnitt sowie getrennte
Typografie für Wert und Einheit. Der Wertebereich bleibt frei konfigurierbar;
die Hauptteilung wird dafür auf gut lesbare Schritte angepasst. Farben stammen
weiterhin aus dem unabhängig gewählten Theme und der optionale Instanztitel
bleibt erhalten.

Die Feinabstimmung im Kacheldesigner skaliert Schriftgrößen für Skala, Wert,
Einheit und Titel sowie Ringstärke, Zeigerstärke, Zeigerlänge, Nebenstriche und
Hauptteiler relativ zur gewählten Vorlage. `100 %` entspricht immer der getesteten Presetvorgabe;
zulässig sind 50 bis 150 %. Dadurch bleiben individuelle Einstellungen auch
bei anderen Kachelgrößen responsiv. Freie ECharts-JSON- oder
JavaScript-Einstellungen werden nicht ausgeführt.

Zusätzlich kann die Zeigerform als Presetvorgabe, Nadel, Linie, Pfeil oder
importiertes SVG gewählt werden. Der SVG-Import akzeptiert ausschließlich
eine `viewBox` und reine Pfadgeometrie aus bis zu 32 `path`-Elementen. Das
SVG darf höchstens 128 KiB groß sein; Transformationen, Skripte, Ereignisse,
externe Referenzen und andere Elemente werden nicht übernommen. An ECharts
wird niemals das SVG-Dokument, sondern nur der validierte Pfad übergeben.
Eine eng an der Pfadgeometrie liegende `viewBox` erhält bei `100 %`
Zeigerstärke die ursprünglichen Proportionen; die Spitze zeigt im SVG nach
oben. Optional legt
`data-echarts-pivot="x y"` am Wurzel-SVG den Drehpunkt in viewBox-Koordinaten
fest; ohne diese Angabe wird unten mittig verwendet. Alternativ kann der
Drehpunkt im Kacheldesigner horizontal und vertikal mit 0 bis 100 % der
`viewBox` festgelegt werden. Diese benutzerdefinierte Justierung überschreibt
den SVG-Drehpunkt und hält die native ECharts-Nabe sichtbar, sodass sie etwa
mittig über einem gezeichneten Zeigerring liegen kann. Im kompatiblen Modus
`SVG oder unten mittig` bleibt ein ausdrücklich im SVG gesetzter Drehpunkt
maßgeblich und die SVG-Geometrie zeichnet ihre eigene Nabe.
Die sichtbare Nabe bleibt stets an diesem Drehpunkt gebunden. Ihre Form kann
bei der Presetvorgabe bleiben, als Kreis oder Ring gezeichnet, ausgeblendet
oder über ein eigenes pfadbasiertes SVG festgelegt werden. Für das Naben-SVG
gelten dieselben Größen- und Sicherheitsgrenzen wie für den Zeigerimport.
Größe und Randstärke werden mit 50 bis 150 % relativ zur Presetvorgabe
skaliert. Füllung und Rand folgen standardmäßig den Gauge- beziehungsweise
Theme-Farben und lassen sich optional unabhängig überschreiben.
Eine optionale Zifferblattplatte wird als native ECharts-Graphic hinter allen
Gauge-Elementen gezeichnet. Sie kann kreisförmig einschließlich Wert und Titel
oder als an Start und Ende des Skalenbogens angepasste Fläche erscheinen.
Größe und Randstärke sind mit 50 bis 150 % skalierbar; Füllung und Rand folgen
wahlweise dem Theme oder eigenen Farben. Die Füllung kann unabhängig davon
transparent sein, und ein optionaler responsiver Schatten hebt die Platte vom
Kachelhintergrund ab. `Ausgeblendet` bleibt der kompatible Standard und
deaktiviert sämtliche anderen Platteneinstellungen. Damit Farben, Größe, Rand,
Transparenz oder Schatten sichtbar werden, muss zuerst `Kreis` oder
`Dem Skalenbogen folgen` als Plattenform gewählt werden. `100 %` ist die
empfohlene Ausgangsgröße. `150 %` vergrößert den Radius tatsächlich auf das
Eineinhalbfache und kann den Kreis deshalb deutlich über den Gauge-Bereich
hinaus bis an den Kachelrand führen.
Bei eigenen Plattenfarben stehen zusätzlich Vollfarbe sowie lineare und
radiale Farbverläufe zur Verfügung. Beide Verlaufsarten verwenden die
Plattenfüllung als Startfarbe und eine eigene Endfarbe; optional wird bei
50 % eine Mittelfarbe eingefügt. Lineare Verläufe besitzen vier feste
Richtungen. Beim radialen Verlauf sind horizontaler und vertikaler Mittelpunkt
von 0 bis 100 % sowie der Radius von 25 bis 150 % einstellbar. Die
Formularvorschau bildet dieselben Verlaufsparameter mit SVG-Verläufen nach.
Zusätzlich kann ein eigenes SVG als Hintergrundmotiv über Vollfarbe oder
Verlauf gelegt werden. Das Motiv wird auf die gewählte Plattenform begrenzt
und lässt sich einpassen, flächenfüllend beschneiden oder strecken. Größe,
horizontaler und vertikaler Versatz, Deckkraft und Drehung bleiben unabhängig
einstellbar. Der Import akzeptiert bis zu 256 KiB große SVGs mit `viewBox`,
sicheren Grundformen, Pfaden, Gruppen, Transformationen und SVG-Verläufen.
Skripte, Ereignisattribute, externe Referenzen, Bilder, Texte, CSS-Blöcke und
`foreignObject` werden abgewiesen. Farben innerhalb des SVG folgen nicht
automatisch dem ECharts-Theme.
Der Skalenbogen bleibt wahlweise bei der Presetvorgabe oder
wird als Voll-, Dreiviertel-, Halb-, Viertelkreis beziehungsweise mit eigenem
Start und Ende definiert. Die Positionen folgen einem Zifferblatt (`0°` oben,
`90°` rechts, `180°` unten, `270°` links) und stehen in 22,5-Grad-Schritten
zur Verfügung. Eigene Farben für Zeiger, Fortschritt, Ring, Skala, Wert und
Titel sind ausdrücklich zuschaltbar; ohne diese Umschaltung bleiben die
Farben des ausgewählten ECharts-Themes maßgeblich.

**Geplant:** Zusätzliche Gauge-Single-Presets, ein sicherer Importvertrag für
eigene Theme-Builder-Dateien, weitere Gestaltungsoptionen sowie die
Ausgabe als optionales IPSView-Widget.
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
Historie. Für die Bereinigung eigener SVG-Hintergründe wird die PHP-DOM-
Erweiterung benötigt. IPSView soll nur für den zusätzlichen
IPSView-Ausgabeweg erforderlich sein, nicht für native Kacheln.

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
Minimum und Maximum, Titel, Einheit, 0 bis 6 Nachkommastellen, ein Gauge-Preset
sowie ein ECharts-Theme. Unter „Geometrie und Zeiger“ werden Zeigerform,
eine optionale SVG-Datei, Skalenbogen und Positionen gewählt; unter „Gauge-Farben“ können sechs
Gauge-spezifische Farbrollen das Theme gezielt überschreiben. Das
„Nabendesign“ steuert Form, optionales SVG, Größe, Randstärke und Farben der
Nabe, ohne ihren Drehpunkt vom Zeiger zu lösen. „Zifferblattplatte“ steuert
Form, Transparenz, Theme- oder eigene Farben, Größe, Rand und Schatten der
Hintergrundfläche sowie Vollfarbe, linearen oder radialen Verlauf und ein
optional darüberliegendes, zugeschnittenes SVG-Motiv. Solange
ihre Form auf `Ausgeblendet` steht, haben die übrigen Plattenfelder bewusst
keine sichtbare Wirkung. Unter
„Feinabstimmung“ können die vier Schriftgrößen, Ringstärke, Zeigerstärke und
Zeigerlänge sowie Nebenstrich- und Hauptteilerlänge jeweils von 50 bis 150 %
der Presetvorgabe angepasst werden. Für eigene SVG-Zeiger lässt sich zusätzlich
der Drehpunkt in Prozent der `viewBox` justieren. Die stabilen Preset-IDs sind
`basic`, `simple`,
`progress` und `speed`. Als Themes stehen `auto`, `dark`, `vintage`,
`macarons`, `infographic`, `shine` und `roma` zur Verfügung. `auto` ist der
kompatible Standard und folgt dem Symcon-Design. Die Aktion
„Aktuelle Gauge-Daten lesen“ gibt das gegenwärtige JSON-Datenmodell zu
Diagnosezwecken aus. Minimum muss kleiner als Maximum sein. Eine SVG-Vorschau
reagiert unmittelbar auf Änderungen im geöffneten Formular, ohne diese Werte
vorzeitig zu speichern. Bei einer gültigen numerischen Quellvariable verwendet
sie deren aktuellen Wert, andernfalls die Mitte des konfigurierten Bereichs.

**Zifferblattplatte einstellen:**

1. Zuerst die Plattenform auf `Kreis` oder `Dem Skalenbogen folgen` stellen.
   Bei `Ausgeblendet` bleiben alle nachfolgenden Einstellungen ohne sichtbare
   Wirkung.
2. Für eigene Farben die Farbquelle auf `Eigene Plattenfarben` umstellen.
   Bei aktivierter transparenter Füllung bleibt die gewählte Füllfarbe
   erwartungsgemäß unsichtbar; Randfarbe und Schatten wirken weiterhin.
3. Für einen Verlauf den Füllstil auf `Linearer Farbverlauf` oder `Radialer
   Farbverlauf` stellen. Die Plattenfüllung ist die Startfarbe; anschließend
   Endfarbe und optional die Mittelfarbe festlegen. Richtung gilt nur für den
   linearen, Mittelpunkt und Radius gelten nur für den radialen Verlauf.
4. Mit `100 %` Plattengröße beginnen. Der Kreis umfasst dabei bewusst auch
   Wert und Titel. Größere Werte skalieren den gesamten Radius; `150 %` kann
   deshalb bis an oder über den Kachelrand reichen.
5. Für ein SVG-Motiv `SVG-Hintergrund der Platte verwenden` aktivieren und
   eine `.svg`-Datei auswählen. `Einpassen` zeigt das vollständige Motiv,
   `Ausfüllen` füllt die Platte ohne Verzerrung und beschneidet Überstände,
   `Strecken` füllt sie gegebenenfalls mit verändertem Seitenverhältnis.
   Größe, Versatz, Deckkraft und Drehung wirken nur auf das Motiv; die zuvor
   gewählte Farbe oder der Verlauf bleibt darunter erhalten.
6. Die Formularvorschau reagiert sofort. Erst `Übernehmen` speichert die
   Auswahl und aktualisiert damit die native Kachel dauerhaft.

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
Die Kachel skaliert dazu eine gemeinsame virtuelle Referenzfläche anhand ihrer
tatsächlichen Breite und Höhe. Fortschrittsbogen und Achslinie bleiben dadurch
deckungsgleich; Teilstriche, Hauptteiler und Skalenwerte behalten auch bei
unterschiedlichen Seitenverhältnissen ihre festgelegte radiale Reihenfolge.
Quadratische Kacheln nutzen dabei einen größeren Anteil der verfügbaren Breite.
Die Quellvariable wird über `VM_UPDATE` beobachtet; neue Werte gelangen über
den HTML-SDK-Nachrichtenkanal in die bestehende Kachel, ohne das gesamte
HTML-Dokument neu aufzubauen. Bei ungültiger Konfiguration oder fehlendem
Gateway zeigt die Kachel einen übersetzten Hinweis statt eines leeren Charts.

Die Darstellung verwendet die lokal mitgelieferte und per SHA-256 geprüfte
Apache-ECharts-Runtime 6.1.0. Die sechs offiziellen Theme-Dateien stammen aus
derselben festgeschriebenen Abhängigkeit und werden ebenfalls vor der
Einbettung geprüft. Die Visualisierung lädt im Betrieb keine CDN-Ressource.
Die aktuelle Implementierung ist technisch gegen Test-Doppel geprüft; die
visuelle Prüfung in realen Symcon-Clients bleibt offen.

#### Kacheldesigner und Konfigurationsvorschau

Die Vorschau im Kacheldesigner ist ein leichtgewichtiges, eigenständiges SVG
und lädt nicht die vollständige ECharts-Browser-Runtime. Dadurch bleibt das
Formular schnell und die noch nicht gespeicherte Konfiguration kann unmittelbar
dargestellt werden. Die Kachel bleibt für die tatsächliche, interaktive
ECharts-Ausgabe maßgeblich; die SVG-Vorschau bildet das ausgewählte Layout
gezielt nach. Die grundlegenden Layoutproportionen werden zwischen Vorschau und
Kachel angeglichen, einschließlich der Anordnung von Achslinie, Teilern und
Skalenwerten. Noch nicht gespeicherte Geometrie-, Platten-, Farb- und
Feinabstimmungen werden ebenfalls sofort
in der Vorschau dargestellt. Sie verwendet außerdem eine stabile, dem
ausgewählten Theme
zugeordnete Vorschaupalette und übernimmt die Gauge-Achssegmente der
offiziellen Themes. Die native Kachel registriert dagegen die unveränderte
offizielle Theme-Datei und bleibt für die exakte Darstellung maßgeblich. Für
unsere vom Originalbeispiel abweichenden Layouts ergänzt sie Hintergrund-,
Kontrast- und identische Achsfarben aus dem gemeinsamen Theme-Katalog.
Layout-Preset und Theme können frei kombiniert werden.

Der offizielle ECharts Theme Builder erzeugt ausführbare JavaScript-Dateien.
Eigene Builder-Dateien werden in dieser Stufe noch nicht importiert oder
ausgeführt; dafür ist vorab ein eigener validierter und ausdrücklich
bestätigter Importvertrag erforderlich.

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
`schemaVersion`, `family`, Theme, Quellvariable und Zeitstempel, die minimale
Gauge-Konfiguration sowie die validierten Geometrie-, Farb- und relativen
Designwerte unter `gauge.style`. Bei einem eigenen SVG-Zeiger enthält das
Modell nur die validierten Pfaddaten und die `viewBox`, nicht das hochgeladene
SVG-Dokument. Außerdem enthält es den numerischen Wert. Dasselbe fachliche Modell wird
vom nativen Gauge-Single-Renderer verwendet.

Weitere Projektgrundsätze: [Entwicklung](../docs/ENTWICKLUNG.md).  
Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).
