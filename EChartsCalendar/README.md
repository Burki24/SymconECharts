# EChartsCalendar

Das Modul zeigt die Tagesmittelwerte einer archivierten Symcon-Variable als
farbigen Jahreskalender. Jedes Feld steht für einen Kalendertag. Es kann als
native Symcon-Kachel und optional als eigenständiges IPSView-HTML-Widget
verwendet werden.

Wählen Sie eine Integer- oder Float-Variable mit aktivierter Aufzeichnung im
Symcon-Archiv und Standardaggregation. Zählervariablen werden nicht unterstützt:
Verbrauchssummen sind keine Tagesmittelwerte. Ein aktives EChartsGateway wird
benötigt; für weitere Diagramme genügt das bereits vorhandene Gateway.

`Jahr = 0` zeigt immer das aktuelle Jahr. Alternativ lässt sich ein Jahr von
1971 bis zum aktuellen Jahr auswählen. Die Pfeile in der Darstellung blättern
zu früheren Jahren und zurück; „Ausgewähltes Jahr“ kehrt zum konfigurierten
Jahr zurück. Die Navigation verändert die gespeicherte Konfiguration nicht.

Es werden ausschließlich abgeschlossene Kalendertage in der Symcon-Zeitzone
berücksichtigt. Der laufende Tag bleibt leer. Fehlende Archivtage bleiben
ebenfalls leer und werden weder interpoliert noch als Null gewertet. Echte
Nullwerte und negative Mittelwerte werden dargestellt. Die Farbskala richtet
sich automatisch nach den vorhandenen Werten des angezeigten Jahres.
Ein Tooltip zeigt Datum, Quellname, Tagesmittelwert und Einheit.

Im Designer sind Ausrichtung, Wochenbeginn, Monats- und Wochentagsnamen,
Farbskala, Farben für niedrige/hohe Werte und leere Tage sowie Zellränder
einstellbar. Die automatische Ausrichtung passt den Kalender an das
Seitenverhältnis an. Titel, Achsen und Zellbeschriftung besitzen eigene
Schriftgrößen und Farben. Tagesnummern beziehungsweise Tagesmittelwerte
erscheinen in den Zellen nur, wenn ausreichend Platz vorhanden ist; im Tooltip
bleiben die Werte erreichbar. Animation und Tooltip sind abschaltbar.

Einheit und Nachkommastellen können aus der Variablendarstellung übernommen
oder selbst festgelegt werden. IPSView erbt standardmäßig das Kacheldesign;
alternativ kann es unabhängig gestaltet werden. Die Formularvorschau verwendet
gekennzeichnete Beispieldaten und löst beim Bearbeiten keine Archivabfrage aus.

Öffentliche Funktionen mit Instanz-ID als erstem Parameter:

- `ECCL_GetCalendarData`: Datenmodell des konfigurierten Jahres als JSON.
- `ECCL_GetCalendarDiagnostic`: Konfigurationsdiagnose als JSON.
- `ECCL_GetIPSViewHTML`: eigenständiges HTML-Dokument für IPSView.
- `ECCL_RefreshArchive`: Darstellung aus dem Archiv aktualisieren.
