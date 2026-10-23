# ADR 0097: Optionale berechnete Sankey-Rest-/Bilanzknoten

- Status: Angenommen
- Datum: 2026-10-23
- Ergänzt: ADR 0051, ADR 0095 und ADR 0096

## Vertrag und Kompatibilität

`Nodes` erhält je Zeile das optionale Boolean-Feld `Balance` mit Default false.
Fehlend und false erhalten das bisherige Modell exakt; falsche Typen werden
mit Knotenzeile abgewiesen. Keine Migration, neue Property, GUID oder Änderung
gespeicherter Designs ist nötig. Die Auswahl ist je Knoten ausdrücklich,
einschließlich bewusst gewählter Anfangs-/Endknoten.

Zunächst gelten unverändert Quellen-/Einheitenprüfung, optionale Kantenumkehr,
Summenschutz und konfigurierte/effektive DAG-Prüfung einschließlich Nullflüssen.
Für jeden ausgewählten Knoten wird danach ausschließlich aus diesen gemessenen
effektiven Flüssen `incoming - outgoing` gebildet:

- Positiv: zusätzlicher Fluss zum Terminal „Nicht zugeordnet (berechnet)“.
- Negativ: zusätzlicher Fluss vom Terminal „Ungeklärter Zufluss (berechnet)“.
- Null: kein zusätzlicher Knoten oder Fluss.

Die Breite ist der positive Differenzbetrag. Relative Gleitkommareste bis
`abs(difference) / max(incoming, outgoing) <= 1e-12` erzeugen keinen Zusatzfluss;
Nullsummen werden separat übersprungen. Es gibt keine absolute Untergrenze und
keine behauptete Messgenauigkeit. Gemessene Werte werden nicht verändert.
Generierte Flüsse werden weder in benachbarten Knotendifferenzen noch erneut
im globalen gemessenen Summenüberlaufschutz gezählt. Auch ein endlicher maximaler
Restfluss bleibt zulässig. Die vollständige ergänzte Topologie wird erneut mit
demselben DAG-Prüfer geprüft.

Die stabile Terminalidentität `@balance:<key>` kann wegen des verbindlichen
Schlüsselvertrags nicht mit konfigurierten Knoten kollidieren. Sie bleibt bei
einem Differenz-Vorzeichenwechsel gleich. Zusätze tragen `calculated`,
`balanceFor`, `balanceIncoming` und `balanceOutgoing`, aber ausdrücklich keine
Variablen-ID oder erfundene Messzeitstempel. Reale Knoten, Verbindungen,
Quellidentitäten, Abonnements und Zeitstempel bleiben unverändert.

## Kennzeichnung und Schutzgrenzen

Generierte Knotennamen enthalten „(berechnet)“. Tooltips und SVG-Titel nennen
die gemessenen Zu-/Abflüsse, den Bilanzknoten und den Hinweis, dass die Differenz
weder Messung noch nachgewiesener Verlust ist. HTML und SVG zeigen zusätzlich
einen dauerhaften Berechnungshinweis; dieser bleibt auch ohne Labels/Tooltip
sichtbar. Nur Modelle mit tatsächlichen berechneten Flüssen reservieren dafür
zusätzlichen Raum. Bestehende Farben, unabhängige Designer und Altgeometrien
bleiben erhalten. Ein Fehler oder Rückkehr zum alten Modell entfernt den Hinweis.

Konfigurierte Knoten/Quellen einschließlich unsichtbarer Einträge und generierte
Elemente teilen die bisherigen Gesamtbudgets von 100 Knoten und 200 Verbindungen.
Eine von aktuellen Werten abhängige Überschreitung liefert Status 205 mit
Knotenzeile statt Teilmodell oder stillschweigender Unterdrückung. Die bisherigen
65536-Byte-Listen, 24-Zeichen-Einheiten und finalen 1-MiB-Ausgaben bleiben erhalten.
Es werden keine Symcon-Variablen angelegt oder verändert.

Differenzen können durch fehlende Quellen, Messabweichungen und verschiedene
Aktualisierungszeiten entstehen. Die aktuellen Einzelwerte sind kein
synchronisierter physikalischer Snapshot. Das Modell ist eine ausdrückliche
lokale Darstellungshilfe, keine globale Bilanz-/Verlustbewertung.

## Zentralisierung und Nachweise

Die Bilanzbildung bleibt im bestehenden familienlokalen `SankeyModel`.
Live-Ausgaben, Formularprüfung und beide SVG-Vorschauen verwenden denselben
Modellbauer und statusfreien Vorschauleser. Gemeinsame kompakte Formularcallbacks
und SVG-/Design-/Interaktionsbausteine bleiben in Gebrauch. Die geprüften
Upstream-Helper haben keinen fachlichen Mengenflussvertrag; neue Kopien,
universelle Flussabstraktionen oder Änderungen an Gateway und ECharts entfallen.

`tests/sankey_balance.php` begann RED am fehlenden Bilanzfluss. Die Tests prüfen
beide Richtungen, exakte Altmodelle, Umkehr vor Bilanz, Null/Rundungsreste und
kleinste/größte endliche Werte, unabhängige benachbarte Bilanzen, Identitäten
und die unveränderten Grenzen einschließlich unsichtbarer Konfiguration.
`--sankey-balance` prüft echte Module/Gateway mit synthetischem Kernel,
beide HTML-Ausgaben und SVGs, ausgeführte ungespeicherte Formcallbacks,
Einheitenprüfung einschließlich Nullquellen, Updates/Erholung und maximale
100-/200-Modelle samt finalen Bytebudgets. Renderer-Tests verwenden die echte
ECharts-Geometrie für beide Adapter, Ausrichtungen und kleine/große Ansichten;
Kennzeichnung und escaped Tooltips bleiben auch bei versteckten Labels erhalten.
Die lokale Canvas-Browserprüfung bestätigt beide Differenzrichtungen, die
HTML-/SVG-Kennzeichnung, exakte Bilanz ohne Zusätze und kontrollierte
Budgetdiagnose. Berechnete Tooltips bleiben innerhalb der eingebetteten Breite
mit Umbruch lesbar. Die Konsole blieb fehlerfrei; echte Mausradaktionen scrollten
den Testhost über beiden Adaptern abwärts/aufwärts (0 → 637 → 0), ohne Chartzoom.
MCP-/Host-Abnahme nach Modulupdate ist ein separater noch offener Nachweis.
