# ADR 0073: Hundert Polar-Quellen mit Auswahl und Seiten

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0035, 0063, 0067, 0071 und 0072

## Vertrag und Kompatibilität

BarPolar erlaubt 1–100 eindeutige numerische Quellen. Endliche negative Werte
bleiben erlaubt. Ohne Normalisierung ist dieselbe effektive Einheit erforderlich;
explizite Min/Max-Normalisierung behält Originalwerte, Einheiten und Präzision.
Radiale Balken und konzentrische Bögen bleiben dieselbe Familie. Kein Archiv,
keine Restgruppen, keine stillen Quellenkürzungen oder Live-Konfigurationsänderungen.

`EnableSourceSelection` ist additiv und standardmäßig `false`; das optionale
boolesche Quellenfeld `InitiallyVisible` bedeutet ohne Angabe `true` und wirkt
nur bei aktiver Auswahl. Schema 1 erhält additive Felder
`polar.enableSourceSelection` und `items[].initiallyVisible`. Quellen-JSON,
Properties, GUID, Präfix, Idents und Designvorgaben werden nicht umgeschrieben.
Das Modell, Gateway-Lesezugriffe, Referenzen, VM-Abonnements und Validierung
enthalten immer alle Quellen, auch versteckte und andere Seiten.

Bis 16 Quellen ohne Auswahl bleiben bisherige Geometrie, automatische native
Wertachse und fehlende Navigation erhalten. Große Modelle und aktivierte Auswahl
nutzen eine getrennte Ansicht: ein tatsächliches Polar-Bar-Serienmodell zeichnet
nur die gewählte Seite. Kein Nachbau von ECharts und keine künstlichen
Legendenserien. DOM-Checkboxen mit Alle/Umkehren steuern die optionale Auswahl;
Vorherige/nächste Seite und Bereichsanzeige bleiben auch ohne Auswahl erreichbar.
Seitenkapazität hängt von Platz, Schrift und Polar-Geometrie ab, höchstens 32.

## Gemeinsame Ansichtslogik und vollständige Skala

`echarts-series-selection.js` erhält direkte `select`, `selectAll` und `invert`
für DOM-Steuerelemente zusätzlich zu seiner unveränderten nativen Legendenwahl.
`echarts-category-navigation.js` erhält `rememberWindow` für direkte Fenster
zusätzlich zum bisherigen DataZoom-Adapter. BarPolar bindet beide vorhandenen
Bausteine über `EChartsAsset` ein. Auswahl und erste sichtbare Quelle werden
an IDs gebunden; Labels sind keine Schlüssel. Aktualisierung, Sortierung,
Resize, Themewechsel und entfernte Quellen bewahren verbleibende Identitäten.
Automatische Farben bleiben in den Auswahl-/Seitenansichten ebenfalls ID-stabil;
kleine Altlisten ohne Auswahl behalten die bisherige sortierungsabhängige
Palettenzuordnung. Die fixierte Hervorhebung bleibt ID-stabil;
ein nur vorübergehend außerhalb der Seite liegender Pin wird nicht gelöscht.
Reload startet mit gespeicherten Anfangsvorgaben. Diese Zustände bleiben lokal
im geöffneten Dokument, nicht in Symcon-Properties.

Auswahl und Seiten verändern niemals die Bezugsmenge der Werteskala. Der
Auto-Bereich umfasst alle Modellwerte und Null, bei Gesamt-Null gilt 0–1.
Manuelle Bereiche bleiben je Designer unverändert; Normalisierung bleibt 0–100.
Tooltip und Werttexte zeigen weiterhin Originalwerte samt Einheit sowie die
Normalisierungsmetadaten. Eine leere Auswahl zeichnet keine erfundenen Balken.
Nullwerte bleiben echte Quellen; ein kleiner Wert wird nicht zum neuen Maximum.

## Vorschau, Ressourcen und Nachweise

Der vorhandene Vorschauresolver erhält nur für BarPolar die explizite Grenze
100. `PolarData` kapselt die familienbezogene Obergrenze und reine vollständige
Bereichs-/Vorschauprojektion. Die Vorschau berechnet ihre nullinklusive Skala
vor Auswahl und Sampling und zeigt höchstens sechs anfangs sichtbare Quellen.
IDs, vollständige Palettenpositionen und Quellenzahlen kennzeichnen den
Ausschnitt. Versteckte späte Extremwerte bleiben im Maßstab berücksichtigt;
manuelle unabhängige IPSView-Bereiche und feste Normalisierung gelten weiter.
Drafts ändern keine Properties und lesen weder Gateway noch Archive.

Modelle sind auf 200000 JSON-Bytes begrenzt, vollständige HTML-Dokumente auf
1 MiB. Überschreitungen liefern einen begrenzten ausdrücklichen Fehler mit
Hinweis auf zu lange Beschriftungen oder Einheiten, nie Teilmodelle.
Gateway, synchronisierte Helper, ECharts-Runtime und Fremdlizenzen bleiben
unverändert. Offizielle API-Grundlage sind `radiusAxis.min/max`,
`angleAxis.min/max` und `series-bar` im Polar-Koordinatensystem, siehe
[Radiusachse](https://echarts.apache.org/en/option.html#radiusAxis.min),
[Winkelachse](https://echarts.apache.org/en/option.html#angleAxis.max) und
[Polar-Balken](https://echarts.apache.org/en/option.html#series-bar.coordinateSystem).

Reine Grenz-/Projektionsprüfungen, isolierte Gateway-/Ausgabeintegration,
Altlisten, Vorschau/Palette/Skala, Fehler und Bytebudgets sowie echte native
Polar-Layouttests sichern die Änderung ab. Lokale synthetische Browserprüfungen
ersetzen nicht die Live-Abnahme nach Modulupdate in Symcon-Kachel und IPSView.
