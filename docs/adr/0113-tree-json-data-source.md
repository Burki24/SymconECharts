# ADR 0113: JSON-Hierarchien als zusätzliche Datenquelle für EChartsTree

- Status: Vertrag festgelegt; Laufzeitimplementierung und Abnahme offen
- Datum: 2026-11-04
- Ergänzt: ADR 0044, ADR 0046 und ADR 0065
- Kanonischer Vertrag: [Tree-JSON 1.0](../TREE_JSON_CONTRACT.md)

## Anlass

Eine Netzwerktopologie kann bereits in Symcon bekannt sein, ohne der
Eltern-Kind-Struktur des Objektbaums zu entsprechen. Anwender sollen solche
Hierarchien aus einer Datei, als Text, aus einer Stringvariable oder durch
einen ausdrücklichen Skriptaufruf liefern können. Dafür sind keine künstlichen
Symcon-Kategorien oder Links erforderlich.

## Entscheidung

`EChartsTree` erhält eine zusätzliche, ausdrücklich ausgewählte Datenquelle.
Modul-GUID, Präfix `ECTR`, Gateway-Zuordnung und die Designer bleiben erhalten.
Die neue Property `SourceMode` hat den kompatiblen Default `objects`.
`json` verwendet ein gespeichertes Dokument; `variable` liest eine anhand ihrer
ID ausgewählte Stringvariable. Datei und Texteingabe sind zwei Eingabewege
für dasselbe gespeicherte Dokument, keine unterschiedlichen Fachmodelle.

Das versionierte Format `de.burki24.symconecharts.tree` 1.0 beschreibt einen
einwurzeligen Baum aus stabilen String-IDs, Elternbezügen und Anzeigenamen.
Optionale lokale Objektbindungen und statische Werttexte sind ausdrücklich
geregelt. Es importiert keine ECharts-Optionen oder Designs und führt keine
Skripte, Templates oder Netzwerkabfragen aus. JSON-Daten sind kein JSLive-Paket.

Die geplanten öffentlichen Funktionen `ECTR_ValidateTreeJSON` und
`ECTR_SetTreeJSON` verwenden denselben Validator wie Formular und Stringquelle.
Der Setter ist eine ausdrückliche Schreibaktion nur im Modus `json`; er
wechselt keinen Modus und verändert keine Designs oder fremden Objekte.
Ein identischer kanonischer Inhalt bleibt ohne erneutes Anwenden unverändert.
Für häufige automatische Aktualisierungen ist der Stringquellenmodus vorgesehen.

Bestehende Grenzen bleiben bestehen: zwölf Nachkommensebenen, maximal 3000
Knoten, standardmäßig MaxDepth 3 / MaxNodes 80 / InitialDepth 2, bis zu 80
Vorschauknoten, Kompression ab 200000 Bytes und der bestehende
1-MiB-Ausgabepuffer. Der Objektbaum behält seinen 30000er-Suchlauf und alle
Filterregeln. Ein zusätzliches Eingangsbudget von 1 MiB gilt ausschließlich
für JSON-Dokumente. Vollständige Eingangsprüfung erfolgt vor jeder
Ansichtsbegrenzung. Ungültige Aktualisierungen bleiben als Fehler erkennbar;
ein alter gültiger Stand darf nur mit sichtbarer Veraltet-Kennzeichnung
weiter angezeigt werden.

## Gemeinsame Logik und Zuständigkeiten

- Die bestehenden Quellenadapter für Symcon-Objekte und das neue JSON-Format
  bleiben getrennt. `TreeHierarchy::Build()` wird nicht durch einen künstlichen
  Objektbaum oder erfundene Symcon-IDs gespeist.
- Die reine Ansichtsprojektion aus einer validierten Hierarchie wird unter
  `libs` mit belegtem Bedarf gemeinsam genutzt. Der bisherige Objektadapter
  muss dabei seine Reihenfolge, Filter und Begrenzung exakt erhalten.
- Beide Quellen liefern dasselbe Tree-Ausgabemodell. `EChartsTreePreview`,
  `echarts-tree.js`, Designer, Titelabstände, Theme, Transport, Frame-Bridge,
  Kompression und beide Ausgabewege werden wiederverwendet.
- Der strikte JSON-Leser `EChartsImportJson` wird nach einem vorgeschalteten
  kleineren Bytebudget wiederverwendet. Seine bestehenden JSLive-Verträge
  werden nicht aufgeweicht. Formular- und Übernahmelogik verwenden die
  vorhandenen `ConfigurationFormHelper`- und Draft-/Vorschaubausteine.
- Eine JSON-Tree-Policy gehört librarylokal nach `libs`; weder existiert ein
  zweiter Library-Nutzer noch wird ein synchronisierter Helper lokal geändert.
- `EChartsTreeSystemHealth` erhält keinen JSON-Modus und keine geänderten
  Zustandsregeln. Gemeinsame Extraktionen müssen seine Regressionen bestehen.

## Folgen und Abnahme

Die neue Quelle erweitert die Herkunft der Hierarchie, nicht die Diagrammfamilie.
Mehrfach-Eltern und Querverbindungen bleiben Graph-Beziehungen. Links des
Symcon-Objektbaums werden durch diese Entscheidung nicht neu interpretiert.

Vertrag, Schema und synthetisches Beispiel sind eine Spezifikation. Öffentliche
Funktionen, Properties, Fehlerpfade, Laufzeitsnapshot und Formularaktionen sind erst mit
der Implementierung verfügbar. Die Pflichtmatrix im Vertrag umfasst
Modell-, Modul-, Formular-, Renderer- und Live-Nachweise. Dieser Schritt
verändert keine Instanzen, Quellen, Archive, Designs oder Versionsmetadaten.
