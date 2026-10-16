# EChartsSunburst

`EChartsSunburst` zeigt aktuelle numerische Symcon-Werte als konzentrische
Ringe in einer nativen Kachel und optional in einem IPSView-HTML-Widget.
Die Winkelgröße eines Segments entspricht seinem Wert beziehungsweise der
Summe seiner Kinder.

Wählen Sie das vorhandene `EChartsGateway` als Parent. Tragen Sie 1 bis 100
eindeutige Integer- oder Float-Variablen ein. Jede Zeile benötigt eine
manuell benannte Gruppe; Untergruppe und abweichende Beschriftung sind
optional. Gleichnamige Gruppen und Untergruppen werden zusammengefasst.
Die Konfigurationsreihenfolge bestimmt die Segmentreihenfolge.

Verwendet wird ausschließlich der aktuelle Rohwert. Negative oder nicht
endliche Werte werden abgewiesen. Nullwerte sind zulässig, haben aber keinen
sichtbaren Winkel; bei ausschließlich Nullwerten erscheint ein Hinweis.
Für sinnvolle Winkel sollten die Werte vergleichbare Einheiten besitzen.
Archivdaten werden nicht benötigt.

Im Kachel- und IPSView-Designer können Sie Theme, feste Gruppenfarben,
Schriftgrößen und -farben, optionale Zahlen im Segment, Innen- und
Außenradius sowie Rahmenbreite und -farbe einstellen. Per Klick kann in
eine Gruppe hineingezoomt werden. IPSView übernimmt standardmäßig das
Kacheldesign, kann aber unabhängig gestaltet werden. Die Formularvorschau
zeigt eine schematische Ringdarstellung mit Beispieldaten.

Öffentliche PHP-Funktionen: `ECSB_GetSunburstData($id)` liefert das
aktuelle hierarchische Datenmodell als JSON.
`ECSB_GetSunburstDiagnostic($id)` liefert Konfigurationsstatus und letzte
Fehlermeldung. `ECSB_GetIPSViewHTML($id)` liefert das eigenständige
HTML-Dokument für IPSView.
