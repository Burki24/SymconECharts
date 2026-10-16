# EChartsParallel

Das Modul zeigt archivierte Werte mehrerer Symcon-Variablen als Parallelkoordinaten:
Jede Linie verbindet die Werte eines gemeinsamen abgeschlossenen Zeitintervalls.
Es ist eine eigenständige Diagramminstanz für Kacheln und optional für ein
IPSView-HTML-Widget.

Wählen Sie zwei bis zehn verschiedene Integer- oder Float-Variablen, die im
Archiv aufgezeichnet werden. Ihre Reihenfolge bestimmt die Achsenfolge; der
Achsentitel kann pro Variable überschrieben werden. Wählen Sie einen Zeitraum
von 24 Stunden, sieben oder 30 Tagen sowie Stunden- oder Tagesintervalle.
Standardwerte werden als Intervallmittelwerte, Zähler als Intervallsummen
abgerufen. Nur Intervalle mit Werten auf *allen* Achsen werden gezeichnet;
fehlende Werte werden nicht interpoliert. Das aktuelle, noch nicht
abgeschlossene Intervall ist ausgeschlossen.

Die maximale Linienzahl ist einstellbar (1–1000, Standard 750). Bei einer
Kürzung zeigt die Darstellung einen Hinweis. Eine kurze oder leere Linie kann
auch an fehlenden Archivwerten liegen. Verschiedene Einheiten werden auf
getrennten Achsen dargestellt und nicht miteinander verrechnet.

Im Kachel- und unabhängigen IPSView-Designer sind Theme, Tooltip, Glättung,
Linienbreite, -transparenz und -farbe sowie Titel- und Achsenschrift
einstellbar. Die Formularvorschau verwendet gekennzeichnete Beispieldaten;
die echten Archivdaten erscheinen nach Übernahme in Kachel und IPSView.
Das Modul benötigt ein aktives EChartsGateway und löst selbst keine
Archivänderungen aus.
