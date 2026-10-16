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

Die Fläche verwendet ausschließlich den aktuellen Rohwert der Variable.
Negative Werte sind für Flächen nicht zulässig und führen zu einem Fehler.
Nullwerte sind zulässig, haben aber keine sichtbare Fläche. Wenn alle Werte
null sind, erscheint ein entsprechender Hinweis. Wählen Sie daher Variablen
mit sinnvoll vergleichbaren, nichtnegativen Werten; es findet keine
Umrechnung zwischen Einheiten statt. Daten werden bei Variablenänderungen
aktualisiert, ohne archivierte Werte zu benötigen.

Das Diagramm kann gezoomt werden. Der Kachel- und der IPSView-Designer
besitzen Themes, Beschriftungs- und Farbeinstellungen sowie eine
gekennzeichnete SVG-Vorschau. IPSView übernimmt standardmäßig das
Kacheldesign und kann anschließend unabhängig gestaltet werden.

Öffentliche PHP-Funktionen: `ECTM_GetTreemapData($id)` liefert das aktuelle
hierarchische Datenmodell als JSON. `ECTM_GetTreemapDiagnostic($id)` liefert
Konfigurationsstatus und die letzte Fehlermeldung. `ECTM_GetIPSViewHTML($id)`
liefert das eigenständige HTML-Dokument für IPSView.
