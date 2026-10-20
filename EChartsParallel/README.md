# EChartsParallel

Das Modul zeigt archivierte Werte mehrerer Symcon-Variablen als Parallelkoordinaten:
Jede Linie verbindet die Werte eines gemeinsamen abgeschlossenen Zeitintervalls.
Es ist eine eigenständige Diagramminstanz für Kacheln und optional für ein
IPSView-HTML-Widget.

Wählen Sie zwei bis zehn verschiedene Integer- oder Float-Variablen, die im
Archiv aufgezeichnet werden. Ihre Reihenfolge bestimmt die Achsenfolge; der
Achsentitel kann pro Variable überschrieben werden. Wählen Sie einen Zeitraum
von 24 Stunden, sieben oder 30 Tagen sowie Stunden- oder Tagesintervalle.
Alternativ ist **Benutzerdefiniert** mit einer Anzahl von 1–1000 Minuten,
Stunden, Tagen oder Wochen verfügbar. Das ist ein rollierendes Zeitfenster.
Für feste Anfangs-/Enddaten ist **Fester historischer Zeitraum** verfügbar.
Bei rollierenden Zeitfenstern entspricht ein Tag 24 Stunden,
eine Woche sieben solchen Tagen. Kachel und IPSView verwenden denselben Zeitraum.
Standardwerte werden als Intervallmittelwerte, Zähler als Intervallsummen
abgerufen. Nur Intervalle mit Werten auf *allen* Achsen werden gezeichnet;
fehlende Werte werden nicht interpoliert. Das aktuelle, noch nicht
abgeschlossene Intervall ist ausgeschlossen.

Die maximale Linienzahl ist einstellbar (1–1000, Standard 750). Bei einer
Kürzung zeigt die Darstellung einen Hinweis. Eine kurze oder leere Linie kann
auch an fehlenden Archivwerten liegen. Verschiedene Einheiten werden auf
getrennten Achsen dargestellt und nicht miteinander verrechnet.

## Fester historischer Zeitraum

Unter **Von (einschließlich)** und **Bis (ausschließlich)** Datum und Uhrzeit
auswählen. Es gilt die im Formular angezeigte Symcon-Zeitzone, nicht die
Browser-Zeitzone. Der Beginn muss vor dem Ende liegen; das Ende darf nicht in
der Zukunft liegen. Der gewählte Zeitraum bleibt bei Aktualisierungen fest.

Nur vollständig enthaltene, abgeschlossene Stunden beziehungsweise lokale
Kalendertage werden ausgewertet. **08:30 bis 13:30** verwendet stündlich die
Intervalle ab 09:00, 10:00, 11:00 und 12:00. **01.10. 08:30 bis 04.10. 13:30**
verwendet täglich den 2. und 3. Oktober. Fehlt ein Wert auf nur einer Achse,
entsteht für dieses Intervall keine Linie. Eine Auswahl ohne vollständiges
Intervall bleibt leer und erzeugt keine Archivabfrage oder Nullwerte.

Bei üblichen Sommer-/Winterzeitwechseln bleiben tatsächliche Stunden sowie
23-/25-stündige Kalendertage erhalten. Doppeldeutige oder nicht existierende
Uhrzeiten als ausgewählte Grenzen werden mit Hinweis abgewiesen. Historische
Stundenfenster über seltene nicht ganzstündige Zeitzonenwechsel, etwa um
30 Minuten, sind wie bei Boxplot ausgeschlossen; wählen Sie Grenzen davor
oder danach.

Das Linienbudget bleibt unverändert: höchstens 1000 Aggregate je Achse,
bei zehn Achsen höchstens 10000 insgesamt. Längere Zeiträume erhöhen es nicht.
Bei Kürzung bleiben der Hinweis und die jüngsten gemeinsamen Intervalle
erhalten. Kachel und IPSView teilen den Zeitraum, aber nicht zwingend das Design.
Die Entwurfsvorschau zeigt die gewählten Grenzen und weiterhin ausschließlich
gekennzeichnete Beispieldaten. Alte Zeiträume und gespeicherte Konfigurationen
bleiben unverändert nutzbar.

Im Kachel- und unabhängigen IPSView-Designer sind Theme, Tooltip, Glättung,
Linienart, -breite, -transparenz und -farbe sowie Titel- und Achsenschrift
einstellbar. Ausgewählte und übrige Linien können beim Eingrenzen über eine
Achse unterschiedlich transparent dargestellt werden. Sichtbarkeit der
Achsenwerte und Teilstriche, Achsenbreite, Abstand der Achsentitel sowie
Animationsdauer und -verlauf sind ebenfalls einstellbar. Die Formularvorschau
verwendet gekennzeichnete Beispieldaten;
die echten Archivdaten erscheinen nach Übernahme in Kachel und IPSView.
Das Modul benötigt ein aktives EChartsGateway und löst selbst keine
Archivänderungen aus.
