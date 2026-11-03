# EChartsGaugeChronograph

`EChartsGaugeChronograph` rendert zwei bis fünf numerische Symcon-Variablen in
einem gemeinsamen Chronographen. Die erste Quelle bildet das Hauptinstrument;
bis zu vier weitere Quellen erscheinen als eingebettete Hilfszifferblätter.

Voraussetzungen und Installation: [SymconECharts](../README.md#voraussetzungen)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartsgaugechronograph).

## Beschriftungsoptionen

Skalenbeschriftungen lassen sich um -100 bis +100 px relativ zur Vorlage verschieben. Titel und Werte können separat horizontal und vertikal um -100 bis +100 % versetzt werden; null bewahrt die bisherige Vorlagengeometrie.

Die Optionen gelten für Kacheln und – bei unabhängigem Design – auch für
IPSView. Bei geerbtem Design folgt IPSView den Kacheleinstellungen. Im
Quelleneditor können diese Abstände und Verschiebungen bei aktivem
individuellem Gauge-Design zusätzlich pro Instrument festgelegt werden.

## Vorübergehende Ladefehler

Können Werte kurzzeitig nicht geladen werden, bleibt die letzte gültige Grafik
in Kachel und IPSView sichtbar. Bei wiederholtem Fehler erscheint ein Hinweis;
das Modul versucht nach 60 Sekunden erneut zu laden. Ohne zuvor geladene Daten
oder bei ungültiger Konfiguration erscheint weiterhin eine Fehlermeldung.

Kachel- und unabhängiger IPSView-Designer ordnen Diagrammtitel, Feinabstimmung,
Zeiger und Nabe, Skala und Farben sowie Zifferblatt in einklappbaren Bereichen.
In **Diagrammtitel** lassen sich Größe (50–150 %) und Farbe des äußeren Titels
einstellen. **Automatisch (Theme)** erhält die bisherige Titelfarbe. Die
Beschriftungen der einzelnen Instrumente werden weiterhin separat gestaltet.
Bei **Kacheldesign verwenden** übernimmt IPSView auch diese Titelwerte.

## Animation

Für Start und Aktualisierung lassen sich außerdem die **Bewegungskurve** und
eine feste **Verzögerung** (0–3000 ms) getrennt wählen. Ohne Änderung gilt
jeweils cubicInOut ohne Verzögerung. Die Verzögerung betrifft das ganze Diagramm,
nicht jeden Datenpunkt einzeln.

Im Kachel- und unabhängigen IPSView-Designer steuern **Animation**, **Startanimation** und **Aktualisierungsanimation** die ECharts-Übergänge. Beide Zeiten sind von 0 bis 3000 ms einstellbar; 0 ms unterdrückt den jeweiligen Übergang. Die Systemeinstellung für reduzierte Bewegung schaltet Animationen immer ab. Bei **Kacheldesign verwenden** übernimmt IPSView die Kachelwerte.

## Voraussetzungen

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5;
- eine aktive [EChartsGateway-Instanz](../EChartsGateway/README.md);
- zwei bis fünf Integer- oder Float-Variablen als Datenquellen;
- IPSView nur für das optionale WebContent-Widget.

## Installation und Einrichtung

Das Modul wird mit der Library **SymconECharts** installiert. Für jeden
Chronographen wird eine EChartsGaugeChronograph-Instanz angelegt und mit dem
gemeinsamen Gateway verbunden. Anschließend werden die Quellen in der
gewünschten Anzeigereihenfolge hinzugefügt.

## Konfiguration

Jede Quellenzeile enthält Variable, Beschriftung, Minimum, Maximum, Einheit
und Nachkommastellen. Optional werden Wertebereich, Einheit und Formatierung
aus der Variablendarstellung übernommen.
Die Variablen-ID identifiziert das Instrument unabhängig von Name und Pfad.
Gleiche Beschriftungen bleiben in Kachel, IPSView und Vorschau unverändert;
die IDs bleiben ausschließlich interne Quellenkennungen.

Der Kacheldesigner definiert das gemeinsame Grunddesign. Im Dialog einer
Quellenzeile kann **Individuelles Gauge-Design** aktiviert werden. Für dieses
Instrument lassen sich Zeiger und Nabe, Skala und Schrift, Farbrollen sowie
Zifferblatt und Kontur abweichend gestalten. Eigene SVG-Zeiger mit justierbarem
Drehpunkt, eigene SVG-Naben und zugeschnittene SVG-Zifferblattmotive werden
über die zentralen, validierten ECharts-Importadapter verarbeitet. Die Designwerte gehören zur
Quellenzeile und bleiben auch nach einer Umsortierung dem Instrument zugeordnet.
Für das unabhängige IPSView-Grunddesign kann jede Quelle ihr Kacheldesign
übernehmen oder eine eigene individuelle IPSView-Variante speichern.

Das optionale IPSView-WebContent-Widget nutzt dieselben Quellen. Sein
Grunddesign erbt standardmäßig das Kacheldesign oder kann unabhängig
konfiguriert werden. Wertänderungen aktualisieren das bestehende Diagramm über
den gemeinsamen Gateway-WebSocket, ohne das WebContent-Dokument neu zu laden.
Der Hintergrund kann transparent an IPSView angepasst und mit einer Theme-
oder Benutzerfarbe in frei wählbarer Deckkraft getönt werden.

Für ein eigenes IPSView-Grunddesign im Abschnitt **IPSView-Design** zuerst
**Kacheldesign verwenden** ausschalten. Solange die Option aktiv ist, sind die
eigenen Designfelder und der Knopf **Kacheldesign nach IPSView kopieren und
unabhängig bearbeiten** direkt im Abschnitt **IPSView-Design** deaktiviert.
Nach dem Ausschalten werden beide sofort aktiv. Der Knopf kopiert das
gespeicherte Kacheldesign einmalig und überschreibt dabei bisherige
unabhängige IPSView-Designwerte. Danach lassen sich Grunddesign
und individuelle Quellen-Designs für IPSView separat bearbeiten.
Änderungen am Kacheldesigner vor dem Kopieren speichern.

## Variablen

Standardmäßig legt das Modul keine Statusvariablen an. Bei aktivierter
IPSView-Ausgabe wird die Stringvariable **Gauge für IPSView** mit dem Ident
`IPSViewGauge` und einer WebContent-Darstellung erzeugt. Beim Abschalten bleibt
eine bereits angelegte Variable erhalten, bis sie im Formular ausdrücklich
gelöscht wird.
## Beispielhintergründe

Unter `examples` liegen drei zurückhaltende SVG-Zifferblattmotive für
Temperatur, Luftfeuchtigkeit und Luftdruck. Sie besitzen einen transparenten
Grund und sind für die Kombination mit einer eigenen Plattenfarbe oder einem
Plattenverlauf vorgesehen.

## Konfigurationsreferenz und Skriptzugriff

Die [Feldreferenz](../docs/KONFIGURATION.md#echartsgaugechronograph) enthält
Formularbeschriftungen, technische Namen, Typen, Standardwerte und Auswahllisten.
Für Skriptänderungen gelten die dort erläuterten JSON- und Anwenderegeln.

### Ausgabevariable

| Ident | Typ / Darstellung | Bedeutung | Einheit | Bedienbar |
| --- | --- | --- | --- | --- |
| `IPSViewGauge` | String / WebContent | HTML-Dokument für das optionale IPSView-Widget | keine | Nein |

Die Variable entsteht bei aktivierter IPSView-Ausgabe. Nach deren Abschalten
bleibt sie erhalten, wird aber nicht weiter aktualisiert. Die ausdrückliche
Löschaktion im Formular entfernt sie samt ihrer bisherigen Verknüpfbarkeit.
Quellvariablen und deren Archive bleiben unverändert. Weitere Statusvariablen
werden für das reguläre Diagramm nicht angelegt.

### Öffentliche PHP-Funktionen

`$InstanzID` bezeichnet immer diese Modulinstanz, nicht eine Quellvariable
oder das Gateway. Alle aufgeführten Argumente beim Symcon-Aufruf übergeben.

| Signatur | Rückgabe und Wirkung |
| --- | --- |
| `ECGC_CopyTileDesignToIPSView(int $InstanzID): void` | Kopiert das gespeicherte Kacheldesign, ersetzt die unabhängigen IPSView-Designwerte und wendet sie an; aktiviert unabhängiges Design. |
| `ECGC_GetGaugeData(int $InstanzID): string` | Liest das reguläre konfigurierte Diagrammmodell als JSON; keine Änderung von Quellen oder Archivwerten. |
| `ECGC_GetIPSViewHTML(int $InstanzID): string` | Vollständiges HTML-Dokument für IPSView; der Rückgabewert bestätigt keine Anzeige in einem Client. |

Beispiel mit synthetischer Instanz-ID (durch die eigene ID ersetzen):

```php
$instanceID = 12000;
$model = json_decode(ECGC_GetGaugeData($instanceID), true, 512, JSON_THROW_ON_ERROR);
```

Die fachlichen Grenzen dieser Anleitung gelten auch bei Skriptaufrufen.
Ungültige Konfigurationen, fehlende Quellen oder ein nicht verfügbares Gateway
können eine Ausnahme beziehungsweise einen ausdrücklich nicht verfügbaren
Zustand liefern. Solche Rückgaben nicht als leeren oder mit null aufgefüllten
Messbestand auswerten. Archivabfragen können den kurzzeitigen Gateway-Cache
nutzen; ein Aufruf garantiert keinen neu protokollierten Messwert.

## Lizenz

Die eigenen Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](../LICENSE).
Für Fremdkomponenten gelten deren [Originallizenzen](../THIRD_PARTY_NOTICES.md).
