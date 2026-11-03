# ADR 0103: Optionale gewichtete Graph-Beziehungen

- Status: Angenommen
- Datum: 2026-10-25
- Entscheider: Burki24
- Ergänzt: ADR 0047, ADR 0066 und ADR 0102

## Vertrag

Je Verbindung ist `WeightMode=none|manual|variable` mit kompatiblem Default
`none` wählbar. Manueller Modus verwendet `Weight=1`, `WeightUnit=''` (bis
24 Zeichen), `WeightDecimals=2` (0–6). Variablenmodus verwendet
`WeightVariableID` als positive ID einer existierenden Integer-/Float-Variable.
Andere Modusfelder bleiben dormant. Eine Quelle darf außerhalb der Knotenliste
liegen und mehrere Kanten versorgen. IDs werden nicht aus Namen abgeleitet.

Gewichte sind endlich und nichtnegativ. Ungültige Werte sind Fehler, keine
fehlenden oder ungewichteten Ersatzverbindungen. Null bleibt eine echte Null
mit sichtbarer Mindestbreite. Gerichtete/ungerichtete Topologie, erlaubte Kreise
und Dublettenregeln bleiben unverändert. Gewichte kehren keine Richtung um.

Gemeinsame Properties `WeightMinimum=0.0` und `WeightMaximum=100.0` bilden einen
festen, nichtnegativen, endlichen, aufsteigenden Zahlenbezug. Er wird nur bei
aktiver Gewichtung geprüft und nicht aus aktuellen Daten abgeleitet. Einheiten
bleiben je Gewicht original; es gibt keine automatische Umrechnung oder Bilanz.
Die unabhängigen Designer erhalten `MinimumEdgeWidth=1`, `MaximumEdgeWidth=8`
in Pixeln; 1–20, Mindestbreite höchstens Höchstbreite. Gleiche Pixelgrenzen sind
zulässig. Rohgewicht und Bezug bestimmen linear die begrenzte Breite. Werte
außerhalb des Bezugsbereichs behalten ihre Originale im Tooltip.

## Zuständigkeiten und Kompatibilität

`GraphModel` erweitert seine letzte optionale Parameterliste kompatibel um
Gewichtsoptionen und einen Quellenleser. `EChartsValueRange` und
`EChartsNormalization` bleiben die numerischen Grundlagen. Das additive
Kantenfeld `weight` enthält Originalwert, formatierte Anzeige, Einheit,
Variablen-ID, Messzeitpunkt und feste Grenzen. Manuelle Gewichte haben explizit
keine Quellen-ID oder Messzeit. Das additive `width` versorgt beide Renderer
und SVGs mit genau derselben fachlichen Projektion; keine Skalierung im Browser.
Nicht gewichtete Kanten behalten ihre bisherigen Modelle und Breiten.

`EChartsCurrentSources` wird unverändert für Gateway-Zugriff und bestehende
Referenz-/Abonnementverwaltung verwendet. Graph liefert dafür seine eigenen
aktiven Gewichts-IDs. Ein ID-bezogener Cache gilt nur für den einzelnen
Modellaufbau; kein fachlicher Wertcache über Aktualisierungen hinweg.
Vorschauen lesen den Kernel direkt, damit ein Gateway-Ausfall weder Status
noch gespeicherte Einstellungen verändert. Quellenprüfung, numerische Regeln,
Metadatenauflösung und Formatierung bleiben gemeinsam. Anzeigen werden aus
dem tatsächlich gelesenen Wert mit dessen Einheit/Präzision gebildet; ein
zweiter Wertabruf könnte sonst Tooltip und Breite auseinanderlaufen lassen.

Native Graph-Linien erhalten die berechnete Breite und ausreichend große
Zielpfeile. Hervorhebung erhält die quantitative Breite. Rohgewichte werden
ausdrücklich nicht an ECharts' Kräfteparameter als Kanten-`value` übergeben.
Pan/Zoom und Kräfte-Ausgangspositionen verwenden unverändert die gemeinsame
Graph-/Z2M-Ansichtsverwaltung. Gegenrichtungen bleiben getrennte Kurven.
ECharts 6.1.0 und seine ausgelieferten Artefakte bleiben unverändert.
Offizielle Referenz: [Graph-Kantenstil](https://echarts.apache.org/en/option.html#series-graph.links.lineStyle).

Das Formular verwendet weiterhin zentrale Formular-/SVG-Helper. Versteckte
Spalten erhalten `add` und `save`; ein frisch erzeugtes Zeilenformular setzt
auch bei Altzeilen jedes Feld explizit. Dadurch übernimmt eine Altzeile keine
Gewichte der vorher bearbeiteten Zeile. Unberührte Altlisten werden weder
migriert noch durch Defaultfelder vergrößert. Es werden ausschließlich
festgelegte Formulardaten in PHP-Code serialisiert, niemals Benutzereingaben.
Dieses Verhalten benötigt keine neue Symcon-9.1-exklusive Formkomponente.

## Budgets und Nachweise

Weiterhin 2–300 Knoten, 1–1000 Verbindungen, 64 KiB je persistierter Liste,
40/80 im echten Vorschauausschnitt und 1 MiB je Ausgabe. Zusätzliche Felder
können die unveränderte Bytegrenze vor der Kantenzahl erreichen. Alle Gewichte
werden vor dem Vorschauausschnitt geprüft; nicht lesbare Quellen oder ungültige
Metadaten bleiben Fehler. GraphZ2M und seine LQI-Skala werden nicht geändert.

Modell- und Renderer-Tests begannen RED an fehlender Gewichtsprojektion bzw.
fehlender nativer Linienbreite. Modultests prüfen feste Referenzen, Null,
Bereichsbegrenzung, Originalmetadaten, wiederverwendete Quellen, unabhängige
Designer, unmittelbare Wertänderungen, Abmeldung, alte Zeilenformulare,
readonly Vorschauen und unveränderte Count-/Bytebudgets. Native Tests verwenden
die wirklich ausgelieferte Runtime. Lokale Browser- und Gesamtsuitennachweise
stehen im Testdokument. Die anschließende MCP-Prüfung und Eigentümerbestätigung in Kachel und
tatsächlichem IPSView liegen vor. Es werden keine Live-Konfigurationen geändert.
