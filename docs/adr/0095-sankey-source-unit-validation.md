# ADR 0095: Sankey-Einheitenmodell und optionale Quellenprüfung

- Status: Angenommen
- Datum: 2026-10-23
- Ergänzt: ADR 0051

## Vertrag

`UnitMode` ist additiv mit Default `manual`. GUID, Properties, Quellenzeilen,
Idents und Designer bleiben erhalten; keine Migration erforderlich.

| Modus | Einheit und Prüfung |
| --- | --- |
| `manual` | Bisheriger getrimmter `Unit`-Text mit höchstens 24 Zeichen. Kein Metadatenzugriff für Einheiten und keine neue Prüfung alter Flüsse. |
| `automatic` | Alle Quellen müssen dieselbe aufgelöste Einheit besitzen. Diese wird angezeigt. Gespeichertes `Unit` ist inaktiv und wird weder ersetzt noch gelöscht. |
| `checked` | Alle aufgelösten Quelleinheiten müssen dem getrimmten `Unit` entsprechen. Leer erwartet ausdrücklich einheitenlos. |

Nur äußere Leerzeichen werden entfernt. Kennzeichen sind case-sensitiv;
W, kW und kWh bleiben verschieden. Native Suffixe überschreiben den Suffix
eines referenzierten Legacy-Profils. Leere/fehlende Suffixe bedeuten einheitenlos;
unlesbare Profile, API-Fehler und falsch typisierte Metadaten werden in geprüften
Modi nicht als leere Einheit kaschiert. Effektive Einheiten umfassen höchstens
24 Zeichen. Es erfolgt keine Dimensionsanalyse, Umrechnung oder Normalisierung.
Die Anwender bleiben für dieselbe fachliche Größe und Rohwertbasis verantwortlich.

Alle konfigurierten Verbindungen werden vor Gateway-Wertabfragen geprüft,
einschließlich Nullflüssen und späteren Zeilen. Mehrfach genutzte Variablen
werden innerhalb der Unit-Policy einmal je ID aufgelöst; ihre Werte bleiben
vollständig an jeder Kante. Gleichnamige Quellen und Knoten bleiben getrennt.
Nicht passende oder unlesbare Einheiten erhalten eine übersetzte Zeilendiagnose
mit Status 201. In geprüften Modi zeigen auch die HTML-Ausgaben diese konkrete
Konfigurationsdiagnose statt teilweise plausibler Flüsse. Manuelle Altfehler
behalten ihre bisherige allgemeine Ausgabemeldung.

Nichtnegative endliche Werte, gerichtete eindeutige Paare, DAG, keine Selbstkante,
2–100 Knoten, 1–200 Verbindungen, begrenzte JSON-Zeilenlisten (65536 Bytes) und
1-MiB-Ausgaben bleiben unverändert. Es gibt keine neue Negativumkehr oder Bilanzknoten.

## Wiederverwendung

`SankeyModel::ResolveUnit` enthält ausschließlich die Familienpolicy und erhält
einen injizierten Metadatenleser. `GetValidatedConfiguration` wird von Live-Modell,
Diagnose und beiden aktuellen SVG-Vorschauen geteilt. Alle verwenden die bereits
gemeinsame `EChartsVariablePresentation::Resolve`; ein optionales `strict=false`
erhält das bisherige Verhalten sämtlicher anderer Consumer. Nur bei ausdrücklich
aktiviertem Zugriff werden unlesbare oder falsch typisierte Einheitenmetadaten
abgewiesen. Bereiche und Präzision werden durch diesen Schritt nicht verändert.

Die geprüften Upstream-Helper `VariablePresentationHelper` und `VariableHelper`
erzeugen Darstellungen beziehungsweise lesen Werte nach Ident, besitzen aber
keinen passenden lesenden Metadatenvertrag. Die bestehende ECharts-Abstraktion
wird deshalb erweitert, nicht kopiert. Formularsichtbarkeit und SVG-Kodierung
verwenden vorhandene gemeinsame Helper; deren abonnierten Dateien bleiben
unverändert. Der bestehende Renderer formatiert die gemeinsame effektive Einheit
bereits in Knoten-/Kantenbeschriftungen und escaped Tooltips, ohne neue Geometrie.

## Nachweise und Grenzen

`tests/sankey_units.php` begann RED an der fehlenden Unit-Policy. Die fokussierte
Integration verwendet echte Module/Gateway/Metadatenauflösung mit synthetischem
Kernel. Sie prüft native und Legacy-Quellen, Vorrang, gleiche Kennzeichen,
einheitenlose Mengen, ungültige/unlesbare Metadaten, späte Nullflüsse,
Mehrfachverwendung, unveränderte Rohwerte, reine Entwürfe und unabhängige Designer.
100 Knoten/200 Flüsse mit maximalen Labels und 24-Zeichen-Einheit prüfen Form-/
HTML-Budgets; bisherige Struktur-/Werte-/N+1-Tests bleiben aktiv.
Rendererprüfungen bestätigen effektive Einheiten und sichere Original-Tooltips.

Die lokale Browserfixture `tests/sankey_units_browser.mjs` verwendet ausschließlich
synthetische Werte mit echten Modul-Ausgaben und der ausgelieferten Runtime.
Die lokale Canvas-Prüfung bestätigt automatische/geprüfte Einheiten,
manuelle Anzeige, einheitenlose Quellen und dieselbe Zeilendiagnose in beiden
HTML-Adaptern und SVG. Die Fixture liefert das unveränderte Interaktionsasset
am normalen Gateway-Pfad aus. Echte Mausradaktionen scrollten die Testseite
über den Sankey-Frames in beide Richtungen (0 → 637 → 0), ohne Diagrammzoom;
die abschließende frische Browserkonsole blieb ohne Warnungen/Fehler.
Neue MCP-Abnahme und tatsächliche Symcon-Kachel-/IPSView-Prüfung bleiben nach
Modulupdate offen. Keine laufende Instanz, Quelle oder Archivierung wird geändert.
