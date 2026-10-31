# ADR 0109: Versionierter JSLive-Export und bestätigte ECharts-Übernahme

- Status: Angenommen; ausschließlich Vertrag, Implementierung und Abnahme offen
- Datum: 2026-10-31
- Ergänzt: JSLive ADR 0006 und die Trennung der Diagrammfamilien aus ADR 0001

## Kontext

Der Eigentümer hat der Community eine möglichst nahtlose Übernahme bestehender
JSLive-Charts zugesagt und die Beta bis zur Umsetzung zurückgestellt. Der
vorhandene JSLive-Konfigurationsexport bewahrt nicht alle erforderlichen
Informationen: der gefilterte Export lässt Bindungen aus, Steuerzustände liegen
teilweise in Variablen und Profilnamen ersetzen keine Darstellungsdefinition.
Zudem sind historische Radarwerte, Mehrfachringe und gemischte historische
Linien-/Balkenmodelle nicht mit den heutigen Zielfamilien gleichzusetzen.

## Entscheidung

- Der gemeinsame [Export-/Importvertrag 1.0](../JSLIVE_MIGRATION_CONTRACT.md)
  ist die kanonische Spezifikation beider Libraries. JSLive ADR 0007 verweist
  auf dieselbe Datei, statt eine zweite Definition zu pflegen.
- JSLive erfasst vollständig; SymconECharts prüft und plant die bestätigte
  Übernahme in neue Instanzen. Der alte JSLive-Export bleibt kompatibel.
- Profile werden vollständig aufgelöst und in profilunabhängige native
  Darstellungen sowie Chart-Metadaten übersetzt. Präfix/Suffix, Schrittweite,
  Zustände, Bereiche, Farben, Icons und Formatintervalle dürfen nicht verloren
  gehen. Prozenttext, native Prozentformatierung und Normalisierung sind getrennt.
- Bestehende Quellen, Profile, Actions, Archive und Designs bleiben unverändert.
  Darstellungsdefinitionen für neue Ziele bedeuten keine Profilumstellung an
  bestehenden Messvariablen. Archivdaten werden weiter über dieselben IDs gelesen.
- Ein vollständiger Feldbericht, Vorschau und konkrete Bestätigung gehen jeder
  Anlage voraus. Unaufgelöste Quellen/Profile und ungültige Daten blockieren.
  Annäherungen sind einzeln kenntlich und niemals „vollständig übernommen“.
- Ein Importjournal sichert Wiederholung und Wiederanlauf. Eigenes JSLive bleibt
  als Vergleich/Rückfall erhalten; keine automatischen Umbauten von IPSView.
- Ressourcen und eigener Code bleiben inert beziehungsweise validiert.
  Austauschformat, Präsentationskonverter und fachliche Mapper sind getrennt;
  gemeinsame Helper entstehen bei tatsächlicher Nutzung beider Libraries.
- Neue Fachmodelle, Modulidentitäten und weitere öffentliche APIs werden erst
  in den jeweiligen Implementierungsschritten entschieden. Keine Erweiterung
  bestehender Familiengrenzen allein durch diesen Vertrag.

## Folgen und Nachweise

Die Pflichtmatrix im Vertrag bildet die Abnahmegrundlage. Vorhandene Export-,
Archiv- und Designtests sind Ausgangsnachweise, keine Migrationstests. Aktuell
fehlen insbesondere der vollständige Profilkonverter, Paketparser, Mapper,
Importjournal und die Vergleichsabnahme. Ein vollautomatischer pixelgleicher
Import beliebiger eigener Chart.js-Templates wird nicht zugesagt.

Dieser Schritt ändert ausschließlich Entwicklungsdokumentation und Regeln.
Laufzeit, Properties, GUIDs, Versionsmetadaten und Installationen bleiben erhalten.
