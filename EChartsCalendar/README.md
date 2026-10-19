# EChartsCalendar

Das Modul zeigt wählbare Tageskennzahlen einer archivierten Symcon-Variable als
farbigen Jahreskalender. Jedes Feld steht für einen Kalendertag. Es kann als
native Symcon-Kachel und optional als eigenständiges IPSView-HTML-Widget
verwendet werden.

Wählen Sie eine Integer- oder Float-Variable mit aktivierter Aufzeichnung im
Symcon-Archiv und die passende **Kennzahl**:

- **Tagesmittelwert** (Voreinstellung): Symcons Tagesmittelwert einer Standardvariable.
- **Tagesminimum** oder **Tagesmaximum**: kleinster beziehungsweise größter
  archivierter Messwert des Tages, nicht das Minimum oder Maximum von Stundenmitteln.
- **Tagesverbrauch**: Symcons Verbrauchssumme eines Zählerarchivs. Sie stammt
  aus den positiven Zähleränderungen des Tages, nicht aus dem absoluten Zählerstand
  oder einer selbst berechneten Differenz von Tagesendwerten. Zählerrücksetzungen
  folgen der Symcon-Archivberechnung; sie werden nicht als negativer Verbrauch dargestellt.

Mittelwert, Minimum und Maximum benötigen Standardaggregation, Verbrauch benötigt
Zähleraggregation. Eine unpassende Auswahl wird gemeldet, nicht automatisch umgestellt.
Das Modul verändert weder Archivierung noch Aggregation der Quelle.
Ein aktives EChartsGateway wird
benötigt; für weitere Diagramme genügt das bereits vorhandene Gateway.

`Jahr = 0` zeigt immer das aktuelle Jahr. Alternativ lässt sich ein Jahr von
1971 bis zum aktuellen Jahr auswählen. Die Pfeile in der Darstellung blättern
zu früheren Jahren und zurück; „Ausgewähltes Jahr“ kehrt zum konfigurierten
Jahr zurück. Die Navigation verändert die gespeicherte Konfiguration nicht.

Es werden ausschließlich abgeschlossene Kalendertage in der Symcon-Zeitzone
berücksichtigt. Der laufende Tag bleibt leer. Fehlende Archivtage bleiben
ebenfalls leer und werden weder interpoliert noch als Null gewertet. Echte
Nullwerte sowie negative Standard-Messwerte werden dargestellt; Verbrauchswerte
müssen nichtnegativ sein. Die Farbskala richtet
sich automatisch nach den vorhandenen Werten des angezeigten Jahres.
Das Archiv muss kein vollständiges Jahr abdecken. Beginnt die Aufzeichnung
erst im Tagesverlauf, wird auch der erste verfügbare Tageswert angezeigt;
er bezieht sich auf die tatsächlich aufgezeichnete Zeit dieses Tages.
Ein Tooltip zeigt Datum, Quellname, ausgewählte Kennzahl und Einheit. Die
Voreinstellung erhält bestehende Kalender als Tagesmittelwert-Darstellung.

Im Designer sind Ausrichtung, Wochenbeginn, Monats- und Wochentagsnamen,
Farbskala, Farben für niedrige/hohe Werte und leere Tage sowie Zellränder
einstellbar. Die automatische Ausrichtung passt den Kalender an das
Seitenverhältnis an. Titel, Achsen und Zellbeschriftung besitzen eigene
Schriftgrößen und Farben. Tagesnummern beziehungsweise Tageswerte
erscheinen in den Zellen nur, wenn ausreichend Platz vorhanden ist; im Tooltip
bleiben die Werte erreichbar. Animation und Tooltip sind abschaltbar.

Monatstrennlinien lassen sich unabhängig von Zellrändern ein-/ausblenden und
mit eigener Farbe, Breite und Linienart gestalten. Die Zellränder können
durchgezogen, gestrichelt oder gepunktet erscheinen; auch die Zelldeckkraft
ist einstellbar. Der Rahmen beim Berühren eines Tages hat eine eigene Farbe,
die Hervorhebung kann abgeschaltet werden.

Monats- und Wochentagsnamen können am Anfang oder Ende des Kalenders stehen,
mit eigenem Abstand. Monate erscheinen zentriert oder am Monatsanfang und
wahlweise als Name, Nummer oder Jahr-Monat. Eine zusätzliche Jahreszahl kann
oben, unten, links oder rechts angezeigt werden, mit eigener Größe, Farbe und
Abstand. Kalenderbeschriftungen können fett dargestellt werden.
Bei zu wenig Platz werden nur einzelne Monats-/Wochentagsnamen beschriftet; die
Kalendertage und ihre Werte bleiben vollständig erhalten.

Die Farbreihenfolge kann umgekehrt werden. Ein optionaler fester Wertebereich
mit Minimum und Maximum hält die Farbzuordnung beim Jahreswechsel konstant.
Werte außerhalb dieses Bereichs verwenden die jeweilige Endfarbe; sie werden
nicht aus den Daten entfernt. Ohne festen Bereich gilt weiterhin die
automatische Skala des angezeigten Jahres.

Einheit und Nachkommastellen können aus der Variablendarstellung übernommen
oder selbst festgelegt werden. IPSView erbt standardmäßig das Kacheldesign;
alternativ kann es unabhängig gestaltet werden. Die Formularvorschau verwendet
gekennzeichnete Beispieldaten und löst beim Bearbeiten keine Archivabfrage aus.

Öffentliche Funktionen mit Instanz-ID als erstem Parameter:

- `ECCL_GetCalendarData`: Datenmodell des konfigurierten Jahres als JSON.
- `ECCL_GetCalendarDiagnostic`: Konfigurationsdiagnose als JSON.
- `ECCL_GetIPSViewHTML`: eigenständiges HTML-Dokument für IPSView.
- `ECCL_RefreshArchive`: Darstellung aus dem Archiv aktualisieren.
