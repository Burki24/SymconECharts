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
`custom` ist der kompatible Standard und verwendet unverändert `LowColor`
und `HighColor`. Die weiteren stabilen Kennungen sind `tempera12`,
`tempera6`, `noaa2023`, `saws2023`, `dwd2023`, `ts2023`, `faz2023`, `usa2023`,
`china2023` und `abc2023`.

Die RGB-Werte sind näherungsweise aus den Farbfeldern der genannten
Abbildungen gewonnen. Reihenfolge und Klassenanzahl werden beibehalten.
Nach einem ersten Laufzeittest wurde die zunächst interpolierende
`continuous`-Abbildung durch eine diskrete `piecewise`-Abbildung ersetzt:
Jede Klasse erhält exakt eine Farbe. Der Bereich zwischen beobachtetem
Minimal- und Maximalwert wird in gleich breite Klassen geteilt. Weder die
historischen festen Temperaturklassengrenzen noch unveröffentlichte
Originaldaten der Anbieter werden behauptet. Die kompakte, nicht
interaktive Legende zeigt alle Klassenfarben sowie die Endwerte mit Einheit,
ohne für jede Klasse einen eigenen ECharts-Legendeneintrag zu beanspruchen.
Die Vorlagen sind für Temperaturwerte in °C gedacht; andere Einheiten werden
nicht automatisch umgerechnet.

Die kanonischen Farbreihen liegen modulnah in `HeatmapColorScales.php`, weil
derzeit nur die Wochentag-Stunden-Heatmap diesen konkreten fachlichen Vertrag
nutzt. PHP erzeugt daraus sowohl SVG-Vorschau als auch Renderer-Daten;
JavaScript besitzt keine zweite, abweichende Palette. Erst eine tatsächlich
weitere nutzende Diagrammfamilie begründet eine Verschiebung nach `libs`.

## Folgen

Instanzen mit `custom` ändern ihr Aussehen nicht. Bereits gewählte Vorlagen
wechseln bei einem Modulupdate von interpolierten Farben zu festen Klassen.
Ein unbekannter Preset-Wert ist eine ungültige Konfiguration; beschädigte
Farbdaten fallen im Renderer auf die individuelle Zwei-Farben-Skala zurück.
Die Formularvorschau verwendet
weiter Beispieldaten. Kachel- und IPSView-Laufzeit werden nach Modulupdate
gesondert live geprüft.
