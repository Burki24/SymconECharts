# ADR 0076: Radar mit 16 Vergleichsgruppen und optionaler Gruppenauswahl

- Status: Angenommen
- Datum: 2026-10-20
- Entscheider: Burki24
- Ergänzt: ADR 0053, 0036 und 0037

## Vertrag

Radar unterstützt 1–16 vollständige Vergleichsgruppen auf unverändert 3–10
Achsen, damit höchstens 160 Variablenzuordnungen. Jede Gruppe benötigt weiterhin
genau einen aktuellen numerischen Wert je Achse. Fehlende Werte werden nicht
als Null ergänzt. Negative und außerhalb des Bereichs liegende Originalwerte
bleiben zulässig; nur die Geometrie wird anhand des expliziten Achsenbereichs
auf 0–100 begrenzt. Die Prüfung umfasst immer alle Gruppen.

Die bestehenden `Axes`-/`Sources`-Listen bleiben unverändert. Die fachliche
Gruppe wird weiterhin über ihren exakt zugeordneten, getrimmten Namen
identifiziert; die daraus gebildete Hash-ID bleibt erhalten. Eine Umbenennung
ist eine neue fachliche Gruppe. Variablen werden ausschließlich über ihre ID
gelesen und abonniert, einschließlich wiederholter Zuordnung derselben ID.

Additive globale Properties:

- `EnableGroupSelection=false`: optionale native Gruppenauswahl in beiden
  Ausgabewegen; keine unabhängige Designer-Property.
- `GroupVisibility='[]'`: höchstens 16 eindeutige Einträge aus `Group` und
  Boolean-`InitiallyVisible`. Fehlend oder `null` entspricht `true`.
  Nicht mehr verwendete Namen sind validierte, wirkungslose Zuordnungen,
  analog zu den vorhandenen Gruppenfarben. Nicht zugeordnete Gruppen starten
  sichtbar; bei ausgeschalteter Auswahl werden Sichtbarkeitsvorgaben ignoriert.

Das Schema 1 wird kompatibel um `radar.enableGroupSelection` und
`radar.data[].initiallyVisible` ergänzt. Gruppenfarben beider Designer erlauben
ebenfalls bis zu 16 eindeutige Zuordnungen. Keine Properties, Quellenlisten,
Instanznamen oder gespeicherten Designs werden automatisch umgeschrieben.

## Ansicht und Wiederverwendung

`libs/echarts-series-selection.js` verwaltet bereits flüchtige Auswahl und
Legendenanker für andere Familien und wird unverändert wiederverwendet.
Radar verwendet im Auswahlmodus Gruppen-IDs als native Legendenidentität;
Formatter, Tooltip und ARIA zeigen ausschließlich Anzeigenamen und Originalwerte.
Die native Scroll-Legende bietet Alle/Umkehren und bleibt auch bei leerer
Auswahl oder ausgeschalteter Designlegende zur Wiederherstellung sichtbar.
Auswahl und Anker überstehen Refresh, Resize und Themewechsel. Neue Gruppen
folgen ihrer Anfangssichtbarkeit; ein Neuladen verwirft die flüchtige Auswahl.

Alle Gruppen bleiben im Fachmodell, in Gateway-Lesezugriffen, Referenzen und
VM_UPDATE-Abonnements. Auswahl verändert weder Achsenanzahl noch deren Skalen,
Originalwerte oder Clippingwarnung. Standardfarben behalten im geöffneten
Diagramm ihre Gruppen-ID-Zuordnung. Kleine Altmodelle ohne neue Auswahl
behalten die bisherige Geometrie. Erweiterte Modelle reservieren nur den Platz
einer scrollbaren Legendenzeile statt 16 fiktiver Einträge untereinander.
Mehrzeilige Warnungen werden im Auswahlmodus getrennt von der Legende platziert.

Die gemeinsame Formularvorschau erhält eine kanonische Liste ihrer Listenfelder
einschließlich `GroupVisibility`, damit dieselben Felder sowohl unterstützte
List-Callbacks als auch indexierte Entwurfskopien erhalten. Skalare Werte werden
in den wiederholten Callback-Skripten mit ausdrücklich aktiviertem `compact()`
übertragen. Radar aktiviert diese gemeinsame Option; andere Familien behalten
ihre bisherigen Skripte und spezialisierten Listenadapter. Das reduziert
die Formulargröße ohne verlorene Entwurfsfelder, Datenänderung oder Änderung
eines synchronisierten Helpers. Die tatsächliche Ausführung eines generierten
Callbacks mit iterierbaren Listen und False-/Nullwerten ist getestet.

## Vorschau und Budgets

Die lesende SVG-Vorschau prüft alle Zuordnungen und aktuellen Werte, danach
filtert sie nach Anfangssichtbarkeit und zeichnet höchstens acht Gruppen.
Ursprüngliche Palette und vollständige Achsenbereiche bleiben erhalten.
Bei erweiterten Modellen nennt sie gezeichnete, ausgewählte und konfigurierte
Gruppen. Auch späte oder ausgeblendete ungültige Werte werden nicht verschwiegen;
die Clippingwarnung bleibt auf das vollständige Modell bezogen. Vorschauen
ändern weder Properties, Status noch Transport und fragen kein Archiv/Gateway ab.

Das Modell ist auf 200000 JSON-Bytes, finales HTML je Ausgabe auf 1 MiB begrenzt.
Überschreitungen liefern `MODEL_TOO_LARGE` und Status 202 sowie ein begrenztes
Fehlerdokument, keine stille Kürzung. Bestehende 65536-Byte-Konfigurationslisten
bleiben begrenzt. Ein korrigierter Titel stellt die Ausgabe wieder her.
Runtime, ECharts-Version, Gateway und Lieferartefakte bleiben unverändert.

## Nachweise und offene Laufzeitebene

- `php tests/radar_model.php`: ursprüngliche Werte/IDs/Skalen, 16/17 Gruppen.
- `php tests/gateway_gauges.php --radar-many`: 16×10 und 17×3, Altlisten,
  vollständiger Gateway-Datenweg, versteckte Updates/Fehler, Gruppenfarben,
  Sichtbarkeitsprüfung, unabhängige Designs, zustandsneutrale Entwurfsvorschau,
  tatsächliche Callback-Ausführung, UTF-8-Maximalformular und getrennte Byteguards.
- `node tests/radar_layout.js`: native gepinnte ECharts-Geometrie und Filterung,
  beide Ausgaben, 10 Achsen, Auswahl/Alle/Umkehren/leer/Wiederherstellung,
  Anker, Palette, Initialänderung, Refresh/Reorder/Resize/Theme, Raw-Tooltips,
  ARIA und mehrzeilige Warnungen außerhalb der Auswahl.
- Lokale Canvas-Browserprüfung mit synthetischen Daten ergänzt diese Tests.
  Die nachfolgende Live-Abnahme in Symcon-Kachel und IPSView ist durch den Eigentümer bestätigt.

Die erste 16-Gruppen-Probe scheiterte nachweisbar an der früheren Acht-Gruppen-
Grenze. Der Formular-Stresstest zeigte zunächst 1094274 Bytes; erst die
semantikbewahrende Callback-Kompaktierung brachte ihn unter den Ausgabepuffer.
