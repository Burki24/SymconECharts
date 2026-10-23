# ADR 0096: Optionale Sankey-Kantenumkehr bei negativen Werten

- Status: Angenommen
- Datum: 2026-10-23
- Ergänzt: ADR 0051 und ADR 0095

## Vertrag und Kompatibilität

`Sources` erhält je Zeile das optionale Boolean-Feld `ReverseNegative` mit
Default false. Fehlend und ausdrücklich false sind gleich; falsch typisierte
Werte werden mit Verbindungszeile abgewiesen. Bestehende GUIDs, Properties,
Idents, Zeilenreihenfolge und gespeicherte Konfigurationen ändern sich nicht.
Eine Migration oder ein globaler Designer-Schalter ist nicht erforderlich.
Mehrfach verwendete Variablen benötigen je Verbindung die ausdrückliche Auswahl.

Nur bei negativem endlichem Wert und aktivierter Auswahl werden tatsächliche
Start-/Zielknoten vertauscht. Breiten verwenden den positiven Betrag. Positive
Altmodelle bleiben unverändert, auch nach Aktivierung. Negative Null bleibt
unsichtbar und kehrt nicht um. Nichtendliche Werte und Summenüberläufe bleiben
Fehler. Der Betrag wird nach Float-Konvertierung gebildet, auch für Integer-Minima.

Die konfigurierte Struktur bleibt wie bisher ein eindeutiger DAG einschließlich
Nullverbindungen. Danach wird die vollständige tatsächliche Struktur erneut
auf doppelte gerichtete Paare und Zyklen geprüft. Nullflüsse behalten dabei ihre
konfigurierte Richtung. Es gibt keine stillschweigende Zusammenfassung, Teilmodelle,
Negativabschneidung oder Verwendung einer Umkehr zur Reparatur zyklischer
Konfigurationen. Bei unveränderten eindeutigen DAG-Konfigurationen können zwei
umgekehrte Kanten derzeit kein neues identisches Paar bilden, ohne bereits in
der Konfiguration einen Kreis zu bilden; die gemeinsame Prüfung schützt dennoch
beide Grenzen ausdrücklich. Ein umgekehrter Shortcut kann dagegen einen neuen
effektiven Kreis erzeugen und wird kontrolliert abgewiesen.

Im Modell erhalten ausschließlich tatsächlich umgekehrte Flüsse die zusätzlichen
Metadaten `originalValue`, `configuredSource`, `configuredTarget` und `reversed`.
Variablen-ID und Zeitstempel bleiben erhalten. Tooltip zeigt tatsächliche Richtung,
Betragsbreite, signierten Originalwert und konfigurierte Richtung. Wertlabels und
Knotensummen bleiben Beträge, keine Nettobilanz. SVG-Titel erhalten dieselben
Angaben escaped; die Geometrie verwendet ausschließlich das effektive Modell.

Status 205 kennzeichnet fehlerhafte aktuelle Flüsse. Aktivierte Umkehrmodelle
zeigen die konkrete Modelldiagnose in beiden HTML-Ausgaben und löschen die
Geometrie statt einer plausiblen Teilmenge. Altmodelle behalten ihre bisherige
allgemeine Wertfehlermeldung. Korrigierte Werte stellen das Diagramm über die
bestehenden VM-Update-/IPSView-Transportpfade wieder her.

## Gemeinsame Logik und Schutzgrenzen

Sankey-spezifische Richtungs-/DAG-Logik verbleibt zentral im familienlokalen
`SankeyModel`. Konfiguration und effektives Modell verwenden denselben
Topologieprüfer; Live-Ausgaben, Formularprüfung und beide Vorschauen verwenden
denselben Modellbauer. Der statusfreie Vorschauleser wird zwischen Formular-
diagnose und SVG geteilt. Gatewayzugriff, Unit-Policy, Quellidentitäten,
Abonnements und unabhängige Designs bleiben unverändert.

Die geprüften Upstream-Helper erzeugen/escapen SVG und verdrahten/serialisieren
Formulare, besitzen aber keinen Mengenflussvertrag. Sie werden weiterverwendet,
nicht kopiert. Der bereits vorhandene kompakte Callback-Modus des gemeinsamen
`EChartsBarPreviewForm` wird nun auch von Sankey verwendet: der Maximaltest mit
200 signierten Flüssen und langen Schlüsseln überschritt sonst durch wiederholte
Feldnamen im Formular 1 MiB. Keine Änderung an synchronisierten Helpern,
gepinntem ECharts-Kern oder neuen Fremdabhängigkeiten.

2–100 Knoten, 1–200 Quellen, 65536-Byte-Listen, 24-Zeichen-Einheiten und finale
1-MiB-Ausgaben bleiben unverändert. Weder Normalisierung/Umrechnung noch
Rest-/Bilanzknoten gehören zu diesem Schritt.

## Nachweise

`tests/sankey_model.php` begann RED mit der bisherigen Negativablehnung. Die
erweiterten Modelltests prüfen Opt-in, gleiche positive Modelle, negative Null,
endliche Extrema, fehlerhafte Typen und effektive Kreise einschließlich Nullflüssen.
Die fokussierte Integration nutzt echte Module/Gateway mit synthetischem Kernel
und prüft beide Ausgaben, signierte Mehrfachverwendung, erhaltene Abonnements,
Fehler/Erholung, Einheitenprüfung und echte kompakte Entwurfscallbacks ohne
Persistenz oder Statusänderung. Maximale signierte Modelle und Formulare
halten die bisherigen Mengen-/Bytegrenzen ein.
Der Renderer-Test prüft sichere Originalwert-Tooltips und die tatsächliche
Geometrie beider Ausrichtungen/Adapter mit der vorhandenen ECharts-Engine.
Die lokale Canvas-Browserprüfung mit synthetischen Quellen bestätigt beide
Ausgaben, den negativen Originalwert im Tooltip, die umgekehrten SVG-Flüsse
und die übereinstimmende Kreisdiagnose statt Teilgeometrie. Echte Mausradaktionen
scrollten über der Kachel abwärts und über dem IPSView-Testframe aufwärts
(scrollTop 0 → 637 → 0), ohne Diagrammzoom. Die Browserkonsole blieb fehlerfrei.
MCP-/Host-Abnahme der neuen Umkehr bleibt nach Modulupdate separat offen.
