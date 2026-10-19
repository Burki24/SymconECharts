# ADR 0061: Benutzerdefinierte relative Archivzeiträume

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0018, 0050, 0054, 0056, 0058 und 0059

## Umfang und Vertrag

Boxplot, Parallel, Candlestick, ThemeRiver und StateTimeline erhalten neben
ihren unveränderten Voreinstellungen 24 h / 7 d / 30 d die Auswahl `custom`.
`CustomRangeValue` (Integer, Standard 7, aktiv gültig 1–1000) und
`CustomRangeUnit` (String, Standard `day`, `minute|hour|day|week`) übernehmen
den bereits bei TimeSeries, BarHistory und Scatter vorhandenen Vertrag aus
`EChartsArchiveQuery`. Minuten sind 60 Sekunden, Stunden 3600, Tage 86400 und
Wochen 604800 Sekunden. Dies sind relative rollierende Zeitfenster, keine
absoluten historischen Von-/Bis-Daten. Unbenutzte Custom-Einstellungen machen
ein gültiges voreingestelltes Fenster nicht ungültig.

Die Properties sind additive Defaults; Quellen, vorhandene Bereiche, Designer,
GUIDs und Idents werden weder migriert noch überschrieben. Kachel und IPSView
teilen in diesen fünf Familien weiterhin ihre Zeitkonfiguration. Die unabhängigen
Zeitkonfigurationen bereits bestehender Familien bleiben unverändert.
Kalender-, CalendarPie- und Heatmap-Layouts behalten ihre eigenen Zeitmodelle.

## Fachliche Zeitgrenzen und Ressourcen

Boxplot und Parallel verwenden den bestehenden Queryplaner: das Fenster endet
an der letzten abgeschlossenen Aggregationsgrenze. Nur vorhandene vollständig
abgeschlossene Aggregate mit Zeitstempel im Fenster werden verwendet; eine
angebrochene führende Stunde bzw. ein führender Tag wird nicht hineingeschätzt.
Boxplot behält Stundenmittelwerte/R7/Min-Max, Parallel exakt gemeinsame
Zeitstempel und seine explizite Kürzungsanzeige. Ein Minutenfenster macht aus
diesen Daten weder Rohwerte noch neue Minutenaggregate.

Candlestick und ThemeRiver hatten dieselbe lokale Fensterplanung dupliziert.
`EChartsArchiveQuery::CompletedIntervals` übernimmt diese Planung, ohne die
vorhandene `Resolve`-Semantik anderer Familien zu verändern. Alte Tagesvorgaben
zählen weiterhin lokale Kalendertage. Neue Custom-Dauern sind verstrichene Zeit:
das Ende liegt an der letzten abgeschlossenen Grenze, der Beginn wird auf das
erste vollständig enthaltene Stunden-/Tagesintervall aufgerundet. Deshalb passt
ein vollständiger 23-Stunden-Tag in 24 Stunden, ein 25-Stunden-Tag nicht. Sehr
kurze Custom-Dauern sind gültig, aber leer, und lösen keine Archivabfrage aus.
Wiederholte Herbststunden behalten ihren tatsächlichen UTC-Offset.

Die Intervallliste bleibt auf 720 Einträge begrenzt. Eine Überschreitung wird vor
Archivzugriff mit einem Hinweis auf kürzere Dauer oder längeres Intervall
abgewiesen. Candlestick berechnet weiterhin echte Rohwert-OCLH, ThemeRiver
nichtnegative gemeinsame Mittelwerte ohne Nullauffüllung. StateTimeline nutzt
dagegen rohe Zustandswechsel samt Vorgänger und endet weiterhin zum aktuellen
Abfragezeitpunkt; es gibt dort keine Aggregations- oder abgeschlossene-Stunden-Grenze.

Alle Datenbudgets bleiben erhalten: Boxplot 1000 Stundenmittel je Quelle und
10000 insgesamt; Parallel 1–1000 Linien und begrenzte Antworten mit Hinweis;
Candlestick 63 Abfragen/64000 Rohwerte; ThemeRiver 1000 Aggregate je Quelle;
StateTimeline 2000 Rohwerte je Quelle plus Vorgänger, 16000 insgesamt und
250000 Modellbytes. Eine größere erlaubte Eingabe ist keine Lastfreigabe für
beliebig lange oder dichte Archive. Keine automatische Vergröberung einer
Statistik und keine unbemerkte Kürzung werden eingeführt.

## Formular und Nachweise

Die neuen Steuerelemente stehen beim Zeitraum vor den Designern. Anfangszustand
und `UpdateTimeRangeForm` steuern die Sichtbarkeit der Custom-Felder; bestehende
Vorschauaktionen bleiben angehängt. Drafts ändern weder gespeicherte Properties
noch Archivkonfiguration und führen keine Archivabfragen aus. Sichtbare Texte
sind deutsch übersetzt. Vorschauen bleiben ausdrücklich synthetisch; wo sie
Zeitangaben darstellen (StateTimeline), berücksichtigen sie die Draft-Dauer.

Die vorher fehlende Unterstützung wird zunächst durch rote Tests nachgewiesen.
Neue Fenster-/Integrationstests prüfen Minuten, Stunden, Tage, Wochen, Grenzen,
leere Fenster, Zeitzone/DST, echte Gatewayanfragen, Vorgänger, unveränderte
Statistik und Budgets, Cacheidentität, Sichtbarkeit und beide HTML-Ausgaben.
Bestehende Tests laufen im Gesamtstand erneut. Live-Konfigurationen werden bei
dieser Implementierung nicht geändert. Die reale Formularbedienung und die
neuen Zeitbereiche in Symcon-Kachel und IPSView bleiben nach Modulupdate durch
den Eigentümer zu bestätigen; lokale Tests ersetzen diese Prüfung nicht.
