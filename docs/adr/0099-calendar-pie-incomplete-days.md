# ADR 0099: Optionale Teilquellen an CalendarPie-Tagen

- Status: Angenommen
- Datum: 2026-10-24
- Ergänzt: ADR 0060

## Vertrag und Kompatibilität

`ShowIncompleteDays` ist eine gemeinsame Boolean-Property mit Default false,
keine unabhängige Designoption. Bestehende Konfigurationen bleiben streng:
numerische Kreise benötigen sämtliche Tagesquellen. Nur bei ausdrücklicher
Aktivierung werden positive verfügbare Gewichte eines `partial`-Tages gezeichnet.
Status, fehlende Quell-IDs, sämtliche Originalwerte und Normalisierungsbereiche
bleiben unverändert. Es gibt keine Nullauffüllung, Schätzung oder Neuberechnung
von Archivkennzahlen. Fehlende Quellen erhalten weder Slice noch Prozentanteil.

Kreisanteile beziehen sich nur auf die vorhandenen Gewichte; bei einer allein
verfügbaren positiven Quelle sind dies 100 %. Diese Einschränkung steht im
Tooltip und in einem dauerhaften Hinweis. Eine orange Raute außerhalb des
Teilkreises kennzeichnet die Zelle auch ohne Legende, Tooltip oder Zahlen.
Ein vorhandenes Nullgewicht bleibt im Modell/Tooltip, erzeugt aber keinen
Sektor. Ein teilweiser Nulltag bleibt `partial` ohne Kreis, ein vollständig
fehlender Tag `missing`, laufende und künftige Tage `pending`.

`states` ignoriert die neue gespeicherte Option vollständig. Unbekannte Zeit
bleibt ein eigener Anteil des tatsächlichen 23-/24-/25-Stunden-Tages. Inaktive
Eingaben werden nicht gelöscht. Properties, GUID, Idents, Schema-Version 1,
Quellidentitäten, Designs und gemeinsame Ausgabewege bleiben erhalten.

## Umsetzung und Grenzen

Die neue Anzeigepolicy im vorhandenen familienlokalen `CalendarPieData` entscheidet für
Live-Modelle und synthetische SVGs. Das additive `day.showPie` transportiert
die Entscheidung; `calendarPie.showIncompleteDays` nennt die wirksame Policy.
Der Renderer besitzt für ältere Modelle ohne dieses Feld einen strengen
Fallback. Die gemeinsame Vorschauverdrahtung verarbeitet auch den echten
Checkbox-Callback ohne Persistenz oder Archivabfragen. Kein neuer generischer
Helper und keine Kopie synchronisierter Infrastruktur: Die geprüften
Upstream-Formular-/SVG-Helper liefern Infrastruktur, keine fachliche Tagespolicy.

Alle Quellen werden unverändert gelesen und validiert: 1–5 numerische Quellen,
31 Tagesaggregate pro Quelle, 2000 Rohwechsel im Zustandsmodus, 32 Zustände,
250000 Modellbytes und 1 MiB HTML. Gekürzte Antworten oder sonstige Archivfehler
bleiben Fehler; die Option ist kein allgemeiner Best-Effort-Lademodus.
Die gepinnte ECharts-Runtime bleibt unverändert. Die Textgrenzenprüfung
berücksichtigt ihren internen 3-Pixel-Versatz für Innenlabels, damit kleine
Teilkreise und Ringe keine überstehenden Zahlen erhalten.

## Nachweise und offene Abnahme

Fokussierte Tests begannen RED an fehlender Tagespolicy und fehlendem Teilkreis.
Reine Policy-, echte Modul-/Gateway-Integration sowie native Engine-Tests
prüfen Standard/Opt-in, beide numerischen Modi, Normalisierung, Originalformate,
Null-/Fehl-/Zukunftstage, Zustandsdauer, fehlende IDs, unabhängige Designs,
beide SVGs und ausgeführte Entwurfscallbacks ohne Schreibzugriff. Kleine und
große Geometrien, sichere Tooltips und Monatsnavigation bleiben abgesichert.
Die MCP-/Symcon- und tatsächliche IPSView-Client-Abnahme nach Modulupdate ist
separat offen; lokale Browserprüfungen sind kein Live-Nachweis.
