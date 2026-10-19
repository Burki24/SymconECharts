# ADR 0063: Optionale Min/Max-Normalisierung aktueller Vergleiche

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0029, 0035, 0038, 0057 und 0060

## Entscheidung und Kompatibilität

Pie, BarCategory, BarPolar und PictorialBar erhalten `NormalizeValues`
(Boolean, Standard `false`). Je Eintrag in `Sources` kommen die additiven
Felder `Minimum` und `Maximum` mit Defaults 0/100 hinzu. Ohne Aktivierung
gelten die bisherigen Einheiten-, Vorzeichen- und Skalierungsverträge;
inaktive Bereiche werden nicht validiert. Bestehende Quellen und Designs
werden nicht umgeschrieben. Unterschiedliche Einheiten und negative
Originalwerte sind im ausdrücklich normalisierten Modus erlaubt.

Jede Quelle wird anhand ihres konfigurierten Bereichs nach
`(Wert - Min) / (Max - Min) * 100` abgebildet. Nur die Geometrie wird auf
0–100 begrenzt. Grenzen stammen weder aus Profilen noch aus beobachteten
Extrema. Sie müssen endlich sein und eine positive, als Float darstellbare
endliche Differenz besitzen. Ungültige aktive Bereiche ergeben einen Fehler.

`libs/EChartsNormalization.php` enthält ausschließlich reine Bereichsprüfung,
Projektion und Originalmetadaten für vier belegte Verbraucher. CalendarPies
vorhandene Normalisierung ist fachliche Referenz, wird aber nicht refaktoriert.
Der gemeinsame Quellenresolver erhält einen optionalen Parameter mit bisherigem
Default. Funnel und andere Verbraucher bleiben unverändert.

## Modell und Darstellung

Schema-v1 wird additiv erweitert: Der Familienblock enthält `normalized`;
`value` bleibt die gezeichnete Größe. Normalisierte Einträge ergänzen
`originalValue`, `minimum` und `maximum`; `unit` und `decimals` behalten
ihre Originalbedeutung. Variablen-IDs bleiben alleinige technische Identität.
Gleiche Namen und Einheiten führen keine Quellen zusammen.

Wertbeschriftung und Tooltip verwenden unveränderte Originalzahlen mit
quelleneigener Einheit und Präzision. Der Tooltip ergänzt normalisierten Wert
und Bereich. Achsen und zugängliche Beschreibungen kennzeichnen den normierten
Vergleich, statt geometrische Werte mit einer Originaleinheit auszugeben.
Der normalisierte Wert ist kein physischer Prozentanteil: Bei Pie ist der
Kreisanteil ein Anteil an der Summe normalisierter Gewichte, nicht an einer
Summe verschiedener Originaleinheiten. Sind alle Gewichte null, entsteht
kein künstlicher gleichgeteilter Kreis.

Sortierung und Navigation verwenden gezeichnete Werte bei stabiler
Quellenidentität. In BarCategory liegt jeder Eintrag auf 0–100; Stapelsummen
dürfen 100 überschreiten. Das ist kein automatisch auf 100 gerechneter Stapel.
PictorialBar und BarPolar verwenden im aktiven Modus eine geometrische Skala
0–100 und ignorieren gespeicherte Rohwertgrenzen, ohne diese zu verändern.
Farbbereiche beziehen sich auf die gezeichnete Skala; gespeicherte Farben und
Grenzwerte bleiben erhalten. Icons, SVGs, Verläufe und Navigation bleiben nutzbar.

Normalisierung gehört zur gemeinsamen Datenkonfiguration, nicht zum
unabhängigen Designer. Beide Ausgabewege und ihre ungespeicherten Vorschauen
verwenden dieselben Bereiche. Min/Max-Editoren sind ausdrücklich als
Normalisierungsfelder benannt. Vorschauen kennzeichnen den Modus, melden
ungültige aktive Bereiche und verändern weder Konfiguration noch Quellwerte.

## Grenzen und Nachweise

Quellenlimits bleiben 16 bei Pie/BarPolar und 100 bei BarCategory/PictorialBar.
Gateway, Referenzen, Abonnements, GUIDs, Idents, Fremd-Runtime und synchronisierte
Helper bleiben unverändert. Ausgabe- und SVG-Importbudgets gelten weiter.
Sankey, Waterfall und andere Mengenmodelle werden nicht mitnormalisiert.

Der kombinierte BarCategory-Grenzfall mit 100 normalisierten Quellen,
120-Zeichen-Namen und fast maximalem SVG-Muster überschritt mit der bisherigen
Whitespace-Minifizierung das 1-MiB-Limit. Der bestehende modulbezogene
`build:bar-category-app` verwendet deshalb vollständige Minifizierung durch
das bereits gepinnte esbuild. Der lesbare Quelltext bleibt maßgeblich; ein
Reproduzierbarkeitstest vergleicht das ausgelieferte `app.min.js` mit dem Build.
Das ersetzt nur die frühere Whitespace-Einstellung aus ADR 0029, nicht die
ECharts-Runtime, Abhängigkeiten oder Budgets.

Tests prüfen fehlende Unterstützung zunächst rot, dann Min/Max, negative und
Nullwerte, außerhalb liegende Originale, verschiedene Einheiten, gleiche Namen,
inaktive Altmodelle und Fehlererholung. Modul-/Gatewaytests sichern beide
Ausgaben, schreibfreie Entwürfe und Budgets; Rendererprüfungen Geometrie,
Labels, Tooltips und Navigation. Lokale synthetische Browserprüfungen ersetzen
keine Live-Abnahme in Symcon 9.0/9.1 und IPSView nach dem Update.
