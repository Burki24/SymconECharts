# EChartsParallel

Das Modul zeigt archivierte Werte mehrerer Symcon-Variablen als Parallelkoordinaten:
Jede Linie verbindet die Werte eines gemeinsamen abgeschlossenen Zeitintervalls.
Es ist eine eigenständige Diagramminstanz für Kacheln und optional für ein
IPSView-HTML-Widget.

Wählen Sie zwei bis 16 verschiedene Integer- oder Float-Variablen, die im
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

## Verschiebbare Achsenansicht

Wenn mehr als zwei Achsen nicht lesbar nebeneinander passen, wird das Diagramm
breiter als seine Kachel beziehungsweise sein HTML-Widget. Verschieben Sie es
mit der horizontalen Scrollleiste, einer horizontalen Wischgeste oder den
Knöpfen **Vorherige Achse / Nächste Achse**. Die Anzeige darunter nennt die
gerade sichtbaren Achsen. Der fokussierte Diagrammbereich unterstützt außerdem
Links/Rechts, Bild auf/ab und Pos1/Ende. Zwei Achsen bleiben gemeinsam sichtbar.

Abstände berücksichtigen Achsentitel und Schriftgröße; lange Titel können
abgekürzt werden. Der Tooltip behält alle vollständigen Achsentitel, Werte
und Einheiten. Der Diagrammtitel bleibt im sichtbaren Bereich zentriert.
Verschieben ist keine Quellenauswahl: **Alle Achsen und vollständigen Linien
bleiben vorhanden**, und auch gerade nicht sichtbare Achsen bestimmen weiterhin
die gemeinsamen Archivintervalle. Native Achsenmarkierungen bleiben beim
Verschieben erhalten. Bei Datenaktualisierung und Größenänderung bleibt die
Position anhand der Quellvariablen-ID erhalten; sie wird nicht als Konfiguration
gespeichert und gilt nur für diese geöffnete Ansicht.

Die Formularvorschauen verwenden weiterhin ausschließlich Beispieldaten.
Bei mehr als sechs Achsen zeigen sie einen ausdrücklich gekennzeichneten
Ausschnitt der ersten sechs, nicht eine verkürzte gespeicherte Quellenliste.

## Individuelle Achsenbereiche

Jede Quelle bietet **Achsenbereich: Automatisch / Fest**. Automatisch bleibt
der Standard, auch für vorhandene Quellen ohne diese Felder. Bei **Fest**
geben Sie im Quelleneditor **Achsenminimum** und **Achsenmaximum** an,
beispielsweise −10 bis 40 °C oder 0 bis 100 %. Beide Grenzen müssen endliche
Zahlen sein; das Minimum muss kleiner sein als das Maximum. Negative Grenzen
und Nachkommastellen sind erlaubt. Gespeicherte Grenzen werden bei automatischer
Skalierung nicht ausgewertet.

Die Grenzen gehören zur jeweiligen Quelle, nicht zu einer Einheitengruppe.
Auch zwei Temperaturachsen dürfen unterschiedliche feste Bereiche besitzen.
Kachel und IPSView teilen diese fachlichen Bereiche, behalten aber ihre
unabhängigen Designs. Feste Skalen ändern sich weder durch Aktualisierung,
Zeitraumwechsel noch durch Verschieben der Achsenansicht.

Dies ist **keine Normalisierung**: Archivwerte, Einheiten und gemeinsame
Zeitlinien bleiben unverändert. Werte außerhalb einer festen Skala können
im Diagramm abgeschnitten werden. Ein Hinweis zählt die Überschreitungen
aller dargestellten Linien und aller Achsen, auch außerhalb des sichtbaren
Ausschnitts. Der Tooltip behält die Originalwerte und kennzeichnet betroffene
Achsenwerte. Werte genau auf den Grenzen sind keine Überschreitungen.
Eine vollständig außerhalb liegende Linie kann unsichtbar sein; vergrößern
Sie dann den Bereich oder wählen Sie wieder Automatisch.
Falls extrem gewählte Grenzen die numerische Genauigkeit des Renderers
überfordern, erscheint eine Fehlermeldung. Grenzen werden nicht stillschweigend
erweitert; wählen Sie dann einen anderen Bereich oder Automatisch.

Die Beispieldaten-Vorschauen zeigen die gewählten Grenzen ohne Archivabfrage;
ungültige Grenzen werden auch bei noch nicht gespeicherten Änderungen gemeldet.
Die Achsenwahl, Archivkennzahlen und das gemeinsame 10000er-Budget ändern sich nicht.

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

Das gemeinsame Ladebudget bleibt bei höchstens **10000 Archivaggregaten**.
Jede Achse erhält höchstens die konfigurierte Linienzahl und maximal den
abgerundeten Anteil `10000 / Achsenanzahl`: Bei zehn Achsen bleiben bis zu 1000,
bei elf bis zu 909 und bei 16 bis zu **625 Aggregate je Achse** möglich.
Ein kleineres eingestelltes Linienlimit bleibt erhalten. `Maximale Linien`
(Default 750) wird dabei nicht in der Konfiguration überschrieben; bei mehr
als zehn Achsen kann das gemeinsame Budget die wirksame Grenze reduzieren.
Diese Grenze steht auch im Hinweis an der Achsenpositionsanzeige.
Die Grenze betrifft geladene Aggregate aller Achsen, nicht nur die am Ende
gemeinsamen Linien oder den sichtbaren Ausschnitt. Längere Zeiträume erhöhen sie nicht.
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
