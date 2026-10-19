# EChartsTreemap

`EChartsTreemap` zeigt aktuelle numerische Symcon-Werte als Rechteckflächen in
einer nativen Kachel und optional in einem IPSView-HTML-Widget. Große Werte
erhalten größere Flächen.

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

Die Fläche verwendet ausschließlich den aktuellen Rohwert der Variable.
Negative Werte sind für Flächen nicht zulässig und führen zu einem Fehler.
Nullwerte sind zulässig, haben aber keine sichtbare Fläche. Wenn alle Werte
null sind, erscheint ein entsprechender Hinweis. Wählen Sie daher Variablen
mit sinnvoll vergleichbaren, nichtnegativen Werten; es findet keine
Umrechnung zwischen Einheiten statt. Daten werden bei Variablenänderungen
aktualisiert, ohne archivierte Werte zu benötigen.

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

Öffentliche PHP-Funktionen: `ECTM_GetTreemapData($id)` liefert das aktuelle
hierarchische Datenmodell als JSON. `ECTM_GetTreemapDiagnostic($id)` liefert
Konfigurationsstatus und die letzte Fehlermeldung. `ECTM_GetIPSViewHTML($id)`
liefert das eigenständige HTML-Dokument für IPSView.
