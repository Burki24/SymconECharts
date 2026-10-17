# ADR 0058: ThemeRiver aus abgeschlossenen Archivmittelwerten

- Status: Angenommen
- Datum: 2026-10-17
- Entscheider: Burki24
- Ergänzt: ADR 0001, 0002, 0005, 0017, 0036 und 0037

## Entscheidung und Wiederverwendung

`EChartsThemeRiver`, Präfix `ECRV`, GUID
`{6767D036-5E2B-4D33-B7F1-C9F1B4376C04}`, ist eine eigenständige
Gerätefamilie. Ein bis acht eindeutige numerische Standard-Archivvariablen
derselben Einheit liefern Stunden- oder Tagesmittelwerte. Negative,
nichtendliche, falsch zugeordnete und gekürzte Antworten werden abgewiesen.
Zähler werden nicht in Mittelwerte umgedeutet.

Verglichen wurden Parallel (abgeschlossene Intervalle), Boxplot (gemeinsame
Einheit und Standardarchive), Candlestick (DST-sichere Zeitfenster) und die
vorhandenen Vorschau-/Ausgabehelfer. Wiederverwendet werden `archive.read`,
Quellenidentität, Variablendarstellungen, Animation, Themen, Titelabstände,
Vorschauverdrahtung, IPSView-Designvererbung und persistenter Transport.
Synchronisierte Helper bleiben unverändert. Zeitfenster und Bandmodell
bleiben lokal; keine neue Gateway-Operation, kein universeller Chartmodus.

`Range` bietet 24 Stunden, sieben oder 30 Tage; `Aggregation` Stunde/Tag.
Stunden sind verstrichene Stunden, Tage lokale Kalendertage in der
Symcon-Zeitzone. Nur abgeschlossene Intervalle werden gelesen, bis zu 720
pro Quelle. Referenzen dienen ausschließlich der Quellenverknüpfung;
ein Minutentimer aktualisiert die vorhandene Ausgabe.

Ein Intervall erscheint nur, wenn alle Quellen einen Wert besitzen.
Sonst bleibt das ganze Intervall leer und ein Hinweis erläutert die Lücke.
Echte Nullwerte bleiben gültig. Keine Nullauffüllung, kein Interpolieren
über fehlende Intervalle, keine Ersetzung durch Momentanwerte.
Identität bleibt die Variablen-ID, auch bei gleichen Anzeigenamen.

## Renderer und Vorschau

Die unveränderte gepinnte ECharts-6.1.0-Basis erhält einen eigenen
Tree-Shaking-Build mit ThemeRiverChart, SingleAxis, Legende, Graphic, Titel, Tooltip,
Aria und Canvas. Offizielle Startseite, llms.txt, ThemeRiver-Dokumentation,
npm-Version sowie die installierte Runtime wurden am 17.10.2026 geprüft.
Apache-2.0 und die vorhandenen LICENSE-/NOTICE-Dateien gelten unverändert.
Herkunft und Integrität stehen in THIRD_PARTY_NOTICES.md.

ECharts ergänzt fehlende ThemeRiver-Punkte selbst mit Null. Deshalb erhält
jeder zusammenhängende vollständige Abschnitt eine eigene Serie mit allen
Quellen. Symmetrische boundaryGap-Werte sichern dieselbe Wert-zu-Pixel-Skala
für alle Abschnitte. Reine Nullabschnitte erhalten keine degenerierte
ECharts-Fläche; ihre Messwerte bleiben im Modell und der Ansicht zugänglich.
Die glatte Verbindung innerhalb eines Abschnitts ist eine grafische
Darstellung der Intervallmittelwerte, keine zusätzliche Archivmessung.
Tooltips nennen immer das tatsächliche Archivintervall und Originalwerte.

Die native ThemeRiver-Ansicht von ECharts 6.1.0 übergibt bei Labels keinen
`labelFetcher` und ignoriert deshalb deren Formatter. Native Bandlabels
bleiben aus: Optionale begrenzte Graphic-Texte verwenden die Anzeigenamen
statt der für Daten/Legende notwendigen ID-Schlüssel. Kleine Bänder lassen
ihre Beschriftung aus; die Legende bleibt verfügbar. Es werden weder
ECharts-Quelldateien noch interne Rendererobjekte verändert.
Reine Nullausgaben erhalten leere, benannte Serien für die Legende, keine
erfundenen Epsilonwerte. Der Tooltip wird anhand der tatsächlichen
Zeigerposition und des Originalintervalls gesteuert, nicht anhand des
standardmäßig zuletzt gebundenen nativen ThemeRiver-Datenpunkts.

Beide Designer besitzen Theme, Tooltip, Banddeckkraft, Legende, Beschriftung,
Schriftgrößen/-farben und Animation; IPSView erbt oder gestaltet unabhängig.
Die SVG-Vorschau verwendet gekennzeichnete synthetische Werte ohne Archivzugriff.

## Nachweise und Grenzen

Geprüft werden Datenfenster einschließlich DST, negative/ungültige Daten,
Lücken, Nullwerte, gleiche Namen, Transport, Vererbung, Vorschauentwürfe,
Output-Limit sowie echter ECharts-Optionsaufbau und Bandgeometrie.
Lokale Browserprüfungen ersetzen keine Live-Abnahme in Symcon und IPSView.
Diese Live-Abnahme erfolgt erst nach dem Modulupdate durch den Eigentümer.
