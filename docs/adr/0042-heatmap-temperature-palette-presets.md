# ADR 0042: Historische Temperatur-Farbreihen als Heatmap-Vorlagen

- Status: Angenommen
- Datum: 2026-10-13
- Entscheider: Burki24
- Ergänzt: ADR 0041
- Ersetzt durch: –

## Kontext

Die Heatmap bot bisher nur zwei individuell wählbare Endfarben. Die
Veröffentlichung von Jochen Schiewe, „Farbschemata für Temperaturkarten – Was
liegt zwischen Blau und Rot?“, *KN – Info und Praxis* 3/2023, zeigt in Abb. 1
acht im April 2023 untersuchte Farbreihen und in Abb. 5 die Vorschläge
Tempera-12 und Tempera-6. Es gibt in der PDF keine maschinenlesbare Farb- und
Klassengrenzen-Tabelle. Die acht Anbieterbeispiele sind historische
Stichproben und dürfen nicht als heutige offizielle Anbieterstandards
erscheinen.

Quelle:
<https://kartdok.staatsbibliothek-berlin.de/servlets/MCRFileNodeServlet/kartdok_derivate_00000704/Schiewe_Farbschemata%20f%C3%BCr%20Temperaturkarten_Was%20liegt%20zwischen%20Blau%20und%20Rot_KN-IP_2023.pdf>

## Entscheidung

`EChartsHeatmap` erhält eine optionale Property `ColorScalePreset` für die
Kachel und `IPSViewColorScalePreset` für den unabhängigen IPSView-Designer.
`custom` ist der kompatible Standard und verwendet weiterhin `LowColor`
und `HighColor`. Drei optionale, separat für Kachel und IPSView gespeicherte
Zwischenfarben erweitern diesen kontinuierlichen Verlauf auf höchstens fünf
gleichmäßig verteilte Farbstützpunkte. Nicht gesetzte Zwischenfarben werden
übersprungen; ohne sie bleibt der bisherige Zwei-Farben-Verlauf unverändert.
Die weiteren stabilen Kennungen sind `tempera12`,
`tempera6`, `noaa2023`, `saws2023`, `dwd2023`, `ts2023`, `faz2023`, `usa2023`,
`china2023` und `abc2023`.

Die RGB-Werte sind näherungsweise aus den Farbfeldern der genannten
Abbildungen gewonnen. Reihenfolge und Klassenanzahl werden beibehalten.
Nach einem ersten Laufzeittest wurde die zunächst interpolierende
`continuous`-Abbildung durch eine diskrete `piecewise`-Abbildung ersetzt:
Jede Klasse erhält exakt eine Farbe. Ein weiterer Laufzeittest zeigte, dass
eine Einteilung über den *beobachteten* Min-/Max-Bereich die historischen
Temperaturfarben bei jeder Datenänderung semantisch verschiebt (beispielsweise
1,5 °C in die erste graue NOAA-Klasse). Deshalb erhält jede Vorlage einen
festen Celsius-Bereich; daraus ergeben sich ihre gleich breiten Klassen.
NOAA ist mit −50 bis +50 °C und 20 Klassen zu 5 °C im Text ausdrücklich
belegt. Die Grenzen der sieben anderen historischen Beispiele sind nur
gerundete Ablesungen aus Abb. 1, keine offiziellen Anbieter-Klassengrenzen.
Abb. 5 enthält für Tempera keine Temperaturgrenzen; die hier verwendeten
−20 bis +40 °C sind daher eine dokumentierte Anwendungsvorgabe. Werte
außerhalb des Bereichs werden mit der ersten beziehungsweise letzten
Klassenfarbe dargestellt. Die kompakte, nicht interaktive Legende zeigt
alle Klassenfarben und die festen Endwerte in °C, ohne für jede Klasse einen
eigenen ECharts-Legendeneintrag zu beanspruchen. Es findet keine automatische
Einheitenumrechnung statt; die Vorlagen setzen Temperaturwerte in °C voraus.
Bei aktivierten Zellenwerten wählt die automatische Beschriftungsfarbe für
jede Zelle kontrastreiches Hell oder Dunkel gegen die jeweils berechnete
Füllfarbe. Die vorhandene explizite `ValueColor` überschreibt diese Automatik.

Die kanonischen Farbreihen liegen modulnah in `HeatmapColorScales.php`, weil
derzeit nur die Wochentag-Stunden-Heatmap diesen konkreten fachlichen Vertrag
nutzt. PHP erzeugt daraus sowohl SVG-Vorschau als auch Renderer-Daten;
JavaScript besitzt keine zweite, abweichende Palette. Erst eine tatsächlich
weitere nutzende Diagrammfamilie begründet eine Verschiebung nach `libs`.

## Folgen

Bestehende Instanzen mit `custom` ändern ihr Aussehen nicht; die neuen
Zwischenfarben sind standardmäßig ungesetzt. Die bisherige automatische
Wertbeschriftungsfarbe wird hingegen zellenweise kontrastabhängig; eine
gespeicherte manuelle Farbe bleibt erhalten. Bereits gewählte Vorlagen
wechseln bei einem Modulupdate von an Archivwerte angepassten zu festen
Celsius-Klassen und zeigen daher gegebenenfalls deutlich andere Farben.
Ein unbekannter Preset-Wert ist eine ungültige Konfiguration; beschädigte
Farbdaten fallen im Renderer auf die individuelle Zwei-Farben-Skala zurück.
Die Formularvorschau verwendet
weiter Beispieldaten. Kachel- und IPSView-Laufzeit werden nach Modulupdate
gesondert live geprüft.
