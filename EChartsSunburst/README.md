# EChartsSunburst

`EChartsSunburst` zeigt aktuelle numerische Symcon-Werte als konzentrische
Ringe in einer nativen Kachel und optional in einem IPSView-HTML-Widget.
Die Winkelgröße eines Segments entspricht seinem Wert beziehungsweise der
Summe seiner Kinder.

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

Öffentliche PHP-Funktionen: `ECSB_GetSunburstData($id)` liefert das
aktuelle hierarchische Datenmodell als JSON.
`ECSB_GetSunburstDiagnostic($id)` liefert Konfigurationsstatus und letzte
Fehlermeldung. `ECSB_GetIPSViewHTML($id)` liefert das eigenständige
HTML-Dokument für IPSView.
