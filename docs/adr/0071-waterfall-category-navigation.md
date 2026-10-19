# ADR 0071: Waterfall mit 100 Quellen und Kategoriennavigation

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0034, 0036 und 0039

## Entscheidung und unveränderte Verträge

BarWaterfall erlaubt 2–100 eindeutige numerische Quellen derselben effektiven
Einheit: einen absoluten Startwert und 1–99 vorzeichenbehaftete Änderungen.
Die Endsumme ist ein berechneter Schritt, keine zusätzliche Quelle; maximal
101 Kategorien. Das bestehende Schema 1 mit `steps[].id`, `kind`, `change`,
`before`, `after` und Zeitstempel bleibt unverändert. Quellenreihenfolge,
Properties, IDs, Präfix, GUID, Farben und Designs werden nicht umgeschrieben.
Negative Starts, Zwischenstände und Endsummen bleiben zulässig. Keine Archive,
Normalisierung, automatische Sortierung oder neuen fachlichen Zwischensummen.

## Bilanz und gemeinsame Logik

Die vorhandene aktuelle Gateway-Operation und `EChartsCurrentSources` lesen
und abonnieren weiterhin alle Quellen. `WaterfallData::Totals` besitzt die
Quellobergrenze und die geordnete endliche Addition für Laufzeitmodell und
Vorschau. Nichtendliche Einzel- oder kumulierte Werte werden abgewiesen,
nicht still abgeschnitten. Gleiche Namen bleiben anhand der Quell-IDs getrennt.

BarCategory und BarWaterfall verwenden `echarts-category-navigation.js` für
die flüchtige Erfassung eines Kategorieankers aus öffentlichem `getOption()`
und die Begrenzung des sichtbaren Fensters. Kein Family-Modell, keine
Geometrie oder Datenmutation im gemeinsamen Baustein. `EChartsAsset` lädt ihn
nur für diese Familien; synchronisierte Helper und Runtime bleiben unverändert.
Der bestehende BarCategory-Minify-Build wird reproduzierbar neu erzeugt.

## Native Darstellung und Vorschau

Die bestehende vertikale Waterfall-Geometrie bleibt erhalten. Bei Platzmangel
ergänzen Slider und Inside-Navigation ein horizontal verschiebbares Fenster.
Die Dichte berücksichtigt Balkenbreite, Achsen-/Werteschrift und sichtbare
Werttexte. Der ID-Anker bleibt bei Aktualisierung, Umsortierung, Größen-/Themewechsel
erhalten; entfernte Anker und verkürzte Listen werden begrenzt, leere Listen
setzen ihn zurück. Beide Resize-Wege bauen die responsive Geometrie neu auf.

Sichtbare Kategorien sind eine reine Ansicht. Die Werte aller ursprünglichen
Schritte einschließlich versteckter Extrema bestimmen die gemeinsame Skala.
Vorberechnete Zwischenstände werden nicht nach dem Ausschnitt neu summiert.
Gefilterte native Serien bewahren die ursprüngliche Schritt-ID für Tooltips,
Beschriftungen und den letzten Gesamtschritt. Eine lokale Browserauswahl wird
nicht in Symcon gespeichert. Kleine Modelle ohne Navigation behalten ihre Labels.

Die Vorschau zeigt höchstens fünf Quellen, bei Bedarf ein Auslassungszeichen
und ausdrücklich die Endsumme aus allen Quellen. Auch Skala, Einheit und höchste
Präzision kommen aus der vollständigen Liste. Der Ausschnitt nennt 5/100 statt
alle Quellen zusammenzudrängen oder die Präfixsumme als Gesamt auszugeben.
Nur ungültige/fehlende Quellen verwenden gekennzeichnete Beispieldaten; Vorschauen
lesen keine Archive oder Gatewaywerte und schreiben keine Konfiguration.

## Ressourcen und Nachweise

Der gemeinsame Aktualisierungspfad ist durch 100 Quellen begrenzt. Das Modell
besitzt eine Grenze von 200000 JSON-Bytes, das HTML einen abschließenden
1-MiB-Guard. Zu große Beschriftungen oder Einheiten führen zu einem begrenzten
expliziten Fehlerdokument statt unvollständiger Bilanzen oder stiller Kürzung.

Grenz-, Altlisten-, ID-, Einheiten-, Vorzeichen-/Überlauf-, Update-/Referenz-,
Vorschau- und Größenprüfungen sichern die PHP-/Gateway-Verträge ab. Native
gepinnt-runtimebasierte Tests prüfen Navigation, letzte Kategorie/Tooltip,
vollständige Skala, Reorder/Resize sowie beide Ausgabearten. BarCategory erhält
Regressionsprüfungen des gemeinsamen Bausteins einschließlich maximaler Ausgaben.
Lokale synthetische Browserprüfungen ersetzen keine erneute Live-Abnahme in
Symcon-Kachel und IPSView. Diese bleibt nach Modulupdate beim Eigentümer;
Live-Konfigurationen und Quellwerte werden bei der Umsetzung nicht verändert.
