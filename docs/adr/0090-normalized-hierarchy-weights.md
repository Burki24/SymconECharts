# ADR 0090: Normalisierte Hierarchiegewichte und Originalformat je Blatt

- Status: Angenommen
- Datum: 2026-10-22
- Ergänzt: ADR 0063 und ADR 0064

## Vertrag und Kompatibilität

Treemap ergänzt zuerst `NormalizeValues` (Boolean, Default false); Sunburst
übernimmt denselben Vertrag. Die Quellenliste erhält optionale `Minimum` und
`Maximum` (Default 0/100). Die bestehende reine `EChartsNormalization`
validiert endliche Grenzen mit endlicher positiver Differenz und projiziert
jedes aktuelle Original auf 0–100. Nur das Gewicht wird begrenzt. Negative
Originale sind im aktivierten Modus zulässig; ohne Aktivierung gilt weiterhin
der bisherige nichtnegative Rohwertvertrag. Inaktive Bereiche werden ignoriert.

Originalformatierung ist unabhängig vom Gewichtsmodus nutzbar. Quellen können
`UseVariablePresentation` (Boolean), `Unit` (höchstens 80 Zeichen) und `Decimals`
(Integer 0–6) enthalten. Neue Formularzeilen übernehmen standardmäßig die
Variablendarstellung; bei manueller Wahl gelten Einheit und Präzision der Zeile.
Der vorhandene `EChartsVariablePresentation` unterstützt native Darstellungen
und Legacy-Profile. Deren Bereiche überschreiben niemals die ausdrücklich
gewählten Normalisierungsgrenzen.

Fehlen in einer alten Zeile sämtliche Formatfelder und ist Normalisierung
ausgeschaltet, bleiben Modell und unitlose Anzeige mit bis zu drei
Nachkommastellen unverändert. Erst Formatfelder oder aktivierte Normalisierung
ergänzen Blattmetadaten. Keine Property, Quellenliste oder Designer-Einstellung
wird migriert, umgeschrieben oder automatisch eingeschaltet.

## Gemeinsames Modell und Darstellung

`EChartsHierarchySources` prüft Quellen, Grenzen und Formatmetadaten einmal für
beide Familien und für gespeicherte Quellen sowie Vorschauentwürfe.
`TreemapModel` erhält dieselbe optionale Aktivierung: `value` bleibt das
gezeichnete Gewicht, Blätter ergänzen `originalValue`, `unit`, `decimals` und
im normalisierten Modus `minimum`/`maximum`. Nur aktivierte Modelle ergänzen
`normalized: true`. Alle Vorfahren summieren gezeichnete Gewichte, niemals
Originalwerte unterschiedlicher Einheiten. Gruppensummen können 100 übersteigen.

Das gezielt nur in diesen zwei Familien geladene
`echarts-hierarchy-values.js` übernimmt Originalformatierung und escaped
Tooltips. Blattlabels zeigen Originale; Tooltips zusätzlich Gewicht und Bereich.
Gruppentooltips benennen die Summe normalisierter Gewichte ausdrücklich.
Eine auch ohne Diagrammtitel sichtbare, umbrechende Kennzeichnung verhindert
die Verwechslung mit physikalischen Mengensummen. Rechteck-/Ringgeometrie und
native Navigation bleiben familieneigen; ihre gemeinsamen stabilen IDs und
Ansichtsanker ändern sich nicht. Andere Familien erhalten keinen zusätzlichen
JavaScript-Code in ihren begrenzten Ausgaben.

SVG-Vorschauen verwenden dieselbe Quellenprüfung und Modellbildung. Sie lesen
keine aktuellen Werte oder Archive: Normalisierte Beispiele liegen in der
Mitte jedes konfigurierten Bereichs, Rohwertbeispiele bleiben unverändert.
Originalformate, Gewichtshinweis und Diagnose ungültiger Entwürfe sind in
beiden unabhängigen Design-Vorschauen enthalten. Beispielwerte sind keine
Live-Daten und Entwürfe werden nicht persistiert.

## Schutzgrenzen und Nachweise

100 Quellen, acht Ebenen einschließlich Blatt, 800 Knoten, 256 KiB
Quellenkonfiguration, 200000 HTML-sichere Modellbytes und das abschließende
1-MiB-Ausgabelimit bleiben unverändert. Nullgewichte bleiben im Modell, erzeugen
aber keine Fläche und bei ausschließlich Null kein erfundenes Diagramm.
GUIDs, Präfixe, Datenfluss, Gateway, Archive und synchronisierte Helper ändern
sich nicht. Maximale Metadatenlängen passen nicht zwangsläufig gemeinsam in
jedes Bytebudget; übergroße Modelle werden weiterhin sichtbar abgewiesen.

Zunächst rote Modell-, Modul- und Rendererprüfungen belegen die fehlende
Funktion. Tests prüfen Projektion, Originalmetadaten, native/manuelle Formate,
negative und außerhalb liegende Originale, Null, Altlisten, späte Fehler,
Entwürfe, beide Designer/Ausgaben und 100 Quellen mit 800 Knoten.
Native ECharts-Prüfungen sichern gezeichnete Gewichte, Originalbeschriftung,
Tooltips, Titelabstände und bestehende Navigation. Live-Abnahme nach Update
auf Symcon sowie im tatsächlichen IPSView-Client bleibt eine eigene Prüfung.
