# ADR 0116 – Eigene Wertachsen für einzelne Archivreihen

- Status: Angenommen
- Datum: 2026-11-21
- Gilt für EChartsTimeSeries und EChartsBarHistory

## Entscheidung

Beide Familien gruppieren Reihen standardmäßig nach ihrer wirksamen Einheit.
Mit `Sources[].OwnAxis=true` erhält eine Reihe eine eigene Wertachse. Die
Reihenfolge der Quellen bestimmt die Achsenreihenfolge. `EChartsUnitAxes`
liefert den Achsenindex für jede Reihe, auch wenn mehrere Achsen dieselbe
Einheit tragen. Die Quellen-ID bleibt die Identität der Reihe.

Für beide Achsentypen kann der Anwender die Seite automatisch bestimmen lassen
oder ausdrücklich festlegen. Für den Wertebereich stehen automatische
Skalierung, die Grenzen der Variablendarstellung und ein manuelles Minimum
und Maximum zur Wahl. Bei einer gemeinsamen Achse müssen die ausdrücklich
konfigurierten Seiten und Bereiche zusammenpassen.

Jede eigene Achse zählt gegen die bestehende Grenze von acht Wertachsen in
TimeSeries bzw. 16 in BarHistory. Das gilt auch für ausgeblendete Reihen.
Für einen Stapel müssen alle Beiträge auf derselben Skala liegen. Ein
Gruppenwert größer null zusammen mit `OwnAxis=true` ist daher ein
Konfigurationsfehler. Eine ungestapelte Reihe derselben Einheit kann daneben
eine unabhängige Skala verwenden.

Der Abstand zwischen sichtbaren Achsen auf derselben Seite gehört zum jeweiligen
Designer. `ValueAxisSpacing` und `IPSViewValueAxisSpacing` verwenden bei 0 die
bisherige Automatik. Bei 1–120 px begrenzt der verfügbare Platz den gewünschten
Abstand. Eine Seite mit nur einer sichtbaren Achse benötigt keinen Zusatzrand.
Bei horizontalen Balken zählen die Achsen unten und oben jeweils für sich.
