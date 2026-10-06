# ADR 0021: Mehrere Wertachsen der Zeitreihe anordnen

- Status: Angenommen
- Datum: 2026-10-06
- Entscheider: Burki24
- Ergänzt: ADR 0018 und ADR 0020
- Ersetzt: die Begrenzung auf zwei Einheitengruppen aus ADR 0018

## Kontext

Die erste Zeitreihenumsetzung begrenzte eine Instanz trotz bis zu acht
Quellen auf zwei effektive Einheitengruppen. Der reale Test mit Temperatur,
Luftfeuchtigkeit und Luftdruck zeigte, dass diese Grenze die zulässigen
Quellen unnötig einschränkt. ECharts unterstützt mehrere versetzte
Wertachsen. Deren Anordnung muss jedoch kontrolliert bleiben, damit gleiche
Einheiten dieselbe Skala verwenden und mehrere Achsen nicht übereinander
liegen.

Die Formularaktion zum Lesen des aktuellen Modells rief bisher unmittelbar
die öffentliche Datenmethode auf. Bei einer ungültigen Konfiguration wurde
deren kontrollierte Ausnahme dadurch als PHP-Fatalfehler in der Konsole
sichtbar. Eine Diagnoseaktion darf einen erwartbaren Konfigurationsfehler
nicht als unbehandelten Fehler ausgeben.

## Entscheidung

- Eine Zeitreiheninstanz unterstützt weiterhin höchstens acht Quellen und
  damit höchstens acht effektive Einheitengruppen und Y-Achsen.
- Quellen mit derselben effektiven Einheit teilen immer dieselbe Y-Achse.
- Jeder Quelleneintrag erhält `AxisPosition` mit den Werten `auto`, `left`
  oder `right`. Für vorhandene Konfigurationen ohne dieses Feld gilt
  kompatibel `auto`.
- Eine ausdrücklich gewählte Seite gilt für die gesamte Einheitengruppe.
  Widersprüchliche Seiten innerhalb derselben Gruppe sind eine kontrolliert
  ungültige Konfiguration.
- `auto` verteilt Einheitengruppen möglichst gleichmäßig auf links und
  rechts. Ausdrücklich zugeordnete Gruppen werden dabei zuerst berücksichtigt.
- Mehrere Achsen auf derselben Seite werden mit responsiven Offsets
  auseinandergezogen. Der Zeichenbereich reserviert entsprechend Platz; nur
  die erste Achse zeichnet Rasterlinien, damit sich Raster nicht überlagern.
- Das fachliche Datenmodell liefert `position` und `positionIndex` je Achse.
  Der Browser besitzt für ältere Modelle ohne diese Felder weiterhin die
  bisherige Rückfallanordnung: erste Achse links, weitere Achsen rechts.
- Der Diagnoseknopf verwendet eine eigene öffentliche Diagnosemethode. Diese
  gibt Fehler als strukturiertes JSON mit Status und übersetzbarer Meldung
  zurück, statt die Ausnahme bis in das Aktionsskript weiterzureichen. Die
  reguläre Datenmethode behält ihr bisheriges Fehlerverhalten für ihre
  programmatischen Aufrufer.

## Folgen

Anwender können alle zulässigen Datenquellen auch dann gemeinsam darstellen,
wenn jede Quelle eine eigene Einheit besitzt. Bei vielen Achsen wird der
eigentliche Zeichenbereich naturgemäß schmaler; die harte Quellen- und
Punktbudgetgrenze bleibt deshalb bestehen. Die neue Listeneigenschaft ist
ohne Migration kompatibel, weil fehlende Werte als `auto` ausgewertet werden.

Die Änderung erweitert nur das Zeitreihen-Gerätemodul. Archivzugriff,
Punktbudget, Reducer, Echtzeitsemantik und Gateway-Vertrag bleiben
unverändert.
