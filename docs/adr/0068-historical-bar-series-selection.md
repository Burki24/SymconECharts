# ADR 0068: Mehr historische Balkenreihen mit gemeinsamer Reihenauswahl

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0030, ADR 0031, ADR 0033, ADR 0067

## Entscheidung und Kompatibilität

EChartsBarHistory erlaubt 1–32 eindeutige numerische Quellen in der bestehenden
geordneten `Sources`-Liste und 200–32000 Gesamtpunkte. Der Default bleibt 1000,
das Gateway-Limit 2000 je Anfrage/Reihe. Das Budget wird über alle konfigurierten
Quellen geteilt und abgerundet, unabhängig von der aktuellen Reihenauswahl.
Eine vollständige 32-Reihen-Ansicht hat bei Budget 32000 höchstens 1000 Punkte
je Reihe. Zusätzliche Quellen vervielfachen das Budget nicht automatisch.

Das optionale boolesche Feld `InitiallyVisible` fehlt in alten Quellenlisten
und gilt dann als `true`. Im Schema-1-Modell ist `initiallyVisible` additiv.
Modulidentität, Properties, gespeicherte Listen und ursprüngliche Budgets
werden nicht umgeschrieben. Bis zu 16 effektive Einheitengruppen bleiben wie
bei der bisherigen 16-Quellen-Grenze zulässig. Diese familienbezogene Grenze
wird nicht mit der achtachsigen TimeSeries-Grenze gleichgesetzt.

Eigenständige Zeitstempel, Lücken, negative Werte, Roh-/Auto-/feste
Aggregationsmodi und Reducer bleiben unverändert. Rohwerte werden nicht
heimlich aggregiert; Daten werden weder interpoliert noch zeitlich angeglichen.
Balken am selben Zeitpunkt bleiben nebeneinander und werden nicht gestapelt.
Unabhängige Zeit-/Designeinstellungen für IPSView bleiben erhalten.

## Auswahl und gemeinsame Verantwortung

Die native scrollbare Legende bietet Einzelwahl, Alle und Umkehren. Gleiche
sichtbare Namen bleiben erlaubt und technisch über Variablen-IDs getrennt.
Die gemeinsame flüchtige Auswahllogik von TimeSeries und BarHistory liegt in
`libs/echarts-series-selection.js`: Auswahl erfassen, anhand neuer IDs und
Anfangssichtbarkeit abgleichen, Legendenanker wiederherstellen. Ein explizit
geänderter Anfangswert gilt nur für die betroffene ID. Entfernte IDs werden
vergessen. Resize und Aktualisierung erhalten Auswahl und den getrennten
Zeitzoom; Reload verwendet wieder die konfigurierten Anfangswerte.

Unsichtbare Reihen bleiben im vollständigen Modell und werden weiterhin
archivseitig gelesen; ihre Budgetanteile werden nicht umverteilt. Unbenutzte
Achsen werden optisch ausgeblendet und verbleibende Offsets kompakt angeordnet.
Die Balkenbreite berücksichtigt die sichtbare Reihenzahl, Zeitzoom die
tatsächliche Punktdichte. Eine anfangs versteckte Einzelreihe besitzt eine
Legende zum Einblenden; die bisher sichtbare Einzelreihe bleibt ohne Legende.
Geometrie, Darstellung und Validierung bleiben in den Diagrammfamilien.

## Ressourcen und Vorschau

Das serialisierte Modell ist auf 900000 Bytes begrenzt, das vollständige
HTML-Dokument auf 1 MiB. Große Texte, Präzision und SVGs können vor dem
Punktbudget an diese Grenze stoßen. Ein klarer Fehler verlangt ein kleineres
Budget oder einfacheres SVG; keine stille Rundung oder Datenentfernung.
Der vorhandene `EChartsStartupState` komprimiert Startzustände über 200000
HTML-sicheren JSON-Bytes verlustfrei. `echarts-startup.js` verwirft überholte
Entpackergebnisse bei neueren Vollzuständen oder Unload. Dafür benötigt der
Browser gzip-`DecompressionStream`. Runtime und Gateway werden nicht erweitert.

Die begrenzte Vorschau zeigt höchstens drei anfangs sichtbare Quellen und
nennt Ausschnitt/Gesamtzahl. Originale Farbindizes und Achsenseiten bleiben
erhalten. Vollständig ausgeblendet bedeutet keine erfundenen Beispielbalken.
Die bestehende SVG-Musterskizze bleibt schematisch; Live-Muster bleiben vollständig.

## Nachweise und verbleibende Lücken

32/33 Quellen, 32000/32001 Punkte, Altlisten/Defaults, 16/17 Einheitengruppen,
versteckte Quellen, echte Gateway-Archive mit Kernel-Doppeln, unabhängige
Zeitstempel, Reducer sowie beide komprimierten Ausgaben werden integriert
geprüft. Native ECharts-Tests sichern Auswahl, gleiche Namen, Navigation,
Zeitzoom, Update/Reorder/Resize, Achsen und Start-Rennen. Große SVGs und
explizite Größenfehler ergänzen die Lastfälle. Eine lokale Canvas-Prüfung
ersetzt keine erneute Live-Abnahme in Symcon und IPSView nach Modulupdate. Die
anschließende funktionale Abnahme ist durch den Eigentümer bestätigt.
Live-Konfigurationen, Quellwerte und Archive werden nicht verändert.
