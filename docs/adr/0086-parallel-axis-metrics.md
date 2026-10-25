# ADR 0086: Auswählbare Archivkennzahlen je Parallel-Achse

- Status: Angenommen
- Datum: 2026-10-21
- Entscheider: Burki24
- Ergänzt: ADR 0050, 0078, 0084 und 0085

## Vertrag und Kompatibilität

`Sources` erhält pro Zeile additiv `Metric`: `auto`, `average`, `minimum`,
`maximum` oder `consumption`. Ein fehlendes Feld bedeutet `auto`; explizit
ungültige Werte werden vor Archivzugriffen abgewiesen, auch auf unsichtbaren
Achsen. Neue Formularzeilen beginnen mit `auto`. Keine Migration schreibt
Altlisten, Designs oder Properties um; GUID, Präfix und Idents bleiben stabil.
Variablen-ID und Zeilenreihenfolge bestimmen weiterhin die eindeutige Quelle;
eine Variable darf nicht als mehrere Achsen dupliziert werden.

`auto` behält das bisherige Verhalten und Modell: Gateway-Reducer `auto`
verwendet Mittelwerte für Standardarchive und Intervallverbrauch für Zähler.
Explizites `average`, `minimum` oder `maximum` erfordert ein Standardarchiv;
`consumption` erfordert ein Zählerarchiv und verwendet den vorhandenen Reducer
`sum`. Die Familie erlaubt trotz technischer Gateway-Unterstützung keine
Zähler-Min-/Max-Werte als Alternative zum Verbrauch. Es gibt keinen Rohwert-,
Standard-Summen- oder automatisch gewechselten Fallbackmodus.

## Gemeinsame Infrastruktur und fachliche Grenzen

Die zentrale `EChartsArchiveQuery`-Planung und das bestehende `archive.read`
werden unverändert wiederverwendet. Jede Quelle benötigt genau eine Abfrage
mit ihrem Reducer; keine zusätzlichen Rohdaten-/Statistikabfragen. Tagesextrema
stammen aus Tagesaggregaten, nicht aus Stundenmittelwerten. Explizite Antworten
müssen Archivtyp, effektiven Reducer und exakte Abfragegrenzen bestätigen.
Nicht passende Typen oder Metadaten erzeugen einen kontrollierten Ladefehler.
Leere Zeitfenster bleiben ohne Abfrage leer; die Vorschau kann ohne Abfrage
nur den Kennzahlwert, nicht den tatsächlichen Archivtyp prüfen.

Kennzahlen lösen weder Archivänderungen noch Reaggregation aus. Alle Quellen,
auch nicht sichtbare, bleiben im exakten Zeitstempel-Join ohne Interpolation
oder Nullergänzung. Stunden-/Tagesintervalle, rollierende/absolute Zeiträume,
2–16 eindeutige Achsen, `LineBudget` 1–1000 (Default 750), 10000 Aggregate
insgesamt und höchstens 625 je Achse bei 16 Quellen bleiben erhalten.
Der Gateway-Vertrag, Cache, Runtime und synchronisierte Helper ändern sich nicht.

Die strikte reine Kennzahlprüfung bleibt bei der Familie und wird für
gespeicherte Quellen und Entwürfe geteilt. Eine allgemeine neue Kennzahl-
Abstraktion für andere Familien ist hierfür nicht erforderlich.

## Modell, Ausgaben und Vorschau

Nur explizit konfigurierte Modellachsen erhalten das additive Feld `metric`.
Automatische Altachsen bleiben byte-/feldkompatibel. Archivwerte und Einheiten
werden weder neu gerechnet noch normalisiert. Feste Skalen und deren
Überschreitungszählung wirken auf die ausgewählte Kennzahl nach dem Join.

Beide Renderer nennen explizite Kennzahlen übersetzt am Achsentitel und im
vollständigen HTML-escaped Tooltip. Achsenabstände berücksichtigen den Zusatz;
Quell-ID, native Markierung und horizontaler Positionsanker bleiben erhalten.
Kachel und IPSView teilen das fachliche Modell, nicht notwendigerweise Designs.
Beide SVG-Vorschauen kennzeichnen die sichtbaren Kennzahlen mit Text und
Metadaten. Die Prüfung umfasst alle Achsen vor dem Sechs-Achsen-Ausschnitt;
Beispielkurven bleiben ausdrücklich schematisch. Ungültige Entwürfe erzeugen
eine Meldung statt plausibler Kurven. Entwürfe speichern nichts.

## Nachweise und Abnahme

Ein neuer öffentlicher Modul-/Gateway-Test begann RED an den fehlenden Min-/
Max-Ergebnissen. Er prüft echte Gateway-Reduktion mit synthetischen Kernel-
Archivdaten, Auto-Kompatibilität, Typtrennung, Metadatenfehler, Stunden/Tage,
historische/eigene Zeiträume, unveränderte feste Grenzen, 16×625-Last,
Kürzung, Lücken, Originalwerte, unabhängige Designs, beide Ausgabeadapter,
WebContent und speicherfreie Vorschauentwürfe. Renderertests prüfen lesbare
Kennzahltexte, Originaldaten, Escaping, Übersetzung und ID-stabile Navigation.
Bestehende Zeit-, Skalen- und Budgetprüfungen bleiben Regressionen.

Feste Achsenbereiche selbst sind inzwischen per MCP geprüft und durch den
Eigentümer in Kachel/IPSView bestätigt. Auch die neue Kennzahlwahl ist inzwischen funktional per MCP geprüft und
vom Eigentümer in Kachel/IPSView bestätigt;
synthetische Tests sind keine Live-Lastfreigabe.
