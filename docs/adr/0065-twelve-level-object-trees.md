# ADR 0065: Zwölf Nachkommensebenen für die Objektbäume

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0044, 0045 und 0046

## Entscheidung und Reihenfolge

Zuerst wird EChartsTree vertikal auf zwölf Nachkommensebenen erweitert,
anschließend übernimmt EChartsTreeSystemHealth dieselbe Grundlage. Die gewählte
Objektwurzel ist Ebene 0; zwölf Nachkommensebenen ergeben bis zu dreizehn
Objektknoten entlang eines Pfads. Die künstliche ECharts-Wurzel zählt nicht mit.
Die anfänglich geöffneten Ebenen sind weiterhin ein unabhängiges Designmerkmal
mit Werten von 0 bis 12. Tiefe begrenzt Datenumfang, Aufklappen nur die Ansicht.

Bestehende Properties, GUIDs, Statusregeln und Defaults bleiben unverändert:
MaxDepth 3, MaxNodes 80 und InitialDepth 2. Es gibt weder eine Migration noch
automatisches Umschreiben oder Vergrößern gespeicherter Konfigurationen.
Tiefen über zwölf bleiben ungültig. Die beiden Geräte bleiben eigenständig;
der einfache Tree erhält keine Zustandsregeln oder automatische Bewertung.

## Schutzgrenzen und Umfang

Die Knotengrenze bleibt maximal 3000, der Namens-/Regelfilter-Suchlauf maximal
30000 besuchte Objekte. Filter, versteckte Zweige, Kinderreihenfolge und stabile
Objekt-IDs behalten ihren Vertrag. Eine größere Tiefe erweitert diese Budgets
nicht. Die Auswahl umfasst weiterhin nur Kategorien, Instanzen und Variablen;
Links, Skripte und Medien werden nicht neu interpretiert.

Die bisherige Zahl 200000 ist bei diesen beiden Familien keine harte
Modellgrößengrenze: Größere Startzustände werden ab dieser Schwelle gzip-
komprimiert eingebettet. Das bleibt unverändert. Eine neue harte 200000-Byte-
Grenze würde zuvor gültige 3000-Knoten-Konfigurationen ablehnen. Der bestehende
Symcon-Ausgabepuffer von 1 MiB bleibt maßgeblich; Tests prüfen große tiefe
Startzustände einschließlich Dekompression und beider Vorschauen.

## Gemeinsame und getrennte Logik

- TreeHierarchy besitzt die gemeinsame maximale Tiefe, Knotengrenze und
  Kompressionsschwelle. Beide Module verwenden weiterhin seine lesende Projektion.
- EChartsTreePreview zeichnet rekursiv echte Eltern-Kind-Verbindungen für beide
  Familien. Anfangstiefe, Layout, Orientierung und Farben sind Parameter, keine
  eigene Kopie der Zeichenlogik. Health liefert zusätzlich Zustandsfarben.
- echarts-tree.js erhält native Aufklappzustände nach Objekt-ID und Pan/Zoom
  bei Datenaktualisierung und Resize. Neue Wurzel, Layout oder Anfangstiefe
  beginnen eine neue Ansicht. Entfernte IDs werden nicht wiederhergestellt.
  Dieses Script wird nur in die zwei Tree-Ausgaben eingebettet; andere
  Diagrammfamilien werden dadurch nicht vergrößert.
- Zustandsregeln, Rohwertlesung und Aggregation bleiben ausschließlich bei
  TreeSystemHealth. Es entsteht kein universelles Widget- oder Formularframework.

Die SVG-Vorschau ist ausdrücklich schematisch und auf 80 dargestellte Knoten
begrenzt. Sie folgt der konfigurierten Tiefe bis zwölf und der Anfangstiefe,
statt vier flach aufgereihte Knoten als Baum darzustellen. Geschlossene oder
abgeschnittene Gruppen sind erkennbar. Formulardrafts persistieren nichts;
Vorschauen führen weder Archivzugriffe noch schreibende Objektaktionen aus.

Tiefe Beschriftungen müssen mit der ausgelieferten Runtime funktionieren.
Nicht eingebundene optionale ECharts-Komponenten sind kein wirksamer Ersatz
für die Prüfung tatsächlicher Textgeometrie. Vollständige Namen und Werte
bleiben in HTML-sicheren, auf das Widget begrenzten Tooltips erreichbar.

## Ehrliche Zustandsbewertung

Bei konfigurierten Regeln weist Health ausdrücklich darauf hin, dass nur der
ausgewählte Teilbaum bewertet wird. Additive healthScope-Metadaten zählen alle
konfigurierten und die tatsächlich enthaltenen Regelvariablen. Fehlende Regeln
oder Knotenkürzung ergeben einen sichtbaren Teilbewertungshinweis. Tiefe,
Sichtbarkeit und Filter werden dabei nicht durch zusätzliche Vollscans umgangen.
Ausgeschlossene Variablen werden weder gelesen noch nach oben aggregiert.
Eine grüne Wurzel ist keine Gesamtbewertung außerhalb dieses Bereichs.

## Nachweise und Grenzen

Zunächst rote Modell-/Modul-/Rendererprüfungen zeigen die alten Sechsergrenzen.
Regressionen prüfen Ebene zwölf/dreizehn, identische Namen mit eigenen IDs,
Filter, ausgeblendete Zweige, unveränderte Properties bei wiederholtem Apply,
Statuspropagation nur sichtbarer Regeln sowie die bisherigen Ressourcenbudgets.
Echte ECharts-Prüfungen und lokale synthetische Canvas-Browserprüfungen ergänzen
Optionstests: beide Ausgabearten, LR/RL/TB/BT und radial, Auf-/Zuklappen,
Aktualisierung, Resize, Ansichtsanker und entfernte Objekte.
Die tatsächliche Symcon-/IPSView-Abnahme nach Modulupdate bleibt separat.
