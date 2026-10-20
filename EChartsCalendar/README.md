# EChartsCalendar

Das Modul zeigt wählbare Tageskennzahlen einer archivierten Symcon-Variable als
farbigen Kalender für ein Jahr, Quartal, einen Monat oder einen festen
historischen Zeitraum. Jedes Feld steht für einen Kalendertag. Es kann als
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

Der **Kalenderzeitraum** legt die Ansicht fest:

- **Jahr** ist die unveränderte Voreinstellung. `Jahr = 0` folgt dem aktuellen
  Jahr; alternativ ist ein Jahr ab 1971 bis zum aktuellen Jahr wählbar.
- **Quartal** verwendet dieses Jahr und Quartal 1–4. `Quartal = 0` verwendet
  die aktuelle Quartalsnummer, auch bei ausdrücklich gewähltem historischem Jahr.
- **Monat** verwendet dieses Jahr und Monat 1–12. `Monat = 0` verwendet die
  aktuelle Monatsnummer, auch bei historischem Jahr.
- **Fester historischer Zeitraum** verwendet die Datumsfelder **Von
  (einschließlich)** und **Bis (ausschließlich)** in der angezeigten
  Symcon-Zeitzone. Beispiel: Von 01.02.2024 bis 01.03.2024 zeigt alle 29 Tage
  des Februars. Auch jahresübergreifende Fenster sind möglich, insgesamt
  höchstens 366 Kalendertage. Das Bis-Datum darf nicht in der Zukunft liegen.

Die Pfeile blättern jeweils um ein Jahr, Quartal oder einen Monat. Beim festen
Zeitraum verschieben sie das Fenster um dieselbe Anzahl Kalendertage, auch über
Sommer-/Winterzeitwechsel hinweg. „Ausgewähltes Jahr/Quartal/Monat“ beziehungsweise
„Ausgewählter Zeitraum“ kehrt zur gespeicherten Auswahl zurück. Es geht nur
rückwärts und wieder bis zur konfigurierten Auswahl, nicht darüber hinaus in
die Zukunft. Monat, Quartal und feste Fenster erlauben bis zu 120 Schritte
zurück, niemals vor 1971; Jahresnavigation bleibt wie bisher bis 1971 möglich.
Die Navigation gilt nur für die jeweilige Browseransicht und verändert weder
gespeicherte Konfiguration noch die Ansicht anderer Benutzer. Vorhandene
Instanzen bleiben ohne Umstellung bei ihrer Jahresansicht und ihrem Design.

Es werden ausschließlich abgeschlossene Kalendertage in der Symcon-Zeitzone
berücksichtigt. Der laufende Tag bleibt leer. Fehlende Archivtage bleiben
ebenfalls leer und werden weder interpoliert noch als Null gewertet. Echte
Nullwerte sowie negative Standard-Messwerte werden dargestellt; Verbrauchswerte
müssen nichtnegativ sein. Die Farbskala richtet
sich automatisch nach den vorhandenen Werten des angezeigten Zeitraums.
In einem aktuellen Jahr, Quartal oder Monat bleiben noch nicht abgeschlossene
Tage leer; sie lösen keine Abfrage zukünftiger Archivwerte aus.
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
mit Minimum und Maximum hält die Farbzuordnung beim Zeitraumwechsel konstant.
Werte außerhalb dieses Bereichs verwenden die jeweilige Endfarbe; sie werden
nicht aus den Daten entfernt. Ohne festen Bereich gilt weiterhin die
automatische Skala des angezeigten Zeitraums.

Einheit und Nachkommastellen können aus der Variablendarstellung übernommen
oder selbst festgelegt werden. IPSView erbt standardmäßig das Kacheldesign;
alternativ kann es unabhängig gestaltet werden. Die Formularvorschau verwendet
gekennzeichnete Beispieldaten und löst beim Bearbeiten keine Archivabfrage aus.

Öffentliche Funktionen mit Instanz-ID als erstem Parameter:

- `ECCL_GetCalendarData`: Datenmodell des konfigurierten Zeitraums als JSON.
- `ECCL_GetCalendarDiagnostic`: Konfigurationsdiagnose als JSON.
- `ECCL_GetIPSViewHTML`: eigenständiges HTML-Dokument für IPSView.
- `ECCL_RefreshArchive`: Darstellung aus dem Archiv aktualisieren.
