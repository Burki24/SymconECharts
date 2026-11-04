# ADR 0112: Semantischer Darstellungsvergleich bei der JSLive-Quellenzuordnung

- Status: umgesetzt und lokal abgesichert; Live-Abnahme nach Modulupdate
- Datum: 2026-11-04
- Ergänzt: ADR 0109–0111, Austauschvertrag 1.0

## Anlass

Ein fremder Radar-Export kann dasselbe Zahlenformat wie die ausdrücklich
zugeordnete Quelle beschreiben, aber andere native Metadaten enthalten:
Legacy-Profile liefern weniger Standardfelder, Zahlenbereiche können als
Integer oder Float vorliegen und ein numerisch wirkungsloses `MULTILINE=false`
kann im Export erhalten sein. Der bisherige vollständige JSON-Vergleich
blockiert diese Fälle als `SOURCE_CHANGED`.

## Entscheidung

`EChartsJSLivePresentation` ist eine migrationsbezogene Policy in `libs`.
Sie wird ausschließlich für die anfängliche Zuordnung normalisierter nativer
Darstellungen verwendet. Der gemeinsame Helper bleibt für die unabhängige
Profil-/Darstellungsnormalisierung zuständig und wird nicht lokal verändert.

Für numerische Wertdarstellungen erlaubt die Policy ausschließlich:

- Fehlende, ausdrücklich bekannte Standardfelder der Wertdarstellung; ein
  vorhandener anderer Wert bleibt eine Änderung.
- Numerisch exakt gleiche Integer-/Float-Werte bei `MIN`, `MAX` und den
  Intervallfeldern `IntervalMinValue`, `IntervalMaxValue`, `ConversionFactor`.
  Es gibt keine Rundung, Toleranz, Einheitenumrechnung oder Aufweichung der
  verlustfreien JSON-Ganzzahlgrenze.
- Das wirkungslose `MULTILINE=false` bei numerischen Wertdarstellungen.
  `true`, unbekannte Felder und andere Darstellungsfamilien erhalten keine
  zusätzliche Gleichheitsregel.

Die Standardfelder sind `COLOR=-1`, `CONTENT_COLOR=-1`,
`DECIMAL_SEPARATOR=Client`, `DIGITS=0` für Integer beziehungsweise `2` für
Float, `DISPLAY_TYPE=0`, `ICON=''`, `INTERVALS=[]`, `INTERVALS_ACTIVE=false`,
`MIN=0`, `MAX=100`, `PERCENTAGE=false`, `PREFIX=''`, `PREVIEW_STYLE=1`,
`SHOW_PREVIEW=true`, `SUFFIX=''`, `THOUSANDS_SEPARATOR=''`, `USAGE_TYPE=0`.
Grenzen und Intervallreihenfolgen werden nicht entfernt oder sortiert.

Originale und aktuelle Metadaten werden nicht verändert. Das Zielmodell
erhält weiterhin die vollständige Originalformatierung. Der vollständige
Plan führt `presentationComparisons` mit `exact`, `equivalent` oder
`different` und den angewandten Regeln auf. Ungeprüfte Quellen heißen
`unverified`.

Die Ausführung prüft weiterhin den unveränderten Fingerabdruck des kompletten
aktuellen Quell-Snapshots. Auch eine harmlose Metadatenänderung nach der
Planbestätigung verlangt einen neuen Plan. IDs, Typen, Actions, Archivierung,
Archivtyp und Zählerregeln bleiben strikt. Eigene Vorlagen und Ressourcen
werden dadurch nicht freigegeben. Bestandsinstanzen werden nicht umgestellt.

## Nachweise

`tests/jslive_presentation_equivalence.php` prüft fremde vollständige Pläne,
erhaltene Originaldaten, vollständige aktuelle Fingerabdrücke, bekannte
Standardfelder, numerische Grenzen und blockierende echte Format-, Quellen-
und Archivänderungen. Bestehende Import-, Sicherheits- und Ausführungstests
bleiben Bestandteil der Qualitätssuite. Der bereitgestellte private Export
gehört nicht in Repository oder Fixtures.
