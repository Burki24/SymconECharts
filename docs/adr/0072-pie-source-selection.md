# ADR 0072: Hundert Pie-Quellen mit optionaler Quellenauswahl

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0038, 0063, 0067 und 0069

## Vertrag und Kompatibilität

Pie erlaubt 1–100 eindeutige numerische Quellen. Ohne Normalisierung bleiben
nichtnegative endliche Werte und dieselbe effektive Einheit erforderlich.
Normalisierung verwendet weiter explizite Min/Max je Quelle, Originalmetadaten
und Gewichte 0–100. Kreis, Donut und Nightingale bleiben dieselbe Familie.
Kein Archiv, keine automatischen Restgruppen oder stillen Quellenkürzungen.

`EnableSourceSelection` ist additiv und standardmäßig `false`. Das optionale
boolesche Quellenfeld `InitiallyVisible` gilt ohne Angabe als `true` und wirkt
nur bei aktivierter erweiterter Auswahl. Bestehende einzelne Legendenwahl
bleibt auch ohne Aktivierung nutzbar. Aktivierung ergänzt Alle/Umkehren und
erzwingt eine erreichbare Legende auch in kompakten Ansichten oder bei
`ShowLegend=false`. Ein Moduswechsel setzt die flüchtige Auswahl zurück.

Properties, Quellen-JSON, Präfix, GUID, Ident und Designvorgaben werden nicht
umgeschrieben. Schema 1 erhält ausschließlich additive Felder
`pie.enableSourceSelection` und `items[].initiallyVisible`. Das fachliche Modell
enthält stets alle Quellen; versteckte Quellen behalten Gateway-Lesezugriff,
Referenz, VM-Abonnement und vollständige Validierung.

## Ansicht und Anteilsberechnung

Die bestehende gemeinsame `echarts-series-selection.js`-Logik aus
TimeSeries/BarHistory/ThemeRiver besitzt ID-Auswahl und Legendenanker. Pie
bettet sie über `EChartsAsset::SeriesSelectionJavaScript()` ein. Sichtbare
Namen sind keine Schlüssel. Aktualisierung, Sortierung, Entfernung,
Umbenennung, Resize und Themewechsel bewahren die verbleibenden IDs; geänderte
Anfangsvorgaben gelten je ID. Reload beginnt mit der gespeicherten Vorgabe.
Palette und Sortierung verwenden weiterhin vollständige Quellenreihenfolge.

Sektorbeschriftung und sichtbare Geometrie beziehen sich auf die aktuelle
Auswahl. Der Tooltip benennt Auswahl- und Gesamtanteil getrennt, einschließlich
Originalwert, Einheit und bei Normalisierung Originalbereich/normalisiertem
Gewicht. Normierte Gewichtsanteile sind keine physikalischen Mengenanteile.
Die Prozentrechnung verwendet max-skalierte Nenner statt potenziell
überlaufender Summen; vollständiges und sichtbares Kollektiv erhalten getrennte
Skalierung, damit große versteckte Werte kleine sichtbare Werte nicht auslöschen.
Nur bei drohendem Überlauf wird die Engine-Geometrie proportional skaliert;
das Modell und die Tooltip-Originale bleiben unverändert.

Keine Auswahl, ausgewählte Nullwerte und echte Gesamt-Null werden getrennt
gemeldet. Chart und Legende bleiben erreichbar; `stillShowZeroSum=false`
verhindert erfundene gleich große Sektoren.
`showEmptyCircle=false` unterdrückt zusätzlich den grauen nativen Platzhalter.
Eine flächen-/schriftabhängige verteilte Beschriftungsauswahl begrenzt bei
großen Modellen nur Text und Führungslinien; Sektoren, Tooltips und Legende
bleiben vollständig. Das benötigt keine Änderung des gepinnten Runtime-Builds.

## Vorschau, Budgets und Nachweise

Der vorhandene Vorschauresolver bekommt nur für Pie die explizite Grenze 100.
`PieData` besitzt Quellenobergrenze und reine overflow-sichere Prozentrechnung.
Die Vorschau zeigt höchstens sechs anfangs sichtbare Quellen, berechnet beide
Anteile jedoch vor dem Ausschnitt. Winkel, Nightingale-Winkelanzahl und
maximaler Radius stammen aus der gesamten Anfangsauswahl; ausgelassene Sektoren
werden nicht als neue Gesamtheit oder als künstlicher Rest gezeichnet.
Farbindizes und Quell-IDs bleiben nachvollziehbar. Quellenzahlen und
Anfangssichtbarkeit kennzeichnen den Ausschnitt; Entwürfe schreiben keine
Properties und lesen weder Gateway noch Archive.

Modelle bleiben auf 200000 JSON-Bytes, vollständige HTML-Dokumente auf 1 MiB
begrenzt. Überschreitung führt zu einem begrenzten ausdrücklichen Fehler,
nicht zu einer unvollständigen Anteilsberechnung. Runtime, Fremdlizenzen,
Gateway und synchronisierte Helper bleiben unverändert.

Grenz-/Altlisten-/Identitäts-, Gateway-/Update-, Vorschau-/Nenner-,
Normalisierungs-/Null-/Überlauf- und finale Ausgabeprüfungen sichern beide
Ausgabewege ab. Native Tests verwenden die ausgelieferte Pie-Runtime.
Lokale synthetische Browserprüfungen ersetzen keine Live-Abnahme nach Update
in Symcon-Kachel und IPSView; diese bleibt beim Eigentümer.
