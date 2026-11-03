# EChartsPie

`EChartsPie` vergleicht Anteile aktueller numerischer Symcon-Werte als Kreis,
Donut oder Nightingale-Rose. Für alle ECharts-Diagramme genügt ein gemeinsames
`EChartsGateway`; bei weiteren Instanzen wählen Sie das vorhandene Gateway.
Ein Archiv ist nicht erforderlich.

Installation: Im **Symcon-Modulstore** nach **SymconECharts** suchen.
[Voraussetzungen und Einrichtung](../README.md#installation-und-erstes-diagramm)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartspie).

## Einrichtung

Die Modulinstanz anlegen und mit dem vorhandenen EChartsGateway verbinden.
Die unten beschriebenen Quellen und Datenregeln konfigurieren, Titel und
Design einstellen und die Änderungen übernehmen. Für IPSView bei Bedarf
die zusätzliche HTML-Ausgabe aktivieren und ihre WebContent-Variable einbinden.

## Beschriftungsoptionen

Außenbeschriftungen lassen sich natürlich, entlang der Führungslinie oder am Containerrand ausrichten. Die beiden Linienlängen, der Textabstand zur Linie und der Randabstand sind getrennt einstellbar. Innenbeschriftungen bleiben davon unberührt; die bisherige Randausrichtung ist der Standard.

Die Optionen gelten für Kacheln und – bei unabhängigem Design – auch für
IPSView. Bei geerbtem Design folgt IPSView den Kacheleinstellungen.

## Quellen

Wählen Sie 1 bis 100 eindeutige Integer- oder Float-Variablen. Die
Variablen-ID dient ausschließlich der internen Zuordnung. Ein ausdrücklich
konfigurierter Anzeigename hat Vorrang, sonst erscheint der aktuelle
Symcon-Name. Gleiche sichtbare Namen bleiben getrennte Sektoren; IDs werden
in der Grafik nicht angezeigt. Alle Quellen müssen dieselbe wirksame Einheit
verwenden, solange die optionale Normalisierung ausgeschaltet ist. Einheit und Nachkommastellen können aus der Variablendarstellung
übernommen oder je Quelle gesetzt werden; jede Quelle kann eine eigene Farbe
erhalten.

Negative Werte sind für Anteile nicht sinnvoll und werden nicht stillschweigend
in positive Werte umgerechnet: Das Diagramm zeigt dann einen Fehler. Bei einer
Summe von null zeigt es „Keine positiven Werte“ statt gleich großer
Scheibensegmente. Nullwerte bleiben als Quellen erhalten, besitzen aber keinen
sichtbaren Sektor.

### Optionale Quellenauswahl

**Erweiterte Quellenauswahl aktivieren** ist zunächst ausgeschaltet. Die
bisherige Einzelwahl über die Legende bleibt nutzbar. Aktiviert ergänzt die
Auswahl **Alle** und **Umkehren**; je Quelle lässt sich **Anfangs sichtbar**
festlegen. Fehlende Angaben bedeuten sichtbar. Ohne Aktivierung werden diese
Anfangsangaben ignoriert. Bestehende Quellenlisten und Designs bleiben erhalten.

Die Auswahl und die Position der scrollbaren Legende bleiben bei Aktualisierung,
Sortierung, Umbenennung und Größenänderung erhalten. Neu geladene Ansichten
beginnen wieder mit den konfigurierten Anfangswerten. Die Auswahl verändert
keine Symcon-Variable und wird nicht in der Instanz gespeichert. Auch versteckte
Quellen werden gelesen, überwacht und validiert; ungültige versteckte Werte
werden nicht stillschweigend übergangen.

Beschriftete Prozentanteile beziehen sich auf die **sichtbare Auswahl**.
Der Tooltip nennt zusätzlich den **Anteil aller Quellen**. Bei beispielsweise
100 gleich großen Quellen mit zehn sichtbaren Quellen ist der Auswahlanteil
je Sektor 10 %, sein Anteil aller Quellen aber 1 %. Im normalisierten Modus
sind das ausdrücklich Anteile normalisierter Gewichte, keine physikalischen
Summen unterschiedlicher Einheiten. Originalwert und Einheit bleiben erhalten.

Ist die erweiterte Auswahl aktiv, bleibt die Legende auch bei ausgeschalteter
Designer-Legende und in kleinen Kacheln sichtbar. So können ausgeblendete
Quellen wieder eingeblendet werden. Keine Auswahl, nur ausgewählte Nullwerte
und insgesamt keine positiven Werte werden unterschieden; es werden keine
Ersatzsektoren erzeugt. Bei vielen Quellen empfiehlt sich eine gezielte Auswahl.
Bei Platzmangel wird nur ein verteilter Teil der Sektoren beschriftet, passend
zur Schriftgröße und verfügbaren Fläche. Dies entfernt keine Sektoren oder
Quellen aus Legende, Tooltip oder Anteilsberechnung.

### Optionaler Min/Max-Vergleich

**Werte anhand Quellen-Min/Max normalisieren** ist zunächst ausgeschaltet.
Aktiviert bildet es jede Quelle anhand ihrer eigenen Felder **Min
(Normalisierung)** und **Max (Normalisierung)** auf 0–100 ab:
`100 × (Wert − Min) / (Max − Min)`. Die Vorgabe ist 0–100; Min muss
kleiner als Max sein. Unterschiedliche Einheiten und negative Originalwerte
sind dann erlaubt, beispielsweise Temperatur mit −20 bis 40 °C und
Luftfeuchtigkeit mit 0 bis 100 %.

Nur das Sektorgewicht wird auf 0–100 begrenzt. Tooltip und zugängliche
Beschreibung behalten den Originalwert mit seiner je Quelle gewählten
Einheit und Genauigkeit. Der Tooltip nennt zusätzlich normalisierten Wert,
Bereich und **Anteil der normalisierten Gewichte**. Dieser Anteil ist kein
physikalischer Mengenanteil und kein Temperatur- oder Feuchtigkeitsprozent.
Sortierung und Nightingale-Radien folgen den normalisierten Gewichten.
Der Hinweis bleibt auch im Diagramm und in beiden Live-Vorschauen sichtbar.
Kachel und IPSView verwenden dieselbe Normalisierung; eigene Designwerte
und Quelldaten werden nicht verändert. Ohne Normalisierung werden die
Min/Max-Felder ignoriert und es gilt das bisherige Anteilsmodell.

## Design und Ausgabe

Der Kacheldesigner bietet Kreis, Donut und Nightingale-Rose sowie Sortierung,
Innenradius in Prozent des Außenradius, Außenradius, Legende, Beschriftungen, Prozentwerte, Schriftgrößen,
Farben, Theme und Start-/Aktualisierungsanimation. Bei der Nightingale-Rose
haben die Sektoren gleiche Winkel und linear wertabhängige Radien. Die Legende
steht in mittelgroßen Kacheln unter dem Diagramm, in breiten Ansichten daneben.
Kreis und Außenbeschriftungen lassen Platz für Legende und Kachelkopf. Ohne
erweiterte Quellenauswahl wird die Legende in sehr kleinen Kacheln zugunsten
der Grafik ausgeblendet; Werte und
Namen bleiben über den Tooltip erreichbar.

Kachel und optionales IPSView-HTML-Widget verwenden dieselben Quellen.
IPSView übernimmt zunächst das Kacheldesign; bei deaktiviertem
„Kacheldesign verwenden“ ist ein eigenständiger Designer aktiv. Der
Kopierknopf überträgt die gespeicherten Kacheleinstellungen einmalig.
Der IPSView-Hintergrund ist separat einstellbar. Die HTML-Variable
`IPSViewPie` wird nur bei aktivierter IPSView-Ausgabe bereitgestellt.

Beide Designer enthalten eine SVG-Live-Vorschau, die auf ungespeicherte
Formularänderungen reagiert. Ohne gültige Quellen nutzt sie gekennzeichnete
Beispieldaten; bei mehr als sechs anfangs sichtbaren Quellen zeichnet sie nur
die ersten sechs der gewählten Sortierung und nennt die Gesamtzahl sowie bei
aktiver Auswahl die anfängliche Sichtbarkeit. Anteile, Winkel und maximale
Nightingale-Radien verwenden weiterhin die vollständige Anfangsauswahl, nicht
den gezeichneten Ausschnitt. Nicht gezeichnete Sektoren bleiben als freie Fläche
erkennbar; es entsteht kein erfundener „Sonstige“-Sektor. Die Vorschau ist
schematisch und ersetzt keine Prüfung
der responsiven ECharts-Kachel oder des IPSView-Widgets.

Sehr große Beschriftungen oder Einheiten können die zulässige Modell- oder
Ausgabegröße überschreiten. Das Modul meldet dies ausdrücklich und kürzt keine
Quellen stillschweigend; kürzen Sie in diesem Fall die Metadaten.

Vorübergehende Gateway-Ladefehler lassen den letzten gültigen Chart zunächst
stehen; nach wiederholtem Fehler erscheint ein Hinweis und nach 60 Sekunden
wird erneut gelesen. Die öffentliche Funktion `ECPI_GetPieData($InstanzID)`
liefert das aktuelle Modell als JSON und verändert keine Quellvariable.

## Übernommene JSLive-Modelle

Das [EChartsGateway](../EChartsGateway/README.md) kann geprüfte JSLive-Pakete
nach ausdrücklicher Bestätigung als **neue** Instanzen übernehmen. Bestehende
Diagramme und Quellen bleiben erhalten. Die importierte Variante erhält ein
eigenes, aus dem Export gebildetes Modell; die reguläre Einrichtung dieses
Moduls bleibt unverändert. Quellen- und Archiv-IDs sowie native Formatierungen
werden erhalten, ohne Legacy-Profile anzulegen. Der Importbericht nennt
Darstellungs- und Verarbeitungsunterschiede einzeln.

Automatische Beschriftungshintergründe und -rahmen folgen der jeweiligen
Segmentfarbe samt Transparenz, auch bei mehreren Ringen. Ausdrücklich gewählte
Farben, transparente Füllungen sowie radiale Anker und Textausrichtungen bleiben
erhalten. Segmente mit dem Wert null hinterlassen keine leeren Labelkästen.

Übernommene Modelle nehmen die Aktualisierung nach Modulupdates automatisch
wieder auf. Ist das Gateway zunächst noch nicht bereit, wird im bisherigen
Aktualisierungstakt erneut versucht. Ein erneutes Speichern ist nicht nötig;
Quellen und Gestaltung bleiben erhalten. Ungültige Modelle bleiben gesperrt.

Importierte Modelle bieten Titel, Zeitsteuerung bei historischen Modellen,
Hintergrund-/Schriftanpassungen und Sichtbarkeitseinstellungen für Kachel und
IPSView. Die Farbkarte ist schematisch; **Native Vorschau herunterladen** zeigt
die tatsächliche ECharts-Ausgabe mit noch nicht gespeicherten Entwurfswerten.
Quellen und vollständige ursprüngliche Stile stammen aus dem Importmodell;
sie werden nicht in die reguläre Quellen-/Designerstruktur zurückübersetzt.
Bei einer neuen Übernahme eines geänderten Exports ist ausdrücklich eine
separate Neuanlage erforderlich. Das bisherige Ziel wird nicht überschrieben.

## Konfigurationsreferenz und Skriptzugriff

Die [Feldreferenz](../docs/KONFIGURATION.md#echartspie) enthält
Formularbeschriftungen, technische Namen, Typen, Standardwerte und Auswahllisten.
Für Skriptänderungen gelten die dort erläuterten JSON- und Anwenderegeln.

### Ausgabevariable

| Ident | Typ / Darstellung | Bedeutung | Einheit | Bedienbar |
| --- | --- | --- | --- | --- |
| `IPSViewPie` | String / WebContent | HTML-Dokument für das optionale IPSView-Widget | keine | Nein |

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
| `ECPI_CopyTileDesignToIPSView(int $InstanzID): void` | Kopiert das gespeicherte Kacheldesign, ersetzt die unabhängigen IPSView-Designwerte und wendet sie an; aktiviert unabhängiges Design. |
| `ECPI_GetPieData(int $InstanzID): string` | Liest das reguläre konfigurierte Diagrammmodell als JSON; keine Änderung von Quellen oder Archivwerten. |
| `ECPI_GetPieDiagnostic(int $InstanzID): string` | JSON-Momentaufnahme der Konfigurationsprüfung und letzten Fehlermeldung; kein Nachweis einer erfolgreichen Datenabfrage. |
| `ECPI_GetIPSViewHTML(int $InstanzID): string` | Vollständiges HTML-Dokument für IPSView; der Rückgabewert bestätigt keine Anzeige in einem Client. |
| `ECPI_GetJSLiveImportHTML(int $InstanzID, bool $IPSView): string` | `$IPSView=false`: Kachelausgabe; `true`: IPSView-Ausgabe eines übernommenen JSLive-Modells als vollständiges HTML. |
| `ECPI_RefreshJSLiveImport(int $InstanzID): void` | Aktualisiert die Diagrammausgaben aus den konfigurierten Quellen. Kein Erfolgswert und keine Änderung von Quellen/Archiven. Nur für importierte Modelle. |
| `ECPI_GetJSLiveImportData(int $InstanzID): string` | JSON-Zustand eines übernommenen JSLive-Modells einschließlich Verfügbarkeit; nur für importierte Varianten. |

Beispiel mit synthetischer Instanz-ID (durch die eigene ID ersetzen):

```php
$instanceID = 12000;
$model = json_decode(ECPI_GetPieData($instanceID), true, 512, JSON_THROW_ON_ERROR);
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
