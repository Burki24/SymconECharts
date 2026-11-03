# EChartsTreemap

`EChartsTreemap` zeigt aktuelle numerische Symcon-Werte als Rechteckflächen in
einer nativen Kachel und optional in einem IPSView-HTML-Widget. Große Werte
erhalten größere Flächen.

Voraussetzungen und Installation: [SymconECharts](../README.md#voraussetzungen)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartstreemap).

## Einrichtung und Quellen

Wählen Sie ein vorhandenes `EChartsGateway` als Parent. Tragen Sie für jede
Quelle eine eindeutige Integer- oder Float-Variable und einen Gruppennamen
ein. Eine Untergruppe und eine abweichende Beschriftung sind optional. Bis zu
100 Variablen können eingetragen werden. Gleichnamige Gruppen werden
zusammengeführt; die Variablen-ID bleibt die technische Identität eines
Rechtecks. Die Reihenfolge der Einträge bestimmt die Grundreihenfolge der
Gruppen und Werte.

Für tiefere Hierarchien verwenden Sie stattdessen den optionalen
„Gruppenpfad“, beispielsweise `Haus / Obergeschoss / Büro / Strom`.
Bis zu sieben Gruppen und das Blatt mit dem Variablenwert ergeben maximal
acht Ebenen. Ein ausgefüllter Gruppenpfad ersetzt für diesen Eintrag die
Felder Gruppe und Untergruppe. Bleibt er leer, werden diese bisherigen Felder
unverändert verwendet; vorhandene Namen mit `/` werden nicht aufgeteilt.
Innerhalb eines Pfadbestandteils schreiben Sie `\/` für einen echten
Schrägstrich und `\\` für einen Rückstrich. Leere Pfadbestandteile sind
unzulässig. Jeder Gruppenname darf bis zu 80 Zeichen, die Blattbeschriftung
bis zu 120 Zeichen enthalten. Insgesamt gelten maximal 100 Variablen und
800 Knoten; ein zu großes Datenmodell wird sichtbar abgelehnt.

Standardmäßig verwendet die Fläche den aktuellen Rohwert der Variable.
Negative Werte sind für Flächen nicht zulässig und führen zu einem Fehler.
Nullwerte sind zulässig, haben aber keine sichtbare Fläche. Wenn alle Werte
null sind, erscheint ein entsprechender Hinweis. Wählen Sie daher Variablen
mit sinnvoll vergleichbaren, nichtnegativen Werten; es findet keine
Umrechnung zwischen Einheiten statt. Daten werden bei Variablenänderungen
aktualisiert, ohne archivierte Werte zu benötigen.

## Gestaltung und Bedienung

Das Diagramm kann gezoomt werden. Der Kachel- und der IPSView-Designer
besitzen Themes, eine einstellbare Titel- und Beschriftungsgröße sowie
Titel- und Beschriftungsfarben und eine gekennzeichnete SVG-Vorschau.
Die bisherigen Beschriftungseinstellungen gelten als Standard für Gruppen,
Untergruppen und Werte. Gruppentitel und Untergruppentitel können zusätzlich
eigene Schriftgrößen und Farben erhalten; 0 % beziehungsweise „Wie
Werte-Beschriftung“ übernimmt die bisherige Einstellung. „Automatisch“ wählt
eine zur jeweiligen Gruppenfarbe lesbare Farbe. Die numerischen Werte können
optional in den Blattrechtecken unter dem Namen angezeigt werden; im Tooltip
sind sie immer verfügbar.

Unter „Feste Gruppenfarben“ können Sie den exakten Gruppennamen aus der
Quellenliste und eine Farbe eintragen. Nicht zugeordnete Gruppen behalten die
Theme-Palette. Gleiche Gruppennamen oder ungültige Farben werden abgelehnt;
ein „Automatisch“-Eintrag verwendet die Theme-Farbe. IPSView übernimmt
standardmäßig das Kacheldesign und kann anschließend unabhängig gestaltet
werden, einschließlich eigener Gruppenfarben.

Im Abschnitt „Rahmen und Navigation“ lassen sich Rahmenstärken für die drei
Hierarchieebenen sowie Abstände zwischen Gruppen und innerhalb von Gruppen
beziehungsweise Untergruppen von 0 bis 12 Pixel festlegen. Ebene 1 enthält
Gruppen, Ebene 2 Untergruppen oder direkt zugeordnete Werte, Ebene 3 Werte
innerhalb von Untergruppen. Eine feste Rahmen- und Abstandsfarbe ist optional;
„Automatisch“ belässt die bisherige Farbgebung. Verschieben/Zoomen,
Zoom beim Klick auf einen Knoten und der Navigationspfad sind getrennt
schaltbar. Auf schmalen Ansichten wird der Navigationspfad bei bis zu drei
Ebenen automatisch ausgeblendet. Tiefere Hierarchien behalten ihn als
Rücknavigation; bei ausdrücklich deaktiviertem Navigationspfad
steht seine Fläche dem Diagramm zur Verfügung. Die statische SVG-Vorschau
zeigt Rahmen und Abstände schematisch; das Navigationsverhalten ist erst im
Diagramm prüfbar.

Bei tieferen Hierarchien gelten Rahmen und Abstände der dritten Ebene auch
für alle weiteren Ebenen; die Untergruppen-Typografie gilt für verschachtelte
Gruppen ab Ebene 2. Die Vorschau zeigt die vollständige Hierarchie mit
Beispielgewichten schematisch, ohne aktuelle Variablenwerte zu lesen.
Sehr kleine Flächen erhalten aus Platzgründen keine sichtbare Beschriftung.
Im Diagramm bleiben der vollständige Pfad und der Wert im Tooltip erreichbar.
Navigationspfad und Hineinzoomen funktionieren auch in tiefen Strukturen;
bei mehr als drei Ebenen zeigt die klickbare Ansicht zunächst drei Ebenen
und erschließt weitere Gruppen durch Hineinklicken.
Die gewählte Gruppenansicht bleibt bei Werteaktualisierungen erhalten,
solange diese Gruppe noch vorhanden ist.

## Optionale Gewichte und Originalformatierung

Mit **Blattgewichte anhand Quellen-Min/Max normalisieren** berechnet jede Quelle
ihr Gewicht von 0 bis 100 anhand ihres eigenen **Minimums** und **Maximums**.
Diese Grenzen sind ausdrücklich festgelegt (Standard 0/100), nicht automatisch
aus Messwerten oder Variablendarstellungen abgeleitet. Minimum muss kleiner
Maximum sein; beide und ihre Differenz müssen endlich sein. Beispielsweise
ergeben 20 °C bei −20 bis 60 °C sowie 50 % bei 0 bis 100 % beide Gewicht 50.
Im aktivierten Modus sind auch negative Originalwerte zulässig. Werte außerhalb
der Grenzen werden nur im Gewicht begrenzt; das Original bleibt erhalten.

Die Fläche folgt dem Gewicht, nicht einer Summe verschiedener Originaleinheiten.
Gruppen summieren die Gewichte ihrer Blätter und dürfen über 100 liegen.
Der sichtbare Hinweis und die Gruppentooltips kennzeichnen ausdrücklich:
**Normalisierte Gewichte sind keine physikalischen Mengensummen.** Bei lauter
Nullgewichten entsteht keine künstliche gleichmäßige Verteilung.

Je Quellenzeile kann **Variablendarstellung verwenden** Einheit und
Nachkommastellen der Symcon-Variable übernehmen. Ohne diese Auswahl gelten
die eigene **Einheit** (bis 80 Zeichen) und **Nachkommastellen** (0–6).
Diese Originalformatierung ist auch ohne Normalisierung nutzbar. Zahlen im
Blatt und Tooltip zeigen Originalwerte; der normalisierte Tooltip ergänzt
Gewicht und Bereich. Die SVG-Vorschau verwendet ausdrücklich Beispieldaten,
im normalisierten Modus die Mitte des jeweiligen Bereichs, keine Live-Werte.

Alte Quellen ohne neue Formatfelder bleiben unverändert unitlos mit bis zu
drei Nachkommastellen. Normalisierung bleibt standardmäßig aus; Quellenlisten,
Designs, Hierarchietiefe, Knotenanzahl und Ausgabebudgets werden nicht geändert.
Ohne Normalisierung sollten weiterhin sinnvoll vergleichbare Rohwerte gewählt
werden; eigene Einheiten rechnen diese nicht automatisch ineinander um.

## Konfigurationsreferenz und Skriptzugriff

Die [Feldreferenz](../docs/KONFIGURATION.md#echartstreemap) enthält
Formularbeschriftungen, technische Namen, Typen, Standardwerte und Auswahllisten.
Für Skriptänderungen gelten die dort erläuterten JSON- und Anwenderegeln.

### Ausgabevariable

| Ident | Typ / Darstellung | Bedeutung | Einheit | Bedienbar |
| --- | --- | --- | --- | --- |
| `IPSViewTreemap` | String / WebContent | HTML-Dokument für das optionale IPSView-Widget | keine | Nein |

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
| `ECTM_CopyTileDesignToIPSView(int $InstanzID): void` | Kopiert das gespeicherte Kacheldesign, ersetzt die unabhängigen IPSView-Designwerte und wendet sie an; aktiviert unabhängiges Design. |
| `ECTM_GetTreemapData(int $InstanzID): string` | Liest das reguläre konfigurierte Diagrammmodell als JSON; keine Änderung von Quellen oder Archivwerten. |
| `ECTM_GetTreemapDiagnostic(int $InstanzID): string` | JSON-Momentaufnahme der Konfigurationsprüfung und letzten Fehlermeldung; kein Nachweis einer erfolgreichen Datenabfrage. |
| `ECTM_GetIPSViewHTML(int $InstanzID): string` | Vollständiges HTML-Dokument für IPSView; der Rückgabewert bestätigt keine Anzeige in einem Client. |

Beispiel mit synthetischer Instanz-ID (durch die eigene ID ersetzen):

```php
$instanceID = 12000;
$model = json_decode(ECTM_GetTreemapData($instanceID), true, 512, JSON_THROW_ON_ERROR);
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
