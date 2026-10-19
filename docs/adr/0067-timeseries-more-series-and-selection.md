# ADR 0067: Mehr Zeitreihen mit stabiler Reihenauswahl

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0018, ADR 0019, ADR 0021

## Entscheidung und unveränderte Verträge

EChartsTimeSeries erlaubt 1–32 eindeutige Integer-/Float-Quellen und ein
Gesamtpunktbudget von 200–32000. Der Default bleibt 2000. Das Budget wird
deterministisch auf alle konfigurierten Reihen verteilt, abgerundet und pro
Reihe auf 2000 begrenzt. Gateway-Limits, Cache, Roh-/Aggregatsemantik,
Zeitfenster, Einheitenauflösung und Quellidentitäten bleiben unverändert.
Höchstens acht effektive Einheitengruppen bleiben zulässig; diese Prüfung
liegt ausschließlich in TimeSeries, nicht im gemeinsamen Achsenbaustein.
Die 32 Annotationen bleiben quellenbezogen. Keine Archivierung wird aktiviert.
Properties, Idents, GUIDs und vorhandene Quellenlisten werden nicht migriert.

Das optionale Quellenfeld `InitiallyVisible` ist boolesch, fehlt es, gilt
`true`. Das additive Modellfeld heißt `initiallyVisible`; Schema 1 bleibt
kompatibel. Die scrollbare native Legende bietet Einzelwahl, Alle und
Umkehren. Namen bleiben unverändert, IDs sind die technische Identität.
Auswahl, Legendenanker und Zeitzoom bleiben bei Update, Umsortierung und
Resize erhalten. Entfernte IDs werden verworfen; neue IDs verwenden ihren
Anfangswert. Änderungen der konfigurierten Anfangssichtbarkeit gelten für die
betroffene ID. Der flüchtige Zustand wird nicht persistent geschrieben.

Auswahl ist keine Datenfilterung: versteckte Reihen bleiben abonniert, werden
weiter gelesen und behalten ihren Budgetanteil. Ihre Achsen und Annotationen
werden nur optisch entfernt; gemeinsame sichtbare Einheitenachsen bleiben.
Ein Reload beginnt wieder mit der konfigurierten Anfangsauswahl.

## Ressourcen und Zentralisierung

Das vollständige JSON-Modell ist auf 900000 Bytes begrenzt, das gerenderte
HTML-Dokument auf 1 MiB. Der zweite Guard liefert bei Überschreitung einen
expliziten begrenzten Fehlerzustand. Das Punktbudget ist deshalb keine Zusage,
dass jede Kombination aus 32000 präzisen Zahlen und großen Mustern passt.
Abhilfe sind kleineres Punktbudget oder einfachere SVG-Muster; Werte werden
nicht stillschweigend gerundet oder als vollständige Teilmodelle ausgegeben.

Die bereits in Graph belegte verlustfreie Startkompression wird in
`EChartsStartupState` und `echarts-startup.js` zentralisiert. Graph behält
seinen kompatiblen `EChartsGraphOutput`-Adapter. Über 200000 HTML-sicheren
JSON-Bytes wird gzip verwendet; kleine Zustände bleiben direkt. Float-Typen
und HTML-sichere Serialisierung bleiben erhalten. Browser benötigen für
komprimierte Starts `DecompressionStream`; Fehler werden sichtbar gemeldet.
Neuere Vollzustände und Unload brechen veraltete Starts ab. Während des
TimeSeries-Starts eingehende Append-Punkte werden begrenzt gesammelt
(höchstens 32000) und nach dem Start gebündelt übernommen.

Die Vorschau zeigt höchstens vier anfangs sichtbare Reihen und nennt sichtbare
und konfigurierte Anzahl. Alle versteckt bedeutet eine leere Zeichnung, keine
erfundenen Beispieldaten. Farben und Annotationen bleiben zur Original-ID
zugeordnet. SVG-Muster über 12000 Bytes einzeln oder zusammen über 24000 Bytes
werden nur in der Vorschau sichtbar gekennzeichnet vereinfacht. Live-Ausgaben
behalten die Originalmuster. Die ausgelieferte ECharts-Runtime bleibt gleich.

## Nachweise und Grenzen

Integration gegen echten Gateway mit Kernel-Doppeln prüft 32/33 Quellen,
32000/32001 Budget, acht/neun Einheitengruppen, Altlisten, versteckte Updates,
Archive, große SVGs, beide Ausgabegrenzen und exakte entpackte Startzustände.
Native Rendererprüfungen decken gleichnamige Reihen, Auswahl, All/Invert,
Legendenanker, Update/Resize/Reorder, Annotationen und asynchronen Start ab.
Eine lokale Canvas-Browserprüfung mit 32 Reihen und 32000 synthetischen
Punkten ergänzt diese Tests. Die Live-Abnahme nach Modulupdate in Symcon-Kachel
und IPSView bleibt offen; bestehende Installationen wurden nicht geändert.
