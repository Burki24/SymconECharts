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

Verwendet wird ausschließlich der aktuelle Rohwert. Negative oder nicht
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

Öffentliche PHP-Funktionen: `ECSB_GetSunburstData($id)` liefert das
aktuelle hierarchische Datenmodell als JSON.
`ECSB_GetSunburstDiagnostic($id)` liefert Konfigurationsstatus und letzte
Fehlermeldung. `ECSB_GetIPSViewHTML($id)` liefert das eigenständige
HTML-Dokument für IPSView.
