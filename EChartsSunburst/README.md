# EChartsSunburst

`EChartsSunburst` zeigt aktuelle numerische Symcon-Werte als konzentrische
Ringe in einer nativen Kachel und optional in einem IPSView-HTML-Widget.
Die Winkelgröße eines Segments entspricht seinem Wert beziehungsweise der
Summe seiner Kinder.

Installation: Im **Symcon-Modulstore** nach **SymconECharts** suchen.
[Voraussetzungen und Einrichtung](../README.md#installation-und-erstes-diagramm)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartssunburst).

## Einrichtung und Quellen

Wählen Sie das vorhandene `EChartsGateway` als Parent. Tragen Sie 1 bis 100
eindeutige Integer- oder Float-Variablen ein. Jede Zeile benötigt eine
manuell benannte Gruppe; Untergruppe und abweichende Beschriftung sind
optional. Gleichnamige Gruppen und Untergruppen werden zusammengefasst.
Die Segmente folgen standardmäßig der Konfigurationsreihenfolge. Wahlweise
können sie nach Wert auf- oder absteigend sortiert werden.

Für tiefere Strukturen tragen Sie einen optionalen „Gruppenpfad“ ein, etwa
`Haus / Obergeschoss / Büro / Strom`. Bis zu sieben Gruppen plus das
Variablenblatt ergeben maximal acht Ebenen. Ein ausgefüllter Gruppenpfad
ersetzt Gruppe und Untergruppe nur für diesen Eintrag. Ohne Gruppenpfad
bleiben vorhandene Einträge unverändert nutzbar; `/` in bisherigen Gruppen-
und Untergruppennamen bleibt wörtlich erhalten. In einem Gruppenpfad steht
`\/` für einen echten Schrägstrich und `\\` für einen Rückstrich. Leere
Pfadbestandteile sind unzulässig. Gruppennamen dürfen bis zu 80 Zeichen,
Blattbeschriftungen bis zu 120 Zeichen enthalten. Maximal 100 Variablen und
800 Knoten sind zulässig; ein zu großes Datenmodell wird sichtbar abgelehnt.

Standardmäßig wird der aktuelle Rohwert verwendet. Negative oder nicht
endliche Werte werden abgewiesen. Nullwerte sind zulässig, haben aber keinen
sichtbaren Winkel; bei ausschließlich Nullwerten erscheint ein Hinweis.
Für sinnvolle Winkel sollten die Werte vergleichbare Einheiten besitzen.
Archivdaten werden nicht benötigt.

Im Kachel- und IPSView-Designer können Sie Theme, feste Gruppenfarben,
Schriftgrößen und -farben, optionale Zahlen im Segment, Innen- und
Außenradius sowie Rahmenbreite und -farbe einstellen. Beschriftungen können
radial, tangential oder waagerecht stehen, je Ring ein- und ausgeblendet
und je Ring in der Größe angepasst werden. Ein Mindestwinkel blendet Labels
auf sehr kleinen Segmenten aus. Startwinkel (0–360°), Drehrichtung,
Sortierung, abgerundete Segmentkanten (0–20 px) und Tooltip sind ebenfalls
einstellbar. Per Klick kann in eine Gruppe hineingezoomt werden. IPSView übernimmt standardmäßig das
Kacheldesign, kann aber unabhängig gestaltet werden. Die Formularvorschau
zeigt eine schematische Ringdarstellung mit Beispieldaten.

Bei tieferen Hierarchien gelten die Einstellungen des äußeren Rings ab
Ebene 3 für alle weiteren Ringe. Die vollständige Hierarchie wird in der
Vorschau mit Beispielgewichten dargestellt, ohne aktuelle Werte zu lesen.
Sehr schmale Segmente bleiben dort unbeschriftet. Im Diagramm zeigt der
Tooltip den vollständigen Pfad und den Wert. Bei mehr als drei Ebenen werden
Beschriftungen passend zum verfügbaren Segmentraum gekürzt oder ausgeblendet;
beim Hineinzoomen wird der neue Platz berücksichtigt. Ein Klick in die Mitte führt
aus einer vergrößerten Gruppenansicht zur übergeordneten Gruppe zurück.
Die gewählte Gruppenansicht bleibt bei Werteaktualisierung und
Größenänderung erhalten, solange diese Gruppe noch vorhanden ist und die
Klicknavigation aktiviert bleibt.

## Optionale Gewichte und Originalformatierung

Mit **Blattgewichte anhand Quellen-Min/Max normalisieren** erhält jede Quelle
ein eigenes **Minimum** und **Maximum** (Standard 0/100). Der aktuelle Wert wird
auf ein Gewicht von 0 bis 100 abgebildet. Minimum muss kleiner Maximum sein;
Grenzen und Differenz müssen endlich sein. Grenzen werden nicht aus aktuellen
Extrema oder Variablendarstellungen abgeleitet. Beispielsweise erhalten
20 °C bei −20 bis 60 °C und 50 % bei 0 bis 100 % beide Gewicht 50.
Negative Originalwerte sind in diesem Modus zulässig; außerhalb der Grenzen
wird nur das Gewicht begrenzt, nicht der Originalwert verändert.

Die Winkel verwenden diese Gewichte. Elternsegmente summieren Kindergewichte,
nicht unterschiedliche Originaleinheiten; ihre Summe darf über 100 liegen.
Ein sichtbarer Hinweis und Gruppentooltips kennzeichnen:
**Normalisierte Gewichte sind keine physikalischen Mengensummen.** Bei lauter
Nullgewichten bleibt das Diagramm leer statt eine Gleichverteilung zu erfinden.

**Variablendarstellung verwenden** übernimmt je Quellenzeile Einheit und
Nachkommastellen der Symcon-Variable. Andernfalls gelten die eigene **Einheit**
(bis 80 Zeichen) und **Nachkommastellen** (0–6). Die Formatierung kann unabhängig
von der Normalisierung verwendet werden. Blattbeschriftung und Tooltip zeigen
Originalwerte; der normalisierte Tooltip ergänzt Gewicht und Bereich.
Die Vorschau zeichnet nur Beispieldaten, bei Normalisierung jeweils die
Bereichsmitte, und bleibt ausdrücklich von aktuellen Werten getrennt.

Alte Quellen ohne Formatfelder behalten ihre bisherige unitlose Darstellung
mit bis zu drei Nachkommastellen. Normalisierung ist standardmäßig aus;
Konfigurationen, Designs und sämtliche Hierarchie-/Ausgabegrenzen bleiben
unverändert. Eigene Einheiten allein rechnen Rohwerte nicht ineinander um.

## Konfigurationsreferenz und Skriptzugriff

Die [Feldreferenz](../docs/KONFIGURATION.md#echartssunburst) enthält
Formularbeschriftungen, technische Namen, Typen, Standardwerte und Auswahllisten.
Für Skriptänderungen gelten die dort erläuterten JSON- und Anwenderegeln.

### Ausgabevariable

| Ident | Typ / Darstellung | Bedeutung | Einheit | Bedienbar |
| --- | --- | --- | --- | --- |
| `IPSViewSunburst` | String / WebContent | HTML-Dokument für das optionale IPSView-Widget | keine | Nein |

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
| `ECSB_CopyTileDesignToIPSView(int $InstanzID): void` | Kopiert das gespeicherte Kacheldesign, ersetzt die unabhängigen IPSView-Designwerte und wendet sie an; aktiviert unabhängiges Design. |
| `ECSB_GetSunburstData(int $InstanzID): string` | Liest das reguläre konfigurierte Diagrammmodell als JSON; keine Änderung von Quellen oder Archivwerten. |
| `ECSB_GetSunburstDiagnostic(int $InstanzID): string` | JSON-Momentaufnahme der Konfigurationsprüfung und letzten Fehlermeldung; kein Nachweis einer erfolgreichen Datenabfrage. |
| `ECSB_GetIPSViewHTML(int $InstanzID): string` | Vollständiges HTML-Dokument für IPSView; der Rückgabewert bestätigt keine Anzeige in einem Client. |

Beispiel mit synthetischer Instanz-ID (durch die eigene ID ersetzen):

```php
$instanceID = 12000;
$model = json_decode(ECSB_GetSunburstData($instanceID), true, 512, JSON_THROW_ON_ERROR);
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
