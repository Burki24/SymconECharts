# ADR 0111: Bestätigte JSLive-Neuanlage und familienbezogene Altmodelle

- Status: Implementiert und lokal geprüft; gemeinsame Live-Abnahme offen
- Datum: 2026-10-31
- Ergänzt: ADR 0109, ADR 0110 und Vertrag 1.0

## Auftrag und Grenzen

Der Eigentümer hat die vollständige Übernahme einschließlich der fehlenden
Zielmodelle für die 40 JSLive-Testvarianten beauftragt. Die reine Vorschau aus
ADR 0110 genügt dafür nicht. Unbekannte Varianten, ausführbare Fremdtemplates,
ungültige Quellen und nicht vollständig übersetzbare Darstellungen bleiben
gesperrt. Eine Bestätigung darf diese Sperren nicht aufheben.

Historische Datenverarbeitung und Gestaltung sind getrennte Nachweise. Auch
eine erfolgreich angelegte und technisch verifizierte Instanz ist kein Beleg
pixelgleicher Darstellung. Abweichungen benötigen einzeln benannte Regeln und
eine an den aktuellen Plan gebundene Bestätigung.

## Persistenz und Wiederanlauf

`EChartsJSLiveJournal` besitzt die versionsgebundene Zustandsmaschine, ein
expliziter Kerneladapter die Schreibgrenze. Das Gateway hält das Journal als
Attribut; normale Modulupdates führen keinen Import aus. Pakete werden für
einen Wiederanlauf erneut ausgewählt, nicht in Debug, Properties oder Journal
dupliziert. Einträge enthalten Identität, Revision, Zieldefinition-Fingerabdruck,
Objektzuordnung, Anlageabsicht, Besitzmarker, geprüfte Ziel-Snapshots und Ergebnis.
Ein Journal ist auf 1000 Einträge und 4 MiB begrenzt; es wird nie still gekürzt.
Eine monotone, lückenlose Sequenz erhält die Revisionsreihenfolge unabhängig von
der kanonischen Sortierung zufälliger Journal-IDs.

Eine Sperre serialisiert Importausführungen. Vor der ersten Erzeugung wird die
Absicht dauerhaft geschrieben. Nach Rückkehr des Erzeugungsaufrufs werden ID
und unveränderter Ausgangszustand gespeichert, dann der Besitzmarker gesetzt.
Ein Absturz zwischen Erzeugung und dieser Speicherung bleibt ausdrücklich
mehrdeutig: Der Wiederanlauf erzeugt kein weiteres Objekt. Eine eigene Aktion
muss das Restobjekt zuordnen oder dessen geprüfte Abwesenheit bestätigen.
Gleiche Namen reichen niemals als Zuordnungsgrund.

Vor Apply wird dessen Absicht persistiert. Ausschließlich moduleigene neue
IPSView-Ausgaben erhalten im Ziel geschützte Eigentumsnachweise einschließlich
stabiler Objekt-/Variablenmetadaten. Sie erlauben nach unterbrochenem Apply die
Übernahme erwarteter neuer Kinder. Fehlender Nachweis oder nachträgliche Änderung
stoppt den Wiederanlauf; dynamische Werte und HTML-Inhalte sind keine Identität.

Jede Konfigurationsänderung erhält einen vorher gespeicherten erwarteten
Folgezustand. Ein Wiederanlauf akzeptiert ausschließlich den zuletzt geprüften
Ausgangszustand oder exakt diesen Folgezustand. Manuelle Änderungen, fehlende
Ziele und geänderte Ausgabeidentitäten verhindern eine Überschreibung.
`ApplyChanges()` darf nur auf dem eigenen unveränderten Neuziel wiederholt
werden. Bestehende Gateways, JSLive-Instanzen, Quellen, Darstellungen, Actions
und Archive sind nie Schreibziele. Automatisches Aufräumen gibt es nicht.

Die dauerhafte Identität ist Installation plus Ursprungsinstanz; Export-UUID
und Erfassungszeit sind keine neue Identität. Eine neue Revision verlangt einen
frischen Vergleich und eine ausdrückliche Entscheidung für eine separate
Neuanlage. Das alte Ziel bleibt erhalten. Wiederholung einer abgeschlossenen
Revision liefert dieselben IDs und wendet ihre Konfiguration nicht erneut an.

## Familienintegration

