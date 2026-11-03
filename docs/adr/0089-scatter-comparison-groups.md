# ADR 0089: Benannte Scatter/Bubble-Vergleichsgruppen

- Status: Angenommen
- Datum: 2026-10-22
- Ergänzt: ADR 0040 und ADR 0088

## Vertrag und Kompatibilität

Die bestehende `Sources`-Liste erhält optional `Group` als Namen der fachlichen
Vergleichsgruppe (höchstens 80 Zeichen). Je Gruppe gelten weiterhin genau X/Y
und optional Größe mit unterschiedlichen numerischen Variablen-IDs. Bis zu
16 vollständige Gruppen und 48 Zuordnungen sind erlaubt. Eine leere Gruppe
bleibt zulässig; fehlen alle Gruppennamen, bleibt das alte einzelne
`roles`-/`points`-Modell ohne Migration oder Umschreibung der Properties erhalten.
Named Modelle ergänzen `groups` mit Rollenmetadaten, Punkten und Trunkierung
je Gruppe. Neue Modelle sind zusätzlich auf 200000 JSON-Bytes begrenzt;
der bestehende finale 1-MiB-HTML-Guard bleibt aktiv.

Gruppennamen ordnen die Zeilen fachlich zu, sind aber keine Quellenidentitäten.
Die interne Serien-ID folgt den kanonisch angeordneten Rollen-Variablen-IDs
und deren Reducern. Umbenennen oder Umordnen derselben Zuordnungen erhält die
ID; vollständig identische Vergleiche werden nicht doppelt angelegt.
Dieselbe Variable darf in anderen Gruppen und mit anderen Kennzahlen erneut
verwendet werden. Sichtbare Namen erhalten niemals technische ID-Zusätze.

## Gemeinsame Planung, getrennte Verknüpfung

Alle Gruppen verwenden denselben zentral geplanten Zeitraum und dieselbe
Aggregationsstufe. Das bestehende 200–2000er-Ladebudget wird vor den Abfragen
über sämtliche Zuordnungen verteilt, nicht je Gruppe erneuert. Wiederverwendete
Variablen beanspruchen weiterhin je Zuordnung einen Anteil. Keine neue
Gateway-Operation, keine unbeschränkte Einzelabfrage und kein Archivtypwechsel.
`ScatterPairing` wird je Gruppe unverändert wiederverwendet. Nur identische
Zeitstempel werden verbunden; Lücken einer Gruppe entfernen keine Punkte einer
anderen Gruppe. Fehler anderer oder ausgeblendeter Gruppen werden nicht ignoriert.

Auf gemeinsamen X-/Y-Achsen müssen die Einheiten jeweils übereinstimmen.
Dasselbe gilt bei mehreren Größenquellen für deren gemeinsame Größenskala.
X/Y/Größe untereinander dürfen weiterhin unterschiedliche Einheiten besitzen.
Es findet keine implizite Normalisierung oder Umrechnung statt.

## Ansicht und gemeinsame Logik

Die bisherige Einzelserien-Geometrie wird für jede Gruppe wiederverwendet.
Bubble- und Farbskalen verwenden die vollständige Population, einschließlich
ausgeblendeter Gruppen. Feste Bezüge bleiben unabhängig je Designer.
Originalwerte und Einheiten bleiben im gruppenspezifischen Tooltip erhalten.
Theme-Farben werden über stabile Indizes vergeben, eine ausdrücklich gesetzte
Punktfarbe bleibt für alle Gruppen wirksam. Die bestehende gemeinsame
`echarts-series-selection.js` verwaltet ID-stabile Auswahl und Legendenanker;
die bestehende Zoomlogik bleibt erhalten. Bereichsauswahl zählt die tatsächlichen
Punktindizes aller ausgewählten Serien.

Quellenprüfung und Intervall-Join werden innerhalb der Familie nicht kopiert.
Vorhandene zentrale Planung, Identitäts-, Präsentations-, Formular-, Vorschau-
und Auswahlbausteine werden unverändert eingebunden. Neue Gruppensemantik bleibt
in Scatter; synchronisierte Helper werden nicht lokal geändert. Die Vorschau
validiert alle Gruppen, zeichnet höchstens sechs und kennzeichnet diesen Auszug
mit Beispielpunkten ohne Archivzugriff.

## Nachweise

Öffentliche Modul-/Gateway-Tests prüfen Grenzen, Budgets, Lücken, Metadaten,
Altlisten, Entwürfe und beide Ausgaben. Native Engine-Tests ergänzen Auswahl,
Tooltip, Skalen und Legendengeometrie. Die nachfolgende Live-Abnahme in Kachel und tatsächlichem IPSView ist
durch den Eigentümer bestätigt. Eine reale Lastfreigabe bleibt separat.
