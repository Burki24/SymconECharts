# ADR 0074: GaugeMulti mit 32 Quellen, Auswahl und Seiten

- Status: Angenommen
- Datum: 2026-10-20
- Entscheider: Burki24
- Ergänzt: ADR 0004, 0036, 0067 und 0073

## Vertrag und Kompatibilität

GaugeMulti erlaubt zwei bis 32 eindeutige numerische Quellen. Minimum,
Maximum, Einheit, Präzision und optionale Darstellungsübernahme bleiben je
Quelle unabhängig. Negative endliche Werte sind weiterhin erlaubt. Keine
allgemeine Normalisierung, gemeinsame Skala oder Archivanbindung.

`EnableSourceSelection` ist additiv und standardmäßig `false`.
Das optionale boolesche Quellenfeld `InitiallyVisible` bedeutet ohne Angabe
`true` und wirkt nur bei aktivierter Auswahl. Schema 1 erhält additive Felder
`gauge.enableSourceSelection` und `items[].initiallyVisible`. Bestehendes
Quellen-JSON, Properties, GUID, Präfix, Idents und Designvorgaben werden nicht
umgeschrieben. Alle Quellen bleiben im Modell, Gateway-Lesezugriff,
Validierung, Referenzabgleich und VM-Abonnement, auch versteckte/andere Seiten.

Listen bis 16 Quellen ohne Auswahl behalten bisherige Vorlagengeometrie und
fehlende Bedienleiste. Bei mehr Quellen oder aktivierter Auswahl bestimmt eine
platz-/schriftabhängige Seitenkapazität den sichtbaren Ausschnitt, höchstens
16 Instrumente. Multi Title, Ringraster, konzentrische Ringe und Wetterstation
bleiben die vier zulässigen Vorlagen. Die Listenreihenfolge bestimmt die Rollen
innerhalb jeder Seite; die erste sichtbare Wetterstationsquelle ist das jeweilige
Hauptinstrument. Gespeicherte Rollen oder Quellen werden nicht umgeschrieben.
Ein einzelnes gewähltes Instrument ist eine gültige Ansicht; die gespeicherte
Liste benötigt weiter mindestens zwei Quellen. Leere Auswahl erzeugt einen
Hinweis statt Beispieldaten. Werttexte und Tooltips behalten ungeklippte
Originalwerte; nur die Geometrie folgt der jeweiligen Skala.

## Gemeinsame Zuständigkeiten

`echarts-series-selection.js` und `echarts-category-navigation.js` aus den
vorangegangenen Familien liefern ID-Auswahl und Seitenanker. Der belegte zweite
DOM-Anwendungsfall rechtfertigt `echarts-source-controls.js` und zugehöriges CSS:
Checkbox-Liste, Alle/Umkehren, beschriftete Seitentasten, Bereich/Leerhinweis und
Warnabstand werden von BarPolar und GaugeMulti geteilt. `EChartsAsset` lädt diese
Ressourcen nur für die beiden Verbraucher. Die Adapter besitzen keine fachlichen
Modelle, Skalen oder Geometrie und kopieren keine synchronisierten Helper.

Auswahl, Seitenanker und automatische Farben bleiben in der geöffneten neuen
Ansicht an IDs gebunden, auch bei Refresh, Umbenennung, Reorder, Themewechsel,
Resize und Entfernung. Anker werden an verbleibende Fenstergrenzen angepasst.
Reload startet mit der gespeicherten Anfangsvorgabe. Zustände bleiben lokal im
Dokument, nicht in Symcon-Properties. Alte kleine Ansichten behalten die
bisherige positionsbezogene Farbzuordnung. Beide Ausgabewege teilen Quellen
und Originaldaten, behalten aber unabhängige Designer und Seitenzustände.

## Vorschau und Budgets

Die bestehende SVG-Vorschau filtert erst nach vollständiger Quellenprüfung und
vor dem maximal vierteiligen Ausschnitt nach Anfangssichtbarkeit. IDs, eigene
Skalen, Werte, Ratio, vollständige Palettenposition und Gesamtzahl bleiben
nachvollziehbar. Ein spät allein sichtbares Instrument zeichnet dasselbe
Skalenverhältnis und dieselbe Farbe; die Quellenzahl ist 1/32, nicht 4/32.
Leere Anfangsauswahl bleibt leer. Drafts ändern keine Properties und lesen
weder Gateway noch Archive. Ungültige Quellen bleiben bearbeitbar.

Das Modell ist auf 900000 JSON-Bytes begrenzt. Die Grenze lässt Platz für bereits
zulässige globale SVG-Zifferblatthintergründe und Zeiger; die vorhandenen
Importbudgets werden nicht verkleinert. Vollständige HTML-Ausgaben sind auf
1 MiB begrenzt. Überschreitung liefert einen begrenzten ausdrücklichen Fehler
mit Status 205 und Hinweis auf Beschriftungen oder Designimporte, keine Kürzung
von Quellen, Skalen oder Assets. Range-Status 202 und Quellenstatus 201 bleiben
unverändert. Runtime, Fremdlizenzen, Gateway und synchronisierte Helper bleiben
unverändert; es gibt keine neue Kompression oder CDN-Ressource.

## Nachweise und separate Befunde

Isolierte Gateway-/Ausgabeintegration prüft 32/33, Altlisten, wiederholtes Apply,
versteckte Aktualisierungen/Fehler, individuelle Skalen und native
Darstellungsübernahme, alle vier Vorlagen/Designer sowie große SVG-Importe und
getrennte Modell-/HTML-Guards. Native Tests verwenden das ausgelieferte Gauge-
Bundle mit zusätzlichem SVGRenderer nur als Headless-Testoberfläche.
BarPolar-Regressionsprüfungen sichern die DOM-/CSS-Extraktion ab. Lokale Canvas-
Prüfungen ersetzen keine Live-Abnahme in Symcon-Kachel und IPSView nach Update.

Bei der Browserprüfung wurde der bereits im Ausgangsstand fehlende
`TitleComponent` im gepinnten Gauge-Subset bestätigt: Die konfigurierte äußere
Diagrammüberschrift wird trotz vorhandener Option nicht gezeichnet. Dieser
Bestandsfehler wird durch Quellen/Seiten weder verursacht noch korrigiert;
eine gesonderte Runtime-Korrektur und Prüfung aller Gauge-Verbraucher bleibt offen.
