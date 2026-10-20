# ADR 0075: Funnel-Quellenauswahl und Stufenseiten

- Status: Angenommen
- Datum: 2026-10-20
- Entscheider: Burki24
- Ergänzt: ADR 0055, 0036, 0067, 0073 und 0074

## Entscheidung und Kompatibilität

EChartsFunnel unterstützt 1–32 unterschiedliche numerische aktuelle Quellen
derselben Einheit. Nichtnegative Originalwerte, echte Nullwerte, konfigurierte
Reihenfolge beziehungsweise stabile Wertsortierung bleiben verbindlich.
Es gibt weder Normalisierung noch Prozentanteile oder eine erzwungene
Prozessabnahme. GUID, Präfix, Idents, Ausgabewege und bestehende Designer bleiben
unverändert. Die Property `EnableSourceSelection` ist standardmäßig `false`.
Das optionale Quellenfeld `InitiallyVisible` ist boolesch; fehlend oder `null`
bedeutet wie bei den benachbarten Familien `true`. Bei deaktivierter Auswahl
wird es ignoriert. Alte Quellenlisten werden nicht umgeschrieben.

Der Schema-1-Vertrag wird additiv um `funnel.enableSourceSelection` und
`funnel.data[].initiallyVisible` ergänzt. `funnel.data` bleibt die vollständige
konfigurierte Liste mit IDs, Originalwerten, Einheiten, Genauigkeit und Zeitstempeln.
Alle Quellen werden geprüft, gelesen, referenziert und über VM_UPDATE abonniert,
einschließlich versteckter Quellen und anderer Seiten. Fehler solcher Quellen
dürfen nicht durch eine günstige Ansicht verborgen werden.

## Ansicht und vollständige Breitenskala

Listen bis 16 Quellen behalten bei deaktivierter Auswahl die bisherige
Darstellung ohne zusätzliche Bedienleiste. Größere Listen sowie aktivierte
Auswahl verwenden responsive Seiten in horizontaler und vertikaler Ausrichtung.
Die Kapazität berücksichtigt Platz, Schriftgröße und Stufenabstand und liegt
zwischen einer und 16 Stufen. Auswahl, Alle/Umkehren und Vorherige/Nächste mit
Bereichsanzeige erschließen sämtliche gewählten Stufen. Leere Auswahl und eine
ausschließlich aus Nullwerten bestehende Auswahl sind ausdrücklich benannt und
bleiben über die Bedienleiste wiederherstellbar.

Das Maximum wird aus allen konfigurierten Werten vor Auswahl und Seitenschnitt
berechnet und als `series.max` an die native Funnel-Geometrie übergeben.
Eine Stufe wird nicht breiter, weil das Maximum auf einer anderen Seite liegt
oder abgewählt wurde. Auch `minSize`/`maxSize` bleiben dieselbe optische Abbildung
mit unveränderten Originalwerten. Die sichtbaren Nachbarn bilden die nativen
Trapeze; das Seitenende ist kein zusätzlicher Nullmesswert und keine Teilbilanz.

Zustände sind flüchtig und ID-basiert: Auswahl, Seitenanker und automatische
Farben überstehen Aktualisierung, Umbenennung, Reorder, Themewechsel und Resize.
Entfernte Quellen werden verworfen, neue Quellen erhalten ihre Anfangsvorgabe.
Neuladen startet erneut aus der gespeicherten Anfangssichtbarkeit. Kachel und
IPSView behalten ihre unabhängigen Designer und Ansichten.

## Wiederverwendung und Grenzen

Die vorhandenen Bausteine `echarts-series-selection.js`,
`echarts-category-navigation.js` und `echarts-source-controls.js` samt CSS werden
über EChartsAsset eingebunden. Sie besitzen ausschließlich Auswahlzustand,
Kategorieanker und DOM-Bedienung, keine Funnel-Skala oder Geometrie.
Der gemeinsame PHP-Resolver EChartsCurrentComparisonSources validiert die
Sichtbarkeitsmetadaten ausdrücklich opt-in; sein Standardbudget bleibt 16,
andere Verbraucher ändern ihre bestehenden Verträge nicht. Gateway,
synchronisierte Helper und gepinnte ECharts-6.1.0-Runtime bleiben unverändert.

Modelle sind auf 200000 JSON-Bytes begrenzt, vollständige HTML-Dokumente auf
1 MiB. Überschreitung liefert eine begrenzte ausdrückliche Fehlermeldung mit
Status 202 und internem `MODEL_TOO_LARGE`, keine gekürzten Quellen oder Assets.
Die bestehenden Statuscodes für Quellen, Transport und negative Werte bleiben
erhalten. Keine neue Kompression, neue Archivoperation oder CDN-Ressource.

## Vorschau und Nachweise

Die SVG-Vorschau validiert und liest alle aktuellen Quellen ohne Gateway oder
Archiv und ohne Konfigurations-/Statusänderung. Das vollständige Maximum bleibt
vor Filterung erhalten. Neue Auswahl-/Seitenansichten zeigen nach Anfangsauswahl
und Sortierung höchstens acht echte Stufen mit Ausschnitt-/Quellenzahlen.
Altlisten bis 16 ohne Auswahl behalten ihre volle bisherige Vorschau. Ungültige
Werte erzeugen einen Hinweis, keine Ersatzdaten; nur eine leere Konfiguration
verwendet die vorhandenen ausdrücklich gekennzeichneten Beispieldaten.

Isolierte Modul-/Gateway-Tests prüfen 32/33, Altlisten, Updates, Einheiten,
Identitäten, versteckte Fehler, Vorschau und getrennte Ressourcenlimits.
Renderer-Tests messen tatsächliche Polygonbreiten im gepinnten ECharts und
prüfen Navigation, leere Zustände, Farben und beide Ausrichtungen/Ausgaben.
Lokale synthetische Canvas-Browserprüfungen ersetzen keine Live-Abnahme nach
Modulupdate in Symcon-Kachel oder der IPSView-App.