Die Importausführung bleibt gemeinsam; Berechnung und ECharts-Geometrie bleiben
bei den Familien. Bestehende Konfigurationen behalten ihre bisherigen Defaults,
Grenzen und Ausgabewege. Erweiterungen für Mehrfachbindungen, laufende
Altintervalle und Rohwertregeln sind ausdrücklich versionierte Importmodelle,
keine stillen Änderungen der bisherigen Datenregeln. TimeSeries und BarHistory
verwenden denselben importierten historischen Datenpfad; ein gemischtes
Linien-/Balkenbild bleibt ein Bild in TimeSeries. Die alte Quellen-ID-Eindeutigkeit
bleibt für normale Konfigurationen erhalten; importierte Bindungen haben zusätzlich
ihren unveränderten Bindungsschlüssel. Pie erhält ein getrennt validiertes
Importmodell für mehrere Ringe mit Originalgewichten und eigener Formatierung.
Es aktiviert niemals still eine Normalisierung. GaugeSingle erhält die vier
Altgeometrien über native ECharts-Grafikprimitive; keine CanvasGauges-Engine.

Historisches Radar wird als eigene Familie `EChartsRadarHistory`, Präfix ECRH,
GUID `{A1ABAD81-1878-4F35-A6EE-29B1DE1BCF2D}` implementiert. Pfadfortschritt
und Füllgeometrien liegen getrennt vom Symbolbalken in `EChartsProgress`, Präfix
ECPG, GUID `{FEF19240-5D04-4DE0-9AE9-D55DECBC2BD2}`. Beide folgen demselben
Gateway-Datenfluss und beginnen mit dem importierten Modell. Die bestehenden
Radar-/PictorialBar-Instanzen und deren öffentliche Verträge werden nicht umgestellt.

Die gemeinsame zusätzliche Operation `archive.snapshot.read` ist ein begrenzter,
lesender Transport für explizite Archiv-IDs, Boolean-Rohwerte und unverarbeitete
Roh-/Avg-Zeilen. Sie enthält keine familienbezogene Reduktion. Jede Abfrage
behält höchstens 2000 Zeilen plus Überschreitungserkennung; die Geräte planen
höchstens 63 Abfragen und 32000 Zeilen gemeinsam. Typ-/Zählerpolitikwechsel und
gekürzte Antworten bleiben Fehler. Der bisherige `archive.read`-Vertrag bleibt
unverändert. Es werden weder Archive aktiviert noch Schutzbudgets angehoben.

## Importierte Designer und Ressourcen

Importmodelle erhalten die vollständigen ursprünglichen Einstellungen getrennt
von den regulären Familien-Properties. Beide Ausgabewege bieten zusätzliche
Titel-/Hintergrund-/Schrift- und Sichtbarkeitsanpassungen. Der Standardhintergrund
bleibt transparent wie die ungefüllten Quellseiten; Container können sich
unterscheiden, was der Bericht ausdrücklich nennt. Die vollständigen Quellstile
werden nicht verlustbehaftet in die bisherigen Designer zurückübersetzt.

Abweichend von der synthetischen Voll-SVG-Vorschau in ADR 0036 zeigen importierte
Modelle eine klar bezeichnete schematische Farbkarte und native HTML-Downloads
für die ungespeicherten Formularentwürfe. Diese Downloads nutzen dieselben
echten Quellen-/Archivleser und Renderer wie die Kachel und IPSView. Die
Ausnahme vermeidet eine zweite unzuverlässige Geometrie für alle Altvarianten.
Normale Modelle und ihre Vorschauen bleiben unverändert.

Standardschriften werden ausschließlich nach bekannten SHA-/Familien-/Lizenz-
Manifesten übernommen. Standardtemplates sind inerte Hashreferenzen; eigene
Templates und unbekannte ausführbare Inhalte bleiben gesperrt. Sichere SVGs
verwenden EChartsSvgImage; importierte Strichpfade erhalten zusätzliche Grammatik-
und Geometriebudgets. Es werden keine alten Rendering-Engines ausgeliefert.

## Nachweisstand

Der isolierte Journaltest prüft bestätigte Neuanlage, Wiederholung ohne erneutes
Anwenden, Zieländerungen, getrennte Revisionen, Sperren und Unterbrechungen nach
jedem Persistenz-/Kernelaufruf, einschließlich bei Apply erzeugter Ausgaben.
40 anonymisierte Pakete bestehen native Modelle und beide HTML-Ausgaben,
vollständige Feldpläne, den konkreten Kerneladapter und die tatsächlichen sechs
Modulklassen mit ausgeführten nativen Formularcallbacks. 520 unabhängige
Quellvergleiche prüfen Zeit-/Radarregeln; 120 ECharts-SSR-Renderings und 80
isolierte Chromium-Dokumente prüfen die neue native Laufzeit. Begrenzte
64-MiB-Berichtsprüfung, Quellen-/Ressourcenfehler und unvollständige Archivantworten
sind gesondert abgesichert. Befehle und Grenzen stehen in tests/README.md.

Gemeinsame MCP-Neuanlage/Wiederanlauf nach Eigentümer-Update, direkte
Original-/Ziel-Bildabnahme und die physische native App-/Touchbedienung der
importierten Modelle stehen aus. Frühere Eigentümerabnahmen normaler Modelle
bleiben gültig. Diese ADR hebt die Beta-Sperre des Eigentümers nicht auf.
