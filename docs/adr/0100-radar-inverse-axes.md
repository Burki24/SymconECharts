# ADR 0100: Optionale inverse Radar-Achsen

- Status: Angenommen
- Datum: 2026-10-24
- Ergänzt: ADR 0053 und 0076

## Vertrag und Kompatibilität

`Axes[].Inverse` ist ein optionaler Boolean mit Default false. Fehlende Felder
und explizites false liefern dasselbe Modell; null, Strings und Zahlen sind
ungültig. Die fachliche Achse bleibt durch `Key` bestimmt und gilt für alle
Gruppen in Kachel, IPSView und Vorschau. Die Option gehört zur Datenzuordnung,
nicht zu den unabhängigen Designern. Properties, GUID, Idents, Schema 1 und
gespeicherte Konfigurationen werden nicht migriert oder umgeschrieben.

Min/Max bleiben endlich und streng aufsteigend. Zuerst wird der Originalwert
wie bisher nur geometrisch begrenzt. Normal gilt
`p = (bounded - Minimum) / (Maximum - Minimum) * 100`; invers gilt `100 - p`.
Das Maximum liegt dann innen, das Minimum außen. Der Clippingzähler prüft
weiterhin den unveränderten Originalwert. Einheit, Präzision, Quellen-ID,
Messzeitstempel, Gruppen-ID und Sichtbarkeit bleiben erhalten. Die Umkehr ist
keine automatische Qualitätsbewertung oder Änderung eines Messwerts.

## Gemeinsame Umsetzung

Der bestehende familienlokale `RadarModel` prüft das additive Feld und liefert
die fertigen Radiusprozente in `percentValues`. Beide HTML-Ausgaben und beide
SVGs verwenden dieselbe Projektion, ohne zweite Umkehr im Renderer. Die
numerische Bereichsprüfung nutzt jetzt den bereits von Parallel und
Normalisierung verwendeten `libs/EChartsValueRange.php`. Gemeinsame Auswahl-,
Formular-, Vorschau- und Ausgabehelper bleiben unverändert; ein neuer
generischer Inversionshelper wäre ohne zweiten fachlichen Nutzer unnötig.

Die [offizielle Radar-Dokumentation](https://echarts.apache.org/en/llms-documents/option-parts/option.radar.md)
bietet für `indicator` Name, Min/Max und Farbe, keine inverse Richtung je Achse.
Die gepinnte 6.1.0-Implementierung setzt deren Extent stets von Innen- zu
Außenradius. Deshalb bleibt die native Skala 0–100; Runtime und Lieferartefakte
werden nicht verändert. Prozentmarkierungen beschreiben immer den Radius.

Inverse Achsen erhalten den lokalisierten Zusatz „(invers)“ in Achsenname,
Tooltip und eigener ARIA-Beschreibung; dies gilt auch bei ausgeschalteter
Gruppenauswahl. Originalbereichstexte laufen von innen nach außen, invers also
Maximum … Minimum. Ausgeschaltete Namens-/Bereichsanzeige bleibt eine
Designentscheidung; Tooltip und ARIA behalten Richtung und Originalwerte.
Die neue Checkbox ist im Zeileneditor und in der Achsenliste verfügbar. Die
vorhandenen echten Listen-Callbacks übertragen auch ungespeichertes false.

Weiterhin 3–10 Achsen, 1–16 vollständige Gruppen, 160 Zuordnungen, höchstens
65536 Bytes je Konfigurationsliste, 200000 Modellbytes und 1 MiB HTML.
Versteckte Gruppen bleiben in Prüfung und Aktualisierung. Unvollständige
Gruppen bleiben ein getrennter Roadmap-Punkt. Keine Archive werden benötigt.

## Nachweise und offene Abnahme

`php tests/radar_model.php` begann RED an der fehlenden inversen Projektion.
Der Test prüft Altstandard/false, negative Bereiche, Enden, Mitte, Clipping,
Originalwerte, Metadaten und strikt abgewiesene Flags.
`php tests/gateway_gauges.php --radar-many` ergänzt 16×10-Mischachsen,
unveränderte Properties/Abonnements, beide Ausgabeadapter und SVG-Geometrien,
ausgeführte Entwurfscallbacks ohne Persistenz/Gatewayzugriff und die letzte Achse.
`node tests/radar_layout.js` prüft Original-Tooltips/-Labels, sichere Texte,
ARIA und tatsächliche native Punktkoordinaten in beiden Formen und Ausgaben.

Die lokale Browserprüfung und vollständige Qualitätssuite werden im
Testdokument festgehalten. Die spätere funktionale MCP-Abnahme auf Symcon 9.1
prüfte reale Quellen und temporäre Grenzwerte, beide SVGs, tatsächlichen
IPSView-WebContent-Push und 16×10 Zuordnungen innerhalb der Ausgabegrenzen.
Die temporären Testobjekte wurden entfernt, die bestehende Konfiguration
einschließlich Designs blieb hashgleich. Der Eigentümer bestätigt anschließend
die inversen Achsen in Kachel und tatsächlichem IPSView. Die kombinierte
MCP-Vorschauseite meldete einen MutationObserver-Fehler; die direkte Radar-
Ausgabe blieb fehlerfrei. Dies ist kein belegter Fehler des Radar-Renderers.
